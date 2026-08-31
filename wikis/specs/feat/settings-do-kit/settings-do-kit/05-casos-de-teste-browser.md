# Casos de Teste de Browser — Settings do kit em `/admin`

> Runtime: `pest-plugin-browser` 5.0.1 (Playwright 1.62.1). O plugin sobe o próprio servidor, in-process, em porta aleatória.
> Comando: `composer test:browser` (embute `npm run build` e `view:cache`) — em série, **nunca** `--parallel`.
> Arquivo: `tests/Browser/ConfiguracoesDoKitTest.php`

## Por que existem CT-B nesta feature

A tabela `## Superfície de UI` do PRD é o gatilho; o critério é o que **só o navegador prova**. Nesta tela há três coisas nessa condição, e nenhuma delas é asserível por componente Livewire:

1. **As abas são JavaScript.** `Tabs` do Filament troca o painel visível no cliente. Um teste de componente enxerga o schema inteiro renderizado e passa mesmo com a troca de aba quebrada — o campo está no DOM, só não é alcançável.
2. **O `ColorPicker` é Alpine.** Ele monta um seletor visual no cliente. Um `assertSchemaComponentExists` prova que o componente está no schema, não que ele inicializa.
3. **O `FileUpload` é FilePond.** Mesmo caso: o campo existe no schema e o widget pode não subir.

E há um risco de integração próprio desta tela: ela é a **primeira** `SettingsPage` do kit. O layout dela vem de um pacote que nunca renderizou nada aqui, no meio de um painel com cerca de 30 plugins. Se algum deles gritar no console nesse contexto, o HTML vem íntegro, o status é 200, e **nenhum** caso do `04` fica vermelho.

## Pré-requisitos

- [x] `npm run build` — embutido no `composer test:browser`
- [x] `php artisan view:cache` — embutido; `.ai/rules/testes-browser.md` mediu que sem ele o primeiro cenário estoura os 45 s por compilação, não por comportamento
- [x] `tests/Browser/Screenshots` já ignorado no git
- [x] Autenticação por `actingAs(usuarioDoKit('admin'))` **antes** do `visit()` — login pela tela custa cerca de 20 s por cenário, e o único caminho real de login já tem cenário próprio em `tests/Browser/PerfisTest.php`
- [x] Aquecimento pelo kernel no `beforeEach` (um `get()` da mesma rota), pelo motivo de DT-06 registrado em `.ai/rules/testes-browser.md`: o `view:cache` cobre as Blade do repositório, não os componentes Livewire do Filament, e rodar este arquivo isolado colocaria cerca de 25 s de compilação dentro do cronômetro do Playwright

## Seletores

O kit não tem `data-testid` (dívida conhecida, registrada em `.ai/rules/testes-browser.md`). O que existe:

| Elemento | Seletor | Já existe? |
|---|---|---|
| campo do nome da aplicação | `#form\.nome_da_aplicacao` | sim — `id` gerado pelo Filament a partir do `statePath` |
| campo da paginação | `#form\.paginacao_padrao` | sim |
| seletor de cor livre | `#form\.cor_primaria_hex` | sim |
| aba (gatilho) | texto visível do rótulo | ⚠️ **premissa** — os rótulos das abas não estão no requisito |
| botão de salvar | texto `Salvar` (tradução pt_BR do plugin, em `vendor/filament/spatie-laravel-settings-plugin/resources/lang/pt_BR/pages/settings-page.php`) | sim |

**Consequência do aviso**: nenhum cenário navega por **texto de aba** como oráculo. CT-B01 alterna pelas abas acionando o gatilho e afirma sobre a **visibilidade do campo** que só existe naquela aba — o campo é do requisito, o rótulo da aba é premissa. Se o usuário renomear as abas, nada aqui fica vermelho por isso.

---

## CT-B01: as abas trocam de painel e o seletor de cor inicializa

**Por que browser e não Livewire**: a asserção é sobre **visibilidade** de campo depois de um clique processado no cliente. `assertSchemaComponentExists` passa com as duas abas no DOM ao mesmo tempo — que é justamente o estado em que a troca de aba está quebrada.

```gherkin
# language: pt

  Cenário: [CT-B01] a tela abre na primeira aba e o clique em outra aba troca os campos visíveis
    Dado o administrador da aplicação autenticado no painel de administração
    Quando ele abre a tela de configurações do kit
    Então o campo do nome da aplicação está visível
    E o campo da paginação não está visível
    E o seletor de cor livre está montado na tela
    Quando ele aciona o gatilho da aba de tabelas
    Então o campo da paginação está visível
    E o campo do nome da aplicação não está visível
    E nenhum erro de JavaScript foi registrado no console
```

> Dois `Quando` no cenário, e é estouro **justificado**: o comportamento sob teste é a *troca*, que só existe como sequência. Separar em dois cenários faria o segundo repetir a abertura inteira da tela — cerca de 10 s — para provar metade da mesma coisa.

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | autentica e abre | `actingAs(usuarioDoKit('admin'))` e `visit('/admin/configuracoes-do-kit')` | tela do formulário |
| 2 | primeira aba ativa | `assertVisible` no campo do nome | campo do nome na tela |
| 3 | outra aba oculta | `assertNotVisible` no campo da paginação | campo da paginação fora de vista |
| 4 | o seletor de cor montou | presença do gatilho de cor do Filament ao lado do campo | seletor visual presente |
| 5 | troca de aba | `click` no gatilho da aba de tabelas | painel troca |
| 6 | inverte a visibilidade | `assertVisible` na paginação e `assertNotVisible` no nome | campos trocados |
| 7 | console | `assertNoJavaScriptErrors()` | sem erro |

**`assertNoJavaScriptErrors()` e não `assertNoSmoke()`**: a tela é montada por um pacote de terceiro (`filament/spatie-laravel-settings-plugin`) dentro de um painel com cerca de 30 plugins. `assertNoSmoke()` reprova em qualquer `console.log` de vendor, e a suíte ficaria vermelha por dívida alheia — é o mesmo raciocínio do CT-B01 de `tests/Browser/BoasVindasTest.php`.

**Sem `assertPathIs`**: o cenário não navega — o clique na aba é troca de painel no cliente, sem mudança de URL. **Sem `wait()`**: o plugin reexecuta cada asserção até o teto de 45 s de `pest()->browser()->timeout()`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| MB1 | os campos são postos direto no `components()`, sem `Tabs` — a tela fica com 21 campos numa coluna | CT-B01 (passo 3: o campo da paginação estaria visível de saída) |
| MB2 | `Tabs` sem `columnSpanFull()` — o kit já tem essa armadilha nas guidelines ("Grid, Section, Fieldset e Repeater não ocupam toda a largura por default"), e o resultado é o componente de abas em meia coluna com o resto do formulário ao lado | CT-B01 (passo 3 — o campo da outra aba fica visível na segunda coluna) |
| MB3 | `ColorPicker` trocado por `TextInput` "porque grava a mesma string" | CT-B01 (passo 4) |
| MB4 | um asset conflitante do painel derruba o Alpine da tela e nenhum campo reage ao clique | CT-B01 (passos 5 a 7) |

---

## CT-B02: o erro de validação aparece na aba do campo

**Por que browser e não Livewire**: `assertHasFormErrors` — que CT-09 já usa, no `04` — prova que a validação **disparou**. Ela não prova que o usuário **vê** o erro. Com o campo inválido numa aba não ativa, o Filament precisa ativar aquela aba no cliente; se não o fizer, o formulário recusa a gravação e a tela não mostra motivo nenhum. É um beco sem saída silencioso, e o `04` fica verde nele.

```gherkin
# language: pt

  Cenário: [CT-B02] salvar com um campo inválido em outra aba revela o erro naquela aba
    Dado o administrador da aplicação autenticado no painel de administração
    E que ele está com a tela de configurações do kit aberta na primeira aba
    Quando ele apaga o nome da aplicação, aciona a aba de tabelas e salva
    Então a mensagem de erro do nome da aplicação está visível
    E o campo do nome da aplicação está visível
    E nenhum erro de JavaScript foi registrado no console
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | autentica e abre | `actingAs(usuarioDoKit('admin'))` e `visit('/admin/configuracoes-do-kit')` | tela do formulário |
| 2 | invalida o campo | `fill` do campo do nome com string vazia | campo vazio |
| 3 | sai da aba | `click` no gatilho da aba de tabelas | painel troca |
| 4 | salva | `press('Salvar')` | validação dispara |
| 5 | o erro é visível | `assertSee` da mensagem de obrigatório **e** `assertVisible` no campo do nome | erro e campo na tela |
| 6 | console | `assertNoJavaScriptErrors()` | sem erro |

**A âncora dupla do passo 5 é o ponto do cenário**: `assertSee` sozinho passa com a mensagem no DOM dentro de uma aba invisível — que é exatamente o defeito. O `assertVisible` do campo é a asserção que prova que o usuário chegou até ele.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| MB5 | `Tabs` sem o comportamento de ativar a aba do campo com erro, e o usuário fica sem saber por que não salva | CT-B02 (passo 5) |
| MB6 | obrigatoriedade declarada só no banco e não no campo — não há erro de formulário para exibir, e o salvamento estoura 500 | CT-B02 (passos 5 e 6) |
| MB7 | o botão de salvar sai do rodapé do formulário e o `press('Salvar')` não submete nada | CT-B02 (passo 4) |

---

## Cogitado e cortado

O perfil da área de gravação é `padrão` (teto: 1 happy path) e o da área de fonte da verdade é `completo` (teto: 1 happy path mais 1 erro visível). Os dois cenários acima consomem o teto do perfil mais alto. O que foi cogitado e ficou fora:

| Cenário cogitado | Por que foi cortado |
|---|---|
| o nome salvo aparece no cabeçalho dos três painéis | provável em HTTP com um `get()` e um `assertSee` — não precisa de navegador. Fica no `04`, como parte de CT-30 |
| a cor livre pinta o painel | a paleta entra na resposta como propriedade CSS dentro de `@filamentStyles`; asserir o valor `oklch(...)` no HTML prova o registro, e prova mais que um screenshot. Fica no `04` (CT-07). Provar o **pixel** exigiria screenshot e olho humano, e `.ai/rules/testes-browser.md` é explícito: para defeito de cor não há saída barata |
| o favicon aparece na aba do navegador | o `<link rel="icon">` está no HTML; o navegador não acrescenta informação. Coberto por CT-26 e por asserção HTTP |
| o upload do favicon por FilePond | mata o mesmo mutante que CT-10 (disco e visibilidade), que roda em milissegundos com `Storage::fake()`. E `attach()` num FilePond dentro de aba não-ativa é fonte conhecida de instabilidade |
| a tela em tema escuro | `assertSee` não valida tema (passa com texto branco em fundo branco). O eixo de tema já tem cenário próprio e mais forte em `tests/Browser/TemaEscuroTest.php`, que cobre o painel; esta tela não introduz cor de autoria própria |
| smoke da tela junto com as outras 52 | **já acontece de graça**: `/admin/configuracoes-do-kit` entra em `telasDoKit()['admin']` (passo 9 do PRD) e `tests/Browser/TelasDoKitTest.php` a visita no lote. Escrever de novo aqui seria cobertura duplicada |
| o `panel_user` recebendo 403 na tela | `assertNoJavaScriptErrors()` **passa** numa página de 403 — o cenário não provaria nada no navegador. É CT-15, por componente |

---

## Roteiro de Validação: Desenhado × Implementado

Preenchido no step 7 da `feature-wiki`, depois de rodar os CT-B.

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | tela em `/admin/configuracoes-do-kit`, quatro abas | igual — a rota abre e o clique em outra aba troca os campos visíveis (a contagem de abas não é asserida) | sim | `tests/Browser/ConfiguracoesDoKitTest.php` · `it('troca os campos visiveis ao acionar outra aba, com o seletor de cor montado')` |
| 2 | aba Identidade: nome, cor por seleção, cor livre, logo, favicon, arte do login | primeira aba, com `nome_da_aplicacao` e `cor_primaria_hex` (Alpine) montados | não verificado — o CT-B01 só assere nome e cor livre; logo, favicon e arte sem CT-B | `tests/Browser/ConfiguracoesDoKitTest.php` · `it('troca os campos visiveis ao acionar outra aba, com o seletor de cor montado')` (parcial) |
| 3 | aba E-mail: mailer, host, porta, esquema, usuário, senha, remetente | — | não verificado | nenhum CT-B abre a aba E-mail |
| 4 | aba Tabelas: paginação, listras, persistência, colunas arrastáveis | aba alcançável por clique, `paginacao_padrao` visível | não verificado — só `paginacao_padrao` é asserido | `tests/Browser/ConfiguracoesDoKitTest.php` · `it('troca os campos visiveis ao acionar outra aba, com o seletor de cor montado')` (parcial) |
| 5 | aba Kit: hub, rótulo da organização (singular e plural) | — | não verificado | nenhum CT-B abre a aba Kit |
| 6 | campos de SMTP visíveis só com o mailer `smtp` | — | não verificado | nenhum CT-B; comportamento coberto só por componente no `04` |
| 7 | senha com revelação, nunca em claro no HTML inicial | senha zerada em `mutateFormDataBeforeFill()` + `dehydrated` condicional (commit `768ea1e`, QA-02) | não verificado — sem CT-B; provado por componente | `tests/Kit/ConfiguracoesDoKitTelaTest.php` · `it('nao serializa a senha de smtp no html da tela')` |
| 8 | item no menu do painel de administração | — | não verificado | nenhum CT-B abre o menu |

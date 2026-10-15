# Casos de Teste de Browser — feat/configuracoes-secoes-colapsaveis: aba Aplicação, seções colapsáveis com ícone e carregamento adiado das abas

> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor.
> Costura: `browser` — linha "Navegador" de `## Costuras de Teste` do `04`
> Comando: `composer test:browser` (embute `npm run build` e `view:cache`), ou `vendor/bin/pest --testsuite=Browser --filter=ConfiguracoesSecoesColapsaveis` — a suíte `Browser` está declarada no `phpunit.xml`. Em série; nunca `--parallel` (`.ai/rules/testes-browser.md`).
> Teto do perfil padrão: 1 CT-B. **Estouro justificado (gate vence teto)**: cada um dos seis é o único matador de mutantes próprios (tabelas abaixo). Três são os que a `## Superfície de UI` nomeia como só-navegador (CT-B01, CT-B02, CT-B03); CT-B04 é o invariante de Q3 com a partição válida da URL; CT-B05 fecha ADV-04/ADV-25 (erro em seção colapsável aninhada, numa aba nunca carregada); CT-B06 fecha ADV-07 (o `live()` do remetente).
> **Débito declarado**: CT-B06 nasceu no fechamento da rodada 2 (a última) e não passou por revisão adversarial independente.

## Pré-requisitos
- [ ] `npm run build` e `php artisan view:cache` executados (`composer test:browser` faz os dois)
- [ ] `tests/Browser/Screenshots` no `.gitignore`
- [ ] `beforeEach`: `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])`, `$this->actingAs(usuarioDoKit('admin'))` e o aquecimento pelo kernel `$this->get('/admin/configuracoes-da-aplicacao')` (molde `tests/Browser/ConfiguracoesDoKitTest.php`)
- [ ] Sem `assertNoJavaScriptErrors()`: `ColorPicker` dentro de `Tabs` emite `ResizeObserver` no headless do CI (`.ai/rules/testes-browser.md`) — a rule vence o template
- [ ] Nunca `wait(n)`: o plugin reexecuta cada asserção até o teto
- [ ] Arranjo de settings por `gravarConfiguracao()` **antes** do `visit()`
- [ ] Isolamento do `localStorage` entre cenários conferido pelo executor para todo CT-B que colapsa seção (CT-B02, CT-B03): o estado colapsado de um cenário não pode chegar ao seguinte (contexto novo por cenário, ou `localStorage.clear()` antes do `visit()`)

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| campo do nome da aplicação (aba Identidade) | `#form\.nome_da_aplicacao` | sim (molde) |
| campo hex da cor primária (aba Identidade) | `#form\.cor_primaria_hex` | sim (molde) |
| campo de paginação padrão (aba Tabelas) | `#form\.paginacao_padrao` | sim (molde) |
| campo do hub de navegação (aba Aplicação) | `#form\.hub_de_navegacao` | a confirmar pelo executor |
| campo da pontuação mínima do anti-robô (aba Login, seção Proteção anti-robô) | `#form\.login_anti_robo_pontuacao_minima` | a confirmar pelo executor |
| campo do remetente (aba E-mail) | `#form\.mail_mailer` (Select) | a confirmar pelo executor |
| campo do servidor SMTP (aba E-mail) | `#form\.mail_host` | a confirmar pelo executor |
| aba E-mail | texto `E-mail` | a confirmar pelo executor |
| aba Tabelas | texto `Tabelas` | sim (molde) |
| botão salvar | `press('Salvar')` | sim (molde) |
| seção que contém um campo | a `.fi-section` ancestral do campo (via `script()`); cabeçalho clicável dela; o `id` da seção vem do `01` e é só seletor — se Q2 mudar o agrupamento, muda o seletor, não o oráculo | nova |
| estado colapsado | classe `fi-collapsed` na seção | framework |
| ausência no documento (não só oculto) | `assertScript("document.querySelector('#form\\\\.paginacao_padrao') === null")` | nova |
| mensagem de erro de um campo | o texto de erro dentro do wrapper do campo | a confirmar pelo executor |

---

## CT-B01: a aba inativa nasce sem os campos e os ganha ao ser selecionada

**Por que browser e não Livewire**: o carregamento depende do `IntersectionObserver` (aba ativa) e do clique que chama `$wire.loadDeferredSchema` — JavaScript executado. CT-07/CT-08 provam o lado do servidor; este prova a ligação.

Dois pares `Quando`/`Então`, com estouro justificado: o comportamento é a sequência abrir → selecionar, e o primeiro `Então` é a pré-condição observável do segundo (ADV-20: as afirmações saíram do `Dado`).

```gherkin
# language: pt
  Cenário: [CT-B01] a aba inativa nasce sem os campos e os ganha ao ser selecionada
    Dado o administrador com acesso à tela de configurações
    Quando ele abre a tela
    Então o campo do nome da aplicação (aba ativa) fica visível sem nenhum clique
    E o documento não contém o campo de paginação padrão, nem oculto
    Quando ele seleciona a aba Tabelas
    Então o campo de paginação padrão fica visível
    E o campo do nome da aplicação deixa de estar visível
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | abrir a tela | `visit('/admin/configuracoes-da-aplicacao')` | tela com as abas |
| 2 | a aba ativa carrega sozinha | `->assertVisible('#form\.nome_da_aplicacao')` | campo do nome visível |
| 3 | a inativa não está no documento | `->assertScript("document.querySelector('#form\\\\.paginacao_padrao') === null")` | — |
| 4 | selecionar Tabelas | `->click('Tabelas')` | — |
| 5 | o conteúdo chega | `->assertVisible('#form\.paginacao_padrao')->assertMissing('#form\.nome_da_aplicacao')` | campo de paginação visível |

**Assertions**: sem `assertPathIs` (não navega); a ordem 2 → 3 importa: o passo 3 só é avaliado depois de a página estar de pé.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| MB1 | o adiamento não está ligado (todos os campos no HTML inicial, só ocultos) | CT-B01 | passo 3: `querySelector` acha o campo; o esperado é `null` |
| MB2 | a chave pedida pelo clique não casa com a chave do schema da aba | CT-B01 | passo 5: `paginacao_padrao` nunca fica visível |
| MB3 | a aba ativa adiada sem gatilho de viewport (só carrega por clique) | CT-B01 | passo 2: o nome nunca fica visível sem clique |

---

## CT-B02: a seção colapsada continua colapsada depois de recarregar `@pendente-Q1`

**Por que browser e não Livewire**: o colapso é Alpine e a persistência é `localStorage` — nenhum dos dois chega ao servidor. Recomendação de Q1 autorizada pelo solicitante e implementada; resposta contrária vira Adendo e re-derivação.

```gherkin
  @pendente-Q1
  Cenário: [CT-B02] a seção colapsada continua colapsada depois de recarregar, e só ela
    Dado o administrador com a tela de configurações aberta e as seções da aba Identidade expandidas
    E ele colapsou a seção que contém o nome da aplicação
    Quando ele recarrega a página
    Então a seção que contém o hex da cor primária continua expandida, com o campo visível
    E a seção que contém o nome da aplicação continua colapsada, com a classe "fi-collapsed" e o campo não visível
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | abrir | `visit('/admin/configuracoes-da-aplicacao')->assertVisible('#form\.nome_da_aplicacao')` | campo visível |
| 2 | colapsar a seção do nome | clique no cabeçalho da seção ancestral de `#form\.nome_da_aplicacao` | campo deixa de estar visível |
| 3 | recarregar | `->refresh()` | — |
| 4 | a aba carregou (controle) | `->assertVisible('#form\.cor_primaria_hex')` | campo da outra seção visível |
| 5 | a seção continua colapsada | `->assertAttributeContains({seção do nome}, 'class', 'fi-collapsed')->assertMissing('#form\.nome_da_aplicacao')` | campo não visível |

**Assertions**: o passo 4 vem antes do 5 de propósito — com a aba adiada, `assertMissing` passaria vazio enquanto o conteúdo ainda não carregou. Exige que nome e hex estejam em seções diferentes (agrupamento de Q2); se Q2 os juntar, troca-se o par de campos. O executor confirma que o contexto do navegador não carrega `localStorage` de um cenário para o outro.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| MB4 | `persistCollapsed()` ausente | CT-B02 | passo 5: a seção volta sem `fi-collapsed` |
| MB5 | id repetido em duas seções da aba | CT-B02 | passo 4: a seção do hex volta colapsada junto |
| MB6 | id gerado por request (ex.: com sufixo aleatório) | CT-B02 | passo 5: a chave do `localStorage` muda e a seção volta expandida |

---

## CT-B03: o erro num campo de seção colapsada pelo usuário fica visível

**Por que browser e não Livewire**: `assertHasFormErrors` prova que a validação disparou; que o usuário **vê** o erro com a seção colapsada no cliente só o navegador prova (P-05). O envio do campo de seção colapsada é CT-12 do `04` (ADV-28).

```gherkin
  Cenário: [CT-B03] salvar com erro num campo de seção colapsada mostra o erro e não grava
    Dado o administrador com a tela de configurações aberta e o nome da aplicação gravado "Antes"
    E ele apagou o nome da aplicação e colapsou a seção que o contém
    Quando ele aciona Salvar
    Então a mensagem de erro de obrigatório do nome da aplicação fica visível
    E o banco continua com o nome "Antes"
```

`@premissa` de mecanismo: o Filament abre a seção do campo com erro (então `#form\.nome_da_aplicacao` também fica visível). P-05 aceita "o erro aparece no topo" como alternativa; nesse caso o oráculo continua o mesmo — a mensagem visível —, só o seletor muda.

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | abrir | `visit('/admin/configuracoes-da-aplicacao')->assertVisible('#form\.nome_da_aplicacao')` | campo visível |
| 2 | apagar o nome | `->fill('#form\.nome_da_aplicacao', '')` | — |
| 3 | colapsar a seção | clique no cabeçalho da seção ancestral do campo; `->assertMissing('#form\.nome_da_aplicacao')` | campo não visível |
| 4 | salvar | `->press('Salvar')` | — |
| 5 | o erro aparece | `->assertVisible({mensagem de erro do nome})` | mensagem visível |
| 6 | nada gravou | `expect(configuracaoGravada('nome_da_aplicacao'))->toBe('Antes')` | — |

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| MB7 | seção colapsada não reage ao erro (fica fechada, sem aviso) | CT-B03 | passo 5: a mensagem existe no DOM mas não está visível |
| MB8 | o colapso remove os campos do formulário enviado e a validação não vê o nome vazio | CT-B03 | passo 5: nenhuma mensagem de erro aparece |

---

## CT-B04: a aba da URL abre com conteúdo, inclusive quando a chave é antiga ou não existe

**Por que browser e não Livewire**: a tela escreve a aba ativa na URL ao selecioná-la, lê a aba da URL ao abrir e carrega o conteúdo por viewport — tudo no cliente. As linhas inválidas são o invariante das duas leituras de Q3 (abra na primeira aba ou na Aplicação, o usuário não fica diante de uma tela sem conteúdo); a linha válida prova que a URL ainda escolhe a aba, sem fixar a chave por literal (ADV-29).

```gherkin
  Esquema do Cenário: [CT-B04] a aba da URL abre com conteúdo, inclusive quando a chave é antiga ou não existe
    Dado o administrador com um link para a tela de configurações com <aba_na_url>
    Quando ele abre o link
    Então <campo_visivel> fica visível sem nenhum clique

    Exemplos:
      | aba_na_url                                                           | campo_visivel                              | # partição             |
      | a URL que a própria tela escreveu ao ele selecionar a aba Tabelas    | o campo de paginação padrão                | válida                 |
      | "?tab=kit::tabs"                                                     | o nome da aplicação ou o hub de navegação  | link antigo da aba Kit |
      | "?tab=nada::tabs"                                                    | o nome da aplicação ou o hub de navegação  | valor fora do domínio  |
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 0 | só linha válida: obter a URL | `visit('/admin/configuracoes-da-aplicacao')->click('Tabelas')->assertVisible('#form\.paginacao_padrao')`; ler `location.href` por `script()` e conferir que mudou (`?tab=` presente) | URL com a aba |
| 1 | abrir o link | `visit({url da linha})` — linha válida: a URL lida no passo 0; inválidas: `/admin/configuracoes-da-aplicacao` + o valor da linha | tela com as abas |
| 2a | linha válida | `->assertVisible('#form\.paginacao_padrao')` | campo de paginação visível |
| 2b | linhas inválidas | `->assertScript("[...document.querySelectorAll('#form\\\\.nome_da_aplicacao, #form\\\\.hub_de_navegacao')].some(e => e.offsetParent !== null)")` | um dos dois campos visível |

**Assertions**: o "ou" das linhas inválidas é de propósito: aceita as duas leituras de Q3; quando Q3 for respondida, a linha `kit::tabs` ganha o campo exato.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| MB9 | com chave desconhecida nenhuma aba fica ativa, e com todas adiadas nada carrega | CT-B04 | passo 2b: nenhum dos dois campos visível |
| MB11 | `persistTabInQueryString` removido junto da reorganização (a tela não escreve nem lê a aba na URL) | CT-B04 | passo 0: a URL não muda ao selecionar Tabelas; ou passo 2a: `paginacao_padrao` não fica visível sem clique |

---

## CT-B05: o erro num campo de seção colapsável aninhada, em aba nunca carregada, fica visível

**Por que browser e não Livewire**: CT-17 prova no servidor que o campo é validado e que a aba carrega; que a mensagem de erro de um campo dentro de uma seção aninhada, numa aba que o usuário nunca abriu, fica visível para ele sem procurar o campo, só o navegador prova (P-05, P-03). *(alterado em 2026-10-15, lote L1: os campos das seções de provedor não têm validação — o cenário usa a seção colapsável "Proteção anti-robô" com a pontuação mínima do reCAPTCHA v3 fora de 0–1 gravada; a marca `@pendente-Q1` caiu)*

```gherkin
  Cenário: [CT-B05] salvar com erro numa seção colapsável aninhada, em aba nunca aberta, leva o usuário à mensagem de erro
    Dado o anti-robô ligado com o provedor reCAPTCHA v3 e a pontuação mínima gravada 5 (fora de 0 a 1)
    E o administrador com a tela de configurações aberta na aba Identidade, sem nunca ter aberto a aba Login
    Quando ele aciona Salvar
    Então a mensagem de erro da pontuação mínima fica visível
    E a seção "Proteção anti-robô" está expandida, sem a classe "fi-collapsed"
```

`@premissa` de mecanismo: o Filament ativa a aba do campo com erro (CT-B02 da ancestral), a aba adiada carrega pelo erro e a seção aninhada (expandida por padrão) mostra o campo. A alternativa de P-05 ("erro no topo") muda só o seletor da mensagem. O não-efeito no banco é provado em CT-17.

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjo | `gravarConfiguracao('login_anti_robo_habilitado', true)`; `gravarConfiguracao('login_anti_robo_provedor', 'recaptcha_v3')` (valor do enum `ProvedorAntiRobo`, a confirmar); `gravarConfiguracao('login_anti_robo_pontuacao_minima', 5)` | — |
| 2 | abrir | `visit('/admin/configuracoes-da-aplicacao')->assertVisible('#form\.nome_da_aplicacao')` | aba Identidade ativa |
| 3 | a aba Login não carregou | `->assertScript("document.querySelector('#form\.login_anti_robo_pontuacao_minima') === null")` | — |
| 4 | salvar | `->press('Salvar')` | — |
| 5 | o erro aparece | `->assertVisible({mensagem de erro da pontuação mínima})` | mensagem visível |
| 6 | a seção está expandida | `->assertScript({a .fi-section ancestral de #form\.login_anti_robo_pontuacao_minima não tem a classe fi-collapsed})` | seção expandida |

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| MB12 | a seção aninhada fica colapsada no render pós-erro (o erro existe no DOM, escondido) | CT-B05 | passo 5: a mensagem existe no DOM mas não está visível; passo 6: a seção tem `fi-collapsed` |
| MB13 | a aba adiada não carrega no erro do salvamento quando o campo está numa seção aninhada | CT-B05 | passo 5: a mensagem nem existe no DOM |

---

## CT-B06: escolher o remetente SMTP mostra a seção do servidor sem salvar

**Por que browser e não Livewire**: a reação na mesma visita depende do `live()` do remetente, que dispara o request ao mudar o Select. No teste de componente a visibilidade é reavaliada a cada request com ou sem `live()` (CT-18 do `04` não mata o mutante). Origem `RQ-02` (+ Fora de Escopo): a seção condicional nova não pode piorar o que a tela fazia.

```gherkin
  Cenário: [CT-B06] escolher o remetente SMTP mostra a seção do servidor na mesma visita, sem salvar
    Dado o remetente de e-mail gravado "log"
    E o administrador com a tela de configurações aberta na aba E-mail, sem o campo do servidor SMTP visível
    Quando ele escolhe o remetente "smtp"
    Então o campo do servidor SMTP fica visível, sem salvar
    E o banco continua com o remetente "log"
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjo | `gravarConfiguracao('mail_mailer', 'log')` | — |
| 2 | abrir na aba E-mail | `visit('/admin/configuracoes-da-aplicacao')->click('E-mail')->assertVisible('#form\.mail_mailer')->assertMissing('#form\.mail_host')` | remetente visível, servidor não |
| 3 | escolher smtp | `->select('#form\.mail_mailer', 'smtp')` | — |
| 4 | a seção aparece | `->assertVisible('#form\.mail_host')` | campo do servidor visível |
| 5 | nada gravou | `expect(configuracaoGravada('mail_mailer'))->toBe('log')` | — |

**Assertions**: o passo 2 confere `#form\.mail_mailer` visível antes do `assertMissing`, para a ausência não passar vazia com a aba ainda carregando.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| MB14 | o `live()` do remetente perdido na refatoração (a seção só aparece depois de salvar ou recarregar) | CT-B06 | passo 4: `#form\.mail_host` não fica visível |

---

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| a seção nasce expandida na primeira visita | recorte de Q1; sem mutante próprio que CT-B02/CT-B03 já não exercitem (ambos partem das seções expandidas) |
| corrigido o erro do CT-B03, salvar de novo grava o campo de outra seção colapsada | nenhum código do kit produz o mutante (MB10 retirado, ADV-28); o envio do campo de seção colapsada é CT-12 do `04` |
| dividir o CT-B01 em abrir e selecionar | ADV-30 rejeitado: o segundo cenário repetiria a abertura inteira como `Dado` para provar metade da mesma coisa |
| ícone visível no cabeçalho de cada seção | CT-04 prova o ícone no schema; o render do ícone é do Filament |
| tema escuro das seções | nenhuma RQ; `assertSee` não valida tema |
| o erro numa aba não carregada, fora de seção colapsada, abre a aba | CT-B02 da ancestral `settings-do-kit` prova no navegador; CT-09 do `04` prova o carregamento no servidor |

## Roteiro de Validação: Desenhado × Implementado

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | troca de aba: o conteúdo aparece depois de um request | | | CT-B01, CT-B04 |
| 2 | clique no cabeçalho colapsa/expande a seção | | | CT-B02, CT-B03 (passos de colapso) |
| 3 | recarregar mantém a seção fechada | | | CT-B02 |
| 4 | preencher e gravar com seção colapsada | | | CT-12 do `04` (componente); CT-B03 (o erro visível) |
| 5 | erro em seção colapsada fica visível | | | CT-B03, CT-B05 |
| 6 | trocar o remetente mostra o servidor SMTP na mesma visita | | | CT-B06 |

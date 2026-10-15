# Casos de Teste de Browser — feat/configuracoes-secoes-colapsaveis: aba Aplicação, seções colapsáveis com ícone e carregamento adiado das abas

> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor.
> Costura: `browser` — linha "Navegador" de `## Costuras de Teste` do `04`
> Comando: `composer test:browser` (embute `npm run build` e `view:cache`), ou `vendor/bin/pest --testsuite=Browser --filter=ConfiguracoesSecoesColapsaveis` — a suíte `Browser` está declarada no `phpunit.xml`. Em série; nunca `--parallel` (`.ai/rules/testes-browser.md`).
> Teto do perfil padrão: 1 CT-B. **Estouro justificado (gate vence teto)**: cada um dos cinco é o único matador de mutantes próprios (tabelas abaixo). Três são os que a `## Superfície de UI` nomeia como só-navegador (CT-B01, CT-B02, CT-B03); CT-B04 é o invariante de Q3 com a partição válida da URL; CT-B05 fecha ADV-04/ADV-25 (erro em seção que nasce colapsada, numa aba nunca carregada).

## Pré-requisitos
- [ ] `npm run build` e `php artisan view:cache` executados (`composer test:browser` faz os dois)
- [ ] `tests/Browser/Screenshots` no `.gitignore`
- [ ] `beforeEach`: `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])`, `$this->actingAs(usuarioDoKit('admin'))` e o aquecimento pelo kernel `$this->get('/admin/configuracoes-da-aplicacao')` (molde `tests/Browser/ConfiguracoesDoKitTest.php`)
- [ ] Sem `assertNoJavaScriptErrors()`: `ColorPicker` dentro de `Tabs` emite `ResizeObserver` no headless do CI (`.ai/rules/testes-browser.md`) — a rule vence o template
- [ ] Nunca `wait(n)`: o plugin reexecuta cada asserção até o teto
- [ ] Arranjo de settings por `gravarConfiguracao()` **antes** do `visit()`

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| campo do nome da aplicação (aba Identidade) | `#form\.nome_da_aplicacao` | sim (molde) |
| campo hex da cor primária (aba Identidade) | `#form\.cor_primaria_hex` | sim (molde) |
| campo de paginação padrão (aba Tabelas) | `#form\.paginacao_padrao` | sim (molde) |
| campo do hub de navegação (aba Aplicação) | `#form\.hub_de_navegacao` | a confirmar pelo executor |
| campo do client_id do Google (aba Login, seção do provedor) | `#form\.login_google_client_id` | a confirmar pelo executor |
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

## CT-B03: o erro num campo de seção colapsada fica visível, e corrigido o erro o campo de outra seção colapsada grava

**Por que browser e não Livewire**: `assertHasFormErrors` prova que a validação disparou; que o usuário **vê** o erro com a seção colapsada no cliente, e que o campo de uma seção que ele colapsou continua indo no salvamento, só o navegador prova (P-05).

Dois pares `Quando`/`Então`, com estouro justificado: o segundo é a **saída do estado de erro** que a skill exige ao lado de todo cenário de erro, e é ele que prova o envio do campo de seção colapsada (ADV-16).

```gherkin
  Cenário: [CT-B03] salvar com erro em seção colapsada mostra o erro, e corrigido o erro o campo de outra seção colapsada grava
    Dado o administrador com a tela de configurações aberta, o nome da aplicação gravado "Antes" e o hex da cor primária gravado "#111111"
    E ele alterou o hex da cor primária para "#7c3aed" e colapsou a seção que o contém
    E ele apagou o nome da aplicação e colapsou a seção que o contém
    Quando ele aciona Salvar
    Então a mensagem de erro de obrigatório do nome da aplicação fica visível
    E o banco continua com o nome "Antes" e o hex "#111111"
    Quando ele preenche o nome da aplicação com "Depois" e aciona Salvar
    Então o banco tem o nome "Depois" e o hex "#7c3aed"
```

`@premissa` de mecanismo: o Filament abre a seção do campo com erro (então `#form\.nome_da_aplicacao` também fica visível, e é por ele que o passo 7 preenche). P-05 aceita "o erro aparece no topo" como alternativa; nesse caso o oráculo continua o mesmo — a mensagem visível —, só o seletor muda. A seção do hex continua colapsada até o segundo salvamento (não tem erro).

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | abrir | `visit('/admin/configuracoes-da-aplicacao')->assertVisible('#form\.cor_primaria_hex')` | campos visíveis |
| 2 | alterar o hex e colapsar a seção dele | `->fill('#form\.cor_primaria_hex', '#7c3aed')`; clique no cabeçalho da seção ancestral; `->assertMissing('#form\.cor_primaria_hex')` | campo não visível |
| 3 | apagar o nome e colapsar a seção dele | `->fill('#form\.nome_da_aplicacao', '')`; clique no cabeçalho da seção ancestral; `->assertMissing('#form\.nome_da_aplicacao')` | campo não visível |
| 4 | salvar | `->press('Salvar')` | — |
| 5 | o erro aparece | `->assertVisible({mensagem de erro do nome})` | mensagem visível |
| 6 | nada gravou | `expect(configuracaoGravada('nome_da_aplicacao'))->toBe('Antes')` e `configuracaoGravada('cor_primaria_hex')` = `'#111111'` | — |
| 7 | corrigir e salvar de novo | `->fill('#form\.nome_da_aplicacao', 'Depois')->press('Salvar')` | — |
| 8 | gravou, com o hex da seção colapsada | `expect(configuracaoGravada('nome_da_aplicacao'))->toBe('Depois')` e `configuracaoGravada('cor_primaria_hex')` = `'#7c3aed'` (o executor espera o aviso de salvo visível antes de ler o banco) | aviso de salvo |

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| MB7 | seção colapsada não reage ao erro (fica fechada, sem aviso) | CT-B03 | passo 5: a mensagem existe no DOM mas não está visível |
| MB8 | o colapso remove os campos do formulário enviado e a validação não vê o nome vazio | CT-B03 | passo 5: nenhuma mensagem de erro aparece |
| MB10 | campo de seção colapsada pelo usuário fica fora do envio (o colapso remove o campo do DOM e do estado sincronizado) | CT-B03 | passo 8: o hex continua "#111111"; esperado "#7c3aed" |

---

## CT-B04: a aba da URL abre com conteúdo, inclusive quando a chave é antiga ou não existe

**Por que browser e não Livewire**: a aba ativa sai da URL e o conteúdo dela carrega por viewport — as duas coisas acontecem no cliente. As linhas inválidas são o invariante das duas leituras de Q3 (abra na primeira aba ou na Aplicação, o usuário não fica diante de uma tela sem conteúdo); a linha válida prova que a URL ainda escolhe a aba.

```gherkin
  Esquema do Cenário: [CT-B04] a aba da URL abre com conteúdo, inclusive quando a chave é antiga ou não existe
    Dado o administrador com um link para a tela de configurações com "<aba_na_url>" na URL
    Quando ele abre o link
    Então <campo_visivel> fica visível sem nenhum clique

    Exemplos:
      | aba_na_url          | campo_visivel                                          | # partição                |
      | ?tab=tabelas::tabs  | o campo de paginação padrão                             | válida (aba inalterada)   |
      | ?tab=kit::tabs      | o nome da aplicação ou o hub de navegação               | link antigo da aba Kit    |
      | ?tab=nada::tabs     | o nome da aplicação ou o hub de navegação               | valor fora do domínio     |
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | abrir o link | `visit('/admin/configuracoes-da-aplicacao<aba_na_url>')` | tela com as abas |
| 2a | linha válida | `->assertVisible('#form\.paginacao_padrao')` | campo de paginação visível |
| 2b | linhas inválidas | `->assertScript("[...document.querySelectorAll('#form\\\\.nome_da_aplicacao, #form\\\\.hub_de_navegacao')].some(e => e.offsetParent !== null)")` | um dos dois campos visível |

**Assertions**: a chave `tabelas::tabs` é o slug do rótulo "Tabelas" + `::tabs` (fato do framework; a aba não muda de nome). O "ou" das linhas inválidas é de propósito: aceita as duas leituras de Q3; quando Q3 for respondida, a linha `kit::tabs` ganha o campo exato.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| MB9 | com chave desconhecida nenhuma aba fica ativa, e com todas adiadas nada carrega | CT-B04 | passo 2b: nenhum dos dois campos visível |
| MB11 | `persistTabInQueryString` removido junto da reorganização (a URL não escolhe mais a aba) | CT-B04 | passo 2a: `paginacao_padrao` não fica visível sem clique |

---

## CT-B05: o erro num campo de seção que nasce colapsada, em aba nunca carregada, fica visível

**Por que browser e não Livewire**: CT-17 prova no servidor que o campo é validado e que a aba carrega; que a seção colapsada do provedor, numa aba que o usuário nunca abriu, mostra o erro sem que ele precise achar o campo, só o navegador prova (P-05, P-03).

```gherkin
  Cenário: [CT-B05] salvar com erro numa seção que nasce colapsada, em aba nunca aberta, leva o usuário à mensagem de erro
    Dado o login com Google habilitado e o client_id do Google gravado vazio
    E o administrador com a tela de configurações aberta na aba Identidade, sem nunca ter aberto a aba Login
    Quando ele aciona Salvar
    Então a mensagem de erro do client_id do Google fica visível
```

`@premissa` de mecanismo: o Filament ativa a aba do campo com erro (CT-B02 da ancestral), a aba adiada carrega pelo erro e a seção do provedor, que nasce colapsada, abre. A alternativa de P-05 ("erro no topo") muda só o seletor. O não-efeito no banco é provado em CT-17; aqui o oráculo é a visibilidade.

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjo | `gravarConfiguracao('login_google_habilitado', true)`; `gravarConfiguracao('login_google_client_id', '')` | — |
| 2 | abrir | `visit('/admin/configuracoes-da-aplicacao')->assertVisible('#form\.nome_da_aplicacao')` | aba Identidade ativa |
| 3 | a aba Login não carregou | `->assertScript("document.querySelector('#form\\\\.login_google_client_id') === null")` | — |
| 4 | salvar | `->press('Salvar')` | — |
| 5 | o erro aparece | `->assertVisible({mensagem de erro do client_id do Google})` | mensagem visível |

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| MB12 | a seção que nasce colapsada não abre no erro (o erro fica dentro dela, escondido) | CT-B05 | passo 5: a mensagem existe no DOM mas não está visível |
| MB13 | a aba adiada não carrega no erro do salvamento quando o campo está numa seção aninhada | CT-B05 | passo 5: a mensagem nem existe no DOM |

---

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| a seção nasce expandida na primeira visita | recorte de Q1; sem mutante próprio que CT-B02/CT-B03 já não exercitem (ambos partem das seções expandidas) |
| trocar o remetente mostra a seção SMTP sem salvar (o `live()`) | `live()` pré-existente, fora do que a feature muda; CT-18 do `04` cobre a reação da seção no servidor; lacuna declarada na taxonomia |
| ícone visível no cabeçalho de cada seção | CT-04 prova o ícone no schema; o render do ícone é do Filament |
| tema escuro das seções | nenhuma RQ; `assertSee` não valida tema |
| o erro numa aba não carregada, fora de seção colapsada, abre a aba | CT-B02 da ancestral `settings-do-kit` prova no navegador; CT-09 do `04` prova o carregamento no servidor |

## Roteiro de Validação: Desenhado × Implementado

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | troca de aba: o conteúdo aparece depois de um request | | | CT-B01, CT-B04 |
| 2 | clique no cabeçalho colapsa/expande a seção | | | CT-B02, CT-B03 (passos de colapso) |
| 3 | recarregar mantém a seção fechada | | | CT-B02 |
| 4 | preencher e gravar com seção colapsada | | | CT-B03 |
| 5 | erro em seção colapsada fica visível | | | CT-B03, CT-B05 |

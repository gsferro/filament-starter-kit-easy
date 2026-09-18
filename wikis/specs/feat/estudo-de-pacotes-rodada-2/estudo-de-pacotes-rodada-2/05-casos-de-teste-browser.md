# Casos de Teste de Browser — Estudo e adoção de pacotes Filament, rodada 2

> Requisito: `00-requisito.md` · Cenários de backend: `04-casos-de-teste.md`
> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor, in-process.
> Comando: `composer test:browser` — **em série, nunca `--parallel`**
> (`.ai/rules/testes-browser.md`, medido: `--parallel` derruba 4 de 11 cenários).

## Por que existe um `05` nesta feature

O gate é estreito de propósito: só vai para o navegador o cenário que afirma sobre algo que
**apenas o navegador prova** — JavaScript executado, erro de console, acessibilidade, cor, tema ou
layout. Validação de formulário, gravação, listagem e autorização na tela são teste de componente
Livewire e ficam no `04`.

Dois cenários passam no gate, e cada um por um motivo diferente:

| CT-B | O que só o navegador prova |
|---|---|
| CT-B01 | o alerta de alterações não salvas é **JavaScript**: o Filament registra um ouvinte de `beforeunload` que compara o estado atual do formulário com o estado salvo. O servidor não sabe se o formulário está sujo; nenhum teste de componente executa esse ouvinte |
| CT-B02 | "nenhuma requisição sai para um domínio de terceiro" é observação de **rede**, não de HTML. O `04` (CT-21) prova que nenhum endereço de imagem da página aponta para fora; só o navegador prova que **nada foi buscado** — inclusive por caminhos que não são `<img src>`, como CSS e fonte — e só ele prova que a imagem embutida realmente **decodifica** |

**A tabela `## Superfície de UI` do `01` é gatilho, não critério.** As outras duas linhas dela — o
rodapé com a versão e o campo novo na tela de configurações — são HTML renderizado no servidor e
gravação de formulário, e foram cortadas explicitamente na `## Poda` do `04`.

---

## Pré-requisitos

- [x] `npm run build` executado — sem `public/build/manifest.json` **toda** tela responde
      `ViteException` e todo cenário falha por um motivo que não é o dele. O `composer test:browser`
      já embute o build.
- [x] `php artisan view:cache` — o primeiro cenário que renderiza um painel paga a compilação
      inteira **dentro do próprio timeout** e estoura os 45 s. O `composer test:browser` também
      embute isso. Não "conserte" o sintoma subindo `pest()->browser()->timeout()`: a rule do
      projeto registra que 40 s e 60 s reproduzem a falha igual.
- [x] Rodando **um arquivo isolado** depois de um `view:clear`: aqueça pelo kernel no `beforeEach`
      com um `$this->get(...)` da mesma tela. O servidor do plugin roda no mesmo processo e reusa
      `storage/framework/views`.
- [x] `tests/Browser/Screenshots` no `.gitignore` — e **nenhuma captura nova** nesta feature, então
      nada a acrescentar em `KitArte::IMAGENS`.
- [x] Autenticação por `$this->actingAs($usuario)` **antes** do `visit()`. Login pela tela custa
      ~20 s por cenário e não é o que estes dois cenários medem.
- [x] O `beforeEach` **não arranja painel**. Cada cenário arranja o seu imediatamente antes de
      visitar — cenário de navegador renderiza a barra lateral do painel em que o processo foi
      deixado.
- [x] **Binários do Playwright instalados** (`npm install` + `npx playwright install`). Numa árvore
      sem `node_modules`, `composer test:browser` morre duas vezes e por motivos diferentes: o
      `npm run build` não acha o `vite`, e depois o plugin aborta a suíte INTEIRA com
      `PlaywrightOutdatedException` — "Playwright is outdated" é a mensagem, mas a causa real é o
      navegador nunca ter sido baixado. Medido nesta feature: 75 testes, 0 verdes, 60 falhas em
      `Playwright\Client.php:106`. Nenhuma delas tem a ver com o cenário. Não é dependência nova —
      `playwright` já está em `package.json`; é passo de ambiente.

---

## Seletores

O kit não tem `data-testid` — é dívida conhecida e registrada em `.ai/rules/testes-browser.md`.
O que existe hoje:

| Elemento | Seletor | Já existe? |
|---|---|---|
| campo de nome da aplicação na tela de configurações | `#form\.nome_da_aplicacao` (`id` gerado pelo Filament; o `.` precisa de escape em CSS) | sim |
| avatar no menu do usuário | `img` dentro do gatilho do menu do usuário, por `aria-label` do botão | sim |
| formulário da página Filament | o elemento que o Livewire monta na página — alcançado pelo `$wire` da própria página, via `script()` | sim |

Nenhum seletor novo é criado por esta feature. Se um cenário precisar de um, ele vira dívida
registrada aqui antes de virar classe de CSS no teste.

---

## CT-B01: o navegador barra a saída quando o formulário está sujo

**Por que browser e não Livewire**: a asserção é sobre **JavaScript executado**. O Filament emite
um script que registra `window.addEventListener('beforeunload', ...)` e, dentro dele, compara o
hash do estado atual do formulário com o hash do estado salvo. Nada disso existe no servidor: o
componente Livewire responde igual com o formulário limpo e com o formulário sujo. O `04` (CT-14,
CT-15) prova que o **painel** decidiu ligar o alerta; este cenário prova que a decisão **chegou ao
navegador e funciona**.

```gherkin
# language: pt
Funcionalidade: Alerta de alterações não salvas

  Regra: o alerta é decidido a cada request pela configuração, nos três painéis

    Cenário: [CT-B01] o navegador impede a saída silenciosa de um formulário com alteração pendente
      Dado que o alerta de alterações não salvas está ligado na configuração
      E a administradora autenticada na tela de configurações da aplicação
      Quando ela altera o nome da aplicação sem salvar
      Então uma tentativa de sair da página é cancelada pelo navegador
      E, antes da alteração, a mesma tentativa não era cancelada
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranja a config ligada e a persona | `config(['kit.alerta_alteracoes_nao_salvas' => true]); $this->actingAs(usuarioDoKit('admin'));` | — |
| 2 | aquece a compilação fora do cronômetro | `$this->get('/admin/configuracoes-da-aplicacao');` | — |
| 3 | abre a tela | `$pagina = visit('/admin/configuracoes-da-aplicacao')->assertPathIs('/admin/configuracoes-da-aplicacao');` | a tela de configurações |
| 4 | **linha de base**: com o formulário limpo, o evento não é cancelado | `$pagina->assertScript("(() => { const e = new Event('beforeunload', {cancelable:true}); window.dispatchEvent(e); return e.defaultPrevented; })()", false)` | — |
| 5 | suja o formulário | `->fill('#form\\.nome_da_aplicacao', 'Nome alterado')` | o campo com o valor novo |
| 6 | **oráculo**: agora o evento é cancelado | `->assertScript("(() => { const e = new Event('beforeunload', {cancelable:true}); window.dispatchEvent(e); return e.defaultPrevented; })()", true)` | — |
| 7 | ~~apoio~~ | ~~`->assertNoJavaScriptErrors()`~~ — **não implementada, de propósito** (ver abaixo) | — |

**Implementado em** `tests/Browser/AlertaDeAlteracoesNaoSalvasTest.php`.

**Assertions**: `assertPathIs` primeiro (é ela que espera a navegação) · o par linha de base ×
oráculo no passo 4 e no passo 6 é o que torna o cenário discriminante.

**Armadilha conhecida desta tela, e por que o passo 7 não existe no código**: a tela de
configurações tem `ColorPicker` dentro de `Tabs`, e o Chrome headless do Linux emite
`ResizeObserver loop completed with undelivered notifications` duas vezes na montagem — **só no
CI**. `.ai/rules/testes-browser.md` não deixa isso como contingência ("se reprovar, remova"), e sim
como regra em pé: *"em tela com esse par de componentes, não use a asserção e escreva por que ela
não está ali"*. O redator do roteiro escreveu o passo 7 condicional; o implementador aplicou a
regra. `tests/Browser/ConfiguracoesDoKitTest.php` — a outra suíte de navegador desta mesma tela —
já a tinha removido pela mesma causa, medida num CI vermelho da feature `settings-do-kit`.

Escrever o passo 7 e esperar o CI reprovar teria custado um pipeline vermelho para reaprender o que
a rule já registra. Os oráculos que provam o comportamento são os dos passos 4 e 6, e esses ficam.

**Por que dois disparos e não um**: com um só, um ouvinte que cancelasse **sempre** (ignorando o
estado do formulário) passaria. O par é o que distingue "o alerta existe" de "o alerta observa o
formulário".

**Por que não usar o diálogo do navegador**: o plugin não expõe asserção sobre diálogo nativo
(`confirm`/`beforeunload`), e o painel não está em modo SPA — `window.addEventListener` +
`preventDefault()` é o mecanismo real, e `defaultPrevented` é o observável dele.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M24 | o painel responde que o alerta está ligado, mas o script não chega ao navegador — a decisão morre no servidor | CT-B01 (passo 6) |
| MB1 | o ouvinte é registrado e cancela **sempre**, mesmo com o formulário limpo, e toda navegação normal passa a pedir confirmação | CT-B01 (passo 4, a linha de base) |

---

## CT-B02: nenhuma requisição sai para o domínio de terceiro, e a imagem embutida decodifica

**Por que browser e não Livewire**: duas asserções, e nenhuma das duas é sobre HTML. A primeira é
sobre **rede** — o `04` (CT-21) prova que a string `ui-avatars.com` não está na página, e isso
ficaria verde se outro ponto da página (um widget, uma folha de estilo, um ícone) buscasse o
domínio. A segunda é sobre **decodificação**: um `data:` URI malformado é um `<img>` presente no
DOM, com `src` preenchido, que o navegador não consegue abrir — o servidor não tem como saber.

```gherkin
# language: pt
Funcionalidade: Avatar padrão

  Regra: o avatar de quem não enviou foto é gerado pela própria aplicação

    Cenário: [CT-B02] a tela de quem não tem foto não busca nada fora da aplicação
      Dado uma usuária autenticada sem foto de perfil
      Quando ela abre o painel /admin no navegador
      Então nenhum recurso carregado pela página veio de um domínio de terceiro
      E nenhuma imagem da página está quebrada
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranja a persona sem foto | `$this->actingAs(usuarioDoKit('admin'));` — o usuário do kit nasce sem foto | — |
| 2 | aquece a compilação fora do cronômetro | `$this->get('/admin');` | — |
| 3 | abre o painel | `$pagina = visit('/admin')->assertPathIs('/admin');` | o painel administrativo |
| 4 | **oráculo de decodificação** — e é ele que espera a carga terminar | `$pagina->assertNoBrokenImages()` | o avatar desenhado |
| 5 | lê a rede | `$recursos = json_decode((string) $pagina->script("JSON.stringify(performance.getEntriesByType('resource').map(r => r.name))"), true, flags: JSON_THROW_ON_ERROR);` e `$origem = (string) $pagina->script('location.origin');` | — |
| 6 | **oráculo de rede** | `expect($recursos)->not->toBeEmpty()` e, filtrando por `! str_starts_with($recurso, $origem)`, `expect($deTerceiros)->toBe([])` | — |
| 7 | apoio | `->assertNoJavaScriptErrors()` | — |

**Implementado em** `tests/Browser/AvatarDeIniciaisTest.php`.

**Ordem trocada em relação ao primeiro rascunho deste roteiro** (a decodificação era o passo 6 e a
leitura de rede o 4): `assertNoBrokenImages()` chama `waitForLoadState('load')`
(`vendor/pestphp/pest-plugin-browser/src/Api/Concerns/MakesConsoleAssertions.php:36`), e é a única
coisa no cenário que espera a carga terminar. Lendo `performance` antes dela, a lista de recursos
seria um retrato de meio caminho — e um retrato de meio caminho satisfaz uma asserção de ausência
pelo motivo errado. Com a ordem atual, o `not->toBeEmpty()` vigia um mundo que já acabou de
carregar.

**Assertions**: `assertPathIs` primeiro · `expect($recursos)->not->toBeEmpty()` é obrigatório — sem
ele, uma página que não carregou recurso nenhum (ou um `script()` que devolveu vazio) satisfaria a
asserção de ausência, que é exatamente o **não-efeito em mundo vazio** que o gate proíbe · a
asserção de host compara com o host da própria página — lido no navegador por `location.origin`, e
não montado em PHP, porque o plugin sobe o servidor em **porta aleatória** e o teste não sabe qual
é —, e não com uma lista de domínios proibidos: uma lista negra só pega o domínio que alguém
lembrou de escrever.

**Por que `assertNoBrokenImages()` e não `assertVisible`**: `assertVisible` passa para qualquer
elemento com caixa não vazia — um `<img>` com `src` inválido continua "visível". `assertNoBrokenImages`
é a asserção que o plugin oferece para o que o navegador **não conseguiu carregar**, e é o único
oráculo barato do "o SVG embutido realmente decodifica".

**O que este cenário NÃO prova**: a aparência do avatar — cor, contraste, posição. Aparência não é
requisito nesta entrega (pergunta nº 7 do `04`), e para defeito de cor não há saída barata: é
screenshot e olhar. **Nem prova que o avatar é o da pessoa certa**: uma implementação que desenhe
sempre as iniciais de quem está autenticado passa aqui inteira. Quem mata esse mutante (`M62`) é
CT-45, no `04`, e ele é teste de componente — duas pessoas sem foto na mesma listagem.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M29 | o provedor local é registrado, mas outro ponto da página (widget, tabela, ícone) continua buscando o domínio de terceiro | CT-B02 (passo 5) |
| MB2 | o SVG é montado com escape ou base64 quebrado: o `<img>` existe, o `src` está preenchido, e o navegador não abre nada — todo avatar da aplicação some sem erro no servidor | CT-B02 (passo 6) |

---

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| "com o alerta desligado, sair da página suja não pede confirmação" | mata o mesmo mutante que CT-14 do `04` (as linhas `false` da matriz painel × chave), que é três ordens de grandeza mais barato. O que só o navegador acrescentaria seria a ausência do ouvinte, e ausência de ouvinte num mundo em que o passo 4 de CT-B01 já mede a linha de base é redundância |
| "o rodapé com a versão aparece no navegador" | HTML renderizado no servidor; CT-01 a CT-03 do `04` provam, e o navegador não acrescenta informação |
| "a tela de login não mostra a versão, no navegador" | o guard é decidido no servidor; CT-05 do `04` prova |
| "o campo novo da tela de configurações grava" | gravação de formulário é teste de componente Livewire — CT-08, CT-10 e CT-15 do `04` |
| "o avatar tem contraste suficiente entre texto e fundo" | aparência não é requisito (pergunta nº 7); e contraste só se prova com screenshot e olho |
| "auditoria de acessibilidade das telas tocadas" | nenhuma tela nova é criada; o rodapé e o avatar entram em telas que a suíte de navegador já percorre. Se algum dia virar requisito, é um cenário por painel, porque `visit([...])` em lote aborta na primeira falha |
| "o alerta funciona nas telas `create`/`edit` de plugin de terceiro" | o alerta é do **painel**, não da tela — CT-14 do `04` mede o painel, e amarrar um cenário de navegador ao plugin da vez é dívida com data de validade |

---

## Roteiro de Validação: Desenhado × Implementado

| # | O que o requisito pediu | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | o alerta de alterações não salvas chega ao navegador quando a configuração o liga (RQ-03/RQ-04 via `## Ambiguidades`) | `->unsavedChangesAlerts(fn (): bool => (bool) config('kit.alerta_alteracoes_nao_salvas'))` nos três painéis; o Filament emite `setUpUnsavedDataChangesAlert({ $wire })` e o ouvinte compara `md5($wire.data)` com `$wire.savedDataHash` | **Sim** | `AdminPanelProvider.php:101`, `AppPanelProvider.php:112`, `InfraPanelProvider.php:123`; `vendor/filament/filament/resources/views/components/page/index.blade.php:162-166`; `vendor/filament/filament/resources/js/unsaved-changes-alert.js:1-14`. CT-B01 verde: `defaultPrevented` é `false` com o formulário limpo e `true` depois do `fill` |
| 2 | nenhum dado do usuário sai para terceiro na renderização do avatar (RQ-13, `@premissa` — pergunta nº 1 do `04`) | `->defaultAvatarProvider(App\Support\AvatarDeIniciais::class)` nos três painéis; o provider devolve `data:image/svg+xml;base64,…` desenhado na própria aplicação, no lugar do `UiAvatarsProvider` do Filament | **Sim** | `AdminPanelProvider.php:95`, `AppPanelProvider.php:106`, `InfraPanelProvider.php:117`; `App\Support\AvatarDeIniciais::get()`. CT-B02 verde: `performance.getEntriesByType('resource')` não veio vazia e **nenhum** item cai fora de `location.origin`; `assertNoBrokenImages()` prova que o `data:` URI decodifica |

### Passo do desenho que NÃO virou código, e por quê

| Passo | Decisão | Motivo |
|---|---|---|
| CT-B01, passo 7 (`assertNoJavaScriptErrors()`) e o `Dado ... a tela abriu sem nenhum erro de JavaScript` que era o par dele no Gherkin | **não implementado; a linha do Gherkin saiu junto** | `ColorPicker` dentro de `Tabs` emite `ResizeObserver loop…` no headless do Linux. `.ai/rules/testes-browser.md` proíbe a asserção nessa combinação — não é contingência, é regra em pé. Não reproduz no Chrome do Windows, então o roteiro ficaria verde local e vermelho no CI |
| CT-B02, Gherkin — a persona chamada "Ana Souza" | **nome removido do cenário** | o roteiro executável arranja `usuarioDoKit('admin')`, cujo `name` é `Usuário`, e **nenhuma asserção do cenário lê o nome** — as iniciais desenhadas não são oráculo aqui (quem as mede é CT-45, no `04`). Um nome no `Dado` que o arranjo não cria é promessa de cobertura que o cenário não tem |
| CT-B02, ordem dos passos 4 e 6 | **invertida** | `assertNoBrokenImages()` é quem espera `load`; lendo `performance` antes dela a asserção de ausência valeria sobre um retrato incompleto |

### Execução

```
composer test:browser
```

(embute `config:clear`, `npm run build`, `view:cache` e `artisan test --testsuite=Browser`, em
série — **nunca `--parallel`**)

| Run | Resultado |
|---|---|
| suíte `Browser` completa | **75 testes, 61 verdes, 14 pulados, 0 falhas, 320 asserções, 254 s** |
| só os dois arquivos novos | **2 verdes, 8 asserções** — e as 8 são exatamente as asserções escritas (3 em CT-B01, 5 em CT-B02), o que confirma execução real e não replay do TIA |

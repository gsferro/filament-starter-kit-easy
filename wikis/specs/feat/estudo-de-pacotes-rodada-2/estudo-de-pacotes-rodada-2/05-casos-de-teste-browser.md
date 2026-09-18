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

- [ ] `npm run build` executado — sem `public/build/manifest.json` **toda** tela responde
      `ViteException` e todo cenário falha por um motivo que não é o dele. O `composer test:browser`
      já embute o build.
- [ ] `php artisan view:cache` — o primeiro cenário que renderiza um painel paga a compilação
      inteira **dentro do próprio timeout** e estoura os 45 s. O `composer test:browser` também
      embute isso. Não "conserte" o sintoma subindo `pest()->browser()->timeout()`: a rule do
      projeto registra que 40 s e 60 s reproduzem a falha igual.
- [ ] Rodando **um arquivo isolado** depois de um `view:clear`: aqueça pelo kernel no `beforeEach`
      com um `$this->get(...)` da mesma tela. O servidor do plugin roda no mesmo processo e reusa
      `storage/framework/views`.
- [ ] `tests/Browser/Screenshots` no `.gitignore` — e **nenhuma captura nova** nesta feature, então
      nada a acrescentar em `KitArte::IMAGENS`.
- [ ] Autenticação por `$this->actingAs($usuario)` **antes** do `visit()`. Login pela tela custa
      ~20 s por cenário e não é o que estes dois cenários medem.
- [ ] O `beforeEach` **não arranja painel**. Cada cenário arranja o seu imediatamente antes de
      visitar — cenário de navegador renderiza a barra lateral do painel em que o processo foi
      deixado.

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
      E que a tela abriu sem nenhum erro de JavaScript
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
| 7 | apoio | `->assertNoJavaScriptErrors()` | — |

**Assertions**: `assertPathIs` primeiro (é ela que espera a navegação) · o par linha de base ×
oráculo no passo 4 e no passo 6 é o que torna o cenário discriminante · `assertNoJavaScriptErrors()`
é **apoio**, nunca o oráculo — e é ela, e não `assertNoSmoke()`, porque a tela de configurações
carrega componentes de plugin de terceiro.

**Armadilha conhecida desta tela**: a tela de configurações tem `ColorPicker` dentro de `Tabs`, e o
Chrome headless do Linux emite `ResizeObserver loop completed with undelivered notifications` na
montagem — só no CI. Se o passo 7 reprovar por isso, **remova a asserção de console e escreva por
quê no arquivo de teste**, como a rule do projeto manda: os oráculos que provam o comportamento são
os dos passos 4 e 6, e esses ficam.

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
      Dado uma usuária autenticada sem foto de perfil, chamada "Ana Souza"
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
| 4 | **oráculo de rede** | `$recursos = json_decode((string) $pagina->script("JSON.stringify(performance.getEntriesByType('resource').map(r => r.name))"), true, flags: JSON_THROW_ON_ERROR);` | — |
| 5 | assere | `expect($recursos)->not->toBeEmpty()` e nenhum item contendo `ui-avatars.com` nem qualquer host fora do host da própria página | — |
| 6 | **oráculo de decodificação** | `$pagina->assertNoBrokenImages()` | o avatar desenhado |
| 7 | apoio | `->assertNoJavaScriptErrors()` | — |

**Assertions**: `assertPathIs` primeiro · `expect($recursos)->not->toBeEmpty()` é obrigatório — sem
ele, uma página que não carregou recurso nenhum (ou um `script()` que devolveu vazio) satisfaria a
asserção de ausência, que é exatamente o **não-efeito em mundo vazio** que o gate proíbe · a
asserção de host compara com o host da própria página, e não com uma lista de domínios proibidos:
uma lista negra só pega o domínio que alguém lembrou de escrever.

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

Preencher no step 7 da `feature-wiki`, com evidência inline.

| # | O que o requisito pediu | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | o alerta de alterações não salvas chega ao navegador quando a configuração o liga (RQ-03/RQ-04 via `## Ambiguidades`) | | | |
| 2 | nenhum dado do usuário sai para terceiro na renderização do avatar (RQ-13, `@premissa` — pergunta nº 1 do `04`) | | | |

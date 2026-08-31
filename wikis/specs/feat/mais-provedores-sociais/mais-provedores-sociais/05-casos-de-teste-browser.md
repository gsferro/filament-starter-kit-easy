# Casos de Teste de Browser — W8: mais provedores de login social

> Requisito: `00-requisito.md` · Casos de backend: `04-casos-de-teste.md`
> Runtime: `pest-plugin-browser ^5.0` (Playwright). **O plugin sobe o próprio servidor** — HTTP
> in-process, porta aleatória. Nada de Herd, `artisan serve`, Sail ou Vite dev server, e nada de
> `APP_URL` a configurar.
> Comando: `composer test:browser` (embute `npm run build` **e** `view:cache`).

## O Gate — por que este arquivo existe, e por que ele tem só dois cenários

`## Superfície de UI` do PRD tem duas linhas, e as duas passam pelo gate — mas por afirmações
diferentes, e cada uma com **exatamente um** mutante que nenhuma camada mais barata mata.

| Afirmação | Camada mais barata que prova | Está em |
|---|---|---|
| o botão do provedor está **presente** no HTML | HTTP | CT-08 (`04`) |
| ele está **depois** do formulário | HTTP (`assertSeeInOrder`) | CT-08 |
| o `href` aponta para a rota do provedor | HTTP | CT-08 |
| o ícone é o da marca daquele provedor | HTTP (marcador) | CT-08 |
| o predicado esconde o botão | HTTP | CT-01 |
| a rota indisponível responde 404 | HTTP | CT-03 |
| o callback autentica / recusa | HTTP | CT-10…CT-19 |
| o segredo não está no HTML | HTTP | CT-26, CT-27 |
| a gravação pela tela grava | componente Livewire | CT-21, CT-28, CT-33 |
| os campos de credencial **existem** e o schema os esconde/mostra | componente Livewire | CT-30, CT-31 |
| **o botão está VISÍVEL na tela renderizada** | **navegador** | **CT-B01** |
| **os campos APARECEM ao clicar no interruptor** | **navegador** | **CT-B02** |

As duas últimas linhas são o arquivo inteiro. As justificativas, uma por cenário:

- **CT-B01** — `assertSee` fica **verde com o botão presente no DOM e invisível**. Um erro de
  JavaScript em qualquer componente da tela de login, um contêiner colapsado pela CSS do Auth
  Designer, um `x-show` herdado, ou um `<svg>` de marca com `width="0"` deixam o botão no HTML e
  fora da tela. O botão é a **única porta** do login social: invisível, a feature está entregue e
  não existe. Esta entrega multiplica a superfície por quatro, e três dos quatro ícones são novos.
- **CT-B02** — o `->live()` do interruptor é um **round-trip de Livewire**. Num teste de
  componente, `fillForm()` muda o estado e a asserção seguinte reavalia o schema: **CT-30 fica
  verde com o `->live()` removido**, e no navegador a pessoa liga o interruptor e nada acontece
  até ela clicar em outro campo. É o mutante **M80** do `04`, declarado lá como matável só aqui.
  RQ-05 diz "abre os campos" — "abre" é um evento no navegador, não um estado no schema.

**Teto do perfil**: `completo` → 1 happy path + 1 erro visível. Os dois cenários ocupam o teto
exato. CT-B01 é o happy path; CT-B02 é o "erro visível" — a falha dele **é** a tela que não
responde ao clique.

---

## Pré-requisitos

- [ ] `npm run build` executado — sem `public/build/manifest.json` **toda** tela responde
      `ViteException` e todo cenário falha por um motivo que não é o dele.
- [ ] `view:cache` executado. Medido em `tests/Browser/CabecalhoDoMenuDoUsuarioTest`: com cache
      frio, `Timeout 45000ms exceeded` em 50 s; com cache quente, passa em 10,6 s. É
      determinístico, e o disfarce é cruel — numa máquina que acabou de rodar `composer test:kit`
      tudo passa, e num clone novo a suíte nasce vermelha com cara de teste instável.
- [ ] **Aquecimento pelo kernel no `beforeEach`.** O `view:cache` cobre as Blade do repositório; o
      primeiro render de um painel ainda paga a compilação dos **componentes Livewire do
      Filament** — ~25 s que o `view:cache` não adianta. Rodando este arquivo isolado ninguém
      pagou essa conta antes. A correção é um `$this->get(...)` da mesma tela no `beforeEach`,
      **fora** do cronômetro do Playwright. Não troque isto por um `timeout()` maior: 40 s e 60 s
      reproduzem a falha igual (registrado em `tests/Pest.php`).
- [ ] `tests/Browser/Screenshots` no `.gitignore` — é caminho fixo do plugin e recebe também os
      screenshots que o Pest grava sozinho quando um cenário de navegador falha.
- [ ] **Nunca `--parallel`.** Multiplica processos de navegador e produz timeout (medido: 4 de 11
      cenários caem). E como `--tia` exige run completo, `--parallel --tia` e os CT-B não convivem
      numa invocação só — são dois comandos, e é isso que o `04` declara como divergência da skill.
- [ ] Autenticação por `$this->actingAs($user)` **antes** do `visit()`. O servidor roda no mesmo
      processo, então `RefreshDatabase`, `:memory:` e `actingAs` valem dentro do navegador. Login
      pela tela custa ~20 s por cenário; o único cenário que faz login pela tela é o que testa o
      formulário de login, e não é nenhum destes.

---

## Seletores

O kit **não** tem `data-testid` (dívida conhecida, `.ai/rules/testes-browser.md`). O que existe:

| Elemento | Seletor | Já existe? |
|---|---|---|
| campo de senha da tela de login | `#form\.password` (o `.` precisa de escape em CSS) | **sim** — usado em `tests/Browser/LoginSocialGoogleTest.php:58` |
| botão de um provedor | `[aria-label="Entrar com Google"]`, `…GitHub"]`, `…LinkedIn"]`, `…X"]` | **sim para o Google** (`:60`); o formato `"Entrar com {rotulo}"` é preservado de propósito pelo ADR-08, então os três novos nascem no mesmo padrão |
| ícone dentro do botão | `[aria-label="Entrar com GitHub"] svg` | **sim** (o `<svg>` é filho do botão) |
| rodapé da tela de login | `.fi-login-rodape` | **sim** (`:62`) |
| interruptor de um provedor, na aba Login | `[id="form.login_github_habilitado"]` | **não** — nasce com esta entrega. `id` gerado pelo Filament a partir do nome da propriedade |
| campo de client_id de um provedor | `[id="form.login_github_client_id"]` | **não** — nasce com esta entrega |
| aba "Login" da tela de configurações | texto visível `Login` no cabeçalho de aba | **sim** |

**Nota de modo estrito do Playwright**: seletor que casa mais de um elemento é **erro**, não "o
primeiro". `[aria-label^="Entrar com"]` casaria os quatro botões e estouraria
`strict mode violation`. Todo seletor destes cenários é por atributo único.

**Nota sobre o `Toggle` do Filament**: ele **não** é um `<input type="checkbox">` — é um botão com
`role="switch"`. O plugin oferece `check()` e `assertChecked()`, e os dois miram checkbox; a ação
correta aqui é `click()`, e o oráculo é a **visibilidade dos campos**, não o estado do controle.
Conferido: `click()`, `assertVisible()`, `assertMissing()`, `assertAttributeContains()` e
`assertSeeIn()` existem no `pest-plugin-browser ^5.0` instalado.

---

## CT-B01: os quatro botões de provedor estão visíveis e clicáveis na tela de login

**Por que navegador e não HTTP/Livewire**: a asserção é **visibilidade renderizada**.
`assertSee` (CT-08 do `04`) fica verde com o elemento no DOM e fora da tela — e é exatamente o
que acontece quando um `<svg>` de marca vem com dimensão zero, quando o contêiner do render hook
é colapsado pela CSS do Auth Designer, ou quando um erro de JS na tela interrompe a montagem do
Alpine antes do bloco dos botões.

```gherkin
# language: pt

  Cenário: [CT-B01] os quatro botões aparecem visíveis, cada um com o ícone dele e o destino dele
    Dado os quatro provedores ligados com as três chaves preenchidas
    E o rodapé da tela de login configurado
    Quando o visitante abre a tela de login do painel /app num navegador
    Então o campo de senha está visível
    E o botão "Entrar com Google" está visível e aponta para /auth/google/redirect
    E o botão "Entrar com GitHub" está visível e aponta para /auth/github/redirect
    E o botão "Entrar com LinkedIn" está visível e aponta para /auth/linkedin-openid/redirect
    E o botão "Entrar com X" está visível e aponta para /auth/x/redirect
    E o ícone dentro de cada um dos quatro botões está visível
    E o rodapé está visível com o texto configurado
    E o console do navegador não registrou erro de JavaScript
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjo, no `beforeEach` | `config()->set([...])` com os 4 interruptores e os 4 blocos de `services.*` | — |
| 2 | aquecimento fora do cronômetro | `$this->get('/app/login')` (retorno descartado de propósito — interessa o efeito em disco) | — |
| 3 | abre a tela | `visit('/app/login')` | tela de login renderizada |
| 4 | âncora do formulário | `->assertVisible('#form\\.password')` | sem ela, "os botões estão na tela" não tem referência |
| 5 | por provedor | `->assertVisible('[aria-label="Entrar com GitHub"]')` | o botão na tela |
| 6 | por provedor | `->assertAttributeContains('[aria-label="Entrar com GitHub"]', 'href', '/auth/github/redirect')` | o destino |
| 7 | por provedor | `->assertVisible('[aria-label="Entrar com GitHub"] svg')` | o ícone **com dimensão**, que é o que HTTP não prova |
| 8 | rodapé | `->assertVisible('.fi-login-rodape')->assertSeeIn('.fi-login-rodape', 'todos os direitos reservados')` | regressão do CT-B01 ancestral |
| 9 | console | `->assertNoJavaScriptErrors()` | apoio, nunca oráculo único |

**Assertions** — `assertVisible` e não `assertPresent`: presente é o que o `04` já prova, e é
exatamente o que fica verde com o botão escondido. Nenhum `assertPathIs`, porque **nenhuma ação
navega** neste cenário. `assertNoJavaScriptErrors()` e **não** `assertNoSmoke()`: a tela de login
é de plugin de terceiro (`caresome/filament-auth-designer`), e o `assertNoSmoke()` deixaria a
suíte vermelha por `console.log` alheio que ninguém vai corrigir.

**Sem `actingAs`**, e é a única superfície da feature em que isso é correto: a tela de login é o
que o visitante sem sessão vê. Autenticar antes de visitar redirecionaria para o painel.

**O clique no botão está deliberadamente fora**, e o motivo é o mesmo do CT-B01 ancestral: o
`redirect()` do provedor falso aponta para `socialite.fake`, domínio que não resolve, então clicar
produziria erro de navegação do Playwright em vez de asserção. O `href` prova o destino sem sair
da página.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| MB1 | o `<svg>` de um dos três ícones novos vem sem `width`/`height` e renderiza com dimensão zero | CT-B01 (passo 7) — HTTP não distingue |
| MB2 | o contêiner do laço de botões é renderizado dentro de um bloco colapsado / com `hidden` | CT-B01 (passo 5) |
| MB3 | o blade novo estoura erro de JS na montagem e interrompe o resto da tela de login | CT-B01 (passo 9 **e** passo 4 — o campo de senha desapareceria) |
| MB4 | o espaçamento entre botões some e os quatro se sobrepõem, deixando três não-clicáveis | ⚠️ **sem matador** — `assertVisible` não prova área clicável nem sobreposição. **Lacuna declarada**: tentado `assertAttributeContains` de estilo (mede a declaração, não o layout) e `->click()` (sai para `socialite.fake`). Para defeito de layout não há saída barata — é screenshot e olhar (`.ai/rules/testes-browser.md`) |
| MB5 | o `aria-label` de um dos três novos foge do formato `"Entrar com {rotulo}"` | CT-B01 (o seletor não casaria) |
| MB6 | o rodapé é derrubado pelo blade novo (o render hook é o mesmo) | CT-B01 (passo 8) |

---

## CT-B02: ligar o interruptor de um provedor faz os campos de credencial dele APARECEREM

**Por que navegador e não Livewire**: o `->live()` do interruptor é um round-trip de Livewire, e a
asserção é sobre o que a **pessoa vê depois do clique**. No teste de componente (CT-30 do `04`) o
`fillForm()` muda o estado e a asserção reavalia o schema no mesmo ciclo — o cenário fica verde
com o `->live()` removido, e no navegador nada acontece até um segundo evento. É o mutante **M80**,
declarado no `04` como matável só aqui. RQ-05 diz "**abre** os campos"; "abre" é um evento.

```gherkin
  Cenário: [CT-B02] o interruptor do GitHub abre os campos de credencial dele, e só os dele
    Dado o administrador da instalação autenticado
    E os quatro provedores desligados
    Quando ele abre a aba "Login" das configurações do kit e liga o interruptor do GitHub
    Então os campos de client_id e client_secret do GitHub ficam visíveis na tela
    E os campos de credencial do Google, do LinkedIn e do X continuam fora da tela
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | persona e seeds | `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]); $this->actingAs(usuarioDoKit('admin'))` | — |
| 2 | aquecimento fora do cronômetro | `$this->get('/admin/configuracoes-do-kit')` | — |
| 3 | abre a tela | `visit('/admin/configuracoes-do-kit')` | a tela de configurações |
| 4 | vai para a aba | `->click('Login')` | a aba Login |
| 5 | estado inicial | `->assertMissing('[id="form.login_github_client_id"]')` | o campo fora da tela **antes** do clique — é ele que dá sentido ao passo seguinte |
| 6 | a ação, e a única | `->click('[id="form.login_github_habilitado"]')` | o interruptor ligado |
| 7 | o oráculo | `->assertVisible('[id="form.login_github_client_id"]')->assertVisible('[id="form.login_github_client_secret"]')` | os campos **apareceram**, sem nenhum outro evento |
| 8 | isolamento | `->assertMissing('[id="form.login_x_client_id"]')->assertMissing('[id="form.login_google_client_id"]')->assertMissing('[id="form.login_linkedin_openid_client_id"]')` | ligar um não abre os outros |

**Assertions** — o par dos passos 5 e 7 é o oráculo: sem o passo 5, uma implementação **sem**
`visible()` (campos sempre na tela) passaria no passo 7. Sem o passo 7, o `->live()` removido
passaria no passo 5.

Nenhum `assertPathIs`: nenhuma ação navega — o clique na aba e no interruptor são Livewire/Alpine
na mesma URL.

**`assertNoJavaScriptErrors()` NÃO é usado neste cenário, e isso é deliberado.** A tela de
configurações do kit tem `ColorPicker` dentro de `Tabs`, e o Chrome headless do Linux emite
`ResizeObserver loop completed with undelivered notifications` duas vezes na montagem — o do
Windows não. O plugin não oferece filtro (`assertNoJavaScriptErrors()` compara com array vazio,
`vendor/pestphp/pest-plugin-browser/src/Api/Concerns/MakesConsoleAssertions.php:78-89`), então a
asserção reprovaria **só no CI**, por dívida alheia. Custou um CI vermelho na feature
`settings-do-kit`. Os oráculos que provam o comportamento são os de visibilidade, e esses ficam.

**Sem espera por tempo.** Nunca `wait($segundos)`, e não existem `waitForText`,
`waitForSelector` nem `waitUntil` — o plugin reexecuta cada assertion até o teto de 45 s de
`tests/Pest.php`. E **nunca** `waitForEvent('networkidle')` em painel do Filament: ele nunca
resolve, porque o painel fica consultando as notificações e a rede não fica ociosa.

**Sobre o painel corrente**: este cenário arranja e visita o **mesmo** painel (`/admin`), então a
armadilha do `visit()` que renderiza a barra lateral do painel do arranjo não se aplica. O
`beforeEach` **não** arranja painel (`.ai/rules/testes-browser.md`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| MB7 | o interruptor não é `->live()` — os campos só aparecem no ciclo seguinte | **CT-B02** (passo 7). É o M80 do `04`, e este é o único cenário do conjunto que o mata |
| MB8 | os campos não têm `visible()` e estão sempre na tela | CT-B02 (passo 5) — e CT-30 do `04` |
| MB9 | o `visible()` de todos os provedores lê o interruptor do primeiro | CT-B02 (passo 8) — e CT-31 do `04` |
| MB10 | a seção do provedor nasce **colapsada** e o interruptor fica inalcançável sem um clique a mais | CT-B02 (passo 6 falharia no seletor) — o ADR-07 aceita o clique a mais, e este passo é o que registra quantos cliques são |
| MB11 | o `->live()` foi posto no `TextInput` em vez do `Toggle` | CT-B02 (passo 7) |

---

## Cogitado e Cortado

O gate é generoso e o teto é apertado. O que foi pensado e não entrou:

| Cenário cogitado | Por que foi cortado |
|---|---|
| clicar no botão do provedor e conferir a navegação para o provedor | o `redirect()` do provedor falso aponta para `socialite.fake`, domínio que não resolve: produz erro de navegação do Playwright, não asserção. O `href` (CT-B01, passo 6) prova o destino sem sair |
| os botões visíveis nos **três** painéis, um cenário por painel | mata o mesmo mutante três vezes. A cobertura dos três painéis é HTTP (CT-08 do `04`), exaustiva e ~40× mais barata; o navegador prova a **renderização**, que é a mesma view nos três |
| o mesmo CT-B01 em `->inDarkMode()` | `assertSee`/`assertVisible` **não validam tema**: passam com texto branco em fundo branco. Os três ícones novos são `currentColor` de propósito (ADR-08), justamente para seguirem o tema sozinhos. Para defeito de cor não há saída barata — é screenshot e olhar, e isso é captura de arte, não CT-B |
| auditoria de acessibilidade (`assertNoAccessibilityIssues`) na tela de login | vale como cenário próprio de outra entrega, não desta: os quatro botões nascem com `aria-label`, e o `aria-hidden` do `<svg>` é decisão do ADR-08. Sem uma linha de requisito sobre acessibilidade, seria escopo novo |
| preencher as credenciais no navegador e salvar | a gravação é componente Livewire (CT-21, CT-28, CT-29, CT-33 do `04`), milissegundos e sem Node. Empurrar gravação para o navegador é a decisão que mais destrói o orçamento de teste |
| o segredo fora do `wire:snapshot` no navegador | é HTTP puro (CT-26 do `04`): o oráculo é o corpo da resposta, e o navegador não acrescenta nada |
| um `visit([...])` em lote com as três telas de login | lote **aborta na primeira falha** e as rotas seguintes não são verificadas naquele run. E o que interessa aqui é a visibilidade dos botões, que exige asserção por seletor |

---

## Roteiro de Validação: Desenhado × Implementado

Preencher **depois** de implementar, olhando a tela. É o que separa "o cenário passou" de "a tela
está certa".

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | quatro botões, um por provedor disponível, abaixo do formulário | igual — laço sobre `ProvedorSocial::cases()`, cada botão visível com `href` para `/auth/{provedor}/redirect`; a posição "abaixo do form" não é asserida | sim | `tests/Browser/LoginSocialTest.php` · `it('mostra os quatro botoes de provedor visiveis, com icone e destino, na tela de login')` |
| 2 | divisor "ou" renderizado **uma vez**, antes do laço | — | não verificado — o CT-B01 não assere o divisor; fica no CT-09 do `04` | CT-09 do `04` |
| 3 | ícone da marca em cada botão, 18×18, `aria-hidden` | `svg[data-provedor]` visível com dimensão em cada botão; 18×18 e `aria-hidden` não asseridos | sim | `tests/Browser/LoginSocialTest.php` · `it('mostra os quatro botoes de provedor visiveis, com icone e destino, na tela de login')` |
| 4 | os três ícones novos em `currentColor`, seguindo tema claro/escuro | — | não verificado | screenshot, claro e escuro — não há CT-B |
| 5 | espaçamento entre botões quando há mais de um | — | não verificado | MB4, lacuna declarada — não há CT-B |
| 6 | uma `Section` colapsável por provedor na aba Login, rotulada com o nome do provedor | igual — aba `Login`, seção `Entrar com GitHub` nasce fechada e abre por clique; ligar o GitHub não abre campos de outro provedor | sim | `tests/Browser/LoginSocialTest.php` · `it('abre os campos de credencial de um provedor ao ligar o interruptor dele, e so os dele')` |
| 7 | interruptor `->live()` e as duas credenciais abrindo com ele | igual — `client_id` e `client_secret` ausentes antes do clique e visíveis depois, sem segundo evento | sim | `tests/Browser/LoginSocialTest.php` · `it('abre os campos de credencial de um provedor ao ligar o interruptor dele, e so os dele')` |
| 8 | `placeholder` do client_secret dizendo que em branco mantém | — | não verificado | inspeção — CT-28 do `04` prova o comportamento, não o texto |
| 9 | `helperText` do client_id citando **onde** criar o app OAuth e **qual** URI cadastrar, por provedor | — | não verificado | inspeção; RQ-10 pelo lado da tela |
| 10 | o rodapé fora das seções de provedor | — | não verificado — nenhum CT-B assere o campo do rodapé nas settings (o CT-B01 assere o rodapé **renderizado** na tela de login, que é outra coisa) | CT-32 do `04` |

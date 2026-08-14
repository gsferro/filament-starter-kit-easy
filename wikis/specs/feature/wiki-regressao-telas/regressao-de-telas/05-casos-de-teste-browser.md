# Casos de Teste de Browser — Regressão de telas

> Referência: `01-plano-acao.md` seção `## Superfície de UI`
> Runtime: `pest-plugin-browser` 5.0.1 (Playwright) — `vendor/bin/pest --testsuite=Browser`

## Pré-requisitos de Ambiente

- [x] `pestphp/pest-plugin-browser` 5.0.1 instalado (commit `8e5221d`)
- [x] `npm install --save-dev playwright@latest && npx playwright install` executados
- [x] **App servido pelo próprio plugin**, in-process, em porta aleatória
      (`http://127.0.0.1:{aleatória}`). Nenhum Herd, `php artisan serve`, Sail ou Vite dev
      server é necessário — e não há `APP_URL` a configurar. Confirmado por sonda (ADR-04).
- [x] **`npm run build` obrigatório antes de rodar.** Sem `public/build/manifest.json` toda
      tela responde `ViteException` e todo CT-B falha por um motivo que não é o dele. Por isso
      o build entra no script `composer test:browser`, e não só nesta lista.
- [x] `/tests/Browser/Screenshots` no `.gitignore`
- [x] Seeders determinísticos: `ShieldPermissionsSeeder` + `PapeisSeeder`, exatamente o par que
      `tests/Kit/PaineisTest.php:20-22` usa. Sem factory aleatória em campo assertado.
- [x] **`pest()->browser()->timeout(20_000)` em `tests/Pest.php`.** O default de 5 s não alcança
      o primeiro boot de um painel Filament em teste — sem opcache, com o Livewire compilando na
      primeira visita. É **teto** de reexecução de assertion, não espera fixa: cenário verde não
      gasta esse tempo. Descoberto na execução, não previsto no plano — ver D-03.

## Setup Global

### Autenticação

**Duas estratégias, e a escolha entre elas é deliberada:**

| Estratégia | Onde | Por quê |
|---|---|---|
| `$this->actingAs($user)` antes do `visit()` | CT-B01 a CT-B05, CT-B07, CT-B08 | funciona no browser porque o plugin roda in-process (ADR-04, fato 3). Cobrir 52 telas logando pela UI custaria ~20 s por cenário |
| Login real pela UI | **CT-B06, e só ele** | é o único caminho pelo qual um usuário de verdade entra. Se `actingAs()` fosse a única estratégia, uma quebra no formulário de login passaria despercebida |

### Estratégia de DB

- `RefreshDatabase` aplicado via `tests/Pest.php` no bloco `->in('Browser')` — o mesmo que
  `Kit` e `Tenancy` usam. Funciona no browser porque `:memory:` é o banco do processo, que é o
  processo do servidor.
- `beforeEach` chama `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])`.
  Herdado de `tests/Kit/PaineisTest.php:20-22`, não abstraído em helper novo — ver passo 4 do
  PRD.

### Device / Viewport

- Default: desktop.
- Variação coberta: `->inDarkMode()` (CT-B07). Mobile **não** coberto — o requisito não pede,
  e `sidebarCollapsibleOnDesktop()` nos três painéis significa que o layout mobile é o do
  Filament, não do kit.

### Seletores

Extraídos do HTML real da tela de login (`$page->content()`, filtrado com `grep`):

| Elemento | Seletor | Já existe? |
|---|---|---|
| Campo e-mail (login) | `#form\.email` | Sim — `id` gerado pelo Filament. O `.` precisa de escape em CSS |
| Campo senha (login) | `#form\.password` | Sim |
| Botão de submit do login | texto `Login` | Sim |
| Heading da tela de login | texto `Faça login` | Sim |
| Alternador → tema escuro | `[aria-label="Mudar para tema escuro"]` | Sim |
| Alternador → tema claro | `[aria-label="Mudar para tema claro"]` | Sim |
| Alternador → tema do sistema | `[aria-label="Mudar para tema do sistema"]` | Sim |

> **Nenhum elemento do kit tem `data-testid`.** Os seletores acima são `id` de framework,
> texto visível e `aria-label` — o melhor disponível hoje. Dívida DT-05.

---

## CT-B01 / CT-B02 / CT-B03: As telas autenticadas de cada painel abrem sem erro de JS

**Arquivo**: `tests/Browser/TelasDoKitTest.php`
**Método**: `it('abre as telas autenticadas do painel')` — dataset `app` / `admin` / `infra`

> **O inventário das rotas NÃO se repete aqui.** Ele vive em dois lugares e só dois: a tabela
> `## Superfície de UI` do `01-plano-acao.md` (o que foi desenhado) e o array do arquivo de
> teste (o que é executado). Uma terceira cópia neste arquivo foi o que produziu a única
> divergência de aritmética da rodada — o `05` dizia 48 telas quando o real era 52. Ver D-02.

### Precondições

- Seeders rodados
- `actingAs(usuarioDoKit('master_global'))` — é o único papel que abre as 52 telas, porque
  vence pelo `Gate::before` sem depender da matriz de permissões. O recorte por papel é do
  CT-B05

### Roteiro

| # | Ação | Código Pest | Resultado visível esperado |
|---|---|---|---|
| 1 | Visitar as rotas do painel em lote | `visit($rotas)` | cada tela renderiza |
| 2 | Verificar o console de cada uma | `->assertNoJavaScriptErrors()` | zero erro de JS |

Um `it()` com dataset, e não três cenários: o corpo é idêntico nos três painéis e o nome do
painel continua aparecendo na saída do Pest. O que é específico de cada um (`/infra/pulse` com
Pulse desligado, Resources de plugin no `/admin`) é comentário ao lado da rota que o motiva.

### Cobertura, e o que fica fora

| Painel | Telas no lote | Fora, e por quê |
|---|---|---|
| `/app` | 9 | as 3 públicas vão para CT-B04 — visitar login **autenticado** redireciona, e o teste mediria o redirecionamento |
| `/admin` | 17 | as 2 públicas vão para CT-B04 |
| `/infra` | 19 | as 2 públicas vão para CT-B04 |

9 + 17 + 19 + 7 públicas = **52**, que é 100% das alcançáveis por URL fixa.

### Assertions

- `assertNoJavaScriptErrors()` em cada rota do lote
- Sem âncora de persistência: nada é gravado

---

## CT-B04: As telas públicas abrem para quem não está autenticado

**Arquivo**: `tests/Browser/TelasDoKitTest.php`
**Método**: `it('abre as telas publicas dos tres paineis')`

### Precondições

- Seeders rodados
- **Nenhum `actingAs()`** — é o ponto do cenário

### Roteiro

As 7 rotas de login e de recuperação de senha dos três painéis. Lista no array do teste.

### Assertions

- `assertNoSmoke()` — e não `assertNoJavaScriptErrors()`: estas são as telas de **autoria do
  kit** (`TelaLogin`, `RegistroPorConvite` + Auth Designer), onde `console.log` é sujeira
  própria e vale pegar de graça. Ver ADR-06.

> Ficam fora, e não por esquecimento: `/*/screen/lock` exige sessão bloqueada, e
> `/*/password-reset/reset` exige token na query. São estado, não rota pública.

---

## CT-B05: Cada papel entra no seu painel e é barrado nos outros

**Arquivo**: `tests/Browser/PerfisTest.php`
**Método**: `it('recorta os paineis por papel na tela')` — com dataset

### Precondições

- Seeders rodados
- Um usuário por papel, criado com `usuarioDoKit($papel)`

### Roteiro

| # | Ação | Código Pest | Resultado visível esperado |
|---|---|---|---|
| 1 | Autenticar com o papel | `actingAs(usuarioDoKit($papel))` | — |
| 2 | Abrir o painel permitido | `visit($painelPermitido)` | dashboard renderiza |
| 3 | Abrir o painel negado | `visit($painelNegado)` | página de 403 **legível** |

Dataset:

| Papel | Entra em | É barrado em |
|---|---|---|
| `admin` | `/admin` | `/infra` |
| `infra` | `/infra` | `/admin` |
| `panel_user` | `/app` | `/admin` |

### Assertions

- No permitido: `assertNoJavaScriptErrors()`
- No negado: `assertSee('403')` **ou** o texto da página de erro do Filament — e
  `assertNoJavaScriptErrors()`. O que se prova aqui, e que
  `tests/Kit/PaineisTest.php:121-125` não prova: o barramento chega à tela como página
  legível, não como tela branca nem erro de JS.

---

## CT-B06: Login real pela UI, e o log da negativa

**Arquivo**: `tests/Browser/PerfisTest.php`
**Método**: `it('faz login pela tela e entra no painel')`

### Precondições

- Seeders rodados
- `usuarioDoKit('master_global', 'login@example.com')` com senha `password`
- **Sem `actingAs()`** — é o único CT-B que entra pela porta

### Roteiro

| # | Ação | Código Pest | Resultado visível esperado |
|---|---|---|---|
| 1 | Abrir o login | `visit('/app/login')` | `Faça login` visível |
| 2 | Preencher e-mail | `->fill('#form\\.email', 'login@example.com')` | campo preenchido |
| 3 | Preencher senha | `->fill('#form\\.password', 'password')` | campo preenchido |
| 4 | Enviar | `->press('Login')` | redireciona para o dashboard |

### Assertions

- `assertPathIs('/app')`
- `$this->assertAuthenticated()` — âncora única deste CT-B. Confirmado viável na sonda.
- `assertNoJavaScriptErrors()`

---

## CT-B07: As telas principais em tema escuro

**Arquivo**: `tests/Browser/TemaEscuroTest.php`
**Método**: `it('renderiza em tema escuro')`

### Precondições

- Seeders rodados; `actingAs(usuarioDoKit('master_global'))`
- O default dos painéis é `--default-theme-mode: system` (confirmado no HTML), então
  `->inDarkMode()` faz o navegador anunciar `prefers-color-scheme: dark` e o painel obedece

### Roteiro

| # | Ação | Código Pest | Resultado visível esperado |
|---|---|---|---|
| 1 | Abrir em modo escuro | `visit([...])->inDarkMode()` | telas em tema escuro |
| 2 | Verificar console | `->assertNoJavaScriptErrors()` | zero erro |

Rotas: os três dashboards (`/app`, `/admin`, `/infra`).

### Assertions

- `assertNoJavaScriptErrors()`
- `assertSee()` de um texto do layout, para garantir que a tela não veio vazia sob o tema

---

## CT-B08: O alternador de tema funciona na tela de login

**Arquivo**: `tests/Browser/TemaEscuroTest.php`
**Método**: `it('alterna o tema pela tela de login')`

### Precondições

- Nenhuma autenticação — o alternador está no `themeToggle()` do Auth Designer, e a tela de
  login é onde ele aparece sem sidebar competindo

### Roteiro

| # | Ação | Código Pest | Resultado visível esperado |
|---|---|---|---|
| 1 | Abrir o login | `visit('/app/login')` | `Faça login` visível |
| 2 | Clicar em tema escuro | `->click('[aria-label="Mudar para tema escuro"]')` | tema muda, sem recarregar |
| 3 | Confirmar que a tela sobreviveu | `->assertSee('Faça login')` | conteúdo intacto |

### Assertions

- `assertSee('Faça login')` depois do clique — a tela não recarrega nem esvazia
- `assertNoJavaScriptErrors()`

> **Por que não assertar a classe `dark` no `<html>`**: o Alpine grava a escolha em
> `localStorage` e aplica a classe num `x-effect`. Assertar o atributo tornaria o CT-B
> dependente do detalhe de implementação do Filament, que muda entre versões. O que interessa
> ao usuário é que a tela continua utilizável depois do clique — e é isso que se assere.

---

## CT-B09: Acessibilidade dos dashboards — `->todo()`, dívida conhecida

**Arquivo**: `tests/Browser/TemaEscuroTest.php`
**Método**: `it('nao tem problema de acessibilidade nos dashboards')->todo()`

### Precondições

- Seeders rodados; `actingAs(usuarioDoKit('master_global'))`

### Estado

**Marcado `->todo()` deliberadamente.** Rodando, ele falha — e a falha é real:

| Severidade | Problema | Painel | Origem |
|---|---|---|---|
| **critical** | `<button wire:click="clear">` do *Clear Cache* sem texto acessível: sem `aria-label`, sem `title`, sem texto interno | **`/infra`** | `cms-multi/filament-clear-cache` |
| **serious** | contraste 4.25:1 no `.environment-indicator` (`#e60076` sobre `#fdf2f8`); mínimo WCAG é 4.5:1 | os três, **só no tema claro** | `pxlrbt/filament-environment-indicator` |

Ambas em `vendor/`. Corrigir mexeria em `app/`, que RQ-07 coloca fora desta entrega.

`->todo()` e não comentado: assim a pendência aparece nomeada na saída de **todo** run, em vez
de dormir num comentário. Ver ADR-07 e `06-divida-tecnica.md` → DT-01, DT-02.

> **Duas correções que o `feature-quality-gate` fez nesta descrição** — ver `07-relatorio-qa.md`:
>
> 1. **A `critical` é do `/infra`, não do `/admin`** (QA-02). A primeira escrita atribuía ao
>    `/admin` porque a sonda a viu ali — mas o plugin é registrado só no `InfraPanelProvider`, e o
>    botão vazou entre painéis no mesmo processo (DT-08). `/admin` isolado tem **0** botões.
> 2. **Este cenário, como está, alcança só um dos dois achados** (QA-03). `visit([...])` aborta na
>    primeira falha, e o `/app` já falha no contraste — então o `/infra` nunca é avaliado e a
>    `critical` não aparece. Ao pagar a dívida, **separar o CT-B09 em um cenário por painel**.
> 3. A `serious` some no tema escuro: o elemento é `dark:fi-text-color-400`, que atravessa o
>    limiar (QA-04).

### Assertions (quando a dívida for paga)

- `assertNoAccessibilityIssues()` nos três dashboards

---

## Roteiro de Validação: Desenhado × Implementado

Preenchido após a execução. Resultado da suíte:
**11 cenários — 10 verdes, 1 `->todo()` — 82 asserções — 120 s**, em série, com
`vendor/bin/pest --testsuite=Browser`.

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | Superfície de UI: 52 telas alcançáveis por URL fixa nos 3 painéis | 52 rotas em 4 cenários de lote — **100% das alcançáveis**. Conferido por script contra `route:list`: 9 + 17 + 19 autenticadas + 7 públicas | ✅ | CT-B01–04 |
| 2 | Matriz papel × painel: `admin`/`infra`/`panel_user` recortados | dataset de 3 casos; painel permitido abre, painel negado mostra `403` legível, ambos sem erro de JS | ✅ | CT-B05 |
| 3 | Login real pela UI com `#form.email` / `#form.password` / `Login` | os 3 seletores funcionaram sem ajuste; `assertPathIs('/app')` + `assertAuthenticated()` | ✅ | CT-B06 |
| 4 | Dark mode via `->inDarkMode()` nos 3 dashboards | funciona. O texto de layout asserido é **`Painel de Controle`**, não `Dashboard` — o `05` não fixava o texto e a primeira escrita errou | ⚠️ | CT-B07, D-01 |
| 5 | Alternador de tema por `[aria-label="Mudar para tema escuro"]` | clica, tema muda, `Faça login` intacto, sem recarregar | ✅ | CT-B08 |
| 6 | Acessibilidade — esperado vermelho, marcado `->todo()` | nasceu `->todo()`; aparece como 1 skipped nomeado em todo run | ✅ | CT-B09, DT-01/02 |
| 7 | Suíte `Browser` fora do `composer test:kit` (grupo `browser`) | `--group=kit` em série: 213/213, 726 asserções — idêntico ao baseline pré-upgrade | ✅ | `tests/Pest.php` |
| 8 | Telas de `{record}` fora do lote (ADR-02) | nenhuma rota com `{record}` nos lotes | ✅ | — |
| 9 | `/*/screen/lock` fora do lote (exige sessão bloqueada) | fora, com o motivo no docblock do CT-B04. `/*/password-reset/reset` também, por exigir token na query — o `05` não mencionava essas 3 | ⚠️ | CT-B04, D-02 |
| 10 | *(não desenhado)* teto de espera do plugin | `pest()->browser()->timeout(20_000)` foi **necessário** e o `05` não previa | ⚠️ | D-03 |
| 11 | *(não desenhado)* CI | `php artisan test` passou a incluir a suíte `Browser`, e o job de CI não tem Node/Playwright — **quebraria** | ⚠️ | D-05 |

### Divergências encontradas

Replicadas em `03-progresso.md` → "Desvios do Plano".

- **D-01 — (a) CT-B errado.** O `05` pedia *"`assertSee()` de um texto do layout"* sem nomeá-lo.
  O `<h1>` dos três dashboards é **`Painel de Controle`** (tradução pt_BR do Filament,
  `vendor/filament/filament/resources/lang/pt_BR/pages/dashboard.php:5`), não `Dashboard`.
  Corrigido nos CT-B06 e CT-B07. **Causa raiz**: especificação vaga, não implementação
  divergente. Reforça DT-05 (`data-testid` tornaria o assert imune à tradução).

- **D-02 — (a) aritmética do `05` e do PRD.** A primeira escrita dizia 76 rotas GET e 48 telas
  alcançáveis, e as contagens por painel somavam 48. Os números reais, conferidos por script
  contra `route:list`: **74** rotas GET, das quais 13 com `{record}`, 3 de passkey e **6 que
  exigem estado ou token** (`/*/screen/lock`, `/*/password-reset/reset`) — sobrando **52**.
  As 6 de estado não estavam na primeira contagem. Corrigido em `00`, `01`, `02`, `04` e neste
  arquivo.

- **D-03 — (c) flake de timing, corrigido sem `wait()` fixo.** O teto de reexecução de
  assertion do plugin é 5 s. Não alcança o primeiro boot de um painel Filament em teste (sem
  opcache, Livewire compilando na primeira visita): o CT-B06 falhava em `assertPathIs` porque o
  redirect terminava depois do teto — e o screenshot pós-falha mostrava o dashboard **já
  renderizado**. Corrigido com `pest()->browser()->timeout(20_000)` em `tests/Pest.php`. É
  **teto**, não espera: cenário verde não gasta esse tempo. Deve entrar nos pré-requisitos de
  ambiente de futuras wikis com CT-B.

- **D-06 — (c) flake de ordem de assertion, e o `05` a especificava errado.** O roteiro do
  CT-B06 listava `press('Login')` → `assertPathIs` → `assertSee`, mas a primeira escrita do
  teste inverteu os dois últimos, e a suíte passou. Na reexecução depois do corte de
  duplicação, falhou: *"Expected to see text [Painel de Controle] on the page **initially with
  the url** [/app/login]"*. O screenshot da falha mostrava o dashboard **já renderizado** — o
  login funcionara.

  A causa é que **`assertPathIs` é a assertion que espera a navegação**; encadeado depois de
  `assertSee`, o `assertSee` é avaliado contra o snapshot da página anterior e o retry não
  recaptura. Ordem correta e agora comentada no teste:
  `press() → assertPathIs() → assertSee()`. Corrigido sem `wait()` fixo.

  **Vale como regra para futuros CT-B**: depois de qualquer ação que navegue, a assertion de
  path vem antes das de conteúdo.

- **D-04 — achado colateral, virou DT-03.** `pest --group=kit --parallel` dá 7 erros
  `Call to undefined function`. Pré-existente, não causado por esta entrega. Ver
  `06-divida-tecnica.md` → DT-03, severidade **bloqueante**.

- **D-05 — regressão introduzida por esta entrega, corrigida nela.** Registrar a suíte
  `Browser` em `phpunit.xml` fez `php artisan test` (sem argumentos) passar a incluí-la — e o
  job `qualidade` do `.github/workflows/ci.yml` não tem Node, browsers do Playwright nem
  `npm run build`. O CI quebraria em toda tela com `ViteException`. **Não é dívida a registrar,
  é defeito desta entrega**: corrigido com `--exclude-group=browser` no job de qualidade e um
  job `telas` novo. Verificado: `pest --exclude-group=browser --list-tests` retorna 0 testes de
  `Browser`.

## Índice de CT-B

| ID | Cenário | Rota | Arquivo |
|----|---------|------|---------|
| CT-B01–03 | 45 telas autenticadas dos 3 painéis (dataset) | lote | `tests/Browser/TelasDoKitTest.php` |
| CT-B04 | 7 telas públicas, sem autenticação, com `assertNoSmoke()` | lote | `tests/Browser/TelasDoKitTest.php` |
| CT-B05 | matriz papel × painel, com 403 legível | 3 painéis | `tests/Browser/PerfisTest.php` |
| CT-B06 | login real pela UI | `/app/login` | `tests/Browser/PerfisTest.php` |
| CT-B07 | tema escuro nos 3 dashboards | lote | `tests/Browser/TemaEscuroTest.php` |
| CT-B08 | alternador de tema | `/app/login` | `tests/Browser/TemaEscuroTest.php` |
| CT-B09 | acessibilidade — `->todo()`, dívida de vendor | lote | `tests/Browser/TemaEscuroTest.php` |

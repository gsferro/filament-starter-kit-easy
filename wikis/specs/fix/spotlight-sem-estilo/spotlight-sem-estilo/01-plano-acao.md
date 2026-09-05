# Plano de Ação — A busca ⌘K (Spotlight) abre fora da tela

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: correção
- **Wiki ancestral**: nenhuma wiki. O gatilho da busca foi corrigido antes desta wiki existir
  (CHANGELOG: "A busca ⌘K não aparecia na topbar" — mudança de render hook para
  `GLOBAL_SEARCH_BEFORE`). O CT-B F-45 em `tests/Browser/RoteiroDoKitTest.php:88` é o teste
  daquela correção, e é ele que fica verde com este defeito.
- **Motivo**: o overlay do pacote é injetado sem uma linha de CSS que o posicione.
- **Toca infra compartilhada?**: **sim** —
  1. `KitServiceProvider::configureCorrecoesDeCss()` (registro de assets dos três painéis)
  2. `KitUpdate::CAMINHOS_DO_KIT` e a varredura de `tests/Kit/KitUpdateTest.php`
  3. `tests/Browser/RoteiroDoKitTest.php` (o roteiro F-01…F-68)

  → regressão obrigatória sobre `KitUpdateTest`, `BoasVindasTest` (que assere o `kit-cards.css`) e
  o roteiro de browser.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Observação |
|----|----------|----------|------------|
| RQ-01 | overlay visível e utilizável | 1, 2 | CSS à mão + registro por `FilamentAsset` |
| RQ-02 | correção no kit | 1, 2 | idem |
| RQ-03 | teste que fica vermelho com o defeito | 4 | CT-B por **geometria**, não por `assertVisible`; mais a guarda de classes |
| RQ-04 | chega via `kit:update` | 3 | `resources/css/filament` entra na lista — e leva `kit.css` e `cards.css` junto, que nunca foram entregues |

## Objetivo

Fazer o overlay do Spotlight aparecer onde o pacote o desenha — ancorado à viewport, com fundo
escurecido e a caixa centralizada — nos três painéis, no kit e em qualquer projeto nascido dele,
**sem introduzir tema Vite nem Node como pré-requisito**. E deixar um teste que reprova se isso
voltar a quebrar.

## Contexto

O `wezlo/filament-search-spotlight` renderiza o overlay como componente Livewire no `BODY_END`,
com **66 utilitárias Tailwind** na blade. Ele conta com um tema compilado do painel para gerar
essas classes (README do pacote, "make sure it scans the package's views"). O kit não tem tema
compilado — decisão deliberada e já registrada como rule — e a CSS pré-compilada do Filament 5
tem **zero** dessas 66. O HTML sai byte a byte correto e sem estilo: `position: fixed` sem
`inset-0` deixa o elemento no fluxo normal, ao fim do documento, fora da viewport.

O kit **já passou por isto** com o `harvirsidhu/filament-cards` (`cards.css`, ADR-02 de
`pagina-boas-vindas`, rule `css-filament.md`). Este plano repete a solução, e não a rediscute.

## Análise dos Arquivos Existentes

### `resources/css/filament/cards.css` e `kit.css`

- Modelo a seguir: CSS à mão, cabeçalho explicando por quê, cores nas variáveis do Filament,
  **escopo** para não atropelar outros plugins, registro por `FilamentAsset`.

### `app/Providers/KitServiceProvider.php:351-366` — `configureCorrecoesDeCss()`

- Registra `kit-correcoes` e `kit-cards` sob `package: 'kit'`. O terceiro `Css::make` entra aqui.
  `php artisan filament:assets` publica em `public/css/kit/`.

### `app/Console/Commands/KitUpdate.php:84-176` — `CAMINHOS_DO_KIT`

- Tem `resources/views/*` (cinco entradas) e nenhum `resources/css`. `KitUpdateTest::DIRETORIOS_DE_CODIGO`
  (`tests/Kit/KitUpdateTest.php:92`) varre `app`, `database/*` e `resources/views` — por isso a
  ausência nunca acusou. É o mesmo furo que engoliu `resources/views/svg` na v0.23.0.

### `tests/Browser/RoteiroDoKitTest.php:88-100` — F-45

- Oráculo: `assertVisible('input[placeholder=…]')`. Verde com o overlay a 1.833 px do topo numa
  viewport de 1.117 px. O Playwright considera visível o que tem caixa não-vazia e não está
  `display:none`/`visibility:hidden` — posição fora da viewport não conta.

### `vendor/wezlo/filament-search-spotlight/resources/views/`

- `livewire/spotlight.blade.php` (raiz do componente, o overlay, o input, a lista),
  `partials/result.blade.php`, `partials/empty-state.blade.php`. As 66 classes saem daqui.
- A raiz do componente não tem classe própria estável; tem o atributo
  `x-on:open-spotlight.window="open()"` (`spotlight.blade.php:60`) — que é justamente o evento que
  o gatilho do kit dispara. É o âncora de escopo (ADR-02).

## Autorização

Nenhuma. CSS e lista de caminhos.

## Rotas

Nenhuma.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Overlay do Spotlight | Livewire (pacote) + CSS do kit | `/admin`, `/app/{org}`, `/infra` (topbar) | clicar na busca ou `Ctrl/⌘+K`; digitar; navegar com setas; Esc fecha | **Sim** |

**Gate de CT-B**: **passa**. O que se afirma — o overlay ancorado à viewport, com backdrop, acima
do conteúdo — é **layout**, e só o navegador prova. Um teste de componente Livewire vê o HTML
idêntico com e sem a correção. E, diferente do `cards.css` (cor, sem oráculo barato), aqui a
geometria **é** mensurável: `getBoundingClientRect()` e `getComputedStyle()` via `script()`.

**Gate de tela de escrita**: não há tela de escrita.

## Variáveis de Ambiente · Eventos · Jobs

Nenhum dos três.

## Impacto em Features Existentes

- **`BoasVindasTest` CT-05** assere `kit-cards.css` na resposta — o registro novo não pode mudar a
  ordem nem o nome dos existentes.
- **`KitUpdateTest`** — a varredura passa a cobrir `resources/css/filament`. `resources/css/app.css`
  e `resources/css/vendor/**` **não** entram: o primeiro é do skeleton, o segundo é publicado por
  pacote. Só o diretório `filament/` é do kit.
- **Todo plugin que emite as mesmas utilitárias** (`.flex`, `.p-4`, `.text-sm`…): é por isso que o
  CSS é **escopado**. Sem escopo, definir `.fixed` global mudaria o layout de qualquer blade de
  vendor que hoje renderiza essas classes sem estilo — e passaria a "funcionar" de um jeito que
  ninguém pediu.

## Rollback

`git revert`. Nenhum dado, nenhuma migration. `php artisan filament:assets` depois do revert
remove o arquivo publicado na próxima publicação (ele sobrescreve o diretório `public/css/kit/`).

## Dependências

Nenhuma. Sem pacote novo, sem npm.

## Riscos

- **Upgrade do pacote muda a blade** (classe nova, atributo de escopo renomeado): o overlay
  quebra em silêncio de novo. Mitigação: a guarda de classes do passo 4 lê a blade do vendor e
  reprova se emitir classe que o `spotlight.css` não declara, ou se o âncora de escopo sumir.
- **Tema escuro**: o Filament põe `class="dark"` no `<html>`; as variantes `dark:` viram
  `.dark [escopo] .classe`. Se o kit algum dia trocar a estratégia de tema, isto envelhece.
  Mitigação: o CT-B roda também em `inDarkMode()`.
- **`filament:assets` esquecido** em projeto atualizado: o CSS chega ao `resources/` e não ao
  `public/`. O aviso pós-update do `kit:update` já manda rodar; ver premissa de RQ-04 no `00`.

## Channel de Log da Feature

**Nenhum.** Não há código de runtime: é uma folha de CSS, uma linha de registro e uma entrada de
lista. Nada executa decisão de fluxo.

## Estrutura de Implementação

### 1. `resources/css/filament/spotlight.css` (RQ-01, RQ-02)

> Skills: `ponytail`

- **Path**: `resources/css/filament/spotlight.css` (novo)
- Cabeçalho no padrão de `cards.css`: por que existe, por que é escopado, o que o pacote exige e o
  kit recusa, "depois de mexer: `php artisan filament:assets`", e a lista das classes que a blade
  emite e o arquivo **não** cobre, se houver.
- **Escopo** (ADR-02): todo seletor sob `[x-on\:open-spotlight\.window]`, que é a raiz do
  componente. O overlay é a própria raiz, então as classes dela (`fixed inset-0 z-50 flex …`)
  vão em `[x-on\:open-spotlight\.window].fixed { … }` (seletor composto, sem espaço); as dos
  descendentes, em `[x-on\:open-spotlight\.window] .rounded-xl { … }`.
- **As 66 classes**, medidas em 2026-09-02 (`vendor/wezlo/filament-search-spotlight/resources/views/{livewire,partials}/*.blade.php`):

  ```text
  backdrop-blur-sm bg-gray-100 bg-gray-200 bg-gray-900/70 bg-transparent bg-white border border-0
  border-b border-gray-200 border-gray-300 dark:bg-gray-900 dark:bg-white/10 dark:bg-white/5
  dark:border-white/10 dark:ring-white/10 dark:text-gray-400 dark:text-white fixed flex flex-1
  flex-col flex-shrink-0 focus:ring-0 font-medium font-semibold gap-2 h-5 inset-0 items-center
  items-start justify-center max-h-[60vh] min-w-0 outline-none overflow-hidden overflow-y-auto
  p-4 pb-1 placeholder-gray-400 pt-2 pt-24 px-1.5 px-3 px-4 py-10 py-2 py-3 ring-1 ring-black/10
  rounded rounded-xl shadow-2xl text-base text-center text-gray-400 text-gray-500 text-gray-900
  text-sm text-xs tracking-wide truncate uppercase w-5 w-full z-50
  ```

- **Cores** pelas variáveis que o Filament emite em runtime (`--gray-50` … `--gray-950`), como o
  `cards.css`: `.bg-white` → `rgb(var(--gray-50))`? **Não** — `bg-white` é branco literal no
  Tailwind e o pacote o usa como fundo da caixa no tema claro; manter `#fff`. `text-gray-*`,
  `border-gray-*`, `bg-gray-*` → `rgb(var(--gray-N))`, para acompanhar a paleta do painel.
- **Variantes**: `dark:` → `.dark [escopo] …`; `focus:ring-0` → `[escopo] .focus\:ring-0:focus`;
  `/70`, `/10`, `/5` → `rgb(… / 0.7)` etc.; `max-h-[60vh]` → `.max-h-\[60vh\]`; `px-1.5` → `.px-1\.5`.
- **`z-50`**: `z-index: 50` — acima da topbar do Filament (`z-index: 20`) e abaixo de nada que
  importe; é o valor do próprio pacote.
- **Logs**: nenhum (CSS).

### 2. Registro do asset (RQ-01, RQ-02)

> Skills: `laravel-best-practices`

- **Path**: `app/Providers/KitServiceProvider.php`, `configureCorrecoesDeCss()`
- Acrescentar `Css::make('kit-spotlight', resource_path('css/filament/spotlight.css'))` ao array
  existente, com comentário curto apontando para o cabeçalho do arquivo — no mesmo tom do de
  `kit-cards`. **Depois dos dois existentes**: a ordem é a de registro, e `BoasVindasTest`
  assere o nome de `kit-cards.css`, não a posição; ainda assim, não há motivo para ir antes.
- Rodar `php artisan filament:assets` → `public/css/kit/kit-spotlight.css`.
- **Logs**: nenhum.

### 3. `kit:update` passa a entregar o CSS do kit (RQ-04)

> Skills: `laravel-best-practices`, `pest-testing`

- **Path**: `app/Console/Commands/KitUpdate.php`, `CAMINHOS_DO_KIT` — acrescentar
  `'resources/css/filament'` **e** `'public/css/kit'`, com comentário no padrão das entradas de
  `resources/views`: o que aconteceu (kit.css e cards.css nunca chegaram a quem atualiza;
  spotlight.css seria o terceiro), por que a varredura não pegou, e por que o publicado vai junto
  (`public/css/kit/` é versionado no kit — `git ls-files` — e entregá-lo dispensa o
  `filament:assets` para estes arquivos; ADR-03).
- **Path**: `tests/Kit/KitUpdateTest.php`, `DIRETORIOS_DE_CODIGO` — acrescentar
  `'resources/css/filament'` (o diretório, não `resources/css`: `app.css` é do skeleton e
  `vendor/` é publicado). Comentário curto com o motivo.
- Rodar `vendor/bin/pest --no-tia tests/Kit/KitUpdateTest.php` — o caso "cobre todo o código do
  kit" é o que passa a acusar CSS fora da lista.
- **Logs**: nenhum (o comando já loga o que entrega).

### 4. Testes (RQ-03)

> Skills: `pest-testing`. Especificação em `04-casos-de-teste.md` e `05-casos-de-teste-browser.md`.

Dois instrumentos, porque medem coisas diferentes:

- **Guarda de classes** (`tests/Kit`, sem navegador): lê as blades do vendor, extrai as classes,
  e reprova se alguma não estiver declarada em `spotlight.css` — **com o escopo**. E reprova se o
  atributo de escopo `x-on:open-spotlight.window` sumir da blade. É o que fica vermelho num
  upgrade do pacote, antes de alguém abrir o navegador. Mais: `GET /admin` traz
  `kit-spotlight.css` (o registro aconteceu e a publicação existe).
- **CT-B F-45 reescrito**: depois do clique, medir a raiz do overlay via `script()`:
  `position === 'fixed'`, `top === 0 && left === 0`, largura = `innerWidth`, altura =
  `innerHeight`, `z-index >= 50`, `background-color` **não** transparente, e o input do overlay
  dentro da viewport (`getBoundingClientRect().top < innerHeight`). É esta a asserção que **fica
  vermelha hoje** (`top: 1833`, fundo `rgba(0,0,0,0)`, `z-index: auto`). Repetir em
  `inDarkMode()`.
- O F-45 atual **não é apagado**: ele vira a primeira metade do novo (o overlay abre) e ganha a
  segunda (o overlay está onde deve).

### 5. CHANGELOG e documentação

> Skills: —

- `CHANGELOG.md`, seção `## [Unreleased]` → `### Corrigido`: o defeito (abre fora da tela, sem
  CSS), a causa (66 utilitárias, zero na CSS do Filament, sem tema compilado), a correção
  (`spotlight.css` via `FilamentAsset`, mesmo mecanismo do `cards.css`), e **a instrução para quem
  já instalou**: `php artisan kit:update` + `php artisan filament:assets`. E a nota de que
  `kit.css`/`cards.css` passam a ser entregues.

## Filosofia de Implementação

> **Ponytail em `full`.** O que a escada decide aqui:
> 1. **Reutilizar o padrão que existe** (`cards.css` + `FilamentAsset`), não criar tema.
> 2. **CSS à mão, não gerado**: gerar com Tailwind CLI exigiria dependência nova na raiz
>    (`CT-12` do site congela o `package.json`) ou um passo de build que o kit não tem. São 66
>    regras curtas.
> 3. **Nada além das 66 classes** que a blade emite hoje. Classe que o pacote não usa não entra.
> 4. **Sem publicar as views do pacote** — cópia que quebra a cada upgrade (é o argumento escrito
>    no `kit.css`).
>
> Atalhos deliberados com `ponytail:` comment. Depois: `/ponytail:ponytail-review` no diff.
>
> **Caveman `full`** na conversa; arquivos da wiki, código, commits e PR em prosa normal.

## Testes

> Ver `04-casos-de-teste.md` (guarda de classes, registro, `kit:update`) e
> `05-casos-de-teste-browser.md` (F-45 por geometria, claro e escuro).

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan filament:assets` — `public/css/kit/kit-spotlight.css` existe
- [ ] `vendor/bin/pest --no-tia tests/Kit/SpotlightCssTest.php tests/Kit/KitUpdateTest.php tests/Kit/BoasVindasTest.php --compact`
- [ ] `npm run build && php artisan view:cache && vendor/bin/pest --no-tia tests/Browser/RoteiroDoKitTest.php --filter=F-45` — **vermelho antes, verde depois** (rodar nas duas pontas)
- [ ] Screenshot do overlay aberto, claro e escuro, nos três painéis — olhar
- [ ] Numa instalação de `TESTES KIT/`: `php artisan kit:update` traz os três CSS; `filament:assets`; o overlay abre

## Commits

- `🐛 fix(spotlight): o overlay da busca ⌘K abria fora da tela — o kit não entregava CSS nenhum para ele`
- `🐛 fix(kit:update): entrega o CSS do kit — kit.css e cards.css nunca chegavam a quem atualiza`
- `✅ test(spotlight): F-45 mede a geometria do overlay, e a guarda de classes vigia a blade do pacote`
- `📝 docs(wiki): fix/spotlight-sem-estilo`

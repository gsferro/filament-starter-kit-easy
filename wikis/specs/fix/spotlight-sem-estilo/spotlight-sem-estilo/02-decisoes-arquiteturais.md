# Decisões Arquiteturais — A busca ⌘K abre fora da tela

## ADR-01: CSS à mão registrado por `FilamentAsset`, não tema compilado

**Status**: Aceita
**Data**: 2026-09-02
**Refina**: ADR-02 de `wikis/specs/feat/pagina-boas-vindas/` e a rule `.ai/rules/css-filament.md`

### Contexto

O `wezlo/filament-search-spotlight` emite 66 utilitárias Tailwind na blade do overlay e espera que
o painel tenha um tema compilado com `@source` nas views dele. O kit não tem tema compilado, e essa
é decisão anterior e documentada: os três painéis funcionam logo depois do `create-project`, sem
Node, porque toda a CSS vem pré-compilada do Filament e dos plugins. A CSS do Filament 5 tem
**zero** das 66.

### Decisão

`resources/css/filament/spotlight.css`, escrito à mão com exatamente as 66 classes, registrado em
`KitServiceProvider::configureCorrecoesDeCss()` por `Css::make('kit-spotlight', …)`, publicado por
`php artisan filament:assets` — o mesmo caminho de `kit.css` e `cards.css`.

### Alternativas Consideradas

1. **`viteTheme()` nos três painéis + `@source` nas views do pacote** — é o que o README do pacote
   manda. Torna `npm run build` pré-requisito de qualquer painel abrir; `kit:install --no-npm`
   deixaria de existir; toda instalação sem Node quebraria com `ViteException`. Contraria a
   decisão que a rule `css-filament.md` registra, e o custo é do kit inteiro, não desta feature.
2. **Publicar as views do pacote (`vendor:publish`) e reescrevê-las com classes `fi-*`** — cópia de
   três blades que quebra a cada upgrade. O `kit.css` já recusa essa saída por escrito.
3. **CSS gerado por Tailwind CLI a partir das views do vendor, e commitado** — resultado idêntico
   ao escolhido, mas exige `@tailwindcss/cli` (dependência nova na raiz, que `CT-12` da wiki
   `site-de-documentacao` congela de propósito) ou um segundo `vite.config`. Sessenta e seis regras
   curtas não pagam uma toolchain.
4. **Regras globais, sem escopo** (`.fixed { position: fixed }` …) — funcionaria para o Spotlight e
   mudaria o layout de toda blade de vendor que hoje emite essas classes sem estilo. Ver ADR-02.

### Consequências

- **Positivas**: sem dependência, sem build, sem cópia de view; o mecanismo já existe e já é rule.
- **Negativas**: o arquivo envelhece com a blade do pacote. Um upgrade que acrescente uma classe
  produz HTML correto sem estilo, em silêncio — o modo de falha desta família inteira. Mitigado
  pela guarda de classes (ADR-04), que lê a blade do vendor e reprova classe não declarada.
- **Negativas**: `bg-white` fica branco literal (é o que o Tailwind faz e o que o pacote desenha
  no tema claro); os cinzas saem de `--gray-*` para acompanhar a paleta do painel. Mistura
  deliberada, documentada no cabeçalho do arquivo.

### Referências

- `vendor/wezlo/filament-search-spotlight/README.md:43-48`
- `vendor/wezlo/filament-search-spotlight/src/FilamentSearchSpotlightPlugin.php:274`
- `resources/css/filament/cards.css` (cabeçalho), `app/Providers/KitServiceProvider.php:351-366`
- `.ai/rules/css-filament.md`

---

## ADR-02: o escopo é o atributo `x-on:open-spotlight.window` da raiz do componente

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

O `cards.css` é escopado em `.kit-cards-page`, classe que as páginas do kit acrescentam em
`getPageClasses()`. O Spotlight não oferece esse ponto: o componente é injetado pelo plugin no
`BODY_END`, a raiz dele não tem classe própria estável (só utilitárias) e o kit não controla a
blade. O Livewire acrescenta `wire:id` e `wire:snapshot` à raiz, mas o primeiro é aleatório e o
segundo é detalhe de serialização.

### Decisão

Todo seletor do `spotlight.css` fica sob `[x-on\:open-spotlight\.window]`. É o atributo Alpine da
raiz do componente (`spotlight.blade.php:60`), o Alpine **não** o remove do DOM, e ele é exatamente
o evento que o gatilho do kit (`resources/views/filament/spotlight-trigger.blade.php`) dispara —
se o pacote renomear o evento, o gatilho do kit quebra junto e ninguém deixa de perceber.

A raiz é o próprio overlay, então as classes dela entram como seletor composto
(`[x-on\:open-spotlight\.window].fixed`), e as dos descendentes com espaço
(`[x-on\:open-spotlight\.window] .rounded-xl`).

### Alternativas Consideradas

1. **Sem escopo** — atropela qualquer outra blade de vendor que emita `.flex`, `.p-4`, `.text-sm`.
   Hoje essas classes não fazem nada; passar a fazê-las funcionar "de graça" mudaria telas que
   ninguém pediu para mudar, e sem teste que acuse.
2. **`[wire\:snapshot*="filament-search-spotlight"]`** — funciona, mas o snapshot é interno do
   Livewire e pode mudar de forma ou sumir (lazy hydration) sem aviso.
3. **Um render hook próprio que envolva o componente num `<div class="kit-spotlight">`** — o hook
   do pacote é `BODY_END`; o kit não consegue envolver o que outro hook injeta, só emitir antes ou
   depois. Seria preciso desligar o registro do pacote e reinjetar o Livewire à mão: mais código
   que o CSS inteiro.

### Consequências

- **Positivas**: nenhuma linha de PHP ou Blade; o âncora é um contrato que o kit já depende.
- **Negativas**: o seletor é feio e precisa de escape (`\:` e `\.`). Um bloco de comentário no
  topo do arquivo explica, e a guarda de classes (ADR-04) confere que o atributo continua na blade.

### Referências

- `vendor/wezlo/filament-search-spotlight/resources/views/livewire/spotlight.blade.php:9-71`
- `resources/views/filament/spotlight-trigger.blade.php` (o `dispatchEvent(new CustomEvent('open-spotlight'))`)

---

## ADR-03: `resources/css/filament` entra em `CAMINHOS_DO_KIT` — e a varredura passa a olhar para lá

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

RQ-04 pede que a correção chegue a quem já instalou. Ao conferir `KitUpdate::CAMINHOS_DO_KIT`,
apareceu que **nenhum** CSS do kit está lá: `kit.css` (correções de cor dos plugins) e `cards.css`
(o hub em cartões) nunca chegaram a projeto atualizado. A varredura de `KitUpdateTest`
("cobre todo o código do kit, e não só o que alguém lembrou de listar") não olha `resources/css`
— o mesmo furo que engoliu `resources/views/svg` na v0.23.0, e a mesma lição: lista à mão
esquece; varredura acusa.

### Decisão

- `CAMINHOS_DO_KIT` ganha `'resources/css/filament'` (o diretório inteiro — hoje três arquivos,
  todos do kit) **e `'public/css/kit'`**: o publicado é versionado no kit (`git ls-files` lista
  `kit-cards.css` e `kit-correcoes.css`), então entregá-lo junto faz a correção valer sem
  `filament:assets` para estes arquivos. Achado da `feature-test-design`, não do plano original.
- `KitUpdateTest::DIRETORIOS_DE_CODIGO` ganha `'resources/css/filament'`. **Não** `resources/css`:
  `app.css` é do skeleton do Laravel (ponto de extensão de quem instala) e `resources/css/vendor/`
  é o que os pacotes publicam — os dois análogos de `Controller.php` e `resources/views/vendor/`,
  que a varredura já exclui.
- O `kit:update` **não** passa a rodar `filament:assets`: o aviso pós-update já lista o comando, e
  o `00` registra essa premissa (RQ-04). Chamar o comando de dentro do update é decisão de fluxo
  do instalador, não desta correção.

### Alternativas Consideradas

1. **Listar só `resources/css/filament/spotlight.css`** — deixaria `kit.css` e `cards.css` de fora
   de novo, e a varredura continuaria cega. É repetir a granularidade fina que o comentário de
   `'app/Filament'` já condenou ("só produziu esquecimento").
2. **`resources/css` inteiro na lista** — entregaria `app.css` por cima do do usuário.

### Consequências

- **Positivas**: a próxima folha de CSS do kit é entregue sem ninguém lembrar; e duas que já
  existiam passam a chegar.
- **Negativas**: quem atualiza recebe `kit.css`/`cards.css` pela primeira vez e precisa de
  `filament:assets` — que já é passo listado. O CHANGELOG diz isso em uma frase.

### Referências

- `app/Console/Commands/KitUpdate.php:84-176`, `tests/Kit/KitUpdateTest.php:92-176`
- CHANGELOG v0.23.1 (o caso de `resources/views/svg`)

---

## ADR-04: o oráculo do CT-B é geometria medida, e a guarda de classes vigia a blade do vendor

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

RQ-03 nasce de um teste verde com o defeito. `assertVisible` do Playwright considera visível o que
tem caixa não-vazia e não está oculto por `display`/`visibility` — posição fora da viewport
**não** torna o elemento invisível para ele. O F-45 media "o input existe e não está escondido",
e isso é verdade com o overlay a 1.833 px do topo.

Para o `cards.css` a wiki ancestral concluiu que "não existe assertion barata para 'está
pintado'" e ficou no screenshot. Para o Spotlight a situação é melhor: o defeito é de
**geometria**, e geometria se mede.

### Decisão

Dois instrumentos:

1. **CT-B F-45** ganha, depois do clique, uma medição via `script()` da raiz do overlay:
   `position: fixed`, `top === 0`, `left === 0`, `width === innerWidth`, `height === innerHeight`,
   `z-index >= 50`, `background-color` com alfa > 0, e o `input` do overlay com
   `getBoundingClientRect().top < innerHeight`. Cada uma dessas é falsa hoje (medido:
   `top: 1833`, `z-index: auto`, `rgba(0,0,0,0)`). Repetido em `inDarkMode()`, porque as variantes
   `dark:` são metade do arquivo.
2. **Guarda de classes** em `tests/Kit`: extrai as classes das três blades do vendor com o mesmo
   regex usado na medição desta wiki, e reprova se alguma não aparecer em `spotlight.css` **dentro
   do escopo**; reprova também se `x-on:open-spotlight.window` sumir da blade. Roda em
   milissegundos, sem navegador, e é o que acusa um upgrade do pacote antes de alguém olhar a tela.

### Alternativas Consideradas

1. **Só screenshot e olhar** — é o que a wiki dos cartões aceitou por falta de saída. Aqui há saída.
2. **Testar o CSS por `assertSee('kit-spotlight.css')` só** — prova o registro, não o efeito.
   Entra como asserção **auxiliar** na guarda, nunca como oráculo único.

### Consequências

- **Positivas**: a primeira vez neste kit em que "sem estilo" reprova por medição e não por olho.
- **Negativas**: `script()` devolve o que o Chromium calcula; se o Filament mudar o `z-index` da
  topbar acima de 50, o teste não vê (o overlay ficaria por baixo). Aceito: é o valor do pacote,
  e a topbar hoje é 20.

### Referências

- `tests/Browser/RoteiroDoKitTest.php:88-100`, `tests/Browser/HubDeCardsTest.php:10-20`
- `vendor/pestphp/pest-plugin-browser/src/Api/Webpage.php:85` (`script()`)
- Medição de 2026-09-02: `{"display":"flex","position":"fixed","zIndex":"auto","background":"rgba(0, 0, 0, 0)","top":1833,"viewportH":1117}`

---
paths:
  - 'resources/css/filament/**'
---

# Css Filament

## Utilitária que blade de vendor emite precisa existir no CSS do kit
O kit não tem tema Filament customizado (`viteTheme()` não é usado em nenhum painel), e a CSS pré-compilada do Filament 5 carrega quase só as classes `fi-*`. Pacote que renderiza blade própria com utilitárias Tailwind **não** ganha estilo de graça: medido no `harvirsidhu/filament-cards`, 51 das 53 utilitárias que a blade dele emite não existem lá.

Por isso `resources/css/filament/cards.css` é um subconjunto escrito à mão, escopado em `.kit-cards-page`, registrado por `FilamentAsset::register()` em `KitServiceProvider::configureCorrecoesDeCss()`.

**O modo de falhar é silencioso**: utilitária ausente produz HTML byte a byte correto e sem estilo nenhum. `assertSee`, `assertOk` e todo teste de componente ficam verdes, e a grade vira uma lista de links soltos.

Ao usar um recurso NOVO de um pacote assim (uma opção do componente, um campo que a blade só renderiza sob `@if`), abra a blade do vendor, liste as classes daquele bloco e confira cada uma no arquivo do kit ANTES de assumir que funciona. Depois de editar: `php artisan filament:assets`.

Ver ADR-02 de `wikis/specs/main/hub-de-navegacao-em-cards/` e ADR-04 de `wikis/specs/feature/v1-enriquecimento-kit/hub-de-cards-opcional/`.

**Segundo caso medido, pior que o primeiro**: o overlay do `wezlo/filament-search-spotlight` emite 66 utilitárias e a CSS do Filament tem **0**. Sem `resources/css/filament/spotlight.css` ele abria `fixed` sem `inset-0`, a 1.800 px do topo — fora da tela, em toda instalação, com o teste de browser verde. Ver `wikis/specs/fix/spotlight-sem-estilo/`.

## Quando o pacote não dá classe própria à raiz, o escopo é um atributo Alpine dela

`cards.css` escopa em `.kit-cards-page` porque a página é do kit e acrescenta a classe em `getPageClasses()`. Componente injetado pelo plugin (render hook `BODY_END`, `@livewire(...)`) não oferece isso: a raiz só tem utilitárias, `wire:id` é aleatório e `wire:snapshot` é detalhe do Livewire. Use o atributo `x-on:` que a raiz já carrega e que o kit já depende — no Spotlight, `[x-on\:open-spotlight\.window]`, o evento que o gatilho do kit dispara. O Alpine **não** remove `x-on:*` do DOM. Como a raiz é o próprio elemento estilizado, declare cada classe nas duas formas: composta (`[escopo].fixed`) e descendente (`[escopo] .fixed`).

Nunca defina a utilitária sem escopo: `.flex { display: flex }` global mudaria toda blade de vendor que hoje emite `flex` sem estilo.

**E não cite o glob `**/*` dentro do comentário do arquivo**: `*/` fecha o comentário CSS e o resto vira regra inválida. Foi teste que pegou (`tests/Kit/SpotlightCssTest.php`).

## Todo CSS desses tem uma guarda que lê a blade do vendor em runtime

Lista congelada de classes envelhece em silêncio no `composer update`. O padrão é `tests/Kit/SpotlightCssTest.php`: extrair as classes de `class="…"` das blades do pacote, exigir que cada uma apareça no CSS do kit **escapada e precedida do escopo**, exigir que nenhum seletor do arquivo fique fora do escopo, e um piso na contagem (controle positivo do detector: regex quebrado devolve lista vazia e "toda classe declarada" fica verde sobre nada). É o que acusa o upgrade do pacote antes de alguém abrir a tela. O `cards.css` ainda não tem a dele — dívida.

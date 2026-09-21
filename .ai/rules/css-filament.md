---
paths:
  - 'resources/css/filament/**'
  - 'app/Providers/**'
---

# Css Filament

## Utilitária que blade de vendor emite precisa existir no CSS do kit
O kit não tem tema Filament customizado (`viteTheme()` não é usado em nenhum painel), e a CSS pré-compilada do Filament 5 carrega quase só as classes `fi-*`. Pacote que renderiza blade própria com utilitárias Tailwind **não** ganha estilo de graça: as utilitárias que a blade emite não existem lá.

Por isso `resources/css/filament/cards.css` é um subconjunto escrito à mão, escopado em `.kit-cards-page`, registrado por `FilamentAsset::register()` em `KitServiceProvider::configureCorrecoesDeCss()`. Desde o `filament-cards` 1.1.0 a grade e as cores dos cartões vêm dos macros `grid()`/`color()` do Filament (já compilados); o arquivo cobre as utilitárias soltas que restam.

**O modo de falhar é silencioso**: utilitária ausente produz HTML byte a byte correto e sem estilo nenhum. `assertSee`, `assertOk` e todo teste de componente ficam verdes, e a grade vira uma lista de links soltos.

Ao usar um recurso NOVO de um pacote assim (uma opção do componente, um campo que a blade só renderiza sob `@if`), abra a blade do vendor, liste as classes daquele bloco e confira cada uma no arquivo do kit ANTES de assumir que funciona. Depois de editar: `php artisan filament:assets`.

Ver ADR-02 de `wikis/specs/main/hub-de-navegacao-em-cards/` e ADR-04 de `wikis/specs/feature/v1-enriquecimento-kit/hub-de-cards-opcional/`.

**Segundo caso medido, pior que o primeiro**: o overlay do `wezlo/filament-search-spotlight` emite 66 utilitárias e a CSS do Filament tem **0**. Sem `resources/css/filament/spotlight.css` ele abria `fixed` sem `inset-0`, a 1.800 px do topo — fora da tela, em toda instalação, com o teste de browser verde. Ver `wikis/specs/fix/spotlight-sem-estilo/`.

## Quando o pacote não dá classe própria à raiz, o escopo é um atributo Alpine dela

`cards.css` escopa em `.kit-cards-page` porque a página é do kit e acrescenta a classe em `getPageClasses()`. Componente injetado pelo plugin (render hook `BODY_END`, `@livewire(...)`) não oferece isso: a raiz só tem utilitárias, `wire:id` é aleatório e `wire:snapshot` é detalhe do Livewire. Use o atributo `x-on:` que a raiz já carrega e que o kit já depende — no Spotlight, `[x-on\:open-spotlight\.window]`, o evento que o gatilho do kit dispara. O Alpine **não** remove `x-on:*` do DOM. Como a raiz é o próprio elemento estilizado, declare cada classe nas duas formas: composta (`[escopo].fixed`) e descendente (`[escopo] .fixed`).

Nunca defina a utilitária sem escopo: `.flex { display: flex }` global mudaria toda blade de vendor que hoje emite `flex` sem estilo.

**E não cite o glob `**/*` dentro do comentário do arquivo**: `*/` fecha o comentário CSS e o resto vira regra inválida. Foi teste que pegou (`tests/Kit/SpotlightCssTest.php`).

## Todo CSS desses tem uma guarda que lê a blade do vendor em runtime

Lista congelada de classes envelhece em silêncio no `composer update`. O padrão é `tests/Kit/SpotlightCssTest.php`: extrair as classes de `class="…"` das blades do pacote, exigir que cada uma apareça no CSS do kit **escapada e precedida do escopo**, exigir que nenhum seletor do arquivo fique fora do escopo, e um piso na contagem (controle positivo do detector: regex quebrado devolve lista vazia e "toda classe declarada" fica verde sobre nada). É o que acusa o upgrade do pacote antes de alguém abrir a tela. O `cards.css` tem a dele em `tests/Kit/CardsCssTest.php`, com uma variação: lê o HTML **renderizado** dentro de `.fi-cards-page` em vez da blade do vendor, porque a blade emite classes condicionais de recursos que o kit não usa — e aceita classe coberta pela CSS compilada do Filament, não só pela do kit.

## CSS de plugin que declara `@layer` reordena a página inteira

Esta rule cobria **uma** direção: utilitária que a blade do vendor emite e o CSS do kit precisa
declarar. A direção inversa custou um defeito que foi ao ar — CSS de plugin que declara
`@layer` e, com isso, decide a precedência de **todas** as folhas da página.

O Filament 5 compila em cascade layers do Tailwind 4, na ordem
`properties, theme, base, components, utilities`. O preflight (`* { padding: 0 }`,
`button { background-color: transparent; border-radius: 0 }`) mora em `base`; `.fi-btn` mora em
`components`, que vem depois e por isso vence. É o desenho.

Só que a ordem das layers de uma página **não** é decidida por quem tem mais regras: é decidida
pela **primeira declaração de layer encontrada no documento**. E o `@filamentStyles` emite as
folhas dos plugins **antes** da `app.css` do Filament. A folha do `croustibat/filament-jobs-monitor`
está inteira em `@layer components{…}` e é registrada como asset **global**
(`FilamentJobsMonitorServiceProvider.php:36`), então é ela quem fixa a ordem:
`components, properties, theme, base, utilities`. Com `base` caindo depois de `components`, o reset
do `button` derrota o `.fi-btn`.

**O sintoma é o que torna isso caro**: todo botão dos três painéis sai sem padding, sem fundo e sem
borda arredondada — e mais nada muda. Todas as folhas respondem **200**, o console fica **limpo**, o
HTML sai byte a byte correto e a suíte fica **verde**. Nenhum oráculo de status, de console ou de
conteúdo alcança. Medido em 2026-09-21 com Playwright no `/admin/login`: bloqueando só aquela folha,
o botão volta a `padding 12px/8px`, fundo `primary-400`, `radius 8px`; bloquear qualquer outra, uma
a uma, não muda nada.

O kit fixa a ordem em `KitServiceProvider::configureOrdemDasCascadeLayers()`, emitindo
`<style>@layer properties, theme, base, components, utilities;</style>` no `STYLES_BEFORE` — que o
layout base do Filament emite logo antes do `@filamentStyles`, e por onde **todos** os layouts
passam, inclusive o do `caresome/filament-auth-designer`. Declaração vazia não cria regra nenhuma:
só decide quem vence quando duas layers disputam, e por isso cobre qualquer plugin futuro sem
enumerá-los.

**Não tente corrigir reescrevendo a folha do plugin**: ela é publicada por `filament:assets` e volta
ao original a cada `composer update` — a correção duraria até o próximo deploy e falharia em
silêncio de novo.

Guarda: `tests/Kit/OrdemDasCascadeLayersTest.php`. O `[CT-01]` exige que a **primeira** ocorrência de
`@layer` do HTML seja a do kit e venha antes da primeira folha. O `[CT-02]` é o controle positivo do
detector: lê as folhas publicadas em runtime e fica **vermelho** se nenhum plugin declarar mais
`@layer` — porque aí o `[CT-01]` estaria provando que uma correção inócua funciona.

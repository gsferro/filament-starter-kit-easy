# Decisões Arquiteturais — Ordem das cascade layers

## ADR-01: A ordem das layers é declarada no `STYLES_BEFORE`, e não se corrige a folha do plugin

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: RQ-09

### Contexto

O Filament 5 compila em cascade layers do Tailwind 4, na ordem
`properties, theme, base, components, utilities`. O preflight do `button`
(`padding: 0`, `background-color: transparent`, `border-radius: 0`) mora em `base`; o `.fi-btn`
mora em `components`, que vem depois e por isso vence.

A ordem de uma página, porém, é fixada pela **primeira declaração de layer do documento** — e o
`@filamentStyles` emite as folhas dos plugins **antes** da `app.css` do Filament. A folha do
`croustibat/filament-jobs-monitor` está inteira em `@layer components{…}` e é registrada como asset
**global**, então era ela quem fixava a ordem: `components, properties, theme, base, utilities`.
Com `base` depois de `components`, o reset derrotava o `.fi-btn`.

**Medido em 2026-09-21**, com Playwright, no `/admin/login` de uma instalação limpa:

| | sem correção | com correção |
|---|---|---|
| `paddingInline` | `0px` | `12px` |
| `paddingBlock` | `0px` | `8px` |
| `background-color` | `rgba(0,0,0,0)` | `oklch(0.828 0.189 84.429)` |
| `border-radius` | `0px` | `8px` |
| `height` | `20px` | `36px` |
| 1ª declaração de layer | `@layer components;` | `@layer properties, theme, base, components, utilities;` |

### Decisão

Emitir `<style>@layer properties, theme, base, components, utilities;</style>` no render hook
`PanelsRenderHook::STYLES_BEFORE`, em `KitServiceProvider::configureOrdemDasCascadeLayers()`.

### Alternativas Consideradas

1. **Reescrever a folha do plugin** (remover o `@layer components{…}`) — descartada, e é a
   alternativa perigosa: a folha é publicada por `filament:assets` e **volta ao original a cada
   `composer update`**. A correção duraria até o próximo deploy e falharia de novo em silêncio,
   que é o pior modo de falha possível para um defeito que já é invisível.
2. **Declarar a ordem dentro de `resources/css/filament/kit.css`** — descartada por chegar tarde:
   esse arquivo é registrado por `FilamentAsset::register()` e sai **depois** das folhas dos
   plugins. Uma declaração correta emitida depois da primeira já não muda nada.
3. **Enumerar os plugins ofensores e neutralizá-los um a um** — descartada por não escalar e por
   ser lista congelada. A declaração vazia cobre qualquer plugin futuro sem citá-lo.
4. **`$panel->renderHook()` nos três painéis** — descartada pela mesma razão de
   `configureTelaDeLogin()`: `FilamentView::registerRenderHook()` cobre os três com uma registração.

### Consequências

- **Positivas**: uma linha, sem tocar em vendor; cobre plugin futuro; sobrevive a `composer update`
- **Negativas**: o kit passa a depender do hook `STYLES_BEFORE` continuar sendo emitido antes do
  `@filamentStyles` no layout base do Filament. É contrato de vendor, e está citado no docblock
- **Riscos**: se um plugin passar a declarar layer **antes** do `STYLES_BEFORE` — por exemplo
  injetando no `<head>` por outro caminho —, a correção volta a não valer. Nenhum plugin hoje faz
  isso; a guarda `[CT-01]` reprova no dia em que algum fizer, porque ela exige que a **primeira**
  ocorrência de `@layer` seja a do kit

### Referências

- `app/Providers/KitServiceProvider.php:configureOrdemDasCascadeLayers()`
- `vendor/filament/filament/resources/views/components/layout/base.blade.php:44` — o `STYLES_BEFORE`
- `vendor/croustibat/filament-jobs-monitor/src/FilamentJobsMonitorServiceProvider.php:36` — o asset global
- `tests/Kit/OrdemDasCascadeLayersTest.php` — a guarda

---

## ADR-02: A guarda tem controle positivo, senão ela apodrece verde

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: RQ-08

### Contexto

O `[CT-01]` afirma que a declaração do kit é a primeira do documento. Essa afirmação continua
verdadeira — e **inútil** — no dia em que nenhum plugin declarar mais `@layer`: o kit seguiria
carregando para sempre uma correção que ninguém sabe mais por que existe, com um teste verde
atestando que ela "funciona".

### Decisão

O `[CT-02]` lê as folhas publicadas em `public/css/**` **em runtime** e fica **vermelho** quando
nenhuma folha de plugin declara `@layer`, com a mensagem de que a correção pode ter virado inócua e
merece reavaliação.

### Alternativas Consideradas

1. **Listar os plugins ofensores conhecidos** (jobs-monitor, pulse) — descartada: é a lista
   congelada que `.ai/rules/css-filament.md` já proíbe para esta família. Não reagiria a um
   `composer update` que acrescentasse um plugin novo.
2. **Não ter o `[CT-02]`** — descartada: é a diferença entre uma guarda e um enfeite.

### Consequências

- **Positivas**: a correção tem prazo de validade explícito e auditável
- **Negativas**: o `[CT-02]` fica vermelho num cenário que é, tecnicamente, uma boa notícia
  (os plugins se comportaram). É deliberado — a mensagem diz o que fazer
- **Riscos**: nenhum medido

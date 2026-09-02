# Progresso — A busca ⌘K (Spotlight) abre fora da tela

> Tipo **correção**; toca infra compartilhada (`KitServiceProvider`, `KitUpdate`, roteiro de
> browser) → regressão sobre `KitUpdateTest`, `BoasVindasTest` e `RoteiroDoKitTest`.

## 1. `resources/css/filament/spotlight.css`

- [x] Cabeçalho no padrão de `cards.css` (por quê, escopo, `filament:assets`)
- [x] As 66 classes, todas sob `[x-on\:open-spotlight\.window]`
- [x] Cada classe nas **duas** formas (composta e descendente) — mais simples que distinguir raiz
      de descendente, e o custo é uma vírgula por regra; `dark:` via `.dark`; escapes de `:` `/` `[` `.`
- [x] Cinzas em `var(--gray-N)` (o Filament 5 emite a cor pronta, não canais); `bg-white` literal;
      opacidade por `color-mix`, como no `cards.css`
- [x] Regra extra `[escopo][x-cloak] { display: none !important }`: a CSS do Filament não traz
      `[x-cloak]`, e com o overlay `fixed` ele cobriria a página até o Alpine iniciar

## 2. Registro do asset

- [x] `Css::make('kit-spotlight', …)` em `configureCorrecoesDeCss()`, depois dos dois existentes
- [x] `php artisan filament:assets` → `public/css/kit/kit-spotlight.css` (12,4 KB; `cmp` com a fonte: idêntico)

## 3. `kit:update` entrega o CSS do kit

- [x] `CAMINHOS_DO_KIT` += `resources/css/filament` **e** `public/css/kit` (premissa de RQ-04 no `04`)
- [x] `KitUpdateTest::DIRETORIOS_DE_CODIGO` += `resources/css/filament`
- [x] `KitUpdateTest` verde (a varredura passa a olhar CSS)

## 4. Testes

- [x] **R5 primeiro**: CT-B01 escrito e rodado contra o código **sem** a correção (2026-09-02):

  ```text
  F-45 … with data set "('escuro')"  tests/Browser/RoteiroDoKitTest.php:138
  o overlay abre a 1833px do topo (viewport de 1117px)
  Failed asserting that 1833 matches expected 0.
  ```

  A linha `claro` estourou `Timeout 45000ms` antes de medir — primeiro cenário do arquivo isolado,
  cache frio dos componentes Livewire (rule "aqueça pelo kernel"). Corrigido com um
  `$this->get('/admin')` antes do `visit()`; a geometria vermelha veio da linha `escuro`.
- [x] `tests/Kit/SpotlightCssTest.php` — CT-01, CT-02, CT-03
- [x] `tests/Kit/KitUpdateTest.php` — CT-04
- [x] `tests/Browser/RoteiroDoKitTest.php` — F-45 reescrito (CT-B01, dataset claro/escuro)
- [x] CT-B01 verde depois da correção: `2 passed, 28 assertions, 45.7 s`. Screenshots
      `spotlight-claro.png` e `spotlight-escuro.png` conferidos: overlay centralizado, backdrop
      com blur, caixa branca no claro e `gray-900` no escuro.
- [x] **CT-02 pegou um defeito real antes de existir código para pegar**: o cabeçalho do
      `spotlight.css` citava o glob `views/**/*` do README do pacote, e `*/` fecha o comentário —
      o resto do cabeçalho virava CSS inválido. O teste acusou "seletor fora do escopo" com o
      texto do comentário. Reescrito sem o glob.

## 5. CHANGELOG

- [ ] `### Corrigido` em `[Unreleased]`: defeito, causa, correção, instrução para quem já instalou
      (`kit:update` + `filament:assets`), e a nota de `kit.css`/`cards.css` passarem a ser entregues

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/pest --no-tia tests/Kit/SpotlightCssTest.php tests/Kit/KitUpdateTest.php tests/Kit/BoasVindasTest.php --compact`
- [ ] `npm run build && php artisan view:cache && vendor/bin/pest --no-tia tests/Browser/RoteiroDoKitTest.php --filter=F-45`
- [ ] Screenshots `spotlight-claro.png` e `spotlight-escuro.png` olhados
- [ ] `git diff` vazio depois de `php artisan filament:assets` (publicado = fonte)
- [ ] Numa instalação de `TESTES KIT/` (v0223-padrao): `php artisan kit:update` traz os três CSS
      e os publicados; o overlay abre — saída colada aqui
- [ ] Roteiro "Desenhado × Implementado" do `05` preenchido
- [ ] `git commit`

## Auditoria Pré-Implementação

### Captura do requisito

- A frase original chegou truncada e é descrição verbal (fidelidade baixa). Três perguntas
  fechadas com o solicitante **antes** de escrever: sintoma ("nada acontece"), o que vinha depois
  de "correção e" (teste que pegue + `kit:update`), nome da branch.
- **Boost `search-docs` indisponível** nesta sessão (MCP `laravel-boost` não conectou). Degradação
  declarada: `FilamentAsset::register()` e `Css::make()` foram confirmados no **código do kit**
  (`KitServiceProvider.php:351-366`, padrão em uso desde `cards.css`) e no vendor
  (`FilamentSearchSpotlightPlugin.php:274`), não na Documentation API.

### Medições que antecederam a wiki (2026-09-02)

| O que | Resultado |
|---|---|
| Classes emitidas pelas 3 blades do vendor | 66 |
| Dessas, presentes em `public/css/filament/filament/app.css` | **0** |
| Painéis com `viteTheme()` | 0 (decisão anterior — rule `css-filament.md`) |
| Overlay após o clique, `/admin`, kit | `position: fixed`, `z-index: auto`, fundo `rgba(0,0,0,0)`, `top: 1833` em viewport de 1117 |
| Versão do pacote nas 14 instalações de `TESTES KIT/` | 1.0.4, igual ao kit |
| `resources/css/filament` em `CAMINHOS_DO_KIT` | **ausente** — `kit.css` e `cards.css` nunca chegaram a projeto atualizado |
| `public/css/kit/*.css` | **versionado** no kit (`git ls-files`) |

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| Só `resources/css/filament` precisa entrar em `CAMINHOS_DO_KIT` | `public/css/kit/` é **versionado**: `git ls-files` lista `kit-cards.css` e `kit-correcoes.css`. Entregar só a fonte deixa quem atualiza dependente de `filament:assets` | `01` passo 3 e ADR-03 passam a entregar os dois diretórios; CT-04 ganhou a linha do publicado; pergunta registrada no `00` |
| `inDarkMode()` basta para CT-B02 | `TemaEscuroTest.php:97`: a emulação **vaza para o cenário seguinte** | CT-B01 declara `inLightMode()` explicitamente |
| O F-45 pode ficar como está e ganhar um caso novo | RQ-03 diz que **ele** está verde com o bug; um caso novo ao lado deixaria o F-45 mentindo | CT-B01 **é** o F-45 reescrito |
| O atalho `Ctrl+K` precisa de CT-B | `x-mousetrap.global.mod-k` é da blade do vendor e já funcionava; o defeito é onde o overlay abre, não se abre | cortado, registrado em "Cogitado e cortado" do `05` |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `delete:` 3º `Então` de CT-01 (raiz como seletor composto) — exigia distinguir raiz de descendente no parser; CT-B01 já mata M7 | sim | `04`, CT-01 e M7 |
| 2 | `shrink:` CT-B02 repetia clique, espera e `script()` para trocar uma asserção → linha `escuro` num `Esquema` de CT-B01 | sim | `05`; índice do `04` |
| 3 | `delete:` passo de docs "nada a mudar" | sim | `01`, passo 5 |
| 4 | `shrink:` linha de perfil para RQ-03, que o próprio texto dizia não ser cenário | sim | `04`, Perfil |
| 5 | `delete:` alternativa de palha na ADR-04 (snapshot do CSS) | sim | `02` |

`net: -1 cenário de browser (~-40 linhas)`.

## Blockers

- Nenhum.

## Desvios do Plano

<!-- pós-implementação -->

## Notas de Implementação

<!-- pós-implementação -->

## Retrospectiva

<!-- pós-implementação -->

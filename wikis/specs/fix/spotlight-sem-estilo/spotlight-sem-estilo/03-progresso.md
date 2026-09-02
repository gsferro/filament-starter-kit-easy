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

- [x] `### Corrigido` em `[Unreleased]`: três entradas — o overlay fora da tela, o `kit:update` sem
      CSS nenhum, o F-45 verde com o defeito

## 6. (Não planejado) O aviso da segunda rodada do `kit:update`

- [x] `encerrar()` recebe a origem e imprime o comando pronto:
      `php artisan kit:update --from=X --tag=Y --no-branch`, com o motivo. Ver "Desvios do Plano".

## Verificação Final

- [x] Revisão Ponytail no diff: um achado (CSS nesting/`:is()` cortaria a duplicação
      composta+descendente pela metade), **recusado** — a forma plana é greppável e CT-01 lê o
      escopo por regex. `net: 0`
- [x] `vendor/bin/pint --dirty --format agent` — passed
- [x] `pest --no-tia tests/Kit/SpotlightCssTest.php tests/Kit/KitUpdateTest.php tests/Kit/BoasVindasTest.php tests/Kit/HelpersDeTesteTest.php` — **84 testes, 156 asserções, verdes**
- [x] `view:cache && pest --no-tia tests/Browser/RoteiroDoKitTest.php --filter=F-45` — **2 passed, 28 assertions**
- [x] Screenshots `spotlight-claro.png` e `spotlight-escuro.png` olhados — overlay centralizado,
      blur no fundo, caixa no tema certo
- [x] `cmp resources/css/filament/spotlight.css public/css/kit/kit-spotlight.css` — idênticos
- [x] **`kit:update` numa instalação real** (`TESTES KIT/v0223-padrao`, v0.22.3, `git init` feito
      nela, `--repo` apontando para o clone local com a tag `v0.24.1-rc.spotlight`):

  | Rodada | Comando | Resultado |
  |---|---|---|
  | 1 | `--tag=v0.24.1-rc.spotlight --all` | 4 novos + 21 modificados; **nenhum CSS** — a lista é a do `KitUpdate.php` antigo |
  | — | `php artisan …` qualquer | **`View [svg.arte-do-login] not found`** — a rodada 1 entregou `IdentidadeDoKit.php` sem a view (lista antiga sem `resources/views/svg`). Destravado copiando a view do kit, como o CHANGELOG 0.23.1 instrui |
  | 2 sem `--from` | `--dry-run --no-branch` | **"Nada a atualizar"** — `config/kit.php` já dizia a versão nova |
  | 2 | `--from=0.22.3 --all --no-branch` (após commit da rodada 1) | `public/css/kit/kit-spotlight.css`, `resources/css/filament/spotlight.css`, `SpotlightCssTest.php` novos; `cmp` com o kit: **idênticos** |

- [x] Roteiro "Desenhado × Implementado" do `05` preenchido
- [x] Instalação nova a partir da branch (`TESTES KIT/spotlight-fix`, `composer create-project …
      dev-fix/spotlight-sem-estilo --repository=vcs local`) — ver "Notas de Implementação"
- [x] `git commit` — 5 commits na `fix/spotlight-sem-estilo`

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

| Onde | O plano dizia | O que foi feito | Por quê |
|---|---|---|---|
| `spotlight.css`, forma dos seletores | raiz como seletor composto, descendentes com espaço — o que exigia saber quais classes estão na raiz | **toda** classe nas duas formas (`[escopo].x, [escopo] .x`) | dispensa a distinção, custa uma vírgula por regra; CT-01 ficou mais simples (o 3º `Então` sobre a raiz caiu na auditoria Ponytail) |
| `spotlight.css`, `x-cloak` | não previsto | regra `[escopo][x-cloak] { display: none !important }` | a CSS do Filament não traz `[x-cloak]`; com o overlay `fixed` ele cobriria a página até o Alpine iniciar |
| Cores | `rgb(var(--gray-N))` | `var(--gray-N)` | o Filament 5 emite a cor pronta (oklch), não canais — é como o `cards.css` já faz |
| Passo 6, não planejado | — | o aviso pós-update imprime `--from=X --tag=Y --no-branch` | descoberto na validação de RQ-04: sem `--from` explícito a 2ª rodada não entrega nada. Sem isso, a correção **não chega** a quem atualiza — está dentro de RQ-04, não fora |

## Notas de Implementação

- **CT-02 pegou defeito antes de existir código para pegar.** O cabeçalho do CSS citava o glob
  `views/**/*` do README do pacote; `*/` fecha o comentário CSS e o resto do cabeçalho virava
  regra inválida. O teste reportou o texto do comentário como "seletor fora do escopo".
- **Primeiro cenário de arquivo de browser isolado estoura 45 s** (rule "aqueça pelo kernel").
  Um `$this->get('/admin')` antes do `visit()` resolve; entrou no F-45.
- **Dívida encontrada, fora desta entrega**: atualizar de v0.22.x direto para ≥ v0.23.0 pelo
  `kit:update` **quebra o boot** do projeto entre as duas rodadas — a lista antiga entrega
  `IdentidadeDoKit.php` sem `resources/views/svg`, e o service provider renderiza a view no boot.
  O CHANGELOG 0.23.1 documenta o contorno (copiar a view), mas o comando poderia (a) ler a lista
  da versão **destino** antes de aplicar, ou (b) o provider tolerar a view ausente. Candidata a
  wiki própria.
- **Instalação nova**: `composer create-project` a partir do clone local
  (`--repository='{"type":"vcs","url":"…/starter-kit-easy"}' dev-fix/spotlight-sem-estilo
  --stability=dev`) funciona — é a forma de validar uma branch antes da tag. **Não** respeita o
  `export-ignore`: para `dev-*` o Composer clona (source) em vez de baixar o dist, e `docs/` e
  `wikis/specs/` vieram junto. Artefato do método; a tag publicada vem por zip do GitHub, que
  respeita. Resultado em `TESTES KIT/spotlight-fix` (2026-09-02): `kit:install` completo
  (`Pronto!`, npm build 6 s, `kit-spotlight.css` publicado), e **dentro da instalação**
  `SpotlightCssTest` + `KitUpdateTest` = 45 testes verdes; F-45 nos dois temas = 2 passed, 28
  assertions.
- **Menu do `kit:update` com versões antigas** (relato do solicitante durante a sessão): o piso
  `PISO_DE_EXIBICAO = 0.23.0` está no kit desde a 0.24.0, mas o menu é montado pelo `KitUpdate.php`
  **da instalação**, que na v0.22.x não tem piso. Encurta na primeira atualização — e é mais um
  motivo para a 2ª rodada funcionar sem surpresa.

## Retrospectiva

- **Funcionou**: medir antes de escrever. A hipótese inicial ("HTML cru no rodapé") estava
  errada em um detalhe que importava — o overlay fica **abaixo** do fim da página, invisível —, e
  foi a medição via `script()` que virou o oráculo do teste. O mesmo `script()` já estava no `04`
  antes de qualquer código existir.
- **Funcionou**: o gate R5 (rodar o teste novo contra o código velho). Custou 90 s e é a única
  prova de que RQ-03 foi atendida.
- **Faltou no plano**: validar o `kit:update` numa instalação **antes** de escrever o plano.
  Teria mostrado as duas armadilhas da segunda rodada na pesquisa, não na verificação final.
- **Faltou no plano**: prever que instalação de `create-project` não é repositório git, e que
  o `kit:update` exige um. O `git init` na instalação de teste é reversível, mas não estava escrito.

# Progresso — Stat de logins do dia em "Usuários e acesso"

Wiki criada em 2026-09-04. Implementação ainda não iniciada.

## 1. Série de logins por dia, dentro de `UsuariosVisaoGeralStats`

- [ ] Constante `DIAS_DO_HISTORICO = 7`
- [ ] `loginsPorDia()` — 7 posições, chaveadas pelo rótulo do dia, da mais antiga até hoje
- [ ] Eixo construído a partir do **calendário**, não do resultado da consulta (dia vazio vale `0`)
- [ ] Agrupamento em PHP com `countBy()`, nunca `GROUP BY DATE()`
- [ ] `logDeAcessoDisponivel()` — guarda privada, com docblock dizendo por que **não** é `fonteDeDadosDisponivel()`
- [ ] Sem log

## 2. O sexto stat em `getStats()`

- [ ] `$stats[]` condicional, depois dos cinco existentes — acrescenta, não substitui
- [ ] Valor por `end($serie)`, nunca por índice calculado
- [ ] `->chart($serie)` e `->chartColor('success')`
- [ ] Docblock da classe reescrito — a frase "não há série temporal a desenhar" deixa de ser verdade
- [ ] Sem log

## Testes

- [ ] `tests/Kit/StatDeLoginsDoDiaTest.php` — CT-01 … CT-08

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --compact tests/Kit/StatDeLoginsDoDiaTest.php`
- [ ] Regressão (tipo `evolução`): `php artisan test --compact tests/Kit/PermissoesDeWidgetsTest.php tests/Kit/InventarioDeTelasTest.php`
- [ ] `vendor/bin/pest --parallel --tia`
- [ ] `pest --mutate --path=app/Filament/Admin/Widgets/UsuariosVisaoGeralStats.php`
- [ ] Abrir `/admin` e conferir 6 caixas, com o sparkline na sexta
- [ ] `git commit`

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| a Verificação Final rodaria `tests/Kit/PaginasAdminTest.php` | **o arquivo não existe.** `tests/Kit/` tem `PaginasInfraTest.php`, `KitAdminTest.php`, `AdminDaOrganizacaoTest.php` e `UsuarioAdminSeederTest.php` | trocado por `PermissoesDeWidgetsTest` + `InventarioDeTelasTest` no `01` e neste arquivo |
| o arranjo dos CT criaria a linha por `AuthenticationLog::create([...])` com as colunas do morph | `$fillable` do model do pacote **não** inclui `authenticatable_type`/`authenticatable_id`; o caminho é a relação `MorphMany` `$usuario->authentications()->create([...])` (`AuthenticationLoggable.php:11-14`) | **medido** com um teste descartável em `tests/Kit` (1 linha, `authenticatable_id` correto) e escrito no `04` → Fixtures |
| `pest --agent=` estaria disponível para sondagem | `pestphp/pest-plugin-agent` **não está instalado** — `vendor/pestphp/` tem pest, plugin, arch, browser, laravel, mutate, phpstan, profanity. `--agent` responde `Unknown option` | nenhuma parte da wiki depende dele; registrado para não ser sugerido |
| seis stats deixariam a linha harmônica (RQ-06) | **confirmado**, e é do vendor: `StatsOverviewWidget::getColumns()` devolve 3 colunas quando `count % 3 !== 1`. Com 5 → 3 colunas (3+2, ragged); com 6 → 3 colunas (3+3, cheio). Em 7 stats saltaria para 4 colunas | sem correção; registrado no `04` como cenário cortado (é comportamento do vendor, não código nosso) |
| `Stat::chart()` existe no Filament 5 e não precisa de dependência | confirmado: `Stat.php:106`; a view carrega o componente Alpine `stats-overview/stat/chart`, cujo JS importa `chart.js` — embarcado em `filament/widgets`, servido por `FilamentAsset`, fora do Vite | sem correção; virou ADR-01 |
| o `<canvas>` novo precisaria de CT-B para smoke de JS | `/admin` já está em `telasDoKit()['admin']` (`tests/Pest.php:250`) e `tests/Browser/TelasDoKitTest.php:41-45` roda `visit($rotas)->assertNoJavaScriptErrors()` sobre a lista | sem correção; sustenta a seção `## Sem CT-B` do `04` |
| os CT leriam os stats pelo HTML | `getStats()`/`getCachedStats()` são `protected` (`StatsOverviewWidget.php`); `->instance()` existe em `Testable:332` | `04` → *Como ler os stats* prescreve closure vinculada ao componente, com o motivo |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `shrink:` `array_values($serie)[self::DIAS_DO_HISTORICO - 1]` faz aritmética de índice sobre array chaveado por rótulo; um off-by-one exibiria **ontem** como hoje, com valor plausível | **sim** — `end($serie)`, e o defeito deixa de ser expressável | `01` passo 2 |
| 2 | `yagni:` `logDeAcessoDisponivel()` tem um único chamador; podia ser `rescue(...)` inline no `if` | **recusada** — o docblock dela é o que impede o rename para `fonteDeDadosDisponivel()`, que é exatamente o defeito de ADR-03. O comentário precisa morar em algum lugar, e um `if` inline não o comporta | `01` passo 1 |
| 3 | `delete:` "exatamente 1 stat tem gráfico" aparece em CT-01 e CT-07 | **recusada** — matam mutantes diferentes (M1/M3 contra M15/M16), e a asserção repetida é uma linha | `04` R1 e R5 |

Segunda passada não executada: o único corte aplicado encurtou uma expressão e não criou arquivo,
passo nem cenário — não há superfície nova a auditar.

## Degradações declaradas

- **`search-docs` indisponível.** O MCP `laravel-boost` respondeu `CONNECT_TIMEOUT` durante toda a
  sessão. A Documentation API não pôde ser consultada. Toda API citada — `Stat::chart()`,
  `Stat::chartColor()`, `StatsOverviewWidget::getColumns()`, `Testable::instance()`,
  `AuthenticationLoggable::authentications()` — foi confirmada por **leitura direta do vendor**,
  com `arquivo:linha` registrado no `01`, no `02` e neste arquivo. Duas delas foram além da
  leitura e foram **executadas** (o arranjo do log de acesso e a ausência do `--agent`).

## Blockers

- Nenhum.

## Desvios do Plano

_a preencher após a implementação._

## Notas de Implementação

_a preencher após a implementação._

## Retrospectiva

_a preencher após a implementação._

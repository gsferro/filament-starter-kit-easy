# Progresso — Stat de logins do dia em "Usuários e acesso"

Wiki criada em 2026-09-04. **Implementação concluída em 2026-09-04.**

## 1. Série de logins por dia, dentro de `UsuariosVisaoGeralStats`

- [x] Constante `DIAS_DO_HISTORICO = 7`
- [x] `loginsPorDia()` — 7 posições, chaveadas pelo rótulo do dia, da mais antiga até hoje
- [x] Eixo construído a partir do **calendário**, não do resultado da consulta (dia vazio vale `0`)
- [x] Agrupamento em PHP com `countBy()`, nunca `GROUP BY DATE()`
- [x] `logDeAcessoDisponivel()` — guarda privada, com docblock dizendo por que **não** é `fonteDeDadosDisponivel()`
- [x] Sem log

## 2. O sexto stat em `getStats()`

- [x] `$stats[]` condicional, depois dos cinco existentes — acrescenta, não substitui
- [x] Valor por `end($serie)`, nunca por índice calculado
- [x] `->chart($serie)` e `->chartColor('success')`
- [x] Docblock da classe reescrito — a frase "não há série temporal a desenhar" deixa de ser verdade
- [x] Sem log

## Testes

- [x] `tests/Kit/StatDeLoginsDoDiaTest.php` — CT-01 … CT-08

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff — nada a cortar; um arquivo de produção, dois métodos privados
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `php artisan test --compact tests/Kit/StatDeLoginsDoDiaTest.php` — **13/13** (8 CT, dois com dataset)
- [x] Regressão: `GraficosDoDashboardTest` + `PermissoesDeWidgetsTest` + os novos — **42/42**
- [x] `pest --mutate --path=app/Filament/Admin/Widgets/UsuariosVisaoGeralStats.php` — **91,36%**, 74 mortos, 7 não cobertos (ver Notas)
- [x] Seis caixas conferidas, com **um** stat tendo gráfico de **7 pontos** — por teste, não por navegador (CT-01 e CT-04)
- [x] `vendor/bin/pest --parallel --tia` — **executado**; a regressão foi por arquivo
- [x] `git commit` — **feito**

## Quality Gate Final — 2026-09-04

- **Veredito**: APROVADO no ciclo 1.
- **Rastreabilidade**: RQ-01..RQ-06 sem lacunas.
- **Regressão focal consolidada**: 114 testes e 163 assertions, verdes.
- **Relatório**: `06-relatorio-qa.md`.

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

Todos de **arranjo de teste**; os dois passos de produção saíram como o PRD escreveu.

- **Os cenários não usam `livewire()` do `pest-plugin-livewire`.** O plugin **não está instalado**
  neste projeto (`Call to undefined function Pest\Livewire\livewire()`). O padrão real do kit é
  `Livewire\Livewire::test(X::class)`, como em `tests/Kit/AbasDeListagemTest.php:50`. O `04` tinha
  prescrito a forma do plugin.
- **O tempo é fixado com `Carbon::setTestNow()`**, não `travelTo()`. Mesmo efeito para o que estes
  cenários precisam, e é a forma que não deixa resíduo entre casos sem um `travelBack()` explícito.
- **`valorDoStat()` acabou sendo necessário** — ver Notas.

## Notas de Implementação

- **`StatPlus::getValue()` NÃO devolve o inteiro.** Ele estende `OdometerStat`, e o retorno é um
  `HtmlString` com `<number-flow data-value="3">0</number-flow>`: o corpo da tag é `0`, porque o
  odômetro anima de zero até o valor **no navegador**, e só o atributo carrega o número. Seis
  cenários falharam com `Failed asserting that Illuminate\Support\HtmlString Object … is
  identical to 3` antes de isso ficar claro.
  O helper `valorDoStat()` extrai `data-value` por regex — o oráculo mais estreito disponível: o
  nome do atributo pinça onde o número vive, ao contrário de um `toContain('3')` no HTML, que
  casaria com qualquer `3` da marcação. **Isto é conhecimento reaproveitável**: qualquer CT futuro
  que afirme sobre o valor de um `StatPlus` cai na mesma armadilha.
- **`end()` recebe por referência.** `end($stat->getChart())` estoura
  `Only variables should be passed by reference` — precisa de variável. No código de produção não
  há problema, porque lá `end($serie)` já opera sobre uma variável local.
- **`tests/Kit/GraficosDoDashboardTest.php:300-303` continua verde**, e vale registrar por quê: ele
  afirma `is_a(UsuariosVisaoGeralStats::class, ApexChartWidget::class) === false`, que é exatamente
  a fronteira de ADR-01. O sparkline é `Stat::chart()`, não um widget de gráfico — se alguém
  "corrigir" a tensão da rule transformando este widget em `ApexChartWidget`, aquele caso reprova.
- **Discriminância medida, duas vezes.** Removendo `->where('login_successful', true)` da consulta,
  CT-02 reprova (12 passam, 1 falha). Trocando `DIAS_DO_HISTORICO` de 7 para 6, **três** casos
  reprovam (10 passam, 3 falham). Nem o filtro nem a constante são decorativos.
- **Mutation 91,36%, e os 7 não cobertos NÃO são lacuna de derivação** — são artefato da
  ferramenta. Três pares caem em linhas de **declaração** (`const DIAS_DO_HISTORICO` na 44,
  `$sort` na 46), que não executam em runtime: o driver de cobertura não atribui teste a elas, e o
  mutante nem chega a rodar. A prova de que a constante É falsificável está no item acima — trocar
  7 por 6 derruba três casos. O sétimo é `return 0;` na linha 192, dentro de
  `contarUsuariosComDoisFatores()`: é o caminho de `breezy_sessions` ausente, **código
  pré-existente** que esta wiki não tocou, e a tabela existe em toda a suíte. Lacuna real, mas de
  outra feature.

## Retrospectiva

- **Funcionou bem**: o `04` ter escolhido afirmar sobre os objetos `Stat` em vez do HTML. Quando o
  `getValue()` se revelou markup, o conserto foi um helper de seis linhas — se os cenários
  afirmassem `assertSee('3')` na página, todos passariam com a série errada.
- **Faltou no plano**: o `04` prescreveu `livewire()` do `pest-plugin-livewire` sem verificar que o
  plugin está instalado. A seção de verificação do stack de testes da skill pede isso
  explicitamente, e eu confirmei Pest, browser plugin e mutate — mas não o de Livewire.
- **Faltou no plano**: nada no `01` nem no `04` previu que `StatPlus::getValue()` devolve HTML.
  Era verificável em uma linha lendo `OdometerStat`, e teria economizado um ciclo de seis
  cenários vermelhos.

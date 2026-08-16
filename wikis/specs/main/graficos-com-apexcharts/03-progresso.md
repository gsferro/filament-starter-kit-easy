# Progresso — Gráficos com ApexCharts

> Plano: `01-plano-acao.md` · Requisito: `00-requisito.md`

## 1. Instalar o pacote

- [x] `composer require leandrocfe/filament-apex-charts:"^5.0"`

## 2. Registrar o plugin

- [x] `AdminPanelProvider` — `FilamentApexChartsPlugin::make()`
- [x] `InfraPanelProvider` — `FilamentApexChartsPlugin::make()`
- [x] `AppPanelProvider` **deliberadamente sem o plugin** (sem gráfico nesta entrega)

## 3. Migrar `IaExecucoesPorDia`

- [x] `extends ApexChartWidget`, mesmo nome de classe e mesmo caminho
- [x] Agregação em PHP preservada, com o comentário do porquê
- [x] Eixo montado pelo calendário preservado, com o comentário do porquê
- [x] Comparação com o período anterior preservada, agora no `$subheading`
- [x] `canView()` intacto
- [x] `$pollingInterval = null`

## 4. Rosca — convites por situação (`/admin`)

- [x] `app/Filament/Admin/Widgets/ConvitesPorSituacao.php`
- [x] Reusa `Convite::situacao()`, sem reescrever a derivação em SQL
- [x] Ordem e cor fixas por situação, situação ausente com zero
- [x] Estado vazio com `series` zerada, nunca array vazio

## 5. Rosca — execuções de IA por status (`/infra`)

- [x] `app/Filament/Infra/Widgets/IaExecucoesPorStatus.php`
- [x] Mapa de cor por status, com fallback `gray` para status desconhecido
- [x] `$pollingInterval = '60s'`

## 6. Radial — taxa de sucesso das filas (`/infra`)

- [x] `app/Filament/Infra/Widgets/FilasTaxaDeSucesso.php`
- [x] `failed` comparado com booleano, não com `1`/`0`
- [x] Jobs em andamento fora do denominador
- [x] Divisão por zero tratada
- [x] `$pollingInterval = '30s'`

## 7. Documentação do kit — a regra dos três pacotes

- [x] `wikis/receitas.md` → "Widget de dashboard" com a tabela de decisão
- [x] Quando rosca, quando radial, e quando **não** usar gráfico (com o caso `SaudeAplicacaoPorStatus`)
- [x] Checklist de widget novo (canView, polling, estado vazio, seeders, plugin no painel)
- [x] `wikis/pacotes.md` → "Já existe — não escreva de novo"
- [x] `wikis/convencoes.md` → a regra + a armadilha do polling de 5 s

## 8. Regenerar permissões

- [x] `ShieldPermissionsSeeder`
- [x] `PapeisSeeder`

## 9. README — dependência

- [x] Linha do `leandrocfe/filament-apex-charts` em `### UI e produtividade`
- [x] Descrição do `laboiteacode/filament-dashboard-widgets` corrigida (não faz mais "tendência")
- [x] Descrição do `flowframe/laravel-trend` revista — nenhum arquivo de `app/` o usa

## 10. Candidato a rule de projeto

- [x] Avaliado nos 4 gates
- [x] Apresentado ao usuário
- [x] Gravado manualmente em `.ai/rules/filament.md` (Qual pacote de widget) — `requirement-to-rule` indisponível nesta sessão

## Testes

- [x] `04-casos-de-teste.md` gerado pela skill `feature-test-design`
- [x] `05-casos-de-teste-browser.md` gerado
- [x] Valores limite cobertos: zero registros, um registro, zero processados, status fora do mapa
- [x] Testes de componente verdes
- [x] CT-B verdes

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `composer types:check`
- [x] `vendor/bin/pest --group=kit --compact`
- [x] `composer test:browser`
- [x] `/admin` e `/infra` abertos com banco **vazio** e com banco **semeado**
- [x] Aba Network: nenhum widget pollando a cada 5 s
- [x] Cor dos gráficos conferida em tema claro e escuro
- [x] Roteiro "Desenhado × Implementado" do `05-*-browser.md` preenchido
- [x] `git commit`

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| PRD dizia usar `flowframe/laravel-trend` na migração | nenhum arquivo de `app/` usa o pacote; `IaExecucoesPorDia` agrega em PHP de propósito (portabilidade entre bancos) | passo 3 reescrito para **preservar** a agregação em PHP; observação registrada no PRD e no passo 10 |
| CT-04 precisaria de `Convite::create([...])` por falta de factory | `database/factories/ConviteFactory.php` **existe** | `04` corrigido: os cenários usam `Convite::factory()`, conferindo os states antes |
| `queue_monitors` com `failed` booleano | confirmado em `FilasStats.php:44-52`, com o comentário de que PostgreSQL rejeita `failed = 1` | nenhuma; a regra R2 já espelha o cuidado |
| `ai_runs` tem coluna `status` | confirmado por `php artisan model:show Fomvasss\AiTasks\Models\AiRun` — `status` é `varchar` NOT NULL | nenhuma |
| `pestphp/pest-plugin-mutate` disponível | **não está** declarado no `composer.json`. Se existir em `vendor/`, é transitivo do Pest 5 e some num `composer update` | `04` registra o `composer require --dev` como pré-requisito do fechamento com mutation |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `delete:` widget `OrganizacoesAtivas` — duplica em gráfico o que a coluna `ativo` da listagem já mostra, só existe com tenancy ligada, e arrasta 1 permission + 1 arquivo em `tests/Tenancy` + os 4 valores de borda do CT-07. RQ-06 continua atendido por `FilasTaxaDeSucesso` | **sim** | `01` (passo 5 removido, passos renumerados), `02` (ADR-04), `03`, `04` (R3/CT-07 removidos) |
| 2 | `shrink:` CT-11 listava os 5 widgets à mão | **sim** | `04` — a lista passa a ser derivada dos widgets do painel, cobrindo também o widget que alguém criar amanhã |
| 3 | `delete:` migração do `IaExecucoesPorDia` | **recusada** — decisão explícita do usuário, e é ela que tira a exceção da regra do ADR-01 |

## Blockers

Nenhum.

## Desvios do Plano

| Passo | Desvio | Motivo |
|---|---|---|
| Testes — CT-11 | a asserção lê a propriedade por **reflexão**, não pelo getter | `Filament\Widgets\Concerns\CanPoll` declara `protected ?string $pollingInterval` **e** `protected function getPollingInterval()`. Ler o valor declarado é, aliás, mais fiel ao que o caso afirma: que a **classe declarou** o intervalo, não que algum override devolveu outra coisa em runtime |
| Testes | o `beforeEach` cria um papel à mão em vez de rodar os dois seeders | `ConviteFactory` resolve `role_id` a partir do primeiro papel existente. Os seeders custam segundos por caso, e nenhum caso deste arquivo é sobre autorização — o único que é (CT-10) semeia por conta própria |

## Notas de Implementação

### O default de polling é real, e é `protected`

Confirmado no vendor: `CanPoll::$pollingInterval = '5s'`. Os quatro gráficos declaram o próprio — `null` para os dois de dado raro, `'60s'` para o status de IA e `'30s'` para as filas.

O CT-11 **deriva a lista** dos widgets registrados nos painéis, em vez de listá-los à mão: assim ele cobre também o gráfico que alguém criar amanhã, que é exatamente quem vai esquecer a declaração.

### A guarda de fronteira dos três pacotes virou caso de teste

Acrescentado um caso curto que assere que `FilasStats` e `UsuariosVisaoGeralStats` **não** são `ApexChartWidget`. É a metade da regra que um refactor entusiasmado desfaria primeiro — transformar stat card em gráfico —, e ela não aparecia em nenhum outro cenário.

### Widget do Filament carrega adiado, e o gatilho é a viewport

O CT-B falhou na primeira execução com *"Expected element [#iaExecucoesPorDia svg.apexcharts-svg] to be present in the DOM"* — que se lê como "o gráfico não desenhou".

Não era isso. Uma sonda HTTP no `/infra` mostrou **3 widgets** na resposta inicial e **zero** ocorrência de `apexcharts`: os gráficos ficam abaixo da dobra e o carregamento adiado nunca é disparado. `->resize(1440, 4000)` no cenário resolve, e a razão está comentada no teste — sem ela o próximo a mexer refaz o diagnóstico inteiro.

### `screenshot()` recebe `bool $fullPage`, não um nome

`Webpage::screenshot(bool $fullPage = true, ?string $filename = null)`. Passar o nome posicionalmente estoura `TypeError`. Nomeie: `->screenshot(filename: 'hub-infraestrutura')`.

### Migração sem perda: a comparação de período virou `$subheading`

`variacaoContraPeriodoAnterior()` foi preservado intacto e agora alimenta o subtítulo. Os dois comentários que carregavam decisão — agregação em PHP por portabilidade de banco, eixo montado pelo calendário para que dia vazio apareça como zero — foram copiados junto, e o CT-08 cobre o segundo.

## Degradações declaradas

- **Boost MCP indisponível nesta sessão**: `search-docs` não estava conectado. A API do pacote foi confirmada lendo o código-fonte na branch `5.x` (`ApexChartWidget.php`, `Concerns/`, `composer.json`) e a documentação oficial. As assinaturas de `$heading`, `$subheading` e `$pollingInterval` ainda precisam de confirmação no vendor instalado — está escrito como passo explícito no PRD.

## Retrospectiva

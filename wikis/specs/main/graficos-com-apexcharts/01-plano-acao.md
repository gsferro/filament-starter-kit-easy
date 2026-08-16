# Plano de Ação — Gráficos com ApexCharts

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova, com **migração** de um widget existente
- **Wiki ancestral**: —
- **Motivo**: pacote novo (`leandrocfe/filament-apex-charts`) + uma regra nova de divisão de responsabilidade entre os três pacotes de widget do kit
- **Toca infra compartilhada?**: **sim** → dois `PanelProvider`, os dois dashboards que o kit entrega prontos e um widget existente que é **reescrito**.

> Regressão **obrigatória** sobre os dashboards do `/admin` e do `/infra`: `tests/Kit/PaginasInfraTest.php` e os cenários de browser que abrem os painéis precisam continuar verdes, porque widget que estoura derruba a página inteira do dashboard, não só o próprio card.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Pacote instalado e integrado | 1, 2 | registrado em `/admin` e `/infra` |
| RQ-02 | Gráfico passa a ser criado com este pacote | 3, 7, 8 | inclui a **migração** do único gráfico existente |
| RQ-03 | Stat card continua no StatPlus | 7 | nenhum `StatsOverviewWidget` é tocado |
| RQ-04 | Demais widgets continuam no dashboard-widgets | 7 | nenhum breakdown/segment/metric/goal/timeline é tocado |
| RQ-05 | Gráficos de rosca explorados | 4, 5 | `ConvitesPorSituacao` (admin), `IaExecucoesPorStatus` (infra) |
| RQ-06 | Gráficos de progresso radial explorados | 6 | `FilasTaxaDeSucesso` (infra). **`OrganizacoesAtivas` foi cortado na auditoria Ponytail** — duplicava em gráfico o que a coluna `ativo` da listagem já mostra, e só existia com tenancy ligada |
| RQ-07 | Documentado para os agentes | 7, 10 | tabela de decisão em `wikis/receitas.md` + candidato a rule |

## Objetivo

Adotar `leandrocfe/filament-apex-charts` como **o** pacote de gráfico do kit e encerrar a ambiguidade que existe hoje: três pacotes de widget instalados, sem uma regra escrita de qual usar para quê.

A regra que a entrega estabelece:

| O que você vai desenhar | Pacote |
|---|---|
| **Gráfico** (linha, área, barra, rosca, radial, radar, mapa de calor…) | `leandrocfe/filament-apex-charts` |
| **Stat card** (número grande, ícone, variação) | `gsferro/filament-stat-plus-easy` |
| **Todo o resto** (métrica, meta, breakdown, barra segmentada, timeline, lista, bullet) | `laboiteacode/filament-dashboard-widgets` |

Junto vêm três gráficos novos nos dashboards existentes — dois de rosca e um de progresso radial — cada um respondendo a uma pergunta que hoje nenhum widget responde.

## Contexto

O kit entrega hoje 20 widgets, distribuídos assim:

- **6 `StatsOverviewWidget`** com `StatPlus` — `UsuariosVisaoGeralStats`, `AgentesIaStats`, `AutenticacaoStats`, `FilasStats`, `IaStats`, `SaudeAplicacaoStats`
- **13 do `laboiteacode/filament-dashboard-widgets`** — 4 `BreakdownWidget`, 2 `SegmentBarWidget`, 2 `RecentItemsWidget`, `GoalProgressWidget`, `TimelineWidget`, `MetricWidget`, `BulletWidget` e **1 `TrendWidget`**
- **1 gráfico de verdade**: `IaExecucoesPorDia`, o `TrendWidget`, que desenha uma linha de área — e é justamente o que o RQ-02 realoca

Nenhum gráfico de rosca e nenhum radial existem hoje. Duas perguntas ficam sem resposta visual nos dashboards:

- *"das execuções de IA, quantas deram erro?"* — `ai_runs.status` não aparece em widget nenhum
- *"os jobs que rodaram, rodaram bem?"* — `FilasStats` mostra os três números soltos, sem a proporção

## Análise dos Arquivos Existentes

### `app/Filament/Infra/Widgets/IaExecucoesPorDia.php` — **será reescrito**

Hoje `extends TrendWidget`. Duas decisões dele **têm de sobreviver** à migração, com o comentário junto:

1. **Agregação em PHP**, não `GROUP BY DATE(created_at)`: *"a função de data muda de nome em cada banco (SQLite/MySQL/PostgreSQL) e o kit roda nos três"*. A janela de 14 dias limita o volume por construção.
2. **Eixo montado a partir do calendário**, não do resultado da consulta: *"dia sem execução tem que aparecer como zero, senão a linha 'pula' o buraco e uma parada de dois dias vira um trecho reto"*.

O que muda: `getTrend(): Trend` some; entra `getOptions(): array`. A comparação com o período anterior sai do `Trend::comparison()` e vira `$subheading` — o método `variacaoContraPeriodoAnterior()` continua igual.

> **`flowframe/laravel-trend` está em `composer.json` e não é usado por nenhum arquivo de `app/`.** Verificado por grep. Não é escopo desta wiki removê-lo, mas fica registrado: a nota do README que o descreve como "agregação por período para os gráficos dos widgets" não corresponde ao código.

### `app/Filament/Infra/Widgets/FilasStats.php:44-52`

Já calcula `$concluidos`, `$falhados` e `$emAndamento` sobre `queue_monitors`, com o cuidado de comparar `failed` com `false`/`true` (booleano) e não com `0`/`1`, porque *"PostgreSQL rejeita `failed = 1`"*. O radial do passo 6 usa a mesma consulta e o mesmo cuidado.

### `app/Filament/Infra/Widgets/SaudeAplicacaoPorStatus.php:15-21`

Traz uma decisão que **restringe** esta wiki: escolheu `SegmentBar` e **recusou rosca** para os health checks, com justificativa — *"a leitura que importa é 'quanto da barra ainda é verde'. Uma barra horizontal responde isso mais rápido que uma rosca, que obriga a comparar ângulos"*. Nenhum gráfico desta wiki deve reabrir essa decisão. Ver ADR-03.

### `app/Models/Convite.php:515-530` — `situacao()`

Deriva `Aceito` / `Recusado` / `Expirado` / `Pendente` de `aceito_em`, `recusado_em` e `expira_em`. Não há coluna de status. O comentário do método avisa que *"duas telas derivando o mesmo estado por dois caminhos é como a divergência volta"* — o widget do passo 4 **reusa o método**, não reescreve a regra em SQL.

### `app/Providers/Filament/{Admin,Infra}PanelProvider.php`

Ambos já fazem `->discoverWidgets(in: app_path('Filament/{Painel}/Widgets'), …)`: widget novo no diretório certo entra no dashboard sozinho, sem registro manual. O `AppPanelProvider` não recebe o plugin — não há gráfico no `/app` nesta entrega.

## Autorização

- **Policies**: nenhuma escrita à mão.
- **Gates**: nenhum.
- **Permissions novas**: **sim** — todo Widget entra em `FilamentShield::getEntitiesPermissions()`. Três widgets novos = três permissions novas (`View:ConvitesPorSituacao`, `View:IaExecucoesPorStatus`, `View:FilasTaxaDeSucesso`). O widget migrado **mantém o nome da classe**, logo mantém a permission — é o motivo de não renomear (ADR-02).
- **Obrigatório após criar os widgets** (regra de `.ai/rules/filament.md`):

```bash
php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
```

- **Subtração do painel `app`**: não se aplica — nenhum widget novo é do painel `app`.
- **`canView()` em todos**: cada widget novo verifica a existência da tabela que consulta, no padrão já usado no kit (`rescue(fn () => Schema::hasTable(…), false, report: false)`). Sem isso, uma instalação sem o pacote de filas ou sem IA derruba o dashboard inteiro.

## Rotas

Nenhuma rota nova. Widgets vivem dentro dos dashboards existentes.

| Método | URI | Name | Middleware |
|--------|-----|------|------------|
| — | — | — | — |

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Dashboard `/admin` | Filament | `/admin` | lê a rosca de convites por situação; passa o mouse para ver o tooltip | **Sim** — ApexCharts desenha SVG em runtime |
| Dashboard `/infra` | Filament | `/infra` | lê a rosca de status de IA, o radial de filas e a área de execuções por dia | **Sim** |

**Gate de CT-B**: o gráfico **não existe no HTML** que o servidor devolve — o ApexCharts o constrói no navegador a partir do array de opções. Um teste de componente prova que os **dados** estão certos; só o navegador prova que o **desenho** aparece. Ambos são necessários, e por motivos diferentes.

**Gate de tela de escrita**: nenhuma rota `create`/`edit` é criada ou alterada. Não se aplica.

## Variáveis de Ambiente

Nenhuma.

## Eventos / Listeners / Observers

Nenhum. Os widgets leem dados que outros fluxos já gravam (`RegistrarAiRun`, o monitor de filas, o fluxo de convites).

## Jobs / Queues

Nenhum job novo. **Cuidado com o inverso**: o `ApexChartWidget` tem `$pollingInterval` com **default de 5 segundos**. Cinco segundos por widget, por aba aberta, é uma consulta agregada a cada 5s multiplicada pelo número de gráficos. Todos os widgets desta wiki declaram `$pollingInterval = null` ou um valor explícito de dezenas de segundos. Ver ADR-04.

## Impacto em Features Existentes

- **Dashboard `/infra`**: sai um `TrendWidget`, entram três gráficos Apex. Se o widget migrado estourar, a página inteira cai — daí o CT de renderização.
- **Dashboard `/admin`**: um widget novo. Conferir se `$sort` e `$columnSpan` mantêm o layout legível; os widgets atuais usam `$sort` de 10 em 10.
- **Matriz de permissões**: três permissions novas nos painéis `admin` e `infra`. Nenhuma no `app`, logo a subtração do `panel_user` não muda.
- **`mddev31/filament-dynamic-dashboard`**: o dashboard configurável enxerga os widgets do painel. Widget novo aparece na lista de arrastar; sem ação.
- **Peso de página**: o ApexCharts é um JS considerável (~150 KB minificado) carregado nos dois painéis. Aceito: é a contrapartida de ter gráfico de verdade.
- **`laboiteacode/filament-dashboard-widgets` continua instalado e em uso** por 12 widgets. Nada a remover.

## Rollback

- **Migration down**: não há migration.
- **Reverter**: `git revert` do commit de migração devolve o `IaExecucoesPorDia` original; apagar os três widgets novos; remover `FilamentApexChartsPlugin::make()` dos dois providers; `composer remove leandrocfe/filament-apex-charts`; rodar os dois seeders.
- **Kill-switch por widget**: cada um tem `canView()`; revogar a permission esconde o gráfico sem tocar em código.

## Dependências

- **Composer**: `leandrocfe/filament-apex-charts` `^5.0` (a linha 5.x é a de Filament 5; requer `filament/widgets ^4|^5`, `illuminate/contracts ^11|^12|^13` e `livewire/livewire ^3|^4` — compatível com Laravel 13 e Filament 5.6 do kit)
- **NPM**: nenhuma. O pacote registra o JS do ApexCharts pelo `FilamentAsset`; não depende do Vite do app nem de tema customizado.

## Riscos

- **Polling default de 5 s** — o risco de performance da entrega. Mitigação: ADR-04 e `$pollingInterval` explícito em todos os widgets.
- **Widget que estoura derruba o dashboard inteiro** — não só o card. Mitigação: `canView()` com `Schema::hasTable()` em todos, e um CT por widget que renderiza com tabela vazia.
- **Divisão por zero no radial**: `FilasTaxaDeSucesso` divide por `concluidos + falhados`. Base zero em instalação nova é o caso **normal**, não o excepcional. Mitigação: o radial devolve `[0]` com legenda de estado vazio, e há CT de valor limite.
- **Perda de informação na migração**: o `TrendWidget` mostra a comparação com o período anterior; se ela sumir na reescrita, o widget migrado é pior que o original. Mitigação: a comparação vira `$subheading`, e há CT que a assere.
- **Cor fora do tema**: o ApexCharts usa a paleta própria dele, não as variáveis do Filament. Um gráfico verde num painel pintado de roxo pela identidade visual da organização é regressão visual. Mitigação: ADR-05.

## Channel de Log da Feature

### Verificação de Channel Existente

Channels do kit em `config/logging.php`: `ai` (85), `tenancy` (93), `autenticacao` (101).

### Decisão

**Nenhum channel novo, e nenhum log novo.**

Todos os widgets são leitura agregada para exibição. Não há decisão de fluxo, escrita, chamada externa nem falha a capturar — e com `canView()` protegendo cada consulta, o modo de falha "tabela não existe" nem chega a acontecer.

Um `Log::info('gráfico renderizado')` seria emitido a cada polling, de cada widget, de cada aba aberta: exatamente o tipo de ruído que a seção "Padrão de log" de `wikis/convencoes.md` existe para evitar.

> **Exceção considerada e recusada**: logar quando uma agregação demora. Isso é trabalho do `laravel/pulse`, que o kit já tem, com página própria no `/infra`.

## Estrutura de Implementação

### 1. Instalar o pacote

> Skills: `ponytail`

```bash
composer require leandrocfe/filament-apex-charts:"^5.0"
```

### 2. Registrar o plugin em `/admin` e `/infra`

> Skills: `laravel-best-practices`

- **Paths**: `app/Providers/Filament/AdminPanelProvider.php`, `app/Providers/Filament/InfraPanelProvider.php`, dentro de `->plugins([...])`

```php
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;

// …
FilamentApexChartsPlugin::make(),
```

- **Não** no `AppPanelProvider`: não há gráfico no `/app` nesta entrega (ver `00-requisito.md`). Quem acrescentar o primeiro gráfico lá registra o plugin junto — **e isso precisa estar na receita**, porque é o mesmo modo de falha do lightbox: sem o plugin, a tela quebra na renderização.

### 3. Migrar `IaExecucoesPorDia` para ApexCharts

> Skills: `laravel-best-practices`, `pest-testing`

- **Path**: `app/Filament/Infra/Widgets/IaExecucoesPorDia.php` (**mesmo nome de classe** — ver ADR-02)

```php
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class IaExecucoesPorDia extends ApexChartWidget
{
    protected static ?string $chartId = 'iaExecucoesPorDia';

    protected static ?string $heading = 'Execuções de IA por dia';

    protected static ?int $sort = 80;

    protected int|string|array $columnSpan = 'full';

    // Sem polling: o dado é diário. O default do pacote é 5 s (ADR-04).
    protected static ?string $pollingInterval = null;

    protected function getOptions(): array
    {
        // eixo pelo CALENDÁRIO, agregação em PHP — ver comentários preservados
    }
}
```

- **Preservar, com o comentário original**: a agregação em PHP (`contarPorDia()`) e a construção do eixo pelo calendário. Os dois comentários explicam decisões que a reescrita apagaria sem querer.
- **`getDescription()` vira `$subheading`** — via `getSubheading()`, para manter a comparação percentual com os 14 dias anteriores. `variacaoContraPeriodoAnterior()` fica como está.
- **`canView()`** permanece idêntico (`Schema::hasTable(config('ai-tasks.table'))`).
- **Tipo do gráfico**: `area`, o mesmo desenho de hoje (`Trend::type('area')`).
- **Confirmar antes de escrever** a assinatura de `$pollingInterval`, `$heading` e `$subheading` em `vendor/leandrocfe/filament-apex-charts/src/Concerns/HasHeader.php` e `Filament\Widgets\Concerns\CanPoll` — propriedade estática vs. método muda entre versões, e errar aqui é `TypeError` no dashboard.

### 4. Rosca — convites por situação (`/admin`)

> Skills: `laravel-best-practices`, `eloquent-best-practices`

- **Path**: `app/Filament/Admin/Widgets/ConvitesPorSituacao.php`
- **Pergunta que responde**: dos convites enviados, quantos viraram conta e quantos morreram no caminho?
- **Por que rosca**: as quatro situações são mutuamente exclusivas e somam o total — é o caso canônico de composição. Diferente dos health checks (`SaudeAplicacaoPorStatus`), aqui a leitura procurada é *proporção entre categorias*, não *"quanto ainda é verde"*.
- **Fonte do dado — reusar `Convite::situacao()`**, nunca reescrever a regra em SQL:

```php
$porSituacao = Convite::query()
    // só as três colunas que a derivação usa
    ->get(['aceito_em', 'recusado_em', 'expira_em'])
    ->countBy(fn (Convite $convite): string => $convite->situacao());
```

O comentário de `Convite::situacao()` avisa que duas telas derivando o mesmo estado por caminhos diferentes é como a divergência volta — e "Aceito vence Expirado" é exatamente a regra que um `whereNull('aceito_em')->where('expira_em','<',now())` escrito à mão erraria. O custo de carregar três colunas é aceito pelo mesmo argumento do `contarPorDia()`.

- **Ordem e cor fixas por situação**: Aceito (success), Pendente (warning), Recusado (danger), Expirado (gray). Situação ausente entra com zero — senão a legenda muda de cor conforme o dado, e a leitura entre duas visitas deixa de ser comparável.
- **Estado vazio**: sem nenhum convite, `series` = `[0,0,0,0]` e um `$subheading` dizendo que ainda não há convites. Não devolver array vazio: o ApexCharts renderiza um canvas em branco sem explicação.
- **`$pollingInterval = null`**.

### 5. Rosca — execuções de IA por status (`/infra`)

- **Path**: `app/Filament/Infra/Widgets/IaExecucoesPorStatus.php`
- **Pergunta que responde**: das execuções de IA, quantas deram erro? Hoje `ai_runs.status` não aparece em widget nenhum, embora `ai_runs.error` seja a coluna que se lê quando algo dá errado.
- **Fonte**: `AiRun::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total','status')`. Aqui o `GROUP BY` é seguro — é coluna de texto, sem função de data, portanto sem a incompatibilidade entre bancos que motivou o PHP no `contarPorDia()`.
- **Cor por status**: sucesso → success, erro/falha → danger, o resto → gray. Mapa explícito, com fallback para gray em status desconhecido (o pacote de IA pode ganhar estados novos).
- **`canView()`**: `Schema::hasTable(config('ai-tasks.table', 'ai_runs'))`, no mesmo padrão dos outros widgets de IA.
- **`$pollingInterval`**: `'60s'` — este dado muda durante o dia; um minuto é atualização útil sem virar consulta contínua.

### 6. Radial — taxa de sucesso das filas (`/infra`)

- **Path**: `app/Filament/Infra/Widgets/FilasTaxaDeSucesso.php`
- **Pergunta que responde**: dos jobs que terminaram, que fração terminou bem? `FilasStats` mostra os três números soltos; a **proporção** entre eles é o que diz se a fila está saudável.
- **Fonte** (mesma consulta e mesmo cuidado de `FilasStats:44-52`):

```php
$concluidos = $this->consulta()->where('failed', false)->whereNotNull('finished_at')->count();
$falhados   = $this->consulta()->where('failed', true)->count();
$processados = $concluidos + $falhados;
```

- **`failed` comparado com booleano**, nunca com `1`/`0` — PostgreSQL rejeita.
- **Jobs em andamento ficam de fora do denominador**: um job que ainda roda não é sucesso nem falha; contá-lo puxaria a taxa para baixo e ela cairia sozinha em horário de pico, sem nada ter piorado.
- **`canView()`**: `Schema::hasTable('queue_monitors')`.
- **Divisão por zero**: `$processados === 0` → `series: [0]` + subtítulo de estado vazio.
- **`$pollingInterval`**: `'30s'` — é o widget mais "ao vivo" do conjunto.

### 7. Documentação do kit — a regra dos três pacotes

> Skills: nenhuma

Este passo é o RQ-07, e é o que sobrevive à entrega.

- **`wikis/receitas.md` → seção "Widget de dashboard"** (linha ~242): substituir/expandir com a **tabela de decisão**:

| Vou desenhar | Pacote | Classe base |
|---|---|---|
| Gráfico (linha, área, barra, rosca, radial, radar, heatmap…) | `leandrocfe/filament-apex-charts` | `ApexChartWidget` |
| Stat card (número grande + ícone + variação) | `gsferro/filament-stat-plus-easy` | `StatsOverviewWidget` + `StatPlus` |
| Métrica, meta, breakdown, barra segmentada, timeline, lista, bullet | `laboiteacode/filament-dashboard-widgets` | `MetricWidget`, `GoalProgressWidget`, `BreakdownWidget`, … |

  Mais, no mesmo lugar:
  - **quando rosca e quando radial**: rosca para categorias mutuamente exclusivas que somam o total (2 a 5 fatias); radial para **um** número entre 0 e 100%. Nenhum dos dois para série temporal
  - **quando NÃO usar gráfico**: quando a barra segmentada ou o breakdown respondem mais rápido — com o exemplo real de `SaudeAplicacaoPorStatus`, que recusou rosca e explica por quê
  - o **checklist de widget novo**: `canView()` com `Schema::hasTable()`; `$pollingInterval` explícito; estado vazio com `series` zerada e não array vazio; os dois seeders do Shield; e o plugin registrado no painel
- **`wikis/pacotes.md` → "Já existe — não escreva de novo"**: a mesma tabela em uma linha, e o aviso de não escrever `<canvas>` nem Chart.js à mão.
- **`wikis/convencoes.md`**: a regra dos três pacotes como convenção, mais a armadilha do polling de 5 s.

### 8. Regenerar permissões

```bash
php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
```

### 9. README — dependência

- **Path**: `README.md`, seção `### UI e produtividade`

```markdown
| [leandrocfe/filament-apex-charts](https://packagist.org/packages/leandrocfe/filament-apex-charts) | os gráficos do kit (linha, área, rosca, progresso radial) |
```

- **Ajustar as linhas vizinhas**: a de `laboiteacode/filament-dashboard-widgets` diz hoje "widgets prontos de métrica, meta, breakdown e **tendência**" — a tendência saiu. E a de `flowframe/laravel-trend` diz "agregação por período para os gráficos dos widgets", o que não corresponde ao código (nenhum arquivo de `app/` o usa).

### 10. Candidato a rule de projeto

> Skills: `requirement-to-rule`

Propor ao usuário (**sem gravar sem aprovação**), para `app/Filament/**/Widgets/**`: a tabela de decisão dos três pacotes + `$pollingInterval` explícito + `canView()` com `Schema::hasTable()`. É a rule de maior valor das três wikis, porque decide **por qual porta** o próximo widget entra.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`**.
>
> Aplicações concretas nesta wiki:
> - **nenhuma classe base própria** de gráfico, nenhum "ApexChartWidget do kit". Cinco widgets independentes; se três deles repetirem o mesmo bloco de opções, aí sim se extrai — depois de existirem, não antes
> - a rosca de convites **reusa `Convite::situacao()`** em vez de reescrever a derivação em SQL: menos código e uma fonte de verdade só
> - o radial de filas **reusa a consulta do `FilasStats`**, inclusive o cuidado com o booleano do PostgreSQL
> - nada de filtros de período (`CanFilter`) nesta entrega: ninguém pediu, e cada filtro é estado de formulário a manter
>
> Atalhos deliberados marcados com `ponytail:` comment.
>
> **Caveman ativo em modo `full`** na comunicação agent ↔ usuário. Wiki, código, commits e PRs são boundary — prosa normal.

## Testes

> Ver `04-casos-de-teste.md` (dados e agregação, por componente) e `05-casos-de-teste-browser.md` (o gráfico desenhado de fato).

Atenção especial nos CTs: **valor limite** é o que quebra esta entrega — zero registros, zero processados, um único registro, e um status que a implementação não previu no mapa de cores.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `composer types:check`
- [ ] `vendor/bin/pest --group=kit --compact`
- [ ] `composer test:browser`
- [ ] Abrir `/admin` e `/infra` com banco **vazio** e com banco **semeado** — os dois estados
- [ ] Confirmar no navegador que nenhum widget faz requisição a cada 5 s (aba Network)

## Commits

- `:package: instala o leandrocfe/filament-apex-charts nos paineis admin e infra`
- `:recycle: migra o grafico de execucoes de IA para o ApexCharts`
- `:sparkles: rosca de convites por situacao no dashboard admin`
- `:sparkles: rosca de status de IA e radial de taxa de sucesso das filas`
- `:white_check_mark: testes dos graficos e dos valores limite`
- `:memo: a regra dos tres pacotes de widget do kit`

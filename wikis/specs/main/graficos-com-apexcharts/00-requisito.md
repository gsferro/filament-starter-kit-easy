# Requisito — Gráficos com ApexCharts

## Fonte

- **Origem**: pedido colado no chat pelo mantenedor do kit, invocando a skill `feature-wiki` (item 3 de 3 pacotes pedidos na mesma mensagem)
- **Data**: 2026-08-15
- **Autor / solicitante**: Guilherme Ferro (mantenedor do starter-kit-easy)
- **Fidelidade**: alta (texto escrito)
- **Wikis irmãs do mesmo pedido**: `lightbox-em-imagens-e-documentos` (item 1), `hub-de-navegacao-em-cards` (item 2)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> 3. analise profundamente o pacote: https://filamentphp.com/plugins/leandrocfe-apex-charts veja como ele pode ser
>   integrado ao projeto.
> - somente os graficos passam a ser criados usando este pacote. os demais, continuam sendo divididos por os outros 2 pacotes de widget: StatPlus e Dashboard widgets respectivamentes
> - explore mais possibilidades de usar os graficos de rosca e de progresso radial nos dashboards dentro das paginas já existentes e deixe documetado para que os agentes usem o pacote como esta sendo recomendado

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | O pacote `leandrocfe/filament-apex-charts` é instalado e integrado ao projeto | "analise profundamente o pacote: … veja como ele pode ser integrado ao projeto" | funcional |
| RQ-02 | **Gráfico** no kit passa a ser criado com este pacote | "somente os graficos passam a ser criados usando este pacote" | restrição |
| RQ-03 | Stat card continua com `gsferro/filament-stat-plus-easy` | "os demais, continuam sendo divididos por os outros 2 pacotes de widget: StatPlus" | restrição |
| RQ-04 | Os demais widgets (métrica, meta, breakdown, timeline, lista, barra segmentada) continuam com `laboiteacode/filament-dashboard-widgets` | "e Dashboard widgets respectivamentes" | restrição |
| RQ-05 | Gráficos de **rosca** são explorados nos dashboards das páginas já existentes | "explore mais possibilidades de usar os graficos de rosca … nos dashboards dentro das paginas já existentes" | funcional |
| RQ-06 | Gráficos de **progresso radial** são explorados nos dashboards das páginas já existentes | "e de progresso radial nos dashboards dentro das paginas já existentes" | funcional |
| RQ-07 | A divisão de responsabilidade entre os três pacotes fica documentada para os agentes | "deixe documetado para que os agentes usem o pacote como esta sendo recomendado" | não-funcional |

## Ambiguidades e Perguntas Abertas

### Resolvidas com o usuário em 2026-08-15

- **RQ-02 — e o gráfico que já existe?**
  `App\Filament\Infra\Widgets\IaExecucoesPorDia` é um gráfico de linha (`TrendWidget`, do `laboiteacode/filament-dashboard-widgets`). Ele contradiz a regra nova.
  - **Decisão do usuário**: **migrar** para `ApexChartWidget`. A regra fica sem exceção, e nenhum agente futuro precisa decidir qual pacote usar olhando para um contraexemplo vivo no repositório.

### Abertas — decididas por premissa, sujeitas a correção

- **RQ-04 vs RQ-02 — o `SegmentBarWidget` é "gráfico"?**
  `SaudeAplicacaoPorStatus` e `AgentesIaPorProvider` desenham uma barra segmentada; `BreakdownWidget` desenha barras horizontais proporcionais. São desenho de dado, mas vêm do pacote que o RQ-04 manda manter.
  - **Assumido**: "gráfico" é o que o `ApexChartWidget` chama de gráfico — série de dados renderizada em canvas/SVG por uma biblioteca de charting (linha, área, barra, rosca, radial, radar…). **Breakdown, segment bar, metric, goal, timeline e recent items continuam onde estão**, sem migração, porque o RQ-04 os nomeia explicitamente como sendo do outro pacote.
  - **Se negado**: seriam mais 5 widgets a migrar, e o `laboiteacode/filament-dashboard-widgets` perderia quase todo o uso — o que contradiz o próprio RQ-04.

- **RQ-05/RQ-06 — quantos e quais gráficos criar?**
  "Explore mais possibilidades" não dá número.
  - **Assumido**: quatro widgets novos, dois de cada tipo, distribuídos entre os dois dashboards que existem (`/admin` e `/infra`), **e cada um respondendo a uma pergunta que nenhum widget atual responde**. Duplicar dado já exibido em outro formato seria poluir o dashboard, não explorar o pacote.
  - **Se negado** (o usuário quiser mais ou menos): a lista do passo 4 a 7 do PRD é o que sobe ou desce; nada mais muda.

- **RQ-05/RQ-06 — o painel `/app` também tem dashboard.**
  - **Assumido**: **fora desta entrega**. O dashboard do `/app` não tem nenhum widget do kit hoje (só `AccountWidget` e `FilamentInfoWidget`), e escolher a métrica de negócio a exibir é decisão de produto de cada instalação — o kit deliberadamente não cria dado de negócio (mesma lógica da nota do `TenantForm` sobre campos de organização). A receita documenta como fazer.
  - **Se negado**: seria preciso escolher a métrica de `Projeto`, que é o model de exemplo do kit.

### Devolvidas pela derivação de testes (`feature-test-design`, 2026-08-15)

Três decisões mudam o **número exibido na tela** e não estão no requisito. Cada cenário dependente
está marcado `@premissa` no `04-casos-de-teste.md`.

- **"Taxa de sucesso das filas" inclui os jobs em andamento no denominador?**
  - **Assumido**: **não** — job que ainda roda não é sucesso nem falha; contá-lo faria a taxa cair
    sozinha em horário de pico, sem nada ter piorado.
  - **Se negado**: CT-04 muda de 75% para 30% e o widget passa a medir outra coisa.

- **Base zero: o gráfico mostra 0% ou some da tela?**
  - **Assumido**: **mostra 0%**, com legenda de estado vazio. É o estado de toda instalação nova.
  - **Se negado**: o `canView()` passa a depender de haver dado, e CT-05/CT-07 mudam.

- **Status de IA fora do mapa de cores conhecido, o que fazer?**
  - **Assumido**: exibir em cinza, como fatia própria. O pacote de IA pode ganhar estados novos a
    qualquer upgrade, e descartá-los faria a soma das fatias deixar de bater com o total.
  - **Se negado** (agrupar em "outros"): CT-09 muda.

- **Portabilidade de banco não é testável nesta suíte.** Dois mutantes reais — `where('failed', 1)`
  em vez de booleano, e `GROUP BY DATE()` em vez de agregação em PHP — passam em SQLite e quebram
  em PostgreSQL. A suíte roda em SQLite. **Lacuna declarada**, com a convenção escrita no PRD e no
  código existente como única mitigação.

## Fora de Escopo (declarado)

- Migrar `BreakdownWidget`, `SegmentBarWidget`, `MetricWidget`, `GoalProgressWidget`, `TimelineWidget`, `BulletWidget` e `RecentItemsWidget` — o RQ-04 manda mantê-los
- Migrar os `StatsOverviewWidget`/`StatPlus` — o RQ-03 manda mantê-los
- Widgets no dashboard do painel `/app` (ver premissa acima)
- Remover `flowframe/laravel-trend`: ele continua sendo a fonte de agregação por período, agora alimentando o ApexCharts em vez do `TrendWidget`
- Dashboard configurável / arrastar widgets — já é do `mddev31/filament-dynamic-dashboard`, sem relação com esta entrega

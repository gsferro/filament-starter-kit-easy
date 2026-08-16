# Decisões Arquiteturais — Gráficos com ApexCharts

## ADR-01: Três pacotes de widget, com fronteira escrita

**Status**: Aceita
**Data**: 2026-08-15

### Contexto

O kit tem três pacotes de widget instalados e **nenhuma regra escrita** de qual usar para quê:

- `gsferro/filament-stat-plus-easy` — stat cards
- `laboiteacode/filament-dashboard-widgets` — 13 widgets, entre eles um `TrendWidget` que desenha gráfico
- e agora `leandrocfe/filament-apex-charts`

Sem fronteira declarada, a escolha vira preferência de quem escreve o widget, e o dashboard fica com duas linguagens visuais para a mesma pergunta.

### Decisão

Fronteira por **tipo de desenho**, não por painel nem por domínio:

| O que vai desenhar | Pacote |
|---|---|
| Gráfico — série renderizada por biblioteca de charting (linha, área, barra, rosca, radial, radar, heatmap) | `leandrocfe/filament-apex-charts` |
| Stat card — número grande, ícone, variação | `gsferro/filament-stat-plus-easy` |
| Todo o resto — métrica, meta, breakdown, barra segmentada, timeline, lista, bullet | `laboiteacode/filament-dashboard-widgets` |

### Alternativas Consideradas

1. **Migrar tudo para ApexCharts e remover o `dashboard-widgets`** — descartada: o RQ-03 e o RQ-04 mandam manter os outros dois, e breakdown/timeline/lista não são gráficos — reimplementá-los em ApexCharts seria desenhar em SVG o que hoje é HTML acessível.
2. **Deixar a escolha caso a caso** — descartada: é o estado atual, e é o que produziu um `TrendWidget` isolado no meio de treze widgets que não são gráfico.
3. **Fronteira por painel** (ex.: infra usa Apex, admin usa dashboard-widgets) — descartada: a mesma pergunta receberia desenhos diferentes conforme onde é feita.

### Consequências

- **Positivas**: a decisão "qual pacote?" some do caminho de quem cria widget; o dashboard fica coerente.
- **Negativas**: três dependências de UI convivendo, cada uma com JS próprio.
- **Riscos**: a fronteira é prosa até virar rule. Mitigação: passo 11 do PRD propõe a Project Rule em `app/Filament/**/Widgets/**`.

### Referências

- `01-plano-acao.md` → passo 8
- `00-requisito.md` → RQ-02, RQ-03, RQ-04

---

## ADR-02: `IaExecucoesPorDia` é migrado mantendo o nome da classe

**Status**: Aceita
**Data**: 2026-08-15
**Decidida por**: usuário, em 2026-08-15

### Contexto

`IaExecucoesPorDia` é o único gráfico do kit e vem do pacote errado segundo a regra nova. Duas escolhas: migrar ou abrir exceção.

Se migrar, resta o detalhe do nome: a classe é uma **entidade do Shield**, e a permission (`View:IaExecucoesPorDia`) é derivada do FQCN. Renomear a classe cria permission nova e deixa a antiga órfã no banco.

### Decisão

Migrar para `ApexChartWidget`, **mantendo o nome da classe e o caminho do arquivo**.

### Alternativas Consideradas

1. **Deixar como está, e a regra vale só para gráfico novo** — recusada pelo usuário: um contraexemplo vivo no repositório é mais forte que a regra escrita, porque agente e pessoa copiam o vizinho antes de ler a convenção.
2. **Migrar e renomear** (ex.: `IaExecucoesPorDiaChart`) — descartada: permission órfã no banco de toda instalação já existente, e o `PapeisSeeder` teria de lidar com o resíduo. Zero ganho de clareza.
3. **Criar o Apex ao lado e apagar o antigo depois** — descartada: dois gráficos idênticos no mesmo dashboard durante a transição.

### Consequências

- **Positivas**: nenhuma exceção à regra do ADR-01; a permission existente continua válida; nada muda para quem já usa o kit em termos de autorização.
- **Negativas**: o `git blame` do arquivo perde a linha original — mitigado porque os dois comentários que importam (agregação em PHP, eixo pelo calendário) são copiados junto, e este ADR registra a origem.
- **Riscos**: perder informação na reescrita — especificamente a comparação com o período anterior, que o `TrendWidget` dava de graça. Mitigação: ela vira `$subheading` e tem CT próprio.

### Referências

- `app/Filament/Infra/Widgets/IaExecucoesPorDia.php`
- `.ai/rules/filament.md` — "Resource ou RelationManager novo exige gerar as permissões"

---

## ADR-03: Rosca não substitui a barra segmentada dos health checks

**Status**: Aceita
**Data**: 2026-08-15

### Contexto

O requisito pede para explorar rosca nos dashboards. O candidato mais óbvio no `/infra` seria `SaudeAplicacaoPorStatus` — composição de status, quatro categorias, soma o total.

Mas esse widget **já recusou a rosca**, por escrito, no próprio código:

> *"SegmentBar e não Composition (rosca): cada verificação tem exatamente um status, então as partes fecham o todo — e a leitura que importa é 'quanto da barra ainda é verde'. Uma barra horizontal responde isso mais rápido que uma rosca, que obriga a comparar ângulos."*

### Decisão

Não reabrir. `SaudeAplicacaoPorStatus` fica como está. As roscas desta wiki vão para dados **sem** widget hoje: situação dos convites (`/admin`) e status das execuções de IA (`/infra`).

### Alternativas Consideradas

1. **Converter o widget de saúde para rosca**, já que o requisito pede rosca — descartada: atenderia a letra do RQ-05 piorando a tela, e desfaria uma decisão tomada com justificativa. "Explorar possibilidades" não é "trocar o que já foi decidido".
2. **Ter os dois**, barra e rosca, do mesmo dado — descartada: dois desenhos da mesma informação no mesmo dashboard é ruído.

### Consequências

- **Positivas**: as quatro adições respondem perguntas novas; nenhum dashboard ganha redundância.
- **Negativas**: nenhuma.
- **Riscos**: alguém no futuro "padronizar" e converter. Mitigação: a receita do passo 8 usa este caso como o exemplo de *quando NÃO usar gráfico*.

### Referências

- `app/Filament/Infra/Widgets/SaudeAplicacaoPorStatus.php:15-21`

---

## ADR-04: Polling é sempre explícito, e o default do pacote é recusado

**Status**: Aceita
**Data**: 2026-08-15

### Contexto

`ApexChartWidget` usa `Filament\Widgets\Concerns\CanPoll` e a documentação do pacote declara **5 segundos** como intervalo padrão de atualização.

Cinco segundos, por widget, por aba aberta. Com três gráficos no `/infra`, uma aba esquecida aberta gera 36 consultas agregadas por minuto — indefinidamente, sem ninguém olhando. É custo de banco proporcional a abas esquecidas, que é a pior variável de dimensionamento possível.

### Decisão

**Todo `ApexChartWidget` do kit declara `$pollingInterval` explicitamente.** Nesta entrega (4 widgets, depois do corte de `OrganizacoesAtivas` na auditoria Ponytail):

| Widget | Intervalo | Por quê |
|---|---|---|
| `IaExecucoesPorDia` | `null` | dado diário; atualizar a cada 5 s não muda um pixel |
| `ConvitesPorSituacao` | `null` | convite é evento raro |
| `IaExecucoesPorStatus` | `'60s'` | muda ao longo do dia; um minuto já informa |
| `FilasTaxaDeSucesso` | `'30s'` | é o mais "ao vivo" do conjunto |

### Alternativas Consideradas

1. **Aceitar o default** — descartada pelo raciocínio acima.
2. **Desligar polling em todos** — descartada: filas e IA são justamente onde ver o dado mudar tem valor operacional.
3. **Configurar via `.env`** — descartada: variável de ambiente para um número que nunca muda por instalação é configuração especulativa (rung 1 da escada do Ponytail).

### Consequências

- **Positivas**: custo de banco previsível e proporcional ao valor de cada gráfico.
- **Negativas**: cinco declarações a mais no código.
- **Riscos**: o próximo widget esquecer a declaração e herdar os 5 s. Mitigação: item do checklist na receita e candidato a rule.

### Referências

- `vendor/leandrocfe/filament-apex-charts/src/Widgets/ApexChartWidget.php` (`use CanPoll`)
- `01-plano-acao.md` → seção "Jobs / Queues"

---

## ADR-05: Cor do gráfico sai da paleta do Filament, não do default do ApexCharts

**Status**: Aceita
**Data**: 2026-08-15

### Contexto

O ApexCharts traz paleta própria. O kit pinta o painel `/app` com a **cor da organização** (`tenants.cor_primaria`, feature "identidade visual da organização"), e o `resources/css/filament/kit.css` existe justamente porque um plugin com paleta literal fazia o alternador de painel aparecer em âmbar dentro de um painel verde.

Um gráfico com a paleta padrão do ApexCharts reintroduz exatamente esse problema — desta vez em elemento grande e central.

### Decisão

Toda cor de série é declarada, e vem de uma destas duas fontes, nesta ordem:

1. **Cor semântica**, quando a série tem significado — sucesso é `success`, falha é `danger`, neutro é `gray`. Vale para as duas roscas e para os dois radiais.
2. **Variável CSS do Filament** (`var(--primary-500)` e irmãs) quando a série não tem semântica própria — caso do gráfico de área de execuções por dia, que hoje usa `->color('primary')`.

Nunca a paleta default do pacote.

### Alternativas Consideradas

1. **Deixar o default** — descartada: o gráfico ficaria fora do tema, e no `/app` fora da cor da organização. É regressão visual de uma feature existente.
2. **Ler `tenants.cor_primaria` no widget e injetar o hexadecimal** — descartada nesta entrega: nenhum dos gráficos vive no painel `/app`, então não há o que resolver ainda. Quando o primeiro gráfico chegar lá, a via correta já está registrada (variável CSS, que o Filament já reescreve por painel).

### Consequências

- **Positivas**: o gráfico acompanha tema claro/escuro e a identidade visual, sem código de cor por painel.
- **Negativas**: cada widget declara cor em vez de herdar.
- **Riscos**: se o ApexCharts não resolver `var(--…)` em alguma opção (algumas propriedades dele esperam hexadecimal literal), o valor cai para o default sem erro. Mitigação: conferir no navegador — está na Verificação Final — e, onde a variável não funcionar, usar o token semântico do próprio pacote.

### Referências

- `resources/css/filament/kit.css` (cabeçalho — o precedente do plugin com paleta literal)
- `wikis/specs/main/admin-da-organizacao/` e a feature de identidade visual da organização

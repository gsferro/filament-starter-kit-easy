# Decisões Arquiteturais — Stat de logins do dia

## ADR-01: Sparkline em Stat é `Stat::chart()`, e a rule "gráfico é ApexCharts" ganha uma exceção escrita

**Status**: Aceita
**Data**: 2026-09-04

### Contexto

`.ai/rules/filament.md` → *"Qual pacote de widget"* é dura e curta:

> Gráfico é `filament-apex-charts`; stat card é `filament-stat-plus-easy`; o resto é
> `filament-dashboard-widgets`.

Ela veio da ADR-01 da wiki `graficos-com-apexcharts`, cuja fronteira é **por tipo de desenho**:
*"Gráfico — série renderizada por biblioteca de charting (linha, área, barra, rosca, radial,
radar, heatmap) → `leandrocfe/filament-apex-charts`"*.

O sparkline pedido em RQ-03 **é** uma série renderizada por biblioteca de charting: a view
`vendor/filament/widgets/resources/views/stats-overview-widget/stat.blade.php:58-70` carrega o
componente Alpine `stats-overview/stat/chart`, cujo JS importa de `chart.js`. Pela letra da rule,
ele deveria ser ApexCharts.

E pela letra da rule ele **não pode** ser ApexCharts, porque RQ-03 exige o gráfico **dentro do
stat**, e um `ApexChartWidget` é um widget — uma caixa ao lado, não uma linha dentro da caixa.

### Decisão

Usar `Stat::chart()`, nativo do Filament, e registrar que a rule fala de **widget de gráfico**,
não de decoração de Stat. A fronteira passa a ter três casos, e o terceiro é este:

| O que vai desenhar | Pacote |
|---|---|
| Widget de gráfico — caixa própria com uma série | `leandrocfe/filament-apex-charts` |
| Stat card | `gsferro/filament-stat-plus-easy` |
| **Sparkline dentro de um Stat** | **`Stat::chart()` nativo — nenhum pacote** |
| Todo o resto | `laboiteacode/filament-dashboard-widgets` |

A emenda da rule é candidata do step 9 desta wiki, não decisão tomada aqui.

### Alternativas Consideradas

1. **`ApexChartWidget` separado, ao lado do stat** — descartada por contrariar RQ-03 diretamente.
   Seria também pior na tela: uma sétima caixa quebrando a grade de 3 que RQ-06 pede para fechar.
2. **Renderizar o sparkline à mão em SVG dentro do `description()`** — descartada: reimplementa em
   Blade o que o framework já entrega, e `description()` espera texto, não markup de gráfico.
3. **Deixar a rule intacta e não registrar nada** — descartada, e é a mais perigosa das três: o
   próximo agente que ler `.ai/rules/filament.md` encontra um `chart()` no meio de stats e conclui
   que a rule foi violada por descuido. Contraexemplo no repositório pesa mais que convenção
   escrita, exatamente como a ADR-02 da wiki ancestral já observou ao migrar o `TrendWidget`.

### Consequências

- **Positivas**: zero dependência nova, zero asset, zero view publicada. O Chart.js usado é o que
  o `filament/widgets` já embarca, servido por `FilamentAsset`, fora do Vite do projeto.
- **Negativas**: passam a existir duas bibliotecas de charting renderizando no `/admin` — ApexCharts
  nos widgets de gráfico e Chart.js nos sparklines. É JS a mais na página, e o custo é real.
- **Riscos**: a exceção vira brecha — alguém desenha um gráfico de verdade dentro de um Stat para
  fugir do ApexCharts. Mitigação: a exceção é nominal (*sparkline dentro de um Stat*) e a rule
  emendada precisa dizer isso com essas palavras.

### Referências

- `vendor/filament/widgets/src/StatsOverviewWidget/Stat.php:106,142`
- `vendor/filament/widgets/resources/views/stats-overview-widget/stat.blade.php:58-70`
- `vendor/filament/widgets/resources/js/components/stats-overview/stat/chart.js:9` — `import … from 'chart.js'`
- `.ai/rules/filament.md` → "Qual pacote de widget"
- `wikis/specs/main/graficos-com-apexcharts/02-decisoes-arquiteturais.md` → ADR-01, ADR-02

---

## ADR-02: A contagem por dia é duplicada em PHP, não extraída para um helper compartilhado

**Status**: Aceita
**Data**: 2026-09-04

### Contexto

`IaExecucoesPorDia::contarPorDia()` já faz exatamente o que o passo 1 precisa: janela, `get()` de
uma coluna, `countBy()` por `Y-m-d`, e o eixo preenchido a partir do calendário para os dias vazios
virarem zero. O instinto — e a escada do Ponytail, no degrau "reutilizar" — manda extrair.

### Decisão

**Copiar o padrão, não extrair a classe.** O método novo vive privado em
`UsuariosVisaoGeralStats`, com um comentário apontando a origem.

### Alternativas Consideradas

1. **Trait `ContaPorDia` em `app/Filament/Concerns/`** — descartada. Seriam **dois** chamadores, em
   painéis diferentes, sobre models diferentes, com colunas de data diferentes (`created_at` ×
   `login_at`), janelas diferentes (14 × 7) e filtros diferentes (nenhum × `login_successful`).
   A trait teria de parametrizar model, coluna, janela e filtro — quatro parâmetros para esconder
   seis linhas de `Collection`. É abstração com um implementador e meio.
2. **Extrair para `app/Support/`** — mesmo problema, com um arquivo a mais e um import a mais.
3. **Um `scope` no model** — não dá: `AuthenticationLog` é model de vendor.

### Consequências

- **Positivas**: cada widget continua legível sozinho, sem saltar para um helper para entender o
  que ele conta. Mudar a janela de um não mexe no outro.
- **Negativas**: duplicação real de ~6 linhas. Se um terceiro widget precisar do mesmo padrão, a
  decisão deve ser revista — três chamadores é onde a extração começa a pagar.
- **Riscos**: a correção de um bug de borda (fuso, virada de dia) precisaria ser feita nos dois
  lugares. Mitigação: os dois têm caso de teste sobre o dia sem registro, que é onde esse bug
  apareceria.

### Referências

- `app/Filament/Infra/Widgets/IaExecucoesPorDia.php:120-157`

---

## ADR-03: A guarda da tabela opcional é do STAT, nunca do widget

**Status**: Aceita
**Data**: 2026-09-04

### Contexto

`authentication_log` é tabela de plugin opcional. Todo widget do kit que a consome se protege com
`Schema::hasTable()`, e o kit tem um lugar canônico para isso: o método
`fonteDeDadosDisponivel()` do trait `ExigePermissaoDoWidget`, cujo `canView()` é
`permissão && fonteDeDadosDisponivel()`.

`UsuariosVisaoGeralStats` já usa o trait, e não declara `fonteDeDadosDisponivel()` — portanto herda
o `true` do trait. Seguir o padrão do kit aqui significaria declará-lo com o `hasTable()` da tabela
de log.

Isso está **errado**, e o erro é do tipo que passa em code review: cinco dos seis stats desta
classe — usuários, 2FA, novos em 30 dias, papéis, permissões — **não têm nada a ver com log de
acesso**. Guardar o widget pela tabela nova faria uma instalação sem o plugin de log perder também
a contagem de usuários e a de permissões, sem erro, sem aviso, com o diff parecendo correto e
idiomático.

### Decisão

A guarda é local ao stat, num método privado com nome próprio (`logDeAcessoDisponivel()`), e
`fonteDeDadosDisponivel()` **não é declarado** nesta classe.

Sem a tabela, o widget renderiza os cinco stats de sempre e o sexto não existe.

### Alternativas Consideradas

1. **`fonteDeDadosDisponivel()` com o `hasTable()`** — descartada pelo motivo acima: esconde cinco
   stats saudáveis para proteger um.
2. **Exibir o sexto stat com valor `0`** — descartada. `0` afirma "ninguém entrou hoje"; a verdade
   é "este dado não é coletado nesta instalação". São coisas diferentes, e a primeira é uma mentira
   sobre segurança.
3. **`try/catch` em volta da consulta** — descartada: transforma ausência de plugin em exceção
   engolida, e o sintoma de uma quebra futura de verdade (coluna renomeada pelo pacote) viraria
   silêncio.

### Consequências

- **Positivas**: o raio de dano do plugin ausente fica do tamanho do que depende dele. O nome
  `logDeAcessoDisponivel()` diz o que guarda, ao contrário de `fonteDeDadosDisponivel()`, que numa
  classe de seis fontes não diz **qual**.
- **Negativas**: a classe passa a ter dois mecanismos de disponibilidade — o do trait (herdado,
  `true`) e o privado. Quem ler rápido pode achar que faltou declarar o do trait. Mitigado por
  comentário no método.
- **Riscos**: a harmonia de RQ-06 é condicional — sem o plugin, voltam as 5 caixas e a linha
  ragged. Aceito e registrado como premissa no `00-requisito.md`.

### Referências

- `app/Filament/Concerns/ExigePermissaoDoWidget.php` — o docblock que explica por que a checagem
  de tabela **não** é autorização, e por que ela mora em `fonteDeDadosDisponivel()`
- `app/Filament/Infra/Widgets/AutenticacaoStats.php:31-38` — o caso em que declarar o método **é**
  correto, porque lá **todos** os stats dependem da mesma tabela

---

## ADR-04: O valor do stat sai da série, não de uma segunda consulta

**Status**: Aceita
**Data**: 2026-09-04

### Contexto

O stat tem duas grandezas na mesma caixa: o número grande (logins de hoje, RQ-02) e a curva de 7
dias (RQ-04). O caminho óbvio é uma consulta para cada — `whereDate('login_at', today())` para o
número, a janela de 7 dias para a série.

### Decisão

Uma consulta só. O número é `array_values($serie)[6]` — a última posição da série.

### Alternativas Consideradas

1. **Duas consultas** — descartada por duas razões, e a segunda é a que importa. A primeira é
   custo. A segunda: **duas consultas podem discordar**. Basta um filtro divergir — uma com
   `login_successful`, outra sem; uma com `whereDate`, outra com `whereBetween` e fuso diferente —
   para a caixa exibir um número que não é a ponta da própria curva. Ninguém percebe, porque o
   sparkline não tem eixo Y rotulado.
2. **Número por consulta e série por cache** — descartada: introduz invalidação para economizar uma
   consulta de 7 dias.

### Consequências

- **Positivas**: número e gráfico são, por construção, a mesma medição. É impossível divergirem.
- **Negativas**: o número depende do índice correto do array. Um off-by-one exibiria ontem como
  hoje — e é um erro silencioso, porque o valor seria plausível.
- **Riscos**: exatamente o off-by-one acima. Mitigação: caso de teste que fixa o tempo e afirma que
  o valor do stat é o de **hoje**, com hoje e ontem tendo contagens **diferentes** — com valores
  iguais, o cenário passa com o índice errado.

### Referências

- `01-plano-acao.md` → passo 2

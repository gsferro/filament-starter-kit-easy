# Casos de Teste — Gráficos com ApexCharts

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A1 — agregação e derivação dos números (rosca de convites, radiais) | 3 — regra com muitas condições: a situação do convite é derivada de três colunas, e as taxas dividem por bases que podem ser zero | 3 — número errado num dashboard é decisão errada tomada com confiança; ninguém desconfia de um gráfico | 9 | **completo** |
| A2 — migração do gráfico existente | 2 | 2 — perda silenciosa de informação que hoje existe | 4 | **padrão** |
| A3 — polling e proteção de tabela ausente (`canView`) | 2 | 2 — widget que estoura derruba o dashboard inteiro | 4 | **padrão** |
| A4 — o gráfico desenhado no navegador | 2 | 1 | 2 | **mínimo** |

- Técnicas aplicadas: **EP exaustiva de enum**, **BVA 3-valores**, **tabela de decisão**, **rastreio de efeito**, **matriz widget × tabela ausente**
- Cenários: 10 (9 no `04`, 1 no `05`) · Regras: 5 · Mutantes previstos: 16 · Sem matador: 2 (declarados)
- **Corte da auditoria Ponytail**: R3/CT-07 saíram junto com o widget `OrganizacoesAtivas`
- **Revisão adversarial**: obrigatória (área A1 em perfil completo) — ver `## Revisão Adversarial` no fim

> **Divergência declarada — Project Rule vence a skill.** `--parallel` fora dos CT-B; `--tia`
> inviável sem PCOV (`.ai/rules/testes-browser.md`).

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | 3 widgets novos + 1 reescrito; 2 registros de plugin. **3 permissions novas** derivadas pelo Shield | CT-10 |
| **F** | duas funções: **agregar** (contar, derivar situação, calcular fração) e **desenhar**. A primeira é onde mora o risco | CT-01…CT-08 |
| **D** | três fontes: `convites` (3 colunas de data, situação derivada), `ai_runs.status`, `queue_monitors.failed`/`finished_at`. Cardinalidades relevantes: **base zero**, **um único registro**, **status fora do mapa conhecido** | CT-02, CT-05, CT-09 |
| **I** | uma interface só: os dashboards `/admin` e `/infra`. Sem rota, comando ou job | CT-10 |
| **P** | **banco** — `failed` é booleano e o PostgreSQL rejeita `failed = 1`; funções de data mudam de nome entre SQLite/MySQL/PostgreSQL (é o motivo da agregação em PHP no widget migrado). **Navegador** — o ApexCharts desenha em runtime | CT-06, CT-08, CT-B01 |
| **O** | dois perfis abrem os dashboards: `master_global` e o papel do painel. Volume: instalação nova tem **todas** as bases zeradas — é o caso normal, não o excepcional | CT-02, CT-05 |
| **T** | o gráfico migrado depende da **janela de 14 dias** e do calendário: dia sem execução tem de aparecer como zero. Sem DST nem timezone em jogo — todas as comparações são no fuso da aplicação | CT-08, CT-09 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — a rosca de convites reflete a situação derivada pelo model | A1 (completo) | RQ-05 | **EP exaustiva do enum** + caso de precedência | CT-01, CT-02, CT-03 |
| **R2** — a taxa de sucesso das filas conta só o que terminou | A1 (completo) | RQ-06 | tabela de decisão + BVA (base zero e dízima) | CT-04, CT-05, CT-06, CT-07 |
| **R4** — a rosca de status de IA reflete os status existentes, inclusive os desconhecidos | A1 (completo) | RQ-05 | EP + partição "fora do mapa" | CT-09 |
| **R5** — o gráfico migrado preserva a série diária e a comparação de período | A2 (padrão) | RQ-02 | rastreio de efeito + BVA de calendário | CT-08 |
| **R6** — widget cuja tabela de origem não existe não aparece, e nenhum widget herda o polling padrão | A3 (padrão) | RQ-01 + ADR-04 | matriz widget × condição | CT-10, CT-11 |

**Regras que o requisito gera e que não viram cenário:**

| `RQ` | Por que não há cenário |
|---|---|
| RQ-03, RQ-04 (os outros dois pacotes continuam donos do resto) | são **restrições de não-mudança**. O cenário natural seria "nenhum `StatsOverviewWidget` virou `ApexChartWidget`", que é asserção sobre o diff, não sobre comportamento. Verificação por revisão, item da Verificação Final. **Alternativa considerada e recusada**: um teste de arquitetura (`arch()`) proibindo `ApexChartWidget` fora de gráfico — não é expressável, porque "ser gráfico" não é propriedade estrutural |
| RQ-07 (documentação para agentes) | prosa em markdown + Project Rule; não observável em teste |

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nomes das classes dos widgets | escolha de implementação | detalhe do cenário |
| `type => 'donut'` / `'radialBar'` | **escolha de implementação do desenho**, ainda que o requisito diga "rosca" e "radial". O que o requisito determina é o **tipo de leitura**; o nome da opção do ApexCharts é do pacote | detalhe — nenhum `Então` afirma sobre a string do tipo |
| `$sort`, `$columnSpan`, `$heading` | apresentação | detalhe |
| os intervalos de polling (`null`, `'60s'`, `'30s'`) | ⚠️ **caso especial**: os valores vêm do ADR-04, não do requisito. Mas **"não herdar o default de 5 s" é comportamento**, com custo real de banco | CT-11 afirma que **nenhum** widget fica com o default, sem fixar o valor de cada um |
| `Convite::situacao()` como fonte | **aceito como oráculo**: o requisito pede a rosca das situações, e a definição de "situação" já existe no model. Reescrevê-la em SQL seria criar uma segunda definição | R1 |
| jobs em andamento fora do denominador | ⚠️ **o requisito não diz**. É decisão do PRD, mas **muda o número na tela** | **pergunta ao usuário**; cenário CT-04 marcado `@premissa` |

**Perguntas para o `00-requisito.md`** (replicar em `## Ambiguidades`):

- **"Taxa de sucesso das filas" inclui os jobs em andamento no denominador?** O PRD decidiu que
  não (job que ainda roda não é sucesso nem falha). A escolha muda o número exibido, e em horário
  de pico a diferença é grande. Confirmar.
- **Base zero: o gráfico mostra 0% ou some da tela?** O PRD decidiu **mostrar 0% com legenda de
  estado vazio**. Alternativa seria `canView()` falso com base vazia. Confirmar.
- **Status de IA fora do mapa conhecido** (o pacote de IA pode ganhar estados novos): o PRD decidiu
  exibir em cinza. Alternativa seria agrupar em "outros". Confirmar.

## Setup Global

### Personas

- **`master_global`** — `usuarioDoKit('master_global')`. Os cenários de agregação **não** são sobre autorização; usar a persona que vence pelo `Gate::before` mantém o cenário focado.
- **`infra`** — `usuarioDoKit('infra')` em CT-10, que é sobre a matriz de permissões.

### Fixtures

| Fixture | Como |
|---|---|
| convite aceito | `Convite` com `aceito_em` preenchido |
| convite recusado | `recusado_em` preenchido |
| convite expirado | `expira_em` no passado, sem `aceito_em` nem `recusado_em` |
| convite pendente | `expira_em` no futuro, as outras duas nulas |
| **convite aceito E expirado** | `aceito_em` preenchido **e** `expira_em` no passado — a fixture discriminante de R1 |
| job concluído | linha em `queue_monitors` com `failed = false` e `finished_at` preenchido |
| job falhado | `failed = true` |
| job em andamento | `failed = false`, `finished_at` nulo |
| execução de IA | `AiRun` com `status` variando |

> **Verificado**: `database/factories/ConviteFactory.php` existe (ao lado de `TenantFactory` e
> `UserFactory`). Os cenários usam `Convite::factory()`, e **confirmam os states disponíveis
> antes** — se já houver um state de aceite/recusa, é ele que entra; se não, os campos vão no
> `->create([...])`. O que o cenário afirma é o **estado do convite**, nunca o caminho para
> produzi-lo.

### Fakes

Nenhum. Nada de e-mail, job ou HTTP.

### Estratégia de DB

`RefreshDatabase` global. Seeders só em CT-10 (que é sobre permissão).

### Onde os arquivos vivem

| Suíte | Arquivo | Cenários |
|---|---|---|
| `tests/Kit` | `GraficosDoDashboardTest.php` | CT-01…CT-11 |
| `tests/Browser` | `GraficosDoDashboardTest.php` | CT-B01 |

### Como observar o dado de um `ApexChartWidget`

O widget é um componente Livewire com a propriedade pública `$options`, preenchida no `mount()`.
Os cenários afirmam sobre ela:

```php
Livewire::test(ConvitesPorSituacao::class)->get('options');
```

> **Confirmar na implementação**: o `mount()` do `ApexChartWidget` preenche `$options` a partir de
> `getOptions()` (`vendor/leandrocfe/filament-apex-charts/src/Widgets/ApexChartWidget.php:37-48`).
> Se o widget usar carregamento diferido (`CanDeferLoading`), `$options` nasce nula e o cenário
> precisa disparar o carregamento antes — **não** relaxar a assertion nesse caso.

---

## Regra R1 — a rosca de convites reflete a situação derivada pelo model

> `RQ-05` · perfil **completo** · técnicas: **EP exaustiva do enum** + caso de precedência

O enum de situação tem quatro valores, e **partição de estado exibido não se amostra**: cobrir
"Aceito" e "Pendente" e deixar "Expirado" de fora permite exatamente o defeito que importa — a
rosca dizer que 80% dos convites foram aceitos quando metade expirou.

```gherkin

# language: pt

Funcionalidade: Gráficos do dashboard

  Regra: a rosca de convites conta cada convite na situação que o sistema já define para ele

    Esquema do Cenário: [CT-01] cada situação é contada na fatia dela
      Dado um convite <descricao>
      Quando o administrador abre o gráfico de convites por situação
      Então a fatia "<situacao>" contém 1 convite

      Exemplos:
        | descricao                                        | situacao  |
        | com aceite registrado                            | Aceito    |
        | com recusa registrada                            | Recusado  |
        | sem resposta, com prazo vencido                  | Expirado  |
        | sem resposta, com prazo em aberto                | Pendente  |

    Cenário: [CT-02] sem nenhum convite a rosca mostra todas as fatias zeradas
      Dado nenhum convite cadastrado
      Quando o administrador abre o gráfico de convites por situação
      Então as quatro fatias existem
      E todas contêm 0 convites

    Cenário: [CT-03] convite aceito continua aceito depois do prazo vencer
      Dado um convite com aceite registrado e prazo já vencido
      Quando o administrador abre o gráfico de convites por situação
      Então a fatia "Aceito" contém 1 convite
      E a fatia "Expirado" contém 0 convites
```

**Camada**: componente Livewire.

**Por que CT-03 é o cenário mais importante desta regra**: ele é o **exemplo discriminante**. Uma
implementação que reescrevesse a derivação em SQL — `whereNull('aceito_em')->where('expira_em','<',now())`
para expirado e `whereNotNull('aceito_em')` para aceito — parece correta, passa em CT-01 inteiro, e
**erra exatamente aqui**, contando o mesmo convite duas vezes ou classificando como expirado um
convite que virou conta. A precedência "aceito vence expirado" está escrita no
`Convite::situacao()` e em nenhum outro lugar.

**Por que CT-02 afirma que as fatias **existem** zeradas**: devolver array vazio faz o ApexCharts
renderizar um canvas em branco, sem legenda e sem explicação. É o estado de uma instalação nova —
o mais comum de todos.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | a derivação é reescrita em SQL, perdendo a precedência "aceito vence expirado" | **CT-03** |
| M2 | base vazia devolve `series: []` e a rosca some | CT-02 |
| M3 | uma situação sem nenhum convite é omitida da legenda, e a ordem das fatias muda conforme o dado | CT-02 |
| M4 | `expira_em` nulo tratado como "pendente" em vez de "expirado" (o `?? true` do `situacao()` diz o contrário) | CT-01 (linha "prazo vencido"), se a fixture incluir o caso nulo. **Acrescentar linha ao `Exemplos`**: `sem prazo definido` → `Expirado` |

---

## Regra R2 — a taxa de sucesso das filas conta só o que terminou

> `RQ-06` · perfil **completo** · técnicas: **tabela de decisão** + **BVA** (base zero) · `@premissa`

| `failed` | `finished_at` | Entra no numerador? | Entra no denominador? |
|---|---|---|---|
| false | preenchido | **sim** | sim |
| true | qualquer | não | sim |
| false | nulo (em andamento) | não | **não** |

```gherkin
  Regra: a taxa de sucesso das filas é a fração dos jobs que terminaram sem falha

    @premissa
    Cenário: [CT-04] jobs em andamento não pesam na taxa
      Dado 3 jobs concluídos sem falha
      E 1 job falhado
      E 6 jobs ainda em execução
      Quando o administrador de infraestrutura abre o gráfico de taxa de sucesso das filas
      Então a taxa exibida é 75%

    Cenário: [CT-05] sem nenhum job terminado a taxa é zero, sem erro
      Dado 2 jobs ainda em execução
      E nenhum job concluído nem falhado
      Quando o administrador de infraestrutura abre o gráfico de taxa de sucesso das filas
      Então a taxa exibida é 0%
      E o gráfico informa que ainda não há jobs terminados

    Cenário: [CT-06] job falhado é contado pelo indicador de falha, não pela ausência de término
      Dado 1 job falhado que registrou horário de término
      E 1 job concluído sem falha
      Quando o administrador de infraestrutura abre o gráfico de taxa de sucesso das filas
      Então a taxa exibida é 50%

    Cenário: [CT-07] a taxa arredonda para o inteiro mais próximo
      Dado 1 job concluído sem falha
      E 2 jobs falhados
      Quando o administrador de infraestrutura abre o gráfico de taxa de sucesso das filas
      Então a taxa exibida é 33%
```

**Camada**: componente Livewire.

**Valores discriminantes** — escolhidos de propósito:

- **CT-04 com 3/1/6**: se os jobs em andamento entrassem no denominador, a taxa seria **30%**, não
  75%. Um cenário com 3 concluídos, 1 falhado e **zero** em andamento daria 75% nas duas
  implementações — seria decorativo.
- **CT-06 com um job falhado que TEM `finished_at`**: distingue "conta falha pela coluna `failed`"
  de "conta falha pela ausência de término". As duas implementações dão o mesmo resultado quando
  todo job falhado tem `finished_at` nulo — que é o caso mais comum e, por isso, o mais enganoso.
- **CT-05 com 2 em andamento e nada terminado**: a base do denominador é **zero**. É a divisão por
  zero, e é o estado normal de uma instalação nova.
- **CT-07 com 1/3**: fixa o **arredondamento**. `33` e não `33,33` nem `34` — sem essa linha,
  truncar e arredondar passam igual. Ela veio realocada do CT-07 antigo, que saiu junto com o
  widget `OrganizacoesAtivas` no corte da auditoria Ponytail; a borda foi movida, não perdida.

> `@premissa` em CT-04: o requisito não diz o que fazer com job em andamento. A premissa está
> registrada em `## Fronteira com o Plano` e devolvida ao `00-requisito.md`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M5 | jobs em andamento entram no denominador — a taxa cai sozinha em horário de pico | **CT-04** |
| M6 | divisão por zero sem guarda: o widget estoura e **derruba o dashboard inteiro** | CT-05 |
| M7 | base zero devolve 100% ("nada falhou") em vez de 0% — otimismo falso, o pior resultado possível para um indicador de saúde | CT-05 |
| M8 | falha detectada por `finished_at` nulo em vez da coluna `failed` | **CT-06** |
| M8b | truncamento onde o cenário pede arredondamento, ou o contrário | **CT-07** |
| M9 | `where('failed', 1)` em vez de `false`/`true` — funciona em SQLite e **quebra em PostgreSQL** | ⚠️ **sem matador**: a suíte roda em SQLite. Lacuna declarada. Tentado: derivar por asserção sobre o SQL gerado (`toSql()`), recusado por ser teste da implementação, não do comportamento. Mitigação real: a convenção está escrita no `FilasStats` e no PRD |

---

## Regra R4 — a rosca de status de IA reflete os status existentes, inclusive os desconhecidos

> `RQ-05` · perfil **completo** · técnica: **EP + partição "fora do mapa"**

```gherkin
  Regra: cada status registrado vira fatia, mesmo o que o kit não conhece

    Cenário: [CT-09] status não previsto aparece na rosca em vez de sumir
      Dado 2 execuções de IA com status de sucesso
      E 1 execução de IA com status "cancelado_pelo_usuario"
      Quando o administrador de infraestrutura abre o gráfico de execuções por status
      Então a rosca tem 2 fatias
      E a fatia "cancelado_pelo_usuario" contém 1 execução
```

**Camada**: componente Livewire.

**Por que este cenário existe**: o mapa de cores por status é uma lista fechada escrita à mão. A
implementação natural — `match` sem `default`, ou `$mapa[$status]` direto — **estoura** ou
**descarta** o status desconhecido. O pacote de IA pode ganhar estados novos a qualquer upgrade, e
o modo de falha "a rosca some do dashboard depois de um `composer update`" é caro e difícil de
diagnosticar.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M14 | `$cores[$status]` sem fallback: `Undefined array key` derruba o dashboard | CT-09 |
| M15 | status fora do mapa é filtrado fora da rosca: a soma das fatias deixa de ser o total de execuções | CT-09 |

---

## Regra R5 — o gráfico migrado preserva a série diária e a comparação de período

> `RQ-02` · perfil **padrão** · técnicas: **rastreio de efeito** + **BVA de calendário**

O risco da migração é **perda silenciosa**: o gráfico novo desenha, fica bonito, e perdeu o eixo
completo ou a comparação com o período anterior — informação que hoje existe.

```gherkin
  Regra: o gráfico de execuções de IA mantém um ponto por dia e a comparação com o período anterior

    Cenário: [CT-08] dia sem execução aparece como zero, não como buraco
      Dado 1 execução de IA registrada hoje
      E nenhuma execução nos 13 dias anteriores
      Quando o administrador de infraestrutura abre o gráfico de execuções por dia
      Então o gráfico tem 14 pontos
      E o ponto de hoje vale 1
      E os 13 pontos anteriores valem 0
```

**Camada**: componente Livewire.

**Por que 14 pontos e não "os pontos que existem"**: é a decisão preservada da implementação atual
— *"dia sem execução tem que aparecer como zero, senão a linha 'pula' o buraco e uma parada de dois
dias vira um trecho reto"*. Uma migração que passasse o resultado da consulta direto ao ApexCharts
produziria **1 ponto**, desenharia sem erro, e mentiria sobre a operação.

> A comparação com o período anterior (o `$subheading`) **não** ganha cenário próprio: ela é
> calculada por `variacaoContraPeriodoAnterior()`, que não é tocado pela migração. O que a
> migração pode perder é a **exibição** — coberto pela conferência do roteiro *Desenhado ×
> Implementado* no `05`. **Lacuna declarada, com mitigação apontada.**

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M16 | a série passa a ser o resultado cru da consulta: dias sem execução somem do eixo | CT-08 |
| M17 | a janela muda de 14 dias na reescrita | CT-08 (contagem de pontos) |
| M18 | a agregação vira `GROUP BY DATE(created_at)` — funciona em SQLite e quebra em outro banco | ⚠️ mesmo caso de M9: **lacuna declarada**, a suíte roda em SQLite |

---

## Regra R6 — widget sem tabela de origem não aparece, e nenhum widget herda o polling padrão

> `RQ-01` + ADR-04 · perfil **padrão** · técnica: **matriz widget × condição**

```gherkin
  Regra: o widget se esconde quando a fonte dele não existe

    Cenário: [CT-10] o dashboard abre mesmo sem as tabelas opcionais
      Dado uma instalação sem a tabela de monitoramento de filas
      Quando o administrador de infraestrutura abre o painel de infraestrutura
      Então a página responde com sucesso
      E o gráfico de taxa de sucesso das filas não é exibido

  Regra: nenhum gráfico atualiza sozinho a cada poucos segundos

    Cenário: [CT-11] nenhum gráfico do kit herda o intervalo padrão do pacote
      Dado todos os gráficos registrados nos painéis de administração e de infraestrutura
      Quando o intervalo de atualização de cada um é consultado
      Então nenhum deles usa o intervalo padrão do pacote
```

**Camada**: `Feature` (CT-10, que precisa da rota e da renderização do dashboard inteiro) e `Feature` (CT-11).

**Sobre CT-11 e a fronteira com o plano**: o cenário afirma que o intervalo **não é o default de
5 s** — não fixa qual é o valor de cada widget. O valor é decisão do PRD; **não herdar o default**
é comportamento, com custo de banco proporcional a abas esquecidas.

**A lista de widgets é derivada, não escrita à mão** (corte da auditoria Ponytail): o cenário
percorre os widgets registrados nos painéis `admin` e `infra`, filtrando os que estendem
`ApexChartWidget`. Assim ele cobre também o gráfico que alguém criar amanhã — que é exatamente
quem vai esquecer a declaração. Lista fixa de nomes só cobre os de hoje.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M19 | `canView()` esquecido num widget: instalação sem o pacote de filas tem o **dashboard inteiro** derrubado, não só o card | CT-10 |
| M20 | `$pollingInterval` não declarado: o widget herda 5 s | CT-11 |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **não se aplica**: os widgets agregam dado global da instalação; não recebem `{id}` nem parâmetro do usuário |
| Autorização exercida na ação | CT-10 (a permission do widget é gerada pelo Shield e exercida pelo `canView()`) |
| Idempotência | **não se aplica**: leitura pura |
| Concorrência | **não se aplica**: nenhum contador é escrito |
| Fronteira no ponto de entrada (gravação) | **não se aplica**: nenhum formulário. **Gate de tela de escrita**: a `## Superfície de UI` do PRD não tem rota `create`/`edit` |
| **Domínio condicionado** | CT-04 (o `finished_at` só importa quando `failed` é falso — é a tabela de decisão de R2) |
| Estado × operação de escrita | **não se aplica** |
| **Ausente ≠ null ≠ vazio** | CT-01 (linha a acrescentar: `expira_em` nulo) e CT-05 (base zero × base ausente) |
| Paginação / ordenação | **não se aplica**: gráfico não pagina. A **ordem das fatias** é fixada por CT-02 |
| **Timezone / DST** | **lacuna declarada**: o gráfico diário compara `created_at` com o calendário do fuso da aplicação. Tentado derivar cenário com `config(['app.timezone' => 'UTC'])` divergente e `travelTo()` na virada do dia; **recusado por escopo**, porque o widget atual já tem esse comportamento e a migração não o altera — seria cobertura nova de código pré-existente, não da entrega |
| Unicode / limite de varchar | CT-09 (status arbitrário vindo do pacote de IA) |
| Unicidade + soft delete | **não se aplica** |
| CRUD combinado | **não se aplica** |
| Mass assignment | **não se aplica** |
| Upload | **não se aplica** |
| **Precisão numérica** | CT-04 (75%, que distingue os denominadores) e **CT-07** (1 concluído, 2 falhados → 33%, nem 33,33 nem 34), que fixa o arredondamento. A borda de dízima saiu com o widget `OrganizacoesAtivas` no corte da auditoria e foi **realocada**, não perdida |
| **Console limpo / JS** | CT-B01 |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | cada situação de convite na fatia dela | R1 | EP exaustiva | componente | `tests/Kit/GraficosDoDashboardTest.php` | M4 |
| CT-02 | rosca zerada em base vazia | R1 | BVA (zero) | componente | idem | M2, M3 |
| CT-03 | aceito vence expirado | R1 | precedência | componente | idem | **M1** |
| CT-04 | jobs em andamento fora da taxa | R2 | tabela de decisão | componente | idem | **M5** |
| CT-05 | base zero na taxa das filas | R2 | BVA (zero) | componente | idem | M6, M7 |
| CT-06 | falha detectada pela coluna `failed` | R2 | tabela de decisão | componente | idem | **M8** |
| CT-07 | a taxa arredonda para o inteiro mais próximo | R2 | BVA (dízima) | componente | idem | M8b |
| CT-08 | 14 pontos, dia vazio como zero | R5 | BVA de calendário | componente | `tests/Kit/GraficosDoDashboardTest.php` | M16, M17 |
| CT-09 | status desconhecido não derruba nem some | R4 | EP (fora do mapa) | componente | idem | M14, M15 |
| CT-10 | dashboard abre sem as tabelas opcionais | R6 | matriz widget × condição | Feature | idem | M19 |
| CT-11 | polling declarado em todos | R6 | EP por widget | Feature | idem | M20 |
| CT-B01 | o gráfico desenha de fato | — | JS executado | Browser | `tests/Browser/GraficosDoDashboardTest.php` | — |

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| **CT-07 — fração de organizações ativas** | **cortado na auditoria Ponytail** junto com o widget `OrganizacoesAtivas`. A borda de arredondamento que ele carregava foi realocada para uma linha do CT-04 |
| um cenário por tipo de gráfico afirmando `type => 'donut'` | testa a string do PRD, não comportamento |
| conferir cores das fatias por valor hexadecimal | cor vem de token semântico (ADR-05); afirmar hexadecimal fixaria a paleta e quebraria com a identidade visual da organização |
| conferir `$sort` e `$columnSpan` | apresentação |
| assertar que nenhum `StatsOverviewWidget` virou Apex | asserção sobre o diff, não sobre comportamento (ver Mapa de Regras) |
| um CT-B por gráfico | mata o mesmo mutante; browser em série é o recurso mais caro |
| timezone no gráfico diário | comportamento pré-existente, não alterado pela migração (ver checklist) |

## Revisão Adversarial

> **Obrigatória**: a área A1 está em perfil **completo**.

Delegar a um sub-agente que **não derivou** estes cenários, com o contrato da skill: entrada é o
`00-requisito.md` + este arquivo; **sem** o PRD, **sem** o código. Tarefa: escrever 5
implementações erradas que passariam por todos os cenários, apontar todo `Então` fraco e todo
cenário sem oráculo.

**Pontos onde a revisão deve olhar primeiro** (autoavaliação, não substitui a revisão):

1. **CT-01 usa um convite por linha.** Uma implementação que conte errado quando há **vários**
   convites na mesma situação passaria. Falta um cenário com 3 aceitos e 2 pendentes afirmando
   `[3, 2, 0, 0]`.
2. **Nenhum cenário mistura as quatro situações no mesmo gráfico.** A soma das fatias nunca é
   comparada ao total de convites.
3. **CT-11 afirma "não é o default"** — uma implementação que declare `'5s'` explicitamente
   passaria, com o mesmo custo de banco que o ADR-04 quer evitar.
4. **M9 e M18 (portabilidade de banco) não têm matador**, e são o mesmo defeito de fundo.

| Rodada | Achados | O que virou cada um |
|---|---|---|
| _(preencher ao executar)_ | | |

## Fechamento com Mutation Testing

```bash
XDEBUG_MODE=coverage vendor/bin/pest tests/Kit/GraficosDoDashboardTest.php --mutate --path=app/Filament/Admin/Widgets
XDEBUG_MODE=coverage vendor/bin/pest tests/Kit/GraficosDoDashboardTest.php --mutate --path=app/Filament/Infra/Widgets
```

Esta é a feature das três com **mais lógica PHP real** — contagens, frações, mapas e um laço de
calendário. É onde o mutation score informa de verdade.

Traduções esperadas de mutante sobrevivente:

| Se sobreviver | Lacuna | O que escrever |
|---|---|---|
| `+` ↔ `-` no denominador da taxa | falta valor discriminante | cenário com concluídos ≠ falhados ≠ em andamento (é CT-04; se sobreviver, os números escolhidos não discriminam) |
| `round` removido | falta borda de arredondamento | CT-07 |
| literal `14` → `0` ou `1` | falta afirmação sobre a contagem de pontos | é CT-08 |
| chamada a `situacao()` removida | oráculo fraco em R1 | é CT-03 |

> Antes de rodar: confirmar `pestphp/pest-plugin-mutate` no `composer.json` — hoje **não está
> declarado**. Se só existir em `vendor/` como dependência transitiva do Pest 5, some num
> `composer update`, e `composer require pestphp/pest-plugin-mutate --dev` vira passo do PRD.

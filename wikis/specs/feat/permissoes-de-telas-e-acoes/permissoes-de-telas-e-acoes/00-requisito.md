# Requisito — Permissões de telas e ações

## Fonte

- **Origem**: `.claude/requisitos/w1b-permissoes-de-telas-e-acoes.txt` — item recortado de um pedido
  maior sobre a tela `/admin/shield/roles`, colado no chat pelo usuário
- **Data**: 2026-08-23
- **Autor / solicitante**: mantenedor do kit (gsferro)
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> vamos melhorar a tela: "/admin/shield/roles"
> [...]
> - ver quais outras modais ainda não tem permissões disponiveis para selecionar, cria-las e deixar disponivel para seleção, além de aplica-las nas telas para estarem cobertas por uma permissão especifica. TODAS as telas, links e actions do sistema precisa ter sua permissão especifica desde o inicio no starter-kit como padrão, para o maximo controle e total flexibilidade a quem for usar o kit nos seus projetos

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Levantar quais superfícies do sistema ainda **não têm** permissão disponível para seleção | "ver quais outras modais ainda não tem permissões disponiveis para selecionar" | funcional |
| RQ-02 | **Criar** as permissões que faltam | "cria-las" | autorização |
| RQ-03 | Deixar as permissões novas **selecionáveis** na tela de papéis | "e deixar disponivel para seleção" | funcional |
| RQ-04 | **Aplicar** a permissão na tela/ação, de forma que a superfície fique de fato coberta | "além de aplica-las nas telas para estarem cobertas por uma permissão especifica" | autorização |
| RQ-05 | Toda **tela** do sistema tem permissão específica | "TODAS as telas [...] precisa ter sua permissão especifica" | autorização |
| RQ-06 | Todo **link** do sistema tem permissão específica | "TODAS as [...] links [...] precisa ter sua permissão especifica" | autorização |
| RQ-07 | Toda **action** do sistema tem permissão específica | "TODAS as [...] actions do sistema precisa ter sua permissão especifica" | autorização |
| RQ-08 | A cobertura é o **default do kit**, não algo que o usuário do kit configura depois | "desde o inicio no starter-kit como padrão" | restrição |
| RQ-09 | O objetivo é controle e flexibilidade **para quem usa o kit nos projetos dele** — ou seja, cada permissão precisa poder ser concedida/revogada por papel, sem editar código | "para o maximo controle e total flexibilidade a quem for usar o kit nos seus projetos" | não-funcional |

## Ambiguidades e Perguntas Abertas

- **RQ-05 — o que conta como "tela"?** O kit tem três famílias de superfície que o Shield trata
  de modo diferente: Resource (14 permissões por model), Page (uma) e Widget (uma). Além delas há
  as **Pages e Widgets de vendor** registrados por plugin no `/infra` (dez Pages e um Widget),
  que são classes de terceiros.
  - **Assumido**: "tela" = Page, Widget e Resource **de código do kit**. As de vendor entram em
    `## Fora desta entrega` porque não há onde aplicar a trait sem subclassear classe de plugin,
    e quatro delas já têm gate próprio (`ver-logs`, `command-center:access`, `viewPulse`).
  - **Se negado**: entra um passo novo de subclasse/decorator por Page de vendor, com risco de
    quebrar rota, slug e registro de plugin — estimativa de esforço comparável à desta entrega
    inteira.

- **RQ-06 — "link" é o item de menu ou a Action de URL?** O kit tem as duas coisas:
  `NavigationItem::make('dashboard-ia')` (já com `->visible()` por gate) e
  `Action::make('dashboardAiTasks')` (sem nada).
  - **Assumido**: as duas. O item de menu já está coberto; a Action de URL passa a checar o mesmo
    gate do destino.
  - **Se negado**: nada muda — o tratamento é o mesmo nas duas leituras.

- **RQ-07 — "permissão específica" para `aceitar`/`recusar` convite recebido significa que o
  usuário comum pode ser impedido de aceitar o próprio convite?**
  - **Assumido**: sim, é knob de configuração — a permissão nasce **concedida** ao `panel_user` e
    ao `admin_app`, e quem usa o kit pode revogá-la. A barreira de identidade continua sendo
    `Convite::exigirDono()` no model (`.ai/rules/filament.md`), que a permissão **não substitui**.
  - **Se negado**: as duas permissões novas saem, e as duas Actions passam a herdar
    `View:ConvitesRecebidos` da Page.

## Fora desta entrega

Recortado para a feature `feat/perfis-e-permissoes`, que roda em paralelo em outro worktree e
mexe em `app/Filament/Admin/Resources/Roles/**` — arquivo que **esta** entrega não toca, de
propósito, para não conflitar no merge:

- label da tela de papéis
- coluna de contagem de permissões
- uuid na URL
- tabs verticais
- select de guard

Fora por decisão registrada nas Ambiguidades acima:

- **Pages de vendor do `/infra`** — `HealthCheckResults`, `BackupRunsPage`, `LogsExplorer`,
  `DependencyGraphPage`, `Commands`, `History`, `RunView`, `RecycleBin`, `MyProfilePage` — e o
  **Widget de vendor** `ComposerReleaseOverviewWidget`. A permissão **existe** no banco e é
  selecionável na tela de papéis (o Shield a gera por descoberta), mas nada a consulta: são
  classes de plugin, sem ponto de extensão. Ver ADR-05.
- **Resources** — já estavam cobertos: 14 permissões por model, consultadas pelas 14 policies em
  `app/Policies/`. Nada a fazer.
- **Actions nativas** (`CreateAction`, `EditAction`, `DeleteAction`, `ViewAction`,
  `DeleteBulkAction`) em Resource e Page de Resource — já consultam a policy do model pelo
  `getDefaultActionAuthorizationResponse()` do Filament. **Exceção**: as de RelationManager, que
  o vendor documenta como não-autorizadas — essas entram na entrega (ver ADR-04).
- **`TelaLogin`, `TelaBloqueio`, `RegistroPorConvite`** (`app/Filament/Pages/Auth/`) — telas
  públicas ou de sessão própria, por definição sem permissão. Não são Pages de painel e o Shield
  não gera permissão para elas.

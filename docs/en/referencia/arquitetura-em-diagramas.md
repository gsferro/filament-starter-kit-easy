---
title: "Architecture diagrams"
description: "Twenty Mermaid diagrams show how the kit works today: architecture, role-based access, authentication, AI, infrastructure, the data model and the kit's own…"
sidebar:
  order: 5
---
Twenty Mermaid diagrams show how the kit works today: architecture, role-based access, authentication, AI, infrastructure, the data model and the kit's own lifecycle. Each one is guarded by an automated test (`tests/Kit/DiagramasDaArquiteturaTest.php`, `tests/Kit/GuardasDosDiagramasTest.php` and, under multi-tenancy, `tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php`) that fails the moment the code stops matching what the diagram describes — none of them describes planned functionality.

## DG-01 — Layered architecture

From the browser to the three Filament panels (`app/Providers/Filament/AppPanelProvider.php:id:79`, `app/Providers/Filament/AdminPanelProvider.php:id:69`, `app/Providers/Filament/InfraPanelProvider.php:id:90`), through role-based access, down to the database, queue, cache and the optional services. It is the same block as the README — a single source.

```mermaid
flowchart TD
%% DG-01
accTitle: Layered architecture
accDescr: From the browser to the three Filament panels, through role-based access, down to the database, queue, cache and the optional services.
  navegador[Browser] --> rota_publica["Public / route"]
  navegador --> painel_app
  navegador --> painel_admin
  navegador --> painel_infra
  subgraph camada_paineis ["Panels (Filament)"]
    painel_app["/app (default)"]
    painel_admin["/admin"]
    painel_infra["/infra"]
  end
  painel_app --> acesso_por_papel[Role-based access]
  painel_admin --> acesso_por_papel
  painel_infra --> acesso_por_papel
  acesso_por_papel --> configuracoes[Settings]
  acesso_por_papel --> agentes_ia[AI agents]
  subgraph camada_infra ["Infrastructure"]
    banco[("Database")]
    fila[Queue]
    cache[("Cache")]
    worker["Worker (optional)"]
    agendador["Scheduler (optional)"]
    reverb["Reverb (optional)"]
    pulse["Pulse (pulse:check)"]
  end
  configuracoes --> banco
  configuracoes --> cache
  agentes_ia -->|"writes ai_runs"| banco
  agentes_ia --> fila
  fila --> worker
  agendador --> fila
  reverb --> camada_paineis
  painel_infra --> pulse
  pulse --> banco
  agentes_ia -.->|"optional"| ia_externa["Local or SaaS AI"]
  camada_paineis -.-> oauth["OAuth"]
  configuracoes -.-> email_externo["Mail"]
  painel_infra -.-> packagist["Packagist"]
```

## DG-02 — Use cases by role

The kit's eight actors (visitor, `panel_user`, `admin_app`, `admin`, `infra`, `master_global`, the scheduler and the CLI) and the use cases each role reaches. `admin_app` only exists when `KIT_TENANCY` is on (`database/seeders/PapeisSeeder.php:papel:80`); the other roles are always seeded (`database/seeders/PapeisSeeder.php:papel:58`, `:papel:61`, `:papel:101`). Every edge ties the role to the permission entity it actually holds in the database (Shield) — or, for `master_global`, to the code that lets them through without Shield: `Gate::before` (`cu_tudo`) and, for "Impersonate user", `User::canImpersonate()` (`app/Models/User.php:canImpersonate:882`), which only accepts `master_global` — never to a guess.

```mermaid
flowchart LR
%% DG-02
accTitle: Use cases by role
accDescr: The kit's eight actors and the use cases each role reaches, inside the Starter Kit Easy boundary.
  ator_visitante["Visitor"]
  ator_panel_user["Panel user (panel_user)"]
  ator_admin_app["Organization admin (admin_app; requires KIT_TENANCY)"]
  ator_admin["Administrator (admin)"]
  ator_infra["Infrastructure (infra)"]
  ator_master_global["Super admin (master_global)"]
  ator_agendador["Scheduler"]
  ator_cli["CLI operator"]
  subgraph fronteira ["Starter Kit Easy"]
    cu_login["Log in"]
    cu_login_social["Log in with social login (optional)"]
    cu_recuperar_senha["Reset password"]
    cu_cadastro["Sign up (optional)"]
    cu_aceitar_convite["Accept/decline received invite (declining requires KIT_TENANCY)"]
    cu_trocar_painel["Switch panel"]
    cu_2fa["Confirm 2FA"]
    cu_bloqueio["Lock/unlock session"]
    cu_operar_negocio["Operate the business in /app"]
    cu_convidar["Invite user (manage invites)"]
    cu_gerir_usuarios["Manage users"]
    cu_gerir_usuarios_org["Manage organization users (optional)"]
    cu_aprovar_cadastro["Approve sign-up (KIT_REGISTRO_APROVACAO_MANUAL)"]
    cu_gerir_papeis["Manage roles and permissions"]
    cu_configuracoes["Configure the application"]
    cu_ver_logs["View the logs"]
    cu_ver_saude["View installation health"]
    cu_lixeira["Use the Recycle Bin"]
    cu_personificar["Impersonate user"]
    cu_lembrar_convites["Remind pending invites"]
    cu_tenancy["Enable multi-tenancy"]
    cu_tudo["Everything, via Gate::before"]
  end
  ator_visitante --> cu_login
  ator_visitante --> cu_login_social
  ator_visitante --> cu_recuperar_senha
  ator_visitante --> cu_cadastro
  ator_panel_user --> cu_login
  ator_panel_user --> cu_2fa
  ator_panel_user --> cu_bloqueio
  ator_panel_user --> cu_trocar_painel
  ator_panel_user --> cu_aceitar_convite
  ator_panel_user --> cu_operar_negocio
  ator_admin_app --> cu_convidar
  ator_admin_app --> cu_gerir_usuarios_org
  ator_admin --> cu_gerir_usuarios
  ator_admin --> cu_convidar
  ator_admin --> cu_gerir_papeis
  ator_admin --> cu_configuracoes
  ator_admin --> cu_aprovar_cadastro
  ator_infra --> cu_ver_logs
  ator_infra --> cu_ver_saude
  ator_infra --> cu_lixeira
  ator_master_global --> cu_tudo
  ator_master_global --> cu_personificar
  ator_agendador --> cu_lembrar_convites
  ator_cli --> cu_tenancy
```

## DG-03 — Panel access rule

The exact order of `User::canAccessPanel()` (`app/Models/User.php:canAccessPanel:157`): account unavailability (`:166`), pending approval (`:193`), `master_global` (`:206`), the role's context — any tenant with tenancy on, only the global context without it (`:217`) — and finally the panel role (`:219`). Once allowed, the `User::canAccessTenant()` extension (`:789`) decides the tenant, and the ORDER matters: an inactive organization denies first, for everyone — including `master_global` (`:813`) —; only then does `master_global` always get in (`:826`), and no link denies (`:830`).

```mermaid
flowchart TD
%% DG-03
accTitle: Panel access rule
accDescr: The decision order of canAccessPanel, from account unavailability to the role check, and the canAccessTenant extension when the panel uses multi-tenancy under KIT_TENANCY.
  inicio(["Start"]) --> checa_indisponivel{"Account unavailable?"}
  checa_indisponivel -->|"Yes"| nega_403["Deny (403)"]
  checa_indisponivel -->|"No"| checa_pendente{"Approval pending?"}
  checa_pendente -->|"Yes"| nega_403
  checa_pendente -->|"No"| checa_master{"Is master_global?"}
  checa_master -->|"Yes"| permite["Allow"]
  checa_master -->|"No"| checa_contexto{"Panel with tenancy?"}
  checa_contexto -->|"Yes"| papel_em_alguma["Role in any tenant"]
  checa_contexto -->|"No"| papel_global["Role in the global context"]
  papel_em_alguma --> checa_papel{"Has panel role?"}
  papel_global --> checa_papel
  checa_papel -->|"Yes"| permite
  checa_papel -->|"No"| nega_403
  permite --> checa_tenant{"Tenant accessed: is it inactive?"}
  checa_tenant -->|"Yes"| nega_404["Deny (404)"]
  checa_tenant -->|"No"| checa_vinculo{"master_global or linked to the tenant?"}
  checa_vinculo -->|"Yes"| permite_tenant["Enter the tenant"]
  checa_vinculo -->|"No"| nega_404
```

## DG-13 — Core entity-relationship diagram

The central entities and how they reference each other: the account (`users`) to a tenant via `tenant_user` (`app/Models/User.php:tenants:778`, `app/Models/Tenant.php:users:118`), to a role via `model_has_roles` (Shield), the social link (`app/Models/User.php:vinculosSociais:788`), the invite — with a nullable `convidado_por_id` (`database/migrations/2026_08_13_000002_create_convites_table.php:convidado_por_id:42`) — via `papel()`, `tenant()` and `convidadoPor()` (`app/Models/Convite.php:papel:116`, `:tenant:122`, `:convidadoPor:128`) and the AI agent catalog — with no relation at all to `projetos`, the demo table (`app/Traits/BelongsToTenant.php:tenant:82`). `ai_runs` and `agent_conversations` are left out of this ER: they store identifiers as loose text (`subject_type`/`subject_id`, `participant_type`/`participant_id`), with no real foreign key, and drawing a relation for them would invent an FK the schema doesn't have. `passkeys` is left out for the opposite reason: the table exists (vendor), but no feature in the kit uses it — passkeys are disabled (`enablePasskeys()` is never called).

```mermaid
erDiagram
%% DG-13
accTitle: Core entity-relationship diagram
accDescr: The kit's central entities and how they reference each other - the user's tie to a tenant and a role, invites, social login links and the AI agent catalog.
  users ||--o{ tenant_user : belongs
  tenants ||--o{ tenant_user : gathers
  users ||--o{ model_has_roles : receives
  roles ||--o{ model_has_roles : grants
  users ||--o{ vinculos_sociais : authenticates
  users |o--o{ convites : invited
  roles ||--o{ convites : "invite role"
  tenants |o--o{ convites : "tenant, with KIT_TENANCY (optional)"
  tenants ||--o{ projetos : "has, demo scenario (KIT_DEMO)"
  agentes_ia {
    string slug
    boolean ativo
    string provider
    text instrucoes
  }
  convites {
    string email
    string token
    timestamp expira_em
    timestamp aceito_em
    timestamp recusado_em
    string token_lembrete
    timestamp enviado_em
    int lembretes_enviados
  }
```

## About where these diagrams come from

[GitDiagram](https://gitdiagram.com/gsferro/filament-starter-kit-easy) generated a first, AI read of this repository — an AI-generated view, not verified. The official diagram is this page, which corrects and expands it: GitDiagram presented what is optional (organizations, tenancy) as always-present and left out the kit's central rule (role-based access).

## Index of the 20 diagrams

| DG | Diagram | Page |
|---|---|---|
| DG-01 | Layered architecture | This page |
| DG-02 | Use cases by role | This page |
| DG-03 | Panel access rule | This page |
| DG-04 | Password login and second factor | [Authentication](../../autenticacao/) |
| DG-05 | Unified login - destination by 0, 1 or N panels | [Unified login](../../autenticacao/login-unificado/) |
| DG-06 | Social login return | [Social login](../../autenticacao/login-social/) |
| DG-07 | Invitation, from send to acceptance | [Invites](../../autenticacao/convites/) |
| DG-08 | User account states | [User states](../../autenticacao/estados-de-usuario/) |
| DG-09 | Invitation states | [Invites](../../autenticacao/convites/) |
| DG-10 | Authenticated session | [Authentication](../../autenticacao/) |
| DG-11 | Assistant sequence | [Feature roadmap](../../operacao/roteiro-de-features/) |
| DG-12 | Map of /infra: screen, source and who writes it | [Infrastructure trails](../../recursos/trilhas-de-infraestrutura/) |
| DG-13 | Core entity-relationship diagram | This page |
| DG-14 | Where configuration comes from | [Kit settings](../../recursos/configuracoes-do-kit/) |
| DG-15 | Installation sequence | [Advanced installation](../../comecar/instalacao-avancada/) |
| DG-16 | The kit:update flow | [Updating the project](../../comecar/atualizando-o-projeto/) |
| DG-17 | The two delivery routes | [Updating the project](../../comecar/atualizando-o-projeto/) |
| DG-18 | Containers by Docker profile | [Advanced installation](../../comecar/instalacao-avancada/) |
| DG-19 | Background - composer dev x Docker Compose x scheduler | [Developing the kit](../../operacao/desenvolvendo-o-kit/) |
| DG-20 | Request to /app/{tenant} | [Multi-tenancy](../../recursos/multi-tenancy/) |

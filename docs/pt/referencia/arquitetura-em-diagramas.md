---
title: "Arquitetura em diagramas"
description: "Vinte diagramas Mermaid mostram como o kit funciona hoje: arquitetura, acesso por papel, autenticação, IA, infraestrutura, modelo de dados e o ciclo de vida…"
sidebar:
  order: 5
---
Vinte diagramas Mermaid mostram como o kit funciona hoje: arquitetura, acesso por papel, autenticação, IA, infraestrutura, modelo de dados e o ciclo de vida do próprio kit. Cada um é guardado por um teste automatizado (`tests/Kit/DiagramasDaArquiteturaTest.php`) que falha quando o código deixar de bater com o que o diagrama descreve — nenhum diagrama aqui descreve funcionalidade planejada.

## DG-01 — Arquitetura em camadas

Do navegador aos três painéis Filament (`app/Providers/Filament/AppPanelProvider.php:id:76`, `app/Providers/Filament/AdminPanelProvider.php:id:67`, `app/Providers/Filament/InfraPanelProvider.php:id:88`), pelo acesso por papel, até banco, fila, cache e os serviços opcionais. É o mesmo bloco do README — a fonte é única.

```mermaid
flowchart TD
%% DG-01
accTitle: Arquitetura em camadas
accDescr: Do navegador aos três painéis Filament, pelo acesso por papel, até banco, fila, cache e os serviços opcionais.
  navegador[Navegador] --> rota_publica["Rota pública /"]
  navegador --> painel_app
  navegador --> painel_admin
  navegador --> painel_infra
  subgraph camada_paineis ["Painéis (Filament)"]
    painel_app["/app (padrão)"]
    painel_admin["/admin"]
    painel_infra["/infra"]
  end
  painel_app --> acesso_por_papel[Acesso por papel]
  painel_admin --> acesso_por_papel
  painel_infra --> acesso_por_papel
  acesso_por_papel --> configuracoes[Configurações]
  acesso_por_papel -->|"só via /app"| agentes_ia[Agentes de IA]
  subgraph camada_infra ["Infraestrutura"]
    banco[("Banco de dados")]
    fila[Fila]
    cache[("Cache")]
    worker["Worker (opcional)"]
    agendador["Agendador (opcional)"]
    reverb["Reverb (opcional)"]
    pulse["Pulse (pulse:check)"]
  end
  configuracoes --> banco
  configuracoes --> cache
  agentes_ia -->|"grava ai_runs"| banco
  agentes_ia --> fila
  fila --> worker
  agendador --> fila
  reverb --> painel_admin
  painel_infra --> pulse
  pulse --> banco
  agentes_ia -.->|"opcional"| ia_externa["IA local ou SaaS"]
  painel_admin -.-> oauth["OAuth"]
  configuracoes -.-> email_externo["E-mail"]
  painel_admin -.-> packagist["Packagist"]
```

## DG-02 — Casos de uso por papel

Os oito atores do kit (visitante, `panel_user`, `admin_app`, `admin`, `infra`, `master_global`, o agendador e a CLI) e os casos de uso que cada papel alcança. `admin_app` só existe com `KIT_TENANCY` ligada (`database/seeders/PapeisSeeder.php:papel:80`); os demais papéis nascem sempre (`database/seeders/PapeisSeeder.php:papel:58`, `:papel:61`, `:papel:101`). Cada aresta liga o papel à entidade de permissão que ele de fato tem no banco (Shield), não a uma suposição.

```mermaid
flowchart LR
%% DG-02
accTitle: Casos de uso por papel
accDescr: Os oito atores do kit e os casos de uso que cada papel alcança, dentro da fronteira do Starter Kit Easy.
  ator_visitante["Visitante"]
  ator_panel_user["Usuário do painel (panel_user)"]
  ator_admin_app["Admin da organização (admin_app; requer KIT_TENANCY)"]
  ator_admin["Administrador (admin)"]
  ator_infra["Infraestrutura (infra)"]
  ator_master_global["Super admin (master_global)"]
  ator_agendador["Agendador"]
  ator_cli["Operador da CLI"]
  subgraph fronteira ["Starter Kit Easy"]
    cu_login["Entrar"]
    cu_login_social["Entrar com login social (opcional)"]
    cu_recuperar_senha["Recuperar senha"]
    cu_cadastro["Cadastrar-se (opcional)"]
    cu_aceitar_convite["Aceitar/recusar convite recebido"]
    cu_trocar_painel["Trocar de painel"]
    cu_2fa["Confirmar 2FA"]
    cu_bloqueio["Bloquear/desbloquear sessão"]
    cu_operar_negocio["Operar o negócio no /app"]
    cu_convidar["Convidar usuário (gerir convites)"]
    cu_gerir_usuarios["Gerir usuários"]
    cu_gerir_usuarios_org["Gerir usuários da organização (opcional)"]
    cu_aprovar_cadastro["Aprovar cadastro (KIT_REGISTRO_APROVACAO_MANUAL)"]
    cu_gerir_papeis["Gerir papéis e permissões"]
    cu_configuracoes["Configurar a aplicação"]
    cu_ver_logs["Ver os logs"]
    cu_ver_saude["Ver a saúde da instalação"]
    cu_lixeira["Usar a Lixeira"]
    cu_personificar["Personificar usuário"]
    cu_lembrar_convites["Lembrar convites pendentes"]
    cu_tenancy["Ligar multi-organização"]
    cu_tudo["Tudo, via Gate::before"]
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
  ator_admin --> cu_personificar
  ator_admin --> cu_aprovar_cadastro
  ator_infra --> cu_ver_logs
  ator_infra --> cu_ver_saude
  ator_infra --> cu_lixeira
  ator_master_global --> cu_tudo
  ator_agendador --> cu_lembrar_convites
  ator_cli --> cu_tenancy
```

## DG-03 — Regra de acesso ao painel

A ordem exata de `User::canAccessPanel()` (`app/Models/User.php:canAccessPanel:156`): indisponibilidade da conta (`:166`), aprovação pendente (`:193`), `master_global` (`:206`), contexto do papel — qualquer organização com tenancy, só o contexto global sem ela (`:217`) — e por fim o papel do painel (`:219`). Depois de permitido, a extensão de `User::canAccessTenant()` (`:789`) decide a organização: inativa nega (`:813`), `master_global` sempre entra (`:826`), e sem vínculo nega (`:830`).

```mermaid
flowchart TD
%% DG-03
accTitle: Regra de acesso ao painel
accDescr: A ordem de decisão de canAccessPanel, da indisponibilidade da conta até a checagem de papel, e a extensão de canAccessTenant quando o painel usa multi-organização sob KIT_TENANCY.
  inicio(["Início"]) --> checa_indisponivel{"Conta indisponível?"}
  checa_indisponivel -->|"Sim"| nega_403["Nega — 403"]
  checa_indisponivel -->|"Não"| checa_pendente{"Aprovação pendente?"}
  checa_pendente -->|"Sim"| nega_403
  checa_pendente -->|"Não"| checa_master{"É master_global?"}
  checa_master -->|"Sim"| permite["Entra no painel"]
  checa_master -->|"Não"| checa_contexto{"Painel com tenancy ligada?"}
  checa_contexto -->|"Sim"| papel_em_alguma["Papel em qualquer organização"]
  checa_contexto -->|"Não"| papel_global["Papel no contexto global"]
  papel_em_alguma --> checa_papel{"Tem papel do painel?"}
  papel_global --> checa_papel
  checa_papel -->|"Sim"| permite
  checa_papel -->|"Não"| nega_403
  permite --> checa_tenant{"Organização acessada: canAccessTenant?"}
  checa_tenant -->|"master_global"| permite_tenant["Entra na organização"]
  checa_tenant -->|"vínculo com a organização"| permite_tenant
  checa_tenant -->|"inativa ou sem vínculo"| nega_404["Nega — 404"]
```

## DG-13 — ER do núcleo

As entidades centrais e como se referenciam: a conta (`users`) a uma organização via `tenant_user` (`app/Models/User.php:tenants:748`, `app/Models/Tenant.php:users:112`), a um papel via `model_has_roles` (Shield), o vínculo social (`app/Models/User.php:vinculosSociais:758`), o convite (`app/Models/Convite.php:papel:116`, `:tenant:122`, `:convidadoPor:128`) e o catálogo de agentes de IA — sem relação nenhuma com `projetos`, a tabela de demonstração (`app/Traits/BelongsToTenant.php:tenant:82`). `ai_runs` e `agent_conversations` guardam identificadores como texto solto, sem chave estrangeira de verdade — por isso entram sem relação desenhada. `passkeys` fica de fora deliberadamente: a tabela existe (vendor), mas nenhuma feature do kit a usa (`enablePasskeys()` não é chamado).

```mermaid
erDiagram
%% DG-13
accTitle: ER do núcleo
accDescr: As entidades centrais do kit e como se referenciam - o vínculo do usuário a uma organização e a um papel, convites, vínculos sociais e o catálogo de agentes de IA; ai_runs e agent_conversations sem FK, só por convenção.
  users ||--o{ tenant_user : pertence
  tenants ||--o{ tenant_user : reune
  users ||--o{ model_has_roles : recebe
  roles ||--o{ model_has_roles : concede
  users ||--o{ vinculos_sociais : autentica
  users ||--o{ convites : convidou
  roles ||--o{ convites : "papel do convite"
  tenants |o--o{ convites : "organização, com KIT_TENANCY (opcional)"
  tenants ||--o{ projetos : "tem, cenário de demo (KIT_DEMO)"
  agentes_ia {
    string slug
    boolean ativo
    string provider
    text instrucoes
  }
  users ||--o{ ai_runs : "grava (sem FK)"
  users ||--o{ agent_conversations : "conversa (sem FK)"
```

## Sobre a origem destes diagramas

O [GitDiagram](https://gitdiagram.com/gsferro/filament-starter-kit-easy) gerou uma primeira leitura deste repositório por IA — é uma visão gerada por IA, não verificada. O diagrama oficial é este, que a corrige e amplia: o GitDiagram apresentava como sempre-presente o que é opcional (organizações, tenancy) e omitia a regra central do kit (acesso por papel).

## Índice dos 20 diagramas

| DG | Diagrama | Página |
|---|---|---|
| DG-01 | Arquitetura em camadas | Nesta página |
| DG-02 | Casos de uso por papel | Nesta página |
| DG-03 | Regra de acesso ao painel | Nesta página |
| DG-04 | Login por senha + 2FA | [Autenticação](../../autenticacao/) |
| DG-05 | Login unificado | [Login unificado](../../autenticacao/login-unificado/) |
| DG-06 | Retorno do login social | [Login social](../../autenticacao/login-social/) |
| DG-07 | Aceite de convite | [Convites](../../autenticacao/convites/) |
| DG-08 | Estados da conta | [Estados de usuário](../../autenticacao/estados-de-usuario/) |
| DG-09 | Estados do convite | [Convites](../../autenticacao/convites/) |
| DG-10 | Assistente de IA na sessão | [Autenticação](../../autenticacao/) |
| DG-11 | Guardrails do assistente | [Roteiro de features](../../operacao/roteiro-de-features/) |
| DG-12 | Mapa do painel /infra | [Trilhas de infraestrutura](../../recursos/trilhas-de-infraestrutura/) |
| DG-13 | ER do núcleo | Nesta página |
| DG-14 | Precedência de configuração | [Configurações do kit](../../recursos/configuracoes-do-kit/) |
| DG-15 | Instalação (kit:install) | [Instalação avançada](../../comecar/instalacao-avancada/) |
| DG-16 | kit:update — relatório | [Atualizando o projeto](../../comecar/atualizando-o-projeto/) |
| DG-17 | Duas rotas de entrega | [Atualizando o projeto](../../comecar/atualizando-o-projeto/) |
| DG-18 | Containers por profile | [Instalação avançada](../../comecar/instalacao-avancada/) |
| DG-19 | Segundo plano (composer dev) | [Desenvolvendo o kit](../../operacao/desenvolvendo-o-kit/) |
| DG-20 | Requisição em /app/{tenant} | [Multi-tenancy](../../recursos/multi-tenancy/) |

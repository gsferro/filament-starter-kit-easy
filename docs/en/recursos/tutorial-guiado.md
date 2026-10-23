---
title: "Guided tutorial"
description: "Getting-started journeys, with tours that point out each screen, across all three panels: seeded by a parent seeder with one child seeder per feature, and enabled by a switch on the settings screen…"
sidebar:
  order: 11
---
The guided tutorial shows newcomers **where everything is**: one "Primeiros passos" (getting
started) journey per panel, with one step per kit feature, and each step with a **tour** that
highlights the screen, table, search or button it describes (`wallacemartinss/filament-onboarding`).
Users track their progress in the **floating checklist**, available on every screen, and on the
**Primeiros passos** page in the menu.

Some steps **complete automatically**: the profile step completes when two-factor authentication
is confirmed, and the invitations step completes after the first invitation is sent, even if it
was sent before the journey existed.

## Two separate things: seeding and enabling

| | What it does | How |
|---|---|---|
| **Seed** | creates the journeys, steps, tours and conditions in the database | `php artisan db:seed --class=OnboardingSeeder`, or `kit:install --onboarding` |
| **Enable** | shows the checklist, tours, "Primeiros passos" page and authoring screens | **Usar o tutorial guiado** (use the guided tutorial), on the **Aplicação** tab of `/admin/configuracoes-da-aplicacao` (the *Tutorial guiado* section) |

Seeding does **not** enable it, and the two can be done in either order. On a new installation the
switch ships **off** (`KIT_ONBOARDING=false`). With it off:

- the **Onboarding** and **Condições** menus disappear from `/admin`, and their URLs return **404**,
  even for users with the permission;
- the checklist, tours and "Primeiros passos" page do not appear in any panel;
- the "Jornadas de onboarding concluídas" widget disappears from the `/admin` dashboard;
- anyone with a screen already open who clicks the checklist has the action refused, and nothing
  is saved.

Turning it off deletes nothing. Turn it back on and everything returns with each person's progress.

## How to start the journeys

**During installation.** `kit:install` asks *Popular os tutoriais guiados?* (seed the guided
tutorials?), with **no** as the default answer. Without a terminal (CI, Docker), pass the option:

```bash
php artisan kit:install --onboarding
```

**Later, at any time:**

```bash
php artisan db:seed --class=OnboardingSeeder
```

After seeding, turn on the switch at `/admin/configuracoes-da-aplicacao`. The seeder is not part of
the default `php artisan db:seed`: seeding the tutorial is the installer's choice.

**Running it again is safe and preserves edits.** The seeder identifies each journey, step and
condition by its key and creates **only what is missing**. It never overwrites anything that
already exists, including text someone edited at `/admin/onboarding-flows`. The seeded content is
the starting point; after that, authoring belongs to whoever administers the installation.

## What each seeder populates

The parent seeder (`database/seeders/OnboardingSeeder.php`) calls **one child seeder per feature**,
under `database/seeders/Onboarding/`. Each child adds its step to the **Primeiros passos** journey
of the panel where the feature lives.

| Child seeder | Panel | Screen | Completes | Hidden when |
|---|---|---|---|---|
| `PerfilEDoisFatoresSeeder` | admin, app, infra | "Meu perfil" | automatically, with 2FA confirmed | — |
| `BuscaGlobalSeeder` | admin, app, infra | Dashboard (⌘K / Ctrl+K search) | when the tour ends | — |
| `HubDeNavegacaoSeeder` | admin, app | Card hub | when the tour ends | the hub is off |
| `DashboardDinamicoSeeder` | admin, app, infra | Dynamic dashboard | when the tour ends | the dynamic dashboard is off in the panel, or the person cannot build it (`Manage:Dashboard`) |
| `DockDeAtalhosSeeder` | admin, app, infra | Shortcut dock | when the tour ends | the dock is not enabled in the panel |
| `BloqueioDeTelaSeeder` | admin, app, infra | Lock screen | when the tour ends | screen locking is off |
| `UsuariosSeeder` | admin | "Usuários" | when the tour ends | — |
| `ConvitesSeeder` | admin | "Convites" | automatically, after the first invitation | — |
| `PapeisEPermissoesSeeder` | admin | "Papéis" | when the tour ends | — |
| `ConfiguracoesDaAplicacaoSeeder` | admin | "Configurações da aplicação" | on visiting the screen | — |
| `AgentesDeIaSeeder` | admin | "Agentes de IA" | when the tour ends | AI agents are off |
| `HubDeInfraestruturaSeeder` | infra | "Central de infraestrutura" | when the tour ends | — |
| `PulseSeeder` | infra | Pulse | when the tour ends | — |
| `BackupsSeeder` | infra | Backups | when the tour ends | — |
| `LogsDaAplicacaoSeeder` | infra | Logs | when the tour ends | — |
| `ExcecoesSeeder` | infra | "Exceções" | when the tour ends | — |
| `FilasSeeder` | infra | "Monitor de filas" | when the tour ends | — |
| `AuditoriaSeeder` | infra | "Auditoria" | when the tour ends | — |
| `ExecucoesDeIaSeeder` | infra | "Execuções de IA" | when the tour ends | — |
| `LixeiraSeeder` | infra | "Lixeira" | when the tour ends | — |
| `CentralDeComandosSeeder` | infra | "Comandos e histórico" | when the tour ends | the command centre is off |

A step for a disabled feature **disappears from the checklist and does not count towards the
percentage** while the feature is off, and returns when it is enabled again.

### With multi-tenancy

Six children teach screens that **only exist with [multi-tenant mode](../multi-tenancy/)** enabled,
and the parent seeder calls them **only** when `KIT_TENANCY=true`:

| Child seeder | Panel | Screen | Completes | Hidden when |
|---|---|---|---|---|
| `OrganizacoesSeeder` | admin | "Organizações" | when the tour ends | multi-tenant mode was turned off after seeding |
| `MembrosDaOrganizacaoSeeder` | app | "Usuários da organização" | when the tour ends | multi-tenant mode was turned off after seeding |
| `ConvitesDaOrganizacaoSeeder` | app | "Convites da organização" | automatically, after the first invitation in the organisation | multi-tenant mode was turned off after seeding |
| `ConvitesRecebidosSeeder` | app | "Convites recebidos" | when the tour ends | multi-tenant mode was turned off after seeding |
| `TrocaDeOrganizacaoSeeder` | app | Organisation selector | when the tour ends | multi-tenant mode was turned off after seeding |
| `ProjetosSeeder` | app | "Projetos" (demo) | when the tour ends | the demo scenario is off, or multi-tenant mode was turned off after seeding |

The journeys are the same across all organisations, but **progress is per organisation**: the same
person goes through the first steps separately in each one. Enabled tenancy after seeding?
`kit:tenancy` recreates the database; run `OnboardingSeeder` again afterwards.

### Outside the tutorial

The login, sign-up, password recovery and e-mail verification screens have no tutorial: the
checklist only exists inside a panel. Neither does the public welcome page (`/`), which sits outside
any panel. Standalone widgets get no step of their own: they belong to the dashboard step. The
package's own welcome screen stays off. The full list of screens without a tutorial, with the
reason for each, is in `OnboardingSeeder::ISENTAS`.

## Conditions

Conditions are the checks that make a step complete or appear automatically. There are two types:

- **In the database**, editable under **Onboarding → Condições**: `convidou_alguem` and
  `convidou_alguem_na_organizacao` (the latter only with tenancy) count the invitations the person
  has sent.
- **In code**, in `App\Support\TutorialGuiado::condicoes()`: `tem_dois_fatores`; one
  `tela:{chave}` per step that teaches a screen (the step shows for whoever can open that screen in
  the panel, which already covers the permission and the feature switch); and the ones for steps
  without a screen of their own (`dock_no_painel`, `bloqueio_de_tela_ligado`, `multi_tenant_ligado`)
  plus `dashboard_dinamico_no_painel` (on in the panel and the person can build the dashboard).
  They appear in the step editor's selector.

## Who can see it

Anyone with the **Primeiros passos** page permission (`View:PrimeirosPassos`, on the roles
screen), with the switch on. Without it, the person sees neither the checklist nor the menu item.
Authoring (journeys and conditions) follows the `ViewAny:OnboardingFlow` and
`ViewAny:OnboardingCondition` permissions.

## New feature

Every new panel screen needs a child seeder or a declared exemption.
`tests/Kit/InventarioDeOnboardingTest.php` fails until it is added to `OnboardingSeeder::TELAS` or
`OnboardingSeeder::ISENTAS`. The instructions for agents and people are in the
`.ai/rules/onboarding.md` rule.

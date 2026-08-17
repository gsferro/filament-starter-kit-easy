# starter-kit-easy

<img alt="Starter Kit Easy" class="filament-hidden" src="https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbnail.png"/>

[![Packagist](https://img.shields.io/packagist/v/gsferro/starter-kit-easy.svg?style=flat-square)](https://packagist.org/packages/gsferro/starter-kit-easy)
[![Downloads](https://img.shields.io/packagist/dt/gsferro/starter-kit-easy.svg?style=flat-square)](https://packagist.org/packages/gsferro/starter-kit-easy)
[![Tests](https://img.shields.io/github/actions/workflow/status/gsferro/filament-starter-kit-easy/ci.yml?branch=main&style=flat-square&label=tests)](https://github.com/gsferro/filament-starter-kit-easy/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/packagist/php-v/gsferro/starter-kit-easy.svg?style=flat-square)](https://packagist.org/packages/gsferro/starter-kit-easy)
[![Filament](https://img.shields.io/badge/Filament-5.x-FFAA00?style=flat-square)](https://filamentphp.com)
[![License](https://img.shields.io/packagist/l/gsferro/starter-kit-easy.svg?style=flat-square)](LICENSE)

> 🇺🇸 English · 🇧🇷 [Português](https://github.com/gsferro/filament-starter-kit-easy/blob/main/README.md)

A ready-to-use **Laravel 13 + Filament 5** starter kit. One command creates the project, installs everything, migrates, seeds the database and hands you three working panels: **business**, **administration** and **infrastructure**.

```bash
composer create-project gsferro/starter-kit-easy my-project
cd my-project
composer dev
```

There is no manual step: `create-project` already creates the `.env`, generates the `APP_KEY`, creates the database, runs the migrations, seeds roles/permissions/user, publishes the Filament assets and builds the front-end. At the end it prints the URLs and the initial login.

Before touching the database, it **asks five questions** — the same way `laravel new` does:

| | Question | Default |
|---|---|---|
| 1 | Project name | the folder name |
| 2 | Database | SQLite · **PostgreSQL** (recommended: the only one with `pgvector`, required by the local AI features) · MySQL |
| 3 | Administrator e-mail and password | `admin@example.com` / `password` |
| 4 | Primary color of the panels | the Filament default |
| 5 | Multi-tenancy | off |

**Hitting Enter on everything installs exactly as before** — no question is mandatory, and the first one is "customize now?", which skips them all at once. With no terminal (CI, Docker, `--no-interaction`) nothing is asked. At the end the installer prints a summary of what changed, what is still edited by hand, and offers to run the kit's test suite.

> Multi-tenancy is the item that pays off most to decide now: switched on during installation it costs nothing; switched on later, `kit:tenancy` **recreates the database** (the permission tables only get the tenant column if the flag is active before the migration).

![Installing starter-kit-easy in a single command](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/install.gif)

Prefer to clone? The same installer runs on its own:

```bash
git clone https://github.com/gsferro/filament-starter-kit-easy.git my-project
cd my-project && rm -rf .git && git init   # drop the kit's history
composer setup
```

## Demo access

The seeder creates a master user that already gets into all three panels:

| | |
|---|---|
| **User** | `admin@example.com` |
| **Password** | `password` |
| **Role** | `master_global` (beats any permission through `Gate::before`) |

Sign in at `/app`, `/admin` or `/infra` — the same session works for all three, and the user menu switches panels.

> ⚠️ **Change the password before exposing the environment.** To be born with different credentials, set `KIT_ADMIN_EMAIL`, `KIT_ADMIN_PASSWORD` and `KIT_ADMIN_NAME` in `.env` **before** running the installation (the values live in `config/kit.php`). On an already-installed project, change it from the panel itself at `/admin/users` or under **My profile**.

To see the access boundary in action, create a user with only the `admin` or `infra` role: they get into the matching panel and take a 403 on the other.

## The three panels

| Panel | URL | What for | Who gets in |
|---|---|---|---|
| **App** | `/app` | The business operation. **Intentionally empty** — this is where your project is born | `master_global`, `panel_user`, `admin_app` (with tenancy) |
| **Admin** | `/admin` | Users, roles and permissions (Shield), AI agent catalog, onboarding authoring | `master_global`, `admin` |
| **Infra** | `/infra` | Health checks, backups, queues, logs, auditing, caches, commands, Pulse, AI costs | `master_global`, `infra` |

**Who gets in comes from the role, not from a list in the code.** Each role declares which panel it is good for, in the `roles.painel` column — the **Painel** field on the `/admin` → Roles screen. `App\Models\User::canAccessPanel()` compares that column against the panel being opened. Creating a role and picking its panel **is** the act of granting access.

Null is **not** a wildcard: a role with no panel only carries permissions and opens no panel at all. The `master_global` role gets into all three another way — it beats any gate through `Gate::before` (`App\Providers\KitServiceProvider`), with no permissions in the database, and `canAccessPanel()` lets it through before it ever looks at the column.

> ⚠️ **Deliberate break:** up to 0.10.0 `/app` was open to **any authenticated user**. Not anymore — with no role, nobody gets into any panel. If you are updating an existing project, run both seeders (`ShieldPermissionsSeeder` and `PapeisSeeder`) and review your users: whoever runs the business needs the `panel_user` role, or a role of your own carrying the `app` panel.

On panels **without** tenancy (`/admin`, `/infra`) the role must be assigned in the global context: being an `admin` inside one organization is not a credential to administer the installation. On `/app` the role counts in any organization — which one you open is decided later, by `canAccessTenant()`.

> With [multi-tenancy](#multi-tenancy-opt-in) turned on, **App** becomes `/app/{tenant}` and shows only the selected tenant's data. Admin and Infra stay global.

Separating admin from infra is the whole point of the kit: whoever administers users doesn't need (and shouldn't) see logs, queues and operational commands, and vice versa.

### What each one looks like

| Login | Administration |
|---|---|
| [![Login screen](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/login.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/login.png) | [![Admin panel](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/panel-admin.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/panel-admin.png) |
| Two-column Auth Designer — swap the artwork in `public/images/auth/login.svg` | Users, roles, AI agents and administration indicators |

| Infrastructure | Business |
|---|---|
| [![Infra panel](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/panel-infra.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/panel-infra.png) | [![App panel](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/panel-app.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/panel-app.png) |
| Health, queues, audit trails, commands and AI costs — grouped under Observability, AI, Trails and System | Intentionally empty: it's where your project is born |

More screens: [application health](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/infra-health.png) · [users](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/admin-users.png) · [permissions (Shield)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/admin-roles.png) · [AI agent catalog](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/admin-agentes-ia.png) · [command center](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/infra-comandos.png) · [⌘K search](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/spotlight.png) · [access denied](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/erro-403.png)

## What's already there

**Administration and security**
- Shield (roles and permissions with a UI) on top of spatie/laravel-permission
- Breezy: user profile, avatar, 2FA and passkeys
- Auth Designer: two-column login screen (swap the artwork in `public/images/auth/login.svg`)
- Lockscreen: session lock on inactivity (30 min), registered on all 3 panels — the lock screen wears the same layout as the login page (Auth Designer), not Filament's simple layout
- Impersonate, authentication log, change auditing (owen-it)
- Panel Switch: switch panels from the user menu

**Observability and maintenance (infra panel)**
- Spatie Health with checks for database, cache, queues, scheduler, disk, debug mode and local AI
- Backup Monitor (spatie/laravel-backup), Jobs Monitor, Logs Explorer (no delete button — a trail is evidence)
- Command Center: Artisan commands pre-approved for the UI, with history
- Laravel Pulse embedded as a panel page
- Dependency Graph: a map of models, relations, resources and panels
- Release Notifier: warns you when there's a new version of the Composer packages

**AI (optional, local by default)**
- `laravel/ai` with an agent catalog in the database: system prompt, provider, model, tools and guardrails are **data**, editable in `/admin` with no deploy
- Chained guardrails: budget, prompt injection, local classifier, PII redaction and sensitive-output filter
- Execution ledger (`ai_runs`) with cost and tokens in the infra panel
- Chat widget with streaming
- 100% local inference through llama.cpp (`docker compose --profile ai up -d`) or any SaaS provider by switching `AI_PROVIDER`

**Productivity**
- **⌘K search** in place of the topbar's native field: finds records, screens, pages and creation actions — all scoped by permission (details below)
- Animated count badges in the menu, notification center with tabs, environment indicator
- **Dashboards already filled in** on the admin and infra panels: 20 widgets (stat cards with an animated counter, funnels, goals, breakdowns, timelines) over the data the panels already have — no empty screen waiting for you
- Branded error pages (Sentinel) in pt-BR — the 403 one only shows the permission diagnosis outside production
- 100% pt-BR UI, including plugins that ship English only (translations in `lang/vendor/`)

### The ⌘K search

[![⌘K search](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/spotlight.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/spotlight.png)

The topbar field is **Filament's native one** — same markup, same look, same `Ctrl/⌘+K`. What changes is what happens on click: instead of typing there, it opens the Spotlight overlay, which searches on four fronts:

| Category | What it finds |
|---|---|
| **Records** | Filament's native global search (respects your resources' `getGloballySearchableAttributes()`) |
| **Screens** | the panel's resources, **filtered by `canAccess()`** |
| **Pages** | the panel's pages, also by `canAccess()` |
| **Actions** | "Create X" for each resource, with `canAccess()` + `canCreate()` + `shouldRegisterNavigation()` |

Permission filtering is the reason `App\Filament\Spotlight\*` exists in the kit: the package's categories do **not** call `canAccess()`, and without that the search offers screens that would result in a 403 — an affordance leak. The "Create X" suggestions are the kit's too (`AcoesDeCriacao`), for the same reason plus one more: the package's discovery resolves URLs without checking context and takes the login screen down with a 500.

## User invitation

Someone from outside becomes a user **by invitation, and only by invitation**. An admin
opens `/admin/convites` — or, with tenancy, whoever holds `admin_app` opens
`/app/{organization}/convites` — and picks e-mail, role and organization; the kit sends a
link carrying a single-use token.

**Whoever invites doesn't need to know whether the address already has an account.** The kit
decides at acceptance time, and both paths use the same invitation and the same link:

| The address | What happens on acceptance |
|---|---|
| has **no** account | the person sets their own password and is born with the right role, in the right context, and with the e-mail already verified — the token proves ownership of the address |
| **already has** an account | it is an **access offer**: nobody is signed up again. The person logs in with the password they already have, confirms, and is linked to the organization with the invitation's role — their access in other organizations stays untouched |

On the offer path the token is **not enough**: acceptance requires the authenticated account
to be the invited e-mail, checked in the model and not in the screen's query. An intercepted
link is not access without the password of the invited address.

And saying **no** is possible. The user menu gains **Convites recebidos** (received
invitations), with the count of pending offers and the accept and decline actions; a decline
is **recorded**, the invitation stops being valid (including through the link), and whoever
administers sees "Recusado" in the listing instead of re-inviting someone who already said
no. The e-mail link remains the canonical path: it also works for someone who doesn't belong
to any organization yet and therefore can't reach that screen.

The acceptance screen is Filament's native registration page (`/app/register`), with one
guard: **without a valid token in the query string it refuses and redirects to login**.
There is no open sign-up.

| What | How |
|---|---|
| Token | `Str::random(64)`, stored **hashed** (`sha256`) — a leaked database dump is not access |
| Lifetime | `KIT_CONVITE_VALIDADE_DIAS` (7 days by default) |
| In bulk | **Invite in bulk** in the listing header: paste the addresses, one role and one organization for the whole batch. Up to `KIT_CONVITE_LIMITE_LOTE` (100 by default) — one bad address **does not stop the others**, and the summary tells you how many went out and why the rest did not |
| Usage | **single use**: for a new account, `aceito_em` is stamped in the same transaction that creates the user; for an offer, by a conditional `update` — which is what keeps two clicks from counting twice |
| Reminder | `KIT_CONVITE_LEMBRETES_DIAS` (D+3 and D+5 by default, counted from the send): the kit sends **one** reminder per invitation per due day, carrying a **second, parallel link** — the original link **keeps working**, and nothing is revoked even if the reminder lands in spam. The cap is the number of days in the list, and an empty list turns the feature off. Every day must be **smaller** than the lifetime, otherwise the invitation expires before the reminder is due and no reminder ever goes out |
| Resend | issues a new token and **kills the previous links** — the one from the send and the one from the last reminder |
| Revoke | deletes the invitation; the link stops working immediately, and the deletion lands in `/infra/audits` |
| Edit | **does not exist** — the invitation was already sent; fix it by revoking and creating another |

> ⚠️ **Invitations depend on two environment facts.** `MAIL_MAILER` at its `log` default
> only writes the e-mail to `storage/logs` — nothing leaves the machine. And the
> notification is queueable with `QUEUE_CONNECTION=database`: **without a running worker
> the invitation never goes out**. `composer dev` starts one; on a deploy, use
> `php artisan queue:work`. A stalled queue shows up in the `/infra` monitor. **Multiply that
> by N for bulk invitations**: a batch of a hundred puts a hundred rows in `jobs` and delivers
> zero, while the screen says "a hundred sent" — because they were, to the queue. With
> `QUEUE_CONNECTION=sync` it is the opposite: each e-mail is an SMTP handshake inside the
> request, and a hundred of them hit `max_execution_time`. That is what the batch limit
> protects.

> ⚠️ **Reminders need both of the above AND the scheduler.** They are sent by
> `kit:convites-lembrar`, scheduled in `routes/console.php` for 08:00 — without
> `php artisan schedule:work` (or the docker compose `scheduler` service) it is never called.
> And the invitation's counter **goes up even with the worker stopped**: the write happens
> before the notification is queued, on purpose, so that a permanently broken address cannot
> make the cron retry the same invitation every day forever. The consequence is honest: a
> stopped worker spends reminders without delivering e-mail. On an installation with old
> pending invitations, rehearse with `MAIL_MAILER=log` — which is the kit's default.

The invitation's role decides the context of the assignment: a role of the `/app` panel is
granted inside the invitation's organization; a role of `/admin` or `/infra` is granted in
the global context — being an admin of one organization is not a credential to administer
the installation.

## Multi-tenancy (opt-in)

The kit is born **single-tenant**. One command turns multi-tenancy on — and those who don't need it pay nothing for it:

```bash
php artisan kit:tenancy          # turn it on
php artisan kit:tenancy --demo   # turn it on + create a demo scenario
```

| Panel | With the mode on |
|---|---|
| **App** | becomes `/app/{tenant}`. Users only see the tenants they're linked to, and it gains the **administration of their own organization** |
| **Admin** | gains the tenant CRUD and the **user linking** — not scoped, whoever administers sees them all |
| **Infra** | unchanged: health, queues and logs belong to the installation, not to a client |

### Administering one organization is not administering the installation

The kit's roles, and what each one means with the mode on:

| Role | Panel | Assignment context | What it does |
|---|---|---|---|
| `master_global` | all | global | beats any permission, via `Gate::before` |
| `admin` | `/admin` | global | users, roles and permissions of the **installation** |
| `infra` | `/infra` | global | health, queues, logs, auditing, commands |
| `admin_app` | `/app` | **the organization** | users and invitations **of their own organization** |
| `panel_user` | `/app` | the organization | uses the business; doesn't see the administration |

`admin_app` is the persona multi-tenancy creates: someone who administers **one** organization without administering the system. Inside `/app/{slug}` they gain **Users** and **Invitations**, scoped to that organization — and nothing beyond that. They don't enter `/admin` or `/infra`, get a 404 on another organization's panel, can't reach an outside user even by direct URL, don't create or edit roles (they only assign, and only `/app` panel roles), don't delete users — deleting would remove the person from **every** organization — and any invitation they create is stamped with their organization, ignoring the form.

The role only exists with tenancy on, and it is granted in `/admin` → organizations → **Linked users** → *Roles in this organization*. **Not** from the user record: there the assignment goes to the global context and the person enters `/app` seeing nothing. The full recipe, with the symptom, is in [`wikis/receitas.md`](wikis/receitas.md#promover-alguém-a-admin-de-uma-organização).

> ⚠️ **If you are updating an existing project:** run `ShieldPermissionsSeeder` and then `PapeisSeeder`. `panel_user` now receives the `/app` matrix **minus** the permissions of those two screens — without running the seeders, every ordinary user would keep the power to create and delete users.

### English in the code, your language in the UI

The code follows Filament's API vocabulary — model `Tenant`, table `tenants`, `getTenants()`, `canAccessTenant()` — so the official docs read without mental translation. **What the user sees is configurable**, and defaults to "Organização":

```php
// config/kit.php
'tenancy' => [
    'label'        => 'Company',    // Organization · Client · School · Unit · Store
    'label_plural' => 'Companies',
    'slug'         => 'companies',  // /admin/companies
],
```

### In your models

Every business model uses the kit's trait:

```php
use App\Traits\BelongsToTenant;

class Projeto extends Model
{
    use BelongsToTenant;

    protected $fillable = ['nome'];   // `tenant_id` stays out: the trait fills it
}
```

It provides the `tenant()` relationship, a **global scope** and automatic `tenant_id` filling. The scope matters because Filament only scopes what goes through a Resource — jobs, commands, listeners and APIs would be left out, and that's exactly where one client's data leaks into another's.

> ⚠️ **`kit:tenancy` recreates the database.** It turns on `permission.teams`, and the spatie migration only creates the tenant columns if the flag is active **before** the migrate. That's why it requires a clean git tree, an explicit confirmation, and runs `migrate:fresh --seed`. **The time to run it is day 1 of the project.** The detailed path — including global vs. per-tenant roles and `scopedUnique()` — is in [`wikis/arquitetura.md`](wikis/arquitetura.md#multi-tenancy-opt-in) (pt-BR).

## Working with AI agents

The kit ships ready for you to develop with a coding agent (Claude Code, Codex, Cursor, Junie, OpenCode) — and, more importantly, with **the documentation the agent needs to read** so it doesn't reinvent or break what's already there.

### 📚 `wikis/` — the kit's documentation

**[`wikis/README.md`](wikis/README.md) is the entry point.** It's where everything an agent (or a new teammate) needs before the first line of code lives:

| Document | What it answers |
|---|---|
| [`wikis/arquitetura.md`](wikis/arquitetura.md) | the three panels, the kit's "glue", a request's lifecycle, the three authorization levels |
| [`wikis/convencoes.md`](wikis/convencoes.md) | the non-negotiable rules and the **traps already handled** — the document that prevents the "fix" that breaks things |
| [`wikis/ia.md`](wikis/ia.md) | the agent as data, fail-closed guardrails, execution ledger |
| [`wikis/receitas.md`](wikis/receitas.md) | step by step: Resource, page, widget, health check, command, AI agent |
| [`wikis/agentes-e-skills.md`](wikis/agentes-e-skills.md) | Boost, MCP, the installed skills and the execution trio |
| [`wikis/pacotes.md`](wikis/pacotes.md) | which package owns which screen — so you don't reimplement vendor code |

It's also the folder where **you** write your project's own docs: `wikis/specs/{branch}/{feature}/` gets one folder per feature, created by the skill below.

> The wiki is written in pt-BR, like the kit's UI and code comments.

### The installed skills

[Laravel Boost](https://github.com/laravel/boost) is configured (`boost.json`) for five agents, with an MCP server (`php artisan boost:mcp`) and nine synchronized skills — among them `laravel-best-practices`, `pest-testing`, `ai-sdk-development`, `tailwindcss-development`, `pulse-development`, `laravel-backup` and `blaze-optimize`.

The one that changes the workflow is **[`feature-wiki`](https://github.com/gsferro/laravel-ai-skills)**: invoked **before** implementing any feature, it creates `wikis/specs/{branch}/{feature}/` with an action plan (PRD), architecture decisions (ADR), progress tracking and test cases — and it sets the project's logging standard.

> 💡 **New feature? Call `/feature-wiki`.** It's the first step, before any `php artisan make:*`. The skill researches the code, writes the plan, and only then does implementation start. For a typo, a config tweak, a pure refactor or a dependency bump, skip it — the skill itself tells you when it isn't worth it.

In Claude Code it works alongside two plugins already enabled in `.claude/settings.json`, each covering a different layer:

| Layer | Tool | Role |
|---|---|---|
| Communication | [Caveman](https://github.com/JuliusBrussee/caveman) | terse replies — does **not** apply to the wiki, code, commits or security warnings |
| Planning | [feature-wiki](https://github.com/gsferro/laravel-ai-skills) | PRD + ADR + test cases + tracking |
| Execution | [Ponytail](https://github.com/DietrichGebert/ponytail) | the minimum code that works — without cutting validation, security or error handling |

```bash
php artisan boost:add-skill gsferro/laravel-ai-skills   # the skill
php artisan boost:update                                # syncs it to every agent
```

> `AGENTS.md` and `CLAUDE.md` are **generated** by Boost — editing them by hand is lost work on the next `boost:update`. Durable rules go in `.ai/rules` (the `record-rule` tool) or in `wikis/`.

## Requirements

- PHP 8.3+ and Composer 2
- Node 20+ (optional — without it the installation still goes through and tells you how to build later)
- Docker (optional — only for Postgres, Redis, local AI and e-mail)

## Database

**The installation asks** — SQLite, PostgreSQL or MySQL. The default is **SQLite**, so it depends on nothing.

**PostgreSQL is the recommended one**, for a functional reason: it is the only one shipping `pgvector`, which the local AI features that use semantic search (embeddings) depend on. With SQLite or MySQL the rest of the kit runs the same — only those features are unavailable.

If you pick Postgres during installation, the `.env` already comes with the block `docker-compose.yml` reads. If the container is not up at that moment, the kit warns you, **skips the migrations** and prints the command to finish:

```bash
docker compose up -d
php artisan migrate --seed
```

To switch after the installation, bring the containers up and copy the variables:

```bash
docker compose up -d              # pgsql (with pgvector) + redis
# copy the database block from .env.docker into your .env
php artisan migrate --seed
```

## Docker

Everything is opt-in per profile. One container per feature:

```bash
docker compose up -d                            # pgsql + redis
docker compose --profile ai up -d               # + llama.cpp (chat and embeddings)
docker compose --profile mail up -d             # + mailpit (1025 / 8025)
docker compose --profile full up -d             # the whole infrastructure
docker compose --profile app up -d --build      # the containerized application
docker compose --profile realtime up -d reverb pulse
```

| Service | Port | Profile |
|---|---|---|
| PostgreSQL 17 + pgvector | 5432 | base |
| Redis 7 (cache only) | 6379 | base |
| llama.cpp (chat) | 8080 | `ai` |
| llama.cpp (embeddings) | 8081 | `ai` |
| Mailpit | 1025 / 8025 | `mail` |
| App (nginx + php-fpm) | 8000 | `app` |
| Reverb (WebSocket) | 8090 | `app`, `realtime` |

Reverb uses 8090 instead of the default 8080 so it doesn't collide with llama.cpp.

## Commands

```bash
composer dev          # server + queue + vite together
composer test         # pint + phpstan + the whole suite
composer test:kit     # only the kit's tests (the foundation)
composer lint         # formats the code
php artisan kit:install --force   # reinstalls from scratch (deletes the SQLite file) and asks again
php artisan kit:install --no-custom   # installs without asking anything
php artisan kit:update            # brings in improvements from a new kit version
php artisan kit:tenancy           # turns on multi-tenancy (opt-in)
```

### The kit's tests

The kit ships its own suite, isolated in `tests/Kit/` — access to the three panels, infra and admin screens standing up, foundation invariants (uuid, gates, auditing) and the AI layer's contract.

It's kept apart from yours on purpose: after a `kit:update` you want to know whether the **foundation** is still intact, without waiting on your business suite.

```bash
composer test:kit                     # shortcut
php artisan test --testsuite=Kit      # equivalent
php artisan test --group=kit          # same thing, by Pest group
php artisan test --testsuite=Feature  # only YOUR tests
```

Your tests go in `tests/Feature` and `tests/Unit`, as usual — the kit never touches them.

## Customize your project

**The installer already asks the first five** — the list below is for changing them later, or for whoever skipped the questions.

| # | What | Where | Asked during installation? |
|---|---|---|---|
| 1 | **Name** | `APP_NAME` in `.env` | ✅ |
| 2 | **Database** | the `DB_*` block in `.env` | ✅ |
| 3 | **Seeder credentials** | `KIT_ADMIN_EMAIL` / `KIT_ADMIN_PASSWORD` in `.env` | ✅ |
| 4 | **Primary color** | `KIT_COR_PRIMARIA` in `.env` (a color name from the Filament palette) | ✅ |
| 5 | **[Multi-tenancy](#multi-tenancy-opt-in)** | `php artisan kit:tenancy`, and the displayed term in `config/kit.php` → `tenancy.label` | ✅ |
| 6 | **Login artwork** | `public/images/auth/login.svg` | — |
| 7 | **Panel access** | each user's role (`/admin` → Roles, the *Painel* field); the rule that reads it is `App\Models\User::canAccessPanel()` | — |
| 8 | **Permission matrix** | `database/seeders/PapeisSeeder.php` | — |
| 9 | **Health checks** | `KitServiceProvider::configureHealthChecks()` | — |
| 10 | **Commands in the UI** | `config/command-center.php` | — |
| 11 | **Backups** | destination and schedule in `config/backup.php` | — |
| 12 | **AI agent** | `/admin` → AI Agents (or `database/seeders/AssistenteSeeder.php`) | — |

The last seven are not asked because they are **code or screen data**, not a value that fits in a terminal prompt. The installer lists them in the final summary, each with its file.

> ⚠️ Item 5 is the only one that is **not** "edit a file" once installed: `kit:tenancy` runs `migrate:fresh --seed` and **deletes your data**. It requires a clean git tree and an explicit confirmation. **Answered during installation it deletes nothing** — the database does not exist yet, and that is the right moment to decide.

> The primary color applies to all three panels. With [multi-tenancy](#multi-tenancy-opt-in) on, each organization's color **wins** over it inside `/app/{slug}` — `/admin` and `/infra` keep the project's one. For a full palette, and not just `primary`, the way is still `->colors([...])` in each `app/Providers/Filament/*PanelProvider.php`.

## Global Filament configuration

A single file defines how **every** table, toggle, modal and column in the project behaves: `app/Providers/Concerns/ConfiguraFilamentGlobal.php` (applied by `KitServiceProvider`). Change it there, and it changes everywhere — including on third-party plugin screens, which you couldn't edit any other way.

**Every table is born with:**

| Behavior | Why |
|---|---|
| `deferLoading()` | the screen shows up before the query finishes |
| `striped()` + `stackedOnMobile()` | list reading on desktop, cards on mobile |
| `persistFilters/Search/Sort/ColumnSearchesInSession()` | the user's slice survives navigation |
| `reorderableColumns()` + `dragReorderableColumns()` + `stickableColumns()` | columns that can be reordered, dragged and pinned |
| **resizable columns** (`asmit/resized-column`) | width adjustable by the user, preserved in the session |
| `filtersLayout(Modal)` + `filtersFormColumns(2)` + `deferFilters()` | with 3+ filters the dropdown turns into scrolling; the modal doesn't |
| `defaultPaginationPageOption(10)` + `extremePaginationLinks()` | predictable pagination, with first/last shortcuts |
| `deselectAllRecordsWhenFiltered(false)` | filtering doesn't throw the selection away |

Also global: modals that do **not** close on Esc (an accidental tap would discard the form), toggles with state color and icon, boolean icon column with a colored check/x, `CreateAction` with a default icon and the panel switcher.

> **Resizable columns on new screens:** the default behavior already applies to any table; for the chosen width to be **remembered**, the list page needs the trait:
>
> ```php
> use Asmit\ResizedColumn\HasResizableColumn;
>
> class ListProdutos extends ListRecords
> {
>     use HasResizableColumn;
> }
> ```

> 📌 **TODO:** turn these defaults into **Settings under `/admin`**, so pagination, density, filter persistence and resizable columns become a project preference set through the interface, with no code editing. `filament/spatie-laravel-settings-plugin` is already installed for that.

## Kit conventions

- **UUID in routes, int `id` as PK.** Every new table gets `$table->uuid('uuid')->unique()` and the model uses `App\Traits\TemUuid`. A URL with a numeric id returns 404 and nobody enumerates records by sequence. UUID is not authorization — policies remain mandatory.
- **Auditing on what is editable.** `App\Traits\AuditsFillables` audits exactly the `$fillable`, without leaking technical columns into the trail.
- **Seeders never use factories or faker.** `fakerphp/faker` is `require-dev` and the Docker image runs `--no-dev`.
- **Permissions come from a seeder, not from the interactive `shield:generate`** — that's what makes an unattended install possible. `ShieldPermissionsSeeder` generates for all **three** panels (the Shield command only sees the current panel); `PapeisSeeder` slices the matrix per panel and hands it to the roles. After creating new Resources, run both (see [below](#after-creating-your-resources)).
- **Panel access is data on the role**, in the `roles.painel` column — not a list of names in the code. A role with no panel opens no panel: the default is closed.
- **No affordance without permission.** Menu, search and actions consult `canAccess()`/`canCreate()` before showing up. Finding something that results in a 403 is considered a bug.
- **Plugin translations go in `lang/vendor/`.** Several packages ship English only; the kit translates them without touching vendor.

### Traps already handled

Things that cost time to figure out and that the kit already delivers done — if you change them, know why:

| Where | What |
|---|---|
| Lockscreen | must be registered on **all three** panels: the package's `routes/web.php` resolves the plugin through the current panel and throws `LogicException` on every request — even `artisan package:discover` dies |
| Lock screen | it is a `SimplePage` and ignores the Auth Designer layout. `App\Filament\Pages\Auth\TelaBloqueio` wears the login layout (bound in `AppServiceProvider`) and **redeclares `$layout`** — the package trait assigns the static property, and without the redeclaration the login layout leaks into every Filament page in the process |
| "Lock session" menu item | the item the package registers has no `sort` and lands after the theme switcher; the kit replaces it in a `bootUsing()` with `sort(-1)` (inside `panel()` it would not work: plugins boot first, and the last registration wins) |
| Command Center | **no** `->cluster()`: with a cluster the root page returns 500 |
| `databaseNotifications()` | declared **after** `plugins()`, otherwise the Notification Center wipes out the customization, with no error at all |
| Dependency Graph | `canAccessUsing()` replaces the package's local-only rule (without it, 404 on staging) |
| Logs Explorer | `deletable(false)`: the package's delete does an `@unlink()` without recording a trace |
| Filter actions | **outside** the global `configureUsing()`: on a table with no filters the action is born nameless and takes the page down |
| Pulse + resized-column | both bundles declare constants in the global scope; loaded as an ES module so the second one doesn't die silently |
| ⌘K search | trigger on the `GLOBAL_SEARCH_BEFORE` hook (`USER_MENU_BEFORE` renders inside the dropdown) and the overlay opened in a `setTimeout`, otherwise the click itself closes the panel |

## After creating your Resources

```bash
php artisan make:filament-resource Produto --panel=app
php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
```

**Both, in this order, every time.** The first runs `shield:generate --all` on **each** panel and writes the policies; the second slices the matrix by the panel the Resource is registered on and hands the permissions back to the roles. The first one alone creates the permission and gives it to nobody — the screen stays at 403 for anyone who isn't `master_global`. Both are idempotent: running them again is normal operation.

> **Shield does not see RelationManagers.** Its discovery covers Resources, Pages and Widgets only, so no permission is generated and authorization falls back to the **related model's policy**. If that model already has a Resource on some panel, there is nothing to do. If it doesn't, write the policy by hand (`php artisan make:policy`) and declare the keys in `config('filament-shield.custom_permissions')` **before** running the seeders — otherwise the RelationManager is open to anyone who can open the parent Resource.

Add the kit's two traits to what was generated:

```php
// On the Resource — animated count badge in the menu:
use App\Filament\Concerns\BadgeContagemNavegacao;

class ProdutoResource extends Resource
{
    use BadgeContagemNavegacao;
}

// On the List page — remembers the column width chosen by the user:
use Asmit\ResizedColumn\HasResizableColumn;

class ListProdutos extends ListRecords
{
    use HasResizableColumn;
}
```

### Count badges

Every **kit** Resource already has a badge in the menu (Users, AI Agents, AI Runs). The count comes from `getEloquentQuery()`, never from `Model::count()`: the resource's query carries the scopes that apply to that panel, and counting straight from the model would show a number the listing doesn't confirm. Zero doesn't become a badge — a gray "0" on every item is just noise.

**Third-party plugin** resources (Auditing, Logins, Queues, Composer Packages, Commands, Shield Roles, Onboarding) go without a badge: `getNavigationBadge()` is a static method on the resource, and Filament offers no API to override it from the outside — the panel's `ResourceConfiguration` only lets you change the slug. Giving them a badge would mean extending each vendor resource and preventing the plugin from registering its own, which breaks on every package update. If one of them matters in your project, that's the path — resource by resource, deliberately.

## Updating a project born from the kit

**The kit is a starting point, not a dependency.** After `create-project` the project is yours: you rename panels, change `canAccessPanel()`, edit seeders. That's why there is **no** `kit:update` that overwrites files — it would rewrite exactly what you customized, and a starter kit that ruins the user's project is worth nothing.

What changes splits into three layers, and each one has its own path:

| Layer | What it is | How to update |
|---|---|---|
| **Dependencies** | Filament, plugins, Laravel | `composer update` — it's most of the improvements and it arrives on its own |
| **The kit's glue** | providers, traits, widgets, error views | manual diff against the new tag (below) |
| **Your business** | everything you wrote | never touched |

### The easy way: `php artisan kit:update`

The command automates the entire git step and **applies nothing without your approval**:

```bash
php artisan kit:update --dry-run   # only shows what changed
php artisan kit:update             # review and apply, file by file
```

What it does, in order:

1. **Checks the ground** — requires a git repository with a clean tree. Without that there would be no way back, so it refuses to run (showing the commands to put the project under version control).
2. **Links the kit temporarily** — adds the `kit` remote with **push blocked** and fetches the tags into a namespace of their own (`kit-v*`), so they don't collide with your project's versions.
3. **Compares** — from the version in `config('kit.version')` up to the chosen tag, restricted to the paths that belong to the kit. Your business code never enters the equation.
4. **Offers a temporary branch** (`kit-update/v0.2.0`) so yours doesn't get dirty.
5. **Asks file by file** — see the diff, apply, skip or stop. You can change your mind halfway and apply the rest in bulk. A file removed from the kit is never deleted automatically: it only warns you.
6. **Unlinks** — removes the remote and the `kit-*` tags on the way out, even if you interrupt it halfway. The project isn't left with anything third-party hanging around.

7. **Marks the applied version** in `config/kit.php` — only that line, without touching the rest of the file. It's the starting point for the next comparison.

Two details that show up in practice:

- **`config/kit.php` always shows up as "modified"** (it carries the version mark). Applying it brings the kit's new keys, but **replaces the whole file** — if you changed seeder credentials or added your own keys there, read the diff and copy only what matters instead of applying.
- **`kit:update` updates itself.** Since PHP already loaded the class into memory, the new behavior (and the new messages) only take effect on the following run. The command tells you when that happens.

At the end nothing is committed: you review with `git diff`, run `composer test:kit` (the foundation) and commit. Went wrong? `git checkout -- .` undoes it, or delete the branch and go back to yours.

**You don't have to approve 30 files one by one.** During the review the menu offers *"Apply all NEW files from here on"* and *"Apply EVERYTHING from here on"* — one confirmation covers the set. And you can start in bulk already:

```bash
php artisan kit:update --only-new   # only what doesn't exist in the project yet
php artisan kit:update --all        # everything, including what overwrites
```

The distinction is the point: **a new file has nothing to overwrite**, so applying those in bulk is safe — that's the case for the widgets, the Spotlight and the concerns. A **modified** one replaces the current content, and if you edited that file your version is lost (recoverable with `git checkout -- <file>`, since nothing is committed). That's why `--only-new` is the recommended bulk for a first pass, leaving the modified ones to review calmly.

| Option | What for |
|---|---|
| `--only-new` | applies all the new files at once (overwrites nothing) |
| `--all` | applies everything at once, with a single confirmation for the set |
| `--dry-run` | report only, changes nothing |
| `--tag=v0.3.0` | compare against a specific version |
| `--from=v0.1.0` | tell it which version the project started from (when `config/kit.php` doesn't know) |
| `--branch=name` | choose the temporary branch's name |
| `--no-branch` | apply on the current branch |
| `--keep-remote` | keep the kit's remote and tags at the end |

With no terminal (CI, `--no-interaction`) the command becomes a report and changes nothing — unless you pass `--only-new` or `--all`, which **are** the approval, given on the command line.

### The manual way

If you'd rather control every step — or understand what the command does under the hood:

Add the kit as a **second remote**, once. Your `origin` stays your project; `kit` is just a read source:

```bash
git remote add kit https://github.com/gsferro/filament-starter-kit-easy.git

# the kit's remote is read-only: it prevents an accidental `git push kit main`
# from sending YOUR project into the kit's repository
git remote set-url --push kit no_push
```

The kit's tags go into a namespace of their own (`kit-v*`). That matters: a `git fetch kit --tags` would bring `v0.1.0`, `v0.2.0`… into your project and collide with **your** versions later.

```bash
git fetch --no-tags kit 'refs/tags/*:refs/tags/kit-*'
git tag -l 'kit-*'      # kit-v0.1.0, kit-v0.2.0, ...
```

Then, at each version, see what changed and bring over only what matters:

```bash
# 1. overview between your version and the new one
git diff kit-v0.1.0..kit-v0.2.0 --stat

# 2. the diff of the kit's "glue" (ignore what you already rewrote)
git diff kit-v0.1.0..kit-v0.2.0 -- app/Providers app/Filament/Concerns \
        app/Filament/Spotlight app/Traits resources/views/errors config/kit.php

# 3. bring it over file by file, reviewing
git checkout kit-v0.2.0 -- resources/views/errors
git checkout kit-v0.2.0 -- app/Filament/Concerns/BadgeContagemNavegacao.php
```

Do this on a branch (`git switch -c update-kit`) and run `composer test` before merging. Files you rewrote: read the diff and apply by hand — it's the only safe path.

> 💡 **TODO / where the project is heading:** extract the "glue" into a Composer package of its own (`gsferro/kit-core`) with the providers, traits, widgets and infra pages. Then the middle layer becomes `composer update gsferro/kit-core` and the skeleton stays minimal — only what really is a starting point. It's this kit's natural evolution.

## Troubleshooting

- **403 on every panel, right after signing in** — the user has no role at all, or their role has no panel declared (an empty `roles.painel` is not a wildcard: it opens nothing). Give them a role at `/admin` → Users, or fill in the *Painel* field at `/admin` → Roles.
- **`/infra` or `/admin` returning 403** — your user needs a role whose panel is that one (`master_global`, `admin` or `infra`), and with multi-tenancy on the role must be assigned in the global context. The 403 screen shows which permission was missing, but **only outside production**: in production it reveals neither roles nor permissions.
- **Filament assets gone** — `php artisan filament:assets`.
- **Pulse with no data** — the daemon is missing: `php artisan pulse:check` (or the compose `pulse` service).
- **The bell doesn't update in real time** — `BROADCAST_CONNECTION=reverb` requires the Reverb process to be up; without it the kit falls back to 30s polling.
- **AI assistant unavailable** — bring up `docker compose --profile ai up -d` (the first boot downloads ~4.5 GB of model) or switch `AI_PROVIDER` to a SaaS provider with an API key.

## Installed packages

Everything below comes installed, published and registered on the panels — there is no "now install plugin X" step. The source of truth for versions is `composer.json`; the table tells you **what each one is for inside the kit**.

### Base

| Package | What for |
|---|---|
| [laravel/framework](https://packagist.org/packages/laravel/framework) | the framework |
| [filament/filament](https://packagist.org/packages/filament/filament) | the panels, tables, forms and widgets |
| [laravel/tinker](https://packagist.org/packages/laravel/tinker) | Laravel's REPL |
| [livewire/blaze](https://packagist.org/packages/livewire/blaze) | optimizes Blade components by folding them into the parent template |

### Administration and security

| Package | What for |
|---|---|
| [bezhansalleh/filament-shield](https://packagist.org/packages/bezhansalleh/filament-shield) | roles and permissions with a UI, on top of spatie/laravel-permission |
| [jeffgreco13/filament-breezy](https://packagist.org/packages/jeffgreco13/filament-breezy) | user profile, avatar, 2FA and passkeys |
| [caresome/filament-auth-designer](https://packagist.org/packages/caresome/filament-auth-designer) | two-column login screen |
| [marjose123/filament-lockscreen](https://packagist.org/packages/marjose123/filament-lockscreen) | session lock on inactivity, without logging out |
| [stechstudio/filament-impersonate](https://packagist.org/packages/stechstudio/filament-impersonate) | sign in as another user |
| [tapp/filament-authentication-log](https://packagist.org/packages/tapp/filament-authentication-log) | login history, IP and device |
| [owen-it/laravel-auditing](https://packagist.org/packages/owen-it/laravel-auditing) | change trail for your models |
| [tapp/filament-auditing](https://packagist.org/packages/tapp/filament-auditing) | the screen for that trail inside the panel |
| [syriable/filament-activitylog](https://packagist.org/packages/syriable/filament-activitylog) | activity log (spatie/laravel-activitylog) in Filament |
| [bezhansalleh/filament-panel-switch](https://packagist.org/packages/bezhansalleh/filament-panel-switch) | panel switching from the user menu |

### Observability and maintenance

| Package | What for |
|---|---|
| [shuvroroy/filament-spatie-laravel-health](https://packagist.org/packages/shuvroroy/filament-spatie-laravel-health) | health checks (database, cache, queues, scheduler, disk, AI) |
| [spatie/laravel-backup](https://packagist.org/packages/spatie/laravel-backup) | application and database backups |
| [brimham/filament-backup-monitor](https://packagist.org/packages/brimham/filament-backup-monitor) | backup history and health per destination |
| [croustibat/filament-jobs-monitor](https://packagist.org/packages/croustibat/filament-jobs-monitor) | queue monitor for any driver |
| [laboiteacode/filament-logs-explorer](https://packagist.org/packages/laboiteacode/filament-logs-explorer) | read and search the logs without leaving the panel |
| [ssbityukov/filament-command-center](https://packagist.org/packages/ssbityukov/filament-command-center) | Artisan commands pre-approved for the UI, with history |
| [laravel/pulse](https://packagist.org/packages/laravel/pulse) | real-time application performance and usage |
| [dotswan/filament-laravel-pulse](https://packagist.org/packages/dotswan/filament-laravel-pulse) | Pulse embedded as a panel page |
| [laboiteacode/filament-dependency-graph](https://packagist.org/packages/laboiteacode/filament-dependency-graph) | visual map of models, relations, resources and panels |
| [mominalzaraa/filament-composer-release-notifier](https://packagist.org/packages/mominalzaraa/filament-composer-release-notifier) | warns you when there's a new version of the Composer packages |
| [cms-multi/filament-clear-cache](https://packagist.org/packages/cms-multi/filament-clear-cache) | clear caches from the panel |

### AI

| Package | What for |
|---|---|
| [laravel/ai](https://packagist.org/packages/laravel/ai) | the official Laravel AI SDK (agents, tools, streaming) |
| [fomvasss/laravel-ai-tasks](https://packagist.org/packages/fomvasss/laravel-ai-tasks) | AI task orchestration: routing, queue, auditing and budget |

### UI and productivity

| Package | What for |
|---|---|
| [wezlo/filament-search-spotlight](https://packagist.org/packages/wezlo/filament-search-spotlight) | the ⌘K search overlay |
| [prodstarter/filament-notification-center](https://packagist.org/packages/prodstarter/filament-notification-center) | notification center with tabs and categories |
| [pxlrbt/filament-environment-indicator](https://packagist.org/packages/pxlrbt/filament-environment-indicator) | environment indicator (local, staging, production) |
| [gsferro/filament-odometer-easy](https://packagist.org/packages/gsferro/filament-odometer-easy) | animated counters in tables, infolists, stats and badges |
| [gsferro/odometer-easy](https://packagist.org/packages/gsferro/odometer-easy) | the odometer base, outside Filament |
| [gsferro/filament-stat-plus-easy](https://packagist.org/packages/gsferro/filament-stat-plus-easy) | stat cards with a corner icon, colored border and skeleton |
| [awcodes/filament-badgeable-column](https://packagist.org/packages/awcodes/filament-badgeable-column) | badges inside table columns |
| [asmit/resized-column](https://packagist.org/packages/asmit/resized-column) | columns resizable by the user |
| [laboiteacode/filament-dashboard-widgets](https://packagist.org/packages/laboiteacode/filament-dashboard-widgets) | ready-made metric, goal, breakdown and trend widgets |
| [mddev31/filament-dynamic-dashboard](https://packagist.org/packages/mddev31/filament-dynamic-dashboard) | user-configurable dashboard: drag and resize widgets |
| [lara-zeus/progress](https://packagist.org/packages/lara-zeus/progress) | progress bars in columns and entries |
| [wallacemartinss/filament-onboarding](https://packagist.org/packages/wallacemartinss/filament-onboarding) | checklists and guided tours, authored in `/admin` |
| [anselmokossa/filament-sentinel](https://packagist.org/packages/anselmokossa/filament-sentinel) | error pages (403, 404, 419, 500, 503) that look like the panel |
| [flowframe/laravel-trend](https://packagist.org/packages/flowframe/laravel-trend) | period aggregation for the widgets' charts |

### Data and services

| Package | What for |
|---|---|
| [filament/spatie-laravel-settings-plugin](https://packagist.org/packages/filament/spatie-laravel-settings-plugin) | settings pages in the panel |
| [spatie/laravel-settings](https://packagist.org/packages/spatie/laravel-settings) | the persisted settings behind them |
| [mike-bronner/laravel-model-caching](https://packagist.org/packages/mike-bronner/laravel-model-caching) | automatic caching of Eloquent queries |
| [predis/predis](https://packagist.org/packages/predis/predis) | pure-PHP Redis client (no extension needed) |
| [laravel/reverb](https://packagist.org/packages/laravel/reverb) | WebSocket for real-time notifications |

> **Engines under the plugins**, installed as dependencies (you don't declare them, but they're what actually runs): `spatie/laravel-permission` (Shield), `spatie/laravel-health` (the checks), `spatie/laravel-activitylog` (the activity log) and `livewire/livewire` (all of Filament).

### Development (`require-dev`)

| Package | What for |
|---|---|
| [pestphp/pest](https://packagist.org/packages/pestphp/pest) + [pest-plugin-laravel](https://packagist.org/packages/pestphp/pest-plugin-laravel) | the test suite |
| [phpunit/phpunit](https://packagist.org/packages/phpunit/phpunit) | the engine under Pest |
| [larastan/larastan](https://packagist.org/packages/larastan/larastan) | static analysis (`composer types:check`) |
| [laravel/pint](https://packagist.org/packages/laravel/pint) | formatting (`composer lint`) |
| [laravel-lang/common](https://packagist.org/packages/laravel-lang/common) | pt-BR translations for Laravel |
| [laravel/pail](https://packagist.org/packages/laravel/pail) | real-time logs in the terminal |
| [laravel/pao](https://packagist.org/packages/laravel/pao) | Laravel development tooling |
| [nunomaduro/collision](https://packagist.org/packages/nunomaduro/collision) | readable errors in the terminal |
| [mockery/mockery](https://packagist.org/packages/mockery/mockery) | mocks in tests |
| [fakerphp/faker](https://packagist.org/packages/fakerphp/faker) | fake data **in tests only** — the kit's seeders never use it |

### Front-end (`package.json`)

| Package | What for |
|---|---|
| [vite](https://www.npmjs.com/package/vite) + [laravel-vite-plugin](https://www.npmjs.com/package/laravel-vite-plugin) | the asset build |
| [tailwindcss](https://www.npmjs.com/package/tailwindcss) + [@tailwindcss/vite](https://www.npmjs.com/package/@tailwindcss/vite) | the CSS (v4, no config file) |
| [concurrently](https://www.npmjs.com/package/concurrently) | runs server, queue and vite together in `composer dev` |
| [@laravel/multiplex](https://www.npmjs.com/package/@laravel/multiplex) | batches Livewire requests (optional) |

## License

MIT.

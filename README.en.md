<img alt="Starter Kit Easy" class="filament-hidden" src="https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbnail.png"/>

[![Packagist](https://img.shields.io/packagist/v/gsferro/starter-kit-easy.svg?style=flat-square)](https://packagist.org/packages/gsferro/starter-kit-easy)
[![Downloads](https://img.shields.io/packagist/dt/gsferro/starter-kit-easy.svg?style=flat-square)](https://packagist.org/packages/gsferro/starter-kit-easy)
[![Plumb](https://plumbphp.dev/badges/gsferro/starter-kit-easy/composite.svg)](https://plumbphp.dev/gsferro/starter-kit-easy)
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
| **Infra** | `/infra` | Health checks, backups, queues, logs, exceptions, mail trail, recycle bin, auditing, caches, commands, Pulse, AI costs | `master_global`, `infra` |

**Who gets in comes from the role, not from a list in the code.** Each role declares which panel it is good for, in the `roles.painel` column — the **Painel** field on the `/admin` → Roles screen. `App\Models\User::canAccessPanel()` compares that column against the panel being opened. Creating a role and picking its panel **is** the act of granting access.

Null is **not** a wildcard: a role with no panel only carries permissions and opens no panel at all. The `master_global` role gets into all three another way — it beats any gate through `Gate::before` (`App\Providers\KitServiceProvider`), with no permissions in the database, and `canAccessPanel()` lets it through before it ever looks at the column.

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

## Our numbers

Not a showcase: it's the inventory of everything that already exists, and of what you won't have to write.

| | `/app` | `/admin` | `/infra` | **Total** |
|---|---:|---:|---:|---:|
| **Navigable screens** | 12 | 28 | 27 | **67** |
| Resources | 4 | 8 | 8 | **20** |
| Standalone pages | 4 | 3 | 12 | **19** |
| Widgets | 1 | 9 | 19 | **29** |
| `GET` routes | 19 | 34 | 33 | **86** |

`/app` is the smallest on purpose — it is born **empty**, because that's where your business comes in.
The other two already come complete.

| Foundation | |
|---|---:|
| Production packages | **55** |
| Development packages | **15** |
| Migrations | **48** |
| Policies | **14** |
| `kit:*` commands | **4** |

| Quality | |
|---|---:|
| Test cases (`Kit` + `Tenancy`) | **411**, with 1138 assertions |
| Screens swept in a real browser | **55** |
| Test files | **51** |
| PHPStan | **level 7**, zero errors |
| FilaCheck | **17** rules, all passing |

| Documentation | |
|---|---:|
| Reference documents (`wikis/`) | **9** |
| Specified features (`wikis/specs/`) | **15** |
| Project rules for AI agents (`.ai/rules/`) | **7** |

### PHPStan at level 7 — and why that's a strong point

Most Laravel projects stop at level 5 or 6. The kit runs at **7, with zero errors and no
baseline**: there is no `@phpstan-ignore` scattered around, no `phpstan-baseline.neon` hiding debt.

What level 7 catches and 6 doesn't, in practice:

- **Unchecked null.** `Filament::getCurrentPanel()` returns `?Panel`; `auth()->user()` returns
  `?User`. At level 6 you call a method on them and it passes. At 7, you have to prove it exists.
- **A wide vendor type leaking into your code.** `session()` is `mixed`, `env()` is `bool|string`,
  Shield's getters are `?array`. 7 forces you to narrow it at the **boundary**, once, instead of
  hoping the value is what you expect at every use.
- **`list<T>` vs `array<int,T>`.** `filter()` and `map()` preserve keys. An array with holes handed
  where a list was expected is a bug that only shows up at `json_encode` — it turns into an object
  instead of an array, and the front end breaks.

Going from 6 to 7 exposed **29 real errors** in the kit, and one of them was a genuine latent bug: a
`Convite|null` with a method called straight on it. All fixed at the source — none silenced.

> ### ⚠️ Watch out when implementing this in your project
>
> **Level 7 applies to the code you write too.** `composer test` runs
> `phpstan analyse` and fails the whole build.
>
> What shows up the most when someone starts writing in the kit:
>
> | What you write | What PHPStan demands |
> |---|---|
> | `auth()->user()->id` | prove there is a user: `auth()->user()?->id`, or an `if` before it |
> | `Filament::getTenant()->nome` | `?Model` — use `instanceof Tenant` as a guard |
> | `->filter()->all()` in a `@return list<string>` | `array_values()` at the end |
> | `env('ALGUMA_COISA')` straight into a `str_*` | `(string) env(...)`, or `config()` with a typed default |
> | a method with no return type | declare the type; the kit requires it everywhere |
>
> **Don't solve it with `@phpstan-ignore` or a baseline.** The kit has exactly **one** exception in
> `phpstan.neon`, and it is for a vendor macro resolved at runtime — with the reason, the two
> alternatives that were tried and dropped, and the test that covers the point for real. That's the
> standard: if an exception is needed, it comes with the justification and with the test that
> replaces it.
>
> If you want to loosen it in your project, it's one line in `phpstan.neon`. But know what you're
> trading away: the 29 errors above were all real.

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
- **Grouped exceptions** by type and frequency — what Health, Pulse and the log file don't answer
- **Sent-mail trail**: separates "it was never sent" from "it was sent and landed in spam"
- **Recycle bin**: restores what was deleted with `SoftDeletes` ([details](#the-infra-trails-exceptions-mail-and-recycle-bin))
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
- Branded error pages (Sentinel) in Portuguese (pt-BR) — the 403 one only shows the permission diagnosis outside production
- 100% pt-BR UI, including plugins that ship English only (translations in `lang/vendor/`)
- **Language switcher** on all three panels and on the login screens — driven by data, not by a flag (details below)
- **Media layer** (spatie/laravel-medialibrary) inside Filament's components: uploads, collections and conversions in form, table and infolist ([details](#attachments-and-media))

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

### The language switcher

The language button (`bezhansalleh/filament-language-switch`) is registered on **all three panels and on the login screens too** — which is exactly where someone who doesn't read Portuguese needs to switch, before a session even exists.

**It is driven by data, not by a flag.** The list of locales lives in `config/kit.php`:

```php
'idiomas' => ['pt_BR'],           // how the kit is born: one language, no button
'idiomas' => ['pt_BR', 'en'],     // two languages: the switcher shows up on its own
```

With a **single language** — the default — the switcher does not appear: there is nowhere to switch to. That is why this is a list and not a boolean; nobody forgets a flag left on with only one language.

> ⚠️ **The switcher translates Filament's layer and the packages', not the kit's own labels.** The coverage comes from Filament and `laravel-lang/common`. "Administrador Geral", "Acesso ao painel /app", the hub titles and the resource labels are pt-BR strings written in the code — there are ten `__()` calls in the whole app. Turning `en` on today makes **half the screen switch language and the other half not**. Internationalizing the kit is declared work, not yet done.

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

And saying **no** is possible. The user menu gains **Received invitations** (Convites
recebidos), with the count of pending offers and the accept and decline actions; a decline
is **recorded**, the invitation stops being valid (including through the link), and whoever
administers sees "Declined" in the listing instead of re-inviting someone who already said
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

## The `/infra` trails: exceptions, mail and recycle bin

The infrastructure panel already showed **health** (Health), **performance** (Pulse), **the log
file** (Logs Explorer) and **queues** (Jobs Monitor) — and none of them answered "which exception
is blowing up, and how often", "did the invitation arrive?" or "can that delete be undone?". Three
screens answer one of those each:

| Screen | Where | What it answers |
|---|---|---|
| **Exceptions** | `/infra`, *Observability* group | exceptions grouped by type and frequency, with a count badge in the menu |
| **Mail trail** | `/infra`, *Trails* group | every e-mail the kit sent — separates "it was never sent" from "it was sent and landed in spam" |
| **Recycle bin** | `/infra`, *System* group | restores records deleted with `SoftDeletes` |

### Both trails store sensitive data

That is why they live **only** on `/infra`, where getting in already requires the `master_global`
or `infra` role — on `/app` any panel role would see them:

- the exception's **stack trace** can carry request parameters, therefore personal data;
- the e-mail's **body** is stored, and the access invitation carries the acceptance link.

### Retention: the number is the intent, the scheduler is the execution

Both tables grow per event — a bug in a loop fills the disk in hours. That is why pruning has a
deadline, in `config/kit.php`:

| Key | `.env` | Default |
|---|---|---|
| `kit.retencao.excecoes_em_dias` | `KIT_RETENCAO_EXCECOES_DIAS` | 14 |
| `kit.retencao.emails_em_dias` | `KIT_RETENCAO_EMAILS_DIAS` | 14 |

The 14 days follow the `days` of the log rotation in `config/logging.php`: the trail dies together
with the log that produced it, not after it. **Zero or negative turns pruning off** for that trail —
and then the table grows with no ceiling, which is a choice, not an oversight.

> ⚠️ **The scheduler is what applies retention.** The routines are in `routes/console.php`; without
> `php artisan schedule:work` (or the docker compose `scheduler` service) the number in the config
> is only a declared intent.

### The recycle bin lists what you declare

`RevivePlugin` takes an **explicit list** of models in
`app/Providers/Filament/InfraPanelProvider.php` — today only `App\Models\Projeto`, the kit's only
model with `SoftDeletes`:

```php
RevivePlugin::make()
    ->navigationGroup('Sistema')
    ->navigationLabel('Lixeira')
    ->models([
        Projeto::class,
    ])
    ->withoutScoping(),
```

**A new model with `SoftDeletes` has to go into that list**, otherwise it ends up deleted with no
screen to restore it from. Automatic scanning of `app/Models` was avoided on purpose: it would
reach `User`, `Role` and `Tenant`, whose restoration has an **authorization** consequence — a user
comes back with a role in an organization that may no longer exist. The lock is the list, just like
the Command Center's allow-list.

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

## Attachments and media

`filament/spatie-laravel-media-library-plugin` delivers the media layer — uploads, collections and
conversions — inside Filament's form, table and infolist components. The demo model
`App\Models\Projeto` shows the whole design:

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Projeto extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('anexos');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('miniatura')
            ->nonQueued()   // the kit is born with QUEUE_CONNECTION=sync: queued, the
            ->width(200)    // conversion would only exist with a worker up, and the
            ->height(200);  // column would stay empty with no error at all
    }
}
```

And `ProjetoResource` consumes both ends:

```php
SpatieMediaLibraryFileUpload::make('anexos'),   // in the form

SpatieMediaLibraryImageColumn::make('anexos')   // in the table
    ->simpleLightbox(),
```

`->simpleLightbox()` works with no glue because `SpatieMediaLibraryImageColumn` **extends
`ImageColumn`**, which is exactly where the lightbox macro is registered.

**Organization scoping comes for free** — and that's the point. Spatie's `media` table is
polymorphic: the file belongs to the record, and the record is already scoped by
`BelongsToTenant`. Whoever can't reach the project can't reach the attachment, with no tenant
column in `media` and no configuration to remember to turn on.

> ⚠️ **The default media disk is `local`, and it is private on purpose.** With
> `MEDIA_DISK=public` the file lands in `storage/app/public`, served by the `public/storage`
> symlink: path `/storage/{id}/{file}`, sequential ID, reachable **without a session** — Filament's
> multi-tenancy does not reach the file system. Use `public` only for avatars and logos, which show
> up on the login screen.
>
> Two practical consequences of the private disk:
>
> 1. **`Media::getUrl()` answers 403.** That is fail-closed, and it is what you want. To publish a
>    link to private media use **`getTemporaryUrl()`**, which signs the URL.
> 2. **Whoever holds the link gets in, for as long as the signature is valid, with no session.**
>    Laravel's `storage.local` route validates the signature, not the user: sharing the link shares
>    the file until it expires. For attachments that need per-organization authorization, serve them
>    through your own route that checks the policy first.
>
> Already running an install with `MEDIA_DISK=public`? The new config only protects NEW files. Run
> **`php artisan kit:midia-privada`** (it takes `--dry-run`) to move what was already written —
> without it, the old media stays served by the symlink.

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

#### Caveman and Ponytail outside of Claude Code

The trio above is only a real trio if all three layers exist. In Claude Code, Caveman and
Ponytail come as **plugin** (`.claude/settings.json`) — with automatic activation by hook and
commands in the `/ponytail:…` and `/caveman:…` namespace. In other agents there is no plugin
system, and `feature-wiki` would invoke a `/ponytail-review` that does not exist.

That's why the kit **versions a copy** of the three skills that `feature-wiki` cites by name, in
`.agents/skills/`, `.ai/skills/` and `.junie/skills/`:

| Skill | What `feature-wiki` uses it for |
|---|---|
| `ponytail` | the simplicity ladder during implementation (step 7) |
| `ponytail-review` | plan audit against over-engineering (step 6, mandatory) and diff audit at the end |
| `caveman` | terse agent ↔ you communication; does **not** apply to wiki, code, commit or security warning |

Two practical consequences:

- **The invocation name changes.** In Claude Code it is `/ponytail:ponytail-review`; in the other
  agents the local copy answers to `/ponytail-review`, with no namespace.
- **`.claude/skills/` is left out on purpose.** Copying there would create two active `ponytail`s
  at the same time — the plugin's and the project's.

`boost:update` **does not** delete these folders: it only removes a skill it has already tracked
and that left `boost.json`, and none of the three are listed there. They are MIT copies, with the
original `LICENSE` attached — updating is re-copying from upstream ([Caveman](https://github.com/JuliusBrussee/caveman),
[Ponytail](https://github.com/DietrichGebert/ponytail)).

### The feature cycle with an agent

The kit does not ask you to trust the agent: it asks the agent to **leave a trail**. Each step
produces a file that the next step checks.

| # | You do | The agent produces | Why it exists |
|---|---|---|---|
| 1 | `/feature-wiki` with the request in plain text | `wikis/specs/{branch}/{feature}/00-requisito.md` — **immutable copy** of what you asked | The requirement is never rewritten to fit what was implemented. It is what judges the delivery |
| 2 | read and adjust | `01-plano-acao.md` (step-by-step PRD), `02-decisoes.md` (ADR), `04-casos-de-teste.md`, and `05-…-browser.md` when there is a screen | Reviewing a plan is cheap; reviewing 900 lines of diff is not |
| 3 | approve | automatic plan audit by `ponytail-review` | Cuts unnecessary step and premature abstraction **before** it becomes code |
| 4 | — | implementation following the plan, with `03-progresso.md` updated | A session that drops resumes from where it stopped, without rebuilding context |
| 5 | — | tests running (`--parallel --tia`) | Green is a precondition for the next step, not the delivery |
| 6 | — | `/feature-quality-gate` → `06-relatorio-qa.md` | Confronts requirement × plan × running app. The **traceability matrix** exposes the clause that never became a step, test or code — the omission a green suite does not denounce |
| 7 | approve | `/requirement-to-rule` → rule in `.ai/rules` | A decision that matters beyond this feature starts to matter for **every future session**, of any agent |

**What this changes in practice:**

- **The agent reads before writing.** `wikis/` and `.ai/rules` answer what already exists, and the
  [feature roadmap](#feature-roadmap) below lists the 61 ready screens. A feature
  reimplemented from scratch because the agent didn't know it existed is the most expensive and most invisible cost.
- **Context becomes a file, not chat history.** Switching agent, machine or person does not
  lose the why of the decision — it is in the ADR, versioned in the same commit as the code.
- **Simple by default, without cutting what matters.** Ponytail never simplifies validation at a trust
  boundary, error handling that prevents data loss, security or accessibility.
- **Less token per reply.** Caveman cuts the prose from the conversation; wiki, code and commit continue
  in normal Portuguese.
- **Every fix sticks.** A resolved trap becomes `.ai/rules` — and the next gate already checks it.
  When it can be proven by `pest --arch`, PHPStan or Rector, the rule points to the test instead of
  asking for goodwill.

> For a typo, `.env` tweak, dependency bump or pure refactor, **skip the cycle**. The skill
> itself tells you when it isn't worth it — ceremony in a one-line change is the over-engineering
> Ponytail exists to cut.

## Feature roadmap

Everything the kit delivers, numbered, with **where it is**, **who can access it** and **how to check it**. It serves three purposes: knowing what already exists before reimplementing, having a manual test script after a `kit:update`, and giving names to features in the automated tests.

**The "Test" column** says what is already checked automatically:

| Mark | Meaning |
|---|---|
| 🟢 | covered by automated test — `composer test:kit` or `composer test:browser` |
| 🔵 | covered **in a real browser**, with JS running |
| ⚪ | no test: depends on an external service (worker, cron, Docker, SMTP) or on visual judgment |

Where the route has `{org}`, it is multi-tenant mode — without it, the path is `/app` directly.

### Access and authentication

| # | Feature | Where | Who can access | How to check | Test |
|---|---|---|---|---|---|
| F-01 | Login on the three panels | `/app/login`, `/admin/login`, `/infra/login` | anyone | the three screens open without authentication, in the two-column layout | 🔵 |
| F-02 | Password recovery | `/{panel}/password-reset/request` | anyone | the screen opens; the e-mail depends on `MAIL_MAILER` | 🔵 |
| F-03 | Registration **only** by invitation | `/app/register?token=…` | whoever has a valid token | without a token in the query, the screen refuses and goes to login | 🟢 |
| F-04 | Two-factor authentication | `/{panel}/two-factor-authentication` | authenticated | the screen opens and offers the QR | 🔵 |
| F-05 | Passkeys | My profile | authenticated | key registration, in Breezy's profile | ⚪ |
| F-06 | Session lock | user menu → *Lock session* | authenticated | locks without logging out; returns with password. Uses the login layout, not `SimplePage` | 🟢 |
| F-07 | My profile, avatar and password | `/{panel}/my-profile` | authenticated | edits name, e-mail, password and avatar | 🔵 |
| F-08 | Impersonate | `/admin/users` → row action | `master_global` | enters as another user and returns via the top banner | ⚪ |

### Authorization

| # | Feature | Where | Who can access | How to check | Test |
|---|---|---|---|---|---|
| F-09 | **The role decides the panel** (`roles.painel`) | `/admin` → Roles | `admin`, `master_global` | create a role with panel `infra`: whoever has it enters `/infra` and takes 403 on `/admin` | 🟢 |
| F-10 | Readable 403 on the wrong panel | any panel | — | the 403 screen tells the account, the roles and offers an exit — and **does not** reveal permission in production | 🔵 |
| F-11 | `master_global` wins by `Gate::before` | the three | `master_global` | they enter everything **without** any permission in the database | 🟢 |
| F-12 | Roles and permissions grouped by panel | `/admin/shield/roles` | `admin` | the screen separates *Panel /admin*, */app* and */infra* | 🟢 |
| F-13 | `panel_user` **does not** administer | `/app{/org}` | `panel_user` | they use the business and don't see Users or Invitations — their matrix is the panel's **minus** the admin screens | 🟢 |
| F-14 | Without a role, nobody enters | the three | — | authenticated user without a role takes 403 on the three. Null **is not** a wildcard | 🟢 |

### Invitations

| # | Feature | Where | Who can access | How to check | Test |
|---|---|---|---|---|---|
| F-15 | Individual invitation | `/admin/convites` · `/app/{org}/convites` | `admin`, `admin_app` | e-mail + role + organization; the link goes by e-mail with a single-use token | 🟢 |
| F-16 | Invitation for someone who **already has an account** | same place | same | it becomes an *access offer*: the person logs in with the password they already have and is linked | 🟢 |
| F-17 | Received invitations box | user menu → *Received invitations* | any authenticated | accept **or decline**; the decline is recorded | 🟢 |
| F-18 | Bulk invitation | listing header | `admin`, `admin_app` | paste N addresses; one with a problem **does not** bring down the others, and the summary says why | 🟢 |
| F-19 | Automatic reminders | `kit:convites-lembrar` (cron 08:00) | — | D+3 and D+5, with a **second parallel link**; the original keeps working | 🟢 |
| F-20 | Resend / revoke | row action | `admin` | resend **kills** the previous links; revoke deletes and goes to `/infra/audits` | 🟢 |

### Multi-tenancy (opt-in)

| # | Feature | Where | Who can access | How to check | Test |
|---|---|---|---|---|---|
| F-21 | Turn the mode on | `php artisan kit:tenancy` | — | runs `migrate:fresh --seed`; **requires a clean git tree** | ⚪ |
| F-22 | Panel by organization | `/app/{org}` | linked | the selector lists only the user's organizations; another one gives 404 | 🟢 |
| F-23 | Organization CRUD | `/admin/organizacoes` | `admin` | create, **view** and edit in full screen | 🔵 |
| F-24 | User linking | organization → *Linked users* | `admin` | link, unlink and give a role **in that** organization | 🟢 |
| F-25 | `admin_app` | `/app/{org}` | the role | administers **one** organization: users and invitations scoped. Does not enter `/admin` | 🟢 |
| F-26 | Scope by trait | your models | — | `BelongsToTenant` gives relationship, global scope and filling — works outside Filament too | 🟢 |
| F-27 | **Visual identity: color** | organization → *Visual identity* | `admin` | choose the color and open `/app/{org}`: the whole panel wears its color, and `/admin` **does not** change | 🔵 |
| F-28 | **Visual identity: logo** | same | `admin` | the logo appears on the `/app` lock screen instead of the base image | 🔵 |

### Administration

| # | Feature | Where | Who can access | How to check | Test |
|---|---|---|---|---|---|
| F-29 | Users | `/admin/users` | `admin` | CRUD, with **mandatory** role on creation | 🟢 |
| F-30 | AI agent catalog | `/admin/agentes-ia` | `admin` | prompt, provider, model, tools and guardrails are **data**, editable without deploy | 🟢 |
| F-31 | Onboarding authoring | `/admin/onboarding-flows` | `admin` | checklists and tours; consumption is in the business panel | 🔵 |
| F-32 | Filled dashboard | `/admin` | `admin` | 6 widgets over the data the panel already has | 🔵 |

### Infrastructure

| # | Feature | Where | Who can access | How to check | Test |
|---|---|---|---|---|---|
| F-33 | Health checks | `/infra/health-check-results` | `infra` | database, cache, queues, scheduler, debug mode and local AI. **Opens empty until `php artisan health:check` runs** | 🔵 |
| F-34 | Backups | `/infra/backup-runs` | `infra` | history and health per destination | 🔵 |
| F-35 | Queues | `/infra/queue-monitors` | `infra` | pending, failed and history — of any driver | 🔵 |
| F-36 | Logs | `/infra/logs` | `infra` (`ver-logs`) | reading and searching by channel. **No delete button**: a trail is evidence | 🔵 |
| F-37 | Change auditing | `/infra/audits` | `infra` | who changed what, field by field | 🔵 |
| F-38 | Access trail | `/infra/authentication-logs` | `infra` | logins, IP and device | 🔵 |
| F-39 | Command center | `/infra/command-center/commands` | `infra` (`command-center:access`) | **pre-approved** commands in `config/command-center.php`, with history | 🔵 |
| F-40 | Pulse | `/infra/pulse` | `infra` | real-time performance. Needs `pulse:check` to have data | 🔵 |
| F-41 | Dependency graph | `/infra/dependency-graph` | authenticated in `/infra` | map of models, relations, resources and panels | 🔵 |
| F-42 | Composer releases | `/infra/composer-release-packages` | `infra` | warns of new version. **Informational — never updates anything.** Sync is a job: without worker, the screen is empty | 🔵 |
| F-43 | AI runs | `/infra/execucoes-ia` | `infra` (`ver-ai-tasks`) | ledger with cost and tokens per run | 🔵 |
| F-44 | Clear caches | `/infra` topbar | `infra` | `cache`, `config`, `view` and `modelCache` together | ⚪ |
| F-57 | Grouped exceptions | `/infra/exceptions` | `infra` | by type and frequency, with a menu badge. Retention (`KIT_RETENCAO_EXCECOES_DIAS`) only happens **with the scheduler running** | 🟢 |
| F-58 | Mail trail | `/infra/mail-logs` | `infra` | every e-mail sent. It stores the **body** — including the invitation's acceptance link | 🟢 |
| F-59 | Recycle bin | `/infra/recycle-bin` | `infra` | restores what was deleted with `SoftDeletes`. Lists **only** the models declared in `models()` in `InfraPanelProvider` | 🟢 |

### Productivity and UI

| # | Feature | Where | Who can access | How to check | Test |
|---|---|---|---|---|---|
| F-45 | ⌘K search | topbar of the three | authenticated | records, screens, pages and "Create X" actions — **all scoped by permission** | ⚪ |
| F-46 | Animated count badges | sidebar | authenticated | the count comes from `getEloquentQuery()`; zero doesn't become a badge | 🟢 |
| F-47 | Notification center | bell | authenticated | tabs and categories; real-time with Reverb, otherwise 30s polling | ⚪ |
| F-48 | Panel switch | user menu | whoever accesses more than one | goes straight to the chosen panel | 🔵 |
| F-49 | **Light/dark theme** | top switch | anyone | screens follow `prefers-color-scheme` and the switch; choice persists | 🔵 |
| F-50 | Resizable columns | any table | authenticated | width adjustable, remembered in the session | ⚪ |
| F-51 | Environment indicator | topbar | anyone | `local`/`staging` badge; hides in production | 🔵 |
| F-52 | Branded error pages | 403, 404, 419, 500, 503 | — | with the panel's look, in Portuguese (pt-BR) | 🔵 |
| F-60 | **Language switcher** | topbar of the three and login screens | anyone | only shows up with **two** locales in `kit.idiomas`; translates Filament and the packages, **not** the kit's labels | 🟢 |
| F-61 | **Attachments and media** | Projects form and table | whoever reaches the resource | upload, `anexos` collection, `miniatura` conversion and a table lightbox. The attachment inherits the record's own organization scope | 🟢 |

### AI

| # | Feature | Where | Who can access | How to check | Test |
|---|---|---|---|---|---|
| F-53 | Assistant chat | corner of **every** `/app` screen | authenticated | streaming; renders empty without user | ⚪ |
| F-54 | Chained guardrails | — | — | budget, prompt injection, local classifier, PII redaction and sensitive-output filter. **Fail-closed** | 🟢 |
| F-55 | Run ledger | `/infra/execucoes-ia` | `infra` | every call becomes a row with cost and tokens | 🟢 |
| F-56 | Local inference | `docker compose --profile ai up -d` | — | llama.cpp; or switch `AI_PROVIDER` to SaaS | ⚪ |

### What the roadmap **does not** cover on its own

Some features depend on something outside the process, and no test replaces it:

| Feature | Depends on | Without this |
|---|---|---|
| F-57, F-58 (trail retention) | the scheduler (`schedule:work`) | the exceptions and mail tables grow with no ceiling; the deadline in `config/kit.php` stays merely declared |
| F-15…F-20 (e-mail delivery) | a real `MAIL_MAILER` **and** a worker (`QUEUE_CONNECTION=database`) | the invitation is saved and the queue fills; nothing leaves |
| F-33 (health checks) | a run of `php artisan health:check` | the screen opens **empty**, with no state explaining — the dashboard widget warns, the resource page doesn't |
| F-35, F-42 (queues and releases) | a worker | the Composer sync job stays in the queue: F-42 shows "no records" and F-35 counts pending against an empty table |
| F-19 (reminders) | the scheduler (`schedule:work`) | the command is never called |
| F-34 (backups) | destination configured in `config/backup.php` | the screen opens empty |
| F-40 (Pulse) | `pulse:check` running | the screen opens with no data |
| F-53, F-56 (AI) | llama.cpp or an API key | the assistant answers unavailable |

The first three are solved by `composer dev` in development: it brings up server, queue and Vite together.

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
composer test         # pint + phpstan + filacheck + the whole suite
composer test:kit     # only the kit's tests (the foundation)
composer lint         # formats the code
composer filament:check   # only the Filament-specific lint (FilaCheck)
composer refactor:preview # what Rector would rewrite (dry-run) — OUTSIDE composer test
composer refactor:apply   # applies Rector's rewrite — OUTSIDE composer test
php artisan kit:install --force   # reinstalls from scratch (deletes the SQLite file) and asks again
php artisan kit:install --no-custom   # installs without asking anything
php artisan kit:update            # brings in improvements from a new kit version
php artisan kit:tenancy           # turns on multi-tenancy (opt-in)
```

### FilaCheck: the lint that only knows Filament

`composer filament:check` runs `laraveldaily/filacheck` — 17 rules that Pint and PHPStan have no
way of having: a deprecated Filament API method, the wrong action namespace, a call that changed
between versions. It runs inside `composer test` along with pint and phpstan, so CI fails on the
same things your machine does.

When it was adopted it found **7 pre-existing problems** in the kit itself — six deprecated test
methods and one `ImageColumn::size()` — all fixed.

### Rector: major upgrades, not linting

The kit has **four** quality tools, on four axes — and only **three** are in the gate:

| Tool | Axis | On finding a problem | Runs |
|---|---|---|---|
| **Pint** | style | **fixes it** | always (gate) |
| **PHPStan** + larastan | types | reports | always (gate), **level 7** |
| **FilaCheck** | Filament's API | reports | always (gate) |
| **Rector** | code rewriting | **changes semantics** | **on demand** |

`composer refactor:preview` and `composer refactor:apply` are **not** part of `composer test` — and
that is deliberate.

**What Rector is for here: major upgrades.** Laravel 13 → 14, PHP 8.4 → 8.5. The `rector.php` at the
root ships with **no set enabled**, and carries, in a comment block, which set to turn on for each
case. The flow is: uncomment the set → `composer refactor:preview` → read the whole diff →
`composer refactor:apply` → `composer test` → turn the set off again.

**Why it stays out of the gate — it was measured, not opined.** With Laravel's quality sets turned
on, Rector would rewrite **103 files** in this project. The three biggest reasons:

| Rule | Files | What it proposes |
|---|---:|---|
| `EloquentMagicMethodToQueryBuilderRector` | 35 | `User::find()` → `User::query()->find()` |
| `AddClosureVoidReturnTypeWhereNoReturnRector` | 26 | `: void` on closures |
| `AppToResolveRector` | 21 | `app()` → `resolve()` |

Those are style opinions, not corrections. In a kit whose product **is readable example code**,
`User::find()` and `app()` are the idiom the ecosystem reads without pausing.

And there is one case that settles it. `CarbonToDateFacadeRector` proposes, in `InfraPanelProvider`:

```diff
- Carbon::now()->subDays(...)
+ Date::now()->subDays(...)
```

And that **breaks**, for three verifiable facts:

1. `now()` **is** `Date::now()` — `Illuminate/Foundation/helpers.php:623`
2. The kit calls `Date::use(CarbonImmutable::class)` — `KitServiceProvider.php:57`
3. `FilamentExceptionsPlugin::modelPruneInterval()` requires a **mutable** `Carbon`

PHPStan at level 7 **already reported exactly this error** when the code used `now()`. The explicit
`Carbon::now()` is the fix — and Rector would undo it.

> **A quality tool that reverts another one's fix is not a gate, it's a dispute** — and the build
> would start depending on which of the two ran last.

`tests/Kit/QualidadeDeCodigoTest.php` pins this down: it fails if Rector enters `composer test`, or
if a quality set is turned on.

**Upgrading Filament is a different tool.** **There is no Filament rule in
`driftingly/rector-laravel`** — searching for "filament" in the package returns zero. That's not a
gap: Filament ships its **own** tool, also based on Rector.

```bash
composer require filament/upgrade --dev -W
vendor/bin/filament-vN     # N = the target major
```

It is kept in lockstep with the framework — whoever writes the rules is whoever breaks the API.

The full reading on the four tools is in
[`wikis/qualidade-de-codigo.md`](wikis/qualidade-de-codigo.md) (pt-BR).

### The kit's tests

The kit ships its own suite, isolated in `tests/Kit/` — access to the three panels, infra and admin screens standing up, foundation invariants (uuid, gates, auditing) and the AI layer's contract.

It's kept apart from yours on purpose: after a `kit:update` you want to know whether the **foundation** is still intact, without waiting on your business suite.

```bash
composer test:kit                     # parallel — ~3 min
composer test:kit:serial              # serial, to investigate a failure
php artisan test --testsuite=Feature  # only YOUR tests
```

**It runs in parallel by default.** Measured on this suite: **12m26s → ~3min** (20 cores), same cases and
same assertions. Each worker has its own database, because `phpunit.xml` uses SQLite `:memory:`, which
is per process.

If a failure only appears in parallel, it's a sign of a test that depends on order or shared
state — `composer test:kit:serial` isolates that, and the difference between the two is the diagnosis.

> **Why `--testsuite` and not `--group=kit`**: `pest-plugin-browser` spins up Playwright already at
> **collection**, when parsing any file with `visit()` — before any group filter is consulted. On a
> freshly installed project, without the browsers downloaded, `--group=kit` dies with
> `PlaywrightNotInstalledException` without running a single test.

> **Extra argument needs `--`**: `composer test:kit --parallel` is silently swallowed by Composer;
> what works is `composer test:kit -- --parallel`. Since parallel is already the default, you don't
> need this — but it's good to know for any other flag.

Your tests go in `tests/Feature` and `tests/Unit`, as usual — the kit never touches them.

### How tests are thought out: SFDIPOT sweep

Every new feature goes through an **SFDIPOT** sweep before it becomes a test case. The heuristic, created by James Bach, splits the system into seven perspectives so that no dimension is forgotten in the specification:

| Letter | Perspective | What it covers |
|---|---|---|
| **S** — Structure | Structure | Code, files, physical or logical components |
| **F** — Function | Function | What the software does, its features |
| **D** — Data | Data | What the system processes, stores or manipulates |
| **I** — Interfaces | Interfaces | Screens, APIs, integrations, inputs and outputs |
| **P** — Platform | Platform | Operating system, hardware or environment it runs on |
| **O** — Operations | Operations | How the user or administrator uses the system day to day |
| **T** — Time | Time | Concurrency, performance, history or the sequence of events |

The benefit is in not deriving tests only from the "happy path". What slips through is rarely one more case — usually it is an entire dimension (data, platform, time, operations) that nobody remembered to cover. The sweep forces this review into the plan before the code exists.

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
| 13 | **[Panel languages](#the-language-switcher)** | `config/kit.php` → `idiomas` (a list of locales; with only one, the switcher doesn't show) | — |
| 14 | **[Trail retention](#retention-the-number-is-the-intent-the-scheduler-is-the-execution)** | `KIT_RETENCAO_EXCECOES_DIAS` / `KIT_RETENCAO_EMAILS_DIAS` in `.env` | — |
| 15 | **[Media disk](#attachments-and-media)** | `MEDIA_DISK` in `.env` (`local` by default — private, served through a signed URL) | `php artisan kit:midia-privada` migrates media already written to a public disk |

The last ten are not asked because they are **code or screen data**, not a value that fits in a terminal prompt. The installer lists them in the final summary, each with its file.

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
4. **Offers a temporary branch** (`kit-update/v0.16.0`) so yours doesn't get dirty.
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
| `--tag=v0.16.0` | compare against a specific version |
| `--from=v0.15.0` | tell it which version the project started from (when `config/kit.php` doesn't know) |
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

The kit's tags go into a namespace of their own (`kit-v*`). That matters: a `git fetch kit --tags` would bring `v0.15.0`, `v0.16.0`… into your project and collide with **your** versions later.

```bash
git fetch --no-tags kit 'refs/tags/*:refs/tags/kit-*'
git tag -l 'kit-*'      # kit-v0.15.0, kit-v0.16.0, ...
```

Then, at each version, see what changed and bring over only what matters:

```bash
# 1. overview between your version and the new one
git diff kit-v0.15.0..kit-v0.16.0 --stat

# 2. the diff of the kit's "glue" (ignore what you already rewrote)
git diff kit-v0.15.0..kit-v0.16.0 -- app/Providers app/Filament/Concerns \
        app/Filament/Spotlight app/Traits resources/views/errors config/kit.php

# 3. bring it over file by file, reviewing
git checkout kit-v0.16.0 -- resources/views/errors
git checkout kit-v0.16.0 -- app/Filament/Concerns/BadgeContagemNavegacao.php
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
| [bezhansalleh/filament-exceptions](https://packagist.org/packages/bezhansalleh/filament-exceptions) | exceptions grouped by type and frequency, with retention |
| [tapp/filament-maillog](https://packagist.org/packages/tapp/filament-maillog) | a trail of every e-mail sent |
| [promethys/revive](https://packagist.org/packages/promethys/revive) | the recycle bin: restores records deleted with `SoftDeletes` |

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
| [bezhansalleh/filament-language-switch](https://packagist.org/packages/bezhansalleh/filament-language-switch) | language switcher on the three panels and on the login screens |

### Data and services

| Package | What for |
|---|---|
| [filament/spatie-laravel-settings-plugin](https://packagist.org/packages/filament/spatie-laravel-settings-plugin) | settings pages in the panel |
| [spatie/laravel-settings](https://packagist.org/packages/spatie/laravel-settings) | the persisted settings behind them |
| [filament/spatie-laravel-media-library-plugin](https://packagist.org/packages/filament/spatie-laravel-media-library-plugin) | the media layer (uploads, collections, conversions) in the form, table and infolist components |
| [mike-bronner/laravel-model-caching](https://packagist.org/packages/mike-bronner/laravel-model-caching) | automatic caching of Eloquent queries |
| [predis/predis](https://packagist.org/packages/predis/predis) | pure-PHP Redis client (no extension needed) |
| [laravel/reverb](https://packagist.org/packages/laravel/reverb) | WebSocket for real-time notifications |

> **Engines under the plugins**, installed as dependencies (you don't declare them, but they're what actually runs): `spatie/laravel-permission` (Shield), `spatie/laravel-health` (the checks), `spatie/laravel-activitylog` (the activity log), `spatie/laravel-medialibrary` (the attachments) and `livewire/livewire` (all of Filament).

### Model Caching

The kit applies the `App\Traits\ModeloCacheavel` trait to models that have a Resource on the `/app` panel — currently `User`, `Convite` and `Projeto`. The `mike-bronner/laravel-model-caching` package caches Eloquent queries when `MODEL_CACHE_ENABLED=true`.

- The default is `false` (`MODEL_CACHE_ENABLED=false` in `.env.example`).
- To turn it on, set `MODEL_CACHE_ENABLED=true` and use `MODEL_CACHE_STORE=model-cache` (Redis store configured in `config/cache.php`).
- Invalidation is automatic: `save`, `update` and `delete` clear the model's cache.
- The `/admin` and `/infra` panels remain **without** model caching by default, reducing the risk of stale data on administrative screens.

```bash
php artisan modelCache:clear      # clears the model cache
```

### Development (`require-dev`)

| Package | What for |
|---|---|
| [pestphp/pest](https://packagist.org/packages/pestphp/pest) + [pest-plugin-laravel](https://packagist.org/packages/pestphp/pest-plugin-laravel) | the test suite |
| [phpunit/phpunit](https://packagist.org/packages/phpunit/phpunit) | the engine under Pest |
| [larastan/larastan](https://packagist.org/packages/larastan/larastan) | static analysis (`composer types:check`) |
| [laravel/pint](https://packagist.org/packages/laravel/pint) | formatting (`composer lint`) |
| [laraveldaily/filacheck](https://packagist.org/packages/laraveldaily/filacheck) | Filament-specific lint (`composer filament:check`) |
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

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
| Test files | **94** |
| PHPStan | **level 7**, zero errors |
| FilaCheck | **17** rules, all passing |

| Documentation | |
|---|---:|
| Reference documents (`wikis/`) | **9** |
| Specified features (`wikis/specs/`) | **28** |
| Project rules for AI agents (`.ai/rules/`) | **13** |

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

**Front door**
- **Welcome page on the `/` route**, replacing Laravel's default welcome: one card per panel plus
  what `kit:install` customised ([details](#the--route-is-public-and-shows-no-secrets))

**Administration and security**
- Shield (roles and permissions with a UI) on top of spatie/laravel-permission
- Breezy: user profile, avatar, 2FA and passkeys
- Auth Designer: two-column login screen (swap the artwork in `public/images/auth/login.svg`)
- **Optional open sign-up** (off by default): registration without an invitation on `/app`, with a single role, manual approval and e-mail verification — each behind its own key ([details](#open-registration-and-approval))
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

## The `/` route is public and shows no secrets

[![Welcome page on the / route: three cards for the /app, /admin and /infra panels, plus two sections with what kit:install customised](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/boas-vindas.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/boas-vindas.png)

Instead of Laravel's `welcome.blade.php`, the root serves `App\Filament\Pages\BoasVindas`: one
card per panel (`/app`, `/admin`, `/infra`) and an infolist with what the installation
customised — name, colour, tenancy, retention windows, kit version.

It is **anonymous**, like the page it replaces, which is why the list of what it does **not**
show matters: the admin's e-mail, name and password, the database host and user, the repository
URL, `app.env`, `app.debug`, `app.url` and the mail configuration. A test plants a sentinel in
each of those values and asserts it is absent from the HTML — alongside an `assertOk()`, because
otherwise a 500 would pass every line by accident.

The "show everything outside production" alternative was deliberately rejected: security that
depends on `APP_ENV` being right is not security.

The route carries the `panel:app` middleware, and that is not decoration — it is the alias for
`SetUpPanel`, which boots the panel and therefore brings in Filament's stylesheet, the project
palette and the theme switcher. Measured: `@filamentStyles` alone does not bring the stylesheet
and the page renders amber even with `KIT_COR_PRIMARIA=Violet`. The middleware authenticates
nobody.

```php
// routes/web.php
Route::get('/', BoasVindas::class)->middleware('panel:app')->name('boas-vindas');
```

## Open registration and approval

Invitation is the kit's default door. The second door — **open sign-up**, with no invitation —
exists, and it **ships off**:

```dotenv
KIT_REGISTRO=false                      # the public door
KIT_REGISTRO_APROVACAO_MANUAL=false     # born pending until someone approves?
KIT_REGISTRO_VERIFICAR_EMAIL=false      # require a verified e-mail on /app? (also editable in the UI)
```

With `KIT_REGISTRO=false`, `/app/register` answers **only** to whoever brings a valid invitation
token — with no token it refuses and sends the visitor to the login. That is what the kit always
did, and nothing in this section happens until you flip the key.

### Both doors share one screen

`/app/register` picks the path by the **absence** of the `token` parameter:

| URL | With `KIT_REGISTRO=false` | With `KIT_REGISTRO=true` |
|---|---|---|
| valid `?token=` | invitation acceptance | invitation acceptance (same) |
| invalid, expired or used `?token=` | refuses → login | **refuses → login** (same) |
| no `token` | refuses → login | sign-up form |

The second row is deliberate: an invalid token **never** falls through to open sign-up. If it
did, `?token=anything` would be a second entrance to the public door — precisely the one that
skips the refusal's rate limit and the generic message that does not reveal whether the token
does not exist, expired or was already used.

### What turning `KIT_REGISTRO=true` makes ripple

| Where | What changes |
|---|---|
| `/app/register` with no token | shows the form instead of refusing |
| `/app/login` | starts offering the "Create account" link (hidden before, because it led to a screen that refuses) |
| the role a new account gets | **only** `panel_user` — no other profile, and 403 on `/admin` and `/infra` |
| `/admin/organizacoes` (with tenancy) | an *"Accepts public sign-up"* field appears on each organization |
| the users screen (`/admin` and `/app`) | gains the **Status** column, the *"Pending only"* filter and the **Approve** action |
| the `autenticacao` log channel | starts recording every sign-up and every approval, with the e-mail masked |

### The role a new account gets, and nothing beyond it

Whoever comes through this door receives **one single** role: `panel_user`, the basic profile of
the business panel. Not `admin_app`, and no reach into `/admin` or `/infra` — both answer
**403**. Administrators adjust roles afterwards, on the users screen, which is where that
decision belongs.

The assignment happens in a single place (`App\Support\RegistroAberto::papel()`), and it holds
for anyone calling registration from outside the screen too — a command, a job, a seeder.

### Manual approval: pending means no panel at all

With `KIT_REGISTRO_APROVACAO_MANUAL=true` the account is born **pending**:

- it gets no role;
- the session the sign-up opened is ended right away, and the person returns to the login with a
  message saying the account awaits release — instead of getting a 403 right after a sign-up
  that worked;
- `/app`, `/admin` and `/infra` all answer **403** while it is pending.

To release it, open the users screen, filter by *"Pending only"* and use the **Approve** action.
Approving grants the business panel role (inside the current organization when tenancy is on)
and is idempotent — clicking twice does not duplicate the role. The action requires the
**edit user** permission, so the ordinary `/app` user never sees it.

> While an account is pending it has **no role**, and the edit form knows that: the *Roles*
> field, normally required, stops being required in that case. Without the exception, editing a
> pending account would be impossible to save, and the only way out would be granting a role by
> hand — which grants access without going through the approval.

### Sign-up per organization (multi-tenancy)

With multi-organization on, **two** conditions hold together: the installation allows sign-up
(`KIT_REGISTRO=true`) **and** each organization opts in, through the *"Accepts public sign-up"*
field on its own screen. Every organization defaults to **no** — flipping the global key does not
open sign-up on any existing organization without someone deciding.

The address carries the slug:

```text
/app/register?org=acme
```

With no `?org`, an unknown slug, an **inactive** organization or sign-up **off** for it, the
screen returns **the same** refusal — a visitor cannot tell which condition failed. Which also
means: on a multi-organization installation, publishing `/app/register` without `?org=` leads
people to the refusal; publish the organization's link.

This is not the same as *creating* an organization: signing up **into** one is not creating it,
and creating organizations remains the job of whoever administers the installation, via `/admin`.

### E-mail verification (optional)

Requires a confirmed e-mail address to enter `/app`. **Editable in
`/admin/configuracoes-do-kit` → Registro tab**, and the stored value applies on the **next
request** — no deploy. `KIT_REGISTRO_VERIFICAR_EMAIL` still exists: it seeds a fresh installation
and is the fallback, like the other settings on that screen.

> Up to v0.19.3 this key worked **only** through `.env`, and the screen said so. The reason was
> real: Filament pins the e-mail-verified middleware into the route's middleware array at
> registration time, so the decision was made at boot and a toggle on the screen saved without
> taking effect. The fix was to take the *decision* out of the route array and put a *decider*
> there instead — `App\Http\Middleware\ExigirEmailVerificado`, which asks on every request. As a
> consequence, the confirmation screen now always exists.

**Read this part before turning it on with people inside — one click is now enough.** The
requirement applies to **every** `/app` user, not only to newly registered ones, and it does
**not** depend on open sign-up being enabled: anyone without `email_verified_at` is taken to
the confirmation screen. On a clean installation that hits nobody, because the paths the kit uses
to create users already fill the column — the admin seeder, the factory, the demo seeder,
invitation acceptance and `kit:admin`. The one that does **not** is manual creation through the
users screen.

To mark an existing base as verified before turning it on:

```bash
php artisan tinker --execute 'App\Models\User::whereNull("email_verified_at")->update(["email_verified_at" => now()]);'
```

**People who came through an invitation are never affected**, and the reason is worth knowing:
`Convite::aceitar()` fills `email_verified_at` on purpose — the token arrived at that person's
address, so possession is already proven, and asking for the same proof twice is friction with no
gain. Filament only sends the confirmation request to users who have not verified yet, so an
invited user never receives that e-mail.

### The rate limit

Submitting the form uses Filament's own limit: **2 attempts per IP** and 2 per e-mail address per
window — the same one invitation acceptance already used. The refusal for a missing invitation has
its own limit (5 per 10 minutes per IP), which protects the **log file** against an anonymous
loop without changing what the person sees.

### Where this lives in the code

| What | Where |
|---|---|
| the three options, read in one place | `app/Support/RegistroAberto.php` |
| the screen, with both modes | `app/Filament/Pages/Auth/RegistroPorConvite.php` |
| the pending barrier | `App\Models\User::canAccessPanel()` — first statement |
| the release | `App\Models\User::aprovar()` |
| column, filter and approve action | `app/Filament/Concerns/AprovacaoDeCadastro.php` |
| the per-organization field | `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php` |

> **If you are wiring these options into a Settings screen**: `App\Support\RegistroAberto` is the
> single read point. Swapping `config()` for Settings means rewriting the body of three methods in
> that file — nowhere else in the project reads those keys, and a test fails if anything starts to.

## Social login: four providers (opt-in, one at a time)

A second way in, next to the password: the **Sign in with…** buttons below the login form on all
three panels. Each provider ships **turned off**, is turned on **individually**, and once on does
exactly one thing — authenticate someone who **already has an account**.

| Provider | Socialite driver | Redirect URI | How the kit confirms the e-mail is verified |
|---|---|---|---|
| **Google** | `google` | `/auth/google/callback` | `email_verified` field in the payload |
| **GitHub** | `github` | `/auth/github/callback` | Socialite already hands over only the `primary` + `verified` address, or nothing — presence is the proof |
| **LinkedIn** | `linkedin-openid` | `/auth/linkedin-openid/callback` | `email_verified` from the OpenID userinfo |
| **X** (formerly Twitter) | `x` | `/auth/x/callback` | X only ever returns `confirmed_email` — presence is the proof |

**Facebook and Discord were left out**, and not by oversight. See
[Facebook and Discord: why they are not here](#facebook-and-discord-why-they-are-not-here) for what
it would take to include them.

### What social login does, and what it deliberately does not

True for all **four** providers, without exception:

| | |
|---|---|
| **Authenticates** an existing account matching the e-mail the provider returns | ✅ always, when enabled |
| **Creates** an account for someone who has none | ❌ only with open registration on, which ships off |
| Accepts an **unverified** e-mail from the provider | ❌ never — it refuses and records the reason |
| Bypasses **two-factor** | ❌ never — a confirmed-2FA account still hits the challenge |
| Stores the access token or `refresh_token` | ❌ nothing is stored |
| Adds a new column to `users` | ❌ none; the link is the verified e-mail |
| Marks a created account as **e-mail verified** | ✅ yes — the provider already proved it, and asking again would be the same proof twice |

The second row is the important one, and it is not timidity: **the invitation is the kit's only
front door**. The callback example in the Laravel Socialite documentation is
`User::updateOrCreate()` — copied here, it would turn anyone with an account on **any** of the
providers into a user of your system, bypassing the invitation, the verification and the role
assignment. That is an authorization hole, not a convenience. If you **do** want sign-up through
social login, turn open registration on: the kit then creates the account and takes the person to
their own profile screen to fill in what is missing.

And remember the rest of the kit: **an account with no role opens no panel**
(`User::canAccessPanel()`). Someone arriving through social login needs a role like everybody else.

### Turning a provider on, in four steps

The steps are the same for all four; only the place where you create the OAuth app changes. You can
do everything through `.env` **or** through `/admin/configuracoes-do-kit` → the **Login** tab
(a value saved on the screen wins over `.env` at runtime).

**1. Create the OAuth app at the provider** and register the redirect URI — your `APP_URL` plus the
path from the table above:

| Provider | Where to create it | What to ask for there |
|---|---|---|
| **Google** | [console.cloud.google.com](https://console.cloud.google.com) → *APIs & Services* → *Credentials* → *OAuth client ID*, type **Web application** | nothing beyond the defaults |
| **GitHub** | [github.com/settings/developers](https://github.com/settings/developers) → *OAuth Apps* → *New OAuth App* | nothing to tick; the kit requests the `user:email` scope in code, and that scope is what makes verification confirmable |
| **LinkedIn** | [linkedin.com/developers](https://www.linkedin.com/developers) → *Create app* → *Products* tab | **enable the _Sign In with LinkedIn using OpenID Connect_ product**. Without it the provider does not return `email_verified`, and the kit refuses every login |
| **X** | [developer.x.com](https://developer.x.com) → *Projects & Apps* → *User authentication settings* | type **Web App**, **OAuth 2.0**, and the `users.read` and `users.email` scopes |

An example URI to register:

```text
https://your-domain.com/auth/github/callback
http://localhost:8000/auth/github/callback     # for development
```

That path is not a choice: it lives in `config/services.php` as a **relative** path, on purpose, so
it follows the `APP_URL` of each environment without one more variable to forget.

**2. Write the provider's three keys into `.env`:**

```dotenv
# Google
KIT_SOCIALITE_GOOGLE=true
GOOGLE_CLIENT_ID=1234567890-abc.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-your-secret

# GitHub
KIT_SOCIALITE_GITHUB=true
GITHUB_CLIENT_ID=Iv1.abc123
GITHUB_CLIENT_SECRET=your-secret

# LinkedIn (linkedin-openid driver)
KIT_SOCIALITE_LINKEDIN=true
LINKEDIN_CLIENT_ID=86abc123
LINKEDIN_CLIENT_SECRET=your-secret

# X (formerly Twitter)
KIT_SOCIALITE_X=true
X_CLIENT_ID=your-client-id
X_CLIENT_SECRET=your-secret
```

**3. Clear the config** (`php artisan config:clear`) and reload the login screen.

**4. Confirm the button showed up.** If it did not, it is one of the two conditions below.

> **Through the screen instead of `.env`**: `/admin/configuracoes-do-kit` → **Login** has one
> section per provider. Turning the switch on **opens** the *Client ID* and *Client Secret* fields
> for that provider — and only that one. The *Client Secret* is stored **encrypted**, is never
> displayed back and does not appear in the page source; leaving the field blank **keeps** whatever
> was already stored.

### The button only shows with EVERYTHING filled in — per provider

There are **two** conditions, in conjunction, and they fail for different reasons:

- that provider's switch on — off is a choice made by whoever installed;
- its `client_id`, `client_secret` and `redirect` **all filled in** — an empty credential is an
  oversight by whoever configured.

A switch on with an empty `client_secret` keeps the button off the air, on purpose: a button leading
to a non-existent OAuth is a promise the screen cannot keep.

**And turning it off takes the ROUTE down, not just the button.** With a provider unavailable,
`/auth/{provider}/redirect` and `/auth/{provider}/callback` answer **404** — only that provider's,
the others stay up. Hiding the button would be no barrier at all: the URL is fixed, public and
well known.

**A provider outside the list answers 404 without even reaching the controller.**
`/auth/facebook/callback`, `/auth/discord/callback` or any other segment return 404 because the
route parameter is typed as `App\Support\ProvedorSocial` — the allow-list *is* the enum, and the
router consults it.

Each switch also **fails closed**: `false`, `0`, `off`, `no`, empty and any unrecognizable value
keep it off. Only `true` and `1` turn it on.

### The login screen footer

The same configuration brings a text footer to the bottom of the login screen on all three panels:

```dotenv
KIT_LOGIN_RODAPE="Fiotec · All rights reserved"
```

Empty (or whitespace only) = no footer, no empty strip.

It is **text, not HTML**, and the value is escaped on output. The login screen is public and
unauthenticated: raw HTML coming from an editable field there would be stored XSS with the worst
possible reach — the screen everyone comes through. If you need a link in the footer, the answer is
a structured field (text + URL, validated), not a free-form HTML field.

### Unverified e-mail: why we refuse, and how each provider proves it

The link to the kit account is made **by e-mail**, compared case-insensitively and trimmed. That is
simple and costs no new column — but it carries a known risk: if the provider returned an
**unverified** e-mail, creating an account at that provider with somebody else's address would be
enough to get into their account. With the kit's registration closed — the default — that is
precisely the main path, not an edge case.

So the kit **requires positive proof** from every provider. Absent, false, or anything that is not
clearly true ⇒ **refusal**, with a notice on screen and the `email_nao_verificado` reason in the
log. Fails closed, always.

What changes from provider to provider is **where** the proof lives, and the difference is large:

- **Google** — reports `email_verified` in the payload (plus a `verified_email` alias). The kit
  reads it and requires true.
- **LinkedIn** — the OpenID Connect userinfo carries `email_verified`. That is why the kit uses the
  `linkedin-openid` driver and not the legacy `linkedin`: the legacy one reports **no verification
  at all**, and its scopes were deprecated by LinkedIn itself.
- **X** — has no verification field, because it does not need one: X only ever returns
  `confirmed_email`, that is, an address it has already confirmed. **The presence of the e-mail is
  the proof.** If X returns no e-mail (an app without the `users.email` scope, or an account with no
  confirmed address), the kit refuses with the `email_ausente` reason.
- **GitHub** — presence as well, by a route worth understanding before you touch the scopes.
  Socialite queries `/user/emails`, picks the entry that is `primary` **and** `verified`, and
  **overwrites** the e-mail with it — or with **nothing**, if the query fails or no entry matches.
  There is no verification field in the payload, and none is needed: a filled e-mail already means
  `primary` + `verified`, and an empty one falls into the `email_ausente` refusal.

  This rests on **one** condition: the `user:email` scope. It is the driver's default, and the
  `scopes` key in `config/services.php` **merges** with the defaults rather than replacing them, so
  adding scopes is safe. What would break the guarantee is a `setScopes()` in the kit's code
  dropping `user:email` — GitHub would then hand over the **public profile** e-mail, unverified,
  silently. There is a test guarding exactly that; do not turn that scope off.

  > Earlier versions of this section said the kit re-ran the `/user/emails` query itself. It did,
  > and it was redundant: the reading of Socialite's code that justified the call was wrong. The
  > call was removed — the kit calls **no** provider API at all.

One consequence to know, for all of them: if the person **changes the e-mail** on the provider
account, the link is lost and they go back to signing in with a password.

### Known limitation: the destination is always the `/app` panel

The buttons appear on the login screens of **all three** panels, because the render hook is a
single one. But anyone arriving through social login lands on `/app`, even having clicked on
`/admin/login` or `/infra/login` — and a refusal also returns to the `/app` login.

This is not a security hole: the person is authenticated and their role still governs what they can
reach. It is navigation friction, recorded as an accepted limitation because carrying the origin
panel across the OAuth round trip is a new feature, not a fix to this one. Administrators and infra
operators normally sign in with a password; social login exists for the `/app` path.

### Facebook and Discord: why they are not here

The original requirement asked for both. Neither made it, each for a different reason.

**Facebook — there is no way to confirm the e-mail.** Socialite has the driver, and it works; what
does not exist is a field asserting that **that address** was confirmed. The `verified` field the
provider requests is **account** level, legacy, and absent from the Graph API version it uses; the
OIDC/Limited Login path returns claims without `email_verified`. Accepting Facebook would make the
assurance level of your login depend on **which button the person clicked** — and the weakest button
would be the vector. If you knowingly accept that risk, what is missing is: a case in
`App\Support\ProvedorSocial` whose verification branch states the assumption, the block in
`config/services.php` (key `facebook`) and in `config/kit.php`, and the three Settings properties.
**Read ADR-05 first** — it lists the alternatives that were considered and why each is worse.

**Discord — it is not a Socialite driver.** The official documentation supports Facebook, X,
LinkedIn, Google, GitHub, GitLab, Bitbucket and Slack; everything else comes from the community
catalogue at [socialiteproviders.com](https://socialiteproviders.com). Including it requires
`composer require socialiteproviders/discord` **and** registering a `SocialiteWasCalled` listener —
a new dependency and a second extension mechanism. The kit does not add dependencies on its own; if
you want it, that is the path, plus a case in the enum (Discord does expose a `verified` field in the
payload, so the barrier has something to stand on).

### Where the records go

Everything goes to the **`autenticacao`** channel (`storage/logs/autenticacao-*.log`), in the same
format as the rest of the kit — `[Class@method] message | key: value`, with the **e-mail masked**,
the `provedor` on every line and a readable `motivo` on each refusal:

| `motivo` | What happened |
|---|---|
| `falha_no_provedor` | invalid CSRF `state`, network down, or credential rejected by the provider |
| `email_ausente` | the provider returned no e-mail (on X, this is the missing-scope case) |
| `email_nao_verificado` | the e-mail is not verified at the provider |
| `conta_inexistente_registro_fechado` | there is no account and open registration is off |
| `conta_criada_por_login_social` | a new account was created (open registration on) |

No **`client_secret` ever appears** — not in a log, not on screen, not in an error message, and not
in the HTML of the configuration screen. And the messages returned to the visitor are deliberately
generic: telling which barrier refused hands reconnaissance information to anyone probing. The
reason stays in the log, for you.

Social login also lands in the `/infra` panel's **access trail** (who signed in, when, from where),
like any other login — with no configuration at all.

### The Google secret was stored in cleartext in the audit trail up to v0.19.3

**If you configured `GOOGLE_CLIENT_SECRET` through the `/admin/configuracoes-do-kit` screen on any
version between 0.19.2 and 0.19.3, rotate that secret in the Google console.**

Why: the audit trail's secret mask decides what to hide by consulting the
`ConfiguracoesDoKit::encrypted()` list, and the Google `client_secret` was not on that list. So
every save through the screen wrote the value **in cleartext** into the `old_values`/`new_values`
columns of the `audits` table — and the audit screen displays those columns for reading.

What this version does for you:

- **fixes the list**, which closes the leak going forward for all four provider secrets and the
  SMTP password at once (one list, three consumers: the read decryptor, the write encryptor and
  the trail's mask);
- **masks what is already stored**, in a migration that replaces the value with the same mask the
  trail uses today. The trail row is preserved — who changed it, when and from where stays on
  record; only the value that should never have been there goes;
- **warns in the log** (`configuracoes` channel) how many rows were masked, with the instruction
  to rotate.

Masking the trail does **not** undo the fact that the value was readable. That is why the rotation
is yours, and it is the one step the kit cannot take for you.

### Adding the next provider

The kit **does** have a provider abstraction now, and it is an enum:
`App\Support\ProvedorSocial`. The decision was made with four cases in hand, not one — which
revealed that the axis worth abstracting was not the redirect nor the button (identical across all
of them), but the **e-mail verification** (radically different in each).

The recipe for a fifth provider, deliberately short:

1. a new case in the enum, with `value` = the **Socialite driver name** (that same value is the URL
   segment and the key in both config files), plus the `rotulo()`, `icone()` and
   **`emailVerificado()`** branches;
2. a block in `config/services.php` and one in `config/kit.php` → `login`, and the keys in
   `.env.example`;
3. three properties in `App\Settings\ConfiguracoesDoKit`, a line each in `mapaDeConfiguracao()`, the
   `client_secret` in **`encrypted()`**, and the `add`/`addEncrypted` pair in a new migration under
   `database/settings/`;
4. an SVG partial in `resources/views/filament/auth/icones/`.

**No logic file changes**: the routes, the controller, the buttons blade and the Login tab of the
Settings screen all iterate `ProvedorSocial::cases()`. And the exhaustive `match` in
`emailVerificado()` **demands** the new branch — forgetting it does not pass static analysis.

**If the provider does not allow confirming e-mail verification, that is an architecture decision,
not a `?? true`.** It is what took Facebook off the list. Record the choice before writing the
branch.

> The full reasoning, with the rejected alternatives and the `vendor/` `file:line` behind every
> claim about Socialite, is in
> `wikis/specs/feat/mais-provedores-sociais/mais-provedores-sociais/`. The previous decision — **not**
> to abstract, with a single provider — is in
> `wikis/specs/feat/login-social-google/login-social-google/`, ADR-10.

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
            ->nonQueued()   // with no guaranteed worker running: queued, the
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

[![The Projeto listing on /app with the attachment column: circular thumbnails stacked on each record's row](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/app-projetos-anexos.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/app-projetos-anexos.png)

Look at the thumbnails stacked on the record's row: each one is served through a **signed URL**,
because the disk is private — the same file requested without the signature answers 403.

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

## CSV import and export

The mechanism is **native Filament 5**: `ImportAction`, `ExportAction`, the jobs, the batch and the
completion notification with a download button. The `imports`, `exports` and `failed_import_rows`
tables are already migrated, and the kit **writes no wrapper at all** around any of it. What it adds
are two base classes, a dedicated permission for each side, and the decision — resource by resource
— to turn them on or not.

![The import and export flow on /app: the Projeto listing with both buttons in the header, the export modal with one field per column, and the import modal with the sample CSV](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/fluxo-import-export.gif)

Both buttons live in the listing header, next to "New": no new screen, no route of their own — what
changes from resource to resource is only the permission each one requires.

### `ImportadorDoKit`: the organization boundary the package does not ship

`Importer::resolveRecord()` runs **inside the worker**. There is no panel and no route in the session
there, so `Filament::getTenant()` returns `null` and the `BelongsToTenant` global scope becomes a
**no-op** — `ImportCsv` restores `auth()->setUser()`, the **user**, and nothing restores the tenant.
Two consequences, both silent:

| CSV row | Without `App\Support\ImportExport\ImportadorDoKit` |
|---|---|
| with a key from **another** organization | UPDATE on someone else's record, no 403 and no log |
| new | born with a **null** `tenant_id` — invisible to everybody, including whoever imported it |

The fix has two ends. The **Action** captures the tenant in the request, where it exists
(`->options(['tenant_id' => Filament::getTenant()?->getKey()])`), and the base class uses it on both
ends: it scopes record resolution and it fills creation, standing in for the `creating` hook that has
no context down there.

And it **fails closed**: tenancy on + a model using `BelongsToTenant` + no `tenant_id` in the options
= the row is **refused** with `RowImportFailedException` (it lands in `failed_import_rows` and comes
out in the notification's failure CSV) and the reason is logged. Carrying on unscoped would be
exactly the defect the class exists to close.

### `ExportadorDoKit`: formula injection neutralized on every column

`preventFormulaInjection()` exists in Filament **per column**, and it is born **off**. A cell
starting with `=`, `+`, `-` or `@` becomes a formula when someone opens the CSV in Excel — and the
data that filled it came from a user form. `App\Support\ImportExport\ExportadorDoKit` applies the
neutralization to **every** column the subclass declares; that is why the subclass declares
`colunas()`, not `getColumns()`.

**Export has not a single line of tenant code, and that is the part worth understanding.** Its query
comes from the screen's table (`getTableQueryForExport()`), built in the request, where the global
scope has already applied `where tenant_id = X`; it is serialized **with** that `where` inside, and
that is what the job runs. Export isolation is **inherited**; import isolation is **built** — the
exact inverse. The full reasoning is in
[`wikis/arquitetura.md`](wikis/arquitetura.md#import-e-export-o-worker-perde-o-tenant-o-export-o-herda)
(pt-BR).

Both modals are Filament's own — the kit draws no screen here:

| Import | Export |
|---|---|
| [![The Projeto import modal, with the link to download a sample CSV and the file upload field](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/import-modal.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/import-modal.png) | [![The Projeto export modal, with one field per exporter column — Nome, Organização, Criado em and Atualizado em — each with a checkbox and an editable label](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/export-modal.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/export-modal.png) |
| **Download an example CSV file** builds the header from the importer's columns — that is where you can see, in practice, that `tenant` is not among them | One field per column declared in `colunas()`, each with a checkbox and an editable label: whoever exports picks the slice and renames the header, but cannot add a column the exporter never declared |

### A dedicated permission, and it is not optional

`import` and `export` are the **kit's addition** to Shield's 12 default methods, in
`config/filament-shield.php` → `policies.methods` — and in `single_parameter_methods` too, because
neither of them receives a record (outside that list Shield would generate
`import(User $user, Model $record)` in the policy, and the Action, which calls
`Gate::authorize('import')` with no record, would throw `ArgumentCountError`). They generate
`Import:{Model}` and `Export:{Model}` for every resource.

[![A role edit screen in Filament Shield, with the Import and Export checkboxes next to View Any, Create and Delete](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/admin-papeis-import-export.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/admin-papeis-import-export.png)

On the roles screen, `Import` and `Export` sit right next to `View Any`, `Create` and `Delete` — for
**every** resource, including the ones that never turned the Actions on. That is what lets you grant
or revoke each side per role, in `/admin` → Roles, without touching code.

They are necessary because **a Filament Action does not check policies on its own** — the vendor says
so in `Actions/Concerns/CanBeAuthorized.php`: the default authorization is `null`, i.e. allowed.
That is why every Action in the kit carries an explicit `->authorize('import')` or
`->authorize('export')`. Without that line, whoever can open the listing takes the whole listing
away.

> ⚠️ **Changed that config? Reseed.** The new permission does not exist in the database until
> `shield:generate` runs again, and the symptom is the Action **disappearing from the screen with no
> error at all**:
>
> ```bash
> php artisan db:seed --class=Database\Seeders\ShieldPermissionsSeeder
> php artisan db:seed --class=Database\Seeders\PapeisSeeder
> ```

### `panel_user` is born with neither of them

The subtraction lives in `PapeisSeeder::ehPermissaoDeImportOuExport()`, and it matches by **action
prefix** (`Import:` / `Export:`), not by a list of FQCNs — on purpose: **a new resource is born with
both outside the ordinary user without anyone having to remember to add it to any list.** The
criterion is what each one actually is: import is a **mass write**; export **takes the
organization's data out of the application** in a file. Whoever uses the business does that one
record at a time; whoever moves spreadsheets is whoever operates the organization. `admin_app` keeps
both, because it receives the panel's whole matrix — and granting it to `panel_user` is one click in
`/admin` → Roles, if that fits your case.

### Who has what today

| Panel | Resource | Import | Export | Why |
|---|---|---|---|---|
| `/app` | **Projeto** | ✅ | ✅ | the demo resource — it is the reference example for both |
| `/admin` | **AgenteIa** | ✅ | ✅ | configuration, no personal data |
| `/admin` | **Tenant** | — | ✅ | creating an organization from a CSV would skip provisioning: per-tenant roles, the first administrator, the visual identity. One spreadsheet row would become an organization nobody can reach |
| `/admin`, `/app` | **User** | — | 💤 commented out | the spreadsheet leaves with the e-mail of everybody who has access; and import would bypass invitation, e-mail verification and role assignment — the three pillars of access in the kit |
| `/admin`, `/app` | **Convite** | — | 💤 commented out | the invitee's e-mail |
| `/admin` | **Role** | — | — | a role is a code identifier, not spreadsheet data |
| `/infra` | **AiRun** | — | ✅ | a cost ledger; the question it answers is "how much did we spend" |

**Commented out** means the two lines **are already** in the Page file, commented, with the warning
of what turning them on exposes — the exporter is there, ready; it is one line to uncomment. The
decision is born **written down**, not forgotten: it is the convention `.ai/rules/filament.md`
demands of every new resource, because silent absence is not a decision — nobody goes back to
reconsider what was never written.

### The columns that are missing on purpose

Filament's generator infers columns from the database, and the kit strips three of them by hand. Do
not put them back:

| Class | Missing column | What it would hand over |
|---|---|---|
| `ConviteExporter` | `token`, `token_lembrete` | `Convite::aceitar()` validates the token and binds the user to the organization with the invite's role: a CSV with that column is a **spreadsheet of entry keys** |
| `AiRunExporter` | `request`, `response` | the full prompt and answer, from any organization — and `/infra` has no tenant in the route |
| `ProjetoImporter` | `tenant` | the generator creates `ImportColumn::make('tenant')->relationship()` for every FK; accepting it would let the **CSV pick the destination organization** and make the `ImportadorDoKit` boundary decorative |

The generator puts all of them back on `--force`. What guards the absence are the tests in
`tests/Kit/ImportExportTest.php`.

### No worker, nothing happens

Filament's import and export are **jobs**. The kit is born with `QUEUE_CONNECTION=database` in
`.env`; `composer dev` already starts a worker, and in production the `worker` service of docker
compose is what processes them. With the queue stopped, the file is accepted, the row lands in
`imports`/`exports` and the completion notification never arrives — a stopped queue shows up in the
**Jobs Monitor** in `/infra`.

### Tracking: no new table

`imports` and `exports` already record who asked, which importer, how many rows and when it
finished. What is **not** there is exactly what a leak audit asks — **which organization the file
came from** — because both tables belong to the package and have no `tenant_id`. That is what
`KitServiceProvider::configureRastroDeImportExport()` adds, on the **`tenancy`** channel: the subject
is organization crossing.

The two sides use different hooks because the package is asymmetric: import has real events
(`ImportStarted` / `ImportCompleted`), export has **none at all**, so the hook is the `Export` model
itself — `created` marks the request and the freshly filled `completed_at` marks the completion.

### Retention: 30 days, and the export pruning deletes the file

| Key | `.env` | Default |
|---|---|---|
| `kit.retencao.importacoes_em_dias` | `KIT_RETENCAO_IMPORTACOES_DIAS` | 30 |
| `kit.retencao.exportacoes_em_dias` | `KIT_RETENCAO_EXPORTACOES_DIAS` | 30 |

**30, and not the 14 of the exception and mail trails**: the history of a mass write is what answers
"who wrote this last week", and that question usually arrives after month-end closing.
`failed_import_rows` falls by cascade; **the export pruning deletes the FILE**, not just the row —
without that the disk grows forever with CSVs nobody can download any more, because the download
link is signed and the row that authorized it is gone.

Both schedules live in `routes/console.php` (02:20 and 02:30), as `Schedule::call` and not as
`model:prune`: Filament's `Import` and `Export` models **use the `Prunable` trait but never declare
`prunable()`**, so the command would throw `LogicException` — and there is no way to add the method
without editing `vendor/`. It is the same pattern already used by the mail-trail pruning. Zero or
negative turns that pruning off, and **the scheduler is what executes it**: with no
`php artisan schedule:work` (or the compose `scheduler` service) the number in the config is just
intent.

### Turning it on for a new resource

```bash
php artisan make:filament-importer Produto -G
php artisan make:filament-exporter Produto -G
```

Swap the generated `extends Importer` / `extends Exporter` for the kit's base classes (in the
exporter, rename `getColumns()` to `protected static function colunas()`), **delete the `tenant`
column** from the importer, and add the Actions to the listing Page's `getHeaderActions()`:

```php
ImportAction::make()
    ->importer(ProdutoImporter::class)
    ->authorize('import')
    ->options(fn (): array => ['tenant_id' => Filament::getTenant()?->getKey()]),

ExportAction::make()
    ->exporter(ProdutoExporter::class)
    ->authorize('export'),
```

Then **reseed both seeders** (`ShieldPermissionsSeeder`, then `PapeisSeeder`) and make sure a worker
is up. The full recipe, including what to do when the decision is *not* to turn it on, is in
[`wikis/receitas.md`](wikis/receitas.md#ligar-importexport-num-resource) (pt-BR).

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
| F-03 | Registration by invitation | `/app/register?token=…` | whoever has a valid token | without a token in the query, the screen refuses and goes to login (with `KIT_REGISTRO=false`, the default) | 🟢 |
| F-03a | Open sign-up (opt-in) | `/app/register` | anyone, with `KIT_REGISTRO=true` | the form shows up; whoever signs up gets **only** `panel_user` and hits 403 on `/admin` and `/infra` | 🟢 |
| F-03b | Sign-up approval (opt-in) | users screen → *Approve* action | whoever can edit users | with `KIT_REGISTRO_APROVACAO_MANUAL=true` the account is born pending and enters no panel at all | 🟢 |
| F-03c | E-mail verification (opt-in) | `/app/email-verification/prompt` | authenticated, with the requirement on (in the UI or in `.env`) | the route always exists — a kit middleware decides, per request; invited users are never blocked | 🟢 |
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
| F-62 | **Every screen in the panel has its own permission, and it is enforced** | `/admin/shield/roles` → *Pages* and *Widgets* tabs | `admin` | uncheck `View:Pulse` on the `infra` role: the screen answers 403 and the menu item is gone. Holds for the kit's 7 Pages and 24 Widgets **and** for the 7 screens that come from packages in `/infra` — except the three from the Command Center, see F-67 | 🟢 |
| F-63 | **Every action and every link in the kit has its own permission** | `/admin/shield/roles` → *Resources* and *Custom* tabs | `admin` | uncheck `Reenviar:Convite`: the *Resend* button leaves the invitations listing. The RelationManager ones (attach, detach, assign roles) likewise | 🟢 |
| F-64 | A new action or link **does not** ship open by forgetfulness | `tests/Kit/PermissoesDeAcoesTest.php` | — | add an `Action::make('x')` under `app/Filament/` and run the suite: the inventory case turns red naming the file | 🟢 |
| F-65 | **Welcome page at the root**, with what the installation customised | `/` | anonymous | open it unauthenticated: the three cards and the config show up, and no secrets — the test plants a sentinel in 8 values and asserts its absence | 🟢 |
| F-66 | The root inherits the project theme and colour | `/` | anonymous | change `KIT_COR_PRIMARIA`, run `npm run build` and reload: the button changes colour. Without the route's `panel:app` it would render amber | 🟢 |
| F-67 | The three exceptions are **declared**, not hidden | `/infra/command-center/commands` | `infra` | uncheck `View:Commands`: the screen **still** opens. The package exposes a single callback for all three of its Pages, so their barrier is `command-center:access`. `tests/Kit/PermissoesDeTelasTest.php` has the case that asserts this gap and turns red the day it closes | 🔵 |

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

### The README images come out of a test

The screenshots in this README are **not taken by hand**. They come from
`tests/BrowserTenancy/CapturaDeArteTest.php`, in the same suite that proves the screens work:

```bash
composer art
```

The command really navigates, saves the PNGs, publishes them into `art/`, generates the
`art/thumbs/` versions and assembles the flow GIF. It is the only way we found for the docs not to
rot: nobody redoes fifteen images every release, and the result is a README showing a version of
the kit that no longer exists.

| Step | What it does |
|---|---|
| `npm run build` + `view:cache` | hard prerequisites of the browser suite |
| `KIT_ART=1 pest tests/BrowserTenancy/CapturaDeArteTest.php` | navigates and writes the PNGs into `tests/Browser/Screenshots/` (the plugin's fixed path) |
| `php artisan kit:arte` | copies into `art/`, resizes the thumbs and assembles the GIF |

Three decisions worth knowing before you touch it:

- **`KIT_ART=1` is not decoration.** Without the variable the file is *skipped*. It writes into
  `art/`, and a CI suite that dirties the working tree is worse than a slow one.
- **The sizes are fixed: 1400x875 full, 760x475 thumb.** That is the ratio of the images already in
  `art/`, and the gallery puts two thumbs per row — a thumb with a different ratio breaks the table.
- **The GIF is a slideshow**, assembled with `ffmpeg` from three frames. The browser plugin does not
  record video, and captured frames are what can be reproduced deterministically. With no `ffmpeg`
  on the PATH the command warns and moves on: the static images were already published.

Only need to redo the thumbs, without repeating the navigation? `php artisan kit:arte --sem-gif`.

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
| 16 | **[CSV import and export](#csv-import-and-export)** | the Action in each `app/Filament/**/Pages/List*.php` (on or commented out); the permission in `config/filament-shield.php` → `policies.methods`; history retention in `KIT_RETENCAO_IMPORTACOES_DIAS` / `KIT_RETENCAO_EXPORTACOES_DIAS` in `.env` | reseed `ShieldPermissionsSeeder` + `PapeisSeeder` after touching the config |

The last eleven are not asked because they are **code or screen data**, not a value that fits in a terminal prompt. The installer lists them in the final summary, each with its file.

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

> **Four of these defaults are editable in [Kit settings](#kit-settings-under-admin)**, on the *Tables* tab: rows per page, striped rows, recall of the user's filter/search/sort, and draggable columns. The rest stays a code decision on purpose — those are choices with a written reason, not matters of taste.
>
> ⚠️ **Table density does not exist in Filament 5**, so it is not on the screen. The old TODO here promised four items and one of them has no API: a sweep over `vendor/filament/tables/src` returns no occurrence of `density`, and `vendor/filament/tables/src/Enums/` holds seven enums, none for density. What the framework does offer as a visual tightness control is `striped()`, and that is the one that became configurable.

## Kit settings under `/admin`

What the installer asked — plus a handful of things you previously could only change by editing a file — now lives at **`/admin/configuracoes-do-kit`**, in four tabs. No `.env`, no deploy.

| Tab | What you change |
|---|---|
| **Identidade** (identity) | application name, primary colour (the Filament palette **or** a free hex value), brand logo, favicon and the artwork on the authentication screens |
| **E-mail** | transport (`log`, `array`, `smtp`), host, port, encryption, username, password and sender |
| **Tabelas** (tables) | rows per page, striped rows, recall of the user's filter/search/sort, and draggable columns — the defaults for **every** table in all three panels |
| **Kit** | card navigation hub, and what your business calls each organisation (singular and plural) |

Everything is stored by `spatie/laravel-settings` in the `settings` table, with the screen coming from `filament/spatie-laravel-settings-plugin` — both were already installed in the kit and unused until this version.

### Who wins: the database or `.env`?

This is the question that decides whether the screen is useful or decorative, and there is a single answer:

> **The database wins at runtime. `.env` seeds the first write and is the fallback.**

How that works without any consumer knowing the settings exist:

1. The `database/settings/*_create_kit_settings.php` migration seeds each property with the value **from `config(...)`**, which comes from `.env`. On a fresh install, the colour and name you picked during `kit:install` reach the database on their own — `migrate` runs after the installer wrote the file.
2. `App\Providers\KitServiceProvider::configureSettingsDoKit()` overlays the process configuration with what the database holds, once per request and per artisan command.
3. `App\Support\CorPrimaria`, the three `PanelProvider`s, the global table configuration and Laravel's own `MailManager` all keep reading `config()`. None of them changed.

What happens in each situation:

| Situation | Who wins |
|---|---|
| the property has a row in the database | **the database** |
| the property has no row (you added one and didn't migrate) | `.env`, with a `warning` in the log |
| the `settings` table does not exist (before the first `migrate`) | `.env`, silently |
| the database is unreachable | `.env`, with a `warning` |
| `kit:install` on a fresh install | `.env` → the migration carries the values into the database |
| `kit:install --force` | drops the database, rewrites `.env` and re-migrates → the database is born matching the new `.env` |
| `kit:install --custom` on an installed project | rewrites `.env` **and** writes to the settings — both sources end up equal |

**There is no switch for "use the settings or not"**, and that is a decision, not an omission: a flag would be a third source of truth, which is exactly the problem the rule above solves. To turn it off, `php artisan migrate:rollback` on the settings migration — with no rows in the table the overlay is a no-op and `.env` is the only source again.

### Colour: closed list and free colour

Two fields, with a declared precedence:

**valid hex → palette name → Filament default.**

Hex wins because it is the more specific field: someone typing `#7c3aed` chose that colour, whereas the list selector has a default value and may never have been touched. A value outside the format (`#abcd`, `blue`, `#gggggg`) is **ignored** and resolution falls back to the name — the same tolerance the kit already had for an invalid colour name, and for the same reason: this runs in every panel's boot, and an exception there would take down **every** page in the project, not one screen.

Inside `/app/{organisation}`, the **organisation's** colour still beats both.

### Permission

Just one: **`View:ConfiguracoesDoKit`**, generated by `ShieldPermissionsSeeder` and handed to the `admin` role by `PapeisSeeder` — with no list to edit, because a role's matrix is the whole panel's. `master_global` gets in through `Gate::before`; `infra` and `panel_user` do not receive it.

It is one permission for opening **and** saving, on purpose. The plugin's `canEdit()` disables the form but **does not hide values** — the package's own README says so in writing — and this screen holds the SMTP password. A "read-only" role here would be a role that reads a credential.

### Upload ceiling: 10 MB, and where to change it

Every upload in the kit — this screen's logo, favicon and login artwork, the organisation logo in
`/admin/organizacoes`, and Projeto attachments — accepts files **up to 10 MB**, and **refuses SVG**.

The number is **one** key, in `.env`:

```dotenv
# In MEGABYTES. Empty, 0 or absent = 10.
KIT_UPLOAD_MAXIMO_MB=10
```

It feeds `config('kit.uploads.maximo_em_kb')` — the config holds **kilobytes**, because that is
the unit Filament's `->maxSize()` and Livewire's temporary-upload rule receive. The multiplication
by 1024 lives in exactly one place, in `config/kit.php`, and `App\Support\TetoDeUpload` is what
reads the key. There is deliberately no field for this on the screen: it is an installation
decision, not a day-to-day one.

**An upload crosses four limits, and the smallest one wins.** They do not refuse in the same way,
and that is what makes a mismatch expensive:

| Layer | Where | Value in the kit | How the error shows up |
|---|---|---|---|
| nginx | `docker/nginx/nginx.conf` | `client_max_body_size 60M` | network failure in the console |
| PHP | `docker/php/uploads.ini` | `upload_max_filesize=52M`, `post_max_size=60M` | same |
| Livewire (temporary upload) | aligned to the kit key by `KitServiceProvider`, with 1 MB of headroom | 11 MB | 422 on the XHR, generic error |
| Filament (`->maxSize()`) | the kit key | 10 MB | **a proper message, on the field** |

Only the last one refuses with a clear message — which is why the kit aligns Livewire to the key
instead of leaving its default (12 MB) looser than the screen.

**To raise the ceiling substantially**, change these together:

1. `KIT_UPLOAD_MAXIMO_MB` — covers the screen and Livewire in one go;
2. above 52 MB, `docker/php/uploads.ini` (`upload_max_filesize` and `post_max_size`);
3. above 60 MB, `docker/nginx/nginx.conf` (`client_max_body_size`).

⚠️ **Outside the kit's Docker setup, PHP usually ships with `upload_max_filesize=2M`.** There the
real ceiling is 2 MB, not the key's — and the error shows up as a network failure that never
mentions size. Check with `php -i | grep upload_max_filesize` before blaming the kit.

### Why SVG is refused

SVG is XML, and XML accepts `<script>`. The logo, the favicon and the login artwork are served
from the application's **same origin**, with public visibility: opening an uploaded SVG's URL
would run the script with access to the session cookie — stored XSS. The uploader is `admin`, who
already has full access, so this is insider escalation rather than an anonymous door; in a starter
kit it is worth closing anyway.

The barrier is **Laravel's** `mimes` rule (not Filament's `->image()`, which is a different thing
and accepts `image/*`, SVG included), with the format list in
`ConfiguracoesDoKit::FORMATOS_DE_IMAGEM`: jpg, jpeg, png, gif, bmp, webp, avif, heic, heif, **ico**,
**tif** and **tiff**. SVG is the only image format left out, and it is the only one that carries script.

And it does **not** look at the extension: the MIME type comes from the file's content on the
temporary disk, so renaming `logo.svg` to `logo.png` does not get through. On Projeto attachments,
where an allow-list would close the field to PDFs and spreadsheets, the rule refuses only
`image/svg+xml`.

### Change trail

Every change shows up in **`/infra/audits`**, with who changed it, when, the property name and the old and new values. One row per changed property; saving without changing anything creates no record.

The mail password is **encrypted** in the `settings` table and enters the trail **masked** (`••••••`): the record says the secret changed, never what it is.

Two details worth knowing before touching this:

- The trail does **not** come from the `App\Traits\AuditsFillables` trait. A spatie settings class is not an Eloquent model, and pointing its repository at a model with the trait would audit only **creation** — changing an existing property goes through `upsert()`, which fires no Eloquent event. The trail comes from a `SavingSettings` listener, the only point in the package carrying old and new values together.
- The recorded event is `settings-updated`, not `updated`, so the trail's "restore" button does **not** appear: it would `fill(['nome_da_aplicacao' => …])` into a row whose columns are `group`/`name`/`payload`.

### This is not an organisation's settings

A tenant's visual identity (per-organisation colour and logo) is still plain CRUD at **`/admin/organizacoes`**, in the `cor_primaria` and `logo` columns of the `Tenant` model, and it beats the kit's inside `/app/{slug}`. Nothing was moved here.

### What was left out, and why

| Item | Why |
|---|---|
| database driver, host and name | changing it after `migrate` is not a config rewrite, it is a different installation |
| turning **multi-tenancy** on/off | the permission tables only get the context column if `permission.teams` is active **before** migrate; the path is `php artisan kit:tenancy` |
| **admin e-mail and password** | `UsuarioAdminSeeder` does not sync, on purpose (it runs on every `db:seed`, and updating the password there would silently revert a change made in the profile screen). A field that does not change the credential is worse than no field — the path is the profile screen |
| organisations CRUD **slug** | it is read at route registration, not at render, and the URL is a permanent identifier |
| panel **languages** | the kit's internationalisation is not done: turning on a second language today switches half the screen. See the `idiomas` block in `config/kit.php` |
| trail **retention** | not an installation question; it stays in `.env`, where zero has documented semantics |

### Performance

The overlay costs **one** query per boot (the whole group comes from a single read). If that bothers you, `SETTINGS_CACHE_ENABLED=true` in `.env` — bearing in mind that with the cache on, saving through the screen requires `php artisan settings:clear-cache`.

### Adding a property

Three places, always, and `tests/Kit/ConfiguracoesDoKitTest.php` fails if you forget one:

1. the typed property in `app/Settings/ConfiguracoesDoKit.php`;
2. the line in `ConfiguracoesDoKit::mapaDeConfiguracao()` (property → `config()` key);
3. the `add()` / `deleteIfExists()` pair in a new migration under `database/settings/`.

Plus the field on the right tab of `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`.

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

### New Page, Widget and Action

Resource is the easy case: the two seeders handle it. The other three families need one line of code,
because Filament's defaults are **permissive** — the vendor says so in a comment, in
`Pages/Concerns/CanAuthorizeAccess.php` (`canAccess()` returns `true`), in `Widget.php` (`canView()`
returns `true`) and in `Actions/Concerns/CanBeAuthorized.php` (authorization defaults to `null`, i.e.
allowed).

Shield **generates** `View:{Page}` and `View:{Widget}` by discovery, `PapeisSeeder` **hands them** to
the panel's roles and the roles screen **shows** the checkbox — none of which makes the permission be
consulted. Without the trait, unchecking the box changes nothing.

```php
// New panel Page
use App\Filament\Concerns\ExigePermissaoDaTela;

class MyPage extends Page
{
    use ExigePermissaoDaTela;

    // Local rule (config flag, tenancy) goes IN THE HOOK, never overriding canAccess():
    protected static function regraLocalDeAcesso(): bool
    {
        return (bool) config('kit.my_flag');
    }
}

// New Widget
use App\Filament\Concerns\ExigePermissaoDoWidget;

class MyWidget extends StatsOverviewWidget
{
    use ExigePermissaoDoWidget;

    // Optional data-source check goes IN THE HOOK, never overriding canView():
    protected static function fonteDeDadosDisponivel(): bool
    {
        return (bool) rescue(fn (): bool => Schema::hasTable('my_table'), false);
    }
}
```

> ⚠️ **Overriding `canAccess()`/`canView()` on the class silently disables the permission.** A class
> method wins over a trait method, with no error and no warning. That is why both concerns publish a
> **hook** for the local rule, and why `tests/Kit/PermissoesDeTelasTest.php` and
> `PermissoesDeWidgetsTest.php` each have a case that walks EVERY class and fails the one that does
> not consult it.

**Action** is an explicit declaration, because Shield discovers no Action at all:

| The Action belongs to… | The permission is born in | And on the Action |
|---|---|---|
| A Resource (table, header, RelationManager) | `config('filament-shield.resources.manage')` on that panel's Resource | `->authorize('MyAction:MyModel')` |
| A Page | `config('filament-shield.custom_permissions')` **and** `PapeisSeeder::paineisDasPermissoesCustomizadas()` | `->authorize('MyAction:MyModel')` |

The second row has two halves because `custom_permissions` **knows nothing about panels**: without the
seeder's map, the new key lands on `admin`, `infra`, `admin_app` **and `panel_user`**. A key with no
entry in the map reaches no role at all (fail-closed) and case `CT-19` of
`tests/Kit/PermissoesDeAcoesTest.php` turns red naming the key.

> ⚠️ **In a RelationManager, not even the NATIVE Action is covered.** `AttachAction`, `DetachAction`,
> `AssociateAction` and `DissociateAction` only check `isReadOnly()` — the comment is in the vendor's
> `getDefaultActionAuthorizationResponse()`. In the kit, the `tenant_user` link that `AttachAction`
> creates is exactly what `User::canAccessTenant()` consults to unlock `/app/{slug}`, so both carry
> `->authorize()`.

**Vendor Pages and Widgets stay out**: they are package classes, with no extension point. Their
permission exists in the database and in the checkbox, and **is not consulted** — the barrier is
`canAccessPanel()` plus the named gates from `KitServiceProvider` (`ver-logs`,
`command-center:access`, `viewPulse`, `ver-ai-tasks`).

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

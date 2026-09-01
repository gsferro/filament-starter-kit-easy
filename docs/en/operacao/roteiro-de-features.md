---
title: "Feature roadmap"
parent: "Operations"
grand_parent: "English"
nav_order: 2
---

# Feature roadmap

Everything the kit delivers, numbered, with **where it is**, **who can access it** and **how to check it**. It serves three purposes: knowing what already exists before reimplementing, having a manual test script after a `kit:update`, and giving names to features in the automated tests.

**The "Test" column** says what is already checked automatically:

| Mark | Meaning |
|---|---|
| 🟢 | covered by automated test — `composer test:kit` or `composer test:browser` |
| 🔵 | covered **in a real browser**, with JS running |
| ⚪ | no test: depends on an external service (worker, cron, Docker, SMTP) or on visual judgment |

Where the route has `{org}`, it is multi-tenant mode — without it, the path is `/app` directly.

## Access and authentication

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
| F-06 | Session lock | user menu → *Lock session* | authenticated | locks without logging out; returns with the password **or** with social login (the same buttons as the login screen). Uses the login layout, not `SimplePage` | 🟢 |
| F-07 | My profile, avatar and password | `/{panel}/my-profile` | authenticated | edits name, e-mail, password and avatar | 🔵 |
| F-08 | Impersonate | `/admin/users` → row action | `master_global` | enters as another user and returns via the top banner | ⚪ |

## Authorization

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
| F-68 | **[Card navigation hub](../recursos/hub-de-navegacao.md)** | `/infra/hub-de-infraestrutura` (always); `/admin/hub-de-administracao` and `/app{/org}/hub-do-negocio` with `KIT_HUB=true` | whoever enters the panel | open the `/infra` hub: a grid of cards, one per destination your role can reach. With `KIT_HUB=false` the `/admin` and `/app` hubs leave the menu, the URL and the ⌘K search | 🟢 |

## Invitations

| # | Feature | Where | Who can access | How to check | Test |
|---|---|---|---|---|---|
| F-15 | Individual invitation | `/admin/convites` · `/app/{org}/convites` | `admin`, `admin_app` | e-mail + role + organization; the link goes by e-mail with a single-use token | 🟢 |
| F-16 | Invitation for someone who **already has an account** | same place | same | it becomes an *access offer*: the person logs in with the password they already have and is linked | 🟢 |
| F-17 | Received invitations box | user menu → *Received invitations* | any authenticated | accept **or decline**; the decline is recorded | 🟢 |
| F-18 | Bulk invitation | listing header | `admin`, `admin_app` | paste N addresses; one with a problem **does not** bring down the others, and the summary says why | 🟢 |
| F-19 | Automatic reminders | `kit:convites-lembrar` (cron 08:00) | — | D+3 and D+5, with a **second parallel link**; the original keeps working | 🟢 |
| F-20 | Resend / revoke | row action | `admin` | resend **kills** the previous links; revoke deletes and goes to `/infra/audits` | 🟢 |

## Multi-tenancy (opt-in)

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

## Administration

| # | Feature | Where | Who can access | How to check | Test |
|---|---|---|---|---|---|
| F-29 | Users | `/admin/users` | `admin` | CRUD, with **mandatory** role on creation | 🟢 |
| F-30 | AI agent catalog | `/admin/agentes-ia` | `admin` | prompt, provider, model, tools and guardrails are **data**, editable without deploy | 🟢 |
| F-31 | Onboarding authoring | `/admin/onboarding-flows` | `admin` | checklists and tours; consumption is in the business panel | 🔵 |
| F-32 | Filled dashboard | `/admin` | `admin` | 6 widgets over the data the panel already has | 🔵 |

## Infrastructure

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

## Productivity and UI

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

## AI

| # | Feature | Where | Who can access | How to check | Test |
|---|---|---|---|---|---|
| F-53 | Assistant chat | corner of **every** `/app` screen | authenticated | streaming; renders empty without user | ⚪ |
| F-54 | Chained guardrails | — | — | budget, prompt injection, local classifier, PII redaction and sensitive-output filter. **Fail-closed** | 🟢 |
| F-55 | Run ledger | `/infra/execucoes-ia` | `infra` | every call becomes a row with cost and tokens | 🟢 |
| F-56 | Local inference | `docker compose --profile ai up -d` | — | llama.cpp; or switch `AI_PROVIDER` to SaaS | ⚪ |

## What the roadmap **does not** cover on its own

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


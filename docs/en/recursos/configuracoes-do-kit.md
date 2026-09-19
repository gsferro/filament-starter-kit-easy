---
title: "Kit settings under /admin"
parent: Features
grand_parent: English
nav_order: 6
---

# Kit settings under `/admin`

What the installer asked — plus a handful of things you previously could only change by editing a file — now lives at **`/admin/configuracoes-da-aplicacao`** — the panel menu labels the screen **Configurações da aplicação**, because once installed the kit is the provenance, not the product —, in six tabs. No `.env`, no deploy.

| Tab | What you change |
|---|---|
| **Identidade** (identity) | application name, **system version**, primary colour (the Filament palette **or** a free hex value), brand logo, favicon and the artwork on the authentication screens |
| **E-mail** | transport (`log`, `array`, `smtp`), host, port, encryption, username, password and sender |
| **Tabelas** (tables) | rows per page, striped rows, recall of the user's filter/search/sort, and draggable columns — the defaults for **every** table in all three panels |
| **Registro** (sign-up) | registration without an invitation on `/app`, manual approval and e-mail verification ([details](../autenticacao/registro-aberto.md)) |
| **Login** | the single login page at `/login` ([details](../autenticacao/login-unificado.md)), the four social login providers, each with its switch, allowed panels, *Client ID* and encrypted *Client Secret*, plus the login screen footer ([details](../autenticacao/login-social.md)) |
| **Kit** | card navigation hub, unsaved-changes alert, whether the kit version shows in the footer, the **dynamic dashboard** — and which panels it applies to —, and what your business calls each organisation (singular and plural) |

Everything is stored by `spatie/laravel-settings` in the `settings` table, with the screen coming from `filament/spatie-laravel-settings-plugin` — both were already installed in the kit and unused until this version.

## The version in the footer: yours, not the kit's

The footer of every screen in all three panels shows **your system's version** — the product born
from the kit. It comes from the *Versão do sistema* field on the **Identidade** tab, seeded by
`APP_VERSION` in `.env`.

These are two different versions, and conflating them is the mistake this section exists to prevent:

| | What it is | Where you edit it |
|---|---|---|
| `config('app.version')` | **your product's** version | the screen, or `APP_VERSION` in `.env` |
| `config('kit.version')` | the **starter kit** version the project was born from | nobody: `kit:update` writes it itself |

The second is an internal kit metric — `kit:update` uses it to know which version to diff from. It
does **not** appear in the footer by default; to show it next to yours, enable the *show kit
version* switch on the **Kit** tab. `php artisan kit:info` always prints both, regardless
of the switch.

**Empty field, no version of YOURS in the footer.** A project that does not version itself need not
pretend to. With the kit-version switch **off** — which is how it ships — the footer renders
nothing at all.

If the switch is on and the field is empty, the footer shows **only the kit version, labelled**
(`kit 0.35.0`). It is never presented as if it were your product's: the label is precisely what
prevents that reading, and it is a kit requirement, not a screen detail.

**`APP_VERSION` seeds ONCE, at install time. After that, the screen wins.**

This is the same rule as every other key on this page — *the database wins at runtime; `.env` seeds
and is the fallback* — and here it has a consequence worth spelling out: on an already-installed
project, **editing `APP_VERSION` in `.env` does not change the footer**. The stored value wins,
including when it is blank. If the field on the screen is empty, the footer shows no version of
yours even with `APP_VERSION=2.4.1` in the file.

So: `APP_VERSION` exists so a fresh install is born versioned. To change the version afterwards, use
the field on the screen.

**The kit does not read the git tag or branch name** at runtime — a production image usually has no
`.git`, and reading from there would create a second source of truth diverging from what the screen
shows. A deploy that checks out a `release/2.4` branch does **not** update the footer by itself.

**The version never shows to signed-out visitors.** The footer also renders on the login,
registration and password-reset screens, and there it stays empty on purpose: an installation's
exact version is the map of vulnerabilities that apply to it.

## Warning before you lose what you typed

On the **Kit** tab, the *unsaved-changes* switch enables Filament's native alert: leaving a form
with pending changes makes the browser ask for confirmation before discarding them.

It applies to **every** create and edit screen across all three panels, including those coming from
third-party plugins — the panel decides, not the resource, so there is nothing to wire up screen by
screen.

It ships **enabled**: the previous behaviour was losing the input silently, and silence is not the
default worth preserving. Turning it off is one click, no deploy.

> It is an alert, not a draft: confirming the exit still loses what you typed. The kit evaluated two
> draft/autosave packages and adopted neither — the reasons are in
> [`wikis/pacotes-candidatos.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/pacotes-candidatos.md).

## The dynamic dashboard is a switch, not a migration

Still on the **Kit** tab, the *Dashboard dinâmico* section swaps the panels' landing screen: instead
of the classic dashboard, a grid the user builds, moves and resizes for themselves
(`mddev31/filament-dynamic-dashboard`). It ships **off** — `KIT_DASHBOARD_DINAMICO=false` in
`.env.example` — and *Painéis onde vale* restricts which panels it applies to: empty means all of
them.

Two decisions are what make it a switch rather than a one-way door:

- **Both pages are always registered.** The panel is built in the provider's `register()` and the
  database value only arrives in `boot()`; a conditional `->pages([...])` would read the config
  before it exists. What decides which page answers is `App\Support\DashboardDinamico`, per
  request — saving here takes effect on the next F5, with no cache clear and no restart.
- **Turning it off deletes nothing.** The grids people built stay in the `dashboards` and
  `dashboard_widgets` tables and come back untouched when you turn it on again.

Viewing is not building: dragging and saving the grid belongs to whoever has Shield's
`Manage:Dashboard` permission — everyone else sees the same screen without being able to edit it.

## Who wins: the database or `.env`?

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

## Colour: closed list and free colour

Two fields, with a declared precedence:

**valid hex → palette name → Filament default.**

Hex wins because it is the more specific field: someone typing `#7c3aed` chose that colour, whereas the list selector has a default value and may never have been touched. A value outside the format (`#abcd`, `blue`, `#gggggg`) is **ignored** and resolution falls back to the name — the same tolerance the kit already had for an invalid colour name, and for the same reason: this runs in every panel's boot, and an exception there would take down **every** page in the project, not one screen.

Inside `/app/{organisation}`, the **organisation's** colour still beats both.

## Permission

Just one: **`View:ConfiguracoesDoKit`**, generated by `ShieldPermissionsSeeder` and handed to the `admin` role by `PapeisSeeder` — with no list to edit, because a role's matrix is the whole panel's. `master_global` gets in through `Gate::before`; `infra` and `panel_user` do not receive it.

It is one permission for opening **and** saving, on purpose. The plugin's `canEdit()` disables the form but **does not hide values** — the package's own README says so in writing — and this screen holds the SMTP password. A "read-only" role here would be a role that reads a credential.

## Upload ceiling: 10 MB, and where to change it

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

## Why SVG is refused

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

## Change trail

Every change shows up in **`/infra/audits`**, with who changed it, when, the property name and the old and new values. One row per changed property; saving without changing anything creates no record.

The mail password is **encrypted** in the `settings` table and enters the trail **masked** (`••••••`): the record says the secret changed, never what it is.

Two details worth knowing before touching this:

- The trail does **not** come from the `App\Traits\AuditsFillables` trait. A spatie settings class is not an Eloquent model, and pointing its repository at a model with the trait would audit only **creation** — changing an existing property goes through `upsert()`, which fires no Eloquent event. The trail comes from a `SavingSettings` listener, the only point in the package carrying old and new values together.
- The recorded event is `settings-updated`, not `updated`, so the trail's "restore" button does **not** appear: it would `fill(['nome_da_aplicacao' => …])` into a row whose columns are `group`/`name`/`payload`.

## This is not an organisation's settings

A tenant's visual identity (per-organisation colour and logo) is still plain CRUD at **`/admin/organizacoes`**, in the `cor_primaria` (free hexadecimal), `cor_primaria_nome` (the same Filament palette as this settings screen — the hexadecimal wins when filled) and `logo` columns of the `Tenant` model, and it beats the kit's inside `/app/{slug}`. Nothing was moved here.

## What was left out, and why

| Item | Why |
|---|---|
| database driver, host and name | changing it after `migrate` is not a config rewrite, it is a different installation |
| turning **multi-tenancy** on/off | the permission tables only get the context column if `permission.teams` is active **before** migrate; the path is `php artisan kit:tenancy` |
| **admin e-mail and password** | `UsuarioAdminSeeder` does not sync, on purpose (it runs on every `db:seed`, and updating the password there would silently revert a change made in the profile screen). A field that does not change the credential is worse than no field — the path is the profile screen |
| organisations CRUD **slug** | it is read at route registration, not at render, and the URL is a permanent identifier |
| panel **languages** | the kit's internationalisation is not done: turning on a second language today switches half the screen. See the `idiomas` block in `config/kit.php` |
| trail **retention** | not an installation question; it stays in `.env`, where zero has documented semantics |

## Performance

The overlay costs **one** query per boot (the whole group comes from a single read). If that bothers you, `SETTINGS_CACHE_ENABLED=true` in `.env` — bearing in mind that with the cache on, saving through the screen requires `php artisan settings:clear-cache`.

## Adding a property

Three places, always, and `tests/Kit/ConfiguracoesDoKitTest.php` fails if you forget one:

1. the typed property in `app/Settings/ConfiguracoesDoKit.php`;
2. the line in `ConfiguracoesDoKit::mapaDeConfiguracao()` (property → `config()` key);
3. the `add()` / `deleteIfExists()` pair in a new migration under `database/settings/`.

Plus the field on the right tab of `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`.

---
title: Advanced installation
parent: Getting started
grand_parent: English
nav_order: 1
---

# Advanced installation

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

### MySQL ships a container too

If you pick MySQL, the `.env` comes pointing at `127.0.0.1:3306` with user `root`, and the kit
brings the server up for you. The command differs from the Postgres one, and the reason matters:

```bash
docker compose up -d mysql redis
php artisan migrate --seed
```

MySQL is the **only database in a profile of its own**, because the installation picks a single
database — leaving it profile-less would make every install bring Postgres and MySQL up together.
Naming the services on the command line enables the MySQL profile **and** restricts the run to what
was named, so the default-profile Postgres stays down. That is why `redis` has to be written there:
naming services turns off the rest of the default set along with it.

Two details of the image, which explain what the installer writes:

- **The user is `root`.** The official image refuses to create `root` through `MYSQL_USER` — keeping
  that user, the only way is the root password.
- **The password is not empty.** `mysql:8.0` refuses to initialize without a root password, and the
  installer writes `secret`, the same one the container reads. Bringing your own server, adjust
  `DB_PASSWORD` in the `.env` — just as you already would with an external Postgres.

- **The host port is `FORWARD_MYSQL_PORT`**, defaulting to 3306, not Postgres' `FORWARD_DB_PORT`.
  They are separate keys because under the `app` profile both databases come up together, and a
  single variable would make them fight over the same port. If a MySQL is already running on your
  machine, Docker refuses with `Bind for 0.0.0.0:3306 failed: port is already allocated` — change
  the key:

  ```bash
  FORWARD_MYSQL_PORT=3399 docker compose up -d mysql redis
  ```

  and point `DB_PORT` in the `.env` at the same port.

The local AI caveat does not change: semantic search and embeddings depend on `pgvector`, which only
Postgres has.

## Container names

No service in `docker-compose.yml` declares a `container_name`. The prefix of every container and
every network comes from `COMPOSE_PROJECT_NAME`, in the `.env`, and `kit:install` writes your chosen
name there — lowercased and hyphenated, which is the format Compose accepts:

```bash
$ docker compose ps
minha-app-pgsql-1
minha-app-redis-1
```

Without that key, `starter-kit` applies — the floor written in `docker-compose.yml` itself.

Two practical points:

- **A project born from an earlier version of the kit** does not get the key through `kit:update`,
  which never touches `.env`. Run `php artisan kit:install --custom`, which redoes name and colour,
  or add the line by hand.
- **Changing the name after containers are already up creates new volumes.** The old data stays in
  the volume under the previous name; migrate it first, or change the name before the first `up`.

## The containerized application and the database

The `app` profile brings the whole application up in a container. It talks to Postgres by default;
to point it at MySQL, set in the `.env`:

```
DOCKER_DB_SERVICE=mysql
```

and enable both profiles together, otherwise the database container never starts and the host does
not exist:

```bash
docker compose --profile app --profile mysql up -d --build
```

One honest caveat: in that combination an idle Postgres container comes up, because the `app`
profile services depend on it to order the boot. The alternative was measured and is worse — without
that dependency, `docker compose --profile app up -d` brings the application up with **no database at
all**, and without an error.

## Commands

```bash
composer dev          # server + queue + vite together
composer test         # pint + phpstan + filacheck + the whole suite
composer test:kit     # only the kit's tests (the foundation), in parallel
composer lint         # formats the code
composer lint:check   # only checks the formatting, changing nothing (what CI runs)
composer filament:check   # only the Filament-specific lint (FilaCheck)
composer refactor:preview # what Rector would rewrite (dry-run) — OUTSIDE composer test
composer refactor:apply   # applies Rector's rewrite — OUTSIDE composer test
composer upgrade:filament # runs vendor/bin/filament-v5 (filament/upgrade is already in require-dev)
php artisan kit:install --force   # reinstalls from scratch (deletes the SQLite file) and asks again
php artisan kit:install --custom   # redoes only name and colour, without touching the database
php artisan kit:install --no-custom   # installs without asking anything
php artisan kit:install --no-npm      # skips installing and building the front-end assets
php artisan kit:install --no-seed     # doesn't seed the database (roles, initial user, AI agents)
php artisan kit:install --no-support  # skips the invitation to star the kit on GitHub
#   --create-project is internal to post-create-project-cmd: removes what only serves the kit's own repository
php artisan kit:admin             # changes the administrator's e-mail and password (asks for confirmation)
php artisan kit:admin --email=x --senha=y --force   # no prompts — avoid it: the password lands in the shell history
php artisan kit:info              # shows how the project is customized and where each value comes from
php artisan kit:update            # brings in improvements from a new kit version
php artisan kit:tenancy           # turns on multi-tenancy (opt-in)
```
The quality deep dives that used to sit under this section — FilaCheck, Rector, the test suite,
the README images and the SFDIPOT sweep — are in
[Code quality](../referencia/qualidade-de-codigo.md).

## Customize your project

**The installer already asks the first five** — the list below is for changing them later, or for whoever skipped the questions.

`php artisan kit:info` shows the current value of every item below, and whether it comes from the database or the `.env`.

| # | What | Where | Asked during installation? |
|---|---|---|---|
| 1 | **Name** | `APP_NAME` in `.env` | ✅ |
| 2 | **Database** | the `DB_*` block in `.env` | ✅ |
| 3 | **Seeder credentials** | `KIT_ADMIN_EMAIL` / `KIT_ADMIN_PASSWORD` in `.env` | ✅ |
| 4 | **Primary color** | `KIT_COR_PRIMARIA` in `.env` (a color name from the Filament palette), or `KIT_COR_PRIMARIA_HEX` with a free hex value — the hex beats the name when both are filled | ✅ |
| 5 | **[Multi-tenancy](../recursos/multi-tenancy.md)** | `php artisan kit:tenancy`, and the displayed term in `config/kit.php` → `tenancy.label` | ✅ |
| 6 | **Login artwork** | none: it **shows the application name** (`APP_NAME`) on its own. To replace it with your own image, upload it at `/admin/configuracoes-do-kit` | ✅ (via the name) |
| 7 | **Panel access** | each user's role (`/admin` → Roles, the *Painel* field); the rule that reads it is `App\Models\User::canAccessPanel()` | — |
| 8 | **Permission matrix** | `database/seeders/PapeisSeeder.php` | — |
| 9 | **Health checks** | `KitServiceProvider::configureHealthChecks()` | — |
| 10 | **Commands in the UI** | `config/command-center.php` | — |
| 11 | **Backups** | destination and schedule in `config/backup.php` | — |
| 12 | **AI agent** | `/admin` → AI Agents (or `database/seeders/AssistenteSeeder.php`) | — |
| 13 | **[Panel languages](../referencia/busca-e-idioma.md#the-language-switcher)** | `config/kit.php` → `idiomas` (a list of locales; with only one, the switcher doesn't show) | — |
| 14 | **[Trail retention](../recursos/trilhas-de-infraestrutura.md#retention-the-number-is-the-intent-the-scheduler-is-the-execution)** | `KIT_RETENCAO_EXCECOES_DIAS` / `KIT_RETENCAO_EMAILS_DIAS` in `.env` | — |
| 15 | **[Media disk](../recursos/anexos-e-midia.md)** | `MEDIA_DISK` in `.env` (`local` by default — private, served through a signed URL) | `php artisan kit:midia-privada` migrates media already written to a public disk |
| 16 | **[CSV import and export](../recursos/import-export-csv.md)** | the Action in each `app/Filament/**/Pages/List*.php` (on or commented out); the permission in `config/filament-shield.php` → `policies.methods`; history retention in `KIT_RETENCAO_IMPORTACOES_DIAS` / `KIT_RETENCAO_EXPORTACOES_DIAS` in `.env` | reseed `ShieldPermissionsSeeder` + `PapeisSeeder` after touching the config |

The last eleven are not asked because they are **code or screen data**, not a value that fits in a terminal prompt. The installer lists them in the final summary, each with its file.

> ⚠️ Item 5 is the only one that is **not** "edit a file" once installed: `kit:tenancy` runs `migrate:fresh --seed` and **deletes your data**. It requires a clean git tree and an explicit confirmation. **Answered during installation it deletes nothing** — the database does not exist yet, and that is the right moment to decide.

> The primary color applies to all three panels. With [multi-tenancy](../recursos/multi-tenancy.md) on, each organization's color **wins** over it inside `/app/{slug}` — `/admin` and `/infra` keep the project's one. For a full palette, and not just `primary`, the way is still `->colors([...])` in each `app/Providers/Filament/*PanelProvider.php`.


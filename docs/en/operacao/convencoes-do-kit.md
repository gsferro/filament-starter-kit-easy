---
title: "Kit conventions"
parent: "Operations"
grand_parent: "English"
nav_order: 3
---

# Kit conventions

- **UUID in routes, int `id` as PK.** Every new table gets `$table->uuid('uuid')->unique()` and the model uses `App\Traits\TemUuid`. A URL with a numeric id returns 404 and nobody enumerates records by sequence. UUID is not authorization — policies remain mandatory.
- **Auditing on what is editable.** `App\Traits\AuditsFillables` audits exactly the `$fillable`, without leaking technical columns into the trail.
- **Seeders never use factories or faker.** `fakerphp/faker` is `require-dev` and the Docker image runs `--no-dev`.
- **Permissions come from a seeder, not from the interactive `shield:generate`** — that's what makes an unattended install possible. `ShieldPermissionsSeeder` generates for all **three** panels (the Shield command only sees the current panel); `PapeisSeeder` slices the matrix per panel and hands it to the roles. After creating new Resources, run both (see [below](depois-de-criar-resources.md)).
- **Panel access is data on the role**, in the `roles.painel` column — not a list of names in the code. A role with no panel opens no panel: the default is closed.
- **No affordance without permission.** Menu, search and actions consult `canAccess()`/`canCreate()` before showing up. Finding something that results in a 403 is considered a bug.
- **A listing with distinct states gets `getTabs()`.** A tab is the **one-click** slice; the modal
  filter is for **combining** (with search, with the trash, with another filter). Both exist, and they
  do not compete. The slicing rule lives in **one place only**, called by both —
  `AprovacaoDeCadastro::recorteDePendentes()` for users, `Convite::recorteDePendentes()`/
  `recorteDeAceitos()` for invitations. Rewriting the query inside `getTabs()` is how the filter and
  the tab start disagreeing without anyone noticing. The badge count always comes from the Resource's
  `getEloquentQuery()`, never from the model: on `/app` it carries the organisation slice, and a badge
  counting the whole installation would report records outside it. **The active tab does not persist
  in the session** (that's how Filament works): to link to an already-sliced listing — from a hub card,
  from a notification — use `?tab=` in the URL, with `ListUsers::getUrl(['tab' => 'pendentes'])`. The
  AI ledger (`/infra/ai-runs`) is deliberately left out: it already has `SelectFilter('status')` on
  screen, and a tab per status would duplicate it.
- **Plugin translations go in `lang/vendor/`.** Several packages ship English only; the kit translates them without touching vendor.

## Traps already handled

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


---
title: "Global Filament configuration"
parent: Features
grand_parent: English
nav_order: 5
---

# Global Filament configuration

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

> **Four of these defaults are editable in [Kit settings](configuracoes-do-kit.md)**, on the *Tables* tab: rows per page, striped rows, recall of the user's filter/search/sort, and draggable columns. The same four exist in `.env` as seed and fallback — `KIT_TABELA_PAGINACAO`, `KIT_TABELA_LISTRADA`, `KIT_TABELA_PERSISTIR_FILTROS` and `KIT_TABELA_COLUNAS_REDIMENSIONAVEIS` — and the value stored in the database wins. The rest stays a code decision on purpose — those are choices with a written reason, not matters of taste.
>
> ⚠️ **Table density does not exist in Filament 5**, so it is not on the screen. The old TODO here promised four items and one of them has no API: a sweep over `vendor/filament/tables/src` returns no occurrence of `density`, and `vendor/filament/tables/src/Enums/` holds seven enums, none for density. What the framework does offer as a visual tightness control is `striped()`, and that is the one that became configurable.


## Contrast fixes the kit applies for you

Not everything that decides how a panel looks lives in PHP. Some colour pairs shipped by Filament and by plugins **fall short of the 4.5:1 WCAG AA minimum** for small text, and the failure is silent: the HTML is correct, the test passes, and it is the user who cannot read it. The kit fixes those cases in `resources/css/filament/kit.css`, loaded into all three panels through the same mechanism plugins use.

| Where | What Filament/the plugin ships | What the kit applies |
|---|---|---|
| Active item in **top** navigation | `primary-600` on `gray-50` — 3.06:1 on the default palette | `primary-700` in light, `primary-400` in dark |
| Environment indicator badge | `color-600` on `color-50` | `color-700` in light |
| Plugin `*-primary-*` utilities | the **literal** amber palette from the package build | the panel's own `--primary-*` variables |

### Top navigation

If your panel uses `->topNavigation()`, the active item's label becomes legible in whatever palette you picked. Measured against Filament's twenty-one named palettes: **nine fail** at the step the framework applies, and none fails at the step the kit applies. `Amber` is the default when `KIT_COR_PRIMARIA` is empty and scores 3.06:1 — a fresh install lands exactly on the worst case.

**If your panel uses the sidebar, which is the default, nothing changes.** Filament only emits the top items when top navigation is on, so the rule matches no element at all. You can switch between the two modes without thinking about it.

### Dark mode is not a detail

Every colour override in the kit ships **two** rules, one per theme, and that is not fussiness. Filament writes its dark rule as

```css
.fi-topbar-item.fi-active .fi-topbar-item-label:where(.dark, .dark *)
```

and **`:where()` contributes zero specificity**. An override written for light mode only, with enough specificity to win, wins in **both** — painting the light colour onto the dark background, making worse exactly what it came to fix.

The dark counterpart is written `.dark:root`, with the class on the **root itself**. `.dark :root` and `:root .dark` look equivalent and are dead letters: `:root` *is* the `<html>` element, so it can neither descend from anything nor contain the class the theme switcher writes onto it.

> **When adding your own colour override**, follow the same pairing and run `php artisan filament:assets` after editing. The guards live in `tests/Kit/ContrasteDaNavegacaoNoTopoTest.php` and `tests/Kit/CorrecaoDeCorPrimariaTest.php`: they read the CSS and the `vendor/` stylesheets at runtime, recompute contrast against Filament's own palettes, and turn red when a package upgrade changes the game.

## Where to point `DocumentRoot` — and what the kit does when it is wrong

A Laravel project is served from **`public/`**, never from the project root. Your web server's
`DocumentRoot` (or your hosting panel's *Document Root*) must point at:

```
/path/to/project/public
```

On shared hosting that is not always configurable, and the usual workaround is an `.htaccess` at
the root rewriting everything into `public/`. **It works, with a side effect.**

After an *internal* rewrite, Apache hands PHP `SCRIPT_NAME=/public/index.php` while `REQUEST_URI`
still holds what the user asked for. Laravel derives the base URL by comparing the two:

| What the browser asked for | Derived base | URLs generated on the page |
|---|---|---|
| `/app`, `/admin`, `/infra` | empty | clean |
| `/public/app` | `/public` | **all** prefixed |

So entering **once** through an address containing `/public` — an old bookmark, a shared link — is
enough for every link on that page to come out prefixed. Because the `.htaccess` usually redirects
back, the prefix appears and disappears, which makes the problem look random.

**The kit defends itself against this.** It refuses to honour a base URL ending in `/public` and
rebuilds the root without that suffix, once per request. Three consequences worth knowing:

- it covers **any panel**, including ones you create — the fix is at the URL root, not in a list
  of panels;
- it covers **assets** too (`/css`, `/js`, `/build`), which come from the same generator;
- on a correct install **nothing happens**: the base is empty and the check returns on its first
  line, with no query and no cost.

> **This does not excuse fixing `DocumentRoot`.** The kit's defence strips the prefix from the
> URLs the application generates; it cannot stop someone typing `/public/...` in the address bar,
> nor stop someone typing `/public/...` in the address bar. If you can point
> `DocumentRoot` at `public/`, do it.
>
**When the kit strips the prefix, and when it does not.** It only shortens the root when there is
**evidence** that `/` really routes into `public/` — in practice, a `RewriteRule` pointing at
`public/` in the root `.htaccess`. Without that evidence it **does nothing**, and the reason is
concrete: an install served at `https://host/public/...` **without** a rewrite works exactly like
that, and shortening would turn every link and every asset into a 404.

nginx has no `.htaccess` to inspect. Declare it by configuration:

```dotenv
KIT_URL_REMOVER_SUFIXO_PUBLIC=true   # the rewrite lives in the vhost
KIT_URL_REMOVER_SUFIXO_PUBLIC=false  # turn it off entirely
```

Without the key, the kit detects on its own.

> **What the defence does NOT reach.** It fixes the URLs the application **generates**. Anything
> taken from the request itself keeps the prefix: the intended URL stored when you are bounced to
> login (`redirect()->guest()`) and the "back" link derived from the `Referer`. A user who reaches
> login from `/public/app` returns to `/public/app` after authenticating. One more reason to fix
> `DocumentRoot`.

> Guard: `tests/Kit/UrlSemPrefixoPublicTest.php`.

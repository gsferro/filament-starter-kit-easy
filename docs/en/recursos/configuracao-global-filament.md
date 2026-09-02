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


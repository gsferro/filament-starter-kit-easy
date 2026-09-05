---
title: "Search and language"
parent: "Reference"
grand_parent: "English"
nav_order: 2
---

# Search and language

## The ⌘K search

[![⌘K search](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/spotlight.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/spotlight.png)

The topbar field is **Filament's native one** — same markup, same look, same `Ctrl/⌘+K`. What changes is what happens on click: instead of typing there, it opens the Spotlight overlay, which searches on four fronts:

| Category | What it finds |
|---|---|
| **Records** | Filament's native global search (respects your resources' `getGloballySearchableAttributes()`) |
| **Screens** | the panel's resources, **filtered by `canAccess()`** |
| **Pages** | the panel's pages, also by `canAccess()` |
| **Actions** | "Create X" for each resource, with `canAccess()` + `canCreate()` + `shouldRegisterNavigation()` |

Permission filtering is the reason `App\Filament\Spotlight\*` exists in the kit: the package's categories do **not** call `canAccess()`, and without that the search offers screens that would result in a 403 — an affordance leak. The "Create X" suggestions are the kit's too (`AcoesDeCriacao`), for the same reason plus one more: the package's discovery resolves URLs without checking context and takes the login screen down with a 500.

**The overlay's styling is the kit's too.** The package's blade emits Tailwind utilities and relies on a compiled theme — which the kit deliberately does not have (the panels work without `npm run build`). Without CSS the overlay opened `fixed` with no `inset-0`, off screen: "nothing happens" on click. `resources/css/filament/spotlight.css` covers those classes, scoped to the component root, and `tests/Kit/SpotlightCssTest.php` reads the vendor blade and fails on any class without a rule — it flags a package upgrade before anyone opens the screen. **Project created before v0.30.0**: `php artisan kit:update` brings the file and the published copy in `public/css/kit/`.

## The language switcher

The language button (`bezhansalleh/filament-language-switch`) is registered on **all three panels and on the login screens too** — which is exactly where someone who doesn't read Portuguese needs to switch, before a session even exists.

**It is driven by data, not by a flag.** The list of locales lives in `config/kit.php`:

```php
'idiomas' => ['pt_BR'],           // how the kit is born: one language, no button
'idiomas' => ['pt_BR', 'en'],     // two languages: the switcher shows up on its own
```

With a **single language** — the default — the switcher does not appear: there is nowhere to switch to. That is why this is a list and not a boolean; nobody forgets a flag left on with only one language.

> ⚠️ **The switcher translates Filament's layer and the packages', not the kit's own labels.** The coverage comes from Filament and `laravel-lang/common`. "Administrador Geral", "Acesso ao painel /app", the hub titles and the resource labels are pt-BR strings written in the code — there are eleven `__()` calls in the whole app. Turning `en` on today makes **half the screen switch language and the other half not**. Internationalizing the kit is declared work, not yet done.


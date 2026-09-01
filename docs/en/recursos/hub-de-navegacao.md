---
title: "Card navigation hub"
parent: Features
grand_parent: English
nav_order: 7
---

# Card navigation hub

Each panel has a **hub** page: a grid of cards, one per destination in the panel, instead of the
sidebar tree — for when the question "where do I see X?" is real. There are three:

| Hub | Panel | URL | Born |
|---|---|---|---|
| `HubDeInfraestrutura` | `/infra` | `/infra/hub-de-infraestrutura` | **on** — sixteen destinations in four groups, half of them with untranslated plugin labels |
| `HubDeAdministracao` | `/admin` | `/admin/hub-de-administracao` | off |
| `HubDoNegocio` | `/app` | `/app{/org}/hub-do-negocio` | off |

The flag is **`KIT_HUB`** (`config/kit.php` → `hub`, default `false`). It turns on the `/admin` and
`/app` hubs; off, both pages leave the menu, the URL and the ⌘K search. `/infra` **does not depend
on it**: it is the only panel where the grid beats the tree by default, and the asymmetry is a
recorded decision (ADR-03 of the `hub-de-cards-opcional` wiki). Setting `KIT_HUB=true` needs
nothing else — `FilamentCardsPlugin` is already registered on all three panels and the cards' CSS is
already published.

The cards come from `App\Filament\Concerns\DescobreCardsDoPainel`, which scans the panel's resources
and pages and **filters by each destination's `canAccess()`** — whoever can't reach the screen doesn't
see the card. The hub adds to the sidebar, it doesn't replace it: no item leaves the navigation.

The browser test is `tests/Browser/HubDeCardsTest.php`: the `/infra` grid **painted**, with the
description inside the card — because the package registers no CSS and without
`resources/css/filament/cards.css` the HTML is the same and the grid becomes a list of loose links.


---
title: "Installed packages"
parent: "Reference"
grand_parent: "English"
nav_order: 3
---

# Installed packages

Everything below comes installed, published and registered on the panels — there is no "now install plugin X" step. The source of truth for versions is `composer.json`; the table tells you **what each one is for inside the kit**.

## Base

| Package | What for |
|---|---|
| [laravel/framework](https://packagist.org/packages/laravel/framework) | the framework |
| [filament/filament](https://packagist.org/packages/filament/filament) | the panels, tables, forms and widgets |
| [laravel/tinker](https://packagist.org/packages/laravel/tinker) | Laravel's REPL |
| [livewire/blaze](https://packagist.org/packages/livewire/blaze) | optimizes Blade components by folding them into the parent template |

## Administration and security

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
| [laravel/socialite](https://packagist.org/packages/laravel/socialite) | social login (Google, GitHub, LinkedIn, X), opt-in per provider |
| [ddr/filament-captcha](https://packagist.org/packages/ddr/filament-captcha) | anti-robot challenge on the public screens (reCAPTCHA v2/v3, Turnstile, hCaptcha), opt-in; the kit wraps it with fail-closed verification and logging ([details](../autenticacao/protecao-anti-robo.md)) |

## Observability and maintenance

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

## AI

| Package | What for |
|---|---|
| [laravel/ai](https://packagist.org/packages/laravel/ai) | the official Laravel AI SDK (agents, tools, streaming) |
| [fomvasss/laravel-ai-tasks](https://packagist.org/packages/fomvasss/laravel-ai-tasks) | AI task orchestration: routing, queue, auditing and budget |

## UI and productivity

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
| [harvirsidhu/filament-cards](https://packagist.org/packages/harvirsidhu/filament-cards) | the card grid of the [navigation hubs](../recursos/hub-de-navegacao.md) |
| [leandrocfe/filament-apex-charts](https://packagist.org/packages/leandrocfe/filament-apex-charts) | ApexCharts charts in the dashboard widgets |
| [solution-forest/filament-simplelightbox](https://packagist.org/packages/solution-forest/filament-simplelightbox) | lightbox to enlarge images in tables and infolists |

## Data and services

| Package | What for |
|---|---|
| [filament/spatie-laravel-settings-plugin](https://packagist.org/packages/filament/spatie-laravel-settings-plugin) | settings pages in the panel |
| [spatie/laravel-settings](https://packagist.org/packages/spatie/laravel-settings) | the persisted settings behind them |
| [filament/spatie-laravel-media-library-plugin](https://packagist.org/packages/filament/spatie-laravel-media-library-plugin) | the media layer (uploads, collections, conversions) in the form, table and infolist components |
| [mike-bronner/laravel-model-caching](https://packagist.org/packages/mike-bronner/laravel-model-caching) | automatic caching of Eloquent queries |
| [predis/predis](https://packagist.org/packages/predis/predis) | pure-PHP Redis client (no extension needed) |
| [laravel/reverb](https://packagist.org/packages/laravel/reverb) | WebSocket for real-time notifications |

> **Engines under the plugins**, installed as dependencies (you don't declare them, but they're what actually runs): `spatie/laravel-permission` (Shield), `spatie/laravel-health` (the checks), `spatie/laravel-activitylog` (the activity log), `spatie/laravel-medialibrary` (the attachments) and `livewire/livewire` (all of Filament).

## Model Caching

The kit applies the `App\Traits\ModeloCacheavel` trait to models that have a Resource on the `/app` panel — currently `User`, `Convite` and `Projeto`. The `mike-bronner/laravel-model-caching` package caches Eloquent queries when `MODEL_CACHE_ENABLED=true`.

- The default is `false` (`MODEL_CACHE_ENABLED=false` in `.env.example`).
- To turn it on, set `MODEL_CACHE_ENABLED=true` and use `MODEL_CACHE_STORE=model-cache` (Redis store configured in `config/cache.php`).
- Invalidation is automatic: `save`, `update` and `delete` clear the model's cache.
- The `/admin` and `/infra` panels remain **without** model caching by default, reducing the risk of stale data on administrative screens.

```bash
php artisan modelCache:clear      # clears the model cache
```

## Development (`require-dev`)

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
| [pestphp/pest-plugin-browser](https://packagist.org/packages/pestphp/pest-plugin-browser) | the browser tests (`tests/Browser`, `tests/BrowserTenancy`) |
| [pestphp/pest-plugin-mutate](https://packagist.org/packages/pestphp/pest-plugin-mutate) | mutation testing (`pest --mutate`) |
| [pestphp/pest-plugin-phpstan](https://packagist.org/packages/pestphp/pest-plugin-phpstan) | PHPStan inside `pest` |
| [rector/rector](https://packagist.org/packages/rector/rector) + [driftingly/rector-laravel](https://packagist.org/packages/driftingly/rector-laravel) | automated rewrites (`composer refactor:preview` / `refactor:apply`) |
| [filament/upgrade](https://packagist.org/packages/filament/upgrade) | Filament's upgrade tool (`composer upgrade:filament`) |
| [laravel/boost](https://packagist.org/packages/laravel/boost) | MCP server and guidelines for the AI agents (`.ai/rules`) |

## Front-end (`package.json`)

| Package | What for |
|---|---|
| [vite](https://www.npmjs.com/package/vite) + [laravel-vite-plugin](https://www.npmjs.com/package/laravel-vite-plugin) | the asset build |
| [tailwindcss](https://www.npmjs.com/package/tailwindcss) + [@tailwindcss/vite](https://www.npmjs.com/package/@tailwindcss/vite) | the CSS (v4, no config file) |
| [concurrently](https://www.npmjs.com/package/concurrently) | runs server, queue and vite together in `composer dev` |
| [playwright](https://www.npmjs.com/package/playwright) | the browser behind the `pest-plugin-browser` tests |
| [@laravel/multiplex](https://www.npmjs.com/package/@laravel/multiplex) | batches Livewire requests (optional) |


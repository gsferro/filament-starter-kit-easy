---
title: "Code quality"
parent: "Reference"
grand_parent: "English"
nav_order: 1
---

# Code quality

## PHPStan at level 7 — and why that's a strong point

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
> **Don't solve it with `@phpstan-ignore` or a baseline.** The kit has exactly **two** exceptions in
> `phpstan.neon`: one for a vendor macro resolved at runtime (`simpleLightbox()`), the other for the
> unsatisfiable annotation of filament-breezy's `customMyProfilePage()` — each with the reason, the
> alternatives that were tried and dropped, and the test that covers the point for real. That's the
> standard: if an exception is needed, it comes with the justification and with the test that
> replaces it.
>
> If you want to loosen it in your project, it's one line in `phpstan.neon`. But know what you're
> trading away: the 29 errors above were all real.

## FilaCheck: the lint that only knows Filament

`composer filament:check` runs `laraveldaily/filacheck` — 17 rules that Pint and PHPStan have no
way of having: a deprecated Filament API method, the wrong action namespace, a call that changed
between versions. It runs inside `composer test` along with pint and phpstan, so CI fails on the
same things your machine does.

When it was adopted it found **7 pre-existing problems** in the kit itself — six deprecated test
methods and one `ImageColumn::size()` — all fixed.

## Rector: major upgrades, not linting

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
composer upgrade:filament   # runs vendor/bin/filament-v5 — filament/upgrade is already in require-dev
```

It is kept in lockstep with the framework — whoever writes the rules is whoever breaks the API.

The full reading on the four tools is in
[`wikis/qualidade-de-codigo.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/qualidade-de-codigo.md) (pt-BR).

## The kit's tests

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

## The README images come out of a test

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

- **`KIT_ART=1` is not decoration.** It is a test-only variable — it exists neither in `config/` nor
  in `.env.example`; the test file itself reads it. Without the variable the file is *skipped*. It writes into
  `art/`, and a CI suite that dirties the working tree is worse than a slow one.
- **The sizes are fixed: 1400x875 full, 760x475 thumb.** That is the ratio of the images already in
  `art/`, and the gallery puts two thumbs per row — a thumb with a different ratio breaks the table.
- **The GIF is a slideshow**, assembled with `ffmpeg` from three frames. The browser plugin does not
  record video, and captured frames are what can be reproduced deterministically. With no `ffmpeg`
  on the PATH the command warns and moves on: the static images were already published.

Only need to redo the thumbs, without repeating the navigation? `php artisan kit:arte --sem-gif`.

## How tests are thought out: SFDIPOT sweep

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


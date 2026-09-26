---
title: "Code quality"
description: "Most Laravel projects stop at level 5 or 6. The kit runs at 8, with zero errors and no baseline: there is no @phpstan-ignore scattered around, no…"
sidebar:
  order: 1
---
## PHPStan at level 8 — and why that's a strong point

Most Laravel projects stop at level 5 or 6. The kit runs at **8, with zero errors and no
baseline**: there is no `@phpstan-ignore` scattered around, no `phpstan-baseline.neon` hiding debt.

What levels 7 and 8 catch and 6 doesn't, in practice:

- **Unchecked null** (what 8 adds). `Filament::getCurrentPanel()` returns `?Panel`;
  `auth()->user()` returns `?User`; `Panel::getLoginUrl()` returns `?string`. Up to level 7 you call
  a method on them and it passes. At 8, you have to prove it exists.
- **A wide vendor type leaking into your code.** `session()` is `mixed`, `env()` is `bool|string`,
  Shield's getters are `?array`. 7 forces you to narrow it at the **boundary**, once, instead of
  hoping the value is what you expect at every use.
- **`list<T>` vs `array<int,T>`.** `filter()` and `map()` preserve keys. An array with holes handed
  where a list was expected is a bug that only shows up at `json_encode` — it turns into an object
  instead of an array, and the front end breaks.

Going from 6 to 7 exposed **29 real errors** in the kit, and one of them was a genuine latent bug: a
`Convite|null` with a method called straight on it. Going from 7 to 8 exposed **48** more, all of them
about nullability: 27 were an impossible null written with a type that was too wide, 7 were a panel
URL that is `null` on a panel without `->login()` — they now get the destination the kit already
uses, the panel root —, 13 were an invariant with no guard, and 1 was a wrong Laravel annotation. All
fixed at the source — none silenced.

> ### ⚠️ Watch out when implementing this in your project
>
> **Level 8 applies to the code you write too.** `composer test` runs
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
> **Don't solve it with `@phpstan-ignore` or a baseline.** The kit has exactly **four** exceptions in
> `phpstan.neon`: a vendor macro resolved at runtime (`simpleLightbox()`), an extension point with no
> use inside the kit (`WidgetDinamico`), the unsatisfiable annotation of filament-breezy's
> `customMyProfilePage()`, and the `null` in the `@return` that Laravel's `EnsureEmailIsVerified`
> declares and never returns — each scoped to one file, with the reason, the alternatives that were
> tried and dropped, and the test that covers the point for real. `tests/Kit/QualidadeDeCodigoTest.php`
> locks the inventory. That's the
> standard: if an exception is needed, it comes with the justification and with the test that
> replaces it.
>
> If you want to loosen it in your project, it's one line in `phpstan.neon`. But know what you're
> trading away: the 77 errors above were all real.

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
| **PHPStan** + larastan | types | reports | always (gate), **level 8** |
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

## Test coverage — and what the number leaves **out**

**81 %** of the lines in `app/` — 8,141 of 9,976 statements, measured on 2026-09-25. The README
badge comes from here, and CI fails when it lies.

> **The number moves slightly with the operating system**, and the badge was designed to absorb
> that: in a 2026-09-25 measurement, the same tree gave **79.89 % on Windows and 79.82 % on Linux**
> — 7 statements apart, from
> code that only runs on one platform. Both truncate to `79%`, so the badge matches on either. Had
> it stored the decimal, CI would fail every measurement taken on the other platform.

> The figures with decimals on this page are **dated**, not derived: they hold for the 2026-09-25
> measurement and they age. The only one with an automatic guard is the whole percentage, pinned
> against `.github/badges/cobertura.json` by `[CT-49]`.

```bash
composer test:coverage    # measures, writes the badge and applies the floor — ~25 min, serial

# the two halves, separately, when you already have the Clover:
php -d pcov.enabled=1 vendor/bin/pest --testsuite=Kit,Tenancy --no-tia --coverage-clover=cobertura.xml
php artisan kit:cobertura cobertura.xml --min=78          # checks (this is what CI does)
php artisan kit:cobertura cobertura.xml --min=78 --write  # writes the badge
```

`kit:cobertura` **travels with the kit**: in your project it applies whatever floor you choose,
over whatever `phpunit.xml` you have. The badge part is the only kit-only piece — outside the
kit's tree `.github/badges/` does not exist, and the command simply leaves badges alone.

### The three test badges in the README, and what each one guarantees

| Badge | Where it comes from | What keeps it from ageing |
|---|---|---|
| **coverage** | `.github/badges/cobertura.json`, produced by `composer test:coverage` | the `cobertura` CI job, which re-measures and fails if the JSON lies |
| **test cases** | count of `it()`/`test()` blocks under `tests/` | `[CT-50]`, which **recomputes it from the tree** on every suite run |
| **PHPStan** | the `level:` in `phpstan.neon` | `[CT-50]`, by the same mechanism |

The last two depend on running nothing — they are derivable by reading, so the guard works out the
truth by itself in milliseconds. The coverage one depends on the ~25 min measurement (52 min on
the CI runner), which is why
it lives in a versioned file checked by CI.

> **The case badge counts blocks written, not cases executed.** An `it()` with a five-row
> `->with()` becomes five cases at run time — which is why the badge number is lower than what Pest
> prints. It is the quantity that can be derived without running the suite, and it is what the
> badge means to say: how many scenarios were written by hand.

### Why serial, and why it takes that long

`--parallel --coverage` **does not exist**: Pest prints the paratest *usage*, and
`artisan test --parallel --coverage-clover` runs and produces no file at all. The measurement is
serial by construction, and the cost was measured on both sides:

| Where | Parallel, no coverage | Serial, with coverage |
|---|---:|---:|
| local machine, 16 cores | ~3 min | **25 min** |
| CI runner, 4 vCPU | ~6 min | **52 min** |

That is why the job runs on `main` and on manual dispatch, not on every PR: **52 minutes** is the
number that matters, because that is where the bill is paid, and charging every PR for it would
multiply the repository's CI time ninefold.

The driver is **PCOV**, not Xdebug: for **line** coverage Xdebug adds nothing and costs far more.
It stays off in `php.ini` (`pcov.enabled=0`) and is switched on per invocation, so the everyday
suite does not pay the **+44%** instrumentation cost.

### The number is a floor, not a ceiling

Three things sit outside the count, and reading the number without knowing that leads to the wrong
conclusion:

| Outside the measurement | Why |
|---|---|
| `tests/Browser` and `tests/BrowserTenancy` | they run against a server in **another process**; PCOV instruments the Pest process |
| The installation tests | they run a real `composer create-project`, also in another process |
| `tests/Unit` and `tests/Feature` | they are **yours**, not the kit's — one example file each |

The effect shows in the breakdown: **`app/Console` reports 34%** and is, in practice, the most
exercised code in the kit — the installation tests run the whole `kit:install` from the outside.
Reading it as *"the commands have no tests"* would be precisely the mistake this section exists to
prevent.

The **real** gap is elsewhere: `app/Policies`, at **23%**. Policies are exercised indirectly (the
screen denies, the test sees the denial), but `Gate` short-circuits before the method in most
cases — and authorisation is where a silent defect costs the most.

### The floor is 78%, and it is not folklore

With 9,976 statements, **1 percentage point is worth ~100 lines**. A bug fix, a new method or a
refactor do not move the number; to lose the 1.89 pp of slack, nearly 190 statements have to land
untested — which is the only event the floor exists to catch.

And line coverage does **not** measure whether the suite detects defects: it measures what was
**executed**, not what was **verified**. That question belongs to the mutation score, for which the
kit uses `pest --mutate` (on Windows, through `pestw.cmd` at the root — its header explains why).

## The mutation score, and what it answers that coverage does not

Coverage measures **what ran**. Mutation testing measures **what was verified**: it changes the code
on purpose — swaps a `<` for `<=`, deletes a call, flips an `&&` — and asks whether any test turns
red. A surviving mutant is a defect nobody would catch.

```bash
# Linux and macOS
vendor/bin/pest tests/Kit/SomeTest.php --mutate --path=app/Support/X.php --covered-only --no-tia

# Windows — through the launcher at the root; its header explains why
cmd /c pestw.cmd tests/Kit/SomeTest.php --mutate --path=app/Support/X.php --covered-only --no-tia
```

Measurements from this tree, with the command, the duration and the platform — without all three a
score is not auditable:

| Target | Mutants | Score | Duration | Platform |
|---|---:|---:|---:|---|
| `app/Support/CustomizadorDaInstalacao.php` | 225, 4 untested and 0 survivors | **98.22%** | 42.96 s | Windows, PHP 8.4.25, PCOV |
| `app/Console/Commands/KitCobertura.php` | 158, 4 untested and 0 survivors | **97.47%** | 38.00 s | Windows, PHP 8.4.25, PCOV |
| `app/Policies/` (all 16) | **4** | 100% | 0.19 s | Windows, PHP 8.4.25, PCOV |

**A survivor is not the same as an untested mutant.** A survivor is a mutant that a test **ran**
and did not notice — a defect that would slip through. An untested mutant sits on a line that **no**
test runs. Both measurements above have zero survivors; the untested ones, named for whoever wants
to close them:

- `app/Support/CustomizadorDaInstalacao.php:470` — four mutations on the same line
- `app/Console/Commands/KitCobertura.php:57`, `:59` (missing report) and `:198` (unreadable
  report) — early exits that line coverage does not attribute to any test, even though
  `[CT-12]` and `[CT-13]` in `tests/Kit/KitCoberturaTest.php` assert both messages; the cause was
  not investigated

`KitCobertura` went from **89.24%** (17 untested, measured on 2026-09-25) to **97.47%** on
2026-09-26, when the reconciliation of the `cobertura-de-testes` wiki wrote the missing scenarios.

> **A high, instant score is a symptom, not a result.** On Windows, running through
> `vendor/bin/pest` instead of the launcher returns **100% in seconds** for a suite that takes
> minutes — the plugin relaunches `argv[0]`, `cmd` cannot execute an `sh` script, and every
> non-zero exit is counted as a killed mutant. The policies row above is honest for a different
> reason: **4 mutants for 222 statements**, because pure delegation code has no operators to
> mutate. There the instrument is not mutation — it is the assertion that each policy checks the
> permission of its **own** model.

## The quality levels the kit did **not** adopt, and why

Survey of 2026-09-25. Every candidate was **run against this tree** before entering the table —
because every quality tool looks good in the abstract, and what separates the worthwhile ones is how
much noise they produce in **this** code. An analyser that reports 594 times is not stricter than
one that reports 48; it is one nobody will switch on.

| Level | Measured here | Verdict |
|---|---|---|
| `arch()->preset()->php()` | passes clean, 53 assertions, 6.5 s | **adopted** |
| `arch()->preset()->security()` | 1 class flagged (3 `exec()` in `KitInstall`, with a constant) | **adopted**, with the exception declared and confined |
| **PHPStan level 8** | **48 errors** | roadmap, high priority — fits a release of its own |
| PHPStan level 9 / `max` | **474** and **594** errors | **no** — a cliff, not a step |
| `arch()->preset()->laravel()` | fails on the 1st class (controller with a method outside the REST set) | **no** — the kit's convention is deliberate |
| `arch()->preset()->strict()` | **163 of 222** classes are not `final` | **no** — `AgenteBase` exists to be extended |
| `arch()->preset()->relaxed()` | forbids `private` methods; **54** files use them | **no** — contradicts the kit's style |
| `declare(strict_types=1)` | **98 of 240** files | roadmap — not a lack of rigour but **inconsistency**, and it requires picking a side |
| `composer-require-checker` + `composer-unused` | not installed | roadmap, high priority — they answer *"of the 58 dependencies, which are used?"* |
| Branch coverage (Xdebug) | Xdebug 3.5.3 installed | roadmap — it is the only reason Xdebug exists here |
| `pest-plugin-type-coverage` | not installed | roadmap |
| Deptrac | not installed | **no** — the kit has no layer boundary to defend |

The three cut-off criteria, applied to every row: **(a)** does it answer a gap already measured in
this project? **(b)** is the adoption cost known? **(c)** is what it catches missed by everything
already in place? The roadmap items pass (a) and (c) and are waiting for budget; the rejected ones
fail (a) or clash with a written convention.

> The figures above age — `48 errors at level 8` is from 2026-09-25 and changes with every release.
> Whoever picks up a roadmap item **re-measures before estimating**.

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


---
title: Updating the project
parent: Getting started
grand_parent: English
nav_order: 2
---

# Updating a project born from the kit

**The kit is a starting point, not a dependency.** After `create-project` the project is yours: you rename panels, change `canAccessPanel()`, edit seeders. That's why there is **no** `kit:update` that overwrites files — it would rewrite exactly what you customized, and a starter kit that ruins the user's project is worth nothing.

What changes splits into three layers, and each one has its own path:

| Layer | What it is | How to update |
|---|---|---|
| **Dependencies** | Filament, plugins, Laravel | `composer update` — it's most of the improvements and it arrives on its own |
| **The kit's glue** | providers, traits, widgets, error views | manual diff against the new tag (below) |
| **Your business** | everything you wrote | never touched |

## The easy way: `php artisan kit:update`

The command automates the entire git step and **applies nothing without your approval**:

```bash
php artisan kit:update --dry-run   # only shows what changed
php artisan kit:update             # review and apply, file by file
```

What it does, in order:

1. **Checks the ground** — requires a git repository with a clean tree. Without that there would be no way back, so it refuses to run (showing the commands to put the project under version control).
2. **Links the kit temporarily** — adds the `kit` remote with **push blocked** and fetches the tags into a namespace of their own (`kit-v*`), so they don't collide with your project's versions.
3. **Compares** — from the version in `config('kit.version')` up to the chosen tag, restricted to the paths that belong to the kit. Your business code never enters the equation.
4. **Offers a temporary branch** (`kit-update/v0.16.0`) so yours doesn't get dirty.
5. **Asks file by file** — see the diff, apply, skip or stop. You can change your mind halfway and apply the rest in bulk. A file removed from the kit is never deleted automatically: it only warns you.
6. **Unlinks** — removes the remote and the `kit-*` tags on the way out, even if you interrupt it halfway. The project isn't left with anything third-party hanging around.

7. **Marks the applied version** in `config/kit.php` — only that line, without touching the rest of the file. It's the starting point for the next comparison.

Two details that show up in practice:

- **`config/kit.php` always shows up as "modified"** (it carries the version mark). Applying it brings the kit's new keys, but **replaces the whole file** — if you changed seeder credentials or added your own keys there, read the diff and copy only what matters instead of applying.
- **`kit:update` updates itself.** Since PHP already loaded the class into memory, the new behavior (and the new messages) only take effect on the following run. The command tells you when that happens.

At the end nothing is committed: you review with `git diff`, run `composer test:kit` (the foundation) and commit. Went wrong? `git checkout -- .` undoes it, or delete the branch and go back to yours.

**You don't have to approve 30 files one by one.** During the review the menu offers *"Apply all NEW files from here on"* and *"Apply EVERYTHING from here on"* — one confirmation covers the set. And you can start in bulk already:

```bash
php artisan kit:update --only-new   # only what doesn't exist in the project yet
php artisan kit:update --all        # everything, including what overwrites
```

The distinction is the point: **a new file has nothing to overwrite**, so applying those in bulk is safe — that's the case for the widgets, the Spotlight and the concerns. A **modified** one replaces the current content, and if you edited that file your version is lost (recoverable with `git checkout -- <file>`, since nothing is committed). That's why `--only-new` is the recommended bulk for a first pass, leaving the modified ones to review calmly.

| Option | What for |
|---|---|
| `--only-new` | applies all the new files at once (overwrites nothing) |
| `--all` | applies everything at once, with a single confirmation for the set |
| `--dry-run` | report only, changes nothing |
| `--tag=v0.16.0` | compare against a specific version |
| `--from=v0.15.0` | tell it which version the project started from (when `config/kit.php` doesn't know) |
| `--branch=name` | choose the temporary branch's name |
| `--no-branch` | apply on the current branch |
| `--keep-remote` | keep the kit's remote and tags at the end |
| `--repo=URL` | compare against another kit repository (a fork, for instance); the default is `config('kit.repository')`, which reads `KIT_REPOSITORY` from `.env` |

With no terminal (CI, `--no-interaction`) the command becomes a report and changes nothing — unless you pass `--only-new` or `--all`, which **are** the approval, given on the command line.

## The manual way

If you'd rather control every step — or understand what the command does under the hood:

Add the kit as a **second remote**, once. Your `origin` stays your project; `kit` is just a read source:

```bash
git remote add kit https://github.com/gsferro/filament-starter-kit-easy.git

# the kit's remote is read-only: it prevents an accidental `git push kit main`
# from sending YOUR project into the kit's repository
git remote set-url --push kit no_push
```

The kit's tags go into a namespace of their own (`kit-v*`). That matters: a `git fetch kit --tags` would bring `v0.15.0`, `v0.16.0`… into your project and collide with **your** versions later.

```bash
git fetch --no-tags kit 'refs/tags/*:refs/tags/kit-*'
git tag -l 'kit-*'      # kit-v0.15.0, kit-v0.16.0, ...
```

Then, at each version, see what changed and bring over only what matters:

```bash
# 1. overview between your version and the new one
git diff kit-v0.15.0..kit-v0.16.0 --stat

# 2. the diff of the kit's "glue" (ignore what you already rewrote)
git diff kit-v0.15.0..kit-v0.16.0 -- app/Providers app/Filament/Concerns \
        app/Filament/Spotlight app/Traits resources/views/errors config/kit.php

# 3. bring it over file by file, reviewing
git checkout kit-v0.16.0 -- resources/views/errors
git checkout kit-v0.16.0 -- app/Filament/Concerns/BadgeContagemNavegacao.php
```

Do this on a branch (`git switch -c update-kit`) and run `composer test` before merging. Files you rewrote: read the diff and apply by hand — it's the only safe path.

> 💡 **TODO / where the project is heading:** extract the "glue" into a Composer package of its own (`gsferro/kit-core`) with the providers, traits, widgets and infra pages. Then the middle layer becomes `composer update gsferro/kit-core` and the skeleton stays minimal — only what really is a starting point. It's this kit's natural evolution.


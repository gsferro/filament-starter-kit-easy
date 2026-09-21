---
title: Starter Kit Easy
description: A Laravel 13 + Filament 5 starter kit with three separate panels, invitations, social login, anti-robot protection and opt-in multi-tenancy.
template: splash
hero:
  tagline: Three separate panels, invitations, social login, anti-robot protection and opt-in multi-tenancy. One command to install.
  actions:
    - text: Install
      link: comecar/instalacao-avancada/
      icon: right-arrow
    - text: View on GitHub
      link: https://github.com/gsferro/filament-starter-kit-easy
      icon: external
      variant: minimal
---

```bash
composer create-project gsferro/starter-kit-easy my-project
cd my-project && php artisan kit:install
```

![The kit login screen](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/login.png)

## Where to start

<div class="cartoes-do-kit">

[**Getting started** — advanced installation, updating a project already born from the kit, and the local domain.](comecar/)

[**Authentication** — invitations, open registration with approval, social login, anti-robot protection and the single login page.](autenticacao/)

[**Features** — opt-in multi-tenancy, attachments and media, CSV import and export, the `/infra` trails and the settings screen.](recursos/)

[**Operations** — working with AI agents, kit conventions, what to do after creating your Resources, and how to develop the kit itself.](operacao/)

</div>

## What ships with it

<div class="cartoes-do-kit">

**Three panels** — `/app` for people who use it, `/admin` for people who administer it and `/infra` for people who operate it, genuinely separate, each with its own set of permissions.

**Multi-tenancy is opt-in** — it ships off. `php artisan kit:tenancy` turns it on, and it is an install-time decision: the permission tables only grow the context column if it exists before `migrate`.

**Permissions ready** — Filament Shield seeded with roles, a per-panel matrix and a screen to edit all of it.

**Configuration on screen** — identity, mail, tables, registration and login live in `/admin/configuracoes-da-aplicacao`. No `.env`, no deploy.

</div>

---

The code lives at
[github.com/gsferro/filament-starter-kit-easy](https://github.com/gsferro/filament-starter-kit-easy).
The `README.md` still exists as the package's short pitch — it is what Packagist shows.

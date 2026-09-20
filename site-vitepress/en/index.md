---
layout: home
title: Starter Kit Easy
titleTemplate: Laravel 13 + Filament 5

hero:
  name: Starter Kit Easy
  text: Three panels, production ready
  tagline: Invitations, social login, anti-robot protection and opt-in multi-tenancy. One command to install.
  actions:
    - theme: brand
      text: Install
      link: /en/comecar/instalacao-avancada
    - theme: alt
      text: View on GitHub
      link: https://github.com/gsferro/filament-starter-kit-easy

features:
  - title: Three separate panels
    details: "/app for people who use it, /admin for people who administer it and /infra for people who operate it — genuinely separate, each with its own set of permissions."
  - title: Multi-tenancy is opt-in
    details: It ships off. One command turns it on — and it is an install-time decision, because the permission tables only grow the context column if it exists before migrate.
  - title: Permissions ready
    details: Filament Shield seeded with roles, a per-panel matrix and a screen to edit all of it.
  - title: Configuration on screen
    details: Identity, mail, tables, registration and login live in /admin/configuracoes-da-aplicacao. No .env, no deploy.
  - title: Complete authentication
    details: User invitation, open registration with manual approval, four social login providers and a single login page.
  - title: Observability under /infra
    details: Trails for exceptions, sent mail and deleted records, each screen with its own permission.
---

```bash
composer create-project gsferro/starter-kit-easy my-project
cd my-project && php artisan kit:install
```

![The kit login screen](/art/login.png)

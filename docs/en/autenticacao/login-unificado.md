---
title: Single login page
parent: Authentication
grand_parent: English
nav_order: 7
---

# Single login page (`/login`), optional

By default each panel has its own door — `/admin/login`, `/infra/login` and `/app/login` —, which
is Filament's behavior. With the switch on, all three lead to **`/login`**, a single login screen
(same artwork, same anti-robot protection, same social login buttons), and the kit decides where the
person goes **after** signing in.

## How to turn it on

Two ways, same key:

```dotenv
KIT_LOGIN_UNIFICADO=true
```

or, in `/admin/configuracoes-do-kit` → **Login** tab → "Unificar o login em /login". The toggle
takes effect immediately, no deploy: the key is read on every request, not at boot. Only `true`
and `1` turn it on — any other value in the `.env` keeps it off.

**Once installed, the screen is in charge.** Like every login key of the kit, the `.env` only
provides the **initial** value: the Settings migration seeds `login_unificado` with whatever the
`.env` said at install time, and from then on the database value overrides the `.env` on every
boot (`ConfiguracoesDoKit::aplicarNaConfig()`). Changing `KIT_LOGIN_UNIFICADO` on an existing
installation does **not** turn anything on or off — use the toggle (or `kit:install --force`,
which recreates the database). Measured on the feature's test installations.

**Project created before v0.31.0**: `php artisan kit:update` brings the migration
`database/settings/*_add_login_unificado_to_kit_settings.php` along with the code. Run
`php artisan migrate` before opening the settings screen — without the row in the database it
breaks. The seeded value is whatever the `.env` said at that moment (`false` if the key is absent).

## What changes for whoever signs in

| Situation | What happens |
|---|---|
| Opens `/admin/login`, `/infra/login` or `/app/login` | is taken to `/login` |
| Signs in and can access **one** panel | goes straight into it — or to the URL requested before login, if it belongs to that panel |
| Signs in and can access **more than one** | `/login/painel`: a screen with one card per accessible panel (the same cards as the welcome page) |
| Signs in and can access **none** | refused, as today ("invalid credentials"; inactive or deleted accounts get the usual explanation) |
| Is already signed in and opens `/login` | goes where they would go after signing in |

The rule "may this person enter this panel?" is the usual one — `User::canAccessPanel()` —, except
the single page asks about **any** panel instead of the current one. An `admin` with no role in
`/app` signs in through `/login` and lands in `/admin`.

The intended URL (you opened `/admin/users` without a session) only wins when it belongs to a
panel you can access, on the same host. Otherwise it is discarded and the rule above applies.

## What stays per panel

- **Password reset, invitation-based registration, e-mail verification**: the screens stay inside
  the panels. They redirect to the panel login when needed, and that login leads to `/login`.
- **2FA (Breezy) and the lock screen**: happen **inside** the chosen panel, as today. The choice
  screen shows up before the second-factor challenge — it only lists panels; entering one triggers
  the challenge as usual.
- **Social login**: with the switch on, the button on the single page carries no panel of origin
  (the provider must be enabled, on any panel), and the destination follows the same rule as the
  table above **restricted to the panels the provider is allowed in** — GitHub enabled only for
  `/infra` never drops anyone into `/admin`; with no allowed accessible panel, the session is
  ended. With the switch off, everything as in [Social login](login-social.md).

## The access log records the panel the person entered

The `authentication_log` stamps the panel of every access (it feeds "accesses per panel" in the
organization insights and the daily logins stat). The single page runs in the default panel's
context only for theme and layout — that does **not** go to the log: a login that starts at
`/login` is born without a panel and receives the panel it **entered** — the only accessible one,
the one of the intended URL, or the card clicked on the choice screen. While the person is on the
choice screen, the access has no panel; if they close the tab without choosing, it stays that way
(a login that entered no panel).

## Heads-up: external SSO is not pre-configured yet

The single page covers the kit's password login and social login (Google, GitHub, LinkedIn, X). An
**external SSO** — SAML, corporate OpenID Connect, Keycloak, Entra ID — that authenticates the
person outside Filament and hands them back with a live session does **not** go through `/login`
nor through the destination rule: they would land on the default panel and, without access to it,
see a 403. If you integrate an SSO yourself, make its callback redirect to the same decider the
single page uses (the after-login destination class, documented in
`wikis/specs/feat/login-unificado/`) instead of to a fixed panel URL. Pre-configured external SSOs
are a planned kit item.

## If you want to go back

Turn the toggle off. Nothing was migrated or stored beyond the Settings property; the `/login` and
`/login/painel` routes keep existing — when off, `/login` redirects to the default panel's login
and `/login/painel` to the default panel itself.

Details and decisions: `wikis/specs/feat/login-unificado/` in the repository.

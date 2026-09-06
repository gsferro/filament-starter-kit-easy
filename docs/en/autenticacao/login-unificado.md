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
  table above. With the switch off, everything as in [Social login](login-social.md).

## One consequence for the access log

The single page runs in the default panel's context (`app`) — that is what gives a screen outside
the panels its theme, colors and login layout. So, with the switch on, the authentication log
records panel `app` for every password login, not the panel the person picked afterwards. The
per-panel breakdown in the organization insights reflects that.

## If you want to go back

Turn the key (or the toggle) off. Nothing was migrated or stored beyond the Settings property; the
`/login` and `/login/painel` routes keep existing — when off, `/login` just redirects to the default
panel's login.

Details and decisions: `wikis/specs/feat/login-unificado/` in the repository.

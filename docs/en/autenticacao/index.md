---
title: "Authentication"
description: "How someone gets in — and how someone stops getting in: invitation, open registration with approval, social login across the four providers, the optional…"
sidebar:
  label: "Overview"
  order: 0
---
How someone gets in — and how someone stops getting in: invitation, open registration with
approval, social login across the four providers, the optional single login page (`/login`),
anti-robot protection, the three account states and what the public `/` route shows (and what it
deliberately does not).

The exact order of the panel access checks — unavailability, pending approval, `master_global`,
role — is in the [Panel access rule](../referencia/arquitetura-em-diagramas/) diagram (DG-03), on
the architecture diagrams page.

## Sequence: password login and 2FA

```mermaid
sequenceDiagram
%% DG-04
accTitle: Password login and second factor
accDescr: The canAccessPanel() check comes before any 2FA challenge, which only shows up inside a conditional block on the next request to the panel.
  participant visitante as Visitor
  participant tela_login as Login screen
  participant vendor_login as Login (Filament)
  participant authenticate as Authenticate
  participant authenticate_session as AuthenticateSession
  participant must_two_factor as MustTwoFactor
  participant locker as Locker
  participant exigir_email as ExigirEmailVerificado (/app, KIT_REGISTRO_VERIFICAR_EMAIL)
  participant resposta_login as RespostaDeLogin
  visitante->>tela_login: credentials
  tela_login->>vendor_login: authenticate()
  note over vendor_login: rate limit 5 attempts
  alt account unavailable (correct password)
    vendor_login-->>visitante: refusal (403) via ContaIndisponivelController
  else canAccessPanel() allows
    vendor_login->>resposta_login: attemptWhen, session regenerated
    resposta_login-->>visitante: decides the destination
    visitante->>authenticate: next request to the panel
    authenticate->>authenticate_session: session validated
    authenticate_session->>must_two_factor: 2FA check
    alt 2FA turned on for the account
      must_two_factor-->>visitante: 2FA challenge
    end
    must_two_factor->>locker: checks lock (idle/manual)
    locker->>exigir_email: e-mail verified?
  end
```

The kit's login screen intercepts the Filament failure only to explain an unavailable account
(`app/Filament/Pages/Auth/TelaLogin.php:authenticate:196`); the 5-attempt rate limit is Filament's
own (`vendor/filament/filament/src/Auth/Pages/Login.php:rateLimit(5):70`), which also decides with
`canAccessPanel()` (`vendor/filament/filament/src/Auth/Pages/Login.php:canAccessPanel:172`). The
login destination is always `app(LoginResponse::class)`, bound to `RespostaDeLogin`
(`app/Providers/KitServiceProvider.php:LoginResponse:838`). The next request's stack (real order,
checked with `php artisan route:list --json`) is `Authenticate` -> `AuthenticateSession` ->
`MustTwoFactor` -> `Locker` -> `ExigirEmailVerificado` (mandatory only on `/app`, with the key on).

## Authenticated session

```mermaid
stateDiagram-v2
%% DG-10
accTitle: Authenticated session
accDescr: After login, the session moves between authenticated, awaiting 2FA, locked and ended, depending on idle time, manual lock and password attempts.
  state "Authenticated" as autenticada
  state "Awaiting 2FA" as aguardando_2fa
  state "Locked" as bloqueada
  state "Ended" as encerrada
  [*] --> autenticada
  autenticada --> aguardando_2fa : has not confirmed 2FA yet
  aguardando_2fa --> autenticada : confirms 2FA
  autenticada --> bloqueada : idle for 1800s or manual lock
  bloqueada --> autenticada : correct password
  bloqueada --> encerrada : 5 wrong attempts (force logout)
  autenticada --> encerrada : manual logout
```

The 2FA challenge only exists for whoever already confirmed it before — it is the same
`MustTwoFactor` from the diagram above. The idle lock uses `config('lockscreen.idle_timeout')` =
1800 (`config/lockscreen.php:idle_timeout:16`); the three panels share the same attempt limit with
forced logout (`app/Providers/Filament/AdminPanelProvider.php:enableRateLimit:267`,
`app/Providers/Filament/AppPanelProvider.php:enableRateLimit:381`,
`app/Providers/Filament/InfraPanelProvider.php:enableRateLimit:290`).

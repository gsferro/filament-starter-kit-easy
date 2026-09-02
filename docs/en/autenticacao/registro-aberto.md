---
title: Open registration and approval
parent: Authentication
grand_parent: English
nav_order: 2
---

# Open registration and approval

Invitation is the kit's default door. The second door — **open sign-up**, with no invitation —
exists, and it **ships off**:

```dotenv
KIT_REGISTRO=false                      # the public door
KIT_REGISTRO_APROVACAO_MANUAL=false     # born pending until someone approves?
KIT_REGISTRO_VERIFICAR_EMAIL=false      # require a verified e-mail on /app? (also editable in the UI)
```

With `KIT_REGISTRO=false`, `/app/register` answers **only** to whoever brings a valid invitation
token — with no token it refuses and sends the visitor to the login. That is what the kit always
did, and nothing in this section happens until you flip the key.

## Both doors share one screen

`/app/register` picks the path by the **absence** of the `token` parameter:

| URL | With `KIT_REGISTRO=false` | With `KIT_REGISTRO=true` |
|---|---|---|
| valid `?token=` | invitation acceptance | invitation acceptance (same) |
| invalid, expired or used `?token=` | refuses → login | **refuses → login** (same) |
| no `token` | refuses → login | sign-up form |

The second row is deliberate: an invalid token **never** falls through to open sign-up. If it
did, `?token=anything` would be a second entrance to the public door — precisely the one that
skips the refusal's rate limit and the generic message that does not reveal whether the token
does not exist, expired or was already used.

## What turning `KIT_REGISTRO=true` makes ripple

| Where | What changes |
|---|---|
| `/app/register` with no token | shows the form instead of refusing |
| `/app/login` | starts offering the "Create account" link (hidden before, because it led to a screen that refuses) |
| the role a new account gets | **only** `panel_user` — no other profile, and 403 on `/admin` and `/infra` |
| `/admin/organizacoes` (with tenancy) | an *"Accepts public sign-up"* field appears on each organization |
| the users screen (`/admin` and `/app`) | gains the **Status** column, the *"Pending only"* filter and the **Approve** action |
| the `autenticacao` log channel | starts recording every sign-up and every approval, with the e-mail masked |

## The role a new account gets, and nothing beyond it

Whoever comes through this door receives **one single** role: `panel_user`, the basic profile of
the business panel. Not `admin_app`, and no reach into `/admin` or `/infra` — both answer
**403**. Administrators adjust roles afterwards, on the users screen, which is where that
decision belongs.

The assignment happens in a single place (`App\Support\RegistroAberto::papel()`), and it holds
for anyone calling registration from outside the screen too — a command, a job, a seeder.

## Manual approval: pending means no panel at all

With `KIT_REGISTRO_APROVACAO_MANUAL=true` the account is born **pending**:

- it gets no role;
- the session the sign-up opened is ended right away, and the person returns to the login with a
  message saying the account awaits release — instead of getting a 403 right after a sign-up
  that worked;
- `/app`, `/admin` and `/infra` all answer **403** while it is pending.

To release it, open the users screen, filter by *"Pending only"* and use the **Approve** action.
Approving grants the business panel role (inside the current organization when tenancy is on)
and is idempotent — clicking twice does not duplicate the role. The action requires the
**edit user** permission, so the ordinary `/app` user never sees it.

> While an account is pending it has **no role**, and the edit form knows that: the *Roles*
> field, normally required, stops being required in that case. Without the exception, editing a
> pending account would be impossible to save, and the only way out would be granting a role by
> hand — which grants access without going through the approval.

## Sign-up per organization (multi-tenancy)

With multi-organization on, **two** conditions hold together: the installation allows sign-up
(`KIT_REGISTRO=true`) **and** each organization opts in, through the *"Accepts public sign-up"*
field on its own screen. Every organization defaults to **no** — flipping the global key does not
open sign-up on any existing organization without someone deciding.

The address carries the slug:

```text
/app/register?org=acme
```

With no `?org`, an unknown slug, an **inactive** organization or sign-up **off** for it, the
screen returns **the same** refusal — a visitor cannot tell which condition failed. Which also
means: on a multi-organization installation, publishing `/app/register` without `?org=` leads
people to the refusal; publish the organization's link.

This is not the same as *creating* an organization: signing up **into** one is not creating it,
and creating organizations remains the job of whoever administers the installation, via `/admin`.

## E-mail verification (optional)

Requires a confirmed e-mail address to enter `/app`. **Editable in
`/admin/configuracoes-do-kit` → Registro tab**, and the stored value applies on the **next
request** — no deploy. `KIT_REGISTRO_VERIFICAR_EMAIL` still exists: it seeds a fresh installation
and is the fallback, like the other settings on that screen.

> Up to v0.19.3 this key worked **only** through `.env`, and the screen said so. The reason was
> real: Filament pins the e-mail-verified middleware into the route's middleware array at
> registration time, so the decision was made at boot and a toggle on the screen saved without
> taking effect. The fix was to take the *decision* out of the route array and put a *decider*
> there instead — `App\Http\Middleware\ExigirEmailVerificado`, which asks on every request. As a
> consequence, the confirmation screen now always exists.

**Read this part before turning it on with people inside — one click is now enough.** The
requirement applies to **every** `/app` user, not only to newly registered ones, and it does
**not** depend on open sign-up being enabled: anyone without `email_verified_at` is taken to
the confirmation screen. On a clean installation that hits nobody, because the paths the kit uses
to create users already fill the column — the admin seeder, the factory, the demo seeder,
invitation acceptance and `kit:admin`. The one that does **not** is manual creation through the
users screen.

To mark an existing base as verified before turning it on:

```bash
php artisan tinker --execute 'App\Models\User::whereNull("email_verified_at")->update(["email_verified_at" => now()]);'
```

**People who came through an invitation are never affected**, and the reason is worth knowing:
`Convite::aceitar()` fills `email_verified_at` on purpose — the token arrived at that person's
address, so possession is already proven, and asking for the same proof twice is friction with no
gain. Filament only sends the confirmation request to users who have not verified yet, so an
invited user never receives that e-mail.

## The rate limit

Submitting the form uses Filament's own limit: **2 attempts per IP** and 2 per e-mail address per
window — the same one invitation acceptance already used. The refusal for a missing invitation has
its own limit (5 per 10 minutes per IP), which protects the **log file** against an anonymous
loop without changing what the person sees.

## Where this lives in the code

| What | Where |
|---|---|
| the three options, read in one place | `app/Support/RegistroAberto.php` |
| the screen, with both modes | `app/Filament/Pages/Auth/RegistroPorConvite.php` |
| the pending barrier | `App\Models\User::canAccessPanel()` — first statement |
| the release | `App\Models\User::aprovar()` |
| column, filter and approve action | `app/Filament/Concerns/AprovacaoDeCadastro.php` |
| the per-organization field | `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php` |

> **If you are wiring these options into a Settings screen**: `App\Support\RegistroAberto` is the
> single read point. Swapping `config()` for Settings means rewriting the body of three methods in
> that file — nowhere else in the project reads those keys, and a test fails if anything starts to.


---
title: "The /infra trails: exceptions, mail and recycle bin"
parent: Features
grand_parent: English
nav_order: 4
---

# The `/infra` trails: exceptions, mail and recycle bin

The infrastructure panel already showed **health** (Health), **performance** (Pulse), **the log
file** (Logs Explorer) and **queues** (Jobs Monitor) — and none of them answered "which exception
is blowing up, and how often", "did the invitation arrive?" or "can that delete be undone?". Three
screens answer one of those each:

| Screen | Where | What it answers |
|---|---|---|
| **Exceptions** | `/infra`, *Observability* group | exceptions grouped by type and frequency, with a count badge in the menu |
| **Mail trail** | `/infra`, *Trails* group | every e-mail the kit sent — separates "it was never sent" from "it was sent and landed in spam" |
| **Recycle bin** | `/infra`, *System* group | restores records deleted with `SoftDeletes` |

## Both trails store sensitive data

That is why they are only **reachable** on `/infra`, where getting in already requires the `master_global`
or `infra` role — on `/app` any panel role would see them. The `ExceptionResource` route exists on all
three panels (`/admin/exceptions`, `/app/{tenant}/exceptions`, `/infra/exceptions`); the barrier is the
permission subtraction in `database/seeders/PapeisSeeder.php`, not the absence of the screen:

- the exception's **stack trace** can carry request parameters, therefore personal data;
- the e-mail's **body** is stored, and the access invitation carries the acceptance link.

## Retention: the number is the intent, the scheduler is the execution

Both tables grow per event — a bug in a loop fills the disk in hours. That is why pruning has a
deadline, in `config/kit.php`:

| Key | `.env` | Default |
|---|---|---|
| `kit.retencao.excecoes_em_dias` | `KIT_RETENCAO_EXCECOES_DIAS` | 14 |
| `kit.retencao.emails_em_dias` | `KIT_RETENCAO_EMAILS_DIAS` | 14 |

The 14 days follow the `days` of the log rotation in `config/logging.php`: the trail dies together
with the log that produced it, not after it. **Zero or negative turns pruning off** for that trail —
and then the table grows with no ceiling, which is a choice, not an oversight.

> ⚠️ **The scheduler is what applies retention.** The routines are in `routes/console.php`; without
> `php artisan schedule:work` (or the docker compose `scheduler` service) the number in the config
> is only a declared intent.

## The recycle bin lists what you declare

`RevivePlugin` takes an **explicit list** of models in
`app/Providers/Filament/InfraPanelProvider.php` — today only `App\Models\Projeto`, the kit's only
model with `SoftDeletes`:

```php
RevivePlugin::make()
    ->navigationGroup('Sistema')
    ->navigationLabel('Lixeira')
    ->models([
        Projeto::class,
    ])
    ->withoutScoping(),
```

**A new model with `SoftDeletes` has to go into that list**, otherwise it ends up deleted with no
screen to restore it from. Automatic scanning of `app/Models` was avoided on purpose: it would
reach `User`, `Role` and `Tenant`, whose restoration has an **authorization** consequence — a user
comes back with a role in an organization that may no longer exist. The lock is the list, just like
the Command Center's allow-list.


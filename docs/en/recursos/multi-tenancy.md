---
title: "Multi-tenancy (opt-in)"
parent: Features
grand_parent: English
nav_order: 1
---

# Multi-tenancy (opt-in)

The kit is born **single-tenant**. One command turns multi-tenancy on — and those who don't need it pay nothing for it:

```bash
php artisan kit:tenancy          # turn it on
php artisan kit:tenancy --demo   # turn it on + create a demo scenario
php artisan kit:tenancy --force  # confirms the database recreation without asking
```

> `--demo` also writes `KIT_DEMO=true` to `.env`. That key is what makes the sample **Projetos**
> resource show up on `/app` — without it the business panel stays empty, which is the kit's design.
> To hide the demo without deleting anything, `KIT_DEMO=false`; to remove it for good, delete the
> files the command lists at the end.

| Panel | With the mode on |
|---|---|
| **App** | becomes `/app/{tenant}`. Users only see the tenants they're linked to, and it gains the **administration of their own organization** |
| **Admin** | gains the tenant CRUD and the **user linking** — not scoped, whoever administers sees them all |
| **Infra** | unchanged: health, queues and logs belong to the installation, not to a client |

## Administering one organization is not administering the installation

The kit's five roles, and what each one means with the mode on:

| Role | Panel | Assignment context | What it does |
|---|---|---|---|
| `master_global` | all | global | beats any permission, via `Gate::before` |
| `admin` | `/admin` | global | users, roles and permissions of the **installation** |
| `infra` | `/infra` | global | health, queues, logs, auditing, commands |
| `admin_app` | `/app` | **the organization** | users and invitations **of their own organization** |
| `panel_user` | `/app` | the organization | uses the business; doesn't see the administration |

`admin_app` is the persona multi-tenancy creates: someone who administers **one** organization without administering the system. Inside `/app/{slug}` they gain **Users** and **Invitations**, scoped to that organization — and nothing beyond that. They don't enter `/admin` or `/infra`, get a 404 on another organization's panel, can't reach an outside user even by direct URL, don't create or edit roles (they only assign, and only `/app` panel roles), don't delete users — deleting would remove the person from **every** organization — and any invitation they create is stamped with their organization, ignoring the form.

The role only exists with tenancy on, and it is granted in `/admin` → organizations → **Linked users** → *Roles in this organization*. **Not** from the user record: there the assignment goes to the global context and the person enters `/app` seeing nothing. The full recipe, with the symptom, is in [`wikis/receitas.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/receitas.md#promover-alguém-a-admin-de-uma-organização).

## English in the code, your language in the UI

The code follows Filament's API vocabulary — model `Tenant`, table `tenants`, `getTenants()`, `canAccessTenant()` — so the official docs read without mental translation. **What the user sees is configurable**, and defaults to "Organização":

```php
// config/kit.php
'tenancy' => [
    'label'        => 'Company',    // Organization · Client · School · Unit · Store
    'label_plural' => 'Companies',
    'slug'         => 'companies',  // /admin/companies
],
```

The same four entries exist in `.env`, as seed and fallback: `KIT_TENANCY` (the flag, written by
`kit:tenancy`), `KIT_TENANCY_LABEL`, `KIT_TENANCY_LABEL_PLURAL` and `KIT_TENANCY_SLUG`.

## In your models

Every business model uses the kit's trait:

```php
use App\Traits\BelongsToTenant;

class Projeto extends Model
{
    use BelongsToTenant;

    protected $fillable = ['nome'];   // `tenant_id` stays out: the trait fills it
}
```

It provides the `tenant()` relationship, a **global scope** and automatic `tenant_id` filling. The scope matters because Filament only scopes what goes through a Resource — jobs, commands, listeners and APIs would be left out, and that's exactly where one client's data leaks into another's.

> ⚠️ **`kit:tenancy` recreates the database.** It turns on `permission.teams`, and the spatie migration only creates the tenant columns if the flag is active **before** the migrate. That's why it requires a clean git tree, an explicit confirmation, and runs `migrate:fresh --seed`. **The time to run it is day 1 of the project.** The detailed path — including global vs. per-tenant roles and `scopedUnique()` — is in [`wikis/arquitetura.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/arquitetura.md#multi-tenancy-opt-in) (pt-BR).


---
title: "After creating your Resources"
parent: "Operations"
grand_parent: "English"
nav_order: 4
---

# After creating your Resources

```bash
php artisan make:filament-resource Produto --panel=app
php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
```

## New package with a Resource: the policy must be registered

Laravel discovers policies by convention only for `App\Models\*`. A **package** resource — the audit
trail, mail logs, queues — has its model in a vendor namespace, and the `App\Policies\XPolicy` you
write for it **is consulted by nothing** until someone calls `Gate::policy()`. The permission shows up
on the roles screen, and decides nothing.

That is how the kit shipped for several versions, and the Blueprint adherence audit (v0.21) caught
it: eight `/infra` and `/admin` screens opened with the permission revoked. The fix is
`App\Support\PoliciesDeVendor`, a `model => policy` map registered at boot. When installing a package
with a resource, add the line there — and check two things on the package's resource:

- `$shouldSkipAuthorization = true` disables the policy entirely (Composer Release had it; the kit
  subclasses it with `false` **and** with the page pointing at the subclass);
- `canAccess()` overridden without `&& parent::canAccess()` disables the policy for the index only.

`tests/Kit/PermissoesDeResourcesTest.php` fails for a new resource without a registered policy and
for a resource that opens with `ViewAny` revoked — naming the resource.

**Both, in this order, every time.** The first runs `shield:generate --all` on **each** panel and writes the policies; the second slices the matrix by the panel the Resource is registered on and hands the permissions back to the roles. The first one alone creates the permission and gives it to nobody — the screen stays at 403 for anyone who isn't `master_global`. Both are idempotent: running them again is normal operation.

## New Page, Widget and Action

Resource is the easy case: the two seeders handle it. The other three families need one line of code,
because Filament's defaults are **permissive** — the vendor says so in a comment, in
`Pages/Concerns/CanAuthorizeAccess.php` (`canAccess()` returns `true`), in `Widget.php` (`canView()`
returns `true`) and in `Actions/Concerns/CanBeAuthorized.php` (authorization defaults to `null`, i.e.
allowed).

Shield **generates** `View:{Page}` and `View:{Widget}` by discovery, `PapeisSeeder` **hands them** to
the panel's roles and the roles screen **shows** the checkbox — none of which makes the permission be
consulted. Without the trait, unchecking the box changes nothing.

```php
// New panel Page
use App\Filament\Concerns\ExigePermissaoDaTela;

class MyPage extends Page
{
    use ExigePermissaoDaTela;

    // Local rule (config flag, tenancy) goes IN THE HOOK, never overriding canAccess():
    protected static function regraLocalDeAcesso(): bool
    {
        return (bool) config('kit.my_flag');
    }
}

// New Widget
use App\Filament\Concerns\ExigePermissaoDoWidget;

class MyWidget extends StatsOverviewWidget
{
    use ExigePermissaoDoWidget;

    // Optional data-source check goes IN THE HOOK, never overriding canView():
    protected static function fonteDeDadosDisponivel(): bool
    {
        return (bool) rescue(fn (): bool => Schema::hasTable('my_table'), false);
    }
}
```

> ⚠️ **Overriding `canAccess()`/`canView()` on the class silently disables the permission.** A class
> method wins over a trait method, with no error and no warning. That is why both concerns publish a
> **hook** for the local rule, and why `tests/Kit/PermissoesDeTelasTest.php` and
> `PermissoesDeWidgetsTest.php` each have a case that walks EVERY class and fails the one that does
> not consult it.

**Action** is an explicit declaration, because Shield discovers no Action at all:

| The Action belongs to… | The permission is born in | And on the Action |
|---|---|---|
| A Resource (table, header, RelationManager) | `config('filament-shield.resources.manage')` on that panel's Resource | `->authorize('MyAction:MyModel')` |
| A Page | `config('filament-shield.custom_permissions')` **and** `PapeisSeeder::paineisDasPermissoesCustomizadas()` | `->authorize('MyAction:MyModel')` |

The second row has two halves because `custom_permissions` **knows nothing about panels**: without the
seeder's map, the new key lands on `admin`, `infra`, `admin_app` **and `panel_user`**. A key with no
entry in the map reaches no role at all (fail-closed) and case `CT-19` of
`tests/Kit/PermissoesDeAcoesTest.php` turns red naming the key.

> ⚠️ **In a RelationManager, not even the NATIVE Action is covered.** `AttachAction`, `DetachAction`,
> `AssociateAction` and `DissociateAction` only check `isReadOnly()` — the comment is in the vendor's
> `getDefaultActionAuthorizationResponse()`. In the kit, the `tenant_user` link that `AttachAction`
> creates is exactly what `User::canAccessTenant()` consults to unlock `/app/{slug}`, so both carry
> `->authorize()`.

**Vendor Pages and Widgets stay out**: they are package classes, with no extension point. Their
permission exists in the database and in the checkbox, and **is not consulted** — the barrier is
`canAccessPanel()` plus the named gates from `KitServiceProvider` (`ver-logs`,
`command-center:access`, `viewPulse`, `ver-ai-tasks`).

> **Shield does not see RelationManagers.** Its discovery covers Resources, Pages and Widgets only, so no permission is generated and authorization falls back to the **related model's policy**. If that model already has a Resource on some panel, there is nothing to do. If it doesn't, write the policy by hand (`php artisan make:policy`) and declare the keys in `config('filament-shield.custom_permissions')` **before** running the seeders — otherwise the RelationManager is open to anyone who can open the parent Resource.

Add the kit's two traits to what was generated:

```php
// On the Resource — animated count badge in the menu:
use App\Filament\Concerns\BadgeContagemNavegacao;

class ProdutoResource extends Resource
{
    use BadgeContagemNavegacao;
}

// On the List page — remembers the column width chosen by the user:
use Asmit\ResizedColumn\HasResizableColumn;

class ListProdutos extends ListRecords
{
    use HasResizableColumn;
}
```

## Count badges

Every **kit** Resource already has a badge in the menu (Users, AI Agents, AI Runs). The count comes from `getEloquentQuery()`, never from `Model::count()`: the resource's query carries the scopes that apply to that panel, and counting straight from the model would show a number the listing doesn't confirm. Zero doesn't become a badge — a gray "0" on every item is just noise.

**Third-party plugin** resources (Auditing, Logins, Queues, Composer Packages, Commands, Shield Roles, Onboarding) go without a badge: `getNavigationBadge()` is a static method on the resource, and Filament offers no API to override it from the outside — the panel's `ResourceConfiguration` only lets you change the slug. Giving them a badge would mean extending each vendor resource and preventing the plugin from registering its own, which breaks on every package update. If one of them matters in your project, that's the path — resource by resource, deliberately.


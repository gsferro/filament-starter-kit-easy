<?php

namespace App\Models;

use App\Traits\TemUuid;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * O papel do spatie com a coluna que o kit acrescentou.
 *
 * `painel` é o que dá acesso a painel: `User::canAccessPanel()` compara esta coluna com
 * o id do painel corrente. **Nulo não é coringa** — papel sem painel não abre painel
 * algum, e quem entra em todos é o `master_global`, pelo `Gate::before`.
 *
 * Este model existe por causa da coluna. Sem ele, `$papel->painel` é atributo dinâmico:
 * sem tipo, sem autocomplete, e o PHPStan reprova todo acesso. Apontar
 * `permission.models.role` para cá basta — spatie e Shield resolvem o model pela config
 * (`Config::roleModel()`, `Utils::getRoleModel()`), então nada mais precisa saber disto.
 *
 * ## O `uuid` na rota
 *
 * `TemUuid` faz a rota do papel usar `uuid` em vez de `id`, que é a convenção do kit
 * (`app/Traits/TemUuid.php:14-18`). A PK continua `id` int: `uniqueIds()` devolve
 * `['uuid']`, e o `HasUniqueStringIds` do Laravel só troca `getKeyType()`/`getIncrementing()`
 * quando a chave primária está nessa lista — as foreign keys de `model_has_roles` e
 * `role_has_permissions` seguem numéricas.
 *
 * O item 3 do checklist da trait ("`uuid` fica FORA do `$fillable`") é atendido por
 * ausência: o `Model` do spatie usa `$guarded = []` e **não** tem `$fillable`. Declarar um
 * aqui quebraria o `Role::create()` do spatie e dos dois seeders, que passam chaves
 * variáveis. Consequência aceita e registrada em ADR-03: `uuid` é mass-assignable. O risco
 * é baixo porque nenhum formulário do kit tem campo `uuid` e
 * `CreateRole::mutateFormDataBeforeCreate()` faz `Arr::only()` antes de gravar
 * (`app/Filament/Admin/Resources/Roles/Pages/CreateRole.php:34-37`).
 *
 * @property ?string $painel
 * @property string $uuid
 */
class Role extends SpatieRole
{
    // Fora da trait, nada: a coluna `painel` é `$guarded = []` no spatie, então mass
    // assignment, casts e queries já funcionam. Scope e helper entram quando houver um
    // segundo chamador.
    use TemUuid;
}

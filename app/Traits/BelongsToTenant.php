<?php

namespace App\Traits;

use App\Models\Tenant;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model que pertence a um tenant.
 *
 * Use em TODA model do negócio quando o projeto roda em modo multi-tenant
 * (`php artisan kit:tenancy`). Ela entrega três coisas:
 *
 *   1. a relação `tenant()` — que é o `ownershipRelationship` que o Filament
 *      procura para escopar os resources sozinho;
 *   2. um escopo global que recorta as queries pelo tenant corrente;
 *   3. o preenchimento automático de `tenant_id` ao criar.
 *
 * ## Por que a trait existe, se o Filament já escopa
 *
 * A documentação do Filament é explícita: *"A tenant-aware resource has to
 * exist in the panel with tenancy enabled for the resource's model to have the
 * global scope applied. If you want to scope the queries for a model that does
 * not have a corresponding resource, you must use middleware to apply
 * additional global scopes."*
 *
 * Ou seja: model consultada em job, comando, listener, widget ou API — sem
 * passar por um Resource — fica de fora do escopo do Filament. É exatamente aí
 * que vaza dado de um tenant para outro, em silêncio. Esta trait fecha o
 * buraco no próprio model, que é onde a garantia não depende de ninguém
 * lembrar de nada.
 *
 * Em model que TEM resource, os dois escopos coexistem: aplicam a mesma
 * condição, então a query só repete o filtro — sem efeito colateral.
 *
 * ## Checklist ao usar
 *
 *   1. migration com `$table->foreignId('tenant_id')->constrained()`;
 *   2. model usa esta trait;
 *   3. `tenant_id` fica FORA do `$fillable` — quem preenche é a trait;
 *   4. no form do resource, use `->scopedUnique()` em vez de `->unique()`
 *      (a regra `unique` do Laravel não enxerga o tenant).
 *
 * ## Limites (leia antes de confiar)
 *
 * - **Sem tenant, sem escopo.** Fora de um request de painel (job, comando,
 *   seeder, tinker) `Filament::getTenant()` é null e a query volta a ser
 *   global. É deliberado: um job que roda para todos os tenants precisa
 *   enxergar todos. Quando o job for de UM tenant, filtre explicitamente —
 *   `Model::where('tenant_id', $id)` — ou chame `Filament::setTenant()`.
 * - **Escopo não é autorização.** Policies continuam obrigatórias, pela mesma
 *   razão que `TemUuid` não substitui policy.
 * - **`withoutGlobalScopes()` derruba este escopo junto com os outros.** A
 *   própria doc do Filament avisa que isso "can lead to data leakage". Para
 *   uma query deliberadamente global, remova só este:
 *   `Model::withoutGlobalScope('tenant')`.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            $tenant = Filament::getTenant();

            if ($tenant instanceof Tenant) {
                $query->where($query->getModel()->qualifyColumn('tenant_id'), $tenant->getKey());
            }
        });

        static::creating(function (self $model): void {
            $tenant = Filament::getTenant();

            if ($tenant instanceof Tenant && blank($model->tenant_id)) {
                $model->tenant_id = $tenant->getKey();
            }
        });
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

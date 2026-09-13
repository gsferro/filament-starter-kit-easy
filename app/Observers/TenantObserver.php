<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Tenant;
use App\Services\Dashboard\CriadorDeDashboardPadrao;
use App\Support\DashboardDinamico;

/**
 * Toda organização nova nasce com o dashboard dinâmico padrão dela.
 *
 * O gate é `habilitadoPara('app')` — por PAINEL, não pela flag global: flag
 * ligada com o `/app` fora da lista de painéis não pode semear linha órfã
 * (P5 do requisito, CT-27). Tenant criado com a feature desligada fica sem
 * dashboard; o `DashboardPadraoSeeder` cobre o backfill na ativação posterior.
 *
 * Registrado no `KitServiceProvider` somente com `kit.tenancy.enabled` — sem
 * organizações não há o que semear.
 */
final class TenantObserver
{
    public function created(Tenant $tenant): void
    {
        if (! DashboardDinamico::habilitadoPara('app')) {
            return;
        }

        CriadorDeDashboardPadrao::para($tenant);
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Filament\App\Pages\Dashboard as DashboardDoApp;
use App\Models\Tenant;
use App\Services\Dashboard\CriadorDeDashboardPadrao;
use App\Support\DashboardDinamico;
use Illuminate\Database\Seeder;
use MDDev\DynamicDashboard\DashboardModelHelper;

/**
 * Backfill do dashboard dinâmico padrão — o caminho de quem liga a feature
 * DEPOIS de já ter organizações (ou de rodar sem tenancy).
 *
 * Idempotente: rodar duas vezes não duplica o "Padrão". Cobre os dois mundos:
 *
 *   php artisan db:seed --class=Database\\Seeders\\DashboardPadraoSeeder
 *   php artisan db:seed --class=Database\\Seeders\\DashboardPadraoSeeder -- --tenant=3
 *
 * Com tenancy, semeia um dashboard por organização; sem tenancy, o global
 * (`tenant_id` nulo). O gate é `habilitadoPara('app')` — com a feature
 * desligada ou o /app fora da lista, o seeder é no-op de propósito: semear
 * linha que nenhuma página alcança é órfão garantido.
 */
final class DashboardPadraoSeeder extends Seeder
{
    public function run(): void
    {
        if (! DashboardDinamico::habilitadoPara('app')) {
            $this->command->warn('Dashboard dinâmico desligado para o /app — nada a semear.');

            return;
        }

        /*
         * `db:seed` não declara `--tenant` — `option()` numa opção inexistente
         * estoura InvalidArgumentException. A opção só existe quando o seeder é
         * chamado por um comando que a declare (ex.: um `tenants:seed` do
         * projeto); por isso a leitura é condicionada à definição.
         */
        $tenantId = $this->command->getDefinition()->hasOption('tenant')
            ? $this->command->option('tenant')
            : null;

        if ($tenantId !== null) {
            $this->paraTenant(Tenant::query()->findOrFail((int) $tenantId));

            return;
        }

        if (config('kit.tenancy.enabled')) {
            Tenant::query()->chunkById(100, fn ($tenants) => $tenants->each(
                fn (Tenant $tenant) => $this->paraTenant($tenant),
            ));

            return;
        }

        $this->paraTenant(null);
    }

    private function paraTenant(?Tenant $tenant): void
    {
        $model = DashboardModelHelper::model();

        $jaExiste = $model::query()
            ->withoutGlobalScope('tenant')
            ->where('page', DashboardDoApp::class)
            ->where('tenant_id', $tenant?->getKey())
            ->exists();

        if ($jaExiste) {
            return;
        }

        CriadorDeDashboardPadrao::para($tenant);
    }
}

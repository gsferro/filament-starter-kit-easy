<?php

namespace Tests;

use App\Models\Tenant;
use Illuminate\Foundation\Application;
use Spatie\Permission\PermissionRegistrar;

/**
 * TestCase dos testes de multi-tenancy.
 *
 * Existe por um motivo de ORDEM. Duas coisas precisam estar decididas antes de
 * momentos diferentes do ciclo:
 *
 *   1. `kit.tenancy.enabled`, antes do BOOT — o AppPanelProvider a lê para
 *      registrar as rotas com `/{tenant}`. Resolvido no `Tests\TestCase`, que
 *      escreve a flag no ambiente antes do bootstrap.
 *   2. `permission.teams`, antes das MIGRATIONS — a migration do spatie a lê em
 *      tempo de execução para criar (ou não) as colunas de team. Resolvido
 *      aqui, em `createApplication()`, que roda antes do `setUpTraits()` que
 *      dispara o RefreshDatabase.
 *
 * A suíte do kit continua rodando em modo single-tenant: só os arquivos da
 * pasta `tests/Tenancy` usam este TestCase (amarrado em tests/Pest.php).
 */
abstract class TenancyTestCase extends TestCase
{
    protected function usaTenancy(): bool
    {
        return true;
    }

    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $app['config']->set('permission.teams', true);
        $app['config']->set('filament-shield.tenant_model', Tenant::class);

        // O PermissionRegistrar é singleton e lê `permission.teams` no
        // construtor — como ele já foi resolvido durante o boot, com a flag
        // ainda desligada, precisa ser descartado para renascer sabendo de
        // teams.
        $app->forgetInstance(PermissionRegistrar::class);

        // Mesmo motivo, do outro lado: o KitServiceProvider fixa o contexto
        // global de papéis no boot, que aconteceu antes desta config existir.
        $app->make(PermissionRegistrar::class)->setPermissionsTeamId(Tenant::CONTEXTO_GLOBAL);

        return $app;
    }
}

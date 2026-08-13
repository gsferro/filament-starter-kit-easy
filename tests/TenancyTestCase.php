<?php

namespace Tests;

use App\Models\Tenant;
use Illuminate\Foundation\Application;
use Spatie\Permission\PermissionRegistrar;

/**
 * TestCase dos testes de multi-tenancy.
 *
 * Existe por um motivo de ORDEM: a migration de permissões do spatie lê
 * `config('permission.teams')` em tempo de execução para decidir se cria as
 * colunas de team. Ligar a flag num `beforeEach` seria tarde demais — o
 * `RefreshDatabase` já teria rodado as migrations sem elas, e os testes de
 * papel por tenant falhariam por schema, não por lógica.
 *
 * `createApplication()` roda ANTES do `setUpTraits()` que dispara o
 * RefreshDatabase (ver Illuminate\Foundation\Testing\TestCase::setUp), então é
 * aqui que a config precisa ser fixada.
 *
 * A suíte do kit continua rodando em modo single-tenant: só os arquivos que
 * declaram `uses(TenancyTestCase::class)` entram neste modo.
 */
abstract class TenancyTestCase extends TestCase
{
    /** Faz o TestCase base remigrar o schema com as colunas de team. */
    protected function schemaComTenancy(): bool
    {
        return true;
    }

    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $app['config']->set('kit.tenancy.enabled', true);
        $app['config']->set('permission.teams', true);
        $app['config']->set('filament-shield.tenant_model', Tenant::class);

        // O PermissionRegistrar é singleton e lê `permission.teams` no
        // construtor — como ele já foi resolvido durante o boot dos providers,
        // com a flag ainda desligada, precisa ser descartado para renascer
        // sabendo de teams.
        $app->forgetInstance(PermissionRegistrar::class);

        // Mesmo motivo, do outro lado: o KitServiceProvider fixa o contexto
        // global de papéis no boot, que aconteceu antes desta config existir.
        $app->make(PermissionRegistrar::class)->setPermissionsTeamId(Tenant::CONTEXTO_GLOBAL);

        return $app;
    }
}

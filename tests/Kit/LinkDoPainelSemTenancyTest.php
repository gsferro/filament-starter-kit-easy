<?php

use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Models\Tenant;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * CT-19 da wiki `link-painel-do-tenant` — com a multi-tenancy desligada a superfície do link não é
 * alcançável.
 *
 * ## Por que o caso vive aqui e não em `tests/Tenancy`
 *
 * `tests/Kit` é a ÚNICA suíte em que `kit.tenancy.enabled` é falso. Escrito em `tests/Tenancy` com
 * um `config()->set()` num `beforeEach`, este caso mediria o arnês: o `Tests\TenancyTestCase` fixa
 * a config em `createApplication()`, antes das migrations (`.ai/rules/testes.md`).
 *
 * ## O "não se aplica" aqui tem destinatário
 *
 * A organização EXISTE na tabela — ela existe sem tenancy, só não significa nada —, então a
 * listagem teria uma linha para renderizar um link. Sem essa fixture o caso passaria por não haver
 * o que renderizar, e não por a tela estar fechada.
 *
 * ## O que ele protege
 *
 * A ADR-03 decidiu NÃO acrescentar guarda de config nenhuma: a feature vive dentro do
 * `TenantResource`, que já se fecha nos dois métodos (`canAccess()` e `shouldRegisterNavigation()`).
 * Este caso é o invariante que sustenta essa decisão — ele fica vermelho se a entrada do link
 * nascer fora do resource (num hub, num widget, no menu), onde o gerador não tem rota de tenant
 * para resolver, e fica vermelho se o par de métodos do resource perder a condição de config.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

it('[CT-19] com a tenancy desligada a listagem de organizacoes nao abre', function (): void {
    expect(config('kit.tenancy.enabled'))->toBeFalse('a suíte Kit tem de rodar com a tenancy desligada');

    Tenant::create(['nome' => 'Acme', 'slug' => 'acme', 'ativo' => true]);

    $administrador = usuarioDoKit('admin', 'admin-instalacao@example.com');

    $this->actingAs($administrador)->get('/admin/organizacoes')->assertForbidden();

    expect(TenantResource::canAccess())->toBeFalse()
        ->and(TenantResource::shouldRegisterNavigation())->toBeFalse();

    // E o endereço da tela nem aparece na navegação do painel.
    $this->actingAs($administrador)->get('/admin')->assertSuccessful()->assertDontSee('/admin/organizacoes');
})->group('kit');

<?php

use App\Filament\App\Resources\Projetos\ProjetoResource;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * O resource de exemplo (Projetos) aparece **só** quando há demo.
 *
 * Aqui a multi-organização está ligada — é o `Tests\TenancyTestCase`. Ou seja:
 * esta suíte isola a SEGUNDA condição, que é a que não existia antes. Com
 * tenancy ligada e sem demo, o painel de negócio continua vazio; é o caso de
 * quem ligou a multi-organização para valer, no projeto dele.
 */
it('some do /app quando a tenancy está ligada mas não é uma demo', function (): void {
    config(['kit.demo' => false]);

    expect(config('kit.tenancy.enabled'))->toBeTrue()
        ->and(ProjetoResource::shouldRegisterNavigation())->toBeFalse()
        ->and(ProjetoResource::canAccess())->toBeFalse();
})->group('kit');

it('aparece no /app quando a demo está ligada', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    config(['kit.demo' => true]);

    // `master_global` vence por Gate::before: o caso mede a GUARDA do resource,
    // não a matriz de permissões, que tem suíte própria.
    $this->actingAs(usuarioComPapel('master_global'));

    expect(ProjetoResource::shouldRegisterNavigation())->toBeTrue()
        ->and(ProjetoResource::canAccess())->toBeTrue();
})->group('kit');

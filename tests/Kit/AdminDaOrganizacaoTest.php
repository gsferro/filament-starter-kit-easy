<?php

use App\Filament\App\Resources\Users\UserResource;
use App\Models\Role;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * Sem multi-tenancy a persona não existe — e os dois Resources ficam inertes.
 *
 * Um `admin_organizacao` em modo single-tenant seria um papel com permissão de criar
 * usuário e NENHUM recorte: um segundo `admin` com outro nome, alcançando toda a base de
 * usuários da instalação a partir do painel de negócio. Ver ADR-09.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

it('nao semeia o admin da organizacao sem tenancy', function (): void {
    expect(Role::where('name', 'admin_organizacao')->exists())->toBeFalse();

    $user = usuarioCom('panel_user');   // helper de tests/Kit/PaineisTest.php

    // Quem barra é `canAccess()`, que devolve false com `kit.tenancy.enabled` desligada —
    // o mesmo par usado no TenantResource.
    $this->actingAs($user)->get('/app/users')->assertForbidden();

    expect(UserResource::shouldRegisterNavigation())->toBeFalse()
        // A parte contraintuitiva: os Resources SÃO descobertos e têm permission gerada
        // mesmo em single-tenant, então `panel_user` os herdaria se a subtração do
        // PapeisSeeder fosse condicional à tenancy.
        ->and($user->can('ViewAny:User'))->toBeFalse()
        ->and($user->can('Create:Convite'))->toBeFalse();
});

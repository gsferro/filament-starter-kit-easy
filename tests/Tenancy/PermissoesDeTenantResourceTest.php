<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * O par tem / não-tem de `TenantResource` — o único resource do `/admin` fora do sweep de `tests/Kit`.
 *
 * `TenantResource::canAccess()` exige `kit.tenancy.enabled`, que é `false` na suíte `Kit`: lá o
 * papel `admin` toma 403 com e sem a permissão, e o par não discrimina nada. Aqui a tenancy está
 * ligada pelo `TenancyTestCase`, e a única variável volta a ser a permissão.
 *
 * A auditoria de aderência ao Blueprint (N-29) registrou que a única asserção negativa existente
 * sobre este resource era por **config**, não por permissão — `IdentidadeVisualTest.php:98`, com
 * `master_global` e a tenancy desligada. Este arquivo fecha essa lacuna.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    noPainelDoShield('admin');
});

it('abre a listagem de organizacoes para o admin com a permissao', function (): void {
    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'))
        ->get('/admin/organizacoes')
        ->assertOk();
})->group('tenancy');

it('nega a listagem de organizacoes ao admin sem ViewAny:Tenant', function (): void {
    semAPermissao('admin', 'ViewAny:Tenant');

    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'))
        ->get('/admin/organizacoes')
        ->assertForbidden();
})->group('tenancy');

<?php

use App\Filament\Admin\Resources\Tenants\Pages\ListTenants;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Livewire\Livewire;

/**
 * O filtro `ativo` de `TenantsTable`
 * (`app/Filament/Admin/Resources/Tenants/Tables/TenantsTable.php:ativo:85`) — o único filtro do
 * kit que só existe com a tenancy ligada.
 *
 * A citação passou a ser `{path}:{símbolo}:{linha}` com o caminho COMPLETO, e não o
 * `TenantsTable.php:55` de antes, por dois motivos medidos: a coluna do link do painel da
 * organização (wiki `link-painel-do-tenant`), acrescentada em `->columns([…])`, deslocou aquele
 * número de 55 para 83 num diff que nem abria este arquivo; e `TenantsTable.php` solto não resolve
 * em `base_path()`, então `tests/Kit/CitacoesDeCodigoTest.php:[CT-26]` a IGNORAVA — a citação
 * ficava errada sem nada acusar. Com o caminho completo e o símbolo, o gate confere. Ver
 * `.ai/rules/specs.md`.
 *
 * A auditoria de aderência ao Blueprint (N-33) o listou entre os filtros declarados e nunca
 * acionados. Vive aqui, e não em `tests/Kit/FiltrosDeTabelaTest.php`, porque
 * `TenantResource::canAccess()` exige `kit.tenancy.enabled`, que é `false` na suíte `Kit`.
 *
 * `noPainelBootado('admin')` porque a coluna de logo usa o macro `simpleLightbox()`, registrado no
 * `boot()` do plugin — sem o boot a tela morre no arranjo com `BadMethodCallException`
 * (`.ai/rules/testes.md` §"Teste de componente de painel").
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

it('filtra as organizações por ativo nos dois sentidos', function (bool $valor): void {
    $ativa   = tenant('Acme', 'acme');
    $inativa = tenant('Globex', 'globex', ativo: false);

    noPainelBootado('admin');
    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    [$visivel, $oculto] = $valor ? [$ativa, $inativa] : [$inativa, $ativa];

    Livewire::test(ListTenants::class)
        ->loadTable()
        ->filterTable('ativo', $valor)
        ->assertCanSeeTableRecords([$visivel])
        ->assertCanNotSeeTableRecords([$oculto]);
})->with([
    'só as ativas'   => [true],
    'só as inativas' => [false],
])->group('tenancy');

<?php

use App\Filament\Admin\Resources\Roles\Pages\ListRoles;
use App\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use App\Filament\Admin\Resources\Tenants\RelationManagers\UsersRelationManager;
use App\Models\Role;
use App\Support\Papeis;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/**
 * A tela de papéis vista de dentro da multi-tenancy.
 *
 * Os dois casos aqui não são a versão "com organização" de casos de `tests/Kit`: eles provam
 * coisas que **só existem** com `permission.teams` ligada, e que ficariam verdes para sempre
 * na suíte single-tenant.
 *
 * Os helpers `tenant()`, `usuario()` e `papelNaOrganizacao()` vêm de `tests/Pest.php` — o Pest
 * carrega os arquivos da suíte inteira, então rode a pasta, não um arquivo só.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * CT-06 — a mesma pessoa em duas organizações conta UMA vez.
 *
 * É o caso que a suíte single-tenant não pode ter, e é o único que reprova
 * `->counts('users')` puro.
 *
 * O mecanismo: com `permission.teams` ligada, a chave primária de `model_has_roles` inclui
 * `team_id` (`database/migrations/2026_08_12_164859_create_permission_tables.php:88-93`), então
 * a mesma pessoa com o mesmo papel em duas organizações são DUAS linhas de pivot. E a relação
 * não dedupe sozinha: `Role::users()` é um `morphedByMany` sem filtro de team
 * (`vendor/spatie/laravel-permission/src/Models/Role.php:100-109`) — o `wherePivot` de team o
 * spatie põe em `HasRoles::roles()`, do lado do usuário, não aqui.
 *
 * Resultado sem o `distinct`: a coluna diz 2 para uma pessoa. Número plausível, tela verde,
 * e ninguém desconfia. Ver ADR-04.
 */
it('conta a mesma pessoa uma vez quando ela tem o papel em duas organizacoes', function (): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $carla = papelNaOrganizacao(usuario('carla@example.com'), 'panel_user', $acme);
    papelNaOrganizacao($carla, 'panel_user', $globex);

    $papel = Role::query()->where('name', 'panel_user')->firstOrFail();

    // Duas linhas na pivot: é a premissa do caso. Se isto deixar de valer, o caso não prova
    // mais nada e precisa ser revisto em vez de "consertado".
    expect(DB::table(pivotDePapeis())
        ->where('role_id', $papel->getKey())
        ->where('model_id', $carla->getKey())
        ->count())->toBe(2);

    noPainelBootado('admin');
    $this->actingAs(usuarioCom('master_global', 'master@example.com'));

    Livewire::test(ListRoles::class)
        ->loadTable()
        ->assertTableColumnStateSet('users_count', 1, $papel);
})->group('kit');

/**
 * CT-10 — o seletor de papéis da organização exibe o rótulo, não a chave.
 *
 * Este é o ponto de exibição que passa por `mapWithKeys` em vez de `formatStateUsing`, e por
 * isso é um caminho de código próprio: corrigir as colunas de tabela e os widgets e esquecer
 * este `Select` deixa a mesma tela mostrando "Painel App" na coluna e `panel_user` no
 * seletor, um logo abaixo do outro.
 */
it('exibe o rotulo do papel no seletor de papeis da organizacao', function (): void {
    $acme = tenant('Acme', 'acme');
    $beto = papelNaOrganizacao(usuario('beto@example.com'), 'panel_user', $acme);
    $beto->tenants()->attach($acme);

    noPainelBootado('admin');
    $this->actingAs(usuarioCom('master_global', 'master@example.com'));

    /*
     * As OPÇÕES do componente, e não `assertSee` no HTML: o Select é `->searchable()`, e o
     * Filament não imprime a lista no HTML inicial — ela vai por requisição separada. Um
     * `assertSee` aqui ficaria vermelho com a tela certa, e um `assertDontSee` da chave
     * ficaria VERDE com a tela errada, que é pior.
     */
    Livewire::test(UsersRelationManager::class, [
        'ownerRecord' => $acme,
        'pageClass'   => EditTenant::class,
    ])
        ->loadTable()
        ->mountAction(TestAction::make('papeisNaOrganizacao')->table($beto))
        ->assertSchemaComponentExists(
            'roles',
            'mountedActionSchema0',
            function (Select $componente): bool {
                $opcoes = $componente->getOptions();

                return in_array(Papeis::rotulo('panel_user'), $opcoes, true)
                    && ! in_array('panel_user', $opcoes, true);
            },
        );
})->group('kit');

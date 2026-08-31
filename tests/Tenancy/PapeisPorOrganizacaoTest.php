<?php

use App\Filament\Admin\Resources\Convites\Pages\CreateConvite;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ContextoDePapeis;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * Papel concedido pelo /admin nasce no contexto CERTO — e ninguém se promove por lá.
 *
 * Parte 1: o /admin não tem tenant, então o contexto do request é o global. Papel do painel
 * `app` gravado aí (`team_id = 0`) produz o pior sintoma da área de acesso: a pessoa autentica
 * no /app, responde 200 e não vê nada — o mesmo defeito da v0.19.1 em `User::aprovar()`.
 * `UserResource::gravarPapeis()` separa por `roles.painel` e grava o papel do /app em cada
 * organização do campo `tenants`.
 *
 * Parte 2 (F-01 da auditoria Blueprint): `admin` conseguia se dar `master_global` pela tela de
 * usuários e pelo convite. Só quem já é master concede master, e a trava é na escrita.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->acme   = tenant('Acme', 'acme');
    $this->globex = tenant('Globex', 'globex');
    $this->dani   = usuario('dani@example.com');

    Filament::setCurrentPanel('admin');
});

/**
 * A ficha da pessoa salva em /admin/users por quem está autenticado.
 *
 * @param  list<int|string>|null  $papeis  null = não mexe no campo (salva o que o form carregou)
 * @param  list<int|string>  $organizacoes
 */
function salvarFichaNoAdmin(User $alvo, ?array $papeis, array $organizacoes): Testable
{
    $dados = ['tenants' => $organizacoes];

    if ($papeis !== null) {
        $dados['roles'] = $papeis;
    }

    return Livewire::test(EditUser::class, ['record' => $alvo->getRouteKey()])
        ->fillForm($dados)
        ->call('save');
}

/*
|--------------------------------------------------------------------------
| Contexto do papel
|--------------------------------------------------------------------------
*/

it('grava papel do app no team_id da organizacao selecionada, nao no global', function (): void {
    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));
    $adminApp = Role::findByName('admin_app');

    salvarFichaNoAdmin($this->dani, [$adminApp->getKey()], [$this->acme->getKey()])
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(pivotDePapeis(), [
        'model_id' => $this->dani->id, 'role_id' => $adminApp->getKey(), 'team_id' => $this->acme->id,
    ]);
    // A asserção que importa: `team_id = 0` é alguém que entra no /app e não vê nada.
    $this->assertDatabaseMissing(pivotDePapeis(), [
        'model_id' => $this->dani->id, 'role_id' => $adminApp->getKey(), 'team_id' => Tenant::CONTEXTO_GLOBAL,
    ]);

    fronteiraDeRequest();

    $this->actingAs($this->dani->fresh())->get('/app/acme/users')->assertSuccessful();
});

it('grava papel de painel sem tenancy no contexto global', function (): void {
    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));
    $admin = Role::findByName('admin');

    salvarFichaNoAdmin($this->dani, [$admin->getKey()], [$this->acme->getKey()])
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(pivotDePapeis(), [
        'model_id' => $this->dani->id, 'role_id' => $admin->getKey(), 'team_id' => Tenant::CONTEXTO_GLOBAL,
    ]);
    $this->assertDatabaseMissing(pivotDePapeis(), [
        'model_id' => $this->dani->id, 'role_id' => $admin->getKey(), 'team_id' => $this->acme->id,
    ]);
});

/**
 * Papel DIFERENTE por organização sobrevive a uma gravação pelo /admin.
 *
 * O form carrega a união dos papéis (a relação `roles` do spatie, filtrada pelo team do
 * request, mostraria só os globais — e gravar a partir dela apagaria o que a pessoa tem
 * em cada organização). Salvar a ficha sem mexer nos papéis não pode achatar nem apagar.
 */
it('mantem papel diferente por organizacao ao salvar a ficha pelo admin', function (): void {
    papelNaOrganizacao($this->dani, 'admin_app', $this->acme);
    papelNaOrganizacao($this->dani, 'panel_user', $this->globex);
    $this->dani->tenants()->attach([$this->acme->id, $this->globex->id]);

    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    salvarFichaNoAdmin($this->dani, null, [$this->acme->getKey(), $this->globex->getKey()])
        ->assertHasNoFormErrors();

    $dani = $this->dani->fresh();

    expect(ContextoDePapeis::em($this->globex->id, $dani, fn (): bool => $dani->hasRole('admin_app')))->toBeFalse()
        ->and(ContextoDePapeis::em($this->globex->id, $dani, fn (): bool => $dani->hasRole('panel_user')))->toBeTrue()
        ->and(ContextoDePapeis::em($this->acme->id, $dani, fn (): bool => $dani->hasRole('admin_app')))->toBeTrue()
        ->and(ContextoDePapeis::em($this->acme->id, $dani, fn (): bool => $dani->hasRole('panel_user')))->toBeFalse();

    // Uma instância por request, como em produção: a relação `roles` fica cacheada no model
    // com o team do request anterior, e a segunda visita mediria a primeira.
    fronteiraDeRequest();
    $this->actingAs($dani->fresh())->get('/app/acme/users')->assertSuccessful();

    fronteiraDeRequest();
    $this->actingAs($dani->fresh())->get('/app/globex/users')->assertForbidden();
});

it('aplica a diferenca em toda organizacao e da a lista inteira a organizacao nova', function (): void {
    papelNaOrganizacao($this->dani, 'panel_user', $this->acme);
    $this->dani->tenants()->attach($this->acme);

    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));
    $adminApp  = Role::findByName('admin_app');
    $panelUser = Role::findByName('panel_user');

    // Troca panel_user por admin_app e entra na Globex, onde ainda não tinha papel nenhum.
    salvarFichaNoAdmin($this->dani, [$adminApp->getKey()], [$this->acme->getKey(), $this->globex->getKey()])
        ->assertHasNoFormErrors();

    foreach ([$this->acme, $this->globex] as $organizacao) {
        $this->assertDatabaseHas(pivotDePapeis(), [
            'model_id' => $this->dani->id, 'role_id' => $adminApp->getKey(), 'team_id' => $organizacao->id,
        ]);
        $this->assertDatabaseMissing(pivotDePapeis(), [
            'model_id' => $this->dani->id, 'role_id' => $panelUser->getKey(), 'team_id' => $organizacao->id,
        ]);
    }

    $this->assertDatabaseMissing(pivotDePapeis(), [
        'model_id' => $this->dani->id, 'team_id' => Tenant::CONTEXTO_GLOBAL,
    ]);
});

/*
|--------------------------------------------------------------------------
| Teto de escalada (F-01)
|--------------------------------------------------------------------------
*/

it('nao deixa quem nao e master_global conceder master_global pela tela de usuarios', function (): void {
    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));
    $master = Role::findByName('master_global');

    salvarFichaNoAdmin($this->dani, [$master->getKey()], [$this->acme->getKey()]);

    expect($this->dani->fresh()->isMasterGlobal())->toBeFalse();
    $this->assertDatabaseMissing(pivotDePapeis(), ['model_id' => $this->dani->id, 'role_id' => $master->getKey()]);

    // A barreira, chamada direto: o payload forjado passa pela validação do Select e ainda
    // assim o papel não entra.
    UserResource::gravarPapeis($this->dani, [$master->getKey()], []);

    expect($this->dani->fresh()->isMasterGlobal())->toBeFalse();
});

it('deixa o master_global conceder master_global', function (): void {
    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));
    $master = Role::findByName('master_global');

    salvarFichaNoAdmin($this->dani, [$master->getKey()], [$this->acme->getKey()])
        ->assertHasNoFormErrors();

    expect($this->dani->fresh()->isMasterGlobal())->toBeTrue();
});

it('nao deixa quem nao e master_global convidar com master_global', function (): void {
    Notification::fake();
    $master = Role::findByName('master_global');

    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));

    Livewire::test(CreateConvite::class)
        ->fillForm(['email' => 'novo@example.com', 'role_id' => $master->getKey()])
        ->call('create')
        ->assertHasFormErrors(['role_id']);

    $this->assertDatabaseMissing('convites', ['email' => 'novo@example.com']);

    // Linha de controle: o master convida com o papel dele.
    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    Livewire::test(CreateConvite::class)
        ->fillForm(['email' => 'novo@example.com', 'role_id' => $master->getKey()])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('convites', ['email' => 'novo@example.com', 'role_id' => $master->getKey()]);
});

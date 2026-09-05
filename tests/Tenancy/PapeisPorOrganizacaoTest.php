<?php

use App\Filament\Admin\Resources\Convites\Pages\CreateConvite;
use App\Filament\Admin\Resources\Convites\Pages\ListConvites;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Convite;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ContextoDePapeis;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
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

/*
|--------------------------------------------------------------------------
| Teto de escalada por PAINEL (F-02) — R3 e R4
|--------------------------------------------------------------------------
| Quem não é `master_global` concede papel SEM painel, papel do painel de
| NEGÓCIO (o `->default()` do kit, hoje `/app`) e papel de painel que ele
| PRÓPRIO acessa — nunca de painel que governa a instalação e ao qual ele não
| tem acesso. Ver Q1 do `00-requisito.md` da wiki travas-de-escalada-de-papeis.
|
| O oráculo é sempre `model_has_roles`, nunca as opções renderizadas: opção é
| UX, e a trava que vale é na ESCRITA.
*/

/** O papel que só carrega permissões — `painel = null`, a partição declarada da premissa 2. */
function papelSemPainel(): Role
{
    return Role::create(['name' => 'etiquetador', 'guard_name' => 'web', 'painel' => null]);
}

/**
 * CT-08 — o alcance do operador recorta o papel concedido na ficha do usuário.
 *
 * A tabela de decisão inteira numa linha por regra. As linhas `recebe` afirmam a presença em
 * `model_has_roles`; as `não recebe`, a ausência EM CONTEXTO ALGUM — sem isso, gravar o papel
 * noutro `team_id` passaria.
 */
it('[CT-08] recorta pelo alcance do operador o papel concedido na ficha', function (string $papel, bool $recebe): void {
    papelSemPainel();

    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));

    $registro = Role::findByName($papel);

    salvarFichaNoAdmin($this->dani, [$registro->getKey()], [$this->acme->getKey()]);

    $chave = ['model_id' => $this->dani->id, 'role_id' => $registro->getKey()];

    $recebe
        ? $this->assertDatabaseHas(pivotDePapeis(), $chave)
        : $this->assertDatabaseMissing(pivotDePapeis(), $chave);
})->with([
    'painel do próprio operador (regra 4)'      => ['admin', true],
    'papel sem painel (regra 3)'                => ['etiquetador', true],
    'painel de negócio, o default (regra 3b)'   => ['panel_user', true],
    'painel que governa a instalação (regra 5)' => ['infra', false],
    'trava por nome, que continua valendo (2)'  => ['master_global', false],
]);

/**
 * CT-09 — o alcance soma os painéis dos papéis do operador em QUALQUER organização.
 *
 * O /admin não tem tenant na rota, então o contexto do request é o global: um operador cujo
 * papel de /app vive na Acme só é reconhecido por uma leitura "em qualquer contexto".
 */
it('[CT-09] soma os paineis dos papeis do operador em qualquer organizacao', function (): void {
    $operador = usuarioDoKit('admin', 'admin@example.com');
    papelNaOrganizacao($operador, 'panel_user', $this->acme);

    $this->actingAs($operador);

    $panelUser = Role::findByName('panel_user');

    salvarFichaNoAdmin($this->dani, [$panelUser->getKey()], [$this->acme->getKey()])
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(pivotDePapeis(), [
        'model_id' => $this->dani->id, 'role_id' => $panelUser->getKey(), 'team_id' => $this->acme->id,
    ]);
});

/** CT-10 — a linha de controle: o master_global concede papel de qualquer painel. */
it('[CT-10] deixa o master_global conceder papel de qualquer painel', function (): void {
    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    $infra = Role::findByName('infra');

    salvarFichaNoAdmin($this->dani, [$infra->getKey()], [$this->acme->getKey()])
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(pivotDePapeis(), [
        'model_id' => $this->dani->id, 'role_id' => $infra->getKey(), 'team_id' => Tenant::CONTEXTO_GLOBAL,
    ]);
});

/**
 * CT-11 — o payload forjado não contorna o recorte, e o legítimo do MESMO payload é gravado.
 *
 * A segunda asserção é o que separa "recorta" de "aborta": descartar o payload inteiro por
 * causa de um item recusado também fecharia a escalada, e quebraria a tela para todo mundo.
 */
it('[CT-11] recorta o payload forjado sem descartar o papel legitimo do mesmo payload', function (): void {
    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));

    $infra = Role::findByName('infra');
    $admin = Role::findByName('admin');

    UserResource::gravarPapeis($this->dani, [$infra->getKey(), $admin->getKey()], []);

    $this->assertDatabaseMissing(pivotDePapeis(), ['model_id' => $this->dani->id, 'role_id' => $infra->getKey()]);
    $this->assertDatabaseHas(pivotDePapeis(), ['model_id' => $this->dani->id, 'role_id' => $admin->getKey()]);
});

/**
 * CT-12 — o convite individual herda a mesma trava.
 *
 * `fillForm()` escreve o state do componente direto, sem passar pela lista renderizada: a
 * linha `infra` já é o payload forjado. A linha `admin` é a célula válida, sem a qual a trava
 * poderia recusar TODO papel.
 */
it('[CT-12] aplica a trava de painel no convite individual', function (string $papel, bool $aceita): void {
    Notification::fake();

    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));

    $registro = Role::findByName($papel);

    $componente = Livewire::test(CreateConvite::class)
        ->fillForm(['email' => 'nova@example.com', 'role_id' => $registro->getKey()])
        ->call('create');

    if ($aceita) {
        $componente->assertHasNoFormErrors();

        $this->assertDatabaseHas('convites', ['email' => 'nova@example.com', 'role_id' => $registro->getKey()]);

        return;
    }

    $componente->assertHasFormErrors(['role_id']);

    $this->assertDatabaseMissing('convites', ['email' => 'nova@example.com']);
})->with([
    'painel fora do alcance' => ['infra', false],
    'painel do operador'     => ['admin', true],
]);

/**
 * CT-13 — o convite em massa herda a mesma trava (verbo irmão).
 *
 * A linha `infra` afirma sobre OS DOIS endereços: um lote que grave e só depois valide
 * deixaria o primeiro entrar.
 */
it('[CT-13] aplica a trava de painel no convite em massa', function (string $papel, bool $aceita): void {
    Notification::fake();

    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));

    $registro = Role::findByName($papel);

    $componente = Livewire::test(ListConvites::class)
        ->callAction(TestAction::make('convidarEmMassa'), [
            'emails'  => "um@example.com\ndois@example.com",
            'role_id' => $registro->getKey(),
        ]);

    if ($aceita) {
        $componente->assertHasNoFormErrors();

        expect(Convite::query()->where('role_id', $registro->getKey())->whereNull('aceito_em')->count())->toBe(2);

        return;
    }

    $componente->assertHasFormErrors(['role_id']);

    $this->assertDatabaseMissing('convites', ['email' => 'um@example.com']);
    $this->assertDatabaseMissing('convites', ['email' => 'dois@example.com']);
})->with([
    'painel fora do alcance' => ['infra', false],
    'painel do operador'     => ['admin', true],
]);

/**
 * CT-23 — salvar a ficha não REVOGA o que o operador não poderia conceder.
 *
 * Escalada por subtração: um recorte aplicado ao conjunto enviado, seguido de `sync`, derruba
 * o `infra` alheio com um clique em Salvar. A linha `escrita efetiva` é a que mata o mutante —
 * a `no-op` sozinha não mataria uma gravação que só rodasse quando o campo mudasse.
 */
it('[CT-23] nao revoga o papel fora do alcance ao salvar a ficha', function (bool $acrescentaAdmin): void {
    papelNaOrganizacao($this->dani, 'infra');
    papelNaOrganizacao($this->dani, 'panel_user', $this->acme);
    $this->dani->tenants()->attach($this->acme);

    $infra     = Role::findByName('infra');
    $panelUser = Role::findByName('panel_user');
    $admin     = Role::findByName('admin');

    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));

    salvarFichaNoAdmin(
        $this->dani,
        $acrescentaAdmin ? [$infra->getKey(), $panelUser->getKey(), $admin->getKey()] : null,
        [$this->acme->getKey()],
    )->assertHasNoFormErrors();

    $this->assertDatabaseHas(pivotDePapeis(), [
        'model_id' => $this->dani->id, 'role_id' => $infra->getKey(), 'team_id' => Tenant::CONTEXTO_GLOBAL,
    ]);
    $this->assertDatabaseHas(pivotDePapeis(), [
        'model_id' => $this->dani->id, 'role_id' => $panelUser->getKey(), 'team_id' => $this->acme->id,
    ]);

    $acrescentaAdmin
        ? $this->assertDatabaseHas(pivotDePapeis(), ['model_id' => $this->dani->id, 'role_id' => $admin->getKey()])
        : $this->assertDatabaseMissing(pivotDePapeis(), ['model_id' => $this->dani->id, 'role_id' => $admin->getKey()]);
})->with([
    'sem mexer no campo de papéis' => [false],
    'acrescentando o papel admin'  => [true],
]);

<?php

use App\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use App\Filament\App\Resources\Users\Pages\ListUsers;
use App\Filament\Pages\Auth\RegistroPorConvite;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\RegistroAberto;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * O registro aberto com a tenancy LIGADA — onde a organização deixa de ser detalhe.
 *
 * Três coisas só existem aqui, e é por isso que estes casos não podiam ficar em `tests/Kit`:
 *
 * 1. `model_has_roles.team_id`, a coluna que faz o contexto da atribuição de papel importar —
 *    papel gravado no contexto global fica INVISÍVEL dentro do /app, porque o `wherePivot` do
 *    spatie filtra pelo team do request. O usuário autentica e não vê nada;
 * 2. o papel `admin_app`, que o `PapeisSeeder` só cria no ramo de tenancy
 *    (`.ai/rules/testes.md`) — sem ele não há como testar "usuário comum não aprova" com a
 *    persona certa;
 * 3. a coluna `tenants.registro_habilitado` só tem efeito com tenancy: é ela que faz cada
 *    organização optar.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/** Liga o registro aberto, e opcionalmente a aprovação manual. */
function ligarRegistroAbertoTenancy(bool $aprovacaoManual = false): void
{
    config([
        'kit.registro.habilitado'       => true,
        'kit.registro.aprovacao_manual' => $aprovacaoManual,
        'kit.registro.verificar_email'  => false,
    ]);
}

/** Organização que aceita cadastro público. */
function organizacaoComRegistro(string $slug = 'acme', bool $ativo = true, bool $registro = true): Tenant
{
    return Tenant::factory()->create([
        'slug'                => $slug,
        'ativo'               => $ativo,
        'registro_habilitado' => $registro,
    ]);
}

/**
 * O cadastro pelo formulário apontando para uma organização — `?org={slug}`.
 *
 * Por QUERY STRING, sempre, nunca pelo construtor do componente: quem lê é `mount()`, por
 * `request()->query('org')`, que é como o link divulgado pela organização chega.
 */
function registrarNaOrganizacao(string $slug, string $email = 'novo@example.com'): Testable
{
    Filament::setCurrentPanel('app');
    Filament::auth()->logout();

    return Livewire::withQueryParams(['org' => $slug])
        ->test(RegistroPorConvite::class)
        ->fillForm([
            'name'                 => 'Fulano',
            'email'                => $email,
            'password'             => 'segredo-bem-longo-123',
            'passwordConfirmation' => 'segredo-bem-longo-123',
        ])
        ->call('register');
}

/*
|--------------------------------------------------------------------------
| R7 — só organização ativa e com registro ligado aceita cadastro
|--------------------------------------------------------------------------
*/

/**
 * CT-24 — o cenário discriminante do CONTEXTO.
 *
 * Ele afirma o papel **dentro da organização**, não só "tem o papel". Com `permission.teams`
 * ligado, papel gravado em `Tenant::CONTEXTO_GLOBAL` fica invisível dentro do /app e a pessoa
 * autentica sem ver nada — é o mutante que só este caso mata. Ver ADR-10 da wiki
 * `admin-da-organizacao`.
 */
it('vincula o cadastro aberto a organizacao que habilitou o registro', function (): void {
    ligarRegistroAbertoTenancy();

    $acme   = organizacaoComRegistro('acme');
    $globex = organizacaoComRegistro('globex', registro: false);

    registrarNaOrganizacao('acme', 'aberto@example.com')->assertHasNoFormErrors();

    $novo = User::where('email', 'aberto@example.com')->firstOrFail();

    expect($novo->tenants->pluck('slug')->all())->toBe(['acme'])
        ->and($novo->tenants->contains($globex))->toBeFalse();

    // O papel EXISTE no contexto da organização — é isto que dá acesso de verdade.
    $this->assertDatabaseHas(pivotDePapeis(), [
        'model_id'                                                    => $novo->id,
        config('permission.column_names.team_foreign_key', 'team_id') => $acme->getKey(),
    ]);

    /*
     * `papeisEmQualquerContexto()`, e NÃO `roles`. A `roles()` do spatie acrescenta
     * `wherePivot(team_id, getPermissionsTeamId())` quando `permission.teams` está ligado, e
     * aqui o contexto corrente é o global (`Tenant::CONTEXTO_GLOBAL`) — a relação devolveria
     * zero mesmo com a pivot correta no banco, que é justamente o que a asserção acima já
     * provou. É a mesma razão pela qual `canAccessPanel()` usa esta relação, e não aquela.
     */
    expect($novo->papeisEmQualquerContexto()->count())->toBe(1)
        ->and($novo->canAccessPanel(Filament::getPanel('app')))->toBeTrue();

    $this->actingAs($novo)->get("/app/{$acme->slug}")->assertSuccessful();
});

/**
 * CT-14 — as quatro linhas negativas da tabela de decisão, com a MESMA recusa.
 *
 * A resposta idêntica é de propósito: um visitante não descobre se a organização não existe,
 * está inativa ou simplesmente não quer cadastro. Mesmo princípio dos três motivos de convite
 * inválido (ADR-02 da wiki `convite-de-usuario`).
 */
it('recusa cadastro em organizacao que nao habilitou o registro', function (?string $slug, callable $arranjo): void {
    ligarRegistroAbertoTenancy();

    $arranjo();

    $url = $slug === null ? '/app/register' : "/app/register?org={$slug}";

    $this->get($url)->assertRedirect(Filament::getPanel('app')->getLoginUrl());

    expect(User::count())->toBe(0);
})->with([
    'E2 — ativa, registro desligado' => [
        'acme',
        fn (): Tenant => organizacaoComRegistro('acme', registro: false),
    ],
    'E3 — inativa, registro ligado' => [
        'acme',
        fn (): Tenant => organizacaoComRegistro('acme', ativo: false),
    ],
    'E4 — slug desconhecido' => [
        'nao-existe',
        fn (): Tenant => organizacaoComRegistro('acme'),
    ],
    'E5 — parâmetro ausente' => [
        null,
        fn (): Tenant => organizacaoComRegistro('acme'),
    ],
]);

/** CT-14b — e o chamador direto, sem organização, também é recusado. */
it('recusa o registro direto sem organizacao quando a tenancy esta ligada', function (): void {
    ligarRegistroAbertoTenancy();

    expect(fn (): User => RegistroAberto::registrar([
        'name'     => 'Direto',
        'email'    => 'direto@example.com',
        'password' => 'segredo-bem-longo-123',
    ]))->toThrow(RuntimeException::class);

    expect(User::count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| R5 — só quem administra aprova
|--------------------------------------------------------------------------
*/

/**
 * CT-23a — @premissa o usuário comum da organização não chega nem à listagem.
 *
 * A primeira barreira do kit para o `panel_user` é anterior à ação: `UserResource::canAccess()`
 * do /app depende de `ViewAny:User`, que o `PapeisSeeder` **subtrai** do papel comum junto com
 * o resto da administração da organização. A tela responde 403, e a ação de aprovar nunca é
 * alcançada.
 *
 * Medido: a primeira versão deste caso tentava `assertActionHidden()` e morria em
 * *"Invalid Livewire snapshot structure"* — o componente nem monta. A mensagem não diz isso, e
 * é a razão de o caso estar dividido em dois: `assertForbidden()` afirma a barreira que existe,
 * e CT-23b afirma a que a ação acrescenta.
 */
it('nega ao usuario comum a listagem de usuarios da organizacao', function (): void {
    ligarRegistroAbertoTenancy(aprovacaoManual: true);

    $acme = organizacaoComRegistro('acme');

    $comum = usuarioComPapel('panel_user', $acme, 'comum@example.com');
    $comum->tenants()->attach($acme->getKey());

    noPainelDa($acme);
    $this->actingAs($comum);

    Livewire::test(ListUsers::class)->assertForbidden();
});

/**
 * CT-23b — quem VÊ a listagem mas não pode editar também não aprova.
 *
 * Este é o caso que falsifica o mutante "a ação nasceu sem `->authorize()`". CT-23a não o
 * falsifica: lá a pessoa nem abre a tela, então a ação sem autorização nenhuma passaria igual.
 *
 * A persona é um papel de LEITURA criado à mão — e isso não é um cenário artificial: a tela de
 * Papéis do /admin existe exatamente para que quem administra recorte perfis assim, e o próprio
 * `PapeisSeeder` documenta que a matriz dele é o ponto de partida, não a lista final.
 *
 * `.ai/rules/filament.md`: **Action do Filament não consulta policy sozinha**. O default de
 * `$authorization` é `null` — "allowed for all users", como o vendor escreve em comentário no
 * próprio código (`vendor/filament/actions/src/Concerns/CanBeAuthorized.php:15-22`). Sem a
 * linha `->authorize('update')`, este papel de leitura aprovaria cadastros.
 *
 * Afirma o NÃO-EFEITO, e não só "a ação está escondida": continua pendente, continua sem papel.
 */
it('nega a aprovacao a quem ve a listagem mas nao pode editar usuario', function (): void {
    ligarRegistroAbertoTenancy(aprovacaoManual: true);

    $acme = organizacaoComRegistro('acme');

    registrarNaOrganizacao('acme', 'pendente@example.com');

    $pendente = User::where('email', 'pendente@example.com')->firstOrFail();

    // Papel de leitura: vê usuários da organização, não edita nenhum. `team_id` nulo na
    // DEFINIÇÃO, como o PapeisSeeder faz — o que varia por organização é a atribuição.
    $leitor = Role::query()->create([
        'name'                                                        => 'leitor_app',
        'guard_name'                                                  => 'web',
        'painel'                                                      => 'app',
        config('permission.column_names.team_foreign_key', 'team_id') => null,
    ]);
    $leitor->syncPermissions(['ViewAny:User', 'View:User']);

    $observador = usuarioComPapel('leitor_app', $acme, 'leitor@example.com');
    $observador->tenants()->attach($acme->getKey());

    noPainelDa($acme);
    $this->actingAs($observador);

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$pendente])
        ->assertActionHidden(TestAction::make('aprovar')->table($pendente));

    $depois = $pendente->fresh();

    expect($depois?->aprovacao_pendente)->toBeTrue()
        ->and($depois?->papeisEmQualquerContexto()->count())->toBe(0);
});

/**
 * CT-19b — e quem administra a organização aprova, no contexto dela.
 *
 * O par positivo de CT-23. O `admin_app` só existe nesta suíte, e é a persona real de quem
 * aprova quando a instalação é multi-organização — no /admin quem aprova é outra pessoa.
 */
it('permite ao administrador da organizacao aprovar o cadastro', function (): void {
    ligarRegistroAbertoTenancy(aprovacaoManual: true);

    $acme = organizacaoComRegistro('acme');

    registrarNaOrganizacao('acme', 'pendente@example.com');

    $pendente = User::where('email', 'pendente@example.com')->firstOrFail();

    $admin = usuarioComPapel('admin_app', $acme, 'admin@example.com');
    $admin->tenants()->attach($acme->getKey());

    noPainelDa($acme);
    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->callAction(TestAction::make('aprovar')->table($pendente))
        ->assertNotified();

    $depois = $pendente->fresh();

    expect($depois?->aprovacao_pendente)->toBeFalse();

    // O papel nasceu NA ORGANIZAÇÃO, não no contexto global — senão o acesso não existe.
    $this->assertDatabaseHas(pivotDePapeis(), [
        'model_id'                                                    => $pendente->id,
        config('permission.column_names.team_foreign_key', 'team_id') => $acme->getKey(),
    ]);

    $this->actingAs($depois)->get("/app/{$acme->slug}")->assertSuccessful();
});

/*
|--------------------------------------------------------------------------
| R9 — a organização decide pela tela
|--------------------------------------------------------------------------
*/

/**
 * CT-25 — gravação por componente: o toggle da organização grava de verdade.
 *
 * *Uma tela aberta não é uma tela que grava* (`.ai/rules/testes.md`): sem este caso, o campo
 * poderia estar fora do `$fillable` do `Tenant` e a tela seguiria verde, com o toggle voltando
 * ao estado anterior a cada salvamento.
 */
it('habilita o registro da organizacao pela tela', function (): void {
    ligarRegistroAbertoTenancy();

    $acme = organizacaoComRegistro('acme', registro: false);

    Filament::setCurrentPanel('admin');
    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    Livewire::test(EditTenant::class, ['record' => $acme->getRouteKey()])
        ->fillForm(['registro_habilitado' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($acme->fresh()?->registro_habilitado)->toBeTrue();

    // E o efeito é o que interessa: a organização passa a ser resolvida pelo registro aberto.
    expect(RegistroAberto::organizacao('acme'))->not->toBeNull();
});

/** CT-25b — com o registro global desligado, o toggle não é oferecido. */
it('esconde o toggle de registro da organizacao quando a opcao global esta desligada', function (): void {
    config(['kit.registro.habilitado' => false]);

    $acme = organizacaoComRegistro('acme', registro: false);

    Filament::setCurrentPanel('admin');
    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    Livewire::test(EditTenant::class, ['record' => $acme->getRouteKey()])
        ->assertFormFieldHidden('registro_habilitado');
});

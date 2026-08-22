<?php

use App\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use App\Filament\Admin\Resources\Tenants\RelationManagers\UsersRelationManager;
use App\Filament\App\Resources\Convites\ConviteResource;
use App\Filament\App\Resources\Convites\Pages\CreateConvite;
use App\Filament\App\Resources\Users\Pages\CreateUser;
use App\Filament\App\Resources\Users\Pages\EditUser;
use App\Filament\App\Resources\Users\Pages\ListUsers;
use App\Filament\App\Resources\Users\UserResource;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Database\Seeders\UsuarioAdminSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Psr\Log\LoggerInterface;
use Spatie\Permission\PermissionRegistrar;

/**
 * O `admin_app` — quem administra UMA organização sem administrar a instalação.
 *
 * O valor da feature não está nas duas telas: está nas seis barreiras que impedem esse
 * administrador de enxergar dados de outra organização ou de se promover a administrador
 * do sistema. Cada barreira tem um caso aqui, e o comentário de cada um diz o que quebra
 * se ele for relaxado.
 *
 * Os helpers `tenant()`, `usuario()` e `papelNaOrganizacao()` vêm de `TenancyTest.php` —
 * o Pest carrega os arquivos da suíte inteira, então rode a pasta, não um arquivo só.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * Duas organizações, quatro pessoas.
 *
 * Carla existe para o caso difícil: ela pertence às DUAS organizações, então é ela que
 * distingue um `whereHas` sobre a pivot de um `where('tenant_id', …)` que não existiria, e
 * é ela que prova que editar papéis na Acme não derruba o acesso dela na Globex.
 *
 * @return array{acme: Tenant, globex: Tenant, ana: User, beto: User, bruno: User, carla: User}
 */
function cenario(): array
{
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $ana   = papelNaOrganizacao(usuario('ana@example.com'), 'admin_app', $acme);
    $beto  = papelNaOrganizacao(usuario('beto@example.com'), 'panel_user', $acme);
    $bruno = papelNaOrganizacao(usuario('bruno@example.com'), 'admin_app', $globex);
    $carla = papelNaOrganizacao(usuario('carla@example.com'), 'panel_user', $acme);

    papelNaOrganizacao($carla, 'panel_user', $globex);

    $ana->tenants()->attach($acme);
    $beto->tenants()->attach($acme);
    $bruno->tenants()->attach($globex);
    $carla->tenants()->attach([$acme->id, $globex->id]);

    return compact('acme', 'globex', 'ana', 'beto', 'bruno', 'carla');
}

/*
|--------------------------------------------------------------------------
| As quatro fronteiras da persona
|--------------------------------------------------------------------------
*/

it('abre o painel de negocio da organizacao que administra', function (): void {
    ['ana' => $ana] = cenario();

    $this->actingAs($ana)->get('/app/acme')->assertSuccessful();
    $this->actingAs($ana)->get('/app/acme/users')->assertSuccessful();

    // A segunda rota prova as três coisas de uma vez: o Resource está registrado, tem
    // permission no banco e é alcançável. Faltando qualquer uma delas, é 403.
    expect($ana->canAccessPanel(Filament::getPanel('app')))->toBeTrue();
});

it('nao entra nos paineis de instalacao', function (string $rota): void {
    ['ana' => $ana, 'acme' => $acme] = cenario();

    // Sem esta asserção o caso passaria com um usuário SEM papel nenhum e não provaria
    // nada: o 403 tem de vir do painel do papel, não da ausência dele.
    $this->assertDatabaseHas(pivotDePapeis(), [
        'model_id' => $ana->id,
        'team_id'  => $acme->id,
    ]);

    $this->actingAs($ana)->get($rota)->assertForbidden();
})->with(['/admin', '/infra']);

it('responde 404 no painel de outra organizacao', function (): void {
    ['ana' => $ana] = cenario();

    // 404 e não 403: um 403 confirmaria que a organização EXISTE, e bastaria varrer
    // slugs para enumerar os clientes da instalação. Quem barra é canAccessTenant(),
    // depois de canAccessPanel() ter dito sim.
    $this->actingAs($ana)->get('/app/globex')->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Barreira 4 — o recorte de dado
|--------------------------------------------------------------------------
*/

it('nega o acesso direto ao registro de usuario de outra organizacao', function (): void {
    ['ana' => $ana, 'beto' => $beto, 'bruno' => $bruno] = cenario();

    // É o caso que a listagem NÃO cobre: quem barra é o route binding, que consulta
    // getEloquentQuery(). Uma implementação que escopasse só a table() passaria no caso
    // da listagem e vazaria aqui.
    $this->actingAs($ana)->get("/app/acme/users/{$bruno->uuid}/edit")->assertNotFound();
    $this->actingAs($ana)->get("/app/acme/users/{$beto->uuid}/edit")->assertSuccessful();
});

it('lista apenas os usuarios da organizacao corrente', function (): void {
    ['acme' => $acme, 'ana' => $ana, 'beto' => $beto, 'bruno' => $bruno, 'carla' => $carla] = cenario();

    noPainelDa($acme);
    $this->actingAs($ana);

    // `loadTable()` porque a tabela do kit carrega adiada (`deferLoading`): sem ele o HTML
    // testado é o do esqueleto e nenhum registro aparece.
    Livewire::test(ListUsers::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$ana, $beto, $carla])
        ->assertCanNotSeeTableRecords([$bruno]);

    // A fonte do recorte, sem passar pela tela. Carla aparece de propósito: dentro da
    // Acme ela é usuária da Acme, mesmo pertencendo também à Globex.
    expect(UserResource::getEloquentQuery()->pluck('email')->all())
        ->not->toContain('bruno@example.com')
        ->toContain('carla@example.com');
});

/*
|--------------------------------------------------------------------------
| Barreiras 1, 2, 5 — os papéis
|--------------------------------------------------------------------------
*/

it('oferece apenas papeis do painel app', function (): void {
    ['acme' => $acme, 'ana' => $ana, 'beto' => $beto] = cenario();

    noPainelDa($acme);
    $this->actingAs($ana);

    $componente = Livewire::test(EditUser::class, ['record' => $beto->uuid])
        ->instance()
        ->getSchemaComponent('form.roles');

    $opcoes = array_keys($componente->getOptions());

    expect($opcoes)
        ->toContain(Role::findByName('panel_user')->getKey())
        ->toContain(Role::findByName('admin_app')->getKey())
        ->not->toContain(Role::findByName('admin')->getKey())
        ->not->toContain(Role::findByName('infra')->getKey())
        ->not->toContain(Role::findByName('master_global')->getKey());

    // Asserção espelho, ligando a opção ao dado: escrita assim, ela não quebra quando o
    // kit ganhar um quinto papel de painel `app`.
    foreach ($opcoes as $id) {
        expect(Role::findOrFail($id)->painel)->toBe('app');
    }
});

it('grava o papel no contexto da organizacao', function (): void {
    ['acme' => $acme, 'ana' => $ana, 'beto' => $beto] = cenario();

    $canal = Mockery::spy(LoggerInterface::class);
    Log::shouldReceive('channel')->with('autenticacao')->andReturn($canal);

    $adminOrg = Role::findByName('admin_app');

    noPainelDa($acme);
    $this->actingAs($ana);

    Livewire::test(EditUser::class, ['record' => $beto->uuid])
        ->fillForm(['roles' => [$adminOrg->getKey()]])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(pivotDePapeis(), [
        'model_id' => $beto->id,
        'role_id'  => $adminOrg->getKey(),
        'team_id'  => $acme->id,
    ]);

    // A asserção que importa: `team_id = 0` produziria alguém que entra no /app e não vê
    // nada — menu vazio, 403 em tudo, sem mensagem de erro.
    $this->assertDatabaseMissing(pivotDePapeis(), [
        'model_id' => $beto->id,
        'role_id'  => $adminOrg->getKey(),
        'team_id'  => Tenant::CONTEXTO_GLOBAL,
    ]);

    // `roles` não é $fillable, então AuditsFillables não cobre esta mudança: este log é a
    // única memória de que alguém virou administrador da organização.
    $canal->shouldHaveReceived('info')
        ->withArgs(fn (string $mensagem, array $contexto): bool => str_starts_with($mensagem, '[UserResource@saveRelationshipsUsing]')
            && $contexto['alvo_id'] === $beto->id
            && $contexto['executor_id'] === $ana->id
            && $contexto['tenant_id'] === $acme->id
            && in_array('admin_app', $contexto['papeis'], true));
});

it('promove a admin da organizacao pelo relation manager', function (): void {
    $this->seed(UsuarioAdminSeeder::class);

    ['acme' => $acme, 'beto' => $beto] = cenario();

    $master   = User::where('email', config('kit.admin.email'))->firstOrFail();
    $adminOrg = Role::findByName('admin_app');

    Filament::setCurrentPanel('admin');
    $this->actingAs($master);

    Livewire::test(UsersRelationManager::class, ['ownerRecord' => $acme, 'pageClass' => EditTenant::class])
        ->callAction(TestAction::make('papeisNaOrganizacao')->table($beto), [
            'roles' => [$adminOrg->getKey()],
        ])
        ->assertHasNoFormErrors();

    // A ação roda num painel SEM tenancy, onde o contexto default do processo é
    // CONTEXTO_GLOBAL. É a troca explícita de contexto que faz o papel nascer na
    // organização certa — sem ela, o promovido entraria no /app e não veria nada.
    $this->assertDatabaseHas(pivotDePapeis(), [
        'model_id' => $beto->id,
        'role_id'  => $adminOrg->getKey(),
        'team_id'  => $acme->id,
    ]);

    $this->assertDatabaseMissing(pivotDePapeis(), [
        'model_id' => $beto->id,
        'role_id'  => $adminOrg->getKey(),
        'team_id'  => Tenant::CONTEXTO_GLOBAL,
    ]);

    // E o contexto volta ao normal — é o `finally` da ação.
    expect(app(PermissionRegistrar::class)->getPermissionsTeamId())->toBe(Tenant::CONTEXTO_GLOBAL);
});

it('descarta papel de outro painel enviado no payload', function (): void {
    ['acme' => $acme, 'ana' => $ana, 'beto' => $beto] = cenario();

    $canal = Mockery::spy(LoggerInterface::class);
    Log::shouldReceive('channel')->with('autenticacao')->andReturn($canal);

    $panelUser = Role::findByName('panel_user');
    $admin     = Role::findByName('admin');
    $master    = Role::findByName('master_global');

    noPainelDa($acme);
    $this->actingAs($ana);

    // Camada 1 — o formulário. Na 5.7.6 o `in:` que o Select monta a partir das opções
    // recusa cada id forjado antes de qualquer gravação. O erro sai por ÍNDICE
    // (`data.roles.1`), não na chave do campo — daí `assertHasFormErrors()` sem chave.
    Livewire::test(EditUser::class, ['record' => $beto->uuid])
        ->fillForm(['roles' => [$panelUser->getKey(), $admin->getKey(), $master->getKey()]])
        ->call('save')
        ->assertHasFormErrors();

    // Camada 2 — a trava de verdade, chamada direto: é o que vale se o Filament mudar de
    // comportamento num upgrade, ou se um segundo caminho de escrita (import, action em
    // massa) não passar pela validação do formulário. ADR-07 existe por isso.
    UserResource::gravarPapeis($beto, [$panelUser->getKey(), $admin->getKey(), $master->getKey()]);

    $this->assertDatabaseHas(pivotDePapeis(), [
        'model_id' => $beto->id,
        'role_id'  => $panelUser->getKey(),
        'team_id'  => $acme->id,
    ]);

    // Em QUALQUER team_id: nem na organização, nem no contexto global.
    $this->assertDatabaseMissing(pivotDePapeis(), [
        'model_id' => $beto->id,
        'role_id'  => $admin->getKey(),
    ]);

    $this->assertDatabaseMissing(pivotDePapeis(), [
        'model_id' => $beto->id,
        'role_id'  => $master->getKey(),
    ]);

    $canal->shouldHaveReceived('warning')
        ->withArgs(fn (string $mensagem, array $contexto): bool => str_starts_with($mensagem, '[UserResource@saveRelationshipsUsing]')
            && $contexto['motivo'] === 'papel_de_outro_painel'
            && count($contexto['ids_enviados']) === 3
            && count($contexto['ids_aceitos']) === 1);

    // Reforço atravessando a fronteira toda.
    $this->actingAs($beto->fresh())->get('/admin')->assertForbidden();
});

it('nao alcanca a administracao de papeis', function (): void {
    ['ana' => $ana] = cenario();

    $resources = Filament::getPanel('app')->getResources();

    // Por class_basename para pegar de uma vez o Resource publicado pelo kit e o do
    // vendor. Este caso falha se alguém "completar" a feature registrando o RoleResource
    // no painel app — o cenário em que criar um papel dentro de uma organização o tornaria
    // visível em todas as outras (a definição do papel é global, `roles.team_id` nulo).
    expect(collect($resources)->map(fn (string $r): string => class_basename($r))->all())
        ->not->toContain('RoleResource');

    expect($ana->can('Create:Role'))->toBeFalse()
        ->and($ana->can('Update:Role'))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Barreira 6 — o convite nasce na organização de quem o criou
|--------------------------------------------------------------------------
*/

it('carimba a organizacao no convite ignorando o formulario', function (): void {
    ['acme' => $acme, 'globex' => $globex, 'ana' => $ana] = cenario();

    noPainelDa($acme);
    $this->actingAs($ana);

    Livewire::test(CreateConvite::class)
        ->fillForm([
            'email'   => 'novo@example.com',
            'role_id' => Role::findByName('panel_user')->getKey(),
            // Forjado: o campo não existe no formulário. O $fillable do Convite NÃO
            // protege — `tenant_id` está dentro dele, para o Select do /admin funcionar.
            'tenant_id' => $globex->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('convites', ['email' => 'novo@example.com', 'tenant_id' => $acme->id]);
    $this->assertDatabaseMissing('convites', ['tenant_id' => $globex->id]);

    // O outro lado, a leitura: da Globex esse convite não existe.
    Filament::setTenant($globex, isQuiet: true);
    expect(ConviteResource::getEloquentQuery()->count())->toBe(0);
});

it('recusa papel de outro painel no convite', function (): void {
    ['acme' => $acme, 'ana' => $ana] = cenario();

    noPainelDa($acme);
    $this->actingAs($ana);

    // `role_id` é coluna escalar, não relação: não há saveRelationshipsUsing onde filtrar,
    // então a trava de escrita é a regra de validação do campo. Sem ela o convite
    // promoveria alguém a administrador da INSTALAÇÃO.
    Livewire::test(CreateConvite::class)
        ->fillForm([
            'email'   => 'escalada@example.com',
            'role_id' => Role::findByName('admin')->getKey(),
        ])
        ->call('create')
        ->assertHasFormErrors(['role_id']);

    $this->assertDatabaseMissing('convites', ['email' => 'escalada@example.com']);
});

/*
|--------------------------------------------------------------------------
| Criação, subtração e efeitos colaterais
|--------------------------------------------------------------------------
*/

it('vincula o usuario criado a organizacao corrente', function (): void {
    ['acme' => $acme, 'globex' => $globex, 'ana' => $ana] = cenario();

    noPainelDa($acme);
    $this->actingAs($ana);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name'     => 'Fulano',
            'email'    => 'fulano@example.com',
            'password' => 'password1234',
            'roles'    => [Role::findByName('panel_user')->getKey()],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $novo = User::where('email', 'fulano@example.com')->firstOrFail();

    // Sem o afterCreate o usuário nasceria órfão: criado, e invisível na própria tela que
    // o criou — porque getEloquentQuery() filtra pela pivot.
    $this->assertDatabaseHas('tenant_user', ['user_id' => $novo->id, 'tenant_id' => $acme->id]);
    $this->assertDatabaseMissing('tenant_user', ['user_id' => $novo->id, 'tenant_id' => $globex->id]);
    $this->assertDatabaseHas(pivotDePapeis(), ['model_id' => $novo->id, 'team_id' => $acme->id]);

    expect(UserResource::getEloquentQuery()->pluck('email')->all())->toContain('fulano@example.com')
        ->and($novo->canAccessTenant($globex))->toBeFalse();
});

it('mantem o usuario comum fora da administracao da organizacao', function (): void {
    ['beto' => $beto] = cenario();

    // /app abre: ele é usuário do negócio. As telas de administração, não.
    $this->actingAs($beto)->get('/app/acme')->assertSuccessful();
    $this->actingAs($beto)->get('/app/acme/users')->assertForbidden();
    $this->actingAs($beto)->get('/app/acme/convites')->assertForbidden();

    // No dado, que é onde a regressão nasce: sem a subtração do PapeisSeeder, `panel_user`
    // herda a matriz inteira do painel e todo usuário comum vira admin da organização —
    // sem migration, sem erro, com a tela funcionando.
    expect($beto->can('ViewAny:User'))->toBeFalse()
        ->and($beto->can('Create:User'))->toBeFalse()
        ->and($beto->can('Create:Convite'))->toBeFalse()
        // E o contraste que prova que a subtração não subtraiu demais.
        ->and($beto->can('ViewAny:Projeto'))->toBeTrue();
});

it('preserva os papeis do usuario nas outras organizacoes', function (): void {
    ['acme' => $acme, 'globex' => $globex, 'ana' => $ana, 'carla' => $carla] = cenario();

    $panelUser = Role::findByName('panel_user');
    $adminOrg  = Role::findByName('admin_app');

    noPainelDa($acme);
    $this->actingAs($ana);

    Livewire::test(EditUser::class, ['record' => $carla->uuid])
        ->fillForm(['roles' => [$adminOrg->getKey()]])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(pivotDePapeis(), [
        'model_id' => $carla->id, 'role_id' => $adminOrg->getKey(), 'team_id' => $acme->id,
    ]);

    $this->assertDatabaseMissing(pivotDePapeis(), [
        'model_id' => $carla->id, 'role_id' => $panelUser->getKey(), 'team_id' => $acme->id,
    ]);

    // Quem garante é o spatie, não o kit: `syncRoles()` apaga pela pivot escopada no team
    // corrente. É COMPORTAMENTO DE VENDOR — daí o caso, que acusa se um upgrade mudar
    // isso. Sem ele, uma edição na Acme derrubaria o acesso da Carla na Globex.
    $this->assertDatabaseHas(pivotDePapeis(), [
        'model_id' => $carla->id, 'role_id' => $panelUser->getKey(), 'team_id' => $globex->id,
    ]);
});

it('fecha a consulta de usuarios quando nao ha organizacao corrente', function (): void {
    ['acme' => $acme] = cenario();

    $canal = Mockery::spy(LoggerInterface::class);
    Log::shouldReceive('channel')->with('autenticacao')->andReturn($canal);

    // O estado de um job, um comando ou um `pulse:check`. O escopo nativo do Filament, no
    // mesmo cenário, devolveria a base INTEIRA de usuários da instalação — é o argumento
    // decisivo de ADR-03.
    Filament::setCurrentPanel('app');
    Filament::setTenant(null, isQuiet: true);

    expect(UserResource::getEloquentQuery()->count())->toBe(0);

    $canal->shouldHaveReceived('warning')
        ->withArgs(fn (string $mensagem, array $contexto): bool => str_starts_with($mensagem, '[UserResource@getEloquentQuery]')
            && $contexto['motivo'] === 'sem_tenant_corrente');

    Filament::setTenant($acme, isQuiet: true);

    expect(UserResource::getEloquentQuery()->count())->toBe(3);
});

it('nao delega o escopo de usuario ao Filament', function (): void {
    expect(UserResource::isScopedToTenant())->toBeFalse();

    Filament::setCurrentPanel('app');
    Filament::setTenant(tenant('Acme', 'acme'), isQuiet: true);

    // Sem `$isScopedToTenant = false`, Panel::boot() registra o global scope de tenancy no
    // model User e esta linha morre com `LogicException: The model [App\Models\User] does
    // not have a relationship named [tenant]`.
    expect(User::query()->count())->toBeInt()
        // E o efeito colateral que a mesma remoção traria: o model User é consultado pelo
        // guard de autenticação e pelo /admin — ele não pode ganhar escopo global.
        ->and(User::hasGlobalScope('app_tenancy'))->toBeFalse();
});

it('nao permite excluir usuario a partir da organizacao', function (): void {
    ['acme' => $acme, 'ana' => $ana, 'beto' => $beto] = cenario();

    noPainelDa($acme);
    $this->actingAs($ana);

    Livewire::test(ListUsers::class)
        ->assertActionDoesNotExist(TestAction::make('delete')->table($beto));

    // A permissão `Delete:User` EXISTE no papel (a matriz é a do painel inteiro) — a trava
    // é o resource. Excluir a Carla apagaria o vínculo dela com a Globex por
    // cascadeOnDelete: uma operação de dentro da Acme atravessando a fronteira.
    expect(UserResource::canDelete($beto))->toBeFalse()
        ->and(UserResource::canDeleteAny())->toBeFalse()
        ->and($ana->can('Delete:User'))->toBeTrue();

    $this->assertDatabaseHas('users', ['id' => $beto->id]);
});

/*
|--------------------------------------------------------------------------
| Barreira 7 — o que não é tela do /app não entra na matriz de ninguém
|--------------------------------------------------------------------------
*/

/**
 * O `admin_app` recebe "a matriz do painel inteira" — e por isso herdava as 14 permissions
 * de `Exception`, `DeleteAny:Exception` inclusive.
 *
 * O `ExceptionResource` está no painel `app` só por obrigação técnica: o
 * `FilamentExceptionsPlugin` precisa estar registrado nos três painéis para o pacote não
 * estourar `LogicException` em todo request. Ele é registrado com `registerNavigation(false)`,
 * o que esconde o item do menu e **não** fecha o acesso — `registerNavigation` mexe em
 * `shouldRegisterNavigation()`, nunca em `canAccess()`. As rotas existem no painel.
 *
 * O caso assere as duas metades da correção, porque cada uma falha por um motivo diferente:
 * o `admin_app` perde `Exception` (a correção nova) e **mantém** `User` e `Convite` (o que
 * não pode ser tirado junto — o recorte é por motivo, não por "tudo que não é do negócio").
 *
 * Ver QA-01 do `06-relatorio-qa.md` da wiki admin-da-organizacao.
 */
it('nao concede permissao de excecao a quem administra a organizacao', function (): void {
    ['acme' => $acme, 'ana' => $ana] = cenario();

    // Sem o tenant corrente o `can()` não resolve papel de team NENHUM e devolve false
    // para tudo — as asserções de `Exception` passariam à toa. É por isso que a metade
    // positiva (`User`, `Convite`) está no mesmo caso: ela é o que prova que o oráculo
    // está vivo. Medido: sem esta linha, `ViewAny:User` também dá false.
    noPainelDa($acme);
    $this->actingAs($ana);

    expect($ana->can('ViewAny:Exception'))->toBeFalse()
        ->and($ana->can('View:Exception'))->toBeFalse()
        ->and($ana->can('Delete:Exception'))->toBeFalse()
        ->and($ana->can('DeleteAny:Exception'))->toBeFalse();

    // A outra metade: administrar a organização continua possível.
    expect($ana->can('ViewAny:User'))->toBeTrue()
        ->and($ana->can('Create:User'))->toBeTrue()
        ->and($ana->can('ViewAny:Convite'))->toBeTrue();
});

/**
 * E o `panel_user` também não as tem — ele já era coberto pela subtração antiga, e este
 * caso existe para o dia em que alguém unificar as duas listas do `PapeisSeeder` de novo:
 * unificar volta a dar `Exception` ao `admin_app`, ou tira `User`/`Convite` dele.
 */
it('nao concede permissao de excecao ao usuario comum da organizacao', function (): void {
    ['acme' => $acme, 'beto' => $beto] = cenario();

    noPainelDa($acme);
    $this->actingAs($beto);

    expect($beto->can('ViewAny:Exception'))->toBeFalse()
        ->and($beto->can('DeleteAny:Exception'))->toBeFalse()
        // O oráculo vivo, como no caso acima: o usuário comum enxerga o negócio.
        ->and($beto->can('ViewAny:Projeto'))->toBeTrue();
});

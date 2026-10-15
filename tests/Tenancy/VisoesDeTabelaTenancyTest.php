<?php

use App\Filament\Admin\Resources\Roles\Pages\EditRole;
use App\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use App\Filament\Admin\Resources\Tenants\Pages\ListTenants;
use App\Filament\Admin\Resources\Tenants\RelationManagers\UsersRelationManager;
use App\Filament\Admin\Resources\Users\Pages\ListUsers as ListUsersDoAdmin;
use App\Filament\App\Resources\Convites\Pages\ListConvites;
use App\Filament\App\Resources\Projetos\Pages\ListProjetos;
use App\Filament\App\Resources\Users\Pages\ListUsers;
use App\Models\Tenant;
use App\Models\User;
use Asignua\FilamentInfiniteScroll\InfiniteScrollMode;
use Asignua\FilamentInfiniteScroll\InfiniteScrollRegistry;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Sehsah\FilamentRememberTable\Models\TableView;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    config(['kit.demo' => true]);
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/** @return array{Tenant, Tenant, User} */
function daniDasVisoesTenancy(): array
{
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');
    $dani   = usuarioComPapel('admin_app', $acme, 'dani@example.com');
    papelNaOrganizacao($dani, 'admin_app', $globex);
    $dani->tenants()->attach([$acme->id, $globex->id]);

    return [$acme, $globex, $dani];
}

/** @param array<string, mixed> $query */
function abrirListagemDasVisoesTenancy(User $usuario, string $pagina, ?Tenant $tenant = null, array $query = []): Testable
{
    fronteiraDeRequest();
    test()->flushSession();
    $usuario->unsetRelation('roles')->unsetRelation('permissions');
    $painel = $tenant === null ? 'admin' : 'app';
    Filament::setCurrentPanel($painel);
    $resource = $pagina::getResource();
    $url      = $resource::getUrl('index', panel: $painel, tenant: $tenant);
    test()->actingAs($usuario)->get($url.($query === [] ? '' : '?'.http_build_query($query)))->assertOk();
    if ($tenant !== null) {
        noPainelDa($tenant);
    }

    return Livewire::withQueryParams($query)->test($pagina)->loadTable();
}

/** @param array<string, mixed> $atributos */
function visaoDeProjetosTenancy(User $dono, ?Tenant $tenant, array $atributos = []): TableView
{
    $visao = visaoGravada($dono, ListProjetos::class, ['search' => 'zz'], ['name' => 'Alvo', 'tenant_id' => $tenant?->id, ...$atributos]);
    $visao->update(['table_key' => app(ListProjetos::class)->getTableViewsKey()]);

    return $visao;
}

it('[CT-05] oferece o botão Visões conforme o interruptor nas listagens Tenancy', function (string $pagina, bool $ligadas): void {
    $acme   = tenant('Acme', 'acme');
    $global = $pagina === ListTenants::class;
    $ana    = usuarioComPapel($global ? 'admin' : 'admin_app', $global ? null : $acme, 'ana@example.com');
    if (! $global) {
        $ana->tenants()->attach($acme);
    }
    concederVisoesDeTabela('admin', 'admin_app');
    ligarVisoesDeTabela($ligadas);

    $tela = abrirListagemDasVisoesTenancy($ana, $pagina, $global ? null : $acme);

    if ($ligadas) {
        $tela->assertSee('Visões');
        expect($tela->instance()->getTableViewActions())->not->toBeEmpty();
    } else {
        $tela->assertDontSee('Visões');
        expect($tela->instance()->getTableViewActions())->toBe([]);
    }
})->with([
    '/app/acme/projetos | Ana (admin_app da Acme) | desligadas | não é' => [ListProjetos::class, false],
    '/app/acme/users | Ana (admin_app da Acme) | desligadas | não é'    => [ListUsers::class, false],
    '/app/acme/convites | Ana (admin_app da Acme) | desligadas | não é' => [ListConvites::class, false],
    '/admin/organizacoes | admin global | desligadas | não é'           => [ListTenants::class, false],
    '/app/acme/projetos | Ana (admin_app da Acme) | ligadas | é'        => [ListProjetos::class, true],
    '/app/acme/users | Ana (admin_app da Acme) | ligadas | é'           => [ListUsers::class, true],
    '/app/acme/convites | Ana (admin_app da Acme) | ligadas | é'        => [ListConvites::class, true],
    '/admin/organizacoes | admin global | ligadas | é'                  => [ListTenants::class, true],
])->group('kit');

it('[CT-23] Edu aplica a compartilhada sem receber a ação de salvar', function (): void {
    $acme = tenant('Acme', 'acme');
    $ana  = usuarioComPapel('admin_app', $acme, 'ana@example.com');
    $edu  = usuarioComPapel('panel_user', $acme, 'edu@example.com');
    $ana->tenants()->attach($acme);
    $edu->tenants()->attach($acme);
    semAPermissao('panel_user', 'Usar:VisoesDeTabela');
    ligarVisoesDeTabela(true, true);
    $visao = visaoDeProjetosTenancy($ana, $acme, ['name' => 'Atrasados', 'is_public' => true]);
    $acao  = 'applyTableView_'.md5((string) $visao->id);

    abrirListagemDasVisoesTenancy($edu, ListProjetos::class, $acme)
        ->assertSee('Visões')->assertActionVisible($acao)->assertActionHidden('saveTableView')
        ->callAction($acao)->assertSet('tableSearch', 'zz');
})->group('kit');

it('[CT-26] salvar usa a organização aberta apesar da sessão e do payload', function (bool $forjar): void {
    [$acme, $globex, $dani] = daniDasVisoesTenancy();
    ligarVisoesDeTabela();
    concederVisoesDeTabela('admin_app');
    $tela = abrirListagemDasVisoesTenancy($dani, ListProjetos::class, $acme);
    session(['tenant_corrente' => $globex->id]);

    $tela->mountAction('saveTableView')->set('mountedActions.0.data.name', 'Da Acme');
    if ($forjar) {
        $tela->set('mountedActions.0.data.tenant_id', $globex->id);
    }
    $tela->callMountedAction()->assertHasNoErrors();

    $this->assertDatabaseHas('remember_table_views', ['name' => 'Da Acme', 'user_id' => $dani->id, 'tenant_id' => $acme->id]);
})->with([
    'nenhum campo forjado'            => [false],
    'tenant_id forjado como a Globex' => [true],
])->group('kit');

it('[CT-31] aplica e lista somente a visão visível na organização aberta', function (string $regra, string $org, bool $aparece, string $busca): void {
    [$acme, $globex, $dani] = daniDasVisoesTenancy();
    ligarVisoesDeTabela(true, true);
    $dono = $dani;
    $ator = $dani;
    if (in_array($regra, ['g', 'e', 'h'], true)) {
        $dono = usuarioComPapel('admin_app', $acme, 'autora@example.com');
        $dono->tenants()->attach($acme);
        $ator = usuarioComPapel('admin_app', $org === 'globex' ? $globex : $acme, 'leitora@example.com');
        $ator->tenants()->attach($org === 'globex' ? $globex : $acme);
    }
    $visao = visaoDeProjetosTenancy($dono, $regra === 'h' ? null : $acme, ['is_public' => in_array($regra, ['g', 'e', 'h'], true)]);
    $tela  = abrirListagemDasVisoesTenancy($ator, ListProjetos::class, $org === 'globex' ? $globex : $acme);

    $tela->call('applyTableView', (string) $visao->id)->assertSet('tableSearch', $busca);

    expect($tela->instance()->getTableViews()->contains('id', $visao->id))->toBe($aparece);
    if ($aparece) {
        $tela->assertSee('Alvo');
    } else {
        $tela->assertDontSee('Alvo');
    }
})->with([
    'd | da Dani, privada, gravada em /app/acme/projetos | a Dani (Acme e Globex) | globex | não aparece | ""'                 => ['d', 'globex', false, ''],
    'a | da Dani, privada, gravada em /app/acme/projetos | a Dani (Acme e Globex) | acme | aparece | "zz"'                     => ['a', 'acme', true, 'zz'],
    'g | da Ana (Acme), compartilhada, em /app/acme/projetos | a Carla (Globex) | globex | não aparece | ""'                   => ['g', 'globex', false, ''],
    'e | da Ana (Acme), compartilhada, em /app/acme/projetos | a Bia (Acme) | acme | aparece | "zz"'                           => ['e', 'acme', true, 'zz'],
    'h | da Bia, compartilhada, com tenant_id nulo e a chave da listagem de projetos | a Ana (Acme) | acme | não aparece | ""' => ['h', 'acme', false, ''],
])->group('kit');

it('[CT-34] a URL de outra organização não aplica a visão nem revela seu nome', function (bool $propria, string $busca): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');
    $ana    = usuarioComPapel('admin_app', $acme, 'ana@example.com');
    $carla  = usuarioComPapel('admin_app', $globex, 'carla@example.com');
    $ana->tenants()->attach($acme);
    $carla->tenants()->attach($globex);
    ligarVisoesDeTabela(true, true);
    $daAcme   = visaoDeProjetosTenancy($ana, $acme, ['name' => 'Plano da Acme', 'is_public' => true]);
    $daGlobex = visaoDeProjetosTenancy($carla, $globex, ['name' => 'Plano da Globex']);
    $visao    = $propria ? $daGlobex : $daAcme;

    $resposta = $this->actingAs($carla)->get('/app/globex/projetos?view='.$visao->id)->assertOk();
    abrirListagemDasVisoesTenancy($carla, ListProjetos::class, $globex, ['view' => (string) $visao->id])->assertSet('tableSearch', $busca);

    if ($propria) {
        $resposta->assertSee('Plano da Globex');
    } else {
        $resposta->assertDontSee('Plano da Acme');
    }
})->with([
    '"Plano da Globex" | "zz" | contém' => [true, 'zz'],
    '"Plano da Acme" | "" | não contém' => [false, ''],
])->group('kit');

it('[CT-41] aplica o modo contínuo e exatamente sete registros por página nas tabelas Tenancy', function (string $pagina, string $modo, InfiniteScrollMode $esperado, bool $relacao): void {
    $acme = tenant('Acme', 'acme');
    ligarCarregamento($modo);
    $global  = $pagina === ListTenants::class || $relacao;
    $usuario = usuarioComPapel($global ? 'master_global' : 'admin_app', $global ? null : $acme);
    if (! $global) {
        $usuario->tenants()->attach($acme);
    }

    if ($relacao) {
        $this->actingAs($usuario)->get('/admin/organizacoes/'.$acme->getRouteKey().'/edit')->assertOk();
        $tela = Livewire::test(UsersRelationManager::class, ['ownerRecord' => $acme, 'pageClass' => EditTenant::class])->loadTable();
    } else {
        $tela = abrirListagemDasVisoesTenancy($usuario, $pagina, $global ? null : $acme);
    }
    $tabela = $tela->instance()->getTable();

    expect(app(InfiniteScrollRegistry::class)->get($tabela)?->mode)->toBe($esperado);
    expect($tabela->getPaginationPageOptions())->toBe([7]);
})->with([
    '/app/acme/projetos | admin_app (Tenancy) | rolagem | Scroll | kit, /app'                                                              => [ListProjetos::class, 'rolagem', InfiniteScrollMode::Scroll, false],
    '/app/acme/users | admin_app (Tenancy) | botao | Button | kit, /app'                                                                   => [ListUsers::class, 'botao', InfiniteScrollMode::Button, false],
    '/app/acme/convites | admin_app (Tenancy) | rolagem | Scroll | kit, /app'                                                              => [ListConvites::class, 'rolagem', InfiniteScrollMode::Scroll, false],
    '/admin/organizacoes | master_global (Tenancy) | rolagem | Scroll | kit'                                                               => [ListTenants::class, 'rolagem', InfiniteScrollMode::Scroll, false],
    'relation manager de usuários em /admin/organizacoes/{acme}/edit | master_global (Tenancy) | rolagem | Scroll | kit, relation manager' => [UsersRelationManager::class, 'rolagem', InfiniteScrollMode::Scroll, true],
])->group('kit');

it('[CT-61] conta o teto de cinquenta visões separadamente em cada organização', function (string $org, int $total): void {
    expect(config('remember-table.max_views_per_table'))->toBe(50);
    [$acme, $globex, $dani] = daniDasVisoesTenancy();
    ligarVisoesDeTabela();
    concederVisoesDeTabela('admin_app');
    for ($indice = 1; $indice <= 50; $indice++) {
        visaoDeProjetosTenancy($dani, $acme, ['name' => 'Visão '.$indice]);
    }
    $alvo = $org === 'acme' ? $acme : $globex;
    $tela = abrirListagemDasVisoesTenancy($dani, ListProjetos::class, $alvo);
    limparAvisosDoFilament();

    $tela->callAction('saveTableView', ['name' => 'Mais uma'])->assertHasNoErrors();

    expect(TableView::query()->where('user_id', $dani->id)->where('table_key', app(ListProjetos::class)->getTableViewsKey())->where('tenant_id', $alvo->id)->count())->toBe($total);
    if ($org === 'acme') {
        $this->assertDatabaseMissing('remember_table_views', ['name' => 'Mais uma', 'user_id' => $dani->id]);
        expect(array_column(avisosDoFilament(), 'title'))->toContain(__('filament-remember-table::remember-table.notifications.limit_reached', ['limit' => 50]));
    } else {
        $this->assertDatabaseHas('remember_table_views', ['name' => 'Mais uma', 'user_id' => $dani->id, 'tenant_id' => $globex->id]);
    }
})->with([
    'acme | recusado, com o aviso de limite | 50 | mesma organização, borda+1' => ['acme', 50],
    'globex | aceito | 1 | outra organização não conta'                        => ['globex', 1],
])->group('kit');

it('[CT-65] a visão do app não aparece nem se aplica na listagem homônima do admin', function (string $entrada): void {
    $acme = tenant('Acme', 'acme');
    $ana  = usuarioComPapel('admin_app', $acme, 'ana@example.com');
    $ana->tenants()->attach($acme);
    $gil = usuarioComPapel('admin', null, 'gil@example.com');
    ligarVisoesDeTabela(true, true);
    $visao = visaoGravada($ana, ListUsers::class, ['search' => 'zz'], ['name' => 'Da Acme', 'tenant_id' => $acme->id, 'is_public' => true]);
    $visao->update(['table_key' => app(ListUsers::class)->getTableViewsKey()]);

    $tela = abrirListagemDasVisoesTenancy($gil, ListUsersDoAdmin::class, query: $entrada === 'url' ? ['view' => (string) $visao->id] : []);
    if ($entrada === 'metodo') {
        $tela->call('applyTableView', (string) $visao->id);
    }

    $tela->assertSet('tableSearch', '')->assertDontSee('Da Acme');
    expect($tela->instance()->getTableViews()->contains('id', $visao->id))->toBeFalse();
})->with([
    'abre /admin/users'                          => ['normal'],
    'abre /admin/users?view={id de "Da Acme"}'   => ['url'],
    'chama applyTableView com o id de "Da Acme"' => ['metodo'],
])->group('kit');

it('[CT-66] a permissão de salvar vem do papel da organização aberta', function (string $org, bool $oferecida, int $linhas): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');
    $fabio  = usuarioComPapel('admin_app', $globex, 'fabio@example.com');
    papelNaOrganizacao($fabio, 'panel_user', $acme);
    $fabio->tenants()->attach([$acme->id, $globex->id]);
    concederVisoesDeTabela('admin_app');
    semAPermissao('panel_user', 'Usar:VisoesDeTabela');
    ligarVisoesDeTabela();
    $tela = abrirListagemDasVisoesTenancy($fabio, ListProjetos::class, $org === 'acme' ? $acme : $globex);
    session(['tenant_corrente' => $globex->id]);

    if ($oferecida) {
        $tela->assertActionVisible('saveTableView');
    } else {
        $tela->assertActionHidden('saveTableView');
    }
    $tela->call('mountAction', 'saveTableView');
    if ($oferecida) {
        $tela->set('mountedActions.0.data.name', 'Do Fábio');
    }
    $tela->call('callMountedAction');

    expect(TableView::query()->where('user_id', $fabio->id)->count())->toBe($linhas);
})->with([
    'acme | não é | 0 | panel_user' => ['acme', false, 0],
    'globex | é | 1 | admin_app'    => ['globex', true, 1],
])->group('kit');

it('[CT-67] marcar a permissão pela tela de papéis permite ao Edu salvar sua visão', function (): void {
    $acme = tenant('Acme', 'acme');
    $edu  = usuarioComPapel('panel_user', $acme, 'edu@example.com');
    $edu->tenants()->attach($acme);
    semAPermissao('panel_user', 'Usar:VisoesDeTabela');
    ligarVisoesDeTabela();
    abrirListagemDasVisoesTenancy($edu, ListProjetos::class, $acme)->assertActionHidden('saveTableView');
    $master = usuarioComPapel('master_global', null, 'master@example.com');
    fronteiraDeRequest();
    $this->flushSession();
    $papel = papelDoKit('panel_user');
    $this->actingAs($master)->get('/admin/shield/roles/'.$papel->getRouteKey().'/edit')->assertOk();
    $edicao = Livewire::test(EditRole::class, ['record' => $papel->getRouteKey()]);
    $campos = array_filter($edicao->instance()->form->getFlatComponents(withHidden: true), static fn ($campo): bool => $campo instanceof CheckboxList && array_key_exists('Usar:VisoesDeTabela', $campo->getOptions()));
    expect($campos)->toHaveCount(1);
    $campo = array_values($campos)[0];
    $edicao->set($campo->getStatePath(), [...($campo->getState() ?? []), 'Usar:VisoesDeTabela'])->call('save')->assertHasNoFormErrors();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $edu->unsetRelation('roles')->unsetRelation('permissions');

    abrirListagemDasVisoesTenancy($edu, ListProjetos::class, $acme)->assertActionVisible('saveTableView')
        ->callAction('saveTableView', ['name' => 'Do Edu'])->assertHasNoErrors();

    $this->assertDatabaseHas('remember_table_views', ['name' => 'Do Edu', 'user_id' => $edu->id, 'user_type' => $edu->getMorphClass()]);
})->group('kit');

it('[CT-68] a padrão e sua URL só se aplicam na organização em que foram gravadas', function (string $org, bool $parametro, string $busca): void {
    [$acme, $globex, $dani] = daniDasVisoesTenancy();
    ligarVisoesDeTabela();
    $visao = visaoDeProjetosTenancy($dani, $acme, ['name' => 'Da Acme', 'is_default' => true]);

    abrirListagemDasVisoesTenancy($dani, ListProjetos::class, $org === 'acme' ? $acme : $globex, $parametro ? ['view' => (string) $visao->id] : [])
        ->assertSet('tableSearch', $busca);
})->with([
    '/app/acme/projetos | "zz"'                        => ['acme', false, 'zz'],
    '/app/globex/projetos | ""'                        => ['globex', false, ''],
    '/app/globex/projetos?view={id de "Da Acme"} | ""' => ['globex', true, ''],
])->group('kit');

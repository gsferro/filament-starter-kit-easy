<?php

use App\Filament\App\Pages\Dashboard as DashboardDoApp;
use App\Filament\Pages\DashboardClassico;
use App\Models\Tenant;
use App\Services\Dashboard\CriadorDeDashboardPadrao;
use Database\Seeders\DashboardPadraoSeeder;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use MDDev\DynamicDashboard\DashboardModelHelper;
use MDDev\DynamicDashboard\Models\Dashboard;
use MDDev\DynamicDashboard\Models\DashboardWidget;

/**
 * A fronteira de organização do dashboard dinâmico — a suíte multi-tenant.
 *
 * Os IDs de CT são os de
 * `wikis/specs/main/dashboard-dinamico-nos-paineis/04-casos-de-teste.md`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

function modeloDeDashboard(): string
{
    return DashboardModelHelper::model();
}

/*
 * CT-11 — o tenant B não vê o dashboard do tenant A.
 *
 * O global scope filtra pelo tenant corrente em painel tenant-aware. Sem ele,
 * a grade de uma organização vazaria para a outra — IDOR por omissão.
 */
it('esconde o dashboard de outra organizacao', function (): void {
    // Organizações criadas com a flag DESLIGADA: sem dashboard do observer,
    // o único que existe é o da Acme, criado à mão depois.
    ligarDashboardDinamico(false);

    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    ligarDashboardDinamico(true);

    $daAcme = CriadorDeDashboardPadrao::para($acme);

    noPainelDa($globex);

    $model = modeloDeDashboard();

    expect($model::query()->find($daAcme->getKey()))->toBeNull()
        ->and($model::query()->count())->toBe(0);
});

/*
 * CT-12 — a criação dentro do painel grava o tenant corrente.
 */
it('carimba o tenant corrente na criacao pelo painel', function (): void {
    ligarDashboardDinamico(true);

    $acme = tenant('Acme', 'acme');
    noPainelDa($acme);

    $model      = modeloDeDashboard();
    $dashboard  = new $model;
    $dashboard->fill([
        'name'        => 'Livre',
        'page'        => DashboardDoApp::class,
        'is_active'   => true,
        'is_personal' => false,
        'ordering'    => 1,
    ]);
    $dashboard->save();

    expect($dashboard->refresh()->tenant_id)->toBe($acme->getKey());
});

/*
 * CT-13 — `tenant_id` não é fillable e o hook sobrescreve SEMPRE.
 *
 * Payload forjado com o tenant de outra organização não cola: o hook
 * `creating` regrava o tenant corrente por cima (P10).
 */
it('ignora tenant_id forjado no payload', function (): void {
    ligarDashboardDinamico(true);

    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    noPainelDa($acme);

    $model     = modeloDeDashboard();
    $dashboard = new $model;
    $dashboard->fill([
        'name'        => 'Forjado',
        'page'        => DashboardDoApp::class,
        'is_active'   => true,
        'is_personal' => false,
        'ordering'    => 1,
        'tenant_id'   => $globex->getKey(),
    ]);
    $dashboard->save();

    expect($dashboard->refresh()->tenant_id)->toBe($acme->getKey());
});

/*
 * CT-14 — organização nova ganha o dashboard padrão dela.
 */
it('semeia o dashboard padrao para a organizacao nova', function (): void {
    ligarDashboardDinamico(true);

    $acme = tenant('Acme', 'acme');

    $model = modeloDeDashboard();

    expect(
        $model::query()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $acme->getKey())
            ->where('page', DashboardDoApp::class)
            ->exists()
    )->toBeTrue();
});

/*
 * CT-15 — com a feature desligada, o observer não cria nada.
 */
it('nao semeia dashboard com a feature desligada', function (): void {
    ligarDashboardDinamico(false);

    $acme = tenant('Acme', 'acme');

    $model = modeloDeDashboard();

    expect(
        $model::query()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $acme->getKey())
            ->exists()
    )->toBeFalse();
});

/*
 * CT-27 — flag ligada MAS o /app fora da lista: o observer não semeia.
 *
 * O gate é por painel (`habilitadoPara`), não pela flag global — sem ele,
 * uma instalação que ligou a feature só para o /admin semearia dashboards
 * órfãos a cada organização criada (P5).
 */
it('nao semeia quando o painel app esta fora da lista', function (): void {
    ligarDashboardDinamico(true, ['admin']);

    $acme = tenant('Acme', 'acme');

    $model = modeloDeDashboard();

    expect(
        $model::query()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $acme->getKey())
            ->exists()
    )->toBeFalse();
});

/*
 * CT-16 — o seeder é idempotente: duas rodadas, um "Padrão" por organização.
 */
it('roda o seeder duas vezes sem duplicar', function (): void {
    ligarDashboardDinamico(true);

    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    // O observer já semeou na criação — o seeder não pode duplicar.
    $this->seed(DashboardPadraoSeeder::class);
    $this->seed(DashboardPadraoSeeder::class);

    $model = modeloDeDashboard();

    foreach ([$acme, $globex] as $tenant) {
        expect(
            $model::query()
                ->withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->getKey())
                ->count()
        )->toBe(1);
    }
});

/*
 * CT-17 — excluir a organização não apaga o dashboard: `nullOnDelete`.
 */
it('preserva o dashboard ao excluir a organizacao', function (): void {
    ligarDashboardDinamico(true);

    $acme      = tenant('Acme', 'acme');
    $dashboard = CriadorDeDashboardPadrao::para($acme);

    $acme->delete();

    expect($dashboard->refresh()->tenant_id)->toBeNull();
});

/*
 * CT-19 — painel sem tenancy não filtra: /admin enxerga os dashboards dele
 * (globais) e o scope não aperta nada.
 */
it('nao filtra por tenant em painel sem tenancy', function (): void {
    ligarDashboardDinamico(true);

    $acme = tenant('Acme', 'acme');
    CriadorDeDashboardPadrao::para($acme);

    Filament::setCurrentPanel('admin');

    $model = modeloDeDashboard();

    // Sem tenant corrente e sem tenancy no painel, a query não é apertada.
    expect($model::query()->count())->toBeGreaterThanOrEqual(1);
});

/*
 * Arranjo de CT-28/CT-30: a organização tem UM dashboard, restrito a um papel
 * que o usuário não tem. É o estado em que o vendor aborta 403
 * (`DynamicDashboard::initializeCurrentDashboard()`, vendor :123-125).
 *
 * @return array{0: Tenant, 1: \App\Models\User}
 */
function organizacaoComDashboardForaDoAlcance(): array
{
    // Organização criada com a flag DESLIGADA: sem o "Padrão" do observer,
    // o único dashboard é o restrito — senão ele renderizaria no lugar (200).
    ligarDashboardDinamico(false);

    $acme = tenant('Acme', 'acme');

    ligarDashboardDinamico(true);

    // Dashboard restrito ao papel admin_app — o panel_user não o tem. O
    // assignRole precisa do contexto da organização: com teams ligado, o
    // carimbo de team no pivot é o que o `roles()` lê depois no request.
    $dashboard = CriadorDeDashboardPadrao::para($acme);

    noPainelDa($acme);
    $dashboard->assignRole('admin_app');

    $usuario = usuarioComPapel('panel_user', $acme, 'comum@example.com');
    $usuario->tenants()->attach($acme->id);

    return [$acme, $usuario];
}

/*
 * CT-28 — dashboard com roles que excluem o usuário devolve para o clássico.
 *
 * `use_spatie_permissions` ligado faz o model ser `DashboardWithRoles` e o
 * `canDisplay()` filtrar por papel. O vendor abortaria 403; o kit intercepta
 * antes (`DashboardDinamico::atende()`) porque 403 na RAIZ do painel é beco
 * sem saída — ver CT-30.
 */
it('devolve para o classico quando o unico dashboard exclui o papel do usuario', function (): void {
    [$acme, $usuario] = organizacaoComDashboardForaDoAlcance();

    $this->actingAs($usuario);

    $this->get(DashboardDoApp::getUrl(panel: 'app', tenant: $acme))
        ->assertRedirect(DashboardClassico::getUrl(panel: 'app', tenant: $acme));
});

/*
 * CT-30 — e o clássico ATENDE, em vez de devolver para o 403.
 *
 * O par dos dois redirecionamentos é o defeito que este caso mata: enquanto o
 * clássico devolvia por `habilitado()` sozinho, o usuário batia 403 na raiz,
 * ia para a raiz e era devolvido para o mesmo 403. Sem tela de entrada nenhuma.
 */
it('mantem o classico respondendo quando a dinamica nao tem dashboard exibivel', function (): void {
    [$acme, $usuario] = organizacaoComDashboardForaDoAlcance();

    $this->actingAs($usuario);

    $this->get("/app/{$acme->slug}")->assertSuccessful();
});

/*
 * CT-31 — widget de outra organização não é alcançável pelo id.
 *
 * As ações do vendor buscam o widget por id CRU do cliente
 * (`DynamicDashboard.php:916,936,962`), guardadas só por `canEdit()`. Quem
 * administra a Globex tem `Manage:Dashboard` — sem o global scope de
 * `DashboardWidget`, `$wire.mountAction('deleteWidget', {widget: <id da Acme>})`
 * apagava o widget da outra organização.
 */
it('esconde o widget de outra organizacao do find por id', function (): void {
    ligarDashboardDinamico(false);

    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    ligarDashboardDinamico(true);

    $daAcme = CriadorDeDashboardPadrao::para($acme);

    $widget = DashboardWidget::create([
        'dashboard_id' => $daAcme->getKey(),
        'name'         => 'Indicadores da Acme',
        'type'         => 'App\\Filament\\Widgets\\QualquerUm',
        'section_slug' => 'main',
        'x'            => 0,
        'y'            => 0,
        'w'            => 4,
        'h'            => 2,
    ]);

    noPainelDa($globex);

    // O caminho exato das ações do vendor: find pelo id que veio do cliente.
    expect(DashboardWidget::find($widget->getKey()))->toBeNull();

    // E o delete que viria depois é no-op — a linha continua no banco.
    DashboardWidget::find($widget->getKey())?->delete();

    expect(
        DashboardWidget::withoutGlobalScope('tenant')->whereKey($widget->getKey())->exists()
    )->toBeTrue();
});

/*
 * CT-32 — `currentDashboardId` não aceita escrita do cliente.
 *
 * Sem o `#[Locked]`, um `$wire.set('currentDashboardId', <id alheio>)` fazia
 * `persistLayout()` e `createWidget()` gravarem no dashboard de outra
 * organização: o guard `is_locked` do vendor passa porque o dashboard alheio
 * some pelo scope e `?? false` lê "não travado".
 */
it('recusa a escrita do cliente em currentDashboardId', function (): void {
    ligarDashboardDinamico(false);

    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    ligarDashboardDinamico(true);

    $daAcme = CriadorDeDashboardPadrao::para($acme);
    CriadorDeDashboardPadrao::para($globex);

    $gestor = usuarioComPapel('admin_app', $globex, 'gestor@example.com');
    $gestor->tenants()->attach($globex->id);

    noPainelDa($globex);
    $this->actingAs($gestor);

    expect(fn () => Livewire::test(DashboardDoApp::class)
        ->set('currentDashboardId', $daAcme->getKey()))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

/*
 * CT-33 — painel tenant-aware sem tenant resolvido fecha a query.
 *
 * Há janela em que o painel já é o corrente e o `IdentifyTenant` ainda não
 * rodou. Com `if ($tenant)` o filtro simplesmente não era aplicado e a query
 * devolvia os dashboards de TODAS as organizações.
 */
it('fecha a query de dashboards em painel tenant-aware sem tenant', function (): void {
    ligarDashboardDinamico(true);

    $acme = tenant('Acme', 'acme');
    CriadorDeDashboardPadrao::para($acme);

    Filament::setCurrentPanel('app');
    Filament::setTenant(null, isQuiet: true);

    $model = modeloDeDashboard();

    expect($model::query()->count())->toBe(0)
        ->and($model::query()->withoutGlobalScope('tenant')->count())->toBeGreaterThanOrEqual(1);
});

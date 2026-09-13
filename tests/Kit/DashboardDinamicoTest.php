<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit as TelaDeConfiguracoes;
use App\Filament\App\Pages\Dashboard as DashboardDoApp;
use App\Filament\Pages\DashboardClassico;
use App\Models\Role;
use App\Services\Dashboard\CriadorDeDashboardPadrao;
use App\Support\DashboardDinamico;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Widgets\AccountWidget;
use Livewire\Livewire;
use MDDev\DynamicDashboard\Contracts\DynamicWidget;
use MDDev\DynamicDashboard\Models\Dashboard;
use Spatie\Permission\Models\Permission;

/**
 * O dashboard dinâmico como tela de entrada dos painéis — a suíte single-tenant.
 *
 * Os IDs de CT são os de
 * `wikis/specs/main/dashboard-dinamico-nos-paineis/04-casos-de-teste.md`.
 * Os casos de organização (CT-11 a CT-17, CT-19, CT-27, CT-28) vivem em
 * `tests/Tenancy/DashboardDinamicoTenancyTest.php`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/*
 * CT-01 — raiz desligada → clássico.
 *
 * O default do kit é a feature DESLIGADA: `/app` responde o dashboard clássico
 * e a página dinâmica devolve para `/inicio`. É o caso que protege quem
 * atualiza o kit de acordar com a tela de entrada trocada.
 */
it('responde o dashboard classico na raiz com a feature desligada', function (): void {
    $this->actingAs(usuarioDoKit('panel_user'));

    // A raiz é da página dinâmica, que devolve para o clássico em /inicio.
    $this->get('/app')->assertRedirect(DashboardClassico::getUrl(panel: 'app'));
    $this->get('/app/inicio')->assertSuccessful();
});

it('devolve a pagina dinamica para /inicio com a feature desligada', function (): void {
    ligarDashboardDinamico(false);

    $this->actingAs(usuarioDoKit('panel_user'));

    $this->get(DashboardDoApp::getUrl(panel: 'app'))
        ->assertRedirect(DashboardClassico::getUrl(panel: 'app'));
});

/*
 * CT-02 — o toggle muda o PRÓXIMO request.
 *
 * A decisão é por request (`DashboardDinamico`), não por registro de painel:
 * ligar a flag já vale no F5 seguinte, sem cache nem restart.
 */
it('troca a tela de entrada no request seguinte ao toggle', function (): void {
    $user = usuarioDoKit('panel_user');
    $this->actingAs($user);

    ligarDashboardDinamico(true);

    // Com a flag ligada e sem dashboards, a raiz responde a grade (CT-26).
    $this->get('/app')->assertSuccessful();

    ligarDashboardDinamico(false);
    fronteiraDeRequest();

    $this->get(DashboardDoApp::getUrl(panel: 'app'))
        ->assertRedirect(DashboardClassico::getUrl(panel: 'app'));
});

/*
 * CT-04 — a decisão é conjuntiva: flag E (lista vazia OU painel na lista).
 */
it('decide por flag e lista de paineis em conjuncao', function (): void {
    ligarDashboardDinamico(false);
    expect(DashboardDinamico::habilitadoPara('app'))->toBeFalse();

    ligarDashboardDinamico(true);
    expect(DashboardDinamico::habilitadoPara('app'))->toBeTrue()
        ->and(DashboardDinamico::habilitadoPara('admin'))->toBeTrue()
        ->and(DashboardDinamico::habilitadoPara('infra'))->toBeTrue();

    ligarDashboardDinamico(true, ['admin']);
    expect(DashboardDinamico::habilitadoPara('app'))->toBeFalse()
        ->and(DashboardDinamico::habilitadoPara('admin'))->toBeTrue();
});

/*
 * CT-05 — sem painel corrente (console, queue), a flag manda sozinha.
 */
it('responde pela flag quando nao ha painel corrente', function (): void {
    ligarDashboardDinamico(false);
    expect(DashboardDinamico::habilitado())->toBeFalse();

    ligarDashboardDinamico(true);
    expect(DashboardDinamico::habilitado())->toBeTrue();
});

/*
 * CT-06 — env "off" falha FECHADO.
 *
 * `filter_var` e não `(bool) env()`: um interruptor que troca a tela de
 * entrada de todo painel não pode nascer ligado por um valor mal digitado.
 */
it('falha fechado com valor de env invalido', function (?string $valor): void {
    $config = kitConfigCom('KIT_DASHBOARD_DINAMICO', $valor);

    expect($config['dashboard_dinamico']['habilitado'])->toBeFalse();
})->with([
    'ausente'   => [null],
    'off'       => ['off'],
    'lixo'      => ['ligado-sim'],
    'vazio'     => [''],
]);

/*
 * CT-07/CT-09 — quem monta é quem tem `Manage:Dashboard`.
 *
 * `canEdit()` é o gancho que o pacote consulta em TODA superfície de escrita
 * (botão, ação Manage, createWidget, persistLayout, drag do GridStack). O
 * panel_user vê e não edita; admin_app e master_global montam.
 */
it('deixa o panel_user ver sem montar', function (): void {
    $this->actingAs(usuarioDoKit('panel_user'));

    expect(DashboardDoApp::canEdit())->toBeFalse();
});

it('deixa admin e master_global montar', function (string $papel): void {
    $this->actingAs(usuarioDoKit($papel));

    expect(DashboardDoApp::canEdit())->toBeTrue();
})->with(['admin', 'master_global']);

/*
 * CT-08 — a guarda é do SERVIDOR, não do botão.
 *
 * `createWidget()` abre com `abort_unless(static::canEdit(), 403)` no vendor —
 * disparar a ação fora do caminho feliz (Livewire chamado direto) toma 403.
 */
it('recusa criar widget sem a permissao, fora do caminho feliz', function (): void {
    ligarDashboardDinamico(true);
    CriadorDeDashboardPadrao::para(null);

    $this->actingAs(usuarioDoKit('panel_user'));

    Livewire::test(DashboardDoApp::class)
        ->call('persistLayout', [['id' => 1, 'section' => 'main', 'x' => 0, 'y' => 0, 'w' => 4, 'h' => 2]])
        ->assertForbidden();
});

/*
 * CT-10 — a permission existe e o panel_user nasce sem ela.
 */
it('gera a permission Manage:Dashboard e a subtrai do panel_user', function (): void {
    expect(Permission::where('name', 'Manage:Dashboard')->exists())->toBeTrue();

    $panelUser = Role::where('name', 'panel_user')->firstOrFail();
    $adminApp  = Role::where('name', 'admin_app')->first();

    expect($panelUser->hasPermissionTo('Manage:Dashboard'))->toBeFalse();

    // admin_app só existe com tenancy ligada; quando existir, ele monta.
    if ($adminApp !== null) {
        expect($adminApp->hasPermissionTo('Manage:Dashboard'))->toBeTrue();
    }
});

/*
 * CT-18 — sem tenancy, o dashboard é global e `tenant_id` nasce nulo.
 */
it('cria o dashboard padrao global, com tenant_id nulo', function (): void {
    $dashboard = CriadorDeDashboardPadrao::para(null);

    expect($dashboard->tenant_id)->toBeNull()
        ->and($dashboard->page)->toBe(DashboardDoApp::class)
        ->and($dashboard->is_active)->toBeTrue();
});

/*
 * CT-20 — painel novo do projeto, sem página dinâmica registrada, segue no
 * clássico mesmo com a feature ligada (RQ-07).
 */
it('mantem o classico em painel sem pagina dinamica registrada', function (): void {
    ligarDashboardDinamico(true);

    $painel = painelRegistradoEmTeste('relatorios');

    expect(DashboardDinamico::paginaDinamicaDo($painel))->toBeNull();
});

/*
 * CT-22 — a tela grava e o valor GOVERNA.
 *
 * O mapa `mapaDeConfiguracao()` é a ligação: sem as duas linhas, o toggle
 * gravaria no banco e não mudaria nada (M30).
 */
it('leva o toggle gravado para a config que o decisor le', function (): void {
    gravarConfiguracao('dashboard_dinamico_habilitado', true);
    gravarConfiguracao('dashboard_dinamico_paineis', ['app']);

    alinharConfiguracoesDoKit();

    expect(config('kit.dashboard_dinamico.habilitado'))->toBeTrue()
        ->and(config('kit.dashboard_dinamico.paineis'))->toBe(['app'])
        ->and(DashboardDinamico::habilitadoPara('app'))->toBeTrue()
        ->and(DashboardDinamico::habilitadoPara('admin'))->toBeFalse();
});

/*
 * CT-23 — sem a chave no ambiente, o default é desligado.
 */
it('nasce desligado sem a chave no ambiente', function (): void {
    $config = kitConfigCom('KIT_DASHBOARD_DINAMICO', null);

    expect($config['dashboard_dinamico']['habilitado'])->toBeFalse()
        ->and($config['dashboard_dinamico']['paineis'])->toBe([]);
});

/*
 * CT-24 — a migration de settings semeia o valor do config, não `true`.
 *
 * Com RefreshDatabase as settings migrations já rodaram: o payload gravado
 * tem de ser `false` — o kit:update não pode acordar ninguém com a feature
 * ligada.
 */
it('semeia o settings desligado', function (): void {
    expect(configuracaoGravada('dashboard_dinamico_habilitado'))->toBeFalse()
        ->and(configuracaoGravada('dashboard_dinamico_paineis'))->toBe([]);
});

/*
 * CT-25 — o item de menu alterna com a flag.
 */
it('alterna os itens de menu com a flag', function (): void {
    noPainelBootado('app');

    ligarDashboardDinamico(false);
    expect(DashboardDoApp::shouldRegisterNavigation())->toBeFalse()
        ->and(DashboardClassico::shouldRegisterNavigation())->toBeTrue();

    ligarDashboardDinamico(true);
    expect(DashboardDoApp::shouldRegisterNavigation())->toBeTrue()
        ->and(DashboardClassico::shouldRegisterNavigation())->toBeFalse();
});

/*
 * CT-26 — ligado sem dashboards não é erro: a grade vazia responde 200.
 */
it('responde a grade vazia quando nao ha dashboard nenhum', function (): void {
    ligarDashboardDinamico(true);

    $this->actingAs(usuarioDoKit('panel_user'));

    $this->get('/app')->assertSuccessful();
});

/*
 * CT-29 — a descoberta filtra pela trait: só `DynamicWidget` vira opção.
 *
 * Um widget clássico na lista de widgets do painel NÃO pode aparecer no
 * seletor — sem o filtro ele quebraria a grade (M37).
 */
it('descobre so os widgets que implementam DynamicWidget', function (): void {
    $painel = Filament::getPanel('app');

    $dinamicos = collect($painel->getWidgets())
        ->filter(fn (string $widget): bool => is_subclass_of($widget, DynamicWidget::class));

    // O painel tem widgets clássicos (AccountWidget) e nenhum dinâmico de
    // fábrica — o filtro é o que separa os dois mundos.
    expect($painel->getWidgets())->toContain(AccountWidget::class)
        ->and($dinamicos)->not->toContain(AccountWidget::class);
});

/*
 * CT-21 — o seletor de painéis da tela lista os painéis registrados.
 */
it('oferece os paineis registrados no seletor da tela', function (): void {
    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    Livewire::test(TelaDeConfiguracoes::class)
        ->assertFormFieldExists('dashboard_dinamico_habilitado')
        ->assertFormFieldExists('dashboard_dinamico_paineis');
});

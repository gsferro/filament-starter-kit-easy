<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use MDDev\DynamicDashboard\DashboardModelHelper;
use MDDev\DynamicDashboard\Models\DashboardWidget;
use Tests\Browser\Fixtures\WidgetDeTeste;

/**
 * A grade GridStack em navegador real — o que só o JS executado prova.
 *
 * CT-B01 e CT-B02 de
 * `wikis/specs/main/dashboard-dinamico-nos-paineis/05-casos-de-teste-browser.md`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    ligarDashboardDinamico(true);

    /*
     * O fixture precisa estar nos widgets do painel: a página só projeta na
     * grade o que `discoverDynamicWidgets()` encontra em `panel->getWidgets()`
     * (`isWidgetAvailableForDashboard`). Sem esta linha a grade renderiza
     * vazia — o widget existe no banco e é filtrado fora da tela.
     */
    Filament::getPanel('app')->widgets([WidgetDeTeste::class]);
});

/**
 * Um dashboard global com dois widgets do fixture — o mínimo para o drag ter
 * para onde ir.
 */
function gradeDeTeste(): array
{
    /*
     * A migration do pacote semeia um "Default Dashboard" (ordering 0,
     * page=null — exibível em qualquer página). É ele que a tela abre, então
     * os widgets do teste vão nele — criar outro deixaria a grade vazia.
     */
    $dashboard = DashboardModelHelper::model()::query()->orderBy('ordering')->firstOrFail();

    $primeiro = DashboardWidget::create([
        'dashboard_id'  => $dashboard->getKey(),
        'name'          => 'Primeiro',
        'type'          => WidgetDeTeste::class,
        'section_slug'  => 'main',
        'x'             => 0,
        'y'             => 0,
        'w'             => 4,
        'h'             => 2,
        'display_title' => true,
        'settings'      => [],
    ]);

    $segundo = DashboardWidget::create([
        'dashboard_id'  => $dashboard->getKey(),
        'name'          => 'Segundo',
        'type'          => WidgetDeTeste::class,
        'section_slug'  => 'main',
        'x'             => 4,
        'y'             => 0,
        'w'             => 4,
        'h'             => 2,
        'display_title' => true,
        'settings'      => [],
    ]);

    return [$dashboard, $primeiro, $segundo];
}

/*
 * CT-B01 — arrastar um widget persiste a nova posição.
 *
 * O gesto é GridStack + Livewire: o teste de componente prova o endpoint, não
 * prova que o drag chega nele. A âncora é o banco (`dashboard_widgets.x/y`),
 * não o pixel.
 */
it('persiste a posicao do widget arrastado', function (): void {
    [, $primeiro, $segundo] = gradeDeTeste();

    $this->actingAs(usuarioDoKit('master_global'));

    $pagina = visit('/app')
        ->assertVisible('.grid-stack-item >> nth=0')
        ->assertVisible('.grid-stack-item >> nth=0 >> .dd-drag-handle');

    /*
     * O movimento entra pela API do GridStack (`grid.update`), não pelo gesto
     * de mouse: o DD do pacote exige `setPointerCapture` com um pointerId real,
     * que nem o `drag()` do Playwright nem eventos sintéticos fornecem — medido
     * aqui, os dois deixam `gs-x` intacto. O que o CT-B01 precisa provar é a
     * fiação `change` → `scheduleFlush` → `$wire.call('persistLayout')` → banco,
     * e `update()` dispara o MESMO evento `change` que o dragstop dispara.
     */
    $pagina->script(<<<'JS'
        (() => new Promise((resolve) => {
            /*
             * O handle é HTML server-side: o assertVisible acima passa ANTES do
             * Alpine bootar (`bootGrids` vai no $nextTick do alpine:init), e sem
             * a instância `el.gridstack` o update morre em silêncio — era a
             * intermitência medida aqui. O evaluate do Playwright aguarda a
             * Promise, então isto É a espera pela grade estar pronta.
             */
            const mover = () => {
                const item = document.querySelector('.grid-stack-item');
                const grid = item?.closest('.grid-stack')?.gridstack;

                if (! grid) {
                    setTimeout(mover, 50);
                    return;
                }

                grid.update(item, { x: 6, y: 3 });

                /*
                 * A espera pelo write acontece AQUI, dentro do navegador, e não
                 * num `usleep` do PHP: o servidor do `pest-plugin-browser` é
                 * in-process, então enquanto o teste dorme em PHP ninguém atende
                 * o `$wire.call('persistLayout')` que o `change` disparou —
                 * medido: com a espera no PHP o dado nunca chega, com ela aqui
                 * chega em ~1s. O debounce do pacote é de 150 ms; 1,5 s dá folga
                 * para o round-trip do Livewire.
                 */
                setTimeout(() => resolve(true), 1500);
            };

            mover();
        }))()
    JS);

    /*
     * O drop dispara `change` → scheduleFlush (debounce de 150 ms) →
     * `$wire.call('persistLayout')` — escrita ASSÍNCRONA, já aguardada dentro
     * do navegador acima. A âncora é o banco, não o pixel.
     */
    $gravou = [$primeiro->refresh()->x, $primeiro->y] !== [0, 0];

    $segundo->refresh();

    expect($gravou)->toBeTrue();

    visit('/app')->assertVisible('.grid-stack-item >> nth=0')->assertNoJavaScriptErrors();

    expect($primeiro->refresh()->x)->not->toBe(0);
});

/*
 * CT-B02 — sem `Manage:Dashboard`, a grade não é arrastável.
 *
 * O mutante que este caso mata: `canEdit()` escondendo o botão mas deixando o
 * drag ativo (MB3). A prova é o gesto NÃO persistir — o banco continua igual.
 */
it('nao deixa a grade ser arrastada sem a permissao', function (): void {
    [, $primeiro] = gradeDeTeste();

    $this->actingAs(usuarioDoKit('panel_user'));

    visit('/app')
        ->assertVisible('.grid-stack-item >> nth=0')
        /*
         * Sem `Manage:Dashboard` o handle nem é renderizado (o `@if ($canEdit)`
         * do widget-wrapper do pacote) e o grid nasce `staticGrid` — a grade é
         * read-only de verdade, não só sem botão.
         */
        ->assertMissing('.dd-drag-handle')
        ->drag('.grid-stack-item >> nth=0', '.grid-stack-item >> nth=1')
        ->assertNoJavaScriptErrors();

    expect([$primeiro->refresh()->x, $primeiro->y])->toBe([0, 0]);
});

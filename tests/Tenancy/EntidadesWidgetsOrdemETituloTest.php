<?php

use App\Filament\Admin\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Admin\Resources\Tenants\Pages\ListTenants;
use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Filament\Admin\Resources\Tenants\Widgets\AcessosPorPainel;
use App\Filament\Admin\Resources\Tenants\Widgets\AtualizacoesDasOrganizacoes;
use App\Filament\Admin\Resources\Tenants\Widgets\OrganizacoesStats;
use App\Filament\Admin\Resources\Tenants\Widgets\UsuariosUnicosPorOrganizacao;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Widgets\Widget;
use Livewire\Livewire;

/**
 * Posição dos widgets e caixa do rótulo na listagem de entidades (organizações), no `/admin`.
 *
 * Vivem em `tests/Tenancy` porque `TenantResource::canAccess()` exige `kit.tenancy.enabled`, que
 * só é `true` em `Tests\TenancyTestCase` — em `tests/Kit` todo cenário mediria o kill-switch.
 *
 * Ver `wikis/specs/feat/entidades-widgets-ordem-e-titulo/`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/*
|--------------------------------------------------------------------------
| R1 — acima da tabela há um único widget, a visão geral
| R2 — os três widgets de detalhe ficam abaixo da tabela
|--------------------------------------------------------------------------
*/

/**
 * CT-01 — a ordem dos cinco blocos no HTML da página.
 *
 * O `[CT-12]` de `InsightsDasOrganizacoesTest` afirma as duas LISTAS declaradas; este caso afirma
 * o que o Filament de fato RENDERIZA, e por isso mata o método declarado com nome errado
 * (`getFooterWidget()`), que a leitura por closure ainda encontraria.
 */
it('[CT-01] renderiza visão geral, tabela e os três widgets de detalhe, nesta ordem', function (): void {
    $admin = usuarioComPapel('admin', null, 'adm@example.com');
    $this->actingAs($admin);
    noPainelBootado('admin');

    /**
     * O marcador de um widget no HTML inicial.
     *
     * Os widgets do Filament são lazy (`CanBeLazy::$isLazy = true`): no primeiro render cada um é
     * um placeholder do Livewire, sem heading — texto de heading não serve de marcador. O que
     * existe em todos é o nome do componente dentro do `wire:snapshot` da raiz, e no Livewire 4 o
     * nome é o próprio FQCN. Como o snapshot é JSON, a contrabarra chega ao HTML dobrada.
     */
    $marcador = static fn (string $widget): string => str_replace('\\', '\\\\', $widget);

    Livewire::test(ListTenants::class)->assertSeeHtmlInOrder([
        $marcador(OrganizacoesStats::class),
        'fi-ta-ctn',
        $marcador(UsuariosUnicosPorOrganizacao::class),
        $marcador(AcessosPorPainel::class),
        $marcador(AtualizacoesDasOrganizacoes::class),
    ]);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R3 — o título da listagem é o rótulo plural como configurado
| R5 — o rótulo de navegação continua sendo o configurado
|--------------------------------------------------------------------------
*/

/**
 * CT-02 — título da aba e menu preservam a caixa do rótulo configurado.
 *
 * "Unidades de Negócio" é a linha discriminante: ela separa "como configurado" de `ucwords`
 * ("Unidades De Negócio") e de `ucfirst(strtolower())` ("Unidades de negócio").
 *
 * O `<h1>` do cabeçalho sai do mesmo `getTitle()` que a aba, então uma asserção basta.
 */
it('[CT-02] título e menu preservam a caixa do rótulo configurado', function (string $rotulo): void {
    $admin = usuarioComPapel('admin', null, 'adm@example.com');
    config()->set('kit.tenancy.label_plural', $rotulo);

    $this->actingAs($admin)
        ->get('/admin/organizacoes')
        ->assertOk()
        ->assertSeeInOrder(['<title>', $rotulo, '</title>'], escape: false);

    expect(TenantResource::getNavigationLabel())->toBe($rotulo);
})->with([
    'uma palavra capitalizada'            => ['Entidades'],
    'preposição minúscula entre palavras' => ['Unidades de Negócio'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R4 — o primeiro item do breadcrumb é o rótulo plural como configurado
|--------------------------------------------------------------------------
*/

/**
 * CT-03 — o breadcrumb preserva a caixa do rótulo, em toda página do Resource.
 *
 * A partição é a PÁGINA: uma correção feita só no título da listagem deixaria o breadcrumb das
 * demais páginas errado.
 */
it('[CT-03] o breadcrumb preserva a caixa do rótulo configurado', function (string $pagina): void {
    $admin = usuarioComPapel('admin', null, 'adm@example.com');
    $this->actingAs($admin);
    config()->set('kit.tenancy.label_plural', 'Unidades de Negócio');
    noPainelBootado('admin');

    $breadcrumbs = Livewire::test($pagina)->instance()->getBreadcrumbs();

    expect(reset($breadcrumbs))->toBe('Unidades de Negócio');
})->with([
    'listagem' => [ListTenants::class],
    'criação'  => [CreateTenant::class],
])->group('kit');

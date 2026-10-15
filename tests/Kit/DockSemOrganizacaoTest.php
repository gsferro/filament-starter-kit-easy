<?php

use App\Filament\Pages\DashboardClassico;
use App\Support\Paineis;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;

it('[CT-08] sem tenancy o grupo usa só o painel e o tooltip usa o caminho', function (): void {
    config(['kit.hub' => true]);
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    $ana = usuarioDoKit('panel_user', 'ana@example.com');
    concederDock('panel_user');
    ligarDock(['app']);
    $chave = chaveDeAtalho('app', DashboardClassico::class);
    gravarAtalhos($ana, [$chave]);
    $this->actingAs($ana)->get('/app')->assertOk();
    Filament::setCurrentPanel('app');

    $grupos = dockNaPagina('app')->instance()->getDockGroups();

    $encontrado = false;
    foreach ($grupos as $grupo) {
        expect($grupo['label'])->not->toContain(' · ');
        if (isset($grupo['actions']['atalho_0'])) {
            $encontrado = true;
            expect($grupo['label'])->toBe(Paineis::rotulo(Filament::getPanel('app')));
            expect($grupo['actions']['atalho_0']->getTooltip())->toBe(parse_url(DashboardClassico::getUrl(panel: 'app'), PHP_URL_PATH));
        }
    }
    expect($encontrado)->toBeTrue();
});

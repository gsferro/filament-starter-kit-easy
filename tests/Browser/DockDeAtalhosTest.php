<?php

use App\Filament\Admin\Resources\Users\UserResource;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

it('[CT-B01] bruno abre o dock e chega à tela do favorito', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $bruno = usuarioDoKit('admin', 'bruno@example.com');
    concederDock('admin');
    ligarDock(['admin']);
    gravarAtalhos($bruno, [chaveDeAtalho('admin', UserResource::class)]);

    $this->actingAs($bruno);
    $this->get('/admin')->assertOk();

    // vendor/hoceineel/filament-quick-action-dock/resources/views/dock.blade.php:x-ref="trigger":171; onTriggerClick:182.
    visit('/admin')
        ->click('[data-layout] button[x-ref="trigger"]')
        ->assertVisible('[data-layout] a[href$="/admin/users"]')
        ->click('[data-layout] a[href$="/admin/users"]')
        ->assertPathIs('/admin/users')
        ->assertNoJavaScriptErrors();
})->group('browser');

it('[CT-B02] a superfície e o favorito do dock acompanham o tema efetivo do painel', function (string $tema): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $bruno = usuarioDoKit('admin', 'bruno@example.com');
    concederDock('admin');
    ligarDock(['admin']);
    gravarAtalhos($bruno, [chaveDeAtalho('admin', UserResource::class)]);

    $this->actingAs($bruno);
    $this->get('/admin')->assertOk();

    $pagina = $tema === 'navegador-escuro'
        ? visit('/admin')->inDarkMode()
        : visit('/admin')->inLightMode();

    if ($tema === 'painel-escuro') {
        // vendor/filament/filament/resources/views/components/user-menu.blade.php:fi-user-menu-trigger:58; theme-switcher:124.
        // vendor/filament/filament/resources/views/components/theme-switcher/button.blade.php:x-on:click:15.
        $pagina->click('.fi-user-menu-trigger')
            ->click('[aria-label="Mudar para tema escuro"]');
    }

    $medidor = <<<'JS'
        (elemento, raiz) => {
            if (!elemento || !raiz || !raiz.contains(elemento)) return null;
            const canvas = document.createElement('canvas');
            canvas.width = canvas.height = 1;
            const contexto = canvas.getContext('2d', { willReadFrequently: true });
            for (let el = elemento; el; el = el.parentElement) {
                const cor = getComputedStyle(el).backgroundColor;
                contexto.clearRect(0, 0, 1, 1);
                contexto.fillStyle = cor;
                contexto.fillRect(0, 0, 1, 1);
                const [r, g, b, alfa] = contexto.getImageData(0, 0, 1, 1).data;
                if (alfa > 0) {
                    const canais = [r, g, b].map(valor => {
                        const canal = valor / 255;
                        return canal <= 0.04045 ? canal / 12.92 : ((canal + 0.055) / 1.055) ** 2.4;
                    });
                    return { cor, luminancia: 0.2126 * canais[0] + 0.7152 * canais[1] + 0.0722 * canais[2] };
                }
                if (el === raiz) break;
            }
            return null;
        }
        JS;

    $comparacao = $tema === 'navegador-claro' ? '>= 0.5' : '<= 0.2';
    $pagina->assertScript("(() => { const fundo = ({$medidor})(document.body, document.body); return fundo !== null && fundo.luminancia {$comparacao}; })()");
    $fundo = json_decode((string) $pagina->script("JSON.stringify(({$medidor})(document.body, document.body))"), true, flags: JSON_THROW_ON_ERROR);

    // No speed dial, menu e raiz são transparentes; a superfície pintada é o chip do favorito.
    // vendor/hoceineel/filament-quick-action-dock/resources/views/dock.blade.php:qad-label:116; qad-item:113; qad-root:61.
    // public/css/hoceineel/filament-quick-action-dock/quick-action-dock.css:.qad-label background var(--qad-chip-bg):1; .qad-item .fi-icon-btn background var(--qad-item-bg):1.
    $pagina->click('[data-layout] button[x-ref="trigger"]')
        ->assertVisible('[data-layout][data-open="true"] .qad-item:has(a[href$="/admin/users"]) .qad-label')
        ->assertVisible('[data-layout][data-open="true"] a[href$="/admin/users"]');

    $medida = json_decode((string) $pagina->script(<<<JS
        (() => {
            const raiz = document.querySelector('[data-layout][data-open="true"]');
            const item = raiz?.querySelector('a[href$="/admin/users"]');
            const superficie = item?.closest('.qad-item')?.querySelector('.qad-label');
            const medir = {$medidor};
            return JSON.stringify({ superficie: medir(superficie, raiz), item: medir(item, raiz) });
        })()
        JS), true, flags: JSON_THROW_ON_ERROR);

    fwrite(STDOUT, "\nCT-B02 {$tema}: ".json_encode(['body' => $fundo, ...$medida], JSON_THROW_ON_ERROR)."\n");
    $pagina->screenshot(filename: "dock-tema-{$tema}")
        ->assertNoJavaScriptErrors();

    foreach (['superficie', 'item'] as $elemento) {
        expect($medida[$elemento])->not->toBeNull();

        if ($tema === 'navegador-claro') {
            expect($medida[$elemento]['luminancia'])->toBeGreaterThanOrEqual(0.5);
        } else {
            expect($medida[$elemento]['luminancia'])->toBeLessThanOrEqual(0.2);
        }
    }
})->with([
    'linha 1: escuro pelo navegador'                        => ['navegador-escuro'],
    'linha 2: claro (controle positivo)'                    => ['navegador-claro'],
    'linha 3: navegador claro e escuro escolhido no painel' => ['painel-escuro'],
])->group('browser');

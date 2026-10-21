<?php

use App\Support\DockDeAtalhos;
use Filament\Support\Facades\FilamentAsset;

/*
 * A cópia corrigida do bundle do dock (`public/js/kit/quick-action-dock.js`) e a troca do asset do
 * pacote por ela. Wiki: `wikis/specs/fix/dock-altura-do-viewport/`.
 */

it('[CT-01] a cópia é o dist instalado do pacote com uma única substituição', function (): void {
    $dist     = base_path('vendor/hoceineel/filament-quick-action-dock/dist/quick-action-dock.js');
    $original = (string) file_get_contents($dist);

    expect(substr_count($original, DockDeAtalhos::TRECHO_DO_PACOTE))
        ->toBe(1, 'O dist do pacote mudou: o trecho medido pelo innerHeight não aparece exatamente uma vez. Confira se a nova versão já mede pela área visível (aí a cópia e DockDeAtalhos::servirScriptCorrigido() saem) ou atualize TRECHO_DO_PACOTE.');

    expect((string) file_get_contents(public_path(DockDeAtalhos::SCRIPT_CORRIGIDO)))
        ->toBe(
            str_replace(DockDeAtalhos::TRECHO_DO_PACOTE, DockDeAtalhos::TRECHO_CORRIGIDO, $original),
            'A cópia corrigida está defasada do dist instalado. Regenere: php -r "file_put_contents(\'public/js/kit/quick-action-dock.js\', str_replace(\'height:window.innerHeight}\', \'height:document.documentElement.clientHeight||window.innerHeight}\', file_get_contents(\'vendor/hoceineel/filament-quick-action-dock/dist/quick-action-dock.js\')));"',
        );
});

it('[CT-02] o asset do pacote é servido pela cópia, uma vez só', function (): void {
    $scripts = collect(FilamentAsset::getScripts([DockDeAtalhos::PACOTE]))
        ->filter(fn ($script): bool => $script->getId() === 'quick-action-dock');

    expect($scripts)->toHaveCount(1);

    $html = $scripts->first()->getHtml()->toHtml();

    expect($html)->toContain(DockDeAtalhos::SCRIPT_CORRIGIDO.'?v='.substr((string) md5_file(public_path(DockDeAtalhos::SCRIPT_CORRIGIDO)), 0, 8))
        ->and($html)->not->toContain('hoceineel/filament-quick-action-dock/quick-action-dock.js');
});

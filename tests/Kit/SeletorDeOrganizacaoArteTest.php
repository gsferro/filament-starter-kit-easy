<?php

use App\Console\Commands\KitArte;

/**
 * A arte do interruptor "ocultar o seletor": clipe e imagem declarados têm quem os produza.
 *
 * ID de CT em `wikis/specs/main/ocultar-seletor-de-organizacao-unica/04-casos-de-teste.md`.
 *
 * É a mesma varredura de `KitArteTest` (CT-50), fechada no que esta feature declara:
 * quadro em `CLIPES`/`IMAGENS` sem `filename:` correspondente em `CapturaDeArteTest`
 * nasceria com GIF montado de quadro faltante ou thumb ausente — e nada acusaria.
 */
it('[CT-11] cada quadro declarado do clipe nasce de uma captura', function (): void {
    $clipes  = (new ReflectionClassConstant(KitArte::class, 'CLIPES'))->getValue();
    $imagens = (new ReflectionClassConstant(KitArte::class, 'IMAGENS'))->getValue();

    expect($clipes['seletor-organizacao'] ?? null)->toBe(
        ['seletor-organizacao-1-visivel', 'seletor-organizacao-2-oculto'],
        'o clipe seletor-organizacao não declara os dois quadros visivel/oculto',
    );

    expect(in_array('admin-configuracoes-seletor', $imagens, true))->toBeTrue(
        'o quadro da tela de configurações não está publicado em KitArte::IMAGENS',
    );

    $capturas = file_get_contents(base_path('tests/BrowserTenancy/CapturaDeArteTest.php'));

    foreach (['seletor-organizacao-1-visivel', 'seletor-organizacao-2-oculto', 'admin-configuracoes-seletor'] as $quadro) {
        expect(str_contains($capturas, "filename: '{$quadro}'"))->toBeTrue(
            "o quadro '{$quadro}' não tem cenário que o capture em CapturaDeArteTest",
        );
    }
});

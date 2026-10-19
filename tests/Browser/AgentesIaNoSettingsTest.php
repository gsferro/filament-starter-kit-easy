<?php

/**
 * CT-B01 da feature `agentes-ia-no-settings`: o chat do Assistente num painel que nunca o teve
 * (`/admin`, `/infra`) entra na pilha de botões flutuantes acima do dock. Helpers e a medição em
 * JS (`MEDIR_PILHA_FLUTUANTE`) vivem em `tests/Pest.php`, compartilhados com
 * `PilhaDeBotoesFlutuantesTest.php`.
 */
it('[CT-B01] o chat aparece na pilha acima do dock, alinhado e sem sobrepor', function (string $painel): void {
    $pagina = abrirPainelDaPilha($painel, chatEm: ['admin', 'infra']);
    $pagina->assertVisible('[data-assistente-chat] button[aria-label="Abrir assistente"]');

    // O contrato da pilha vale com o dock no canto inferior direito e a página rolada (como no
    // CT-B01 da pilha); os dois helpers vivem em tests/Pest.php.
    moverDockDaPilha($pagina);
    rolarPaginaDaPilha($pagina);
    $pagina->assertScript('() => { const m = '.MEDIR_PILHA_FLUTUANTE.'; return m.chat && m.chat.visivel && Math.abs(m.dock.top-m.chat.bottom-16)<=1; }');

    $m = json_decode($pagina->script('JSON.stringify('.MEDIR_PILHA_FLUTUANTE.')'), true, flags: JSON_THROW_ON_ERROR);

    expect($m['chat'])->not->toBeNull();
    expect($m['chat']['visivel'])->toBeTrue();
    expect($m['chat']['width'])->toBeGreaterThan(0);
    expect($m['chat']['height'])->toBeGreaterThan(0);
    expect(abs($m['dock']['top'] - $m['chat']['bottom'] - 16))->toBeLessThanOrEqual(1);
    expect(abs($m['chat']['centroX'] - $m['gatilho']['centroX']))->toBeLessThanOrEqual(1);
    foreach (['dock', 'voltar'] as $outro) {
        $a = $m['chat'];
        $b = $m[$outro];
        expect($a['left'] < $b['right'] && $b['left'] < $a['right'] && $a['top'] < $b['bottom'] && $b['top'] < $a['bottom'])
            ->toBeFalse("O chat intercepta o retangulo de {$outro}");
    }
    $pagina->assertNoJavaScriptErrors();
})->with([
    'administradora /admin' => ['/admin'],
    'iris /infra'           => ['/infra'],
]);

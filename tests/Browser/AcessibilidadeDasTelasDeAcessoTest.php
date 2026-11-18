<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * Os débitos de verificação H da aparência das telas de acesso (wiki
 * `feat/debitos-da-aparencia-das-telas`, RQ-02..RQ-04; débito do `06` de
 * `feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel`).
 *
 * O teclado no painel de marca já é o CT-B03 daquela wiki; aqui ficam o axe nas três telas, nos dois
 * layouts e nos dois temas, o teclado no clássico, e "reduzir movimento" na transição entre as telas.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    config(['kit.registro.habilitado' => true]);

    // Paga a compilação das telas fora do cronômetro do Playwright.
    $this->get('/admin/login');
    $this->get('/app/register');
    $this->get('/admin/password-reset/request');
});

it('[CT-01] as telas de acesso passam no axe, no clássico e no painel de marca, nos dois temas', function (string $layout, string $url, string $tema): void {
    definirAparenciaGlobal($layout === 'painel' ? aparenciaDeTeste('P') : null);

    $pagina = $tema === 'escuro' ? visit($url)->inDarkMode() : visit($url)->inLightMode();

    $pagina->resize(1440, 900)
        ->assertPathIs($url)
        ->assertScript("document.documentElement.classList.contains('dark') === ".($tema === 'escuro' ? 'true' : 'false'))
        ->assertNoAccessibilityIssues();
})->with([
    'clássico'         => 'classico',
    'painel de marca'  => 'painel',
])->with([
    'login'            => '/admin/login',
    'cadastro'         => '/app/register',
    'recuperar senha'  => '/admin/password-reset/request',
])->with([
    'claro'  => 'claro',
    'escuro' => 'escuro',
]);

it('[CT-02] no clássico, a pessoa chega do e-mail ao botão de entrar só com o teclado', function (): void {
    definirAparenciaGlobal(null);

    $pagina = visit('/admin/login')->resize(1440, 900)->assertPathIs('/admin/login');

    $pagina->click('#form\\.email');

    $alcancados = [];
    for ($i = 0; $i < 8; $i++) {
        $pagina->script("document.querySelectorAll('[data-kit-foco]').forEach(e => e.removeAttribute('data-kit-foco')); document.activeElement?.setAttribute('data-kit-foco', '1')");
        $pagina->keys('[data-kit-foco="1"]', 'Tab');
        $alcancados[] = (string) $pagina->script("(() => { const e = document.activeElement; return (e.id || '') + '|' + (e.getAttribute('type') || '') + '|' + (e.tagName || ''); })()");
    }

    $texto = implode("\n", $alcancados);

    expect($texto)->toContain('form.password')
        ->and(preg_match('~\|submit\|BUTTON~', $texto))->toBe(1);
});

it('[CT-03] com "reduzir movimento" no sistema, a troca entre as telas não anima mesmo com a transição ligada', function (): void {
    definirAparenciaGlobal([
        ...aparenciaDeTeste('P'), 'lado_do_formulario' => 'direita', 'transicao_entre_telas' => true,
        'telas'                                        => ['recuperacao' => [...aparenciaDeTeste('P'), 'lado_do_formulario' => 'esquerda']],
    ]);

    $pagina = visitaComMovimentoReduzido('/admin/login')->resize(1440, 900)->assertPresent('.kit-painel-de-marca');

    $pagina->assertScript("matchMedia('(prefers-reduced-motion: reduce)').matches");

    expect((string) $pagina->script("getComputedStyle(document.querySelector('.fi-auth-media-wrapper')).viewTransitionName"))->toBe('none');

    $pagina->script("window.addEventListener('pageswap', (e) => sessionStorage.setItem('kit-transicao', e.viewTransition ? 'sim' : 'nao'))");

    $pagina->click('Esqueceu sua senha?')
        ->assertPathIs('/admin/password-reset/request');

    expect($pagina->script("sessionStorage.getItem('kit-transicao')"))->toBe('nao');
});

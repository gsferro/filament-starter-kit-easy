<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * Os débitos de verificação H da aparência das telas de acesso (wiki
 * `feat/debitos-da-aparencia-das-telas`, RQ-02..RQ-04; débito do `06` de
 * `feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel`). Os IDs seguem a numeração de
 * CT-B daquela wiki.
 *
 * O teclado no painel de marca já é o CT-B03 de lá; aqui ficam o axe nas três telas, nos dois
 * layouts e nos dois temas, o teclado no clássico, e "reduzir movimento" na transição entre as telas.
 *
 * Cada caso aquece pelo kernel só a tela que visita: um GET de outro painel no mesmo processo deixa
 * aquele painel como o corrente, e a tela seguinte do `/app` sairia sem a arte e sem o alternador.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    config(['kit.registro.habilitado' => true]);
});

it('[CT-B21] as telas de acesso passam no axe, no clássico e no painel de marca, nos dois temas', function (string $layout, string $url, string $tema): void {
    definirAparenciaGlobal($layout === 'painel' ? aparenciaDeTeste('P') : null);

    // Paga a compilação da tela fora do cronômetro do Playwright.
    $this->get($url);

    $pagina = $tema === 'escuro' ? visit($url)->inDarkMode() : visit($url)->inLightMode();

    comTelaAssentada($pagina->resize(1440, 900)
        ->assertPathIs($url))
        ->assertPresent('.fi-auth-theme-switcher-wrapper[role="menu"]')
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

it('[CT-B22] no clássico, a pessoa chega do e-mail ao botão de entrar só com o teclado', function (): void {
    definirAparenciaGlobal(null);
    $this->get('/admin/login');

    $pagina = visit('/admin/login')->resize(1440, 900)->assertPathIs('/admin/login');

    $pagina->click('#form\\.email');

    // Cada parada: o id, o tipo, a tag e se o elemento é do mesmo formulário do e-mail.
    $paradas = [];
    for ($i = 0; $i < 8; $i++) {
        $pagina->script("document.querySelectorAll('[data-kit-foco]').forEach(e => e.removeAttribute('data-kit-foco')); document.activeElement?.setAttribute('data-kit-foco', '1')");
        $pagina->keys('[data-kit-foco="1"]', 'Tab');
        $paradas[] = (string) $pagina->script("(() => { const e = document.activeElement; const doEmail = document.getElementById('form.email').closest('form'); return (e.id || '') + '|' + (e.getAttribute('type') || '') + '|' + (e.tagName || '') + '|' + (doEmail !== null && doEmail.contains(e) ? 'form' : 'fora'); })()");
    }

    $senha  = collect($paradas)->search(fn (string $p): bool => str_starts_with($p, 'form.password|'));
    $enviar = collect($paradas)->search(fn (string $p): bool => str_ends_with($p, '|submit|BUTTON|form'));

    expect($senha)->toBeInt('o foco não passou pela senha: '.implode(' > ', $paradas))
        ->and($enviar)->toBeInt('o foco não chegou ao botão de entrar do formulário: '.implode(' > ', $paradas))
        ->and($senha)->toBeLessThan($enviar);
});

it('[CT-B23] com "reduzir movimento" no sistema, a troca entre as telas não anima mesmo com a transição ligada', function (): void {
    definirAparenciaGlobal([
        ...aparenciaDeTeste('P'), 'lado_do_formulario' => 'direita', 'transicao_entre_telas' => true,
        'telas'                                        => ['recuperacao' => [...aparenciaDeTeste('P'), 'lado_do_formulario' => 'esquerda']],
    ]);
    $this->get('/admin/login');

    $pagina = visitaComMovimentoReduzido('/admin/login')->resize(1440, 900)->assertPresent('.kit-painel-de-marca');

    // Controle positivo: a página participa da transição — só o sistema a desliga.
    $pagina->assertPresent('style[data-kit-transicao-entre-telas]')
        ->assertPresent('.kit-painel-de-marca[data-kit-transicao]')
        ->assertScript("matchMedia('(prefers-reduced-motion: reduce)').matches");

    expect((string) $pagina->script("getComputedStyle(document.querySelector('.fi-auth-media-wrapper')).viewTransitionName"))->toBe('none');

    $pagina->script("window.addEventListener('pageswap', (e) => sessionStorage.setItem('kit-transicao', e.viewTransition ? 'sim' : 'nao'))");

    $pagina->click('Esqueceu sua senha?')
        ->assertPathIs('/admin/password-reset/request');

    expect($pagina->script("sessionStorage.getItem('kit-transicao')"))->toBe('nao');
});

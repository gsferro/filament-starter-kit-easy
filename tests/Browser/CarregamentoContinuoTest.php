<?php

use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

it('[CT-B01] chegar ao fim da tabela no modo rolagem carrega exatamente o próximo bloco', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    ligarCarregamento('rolagem', 25);
    $master = usuarioDoKit('master_global');
    User::factory()->count(74)->create();

    $this->actingAs($master);
    $this->get('/admin/users')->assertOk();

    $pagina = visit('/admin/users')->inLightMode();
    $pagina->assertPathIs('/admin/users')
        ->assertSee('Mostrando 25 de 75')
        ->assertAttribute('.fi-ta-infinite-scroll', 'data-mode', 'scroll')
        ->assertAttribute('.fi-ta-infinite-scroll', 'data-loaded', '25')
        ->assertScript("document.querySelectorAll('.fi-ta-row').length === 25");

    $pagina->script("document.querySelector('.fi-ta-infinite-scroll').scrollIntoView({block: 'end'})");

    $pagina->assertSee('Mostrando 50 de 75')
        ->assertAttribute('.fi-ta-infinite-scroll', 'data-loaded', '50')
        ->assertAttribute('.fi-ta-infinite-scroll', 'data-has-more', '1')
        ->assertScript("document.querySelectorAll('.fi-ta-row').length === 50")
        ->assertNoJavaScriptErrors();
});

/**
 * CT-B02 — o rodapé do carregamento contínuo é legível no tema escuro.
 *
 * Oráculo de cor, não `assertSee`: o texto do rodapé e o fundo atrás dele são lidos por
 * `getComputedStyle` e convertidos para RGB num canvas (o Chrome devolve `oklch(...)` para as
 * cores do Filament 5; o canvas resolve qualquer espaço de cor). Luminância do texto acima de 120
 * e do fundo abaixo de 80 só acontece com o `dark` aplicado ao rodapé do pacote — no tema claro o
 * fundo é branco (luminância 255) e a asserção cai. QA-06 do quality gate, ciclo 1.
 */
it('[CT-B02] o rodapé do carregamento contínuo tem texto claro sobre fundo escuro no tema escuro', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    ligarCarregamento('rolagem', 10);
    $master = usuarioDoKit('master_global');
    User::factory()->count(29)->create();

    $this->actingAs($master);
    $this->get('/admin/users')->assertOk();

    $pagina = visit('/admin/users')->inDarkMode();
    $pagina->assertPathIs('/admin/users')
        ->assertScript("document.documentElement.classList.contains('dark')")
        ->assertVisible('.fi-ta-infinite-scroll')
        ->assertAttribute('.fi-ta-infinite-scroll', 'data-mode', 'scroll')
        ->assertScript(<<<'JS'
            (() => {
                const rgb = (cor) => {
                    const contexto = document.createElement('canvas').getContext('2d');
                    contexto.fillStyle = cor;
                    contexto.fillRect(0, 0, 1, 1);
                    return [...contexto.getImageData(0, 0, 1, 1).data].slice(0, 3);
                };
                const luminancia = ([r, g, b]) => 0.2126 * r + 0.7152 * g + 0.0722 * b;
                const rodape = document.querySelector('.fi-ta-infinite-scroll');
                let elemento = rodape;
                let fundo = 'rgba(0, 0, 0, 0)';
                while (elemento && /rgba\(0, 0, 0, 0\)|transparent/.test(fundo)) {
                    fundo = getComputedStyle(elemento).backgroundColor;
                    elemento = elemento.parentElement;
                }
                return luminancia(rgb(getComputedStyle(rodape).color)) > 120 && luminancia(rgb(fundo)) < 80;
            })()
        JS)
        ->assertNoJavaScriptErrors();
});

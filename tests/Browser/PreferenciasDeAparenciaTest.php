<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * CT-B01 e CT-B02 — o que só o navegador calcula na aparência por usuário.
 *
 * `wikis/specs/feat/preferencias-de-aparencia/preferencias-de-aparencia/05-casos-de-teste-browser.md`.
 *
 * O recolher do menu é Alpine e a fonte pode perder a cascata para o tema: o HTML servido é o
 * mesmo com e sem o defeito. O oráculo é `getBoundingClientRect`/`getComputedStyle`.
 *
 * O `beforeEach` não arranja painel (`.ai/rules/testes-browser.md`): cada cenário arranja o seu.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * Largura VISÍVEL da barra lateral (a parte que cai dentro da viewport) e o estado do primeiro ícone.
 */
const MEDIDA_DA_BARRA_LATERAL = <<<'JS'
    (() => {
        const s = document.querySelector('.fi-sidebar').getBoundingClientRect();
        const visivel = Math.max(0, Math.min(s.right, innerWidth) - Math.max(s.left, 0));
        const i = document.querySelector('.fi-sidebar-item-icon');
        const r = i.getBoundingClientRect(), cs = getComputedStyle(i);
        const iconeVisivel = cs.display !== 'none' && cs.visibility !== 'hidden' && r.width > 0 && r.height > 0
            && r.right > 0 && r.left < innerWidth && r.bottom > 0 && r.top < innerHeight
            && Math.min(r.right, innerWidth) - Math.max(r.left, 0) > 0;
        return JSON.stringify({ largura: visivel, iconeVisivel });
    })()
JS;

/**
 * Espera a largura visível da barra parar de mudar (fim da transição), sem `wait()` fixo.
 */
const ESTABILIZA_A_BARRA_LATERAL = <<<'JS'
    new Promise(resolve => {
        const medir = () => { const s = document.querySelector('.fi-sidebar').getBoundingClientRect();
            return Math.max(0, Math.min(s.right, innerWidth) - Math.max(s.left, 0)); };
        let anterior = medir(), estaveis = 0;
        const id = setInterval(() => {
            const atual = medir();
            estaveis = atual === anterior ? estaveis + 1 : 0;
            anterior = atual;
            if (estaveis >= 3) { clearInterval(id); resolve(true); }
        }, 100);
    })
JS;

/**
 * CT-B01 — recolhido, o menu segue o modo da Ana (a instalação é o oposto). "Esconder tudo" roda
 * nos três painéis e, depois de recolher, a barra reabre: volta à largura aberta e um item de
 * navegação fica visível e clicável (BP-37).
 */
it('[CT-B01] recolhido, o menu segue o modo escolhido pela Ana', function (string $painel, string $papel, string $menuDaInstalacao, string $menuDaAna, bool $manterIcones): void {
    aparenciaDaInstalacao(porUsuario: true, menu: $menuDaInstalacao);

    $ana = usuarioDoKit($papel, 'ana@example.com');
    aparenciaDe($ana, ['menu' => $menuDaAna]);

    $this->actingAs($ana)->get("/{$painel}")->assertOk();

    $pagina = visit("/{$painel}")->resize(1280, 800);

    $pagina->assertPathIs("/{$painel}")
        ->assertPresent('.fi-sidebar-item-icon');

    $aberta = json_decode((string) $pagina->script(MEDIDA_DA_BARRA_LATERAL), true, flags: JSON_THROW_ON_ERROR);

    expect($aberta['largura'])->toBeGreaterThan(0)
        ->and($aberta['iconeVisivel'])->toBeTrue();

    $larguraAberta = $aberta['largura'];

    $pagina->click('[aria-label="Recolher barra lateral"]:visible');

    // A transição leva um instante: espera o estado final (menor que a aberta), sem wait().
    $pagina->assertScript(
        "(() => { const s = document.querySelector('.fi-sidebar').getBoundingClientRect();"
        ."return Math.max(0, Math.min(s.right, innerWidth) - Math.max(s.left, 0)) < {$larguraAberta}; })()"
    );

    // Fim da transição: a largura visível parou de mudar.
    $pagina->assertScript(ESTABILIZA_A_BARRA_LATERAL);

    $recolhida = json_decode((string) $pagina->script(MEDIDA_DA_BARRA_LATERAL), true, flags: JSON_THROW_ON_ERROR);

    if ($manterIcones) {
        expect($recolhida['largura'])->toBeGreaterThan(0)->toBeLessThan($larguraAberta)
            ->and($recolhida['iconeVisivel'])->toBeTrue();
    } else {
        expect($recolhida['largura'])->toEqual(0)
            ->and($recolhida['iconeVisivel'])->toBeFalse();

        // Reabre pelo botão do topo: volta à largura aberta, com item de navegação visível e clicável.
        $pagina->click('.fi-topbar-open-sidebar-btn');

        $pagina->assertScript(
            "(() => { const s = document.querySelector('.fi-sidebar').getBoundingClientRect();"
            ."return Math.max(0, Math.min(s.right, innerWidth) - Math.max(s.left, 0)) >= {$larguraAberta} - 1; })()"
        );
        $pagina->assertScript(ESTABILIZA_A_BARRA_LATERAL);

        $reaberta = json_decode((string) $pagina->script(MEDIDA_DA_BARRA_LATERAL), true, flags: JSON_THROW_ON_ERROR);

        expect($reaberta['largura'])->toEqualWithDelta($larguraAberta, 1)
            ->and($reaberta['iconeVisivel'])->toBeTrue();

        // Clicável: o elemento no centro do item é o próprio item (nada o cobre) e o clique funciona.
        $pagina->assertScript(<<<'JS'
            (() => {
                const a = document.querySelector('.fi-sidebar-item a, .fi-sidebar-item-btn');
                const r = a.getBoundingClientRect();
                const topo = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2);
                return r.width > 0 && r.height > 0 && r.bottom <= innerHeight && a.contains(topo);
            })()
        JS);

        $pagina->click('.fi-sidebar-item-btn >> nth=0');
    }

    $pagina->assertNoJavaScriptErrors();
})->with([
    'admin: instalação Esconder tudo, Ana Manter os ícones' => ['admin', 'admin', 'oculto', 'icones', true],
    'admin: instalação Manter os ícones, Ana Esconder tudo' => ['admin', 'admin', 'icones', 'oculto', false],
    'app: instalação Manter os ícones, Ana Esconder tudo'   => ['app', 'panel_user', 'icones', 'oculto', false],
    'infra: instalação Manter os ícones, Ana Esconder tudo' => ['infra', 'infra', 'icones', 'oculto', false],
])->group('browser');

/**
 * CT-B02 — o texto da Ana fica no percentual de P-08 sobre o tamanho de referência da Bia.
 */
const MEDIDA_DA_FONTE = <<<'JS'
    (() => {
        const t = document.querySelector('.fi-header-heading'), r = document.querySelector('.fi-sidebar-item-label');
        return JSON.stringify({
            titulo: parseFloat(getComputedStyle(t).fontSize),
            rotulo: parseFloat(getComputedStyle(r).fontSize),
        });
    })()
JS;

it('[CT-B02] o texto da Ana fica no percentual do tamanho escolhido', function (string $fonte, float $razao): void {
    aparenciaDaInstalacao(porUsuario: true, fonte: 'normal');

    $bia = usuarioDoKit('admin', 'bia@example.com');
    $ana = usuarioDoKit('admin', 'ana@example.com');
    aparenciaDe($ana, ['fonte' => $fonte]);

    $this->actingAs($bia)->get('/admin')->assertOk();

    $referencia = visit('/admin')->resize(1280, 800);
    $referencia->assertPathIs('/admin')->assertPresent('.fi-header-heading');
    $base = json_decode((string) $referencia->script(MEDIDA_DA_FONTE), true, flags: JSON_THROW_ON_ERROR);

    expect($base['titulo'])->toBeGreaterThan(0)->and($base['rotulo'])->toBeGreaterThan(0);

    test()->flushSession();
    $this->actingAs($ana)->get('/admin')->assertOk();

    $pagina = visit('/admin')->resize(1280, 800);
    $pagina->assertPathIs('/admin')->assertPresent('.fi-header-heading');
    $medida = json_decode((string) $pagina->script(MEDIDA_DA_FONTE), true, flags: JSON_THROW_ON_ERROR);

    expect($medida['titulo'] / $base['titulo'])->toEqualWithDelta($razao, 0.01)
        ->and($medida['rotulo'] / $base['rotulo'])->toEqualWithDelta($razao, 0.01);

    $pagina->assertNoJavaScriptErrors();
})->with([
    'Pequena'      => ['pequena', 0.875],
    'Grande'       => ['grande', 1.125],
    'Muito grande' => ['muito-grande', 1.25],
])->group('browser');

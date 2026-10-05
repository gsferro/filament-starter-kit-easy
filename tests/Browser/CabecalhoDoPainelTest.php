<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * CT-B01 e CT-B02 — o que só o navegador calcula no cabeçalho dos painéis.
 *
 * `wikis/specs/feat/cabecalho-do-painel/cabecalho-do-painel/05-casos-de-teste-browser.md`.
 *
 * O HTML servido é idêntico com e sem o defeito: a ocultação abaixo de 768 px é media query,
 * a posição é layout flex, e a troca da logo é a classe `dark` do `<html>` mais CSS. O
 * oráculo é `getComputedStyle`/`getBoundingClientRect`, nunca presença no DOM.
 *
 * O `beforeEach` não arranja painel (`.ai/rules/testes-browser.md`): cada cenário arranja o
 * seu e visita o `/admin`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * CT-B01 — 767 px oculta o bloco, 768 px o mostra, e à esquerda do avatar.
 *
 * Uma visita por largura: o `resize()` vai ANTES de qualquer asserção, mas a visita já abriu
 * a página; por isso o cenário recarrega nada — mede depois do resize, que reaplica a media
 * query no Chromium.
 */
it('[CT-B01] o bloco do usuário na borda de 768 px', function (int $largura, bool $visivel): void {
    gravarConfiguracao('cabecalho_usuario', true);
    alinharConfiguracoesDoKit();

    $maria = usuarioDoKit('master_global', 'maria@example.com');
    $maria->update(['name' => 'Maria Ômega']);

    $this->actingAs($maria);
    $this->get('/admin');

    $pagina = visit('/admin')->resize($largura, 900);

    // No DOM nas duas linhas: sem isto, "oculto" passaria com o bloco nunca renderizado.
    $pagina->assertScript("document.querySelector('.kit-usuario') !== null")
        ->assertVisible('.fi-user-menu-trigger');

    if (! $visivel) {
        $pagina->assertScript("getComputedStyle(document.querySelector('.kit-usuario')).display === 'none'")
            ->assertNoJavaScriptErrors();

        return;
    }

    $pagina->assertVisible('.kit-usuario')
        ->assertSeeIn('.kit-usuario', 'Maria Ômega');

    $medida = json_decode((string) $pagina->script(<<<'JS'
        (() => {
            const b = document.querySelector('.kit-usuario').getBoundingClientRect(),
                  a = document.querySelector('.fi-user-menu-trigger').getBoundingClientRect();
            return JSON.stringify({ direitaDoBloco: b.right, esquerdaDoAvatar: a.left, largura: b.width });
        })()
    JS), true, flags: JSON_THROW_ON_ERROR);

    expect($medida['largura'])->toBeGreaterThan(0)
        ->and($medida['direitaDoBloco'])->toBeLessThanOrEqual($medida['esquerdaDoAvatar']);

    $pagina->assertNoJavaScriptErrors();
})->with([
    '767 px oculto'  => [767, false],
    '768 px visível' => [768, true],
])->group('browser');

/**
 * CT-B02 — na marca separada, a composição mostra a variante escura só no tema escuro.
 *
 * Disco `public` REAL: o navegador baixa a URL, e o `Storage::fake` gravaria onde ela não vê.
 * Uma visita por tema — `inDarkMode()` só vale no load.
 */
it('[CT-B02] a logo da composição troca com o tema', function (): void {
    Storage::disk('public')->put('kit/logo-cab.png', UploadedFile::fake()->image('a.png', 400, 80)->get());
    Storage::disk('public')->put('kit/logo-cab-dark.png', UploadedFile::fake()->image('b.png', 400, 80)->get());

    gravarConfiguracao('logo', 'kit/logo-cab.png');
    gravarConfiguracao('logo_dark', 'kit/logo-cab-dark.png');
    gravarConfiguracao('unifica_logo_marca', false);
    gravarConfiguracao('cabecalho_logo_da_marca', true);
    alinharConfiguracoesDoKit();

    $this->actingAs(usuarioDoKit('admin'));
    $this->get('/admin');

    $todasOcultas  = fn (string $classe): string => "(() => { const l = [...document.querySelectorAll('.kit-cabecalho .{$classe}')]; return l.length > 0 && l.every(e => getComputedStyle(e).display === 'none'); })()";
    $algumaVisivel = fn (string $classe): string => "[...document.querySelectorAll('.kit-cabecalho .{$classe}')].some(e => getComputedStyle(e).display !== 'none' && e.getBoundingClientRect().width > 0)";

    $clara = visit('/admin')->inLightMode();

    $clara->assertScript($todasOcultas('fi-logo-dark'))
        ->assertScript($algumaVisivel('fi-logo-light'))
        ->assertScript("[...document.querySelectorAll('.kit-cabecalho .fi-logo-light')].some(e => getComputedStyle(e).display !== 'none' && e.src.includes('logo-cab.png'))")
        ->assertNoJavaScriptErrors();

    $escura = visit('/admin')->inDarkMode();

    $escura->assertScript($todasOcultas('fi-logo-light'))
        ->assertScript($algumaVisivel('fi-logo-dark'))
        ->assertScript("[...document.querySelectorAll('.kit-cabecalho .fi-logo-dark')].some(e => getComputedStyle(e).display !== 'none' && e.src.includes('logo-cab-dark.png'))")
        ->assertNoJavaScriptErrors();
})->group('browser');

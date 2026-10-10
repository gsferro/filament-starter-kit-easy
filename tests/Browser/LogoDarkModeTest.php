<?php

use App\Models\Tenant;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * CT-B01 — o swap visual light↔dark da logo na lock-screen.
 *
 * `wikis/specs/feat/logo-dark-mode/logo-dark-mode/05-casos-de-teste-browser.md`.
 *
 * O que só o navegador prova: a classe `dark` no `<html>` realmente mostra a
 * escura e esconde a clara. O HTML do CT-16 já assere as DUAS `<img>` — aqui se
 * afirma a VISIBILIDADE, via `getComputedStyle`, que é o mecanismo que o CSS
 * nativo (`app.css`, `.dark .fi-logo-light { display:none }`) dispara.
 *
 * Arquivos no disk público REAL, e não `Storage::fake`: o navegador baixa
 * `asset('storage/...')` por HTTP, e o fake gravaria num diretório de teste que
 * a URL pública não enxerga — toda `<img>` sairia quebrada com a página abrindo.
 *
 * `master_global` SEM organização de propósito: sem `tenant_corrente`, a
 * resolução cai no par do kit — que é o caminho mais público e o que o screenshot
 * de documentação ilustra. O override do tenant já tem oráculo no CT-16.
 */
it('[CT-B01] troca a logo da marca entre claro e escuro no navegador', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    Storage::disk('public')->put('kit/logo-b01.png', UploadedFile::fake()->image('logo-b01.png', 400, 80)->get());
    Storage::disk('public')->put('kit/logo-b01-dark.png', UploadedFile::fake()->image('logo-b01-dark.png', 400, 80)->get());

    gravarConfiguracao('logo', 'kit/logo-b01.png');
    gravarConfiguracao('logo_dark', 'kit/logo-b01-dark.png');
    gravarConfiguracao('unifica_logo_marca', false);
    alinharConfiguracoesDoKit();

    $this->actingAs(usuarioDoKit('master_global'));

    // A tela é SimplePage de Livewire: quem marca a sessão como bloqueada é a
    // rota do pacote, não `session()` no arranjo — o navegador precisa do cookie.
    $this->post(route('lockscreen.app.lock-session'))->assertRedirect();

    /*
     * Uma visita por tema, e não `inDarkMode()` no meio: a emulação de
     * `prefers-color-scheme` vale no LOAD da página — o `theme.js` do Filament
     * lê a preferência do sistema no `DOMContentLoaded` e fixa `dark` no `<html>`
     * ali; trocar a emulação depois não reavalia.
     */
    $paginaClara = visit('/app/screen/lock')->inLightMode();

    $paginaClara
        ->assertVisible('.fi-logo-light')
        // A escura está no DOM, só escondida: o swap é CSS, não re-render.
        ->assertScript("document.querySelector('.fi-logo-dark') !== null")
        ->assertScript("getComputedStyle(document.querySelector('.fi-logo-dark')).display === 'none'")
        ->assertNoJavaScriptErrors();

    $paginaClara->screenshotElement('.fi-auth-media-wrapper', 'logo-tema-claro');

    $paginaEscura = visit('/app/screen/lock')->inDarkMode();

    $paginaEscura
        ->assertScript("document.querySelector('.fi-logo-light') !== null")
        ->assertScript("getComputedStyle(document.querySelector('.fi-logo-light')).display === 'none'")
        ->assertVisible('.fi-logo-dark')
        ->assertNoJavaScriptErrors();

    $paginaEscura->screenshotElement('.fi-auth-media-wrapper', 'logo-tema-escuro');
})->group('browser');

it('[CT-B02] mantém a logo unificada visível nos dois temas da tela de bloqueio', function (bool $daOrganizacao, bool $escuro): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $caminho = 'kit/logo-unificada-b02.png';
    Storage::disk('public')->put($caminho, UploadedFile::fake()->image('logo.png', 400, 80)->get());
    Storage::disk('public')->put('kit/logo-escura-b02.png', UploadedFile::fake()->image('escura.png', 400, 80)->get());

    gravarConfiguracao('logo', $caminho);
    gravarConfiguracao('logo_dark', 'kit/logo-escura-b02.png');
    gravarConfiguracao('unifica_logo_marca', ! $daOrganizacao);
    alinharConfiguracoesDoKit();

    if ($daOrganizacao) {
        config(['kit.tenancy.enabled' => true]);
        $caminho = 'organizacoes/logo-unificada-b02.png';
        Storage::disk('public')->put($caminho, UploadedFile::fake()->image('organizacao.png', 400, 80)->get());
        $organizacao = Tenant::factory()->create(['logo' => $caminho, 'unifica_logo' => true]);
        session(['tenant_corrente' => $organizacao->getKey()]);
    }

    $this->actingAs(usuarioDoKit('master_global'));
    $this->post(route('lockscreen.app.lock-session'))->assertRedirect();

    $pagina = $escuro
        ? visit('/app/screen/lock')->inDarkMode()
        : visit('/app/screen/lock')->inLightMode();

    $pagina->assertVisible('.fi-auth-media-wrapper img.fi-logo')
        ->assertAttributeContains('.fi-auth-media-wrapper img.fi-logo', 'src', '/storage/'.$caminho)
        ->assertScript("document.querySelector('.fi-auth-media-wrapper img.fi-logo').naturalWidth > 0")
        ->assertScript("document.querySelector('.fi-auth-media-wrapper .fi-logo-light, .fi-auth-media-wrapper .fi-logo-dark') === null")
        ->assertScript("document.documentElement.classList.contains('dark') === ".($escuro ? 'true' : 'false'))
        ->assertNoJavaScriptErrors();
})->with([
    'instalacao clara'   => [false, false],
    'instalacao escura'  => [false, true],
    'organizacao clara'  => [true, false],
    'organizacao escura' => [true, true],
])->group('browser');

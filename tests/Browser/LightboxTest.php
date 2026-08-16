<?php

use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Support\Facades\Storage;

/**
 * CT-B01 — o lightbox abre de fato sobre a imagem clicada.
 *
 * Por que navegador e não componente: a assertion é sobre um elemento que **não existe no HTML
 * entregue pelo servidor**. O overlay do `fslightbox` é construído em runtime, depois do clique.
 *
 * E o caso que justifica o custo é específico: o `x-on:click` pode estar perfeito no HTML e o
 * clique ser **inerte**, porque `php artisan filament:assets` não publicou o JS do pacote. Não há
 * erro, não há 500, e nada no HTML distingue os dois estados.
 *
 * Ver `wikis/specs/main/lightbox-em-imagens-e-documentos/05-casos-de-teste-browser.md`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    // Arquivo de verdade no disco: `ImageColumn` confere a existência por padrão e devolve
    // `src=""` quando não acha (ImageColumn.php:208-220).
    Storage::fake('public');
    Storage::disk('public')->put('avatars/foto.png', file_get_contents(public_path('favicon.ico')));

    User::create([
        'name'       => 'Pessoa Com Avatar',
        'email'      => 'comavatar@example.com',
        'password'   => 'password',
        'avatar_url' => 'avatars/foto.png',
    ]);
});

it('abre o lightbox ao clicar na miniatura do avatar', function (): void {
    // `actingAs()` antes do `visit()`: o plugin sobe o servidor IN-PROCESS, então a sessão do
    // teste vale dentro do navegador. Login pela tela custaria ~20 s por cenário.
    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    visit('/admin/users')
        // A classe vem do macro (`extraImgAttributes`), é contrato do pacote.
        ->assertPresent('img.simple-light-box-img-indicator')
        ->click('img.simple-light-box-img-indicator')
        // A âncora do cenário: o container do fslightbox só existe se o JS foi publicado E
        // executou. Sem ele, tudo o mais aqui passaria com o clique inerte.
        ->assertPresent('.fslightbox-container')
        // Prova que o clique NÃO navegou — uma implementação que trocasse o lightbox por um
        // link para a imagem passaria em todo o resto.
        ->assertPathIs('/admin/users')
        // Apoio, nunca oráculo único: console limpo passa com página em branco. E é
        // `assertNoJavaScriptErrors` e não `assertNoSmoke` porque a tela carrega componentes de
        // plugin de terceiro (.ai/rules/testes-browser.md).
        ->assertNoJavaScriptErrors();
});

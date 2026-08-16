<?php

use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * O lightbox de imagem em tabela (solution-forest/filament-simplelightbox).
 *
 * O que estes casos provam e o smoke de tela não prova: a coluna existir com a miniatura é
 * uma coisa; ela carregar o GATILHO do lightbox é outra. Uma `ImageColumn` sem
 * `->simpleLightbox()` renderiza a miniatura certa, com a URL certa, e passa em qualquer
 * verificação de "a tela abriu".
 *
 * Ver `wikis/specs/main/lightbox-em-imagens-e-documentos/04-casos-de-teste.md`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->master = usuarioDoKit('master_global', 'master@example.com');

    /*
     * Disco falso com o arquivo DE VERDADE dentro — e não só a coluna preenchida.
     *
     * `ImageColumn` verifica a existência do arquivo por padrão
     * (`shouldCheckFileExistence()`, ImageColumn.php:217-220) e devolve `null` quando ele não
     * existe: a célula sai com `src=""`, sem erro. Fixture com caminho inventado faria os casos
     * falharem por um motivo que não é o deles — e faria parecer que a coluna está quebrada.
     */
    Storage::fake('public');
    Storage::disk('public')->put('avatars/foto.png', 'png');
});

/**
 * CT-01 — o avatar enviado vira miniatura ampliável na listagem do /admin.
 *
 * Duas asserções, e nenhuma delas sozinha é oráculo: a URL prova que o disk resolvido é o do
 * upload (sem `disk('public')` a coluna cairia no `local`, que não é servível por URL); o
 * gatilho prova que o macro foi aplicado à coluna.
 */
it('exibe o avatar com gatilho de lightbox na listagem de usuários do admin', function (): void {
    User::create([
        'name'       => 'Com Avatar',
        'email'      => 'comavatar@example.com',
        'password'   => 'password',
        'avatar_url' => 'avatars/foto.png',
    ]);

    noPainelBootado('admin');

    $this->actingAs($this->master);

    Livewire::test(ListUsers::class)
        // O kit liga `deferLoading()` globalmente (ConfiguraFilamentGlobal): sem isto a
        // resposta inicial traz o cabeçalho e NENHUMA linha, e a asserção falharia por um
        // motivo que não é o dela.
        ->call('loadTable')
        ->assertSee('avatars/foto.png')
        ->assertSee('SimpleLightBox.open', escape: false);
});

/**
 * CT-04 — quem não enviou avatar não ganha miniatura clicável.
 *
 * A asserção é uma CONTAGEM, e não um `assertDontSee`: as duas pessoas estão na mesma página,
 * então o caminho da mídia da primeira aparece no HTML de qualquer jeito. Um `assertDontSee`
 * genérico passaria com um placeholder clicável renderizado para a segunda — que é
 * exatamente o defeito que `->defaultImageUrl('/placeholder.png')` introduziria.
 */
it('nao oferece lightbox para quem nao enviou avatar', function (): void {
    User::create([
        'name'       => 'Com Avatar',
        'email'      => 'comavatar@example.com',
        'password'   => 'password',
        'avatar_url' => 'avatars/foto.png',
    ]);
    User::create(['name' => 'Sem Avatar', 'email' => 'semavatar@example.com', 'password' => 'password']);

    noPainelBootado('admin');

    $this->actingAs($this->master);

    $html = Livewire::test(ListUsers::class)
        ->call('loadTable')
        ->assertSee('Com Avatar')
        ->assertSee('Sem Avatar')
        ->html();

    // A classe é injetada pelo macro em `extraImgAttributes` — uma por miniatura renderizada.
    expect(substr_count($html, 'simple-light-box-img-indicator'))->toBe(1);
});

/**
 * CT-03 — o macro existe em TODO painel do kit, inclusive no /infra, que hoje não tem mídia.
 *
 * A asserção é sobre o PLUGIN registrado no painel, e não sobre o macro em si: macro é
 * estático na classe `ImageColumn`, então o primeiro painel a bootar o registra para o
 * processo inteiro — e as linhas seguintes do dataset passariam mesmo que aqueles painéis
 * não tivessem o plugin. Seria um falso verde.
 *
 * O modo de falha coberto: coluna de imagem criada num painel sem o plugin derruba a tela com
 * `BadMethodCallException` na RENDERIZAÇÃO — não no boot, não no deploy.
 */
it('registra o plugin de lightbox no painel', function (string $painel): void {
    expect(Filament::getPanel($painel)->getPlugin('filament-simplelightbox'))->not->toBeNull();
})->with([
    'admin' => 'admin',
    'app'   => 'app',
    // Sem mídia hoje: cobertura preventiva, ver ADR-02 da wiki.
    'infra' => 'infra',
]);

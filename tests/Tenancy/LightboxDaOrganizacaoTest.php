<?php

use App\Filament\Admin\Resources\Tenants\Pages\ListTenants;
use App\Filament\App\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * O lightbox na mídia das telas que só existem com multi-tenancy ligada.
 *
 * Esta suíte, e não `tests/Kit`, por dois motivos independentes:
 *
 * - o `TenantResource` só existe com o modo ligado — `canAccess()` devolve
 *   `config('kit.tenancy.enabled')` (TenantResource.php:84-87). Em single-tenant a página nem
 *   renderiza, e o caso falharia por um motivo que não é o dele;
 * - a listagem de usuários do /app precisa de organização corrente.
 *
 * Par do `tests/Kit/LightboxEmTabelaTest.php`, que cobre o avatar no /admin.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->master = usuarioDoKit('master_global', 'master@example.com');

    /*
     * O arquivo precisa existir de verdade: `ImageColumn` confere a existência por padrão
     * (`shouldCheckFileExistence()`, ImageColumn.php:217-220) e devolve `null` quando não
     * acha, deixando a célula com `src=""` sem erro nenhum.
     */
    Storage::fake('public');
    Storage::disk('public')->put('organizacoes/logos/acme.png', 'png');
    Storage::disk('public')->put('avatars/foto.png', 'png');
});

/**
 * CT-02 — a logo da organização vira miniatura ampliável.
 *
 * Duas asserções: a URL prova que o disk resolvido é o do upload; o gatilho prova que o macro
 * do plugin foi aplicado à coluna. Uma `ImageColumn` sem `->simpleLightbox()` passa em
 * qualquer verificação que olhe só a miniatura.
 */
it('exibe a logo com gatilho de lightbox na listagem de organizações', function (): void {
    tenant('Acme', 'acme')->update(['logo' => 'organizacoes/logos/acme.png']);

    noPainelBootado('admin');

    $this->actingAs($this->master);

    Livewire::test(ListTenants::class)
        ->call('loadTable')
        ->assertSee('organizacoes/logos/acme.png')
        ->assertSee('SimpleLightBox.open', escape: false);
});

/**
 * CT-05 — o avatar também é ampliável na listagem de usuários DA ORGANIZAÇÃO.
 *
 * Não é repetição do caso do /admin: o `App\Filament\App\Resources\Users\UserResource` é uma
 * classe SEPARADA do irmão de administração, de propósito (ADR-04 da wiki
 * admin-da-organizacao). Coluna acrescentada num e esquecida no outro é o modo de falha real,
 * e nenhum caso do /admin o pegaria.
 *
 * `noPainelDa()` é indispensável: sem organização corrente o `getEloquentQuery()` do resource
 * cai no ramo fail-closed (`whereRaw('1 = 0')`) e a listagem viria vazia.
 */
it('exibe o avatar com gatilho de lightbox na listagem de usuários da organização', function (): void {
    $acme = tenant('Acme', 'acme');

    $pessoa = User::create([
        'name'       => 'Com Avatar',
        'email'      => 'comavatar@example.com',
        'password'   => 'password',
        'avatar_url' => 'avatars/foto.png',
    ]);
    $pessoa->tenants()->attach($acme->getKey());

    /*
     * Boota o painel ADMIN, e não o app.
     *
     * O que se precisa do boot é o registro dos macros do plugin — e macro é estático em
     * `ImageColumn`, então qualquer painel do kit que boote serve (os três registram o
     * plugin; é o que o CT-03 assere). Bootar o `app` aqui morre em
     * `BreezyCore.php:112` com "Call to a member function parameter() on null": o boot dele
     * monta a rota do "Meu perfil", que num painel com tenancy exige o parâmetro de
     * organização que só um request de verdade tem.
     *
     * Em seguida o painel corrente vira o `app`, sem bootar — que é o mesmo estado em que os
     * demais casos desta suíte rodam.
     */
    noPainelBootado('admin');
    noPainelDa($acme);

    $this->actingAs($this->master);

    Livewire::test(ListUsers::class)
        // Idiom da suíte: a tabela do kit carrega adiada (`deferLoading` global).
        ->loadTable()
        ->assertSee('avatars/foto.png')
        ->assertSee('SimpleLightBox.open', escape: false);
});

<?php

use App\Filament\Pages\Auth\TelaBloqueio;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * Bloqueio de sessão (marjose123/filament-lockscreen) vestido com o layout de login do kit
 * (caresome/filament-auth-designer). O que estes casos fixam: a trava e o destravamento por
 * senha, o auto-lock por inatividade, e as duas armadilhas do acoplamento entre os dois
 * pacotes — o layout que não pode vazar para as outras páginas, e a tela aberta destravada
 * que não pode virar 500.
 *
 * `Filament::setCurrentPanel()` é obrigatório nos casos de componente: a página do pacote lê
 * `filament()->getCurrentPanel()->auth()` sem cair no painel default.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

function usuarioMaster(): User
{
    $user = User::create([
        'name'     => 'Teste',
        'email'    => fake()->unique()->safeEmail(),
        'password' => 'password',
    ]);

    $user->assignRole('master_global');

    return $user;
}

/**
 * O item de menu nasce num `bootUsing()`, então o painel precisa estar BOOTADO — em request
 * real quem faz isso é o middleware do Filament. E `actingAs` antes não é cerimônia: o item
 * de perfil do Breezy resolve o nome do usuário nesse mesmo boot e estoura com sessão
 * anônima.
 */
it('registra o plugin e o item de menu nos três painéis', function (string $painel): void {
    $this->actingAs(usuarioMaster());

    $panel = Filament::getPanel($painel);
    Filament::setCurrentPanel($painel);
    Filament::bootCurrentPanel();

    expect($panel->hasPlugin('filament-lockscreen'))->toBeTrue();

    $item = collect($panel->getUserMenuItems())->first(
        fn ($acao): bool => $acao->getName() === 'lockSession',
    );

    // sort(-1) é o que põe o item no grupo do "Meu perfil", e não depois do
    // alternador de tema — ver TelaBloqueio::itemDeMenu().
    expect($item)->not->toBeNull()
        ->and($item->getSort())->toBe(-1);
})->with(['app', 'admin', 'infra']);

it('trava a sessão pela rota, sem deslogar', function (): void {
    $user = usuarioMaster();
    $this->actingAs($user);

    $this->post('/admin/lock-session')->assertRedirect();

    expect(session('lockscreen'))->toBeTrue()
        ->and(auth()->id())->toBe($user->id);
});

it('redireciona o painel para a tela de bloqueio enquanto travado', function (string $painel): void {
    $this->actingAs(usuarioMaster());
    session(['lockscreen' => true]);

    $this->get("/{$painel}")->assertRedirect(route("lockscreen.{$painel}.page"));
})->with(['app', 'admin', 'infra']);

/**
 * O pacote entrega a tela como `SimplePage`; sem a trait do auth-designer na
 * TelaBloqueio ela sairia com `fi-simple-layout` e perderia a mídia da marca.
 */
it('renderiza a tela de bloqueio com o layout do login, não com o layout simples', function (string $painel): void {
    $this->actingAs(usuarioMaster());
    session(['lockscreen' => true]);

    $this->get(route("lockscreen.{$painel}.page"))
        ->assertOk()
        ->assertSee('fi-auth-layout', escape: false)
        ->assertDontSee('fi-simple-layout', escape: false);
})->with(['app', 'admin', 'infra']);

/**
 * O contrapeso do caso acima: a trait atribui `static::$layout`, e sem a redeclaração da
 * propriedade na TelaBloqueio a atribuição cai no estático herdado de `Filament\Pages\Page`
 * e passa a valer para o processo inteiro.
 */
it('não vaza o layout de login para as outras páginas do painel', function (): void {
    $this->actingAs(usuarioMaster());
    session(['lockscreen' => true]);

    $this->get(route('lockscreen.admin.page'))->assertOk()->assertSee('fi-auth-layout', escape: false);

    session(['lockscreen' => false]);

    $this->get('/admin')
        ->assertOk()
        ->assertDontSee('fi-auth-layout', escape: false);
});

it('mantém travado quando a senha está errada', function (): void {
    $this->actingAs(usuarioMaster());
    session(['lockscreen' => true]);
    Filament::setCurrentPanel('admin');

    Livewire::test(TelaBloqueio::class)
        ->set('data.password', 'senha-errada')
        ->call('authenticate')
        ->assertHasErrors('data.password');

    expect(session('lockscreen'))->toBeTrue();
});

it('destrava com a senha correta e mantém a sessão', function (): void {
    $user = usuarioMaster();
    $this->actingAs($user);
    session(['lockscreen' => true]);
    Filament::setCurrentPanel('admin');

    Livewire::test(TelaBloqueio::class)
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertHasNoErrors();

    expect(session('lockscreen'))->toBeFalse()
        ->and(auth()->id())->toBe($user->id);
});

it('tranca sozinha depois do tempo de inatividade', function (): void {
    $this->actingAs(usuarioMaster());

    session(['session_last_activity' => time() - (config('lockscreen.idle_timeout') + 60)]);

    $this->get('/admin')->assertRedirect(route('lockscreen.admin.page'));

    expect(session('lockscreen'))->toBeTrue();
});

it('manda visitante não autenticado para o login', function (): void {
    session(['lockscreen' => true]);

    $this->get(route('lockscreen.admin.page'))->assertRedirect();
});

/**
 * O `mount()` do pacote chama `redirect()` sem `return`: num processo onde o Livewire já
 * instalou o Redirector dele, o objeto chega onde o Laravel espera um código HTTP e o
 * request morre em `ErrorException ... could not be converted to int`. O primeiro GET existe
 * para instalar esse Redirector — sem ele o caso passa mesmo com o bug.
 */
it('redireciona em vez de estourar quando a tela é aberta sem a sessão travada', function (): void {
    $this->actingAs(usuarioMaster());

    $this->get('/admin')->assertOk();

    $this->get(route('lockscreen.admin.page'))->assertRedirect(url('admin'));
});

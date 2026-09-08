<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\CadastroUnificado;
use App\Filament\Pages\Auth\RegistroPorConvite;
use App\Http\Responses\RespostaDeCadastro;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * Registra pela tela de convite.
 *
 * Quando a página única está ligada, o ponto de entrada é `/cadastro` (CadastroUnificado);
 * quando desligada, o ponto de entrada é `/app/register` (RegistroPorConvite).
 */
function cadastrarPorConvite(string $componente, string $token, string $email = 'novo@example.com', string $nome = 'Fulano')
{
    Filament::auth()->logout();
    Filament::setCurrentPanel('app');

    return Livewire::withQueryParams(['token' => $token])
        ->test($componente)
        ->fillForm([
            'name'                 => $nome,
            'email'                => $email,
            'password'             => 'segredo-bem-longo-123',
            'passwordConfirmation' => 'segredo-bem-longo-123',
        ])
        ->call('register');
}

/** CT-67 — chave ligada, conta só de /admin cai no /admin. */
it('com a chave ligada e sem pretendida, conta convidada como admin cai no admin', function (): void {
    ligarLoginUnificado();

    $convite = ofertaPara('novo.admin@example.com', null, 'admin');

    cadastrarPorConvite(CadastroUnificado::class, $convite->enviar(), 'novo.admin@example.com')
        ->assertRedirect('/admin');

    expect(Filament::auth()->check())->toBeTrue()
        ->and(User::where('email', 'novo.admin@example.com')->exists())->toBeTrue();
});

/** CT-69 — com a chave ligada, o destino segue urlPara(). */
it('com a chave ligada delega ao DestinoAposLogin', function (): void {
    ligarLoginUnificado();

    $user = User::factory()->create(['email' => 'teste@example.com']);
    $user->assignRole('panel_user');

    session()->put('url.intended', url('/admin/users'));
    Filament::setCurrentPanel('app');
    Filament::auth()->login($user);

    $resposta = app(RegistrationResponse::class);

    expect($resposta)->toBeInstanceOf(RespostaDeCadastro::class);

    $redirect = $resposta->toResponse(Request::capture());

    expect($redirect->getTargetUrl())->toBe(url('/app'));
});

/** CT-70 — chave desligada, pretendida inacessível é descartada. */
it('com a chave desligada descarta a pretendida inacessivel', function (): void {
    $user = User::factory()->create(['email' => 'teste@example.com']);
    $user->assignRole('panel_user');

    session()->put('url.intended', url('/admin/users'));
    Filament::setCurrentPanel('app');
    Filament::auth()->login($user);

    $redirect = app(RegistrationResponse::class)->toResponse(Request::capture());

    expect($redirect->getTargetUrl())->toBe(url('/app'));
});

/** CT-72 — a sequência medida no navegador não volta. */
it('nao entrega 403 depois de visitar admin e se cadastrar', function (): void {
    ligarLoginUnificado();

    $this->get('/admin');

    $convite = ofertaPara('novo@example.com', null, 'panel_user');

    cadastrarPorConvite(CadastroUnificado::class, $convite->enviar())
        ->assertRedirect('/app');

    $this->get('/app')->assertOk();
});

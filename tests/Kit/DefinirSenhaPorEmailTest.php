<?php

use App\Livewire\DefinirSenhaPorEmail;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

/**
 * O bloco "Definir senha por e-mail" do perfil.
 *
 * Nasceu da validação real do login social (2026-08-26): a conta criada pelo Google não tem
 * senha que a pessoa conheça, e trocar a senha, ligar o 2FA e desbloquear a sessão pedem a
 * atual. O bloco reaproveita o fluxo do "Esqueceu a senha?" — o oráculo aqui é a NOTIFICAÇÃO
 * do Filament, com a URL assinada da página de redefinição, e a sessão encerrada em seguida.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

it('mostra o bloco no perfil dos três painéis', function (string $painel, string $papel): void {
    $this->actingAs(usuarioDoKit($papel));

    $this->get("/{$painel}/meu-perfil")
        ->assertOk()
        ->assertSee('Definir senha por e-mail')
        ->assertSee('Receber link por e-mail');
})->with([
    'app'   => ['app', 'panel_user'],
    'admin' => ['admin', 'admin'],
    'infra' => ['infra', 'infra'],
])->group('kit');

/**
 * O link é o mesmo do "Esqueceu a senha?": notificação do Filament, URL da rota assinada de
 * redefinição do painel corrente, para o e-mail da conta logada — e ninguém mais.
 */
it('envia o link de redefinicao do filament para o e-mail da conta e encerra a sessao', function (): void {
    Notification::fake();

    $user  = usuarioDoKit('panel_user', 'social@example.com');
    $outro = usuario('outro@example.com');

    $this->actingAs($user);
    noPainelBootado('app');

    Livewire::test(DefinirSenhaPorEmail::class)
        ->call('enviar')
        ->assertRedirect(Filament::getPanel('app')->getLoginUrl());

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $n): bool {
        return str_contains($n->url, '/app/password-reset/reset')
            && str_contains($n->url, 'signature=')
            && str_contains($n->url, 'email='.urlencode('social@example.com'));
    });
    Notification::assertNotSentTo($outro, ResetPasswordNotification::class);

    $this->assertGuest();
})->group('kit');

/** Sem sessão o bloco não tem a quem mandar — e não pode virar um "esqueci a senha" para terceiros. */
it('nao envia nada sem sessao', function (): void {
    Notification::fake();
    usuario('alguem@example.com');

    noPainelBootado('app');

    Livewire::test(DefinirSenhaPorEmail::class)->call('enviar');

    Notification::assertNothingSent();
    expect(User::query()->count())->toBe(1);
})->group('kit');

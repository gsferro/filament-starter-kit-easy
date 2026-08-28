<?php

use App\Models\Convite;
use App\Models\Role;
use App\Models\User;
use App\Support\ProvedorSocial;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Socialite;

/**
 * Cadastro pelo provedor social a partir da tela de registro — sem tenancy.
 * IDs de CT: `wikis/specs/feat/cadastro-social-por-convite-e-organizacao/…/04-casos-de-teste.md`.
 */
beforeEach(function (): void {
    Notification::fake();
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    ligarProvedor(ProvedorSocial::Google);
});

function conviteSocialPara(string $email): array
{
    $convite = Convite::factory()->create([
        'email'   => $email,
        'role_id' => Role::query()->where('name', 'panel_user')->value('id'),
    ]);

    return [$convite, $convite->enviar()];
}

/** CT-C01 — a tela de registro aberto oferece os botões sociais. */
it('oferece os botoes sociais na tela de registro aberto', function (): void {
    config()->set('kit.registro.habilitado', true);

    $this->get('/app/register')
        ->assertOk()
        ->assertSee('Entrar com Google')
        ->assertSee(route('auth.social.redirect', ProvedorSocial::Google), escape: false);
})->group('kit');

/** O `redirect` guarda `org` e `token` da query na sessão — e não os loga. */
it('guarda org e token da tela de registro na sessao ao redirecionar', function (): void {
    $this->get('/auth/google/redirect?org=acme&token=abc')
        ->assertRedirect()
        ->assertSessionHas('login_social.contexto', ['org' => 'acme', 'token' => 'abc']);
})->group('kit');

/** CT-C04 (Kit) — o convite é a porta: cria com o papel do convite, mesmo com o registro aberto FECHADO. */
it('cria a conta pelo provedor a partir do convite e o consome', function (): void {
    config()->set('kit.registro.habilitado', false);
    [$convite, $token] = conviteSocialPara('nova@example.com');

    Socialite::fake('google', usuarioSocialFalso(ProvedorSocial::Google, [], ['id' => 'sub-c', 'email' => 'nova@example.com', 'name' => 'Pessoa Convidada']));

    $this->withSession(['login_social.contexto' => ['token' => $token]])
        ->get('/auth/google/callback')
        ->assertRedirect();

    $nova = User::query()->where('email', 'nova@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($nova);

    expect($nova->name)->toBe('Pessoa Convidada')
        ->and($nova->origem)->toBe('google')
        ->and($nova->email_verified_at)->not->toBeNull()
        ->and($nova->hasRole('panel_user'))->toBeTrue()
        ->and($convite->fresh()->aceito_em)->not->toBeNull()
        ->and($convite->fresh()->token_lembrete)->toBeNull()
        ->and(session('login_social.contexto'))->toBeNull();
})->group('kit');

/** CT-C05 — convite para outro e-mail: recusa, nada criado, convite intacto. */
it('recusa o convite quando o e-mail do provedor e outro, e deixa o convite intacto', function (): void {
    config()->set('kit.registro.habilitado', false);
    [$convite, $token] = conviteSocialPara('dona@example.com');

    Socialite::fake('google', usuarioSocialFalso(ProvedorSocial::Google, [], ['id' => 'sub-o', 'email' => 'outra@example.com']));

    $this->withSession(['login_social.contexto' => ['token' => $token]])
        ->get('/auth/google/callback')
        ->assertRedirect(Filament::getPanel('app')->getLoginUrl());

    $this->assertGuest();

    expect(User::query()->where('email', 'outra@example.com')->exists())->toBeFalse()
        ->and($convite->fresh()->aceito_em)->toBeNull();
})->group('kit');

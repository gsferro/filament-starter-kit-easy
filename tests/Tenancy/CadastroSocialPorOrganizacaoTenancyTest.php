<?php

use App\Models\Convite;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ProvedorSocial;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Socialite;

/**
 * Cadastro pelo provedor social com a multi-organização ligada: `?org=` e `?token=`.
 * IDs de CT: `wikis/specs/feat/cadastro-social-por-convite-e-organizacao/…/04-casos-de-teste.md`.
 */
beforeEach(function (): void {
    Notification::fake();
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    ligarProvedor(ProvedorSocial::Google);

    $this->acme = tenant('Acme', 'acme');
    $this->acme->forceFill(['registro_habilitado' => true])->save();
    $this->globex = tenant('Globex', 'globex');
});

function papelNaOrganizacaoExiste(User $user, Tenant $tenant, string $papel): bool
{
    return DB::table(pivotDePapeis())
        ->where('model_id', $user->getKey())
        ->where('team_id', $tenant->getKey())
        ->where('role_id', Role::query()->where('name', $papel)->value('id'))
        ->exists();
}

/** CT-C02 — o link do botão carrega o `org` (e o `token`) da tela de registro. */
it('carrega org e token da tela de registro no link do botao social', function (): void {
    config()->set('kit.registro.habilitado', true);

    $this->get('/app/register?org=acme')
        ->assertOk()
        ->assertSee('auth/google/redirect?org=acme', escape: false);

    $convite = Convite::factory()->create(['email' => 'nova@example.com', 'tenant_id' => $this->globex->getKey(), 'role_id' => Role::query()->where('name', 'panel_user')->value('id')]);
    $token   = $convite->enviar();

    $this->get('/app/register?token='.$token)
        ->assertOk()
        ->assertSee('token='.$token, escape: false);
})->group('kit');

/** CT-C03 — `?org=acme` cria a conta NA acme, com o papel do registro aberto nela. */
it('cria a conta na organizacao do org da tela de registro', function (): void {
    config()->set('kit.registro.habilitado', true);

    Socialite::fake('google', usuarioSocialFalso(ProvedorSocial::Google, [], ['id' => 'sub-a', 'email' => 'nova@example.com']));

    $this->withSession(['login_social.contexto' => ['org' => 'acme']])
        ->get('/auth/google/callback')
        ->assertRedirect();

    $nova = User::query()->where('email', 'nova@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($nova);

    expect($nova->tenants->pluck('slug')->all())->toBe(['acme'])
        ->and(papelNaOrganizacaoExiste($nova, $this->acme, 'panel_user'))->toBeTrue()
        ->and($nova->origem)->toBe('google');
})->group('kit');

/** CT-C04 (Tenancy) — o convite leva organização e papel, com o registro aberto fechado. */
it('cria a conta pelo convite na organizacao do convite, mesmo sem registro aberto', function (): void {
    config()->set('kit.registro.habilitado', false);
    $convite = Convite::factory()->create(['email' => 'nova@example.com', 'tenant_id' => $this->globex->getKey(), 'role_id' => Role::query()->where('name', 'panel_user')->value('id')]);
    $token   = $convite->enviar();

    Socialite::fake('google', usuarioSocialFalso(ProvedorSocial::Google, [], ['id' => 'sub-g', 'email' => 'nova@example.com']));

    $this->withSession(['login_social.contexto' => ['token' => $token]])
        ->get('/auth/google/callback')
        ->assertRedirect();

    $nova = User::query()->where('email', 'nova@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($nova);

    expect($nova->tenants->pluck('slug')->all())->toBe(['globex'])
        ->and(papelNaOrganizacaoExiste($nova, $this->globex, 'panel_user'))->toBeTrue()
        ->and($convite->fresh()->aceito_em)->not->toBeNull();
})->group('kit');

/** CT-C06 — organização inexistente ou fechada ao registro: recusa, nada criado. */
it('recusa criar conta em organizacao inexistente ou fechada', function (string $org): void {
    config()->set('kit.registro.habilitado', true);

    Socialite::fake('google', usuarioSocialFalso(ProvedorSocial::Google, [], ['id' => 'sub-x', 'email' => 'nova@example.com']));

    $this->withSession(['login_social.contexto' => ['org' => $org]])
        ->get('/auth/google/callback')
        ->assertRedirect(Filament::getPanel('app')->getLoginUrl());

    $this->assertGuest();
    expect(User::query()->where('email', 'nova@example.com')->exists())->toBeFalse();
})->with(['inexistente' => ['nao-existe'], 'fechada' => ['globex']])->group('kit');

/** CT-C07 — o contexto morre no callback: uma recusa consome o `org`, e o próximo callback não o herda. */
it('consome o contexto da sessao no callback mesmo quando recusa', function (): void {
    config()->set('kit.registro.habilitado', true);

    // 1º callback: e-mail NÃO verificado → recusa; o `org` é consumido junto.
    Socialite::fake('google', usuarioSocialFalso(ProvedorSocial::Google, ['email_verified' => false], ['id' => 'sub-a', 'email' => 'nova@example.com']));
    $this->withSession(['login_social.contexto' => ['org' => 'acme']])->get('/auth/google/callback')->assertRedirect();
    $this->assertGuest();

    // 2º callback, verificado, SEM novo redirect: com tenancy e sem organização, a porta recusa.
    Socialite::fake('google', usuarioSocialFalso(ProvedorSocial::Google, [], ['id' => 'sub-a', 'email' => 'nova@example.com']));
    $this->get('/auth/google/callback')->assertRedirect(Filament::getPanel('app')->getLoginUrl());

    $this->assertGuest();
    expect(User::query()->where('email', 'nova@example.com')->exists())->toBeFalse();
})->group('kit');

/** CT-C08 — conta existente aceita o convite pelo botão social. */
it('faz a conta existente aceitar o convite na volta do provedor', function (): void {
    $user    = usuario('ja.tem@example.com');
    $convite = Convite::factory()->create(['email' => 'ja.tem@example.com', 'tenant_id' => $this->acme->getKey(), 'role_id' => Role::query()->where('name', 'panel_user')->value('id')]);
    $token   = $convite->enviar();

    Socialite::fake('google', usuarioSocialFalso(ProvedorSocial::Google, [], ['id' => 'sub-e', 'email' => 'ja.tem@example.com']));

    $this->withSession(['login_social.contexto' => ['token' => $token]])
        ->get('/auth/google/callback')
        ->assertRedirect();

    $this->assertAuthenticatedAs($user);

    expect($user->fresh()->tenants->pluck('slug')->all())->toBe(['acme'])
        ->and(papelNaOrganizacaoExiste($user, $this->acme, 'panel_user'))->toBeTrue()
        ->and($convite->fresh()->aceito_em)->not->toBeNull();
})->group('kit');

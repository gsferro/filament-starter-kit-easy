<?php

use App\Filament\App\Pages\ConvitesRecebidos;
use App\Models\Convite;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VinculoSocial;
use App\Notifications\ConfirmarVinculoSocial;
use App\Support\ProvedorSocial;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Socialite;
use Livewire\Livewire;

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

/*
|--------------------------------------------------------------------------
| R5 — conta existente NÃO consome convite na volta do provedor
|--------------------------------------------------------------------------
| Era o CT-C08 desta suíte, que afirmava o contrário: a conta existente virava
| membro da Acme na volta do provedor. O `?token=` entra na sessão pelo
| `redirecionar()`, que é rota GET pública sem CSRF — com SSO silencioso do
| provedor o convite era aceito sem clique nem consentimento da pessoa. F-04 da
| auditoria Blueprint; RQ-06 da wiki travas-de-escalada-de-papeis.
|
| O aceite migrou para a tela autenticada `ConvitesRecebidos` (CT-15), que exige
| o dono e pede confirmação.
*/

/** A pessoa que já tem conta e já opera OUTRA organização — o convite dela é para a Acme. */
function quemJaTemConta(Tenant $globex): User
{
    $user = usuarioComPapel('panel_user', $globex, 'ja.tem@example.com');

    $user->tenants()->attach($globex);

    return $user;
}

/**
 * CT-14/CT-16 — a volta do provedor não consome o convite de quem já tem conta.
 *
 * Os dois ramos de reconhecimento de conta existente, porque são verbos irmãos: pelo e-mail
 * verificado (CT-14) e pelo vínculo social já registrado (CT-16). Remover o aceite de um e
 * esquecer o outro é o mutante mais provável.
 *
 * O oráculo é o AGREGADO, não só `aceito_em`: uma implementação que deixasse de gravar a data
 * e continuasse filiando a pessoa à organização de terceiro é o mesmo furo com outra cara.
 * `Convite::valido()` de volta não-nulo prova as três metades de uma vez — pendente, dentro da
 * validade e sem marca de recusa.
 */
it('[CT-14/CT-16] nao consome o convite de conta existente na volta do provedor', function (bool $jaVinculada): void {
    $user  = quemJaTemConta($this->globex);
    $token = ofertaPara('ja.tem@example.com', $this->acme)->enviar();

    if ($jaVinculada) {
        VinculoSocial::vincular($user, ProvedorSocial::Google, 'sub-1');
    }

    Socialite::fake('google', usuarioSocialFalso(ProvedorSocial::Google, [], ['id' => 'sub-1', 'email' => 'ja.tem@example.com']));

    $this->withSession(['login_social.contexto' => ['token' => $token]])
        ->get('/auth/google/callback')
        ->assertRedirect();

    $this->assertAuthenticatedAs($user);

    expect(Convite::valido($token))->not->toBeNull()
        ->and($user->fresh()->tenants->pluck('slug')->all())->not->toContain('acme')
        ->and(papelNaOrganizacaoExiste($user, $this->acme, 'panel_user'))->toBeFalse();
})->with([
    'reconhecida pelo e-mail'  => [false],
    'reconhecida pelo vínculo' => [true],
])->group('kit');

/**
 * CT-15 — o convite que SOBROU da volta do provedor é aceito na tela autenticada.
 *
 * Encadeia a partir de CT-14 de propósito, em vez de usar fixture própria: é o que prova que o
 * convite deixado lá continua aceitável, e não apenas que existe um convite qualquer que a tela
 * aceita. Sem este caso a correção do RQ-06 viraria "o convite some".
 */
it('[CT-15] aceita na tela autenticada o convite que sobrou da volta do provedor', function (): void {
    $user  = quemJaTemConta($this->globex);
    $token = ofertaPara('ja.tem@example.com', $this->acme)->enviar();

    Socialite::fake('google', usuarioSocialFalso(ProvedorSocial::Google, [], ['id' => 'sub-1', 'email' => 'ja.tem@example.com']));

    $this->withSession(['login_social.contexto' => ['token' => $token]])
        ->get('/auth/google/callback')
        ->assertRedirect();

    $convite = Convite::valido($token);

    expect($convite)->not->toBeNull();

    noPainelDa($this->globex);
    $this->actingAs($user);

    Livewire::test(ConvitesRecebidos::class)
        ->loadTable()
        ->callAction(TestAction::make('aceitar')->table($convite));

    expect($convite->fresh()->aceito_em)->not->toBeNull()
        ->and($user->fresh()->tenants->pluck('slug')->all())->toContain('acme')
        ->and(papelNaOrganizacaoExiste($user, $this->acme, 'panel_user'))->toBeTrue();
})->group('kit');

/**
 * CT-25 — o link de confirmação de vínculo também não consome o convite.
 *
 * É o TERCEIRO ramo de autenticação de conta existente, e ele enxerga o mesmo contexto de
 * sessão: tirar o aceite do retorno do provedor e deixá-lo aqui reabriria o furo inteiro pelo
 * modo estrito.
 */
it('[CT-25] nao consome o convite pelo link de confirmacao de vinculo', function (): void {
    config()->set('kit.login.vinculo_confirmar', true);

    $user  = quemJaTemConta($this->globex);
    $token = ofertaPara('ja.tem@example.com', $this->acme)->enviar();

    Socialite::fake('google', usuarioSocialFalso(ProvedorSocial::Google, [], ['id' => 'sub-1', 'email' => 'ja.tem@example.com']));

    $this->withSession(['login_social.contexto' => ['token' => $token]])
        ->get('/auth/google/callback')
        ->assertRedirect(Filament::getPanel('app')->getLoginUrl());

    $this->assertGuest();

    $url = null;
    Notification::assertSentTo($user, ConfirmarVinculoSocial::class, function (ConfirmarVinculoSocial $n) use (&$url): bool {
        $url = $n->url;

        return true;
    });

    $this->get((string) $url)->assertRedirect();

    $this->assertAuthenticatedAs($user);

    expect(VinculoSocial::de(ProvedorSocial::Google, 'sub-1')?->user_id)->toBe($user->id)
        ->and(Convite::valido($token))->not->toBeNull()
        ->and($user->fresh()->tenants->pluck('slug')->all())->not->toContain('acme')
        ->and(papelNaOrganizacaoExiste($user, $this->acme, 'panel_user'))->toBeFalse();
})->group('kit');

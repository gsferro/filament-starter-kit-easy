<?php

use App\Models\User;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as UsuarioDoGoogle;

/**
 * Login social com a multi-tenancy LIGADA.
 *
 * Achado do quality gate (QA-02): `tests/Kit/LoginSocialGoogleTest.php` cobre a feature inteira,
 * e cobre em SINGLE-TENANT. Todo o ramo `hasTenancy()` do controller — o destino de quem entra e
 * o destino de quem se registra — nunca era executado por caso nenhum, apesar de a tenancy ser
 * opt-in do kit e de o `.env` deste worktree nascer com ela ligada.
 *
 * O que este arquivo cobre, e que o de Kit não pode cobrir:
 *
 * - a tela de login continua acessível e com o botão: a rota de login NÃO é escopada por tenant,
 *   e se fosse, ligar a tenancy tiraria o login social do ar sem nada acusar;
 * - quem já tem conta E organização cai no painel DA organização (a URL leva o slug);
 * - quem entra sem nenhuma organização não estoura — cai no painel, que é quem sabe o que fazer
 *   com quem não tem organização. Este é o caso que um `route('...meu-perfil')` sem guarda
 *   derrubaria com `UrlGenerationException`, ou seja, 500 no callback.
 *
 * Suíte `Tenancy` e não `Kit` porque o modo tem de estar decidido antes das migrations —
 * `Tests\TenancyTestCase` fixa `permission.teams` em `createApplication()`, e ligar a flag num
 * `beforeEach` é tarde demais (`.ai/rules/testes.md`).
 */
function ligarLoginComGoogleTenancy(): void
{
    config()->set('kit.login.google.habilitado', true);

    config()->set('services.google', [
        'client_id'     => 'id-de-teste',
        'client_secret' => 'segredo-de-teste',
        'redirect'      => '/auth/google/callback',
    ]);
}

/**
 * A tela de login do `/app` com tenancy ligada não é escopada por organização — e o botão
 * continua lá.
 *
 * O oráculo é o botão, não o status: `assertOk()` sozinho ficaria verde com a tela renderizando
 * sem o render hook.
 */
it('mostra o botão do Google na tela de login mesmo com a tenancy ligada', function (): void {
    ligarLoginComGoogleTenancy();

    $this->get('/app/login')
        ->assertOk()
        ->assertSee('Entrar com Google')
        ->assertSee('/auth/google/redirect', escape: false);
})->group('kit');

/**
 * Quem já tem conta e organização entra e cai no painel DA organização.
 *
 * A âncora é o slug na URL de destino: sem ele, o destino seria `/app` cru, e o Filament mandaria
 * a pessoa escolher organização — funciona, mas não é o comportamento de quem já tem uma.
 */
it('leva quem já tem conta e organização para o painel da organização', function (): void {
    ligarLoginComGoogleTenancy();

    $organizacao = tenant('Acme', 'acme');
    $user        = usuario('ja.tem@example.com');
    $user->tenants()->attach($organizacao);

    Socialite::fake('google', UsuarioDoGoogle::fake([
        'id'             => 'google-sub-123',
        'name'           => 'Pessoa do Google',
        'email'          => 'ja.tem@example.com',
        'email_verified' => true,
    ]));

    $resposta = $this->get('/auth/google/callback');

    $resposta->assertStatus(302);

    $this->assertAuthenticatedAs($user);

    expect($resposta->headers->get('Location'))->toContain('acme');
})->group('kit');

/**
 * E quem entra sem nenhuma organização NÃO estoura.
 *
 * É o caso que o `Route::has()` e o guarda de `hasTenancy()` do `urlDoPerfil()` existem para
 * cobrir: com a tenancy ligada, a rota do perfil exige o slug de uma organização, e uma conta
 * recém-criada por login social não pertence a nenhuma. Sem o guarda, `route()` lança
 * `UrlGenerationException` e o callback responde 500 — no exato caminho em que a pessoa acabou
 * de se cadastrar.
 *
 * O registro aberto é forçado aqui porque é o único jeito de alcançar o ramo de criação; a chave
 * é da branch de registro e aprovação e nasce ausente (ADR-02).
 *
 * Duas asserções, e a segunda é a que importa: não é 500, e a conta existe.
 */
it('não estoura quando a conta criada por login social não tem organização', function (): void {
    ligarLoginComGoogleTenancy();
    config()->set('kit.registro.aberto', true);

    Socialite::fake('google', UsuarioDoGoogle::fake([
        'id'             => 'google-sub-456',
        'name'           => 'Pessoa Nova',
        'email'          => 'novo@example.com',
        'email_verified' => true,
    ]));

    $this->get('/auth/google/callback')->assertStatus(302);

    $novo = User::query()->where('email', 'novo@example.com')->first();

    expect($novo)->not->toBeNull()
        ->and($novo->name)->toBe('Pessoa Nova');

    $this->assertAuthenticatedAs($novo);
})->group('kit');

/**
 * A barreira do convite vale igual com a tenancy ligada.
 *
 * Não é redundante com o caso de Kit: a query de `contaCom()` roda com `permission.teams` ligado
 * e com o `PermissionRegistrar` carimbado no contexto global, e é aí que escopo global de model
 * costuma mudar o resultado de uma busca sem ninguém perceber.
 */
it('recusa quem não tem conta com a tenancy ligada e o registro fechado', function (): void {
    ligarLoginComGoogleTenancy();

    Socialite::fake('google', UsuarioDoGoogle::fake([
        'id'             => 'google-sub-789',
        'name'           => 'De Fora',
        'email'          => 'de.fora@example.com',
        'email_verified' => true,
    ]));

    $this->get('/auth/google/callback')->assertRedirectContains('/app/login');

    $this->assertGuest();

    expect(User::query()->where('email', 'de.fora@example.com')->exists())->toBeFalse();
})->group('kit');

<?php

use App\Models\VinculoSocial;
use App\Notifications\ConfirmarVinculoSocial;
use App\Notifications\PrimeiroAcessoSocial;
use App\Support\ProvedorSocial;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Laravel\Socialite\Socialite;
use Tests\TestCase;

/**
 * Login social recusando contas inativas e excluídas — R5 da wiki
 * `status-e-exclusao-logica-de-usuario`.
 *
 * Cobre os cinco cenários CT-13..CT-17. Tudo usa o Google como representante: a lógica de
 * indisponibilidade mora no controller e não no driver.
 */
beforeEach(function (): void {
    Http::preventStrayRequests();
    Notification::fake();
    config()->set('kit.login.rodape', null);
    Carbon::setTestNow('2026-08-12 10:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

/**
 * Abre o callback do Google com um e-mail/sub específicos e devolve a resposta.
 */
function callbackDoGoogle(TestCase $teste, string $email, string $sub = 'sub-1'): TestResponse
{
    ligarProvedor(ProvedorSocial::Google);

    Socialite::fake(ProvedorSocial::Google->value, usuarioSocialFalso(
        ProvedorSocial::Google,
        [],
        ['id' => $sub, 'email' => $email],
    ));

    return $teste->get('/auth/google/callback');
}

/** CT-13 — inativo sem vínculo cai no aviso, sem criar vínculo e sem enviar notificação. */
it('[CT-13] inativo sem vínculo cai no aviso e não cria vínculo', function (): void {
    $user = usuario('ja.tem@example.com');
    $user->forceFill(['ativo' => false])->save();

    $resposta = callbackDoGoogle($this, 'ja.tem@example.com', 'sub-1');

    $resposta->assertRedirect(route('auth.conta-indisponivel'));
    $this->assertGuest();
    expect(VinculoSocial::de(ProvedorSocial::Google, 'sub-1'))->toBeNull();
    Notification::assertNotSentTo($user, PrimeiroAcessoSocial::class);
    Notification::assertNotSentTo($user, ConfirmarVinculoSocial::class);
})->group('kit');

/** CT-14 — inativo já vinculado é recusado pelo ramo do vínculo sem registrar acesso. */
it('[CT-14] inativo vinculado é recusado sem registrar acesso', function (): void {
    $user = usuario('Ja.Tem@Example.com');
    $user->forceFill(['ativo' => false])->save();
    $vinculo = VinculoSocial::vincular($user, ProvedorSocial::Google, 'sub-1');
    $antes   = $vinculo->fresh()->ultimo_acesso_em;

    $resposta = callbackDoGoogle($this, 'ja.tem@example.com', 'sub-1');

    $resposta->assertRedirect(route('auth.conta-indisponivel'));
    $this->assertGuest();
    expect($vinculo->fresh()->ultimo_acesso_em->equalTo($antes))->toBeTrue('registrarAcesso() foi chamado e mudou ultimo_acesso_em.');
})->group('kit');

/** CT-15 — excluído volta do provedor e vê a data da exclusão. */
it('[CT-15] excluído volta do provedor e vê a data da exclusão', function (): void {
    $user = usuario('ja.tem@example.com');
    $user->delete();

    $resposta = callbackDoGoogle($this, 'ja.tem@example.com', 'sub-1');

    $resposta->assertRedirect(route('auth.conta-indisponivel'));

    $this->get(route('auth.conta-indisponivel'))
        ->assertStatus(403)
        ->assertSee('Conta excluída')
        ->assertSee('12/08/2026')
        ->assertSee('restaurar');

    $this->assertGuest();
    expect(VinculoSocial::de(ProvedorSocial::Google, 'sub-1'))->toBeNull();
})->group('kit');

/** CT-16 — link de confirmação do modo estrito não entra numa conta inativa. */
it('[CT-16] link de confirmação não entra em conta inativa', function (): void {
    config()->set('kit.login.vinculo_confirmar', true);
    ligarProvedor(ProvedorSocial::Google);

    $user = usuario('ja.tem@example.com');
    $user->forceFill(['ativo' => false])->save();

    $url = URL::temporarySignedRoute('auth.social.confirmar', now()->addMinutes(30), [
        'provedor' => ProvedorSocial::Google->value,
        'user'     => $user->getKey(),
        'sub'      => 'sub-1',
    ]);

    $this->get($url)->assertRedirect(route('auth.conta-indisponivel'));
    $this->assertGuest();
    expect(VinculoSocial::de(ProvedorSocial::Google, 'sub-1'))->toBeNull();
})->group('kit');

/** CT-17 — a recusa social fica no log e vira uma linha de falha na trilha. */
it('[CT-17] recusa social vai para o log e para a trilha', function (): void {
    $canal = espiarAutenticacao();
    $user  = usuario('ja.tem@example.com');
    $user->forceFill(['ativo' => false])->save();

    callbackDoGoogle($this, 'ja.tem@example.com', 'sub-1');

    $canal->shouldHaveReceived('warning')->once();

    $this->assertDatabaseHas('authentication_log', [
        'authenticatable_id'   => $user->getKey(),
        'authenticatable_type' => $user->getMorphClass(),
        'login_successful'     => false,
    ]);
})->group('kit');

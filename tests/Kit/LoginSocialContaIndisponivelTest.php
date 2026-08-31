<?php

use App\Models\Convite;
use App\Models\VinculoSocial;
use App\Notifications\ConfirmarVinculoSocial;
use App\Notifications\PrimeiroAcessoSocial;
use App\Support\ProvedorSocial;
use Carbon\Carbon;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
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

/**
 * CT-18 — a conta indisponível não QUEIMA o convite.
 *
 * O oráculo que faltava nos CT-13..CT-15 acima: eles recusam a sessão e não afirmam nada sobre
 * o convite, e é exatamente por baixo deles que o defeito F-03 atravessava — o convite era
 * consumido ANTES da barreira de indisponibilidade, então uma conta desativada queimava o
 * convite sem entrar.
 *
 * A linha `ativa` é o que impede o caso de virar tautologia: sem ela, uma implementação que
 * nunca consumisse convite em caso algum passaria em todas as linhas. Ela é também o que
 * separa R6 de R5 — nas duas o convite fica pendente, mas aqui a sessão NÃO abre.
 *
 * `Convite::valido()` de volta não-nulo é o oráculo compacto das quatro metades: `aceito_em`
 * vazio, sem marca de recusa, dentro da validade e com o token intacto.
 */
it('[CT-18] nao queima o convite quando a conta esta indisponível', function (string $estado): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $user = usuario('ja.tem@example.com');

    match ($estado) {
        'ativa'     => null,
        'inativa'   => $user->forceFill(['ativo' => false])->save(),
        'excluída'  => $user->delete(),
        'pendente'  => $user->forceFill(['aprovacao_pendente' => true])->save(),
    };

    $convite  = ofertaPara('ja.tem@example.com');
    $token    = $convite->enviar();
    $lembrete = $convite->fresh()->token_lembrete;

    $this->withSession(['login_social.contexto' => ['token' => $token]]);

    callbackDoGoogle($this, 'ja.tem@example.com', 'sub-1');

    $estado === 'ativa'
        ? $this->assertAuthenticatedAs($user)
        : $this->assertGuest();

    expect(Convite::valido($token))->not->toBeNull()
        ->and($convite->fresh()->aceito_em)->toBeNull()
        ->and($convite->fresh()->token_lembrete)->toBe($lembrete);
})->with([
    'ativa (célula de controle)' => ['ativa'],
    'desativada'                 => ['inativa'],
    'excluída logicamente'       => ['excluída'],
    'pendente de aprovação'      => ['pendente'],
])->group('kit');

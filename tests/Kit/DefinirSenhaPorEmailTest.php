<?php

use App\Livewire\DefinirSenhaPorEmail;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
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

/**
 * ===========================================================================
 * R9 (Adendo 1, RQ-08) — wikis/specs/feat/phpstan-nivel-8
 * ===========================================================================
 *
 * Ambiguidade do 04 resolvida (não é sobre o Então, é sobre o arnês): `painelRegistradoEmTeste()`
 * só registra o painel no `PanelRegistry` — ele nunca passa pelo boot real das 3 PanelProviders,
 * então nenhuma rota Laravel dele existe (`Route::has('filament.financeiro.auth.password-reset.
 * reset')` é falso, medido em tinker). `DefinirSenhaPorEmail::enviar()` chama
 * `Filament::getResetPasswordUrl()` (dentro do callback do `Password::broker()->sendResetLink()`)
 * ANTES de chegar no redirect que o CT-23 mede — sem a rota, o teste morre com
 * `RouteNotFoundException`, um jeito de vermelho que não tem nada a ver com o RQ-08 e mascararia
 * o resultado. `registrarRotaDeResetSenhaSemLogin()` (abaixo, só usada aqui) supre exatamente essa
 * rota, e só ela — nenhum outro comportamento do painel muda.
 *
 * CT-23 — `enviar()` lê `(Filament::getCurrentPanel() ?? Filament::getDefaultPanel())->getLoginUrl()`
 * SEM tratar o nulo (`app/Livewire/DefinirSenhaPorEmail.php`) antes de `$this->redirect($loginUrl)`.
 * No painel `financeiro` (`painelRegistradoEmTeste()`, sem `->login()`) esse `getLoginUrl()` é
 * null — a mesma premissa P-02 de CT-10/CT-11 — e o `redirect(null)` congela a tela COM a sessão
 * já encerrada (RD-02): o 3º `Então` (link enviado) e o 2º (sessão encerrada) já aconteceram
 * quando o 1º (redirect para um destino não nulo) falha. É por isso que as três asserções vêm
 * juntas: um caso que só olhasse o redirect aceitaria "redireciona sem encerrar", e um que só
 * olhasse a sessão aceitaria o `redirect(null)`.
 *
 * A linha `financeiro` é esperada VERMELHA nesta entrega (RQ-09/RQ-08 ainda não implementados no
 * código de app — só a wiki e os testes chegaram até aqui): `assertRedirect('/financeiro')` não
 * bate com o destino nulo que o código produz hoje.
 */
function registrarRotaDeResetSenhaSemLogin(string $painel): void
{
    Route::get("/{$painel}/password-reset/reset", fn () => null)
        ->name("filament.{$painel}.auth.password-reset.reset");

    // Rota acrescentada DEPOIS do boot da aplicação: sem o refresh manual da tabela de
    // nomes ela fica invisível para `route()`/`URL::signedRoute()` (medido em tinker —
    // `Route::has()` só enxerga o nome com este refresh).
    Route::getRoutes()->refreshNameLookups();
}

it('[CT-23] definir senha por e-mail num painel sem login encerra a sessão e leva à raiz do painel', function (string $painel, string $destino): void {
    if ($painel === 'financeiro') {
        painelRegistradoEmTeste('financeiro');
        registrarRotaDeResetSenhaSemLogin('financeiro');
    }

    Notification::fake();

    $ana = usuarioDoKit('panel_user', 'ana@example.com');
    $this->actingAs($ana);
    noPainelBootado($painel);

    Livewire::test(DefinirSenhaPorEmail::class)
        ->call('enviar')
        ->assertRedirect($destino);

    $this->assertGuest();

    Notification::assertSentToTimes($ana, ResetPasswordNotification::class, 1);
})->with([
    'admin, com login (fato)'                     => ['admin', '/admin/login'],
    'financeiro, sem login (premissa P-02, fixa)' => ['financeiro', '/financeiro'],
])->group('kit');

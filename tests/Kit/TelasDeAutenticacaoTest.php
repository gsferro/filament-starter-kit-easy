<?php

use App\Filament\Pages\Auth\TelaDoisFatores;
use App\Models\Convite;
use Caresome\FilamentAuthDesigner\AuthDesignerConfigRepository;
use Caresome\FilamentAuthDesigner\Enums\MediaPosition;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

/**
 * O guarda-roupa do Auth Designer — todas as telas de autenticação do kit, públicas e não.
 *
 * O que se afirma aqui é o EIXO do split, que é o que o usuário enxerga: a classe
 * `media-left` põe a arte à esquerda e o formulário à direita (`flex-direction: row`);
 * `media-right` inverte os dois (`row-reverse`). São as duas classes que a CSS do pacote
 * consome, então elas são o oráculo honesto — afirmar "a tela abriu" não distingue layout
 * nenhum.
 *
 * `fi-auth-layout` sozinho também não distingue o suficiente: o blade do pacote emite a
 * classe SEMPRE, e mídia e eixo saem de `$config->hasMedia()`/`$config->position`
 * (`vendor/caresome/filament-auth-designer/resources/views/components/layouts/auth.blade.php:7-9,28`).
 * Chave de configuração não registrada não estoura — o repositório devolve um
 * `AuthPageConfig` vazio e a tela sai vestida e VAZIA, com `no-media`. Daí a asserção de
 * mídia ao lado da de layout.
 *
 * Login à esquerda; tudo o que NÃO é login vai espelhado à direita — recuperação de senha,
 * aceite de convite e confirmação de e-mail. É o sinal de que se saiu do login, sem trocar
 * cor nem marca.
 *
 * O desafio de 2FA é a exceção proposital: ele é a SEGUNDA ETAPA do login, então usa a chave
 * `login` e sai com a arte do mesmo lado. Ver ADR-02 da wiki `auth-designer-telas`.
 */
it('abre o login com a arte à esquerda e o formulário à direita', function (string $painel): void {
    $this->get("/{$painel}/login")
        ->assertOk()
        ->assertSee('fi-auth-layout', escape: false)
        ->assertSee('media-left', escape: false)
        ->assertDontSee('media-right', escape: false);
})->with(['app', 'admin', 'infra'])->group('kit');

it('espelha a recuperação de senha: arte à direita, formulário à esquerda', function (string $painel): void {
    $this->get("/{$painel}/password-reset/request")
        ->assertOk()
        ->assertSee('fi-auth-layout', escape: false)
        ->assertSee('media-right', escape: false);
})->with(['app', 'admin', 'infra'])->group('kit');

/**
 * A tela de redefinição (a que o link do e-mail abre) usa a MESMA configuração do
 * pedido: o plugin registra as duas páginas com uma chave só (`password-reset`).
 * Se alguém configurar só uma das duas, esta é a que denuncia.
 *
 * A URL precisa ser ASSINADA: a rota de redefinição do Filament roda atrás do
 * `signed`, e montá-la à mão devolve 403 — o que reprovaria o caso por um motivo
 * que não é o dele.
 */
it('mantém o espelho também na tela de redefinição', function (string $painel): void {
    $url = URL::signedRoute("filament.{$painel}.auth.password-reset.reset", [
        'email' => 'alguem@example.com',
        'token' => 'token-de-teste',
    ]);

    $this->get($url)
        ->assertOk()
        ->assertSee('media-right', escape: false);
})->with(['app', 'admin', 'infra'])->group('kit');

/**
 * CT-01 — o desafio de 2FA vestido, nos TRÊS painéis.
 *
 * Exaustivo e não amostrado de propósito: a configuração do Auth Designer é copiada à mão em
 * três providers, e o defeito histórico do kit nessa área é configurar um painel e esquecer
 * os outros dois.
 *
 * Sem arranjo de 2FA: a rota do Breezy roda sob `$panel->getMiddleware()`, não sob o
 * `authMiddleware` (`vendor/jeffgreco13/filament-breezy/routes/web.php:15`), e
 * `hasValidTwoFactorSession()` devolve `false` para quem não tem `breezySession`
 * (`vendor/jeffgreco13/filament-breezy/src/Traits/TwoFactorAuthenticatable.php:70-73`) —
 * então qualquer autenticado renderiza a tela.
 */
it('veste o desafio de 2FA com o layout de autenticação nos três painéis', function (string $painel): void {
    $this->actingAs(usuario());

    $this->get("/{$painel}/two-factor-authentication")
        ->assertOk()
        ->assertSee('fi-auth-layout', escape: false)
        ->assertDontSee('fi-simple-layout', escape: false);
})->with(['app', 'admin', 'infra'])->group('kit');

/**
 * CT-02 — e com a MESMA arte do login, do mesmo lado.
 *
 * O contrapeso do caso acima: `fi-auth-layout` fica verde com a tela vestida e vazia, que é
 * o que uma chave de configuração errada produz. Aqui o oráculo é a mídia e o eixo.
 */
it('herda a arte e o eixo do login no desafio de 2FA', function (): void {
    $this->actingAs(usuario());

    $this->get('/admin/two-factor-authentication')
        ->assertOk()
        ->assertSee('has-media', escape: false)
        ->assertDontSee('no-media', escape: false)
        ->assertSee('media-left', escape: false);
})->group('kit');

/**
 * CT-03 — a linha de base do par: página comum de painel, no mesmo processo, sem nenhuma
 * tela de autenticação aberta antes. Sem ela o CT-04 não distingue "não vazou" de "nunca
 * teve".
 */
it('não veste a página comum do painel com o layout de autenticação', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    $this->actingAs(usuarioDoKit('master_global'));

    $this->get('/admin')
        ->assertOk()
        ->assertDontSee('fi-auth-layout', escape: false);
})->group('kit');

/**
 * CT-04 — o caso que mata o vazamento.
 *
 * `HasAuthDesignerLayout::boot()` faz `static::$layout = ...`
 * (`vendor/caresome/filament-auth-designer/src/Concerns/HasAuthDesignerLayout.php:14`). Sem a
 * redeclaração da propriedade em `TelaDoisFatores`, a atribuição cai no estático de
 * `Filament\Pages\SimplePage` (`vendor/filament/filament/src/Pages/SimplePage.php:12`) e a
 * primeira renderização do 2FA passa a vestir o layout de login em toda página simples do
 * processo. CT-01 fica VERDE com esse defeito presente — só este caso o denuncia.
 *
 * É o par que `.ai/rules/auth.md` cobra, e o mesmo molde de
 * `tests/Kit/BloqueioDeSessaoTest.php:93-109`.
 */
it('não vaza o layout de autenticação do 2FA para as outras páginas do painel', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    $this->actingAs(usuarioDoKit('master_global'));

    $this->get('/admin/two-factor-authentication')
        ->assertOk()
        ->assertSee('fi-auth-layout', escape: false);

    $this->get('/admin')
        ->assertOk()
        ->assertDontSee('fi-auth-layout', escape: false);
})->group('kit');

/**
 * CT-05 — vestir a tela não pode quebrar o que ela faz.
 *
 * Uma tela de 2FA que abre e não autentica é o mesmo defeito de "tela aberta não é tela que
 * grava" (`.ai/rules/testes.md`). E este caso cobre uma segunda coisa: o alias Livewire
 * `two-factor-page` que o Breezy registra aponta para a classe DELE
 * (`vendor/jeffgreco13/filament-breezy/src/FilamentBreezyServiceProvider.php:40`) — a nossa
 * subclasse depende da resolução automática do registry, e é aqui que isso é exercido.
 *
 * `noPainelBootado()` é obrigatório: `enableTwoFactorAuthentication()` do model resolve
 * `filament('filament-breezy')`, e `BreezySession::booted()` grava `panel_id` a partir do
 * painel corrente (`vendor/jeffgreco13/filament-breezy/src/Models/BreezySession.php:23-31`).
 */
it('recusa código errado no desafio de 2FA sem abrir a sessão', function (): void {
    noPainelBootado('admin');

    $user = usuario();
    $this->actingAs($user);
    $user->enableTwoFactorAuthentication();

    Livewire::test(TelaDoisFatores::class)
        ->set('data.code', '000000')
        ->call('authenticate')
        ->assertHasErrors('data.code');

    expect(session()->has('breezy_session_id'))->toBeFalse();
})->group('kit');

/**
 * CT-06 — a célula válida da tabela estado × operação.
 *
 * Código de RECUPERAÇÃO e não TOTP de propósito: `verifyRecoveryCode()` é `hash_equals`
 * contra o que está gravado
 * (`vendor/jeffgreco13/filament-breezy/src/Concerns/Plugin/HasTwoFactorAuthentication.php:104-114`),
 * então o caso é determinístico e não depende de janela de tempo.
 *
 * As duas asserções: a sessão de 2FA abre (`breezy_session_id` = md5 do id da sessão do
 * Breezy, `BreezySession.php:47-50`) e o código é CONSUMIDO — âncora no agregado, a lista de
 * códigos do usuário.
 */
it('abre a sessão de 2FA com código de recuperação e consome o código', function (): void {
    noPainelBootado('admin');

    $user = usuario();
    $this->actingAs($user);
    $user->enableTwoFactorAuthentication();

    $codigo = $user->breezySession->two_factor_recovery_codes[0];

    Livewire::test(TelaDoisFatores::class)
        ->call('toggleRecoveryCode')
        ->set('data.code', $codigo)
        ->call('authenticate')
        ->assertHasNoErrors();

    expect(session('breezy_session_id'))->toBe(md5((string) $user->breezySession->id))
        ->and($user->fresh()->breezySession->two_factor_recovery_codes)->not->toContain($codigo);
})->group('kit');

/**
 * CT-09 — o aceite de convite espelhado.
 *
 * Precisa de convite VÁLIDO na query string: sem ele `RegistroPorConvite::recusar()` manda
 * para o login (`app/Filament/Pages/Auth/RegistroPorConvite.php:200-255`) e o caso mediria a
 * tela de login — ficando verde com o eixo errado.
 *
 * O convite é montado inline, e não por helper: `.ai/rules/testes.md` manda que helper usado
 * por mais de um arquivo viva em `tests/Pest.php`, e quatro linhas não justificam mover o
 * `conviteCom()` de `tests/Kit/ConviteTest.php`.
 */
it('espelha o aceite de convite: arte à direita, formulário à esquerda', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    Notification::fake();

    $convite = Convite::factory()->create(['email' => 'convidado@example.com']);
    $token   = $convite->enviar();

    $this->get("/app/register?token={$token}")
        ->assertOk()
        ->assertSee('fi-auth-layout', escape: false)
        ->assertSee('media-right', escape: false)
        ->assertDontSee('media-left', escape: false);
})->group('kit');

/**
 * CT-07 — a confirmação de e-mail JÁ ESTÁ VESTIDA, com a rota desligada.
 *
 * A afirmação do requisito é "caso use, já esta implemetado o auth designer nela também", e
 * é isso que se mede aqui: a chave `email-verification` do `AuthDesignerConfigRepository`,
 * com mídia, eixo espelhado e alternador de tema. Quem grava é o `boot()` do plugin
 * (`vendor/caresome/filament-auth-designer/src/AuthDesignerPlugin.php:99-101`), e a gravação
 * NÃO depende de a rota existir — daí o oráculo ser o repositório.
 *
 * Por que não um `$this->get()` da tela: MEDIDO, ela responde 500.
 * `EmailVerificationPrompt::getVerifiable()` declara retorno `MustVerifyEmail`
 * (`vendor/filament/filament/src/Auth/Pages/EmailVerification/EmailVerificationPrompt.php:36-43`)
 * e é chamada no `mount()` (`:31`); `App\Models\User` não implementa a interface. Ligar a
 * verificação de e-mail de verdade está FORA deste requisito — ver ADR-03 da wiki
 * `auth-designer-telas`.
 *
 * `noPainelBootado()` é obrigatório: o repositório é preenchido no `boot()` do plugin, e
 * teste que só faz `setCurrentPanel()` não passa por lá (`.ai/rules/testes.md`).
 */
it('deixa a confirmação de e-mail já vestida pelo auth designer nos três painéis', function (string $painel): void {
    noPainelBootado($painel);

    $repositorio = app(AuthDesignerConfigRepository::class);

    expect($repositorio->hasPageConfig('email-verification', $painel))->toBeTrue();

    $config = $repositorio->getConfig('email-verification', $painel);

    expect($config->hasMedia())->toBeTrue()
        ->and($config->position)->toBe(MediaPosition::Right)
        ->and($config->showThemeSwitcher)->toBeTrue();
})->with(['app', 'admin', 'infra'])->group('kit');

/**
 * CT-08 — e a tela NÃO entra no ar, em nenhum painel.
 *
 * O plugin chama `$panel->emailVerification($classe)` com um argumento só
 * (`AuthDesignerPlugin.php:45-47`), e o segundo parâmetro do Filament é
 * `bool $isRequired = true` (`vendor/filament/filament/src/Panel/Concerns/HasAuth.php:110`).
 * Quem desfaz é a chamada com `null` DEPOIS do `->plugins([...])`, porque `Panel::plugin()`
 * registra na hora (`vendor/filament/filament/src/Panel/Concerns/HasPlugins.php:15-21`).
 *
 * Três asserções, nenhuma redundante: a exigência desarmada, a rota inexistente (é ela que
 * denuncia se alguém devolver a classe ao primeiro parâmetro) e — em CT-07 — a configuração
 * preservada. Sem a terceira, apagar o bloco inteiro deixaria estes dois casos verdes.
 */
it('não põe a confirmação de e-mail no ar em nenhum painel', function (string $painel): void {
    $panel = Filament::getPanel($painel);

    expect($panel->isEmailVerificationRequired())->toBeFalse()
        ->and($panel->hasEmailVerification())->toBeFalse()
        ->and(Route::has("filament.{$painel}.auth.email-verification.prompt"))->toBeFalse();
})->with(['app', 'admin', 'infra'])->group('kit');

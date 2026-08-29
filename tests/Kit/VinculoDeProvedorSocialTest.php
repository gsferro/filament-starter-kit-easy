<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use App\Models\User;
use App\Models\VinculoSocial;
use App\Notifications\ConfirmarVinculoSocial;
use App\Notifications\PrimeiroAcessoSocial;
use App\Settings\ConfiguracoesDoKit as SettingsDoKit;
use App\Support\ConfiguracaoDoLogin;
use App\Support\ProvedorSocial;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Socialite;
use Livewire\Livewire;

/**
 * O vínculo entre a conta e a identidade no provedor — `wikis/specs/feat/vinculo-de-provedor-social/`.
 *
 * Os IDs de CT são os do `04-casos-de-teste.md` daquela wiki. Os helpers (`ligarProvedor`,
 * `usuarioSocialFalso`, `usuario`) são os de `tests/Pest.php`; o `id` do usuário falso é o `sub`.
 */
beforeEach(function (): void {
    Notification::fake();
    ligarProvedor(ProvedorSocial::Google);
});

/** CT-V01 — primeira entrada em conta existente: vincula, entra, avisa. */
it('vincula, entra e avisa por e-mail na primeira entrada de um provedor em conta existente', function (): void {
    $user = usuario('ja.tem@example.com');

    Socialite::fake('google', usuarioSocialFalso(ProvedorSocial::Google, [], ['id' => 'sub-1', 'email' => $user->email]));

    $this->get('/auth/google/callback')->assertRedirect();

    $this->assertAuthenticatedAs($user);

    $vinculo = VinculoSocial::de(ProvedorSocial::Google, 'sub-1');

    expect($vinculo)->not->toBeNull()
        ->and($vinculo->user_id)->toBe($user->id)
        ->and($vinculo->confirmado_em)->not->toBeNull();

    Notification::assertSentTo($user, PrimeiroAcessoSocial::class, fn (PrimeiroAcessoSocial $n): bool => $n->provedor === ProvedorSocial::Google);
})->group('kit');

/** CT-V02 — a segunda entrada não avisa de novo e registra o acesso. */
it('nao avisa de novo na segunda entrada e registra o ultimo acesso', function (): void {
    $user = usuario('ja.tem@example.com');
    VinculoSocial::vincular($user, ProvedorSocial::Google, 'sub-1');

    Socialite::fake('google', usuarioSocialFalso(ProvedorSocial::Google, [], ['id' => 'sub-1', 'email' => $user->email]));

    $this->get('/auth/google/callback')->assertRedirect();

    $this->assertAuthenticatedAs($user);
    Notification::assertNothingSent();

    expect(VinculoSocial::de(ProvedorSocial::Google, 'sub-1')?->ultimo_acesso_em)->not->toBeNull()
        ->and(VinculoSocial::query()->count())->toBe(1);
})->group('kit');

/**
 * CT-V03 — o vínculo vence o e-mail: mesma identidade no provedor, e-mail diferente, mesma conta.
 *
 * É o caso do endereço reciclado (ADR-02). Sem a consulta ao vínculo, o e-mail novo levaria a
 * OUTRA conta — a `outra`, criada aqui de propósito com o e-mail que o provedor passou a devolver.
 */
it('reconhece a conta pelo vinculo mesmo com o e-mail trocado no provedor', function (): void {
    $dona  = usuario('dona@example.com');
    $outra = usuario('reciclado@example.com');
    VinculoSocial::vincular($dona, ProvedorSocial::Google, 'sub-1');

    Socialite::fake('google', usuarioSocialFalso(ProvedorSocial::Google, [], ['id' => 'sub-1', 'email' => 'reciclado@example.com']));

    $this->get('/auth/google/callback')->assertRedirect();

    $this->assertAuthenticatedAs($dona);

    expect($dona->fresh()->email)->toBe('dona@example.com')
        ->and(VinculoSocial::query()->where('user_id', $outra->id)->exists())->toBeFalse();
})->group('kit');

/** CT-V04 — modo estrito: não entra, manda o link assinado, e o link conclui. */
it('no modo estrito exige o link do e-mail antes de entrar, e o link vincula e entra', function (): void {
    config()->set('kit.login.vinculo_confirmar', true);
    $user = usuario('ja.tem@example.com');

    Socialite::fake('google', usuarioSocialFalso(ProvedorSocial::Google, [], ['id' => 'sub-1', 'email' => $user->email]));

    $this->get('/auth/google/callback')->assertRedirect(Filament::getPanel('app')->getLoginUrl());

    $this->assertGuest();
    expect(VinculoSocial::de(ProvedorSocial::Google, 'sub-1'))->toBeNull();
    Notification::assertNotSentTo($user, PrimeiroAcessoSocial::class);

    $url = null;
    Notification::assertSentTo($user, ConfirmarVinculoSocial::class, function (ConfirmarVinculoSocial $n) use (&$url): bool {
        $url = $n->url;

        return $n->provedor === ProvedorSocial::Google
            && str_contains($n->url, '/auth/google/confirmar')
            && str_contains($n->url, 'signature=')
            && str_contains($n->url, 'expires=');
    });

    $this->get((string) $url)->assertRedirect();

    $this->assertAuthenticatedAs($user);
    expect(VinculoSocial::de(ProvedorSocial::Google, 'sub-1')?->user_id)->toBe($user->id);
})->group('kit');

/** CT-V05 — o link sem assinatura válida não vincula nem entra. */
it('recusa o link de confirmacao sem assinatura valida', function (): void {
    config()->set('kit.login.vinculo_confirmar', true);
    $user = usuario('ja.tem@example.com');

    $this->get(route('auth.social.confirmar', ['provedor' => 'google', 'user' => $user->id, 'sub' => 'sub-1']))
        ->assertForbidden();

    $this->assertGuest();
    expect(VinculoSocial::query()->count())->toBe(0);
})->group('kit');

/** CT-V06 — conta nova (registro aberto) nasce vinculada, em qualquer modo. */
it('cria a conta nova ja vinculada ao provedor', function (bool $estrito): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    config()->set('kit.registro.habilitado', true);
    config()->set('kit.login.vinculo_confirmar', $estrito);

    Socialite::fake('google', usuarioSocialFalso(ProvedorSocial::Google, [], ['id' => 'sub-novo', 'email' => 'novo@example.com']));

    $this->get('/auth/google/callback')->assertRedirect();

    $novo = User::query()->where('email', 'novo@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($novo);
    expect(VinculoSocial::de(ProvedorSocial::Google, 'sub-novo')?->user_id)->toBe($novo->id);
    // Conta nova não tem "primeira vez" a avisar: não havia conta antes.
    Notification::assertNothingSent();
})->with(['modo padrao' => [false], 'modo estrito' => [true]])->group('kit');

/** CT-V07 — apagar a conta apaga os vínculos. */
it('apaga os vinculos junto com a conta', function (): void {
    $user = usuario('ja.tem@example.com');
    VinculoSocial::vincular($user, ProvedorSocial::Google, 'sub-1');
    VinculoSocial::vincular($user, ProvedorSocial::Github, 'gh-1');

    $user->forceDelete();

    expect(VinculoSocial::query()->count())->toBe(0);
})->group('kit');

/** CT-V08 — o link de confirmação não re-vincula uma identidade que já é de outra conta. */
it('recusa confirmar um vinculo cuja identidade ja pertence a outra conta', function (): void {
    config()->set('kit.login.vinculo_confirmar', true);
    $dona  = usuario('dona@example.com');
    $outra = usuario('outra@example.com');
    VinculoSocial::vincular($dona, ProvedorSocial::Google, 'sub-1');

    $url = URL::temporarySignedRoute('auth.social.confirmar', now()->addMinutes(30), [
        'provedor' => 'google', 'user' => $outra->id, 'sub' => 'sub-1',
    ]);

    $this->get($url)->assertRedirect(Filament::getPanel('app')->getLoginUrl());

    $this->assertGuest();
    expect(VinculoSocial::de(ProvedorSocial::Google, 'sub-1')?->user_id)->toBe($dona->id);
})->group('kit');

/** CT-V09 — conta existente pendente de aprovação não abre sessão nem pelo provedor. */
it('nao abre sessao para conta existente pendente de aprovacao', function (): void {
    $user = usuario('pendente@example.com');
    $user->forceFill(['aprovacao_pendente' => true])->save();

    Socialite::fake('google', usuarioSocialFalso(ProvedorSocial::Google, [], ['id' => 'sub-1', 'email' => $user->email]));

    $this->get('/auth/google/callback')->assertRedirect(Filament::getPanel('app')->getLoginUrl());

    $this->assertGuest();
})->group('kit');

/**
 * CT-V10 — o modo estrito gravado no Settings chega à config e ao ponto único de leitura.
 *
 * Os CT-V04/05/08 acima ligam o modo por `config()->set()`, o que prova o controller mas não a
 * linha do `mapaDeConfiguracao()` — esquecê-la é o defeito silencioso de `.ai/rules/settings.md`:
 * o toggle grava e não governa nada. `alinharConfiguracoesDoKit()` é o boot chamado à mão.
 */
it('leva o modo estrito gravado no settings para a config e para o ponto unico de leitura', function (): void {
    $settings                          = app(SettingsDoKit::class);
    $settings->login_vinculo_confirmar = true;
    $settings->save();

    alinharConfiguracoesDoKit();

    expect(config('kit.login.vinculo_confirmar'))->toBeTrue()
        ->and(ConfiguracaoDoLogin::vinculoExigeConfirmacao())->toBeTrue();
})->group('kit');

/** CT-V11 — o toggle da tela /admin/configuracoes-do-kit grava a propriedade. */
it('grava o modo estrito pelo toggle da tela de configuracoes do kit', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    $this->actingAs(usuarioDoKit('admin'));

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['login_vinculo_confirmar' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    app()->forgetInstance(SettingsDoKit::class);

    expect(app(SettingsDoKit::class)->login_vinculo_confirmar)->toBeTrue()
        ->and(configuracaoGravada('login_vinculo_confirmar'))->toBeTrue();
})->group('kit');

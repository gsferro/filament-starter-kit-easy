<?php

use App\Settings\ConfiguracoesDoKit;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * O widget anti-robô num navegador de verdade — o que HTTP não prova.
 *
 * `tests/Kit/ProtecaoAntiRoboTest.php` já prova que o token é verificado no lado do servidor,
 * que a falha é fechada e que o campo é obrigatório. O que SÓ o navegador prova:
 *
 * 1. **O script externo é carregado e o widget renderiza.** Um `<div>` vazio com atributos
 *    certos passa em qualquer assertiva de DOM mas não renderiza nada se o script não carregar
 *    ou se o `onload` não disparar.
 * 2. **O `wire:ignore` mantém o widget vivo após erro de validação.** Re-render do Livewire
 *    destrói o DOM interno; sem `wire:ignore` o iframe do reCAPTCHA desaparece depois do
 *    primeiro submit — e o campo vira impossível de preencher.
 *
 * Usa as chaves de teste oficiais do reCAPTCHA v2:
 * - site: 6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI (sempre passa)
 * - secreta: 6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe (sempre aceita)
 *
 * Os cenários NÃO clicam no checkbox — as chaves de teste renderizam o widget normalmente
 * (com a caixa), e a asserção é sobre a PRESENÇA visível do iframe, não sobre a interação.
 * A interação (marcar + submeter) é provada pelas chaves de teste do servidor em
 * `tests/Kit/ProtecaoAntiRoboTest.php`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $settings                                = app(ConfiguracoesDoKit::class);
    $settings->login_anti_robo_habilitado    = true;
    $settings->login_anti_robo_provedor      = 'recaptcha';
    $settings->login_anti_robo_chave_do_site = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
    $settings->login_anti_robo_chave_secreta = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';
    $settings->save();
    $settings->aplicarNaConfig();

    // Aquece a compilação dos componentes fora do cronômetro do Playwright.
    $this->get('/app/login');
});

/**
 * CT-B01 — o widget reCAPTCHA renderiza na tela de login do /app.
 *
 * A asserção é sobre o iframe que o reCAPTCHA injeta dentro do container `.fi-fo-anti-robo`.
 * O iframe só existe se o script externo carregou E o `onload` disparou E o `render()` foi
 * chamado com a `sitekey` — qualquer falha na cadeia e o container fica vazio.
 */
it('renderiza o widget recaptcha na tela de login', function (): void {
    visit('/app/login')
        ->assertVisible('.fi-fo-anti-robo')
        ->assertVisible('.fi-fo-anti-robo iframe[title="reCAPTCHA"]')
        ->assertNoJavaScriptErrors();
})->group('browser', 'kit');

/**
 * CT-B02 — o widget reCAPTCHA renderiza na tela de recuperação de senha do /app.
 */
it('renderiza o widget recaptcha na tela de recuperacao de senha', function (): void {
    visit('/app/password-reset/request')
        ->assertVisible('.fi-fo-anti-robo')
        ->assertVisible('.fi-fo-anti-robo iframe[title="reCAPTCHA"]')
        ->assertNoJavaScriptErrors();
})->group('browser', 'kit');

/**
 * CT-B03 — o widget reCAPTCHA renderiza na tela de registro aberto do /app.
 */
it('renderiza o widget recaptcha na tela de registro aberto', function (): void {
    config(['kit.registro.habilitado' => true]);

    // Aquece a rota de registro (pode não existir sem a config)
    $this->get('/app/register');

    visit('/app/register')
        ->assertVisible('.fi-fo-anti-robo')
        ->assertVisible('.fi-fo-anti-robo iframe[title="reCAPTCHA"]')
        ->assertNoJavaScriptErrors();
})->group('browser', 'kit');

/**
 * CT-B04 — o widget sobrevive a um erro de validação (wire:ignore funciona).
 *
 * Submete o login sem preencher nada → o formulário falha com erro de validação → o iframe
 * do reCAPTCHA continua visível. Sem `wire:ignore`, o re-render destruiria o iframe.
 */
it('mantem o widget visivel apos erro de validacao', function (): void {
    $pagina = visit('/app/login')
        ->assertVisible('.fi-fo-anti-robo iframe[title="reCAPTCHA"]');

    // Submete com campos vazios para forçar erro de validação
    $pagina->click('[type="submit"]')
        ->assertVisible('.fi-fo-anti-robo iframe[title="reCAPTCHA"]')
        ->assertNoJavaScriptErrors();
})->group('browser', 'kit');

/**
 * CT-B05 — o widget Turnstile renderiza na tela de login com o provedor trocado.
 *
 * As chaves de teste oficiais do Cloudflare Turnstile:
 * - site: 1x00000000000000000000AA (always passes, visible)
 * - secret: 1x0000000000000000000000000000000AA (always succeeds)
 */
it('renderiza o widget turnstile na tela de login', function (): void {
    $settings                                = app(ConfiguracoesDoKit::class);
    $settings->login_anti_robo_provedor      = 'turnstile';
    $settings->login_anti_robo_chave_do_site = '1x00000000000000000000AA';
    $settings->login_anti_robo_chave_secreta = '1x0000000000000000000000000000000AA';
    $settings->save();
    $settings->aplicarNaConfig();

    // Aquece com o novo provedor
    $this->get('/app/login');

    visit('/app/login')
        ->assertVisible('.fi-fo-anti-robo')
        ->assertVisible('[data-anti-robo="turnstile"]')
        ->assertNoJavaScriptErrors();
})->group('browser', 'kit');

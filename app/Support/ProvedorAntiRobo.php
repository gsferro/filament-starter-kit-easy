<?php

declare(strict_types=1);

namespace App\Support;

use Filament\Support\Contracts\HasLabel;

/**
 * Os provedores do desafio anti-robô das telas públicas — e a razão de caberem num enum só.
 *
 * Google reCAPTCHA v2 (a caixa "não sou um robô"), Cloudflare Turnstile e hCaptcha falam o MESMO
 * protocolo, nas duas pontas:
 *
 *   - no navegador: um script carregado com `?render=explicit&onload={fn}`, um objeto global com
 *     `render(el, {sitekey, theme, callback, 'expired-callback', 'error-callback'})` que devolve
 *     um id, e `reset(id)`;
 *   - no servidor: um `POST` em `application/x-www-form-urlencoded` com `secret`, `response` e
 *     `remoteip`, que responde `{"success": bool, "error-codes": [...]}`.
 *
 * Então o que varia entre eles são três URLs e o nome do objeto — e é só isso que este enum guarda.
 * O campo (`App\Filament\Forms\Components\CampoAntiRobo`) e a view são um para os três.
 *
 * O `value` de cada caso é a string que vai em `KIT_ANTI_ROBO_PROVEDOR` e em
 * `kit.login.anti_robo.provedor`. Quem decide se a proteção está no ar é
 * `ConfiguracaoDoLogin::antiRobo()`, que devolve um caso daqui ou `null`.
 *
 * reCAPTCHA **v3** não está aqui de propósito: ele não devolve `success`, devolve uma pontuação, e
 * exigiria limiar e `action` por tela. Ver ADR-02 da wiki `recaptcha-nas-telas-publicas`.
 */
enum ProvedorAntiRobo: string implements HasLabel
{
    case Recaptcha = 'recaptcha';

    case Turnstile = 'turnstile';

    case Hcaptcha = 'hcaptcha';

    public function getLabel(): string
    {
        return match ($this) {
            self::Recaptcha => 'Google reCAPTCHA v2',
            self::Turnstile => 'Cloudflare Turnstile',
            self::Hcaptcha  => 'hCaptcha',
        };
    }

    /** O script do widget, SEM query string: a view acrescenta `render=explicit` e o `onload`. */
    public function urlDoScript(): string
    {
        return match ($this) {
            self::Recaptcha => 'https://www.google.com/recaptcha/api.js',
            self::Turnstile => 'https://challenges.cloudflare.com/turnstile/v0/api.js',
            self::Hcaptcha  => 'https://js.hcaptcha.com/1/api.js',
        };
    }

    /** O endpoint `siteverify`, chamado pelo servidor com a chave secreta. */
    public function urlDeVerificacao(): string
    {
        return match ($this) {
            self::Recaptcha => 'https://www.google.com/recaptcha/api/siteverify',
            self::Turnstile => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            self::Hcaptcha  => 'https://api.hcaptcha.com/siteverify',
        };
    }

    /** O objeto global que o script expõe, com `render()` e `reset()`. */
    public function objetoJs(): string
    {
        return match ($this) {
            self::Recaptcha => 'grecaptcha',
            self::Turnstile => 'turnstile',
            self::Hcaptcha  => 'hcaptcha',
        };
    }

    /** Onde criar o par de chaves — o roteiro do `helperText` da tela e do README. */
    public function ondeCriarAsChaves(): string
    {
        return match ($this) {
            self::Recaptcha => 'google.com/recaptcha/admin → Criar → tipo "Desafio (v2)", caixa "Não sou um robô", com o seu domínio. Copie a chave do site e a chave secreta.',
            self::Turnstile => 'dash.cloudflare.com → Turnstile → Add widget, modo "Managed", com o seu domínio. Copie a Site Key e a Secret Key.',
            self::Hcaptcha  => 'dashboard.hcaptcha.com → Sites → New, com o seu domínio. A Site Key fica no site; a Secret Key, em Settings.',
        };
    }
}

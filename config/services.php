<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Google — login social (laravel/socialite)
    |--------------------------------------------------------------------------
    | O formato deste bloco é o que o Socialite exige: ele lê `services.{driver}`
    | e nada mais. Por isso o INTERRUPTOR de ligar/desligar o login com Google
    | NÃO vive aqui — ele está em `config/kit.php` → `login.google.habilitado`,
    | junto das outras opções do kit. Credencial preenchida aqui, sozinha, não
    | põe botão nenhum no ar.
    |
    | O `redirect` é um caminho RELATIVO de propósito, e não `env()`: o Socialite
    | resolve caminho relativo para URL absoluta ("If the redirect option contains
    | a relative path, it will automatically be resolved to a fully qualified
    | URL"), então o valor acompanha o APP_URL de cada ambiente sem uma chave a
    | mais para alguém esquecer de trocar entre local, homologação e produção.
    | É também o caminho literal que o requisito escreveu — cadastre exatamente
    | ele, absoluto, no console do Google.
    |
    | GOOGLE_CLIENT_SECRET é SEGREDO: nenhum log do kit o menciona, nenhuma
    | mensagem de erro o cita e ele não aparece no HTML de nenhuma tela. Ver
    | ADR-09 de wikis/specs/feat/login-social-google/login-social-google/.
    */

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => '/auth/google/callback',
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];

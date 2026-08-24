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
    | Login social (laravel/socialite) — os quatro provedores do kit
    |--------------------------------------------------------------------------
    | O formato destes blocos é o que o Socialite exige: ele lê `services.{driver}`
    | e nada mais. Por isso o INTERRUPTOR de ligar/desligar cada provedor NÃO vive
    | aqui — ele está em `config/kit.php` → `login.{driver}.habilitado`, junto das
    | outras opções do kit. Credencial preenchida aqui, sozinha, não põe botão
    | nenhum no ar.
    |
    | A chave de cada bloco é o NOME DO DRIVER, e é também o segmento da URL e o
    | valor do caso em `App\Support\ProvedorSocial`. Uma string, quatro usos, zero
    | tabela de mapeamento — ver o docblock daquele enum.
    |
    | `linkedin-openid` e não `linkedin`: são DOIS drivers diferentes no Socialite,
    | e só o OpenID devolve `email_verified`. O legado usa escopos que a própria
    | LinkedIn descontinuou e não traz sinal de verificação nenhum. O
    | `SocialiteManager` lê exatamente esta chave
    | (`vendor/laravel/socialite/src/SocialiteManager.php:110`).
    |
    | O `redirect` é um caminho RELATIVO de propósito, e não `env()`: o Socialite
    | resolve caminho relativo para URL absoluta ("If the redirect option contains
    | a relative path, it will automatically be resolved to a fully qualified
    | URL"), então o valor acompanha o APP_URL de cada ambiente sem uma chave a
    | mais para alguém esquecer de trocar entre local, homologação e produção.
    | Cadastre exatamente ele, absoluto, no console de cada provedor — os READMEs
    | dizem onde, provedor por provedor.
    |
    | NENHUMA chave `scopes` aqui, e a ausência é decisão: os defaults de cada
    | provider já pedem o que o kit precisa — GitHub `user:email`, LinkedIn OpenID
    | `openid profile email`, X `users.read users.email tweet.read`. E o cuidado
    | que a ausência evita: `scopes()` SOMA aos defaults
    | (`.../Two/AbstractProvider.php:396-401`) e só `setScopes()` substitui
    | (`:409-414`); um `setScopes(['read:user'])` tiraria `user:email` do GitHub e
    | ele deixaria de trazer e-mail verificado EM SILÊNCIO, porque o gate em
    | `.../Two/GithubProvider.php:47` lê a propriedade crua de escopos.
    |
    | Os *_CLIENT_SECRET são SEGREDO: nenhum log do kit os menciona, nenhuma
    | mensagem de erro os cita e eles não aparecem no HTML de nenhuma tela — nem
    | na tela de configuração, onde são zerados antes do formulário ser preenchido.
    | Ver ADR-09 da wiki login-social-google e ADR-06 desta.
    |
    | Facebook e Discord NÃO estão aqui de propósito: o Facebook não expõe sinal de
    | e-mail verificado e o Discord não é driver do Socialite. Os ADR-04 e ADR-05
    | de wikis/specs/feat/mais-provedores-sociais/ explicam, e os READMEs dizem o
    | que faltaria para incluí-los.
    */

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => '/auth/google/callback',
    ],

    'github' => [
        'client_id'     => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect'      => '/auth/github/callback',
    ],

    'linkedin-openid' => [
        'client_id'     => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect'      => '/auth/linkedin-openid/callback',
    ],

    'x' => [
        'client_id'     => env('X_CLIENT_ID'),
        'client_secret' => env('X_CLIENT_SECRET'),
        'redirect'      => '/auth/x/callback',
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

<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace'   => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | utilizes the Monolog PHP logging library, which includes a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */

    'channels' => [

        'stack' => [
            'driver'            => 'stack',
            'channels'          => explode(',', (string) env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver'               => 'single',
            'path'                 => storage_path('logs/laravel.log'),
            'level'                => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver'               => 'daily',
            'path'                 => storage_path('logs/laravel.log'),
            'level'                => env('LOG_LEVEL', 'debug'),
            'max_files'            => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        /*
        |------------------------------------------------------------------
        | Canais do kit — um por camada transversal (padrão: um canal por
        | feature, sempre daily/14 dias, visíveis no Logs Explorer do /infra).
        | Regra LGPD: nunca logar conteúdo de prompt/notificação em claro;
        | identificadores sempre mascarados.
        |------------------------------------------------------------------
        */

        /*
        |------------------------------------------------------------------
        | Por que os três canais do kit têm driver por env e um `handler`
        |------------------------------------------------------------------
        |
        | Os canais 'ai', 'tenancy' e 'autenticacao' leem o driver de LOG_KIT_DRIVER, e o
        | phpunit.xml fixa `monolog` nele. Sem isso a suíte escreve nos logs de trabalho de
        | quem a roda — medido: 4.463 linhas e 1,1 MB num dia em `autenticacao-*.log`, 1.033
        | delas de `[User@canAccessPanel]`. Ver DT-10 em
        | wikis/specs/feature/wiki-regressao-telas/regressao-de-telas/06-divida-tecnica.md.
        |
        | Duas armadilhas, as duas medidas contra o vendor:
        |
        | 1. `LOG_CHANNEL=null` **não** basta. Ele troca só o canal DEFAULT, e as 60 chamadas
        |    de log do kit são `Log::channel('ai'|'tenancy'|'autenticacao')` nomeadas.
        |
        | 2. `LOG_KIT_DRIVER=null` **não** funciona, e falha em SILÊNCIO. Não existe
        |    `createNullDriver` no `LogManager` (os drivers estão em
        |    `vendor/laravel/framework/src/Illuminate/Log/LogManager.php:260-433`; `resolve()`
        |    estoura em `:240`), e o `env()` do Laravel converte a string "null" em `null` de
        |    verdade — então `resolve()` lança, o `get()` (`:213`) captura o Throwable e
        |    devolve o **emergency logger**, que grava em `storage/logs/laravel.log`. O log
        |    continuaria em disco, no arquivo errado, com um `emergency` por canal.
        |
        | O `null` é um CANAL (abaixo), não um driver: `monolog` + `NullHandler`. Daí a forma
        | usada aqui — driver por env e `handler` sempre presente. `createDailyDriver` ignora
        | a chave `handler`; `createMonologDriver` (`:433`) é quem a usa.
        */

        'ai' => [
            'driver'               => env('LOG_KIT_DRIVER', 'daily'),
            'handler'              => NullHandler::class,
            'path'                 => storage_path('logs/ai.log'),
            'level'                => env('LOG_AI_LEVEL', env('LOG_LEVEL', 'debug')),
            'days'                 => 14,
            'replace_placeholders' => true,
        ],

        'tenancy' => [
            'driver'               => env('LOG_KIT_DRIVER', 'daily'),
            'handler'              => NullHandler::class,
            'path'                 => storage_path('logs/tenancy.log'),
            'level'                => env('LOG_TENANCY_LEVEL', env('LOG_LEVEL', 'debug')),
            'days'                 => 14,
            'replace_placeholders' => true,
        ],

        'autenticacao' => [
            'driver'               => env('LOG_KIT_DRIVER', 'daily'),
            'handler'              => NullHandler::class,
            'path'                 => storage_path('logs/autenticacao.log'),
            'level'                => env('LOG_LEVEL', 'debug'),
            'days'                 => 14,
            'replace_placeholders' => true,
        ],

        'monthly' => [
            'driver'               => 'monthly',
            'path'                 => storage_path('logs/laravel.log'),
            'level'                => env('LOG_LEVEL', 'debug'),
            'max_files'            => 3,
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver'               => 'slack',
            'url'                  => env('LOG_SLACK_WEBHOOK_URL'),
            'username'             => env('LOG_SLACK_USERNAME', env('APP_NAME', 'Laravel')),
            'emoji'                => env('LOG_SLACK_EMOJI', ':boom:'),
            'level'                => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver'       => 'monolog',
            'level'        => env('LOG_LEVEL', 'debug'),
            'handler'      => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host'             => env('PAPERTRAIL_URL'),
                'port'             => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver'       => 'monolog',
            'level'        => env('LOG_LEVEL', 'debug'),
            'handler'      => StreamHandler::class,
            'handler_with' => [
                'stream' => 'php://stderr',
            ],
            'formatter'  => env('LOG_STDERR_FORMATTER'),
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver'               => 'syslog',
            'level'                => env('LOG_LEVEL', 'debug'),
            'facility'             => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver'               => 'errorlog',
            'level'                => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver'  => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

    ],

];

<?php

use App\Support\ProxiesConfiaveis;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Route;

/**
 * Quem o kit acredita quando lê `X-Forwarded-*` — a chave `TRUSTED_PROXIES` do `.env`.
 *
 * Duas camadas, de propósito:
 *
 * - CT-20 testa a função pura que interpreta o valor bruto (forma de `BooleanoDoEnvTest`);
 * - CT-21 e CT-22 testam o EFEITO no request: o `bootstrap/app.php` real, com o env fixado no caso,
 *   e a aplicação lendo esquema, host, URL e IP. Provar só a função deixaria passar o
 *   `trustProxies(at: '*')` incondicional e o env passado cru.
 *
 * Costura de G6, medida: `app()->forgetInstance(Kernel::class)` faz o `afterResolving` de
 * `withMiddleware` rodar de novo no próximo `get()` — o `refreshApplication()` não é necessário. O
 * estado estático `TrustProxies::$alwaysTrustProxies` é zerado pelo `tearDown` do framework.
 */

/**
 * Fixa `TRUSTED_PROXIES` nas três formas que o `env()` consulta; `null` remove a chave.
 * O valor anterior é guardado na primeira chamada e devolvido por `restauraTrustedProxies()`.
 */
function comTrustedProxies(?string $valor): void
{
    if (! isset($GLOBALS['__trusted_proxies_anterior'])) {
        $GLOBALS['__trusted_proxies_anterior'] = [
            'putenv' => getenv('TRUSTED_PROXIES'),
            'env'    => $_ENV['TRUSTED_PROXIES'] ?? null,
            'server' => $_SERVER['TRUSTED_PROXIES'] ?? null,
        ];
    }

    if ($valor === null) {
        putenv('TRUSTED_PROXIES');
        unset($_ENV['TRUSTED_PROXIES'], $_SERVER['TRUSTED_PROXIES']);

        return;
    }

    putenv("TRUSTED_PROXIES={$valor}");
    $_ENV['TRUSTED_PROXIES']    = $valor;
    $_SERVER['TRUSTED_PROXIES'] = $valor;
}

function restauraTrustedProxies(): void
{
    $anterior = $GLOBALS['__trusted_proxies_anterior'] ?? null;

    if ($anterior === null) {
        return;
    }

    $anterior['putenv'] === false ? putenv('TRUSTED_PROXIES') : putenv("TRUSTED_PROXIES={$anterior['putenv']}");

    $anterior['env'] === null ? $_ENV       = array_diff_key($_ENV, ['TRUSTED_PROXIES' => 1]) : $_ENV['TRUSTED_PROXIES'] = $anterior['env'];
    $anterior['server'] === null ? $_SERVER = array_diff_key($_SERVER, ['TRUSTED_PROXIES' => 1]) : $_SERVER['TRUSTED_PROXIES'] = $anterior['server'];

    unset($GLOBALS['__trusted_proxies_anterior']);
}

/**
 * Faz o request do Traefik à rota de teste (sem sessão, sem banco) e devolve o que a aplicação viu.
 *
 * @return array{status: int, seguro: bool, host: string, url: string, ip: string}
 */
function requestDoTraefik(string $chamador): array
{
    app()->forgetInstance(Kernel::class);
    config(['app.url' => 'https://dev.exemplo.test']);

    Route::get('/_proxies', fn () => [
        'seguro' => request()->isSecure(),
        'host'   => request()->getHost(),
        'url'    => url('/x'),
        'ip'     => request()->ip(),
    ]);

    $resposta = test()->withServerVariables(['REMOTE_ADDR' => $chamador, 'HTTP_HOST' => 'interno.local'])->get('http://interno.local/_proxies', [
        'X-Forwarded-For'   => '203.0.113.9',
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Host'  => 'dev.exemplo.test',
        'X-Forwarded-Port'  => '443',
    ]);

    return ['status' => $resposta->status()] + ($resposta->json() ?? []);
}

afterEach(function (): void {
    restauraTrustedProxies();
});

/**
 * Cada forma do valor tem uma interpretação só. A comparação é `toBe` (estrita): lista com chaves
 * `0, 2` reprova. Tokens mágicos (`*`, `**`, `REMOTE_ADDR`, `PRIVATE_SUBNETS`) só valem como `*`
 * sozinho; em lista são descartados, e `**`/`PRIVATE_SUBNETS` sozinhos viram `null` (falha fechado).
 */
it('[CT-20] cada forma do valor tem uma interpretacao so', function (mixed $bruto, array|string|null $resultado): void {
    expect(ProxiesConfiaveis::doEnv($bruto))->toBe($resultado);
})->with([
    'ausente'                         => [null, null],
    'vazia'                           => ['', null],
    'só espaços'                      => ['   ', null],
    'só separador'                    => [',', null],
    'separadores e espaços'           => [' , , ', null],
    'todos'                           => ['*', '*'],
    'todos, com espaço'               => [' * ', '*'],
    'um'                              => ['10.0.0.1', ['10.0.0.1']],
    'dois, CIDR preservado'           => ['10.0.0.1,172.18.0.0/16', ['10.0.0.1', '172.18.0.0/16']],
    'espaço e item vazio no meio'     => [' 10.0.0.1 , ,172.18.0.0/16 ', ['10.0.0.1', '172.18.0.0/16']],
    'não-string bool'                 => [true, null],
    'não-string inteiro'              => [1, null],
    '* dentro de lista'               => ['10.0.0.1,*', ['10.0.0.1']],
    '* repetido, em lista'            => ['*,*', null],
    '** sozinho'                      => ['**', null],
    'REMOTE_ADDR dentro de lista'     => ['10.9.9.9,REMOTE_ADDR', ['10.9.9.9']],
    'REMOTE_ADDR sozinho'             => ['REMOTE_ADDR', null],
    'PRIVATE_SUBNETS sozinho'         => ['PRIVATE_SUBNETS', null],
    'PRIVATE_SUBNETS dentro de lista' => ['10.9.9.9,PRIVATE_SUBNETS', ['10.9.9.9']],
])->group('kit');

/**
 * Sem proxy confiável a aplicação vê o request como sempre viu: nenhuma combinação de chamador e
 * valor fora de R12 abre a confiança. O `APP_URL` https é o que torna `url` discriminante.
 *
 * `efetivo` é o que `env('TRUSTED_PROXIES')` devolve — para `=true` é o bool `true`, e o `Dado`
 * o afirma antes do request para o caso não medir o `.env` do desenvolvedor.
 */
it('[CT-21] cabecalhos de proxy nao confiavel nao mudam esquema, host, URL nem IP', function (string $chamador, ?string $bruto, mixed $efetivo): void {
    comTrustedProxies($bruto);

    expect(env('TRUSTED_PROXIES'))->toBe($efetivo);

    $visto = requestDoTraefik($chamador);

    expect($visto['status'])->toBe(200)
        ->and($visto['seguro'])->toBeFalse()
        ->and($visto['host'])->toBe('interno.local')
        ->and($visto['url'])->toBe('http://interno.local/x')
        ->and($visto['ip'])->toBe($chamador);
})->with([
    'ausente'                      => ['127.0.0.1', null, null],
    'vazia'                        => ['127.0.0.1', '', ''],
    'lista sem o chamador'         => ['127.0.0.1', '10.9.9.9', '10.9.9.9'],
    'não-string, falha fechado'    => ['127.0.0.1', 'true', true],
    '* dentro de lista'            => ['127.0.0.1', '10.9.9.9,*', '10.9.9.9,*'],
    'REMOTE_ADDR dentro de lista'  => ['127.0.0.1', '10.9.9.9,REMOTE_ADDR', '10.9.9.9,REMOTE_ADDR'],
    'rede do Docker, sem chave'    => ['172.18.0.5', null, null],
    '10/8, sem chave'              => ['10.0.0.7', null, null],
    'rede do Docker, vazia'        => ['172.18.0.5', '', ''],
    'faixa privada, lista sem ele' => ['10.0.0.7', '10.9.9.9', '10.9.9.9'],
    'PRIVATE_SUBNETS em lista'     => ['172.18.0.5', '10.9.9.9,PRIVATE_SUBNETS', '10.9.9.9,PRIVATE_SUBNETS'],
])->group('kit');

/**
 * Com `*` ou lista que contém o chamador, esquema, host, URL e IP são os que o Traefik informa.
 * A linha com espaço é a que reprova o `env()` passado cru ao `trustProxies()`.
 */
it('[CT-22] cabecalhos de proxy confiavel chegam a aplicacao', function (string $bruto): void {
    comTrustedProxies($bruto);

    expect(env('TRUSTED_PROXIES'))->toBe($bruto);

    $visto = requestDoTraefik('127.0.0.1');

    expect($visto['status'])->toBe(200)
        ->and($visto['seguro'])->toBeTrue()
        ->and($visto['host'])->toBe('dev.exemplo.test')
        ->and($visto['url'])->toBe('https://dev.exemplo.test/x')
        ->and($visto['ip'])->toBe('203.0.113.9');
})->with([
    'todos'                => ['*'],
    'todos, com espaço'    => [' * '],
    'lista com o chamador' => ['10.9.9.9,127.0.0.1'],
])->group('kit');

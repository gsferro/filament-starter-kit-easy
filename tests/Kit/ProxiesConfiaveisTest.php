<?php

use App\Providers\KitServiceProvider;
use App\Support\ProxiesConfiaveis;
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
 * Costura de G6/G7: o env é fixado nas três formas e `refreshApplication()` refaz o boot — o
 * `config/kit.php` relê a chave e o `KitServiceProvider::boot()` a aplica (`TrustProxies::at()`). O
 * `forgetInstance(Kernel)` não refaz o boot do provider. O estático `TrustProxies::$alwaysTrustProxies`
 * é zerado pelo `tearDown` do framework. O refresh descarta rotas, `config()` e fachadas: a rota e o
 * `app.url` são declarados depois dele, e o spy de log (CT-50) também.
 */

/**
 * Fixa uma chave nas três formas que o `env()` consulta; `null` remove a chave.
 * O valor anterior é guardado na primeira chamada de cada chave e devolvido por `restauraEnvDoCaso()`.
 */
function comEnvDoCaso(string $chave, ?string $valor): void
{
    if (! isset($GLOBALS['__env_do_caso_anterior'][$chave])) {
        $GLOBALS['__env_do_caso_anterior'][$chave] = [
            'putenv' => getenv($chave),
            'env'    => $_ENV[$chave] ?? null,
            'server' => $_SERVER[$chave] ?? null,
        ];
    }

    if ($valor === null) {
        putenv($chave);
        unset($_ENV[$chave], $_SERVER[$chave]);

        return;
    }

    putenv("{$chave}={$valor}");
    $_ENV[$chave]    = $valor;
    $_SERVER[$chave] = $valor;
}

function comTrustedProxies(?string $valor): void
{
    comEnvDoCaso('TRUSTED_PROXIES', $valor);
}

function restauraEnvDoCaso(): void
{
    foreach ($GLOBALS['__env_do_caso_anterior'] ?? [] as $chave => $anterior) {
        $anterior['putenv'] === false ? putenv($chave) : putenv("{$chave}={$anterior['putenv']}");

        $anterior['env'] === null ? $_ENV       = array_diff_key($_ENV, [$chave => 1]) : $_ENV[$chave] = $anterior['env'];
        $anterior['server'] === null ? $_SERVER = array_diff_key($_SERVER, [$chave => 1]) : $_SERVER[$chave] = $anterior['server'];
    }

    unset($GLOBALS['__env_do_caso_anterior']);
}

/**
 * Faz o request do Traefik à rota de teste (sem sessão, sem banco) e devolve o que a aplicação viu.
 *
 * @return array{status: int, seguro: bool, host: string, url: string, ip: string}
 */
function requestDoTraefik(string $chamador, string $porta = '443'): array
{
    test()->refreshApplication();
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
        'X-Forwarded-Port'  => $porta,
    ]);

    return ['status' => $resposta->status()] + ($resposta->json() ?? []);
}

afterEach(function (): void {
    restauraEnvDoCaso();
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
    'não-string falso'                => [false, null],
    '* dentro de lista'               => ['10.0.0.1,*', ['10.0.0.1']],
    '* repetido, em lista'            => ['*,*', null],
    '*, com separador'                => ['*,', null],
    '** sozinho'                      => ['**', null],
    '** dentro de lista'              => ['10.0.0.1,**', ['10.0.0.1']],
    'REMOTE_ADDR dentro de lista'     => ['10.9.9.9,REMOTE_ADDR', ['10.9.9.9']],
    'REMOTE_ADDR com espaço'          => ['10.9.9.9, REMOTE_ADDR ', ['10.9.9.9']],
    'REMOTE_ADDR sozinho'             => ['REMOTE_ADDR', null],
    'PRIVATE_SUBNETS sozinho'         => ['PRIVATE_SUBNETS', null],
    'PRIVATE_SUBNETS dentro de lista' => ['10.9.9.9,PRIVATE_SUBNETS', ['10.9.9.9']],
    'PRIVATE_SUBNETS com espaço'      => ['10.9.9.9 , PRIVATE_SUBNETS', ['10.9.9.9']],
    'private_ranges sozinho'          => ['private_ranges', null],
    'private_ranges dentro de lista'  => ['10.9.9.9,private_ranges', ['10.9.9.9']],
    'CIDR com prefixo malformado'     => ['172.18.0.0/16x', null],
    'IP com octeto malformado'        => ['17x.18.0.1', null],
    'inválido dentro de lista'        => ['10.0.0.1,172.18.0.0/16x', ['10.0.0.1']],
    'prefixo IPv4 acima de 32'        => ['10.0.0.0/33', null],
    'IPv6 válido'                     => ['2001:db8::1', ['2001:db8::1']],
    'prefixo IPv6 acima de 128'       => ['2001:db8::/129', null],
    'CIDR com bits de host'           => ['10.0.0.1/8', ['10.0.0.1/8']],
    'prefixo no limite IPv4 (/32)'    => ['10.0.0.1/32', ['10.0.0.1/32']],
    'prefixo no limite IPv6 (/128)'   => ['2001:db8::1/128', ['2001:db8::1/128']],
])->group('kit');

/**
 * O que o kit descartou e vai avisar (R21): coringa em lista, coringa sozinho (Q13), item que não é
 * IP/CIDR e não-string. `*` sozinho vale "todos" e item vazio não é descarte. `toBe`: ordem e chaves.
 */
it('[CT-49] o kit diz quais itens descartou', function (mixed $bruto, array $descartados): void {
    expect(ProxiesConfiaveis::descartados($bruto))->toBe($descartados);
})->with([
    'coringa em lista'           => ['10.0.0.1,*', ['*']],
    'coringa minúsculo em lista' => ['10.9.9.9,private_ranges', ['private_ranges']],
    'inválido sozinho'           => ['172.18.0.0/16x', ['172.18.0.0/16x']],
    'misto, na ordem'            => ['10.0.0.1,REMOTE_ADDR,17x.18.0.1', ['REMOTE_ADDR', '17x.18.0.1']],
    'o descartado vem aparado'   => [' 10.0.0.1 , 17x.18.0.1 ', ['17x.18.0.1']],
    'ausente'                    => [null, []],
    'vazio'                      => ['', []],
    'só separadores'             => [' , , ', []],
    'lista limpa'                => ['10.0.0.1,172.18.0.0/16', []],
    '* sozinho'                  => ['*', []],
    '** sozinho'                 => ['**', ['**']],
    'REMOTE_ADDR sozinho'        => ['REMOTE_ADDR', ['REMOTE_ADDR']],
    'private_ranges sozinho'     => ['private_ranges', ['private_ranges']],
    'não-string'                 => [true, ['true']],
])->group('kit');

/**
 * Sem proxy confiável a aplicação vê o request como sempre viu: nenhuma combinação de chamador e
 * valor fora de R12 abre a confiança. O `APP_URL` https é o que torna `url` discriminante.
 *
 * `efetivo` é o que `env('TRUSTED_PROXIES')` devolve — para `=true` é o bool `true`, e o `Dado`
 * o afirma antes do request para o caso não medir o `.env` do desenvolvedor.
 */
it('[CT-21] cabecalhos de proxy nao confiavel nao mudam esquema, host, URL nem IP', function (string $chamador, ?string $bruto, mixed $efetivo, ?string $traefikHost = null): void {
    comTrustedProxies($bruto);
    comEnvDoCaso('TRAEFIK_HOST', $traefikHost);

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
    '** sozinho'                   => ['127.0.0.1', '**', '**'],
    'REMOTE_ADDR sozinho'          => ['127.0.0.1', 'REMOTE_ADDR', 'REMOTE_ADDR'],
    '*, repetido, em lista'        => ['127.0.0.1', '*,*', '*,*'],
    'TRAEFIK_HOST definida'        => ['127.0.0.1', null, null, 'dev.exemplo.test'],
    'rede do Docker, sem chave'    => ['172.18.0.5', null, null],
    '10/8, sem chave'              => ['10.0.0.7', null, null],
    'rede do Docker, vazia'        => ['172.18.0.5', '', ''],
    'faixa privada, lista sem ele' => ['10.0.0.7', '10.9.9.9', '10.9.9.9'],
    'PRIVATE_SUBNETS em lista'     => ['172.18.0.5', '10.9.9.9,PRIVATE_SUBNETS', '10.9.9.9,PRIVATE_SUBNETS'],
    'PRIVATE_SUBNETS sozinho'      => ['172.18.0.5', 'PRIVATE_SUBNETS', 'PRIVATE_SUBNETS'],
    'private_ranges em lista'      => ['172.18.0.5', '10.9.9.9,private_ranges', '10.9.9.9,private_ranges'],
    'item malformado, sem erro'    => ['127.0.0.1', '172.18.0.0/16x', '172.18.0.0/16x'],
])->group('kit');

/**
 * Com `*` ou lista que contém o chamador, esquema, host, URL e IP são os que o Traefik informa.
 * A linha com espaço é a que reprova o `env()` passado cru ao `trustProxies()`.
 */
it('[CT-22] cabecalhos de proxy confiavel chegam a aplicacao', function (string $bruto, string $porta, string $url): void {
    comTrustedProxies($bruto);

    expect(env('TRUSTED_PROXIES'))->toBe($bruto);

    $visto = requestDoTraefik('127.0.0.1', $porta);

    expect($visto['status'])->toBe(200)
        ->and($visto['seguro'])->toBeTrue()
        ->and($visto['host'])->toBe('dev.exemplo.test')
        ->and($visto['url'])->toBe($url)
        ->and($visto['ip'])->toBe('203.0.113.9');
})->with([
    'todos'                     => ['*', '443', 'https://dev.exemplo.test/x'],
    'todos, com espaço'         => [' * ', '443', 'https://dev.exemplo.test/x'],
    'lista com o chamador'      => ['10.9.9.9,127.0.0.1', '443', 'https://dev.exemplo.test/x'],
    'porta não padrão do proxy' => ['*', '8443', 'https://dev.exemplo.test:8443/x'],
    'malformado descartado'     => ['17x.18.0.1,127.0.0.1', '443', 'https://dev.exemplo.test/x'],
])->group('kit');

/**
 * A chave sai do `bootstrap/app.php` (o closure de `withMiddleware()` roda antes do `.env`) e passa a
 * ser lida pelo config e aplicada no boot. A linha textual é a que discrimina o bootstrap; ela só roda
 * na árvore do kit (o projeto instalado pode ter o seu próprio `trustProxies()`). A asserção de
 * ausência filtra comentário (`.ai/rules/testes.md`): citar não é executar.
 */
it('[CT-48] a chave e lida pelo config e aplicada no boot, nao no bootstrap', function (?string $ambiente, ?string $noConfig, bool $seguro): void {
    comTrustedProxies($ambiente);

    $visto = requestDoTraefik('127.0.0.1');

    expect(config('kit.proxies_confiaveis'))->toBe($noConfig)
        ->and($visto['status'])->toBe(200)
        ->and($visto['seguro'])->toBe($seguro);
})->with([
    'ausente'      => [null, null, false],
    '*'            => ['*', '*', true],
    '* com espaço' => [' * ', ' * ', true],
])->group('kit');

it('[CT-48] o bootstrap/app.php nao le TRUSTED_PROXIES nem chama trustProxies', function (): void {
    $codigo = preg_replace(['~/\*.*?\*/~s', '~^\s*(//|#).*$~m'], '', file_get_contents(base_path('bootstrap/app.php')));

    expect($codigo)->not->toContain('trustProxies')
        ->and($codigo)->not->toContain('TRUSTED_PROXIES');
})->skip(fn (): bool => ! naArvoreDoKit(), 'o bootstrap do kit só é conferido na árvore do kit')->group('kit');

/**
 * Arnês G7: env fixado → refresh (limpa as fachadas) → spy do canal → re-execução do passo de boot que aplica a chave
 * (forma de `alinharConfiguracoesDoKit()`: o `boot()` inteiro registra os health checks de novo e estoura
 * `DuplicateCheckNamesFound`). Conta-se só o que a re-execução emite.
 *
 * @param  list<string>  $descartados
 */
it('[CT-50] cada item descartado gera um aviso no canal configuracoes, e so ele', function (string $valor, int $n, array $descartados): void {
    comTrustedProxies($valor);
    expect(env('TRUSTED_PROXIES'))->toBe($valor);

    test()->refreshApplication();

    $canal = espiarConfiguracoes();

    (fn () => $this->confiarNosProxiesDoEnv())->call(app()->getProvider(KitServiceProvider::class));

    if ($n === 0) {
        $canal->shouldNotHaveReceived('warning');

        return;
    }

    $canal->shouldHaveReceived('warning')->times($n);

    foreach ($descartados as $item) {
        $canal->shouldHaveReceived('warning')
            ->withArgs(fn (string $mensagem, array $contexto = []): bool => str_starts_with($mensagem, '[KitServiceProvider@confiarNosProxiesDoEnv]')
                && ($contexto['item'] ?? null) === $item)
            ->once();
    }
})->with([
    'coringa e malformado em lista' => ['10.0.0.1,REMOTE_ADDR,17x.18.0.1', 2, ['REMOTE_ADDR', '17x.18.0.1']],
    'lista limpa'                   => ['10.0.0.1,172.18.0.0/16', 0, []],
    '* sozinho'                     => ['*', 0, []],
])->group('kit');

<?php

/**
 * Deploy com Docker: vários ambientes não produtivos no mesmo servidor, atrás do Traefik.
 *
 * Derivado do `04-casos-de-teste.md` da wiki `deploy-multiambiente-docker`, que é o oráculo —
 * não o código. Este arquivo cobre as regras R1 a R9 e R13 a R20 (CT-20, CT-21 e CT-22 moram em
 * `ProxiesConfiaveisTest.php`).
 *
 * ## Duas formas de observar
 *
 * - LEITURA ESTÁTICA (G1, G3, G4): o `Então` afirma texto de arquivo entregue. A asserção de
 *   AUSÊNCIA roda sobre o texto SEM as linhas de comentário — os arquivos do kit citam o que
 *   proíbem; a de PRESENÇA roda sobre o texto cru (`.ai/rules/testes.md`).
 * - `docker compose config` EM PASTA TEMPORÁRIA (G2): o observável é a configuração EFETIVA que o
 *   Compose montaria. Interpolação de label, merge de `ports` e carga automática do override só
 *   o Compose resolve; regex sobre YAML não vê. Quem não tem o CLI pula (`composeDisponivel()`).
 *
 * ## A armadilha de ambiente do G2
 *
 * O Laravel escreve as chaves do `.env` do desenvolvedor no `putenv`, e a `Process` herda o
 * ambiente do PHP. Variável de shell VENCE o `.env` na interpolação do Compose: um
 * `COMPOSE_PROJECT_NAME=starter-kit` herdado invalida o caso em silêncio. Por isso a `Process`
 * recebe `false` (remove) para todo nome presente em `getenv()`, `$_ENV` e `$_SERVER`, exceto a
 * lista de sistema que o CLI precisa para achar o plugin e o daemon. No Windows a lista do 04
 * ganhou `ProgramFiles` e `ProgramW6432`: sem elas o `docker` não acha o plugin `compose`
 * (medido: "unknown command: docker compose"); nenhuma é chave de `.env`.
 *
 * ## O golden do CT-34 (`tests/Kit/fixtures/compose-base.json`)
 *
 * É a configuração efetiva do `docker-compose.yml` base — `docker compose --profile app config
 * --format json` sobre uma pasta com só o base e um `.env` de uma linha
 * (`COMPOSE_PROJECT_NAME=starter-kit`), com todo caminho absoluto trocado por `<raiz>`.
 *
 * O fixture EXPIRA quando o base mudar DE PROPÓSITO (porta, `restart`, `healthcheck`, serviço
 * novo): o caso fica vermelho e a mudança precisa vir acompanhada do fixture novo — esse é o ato
 * deliberado. Para regenerar, rode o arquivo uma vez com `GOLDEN_COMPOSE_REGENERAR=1` no
 * ambiente (o caso grava o fixture a partir da saída do momento e passa), confira o diff do
 * fixture no git e commite os dois juntos. Os tipos escalares são normalizados para string dos
 * dois lados (versões do Compose divergem em `published` número × string).
 *
 * Helpers ficam LOCAIS: um consumidor só (`.ai/rules/testes.md`).
 */

use App\Console\Commands\KitUpdate;
use Symfony\Component\Process\Process;

/*
|--------------------------------------------------------------------------
| Helpers — Compose
|--------------------------------------------------------------------------
*/

/** O CLI do Docker Compose responde? (cacheado: um processo por execução do arquivo) */
function composeDisponivel(): bool
{
    static $disponivel = null;

    if ($disponivel === null) {
        try {
            $processo = new Process(['docker', 'compose', 'version']);
            $processo->setTimeout(60);
            $processo->run();
            $disponivel = $processo->isSuccessful();
        } catch (Throwable) {
            $disponivel = false;
        }
    }

    return $disponivel;
}

/**
 * O ambiente do subprocesso: tudo que o PHP carrega vira `false` (remove), menos a lista de
 * sistema.
 *
 * @return array<string, false>
 */
function ambienteLimpoDoCompose(): array
{
    $manter = ['PATH', 'SYSTEMROOT', 'TEMP', 'TMP', 'HOME', 'USERPROFILE', 'APPDATA', 'LOCALAPPDATA', 'PROGRAMDATA', 'PROGRAMFILES', 'PROGRAMW6432', 'DOCKER_HOST', 'DOCKER_CONFIG', 'DOCKER_CONTEXT'];

    $nomes = array_merge(array_keys(getenv()), array_keys($_ENV), array_keys($_SERVER));
    $env   = [];

    foreach ($nomes as $nome) {
        $nome = (string) $nome;

        if ($nome === '' || str_contains($nome, '=') || in_array(strtoupper($nome), $manter, true)) {
            continue;
        }

        $env[$nome] = false;
    }

    return $env;
}

/** Um `.env` a partir de pares; valor `null` omite a chave (ausente), `''` a deixa vazia. */
function dotenvDoCaso(array $pares): string
{
    $linhas = [];

    foreach ($pares as $chave => $valor) {
        if ($valor !== null) {
            $linhas[] = "{$chave}={$valor}";
        }
    }

    return implode("\n", $linhas)."\n";
}

/**
 * Os arquivos de Compose que o kit entrega na raiz (`docker-compose*.y*ml`, `compose*.y*ml`),
 * como mapa destino => origem relativa a `base_path()`.
 *
 * @return array<string, string>
 */
function arquivosDeComposeDaRaiz(): array
{
    $achados = array_merge(
        glob(base_path('docker-compose*.y*ml')) ?: [],
        glob(base_path('compose*.y*ml')) ?: [],
    );

    $mapa = [];

    foreach ($achados as $caminho) {
        $mapa[basename($caminho)] = basename($caminho);
    }

    return $mapa;
}

/** Apaga uma pasta temporária, inclusive o que o Windows marca como somente leitura. */
function removerPastaTemporaria(string $pasta): void
{
    if (! is_dir($pasta)) {
        return;
    }

    $itens = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($pasta, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($itens as $item) {
        if ($item->isDir()) {
            @rmdir($item->getPathname());
        } else {
            @chmod($item->getPathname(), 0666);
            @unlink($item->getPathname());
        }
    }

    @rmdir($pasta);
}

/**
 * `docker compose --profile app config --format json` numa pasta temporária.
 *
 * @param  array<string, string>  $copias  destino => origem (relativa a base_path())
 * @param  array<string, string>  $textos  destino => conteúdo
 * @param  list<string>|null  $args  argumentos depois de `docker compose`
 * @return array{codigo: int, config: array<string, mixed>, erro: string, raiz: string}
 */
function composeConfig(string $env, array $copias, array $textos = [], ?array $args = null): array
{
    $pasta = sys_get_temp_dir().DIRECTORY_SEPARATOR.'kit-compose-'.uniqid('', true);
    mkdir($pasta, 0777, true);

    try {
        foreach ($copias as $destino => $origem) {
            $alvo = $pasta.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $destino);
            @mkdir(dirname($alvo), 0777, true);
            copy(base_path($origem), $alvo);
        }

        foreach ($textos as $destino => $texto) {
            $alvo = $pasta.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $destino);
            @mkdir(dirname($alvo), 0777, true);
            file_put_contents($alvo, $texto);
        }

        file_put_contents($pasta.DIRECTORY_SEPARATOR.'.env', $env);

        $processo = new Process(
            ['docker', 'compose', ...($args ?? ['--profile', 'app', 'config', '--format', 'json'])],
            $pasta,
            ambienteLimpoDoCompose(),
        );
        $processo->setTimeout(120);
        $processo->run();

        $config = json_decode($processo->getOutput(), true);

        return [
            'codigo' => (int) $processo->getExitCode(),
            'config' => is_array($config) ? $config : [],
            'erro'   => $processo->getErrorOutput(),
            'raiz'   => $pasta,
        ];
    } finally {
        removerPastaTemporaria($pasta);
    }
}

/**
 * A configuração do base sozinho (só o `docker-compose.yml` e o `.env` dado), com os caminhos
 * absolutos da pasta temporária trocados por `<raiz>` — duas pastas, dois caminhos, e o diff do
 * CT-06 não pode ver a diferença entre eles.
 */
function configDoBase(string $env): array
{
    $resultado = composeConfig($env, ['docker-compose.yml' => 'docker-compose.yml']);

    expect($resultado['codigo'])->toBe(0, 'O compose do base recusou: '.$resultado['erro']);

    return normalizarConfiguracao($resultado['config'], $resultado['raiz']);
}

/** O base com o exemplo copiado PARA A RAIZ (a cópia ativa que liga o Traefik). */
function configComOverride(string $env, array $textosExtras = []): array
{
    $resultado = composeConfig(
        $env,
        ['docker-compose.yml' => 'docker-compose.yml', 'docker-compose.override.yml' => 'docker/traefik/docker-compose.override.yml'],
        $textosExtras,
    );

    expect($resultado['codigo'])->toBe(0, 'O compose com o override recusou: '.$resultado['erro']);

    return normalizarConfiguracao($resultado['config'], $resultado['raiz']);
}

/** Os labels de um serviço, como mapa. */
function labelsDoServico(array $config, string $servico): array
{
    return $config['services'][$servico]['labels'] ?? [];
}

/** As redes de um serviço (nomes). Sem a chave, o Compose o põe só na `default`. */
function redesDoServico(array $config, string $servico): array
{
    $redes = $config['services'][$servico]['networks'] ?? ['default' => null];

    return array_keys($redes);
}

/** A chave da rede de topo externa (a do Traefik), ou `null`. */
function chaveDaRedeExterna(array $config): ?string
{
    foreach ($config['networks'] ?? [] as $chave => $rede) {
        if ($chave !== 'default' && ($rede['external'] ?? false) === true) {
            return (string) $chave;
        }
    }

    return null;
}

/**
 * Toda publicação de porta da configuração.
 *
 * @return list<array{servico: string, ip: string, publicada: string, alvo: string}>
 */
function publicacoesDaConfig(array $config): array
{
    $saida = [];

    foreach ($config['services'] ?? [] as $servico => $definicao) {
        foreach ($definicao['ports'] ?? [] as $porta) {
            $saida[] = [
                'servico'   => (string) $servico,
                'ip'        => (string) ($porta['host_ip'] ?? ''),
                'publicada' => (string) ($porta['published'] ?? ''),
                'alvo'      => (string) ($porta['target'] ?? ''),
            ];
        }
    }

    return $saida;
}

/** Dois IPs de host disputam a mesma porta? Vazio e 0.0.0.0 sobrepõem qualquer IP. */
function ipsSobrepoem(string $a, string $b): bool
{
    $qualquer = static fn (string $ip): bool => $ip === '' || $ip === '0.0.0.0';

    return $qualquer($a) || $qualquer($b) || $a === $b;
}

/**
 * Pares de publicações que disputam a mesma porta do host.
 *
 * @param  list<array{servico: string, ip: string, publicada: string, alvo: string}>  $publicacoes
 * @return list<string>
 */
function colisoesDePorta(array $publicacoes): array
{
    $colisoes = [];

    foreach ($publicacoes as $i => $a) {
        foreach ($publicacoes as $j => $b) {
            if ($j <= $i || $a['publicada'] !== $b['publicada'] || ! ipsSobrepoem($a['ip'], $b['ip'])) {
                continue;
            }

            $colisoes[] = "{$a['servico']}({$a['ip']}:{$a['publicada']}) × {$b['servico']}({$b['ip']}:{$b['publicada']})";
        }
    }

    return $colisoes;
}

/**
 * Toda folha de uma árvore, por caminho pontilhado; folha vazia (`[]`, `null`) é folha.
 *
 * @return array<string, string>
 */
function folhasDaConfiguracao(mixed $no, string $caminho = ''): array
{
    if (is_array($no) && $no !== []) {
        $saida = [];

        foreach ($no as $chave => $filho) {
            $saida += folhasDaConfiguracao($filho, $caminho === '' ? (string) $chave : $caminho.'.'.$chave);
        }

        return $saida;
    }

    return [$caminho => (string) json_encode($no)];
}

/**
 * Escalar vira string, mapa ganha chaves ordenadas, e todo caminho absoluto da pasta temporária
 * vira `<raiz>` — dos dois lados da comparação do golden.
 */
function normalizarConfiguracao(mixed $no, string $raiz): mixed
{
    if (is_array($no)) {
        $normalizado = array_map(static fn (mixed $filho): mixed => normalizarConfiguracao($filho, $raiz), $no);

        if (! array_is_list($normalizado)) {
            ksort($normalizado);
        }

        return $normalizado;
    }

    if (is_bool($no) || $no === null) {
        return $no;
    }

    $texto = str_replace('\\', '/', (string) $no);

    if ($raiz !== '') {
        $variantes = array_unique(array_filter([$raiz, realpath($raiz) ?: '']));

        foreach ($variantes as $variante) {
            $prefixo = rtrim(str_replace('\\', '/', $variante), '/');

            if (stripos($texto, $prefixo) === 0) {
                return '<raiz>'.substr($texto, strlen($prefixo));
            }
        }
    }

    return (string) $no;
}

/*
|--------------------------------------------------------------------------
| Helpers — texto
|--------------------------------------------------------------------------
*/

/** O texto sem as linhas de comentário — só para asserção de AUSÊNCIA. */
function semLinhasDeComentario(string $texto): string
{
    return implode("\n", array_filter(
        explode("\n", $texto),
        static fn (string $linha): bool => ! str_starts_with(ltrim($linha), '#'),
    ));
}

/** O texto de um arquivo do kit, cru. */
function textoDoArquivo(string $caminho): string
{
    return (string) file_get_contents(base_path($caminho));
}

/** O recorte do estágio `assets` do Dockerfile: do `FROM … AS assets` até o próximo `FROM`. */
function estagioAssetsDoDockerfile(): string
{
    $linhas  = explode("\n", textoDoArquivo('Dockerfile.laravel'));
    $dentro  = false;
    $recorte = [];

    foreach ($linhas as $linha) {
        if (str_starts_with($linha, 'FROM node:22-alpine AS assets')) {
            $dentro = true;
        } elseif ($dentro && str_starts_with($linha, 'FROM ')) {
            break;
        }

        if ($dentro) {
            $recorte[] = $linha;
        }
    }

    return implode("\n", $recorte);
}

/**
 * As linhas entre os marcadores do bloco do Reverb, SEM o `# ` inicial (indentação preservada).
 *
 * @return list<string>
 */
function linhasDoBlocoDoReverb(string $exemplo): array
{
    $linhas = [];
    $dentro = false;

    foreach (explode("\n", $exemplo) as $linha) {
        if (str_contains($linha, '# >>> reverb-traefik')) {
            $dentro = true;

            continue;
        }

        if (str_contains($linha, '# <<< reverb-traefik')) {
            break;
        }

        if ($dentro) {
            $linhas[] = (string) preg_replace('/^(\s*)# ?/', '$1', $linha);
        }
    }

    return $linhas;
}

/** `${NOME}`, `${NOME:-x}`, `${NOME:?x}` trocados pelos valores dados; o resto fica com `${`. */
function interpolarComoOCompose(string $texto, array $valores): string
{
    return (string) preg_replace_callback(
        '~\$\{(\w+)(?::?[-?][^}]*)?\}~',
        static fn (array $m): string => $valores[$m[1]] ?? '${'.$m[1],
        $texto,
    );
}

/** A regra do router `-reverb` do exemplo (valor cru, com `${…}`), das linhas do bloco. */
function regraDoRouterDoReverb(string $exemplo): ?string
{
    foreach (linhasDoBlocoDoReverb($exemplo) as $linha) {
        if (preg_match('~^\s*- traefik\.http\.routers\.[^\s=]+-reverb\.rule=(.+)$~', $linha, $m) === 1) {
            return trim($m[1]);
        }
    }

    return null;
}

/** O que a regra do Reverb do R7 precisa casar. */
function regexDaRegraDoReverb(): string
{
    return '~^Host\(`[^`]+`\) && \(PathPrefix\(`/app/[^`]+`\) \|\| PathPrefix\(`/apps/[^`]+`\)\)$~';
}

/**
 * As linhas ATIVAS (não comentadas) de um `.env`, como lista de [chave, valor].
 *
 * @return list<array{0: string, 1: string}>
 */
function linhasAtivasDoEnv(string $env): array
{
    $saida = [];

    foreach (explode("\n", $env) as $linha) {
        if (preg_match('/^\s*([A-Z][A-Z0-9_]*)=(.*)$/', $linha, $m) === 1 && ! str_starts_with(ltrim($linha), '#')) {
            $saida[] = [$m[1], $m[2]];
        }
    }

    return $saida;
}

/*
|--------------------------------------------------------------------------
| Helpers — a página de documentação
|--------------------------------------------------------------------------
*/

/** O Markdown da página do idioma. Quem chama leva `skip` fora da árvore do kit. */
function paginaMultiambiente(string $idioma): string
{
    return (string) file_get_contents(base_path("docs/{$idioma}/operacao/deploy-docker-multiambiente.md"));
}

/**
 * As linhas do Markdown com a marca de "dentro de cerca de código".
 *
 * @return list<array{0: string, 1: bool}>
 */
function linhasDoMarkdown(string $markdown): array
{
    $saida  = [];
    $cerca  = null;

    foreach (explode("\n", $markdown) as $linha) {
        if (preg_match('/^\s*(`{3,}|~{3,})/', $linha, $m) === 1) {
            if ($cerca === null) {
                $cerca   = $m[1][0];
                $saida[] = [$linha, true];

                continue;
            }

            if ($m[1][0] === $cerca) {
                $cerca   = null;
                $saida[] = [$linha, true];

                continue;
            }
        }

        $saida[] = [$linha, $cerca !== null];
    }

    return $saida;
}

/**
 * Os blocos de código (conteúdo, sem as cercas).
 *
 * @return list<string>
 */
function blocosDeCodigoDoMarkdown(string $markdown): array
{
    $blocos = [];
    $atual  = null;

    foreach (linhasDoMarkdown($markdown) as [$linha, $dentro]) {
        $ehCerca = preg_match('/^\s*(`{3,}|~{3,})/', $linha) === 1;

        if ($ehCerca && $atual === null) {
            $atual = [];

            continue;
        }

        if ($ehCerca && $atual !== null) {
            $blocos[] = implode("\n", $atual);
            $atual    = null;

            continue;
        }

        if ($atual !== null) {
            $atual[] = $linha;
        }
    }

    return $blocos;
}

/** A prosa: linhas fora de bloco de código (a tabela continua). */
function prosaDoMarkdown(string $markdown): string
{
    return implode("\n", array_map(
        static fn (array $l): string => $l[0],
        array_filter(linhasDoMarkdown($markdown), static fn (array $l): bool => ! $l[1]),
    ));
}

/**
 * As seções, uma por título (fora de cerca de código): título, nível e o texto até o PRÓXIMO título.
 *
 * @return list<array{titulo: string, nivel: int, texto: string}>
 */
function secoesDaPagina(string $markdown): array
{
    $secoes = [];

    foreach (linhasDoMarkdown($markdown) as [$linha, $dentro]) {
        if (! $dentro && preg_match('/^(#{1,6}) (.*)$/', $linha, $m) === 1) {
            $secoes[] = ['titulo' => trim($m[2]), 'nivel' => strlen($m[1]), 'texto' => ''];

            continue;
        }

        if ($secoes === []) {
            $secoes[] = ['titulo' => '', 'nivel' => 0, 'texto' => ''];
        }

        $secoes[count($secoes) - 1]['texto'] .= $linha."\n";
    }

    return $secoes;
}

/** O texto da seção `$i` com as subseções (títulos de nível maior que o dela). */
function textoComSubsecoes(array $secoes, int $i): string
{
    $texto = $secoes[$i]['texto'];

    for ($j = $i + 1; $j < count($secoes) && $secoes[$j]['nivel'] > $secoes[$i]['nivel']; $j++) {
        $texto .= $secoes[$j]['titulo']."\n".$secoes[$j]['texto'];
    }

    return $texto;
}

/** O índice da seção cujo título casa o padrão, ou `null`. */
function indiceDaSecaoPorTitulo(array $secoes, string $padrao): ?int
{
    foreach ($secoes as $i => $secao) {
        if (preg_match($padrao, $secao['titulo']) === 1) {
            return $i;
        }
    }

    return null;
}

/** `str_contains` sem diferença de caixa, para texto em UTF-8. */
function contemSemCaixa(string $texto, string $agulha): bool
{
    return mb_stripos($texto, $agulha) !== false;
}

/**
 * Todas as combinações idioma × item, com nome legível.
 *
 * @param  list<string>  $itens
 * @return array<string, array{0: string, 1: string}>
 */
function idiomasPorItem(array $itens): array
{
    $saida = [];

    foreach (['pt', 'en'] as $idioma) {
        foreach ($itens as $item) {
            $saida["{$idioma}: {$item}"] = [$idioma, $item];
        }
    }

    return $saida;
}

/*
|--------------------------------------------------------------------------
| R1 — Sem a cópia ativa do override, a stack do kit não muda
|--------------------------------------------------------------------------
*/

it('[CT-01] o exemplo em docker/traefik não é carregado pelo Compose', function (): void {
    $resultado = composeConfig(
        dotenvDoCaso(['APP_ENV' => 'local']),
        [...arquivosDeComposeDaRaiz(), 'docker/traefik/docker-compose.override.yml' => 'docker/traefik/docker-compose.override.yml'],
    );

    expect($resultado['codigo'])->toBe(0, $resultado['erro']);

    $config = $resultado['config'];

    foreach (array_keys($config['services']) as $servico) {
        $comTraefik = array_filter(array_keys(labelsDoServico($config, $servico)), static fn (string $chave): bool => str_starts_with($chave, 'traefik.'));

        expect($comTraefik)->toBe([], "O serviço {$servico} ganhou label do Traefik sem a cópia ativa.");
    }

    expect(array_values(array_diff(array_keys($config['networks'] ?? []), ['default'])))->toBe([]);

    $nginx = array_values(array_filter(publicacoesDaConfig($config), static fn (array $p): bool => $p['servico'] === 'nginx'));

    expect($nginx)->toHaveCount(1)
        ->and($nginx[0]['publicada'])->toBe('8000')
        ->and($nginx[0]['alvo'])->toBe('80');
})->group('kit')->skip(fn (): bool => ! composeDisponivel(), 'CLI do Docker Compose ausente');

it('[CT-02] os arquivos-base continuam sem nada do Traefik nas linhas ativas', function (string $arquivo, array $proibidos, string $preservado): void {
    $cru   = textoDoArquivo($arquivo);
    $ativo = semLinhasDeComentario($cru);

    foreach ($proibidos as $proibido) {
        expect(preg_match('~'.$proibido.'~mi', $ativo))->toBe(0, "`{$arquivo}` ganhou `{$proibido}` em linha ativa.");
    }

    expect(preg_match('~'.$preservado.'~m', $cru))->toBe(1, "`{$arquivo}` perdeu a linha que casa `{$preservado}`.");
})->with([
    'compose base'           => ['docker-compose.yml', ['^\s*networks:', '^\s*labels:', 'traefik'], "^\s*- '\\$\\{FORWARD_APP_PORT:-8000\\}:80'$"],
    'TLS termina no Traefik' => ['docker/nginx/nginx.conf', ['listen 443', 'ssl_certificate'], '^\s*listen 80;$'],
])->group('kit');

it('[CT-03] o script de deploy chama o Compose sem fixar arquivo, projeto nem env-file', function (): void {
    $ativo = semLinhasDeComentario(textoDoArquivo('deploy_docker_local.sh'));

    // `docker compose …` e a forma em array do script (`up=(compose …)` + `docker "${up[@]}"`).
    $invocacoes = array_filter(
        explode("\n", $ativo),
        static fn (string $linha): bool => str_contains($linha, 'docker compose')
            || str_contains($linha, 'up=(compose')
            || str_contains($linha, 'docker "${up[@]}"'),
    );

    expect($invocacoes)->not->toBeEmpty();

    foreach ($invocacoes as $linha) {
        expect(preg_match('/(^|\s)(-f|--file|-p|--project-name|--env-file)(\s|=|$)/', $linha))->toBe(0, "A linha fixa arquivo, projeto ou env-file: {$linha}");
    }

    expect($ativo)->not->toContain('COMPOSE_FILE', 'COMPOSE_PROJECT_NAME');

    $comUpBuild = array_filter($invocacoes, static fn (string $linha): bool => str_contains($linha, 'up -d --build'));

    expect($comUpBuild)->not->toBeEmpty();

    $linhaDoHealthCheck = array_values(array_filter(explode("\n", $ativo), static fn (string $linha): bool => str_contains($linha, 'port nginx 80')));

    expect($linhaDoHealthCheck)->not->toBeEmpty()
        ->and($linhaDoHealthCheck[0])->toContain('docker compose --profile app port nginx 80')
        ->and($ativo)->not->toContain('${FORWARD_APP_PORT');
})->group('kit');

it('[CT-33] sem o override e sem a chave no .env, nenhum serviço recebe TRUSTED_PROXIES', function (): void {
    $config = configDoBase(dotenvDoCaso(['APP_ENV' => 'local']));

    foreach ($config['services'] as $servico => $definicao) {
        expect(array_key_exists('TRUSTED_PROXIES', $definicao['environment'] ?? []))->toBeFalse("O serviço {$servico} recebeu TRUSTED_PROXIES.");
    }
})->group('kit')->skip(fn (): bool => ! composeDisponivel(), 'CLI do Docker Compose ausente');

/**
 * [CT-34] A configuração efetiva do base é a do golden versionado.
 *
 * O fixture `tests/Kit/fixtures/compose-base.json` expira quando o `docker-compose.yml` mudar de
 * propósito. Regenerar: rode este arquivo com `GOLDEN_COMPOSE_REGENERAR=1` no ambiente — o caso
 * grava o fixture (com os caminhos absolutos normalizados para `<raiz>`) e passa; confira o diff
 * e commite o fixture junto com a mudança do base. O `.env` do golden é fixo e mínimo, porque o
 * `config` inlina o `.env` em `environment`.
 */
it('[CT-34] a configuração efetiva do base é a do golden versionado', function (): void {
    $resultado = composeConfig(dotenvDoCaso(['COMPOSE_PROJECT_NAME' => 'starter-kit']), ['docker-compose.yml' => 'docker-compose.yml']);

    expect($resultado['codigo'])->toBe(0, $resultado['erro']);

    $atual   = normalizarConfiguracao($resultado['config'], $resultado['raiz']);
    $arquivo = base_path('tests/Kit/fixtures/compose-base.json');

    if (getenv('GOLDEN_COMPOSE_REGENERAR') === '1') {
        file_put_contents($arquivo, json_encode($atual, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
    }

    expect($arquivo)->toBeFile();

    $golden = normalizarConfiguracao(json_decode((string) file_get_contents($arquivo), true), '');

    expect($atual['name'])->toBe($golden['name'])
        ->and(array_keys($atual['services']))->toEqualCanonicalizing(array_keys($golden['services']))
        ->and($atual['networks'] ?? [])->toBe($golden['networks'] ?? [])
        ->and($atual['volumes'] ?? [])->toBe($golden['volumes'] ?? []);

    $chaves = ['ports', 'command', 'restart', 'env_file', 'environment', 'volumes', 'profiles', 'depends_on', 'healthcheck', 'user'];

    foreach ($golden['services'] as $servico => $esperado) {
        foreach ($chaves as $chave) {
            expect($atual['services'][$servico][$chave] ?? null)->toBe($esperado[$chave] ?? null, "services.{$servico}.{$chave} divergiu do golden.");
        }

        expect($atual['services'][$servico]['build']['target'] ?? null)->toBe($esperado['build']['target'] ?? null, "services.{$servico}.build.target divergiu do golden.");
    }
})->group('kit')->skip(fn (): bool => ! composeDisponivel(), 'CLI do Docker Compose ausente');

it('[CT-35] o nginx.conf não passa a honrar cabeçalho de proxy por conta própria', function (): void {
    $ativo = semLinhasDeComentario(textoDoArquivo('docker/nginx/nginx.conf'));

    expect($ativo)->not->toContain('fastcgi_param HTTPS', 'X_FORWARDED', 'set_real_ip_from', 'real_ip_header');
})->group('kit');

it('[CT-36] a raiz do kit tem um arquivo de Compose só, que lê o .env literal', function (): void {
    $arquivos = array_values(array_unique(array_merge(
        array_map('basename', glob(base_path('compose*.y*ml')) ?: []),
        array_map('basename', glob(base_path('docker-compose*.y*ml')) ?: []),
    )));

    expect($arquivos)->toBe(['docker-compose.yml']);

    $compose = textoDoArquivo('docker-compose.yml');

    foreach (['app', 'queue', 'scheduler', 'reverb', 'pulse'] as $servico) {
        $bloco = blocoDoServico($compose, $servico);

        expect(preg_match('/^\s*env_file:\s*\.env\s*(#.*)?$/m', $bloco))->toBe(1, "O serviço {$servico} não declara `env_file: .env` literal.");
    }
})->group('kit');

/*
|--------------------------------------------------------------------------
| R2 — O exemplo chega a quem instala; a cópia ativa nunca entra no git
|--------------------------------------------------------------------------
*/

it('[CT-04] o git ignora a cópia ativa e versiona o exemplo', function (string $caminho, bool $ignorado, bool $versionado): void {
    $ignore = new Process(['git', 'check-ignore', '--no-index', '-q', $caminho], base_path());
    $ignore->run();

    expect($ignore->getExitCode() === 0)->toBe($ignorado, "`git check-ignore --no-index {$caminho}` respondeu o contrário do esperado.");

    $lista = new Process(['git', 'ls-files', '--', $caminho], base_path());
    $lista->run();

    expect(trim($lista->getOutput()) !== '')->toBe($versionado, "`git ls-files {$caminho}` respondeu o contrário do esperado.");
})->with([
    'cópia ativa' => ['docker-compose.override.yml', true, false],
    'exemplo'     => ['docker/traefik/docker-compose.override.yml', false, true],
])->group('kit')->skip(fn (): bool => ! naArvoreDoKit(), 'git e .gitignore só existem na árvore do kit');

it('[CT-05] o exemplo está nas duas listas de entrega', function (): void {
    $exemplo = 'docker/traefik/docker-compose.override.yml';

    $cobertoPeloUpdate = array_filter(caminhosDoKit(), static fn (string $caminho): bool => $caminho === $exemplo
        || str_starts_with($exemplo, rtrim($caminho, '/').'/'));

    expect($cobertoPeloUpdate)->not->toBeEmpty('Nenhum caminho do kit:update cobre o exemplo.');

    foreach (explode("\n", textoDoArquivo('.gitattributes')) as $linha) {
        $linha = trim($linha);

        if ($linha === '' || str_starts_with($linha, '#') || ! str_contains($linha, 'export-ignore')) {
            continue;
        }

        $padrao = trim((string) preg_split('/\s+/', $linha)[0], '/');

        $cobre = $padrao === $exemplo
            || str_starts_with($exemplo, $padrao.'/')
            || fnmatch($padrao, $exemplo);

        expect($cobre)->toBeFalse("O .gitattributes tem `{$linha}`, que cobre o exemplo.");
    }
})->group('kit');

it('[CT-37] o kit:update nunca sobrescreve a cópia ativa do servidor', function (): void {
    $copia = 'docker-compose.override.yml';

    foreach (caminhosDoKit() as $caminho) {
        $normalizado = rtrim($caminho, '/');

        expect($normalizado)->not->toBe($copia, "`{$caminho}` é a cópia ativa.")
            ->and(in_array($normalizado, ['', '.', 'docker-compose'], true))->toBeFalse("`{$caminho}` cobre a cópia ativa.")
            ->and(str_starts_with($copia, $normalizado.'/'))->toBeFalse("`{$caminho}` é prefixo da cópia ativa.");
    }

    // Controle: a constante lida é a do kit:update (lista não vazia).
    expect((new ReflectionClass(KitUpdate::class))->getConstant('CAMINHOS_DO_KIT'))->not->toBeEmpty();
})->group('kit');

/*
|--------------------------------------------------------------------------
| R3 — Ligado o override, só muda o que o Traefik e o build precisam
|--------------------------------------------------------------------------
*/

it('[CT-06] a configuração com o override difere do base só nos caminhos permitidos', function (): void {
    $env  = dotenvDoCaso(['COMPOSE_PROJECT_NAME' => 'proj-dev', 'TRAEFIK_HOST' => 'dev.exemplo.test']);
    $base = configDoBase($env);
    $com  = configComOverride($env);

    $rede = chaveDaRedeExterna($com);

    expect($rede)->not->toBeNull('O override não declarou rede externa.');

    $permitidos = [
        '~^services\.nginx\.networks\.'.preg_quote((string) $rede, '~').'(\.|$)~',
        '~^services\.nginx\.labels\.traefik\.~',
        '~^services\.[^.]+\.build\.args\.VITE_REVERB_~',
        '~^networks\.'.preg_quote((string) $rede, '~').'(\.|$)~',
    ];

    $a = folhasDaConfiguracao($base);
    $b = folhasDaConfiguracao($com);

    $diferentes = [];

    foreach (array_unique([...array_keys($a), ...array_keys($b)]) as $caminho) {
        if (($a[$caminho] ?? null) === ($b[$caminho] ?? null)) {
            continue;
        }

        $permitido = false;

        foreach ($permitidos as $padrao) {
            if (preg_match($padrao, $caminho) === 1) {
                $permitido = true;
            }
        }

        if (! $permitido) {
            $diferentes[] = $caminho;
        }
    }

    expect($diferentes)->toBe([], 'O override mudou folhas fora dos caminhos permitidos.');
})->group('kit')->skip(fn (): bool => ! composeDisponivel(), 'CLI do Docker Compose ausente');

it('[CT-07] todo serviço do exemplo existe no base', function (): void {
    /** @return list<string> as chaves de coluna 2 sob `services:` */
    $servicosDe = static function (string $yaml): array {
        $chaves     = [];
        $emServicos = false;

        foreach (explode("\n", semLinhasDeComentario($yaml)) as $linha) {
            if (preg_match('/^\S/', $linha) === 1) {
                $emServicos = preg_match('/^services:\s*$/', $linha) === 1;

                continue;
            }

            if ($emServicos && preg_match('/^  ([A-Za-z0-9_.-]+):\s*(#.*)?$/', $linha, $m) === 1) {
                $chaves[] = $m[1];
            }
        }

        return $chaves;
    };

    $doExemplo = $servicosDe(textoDoArquivo('docker/traefik/docker-compose.override.yml'));
    $doBase    = $servicosDe(textoDoArquivo('docker-compose.yml'));

    expect($doExemplo)->not->toBeEmpty()
        ->and(array_values(array_diff($doExemplo, $doBase)))->toBe([]);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R4 — O nginx entra na rede externa sem sair da própria
|--------------------------------------------------------------------------
*/

it('[CT-08] a rede externa vem do .env, com default my-network', function (?string $valor, string $rede): void {
    $config = configComOverride(dotenvDoCaso(['TRAEFIK_HOST' => 'dev.exemplo.test', 'TRAEFIK_REDE' => $valor]));

    $chave = chaveDaRedeExterna($config);

    expect($chave)->not->toBeNull()
        ->and($config['networks'][$chave]['name'])->toBe($rede)
        ->and($config['networks'][$chave]['external'])->toBeTrue()
        ->and(redesDoServico($config, 'nginx'))->toContain('default', $chave)
        ->and(labelsDoServico($config, 'nginx')['traefik.docker.network'] ?? null)->toBe($rede);
})->with([
    'ausente'         => [null, 'my-network'],
    'vazia ≠ ausente' => ['', 'my-network'],
    'definida'        => ['rede-do-traefik', 'rede-do-traefik'],
])->group('kit')->skip(fn (): bool => ! composeDisponivel(), 'CLI do Docker Compose ausente');

it('[CT-09] só o nginx entra na rede externa', function (): void {
    $config = configComOverride(dotenvDoCaso(['TRAEFIK_HOST' => 'dev.exemplo.test']));

    $chave = chaveDaRedeExterna($config);

    expect($chave)->not->toBeNull();

    foreach (['app', 'queue', 'scheduler', 'reverb', 'pulse', 'pgsql', 'redis'] as $servico) {
        expect($config['services'])->toHaveKey($servico);
        expect(redesDoServico($config, $servico))->toBe(['default'], "O serviço {$servico} não está só na rede default.");
    }

    foreach (array_keys($config['services']) as $servico) {
        if ($servico !== 'nginx') {
            expect(redesDoServico($config, $servico))->not->toContain($chave);
        }
    }
})->group('kit')->skip(fn (): bool => ! composeDisponivel(), 'CLI do Docker Compose ausente');

/*
|--------------------------------------------------------------------------
| R5 — Labels do docker provider com os valores do requisito
|--------------------------------------------------------------------------
*/

it('[CT-10] o nginx carrega router por Host, websecure, TLS e service na porta 80', function (): void {
    $config = configComOverride(dotenvDoCaso(['COMPOSE_PROJECT_NAME' => 'proj-dev', 'TRAEFIK_HOST' => 'dev.exemplo.test']));

    $labels = labelsDoServico($config, 'nginx');

    expect($labels['traefik.enable'] ?? null)->toBe('true')
        ->and($labels['traefik.http.routers.proj-dev.rule'] ?? null)->toBe('Host(`dev.exemplo.test`)')
        ->and($labels['traefik.http.routers.proj-dev.entrypoints'] ?? null)->toBe('websecure')
        ->and($labels['traefik.http.routers.proj-dev.tls'] ?? null)->toBe('true')
        ->and($labels['traefik.http.services.proj-dev.loadbalancer.server.port'] ?? null)->toBe('80');
})->group('kit')->skip(fn (): bool => ! composeDisponivel(), 'CLI do Docker Compose ausente');

/*
|--------------------------------------------------------------------------
| R6 — Router/service únicos por ambiente; hostname nunca fixo
|--------------------------------------------------------------------------
*/

it('[CT-11] o router leva o nome do projeto de cada ambiente', function (?string $valor, string $router): void {
    $config = configComOverride(dotenvDoCaso(['TRAEFIK_HOST' => 'h.exemplo.test', 'COMPOSE_PROJECT_NAME' => $valor]));

    $labels = labelsDoServico($config, 'nginx');

    expect($labels["traefik.http.routers.{$router}.rule"] ?? null)->toBe('Host(`h.exemplo.test`)')
        ->and($labels["traefik.http.services.{$router}.loadbalancer.server.port"] ?? null)->toBe('80');

    foreach (array_keys($labels) as $chave) {
        if (preg_match('/^traefik\.http\.(routers|services|middlewares)\.([^.]+)\./', $chave, $m) === 1) {
            expect(str_starts_with($m[2], $router))->toBeTrue("O objeto `{$m[2]}` do label `{$chave}` não começa por `{$router}`.");
        }

        expect(str_contains($chave, '${'))->toBeFalse("A chave de label `{$chave}` ficou sem interpolar.");
    }
})->with([
    'ambiente 1'      => ['proj-dev', 'proj-dev'],
    'ambiente 2'      => ['proj-homol', 'proj-homol'],
    'ausente (piso)'  => [null, 'starter-kit'],
    'vazia ≠ ausente' => ['', 'starter-kit'],
])->group('kit')->skip(fn (): bool => ! composeDisponivel(), 'CLI do Docker Compose ausente');

it('[CT-12] sem TRAEFIK_HOST o Compose recusa e diz qual chave falta', function (?string $valor): void {
    $resultado = composeConfig(
        dotenvDoCaso(['TRAEFIK_HOST' => $valor]),
        ['docker-compose.yml' => 'docker-compose.yml', 'docker-compose.override.yml' => 'docker/traefik/docker-compose.override.yml'],
    );

    expect($resultado['codigo'])->not->toBe(0)
        ->and($resultado['erro'])->toContain('TRAEFIK_HOST');
})->with([
    'ausente'         => [null],
    'vazia ≠ ausente' => [''],
])->group('kit')->skip(fn (): bool => ! composeDisponivel(), 'CLI do Docker Compose ausente');

it('[CT-13] o exemplo não fixa hostname', function (): void {
    $ativo = semLinhasDeComentario(textoDoArquivo('docker/traefik/docker-compose.override.yml'));

    preg_match_all('~Host\(`([^`]*)`\)~', $ativo, $achados);

    expect($achados[1])->not->toBeEmpty('Controle: nenhum Host( nas linhas ativas.');

    foreach ($achados[1] as $argumento) {
        expect(str_starts_with($argumento, '${TRAEFIK_HOST'))->toBeTrue("`Host(`{$argumento}`)` fixa o hostname.");
    }

    expect($ativo)->not->toContain('fiocruz.br', 'projtec');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R7 — Reverb: duas rotas, nenhuma imposta; a do Traefik não captura painel
|--------------------------------------------------------------------------
*/

it('[CT-14] com o override ativo, o Reverb continua na rota por porta', function (): void {
    $config = configComOverride(dotenvDoCaso(['TRAEFIK_HOST' => 'dev.exemplo.test', 'FORWARD_REVERB_PORT' => '8190']));

    $comTraefik = array_filter(array_keys(labelsDoServico($config, 'reverb')), static fn (string $chave): bool => str_starts_with($chave, 'traefik.'));

    expect($comTraefik)->toBe([])
        ->and(redesDoServico($config, 'reverb'))->not->toContain((string) chaveDaRedeExterna($config));

    $reverb = array_values(array_filter(publicacoesDaConfig($config), static fn (array $p): bool => $p['servico'] === 'reverb'));

    expect($reverb)->toHaveCount(1)
        ->and($reverb[0]['publicada'])->toBe('8190')
        ->and($reverb[0]['alvo'])->toBe('8090');
})->group('kit')->skip(fn (): bool => ! composeDisponivel(), 'CLI do Docker Compose ausente');

it('[CT-15] o bloco comentado do Reverb traz a rota completa pelo Traefik', function (): void {
    $exemplo = textoDoArquivo('docker/traefik/docker-compose.override.yml');
    $linhas  = array_map('trim', linhasDoBlocoDoReverb($exemplo));

    expect($linhas)->not->toBeEmpty('Os marcadores do bloco do Reverb não existem.');

    $regra = regraDoRouterDoReverb($exemplo);

    expect($regra)->not->toBeNull('O bloco não traz o router `-reverb`.');

    $router = '${COMPOSE_PROJECT_NAME:-starter-kit}-reverb';

    expect($linhas)->toContain("- traefik.http.routers.{$router}.rule={$regra}")
        ->and(preg_match(regexDaRegraDoReverb(), (string) $regra))->toBe(1, "A regra `{$regra}` não casa a forma esperada.");

    preg_match('~Host\(`([^`]*)`\)~', (string) $regra, $host);

    expect(str_starts_with($host[1] ?? '', '${TRAEFIK_HOST'))->toBeTrue();

    expect($linhas)->toContain("- traefik.http.services.{$router}.loadbalancer.server.port=8090");

    // O reverb entra na rede externa: o item da lista `networks:` é a chave da rede externa do exemplo.
    expect(preg_match('~^networks:\s*\n\s+([\w-]+):\s*\n(?:\s+\S.*\n)*?\s+external:\s*true~m', $exemplo, $rede))->toBe(1);

    $posicao = array_search('networks:', $linhas, true);

    expect($posicao)->not->toBeFalse();

    $itens = [];

    for ($i = $posicao + 1; $i < count($linhas) && str_starts_with($linhas[$i], '- '); $i++) {
        $itens[] = substr($linhas[$i], 2);
    }

    expect($itens)->toContain($rede[1])
        ->and(count(array_filter($linhas, static fn (string $l): bool => str_starts_with($l, '- traefik.docker.network='))))->toBe(1);
})->group('kit');

it('[CT-16] a regra do Reverb pelo Traefik recorta pela chave e não captura caminho de painel', function (string $chave, string $id, string $ausente): void {
    $regraCrua = regraDoRouterDoReverb(textoDoArquivo('docker/traefik/docker-compose.override.yml'));

    expect($regraCrua)->not->toBeNull();

    $regra = interpolarComoOCompose((string) $regraCrua, [
        'TRAEFIK_HOST'   => 'dev.exemplo.test',
        'REVERB_APP_KEY' => $chave,
        'REVERB_APP_ID'  => $id,
    ]);

    expect(preg_match(regexDaRegraDoReverb(), $regra))->toBe(1, "A regra interpolada `{$regra}` não casa a forma esperada.");

    preg_match_all('~PathPrefix\(`([^`]+)`\)~', $regra, $m);

    $prefixos = $m[1];

    foreach ($prefixos as $prefixo) {
        foreach (['/app', '/app/login', '/app/acme/users', '/admin', '/infra'] as $caminho) {
            expect(str_starts_with($caminho, $prefixo))->toBeFalse("`PathPrefix({$prefixo})` captura `{$caminho}`, rota de painel do kit.");
        }
    }

    // Controle positivo: uma regra que não casa nada não passa.
    expect(array_filter($prefixos, static fn (string $p): bool => str_starts_with("/app/{$chave}", $p)))->not->toBeEmpty()
        ->and(array_filter($prefixos, static fn (string $p): bool => str_starts_with("/apps/{$id}/events", $p)))->not->toBeEmpty();

    $this->assertStringNotContainsString($ausente, $regra);
})->with([
    'valores do .env.docker'           => ['starter-kit-key', 'starter-kit', '${'],
    'outra chave: o valor vem do .env' => ['outra-chave', 'outro-id', 'starter-kit-key'],
])->group('kit');

it('[CT-44] descomentar o bloco entre os marcadores liga a rota do Reverb pelo Traefik', function (): void {
    $linhas    = explode("\n", textoDoArquivo('docker/traefik/docker-compose.override.yml'));
    $resultado = [];
    $dentro    = false;

    foreach ($linhas as $linha) {
        if (str_contains($linha, '# <<< reverb-traefik')) {
            $dentro = false;
        }

        $resultado[] = $dentro ? (string) preg_replace('/^(\s*)# ?/', '$1', $linha) : $linha;

        if (str_contains($linha, '# >>> reverb-traefik')) {
            $dentro = true;
        }
    }

    $env = dotenvDoCaso([
        'TRAEFIK_HOST'         => 'dev.exemplo.test',
        'COMPOSE_PROJECT_NAME' => 'proj-dev',
        'REVERB_APP_KEY'       => 'chave-x',
        'REVERB_APP_ID'        => 'id-y',
    ]);

    $resposta = composeConfig($env, ['docker-compose.yml' => 'docker-compose.yml'], ['docker-compose.override.yml' => implode("\n", $resultado)]);

    expect($resposta['codigo'])->toBe(0, $resposta['erro']);

    $config = $resposta['config'];
    $rede   = chaveDaRedeExterna($config);

    expect($rede)->not->toBeNull()
        ->and(redesDoServico($config, 'reverb'))->toContain((string) $rede);

    $labelsReverb = labelsDoServico($config, 'reverb');

    expect($labelsReverb['traefik.docker.network'] ?? null)->toBe($config['networks'][$rede]['name']);

    $regra = $labelsReverb['traefik.http.routers.proj-dev-reverb.rule'] ?? '';

    expect(preg_match(regexDaRegraDoReverb(), $regra))->toBe(1, "A regra `{$regra}` não casa a forma esperada.")
        ->and($regra)->toContain('/app/chave-x', '/apps/id-y')
        ->and($labelsReverb['traefik.http.routers.proj-dev-reverb.entrypoints'] ?? null)->toBe('websecure')
        ->and($labelsReverb['traefik.http.routers.proj-dev-reverb.tls'] ?? null)->toBe('true')
        ->and($labelsReverb['traefik.http.services.proj-dev-reverb.loadbalancer.server.port'] ?? null)->toBe('8090');

    $labelsNginx = labelsDoServico($config, 'nginx');

    expect($labelsNginx)->toHaveKey('traefik.http.routers.proj-dev.rule');

    foreach (array_keys($labelsNginx) as $chave) {
        expect(str_contains($chave, '-reverb'))->toBeFalse("O nginx ganhou o label `{$chave}` do Reverb.");
    }
})->group('kit')->skip(fn (): bool => ! composeDisponivel(), 'CLI do Docker Compose ausente');

/*
|--------------------------------------------------------------------------
| R8 — Os quatro VITE_REVERB_* chegam ao npm run build de toda imagem
|--------------------------------------------------------------------------
*/

it('[CT-17] o ARG está no estágio assets, antes do npm run build', function (string $arg): void {
    $recorte = estagioAssetsDoDockerfile();

    expect($recorte)->not->toBe('');

    expect(preg_match('/^ARG '.$arg.'(=.*)?$/m', $recorte, $achado, PREG_OFFSET_CAPTURE))->toBe(1, "`ARG {$arg}` não está no estágio assets.");

    $build = strpos($recorte, 'RUN npm run build');

    expect($build)->not->toBeFalse()
        ->and($achado[0][1])->toBeLessThan($build);
})->with([
    'VITE_REVERB_HOST',
    'VITE_REVERB_PORT',
    'VITE_REVERB_SCHEME',
    'VITE_REVERB_APP_KEY',
])->group('kit');

it('[CT-18] todo serviço com build recebe os quatro args com os valores do .env', function (): void {
    $env = dotenvDoCaso([
        'VITE_REVERB_HOST'    => 'dev.exemplo.test',
        'VITE_REVERB_PORT'    => '443',
        'VITE_REVERB_SCHEME'  => 'https',
        'VITE_REVERB_APP_KEY' => 'chave-dev',
        'TRAEFIK_HOST'        => 'dev.exemplo.test',
    ]);

    $base = configDoBase($env);
    $com  = configComOverride($env);

    $comBuild = array_keys(array_filter($base['services'], static fn (array $s): bool => isset($s['build'])));

    expect($comBuild)->not->toBeEmpty();

    $esperado = [
        'VITE_REVERB_HOST'    => 'dev.exemplo.test',
        'VITE_REVERB_PORT'    => '443',
        'VITE_REVERB_SCHEME'  => 'https',
        'VITE_REVERB_APP_KEY' => 'chave-dev',
    ];

    foreach ($comBuild as $servico) {
        $args = array_map('strval', $com['services'][$servico]['build']['args'] ?? []);
        $args = array_intersect_key($args, $esperado);

        ksort($args);
        ksort($esperado);

        expect($args)->toBe($esperado, "O serviço {$servico} não recebeu os quatro args com os valores do .env.");
    }
})->group('kit')->skip(fn (): bool => ! composeDisponivel(), 'CLI do Docker Compose ausente');

/*
|--------------------------------------------------------------------------
| R9 — Sem build-arg, o build é o de hoje
|--------------------------------------------------------------------------
*/

it('[CT-19] o estágio assets não define VITE_REVERB_* quando ninguém as passa', function (): void {
    $recorte = semLinhasDeComentario(estagioAssetsDoDockerfile());

    expect($recorte)->not->toBe('');

    foreach (explode("\n", $recorte) as $linha) {
        $linha = trim($linha);

        if (preg_match('/^(ARG|ENV|COPY|ADD|RUN)\s/', $linha, $m) !== 1) {
            continue;
        }

        match ($m[1]) {
            'ENV'         => expect(preg_match('/VITE_REVERB_/', $linha))->toBe(0, "ENV atribui VITE_REVERB_*: {$linha}"),
            'ARG'         => expect(preg_match('/^ARG VITE_REVERB_\w+=/', $linha))->toBe(0, "ARG com default: {$linha}"),
            'COPY', 'ADD' => expect(preg_match('/\.env/', $linha))->toBe(0, "COPY/ADD de .env: {$linha}"),
            'RUN'         => expect(preg_match('/VITE_REVERB_\w+=/', $linha))->toBe(0, "RUN atribui VITE_REVERB_*: {$linha}"),
        };
    }
})->group('kit');

it('[CT-38] o base sozinho não passa VITE_REVERB_* ao build', function (): void {
    $config = configDoBase(dotenvDoCaso(['VITE_REVERB_HOST' => 'dev.exemplo.test']));

    foreach ($config['services'] as $servico => $definicao) {
        $chaves = array_filter(array_keys($definicao['build']['args'] ?? []), static fn (string $chave): bool => str_starts_with($chave, 'VITE_REVERB_'));

        expect($chaves)->toBe([], "O serviço {$servico} passa VITE_REVERB_* ao build sem o override.");
    }
})->group('kit')->skip(fn (): bool => ! composeDisponivel(), 'CLI do Docker Compose ausente');

/*
|--------------------------------------------------------------------------
| R13 — Chaves novas só como linha comentada; a sugestão não colide
|--------------------------------------------------------------------------
*/

it('[CT-23] a chave nova existe só como linha comentada', function (string $arquivo, string $chave): void {
    $cru = textoDoArquivo($arquivo);

    expect(preg_match('/^\s*'.$chave.'=/m', semLinhasDeComentario($cru)))->toBe(0, "`{$arquivo}` tem linha ativa de {$chave}.")
        ->and(preg_match('/^#\s*'.$chave.'=/m', $cru))->toBe(1, "`{$arquivo}` não oferece `# {$chave}=`.");

    $ativasDoDocker = array_column(linhasAtivasDoEnv(textoDoArquivo('.env.docker')), 0);

    expect($ativasDoDocker)->not->toContain('COMPOSE_FILE')
        ->and($ativasDoDocker)->not->toContain('COMPOSE_PROFILES');
})->with([
    '.env.example TRUSTED_PROXIES'     => ['.env.example', 'TRUSTED_PROXIES'],
    '.env.docker TRUSTED_PROXIES'      => ['.env.docker', 'TRUSTED_PROXIES'],
    '.env.docker TRAEFIK_HOST'         => ['.env.docker', 'TRAEFIK_HOST'],
    '.env.docker TRAEFIK_REDE'         => ['.env.docker', 'TRAEFIK_REDE'],
    '.env.docker COMPOSE_PROJECT_NAME' => ['.env.docker', 'COMPOSE_PROJECT_NAME'],
    '.env.docker FORWARD_APP_PORT'     => ['.env.docker', 'FORWARD_APP_PORT'],
    '.env.docker FORWARD_DB_PORT'      => ['.env.docker', 'FORWARD_DB_PORT'],
    '.env.docker FORWARD_REDIS_PORT'   => ['.env.docker', 'FORWARD_REDIS_PORT'],
    '.env.docker FORWARD_REVERB_PORT'  => ['.env.docker', 'FORWARD_REVERB_PORT'],
])->group('kit');

it('[CT-24] toda variável que o exemplo interpola é oferecida no .env.docker', function (): void {
    $exemplo = textoDoArquivo('docker/traefik/docker-compose.override.yml');
    $docker  = textoDoArquivo('.env.docker');

    preg_match_all('/\$\{([A-Za-z_][A-Za-z0-9_]*)/', $exemplo, $m);

    // "Exceto os já resolvidos pelo próprio Compose" (04): `VAR` é o placeholder que um comentário do
    // exemplo cita para explicar por que os `build.args` vão em lista — citar não é consumir.
    $excecoes = ['VAR'];

    $nomes = array_values(array_diff(array_unique($m[1]), $excecoes));

    expect($nomes)->not->toBeEmpty();

    foreach ($nomes as $nome) {
        $oferecida = preg_match('/^(#\s*)?'.$nome.'=/m', $docker) === 1;

        expect($oferecida)->toBeTrue("O exemplo interpola `{$nome}` e o .env.docker não a oferece.");
    }

    $appUrls = array_values(array_filter(linhasAtivasDoEnv($docker), static fn (array $l): bool => $l[0] === 'APP_URL'));

    expect($appUrls)->not->toBeEmpty()
        ->and(end($appUrls)[1])->toBe('http://localhost:8000');
})->group('kit');

it('[CT-25] descomentar o bloco do .env.docker não publica duas vezes a mesma porta', function (): void {
    $chaves = ['COMPOSE_PROJECT_NAME', 'APP_URL', 'TRUSTED_PROXIES', 'TRAEFIK_HOST', 'TRAEFIK_REDE', 'FORWARD_APP_PORT', 'FORWARD_DB_PORT', 'FORWARD_REDIS_PORT', 'FORWARD_REVERB_PORT'];

    $linhas = [];

    foreach (explode("\n", textoDoArquivo('.env.docker')) as $linha) {
        if (preg_match('/^#\s*([A-Z][A-Z0-9_]*)=(.*)$/', $linha, $m) === 1 && in_array($m[1], $chaves, true)) {
            $valor = trim((string) preg_replace('/\s+#.*$/', '', $m[2]));
            $valor = $valor === '' && $m[1] === 'TRAEFIK_HOST' ? 'dev.exemplo.test' : $valor;
            $linha = "{$m[1]}={$valor}";
        }

        $linhas[] = $linha;
    }

    $env = implode("\n", $linhas)."\n";

    expect(array_column(linhasAtivasDoEnv($env), 0))->toContain('FORWARD_APP_PORT', 'FORWARD_DB_PORT', 'FORWARD_REDIS_PORT', 'FORWARD_REVERB_PORT');

    $config = configComOverride($env);

    expect(publicacoesDaConfig($config))->not->toBeEmpty()
        ->and(colisoesDePorta(publicacoesDaConfig($config)))->toBe([]);
})->group('kit')->skip(fn (): bool => ! composeDisponivel(), 'CLI do Docker Compose ausente');

it('[CT-39] o .env.docker copiado como sempre, sem o override, não liga o Traefik', function (): void {
    $resultado = composeConfig(
        textoDoArquivo('.env.docker'),
        [...arquivosDeComposeDaRaiz(), 'docker/traefik/docker-compose.override.yml' => 'docker/traefik/docker-compose.override.yml'],
    );

    expect($resultado['codigo'])->toBe(0, $resultado['erro']);

    $config = $resultado['config'];

    foreach (array_keys($config['services']) as $servico) {
        $comTraefik = array_filter(array_keys(labelsDoServico($config, $servico)), static fn (string $chave): bool => str_starts_with($chave, 'traefik.'));

        expect($comTraefik)->toBe([], "O serviço {$servico} ganhou label do Traefik com o .env.docker verbatim.");
    }

    expect(array_values(array_diff(array_keys($config['networks'] ?? []), ['default'])))->toBe([]);
})->group('kit')->skip(fn (): bool => ! composeDisponivel(), 'CLI do Docker Compose ausente');

it('[CT-40] com as quatro FORWARD_* em loopback, toda publicação fica em 127.0.0.1', function (): void {
    $config = configComOverride(dotenvDoCaso([
        'TRAEFIK_HOST'        => 'dev.exemplo.test',
        'FORWARD_APP_PORT'    => '127.0.0.1:8090',
        'FORWARD_DB_PORT'     => '127.0.0.1:5433',
        'FORWARD_REDIS_PORT'  => '127.0.0.1:6380',
        'FORWARD_REVERB_PORT' => '127.0.0.1:8190',
    ]));

    $esperado = ['nginx' => ['8090'], 'pgsql' => ['5433'], 'redis' => ['6380'], 'reverb' => ['8190']];

    foreach ($esperado as $servico => $portas) {
        $publicacoes = array_values(array_filter(publicacoesDaConfig($config), static fn (array $p): bool => $p['servico'] === $servico));

        expect(array_column($publicacoes, 'publicada'))->toBe($portas, "As portas publicadas de {$servico} divergem.");

        foreach ($publicacoes as $publicacao) {
            expect($publicacao['ip'])->toBe('127.0.0.1', "{$servico} publica fora do loopback.");
        }
    }
})->group('kit')->skip(fn (): bool => ! composeDisponivel(), 'CLI do Docker Compose ausente');

/*
|--------------------------------------------------------------------------
| R14 — Três ambientes com a matriz do requisito não disputam porta nem nome
|--------------------------------------------------------------------------
*/

it('[CT-26] dev, teste e homol com a matriz sugerida não colidem', function (): void {
    $ambientes = [
        ['projeto3-dev', 'dev.exemplo.test', '8090', '5433', '6380', '8190'],
        ['projeto3-teste', 'teste.exemplo.test', '9090', '5434', '6381', '8191'],
        ['projeto3-homol', 'homol.exemplo.test', '8080', '5435', '6382', '8192'],
    ];

    $configs = [];

    foreach ($ambientes as [$projeto, $host, $app, $db, $redis, $reverb]) {
        $configs[$projeto] = configComOverride(dotenvDoCaso([
            'COMPOSE_PROJECT_NAME' => $projeto,
            'TRAEFIK_HOST'         => $host,
            'FORWARD_APP_PORT'     => $app,
            'FORWARD_DB_PORT'      => $db,
            'FORWARD_REDIS_PORT'   => $redis,
            'FORWARD_REVERB_PORT'  => $reverb,
        ]));
    }

    $todas = [];

    foreach ($configs as $projeto => $config) {
        foreach (publicacoesDaConfig($config) as $publicacao) {
            $todas[] = [...$publicacao, 'servico' => "{$projeto}/{$publicacao['servico']}"];
        }

        foreach ($config['services'] as $servico => $definicao) {
            expect(array_key_exists('container_name', $definicao))->toBeFalse("O serviço {$servico} fixa container_name.");
        }
    }

    expect($todas)->not->toBeEmpty()
        ->and(colisoesDePorta($todas))->toBe([])
        ->and(array_column($configs, 'name'))->toBe(array_keys($configs));

    $nomesDaRede   = array_map(static fn (array $c): string => (string) $c['networks']['default']['name'], $configs);
    $nomesDeVolume = [];

    foreach ($configs as $projeto => $config) {
        foreach ($config['volumes'] ?? [] as $volume) {
            $nomesDeVolume[$projeto][] = (string) ($volume['name'] ?? '');
        }
    }

    expect(array_unique($nomesDaRede))->toHaveCount(3);

    $achatado = array_merge(...array_values($nomesDeVolume));

    expect($achatado)->not->toBeEmpty()
        ->and(array_unique($achatado))->toHaveCount(count($achatado));
})->group('kit')->skip(fn (): bool => ! composeDisponivel(), 'CLI do Docker Compose ausente');

/*
|--------------------------------------------------------------------------
| R15 — A página existe nos dois idiomas e é alcançável; o CHANGELOG registra
|--------------------------------------------------------------------------
*/

it('[CT-27] a página do idioma existe e todos os caminhos levam a ela', function (string $idioma, string $readme): void {
    $pagina = 'operacao/deploy-docker-multiambiente.md';

    $paginas = paginasDoSite($idioma);

    expect($paginas)->toHaveKey($pagina);

    // As cláusulas do `Então` são coletadas e afirmadas juntas: a primeira que falha não esconde as outras.
    $faltas = [];

    if (preg_match('/\A---
(.*?)
---/s', $paginas[$pagina], $frente) !== 1
        || preg_match('/^title:\s*\S/m', $frente[1]) !== 1
        || preg_match('/^description:\s*\S/m', $frente[1]) !== 1) {
        $faltas[] = 'a página não tem "title" e "description" no front-matter';
    }

    $secaoDocker = preg_match('/^## Docker\s*$(.*?)(?=^## |\z)/ms', textoDoArquivo($readme), $secao) === 1 ? $secao[1] : '';

    if (preg_match('~\]\([^)\s]*/'.$idioma.'/operacao/deploy-docker-multiambiente\.html\)~', $secaoDocker) !== 1) {
        $faltas[] = "o {$readme} não linka a página dentro de `## Docker`";
    }

    if (! str_contains(textoDoArquivo("docs/{$idioma}/operacao/index.md"), 'deploy-docker-multiambiente')) {
        $faltas[] = "docs/{$idioma}/operacao/index.md não linka a página";
    }

    if (preg_match('/"slug"\s*:\s*"operacao\/deploy-docker-multiambiente"/', textoDoArquivo('site/sidebar.json')) !== 1) {
        $faltas[] = 'site/sidebar.json não tem o slug';
    }

    if (! is_file(base_path("site/public/{$idioma}/operacao/deploy-docker-multiambiente.html"))) {
        $faltas[] = "o stub site/public/{$idioma}/operacao/deploy-docker-multiambiente.html não existe";
    }

    expect($faltas)->toBe([]);
})->with([
    'pt' => ['pt', 'README.md'],
    'en' => ['en', 'README.en.md'],
])->group('kit')->skip(fn (): bool => ! naArvoreDoKit(), 'a página do site, os stubs e o README são do repositório do kit: não viajam no create-project (export-ignore)');

it('[CT-28] o CHANGELOG registra a entrega', function (): void {
    expect(textoDoArquivo('CHANGELOG.md'))->toContain('docker/traefik/docker-compose.override.yml', 'TRUSTED_PROXIES');
})->group('kit')->skip(fn (): bool => ! naArvoreDoKit(), 'CHANGELOG.md é export-ignore: só existe na árvore do kit');

/*
|--------------------------------------------------------------------------
| R16 — A página ensina o mecanismo central, o encaixe no Traefik e o passo a passo
|--------------------------------------------------------------------------
*/

it('[CT-29] a página do idioma carrega cada âncora do procedimento', function (string $idioma, string $ancora): void {
    $pagina = paginaMultiambiente($idioma);
    $blocos = blocosDeCodigoDoMarkdown($pagina);

    switch ($ancora) {
        case 'nome de projeto por ambiente':
            preg_match_all('/^COMPOSE_PROJECT_NAME=([a-z0-9_-]+)$/m', $pagina, $m);

            expect(array_unique($m[1]))->toHaveCount(3, 'Menos de 3 valores distintos de COMPOSE_PROJECT_NAME.');
            break;

        case 'um .env por ambiente':
            $blocoDe = [];

            foreach ($blocos as $indice => $bloco) {
                if (preg_match_all('/^COMPOSE_PROJECT_NAME=([a-z0-9_-]+)$/m', $bloco, $m) > 0) {
                    foreach ($m[1] as $valor) {
                        $blocoDe[$valor] ??= $indice;
                    }
                }
            }

            expect($blocoDe)->toHaveCount(3)
                ->and(array_unique(array_values($blocoDe)))->toHaveCount(3, 'Os 3 valores não estão em blocos de .env distintos.');
            break;

        case 'rede e label obrigatórios':
            expect($pagina)->toContain('traefik.docker.network=', 'external: true');
            break;

        case 'labels do router':
            expect($pagina)->toContain('entrypoints=websecure', 'tls=true', 'loadbalancer.server.port=80');
            break;

        case 'cópia do exemplo':
            $achou = array_filter($blocos, static fn (string $b): bool => preg_match('~cp\s+docker/traefik/docker-compose\.override\.yml\s+(\./)?docker-compose\.override\.yml~', $b) === 1);

            expect($achou)->not->toBeEmpty('Nenhum bloco de código copia o exemplo para a raiz.');
            expect(base_path('docker/traefik/docker-compose.override.yml'))->toBeFile();
            break;

        case 'proxies confiáveis':
            expect($pagina)->toContain('TRUSTED_PROXIES=');
            break;
    }
})->with(idiomasPorItem([
    'nome de projeto por ambiente',
    'um .env por ambiente',
    'rede e label obrigatórios',
    'labels do router',
    'cópia do exemplo',
    'proxies confiáveis',
]))->group('kit')->skip(fn (): bool => ! naArvoreDoKit(), 'a página do site é do repositório do kit: não viaja no create-project (export-ignore)');

it('[CT-41] a página avisa que, com a configuração em cache, TRUSTED_PROXIES vem do ambiente do processo', function (string $idioma, array $cache, string $ambiente): void {
    $secoes = secoesDaPagina(paginaMultiambiente($idioma));

    $achou = array_filter($secoes, static function (array $secao) use ($cache, $ambiente): bool {
        $temCache = false;

        foreach ($cache as $termo) {
            $temCache = $temCache || contemSemCaixa($secao['texto'], $termo);
        }

        return $temCache && contemSemCaixa($secao['texto'], $ambiente) && str_contains($secao['texto'], 'TRUSTED_PROXIES');
    });

    expect($achou)->not->toBeEmpty('Nenhuma seção junta o aviso de cache, o ambiente do processo e TRUSTED_PROXIES.');
})->with([
    'pt' => ['pt', ['config:cache', 'configuração em cache'], 'ambiente do processo'],
    'en' => ['en', ['config:cache', 'cached configuration'], 'process environment'],
])->group('kit')->skip(fn (): bool => ! naArvoreDoKit(), 'a página do site é do repositório do kit: não viaja no create-project (export-ignore)');

/*
|--------------------------------------------------------------------------
| R17 — A página cobre portas, matriz, o build do Reverb e as duas rotas
|--------------------------------------------------------------------------
*/

it('[CT-30] a matriz de portas, o build e as rotas do Reverb estão na página', function (string $idioma, string $item): void {
    $pagina = paginaMultiambiente($idioma);
    $prosa  = prosaDoMarkdown($pagina);
    $blocos = blocosDeCodigoDoMarkdown($pagina);

    switch ($item) {
        case 'linha de app da matriz':
            expect(preg_match('/^\|.*FORWARD_APP_PORT.*\|\s*8090\s*\|\s*9090\s*\|\s*8080\s*\|/m', $pagina))->toBe(1);
            break;

        case 'linha de banco':
            expect(preg_match('/^\|.*FORWARD_DB_PORT.*\|\s*5433\s*\|\s*5434\s*\|\s*5435\s*\|/m', $pagina))->toBe(1);
            break;

        case 'linha de cache':
            expect(preg_match('/^\|.*FORWARD_REDIS_PORT.*\|\s*6380\s*\|\s*6381\s*\|\s*6382\s*\|/m', $pagina))->toBe(1);
            break;

        case 'linha do Reverb':
            expect(preg_match('/^\|.*FORWARD_REVERB_PORT.*\|\s*8190\s*\|\s*8191\s*\|\s*8192\s*\|/m', $pagina))->toBe(1);
            break;

        case 'nota do 8090':
            $linhas = array_filter(explode("\n", $prosa), static fn (string $l): bool => trim($l) !== '' && ! str_starts_with(ltrim($l), '|'));
            $achou  = array_filter($linhas, static fn (string $l): bool => str_contains($l, 'FORWARD_REVERB_PORT') && str_contains($l, '8090'));

            expect($achou)->not->toBeEmpty('Nenhuma linha de prosa liga FORWARD_REVERB_PORT ao 8090.');
            break;

        case 'bind de administração':
            expect($pagina)->toContain('FORWARD_APP_PORT=127.0.0.1:');
            break;

        case 'rota do Reverb pelo Traefik':
            expect(preg_match('/traefik\.http\.routers\.\S*-reverb/', $pagina))->toBe(1);
            break;

        case 'rota do Reverb por porta':
            $foraDaTabela = array_filter(explode("\n", $prosa), static fn (string $l): bool => ! str_starts_with(ltrim($l), '|'));

            expect(str_contains(implode("\n", $foraDaTabela), 'FORWARD_REVERB_PORT='))->toBeTrue();
            break;

        case 'VITE do Reverb no build':
            $achou = array_filter($blocos, static fn (string $b): bool => str_contains($b, 'VITE_REVERB_HOST=')
                && str_contains($b, 'VITE_REVERB_PORT=443')
                && str_contains($b, 'VITE_REVERB_SCHEME=https'));

            expect($achou)->not->toBeEmpty('Nenhum bloco traz os VITE_REVERB_* atrás do Traefik.');
            break;

        case 'rebuild ao mudar o VITE':
            $secoes = secoesDaPagina($pagina);
            $achou  = array_filter($secoes, static fn (array $s): bool => str_contains($s['texto'], 'VITE_REVERB_PORT=443') && str_contains($s['texto'], '--build'));

            expect($achou)->not->toBeEmpty('A seção do bloco VITE não manda reconstruir com --build.');
            break;
    }
})->with(idiomasPorItem([
    'linha de app da matriz',
    'linha de banco',
    'linha de cache',
    'linha do Reverb',
    'nota do 8090',
    'bind de administração',
    'rota do Reverb pelo Traefik',
    'rota do Reverb por porta',
    'VITE do Reverb no build',
    'rebuild ao mudar o VITE',
]))->group('kit')->skip(fn (): bool => ! naArvoreDoKit(), 'a página do site é do repositório do kit: não viaja no create-project (export-ignore)');

it('[CT-42] a matriz diz que as quatro portas são obrigatórias e distintas', function (string $idioma, string $obrigatoria, string $distinta, string $opcional): void {
    $secoes = secoesDaPagina(paginaMultiambiente($idioma));

    $indices = array_keys(array_filter($secoes, static fn (array $s): bool => preg_match('/^\|.*FORWARD_APP_PORT.*\|/m', $s['texto']) === 1));

    expect($indices)->not->toBeEmpty('A tabela da matriz de portas não foi encontrada.');

    $texto   = $secoes[$indices[0]]['texto'];
    $celulas = implode("\n", array_filter(explode("\n", $texto), static fn (string $l): bool => str_starts_with(ltrim($l), '|')));

    expect(contemSemCaixa($texto, $obrigatoria))->toBeTrue("A seção da matriz não diz `{$obrigatoria}`.")
        ->and(contemSemCaixa($texto, $distinta))->toBeTrue("A seção da matriz não diz `{$distinta}`.")
        ->and(contemSemCaixa($celulas, $opcional))->toBeFalse("Uma célula da matriz diz `{$opcional}`.");
})->with([
    'pt' => ['pt', 'obrigatóri', 'distint', 'opcional'],
    'en' => ['en', 'mandatory', 'distinct', 'optional'],
])->group('kit')->skip(fn (): bool => ! naArvoreDoKit(), 'a página do site é do repositório do kit: não viaja no create-project (export-ignore)');

/*
|--------------------------------------------------------------------------
| R18 — A página apresenta as opções A–D e as armadilhas
|--------------------------------------------------------------------------
*/

it('[CT-31] cada opção e cada armadilha tem seu lugar na página', function (string $idioma, string $item): void {
    $pagina = paginaMultiambiente($idioma);
    $secoes = secoesDaPagina($pagina);

    $titulo = static fn (string $letra): string => $idioma === 'pt'
        ? '/\bOp[cç][aã]o\s+'.$letra.'\b/iu'
        : '/\bOption\s+'.$letra.'\b/i';

    $recomendada = $idioma === 'pt' ? 'recomendad' : 'recommended';

    switch ($item) {
        case 'Opção A recomendada':
            $i = indiceDaSecaoPorTitulo($secoes, $titulo('A'));

            expect($i)->not->toBeNull('Nenhum título de seção para a Opção A.');
            expect(contemSemCaixa(textoComSubsecoes($secoes, (int) $i), $recomendada))->toBeTrue();
            break;

        case 'fluxo de atualização':
            $i = indiceDaSecaoPorTitulo($secoes, $titulo('A'));

            expect($i)->not->toBeNull('Nenhum título de seção para a Opção A.');

            $texto = textoComSubsecoes($secoes, (int) $i);
            $pull  = strpos($texto, 'git pull');

            $posicoes = array_filter([
                strpos($texto, '--profile app up -d --build'),
                strpos($texto, './deploy_docker_local.sh'),
            ], static fn (int|false $p): bool => $p !== false);

            expect($pull)->not->toBeFalse()
                ->and($posicoes)->not->toBeEmpty()
                ->and($pull)->toBeLessThan(min($posicoes));
            break;

        case 'opções B, C e D':
            $semTitulo = array_values(array_filter(['B', 'C', 'D'], static fn (string $letra): bool => indiceDaSecaoPorTitulo($secoes, $titulo($letra)) === null));

            expect($semTitulo)->toBe([], 'Opções sem título de seção próprio.');
            break;

        case 'equilíbrio da D':
            $i = indiceDaSecaoPorTitulo($secoes, $titulo('D'));

            expect($i)->not->toBeNull('Nenhum título de seção para a Opção D.');

            $texto = textoComSubsecoes($secoes, (int) $i);

            expect($texto)->toContain('pgsql')->and(str_contains($texto, 'redis'))->toBeTrue();

            $compartilhado = $idioma === 'pt' ? 'compartilhad' : 'shared';
            $frases        = preg_split('/(?<=[.!?])\s+|\n\s*\n/u', (string) preg_replace('/\n(?!\s*\n)/', ' ', $texto)) ?: [];

            $achou = array_filter($frases, static fn (string $f): bool => contemSemCaixa($f, 'llama')
                && contemSemCaixa($f, 'mailpit')
                && contemSemCaixa($f, $compartilhado));

            expect($achou)->not->toBeEmpty('Nenhuma frase da Opção D junta llama, mailpit e o que se compartilha.');
            break;

        case 'armadilhas':
            expect($pagina)->toContain('SESSION_COOKIE', 'APP_KEY', 'APP_URL=https://', 'APP_DEBUG=false');
            break;

        case 'sem SESSION_COOKIE customizado':
            foreach (blocosDeCodigoDoMarkdown($pagina) as $bloco) {
                expect(str_contains($bloco, 'SESSION_COOKIE='))->toBeFalse('Um bloco de código customiza SESSION_COOKIE.');
            }
            break;
    }
})->with(idiomasPorItem([
    'Opção A recomendada',
    'fluxo de atualização',
    'opções B, C e D',
    'equilíbrio da D',
    'armadilhas',
    'sem SESSION_COOKIE customizado',
]))->group('kit')->skip(fn (): bool => ! naArvoreDoKit(), 'a página do site é do repositório do kit: não viaja no create-project (export-ignore)');

/*
|--------------------------------------------------------------------------
| R19 — pt e en são espelho
|--------------------------------------------------------------------------
*/

it('[CT-32] as duas páginas têm a mesma estrutura e os mesmos identificadores', function (): void {
    $pt = paginaMultiambiente('pt');
    $en = paginaMultiambiente('en');

    $titulos = static function (string $pagina): array {
        $n2 = $n3 = 0;

        foreach (linhasDoMarkdown($pagina) as [$linha, $dentro]) {
            if ($dentro) {
                continue;
            }

            $n2 += str_starts_with($linha, '## ') ? 1 : 0;
            $n3 += str_starts_with($linha, '### ') ? 1 : 0;
        }

        return [$n2, $n3];
    };

    $tokens = static function (string $pagina): array {
        preg_match_all('/[A-Z][A-Z0-9_]{2,}=/', $pagina, $a);
        preg_match_all('/traefik\.[a-z0-9.${}:_-]+/', $pagina, $b);

        $todos = array_values(array_unique([...$a[0], ...$b[0]]));
        sort($todos);

        return $todos;
    };

    expect($titulos($en))->toBe($titulos($pt))
        ->and($tokens($en))->toBe($tokens($pt))
        ->and(count(blocosDeCodigoDoMarkdown($en)))->toBe(count(blocosDeCodigoDoMarkdown($pt)));
})->group('kit')->skip(fn (): bool => ! naArvoreDoKit(), 'a página do site é do repositório do kit: não viaja no create-project (export-ignore)');

/*
|--------------------------------------------------------------------------
| R20 — O bundle do kit continua sem Echo
|--------------------------------------------------------------------------
*/

it('[CT-43] nenhum arquivo do bundle importa Echo nem lê VITE_REVERB_*', function (): void {
    $raiz = base_path('resources/js');

    expect($raiz)->toBeDirectory();

    $lidos = 0;

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS)) as $arquivo) {
        if (! $arquivo->isFile()) {
            continue;
        }

        $lidos++;
        $ativo = semComentarios((string) file_get_contents($arquivo->getPathname()));

        expect($ativo)->not->toContain('laravel-echo', 'import.meta.env.VITE_REVERB_');
    }

    expect($lidos)->toBeGreaterThan(0);

    $pacote = json_decode(textoDoArquivo('package.json'), true);

    foreach (['dependencies', 'devDependencies'] as $grupo) {
        $nomes = array_keys($pacote[$grupo] ?? []);

        expect($nomes)->not->toContain('laravel-echo')
            ->and($nomes)->not->toContain('pusher-js');
    }
})->group('kit');

<?php

/**
 * Dependências declaradas × usadas (`composer-unused` + `composer-require-checker`).
 *
 * Casos CT-01..CT-08 de `wikis/specs/feat/verificacao-de-dependencias/verificacao-de-dependencias/
 * 04-casos-de-teste.md`. O oráculo é o Gherkin, não o código.
 */

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Yaml\Yaml;

const VD_VEREDITOS = ['usado por outro caminho', 'sem uso', 'uso direto de dependência só transitiva'];

/** O `composer.json` ou o `composer.lock` decodificado. @return array<string, mixed> */
function vdJson(string $arquivo): array
{
    return json_decode((string) file_get_contents(base_path($arquivo)), true) ?: [];
}

/**
 * Expande um script do composer, seguindo `@script`, até os comandos finais.
 *
 * @param  array<string, mixed>  $scripts
 * @param  list<string>  $inexistentes  recebe as referências sem destino
 * @return list<string>
 */
function vdExpandirScript(array $scripts, string $nome, array &$inexistentes, int $profundidade = 0): array
{
    if (! isset($scripts[$nome])) {
        $inexistentes[] = $nome;

        return [];
    }

    if ($profundidade > 10) {
        return [];
    }

    $finais = [];

    foreach ((array) $scripts[$nome] as $comando) {
        // `@php`, `@putenv`, `@composer` e `@additional_args` são comandos do próprio composer, não scripts (CR-05 do step 9).
        if (is_string($comando) && preg_match('~^@(?!php|putenv|composer|additional_args)~', $comando) === 1) {
            $referencia = preg_split('/\s+/', substr($comando, 1))[0];
            array_push($finais, ...vdExpandirScript($scripts, $referencia, $inexistentes, $profundidade + 1));

            continue;
        }

        $finais[] = (string) $comando;
    }

    return $finais;
}

/**
 * Os passos (com o job) cujo `run` cita uma das ferramentas, em todos os workflows.
 *
 * @return list<array{job: string, indice: int, passo: array<string, mixed>, passos: list<array<string, mixed>>}>
 */
function vdPassosDasFerramentas(): array
{
    $achados = [];

    foreach (glob(base_path('.github/workflows/*.{yml,yaml}'), GLOB_BRACE) ?: [] as $arquivo) {
        $workflow = Yaml::parse((string) file_get_contents($arquivo));

        foreach ($workflow['jobs'] ?? [] as $job => $definicao) {
            foreach ($definicao['steps'] ?? [] as $indice => $passo) {
                $run = (string) ($passo['run'] ?? '');

                if (preg_match('/composer-unused|composer-require-checker|deps:check/', $run)) {
                    $achados[] = ['job' => (string) $job, 'indice' => $indice, 'passo' => $passo, 'passos' => $definicao['steps']];
                }
            }
        }
    }

    return $achados;
}

/**
 * As linhas da tabela de triagem de `wikis/qualidade-de-codigo.md`: pacote, veredito, evidência
 * e recomendação (quarta coluna, se houver). Marcação `**` e crases saem das células.
 *
 * @return list<array{pacote: string, veredito: string, evidencia: string, recomendacao: string}>
 */
function vdLinhasDaTriagem(): array
{
    $linhas = [];

    foreach (secoesDoMarkdown('wikis/qualidade-de-codigo.md') as $secao) {
        if (! str_starts_with($secao, 'A triagem de') && ! str_contains(strtok($secao, "\n"), 'triagem')) {
            continue;
        }

        foreach (explode("\n", $secao) as $linha) {
            if (! str_starts_with(trim($linha), '|')) {
                continue;
            }

            $celulas = array_map(
                static fn (string $c): string => trim(str_replace(['**', '`'], '', $c)),
                explode('|', trim(trim($linha), '|')),
            );

            if (! preg_match('~^[a-z0-9._-]+/[a-z0-9._-]+$~i', $celulas[0] ?? '')) {
                continue;
            }

            $linhas[] = [
                'pacote'       => $celulas[0],
                'veredito'     => mb_strtolower($celulas[1] ?? ''),
                'evidencia'    => $celulas[2] ?? '',
                'recomendacao' => $celulas[3] ?? '',
            ];
        }
    }

    return $linhas;
}

/** O código PHP do `composer-unused.php` sem comentário. */
function vdConfiguracaoSemComentario(): string
{
    $codigo = (string) file_get_contents(base_path('composer-unused.php'));
    $codigo = (string) preg_replace('~/\*.*?\*/~s', '', $codigo);

    return (string) preg_replace('~^\s*//.*$~m', '', $codigo);
}

/** @return list<string> */
function vdPacotesExcluidos(): array
{
    preg_match_all('~[\'"]([a-z0-9._-]+/[a-z0-9._-]+)[\'"]~i', vdConfiguracaoSemComentario(), $m);

    return array_values(array_unique($m[1]));
}

it('[CT-01] a ferramenta é dependência de desenvolvimento travada no lock', function (string $pacote): void {
    $composer = vdJson('composer.json');
    $lock     = vdJson('composer.lock');
    $nomes    = static fn (array $lista): array => array_column($lista, 'name');

    expect($composer['require-dev'] ?? [])->toHaveKey($pacote)
        ->and($composer['require'] ?? [])->not->toHaveKey($pacote)
        ->and($nomes($lock['packages-dev'] ?? []))->toContain($pacote)
        ->and($nomes($lock['packages'] ?? []))->not->toContain($pacote);
})->with([
    'composer-unused'          => ['icanhazstring/composer-unused'],
    'composer-require-checker' => ['maglnet/composer-require-checker'],
])->group('kit');

it('[CT-02] deps:check alcança composer-unused e composer-require-checker', function (): void {
    $inexistentes = [];
    $finais       = vdExpandirScript(vdJson('composer.json')['scripts'] ?? [], 'deps:check', $inexistentes);
    $texto        = implode("\n", $finais);

    expect($inexistentes)->toBe([], 'referência @script sem destino: '.implode(', ', $inexistentes))
        ->and($texto)->toContain('composer-unused')
        ->and($texto)->toContain('composer-require-checker');
})->group('kit');

it('[CT-03] o passo do CI roda as duas do vendor/bin, depois do composer install', function (): void {
    $passos = vdPassosDasFerramentas();
    expect($passos)->not->toBeEmpty('nenhum passo de CI roda as ferramentas');

    $scripts      = vdJson('composer.json')['scripts'] ?? [];
    $inexistentes = [];

    foreach ($passos as $achado) {
        $run           = (string) $achado['passo']['run'];
        $semComentario = implode("\n", array_filter(
            explode("\n", $run),
            static fn (string $l): bool => ! str_starts_with(ltrim($l), '#'),
        ));

        // Texto que o passo efetivamente alcança: o próprio run + os scripts do composer que ele chama.
        $alcancado = $semComentario;
        if (preg_match_all('~composer\s+(?:run(?:-script)?\s+)?([a-z0-9:_-]+)~i', $semComentario, $m)) {
            foreach ($m[1] as $script) {
                if (isset($scripts[$script])) {
                    $alcancado .= "\n".implode("\n", vdExpandirScript($scripts, $script, $inexistentes));
                }
            }
        }

        expect($alcancado)->toContain('composer-unused')
            ->and($alcancado)->toContain('composer-require-checker')
            ->and($achado['passo'])->not->toHaveKey('uses')
            ->and($semComentario)->not->toContain('.phar')
            ->and($semComentario)->not->toContain('composer global');

        $instalou  = false;
        $semNoDev  = true;
        foreach (array_slice($achado['passos'], 0, $achado['indice']) as $anterior) {
            $anteriorRun = (string) ($anterior['run'] ?? '');
            if (str_contains($anteriorRun, 'composer install')) {
                $instalou = true;
                $semNoDev = $semNoDev && ! str_contains($anteriorRun, '--no-dev');
            }
        }

        expect($instalou)->toBeTrue("job {$achado['job']}: nenhum passo 'composer install' antes do passo das ferramentas")
            ->and($semNoDev)->toBeTrue("job {$achado['job']}: o composer install anterior usa --no-dev")
            ->and($achado['job'])->toBe('qualidade')
            ->and($achado['passo'])->not->toHaveKey('if');
    }
})->skip(fn (): bool => ! naArvoreDoKit(), 'lê .github/, que não viaja no create-project')->group('kit');

it('[CT-04] com achado ou ferramenta quebrada, o passo publica o que as duas dizem no resumo e não reprova o job', function (array $unused, array $checker, array $esperado): void {
    $passos = array_values(array_filter(
        vdPassosDasFerramentas(),
        static fn (array $a): bool => str_contains((string) $a['passo']['run'], 'composer-unused')
            || str_contains((string) $a['passo']['run'], 'deps:check'),
    ));
    expect($passos)->not->toBeEmpty('nenhum passo de CI roda as ferramentas');

    $passo = $passos[0]['passo'];
    $run   = str_replace('${{ github.workspace }}', base_path(), (string) $passo['run']);

    // Ferramentas FALSAS num diretório temporário: a saída e o código de cada uma são controlados.
    $raiz = sys_get_temp_dir().DIRECTORY_SEPARATOR.'vd-ct04-'.bin2hex(random_bytes(4));
    mkdir($raiz.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'bin', 0777, true);

    foreach (['composer-unused' => $unused, 'composer-require-checker' => $checker] as $nome => $falsa) {
        $corpo = "#!/usr/bin/env bash\n";
        $corpo .= $falsa['stdout'] !== '' ? 'echo '.escapeshellarg($falsa['stdout'])."\n" : '';
        $corpo .= $falsa['stderr'] !== '' ? 'echo '.escapeshellarg($falsa['stderr'])." >&2\n" : '';
        $corpo .= 'exit '.$falsa['codigo']."\n";
        $arquivo = $raiz.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.$nome;
        file_put_contents($arquivo, $corpo);
        chmod($arquivo, 0755);
    }

    $resumo = $raiz.DIRECTORY_SEPARATOR.'resumo.md';
    file_put_contents($resumo, '');

    try {
        $resultado = Process::path($raiz)->timeout(60)
            ->env(['GITHUB_STEP_SUMMARY' => strtr($resumo, [chr(92) => '/'])])
            ->run(['bash', '-eo', 'pipefail', '-c', $run]);
        $codigo   = $resultado->exitCode();
        $conteudo = (string) file_get_contents($resumo);
    } finally {
        File::deleteDirectory($raiz);
    }

    expect($codigo === 0 || ($passo['continue-on-error'] ?? false) === true)
        ->toBeTrue("o passo saiu com {$codigo} e não declara continue-on-error: true");

    foreach ($esperado as $trecho) {
        expect(str_contains($conteudo, $trecho))->toBeTrue("o resumo não contém '{$trecho}':\n{$conteudo}");
    }
})->with([
    'as duas com achado' => [
        ['stdout' => "Used packages\nawcodes/filament-badgeable-column", 'stderr' => '', 'codigo' => 1],
        ['stdout' => "unknown symbols\nFoo\Bar\Simbolo", 'stderr' => '', 'codigo' => 1],
        ['Used packages', 'awcodes/filament-badgeable-column', 'unknown symbols', 'Foo\Bar\Simbolo'],
    ],
    'a primeira ausente e a segunda ainda roda' => [
        ['stdout' => '', 'stderr' => 'composer-unused: erro-da-primeira', 'codigo' => 127],
        ['stdout' => "unknown symbols\nFoo\Bar\Simbolo", 'stderr' => '', 'codigo' => 1],
        ['erro-da-primeira', 'unknown symbols', 'Foo\Bar\Simbolo'],
    ],
    'a segunda escreve o erro só em stderr' => [
        ['stdout' => "Used packages\nawcodes/filament-badgeable-column", 'stderr' => '', 'codigo' => 1],
        ['stdout' => '', 'stderr' => 'erro-da-segunda', 'codigo' => 1],
        ['Used packages', 'awcodes/filament-badgeable-column', 'erro-da-segunda'],
    ],
])->skip(fn (): bool => ! naArvoreDoKit(), 'lê .github/, que não viaja no create-project')->group('kit');

it('[CT-05] o arquivo de configuração do composer-unused está nas duas rotas de entrega', function (): void {
    $arquivo = 'composer-unused.php';
    expect(file_exists(base_path($arquivo)))->toBeTrue('o arquivo de configuração não existe na raiz')
        ->and(caminhosDoKit())->toContain($arquivo);

    $ignorados = array_filter(
        explode("\n", (string) file_get_contents(base_path('.gitattributes'))),
        static fn (string $l): bool => ! str_starts_with(ltrim($l), '#') && str_contains($l, 'export-ignore'),
    );

    $comandos = implode("\n", array_merge(
        array_map(static fn ($c): string => (string) $c, array_merge(...array_values(array_map(
            static fn ($v): array => (array) $v,
            array_filter(vdJson('composer.json')['scripts'] ?? [], static fn (string $k): bool => str_starts_with($k, 'deps:'), ARRAY_FILTER_USE_KEY),
        )))),
        array_map(static fn (array $a): string => (string) $a['passo']['run'], vdPassosDasFerramentas()),
    ));
    preg_match_all('~--config-file[= ]+([^\s"\']+)~', $comandos, $configs);

    foreach ($configs[1] as $configurado) {
        expect(in_array(ltrim($configurado, './'), caminhosDoKit(), true))->toBeTrue("--config-file aponta para '{$configurado}', fora de caminhosDoKit()");
    }

    foreach ($ignorados as $linha) {
        $padrao = trim((string) preg_split('/\s+/', trim($linha))[0], '/');
        $cobre  = $padrao === $arquivo || fnmatch($padrao, $arquivo) || str_starts_with($arquivo, $padrao.'/');
        expect($cobre)->toBeFalse("a linha export-ignore '{$linha}' cobre {$arquivo}");
    }
})->skip(fn (): bool => ! naArvoreDoKit(), 'lê .gitattributes, que não viaja no create-project')->group('kit');

it('[CT-06] todo pacote excluído do composer-unused está na triagem como usado por outro caminho, com motivo', function (): void {
    $excluidos = vdPacotesExcluidos();
    $triagem   = vdLinhasDaTriagem();
    $porPacote = array_column($triagem, null, 'pacote');

    expect($excluidos)->not->toBeEmpty('a lista de exclusão está vazia (oráculo vazio)');

    $scriptsDeps = implode("\n", array_map(
        static fn ($v): string => implode("\n", (array) $v),
        array_filter(vdJson('composer.json')['scripts'] ?? [], static fn (string $k): bool => str_starts_with($k, 'deps:'), ARRAY_FILTER_USE_KEY),
    ));
    $runsDoCi = implode("\n", array_map(static fn (array $a): string => (string) $a['passo']['run'], vdPassosDasFerramentas()));

    expect($scriptsDeps)->not->toContain('--excludePackage')
        ->and($runsDoCi)->not->toContain('--excludePackage')
        ->and(vdConfiguracaoSemComentario())->not->toMatch('~fromString\(\s*[\'"]/|PatternFilter|fromPattern~');

    foreach ($excluidos as $pacote) {
        expect($porPacote)->toHaveKey($pacote);
        expect($porPacote[$pacote]['veredito'])->toBe('usado por outro caminho', "veredito de {$pacote}");
        expect($porPacote[$pacote]['evidencia'])->not->toBe('', "evidência de {$pacote}");
        expect(in_array($porPacote[$pacote]['evidencia'], ['—', '-', 'N/A', 'n/a', '?'], true))->toBeFalse("evidência de {$pacote} é só marcador");
    }

    foreach ($triagem as $linha) {
        if ($linha['veredito'] === 'sem uso') {
            expect($excluidos)->not->toContain($linha['pacote']);
        }
    }
})->skip(fn (): bool => ! naArvoreDoKit(), 'lê wikis/, que não viaja no create-project')->group('kit');

it('[CT-07] a documentação de qualidade traz a triagem com veredito e evidência em cada linha', function (): void {
    $triagem = vdLinhasDaTriagem();

    expect($triagem)->not->toBeEmpty('a tabela de triagem não tem linhas');

    foreach ($triagem as $linha) {
        expect(in_array($linha['veredito'], VD_VEREDITOS, true))->toBeTrue("veredito de {$linha['pacote']}: '{$linha['veredito']}'");
        expect($linha['evidencia'])->not->toBe('', "evidência de {$linha['pacote']}");
        expect(in_array($linha['evidencia'], ['—', '-', 'N/A', 'n/a', '?'], true))->toBeFalse("evidência de {$linha['pacote']} é só marcador");
    }
})->skip(fn (): bool => ! naArvoreDoKit(), 'lê wikis/, que não viaja no create-project')->group('kit');

it('[CT-08] cada linha da triagem com veredito diferente de "usado por outro caminho" tem recomendação registrada', function (): void {
    $achados = array_filter(
        vdLinhasDaTriagem(),
        static fn (array $l): bool => in_array($l['veredito'], ['sem uso', 'uso direto de dependência só transitiva'], true),
    );

    expect($achados)->not->toBeEmpty('precondição: a triagem não tem linha "sem uso" nem "uso direto de dependência só transitiva"');

    foreach ($achados as $linha) {
        expect($linha['recomendacao'])->not->toBe('', "recomendação de {$linha['pacote']}");
    }

    // Achados do composer-require-checker: contagem de símbolos, pacotes só transitivos e ext-*, com recomendação.
    $documento = (string) file_get_contents(base_path('wikis/qualidade-de-codigo.md'));
    $inicio    = (int) strpos($documento, 'A triagem de');
    $triagem   = substr($documento, $inicio, (int) (strpos($documento, "\n## ", $inicio) ?: strlen($documento)) - $inicio);

    expect($triagem)->toMatch('~\d+\s+s[ií]mbolos~u')
        ->and($triagem)->toMatch('~transitiv~iu')
        ->and($triagem)->toContain('ext-')
        ->and($triagem)->toMatch('~recomenda~iu');
})->skip(fn (): bool => ! naArvoreDoKit(), 'lê wikis/, que não viaja no create-project')->group('kit');

it('[CT-09] o deps:check fica fora do gate local', function (): void {
    $scripts      = vdJson('composer.json')['scripts'] ?? [];
    $inexistentes = [];
    $finais       = implode("\n", vdExpandirScript($scripts, 'test', $inexistentes));

    // Os scripts alcançados por "@nome" (e os comandos finais) não podem ser deps:* nem chamar as ferramentas.
    $referencias = [];
    foreach ((array) ($scripts['test'] ?? []) as $comando) {
        if (is_string($comando) && preg_match('~^@([a-z0-9:_-]+)~i', $comando, $m)) {
            $referencias[] = $m[1];
        }
    }

    expect($finais)->not->toContain('composer-unused')
        ->and($finais)->not->toContain('composer-require-checker')
        ->and($finais)->not->toContain('deps:');

    foreach ($referencias as $referencia) {
        expect(str_starts_with($referencia, 'deps:'))->toBeFalse("o script test chama @{$referencia}");
    }
})->group('kit');

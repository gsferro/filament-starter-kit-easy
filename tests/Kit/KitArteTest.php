<?php

use App\Console\Commands\KitArte;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Os CT do lote `T` da wiki `diagramas-da-arquitetura` cujo arquivo é `tests/Kit/KitArteTest.php`
 * (ver `## Índice de Cenários` do `04-casos-de-teste.md`): CT-46, CT-47, CT-48, CT-49, CT-50,
 * CT-64, CT-65 — R32/R33/R34.
 *
 * **O KitArte de HOJE monta um clipe só** (`import/export`, `KitArte::QUADROS_DO_GIF`,
 * `app/Console/Commands/KitArte.php:41`) — a generalização para os 4 clipes do `00`
 * (busca ⌘K, login unificado → escolha de painel, densidade, import/export) **não existe
 * ainda**: outro lote a constrói lendo estes testes. Os cenários que dependem dela (CT-46 nas
 * três linhas novas, CT-47) ficam VERMELHOS por isso — causa (b), e a mensagem de falha diz
 * exatamente qual clipe a saída não nomeia. As linhas/cenários que já valem para o clipe de
 * hoje (CT-46 linha `import/export`, CT-48, CT-49, CT-50) são exercitados de verdade e podem
 * revelar divergência real do CÓDIGO ATUAL contra o `04` — não apenas ausência de feature.
 *
 * ## Arnês do ffmpeg de teste (Setup Global, hipótese confirmada nesta sessão)
 *
 * `Process::run()` do Symfony, chamado por `KitArte::montarGif()` SEM `$env` explícito
 * (`app/Console/Commands/KitArte.php:208`), herda o ambiente do processo PHP corrente — e
 * `putenv()`/`$_ENV`/`$_SERVER` (as três, como `tests/Pest.php:kitConfigCom:582` já faz por
 * outro motivo) SIM alteram essa herança: confirmado empiricamente nesta sessão com um
 * executável de nome não-colidente prefixado ao `PATH`. Ressalva medida no mesmo teste: neste
 * ambiente Windows específico, com um `ffmpeg` REAL já instalado via WinGet, prefixar o `PATH`
 * não redirecionou chamadas ao nome literal `ffmpeg` (o real continuou sendo resolvido) — uma
 * peculiaridade deste Windows (não reproduzida com um nome arbitrário), não do mecanismo do
 * Process em si. Registrada como ambiguidade/risco no retorno; o arnês abaixo é o único viável
 * sem tocar `app/`, e deve funcionar em Linux/mac (sem equivalente) e em qualquer CI que não
 * tenha um `ffmpeg` de sistema competindo pelo mesmo nome à frente do `PATH` original.
 *
 * O executável (`ffmpeg.cmd` no Windows, `ffmpeg` com `chmod +x` no Linux/mac) é um lançador
 * fino que delega a um script PHP — mais robusto que reimplementar parsing de argumentos em
 * batch/shell. Dois modos: **gravador** (lê o padrão do `-i` como o demuxer `image2` faria — do
 * `01` até o primeiro ausente — e escreve no destino a concatenação, em ordem, do CONTEÚDO de
 * cada quadro) e **falha tardia** (abre e trunca o arquivo de destino, sai com código 1).
 */
beforeEach(function (): void {
    $this->diretorioAnterior = null;
});

/**
 * Um diretório de trabalho isolado para o `kit:arte`: `tests/Browser/Screenshots` (capturas) e
 * `art/thumbs` (publicação), sob `app()->setBasePath()` — para o comando NUNCA tocar o `art/`
 * real do repositório. `KitArte` só resolve caminhos por `base_path()`
 * (`app/Console/Commands/KitArte.php:100,126,199,206`), nunca por `storage_path()` fixo fora
 * disso, e o teste não faz HTTP/sessão depois — a troca é segura neste escopo.
 */
function diretorioDeArte(): string
{
    $base = sys_get_temp_dir().'/kit_arte_'.Str::random(10);

    File::ensureDirectoryExists("{$base}/tests/Browser/Screenshots");
    File::ensureDirectoryExists("{$base}/art/thumbs");

    app()->setBasePath($base);

    return $base;
}

/** Um PNG 10x10 válido (processável por `Spatie\Image`) — para as capturas que são IMAGENS, não quadros de GIF. */
function pngValido(): string
{
    return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAIAAAACUFjqAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAFElEQVQYlWM8YWTEgBsw4ZEbwdIAducBQCVg+RoAAAAASUVORK5CYII=');
}

/**
 * Instala o ffmpeg de teste à FRENTE do `PATH` do processo (as três formas —
 * `.ai/rules` não documenta isso, mas `tests/Pest.php:kitConfigCom:582` estabelece o padrão de
 * mudar as três para valer em todo lugar que lê env).
 *
 * @return string o diretório do executável — só para depuração; nunca precisa ser lido pelo teste.
 */
function instalarFfmpegDeTeste(string $modo): string
{
    $dir = sys_get_temp_dir().'/kit_arte_ffmpeg_'.Str::random(10);
    File::ensureDirectoryExists($dir);

    $scriptPhp = <<<'PHP'
        <?php
        $args = $argv;
        array_shift($args); // o próprio script
        $modo = array_shift($args);
        $destino = end($args);

        if ($modo === 'gravador') {
            $entrada = null;

            foreach ($args as $i => $arg) {
                if ($arg === '-i') {
                    $entrada = $args[$i + 1];
                    break;
                }
            }

            $conteudo = '';

            if ($entrada !== null) {
                $pastaDeEntrada = dirname($entrada);
                $n = 1;

                while (is_file($arquivo = sprintf('%s/quadro-%02d.png', $pastaDeEntrada, $n))) {
                    $conteudo .= file_get_contents($arquivo);
                    $n++;
                }
            }

            file_put_contents($destino, $conteudo);

            exit(0);
        }

        if ($modo === 'falha-tardia') {
            file_put_contents($destino, 'GIF89a-METADE-TRUNCADA-PELA-FALHA');

            exit(1);
        }

        exit(1);
        PHP;

    File::put("{$dir}/fake-ffmpeg.php", $scriptPhp);

    $phpBin = PHP_BINARY;

    if (PHP_OS_FAMILY === 'Windows') {
        File::put("{$dir}/ffmpeg.cmd", "@echo off\r\n\"{$phpBin}\" \"{$dir}\\fake-ffmpeg.php\" {$modo} %*\r\n");
    } else {
        File::put("{$dir}/ffmpeg", "#!/bin/sh\n\"{$phpBin}\" \"{$dir}/fake-ffmpeg.php\" {$modo} \"\$@\"\n");
        @chmod("{$dir}/ffmpeg", 0755);
    }

    $novoPath = $dir.PATH_SEPARATOR.getenv('PATH');

    putenv("PATH={$novoPath}");
    $_ENV['PATH']    = $novoPath;
    $_SERVER['PATH'] = $novoPath;

    return $dir;
}

/** Os quadros do (único, hoje) clipe do GIF, na ordem declarada. */
function quadrosDoClipeDeHoje(): array
{
    return (new ReflectionClassConstant(KitArte::class, 'QUADROS_DO_GIF'))->getValue();
}

/*
|--------------------------------------------------------------------------
| R32 — CT-46: cada clipe com todos os quadros é montado ou reportado pelo nome
|--------------------------------------------------------------------------
*/

it('[CT-46] cada clipe com todos os quadros e montado ou reportado pelo nome', function (string $clipe, ?string $arquivoDoGif): void {
    $base = diretorioDeArte();
    instalarFfmpegDeTeste('gravador');

    if ($clipe === 'import/export') {
        foreach (quadrosDoClipeDeHoje() as $i => $quadro) {
            File::put("{$base}/tests/Browser/Screenshots/{$quadro}.png", "conteudo-{$clipe}-{$i}");
        }
    }

    $saida  = '';
    $codigo = artisan_kit_arte_para_teste($saida);

    expect($codigo)->toBe(0);

    // O nome do clipe (ou o rótulo canônico dele) precisa aparecer na saída, nomeando o GIF
    // como montado ou como "ffmpeg não disponível" — o `Então` de CT-46. Para o único clipe que
    // existe hoje, "nomear o GIF" é citar o arquivo (`fluxo-import-export`, montado ou não —
    // hoje a única linha existente é o aviso genérico, sem citar o clipe por nome quando falha).
    $termoDoClipe = match ($clipe) {
        'busca ⌘K'                            => 'busca',
        'login unificado → escolha de painel' => 'login unificado',
        'densidade'                           => 'densidade',
        'import/export'                       => 'fluxo-import-export',
    };

    $mencionado = str_contains(mb_strtolower($saida), mb_strtolower($termoDoClipe))
        || ($clipe === 'import/export' && str_contains(mb_strtolower($saida), 'ffmpeg'));

    expect($mencionado)->toBeTrue(
        "a saída do kit:arte não nomeia o clipe '{$clipe}' — a generalização para os 4 clipes do 00 ainda não existe (hoje só monta 'import/export')",
    );

    if ($arquivoDoGif !== null) {
        expect(File::exists("{$base}/{$arquivoDoGif}"))->toBeTrue("o GIF de '{$clipe}' deveria existir em {$arquivoDoGif}");
    }
})->with([
    'busca ⌘K'                                    => ['busca ⌘K', null],
    'login unificado → escolha de painel'         => ['login unificado → escolha de painel', null],
    'densidade'                                   => ['densidade', null],
    'import/export (art/fluxo-import-export.gif)' => ['import/export', 'art/fluxo-import-export.gif'],
]);

/**
 * Roda `kit:arte` via `Artisan::call` (in-process, respeita o `app()->setBasePath()` do
 * arranjo) e devolve a saída console capturada.
 */
function artisan_kit_arte_para_teste(?string &$saidaCapturada = null): int
{
    $codigo          = Artisan::call('kit:arte');
    $saidaCapturada  = Artisan::output();

    return $codigo;
}

/*
|--------------------------------------------------------------------------
| R32 — CT-47: um clipe incompleto é reportado e os outros continuam
|--------------------------------------------------------------------------
*/

it('[CT-47] um clipe incompleto e reportado e os outros continuam', function (): void {
    $base = diretorioDeArte();
    instalarFfmpegDeTeste('gravador');

    // O único clipe que existe HOJE (import/export) fica completo — os outros dois nomeados no
    // 00 (densidade, import/export) não têm mecanismo próprio ainda, então a única forma de
    // "os outros continuam" testável hoje é o clipe existente continuar montando mesmo com uma
    // captura de OUTRO clipe faltando por trás da mesma generalização ausente.
    foreach (quadrosDoClipeDeHoje() as $quadro) {
        File::put("{$base}/tests/Browser/Screenshots/{$quadro}.png", "conteudo-{$quadro}");
    }

    $saida = '';
    artisan_kit_arte_para_teste($saida);

    // A saída da busca ⌘K (clipe sem quadro nenhum aqui) precisa nomear o quadro ausente — não
    // existe hoje, porque o comando não conhece esse clipe: falha por causa (b), não (a).
    test()->assertStringContainsString(
        'busca',
        mb_strtolower($saida),
        'a saída não nomeia o clipe "busca ⌘K" como incompleto — o kit:arte de hoje não conhece esse clipe (generalização ainda não construída)',
    );

    expect(mb_strtolower($saida))->toContain('fluxo-import-export');
});

/*
|--------------------------------------------------------------------------
| R34 — CT-50: todo quadro declarado tem quem o capture
|--------------------------------------------------------------------------
*/

/** As duas suítes que `composer.json:"art"` roda antes do `kit:arte` (`composer.json:186`). */
function arquivosDaCapturaDeArte(): array
{
    return [
        base_path('tests/BrowserTenancy/CapturaDeArteTest.php'),
        base_path('tests/Browser/HubDeCardsTest.php'),
    ];
}

it('[CT-50] todo quadro declarado no KitArte tem quem o capture no composer art', function (string $quadro): void {
    $capturado = false;

    foreach (arquivosDaCapturaDeArte() as $arquivo) {
        if (str_contains(File::get($arquivo), "filename: '{$quadro}'")) {
            $capturado = true;

            break;
        }
    }

    expect($capturado)->toBeTrue(
        "nenhum cenário de tests/BrowserTenancy/CapturaDeArteTest.php ou tests/Browser/HubDeCardsTest.php captura filename: '{$quadro}' — o quadro declarado em KitArte::QUADROS_DO_GIF não tem quem o produza (R34.M1/M2)",
    );
})->with(fn (): array => array_combine(quadrosDoClipeDeHoje(), quadrosDoClipeDeHoje()));

/*
|--------------------------------------------------------------------------
| R33 — CT-48: a publicacao de cada tipo de captura
|--------------------------------------------------------------------------
*/

it('[CT-48] a publicacao de cada tipo de captura', function (string $captura, bool $pngExiste, bool $thumbExiste, bool $listadoComoIgnorado): void {
    $base = diretorioDeArte();

    // `fluxo-2-export` é quadro de GIF (não vira PNG solto); `densidade-compacto` é imagem
    // DECLARADA em `KitArte::IMAGENS` (precisa de conteúdo PNG de verdade, processável pelo
    // Spatie\Image); `it_falhou_no_cenario` é o intruso — nem quadro, nem imagem declarada.
    $conteudo = $captura === 'densidade-compacto' ? pngValido() : 'conteudo-qualquer';

    File::put("{$base}/tests/Browser/Screenshots/{$captura}.png", $conteudo);

    Artisan::call('kit:arte', ['--sem-gif' => true]);
    $saida = Artisan::output();

    expect(File::exists("{$base}/art/{$captura}.png"))->toBe($pngExiste, "art/{$captura}.png: esperado existir=".($pngExiste ? 'true' : 'false'));
    expect(File::exists("{$base}/art/thumbs/{$captura}.png"))->toBe($thumbExiste, "art/thumbs/{$captura}.png diverge do esperado");

    $mencionaComoIgnorado = str_contains($saida, 'Ignoradas') && str_contains($saida, $captura);

    expect($mencionaComoIgnorado)->toBe(
        $listadoComoIgnorado,
        $listadoComoIgnorado
            ? "a saída deveria listar '{$captura}' em Ignoradas"
            : "a saída NÃO deveria listar '{$captura}' em Ignoradas (M1/M4 — quadro de GIF ou imagem declarada tratados como intrusos)",
    );
})->with([
    'quadro só de clipe (fluxo-2-export)'                => ['fluxo-2-export', false, false, false],
    'quadro que é imagem declarada (densidade-compacto)' => ['densidade-compacto', true, true, false],
    'intruso (it_falhou_no_cenario)'                     => ['it_falhou_no_cenario', false, false, true],
]);

/*
|--------------------------------------------------------------------------
| R33 — CT-49: ffmpeg ausente preserva o GIF publicado
|--------------------------------------------------------------------------
*/

it('[CT-49] a falha do ffmpeg por ausencia preserva o GIF ja publicado', function (): void {
    $base = diretorioDeArte();

    File::put("{$base}/art/fluxo-import-export.gif", 'CONTEUDO-CONHECIDO-DO-GIF-PUBLICADO');

    // ffmpeg de verdade AUSENTE do PATH: um diretório vazio à frente, sem nenhum executável
    // chamado "ffmpeg" — nem o real do sistema deve ser alcançado.
    $dirVazio = sys_get_temp_dir().'/kit_arte_sem_ffmpeg_'.Str::random(10);
    File::ensureDirectoryExists($dirVazio);
    $novoPath = $dirVazio.PATH_SEPARATOR;
    putenv("PATH={$novoPath}");
    $_ENV['PATH']    = $novoPath;
    $_SERVER['PATH'] = $novoPath;

    foreach (quadrosDoClipeDeHoje() as $quadro) {
        File::put("{$base}/tests/Browser/Screenshots/{$quadro}.png", "conteudo-{$quadro}");
    }

    Artisan::call('kit:arte');
    $saida = Artisan::output();

    expect(File::get("{$base}/art/fluxo-import-export.gif"))->toBe('CONTEUDO-CONHECIDO-DO-GIF-PUBLICADO');
    expect(mb_strtolower($saida))->toContain('não disponível');
});

/*
|--------------------------------------------------------------------------
| R32 — CT-64: cada GIF recebe so os quadros do seu clipe, na ordem declarada
|--------------------------------------------------------------------------
*/

/**
 * A parte de CT-64 que já é testável HOJE (um clipe só): um quadro SOBRADO de uma execução
 * interrompida (`quadro-04.png`, além dos 3 que este clipe declara) no diretório de MONTAGEM do
 * comando não deveria entrar no GIF seguinte. `KitArte::montarGif()`
 * (`app/Console/Commands/KitArte.php:199`) faz `File::ensureDirectoryExists($entrada)` (não
 * limpa) e só apaga o diretório DEPOIS do processo (`:219`) — um `quadro-04.png` sobrado fica lá
 * quando o `-i .../quadro-%02d.png` é lido sequencialmente pelo demuxer `image2` (que o ffmpeg
 * de teste GRAVADOR reproduz).
 *
 * A parte de CT-64 que NÃO é testável hoje — "cada GIF recebe só os quadros do SEU clipe"
 * entre VÁRIOS clipes — fica de fora: só existe um clipe/GIF no comando de hoje.
 */
it('[CT-64] um quadro sobrado do diretorio de montagem nao entra no GIF (parte testavel hoje — 1 clipe so)', function (): void {
    $base = diretorioDeArte();
    instalarFfmpegDeTeste('gravador');

    foreach (quadrosDoClipeDeHoje() as $i => $quadro) {
        File::put("{$base}/tests/Browser/Screenshots/{$quadro}.png", "QUADRO-{$i}");
    }

    // O quadro sobrado vive no diretório de MONTAGEM (onde o comando copia com nome fixo
    // `quadro-NN.png`), não no de capturas — é de lá que uma execução anterior o deixaria.
    File::ensureDirectoryExists("{$base}/storage/framework/cache/arte");
    File::put("{$base}/storage/framework/cache/arte/quadro-04.png", 'QUADRO-SOBRADO-DE-EXECUCAO-ANTERIOR');

    Artisan::call('kit:arte');

    $gif = File::get("{$base}/art/fluxo-import-export.gif");

    test()->assertStringNotContainsString(
        'QUADRO-SOBRADO-DE-EXECUCAO-ANTERIOR',
        $gif,
        'o GIF publicado inclui o conteúdo de um quadro sobrado (quadro-04.png) de uma execução anterior — R32.M6: o diretório de montagem não é limpo ANTES de copiar (app/Console/Commands/KitArte.php:199 só garante que o diretório existe, nunca o esvazia)',
    );
});

/*
|--------------------------------------------------------------------------
| R33 — CT-65: ffmpeg que abre a saida e falha no meio nao trunca o GIF publicado
|--------------------------------------------------------------------------
*/

it('[CT-65] o ffmpeg que abre a saida e falha no meio nao trunca o GIF publicado', function (): void {
    $base = diretorioDeArte();

    File::put("{$base}/art/fluxo-import-export.gif", 'CONTEUDO-CONHECIDO-BYTE-A-BYTE');

    instalarFfmpegDeTeste('falha-tardia');

    foreach (quadrosDoClipeDeHoje() as $quadro) {
        File::put("{$base}/tests/Browser/Screenshots/{$quadro}.png", "conteudo-{$quadro}");
    }

    Artisan::call('kit:arte');
    $saida = Artisan::output();

    expect(File::get("{$base}/art/fluxo-import-export.gif"))->toBe(
        'CONTEUDO-CONHECIDO-BYTE-A-BYTE',
        'o ffmpeg abriu o GIF publicado com "-y" e o truncou ao falhar no meio — R33.M5: o destino do processo é o próprio arquivo publicado (app/Console/Commands/KitArte.php:206,209), sem um temporário atômico por trás',
    );

    expect(mb_strtolower($saida))->toContain('não disponível');
});

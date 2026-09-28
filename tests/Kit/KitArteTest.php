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
 * **O KitArte de HOJE já generaliza os 4 clipes do `00`** (busca ⌘K, login unificado → escolha
 * de painel, densidade, import/export) via `KitArte::CLIPES` (`app/Console/Commands/KitArte.php:CLIPES:70`),
 * mais um quinto clipe (`install`, referenciado pelo README) que não está nos exemplos do `04`
 * mas existe no código — coberto aqui como achado adicional, não como divergência.
 *
 * *(RD2-17, 2026-09-28)* A CAPTURA de tela de `busca-spotlight`, `login-unificado` e `install` —
 * que faltava quando este docblock foi escrito pela primeira vez — já existe hoje (`cfafcd2`):
 * `tests/BrowserTenancy/CapturaDeArteTest.php` grava os quadros dos cinco clipes
 * (`busca-spotlight-{1,2}-*`, `login-unificado-{1,2}-*`, `instalacao-{1..4}-*`, além dos três de
 * `fluxo-*` e dos três de `densidade-*`). `[CT-50]` está VERDE para os cinco clipes hoje — nenhum
 * quadro declarado em `KitArte::CLIPES` fica sem captura. `[CT-46]`, `[CT-47]` e `[CT-64]` não
 * dependem dessa captura real de qualquer forma — eles fornecem os quadros por FIXTURE e provam
 * que o `kit:arte` monta um GIF completo de verdade (conteúdo exato, byte a byte, na ordem
 * declarada), não apenas que o nome do clipe aparece na saída.
 *
 * ## Arnês do ffmpeg de teste (Setup Global, hipótese confirmada nesta sessão)
 *
 * `Process::run()` do Symfony, chamado por `KitArte::montarClipe()` (por sua vez chamado em laço
 * por `montarGif()`) SEM `$env` explícito (`app/Console/Commands/KitArte.php:new Process([:323`),
 * herda o ambiente do processo PHP corrente — e
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
 *
 * ## PATH é estado do PROCESSO, não do teste (RD-01)
 *
 * `instalarFfmpegDeTeste()` e o `Dado` "ffmpeg ausente" de `[CT-49]` escrevem em `putenv()`,
 * `$_ENV['PATH']` e `$_SERVER['PATH']` — as três formas que o `resolverFfmpeg()` do comando e o
 * `Process` do Symfony podem ler. Isso é estado do PROCESSO PHP inteiro, não da `Application`
 * (que é recriada a cada teste por `TestCase::createApplication()`): sem restaurar, o `PATH`
 * mutilado de um teste deste arquivo vaza para QUALQUER suíte que rode depois na mesma execução
 * (`--parallel` isola por processo e não sofre; execução em série sofre) — medido nesta sessão
 * com `DeployDockerLocalTest` e `SiteDeDocumentacaoTest`, que chamam `git`/`php` reais e quebram
 * com o `PATH` vazio deixado por `[CT-49]`. O `afterEach` abaixo restaura os três valores
 * originais e apaga os diretórios temporários `kit_arte_*` (nunca eram apagados antes).
 */
beforeEach(function (): void {
    $this->pathOriginalPutenv = getenv('PATH');
    $this->pathOriginalEnv    = $_ENV['PATH'] ?? null;
    $this->pathOriginalServer = $_SERVER['PATH'] ?? null;
});

afterEach(function (): void {
    if ($this->pathOriginalPutenv === false) {
        putenv('PATH');
    } else {
        putenv("PATH={$this->pathOriginalPutenv}");
    }

    if ($this->pathOriginalEnv === null) {
        unset($_ENV['PATH']);
    } else {
        $_ENV['PATH'] = $this->pathOriginalEnv;
    }

    if ($this->pathOriginalServer === null) {
        unset($_SERVER['PATH']);
    } else {
        $_SERVER['PATH'] = $this->pathOriginalServer;
    }

    foreach (File::glob(sys_get_temp_dir().'/kit_arte_*') as $temporario) {
        File::deleteDirectory($temporario);
    }
});

/**
 * Um diretório de trabalho isolado para o `kit:arte`: `tests/Browser/Screenshots` (capturas) e
 * `art/thumbs` (publicação), sob `app()->setBasePath()` — para o comando NUNCA tocar o `art/`
 * real do repositório. `KitArte` só resolve caminhos por `base_path()`
 * (`app/Console/Commands/KitArte.php:base_path():146,172,188,197,309,310`), nunca por
 * `storage_path()` fixo fora disso, e o teste não faz HTTP/sessão depois — a troca é segura neste
 * escopo.
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
 * Um PNG 10x10 válido, com uma cor sólida — para gerar VARIAÇÕES que são PNG parseável mas com
 * bytes distintos entre si. Necessário para `densidade-*`: é IMAGEM DECLARADA em `KitArte::IMAGENS`
 * **e** quadro de clipe ao mesmo tempo, e `publicar()` confere `IMAGENS` primeiro — chama
 * `Image::load()` nela ANTES de checar se é quadro (`app/Console/Commands/KitArte.php:185-202`).
 * Conteúdo de texto solto (como os outros quadros usam) faria o Spatie\Image derrubar o comando
 * inteiro com uma exceção não capturada.
 */
function pngValidoCor(int $tom): string
{
    $imagem = imagecreatetruecolor(10, 10);
    imagefill($imagem, 0, 0, imagecolorallocate($imagem, $tom % 256, $tom % 256, $tom % 256));

    ob_start();
    imagepng($imagem);
    $conteudo = ob_get_clean();

    imagedestroy($imagem);

    return $conteudo;
}

/**
 * O conteúdo de teste para o quadro `$indice` do clipe `$clipe`: PNG de verdade (cor variando
 * pela posição) para `densidade` — porque `densidade-*` também é IMAGEM DECLARADA (ver
 * `pngValidoCor()`) —, texto identificando clipe+posição para os demais.
 */
function conteudoDoQuadroDeTeste(string $clipe, int $indice, string $prefixoDeTexto = 'conteudo'): string
{
    return $clipe === 'densidade'
        ? pngValidoCor(30 + $indice * 70)
        : "{$prefixoDeTexto}-{$clipe}-{$indice}";
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

/** Os quadros do clipe `fluxo-import-export` (o único já com captura real hoje), na ordem declarada. */
function quadrosDoClipeDeHoje(): array
{
    return (new ReflectionClassConstant(KitArte::class, 'QUADROS_DO_GIF'))->getValue();
}

/** Todos os clipes declarados em `KitArte::CLIPES`, chave => quadros na ordem declarada. */
function clipesDoKitArte(): array
{
    return (new ReflectionClassConstant(KitArte::class, 'CLIPES'))->getValue();
}

/** Todos os quadros de todos os clipes de `KitArte::CLIPES`, achatados. */
function todosOsQuadrosDoKitArte(): array
{
    return array_merge(...array_values(clipesDoKitArte()));
}

/*
|--------------------------------------------------------------------------
| R32 — CT-46: cada clipe com todos os quadros é montado ou reportado pelo nome
|--------------------------------------------------------------------------
*/

it('[CT-46] cada clipe com todos os quadros e montado, com o conteudo exato dos seus quadros na ordem declarada', function (string $clipe, array $quadros): void {
    $base = diretorioDeArte();
    instalarFfmpegDeTeste('gravador');

    $conteudoEsperado = '';

    foreach ($quadros as $i => $quadro) {
        $conteudo = conteudoDoQuadroDeTeste($clipe, $i);
        File::put("{$base}/tests/Browser/Screenshots/{$quadro}.png", $conteudo);
        $conteudoEsperado .= $conteudo;
    }

    $saida  = '';
    $codigo = artisan_kit_arte_para_teste($saida);

    expect($codigo)->toBe(0);

    $arquivoDoGif = "art/{$clipe}.gif";

    // Prova de MONTAGEM REAL, não só de menção na saída (R32.M2 — clipe declarado que o laço
    // nunca tenta ficaria com o GIF ausente aqui, mesmo citando o nome em outro lugar).
    expect(File::exists("{$base}/{$arquivoDoGif}"))->toBeTrue(
        "o GIF do clipe '{$clipe}' deveria existir em {$arquivoDoGif} — com todos os seus quadros presentes, ele não pode ficar apenas 'não montado'. Saída: {$saida}",
    );

    expect(File::get("{$base}/{$arquivoDoGif}"))->toBe(
        $conteudoEsperado,
        "o GIF de '{$clipe}' não tem exatamente o conteúdo dos seus próprios quadros, na ordem declarada em KitArte::CLIPES",
    );

    test()->assertStringContainsString(
        $arquivoDoGif,
        $saida,
        "a saída do kit:arte não reporta '{$arquivoDoGif}' como montado",
    );
})->with(fn (): array => array_combine(
    array_keys(clipesDoKitArte()),
    array_map(
        static fn (string $clipe, array $quadros): array => [$clipe, $quadros],
        array_keys(clipesDoKitArte()),
        array_values(clipesDoKitArte()),
    ),
));

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
| R32 — CT-47: um clipe incompleto e reportado e os outros continuam
|--------------------------------------------------------------------------
*/

it('[CT-47] um clipe incompleto e reportado e os outros continuam', function (): void {
    $base = diretorioDeArte();
    instalarFfmpegDeTeste('gravador');

    $clipes = clipesDoKitArte();

    // import/export e densidade COMPLETOS.
    foreach (['fluxo-import-export', 'densidade'] as $completo) {
        foreach ($clipes[$completo] as $i => $quadro) {
            File::put("{$base}/tests/Browser/Screenshots/{$quadro}.png", conteudoDoQuadroDeTeste($completo, $i));
        }
    }

    // busca-spotlight SEM o seu último quadro.
    $quadrosBusca  = $clipes['busca-spotlight'];
    $quadroAusente = end($quadrosBusca);

    foreach (array_slice($quadrosBusca, 0, -1) as $i => $quadro) {
        File::put("{$base}/tests/Browser/Screenshots/{$quadro}.png", conteudoDoQuadroDeTeste('busca-spotlight', $i));
    }

    $saida = '';
    artisan_kit_arte_para_teste($saida);

    test()->assertStringContainsString(
        'busca spotlight',
        mb_strtolower($saida),
        "a saída não nomeia o clipe 'busca-spotlight' como incompleto",
    );

    test()->assertStringContainsString(
        $quadroAusente,
        $saida,
        "a saída não nomeia o quadro ausente '{$quadroAusente}'",
    );

    expect($saida)->toContain('art/fluxo-import-export.gif')
        ->and($saida)->toContain('art/densidade.gif');
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

/**
 * O quadro `$quadro` tem algum cenário do `composer art` que o produz? Duas formas contam, as
 * duas usadas de verdade nesta base: o literal `filename: '<quadro>'` (a maioria dos cenários),
 * e o quadro como VALOR de um `->with([...])` cujo cenário grava com `filename: $variavel`
 * (`densidade-*`, `tests/BrowserTenancy/CapturaDeArteTest.php:372` — `filename: $arquivo`, com
 * `'densidade-confortavel'` etc. só no dataset). As duas produzem o arquivo; procurar só a forma
 * literal acusaria `densidade-*` como sem captura, quando ela já existe.
 */
function quadroTemCapturaDeclarada(string $quadro): bool
{
    foreach (arquivosDaCapturaDeArte() as $arquivo) {
        if (str_contains(File::get($arquivo), "'{$quadro}'")) {
            return true;
        }
    }

    return false;
}

it('[CT-50] todo quadro declarado no KitArte (todos os clipes) tem quem o capture no composer art', function (string $quadro): void {
    expect(quadroTemCapturaDeclarada($quadro))->toBeTrue(
        "nenhum cenário de tests/BrowserTenancy/CapturaDeArteTest.php ou tests/Browser/HubDeCardsTest.php produz o quadro '{$quadro}' — o quadro declarado em KitArte::CLIPES não tem quem o capture (R34.M1/M2).",
    );
})->with(fn (): array => array_combine(todosOsQuadrosDoKitArte(), todosOsQuadrosDoKitArte()));

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

it('[CT-64] cada GIF recebe so os quadros do seu proprio clipe, na ordem declarada, e nenhum leva o quadro sobrado', function (): void {
    $base = diretorioDeArte();
    instalarFfmpegDeTeste('gravador');

    $clipes = clipesDoKitArte();

    // Quadros de TODOS os clipes, cada um com conteúdo que identifica clipe + posição (R32.M5:
    // um clipe com mais quadros do que o seguinte, no mesmo laço, não pode "vazar" para ele).
    foreach ($clipes as $clipe => $quadros) {
        foreach ($quadros as $i => $quadro) {
            File::put("{$base}/tests/Browser/Screenshots/{$quadro}.png", conteudoDoQuadroDeTeste($clipe, $i, 'QUADRO'));
        }
    }

    /*
     * Quadro sobrado no diretório de MONTAGEM do comando (não no de capturas) — de uma execução
     * interrompida, exatamente o que R32.M6 (diretório não limpo antes de copiar) deixaria lá.
     *
     * (RD2-01) O índice tem de ser MAIOR que a quantidade de quadros do PRIMEIRO clipe processado
     * (`fluxo-import-export`, primeira chave de `KitArte::CLIPES`) — E contíguo com a numeração
     * real dele (`quadro-01`, `quadro-02`... sem lacuna), para o ffmpeg (de teste ou real, ambos
     * lendo pelo padrão `%02d` do demuxer `image2`) de fato incluir o sobrado na leitura sequencial
     * do PRIMEIRO clipe, antes de o `finally` de `montarClipe()` limpar o diretório para o próximo.
     * Um índice DENTRO da contagem do primeiro clipe (como `quadro-03` era, com um clipe de 3
     * quadros) é sobrescrito pelo próprio `File::copy()` do clipe — sobrevive à falta de limpeza
     * e ao encontro dela igualmente, e não discrimina nada (achado da 3ª rodada: o caso encolheu
     * de `quadro-04` para `quadro-03` e parou de pegar a regressão).
     */
    $primeiroClipe = array_key_first($clipes);
    $indiceSobrado = count($clipes[$primeiroClipe]) + 1;
    $quadroSobrado = sprintf('quadro-%02d.png', $indiceSobrado);

    File::ensureDirectoryExists("{$base}/storage/framework/cache/arte");
    File::put("{$base}/storage/framework/cache/arte/{$quadroSobrado}", 'QUADRO-SOBRADO-DE-EXECUCAO-ANTERIOR');

    Artisan::call('kit:arte');

    foreach ($clipes as $clipe => $quadros) {
        $gif = File::get("{$base}/art/{$clipe}.gif");

        $esperado = '';
        foreach ($quadros as $i => $quadro) {
            $esperado .= conteudoDoQuadroDeTeste($clipe, $i, 'QUADRO');
        }

        expect($gif)->toBe(
            $esperado,
            "o GIF de '{$clipe}' não lista exatamente os quadros do seu próprio clipe, na ordem declarada — R32.M5 (quadro de outro clipe) ou M6 (diretório de montagem não limpo antes de copiar)",
        );

        test()->assertStringNotContainsString(
            'QUADRO-SOBRADO-DE-EXECUCAO-ANTERIOR',
            $gif,
            "o GIF de '{$clipe}' inclui o conteúdo do quadro sobrado de uma execução anterior — R32.M6",
        );
    }
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
        'o ffmpeg abriu o arquivo de saída e falhou no meio, mas o GIF publicado continua intacto — prova de que o destino do processo é um TEMPORÁRIO fora de art/ (app/Console/Commands/KitArte.php:montarClipe), copiado por cima do publicado só em caso de sucesso (R33)',
    );

    expect(mb_strtolower($saida))->toContain('não disponível');
});

/*
|--------------------------------------------------------------------------
| R32 — RD2-02/RD2-03: uma excecao QUALQUER num clipe nao aborta os demais
|--------------------------------------------------------------------------
| O docblock de `montarClipe()` promete que falha, timeout OU "qualquer outra excecao" do
| processo nao sobem e nao param o `foreach` de `montarGif()` (R32) — mas o `try` de
| `montarClipe()` so tem `finally`, sem `catch`: só a excecao do `$processo->run()` (o
| `ProcessTimedOutException` interno) e tratada. Uma excecao que nasca ANTES do `Process` — aqui,
| `File::copy()` sobre um quadro que e um DIRETORIO, a mesma classe de falha que a revisao mediu
| com uma captura real (`busca-spotlight-2-aberta.png` virando diretorio) — sobe por cima do laco
| inteiro. `ProcessStartFailedException` (o outro repro da revisao) nao foi construida aqui: exige
| apagar o binario do ffmpeg resolvido ENTRE a resolucao e o `Process::run()` deste clipe
| especifico, uma corrida que este arnes (sincrono, um so processo) nao consegue forcar de forma
| deterministica — declarado como nao-falsificavel nesta pilha sem tocar o SO.
*/

it('[RD2-02/RD2-03] uma excecao qualquer ao montar um clipe nao aborta os demais, e o diretorio de montagem fica limpo', function (): void {
    $base = diretorioDeArte();
    instalarFfmpegDeTeste('gravador');

    $clipes = clipesDoKitArte();

    // `fluxo-import-export` e o PRIMEIRO clipe do laco (primeira chave de `KitArte::CLIPES`): o
    // seu primeiro quadro vira um DIRETORIO em vez de um PNG, e `File::copy()` (chamado dentro do
    // `try` de `montarClipe()`, ANTES do `Process`) lanca `ErrorException` ("the first argument to
    // copy() function cannot be a directory") — o Laravel converte o warning do PHP em excecao.
    [$primeiroQuadro] = $clipes['fluxo-import-export'];
    File::ensureDirectoryExists("{$base}/tests/Browser/Screenshots/{$primeiroQuadro}.png");

    foreach (array_slice($clipes['fluxo-import-export'], 1) as $i => $quadro) {
        File::put("{$base}/tests/Browser/Screenshots/{$quadro}.png", conteudoDoQuadroDeTeste('fluxo-import-export', $i + 1));
    }

    // Os DEMAIS clipes, completos — para provar que SERIAM montados se a excecao do primeiro nao
    // abortasse o `foreach` de `montarGif()` inteiro.
    foreach (array_diff_key($clipes, ['fluxo-import-export' => null]) as $clipe => $quadros) {
        foreach ($quadros as $i => $quadro) {
            File::put("{$base}/tests/Browser/Screenshots/{$quadro}.png", conteudoDoQuadroDeTeste($clipe, $i));
        }
    }

    $excecao = null;

    try {
        Artisan::call('kit:arte');
    } catch (Throwable $e) {
        $excecao = $e;
    }

    expect($excecao)->toBeNull(
        'uma excecao ao montar UM clipe (aqui, File::copy() sobre um quadro que e um diretorio) '
        .'nao deveria propagar para fora do kit:arte inteiro — o docblock de montarClipe() promete '
        .'que "qualquer outra excecao" segue para o proximo clipe (R32), nao so falha/timeout do '
        .'ffmpeg. Excecao real: '.($excecao instanceof Throwable ? $excecao::class.': '.$excecao->getMessage() : ''),
    );

    // O `finally` de `montarClipe()` roda mesmo quando o `try` lanca — esta parte da promessa
    // (diretorio de montagem limpo) NAO esta quebrada; a prova fica aqui ao lado da que esta.
    expect(File::isDirectory("{$base}/storage/framework/cache/arte"))->toBeFalse(
        'o diretorio de montagem deveria ficar limpo (finally) mesmo quando um clipe lanca uma excecao qualquer',
    );

    // `densidade` vem DEPOIS de `fluxo-import-export` em `KitArte::CLIPES` e tem todos os seus
    // quadros: hoje ele nem chega a ser tentado, porque a excecao do primeiro aborta o laco antes
    // de `montarGif()` alcancar os clipes seguintes.
    expect(File::exists("{$base}/art/densidade.gif"))->toBeTrue(
        'art/densidade.gif deveria existir — densidade vem depois de fluxo-import-export em KitArte::CLIPES e tem todos os seus quadros; um clipe malsucedido nao pode impedir os demais (R32)',
    );
});

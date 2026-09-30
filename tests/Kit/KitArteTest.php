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
 * por `montarGif()`) SEM `$env` explícito (`app/Console/Commands/KitArte.php:new Process([:332`),
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
 * (`app/Console/Commands/KitArte.php:base_path():146,172,188,197,318,319`), nunca por
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

        // (RD4-01) O ffmpeg real escolhe o muxer pela extensão da saída, ou por `-f` antes dela:
        // `quadro.gif.tmp-…` sem `-f gif` sai com "Unable to choose an output format" antes de
        // abrir o arquivo. Sem esta checagem, o falso aceitava o que o real recusa, e a suíte
        // ficou verde com o `kit:arte` sem montar GIF nenhum.
        $indiceDoFormato = array_search('-f', $args, true);
        $formatoDeclarado = $indiceDoFormato !== false && ($args[$indiceDoFormato + 1] ?? null) === 'gif';

        if (! $formatoDeclarado && ! str_ends_with((string) $destino, '.gif')) {
            fwrite(STDERR, "Unable to choose an output format for '{$destino}'\n");

            exit(1);
        }

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

        if ($modo === 'saida-vira-diretorio') {
            // (RD3-09) "Sucesso" que deixa o TEMPORÁRIO (o que `montarClipe()` confere com
            // `File::exists($temporario)`) sendo um DIRETÓRIO em vez de um arquivo — a mesma
            // classe de falha que RD2-02/RD2-03 já mede para um QUADRO de entrada, aqui do lado
            // da SAÍDA do ffmpeg, para forçar `publicarGif()` a tentar o `rename()` de um
            // diretório por cima do GIF publicado.
            @mkdir($destino, 0777, true);

            exit(0);
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
 *
 * *(RD3-07, 2026-09-28)* `str_contains()` sobre o texto CRU do arquivo passa com o nome só
 * MENCIONADO num docblock — foi assim que renomear o `filename:` real de `busca-spotlight-2-aberta`
 * em `CapturaDeArteTest.php` continuou "encontrando" o quadro, porque o docblock da mesma tela
 * já citava `screenshot('busca-spotlight-2-aberta')` como exemplo. `token_get_all()` em vez de
 * regex/`str_contains()` — o mesmo padrão de `tests/Kit/HelpersDeTesteTest.php` — resolve isso
 * de graça: um comentário (`T_COMMENT`/`T_DOC_COMMENT`) é UM token só, e o literal citado dentro
 * dele nunca vira um token `T_CONSTANT_ENCAPSED_STRING` separado. Só o literal que o PARSER
 * reconhece como string de CÓDIGO (dataset ou argumento nomeado) conta.
 */
function quadroTemCapturaDeclarada(string $quadro): bool
{
    foreach (arquivosDaCapturaDeArte() as $arquivo) {
        foreach (token_get_all(File::get($arquivo)) as $token) {
            if (! is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING) {
                continue;
            }

            // Tira as aspas (simples ou duplas) que o próprio token carrega.
            if (substr($token[1], 1, -1) === $quadro) {
                return true;
            }
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
        'o ffmpeg abriu o arquivo de saída e falhou no meio, mas o GIF publicado continua intacto — prova de que o destino do processo é um TEMPORÁRIO ao lado do publicado, em art/ (app/Console/Commands/KitArte.php:montarClipe), que só troca de lugar com ele por rename() em caso de sucesso (R33)',
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

it('[RD2-02/RD2-03][CT-127] uma excecao qualquer ao montar um clipe nao aborta os demais, e o diretorio de montagem fica limpo', function (): void {
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

/*
|--------------------------------------------------------------------------
| R33 — RD3-09: falha ao PUBLICAR (nao o ffmpeg) avisa a causa certa
|--------------------------------------------------------------------------
| O ffmpeg TERMINA BEM (`$ffmpegOk = true`) — o problema é só a publicação
| (`publicarGif()`: rename, e o copy/rename de contorno) — e hoje essa falha, quando nasce de
| uma EXCEÇÃO (não de um `return false` limpo), escapa do método sem `catch` próprio, sobe pelo
| `try` de `montarClipe()` e cai no `catch (Throwable $e)` do RD2-03: o aviso vira "Falha ao
| montar o clipe" (que manda investigar o ffmpeg) em vez de "Não consegui publicar" (RD2-04) —
| e o GIF publicado ANTES desta execução (se houver) precisa continuar intacto (R33), porque
| `publicarGif()` nunca chegou a substituí-lo.
*/

it('[RD3-09][CT-128] falha ao PUBLICAR o gif (o ffmpeg terminou bem) avisa "nao consegui publicar", nao "falha ao montar o clipe", e preserva o gif anterior', function (): void {
    $base = diretorioDeArte();
    instalarFfmpegDeTeste('saida-vira-diretorio');

    $clipe = array_key_first(clipesDoKitArte());

    // Um GIF publicado ANTES desta execução — para provar que ele sobrevive à falha de
    // publicação (R33), o mesmo oráculo de CT-49/CT-65.
    File::put("{$base}/art/{$clipe}.gif", 'CONTEUDO-ANTIGO-PRESERVADO');

    foreach (clipesDoKitArte()[$clipe] as $i => $quadro) {
        File::put("{$base}/tests/Browser/Screenshots/{$quadro}.png", conteudoDoQuadroDeTeste($clipe, $i));
    }

    Artisan::call('kit:arte');
    $saida = mb_strtolower(Artisan::output());

    $destino = "{$base}/art/{$clipe}.gif";

    // Três asserções, não uma direto no conteúdo: `File::get()` sobre um caminho ausente OU
    // sobre um DIRETÓRIO lança uma exceção do Filesystem (erro do teste, não falha limpa) — a
    // publicação frustrada pode ter apagado o GIF antigo, ou pior, substituído o ARQUIVO por um
    // DIRETÓRIO (o próprio temporário virando o destino via rename/copy malsucedido); as duas
    // causas ficam NOMEADAS antes de qualquer comparação de conteúdo.
    expect(File::isDirectory($destino))->toBeFalse(
        'publicarGif() substituiu o GIF publicado (um ARQUIVO) por um DIRETORIO — o temporario do '
        .'ffmpeg (que e um diretorio neste cenario) nao pode acabar ocupando o lugar do destino '
        .'publicado (R33/RD3-09)',
    );

    expect(File::exists($destino) && File::isFile($destino))->toBeTrue(
        'o GIF publicado antes desta execução sumiu de art/ — publicarGif() não pode apagar o '
        .'destino antes de confirmar que o novo conteúdo foi escrito (R33/RD3-09)',
    );

    expect(File::get($destino))->toBe(
        'CONTEUDO-ANTIGO-PRESERVADO',
        'publicarGif() nao terminou (o temporario do ffmpeg virou diretorio), entao o GIF publicado antes desta execucao nao pode ter sido tocado (R33)',
    );

    $this->assertStringContainsString(
        'não consegui publicar',
        $saida,
        'o ffmpeg terminou bem (o temporario existe) — quem falhou foi a publicacao (rename/copy para art/), entao o aviso tem de ser "nao consegui publicar" (RD2-04/RD3-09), com a saida: '.$saida,
    );

    $this->assertStringNotContainsString(
        'falha ao montar o clipe',
        $saida,
        'a falha de PUBLICACAO (rename/copy) esta escapando como excecao nao-capturada e caindo no aviso generico do RD2-03 — deveria ser capturada DENTRO de publicarGif() (RD3-09), saida: '.$saida,
    );

    expect(File::glob("{$base}/art/*.tmp-*"))->toBe(
        [],
        'nenhum temporario de publicacao pode sobrar em art/ depois de uma falha (RD3-09)',
    );
})->group('kit');

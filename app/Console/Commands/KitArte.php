<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Publica em `art/` o que a captura de tela produziu, e monta o GIF.
 *
 * Segunda metade do `composer art`. A primeira é a suíte
 * `tests/BrowserTenancy/CapturaDeArteTest.php`, que navega de verdade e escreve os PNG em
 * `tests/Browser/Screenshots/` — caminho fixo do `pest-plugin-browser`, não configurável.
 *
 * Este comando só move, redimensiona e monta. Ele não abre navegador: separado assim, dá
 * para refazer as thumbs sem repetir a navegação (que custa minutos).
 *
 * As medidas não são gosto — são as das imagens que já estão no `art/`: **1400x875** no
 * cheio e **760x475** na thumb. A galeria do README põe duas thumbs por linha, e thumb com
 * outra proporção desalinha a tabela.
 */
class KitArte extends Command
{
    protected $signature = 'kit:arte {--sem-gif : Não monta o GIF do fluxo}';

    protected $description = 'Publica as capturas de tela em art/, gera as thumbs e monta o GIF';

    private const LARGURA_DA_THUMB = 760;

    private const ALTURA_DA_THUMB = 475;

    /**
     * Os quadros do clipe `fluxo-import-export`, na ordem. Nome mantido (em vez de dobrar
     * dentro de `CLIPES` direto) porque `tests/Kit/KitArteTest.php` e
     * `tests/Kit/DiagramasDaArquiteturaTest.php` o leem por Reflection pelo nome — mudar o
     * nome quebraria os dois sem nenhuma mudança de comportamento.
     *
     * @var list<string>
     */
    private const QUADROS_DO_GIF = [
        'fluxo-1-listagem',
        'fluxo-2-export',
        'fluxo-3-import',
    ];

    /**
     * Os clipes do GIF: chave = nome do arquivo publicado (`art/{chave}.gif`), valor = os
     * quadros do clipe, na ordem. Eles NÃO vão para `art/` como PNG solto: existem só para
     * virar GIF, e publicá-los dobraria o peso do repositório sem uso no README — salvo
     * quando o quadro também é imagem DECLARADA em `IMAGENS` (`densidade-*`), que continua
     * virando PNG normalmente porque `publicar()` confere `IMAGENS` primeiro.
     *
     * `fluxo-import-export` reaproveita `QUADROS_DO_GIF`: é o nome já referenciado em
     * `docs/pt/recursos/import-export-csv.md` e `docs/en/recursos/import-export-csv.md`
     * (CT-46/CT-51 dependem dele não mudar).
     *
     * *(RD2-17, 2026-09-28)* `busca-spotlight`, `login-unificado` e `install` já têm cenário de
     * captura próprio em `tests/BrowserTenancy/CapturaDeArteTest.php` (`cfafcd2`) — os cinco
     * clipes têm quem produza os quadros deles hoje. Um quadro que faltar (captura removida,
     * cenário quebrado) continua aparecendo como "ausente" na saída, o que é o comportamento
     * correto de R32 (nomeado, não silenciado), não um defeito.
     *
     * @var array<string, list<string>>
     */
    private const CLIPES = [
        'fluxo-import-export' => self::QUADROS_DO_GIF,
        'busca-spotlight'     => [
            'busca-spotlight-1-fechada',
            'busca-spotlight-2-aberta',
        ],
        'login-unificado' => [
            'login-unificado-1-formulario',
            'login-unificado-2-escolha',
        ],
        'densidade' => [
            'densidade-confortavel',
            'densidade-compacto',
            'densidade-denso',
        ],
        'install' => [
            'instalacao-1-inicio',
            'instalacao-2-senha',
            'instalacao-3-progresso',
            'instalacao-4-resumo',
        ],
        'seletor-organizacao' => [
            'seletor-organizacao-1-visivel',
            'seletor-organizacao-2-oculto',
        ],
    ];

    /**
     * As capturas que este comando publica, por nome de arquivo.
     *
     * ## Por que uma lista, e não "tudo o que estiver no diretório"
     *
     * `tests/Browser/Screenshots` é caminho fixo do `pest-plugin-browser` e recebe TUDO: as
     * capturas de arte, os `->screenshot()` de evidência de qualquer CT-B, e os screenshots que o
     * próprio Pest grava automaticamente quando um cenário de navegador FALHA.
     *
     * Publicar tudo fazia a galeria da documentação depender de qual suíte rodou por último. O
     * caso concreto: um screenshot de falha (`it_desenha_a_descricao_...png`) ficava a um
     * `kit:arte` de distância de entrar no `art/`.
     *
     * Arquivo não declarado é **reportado**, nunca publicado e nunca silenciado. Os dois erros
     * possíveis passam a ser visíveis: o intruso aparece como ignorado, e a captura nova que
     * esqueceu a linha aqui aparece como ignorada também, com o nome dela.
     *
     * @var list<string>
     */
    private const IMAGENS = [
        // A tela de login da vitrine (wiki arte-do-login-com-nome-da-aplicacao, passo 5).
        'login',

        // Login social e vínculo de provedor (wiki vinculo-de-provedor-social, passo 9).
        'login-social',
        'admin-configuracoes-login',
        'app-perfil-definir-senha',
        'app-bloqueio-social',
        'admin-users-origem',
        'admin-papeis-import-export',
        'boas-vindas',
        'app-projetos-anexos',
        'export-modal',
        'import-modal',
        'infra-hub',

        // Densidade do layout, os três níveis na mesma tela (wiki layout-compact).
        'densidade-confortavel',
        'densidade-compacto',
        'densidade-denso',

        // Proteção anti-robô (wiki recaptcha-nas-telas-publicas).
        'admin-anti-robo',

        // O interruptor "ocultar o seletor" na tela de configurações
        // (wiki ocultar-seletor-de-organizacao-unica, RQ-05).
        'admin-configuracoes-seletor',

        // As duas telas de login com o desafio no ar, cada uma com chave real do
        // provedor. Só são geradas por quem tem as chaves no ambiente — os cenários
        // se pulam sem elas, e aí estas duas linhas simplesmente não casam com nada.
        'login-turnstile',
        'login-recaptcha-v3',
    ];

    public function handle(): int
    {
        $origem = base_path('tests/Browser/Screenshots');

        if (! File::isDirectory($origem)) {
            $this->components->error("Nada em {$origem}. Rode a captura antes: KIT_ART=1 php artisan test --filter=CapturaDeArte");

            return self::FAILURE;
        }

        $publicadas = $this->publicar($origem);

        if ($publicadas === 0 && $this->option('sem-gif')) {
            $this->components->warn('Nenhuma imagem nova encontrada.');

            return self::SUCCESS;
        }

        if (! $this->option('sem-gif')) {
            $this->montarGif($origem);
        }

        return self::SUCCESS;
    }

    /** Copia para `art/` e gera a thumb de cada captura que não seja quadro só de GIF. */
    private function publicar(string $origem): int
    {
        File::ensureDirectoryExists(base_path('art/thumbs'));

        $quadrosDeClipe = $this->quadrosDeTodosOsClipes();

        $publicadas = 0;
        $ignoradas  = [];

        foreach (File::glob($origem.'/*.png') as $arquivo) {
            $nome = pathinfo($arquivo, PATHINFO_FILENAME);

            /*
             * IMAGENS primeiro, SEMPRE — mesmo quando o nome também é quadro de um clipe
             * (`densidade-*`): senão a generalização por clipe passaria a pular a publicação
             * dessas PNG em silêncio (R33.M1).
             */
            if (in_array($nome, self::IMAGENS, true)) {
                File::copy($arquivo, $destino = base_path("art/{$nome}.png"));

                /*
                 * `fit(Contain)` e não `width()`: a captura já sai em 1400x875, mas o dia em
                 * que alguém mudar a viewport a thumb continua na proporção da galeria — com
                 * borda, não esticada.
                 */
                Image::load($destino)
                    ->fit(Fit::Contain, self::LARGURA_DA_THUMB, self::ALTURA_DA_THUMB)
                    ->save(base_path("art/thumbs/{$nome}.png"));

                $this->components->twoColumnDetail("art/{$nome}.png", 'publicada + thumb');

                $publicadas++;

                continue;
            }

            if (in_array($nome, $quadrosDeClipe, true)) {
                continue;
            }

            $ignoradas[] = $nome;
        }

        /*
         * Reportado, e não silenciado: se a captura nova esqueceu a linha em `IMAGENS`, este aviso
         * é a única coisa que separa "não publiquei" de "publiquei e você não viu".
         */
        if ($ignoradas !== []) {
            $this->components->warn(
                'Ignoradas (não declaradas em KitArte::IMAGENS): '.implode(', ', $ignoradas)
            );
        }

        return $publicadas;
    }

    /**
     * Todos os quadros de todos os clipes, achatados — para `publicar()` não tratar quadro de
     * clipe (que não seja também imagem declarada) como intruso.
     *
     * @return list<string>
     */
    private function quadrosDeTodosOsClipes(): array
    {
        return array_merge(...array_values(self::CLIPES));
    }

    /**
     * Monta o GIF de cada clipe declarado em `CLIPES`, com ffmpeg.
     *
     * **Slideshow, não vídeo.** O `pest-plugin-browser` não grava vídeo, e captura de
     * quadros é o que dá para fazer de forma determinística — o mesmo cenário, os mesmos
     * estados, sempre. Um GIF de gravação real mudaria a cada execução.
     *
     * `palettegen`/`paletteuse` porque GIF é limitado a 256 cores: sem a paleta calculada
     * a partir DESTES quadros, a interface do Filament sai com faixas de cor visíveis.
     *
     * Um clipe incompleto, cujo ffmpeg falha, cujo ffmpeg ESTOURA O TIMEOUT (padrão do
     * `Process`: 60s), ou que lança QUALQUER OUTRA exceção antes disso (RD2-03 — ex.: um quadro
     * que virou diretório e derruba `File::copy()`) é reportado pelo nome e não impede os outros
     * (R32) — o `foreach` nunca para no primeiro incompleto/falho/travado/com exceção;
     * `montarClipe()` trata os quatro casos com o mesmo `try/catch/finally`.
     *
     * Sem ffmpeg no PATH, avisa UMA VEZ e segue sem tentar nenhum clipe — as imagens
     * estáticas já foram publicadas, e tentar cada clipe só repetiria o mesmo aviso.
     */
    private function montarGif(string $origem): void
    {
        $ffmpeg = $this->resolverFfmpeg();

        if ($ffmpeg === null) {
            $this->components->warn('ffmpeg não disponível — GIF não montado. As imagens estáticas foram publicadas.');

            return;
        }

        foreach (self::CLIPES as $clipe => $quadros) {
            $this->montarClipe($clipe, $quadros, $origem, $ffmpeg);
        }
    }

    /**
     * Monta o GIF de UM clipe. Nunca escreve por cima do GIF publicado antes de confirmar
     * sucesso (R33): o ffmpeg recebe como destino um temporário AO LADO do publicado — mesmo
     * diretório, `art/` — e só o sucesso publica esse temporário no lugar do publicado, de forma
     * atômica (`publicarGif()`). O diretório de MONTAGEM (`$entrada`, os quadros de entrada) é
     * limpo ANTES de copiar os quadros deste clipe (R32.M6) — um quadro sobrado de uma execução
     * interrompida, ou do clipe anterior no mesmo laço (R32.M5), não sobrevive para entrar neste
     * GIF.
     *
     * *(RD3-09)* O temporário do ffmpeg vive DENTRO de `art/`, não em `$entrada`: a versão
     * anterior deste docblock media uma premissa falsa — "`rename()` falha entre volumes
     * diferentes (Windows: `false`; Linux: `EXDEV`)". No PHP, `rename()` entre volumes diferentes
     * não falha: ele COPIA o conteúdo e devolve `true` mesmo assim, só que sem ser atômico (um
     * leitor concorrente podia ver o destino publicado truncado a meio caminho). O conserto não é
     * um fallback de copy+rename: é nunca ter dois volumes — com o temporário já em `art/`,
     * `publicarGif()` faz um único `rename()` sempre intra-volume, sempre atômico de verdade.
     *
     * O `try/catch/finally` cobre as QUATRO formas de um clipe não terminar bem — falha do
     * ffmpeg (código != 0), timeout (`ProcessTimedOutException`, o padrão do `Process` é 60s),
     * falha ao PUBLICAR o GIF já pronto (`publicarGif()` retorna `false` — RD2-04/RD3-09) e
     * qualquer OUTRA exceção, do `File::copy()` dos quadros ao próprio `Process` que não consegue
     * iniciar (RD2-03): nos quatro casos o diretório de montagem é limpo do mesmo jeito
     * (`finally`), o temporário do ffmpeg (se sobrou algum em `art/`) some junto, e `montarGif()`
     * segue para o próximo clipe (R32) — travar num clipe não pode significar nunca tentar os
     * outros. O aviso distingue as três causas (RD2-04): "ffmpeg não disponível ou falhou" só
     * quando o PRÓPRIO ffmpeg não terminou bem; "não consegui publicar" quando o ffmpeg terminou
     * bem mas o `rename()` para `art/` falhou; e a exceção crua, nomeando o clipe, para qualquer
     * outra falha (RD2-03) — as três dizem coisas diferentes, e confundi-las mandaria quem lê o
     * aviso investigar o ffmpeg quando o problema era outro.
     *
     * @param  list<string>  $quadros
     */
    private function montarClipe(string $clipe, array $quadros, string $origem, string $ffmpeg): void
    {
        $label = str_replace('-', ' ', $clipe);

        $faltando = array_filter(
            $quadros,
            fn (string $quadro): bool => ! File::exists("{$origem}/{$quadro}.png"),
        );

        if ($faltando !== []) {
            $this->components->warn("Quadros do GIF '{$label}' ausentes: ".implode(', ', $faltando).". GIF de '{$label}' não montado.");

            return;
        }

        $entrada    = base_path('storage/framework/cache/arte');
        $destino    = base_path("art/{$clipe}.gif");
        $temporario = "{$destino}.tmp-".bin2hex(random_bytes(8));
        $ffmpegOk   = false;
        $publicado  = false;

        try {
            File::deleteDirectory($entrada);
            File::ensureDirectoryExists($entrada);

            foreach ($quadros as $indice => $quadro) {
                File::copy("{$origem}/{$quadro}.png", sprintf('%s/quadro-%02d.png', $entrada, $indice + 1));
            }

            /*
             * (QA-13) Os quadros de um clipe podem chegar com alturas diferentes — a captura
             * do `install` agora recorta cada quadro à PRÓPRIA altura do conteúdo, não à do
             * quadro mais alto. O `paletteuse` do ffmpeg descarta em silêncio todo quadro de
             * dimensão divergente do primeiro (medido: `nb_frames` cai para 1 e o GIF sai com
             * um quadro só, sem erro nenhum), então o pad uniforme acontece AQUI, em GD —
             * nunca no filtro do ffmpeg, que aceita só dimensões constantes.
             */
            $this->uniformizarQuadros($entrada, count($quadros));

            $processo = new Process([
                $ffmpeg, '-y',
                '-framerate', '0.6',
                '-i', $entrada.'/quadro-%02d.png',
                '-vf', 'scale=1000:-1:flags=lanczos,split[a][b];[a]palettegen[p];[b][p]paletteuse',
                '-loop', '0',
                // O temporário não termina em `.gif`, e o ffmpeg escolhe o muxer pela extensão:
                // sem `-f gif` ele recusa a saída ("Unable to choose an output format").
                '-f', 'gif',
                $temporario,
            ]);

            try {
                $processo->run();
            } catch (ProcessTimedOutException) {
                // Tratado como qualquer outra falha do ffmpeg logo abaixo — não sobe, não para
                // o `foreach` de `montarGif()`.
            }

            $ffmpegOk = $processo->isSuccessful() && File::exists($temporario);

            if ($ffmpegOk) {
                $publicado = $this->publicarGif($temporario, $destino);
            }
        } catch (Throwable $e) {
            /*
             * RD2-03: qualquer exceção que nasça ANTES do ffmpeg terminar — um quadro que virou
             * diretório (`File::copy()` lança `ErrorException`), o `Process` que não consegue
             * sequer iniciar (`ProcessStartFailedException`) — é reportada pelo NOME do clipe e
             * não impede os demais. O docblock já prometia isso para "qualquer outra exceção";
             * faltava o `catch` que faz a promessa valer.
             */
            $this->components->warn("Falha ao montar o clipe '{$label}': {$e->getMessage()}. GIF de '{$label}' não montado.");

            return;
        } finally {
            // Sempre — sucesso, falha, timeout ou qualquer exceção: nenhum quadro nem GIF
            // temporário deste clipe pode sobreviver para contaminar o próximo (R32.M6).
            File::deleteDirectory($entrada);

            /*
             * (RD3-09) O temporário do ffmpeg mora em `art/`, não em `$entrada` — o
             * `deleteDirectory()` acima não o alcança. Sobra dele quando o PRÓPRIO ffmpeg
             * terminou mal com conteúdo parcial já escrito (timeout, código != 0) OU quando
             * terminou bem mas a publicação falhou (`publicarGif()` já devolveu `false` sem
             * apagar o temporário sozinho — quem chama é dono do ciclo de vida inteiro). Nos
             * demais casos ele nunca chegou a existir, e `File::exists()` já resolve isso.
             */
            if (File::exists($temporario)) {
                File::isDirectory($temporario) ? File::deleteDirectory($temporario) : File::delete($temporario);
            }
        }

        if (! $ffmpegOk) {
            $this->components->warn("ffmpeg não disponível ou falhou — GIF de '{$label}' não montado. As imagens estáticas foram publicadas.");

            return;
        }

        if (! $publicado) {
            // RD2-04: o ffmpeg terminou bem — o problema é a publicação (rename para art/), não
            // o ffmpeg. Dizer "ffmpeg... falhou" aqui mandaria investigar o processo errado.
            $this->components->warn("Não consegui publicar o GIF de '{$label}' — o ffmpeg concluiu, mas o rename para art/ falhou. O GIF anterior, se houver, foi preservado.");

            return;
        }

        $this->components->twoColumnDetail(
            "art/{$clipe}.gif",
            number_format(File::size($destino) / 1024, 0).' KB',
        );
    }

    /**
     * Deixa todos os `quadro-NN.png` do diretório com a MESMA largura e altura — o máximo
     * entre eles — completando os menores embaixo com a cor do próprio fundo (QA-13).
     *
     * Por que em GD e não no `-vf pad` do ffmpeg: o `pad` aceita só dimensões constantes, e
     * medir o maior quadro exigiria um `ffprobe` extra ou um valor chumbado — a soma dos dois
     * motivos pelo qual a captura recorta à própria altura é justamente não chutar número.
     * Aqui `getimagesize()` mede os PNGs já copiados e o menor recebe pad real, com a cor do
     * pixel de canto — que no `install` é o `#0d1117` do `body` da fixture, não um preto
     * avulso.
     *
     * Sem a extensão GD nada acontece: quadros do mesmo tamanho (o resto dos clipes) não
     * precisam dela, e o ffmpeg continua publicando o GIF.
     */
    private function uniformizarQuadros(string $entrada, int $quantidade): void
    {
        if (! extension_loaded('gd')) {
            return;
        }

        $quadros    = [];
        $larguraMax = 0;
        $alturaMax  = 0;

        for ($i = 1; $i <= $quantidade; $i++) {
            $arquivo   = sprintf('%s/quadro-%02d.png', $entrada, $i);
            $dimensoes = getimagesize($arquivo);

            if ($dimensoes === false) {
                continue;
            }

            $quadros[$arquivo] = [$dimensoes[0], $dimensoes[1]];
            $larguraMax        = max($larguraMax, $dimensoes[0]);
            $alturaMax         = max($alturaMax, $dimensoes[1]);
        }

        // Nenhum quadro legível (ou medido em zero): não há referência de tamanho para
        // completar — e `imagecreatetruecolor()` abaixo exige dimensões positivas.
        if ($larguraMax < 1 || $alturaMax < 1) {
            return;
        }

        foreach ($quadros as $arquivo => [$largura, $altura]) {
            if ($largura === $larguraMax && $altura === $alturaMax) {
                continue;
            }

            $origem = imagecreatefrompng($arquivo);

            if ($origem === false) {
                continue;
            }

            $canvas = imagecreatetruecolor($larguraMax, $alturaMax);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);

            // A cor do canto inferior esquerdo do PRÓPRIO quadro: o pad embaixo continua o
            // fundo da página, sem uma faixa de cor estranha à captura.
            $fundo         = (int) imagecolorat($origem, 0, $altura - 1);
            $preenchimento = imagecolorallocate(
                $canvas,
                ($fundo >> 16) & 0xFF,
                ($fundo >> 8) & 0xFF,
                $fundo & 0xFF,
            );

            imagefill($canvas, 0, 0, (int) $preenchimento);
            imagecopy($canvas, $origem, 0, 0, 0, 0, $largura, $altura);
            imagepng($canvas, $arquivo);
            imagedestroy($origem);
            imagedestroy($canvas);
        }
    }

    /**
     * Publica o temporário — já escrito pelo próprio ffmpeg dentro de `art/`, ao LADO do destino
     * (`montarClipe()`) — no lugar do destino, com `rename()`.
     *
     * *(RD3-09)* A versão anterior desta docblock media a premissa errada: "`rename()` falha
     * entre volumes diferentes (Windows: `false`; Linux: `EXDEV`)". No PHP, `rename()` entre
     * volumes diferentes não falha — ele COPIA o arquivo e devolve `true` mesmo assim, só que sem
     * ser atômico. O conserto não é um fallback de copy+rename: é nunca ter dois volumes — o
     * temporário e o destino estão sempre no MESMO diretório (`art/`), portanto sempre no MESMO
     * volume, e `rename()` aqui é sempre intra-volume, sempre atômico de verdade.
     *
     * O temporário tem de ser um ARQUIVO — nunca um diretório (RD3-09: um ffmpeg que "termina
     * bem" mas deixa o destino como diretório, por alguma falha do lado de fora). Medido nesta
     * base: `rename()` de um DIRETÓRIO por cima de um ARQUIVO existente **não falha** no Windows
     * — ele substitui o arquivo pelo diretório, silenciosamente, o oposto de "preservar o GIF
     * anterior" (R33). A guarda (`File::isFile()`) recusa ANTES de chamar `rename()`, então o
     * destino nunca é sequer tocado nesse caso.
     *
     * Qualquer outra falha de publicação — `rename()` devolvendo `false`, ou uma exceção
     * (`ErrorException`, se algum erro do sistema de arquivos escapar da supressão de `@`) — é
     * capturada AQUI DENTRO (RD3-09): quem chama só precisa saber SE publicou, nunca por quê. Uma
     * exceção de publicação que escapasse cairia no `catch` genérico de `montarClipe()` e
     * imprimiria a mensagem ERRADA ("falha ao montar o clipe", que manda investigar o ffmpeg, em
     * vez de "não consegui publicar" — RD2-04).
     */
    private function publicarGif(string $temporario, string $destino): bool
    {
        try {
            if (! File::isFile($temporario)) {
                return false;
            }

            return (bool) @rename($temporario, $destino);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Resolve o caminho do ffmpeg no PATH do ambiente, sem mutar o processo.
     *
     * `Symfony\Component\Process\ExecutableFinder` varre por SUFIXO primeiro e diretório
     * depois: no Windows, com um ffmpeg REAL instalado mais adiante no PATH (ex.: WinGet), a
     * extensão `.exe` é tentada em TODOS os diretórios antes de `.cmd` ser tentada em qualquer
     * um — um ffmpeg de teste `.cmd` à FRENTE do PATH perde para o `.exe` real mais atrás, que
     * não é a ordem que o Windows de fato usa (nem a que o operador espera ao editar o PATH).
     * Varrer aqui, diretório por diretório e SÓ DEPOIS as extensões, resolve isso sem recorrer
     * ao truque de sobrescrever `PATH` do processo por diretório (que mutava estado global e,
     * no Linux, disparava um `command -v` por diretório dentro do `ExecutableFinder` quando o
     * arquivo não existia ali).
     *
     * Fora do Windows, `ffmpeg` é o único candidato por diretório (sem PATHEXT) e precisa
     * também de `is_executable` — `is_file` sozinho aceita um arquivo sem permissão de
     * execução.
     */
    private function resolverFfmpeg(): ?string
    {
        $pathOriginal = getenv('PATH') ?: getenv('Path') ?: '';
        $diretorios   = array_filter(explode(PATH_SEPARATOR, $pathOriginal), fn (string $d): bool => $d !== '');
        $ehWindows    = PHP_OS_FAMILY === 'Windows';
        $extensoes    = $ehWindows ? $this->extensoesDoPathext() : [''];

        foreach ($diretorios as $diretorio) {
            foreach ($extensoes as $extensao) {
                $candidato = rtrim($diretorio, '\\/').DIRECTORY_SEPARATOR."ffmpeg{$extensao}";

                if (! is_file($candidato)) {
                    continue;
                }

                if (! $ehWindows && ! is_executable($candidato)) {
                    continue;
                }

                return $candidato;
            }
        }

        return null;
    }

    /**
     * As extensões de executável do Windows, na ordem de `PATHEXT` — cada uma já com o ponto
     * (`.EXE`, `.CMD`…). Com a variável ausente ou vazia, a mesma lista padrão que o próprio
     * Windows usa quando `PATHEXT` não foi definida.
     *
     * @return list<string>
     */
    private function extensoesDoPathext(): array
    {
        $pathext = getenv('PATHEXT') ?: '.COM;.EXE;.BAT;.CMD';

        return array_values(array_filter(explode(PATH_SEPARATOR, $pathext), fn (string $e): bool => $e !== ''));
    }
}

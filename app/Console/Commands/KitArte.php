<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;
use Symfony\Component\Process\Process;

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
     * Os quadros do GIF, na ordem. Eles NÃO vão para `art/` como PNG: existem só para
     * virar GIF, e publicá-los dobraria o peso do repositório sem uso no README.
     *
     * @var list<string>
     */
    private const QUADROS_DO_GIF = [
        'fluxo-1-listagem',
        'fluxo-2-export',
        'fluxo-3-import',
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
        'admin-papeis-import-export',
        'boas-vindas',
        'app-projetos-anexos',
        'export-modal',
        'import-modal',
        'infra-hub',
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

    /** Copia para `art/` e gera a thumb de cada captura que não seja quadro de GIF. */
    private function publicar(string $origem): int
    {
        File::ensureDirectoryExists(base_path('art/thumbs'));

        $publicadas = 0;
        $ignoradas  = [];

        foreach (File::glob($origem.'/*.png') as $arquivo) {
            $nome = pathinfo($arquivo, PATHINFO_FILENAME);

            if (in_array($nome, self::QUADROS_DO_GIF, true)) {
                continue;
            }

            if (! in_array($nome, self::IMAGENS, true)) {
                $ignoradas[] = $nome;

                continue;
            }

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
     * Monta o GIF do fluxo a partir dos quadros, com ffmpeg.
     *
     * **Slideshow, não vídeo.** O `pest-plugin-browser` não grava vídeo, e captura de
     * quadros é o que dá para fazer de forma determinística — o mesmo cenário, os mesmos
     * três estados, sempre. Um GIF de gravação real mudaria a cada execução.
     *
     * `palettegen`/`paletteuse` porque GIF é limitado a 256 cores: sem a paleta calculada
     * a partir DESTES quadros, a interface do Filament sai com faixas de cor visíveis.
     *
     * Sem ffmpeg no PATH, avisa e segue — as imagens estáticas já foram publicadas, e
     * falhar aqui desperdiçaria a navegação inteira.
     */
    private function montarGif(string $origem): void
    {
        $faltando = array_filter(
            self::QUADROS_DO_GIF,
            fn (string $quadro): bool => ! File::exists("{$origem}/{$quadro}.png"),
        );

        if ($faltando !== []) {
            $this->components->warn('Quadros do GIF ausentes: '.implode(', ', $faltando).'. GIF não montado.');

            return;
        }

        $entrada = base_path('storage/framework/cache/arte');
        File::ensureDirectoryExists($entrada);

        foreach (self::QUADROS_DO_GIF as $indice => $quadro) {
            File::copy("{$origem}/{$quadro}.png", sprintf('%s/quadro-%02d.png', $entrada, $indice + 1));
        }

        $destino = base_path('art/fluxo-import-export.gif');

        $processo = new Process([
            'ffmpeg', '-y',
            '-framerate', '0.6',
            '-i', $entrada.'/quadro-%02d.png',
            '-vf', 'scale=1000:-1:flags=lanczos,split[a][b];[a]palettegen[p];[b][p]paletteuse',
            '-loop', '0',
            $destino,
        ]);

        $processo->run();

        File::deleteDirectory($entrada);

        if (! $processo->isSuccessful()) {
            $this->components->warn('ffmpeg não disponível ou falhou — GIF não montado. As imagens estáticas foram publicadas.');

            return;
        }

        $this->components->twoColumnDetail(
            'art/fluxo-import-export.gif',
            number_format(File::size($destino) / 1024, 0).' KB',
        );
    }
}

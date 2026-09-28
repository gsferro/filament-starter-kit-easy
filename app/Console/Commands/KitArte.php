<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
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
     * Os quadros de `busca-spotlight`, `login-unificado` e `install` ainda não têm cenário de
     * captura próprio (outro lote da feature os cria) — até lá, esses clipes aparecem sempre
     * como "quadros ausentes" na saída, o que é o comportamento correto de R32 (nomeado, não
     * silenciado), não um defeito.
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
     * Um clipe incompleto, cujo ffmpeg falha ou cujo ffmpeg ESTOURA O TIMEOUT (padrão do
     * `Process`: 60s) é reportado pelo nome e não impede os outros (R32) — o `foreach` nunca
     * para no primeiro incompleto/falho/travado; `montarClipe()` trata os três casos com o
     * mesmo `try/finally`.
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
     * sucesso (R33): o ffmpeg recebe um destino TEMPORÁRIO, fora de `art/`
     * (R33.M6 — um temporário dentro de `art/` ficaria lá, publicado, se o ffmpeg falhasse), e
     * só o sucesso publica o temporário no lugar do publicado, de forma atômica
     * (`publicarGif()`). O diretório de montagem é limpo ANTES de copiar os quadros deste
     * clipe (R32.M6) — um quadro sobrado de uma execução interrompida, ou do clipe anterior no
     * mesmo laço (R32.M5), não sobrevive para entrar neste GIF.
     *
     * O `try/finally` cobre as TRÊS formas de o ffmpeg não terminar bem — falha (código != 0),
     * timeout (`ProcessTimedOutException`, o padrão do `Process` é 60s) e qualquer outra
     * exceção do processo: nos três casos o diretório de montagem é limpo do mesmo jeito, e
     * `montarGif()` segue para o próximo clipe (R32) — travar num clipe não pode significar
     * nunca tentar os outros.
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
        $temporario = "{$entrada}/{$clipe}-saida.gif";
        $sucesso    = false;

        try {
            File::deleteDirectory($entrada);
            File::ensureDirectoryExists($entrada);

            foreach ($quadros as $indice => $quadro) {
                File::copy("{$origem}/{$quadro}.png", sprintf('%s/quadro-%02d.png', $entrada, $indice + 1));
            }

            $processo = new Process([
                $ffmpeg, '-y',
                '-framerate', '0.6',
                '-i', $entrada.'/quadro-%02d.png',
                '-vf', 'scale=1000:-1:flags=lanczos,split[a][b];[a]palettegen[p];[b][p]paletteuse',
                '-loop', '0',
                $temporario,
            ]);

            try {
                $processo->run();
            } catch (ProcessTimedOutException) {
                // Tratado como qualquer outra falha do ffmpeg logo abaixo — não sobe, não para
                // o `foreach` de `montarGif()`.
            }

            $sucesso = $processo->isSuccessful() && File::exists($temporario);

            if ($sucesso) {
                $sucesso = $this->publicarGif($temporario, $destino);
            }
        } finally {
            // Sempre — sucesso, falha, timeout ou qualquer exceção: nenhum quadro nem GIF
            // temporário deste clipe pode sobreviver para contaminar o próximo (R32.M6).
            File::deleteDirectory($entrada);
        }

        if (! $sucesso) {
            $this->components->warn("ffmpeg não disponível ou falhou — GIF de '{$label}' não montado. As imagens estáticas foram publicadas.");

            return;
        }

        $this->components->twoColumnDetail(
            "art/{$clipe}.gif",
            number_format(File::size($destino) / 1024, 0).' KB',
        );
    }

    /**
     * Publica o temporário no destino de forma ATÔMICA (R33): um leitor concorrente nunca vê o
     * GIF publicado pela metade.
     *
     * `rename()` é atômico quando origem e destino estão no MESMO volume — o caso comum aqui,
     * já que `$temporario` mora em `storage/framework/cache/arte` e `$destino` em `art/`,
     * ambos dentro do mesmo `base_path()`. Quando os dois estão em volumes diferentes,
     * `rename()` falha (Windows: `false`; Linux: `EXDEV`) e a troca vira: copia para um
     * temporário AO LADO do destino (mesmo diretório `art/`, portanto mesmo volume que ele) e
     * só então renomeia esse temporário para o nome final — `File::copy()` direto por cima do
     * publicado NÃO é atômico, e um leitor concorrente poderia ver o arquivo truncado a meio
     * caminho.
     */
    private function publicarGif(string $temporario, string $destino): bool
    {
        if (@rename($temporario, $destino)) {
            return true;
        }

        $temporarioAoLado = $destino.'.tmp-'.bin2hex(random_bytes(8));

        if (! File::copy($temporario, $temporarioAoLado)) {
            return false;
        }

        if (@rename($temporarioAoLado, $destino)) {
            return true;
        }

        File::delete($temporarioAoLado);

        return false;
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

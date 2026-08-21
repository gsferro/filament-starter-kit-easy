<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Move para disco privado a mídia que ficou gravada em disco público.
 *
 * Trocar o default de `config/media-library.php` fecha a porta para a mídia NOVA e
 * deixa a janela aberta: o que já está em `storage/app/public/{id}/` continua servido
 * pelo symlink `public/storage`, sem sessão e sem assinatura. Este comando fecha a
 * janela.
 *
 * **É por item, e o arquivo chega antes de a linha mudar.** Falha no meio deixa
 * metade migrada e metade intacta — nunca uma linha apontando para arquivo que não
 * existe. Reexecutar termina o serviço.
 *
 * **Escolha explícita de disco público é preservada.** Coleção que declara
 * `useDisk('public')` — avatar, logo, identidade visual — quis ser pública. O critério
 * é a visibilidade declarada no `config/filesystems.php`, não o nome do disco: é o
 * mesmo critério que a rota `storage.{disk}` usa para decidir se exige assinatura.
 *
 * Sem escopo de model: a correção é do kit, não da demo. Mídia de qualquer
 * `model_type` entra.
 */
class KitMidiaPrivada extends Command
{
    protected $signature = 'kit:midia-privada {--dry-run : Apenas relata o que seria migrado}';

    protected $description = 'Move para disco privado a mídia gravada em disco público';

    public function handle(): int
    {
        $simulacao = (bool) $this->option('dry-run');
        $migradas  = 0;
        $ignoradas = 0;

        Media::query()->chunkById(100, function (Collection $lote) use ($simulacao, &$migradas, &$ignoradas): void {
            /** @var Media $midia */
            foreach ($lote as $midia) {
                $destino = $this->discoDeDestino($midia);

                if ($destino === null) {
                    $ignoradas++;

                    continue;
                }

                $moveuOriginal   = $this->ehPublico($midia->disk);
                $moveuConversoes = $this->ehPublico($this->discoDasConversoes($midia));

                if (! $moveuOriginal && ! $moveuConversoes) {
                    continue;
                }

                $migradas++;

                if ($simulacao) {
                    continue;
                }

                $this->migrar($midia, $destino, $moveuOriginal, $moveuConversoes);
            }
        });

        $verbo = $simulacao ? 'seriam migradas' : 'migradas';
        $this->components->info("Mídias {$verbo}: {$migradas}.");

        if ($ignoradas > 0) {
            $this->components->info("Mídias em coleção que declara disco público (preservadas): {$ignoradas}.");
        }

        Log::channel('tenancy')->info(
            "[KitMidiaPrivada@handle] Mídia pública {$verbo} | total: {$migradas} | simulacao: ".($simulacao ? 'sim' : 'não'),
            ['migradas' => $migradas, 'preservadas' => $ignoradas, 'simulacao' => $simulacao],
        );

        return self::SUCCESS;
    }

    /**
     * O disco para onde esta mídia deve ir, ou `null` se ela não deve ser tocada.
     *
     * A coleção manda: se ela declara disco, é o dela. Coleção que declarou disco
     * PÚBLICO fez uma escolha, e migrar por cima dela quebraria a tela de login.
     */
    private function discoDeDestino(Media $midia): ?string
    {
        $declarado = $this->discoDeclaradoPelaColecao($midia);

        if ($declarado !== null) {
            return $this->ehPublico($declarado) ? null : $declarado;
        }

        $default = (string) config('media-library.disk_name');

        return $this->ehPublico($default) ? null : $default;
    }

    /**
     * O `useDisk()` da coleção, lido do próprio model — não do banco.
     *
     * Model que não existe mais (classe removida, morph map antigo) ou que não usa a
     * media library não declara nada: cai no default.
     */
    private function discoDeclaradoPelaColecao(Media $midia): ?string
    {
        $classe = Relation::getMorphedModel($midia->model_type) ?? $midia->model_type;

        if (! class_exists($classe)) {
            return null;
        }

        $model = new $classe;

        if (! $model instanceof HasMedia) {
            return null;
        }

        $colecao = $model->getMediaCollection($midia->collection_name);

        return ($colecao === null || $colecao->diskName === '') ? null : $colecao->diskName;
    }

    /**
     * Disco que a rota `storage.{disk}` serve SEM exigir assinatura.
     *
     * É a visibilidade declarada em `config/filesystems.php` que decide, e é a mesma
     * chave que o `ServeFile` do Laravel consulta. Perguntar pelo nome do disco daria
     * falso negativo no primeiro projeto que renomeasse o `public`.
     */
    private function ehPublico(string $disco): bool
    {
        if ($disco === '') {
            return false;
        }

        return config("filesystems.disks.{$disco}.visibility") === 'public';
    }

    /**
     * O disco onde as conversões desta mídia estão.
     *
     * A coluna é sempre preenchida pelo `FileAdder`, mas o fallback do próprio spatie
     * é o disco do original — linha antiga ou escrita fora do pacote pode chegar aqui
     * vazia, e sem o fallback a miniatura pública ficaria para trás.
     */
    private function discoDasConversoes(Media $midia): string
    {
        $disco = (string) $midia->conversions_disk;

        return $disco === '' ? $midia->disk : $disco;
    }

    private function migrar(Media $midia, string $destino, bool $original, bool $conversoes): void
    {
        $base = (string) $midia->getKey();

        if ($original) {
            $this->moverArquivos($midia->disk, $destino, Storage::disk($midia->disk)->files($base));
            $midia->disk = $destino;
        }

        if ($conversoes) {
            $de = $this->discoDasConversoes($midia);

            foreach (['conversions', 'responsive-images'] as $sub) {
                $this->moverArquivos($de, $destino, Storage::disk($de)->files("{$base}/{$sub}"));
            }

            $midia->conversions_disk = $destino;
        }

        // Só depois de todo arquivo ter chegado. A ordem inversa deixaria a linha
        // apontando para arquivo inexistente — e a mídia sumiria da tela.
        $midia->save();

        Log::channel('tenancy')->info(
            "[KitMidiaPrivada@migrar] Mídia movida para disco privado | midia_id: {$midia->getKey()} | disco: {$destino}",
            ['midia_id' => $midia->getKey(), 'disco' => $destino, 'original' => $original, 'conversoes' => $conversoes],
        );
    }

    /**
     * @param  array<int, string>  $arquivos
     */
    private function moverArquivos(string $de, string $para, array $arquivos): void
    {
        foreach ($arquivos as $arquivo) {
            $conteudo = Storage::disk($de)->get($arquivo);

            if ($conteudo === null) {
                continue;
            }

            Storage::disk($para)->put($arquivo, $conteudo);
            Storage::disk($de)->delete($arquivo);
        }
    }
}

<?php

namespace App\Filament\App\Resources\Projetos\Pages;

use App\Filament\App\Resources\Projetos\ProjetoResource;
use App\Filament\Exports\ProjetoExporter;
use App\Filament\Imports\ProjetoImporter;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListProjetos extends ListRecords
{
    use HasResizableColumn;

    protected static string $resource = ProjetoResource::class;

    /**
     * Cabeçalho da página, como nas demais listagens do kit.
     *
     * **O par import/export do kit, demonstrado no resource de demonstração.** Ver
     * `.ai/rules/filament.md` para a convenção — todo resource novo decide os dois, e a
     * decisão nasce escrita no arquivo, ligada ou comentada.
     *
     * Três coisas não são opcionais aqui, e cada uma fecha um furo:
     *
     * 1. **`->authorize('import')` / `->authorize('export')`.** Action do Filament NÃO
     *    consulta policy sozinha (`Concerns/CanBeAuthorized.php:16-19`: a autorização
     *    default é `null`, ou seja, liberada). Sem esta linha, quem abre a listagem
     *    exporta a listagem.
     * 2. **`->options(['tenant_id' => ...])` no import.** O `resolveRecord()` roda dentro
     *    do worker, onde `Filament::getTenant()` é `null` e o escopo de `BelongsToTenant`
     *    vira no-op. O tenant é capturado AQUI, no request, e viaja no payload do job.
     *    Ver `App\Support\ImportExport\ImportadorDoKit`.
     * 3. **O export não precisa de options.** A query dele vem da tabela desta tela, já
     *    com o `where tenant_id`, e é serializada com ele dentro.
     *
     * Sem worker de fila nada processa: `composer dev` sobe um; em produção, o serviço
     * `worker` do docker compose.
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            ImportAction::make()
                ->importer(ProjetoImporter::class)
                ->authorize('import')
                ->options(fn (): array => ['tenant_id' => Filament::getTenant()?->getKey()]),

            ExportAction::make()
                ->exporter(ProjetoExporter::class)
                ->authorize('export'),
        ];
    }
}

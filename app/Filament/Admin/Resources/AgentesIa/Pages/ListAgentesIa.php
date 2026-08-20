<?php

namespace App\Filament\Admin\Resources\AgentesIa\Pages;

use App\Filament\Admin\Resources\AgentesIa\AgenteIaResource;
use App\Filament\Exports\AgenteIaExporter;
use App\Filament\Imports\AgenteIaImporter;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListAgentesIa extends ListRecords
{
    use HasResizableColumn;

    protected static string $resource = AgenteIaResource::class;

    /**
     * Import e export LIGADOS: agente de IA é configuração, sem dado pessoal, e mover
     * configuração entre instalações por CSV é o caso de uso da feature.
     *
     * Sem `->options()`: `AgenteIa` não usa `BelongsToTenant` e o `/admin` não tem tenant
     * na rota. O `ImportadorDoKit` detecta isso e não exige organização — é o caso que
     * prova que a fronteira não engessa o modo single-tenant.
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Novo agente'),

            ImportAction::make()
                ->importer(AgenteIaImporter::class)
                ->authorize('import'),

            ExportAction::make()
                ->exporter(AgenteIaExporter::class)
                ->authorize('export'),
        ];
    }
}

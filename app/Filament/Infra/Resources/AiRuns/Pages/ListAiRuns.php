<?php

namespace App\Filament\Infra\Resources\AiRuns\Pages;

use App\Filament\Exports\AiRunExporter;
use App\Filament\Infra\Resources\AiRuns\AiRunResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Grid do ledger. Sem CreateAction — execução é gravada pelo sistema, nunca criada à mão.
 */
class ListAiRuns extends ListRecords
{
    use HasResizableColumn;

    protected static string $resource = AiRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Dashboard do fomvasss/laravel-ai-tasks (rota do pacote, fora do Filament).
            Action::make('dashboardAiTasks')
                ->label('Dashboard de estatísticas')
                ->icon('heroicon-o-chart-bar')
                ->url(fn (): string => route('ai-tasks.index'))
                ->openUrlInNewTab(),

            /*
             * Export sim, import não: ledger é escrito pelo sistema, e importar execução
             * seria falsificar custo. As colunas de `request`/`response` ficam FORA do
             * exporter — ver `AiRunExporter`.
             */
            ExportAction::make()
                ->exporter(AiRunExporter::class)
                ->authorize('export'),
        ];
    }
}

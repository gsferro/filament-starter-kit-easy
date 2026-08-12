<?php

namespace App\Filament\Infra\Resources\AiRuns\Pages;

use App\Filament\Infra\Resources\AiRuns\AiRunResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

/**
 * Grid do ledger. Sem CreateAction — execução é gravada pelo sistema, nunca criada à mão.
 */
class ListAiRuns extends ListRecords
{
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
        ];
    }
}

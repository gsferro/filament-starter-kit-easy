<?php

namespace App\Filament\Infra\Resources\AiRuns\Pages;

use App\Filament\Infra\Resources\AiRuns\AiRunResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAiRun extends ViewRecord
{
    protected static string $resource = AiRunResource::class;

    /** Sem EditAction: o ledger é imutável. */
    protected function getHeaderActions(): array
    {
        return [];
    }
}

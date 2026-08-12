<?php

namespace App\Filament\Admin\Resources\AgentesIa\Pages;

use App\Filament\Admin\Resources\AgentesIa\AgenteIaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAgentesIa extends ListRecords
{
    protected static string $resource = AgenteIaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Novo agente'),
        ];
    }
}

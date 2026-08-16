<?php

namespace App\Filament\App\Resources\Projetos\Pages;

use App\Filament\App\Resources\Projetos\ProjetoResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProjetos extends ListRecords
{
    use HasResizableColumn;

    protected static string $resource = ProjetoResource::class;

    /** Cabeçalho da página, como nas demais listagens do kit. */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

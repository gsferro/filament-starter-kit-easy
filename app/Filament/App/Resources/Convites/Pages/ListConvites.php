<?php

namespace App\Filament\App\Resources\Convites\Pages;

use App\Filament\App\Resources\Convites\ConviteResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConvites extends ListRecords
{
    use HasResizableColumn;

    protected static string $resource = ConviteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\Convites\Pages;

use App\Filament\Admin\Resources\Convites\ConviteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConvites extends ListRecords
{
    protected static string $resource = ConviteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

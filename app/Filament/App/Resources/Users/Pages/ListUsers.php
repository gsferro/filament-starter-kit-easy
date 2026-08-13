<?php

namespace App\Filament\App\Resources\Users\Pages;

use App\Filament\App\Resources\Users\UserResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    use HasResizableColumn;

    protected static string $resource = UserResource::class;
}

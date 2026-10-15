<?php

namespace Tests\Kit\fixtures;

use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class ListUsersAgrupavel extends ListUsers
{
    public function table(Table $table): Table
    {
        return parent::table($table)->groups([Group::make('email')]);
    }
}

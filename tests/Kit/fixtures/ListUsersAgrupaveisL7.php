<?php

namespace Tests\Kit\fixtures;

use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use Filament\Tables\Table;

class ListUsersAgrupaveisL7 extends ListUsers
{
    public function table(Table $table): Table
    {
        return parent::table($table)->groups(['name']);
    }
}

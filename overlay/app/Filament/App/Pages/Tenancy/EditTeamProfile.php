<?php

namespace App\Filament\App\Pages\Tenancy;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Schema;

class EditTeamProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Perfil da equipe';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nome da equipe')
                ->required()
                ->maxLength(255),
        ]);
    }
}

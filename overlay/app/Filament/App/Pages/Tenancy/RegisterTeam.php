<?php

namespace App\Filament\App\Pages\Tenancy;

use App\Models\Team;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class RegisterTeam extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Criar equipe';
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

    protected function handleRegistration(array $data): Team
    {
        $team = Team::create([
            ...$data,
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(6)),
        ]);

        $team->users()->attach(auth()->user());

        return $team;
    }
}

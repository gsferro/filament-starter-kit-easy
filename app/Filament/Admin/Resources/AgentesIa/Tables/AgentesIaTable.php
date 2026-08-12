<?php

namespace App\Filament\Admin\Resources\AgentesIa\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Grid do catálogo de agentes. Sem DeleteAction — a "exclusão" é lógica, pela flag `ativo`:
 * apagar o paper de um agente que existe em código deixaria a aplicação com um agente que
 * não sobe.
 */
class AgentesIaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('nome')
            ->columns([
                TextColumn::make('nome')->label('Nome')->searchable(['nome', 'slug'])->sortable(),
                TextColumn::make('slug')->label('Slug')->badge()->color('gray')->searchable(),
                IconColumn::make('ativo')->label('Ativo')->boolean(),
                TextColumn::make('provider')->label('Provider')->badge()->placeholder('default')->searchable(),
                TextColumn::make('modelo')->label('Modelo')->placeholder('default')->searchable(),
                TextColumn::make('versao')->label('Versão')->badge()->color('gray')->sortable(),
                TextColumn::make('updated_at')->label('Atualizado em')->dateTime('d/m/Y H:i')
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('ativo')->label('Ativo'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->emptyStateHeading('Nenhum agente cadastrado')
            ->emptyStateDescription('Rode os seeders do kit (AssistenteSeeder, GuardaPromptSeeder) ou cadastre um agente.');
    }
}

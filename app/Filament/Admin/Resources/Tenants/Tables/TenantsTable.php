<?php

namespace App\Filament\Admin\Resources\Tenants\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Grid dos tenants. Sem DeleteAction: a "exclusão" é a flag `ativo`. Apagar um
 * tenant levaria junto, em cascata, todos os dados de negócio dele — e isso
 * não pode ser um clique numa listagem.
 */
class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('nome')
            ->columns([
                TextColumn::make('nome')->label('Nome')->searchable(['nome', 'slug'])->sortable(),
                TextColumn::make('slug')->label('Slug')->badge()->color('gray')->searchable(),
                IconColumn::make('ativo')->label('Ativo')->boolean(),
                TextColumn::make('users_count')
                    ->label('Usuários')
                    ->counts('users')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('ativo')->label('Ativo'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->emptyStateHeading('Nenhum registro cadastrado')
            ->emptyStateDescription('Cadastre o primeiro para liberar o acesso ao painel de negócio.');
    }
}

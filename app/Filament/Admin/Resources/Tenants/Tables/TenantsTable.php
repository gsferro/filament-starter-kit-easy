<?php

namespace App\Filament\Admin\Resources\Tenants\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
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
                /*
                 * A logo enviada no formulário da organização, ampliável em lightbox.
                 *
                 * Quadrada e não `circular()`: logo costuma ser retangular, e o corte circular
                 * comeria as pontas. Diferente do avatar de pessoa, de propósito.
                 *
                 * A coluna NÃO usa `Tenant::logoUrl()`, que confere `Storage::exists()`: isso
                 * seria uma ida ao disco por linha renderizada, a cada paginação, ordenação e
                 * busca. Registro cuja logo sumiu do disco mostra imagem quebrada aqui — o
                 * comportamento padrão de qualquer ImageColumn. Onde a verificação importa (a
                 * tela de bloqueio) o acessor continua sendo usado. Ver ADR-05 da wiki
                 * lightbox-em-imagens-e-documentos.
                 */
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->disk('public')
                    ->imageSize(40)
                    ->simpleLightbox(),
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
                // Sem `->url()` nos dois: com as páginas `view` e `edit` registradas, o
                // `Page::getDefaultActionUrl()` resolve a URL sozinho e o Filament renderiza
                // `<a href>` em vez de `wire:click` (Page.php:373-389, Action.php:889). Passar
                // URL à mão duplicaria isso — e apontaria para rota inexistente no dia em que
                // uma das páginas saísse do resource.
                ViewAction::make(),
                EditAction::make(),
            ])
            ->emptyStateHeading('Nenhum registro cadastrado')
            ->emptyStateDescription('Cadastre o primeiro para liberar o acesso ao painel de negócio.');
    }
}

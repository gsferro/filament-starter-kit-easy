<?php

namespace App\Filament\App\Resources\Projetos;

use App\Filament\App\Resources\Projetos\Pages\ListProjetos;
use App\Filament\Concerns\BadgeContagemNavegacao;
use App\Models\Projeto;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * DEMONSTRAÇÃO — o primeiro (e único) resource do painel /app, criado por
 * `php artisan kit:tenancy --demo`.
 *
 * O painel /app nasce vazio de propósito; este resource existe só para você
 * VER o isolamento funcionando: entre em `/app/acme` e `/app/globex` com o
 * mesmo usuário e compare as listagens.
 *
 * Repare no que NÃO está aqui: nenhum `where('tenant_id', ...)`. O recorte vem
 * de duas camadas que se sobrepõem — o escopo do Filament (porque a model tem
 * a relação `tenant()`) e o escopo global da trait `BelongsToTenant`, que
 * também cobre as queries fora de resources.
 *
 * O formulário usa `->scopedUnique()`: a regra `unique` do Laravel não passa
 * pelo Eloquent e ignoraria o tenant, deixando o nome usado por OUTRO cliente
 * bloquear o cadastro aqui.
 *
 * Descartável: apague esta pasta junto com o resto da demo.
 */
class ProjetoResource extends Resource
{
    use BadgeContagemNavegacao;

    protected static ?string $model = Projeto::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'projeto';

    protected static ?string $pluralModelLabel = 'projetos';

    protected static ?string $recordTitleAttribute = 'nome';

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['nome'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nome')
                ->label('Nome')
                ->required()
                ->maxLength(120)
                // scopedUnique, não unique: a regra do Laravel ignora o tenant.
                ->scopedUnique(ignoreRecord: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nome')
            ->columns([
                TextColumn::make('nome')->label('Nome')->searchable()->sortable(),
                TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->headerActions([
                CreateAction::make()->label('Novo projeto'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Nenhum projeto aqui')
            ->emptyStateDescription('Cada registro pertence ao tenant selecionado no topo — troque de tenant e a lista muda.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjetos::route('/'),
        ];
    }
}

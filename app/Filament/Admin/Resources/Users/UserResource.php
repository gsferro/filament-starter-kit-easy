<?php

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Concerns\BadgeContagemNavegacao;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use STS\FilamentImpersonate\Actions\Impersonate;

class UserResource extends Resource
{
    use BadgeContagemNavegacao;

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $modelLabel = 'Usuário';

    protected static ?string $pluralModelLabel = 'Usuários';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nome')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label('E-mail')
                ->email()
                ->required()
                ->unique(ignoreRecord: true),
            TextInput::make('password')
                ->label('Senha')
                ->password()
                ->revealable()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->maxLength(255),
            Select::make('roles')
                ->label('Papéis')
                ->relationship('roles', 'name')
                ->multiple()
                ->preload()
                ->searchable()
                /*
                 * Gravar papel é pela API do spatie, NUNCA pelo sync da relação.
                 *
                 * O `->relationship()` grava com `$relationship->sync()`, que
                 * escreve na pivot só as colunas da chave. Com multi-tenancy a
                 * `model_has_roles.team_id` é NOT NULL e ninguém a preenche: o
                 * `wherePivot` que o spatie põe em `roles()` filtra LEITURA, não
                 * alimenta escrita. Resultado era 500 ao salvar o usuário —
                 * `NOT NULL constraint failed: model_has_roles.team_id`.
                 *
                 * O `syncRoles()` resolve os dois lados: passa o `team_id` do
                 * contexto corrente no attach e invalida o cache de papéis, que
                 * o `sync()` deixava velho mesmo em modo single-tenant.
                 *
                 * Os papéis são resolvidos em modelos antes de entrar: o state
                 * vem do Livewire como string, e o `collectRoles()` do spatie
                 * trata string como NOME de papel — `"4"` viraria
                 * `RoleDoesNotExist`.
                 */
                ->saveRelationshipsUsing(function (User $record, array $state): void {
                    $record->syncRoles(
                        $record->roles()->getRelated()->newQuery()->whereKey($state)->get()
                    );
                }),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                TextColumn::make('email')->label('E-mail')->searchable()->sortable(),
                TextColumn::make('roles.name')->label('Papéis')->badge(),
                TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->recordActions([
                Impersonate::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit'   => EditUser::route('/{record}/edit'),
        ];
    }
}

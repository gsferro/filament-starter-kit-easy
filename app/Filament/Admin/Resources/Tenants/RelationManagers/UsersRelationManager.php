<?php

namespace App\Filament\Admin\Resources\Tenants\RelationManagers;

use App\Models\Tenant;
use App\Models\User;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Vínculo usuário ↔ tenant.
 *
 * É esta tela que decide quem consegue abrir `/app/{slug}`: sem linha no pivot,
 * o `User::canAccessTenant()` nega e o tenant nem aparece no seletor.
 *
 * Attach/detach são registrados no canal `tenancy` — mudar quem enxerga os
 * dados de um cliente é exatamente o tipo de evento que se precisa auditar
 * depois, e o pivot não tem timestamps para contar essa história.
 */
class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Usuários vinculados';

    protected static ?string $modelLabel = 'usuário';

    protected static ?string $pluralModelLabel = 'usuários';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                TextColumn::make('email')->label('E-mail')->searchable()->sortable(),
                TextColumn::make('roles.name')->label('Papéis')->badge()->color('gray'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Vincular usuário')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name', 'email'])
                    ->after(fn (User $record): null => $this->registrar('vinculado', $record)),
            ])
            ->recordActions([
                DetachAction::make()
                    ->label('Desvincular')
                    ->after(fn (User $record): null => $this->registrar('desvinculado', $record)),
            ])
            ->emptyStateHeading('Nenhum usuário vinculado')
            ->emptyStateDescription('Sem vínculo, ninguém abre o painel de negócio deste registro.');
    }

    private function registrar(string $acao, User $usuario): null
    {
        /** @var Tenant $tenant */
        $tenant = $this->getOwnerRecord();

        Log::channel('tenancy')->info(
            "[UsersRelationManager@registrar] Usuário {$acao} | tenant: {$tenant->slug} - user: {$usuario->id}",
            [
                'acao'        => $acao,
                'tenant_id'   => $tenant->id,
                'user_id'     => $usuario->id,
                'executor_id' => Auth::id(),
            ],
        );

        return null;
    }
}

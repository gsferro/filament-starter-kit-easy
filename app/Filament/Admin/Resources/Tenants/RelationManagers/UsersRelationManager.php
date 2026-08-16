<?php

namespace App\Filament\Admin\Resources\Tenants\RelationManagers;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Papeis;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;

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

    protected static ?string $modelLabel = 'Usuário';

    protected static ?string $pluralModelLabel = 'Usuários';

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
                TextColumn::make('roles.name')->label('Papéis')->badge()->color('gray')
                    ->formatStateUsing(fn (?string $state): string => Papeis::rotulo($state)),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Vincular usuário')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name', 'email'])
                    ->after(fn (User $record): null => $this->registrar('vinculado', $record)),
            ])
            ->recordActions([
                $this->acaoDePapeis(),
                DetachAction::make()
                    ->label('Desvincular')
                    ->after(fn (User $record): null => $this->registrar('desvinculado', $record)),
            ])
            ->emptyStateHeading('Nenhum usuário vinculado')
            ->emptyStateDescription('Sem vínculo, ninguém abre o painel de negócio deste registro.');
    }

    /**
     * "Papéis nesta organização" — onde nasce o primeiro admin de uma organização.
     *
     * Problema de bootstrap: `admin_app` só vale atribuído DENTRO da organização,
     * e o Select de papéis do `UserResource` do /admin grava em `Tenant::CONTEXTO_GLOBAL`
     * (é o que ele deve fazer — lá se concedem `admin`, `infra` e `master_global`, que são
     * papéis de instalação). Promover por lá produz a falha mais silenciosa da feature: a
     * pessoa ENTRA no /app e não vê nada, porque o `wherePivot` do spatie filtra pelo team
     * do request. Ver ADR-10.
     *
     * Este relation manager é o único lugar do sistema que conhece o usuário E a
     * organização ao mesmo tempo.
     */
    private function acaoDePapeis(): Action
    {
        return Action::make('papeisNaOrganizacao')
            ->label('Papéis nesta organização')
            ->icon(Heroicon::OutlinedShieldCheck)
            ->schema([
                Select::make('roles')
                    ->label('Papéis')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->options(fn (): array => Role::query()->where('painel', 'app')->pluck('name', 'id')->all())
                    ->helperText('Só papéis do painel /app. Papel de instalação (admin, infra) se dá no cadastro do usuário.'),
            ])
            ->fillForm(fn (User $record): array => ['roles' => $this->papeisNoTenant($record)])
            ->action(function (User $record, array $data): void {
                /** @var Tenant $tenant */
                $tenant = $this->getOwnerRecord();

                // Mesmo filtro de painel da escrita do /app (ADR-07): o state vem do
                // cliente, e um id de papel `admin` gravado aqui daria acesso à instalação.
                $papeis = Role::query()->whereKey($data['roles'] ?? [])->where('painel', 'app')->get();

                $this->noContextoDe($tenant, $record, fn (): mixed => $record->syncRoles($papeis));

                Log::channel('autenticacao')->info(
                    "[UsersRelationManager@papeisNaOrganizacao] Papéis definidos na organização | tenant: {$tenant->slug} - user: {$record->id}",
                    [
                        'tenant_id'   => $tenant->id,
                        'user_id'     => $record->id,
                        'executor_id' => Auth::id(),
                        'papeis'      => $papeis->pluck('name')->all(),
                    ],
                );
            })
            ->successNotificationTitle('Papéis atualizados nesta organização');
    }

    /**
     * Os ids de papel que o usuário tem DENTRO desta organização.
     *
     * @return list<int|string>
     */
    private function papeisNoTenant(User $usuario): array
    {
        /** @var Tenant $tenant */
        $tenant = $this->getOwnerRecord();

        return $this->noContextoDe($tenant, $usuario, fn (): array => $usuario->roles->modelKeys());
    }

    /**
     * Roda o callback com o contexto de papéis fixado nesta organização.
     *
     * O `unsetRelation('roles')` nas duas pontas não é zelo: o Eloquent cacheia `roles` na
     * instância, e o cache do contexto anterior contaminaria tanto a leitura quanto o
     * `syncRoles()`. É o mesmo par que `DemoTenancySeeder::papelDoApp()` usa.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function noContextoDe(Tenant $tenant, User $usuario, callable $callback): mixed
    {
        $registrar = app(PermissionRegistrar::class);
        $anterior  = $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId($tenant->getKey());
            $usuario->unsetRelation('roles');

            return $callback();
        } finally {
            $registrar->setPermissionsTeamId($anterior);
            $usuario->unsetRelation('roles');
        }
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

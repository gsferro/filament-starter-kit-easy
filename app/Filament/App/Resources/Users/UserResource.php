<?php

namespace App\Filament\App\Resources\Users;

use App\Filament\App\Resources\Users\Pages\CreateUser;
use App\Filament\App\Resources\Users\Pages\EditUser;
use App\Filament\App\Resources\Users\Pages\ListUsers;
use App\Filament\Concerns\BadgeContagemNavegacao;
use App\Models\Tenant;
use App\Models\User;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use UnitEnum;

/**
 * Usuários DA ORGANIZAÇÃO corrente — a tela do `admin_organizacao`.
 *
 * Irmão do `App\Filament\Admin\Resources\Users\UserResource`, e deliberadamente uma
 * classe separada: o que é igual são quatro campos de formulário; o que é diferente é
 * regra de segurança (query escopada, papéis filtrados por painel, sem exclusão, sem
 * impersonate, sem campo de organização). Uma base compartilhada faria uma edição pensada
 * no /admin alargar o /app em silêncio — e é o /app que tem cliente dentro. Ver ADR-04 em
 * `wikis/specs/main/admin-da-organizacao/02-decisoes-arquiteturais.md`.
 */
class UserResource extends Resource
{
    use BadgeContagemNavegacao;

    protected static ?string $model = User::class;

    /**
     * O Filament NÃO escopa este resource sozinho — e não pode.
     *
     * `User` não tem relação de posse com `Tenant`: o vínculo é a pivot many-to-many
     * `tenant_user`, e a mesma pessoa pertence a N organizações. Com o escopo nativo
     * ligado, `Panel::boot()` registra um global scope que procura a relação `tenant`
     * (singular, o default de `Filament::getTenantOwnershipRelationshipName()`) e a
     * primeira query do painel morre com `LogicException: The model [App\Models\User]
     * does not have a relationship named [tenant]`.
     *
     * Apontar `$tenantOwnershipRelationshipName = 'tenants'` funcionaria e foi recusado:
     * o escopo nativo FALHA ABERTO (sem tenant corrente ele retorna em silêncio e a
     * listagem vira a base inteira de usuários da instalação), registra um global scope
     * no model `User`, que é compartilhado com o guard de autenticação e o /admin, e traz
     * junto um observer de vendor no `created`. Ver ADR-03.
     *
     * Desligar aqui é o que devolve o recorte para `getEloquentQuery()`, que falha
     * FECHADO.
     */
    protected static bool $isScopedToTenant = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Administração';

    protected static ?string $modelLabel = 'usuário';

    protected static ?string $pluralModelLabel = 'usuários';

    protected static ?string $recordTitleAttribute = 'name';

    /** Espelha o TenantResource: sem tenancy não existe organização para administrar. */
    public static function shouldRegisterNavigation(): bool
    {
        return (bool) config('kit.tenancy.enabled');
    }

    public static function canAccess(): bool
    {
        return (bool) config('kit.tenancy.enabled') && parent::canAccess();
    }

    /**
     * Excluir usuário é ato GLOBAL: apaga a linha de `users` e, com ela, o vínculo da
     * pessoa com TODAS as organizações (`tenant_user` tem `cascadeOnDelete`). Quem
     * administra UMA organização não pode alcançar isso.
     *
     * A permissão `Delete:User` existe no papel — a matriz é a do painel inteiro. A trava
     * é aqui, no resource, onde a decisão se lê. Ver ADR-08.
     */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /**
     * Só os usuários vinculados à organização corrente.
     *
     * `whereHas` e não `where('tenant_id', …)`: a posse mora na pivot `tenant_user`, e um
     * usuário pertence a N organizações — a Carla da demo pertence a duas, e dentro da
     * Acme ela é usuária da Acme.
     *
     * Sem organização corrente a query FECHA. Fora de um request de painel (job, comando,
     * tinker) `Filament::getTenant()` é null e, se este método devolvesse a query crua, a
     * listagem mostraria a base inteira de usuários da instalação — pessoas de outros
     * clientes. `whereRaw('1 = 0')` e não uma exception: exception derrubaria qualquer
     * varredura que toque o Resource fora de request.
     *
     * O recorte fica aqui, e não na `table()`, porque quatro consumidores passam por este
     * método: a listagem, o route binding (é ele que devolve 404 na URL direta para um
     * usuário de outra organização), a busca ⌘K e o badge de contagem do menu.
     */
    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Tenant) {
            Log::channel('autenticacao')->warning(
                '[UserResource@getEloquentQuery] Consulta de usuários sem organização corrente — recorte fechado | painel: app',
                [
                    'painel'      => 'app',
                    'executor_id' => Auth::id(),
                    'motivo'      => 'sem_tenant_corrente',
                ],
            );

            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        // parent::getEloquentQuery() e não User::query(): o pai é quem lida com o global
        // scope de tenancy do Filament. Aqui é no-op, mas User::query() quebraria em
        // silêncio se alguém religasse $isScopedToTenant um dia.
        return parent::getEloquentQuery()
            ->whereHas('tenants', fn (Builder $query): Builder => $query->whereKey($tenant->getKey()));
    }

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

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
                /*
                 * Barreira 1 (UX): só papéis DO PAINEL APP entram na lista. `master_global`,
                 * `admin` e `infra` nunca aparecem.
                 */
                ->relationship('roles', 'name', fn (Builder $query): Builder => $query->where('painel', 'app'))
                ->multiple()
                ->preload()
                ->searchable()
                ->required()
                ->helperText('Os papéis valem apenas dentro desta '.mb_strtolower((string) config('kit.tenancy.label', 'Organização')).'.')
                ->saveRelationshipsUsing(self::gravarPapeis(...)),

            /*
             * Nenhum campo de organização, de propósito. Um Select de organização dentro
             * de um painel que JÁ está numa organização é superfície de escalada, não
             * conveniência: o vínculo é carimbado no `afterCreate` da CreateUser, com o
             * tenant do painel.
             */
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // O avatar enviado pela própria pessoa no "Meu perfil" (Breezy), ampliável em
                // lightbox. Mesmos cuidados do irmão em /admin: `disk('public')` explícito
                // porque o default não é servível por URL, e SEM `defaultImageUrl()` para que
                // quem não enviou avatar fique com a célula vazia em vez de um placeholder
                // clicável. O macro `simpleLightbox()` vem do plugin registrado no painel.
                ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->disk('public')
                    ->circular()
                    ->simpleLightbox(),
                TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                TextColumn::make('email')->label('E-mail')->searchable()->sortable(),
                // Mostra só os papéis do contexto corrente: o `wherePivot` que o spatie
                // põe em `roles()` faz o recorte por team sozinho.
                TextColumn::make('roles.name')->label('Papéis')->badge(),
                TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->headerActions([
                CreateAction::make()->label('Novo usuário'),
            ])
            // Sem Impersonate (é privilégio do master_global) e sem DeleteAction nem
            // DeleteBulkAction (ADR-08) — ver canDelete() acima.
            ->recordActions([
                EditAction::make(),
            ])
            ->emptyStateHeading('Nenhum usuário nesta '.mb_strtolower((string) config('kit.tenancy.label', 'Organização')))
            ->emptyStateDescription('Crie aqui ou convide por e-mail — em qualquer caso a pessoa nasce vinculada a esta organização.');
    }

    /**
     * A trava contra escalada de privilégio: ela é NA ESCRITA.
     *
     * Opção de Select é sugestão de UI. O `$state` chega do Livewire, e o
     * `->relationship(…, $modifyQuery)` usa a query modificada para MONTAR a lista, não
     * para validar a gravação. Na 5.7.6 o `Select::getInValidationRuleValues()` do
     * Filament recusa o id que não está entre as opções — mas isso é comportamento de
     * framework, verificado numa versão, e uma barreira de segurança não se apoia nisso.
     * O `where('painel', 'app')` aqui é a trava que vale em qualquer caminho de escrita.
     * Ver ADR-07.
     *
     * `syncRoles()` e não sync da relação: `model_has_roles.team_id` é NOT NULL e quem o
     * preenche é a API do spatie (`.ai/rules/filament.md`). E o contexto de team é o do
     * request, fixado por `DefinirTenantDePermissoes` no tenant corrente — nunca
     * `Tenant::CONTEXTO_GLOBAL`, que produziria alguém que entra no /app e não vê nada.
     *
     * Papel não é `$fillable`, então `AuditsFillables` não cobre esta mudança: os logs
     * abaixo são a única memória de quem virou o quê.
     *
     * Pública porque é a barreira, e barreira sem teste direto não é barreira: a validação
     * do Filament impede o formulário de chegar até aqui com um id inválido, então o único
     * jeito de exercitar a trava é chamá-la.
     *
     * @param  list<int|string>  $state
     */
    public static function gravarPapeis(User $record, array $state): void
    {
        $papeis = $record->roles()->getRelated()->newQuery()
            ->whereKey($state)
            ->where('painel', 'app')
            ->get();

        if ($papeis->count() !== count($state)) {
            Log::channel('autenticacao')->warning(
                "[UserResource@saveRelationshipsUsing] Papel fora do painel app descartado | alvo: {$record->id}",
                [
                    'alvo_id'      => $record->id,
                    'executor_id'  => Auth::id(),
                    'tenant_id'    => Filament::getTenant()?->getKey(),
                    'ids_enviados' => $state,
                    'ids_aceitos'  => $papeis->modelKeys(),
                    'motivo'       => 'papel_de_outro_painel',
                ],
            );
        }

        $record->syncRoles($papeis);

        Log::channel('autenticacao')->info(
            "[UserResource@saveRelationshipsUsing] Papéis atualizados na organização | alvo: {$record->id} - tenant: ".Filament::getTenant()?->getKey(),
            [
                'alvo_id'     => $record->id,
                'executor_id' => Auth::id(),
                'tenant_id'   => Filament::getTenant()?->getKey(),
                'papeis'      => $papeis->pluck('name')->all(),
            ],
        );
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

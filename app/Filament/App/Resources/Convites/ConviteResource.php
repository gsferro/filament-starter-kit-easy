<?php

namespace App\Filament\App\Resources\Convites;

use App\Filament\App\Resources\Convites\Pages\CreateConvite;
use App\Filament\App\Resources\Convites\Pages\ListConvites;
use App\Filament\Concerns\BadgeContagemNavegacao;
use App\Models\Convite;
use App\Models\Tenant;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use UnitEnum;

/**
 * Convites DA ORGANIZAÇÃO corrente — o outro lado da administração do `admin_organizacao`.
 *
 * Sem página de edição, como no /admin: o convite já foi enviado, existe um e-mail com um
 * link funcionando, e editar o papel faria a pessoa receber algo diferente do que o e-mail
 * anunciou. Ver o PHPDoc do `App\Filament\Admin\Resources\Convites\ConviteResource`.
 *
 * O recorte NÃO vem de graça: `App\Models\Convite` tem `tenant_id` dentro do `$fillable`
 * (o Select de organização do /admin precisa disso) e não usa `App\Traits\BelongsToTenant`.
 * As duas pontas são código desta classe: `getEloquentQuery()` na leitura e o
 * `mutateFormDataBeforeCreate()` da CreateConvite na escrita.
 */
class ConviteResource extends Resource
{
    use BadgeContagemNavegacao;

    protected static ?string $model = Convite::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Administração';

    protected static ?string $modelLabel = 'convite';

    protected static ?string $pluralModelLabel = 'convites';

    protected static ?string $recordTitleAttribute = 'email';

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
     * Só os convites da organização corrente, fail-closed.
     *
     * O mesmo par do `UserResource` deste painel, com `where` no lugar do `whereHas`
     * porque aqui a posse é uma coluna. Sem organização corrente a query fecha em vez de
     * devolver os convites de todos os clientes da instalação.
     */
    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Tenant) {
            Log::channel('autenticacao')->warning(
                '[ConviteResource@getEloquentQuery] Consulta de convites sem organização corrente — recorte fechado | painel: app',
                [
                    'painel'      => 'app',
                    'executor_id' => Auth::id(),
                    'motivo'      => 'sem_tenant_corrente',
                ],
            );

            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()->where('tenant_id', $tenant->getKey());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('email')
                ->label('E-mail')
                ->email()
                ->required()
                ->maxLength(255)
                /*
                 * SEM `->unique('users', 'email')`, e é aqui que a feature mais importa.
                 *
                 * Até a v0.11.0 este campo recusava endereço que já tinha conta — e o
                 * `admin_organizacao` era justamente quem ficava sem NENHUM caminho para
                 * trazer a consultora que já atende outro cliente. Agora o endereço com
                 * conta vira OFERTA DE ACESSO: ninguém é cadastrado de novo, a pessoa
                 * confirma autenticada e é vinculada a ESTA organização com o papel abaixo.
                 */
                ->helperText('O convite sai por e-mail, com link de uso único. Se o endereço já tiver conta, ninguém é cadastrado de novo: a pessoa recebe uma oferta para entrar nesta '
                    .mb_strtolower((string) config('kit.tenancy.label', 'Organização')).' e escolhe aceitar ou recusar.')
                ->columnSpanFull(),

            Select::make('role_id')
                ->label('Papel')
                // Barreira 1 (UX): só papéis do painel app aparecem.
                ->relationship('papel', 'name', fn (Builder $query): Builder => $query->where('painel', 'app'))
                ->required()
                ->preload()
                ->searchable()
                /*
                 * E a mesma trava DE NOVO, no servidor. Aqui o papel é uma coluna escalar
                 * e não uma relação, então não há `saveRelationshipsUsing()` onde filtrar
                 * como no UserResource: o ponto de escrita é a validação, e um `role_id`
                 * forjado com o id de `admin` vira erro de formulário em vez de um convite
                 * que promove alguém a administrador da instalação. Ver ADR-07.
                 */
                ->rule(fn (): object => Rule::exists(config('permission.table_names.roles', 'roles'), 'id')
                    ->where('painel', 'app'))
                ->helperText('Só papéis do painel de negócio — e valem apenas dentro desta '
                    .mb_strtolower((string) config('kit.tenancy.label', 'Organização')).'.'),

            // Nenhum campo de organização: ela vem do painel, sempre. Ver
            // CreateConvite::mutateFormDataBeforeCreate().
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('email')->label('E-mail')->searchable()->sortable(),
                TextColumn::make('papel.name')->label('Papel')->badge(),
                TextColumn::make('expira_em')->label('Expira em')->dateTime('d/m/Y H:i')->sortable(),
                /*
                 * Situação DERIVADA pelo model, e não `aceito_em` com placeholder
                 * "Pendente": aquele placeholder mentia para convite recusado — mostrava
                 * "Pendente" para sempre, e o admin da organização reconvidaria alguém que
                 * já disse não.
                 */
                TextColumn::make('situacao')
                    ->label('Situação')
                    ->badge()
                    ->color(fn (Convite $record): string => match ($record->situacao()) {
                        'Aceito'   => 'success',
                        'Recusado' => 'gray',
                        'Expirado' => 'danger',
                        default    => 'warning',
                    })
                    ->state(fn (Convite $record): string => $record->situacao()),
            ])
            ->headerActions([
                CreateAction::make()->label('Novo convite'),
            ])
            ->emptyStateHeading('Nenhum convite enviado')
            ->emptyStateDescription('Convide alguém para que ela crie a própria senha e nasça dentro desta organização.');
    }

    /**
     * Convite não se edita nem se exclui a partir da organização: reenvio e revogação
     * vivem no /admin. Sem página de edição também não há rota para alcançar.
     */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListConvites::route('/'),
            'create' => CreateConvite::route('/create'),
        ];
    }
}

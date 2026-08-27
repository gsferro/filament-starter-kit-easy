<?php

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Concerns\AprovacaoDeCadastro;
use App\Filament\Concerns\BadgeContagemNavegacao;
use App\Models\User;
use App\Support\Papeis;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;
use STS\FilamentImpersonate\Actions\Impersonate;

class UserResource extends Resource
{
    use AprovacaoDeCadastro;
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
                ->unique(),
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
                ->getOptionLabelFromRecordUsing(fn (Role $record): string => Papeis::rotulo($record->name))
                ->multiple()
                ->preload()
                ->searchable()
                /*
                 * Papel é obrigatório porque é ele que dá acesso a painel
                 * (User::canAccessPanel lê `roles.painel`). Usuário sem papel é conta
                 * morta: entra na tela de login, autentica e leva 403 nos três painéis.
                 */
                // Obrigatório, MENOS para cadastro pendente de aprovação, que não tem papel por
                // desenho. Ver `AprovacaoDeCadastro::papelObrigatorioNaEdicao()`.
                ->required(self::papelObrigatorioNaEdicao())
                ->helperText('O acesso aos painéis vem do papel — o painel de cada um aparece ao lado do nome.')
                // O painel no rótulo da opção, e não um select agrupado: agrupar exigiria
                // abandonar o ->relationship(), que é quem hidrata o estado na edição e
                // mantém a chave fora do update() do model.
                // O papel é tipado pela classe do spatie, não por App\Models\Role: quem
                // resolve o model é `permission.models.role`, e `config/` fica fora do
                // kit:update — num projeto atualizado a config pode ainda apontar para o
                // model do pacote, e o type hint concreto viraria TypeError na tela.
                // O parâmetro TEM de se chamar `$record`: o Filament injeta closure por
                // NOME, não por tipo. Com outro nome a tela morre em
                // "[$papel] was unresolvable" só ao renderizar o campo.
                ->getOptionLabelFromRecordUsing(function (Role $record): string {
                    $painel = $record->getAttribute('painel');

                    return $painel === null
                        ? "{$record->name} — sem painel"
                        : "{$record->name} — /{$painel}";
                })
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

            /*
             * Só com a tenancy ligada. É este vínculo que impede o acesso indevido a
             * dados de outra organização: `User::canAccessTenant()` exige a linha na
             * pivot, e sem nenhuma o usuário entra no /app e não encontra organização
             * para abrir.
             *
             * Aqui o ->relationship() basta sozinho — a armadilha do sync() é específica
             * de `model_has_roles.team_id`, que é NOT NULL. A `tenant_user` é pivot magra,
             * só com as duas chaves.
             */
            Select::make('tenants')
                ->label(config('kit.tenancy.label_plural', 'Organizações'))
                ->relationship('tenants', 'nome')
                ->multiple()
                ->preload()
                ->searchable()
                ->required()
                ->visible(fn (): bool => (bool) config('kit.tenancy.enabled'))
                ->helperText('Sem vínculo o usuário entra no painel e não vê organização nenhuma.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                /*
                 * O avatar que a pessoa enviou no "Meu perfil" (Breezy, `hasAvatars: true`),
                 * ampliável em lightbox — `->simpleLightbox()`, do
                 * solution-forest/filament-simplelightbox.
                 *
                 * `disk('public')` explícito: o default é `local`, que aponta para
                 * storage/app/private e NÃO é servível por URL — a miniatura nasceria quebrada.
                 * É o mesmo disk em que o Breezy grava.
                 *
                 * SEM `defaultImageUrl()`: quem nunca enviou avatar tem de ficar com a célula
                 * VAZIA, não com um placeholder clicável que abriria o lightbox em cima de nada.
                 *
                 * O macro vem do plugin registrado no painel (AdminPanelProvider). Painel sem o
                 * plugin + esta linha = BadMethodCallException na renderização da tabela.
                 */
                ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->disk('public')
                    ->circular()
                    ->simpleLightbox(),
                TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                TextColumn::make('email')->label('E-mail')->searchable()->sortable(),
                TextColumn::make('roles.name')->label('Papéis')->badge()
                    ->formatStateUsing(fn (?string $state): string => Papeis::rotulo($state)),
                // Por qual porta a conta entrou: provedor social, convite, registro aberto ou
                // interno. Exibição, nunca autorização — ver `User::rotuloDaOrigem()`.
                TextColumn::make('origem')
                    ->label('Origem')
                    ->badge()
                    ->state(fn (User $record): string => $record->rotuloDaOrigem())
                    ->color(fn (User $record): string => ($record->origem ?? User::ORIGEM_INTERNO) === User::ORIGEM_INTERNO ? 'gray' : 'info')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->sortable(),
                self::colunaDeSituacao(),
            ])
            ->filters([
                self::filtroDePendentes(),
            ])
            ->recordActions([
                self::acaoDeAprovar(),
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

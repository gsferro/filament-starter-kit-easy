<?php

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Concerns\AprovacaoDeCadastro;
use App\Filament\Concerns\BadgeContagemNavegacao;
use App\Filament\Concerns\SituacaoDaConta;
use App\Models\Tenant;
use App\Models\User;
use App\Support\AdministradorDaInstalacao;
use App\Support\ContextoDePapeis;
use App\Support\Papeis;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use STS\FilamentImpersonate\Actions\Impersonate;

class UserResource extends Resource
{
    use AprovacaoDeCadastro;
    use BadgeContagemNavegacao;
    use SituacaoDaConta;

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
                // Recorte de UX do teto de escalada: quem não é master_global não vê o
                // master_global, nem papel de painel fora do alcance dele. A trava que vale
                // é em gravarPapeis(). O `$record` entra no recorte para que o papel que a
                // pessoa JÁ tem continue sendo opção válida — senão a ficha de quem tem
                // `infra` não salva mais.
                ->relationship('roles', 'name', fn (Builder $query, ?User $record): Builder => AdministradorDaInstalacao::recortarConcessao($query, $record))
                /*
                 * A UNIÃO dos papéis em qualquer contexto, não a relação `roles` do spatie:
                 * ela filtra pelo team do request, e no /admin (sem tenant) só mostraria os
                 * globais — e gravar a partir dessa lista apagaria os papéis do /app de toda
                 * organização. Ver gravarPapeis().
                 */
                ->loadStateFromRelationshipsUsing(function (Select $component, ?User $record): void {
                    if ($record === null) {
                        return;
                    }

                    $papeis = $record->papeisEmQualquerContexto();

                    $component->state(array_values(array_unique(array_map(
                        strval(...),
                        $papeis->pluck($papeis->getRelated()->getQualifiedKeyName())->all(),
                    ))));
                })
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

                    return Papeis::rotulo($record->name).' — '.($painel === null ? 'sem painel' : "/{$painel}");
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
                 * E o contexto de cada papel é decidido em gravarPapeis(), nunca
                 * herdado do request. O campo `tenants` do MESMO form diz em quais
                 * organizações o papel do /app deve ser gravado.
                 */
                ->saveRelationshipsUsing(function (User $record, array $state, Get $get): void {
                    self::gravarPapeis($record, array_values($state), array_values((array) ($get('tenants') ?? [])));
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
                self::filtroDeInativos(),
                /*
                 * A lixeira DESTA tela. `TrashedFilter` chama `withTrashed()`/`onlyTrashed()`,
                 * que removem o `SoftDeletingScope` sozinhos — por isso `getEloquentQuery()` não
                 * é tocada, e o badge de contagem do menu (que conta por ela) segue sem excluídos.
                 */
                TrashedFilter::make(),
            ])
            ->recordActions([
                self::acaoDeAprovar(),
                // Desativar/Reativar: só aqui, não no /app — ato global, mesma régua da exclusão
                // (ADR-04 da wiki status-e-exclusao-logica-de-usuario).
                self::acaoDeDesativar(),
                self::acaoDeReativar(),
                Impersonate::make(),
                EditAction::make(),
                // Excluir é LÓGICO (`SoftDeletes` no model); Restaurar só aparece em linha excluída e
                // autoriza por `Restore:User`, via `getRestoreAuthorizationResponse()` → policy.
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // `authorizeIndividualRecords('delete')` porque a BulkAction pergunta só
                    // `deleteAny` e nunca consulta a policy de cada registro selecionado
                    // (`Concerns/CanBeAuthorized.php:252-266`). Hoje `UserPolicy::delete()` não
                    // decide por registro, então a linha não muda comportamento — ela existe
                    // para o dia em que decidir, que é quando o buraco reabriria em silêncio.
                    DeleteBulkAction::make()->authorizeIndividualRecords('delete'),
                ]),
            ]);
    }

    /**
     * Grava os papéis do /admin no contexto CERTO de cada um — nunca no do request.
     *
     * O /admin não tem tenant, então o contexto do request é o global (`team_id = 0`).
     * Papel de painel SEM tenancy (`admin`, `infra`, `master_global`) mora aí. Papel do
     * painel `app` mora no `team_id` de cada organização selecionada em `tenants`: gravado
     * em 0 produzia alguém que autentica no /app e não vê nada — o mesmo sintoma da
     * v0.19.1 corrigido em `User::aprovar()`. Rule "Papel se atribui dentro de
     * ContextoDePapeis" (`.ai/rules/app.md`). Sem tenancy, tudo vai no global, como antes.
     *
     * Limitação assumida: o form mostra a UNIÃO dos papéis e não expressa papel DIFERENTE
     * por organização. Para não achatar o que já existe, a gravação por organização é por
     * DIFERENÇA — tira o que saiu da união, põe o que entrou, não mexe no que ficou — e a
     * organização em que a pessoa ainda não tem papel nenhum recebe a lista inteira (senão
     * ela entraria e não veria nada). Papel diferente por organização se dá no /app ou em
     * Organizações → Usuários → "Papéis nesta organização".
     *
     * Teto de escalada (F-01): `master_global` só entra pela mão de quem já o tem; vindo de
     * outro operador é descartado e logado, mesmo que o payload o traga. Efeito colateral
     * aceito: quem não é master não consegue salvar a ficha de um master — o papel dele
     * está fora das opções e a validação do Select recusa.
     *
     * Pública porque é a barreira, e barreira sem teste direto não é barreira.
     *
     * Os papéis são resolvidos em modelos antes de entrar: o state vem do Livewire como
     * string, e o `collectRoles()` do spatie trata string como NOME — `"4"` viraria
     * `RoleDoesNotExist`.
     *
     * @param  list<int|string>  $state  ids dos papéis selecionados
     * @param  list<int|string>  $organizacoes  ids das organizações do campo `tenants`
     */
    public static function gravarPapeis(User $record, array $state, array $organizacoes): void
    {
        $selecionados = AdministradorDaInstalacao::recortarConcessao(
            $record->roles()->getRelated()->newQuery()->whereKey($state),
            $record,
        )->get();

        if ($selecionados->count() !== count($state)) {
            Log::channel('autenticacao')->warning(
                "[UserResource@gravarPapeis] master_global descartado: operador não é master_global | alvo: {$record->id}",
                ['alvo_id' => $record->id, 'executor_id' => Auth::id(), 'ids_enviados' => $state, 'ids_aceitos' => $selecionados->modelKeys()],
            );
        }

        $antes = $record->papeisEmQualquerContexto()->get()->unique(fn (Model $papel): mixed => $papel->getKey());

        $doApp   = config('kit.tenancy.enabled') ? $selecionados->where('painel', 'app') : $selecionados->take(0);
        $globais = $selecionados->reject(fn (Model $papel): bool => $doApp->contains($papel));

        ContextoDePapeis::em(Tenant::CONTEXTO_GLOBAL, $record, fn () => $record->syncRoles($globais));

        $removidos   = $antes->filter(fn (Model $papel): bool => $papel->getAttribute('painel') === 'app' && ! $selecionados->contains($papel));
        $adicionados = $doApp->reject(fn (Model $papel): bool => $antes->contains($papel));

        foreach ($organizacoes as $organizacao) {
            ContextoDePapeis::em((int) $organizacao, $record, function () use ($record, $doApp, $removidos, $adicionados): void {
                $atuais = $record->roles()->get();

                $record->syncRoles($atuais->isEmpty()
                    ? $doApp
                    : $atuais->reject(fn (Model $papel): bool => $removidos->contains($papel))->merge($adicionados));
            });
        }

        Log::channel('autenticacao')->info(
            "[UserResource@gravarPapeis] Papéis atualizados pelo /admin | alvo: {$record->id}",
            [
                'alvo_id'      => $record->id,
                'executor_id'  => Auth::id(),
                'globais'      => $globais->pluck('name')->all(),
                'do_app'       => $doApp->pluck('name')->all(),
                'organizacoes' => array_map(intval(...), $organizacoes),
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

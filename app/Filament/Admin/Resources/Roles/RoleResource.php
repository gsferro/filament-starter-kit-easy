<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Roles;

use App\Filament\Admin\Resources\Roles\Pages\CreateRole;
use App\Filament\Admin\Resources\Roles\Pages\EditRole;
use App\Filament\Admin\Resources\Roles\Pages\ListRoles;
use App\Filament\Admin\Resources\Roles\Pages\ViewRole;
use App\Support\Paineis;
use App\Support\Papeis;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use BezhanSalleh\FilamentShield\Support\Utils;
use BezhanSalleh\FilamentShield\Traits\HasShieldFormComponents;
use BezhanSalleh\PluginEssentials\Concerns\Resource as Essentials;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Schemas\Components\EmptyState;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Unique;
use Override;
use RuntimeException;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Tela de papéis do Shield, PUBLICADA no projeto (`php artisan shield:publish --panel=admin`).
 *
 * Publicada porque o Shield não oferece hook para agrupar as permissões por painel — nada
 * em `HasShieldFormComponents` consulta o painel. As edições em relação ao vendor são
 * deliberadamente mínimas, para o diff de um upgrade continuar legível:
 *
 *   1. `Select::make('painel')` — é ele que dá acesso ao painel (User::canAccessPanel);
 *   2. `getResourceEntitiesSchema()` agrupa as permissões num tab VERTICAL por painel, com
 *      `selecionadas/total` no badge de cada painel;
 *   3. `secaoDoResource()` — o corpo do `map()` original, extraído para ser reusado, com o
 *      mesmo contador no cabeçalho da seção;
 *   3a. `guard_name` é `Select` das chaves de `config('auth.guards')`, não texto livre;
 *   3b. `getRecordTitle()` devolve `Papeis::rotulo()` — o default vazava a chave do papel para
 *      o breadcrumb, o título da tela de edição e a busca global;
 *   3c. a coluna `users_count` e a action `usuarios` (slide-over de leitura) não existem no
 *      vendor.
 *   4. os três pontos onde o tipo do vendor é largo demais para o do Filament — a **5ª
 *      divergência** na contagem de `wikis/pacotes.md`, que inclui as Pages logo abaixo, e é
 *      assim que estão marcados no corpo: `colunasDaGrade()` (o `getGridColumns()` do plugin é
 *      `int|string|array` e o `columns()` do Filament não aceita string nem array solto) e as
 *      guardas de `getModel()`/`getCluster()` (o `Utils` do Shield devolve `string`, e o
 *      Filament exige `class-string`). São normalizações de tipo, não mudança de
 *      comportamento: com config válida a tela é byte a byte a do vendor; com config inválida
 *      o erro passa a ser explícito em vez de "class not found" no meio do render.
 *
 * As Pages `CreateRole` e `EditRole` também foram tocadas: sem acrescentar `painel` às
 * listas de `mutateFormDataBefore*`, o Shield trataria o valor como lista de permissões e
 * criaria uma permission chamada `app`.
 *
 * Enquanto esta classe existir, o `FilamentShieldPlugin` não registra a dele:
 * `Utils::isResourcePublished()` procura `\RoleResource` entre os resources do painel.
 */
class RoleResource extends Resource
{
    use Essentials\BelongsToParent;
    use Essentials\BelongsToTenant;
    use Essentials\HasGlobalSearch;
    use Essentials\HasLabels;
    use Essentials\HasNavigation;
    use HasShieldFormComponents;

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('filament-shield::filament-shield.field.name'))
                                    ->unique(
                                        ignoreRecord: true, /** @phpstan-ignore-next-line */
                                        modifyRuleUsing: fn (Unique $rule): Unique => Utils::isTenancyEnabled() ? $rule->where(Utils::getTenantModelForeignKey(), Filament::getTenant()?->id) : $rule
                                    )
                                    ->required()
                                    ->maxLength(255),

                                Select::make('guard_name')
                                    ->label(__('filament-shield::filament-shield.field.guard_name'))
                                    /*
                                     * As chaves de `config('auth.guards')`, e nao texto livre.
                                     * Guard e o que amarra o papel ao provider de usuarios, e um
                                     * valor digitado errado cria um papel que nunca casa com
                                     * ninguem - sem erro nenhum, porque `guard_name` e so uma
                                     * string na tabela. Hoje o kit tem um guard (`web`); a lista
                                     * vem da config para que um projeto que acrescente o seu nao
                                     * precise tocar nesta tela.
                                     *
                                     * `required()` e nao `nullable()`: guard nulo chega ao
                                     * `firstOrCreate` de permission em `CreateRole@afterCreate`
                                     * (`CreateRole.php:44-47`) e cria permission com guard nulo,
                                     * que `checkPermissionTo()` nunca encontra.
                                     */
                                    /*
                                     * Sem `->in()` explicito, de proposito: aqui o Select JA
                                     * valida no servidor. `Select::getInValidationRuleValues()`
                                     * (`vendor/filament/forms/src/Components/Select.php:1787-1811`)
                                     * devolve `[]` quando o state nao casa com nenhuma opcao, e
                                     * `CanBeValidated::getInValidationRule()` (`:808-815`) o
                                     * transforma em `Rule::in([])` - que reprova qualquer valor.
                                     * Um `->in()` nosso SOBRESCREVERIA essa logica por uma pior,
                                     * porque ela tambem cobre opcao desabilitada.
                                     *
                                     * A diferenca em relacao a `ConviteResource::role_id`, que
                                     * PRECISA de `->rule()` explicito, e que la o Select e de
                                     * `->relationship()` e a trava e de ESCOPO (so papeis do
                                     * painel app), nao de dominio - `Rule::in` das opcoes nao
                                     * saberia recortar por painel.
                                     */
                                    ->options(self::guardsDaAplicacao(...))
                                    ->default(Utils::getFilamentAuthGuard())
                                    ->required()
                                    ->native(false),

                                Select::make('painel')
                                    ->label('Acesso ao painel')
                                    ->options(Paineis::opcoes())
                                    ->placeholder('Nenhum — o papel não abre painel')
                                    ->helperText('É este campo que dá acesso ao painel. Papel sem painel só carrega permissões: quem o tiver sozinho não entra em lugar nenhum.')
                                    ->native(false),

                                Select::make(config('permission.column_names.team_foreign_key'))
                                    ->label(__('filament-shield::filament-shield.field.team'))
                                    ->placeholder(__('filament-shield::filament-shield.field.team.placeholder'))
                                    /** @phpstan-ignore-next-line */
                                    ->default(Filament::getTenant()?->id)
                                    ->options(fn (): array => in_array(Utils::getTenantModel(), [null, '', '0'], true) ? [] : Utils::getTenantModel()::pluck('name', 'id')->toArray())
                                    ->visible(fn (): bool => static::shield()->isCentralApp() && Utils::isTenancyEnabled())
                                    ->dehydrated(fn (): bool => static::shield()->isCentralApp() && Utils::isTenancyEnabled()),
                                static::getSelectAllFormComponent(),

                            ])
                            ->columns([
                                'sm' => 2,
                                'lg' => 3,
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                static::getShieldFormComponents(),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->weight(FontWeight::Medium)
                    ->label(__('filament-shield::filament-shield.column.name'))
                    ->formatStateUsing(fn (string $state): string => Papeis::rotulo($state))
                    ->searchable(),
                TextColumn::make('guard_name')
                    ->badge()
                    ->color('warning')
                    ->label(__('filament-shield::filament-shield.column.guard_name')),
                TextColumn::make('painel')
                    ->label('Acesso ao painel')
                    ->badge()
                    ->color(fn (?string $state): string => $state === null ? 'gray' : 'success')
                    ->formatStateUsing(fn (?string $state): string => Papeis::rotuloDoPainel($state))
                    ->default(null),
                TextColumn::make('team.name')
                    ->default('Global')
                    ->badge()
                    ->color(fn (mixed $state): string => str($state)->contains('Global') ? 'gray' : 'primary')
                    ->label(__('filament-shield::filament-shield.column.team'))
                    ->searchable()
                    ->visible(fn (): bool => static::shield()->isCentralApp() && Utils::isTenancyEnabled()),
                TextColumn::make('users_count')
                    ->label('Usuários')
                    ->badge()
                    ->color(fn (?int $state): string => ($state ?? 0) === 0 ? 'gray' : 'primary')
                    /*
                     * `distinct` e nao `->counts('users')` puro: com `permission.teams` ligada, a
                     * chave primaria de `model_has_roles` inclui `team_id`
                     * (`database/migrations/2026_08_12_164859_create_permission_tables.php:88-93`),
                     * entao a MESMA pessoa com o MESMO papel em duas organizacoes sao DUAS linhas
                     * de pivot, e `count(*)` diria 2 para uma pessoa. A relacao nao dedupe
                     * sozinha: `Role::users()` e um `morphedByMany` sem filtro de team
                     * (`vendor/spatie/laravel-permission/src/Models/Role.php:100-109`) - o
                     * `wherePivot` de team o spatie poe em `HasRoles::roles()`, do lado do
                     * usuario. Ver ADR-04 de
                     * `wikis/specs/feat/perfis-e-permissoes/tela-de-perfis/`.
                     */
                    /*
                     * A expressao e LITERAL, e nao montada com `getQualifiedKeyName()`: o
                     * PHPStan recusa `Expression`/`DB::raw()` com string dinamica
                     * (`argument.type`, `TValue of float|int|literal-string`), e a regra existe
                     * por um bom motivo - SQL montado por concatenacao e como injecao entra.
                     *
                     * O preco e o acoplamento a `auth.providers.users.model` apontar para uma
                     * model na tabela `users`. Ele falha ALTO se alguem trocar: o SQL vira
                     * "no such column: users.id" na primeira abertura da listagem, em vez de
                     * devolver numero errado em silencio.
                     */
                    ->counts(['users' => fn (Builder $query): Builder => $query->select(
                        new Expression('count(distinct users.id)')
                    )])
                    ->sortable(),
                TextColumn::make('permissions_count')
                    ->badge()
                    ->label(__('filament-shield::filament-shield.column.permissions'))
                    ->counts('permissions')
                    ->color('primary'),
                TextColumn::make('updated_at')
                    ->label(__('filament-shield::filament-shield.column.updated_at'))
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                self::acaoDeUsuarios(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    /**
     * As permissões, agrupadas por painel num tab VERTICAL.
     *
     * Sobrescreve `HasShieldFormComponents::getResourceEntitiesSchema()`, que devolve uma
     * lista plana de seções — os Resources dos três painéis misturados, sem pista de onde
     * cada tela mora.
     *
     * Até a 0.18.8 o kit agrupava em `Section` collapsible por painel. Virou tab vertical
     * porque com dois painéis abertos a tela rolava por vários viewports e quem customiza
     * permissão perdia a referência de onde estava. Ver ADR-05 de
     * `wikis/specs/feat/perfis-e-permissoes/tela-de-perfis/`.
     *
     * A fonte NÃO pode ser `FilamentShield::getResources()`: ela devolve o painel
     * corrente, e esta tela vive no /admin — sairia um grupo só. Quem varre os três é
     * `App\Support\Paineis`.
     *
     * @return array<int, Tabs>
     */
    // Sem #[Override]: o método vem da trait HasShieldFormComponents, e o atributo só
    // vale para método de classe pai — com ele o PHP aborta o request inteiro.
    public static function getResourceEntitiesSchema(): ?array
    {
        $opcoes = Paineis::opcoes();

        $tabs = collect(Paineis::resources())
            ->reject(fn (array $entidades): bool => $entidades === [])
            ->map(fn (array $entidades, string $painel): Tab => Tab::make('painel-'.$painel)
                ->label('Painel '.$opcoes[$painel])
                ->icon(Heroicon::OutlinedRectangleGroup)
                ->badge(fn (Get $get): string => self::selecionadas($get, $entidades).'/'.self::totalDe($entidades))
                ->badgeColor(fn (Get $get): string => self::selecionadas($get, $entidades) === 0 ? 'gray' : 'primary')
                ->schema([
                    Grid::make()
                        ->schema(array_map(static::secaoDoResource(...), $entidades))
                        ->columns(self::colunasDaGrade()),
                ]))
            ->values()
            ->all();

        // Nome próprio, diferente do `Tabs::make('Permissions')` do vendor que envolve
        // este: dois Tabs com a mesma chave compartilhariam o estado de aba ativa, e o
        // clique num painel trocaria a aba de fora.
        return [Tabs::make('paineis')->vertical()->tabs($tabs)->columnSpanFull()];
    }

    /**
     * Quantas permissões destas entidades o papel já tem MARCADAS no formulário.
     *
     * O state de cada grupo vive sob o FQCN do Resource, porque é esse o `name` que o
     * Shield dá ao CheckboxList: `getCheckBoxListComponentForResource()` chama
     * `getCheckboxListFormComponent(name: $entity['resourceFqcn'], ...)`
     * (`vendor/bezhansalleh/filament-shield/src/Traits/HasShieldFormComponents.php:122-133`).
     * É a mesma premissa de que `CreateRole::mutateFormDataBeforeCreate()` já depende, ao
     * tratar toda chave de `$data` fora da lista de exclusão como permissão.
     *
     * FQCN tem barra invertida e nenhum ponto, e o `Get` do Filament separa caminho por
     * PONTO — então o FQCN é uma chave de primeiro nível válida, sem escape.
     *
     * Ler o STATE e não o banco é deliberado (ADR-06): contar de `$record->permissions`
     * deixaria o número parado justamente enquanto a pessoa marca as caixas, e em `create`
     * não há registro nenhum. A reatividade sai de graça porque o CheckboxList do Shield já
     * é `->live()` (`HasShieldFormComponents.php:209`).
     *
     * @param  list<array<string, mixed>>  $entidades
     */
    private static function selecionadas(Get $get, array $entidades): int
    {
        return array_sum(array_map(
            static fn (array $entidade): int => count((array) $get($entidade['resourceFqcn'])),
            $entidades,
        ));
    }

    /**
     * Quantas permissões estas entidades têm no total.
     *
     * @param  list<array<string, mixed>>  $entidades
     */
    private static function totalDe(array $entidades): int
    {
        return array_sum(array_map(
            static fn (array $entidade): int => count($entidade['permissions']),
            $entidades,
        ));
    }

    /**
     * Os guards da aplicação, no formato `chave => chave` que o Select consome.
     *
     * As CHAVES de `config('auth.guards')`, nunca os valores: o valor é o array
     * `['driver' => ..., 'provider' => ...]`, e não um nome de guard.
     *
     * @return array<string, string>
     */
    private static function guardsDaAplicacao(): array
    {
        $chaves = array_keys((array) config('auth.guards', []));

        return array_combine($chaves, $chaves);
    }

    /**
     * Quem tem este papel, num slide-over de leitura.
     *
     * `->authorize('view')` não é zelo: Action do Filament não consulta policy sozinha — o
     * default de `vendor/filament/actions/src/Concerns/CanBeAuthorized.php` é `null`, ou
     * seja, liberada para todo mundo. Sem a linha, quem recebeu apenas `ViewAny:Role` (para
     * abrir a listagem) passaria a ler o e-mail de todos os usuários da instalação. Ver
     * ADR-07.
     *
     * O log existe pelo mesmo motivo: leitura de e-mail de terceiro sem rastro é o tipo de
     * coisa de que ninguém sente falta até precisar. Channel `autenticacao`, o mesmo de
     * `CreateRole@afterCreate` — papel e permissão são assunto de autorização, e um channel
     * por tela fragmentaria a trilha.
     */
    private static function acaoDeUsuarios(): Action
    {
        return Action::make('usuarios')
            ->label('Ver usuários')
            ->icon(Heroicon::OutlinedUsers)
            ->color('gray')
            ->authorize('view')
            ->slideOver()
            ->modalHeading(fn (Model $record): string => 'Usuários com o papel '.Papeis::rotulo((string) $record->getAttribute('name')))
            ->modalDescription('Somente leitura. O vínculo se altera no cadastro do usuário.')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            /*
             * O log vive no hook de MONTAGEM, e nao em `->action()`.
             *
             * `->action()` seria codigo morto aqui: com `->modalSubmitAction(false)` nao existe
             * botao que dispare `callMountedAction`, e "Fechar" desmonta a action. So um
             * `callAction()` de teste chegaria la - o log ficaria verde no teste e nunca
             * aconteceria na tela, que e a pior forma de trilha de auditoria.
             *
             * `afterFormFilled` e chamado por `InteractsWithActions::mountAction()` logo depois
             * do `mount()`, uma vez por abertura
             * (`vendor/filament/actions/src/Concerns/InteractsWithActions.php:185-194`).
             */
            ->afterFormFilled(function (Model $record): void {
                Log::channel('autenticacao')->info(
                    '[RoleResource@usuarios] Lista de usuarios do papel consultada | papel: '.$record->getAttribute('name'),
                    [
                        'role_id'  => $record->getKey(),
                        'papel'    => $record->getAttribute('name'),
                        'executor' => auth()->id(),
                    ],
                );
            })
            ->schema([
                RepeatableEntry::make('usuarios')
                    ->hiddenLabel()
                    ->state(self::usuariosDoPapel(...))
                    ->table([
                        TableColumn::make('Nome'),
                        TableColumn::make('E-mail'),
                    ])
                    ->schema([
                        TextEntry::make('name')->hiddenLabel(),
                        TextEntry::make('email')->hiddenLabel()->copyable(),
                    ])
                    ->visible(fn (Model $record): bool => self::usuariosDoPapel($record) !== []),

                // `key()` para o teste poder afirmar sobre a VISIBILIDADE deste componente: o
                // Filament não renderiza o conteúdo do modal no HTML do componente pai, então
                // `assertSee` do texto não alcança. E é a visibilidade que importa aqui — ela é
                // complementar à da tabela acima, e um erro de sinal num dos dois deixa os dois
                // visíveis ou nenhum.
                EmptyState::make('Nenhum usuário tem este papel')
                    ->key('semUsuarios')
                    ->description('Papel sem ninguém vinculado não concede acesso a ninguém.')
                    ->icon(Heroicon::OutlinedUsers)
                    ->visible(fn (Model $record): bool => self::usuariosDoPapel($record) === []),
            ]);
    }

    /**
     * Os usuários do papel, uma vez cada.
     *
     * `distinct()` pelo mesmo motivo da coluna `users_count`: com tenancy, a mesma pessoa
     * com o mesmo papel em duas organizações são duas linhas em `model_has_roles`, e
     * `$papel->users` a devolveria duas vezes. Ver ADR-04.
     *
     * `once()` porque três closures do slide-over chamam este método na mesma abertura — o
     * `->state()` da tabela e os dois `->visible()` complementares. Sem a memoização são três
     * consultas idênticas por clique, e o modo de falhar é invisível: a tela fica certa.
     *
     * ponytail: lista inteira, sem paginação nem busca — "exibir todos os usuários" é o que
     * o requisito pede, e papel de instalação tem dezenas de pessoas. Se um dia tiver
     * milhares, a saída é um RelationManager, não paginar dentro de modal.
     *
     * @return array<int, Model>
     */
    private static function usuariosDoPapel(Model $record): array
    {
        return once(function () use ($record): array {
            /*
             * Mesma guarda de `CreateRole@afterCreate` e `EditRole@afterSave`, e pelo mesmo
             * motivo: `permission.models.role` é config, e um valor apontando para outra coisa
             * faria esta lista sair vazia em silêncio. Aqui devolver `[]` é o comportamento
             * correto — a tela mostra o estado vazio em vez de estourar —, mas a checagem
             * precisa existir para o analisador e para o leitor.
             */
            if (! $record instanceof SpatieRole) {
                return [];
            }

            return $record->users()->distinct()->orderBy('name')->get()->all();
        });
    }

    /**
     * As colunas da grade, no tipo que o `columns()` do Filament aceita (5ª divergência).
     *
     * `CanCustomizeColumns::getGridColumns()` devolve `int|string|array`. A string é herança
     * do `columnSpan` ('full'), que não faz sentido como CONTAGEM de coluna; e o array é o
     * mapa `breakpoint => colunas`, sem tipo declarado no plugin. Normalizar aqui é o que
     * impede um valor de config virar breakpoint inválido no Tailwind — falha que não gera
     * erro, só um layout errado.
     *
     * @return array<string, int>|int
     */
    private static function colunasDaGrade(): array|int
    {
        $colunas = static::shield()->getGridColumns();

        if (! is_array($colunas)) {
            return (int) $colunas;
        }

        $normalizadas = [];

        foreach ($colunas as $breakpoint => $quantidade) {
            $normalizadas[(string) $breakpoint] = (int) $quantidade;
        }

        return $normalizadas;
    }

    /**
     * Uma seção de Resource — o corpo do `map()` original do vendor, extraído para que o
     * agrupamento por painel reuse a marcação em vez de copiá-la.
     *
     * @param  array<string, mixed>  $entity
     */
    public static function secaoDoResource(array $entity): Section
    {
        $rotulo = strval(
            static::shield()->hasLocalizedPermissionLabels()
                ? FilamentShield::getLocalizedResourceLabel($entity['resourceFqcn'])
                : $entity['model']
        );

        return Section::make($rotulo)
            ->description(fn (): HtmlString => new HtmlString('<span style="word-break: break-word;">'.Utils::showModelPath($entity['modelFqcn']).'</span>'))
            /*
             * `selecionadas/total` deste Resource — o grupo mais fino que a tela tem.
             *
             * Por `afterHeader()` e não por `->badge()`: `Section` do Filament não tem badge
             * (`vendor/filament/schemas/src/Components/Section.php` só expõe
             * `afterHeader()`, `:159`), e pôr o número na `description` disputaria espaço com
             * o caminho do model, que é o que ela já mostra.
             */
            ->afterHeader([
                Text::make(fn (Get $get): string => self::selecionadas($get, [$entity]).'/'.self::totalDe([$entity]))
                    ->badge()
                    ->color(fn (Get $get): string => self::selecionadas($get, [$entity]) === 0 ? 'gray' : 'primary'),
            ])
            ->compact()
            ->schema([
                static::getCheckBoxListComponentForResource($entity),
            ])
            ->columnSpan(static::shield()->getSectionColumnSpan())
            ->collapsible();
    }

    /**
     * O título do registro — breadcrumb, título da tela de edição e resultado da busca global.
     *
     * O default devolve o atributo cru
     * (`vendor/filament/filament/src/Resources/Resource/Concerns/HasLabels.php:105-108`), e é
     * por isso que o breadcrumb de `/admin/shield/roles/{uuid}/edit` dizia `panel_user`.
     * Chave é identificador, não rótulo — a mesma razão de `App\Support\Papeis` existir.
     */
    #[Override]
    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return $record === null
            ? static::getModelLabel()
            : Papeis::rotulo((string) $record->getAttribute('name'));
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'view'   => ViewRole::route('/{record}'),
            'edit'   => EditRole::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModel(): string
    {
        $model = Utils::getRoleModel();

        // 5ª divergência: o `Utils` do Shield devolve `string` cru e o Filament exige uma
        // classe de Model — o Resource inteiro (query, policy, route binding) depende disso.
        // Config apontando para outra coisa quebraria adiante, com mensagem que não aponta
        // para a config.
        if (! is_a($model, Model::class, true)) {
            throw new RuntimeException("permission.models.role aponta para [{$model}], que não é um Eloquent Model.");
        }

        return $model;
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return Utils::getResourceSlug();
    }

    public static function getCluster(): ?string
    {
        $cluster = Utils::getResourceCluster();

        // 5ª divergência: mesma razão do `getModel()` acima — o Shield devolve `?string` e o
        // Filament exige uma classe de Cluster para montar navegação e breadcrumb.
        if ($cluster !== null && ! is_a($cluster, Cluster::class, true)) {
            throw new RuntimeException("filament-shield.shield_resource.cluster aponta para [{$cluster}], que não é um Cluster.");
        }

        return $cluster;
    }

    public static function getEssentialsPlugin(): ?FilamentShieldPlugin
    {
        return FilamentShieldPlugin::get();
    }
}

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
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Unique;
use Override;
use RuntimeException;

/**
 * Tela de papéis do Shield, PUBLICADA no projeto (`php artisan shield:publish --panel=admin`).
 *
 * Publicada porque o Shield não oferece hook para agrupar as permissões por painel — nada
 * em `HasShieldFormComponents` consulta o painel. As edições em relação ao vendor são
 * deliberadamente mínimas, para o diff de um upgrade continuar legível:
 *
 *   1. `Select::make('painel')` — é ele que dá acesso ao painel (User::canAccessPanel);
 *   2. `getResourceEntitiesSchema()` agrupa as seções por painel;
 *   3. `secaoDoResource()` — o corpo do `map()` original, extraído para ser reusado.
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

                                TextInput::make('guard_name')
                                    ->label(__('filament-shield::filament-shield.field.guard_name'))
                                    ->default(Utils::getFilamentAuthGuard())
                                    ->nullable()
                                    ->maxLength(255),

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
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    /**
     * As seções de permissão, agrupadas por painel.
     *
     * Sobrescreve `HasShieldFormComponents::getResourceEntitiesSchema()`, que devolve uma
     * lista plana de seções — os Resources dos três painéis misturados, sem pista de onde
     * cada tela mora.
     *
     * A fonte NÃO pode ser `FilamentShield::getResources()`: ela devolve o painel
     * corrente, e esta tela vive no /admin — sairia um grupo só. Quem varre os três é
     * `App\Support\Paineis`.
     *
     * @return array<int, Section>
     */
    // Sem #[Override]: o método vem da trait HasShieldFormComponents, e o atributo só
    // vale para método de classe pai — com ele o PHP aborta o request inteiro.
    public static function getResourceEntitiesSchema(): ?array
    {
        $opcoes = Paineis::opcoes();

        return collect(Paineis::resources())
            ->reject(fn (array $entidades): bool => $entidades === [])
            ->map(fn (array $entidades, string $painel): Section => Section::make('Painel '.$opcoes[$painel])
                ->description('Permissões dos Resources registrados neste painel.')
                ->collapsible()
                ->columnSpanFull()
                ->schema([
                    Grid::make()
                        ->schema(array_map(static::secaoDoResource(...), $entidades))
                        ->columns(self::colunasDaGrade()),
                ]))
            ->values()
            ->all();
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
            ->compact()
            ->schema([
                static::getCheckBoxListComponentForResource($entity),
            ])
            ->columnSpan(static::shield()->getSectionColumnSpan())
            ->collapsible();
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

<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Roles;

use App\Filament\Admin\Resources\Roles\Pages\CreateRole;
use App\Filament\Admin\Resources\Roles\Pages\EditRole;
use App\Filament\Admin\Resources\Roles\Pages\ListRoles;
use App\Filament\Admin\Resources\Roles\Pages\ViewRole;
use App\Support\Paineis;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use BezhanSalleh\FilamentShield\Support\Utils;
use BezhanSalleh\FilamentShield\Traits\HasShieldFormComponents;
use BezhanSalleh\PluginEssentials\Concerns\Resource as Essentials;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Override;

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
                                    ->label('Painel')
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
                    ->formatStateUsing(fn (string $state): string => Str::headline($state))
                    ->searchable(),
                TextColumn::make('guard_name')
                    ->badge()
                    ->color('warning')
                    ->label(__('filament-shield::filament-shield.column.guard_name')),
                TextColumn::make('painel')
                    ->label('Painel')
                    ->badge()
                    ->color(fn (?string $state): string => $state === null ? 'gray' : 'success')
                    ->formatStateUsing(fn (?string $state): string => $state === null ? '—' : '/'.$state)
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
                        ->columns(static::shield()->getGridColumns()),
                ]))
            ->values()
            ->all();
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
        return Utils::getRoleModel();
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return Utils::getResourceSlug();
    }

    public static function getCluster(): ?string
    {
        return Utils::getResourceCluster();
    }

    public static function getEssentialsPlugin(): ?FilamentShieldPlugin
    {
        return FilamentShieldPlugin::get();
    }
}

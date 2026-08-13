<?php

namespace App\Filament\Admin\Resources\Tenants;

use App\Filament\Admin\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use App\Filament\Admin\Resources\Tenants\Pages\ListTenants;
use App\Filament\Admin\Resources\Tenants\RelationManagers\UsersRelationManager;
use App\Filament\Admin\Resources\Tenants\Schemas\TenantForm;
use App\Filament\Admin\Resources\Tenants\Tables\TenantsTable;
use App\Filament\Concerns\BadgeContagemNavegacao;
use App\Models\Tenant;
use BackedEnum;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Cadastro de tenants.
 *
 * Vive no painel `admin` e NÃO é escopado por tenant: quem administra os
 * tenants precisa enxergar todos. O recorte vale para o painel `/app`, que é a
 * operação do negócio.
 *
 * O vínculo usuário ↔ tenant é feito pelo relation manager: é ele que decide
 * quem consegue abrir `/app/{slug}`.
 *
 * ## Rótulo e URL configuráveis
 *
 * A classe segue o vocabulário da API do Filament, mas tudo que o usuário lê
 * (menu, títulos, mensagens) e o segmento da URL saem de `config('kit.tenancy')`
 * — "Organização"/"organizacoes" por default. Por isso são MÉTODOS e não
 * propriedades estáticas: propriedade estática é avaliada antes da config
 * existir.
 *
 * Só aparece com o modo multi-tenant ligado — sem tenancy a tabela existe mas
 * não significa nada.
 */
class TenantResource extends Resource
{
    use BadgeContagemNavegacao;

    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Administração';

    protected static ?string $recordTitleAttribute = 'nome';

    public static function getModelLabel(): string
    {
        return mb_strtolower((string) config('kit.tenancy.label', 'Organização'));
    }

    public static function getPluralModelLabel(): string
    {
        return mb_strtolower((string) config('kit.tenancy.label_plural', 'Organizações'));
    }

    public static function getNavigationLabel(): string
    {
        return (string) config('kit.tenancy.label_plural', 'Organizações');
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return (string) config('kit.tenancy.slug', 'organizacoes');
    }

    /**
     * Sem tenancy ligada o resource some do menu e da busca ⌘K — a categoria
     * "Telas" do Spotlight consulta `canAccess()`, e o menu, este método.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return (bool) config('kit.tenancy.enabled');
    }

    public static function canAccess(): bool
    {
        return config('kit.tenancy.enabled') && parent::canAccess();
    }

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['nome', 'slug'];
    }

    public static function form(Schema $schema): Schema
    {
        return TenantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'edit'   => EditTenant::route('/{record}/edit'),
        ];
    }
}

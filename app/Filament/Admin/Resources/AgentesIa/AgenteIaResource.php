<?php

namespace App\Filament\Admin\Resources\AgentesIa;

use App\Filament\Admin\Resources\AgentesIa\Pages\CreateAgenteIa;
use App\Filament\Admin\Resources\AgentesIa\Pages\EditAgenteIa;
use App\Filament\Admin\Resources\AgentesIa\Pages\ListAgentesIa;
use App\Filament\Admin\Resources\AgentesIa\Schemas\AgenteIaForm;
use App\Filament\Admin\Resources\AgentesIa\Tables\AgentesIaTable;
use App\Filament\Concerns\BadgeContagemNavegacao;
use App\Models\AgenteIa;
use App\Support\AgentesDeIa;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Catálogo dos agentes de IA — o "paper" de cada agente como registro editável.
 *
 * Vive no painel `admin` porque é operação TÉCNICA: provider, modelo, temperatura, tools,
 * guardrails e system prompt não são decisão de quem opera o negócio. O acesso ao painel já
 * é restrito (`User::canAccessPanel`), o que é o portão desta superfície.
 *
 * As instruções (prompt) NÃO entram na busca global: são configuração sensível.
 *
 * Ligado pelo settings (wiki `agentes-ia-no-settings`): com o uso de agentes desligado — o
 * default — o catálogo some do menu, do hub, do ⌘K e do catálogo do dock (`canAccess()` e
 * `shouldRegisterNavigation()`, os dois, `.ai/rules/filament.md`), e as três Pages respondem 404
 * pelo trait `ExigeAgentesDeIaLigados`, mesmo para quem tem a permissão.
 */
class AgenteIaResource extends Resource
{
    use BadgeContagemNavegacao;

    protected static ?string $model = AgenteIa::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?string $modelLabel = 'Agente de IA';

    protected static ?string $pluralModelLabel = 'Agentes de IA';

    protected static string|UnitEnum|null $navigationGroup = 'IA';

    protected static ?string $slug = 'agentes-ia';

    protected static ?string $recordTitleAttribute = 'nome';

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['nome', 'slug'];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return AgentesDeIa::habilitado() && parent::shouldRegisterNavigation();
    }

    /**
     * Com `&& parent::canAccess()`: sem ele a policy deixaria de ser consultada para o índice
     * (`CanAuthorizeResourceAccess` chama `canAccess()`, não `canViewAny()`). A busca global já
     * segue este método (`HasGlobalSearch::canGloballySearch()`).
     */
    public static function canAccess(): bool
    {
        return AgentesDeIa::habilitado() && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return AgenteIaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AgentesIaTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListAgentesIa::route('/'),
            'create' => CreateAgenteIa::route('/create'),
            'edit'   => EditAgenteIa::route('/{record}/edit'),
        ];
    }
}

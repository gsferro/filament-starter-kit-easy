<?php

namespace App\Filament\Admin\Resources\AgentesIa;

use App\Filament\Admin\Resources\AgentesIa\Pages\CreateAgenteIa;
use App\Filament\Admin\Resources\AgentesIa\Pages\EditAgenteIa;
use App\Filament\Admin\Resources\AgentesIa\Pages\ListAgentesIa;
use App\Filament\Admin\Resources\AgentesIa\Schemas\AgenteIaForm;
use App\Filament\Admin\Resources\AgentesIa\Tables\AgentesIaTable;
use App\Models\AgenteIa;
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
 */
class AgenteIaResource extends Resource
{
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

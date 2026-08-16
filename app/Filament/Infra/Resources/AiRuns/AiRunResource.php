<?php

namespace App\Filament\Infra\Resources\AiRuns;

use App\Filament\Concerns\BadgeContagemNavegacao;
use App\Filament\Infra\Resources\AiRuns\Pages\ListAiRuns;
use App\Filament\Infra\Resources\AiRuns\Pages\ViewAiRun;
use App\Filament\Infra\Resources\AiRuns\Schemas\AiRunInfolist;
use App\Filament\Infra\Resources\AiRuns\Tables\AiRunsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Fomvasss\AiTasks\Models\AiRun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Ledger de execuções de IA (tabela `ai_runs` do fomvasss/laravel-ai-tasks, alimentada pelo
 * listener `App\Ai\Listeners\RegistrarAiRun`). READ-ONLY: trilha é observabilidade, nunca
 * editada à mão — por isso não há Create/Edit/Delete nem policy de escrita.
 *
 * Mesmo gate do dashboard `/ai-tasks` do pacote (`ver-ai-tasks`).
 *
 * `request`/`response` NUNCA entram na busca: carregam prompt e resposta do modelo, com dado
 * do usuário. Só aparecem na tela de detalhe, e apenas quando `ai-tasks.store_request` estiver
 * ligado.
 */
class AiRunResource extends Resource
{
    use BadgeContagemNavegacao;

    protected static ?string $model = AiRun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static string|UnitEnum|null $navigationGroup = 'IA';

    protected static ?string $navigationLabel = 'Execuções de IA';

    protected static ?string $modelLabel = 'Execução de IA';

    protected static ?string $pluralModelLabel = 'Execuções de IA';

    protected static ?string $slug = 'execucoes-ia';

    protected static ?string $recordTitleAttribute = 'task';

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['task', 'driver', 'model'];
    }

    /**
     * @param  AiRun  $record
     */
    public static function getGlobalSearchResultTitle(Model $record): string
    {
        // Model do vendor sem @property declarado: getAttribute() em vez de acesso mágico.
        return $record->getAttribute('task').' — '.$record->getAttribute('driver');
    }

    /**
     * @param  AiRun  $record
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return array_filter([
            'Status' => $record->getAttribute('status'),
            'Modelo' => $record->getAttribute('model'),
            'Quando' => $record->getAttribute('created_at')?->format('d/m/Y H:i'),
        ]);
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('ver-ai-tasks') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return AiRunsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AiRunInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiRuns::route('/'),
            'view'  => ViewAiRun::route('/{record}'),
        ];
    }
}

<?php

namespace App\Filament\Infra\Resources\AiRuns\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Fomvasss\AiTasks\Models\AiRun;
use Gsferro\FilamentOdometerEasy\Infolists\Components\OdometerEntry;

/**
 * View read-only da execução de IA. Request/response aparecem como JSON e só têm conteúdo
 * quando `ai-tasks.store_request` está ligado — por padrão o kit NÃO persiste prompt nem
 * resposta.
 */
class AiRunInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Execução')->columns(2)->schema([
                TextEntry::make('created_at')->label('Quando')->dateTime('d/m/Y H:i:s'),
                TextEntry::make('task')->label('Tarefa')->badge(),
                TextEntry::make('driver')->label('Driver')->badge()->color('gray'),
                TextEntry::make('request.options.model')->label('Modelo')->placeholder('—'),
                TextEntry::make('status')->label('Status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ok'            => 'success',
                        'error', 'dead' => 'danger',
                        'running'       => 'info',
                        'waiting'       => 'warning',
                        default         => 'gray',
                    }),
                TextEntry::make('dispatch')->label('Dispatch')->badge()->color('gray'),
                TextEntry::make('subject_id')->label('Usuário (subject)')->placeholder('—'),
                TextEntry::make('error')->label('Erro')->placeholder('—')->color('danger')->columnSpanFull(),
            ]),
            Section::make('Consumo')->columns(3)->schema([
                OdometerEntry::make('tokens_in')->label('Tokens in')->placeholder('—'),
                OdometerEntry::make('tokens_out')->label('Tokens out')->placeholder('—'),
                OdometerEntry::make('cost')->label('Custo (USD)')
                    ->format(['style' => 'currency', 'currency' => 'USD', 'minimumFractionDigits' => 2, 'maximumFractionDigits' => 6])
                    ->placeholder('—'),
                OdometerEntry::make('cache_read_tokens')->label('Cache lido')->placeholder('—'),
                OdometerEntry::make('cache_write_tokens')->label('Cache gravado')->placeholder('—'),
                OdometerEntry::make('duration_ms')->label('Duração (ms)')->placeholder('—'),
            ]),
            Section::make('Detalhes')->collapsed()->schema([
                // Model do vendor sem @property declarado: getAttribute() em vez de acesso
                // mágico (o cast `array` do pacote continua valendo).
                TextEntry::make('request')->label('Request')
                    ->state(fn (AiRun $record): string => self::json($record->getAttribute('request'))),
                TextEntry::make('response')->label('Response')
                    ->state(fn (AiRun $record): string => self::json($record->getAttribute('response'))),
            ]),
        ]);
    }

    /** JSON legível para exibição; vazio/null vira travessão. */
    private static function json(mixed $valor): string
    {
        if (blank($valor)) {
            return '—';
        }

        return json_encode($valor, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—';
    }
}

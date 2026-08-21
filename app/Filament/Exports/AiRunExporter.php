<?php

namespace App\Filament\Exports;

use App\Support\ImportExport\ExportadorDoKit;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Fomvasss\AiTasks\Models\AiRun;
use Illuminate\Support\Number;

/**
 * Export do ledger de custo de IA — o caso em que exportar é o ponto da tela.
 *
 * `request` e `response` ficam FORA, e essa é a decisão inteira do arquivo: são o prompt
 * e a resposta completos, de qualquer organização, e o `/infra` não tem tenant na rota
 * para recortar nada. Um CSV com essas duas colunas é o conteúdo das conversas da
 * instalação em anexo de e-mail.
 *
 * O que sobra é o que a tela existe para responder: quanto custou, quanto demorou, o que
 * falhou.
 */
class AiRunExporter extends ExportadorDoKit
{
    protected static ?string $model = AiRun::class;

    /**
     * @return array<ExportColumn>
     */
    protected static function colunas(): array
    {
        return [
            ExportColumn::make('task')
                ->label('Tarefa'),
            ExportColumn::make('driver')
                ->label('Driver'),
            ExportColumn::make('model')
                ->label('Modelo'),
            ExportColumn::make('status')
                ->label('Status'),
            ExportColumn::make('tokens_in')
                ->label('Tokens de entrada'),
            ExportColumn::make('tokens_out')
                ->label('Tokens de saída'),
            ExportColumn::make('cost')
                ->label('Custo'),
            ExportColumn::make('duration_ms')
                ->label('Duração (ms)'),
            ExportColumn::make('started_at')
                ->label('Início'),
            ExportColumn::make('finished_at')
                ->label('Fim'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $corpo = 'Exportação de execuções de IA concluída: '
            .Number::format($export->successful_rows).' '
            .str('linha')->plural($export->successful_rows).' exportada'
            .($export->successful_rows === 1 ? '' : 's').'.';

        if ($falhas = $export->getFailedRowsCount()) {
            $corpo .= ' '.Number::format($falhas).' '
                .str('linha')->plural($falhas).' falhou.';
        }

        return $corpo;
    }
}

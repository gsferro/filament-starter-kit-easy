<?php

namespace App\Filament\Exports;

use App\Models\AgenteIa;
use App\Support\ImportExport\ExportadorDoKit;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

/**
 * Export de agentes de IA — configuração, sem dado pessoal.
 *
 * `tools` e `guardrails` ficam fora: são `array` no cast, e array em célula de CSV vira
 * `Array` ou JSON escapado — ruído que não reimporta.
 */
class AgenteIaExporter extends ExportadorDoKit
{
    protected static ?string $model = AgenteIa::class;

    /**
     * @return array<ExportColumn>
     */
    protected static function colunas(): array
    {
        return [
            ExportColumn::make('slug')
                ->label('Slug'),
            ExportColumn::make('nome')
                ->label('Nome'),
            ExportColumn::make('descricao')
                ->label('Descrição'),
            ExportColumn::make('ativo')
                ->label('Ativo'),
            ExportColumn::make('provider')
                ->label('Provider'),
            ExportColumn::make('modelo')
                ->label('Modelo'),
            ExportColumn::make('temperatura')
                ->label('Temperatura'),
            ExportColumn::make('max_tokens')
                ->label('Máximo de tokens'),
            ExportColumn::make('versao')
                ->label('Versão'),
            ExportColumn::make('created_at')
                ->label('Criado em'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $corpo = 'Exportação de agentes de IA concluída: '
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

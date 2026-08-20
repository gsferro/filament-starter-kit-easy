<?php

namespace App\Filament\Exports;

use App\Models\Convite;
use App\Support\ImportExport\ExportadorDoKit;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

/**
 * Export de convites — **nasce comentado**, e com duas colunas deliberadamente ausentes.
 *
 * `token` e `token_lembrete` NÃO entram. O gerador do Filament as inclui porque são
 * colunas do banco, e exportá-las é entregar acesso: `Convite::aceitar()` valida o token
 * e vincula o usuário à organização com o papel do convite. Um CSV com a coluna `token`
 * é uma planilha de chaves de entrada.
 *
 * `email` fica, e é justamente por causa dele que a Action nasce comentada.
 */
class ConviteExporter extends ExportadorDoKit
{
    protected static ?string $model = Convite::class;

    /**
     * @return array<ExportColumn>
     */
    protected static function colunas(): array
    {
        return [
            ExportColumn::make('email')
                ->label('E-mail'),
            ExportColumn::make('tenant.nome')
                ->label('Organização'),
            ExportColumn::make('convidadoPor.name')
                ->label('Convidado por'),
            ExportColumn::make('expira_em')
                ->label('Expira em'),
            ExportColumn::make('enviado_em')
                ->label('Enviado em'),
            ExportColumn::make('aceito_em')
                ->label('Aceito em'),
            ExportColumn::make('recusado_em')
                ->label('Recusado em'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $corpo = 'Exportação de convites concluída: '
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

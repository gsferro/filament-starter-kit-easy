<?php

namespace App\Filament\Exports;

use App\Models\Tenant;
use App\Support\ImportExport\ExportadorDoKit;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

/**
 * Export de organizações — o inventário da instalação.
 *
 * `/admin` não tem tenant na rota, então este export é global por construção, e é o que
 * se quer: quem alcança a tela de organizações administra a instalação.
 *
 * `logo` fica fora: é caminho de arquivo em disco, inútil em planilha e uma dica de
 * onde procurar arquivo para quem receber o CSV.
 */
class TenantExporter extends ExportadorDoKit
{
    protected static ?string $model = Tenant::class;

    /**
     * @return array<ExportColumn>
     */
    protected static function colunas(): array
    {
        return [
            ExportColumn::make('nome')
                ->label('Nome'),
            ExportColumn::make('slug')
                ->label('Slug'),
            ExportColumn::make('ativo')
                ->label('Ativa'),
            ExportColumn::make('created_at')
                ->label('Criada em'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $corpo = 'Exportação de organizações concluída: '
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

<?php

namespace App\Filament\Exports;

use App\Models\Projeto;
use App\Support\ImportExport\ExportadorDoKit;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

/**
 * Export de projetos — o exemplo de referência da convenção do kit.
 *
 * O recorte por organização não está escrito aqui, e é de propósito: a query vem da
 * tabela da tela (`getTableQueryForExport()`), montada no request, onde o escopo global
 * de `BelongsToTenant` aplica o `where tenant_id = X`. Ela é serializada COM esse
 * `where` e é isso que o job executa.
 *
 * `tenant.nome` em vez de `tenant_id`: o ID não diz nada em planilha, e num export que
 * já é de uma organização só ele é redundante.
 */
class ProjetoExporter extends ExportadorDoKit
{
    protected static ?string $model = Projeto::class;

    /**
     * @return array<ExportColumn>
     */
    protected static function colunas(): array
    {
        return [
            ExportColumn::make('nome')
                ->label('Nome'),
            ExportColumn::make('tenant.nome')
                ->label('Organização'),
            ExportColumn::make('created_at')
                ->label('Criado em'),
            ExportColumn::make('updated_at')
                ->label('Atualizado em'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $corpo = 'Exportação de projetos concluída: '
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

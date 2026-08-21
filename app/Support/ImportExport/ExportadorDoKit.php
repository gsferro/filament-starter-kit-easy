<?php

namespace App\Support\ImportExport;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;

/**
 * Export do Filament com formula injection neutralizada em TODA coluna.
 *
 * O escopo por organização não precisa de nada aqui, e vale saber por quê: a query do
 * export vem da tabela da tela (`CanExportRecords::getTableQueryForExport()`), montada
 * **no request**, onde `BelongsToTenant` aplica o `where tenant_id = X`. Ela é
 * serializada com esse `where` dentro e é isso que o job executa. O isolamento do export
 * é herdado, não construído — e é o inverso exato do import, onde a resolução acontece
 * dentro do worker e o escopo se perde (ver `ImportadorDoKit`).
 *
 * O que o pacote **não** faz é neutralizar formula injection: `preventFormulaInjection()`
 * existe por coluna (`Exports\Concerns\CanFormatState:102`) e nasce desligado. Um CSV
 * exportado com uma célula começando em `=`, `+`, `-` ou `@` vira fórmula quando alguém
 * abre no Excel, e o dado que a preencheu veio de formulário de usuário. Aqui a
 * neutralização é aplicada a toda coluna que a subclasse declarar, para que ligar seja o
 * default e desligar seja a decisão explícita.
 *
 * Uso: declare `colunas()` em vez de `getColumns()`.
 *
 * ```php
 * class ProjetoExporter extends ExportadorDoKit
 * {
 *     protected static ?string $model = Projeto::class;
 *
 *     protected static function colunas(): array
 *     {
 *         return [ExportColumn::make('nome')];
 *     }
 * }
 * ```
 */
abstract class ExportadorDoKit extends Exporter
{
    /**
     * As colunas do export, sem a neutralização — ela é aplicada por `getColumns()`.
     *
     * @return array<ExportColumn>
     */
    abstract protected static function colunas(): array;

    /**
     * @return array<ExportColumn>
     */
    final public static function getColumns(): array
    {
        return array_map(
            fn (ExportColumn $coluna): ExportColumn => $coluna->preventFormulaInjection(),
            static::colunas(),
        );
    }
}

<?php

namespace App\Filament\Imports;

use App\Models\Projeto;
use App\Support\ImportExport\ImportadorDoKit;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

/**
 * Import de projetos — o exemplo de referência da convenção do kit.
 *
 * **`tenant` NÃO é coluna do CSV, e essa ausência é a feature.** O gerador do Filament
 * cria `ImportColumn::make('tenant')->relationship()` para toda FK, e aceitá-la aqui
 * deixaria o CSV escolher a organização de destino — o vazamento que o
 * `ImportadorDoKit` fecha, reaberto pela porta da frente. A organização vem do request,
 * via `->options(['tenant_id' => …])` na Action.
 *
 * `uuid` também sai: é gerado pela trait `TemUuid` na criação, e importá-lo permitiria
 * colidir com registro de outra organização.
 */
class ProjetoImporter extends ImportadorDoKit
{
    protected static ?string $model = Projeto::class;

    protected function colunaDeResolucao(): string
    {
        return 'nome';
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nome')
                ->label('Nome')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $corpo = 'Importação de projetos concluída: '
            .Number::format($import->successful_rows).' '
            .str('linha')->plural($import->successful_rows).' importada'
            .($import->successful_rows === 1 ? '' : 's').'.';

        if ($falhas = $import->getFailedRowsCount()) {
            $corpo .= ' '.Number::format($falhas).' '
                .str('linha')->plural($falhas).' falhou'
                .($falhas === 1 ? '' : ' (ver o CSV de falhas)').'.';
        }

        return $corpo;
    }
}

<?php

namespace App\Filament\Imports;

use App\Models\AgenteIa;
use App\Support\ImportExport\ImportadorDoKit;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

/**
 * Import de agentes de IA — configuração, não dado pessoal.
 *
 * Vive no `/admin`, que não tem tenant na rota, e `AgenteIa` não usa `BelongsToTenant`:
 * `exigeEscopoDeTenant()` devolve `false` e nenhuma option de organização é necessária.
 * É o caso que prova que o `ImportadorDoKit` não morre no modo single-tenant.
 *
 * `uuid` fora das colunas: gerado pela trait `TemUuid` e fora do `$fillable`.
 * `temperatura` é `float` no cast do model — o gerador do Filament inferiu `integer` da
 * coluna do banco e teria recusado `0.7`.
 */
class AgenteIaImporter extends ImportadorDoKit
{
    protected static ?string $model = AgenteIa::class;

    protected function colunaDeResolucao(): string
    {
        return 'slug';
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('slug')
                ->label('Slug')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('nome')
                ->label('Nome')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('descricao')
                ->label('Descrição'),
            ImportColumn::make('ativo')
                ->label('Ativo')
                ->boolean()
                ->rules(['boolean']),
            ImportColumn::make('provider')
                ->label('Provider'),
            ImportColumn::make('modelo')
                ->label('Modelo'),
            ImportColumn::make('temperatura')
                ->label('Temperatura')
                ->numeric()
                ->rules(['numeric', 'between:0,2']),
            ImportColumn::make('max_tokens')
                ->label('Máximo de tokens')
                ->numeric()
                ->rules(['integer', 'min:1']),
            ImportColumn::make('instrucoes')
                ->label('Instruções')
                ->requiredMapping()
                ->rules(['required', 'string']),
            ImportColumn::make('versao')
                ->label('Versão')
                ->numeric()
                ->rules(['integer', 'min:1']),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $corpo = 'Importação de agentes de IA concluída: '
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

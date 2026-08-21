<?php

namespace App\Filament\Exports;

use App\Models\User;
use App\Support\ImportExport\ExportadorDoKit;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

/**
 * Export de usuários — **nasce comentado nas Actions**, e é o motivo de este arquivo
 * existir sem estar ligado em lugar nenhum.
 *
 * A classe precisa existir para que descomentar a linha na Page seja uma linha só. Mas
 * ligar export de usuários é decidir expor o e-mail de todo mundo num arquivo que sai da
 * aplicação — decisão de quem opera a instalação, não default do kit.
 *
 * `avatar_url` fora: caminho de arquivo. `email_verified_at` fica, porque é o que
 * distingue conta ativa de convite pendente numa auditoria de acesso.
 */
class UserExporter extends ExportadorDoKit
{
    protected static ?string $model = User::class;

    /**
     * @return array<ExportColumn>
     */
    protected static function colunas(): array
    {
        return [
            ExportColumn::make('name')
                ->label('Nome'),
            ExportColumn::make('email')
                ->label('E-mail'),
            ExportColumn::make('email_verified_at')
                ->label('E-mail verificado em'),
            ExportColumn::make('created_at')
                ->label('Criado em'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $corpo = 'Exportação de usuários concluída: '
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

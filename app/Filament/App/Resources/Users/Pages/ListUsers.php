<?php

namespace App\Filament\App\Resources\Users\Pages;

use App\Filament\App\Resources\Users\UserResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    use HasResizableColumn;

    protected static string $resource = UserResource::class;

    /**
     * O botão de criar vive AQUI, no cabeçalho da página, e não no
     * `headerActions()` da tabela.
     *
     * É a convenção do kit — as sete telas de listagem fazem assim —, e ter os
     * dois ao mesmo tempo é o que produzia dois botões idênticos na mesma tela.
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            /*
             * Export de usuários — **desligado de propósito**. Descomente ciente do que
             * liga: a planilha sai com o e-mail de todo mundo que tem acesso, e o arquivo
             * deixa a aplicação. `App\Filament\Exports\UserExporter` já existe pronto.
             *
             * Import de usuário NÃO existe, e não é esquecimento: criar conta por CSV
             * contorna convite, verificação de e-mail e atribuição de papel — os três
             * pilares do acesso no kit.
             */
            // ExportAction::make()
            //     ->exporter(UserExporter::class)
            //     ->authorize('export'),
        ];
    }
}

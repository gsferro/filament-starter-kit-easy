<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class ListUsers extends ListRecords
{
    use HasResizableColumn;

    protected static string $resource = UserResource::class;

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

    /**
     * Abas de recorte: "Todos" e "Pendentes de aprovação".
     *
     * A aba é o recorte de UM clique; o filtro do modal continua existindo para COMBINAR
     * (com a lixeira, com a busca). A regra do recorte é uma só, em
     * `AprovacaoDeCadastro::recorteDePendentes()`.
     *
     * A contagem do badge sai do `getEloquentQuery()` do Resource, nunca de `User::query()`:
     * no /app a listagem é recortada por organização, e um badge contando a instalação
     * inteira informaria quantos existem fora dela ao lado de uma tabela que mostra só a
     * organização corrente. ADR-02 da wiki abas-nas-listagens.
     *
     * "Todos" é a primeira chave porque o Filament ativa a primeira quando não há `?tab=` —
     * a tela de quem não clicar em nada continua sendo a de hoje. A aba ativa não persiste
     * na sessão (é nativo): para linkar uma listagem já recortada, use
     * `ListUsers::getUrl(['tab' => 'pendentes'])`.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos'),

            'pendentes' => Tab::make('Pendentes de aprovação')
                ->icon(Heroicon::OutlinedClock)
                ->badge(fn (): int => UserResource::recorteDePendentes(UserResource::getEloquentQuery())->count())
                ->modifyQueryUsing(UserResource::recorteDePendentes(...)),
        ];
    }
}

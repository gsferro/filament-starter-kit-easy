<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Filament\Concerns\FichaDeUsuario;
use App\Models\User;
use App\Support\Papeis;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * A ficha somente-leitura de uma conta no /admin.
 *
 * O que ela tem a mais que a do /app e a secao "Vinculos": as organizacoes e os papeis, em TODOS
 * os contextos. Ela so pode existir aqui — no /app, listar as organizacoes de alguem contaria a
 * quem administra a Acme que aquela pessoa tambem e da Globex. Ver o bloco "O que esta secao NAO
 * contem" em `App\Filament\Concerns\FichaDeUsuario`.
 */
class UserInfolist
{
    use FichaDeUsuario;

    public static function configure(Schema $schema): Schema
    {
        /*
         * `columns(1)` explicito, e nao o default. `ViewRecord::defaultInfolist():182` aplica
         * `columns(2)` quando o schema nao declara — e como cada `Section` daqui ja divide o
         * interior em 2, a entrada acabaria com 25% da largura da pagina ("Cadastrado em
         * 24/08/2026 14:31" num quarto de tela). E o item "Nested Columns Too Narrow" do
         * `checklist.md` do Filament Blueprint, medido nesta ficha antes da correcao.
         *
         * Com 1 coluna no pai, cada Section ocupa a largura inteira e as entradas ficam a 50%.
         */
        return $schema
            ->columns(1)
            ->components([
                self::secaoDaConta(),
                Section::make('Vinculos')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('organizacoes')
                            ->label('Organizacoes')
                            ->badge()
                            ->state(fn (User $record): array => $record->tenants()->pluck('nome')->all())
                            ->placeholder('Nenhuma'),
                        /*
                     * `papeisEmQualquerContexto()`, nunca a relacao `roles` do spatie: com
                     * `permission.teams` ligada, ela filtra pelo team do REQUEST
                     * (`app/Models/User.php:papeisEmQualquerContexto:683`), e no /admin o contexto
                     * e o global — a ficha mostraria so os papeis globais e esconderia os de
                     * organizacao, com cara de "esta pessoa nao tem papel nenhum".
                     */
                        TextEntry::make('papeis')
                            ->label('Papeis')
                            ->badge()
                            ->state(fn (User $record): array => $record->papeisEmQualquerContexto()
                                ->pluck('name')
                                ->unique()
                                ->map(Papeis::rotulo(...))
                                ->values()
                                ->all())
                            ->placeholder('Nenhum'),
                    ]),
            ]);
    }
}

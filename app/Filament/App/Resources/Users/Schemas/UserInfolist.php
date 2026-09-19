<?php

namespace App\Filament\App\Resources\Users\Schemas;

use App\Filament\Concerns\FichaDeUsuario;
use Filament\Schemas\Schema;

/**
 * A ficha somente-leitura de uma conta no /app.
 *
 * So a secao compartilhada, e isso e a feature: a ficha do /admin acrescenta "Vinculos" com as
 * organizacoes da pessoa, e aqui essa secao NAO pode entrar — quem administra a Acme veria que
 * aquele usuario tambem e da Globex. O recorte de `UserResource::getEloquentQuery():169` garante
 * que so se veja gente DA organizacao corrente; ele nao garante que se possa ver onde mais ela
 * esta. Ver `App\Filament\Concerns\FichaDeUsuario`.
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
            ]);
    }
}

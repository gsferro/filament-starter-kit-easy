<?php

namespace App\Filament\App\Resources\Users\Schemas;

use App\Filament\Concerns\FichaDeUsuario;
use Filament\Schemas\Schema;

/**
 * A ficha somente-leitura de uma conta no /app.
 *
 * So a secao compartilhada, e isso e a feature: a ficha do /admin acrescenta "Vinculos" com as
 * organizacoes da pessoa, e aqui essa secao NAO pode entrar — quem administra a Acme veria que
 * aquele usuario tambem e da Globex. O recorte de `UserResource::getEloquentQuery():197` garante
 * que so se veja gente DA organizacao corrente; ele nao garante que se possa ver onde mais ela
 * esta. Ver `App\Filament\Concerns\FichaDeUsuario`.
 */
class UserInfolist
{
    use FichaDeUsuario;

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            self::secaoDaConta(),
        ]);
    }
}

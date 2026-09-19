<?php

namespace App\Filament\App\Resources\Users\Schemas;

use App\Filament\Concerns\CabecalhoDeUsuario;
use Filament\Schemas\Schema;

/**
 * O cabecalho rico das telas de registro de usuario do painel app.
 *
 * O NOME e o LUGAR desta classe nao sao escolha: o pacote a descobre por convencao sobre o MODEL
 * do resource — `{namespace do resource}\Schemas\{class_basename do model}Header` — em
 * `vendor/mortalkiller/filament-page-header/src/Concerns/HasPageHeader.php:getPageHeaderSchemaClass:65-66`.
 * `configure()` estatica e exigida pela mesma funcao (`:68`, `is_callable([$class, 'configure'])`).
 *
 * O conteudo mora em `CabecalhoDeUsuario` porque e identico nos dois paineis por definicao.
 */
class UserHeader
{
    use CabecalhoDeUsuario;

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            self::cabecalhoDeUsuario(),
        ]);
    }
}

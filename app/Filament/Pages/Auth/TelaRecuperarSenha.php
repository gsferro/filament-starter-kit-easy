<?php

namespace App\Filament\Pages\Auth;

use App\Filament\Forms\Components\CampoAntiRobo;
use Caresome\FilamentAuthDesigner\Pages\Auth\RequestPasswordReset;
use Filament\Schemas\Schema;

/**
 * O "Esqueceu a senha?" dos três painéis, com o desafio anti-robô quando ele está ligado.
 *
 * Existe por uma linha: o campo. O `form()` do Filament tem só o e-mail
 * (`vendor/filament/filament/src/Auth/Pages/PasswordReset/RequestPasswordReset.php:141-147`), e o
 * `request()` valida por `$this->form->getState()` antes de `Password::broker()->sendResetLink()`
 * (`:56-71`) — então a regra do campo roda antes de qualquer e-mail sair. É a tela que mais paga
 * o desafio: cada envio bem-sucedido dela manda um e-mail.
 *
 * Só a página do PEDIDO. A de redefinição (a que o link do e-mail abre) continua a do vendor: ela
 * exige um token assinado que já veio de um e-mail, e um robô não tem isso. Ver o `00-requisito.md`
 * da wiki `recaptcha-nas-telas-publicas`.
 *
 * Registrada nos três `PanelProvider` por `AuthPageConfig::usingPage()` na chave `password-reset`.
 */
class TelaRecuperarSenha extends RequestPasswordReset
{
    /** Regra do kit: página de auth redeclara o `$layout`. Ver `.ai/rules/auth.md`. */
    protected static string $layout = 'filament-auth-designer::components.layouts.auth';

    public function form(Schema $schema): Schema
    {
        return CampoAntiRobo::acrescentarA(parent::form($schema));
    }
}

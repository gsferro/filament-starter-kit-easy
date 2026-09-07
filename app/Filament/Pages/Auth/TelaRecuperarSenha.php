<?php

namespace App\Filament\Pages\Auth;

use App\Filament\Forms\Components\CampoAntiRobo;
use App\Support\ConfiguracaoDoLogin;
use Caresome\FilamentAuthDesigner\Pages\Auth\RequestPasswordReset;
use Filament\Schemas\Schema;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;

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
 * Com a página única de login ligada, ela responde em `/esqueci-minha-senha` pela subclasse
 * `TelaRecuperarSenhaUnificada`, e as rotas dos painéis levam para lá.
 */
class TelaRecuperarSenha extends RequestPasswordReset
{
    /** Regra do kit: página de auth redeclara o `$layout`. Ver `.ai/rules/auth.md`. */
    protected static string $layout = 'filament-auth-designer::components.layouts.auth';

    /**
     * Mesma guarda de `TelaLogin::mount()`: com a chave ligada, a rota do painel devolve para a
     * página única. `TelaRecuperarSenhaUnificada` responde `ehAPaginaUnica()` e não entra no laço
     * — a rota corrente não serve, é nula em `Livewire::test()` (`.ai/rules/auth.md`).
     */
    public function mount(): void
    {
        if (ConfiguracaoDoLogin::unificado() && ! $this->ehAPaginaUnica()) {
            throw new HttpResponseException(new RedirectResponse(route('esqueci-minha-senha')));
        }

        parent::mount();
    }

    /** A página única (`/esqueci-minha-senha`) sobrescreve para `true`; as dos painéis são `false`. */
    protected function ehAPaginaUnica(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return CampoAntiRobo::acrescentarA(parent::form($schema));
    }
}

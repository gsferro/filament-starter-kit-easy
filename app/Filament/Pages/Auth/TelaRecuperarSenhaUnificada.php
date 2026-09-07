<?php

namespace App\Filament\Pages\Auth;

use App\Support\ConfiguracaoDoLogin;
use Filament\Facades\Filament;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;

/**
 * O "Esqueceu a senha?" em `/esqueci-minha-senha`, fora dos painéis, quando o login é unificado.
 *
 * Mesmo molde de `TelaLoginUnificada` e `CadastroUnificado`. O formulário, o anti-robô e o envio
 * continuam sendo os de `TelaRecuperarSenha`; esta classe só decide onde a tela responde.
 *
 * **O e-mail continua apontando para o painel.** `RequestPasswordReset::sendResetLink()` monta a
 * URL com `Filament::getResetPasswordUrl()`, que resolve pelo painel corrente — aqui, o `app`,
 * imposto pelo `panel:app` da rota. Quem clica no link do e-mail cai em
 * `/app/password-reset/reset?token=…`, que exige token assinado e funciona. Ver ADR-02 de
 * `wikis/specs/feat/login-unificado-telas-externas/`.
 */
class TelaRecuperarSenhaUnificada extends TelaRecuperarSenha
{
    /** Redeclarado de propósito — `.ai/rules/auth.md`: sem isto o layout de auth vaza para toda página. */
    protected static string $layout = 'filament-auth-designer::components.layouts.auth';

    public function mount(): void
    {
        if (! ConfiguracaoDoLogin::unificado()) {
            throw new HttpResponseException(new RedirectResponse(
                Filament::getPanel('app')->getRequestPasswordResetUrl() ?? url('/'),
            ));
        }

        parent::mount();
    }

    protected function ehAPaginaUnica(): bool
    {
        return true;
    }
}

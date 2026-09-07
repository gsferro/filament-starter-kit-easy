<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Support\ConfiguracaoDoLogin;
use App\Support\DestinoAposLogin;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;

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

        /*
         * Quem já entrou vai para o destino DELA. O `mount()` do vendor faz
         * `redirect()->intended(Filament::getUrl())` — o /app emprestado pelo `panel:app` —, onde
         * um `admin` toma 403. Mesmo molde de `TelaLoginUnificada` e `CadastroUnificado`.
         */
        if (($user = Filament::auth()->user()) instanceof User) {
            throw new HttpResponseException(new RedirectResponse(DestinoAposLogin::urlPara($user)));
        }

        parent::mount();
    }

    /**
     * Antes de pedir o link, põe o painel corrente no primeiro painel que a CONTA acessa.
     *
     * Sem isto a recuperação fica quebrada em silêncio para metade das pessoas. O `request()` do
     * Filament só envia o e-mail se `$user->canAccessPanel(Filament::getCurrentOrDefaultPanel())`
     * (`vendor/filament/filament/src/Auth/Pages/PasswordReset/RequestPasswordReset.php:71-77`) — e
     * aqui o painel corrente é o `app`, emprestado pelo `panel:app` da rota. Quem só acessa o
     * `/admin` recebia a mesma mensagem de "enviamos, se a conta existir" e **nenhum e-mail**.
     * Medido: acontece igual na tela do painel quando o painel corrente é o `app`.
     *
     * É a mesma decisão de `TelaLoginUnificada::isUserAllowedToAccessPanel()` (ADR-07 de
     * `login-unificado`): fora dos painéis, a pergunta é "acessa **algum** painel", não "acessa o
     * corrente". De brinde, o link do e-mail passa a abrir no painel da própria pessoa.
     *
     * O usuário é resolvido pelo MESMO caminho do vendor (`PasswordBroker::getUser()`), então não
     * há uma segunda regra de credencial a manter — e quem não existe segue sem efeito nenhum.
     */
    public function request(): void
    {
        $painel = $this->painelDaConta();

        if ($painel instanceof Panel) {
            Filament::setCurrentPanel($painel);
        }

        parent::request();
    }

    private function painelDaConta(): ?Panel
    {
        // `collect()` porque `getRawState()` devolve `array|Arrayable`.
        $email = collect($this->form->getRawState())->get('email');

        if (! is_string($email) || blank($email)) {
            return null;
        }

        $conta = Password::broker(Filament::getAuthPasswordBroker())->getUser(['email' => $email]);

        if (! $conta instanceof User) {
            return null;
        }

        return DestinoAposLogin::paineisDe($conta)[0] ?? null;
    }

    protected function ehAPaginaUnica(): bool
    {
        return true;
    }
}

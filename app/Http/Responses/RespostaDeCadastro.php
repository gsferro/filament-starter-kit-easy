<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Models\User;
use App\Support\ConfiguracaoDoLogin;
use App\Support\DestinoAposLogin;
use App\Support\Paineis;
use Filament\Auth\Http\Responses\RegistrationResponse;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * O destino de todo cadastro que entra — convite e registro aberto, `/app/register` e `/cadastro`.
 *
 * `Register::register()` termina em `app(RegistrationResponse::class)` depois de autenticar a
 * conta nova (`vendor/filament/filament/src/Auth/Pages/Register.php:register():70-112`), e a
 * resposta do Filament é `redirect()->intended(Filament::getUrl())` — sem olhar quem é a conta.
 * A URL pretendida entra na sessão sozinha: o middleware do painel a grava quando um visitante
 * tenta abrir `/admin` e é mandado ao login, e ela sobrevive ao `session()->regenerate()` do
 * cadastro. Resultado medido em instalação real: quem abriu `/admin`, foi ao login e só então
 * clicou no link do convite terminava o cadastro em **403**.
 *
 * O kit já resolvia isso para o LOGIN (`RespostaDeLogin` + `DestinoAposLogin::urlPara()`, ADR-06
 * de `wikis/specs/feat/login-unificado/`); o cadastro tinha ficado de fora. Esta classe fecha a
 * assimetria pelo container, e não sobrescrevendo `register()`, porque o bind vale para os dois
 * pontos de entrada, os dois modos e qualquer página de registro futura. Vinculada no
 * `KitServiceProvider`. Ver `wikis/specs/fix/destino-apos-cadastro/`.
 *
 * O cadastro **pendente de aprovação** nunca chega aqui: `RegistroPorConvite::register()` encerra
 * a sessão e redireciona antes, devolvendo `null`.
 */
final class RespostaDeCadastro extends RegistrationResponse
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return parent::toResponse($request);
        }

        // Ligada, a decisão é a MESMA do login — inclusive o carimbo de painel no log de acesso.
        if (ConfiguracaoDoLogin::unificado()) {
            return redirect()->to(DestinoAposLogin::urlPara($user));
        }

        /*
         * Desligada não existe tela de escolha (`EscolhaDePainel::mount()` devolve ao painel
         * default). A pretendida inacessível sai; a acessível vence, e o Filament a segue.
         */
        DestinoAposLogin::descartarPretendidaInacessivel($user);

        $corrente = Paineis::correnteOuPadrao();

        if (session()->has('url.intended') || $user->canAccessPanel($corrente)) {
            return parent::toResponse($request);
        }

        /*
         * O painel corrente também pode ser inacessível: o link do convite é sempre o registro do
         * `/app`, qualquer que seja o papel, e um convite `infra` terminava em 403 (issue #191).
         * Ver `wikis/specs/fix/convite-painel-do-papel/`.
         */
        $painel = DestinoAposLogin::painelSemEscolha($user);

        if ($painel instanceof Panel) {
            Log::channel('autenticacao')->info(
                "[RespostaDeCadastro@toResponse] Painel corrente inacessivel — destino no painel acessivel | user: {$user->getKey()} - painel: {$painel->getId()}",
                ['user_id' => $user->getKey(), 'corrente' => $corrente->getId(), 'painel' => $painel->getId()],
            );

            return redirect()->to(Paineis::url($painel));
        }

        Log::channel('autenticacao')->warning(
            "[RespostaDeCadastro@toResponse] Cadastro sem nenhum painel acessivel — sessao encerrada | user: {$user->getKey()}",
            ['user_id' => $user->getKey(), 'corrente' => $corrente->getId(), 'motivo' => 'sem_painel_acessivel'],
        );

        Filament::auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to($corrente->getLoginUrl() ?? url('/'));
    }
}

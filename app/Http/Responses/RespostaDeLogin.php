<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Models\User;
use App\Support\ConfiguracaoDoLogin;
use App\Support\DestinoAposLogin;
use Filament\Auth\Http\Responses\LoginResponse;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * O destino de todo login por senha nos três painéis (`Login::authenticate()` devolve
 * `app(LoginResponse::class)`). Vinculada no `KitServiceProvider`.
 *
 * Com a página única desligada, é exatamente a resposta do Filament:
 * `redirect()->intended(Filament::getUrl())`. Ligada, a decisão é de `DestinoAposLogin` — um
 * painel acessível → entra nele; mais de um → escolhe. Ver `wikis/specs/feat/login-unificado/`.
 */
final class RespostaDeLogin extends LoginResponse
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = Filament::auth()->user();

        if (! ConfiguracaoDoLogin::unificado() || ! $user instanceof User) {
            return parent::toResponse($request);
        }

        return redirect()->to(DestinoAposLogin::urlPara($user));
    }
}

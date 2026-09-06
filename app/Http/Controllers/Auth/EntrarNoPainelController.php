<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\DestinoAposLogin;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;

/**
 * O clique num cartão da escolha de painel (`/login/painel/{painel}`).
 *
 * Existe por um motivo só: carimbar no log de acesso o painel que a pessoa ESCOLHEU. O login pela
 * página única nasce com painel nulo no `authentication_log` (não há painel de origem), e é aqui
 * — ou em `DestinoAposLogin::urlPara()`, quando há um só painel — que o valor certo entra. Sem
 * isto, o breakdown de acessos por painel dos insights contaria `app` para todo mundo.
 *
 * Painel que a pessoa não acessa volta à escolha, sem carimbo. Ver `wikis/specs/feat/login-unificado/`.
 */
final class EntrarNoPainelController extends Controller
{
    public function __invoke(string $painel): RedirectResponse
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        return redirect()->to(DestinoAposLogin::entrarEm($user, $painel));
    }
}

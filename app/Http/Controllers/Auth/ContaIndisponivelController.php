<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * A página de aviso de quem tentou entrar com uma conta desativada ou excluída.
 *
 * Ela não existe "solta": só renderiza com um aviso deixado na sessão pelo request anterior — o
 * interceptor de `TelaLogin::authenticate()` ou a recusa de `LoginSocialController`. Visitada
 * sem aviso, manda para a raiz. É o mesmo mecanismo de flash que `Notification::send()` fora do
 * Livewire já usa no kit: um request grava, o seguinte lê.
 *
 * O que ela NÃO decide é se a pessoa é dona da conta — quem provou isso foi quem gravou o aviso
 * (senha certa, ou e-mail verificado no provedor). Ver ADR-02 e ADR-03 da wiki
 * `status-e-exclusao-logica-de-usuario`.
 */
final class ContaIndisponivelController extends Controller
{
    public const CHAVE_DA_SESSAO = 'conta_indisponivel';

    /**
     * Grava o aviso na sessão e devolve a URL da página. Quem chama decide como redirecionar
     * (`$this->redirect()` no Livewire, `redirect()->to()` num controller).
     */
    public static function redirecionar(User $user, string $voltarPara): string
    {
        session()->flash(self::CHAVE_DA_SESSAO, [
            'motivo'      => $user->motivoDeIndisponibilidade(),
            'excluida_em' => $user->deleted_at?->toIso8601String(),
            'voltar_para' => $voltarPara,
        ]);

        return route('auth.conta-indisponivel');
    }

    public function __invoke(Request $request): Response|RedirectResponse
    {
        $aviso = $request->session()->get(self::CHAVE_DA_SESSAO);

        if (! is_array($aviso) || ! is_string($aviso['motivo'] ?? null)) {
            return redirect()->to('/');
        }

        $excluidaEm = is_string($aviso['excluida_em'] ?? null) ? Carbon::parse($aviso['excluida_em']) : null;

        return response()->view('auth.conta-indisponivel', [
            'motivo'     => $aviso['motivo'],
            'excluidaEm' => $excluidaEm,
            'voltarPara' => is_string($aviso['voltar_para'] ?? null) ? $aviso['voltar_para'] : url('/'),
        ], 403);
    }
}

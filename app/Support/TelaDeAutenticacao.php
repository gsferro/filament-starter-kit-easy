<?php

declare(strict_types=1);

namespace App\Support;

use Caresome\FilamentAuthDesigner\Concerns\HasAuthDesignerLayout;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * "Esta rota é uma tela de autenticação?" — a pergunta que o dock de atalhos e o chat do
 * Assistente fazem antes de aparecer (wiki `pilha-de-botoes-flutuantes`, Adendos 4 a 6).
 *
 * Numa tela de autenticação nenhum dos dois é renderizado: o componente não vai no HTML. Na tela
 * de bloqueio a sessão está autenticada, então o dock entregaria os favoritos e o chat os títulos
 * das conversas a quem estiver diante da tela — esconder por CSS deixaria tudo no HTML.
 *
 * Três critérios, porque nenhum cobre os outros:
 *
 * - a classe da rota usa o layout do Auth Designer (`HasAuthDesignerLayout`, direto ou herdado):
 *   login dos painéis e unificado, recuperar senha, cadastro unificado, registro por convite,
 *   dois fatores;
 * - a rota é a da tela de bloqueio (`lockscreen.*`): ela resolve o `LockerScreen` do pacote, que
 *   não usa o layout; o `TelaBloqueio` do kit só entra por bind no container (AppServiceProvider);
 * - a rota é a escolha de painel do login unificado (`login.painel`), que não usa o layout e é
 *   alcançada por quem já está autenticado.
 */
final class TelaDeAutenticacao
{
    /** Rotas de autenticação que o layout do Auth Designer não alcança. */
    private const ROTAS = ['lockscreen.*', 'login.painel'];

    /** A rota de nome `$nome` é tela de autenticação? Nulo (sem rota nomeada) não é. */
    public static function rota(?string $nome): bool
    {
        if (blank($nome)) {
            return false;
        }

        if (Str::is(self::ROTAS, $nome)) {
            return true;
        }

        $classe = Route::getRoutes()->getByName($nome)?->getControllerClass();

        return is_string($classe)
            && class_exists($classe)
            && in_array(HasAuthDesignerLayout::class, class_uses_recursive($classe), true);
    }

    /** A rota do request corrente é tela de autenticação? */
    public static function corrente(): bool
    {
        return self::rota(request()->route()?->getName());
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use lockscreen\FilamentLockscreen\Http\Middleware\Locker;

/**
 * O bloqueio de sessão do pacote, guardando a página que a trava interceptou.
 *
 * ## O que esta classe existe para consertar
 *
 * O `Locker` do `marjose123/filament-lockscreen` trava a sessão em dois ramos e nenhum guarda a
 * página certa para o desbloqueio, que termina em `redirect()->intended()` sem default
 * (`vendor/marjose123/filament-lockscreen/src/Http/Livewire/LockerScreen.php:intended:115`):
 *
 * - por inatividade, não grava `url.intended` nenhuma
 *   (`vendor/marjose123/filament-lockscreen/src/Http/Middleware/Locker.php:session_last_activity:48`)
 *   — o desbloqueio caía em `/`, a página pública de boas-vindas;
 * - com a sessão já travada, grava `url()->previous()`
 *   (`vendor/marjose123/filament-lockscreen/src/Http/Middleware/Locker.php:setIntendedUrl:32`), que
 *   prefere o `Referer` e, sem ele, é a última página servida — a própria tela de bloqueio.
 *
 * A régua aqui é a do `Redirector::guest()` do Laravel, a do *intended* do login: a URL do GET
 * interceptado, com a query, desde que não espere JSON. Chamada Livewire nem chega a travar — o
 * pacote a passa adiante.
 *
 * ## Por que "o `$next` não foi chamado" decide
 *
 * Os dois únicos caminhos do `parent::handle()` que não passam a requisição adiante são os dois
 * redirects para a tela de bloqueio. Detectar por aí independe do path configurado em
 * `Lockscreen::setUrl()` e do painel — e grava DEPOIS do pacote, que é o que sobrescreve o
 * `previous()` do ramo da sessão já travada.
 *
 * Quem coloca esta classe no lugar da do pacote é o bind em `AppServiceProvider::register()`: o
 * plugin registra `Locker::class` no `authMiddleware` dos painéis e o pipeline o resolve pelo
 * container. Ver a wiki `desbloqueio-volta-a-pagina`.
 */
class BloquearSessaoComRetorno extends Locker
{
    public function handle(Request $request, Closure $next): mixed
    {
        $seguiu = false;

        $resposta = parent::handle($request, function (Request $request) use ($next, &$seguiu): mixed {
            $seguiu = true;

            return $next($request);
        });

        if (! $seguiu && $request->isMethod('GET') && ! $request->expectsJson()) {
            redirect()->setIntendedUrl($request->fullUrl());

            Log::channel('autenticacao')->info(
                '[BloquearSessaoComRetorno@handle] Página de retorno guardada ao travar a sessão | painel: '.(filament()->getCurrentPanel()?->getId() ?? 'nenhum').' - caminho: /'.$request->path(),
                [
                    'painel'  => filament()->getCurrentPanel()?->getId(),
                    'caminho' => '/'.$request->path(),
                    'user_id' => $request->user()?->getAuthIdentifier(),
                ],
            );
        }

        return $resposta;
    }
}

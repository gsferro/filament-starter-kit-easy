<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Filament\Admin\Resources\Onboarding\CondicaoResource;
use App\Filament\Admin\Resources\Onboarding\JornadaResource;
use App\Filament\Pages\PrimeirosPassos;
use App\Support\TutorialGuiado;
use Closure;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * As telas do onboarding respondem 404 com o tutorial desligado — mesmo para quem tem a
 * permissão (RQ-16 da wiki `onboarding-guiado`): a autoria (jornadas e condições, no `/admin`) e
 * a página "Primeiros passos" (nos três painéis).
 *
 * Middleware de painel, e não o trait nas Pages que `ExigeAgentesDeIaLigados` usa: as telas são
 * do vendor, e o trait exigiria subclassificar 2 Resources e 7 Pages só para levar estas linhas
 * (D9 do plano). Registrado com `isPersistent: true` em `authMiddleware()`, então o Filament o
 * reaplica nos updates Livewire — a tela de autoria já aberta quando a instalação desliga o
 * tutorial recusa a próxima ação. O interruptor vem antes da permissão, como nos agentes de IA.
 *
 * Com o tutorial ligado, quem decide a página "Primeiros passos" é a permissão dela, no
 * `canAccess()` de `App\Filament\Pages\PrimeirosPassos` (403).
 */
class ExigeTutorialGuiadoLigado
{
    public function handle(Request $request, Closure $next): Response
    {
        // O interruptor primeiro: ligado (o caso que nunca recusa), nada de rota nem de resources.
        if (TutorialGuiado::habilitado()) {
            return $next($request);
        }

        $rota   = (string) $request->route()?->getName();
        $painel = Filament::getCurrentPanel();

        if ($painel === null || $rota === '') {
            return $next($request);
        }

        $paginaDeProgresso = $rota === PrimeirosPassos::getRouteName($painel);

        if (! $paginaDeProgresso && ! self::rotaDeAutoria($rota, $painel)) {
            return $next($request);
        }

        Log::channel('onboarding')->warning(
            '[ExigeTutorialGuiadoLigado@handle] Tela de onboarding recusada com o tutorial desligado | rota: '.$rota,
            ['user_id' => auth()->id(), 'rota' => $rota, 'motivo' => 'tutorial_desligado'],
        );

        abort(404);
    }

    /** As rotas dos dois Resources de autoria — só existem no `/admin`. */
    private static function rotaDeAutoria(string $rota, Panel $painel): bool
    {
        foreach ([JornadaResource::class, CondicaoResource::class] as $resource) {
            if (! in_array($resource, $painel->getResources(), true)) {
                continue;
            }

            if (str_starts_with($rota, $resource::getRouteBaseName($painel).'.')) {
                return true;
            }
        }

        return false;
    }
}

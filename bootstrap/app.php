<?php

use App\Http\Middleware\RaizDeUrlSemPublic;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * `append` ao stack GLOBAL, e a posicao e requisito: ele precisa rodar DEPOIS do
         * TrustProxies, senao leria host e porta sem os cabecalhos X-Forwarded-* e congelaria
         * uma raiz que o navegador nao alcanca. Ver o docblock da classe.
         */
        $middleware->append(RaizDeUrlSemPublic::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

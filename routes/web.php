<?php

use App\Filament\Pages\BoasVindas;
use App\Http\Controllers\Auth\LoginComGoogleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Boas-vindas ao kit, no lugar da welcome padrão do Laravel
|--------------------------------------------------------------------------
| Rota PÚBLICA e anônima, como a welcome que ela substitui. Nada de segredo na
| tela — a lista do que não entra está no ADR-04 da wiki `pagina-boas-vindas`, e
| `tests/Kit/BoasVindasTest.php` assere a ausência de cada item.
|
| O `panel:app` NÃO é decoração: é o alias de `Filament\Http\Middleware\SetUpPanel`
| e é ele que boota o painel, o que traz a folha do Filament, a paleta do projeto
| e o script de tema claro/escuro. Medido: `@filamentStyles` sozinho não traz a
| folha e ignora `KIT_COR_PRIMARIA`. O porquê completo está no docblock de
| `App\Filament\Pages\BoasVindas` e no ADR-01 da wiki.
*/

Route::get('/', BoasVindas::class)
    ->middleware('panel:app')
    ->name('boas-vindas');

/*
|--------------------------------------------------------------------------
| Login social com Google (laravel/socialite)
|--------------------------------------------------------------------------
| A segunda e a terceira rotas PÚBLICAS do kit — antes desta feature havia
| só a de boas-vindas acima.
|
| O caminho do callback é literal do requisito e é o MESMO valor que está em
| `config/services.php` → `services.google.redirect`. Cadastre-o, absoluto,
| como URI de redirecionamento autorizada no console do Google.
|
| As rotas são registradas SEMPRE, e quem as tira do ar é o `abort_unless`
| do controller. Registrar dentro de um `if` faria `route('auth.google.*')`
| deixar de existir — estourando `RouteNotFoundException` em `route:list` e
| em qualquer `route:cache` feito com o .env de outro momento — e faria o
| comportamento depender da ordem entre carregar config e carregar rotas.
| A barreira fica no controller de propósito: um lugar, duas rotas. ADR-03.
|
| `throttle:10,1`: superfície pública que dispara chamada HTTP externa. Dez
| por minuto por IP é folgado para uma pessoa e apertado para um script.
*/

Route::middleware('throttle:10,1')
    ->prefix('auth/google')
    ->name('auth.google.')
    ->group(function (): void {
        Route::get('redirect', [LoginComGoogleController::class, 'redirecionar'])->name('redirect');
        Route::get('callback', [LoginComGoogleController::class, 'retorno'])->name('callback');
    });

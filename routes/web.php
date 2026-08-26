<?php

use App\Filament\Pages\BoasVindas;
use App\Http\Controllers\Auth\LoginSocialController;
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
| Login social (laravel/socialite) — quatro provedores, duas rotas
|--------------------------------------------------------------------------
| As rotas PÚBLICAS do kit, além da de boas-vindas acima.
|
| `{provedor}` é tipado como `App\Support\ProvedorSocial` no controller, e é isso
| que faz o **implicit enum binding** do Laravel devolver 404 automático para
| qualquer segmento que não seja caso do enum. A lista branca é o enum — não é
| código que alguém escreve e mantém em sincronia com ele. É por isso que o
| ADR-02 desta wiki reabriu a decisão do ADR-10 da wiki do Google, que havia
| recusado a rota genérica justamente pelo custo de validar o parâmetro.
|
| As URIs resultantes são LITERAIS e não mudaram: /auth/google/callback continua
| /auth/google/callback. Os irmãos novos são /auth/github/*,
| /auth/linkedin-openid/* e /auth/x/*. Cadastre cada um, absoluto, como URI de
| redirecionamento autorizada no console do provedor correspondente — os READMEs
| dizem onde, provedor por provedor. O caminho vive em `config/services.php` e é
| relativo de propósito, para acompanhar o APP_URL de cada ambiente.
|
| As rotas são registradas SEMPRE, e quem tira um provedor do ar é o
| `abort_unless` do controller, por provedor. Registrar dentro de um `if` faria
| `route('auth.social.*')` deixar de existir — estourando `RouteNotFoundException`
| em `route:list` e em qualquer `route:cache` feito com o .env de outro momento —
| e faria o comportamento depender da ordem entre carregar config e carregar
| rotas. A barreira fica no controller de propósito: um lugar, duas rotas, quatro
| provedores. ADR-03 da wiki login-social-google.
|
| `throttle:10,1`: superfície pública que dispara chamada HTTP externa. Dez por
| minuto por IP é folgado para uma pessoa e apertado para um script. O limite é
| do GRUPO, então ele soma os quatro provedores — quem alterna quatro provedores
| em um minuto é script, que é exatamente quem o limite existe para conter.
*/

Route::middleware('throttle:10,1')
    ->prefix('auth/{provedor}')
    ->name('auth.social.')
    ->group(function (): void {
        Route::get('redirect', [LoginSocialController::class, 'redirecionar'])->name('redirect');
        Route::get('callback', [LoginSocialController::class, 'retorno'])->name('callback');
        // O link do e-mail de confirmação do vínculo (modo estrito). `signed` — URL temporária de
        // 30 minutos; inválida ou vencida responde 403. Wiki vinculo-de-provedor-social, ADR-03.
        Route::get('confirmar', [LoginSocialController::class, 'confirmarVinculo'])->middleware('signed')->name('confirmar');
    });

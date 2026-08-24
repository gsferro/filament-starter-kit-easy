<?php

use App\Filament\Pages\BoasVindas;
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

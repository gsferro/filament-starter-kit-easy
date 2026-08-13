<?php

namespace App\Filament\Pages\Auth;

use Caresome\FilamentAuthDesigner\Pages\Auth\Login;
use Illuminate\Contracts\Support\Htmlable;

/**
 * O login do painel /app, sem o link "Cadastre-se".
 *
 * O `Login` do Filament exibe o link sempre que o painel tem registro
 * (`vendor/filament/filament/src/Auth/Pages/Login.php:445-455`). Aqui o registro existe,
 * mas só serve a quem tem convite: o link levaria TODA visita a uma página que recusa —
 * affordance para um caminho que não leva a lugar nenhum, que `wikis/convencoes.md`
 * classifica como bug e não como detalhe.
 */
class TelaLogin extends Login
{
    /** Regra do kit: página de auth redeclara o `$layout`. Ver `.ai/rules/auth.md`. */
    protected static string $layout = 'filament-auth-designer::components.layouts.auth';

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }
}

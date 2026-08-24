<?php

namespace App\Filament\Pages\Auth;

use App\Support\RegistroAberto;
use Caresome\FilamentAuthDesigner\Pages\Auth\Login;
use Illuminate\Contracts\Support\Htmlable;

/**
 * O login do painel /app, com o link "Cadastre-se" **condicionado ao registro aberto**.
 *
 * O `Login` do Filament exibe o link sempre que o painel tem registro
 * (`vendor/filament/filament/src/Auth/Pages/Login.php:445-456`), e o /app sempre tem — a rota
 * de registro é a tela de aceite de convite. Enquanto o cadastro aberto está desligado (o
 * default), o link levaria TODA visita a uma página que recusa: affordance para um caminho que
 * não leva a lugar nenhum, que `wikis/convencoes.md` classifica como bug e não como detalhe.
 *
 * Com o cadastro aberto ligado o caminho passa a existir, e esconder o link viraria o defeito
 * espelhado — a porta aberta que ninguém acha. Daí a condição, e não uma das duas constantes.
 *
 * A leitura passa por `RegistroAberto`, o ponto único (ADR-02): esta tela não conhece o nome de
 * chave de config nenhuma.
 */
class TelaLogin extends Login
{
    /** Regra do kit: página de auth redeclara o `$layout`. Ver `.ai/rules/auth.md`. */
    protected static string $layout = 'filament-auth-designer::components.layouts.auth';

    public function getSubheading(): string|Htmlable|null
    {
        return RegistroAberto::habilitado() ? parent::getSubheading() : null;
    }
}

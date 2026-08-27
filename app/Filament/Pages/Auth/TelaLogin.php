<?php

namespace App\Filament\Pages\Auth;

use App\Filament\Forms\Components\CampoAntiRobo;
use App\Support\RegistroAberto;
use Caresome\FilamentAuthDesigner\Pages\Auth\Login;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

/**
 * O login dos TRÊS painéis, com o link "Cadastre-se" **condicionado ao registro aberto** e o
 * desafio anti-robô quando ele está ligado.
 *
 * Até a proteção anti-robô, só o /app usava esta classe; /admin e /infra ficavam com a `Login` do
 * Auth Designer. Passaram para cá porque o campo precisa entrar no `form()`, e três páginas
 * idênticas em três painéis é o defeito histórico do kit nessa área (configurar um e esquecer os
 * outros dois). Para os painéis sem registro nada muda: `parent::getSubheading()` já devolve `null`
 * quando o painel não tem registro (`vendor/filament/filament/src/Auth/Pages/Login.php:445-456`),
 * e é isso que `RegistroAberto::habilitado()` decide para o /app.
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

    /**
     * Os três campos do Filament mais o desafio anti-robô — que decide sozinho se aparece.
     *
     * Sem `if`: o campo é `->visible()` pela configuração, avaliada no render e na validação, e
     * oculto ele não é renderizado nem validado. Ver o docblock de `CampoAntiRobo`.
     */
    public function form(Schema $schema): Schema
    {
        return CampoAntiRobo::acrescentarA(parent::form($schema));
    }
}

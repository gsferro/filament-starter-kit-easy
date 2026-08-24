<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

/**
 * A permissão `View:{Page}` passa a DECIDIR o acesso à Page.
 *
 * O Shield já **gerava** essa permissão para toda Page de painel (`tabs.pages` ligado), o
 * `PapeisSeeder` já a **entregava** ao papel do painel, e a tela de papéis já a mostrava como
 * checkbox. O que faltava era alguém **consultá-la**: o default do Filament é permissivo, e o
 * próprio vendor diz isso em comentário —
 * `vendor/filament/filament/src/Pages/Concerns/CanAuthorizeAccess.php:17-24` retorna `true` com a
 * nota *"Security: Custom pages default to allowing access for all authenticated panel users."*
 * Desmarcar o checkbox não mudava nada.
 *
 * ## Por que não `use HasPageShield;` direto na Page
 *
 * Porque **método definido na classe vence método vindo de trait**, sem erro, sem aviso e sem
 * deprecation. Três das cinco Pages do kit já têm `canAccess()` com regra local legítima (a flag
 * `kit.hub` nos dois hubs opcionais, o contexto de organização em `ConvitesRecebidos`), e nelas a
 * linha `use HasPageShield;` seria **no-op silencioso**: o diff pareceria correto e a permissão
 * continuaria sem ser consultada.
 *
 * O alias resolve: `canAccess()` daqui publica `permissão && regra local`, e a regra local vira o
 * hook `regraLocalDeAcesso()`. Page sem regra local não sobrescreve nada.
 *
 * ## O que vem junto do vendor
 *
 * `HasPageShield` também define `shouldRegisterNavigation()` (`:14-17`) como
 * `static::canAccess() && parent::shouldRegisterNavigation()`. A resolução é tardia, então ele
 * consulta a NOSSA `canAccess()` — o item de menu some junto com o acesso, em vez de aparecer e dar
 * 403 no clique. Não é a redundância que `.ai/rules/filament.md` proíbe: a rule fala de escrever
 * `shouldRegisterNavigation()` **à mão** numa Page, o que continua desnecessário porque
 * `Page::registerNavigationItems()` já retorna cedo (`vendor/filament/filament/src/Pages/Page.php:133-135`).
 *
 * ## Fail-open herdado, e por que ele fica
 *
 * `HasPageShield::canAccess()` (`:24-26`) cai em `parent::canAccess()` — ou seja, LIBERA — quando a
 * chave não resolve ou quando não há usuário autenticado. A chave só não resolve se
 * `FilamentShield::getPages()` não contiver a classe, o que acontece quando o painel corrente não é
 * o da Page. Em request real isso não ocorre: o middleware `SetUpPanel` fixa o painel antes de
 * qualquer Page ser tocada. Em teste de componente ocorre, e é para isso que existem
 * `noPainelBootado()` e `noPainelDa()` no `tests/Pest.php`.
 *
 * Inverter para fail-closed exigiria reimplementar o corpo do vendor por dentro do alias — pior que
 * herdar o comportamento dele. Ver ADR-01 de
 * `wikis/specs/feat/permissoes-de-telas-e-acoes/permissoes-de-telas-e-acoes/`.
 */
trait ExigePermissaoDaTela
{
    use HasPageShield {
        canAccess as protected permitidaPelaPermissao;
    }

    public static function canAccess(): bool
    {
        return static::permitidaPelaPermissao() && static::regraLocalDeAcesso();
    }

    /**
     * A regra da PRÓPRIA tela, além da permissão — flag de config, contexto de organização.
     *
     * Sobrescreva ESTE método, nunca o `canAccess()`: a sobrescrita do `canAccess()` na classe
     * vence o deste trait em silêncio e desliga a permissão.
     */
    protected static function regraLocalDeAcesso(): bool
    {
        return true;
    }
}

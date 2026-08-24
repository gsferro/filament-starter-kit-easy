<?php

use Filament\Facades\Filament;

/**
 * A página de boas-vindas da rota `/`, com a multi-organização LIGADA.
 *
 * CT-04 de `wikis/specs/feat/pagina-boas-vindas/pagina-boas-vindas/04-casos-de-teste.md`.
 *
 * Aqui, e não em `tests/Kit`, porque é a única forma de exercer a partição "tenancy ligada":
 * `Tests\TenancyTestCase` fixa `permission.teams` em `createApplication()`, antes das migrations, e
 * o Pest não permite dois TestCases na mesma pasta (`.ai/rules/testes.md`).
 */

/**
 * CT-04 — com a multi-organização ligada, o cartão do negócio ainda leva a `/app`.
 *
 * A segunda asserção é a discriminante. Com `->tenant()` registrado, as rotas do painel viram
 * `/app/{tenant}/…` — mas `Panel::getUrl()` sem usuário autenticado cai no
 * `return url($this->getPath())` de
 * `vendor/filament/filament/src/Panel/Concerns/HasRoutes.php:196`, porque o ramo de domínio de
 * tenant exige tenant, o ramo da rota `home` exige `(! $hasTenancy) || $tenant`, e o ramo de
 * prefixo exige tenant também. Uma implementação que resolvesse o tenant mais cedo produziria
 * `/app/{slug}` — ou uma exceção, se não houvesse tenant para resolver.
 */
it('mantem o cartao do negocio apontando para a raiz do painel com a tenancy ligada', function (): void {
    expect(config('kit.tenancy.enabled'))->toBeTrue();

    $raiz = url(Filament::getPanel('app')->getPath());

    $this->get('/')
        ->assertOk()
        ->assertSee('href="'.$raiz.'"', escape: false)
        ->assertDontSee('href="'.$raiz.'/', escape: false);
});

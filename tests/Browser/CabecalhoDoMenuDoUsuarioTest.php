<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * CT-B01 — o cabeçalho de identidade aparece ao ABRIR o dropdown do usuário.
 *
 * Por que navegador, e só este cenário: o conteúdo do cabeçalho já vem no HTML do
 * servidor, e a suíte `Kit` prova nos três painéis que ele está lá, com nome, e-mail e
 * badge. O que o HTML NÃO prova é que ele fica visível: o painel do dropdown é
 * `x-show` do Alpine, e um erro de JavaScript em qualquer outro componente da topbar
 * deixa o cabeçalho presente no DOM e invisível para sempre.
 *
 * `assertVisible` e não `assertPresent`: presente é o estado com o dropdown FECHADO —
 * a asserção passaria sem o clique ter feito nada.
 *
 * A âncora é `[data-user-menu-header]` e não o nome do usuário, porque o nome também
 * aparece no `AccountWidget` do dashboard, na mesma página. Ver ADR-06 da feature.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

it('abre o dropdown do usuário e mostra o cabeçalho de identidade', function (): void {
    $user = usuarioDoKit('master_global', 'master@example.com');

    $this->actingAs($user);

    /*
     * DT-06 — paga a compilação dos componentes do painel num request pelo KERNEL, antes de
     * abrir o navegador. Compilar as ~590 views do kit custa dezenas de segundos, e o primeiro
     * cenário que renderiza um painel paga a conta inteira DENTRO do timeout de 45 s do
     * Playwright — falhando por um motivo que não é o dele.
     *
     * Medido: numa árvore recém-buildada, este arquivo estourou `Timeout 45000ms exceeded`; a
     * execução seguinte, sem mudar uma linha, passou. É o disfarce que
     * `.ai/rules/testes-browser.md` descreve — "tem o formato de teste instável" — e que custou
     * duas execuções completas para separar de um defeito real.
     *
     * O `get()` é descartado de propósito: o que interessa é o efeito colateral em disco, que o
     * servidor do navegador reusa.
     */
    $this->get('/admin');

    visit('/admin')
        /*
         * Fechado, o painel do dropdown existe no DOM mas está escondido. Fixar isto
         * antes do clique é o que impede o caso de virar uma asserção que passaria de
         * qualquer jeito.
         */
        ->assertPresent('[data-user-menu-header]')
        ->assertMissing('[data-user-menu-header]')
        ->click('.fi-user-menu-trigger')
        ->assertVisible('[data-user-menu-header]')
        ->assertSeeIn('[data-user-menu-header]', $user->name)
        ->assertSeeIn('[data-user-menu-header]', $user->email)
        ->assertNoJavaScriptErrors();
})->group('browser');

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

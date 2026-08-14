<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * O recorte de painel por papel, e o login, vistos pela tela.
 *
 * `tests/Kit/PaineisTest.php` já prova que `canAccessPanel()` decide certo e que o HTTP
 * responde 403. O que ele não prova, e é o que se prova aqui: que a negativa CHEGA à
 * tela como página legível, e não como tela branca ou erro de JS — e que o formulário de
 * login funciona de verdade, clicando.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * CT-B05 — cada papel entra no seu painel e é barrado no outro.
 */
it('recorta os paineis por papel na tela', function (string $papel, string $permitido, string $negado): void {
    $this->actingAs(usuarioDoKit($papel));

    visit($permitido)->assertNoJavaScriptErrors();

    // A página de 403 é tela também: se o barramento virar exceção não tratada ou layout
    // vazio, o usuário barrado fica sem saber que foi barrado.
    visit($negado)
        ->assertSee('403')
        ->assertNoJavaScriptErrors();
})->with([
    'admin'      => ['admin', '/admin', '/infra'],
    'infra'      => ['infra', '/infra', '/admin'],
    'panel_user' => ['panel_user', '/app', '/admin'],
]);

/**
 * CT-B06 — o único CT-B que entra pela porta da frente.
 *
 * Todos os outros usam `actingAs()`, que funciona porque o servidor do plugin é
 * in-process. Barato, mas cego para o formulário: uma quebra em `#form.email`, no
 * `wire:model` ou no botão passaria a suíte inteira sem uma falha. Este cenário é a
 * única coisa que a pega.
 */
it('faz login pela tela e entra no painel', function (): void {
    usuarioDoKit('master_global', 'login@example.com');

    visit('/app/login')
        ->assertSee('Faça login')
        ->fill('#form\\.email', 'login@example.com')
        ->fill('#form\\.password', 'password')
        ->press('Login')
        // A URL certa não basta: o redirect pode levar a um dashboard que não renderizou.
        // O par (conteúdo, path) é o que distingue "logou" de "trocou de URL".
        ->assertSee('Painel de Controle')
        ->assertPathIs('/app')
        ->assertNoJavaScriptErrors();

    // Vale porque o navegador compartilha o processo do teste: a sessão que o formulário
    // criou é a sessão que o PHPUnit inspeciona.
    $this->assertAuthenticated();
});

<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * CT-B01 da wiki `feat/login-unificado`: entrar pela página única, escolher o painel e chegar nele,
 * sem erro de JavaScript. É o único cenário de navegador da feature — o que só ele prova é o
 * encadeamento real de três páginas (login fora de painel → escolha → painel) com o formulário
 * Livewire e o layout do Auth Designer; cada decisão isolada está em `tests/Kit/LoginUnificadoTest.php`.
 *
 * Login pela TELA de propósito (o caminho real do usuário); os demais cenários do kit usam
 * `actingAs()`. Aquecimento pelo kernel antes do primeiro `visit()` (`.ai/rules/testes-browser.md`).
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    config()->set('kit.login.unificado', true);
});

it('CT-B01: quem tem dois painéis entra por /login, escolhe Administração e chega ao /admin', function (): void {
    usuarioDoKit('admin', 'dois@example.com')->assignRole('infra');

    $this->get('/login');

    $pagina = visit('/login')
        ->assertPresent('.fi-auth-layout')
        ->assertNoJavaScriptErrors()
        ->fill('#form\.email', 'dois@example.com')
        ->fill('#form\.password', 'password')
        ->press('Login');

    $pagina->assertPathIs('/login/painel')
        ->assertSee('Administração')
        ->assertSee('Infraestrutura')
        ->assertDontSee('Painel do negócio')
        ->assertNoJavaScriptErrors();

    $pagina->click('Administração')
        ->assertPathIs('/admin')
        ->assertNoJavaScriptErrors();
})->group('browser');

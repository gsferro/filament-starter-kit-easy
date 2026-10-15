<?php

use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

it('[CT-B01] chegar ao fim da tabela no modo rolagem carrega exatamente o próximo bloco', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    ligarCarregamento('rolagem', 25);
    $master = usuarioDoKit('master_global');
    User::factory()->count(74)->create();

    $this->actingAs($master);
    $this->get('/admin/users')->assertOk();

    $pagina = visit('/admin/users')->inLightMode();
    $pagina->assertPathIs('/admin/users')
        ->assertSee('Mostrando 25 de 75')
        ->assertAttribute('.fi-ta-infinite-scroll', 'data-mode', 'scroll')
        ->assertAttribute('.fi-ta-infinite-scroll', 'data-loaded', '25')
        ->assertScript("document.querySelectorAll('.fi-ta-row').length === 25");

    $pagina->script("document.querySelector('.fi-ta-infinite-scroll').scrollIntoView({block: 'end'})");

    $pagina->assertSee('Mostrando 50 de 75')
        ->assertAttribute('.fi-ta-infinite-scroll', 'data-loaded', '50')
        ->assertAttribute('.fi-ta-infinite-scroll', 'data-has-more', '1')
        ->assertScript("document.querySelectorAll('.fi-ta-row').length === 50")
        ->assertNoJavaScriptErrors();
});

<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * CT-B01 — a grade do hub aparece PINTADA.
 *
 * Por que navegador: é o risco central da entrega em forma executável. O
 * `harvirsidhu/filament-cards` não registra CSS nenhum, e a CSS pré-compilada do Filament 5
 * carrega quase só as classes `fi-*` — medido, 51 das 53 utilitárias que a blade do pacote emite
 * não existem lá. Sem `resources/css/filament/cards.css`, o HTML é **byte a byte o mesmo** e a
 * grade vira uma lista de links soltos: todo `assertSee`, todo `assertOk` e todo teste de
 * componente continuam verdes.
 *
 * Este cenário é honesto sobre a própria limitação: **não existe assertion barata para "está
 * pintado"**. `.ai/rules/testes-browser.md` já registra que para defeito de cor é screenshot e
 * olhar. O que dá para provar aqui em código é o que vem abaixo; o resto é o PNG.
 *
 * Ver `wikis/specs/main/hub-de-navegacao-em-cards/05-casos-de-teste-browser.md`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

it('desenha a grade de cartões do hub de infraestrutura', function (): void {
    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    visit('/infra/hub-de-infraestrutura')
        // O atributo é contrato do pacote, injetado em cada cartão quando `$searchable` é true.
        ->assertPresent('a[data-search-text]')
        // A classe que dá escopo ao `cards.css`: se ela sumir de `getPageClasses()`, o CSS
        // inteiro deixa de valer e a grade se desmancha — sem erro nenhum.
        ->assertPresent('.kit-cards-page')
        // Evidência visual para o roteiro "Desenhado × Implementado" do arquivo 05.
        ->screenshot(filename: 'hub-infraestrutura')
        ->assertNoJavaScriptErrors();
});

<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * A tela de papéis, no navegador — e só o que o navegador prova.
 *
 * Tudo o mais desta feature (rótulo, contagem, contador de permissões, `uuid` na rota, guard)
 * é teste de componente Livewire ou HTTP e vive em `tests/Kit/TelaDePapeisTest.php` e
 * `tests/Kit/UuidDoPapelTest.php`. Aqui ficam dois cenários, cada um afirmando sobre algo que
 * nenhuma outra camada alcança:
 *
 *   - CT-B01: trocar de painel no tab vertical é Alpine. O teste de componente renderiza os
 *     três painéis no HTML de uma vez; ele não sabe dizer qual está VISÍVEL. O `assertSee` do
 *     plugin confere `isVisible()`
 *     (`vendor/pestphp/pest-plugin-browser/src/Api/Concerns/MakesElementAssertions.php:53-58`),
 *     que é exatamente a diferença.
 *   - CT-B02: o slide-over é um modal montado por JavaScript. `callAction()` executa a ação
 *     sem provar que o painel lateral abre.
 *
 * Wiki: `wikis/specs/feat/perfis-e-permissoes/tela-de-perfis/05-casos-de-teste-browser.md`.
 *
 * O `beforeEach` NÃO arranja painel: o servidor do plugin roda in-process e o `visit()`
 * renderiza a barra lateral do painel em que o processo foi deixado. Cada cenário arranja o
 * seu, imediatamente antes de visitar (`.ai/rules/testes-browser.md`).
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * CT-B01 — o tab vertical troca de painel por clique.
 *
 * O oráculo é o CONTEÚDO de cada painel, não o rótulo do tab: o rótulo está no DOM nos três
 * casos, então afirmar sobre ele passaria com o tab quebrado. `Audit` só existe no /infra e
 * `Tenant` só no /admin — conferido em `Paineis::resources()`, e é isso que torna o par
 * discriminante.
 *
 * O `assertDontSee` do painel de origem é o que mata o mutante "tudo dentro de um tab só":
 * ali os dois textos estariam visíveis ao mesmo tempo.
 */
it('troca de painel no tab vertical de permissoes', function (): void {
    $this->actingAs(usuarioDoKit('master_global'));

    // Paga a compilação dos componentes Livewire do painel FORA do cronômetro do Playwright.
    // Sem isto, o primeiro cenário de um arquivo isolado estoura o teto de 45 s por um motivo
    // que não é o dele (`.ai/rules/testes-browser.md`).
    $this->get('/admin/shield/roles/create');

    visit('/admin/shield/roles/create')
        ->assertSee('Tenant')
        ->click('Painel /infra')
        ->assertSee('Audit')
        ->assertDontSee('Tenant')
        ->click('Painel /admin')
        ->assertSee('Tenant')
        ->assertNoJavaScriptErrors();
});

/**
 * CT-B02 — o slide-over abre e lista quem tem o papel.
 *
 * `?search=panel_user` deixa uma linha só na tabela, e é o que torna o clique determinístico:
 * "Ver usuários" aparece uma vez por linha, e sem o filtro o clique cairia numa linha
 * arbitrária. O parâmetro é o `#[Url(as: 'search')]` de
 * `vendor/filament/filament/src/Resources/Pages/ListRecords.php:48-49`.
 *
 * O oráculo é o nome e o e-mail da pessoa criada no arranjo — dado que não existe em nenhum
 * outro lugar desta tela, então só passa se o painel lateral abriu E carregou a lista.
 *
 * O `assertDontSee('Salvar')` é o que mata o mutante do `modalSubmitAction(false)` esquecido:
 * um submit ali gravaria estado vazio sobre o papel.
 */
it('abre o slide-over com os usuarios do papel', function (): void {
    $marina = usuario('marina@example.com');
    $marina->forceFill(['name' => 'Marina Tavares'])->save();
    $marina->assignRole('panel_user');

    $this->actingAs(usuarioDoKit('master_global', 'admin@example.com'));

    $this->get('/admin/shield/roles');

    visit('/admin/shield/roles?search=panel_user')
        ->assertSee('Painel App')
        ->click('Ver usuários')
        ->assertSee('Marina Tavares')
        ->assertSee('marina@example.com')
        ->assertDontSee('Salvar')
        ->assertNoJavaScriptErrors();
});

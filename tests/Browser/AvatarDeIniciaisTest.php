<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * CT-B02 de
 * `wikis/specs/feat/estudo-de-pacotes-rodada-2/estudo-de-pacotes-rodada-2/05-casos-de-teste-browser.md`.
 *
 * ## Por que navegador, e não teste de componente Livewire
 *
 * Duas asserções, e nenhuma das duas é sobre HTML.
 *
 * A primeira é sobre **rede**. CT-21 do `04` prova que a string `ui-avatars.com` não está na
 * página — e isso ficaria verde se outro ponto da página (um widget, uma folha de estilo, um
 * ícone, uma fonte) buscasse o domínio por um caminho que não é `<img src>`. Só o navegador prova
 * que **nada foi buscado**.
 *
 * A segunda é sobre **decodificação**. `App\Support\AvatarDeIniciais::get()` devolve
 * `data:image/svg+xml;base64,…`; um base64 ou um escape quebrado produz um `<img>` presente no
 * DOM, com `src` preenchido, que o navegador não consegue abrir — e o servidor não tem como
 * saber (mutante MB2).
 *
 * ## O que este cenário NÃO prova
 *
 * A **aparência** do avatar — cor, contraste, posição. Aparência não é requisito nesta entrega
 * (pergunta nº 7 do `04`), e para defeito de cor não há saída barata: é screenshot e olhar.
 *
 * E **não prova que o avatar é o da pessoa certa**: uma implementação que desenhasse sempre as
 * iniciais de quem está autenticado passa aqui inteira. Quem mata esse mutante (M62) é CT-45, no
 * `04`, que é teste de componente — duas pessoas sem foto na mesma listagem.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

it('[CT-B02] nao busca nada fora da aplicacao na tela de quem nao tem foto', function (): void {
    // O usuário do kit nasce sem foto de perfil — é a persona do cenário sem arranjo extra.
    $this->actingAs(usuarioDoKit('admin'));

    /*
     * DT-06 — paga a compilação dos componentes do painel num request pelo KERNEL, fora do
     * cronômetro do Playwright.
     */
    $this->get('/admin');

    $pagina = visit('/admin')
        // `assertPathIs` primeiro: é ela que espera a navegação.
        ->assertPathIs('/admin');

    /*
     * Oráculo de decodificação ANTES da leitura da rede, de propósito: `assertNoBrokenImages()`
     * chama `waitForLoadState('load')`
     * (`vendor/pestphp/pest-plugin-browser/src/Api/Concerns/MakesConsoleAssertions.php:36`), então
     * ele é também quem garante que a `performance` lida a seguir já tem os recursos da carga
     * inteira — e não um retrato de meio caminho, que satisfaria a asserção de ausência por
     * mundo vazio.
     *
     * `assertNoBrokenImages` e não `assertVisible`: `assertVisible` passa para qualquer elemento
     * com caixa não vazia, e um `<img>` com `src` inválido continua "visível".
     */
    $pagina->assertNoBrokenImages();

    $recursos = json_decode(
        (string) $pagina->script("JSON.stringify(performance.getEntriesByType('resource').map(r => r.name))"),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    $origem = (string) $pagina->script('location.origin');

    /*
     * Obrigatório, e é o gate do não-efeito em mundo vazio: sem ele, uma página que não carregou
     * recurso nenhum — ou um `script()` que devolvesse vazio — satisfaria a asserção de ausência
     * abaixo com a feature inteira removida.
     */
    expect($recursos)->not->toBeEmpty();

    /*
     * A comparação é com o host da PRÓPRIA página, e não com uma lista de domínios proibidos:
     * lista negra só pega o domínio que alguém lembrou de escrever, e o defeito que interessa é
     * "saiu requisição para fora", não "saiu para este endereço".
     */
    $deTerceiros = array_values(array_filter(
        $recursos,
        static fn (string $recurso): bool => ! str_starts_with($recurso, $origem),
    ));

    expect($deTerceiros)->toBe([], 'Recursos buscados fora de '.$origem.': '.implode(', ', $deTerceiros));

    $pagina->assertNoJavaScriptErrors();
})->group('browser');

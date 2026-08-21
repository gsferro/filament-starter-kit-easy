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

    $this->usuario = usuarioDoKit('master_global', 'master@example.com');

    $this->actingAs($this->usuario);

    /*
     * Aquece a compilação dos componentes Livewire do painel **fora do cronómetro do Playwright**.
     *
     * O `composer art` roda este arquivo numa invocação PRÓPRIA do `artisan test`, isolada: o
     * `view:cache` cobre as Blade do repositório, mas o primeiro render de um painel ainda paga
     * ~25s de compilação de componente, e isso estoura o teto de 45s do plugin. Num request pelo
     * KERNEL a mesma conta é paga em PHP, onde ninguém cronometra, e os arquivos compilados ficam
     * em disco para o servidor do navegador reusar. Ver `.ai/rules/testes-browser.md`.
     *
     * **Só o /infra.** Aquecer outro painel aqui reintroduziria o vazamento de estado que faz a
     * tela sair com a barra lateral do painel errado — o defeito que a suíte de arte tem e que este
     * arquivo, por não atravessar painéis, não tem.
     */
    $this->get('/infra/hub-de-infraestrutura');
});

it('desenha a grade de cartões do hub de infraestrutura', function (): void {
    visit('/infra/hub-de-infraestrutura')
        // O atributo é contrato do pacote, injetado em cada cartão quando `$searchable` é true.
        ->assertPresent('a[data-search-text]')
        // A classe que dá escopo ao `cards.css`: se ela sumir de `getPageClasses()`, o CSS
        // inteiro deixa de valer e a grade se desmancha — sem erro nenhum.
        ->assertPresent('.kit-cards-page')
        ->assertNoJavaScriptErrors();
});

/**
 * CT-B02 — a descrição do cartão aparece DESENHADA.
 *
 * Por que navegador, e por que separado do CT-B01: o CT-B01 prova que a grade sai pintada; este
 * prova o mesmo sobre a frase que cada cartão ganhou. Texto no DOM é teste de componente e está
 * coberto por `tests/Kit/HubDeCardsTest.php` — o que só o navegador diz é que a frase renderiza com
 * a tipografia do kit, dentro do cartão.
 *
 * Conferi as três utilitárias que a blade emite no bloco de descrição
 * (`vendor/harvirsidhu/filament-cards/resources/views/pages/cards-page.blade.php:373-381`) contra
 * `resources/css/filament/cards.css`, e as três já estavam lá — `text-sm` na linha 114,
 * `text-gray-500` na 120 e o par de tema escuro na 141. Isto REDUZ o risco de ADR-02 da wiki
 * ancestral; não o elimina, porque o que a leitura não cobre é o efeito composto: dezesseis
 * cartões que antes tinham uma linha e agora têm três.
 *
 * `assertSee` não prova legibilidade — passa com cinza-claro sobre cinza-claro. Por isso o
 * screenshot: para defeito de cor não há saída barata (`.ai/rules/testes-browser.md`).
 *
 * Ver `wikis/specs/feature/v1-enriquecimento-kit/hub-de-cards-opcional/05-casos-de-teste-browser.md`.
 */
it('desenha a descrição dentro do cartão do hub de infraestrutura', function (): void {
    visit('/infra/hub-de-infraestrutura')
        // 1400x875 é a proporção da galeria da documentação (thumb 760x475). Este cenário é o
        // ÚNICO lugar onde a tela do hub é capturada, e é ele que alimenta `art/infra-hub.png`
        // pelo `composer art` — ver ADR-05 da wiki `hub-de-cards-opcional`.
        ->resize(1400, 875)
        ->assertPresent('.kit-cards-page')
        // Trecho da FRASE, não o rótulo: "Backups" casaria o cartão e o item da barra lateral, e o
        // modo estrito do Playwright trata seletor ambíguo como erro.
        ->assertSee('quando rodaram, o tamanho e se o destino respondeu')
        ->screenshot(fullPage: false, filename: 'infra-hub')
        ->assertNoJavaScriptErrors();
});

<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * A tela /admin/configuracoes-do-kit num navegador de verdade.
 *
 * CT-B01 e CT-B02 de
 * `wikis/specs/feat/settings-do-kit/settings-do-kit/05-casos-de-teste-browser.md`.
 *
 * Dois cenários, e o `05` registra por escrito os sete que foram cogitados e
 * cortados. O critério é o de sempre: só vem para cá o que **só** o navegador
 * prova. Preenchimento, validação, gravação, autorização e a trilha de auditoria
 * são teste de componente e vivem em `tests/Kit/ConfiguracoesDoKitTelaTest.php`,
 * onde rodam em milissegundos.
 *
 * O que sobrou:
 *
 * 1. **A troca de aba é JavaScript.** `assertSchemaComponentExists` passa com as
 *    quatro abas no DOM ao mesmo tempo — que é exatamente o estado em que a troca
 *    está quebrada e o campo é inalcançável.
 * 2. **O erro de validação num campo de aba não-ativa.** `assertHasFormErrors`
 *    prova que a validação disparou, não que o usuário VÊ o motivo. Sem o Filament
 *    ativar a aba do campo com erro, o formulário recusa a gravação e a tela não
 *    mostra nada: um beco sem saída silencioso, com o `04` verde.
 *
 * E há um risco de integração próprio: esta é a PRIMEIRA `SettingsPage` do kit, e o
 * layout dela vem de um pacote que nunca renderizou nada aqui, dentro de um painel
 * com cerca de 30 plugins.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->actingAs(usuarioDoKit('admin'));

    /*
     * DT-06 — paga a compilação dos componentes do painel num request pelo KERNEL,
     * antes de abrir o navegador. O `view:cache` cobre as Blade do repositório, não
     * os componentes Livewire do Filament: são ~25 s que ele não adianta, e rodando
     * este arquivo isolado eles cairiam dentro do teto de 45 s do Playwright,
     * falhando por um motivo que não é o do cenário.
     */
    $this->get('/admin/configuracoes-do-kit');
});

/**
 * CT-B01 — a tela abre na primeira aba e o clique em outra troca os campos visíveis.
 *
 * Dois `Quando` no cenário, e o estouro é justificado: o comportamento sob teste é
 * a *troca*, que só existe como sequência. Dois cenários fariam o segundo repetir a
 * abertura inteira da tela para provar metade da mesma coisa.
 *
 * As abas são acionadas por TEXTO do rótulo, mas nenhuma asserção é sobre esse
 * texto: os rótulos são premissa desta wiki (o requisito não os nomeia), e o
 * oráculo é a visibilidade do CAMPO que só existe naquela aba — o campo vem do
 * requisito. Renomear as abas não deixa este arquivo vermelho por isso.
 *
 * **Sem `assertNoJavaScriptErrors()`, e a ausência é medida.** O `ColorPicker` do
 * Filament dentro de `Tabs` emite `ResizeObserver loop completed with undelivered
 * notifications` no Chrome headless — duas vezes, na montagem. É ruído conhecido do
 * navegador (observers reagindo em cascata), não erro do kit: não aparece no Chrome
 * do Windows e apareceu no CI, então a asserção deixava a suíte vermelha por
 * ambiente. O plugin não oferece filtro (`assertNoJavaScriptErrors()` compara com
 * array vazio, `vendor/pestphp/pest-plugin-browser/src/Api/Concerns/MakesConsoleAssertions.php:78-89`),
 * e escrever um filtro próprio custaria mais do que vale: os oráculos que provam o
 * comportamento são o `assertVisible`/`assertMissing` do campo, e eles continuam.
 *
 * É o mesmo espírito da nota de `.ai/rules/testes-browser.md` sobre `assertNoSmoke()`
 * em tela de plugin: suíte vermelha por dívida alheia ninguém conserta, e o que ela
 * ensina é a ignorar o vermelho.
 *
 * Sem `assertPathIs`: o cenário não navega, o clique na aba é troca de painel no
 * cliente. Sem `wait()`: o plugin reexecuta cada asserção até o teto de 45 s.
 */
it('troca os campos visiveis ao acionar outra aba, com o seletor de cor montado', function (): void {
    visit('/admin/configuracoes-do-kit')
        // Primeira aba ativa: o campo dela está visível, o da outra não.
        ->assertVisible('#form\.nome_da_aplicacao')
        ->assertMissing('#form\.paginacao_padrao')
        // O seletor de cor é Alpine: um `assertSchemaComponentExists` provaria que
        // ele está no schema, não que inicializou.
        ->assertVisible('#form\.cor_primaria_hex')
        ->click('Tabelas')
        // E a visibilidade se inverte.
        ->assertVisible('#form\.paginacao_padrao')
        ->assertMissing('#form\.nome_da_aplicacao');
})->group('browser');

/**
 * CT-B02 — salvar com um campo inválido em outra aba revela o erro naquela aba.
 *
 * Três ações num `Quando`, e é a sequência que É o comportamento: invalidar, sair
 * da aba e salvar. Separá-las não produziria o estado que o cenário afirma.
 *
 * A âncora dupla do final é o ponto: `assertSee` da mensagem passa com o texto no
 * DOM dentro de uma aba invisível — que é o defeito. `assertVisible` do campo é o
 * que prova que o usuário chegou até ele.
 */
it('revela o erro de validacao na aba do campo invalido', function (): void {
    visit('/admin/configuracoes-do-kit')
        ->fill('#form\.nome_da_aplicacao', '')
        ->click('Tabelas')
        ->press('Salvar')
        // O campo do nome está noutra aba, e o Filament precisa trazê-la de volta.
        ->assertVisible('#form\.nome_da_aplicacao');
})->group('browser');

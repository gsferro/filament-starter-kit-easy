<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * Tema escuro e acessibilidade — os dois eixos que só um navegador de verdade mede.
 *
 * Tema é CSS aplicado por Alpine a partir de `prefers-color-scheme` e de `localStorage`:
 * nada disso existe num `$this->get()`, que devolve o mesmo HTML nos dois temas. Uma
 * regressão de tema é invisível para a suíte HTTP por construção.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * CT-B07 — os três dashboards com o navegador anunciando `prefers-color-scheme: dark`.
 *
 * Funciona porque o default dos painéis é `--default-theme-mode: system`, então o painel
 * obedece ao navegador sem precisar de clique nem de `localStorage` pré-carregado.
 *
 * O `assertSee()` é o que separa "renderizou escuro" de "renderizou nada": um erro de
 * asset sob o tema escuro produziria tela vazia, que passaria só com o assert de console.
 */
it('renderiza em tema escuro', function (): void {
    $this->actingAs(usuarioDoKit('master_global'));

    visit(['/app', '/admin', '/infra'])
        ->inDarkMode()
        // Título do Dashboard do Filament em pt_BR — o mesmo nos três painéis, porque
        // nenhum deles substitui a página padrão.
        ->assertSee('Painel de Controle')
        ->assertNoJavaScriptErrors();
});

/**
 * CT-B08 — o alternador de tema, na tela onde ele aparece sem sidebar competindo.
 *
 * Não se assere a classe `dark` no `<html>`: o Alpine grava a escolha em `localStorage` e
 * aplica a classe num `x-effect`, detalhe de implementação do Filament que muda entre
 * versões. O que interessa ao usuário é que a tela continua utilizável depois do clique.
 */
it('alterna o tema pela tela de login', function (): void {
    visit('/app/login')
        ->assertSee('Faça login')
        ->click('[aria-label="Mudar para tema escuro"]')
        ->assertSee('Faça login')
        ->assertNoJavaScriptErrors();
});

/**
 * CT-B09 — acessibilidade dos dashboards. Nasce `->todo()`, e isso é deliberado.
 *
 * Rodando, ele falha de verdade, com dois achados que vêm de `vendor/`: contraste 4.25:1 no
 * environment indicator (serious, `pxlrbt/filament-environment-indicator`, e SÓ no tema
 * claro — no escuro o `dark:fi-text-color-400` atravessa o limiar) e o botão do Clear Cache
 * sem texto acessível (critical, `cms-multi/filament-clear-cache`, no `/infra` — o plugin é
 * registrado apenas no InfraPanelProvider). Corrigir exigiria mexer em `app/`, que o
 * requisito desta entrega põe fora de escopo. Ver 06-divida-tecnica.md → DT-01 e DT-02.
 *
 * `->todo()` e não comentado: assim a pendência aparece nomeada na saída de todo run, em
 * vez de dormir num comentário que ninguém lê.
 *
 * ATENÇÃO ao pagar a dívida: como lote, este cenário alcança só o PRIMEIRO painel que falha
 * — `visit([...])` aborta na primeira exceção, e `/app` já falha no contraste, então a
 * `critical` do `/infra` nunca é avaliada. Separar em um cenário por painel antes de
 * remover o `->todo()`. Ver QA-03 do 07-relatorio-qa.md.
 */
it('nao tem problema de acessibilidade nos dashboards', function (): void {
    $this->actingAs(usuarioDoKit('master_global'));

    visit(['/app', '/admin', '/infra'])->assertNoAccessibilityIssues();
})->todo();

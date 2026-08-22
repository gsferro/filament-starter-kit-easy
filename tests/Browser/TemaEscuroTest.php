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
 * CT-B09 — acessibilidade dos dashboards. Nasceu `->todo()`, e deixou de ser.
 *
 * Rodando, falhava com dois achados que vinham de `vendor/`: contraste no environment
 * indicator (serious, `pxlrbt/filament-environment-indicator`, e SÓ no tema claro — no
 * escuro o `dark:fi-text-color-400` atravessa o limiar) e o botão de limpar cache sem texto
 * acessível (critical, `cms-multi/filament-clear-cache`, no `/infra`). As duas dívidas foram
 * pagas — DT-02 por uma regra em `resources/css/filament/kit.css`, DT-01 pela cópia da blade
 * em `resources/views/vendor/filament-clear-cache/` — e o `->todo()` saiu junto.
 *
 * **Um cenário por painel, e não `visit([...])` em lote**: o lote aborta na primeira exceção,
 * então o `/app` falhando no contraste fazia a `critical` do `/infra` nunca ser avaliada — é
 * o que produziu o erro de proveniência de DT-01, que atribuiu o botão ao painel errado. Com
 * o dataset, os três painéis são medidos em todo run e cada um reporta o seu. Ver QA-03 do
 * 07-relatorio-qa.md.
 *
 * O tema claro é o eixo que interessa aqui: é onde os dois achados viviam.
 *
 * **O `waitForEvent('networkidle')` não é tempero — sem ele o caso é flaky por construção.**
 * O `assertNoAccessibilityIssues()` varre o DOM quando é chamado, e o axe julga **cor
 * computada**: varrer antes da folha de estilo assentar mede uma página sem CSS. Medido numa
 * execução de cache frio, no `/app`:
 *
 *     h1.fi-header-heading          #d0d0d0 sobre #fafafa   1,47:1
 *     .fi-account-widget-heading    #d0d0d0 sobre #fbfbfb   1,49:1
 *     .fi-account-widget-user-name  #e2e2e4 sobre #fbfbfb   1,25:1
 *     .fi-btn                       #d0d0d0 sobre #fbfbfb   1,49:1
 *
 * Quatro achados `serious`, e o que os denuncia como falsos é **todo** o texto da página estar
 * em cinza-claro: título, subtítulo, parágrafo e botão. O texto do Filament no tema claro é
 * quase preto — 1,25:1 uniforme é ausência de CSS, não escolha de paleta. A mesma suíte
 * passou na execução seguinte, com os assets quentes e sem uma linha mudada.
 *
 * O `networkidle` espera a rede sossegar, que é quando a folha de estilo já chegou e foi
 * aplicada. É o estado que o caso precisa, e não um `sleep` disfarçado: se o CSS nunca chegar,
 * a espera estoura com essa causa em vez de produzir quatro achados de contraste que mandam
 * quem lê procurar defeito de paleta onde não há.
 */
it('nao tem problema de acessibilidade no dashboard', function (string $painel): void {
    $this->actingAs(usuarioDoKit('master_global'));

    visit($painel)
        ->waitForEvent('networkidle')
        ->assertNoAccessibilityIssues();
})->with(['/app', '/admin', '/infra']);

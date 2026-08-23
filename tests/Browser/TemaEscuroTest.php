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

    /*
     * DT-06 — paga a compilação dos componentes do painel num request pelo KERNEL, antes de
     * abrir o navegador. Compilar as ~590 views do kit custa dezenas de segundos, e o primeiro
     * cenário que renderiza um painel paga a conta inteira DENTRO do timeout de 45 s do
     * Playwright — falhando por um motivo que não é o dele.
     *
     * Medido: numa árvore recém-buildada, este arquivo estourou `Timeout 45000ms exceeded`; a
     * execução seguinte, sem mudar uma linha, passou. É o disfarce que
     * `.ai/rules/testes-browser.md` descreve — "tem o formato de teste instável" — e que custou
     * duas execuções completas para separar de um defeito real.
     *
     * O `get()` é descartado de propósito: o que interessa é o efeito colateral em disco, que o
     * servidor do navegador reusa.
     */
    foreach (['/app', '/admin', '/infra'] as $painel) {
        $this->get($painel);
    }

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
 * **O `inLightMode()` é o que conserta a instabilidade, e a explicação anterior estava errada.**
 *
 * Este caso falhava com quatro achados `serious` no `/app`, todo o texto da página reportado em
 * `#d0d0d0` sobre `#fafafa`. A v0.18.4 atribuiu isso a cache frio — "varre antes de a folha de
 * estilo assentar" — e acrescentou o `waitForEvent('networkidle')`. **Diagnóstico errado.** O
 * experimento que o derruba: com a árvore recém-buildada e `view:clear` (cache frio de verdade),
 * rodar **só este caso** passa 3 de 3; rodar o ARQUIVO inteiro falha no `/app`. Não é o cache —
 * é o cenário anterior.
 *
 * O primeiro caso deste arquivo chama `->inDarkMode()`, e a emulação de `prefers-color-scheme`
 * **vaza para o cenário seguinte**. O Filament então emite os tokens de texto do tema escuro
 * (`#d0d0d0` é cinza-claro, correto sobre fundo escuro) enquanto o fundo continua claro. Não é
 * página sem CSS: é paleta escura sobre fundo claro.
 *
 * Daí a correção ser declarar o tema em vez de herdá-lo — e o docblock deste caso já dizia, na
 * última linha, que "o tema claro é o eixo que interessa aqui". Ele só não estava pedindo isso ao
 * navegador.
 *
 * O `networkidle` ficou: não custa nada e a rede sossegada continua sendo a pré-condição honesta
 * para o axe julgar cor computada. Mas não era ele que faltava.
 *
 * Os quatro achados, para quem os encontrar de novo:
 *
 *     h1.fi-header-heading          #d0d0d0 sobre #fafafa   1,47:1
 *     .fi-account-widget-heading    #d0d0d0 sobre #fbfbfb   1,49:1
 *     .fi-account-widget-user-name  #e2e2e4 sobre #fbfbfb   1,25:1
 *     .fi-btn                       #d0d0d0 sobre #fbfbfb   1,49:1
 *
 * O que os denuncia como falsos é **todo** o texto da página estar em cinza-claro ao mesmo tempo:
 * título, subtítulo, parágrafo e botão. Paleta inteira trocada é sinal de tema, não de um
 * elemento com cor mal escolhida.
 */
it('nao tem problema de acessibilidade no dashboard', function (string $painel): void {
    $this->actingAs(usuarioDoKit('master_global'));

    // DT-06 — mesma razão do primeiro caso: compila o painel fora do cronômetro do Playwright.
    $this->get($painel);

    visit($painel)
        ->inLightMode()
        ->waitForEvent('networkidle')
        ->assertNoAccessibilityIssues();
})->with(['/app', '/admin', '/infra']);

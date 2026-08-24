<?php

/**
 * A página de boas-vindas da rota `/` num navegador de verdade.
 *
 * CT-B01 de `wikis/specs/feat/pagina-boas-vindas/pagina-boas-vindas/05-casos-de-teste-browser.md`.
 *
 * Um cenário só, e o `05` registra por escrito os seis que foram cogitados e cortados. O eixo que
 * sobra é o único que só o navegador prova: `assertSee` devolve o MESMO HTML nos dois temas — ele
 * passa com texto branco em fundo branco (`.ai/rules/testes-browser.md`). O `tests/Kit` prova que o
 * `<script>` de tema está na resposta (CT-14); aqui se prova que ele roda.
 *
 * O segundo motivo é o risco que o ADR-01 assumiu: a rota `/` boota o painel `app` com ~30 plugins
 * FORA de uma rota de painel. Se algum deles gritar no console nesse contexto, o corpo do HTML vem
 * íntegro, o status é 200 e nenhum caso do `04` fica vermelho.
 *
 * Sem `actingAs()`: o cenário é anônimo por definição, como a rota.
 */
beforeEach(function (): void {
    /*
     * DT-06 — paga a compilação dos componentes do painel num request pelo KERNEL, antes de abrir o
     * navegador. O `view:cache` cobre as Blade do repositório, não os componentes Livewire do
     * Filament: são ~25 s que ele não adianta, e rodando este arquivo isolado eles cairiam dentro
     * do teto de 45 s do Playwright, falhando por um motivo que não é o do cenário.
     *
     * O retorno é descartado de propósito — o que interessa é o efeito colateral em disco, que o
     * servidor in-process do plugin reusa.
     */
    $this->get('/');
});

/**
 * CT-B01 — a página abre em tema escuro, com conteúdo, e sem erro no console.
 *
 * `assertNoJavaScriptErrors()` e não `assertNoSmoke()`: a `/` é HTML de autoria do kit DENTRO de um
 * painel bootado com ~30 plugins e o render hook `BODY_END` do `assistente-chat-widget`
 * (`AppPanelProvider.php:94-97`). `assertNoSmoke()` reprova em qualquer `console.log` de vendor, e
 * a suíte ficaria vermelha por dívida de terceiro — é o mesmo raciocínio que o CT-B04 de
 * `TelasDoKitTest.php` usa para justificar o inverso lá, onde as telas são todas de autoria
 * própria. Ver ADR-05.
 *
 * As asserções de conteúdo são as âncoras: console limpo, sozinho, passa em página em branco, em
 * 403 renderizado e em tela sem conteúdo.
 *
 * Sem `assertPathIs`: o cenário não navega, não há `press` nem `click`. Sem `wait()`: o plugin
 * reexecuta cada asserção até o teto de `pest()->browser()->timeout()`. Sem
 * `waitForEvent('networkidle')`: numa página que carrega um painel Filament a rede nunca fica
 * ociosa, e a espera morre no teto.
 */
it('abre em tema escuro, com conteudo, e sem erro de javascript', function (): void {
    visit('/')
        ->inDarkMode()
        ->assertSee('Bem-vindo ao Starter Kit Easy')
        ->assertSee('Painel do negócio')
        ->assertSee('Administração')
        ->assertSee('Infraestrutura')
        ->assertSee((string) config('kit.version'))
        ->assertNoJavaScriptErrors();
});

/**
 * CT-B02 — a página é legível nos dois temas.
 *
 * Nasceu de um achado do quality gate (QA-01, `06-relatorio-qa.md`): CT-B01 sozinho **não valida
 * tema**. `inDarkMode()->assertSee(...)` prova que a página ABRE sob `prefers-color-scheme: dark`,
 * e nada sobre cor — o texto pode estar branco em fundo branco, que ele continua no DOM e o
 * `assertSee` continua verde (`.ai/rules/testes-browser.md`). Sem este cenário, RQ-11 ("herda o
 * darkmode") ficava com oráculo apenas estático: a PRESENÇA do script de tema no HTML (CT-14).
 *
 * O axe é a verificação de contraste que existe sem pagar screenshot e olhar. Ele não pega tudo —
 * ícone que desaparece, sombra invertida — mas pega o defeito que importa aqui, que é cor de texto
 * sobre cor de fundo.
 *
 * **O tema é DECLARADO em cada linha, nunca herdado.** `tests/Browser/TemaEscuroTest.php` mediu
 * que a emulação de `prefers-color-scheme` **vaza** para o cenário seguinte: um cenário escuro
 * antes de um cenário sem declaração produzia quatro achados `serious` falsos — paleta escura sobre
 * fundo claro, com TODO o texto da página em cinza-claro ao mesmo tempo. Foi preciso duas execuções
 * completas da suíte para separar isso de um defeito real.
 */
it('nao tem problema de acessibilidade nos dois temas', function (string $tema): void {
    $pagina = visit('/');

    ($tema === 'escuro' ? $pagina->inDarkMode() : $pagina->inLightMode())
        ->assertSee('Bem-vindo ao Starter Kit Easy')
        ->assertNoAccessibilityIssues();
})->with(['claro', 'escuro']);

<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * CT-B01 de
 * `wikis/specs/feat/estudo-de-pacotes-rodada-2/estudo-de-pacotes-rodada-2/05-casos-de-teste-browser.md`.
 *
 * ## Por que navegador, e não teste de componente Livewire
 *
 * A asserção é sobre **JavaScript executado**. O Filament emite
 * `setUpUnsavedDataChangesAlert({ $wire })`
 * (`vendor/filament/filament/resources/views/components/page/index.blade.php:162-166`), e a função
 * registra um ouvinte de `beforeunload` que compara o md5 de `$wire.data` com `$wire.savedDataHash`
 * (`vendor/filament/filament/resources/js/unsaved-changes-alert.js:1-14`). Nada disso existe no
 * servidor: o componente Livewire responde igual com o formulário limpo e com o formulário sujo.
 *
 * CT-14 e CT-15 do `04` provam que o **painel** decidiu ligar o alerta (a matriz painel × chave).
 * Este cenário prova que a decisão **chegou ao navegador e funciona**.
 *
 * ## Por que dois disparos e não um
 *
 * Com um só, um ouvinte que cancelasse **sempre** — ignorando o estado do formulário — passaria
 * (mutante MB1). O par linha de base × oráculo é o que distingue "o alerta existe" de "o alerta
 * observa o formulário".
 *
 * ## Por que `defaultPrevented` e não o diálogo do navegador
 *
 * O plugin não expõe asserção sobre diálogo nativo (`confirm`/`beforeunload`), e o painel não está
 * em modo SPA — `window.addEventListener` + `preventDefault()` é o mecanismo real, e
 * `defaultPrevented` é o observável dele.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

it('[CT-B01] impede a saida silenciosa de um formulario com alteracao pendente', function (): void {
    /*
     * O disparo sintético de `beforeunload`, e o que o navegador respondeu.
     *
     * `Event` e não `BeforeUnloadEvent`: o ouvinte do Filament não lê nada do evento além de
     * `preventDefault()`/`returnValue`, e `cancelable: true` é a única propriedade de que
     * `defaultPrevented` depende.
     *
     * Variável local, e não constante de arquivo: em PHP a constante de topo é global no
     * processo, e o Pest carrega todos os arquivos de teste no mesmo — o mesmo motivo que
     * `.ai/rules/testes.md` dá para helper cruzado.
     */
    $disparaBeforeunload = <<<'JS'
        (() => {
            const evento = new Event('beforeunload', { cancelable: true });
            window.dispatchEvent(evento);
            return evento.defaultPrevented;
        })()
    JS;

    config(['kit.alerta_alteracoes_nao_salvas' => true]);

    $this->actingAs(usuarioDoKit('admin'));

    /*
     * DT-06 — paga a compilação dos componentes do painel num request pelo KERNEL, antes de abrir
     * o navegador. O `view:cache` cobre as Blade do repositório, não os componentes Livewire do
     * Filament: rodando este arquivo isolado eles cairiam dentro do teto de 45 s do Playwright.
     */
    $this->get('/admin/configuracoes-da-aplicacao');

    visit('/admin/configuracoes-da-aplicacao')
        // `assertPathIs` primeiro: é ela que espera a navegação.
        ->assertPathIs('/admin/configuracoes-da-aplicacao')
        // Linha de base — com o formulário limpo, a saída NÃO é barrada. Mata MB1.
        ->assertScript($disparaBeforeunload, false)
        ->fill('#form\.nome_da_aplicacao', 'Nome alterado')
        // Oráculo — agora é. Mata M24 (a decisão que morre no servidor).
        ->assertScript($disparaBeforeunload, true);

    /*
     * **Sem `assertNoJavaScriptErrors()`, e a ausência é medida.** Esta tela tem `ColorPicker`
     * dentro de `Tabs`, e o Chrome headless do Linux emite `ResizeObserver loop completed with
     * undelivered notifications` duas vezes na montagem — só no CI. É ruído do navegador, não
     * defeito do kit, e o plugin não oferece filtro (`assertNoJavaScriptErrors()` compara com
     * array vazio, `vendor/pestphp/pest-plugin-browser/src/Api/Concerns/MakesConsoleAssertions.php:78-89`).
     *
     * É a asserção de apoio do passo 7 do roteiro do `05`, que o próprio `05` autoriza remover
     * por este motivo — e `tests/Browser/ConfiguracoesDoKitTest.php` já a removeu pela mesma
     * causa, medida. Os oráculos que provam o comportamento são os dois `assertScript`, e eles
     * ficam.
     */
})->group('browser');

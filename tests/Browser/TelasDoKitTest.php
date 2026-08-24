<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * Smoke em navegador real das telas alcançáveis por URL fixa nos três painéis.
 *
 * O que isto pega e o `$this->get()` de tests/Kit não pega: um painel Filament é
 * Livewire + Alpine, então o HTML pode vir íntegro com status 200 e a tela estar
 * inutilizável porque um `x-on:click` estourou, porque um asset do Vite não subiu ou
 * porque um componente de plugin registrou erro no console. Nenhuma dessas três falhas
 * move o status HTTP.
 *
 * Lote com `visit([...])`: as 55 telas em 2 cenários (um deles com dataset de 3 painéis).
 * Escrever um cenário por tela custaria 55 boots de navegador para provar a mesma coisa.
 *
 * O inventário das telas autenticadas vive em `telasDoKit()`, em `tests/Pest.php` — não
 * porque este arquivo precise de um helper, mas porque `tests/Kit/InventarioDeTelasTest.php`
 * o reconcilia contra o que os painéis registram de verdade. Antes disso (DT-07) o array
 * podia divergir da realidade sem nada acusar, e divergiu: quatro telas registradas ficaram
 * de fora sem ninguém notar.
 */
beforeEach(function (): void {
    // Mesmo par de seeders de tests/Kit/PaineisTest.php:20-22. Sem helper novo de
    // propósito: papel sem a matriz de permissões do Shield abre painel e não abre tela.
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * CT-B01, CT-B02 e CT-B03 — as telas autenticadas de cada painel.
 *
 * Um dataset e não três cenários: o corpo era idêntico nos três, e o nome do painel
 * continua aparecendo na saída do Pest. O que era específico de cada um virou comentário
 * junto da rota que o motiva, dentro de `telasDoKit()`.
 *
 * `master_global` porque ele vence pelo `Gate::before` sem depender da matriz de
 * permissões: é o único papel capaz de abrir todas as telas. O recorte por papel é assunto
 * do CT-B05, não deste.
 */
it('abre as telas autenticadas do painel', function (array $rotas): void {
    $this->actingAs(usuarioDoKit('master_global'));

    visit($rotas)->assertNoJavaScriptErrors();
})->with(collect(telasDoKit())->map(fn (array $rotas): array => [$rotas])->all());

/**
 * CT-B04 — as telas públicas, sem nenhum `actingAs()`.
 *
 * Sem autenticação é o ponto do cenário: visitar `/app/login` autenticado redireciona, e
 * o teste mediria o redirecionamento em vez da tela.
 *
 * Aqui vale `assertNoSmoke()` e não `assertNoJavaScriptErrors()`: estas são as telas de
 * autoria do kit (TelaLogin, RegistroPorConvite, Auth Designer), onde `console.log` é
 * sujeira própria e sai de graça no mesmo cenário. Nas telas de painel, cheias de
 * plugin, seria vermelho por dívida de terceiro.
 *
 * Ficam fora de `telasDoKit()` porque não são tela de painel: a reconciliação com
 * `getPages()` + `getResources()` não tem nada a dizer sobre elas.
 *
 * `/*​/screen/lock` fica fora: o Lockscreen exige sessão bloqueada, que é estado, não
 * rota pública.
 */
it('abre as telas publicas dos tres paineis', function (): void {
    visit([
        '/app/login',
        '/app/register',
        '/app/password-reset/request',
        '/admin/login',
        '/admin/password-reset/request',
        '/infra/login',
        '/infra/password-reset/request',
    ])->assertNoSmoke();
});

/**
 * CT-B05 — o desafio de 2FA RENDERIZA o layout do Auth Designer, e continua operável.
 *
 * O que este cenário acrescenta, com precisão — porque `/{painel}/two-factor-authentication`
 * já está em `telasDoKit()` e já passa pelo lote do CT-B01 acima:
 *
 * - o lote prova que a tela não registra erro de JavaScript. Isso ele já provava antes desta
 *   feature, e continua provando agora que a classe da rota é a nossa;
 * - `tests/Kit/TelasDeAutenticacaoTest.php` prova que as classes do layout estão no HTML;
 * - **nenhum dos dois** prova que o layout do Auth Designer RENDERIZA o que ele injeta. O
 *   `.fi-auth-theme-switcher-wrapper` só existe no layout do pacote
 *   (`vendor/caresome/filament-auth-designer/resources/views/components/partials/theme-toggle.blade.php:20`),
 *   e `assertVisible` sobre ele é a única asserção da suíte que diz que a tela de 2FA saiu
 *   de fato vestida num navegador — não só com a classe no DOM.
 *
 * O `$this->get()` antes do `visit()` paga a compilação dos componentes do painel FORA do
 * cronômetro do Playwright — ver a rule do `view:cache` em `.ai/rules/testes-browser.md`.
 *
 * `assertNoJavaScriptErrors()` e não `assertNoSmoke()`: tela de terceiro (Breezy) dentro de
 * layout de terceiro (Auth Designer). E o console não é o oráculo — os dois `assertVisible`
 * são, porque página em branco e 403 renderizado passam por um console limpo.
 */
it('abre o desafio de 2FA vestido pelo auth designer, sem erro de JavaScript', function (): void {
    $this->actingAs(usuarioDoKit('master_global'));

    $this->get('/admin');

    visit('/admin/two-factor-authentication')
        ->assertVisible('#form\.code')
        ->assertVisible('.fi-auth-theme-switcher-wrapper')
        ->assertNoJavaScriptErrors();
});

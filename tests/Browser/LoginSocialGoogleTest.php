<?php

/**
 * CT-B01 — o botão de entrar com Google está VISÍVEL na tela de login renderizada.
 *
 * Um cenário só, e o gate está escrito no `05-casos-de-teste-browser.md`: presença, ordem no
 * DOM, ícone, `href` e escape do rodapé se provam em HTTP nos três painéis, ~40× mais barato, e
 * é o que `tests/Kit/LoginSocialGoogleTest.php` faz.
 *
 * Sobra exatamente uma afirmação que o HTML não prova:
 *
 *   `assertSee` fica VERDE com o botão presente no DOM e invisível.
 *
 * Um erro de JavaScript em qualquer componente da tela de login, um contêiner colapsado pela CSS
 * do Auth Designer ou um `x-show` herdado deixam o botão no HTML e fora da tela. O botão é a
 * ÚNICA porta do login social: invisível, a feature está entregue e não existe.
 *
 * **Sem `actingAs`**: a tela de login é a única superfície desta feature que o visitante vê sem
 * sessão. Autenticar antes de visitar redirecionaria para o painel.
 *
 * `assertNoJavaScriptErrors()` e não `assertNoSmoke()`: a tela é de plugin de terceiro
 * (`caresome/filament-auth-designer`), e o `assertNoSmoke()` deixaria a suíte vermelha por
 * `console.log` alheio que ninguém vai corrigir (`.ai/rules/testes-browser.md`).
 *
 * Nenhum `assertPathIs`: nenhuma ação navega aqui. O clique no botão foi deliberadamente
 * cortado — o `redirect()` do provedor falso aponta para `socialite.fake`, domínio que não
 * resolve, então clicar produziria erro de navegação do Playwright em vez de asserção. O `href`
 * prova o destino sem sair. Ver "Cogitado e cortado" no `05`.
 */
beforeEach(function (): void {
    config()->set([
        'kit.login.google.habilitado' => true,
        'kit.login.rodape'            => 'Kit — todos os direitos reservados',
        'services.google'             => [
            'client_id'     => 'id-de-teste',
            'client_secret' => 'segredo-de-teste',
            'redirect'      => '/auth/google/callback',
        ],
    ]);

    /*
     * DT-06 — paga a compilação dos componentes do painel num request pelo KERNEL, fora do
     * cronômetro do Playwright. O `view:cache` do `composer test:browser` cobre as Blade do
     * repositório, mas o primeiro render de um painel ainda paga a compilação dos componentes
     * Livewire do Filament — e rodando ESTE arquivo isolado ninguém pagou essa conta antes, o
     * que estoura os 45 s por um motivo que não é o do cenário.
     *
     * O retorno é descartado de propósito: o que interessa é o efeito colateral em disco, que o
     * servidor do navegador (mesmo processo) reusa.
     */
    $this->get('/app/login');
});

it('mostra o botão do Google visível e clicável na tela de login', function (): void {
    visit('/app/login')
        // Âncora do formulário primeiro: sem ela, "abaixo do form" não tem referência.
        ->assertVisible('#form\\.password')
        // A asserção da feature. `assertVisible` e não `assertPresent`: presente é o que
        // `assertSee` do arquivo de Kit já prova, e é o que fica verde com o botão escondido.
        ->assertVisible('[aria-label="Entrar com Google"]')
        ->assertAttributeContains('[aria-label="Entrar com Google"]', 'href', '/auth/google/redirect')
        ->assertVisible('.fi-login-rodape')
        ->assertSeeIn('.fi-login-rodape', 'todos os direitos reservados')
        ->assertNoJavaScriptErrors();
})->group('browser');

<?php

use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * O cadastro aberto visto pelo navegador — o que HTTP e componente Livewire não provam.
 *
 * `tests/Kit/RegistroAbertoTest.php` já prova que o cadastro grava, que o papel nasce certo e
 * que o pendente não fica autenticado. O que só o navegador prova aqui é outra coisa:
 *
 * 1. **o formulário existe e responde ao clique.** Até esta feature, a única cobertura de
 *    browser da rota `/app/register` era o lote de `tests/Browser/TelasDoKitTest.php` — que,
 *    sem token, REDIRECIONA para o login, e `assertNoJavaScriptErrors()` passa numa tela de
 *    login. O furo está registrado por escrito em
 *    `wikis/specs/fix/auth-designer-telas/.../05-casos-de-teste-browser.md:87`. Ligar o
 *    cadastro aberto é a primeira vez que aquele formulário existe para ser preenchido.
 * 2. **a notificação do pendente sobrevive ao redirecionamento.** Ela é gravada na sessão e
 *    renderizada por um componente Livewire na tela de DESTINO. Componente prova o despacho
 *    (`assertNotified()`); só o navegador prova que ela aparece depois da navegação — e que a
 *    ordem `logout` → `invalidate` → notificação não a joga fora.
 *
 * Persona: **visitante anônimo**, sem `actingAs()`. É a exceção legítima à regra do kit de
 * autenticar por `actingAs()` — aqui a ausência de sessão é o cenário.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    /*
     * Aquece a compilação dos componentes do painel FORA do cronômetro do Playwright.
     *
     * `.ai/rules/testes-browser.md`: o `view:cache` do `composer test:browser` cobre as Blade do
     * repositório, mas o primeiro render de um painel ainda paga a compilação dos componentes
     * Livewire do Filament — dezenas de segundos, dentro do teto de 45 s do cenário. Um request
     * pelo kernel paga a conta em PHP, e o servidor do plugin (que roda no MESMO processo) reusa
     * os arquivos compilados.
     */
    config(['kit.registro.habilitado' => true]);
    $this->get('/app/register');
});

/**
 * CT-B01 — o visitante cria a conta clicando, e chega ao painel.
 *
 * `assertPathIs` vem ANTES do `assertSee`, e a ordem não é estilo: é ela que espera a
 * navegação. Invertida, o `assertSee` é avaliado contra o snapshot da tela de cadastro e falha
 * dizendo que não achou o texto — com o cadastro tendo funcionado.
 *
 * `assertNoJavaScriptErrors()` e não `assertNoSmoke()`: a tela é o `Register` do Filament
 * vestido pelo Auth Designer, ou seja, tela de vendor, e o `assertNoSmoke()` reprovaria por
 * `console.log` alheio (`.ai/rules/testes-browser.md`).
 */
it('cria a conta pela tela de cadastro aberto e entra no painel', function (): void {
    config([
        'kit.registro.habilitado'       => true,
        'kit.registro.aprovacao_manual' => false,
        'kit.registro.verificar_email'  => false,
    ]);

    visit('/app/register')
        ->assertSee('Criar sua conta')
        ->fill('#form\\.name', 'Fulano da Silva')
        ->fill('#form\\.email', 'browser@example.com')
        ->fill('#form\\.password', 'segredo-bem-longo-123')
        ->fill('#form\\.passwordConfirmation', 'segredo-bem-longo-123')
        ->press('Criar conta')
        // Espera a navegação PRIMEIRO.
        ->assertPathIs('/app')
        // E a URL certa não basta: o par (path, conteúdo) é o que distingue "cadastrou" de
        // "trocou de URL para um painel que não renderizou".
        ->assertSee('Painel de Controle')
        ->assertNoJavaScriptErrors();

    $novo = User::where('email', 'browser@example.com')->firstOrFail();

    expect($novo->papeisEmQualquerContexto()->count())->toBe(1)
        ->and($novo->aprovacao_pendente)->toBeFalse();
});

/**
 * CT-B02 — @premissa o cadastro pendente termina no login, com a mensagem na tela.
 *
 * A asserção da mensagem casa um RADICAL (`aprova`), não a frase inteira: a redação não está no
 * requisito (ver `## Fronteira com o Plano` do `04`). Se o texto mudar sem perder o sentido, o
 * cenário segue válido; se a mensagem desaparecer, ele reprova.
 */
it('avisa na tela que o cadastro pendente aguarda aprovacao', function (): void {
    config([
        'kit.registro.habilitado'       => true,
        'kit.registro.aprovacao_manual' => true,
        'kit.registro.verificar_email'  => false,
    ]);

    visit('/app/register')
        ->fill('#form\\.name', 'Pendente da Silva')
        ->fill('#form\\.email', 'pendente-browser@example.com')
        ->fill('#form\\.password', 'segredo-bem-longo-123')
        ->fill('#form\\.passwordConfirmation', 'segredo-bem-longo-123')
        ->press('Criar conta')
        // assertPathIs espera PATH; getLoginUrl() devolveria URL absoluta.
        ->assertPathIs('/app/login')
        ->assertSee('aprova')
        ->assertNoJavaScriptErrors();

    $pendente = User::where('email', 'pendente-browser@example.com')->firstOrFail();

    expect($pendente->aprovacao_pendente)->toBeTrue()
        ->and($pendente->papeisEmQualquerContexto()->count())->toBe(0);
});

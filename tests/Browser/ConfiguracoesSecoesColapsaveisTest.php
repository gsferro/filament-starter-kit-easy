<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * O plugin cria um contexto novo por visit(), isolando o localStorage entre cenários.
 * Sem asserção de console: ColorPicker em Tabs emite ResizeObserver no CI
 * (regra testes-browser.md). Os oráculos verificam o comportamento visível.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    $this->actingAs(usuarioDoKit('admin'));
    $this->get('/admin/configuracoes-da-aplicacao');
});

it('[CT-B01] carrega a aba ativa sozinha e a inativa somente ao selecioná-la', function (): void {
    visit('/admin/configuracoes-da-aplicacao')
        ->assertVisible('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao')
        ->assertScript(<<<'JS'
            document.querySelector('#form\\.configuracoes\\.tabelas\\.paginacao_padrao') === null
            JS)
        ->click('Tabelas')
        ->assertVisible('#form\\.configuracoes\\.tabelas\\.paginacao_padrao')
        ->assertMissing('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao');
});

it('[CT-B02] mantém somente a seção colapsada pelo usuário fechada após recarregar', function (): void {
    $pagina = visit('/admin/configuracoes-da-aplicacao')
        ->assertVisible('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao')
        ->assertVisible('#form\\.configuracoes\\.identidade\\.cor_primaria_hex');

    $pagina->script(<<<'JS'
        document.querySelector('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao').closest('section').querySelector('[aria-expanded]').click()
        JS);

    $pagina->assertScript(<<<'JS'
        document.querySelector('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao').closest('section').querySelector('[aria-expanded]').getAttribute('aria-expanded') === 'false'
        JS)
        ->assertMissing('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao')
        ->refresh()
        ->assertVisible('#form\\.configuracoes\\.identidade\\.cor_primaria_hex')
        ->assertScript(<<<'JS'
            document.querySelector('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao').closest('section').querySelector('[aria-expanded]').getAttribute('aria-expanded') === 'false'
            JS)
        ->assertMissing('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao');

    // O inverso também persiste: a seção do Google nasce fechada; aberta pelo usuário, continua aberta depois de recarregar.
    $pagina->click('Login')
        ->assertSee('Entrar com Google')
        ->assertScript(<<<'JS'
            [...document.querySelectorAll('section')].find(s => s.querySelector('h1,h2,h3,h4,h5,h6')?.textContent.trim() === 'Entrar com Google').querySelector('[aria-expanded]').getAttribute('aria-expanded') === 'false'
            JS);

    $pagina->script(<<<'JS'
        [...document.querySelectorAll('section')].find(s => s.querySelector('h1,h2,h3,h4,h5,h6')?.textContent.trim() === 'Entrar com Google').querySelector('[aria-expanded]').click()
        JS);

    $pagina->assertVisible('#form\\.configuracoes\\.login\\.login_google_habilitado')
        ->refresh()
        ->click('Login')
        ->assertVisible('#form\\.configuracoes\\.login\\.login_google_habilitado')
        ->assertScript(<<<'JS'
            [...document.querySelectorAll('section')].find(s => s.querySelector('h1,h2,h3,h4,h5,h6')?.textContent.trim() === 'Entrar com Google').querySelector('[aria-expanded]').getAttribute('aria-expanded') === 'true'
            JS);
});

it('[CT-B03] mostra o erro do nome na seção colapsada e preserva o valor gravado', function (): void {
    gravarConfiguracao('nome_da_aplicacao', 'Antes');

    $pagina = visit('/admin/configuracoes-da-aplicacao')
        ->assertVisible('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao')
        ->fill('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao', '');

    $pagina->script(<<<'JS'
        document.querySelector('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao').closest('section').querySelector('[aria-expanded]').click()
        JS);

    $pagina->assertScript(<<<'JS'
        document.querySelector('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao').closest('section').querySelector('[aria-expanded]').getAttribute('aria-expanded') === 'false'
        JS)
        ->assertMissing('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao')
        ->press('Salvar')
        /*
         * O campo é `required()`: a validação nativa do navegador barra o envio antes do
         * Livewire. O Filament escuta o evento `invalid` do controle oculto, expande a seção
         * que o esconde e deixa o navegador mostrar a mensagem nativa no campo focado
         * (`vendor/filament/schemas/resources/js/index.js`, bloco `invalid`).
         */
        ->assertVisible('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao')
        ->assertScript(<<<'JS'
            (() => {
                const campo = document.querySelector('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao');

                return campo.closest('section').querySelector('[aria-expanded]').getAttribute('aria-expanded') === 'true'
                    && campo.validity.valueMissing
                    && campo.validationMessage.length > 0
                    && document.activeElement === campo;
            })()
            JS);

    expect(configuracaoGravada('nome_da_aplicacao'))->toBe('Antes');
});

it('[CT-B04] abre a aba da URL com conteúdo para chave válida, antiga ou desconhecida', function (?string $consulta): void {
    $url = '/admin/configuracoes-da-aplicacao'.($consulta ?? '');

    if ($consulta === null) {
        $pagina = visit($url)
            ->assertVisible('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao')
            ->click('Tabelas')
            ->assertVisible('#form\\.configuracoes\\.tabelas\\.paginacao_padrao')
            ->assertScript("new URL(location.href).searchParams.has('tab')");

        $url = $pagina->script('location.href');
    }

    $pagina = visit($url);

    if ($consulta === null) {
        $pagina->assertVisible('#form\\.configuracoes\\.tabelas\\.paginacao_padrao');
    } else {
        // Chave desconhecida cai na primeira aba (Identidade), e só ela carrega.
        $pagina->assertVisible('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao')
            ->assertScript(<<<'JS'
                document.querySelector('#form\\.configuracoes\\.tabelas\\.paginacao_padrao') === null
                JS);
    }
})->with([
    'URL escrita pela aba Tabelas'                  => [null],
    'link antigo da aba Kit (chave da v0.49.0)'     => ['?tab=kit::data::tab'],
    'link antigo da aba Tabelas (chave da v0.49.0)' => ['?tab=tabelas::data::tab'],
    'valor fora do domínio'                         => ['?tab=nada::tabs'],
]);

it('[CT-B05] revela o erro na seção anti-robô da aba Login nunca aberta', function (): void {
    gravarConfiguracao('login_anti_robo_habilitado', true);
    gravarConfiguracao('login_anti_robo_provedor', 'recaptcha_v3');
    gravarConfiguracao('login_anti_robo_pontuacao_minima', 5);

    visit('/admin/configuracoes-da-aplicacao')
        ->assertVisible('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao')
        ->assertScript(<<<'JS'
            document.querySelector('#form\\.configuracoes\\.login\\.login_anti_robo_pontuacao_minima') === null
            JS)
        ->press('Salvar')
        ->assertVisible('[data-field-wrapper]:has(#form\\.configuracoes\\.login\\.login_anti_robo_pontuacao_minima) .fi-fo-field-wrp-error-message')
        ->assertScript(<<<'JS'
            document.querySelector('#form\\.configuracoes\\.login\\.login_anti_robo_pontuacao_minima').closest('section').querySelector('[aria-expanded]').getAttribute('aria-expanded') === 'true'
            JS);
});

it('[CT-B06] mostra o servidor SMTP ao trocar o remetente sem salvar', function (): void {
    gravarConfiguracao('mail_mailer', 'log');

    visit('/admin/configuracoes-da-aplicacao')
        ->assertVisible('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao')
        ->click('E-mail')
        ->assertVisible('#form\\.configuracoes\\.email\\.mail_mailer')
        ->assertMissing('#form\\.configuracoes\\.email\\.mail_host')
        ->select('#form\\.configuracoes\\.email\\.mail_mailer', 'smtp')
        ->assertVisible('#form\\.configuracoes\\.email\\.mail_host');

    expect(configuracaoGravada('mail_mailer'))->toBe('log');
});

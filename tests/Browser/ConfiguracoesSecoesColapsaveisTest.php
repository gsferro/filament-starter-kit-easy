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

    $secaoDoNome = $pagina->script(<<<'JS'
        '#' + CSS.escape(document.querySelector('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao').closest('.fi-section').id)
        JS);

    $pagina->script(<<<'JS'
        document.querySelector('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao').closest('.fi-section').querySelector('.fi-section-header').click()
        JS);

    $pagina->assertAttributeContains($secaoDoNome, 'class', 'fi-collapsed')
        ->assertMissing('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao')
        ->refresh()
        ->assertVisible('#form\\.configuracoes\\.identidade\\.cor_primaria_hex')
        ->assertAttributeContains($secaoDoNome, 'class', 'fi-collapsed')
        ->assertMissing('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao');
});

it('[CT-B03] mostra o erro do nome na seção colapsada e preserva o valor gravado', function (): void {
    gravarConfiguracao('nome_da_aplicacao', 'Antes');

    $pagina = visit('/admin/configuracoes-da-aplicacao')
        ->assertVisible('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao')
        ->fill('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao', '');

    $secaoDoNome = $pagina->script(<<<'JS'
        '#' + CSS.escape(document.querySelector('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao').closest('.fi-section').id)
        JS);

    $pagina->script(<<<'JS'
        document.querySelector('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao').closest('.fi-section').querySelector('.fi-section-header').click()
        JS);

    $pagina->assertAttributeContains($secaoDoNome, 'class', 'fi-collapsed')
        ->assertMissing('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao')
        ->press('Salvar')
        ->assertVisible('[data-field-wrapper]:has(#form\\.configuracoes\\.identidade\\.nome_da_aplicacao) .fi-fo-field-wrp-error-message')
        ->assertScript(<<<'JS'
            document.querySelector('[data-field-wrapper]:has(#form\\.configuracoes\\.identidade\\.nome_da_aplicacao) .fi-fo-field-wrp-error-message').textContent.includes('obrigatório')
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
        $pagina->assertScript(<<<'JS'
            [...document.querySelectorAll('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao, #form\\.configuracoes\\.aplicacao\\.hub_de_navegacao')].some(e => e.offsetParent !== null)
            JS);
    }
})->with([
    'URL escrita pela aba Tabelas' => [null],
    'link antigo da aba Kit' => ['?tab=kit::tabs'],
    'valor fora do domínio' => ['?tab=nada::tabs'],
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
            ! document.querySelector('#form\\.configuracoes\\.login\\.login_anti_robo_pontuacao_minima').closest('.fi-section').classList.contains('fi-collapsed')
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

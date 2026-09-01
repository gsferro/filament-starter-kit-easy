<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * CT-B05 — sem organização, a tela de bloqueio usa a mídia base da aplicação.
 *
 * Este é o caso que protege os painéis `/admin` e `/infra`: a mesma `TelaBloqueio` serve os três
 * (o bind está em `AppServiceProvider::register()`), e a guarda de painel é o que impede o
 * administrador da instalação de ver a logo de um cliente.
 *
 * Fica na pasta single-tenant DE PROPÓSITO — é o cenário sem tenant, e é aqui que `/app` existe
 * sem o segmento `{tenant}`. O contrário, com a logo da organização, é
 * `tests/BrowserTenancy/IdentidadeVisualTest.php`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

it('usa a midia base na tela de bloqueio sem organizacao', function (): void {
    $this->actingAs(usuarioDoKit('master_global'));

    // A trava pela rota do pacote — o mesmo POST do item "Bloquear sessão" do menu do usuário.
    // Sai do processo do teste porque o servidor do plugin é in-process: é a MESMA sessão que o
    // navegador enxerga.
    $this->post(route('lockscreen.app.lock-session'))->assertRedirect();

    visit('/app/screen/lock')
        ->assertPathIs('/app/screen/lock')
        // A arte padrão é um SVG gerado com o nome da aplicação, embutido como data URI
        // (wiki `arte-do-login-com-nome-da-aplicacao`). Se a troca de mídia da organização
        // vazasse para o caso sem tenant, o `src` seria a URL do arquivo dela.
        ->assertAttributeContains('.fi-auth-media', 'src', 'data:image/svg+xml;base64,')
        // CT-B01 — a arte PINTA, e não é o texto alternativo.
        //
        // Este é o único oráculo do conjunto que o HTTP não expressa: para uma resposta
        // HTTP, "a imagem quebrou" e "a imagem pintou" são idênticas — mesmo status, mesma
        // string. O data URI é construído por nós, e mime errado, `;base64` esquecido ou
        // payload truncado produzem `naturalWidth === 0` com toda a suíte verde.
        //
        // A ordem importa: `assertNoBrokenImages()` filtra `document.images`, e conjunto
        // vazio satisfaz "nenhuma quebrada". Se a mídia deixasse de ser `<img>` — o pacote
        // escolhe o ramo por extensão, e data URI não tem —, a página ficaria sem arte e a
        // asserção passaria. Por isso a existência da imagem é afirmada antes.
        ->assertPresent('img.fi-auth-media')
        ->assertNoBrokenImages()
        ->assertSee('Desbloquear')
        ->assertNoJavaScriptErrors();
});

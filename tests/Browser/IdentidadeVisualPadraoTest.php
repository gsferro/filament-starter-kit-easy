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
        // `images/auth/login.svg` é a mídia configurada no `AuthDesignerPlugin` do painel. Se a
        // troca de mídia da organização vazasse para o caso sem tenant, o `src` seria outro.
        ->assertAttributeContains('.fi-auth-media', 'src', 'images/auth/login.svg')
        ->assertSee('Desbloquear')
        ->assertNoJavaScriptErrors();
});

<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use HoceineEl\QuickActionDock\Support\DockContext;

it('[CT-10] bloqueio com tenancy não entrega dock nem chat e o painel entrega', function (bool $bloquear): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    ['acme' => $acme, 'usuario' => $usuario] = arranjarFavoritosDoDockTenancy();
    conversaPara($usuario, 'Sentinela da pilha 7f3c');
    ligarAgentesDeIa(true, ['app']);
    $this->get('/app/'.$acme->slug)->assertOk();
    if ($bloquear) {
        $this->post(route('lockscreen.app.lock-session'))->assertRedirect();
    }

    app()->forgetInstance(DockContext::class);
    $resposta = $this->get($bloquear ? route('lockscreen.app.page') : '/app/'.$acme->slug)->assertOk();

    $html = preg_replace('~<script\b[^>]*>.*?</script>~is', '', $resposta->getContent());
    $this->assertStringContainsString('data-voltar-ao-topo', $html);
    foreach (['wire:name="quick-action-dock"', 'data-assistente-chat', 'Sentinela da pilha 7f3c'] as $agulha) {
        if ($bloquear) {
            $this->assertStringNotContainsString($agulha, $html, $agulha);
        } else {
            $this->assertStringContainsString($agulha, $html, $agulha);
        }
    }
})->with(['bloqueio' => [true], 'controle positivo' => [false]]);

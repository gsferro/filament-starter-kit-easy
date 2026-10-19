<?php

use App\Livewire\AssistenteChatWidget;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * [CT-23] Com organizações ligadas, o chat no /admin (sem organização corrente) abre e lista
 * só as conversas de quem está nele. A Carla é a persona de controle: conversa real no banco.
 */
it('[CT-23] com organizações, o chat no /admin abre e lista só as conversas de quem está nele', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    ['acme' => $acme, 'usuario' => $bruno] = duasOrganizacoes();
    $carla                                 = usuarioComPapel('panel_user', $acme, 'carla@example.com');
    conversaPara($bruno, 'Revisão do contrato');
    conversaPara($carla, 'Pedido da Acme');
    ligarAgentesDeIa(true, ['admin']);
    $this->actingAs($bruno);

    $resposta = $this->get('/admin')->assertOk();

    $html = preg_replace('~<script\b[^>]*>.*?</script>~is', '', $resposta->getContent());
    expect($html)->toContain('data-assistente-chat');

    fronteiraDeRequest();
    Filament::setCurrentPanel('admin');

    Livewire::test(AssistenteChatWidget::class)
        ->assertSee('Revisão do contrato')
        ->assertDontSee('Pedido da Acme');
});

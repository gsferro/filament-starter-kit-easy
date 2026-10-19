<?php

use App\Ai\Agents\Assistente;
use App\Ai\Agents\GuardaPrompt;
use App\Livewire\AssistenteChatWidget;
use Database\Seeders\AssistenteSeeder;
use Database\Seeders\GuardaPromptSeeder;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
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

/**
 * [CT-28] A execução gravada em `ai_runs` leva o tenant da rota; fora de rota de organização,
 * o `default` do ledger. O Bruno é membro da Acme, então um resolvedor que olhasse o usuário
 * em vez da rota teria uma organização a devolver no `/admin`.
 */
it('[CT-28] a execução gravada pelo chat leva o tenant da rota, ou o default fora de organização', function (string $onde): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    $this->seed([AssistenteSeeder::class, GuardaPromptSeeder::class]);
    config(['ai.providers.llamacpp.models.text.default' => 'modelo-de-teste']);
    ['acme' => $acme, 'usuario' => $bruno] = duasOrganizacoes();
    ligarAgentesDeIa(true, ['admin', 'app']);
    $this->actingAs($bruno);

    if ($onde === 'app') {
        $this->get('/app/'.$acme->slug)->assertOk();
        fronteiraDeRequest();
        noPainelDa($acme);
    } else {
        Filament::setCurrentPanel('admin');
    }

    GuardaPrompt::fake([['seguro' => true, 'categoria' => 'legitima', 'motivo' => 'pergunta comum']]);
    Assistente::fake(['Resposta fixa do teste']);
    DB::table('ai_runs')->delete();

    Livewire::test(AssistenteChatWidget::class)
        ->set('mensagemPendente', 'Qual é o prazo?')
        ->call('responder');

    Assistente::assertPrompted('Qual é o prazo?');
    $tenants  = DB::table('ai_runs')->pluck('tenant_id')->map(fn ($t) => (string) $t)->all();
    $esperado = $onde === 'app' ? (string) $acme->getKey() : config('ai-tasks.default_tenant');
    expect($tenants)->not->toBeEmpty()->and(array_unique($tenants))->toBe([$esperado]);
})->with(['/admin' => ['admin'], '/app/{acme}' => ['app']]);

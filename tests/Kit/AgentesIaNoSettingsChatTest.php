<?php

use App\Ai\Agents\Assistente;
use App\Ai\Agents\GuardaPrompt;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Admin\Widgets\AgentesIaPorProvider;
use App\Filament\Admin\Widgets\AgentesIaStats;
use App\Filament\Infra\Resources\AiRuns\AiRunResource;
use App\Filament\Infra\Resources\AiRuns\Pages\ListAiRuns;
use App\Filament\Infra\Widgets\IaStats;
use App\Livewire\AssistenteChatWidget;
use App\Models\AgenteIa;
use App\Models\User;
use Croustibat\FilamentJobsMonitor\Resources\QueueMonitorResource;
use Database\Seeders\AssistenteSeeder;
use Database\Seeders\GuardaPromptSeeder;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Models\ConversationMessage;
use Livewire\Livewire;
use Psr\Log\LoggerInterface;

/**
 * Lote B da feature `agentes-ia-no-settings` (R8, R9, R10, R11, R13 sem o CT-23): o chat do
 * Assistente por painel (render e ações) e os widgets do dashboard.
 *
 * O `04` nomeia um arquivo só para a suíte Kit (`tests/Kit/AgentesIaNoSettingsTest.php`); a
 * sessão o dividiu em dois por tamanho, e este é o do chat e dos widgets.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    $this->seed([AssistenteSeeder::class, GuardaPromptSeeder::class]);
    config(['ai.providers.llamacpp.models.text.default' => 'modelo-de-teste']);
});

/** O Bruno: entra nos três painéis. */
function brunoDoChat(): User
{
    $bruno = usuarioDoKit('admin', 'bruno@example.com');
    $bruno->assignRole(['panel_user', 'infra']);

    return $bruno;
}

/**
 * Grava a decisão pelo caminho banco → config (o do boot), como a tela de configurações a deixa.
 *
 * @param  list<string>  $paineis
 */
function decisaoGravadaDoChat(bool $ligado, array $paineis): void
{
    gravarConfiguracao('agentes_ia_habilitado', $ligado);
    gravarConfiguracao('agentes_ia_paineis', $paineis);
    alinharConfiguracoesDoKit();
}

/** O HTML sem `<script>`: onde o marcador do chat vale. */
function htmlSemScript(string $html): string
{
    return preg_replace('~<script\b[^>]*>.*?</script>~is', '', $html);
}

/** O espião do canal `ai`, instalado depois do arranjo. */
function espiaDoCanalAi(): LoggerInterface
{
    $canal = Mockery::spy(LoggerInterface::class);
    Log::partialMock()->shouldReceive('channel')->with('ai')->andReturn($canal);

    return $canal;
}

it('[CT-16] a lista e o interruptor decidem em que painel o chat é renderizado', function (bool $ligado, array $lista, string $tela, bool $aparece): void {
    $bruno = brunoDoChat();
    decisaoGravadaDoChat($ligado, $lista);

    $url = match ($tela) {
        'dashboard-admin' => '/admin',
        'dashboard-app'   => '/app',
        'dashboard-infra' => '/infra',
        'admin-users'     => UserResource::getUrl(panel: 'admin'),
        'infra-filas'     => QueueMonitorResource::getUrl(panel: 'infra'),
    };

    $resposta = $this->actingAs($bruno)->get($url)->assertOk();
    $html     = htmlSemScript($resposta->getContent());

    if ($aparece) {
        $resposta->assertSeeLivewire(AssistenteChatWidget::class);
        $this->assertStringContainsString('data-assistente-chat', $html);
    } else {
        $resposta->assertDontSeeLivewire(AssistenteChatWidget::class);
        $this->assertStringNotContainsString('data-assistente-chat', $html);
    }
})->with([
    'desligado: interruptor vence a lista (/admin)'  => [false, ['admin', 'app', 'infra'], 'dashboard-admin', false],
    'desligado: interruptor vence a lista (/app)'    => [false, ['admin', 'app', 'infra'], 'dashboard-app', false],
    'desligado: interruptor vence a lista (/infra)'  => [false, ['admin', 'app', 'infra'], 'dashboard-infra', false],
    'vazia = nenhum (/admin)'                        => [true, [], 'dashboard-admin', false],
    'vazia = nenhum (/app perde o chat)'             => [true, [], 'dashboard-app', false],
    'vazia = nenhum (/infra)'                        => [true, [], 'dashboard-infra', false],
    '["admin"] no dashboard do /admin'               => [true, ['admin'], 'dashboard-admin', true],
    '["admin"] em /admin/users (Page comum)'         => [true, ['admin'], 'admin-users', true],
    '["admin"] no dashboard do /app'                 => [true, ['admin'], 'dashboard-app', false],
    '["app"] no dashboard do /app'                   => [true, ['app'], 'dashboard-app', true],
    '["admin","infra"] no dashboard do /infra'       => [true, ['admin', 'infra'], 'dashboard-infra', true],
    '["infra"] em /infra/queue-monitors'             => [true, ['infra'], 'infra-filas', true],
    '["admin","infra"] no dashboard do /app'         => [true, ['admin', 'infra'], 'dashboard-app', false],
    '["App"] (caixa) no /app'                        => [true, ['App'], 'dashboard-app', false],
    '["apps"] (sufixo) no /app'                      => [true, ['apps'], 'dashboard-app', false],
    '["infra-x","admin"] no /infra'                  => [true, ['infra-x', 'admin'], 'dashboard-infra', false],
    '["infra-x","infra"] no /infra'                  => [true, ['infra-x', 'infra'], 'dashboard-infra', true],
]);

it('[CT-17] o chat nunca é renderizado em tela de autenticação', function (string $tela, bool $autenticado, bool $aparece): void {
    $bruno = brunoDoChat();
    ligarAgentesDeIa(true, ['admin', 'app', 'infra']);
    ligarLoginUnificado(str_starts_with($tela, '/login'));

    if ($autenticado) {
        $this->actingAs($bruno);
    }

    if ($tela === '/admin/screen/lock') {
        $this->post(route('lockscreen.admin.lock-session'))->assertRedirect();
    }

    $resposta = $this->get($tela)->assertOk();
    $html     = htmlSemScript($resposta->getContent());

    if ($aparece) {
        $resposta->assertSeeLivewire(AssistenteChatWidget::class);
        $this->assertStringContainsString('data-assistente-chat', $html);
    } else {
        $resposta->assertDontSeeLivewire(AssistenteChatWidget::class);
        $this->assertStringNotContainsString('data-assistente-chat', $html);
    }
})->with([
    'bloqueio, dentro do painel'    => ['/admin/screen/lock', true, false],
    'login do painel'               => ['/admin/login', false, false],
    'layout do Auth Designer'       => ['/admin/password-reset/request', false, false],
    'página única de login'         => ['/login', false, false],
    'escolha de painel'             => ['/login/painel', true, false],
    'controle positivo'             => ['/admin', true, true],
]);

/**
 * Monta o chat da Ana no `/app`, com as duas conversas dela, e devolve o necessário para o CT-18.
 *
 * @return array{0: Livewire\Features\SupportTesting\Testable, 1: Conversation, 2: Conversation}
 */
function chatMontadoDaAnaNoApp(object $teste): array
{
    ligarAgentesDeIa(true, ['app']);

    $ana = usuarioDoKit('panel_user', 'ana@example.com');
    $teste->actingAs($ana)->get('/app')->assertOk();
    Filament::setCurrentPanel('app');

    $plano  = conversaPara($ana, 'Plano de férias');
    $viagem = conversaPara($ana, 'Viagem');

    $chat = Livewire::test(AssistenteChatWidget::class)
        ->call('retomarConversa', $plano->id)
        ->assertSet('conversaId', $plano->id);

    return [$chat, $plano, $viagem];
}

it('[CT-18] o chat montado com o uso ligado recusa a ação depois que o uso é desligado no painel', function (string $motivo, string $verbo): void {
    GuardaPrompt::fake([['seguro' => true, 'categoria' => 'legitima', 'motivo' => 'pergunta comum']]);
    Assistente::fake(['Resposta fixa do teste']);

    [$chat, $plano, $viagem] = chatMontadoDaAnaNoApp($this);

    $chat->set('mensagem', 'Qual é o prazo?');
    if ($verbo === 'responder') {
        $chat->set('mensagem', '')->set('mensagemPendente', 'Qual é o prazo?');
    }

    $conversasAntes = Conversation::count();
    $mensagensAntes = ConversationMessage::count();

    $motivo === 'desligado'
        ? ligarAgentesDeIa(false, ['app'])
        : ligarAgentesDeIa(true, ['admin']);

    $canal = espiaDoCanalAi();

    $chamada = match ($verbo) {
        'enviar'           => fn () => $chat->call('enviar'),
        'responder'        => fn () => $chat->call('responder'),
        'novaConversa'     => fn () => $chat->call('novaConversa'),
        'retomarConversa'  => fn () => $chat->call('retomarConversa', $viagem->id),
        'renomearConversa' => fn () => $chat->call('renomearConversa', $viagem->id, 'Renomeada'),
    };

    $chamada()->assertNotFound();

    Assistente::assertNeverPrompted();
    GuardaPrompt::assertNeverPrompted();

    expect(Conversation::count())->toBe($conversasAntes)
        ->and(ConversationMessage::count())->toBe($mensagensAntes)
        ->and($plano->fresh()->title)->toBe('Plano de férias')
        ->and($viagem->fresh()->title)->toBe('Viagem');

    $canal->shouldHaveReceived('warning')->once();
})->with(function () {
    foreach (['desligado' => 'o uso é desligado', 'fora' => 'o /app sai da lista'] as $motivo => $rotulo) {
        foreach (['enviar', 'responder', 'novaConversa', 'retomarConversa', 'renomearConversa'] as $verbo) {
            yield "{$rotulo}: {$verbo}" => [$motivo, $verbo];
        }
    }
});

it('[CT-19] cada forma de o painel corrente ficar fora da lista recusa a pergunta, e o painel na lista a responde', function (string $painel, array $lista, string $mudanca, int $status, int $conversasEsperadas, int $warnings): void {
    GuardaPrompt::fake([['seguro' => true, 'categoria' => 'legitima', 'motivo' => 'pergunta comum']]);
    Assistente::fake(['Resposta fixa do teste']);

    ligarAgentesDeIa(true, $lista);

    if ($painel === 'app') {
        $pessoa = usuarioDoKit('panel_user', 'ana@example.com');
        $this->actingAs($pessoa)->get('/app')->assertOk();
        Filament::setCurrentPanel('app');
    } else {
        $pessoa = brunoDoChat();
        $this->actingAs($pessoa);
        noPainelBootado('admin');
    }

    $chat = Livewire::test(AssistenteChatWidget::class)
        ->set('mensagemPendente', 'Qual é o prazo?');

    match ($mudanca) {
        'esvaziada'  => ligarAgentesDeIa(true, []),
        'App'        => ligarAgentesDeIa(true, ['App']),
        'apps'       => ligarAgentesDeIa(true, ['apps']),
        'sem-painel' => ligarAgentesDeIa(true, ['admin', 'app', 'infra']),
        'nada'       => null,
    };

    if ($mudanca === 'sem-painel') {
        Filament::setCurrentPanel(null);
    }

    $canal = espiaDoCanalAi();

    $chat->call('responder');

    if ($status === 404) {
        $chat->assertNotFound();
        Assistente::assertNeverPrompted();
        GuardaPrompt::assertNeverPrompted();
    } else {
        $chat->assertOk();
        Assistente::assertPrompted('Qual é o prazo?');
    }

    expect(Conversation::query()
        ->where('participant_type', $pessoa->getMorphClass())
        ->where('participant_id', $pessoa->getKey())
        ->count())->toBe($conversasEsperadas);

    if ($status === 200) {
        expect(Conversation::sole()->participant_id)->toBe($pessoa->getKey());
    }

    if ($warnings === 0) {
        $canal->shouldNotHaveReceived('warning');
    } else {
        $canal->shouldHaveReceived('warning')->times($warnings);
    }
})->with([
    'a lista é esvaziada'                         => ['app', ['app'], 'esvaziada', 404, 0, 1],
    'a lista passa a ["App"]'                     => ['app', ['app'], 'App', 404, 0, 1],
    'a lista passa a ["apps"]'                    => ['app', ['app'], 'apps', 404, 0, 1],
    'não há painel corrente'                      => ['app', ['app'], 'sem-painel', 404, 0, 1],
    'nada muda (controle positivo)'               => ['app', ['app'], 'nada', 200, 1, 0],
    'o Bruno no /admin (controle no painel novo)' => ['admin', ['admin'], 'nada', 200, 1, 0],
]);

it('[CT-20] os dois widgets somem com o uso desligado e voltam ao religar, com os agentes intactos', function (string $historico): void {
    $mestre = usuarioDoKit('master_global', 'master@example.com');
    $this->actingAs($mestre);

    $presentes = $historico !== 'desligado';

    match ($historico) {
        'desligado' => ligarAgentesDeIa(false, []),
        'religado'  => ligarAgentesDeIa(false, []),
        'ligado'    => ligarAgentesDeIa(true, ['admin']),
    };

    if ($historico === 'religado') {
        $this->get('/admin')->assertOk()
            ->assertDontSeeLivewire(AgentesIaStats::class)
            ->assertDontSeeLivewire(AgentesIaPorProvider::class);

        fronteiraDeRequest();
        ligarAgentesDeIa(true, []);
    }

    $resposta = $this->get('/admin')->assertOk();

    if ($presentes) {
        $resposta->assertSeeLivewire(AgentesIaStats::class)
            ->assertSeeLivewire(AgentesIaPorProvider::class);
    } else {
        $resposta->assertDontSeeLivewire(AgentesIaStats::class)
            ->assertDontSeeLivewire(AgentesIaPorProvider::class);
    }

    expect(AgenteIa::query()->count())->toBe(2)
        ->and(AgenteIa::query()->where('ativo', true)->count())->toBe(2);
})->with([
    'o uso desligado'                                       => ['desligado'],
    'dashboard já aberto desligado, religado com []'        => ['religado'],
    'o uso ligado com ["admin"]'                            => ['ligado'],
]);

it('[CT-21] com o uso desligado, as trilhas de IA do /infra continuam', function (string $tela): void {
    ligarAgentesDeIa(false, []);
    $this->actingAs(usuarioDoKit('infra', 'iris@example.com'));

    if ($tela === 'dashboard') {
        $this->get('/infra')->assertOk()->assertSeeLivewire(IaStats::class);

        return;
    }

    $this->get(AiRunResource::getUrl('index', panel: 'infra'))
        ->assertOk()
        ->assertSeeLivewire(ListAiRuns::class);
})->with([
    'o dashboard do /infra'       => ['dashboard'],
    'a lista de execuções de IA'  => ['lista'],
]);

it('[CT-24] a conversa criada no /app aparece no chat do /admin da mesma pessoa', function (): void {
    GuardaPrompt::fake([['seguro' => true, 'categoria' => 'legitima', 'motivo' => 'pergunta comum']]);
    Assistente::fake(['Resposta fixa do teste']);
    ligarAgentesDeIa(true, ['admin', 'app']);

    $ana   = usuarioDoKit('panel_user', 'ana@example.com');
    $bruno = brunoDoChat();
    conversaPara($ana, 'Plano de férias');

    $this->actingAs($bruno)->get('/app')->assertOk();
    Filament::setCurrentPanel('app');

    $noApp = Livewire::test(AssistenteChatWidget::class)
        ->set('mensagemPendente', 'Viagem')
        ->call('responder')
        ->assertOk();

    $conversa = Conversation::query()->where('participant_id', $bruno->getKey())->sole();
    $noApp->call('renomearConversa', $conversa->id, 'Viagem');

    fronteiraDeRequest();
    noPainelBootado('admin');

    Livewire::test(AssistenteChatWidget::class)
        ->assertSee('Viagem')
        ->assertDontSee('Plano de férias');
});

it('[CT-26] o /app sai da lista e volta, e a Ana retoma a conversa com as mensagens de antes', function (): void {
    ligarAgentesDeIa(true, ['app']);

    $ana   = usuarioDoKit('panel_user', 'ana@example.com');
    $plano = conversaPara($ana, 'Plano de férias');

    foreach ([['user', 'Qual é o prazo?'], ['assistant', 'O prazo é sexta-feira.']] as [$papel, $texto]) {
        ConversationMessage::create([
            'id'               => (string) Str::orderedUuid(),
            'conversation_id'  => $plano->id,
            'participant_type' => $ana->getMorphClass(),
            'participant_id'   => $ana->getKey(),
            'agent'            => Assistente::class,
            'role'             => $papel,
            'content'          => $texto,
            'attachments'      => '[]',
            'tool_calls'       => '[]',
            'tool_results'     => '[]',
            'usage'            => '[]',
            'meta'             => '[]',
        ]);
    }

    $this->actingAs($ana)->get('/app')->assertOk();
    Filament::setCurrentPanel('app');

    $chat = Livewire::test(AssistenteChatWidget::class);

    ligarAgentesDeIa(true, ['admin']);
    $chat->call('retomarConversa', $plano->id)->assertNotFound();

    ligarAgentesDeIa(true, ['app']);

    $retomado = Livewire::test(AssistenteChatWidget::class)
        ->call('retomarConversa', $plano->id)
        ->assertOk()
        ->assertSet('conversaId', $plano->id);

    $html = $retomado->html();

    expect($html)->toContain('Qual é o prazo?')->toContain('O prazo é sexta-feira.')
        ->and(strpos($html, 'Qual é o prazo?'))->toBeLessThan(strpos($html, 'O prazo é sexta-feira.'));

    expect(ConversationMessage::query()->where('conversation_id', $plano->id)->count())->toBe(2);
});

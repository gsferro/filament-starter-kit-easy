<?php

use App\Ai\Agents\Assistente;
use App\Livewire\AssistenteChatWidget;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Support\Str;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Models\ConversationMessage;
use Livewire\Livewire;

/**
 * Regra R7 da wiki `phpstan-nivel-8`: o widget do assistente (`AssistenteChatWidget`, montado no
 * `BODY_END` de TODA página do `/app` — inclusive a tela de login) só mostra e só age sobre
 * conversas do usuário autenticado. Sem usuário, ele renderiza vazio e recusa toda ação.
 *
 * Não existia nenhum teste deste componente antes desta wiki (`grep` por
 * `AssistenteChatWidget`/`assistente-chat` em `tests/` só achava um comentário em
 * `tests/Browser/BoasVindasTest.php`).
 *
 * `Conversation`/`ConversationMessage` são do vendor (`laravel/ai`, tabelas `agent_conversations`
 * e `agent_conversation_messages`) e não têm factory própria no kit — `conversaPara()` cobre.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * Uma conversa, com dono opcional. `participant_type`/`participant_id` nulos = "Rascunho órfão"
 * (as duas colunas são nullable — migration `2026_08_12_165028_create_agent_conversations_table.php`).
 *
 * `Conversation` tem `$keyType = 'string'` e `#[WithoutIncrementing]`: a chave não se gera
 * sozinha, e `$guarded = []` deixa todo atributo passar pelo `create()`.
 */
function conversaPara(?User $dono, string $titulo): Conversation
{
    return Conversation::create([
        'id'               => (string) Str::uuid(),
        'participant_type' => $dono?->getMorphClass(),
        'participant_id'   => $dono?->getKey(),
        'title'            => $titulo,
    ]);
}

/**
 * CT-14 — o visitante abre a tela de login do painel de negócio sem erro, o widget está montado
 * (M33a: se ele sumir do render hook, este caso é quem acusa) e o corpo da página não contém a
 * conversa de ninguém.
 */
it('[CT-14] o visitante abre a tela de login do painel de negócio sem erro e sem conversa de ninguém', function (): void {
    $ana = usuarioDoKit('panel_user', 'ana@example.com');
    conversaPara($ana, 'Plano de férias');

    $resposta = $this->get('/app/login');

    $resposta->assertOk()->assertSeeLivewire(AssistenteChatWidget::class);

    $this->assertStringNotContainsString('Plano de férias', $resposta->getContent());
});

/**
 * CT-15 — o widget lista o histórico só da dona da conversa. `bruno` (autenticado, sem conversa
 * própria) e o "Rascunho órfão" (dono nulo) nunca aparecem, nas duas personas.
 *
 * A linha `ana` é o CONTROLE POSITIVO — sem ela, um `historico()` que sempre devolvesse vazio
 * passaria na linha `visitante` sem provar nada (o oráculo vive: `.ai/rules/testes.md`).
 */
it('[CT-15] o widget lista conversas só para a dona delas', function (string $persona, string $resultado): void {
    $ana   = usuarioDoKit('panel_user', 'ana@example.com');
    $bruno = usuarioDoKit('infra', 'bruno@example.com');

    conversaPara($ana, 'Plano de férias');
    conversaPara($bruno, 'Orçamento do Bruno');
    conversaPara(null, 'Rascunho órfão');

    if ($persona === 'ana') {
        $this->actingAs($ana);
    }

    $componente = Livewire::test(AssistenteChatWidget::class);

    $resultado === 'mostra'
        ? $componente->assertSee('Plano de férias')
        : $componente->assertDontSee('Plano de férias');

    $componente
        ->assertDontSee('Orçamento do Bruno')
        ->assertDontSee('Rascunho órfão');
})->with([
    'sem usuário'                        => ['visitante', 'não mostra'],
    'controle positivo: o oráculo vive'  => ['ana', 'mostra'],
]);

/**
 * CT-16 — o visitante que chama qualquer ação do widget recebe 403, e nada muda: nem a conversa
 * da Ana, nem a contagem de conversas e mensagens.
 *
 * `abort_unless($user instanceof User, 403)` é `Symfony\...\HttpException`, que o harness de
 * teste do Livewire deixa passar pelo exception handler normal
 * (`vendor/livewire/livewire/src/Features/SupportTesting/RequestBroker.php:29`) — por isso
 * `assertForbidden()` funciona direto, sem `try`/`catch` (mesmo padrão de
 * `Livewire::test(ConfiguracoesDoKit::class)->assertForbidden()` em `ConfiguracoesDoKitTelaTest.php`).
 *
 * A última linha é o discriminante: "Rascunho órfão" tem dono NULO, e um tratamento de nulo por
 * nullsafe na conferência de posse (`$user?->getMorphClass()`) viraria `whereNull` e abriria a
 * conversa órfã ao visitante — o oposto de "recusa fechado".
 *
 * **`responder` entrou pelo RQ-10 (Adendo 1)**, revertendo um corte anterior: é a ação cuja
 * autorização mais mudou (`assertContexto($this->conversaId)` logo na primeira linha do método).
 * O visitante define `mensagemPendente` por `set()` — simulando a chamada
 * `$wire.responder()` que o `enviar()` dispara no browser — e chama `responder()` direto.
 * `Assistente::fake([...])` está aqui por segurança: SEM ele, um guarda ausente (M32a) não
 * pararia em 403 — tentaria de fato falar com o provedor de IA antes de gravar a conversa que
 * este cenário conta, e o teste morreria por infraestrutura, não pela asserção.
 */
it('[CT-16] o visitante que chama uma ação do widget recebe 403 e nada muda', function (string $metodo, string $alvo, array $argsExtras): void {
    Assistente::fake(['nunca deveria responder ao visitante']);

    $ana           = usuarioDoKit('panel_user', 'ana@example.com');
    $conversaDaAna = conversaPara($ana, 'Plano de férias');
    $orfa          = conversaPara(null, 'Rascunho órfão');

    $totalConversasAntes = Conversation::count();
    $totalMensagensAntes = ConversationMessage::count();

    $args = match ($alvo) {
        'ana'    => [$conversaDaAna->id, ...$argsExtras],
        'orfa'   => [$orfa->id, ...$argsExtras],
        'nenhum' => $argsExtras,
    };

    $teste = match ($metodo) {
        'enviar'    => Livewire::test(AssistenteChatWidget::class)->set('mensagem', 'oi'),
        'responder' => Livewire::test(AssistenteChatWidget::class)->set('mensagemPendente', 'oi'),
        default     => Livewire::test(AssistenteChatWidget::class),
    };

    $teste->call($metodo, ...$args)->assertForbidden();

    expect($conversaDaAna->fresh()?->title)->toBe('Plano de férias')
        ->and($orfa->fresh()?->title)->toBe('Rascunho órfão')
        ->and(Conversation::count())->toBe($totalConversasAntes)
        ->and(ConversationMessage::count())->toBe($totalMensagensAntes);
})->with([
    'enviar (com mensagem "oi") — sem id'                                                => ['enviar', 'nenhum', []],
    'renomearConversa (id da Ana, "Invadido") — id de terceiro: 403, não 404'            => ['renomearConversa', 'ana', ['Invadido']],
    'retomarConversa (id da Ana) — id de terceiro: 403, não 404'                         => ['retomarConversa', 'ana', []],
    'novaConversa — sem id'                                                              => ['novaConversa', 'nenhum', []],
    'renomearConversa (id do "Rascunho órfão") — dono nulo, nullsafe abriria'            => ['renomearConversa', 'orfa', ['Invadido']],
    'responder (mensagemPendente = "oi" por set) — a autorização que mais mudou (RQ-10)' => ['responder', 'nenhum', []],
]);

/**
 * CT-22 — CARACTERIZAÇÃO: o 404 para o autenticado que não é dono é o comportamento da `main`.
 *
 * `git diff main -- app/Livewire/AssistenteChatWidget.php` TEM diferença (esta entrega altera o
 * arquivo: 24 inserções e 15 remoções, `git diff --stat`). A afirmação de "sem diferença nenhuma"
 * era falsa e foi substituída pela medição de verdade: o arquivo na `main`
 * (`git show main:app/Livewire/AssistenteChatWidget.php`) foi colocado na árvore de trabalho por
 * cima do da branch, só este caso (`--filter="CT-22"`) rodou sozinho contra ele, e voltou VERDE —
 * "passed, tests: 2, assertions: 6". O 404 para `bruno` na conversa da Ana já é o comportamento
 * da `main`; o que esta entrega mudou no arquivo é o tratamento de nulo em outro lugar (R7 nas
 * demais ações), não a asserção de posse que este cenário mede. Em seguida o arquivo foi
 * restaurado ao commit da branch (`git checkout HEAD -- app/Livewire/AssistenteChatWidget.php`),
 * conferido por `md5sum` antes/depois e por `git status --short` vazio para o arquivo.
 *
 * Fecha o par com CT-16: visitante → 403, autenticado alheio → 404. Um tratamento de nulo que
 * unificasse os dois (M31b) ou que comparasse só `participant_type`/só `auth()->check()` (M31a)
 * mudaria um dos lados.
 */
it('[CT-22] um usuário autenticado que não é dono recebe 404 na conversa alheia e nada muda', function (string $metodo): void {
    $ana           = usuarioDoKit('panel_user', 'ana@example.com');
    $bruno         = usuarioDoKit('infra', 'bruno@example.com');
    $conversaDaAna = conversaPara($ana, 'Plano de férias');

    $this->actingAs($bruno);

    $args = $metodo === 'renomearConversa' ? [$conversaDaAna->id, 'Invadido'] : [$conversaDaAna->id];

    Livewire::test(AssistenteChatWidget::class)
        ->call($metodo, ...$args)
        ->assertNotFound()
        ->assertSet('conversaId', null);

    expect($conversaDaAna->fresh()?->title)->toBe('Plano de férias');
})->with([
    'renomearConversa ("Invadido") — escrita'  => ['renomearConversa'],
    'retomarConversa — leitura'                => ['retomarConversa'],
]);

<?php

use App\Ai\Agents\Assistente;
use App\Ai\Agents\GuardaPrompt;
use App\Livewire\AssistenteChatWidget;
use App\Models\User;
use Database\Seeders\AssistenteSeeder;
use Database\Seeders\GuardaPromptSeeder;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Models\ConversationMessage;
use Livewire\Livewire;
use Psr\Log\LoggerInterface;

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

    /*
     * Só CT-24/CT-25 chegam a este ponto (pergunta pendente válida, autenticado): o paper do
     * `Assistente` no catálogo `agentes_ia` e do classificador de guardrail no `GuardaPrompt`,
     * sem os quais `AgenteBase::agente()`/`middleware()` estouram `RuntimeException` antes do
     * guard de `responder()` rodar — achado igual ao de `tests/Kit/GuardrailsDtoTest.php`. Nas
     * demais linhas deste arquivo (403/404 antes do stream), as duas seeders são inertes.
     */
    $this->seed([AssistenteSeeder::class, GuardaPromptSeeder::class]);
    config(['ai.providers.llamacpp.models.text.default' => 'modelo-de-teste']);
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

/**
 * Regra R10 da wiki `phpstan-nivel-8` (origem: quality gate ciclo 1, QA-04): o `responder()`
 * autenticado só consulta o agente com `mensagemPendente` não nula e de até 2000 caracteres —
 * o mesmo teto de `#[Validate('required|string|max:2000', ...)]` do campo `mensagem`. É
 * caracterização da `main`, não número novo do `00`.
 *
 * `Assistente::fake(['ok'])` registra o prompt mesmo em streaming: `stream()` passa pelo MESMO
 * `gatherMiddlewareFor()` de `prompt()` (`vendor/laravel/ai/src/Providers/Concerns/GeneratesText.php:142`,
 * também usado por `StreamsText.php:35`), que grava em `Ai::recordPrompt()` quando o agente está
 * faked — por isso `assertPrompted()`/`assertNeverPrompted()` são oráculo válido aqui, ao
 * contrário do que a nota do `04` cogitava como fallback.
 *
 * `GuardaPrompt::fake([...])` libera o guardrail `prompt_guard_local` (2ª camada, classificador
 * próprio) com veredito seguro — sem ele o teste chamaria de verdade um segundo agente antes de
 * chegar ao `Assistente` (mesmo padrão de `tests/Kit/GuardrailsDtoTest.php::[CT-22]`).
 */
it('[CT-24] o responder da Ana consulta o agente só com pergunta pendente de até 2000 caracteres', function (?string $pendente, string $chamado, string $efeito): void {
    GuardaPrompt::fake([['seguro' => true, 'categoria' => 'legitima', 'motivo' => 'pergunta comum']]);
    Assistente::fake(['ok']);

    $ana = usuarioDoKit('panel_user', 'ana@example.com');
    $this->actingAs($ana);

    $totalConversasAntes = Conversation::count();
    $totalMensagensAntes = ConversationMessage::count();

    Livewire::test(AssistenteChatWidget::class)
        ->set('mensagemPendente', $pendente)
        ->call('responder');

    $chamado === 'é consultado'
        ? Assistente::assertPrompted($pendente)
        : Assistente::assertNeverPrompted();

    if ($efeito === 'cresce') {
        expect(Conversation::count())->toBeGreaterThan($totalConversasAntes)
            ->and(ConversationMessage::count())->toBeGreaterThan($totalMensagensAntes);
    } else {
        expect(Conversation::count())->toBe($totalConversasAntes)
            ->and(ConversationMessage::count())->toBe($totalMensagensAntes);
    }
})->with([
    'partição nula'                  => [null, 'não é consultado', 'é o mesmo de antes'],
    'borda−1 (1999 caracteres "a")'  => [str_repeat('a', 1999), 'é consultado', 'cresce'],
    'borda (2000 caracteres "a")'    => [str_repeat('a', 2000), 'é consultado', 'cresce'],
    'borda+1 (2001 caracteres "a")'  => [str_repeat('a', 2001), 'não é consultado', 'é o mesmo de antes'],
]);

/**
 * CT-25 — pergunta válida grava a conversa da Ana (participante, não órfã) e limpa a bolha
 * pendente. `conversaId` é `#[Locked]` só contra escrita vinda do browser (CT-16); aqui é o
 * próprio componente quem o define ao final do streaming (`AssistenteChatWidget::responder()`,
 * `$this->conversaId = $resposta->conversationId`).
 */
it('[CT-25] o responder da Ana com pergunta válida grava a conversa dela e limpa a pendência', function (): void {
    GuardaPrompt::fake([['seguro' => true, 'categoria' => 'legitima', 'motivo' => 'pergunta comum']]);
    Assistente::fake(['Resposta fixa do teste']);

    $ana = usuarioDoKit('panel_user', 'ana@example.com');
    $this->actingAs($ana);

    $componente = Livewire::test(AssistenteChatWidget::class)
        ->set('mensagemPendente', 'Qual é o prazo?')
        ->call('responder');

    expect(Conversation::count())->toBe(1);

    $conversa = Conversation::sole();

    expect($conversa->participant_type)->toBe($ana->getMorphClass())
        ->and($conversa->participant_id)->toBe($ana->getKey());

    $componente
        ->assertSet('conversaId', $conversa->id)
        ->assertSet('mensagemPendente', null);
});

/**
 * CT-26 — a negação de posse (já provada pelo 404 do CT-22) grava a trilha de auditoria no
 * canal `ai`. A skill proíbe CT de log; a exceção declarada em R10 é exatamente esta: o log É
 * a trilha de uma negação de acesso, não um detalhe de formatação — 7 mutantes sobreviventes
 * publicados pelo gate (M48…M53) miram este warning.
 *
 * `Log::partialMock()->shouldReceive('channel')->with('ai')` (padrão de
 * `tests/Kit/GuardrailsDtoTest.php::[CT-31]`) troca só o canal nomeado por um espião; os
 * demais canais continuam reais.
 */
it('[CT-26] negar a conversa alheia responde 404 e grava a trilha da negação no canal de IA', function (): void {
    $ana   = usuarioDoKit('panel_user', 'ana@example.com');
    $bruno = usuarioDoKit('infra', 'bruno@example.com');

    $conversaDaAna = conversaPara($ana, 'Plano de férias');

    $canal = Mockery::spy(LoggerInterface::class);
    Log::partialMock()->shouldReceive('channel')->with('ai')->andReturn($canal);

    $this->actingAs($bruno);

    Livewire::test(AssistenteChatWidget::class)
        ->call('retomarConversa', $conversaDaAna->id)
        ->assertNotFound();

    $canal->shouldHaveReceived('warning')
        ->withArgs(function (string $mensagem, array $contexto) use ($conversaDaAna, $bruno): bool {
            return str_starts_with($mensagem, '[AssistenteChatWidget@assertContexto] Acesso negado a conversa')
                && ($contexto['motivo'] ?? null) === 'posse_invalida'
                && ($contexto['conversa_id'] ?? null) === $conversaDaAna->id
                && ($contexto['user_id'] ?? null) === $bruno->id;
        })
        ->once();
});

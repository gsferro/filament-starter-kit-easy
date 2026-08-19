<?php

namespace App\Livewire;

use App\Ai\Agents\Assistente;
use App\Ai\Exceptions\PromptInjecaoBloqueadaException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Models\ConversationMessage;
use Laravel\Ai\Streaming\Events\TextDelta;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Throwable;

/**
 * Widget de chat do `Assistente`. Superfície de UI embarcada em todas as telas do painel via
 * render hook (`PanelsRenderHook::BODY_END`, registrado no PanelProvider). Não cria agente
 * nem lógica de IA: consome o contrato de `App\Ai\Agents\Assistente`, que já traz streaming,
 * guardrails, auditoria e persistência de conversa.
 *
 * DUAS FASES (two-phase): `enviar()` valida e mostra a bolha otimista com re-render imediato;
 * `responder()` — disparado pelo browser via `$this->js()` — é quem faz o streaming. Fazer
 * tudo em uma ação só congelaria a tela até o primeiro token, que numa inferência local pode
 * demorar dezenas de segundos.
 *
 * SEM tenancy: o kit não tem entidade/tenant. A posse da conversa é verificada pelo
 * participante (usuário autenticado) — `agent_conversations` é tabela do vendor e escopa
 * exatamente por isso.
 */
class AssistenteChatWidget extends Component
{
    public bool $aberto = false;

    /**
     * `message` sobrescreve o texto padrão do Laravel ("indicação de um valor para o campo
     * mensagem"): aqui o campo é uma pergunta, e "valor" faz o usuário pensar em número.
     */
    #[Validate('required|string|max:2000', message: [
        'mensagem.required' => 'Digite sua pergunta antes de enviar.',
        'mensagem.max'      => 'A pergunta deve ter no máximo 2000 caracteres.',
    ])]
    public string $mensagem = '';

    /** Bolha otimista do two-phase: preenchida no enviar(), consumida no responder(). */
    public ?string $mensagemPendente = null;

    #[Locked]
    public ?string $conversaId = null;

    public bool $indisponivel = false;

    /**
     * Recusa por guardrail. Separada de `$indisponivel`: bloqueio de segurança não é falha de
     * infraestrutura e não convida ao reenvio. Texto sempre fixo da aplicação (constante da
     * exceção), nunca gerado pelo modelo.
     */
    public ?string $recusa = null;

    /**
     * Fase 1: valida, mostra a bolha otimista (re-render imediato com o input limpo) e agenda
     * a fase 2 no browser. O streaming NÃO acontece aqui.
     */
    public function enviar(): void
    {
        $this->assertContexto($this->conversaId);
        $this->validate();

        $this->indisponivel     = false;
        $this->recusa           = null;
        $this->mensagemPendente = $this->mensagem;
        $this->mensagem         = '';

        Log::channel('ai')->debug(
            '[AssistenteChatWidget@enviar] Mensagem aceita para envio | conversa: '.($this->conversaId ?? 'nova'),
            ['user_id' => auth()->id(), 'conversa_id' => $this->conversaId, 'tamanho_mensagem' => mb_strlen($this->mensagemPendente)],
        );

        $this->js('$wire.responder()');
    }

    /**
     * Fase 2: streaming da resposta. Chamada pelo browser via `$this->js()` — no-op idempotente
     * sem mensagem pendente (guarda contra chamada direta, já que a action é pública).
     */
    public function responder(): void
    {
        // Revalidação defensiva: `mensagemPendente` não é #[Locked] e a action é pública.
        if ($this->mensagemPendente === null || mb_strlen($this->mensagemPendente) > 2000) {
            return;
        }

        $this->assertContexto($this->conversaId);

        $user    = auth()->user();
        $eraNova = $this->conversaId === null;

        try {
            $agente = new Assistente($user);
            $agente = $eraNova
                ? $agente->forUser($user)
                : $agente->continue($this->conversaId, as: $user);

            // O stream nativo do agente é a via canônica: o listener RegistrarAiRun
            // (AgentStreamed) grava a execução em `ai_runs`. A facade AI::stream do
            // fomvasss/laravel-ai-tasks NÃO é usada de propósito — ela monta um
            // AnonymousAgent e perderia guardrails, tools e conversas do SDK.
            $resposta = $agente->stream($this->mensagemPendente);

            $chunks = 0;
            $inicio = hrtime(true);

            foreach ($resposta as $evento) {
                if ($evento instanceof TextDelta) {
                    $this->stream(content: $evento->delta, name: 'resposta-parcial');
                    $chunks++;
                }
            }

            $this->conversaId       = $resposta->conversationId;
            $this->mensagemPendente = null;

            Log::channel('ai')->info(
                '[AssistenteChatWidget@responder] Resposta streamada com sucesso | conversa: '.$this->conversaId,
                ['user_id' => $user->id, 'conversa_id' => $this->conversaId, 'chunks' => $chunks, 'latencia_ms' => intdiv(hrtime(true) - $inicio, 1_000_000)],
            );
        } catch (PromptInjecaoBloqueadaException $e) {
            // Nível 1 de erro — RECUSA por guardrail: causa honesta, `indisponivel` continua
            // false e o texto NÃO volta ao input (não treinar o usuário a reenviar o abuso).
            $this->recusa           = $e->getMessage();
            $this->mensagemPendente = null;

            Log::channel('ai')->warning(
                '[AssistenteChatWidget@responder] Mensagem recusada por guardrail | conversa: '.($this->conversaId ?? 'nova'),
                [
                    'user_id'     => $user->id,
                    'conversa_id' => $this->conversaId,
                    'motivo'      => class_basename($e),
                ],
            );
        } catch (Throwable $e) {
            // Nível 2 — INDISPONIBILIDADE honesta: nunca resposta inventada. As mensagens já
            // persistidas permanecem (o re-render lê do banco) e o texto volta ao input para
            // reenvio. Cobre agente inativo, provider fora do ar, budget estourado e afins.
            $this->indisponivel     = true;
            $this->mensagem         = $this->mensagemPendente;
            $this->mensagemPendente = null;

            Log::channel('ai')->error(
                '[AssistenteChatWidget@responder] Falha no streaming do assistente | conversa: '.($this->conversaId ?? 'nova'),
                ['exception' => $e, 'user_id' => $user->id, 'conversa_id' => $this->conversaId],
            );
        }
    }

    /**
     * Renomeia conversa do participante atual. Título vazio é no-op; o log nunca carrega o
     * conteúdo do título.
     */
    public function renomearConversa(string $id, string $titulo): void
    {
        $this->assertContexto($id);

        $titulo = trim($titulo);

        if ($titulo === '') {
            return;
        }

        $titulo = Str::limit($titulo, 100, '');

        Conversation::query()->whereKey($id)->update(['title' => $titulo]);

        Log::channel('ai')->info(
            '[AssistenteChatWidget@renomearConversa] Conversa renomeada | conversa: '.$id,
            ['user_id' => auth()->id(), 'conversa_id' => $id, 'tamanho_titulo' => mb_strlen($titulo)],
        );
    }

    public function novaConversa(): void
    {
        $this->assertContexto();

        $this->conversaId       = null;
        $this->mensagem         = '';
        $this->mensagemPendente = null;
        $this->indisponivel     = false;
        $this->recusa           = null;
    }

    public function retomarConversa(string $id): void
    {
        $this->assertContexto($id);

        $this->conversaId       = $id;
        $this->mensagemPendente = null;
        $this->indisponivel     = false;
        $this->recusa           = null;

        Log::channel('ai')->debug(
            '[AssistenteChatWidget@retomarConversa] Conversa retomada | conversa: '.$id,
            ['user_id' => auth()->id(), 'conversa_id' => $id],
        );
    }

    /**
     * Ponto ÚNICO de autorização das actions: usuário autenticado e, quando `$conversaId` é
     * informado, conversa pertencente ao participante atual (senão 404 + log de warning).
     * Toda action pública passa por aqui — id de conversa vem do browser.
     */
    private function assertContexto(?string $conversaId = null): void
    {
        abort_unless(auth()->check(), 403);

        if ($conversaId === null) {
            return;
        }

        $user = auth()->user();

        $pertence = Conversation::query()
            ->whereKey($conversaId)
            ->where('participant_type', $user->getMorphClass())
            ->where('participant_id', $user->getKey())
            ->exists();

        if (! $pertence) {
            Log::channel('ai')->warning(
                '[AssistenteChatWidget@assertContexto] Acesso negado a conversa | conversa: '.$conversaId,
                ['user_id' => $user->id, 'conversa_id' => $conversaId, 'motivo' => 'posse_invalida'],
            );

            abort(404);
        }
    }

    /**
     * Conversas do participante atual.
     *
     * @return Collection<int, Conversation>
     */
    private function historico(): Collection
    {
        if (! auth()->check()) {
            return collect();
        }

        return Conversation::query()
            ->where('participant_type', auth()->user()->getMorphClass())
            ->where('participant_id', auth()->id())
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'updated_at']);
    }

    /**
     * Mensagens da conversa atual — a fonte de verdade é o banco (`RemembersConversations`),
     * sem estado duplicado no componente.
     *
     * @return Collection<int, ConversationMessage>
     */
    private function mensagens(): Collection
    {
        if ($this->conversaId === null) {
            return collect();
        }

        return ConversationMessage::query()
            ->where('conversation_id', $this->conversaId)
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('id')
            // `created_at` alimenta o hint de horário da bolha: a hora vem do banco, sem
            // estado novo no componente.
            ->get(['id', 'role', 'content', 'created_at']);
    }

    public function render(): View
    {
        return view('livewire.assistente-chat-widget', [
            // O render hook roda até na tela de login do painel: sem usuário, o widget
            // renderiza vazio em vez de abortar.
            'disponivel' => auth()->check(),
            'historico'  => $this->historico(),
            'mensagens'  => $this->mensagens(),
        ]);
    }
}

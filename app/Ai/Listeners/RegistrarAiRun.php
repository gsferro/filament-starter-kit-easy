<?php

namespace App\Ai\Listeners;

use App\Ai\Agents\AgenteBase;
use App\Ai\Support\ResolvedorDeTenant;
use Fomvasss\AiTasks\Models\AiRun;
use Fomvasss\AiTasks\Support\Cost;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\AgentStreamed;
use Throwable;

/**
 * Ledger de execuções de agentes. O fomvasss/laravel-ai-tasks só grava runs de tasks
 * roteadas pela própria facade `AI::send/stream/queue` (que monta um AnonymousAgent, sem os
 * guardrails, conversas e fakes dos agentes do kit). Este listener alimenta a MESMA tabela
 * `ai_runs` a partir dos eventos nativos do laravel/ai — `AgentPrompted` (fim do `prompt()`)
 * e `AgentStreamed` (fim do `stream()`) — para que o dashboard `/ai-tasks`, o resource
 * "Execuções de IA" e o guard de budget enxerguem toda execução, sem alterar o caminho dos
 * agentes.
 *
 * Registrado em `App\Providers\KitServiceProvider::configureAiLedger()`.
 *
 * Falha aqui NUNCA derruba a execução do agente: ledger é observabilidade, não regra.
 */
final class RegistrarAiRun
{
    public function handle(AgentPrompted $event): void
    {
        try {
            $agente = $event->prompt->agent;
            $driver = $event->prompt->provider()->name();
            $usage  = $event->response->usage;

            $tokens = [
                'tokens_in'          => $usage->promptTokens ?: null,
                'tokens_out'         => $usage->completionTokens ?: null,
                'cache_read_tokens'  => $usage->cacheReadInputTokens ?: null,
                'cache_write_tokens' => $usage->cacheWriteInputTokens ?: null,
            ];

            AiRun::create([
                // Organização corrente do painel, ou o tenant default do pacote fora de um
                // request de painel — `ai_runs.tenant_id` é NOT NULL, então "sem tenant" é um
                // valor, não null. Mesma chave usada pelo BudgetGuard.
                'tenant_id'    => app(ResolvedorDeTenant::class)->id(),
                'task'         => $agente instanceof AgenteBase ? $agente->slug() : Str::snake(class_basename($agente)),
                'driver'       => $driver,
                // Coluna dedicada (migration `add_model_to_ai_runs_table`): é o que o filtro e
                // a busca do resource usam — o mesmo valor aparece em `request.options.model`
                // só para exibição.
                'model'        => $event->prompt->model,
                'modality'     => 'text',
                'subject_type' => ($agente->user ?? null)?->getMorphClass(),
                'subject_id'   => ($agente->user ?? null)?->getKey(),
                'dispatch'     => 'sync',
                'status'       => 'ok',
                'request'      => [
                    'modality' => 'text',
                    'options'  => ['model' => $event->prompt->model],
                    'meta'     => [
                        'invocation_id'   => $event->invocationId,
                        'conversation_id' => $event->response->conversationId,
                        'streamed'        => $event instanceof AgentStreamed,
                    ],
                ],
                // Conteúdo (prompt/resposta) só é persistido com opt-in explícito — mesma
                // semântica do flag `store_request` do pacote.
                'response'    => config('ai-tasks.store_request') ? ['content' => $event->response->text] : null,
                ...$tokens,
                'cost'        => Cost::calc($driver, $tokens, config("ai-tasks.drivers.{$driver}", [])),
                'started_at'  => now(),
                'finished_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::channel('ai')->error(
                '[RegistrarAiRun@handle] Falha ao gravar ai_run, execução do agente não afetada | agente: '.$event->prompt->agent::class,
                ['exception' => $e->getMessage()],
            );
        }
    }
}

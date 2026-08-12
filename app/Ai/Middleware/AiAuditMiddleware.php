<?php

namespace App\Ai\Middleware;

use App\Ai\Agents\AgenteBase;
use Closure;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Prompts\AgentPrompt;
use Throwable;

/**
 * Auditoria de execução de agentes no canal de log `ai`. Vai por ÚLTIMO no pipeline
 * (`middleware()` do agente devolve `[...guardrails, AiAuditMiddleware]`), então o prompt
 * que chega aqui já passou pelo redator de PII.
 *
 * O TEXTO do prompt nunca é logado em claro: só metadados (tamanho, tokens, latência,
 * provider/modelo). Se o seu projeto precisar do conteúdo para depuração, ligue-o num canal
 * separado e com retenção curta — não aqui.
 *
 * O log `error` (falha de provider) e os `warning` dos guardrails são os insumos estáveis
 * para quem for plugar registro de incidentes.
 */
final class AiAuditMiddleware
{
    public function handle(AgentPrompt $prompt, Closure $next): mixed
    {
        $agente = $prompt->agent;
        $slug   = $agente instanceof AgenteBase ? $agente->slug() : $agente::class;
        $base   = $this->contextoBase($agente, $slug);

        Log::channel('ai')->debug(
            "[AiAuditMiddleware@handle] Execução de agente iniciada | agente: {$slug}",
            [...$base, 'tamanho_prompt' => mb_strlen($prompt->prompt)],
        );

        $inicio = microtime(true);

        try {
            $resposta = $next($prompt);
        } catch (Throwable $e) {
            Log::channel('ai')->error(
                "[AiAuditMiddleware@handle] Falha na execução do agente | agente: {$slug}",
                [...$base, 'exception' => $e->getMessage()],
            );

            throw $e;
        }

        $latenciaMs = (int) round((microtime(true) - $inicio) * 1000);

        Log::channel('ai')->info(
            "[AiAuditMiddleware@handle] Execução de agente concluída | agente: {$slug} - latencia_ms: {$latenciaMs}",
            [
                ...$base,
                // `?? null` interno: em stream o retorno é StreamableAgentResponse, que NÃO
                // tem $toolCalls (laravel/ai 0.10) — acesso direto viraria ErrorException.
                'tools_chamadas'   => ($resposta->toolCalls ?? null)?->map(fn ($call) => $call->name)->all() ?? [],
                'tokens_prompt'    => $resposta->usage->promptTokens ?? null,
                'tokens_resposta'  => $resposta->usage->completionTokens ?? null,
                'tamanho_resposta' => isset($resposta->text) ? mb_strlen((string) $resposta->text) : null,
                'latencia_ms'      => $latenciaMs,
            ],
        );

        return $resposta;
    }

    /**
     * Contexto comum a todos os logs (metadados do agente + provider/modelo).
     *
     * @return array<string, mixed>
     */
    private function contextoBase(object $agente, string $slug): array
    {
        return [
            'agente'       => $slug,
            'versao_paper' => $agente instanceof AgenteBase ? $agente->agente()->versao : null,
            'user_id'      => $agente->user->id ?? null,
            'provider'     => $agente instanceof AgenteBase ? $agente->provider() : null,
            'modelo'       => $agente instanceof AgenteBase ? $agente->model() : null,
        ];
    }
}

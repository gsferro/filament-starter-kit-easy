<?php

namespace App\Ai\Guardrails;

use Closure;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Prompts\AgentPrompt;

/**
 * Guardrail `pii_redactor` — 3ª camada: redige PII do prompt ANTES do provider. Nunca
 * bloqueia; apenas sanitiza e loga (o context do log jamais carrega o dado em claro).
 *
 * `AgentPrompt::revise()` devolve uma NOVA instância (o prompt é imutável no SDK), então o
 * retorno precisa ser reatribuído antes de seguir o pipeline.
 */
final class PiiRedactorMiddleware
{
    public function __construct(private PiiRedactor $redactor) {}

    public function handle(AgentPrompt $prompt, Closure $next): mixed
    {
        $original = $prompt->prompt;
        $redigido = $this->redactor->redigir($original);

        if ($redigido !== $original) {
            Log::channel('ai')->warning(
                '[PiiRedactorMiddleware@handle] PII redigido no prompt | agente: '.$prompt->agent::class,
                ['agente' => $prompt->agent::class, 'tipos' => $this->redactor->tiposDetectados($original)],
            );

            $prompt = $prompt->revise($redigido);
        }

        return $next($prompt);
    }
}

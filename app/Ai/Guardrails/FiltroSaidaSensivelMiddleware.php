<?php

namespace App\Ai\Guardrails;

use App\Ai\Agents\AgenteBase;
use Closure;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;

/**
 * Guardrail `filtro_saida_sensivel` — 4ª camada: rede de segurança na SAÍDA. Roda no
 * `->then()` do prompt middleware (hook nativo do SDK) e redige segredo que tenha escapado.
 *
 * Motivo concreto: em tentativa real, um agente recusou entregar as chaves de configuração
 * e, ainda assim, devolveu um trecho de código com `db_password` e `api_key` "de exemplo".
 * Recusar e vazar no mesmo turno é exatamente o que esta camada cobre.
 *
 * NÃO reusa o `PiiRedactor` de propósito: aquele mascara e-mail/telefone/CPF/CNPJ/IP, que na
 * SAÍDA costumam ser dado de negócio legítimo que o usuário tem direito de ver (o escopo já
 * foi limitado na query das tools). Aqui a lista é estreita: só o que nunca deve sair,
 * independentemente de perfil.
 *
 * ATENÇÃO ao streaming: com `stream()` o `then()` roda ao FIM do stream — os deltas já foram
 * enviados ao browser, então nesse caminho a camada DETECTA (log) mas não impede a exibição.
 * Prevenção real exigiria bufferizar a resposta inteira, matando o streaming do widget. Em
 * chamadas não-streamadas (`prompt()`, jobs) o texto é efetivamente redigido.
 */
final class FiltroSaidaSensivelMiddleware
{
    /** @var array<string, string> tipo => regex */
    private const PADROES = [
        'chave_api'    => '/\b(?:sk|pk|ghp|xox[baprs])[-_][A-Za-z0-9]{10,}\b/',
        'bearer'       => '/Bearer\s+[A-Za-z0-9._\-]{10,}/i',
        'cartao'       => '/\b\d{4}[ -]?\d{4}[ -]?\d{4}[ -]?\d{4}\b/',
        'credencial'   => '/\b(?:DB_PASSWORD|DB_USERNAME|APP_KEY|[A-Z_]*API_KEY|[A-Z_]*SECRET)\b\s*[:=]\s*\S+/',
        'senha_inline' => '/\b(?:senha|password|passwd)\b\s*[:=]\s*[\'"]?\S{4,}/i',
    ];

    public function handle(AgentPrompt $prompt, Closure $next): mixed
    {
        return $next($prompt)->then(function (AgentResponse $response) use ($prompt): void {
            [$texto, $tipos] = $this->redigir($response->text);

            if ($tipos === []) {
                return;
            }

            $response->text = $texto;

            $agente = $prompt->agent;

            Log::channel('ai')->critical(
                '[FiltroSaidaSensivelMiddleware@handle] Conteúdo sensível na resposta do agente | tipos: '.implode(',', $tipos),
                [
                    'agente' => $agente::class,
                    'slug'   => $agente instanceof AgenteBase ? $agente->slug() : null,
                    // Só os TIPOS detectados — jamais o segredo em si.
                    'tipos'   => $tipos,
                    'acao'    => 'redact',
                    'user_id' => $agente->user->id ?? null,
                ],
            );
        });
    }

    /**
     * Texto redigido + tipos encontrados, numa passada só (o `$count` do `preg_replace` já
     * diz se o padrão casou — varrer duas vezes seria trabalho dobrado).
     *
     * @return array{0: string, 1: list<string>}
     */
    private function redigir(string $texto): array
    {
        $tipos = [];

        foreach (self::PADROES as $tipo => $padrao) {
            $texto = preg_replace($padrao, "[REDIGIDO:{$tipo}]", $texto, -1, $casou) ?? $texto;

            if ($casou > 0) {
                $tipos[] = $tipo;
            }
        }

        return [$texto, $tipos];
    }
}

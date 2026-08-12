<?php

namespace App\Ai\Health;

use Illuminate\Support\Facades\Log;
use Spatie\Health\Checks\Checks\PingCheck;
use Spatie\Health\Checks\Result;

/**
 * Healthcheck dos endpoints de IA LOCAL (llama.cpp / ollama) na página de Health do painel
 * infra.
 *
 * Extensão fina do `PingCheck` do vendor (HTTP + timeout/retry prontos): só acrescenta a
 * factory condicional por provider ativo e o log `warning` no canal `ai`.
 */
final class LocalAiCheck extends PingCheck
{
    /**
     * Checks a registrar conforme o provider ativo. Provider não-local (ou `null`) ⇒ nenhum
     * check: com `AI_PROVIDER=openai` a página de health continua intacta, sem falha falsa.
     *
     * @return list<self>
     */
    public static function checksFor(?string $provider): array
    {
        return match ($provider) {
            'llamacpp' => [
                self::endpoint('ia-local-chat', self::base('ai.providers.llamacpp.url').'/health'),
                self::endpoint('ia-local-embeddings', self::base('ai.providers.llamacpp-embed.url').'/health'),
            ],
            'ollama' => [
                self::endpoint('ia-local-chat', self::base('ai.providers.ollama.url').'/api/version'),
            ],
            default => [],
        };
    }

    protected static function endpoint(string $nome, string $url): self
    {
        // O timeout default do vendor (1s) é curto para container em cold start; 3s não trava
        // a página. Os setters fluentes do PingCheck retornam PingCheck (não self), então a
        // configuração é feita sem encadear o retorno.
        $check = self::new();
        $check->name($nome)->url($url)->timeout(3)->failureMessage("Endpoint local de IA inacessível: {$url}");

        return $check;
    }

    /**
     * Base do provider sem o sufixo `/v1` — o `/health` (llama.cpp) e o `/api/version`
     * (ollama) vivem fora do `/v1`.
     */
    protected static function base(string $configKey): string
    {
        return (string) preg_replace('#/v1/?$#', '', (string) config($configKey));
    }

    /**
     * Falha (não-2xx ou exceção) — loga `warning` no canal `ai`. Sucesso é silencioso: o
     * próprio Health já persiste o resultado.
     */
    protected function failedResult(): Result
    {
        Log::channel('ai')->warning(
            "[LocalAiCheck@failedResult] Endpoint local de IA indisponível | check: {$this->getName()}",
            ['check' => $this->getName(), 'url' => $this->url],
        );

        return parent::failedResult();
    }
}

<?php

namespace App\Ai\Guardrails;

use InvalidArgumentException;

/**
 * Mapa chave-do-catálogo → classe do guardrail (prompt middleware do agente). É o contrato
 * estável entre o DADO (`agentes_ia.guardrails`) e o CÓDIGO: o paper declara nomes, o
 * registry resolve classes.
 *
 * Para adicionar um guardrail próprio: crie o middleware (método
 * `handle(AgentPrompt $prompt, Closure $next)`) e registre a chave aqui.
 */
final class GuardrailRegistry
{
    /**
     * Ordem típica no paper: determinísticos (de graça) → classificador local (1 inferência)
     * → PII no que sai da aplicação → filtro da resposta.
     *
     * @var array<string, class-string>
     */
    public const MAPA = [
        'prompt_injection'      => PromptInjectionGuardMiddleware::class,
        'prompt_guard_local'    => GarantirPromptSeguroMiddleware::class,
        'pii_redactor'          => PiiRedactorMiddleware::class,
        'filtro_saida_sensivel' => FiltroSaidaSensivelMiddleware::class,
    ];

    /**
     * Resolve nomes de guardrails em instâncias de middleware (via container, para que as
     * dependências sejam injetadas). Nome desconhecido → exceção: nunca silencia um
     * guardrail declarado que não existe (typo no paper viraria agente desprotegido).
     *
     * @param  list<string>  $nomes
     * @return list<object>
     */
    public static function resolver(array $nomes): array
    {
        return array_map(function (string $nome): object {
            $classe = self::MAPA[$nome] ?? throw new InvalidArgumentException("Guardrail desconhecido: {$nome}");

            return app($classe);
        }, $nomes);
    }
}

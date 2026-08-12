<?php

namespace App\Ai\Agents;

use App\Ai\Exceptions\AgenteSemGuardrailsException;
use App\Ai\Guardrails\GuardrailRegistry;
use App\Ai\Middleware\BudgetGuardMiddleware;
use App\Models\AgenteIa;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasMiddleware;
use Laravel\Ai\Promptable;
use RuntimeException;
use Stringable;

/**
 * Classe base dos agentes de IA do kit. Todo agente a estende e herda:
 *
 *  - as instruções e a configuração de runtime vindas do **catálogo** (`agentes_ia`,
 *    chave `slug`) — paper como dado, não como código;
 *  - o pipeline de guardrails (fail-closed: sem guardrail o agente não sobe);
 *  - a execução do Laravel AI SDK via `Promptable` (`prompt()`/`stream()`/`queue()`,
 *    além de `::fake()`/`assertPrompted()` nos testes).
 *
 * Contrato para as subclasses: implementar `slug()`. O resto vem do registro — a
 * subclasse sobrescreve só o que precisar (tools via `HasTools`, memória via
 * `Conversational`, schema via `HasStructuredOutput`, instruções customizadas).
 *
 * Regra de ouro: validação de regra de negócio NUNCA passa por um agente. Guardrails e
 * escopo de dados são determinísticos; o modelo redige, não decide.
 */
abstract class AgenteBase implements Agent, HasMiddleware
{
    use Promptable;

    private ?AgenteIa $registro = null;

    /** Slug do agente no catálogo `agentes_ia` (chave estável do registro). */
    abstract public function slug(): string;

    /**
     * Registro do catálogo (o "paper"). Memoizado por instância — um turno com tools faz
     * várias inferências e cada uma tocaria o banco de novo.
     * Ausente → exceção (fail-closed: agente sem registro não sobe).
     */
    public function agente(): AgenteIa
    {
        return $this->registro ??= AgenteIa::doSlug($this->slug())
            ?? throw new RuntimeException("Agente não cadastrado no catálogo agentes_ia: {$this->slug()}");
    }

    /** Instruções do agente (corpo do paper). Subclasse pode sobrescrever. */
    public function instructions(): Stringable|string
    {
        return (string) $this->agente()->instrucoes;
    }

    /**
     * Guardrails do agente resolvidos do catálogo, precedidos do guard de budget mensal
     * (pré-flight barato: no-op quando não há cap configurado).
     *
     * Lista vazia/null → exceção. É deliberado: um agente que sobe sem guardrail chega cru
     * ao provider, e o momento de descobrir isso é a primeira execução, não a auditoria.
     *
     * @return list<object>
     */
    public function middleware(): array
    {
        $guardrails = $this->agente()->guardrails ?? [];

        if ($guardrails === []) {
            throw new AgenteSemGuardrailsException(
                "Agente [{$this->slug()}] sem guardrails declarados — subida bloqueada."
            );
        }

        return [new BudgetGuardMiddleware, ...GuardrailRegistry::resolver($guardrails)];
    }

    /** Agente ativo no catálogo? Quem consome decide a degradação honesta. */
    public function estaAtivo(): bool
    {
        return (bool) $this->agente()->ativo;
    }

    /**
     * Provider do agente. O Laravel AI SDK chama este método automaticamente quando
     * `prompt()` não recebe provider explícito; `null` = default de config/ai.php
     * (env `AI_PROVIDER` define o disponível — nunca hardcode no paper).
     */
    public function provider(): ?string
    {
        return $this->agente()->provider;
    }

    /** Modelo do agente. O SDK o usa automaticamente; `null` = default do provider. */
    public function model(): ?string
    {
        return $this->agente()->modelo;
    }

    /**
     * Timeout da chamada ao provider, em segundos (o SDK resolve este método sozinho —
     * `Promptable::getTimeout()`). O default do SDK é 60s, insuficiente para inferência
     * LOCAL em CPU: um turno com tools faz uma inferência por passo do loop, e um modelo
     * 7B leva vários segundos até no passo mais simples. Ajuste por env, não por código.
     */
    public function timeout(): int
    {
        return (int) config('ai.agente_timeout');
    }
}

<?php

namespace App\Ai\Exceptions;

use RuntimeException;

/**
 * Agente sem guardrails declarados no catálogo.
 *
 * Fail-closed por contrato: um agente sem guardrail não sobe. Deixar a lista vazia
 * "temporariamente" é o caminho mais comum para um agente chegar cru ao provider —
 * por isso a ausência é erro, não default silencioso. A única exceção documentada é
 * o próprio classificador de segurança (`App\Ai\Agents\GuardaPrompt`).
 */
class AgenteSemGuardrailsException extends RuntimeException {}

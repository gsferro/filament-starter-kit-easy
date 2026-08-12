<?php

namespace App\Ai\Exceptions;

use RuntimeException;

/**
 * Prompt bloqueado por guardrail (injection, jailbreak, abuso ou fora de escopo).
 * Fail-closed: a execução do agente é interrompida ANTES de chegar ao provider.
 */
class PromptInjecaoBloqueadaException extends RuntimeException
{
    /**
     * Texto EXIBIDO ao usuário pelo widget de chat. Sem detalhe do guardrail que casou:
     * não se ensina ao atacante onde está a barreira. O rastro técnico vive no log `ai`.
     */
    public const MENSAGEM = 'Não posso atender esse pedido. Posso ajudar com dúvidas sobre '
        .'o uso da aplicação.';
}

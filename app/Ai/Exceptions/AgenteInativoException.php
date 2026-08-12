<?php

namespace App\Ai\Exceptions;

use RuntimeException;

/**
 * Agente marcado como inativo no catálogo (`agentes_ia.ativo = false`). Levantada em vez
 * de responder: indisponibilidade honesta, nunca resposta inventada.
 */
final class AgenteInativoException extends RuntimeException {}

<?php

declare(strict_types=1);

namespace App\Data\Convite;

use Spatie\LaravelData\Data;

/**
 * Um endereço que não saiu no lote, e por quê.
 *
 * O `motivo` é a chave crua (`formato_invalido`, `convite_pendente`, `recusou_antes`,
 * `ja_e_membro`, `erro_no_envio`), não o texto de tela: a tradução para pt-BR vive num `match` só,
 * em `ConvidaEmMassa::motivoLegivel()`, e é ele que decide o que o usuário lê. Um enum aqui
 * obrigaria a tela a traduzir duas vezes — ver o docblock daquele método.
 */
final class FalhaDoConviteData extends Data
{
    public function __construct(
        public readonly string $email,
        public readonly string $motivo,
    ) {}
}

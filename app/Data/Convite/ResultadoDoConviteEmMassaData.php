<?php

declare(strict_types=1);

namespace App\Data\Convite;

use Spatie\LaravelData\Data;

/**
 * O resultado de um lote de convites: o que saiu e o que não saiu.
 *
 * ## Por que existe
 *
 * O mesmo shape estava documentado **palavra por palavra** em duas classes —
 * `Convite::convidarEmMassa()` e `ConvidaEmMassa::notificarResultadoDoLote()`. Produtor e
 * consumidor concordavam por comentário, e nada os obrigava a continuar concordando.
 *
 * ## `list<FalhaDoConviteData>`, não `DataCollection`
 *
 * Convenção do kit (ADR-05): array tipado por padrão; `DataCollection` só quando o cenário
 * precisar de `include()`/`exclude()` na coleção, com a necessidade escrita no docblock. Aqui a
 * tela só itera e conta.
 */
final class ResultadoDoConviteEmMassaData extends Data
{
    /**
     * @param  list<string>  $enviados  endereços que receberam convite, em claro
     * @param  list<FalhaDoConviteData>  $falhas
     */
    public function __construct(
        public readonly array $enviados = [],
        public readonly array $falhas = [],
    ) {}

    /**
     * @param  list<string>  $enviados
     * @param  list<array{email: string, motivo: string}>  $falhas
     */
    public static function de(array $enviados, array $falhas): self
    {
        return self::from([
            'enviados' => $enviados,
            'falhas'   => array_map(
                static fn (array $falha): FalhaDoConviteData => FalhaDoConviteData::from($falha),
                $falhas,
            ),
        ]);
    }

    public function totalDeEnviados(): int
    {
        return count($this->enviados);
    }

    public function totalDeFalhas(): int
    {
        return count($this->falhas);
    }
}

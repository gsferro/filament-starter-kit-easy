<?php

declare(strict_types=1);

namespace App\Support\Casts;

use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

/**
 * Booleano vindo de API externa, que quase nunca chega como booleano.
 *
 * O caso que motivou: o classificador de prompt devolve `seguro` e alguns provedores devolvem
 * `"false"`, `"0"` ou `0` — e `"false"` é **truthy** em PHP. Sem este cast, um veredito inseguro
 * expresso como texto liberaria o prompt.
 *
 * Mora em `app/Support/Casts` e não em `app/Data`: dentro de `app/Data` toda classe estende o
 * `Data` do pacote, e o guarda do padrão (`App\Support\GuardaDoPadraoDeDto`) reprova quem não
 * estender. Cast é ferramenta de Data, não é Data.
 *
 * Só roda pelo pipeline do pacote — `MeuData::from(...)`. `new MeuData(...)` não o executa, e é
 * por isso que a criação passa sempre pela fábrica nomeada (ADR-03 da wiki
 * `laravel-data-como-padrao-de-dto`).
 */
final class BooleanoFlexivelCast implements Cast
{
    /**
     * @param  array<string, mixed>  $properties
     * @param  CreationContext<Data>  $context
     */
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): mixed
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value) || is_int($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }

        return false;
    }
}

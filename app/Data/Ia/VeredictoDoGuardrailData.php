<?php

declare(strict_types=1);

namespace App\Data\Ia;

use App\Support\Casts\BooleanoFlexivelCast;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;

/**
 * O veredito do classificador de prompt — o schema de `App\Ai\Agents\GuardaPrompt::schema()`
 * expresso como tipo em vez de `array<string, mixed>`.
 *
 * ## Por que existe
 *
 * O `GarantirPromptSeguroMiddleware` lia `$veredito['seguro'] ?? false` e
 * `$veredito['categoria'] ?? 'fora_de_escopo'` de um array cru vindo do modelo. O contrato estava
 * escrito em dois lugares (o schema do agente e o `??` do middleware) e em nenhum deles era
 * verificável.
 *
 * ## Os defaults são a decisão de segurança
 *
 * `seguro = false` **não** é um default inocente: é o fail-closed do campo que decide bloquear.
 * Classificador que responde no schema mas omite a chave não libera prompt — e é isso que o
 * `#[WithCast(BooleanoFlexivelCast::class)]` protege do outro lado, porque `"false"` em texto é
 * truthy em PHP.
 *
 * ## A criação passa pela fábrica
 *
 * `de()` é o único caminho. Ela normaliza a fonte (resposta do SDK, array, objeto ou JSON), remove
 * as chaves nulas para que o default do construtor valha, e chama `self::from()` — o pipeline do
 * pacote, que é quem roda o cast. `new VeredictoDoGuardrailData(...)` **não** roda cast nenhum.
 */
final class VeredictoDoGuardrailData extends Data
{
    public function __construct(
        #[WithCast(BooleanoFlexivelCast::class)]
        public readonly bool $seguro = false,
        public readonly string $categoria = 'fora_de_escopo',
        public readonly string $motivo = '',
    ) {}

    /**
     * A fábrica da fronteira: aceita o que o SDK devolve, e também array, objeto ou JSON.
     *
     * @param  StructuredAgentResponse|array<string, mixed>|object|string  $fonte
     */
    public static function de(array|object|string $fonte): self
    {
        $dados = match (true) {
            $fonte instanceof StructuredAgentResponse => $fonte->toArray(),
            is_array($fonte)                          => $fonte,
            is_string($fonte)                         => (array) json_decode($fonte, true),
            default                                   => get_object_vars($fonte),
        };

        /*
         * Chave nula sai: o pipeline do pacote preenche pelo default do construtor
         * (`DefaultValuesDataPipe`), e é assim que "ausente" e "nulo" caem na mesma partição.
         * String vazia FICA — `categoria: ''` é o provedor dizendo algo, não omitindo.
         */
        $presentes = array_filter(
            [
                'seguro'    => $dados['seguro'] ?? null,
                'categoria' => $dados['categoria'] ?? null,
                'motivo'    => $dados['motivo'] ?? null,
            ],
            static fn (mixed $valor): bool => $valor !== null,
        );

        return self::from($presentes);
    }
}

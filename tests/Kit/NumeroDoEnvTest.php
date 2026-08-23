<?php

use App\Support\NumeroDoEnv;

/**
 * As duas regras de coerção de inteiro vindo do `.env` — e por que são duas.
 *
 * O defeito de origem: o segundo argumento do `env()` só vale para chave **ausente**. Com a
 * chave presente e valor vazio (`KIT_ALGUMA_COISA=`), `env()` devolve string vazia e `(int) ''`
 * é 0, então o default nunca entra. Cinco chaves do kit nasceram assim, e o zero significava
 * coisa diferente em cada uma.
 *
 * Por isso o dataset é a tabela de decisão, e não um exemplo: é ela que impede alguém de
 * "unificar" as duas regras — unificar obriga a escolher um significado para o zero, e as duas
 * precisam de significados opostos.
 */
it('trata vazio como ausente e recusa zero onde o numero e obrigatorio', function (mixed $bruto, int $esperado): void {
    expect(NumeroDoEnv::positivo($bruto, 100))->toBe($esperado);
})->with([
    'vazio — o defeito de origem' => ['', 100],
    'zero não é configuração'     => ['0', 100],
    'zero como int'               => [0, 100],
    'ausente'                     => [null, 100],
    'negativo cai no piso'        => ['-5', 1],
    'texto cai no piso'           => ['abc', 1],
    'valor legítimo'              => ['30', 30],
    'valor legítimo como int'     => [30, 30],
]);

/**
 * Para prazo de poda, zero é escolha documentada — e texto tem de errar para o lado de NÃO
 * apagar.
 *
 * O bloco `retencao` do `config/kit.php` promete que "zero ou negativo desliga a poda daquela
 * trilha". Um `0` escrito à mão é intenção e precisa sobreviver; só o vazio e a ausência caem
 * no default.
 */
it('respeita o zero como desligado nos prazos de poda', function (mixed $bruto, int $esperado): void {
    expect(NumeroDoEnv::diasOuDesligado($bruto, 14))->toBe($esperado);
})->with([
    'vazio cai no default'          => ['', 14],
    'ausente cai no default'        => [null, 14],
    'zero DESLIGA, e é intenção'    => ['0', 0],
    'zero como int desliga'         => [0, 0],
    'negativo desliga'              => ['-5', 0],
    'texto desliga, não apaga tudo' => ['abc', 0],
    'valor legítimo'                => ['30', 30],
]);

/**
 * O contraste que dá nome às duas regras: o mesmo valor cru, dois resultados.
 *
 * Se alguém trocar uma pela outra num call site, este caso é o que explica o estrago —
 * `positivo('0', 14)` devolveria 14 num prazo que deveria estar DESLIGADO, e
 * `diasOuDesligado('0', 100)` devolveria 0 num limite de lote, recusando todo lote.
 */
it('difere no significado do zero, que e o ponto', function (): void {
    expect(NumeroDoEnv::positivo('0', 14))->toBe(14)
        ->and(NumeroDoEnv::diasOuDesligado('0', 14))->toBe(0);
});

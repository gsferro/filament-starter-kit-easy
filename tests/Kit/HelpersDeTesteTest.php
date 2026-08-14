<?php

use Symfony\Component\Finder\Finder;

/**
 * A guarda da regra que `tests/Pest.php` já enuncia em prosa — e que três arquivos violavam
 * em silêncio.
 *
 * Em PHP função é global no processo. Quando o Pest carrega TODOS os arquivos de teste, um
 * helper declarado em `AlgumTest.php` vaza para o vizinho e tudo passa: o acoplamento fica
 * invisível. Ele só aparece quando algo carrega um SUBCONJUNTO dos arquivos — e é exatamente
 * o que fazem os três comandos mais usados:
 *
 *   vendor/bin/pest --parallel                      (cada worker leva um subconjunto)
 *   vendor/bin/pest --tia                           (só o afetado pelo diff)
 *   vendor/bin/pest tests/Kit/AlgumTest.php         (um arquivo)
 *
 * O sintoma é `Call to undefined function`, que não diz nada sobre a causa. Antes desta
 * guarda eram 7 erros em `--parallel`, e o `--tia` do Pest 5 — a feature que motivou o
 * upgrade — era inutilizável. Ver `06-divida-tecnica.md` → DT-03 na wiki
 * `regressao-de-telas`.
 *
 * O que a regra NÃO diz: que helper local é proibido. Helper usado por um arquivo só
 * continua nele, como `tests/Pest.php` determina. O defeito é o uso CRUZADO.
 */
it('não usa helper de teste declarado em outro arquivo de teste', function (): void {
    $arquivos = [];

    foreach (Finder::create()->files()->in(base_path('tests'))->name('*Test.php') as $arquivo) {
        $arquivos[$arquivo->getRelativePathname()] = $arquivo->getContents();
    }

    // Onde cada helper nasce.
    $donos = [];

    foreach ($arquivos as $caminho => $codigo) {
        preg_match_all('/^function\s+(\w+)\s*\(/m', $codigo, $achados);

        foreach ($achados[1] as $nome) {
            $donos[$nome] = $caminho;
        }
    }

    /**
     * Chamadas reais, sem comentário nem string.
     *
     * `token_get_all()` e não regex: a menção a `conviteCom()` dentro de um docblock é o
     * caso mais comum nesta suíte, e um regex a contaria como chamada — guarda com falso
     * positivo é pior que guarda nenhuma, porque ensina o time a ignorá-la.
     *
     * @return list<string>
     */
    $chamadasDe = function (string $codigo) use ($donos): array {
        $tokens   = token_get_all($codigo);
        $chamadas = [];

        foreach ($tokens as $i => $token) {
            if (! is_array($token) || $token[0] !== T_STRING || ! isset($donos[$token[1]])) {
                continue;
            }

            // O token anterior significativo: `function` marca declaração, `->`/`::` marca
            // método, e nenhum dos dois é chamada de helper global.
            $anterior = null;

            for ($j = $i - 1; $j >= 0; $j--) {
                if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }

                $anterior = $tokens[$j];
                break;
            }

            $ehDeclaracao = is_array($anterior) && $anterior[0] === T_FUNCTION;
            $ehMembro     = is_array($anterior) && in_array($anterior[0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON], true);

            if (! $ehDeclaracao && ! $ehMembro) {
                $chamadas[] = $token[1];
            }
        }

        return array_values(array_unique($chamadas));
    };

    $violacoes = [];

    foreach ($arquivos as $caminho => $codigo) {
        foreach ($chamadasDe($codigo) as $nome) {
            if ($donos[$nome] !== $caminho) {
                $violacoes[] = "{$caminho} chama {$nome}(), declarado em {$donos[$nome]}";
            }
        }
    }

    expect($violacoes)->toBe([], 'Helper de teste usado de outro arquivo. Mova para a seção de '
        ."helpers compartilhados de tests/Pest.php:\n  - ".implode("\n  - ", $violacoes));
});

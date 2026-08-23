<?php

namespace App\Support;

/**
 * Inteiro vindo do `.env`, com o caso VAZIO tratado — que é o que o `env()` não faz.
 *
 * O segundo argumento do `env()` só vale para chave **ausente**. Com a chave presente e o valor
 * vazio (`KIT_ALGUMA_COISA=`, o que sobra quando alguém apaga o número e esquece de apagar o
 * `=`), `env()` devolve string vazia, e `(int) ''` é **0**. O default nunca entra.
 *
 * Cinco chaves do kit nasceram com esse padrão, e o zero significava coisa diferente em cada
 * uma — de "modal que recusa todo lote" a "apaga a tabela inteira". Daí duas regras com nome,
 * em vez de cinco expressões parecidas:
 *
 * - {@see positivo()} para grandeza que **precisa** de um número — um limite de lote de zero
 *   não é configuração, é a feature desligada por acidente;
 * - {@see diasOuDesligado()} para prazo em que **zero é uma escolha legítima** e documentada,
 *   como as retenções: zero desliga a poda, e a tabela crescer é decisão de quem opera.
 *
 * A diferença entre as duas está toda no que fazer com o zero, e é por isso que não dá para
 * unificar: `positivo()` recusa o zero, `diasOuDesligado()` o respeita. O que as duas
 * compartilham é tratar **vazio como ausente**, que é o defeito original.
 */
class NumeroDoEnv
{
    /**
     * Um inteiro maior que zero, sempre.
     *
     * Vazio, `null`, `0` e ausente caem no default. Negativo e texto (`(int) 'abc'` é 0) caem
     * em **1**, e o piso é deliberado: o pior caso passa a ser um valor curto e visível, que
     * faz alguém corrigir o `.env`, em vez de uma feature silenciosamente desligada.
     */
    public static function positivo(mixed $bruto, int $padrao): int
    {
        return max(1, (int) ($bruto ?: $padrao));
    }

    /**
     * Um prazo em dias, onde zero e negativo significam **desligado**.
     *
     * Só o vazio e a ausência caem no default — um `0` escrito à mão é intenção, e a
     * documentação do bloco `retencao` no `config/kit.php` promete que ele desliga a poda.
     * Texto vira 0, ou seja desligado: para poda, errar para o lado de **não apagar** é o
     * único erro aceitável.
     */
    public static function diasOuDesligado(mixed $bruto, int $padrao): int
    {
        if ($bruto === null || $bruto === '') {
            return $padrao;
        }

        return max(0, (int) $bruto);
    }
}

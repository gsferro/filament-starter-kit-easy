<?php

namespace App\Support;

/**
 * Quantos dias um convite vale, a partir do valor cru de `KIT_CONVITE_VALIDADE_DIAS`.
 *
 * Uma classe para uma conta de uma linha porque a linha é uma **fronteira de confiança**, e
 * ela já falhou: `KIT_CONVITE_VALIDADE_DIAS=` — chave presente com valor VAZIO, o que sobra
 * quando alguém apaga o número e esquece de apagar o `=` — devolve string vazia, e o segundo
 * argumento do `env()` **não** alcança esse caso: ele só vale para chave ausente. `(int) ''` é
 * 0, `now()->addDays(0)` grava `expira_em` igual ao instante do envio, e o `Convite::valido()`
 * exige prazo no futuro. O convite nascia morto: o e-mail saía, o log registrava sucesso, e
 * quem recebia via "convite expirado" no primeiro clique. Nenhum erro em lugar nenhum.
 *
 * O segundo motivo é de teste, e é medido: a primeira versão desta guarda vivia inteira no
 * `config/kit.php`, e o único jeito de exercitá-la era montar `putenv()`/`$_ENV` à mão e dar
 * `require` no arquivo de config. Passava aqui e **falhava no CI** — três datasets devolvendo o
 * default —, porque o que o `env()` do Laravel enxerga depende dos adaptadores de ambiente do
 * runner. Teste de coerção que depende de plumbing de ambiente mede o runner, não a regra. Com
 * a regra num método puro, o dataset é determinístico em qualquer máquina.
 *
 * A tabela de decisão está no teste (`tests/Kit/ConviteTest.php`), e o resumo é:
 * vazio, `0`, `null` e ausente caem no default de sete dias — convite de zero dia nunca é
 * intenção; negativo e texto caem em **um** dia. O piso é 1 de propósito: o pior caso passa a
 * ser um convite curto e visível, que faz alguém corrigir o `.env`, em vez de um convite
 * inválido ao nascer, que se disfarça de "link expirado".
 */
class ValidadeDoConvite
{
    /**
     * O default do kit, e o único lugar onde ele está escrito.
     */
    public const int DIAS_PADRAO = 7;

    /**
     * @param  mixed  $bruto  o que veio do `.env`: string, `null`, ou já um int
     */
    public static function emDias(mixed $bruto): int
    {
        // `?:` e não `??`: o `??` só pega `null` e deixaria passar a string vazia e o zero,
        // que são justamente os dois valores que produziam o convite morto.
        return max(1, (int) ($bruto ?: self::DIAS_PADRAO));
    }
}

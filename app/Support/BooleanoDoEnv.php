<?php

namespace App\Support;

/**
 * Booleano vindo do `.env`, com o caso VAZIO tratado — o irmão de {@see NumeroDoEnv}.
 *
 * O defeito é o mesmo que aquela classe documenta para inteiros, e por isso vale a pena
 * ler o docblock de lá: o segundo argumento do `env()` só se aplica a chave **ausente**.
 * Com `KIT_ALGUMA_COISA=` (presente, valor vazio — o que sobra quando alguém apaga o valor
 * e esquece de apagar o `=`), `env()` devolve string vazia, `(bool) ''` é **false**, e o
 * default nunca entra.
 *
 * Enquanto o default de uma chave for `false`, o defeito é inócuo — é o caso de `KIT_HUB` e
 * `KIT_TENANCY`, que continuam com `(bool) env(...)` no `config/kit.php` justamente por
 * isso. **Com default `true` ele inverte a configuração**, e foi assim que os três
 * interruptores de tabela nasceram desligados nesta feature.
 *
 * ## `filter_var` sozinho NÃO resolve, e essa é a parte medida
 *
 * A correção óbvia — e a primeira que foi escrita aqui — é
 * `filter_var($bruto, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $padrao`. Ela
 * parece certa, passa em revisão de código e **reproduz o defeito**, porque o filtro do PHP
 * trata ausente e vazio como `false`, não como falha:
 *
 * | `$bruto`  | `filter_var(..., FILTER_NULL_ON_FAILURE)` |
 * |-----------|-------------------------------------------|
 * | `null`    | `false`                                   |
 * | `''`      | `false`                                   |
 * | `'0'`     | `false`                                   |
 * | `'false'` | `false`                                   |
 * | `'true'`  | `true`                                    |
 * | `'lixo'`  | `null`                                    |
 *
 * O `??` só dispara na última linha. Daí a guarda explícita ANTES do filtro: é ela que
 * carrega a regra, e o filtro só cuida do vocabulário (`true/false`, `1/0`, `on/off`,
 * `yes/no`).
 *
 * Verificado com `php -r`, não deduzido — e os dois casos que importam (`null` e `''`) têm
 * caso de teste em `tests/Kit/BooleanoDoEnvTest.php`.
 */
class BooleanoDoEnv
{
    /**
     * Um booleano, com ausente, vazio e ilegível caindo no default.
     *
     * Texto que não é vocabulário de booleano (`'talvez'`) cai no **default**, e não em
     * `false`: aqui não existe um "pior caso visível" como o piso de 1 de
     * `NumeroDoEnv::positivo()` — um booleano errado é indistinguível de um booleano
     * escolhido. Entregar o comportamento que o kit promete é o único resultado que não
     * mente para quem lê o `.env.example`.
     */
    public static function comPadrao(mixed $bruto, bool $padrao): bool
    {
        if ($bruto === null || $bruto === '') {
            return $padrao;
        }

        return filter_var($bruto, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $padrao;
    }
}

<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * A data de corte da poda da trilha de exceções.
 *
 * Existe por um defeito medido, e o formato do vendor é metade do motivo:
 * `FilamentExceptionsPlugin::modelPruneInterval()` recebe uma **data**, não uma quantidade de
 * dias, e `Exception::prunable()` faz
 * `whereDate('created_at', '<=', $intervalo)`
 * (`vendor/bezhansalleh/filament-exceptions/src/Models/Exception.php:44`). `whereDate` compara
 * só a data: um corte de hoje casa com a tabela **inteira**, inclusive as linhas do dia.
 *
 * Logo `subDays(0)` apagava tudo, e `subDays(-5)` — corte no futuro — também. Enquanto isso o
 * bloco `retencao` do `config/kit.php` promete, por escrito, que "zero ou negativo desliga a
 * poda daquela trilha". As três podas de `routes/console.php` honram a promessa com
 * `if ($dias <= 0) return;`; esta era a quarta, e fazia o oposto do documentado.
 *
 * Está numa classe, e não no ternário dentro do `InfraPanelProvider`, porque o provider registra
 * o plugin **uma vez, no boot**: um teste que mude a config depois mede a instância antiga e
 * passa a medir plumbing em vez da regra. Foi exatamente esse o erro na primeira tentativa de
 * cobrir isto — o caso ficou verde sem provar nada até a decisão sair de lá.
 */
class RetencaoDeExcecoes
{
    /**
     * Cem anos: desliga de verdade, e mantém o contrato do vendor, que exige uma data.
     *
     * Não existe "nulo" aceitável em `modelPruneInterval()`, então desligar é escolher um corte
     * que nenhuma linha alcança.
     */
    private const int ANOS_PARA_DESLIGAR = 100;

    public static function corte(): Carbon
    {
        $dias = (int) config('kit.retencao.excecoes_em_dias', 14);

        /*
         * `Carbon::now()` explícito, e não o helper `now()`: o kit faz
         * `Date::use(CarbonImmutable::class)` no `KitServiceProvider`, então `now()` devolve
         * `CarbonImmutable` — e a assinatura do pacote pede o `Carbon` mutável. O PHPStan pega;
         * em runtime seria `TypeError` no boot do painel, ou seja em toda página do `/infra`.
         */
        return $dias > 0
            ? Carbon::now()->subDays($dias)
            : Carbon::now()->subYears(self::ANOS_PARA_DESLIGAR);
    }
}

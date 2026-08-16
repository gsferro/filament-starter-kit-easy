<?php

declare(strict_types=1);

namespace App\Traits;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;

/**
 * Ponto único de ativação do model caching para as models do kit.
 *
 * O pacote `mike-bronner/laravel-model-caching` lê `config('laravel-model-caching.enabled')`
 * para decidir se realmente cacheia. Usar esta trait intermediária em vez da trait do vendor
 * permite trocar ou desligar o mecanismo sem tocar em cada model.
 */
trait ModeloCacheavel
{
    use Cachable;
}

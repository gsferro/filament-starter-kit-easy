<?php

namespace Tests;

/**
 * TestCase dos testes de multi-tenancy: liga o modo, nada mais.
 *
 * Todo o mecanismo — as três chaves que precisam concordar e o prazo de cada
 * uma — mora no `Tests\TestCase`. Aqui só se declara o modo.
 *
 * A pasta separada é exigência de bootstrap, não de organização: o modo tem de
 * estar decidido antes das migrations, e o Pest não permite dois TestCases no
 * mesmo diretório. Daí `tests/Tenancy` ter o seu.
 *
 * Mesmo grupo `kit`, então continua entrando em `composer test:kit`.
 */
abstract class TenancyTestCase extends TestCase
{
    protected function usaTenancy(): bool
    {
        return true;
    }
}

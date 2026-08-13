<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Modo de tenancy com que o schema em memória foi migrado nesta execução.
     * `null` = ainda não migrou.
     */
    private static ?bool $schemaComTenancy = null;

    /**
     * O `RefreshDatabase` migra UMA vez por processo e reaproveita o schema em
     * todos os testes seguintes. Isso quebra quando a suíte mistura os dois
     * modos do kit: a migration de permissões do spatie cria (ou não) as
     * colunas de team conforme `permission.teams`, então o primeiro teste a
     * rodar decidiria o schema de todos os outros — e o outro modo falharia
     * com violação de NOT NULL, sem relação com o que está sendo testado.
     *
     * Aqui o schema é invalidado só quando o modo MUDA. Numa execução de
     * `--group=kit` isso custa uma migration extra na virada, não uma por
     * teste.
     */
    protected function setUp(): void
    {
        if (self::$schemaComTenancy !== $this->schemaComTenancy()) {
            RefreshDatabaseState::$migrated = false;
            self::$schemaComTenancy         = $this->schemaComTenancy();
        }

        parent::setUp();
    }

    /** Sobrescrito por Tests\TenancyTestCase. */
    protected function schemaComTenancy(): bool
    {
        return false;
    }
}

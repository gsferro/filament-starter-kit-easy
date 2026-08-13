<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Modo de tenancy com que o schema em memória foi migrado nesta execução.
     * `null` = ainda não migrou.
     */
    private static ?bool $schemaMigradoComTenancy = null;

    /** Sobrescrito por Tests\TenancyTestCase. */
    protected function usaTenancy(): bool
    {
        return false;
    }

    /**
     * A flag vai para o AMBIENTE, antes do bootstrap.
     *
     * O `AppPanelProvider` lê `kit.tenancy.enabled` durante o boot para decidir
     * se registra as rotas do painel com o segmento `/{tenant}`. Config ajustada
     * depois de `parent::createApplication()` chega tarde: as rotas já existem
     * sem o segmento, e todo `/app/{slug}` responde 404.
     *
     * O repositório de env do Laravel é imutável, então valor já presente em
     * `$_SERVER` vence o que estiver no `.env` — é o que permite alternar os
     * dois modos dentro da mesma execução de testes.
     */
    public function createApplication(): Application
    {
        $valor = $this->usaTenancy() ? 'true' : 'false';

        putenv("KIT_TENANCY={$valor}");
        $_ENV['KIT_TENANCY']    = $valor;
        $_SERVER['KIT_TENANCY'] = $valor;

        return parent::createApplication();
    }

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
        if (self::$schemaMigradoComTenancy !== $this->usaTenancy()) {
            RefreshDatabaseState::$migrated = false;
            self::$schemaMigradoComTenancy  = $this->usaTenancy();
        }

        parent::setUp();
    }
}

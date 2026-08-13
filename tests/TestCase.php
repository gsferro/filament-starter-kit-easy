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
     */
    public function createApplication(): Application
    {
        /*
         * Cache de config e de rotas ficam FORA de alcance nos testes.
         *
         * Os dois congelam decisões de um ambiente: com `bootstrap/cache/config.php`
         * no lugar, o `env()` nem é consultado e a flag escrita logo abaixo não tem
         * efeito nenhum — a tenancy volta a ligar, o painel /app vira /app/{tenant}
         * e a suíte do kit falha com 404 em `GET /app`, sem pista da causa.
         *
         * Apontar as duas chaves para arquivos que não existem faz o Laravel
         * bootar da fonte. Nada é apagado: o cache do projeto continua no lugar.
         */
        $this->definirEnv('APP_CONFIG_CACHE', 'bootstrap/cache/config.testing.php');
        $this->definirEnv('APP_ROUTES_CACHE', 'bootstrap/cache/routes-v7.testing.php');

        $this->definirEnv('KIT_TENANCY', $this->usaTenancy() ? 'true' : 'false');

        return parent::createApplication();
    }

    /**
     * Escreve no ambiente do processo, antes do bootstrap.
     *
     * O repositório de env do Laravel é imutável, então valor já presente em
     * `$_SERVER` vence o que estiver no `.env` — é o que permite alternar os dois
     * modos de tenancy dentro da mesma execução de testes.
     */
    private function definirEnv(string $chave, string $valor): void
    {
        putenv("{$chave}={$valor}");
        $_ENV[$chave]    = $valor;
        $_SERVER[$chave] = $valor;
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

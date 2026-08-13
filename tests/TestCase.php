<?php

namespace Tests;

use App\Models\Tenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

/**
 * Base dos testes do kit — e o único lugar que decide o modo de tenancy da
 * execução, por `usaTenancy()`.
 *
 * O modo mora em TRÊS chaves que precisam concordar, e cada uma tem seu prazo:
 *
 *   1. `kit.tenancy.enabled`, antes do BOOT — o `AppPanelProvider` a lê para
 *      registrar (ou não) as rotas do painel com o segmento `/{tenant}`.
 *   2. `permission.teams` + `filament-shield.tenant_model`, antes das
 *      MIGRATIONS — a migration do spatie lê a primeira em tempo de execução
 *      para criar (ou não) as colunas de team.
 *   3. o contexto de papéis do `PermissionRegistrar`, que é singleton e lê
 *      `permission.teams` no construtor.
 *
 * Elas não vêm do mesmo lugar, e é aí que saíam de sincronia: a primeira é env
 * (`KIT_TENANCY`), as outras duas são arquivos que o `kit:tenancy` reescreve em
 * DISCO. Num projeto com a tenancy ligada, `permission.teams` chegava aqui como
 * `true` mesmo nas suítes single-tenant — e como ninguém fixava o contexto de
 * papéis (o `KitServiceProvider` só o faz com `kit.tenancy.enabled`), atribuir
 * papel estourava `NOT NULL constraint failed: model_has_roles.team_id`.
 */
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

        $app = parent::createApplication();

        /*
         * As duas chaves de DISCO, alinhadas com o modo desta execução.
         *
         * Aqui ainda é antes das migrations: o RefreshDatabase roda no
         * `setUpTraits()`, depois deste método. Ajustar `permission.teams` num
         * `beforeEach` seria tarde — o schema já teria sido criado.
         */
        $app['config']->set('permission.teams', $this->usaTenancy());
        $app['config']->set('filament-shield.tenant_model', $this->usaTenancy() ? Tenant::class : null);

        /*
         * O PermissionRegistrar já foi resolvido durante o boot, lendo o
         * `permission.teams` do arquivo — precisa ser descartado para renascer
         * sabendo do modo. Com teams ligado, o contexto global de papéis também
         * precisa ser fixado à mão: o `KitServiceProvider::configureTenancy()`
         * rodou no boot, quando esta config ainda não existia.
         */
        $app->forgetInstance(PermissionRegistrar::class);

        if ($this->usaTenancy()) {
            $app->make(PermissionRegistrar::class)->setPermissionsTeamId(Tenant::CONTEXTO_GLOBAL);
        }

        return $app;
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

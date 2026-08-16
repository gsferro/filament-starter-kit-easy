<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Os passos NÃO destrutivos de ligar o modo multi-tenant.
 *
 * Ligar a tenancy são três chaves que precisam concordar, e cada uma tem seu prazo:
 *
 *   1. `KIT_TENANCY=true` no .env  → antes do BOOT: o AppPanelProvider a lê para
 *                                    registrar (ou não) as rotas com `/{tenant}`
 *   2. `permission.teams = true`   → antes das MIGRATIONS: a migration do spatie
 *      + `filament-shield.tenant_model`  lê a config em tempo de execução para criar
 *                                    (ou não) as colunas de team
 *   3. contexto do PermissionRegistrar → antes de qualquer `assignRole()`: o
 *                                    singleton lê `permission.teams` no construtor
 *
 * Nada disso apaga dado. O que apaga é o QUARTO passo, que só o `kit:tenancy`
 * precisa: `migrate:fresh --seed`, para um banco que já foi migrado sem as
 * colunas de team. Num banco que ainda não nasceu — a instalação — os três
 * passos daqui bastam, e a tenancy sai de graça.
 *
 * Por isso os dois chamadores:
 *
 *   - `KitInstall`  → chama antes do primeiro migrate; não destrói nada
 *   - `KitTenancy`  → chama e, em seguida, recria o banco
 */
final class AtivadorDeTenancy
{
    /** Escreve a flag e os rótulos no .env. */
    public static function escreverEnv(
        string $caminhoDoEnv,
        ?string $label = null,
        ?string $labelPlural = null,
        ?string $slug = null,
    ): void {
        SubstituicaoEmArquivo::aplicar(
            $caminhoDoEnv,
            '/^#?\s*KIT_TENANCY=.*$/m',
            'KIT_TENANCY=true',
            PHP_EOL.'KIT_TENANCY=true'.PHP_EOL,
        );

        if ($label === null) {
            return;
        }

        $labelPlural ??= $label.'s';
        $slug ??= Str::slug($labelPlural);

        SubstituicaoEmArquivo::definirNoEnv($caminhoDoEnv, 'KIT_TENANCY_LABEL', $label);
        SubstituicaoEmArquivo::definirNoEnv($caminhoDoEnv, 'KIT_TENANCY_LABEL_PLURAL', $labelPlural);
        SubstituicaoEmArquivo::definirNoEnv($caminhoDoEnv, 'KIT_TENANCY_SLUG', $slug);
    }

    /**
     * Liga o recorte por tenant no spatie E no Shield.
     *
     * `Utils::isTenancyEnabled()` do Shield lê exatamente `permission.teams` — as
     * duas chaves são uma decisão só, e separá-las produz um estado em que o
     * banco tem as colunas e a tela de papéis não as usa.
     *
     * O diretório é parâmetro pelo mesmo motivo do diretório-base do
     * `CustomizadorDaInstalacao`: sem ele, um teste desta ativação reescreveria o
     * `config/permission.php` do projeto de quem roda a suíte.
     */
    public static function ligarPapeisPorTenant(?string $dirDeConfig = null): void
    {
        $dirDeConfig ??= config_path();

        SubstituicaoEmArquivo::aplicar(
            $dirDeConfig.DIRECTORY_SEPARATOR.'permission.php',
            "/'teams'\s*=>\s*false/",
            "'teams' => true",
        );

        SubstituicaoEmArquivo::aplicar(
            $dirDeConfig.DIRECTORY_SEPARATOR.'filament-shield.php',
            "/'tenant_model'\s*=>\s*null/",
            "'tenant_model' => \\App\\Models\\Tenant::class",
        );
    }

    /**
     * Alinha a config JÁ CARREGADA com o que acabou de ser escrito em disco.
     *
     * Sem isto o processo corrente falha de um jeito traiçoeiro: `config:clear`
     * apaga o arquivo de cache mas NÃO recarrega a config em memória. O
     * `migrate` que roda em seguida, no mesmo processo, lê `permission.teams`
     * ainda como `false` e cria as tabelas de permissão SEM as colunas de team.
     * A requisição seguinte — processo novo, config nova — consulta
     * `model_has_roles.team_id` e recebe "no such column".
     *
     * O `PermissionRegistrar` é singleton e lê `permission.teams` no construtor,
     * então precisa ser descartado para renascer sabendo de teams. E o contexto
     * global de papéis precisa ser fixado à mão, porque o
     * `KitServiceProvider::configureTenancy()` já rodou no boot, quando a flag
     * ainda estava desligada — sem ele, os seeders atribuem papel com `team_id`
     * nulo e estouram a constraint NOT NULL.
     */
    public static function alinharConfigEmMemoria(): void
    {
        config([
            'kit.tenancy.enabled'          => true,
            'permission.teams'             => true,
            'filament-shield.tenant_model' => Tenant::class,
        ]);

        app()->forgetInstance(PermissionRegistrar::class);
        app(PermissionRegistrar::class)->setPermissionsTeamId(Tenant::CONTEXTO_GLOBAL);
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Pages\HubDeAdministracao;
use App\Filament\Admin\Resources\AgentesIa\AgenteIaResource;
use App\Filament\Admin\Resources\Convites\ConviteResource;
use App\Filament\Admin\Resources\Onboarding\CondicaoResource;
use App\Filament\Admin\Resources\Onboarding\JornadaResource;
use App\Filament\Admin\Resources\Roles\RoleResource;
use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\App\Pages\ConvitesRecebidos;
use App\Filament\App\Pages\HubDoNegocio;
use App\Filament\App\Resources\Projetos\ProjetoResource;
use App\Filament\Infra\Pages\BackupRunsPage;
use App\Filament\Infra\Pages\HubDeInfraestrutura;
use App\Filament\Infra\Pages\Pulse;
use App\Filament\Infra\Resources\AiRuns\AiRunResource;
use App\Filament\Infra\Resources\ComposerReleasePackages\ComposerReleasePackageResource;
use App\Filament\Pages\DashboardClassico;
use App\Filament\Pages\MyProfilePage;
use App\Filament\Pages\PrimeirosPassos;
use BezhanSalleh\FilamentExceptions\Resources\ExceptionResource;
use Bityukov\CommandCenter\Filament\Pages\Commands;
use Bityukov\CommandCenter\Filament\Pages\History;
use Bityukov\CommandCenter\Filament\Pages\RunView;
use Bityukov\CommandCenter\Filament\Resources\CommandRecordResource;
use Croustibat\FilamentJobsMonitor\Resources\QueueMonitorResource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use LaBoiteACode\DependencyGraph\Filament\Pages\DependencyGraphPage;
use LaBoiteACode\FilamentLogsExplorer\Pages\LogsExplorer;
use Promethys\Revive\Pages\RecycleBin;
use ShuvroRoy\FilamentSpatieLaravelHealth\Pages\HealthCheckResults;
use Tapp\FilamentAuditing\Filament\Resources\Audits\AuditResource;
use Tapp\FilamentAuthenticationLog\Resources\AuthenticationLogResource;
use Tapp\FilamentMailLog\Resources\MailLogResource;

/**
 * O seeder PAI do tutorial guiado (wiki `onboarding-guiado`): chama um seeder filho por feature
 * do kit, e cada filho acrescenta o seu passo, com tour, à jornada "Primeiros passos" do painel
 * em que a feature vive.
 *
 *   php artisan db:seed --class=OnboardingSeeder
 *
 * ou na instalação: `php artisan kit:install --onboarding` (ou "sim" à pergunta). Fora do
 * `DatabaseSeeder` de propósito: semear o tutorial é escolha de quem instala (RQ-06).
 *
 * Idempotente, e PRESERVA: rodar de novo cria só o que falta e nunca sobrescreve o que foi
 * editado na autoria (RQ-25 — ver `Onboarding\SeederDeOnboarding`). Não liga o interruptor:
 * semear não é ligar (RQ-21); quem liga é a tela de configurações.
 *
 * ## Feature nova
 *
 * Toda tela de painel nova entra em `TELAS` (com o filho que a ensina) ou em `ISENTAS` (com o
 * motivo) — o `tests/Kit/InventarioDeOnboardingTest.php` fica vermelho até entrar. Feature nova
 * ganha o seu filho em `database/seeders/Onboarding/` e a linha em `FILHOS` (ou em
 * `FILHOS_DE_TENANCY`, se só existe com multi-tenant). Ver `.ai/rules/onboarding.md`.
 */
final class OnboardingSeeder extends Seeder
{
    /**
     * Os filhos que rodam sempre, na ordem dos passos dentro de cada jornada.
     *
     * @var list<class-string<Onboarding\SeederDeOnboarding>>
     */
    public const FILHOS = [
        Onboarding\PerfilEDoisFatoresSeeder::class,
        Onboarding\BuscaGlobalSeeder::class,
        Onboarding\HubDeNavegacaoSeeder::class,
        Onboarding\DashboardDinamicoSeeder::class,
        Onboarding\DockDeAtalhosSeeder::class,
        Onboarding\BloqueioDeTelaSeeder::class,
        Onboarding\UsuariosSeeder::class,
        Onboarding\ConvitesSeeder::class,
        Onboarding\PapeisEPermissoesSeeder::class,
        Onboarding\ConfiguracoesDaAplicacaoSeeder::class,
        Onboarding\AgentesDeIaSeeder::class,
        Onboarding\HubDeInfraestruturaSeeder::class,
        Onboarding\PulseSeeder::class,
        Onboarding\BackupsSeeder::class,
        Onboarding\LogsDaAplicacaoSeeder::class,
        Onboarding\ExcecoesSeeder::class,
        Onboarding\FilasSeeder::class,
        Onboarding\AuditoriaSeeder::class,
        Onboarding\ExecucoesDeIaSeeder::class,
        Onboarding\LixeiraSeeder::class,
        Onboarding\CentralDeComandosSeeder::class,
    ];

    /**
     * Os filhos das features que só existem com multi-tenant (RQ-24): rodam só com
     * `kit.tenancy.enabled`. Sem tenancy as rotas existem, mas as telas não abrem — semear o
     * passo seria ensinar uma tela que a pessoa não alcança.
     *
     * @var list<class-string<Onboarding\SeederDeOnboarding>>
     */
    public const FILHOS_DE_TENANCY = [
        Onboarding\OrganizacoesSeeder::class,
        Onboarding\MembrosDaOrganizacaoSeeder::class,
        Onboarding\ConvitesDaOrganizacaoSeeder::class,
        Onboarding\ConvitesRecebidosSeeder::class,
        Onboarding\TrocaDeOrganizacaoSeeder::class,
        Onboarding\ProjetosSeeder::class,
    ];

    /**
     * Toda tela de painel (Page ou Resource registrado) com o filho que a ensina, por painel —
     * o inventário que o teste confere contra `getPages()`/`getResources()` de cada painel.
     *
     * @var array<string, array<class-string, class-string<Onboarding\SeederDeOnboarding>>>
     */
    public const TELAS = [
        'admin' => [
            ConfiguracoesDoKit::class                        => Onboarding\ConfiguracoesDaAplicacaoSeeder::class,
            Dashboard::class                                 => Onboarding\DashboardDinamicoSeeder::class,
            HubDeAdministracao::class                        => Onboarding\HubDeNavegacaoSeeder::class,
            MyProfilePage::class                             => Onboarding\PerfilEDoisFatoresSeeder::class,
            AgenteIaResource::class                          => Onboarding\AgentesDeIaSeeder::class,
            ConviteResource::class                           => Onboarding\ConvitesSeeder::class,
            RoleResource::class                              => Onboarding\PapeisEPermissoesSeeder::class,
            TenantResource::class                            => Onboarding\OrganizacoesSeeder::class,
            UserResource::class                              => Onboarding\UsuariosSeeder::class,
        ],
        'app' => [
            ConvitesRecebidos::class                                             => Onboarding\ConvitesRecebidosSeeder::class,
            \App\Filament\App\Pages\Dashboard::class                             => Onboarding\DashboardDinamicoSeeder::class,
            HubDoNegocio::class                                                  => Onboarding\HubDeNavegacaoSeeder::class,
            MyProfilePage::class                                                 => Onboarding\PerfilEDoisFatoresSeeder::class,
            \App\Filament\App\Resources\Convites\ConviteResource::class          => Onboarding\ConvitesDaOrganizacaoSeeder::class,
            ProjetoResource::class                                               => Onboarding\ProjetosSeeder::class,
            \App\Filament\App\Resources\Users\UserResource::class                => Onboarding\MembrosDaOrganizacaoSeeder::class,
        ],
        'infra' => [
            BackupRunsPage::class                                                => Onboarding\BackupsSeeder::class,
            \App\Filament\Infra\Pages\Dashboard::class                           => Onboarding\DashboardDinamicoSeeder::class,
            HubDeInfraestrutura::class                                           => Onboarding\HubDeInfraestruturaSeeder::class,
            Pulse::class                                                         => Onboarding\PulseSeeder::class,
            MyProfilePage::class                                                 => Onboarding\PerfilEDoisFatoresSeeder::class,
            LogsExplorer::class                                                  => Onboarding\LogsDaAplicacaoSeeder::class,
            Commands::class                                                      => Onboarding\CentralDeComandosSeeder::class,
            History::class                                                       => Onboarding\CentralDeComandosSeeder::class,
            CommandRecordResource::class                                         => Onboarding\CentralDeComandosSeeder::class,
            RecycleBin::class                                                    => Onboarding\LixeiraSeeder::class,
            AiRunResource::class                                                 => Onboarding\ExecucoesDeIaSeeder::class,
            QueueMonitorResource::class                                          => Onboarding\FilasSeeder::class,
            AuditResource::class                                                 => Onboarding\AuditoriaSeeder::class,
            ExceptionResource::class                                             => Onboarding\ExcecoesSeeder::class,
        ],
    ];

    /**
     * As telas de painel SEM tutorial, cada uma com o motivo. Classe isenta vale em todo painel
     * em que estiver registrada.
     *
     * @var array<class-string, string>
     */
    public const ISENTAS = [
        DashboardClassico::class                                     => 'dashboard padrão do Filament: só widgets, sem ação a ensinar (o passo do dashboard é o do dinâmico)',
        PrimeirosPassos::class                                       => 'é a própria página do tutorial',
        JornadaResource::class                                       => 'autoria do próprio tutorial',
        CondicaoResource::class                                      => 'autoria do próprio tutorial',
        HealthCheckResults::class                                    => 'fora da tabela aprovada na Q5 da wiki onboarding-guiado',
        DependencyGraphPage::class                                   => 'fora da tabela aprovada na Q5 da wiki onboarding-guiado',
        RunView::class                                               => 'tela de detalhe de uma execução: só abre com um registro',
        ComposerReleasePackageResource::class                        => 'fora da tabela aprovada na Q5 da wiki onboarding-guiado',
        AuthenticationLogResource::class                             => 'fora da tabela aprovada na Q5 da wiki onboarding-guiado',
        MailLogResource::class                                       => 'fora da tabela aprovada na Q5 da wiki onboarding-guiado',
    ];

    /**
     * Telas isentas só em alguns painéis — a mesma classe é ensinada em outro.
     *
     * @var array<string, array<class-string, string>>
     */
    public const ISENTAS_NO_PAINEL = [
        'admin' => [ExceptionResource::class => 'registrada fora da navegação neste painel; o passo das exceções é o do /infra'],
        'app'   => [ExceptionResource::class => 'registrada fora da navegação neste painel; o passo das exceções é o do /infra'],
    ];

    public function run(): void
    {
        $tenancy = (bool) config('kit.tenancy.enabled');
        $filhos  = $tenancy ? [...self::FILHOS, ...self::FILHOS_DE_TENANCY] : self::FILHOS;

        $this->call($filhos);

        Log::channel('onboarding')->info(
            '[OnboardingSeeder@run] Jornadas semeadas | filhos: '.count($filhos),
            ['filhos' => count($filhos), 'tenancy' => $tenancy],
        );
    }
}

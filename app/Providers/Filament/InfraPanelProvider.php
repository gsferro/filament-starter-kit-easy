<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\TelaBloqueio;
use App\Filament\Spotlight\AcoesDeCriacao;
use App\Filament\Spotlight\PagesAutorizadasCategory;
use App\Filament\Spotlight\ResourcesAutorizadasCategory;
use App\Support\CorPrimaria;
use Asmit\ResizedColumn\ResizedColumnPlugin;
use Bityukov\CommandCenter\Filament\CommandCenterPlugin;
use Bityukov\CommandCenter\Filament\Pages\Commands as CommandCenterCommands;
use Bityukov\CommandCenter\Filament\Pages\History as CommandCenterHistory;
use Brimham\FilamentBackupMonitor\FilamentBackupMonitorPlugin;
use Caresome\FilamentAuthDesigner\AuthDesignerPlugin;
use Caresome\FilamentAuthDesigner\Data\AuthPageConfig;
use Caresome\FilamentAuthDesigner\Enums\MediaPosition;
use CmsMulti\FilamentClearCache\FilamentClearCachePlugin;
use Croustibat\FilamentJobsMonitor\FilamentJobsMonitorPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Gsferro\FilamentOdometerEasy\FilamentOdometerEasyPlugin;
use Harvirsidhu\FilamentCards\FilamentCardsPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use LaBoiteACode\DependencyGraph\DependencyGraphPlugin;
use LaBoiteACode\FilamentLogsExplorer\FilamentLogsExplorerPlugin;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;
use lockscreen\FilamentLockscreen\Lockscreen;
use MominAlZaraa\FilamentComposerReleaseNotifier\FilamentComposerReleaseNotifierPlugin;
use Prodstarter\FilamentNotificationCenter\FilamentNotificationCenterPlugin;
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;
use ShuvroRoy\FilamentSpatieLaravelHealth\FilamentSpatieLaravelHealthPlugin;
use SolutionForest\FilamentSimpleLightBox\SimpleLightBoxPlugin;
use Tapp\FilamentAuditing\FilamentAuditingPlugin;
use Tapp\FilamentAuthenticationLog\FilamentAuthenticationLogPlugin;
use Wezlo\FilamentSearchSpotlight\Categories\ActionsCategory;
use Wezlo\FilamentSearchSpotlight\Categories\RecordsCategory;
use Wezlo\FilamentSearchSpotlight\FilamentSearchSpotlightPlugin;

/**
 * Painel INFRA — observabilidade e manutenção: health checks, backups, filas,
 * logs, auditoria, caches, comandos operacionais, Pulse e observabilidade de IA.
 * Acesso: papéis `master_global` e `infra` (User::canAccessPanel).
 */
class InfraPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('infra')
            ->path('infra')
            ->login()
            ->passwordReset()
            ->brandName(config('app.name').' • Infra')
            ->colors(fn (): array => CorPrimaria::paleta())
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth(Width::Full)
            ->subNavigationPosition(SubNavigationPosition::Top)
            ->databaseNotifications()
            ->databaseNotificationsPolling(config('broadcasting.default') === 'reverb' ? null : '30s')
            ->discoverResources(in: app_path('Filament/Infra/Resources'), for: 'App\Filament\Infra\Resources')
            ->discoverPages(in: app_path('Filament/Infra/Pages'), for: 'App\Filament\Infra\Pages')
            ->discoverWidgets(in: app_path('Filament/Infra/Widgets'), for: 'App\Filament\Infra\Widgets')
            /*
             * Ordem explícita dos grupos — sem isto o Filament ordena alfabeticamente
             * e a navegação vira uma lista sem hierarquia de leitura. Cada plugin é
             * encaixado num destes quatro grupos logo abaixo, pelo mecanismo que ele
             * expõe (método do plugin, chave de config ou tradução).
             */
            ->navigationGroups([
                'Observabilidade',
                'IA',
                'Trilhas',
                'Sistema',
            ])
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->navigationItems([
                // Dashboard de estatísticas do fomvasss/laravel-ai-tasks (rota do
                // pacote, fora do Filament) — mesmo gate do resource Execuções de IA.
                NavigationItem::make('dashboard-ia')
                    ->label('Dashboard de IA')
                    ->group('IA')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn (): string => route('ai-tasks.index'), shouldOpenInNewTab: true)
                    ->visible(fn (): bool => auth()->user()?->can('ver-ai-tasks') ?? false),
            ])
            ->bootUsing(function (Panel $panel): void {
                // Registra as sugestões "Criar X" no request, com auth já resolvido.
                AcoesDeCriacao::registrar();

                // "Bloquear sessão" logo abaixo do "Meu perfil" — ver
                // TelaBloqueio::itemDeMenu(). A guarda espelha a do plugin: com o
                // kill-switch desligado a rota não existe e o item estouraria no render.
                if (config('lockscreen.enabled')) {
                    $panel->userMenuItems([TelaBloqueio::itemDeMenu($panel->getId())]);
                }
            })
            ->plugins([
                FilamentSearchSpotlightPlugin::make()
                    ->keyBinding(['mod+k'])
                    ->disableDefaultGlobalSearch()
                    ->resultLimitPerCategory(5)
                    ->actionsEnabled()
                    ->disableCreateActions()
                    ->placeholder('Buscar registros e telas...')
                    // As categorias do vendor NÃO checam canAccess(); as nossas checam.
                    ->categories([
                        RecordsCategory::class,
                        ResourcesAutorizadasCategory::class,
                        PagesAutorizadasCategory::class,
                        // Lê o registry alimentado por AcoesDeCriacao (as sugestões "Criar X").
                        ActionsCategory::class,
                    ]),

                AuthDesignerPlugin::make()
                    ->login(fn (AuthPageConfig $config): AuthPageConfig => $config
                        ->media(asset('images/auth/login.svg'), alt: config('app.name'))
                        ->mediaPosition(MediaPosition::Left)
                        ->mediaSize('70%')
                        ->themeToggle()
                    )
                    // Recuperação de senha espelhada — ver a nota no AppPanelProvider.
                    ->passwordReset(fn (AuthPageConfig $config): AuthPageConfig => $config
                        ->media(asset('images/auth/login.svg'), alt: config('app.name'))
                        ->mediaPosition(MediaPosition::Right)
                        ->mediaSize('70%')
                        ->themeToggle()
                    ),

                BreezyCore::make()
                    ->myProfile(shouldRegisterUserMenu: true, hasAvatars: true, slug: 'meu-perfil', userMenuLabel: 'Meu perfil')
                    ->enableTwoFactorAuthentication(),

                // Obrigatório em todos os painéis — ver nota no AdminPanelProvider.
                Lockscreen::make()
                    ->enablePlugin((bool) config('lockscreen.enabled'))
                    ->enableIdleTimeout((int) config('lockscreen.idle_timeout'))
                    ->enableRateLimit(limit: 5, decayMinutes: 5, forceLogout: true),

                // --- Observabilidade -------------------------------------------

                FilamentSpatieLaravelHealthPlugin::make()
                    ->navigationGroup('Observabilidade'),
                // Backup Monitor e Auditing não expõem grupo de navegação nem
                // rótulo traduzível: ficam soltos no topo do menu, antes dos grupos.
                FilamentBackupMonitorPlugin::make(),
                // Grupo vem de config/filament-jobs-monitor.php.
                FilamentJobsMonitorPlugin::make(),

                // Trilha de ACESSO (logins, novos dispositivos).
                FilamentAuthenticationLogPlugin::make(),

                // Trilha de ALTERAÇÃO (owen-it/laravel-auditing) — quem mudou o quê.
                FilamentAuditingPlugin::make(),

                /**
                 * Logs pela UI. deletable(false) é deliberado: o delete do pacote
                 * faz @unlink() sem gravar rastro nenhum — apagar trilha de
                 * evidências sem registro. Retenção é papel da rotação diária.
                 */
                FilamentLogsExplorerPlugin::make()
                    ->navigationGroup('Trilhas')
                    ->navigationSort(210)
                    ->canAccessUsing(fn (): bool => auth()->user()?->can('ver-logs') ?? false)
                    ->deletable(false),

                /**
                 * Mapa de arquitetura (models, relações, resources, painéis).
                 * canAccessUsing() SUBSTITUI a regra local-only do pacote — sem o
                 * callback a página é 404 fora de ambiente local, inclusive em
                 * homologação. Entrar no /infra já exige papel de infra.
                 */
                DependencyGraphPlugin::make()
                    ->navigationGroup('Sistema')
                    ->navigationSort(220)
                    ->canAccessUsing(fn (): bool => auth()->check()),

                // Releases dos pacotes Composer (informativo, nunca atualiza nada).
                FilamentComposerReleaseNotifierPlugin::make()
                    ->resource(enabled: true)
                    ->widget(enabled: true)
                    ->mailReports(enabled: false),

                // `cache:clear` só limpa o store default; os comandos extras
                // (config:clear, view:clear, modelCache:clear) entram pelo
                // KitServiceProvider::configureClearCacheButton().
                FilamentClearCachePlugin::make(),

                /**
                 * Central de comandos — execução de comandos pré-aprovados pela UI,
                 * com histórico. A trava real é a allow-list de config/command-center.php;
                 * o authorize() abaixo é o kill-switch + gate para o papel infra.
                 *
                 * SEM ->cluster(): com cluster a página raiz devolve 500
                 * ("Redirector could not be converted to int").
                 */
                CommandCenterPlugin::make()
                    ->navigationGroup('Sistema')
                    ->navigationSort(260)
                    ->authorize(fn (): bool => (bool) config('command-center.enabled', true)
                        && (auth()->user()?->can('command-center:access') ?? false)),

                EnvironmentIndicatorPlugin::make()
                    ->visible(fn (): bool => ! app()->isProduction()),

                FilamentOdometerEasyPlugin::make()
                    ->delay(1000)
                    ->duration(1500)
                    ->badgeOnCollapsedSidebar(),

                // Colunas redimensionáveis/fixáveis. Os defaults de tabela ficam em
                // App\Providers\Concerns\ConfiguraFilamentGlobal; aqui só a persistência.
                ResizedColumnPlugin::make()
                    ->preserveOnSession(),

                FilamentNotificationCenterPlugin::make(),

                /*
                 * Lightbox em imagem e documento de tabela.
                 *
                 * Registrado AQUI mesmo sem nenhuma coluna de mídia no /infra hoje: o plugin
                 * registra MACROS por painel, e a primeira `ImageColumn` que alguém criar neste
                 * painel derrubaria a tela com `BadMethodCallException` na renderização — uma
                 * falha cara e silenciosa até o clique, para economizar um `<script>`.
                 * Ver ADR-02 da wiki lightbox-em-imagens-e-documentos.
                 */
                SimpleLightBoxPlugin::make(),

                // Os gráficos do kit (App\Filament\Infra\Widgets\Ia*, FilasTaxaDeSucesso).
                FilamentApexChartsPlugin::make(),

                // Páginas hub em grade de cartões (App\Filament\Infra\Pages\HubDeInfraestrutura).
                FilamentCardsPlugin::make(),
            ])
            /*
             * Rótulos da Central de comandos em pt-BR. Precisa ser em bootUsing():
             * o register() do plugin escreve 'Commands'/'Run history' nas mesmas
             * propriedades estáticas — só sobrescreve quem roda depois.
             * Só rótulo, nunca slug (mudar slug por setter estático quebra a rota).
             */
            ->bootUsing(function (Panel $panel): void {
                CommandCenterCommands::navigationLabel('Comandos');
                CommandCenterHistory::navigationLabel('Histórico de execuções');
            })
            /*
             * Gatilho da busca ⌘K, no lugar exato do campo nativo.
             *
             * GLOBAL_SEARCH_BEFORE (e não USER_MENU_BEFORE, que renderiza
             * DENTRO do dropdown do usuário): o hook é emitido pela topbar
             * incondicionalmente — o `disableDefaultGlobalSearch()` guarda o
             * componente Livewire da busca, não o hook. Então a topbar mantém
             * a mesma aparência de sempre, e o clique abre o overlay.
             */
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): string => view('filament.spotlight-trigger')->render(),
            )
            /*
             * Cabeçalho de identidade: avatar, nome, e-mail e o badge do papel.
             *
             * USER_MENU_PROFILE_BEFORE renderiza DENTRO do dropdown, e é por isso
             * que ele serve aqui. Não contradiz o bloco de cima: lá o gatilho ⌘K
             * precisava ficar na TOPBAR, e foi esse mesmo fato que desqualificou o
             * USER_MENU_BEFORE. Mesmo comportamento, exigência oposta.
             */
            ->renderHook(
                PanelsRenderHook::USER_MENU_PROFILE_BEFORE,
                fn (): string => view('filament.user-menu-header')->render(),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

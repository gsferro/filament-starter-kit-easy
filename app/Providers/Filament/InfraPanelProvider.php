<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\TelaBloqueio;
use App\Filament\Pages\Auth\TelaDoisFatores;
use App\Filament\Spotlight\AcoesDeCriacao;
use App\Filament\Spotlight\PagesAutorizadasCategory;
use App\Filament\Spotlight\ResourcesAutorizadasCategory;
use App\Models\Projeto;
use App\Support\CorPrimaria;
use App\Support\IdentidadeDoKit;
use App\Support\RetencaoDeExcecoes;
use Asmit\ResizedColumn\ResizedColumnPlugin;
use BezhanSalleh\FilamentExceptions\FilamentExceptionsPlugin;
use Bityukov\CommandCenter\Filament\CommandCenterPlugin;
use Bityukov\CommandCenter\Filament\Pages\Commands as CommandCenterCommands;
use Bityukov\CommandCenter\Filament\Pages\History as CommandCenterHistory;
use Brimham\FilamentBackupMonitor\FilamentBackupMonitorPlugin;
use Carbon\Carbon;
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
use Promethys\Revive\RevivePlugin;
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;
use ShuvroRoy\FilamentSpatieLaravelHealth\FilamentSpatieLaravelHealthPlugin;
use SolutionForest\FilamentSimpleLightBox\SimpleLightBoxPlugin;
use Tapp\FilamentAuditing\FilamentAuditingPlugin;
use Tapp\FilamentAuthenticationLog\FilamentAuthenticationLogPlugin;
use Tapp\FilamentMailLog\FilamentMailLogPlugin;
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
            ->brandName(fn (): string => config('app.name').' • Infra')
            /*
             * Marca e ícone vindos de /admin/configuracoes-do-kit.
             *
             * `Closure` nos três, e não escalar: o argumento escalar é resolvido
             * quando o `Panel` é construído e CONGELA. Medido — `config(['app.name' => X])`
             * depois do boot não muda `getPanel()->getBrandName()`. A Closure é
             * avaliada no render, depois do alinhamento do KitServiceProvider. É a
             * mesma razão que `->colors()` acima já documenta. Ver ADR-02.
             *
             * `IdentidadeDoKit` devolve `null` quando não há arquivo utilizável, e
             * aí o Filament cai no brand em texto e no favicon dele — que é o
             * comportamento do kit antes desta feature.
             */
            ->brandLogo(fn (): ?string => IdentidadeDoKit::logo())
            ->brandLogoHeight('2rem')
            ->favicon(fn (): ?string => IdentidadeDoKit::favicon())
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
                        ->media(IdentidadeDoKit::arteDoLogin(), alt: config('app.name'))
                        ->mediaPosition(MediaPosition::Left)
                        ->mediaSize('70%')
                        ->themeToggle()
                    )
                    // Recuperação de senha espelhada — ver a nota no AppPanelProvider.
                    ->passwordReset(fn (AuthPageConfig $config): AuthPageConfig => $config
                        ->media(IdentidadeDoKit::arteDoLogin(), alt: config('app.name'))
                        ->mediaPosition(MediaPosition::Right)
                        ->mediaSize('70%')
                        ->themeToggle()
                    )
                    // Confirmação de e-mail: este bloco VESTE a tela (grava a chave
                    // 'email-verification' no AuthDesignerConfigRepository) e nada mais. Quem
                    // decide se ela entra no ar é o `->emailVerification(null, ...)` depois do
                    // `->plugins([...])` — ver a nota longa no AppPanelProvider e ADR-03.
                    ->emailVerification(fn (AuthPageConfig $config): AuthPageConfig => $config
                        ->media(IdentidadeDoKit::arteDoLogin(), alt: config('app.name'))
                        ->mediaPosition(MediaPosition::Right)
                        ->mediaSize('70%')
                        ->themeToggle()
                    ),

                BreezyCore::make()
                    ->myProfile(shouldRegisterUserMenu: true, hasAvatars: true, slug: 'meu-perfil', userMenuLabel: 'Meu perfil')
                    // A tela do desafio de 2FA com o layout do login — ver a nota no
                    // AppPanelProvider. `action:` nomeado de propósito: posicional cairia
                    // em `$condition`.
                    ->enableTwoFactorAuthentication(action: TelaDoisFatores::class),

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

                /*
                 * Exceções agrupadas por tipo e frequência.
                 *
                 * O /infra já mostrava SAÚDE (Health), DESEMPENHO (Pulse), ARQUIVO DE LOG
                 * (LogsExplorer) e FILAS (JobsMonitor) — e nenhum deles responde "qual
                 * exception está estourando, e quantas vezes". Achar isso no LogsExplorer
                 * exigia saber o dia e caçar dentro do arquivo.
                 *
                 * A retenção não é opcional: a tabela cresce por request com defeito, e um
                 * bug em laço enche o disco em horas. O prazo vem de
                 * `config('kit.retencao.excecoes_em_dias')`, que nasce em 14 para acompanhar
                 * o `days` da rotação em `config/logging.php` — a trilha morre junto com o
                 * log que a originou, não depois.
                 *
                 * `modelPruneInterval()` recebe a DATA DE CORTE, não uma quantidade de dias:
                 * o `Exception::prunable()` do pacote faz
                 * `whereDate('created_at', '<=', $intervalo)`. Passar `14` compararia
                 * `created_at` com o ano 14 e nunca podaria nada. O default do pacote é
                 * `now()->subWeek()`.
                 *
                 * Quem APLICA é o `model:prune` agendado em `routes/console.php` — sem o
                 * agendador rodando, isto aqui é só intenção declarada.
                 *
                 * Cuidado com o dado: o stack trace guardado pode conter parâmetro de
                 * request, logo pode conter dado pessoal. É parte do motivo de a tela viver
                 * só aqui, onde entrar já exige `master_global` ou `infra`.
                 */
                FilamentExceptionsPlugin::make()
                    ->navigationGroup('Observabilidade')
                    ->navigationSort(60)
                    ->navigationBadge()
                    /*
                     * `Carbon::now()` explícito, e não o helper `now()`: o kit faz
                     * `Date::use(CarbonImmutable::class)` no KitServiceProvider, então
                     * `now()` devolve `CarbonImmutable` — e a assinatura do pacote pede
                     * `Carbon` (o mutável). O PHPStan pega; em runtime seria TypeError.
                     */
                    /*
                     * O ramo do zero NÃO é preciosismo: sem ele, `subDays(0)` devolve AGORA, e
                     * o `Exception::prunable()` do pacote faz
                     * `whereDate('created_at', '<=', $intervalo)`
                     * (`vendor/bezhansalleh/filament-exceptions/src/Models/Exception.php:44`).
                     * `whereDate` compara só a DATA, então o corte de hoje casa com **toda** a
                     * tabela, inclusive as linhas de hoje — o `model:prune` seguinte apagava a
                     * trilha inteira. Negativo é pior: `subDays(-5)` joga o corte no futuro.
                     *
                     * E o bloco `retencao` do `config/kit.php` promete, por escrito, que "zero
                     * ou negativo desliga a poda daquela trilha". As três podas de
                     * `routes/console.php` honram isso com `if ($dias <= 0) return;`. Esta era
                     * a quarta, e fazia o oposto do documentado: apagava tudo.
                     *
                     * Um corte de cem anos atrás desliga de verdade, mantendo o contrato do
                     * pacote, que exige uma data e não aceita nulo.
                     */
                    ->modelPruneInterval(RetencaoDeExcecoes::corte()),

                /*
                 * Trilha de e-mail enviado.
                 *
                 * O kit envia `ConviteDeAcesso`, que é a ÚNICA porta de entrada de usuário, e
                 * não guardava registro nenhum. "O convite não chegou" era impossível de
                 * responder — não dava para separar "não foi enviado" de "foi enviado e caiu
                 * no spam".
                 *
                 * Mesma ressalva de dado pessoal do plugin acima, e mais forte: o corpo do
                 * e-mail é gravado, e o convite carrega o link de aceite. A retenção está no
                 * agendamento de `routes/console.php`, não no plugin.
                 *
                 * Sem `->navigationGroup()`: o resource lê grupo e ícone de
                 * `config/filament-maillog.php`, então rótulo em dois lugares seria um deles
                 * errado — mesmo motivo do jobs-monitor.
                 */
                FilamentMailLogPlugin::make(),

                /*
                 * Lixeira: restaura o que foi apagado com `SoftDeletes`.
                 *
                 * Aqui e NÃO no /app, apesar de o pacote suportar escopo por tenant. Duas
                 * razões: a lixeira varre models da instalação inteira, e uma tela que lista
                 * tudo que foi apagado é, ela mesma, exposição de dado. No /infra entrar já
                 * exige `master_global` ou `infra`; no /app qualquer papel do painel veria.
                 *
                 * `models()` explícito em vez de `modelsNamespace()`: a varredura automática
                 * de `app/Models` alcançaria `User`, `Role` e `Tenant`, cuja restauração tem
                 * consequência de AUTORIZAÇÃO — um usuário volta com papel numa organização
                 * que pode não existir mais. Lista explícita é a mesma escolha da allow-list
                 * do command-center: a trava é a lista, não o gate.
                 *
                 * `withoutScoping()` porque o /infra não tem tenancy: escopo por tenant aqui
                 * não teria de onde sair. Se um dia a lixeira for para o /app, é
                 * `enableTenantScoping()` que entra — e com CT provando o isolamento.
                 *
                 * Hoje só `Projeto` usa `SoftDeletes` no kit. A tela nasce com um model e
                 * cresce com o seu: acrescente aqui toda model que ganhar a trait.
                 */
                RevivePlugin::make()
                    ->navigationGroup('Sistema')
                    ->navigationLabel('Lixeira')
                    ->navigationSort(250)
                    ->models([
                        Projeto::class,
                    ])
                    ->withoutScoping(),

                // Páginas hub em grade de cartões (App\Filament\Infra\Pages\HubDeInfraestrutura).
                // Este painel NAO depende de config('kit.hub') — ver o docblock da Page.
                FilamentCardsPlugin::make(),
            ])
            /*
             * Confirmação de e-mail: o Auth Designer configurado, a ROTA desligada — ver a
             * nota longa no AppPanelProvider, inclusive os três passos para ligar.
             *
             * Em resumo: o `->emailVerification(...)` do plugin acima grava a chave
             * 'email-verification' no AuthDesignerConfigRepository (a tela já está vestida), e
             * este `null` apaga a ação da rota, para não expor uma tela que responde 500
             * enquanto `App\Models\User` não implementa `MustVerifyEmail`.
             */
            ->emailVerification(null, isRequired: false)
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

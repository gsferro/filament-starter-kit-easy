<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\TelaBloqueio;
use App\Filament\Spotlight\AcoesDeCriacao;
use App\Filament\Spotlight\PagesAutorizadasCategory;
use App\Filament\Spotlight\ResourcesAutorizadasCategory;
use App\Http\Middleware\DefinirTenantDePermissoes;
use App\Models\Tenant;
use Asmit\ResizedColumn\ResizedColumnPlugin;
use Caresome\FilamentAuthDesigner\AuthDesignerPlugin;
use Caresome\FilamentAuthDesigner\Data\AuthPageConfig;
use Caresome\FilamentAuthDesigner\Enums\MediaPosition;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Gsferro\FilamentOdometerEasy\FilamentOdometerEasyPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use LaBoiteACode\FilamentDashboardWidgets\FilamentDashboardWidgetsPlugin;
use lockscreen\FilamentLockscreen\Lockscreen;
use Prodstarter\FilamentNotificationCenter\FilamentNotificationCenterPlugin;
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;
use Wezlo\FilamentSearchSpotlight\Categories\ActionsCategory;
use Wezlo\FilamentSearchSpotlight\Categories\RecordsCategory;
use Wezlo\FilamentSearchSpotlight\FilamentSearchSpotlightPlugin;

/**
 * Painel APP — a operação de negócio. Vem vazio de propósito: é aqui que cada
 * novo projeto constrói suas features (Resources em app/Filament/App/).
 * Acesso: qualquer usuário autenticado — ajuste em User::canAccessPanel().
 */
class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('app')
            ->path('app')
            ->login()
            ->passwordReset()
            ->brandName(config('app.name'))
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth(Width::Full)
            ->subNavigationPosition(SubNavigationPosition::Top)
            ->databaseNotifications()
            ->databaseNotificationsPolling(config('broadcasting.default') === 'reverb' ? null : '30s')
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\Filament\App\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\Filament\App\Pages')
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\Filament\App\Widgets')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
            ])
            // Chat do assistente embarcado em TODA tela deste painel. Render hook (e não um
            // widget de dashboard) porque a superfície precisa acompanhar o usuário em
            // qualquer página. O próprio componente renderiza vazio quando não há usuário
            // autenticado — o hook também roda na tela de login.
            // Para tirar o assistente do painel, remova estas linhas; para tê-lo no admin ou
            // no infra, replique-as no PanelProvider correspondente.
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render('@livewire(\'assistente-chat-widget\')'),
            )
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
                    ),

                BreezyCore::make()
                    ->myProfile(shouldRegisterUserMenu: true, hasAvatars: true, slug: 'meu-perfil', userMenuLabel: 'Meu perfil')
                    ->enableTwoFactorAuthentication(),

                // Obrigatório em todos os painéis — ver nota no AdminPanelProvider.
                Lockscreen::make()
                    ->enablePlugin((bool) config('lockscreen.enabled'))
                    ->enableIdleTimeout((int) config('lockscreen.idle_timeout'))
                    ->enableRateLimit(limit: 5, decayMinutes: 5, forceLogout: true),

                // Bases prontas de widgets de dashboard (funil, timeline, metas,
                // segment bar...) para os indicadores do seu negócio.
                FilamentDashboardWidgetsPlugin::make(),

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
            ])
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

        /*
         * Multi-tenancy (opt-in) — ligue com `php artisan kit:tenancy`.
         *
         * Declarado DEPOIS de plugins() e middleware() de propósito: o
         * ->tenant() reescreve as rotas do painel para /app/{tenant}, e plugin
         * registrado depois disso não enxerga o prefixo.
         *
         * `IdentifyTenant` NÃO entra na lista: o Filament já o registra sozinho.
         * O tenantMiddleware recebe só o middleware do kit, e `isPersistent`
         * é o que o faz rodar também nos requests AJAX do Livewire — sem isso
         * o contexto de papéis se perde na primeira interação de tabela.
         *
         * Sem `->tenantRegistration()` de propósito: quem cria tenant é o
         * administrador, em /admin. Ligar o auto-cadastro deixaria qualquer
         * usuário autenticado criar tenants pelo painel de negócio.
         */
        if (config('kit.tenancy.enabled')) {
            $panel
                ->tenant(Tenant::class, slugAttribute: 'slug')
                ->tenantMiddleware([DefinirTenantDePermissoes::class], isPersistent: true);
        }

        return $panel;
    }
}

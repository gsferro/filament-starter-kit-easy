<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\TelaBloqueio;
use App\Filament\Pages\Auth\TelaDoisFatores;
use App\Filament\Spotlight\AcoesDeCriacao;
use App\Filament\Spotlight\PagesAutorizadasCategory;
use App\Filament\Spotlight\ResourcesAutorizadasCategory;
use App\Support\CorPrimaria;
use Asmit\ResizedColumn\ResizedColumnPlugin;
use BezhanSalleh\FilamentExceptions\FilamentExceptionsPlugin;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
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
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;
use lockscreen\FilamentLockscreen\Lockscreen;
use Prodstarter\FilamentNotificationCenter\FilamentNotificationCenterPlugin;
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;
use SolutionForest\FilamentSimpleLightBox\SimpleLightBoxPlugin;
use Wallacemartinss\FilamentOnboarding\FilamentOnboardingPlugin;
use Wezlo\FilamentSearchSpotlight\Categories\ActionsCategory;
use Wezlo\FilamentSearchSpotlight\Categories\RecordsCategory;
use Wezlo\FilamentSearchSpotlight\FilamentSearchSpotlightPlugin;

/**
 * Painel ADMIN — administração da aplicação: usuários, papéis e permissões
 * (Shield), catálogo de agentes de IA e autoria das jornadas de onboarding.
 * Acesso: papéis `master_global` e `admin` (User::canAccessPanel).
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->brandName(config('app.name').' • Admin')
            ->colors(fn (): array => CorPrimaria::paleta())
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth(Width::Full)
            ->subNavigationPosition(SubNavigationPosition::Top)
            // Com Reverb o sininho reage ao evento Echo (sem polling); sem ele,
            // volta o polling de 30s — senão o sininho "funciona" mas nunca atualiza.
            ->databaseNotifications()
            ->databaseNotificationsPolling(config('broadcasting.default') === 'reverb' ? null : '30s')
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
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
                // Busca ⌘K. O discovery de ações de criação do pacote fica fora:
                // ele monta getUrl('create') sem checar canCreate().
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

                // Login split: mídia à esquerda, formulário à direita.
                AuthDesignerPlugin::make()
                    ->login(fn (AuthPageConfig $config): AuthPageConfig => $config
                        ->media(asset('images/auth/login.svg'), alt: config('app.name'))
                        ->mediaPosition(MediaPosition::Left)
                        ->mediaSize('70%')
                        ->themeToggle()
                    )
                    ->passwordReset(fn (AuthPageConfig $config): AuthPageConfig => $config
                        ->media(asset('images/auth/login.svg'), alt: config('app.name'))
                        ->mediaPosition(MediaPosition::Right)
                        ->mediaSize('70%')
                        ->themeToggle()
                    )
                    // Confirmação de e-mail: este bloco VESTE a tela (grava a chave
                    // 'email-verification' no AuthDesignerConfigRepository) e nada mais. Quem
                    // decide se ela entra no ar é o `->emailVerification(null, ...)` depois do
                    // `->plugins([...])` — ver a nota longa no AppPanelProvider e ADR-03.
                    ->emailVerification(fn (AuthPageConfig $config): AuthPageConfig => $config
                        ->media(asset('images/auth/login.svg'), alt: config('app.name'))
                        ->mediaPosition(MediaPosition::Right)
                        ->mediaSize('70%')
                        ->themeToggle()
                    ),

                /**
                 * Papéis e permissões com UI (spatie/laravel-permission).
                 *
                 * Os três rótulos existem porque a tradução pt_BR do Shield diz "Funções"
                 * (`vendor/bezhansalleh/filament-shield/resources/lang/pt_BR/filament-shield.php:37`)
                 * e esse termo não aparece em nenhum outro lugar do kit: a coluna se chama
                 * "Papéis" em quatro tabelas, o helper de exibição é `App\Support\Papeis` e o
                 * seeder é o `PapeisSeeder`. Configurar aqui, e não publicar a tradução, porque
                 * `vendor:publish --force` sobrescreveria o arquivo de idioma.
                 */
                FilamentShieldPlugin::make()
                    ->modelLabel('Papel')
                    ->pluralModelLabel('Papéis')
                    ->navigationLabel('Papéis'),

                // Perfil do usuário + 2FA. O label explícito evita repetir o nome
                // do usuário duas vezes no dropdown.
                BreezyCore::make()
                    ->myProfile(shouldRegisterUserMenu: true, hasAvatars: true, slug: 'meu-perfil', userMenuLabel: 'Meu perfil')
                    // A tela do desafio de 2FA com o layout do login — ver a nota no
                    // AppPanelProvider. `action:` nomeado de propósito: posicional cairia
                    // em `$condition`.
                    ->enableTwoFactorAuthentication(action: TelaDoisFatores::class),

                /**
                 * Bloqueio de sessão. Precisa estar registrado em TODOS os painéis:
                 * o routes/web.php do pacote resolve o plugin pelo painel corrente
                 * e estoura LogicException em todo request num painel sem ele
                 * (até `artisan package:discover` morre).
                 */
                Lockscreen::make()
                    ->enablePlugin((bool) config('lockscreen.enabled'))
                    ->enableIdleTimeout((int) config('lockscreen.idle_timeout'))
                    ->enableRateLimit(limit: 5, decayMinutes: 5, forceLogout: true),

                /**
                 * AUTORIA das jornadas de onboarding — e só ela. O consumo
                 * (launcher/tours) pertence ao painel de negócio; a autoria fica
                 * onde entrar já exige papel de administração.
                 */
                FilamentOnboardingPlugin::make()
                    ->manageFlows((bool) config('filament-onboarding.enabled', true))
                    ->launcher(false)
                    ->tours(false),

                EnvironmentIndicatorPlugin::make()
                    ->visible(fn (): bool => ! app()->isProduction()),

                FilamentOdometerEasyPlugin::make()
                    ->delay(1000)
                    ->duration(1500)
                    // Sem isto o badge de contagem some com a sidebar recolhida.
                    ->badgeOnCollapsedSidebar(),

                // Colunas redimensionáveis/fixáveis. Os defaults de tabela ficam em
                // App\Providers\Concerns\ConfiguraFilamentGlobal; aqui só a persistência.
                ResizedColumnPlugin::make()
                    ->preserveOnSession(),

                FilamentNotificationCenterPlugin::make(),

                /*
                 * Lightbox em imagem e documento de tabela — o `->simpleLightbox()` das colunas.
                 *
                 * Registrado nos TRÊS painéis, inclusive no /infra, que hoje não tem mídia
                 * nenhuma. O plugin não configura nada: ele REGISTRA MACROS
                 * (`ImageColumn::macro('simpleLightbox', …)` e três irmãs) no `boot(Panel $panel)`
                 * dele. Macro é resolvido por `Macroable::__call()` no momento da chamada, então
                 * a primeira coluna de imagem criada num painel sem o plugin derruba a tela com
                 * `BadMethodCallException` — na RENDERIZAÇÃO, não no boot, e com uma mensagem que
                 * não menciona nem "painel" nem "plugin".
                 *
                 * A economia seria um `<script>` por página; o custo é um modo de falha caro e
                 * silencioso até o clique. Ver ADR-02 da wiki lightbox-em-imagens-e-documentos.
                 *
                 * Depois de instalar/atualizar: `php artisan filament:assets`. Sem o JS publicado
                 * o clique é INERTE, sem erro nenhum.
                 */
                SimpleLightBoxPlugin::make(),

                /*
                 * Gráficos do kit. Registrado só onde há gráfico — /admin e /infra.
                 * O primeiro gráfico criado no /app precisa registrar o plugin lá junto,
                 * pelo mesmo motivo do lightbox acima.
                 */
                FilamentApexChartsPlugin::make(),

                // Páginas hub em grade de cartões (App\Filament\Admin\Pages\HubDeAdministracao),
                // ligadas por config('kit.hub') — desligado no default do kit.
                FilamentCardsPlugin::make(),

                /*
                 * Registrado aqui SEM navegação — a tela pertence ao /infra.
                 *
                 * O `ExceptionResource` resolve o plugin pelo painel CORRENTE, e o
                 * filament-shield percorre todos os painéis no boot sem fixar qual é o
                 * corrente. Painel sem o plugin estoura `LogicException` em todo request e
                 * em todo comando artisan. É a mesma armadilha do `Lockscreen`; a saída é a
                 * mesma: registrar nos três, com navegação só onde a tela deve estar.
                 *
                 * Ver o comentário longo no AppPanelProvider e .ai/rules/filament.md §4 —
                 * o resource entra na matriz deste painel, e por isso na subtração do
                 * `panel_user`.
                 */
                FilamentExceptionsPlugin::make()
                    ->registerNavigation(false),
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

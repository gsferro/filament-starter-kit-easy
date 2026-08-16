<?php

namespace App\Providers\Filament;

use App\Filament\App\Pages\ConvitesRecebidos;
use App\Filament\Pages\Auth\RegistroPorConvite;
use App\Filament\Pages\Auth\TelaBloqueio;
use App\Filament\Pages\Auth\TelaLogin;
use App\Filament\Spotlight\AcoesDeCriacao;
use App\Filament\Spotlight\PagesAutorizadasCategory;
use App\Filament\Spotlight\ResourcesAutorizadasCategory;
use App\Http\Middleware\DefinirTenantDePermissoes;
use App\Models\Tenant;
use App\Support\CorPrimaria;
use Asmit\ResizedColumn\ResizedColumnPlugin;
use Caresome\FilamentAuthDesigner\AuthDesignerPlugin;
use Caresome\FilamentAuthDesigner\Data\AuthPageConfig;
use Caresome\FilamentAuthDesigner\Enums\MediaPosition;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentColor;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Gsferro\FilamentOdometerEasy\FilamentOdometerEasyPlugin;
use Harvirsidhu\FilamentCards\FilamentCardsPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use LaBoiteACode\FilamentDashboardWidgets\FilamentDashboardWidgetsPlugin;
use lockscreen\FilamentLockscreen\Lockscreen;
use Prodstarter\FilamentNotificationCenter\FilamentNotificationCenterPlugin;
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;
use SolutionForest\FilamentSimpleLightBox\SimpleLightBoxPlugin;
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
            // Closure, e não array: o valor precisa vir da config resolvida no
            // request, não da que existia quando o provider foi registrado.
            // A cor da organização, registrada no bootUsing() abaixo, vence esta.
            ->colors(fn (): array => CorPrimaria::paleta())
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
                ConvitesRecebidos::class,
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

                /*
                 * A cor da organização corrente.
                 *
                 * `FilamentColor::register()` com Closure, e NÃO `$panel->colors()`. Os dois
                 * aceitam Closure na assinatura, o que os faz parecer equivalentes — não são:
                 * `Panel::boot()` faz `FilamentColor::register($this->getColors())`
                 * (vendor/filament/filament/src/Panel.php:95) e o `getColors()` do painel avalia
                 * a Closure ali mesmo (Panel/Concerns/HasColors.php:31). Como o `Panel::boot()` é
                 * disparado pelo middleware `panel:{id}`/`SetUpPanel`, que é o PRIMEIRO da pilha
                 * (HasMiddleware.php:97-103), o `IdentifyTenant` ainda não rodou e
                 * `Filament::getTenant()` é sempre null. O código pareceria certo, rodaria sem
                 * erro e nunca aplicaria cor.
                 *
                 * O `register()` guarda a Closure e a avalia em `getColors()`
                 * (ColorManager.php:80), chamado por `AssetManager::renderStyles()`
                 * (AssetManager.php:286) — o `@filamentStyles`, no render do <head>, depois de
                 * todo middleware. É a única janela que serve.
                 *
                 * A guarda de painel não é defensiva, é obrigatória: `FilamentColor` é GLOBAL,
                 * não por painel. Sem ela, a cor de um cliente pintaria /admin e /infra também.
                 *
                 * Uma cor, não uma paleta: o ColorManager chama `Color::generatePalette()` sozinho
                 * quando recebe string (ColorManager.php:84-85).
                 *
                 * Ver ADR-02 da wiki `identidade-visual-da-organizacao`.
                 */
                FilamentColor::register(function (): array {
                    $tenant = Filament::getTenant();

                    // `instanceof Tenant` e não `?->`: `getTenant()` devolve `?Model`, e o model de
                    // tenancy é configurável — o narrowing é guarda real, não cerimônia de tipo.
                    if (Filament::getCurrentPanel()?->getId() !== 'app'
                        || ! $tenant instanceof Tenant
                        || blank($tenant->cor_primaria)) {
                        // Array vazio é o neutro: `getColors()` faz foreach sobre o resultado
                        // (ColorManager.php:82), então nada é sobrescrito e o default sobrevive.
                        return [];
                    }

                    Log::channel('tenancy')->debug(
                        '[AppPanelProvider@bootUsing] Cor da organização aplicada | tenant: '.$tenant->getKey(),
                        [
                            'tenant_id'    => $tenant->getKey(),
                            'tenant_slug'  => $tenant->slug,
                            'cor_primaria' => $tenant->cor_primaria,
                        ],
                    );

                    return ['primary' => $tenant->cor_primaria];
                });

                // "Bloquear sessão" logo abaixo do "Meu perfil" — ver
                // TelaBloqueio::itemDeMenu(). A guarda espelha a do plugin: com o
                // kill-switch desligado a rota não existe e o item estouraria no render.
                if (config('lockscreen.enabled')) {
                    $panel->userMenuItems([TelaBloqueio::itemDeMenu($panel->getId())]);
                }

                /*
                 * Convites recebidos, com a contagem das ofertas pendentes. Registrado
                 * aqui, e não em `->pages()`, porque o caminho é o menu do usuário: a
                 * página fica fora da navegação lateral e só aparece quando há algo a
                 * decidir. `itemDeMenu()` já cuida do `visible()`.
                 */
                $panel->userMenuItems([ConvitesRecebidos::itemDeMenu()]);
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
                        // A nossa tela de login: a única diferença é não oferecer
                        // "Cadastre-se", que o Filament acrescenta sozinho assim que o
                        // painel ganha registro.
                        ->usingPage(TelaLogin::class)
                        ->media(asset('images/auth/login.svg'), alt: config('app.name'))
                        ->mediaPosition(MediaPosition::Left)
                        ->mediaSize('70%')
                        ->themeToggle()
                    )
                    /*
                     * A tela de aceite do convite — a ÚNICA rota pública deste painel
                     * além do login. Sem token válido na query string ela recusa e
                     * manda para o login (App\Filament\Pages\Auth\RegistroPorConvite).
                     *
                     * Passa pelo PLUGIN, e não por `$panel->registration(...)` direto:
                     * é o plugin que grava a chave 'registration' no
                     * AuthDesignerConfigRepository (AuthDesignerPlugin.php:92-94). Sem
                     * ela o repositório cai em `new AuthPageConfig`
                     * (AuthDesignerConfigRepository.php:80) e a tela nasce sem mídia e
                     * sem alternador de tema — diferente do login ao lado, sem erro
                     * nenhum. Ver ADR-06.
                     *
                     * Para fechar o cadastro por completo, remova este bloco: a rota
                     * deixa de existir e a superfície pública desaparece.
                     */
                    ->registration(fn (AuthPageConfig $config): AuthPageConfig => $config
                        ->usingPage(RegistroPorConvite::class)
                        ->media(asset('images/auth/login.svg'), alt: config('app.name'))
                        ->mediaPosition(MediaPosition::Left)
                        ->mediaSize('70%')
                        ->themeToggle()
                    )
                    /*
                     * Recuperação de senha: o MESMO layout do login, ESPELHADO.
                     *
                     * `MediaPosition::Right` inverte o eixo (a CSS do pacote troca
                     * `row` por `row-reverse`), então a arte vai para a direita e o
                     * formulário para a esquerda. É a diferença que dá ao usuário um
                     * sinal imediato de que ele saiu do login — sem trocar cor, texto
                     * ou marca.
                     *
                     * Precisa passar pelo PLUGIN, e não por `$panel->passwordReset()`
                     * direto, pela mesma razão do registro logo acima: é o plugin que
                     * grava a chave 'password-reset' no AuthDesignerConfigRepository.
                     * Sem ela a tela nasce sem mídia e sem alternador de tema — sem
                     * erro nenhum. Ver ADR-06.
                     */
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

                /*
                 * Lightbox em imagem e documento de tabela. O plugin registra MACROS
                 * (`ImageColumn::macro('simpleLightbox', …)`) no `boot()` dele, por painel:
                 * coluna chamando `->simpleLightbox()` num painel sem o plugin derruba a tela
                 * com `BadMethodCallException` na renderização. Ver o comentário longo no
                 * AdminPanelProvider e o ADR-02 da wiki lightbox-em-imagens-e-documentos.
                 */
                SimpleLightBoxPlugin::make(),

                // Páginas hub em grade de cartões (App\Filament\App\Pages\HubDoNegocio).
                FilamentCardsPlugin::make(),
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

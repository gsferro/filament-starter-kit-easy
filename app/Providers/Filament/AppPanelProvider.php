<?php

namespace App\Providers\Filament;

use App\Filament\App\Pages\ConvitesRecebidos;
use App\Filament\Pages\Auth\RegistroPorConvite;
use App\Filament\Pages\Auth\TelaBloqueio;
use App\Filament\Pages\Auth\TelaDoisFatores;
use App\Filament\Pages\Auth\TelaLogin;
use App\Filament\Pages\MyProfilePage;
use App\Filament\Spotlight\AcoesDeCriacao;
use App\Filament\Spotlight\PagesAutorizadasCategory;
use App\Filament\Spotlight\ResourcesAutorizadasCategory;
use App\Http\Middleware\DefinirTenantDePermissoes;
use App\Models\Tenant;
use App\Support\CorPrimaria;
use App\Support\IdentidadeDoKit;
use App\Support\RegistroAberto;
use Asmit\ResizedColumn\ResizedColumnPlugin;
use BezhanSalleh\FilamentExceptions\FilamentExceptionsPlugin;
use Caresome\FilamentAuthDesigner\AuthDesignerPlugin;
use Caresome\FilamentAuthDesigner\Data\AuthPageConfig;
use Caresome\FilamentAuthDesigner\Enums\MediaPosition;
use Caresome\FilamentAuthDesigner\Pages\Auth\EmailVerification;
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
            ->brandName(fn (): string => config('app.name'))
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
                        ->media(IdentidadeDoKit::arteDoLogin(), alt: config('app.name'))
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
                     *
                     * `MediaPosition::Right` — ESPELHADO em relação ao login, igual à
                     * recuperação de senha logo abaixo e pela mesma razão: o eixo
                     * invertido é o sinal imediato de que se saiu do login, sem trocar
                     * cor, texto ou marca.
                     */
                    ->registration(fn (AuthPageConfig $config): AuthPageConfig => $config
                        ->usingPage(RegistroPorConvite::class)
                        ->media(IdentidadeDoKit::arteDoLogin(), alt: config('app.name'))
                        ->mediaPosition(MediaPosition::Right)
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
                        ->media(IdentidadeDoKit::arteDoLogin(), alt: config('app.name'))
                        ->mediaPosition(MediaPosition::Right)
                        ->mediaSize('70%')
                        ->themeToggle()
                    )
                    /*
                     * Confirmação de e-mail: este bloco VESTE a tela, e só isso.
                     *
                     * A verificação é OPCIONAL — `KIT_REGISTRO_VERIFICAR_EMAIL`, lida por
                     * `RegistroAberto::exigirVerificacaoDeEmail()`. Este bloco garante que,
                     * quando ela estiver ligada, a tela saia com a mesma arte das outras em vez
                     * de crua: quem grava a chave 'email-verification' no
                     * AuthDesignerConfigRepository é o `boot()` do plugin
                     * (AuthDesignerPlugin.php:99-101), e a gravação não depende da rota existir.
                     *
                     * Ele também faz o plugin chamar `$panel->emailVerification($classe)`
                     * (AuthDesignerPlugin.php:45-47) — com UM argumento, então o
                     * `bool $isRequired = true` do Filament (HasAuth.php:110) entra de
                     * tabela. Quem decide o que vai para o ar é o `->emailVerification(...)`
                     * logo depois do `->plugins([...])`, onde está a nota longa. Ver ADR-03 da
                     * wiki `auth-designer-telas` e ADR-05 da wiki `registro-e-aprovacao`.
                     */
                    ->emailVerification(fn (AuthPageConfig $config): AuthPageConfig => $config
                        ->media(IdentidadeDoKit::arteDoLogin(), alt: config('app.name'))
                        ->mediaPosition(MediaPosition::Right)
                        ->mediaSize('70%')
                        ->themeToggle()
                    ),

                BreezyCore::make()
                    ->myProfile(shouldRegisterUserMenu: true, hasAvatars: true, slug: 'meu-perfil', userMenuLabel: 'Meu perfil')
                    /*
                     * A tela de perfil do KIT no lugar da do pacote, e o motivo e' so' um: a do
                     * pacote nao declara `canAccess()`, entao `View:MyProfilePage` existia no banco
                     * e no checkbox de `/admin/shield/roles` sem decidir nada.
                     *
                     * `customMyProfilePage()` e' o ponto de extensao publicado
                     * (`src/Concerns/Plugin/HasMyProfile.php:30-38`), lido por
                     * `getMyProfilePageClass()` (`:151-154`) tanto no registro da Page
                     * (`BreezyCore.php:70`) quanto na URL do item do menu do usuario (`:115,120`)
                     * — os dois passam a apontar para a mesma classe.
                     *
                     * Nos TRES paineis, porque a tela existe nos tres com UMA permissao so'. Ver
                     * ADR-04 de
                     * `wikis/specs/feat/permissoes-de-telas-de-pacote/permissoes-de-telas-de-pacote/`.
                     */
                    ->customMyProfilePage(MyProfilePage::class)
                    // `action:` é o ponto de extensão do Breezy para a tela do desafio de
                    // 2FA — a rota do pacote pergunta ao plugin qual classe usar. A nossa
                    // troca o layout simples pelo do login. NOMEADO de propósito: `action`
                    // é o 3º parâmetro, e posicional cairia em `$condition`.
                    ->enableTwoFactorAuthentication(action: TelaDoisFatores::class),

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

                // Páginas hub em grade de cartões (App\Filament\App\Pages\HubDoNegocio),
                // ligadas por config('kit.hub') — desligado no default do kit.
                FilamentCardsPlugin::make(),

                /*
                 * Registrado aqui SEM navegação, e isso não é opcional.
                 *
                 * A tela de exceções pertence ao /infra, e só lá ela aparece. Mas o
                 * `ExceptionResource` do pacote resolve o plugin pelo painel CORRENTE — os
                 * métodos estáticos de navegação dele chamam `FilamentExceptionsPlugin::get()`,
                 * que é o helper `filament()`. E o filament-shield percorre
                 * `Filament::getPanels()` no boot para montar a matriz de permissões, sem
                 * fixar o painel corrente: a resolução cai no painel DEFAULT, que é este.
                 *
                 * Sem esta linha, `LogicException: Plugin [filament-exceptions] is not
                 * registered for panel [app]` derruba TODO request e TODO comando artisan —
                 * `migrate` e `inspire` inclusive. Medido, não suposto.
                 *
                 * Mesma armadilha do `Lockscreen` logo acima, mesma saída: registrar nos
                 * três. A diferença é o `registerNavigation(false)`, porque aqui a tela não
                 * deve aparecer.
                 *
                 * Consequência que NÃO pode ser esquecida: o resource passa a existir na
                 * matriz deste painel, então `Exception` entra na lista de subtração do
                 * `panel_user` no `PapeisSeeder`. Ver .ai/rules/filament.md §4.
                 */
                FilamentExceptionsPlugin::make()
                    ->registerNavigation(false),
            ])
            /*
             * Confirmação de e-mail: OPCIONAL, e a chave decide se a rota existe.
             *
             * Esta linha roda DEPOIS do `->plugins([...])` — `Panel::plugin()` registra o
             * plugin na hora (Panel/Concerns/HasPlugins.php:15-21), então quem fala por último
             * vence, e é aqui que se decide. Com `null` no primeiro parâmetro a AÇÃO da rota
             * que o plugin havia registrado é apagada: `hasEmailVerification()` é
             * `filled($action)` (HasAuth.php:620-623), fica falso, e nenhuma rota nasce
             * (vendor/filament/filament/routes/web.php:75-84). Com a classe, as duas rotas
             * nascem e o `isRequired: true` acrescenta o middleware de verificação a cada rota
             * de página do painel (Pages/Concerns/HasRoutes.php:91).
             *
             * Sobrevive dos dois lados o que o Auth Designer gravou: a chave
             * 'email-verification' no AuthDesignerConfigRepository, com mídia, eixo e
             * alternador de tema — a gravação acontece no `boot()` do plugin
             * (AuthDesignerPlugin.php:99-101) e não depende da rota. A tela nasce vestida;
             * a chave decide se ela está no ar.
             *
             * Não use CLOSURE no primeiro parâmetro para tentar adiar a decisão: a assinatura
             * aceita (HasAuth.php:110), mas `filled(Closure)` é sempre `true` e a rota
             * nasceria SEMPRE, inclusive com a opção desligada.
             *
             * Os três passos que este arquivo listava para "ligar um dia" viraram código:
             *   1. `App\Models\User implements MustVerifyEmail` — feito. Sem ele a tela
             *      responde 500, porque `EmailVerificationPrompt::getVerifiable()` declara
             *      retorno `MustVerifyEmail`
             *      (vendor/filament/filament/src/Auth/Pages/EmailVerification/EmailVerificationPrompt.php:36-43)
             *      e é chamada no `mount()` (:31), e o middleware do Laravel não barra ninguém
             *      (Illuminate/Auth/Middleware/EnsureEmailIsVerified.php:32-40);
             *   2. a classe da tela, condicionada abaixo;
             *   3. o `isRequired`, condicionado pela mesma chave.
             *
             * CORREÇÃO DE FATO: a versão anterior desta nota afirmava que "NENHUM usuário
             * semeado tem `email_verified_at`". Era falso, e a afirmação sustentava a decisão
             * de não ligar. Cinco dos sete caminhos que criam usuário no kit gravam a coluna:
             * UsuarioAdminSeeder.php:45, UserFactory.php:30, DemoTenancySeeder.php:103,
             * Convite::aceitar() em Convite.php:591 e KitAdmin.php:204. Os dois que NÃO gravam
             * são a tela de usuários do /admin e a do /app — quem for criado por ali com a
             * opção ligada recebe o prompt de verificação, que é o comportamento correto.
             *
             * O que ligar a opção realmente custa: o middleware barra TODO usuário do /app sem
             * `email_verified_at`, não só os recém-registrados. Numa instalação limpa isso não
             * atinge ninguém; numa base legada, atinge quem foi criado pela tela. O README traz
             * o reparo.
             *
             * Quem NUNCA é afetado é o convidado: `Convite::aceitar()` grava a coluna de
             * propósito (o token já prova posse do endereço), então
             * `Register::sendEmailVerificationNotification()` pula o envio para ele
             * (Register.php:167-169). Ver ADR-05 da wiki `registro-e-aprovacao`.
             */
            ->emailVerification(
                RegistroAberto::exigirVerificacaoDeEmail() ? EmailVerification::class : null,
                isRequired: RegistroAberto::exigirVerificacaoDeEmail(),
            )
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

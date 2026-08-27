<?php

namespace App\Providers\Filament;

use App\Filament\App\Pages\ConvitesRecebidos;
use App\Filament\Pages\Auth\RegistroPorConvite;
use App\Filament\Pages\Auth\TelaBloqueio;
use App\Filament\Pages\Auth\TelaDoisFatores;
use App\Filament\Pages\Auth\TelaLogin;
use App\Filament\Pages\Auth\TelaRecuperarSenha;
use App\Filament\Pages\MyProfilePage;
use App\Filament\Spotlight\AcoesDeCriacao;
use App\Filament\Spotlight\PagesAutorizadasCategory;
use App\Filament\Spotlight\ResourcesAutorizadasCategory;
use App\Http\Middleware\DefinirTenantDePermissoes;
use App\Http\Middleware\ExigirEmailVerificado;
use App\Livewire\DefinirSenhaPorEmail;
use App\Models\Tenant;
use App\Support\CorPrimaria;
use App\Support\IdentidadeDoKit;
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
                        // A página do PEDIDO é a do kit, por causa do desafio anti-robô; a de
                        // redefinição (com token do e-mail) continua a do vendor. Ver TelaRecuperarSenha.
                        ->usingPage(TelaRecuperarSenha::class)
                        ->media(IdentidadeDoKit::arteDoLogin(), alt: config('app.name'))
                        ->mediaPosition(MediaPosition::Right)
                        ->mediaSize('70%')
                        ->themeToggle()
                    )
                    /*
                     * Confirmação de e-mail: este bloco VESTE a tela, e só isso.
                     *
                     * A tela existe SEMPRE (ver a nota longa do `->emailVerification()` abaixo);
                     * quem decide se alguém é levado até ela é o middleware do kit, por request.
                     * Este bloco só a VESTE, para que ela saia com a mesma arte das outras em vez
                     * de crua: quem grava a chave 'email-verification' no
                     * AuthDesignerConfigRepository é o `boot()` do plugin
                     * (AuthDesignerPlugin.php:99-101), e a gravação não depende da rota existir.
                     *
                     * Ele também faz o plugin chamar `$panel->emailVerification($classe)`
                     * (AuthDesignerPlugin.php:45-47) — com UM argumento, então o
                     * `bool $isRequired = true` do Filament (HasAuth.php:110) entra de
                     * tabela. O `->emailVerification(...)` logo depois do `->plugins([...])`
                     * reafirma a mesma coisa e é quem fala por último. Ver ADR-03 da wiki
                     * `auth-designer-telas`, ADR-05 da wiki `registro-e-aprovacao` e ADR-01/ADR-03
                     * da wiki `verificacao-de-email-editavel`.
                     */
                    ->emailVerification(fn (AuthPageConfig $config): AuthPageConfig => $config
                        ->media(IdentidadeDoKit::arteDoLogin(), alt: config('app.name'))
                        ->mediaPosition(MediaPosition::Right)
                        ->mediaSize('70%')
                        ->themeToggle()
                    ),

                BreezyCore::make()
                    ->myProfile(shouldRegisterUserMenu: true, hasAvatars: true, slug: 'meu-perfil', userMenuLabel: 'Meu perfil')
                    // Quem entrou por login social não tem senha atual — e a troca de senha, o 2FA e o
                    // desbloqueio da sessão pedem uma. O bloco manda o link de definição por e-mail.
                    ->myProfileComponents(['definir_senha_por_email' => DefinirSenhaPorEmail::class])
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
             * Confirmação de e-mail: SEMPRE aplicada, e quem decide é o middleware do kit.
             *
             * Esta é a linha que paga a dívida da v0.19.1. A versão anterior condicionava as
             * duas coisas — a classe da tela e o `isRequired` — a
             * `RegistroAberto::exigirVerificacaoDeEmail()`, lida AQUI, no boot. Duas
             * consequências, e as duas foram medidas pelo quality gate:
             *
             *   1. o painel é montado antes de `ConfiguracoesDoKit::aplicarNaConfig()`, então o
             *      valor gravado no banco chegava tarde;
             *   2. pior, o middleware de e-mail verificado é fixado no ARRAY DA ROTA no momento
             *      do registro (Pages/Concerns/HasRoutes.php:91), e array de rota registrada não
             *      é reavaliado por request. Nem Closure em `isRequired` resolveria: quem chama
             *      `isEmailVerificationRequired()` é o registro da rota, não o request.
             *
             * A saída não é combater o array fixo — é mudar o que está fixado nele. A exigência
             * entra SEM condição, e `->emailVerifiedMiddlewareName()` troca o alias `verified`
             * do Filament (HasAuth.php:26) pela classe do kit. `getEmailVerifiedMiddleware()`
             * concatena nome e rota de destino (HasAuth.php:367-370), então o array da rota passa
             * a conter `ExigirEmailVerificado:filament.app.auth.email-verification.prompt`: um
             * DECISOR, não uma decisão. A pergunta é feita a cada request, dentro dele.
             *
             * POR ISSO a classe da tela é incondicional, e isto não é detalhe de estilo. É
             * `hasEmailVerification()` — `filled($action)` (HasAuth.php:620-622) — que faz as duas
             * rotas de verificação nascerem (vendor/filament/filament/routes/web.php:75-84). Com
             * a condição de volta, ligar a opção pela tela mandaria a pessoa para uma rota que
             * não existe: `RouteNotFoundException`, um 500 em vez de tela. Ver ADR-03.
             *
             * A rota do prompt NÃO é registrada por `Page::registerRoutes()`, e é isso que
             * impede o laço: ela nasce de um `Route::get()` direto no `routes/web.php` do
             * Filament, então não recebe `getRouteMiddleware()` — o destino do redirecionamento
             * não é guardado pelo próprio middleware que redireciona.
             *
             * Esta linha continua tendo de rodar DEPOIS do `->plugins([...])`: `Panel::plugin()`
             * registra o plugin na hora (Panel/Concerns/HasPlugins.php:15-21) e o Auth Designer
             * chama `$panel->emailVerification($classe)` no `register()`
             * (AuthDesignerPlugin.php:45-47). Quem fala por último vence, e é aqui.
             *
             * O que o Auth Designer gravou sobrevive: a chave 'email-verification' no
             * AuthDesignerConfigRepository, com mídia, eixo e alternador de tema
             * (AuthDesignerPlugin.php:99-101). A tela nasce vestida.
             *
             * `App\Models\User implements MustVerifyEmail` é pré-requisito e já está feito. Sem
             * ele a tela responde 500 — `EmailVerificationPrompt::getVerifiable()` declara
             * retorno `MustVerifyEmail`
             * (vendor/filament/filament/src/Auth/Pages/EmailVerification/EmailVerificationPrompt.php:36-43)
             * e é chamada no `mount()` (:31). E o contrato é GLOBAL: o que mantém /admin e /infra
             * fora disso é aqueles painéis não pedirem verificação — o default do Filament é
             * `isEmailVerificationRequired = false` (HasAuth.php:56) —, não o model.
             *
             * O que a opção LIGADA custa continua sendo o mesmo, e agora um clique basta: o
             * middleware barra TODO usuário do /app sem `email_verified_at`, não só os
             * recém-registrados. Numa instalação limpa isso não atinge ninguém — cinco dos sete
             * caminhos que criam usuário gravam a coluna (UsuarioAdminSeeder.php:45,
             * UserFactory.php:30, DemoTenancySeeder.php:103, Convite.php:591, KitAdmin.php:204);
             * os dois que não gravam são a tela de usuários do /admin e a do /app. Numa base
             * legada, atinge quem foi criado por ali, e o README traz o reparo.
             *
             * Quem NUNCA é afetado é o convidado: `Convite::aceitar()` grava a coluna de
             * propósito (o token já prova posse do endereço), então
             * `Register::sendEmailVerificationNotification()` pula o envio para ele
             * (Register.php:161-167). Ver ADR-05 da wiki `registro-e-aprovacao`.
             */
            ->emailVerification(EmailVerification::class)
            ->emailVerifiedMiddlewareName(ExigirEmailVerificado::class)
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

<?php

namespace App\Providers;

use App\Ai\Health\LocalAiCheck;
use App\Ai\Listeners\RegistrarAiRun;
use App\Models\Tenant;
use App\Models\User;
use App\Providers\Concerns\ConfiguraFilamentGlobal;
use App\Settings\ConfiguracoesDoKit;
use App\Support\PoliciesDeVendor;
use App\Support\TetoDeUpload;
use Carbon\CarbonImmutable;
use CmsMulti\FilamentClearCache\Facades\FilamentClearCache;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Imports\Events\ImportCompleted;
use Filament\Actions\Imports\Events\ImportStarted;
use Filament\Facades\Filament;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\AgentStreamed;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

/**
 * Provider "cola" do starter-kit: defaults do framework, gates, health checks
 * e a configuração global do Filament. Tudo que o kit adiciona ao Laravel cru
 * e que não pertence a um painel específico mora aqui.
 */
class KitServiceProvider extends ServiceProvider
{
    use ConfiguraFilamentGlobal;

    public function boot(): void
    {
        $this->configureDefaults();

        /*
         * ANTES de `configuraFilamentGlobal()`, e a ordem é requisito: aquele
         * método lê `kit.tabelas.*`, que é justamente o que este alinha.
         */
        $this->configureSettingsDoKit();

        $this->configureTenancy();
        $this->configureGates();
        $this->configureAiLedger();
        $this->configureRastroDeImportExport();
        $this->configureHealthChecks();
        $this->configureClearCacheButton();
        $this->configureProcessEnvNoWindows();
        $this->configuraFilamentGlobal();
        $this->configureCorrecoesDeCss();
        $this->configureTelaDeLogin();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(app()->isProduction());

        Password::defaults(fn (): Password => app()->isProduction()
            ? Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()
            : Password::min(8));

        $this->configureTetoDeUpload();
    }

    /**
     * O teto do upload TEMPORÁRIO do Livewire, alinhado à chave do kit.
     *
     * Um upload atravessa quatro limites e o MENOR manda, mas eles não recusam
     * igual: o `->maxSize()` do campo devolve mensagem em português no
     * formulário, enquanto o do Livewire devolve 422 no XHR do upload
     * temporário e o FilePond mostra um erro genérico — e o do PHP e do nginx
     * cortam o corpo do POST, o que aparece como falha de rede no console.
     *
     * Sem esta linha, o default do Livewire é `max:12288`
     * (vendor/livewire/livewire/src/Features/SupportFileUploads/FileUploadConfiguration.php:116)
     * — 12 MB, fixo. É mais frouxo que os 10 MB de fábrica do kit e mais
     * ESTREITO no instante em que alguém sobe `KIT_UPLOAD_MAXIMO_MB` acima de 12:
     * todo arquivo entre 12 MB e o novo teto falharia sem nenhuma mensagem sobre
     * tamanho. A promessa de que a chave é fácil de mudar só vale se ela mudar as
     * duas camadas.
     *
     * E a conta é `emKbComFolgaDoLivewire()`, não `emKb()`: igualar os dois tetos
     * torna a mensagem de erro do campo INALCANÇÁVEL, porque o Livewire recusa
     * antes de o formulário validar. O docblock daquele método tem a medição.
     *
     * `config()->set()` em vez de publicar o `config/livewire.php`: o projeto
     * não tem esse arquivo, e publicá-lo traria ~130 linhas de configuração
     * alheia para o repositório só para mudar um número — que passaria a ter
     * duas donas. Ver ADR-04 em
     * wikis/specs/feat/upload-limite-e-tipos/upload-limite-e-tipos/.
     */
    protected function configureTetoDeUpload(): void
    {
        config()->set('livewire.temporary_file_upload.rules', [
            'required',
            'file',
            'max:'.TetoDeUpload::emKbComFolgaDoLivewire(),
        ]);
    }

    /**
     * Contexto de papéis padrão do processo, no modo multi-tenant.
     *
     * Com `permission.teams` ligado, o spatie exige um `team_id` em toda
     * atribuição de papel (a coluna é NOT NULL). Sem um valor default, seeder,
     * comando, job e os painéis /admin e /infra — que não têm tenant — quebram
     * com violação de constraint.
     *
     * Aqui fica o contexto GLOBAL. O painel /app sobrescreve por request com o
     * tenant corrente, via App\Http\Middleware\DefinirTenantDePermissoes.
     */
    protected function configureTenancy(): void
    {
        if (! config('kit.tenancy.enabled')) {
            return;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId(Tenant::CONTEXTO_GLOBAL);
    }

    /**
     * A configuração gravada em /admin/configuracoes-do-kit, aplicada ao processo.
     *
     * Este é o ponto único que faz o banco vencer o `.env` em tempo de execução —
     * e o que permite que NENHUM consumidor mude: `CorPrimaria::paleta()`, os três
     * painéis, `configuraTable()` e o MailManager do Laravel continuam lendo
     * `config()` sem saber que o settings existe. Ver ADR-01 da wiki
     * `settings-do-kit`.
     *
     * ## Falha para o lado do `.env`, sempre
     *
     * Isto roda em TODO request e TODO comando artisan. Um `Throwable` aqui
     * derrubaria a aplicação inteira, não uma tela — então o `catch` é de
     * `Throwable` (não `Exception`: `TypeError` e `Error` escapariam) e envolve
     * inclusive o `Schema::hasTable()`, que num banco inexistente **lança antes de
     * responder**. É exatamente o que acontece no primeiro `migrate` de uma
     * instalação nova.
     *
     * O `hasTable()` falso sai em SILÊNCIO, sem log: tabela ausente é o estado
     * normal de uma instalação nova, e um `warning` ali gritaria em todo `migrate`
     * de todo mundo — canal que grita no caminho feliz é canal que ninguém lê.
     * Banco quebrado, ao contrário, é anomalia e precisa aparecer.
     *
     * ## Efeito colateral desejado na suíte de testes
     *
     * Com `RefreshDatabase`, o `boot()` roda ANTES das migrations, então a tabela
     * não existe e este método é inerte. É por isso que os valores forçados no
     * `phpunit.xml` (`KIT_COR_PRIMARIA`, `KIT_HUB`, os rótulos de organização)
     * continuam valendo e a suíte não precisou de arranjo nenhum.
     */
    protected function configureSettingsDoKit(): void
    {
        try {
            $tabela = config('settings.repositories.database.table') ?? 'settings';

            if (! Schema::hasTable($tabela)) {
                return;
            }

            app(ConfiguracoesDoKit::class)->aplicarNaConfig();
        } catch (Throwable $e) {
            Log::channel('configuracoes')->warning(
                '[KitServiceProvider@configureSettingsDoKit] Configuração do banco ignorada, valendo o .env | motivo: '.$e->getMessage(),
                ['exception' => $e],
            );
        }
    }

    protected function configureGates(): void
    {
        // master_global vence qualquer gate SEM precisar de permissions no banco.
        // Retornar null (não false) deixa os demais checks seguirem o fluxo normal.
        Gate::before(fn (User $user) => $user->isMasterGlobal() ? true : null);

        // Superfícies de infra/observabilidade. master_global passa pelo before; quem
        // tem papel do painel /infra recebe acesso explícito aqui.
        //
        // `temPapelDoPainel()` e não `hasRole('infra')`: a relação `roles` do spatie é
        // filtrada pelo team corrente quando `permission.teams` está ligado, então o
        // mesmo gate responderia diferente conforme a organização aberta no request. A
        // pergunta é sobre a instalação, não sobre a organização — daí o contexto global.
        $doPainelInfra = fn (User $user): bool => $user->temPapelDoPainel(
            'infra',
            config('permission.teams') ? Tenant::CONTEXTO_GLOBAL : null,
        );

        Gate::define('ver-ai-tasks', $doPainelInfra);
        Gate::define('ver-logs', $doPainelInfra);
        Gate::define('command-center:access', $doPainelInfra);
        Gate::define('viewPulse', $doPainelInfra);

        /*
         * As policies dos modelos de VENDOR não são descobertas pelo Laravel (só `App\Models\*`
         * é). Sem esta linha, oito resources abriam com a permissão revogada — ver a classe.
         */
        PoliciesDeVendor::registrar();

        // command-center:prune-history e :manage-commands ficam deliberadamente
        // SEM define: um `fn () => false` seria vencido pelo Gate::before do
        // master_global de qualquer forma, e ninguém mais deve tê-los.
    }

    /**
     * Ledger de IA: toda execução de agente (prompt e stream) vira uma linha em `ai_runs`,
     * que alimenta o resource "Execuções de IA" (painel infra), o dashboard /ai-tasks do
     * pacote e o guard de budget.
     *
     * Os dois eventos são necessários: `AgentPrompted` cobre `prompt()`/`queue()` e
     * `AgentStreamed` cobre `stream()` (é disparado ao FIM do stream). O listener é
     * blindado — falha ao gravar nunca derruba a execução do agente.
     */
    protected function configureAiLedger(): void
    {
        Event::listen([AgentPrompted::class, AgentStreamed::class], RegistrarAiRun::class);
    }

    /**
     * Rastro de import e export no channel `tenancy` — sem tabela nova.
     *
     * O rastro **já existe** em `imports` e `exports`: quem, qual importador, quantas
     * linhas, quando terminou. O que falta lá é o que interessa numa auditoria de
     * vazamento — de qual ORGANIZAÇÃO saiu o arquivo — porque as duas tabelas são do
     * pacote e não têm `tenant_id`. É o que o log acrescenta, e é por isso que o channel é
     * o `tenancy`: o assunto é cruzamento de organização.
     *
     * **Import tem eventos; export não.** `ImportStarted`/`ImportCompleted` são eventos de
     * verdade do Filament. Do lado do export não existe nenhum
     * (`vendor/filament/actions/src/Exports/` não tem diretório `Events/`), então o gancho
     * é o próprio model: `created` marca o pedido e o `completed_at` recém-preenchido
     * marca a conclusão.
     *
     * O `wasChanged('completed_at')` não é preciosismo: a `ExportAction` salva a linha
     * DUAS vezes antes de qualquer job rodar — uma para obter o id do diretório e outra
     * com o `file_name` — e sem o filtro cada export renderia três registros de
     * "concluído".
     */
    protected function configureRastroDeImportExport(): void
    {
        Event::listen(ImportStarted::class, function (ImportStarted $evento): void {
            Log::channel('tenancy')->info(
                '[KitServiceProvider@configureRastroDeImportExport] Importação iniciada'
                ." | import_id: {$evento->getImport()->getKey()}"
                .' | importer: '.$evento->getImport()->importer
                .' | tenant_id: '.($evento->getOptions()['tenant_id'] ?? 'sem organização')
                .' | user_id: '.($evento->getImport()->user_id ?? 'sem usuário'),
                [
                    'import_id' => $evento->getImport()->getKey(),
                    'importer'  => $evento->getImport()->importer,
                    'tenant_id' => $evento->getOptions()['tenant_id'] ?? null,
                    'user_id'   => $evento->getImport()->user_id,
                ],
            );
        });

        Event::listen(ImportCompleted::class, function (ImportCompleted $evento): void {
            $import = $evento->getImport();

            Log::channel('tenancy')->info(
                '[KitServiceProvider@configureRastroDeImportExport] Importação concluída'
                ." | import_id: {$import->getKey()}"
                ." | linhas_ok: {$import->successful_rows}"
                .' | linhas_falhas: '.$import->getFailedRowsCount()
                .' | tenant_id: '.($evento->getOptions()['tenant_id'] ?? 'sem organização'),
                [
                    'import_id'      => $import->getKey(),
                    'successful'     => $import->successful_rows,
                    'failed'         => $import->getFailedRowsCount(),
                    'tenant_id'      => $evento->getOptions()['tenant_id'] ?? null,
                ],
            );
        });

        Export::created(function (Export $export): void {
            Log::channel('tenancy')->info(
                '[KitServiceProvider@configureRastroDeImportExport] Exportação solicitada'
                ." | export_id: {$export->getKey()}"
                ." | exporter: {$export->exporter}"
                .' | tenant_id: '.(Filament::getTenant()?->getKey() ?? 'sem organização')
                .' | user_id: '.($export->user_id ?? 'sem usuário'),
                [
                    'export_id' => $export->getKey(),
                    'exporter'  => $export->exporter,
                    'tenant_id' => Filament::getTenant()?->getKey(),
                    'user_id'   => $export->user_id,
                ],
            );
        });

        Export::updated(function (Export $export): void {
            if (! $export->wasChanged('completed_at') || $export->completed_at === null) {
                return;
            }

            Log::channel('tenancy')->info(
                '[KitServiceProvider@configureRastroDeImportExport] Exportação concluída'
                ." | export_id: {$export->getKey()}"
                ." | linhas_ok: {$export->successful_rows}"
                .' | linhas_falhas: '.$export->getFailedRowsCount(),
                [
                    'export_id'  => $export->getKey(),
                    'successful' => $export->successful_rows,
                    'failed'     => $export->getFailedRowsCount(),
                ],
            );
        });
    }

    /**
     * Health checks padrão — adicione/remova conforme o projeto.
     * A página fica no painel infra; agendamento em routes/console.php.
     */
    /**
     * Registra o CSS de correções do kit nos três painéis.
     *
     * Pelo `FilamentAsset::register()`, que é o MESMO mecanismo dos plugins — e não pelo
     * `resources/css/app.css`: o painel Filament não carrega o Vite da aplicação, então uma regra
     * escrita lá nunca chega à tela. (Foi assim que a primeira tentativa de corrigir a cor do
     * alternador de painel falhou em silêncio.)
     *
     * Registrado no fim do `boot()` de propósito: os assets saem na ordem de registro, e a regra
     * do kit precisa vir DEPOIS da do `filament-jobs-monitor`, que é quem sequestra as
     * utilitárias. O motivo completo está no cabeçalho de `resources/css/filament/kit.css`.
     *
     * Depois de mexer no arquivo: `php artisan filament:assets`.
     */
    protected function configureCorrecoesDeCss(): void
    {
        FilamentAsset::register(
            [
                Css::make('kit-correcoes', resource_path('css/filament/kit.css')),
                /*
                 * O estilo das páginas hub em cartões. Vive aqui, e não num tema Filament, porque
                 * o `harvirsidhu/filament-cards` não registra CSS nenhum e a CSS pré-compilada do
                 * Filament 5 carrega quase só as classes `fi-*` — 51 das 53 utilitárias que a
                 * blade do pacote emite não existem lá. Tudo escopado em `.kit-cards-page`.
                 * Ver o cabeçalho de `resources/css/filament/cards.css`.
                 */
                Css::make('kit-cards', resource_path('css/filament/cards.css')),
                /*
                 * O overlay da busca ⌘K (`wezlo/filament-search-spotlight`). Mesmo caso do
                 * `cards.css`, mais grave: a blade do pacote emite 66 utilitárias e a CSS do
                 * Filament não tem NENHUMA — sem isto o overlay abre `fixed` sem `inset-0`,
                 * a 1.800 px do topo, fora da tela. Escopado no atributo Alpine da raiz do
                 * componente. Ver o cabeçalho de `resources/css/filament/spotlight.css`.
                 */
                Css::make('kit-spotlight', resource_path('css/filament/spotlight.css')),
            ],
            package: 'kit',
        );
    }

    /**
     * As superficies que o kit acrescenta a tela de login dos TRES paineis: o botao de login
     * social e o rodape.
     *
     * `FilamentView::registerRenderHook()` e nao `$panel->renderHook()`: sem `$scopes` o hook
     * cai no escopo vazio (`vendor/filament/support/src/View/ViewManager.php:32-34`), e o
     * `renderHook()` renderiza o escopo vazio em QUALQUER escopo pedido (`:93-96`). Uma
     * registracao cobre os tres paineis. Pelo painel seriam tres blocos identicos em tres
     * providers — e o defeito historico do kit nessa area e exatamente configurar um painel e
     * esquecer os outros dois, como diz o docblock de
     * `tests/Kit/TelasDeAutenticacaoTest.php`.
     *
     * `AUTH_LOGIN_FORM_AFTER` e a chave que a `content()` da tela de login do Filament emite
     * DEPOIS do componente do formulario
     * (`vendor/filament/filament/src/Auth/Pages/Login.php:458-466`), que e onde o requisito
     * pede o botao. Registrar global e seguro porque essa chave nao e emitida em nenhuma
     * outra tela.
     *
     * O rodape NAO usa o hook `FOOTER`, apesar de o layout do Auth Designer renderizar aquele
     * hook (`filament-auth-designer/resources/views/components/layouts/auth.blade.php:63`):
     * o layout de painel autenticado tambem renderiza `FOOTER`, e o rodape apareceria em toda
     * tela de todo painel. Ver ADR-05.
     *
     * Duas registracoes e nao um blade com dois blocos: as duas superficies tem condicoes
     * independentes — os botoes dependem das credenciais de cada provedor, o rodape do texto. A
     * ordem de render e a ordem de registro, entao os botoes vem antes do rodape.
     *
     * A view dos botoes e UMA para os quatro provedores: ela percorre
     * `ConfiguracaoDoLogin::disponiveis()`. Provedor novo aparece na tela de login sem tocar
     * neste metodo. Ver ADR-08 da wiki mais-provedores-sociais.
     */
    protected function configureTelaDeLogin(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
            fn (): string => view('filament.auth.botoes-sociais')->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
            fn (): string => view('filament.auth.rodape-login')->render(),
        );

        // A tela de registro (registro aberto e aceite de convite) oferece os mesmos botoes; a
        // view carrega o `org`/`token` da query ate o `redirect`. Sem o rodape, que e do login.
        // Wiki cadastro-social-por-convite-e-organizacao.
        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_REGISTER_FORM_AFTER,
            fn (): string => view('filament.auth.botoes-sociais')->render(),
        );
    }

    protected function configureHealthChecks(): void
    {
        Health::checks(array_filter([
            DatabaseCheck::new(),
            CacheCheck::new(),
            QueueCheck::new(),
            ScheduleCheck::new(),
            DebugModeCheck::new(),
            EnvironmentCheck::new(),
            OptimizedAppCheck::new(),
            // Sem suporte a Windows no check de disco do Spatie.
            windows_os() ? null : UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage(70)
                ->failWhenUsedSpaceIsAbovePercentage(90),
            // Endpoints de IA local (llama.cpp/ollama) — devolve [] em provider SaaS, então
            // a página fica intacta quando a inferência não é local.
            ...LocalAiCheck::checksFor(config('ai.default')),
        ]));
    }

    /** Comandos extras no botão "Limpar cache" (painel infra). */
    protected function configureClearCacheButton(): void
    {
        FilamentClearCache::addCommand('config:clear');
        FilamentClearCache::addCommand('view:clear');

        if (config('laravel-model-caching.enabled')) {
            FilamentClearCache::addCommand('modelCache:clear');
        }
    }

    /**
     * No Windows, processos criados pela UI (Command Center) nascem sem
     * SystemRoot/PATH em $_SERVER e morrem com erros vazios de socket
     * (ex.: SQLSTATE[08006]). Repõe o essencial a partir de getenv().
     */
    protected function configureProcessEnvNoWindows(): void
    {
        if (! windows_os()) {
            return;
        }

        foreach (['SystemRoot', 'PATH', 'TEMP', 'TMP', 'USERPROFILE'] as $key) {
            if (! isset($_SERVER[$key]) && getenv($key) !== false) {
                $_SERVER[$key] = getenv($key);
            }
        }
    }
}

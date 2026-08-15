<?php

namespace App\Providers;

use App\Ai\Health\LocalAiCheck;
use App\Ai\Listeners\RegistrarAiRun;
use App\Models\Tenant;
use App\Models\User;
use App\Providers\Concerns\ConfiguraFilamentGlobal;
use Carbon\CarbonImmutable;
use CmsMulti\FilamentClearCache\Facades\FilamentClearCache;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
        $this->configureTenancy();
        $this->configureGates();
        $this->configureAiLedger();
        $this->configureHealthChecks();
        $this->configureClearCacheButton();
        $this->configureProcessEnvNoWindows();
        $this->configuraFilamentGlobal();
        $this->configureCorrecoesDeCss();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(app()->isProduction());

        Password::defaults(fn (): Password => app()->isProduction()
            ? Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()
            : Password::min(8));
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
            [Css::make('kit-correcoes', resource_path('css/filament/kit.css'))],
            package: 'kit',
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

<?php

namespace App\Providers;

use App\Ai\Health\LocalAiCheck;
use App\Ai\Listeners\RegistrarAiRun;
use App\Models\User;
use App\Providers\Concerns\ConfiguraFilamentGlobal;
use Carbon\CarbonImmutable;
use CmsMulti\FilamentClearCache\Facades\FilamentClearCache;
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
        $this->configureGates();
        $this->configureAiLedger();
        $this->configureHealthChecks();
        $this->configureClearCacheButton();
        $this->configureProcessEnvNoWindows();
        $this->configuraFilamentGlobal();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(app()->isProduction());

        Password::defaults(fn (): Password => app()->isProduction()
            ? Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()
            : Password::min(8));
    }

    protected function configureGates(): void
    {
        // master_global vence qualquer gate SEM precisar de permissions no banco.
        // Retornar null (não false) deixa os demais checks seguirem o fluxo normal.
        Gate::before(fn (User $user) => $user->isMasterGlobal() ? true : null);

        // Superfícies de infra/observabilidade. master_global passa pelo before;
        // o papel `infra` recebe acesso explícito aqui.
        Gate::define('ver-ai-tasks', fn (User $user): bool => $user->hasRole('infra'));
        Gate::define('ver-logs', fn (User $user): bool => $user->hasRole('infra'));
        Gate::define('command-center:access', fn (User $user): bool => $user->hasRole('infra'));
        Gate::define('viewPulse', fn (User $user): bool => $user->hasRole('infra'));

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

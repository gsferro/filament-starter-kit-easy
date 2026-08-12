<?php

namespace App\Ai\Middleware;

use Closure;
use Fomvasss\AiTasks\Exceptions\BudgetExceededException;
use Fomvasss\AiTasks\Support\Budget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Prompts\AgentPrompt;

/**
 * Guard de budget mensal. Pré-flight: roda ANTES dos guardrails e do provider; cap
 * estourado → `BudgetExceededException` (o consumidor degrada com mensagem honesta).
 *
 * O fomvasss/laravel-ai-tasks não emite evento de budget (só a exception nos fluxos da
 * própria facade `AI::send/stream/queue`); como os agentes rodam pelo SDK nativo, a checagem
 * é reaplicada aqui com o MESMO `Budget` — mesma config `ai-tasks.budgets`, mesmo somatório
 * de `ai_runs.cost`. Sem cap configurado (`ai-tasks.budgets.default.monthly_usd` ausente) o
 * guard é no-op, sem nenhuma query.
 *
 * O kit não tem multi-tenancy: tudo cai no tenant default de `ai-tasks.default_tenant`
 * (a coluna `ai_runs.tenant_id` é NOT NULL, então "sem tenant" é o tenant default, não null).
 */
final class BudgetGuardMiddleware
{
    public function handle(AgentPrompt $prompt, Closure $next): mixed
    {
        $tenant = (string) config('ai-tasks.default_tenant', 'default');
        $budget = app(Budget::class);
        $limite = $budget->getMonthlyLimit($tenant);

        if ($limite === null) {
            return $next($prompt); // sem cap configurado — no-op, zero query
        }

        // Mesma semântica de Budget::ensureNotExceeded, mas com Carbon MUTÁVEL explícito:
        // o kit usa Date::use(CarbonImmutable) e o vendor type-hinta Illuminate\Support\Carbon
        // em getSpentBetween() — deixar o vendor chamar now() daria TypeError.
        $gasto = $budget->getMonthlySpent($tenant, Carbon::now());

        if ($gasto <= $limite) {
            return $next($prompt);
        }

        Log::channel('ai')->warning(
            "[BudgetGuardMiddleware@handle] Budget mensal de IA estourado, execução bloqueada | tenant: {$tenant}",
            ['tenant_id' => $tenant, 'budget_usd' => $limite, 'gasto_mes_usd' => $gasto],
        );

        throw new BudgetExceededException(
            "Budget exceeded for tenant [{$tenant}]: spent \${$gasto} of \${$limite} limit"
        );
    }
}

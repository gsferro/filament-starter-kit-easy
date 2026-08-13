<?php

namespace App\Ai\Support;

use Filament\Facades\Filament;

/**
 * Tenant das execuções de IA (`ai_runs.tenant_id` e budget do
 * fomvasss/laravel-ai-tasks).
 *
 * Existe porque o `TenantResolver` do pacote resolve por header `X-Tenant-Id`
 * e, na falta dele, por `$user->tenant_id` — nenhum dos dois existe aqui: o
 * tenant do kit vem da rota do painel, via Filament.
 *
 * Fora de um request de painel (job, comando, seeder, agente em fila) não há
 * tenant, e aí cai no default do pacote. Isso é deliberado: `ai_runs.tenant_id`
 * é NOT NULL, então "sem tenant" precisa ser um valor, não null.
 */
final class ResolvedorDeTenant
{
    public function id(): string
    {
        $organizacao = Filament::getTenant();

        return (string) ($organizacao?->getKey() ?? config('ai-tasks.default_tenant', 'default'));
    }
}

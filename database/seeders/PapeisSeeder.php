<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Papéis do kit — a fronteira de acesso aos três painéis (User::canAccessPanel).
 *
 *   master_global → tudo (vence qualquer gate pelo Gate::before)
 *   admin         → painel /admin: usuários, papéis, agentes de IA
 *   infra         → painel /infra: health, filas, logs, auditoria, comandos
 *   panel_user    → só o painel /app (a operação do negócio)
 *
 * Idempotente: pode rodar de novo depois de criar Resources novos.
 *
 * ## Com multi-tenancy ligada (`permission.teams`)
 *
 * Os papéis continuam sendo criados SEM team (`team_id` nulo) — no spatie isso
 * significa "papel global, disponível em qualquer tenant". O que passa a ser
 * por tenant é a ATRIBUIÇÃO: `$user->assignRole('admin')` grava em
 * `model_has_roles` o team corrente, fixado a cada request pelo middleware
 * `DefinirTenantDePermissoes`.
 *
 * Efeito prático: o mesmo usuário pode ser `admin` num tenant e usuário comum
 * em outro, sem duplicar a definição do papel.
 */
class PapeisSeeder extends Seeder
{
    public function run(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        $master = $this->papel(config('filament-shield.super_admin.name', 'master_global'), $guard);
        $admin  = $this->papel('admin', $guard);
        $infra  = $this->papel('infra', $guard);

        $this->papel(config('filament-shield.panel_user.name', 'panel_user'), $guard);

        // master_global fica sem permissions de propósito: o acesso vem do
        // Gate::before (KitServiceProvider). Sincronizar tudo aqui só criaria
        // uma lista que apodrece a cada Resource novo.
        $master->syncPermissions([]);

        // admin e infra nascem com as permissions que já existem no banco,
        // recortadas por prefixo de model. Ajuste à vontade — este seeder é o
        // lugar da matriz de autorização do seu projeto.
        $todas = Permission::where('guard_name', $guard)->pluck('name');

        $admin->syncPermissions(
            $todas->filter(fn (string $p): bool => str_contains($p, 'User')
                || str_contains($p, 'Role')
                || str_contains($p, 'AgenteIa')
                || str_contains($p, 'Tenant'))
        );

        $infra->syncPermissions(
            $todas->filter(fn (string $p): bool => str_contains($p, 'AiRun') || str_contains($p, 'Audit') || str_contains($p, 'AuthenticationLog'))
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Papel com DEFINIÇÃO global (`roles.team_id` nulo).
     *
     * Com `permission.teams` ligado, o `Role::findOrCreate` do spatie carimba
     * o team corrente na definição do papel, e um papel carimbado no tenant A
     * fica invisível no tenant B — não haveria como atribuir `admin` em dois
     * tenants sem duplicar a definição.
     *
     * `roles.team_id` é nullable justamente para isso: nulo = papel disponível
     * em qualquer contexto. O que varia por tenant é a ATRIBUIÇÃO
     * (`model_has_roles.team_id`, essa sim NOT NULL).
     */
    private function papel(string $nome, string $guard): Role
    {
        $atributos = ['name' => $nome, 'guard_name' => $guard];

        if (config('permission.teams')) {
            $atributos[config('permission.column_names.team_foreign_key', 'team_id')] = null;
        }

        return Role::query()->firstOrCreate($atributos);
    }
}

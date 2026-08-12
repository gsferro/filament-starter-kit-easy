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
 */
class PapeisSeeder extends Seeder
{
    public function run(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        $master = Role::findOrCreate(
            config('filament-shield.super_admin.name', 'master_global'),
            $guard,
        );

        $admin = Role::findOrCreate('admin', $guard);
        $infra = Role::findOrCreate('infra', $guard);

        Role::findOrCreate(config('filament-shield.panel_user.name', 'panel_user'), $guard);

        // master_global fica sem permissions de propósito: o acesso vem do
        // Gate::before (KitServiceProvider). Sincronizar tudo aqui só criaria
        // uma lista que apodrece a cada Resource novo.
        $master->syncPermissions([]);

        // admin e infra nascem com as permissions que já existem no banco,
        // recortadas por prefixo de model. Ajuste à vontade — este seeder é o
        // lugar da matriz de autorização do seu projeto.
        $todas = Permission::where('guard_name', $guard)->pluck('name');

        $admin->syncPermissions(
            $todas->filter(fn (string $p): bool => str_contains($p, 'User') || str_contains($p, 'Role') || str_contains($p, 'AgenteIa'))
        );

        $infra->syncPermissions(
            $todas->filter(fn (string $p): bool => str_contains($p, 'AiRun') || str_contains($p, 'Audit') || str_contains($p, 'AuthenticationLog'))
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Tenant inicial do modo multi-tenant.
 *
 * Um painel com tenancy precisa de pelo menos um tenant para abrir: sem
 * nenhum, o /app não tem para onde redirecionar e o usuário fica preso numa
 * tela de seleção vazia. Este seeder garante o primeiro.
 *
 * Idempotente (`updateOrCreate` por slug) e sem factory/faker — convenção do
 * kit, ver DatabaseSeeder.
 */
class TenantsSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::updateOrCreate(
            ['slug' => 'padrao'],
            ['nome' => config('kit.tenancy.label', 'Organização').' Padrão', 'ativo' => true],
        );

        // Vincula o usuário inicial, senão ele entra no /admin mas não no /app.
        // syncWithoutDetaching preserva vínculos criados à mão depois.
        $admin = User::where('email', config('kit.admin.email'))->first();

        $admin?->tenants()->syncWithoutDetaching([$tenant->id]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Projeto;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * DEMONSTRAÇÃO do modo multi-tenant — `php artisan kit:tenancy --demo`.
 *
 * Monta o cenário mínimo que prova o isolamento em três cliques:
 *
 *   Acme    → Ana (só Acme)      + 2 projetos
 *   Globex  → Bruno (só Globex)  + 2 projetos
 *   ambos   → Carla (nos dois)   — é ela que mostra o seletor de tenant e a
 *                                  listagem mudando ao trocar
 *
 * Senha de todos: `password`.
 *
 * Idempotente (`updateOrCreate`) e sem factory/faker — convenção do kit.
 * Descartável junto com o resto da demo.
 */
class DemoTenancySeeder extends Seeder
{
    public function run(): void
    {
        $acme   = $this->tenant('Acme', 'acme');
        $globex = $this->tenant('Globex', 'globex');

        $ana   = $this->usuario('Ana (só Acme)', 'ana@example.com');
        $bruno = $this->usuario('Bruno (só Globex)', 'bruno@example.com');
        $carla = $this->usuario('Carla (Acme e Globex)', 'carla@example.com');

        $ana->tenants()->syncWithoutDetaching([$acme->id]);
        $bruno->tenants()->syncWithoutDetaching([$globex->id]);
        $carla->tenants()->syncWithoutDetaching([$acme->id, $globex->id]);

        // tenant_id explícito: fora de um request de painel não há
        // `Filament::getTenant()`, então a trait não tem o que preencher.
        $this->projeto($acme, 'Portal do cliente');
        $this->projeto($acme, 'Migração de dados');
        $this->projeto($globex, 'App de vendas');
        $this->projeto($globex, 'Integração fiscal');

        $this->command->info('Demo criada: /app/acme e /app/globex — entre como carla@example.com (senha: password).');
    }

    private function tenant(string $nome, string $slug): Tenant
    {
        return Tenant::updateOrCreate(['slug' => $slug], ['nome' => $nome, 'ativo' => true]);
    }

    private function usuario(string $nome, string $email): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            ['name' => $nome, 'password' => Hash::make('password'), 'email_verified_at' => now()],
        );
    }

    /**
     * `tenant_id` é atribuído fora do construtor de propósito: ele fica fora do
     * `$fillable` por convenção do kit, então mass assignment o descartaria em
     * silêncio e a FK estouraria.
     *
     * `withoutGlobalScope('tenant')` porque aqui não há tenant corrente — sem
     * isso a checagem de existência seria feita sem filtro e o seeder deixaria
     * de ser idempotente entre tenants com o mesmo nome de projeto.
     */
    private function projeto(Tenant $tenant, string $nome): void
    {
        $jaExiste = Projeto::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('nome', $nome)
            ->exists();

        if ($jaExiste) {
            return;
        }

        $projeto            = new Projeto(['nome' => $nome]);
        $projeto->tenant_id = $tenant->id;
        $projeto->save();
    }
}

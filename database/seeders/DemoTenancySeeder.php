<?php

namespace Database\Seeders;

use App\Models\Projeto;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * DEMONSTRAÇÃO do modo multi-tenant — `php artisan kit:tenancy --demo`.
 *
 * Monta o cenário mínimo que prova o isolamento em três cliques:
 *
 *   Acme    → Ana (só Acme)      + 2 projetos — `admin_app`: é ela que
 *                                  mostra as telas de usuários e convites
 *                                  recortadas à organização dela
 *   Globex  → Bruno (só Globex)  + 2 projetos — `panel_user`: entra no /app e
 *                                  NÃO vê a administração da organização
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
        // A demo precisa do papel `panel_user` existindo para dar acesso ao /app. Chamar
        // aqui deixa este seeder autossuficiente: `kit:tenancy --demo` já rodou o
        // PapeisSeeder no migrate:fresh, e rodá-lo de novo é idempotente.
        $this->call(PapeisSeeder::class);

        $acme   = $this->tenant('Acme', 'acme');
        $globex = $this->tenant('Globex', 'globex');

        $ana   = $this->usuario('Ana (só Acme)', 'ana@example.com');
        $bruno = $this->usuario('Bruno (só Globex)', 'bruno@example.com');
        $carla = $this->usuario('Carla (Acme e Globex)', 'carla@example.com');

        $ana->tenants()->syncWithoutDetaching([$acme->id]);
        $bruno->tenants()->syncWithoutDetaching([$globex->id]);
        $carla->tenants()->syncWithoutDetaching([$acme->id, $globex->id]);

        // Vínculo não é acesso: quem abre o /app é o papel (User::canAccessPanel lê
        // `roles.painel`). Sem esta parte a demo nasce com três usuários que autenticam
        // e levam 403 — ou seja, sem demonstrar nada.
        //
        // A atribuição é POR organização: `model_has_roles.team_id` guarda o contexto, e
        // `assignRole()` carimba o que estiver fixado no PermissionRegistrar.
        //
        // Ana administra a Acme (usuários e convites recortados à organização dela);
        // Bruno e Carla são usuários comuns do negócio. É esse contraste que mostra a
        // persona nova — e a Carla, que está nas duas organizações, é quem prova que
        // mexer nos papéis dela na Acme não toca nos da Globex.
        $this->papelDoApp($ana, $acme, 'admin_app');
        $this->papelDoApp($bruno, $globex);
        $this->papelDoApp($carla, $acme);
        $this->papelDoApp($carla, $globex);

        // tenant_id explícito: fora de um request de painel não há
        // `Filament::getTenant()`, então a trait não tem o que preencher.
        $this->projeto($acme, 'Portal do cliente');
        $this->projeto($acme, 'Migração de dados');
        $this->projeto($globex, 'App de vendas');
        $this->projeto($globex, 'Integração fiscal');

        $this->command->info('Demo criada: /app/acme e /app/globex — entre como carla@example.com (senha: password).');
    }

    /** Papel do /app atribuído dentro do contexto da organização. */
    private function papelDoApp(User $usuario, Tenant $tenant, ?string $papel = null): void
    {
        $papel ??= (string) config('filament-shield.panel_user.name', 'panel_user');

        $registrar = app(PermissionRegistrar::class);
        $anterior  = $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId($tenant->getKey());
            $usuario->unsetRelation('roles');
            $usuario->assignRole($papel);
        } finally {
            $registrar->setPermissionsTeamId($anterior);
            $usuario->unsetRelation('roles');
        }
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

        // `associate()` e não `$projeto->tenant_id = $tenant->id`: quem sabe qual é a coluna
        // da FK é a própria relação, e o valor vem da chave primária do model — sem repetir
        // aqui o nome da coluna nem o tipo dela.
        $projeto = new Projeto(['nome' => $nome]);
        $projeto->tenant()->associate($tenant);
        $projeto->save();
    }
}

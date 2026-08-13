<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Ordem importa: papéis e permissions antes do usuário que os recebe.
     *
     * Nenhum seeder do kit usa factory/fake: `fakerphp/faker` é require-dev e
     * a imagem Docker roda `composer install --no-dev` — um seeder com faker
     * quebraria o deploy containerizado.
     */
    public function run(): void
    {
        $this->call([
            ShieldPermissionsSeeder::class,
            PapeisSeeder::class,
            UsuarioAdminSeeder::class,
            AssistenteSeeder::class,
            GuardaPromptSeeder::class,
        ]);

        // Só no modo multi-tenant: em single-tenant a tabela existe mas não é
        // usada, e semear um tenant fantasma só confundiria.
        if (config('kit.tenancy.enabled')) {
            $this->call(TenantsSeeder::class);
        }
    }
}

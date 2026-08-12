<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Usuário inicial do kit. TROQUE A SENHA antes de qualquer ambiente exposto.
 *
 * Sem factory/fake de propósito (faker é require-dev e a imagem Docker roda
 * `composer install --no-dev`).
 */
class UsuarioAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => config('kit.admin.email')],
            [
                'name'              => config('kit.admin.name'),
                'password'          => config('kit.admin.password'),
                'email_verified_at' => now(),
            ],
        );

        $user->assignRole(config('filament-shield.super_admin.name', 'master_global'));
    }
}

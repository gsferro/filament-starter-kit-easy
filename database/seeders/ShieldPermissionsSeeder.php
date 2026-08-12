<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permissions do Shield geradas por SEEDER, não por `shield:generate`.
 *
 * O comando do Shield é interativo e falha em modo não-interativo (é o que
 * quebraria o `composer create-project` no Windows). Aqui a geração roda com
 * `--no-interaction` e, se ainda assim falhar, o kit segue instalando: o
 * master_global passa pelo Gate::before e os papéis são criados pelo
 * PapeisSeeder de qualquer forma.
 *
 * Rode novamente depois de criar seus Resources:
 *   php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
 */
class ShieldPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('permissions')) {
            $this->command->warn('Tabela `permissions` ausente — rode as migrations antes.');

            return;
        }

        try {
            Artisan::call('shield:generate', [
                '--all'            => true,
                '--panel'          => 'admin',
                '--no-interaction' => true,
            ]);
        } catch (\Throwable $e) {
            $this->command->warn(
                'shield:generate não pôde rodar agora ('.$e->getMessage().'). '
                .'Gere as permissions depois com: php artisan shield:generate --all --panel=admin'
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

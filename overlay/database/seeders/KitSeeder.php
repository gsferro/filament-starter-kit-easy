<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class KitSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['super_admin', 'admin', 'infra'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Administrador', 'password' => 'password'],
        );

        $admin->assignRole('super_admin');
    }
}

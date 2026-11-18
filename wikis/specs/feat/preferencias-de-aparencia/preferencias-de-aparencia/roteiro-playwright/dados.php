<?php

// Dados do teste manual das preferências de aparência. Roda por `php artisan tinker`.
use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

$tenancy = (bool) config('kit.tenancy.enabled');

if ($tenancy) {
    app(PermissionRegistrar::class)->setPermissionsTeamId(Tenant::CONTEXTO_GLOBAL);
}

$master = User::query()->updateOrCreate(['email' => 'master@manual.test'], ['name' => 'Master Manual', 'password' => bcrypt('senha-manual-123')]);
$master->forceFill(['email_verified_at' => now(), 'aparencia' => null])->save();
$master->unsetRelation('roles');
$master->assignRole('master_global');

if ($tenancy) {
    $acme   = Tenant::query()->updateOrCreate(['slug' => 'acme'], ['nome' => 'Acme', 'ativo' => true, 'cor_primaria_nome' => 'Rose', 'aparencia_por_usuario' => true]);
    $globex = Tenant::query()->updateOrCreate(['slug' => 'globex'], ['nome' => 'Globex', 'ativo' => true, 'cor_primaria_nome' => 'Rose', 'aparencia_por_usuario' => false]);
    $acme->users()->syncWithoutDetaching([$master->id]);
    $globex->users()->syncWithoutDetaching([$master->id]);
}

echo "dados ok\n";

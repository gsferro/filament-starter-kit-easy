<?php

use App\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * A renomeação de `admin_organizacao` para `admin_app` alcança quem JÁ instalou.
 *
 * O papel é dado, não código: mudar o seeder resolve para quem instala do zero e não
 * faz nada pelo banco de quem já está rodando — e é justamente esse projeto que
 * quebraria, porque o código do kit passou a perguntar por `admin_app`.
 *
 * A migration é o único caminho que o `kit:update` distribui e que chega ao banco.
 */
it('renomeia o papel que já existia no banco', function (): void {
    Role::query()->where('name', 'admin_app')->delete();

    DB::table('roles')->insert([
        'name'       => 'admin_organizacao',
        'guard_name' => 'web',
        'painel'     => 'app',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    (require base_path('database/migrations/2026_08_16_000001_rename_admin_organizacao_role.php'))->up();

    expect(Role::query()->where('name', 'admin_app')->exists())->toBeTrue()
        ->and(Role::query()->where('name', 'admin_organizacao')->exists())->toBeFalse();
})->group('kit');

/**
 * Projeto single-tenant nunca semeou esse papel. A migration não pode INVENTAR um:
 * papel a mais é papel que alguém pode atribuir sem querer.
 */
it('não cria papel nenhum quando ele não existia', function (): void {
    Role::query()->whereIn('name', ['admin_app', 'admin_organizacao'])->delete();

    $antes = Role::query()->count();

    (require base_path('database/migrations/2026_08_16_000001_rename_admin_organizacao_role.php'))->up();

    expect(Role::query()->count())->toBe($antes)
        ->and(Role::query()->where('name', 'admin_app')->exists())->toBeFalse();
})->group('kit');

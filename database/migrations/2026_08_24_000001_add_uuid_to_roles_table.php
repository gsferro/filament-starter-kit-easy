<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * `uuid` na tabela de papéis — a convenção `App\Traits\TemUuid` aplicada à última tabela do
 * kit que ainda expunha `id` na URL.
 *
 * A tabela é a do spatie (`create_permission_tables`), então a coluna vem por migration
 * PRÓPRIA em vez de editar aquela: a do vendor é republicável e um
 * `vendor:publish --tag=permission-migrations --force` apagaria a edição sem avisar.
 *
 * **Três tempos, e não um.** Em SQLite, acrescentar coluna NOT NULL sem default a uma tabela
 * com linhas é erro, e toda instalação existente já tem os cinco papéis do `PapeisSeeder`.
 * Coluna nullable → backfill → índice único.
 *
 * E **não** se fecha o NOT NULL com `->change()` depois do backfill: em SQLite o `change()`
 * reconstrói a tabela, e `roles` é alvo de foreign key em `model_has_roles` e
 * `role_has_permissions` (`2026_08_12_164859_create_permission_tables.php:84-87` e `:109-112`).
 * O índice único já garante o que a rota precisa — unicidade —, e o `HasUuids` de `TemUuid`
 * garante o preenchimento de toda linha nova pelo hook de `creating`. Ver ADR-03 de
 * `wikis/specs/feat/perfis-e-permissoes/tela-de-perfis/`.
 *
 * A PK continua `id` int: `TemUuid::uniqueIds()` devolve `['uuid']`, e o
 * `HasUniqueStringIds` do Laravel só troca `getKeyType()`/`getIncrementing()` quando a CHAVE
 * PRIMÁRIA está nessa lista. As duas foreign keys acima seguem intactas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->tabela(), function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::table($this->tabela())
            ->whereNull('uuid')
            ->orderBy('id')
            ->each(fn (object $papel): int => DB::table($this->tabela())
                ->where('id', $papel->id)
                ->update(['uuid' => (string) Str::uuid()]));

        Schema::table($this->tabela(), function (Blueprint $table): void {
            $table->unique('uuid');
        });

        $this->esquecerCacheDePermissoes();
    }

    public function down(): void
    {
        Schema::table($this->tabela(), function (Blueprint $table): void {
            // Só `dropColumn`: em todos os drivers que o Laravel suporta o índice de coluna
            // única cai com a coluna, e escrever o nome do índice à mão o acoplaria à
            // convenção de nomes do Laravel para uma tabela cujo nome vem de config.
            $table->dropColumn('uuid');
        });

        $this->esquecerCacheDePermissoes();
    }

    private function tabela(): string
    {
        return (string) config('permission.table_names.roles', 'roles');
    }

    /**
     * O `PermissionRegistrar` cacheia os papéis, e cache montado antes de uma mudança de
     * schema é defeito que só aparece no request seguinte — sem erro, com dado velho.
     *
     * `forgetCachedPermissions()` e não o `app('cache')->store(...)->forget(...)` que a
     * migration do spatie usa (`create_permission_tables.php:117-119`): o helper resolve a
     * store sozinho E limpa o índice de wildcard
     * (`vendor/spatie/laravel-permission/src/PermissionRegistrar.php:136-142`), que a versão
     * manual deixa para trás.
     */
    private function esquecerCacheDePermissoes(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

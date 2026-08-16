<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Renomeia o papel `admin_organizacao` para `admin_app`.
 *
 * O papel é DADO, não código: ele nasce do `PapeisSeeder` e vive na tabela `roles`.
 * Renomear no seeder resolve para quem instala do zero e não faz nada por quem já
 * instalou — e é justamente esse projeto que quebraria, porque o código do kit passou a
 * perguntar por `admin_app`.
 *
 * Por isso a migration: ela é o único caminho que o `kit:update` distribui e que alcança
 * o banco de quem já está rodando. Sem ela, o `admin_organizacao` continuaria no banco,
 * as pessoas continuariam com ele, e nenhuma das telas que ele abre reconheceria mais o
 * papel.
 *
 * `update` condicional, e não `updateOrInsert`: se o papel não existe (projeto
 * single-tenant, que nunca o semeou), não há nada a renomear e a migration não deve
 * criar nada. Rodar de novo é operação normal — o `where` já não casa.
 */
return new class extends Migration
{
    private const DE = 'admin_organizacao';

    private const PARA = 'admin_app';

    public function up(): void
    {
        $this->renomear(self::DE, self::PARA);
    }

    public function down(): void
    {
        $this->renomear(self::PARA, self::DE);
    }

    /**
     * O nome da tabela vem da config do spatie: o projeto pode tê-la renomeado, e
     * `roles` fixo aqui erraria sem avisar.
     */
    private function renomear(string $de, string $para): void
    {
        DB::table(config('permission.table_names.roles', 'roles'))
            ->where('name', $de)
            ->update(['name' => $para]);
    }
};

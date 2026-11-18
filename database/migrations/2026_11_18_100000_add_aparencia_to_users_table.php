<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * As preferências de aparência de cada pessoa (wiki `feat/preferencias-de-aparencia`).
 *
 * Um objeto JSON com até quatro chaves — `densidade`, `fonte`, `cor` e `menu` —, cada uma ausente ou
 * nula quando a pessoa deixou no padrão. `null` na coluna inteira (o valor de toda conta que já
 * existia) = tudo no padrão.
 *
 * Na própria `users`, e não tabela própria, pelo mesmo motivo do dock (`add_dock_to_users_table`): o
 * usuário autenticado já está carregado no request, então ler a escolha custa zero consulta em toda
 * tela. Fica FORA do `$fillable` e só é escrita por `forceFill`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->json('aparencia')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('aparencia');
        });
    }
};

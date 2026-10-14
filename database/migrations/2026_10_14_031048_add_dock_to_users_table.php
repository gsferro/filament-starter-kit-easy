<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Os favoritos e o layout do dock de atalhos de cada usuário (wiki `dock-de-acoes-rapidas`, ADR-02).
 *
 * Colunas na própria `users`, e não tabela própria: o usuário autenticado já está carregado no
 * request, então ler os favoritos custa zero consulta (RQ-22). `dock_atalhos` é uma lista JSON de
 * chaves `{painel}|{FQCN}|{organização ou vazio}`; `dock_layout` nulo = o padrão da instalação.
 *
 * As duas ficam FORA do `$fillable` do `User` e só são escritas por `forceFill`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->json('dock_atalhos')->nullable();
            $table->string('dock_layout', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['dock_atalhos', 'dock_layout']);
        });
    }
};

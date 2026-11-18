<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada organização decide se as pessoas dela usam a aparência pessoal (wiki
 * `feat/preferencias-de-aparencia`, RQ-08).
 *
 * Só tem efeito com a chave da instalação (`kit.aparencia_por_usuario`) ligada: esta coluna recorta
 * uma liberação que a instalação já deu, nunca libera sozinha (RQ-09).
 *
 * Default `false` (P-04), pelo mesmo motivo do `registro_habilitado`: com `true`, toda organização
 * existente passaria a aceitar a escolha pessoal no instante do `migrate`, sem ninguém decidir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->boolean('aparencia_por_usuario')->default(false)->after('registro_habilitado');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn('aparencia_por_usuario');
        });
    }
};

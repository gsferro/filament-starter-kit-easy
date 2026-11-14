<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A aparência das telas de autenticação da organização — ou nenhuma.
 *
 * `null` (o default, e o valor de toda organização que já existia) = usa a aparência padrão da
 * instalação. Um objeto = a aparência própria, INTEIRA: não há herança campo a campo (ADR-02 da
 * wiki `feat/aparencia-das-telas-de-acesso`). JSON, e não uma coluna por opção, porque a ADR-01
 * da identidade visual da organização já pedia reavaliar JSON a partir do terceiro campo.
 *
 * Sem backfill: nada muda para quem atualiza.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->json('aparencia_das_telas')->nullable()->after('unifica_logo');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn('aparencia_das_telas');
        });
    }
};

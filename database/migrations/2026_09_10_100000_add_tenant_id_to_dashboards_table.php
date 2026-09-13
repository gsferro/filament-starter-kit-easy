<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `tenant_id` nos dashboards dinâmicos — a fronteira de organização da grade.
 *
 * Nullable de propósito: sem multi-organização a coluna fica `null` e nada
 * muda. `nullOnDelete` e não cascade: excluir a organização não pode varrer os
 * dashboards — eles ficam órfãos e invisíveis (o scope filtra pelo tenant
 * corrente), preservando o dado para auditoria.
 *
 * Guardada por `hasColumn`: quem já criou a coluna à mão (porte da referência)
 * não quebra no `migrate`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dashboards') || Schema::hasColumn('dashboards', 'tenant_id')) {
            return;
        }

        Schema::table('dashboards', function (Blueprint $table): void {
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('page')
                ->constrained('tenants')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('dashboards', 'tenant_id')) {
            return;
        }

        Schema::table('dashboards', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tenant_id');
        });
    }
};

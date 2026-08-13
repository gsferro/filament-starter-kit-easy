<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DEMONSTRAÇÃO — tabela de exemplo do modo multi-tenant.
 *
 * É o molde de toda tabela de negócio com tenancy: FK obrigatória para
 * `tenants`, com cascade. Apagar um tenant apaga os dados dele — por isso a
 * tela de tenants não tem DeleteAction.
 *
 * Descartável junto com o resto da demo (ver App\Models\Projeto).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projetos', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('nome');
            $table->timestamps();

            // Toda listagem filtra por tenant primeiro: o índice acompanha o
            // formato real da query, não a ordem em que as colunas nasceram.
            $table->index(['tenant_id', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projetos');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Convite de acesso — a única porta de entrada de quem vem de fora.
 *
 * O token vai HASHEADO (`hash('sha256', $token)`): um dump de banco vazado devolve o
 * digest, não o acesso. O token em claro existe só no e-mail e no link do navegador.
 *
 * Sem coluna de status e sem `revogado_em` de propósito: pendente/aceito/expirado se
 * derivam de `aceito_em` e `expira_em`, e revogar é apagar a linha (a trilha fica na
 * auditoria). Ver ADR-02 e ADR-04 em
 * `wikis/specs/main/convite-de-usuario/02-decisoes-arquiteturais.md`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('convites', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('email')->index();

            // sha256 em hex: 64 chars, determinístico, indexável — é o que torna a busca
            // POR TOKEN um lookup de índice único. Nullable porque quem grava é
            // `Convite::enviar()`, depois do `create()` da tela.
            $table->string('token', 64)->nullable()->unique();

            // Sem cascadeOnDelete: apagar o papel de um convite pendente tem de doer na
            // hora, em vez de deixar o convite aceitar com papel nulo.
            $table->foreignId('role_id')
                ->constrained(config('permission.table_names.roles', 'roles'));

            // Só preenchida com a tenancy ligada. A tabela `tenants` existe nos dois modos.
            $table->foreignId('tenant_id')->nullable()->constrained();

            // Quem convidou. nullOnDelete: apagar o admin não apaga o histórico do convite.
            $table->foreignId('convidado_por_id')->nullable()
                ->constrained('users')->nullOnDelete();

            // Nullable porque só `enviar()` sabe o prazo: a linha nasce no `create()` do
            // Filament e ganha token e validade no `afterCreate()`. NULL falha fechado —
            // `expira_em > now()` não casa com NULL, então convite sem envio não vale.
            $table->timestamp('expira_em')->nullable();
            $table->timestamp('aceito_em')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convites');
    }
};

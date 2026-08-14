<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O convite cobra a si mesmo: um segundo link, um relógio e um contador.
 *
 * As três colunas ficam FORA do `$fillable` do model, o que as mantém fora da trilha de
 * `/infra/audits` — hash de credencial não é dado de diagnóstico. Ver ADR-01 em
 * `wikis/specs/main/lembretes-de-convite/02-decisoes-arquiteturais.md`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('convites', function (Blueprint $table): void {
            /*
             * O SEGUNDO token do convite, hasheado como o primeiro. Um lembrete gera um
             * token novo e grava o hash aqui, SEM tocar em `token`: o link original
             * continua valendo, então nada é revogado nem se o lembrete cair no spam.
             * Cada lembrete sobrescreve esta coluna — no máximo dois links vivos por
             * convite, os dois morrendo com o mesmo `expira_em`. Ver ADR-01.
             */
            $table->string('token_lembrete', 64)->nullable()->unique();

            /*
             * Quando o convite foi enviado de verdade — e NÃO `created_at`: `enviar()` é
             * também o reenvio, então `created_at` pode estar a semanas do último envio.
             * Nulo em toda linha anterior a esta migration, o que as mantém fora dos
             * lembretes: `enviado_em <= ?` nunca casa com NULL. Ver ADR-02.
             */
            $table->timestamp('enviado_em')->nullable();

            // Quantos lembretes já saíram para o envio corrente. Zerado por `enviar()`.
            $table->unsignedTinyInteger('lembretes_enviados')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('convites', function (Blueprint $table): void {
            $table->dropColumn(['token_lembrete', 'enviado_em', 'lembretes_enviados']);
        });
    }
};

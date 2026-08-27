<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * De onde a conta veio: `interno` (admin, seeder, comando), `convite`, `registro` (aberto,
 * pelo formulário) ou o driver do provedor social (`google`, `github`, …).
 *
 * É a única coluna que o login social pôs em `users`, e ela NÃO é vínculo: não guarda id do
 * provedor, token nem nada que autentique — o vínculo continua sendo o e-mail verificado.
 * Serve para quem administra ver, na lista e no dashboard, por qual porta cada pessoa entrou.
 * Pedido do solicitante na validação real dos provedores (2026-08-26).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // `default('interno')`: todo caminho que NÃO diz de onde veio é interno — admin
            // criando pela tela, seeder, `kit:admin`. Só convite, registro e provedor marcam.
            $table->string('origem', 32)->default('interno')->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('origem');
        });
    }
};

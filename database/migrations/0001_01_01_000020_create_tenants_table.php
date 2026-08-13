<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenants — a unidade de isolamento do kit.
 *
 * O vocabulário do CÓDIGO é o da API do Filament (`tenants`, `tenant_id`,
 * `getTenants()`); o que o usuário vê vem de `config('kit.tenancy.label')`,
 * que nasce como "Organização" e cada projeto troca pelo termo do seu negócio.
 *
 * A tabela existe sempre; o que o `kit:tenancy` liga é o USO dela. Criá-la em
 * modo single-tenant é inofensivo e evita que ligar a tenancy dependa de
 * migration nova.
 *
 * `id` int como PK e `uuid` na rota — convenção do kit (App\Traits\TemUuid).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nome');
            // Segmento da URL em /app/{slug} — é o `slugAttribute` do painel.
            $table->string('slug')->unique();
            // Exclusão é lógica: desligar um tenant é dado, não DELETE.
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};

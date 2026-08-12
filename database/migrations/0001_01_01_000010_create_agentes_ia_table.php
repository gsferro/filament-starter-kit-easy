<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de agentes de IA — o "paper" do agente como DADO, não como código.
 * System prompt, provider, modelo, tools e guardrails são linhas desta tabela,
 * editáveis pelo painel admin sem deploy. Lido por App\Ai\Agents\AgenteBase.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agentes_ia', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->string('provider')->nullable();       // null = default de config/ai.php
            $table->string('modelo')->nullable();          // null = default do provider
            $table->decimal('temperatura', 3, 2)->nullable();
            $table->unsignedInteger('max_tokens')->nullable();
            $table->json('tools')->nullable();              // allowlist de tools por chave
            $table->json('guardrails')->nullable();         // chaves do GuardrailRegistry
            $table->text('instrucoes');                     // system prompt
            $table->unsignedInteger('versao')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agentes_ia');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O vínculo entre a conta e a identidade dela num provedor de login social.
 *
 * `sub` é o identificador da conta NO PROVEDOR (`Socialite user->getId()`): estável quando o
 * e-mail muda. É por ele que o kit reconhece quem já entrou por aquele provedor — e não pelo
 * e-mail, que um endereço reciclado no correio poderia levar a outra conta. Não guarda token
 * nem nada que autentique: é identidade, não credencial. Ver ADR-02 da wiki
 * `vinculo-de-provedor-social`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vinculos_sociais', function (Blueprint $table): void {
            $table->id();
            // Apagar a conta apaga os vínculos: sem conta, o vínculo não aponta para nada.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provedor', 32);
            $table->string('sub', 191);
            $table->timestamp('confirmado_em');
            $table->timestamp('ultimo_acesso_em')->nullable();
            $table->timestamps();

            // Uma identidade de provedor pertence a UMA conta — é a chave do reconhecimento.
            $table->unique(['provedor', 'sub']);
            $table->index(['user_id', 'provedor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vinculos_sociais');
    }
};

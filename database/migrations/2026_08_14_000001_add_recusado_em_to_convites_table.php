<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quando o convidado disse não.
 *
 * Uma coluna e não a exclusão da linha (que é o que o `jeffersongoncalves/filament-teams`
 * faz): recusa é INFORMAÇÃO. "Ela disse não, não convide de novo" é diferente de "o
 * convite desapareceu" — e quem administra a organização precisa dessa diferença para não
 * reconvidar alguém que já recusou.
 *
 * O estado do convite continua DERIVADO, agora de três fatos (`aceito_em`, `recusado_em`,
 * `expira_em`) em vez de dois. Nenhuma coluna de status: é o que nos poupou dos dois bugs
 * do `offload-project/laravel-invite-only`, onde a validade depende do cron ter rodado e o
 * par `get()`/`update()` sobrescreve um aceite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('convites', function (Blueprint $table): void {
            $table->timestamp('recusado_em')->nullable()->after('aceito_em');
        });
    }

    public function down(): void
    {
        Schema::table('convites', function (Blueprint $table): void {
            $table->dropColumn('recusado_em');
        });
    }
};

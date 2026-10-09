<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Uma logo só, nos dois temas — por organização.
 *
 * É o `unifica_logo_marca` do settings, um nível abaixo: ligado (o default), a
 * `logo` da organização serve o tema claro e o escuro e ela não é obrigada a
 * enviar uma segunda imagem; desligado, o `logo_dark` dela entra em cena.
 *
 * O default `true` vale para linhas NOVAS. Para quem já existia, o backfill
 * decide pela evidência: organização com `logo_dark` gravada separou a marca de
 * propósito e nasce com `false` — a escura que ela enviou não pode ficar inerte
 * por causa de um update. Quem nunca enviou a escura nasce `true`, que é o
 * comportamento pedido: a clara dela cobre os dois temas em vez de a `logo_dark`
 * da instalação aparecer no dark. ADR-01 da wiki `unificar-logo-da-organizacao`.
 *
 * Só vale com a marca da instalação separada (`kit.identidade.unifica_logo_marca`
 * desligado): com ela unificada, toda `logo_dark` — da instalação e da
 * organização — já é inerte, e o toggle nem aparece no formulário.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->boolean('unifica_logo')->default(true)->after('logo_dark');
        });

        DB::table('tenants')
            ->whereNotNull('logo_dark')
            ->update(['unifica_logo' => false]);
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn('unifica_logo');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `deleted_at` em `projetos` — o que dá conteúdo à Lixeira do /infra.
 *
 * Migration separada, e não uma linha acrescentada ao `create_projetos_table`:
 * quem já instalou o kit tem aquela migration rodada, e editá-la não alcançaria
 * o banco de ninguém. Ver `php artisan kit:update`.
 *
 * `projetos` é a tabela de DEMONSTRAÇÃO (App\Models\Projeto). Ela existe mesmo
 * sem `KIT_DEMO`, porque a migration base sempre roda; o que o modo demo faz é
 * semear dado e mostrar o resource no /app.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projetos', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('projetos', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vínculo usuário ↔ tenant.
 *
 * É a fonte da verdade de `User::getTenants()` e `User::canAccessTenant()`:
 * sem linha aqui, o usuário não enxerga o tenant no seletor e toma 403 ao
 * tentar a URL direto. O `master_global` é a exceção, pelo Gate::before.
 *
 * O pivot é intencionalmente magro (sem papel, sem data de entrada): papel é
 * responsabilidade do spatie/permission, que guarda o próprio `team_id` nas
 * suas tabelas quando `permission.teams` está ligado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_user', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->unique(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_user');
    }
};

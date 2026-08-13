<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O painel em que o papel dá acesso.
 *
 * É esta coluna que `App\Models\User::canAccessPanel()` compara com o id do painel. Sem
 * ela o acesso vinha de uma lista de nomes de papel escrita dentro do model — e o /app
 * ficava aberto a qualquer usuário autenticado.
 *
 * **Nulo NÃO é coringa.** Papel sem painel não abre painel algum: o default fecha. É o
 * valor do `master_global`, que entra nos três por `Gate::before` (KitServiceProvider) e
 * nunca por esta coluna. Ver `wikis/specs/main/perfil-e-acesso-ao-painel/02-decisoes-arquiteturais.md`,
 * ADR-03 — a analogia com `roles.team_id` (onde nulo É coringa) é armadilha: lá o nulo
 * vale para a DEFINIÇÃO do papel, aqui valeria para a CONCESSÃO de acesso.
 *
 * Sem foreign key de propósito: painel não é registro de banco, é um id declarado no
 * PanelProvider e resolvido em runtime por `Filament::getPanels()`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->tabela(), function (Blueprint $table): void {
            $table->string('painel')->nullable()->index()->after('guard_name');
        });
    }

    public function down(): void
    {
        Schema::table($this->tabela(), function (Blueprint $table): void {
            $table->dropColumn('painel');
        });
    }

    private function tabela(): string
    {
        return config('permission.table_names.roles', 'roles');
    }
};

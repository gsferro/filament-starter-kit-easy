<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * De qual painel veio cada acesso.
 *
 * `authentication_log` (rappasoft) guarda um `morphTo` para `User` e mais nada sobre origem: IP,
 * dispositivo, sucesso — nunca o painel. "Quantos acessos cada painel teve" não era derivável do
 * dado existente, e é essa coluna que passa a permitir a pergunta.
 *
 * O nome da tabela sai da config e nunca é literal: o pacote permite renomeá-la
 * (`authentication-log.table_name`).
 *
 * **Aditiva e nullable de propósito.** Todo acesso anterior a esta migration fica com `painel`
 * nulo, e não há como inferir o painel de um login passado. O widget que lê a coluna agrupa os
 * nulos numa fatia própria em vez de descartá-los — descartar faria a soma das fatias divergir do
 * total real de acessos, sem nada avisar. Ver ADR-04 de
 * `wikis/specs/main/insights-das-organizacoes/`.
 *
 * A organização NÃO ganha coluna aqui, e isso é decisão, não esquecimento: no painel `/app` a
 * organização é escolhida DEPOIS de autenticar, então `Filament::getTenant()` no instante do
 * evento `Login` é nulo. Carimbar ali gravaria nulo justamente onde a organização importa. A
 * métrica por organização sai do vínculo `tenant_user`. Ver ADR-02.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tabela = $this->tabela();

        if (! Schema::hasTable($tabela) || Schema::hasColumn($tabela, 'painel')) {
            return;
        }

        Schema::table($tabela, function (Blueprint $table): void {
            $table->string('painel')->nullable()->index()->after('user_agent');
        });
    }

    /**
     * O índice cai ANTES da coluna, e essa ordem não é estilo — é requisito do SQLite.
     *
     * Medido: `dropColumn('painel')` sozinho estoura
     * `General error: 1 error in index authentication_log_painel_index after drop column: no such
     * column: "painel"`. O SQLite recria a tabela no `drop column` e revalida os índices, então um
     * índice que aponta para a coluna que está saindo o deixa num estado inválido. MySQL e
     * PostgreSQL descartam o índice sozinhos, mas o SQLite é o banco default do kit — e é nele que
     * um `migrate:rollback` seria tentado primeiro.
     *
     * Os dois `Schema::table()` separados também são requisito: no mesmo bloco o SQLite recebe as
     * duas alterações num único rebuild de tabela e falha igual.
     */
    public function down(): void
    {
        $tabela = $this->tabela();

        if (! Schema::hasTable($tabela) || ! Schema::hasColumn($tabela, 'painel')) {
            return;
        }

        Schema::table($tabela, function (Blueprint $table): void {
            $table->dropIndex(['painel']);
        });

        Schema::table($tabela, function (Blueprint $table): void {
            $table->dropColumn('painel');
        });
    }

    private function tabela(): string
    {
        return (string) config('authentication-log.table_name', 'authentication_log');
    }
};

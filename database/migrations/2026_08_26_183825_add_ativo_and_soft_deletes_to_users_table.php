<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O usuário ganha um estado (`ativo`) e a exclusão passa a ser lógica (`deleted_at`).
 *
 * ## `ativo` é boolean com default `true`, pela mesma razão de `aprovacao_pendente`
 *
 * A direção do default decide quem paga o esquecimento. Com `true`, todo caminho que cria
 * usuário — seeder, factory, convite, registro aberto, `kit:admin`, a tela — nasce ativo sem
 * uma linha a mais; só a ação de desativar escreve `false`. O inverso obrigaria seis lugares a
 * lembrar de ligar a conta, e esquecer não dá erro: dá uma pessoa trancada fora com 403.
 *
 * Fica FORA do `$fillable` do model (estado de fronteira de acesso não se escreve por atribuição
 * em massa); só `forceFill`, em `User::desativar()` e `User::reativar()`.
 *
 * ## `deleted_at` é o `SoftDeletes` do Laravel
 *
 * `delete()` passa a gravar a data em vez de remover a linha. Consequências que valem saber:
 * `users.email` continua único e a linha excluída **reserva** o endereço (ADR-05 da wiki); as
 * pivots `tenant_user` e `model_has_roles` e a tabela `vinculos_sociais` **não** são apagadas —
 * restaurar devolve exatamente o que havia. Quem lista o que está aqui é a Lixeira do /infra,
 * via trait `Recyclable` no model (ADR-06).
 *
 * Wiki: `wikis/specs/feat/status-e-exclusao-logica-de-usuario/`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // ponytail: sem índice em `ativo` — boolean de baixa cardinalidade; o filtro roda
            // sobre a tabela de usuários de uma instalação, não sobre milhões de linhas.
            $table->boolean('ativo')->default(true)->after('aprovacao_pendente');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            /*
             * Reverter RESSUSCITA quem estava na lixeira e REATIVA quem estava inativo: as duas
             * colunas somem e não há para onde guardar o estado. É a decisão menos destrutiva
             * (a alternativa seria apagar as contas excluídas de verdade), mas quem reverter com
             * usuários excluídos na base precisa saber que eles voltam a entrar.
             */
            $table->dropSoftDeletes();
            $table->dropColumn('ativo');
        });
    }
};

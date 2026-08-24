<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O cadastro que ainda não foi aprovado por ninguém.
 *
 * ## Por que boolean com default `false`, e não `aprovado_em` nullable
 *
 * A convenção do kit para "aconteceu em" é timestamp nullable — `convites.aceito_em`,
 * `convites.recusado_em`, `users.email_verified_at`. Aqui ela é a escolha errada, e o motivo é a
 * **direção do default**.
 *
 * Com `aprovado_em` nulo significando pendente, todo caminho que cria usuário passa a nascer
 * pendente e tem de lembrar de preencher a coluna. São seis hoje: `UsuarioAdminSeeder`,
 * `UserFactory`, `DemoTenancySeeder`, `Convite::aceitar()`, `php artisan kit:admin` e a tela de
 * usuários. Esquecer não dá erro nenhum: dá uma pessoa trancada fora dos três painéis, com 403 e
 * sem explicação. É a mesma classe de defeito que `.ai/rules/config.md` documenta para
 * `(int) env()` — o default silenciosamente errado.
 *
 * Com o boolean, só quem se registra pela porta aberta grava `true`. Todo o resto nasce aprovado
 * por omissão, sem uma linha a mais em lugar nenhum, e sem migration de backfill.
 *
 * ## Quem aprovou, e quando
 *
 * Não estão aqui de propósito. `App\Models\User` usa `AuditsFillables`
 * (`owen-it/laravel-auditing`), e `User::aprovar()` grava `alvo_id` e `executor_id` no channel
 * `autenticacao`. Duas colunas a mais se pagam no dia em que alguém precisar de um relatório de
 * aprovações por administrador — esse é o gatilho para reavaliar, e está escrito para não ser
 * redescoberto.
 *
 * ## A coluna fica FORA do `$fillable`
 *
 * Mesmo motivo de `email_verified_at`: estado de fronteira de acesso não se escreve por atribuição
 * em massa vinda de formulário. Só `forceFill`. Há caso de teste que reprova o contrário.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // ponytail: sem índice de propósito — boolean é de baixa cardinalidade, e o filtro de
            // pendentes roda sobre a tabela de usuários de UMA organização.
            $table->boolean('aprovacao_pendente')->default(false)->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            /*
             * Reverter APROVA quem estava pendente — não há para onde guardar o estado, e o
             * default da coluna era `false`. É a decisão menos destrutiva das duas: a alternativa
             * seria apagar a conta de quem se cadastrou e ainda não foi analisado.
             *
             * Consequência prática: quem reverter esta migration com cadastros pendentes na base
             * precisa saber que aquelas pessoas passam a entrar no /app — o papel delas, porém,
             * continua inexistente (pendente nasce SEM papel), então elas seguem levando 403 até
             * alguém dar um papel na tela de usuários. Fecha por acidente, e por acidente vale
             * registrar.
             */
            $table->dropColumn('aprovacao_pendente');
        });
    }
};

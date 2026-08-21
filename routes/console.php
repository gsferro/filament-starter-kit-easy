<?php

use BezhanSalleh\FilamentExceptions\Models\Exception;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Tapp\FilamentMailLog\Models\MailLog;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Agendamentos do kit
|--------------------------------------------------------------------------
| Nada disso roda sem um scheduler ativo: `php artisan schedule:work` em dev
| (já incluso no `composer dev`) ou o serviço `scheduler` do docker compose.
|
| O ScheduleCheck do Health falha justamente quando o agendador está parado —
| é assim que o painel infra avisa que as rotinas abaixo não estão rodando.
*/

// Alimenta a página Health do painel infra. Sem isto, a tela fica vazia até
// alguém clicar em "executar".
Schedule::command('health:check')->everyFifteenMinutes();

// Expurgo da trilha de acesso (retenção em config/authentication-log.php).
Schedule::command('authentication-log:purge')->daily();

// Lembrete dos convites pendentes (prazos em config/kit.php). Não manda nada
// quando a lista de dias está vazia, quando MAIL_MAILER=log ou quando não há
// convite pendente — então nasce ligado, e inerte numa instalação nova.
//
// ponytail: sem onOneServer(); o kit não presume cluster, e nem o health:check
// nem o purge acima usam. Em cluster, acrescente ->onOneServer() aqui.
Schedule::command('kit:convites-lembrar')->dailyAt('08:00');

/*
 * Retenção das trilhas que o kit GRAVA — exceções e e-mails enviados.
 *
 * Prazos em `config/kit.php` → `retencao`. O que está no config é a INTENÇÃO; quem
 * executa é o que vem abaixo. Sem isto, as duas tabelas crescem para sempre — e as
 * duas guardam dado sensível: stack trace com parâmetro de request, e corpo de
 * e-mail com o link de aceite do convite.
 *
 * São dois mecanismos diferentes porque os pacotes são diferentes, e vale saber por quê.
 */

/*
 * Exceções: `model:prune` do framework.
 *
 * `BezhanSalleh\FilamentExceptions\Models\Exception` declara `prunable()`, que é o
 * contrato do Laravel — o corte sai de `FilamentExceptionsPlugin::getModelPruneInterval()`,
 * configurado no InfraPanelProvider a partir do mesmo `config('kit.retencao')`.
 *
 * `--model` explícito, e não a varredura automática do comando: sem ele o `model:prune`
 * alcançaria qualquer model podável do projeto — inclusive as SUAS —, e retenção de dado
 * de terceiro não pode ser efeito colateral de um agendamento do kit.
 */
Schedule::command('model:prune', [
    '--model' => [Exception::class],
])->daily()->at('02:00');

/*
 * E-mails: exclusão direta.
 *
 * `Tapp\FilamentMailLog\Models\MailLog` **não** implementa `Prunable` (verificado no
 * vendor), então `model:prune` não o alcança — passá-lo no `--model` acima seria um
 * agendamento verde que nunca apaga nada, que é o pior resultado possível para uma
 * rotina de retenção de dado pessoal.
 *
 * ponytail: closure em vez de comando próprio. É uma cláusula `where` sobre uma tabela;
 * um `app/Console/Commands/` inteiro para isso seria arquivo por arquivo. O teto: sem
 * `chunk`, uma tabela com milhões de linhas faz um DELETE longo — se chegar lá, vire
 * comando com `chunkById()`.
 *
 * Zero ou negativo em `emails_em_dias` desliga a poda, sem apagar nada por engano.
 */
Schedule::call(function (): void {
    $dias = (int) config('kit.retencao.emails_em_dias', 14);

    if ($dias <= 0) {
        return;
    }

    MailLog::query()
        ->where('created_at', '<', now()->subDays($dias))
        ->delete();
})->daily()->at('02:10')->name('kit:limpar-trilha-de-emails');

/*
 * Histórico de import e export.
 *
 * Os models `Import` e `Export` do Filament usam a trait `Prunable` mas **não declaram
 * `prunable()`** (verificado no vendor) — passá-los ao `model:prune` daria
 * `LogicException`, e não há como acrescentar o método sem editar `vendor/`. Daí a
 * exclusão direta, no mesmo padrão da trilha de e-mails acima.
 *
 * `failed_import_rows` cai por cascata (`import_id` é FK com `cascadeOnDelete`).
 *
 * A do export apaga o ARQUIVO antes da linha, via `deleteFileDirectory()` do próprio
 * model: invertido, ficaria arquivo órfão em disco sem nada que aponte para ele — e é
 * arquivo com dado exportado da aplicação.
 *
 * ponytail: closures, não comandos. São duas cláusulas `where`. O teto é o mesmo da poda
 * de e-mails: sem `chunk`, tabela com milhões de linhas faz um DELETE longo.
 */
Schedule::call(function (): void {
    $dias = (int) config('kit.retencao.importacoes_em_dias', 30);

    if ($dias <= 0) {
        return;
    }

    Import::query()
        ->where('created_at', '<', now()->subDays($dias))
        ->delete();
})->daily()->at('02:20')->name('kit:limpar-historico-de-importacoes');

Schedule::call(function (): void {
    $dias = (int) config('kit.retencao.exportacoes_em_dias', 30);

    if ($dias <= 0) {
        return;
    }

    Export::query()
        ->where('created_at', '<', now()->subDays($dias))
        ->each(function (Export $export): void {
            $export->deleteFileDirectory();
            $export->delete();
        });
})->daily()->at('02:30')->name('kit:limpar-historico-de-exportacoes');

// Backups. Ligue quando configurar o destino em config/backup.php.
// Schedule::command('backup:clean')->daily()->at('01:00');
// Schedule::command('backup:run')->daily()->at('01:30');

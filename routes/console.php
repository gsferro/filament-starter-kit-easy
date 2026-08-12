<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

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

// Backups. Ligue quando configurar o destino em config/backup.php.
// Schedule::command('backup:clean')->daily()->at('01:00');
// Schedule::command('backup:run')->daily()->at('01:30');

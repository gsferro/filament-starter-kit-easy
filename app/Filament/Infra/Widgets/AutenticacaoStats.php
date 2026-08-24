<?php

declare(strict_types=1);

namespace App\Filament\Infra\Widgets;

use App\Filament\Concerns\ExigePermissaoDoWidget;
use Filament\Widgets\StatsOverviewWidget;
use Gsferro\FilamentStatPlusEasy\Widgets\StatPlus;
use Illuminate\Support\Facades\Schema;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

/**
 * Movimento de autenticação nas últimas 24 horas.
 *
 * Janela de 24h e não total acumulado: o log de acesso só vira sinal de
 * segurança quando comparado com o ritmo normal do dia. "3.412 falhas desde
 * sempre" não alarma ninguém; "3.412 falhas hoje" é um ataque de força bruta.
 */
class AutenticacaoStats extends StatsOverviewWidget
{
    use ExigePermissaoDoWidget;

    protected static ?int $sort = 110;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Acessos nas últimas 24 horas';

    protected static function fonteDeDadosDisponivel(): bool
    {
        return (bool) rescue(
            fn (): bool => Schema::hasTable((string) config('authentication-log.table_name', 'authentication_log')),
            false,
            report: false,
        );
    }

    /**
     * @return array<int, StatPlus>
     */
    protected function getStats(): array
    {
        $desde = now()->subDay();

        $sucessos = AuthenticationLog::query()
            ->where('login_at', '>=', $desde)
            ->where('login_successful', true)
            ->count();

        $falhas = AuthenticationLog::query()
            ->where('login_at', '>=', $desde)
            ->where('login_successful', false)
            ->count();

        // Sessão aberta = login bem-sucedido sem logout registrado. Não é o
        // mesmo que sessão viva (a do Laravel expira sozinha), mas é o que o
        // log sabe — e é o que mostra conta esquecida logada.
        $sessoesAbertas = AuthenticationLog::query()
            ->where('login_successful', true)
            ->whereNull('logout_at')
            ->count();

        return [
            StatPlus::make('Logins bem-sucedidos', $sucessos)
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->iconColor('success')
                ->accentColor('success')
                ->description('Entradas confirmadas nas últimas 24h'),

            StatPlus::make('Tentativas falhas', $falhas)
                ->icon('heroicon-o-lock-closed')
                ->iconColor($this->corDasFalhas($falhas, $sucessos))
                ->accentColor($this->corDasFalhas($falhas, $sucessos))
                ->description('Senha errada, conta inexistente ou 2FA recusado'),

            StatPlus::make('Sessões sem logout', $sessoesAbertas)
                ->icon('heroicon-o-computer-desktop')
                ->iconColor('gray')
                ->accentColor('gray')
                ->description('Acumulado — inclui sessões já expiradas'),
        ];
    }

    /**
     * Vermelho quando as falhas passam dos sucessos: é a assinatura de
     * varredura de credenciais, não de gente digitando errado.
     */
    private function corDasFalhas(int $falhas, int $sucessos): string
    {
        return match (true) {
            $falhas === 0        => 'gray',
            $falhas > $sucessos  => 'danger',
            default              => 'warning',
        };
    }
}

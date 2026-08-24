<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Filament\Concerns\ExigePermissaoDoWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LaBoiteACode\FilamentDashboardWidgets\Data\Goal;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\GoalProgressWidget;

/**
 * Taxa de conclusão das jornadas de onboarding: de tudo que foi iniciado,
 * quanto chegou ao fim.
 *
 * GoalProgress e não stat: a pergunta é "quanto falta", e o valor absoluto
 * ("12 jornadas concluídas") não diz nada sem o denominador. A barra mostra
 * atual/alvo na mesma leitura.
 *
 * O alvo é o que foi INICIADO, não `usuários × jornadas ativas`: jornada com
 * condição de visibilidade não existe para todo mundo, então o produto
 * inventaria um denominador que ninguém deveria alcançar.
 */
class ProgressoOnboarding extends GoalProgressWidget
{
    use ExigePermissaoDoWidget;

    protected static ?int $sort = 60;

    protected int|string|array $columnSpan = 1;

    /**
     * O onboarding é um plugin opcional (`filament-onboarding.enabled`) e as
     * tabelas podem simplesmente não existir. Sem elas o widget some em vez de
     * derrubar o dashboard.
     */
    protected static function fonteDeDadosDisponivel(): bool
    {
        return (bool) rescue(
            fn (): bool => Schema::hasTable(self::tabelaDeProgresso()),
            false,
            report: false,
        );
    }

    public function getEmptyStateHeading(): string
    {
        return 'Nenhuma jornada iniciada';
    }

    public function getEmptyStateIcon(): string
    {
        return 'heroicon-o-map';
    }

    protected function getGoal(): Goal
    {
        $progresso = DB::table(self::tabelaDeProgresso());

        $iniciadas   = (clone $progresso)->count();
        $concluidas  = (clone $progresso)->whereNotNull('completed_at')->count();
        $abandonadas = (clone $progresso)->whereNotNull('dismissed_at')->whereNull('completed_at')->count();

        return Goal::make('Jornadas de onboarding concluídas', $concluidas, $iniciadas)
            ->icon('heroicon-o-map')
            ->color($this->corDoProgresso($concluidas, $iniciadas))
            ->description($this->descrever($abandonadas))
            // "Faltam N" desligado de propósito: o pacote só traduz esse texto
            // para en/fr, e a barra já mostra "1 / 3" e o percentual — o
            // restante seria a mesma informação, em inglês.
            ->showRemaining(false)
            ->showPercentage();
    }

    /**
     * Verde só a partir de 80%: abaixo disso a jornada está travando gente no
     * meio do caminho, que é exatamente o que o widget existe para denunciar.
     */
    private function corDoProgresso(int $concluidas, int $iniciadas): string
    {
        if ($iniciadas === 0) {
            return 'gray';
        }

        $percentual = ($concluidas / $iniciadas) * 100;

        return match (true) {
            $percentual >= 80 => 'success',
            $percentual >= 50 => 'warning',
            default           => 'danger',
        };
    }

    private function descrever(int $abandonadas): string
    {
        if ($abandonadas === 0) {
            return 'Sobre as jornadas efetivamente iniciadas';
        }

        return $abandonadas === 1
            ? '1 jornada dispensada antes do fim'
            : "{$abandonadas} jornadas dispensadas antes do fim";
    }

    private static function tabelaDeProgresso(): string
    {
        return (string) config('filament-onboarding.tables.flow_progress', 'onboarding_flow_progress');
    }
}

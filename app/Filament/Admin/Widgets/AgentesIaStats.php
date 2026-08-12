<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\AgenteIa;
use Filament\Widgets\StatsOverviewWidget;
use Gsferro\FilamentStatPlusEasy\Widgets\StatPlus;

/**
 * Tamanho e saúde do catálogo de agentes de IA.
 *
 * O catálogo não tem exclusão física (a coluna `ativo` é a "lixeira"), então
 * "inativos" é informação de gestão e não resíduo: são agentes que existem,
 * ocupam slug e podem voltar a rodar com um clique.
 */
class AgentesIaStats extends StatsOverviewWidget
{
    protected static ?int $sort = 40;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Agentes de IA';

    /**
     * @return array<int, StatPlus>
     */
    protected function getStats(): array
    {
        $total  = AgenteIa::query()->count();
        $ativos = AgenteIa::query()->where('ativo', true)->count();

        return [
            StatPlus::make('Agentes cadastrados', $total)
                ->icon('heroicon-o-cpu-chip')
                ->iconColor('primary')
                ->accentColor('primary')
                ->description('Catálogo completo, ativos e inativos'),

            StatPlus::make('Ativos', $ativos)
                ->icon('heroicon-o-bolt')
                ->iconColor('success')
                ->accentColor('success')
                ->description('Disponíveis para execução'),

            StatPlus::make('Inativos', $total - $ativos)
                ->icon('heroicon-o-pause-circle')
                ->iconColor('gray')
                ->accentColor('gray')
                ->description('Desligados pela flag `ativo`'),
        ];
    }
}

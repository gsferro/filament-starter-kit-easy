<?php

declare(strict_types=1);

namespace App\Filament\Infra\Widgets;

use DateTimeInterface;
use Fomvasss\AiTasks\Models\AiRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use LaBoiteACode\FilamentDashboardWidgets\Data\Trend;
use LaBoiteACode\FilamentDashboardWidgets\Data\TrendPoint;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\TrendWidget;

/**
 * Execuções de IA por dia nos últimos 14 dias.
 *
 * Trend e não ComparisonChart: há UMA série (volume diário) — o
 * ComparisonChart existe para sobrepor duas, e desenhar uma só nele daria um
 * gráfico com legenda inútil. A comparação com a quinzena anterior vira um
 * número no subtítulo, que é onde ela é lida de fato.
 */
class IaExecucoesPorDia extends TrendWidget
{
    private const DIAS = 14;

    protected static ?int $sort = 80;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '220px';

    public static function canView(): bool
    {
        return (bool) rescue(
            fn (): bool => Schema::hasTable((string) config('ai-tasks.table', 'ai_runs')),
            false,
            report: false,
        );
    }

    public function getEmptyStateHeading(): string
    {
        return 'Nenhuma execução de IA no período';
    }

    /**
     * Subtítulo próprio: o do pacote monta a comparação com a string
     * "Compared to the previous period", que só tem tradução en/fr.
     */
    public function getDescription(): ?string
    {
        $tendencia = $this->getTrend();
        $partes    = [$tendencia->getFormattedValue()];

        if ($tendencia->hasComparison()) {
            $partes[] = $tendencia->getComparisonLabel().' em relação aos '.self::DIAS.' dias anteriores';
        }

        return implode(' • ', array_filter($partes));
    }

    protected function getTrend(): Trend
    {
        $primeiroDia = Carbon::today()->subDays(self::DIAS - 1);
        $porDia      = $this->contarPorDia($primeiroDia, Carbon::today()->endOfDay());

        $pontos = [];
        $total  = 0;

        // O eixo é construído a partir do CALENDÁRIO, não do resultado da
        // consulta: dia sem execução tem que aparecer como zero, senão a linha
        // "pula" o buraco e uma parada de dois dias vira um trecho reto.
        for ($i = 0; $i < self::DIAS; $i++) {
            $dia        = $primeiroDia->copy()->addDays($i);
            $quantidade = $porDia[$dia->toDateString()] ?? 0;
            $total += $quantidade;

            $pontos[] = TrendPoint::make($dia->format('d/m'), $quantidade);
        }

        return Trend::make('Execuções de IA por dia')
            ->type('area')
            ->color('primary')
            ->points($pontos)
            ->value($total)
            ->comparison($this->variacaoContraPeriodoAnterior($total, $primeiroDia))
            ->formatUsing(fn (mixed $valor): string => $valor.' execuções em '.self::DIAS.' dias');
    }

    /**
     * Agrupamento em PHP e não `GROUP BY DATE(created_at)`: a função de data
     * muda de nome em cada banco (SQLite/MySQL/PostgreSQL) e o kit roda nos
     * três. A janela é de 14 dias, então o volume trazido é limitado por
     * construção.
     *
     * @return array<string, int>
     */
    private function contarPorDia(Carbon $de, Carbon $ate): array
    {
        return AiRun::query()
            ->whereBetween('created_at', [$de, $ate])
            ->get(['created_at'])
            ->map(fn (AiRun $execucao): mixed => $execucao->getAttribute('created_at'))
            ->filter(fn (mixed $data): bool => $data instanceof DateTimeInterface)
            ->countBy(fn (DateTimeInterface $data): string => $data->format('Y-m-d'))
            ->all();
    }

    /**
     * Variação percentual contra os 14 dias imediatamente anteriores.
     * Devolve `null` quando não há base de comparação — inventar "+100%" a
     * partir de zero é ruído, não informação.
     */
    private function variacaoContraPeriodoAnterior(int $total, Carbon $primeiroDia): ?float
    {
        $anterior = AiRun::query()
            ->whereBetween('created_at', [
                $primeiroDia->copy()->subDays(self::DIAS),
                $primeiroDia->copy()->subSecond(),
            ])
            ->count();

        if ($anterior === 0) {
            return null;
        }

        return round((($total - $anterior) / $anterior) * 100, 2);
    }
}

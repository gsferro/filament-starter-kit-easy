<?php

declare(strict_types=1);

namespace App\Filament\Infra\Widgets;

use DateTimeInterface;
use Fomvasss\AiTasks\Models\AiRun;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

/**
 * Execuções de IA por dia nos últimos 14 dias.
 *
 * Área e não barra: a leitura que importa é a CONTINUIDADE do volume — uma parada de dois dias
 * tem de saltar aos olhos.
 *
 * Migrado do `TrendWidget` do laboiteacode/filament-dashboard-widgets para o ApexCharts na
 * 0.15.0. A regra do kit passou a ser: GRÁFICO é ApexCharts, stat card é StatPlus, todo o resto
 * é dashboard-widgets. Este era o único gráfico do kit e virava exceção viva à regra — e
 * contraexemplo no repositório pesa mais que convenção escrita, porque se copia o vizinho antes
 * de ler a convenção. O nome da classe foi PRESERVADO de propósito: ele é entidade do Shield, e
 * renomear criaria permission nova deixando `View:IaExecucoesPorDia` órfã em toda instalação já
 * existente. Ver ADR-01 e ADR-02 da wiki `graficos-com-apexcharts`.
 */
class IaExecucoesPorDia extends ApexChartWidget
{
    private const DIAS = 14;

    protected static ?string $chartId = 'iaExecucoesPorDia';

    protected static ?string $heading = 'Execuções de IA por dia';

    protected static ?int $sort = 80;

    protected int|string|array $columnSpan = 'full';

    /*
     * O default do pacote é 5 SEGUNDOS — por widget, por aba aberta. Aqui o dado é diário:
     * atualizar a cada 5 s não muda um pixel e cobra do banco proporcionalmente às abas que
     * alguém esqueceu abertas. Ver ADR-04 da wiki.
     */
    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return (bool) rescue(
            fn (): bool => Schema::hasTable((string) config('ai-tasks.table', 'ai_runs')),
            false,
            report: false,
        );
    }

    /**
     * Subtítulo próprio, com a comparação contra a quinzena anterior.
     *
     * Ela vinha de graça no `Trend::comparison()` do widget antigo; perdê-la na migração seria
     * entregar um gráfico mais bonito e menos informativo que o que existia.
     */
    protected function getSubheading(): null|string|Htmlable|View
    {
        $primeiroDia = Carbon::today()->subDays(self::DIAS - 1);
        $total       = array_sum($this->quantidadesPorDia($primeiroDia));
        $variacao    = $this->variacaoContraPeriodoAnterior($total, $primeiroDia);

        $partes = [$total.' execuções em '.self::DIAS.' dias'];

        if ($variacao !== null) {
            $partes[] = sprintf('%+.2f%%', $variacao).' em relação aos '.self::DIAS.' dias anteriores';
        }

        return implode(' • ', $partes);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        $primeiroDia  = Carbon::today()->subDays(self::DIAS - 1);
        $quantidades  = $this->quantidadesPorDia($primeiroDia);
        $rotulos      = [];

        for ($i = 0; $i < self::DIAS; $i++) {
            $rotulos[] = $primeiroDia->copy()->addDays($i)->format('d/m');
        }

        return [
            'chart' => [
                'type'    => 'area',
                'height'  => 220,
                'toolbar' => ['show' => false],
            ],
            'series' => [[
                'name' => 'Execuções',
                'data' => array_values($quantidades),
            ]],
            'xaxis' => [
                'categories' => $rotulos,
                'labels'     => ['style' => ['fontFamily' => 'inherit']],
            ],
            'yaxis' => [
                'labels' => ['style' => ['fontFamily' => 'inherit']],
            ],
            // Token semântico, nunca hexadecimal: é isto que faz o gráfico acompanhar o tema e a
            // cor da organização. Ver ADR-05 da wiki.
            'colors'      => ['var(--primary-500)'],
            'stroke'      => ['curve' => 'smooth', 'width' => 2],
            'dataLabels'  => ['enabled' => false],
        ];
    }

    /**
     * Uma posição por dia do CALENDÁRIO, incluindo os dias sem execução.
     *
     * O eixo é construído a partir do calendário, e não do resultado da consulta: dia sem
     * execução tem que aparecer como zero, senão a linha "pula" o buraco e uma parada de dois
     * dias vira um trecho reto — mentindo sobre a operação.
     *
     * @return array<int, int>
     */
    private function quantidadesPorDia(Carbon $primeiroDia): array
    {
        $porDia = $this->contarPorDia($primeiroDia, Carbon::today()->endOfDay());

        $quantidades = [];

        for ($i = 0; $i < self::DIAS; $i++) {
            $dia           = $primeiroDia->copy()->addDays($i);
            $quantidades[] = $porDia[$dia->toDateString()] ?? 0;
        }

        return $quantidades;
    }

    /**
     * Agrupamento em PHP e não `GROUP BY DATE(created_at)`: a função de data muda de nome em
     * cada banco (SQLite/MySQL/PostgreSQL) e o kit roda nos três. A janela é de 14 dias, então
     * o volume trazido é limitado por construção.
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
     *
     * Devolve `null` quando não há base de comparação — inventar "+100%" a partir de zero é
     * ruído, não informação.
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

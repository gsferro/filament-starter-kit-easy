<?php

declare(strict_types=1);

namespace App\Filament\Infra\Widgets;

use App\Filament\Concerns\ExigePermissaoDoWidget;
use Fomvasss\AiTasks\Models\AiRun;
use Illuminate\Support\Facades\Schema;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

/**
 * Execuções de IA por status — quantas deram erro, em proporção.
 *
 * Rosca porque as fatias fecham o todo e a leitura procurada é a PROPORÇÃO entre categorias
 * ("um em cada cinco falhou"), não um número absoluto. Diferente do `SaudeAplicacaoPorStatus`,
 * que recusou rosca de propósito: lá a pergunta é "quanto da barra ainda é verde", e barra
 * horizontal responde mais rápido que ângulo. Ver ADR-03 da wiki `graficos-com-apexcharts`.
 *
 * Responde uma pergunta que nenhum widget do kit responde hoje: `ai_runs.status` não aparece em
 * lugar nenhum, embora `ai_runs.error` seja a coluna que se abre quando algo dá errado.
 */
class IaExecucoesPorStatus extends ApexChartWidget
{
    use ExigePermissaoDoWidget;

    protected static ?string $chartId = 'iaExecucoesPorStatus';

    protected static ?string $heading = 'Execuções de IA por status';

    protected static ?string $subheading = 'Proporção entre sucesso e falha no total registrado';

    protected static ?int $sort = 85;

    protected int|string|array $columnSpan = 1;

    // Muda ao longo do dia, mas não é painel de sala de guerra: um minuto informa sem virar
    // consulta contínua. O default do pacote seria 5 s — ver ADR-04 da wiki.
    protected ?string $pollingInterval = '60s';

    protected static function fonteDeDadosDisponivel(): bool
    {
        return (bool) rescue(
            fn (): bool => Schema::hasTable((string) config('ai-tasks.table', 'ai_runs')),
            false,
            report: false,
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        /*
         * `GROUP BY` é seguro AQUI, ao contrário do gráfico diário: agrupa por coluna de texto,
         * sem função de data — não há a incompatibilidade de nome entre SQLite, MySQL e
         * PostgreSQL que obriga o `IaExecucoesPorDia` a contar em PHP.
         */
        $porStatus = AiRun::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Base vazia devolve UMA fatia zerada, e não `series: []`: array vazio faz o ApexCharts
        // desenhar um canvas em branco, sem legenda e sem explicação — que é o estado de toda
        // instalação nova.
        if ($porStatus->isEmpty()) {
            return $this->opcoesDaRosca(['Nenhuma execução' => 0]);
        }

        return $this->opcoesDaRosca($porStatus->all());
    }

    /**
     * @param  array<string, int>  $porStatus
     * @return array<string, mixed>
     */
    private function opcoesDaRosca(array $porStatus): array
    {
        return [
            'chart'  => ['type' => 'donut', 'height' => 260],
            'series' => array_map(intval(...), array_values($porStatus)),
            'labels' => array_keys($porStatus),
            'colors' => array_map($this->corDoStatus(...), array_keys($porStatus)),
            'legend' => ['position' => 'bottom', 'fontFamily' => 'inherit'],
        ];
    }

    /**
     * Mapa de cor por status, com FALLBACK obrigatório.
     *
     * O pacote de IA pode ganhar estados novos a qualquer upgrade. Um `match` sem `default` (ou
     * um `$mapa[$status]` direto) transformaria isso em `Undefined array key` — e widget que
     * estoura derruba o dashboard INTEIRO, não só o próprio card.
     */
    private function corDoStatus(string $status): string
    {
        return match (mb_strtolower($status)) {
            'success', 'succeeded', 'completed', 'done' => 'var(--success-500)',
            'error', 'failed', 'failure'                => 'var(--danger-500)',
            'pending', 'running', 'queued'              => 'var(--warning-500)',
            default                                     => 'var(--gray-500)',
        };
    }
}

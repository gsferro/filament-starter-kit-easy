<?php

declare(strict_types=1);

namespace App\Filament\Infra\Widgets;

use App\Filament\Concerns\ExigePermissaoDoWidget;
use Filament\Widgets\StatsOverviewWidget;
use Gsferro\FilamentStatPlusEasy\Widgets\StatPlus;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Estado das filas segundo o croustibat/filament-jobs-monitor.
 *
 * A tabela é `queue_monitors` (uma linha por job monitorado), não a
 * `job_batches` do Laravel: só ela guarda `failed`, `progress` e os tempos de
 * início/fim que interessam aqui.
 *
 * StatsOverview porque as três grandezas são independentes — não formam
 * composição (um job concluído hoje e um em execução agora não somam um todo
 * com significado).
 */
class FilasStats extends StatsOverviewWidget
{
    use ExigePermissaoDoWidget;

    protected static ?int $sort = 40;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Filas e jobs';

    protected static function fonteDeDadosDisponivel(): bool
    {
        return (bool) rescue(
            fn (): bool => Schema::hasTable('queue_monitors'),
            false,
            report: false,
        );
    }

    /**
     * @return array<int, StatPlus>
     */
    protected function getStats(): array
    {
        // `failed` é boolean: comparar com `true` (e não com `1`) deixa o
        // Laravel traduzir para cada driver — PostgreSQL rejeita `failed = 1`.
        $concluidos  = $this->consulta()->where('failed', false)->whereNotNull('finished_at')->count();
        $falhados    = $this->consulta()->where('failed', true)->count();
        $emAndamento = $this->consulta()->where('failed', false)->whereNull('finished_at')->count();

        $processados = $concluidos + $falhados;

        return [
            StatPlus::make('Jobs concluídos', $concluidos)
                ->icon('heroicon-o-check-circle')
                ->iconColor('success')
                ->accentColor('success')
                ->description('Terminaram sem exceção'),

            StatPlus::make('Jobs falhados', $falhados)
                ->icon('heroicon-o-x-circle')
                ->iconColor($falhados > 0 ? 'danger' : 'gray')
                ->accentColor($falhados > 0 ? 'danger' : 'gray')
                ->description($this->taxaDeFalha($falhados, $processados)),

            StatPlus::make('Em andamento', $emAndamento)
                ->icon('heroicon-o-arrow-path')
                ->iconColor($emAndamento > 0 ? 'info' : 'gray')
                ->accentColor($emAndamento > 0 ? 'info' : 'gray')
                // Sem `finished_at` o job ou está rodando ou morreu sem
                // registrar o fim — nos dois casos é o número que precisa de
                // olho quando não desce.
                ->description('Iniciados e ainda sem registro de fim'),
        ];
    }

    private function consulta(): Builder
    {
        return DB::table('queue_monitors');
    }

    private function taxaDeFalha(int $falhados, int $processados): string
    {
        if ($processados === 0) {
            return 'Nenhum job processado ainda';
        }

        return round(($falhados / $processados) * 100, 1).'% dos jobs processados';
    }
}

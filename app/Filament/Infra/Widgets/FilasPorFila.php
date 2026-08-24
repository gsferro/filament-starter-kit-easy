<?php

declare(strict_types=1);

namespace App\Filament\Infra\Widgets;

use App\Filament\Concerns\ExigePermissaoDoWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LaBoiteACode\FilamentDashboardWidgets\Data\BreakdownItem;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\BreakdownWidget;

/**
 * Volume de jobs por fila, com as falhas de cada uma na descrição.
 *
 * Breakdown por FILA e não por status: o status já está no
 * `FilasStats` logo acima, e ali ele responde "está tudo bem?". A pergunta que
 * sobra é "onde", e essa só a fila responde — é ela que aponta o worker a
 * reiniciar ou o `--queue` que ficou sem consumidor.
 *
 * A barra fica pintada de vermelho quando a fila tem falha: é o único jeito de
 * a fila problemática saltar sem obrigar a ler número por número.
 */
class FilasPorFila extends BreakdownWidget
{
    use ExigePermissaoDoWidget;

    protected static ?int $sort = 50;

    protected int|string|array $columnSpan = 1;

    protected static function fonteDeDadosDisponivel(): bool
    {
        return (bool) rescue(
            fn (): bool => Schema::hasTable('queue_monitors'),
            false,
            report: false,
        );
    }

    public function getHeading(): ?string
    {
        return 'Jobs por fila';
    }

    public function getHeadingDescription(): ?string
    {
        return 'Total monitorado e falhas de cada fila';
    }

    public function getEmptyStateHeading(): string
    {
        return 'Nenhum job monitorado';
    }

    public function getEmptyStateIcon(): string
    {
        return 'heroicon-o-queue-list';
    }

    protected function shouldSortByValue(): bool
    {
        return true;
    }

    /**
     * @return array<int, BreakdownItem>
     */
    protected function getItems(): array
    {
        $totais = $this->contarPorFila(apenasFalhas: false);
        $falhas = $this->contarPorFila(apenasFalhas: true);

        $itens = [];

        foreach ($totais as $fila => $total) {
            $comFalha = $falhas[$fila] ?? 0;

            $itens[] = BreakdownItem::make($fila, $total)
                ->icon('heroicon-o-queue-list')
                ->color($comFalha > 0 ? 'danger' : 'success')
                ->description($comFalha > 0 ? "{$comFalha} com falha" : 'sem falhas');
        }

        return $itens;
    }

    /**
     * Duas consultas agregadas em vez de um `SUM(CASE WHEN ...)`: a expressão
     * condicional muda de sintaxe entre SQLite, MySQL e PostgreSQL, e o kit
     * roda nos três. Dois COUNT agrupados são portáveis e igualmente baratos.
     *
     * @return array<string, int>
     */
    private function contarPorFila(bool $apenasFalhas): array
    {
        $consulta = DB::table('queue_monitors')
            ->selectRaw('queue, count(*) as total')
            ->groupBy('queue');

        if ($apenasFalhas) {
            $consulta->where('failed', true);
        }

        $contagem = [];

        foreach ($consulta->get() as $linha) {
            // `queue` é nullable na tabela: job despachado sem fila explícita
            // cai na `default` do connection — mostrar "—" esconderia isso.
            $fila = is_string($linha->queue) && $linha->queue !== '' ? $linha->queue : 'default';

            $contagem[$fila] = (int) $linha->total;
        }

        return $contagem;
    }
}

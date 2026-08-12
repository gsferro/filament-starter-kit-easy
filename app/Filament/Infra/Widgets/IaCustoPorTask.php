<?php

declare(strict_types=1);

namespace App\Filament\Infra\Widgets;

use Fomvasss\AiTasks\Models\AiRun;
use Illuminate\Support\Facades\Schema;
use LaBoiteACode\FilamentDashboardWidgets\Data\BreakdownItem;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\BreakdownWidget;

/**
 * Onde o dinheiro de IA está sendo gasto, task a task.
 *
 * Breakdown e não rosca: o valor absoluto de cada task importa tanto quanto a
 * fatia (o time precisa ler "US$ 12,3400", não "38%"), e o Breakdown mostra
 * rótulo, valor formatado e barra na mesma linha. Numa rosca o número exato
 * só apareceria no tooltip.
 */
class IaCustoPorTask extends BreakdownWidget
{
    protected static ?int $sort = 90;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return (bool) rescue(
            fn (): bool => Schema::hasTable((string) config('ai-tasks.table', 'ai_runs')),
            false,
            report: false,
        );
    }

    public function getHeading(): ?string
    {
        return 'Custo de IA por task';
    }

    public function getHeadingDescription(): ?string
    {
        return 'Somatório de `cost` em dólares, por nome de task';
    }

    public function getEmptyStateHeading(): string
    {
        return 'Nenhum custo registrado';
    }

    public function getEmptyStateDescription(): ?string
    {
        return 'O driver só grava `cost` quando o provedor devolve o consumo.';
    }

    public function getEmptyStateIcon(): string
    {
        return 'heroicon-o-banknotes';
    }

    protected function shouldSortByValue(): bool
    {
        return true;
    }

    /**
     * Só as dez tasks mais caras: a cauda longa de centavos empurraria as
     * linhas que importam para fora da tela.
     */
    protected function getLimit(): ?int
    {
        return 10;
    }

    /**
     * @return array<int, BreakdownItem>
     */
    protected function getItems(): array
    {
        return AiRun::query()
            ->selectRaw('task, SUM(cost) as custo_total')
            ->whereNotNull('cost')
            ->groupBy('task')
            ->get()
            ->map(fn (AiRun $linha): BreakdownItem => BreakdownItem::make(
                (string) $linha->getAttribute('task'),
                (float) $linha->getAttribute('custo_total'),
            )
                ->icon('heroicon-o-banknotes')
                // 4 casas porque `cost` é decimal(12,8): com 2 casas quase toda
                // task apareceria como US$ 0,00 e o widget não diria nada.
                ->formatUsing(fn (float $valor): string => 'US$ '.number_format($valor, 4, ',', '.')))
            ->all();
    }
}

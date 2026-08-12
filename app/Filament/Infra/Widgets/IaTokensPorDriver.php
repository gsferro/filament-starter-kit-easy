<?php

declare(strict_types=1);

namespace App\Filament\Infra\Widgets;

use Fomvasss\AiTasks\Models\AiRun;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Number;
use LaBoiteACode\FilamentDashboardWidgets\Data\BreakdownItem;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\BreakdownWidget;

/**
 * Tokens consumidos por driver (openai, anthropic, ollama...).
 *
 * Breakdown pelo mesmo motivo do custo por task: o número absoluto é a unidade
 * de negociação com o fornecedor. Aqui ele responde à pergunta de dependência
 * — "se este provedor cair amanhã, quanto do nosso volume para?".
 */
class IaTokensPorDriver extends BreakdownWidget
{
    protected static ?int $sort = 100;

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
        return 'Tokens por driver';
    }

    public function getHeadingDescription(): ?string
    {
        return 'Entrada + saída, somadas por provedor';
    }

    public function getEmptyStateHeading(): string
    {
        return 'Nenhum token contabilizado';
    }

    public function getEmptyStateIcon(): string
    {
        return 'heroicon-o-hashtag';
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
        return AiRun::query()
            // COALESCE porque as colunas de token são nullable: sem ele, uma
            // única execução sem `tokens_out` zera a soma inteira do driver em
            // alguns bancos (NULL + número = NULL).
            ->selectRaw('driver, SUM(COALESCE(tokens_in, 0) + COALESCE(tokens_out, 0)) as tokens')
            ->groupBy('driver')
            ->get()
            ->map(fn (AiRun $linha): BreakdownItem => BreakdownItem::make(
                (string) $linha->getAttribute('driver'),
                (int) $linha->getAttribute('tokens'),
            )
                ->icon('heroicon-o-cpu-chip')
                ->formatUsing(fn (float $valor): string => Number::format($valor).' tokens'))
            // Driver que nunca reportou token não vira barra vazia na lista.
            ->filter(fn (BreakdownItem $item): bool => $item->getValue() > 0)
            ->values()
            ->all();
    }
}

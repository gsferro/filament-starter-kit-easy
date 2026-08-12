<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\AgenteIa;
use LaBoiteACode\FilamentDashboardWidgets\Data\BreakdownItem;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\SegmentBarWidget;

/**
 * Como o catálogo se divide entre provedores de LLM.
 *
 * SegmentBar e não Breakdown: aqui as partes REALMENTE fecham o todo (cada
 * agente tem exatamente um provider), e a pergunta é de concentração — "estamos
 * presos a um fornecedor só?". Uma barra única segmentada responde isso de
 * relance; uma lista de barras separadas obrigaria a somar de cabeça.
 */
class AgentesIaPorProvider extends SegmentBarWidget
{
    /** Cores cicladas por ordem de tamanho — o pacote não escolhe cor sozinho. */
    private const CORES = ['primary', 'success', 'info', 'warning', 'danger', 'gray'];

    protected static ?int $sort = 50;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return 'Agentes por provedor';
    }

    public function getHeadingDescription(): ?string
    {
        return 'Concentração do catálogo por fornecedor de LLM';
    }

    public function getEmptyStateHeading(): string
    {
        return 'Nenhum agente cadastrado';
    }

    public function getEmptyStateIcon(): string
    {
        return 'heroicon-o-cpu-chip';
    }

    /**
     * @return array<int, BreakdownItem>
     */
    protected function getSegments(): array
    {
        // Agrupamento em PHP e não em SQL: o catálogo é um punhado de linhas
        // (é configuração, não movimento) e `provider` é NULLABLE — agrupar no
        // banco devolveria uma chave nula que cada driver representa de um
        // jeito. Em PHP o fallback fica explícito e legível.
        $porProvider = AgenteIa::query()
            ->get(['provider'])
            ->countBy(fn (AgenteIa $agente): string => $agente->provider ?? 'padrão de config/ai.php')
            ->sortDesc();

        $indice = 0;

        return $porProvider
            ->map(function (int $total, string $provider) use (&$indice): BreakdownItem {
                $cor = self::CORES[$indice % count(self::CORES)];
                $indice++;

                return BreakdownItem::make($provider, $total)->color($cor);
            })
            ->values()
            ->all();
    }
}

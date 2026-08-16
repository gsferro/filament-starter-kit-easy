<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Convite;
use Illuminate\Support\Facades\Schema;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

/**
 * Convites por situação — quantos viraram conta e quantos morreram no caminho.
 *
 * Rosca: as quatro situações são mutuamente exclusivas e somam o total de convites enviados —
 * o caso canônico de composição, e a leitura procurada é a proporção entre elas.
 */
class ConvitesPorSituacao extends ApexChartWidget
{
    /**
     * Ordem e cor FIXAS, com zero para a situação sem nenhum convite.
     *
     * Se a legenda mudasse de ordem ou de cor conforme o dado, duas visitas ao dashboard
     * deixariam de ser comparáveis — e "a fatia vermelha cresceu" perderia o sentido.
     */
    private const SITUACOES = [
        'Aceito'   => 'var(--success-500)',
        'Pendente' => 'var(--warning-500)',
        'Recusado' => 'var(--danger-500)',
        'Expirado' => 'var(--gray-500)',
    ];

    protected static ?string $chartId = 'convitesPorSituacao';

    protected static ?string $heading = 'Convites por situação';

    protected static ?string $subheading = 'Quantos dos convites enviados viraram conta';

    protected static ?int $sort = 35;

    protected int|string|array $columnSpan = 1;

    // Convite é evento raro: não há o que atualizar a cada poucos segundos. O default do pacote
    // seria 5 s, por aba aberta — ver ADR-04 da wiki `graficos-com-apexcharts`.
    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return (bool) rescue(
            fn (): bool => Schema::hasTable((new Convite)->getTable()),
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
         * A situação vem de `Convite::situacao()`, NUNCA reescrita em SQL.
         *
         * Não há coluna de status: o estado é derivado de `aceito_em`, `recusado_em` e
         * `expira_em`, e a regra tem uma precedência que um `where` ingênuo erra —
         * ACEITO VENCE EXPIRADO (um convite aceito ontem não vira "Expirado" hoje). O próprio
         * método avisa que duas telas derivando o mesmo estado por dois caminhos é como a
         * divergência volta.
         *
         * Custo aceito: carregar três colunas de cada convite. Mesmo argumento do
         * `IaExecucoesPorDia` — a alternativa é uma segunda definição da regra.
         */
        $porSituacao = Convite::query()
            ->get(['aceito_em', 'recusado_em', 'expira_em'])
            ->countBy(fn (Convite $convite): string => $convite->situacao());

        return [
            'chart'  => ['type' => 'donut', 'height' => 260],
            // Situação sem nenhum convite entra com ZERO, e não some: base vazia devolvendo
            // `series: []` faria o ApexCharts desenhar um canvas em branco, sem legenda e sem
            // explicação — que é o estado de toda instalação nova.
            'series' => array_map(
                fn (string $situacao): int => $porSituacao->get($situacao, 0),
                array_keys(self::SITUACOES),
            ),
            'labels' => array_keys(self::SITUACOES),
            'colors' => array_values(self::SITUACOES),
            'legend' => ['position' => 'bottom', 'fontFamily' => 'inherit'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Infra\Widgets;

use App\Filament\Concerns\ExigePermissaoDoWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

/**
 * Que fração dos jobs que TERMINARAM terminou bem.
 *
 * Progresso radial porque o resultado é UM número entre 0 e 100% — o caso canônico do
 * `radialBar`. Rosca com duas fatias responderia o mesmo, pior.
 *
 * Complementa o `FilasStats`, que mostra os três números soltos (concluídos, falhados, em
 * andamento) sem a proporção entre eles — e é a proporção que diz se a fila está saudável.
 */
class FilasTaxaDeSucesso extends ApexChartWidget
{
    use ExigePermissaoDoWidget;

    protected static ?string $chartId = 'filasTaxaDeSucesso';

    protected static ?string $heading = 'Taxa de sucesso das filas';

    protected static ?int $sort = 45;

    protected int|string|array $columnSpan = 1;

    // O widget mais "ao vivo" do conjunto: é aqui que se acompanha uma fila degradando.
    // Ainda assim explícito, nunca herdado — o default do pacote é 5 s (ADR-04 da wiki).
    protected ?string $pollingInterval = '30s';

    protected static function fonteDeDadosDisponivel(): bool
    {
        return (bool) rescue(
            fn (): bool => Schema::hasTable('queue_monitors'),
            false,
            report: false,
        );
    }

    protected function getSubheading(): null|string|Htmlable|View
    {
        [$concluidos, $falhados] = $this->terminados();

        if (($concluidos + $falhados) === 0) {
            return 'Nenhum job terminou ainda';
        }

        return $concluidos.' de '.($concluidos + $falhados).' jobs terminaram sem exceção';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        [$concluidos, $falhados] = $this->terminados();

        $processados = $concluidos + $falhados;

        /*
         * Base zero é o estado NORMAL de uma instalação nova, não uma exceção.
         *
         * Devolver 0% e não 100%: "nada falhou" num sistema que nunca rodou nada é otimismo
         * falso, e o pior resultado possível para um indicador de saúde. E sem a guarda, a
         * divisão por zero derrubaria o dashboard inteiro — widget que estoura leva a página
         * junto, não só o próprio card.
         */
        $taxa = $processados === 0 ? 0 : (int) round(($concluidos / $processados) * 100);

        return [
            'chart'       => ['type' => 'radialBar', 'height' => 260],
            'series'      => [$taxa],
            'labels'      => ['Concluídos sem falha'],
            'colors'      => [$taxa >= 90 ? 'var(--success-500)' : ($taxa >= 70 ? 'var(--warning-500)' : 'var(--danger-500)')],
            'plotOptions' => [
                'radialBar' => [
                    'dataLabels' => [
                        'name'  => ['fontFamily' => 'inherit'],
                        'value' => ['fontFamily' => 'inherit'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Concluídos e falhados — os que TERMINARAM.
     *
     * Job em andamento fica de fora do denominador de propósito: ele não é sucesso nem falha, e
     * contá-lo faria a taxa cair sozinha em horário de pico, sem nada ter piorado.
     *
     * `failed` comparado com booleano, nunca com `1`/`0`: a coluna é boolean e o PostgreSQL
     * rejeita `failed = 1`. Mesmo cuidado do `FilasStats`.
     *
     * @return array{0: int, 1: int}
     */
    private function terminados(): array
    {
        return [
            $this->consulta()->where('failed', false)->whereNotNull('finished_at')->count(),
            $this->consulta()->where('failed', true)->count(),
        ];
    }

    private function consulta(): Builder
    {
        return DB::table('queue_monitors');
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Infra\Widgets;

use Illuminate\Support\Facades\Schema;
use LaBoiteACode\FilamentDashboardWidgets\Data\BreakdownItem;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\SegmentBarWidget;
use Spatie\Health\Models\HealthCheckResultHistoryItem;
use Spatie\Health\ResultStores\ResultStore;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResult;

/**
 * A última rodada de health checks desenhada como uma barra só.
 *
 * SegmentBar e não Composition (rosca): cada verificação tem exatamente um
 * status, então as partes fecham o todo — e a leitura que importa é "quanto da
 * barra ainda é verde". Uma barra horizontal responde isso mais rápido que uma
 * rosca, que obriga a comparar ângulos.
 */
class SaudeAplicacaoPorStatus extends SegmentBarWidget
{
    protected static ?int $sort = 20;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return (bool) rescue(
            fn (): bool => Schema::hasTable((new HealthCheckResultHistoryItem)->getTable()),
            false,
            report: false,
        );
    }

    public function getHeading(): ?string
    {
        return 'Verificações por status';
    }

    public function getHeadingDescription(): ?string
    {
        return 'Composição da última rodada de health checks';
    }

    public function getEmptyStateHeading(): string
    {
        return 'Nenhuma verificação registrada';
    }

    public function getEmptyStateDescription(): ?string
    {
        return 'Rode `php artisan health:check` para popular o histórico.';
    }

    public function getEmptyStateIcon(): string
    {
        return 'heroicon-o-heart';
    }

    /**
     * @return array<int, BreakdownItem>
     */
    protected function getSegments(): array
    {
        $resultados = app(ResultStore::class)->latestResults();

        if ($resultados === null) {
            return [];
        }

        $contagem = $resultados->storedCheckResults->countBy(
            fn (StoredCheckResult $resultado): string => $resultado->status ?: 'desconhecido',
        );

        // Ordem fixa (do bom para o ruim) e não por tamanho: a barra tem que
        // ficar estável entre rodadas, senão o segmento vermelho muda de lugar
        // toda vez e ninguém consegue comparar de relance.
        $mapa = [
            'ok'      => ['Tudo certo', 'success'],
            'warning' => ['Aviso', 'warning'],
            'failed'  => ['Falha', 'danger'],
            'crashed' => ['Quebrou ao rodar', 'danger'],
            'skipped' => ['Pulado', 'gray'],
        ];

        $segmentos = [];

        foreach ($mapa as $status => [$rotulo, $cor]) {
            $total = (int) $contagem->get($status, 0);

            // Segmento zerado não entra: viraria um rótulo sem barra alguma.
            if ($total > 0) {
                $segmentos[] = BreakdownItem::make($rotulo, $total)->color($cor);
            }
        }

        return $segmentos;
    }
}

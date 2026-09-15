<?php

declare(strict_types=1);

namespace Tests\Browser\Fixtures;

use App\Filament\Concerns\WidgetDinamico;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use MDDev\DynamicDashboard\Contracts\DynamicWidget;

/**
 * Widget dinâmico mínimo para os CT-B da grade — um Stat que sempre renderiza.
 *
 * Vive em tests/ porque é fixture: o kit não entrega widget dinâmico de
 * fábrica (a descoberta é do projeto, via `WidgetDinamico`), e o teste de
 * browser precisa de uma célula real na grade para arrastar.
 */
final class WidgetDeTeste extends StatsOverviewWidget implements DynamicWidget
{
    use WidgetDinamico;

    protected static ?string $widgetLabel = 'Indicador de Teste';

    protected function getStats(): array
    {
        return [
            Stat::make('Indicador', '42'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\TableWidget;
use Filament\Widgets\Widget;
use MDDev\DynamicDashboard\Concerns\HasEmptySettings;
use MDDev\DynamicDashboard\Concerns\HasSizeDefaults;

/**
 * Torna um widget de qualquer painel compatível com o dashboard dinâmico.
 *
 * Uso: `implements DynamicWidget` + `use WidgetDinamico` na classe do widget.
 * A descoberta é automática por painel — o `DynamicDashboard` varre
 * `panel->getWidgets()` filtrando por `is_subclass_of(DynamicWidget::class)`,
 * então basta registrar o widget no painel de sempre.
 *
 * A classe pode declarar:
 *   protected static ?string $widgetLabel = '...';
 *   protected static ?int $defaultHeight = N; (opcional, padrão 2)
 *
 * O rótulo ganha o sufixo do tipo — "Indicadores (Stat)", "Projetos (Table)" —
 * para o seletor de widgets dizer o que cada opção é.
 */
trait WidgetDinamico
{
    use HasEmptySettings;
    use HasSizeDefaults;

    public static function getWidgetLabel(): string
    {
        $label  = static::getWidgetBaseLabel();
        $sufixo = ' ('.static::getWidgetTypeLabel().')';

        if (str_ends_with($label, $sufixo)) {
            return $label;
        }

        return $label.$sufixo;
    }

    public static function getDynamicDashboardDefaultHeight(): int
    {
        if (! property_exists(static::class, 'defaultHeight')) {
            return 2;
        }

        return static::$defaultHeight ?? 2;
    }

    private static function getWidgetBaseLabel(): string
    {
        if (! property_exists(static::class, 'widgetLabel')) {
            return class_basename(static::class);
        }

        return static::$widgetLabel ?? class_basename(static::class);
    }

    private static function getWidgetTypeLabel(): string
    {
        return match (true) {
            is_subclass_of(static::class, StatsOverviewWidget::class) => 'Stat',
            is_subclass_of(static::class, TableWidget::class)         => 'Table',
            is_subclass_of(static::class, Widget::class)              => 'Chart',
            default                                                   => 'Widget',
        };
    }
}

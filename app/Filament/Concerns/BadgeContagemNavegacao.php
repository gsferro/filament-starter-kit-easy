<?php

namespace App\Filament\Concerns;

use Gsferro\FilamentOdometerEasy\Navigation\OdometerNavigationBadge;

/**
 * Badge de contagem animado no item de menu do Resource.
 *
 * A contagem sai de `getEloquentQuery()`, NUNCA de `getModel()::count()`: a
 * query do resource já carrega os escopos que valem para aquele painel
 * (soft deletes, filtros de posse, escopos globais). Contar direto no model
 * mostraria ao usuário um número que a listagem não confirma.
 *
 * O badge é renderizado pelo gsferro/filament-odometer-easy — o mesmo contador
 * animado dos stats. O plugin está registrado nos três painéis com
 * `badgeOnCollapsedSidebar()`, então o número continua visível com a sidebar
 * recolhida.
 */
trait BadgeContagemNavegacao
{
    public static function getNavigationBadge(): ?string
    {
        $total = static::getEloquentQuery()->count();

        // Zero não vira badge: um "0" cinza em todo item só polui o menu.
        if ($total === 0) {
            return null;
        }

        return OdometerNavigationBadge::make($total);
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total de registros';
    }
}

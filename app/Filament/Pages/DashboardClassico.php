<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Support\DashboardDinamico;
use Filament\Pages\Dashboard as FilamentDashboard;

/**
 * O dashboard clássico, compartilhado pelos painéis — o fallback da feature.
 *
 * Uma classe só, registrada nos três painéis: ela não grava `dashboards.page`,
 * então não precisa da fronteira por FQCN que a dinâmica precisa — mesmo
 * padrão do `Filament\Pages\Dashboard` que o kit já registrava nos três.
 *
 * Ocupa `/inicio` e é o espelho simétrico da dinâmica: com a feature ligada E
 * página dinâmica registrada no painel, devolve para `/`; caso contrário
 * responde normalmente, com os widgets clássicos do painel.
 */
final class DashboardClassico extends FilamentDashboard
{
    protected static string $routePath = '/inicio';

    public function mount(): void
    {
        $dinamica = DashboardDinamico::paginaDinamicaDo();

        if (DashboardDinamico::habilitado() && $dinamica !== null) {
            $this->redirect($dinamica::getUrl(), navigate: true);
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        return ! DashboardDinamico::habilitado()
            || DashboardDinamico::paginaDinamicaDo() === null;
    }
}

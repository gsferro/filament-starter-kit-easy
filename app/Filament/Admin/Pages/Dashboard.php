<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Pages\DashboardClassico;
use App\Support\DashboardDinamico;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use MDDev\DynamicDashboard\Pages\DynamicDashboard;

/**
 * Dashboard dinâmico do /admin — ver a irmã `App\Filament\App\Pages\Dashboard`
 * para o contrato completo. A FQCN própria é a fronteira do `dashboards.page`.
 */
final class Dashboard extends DynamicDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?int $navigationSort = -2;

    protected static string $routePath = '/';

    public static function getRoutePath(Panel $panel): string
    {
        return self::$routePath;
    }

    public function mount(): void
    {
        if (! DashboardDinamico::habilitado()) {
            $this->redirect(DashboardClassico::getUrl(), navigate: true);

            return;
        }

        parent::mount();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return DashboardDinamico::habilitado();
    }

    public function getTitle(): string
    {
        return __('filament-panels::pages/dashboard.title');
    }

    public static function canEdit(): bool
    {
        return auth()->user()?->can('Manage:Dashboard') ?? false;
    }
}

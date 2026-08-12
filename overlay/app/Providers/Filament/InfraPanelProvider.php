<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Painel INFRA — observabilidade e manutenção (health check, cache, filas, pacotes).
 */
class InfraPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('infra')
            ->path('infra')
            ->login()
            ->brandLogo(asset('images/logo.svg'))
            ->brandLogoHeight('2rem')
            ->brandName(config('app.name').' • Infra')
            ->discoverResources(in: app_path('Filament/Infra/Resources'), for: 'App\Filament\Infra\Resources')
            ->discoverPages(in: app_path('Filament/Infra/Pages'), for: 'App\Filament\Infra\Pages')
            ->discoverWidgets(in: app_path('Filament/Infra/Widgets'), for: 'App\Filament\Infra\Widgets')
            ->navigationItems([
                NavigationItem::make('Laravel Pulse')
                    ->url('/pulse', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-chart-bar')
                    ->group('Observabilidade'),
                NavigationItem::make('Horizon (Filas)')
                    ->url('/horizon', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-queue-list')
                    ->group('Observabilidade'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

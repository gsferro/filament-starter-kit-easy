<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Painel APP — a operação de negócio. Vem vazio de propósito:
 * é aqui que cada novo projeto constrói suas features.
 */
class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Multi-tenancy opcional: ative com KIT_TENANCY=true no .env.
        if (config('kit.tenancy.enabled')) {
            $panel
                ->tenant(
                    config('kit.tenancy.model'),
                    slugAttribute: config('kit.tenancy.slug_attribute'),
                    ownershipRelationship: config('kit.tenancy.ownership_relationship'),
                )
                ->tenantRegistration(RegisterTeam::class)
                ->tenantProfile(EditTeamProfile::class);
        }

        return $panel
            ->id('app')
            ->path('app')
            ->default()
            ->login()
            ->passwordReset()
            ->profile()
            ->brandLogo(asset('images/logo.svg'))
            ->brandLogoHeight('2rem')
            ->brandName(config('app.name'))
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\Filament\App\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\Filament\App\Pages')
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\Filament\App\Widgets')
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

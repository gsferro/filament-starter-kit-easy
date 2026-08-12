<?php

namespace App\Filament\Infra\Pages;

use BackedEnum;
use Dotswan\FilamentLaravelPulse\Widgets\PulseCache;
use Dotswan\FilamentLaravelPulse\Widgets\PulseExceptions;
use Dotswan\FilamentLaravelPulse\Widgets\PulseQueues;
use Dotswan\FilamentLaravelPulse\Widgets\PulseServers;
use Dotswan\FilamentLaravelPulse\Widgets\PulseSlowJobs;
use Dotswan\FilamentLaravelPulse\Widgets\PulseSlowOutGoingRequests;
use Dotswan\FilamentLaravelPulse\Widgets\PulseSlowQueries;
use Dotswan\FilamentLaravelPulse\Widgets\PulseSlowRequests;
use Dotswan\FilamentLaravelPulse\Widgets\PulseUsage;
use Filament\Pages\Dashboard;
use UnitEnum;

/**
 * Laravel Pulse dentro do painel infra.
 *
 * Os widgets são listados à mão porque o dotswan/filament-laravel-pulse não
 * expõe classe de plugin. A rota nativa /pulse continua existindo e é protegida
 * pelo gate `viewPulse` (KitServiceProvider); esta página evita sair do painel.
 *
 * Os dados só aparecem com o daemon rodando: `php artisan pulse:check`
 * (ou o serviço `pulse` do docker compose --profile app/realtime).
 */
class Pulse extends Dashboard
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $title = 'Pulse';

    protected static ?string $navigationLabel = 'Pulse';

    protected static string|UnitEnum|null $navigationGroup = 'Observabilidade';

    protected static ?int $navigationSort = 240;

    protected static string $routePath = 'pulse';

    public function getWidgets(): array
    {
        return [
            PulseServers::class,
            PulseUsage::class,
            PulseQueues::class,
            PulseCache::class,
            PulseSlowRequests::class,
            PulseSlowQueries::class,
            PulseSlowJobs::class,
            PulseSlowOutGoingRequests::class,
            PulseExceptions::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 12;
    }
}

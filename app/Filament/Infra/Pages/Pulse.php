<?php

namespace App\Filament\Infra\Pages;

use App\Filament\Concerns\ExigePermissaoDaTela;
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
 * expõe classe de plugin.
 *
 * ## Duas barreiras diferentes, e elas não se substituem
 *
 * A rota nativa `/pulse`, do pacote, é protegida pelo gate `viewPulse` (KitServiceProvider).
 * **Esta Page é outra rota** — `/infra/pulse` — e até a 0.18.9 ela não consultava nada: o
 * `canAccess()` default do Filament é `return true`
 * (`vendor/filament/filament/src/Pages/Concerns/CanAuthorizeAccess.php:17-24`), então quem abria o
 * `/infra` via servidores, filas, cache, slow queries e exceções. A permissão `View:Pulse` existia
 * no banco, aparecia como checkbox em `/admin/shield/roles` e não decidia nada.
 *
 * Agora decide, por `ExigePermissaoDaTela`. O gate `viewPulse` continua onde estava, para a rota do
 * pacote.
 *
 * Os dados só aparecem com o daemon rodando: `php artisan pulse:check`
 * (ou o serviço `pulse` do docker compose --profile app/realtime).
 */
class Pulse extends Dashboard
{
    use ExigePermissaoDaTela;

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

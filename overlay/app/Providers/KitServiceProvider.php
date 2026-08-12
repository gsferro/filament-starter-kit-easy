<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

/**
 * Provider "cola" do start-kit: health checks e gates de observabilidade.
 */
class KitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Health checks padrão — adicione/remova conforme o projeto.
        Health::checks([
            DatabaseCheck::new(),
            CacheCheck::new(),
            DebugModeCheck::new(),
            EnvironmentCheck::new(),
            OptimizedAppCheck::new(),
            QueueCheck::new(),
            ScheduleCheck::new(),
            UsedDiskSpaceCheck::new(),
        ]);

        // Acesso ao Pulse e ao Horizon restrito a quem acessa o painel infra.
        Gate::define('viewPulse', fn (User $user) => $user->hasAnyRole(['super_admin', 'infra']));
        Gate::define('viewHorizon', fn (User $user) => $user->hasAnyRole(['super_admin', 'infra']));
    }
}

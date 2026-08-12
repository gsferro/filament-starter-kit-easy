<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\AppPanelProvider;
use App\Providers\Filament\InfraPanelProvider;
use App\Providers\KitServiceProvider;

return [
    AppServiceProvider::class,
    KitServiceProvider::class,
    AdminPanelProvider::class,
    AppPanelProvider::class,
    InfraPanelProvider::class,
];

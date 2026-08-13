<?php

namespace App\Providers;

use App\Filament\Pages\Auth\TelaBloqueio;
use Illuminate\Support\ServiceProvider;
use lockscreen\FilamentLockscreen\Http\Livewire\LockerScreen;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // A tela de bloqueio do pacote é uma SimplePage do Filament e ignora o layout
        // do login (auth-designer). A rota do pacote resolve a classe pelo container,
        // então o bind entrega a nossa subclasse — mesma mídia, mesmo tema.
        $this->app->bind(LockerScreen::class, TelaBloqueio::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

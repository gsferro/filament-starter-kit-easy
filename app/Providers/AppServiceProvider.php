<?php

namespace App\Providers;

use App\Filament\Pages\Auth\TelaBloqueio;
use App\Support\GerenciadorAntiRobo;
use Ddr\FilamentCaptcha\CaptchaManager;
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

        // O captcha do `ddr/filament-captcha` lendo a config DO KIT, com falha fechada e log. O
        // pacote registra o singleton dele em `packageRegistered()`; este provider é registrado
        // depois (app providers vêm após os descobertos), então o nosso vence. Há caso de teste.
        $this->app->singleton(CaptchaManager::class, fn (): CaptchaManager => new GerenciadorAntiRobo);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

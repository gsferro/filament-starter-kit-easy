<?php

namespace App\Filament\Infra\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Spatie\Health\ResultStores\ResultStore;

class HealthChecks extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static ?string $title = 'Health Check';

    protected static string|\UnitEnum|null $navigationGroup = 'Observabilidade';

    protected string $view = 'filament.infra.pages.health-checks';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run')
                ->label('Executar checks')
                ->icon('heroicon-o-arrow-path')
                ->action(function (): void {
                    Artisan::call('health:check');

                    Notification::make()
                        ->title('Checks executados')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getCheckResults(): array
    {
        $latest = app(ResultStore::class)->latestResults();

        return $latest?->storedCheckResults?->toArray() ?? [];
    }
}

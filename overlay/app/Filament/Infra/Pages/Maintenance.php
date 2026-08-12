<?php

namespace App\Filament\Infra\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class Maintenance extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $title = 'Manutenção';

    protected static string|\UnitEnum|null $navigationGroup = 'Manutenção';

    protected string $view = 'filament.infra.pages.maintenance';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('optimizeClear')
                ->label('Limpar tudo (optimize:clear)')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->action(fn () => $this->runArtisan('optimize:clear')),
        ];
    }

    /** Comandos expostos como botões na página. */
    public function commands(): array
    {
        return [
            'cache:clear'    => 'Limpar cache da aplicação',
            'config:clear'   => 'Limpar cache de configuração',
            'route:clear'    => 'Limpar cache de rotas',
            'view:clear'     => 'Limpar views compiladas',
            'event:clear'    => 'Limpar cache de eventos',
            'queue:restart'  => 'Reiniciar workers de fila',
            'storage:link'   => 'Criar link simbólico do storage',
            'filament:optimize-clear' => 'Limpar cache do Filament',
        ];
    }

    public function run(string $command): void
    {
        if (! array_key_exists($command, $this->commands())) {
            return;
        }

        $this->runArtisan($command);
    }

    protected function runArtisan(string $command): void
    {
        Artisan::call($command);

        Notification::make()
            ->title("Comando `{$command}` executado")
            ->body(trim(Artisan::output()) ?: null)
            ->success()
            ->send();
    }
}

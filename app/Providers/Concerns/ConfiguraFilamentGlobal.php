<?php

namespace App\Providers\Concerns;

use BezhanSalleh\PanelSwitch\PanelSwitch;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Toggle;
use Filament\Livewire\DatabaseNotifications;
use Filament\Support\View\Components\ModalComponent;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

/**
 * Configuração GLOBAL do Filament — vale para os 3 painéis (admin, infra, app).
 *
 * Mora num trait do KitServiceProvider (e não num plugin por painel) porque
 * `configureUsing()` é estático/global. O Panel Switch também é configurado
 * aqui: o pacote não é um plugin de painel.
 */
trait ConfiguraFilamentGlobal
{
    protected function configuraFilamentGlobal(): void
    {
        ModalComponent::closedByEscaping(false);

        // Fallback de polling do sininho; os painéis zeram o intervalo quando
        // o broadcast é Reverb (tempo real de verdade, sem polling).
        DatabaseNotifications::pollingInterval('30s');

        Toggle::configureUsing(function (Toggle $toggle): void {
            $toggle->onColor('success')->offColor('danger');
        });

        ToggleColumn::configureUsing(function (ToggleColumn $column): void {
            $column->onColor('success')->offColor('danger');
        });

        IconColumn::configureUsing(function (IconColumn $column): void {
            if ($column->isBoolean()) {
                $column->trueColor('success')->falseColor('danger');
            }
        });

        CreateAction::configureUsing(function (CreateAction $action): void {
            $action->icon('heroicon-s-plus-circle');
        });

        Table::configureUsing(function (Table $table): void {
            $table
                ->deferLoading()
                ->striped()
                ->persistFiltersInSession()
                ->persistSearchInSession()
                ->persistSortInSession()
                ->reorderableColumns()
                ->deferFilters()
                ->filtersFormColumns(2)
                ->defaultPaginationPageOption(10)
                ->extremePaginationLinks();
        });

        $this->configuraPanelSwitch();
    }

    protected function configuraPanelSwitch(): void
    {
        // Não implemente canSwitchPanels() no User: o componente só mapeia
        // URLs para null e continua renderizando a lista. O recorte real é o
        // canAccessPanel() — painéis inacessíveis somem sozinhos.
        PanelSwitch::configureUsing(function (PanelSwitch $panelSwitch): void {
            $panelSwitch
                ->simple()
                ->labels([
                    'admin' => 'Administração',
                    'infra' => 'Infraestrutura',
                    'app'   => config('app.name'),
                ])
                ->icons([
                    'admin' => 'heroicon-o-wrench-screwdriver',
                    'infra' => 'heroicon-o-server-stack',
                    'app'   => 'heroicon-o-rocket-launch',
                ]);
        });
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial do dashboard dinâmico (`App\Filament\{Admin,App,Infra}\Pages\Dashboard`).
 * Conclui ao terminar o tour.
 */
final class DashboardDinamicoSeeder extends SeederDeOnboarding
{
    public function run(): void
    {
        foreach (['admin', 'app', 'infra'] as $painel) {
            $this->passo($painel, 'dashboard-dinamico', [
                'type'        => 'tour',
                'title'       => self::texto('Monte seu painel de widgets', 'Build your widget dashboard'),
                'description' => self::texto(
                    'Organize os widgets para acompanhar o que interessa a você.',
                    'Arrange widgets to keep track of what matters to you.',
                ),
                'icon'                 => 'heroicon-o-chart-bar',
                'completion_mode'      => 'manual',
                'visibility_condition' => 'dashboard_dinamico_no_painel',
                'sort_order'           => 40,
                'tour_steps'           => [
                    self::parada(
                        "filament.{$painel}.pages.dashboard-dinamico",
                        ['Um painel do seu jeito', 'A dashboard your way'],
                        ['Aqui você monta seu próprio painel de widgets. Use as opções de personalização para escolher e organizar o que deseja acompanhar.', 'Here you build your own widget dashboard. Use the customization options to choose and arrange what you want to track.'],
                    ),
                ],
            ]);
        }
    }
}

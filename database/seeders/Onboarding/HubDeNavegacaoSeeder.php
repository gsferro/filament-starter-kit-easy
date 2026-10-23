<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial dos hubs `App\Filament\Admin\Pages\HubDeAdministracao` e
 * `App\Filament\App\Pages\HubDoNegocio`. Conclui ao terminar o tour.
 */
final class HubDeNavegacaoSeeder extends SeederDeOnboarding
{
    public function run(): void
    {
        foreach (['admin' => 'hub-de-administracao', 'app' => 'hub-do-negocio'] as $painel => $hub) {
            $this->passo($painel, 'hub-de-navegacao', [
                'type'        => 'tour',
                'title'       => self::texto('Explore os atalhos do painel', 'Explore panel shortcuts'),
                'description' => self::texto(
                    'Use os cartões do hub para chegar aos destinos do painel.',
                    'Use hub cards to reach destinations in this panel.',
                ),
                'icon'                 => 'heroicon-o-squares-2x2',
                'completion_mode'      => 'manual',
                'sort_order'           => 30,
                'tour_steps'           => [
                    self::parada(
                        "filament.{$painel}.pages.{$hub}",
                        ['Seus destinos em um lugar', 'Your destinations in one place'],
                        ['Aqui você encontra cartões de atalho para os destinos deste painel. Escolha um cartão para abrir a tela correspondente.', 'Here you find shortcut cards for destinations in this panel. Choose a card to open its screen.'],
                    ),
                ],
            ]);
        }
    }
}

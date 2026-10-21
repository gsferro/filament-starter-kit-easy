<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial do hub (`App\Filament\Infra\Pages\HubDeInfraestrutura`).
 * Conclui ao terminar o tour.
 */
final class HubDeInfraestruturaSeeder extends SeederDeOnboarding
{
    public function run(): void
    {
        $this->passo('infra', 'hub-de-infraestrutura', [
            'type'        => 'tour',
            'title'       => self::texto('Explore a central de infraestrutura', 'Explore the infrastructure hub'),
            'description' => self::texto(
                'Encontre as ferramentas de infraestrutura e use a busca da central.',
                'Find infrastructure tools and use the hub search.',
            ),
            'icon'            => 'heroicon-o-server-stack',
            'completion_mode' => 'manual',
            'sort_order'      => 110,
            'tour_steps'      => [
                self::parada(
                    'filament.infra.pages.hub-de-infraestrutura',
                    ['Ferramentas de infraestrutura', 'Infrastructure tools'],
                    ['Aqui você encontra os atalhos para as ferramentas de infraestrutura. Use a busca da central para localizar o destino de que precisa.', 'Here you find shortcuts to infrastructure tools. Use the hub search to locate the destination you need.'],
                ),
            ],
        ]);
    }
}

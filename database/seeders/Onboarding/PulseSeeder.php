<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial do Pulse (`App\Filament\Infra\Pages\Pulse`).
 * Conclui ao terminar o tour.
 */
final class PulseSeeder extends SeederDeOnboarding
{
    public function run(): void
    {
        $this->passo('infra', 'pulse', [
            'type'        => 'tour',
            'title'       => self::texto('Acompanhe a saúde da aplicação', 'Monitor application health'),
            'description' => self::texto(
                'Observe servidores, filas, cache e requisições lentas.',
                'Observe servers, queues, cache and slow requests.',
            ),
            'icon'            => 'heroicon-o-heart',
            'completion_mode' => 'manual',
            'sort_order'      => 120,
            'tour_steps'      => [
                self::parada(
                    'filament.infra.pages.pulse',
                    ['Métricas da aplicação', 'Application metrics'],
                    ['Aqui você acompanha servidores, filas e cache. Consulte também as requisições lentas para identificar pontos que precisam de atenção.', 'Here you monitor servers, queues and cache. Review slow requests as well to identify areas that need attention.'],
                ),
            ],
        ]);
    }
}

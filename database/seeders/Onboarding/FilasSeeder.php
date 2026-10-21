<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial de Filas (`Croustibat\FilamentJobsMonitor\Resources\QueueMonitorResource`).
 * Conclui ao terminar o tour.
 */
final class FilasSeeder extends SeederDeOnboarding
{
    public function run(): void
    {
        $this->passo('infra', 'filas', [
            'type'        => 'tour',
            'title'       => self::texto('Acompanhe os jobs de fila', 'Track queued jobs'),
            'description' => self::texto(
                'Consulte a situação dos jobs executados em segundo plano.',
                'Review the status of jobs running in the background.',
            ),
            'icon'            => 'heroicon-o-queue-list',
            'completion_mode' => 'manual',
            'sort_order'      => 160,
            'tour_steps'      => [
                self::parada(
                    'filament.infra.resources.queue-monitors.index',
                    ['Monitor de jobs', 'Job monitor'],
                    ['Aqui você acompanha os jobs de fila. Confira a situação e os detalhes de cada execução para investigar atrasos ou falhas.', 'Here you track queued jobs. Check the status and details of each run to investigate delays or failures.'],
                    'part:table',
                ),
            ],
        ]);
    }
}

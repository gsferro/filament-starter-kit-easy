<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial de Execuções de IA (`App\Filament\Infra\Resources\AiRuns\AiRunResource`).
 * Conclui ao terminar o tour.
 */
final class ExecucoesDeIaSeeder extends SeederDeOnboarding
{
    public function run(): void
    {
        $this->passo('infra', 'execucoes-de-ia', [
            'type'        => 'tour',
            'title'       => self::texto('Acompanhe as execuções de IA', 'Track AI runs'),
            'description' => self::texto(
                'Consulte as execuções de IA e investigue seus detalhes.',
                'Review AI runs and investigate their details.',
            ),
            'icon'            => 'heroicon-o-cpu-chip',
            'completion_mode' => 'manual',
            'sort_order'      => 180,
            'tour_steps'      => [
                self::parada(
                    'filament.infra.resources.execucoes-ia.index',
                    ['Execuções de IA', 'AI runs'],
                    ['Aqui você consulta as execuções de IA registradas. Abra uma execução para analisar sua situação e os detalhes disponíveis.', 'Here you review recorded AI runs. Open a run to examine its status and available details.'],
                    'part:table',
                ),
            ],
        ]);
    }
}

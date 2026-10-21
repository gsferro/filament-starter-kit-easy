<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial de Agentes de IA (`App\Filament\Admin\Resources\AgentesIa\AgenteIaResource`).
 * Conclui ao terminar o tour.
 */
final class AgentesDeIaSeeder extends SeederDeOnboarding
{
    public function run(): void
    {
        $this->passo('admin', 'agentes-de-ia', [
            'type'        => 'tour',
            'title'       => self::texto('Conheça o catálogo de agentes de IA', 'Explore the AI agent catalog'),
            'description' => self::texto(
                'Consulte os agentes de IA disponíveis na instalação.',
                'View the AI agents available in the installation.',
            ),
            'icon'                 => 'heroicon-o-sparkles',
            'completion_mode'      => 'manual',
            'sort_order'           => 150,
            'tour_steps'           => [
                self::parada(
                    'filament.admin.resources.agentes-ia.index',
                    ['Catálogo de agentes', 'Agent catalog'],
                    ['Aqui você consulta o catálogo de agentes de IA. Abra um agente para conhecer sua configuração e finalidade.', 'Here you browse the AI agent catalog. Open an agent to review its configuration and purpose.'],
                    'part:table',
                ),
            ],
        ]);
    }
}

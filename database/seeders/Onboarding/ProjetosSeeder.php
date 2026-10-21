<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial de Projetos (`App\Filament\App\Resources\Projetos\ProjetoResource`).
 * Conclui ao terminar o tour.
 */
final class ProjetosSeeder extends SeederDeOnboardingDeTenancy
{
    public function run(): void
    {
        $this->passo('app', 'projetos', [
            'type'        => 'tour',
            'title'       => self::texto('Explore os projetos de demonstração', 'Explore demo projects'),
            'description' => self::texto(
                'Conheça o cadastro de projetos e a importação e exportação em CSV.',
                'Explore project records and CSV import and export.',
            ),
            'icon'                 => 'heroicon-o-folder',
            'completion_mode'      => 'manual',
            'sort_order'           => 150,
            'tour_steps'           => [
                self::parada(
                    'filament.app.resources.projetos.index',
                    ['Cadastro de demonstração', 'Demo records'],
                    ['Aqui você explora o cadastro de demonstração de projetos da organização atual. Consulte os registros para conhecer o funcionamento da tabela.', 'Here you explore demo project records for the current organization. Browse the records to learn how the table works.'],
                    'part:table',
                ),
                self::parada(
                    'filament.app.resources.projetos.index',
                    ['Importe e exporte CSV', 'Import and export CSV'],
                    ['Use as ações de importar e exportar CSV, quando disponíveis para seu acesso, para trazer projetos ou baixar os dados.', 'Use CSV import and export actions, when available for your access, to bring in projects or download data.'],
                ),
            ],
        ]);
    }
}

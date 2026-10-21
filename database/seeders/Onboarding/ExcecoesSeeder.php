<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial de Exceções (`BezhanSalleh\FilamentExceptions\Resources\ExceptionResource`).
 * Conclui ao terminar o tour.
 */
final class ExcecoesSeeder extends SeederDeOnboarding
{
    public function run(): void
    {
        $this->passo('infra', 'excecoes', [
            'type'        => 'tour',
            'title'       => self::texto('Investigue exceções registradas', 'Investigate recorded exceptions'),
            'description' => self::texto(
                'Consulte os erros registrados e seus detalhes.',
                'Review recorded errors and their details.',
            ),
            'icon'            => 'heroicon-o-exclamation-triangle',
            'completion_mode' => 'manual',
            'sort_order'      => 150,
            'tour_steps'      => [
                self::parada(
                    'filament.infra.resources.exceptions.index',
                    ['Exceções da aplicação', 'Application exceptions'],
                    ['Aqui você consulta as exceções registradas. Abra um registro para analisar a mensagem e os detalhes do erro.', 'Here you review recorded exceptions. Open a record to examine the error message and details.'],
                    'part:table',
                ),
            ],
        ]);
    }
}

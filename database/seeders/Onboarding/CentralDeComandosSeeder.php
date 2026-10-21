<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial da central (`Bityukov\CommandCenter\Filament\Pages\Commands` e `History`).
 * Conclui ao terminar o tour.
 */
final class CentralDeComandosSeeder extends SeederDeOnboarding
{
    public function run(): void
    {
        $this->passo('infra', 'central-de-comandos', [
            'type'        => 'tour',
            'title'       => self::texto('Conheça a central de comandos', 'Explore the command center'),
            'description' => self::texto(
                'Execute comandos disponíveis e consulte o histórico.',
                'Run available commands and review the history.',
            ),
            'icon'                 => 'heroicon-o-command-line',
            'completion_mode'      => 'manual',
            'sort_order'           => 200,
            'tour_steps'           => [
                self::parada(
                    'filament.infra.pages.command-center.commands',
                    ['Execute um comando', 'Run a command'],
                    ['Aqui você encontra os comandos disponíveis. Confira a finalidade e os parâmetros do comando antes de executá-lo.', 'Here you find available commands. Check the command’s purpose and parameters before running it.'],
                ),
                self::parada(
                    'filament.infra.pages.command-center.history',
                    ['Confira o histórico', 'Review the history'],
                    ['Aqui você acompanha o histórico de execuções. Abra uma execução para consultar sua situação e o resultado disponível.', 'Here you track execution history. Open a run to review its status and available output.'],
                ),
            ],
        ]);
    }
}

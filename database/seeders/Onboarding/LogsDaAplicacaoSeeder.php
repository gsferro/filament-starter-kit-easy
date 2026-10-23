<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial de Logs (`LaBoiteACode\FilamentLogsExplorer\Pages\LogsExplorer`).
 * Conclui ao terminar o tour; a consulta ensinada é somente leitura.
 */
final class LogsDaAplicacaoSeeder extends SeederDeOnboarding
{
    public function run(): void
    {
        $this->passo('infra', 'logs-da-aplicacao', [
            'type'        => 'tour',
            'title'       => self::texto('Leia os logs da aplicação', 'Read application logs'),
            'description' => self::texto(
                'Consulte os registros para investigar o que aconteceu.',
                'Review log entries to investigate what happened.',
            ),
            'icon'            => 'heroicon-o-document-text',
            'completion_mode' => 'manual',
            'sort_order'      => 140,
            'tour_steps'      => [
                self::parada(
                    'filament.infra.pages.logs',
                    ['Consulte os logs', 'Review logs'],
                    ['Aqui você lê os logs da aplicação em modo somente leitura. Consulte as mensagens e os horários para investigar um problema.', 'Here you read application logs in read-only mode. Review messages and timestamps to investigate a problem.'],
                ),
            ],
        ]);
    }
}

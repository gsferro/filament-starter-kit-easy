<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial de Backups (`App\Filament\Infra\Pages\BackupRunsPage`).
 * Conclui ao terminar o tour.
 */
final class BackupsSeeder extends SeederDeOnboarding
{
    public function run(): void
    {
        $this->passo('infra', 'backups', [
            'type'        => 'tour',
            'title'       => self::texto('Conheça os backups', 'Learn about backups'),
            'description' => self::texto(
                'Consulte as cópias de segurança e inicie um backup quando necessário.',
                'Review backups and start a backup when needed.',
            ),
            'icon'            => 'heroicon-o-archive-box',
            'completion_mode' => 'manual',
            'sort_order'      => 130,
            'tour_steps'      => [
                self::parada(
                    'filament.infra.pages.backup-runs',
                    ['Cópias de segurança', 'Backups'],
                    ['Aqui você consulta os backups e acompanha sua situação. Use a ação disponível para disparar um novo backup quando necessário.', 'Here you review backups and track their status. Use the available action to start a new backup when needed.'],
                ),
            ],
        ]);
    }
}

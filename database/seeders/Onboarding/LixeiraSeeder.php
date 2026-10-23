<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial da Lixeira (`Promethys\Revive\Pages\RecycleBin`).
 * Conclui ao terminar o tour.
 */
final class LixeiraSeeder extends SeederDeOnboarding
{
    public function run(): void
    {
        $this->passo('infra', 'lixeira', [
            'type'        => 'tour',
            'title'       => self::texto('Conheça a lixeira', 'Explore the recycle bin'),
            'description' => self::texto(
                'Encontre registros excluídos e restaure os que precisar.',
                'Find deleted records and restore those you need.',
            ),
            'icon'            => 'heroicon-o-trash',
            'completion_mode' => 'manual',
            'sort_order'      => 190,
            'tour_steps'      => [
                self::parada(
                    'filament.infra.pages.recycle-bin',
                    ['Restaure registros excluídos', 'Restore deleted records'],
                    ['Aqui você encontra registros excluídos que podem ser restaurados. Revise o registro e use a ação de restaurar quando precisar recuperá-lo.', 'Here you find deleted records that can be restored. Review the record and use the restore action when you need to recover it.'],
                ),
            ],
        ]);
    }
}

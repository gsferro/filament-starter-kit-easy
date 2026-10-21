<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial de Auditoria (`Tapp\FilamentAuditing\Filament\Resources\Audits\AuditResource`).
 * Conclui ao terminar o tour.
 */
final class AuditoriaSeeder extends SeederDeOnboarding
{
    public function run(): void
    {
        $this->passo('infra', 'auditoria', [
            'type'        => 'tour',
            'title'       => self::texto('Consulte a trilha de auditoria', 'Review the audit trail'),
            'description' => self::texto(
                'Veja as alterações registradas na aplicação.',
                'View recorded changes in the application.',
            ),
            'icon'            => 'heroicon-o-clipboard-document-list',
            'completion_mode' => 'manual',
            'sort_order'      => 170,
            'tour_steps'      => [
                self::parada(
                    'filament.infra.resources.audits.index',
                    ['Histórico de alterações', 'Change history'],
                    ['Aqui você consulta a trilha de auditoria. Abra um registro para conferir quem alterou os dados, quando e o que mudou.', 'Here you review the audit trail. Open a record to check who changed the data, when and what changed.'],
                    'part:table',
                ),
            ],
        ]);
    }
}

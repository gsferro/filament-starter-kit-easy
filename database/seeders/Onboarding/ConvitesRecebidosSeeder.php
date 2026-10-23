<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial de Convites recebidos (`App\Filament\App\Pages\ConvitesRecebidos`).
 * Conclui ao terminar o tour.
 */
final class ConvitesRecebidosSeeder extends SeederDeOnboardingDeTenancy
{
    public function run(): void
    {
        $this->passo('app', 'convites-recebidos', [
            'type'        => 'tour',
            'title'       => self::texto('Confira seus convites recebidos', 'Review your received invitations'),
            'description' => self::texto(
                'Aceite ou recuse os convites enviados a você.',
                'Accept or decline invitations sent to you.',
            ),
            'icon'            => 'heroicon-o-inbox',
            'completion_mode' => 'manual',
            'sort_order'      => 130,
            'tour_steps'      => [
                self::parada(
                    'filament.app.pages.convites-recebidos',
                    ['Convites enviados a você', 'Invitations sent to you'],
                    ['Aqui você confere os convites recebidos. Revise a organização e o papel proposto antes de aceitar ou recusar.', 'Here you review received invitations. Check the organization and proposed role before accepting or declining.'],
                ),
            ],
        ]);
    }
}

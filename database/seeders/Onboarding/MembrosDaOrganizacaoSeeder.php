<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial de Membros (`App\Filament\App\Resources\Users\UserResource`).
 * Conclui ao terminar o tour.
 */
final class MembrosDaOrganizacaoSeeder extends SeederDeOnboardingDeTenancy
{
    public function run(): void
    {
        $this->passo('app', 'membros-da-organizacao', [
            'type'        => 'tour',
            'title'       => self::texto('Conheça os membros da organização', 'Meet organization members'),
            'description' => self::texto(
                'Consulte e cadastre pessoas na organização atual.',
                'View and add people in the current organization.',
            ),
            'icon'            => 'heroicon-o-users',
            'completion_mode' => 'manual',
            'sort_order'      => 110,
            'tour_steps'      => [
                self::parada(
                    'filament.app.resources.users.index',
                    ['Pessoas da organização atual', 'People in the current organization'],
                    ['Aqui você consulta as pessoas da organização selecionada. Confira a organização atual antes de gerenciar seus membros.', 'Here you view people in the selected organization. Check the current organization before managing its members.'],
                    'part:table',
                ),
                self::parada(
                    'filament.app.resources.users.create',
                    ['Cadastre um membro', 'Add a member'],
                    ['Preencha os dados da pessoa e escolha seu papel na organização atual. Salve para concluir o cadastro.', 'Fill in the person’s details and choose their role in the current organization. Save to complete registration.'],
                    'action:submit',
                ),
            ],
        ]);
    }
}

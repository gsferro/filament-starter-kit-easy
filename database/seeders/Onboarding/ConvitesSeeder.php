<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

use App\Models\Convite;

/**
 * Tutorial de Convites (`App\Filament\Admin\Resources\Convites\ConviteResource`).
 * Conclui automaticamente quando você tiver enviado ao menos um convite.
 */
final class ConvitesSeeder extends SeederDeOnboarding
{
    public function run(): void
    {
        $this->condicao('convidou_alguem', [
            'label'       => self::texto('Já convidou alguém', 'Has invited someone'),
            'description' => self::texto(
                'Você enviou ao menos um convite na instalação.',
                'You have sent at least one invitation in the installation.',
            ),
            'type'           => 'aggregate',
            'model'          => Convite::class,
            'subject_column' => 'convidado_por_id',
            'scope_column'   => null,
            'filters'        => [],
            'minimum'        => 1,
        ]);

        $this->passo('admin', 'convites', [
            'type'        => 'tour',
            'title'       => self::texto('Convide pessoas para a instalação', 'Invite people to the installation'),
            'description' => self::texto(
                'Envie um convite e acompanhe sua situação.',
                'Send an invitation and track its status.',
            ),
            'icon'            => 'heroicon-o-envelope',
            'completion_mode' => 'condition',
            'condition_key'   => 'convidou_alguem',
            'sort_order'      => 120,
            'tour_steps'      => [
                self::parada(
                    'filament.admin.resources.convites.index',
                    ['Acompanhe seus convites', 'Track your invitations'],
                    ['Aqui você consulta os convites da instalação e acompanha a situação de cada um.', 'Here you view installation invitations and track the status of each one.'],
                    'part:table',
                ),
                self::parada(
                    'filament.admin.resources.convites.create',
                    ['Convide alguém', 'Invite someone'],
                    ['Preencha os dados da pessoa e envie o convite. Este passo conclui sozinho após seu primeiro convite.', 'Fill in the person’s details and send the invitation. This step completes automatically after your first invitation.'],
                    'action:submit',
                ),
            ],
        ]);
    }
}

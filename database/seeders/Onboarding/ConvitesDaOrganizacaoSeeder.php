<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

use App\Models\Convite;

/**
 * Tutorial de Convites (`App\Filament\App\Resources\Convites\ConviteResource`).
 * Conclui quando você tiver enviado ao menos um convite na organização atual.
 */
final class ConvitesDaOrganizacaoSeeder extends SeederDeOnboardingDeTenancy
{
    public function run(): void
    {
        $this->condicao('convidou_alguem_na_organizacao', [
            'label'       => self::texto('Já convidou alguém na organização', 'Has invited someone to the organization'),
            'description' => self::texto(
                'Você enviou ao menos um convite na organização atual.',
                'You have sent at least one invitation in the current organization.',
            ),
            'type'           => 'aggregate',
            'model'          => Convite::class,
            'subject_column' => 'convidado_por_id',
            'scope_column'   => 'tenant_id',
            'filters'        => [],
            'minimum'        => 1,
        ]);

        $this->passo('app', 'convites-da-organizacao', [
            'type'        => 'tour',
            'title'       => self::texto('Convide pessoas para a organização', 'Invite people to the organization'),
            'description' => self::texto(
                'Envie convites para a organização atual e acompanhe sua situação.',
                'Send invitations to the current organization and track their status.',
            ),
            'icon'            => 'heroicon-o-envelope',
            'completion_mode' => 'condition',
            'condition_key'   => 'convidou_alguem_na_organizacao',
            'sort_order'      => 120,
            'tour_steps'      => [
                self::parada(
                    'filament.app.resources.convites.index',
                    ['Convites da organização', 'Organization invitations'],
                    ['Aqui você acompanha os convites da organização atual e a situação de cada um.', 'Here you track invitations for the current organization and the status of each one.'],
                    'part:table',
                ),
                self::parada(
                    'filament.app.resources.convites.create',
                    ['Convide para esta organização', 'Invite to this organization'],
                    ['Informe os dados da pessoa e envie o convite. O passo conclui sozinho após seu primeiro convite nesta organização.', 'Enter the person’s details and send the invitation. This step completes automatically after your first invitation in this organization.'],
                    'action:submit',
                ),
            ],
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial de Papéis (`App\Filament\Admin\Resources\Roles\RoleResource`).
 * Conclui ao terminar o tour.
 */
final class PapeisEPermissoesSeeder extends SeederDeOnboarding
{
    public function run(): void
    {
        $this->passo('admin', 'papeis-e-permissoes', [
            'type'        => 'tour',
            'title'       => self::texto('Conheça papéis e permissões', 'Learn about roles and permissions'),
            'description' => self::texto(
                'Defina o que cada papel pode acessar e fazer.',
                'Define what each role can access and do.',
            ),
            'icon'            => 'heroicon-o-shield-check',
            'completion_mode' => 'manual',
            'sort_order'      => 130,
            'tour_steps'      => [
                self::parada(
                    'filament.admin.resources.shield.roles.index',
                    ['Papéis de acesso', 'Access roles'],
                    ['Aqui você consulta os papéis da instalação. Cada papel reúne permissões que definem o que a pessoa pode acessar e fazer.', 'Here you view installation roles. Each role groups permissions that define what a person can access and do.'],
                    'part:table',
                ),
                self::parada(
                    'filament.admin.resources.shield.roles.create',
                    ['Defina as permissões', 'Define permissions'],
                    ['Dê um nome ao papel, selecione as permissões necessárias e salve. Conceda apenas os acessos de que esse papel precisa.', 'Name the role, select the required permissions and save. Grant only the access this role needs.'],
                    'action:submit',
                ),
            ],
        ]);
    }
}

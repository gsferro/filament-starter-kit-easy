<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial de Usuários, no `/admin` (`App\Filament\Admin\Resources\Users\UserResource`): onde se
 * cadastra, procura e edita quem usa a instalação. Conclui ao terminar o tour.
 */
final class UsuariosSeeder extends SeederDeOnboarding
{
    public function run(): void
    {
        $this->passo('admin', 'usuarios', [
            'type'            => 'tour',
            'title'           => self::texto('Conheça a tela de usuários', 'Meet the users screen'),
            'description'     => self::texto(
                'Onde você cadastra, procura e edita as pessoas que usam a instalação.',
                'Where you add, search and edit the people who use this installation.',
            ),
            'icon'            => 'heroicon-o-users',
            'completion_mode' => 'manual',
            'sort_order'      => 110,
            'tour_steps'      => [
                self::parada(
                    'filament.admin.resources.users.index',
                    ['Todos os usuários', 'Every user'],
                    ['Esta tabela lista quem tem acesso à instalação, com o papel e a situação da conta.', 'This table lists who has access to the installation, with their role and account status.'],
                    'part:table',
                ),
                self::parada(
                    'filament.admin.resources.users.index',
                    ['Procure por nome ou e-mail', 'Search by name or e-mail'],
                    ['A busca filtra a tabela enquanto você digita.', 'Search filters the table as you type.'],
                    'part:search',
                ),
                self::parada(
                    'filament.admin.resources.users.create',
                    ['Cadastre alguém', 'Add someone'],
                    ['Preencha os dados e salve: a pessoa recebe acesso com o papel que você escolher.', 'Fill in the details and save: the person gets access with the role you pick.'],
                    'action:submit',
                ),
            ],
        ]);
    }
}

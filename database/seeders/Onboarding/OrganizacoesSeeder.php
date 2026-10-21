<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial de Organizações (`App\Filament\Admin\Resources\Tenants\TenantResource`).
 * Conclui ao terminar o tour.
 */
final class OrganizacoesSeeder extends SeederDeOnboardingDeTenancy
{
    public function run(): void
    {
        $this->passo('admin', 'organizacoes', [
            'type'        => 'tour',
            'title'       => self::texto('Cadastre organizações', 'Register organizations'),
            'description' => self::texto(
                'Consulte e cadastre as organizações da instalação.',
                'View and register organizations in the installation.',
            ),
            'icon'            => 'heroicon-o-building-office-2',
            'completion_mode' => 'manual',
            'sort_order'      => 160,
            'tour_steps'      => [
                self::parada(
                    'filament.admin.resources.organizacoes.index',
                    ['Organizações da instalação', 'Installation organizations'],
                    ['Aqui você consulta as organizações cadastradas e abre seus dados para revisão.', 'Here you view registered organizations and open their details for review.'],
                    'part:table',
                ),
                self::parada(
                    'filament.admin.resources.organizacoes.create',
                    ['Nova organização', 'New organization'],
                    ['Preencha os dados da organização e salve para cadastrá-la na instalação.', 'Fill in the organization details and save to register it in the installation.'],
                    'action:submit',
                ),
            ],
        ]);
    }
}

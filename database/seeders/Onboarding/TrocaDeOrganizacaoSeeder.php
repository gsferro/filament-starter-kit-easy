<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial da troca de organização no dashboard do painel app.
 * Conclui ao terminar o tour.
 */
final class TrocaDeOrganizacaoSeeder extends SeederDeOnboardingDeTenancy
{
    public function run(): void
    {
        $this->passo('app', 'troca-de-organizacao', [
            'type'        => 'tour',
            'title'       => self::texto('Troque de organização', 'Switch organizations'),
            'description' => self::texto(
                'Use o seletor para trabalhar em outra organização.',
                'Use the selector to work in another organization.',
            ),
            'icon'                 => 'heroicon-o-arrows-right-left',
            'completion_mode'      => 'manual',
            'visibility_condition' => 'multi_tenant_ligado',
            'sort_order'           => 140,
            'tour_steps'           => [
                self::parada(
                    'filament.app.pages.dashboard',
                    ['Escolha a organização atual', 'Choose the current organization'],
                    ['Use o seletor no topo da barra lateral para trocar de organização. Confira o nome selecionado antes de consultar ou alterar dados.', 'Use the selector at the top of the sidebar to switch organizations. Check the selected name before viewing or changing data.'],
                ),
            ],
        ]);
    }
}

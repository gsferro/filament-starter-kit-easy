<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial do dock de atalhos no dashboard dos painéis admin, app e infra.
 * Conclui ao terminar o tour.
 */
final class DockDeAtalhosSeeder extends SeederDeOnboarding
{
    public function run(): void
    {
        foreach (['admin', 'app', 'infra'] as $painel) {
            $this->passo($painel, 'dock-de-atalhos', [
                'type'        => 'tour',
                'title'       => self::texto('Guarde seus atalhos no dock', 'Keep your shortcuts in the dock'),
                'description' => self::texto(
                    'Fixe páginas e gerencie seus atalhos para acessá-las rapidamente.',
                    'Pin pages and manage your shortcuts for quick access.',
                ),
                'icon'                 => 'heroicon-o-bookmark',
                'completion_mode'      => 'manual',
                'visibility_condition' => 'dock_no_painel',
                'sort_order'           => 50,
                'tour_steps'           => [
                    self::parada(
                        "filament.{$painel}.pages.dashboard",
                        ['Fixe a página atual', 'Pin the current page'],
                        ['Use o dock para fixar a página atual como atalho. Assim você volta às telas mais usadas com poucos cliques.', 'Use the dock to pin the current page as a shortcut. This helps you return to frequently used screens in a few clicks.'],
                    ),
                    self::parada(
                        "filament.{$painel}.pages.dashboard",
                        ['Organize seus atalhos', 'Organize your shortcuts'],
                        ['Abra o gerenciamento de atalhos do dock para organizar os destinos salvos e remover os que você não usa mais.', 'Open shortcut management in the dock to organize saved destinations and remove those you no longer use.'],
                    ),
                ],
            ]);
        }
    }
}

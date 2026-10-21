<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial da busca global no dashboard dos painéis admin, app e infra.
 * Conclui ao terminar o tour.
 */
final class BuscaGlobalSeeder extends SeederDeOnboarding
{
    public function run(): void
    {
        foreach (['admin', 'app', 'infra'] as $painel) {
            $this->passo($painel, 'busca-global', [
                'type'        => 'tour',
                'title'       => self::texto('Encontre com a busca global', 'Find things with global search'),
                'description' => self::texto(
                    'Abra a busca de telas, registros e ações de qualquer lugar.',
                    'Search screens, records and actions from anywhere.',
                ),
                'icon'            => 'heroicon-o-magnifying-glass',
                'completion_mode' => 'manual',
                'sort_order'      => 20,
                'tour_steps'      => [
                    self::parada(
                        "filament.{$painel}.pages.dashboard",
                        ['Busque de qualquer lugar', 'Search from anywhere'],
                        ['Pressione Ctrl+K ou ⌘K para abrir a busca global. Aqui você encontra telas, registros e ações sem navegar pelos menus.', 'Press Ctrl+K or ⌘K to open global search. Here you find screens, records and actions without browsing menus.'],
                    ),
                ],
            ]);
        }
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial do bloqueio de tela no dashboard dos painéis admin, app e infra.
 * Conclui ao terminar o tour.
 */
final class BloqueioDeTelaSeeder extends SeederDeOnboarding
{
    public function run(): void
    {
        foreach (['admin', 'app', 'infra'] as $painel) {
            $this->passo($painel, 'bloqueio-de-tela', [
                'type'        => 'tour',
                'title'       => self::texto('Conheça o bloqueio de tela', 'Learn about screen locking'),
                'description' => self::texto(
                    'Proteja sua sessão ao se afastar do computador.',
                    'Protect your session when you step away from your computer.',
                ),
                'icon'                 => 'heroicon-o-lock-closed',
                'completion_mode'      => 'manual',
                'visibility_condition' => 'bloqueio_de_tela_ligado',
                'sort_order'           => 60,
                'tour_steps'           => [
                    self::parada(
                        "filament.{$painel}.pages.dashboard",
                        ['Bloqueie ao se afastar', 'Lock when stepping away'],
                        ['Abra o menu do usuário e escolha bloquear a tela ao se afastar. A tela também é bloqueada automaticamente após o período de inatividade configurado.', 'Open the user menu and choose to lock the screen when stepping away. The screen also locks automatically after the configured inactivity period.'],
                    ),
                ],
            ]);
        }
    }
}

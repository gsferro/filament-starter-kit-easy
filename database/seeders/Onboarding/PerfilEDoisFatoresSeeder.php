<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial de Meu perfil (`App\Filament\Pages\MyProfilePage`) nos três painéis.
 * Conclui automaticamente quando a autenticação em dois fatores está confirmada.
 */
final class PerfilEDoisFatoresSeeder extends SeederDeOnboarding
{
    public function run(): void
    {
        foreach (['admin', 'app', 'infra'] as $painel) {
            $this->passo($painel, 'perfil-e-dois-fatores', [
                'type'        => 'tour',
                'title'       => self::texto('Cuide do seu perfil e da segurança', 'Manage your profile and security'),
                'description' => self::texto(
                    'Atualize seus dados e confirme a autenticação em dois fatores.',
                    'Update your details and confirm two-factor authentication.',
                ),
                'icon'            => 'heroicon-o-user-circle',
                'completion_mode' => 'condition',
                'condition_key'   => 'tem_dois_fatores',
                'sort_order'      => 10,
                'tour_steps'      => [
                    self::parada(
                        "filament.{$painel}.pages.meu-perfil",
                        ['Seu perfil', 'Your profile'],
                        ['Aqui você atualiza sua foto, seu nome e sua senha.', 'Here you update your photo, name and password.'],
                    ),
                    self::parada(
                        "filament.{$painel}.pages.meu-perfil",
                        ['Ative a autenticação em dois fatores', 'Enable two-factor authentication'],
                        ['Ative e confirme a autenticação em dois fatores. Este passo conclui sozinho quando a confirmação estiver pronta.', 'Enable and confirm two-factor authentication. This step completes automatically once confirmation is done.'],
                    ),
                ],
            ]);
        }
    }
}

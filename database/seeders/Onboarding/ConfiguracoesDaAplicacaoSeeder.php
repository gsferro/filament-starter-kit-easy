<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Tutorial de Configurações (`App\Filament\Admin\Pages\ConfiguracoesDoKit`).
 * Conclui ao visitar a tela de configurações da aplicação.
 */
final class ConfiguracoesDaAplicacaoSeeder extends SeederDeOnboarding
{
    public function run(): void
    {
        $this->passo('admin', 'configuracoes-da-aplicacao', [
            'type'        => 'tour',
            'title'       => self::texto('Conheça as configurações da aplicação', 'Explore application settings'),
            'description' => self::texto(
                'Explore as abas e encontre as opções da instalação.',
                'Explore the tabs and find installation options.',
            ),
            'icon'            => 'heroicon-o-cog-6-tooth',
            'completion_mode' => 'visit',
            // Sem `*` na frente: o pacote prefixa `/` no padrão (`OnboardingStep::matchesVisit()`), e
            // `/*/admin/...` nunca casa com `/admin/...` — o passo não concluiria nunca (CT-26).
            'visit_url'       => 'admin/configuracoes-da-aplicacao*',
            'sort_order'      => 140,
            'tour_steps'      => [
                self::parada(
                    'filament.admin.pages.configuracoes-da-aplicacao',
                    ['Configurações por assunto', 'Settings by topic'],
                    ['Aqui você encontra as configurações organizadas em abas. Escolha a aba do assunto que deseja ajustar e confira as opções disponíveis.', 'Here you find settings organized into tabs. Choose the tab for the topic you want to adjust and review its options.'],
                ),
                self::parada(
                    'filament.admin.pages.configuracoes-da-aplicacao',
                    ['Tutorial guiado', 'Guided tutorial'],
                    ['Na aba Aplicação, a seção "Tutorial guiado" permite ligar ou desligar este tutorial para a instalação.', 'In the Application tab, the "Guided tutorial" section lets you enable or disable this tutorial for the installation.'],
                ),
            ],
        ]);
    }
}

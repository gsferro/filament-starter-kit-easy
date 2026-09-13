<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Filament\App\Pages\Dashboard as DashboardDoApp;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use MDDev\DynamicDashboard\DashboardModelHelper;
use MDDev\DynamicDashboard\Models\Dashboard;
use MDDev\DynamicDashboard\Models\DashboardWidget;

/**
 * Cria o dashboard dinâmico padrão — por organização, ou global sem tenancy.
 *
 * O kit não embarca widget de negócio (o /app nasce vazio por desenho), então
 * `widgets()` devolve `[]`: o dashboard nasce como grade vazia pronta para o
 * gestor montar. No seu projeto, sobrescreva `widgets()` com o layout inicial
 * — a referência (`universidade-corporativa`) semeia indicadores, gráficos e
 * tabela num template de três seções.
 *
 * O `page` gravado é a FQCN da página dinâmica do /app — é ela que o escopo
 * `available()` do pacote usa como fronteira entre painéis.
 */
final class CriadorDeDashboardPadrao
{
    public static function para(?Tenant $tenant): Dashboard
    {
        return DB::transaction(static function () use ($tenant): Dashboard {
            $model = DashboardModelHelper::model();

            /** @var Dashboard $dashboard */
            $dashboard = new $model;
            $dashboard->fill([
                'name'         => 'Padrão',
                'description'  => 'Dashboard padrão',
                'page'         => DashboardDoApp::class,
                'is_active'    => true,
                'is_locked'    => false,
                'is_personal'  => false,
                'ordering'     => 1,
                'template_key' => 'flat-12',
            ]);
            // Atribuição direta, não fill: `tenant_id` fica fora do $fillable
            // do model do vendor de propósito — ninguém grava tenant por payload.
            $dashboard->tenant_id = $tenant?->getKey();
            $dashboard->save();

            foreach (self::widgets() as $widget) {
                DashboardWidget::create([
                    'dashboard_id'  => $dashboard->getKey(),
                    'type'          => $widget['type'],
                    'name'          => $widget['name'],
                    'section_slug'  => $widget['section_slug'],
                    'x'             => $widget['x'],
                    'y'             => $widget['y'],
                    'w'             => $widget['w'],
                    'h'             => $widget['h'],
                    'display_title' => true,
                    'settings'      => [],
                ]);
            }

            return $dashboard;
        });
    }

    /**
     * O layout inicial do dashboard padrão. Vazio no kit — sobrescreva no
     * projeto com os widgets do seu domínio.
     *
     * @return array<int, array{type: class-string, name: string, section_slug: string, x: int, y: int, w: int, h: int}>
     */
    protected static function widgets(): array
    {
        return [];
    }
}

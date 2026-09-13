<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Filament\Pages\DashboardClassico;
use App\Support\DashboardDinamico;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use MDDev\DynamicDashboard\Pages\DynamicDashboard;

/**
 * Dashboard dinâmico do /app — a raiz do painel quando a feature está ligada.
 *
 * Uma subclasse POR PAINEL, nunca compartilhada: o escopo `available()` do
 * pacote filtra por `dashboards.page = static::class`, então a FQCN desta
 * página É a fronteira entre os dashboards de painéis diferentes.
 *
 * Ocupa `/` pelo mesmo mecanismo do `Filament\Pages\Dashboard`
 * (`$routePath` + `getRoutePath()`); com tenancy a rota vira `->fallback()`
 * de graça. Desligada, `mount()` devolve para `/inicio` — a URL canônica do
 * painel nunca quebra.
 */
final class Dashboard extends DynamicDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?int $navigationSort = -2;

    protected static string $routePath = '/';

    public static function getRoutePath(Panel $panel): string
    {
        return self::$routePath;
    }

    public function mount(): void
    {
        if (! DashboardDinamico::habilitado()) {
            $this->redirect(DashboardClassico::getUrl(), navigate: true);

            return;
        }

        parent::mount();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return DashboardDinamico::habilitado();
    }

    public function getTitle(): string
    {
        return __('filament-panels::pages/dashboard.title');
    }

    /**
     * Quem pode MONTAR o dashboard — o gancho que o pacote consulta em toda
     * superfície de escrita (botão "Add Widget", ação "Manage", `createWidget()`,
     * `persistLayout()` e o drag do GridStack). Sem override ele é `true` para
     * qualquer um. "Ver" é livre; "gerenciar" é `Manage:Dashboard`.
     */
    public static function canEdit(): bool
    {
        return auth()->user()?->can('Manage:Dashboard') ?? false;
    }
}

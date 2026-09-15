<?php

declare(strict_types=1);

namespace App\Support;

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use MDDev\DynamicDashboard\DashboardModelHelper;
use MDDev\DynamicDashboard\Models\Dashboard;
use MDDev\DynamicDashboard\Pages\DynamicDashboard;

/**
 * O decisor do dashboard dinâmico — a ÚNICA leitura de `kit.dashboard_dinamico.*`.
 *
 * ## Por que existe
 *
 * Os painéis são montados no `register()` dos PanelProviders e
 * `ConfiguracoesDoKit::aplicarNaConfig()` roda no `boot()` do
 * `KitServiceProvider` — o valor do banco chega DEPOIS do painel montado
 * (`.ai/rules/settings.md`). Por isso NENHUM `->pages([...])` é condicional:
 * as duas páginas (dinâmica e clássica) estão sempre registradas e toda
 * decisão liga/desliga passa por aqui, por request — `mount()`,
 * `shouldRegisterNavigation()` e o observer de tenant.
 *
 * ## A conjunção
 *
 * `habilitado` **E** (`paineis` vazio **OU** o painel na lista). Lista vazia
 * significa TODOS os painéis registrados — a tradução é desta classe, a
 * config guarda a lista crua.
 */
final class DashboardDinamico
{
    /**
     * A feature está ligada para o painel CORRENTE (ou só pela flag, fora de
     * contexto Filament: console, queue)?
     */
    public static function habilitado(?Panel $painel = null): bool
    {
        $painel ??= Filament::getCurrentPanel();

        if ($painel === null) {
            return self::flagLigada();
        }

        return self::habilitadoPara($painel->getId());
    }

    /**
     * A feature está ligada para ESTE painel? É o que o `TenantObserver` e o
     * `DashboardPadraoSeeder` consultam: eles rodam fora de contexto de painel
     * e o gate correto é por painel — flag ligada com o `/app` fora da lista
     * não pode semear dashboard órfão (P5 do requisito, CT-27).
     */
    public static function habilitadoPara(string $panelId): bool
    {
        if (! self::flagLigada()) {
            return false;
        }

        $paineis = self::paineis();

        return $paineis === [] || in_array($panelId, $paineis, true);
    }

    /**
     * A página dinâmica registrada no painel — ou `null` quando o painel não a
     * tem (painel novo do projeto, sem subclasse própria).
     *
     * É o que `DashboardClassico` consulta para decidir para onde devolver: a
     * flag ligada sozinha não basta, o painel precisa ter a página — senão o
     * clássico permanece a tela de entrada (RQ-07, CT-20).
     *
     * @return class-string<DynamicDashboard>|null
     */
    public static function paginaDinamicaDo(?Panel $painel = null): ?string
    {
        $painel ??= Filament::getCurrentPanel();

        if ($painel === null) {
            return null;
        }

        foreach ($painel->getPages() as $pagina) {
            if (is_subclass_of($pagina, DynamicDashboard::class)) {
                return $pagina;
            }
        }

        return null;
    }

    /**
     * A página dinâmica ATENDE este request? Flag ligada para o painel corrente
     * E dashboard exibível para quem entrou.
     *
     * As duas metades são necessárias: sem a segunda, o vendor aborta 403
     * quando existem dashboards e nenhum passa pelo `canDisplay()`
     * (`vendor/mddev31/filament-dynamic-dashboard/src/Pages/DynamicDashboard.php:123-125`)
     * — e como o clássico devolve para a dinâmica, a raiz do painel ficava sem
     * saída: bastava o gestor restringir o único dashboard a um papel para
     * todo `panel_user` perder a tela de entrada (CT-28, CT-30).
     */
    public static function atende(string $pagina): bool
    {
        return self::habilitado() && self::temDashboardExibivel($pagina);
    }

    /**
     * Existe dashboard que o usuário corrente PODE ver nesta página?
     *
     * Nenhum dashboard também é "sim": a grade vazia é resposta legítima
     * (CT-26). O 403 do vendor é só o caso "existem e nenhum é exibível".
     *
     * A consulta reusa o scope `available()` e o `canDisplay()` do próprio
     * pacote (`src/Models/Dashboard.php:206-224`,
     * `src/Pages/DynamicDashboard.php:157-183`) — replicar a regra aqui seria
     * divergir dela na primeira atualização.
     *
     * @param  class-string<DynamicDashboard>  $pagina
     */
    public static function temDashboardExibivel(string $pagina): bool
    {
        /** @var Collection<int, Dashboard> $disponiveis */
        $disponiveis = DashboardModelHelper::model()::query()
            ->available($pagina)
            ->when(
                (bool) config('filament-dynamic-dashboard.use_spatie_permissions'),
                fn ($query) => $query->with('roles'),
            )
            ->get();

        return $disponiveis->isEmpty()
            || $disponiveis->contains(fn (Dashboard $dashboard): bool => $pagina::canDisplay($dashboard));
    }

    private static function flagLigada(): bool
    {
        return (bool) config('kit.dashboard_dinamico.habilitado', false);
    }

    /** @return list<string> */
    private static function paineis(): array
    {
        return array_values(array_filter(
            (array) config('kit.dashboard_dinamico.paineis', []),
            'is_string',
        ));
    }
}

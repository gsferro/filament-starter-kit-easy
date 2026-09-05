<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Tenants\Widgets;

use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Models\Tenant;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Gsferro\FilamentStatPlusEasy\Widgets\StatPlus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Visão geral das organizações, no topo da listagem: quantas existem, quanto do cadastro está
 * vinculado a alguma, e quanto desse vínculo virou USO.
 *
 * A taxa de ativação é o número que nenhuma outra tela do kit dava: "12 organizações e 340
 * usuários" não diz se alguém entra. Ela responde quanto do cadastro está vivo.
 *
 * ## Este widget não tem permission própria
 *
 * Ele vive fora de `app/Filament/Admin/Widgets/`, que é o diretório que o `discoverWidgets()` do
 * `AdminPanelProvider` varre — de propósito: `HasComponents::discoverComponents()` usa
 * `allFiles()`, que é RECURSIVO, e o `Dashboard` padrão renderiza todo widget registrado no
 * painel. Estar aqui é o que impede este widget de aparecer no dashboard.
 *
 * O preço é que o Shield não o descobre (a descoberta dele é `Filament::getWidgets()`), então não
 * existe `View:OrganizacoesStats`. A barreira é `TenantResource::canAccess()` — a mesma da tela
 * que o hospeda, que já exige `ViewAny:Tenant` **e** `config('kit.tenancy.enabled')`. Ver ADR-03
 * de `wikis/specs/main/insights-das-organizacoes/`.
 */
class OrganizacoesStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Visão geral';

    public static function canView(): bool
    {
        return TenantResource::canAccess();
    }

    /**
     * @return array<int, StatPlus>
     */
    protected function getStats(): array
    {
        $ativas     = Tenant::query()->where('ativo', true)->count();
        $total      = Tenant::query()->count();
        $vinculados = DB::table('tenant_user')->distinct()->count('user_id');
        $ativos     = $this->usuariosVinculadosComAcesso();

        return [
            StatPlus::make('Organizações ativas', $ativas)
                ->icon('heroicon-o-building-office-2')
                ->iconColor('primary')
                ->accentColor('primary')
                ->description($total === $ativas
                    ? 'Todas as cadastradas estão ativas'
                    : $total.' cadastradas, incluindo as inativas'),

            StatPlus::make('Usuários vinculados', $vinculados)
                ->icon('heroicon-o-users')
                ->iconColor('info')
                ->accentColor('info')
                ->description('Pessoas distintas com vínculo a alguma organização'),

            StatPlus::make('Ativos em '.TenantResource::DIAS_DE_INSIGHT.' dias', $ativos)
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->iconColor('success')
                ->accentColor('success')
                ->description('Vinculados que entraram desde '.now()->subDays(TenantResource::DIAS_DE_INSIGHT)->translatedFormat('d/m/Y')),

            StatPlus::make('Taxa de ativação', $this->taxaDeAtivacao($ativos, $vinculados))
                ->icon('heroicon-o-chart-bar')
                ->iconColor('gray')
                ->accentColor('gray')
                ->description($vinculados === 0
                    ? 'Nenhum usuário vinculado ainda'
                    : 'Percentual dos vinculados que usaram o sistema'),
        ];
    }

    /**
     * Pessoas distintas que TÊM vínculo com alguma organização e entraram na janela.
     *
     * O `join` com a pivot não é enfeite: sem ele o número contaria também quem só acessa o
     * `/admin` ou o `/infra`, que não pertence a organização nenhuma — e a taxa de ativação
     * passaria dos 100%.
     *
     * Sem a tabela de log, devolve 0 em vez de estourar: o pacote é opcional numa instalação
     * derivada, e um widget que estoura derruba a tela inteira.
     */
    private function usuariosVinculadosComAcesso(): int
    {
        $tabela = (string) config('authentication-log.table_name', 'authentication_log');

        if (! rescue(fn (): bool => Schema::hasTable($tabela), false, report: false)) {
            return 0;
        }

        return DB::table('tenant_user')
            ->join($tabela, function ($join) use ($tabela): void {
                $join->on($tabela.'.authenticatable_id', '=', 'tenant_user.user_id')
                    ->where($tabela.'.authenticatable_type', '=', (new User)->getMorphClass())
                    ->where($tabela.'.login_successful', '=', true)
                    ->where($tabela.'.login_at', '>=', now()->subDays(TenantResource::DIAS_DE_INSIGHT));
            })
            ->distinct()
            ->count('tenant_user.user_id');
    }

    /**
     * Base zero não tem percentual — a divisão seria por zero, e "0%" mentiria sobre uma amostra
     * inexistente. Mesmo critério de `UsuariosVisaoGeralStats::descreverCobertura()`.
     */
    private function taxaDeAtivacao(int $ativos, int $vinculados): int
    {
        return $vinculados === 0 ? 0 : (int) round(($ativos / $vinculados) * 100);
    }
}

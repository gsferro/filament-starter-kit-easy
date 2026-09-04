<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Tenants\Widgets;

use App\Filament\Admin\Resources\Tenants\TenantResource;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Schema;
use LaBoiteACode\FilamentDashboardWidgets\Data\BreakdownItem;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\BreakdownWidget;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

/**
 * Quantos acessos bem-sucedidos cada painel recebeu na janela.
 *
 * A coluna `painel` de `authentication_log` é nova (migration
 * `add_painel_to_authentication_log_table`) e quem a preenche é o hook `creating` de
 * `KitServiceProvider::registrarPainelNoLogDeAcesso()`.
 *
 * ## Os acessos sem painel aparecem, e é isso que impede o widget de mentir
 *
 * Todo login anterior à migration tem `painel` nulo, e não há como inferir o painel de um acesso
 * passado. Um `whereNotNull('painel')` deixaria o gráfico bonito e ERRADO: a soma das fatias não
 * bateria com o total de acessos da janela, e nada na tela diria por quê. Os nulos viram uma
 * fatia própria, em cinza. Ela encolhe sozinha conforme o tempo passa — é temporária por
 * construção. Ver ADR-04 de `wikis/specs/main/insights-das-organizacoes/`.
 */
class AcessosPorPainel extends BreakdownWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    /**
     * Sem a COLUNA, e não sem a tabela: numa instalação que atualizou o código e ainda não rodou
     * `migrate`, a tabela existe e a coluna não — consultar `painel` ali estouraria a tela.
     */
    public static function canView(): bool
    {
        return TenantResource::canAccess() && rescue(
            fn (): bool => Schema::hasColumn(
                (string) config('authentication-log.table_name', 'authentication_log'),
                'painel',
            ),
            false,
            report: false,
        );
    }

    public function getHeading(): ?string
    {
        return 'Acessos por painel';
    }

    public function getHeadingDescription(): ?string
    {
        return 'Entradas confirmadas nos últimos '.TenantResource::DIAS_DE_INSIGHT.' dias';
    }

    public function getEmptyStateHeading(): string
    {
        return 'Nenhum acesso registrado na janela';
    }

    public function getEmptyStateIcon(): string
    {
        return 'heroicon-o-rectangle-group';
    }

    /**
     * @return array<int, BreakdownItem>
     */
    protected function getItems(): array
    {
        return AuthenticationLog::query()
            ->selectRaw('painel, COUNT(*) as acessos')
            ->where('login_successful', true)
            ->where('login_at', '>=', now()->subDays(TenantResource::DIAS_DE_INSIGHT))
            ->groupBy('painel')
            ->orderByDesc('acessos')
            ->get()
            ->map(fn (AuthenticationLog $linha): BreakdownItem => $this->fatia(
                $linha->getAttribute('painel'),
                (int) $linha->getAttribute('acessos'),
            ))
            ->all();
    }

    /**
     * A fatia de um painel — ou a dos acessos que nasceram antes do carimbo.
     *
     * `Filament::getPanel($id, isStrict: false)` e o `isStrict` **não é opcional**: a assinatura é
     * `getPanel(?string $id = null, bool $isStrict = true)` (`FilamentManager.php:372`) e o
     * default LANÇA quando o id não existe. Id de painel que sobrou no log depois de o painel ser
     * removido derrubaria o widget inteiro.
     */
    private function fatia(mixed $painel, int $acessos): BreakdownItem
    {
        if (! is_string($painel) || $painel === '') {
            return BreakdownItem::make('Antes do registro por painel', $acessos)
                ->color('gray')
                ->description('Acessos anteriores ao carimbo — o painel não é inferível');
        }

        $registrado = rescue(
            fn (): string => Filament::getPanel($painel, isStrict: false)->getId(),
            null,
            report: false,
        );

        return BreakdownItem::make($registrado ?? $painel, $acessos)
            ->color($registrado === null ? 'warning' : 'primary')
            ->description($registrado === null ? 'Painel que não existe mais nesta instalação' : null);
    }
}

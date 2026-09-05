<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Tenants\Widgets;

use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Schema;
use LaBoiteACode\FilamentDashboardWidgets\Data\BreakdownItem;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\BreakdownWidget;

/**
 * Quantas PESSOAS DISTINTAS de cada organização entraram na janela.
 *
 * É o pedido central da wiki, e "distintas" foi decidido com o solicitante: uma pessoa vinculada
 * a duas organizações conta nas duas. A leitura recusada era "usuários que pertencem só a esta
 * organização", que deixaria de fora justamente quem opera mais de uma.
 *
 * ## O que este número NÃO é
 *
 * Não é auditoria de acesso. É "quantos vinculados usaram o sistema", e a diferença aparece
 * quando alguém é desvinculado: o acesso de ontem sai da contagem de hoje. `authentication_log`
 * não pode carimbar organização — no `/app` ela é escolhida DEPOIS de autenticar, e
 * `Filament::getTenant()` no instante do `Login` é nulo. Ver ADR-02.
 *
 * Uma consulta agregada, nunca N+1: o `COUNT(DISTINCT)` roda no banco e o `join` do log entra com
 * o filtro de morph, de sucesso e de janela — tudo na cláusula do `join`, não num `where` depois,
 * senão organização sem nenhum acesso desapareceria em vez de aparecer com zero.
 */
class UsuariosUnicosPorOrganizacao extends BreakdownWidget
{
    private const TETO_DE_ORGANIZACOES = 10;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return TenantResource::canAccess() && rescue(
            fn (): bool => Schema::hasTable(self::tabelaDeLog()),
            false,
            report: false,
        );
    }

    public function getHeading(): ?string
    {
        return 'Usuários únicos por organização';
    }

    public function getHeadingDescription(): ?string
    {
        return 'Pessoas distintas que entraram nos últimos '.TenantResource::DIAS_DE_INSIGHT.' dias';
    }

    public function getEmptyStateHeading(): string
    {
        return 'Nenhum acesso registrado na janela';
    }

    public function getEmptyStateIcon(): string
    {
        return 'heroicon-o-users';
    }

    /**
     * @return array<int, BreakdownItem>
     */
    protected function getItems(): array
    {
        $tabela = self::tabelaDeLog();
        $desde  = now()->subDays(TenantResource::DIAS_DE_INSIGHT);
        $morph  = (new User)->getMorphClass();

        return Tenant::query()
            ->select('tenants.id', 'tenants.nome')
            ->selectRaw('COUNT(DISTINCT CASE WHEN users.deleted_at IS NULL AND access_logs.authenticatable_id IS NOT NULL THEN access_logs.authenticatable_id END) as usuarios')
            ->leftJoin('tenant_user', 'tenant_user.tenant_id', '=', 'tenants.id')
            ->leftJoin('users', 'users.id', '=', 'tenant_user.user_id')
            /*
             * Os três filtros vão DENTRO do `join`, não num `where` depois: num `where` eles
             * transformariam o LEFT em INNER de fato e a organização sem acesso na janela sairia
             * do resultado — quando o que se quer é vê-la com zero.
             */
            ->leftJoin($tabela.' as access_logs', function (JoinClause $join) use ($desde, $morph): void {
                $join->on('access_logs.authenticatable_id', '=', 'tenant_user.user_id')
                    ->where('access_logs.authenticatable_type', '=', $morph)
                    ->where('access_logs.login_successful', '=', true)
                    ->where('access_logs.login_at', '>=', $desde);
            })
            ->groupBy('tenants.id', 'tenants.nome')
            ->orderByDesc('usuarios')
            ->limit(self::TETO_DE_ORGANIZACOES)
            ->get()
            ->map(fn (Tenant $organizacao): BreakdownItem => BreakdownItem::make(
                (string) $organizacao->getAttribute('nome'),
                (int) $organizacao->getAttribute('usuarios'),
            )
                /*
                 * O breakdown vira navegação: clicar na fatia abre a organização.
                 *
                 * `panel: 'admin'` é OBRIGATÓRIO. `Resource::getUrl()` resolve contra o painel
                 * CORRENTE, e este widget é montado por um componente Livewire — não por um
                 * request de painel. Sem fixar, a rota é procurada no painel que estiver corrente
                 * e o widget estoura com
                 * `Route [filament.infra.resources.organizacoes.view] not defined`.
                 * Medido: foi assim que CT-08 falhou na primeira execução.
                 */
                ->url(TenantResource::getUrl('view', ['record' => $organizacao->getKey()], panel: 'admin')))
            ->all();
    }

    private static function tabelaDeLog(): string
    {
        return (string) config('authentication-log.table_name', 'authentication_log');
    }
}

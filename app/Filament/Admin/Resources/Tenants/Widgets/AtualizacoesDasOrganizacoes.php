<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Tenants\Widgets;

use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Filament\Infra\Widgets\AuditoriaRecente;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use OwenIt\Auditing\Models\Audit;

/**
 * A timeline de alterações, recortada no cadastro de organizações.
 *
 * **Estende `AuditoriaRecente`, não `TimelineWidget` direto.** Este widget é aquele mais um
 * `where` — `getEvents()`, o agrupamento por dia, os rótulos "Hoje"/"Ontem", a resolução dos
 * nomes dos autores numa consulta só, os ícones e as cores por evento são todos idênticos.
 * Reescrevê-los seria copiar ~120 linhas para acrescentar uma cláusula, e o dia em que o pai
 * mudasse esta classe ficaria para trás em silêncio.
 *
 * O seam é `AuditoriaRecente::consulta()`, extraído sem mudar comportamento nenhum lá.
 *
 * **Custo aceito**: mexer em `AuditoriaRecente` passa a mexer nos dois. É o preço de não manter
 * duas cópias da mesma timeline, e o seam é justamente o que torna a herança um ponto só de
 * contato. Ver o passo 7 de `wikis/specs/main/insights-das-organizacoes/01-plano-acao.md`.
 */
class AtualizacoesDasOrganizacoes extends AuditoriaRecente
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    /**
     * Sobrescreve o `canView()` que vem de `ExigePermissaoDoWidget` no pai — e essa sobrescrita é
     * o ponto, não um descuido.
     *
     * O pai é widget de painel, descoberto pelo `discoverWidgets()` do `/infra`, e tem
     * `View:AuditoriaRecente` no banco. Este vive fora do discovery (para não aparecer no
     * dashboard) e por isso o Shield não gera permission para ele. A barreira é a da tela que o
     * hospeda. Ver ADR-03.
     */
    public static function canView(): bool
    {
        return TenantResource::canAccess() && (bool) rescue(
            fn (): bool => Schema::hasTable((string) config('audit.drivers.database.table', 'audits')),
            false,
            report: false,
        );
    }

    public function getHeading(): ?string
    {
        return 'Atualizações recentes';
    }

    public function getHeadingDescription(): ?string
    {
        return 'O que mudou no cadastro das organizações';
    }

    public function getEmptyStateHeading(): string
    {
        return 'Nenhuma alteração no cadastro';
    }

    public function getEmptyStateIcon(): string
    {
        return 'heroicon-o-building-office-2';
    }

    /**
     * @return Builder<Audit>
     */
    protected function consulta(): Builder
    {
        return parent::consulta()
            ->where('auditable_type', (new Tenant)->getMorphClass());
    }
}

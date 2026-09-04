<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Tenants\Widgets;

use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Models\Tenant;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use LaBoiteACode\FilamentDashboardWidgets\Data\RecentItem;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\RecentItemsWidget;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

/**
 * Os últimos acessos de quem pertence a ESTA organização.
 *
 * Mesmo desenho de `App\Filament\Infra\Widgets\UltimosAcessos`, recortado nos usuários da
 * organização aberta — e a duplicação é aceita: aquele widget é do `/infra`, sem `$record`, e
 * herdar dele para acrescentar um `whereIn` traria junto o `canView()` de painel e a permission
 * dele.
 *
 * As FALHAS entram na mesma lista, de propósito: uma lista só de sucessos esconde exatamente o
 * evento que se quer flagrar — a sequência de tentativas malsucedidas logo antes de uma
 * bem-sucedida.
 */
class OrganizacaoUltimosAcessos extends RecentItemsWidget
{
    public ?Tenant $record = null;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return TenantResource::canAccess() && (bool) rescue(
            fn (): bool => Schema::hasTable((string) config('authentication-log.table_name', 'authentication_log')),
            false,
            report: false,
        );
    }

    public function getHeading(): ?string
    {
        return 'Últimos acessos desta organização';
    }

    public function getEmptyStateHeading(): string
    {
        return 'Nenhum acesso registrado';
    }

    public function getEmptyStateIcon(): string
    {
        return 'heroicon-o-finger-print';
    }

    /**
     * @return array<int, RecentItem>
     */
    protected function getItems(): array
    {
        if (! $this->record instanceof Tenant) {
            return [];
        }

        return AuthenticationLog::query()
            // morphTo eager loaded: sem isto seriam N consultas só para descobrir o nome.
            ->with('authenticatable')
            ->where('authenticatable_type', (new User)->getMorphClass())
            ->whereIn('authenticatable_id', $this->record->users()->pluck('users.id'))
            ->orderByDesc('login_at')
            ->limit(5)
            ->get()
            ->map(function (AuthenticationLog $acesso): RecentItem {
                $sucesso = (bool) $acesso->getAttribute('login_successful');

                return RecentItem::make($this->identificar($acesso), $this->descrever($acesso))
                    ->icon($sucesso ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color($sucesso ? 'success' : 'danger')
                    ->badge($sucesso ? 'sucesso' : 'falha')
                    ->badgeColor($sucesso ? 'success' : 'danger')
                    ->meta($this->momentoDe($acesso)?->diffForHumans());
            })
            ->all();
    }

    private function identificar(AuthenticationLog $acesso): string
    {
        $autenticavel = $acesso->authenticatable;

        if (! $autenticavel instanceof Model) {
            return 'Origem não identificada';
        }

        $nome = $autenticavel->getAttribute('name') ?? $autenticavel->getAttribute('email');

        return is_string($nome) && $nome !== '' ? $nome : 'Usuário #'.$autenticavel->getKey();
    }

    /**
     * IP, dispositivo e — quando carimbado — o painel de onde veio o acesso.
     *
     * O painel é lido por `getAttribute()` e não por propriedade: numa instalação que ainda não
     * rodou a migration a coluna não existe, e o widget continua útil sem ela.
     */
    private function descrever(AuthenticationLog $acesso): string
    {
        /** @var list<string> $partes */
        $partes = [];

        foreach (['ip_address', 'device_name', 'painel'] as $campo) {
            $valor = $acesso->getAttribute($campo);

            if (is_string($valor) && $valor !== '') {
                $partes[] = $valor;
            }
        }

        return $partes === [] ? 'origem desconhecida' : implode(' • ', $partes);
    }

    private function momentoDe(AuthenticationLog $acesso): ?Carbon
    {
        $quando = $acesso->getAttribute('login_at');

        return $quando instanceof DateTimeInterface ? Carbon::instance($quando) : null;
    }
}

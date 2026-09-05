<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Tenants\Widgets;

use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Models\Tenant;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Gsferro\FilamentStatPlusEasy\Widgets\StatPlus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

/**
 * Os números de UMA organização, no topo da tela dela.
 *
 * O `$record` é injetado pelo Filament: `Resources\Pages\Concerns\InteractsWithRecord::getWidgetData()`
 * devolve `['record' => …]`, e `Page::getWidgetsSchemaComponents()` espalha isso nos parâmetros de
 * mount do Livewire. Não há parâmetro a passar à mão.
 *
 * **Sem stat de "situação"**: o campo `ativo` já está no infolist do mesmo registro, na mesma
 * tela, dois blocos abaixo. Foi corte da auditoria Ponytail da wiki.
 */
class OrganizacaoStats extends StatsOverviewWidget
{
    public ?Tenant $record = null;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Números desta organização';

    public static function canView(): bool
    {
        return TenantResource::canAccess();
    }

    /**
     * @return array<int, StatPlus>
     */
    protected function getStats(): array
    {
        if (! $this->record instanceof Tenant) {
            return [];
        }

        $vinculados = $this->record->users()->count();
        $ativos     = $this->usuariosComAcesso();

        return [
            StatPlus::make('Usuários vinculados', $vinculados)
                ->icon('heroicon-o-users')
                ->iconColor('primary')
                ->accentColor('primary')
                ->description('Pessoas com acesso a esta organização'),

            StatPlus::make('Ativos em '.TenantResource::DIAS_DE_INSIGHT.' dias', $ativos)
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->iconColor($ativos > 0 ? 'success' : 'warning')
                ->accentColor($ativos > 0 ? 'success' : 'warning')
                ->description($vinculados === 0
                    ? 'Nenhum usuário vinculado ainda'
                    : $ativos.' de '.$vinculados.' entraram na janela'),

            StatPlus::make('Último acesso', 0)
                ->icon('heroicon-o-clock')
                ->iconColor('gray')
                ->accentColor('gray')
                ->description($this->descreverUltimoAcesso()),
        ];
    }

    /** Pessoas distintas desta organização que entraram na janela. */
    private function usuariosComAcesso(): int
    {
        if (! $this->logDisponivel()) {
            return 0;
        }

        return AuthenticationLog::query()
            ->where('login_successful', true)
            ->where('login_at', '>=', now()->subDays(TenantResource::DIAS_DE_INSIGHT))
            ->where('authenticatable_type', (new User)->getMorphClass())
            ->whereIn('authenticatable_id', $this->idsDosUsuarios())
            ->distinct()
            ->count('authenticatable_id');
    }

    /**
     * O último acesso é DESCRIÇÃO, não valor: o odômetro do `StatPlus` só anima número, e
     * "há 3 dias" viraria zero na tela. Daí o valor `0` acima e a data aqui.
     */
    private function descreverUltimoAcesso(): string
    {
        if (! $this->logDisponivel()) {
            return 'Log de acesso não disponível nesta instalação';
        }

        $quando = AuthenticationLog::query()
            ->where('login_successful', true)
            ->where('authenticatable_type', (new User)->getMorphClass())
            ->whereIn('authenticatable_id', $this->idsDosUsuarios())
            ->max('login_at');

        return $quando === null
            ? 'Ninguém desta organização entrou ainda'
            : Carbon::parse($quando)->diffForHumans();
    }

    /**
     * Os ids dos usuários desta organização, uma consulta por render.
     *
     * `once()`: os dois stats que dependem disso chamariam a pivot duas vezes cada vez que a tela
     * carrega. Corte da auditoria Ponytail.
     *
     * @return array<int, mixed>
     */
    private function idsDosUsuarios(): array
    {
        return once(fn (): array => $this->record?->users()->pluck('users.id')->all() ?? []);
    }

    private function logDisponivel(): bool
    {
        return (bool) rescue(
            fn (): bool => Schema::hasTable((string) config('authentication-log.table_name', 'authentication_log')),
            false,
            report: false,
        );
    }
}

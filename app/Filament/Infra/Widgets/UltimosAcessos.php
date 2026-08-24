<?php

declare(strict_types=1);

namespace App\Filament\Infra\Widgets;

use App\Filament\Concerns\ExigePermissaoDoWidget;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use LaBoiteACode\FilamentDashboardWidgets\Data\RecentItem;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\RecentItemsWidget;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

/**
 * As últimas tentativas de acesso, com ou sem sucesso.
 *
 * As FALHAS entram na mesma lista de propósito: uma lista só de sucessos
 * esconde exatamente o evento que se quer flagrar — a sequência de tentativas
 * malsucedidas logo antes de uma bem-sucedida.
 *
 * RecentItems e não Timeline: aqui cada linha é um registro com identidade
 * (quem, de onde, em que dispositivo). A Timeline é para narrativa de
 * mudanças — que é o papel do widget de auditoria.
 */
class UltimosAcessos extends RecentItemsWidget
{
    use ExigePermissaoDoWidget;

    protected static ?int $sort = 120;

    protected int|string|array $columnSpan = 1;

    protected static function fonteDeDadosDisponivel(): bool
    {
        return (bool) rescue(
            fn (): bool => Schema::hasTable((string) config('authentication-log.table_name', 'authentication_log')),
            false,
            report: false,
        );
    }

    public function getHeading(): ?string
    {
        return 'Últimos acessos';
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
        return AuthenticationLog::query()
            // morphTo eager loaded: sem isto seriam N consultas só para
            // descobrir o nome de quem tentou entrar.
            ->with('authenticatable')
            ->orderByDesc('login_at')
            ->limit(5)
            ->get()
            ->map(function (AuthenticationLog $acesso): RecentItem {
                $sucesso = (bool) $acesso->login_successful;

                return RecentItem::make(
                    $this->identificar($acesso),
                    $this->descrever($acesso),
                )
                    ->icon($sucesso ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color($sucesso ? 'success' : 'danger')
                    ->badge($sucesso ? 'sucesso' : 'falha')
                    ->badgeColor($sucesso ? 'success' : 'danger')
                    ->meta($this->momentoDe($acesso)?->diffForHumans());
            })
            ->all();
    }

    /**
     * Tentativa contra conta inexistente não tem `authenticatable` — e é
     * justamente a que mais interessa numa varredura.
     */
    private function identificar(AuthenticationLog $acesso): string
    {
        $autenticavel = $acesso->authenticatable;

        if (! $autenticavel instanceof Model) {
            return 'Origem não identificada';
        }

        $nome = $autenticavel->getAttribute('name') ?? $autenticavel->getAttribute('email');

        return is_string($nome) && $nome !== '' ? $nome : 'Usuário #'.$autenticavel->getKey();
    }

    private function descrever(AuthenticationLog $acesso): string
    {
        /** @var list<string> $partes */
        $partes = [];

        if (is_string($acesso->ip_address) && $acesso->ip_address !== '') {
            $partes[] = $acesso->ip_address;
        }

        if (is_string($acesso->device_name) && $acesso->device_name !== '') {
            $partes[] = $acesso->device_name;
        }

        return $partes === [] ? 'origem desconhecida' : implode(' • ', $partes);
    }

    private function momentoDe(AuthenticationLog $acesso): ?Carbon
    {
        $quando = $acesso->getAttribute('login_at');

        return $quando instanceof DateTimeInterface ? Carbon::instance($quando) : null;
    }
}

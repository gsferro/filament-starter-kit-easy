<?php

declare(strict_types=1);

namespace App\Filament\Infra\Widgets;

use Illuminate\Support\Facades\Schema;
use LaBoiteACode\FilamentDashboardWidgets\Data\BreakdownItem;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\BreakdownWidget;
use OwenIt\Auditing\Models\Audit;

/**
 * Perfil das alterações registradas na trilha de auditoria.
 *
 * A leitura útil é a PROPORÇÃO entre criar, alterar e apagar: uma base saudável
 * tem muito `updated` e pouquíssimo `deleted`. Um salto de exclusões é o sinal
 * que este widget existe para dar — por isso o `deleted` vem sempre pintado de
 * vermelho, mesmo quando é a menor barra.
 */
class AuditoriaPorEvento extends BreakdownWidget
{
    /** Cor e rótulo pt-BR por evento do owen-it/laravel-auditing. */
    private const EVENTOS = [
        'created'  => ['Criações', 'success', 'heroicon-o-plus-circle'],
        'updated'  => ['Alterações', 'info', 'heroicon-o-pencil-square'],
        'deleted'  => ['Exclusões', 'danger', 'heroicon-o-trash'],
        'restored' => ['Restaurações', 'warning', 'heroicon-o-arrow-uturn-left'],
    ];

    protected static ?int $sort = 130;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return (bool) rescue(
            fn (): bool => Schema::hasTable(self::tabela()),
            false,
            report: false,
        );
    }

    public function getHeading(): ?string
    {
        return 'Auditoria por evento';
    }

    public function getHeadingDescription(): ?string
    {
        return 'Tudo que foi criado, alterado ou apagado';
    }

    public function getEmptyStateHeading(): string
    {
        return 'Nenhuma alteração auditada';
    }

    public function getEmptyStateIcon(): string
    {
        return 'heroicon-o-clipboard-document-list';
    }

    /**
     * @return array<int, BreakdownItem>
     */
    protected function getItems(): array
    {
        $contagem = Audit::query()
            ->selectRaw('event, count(*) as total')
            ->groupBy('event')
            ->pluck('total', 'event');

        $itens = [];

        // Ordem fixa do ciclo de vida (nasce, muda, morre, volta) em vez de
        // ordenar por volume: a lista precisa ficar no mesmo lugar entre
        // visitas para que a variação salte aos olhos.
        foreach (self::EVENTOS as $evento => [$rotulo, $cor, $icone]) {
            $total = (int) $contagem->get($evento, 0);

            if ($total > 0) {
                $itens[] = BreakdownItem::make($rotulo, $total)->color($cor)->icon($icone);
            }
        }

        // Eventos customizados (o pacote aceita qualquer string em `event`)
        // caem aqui — sem isso, uma trilha customizada sumiria do gráfico.
        $outros = $contagem
            ->reject(fn (mixed $total, string $evento): bool => array_key_exists($evento, self::EVENTOS))
            ->sum();

        if ((int) $outros > 0) {
            $itens[] = BreakdownItem::make('Outros eventos', (int) $outros)
                ->color('gray')
                ->icon('heroicon-o-ellipsis-horizontal-circle');
        }

        return $itens;
    }

    private static function tabela(): string
    {
        return (string) config('audit.drivers.database.table', 'audits');
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Filament\Concerns\ExigePermissaoDoWidget;
use App\Models\User;
use App\Support\Papeis;
use LaBoiteACode\FilamentDashboardWidgets\Data\BreakdownItem;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\BreakdownWidget;
use Spatie\Permission\Models\Role;

/**
 * Quantas pessoas carregam cada papel.
 *
 * Breakdown e não rosca: os papéis NÃO são partes de um todo — um usuário pode
 * ter vários papéis, então a soma das fatias pode passar do total de usuários e
 * um gráfico de composição ficaria matematicamente errado. A lista com barra
 * comparativa diz o que interessa (quem é maior que quem) sem prometer que as
 * partes fecham 100%.
 */
class UsuariosPorPapel extends BreakdownWidget
{
    use ExigePermissaoDoWidget;

    protected static ?int $sort = 20;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return 'Usuários por papel';
    }

    public function getHeadingDescription(): ?string
    {
        return 'Um usuário pode acumular mais de um papel';
    }

    public function getEmptyStateHeading(): string
    {
        return 'Nenhum papel cadastrado';
    }

    /**
     * Maior primeiro: a leitura útil é "qual papel concentra gente".
     */
    protected function shouldSortByValue(): bool
    {
        return true;
    }

    /**
     * @return array<int, BreakdownItem>
     */
    protected function getItems(): array
    {
        $itens = Role::query()
            ->withCount('users')
            ->get()
            ->map(fn (Role $papel): BreakdownItem => BreakdownItem::make(
                // Rótulo, não chave: `Papeis::rotulo()` é a mesma fonte da tabela de papéis.
                Papeis::rotulo((string) $papel->getAttribute('name')),
                // `users_count` só existe por causa do withCount() acima — vem
                // por getAttribute() porque não é coluna da tabela.
                (int) $papel->getAttribute('users_count'),
            )->icon('heroicon-o-identification'))
            ->all();

        // Usuário sem papel nenhum é o caso que ninguém vê e que mais dói:
        // entra no painel `app` e não aparece em nenhuma barra de papel.
        $semPapel = User::query()->doesntHave('roles')->count();

        if ($semPapel > 0) {
            $itens[] = BreakdownItem::make('Sem papel', $semPapel)
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning');
        }

        return $itens;
    }
}

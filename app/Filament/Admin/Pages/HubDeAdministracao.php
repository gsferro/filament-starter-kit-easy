<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Concerns\DescobreCardsDoPainel;
use BackedEnum;
use Filament\Pages\Dashboard;
use Harvirsidhu\FilamentCards\CardGroup;
use Harvirsidhu\FilamentCards\Filament\Pages\CardsPage;

/**
 * Porta de entrada do /admin: usuários, papéis, convites, organizações e agentes de IA em grade.
 *
 * Mesma construção do hub de infraestrutura — os cartões saem de `cardsDoPainel()`, que filtra
 * por `canAccess()` de cada destino. Ver `App\Filament\Concerns\DescobreCardsDoPainel`.
 */
class HubDeAdministracao extends CardsPage
{
    use DescobreCardsDoPainel;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = -10;

    /** @var int|string|array<string, int|string> */
    protected static int|string|array $columns = 3;

    protected static bool $searchable = true;

    protected static ?string $searchPlaceholder = 'Buscar destino...';

    protected static ?string $title = 'Hub de administração';

    protected static ?string $navigationLabel = 'Hub de administração';

    /**
     * A classe que dá escopo ao `resources/css/filament/cards.css`.
     *
     * Sem ela a grade sai sem estilo nenhum — e com o HTML correto, sem erro, sem aviso.
     * O CSS é escopado (e não global) para não atropelar a marcação de outros plugins que
     * usem os mesmos nomes de utilitária.
     *
     * @return array<string>
     */
    public function getPageClasses(): array
    {
        return ['kit-cards-page'];
    }

    /**
     * @return array<CardGroup>
     */
    protected static function getCards(): array
    {
        return static::cardsDoPainel(excluir: [static::class, Dashboard::class]);
    }
}

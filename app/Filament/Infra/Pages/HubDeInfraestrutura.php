<?php

declare(strict_types=1);

namespace App\Filament\Infra\Pages;

use App\Filament\Concerns\DescobreCardsDoPainel;
use BackedEnum;
use Filament\Pages\Dashboard;
use Harvirsidhu\FilamentCards\CardGroup;
use Harvirsidhu\FilamentCards\Filament\Pages\CardsPage;

/**
 * Porta de entrada do /infra: os destinos do painel em grade, não em árvore.
 *
 * É o painel que mais precisa: quatro grupos de navegação e as páginas próprias de sete plugins
 * (health, backups, filas, logs, auditoria, trilha de acesso, central de comandos, grafo de
 * dependências, releases). "Onde vejo os backups?" é uma pergunta real aqui.
 *
 * O hub SOMA à barra lateral, não a substitui: nenhum item sai da navegação. Esconder da sidebar
 * o que está no hub quebraria a busca ⌘K e obrigaria dois cliques onde havia um (ADR-01 da wiki
 * `hub-de-navegacao-em-cards`).
 *
 * Os cartões saem de `cardsDoPainel()`, que filtra por `canAccess()` de cada destino — ver o
 * cabeçalho de `App\Filament\Concerns\DescobreCardsDoPainel` para por que isso não pode ser
 * `CardItem::make()` à mão.
 */
class HubDeInfraestrutura extends CardsPage
{
    use DescobreCardsDoPainel;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    // Sem grupo e com sort negativo: fica no topo, acima dos quatro grupos, como porta de entrada.
    protected static ?int $navigationSort = -10;

    /** @var int|string|array<string, int|string> */
    protected static int|string|array $columns = 3;

    // O painel com mais destinos é o único onde um campo de busca paga o próprio espaço.
    protected static bool $searchable = true;

    protected static ?string $searchPlaceholder = 'Buscar destino...';

    protected static ?string $title = 'Central de infraestrutura';

    protected static ?string $navigationLabel = 'Central de infraestrutura';

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

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
 * **Desligada por default** — só existe com `KIT_HUB=true`. Ver `canAccess()` abaixo e o bloco
 * "Hub de navegação em cards" de `config/kit.php`.
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
     * Some do menu, da URL e da busca ⌘K quando `kit.hub` está desligado — que é o default do kit.
     *
     * **Só `canAccess()`, sem `shouldRegisterNavigation()`.** Em Page do Filament 5 um método basta
     * para os três efeitos: `Page::registerNavigationItems()` já retorna cedo quando `canAccess()`
     * é falso (`vendor/filament/filament/src/Pages/Page.php:133-135`), e a categoria
     * `PagesAutorizadasCategory` do Spotlight consulta o mesmo método. Em RESOURCE são dois — é por
     * isso que `ProjetoResource` sobrescreve os dois e esta Page, um. Acrescentar o segundo aqui é
     * ruído que sugere uma barreira que não existe.
     *
     * A rota continua registrada e responde 403, com a tela branda do filament-sentinel. Tirar a
     * rota exigiria recortar o `discoverPages()` do provider, e aí o Shield deixaria de gerar
     * `View:HubDeAdministracao` — ver ADR-02 da wiki `hub-de-cards-opcional`.
     */
    public static function canAccess(): bool
    {
        return (bool) config('kit.hub') && parent::canAccess();
    }

    /**
     * @return array<CardGroup>
     */
    protected static function getCards(): array
    {
        return static::cardsDoPainel(excluir: [static::class, Dashboard::class]);
    }
}

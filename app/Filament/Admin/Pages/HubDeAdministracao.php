<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Concerns\DescobreCardsDoPainel;
use App\Filament\Concerns\ExigePermissaoDaTela;
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
    use ExigePermissaoDaTela;

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
     * **Este método é o hook de `ExigePermissaoDaTela`, não o `canAccess()`.** O trait publica
     * `canAccess()` como `permissão && regraLocalDeAcesso()`, e sobrescrever `canAccess()` aqui
     * desligaria a permissão **em silêncio** — método de classe vence método de trait, sem erro
     * nenhum. A flag e a permissão são ortogonais e valem as duas (ADR-06 da wiki
     * `permissoes-de-telas-e-acoes`): com a flag desligada nem o `master_global` entra, porque ele
     * vence permissão pelo `Gate::before` e não vence config.
     *
     * **Um método só, sem `shouldRegisterNavigation()` à mão.** Em Page do Filament 5 o
     * `canAccess()` basta para os três efeitos: `Page::registerNavigationItems()` retorna cedo
     * quando ele é falso (`vendor/filament/filament/src/Pages/Page.php:133-135`), e a categoria
     * `PagesAutorizadasCategory` do Spotlight consulta o mesmo método. Em RESOURCE são dois — é por
     * isso que `ProjetoResource` sobrescreve os dois e esta Page, um.
     *
     * A rota continua registrada e responde 403, com a tela branda do filament-sentinel. Tirar a
     * rota exigiria recortar o `discoverPages()` do provider, e aí o Shield deixaria de gerar
     * `View:HubDeAdministracao` — ver ADR-02 da wiki `hub-de-cards-opcional`.
     */
    protected static function regraLocalDeAcesso(): bool
    {
        return (bool) config('kit.hub');
    }

    /**
     * @return array<CardGroup>
     */
    protected static function getCards(): array
    {
        return static::cardsDoPainel(excluir: [static::class, Dashboard::class]);
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Filament\Concerns\DescobreCardsDoPainel;
use App\Filament\Concerns\ExigePermissaoDaTela;
use BackedEnum;
use Filament\Pages\Dashboard;
use Harvirsidhu\FilamentCards\CardGroup;
use Harvirsidhu\FilamentCards\Filament\Pages\CardsPage;

/**
 * Porta de entrada do painel de negócio — o índice do que a pessoa pode fazer na organização.
 *
 * ## Esta página NÃO entra na lista de subtração do `panel_user`
 *
 * `.ai/rules/filament.md` manda acrescentar toda Page de ADMINISTRAÇÃO do painel `app` a
 * `PapeisSeeder::permissoesDeAdministracaoDoApp()`. Esta não é: é navegação de negócio, e todo
 * usuário do painel deve alcançá-la. Numa subtração o erro é espelhado — acrescentá-la "por
 * precaução" deixaria o usuário comum com 403 na própria página inicial dele.
 *
 * Não há risco de vazamento por isso: os cartões de administração (Usuários, Convites) somem
 * sozinhos pelo `canAccess()` que o `cardsDoPainel()` aplica. O usuário comum vê o hub COM MENOS
 * CARTÕES, nunca uma tela negada. Ver ADR-05 da wiki `hub-de-navegacao-em-cards`.
 *
 * A flag `kit.hub` **esconde a página; ela não muda a permissão**. Com o hub desligado,
 * `View:HubDoNegocio` continua no `panel_user` — e é isso que faz ligar a flag bastar, sem
 * ressemear nada. Ver ADR-02 da wiki `hub-de-cards-opcional`.
 *
 * **Desligada por default** — só existe com `KIT_HUB=true`. Ver `canAccess()` abaixo.
 *
 * Sem `$searchable`: com meia dúzia de destinos um campo de busca é ruído.
 */
class HubDoNegocio extends CardsPage
{
    use DescobreCardsDoPainel;
    use ExigePermissaoDaTela;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = -10;

    /** @var int|string|array<string, int|string> */
    protected static int|string|array $columns = 3;

    protected static ?string $title = 'Início';

    protected static ?string $navigationLabel = 'Início';

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
     * Hook de `ExigePermissaoDaTela`, e não `canAccess()`: sobrescrever o `canAccess()` aqui
     * desligaria a permissão em silêncio. Um método só, e não dois — o motivo está no docblock do
     * mesmo método em `App\Filament\Admin\Pages\HubDeAdministracao`.
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

<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Support\DashboardDinamico;
use Filament\Pages\Dashboard as FilamentDashboard;

/**
 * O dashboard clássico, compartilhado pelos painéis — o fallback da feature.
 *
 * Uma classe só, registrada nos três painéis: ela não grava `dashboards.page`,
 * então não precisa da fronteira por FQCN que a dinâmica precisa — mesmo
 * padrão do `Filament\Pages\Dashboard` que o kit já registrava nos três.
 *
 * Ocupa a RAIZ do painel (`$routePath = '/'`, herdado do
 * `Filament\Pages\Dashboard`) — a mesma posição da página que ele substituiu,
 * de propósito: o update do kit não pode mudar a URL canônica de painel nenhum
 * (RQ-08). Com a feature ligada E dashboard exibível, devolve para
 * `/dashboard`; caso contrário responde normalmente, com os widgets clássicos.
 */
final class DashboardClassico extends FilamentDashboard
{
    /**
     * O slug histórico, preservado de propósito.
     *
     * A rota vem do `$routePath = '/'` herdado, mas o NOME dela sai do slug
     * (`Pages/Concerns/HasRoutes.php:55-58`): sem esta linha a tela de entrada
     * passaria a se chamar `filament.{painel}.pages.dashboard-classico` e todo
     * `route('filament.app.pages.dashboard')` de projeto sobre o kit quebraria
     * em silêncio. RQ-08 vale para o nome da rota, não só para a URL.
     */
    protected static ?string $slug = 'dashboard';

    /**
     * Devolve para a dinâmica só quando ela ATENDE de verdade — flag ligada e
     * dashboard exibível para quem entrou.
     *
     * O `habilitado()` sozinho criava um beco: com dashboards restritos a
     * papéis que o usuário não tem, a dinâmica aborta 403 (vendor
     * `DynamicDashboard.php:123-125`) e este `mount()` mandava o usuário de
     * volta para ele a cada tentativa (CT-30).
     */
    public function mount(): void
    {
        $dinamica = DashboardDinamico::paginaDinamicaDo();

        if ($dinamica !== null && DashboardDinamico::atende($dinamica)) {
            $this->redirect($dinamica::getUrl(), navigate: true);
        }
    }

    /**
     * A navegação continua decidida SÓ pela config — de propósito.
     *
     * `atende()` consulta o banco, e isto roda em toda renderização de menu.
     * No caso do 403 quem leva o usuário até aqui é o próprio item da
     * dinâmica, que redireciona: ninguém fica sem caminho, e nenhuma página
     * paga uma query de dashboards para montar o menu.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return ! DashboardDinamico::habilitado()
            || DashboardDinamico::paginaDinamicaDo() === null;
    }
}

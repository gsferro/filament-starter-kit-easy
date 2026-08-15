<?php

namespace App\Filament\Spotlight;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Wezlo\FilamentSearchSpotlight\Actions\SpotlightAction;
use Wezlo\FilamentSearchSpotlight\Actions\SpotlightActionRegistry;

/**
 * Sugestões "Criar X" na busca ⌘K — a versão do kit, com permissão.
 *
 * Por que não usar o discovery do pacote: ele percorre os resources do painel
 * filtrando só por `class_exists` e pela existência da page `create`, SEM
 * chamar `canCreate()`, e resolve a URL ao montar o resultado. Isso dá dois
 * problemas conhecidos:
 *
 * 1. **500 na tela de login** — a busca com termo vazio roda no mount do
 *    overlay, e `getUrl('create')` de um resource com tenant estoura
 *    `UrlGenerationException` quando ainda não há tenant.
 * 2. **Vazamento de affordance** — oferece "Criar Usuário" a quem não pode.
 *
 * Por isso o painel liga `actionsEnabled()` e desliga `disableCreateActions()`:
 * as ações entram por este registry, lido pelo pacote no MESMO request, depois
 * de auth e tenant resolvidos. É o único ponto de injeção disponível — o
 * caminho de query vazia instancia a categoria de ações do vendor direto, sem
 * passar pela lista de `categories()` do painel.
 *
 * Três guards, todos necessários:
 * - `canAccess()` — o perfil pode abrir a tela?
 * - `canCreate()` — pode criar o registro? (a pergunta que o vendor não faz)
 * - `shouldRegisterNavigation()` — a tela é exposta no produto?
 */
class AcoesDeCriacao
{
    public static function registrar(): void
    {
        $painel = Filament::getCurrentPanel();

        // Visitante (tela de login) não recebe ação nenhuma — é o que impede o
        // 500 de voltar: sem usuário, nenhuma policy passa e nada é registrado.
        if ($painel === null || Auth::guest()) {
            return;
        }

        /*
         * Painel COM tenancy e SEM tenant resolvido também fica de fora. Mesma
         * falha do item 1, por outro caminho: usuário autenticado numa página
         * cuja rota não é tenant-aware (a tela de 2FA é o caso clássico).
         * O guard é do lote inteiro porque, nesses painéis, toda rota de
         * resource carrega `{tenant}` — sem tenant não há uma única ação que
         * resolva. O kit não usa tenancy, mas o guard fica: é grátis e evita
         * a regressão no dia em que alguém ligar.
         */
        if ($painel->hasTenancy() && Filament::getTenant() === null) {
            return;
        }

        $registry = app(SpotlightActionRegistry::class);

        foreach ($painel->getResources() as $resource) {
            if (! isset($resource::getPages()['create'])) {
                continue;
            }

            if (! $resource::canAccess() || ! $resource::canCreate() || ! $resource::shouldRegisterNavigation()) {
                continue;
            }

            $rotulo = ucfirst((string) $resource::getModelLabel());

            $registry->register(
                SpotlightAction::make('criar-'.$resource::getSlug())
                    ->label("Criar {$rotulo}")
                    ->icon('heroicon-o-plus')
                    ->keywords(['criar', 'novo', 'nova', 'adicionar', $rotulo])
                    ->group('Criar')
                    /*
                     * Closure para resolver a URL só ao montar o resultado, quando o contexto do
                     * request já está completo — e `panel:` FIXANDO o painel de origem.
                     *
                     * Sem o `panel:`, a URL é resolvida contra o painel CORRENTE no momento do
                     * clique, e não contra o painel que registrou a ação. Isso quebra porque o
                     * `SpotlightActionRegistry` é singleton de container
                     * (`FilamentSearchSpotlightServiceProvider.php:25`): num processo que atende
                     * dois painéis — worker persistente, teste, console — as ações do primeiro
                     * sobrevivem, e a Closure delas tenta uma rota que só existe lá. O sintoma é
                     * `Route [filament.app.resources.agentes-ia.create] not defined`, com 500 numa
                     * tela que não tem nada a ver com o resource citado.
                     */
                    ->url(fn (): string => $resource::getUrl('create', panel: $painel->getId())),
            );
        }
    }
}

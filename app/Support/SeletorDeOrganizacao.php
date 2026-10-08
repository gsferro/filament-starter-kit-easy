<?php

namespace App\Support;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;

/**
 * O seletor de organização do painel /app: aparece ou não, por request.
 *
 * ## Quem lê e quando
 *
 * O `AppPanelProvider` passa uma Closure a `Panel::tenantMenu()`, e o Filament a
 * avalia no RENDER da sidebar e da topbar — `hasTenantMenu()` roda `$this->evaluate()`
 * por request (`vendor/filament/filament/src/Panel/Concerns/HasTenancy.php:344-347`),
 * não na construção do painel. É por isso que o interruptor da tela de configurações
 * governa de verdade: gravou, vale no próximo F5, sem cache nem restart.
 *
 * ## O que "acesso a uma organização" conta
 *
 * A contagem é `Filament::getUserTenants()` — a MESMA fonte do seletor
 * (`FilamentManager::getUserTenants()` delega a `User::getTenants()`, que devolve só
 * organizações ativas, e para o `master_global` todas as ativas). Contar a tabela
 * pivot diria que quem tem um vínculo inativo "tem duas organizações" e exibiria um
 * seletor sem nada para trocar — divergindo do próprio componente que ele governa.
 *
 * ## Por que `tenantMenu()` e não `tenantSwitcher()`
 *
 * O switcher esconde só a lista suspensa; o bloco inteiro (avatar, rótulo e nome da
 * organização) continuaria ocupando o topo da barra lateral — que é exatamente a
 * informação redundante com o cabeçalho configurável, e o motivo da feature.
 *
 * Esconder é decisão de UI: `canAccessTenant()`, a URL `/app/{tenant}` e o acesso
 * por link direto continuam valendo. Quem tem uma organização continua dentro dela.
 */
final class SeletorDeOrganizacao
{
    /**
     * O seletor de organização aparece para o usuário corrente?
     *
     * Desligado (o default), sempre aparece — comportamento de sempre, sem query.
     * Ligado, esconde de quem tem zero ou uma organização acessível: zero é o caso
     * defensivo (sem organização acessível o `/app` nem resolve tenant), e uma é o
     * caso em que não há nada para trocar.
     */
    public static function visivel(): bool
    {
        if (! config('kit.tenancy.ocultar_seletor_unico')) {
            return true;
        }

        $usuario = Filament::auth()->user();

        // Defesa: o menu só renderiza com usuário autenticado, mas a Closure pode
        // ser avaliada fora desse contexto (console, página de erro). Sem usuário
        // não há o que ocultar.
        if ($usuario === null) {
            return true;
        }

        $quantidade = count(Filament::getUserTenants($usuario));

        if ($quantidade > 1) {
            return true;
        }

        Log::channel('tenancy')->debug(
            "[SeletorDeOrganizacao@visivel] Seletor de organizacao oculto | user: {$usuario->getKey()}",
            [
                'user_id'               => $usuario->getKey(),
                'organizacoes'          => $quantidade,
                'ocultar_seletor_unico' => true,
            ],
        );

        return false;
    }
}

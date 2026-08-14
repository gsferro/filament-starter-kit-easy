<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fixa o contexto de papéis do spatie/permission no tenant corrente.
 *
 * Com `permission.teams` ligado, papel e permissão são resolvidos por
 * `team_id`. Se ninguém disser qual é o team do request, o spatie resolve com
 * `team_id` nulo: o usuário aparece SEM papel nenhum, sem erro — a pior forma
 * de falhar, porque a tela só fica vazia.
 *
 * Registrado como TENANT middleware no AppPanelProvider, com
 * `isPersistent: true`. O `isPersistent` é o que o faz rodar também nos
 * requests AJAX do Livewire; sem isso o contexto se perde na primeira
 * interação de tabela e a página passa a mentir sobre as permissões.
 *
 * Não confundir com `Filament\Http\Middleware\IdentifyTenant`, que resolve o
 * tenant a partir da rota — esse o Filament registra sozinho.
 *
 * ## Duas responsabilidades, e o nome só anuncia uma
 *
 * Além do contexto de papéis, ele grava o tenant corrente na sessão — porque este é o ponto do
 * kit em que o tenant do request é conhecido, e a tela de bloqueio precisa dele sem tê-lo na
 * rota. Um middleware separado só para uma chave de sessão, ao lado de um que já tem o valor em
 * mãos, é arquivo a mais sem ganho. Renomear a classe tocaria o `AppPanelProvider` e os testes
 * de tenancy por ganho cosmético. Ver ADR-03.
 */
class DefinirTenantDePermissoes
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        // Sem tenant resolvido (tela de 2FA, rota não tenant-aware), cai no
        // contexto global em vez de null: `model_has_roles.team_id` é NOT NULL
        // e um null aqui derruba qualquer atribuição de papel no request.
        app(PermissionRegistrar::class)->setPermissionsTeamId(
            $tenant?->getKey() ?? Tenant::CONTEXTO_GLOBAL,
        );

        /*
         * O tenant corrente, para quem NÃO o recebe pela rota.
         *
         * A tela de bloqueio é o caso: o pacote registra `/{painel}/screen/lock` com
         * `->prefix($panel->getPath())` e só o middleware base
         * (vendor/marjose123/filament-lockscreen/routes/web.php), então ela não tem o segmento
         * `{tenant}` nem o tenantMiddleware, e `Filament::getTenant()` é null lá. O Filament
         * também não persiste tenant em sessão — `FilamentManager::$tenant` é propriedade de
         * instância, preenchida só pelo `IdentifyTenant` a partir da rota.
         *
         * Esta linha é a única fonte. Ver ADR-03 da wiki `identidade-visual-da-organizacao`.
         */
        session(['tenant_corrente' => $tenant?->getKey()]);

        Log::channel('tenancy')->debug(
            '[DefinirTenantDePermissoes@handle] Contexto de papéis fixado | tenant: '.($tenant?->getKey() ?? 'nenhum'),
            [
                'tenant_id' => $tenant?->getKey(),
                'user_id'   => $request->user()?->getKey(),
                'em_sessao' => true,
            ],
        );

        return $next($request);
    }
}

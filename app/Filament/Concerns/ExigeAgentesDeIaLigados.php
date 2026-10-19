<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Support\AgentesDeIa;
use Illuminate\Support\Facades\Log;

/**
 * As Pages do catálogo "Agentes de IA" respondem 404 com o uso de agentes desligado — mesmo
 * para quem tem a permissão (RQ-05 da wiki `agentes-ia-no-settings`).
 *
 * Sobrescreve `authorizeResourceAccess()` do vendor, que o Filament chama no `mount` E em todo
 * update Livewire das Pages (`CanAuthorizeResourceAccess::hydrateCanAuthorizeResourceAccess()`):
 * a tela já aberta quando a instalação desliga o uso recusa a próxima ação (P-06). O `parent::`
 * mantém o 403 por permissão de sempre — o interruptor é condição a mais, não substitui a policy.
 *
 * 404 e não 403, de propósito: com o uso desligado o catálogo "não é disponível", e o pedido é
 * explícito (RQ-05). A rule `.ai/rules/filament.md` descreve o 403 da Page escondida por
 * config — é o caso geral; este é a exceção pedida. A rota continua registrada, então o Shield
 * continua gerando as permissions do Resource.
 */
trait ExigeAgentesDeIaLigados
{
    public static function authorizeResourceAccess(): void
    {
        static::exigirUsoDeAgentesLigado();

        parent::authorizeResourceAccess();
    }

    /**
     * O interruptor vem ANTES da permissão. O `mount()` de `CreateRecord` e de `EditRecord` chama
     * `authorizeAccess()` (403 por `canCreate()`/`canEdit()`) antes de o Livewire rodar o hook
     * `mountCanAuthorizeResourceAccess()`: sem esta sobrescrita, quem não tinha a permissão recebia
     * 403 com o uso desligado, e o 404 só valia para quem tinha — a ordem errada para "não é
     * disponível" (CT-11 da wiki, linhas "sem permissão"). `ListRecords` declara o método vazio,
     * então o `parent::` existe nas três Pages. Só o interruptor aqui: a policy do índice
     * (`parent::authorizeResourceAccess()`) continua sendo conferida uma vez, pelo hook do vendor —
     * chamá-la daqui a avaliaria duas vezes por mount e por update (CR-02 da revisão do diff).
     */
    protected function authorizeAccess(): void
    {
        static::exigirUsoDeAgentesLigado();

        parent::authorizeAccess();
    }

    private static function exigirUsoDeAgentesLigado(): void
    {
        if (AgentesDeIa::habilitado()) {
            return;
        }

        Log::channel('ai')->warning(
            '[ExigeAgentesDeIaLigados@exigirUsoDeAgentesLigado] Catálogo recusado com o uso de agentes desligado | pagina: '.static::class,
            ['user_id' => auth()->id(), 'pagina' => static::class, 'motivo' => 'agentes_ia_desligado'],
        );

        abort(404);
    }
}

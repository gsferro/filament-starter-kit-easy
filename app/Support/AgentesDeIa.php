<?php

declare(strict_types=1);

namespace App\Support;

use Filament\Facades\Filament;
use Filament\Panel;

/**
 * O decisor dos agentes de IA — a ÚNICA leitura de `kit.agentes_ia.*`.
 *
 * Duas chaves, editáveis em /admin/configuracoes-da-aplicacao (aba Aplicação, seção "Agentes
 * de IA"): o interruptor, que liga o catálogo "Agentes de IA" do `/admin` e é condição para
 * o chat, e a lista de painéis em que o chat do Assistente aparece. As duas chegam por
 * `config()`, já sobreposta pelo banco no boot (`ConfiguracoesDoKit::aplicarNaConfig()`):
 * zero consulta ao banco para decidir se o catálogo, os widgets ou o chat aparecem — o mesmo
 * regime de `DockDeAtalhos` e `DashboardDinamico`.
 *
 * Lista VAZIA = NENHUM painel, como no dock (e ao contrário do dashboard dinâmico, em que
 * vazio é todos): instalação nova nasce sem chat em lugar nenhum, e o interruptor nasce
 * desligado. Falha fechado nas duas pontas.
 *
 * Wiki `wikis/specs/feat/agentes-ia-no-settings/`.
 */
final class AgentesDeIa
{
    /** O uso dos agentes está ligado? Governa o catálogo, os widgets dele e é condição para o chat. */
    public static function habilitado(): bool
    {
        return (bool) config('kit.agentes_ia.habilitado', false);
    }

    /** O chat do Assistente vale neste painel? Nulo (fora de contexto de painel) é não. */
    public static function assistenteNoPainel(?Panel $painel): bool
    {
        return $painel !== null
            && self::habilitado()
            && in_array($painel->getId(), (array) config('kit.agentes_ia.paineis', []), true);
    }

    /**
     * O chat está nesta tela? — a condição ÚNICA, consumida pelo render hook que o monta
     * (`ConfiguraFilamentGlobal::configuraChatDoAssistente()`) e pelo recuo do botão "voltar ao
     * topo", que existe por causa dele. Tela de autenticação nunca tem o chat
     * (`App\Support\TelaDeAutenticacao`): numa sessão bloqueada, o slide-over mostraria os
     * títulos das conversas.
     */
    public static function chatNaTela(): bool
    {
        return self::assistenteNoPainel(Filament::getCurrentPanel()) && ! TelaDeAutenticacao::corrente();
    }
}

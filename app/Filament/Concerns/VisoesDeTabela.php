<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Collection;
use Sehsah\FilamentRememberTable\Concerns\HasRememberTable;
use Sehsah\FilamentRememberTable\Models\TableView;

/**
 * Visões salvas de tabela nas páginas de listagem do kit — o embrulho do
 * `sehsah/filament-remember-table` com o interruptor das configurações e a permissão.
 *
 * ## O que o pacote entrega
 *
 * `HasRememberTable` grava, com nome, o recorte da listagem (filtros, busca, buscas por coluna,
 * ordenação, agrupamento, colunas e aba ativa) por usuário e, no painel de organização, por
 * organização (`tenant_id` = `Filament::getTenant()`), e devolve um `ActionGroup` "Visões" para o
 * cabeçalho da página. A fronteira já está na consulta dele: `visibleTo($user)` (as próprias mais
 * as compartilhadas), `ownedBy($user)` (atualizar, gerenciar, apagar) e `forTenant()` em toda
 * leitura (`vendor/sehsah/filament-remember-table/src/Concerns/HasRememberTable.php:tableViewsQuery():576-584`).
 * O que ele NÃO tem é interruptor nem permissão — é o que este trait acrescenta.
 *
 * ## Interruptor lido por request, sem consulta quando desligado (D4, P-03)
 *
 * `config('kit.tabelas.visoes_salvas')` é a propriedade `visoes_de_tabela` das configurações,
 * sobreposta pelo banco no boot (`ConfiguracoesDoKit::mapaDeConfiguracao()`). Desligada, três
 * sobrescritas garantem que NADA aconteça: `getTableViewActions()` devolve `[]` (sem botão),
 * `getTableViews()` devolve coleção vazia (então `applyTableView()`, chamável por `$wire`, não acha
 * visão nenhuma e devolve `false`) e `bootedHasRememberTable()` não roda (a visão padrão não é
 * consultada nem aplicada). Zero consulta ao banco, e as visões gravadas ficam intactas para
 * quando a opção voltar. Os sete nomes do pacote renomeados no `use … as protected …DoPacote`
 * NÃO são superfície Livewire: alias de trait herdaria `public`, e o Livewire 4 expõe todo
 * método público não estático a `$wire.` (`vendor/livewire/livewire/src/Drawer/BaseUtils.php:getPublicMethodsDefinedBySubClass():108-116`)
 * — `$wire.getTableViewsDoPacote()` furaria o interruptor (achado RD-02 do step 9; CT-80).
 *
 * ## Por que sobrescrever funciona
 *
 * Método declarado no trait que `use` outro trait VENCE o do trait usado (provado por script no
 * step 3 da wiki), e o Livewire resolve o hook `booted{NomeDoTrait}` por `class_uses_recursive`
 * mais o nome do método (`vendor/livewire/livewire/src/Features/SupportLifecycleHooks/SupportLifecycleHooks.php:'booted':125`)
 * — então o `bootedHasRememberTable` daqui é o chamado, e o do pacote só roda pelo alias.
 *
 * ## Interruptor também nas quatro Actions
 *
 * `getTableViewActions()` vazio esconde o botão, mas Action do Filament continua montável pelo
 * NOME (`$wire.mountAction('saveTableView')`), e o `booted` desligado não impede uma gravação
 * forjada. Por isso cada uma das quatro leva também `->hidden(! visoesHabilitadas())` — `hidden()`
 * e `authorize()` bloqueiam igual no `mountAction()` (`.ai/rules/filament.md`). Achado do CT-08.
 * É `hidden()`, não `visible()`: `CanBeHidden::visible()` SUBSTITUI a condição, e o pacote usa
 * `visible()` para esconder *Atualizar* sem visão própria ativa e modificada e *Gerenciar* sem visão
 * própria (`vendor/sehsah/filament-remember-table/src/Concerns/HasRememberTable.php:updateTableViewAction():209`, `:219-220`; `:manageTableViewsAction():238`, `:246`);
 * `resolveIsHiddenInGroup()` avalia `hidden` ANTES de `visible`, então, desligado, a condição do
 * pacote nem roda (achado RD-01 do step 9; CT-79).
 *
 * ## Permissão nas quatro Actions, não no model (D6, D11, P-08)
 *
 * `.ai/rules/filament.md`: Action nova nasce com a permissão consultada. As quatro Actions do
 * pacote são métodos públicos sobrescrevíveis (padrão documentado pelo autor: alias + override),
 * e é aqui que `->authorize('Usar:VisoesDeTabela')` entra sem editar o vendor. **Aplicar** uma
 * visão (`applyTableView_*` e `applyTableView()`) não exige a permissão: só lê o que o usuário já
 * vê. O model `TableView` não ganha barreira própria porque nenhum ponto de entrada o grava fora
 * dessas Actions (greps da `## Superfície Livewire` do `02`).
 *
 * A varredura de `tests/Kit/PermissoesDeAcoesTest.php` não vê estas Actions (o `Action::make()`
 * mora no vendor), e o inventário dela NÃO ganha linha — linha sem ocorrência vira órfã. A prova
 * da barreira é o CT-20/CT-21 da wiki.
 *
 * ## Como usar numa listagem nova
 *
 *   use VisoesDeTabela;
 *   protected function getHeaderActions(): array { return [...$this->getTableViewActions(), CreateAction::make()]; }
 *
 * Wiki `wikis/specs/feat/estudo-de-pacotes-rodada-3/`, ADR-01.
 */
trait VisoesDeTabela
{
    use HasRememberTable {
        bootedHasRememberTable as protected bootedHasRememberTableDoPacote;
        getTableViews as protected getTableViewsDoPacote;
        getTableViewActions as protected getTableViewActionsDoPacote;
        saveTableViewAction as protected saveTableViewActionDoPacote;
        updateTableViewAction as protected updateTableViewActionDoPacote;
        manageTableViewsAction as protected manageTableViewsActionDoPacote;
        resetTableViewAction as protected resetTableViewActionDoPacote;
    }

    /** A permissão exclusiva das visões (`config('filament-shield.custom_permissions')`). */
    public const string PERMISSAO_DAS_VISOES = 'Usar:VisoesDeTabela';

    /** O interruptor das configurações, lido por request. */
    public static function visoesHabilitadas(): bool
    {
        return (bool) config('kit.tabelas.visoes_salvas', false);
    }

    public function bootedHasRememberTable(): void
    {
        if (self::visoesHabilitadas()) {
            $this->bootedHasRememberTableDoPacote();
        }
    }

    /** @return Collection<int, TableView> */
    public function getTableViews(): Collection
    {
        return self::visoesHabilitadas() ? $this->getTableViewsDoPacote() : new Collection;
    }

    /** @return array<Action | ActionGroup> */
    public function getTableViewActions(): array
    {
        if (! self::visoesHabilitadas()) {
            return [];
        }

        // O painel do pacote nasce estreito e corta nome de visão com badge ("Administ…"):
        // `sm` cabe o nome de 100 caracteres do CT-53 com o badge ao lado.
        return array_map(
            static fn (Action|ActionGroup $acao): Action|ActionGroup => $acao instanceof ActionGroup
                ? $acao->dropdownWidth(Width::Small)
                : $acao,
            $this->getTableViewActionsDoPacote(),
        );
    }

    public function saveTableViewAction(): Action
    {
        return $this->saveTableViewActionDoPacote()
            ->hidden(static fn (): bool => ! self::visoesHabilitadas())
            ->authorize(self::PERMISSAO_DAS_VISOES);
    }

    public function updateTableViewAction(): Action
    {
        return $this->updateTableViewActionDoPacote()
            ->hidden(static fn (): bool => ! self::visoesHabilitadas())
            ->authorize(self::PERMISSAO_DAS_VISOES);
    }

    public function manageTableViewsAction(): Action
    {
        return $this->manageTableViewsActionDoPacote()
            ->hidden(static fn (): bool => ! self::visoesHabilitadas())
            ->authorize(self::PERMISSAO_DAS_VISOES);
    }

    public function resetTableViewAction(): Action
    {
        return $this->resetTableViewActionDoPacote()
            ->hidden(static fn (): bool => ! self::visoesHabilitadas())
            ->authorize(self::PERMISSAO_DAS_VISOES);
    }
}

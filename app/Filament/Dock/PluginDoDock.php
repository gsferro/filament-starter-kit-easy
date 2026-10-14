<?php

declare(strict_types=1);

namespace App\Filament\Dock;

use App\Models\User;
use App\Support\DockDeAtalhos;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use HoceineEl\QuickActionDock\Enums\DockLayout;
use HoceineEl\QuickActionDock\Enums\DockPosition;
use HoceineEl\QuickActionDock\Enums\DockStyle;
use HoceineEl\QuickActionDock\Livewire\QuickActionDock;
use HoceineEl\QuickActionDock\QuickActionDockPlugin;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/**
 * O dock de atalhos montado para os três painéis (wiki `dock-de-acoes-rapidas`).
 *
 * Registrado SEMPRE nos três providers (D3, `.ai/rules/providers-filament.md`): o componente
 * resolve o plugin pelo painel corrente (`QuickActionDockPlugin::get()`). Quem decide se o dock
 * aparece é o próprio plugin, por request:
 *
 * - `visible()` — o painel corrente está em `kit.dock.paineis` (`DockDeAtalhos::habilitadoNoPainel()`)
 * - `authorize()` — `Usar:Dock` pelo `Gate`, que passa pelo `Gate::before` do `master_global`
 *
 * As duas entram em `QuickActionDockPlugin::shouldRender()`, que decide tanto o render hook
 * (`renderDock()`) quanto o registro das actions no componente (`QuickActionDock::cacheDockActions()`):
 * sem as duas, nenhuma action do dock existe para o cliente chamar. As actions repetem
 * `->authorize(Usar:Dock)` porque o Filament reconfere a autorização ao executar (`mountAction()`).
 *
 * Todas as Closures leem só `config()` e o usuário já carregado: nenhuma consulta ao banco para
 * decidir se e como o dock aparece (RQ-22).
 */
final class PluginDoDock
{
    public static function make(): QuickActionDockPlugin
    {
        self::recusarNomeDeActionForjado();

        return QuickActionDockPlugin::make()
            ->authorize(DockDeAtalhos::PERMISSAO)
            ->visible(fn (): bool => DockDeAtalhos::habilitadoNoPainel(Filament::getCurrentPanel()))
            ->layout(fn (): DockLayout => DockDeAtalhos::layoutPara(self::usuario()))
            ->style(fn (): DockStyle => DockDeAtalhos::estilo())
            // Centro: os cantos inferiores direitos já têm o "voltar ao topo" e o chat (D5).
            ->position(DockPosition::BottomCenter)
            ->actions(fn (): array => self::atalhos())
            ->group('Atalhos', fn (): array => [
                self::adicionarPaginaAtual(),
                self::gerenciarAtalhos(),
            ]);
    }

    /**
     * Um link por favorito que o usuário pode abrir, na ordem gravada.
     *
     * O nome é o índice na lista visível (`atalho_0`, `atalho_1`…): a action só navega, e a URL
     * sai do servidor, de destino já autorizado no contexto dele (`DockDeAtalhos::atalhosVisiveis()`).
     *
     * @return list<Action>
     */
    private static function atalhos(): array
    {
        $usuario = self::usuario();

        if ($usuario === null) {
            return [];
        }

        $actions = [];

        foreach (DockDeAtalhos::atalhosVisiveis($usuario) as $indice => $atalho) {
            $actions[] = Action::make('atalho_'.$indice)
                ->label($atalho['rotulo'])
                ->icon($atalho['icone'] ?? Heroicon::OutlinedLink)
                ->url($atalho['url'])
                ->authorize(DockDeAtalhos::PERMISSAO);
        }

        return $actions;
    }

    /**
     * "Adicionar esta página aos atalhos" (RQ-21).
     *
     * Visível só quando a página atual é destino do catálogo e ainda não é favorita
     * (`DockDeAtalhos::paginaAtualComoAtalho()`, RQ-23, RQ-28). A visibilidade é conveniência: a
     * execução refaz a conta em `DockDeAtalhos::adicionarPaginaAtual()`, então a chamada direta de
     * uma action oculta, ou o duplo clique, não grava nada. Com 10 gravados a opção continua
     * visível e a adição é recusada com o aviso do limite (RQ-26).
     */
    private static function adicionarPaginaAtual(): Action
    {
        return Action::make('adicionarPaginaAtual')
            ->label('Adicionar esta página')
            ->icon(Heroicon::OutlinedStar)
            ->authorize(DockDeAtalhos::PERMISSAO)
            ->visible(static function (): bool {
                $usuario = self::usuario();

                return $usuario !== null && DockDeAtalhos::paginaAtualComoAtalho($usuario) !== null;
            })
            ->action(static function (): void {
                $usuario = self::usuario();

                if ($usuario === null) {
                    return;
                }

                $adicionada = DockDeAtalhos::adicionarPaginaAtual($usuario);

                if ($adicionada === false) {
                    self::avisarLimite();

                    return;
                }

                if ($adicionada === true) {
                    Notification::make()
                        ->success()
                        ->title('Página adicionada aos atalhos')
                        ->send();
                }
            });
    }

    /** O aviso de recusa pelo teto, comum à adição da página e ao slideover (RQ-26). */
    private static function avisarLimite(): void
    {
        Notification::make()
            ->warning()
            ->title('Limite de '.DockDeAtalhos::MAXIMO_DE_ATALHOS.' atalhos')
            ->body('O dock guarda no máximo '.DockDeAtalhos::MAXIMO_DE_ATALHOS.' atalhos, contando os que você não pode abrir agora. Nada foi gravado.')
            ->send();
    }

    /**
     * O slideover de gerenciamento dos favoritos (RQ-20).
     *
     * As opções são as de `DockDeAtalhos::opcoesDoSlideover()`: os favoritos gravados que o
     * usuário ainda abre, de qualquer painel (RQ-35), e o catálogo deste request (RQ-12). Calculadas
     * só quando o schema é montado — o dock fechado não avalia catálogo nenhum (RQ-22). O
     * `in` automático do `Select` já recusa chave fora das opções; `gravar()` repete a regra
     * porque é ela, e não o campo, a fronteira (RQ-29). Sem `maxItems()`: o teto conta os
     * favoritos ocultos, que não estão no formulário, e quem decide é `gravar()`. Acima do teto,
     * aviso do limite e o slideover continua aberto (`halt()`), sem nada gravado (RQ-26).
     */
    private static function gerenciarAtalhos(): Action
    {
        return Action::make('gerenciarAtalhos')
            ->label('Gerenciar atalhos')
            ->icon(Heroicon::OutlinedCog6Tooth)
            ->authorize(DockDeAtalhos::PERMISSAO)
            ->slideOver()
            ->modalHeading('Gerenciar atalhos')
            ->modalDescription('Escolha as telas que aparecem no seu dock e arraste para ordenar.')
            ->modalSubmitActionLabel('Salvar')
            ->schema(static function (): array {
                $usuario = self::usuario();

                return [
                    Select::make('atalhos')
                        ->label('Atalhos')
                        ->multiple()
                        ->reorderable()
                        ->searchable()
                        // Cada item cru precisa ser string: o cast de opções do `Select` descarta
                        // um item-lista em silêncio, e o resto do envio seria gravado (RQ-29).
                        ->nestedRecursiveRules(['string'])
                        ->options($usuario === null ? [] : DockDeAtalhos::opcoesDoSlideover($usuario)),
                    Select::make('layout')
                        ->label('Layout do dock')
                        ->options(DockDeAtalhos::opcoesDeLayout())
                        ->placeholder('Padrão da instalação'),
                ];
            })
            ->fillForm(static function (): array {
                $usuario = self::usuario();

                return [
                    'atalhos' => $usuario === null ? [] : array_column(DockDeAtalhos::atalhosVisiveis($usuario), 'chave'),
                    'layout'  => DockLayout::tryFrom((string) $usuario?->getAttribute('dock_layout'))?->value,
                ];
            })
            ->action(static function (array $data, Schema $schema, Action $action): void {
                $usuario = self::usuario();

                if ($usuario === null) {
                    return;
                }

                try {
                    $gravou = DockDeAtalhos::gravar($usuario, (array) ($data['atalhos'] ?? []), is_string($data['layout'] ?? null) ? $data['layout'] : null);
                } catch (ValidationException $recusa) {
                    // A mensagem vai para o campo do slideover, não para uma chave solta.
                    $campo = $schema->getComponent('atalhos');

                    throw ValidationException::withMessages([
                        $campo instanceof Select ? $campo->getStatePath() : 'atalhos' => $recusa->errors()['atalhos'] ?? $recusa->getMessage(),
                    ]);
                }

                if (! $gravou) {
                    self::avisarLimite();

                    $action->halt();
                }
            });
    }

    /**
     * O componente do dock recusa `mountAction` com nome que não é string.
     *
     * `QuickActionDock` é de vendor e herda `InteractsWithActions::mountAction(string $name, ...)`:
     * um cliente adulterado que mande o nome como array derruba o update com `TypeError` (500)
     * antes de qualquer autorização. Nada seria gravado, mas a superfície do dock responde sem
     * exceção ao payload forjado (Superfície Livewire do `02`, CT-05): o hook `call` do Livewire
     * (`HandleComponents::callMethods()`) devolve cedo, sem chamar o método.
     *
     * Escopado ao componente do dock, e registrado uma vez por instância da aplicação — `make()`
     * roda uma vez por painel. A marca vive no container, e não numa estática, para que cada
     * aplicação nova (teste, worker) registre o seu próprio hook.
     */
    private static function recusarNomeDeActionForjado(): void
    {
        if (app()->bound(self::class.'@hook')) {
            return;
        }

        app()->instance(self::class.'@hook', true);

        Livewire::listen('call', static function (object $componente, string $metodo, array $parametros, mixed $contexto, callable $devolverCedo): void {
            if ($componente instanceof QuickActionDock && $metodo === 'mountAction' && ! is_string($parametros[0] ?? null)) {
                $devolverCedo();
            }
        });
    }

    private static function usuario(): ?User
    {
        $usuario = Filament::auth()->user();

        return $usuario instanceof User ? $usuario : null;
    }
}

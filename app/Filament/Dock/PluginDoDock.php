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
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

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
 *
 * Posição no centro inferior: os cantos inferiores direitos já têm o "voltar ao topo" e o chat (D5).
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
     * visível e a adição é recusada com o aviso do limite (RQ-26). Quando a página já não pode ser
     * adicionada — favoritada em outra aba, ou o destino deixou de ser abrível entre o render e o
     * clique —, aviso e nada gravado (P-03). Gravou: o dock desta mesma requisição já mostra o
     * novo atalho (`recachearAtalhos()`).
     *
     * Por isso a visibilidade vale para o RENDER, não para o clique: com a action montada, ela
     * conta como visível. O Filament reavalia a visibilidade no `mountAction()` e no
     * `callMountedAction()` (`isDisabled()` → `isHidden()`,
     * `vendor/filament/actions/src/Concerns/CanBeDisabled.php:isHidden:24`) e, oculta, devolve
     * `null` calado — o aviso da P-03 nunca sairia. Não abre barreira: a autorização
     * (`Usar:Dock`) continua conferida no `isHidden()` depois da visibilidade
     * (`vendor/filament/actions/src/Concerns/CanBeHidden.php:isAuthorizedOrNotHiddenWhenUnauthorized:94`),
     * e quem decide se grava é `DockDeAtalhos::adicionarPaginaAtual()`.
     */
    private static function adicionarPaginaAtual(): Action
    {
        return Action::make('adicionarPaginaAtual')
            ->label('Adicionar esta página')
            ->icon(Heroicon::OutlinedStar)
            ->authorize(DockDeAtalhos::PERMISSAO)
            ->visible(static function (mixed $livewire): bool {
                $usuario = self::usuario();

                if ($usuario === null) {
                    return false;
                }

                return self::estaMontada($livewire, 'adicionarPaginaAtual')
                    || DockDeAtalhos::paginaAtualComoAtalho($usuario) !== null;
            })
            ->action(static function (mixed $livewire): void {
                $usuario = self::usuario();

                if ($usuario === null) {
                    return;
                }

                $adicionada = DockDeAtalhos::adicionarPaginaAtual($usuario);

                if ($adicionada === false) {
                    self::avisarLimite();

                    return;
                }

                if ($adicionada === null) {
                    Notification::make()
                        ->warning()
                        ->title('Página não adicionada')
                        ->body('Esta página já está nos seus atalhos ou não está mais disponível para você. Nada foi gravado.')
                        ->send();

                    return;
                }

                self::recachearAtalhos($livewire);

                Notification::make()
                    ->success()
                    ->title('Página adicionada aos atalhos')
                    ->send();
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
     *
     * - `nestedRecursiveRules(['string'])`: cada item cru precisa ser string — o cast de opções do
     *   `Select` descarta um item-lista em silêncio, e o resto do envio seria gravado (RQ-29).
     * - A action lê os favoritos só de `$data`, o estado validado do formulário: argumentos
     *   forjados em `mountAction()`/`callMountedAction()` não chegam a `gravar()` (CT-70).
     * - A recusa de `gravar()` vira erro no campo do slideover (o state path do `Select`), não numa
     *   chave solta.
     * - Gravou: o dock desta mesma requisição já mostra os favoritos novos, na ordem nova
     *   (`recachearAtalhos()`, CR-01).
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
            ->action(static function (array $data, Schema $schema, Action $action, mixed $livewire): void {
                $usuario = self::usuario();

                if ($usuario === null) {
                    return;
                }

                try {
                    $gravou = DockDeAtalhos::gravar($usuario, (array) ($data['atalhos'] ?? []), is_string($data['layout'] ?? null) ? $data['layout'] : null);
                } catch (ValidationException $recusa) {
                    $campo = $schema->getComponent('atalhos');

                    throw ValidationException::withMessages([
                        $campo instanceof Select ? $campo->getStatePath() : 'atalhos' => $recusa->errors()['atalhos'] ?? $recusa->getMessage(),
                    ]);
                }

                if (! $gravou) {
                    self::avisarLimite();

                    $action->halt();
                }

                self::recachearAtalhos($livewire);
            });
    }

    /** A action `$nome` está montada no componente do dock (clique em andamento)? */
    private static function estaMontada(mixed $livewire, string $nome): bool
    {
        return $livewire instanceof QuickActionDock
            && in_array($nome, array_column($livewire->mountedActions ?? [], 'name'), true);
    }

    /**
     * Troca as actions `atalho_{n}` já cacheadas no componente pelas dos favoritos recém-gravados.
     *
     * O componente cacheia as actions do dock no `hydrate()`, ANTES de a action rodar
     * (`vendor/hoceineel/filament-quick-action-dock/src/Livewire/QuickActionDock.php:cacheDockActions:51`),
     * e o render prefere a cacheada à recém-avaliada
     * (`vendor/hoceineel/filament-quick-action-dock/src/Livewire/QuickActionDock.php:cachedActions:89`):
     * sem isto, o dock re-renderizado na mesma requisição mostraria os favoritos antigos (CR-01).
     * `cacheDockActions()` é `protected`; `cacheAction()` é público
     * (`vendor/filament/actions/src/Concerns/InteractsWithActions.php:cacheAction:572`) e sobrescreve
     * pelo nome. Um `atalho_{n}` que sobrar de uma lista maior fica no cache sem ser desenhado — o
     * render só percorre os nomes da lista nova —, e o próximo request nem o cacheia.
     */
    private static function recachearAtalhos(mixed $livewire): void
    {
        if (! $livewire instanceof QuickActionDock) {
            return;
        }

        foreach (self::atalhos() as $atalho) {
            $livewire->cacheAction($atalho);
        }
    }

    /**
     * O componente do dock recusa, sem exceção, chamada pública com argumento que não cabe na
     * assinatura do método (CR-05, RD-02, CT-05, CT-70).
     *
     * `QuickActionDock` é de vendor e herda métodos públicos tipados de `InteractsWithActions`
     * (`mountAction(string $name, array $arguments = [], array $context = [])`,
     * `callMountedAction(array $arguments = [])`…). O Livewire os chama com os parâmetros do
     * payload desempacotados (`vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php:callMethods:530`),
     * então um cliente adulterado que mande texto onde a assinatura pede lista derruba o update com
     * `TypeError` (500) antes de qualquer autorização. Nada seria gravado, mas a superfície do dock
     * responde sem exceção ao payload forjado: o hook `call` do Livewire devolve cedo, sem chamar o
     * método, quando os parâmetros não cabem na assinatura REAL (`ReflectionMethod`) — e não numa
     * lista de métodos escrita aqui, que envelheceria com o vendor.
     *
     * Escopado ao componente do dock. Os parâmetros do listener não são tipados: o hook é de TODO
     * componente, e um tipo aqui lançaria `TypeError` no listener para os outros. Registrado uma vez
     * por instância da aplicação — `make()` roda uma vez por painel. A marca vive no container, e
     * não numa estática, para que cada aplicação nova (teste, worker) registre o seu próprio hook.
     */
    private static function recusarNomeDeActionForjado(): void
    {
        if (app()->bound(self::class.'@hook')) {
            return;
        }

        app()->instance(self::class.'@hook', true);

        Livewire::listen('call', static function ($componente, $metodo, $parametros, $contexto, $devolverCedo): void {
            if (! $componente instanceof QuickActionDock || ! is_callable($devolverCedo)) {
                return;
            }

            if (! is_string($metodo) || ! is_array($parametros) || ! self::cabeNaAssinatura($componente, $metodo, $parametros)) {
                $devolverCedo();
            }
        });
    }

    /**
     * Os parâmetros do payload cabem na assinatura do método público do componente?
     *
     * Método que não é público do componente fica com o Livewire (que responde
     * `MethodNotFoundException`, ou trata as ações mágicas como `$refresh`): aqui só se confere o
     * que ele chamaria. Parâmetro por nome inexistente, obrigatório ausente ou valor fora do tipo
     * declarado não cabem. A conferência é estrita, como o payload: JSON não manda `42` onde o
     * cliente legítimo manda `"42"`.
     *
     * @param  array<mixed>  $parametros
     */
    private static function cabeNaAssinatura(QuickActionDock $componente, string $metodo, array $parametros): bool
    {
        if (! method_exists($componente, $metodo)) {
            return true;
        }

        $assinatura = new ReflectionMethod($componente, $metodo);

        if (! $assinatura->isPublic()) {
            return true;
        }

        $declarados = $assinatura->getParameters();
        $porNome    = [];

        foreach ($declarados as $declarado) {
            $porNome[$declarado->getName()] = $declarado;
        }

        $recebidos = [];

        foreach ($parametros as $chave => $valor) {
            $declarado = is_int($chave) ? ($declarados[$chave] ?? null) : ($porNome[$chave] ?? null);

            if ($declarado === null) {
                if (is_int($chave) || $assinatura->isVariadic()) {
                    continue;
                }

                return false;
            }

            if (! self::cabeNoTipo($declarado->getType(), $valor)) {
                return false;
            }

            $recebidos[$declarado->getName()] = true;
        }

        foreach ($declarados as $declarado) {
            if (! $declarado->isOptional() && ! isset($recebidos[$declarado->getName()])) {
                return false;
            }
        }

        return true;
    }

    /** O valor cabe no tipo declarado? Sem tipo, `mixed` ou tipo de interseção, cabe. */
    private static function cabeNoTipo(?ReflectionType $tipo, mixed $valor): bool
    {
        if ($tipo === null) {
            return true;
        }

        if ($valor === null) {
            return $tipo->allowsNull();
        }

        $tipos = $tipo instanceof ReflectionUnionType ? $tipo->getTypes() : [$tipo];

        foreach ($tipos as $umTipo) {
            if (! $umTipo instanceof ReflectionNamedType) {
                return true;
            }

            $nome = $umTipo->getName();

            $cabe = match ($nome) {
                'mixed'             => true,
                'string'            => is_string($valor),
                'int'               => is_int($valor),
                'float'             => is_float($valor) || is_int($valor),
                'bool'              => is_bool($valor),
                'true'              => $valor === true,
                'false'             => $valor === false,
                'array', 'iterable' => is_array($valor),
                default             => $valor instanceof $nome,
            };

            if ($cabe) {
                return true;
            }
        }

        return false;
    }

    private static function usuario(): ?User
    {
        $usuario = Filament::auth()->user();

        return $usuario instanceof User ? $usuario : null;
    }
}

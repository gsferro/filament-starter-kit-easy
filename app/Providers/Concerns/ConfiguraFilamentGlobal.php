<?php

namespace App\Providers\Concerns;

use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Livewire\DatabaseNotifications;
use Filament\Resources\Resource;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Icons\Heroicon;
use Filament\Support\View\Components\ModalComponent;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\ColumnManagerLayout;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

/**
 * Configuração GLOBAL do Filament — vale para os três painéis (app, admin, infra).
 *
 * Mora aqui, e não num plugin de painel, porque `configureUsing()` é estático do
 * container: registrar em cada PanelProvider daria a impressão de config por painel
 * com comportamento global (o último registro venceria). O Panel Switch segue a
 * mesma regra — o pacote não é plugin de painel, registra um render hook global.
 *
 * É este arquivo que define como TODA tabela, toggle e modal do projeto se comporta.
 * Mudou aqui, mudou em todo lugar — inclusive nas telas dos plugins de terceiros.
 *
 * TODO: transformar estes defaults num Settings editável em `/admin`
 *       (filament/spatie-laravel-settings-plugin já está instalado). A ideia é
 *       que paginação, densidade da tabela, persistência de filtros e colunas
 *       redimensionáveis virem preferência do projeto pela UI, sem editar código.
 */
trait ConfiguraFilamentGlobal
{
    protected function configuraFilamentGlobal(): void
    {
        $this->isolarScriptsConflitantes();

        // Modal só fecha no botão: um Esc acidental no meio de um formulário
        // longo descarta o preenchimento sem confirmação.
        /*
         * Title case FORA — ele é regra do inglês, não do português.
         *
         * `Resource::getNavigationLabel()` cai em `getTitleCasePluralModelLabel()`
         * (HasNavigation.php:142), que aplica `Str::ucwords()` ao label plural — e isso
         * capitaliza TODA palavra, preposição inclusive. Num painel em pt-BR o resultado é
         * "Agentes **De** IA", "Logs **De** Autenticação", "Pacotes **Do** Composer": errado no
         * menu, no breadcrumb e no <h1>, porque os três saem do mesmo lugar.
         *
         * Desligado aqui e não resource a resource: a chave é estática e vale para TODOS,
         * inclusive os Resources de plugin de terceiro, que é onde o kit não tem como declarar
         * label. Quem quiser o label exato continua declarando `$navigationLabel` — esta chave
         * só para de MEXER no que já foi escrito.
         */
        Resource::titleCaseModelLabel(false);

        ModalComponent::closedByEscaping(false);

        // Fallback do sininho. Os painéis zeram o intervalo quando o broadcast
        // é Reverb (tempo real de verdade, sem polling).
        DatabaseNotifications::pollingInterval('30s');

        Toggle::configureUsing(fn (Toggle $toggle): Toggle => $this->configuraToggle($toggle));
        ToggleColumn::configureUsing(fn (ToggleColumn $toggle): ToggleColumn => $this->configuraToggle($toggle));
        IconColumn::configureUsing(fn (IconColumn $coluna): IconColumn => $this->configuraIconColumn($coluna));
        CreateAction::configureUsing(fn (CreateAction $acao): CreateAction => $acao->icon(Heroicon::OutlinedPlusCircle));
        Table::configureUsing(fn (Table $table): Table => $this->configuraTable($table));

        $this->configuraPanelSwitch();
        $this->configuraSeletorDeIdioma();
    }

    /**
     * Seletor de idioma nos três painéis e nas telas de autenticação.
     *
     * Global e não por painel pelo mesmo motivo do Panel Switch acima: o pacote não é
     * plugin de painel, registra um render hook global no `BODY_END`. Configurar em
     * cada provider daria aparência de config por painel com efeito global.
     *
     * **Dirigido por dado, sem flag.** Com um idioma só em `config('kit.idiomas')` — que é
     * como o kit nasce — não há para onde trocar, e o `visible(false)` some com o botão.
     * Quem quer a feature declara o segundo locale; ninguém esquece um booleano ligado.
     *
     * O que o seletor NÃO faz: traduzir o kit. A cobertura é da camada do Filament e dos
     * pacotes (laravel-lang/common). Os rótulos do próprio kit são strings pt-BR escritas
     * no código — há dez `__()` em todo o app. Ligar `en` hoje troca metade da tela.
     * Está declarado em `config/kit.php` e em wikis/pacotes-ranking.md.
     */
    private function configuraSeletorDeIdioma(): void
    {
        LanguageSwitch::configureUsing(function (LanguageSwitch $seletor): void {
            /*
             * A leitura da config fica DENTRO da closure, e isso não é estilo.
             *
             * Lida fora, ela seria avaliada uma vez no boot do provider e capturada por
             * valor — o seletor passaria a exibir a lista que existia naquele instante,
             * não a que o request tem. É o mesmo motivo pelo qual os painéis passam
             * `fn (): array => CorPrimaria::paleta()` em vez do array pronto.
             *
             * Foi um defeito real: a primeira versão capturava `$idiomas` por `use`, e o
             * caso "mostra o seletor quando há um segundo idioma" reprovou.
             */
            /** @var list<string> $idiomas */
            $idiomas = array_values(array_filter((array) config('kit.idiomas', [])));

            $seletor
                ->locales($idiomas)
                // `displayLocale(null)` = cada idioma aparece escrito NELE MESMO
                // ("Português", "English"), e não traduzido para o idioma corrente.
                // Quem procura o próprio idioma numa lista o reconhece assim.
                ->displayLocale(null)
                ->circular()
                // Também fora do painel: a tela de login é justamente onde alguém que
                // não lê português precisa trocar, e ela é servida antes da sessão.
                ->outsidePanelRoutes([
                    'filament.app.auth.login',
                    'filament.admin.auth.login',
                    'filament.infra.auth.login',
                ])
                /*
                 * Dentro do painel, o `isVisible()` do pacote já exige
                 * `count(getLocales()) > 1` — a checagem aqui é redundante e fica por
                 * simetria. FORA do painel não há essa proteção: `isVisibleOutsidePanels()`
                 * só avalia a flag. Sem a contagem, a tela de login mostraria um seletor
                 * com uma opção só.
                 */
                ->visible(insidePanels: count($idiomas) > 1, outsidePanels: count($idiomas) > 1);
        });
    }

    /**
     * Padrão de TODA tabela do projeto.
     *
     * Colunas redimensionáveis, reordenáveis e fixáveis (asmit/resized-column)
     * entram aqui para valer também nas tabelas dos plugins de terceiros — não
     * há como editar o `table()` de um resource de vendor.
     *
     * A largura escolhida pelo usuário só é lembrada nas telas que usam o trait
     * `Asmit\ResizedColumn\HasResizableColumn` (ver README, "Convenções do kit").
     */
    private function configuraTable(Table $table): Table
    {
        $table = $table
            // Carrega os dados de forma assíncrona: a tela aparece antes da query.
            ->deferLoading()
            ->striped()

            // O recorte do usuário sobrevive à navegação.
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()

            // Colunas: reordenar pelo gerenciador (nativo). Arrastar e fixar são
            // macros do resized-column, aplicadas logo abaixo.
            ->reorderableColumns()
            ->columnManagerLayout(ColumnManagerLayout::Modal)

            /*
             * Filtro em modal de 2 colunas: com 3+ filtros o dropdown estreito
             * vira rolagem. O gatilho continua sendo o botão "Filtros", então o
             * número de cliques não muda — muda o contêiner que abre.
             */
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->deferFilters()

            /*
             * Sem `filtersTriggerAction()`/`filtersApplyAction()`/`filtersRemoveAllAction()`
             * aqui, e isso foi medido: num `configureUsing()` global elas atingem também as
             * tabelas SEM filtro (as dos plugins de terceiros), onde a ação nasce sem nome e
             * a página inteira morre com
             * `LogicException: Action of class [Filament\Actions\Action] must have a unique
             * name`. Oito telas do painel infra caíam em 500 por causa disso.
             *
             * Os rótulos padrão já vêm em pt-BR pelas traduções do Filament; se quiser
             * customizar, faça no `table()` do resource, onde os filtros existem de fato.
             */

            // No celular a linha vira cartão em vez de rolar na horizontal.
            ->stackedOnMobile()

            // Filtrar não desmarca o que já estava selecionado.
            ->deselectAllRecordsWhenFiltered(false)

            ->defaultPaginationPageOption(10)
            ->extremePaginationLinks();

        return $this->aplicaMacrosDeColuna($table);
    }

    /**
     * Arrastar colunas (asmit/resized-column).
     *
     * São `Table::macro()` registradas em runtime pelo ServiceProvider do pacote:
     * invisíveis para a análise estática e inexistentes se o pacote for removido.
     * O `hasMacro()` faz as duas coisas de uma vez — degrada sem quebrar a tabela
     * e dispensa fingir para o PHPStan que o método existe.
     *
     * `stickableColumns` ficou de fora de propósito: ele acrescenta um botão
     * "fixar colunas" em TODA tabela do projeto, e o gerenciador de colunas —
     * que já está ali ao lado, no mesmo cabeçalho — resolve organizar a tabela
     * sozinho. Dois botões para o mesmo objetivo é escolha a mais na cara de
     * quem só quer ver a listagem. Para trazer de volta, acrescente
     * `'stickableColumns'` à lista abaixo.
     */
    private function aplicaMacrosDeColuna(Table $table): Table
    {
        foreach (['dragReorderableColumns'] as $macro) {
            if (Table::hasMacro($macro)) {
                $table = $table->{$macro}();
            }
        }

        return $table;
    }

    /**
     * O mesmo par de cor e ícone no Toggle de formulário e na ToggleColumn de tabela.
     *
     * Genérico, e não dois métodos: cada `configureUsing()` exige de volta EXATAMENTE o tipo
     * que entregou (o do Toggle devolve `Toggle`, o da ToggleColumn devolve `ToggleColumn`).
     * Um retorno `Toggle|ToggleColumn` cru não satisfaz nenhum dos dois — o `@template`
     * amarra saída à entrada e mantém um método só.
     *
     * @template T of Toggle|ToggleColumn
     *
     * @param  T  $toggle
     * @return T
     */
    private function configuraToggle(Toggle|ToggleColumn $toggle): Toggle|ToggleColumn
    {
        return $toggle
            ->onColor('success')
            ->offColor('danger')
            ->onIcon(Heroicon::OutlinedHandThumbUp)
            ->offIcon(Heroicon::OutlinedHandRaised);
    }

    /** Coluna booleana ganha check/x com cor, sem repetir isso em cada resource. */
    private function configuraIconColumn(IconColumn $coluna): IconColumn
    {
        return $coluna->isBoolean()
            ? $coluna
                ->trueIcon(Heroicon::OutlinedCheckCircle)
                ->falseIcon(Heroicon::OutlinedXCircle)
                ->trueColor('success')
                ->falseColor('danger')
            : $coluna;
    }

    private function configuraPanelSwitch(): void
    {
        /*
         * Não implemente canSwitchPanels() no User: o nome engana. Ele não
         * esconde painel nenhum — só mapeia as URLs para null e deixa a lista
         * renderizada. O recorte real é o canAccessPanel(), que o próprio pacote
         * consulta; painel inacessível some sozinho.
         */
        PanelSwitch::configureUsing(function (PanelSwitch $panelSwitch): void {
            $panelSwitch
                ->simple()
                ->labels([
                    'app'   => config('app.name'),
                    'admin' => 'Administração',
                    'infra' => 'Infraestrutura',
                ])
                ->icons([
                    'app'   => 'heroicon-o-rocket-launch',
                    'admin' => 'heroicon-o-wrench-screwdriver',
                    'infra' => 'heroicon-o-server-stack',
                ]);
        });
    }

    /**
     * Carrega os bundles conflitantes como ES module.
     *
     * O script do Pulse (dotswan) e o do resized-column não são encapsulados:
     * cada um declara constantes no escopo global. O Filament injeta os dois na
     * mesma página e o segundo morre inteiro com
     * `SyntaxError: Identifier '$e' has already been declared` — sem erro na
     * tela, só a funcionalidade sumindo (foi assim que os gráficos do Pulse
     * pararam de renderizar).
     *
     * `type="module"` dá escopo próprio a cada um. A mutação é feita nos objetos
     * JÁ registrados porque `FilamentAsset::register()` ACUMULA em vez de
     * substituir: registrar de novo carregaria o mesmo arquivo duas vezes e
     * recriaria o conflito.
     */
    private function isolarScriptsConflitantes(): void
    {
        $conflitantes = ['filament-laravel-pulse', 'resized-column'];

        foreach (FilamentAsset::getScripts() as $script) {
            if (in_array($script->getId(), $conflitantes, true)) {
                $script->module();
            }
        }
    }
}

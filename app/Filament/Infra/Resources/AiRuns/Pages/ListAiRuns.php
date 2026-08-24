<?php

namespace App\Filament\Infra\Resources\AiRuns\Pages;

use App\Filament\Exports\AiRunExporter;
use App\Filament\Infra\Resources\AiRuns\AiRunResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Grid do ledger. Sem CreateAction — execução é gravada pelo sistema, nunca criada à mão.
 */
class ListAiRuns extends ListRecords
{
    use HasResizableColumn;

    protected static string $resource = AiRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            /*
             * Dashboard do fomvasss/laravel-ai-tasks (rota do pacote, fora do Filament).
             *
             * ## Este link NÃO leva `->visible()`, e a ausência é decisão medida
             *
             * A varredura da wiki `permissoes-de-telas-e-acoes` listou este botão como furo de
             * affordance ("aparece para quem abre a listagem e leva a um 403"). **Não é.**
             * `AiRunResource::canAccess()` é literalmente `Auth::user()?->can('ver-ai-tasks')`
             * (`AiRunResource.php:81-84`), o MESMO gate que protege a rota de destino
             * (`KitServiceProvider@configureGates`). Quem chega a esta tela já passou no gate do
             * destino — as duas condições são a mesma expressão.
             *
             * Um `->visible(fn () => Gate::allows('ver-ai-tasks'))` aqui seria no-op: não existe
             * persona que abra a listagem e falhe no gate, então a linha não poderia sequer ser
             * falsificada por um caso de teste. Ver a hipótese rejeitada no `03-progresso.md` da
             * wiki, e CT-20/CT-31 de `tests/Kit/PermissoesDeAcoesTest.php`, que afirmam a
             * propriedade real: a tela inteira responde 403 para quem não passa no gate.
             *
             * O `NavigationItem` irmão em `InfraPanelProvider` é caso diferente e MANTÉM o
             * `->visible()`: item de menu é renderizado fora do Resource, então lá a checagem não é
             * redundante.
             */
            Action::make('dashboardAiTasks')
                ->label('Dashboard de estatísticas')
                ->icon('heroicon-o-chart-bar')
                ->url(fn (): string => route('ai-tasks.index'))
                ->openUrlInNewTab(),

            /*
             * Export sim, import não: ledger é escrito pelo sistema, e importar execução
             * seria falsificar custo. As colunas de `request`/`response` ficam FORA do
             * exporter — ver `AiRunExporter`.
             */
            ExportAction::make()
                ->exporter(AiRunExporter::class)
                ->authorize('export'),
        ];
    }
}

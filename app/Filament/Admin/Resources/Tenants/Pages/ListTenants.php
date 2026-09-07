<?php

namespace App\Filament\Admin\Resources\Tenants\Pages;

use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Filament\Admin\Resources\Tenants\Widgets\AcessosPorPainel;
use App\Filament\Admin\Resources\Tenants\Widgets\AtualizacoesDasOrganizacoes;
use App\Filament\Admin\Resources\Tenants\Widgets\OrganizacoesStats;
use App\Filament\Admin\Resources\Tenants\Widgets\UsuariosUnicosPorOrganizacao;
use App\Filament\Exports\TenantExporter;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListTenants extends ListRecords
{
    use HasResizableColumn;

    protected static string $resource = TenantResource::class;

    /**
     * Export sim, import **não** — e a ausência é decisão, não esquecimento.
     *
     * Criar organização por CSV pula o fluxo de provisionamento: papéis por tenant,
     * primeiro administrador, identidade visual. Uma linha de planilha viraria uma
     * organização sem ninguém que a alcance.
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Novo registro'),

            ExportAction::make()
                ->exporter(TenantExporter::class)
                ->authorize('export'),
        ];
    }

    /**
     * A visão geral fica ACIMA da tabela; os três widgets de detalhe, ABAIXO dela.
     *
     * A tabela é o que se opera nesta tela — os números de detalhe se leem depois, então só o
     * agregado abre a página. O template do Filament é linear (cabeçalho → conteúdo → rodapé),
     * então a divisão entre este método e `getFooterWidgets()` é a posição na tela.
     *
     * `Page::getWidgetsSchemaComponents()` filtra por `canView()` antes de montar o grid
     * (`Page.php:427`) nas DUAS listas, então widget cuja fonte não existe some sem deixar buraco
     * — não é preciso condicionar nada aqui.
     *
     * Eles vivem em `Resources/Tenants/Widgets/` e NÃO em `app/Filament/Admin/Widgets/`, que é o
     * diretório do `discoverWidgets()`: a descoberta é recursiva e o `Dashboard` renderiza todo
     * widget registrado no painel. Ver ADR-03 de
     * `wikis/specs/main/insights-das-organizacoes/`.
     *
     * @return array<int, class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            OrganizacoesStats::class,
        ];
    }

    /**
     * @return array<int, class-string>
     */
    protected function getFooterWidgets(): array
    {
        return [
            UsuariosUnicosPorOrganizacao::class,
            AcessosPorPainel::class,
            AtualizacoesDasOrganizacoes::class,
        ];
    }
}

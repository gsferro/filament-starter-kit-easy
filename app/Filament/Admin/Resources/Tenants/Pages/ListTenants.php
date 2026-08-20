<?php

namespace App\Filament\Admin\Resources\Tenants\Pages;

use App\Filament\Admin\Resources\Tenants\TenantResource;
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
}

<?php

namespace App\Filament\Admin\Resources\Tenants\Pages;

use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Filament\Admin\Schemas\EditorDeAparenciaDasTelas;
use App\Models\Tenant;
use App\Support\AparenciaDasTelasDeAcesso;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    /** O editor nasce com a cópia da padrão, para quando o toggle for desligado (P-07). */
    protected function afterFill(): void
    {
        $this->data['aparencia_das_telas'] = EditorDeAparenciaDasTelas::paraOFormulario(AparenciaDasTelasDeAcesso::global());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return EditorDeAparenciaDasTelas::paraGravarNaOrganizacao($data, $this->data);
    }

    /** Trilha no canal `tenancy`: criar um tenant é criar uma fronteira de dados. */
    protected function afterCreate(): void
    {
        $record = $this->record;

        if (! $record instanceof Tenant) {
            return;
        }

        Log::channel('tenancy')->info(
            "[CreateTenant@afterCreate] Tenant criado | slug: {$record->slug}",
            ['tenant_id' => $record->id, 'executor_id' => Auth::id()],
        );
    }
}

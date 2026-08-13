<?php

namespace App\Filament\Admin\Resources\Tenants\Pages;

use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Models\Tenant;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

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

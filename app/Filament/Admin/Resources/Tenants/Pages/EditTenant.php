<?php

namespace App\Filament\Admin\Resources\Tenants\Pages;

use App\Filament\Admin\Resources\Tenants\TenantResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Sem DeleteAction: apagar um tenant levaria em cascata todos os dados de
 * negócio dele. A "exclusão" é a flag `ativo`, no próprio formulário.
 */
class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;
}

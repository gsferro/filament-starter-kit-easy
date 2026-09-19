<?php

namespace App\Filament\Admin\Resources\Tenants\Pages;

use App\Filament\Admin\Resources\Tenants\TenantResource;
use Filament\Resources\Pages\EditRecord;
use MortalKiller\FilamentPageHeader\Concerns\HasPageHeader;

/**
 * Sem DeleteAction: apagar um tenant levaria em cascata todos os dados de
 * negócio dele. A "exclusão" é a flag `ativo`, no próprio formulário.
 */
class EditTenant extends EditRecord
{
    /*
     * Cabecalho rico. O schema vem por CONVENCAO do pacote, a partir do MODEL do resource
     * (`vendor/mortalkiller/filament-page-header/src/Concerns/HasPageHeader.php:getPageHeaderSchemaClass:65-66`)
     * — nada a declarar aqui.
     *
     * O trait sobrescreve `getHeader()`. Declarar esse metodo nesta classe tornaria o pacote
     * INERTE, sem erro nenhum (`vendor/mortalkiller/filament-page-header/docs/specification.md:15`:
     * "a page getHeader override wins").
     */
    use HasPageHeader;

    protected static string $resource = TenantResource::class;
}

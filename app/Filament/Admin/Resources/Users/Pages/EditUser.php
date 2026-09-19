<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use MortalKiller\FilamentPageHeader\Concerns\HasPageHeader;

class EditUser extends EditRecord
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

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\App\Resources\Users\Pages;

use App\Filament\App\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Sem `getHeaderActions()` — o gerador inclui um `DeleteAction` ali por default, e
 * excluir usuário a partir de uma organização é proibido (ADR-08). A trava de verdade é
 * `UserResource::canDelete()`; a ausência aqui é para não haver superfície.
 */
class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;
}

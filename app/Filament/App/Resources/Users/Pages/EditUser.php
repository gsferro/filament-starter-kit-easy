<?php

namespace App\Filament\App\Resources\Users\Pages;

use App\Filament\App\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;
use MortalKiller\FilamentPageHeader\Concerns\HasPageHeader;

/**
 * Sem `getHeaderActions()` — o gerador inclui um `DeleteAction` ali por default, e excluir
 * usuário a partir de uma organização é proibido (ADR-08).
 *
 * ## A trava é `UserResource::getDeleteAuthorizationResponse()`
 *
 * Este comentário já disse que a trava era `UserResource::canDelete()`, e era falso: no
 * Filament v5 aquele método não autoriza nada — a `DeleteAction` resolve por
 * `getDeleteAuthorizationResponse()` (`Resources/Pages/Page.php:313`) e a `DeleteBulkAction`
 * por `getDeleteAnyAuthorizationResponse()` (`:329`). Quem apontou foi a auditoria do
 * Filament Blueprint (achado F-01).
 *
 * A ausência de `getHeaderActions()` aqui continua valendo como camada a mais, mas ela é
 * ausência de SUPERFÍCIE, não autorização — e o gerador a desfaz sozinho no próximo
 * `make:filament-resource`. Por isso a trava tem de estar no resource, e estar no método que
 * o framework consulta.
 */
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
}

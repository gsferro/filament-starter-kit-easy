<?php

declare(strict_types=1);

namespace App\Filament\Infra\Resources\ComposerReleasePackages\Pages;

use App\Filament\Infra\Resources\ComposerReleasePackages\ComposerReleasePackageResource;
use MominAlZaraa\FilamentComposerReleaseNotifier\Filament\Resources\Pages\ListComposerReleasePackages as PaginaDoPacote;

/**
 * A página do pacote apontando para o resource do KIT — é a única linha que muda, e é a que autoriza.
 *
 * `CanAuthorizeResourceAccess::authorizeResourceAccess()` chama `static::getResource()::canAccess()`.
 * Com o `$resource` do vendor, a página autorizaria pela classe que tem
 * `$shouldSkipAuthorization = true`, e a subclasse do resource não valeria nada. Ver o docblock do
 * resource.
 */
class ListComposerReleasePackages extends PaginaDoPacote
{
    protected static string $resource = ComposerReleasePackageResource::class;
}

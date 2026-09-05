<?php

declare(strict_types=1);

namespace App\Filament\Infra\Resources\ComposerReleasePackages;

use App\Filament\Concerns\BadgeContagemNavegacao;
use App\Filament\Infra\Resources\ComposerReleasePackages\Pages\ListComposerReleasePackages;
use MominAlZaraa\FilamentComposerReleaseNotifier\Filament\Resources\ComposerReleasePackageResource as ResourceDoPacote;

/**
 * O resource do pacote, com a autorização LIGADA.
 *
 * ## Por que existe
 *
 * `MominAlZaraa\...\ComposerReleasePackageResource` declara
 * `protected static bool $shouldSkipAuthorization = true;` — o pacote pula policy de propósito.
 * O kit tem `ComposerReleasePackageSnapshotPolicy`, o Shield gera
 * `ViewAny:ComposerReleasePackageSnapshot`, a checkbox aparece em `/admin/shield/roles`, e nada
 * disso decidia: `Resource::getAuthorizationResponse()` devolve `allow()` antes de olhar a policy
 * quando a flag está ligada (`HasAuthorization.php:35-37`). A auditoria de aderência ao Blueprint
 * mediu o índice abrindo com a permissão revogada.
 *
 * ## Por que subclasse, e por que a página vem junto
 *
 * O plugin registra o resource por `discoverResources()` num diretório fixo do vendor e não expõe
 * callback para trocar a classe — o mesmo motivo pelo qual o widget dele já é subclasse do kit
 * (`App\Filament\Infra\Widgets\ComposerReleaseOverviewWidget`). Então o plugin fica com
 * `->resource(enabled: false)` e este arquivo entra pelo `discoverResources()` do painel.
 *
 * A página TEM de vir junto: `ListComposerReleasePackages` do pacote declara
 * `$resource = ResourceDoPacote::class`, e é `static::getResource()::canAccess()` que
 * `CanAuthorizeResourceAccess` consulta (`:19`). Herdar só o resource deixaria a página autorizando
 * pela classe do vendor — a que pula.
 *
 * Mesmo `class_basename`, então as chaves de permissão não mudam: elas derivam do MODELO
 * (`ComposerReleasePackageSnapshot`), não do resource.
 */
class ComposerReleasePackageResource extends ResourceDoPacote
{
    /*
     * Sem `insteadof`: o resource do pacote estende `Filament\Resources\Resource` puro e não
     * declara nenhum método de badge, então não há colisão. Contraste com `RoleResource`.
     */
    use BadgeContagemNavegacao;

    protected static bool $shouldSkipAuthorization = false;

    public static function getPages(): array
    {
        return [
            'index' => ListComposerReleasePackages::route('/'),
        ];
    }
}

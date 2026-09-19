<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use MortalKiller\FilamentPageHeader\Concerns\HasPageHeader;

/**
 * A ficha somente-leitura de uma conta no painel admin.
 *
 * ## Por que a classe existe, alem de mostrar a ficha
 *
 * Sem ela, o `ViewAction` da tabela abre MODAL em vez de navegar:
 * `Resources\Pages\Page::getDefaultActionUrl()` so devolve URL quando `hasPage('view')` e
 * verdadeiro (`vendor/filament/filament/src/Resources/Pages/Page.php:382-389`). E o mesmo motivo
 * que o docblock de `ViewTenant` registra.
 *
 * ## Autorizacao: nada a declarar aqui, e isso e verificado
 *
 * `ViewRecord::mount():71` chama `authorizeAccess()`, que e
 * `abort_unless(static::getResource()::canView($this->getRecord()), 403)`
 * (`ViewRecord.php:authorizeAccess:80`) — e a cadeia termina em `UserPolicy::view():17-20`, que
 * consulta `can('View:User')`. `ViewRecord::hydrate():85` repete a checagem a cada hidratacao
 * Livewire, entao revogar a permissao com a aba aberta fecha a tela.
 *
 * Nada de `canAccess()` e nada de `ExigePermissaoDaTela`: aquele trait e para Page de PAINEL, e
 * pagina de Resource autoriza pela policy do Resource. Ver `.ai/rules/filament.md`.
 *
 * ## O cabecalho
 *
 * O schema vem por convencao do pacote a partir do MODEL
 * (`vendor/mortalkiller/filament-page-header/src/Concerns/HasPageHeader.php:getPageHeaderSchemaClass:65-66`).
 * NAO declare `getHeader()` nesta classe: o trait sobrescreve exatamente esse metodo, e a classe
 * venceria o trait tornando o pacote inerte, sem erro nenhum.
 */
class ViewUser extends ViewRecord
{
    use HasPageHeader;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sem `->authorize()` a mais: `EditAction` resolve por
            // `getEditAuthorizationResponse()` do resource (`Resources/Pages/Page.php:314`), que
            // no /app ja nega quem governa a instalacao.
            EditAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\Tenants\Pages;

use App\Filament\Admin\Resources\Tenants\TenantResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Leitura do registro da organização, incluindo a identidade visual.
 *
 * Existe por dois motivos, e o segundo é o que a torna necessária e não cosmética:
 *
 * 1. Dá um lugar para ler sem risco de gravar — útil quando a organização tem logo e cor, que
 *    são as informações que alguém confere sem querer alterar.
 * 2. **É o que faz o `ViewAction` da tabela navegar em vez de abrir modal.**
 *    `Resources\Pages\Page::getDefaultActionUrl()` só devolve URL para o `ViewAction` quando
 *    `hasPage('view')` é verdadeiro (`vendor/filament/filament/src/Resources/Pages/Page.php:382-389`);
 *    sem esta classe, a action cairia em modal — exatamente o que a wiki
 *    `identidade-visual-da-organizacao` foi pedida para evitar.
 *
 * Sem `DeleteAction`, pelo mesmo motivo do `EditTenant`: apagar uma organização levaria em
 * cascata os dados de negócio dela. A "exclusão" é a flag `ativo`.
 */
class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

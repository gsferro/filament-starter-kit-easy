<?php

namespace App\Filament\Admin\Resources\Tenants\Pages;

use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Filament\Admin\Schemas\EditorDeAparenciaDasTelas;
use App\Support\AparenciaDasTelasDeAcesso;
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
     * (`vendor/mortalkiller/filament-page-header/src/Concerns/HasPageHeader.php:getPageHeaderSchemaClass:65`)
     * — nada a declarar aqui.
     *
     * O trait sobrescreve `getHeader()`. Declarar esse metodo nesta classe tornaria o pacote
     * INERTE, sem erro nenhum (`vendor/mortalkiller/filament-page-header/docs/specification.md:15`:
     * "a page getHeader override wins").
     */
    use HasPageHeader;

    protected static string $resource = TenantResource::class;

    /**
     * A coluna `aparencia_das_telas` vira o par toggle + editor: `null` = usa a padrão, e o editor
     * já nasce com a cópia da padrão para quando o toggle for desligado (P-07).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $propria = $data['aparencia_das_telas'] ?? null;

        $data['usar_aparencia_padrao'] = $propria === null;
        $data['aparencia_das_telas']   = EditorDeAparenciaDasTelas::paraOFormulario($propria ?? AparenciaDasTelasDeAcesso::global());

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return EditorDeAparenciaDasTelas::paraGravarNaOrganizacao($data, $this->data);
    }
}

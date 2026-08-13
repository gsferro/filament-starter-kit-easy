<?php

namespace App\Filament\App\Resources\Projetos\Pages;

use App\Filament\App\Resources\Projetos\ProjetoResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Resources\Pages\ListRecords;

/**
 * DEMONSTRAÇÃO — descartável junto com o resto da demo.
 *
 * Listagem com formulário em modal (o resource não declara páginas create/edit),
 * que é o formato mais direto para um cadastro de uma coluna só.
 */
class ListProjetos extends ListRecords
{
    use HasResizableColumn;

    protected static string $resource = ProjetoResource::class;
}

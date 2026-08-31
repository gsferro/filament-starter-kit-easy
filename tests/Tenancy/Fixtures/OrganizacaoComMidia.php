<?php

namespace Tests\Tenancy\Fixtures;

use App\Models\Tenant;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Fixture: um model que NÃO é `Projeto` e tem as duas naturezas de coleção — uma que
 * declara disco público (identidade visual) e uma que não declara nada (cai no default).
 *
 * Herda de `Tenant` pela tabela: a media library só precisa de `model_type` e `model_id`,
 * e criar migration de teste para isso seria carregar schema para nada.
 *
 * Vive em arquivo próprio porque classe nomeada dentro de um `*Test.php` sem namespace
 * viola o PSR-4 de `Tests\` e faz o `composer create-project` avisar
 * "does not comply with psr-4 autoloading standard".
 */
class OrganizacaoComMidia extends Tenant implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'tenants';

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('identidade')->useDisk('public');
        $this->addMediaCollection('documentos');
    }
}

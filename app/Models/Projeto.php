<?php

namespace App\Models;

use App\Traits\AuditsFillables;
use App\Traits\BelongsToTenant;
use App\Traits\ModeloCacheavel;
use App\Traits\TemUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use Promethys\Revive\Concerns\Recyclable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * DEMONSTRAÇÃO — criada por `php artisan kit:tenancy --demo`.
 *
 * Existe para PROVAR o isolamento entre tenants numa tela de verdade, não para
 * ser feature do kit. É descartável: apague este arquivo, o resource em
 * `app/Filament/App/Resources/Projetos/`, a migration `*_create_projetos_table`
 * e o `DemoTenancySeeder`.
 *
 * Serve também de exemplo canônico do que uma model de negócio precisa em modo
 * multi-tenant:
 *
 *   - `BelongsToTenant`     → relação, escopo global e preenchimento de tenant_id
 *   - `TemUuid`             → uuid na rota
 *   - `AuditsFillables`     → trilha do que é editável
 *   - `SoftDeletes`         → apagar é reversível, e a Lixeira do /infra restaura
 *   - `InteractsWithMedia`  → anexos pelo spatie/laravel-medialibrary
 *   - `tenant_id` FORA do $fillable — quem preenche é a trait
 */
class Projeto extends Model implements Auditable, HasMedia
{
    use AuditsFillables;
    use BelongsToTenant;
    use InteractsWithMedia;
    use ModeloCacheavel;

    /**
     * Sem esta trait a Lixeira lista VAZIO: a tela lê `recycle_bin_items`, e quem grava a linha
     * ali é o evento `deleted` dela (`vendor/promethys/revive/src/Concerns/Recyclable.php:29-45`).
     * Este model ficou só com `SoftDeletes` da 0.17.0 até a feature de status do usuário, e a
     * Lixeira nunca mostrou um projeto. Guarda: `tests/Kit/LixeiraTest.php`.
     */
    use Recyclable;

    /**
     * Apagar aqui é reversível — e é o que dá conteúdo à Lixeira do /infra
     * (`promethys/revive`, registrado no InfraPanelProvider).
     *
     * Duas consequências que valem para QUALQUER model que ganhe a trait:
     *
     * 1. `delete()` passa a gravar `deleted_at` em vez de remover a linha. Índice
     *    único continua ocupado pelo registro apagado — se `projetos` tivesse um
     *    unique em `nome`, não daria para recriar com o mesmo nome.
     * 2. O registro some das queries por causa do escopo global do `SoftDeletes`,
     *    que roda ANTES do escopo de tenant do `BelongsToTenant`. Os dois convivem;
     *    a ordem não importa porque ambos são `where`.
     *
     * Model nova com esta trait precisa entrar na lista `models()` do `RevivePlugin` E usar
     * `Recyclable` (abaixo), senão fica apagada sem tela para restaurar.
     */
    use SoftDeletes;

    use TemUuid;

    protected $fillable = [
        'nome',
    ];

    /**
     * Anexos do projeto — a demonstração da camada de mídia do kit.
     *
     * `singleFile()` NÃO é usado de propósito: o caso interessante de um anexo é o
     * múltiplo, e é ele que exercita ordenação e remoção na tela.
     *
     * O escopo por organização vem DE GRAÇA e é o ponto: a tabela `media` do Spatie
     * é polimórfica (`morphs('model')`), então o arquivo pertence a ESTE projeto, e
     * este projeto já é escopado por `BelongsToTenant`. Quem não alcança o projeto
     * não alcança o anexo — sem coluna de tenant em `media`, sem configuração.
     *
     * O que NÃO vem de graça é a URL, e por isso o `useDisk()` está escrito aqui
     * mesmo sendo redundante com o default de `config/media-library.php`: quem decide
     * se o arquivo é alcançável sem sessão é o DISCO, não a visibilidade do campo de
     * upload. Com `public`, o caminho é `/storage/{id}/{arquivo}` — ID sequencial,
     * servido pelo symlink, sem sessão e sem assinatura. Com `local`, a entrega passa
     * pela rota `storage.local`, que exige URL assinada.
     *
     * A redundância é defesa em profundidade de propriedade de segurança: trocar
     * `MEDIA_DISK` de volta para `public` não reabre o vazamento nesta coleção. Vale
     * como padrão — coleção de mídia declara o disco (.ai/rules/models.md).
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('anexos')->useDisk('local');
    }

    /**
     * Miniatura para a tabela e o lightbox.
     *
     * `nonQueued()` porque o kit nasce com `QUEUE_CONNECTION=database` e **sem
     * garantia de worker no ar**: enfileirada, a conversão só existiria com um worker
     * rodando, e a coluna da tabela ficaria vazia sem erro nenhum — falha silenciosa é
     * o que este kit evita por padrão. Com worker garantido (o serviço `worker` do
     * docker compose, ou o `composer dev`), tire o `nonQueued()`.
     *
     * E `nonQueued()` vem ANTES de `width()`/`height()`: os dois últimos são
     * encaminhados ao `ImageDriver` do spatie/image e devolvem o DRIVER, não a
     * `Conversion` — encadear `nonQueued()` depois deles procura o método na classe
     * errada. O PHPStan pega; em runtime seria `BadMethodCallException` na primeira
     * conversão.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('miniatura')
            ->nonQueued()
            ->width(200)
            ->height(200);
    }
}

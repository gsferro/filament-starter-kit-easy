<?php

use App\Models\Projeto;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Conversions\Conversion;

/**
 * Soft delete e mídia no `Projeto` — o que só dá para testar com organização.
 *
 * `projetos.tenant_id` é NOT NULL com FK, e quem o preenche é a trait
 * `BelongsToTenant` a partir do tenant do Filament. Sem tenancy não há projeto para
 * apagar nem para anexar arquivo, então estes casos vivem aqui e não em `tests/Kit`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    noPainelDa(tenant('Acme', 'acme'));
});

/*
|--------------------------------------------------------------------------
| Lixeira
|--------------------------------------------------------------------------
*/

it('apaga o projeto de forma reversível', function (): void {
    $projeto = Projeto::create(['nome' => 'Reversível']);

    $projeto->delete();

    expect(Projeto::query()->count())->toBe(0)
        ->and(Projeto::withTrashed()->count())->toBe(1);

    Projeto::withTrashed()->firstOrFail()->restore();

    expect(Projeto::query()->count())->toBe(1);
});

/**
 * O soft delete NÃO afrouxa a fronteira de organização.
 *
 * São dois escopos globais empilhados — o do `SoftDeletes` e o do `BelongsToTenant` —
 * e `withTrashed()` remove só o primeiro. Se removesse os dois, a Lixeira de uma
 * organização mostraria o que a outra apagou.
 */
it('mantém o recorte por organização mesmo com withTrashed', function (): void {
    Projeto::create(['nome' => 'Da Acme'])->delete();

    noPainelDa(tenant('Globex', 'globex'));

    expect(Projeto::withTrashed()->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Mídia
|--------------------------------------------------------------------------
*/

it('anexa arquivo na coleção do projeto', function (): void {
    Storage::fake('local');

    $projeto = Projeto::create(['nome' => 'Com anexo']);

    $projeto->addMedia(UploadedFile::fake()->create('contrato.pdf', 12))
        ->toMediaCollection('anexos');

    expect($projeto->getMedia('anexos'))->toHaveCount(1)
        ->and($projeto->getFirstMedia('anexos')?->file_name)->toBe('contrato.pdf');
});

/**
 * O ponto que decidiu o pacote de mídia do kit.
 *
 * A tabela `media` do Spatie é polimórfica: o arquivo pertence ao PROJETO, e o projeto
 * já é escopado por `BelongsToTenant`. Quem não alcança o projeto não alcança o anexo —
 * sem coluna de tenant em `media`, sem configuração a lembrar de ligar.
 *
 * É por isso que o kit escolheu `filament/spatie-laravel-media-library-plugin` em vez
 * do Curator, cuja biblioteca é compartilhada e cujo escopo por tenant nasce
 * DESLIGADO. Ver wikis/pacotes-ranking.md.
 */
it('isola o anexo por organização, sem coluna de tenant em media', function (): void {
    Storage::fake('local');

    Projeto::create(['nome' => 'Da Acme'])
        ->addMedia(UploadedFile::fake()->create('sigiloso.pdf', 12))
        ->toMediaCollection('anexos');

    noPainelDa(tenant('Globex', 'globex'));

    // A outra organização não alcança o projeto...
    expect(Projeto::query()->count())->toBe(0);

    // ...logo não há caminho pela aplicação até a mídia dele.
    $projetoDaOutra = Projeto::create(['nome' => 'Da Globex']);

    expect($projetoDaOutra->getMedia('anexos'))->toHaveCount(0);
});

/**
 * A conversão precisa ser NÃO enfileirada enquanto o kit nascer com `QUEUE_CONNECTION=sync`.
 *
 * Enfileirada sem worker, a miniatura nunca é gerada e a coluna da tabela fica vazia —
 * sem erro nenhum. Falha silenciosa é exatamente o que o kit evita por padrão.
 */
it('gera a miniatura sem depender de worker de fila', function (): void {
    $projeto = Projeto::create(['nome' => 'Com miniatura']);

    $projeto->registerAllMediaConversions();

    // `getName()` e não `firstWhere('name', ...)`: o `$name` da `Conversion` é
    // `protected`, então a busca por atributo não enxerga nada e devolve null — o que
    // pareceria "conversão não registrada".
    $miniatura = collect($projeto->mediaConversions)
        ->first(fn (Conversion $conversao): bool => $conversao->getName() === 'miniatura');

    expect($miniatura)->not->toBeNull()
        ->and($miniatura->shouldBeQueued())->toBeFalse();
});

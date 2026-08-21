<?php

use App\Filament\App\Resources\Projetos\Pages\ListProjetos;
use App\Filament\App\Resources\Projetos\ProjetoResource;
use App\Models\Projeto;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * Anexo de projeto não é entregue a quem não tem assinatura válida.
 *
 * O defeito que estes casos fecham: `config/media-library.php` tinha `public` como
 * default, e `storage/app/public` é servido pelo symlink `public/storage` SEM sessão e
 * sem assinatura. O upload pela tela já resolvia para o disco privado — o que vazava
 * era `addMedia(...)->toMediaCollection('anexos')`, a chamada da documentação do
 * spatie, a que o usuário do kit escreve.
 *
 * **Quase não há `Storage::fake()` aqui, e a razão importa.** O fake NÃO substitui a
 * rota `storage.{disk}`: ela continua de pé e o `ServeFile` passa a ler do root falso
 * consultando a visibilidade do config CAPTURADO NO BOOT
 * (`FilesystemServiceProvider::registerPublicDiskRoutes()`). Um cenário de rede com
 * fake parece válido e mede outra coisa. Os cenários de rede usam disco real em
 * diretório temporário.
 *
 * E é por isso que o `Então` dos cenários de disco fala em "não declara visibilidade
 * pública na configuração", e não em "não é o disco `public`": é o valor que a rota
 * de fato consulta, e afirmar sobre ele é a única forma de matar o mutante
 * "declarar `visibility => public` no disco privado".
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->organizacao = tenant('Acme', 'acme');
    noPainelDa($this->organizacao);

    // Disco real, raiz descartável. Sem isto, o cenário de rede escreveria em
    // `storage/app/private` de verdade e deixaria lixo entre execuções.
    $this->raizDoDiscoPrivado = storage_path('framework/testing/midia-privada-'.Str::random(8));
    config(['filesystems.disks.local.root' => $this->raizDoDiscoPrivado]);
    Storage::forgetDisk('local');
});

afterEach(function (): void {
    File::deleteDirectory($this->raizDoDiscoPrivado);
});

/** O disco declara visibilidade pública na configuração — o critério que a rota usa. */
function ehDiscoPublico(string $disco): bool
{
    return config("filesystems.disks.{$disco}.visibility") === 'public';
}

/*
|--------------------------------------------------------------------------
| R1 e R3 — mídia nova nasce em disco privado, e os caminhos concordam
|--------------------------------------------------------------------------
*/

it('[CT-01] não grava em disco público pela chamada idiomática do spatie', function (): void {
    $projeto = Projeto::create(['nome' => 'Com anexo']);

    $projeto->addMedia(UploadedFile::fake()->create('contrato.pdf', 12))
        ->toMediaCollection('anexos');

    expect(ehDiscoPublico($projeto->getFirstMedia('anexos')->disk))->toBeFalse();
});

/**
 * O veredito não pode depender do `.env`, que é gitignorado.
 *
 * Quem corrigisse apenas o `.env.example` teria este caso verde numa instalação nova, e
 * o default de `config/` — o único artefato que o `kit:update` entrega — seguiria
 * vazando para toda instalação existente.
 */
it('[CT-02] coleção sem declaração herda o default privado, sem variável de ambiente', function (): void {
    $anterior = $_SERVER['MEDIA_DISK'] ?? null;
    unset($_ENV['MEDIA_DISK'], $_SERVER['MEDIA_DISK']);
    putenv('MEDIA_DISK');

    try {
        /** @var array{disk_name: string} $config */
        $config = require config_path('media-library.php');

        expect(ehDiscoPublico($config['disk_name']))->toBeFalse();
    } finally {
        if ($anterior !== null) {
            $_ENV['MEDIA_DISK'] = $_SERVER['MEDIA_DISK'] = $anterior;
            putenv('MEDIA_DISK='.$anterior);
        }
    }

    $projeto = Projeto::create(['nome' => 'Coleção não declarada']);

    $projeto->addMedia(UploadedFile::fake()->create('avulso.pdf', 12))
        ->toMediaCollection('sem-declaracao');

    expect(ehDiscoPublico($projeto->getFirstMedia('sem-declaracao')->disk))->toBeFalse();
});

/** O `useDisk()` da coleção é redundante com o default DE PROPÓSITO: é o que sobrevive a trocá-lo. */
it('[CT-03] a coleção que declara o disco ignora o default trocado para público', function (): void {
    config(['media-library.disk_name' => 'public']);

    $projeto = Projeto::create(['nome' => 'Default trocado']);

    $projeto->addMedia(UploadedFile::fake()->create('contrato.pdf', 12))
        ->toMediaCollection('anexos');

    expect(ehDiscoPublico($projeto->getFirstMedia('anexos')->disk))->toBeFalse();
});

/**
 * Guarda de regressão, não falsificação: o caminho da tela JÁ estava correto antes da
 * correção, porque `SpatieMediaLibraryFileUpload::getDiskName()` força disco privado
 * quando o default seria público. Está aqui para que a correção não quebre o que
 * funcionava.
 */
it('[CT-04] o upload pela tela continua gravando em disco privado', function (): void {
    // `ProjetoResource::canAccess()` exige `kit.demo`, e o phpunit.xml o fixa em `false`
    // para o menu nascer vazio no resto da suíte. Sem esta linha a tela responde 403 e o
    // caso mediria a guarda do resource, não o disco.
    config(['kit.demo' => true]);

    $usuario = usuarioComPapel('admin_app', $this->organizacao);
    $usuario->tenants()->attach($this->organizacao);
    $this->actingAs($usuario);

    // Request de verdade na tela ANTES do teste de componente: é o `SetUpPanel` que boota
    // o painel, e o `BreezyCore` lê `request()->route()->parameter('tenant')` no boot —
    // `noPainelBootado()` sozinho morre ali, sem rota, no ARRANJO.
    $this->get(ProjetoResource::getUrl('index', tenant: $this->organizacao))->assertOk();

    // Resource simples: a criação é modal na listagem, não página própria.
    Livewire::test(ListProjetos::class)
        ->callAction('create', [
            'nome'   => 'Pela tela',
            'anexos' => [UploadedFile::fake()->create('contrato.pdf', 12)],
        ])
        ->assertHasNoFormErrors();

    $midia = Projeto::query()->firstOrFail()->getFirstMedia('anexos');

    expect($midia)->not->toBeNull()
        ->and(ehDiscoPublico($midia->disk))->toBeFalse();
});

/**
 * Sem este caso, nada impediria que a tela gravasse num disco privado e o `addMedia()`
 * em OUTRO — os dois satisfazendo "não entregue sem assinatura", e o comando de
 * migração não enxergando nenhum dos dois.
 */
it('[CT-05] os dois caminhos de escrita convergem para o mesmo disco', function (): void {
    // `ProjetoResource::canAccess()` exige `kit.demo`, e o phpunit.xml o fixa em `false`
    // para o menu nascer vazio no resto da suíte. Sem esta linha a tela responde 403 e o
    // caso mediria a guarda do resource, não o disco.
    config(['kit.demo' => true]);

    $usuario = usuarioComPapel('admin_app', $this->organizacao);
    $usuario->tenants()->attach($this->organizacao);
    $this->actingAs($usuario);

    // Request de verdade na tela ANTES do teste de componente: é o `SetUpPanel` que boota
    // o painel, e o `BreezyCore` lê `request()->route()->parameter('tenant')` no boot —
    // `noPainelBootado()` sozinho morre ali, sem rota, no ARRANJO.
    $this->get(ProjetoResource::getUrl('index', tenant: $this->organizacao))->assertOk();

    Livewire::test(ListProjetos::class)
        ->callAction('create', [
            'nome'   => 'Dois caminhos',
            'anexos' => [UploadedFile::fake()->create('pela-tela.pdf', 12)],
        ])
        ->assertHasNoFormErrors();

    $projeto = Projeto::query()->firstOrFail();

    $projeto->addMedia(UploadedFile::fake()->create('programatico.pdf', 12))
        ->toMediaCollection('anexos');

    $discos = $projeto->refresh()->getMedia('anexos')->pluck('disk')->unique();

    expect($discos)->toHaveCount(1);
});

/*
|--------------------------------------------------------------------------
| R2 — o arquivo só sai com assinatura válida, e sai quando ela existe
|--------------------------------------------------------------------------
*/

/**
 * O oráculo é o PAR, não o 403 sozinho.
 *
 * `ServeFile` valida a assinatura ANTES de checar existência (`ServeFile.php:27-32`):
 * 403 sem assinatura é o que a rota devolve para arquivo protegido, inexistente OU
 * disco quebrado. Só a versão assinada entregando os bytes distingue os três.
 */
it('[CT-06] a mesma URL responde diferente com e sem assinatura', function (): void {
    $midia = Projeto::create(['nome' => 'Com anexo'])
        ->addMedia(UploadedFile::fake()->create('contrato.pdf', 12))
        ->toMediaCollection('anexos');

    $this->get($midia->getUrl())->assertForbidden();

    $this->get($midia->getTemporaryUrl(now()->addMinutes(5)))
        ->assertOk()
        ->assertStreamedContent(Storage::disk($midia->disk)->get($midia->getPathRelativeToRoot()));
});

/**
 * O par POSITIVO da miniatura, e ele é o que mata "trocar o disco e quebrar a conversão
 * no mesmo commit": por F1, o lado negativo sozinho fica verde com a conversão NUNCA
 * gerada.
 */
it('[CT-07] a miniatura obedece ao mesmo par', function (): void {
    $midia = Projeto::create(['nome' => 'Com imagem'])
        ->addMedia(UploadedFile::fake()->image('planta.png', 600, 600))
        ->toMediaCollection('anexos');

    $midia->refresh();

    expect($midia->hasGeneratedConversion('miniatura'))->toBeTrue();

    $this->get($midia->getUrl('miniatura'))->assertForbidden();

    $this->get($midia->getTemporaryUrl(now()->addMinutes(5), 'miniatura'))->assertOk();

    // Dimensão, não bytes: PNG de teste é quase todo cabeçalho, e a miniatura de 600px
    // para 200px pode sair MAIOR em bytes que o original. O que caracteriza miniatura é
    // ser menor na tela.
    [$largura] = getimagesizefromstring(
        Storage::disk($midia->conversions_disk)->get($midia->getPathRelativeToRoot('miniatura'))
    );

    expect($largura)->toBeLessThanOrEqual(200);
});

/** Falha fechada: a URL que o sistema publica por default não serve o arquivo. */
it('[CT-08] a URL devolvida por getUrl() não serve o arquivo', function (): void {
    $midia = Projeto::create(['nome' => 'Com anexo'])
        ->addMedia(UploadedFile::fake()->create('contrato.pdf', 12))
        ->toMediaCollection('anexos');

    expect($this->get($midia->getUrl())->status())->not->toBe(200);
});

it('[CT-09] assinatura expirada não serve', function (): void {
    $midia = Projeto::create(['nome' => 'Com anexo'])
        ->addMedia(UploadedFile::fake()->create('contrato.pdf', 12))
        ->toMediaCollection('anexos');

    $url = $midia->getTemporaryUrl(now()->addMinutes(5));

    $this->travel(6)->minutes();

    expect($this->get($url)->status())->not->toBe(200);
});

/**
 * A superfície de ESCRITA que ninguém tinha olhado: o mesmo `serve => true` de que a
 * correção passa a depender registra também `PUT /storage/{path}`, com `middleware: []`.
 */
it('[CT-10] a rota de escrita não aceita PUT anônimo sem assinatura', function (): void {
    $midia = Projeto::create(['nome' => 'Com anexo'])
        ->addMedia(UploadedFile::fake()->create('contrato.pdf', 12))
        ->toMediaCollection('anexos');

    $antes = Storage::disk($midia->disk)->get($midia->getPathRelativeToRoot());

    expect($this->put($midia->getUrl(), [], ['CONTENT_TYPE' => 'application/pdf'])->status())->not->toBe(200);

    expect(Storage::disk($midia->disk)->get($midia->getPathRelativeToRoot()))->toBe($antes);
});

/**
 * Deliberadamente um SUCESSO para anônimo: fixa o limite que o ADR-03 aceitou — quem
 * tem o link entra, sem sessão. Fica vermelho no dia em que alguém implementar
 * autorização real na rota, sinalizando que a decisão mudou.
 */
it('[CT-11] a assinatura válida é o que separa o 403 do 200, sem sessão nenhuma', function (): void {
    $midia = Projeto::create(['nome' => 'Com anexo'])
        ->addMedia(UploadedFile::fake()->create('contrato.pdf', 12))
        ->toMediaCollection('anexos');

    $this->assertGuest();

    $this->get($midia->getTemporaryUrl(now()->addMinutes(5)))->assertOk();
});

/**
 * A miniatura na LISTAGEM sai com URL assinada.
 *
 * Aqui e não no navegador: `<img>` que responde 403 **não gera erro de JavaScript**,
 * então `assertNoJavaScriptErrors()` fica verde com a coluna inteira quebrada. O oráculo
 * é o `src` renderizado, e o HTML do componente já o carrega.
 */
it('[CT-07b] a coluna de mídia da listagem renderiza src assinada', function (): void {
    config(['kit.demo' => true]);

    $usuario = usuarioComPapel('admin_app', $this->organizacao);
    $usuario->tenants()->attach($this->organizacao);
    $this->actingAs($usuario);

    Projeto::create(['nome' => 'Com imagem'])
        ->addMedia(UploadedFile::fake()->image('planta.png', 600, 600))
        ->toMediaCollection('anexos');

    $this->get(ProjetoResource::getUrl('index', tenant: $this->organizacao))->assertOk();

    // `loadTable` explícito: o corpo da tabela do Filament 5 nasce vazio e é carregado
    // por `wire:init`, então sem esta chamada o HTML não tem linha nenhuma — e a asserção
    // passaria a medir a ausência de tabela.
    Livewire::test(ListProjetos::class)
        ->call('loadTable')
        ->assertSee('signature=', escape: false);
});

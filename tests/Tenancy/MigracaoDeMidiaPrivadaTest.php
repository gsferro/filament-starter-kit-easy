<?php

use App\Models\Projeto;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Tenancy\Fixtures\OrganizacaoComMidia;

/**
 * `kit:midia-privada` — a janela que a correção do default deixaria aberta.
 *
 * Trocar o default de `config/media-library.php` protege a mídia NOVA. O que já está em
 * `storage/app/public/{id}/` continua servido pelo symlink `public/storage`, sem sessão
 * e sem assinatura: o vazamento medido continua reproduzível numa instalação que só
 * recebeu a config nova.
 *
 * **Estes casos vivem em `tests/Tenancy` e não em `tests/Kit`, ao contrário do que o
 * `04-casos-de-teste.md` indexou.** `projetos.tenant_id` é NOT NULL com FK, e é o
 * `Projeto` a única superfície de mídia real do kit — sem organização não há como
 * anexar arquivo pelo caminho de verdade. Registrado como desvio no `03-progresso.md`.
 *
 * Os discos são reapontados para raiz descartável. O oráculo do lado PÚBLICO é de
 * sistema de arquivos, não de HTTP: o symlink é resolvido pelo servidor web ANTES do
 * framework (`public/server.php`), então "entregue sem assinatura" não é observável
 * numa requisição de teste. O lado privado é observável, e é onde o par 403/200 mora.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->organizacao = tenant('Acme', 'acme');
    noPainelDa($this->organizacao);

    $this->raizes = [
        'local'  => storage_path('framework/testing/migracao-privada-'.Str::random(8)),
        'public' => storage_path('framework/testing/migracao-publica-'.Str::random(8)),
    ];

    config([
        'filesystems.disks.local.root'  => $this->raizes['local'],
        'filesystems.disks.public.root' => $this->raizes['public'],
    ]);

    Storage::forgetDisk(['local', 'public']);
});

afterEach(function (): void {
    foreach ($this->raizes as $raiz) {
        File::deleteDirectory($raiz);
    }
});

/**
 * O estado LEGADO, e ele não se reproduz na coleção `anexos`.
 *
 * `Projeto::registerMediaCollections()` declara `useDisk('local')`, e a declaração vence
 * o default — é o que o CT-03 fixa. Mídia legada é o que foi gravado quando a coleção
 * NÃO declarava disco, e é isso que uma coleção sem declaração reproduz.
 */
function anexoLegado(Projeto $projeto, string $arquivo = 'contrato.pdf', string $colecao = 'legado', bool $imagem = false)
{
    config(['media-library.disk_name' => 'public']);

    // Imagem DE VERDADE quando o cenário precisa de conversão: `fake()->create()` produz
    // conteúdo de mentira, e aí a miniatura simplesmente não é gerada — sem erro.
    $upload = $imagem
        ? UploadedFile::fake()->image($arquivo, 600, 600)
        : UploadedFile::fake()->create($arquivo, 12);

    $midia = $projeto->addMedia($upload)->toMediaCollection($colecao);

    config(['media-library.disk_name' => 'local']);

    return $midia->refresh();
}

/** Todo arquivo que vive sob a raiz servida pelo symlink `public/storage`. */
function inventarioPublico(): array
{
    return Storage::disk('public')->allFiles();
}

/*
|--------------------------------------------------------------------------
| R4 — a migração leva original e conversões, de qualquer model, sem perder arquivo
|--------------------------------------------------------------------------
*/

it('[CT-12] o anexo legado deixa de ser entregue sem assinatura', function (): void {
    $midia = anexoLegado(Projeto::create(['nome' => 'Legado']));
    $bytes = Storage::disk('public')->get($midia->getPathRelativeToRoot());

    $this->artisan('kit:midia-privada')->assertSuccessful();

    $midia->refresh();

    $this->get($midia->getUrl())->assertForbidden();

    $this->get($midia->getTemporaryUrl(now()->addMinutes(5)))
        ->assertOk()
        ->assertStreamedContent($bytes);
});

/**
 * A lacuna mais cara que a revisão adversarial encontrou.
 *
 * Uma migração que mova o original e ignore `conversions_disk` deixa EXATAMENTE a segunda
 * linha da medição do requisito — a miniatura em `/storage/{id}/conversions/…` — servida
 * pelo symlink, com a suíte inteira verde.
 */
it('[CT-13] a conversão legada é migrada junto do original', function (): void {
    $midia = anexoLegado(Projeto::create(['nome' => 'Com miniatura']), 'planta.png', 'anexos-legado', imagem: true);

    // A conversão só existe se a coleção registrar uma. `registerMediaConversions()` do
    // `Projeto` vale para qualquer coleção dele, inclusive esta.
    expect($midia->hasGeneratedConversion('miniatura'))->toBeTrue()
        ->and(Storage::disk('public')->exists($midia->getPathRelativeToRoot('miniatura')))->toBeTrue();

    $this->artisan('kit:midia-privada')->assertSuccessful();

    $midia->refresh();

    $this->get($midia->getUrl('miniatura'))->assertForbidden();
    $this->get($midia->getTemporaryUrl(now()->addMinutes(5), 'miniatura'))->assertOk();

    expect(inventarioPublico())->toBeEmpty();
});

/**
 * O `Dado` carrega a primeira execução, e o oráculo compara o inventário ANTES e DEPOIS
 * da segunda — não um contador. "Executa duas vezes" num passo só ficaria verde com uma
 * primeira execução que falha e uma segunda que corrige.
 */
it('[CT-14] a segunda execução não muda o estado deixado pela primeira', function (): void {
    anexoLegado(Projeto::create(['nome' => 'Duas vezes']));

    $this->artisan('kit:midia-privada')->assertSuccessful();

    $antes = Storage::disk('local')->allFiles();

    $this->artisan('kit:midia-privada')
        ->expectsOutputToContain('Mídias migradas: 0.')
        ->assertSuccessful();

    expect(Storage::disk('local')->allFiles())->toBe($antes);
});

/**
 * Fecha o escopo: um comando filtrado por `model_type = Projeto` passaria todos os outros
 * cenários, porque `Projeto` é a única superfície de mídia do kit — e contrariaria a
 * premissa de que a correção é do kit, não da demo.
 */
it('[CT-15] mídia de outro model também é migrada', function (): void {
    $organizacao = OrganizacaoComMidia::query()->firstOrFail();

    config(['media-library.disk_name' => 'public']);
    $midia = $organizacao->addMedia(UploadedFile::fake()->create('ata.pdf', 12))->toMediaCollection('documentos');
    config(['media-library.disk_name' => 'local']);

    expect($midia->refresh()->disk)->toBe('public');

    $this->artisan('kit:midia-privada')->assertSuccessful();

    $midia->refresh();

    $this->get($midia->getUrl())->assertForbidden();
    $this->get($midia->getTemporaryUrl(now()->addMinutes(5)))->assertOk();
});

/*
|--------------------------------------------------------------------------
| R5 — a migração não desfaz escolha explícita de disco público
|--------------------------------------------------------------------------
*/

/**
 * Avatar e logo são públicos DE PROPÓSITO: aparecem na tela de login, antes de haver
 * sessão. Migrar por cima da declaração quebraria a identidade visual.
 *
 * O oráculo é de sistema de arquivos, e não HTTP, por F7: o symlink é resolvido antes do
 * framework, então "continua entregue sem assinatura" não tem como ser medido por
 * requisição de teste. O que caracteriza o estado público aqui é o arquivo seguir no
 * disco cuja visibilidade declarada é `public`.
 */
it('[CT-16] coleção que escolheu o disco público não é migrada', function (): void {
    $organizacao = OrganizacaoComMidia::query()->firstOrFail();

    $midia = $organizacao->addMedia(UploadedFile::fake()->create('logo.png', 12))
        ->toMediaCollection('identidade');

    $this->artisan('kit:midia-privada')->assertSuccessful();

    $midia->refresh();

    expect($midia->disk)->toBe('public')
        ->and(config("filesystems.disks.{$midia->disk}.visibility"))->toBe('public')
        ->and(Storage::disk('public')->exists($midia->getPathRelativeToRoot()))->toBeTrue();
});

/**
 * O número é o oráculo. "Informa quantos seriam migrados" é satisfeito literalmente por
 * um comando que imprime sempre `0` — o mutante sobrevivia ao cenário que o declarava
 * morto.
 */
it('[CT-17] a simulação não move nada e informa o número exato', function (): void {
    $projeto = Projeto::create(['nome' => 'Simulação']);

    anexoLegado($projeto, 'um.pdf');
    anexoLegado($projeto, 'dois.pdf', 'outro-legado');

    $antes = inventarioPublico();

    $this->artisan('kit:midia-privada --dry-run')
        ->expectsOutputToContain('Mídias seriam migradas: 2.')
        ->assertSuccessful();

    expect(inventarioPublico())->toBe($antes);
});

/*
|--------------------------------------------------------------------------
| R2 (estrutural) — nada sob o symlink sombreia a rota assinada
|--------------------------------------------------------------------------
*/

/**
 * Estrutural e observável, ao contrário da colisão de URI que ele substitui: afirma sobre
 * o sistema de arquivos, não sobre resposta HTTP. É ele que garante que NÃO HÁ o que
 * sombrear — a propriedade de que o requisito de fato precisa.
 */
it('[CT-18] após a migração não resta mídia no diretório servido pelo symlink', function (): void {
    $projeto = Projeto::create(['nome' => 'Sem sobra']);

    anexoLegado($projeto, 'planta.png', 'com-conversao', imagem: true);
    anexoLegado($projeto, 'contrato.pdf');

    expect(inventarioPublico())->not->toBeEmpty();

    $this->artisan('kit:midia-privada')->assertSuccessful();

    expect(inventarioPublico())->toBeEmpty();
});

/**
 * **Limite aceito, escrito como teste.** A rota `storage.local` e o symlink
 * `public/storage` dividem o prefixo `/storage`, e arquivo físico ganha da rota. O ADR-01
 * aceitou a convivência apoiada em CT-18 — enquanto não houver mídia sob o symlink, não
 * há o que sombrear.
 *
 * Este caso é o marco dessa decisão, não a sua negação: ele fica VERMELHO no dia em que
 * alguém der `url` própria ao disco privado (a opção C do ADR-01), obrigando a reler a
 * decisão em vez de descobri-la por acidente.
 */
it('[CT-19] o prefixo de URI do disco de mídia é o mesmo do symlink — limite aceito no ADR-01', function (): void {
    $prefixoDoSymlink = rtrim(parse_url((string) config('filesystems.disks.public.url'), PHP_URL_PATH), '/');

    $urlDoPrivado     = config('filesystems.disks.local.url');
    $prefixoDoPrivado = $urlDoPrivado === null
        ? '/storage'
        : rtrim(parse_url((string) $urlDoPrivado, PHP_URL_PATH), '/');

    expect($prefixoDoPrivado)->toBe($prefixoDoSymlink)
        ->and(config('filesystems.disks.local.serve'))->toBeTrue();
});

<?php

use App\Filament\Admin\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use App\Filament\App\Resources\Projetos\Pages\ListProjetos;
use App\Filament\App\Resources\Projetos\ProjetoResource;
use App\Models\Projeto;
use App\Models\Tenant;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * O teto de upload e a recusa de SVG nos dois campos que só existem com tenancy.
 *
 * IDs de CT em `wikis/specs/feat/upload-limite-e-tipos/upload-limite-e-tipos/04-casos-de-teste.md`.
 *
 * Aqui e não em `tests/Kit` por dois motivos independentes: o `TenantResource` só
 * faz sentido com organizações, e o `ProjetoResource` se esconde sozinho sem
 * `kit.tenancy.enabled` **e** `kit.demo` (`ProjetoResource::daDemo()`). E
 * `Tests\TenancyTestCase` fixa `permission.teams` em `createApplication()`, antes
 * das migrations — ligar a flag num `beforeEach` seria tarde demais
 * (`.ai/rules/testes.md`).
 *
 * Os dois campos exigem formas DIFERENTES de recusar SVG, e é isso que estes
 * casos protegem: a logo é imagem e usa allow-list; o anexo é documento, onde
 * allow-list fecharia o campo para PDF, e a recusa é de um formato só.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * O arranjo mínimo para exercitar o form de Projeto por componente.
 *
 * Três coisas, e nenhuma é dispensável:
 *
 * 1. `kit.demo` ligado — `ProjetoResource::canAccess()` exige a flag, e o
 *    `phpunit.xml` a fixa em `false` para o menu nascer vazio no resto da suíte.
 *    Sem ela, a Action `create` não existe e o caso morre em "Attempt to read
 *    property mountedActions on null", que não fala de flag nenhuma.
 * 2. usuário `admin_app` VINCULADO à organização — o papel só existe em
 *    `tests/Tenancy` (`.ai/rules/testes.md`), e sem o `attach()` o painel não o
 *    alcança.
 * 3. um GET de verdade na tela ANTES do teste de componente — quem boota o painel
 *    é o middleware `SetUpPanel`, e o `BreezyCore` lê
 *    `request()->route()->parameter('tenant')` no boot. Teste de componente não
 *    atravessa middleware, e o arranjo morre ali sem a rota.
 *
 * Mesma sequência de `tests/Tenancy/AnexosPrivadosTest.php:36-44,123-137`.
 */
function noFormDeProjetoDa(Tenant $organizacao): void
{
    config(['kit.demo' => true]);

    // Painel corrente ANTES do `getUrl()`: sem isto o Filament resolve a rota no
    // painel default do processo (`infra` nesta suíte) e o arranjo morre em
    // "Route [filament.infra.resources.projetos.index] not defined" — erro que não
    // fala nem de painel nem de tenant.
    noPainelDa($organizacao);

    $usuario = usuarioComPapel('admin_app', $organizacao);
    $usuario->tenants()->attach($organizacao);
    test()->actingAs($usuario);

    test()->get(ProjetoResource::getUrl('index', tenant: $organizacao))->assertOk();
}

/*
|--------------------------------------------------------------------------
| RQ-01, RQ-02 e RQ-05 — a logo da organização
|--------------------------------------------------------------------------
*/

/**
 * CT-10 — a logo da organização aceita até o teto da config e recusa acima.
 *
 * Ela tinha `->maxSize(1024)` — **1 MB** — sem nenhum comentário justificando o
 * número, entre encadeamentos que explicavam tipo, disco e visibilidade. O caso
 * de 5 MB é o que reprova aquele valor: ele passa agora e passaria a reprovar no
 * instante em que alguém cravar um literal ali de novo.
 *
 * A asserção é sobre COMPORTAMENTO, e não sobre o texto do arquivo: um
 * `str_contains` no fonte ficaria verde com `->maxSize(TetoDeUpload::emKb())`
 * escrito e nunca encadeado.
 *
 * `setCurrentPanel('admin')` antes do `Livewire::test`: o componente de resource
 * resolve o schema pelo painel corrente, e sem ele o caso morre em
 * `getDefaultTestingSchemaName() on null`.
 */
it('[CT-10] aceita a logo da organizacao ate o teto da config e recusa acima', function (int $tamanhoEmKb, bool $recusado): void {
    Storage::fake('public');
    Filament::setCurrentPanel('admin');

    // Alarga a barreira DE FORA para isolar a do campo. Sem isto, o arquivo um
    // kilobyte acima do teto seria recusado pelo upload temporário do Livewire e o
    // `->maxSize()` do campo poderia estar AUSENTE com o caso verde — que é
    // literalmente o defeito que esta feature paga. Ver `## A armadilha de camada`
    // no 04-casos-de-teste.md.
    config()->set('livewire.temporary_file_upload.rules', ['required', 'file', 'max:1048576']);

    $this->actingAs(usuarioComPapel('master_global'));

    $resultado = Livewire::test(CreateTenant::class)
        ->fillForm([
            'nome'  => 'Acme',
            'slug'  => 'acme',
            'ativo' => true,
            'logo'  => UploadedFile::fake()->image('marca.png')->size($tamanhoEmKb),
        ])
        ->call('create');

    // `['logo']` e nao `['logo' => 'max']`: as regras de arquivo do Filament rodam
    // num validador ANINHADO e a falha volta como `$fail($validator->errors()->first())`
    // (BaseFileUpload.php:752-772), o que descarta o nome da regra no caminho. Quem
    // afirma QUAL barreira recusou e a mensagem, abaixo.
    $recusado
        ? $resultado->assertHasFormErrors(['logo'])->assertSee('O arquivo passa de 10 MB.')
        : $resultado->assertHasNoFormErrors();

    expect(Tenant::where('slug', 'acme')->exists())->toBe(! $recusado);
})->with([
    'acima do antigo 1 MB, dentro do teto' => [5120, false],
    'exatamente no teto'                   => [10240, false],
    'um acima do teto'                     => [10241, true],
])->group('kit');

/**
 * CT-11 — a logo da organização continua recusando SVG.
 *
 * Regressão, não feature nova: aquele campo já tinha a allow-list
 * `['image/png','image/jpeg','image/webp']` e o comentário explicando a
 * armadilha. Este caso existe porque esta feature MEXEU naquele encadeamento
 * (trocou o `maxSize`), e o jeito de descobrir que a allow-list caiu no caminho
 * é ter um caso que reprove.
 */
it('[CT-11] recusa SVG na logo da organizacao', function (): void {
    Storage::fake('public');
    Filament::setCurrentPanel('admin');

    $this->actingAs(usuarioComPapel('master_global'));

    Livewire::test(CreateTenant::class)
        ->fillForm([
            'nome'  => 'Acme',
            'slug'  => 'acme',
            'ativo' => true,
            'logo'  => UploadedFile::fake()->create('marca.svg', 8, 'image/svg+xml'),
        ])
        ->call('create')
        ->assertHasFormErrors(['logo']);

    expect(Tenant::where('slug', 'acme')->exists())->toBeFalse();
})->group('kit');

/*
|--------------------------------------------------------------------------
| RQ-01, RQ-02, RQ-03 e RQ-05 — os anexos de Projeto
|--------------------------------------------------------------------------
*/

/**
 * CT-12 — o anexo aceita até o teto da config e recusa acima.
 *
 * O `10 * 1024` estava cravado no código e repetido no `helperText` do mesmo
 * bloco — dois lugares, um número, nenhuma config. Trocar por
 * `TetoDeUpload::emKb()` não muda o valor de fábrica, e é exatamente por isso
 * que o caso de fronteira importa: só ele distingue "o teto continua valendo" de
 * "alguém apagou o `maxSize()` junto com o literal".
 */
it('[CT-12] aceita o anexo ate o teto da config e recusa acima', function (int $tamanhoEmKb, bool $recusado): void {
    config()->set('livewire.temporary_file_upload.rules', ['required', 'file', 'max:1048576']);

    noFormDeProjetoDa(tenant('Acme', 'acme'));

    $resultado = Livewire::test(ListProjetos::class)
        ->callAction('create', [
            'nome'   => 'Com anexo',
            'anexos' => [UploadedFile::fake()->create('contrato.pdf', $tamanhoEmKb, 'application/pdf')],
        ]);

    $recusado
        ? $resultado->assertHasFormErrors(['anexos'])
        : $resultado->assertHasNoFormErrors();

    expect(Projeto::where('nome', 'Com anexo')->exists())->toBe(! $recusado);
})->with([
    'exatamente no teto' => [10240, false],
    'um acima do teto'   => [10241, true],
])->group('kit');

/**
 * CT-13 — o anexo recusa SVG e continua aceitando PDF.
 *
 * As duas metades são a cláusula inteira. Sem a segunda, uma allow-list de
 * imagem colada aqui por descuido ficaria verde — e o campo de ANEXOS de um
 * projeto pararia de aceitar contrato, planilha e documento, que é a razão de
 * ele existir. É o par que separa "recusa SVG" de "recusa tudo que não é
 * imagem", e a razão de a barreira aqui recusar um formato em vez de permitir
 * uma lista.
 *
 * Os dois arquivos têm o mesmo tamanho, longe do teto: se o SVG fosse maior, a
 * recusa poderia vir do `max` e o caso ficaria verde com a regra removida.
 */
it('[CT-13] recusa SVG no anexo sem recusar documento', function (): void {
    noFormDeProjetoDa(tenant('Acme', 'acme'));

    Livewire::test(ListProjetos::class)
        ->callAction('create', [
            'nome'   => 'Com SVG',
            'anexos' => [UploadedFile::fake()->create('marca.svg', 8, 'image/svg+xml')],
        ])
        ->assertHasFormErrors(['anexos']);

    expect(Projeto::where('nome', 'Com SVG')->exists())->toBeFalse();

    Livewire::test(ListProjetos::class)
        ->callAction('create', [
            'nome'   => 'Com PDF',
            'anexos' => [UploadedFile::fake()->create('contrato.pdf', 8, 'application/pdf')],
        ])
        ->assertHasNoFormErrors();

    expect(Projeto::where('nome', 'Com PDF')->exists())->toBeTrue();
})->group('kit');

/**
 * CT-21 - a logo tambem e barrada na EDICAO, nao so na criacao.
 *
 * Criação e edição são páginas diferentes, com `->call('create')` e
 * `->call('save')` diferentes, e o Filament monta o schema do zero em cada uma.
 * Um cenário só de criação fica verde com a validação valendo apenas lá — e o
 * caminho de edição é o mais usado depois do primeiro dia.
 *
 * O par é o de sempre: dentro do teto grava, acima não. O `refresh()` é o oráculo
 * de "não gravou": afirmar só a ausência de erro não distingue recusado de
 * gravado-com-erro-em-outro-campo.
 */
it('[CT-21] barra a logo acima do teto tambem na edicao da organizacao', function (int $tamanhoEmKb, bool $recusado): void {
    Storage::fake('public');
    Filament::setCurrentPanel('admin');
    config()->set('livewire.temporary_file_upload.rules', ['required', 'file', 'max:1048576']);

    $this->actingAs(usuarioComPapel('master_global'));

    $organizacao = Tenant::factory()->create(['nome' => 'Acme', 'slug' => 'acme', 'logo' => null]);

    $resultado = Livewire::test(EditTenant::class, ['record' => $organizacao->getRouteKey()])
        ->fillForm(['logo' => UploadedFile::fake()->image('marca.png')->size($tamanhoEmKb)])
        ->call('save');

    $recusado
        ? $resultado->assertHasFormErrors(['logo'])
        : $resultado->assertHasNoFormErrors();

    expect($organizacao->refresh()->logo)->{$recusado ? 'toBeNull' : 'toBeString'}();
})->with([
    'no teto'         => [10240, false],
    'acima do teto'   => [10241, true],
])->group('kit');

/**
 * CT-16 - o SVG e recusado em QUALQUER posicao do envio multiplo.
 *
 * O `anexos` é `->multiple()`, e a regra de recusa é um `Closure`. Um `Closure`
 * escrito à mão é a mais frágil das duas barreiras desta feature (a outra é a
 * regra `image`, mantida pelo framework), e o mutante plausível dele é olhar só o
 * primeiro arquivo — `$valor[0]` em vez de cada um.
 *
 * Esse mutante sobrevive a qualquer cenário que mande um arquivo só, e sobrevive
 * ao cenário de dois arquivos com o SVG na frente. Ele morre com o SVG **atrás**
 * de um PDF válido, que é por isso que o dataset tem as duas ordens.
 */
it('[CT-16] recusa o anexo SVG em qualquer posicao do envio', function (int $posicaoDoSvg): void {
    config()->set('livewire.temporary_file_upload.rules', ['required', 'file', 'max:1048576']);
    noFormDeProjetoDa(tenant('Acme', 'acme'));

    $arquivos = [UploadedFile::fake()->create('contrato.pdf', 8, 'application/pdf')];
    array_splice($arquivos, $posicaoDoSvg, 0, [UploadedFile::fake()->create('marca.svg', 8, 'image/svg+xml')]);

    Livewire::test(ListProjetos::class)
        ->callAction('create', ['nome' => 'Com SVG escondido', 'anexos' => $arquivos])
        ->assertHasFormErrors(['anexos']);

    expect(Projeto::where('nome', 'Com SVG escondido')->exists())->toBeFalse();
})->with([
    'SVG na frente' => 0,
    'SVG atrás'     => 1,
])->group('kit');

<?php

use App\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use App\Models\Tenant;
use App\Support\IdentidadeDoKit;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Uma logo só, nos dois temas — POR ORGANIZAÇÃO. O espelho do `unifica_logo_marca`
 * do settings (`LogoDarkModeTest`), um nível abaixo: `tenants.unifica_logo`.
 *
 * Os IDs de CT são os de
 * `wikis/specs/main/unificar-logo-da-organizacao/04-casos-de-teste.md`.
 */

// --- coluna e flag ------------------------------------------------------------

/**
 * CT-01 — `unifica_logo` é atribuível em massa, booleano, e nasce `true`.
 *
 * Três defeitos num caso só: fora do `$fillable` o `create()` descarta em
 * silêncio; sem o cast, o banco devolve `0`/`1` e comparação estrita quebra;
 * sem o `default(true)`, organização nova nascia separada — o contrário do
 * default da marca da instalação.
 */
it('[CT-01] guarda unifica_logo e nasce unificada', function (): void {
    $semFlag   = Tenant::create(['nome' => 'Acme', 'slug' => 'acme', 'ativo' => true]);
    $desligada = Tenant::create(['nome' => 'Globex', 'slug' => 'globex', 'ativo' => true, 'unifica_logo' => false]);

    expect($semFlag->fresh()->unifica_logo)->toBeTrue()
        ->and($desligada->fresh()->unifica_logo)->toBeFalse();
})->group('kit');

// --- o form: visibilidade e gravação -------------------------------------------

/**
 * CT-02 — o toggle governa o campo `logo_dark`, e os dois só existem com a marca
 * da instalação separada.
 *
 * `assertSchemaComponentHidden` + `Visible` no mesmo componente: a metade
 * esquerda sozinha passaria com o campo escondido para sempre, e a direita com
 * ele sempre visível — os dois estados é que fecham a tabela.
 */
it('[CT-02] o toggle governa o campo logo dark no formulario da organizacao', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    config(['kit.tenancy.enabled' => true]);
    config(['kit.identidade.unifica_logo_marca' => false]);
    noPainelDoShield('admin');
    noPainelBootado('admin');

    $organizacao = Tenant::factory()->create(['unifica_logo' => true]);

    $this->actingAs(usuarioDoKit('master_global'));

    Livewire::test(EditTenant::class, ['record' => $organizacao->getRouteKey()])
        ->assertSchemaComponentVisible('unifica_logo')
        ->assertSchemaComponentHidden('logo_dark')
        ->fillForm(['unifica_logo' => false])
        ->assertSchemaComponentVisible('logo_dark');

    config(['kit.identidade.unifica_logo_marca' => true]);

    Livewire::test(EditTenant::class, ['record' => $organizacao->getRouteKey()])
        ->assertSchemaComponentHidden('unifica_logo')
        ->assertSchemaComponentHidden('logo_dark');
})->group('kit');

/**
 * CT-03 — a gravação por componente persiste o toggle.
 *
 * Uma tela aberta não é uma tela que grava: o fillForm + save do Livewire cobre o
 * caminho inteiro, e o `fresh()` lê o que de fato chegou ao banco.
 */
it('[CT-03] grava o toggle pela tela do tenant', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    config(['kit.tenancy.enabled' => true]);
    config(['kit.identidade.unifica_logo_marca' => false]);
    noPainelDoShield('admin');
    noPainelBootado('admin');

    $organizacao = Tenant::factory()->create(['unifica_logo' => true]);

    $this->actingAs(usuarioDoKit('master_global'));

    Livewire::test(EditTenant::class, ['record' => $organizacao->getRouteKey()])
        ->fillForm(['unifica_logo' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($organizacao->fresh()->unifica_logo)->toBeFalse();
})->group('kit');

// --- a regra do par ------------------------------------------------------------

/**
 * CT-04 — organização unificada ignora a `logo_dark` que ela tenha gravada.
 *
 * O mutante é o `||` virar `&&`: com a marca global separada e o flag ligado,
 * `escura` tem de ser `null` mesmo com `logo_dark` presente — é a clara dela que
 * serve os dois temas (RQ-03).
 */
it('[CT-04] organizacao unificada ignora a logo dark gravada', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('organizacoes/logos/a.png', 'png');
    Storage::disk('public')->put('organizacoes/logos/b.png', 'png');
    Storage::disk('public')->put('kit/d.png', 'png');

    config(['kit.identidade.unifica_logo_marca' => false]);
    config(['kit.identidade.logo_dark' => 'kit/d.png']);

    $organizacao = Tenant::factory()->comIdentidadeVisual(logo: 'organizacoes/logos/a.png', logoEscura: 'organizacoes/logos/b.png', unifica: true)->create();

    $par = IdentidadeDoKit::logosPara($organizacao);

    expect($par['clara'])->toBeString()->toContain('organizacoes/logos/a.png')
        ->and($par['escura'])->toBeNull();
})->group('kit');

/**
 * CT-05 — separada e sem `logo_dark`, a escura cai na da instalação (Q4 — compat).
 *
 * É o comportamento publicado: a queda por variante não muda. O flag desligado
 * explicitamente é o que distingue este caso do CT-04 — sem o flag na regra, os
 * dois virariam o mesmo caminho.
 */
it('[CT-05] organizacao separada sem logo dark cai para a da instalacao', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('organizacoes/logos/a.png', 'png');
    Storage::disk('public')->put('kit/d.png', 'png');

    config(['kit.identidade.unifica_logo_marca' => false]);
    config(['kit.identidade.logo_dark' => 'kit/d.png']);

    $organizacao = Tenant::factory()->comIdentidadeVisual(logo: 'organizacoes/logos/a.png', unifica: false)->create();

    $par = IdentidadeDoKit::logosPara($organizacao);

    expect($par['clara'])->toBeString()->toContain('organizacoes/logos/a.png')
        ->and($par['escura'])->toBeString()->toContain('kit/d.png');
})->group('kit');

/**
 * CT-06 — separada com `logo_dark`, a escura é a da organização.
 *
 * O caminho que já existia, agora com o flag explicitamente desligado: se a
 * consulta ao flag inverter (`! unifica_logo` no lugar certo mas com a leitura
 * trocada), este caso e o CT-04 morrem juntos.
 */
it('[CT-06] organizacao separada com logo dark usa a dela', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('organizacoes/logos/a.png', 'png');
    Storage::disk('public')->put('organizacoes/logos/b.png', 'png');

    config(['kit.identidade.unifica_logo_marca' => false]);

    $organizacao = Tenant::factory()->comIdentidadeVisual(logo: 'organizacoes/logos/a.png', logoEscura: 'organizacoes/logos/b.png', unifica: false)->create();

    $par = IdentidadeDoKit::logosPara($organizacao);

    expect($par['escura'])->toBeString()->toContain('organizacoes/logos/b.png');
})->group('kit');

/**
 * CT-06b — organização unificada SEM logo clara não suprime a escura da instalação.
 *
 * O flag decide sobre as logos DELA: sem clara própria resolvível, não há o que
 * unificar — `escura` segue a queda para a instalação. O mutante é tirar a cláusula
 * `urlDaLogo() !== null` do `logosPara()`: este caso deixa de devolver a dark da
 * instalação e o dark da organização sem logo some da tela.
 */
it('[CT-06b] unificada sem logo clara nao suprime a escura da instalacao', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('kit/d.png', 'png');

    config(['kit.identidade.unifica_logo_marca' => false]);
    config(['kit.identidade.logo_dark' => 'kit/d.png']);

    $organizacao = Tenant::factory()->create(['unifica_logo' => true]);

    $par = IdentidadeDoKit::logosPara($organizacao);

    expect($par['escura'])->toBeString()->toContain('kit/d.png');
})->group('kit');

/**
 * CT-06c — unificada com a clara órfã também cai na escura da instalação.
 *
 * Mesma cláusula pelo outro lado: a coluna declarada mas o arquivo fora do disco
 * equivale a "sem clara" — `urlDaLogo()` devolve `null` e o flag fica mudo.
 */
it('[CT-06c] unificada com a clara orfa cai na escura da instalacao', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('kit/d.png', 'png');

    config(['kit.identidade.unifica_logo_marca' => false]);
    config(['kit.identidade.logo_dark' => 'kit/d.png']);

    $organizacao = Tenant::factory()->create(['logo' => 'organizacoes/logos/sumiu.png', 'unifica_logo' => true]);

    $par = IdentidadeDoKit::logosPara($organizacao);

    expect($par['escura'])->toBeString()->toContain('kit/d.png');
})->group('kit');

// --- a segunda superfície ------------------------------------------------------

/**
 * CT-07 — a lock screen não emite `<img>` dark para organização unificada.
 *
 * P-03: a regra vale nas duas superfícies porque as duas leem `logosPara()`.
 * `fi-logo-dark` ausente é o oráculo — com ele, a clara cobre os dois temas.
 */
it('[CT-07] lock screen nao emite img dark para organizacao unificada', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('organizacoes/logos/a.png', 'png');
    Storage::disk('public')->put('kit/d.png', 'png');

    config(['kit.identidade.unifica_logo_marca' => false]);
    config(['kit.identidade.logo_dark' => 'kit/d.png']);

    $organizacao = Tenant::factory()->comIdentidadeVisual('#7c3aed', 'organizacoes/logos/a.png', null, null, true)->create();

    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->actingAs(usuarioDoKit('master_global'));
    session(['lockscreen' => true]);
    session(['tenant_corrente' => $organizacao->getKey()]);

    $html = $this->get(route('lockscreen.app.page'))->assertOk()->getContent();

    expect($html)->toContain('organizacoes/logos/a.png')
        ->and($html)->not->toContain('fi-logo-dark')
        ->and($html)->not->toContain('kit/d.png');
})->group('kit');

// --- costura de teste -----------------------------------------------------------

/**
 * CT-08 — a factory escreve o flag só quando pedida.
 *
 * `unifica: null` (omitido) não pode mandar `null` para a coluna — ela é
 * NOT NULL e o default `true` é do banco, não do estado da factory.
 */
it('[CT-08] factory respeita o parametro unifica', function (): void {
    $padrao   = Tenant::factory()->comIdentidadeVisual()->create();
    $separada = Tenant::factory()->comIdentidadeVisual('#7c3aed', 'organizacoes/logos/x.png', null, null, false)->create();

    expect($padrao->fresh()->unifica_logo)->toBeTrue()
        ->and($separada->fresh()->unifica_logo)->toBeFalse();
})->group('kit');

// --- documentação ----------------------------------------------------------------

/**
 * CT-09 — a documentação menciona o toggle nos dois idiomas.
 *
 * A página certa é a de configurações do kit (a identidade visual por tema mora
 * na seção da logo da marca), nos dois idiomas.
 */
it('[CT-09] documenta o toggle de unificar logo da organizacao nos dois idiomas', function (string $idioma, string $trecho): void {
    $pagina = file_get_contents(base_path("docs/{$idioma}/recursos/configuracoes-do-kit.md"));

    expect($pagina)->toContain($trecho);
})->with([
    'pt' => ['pt', 'no formulário dela: **Uma logo só, nos dois temas**'],
    'en' => ['en', 'on its own form: **One logo for both themes**'],
])->skip(fn (): bool => ! naArvoreDoKit(), 'O kit:update e o create-project nao entregam docs/ (export-ignore): a pagina que este caso le nao viaja.')->group('kit');

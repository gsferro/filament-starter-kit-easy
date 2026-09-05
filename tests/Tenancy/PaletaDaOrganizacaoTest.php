<?php

use App\Filament\Admin\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use App\Models\Tenant;
use App\Support\CustomizadorDaInstalacao;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord;

/**
 * A paleta do Filament na identidade visual da organização — a mesma escolha do settings do kit.
 *
 * IDs de CT em `wikis/specs/feat/paleta-do-filament-na-organizacao/…/04-casos-de-teste.md`.
 *
 * O que se afirma é a PALETA registrada em `FilamentColor`, como a wiki ancestral faz para a cor
 * livre — não o pixel. A regra de precedência é a do kit (`CorPrimaria::resolver()`), e é isso que
 * a tabela de decisão de CT-04 prova sobre as duas colunas da organização.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/** Quem edita organizações: master_global no /admin, com o painel corrente para o schema resolver. */
function administradoraNoAdmin(): void
{
    Filament::setCurrentPanel('admin');
    test()->actingAs(usuarioComPapel('master_global'));
}

/** A paleta primária registrada depois de abrir o painel da organização, medida do zero. */
function paletaAposAbrir(string $slug): array
{
    fronteiraDeRequest();

    test()->get("/app/{$slug}")->assertSuccessful();

    return FilamentColor::getColors()['primary'];
}

/*
|--------------------------------------------------------------------------
| R1 — o Select oferece exatamente a lista do kit, grava o que está nela e recusa o que não está
|--------------------------------------------------------------------------
*/

/** CT-01 — a gravação por componente; `fresh()` porque é o banco que tem de responder ($fillable). */
it('[CT-01] a administradora escolhe uma cor da paleta e ela fica gravada', function (): void {
    $organizacao = Tenant::factory()->create(['nome' => 'Acme', 'slug' => 'acme']);

    administradoraNoAdmin();

    Livewire::test(EditTenant::class, ['record' => $organizacao->getRouteKey()])
        ->fillForm(['cor_primaria_nome' => 'Blue'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($organizacao->fresh())
        ->cor_primaria_nome->toBe('Blue')
        ->cor_primaria->toBeNull();
});

/**
 * CT-02 — fora da lista não grava. `Zinc` é a linha que separa "existe em `Color`" de "está na
 * lista do kit"; `blue` separa `in()` estrito de comparação frouxa. O não-efeito é obrigatório.
 */
it('[CT-02] o que nao esta na lista nao grava', function (string $valor): void {
    $organizacao = Tenant::factory()->create(['nome' => 'Acme', 'slug' => 'acme']);

    administradoraNoAdmin();

    Livewire::test(EditTenant::class, ['record' => $organizacao->getRouteKey()])
        ->fillForm(['cor_primaria_nome' => $valor])
        ->call('save')
        ->assertHasFormErrors(['cor_primaria_nome']);

    expect($organizacao->fresh()->cor_primaria_nome)->toBeNull();
})->with([
    'nome fora da lista'                         => 'Roxo',
    'caixa diferente'                            => 'blue',
    'cor que existe no Filament, fora da lista'  => 'Zinc',
]);

/** CT-03 — a lista é a do kit, nome a nome e na mesma ordem; e cada nome é uma constante de `Color`. */
it('[CT-03] a lista oferecida e a lista do kit, nome a nome', function (): void {
    administradoraNoAdmin();

    Livewire::test(CreateTenant::class)
        ->assertSchemaComponentExists(
            'cor_primaria_nome',
            'form',
            fn (Select $select): bool => array_keys($select->getOptions()) === CustomizadorDaInstalacao::CORES,
        );

    foreach (CustomizadorDaInstalacao::CORES as $nome) {
        expect(defined(Color::class.'::'.$nome))->toBeTrue("`{$nome}` não é uma constante de Color");
    }
});

/** CT-06 — verbo irmão não herda evidência: a criação grava paleta e hex juntos. */
it('[CT-06] a criacao grava paleta e cor livre juntas', function (): void {
    administradoraNoAdmin();

    Livewire::test(CreateTenant::class)
        ->fillForm([
            'nome'              => 'Acme',
            'slug'              => 'acme',
            'ativo'             => true,
            'cor_primaria_nome' => 'Emerald',
            'cor_primaria'      => '#059669',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Tenant::where('slug', 'acme')->firstOrFail())
        ->cor_primaria_nome->toBe('Emerald')
        ->cor_primaria->toBe('#059669');
});

/*
|--------------------------------------------------------------------------
| R2 — no /app/{slug} a paleta aplicada segue a regra do kit
|--------------------------------------------------------------------------
*/

/**
 * CT-04 — a tabela de decisão hex × nome, medida na paleta registrada depois de abrir o painel.
 *
 * As duas últimas linhas só existem porque a factory grava direto: o formulário recusa os dois
 * valores (CT-02 e o caso herdado `recusa cor fora do formato hexadecimal`). São elas que provam
 * "mesmo inválido": hoje `lixo` em `cor_primaria` chegava a `generatePalette()` e o painel ficava
 * acromático; com a regra do kit cai para a paleta. E o `assertSuccessful()` é o que separa "cai
 * para o padrão" de "derruba o painel".
 *
 * O default é MEDIDO antes de qualquer request, como a ancestral faz: a cor da aplicação pode ser
 * a do kit (config), não o âmbar.
 */
it('[CT-04] a paleta registrada no /app e decidida pelas duas colunas', function (?string $hex, ?string $nome, string $esperado): void {
    $organizacao = Tenant::factory()->comIdentidadeVisual($hex, null, $nome)->create(['nome' => 'Acme', 'slug' => 'acme']);
    $usuario     = usuarioComPapel('panel_user', $organizacao);
    $usuario->tenants()->attach($organizacao);

    fronteiraDeRequest();
    $default = FilamentColor::getColors()['primary'];

    $this->actingAs($usuario);

    $paleta = paletaAposAbrir('acme');

    $esperada = match ($esperado) {
        'hex'     => Color::generatePalette((string) $hex),
        'paleta'  => Color::Blue,
        'default' => $default,
    };

    expect($paleta)->toBe($esperada);

    if ($esperado !== 'default') {
        expect($paleta)->not->toBe($default);
    }
})->with([
    'os dois: hex vence'                            => ['#059669', 'Blue', 'hex'],
    'so a paleta — o novo'                          => [null, 'Blue', 'paleta'],
    'so o hex — regressao da cor livre'             => ['#059669', null, 'hex'],
    'nada: neutro'                                  => [null, null, 'default'],
    'nome inexistente gravado direto'               => [null, 'Roxo', 'default'],
    'hex invalido gravado direto cai para a paleta' => ['lixo', 'Blue', 'paleta'],
]);

/*
|--------------------------------------------------------------------------
| R4 — a aplicação da cor deixa registro com a fonte usada
|--------------------------------------------------------------------------
*/

/** CT-07 — o `debug` do channel `tenancy` diz de onde a cor veio. Handler trocado, como a ancestral. */
it('[CT-07] o registro de cor aplicada diz a fonte', function (?string $hex, string $nome, string $fonte): void {
    $organizacao = Tenant::factory()->comIdentidadeVisual($hex, null, $nome)->create(['nome' => 'Acme', 'slug' => 'acme']);
    $usuario     = usuarioComPapel('panel_user', $organizacao);
    $usuario->tenants()->attach($organizacao);

    $registros = new TestHandler;
    Log::channel('tenancy')->getLogger()->setHandlers([$registros]);

    $this->actingAs($usuario);
    paletaAposAbrir('acme');

    expect($registros->hasDebugThatPasses(
        fn (LogRecord $registro): bool => str_starts_with($registro->message, '[AppPanelProvider@bootUsing] Cor da organização aplicada')
            && ($registro->context['tenant_id'] ?? null) === $organizacao->getKey()
            && ($registro->context['fonte'] ?? null) === $fonte,
    ))->toBeTrue("Nenhum debug do channel `tenancy` registrou a fonte `{$fonte}`.");
})->with([
    'paleta' => [null, 'Blue', 'paleta'],
    'hex'    => ['#059669', 'Blue', 'hex'],
]);

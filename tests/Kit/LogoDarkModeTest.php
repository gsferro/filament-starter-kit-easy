<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit as PaginaDeConfiguracoes;
use App\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use App\Models\Tenant;
use App\Support\IdentidadeDoKit;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * A logo da marca — e a da organização — para o modo escuro, com o toggle que
 * separa (ou unifica) os dois campos.
 *
 * Os IDs de CT são os de
 * `wikis/specs/feat/logo-dark-mode/logo-dark-mode/04-casos-de-teste.md`, mais CT-40, CT-41, CT-54 e
 * CT-55 de `wikis/specs/fix/logo-dark-do-tenant/logo-dark-do-tenant/04-casos-de-teste.md` (a regra
 * única do par de logos, a tela de bloqueio e a documentação).
 */

// --- R1 — logo_dark da organização -------------------------------------------

/**
 * CT-01 a CT-03 — a URL da variante escura, e os DOIS jeitos de não ter uma.
 *
 * Mesma guarda de `urlDaLogo()` (CT-02 de `IdentidadeVisualTest`): o path órfão
 * — arquivo declarado e ausente no disco — degrada para a logo_dark da instalação
 * na resolução do par, nunca para um `<img>` quebrado. `create()` separado por
 * atributo, para o mutante "resolve `$this->logo` em vez de `$this->logo_dark`"
 * morrer no caminho que devolve o campo certo.
 */
it('[CT-01][CT-02][CT-03] resolve a url da logo dark pelo disk publico', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('organizacoes/logos/acme.png', 'png');
    Storage::disk('public')->put('organizacoes/logos/acme-dark.png', 'png');

    $comPar   = Tenant::factory()->create([
        'logo'      => 'organizacoes/logos/acme.png',
        'logo_dark' => 'organizacoes/logos/acme-dark.png',
    ]);
    $semDark  = Tenant::factory()->create(['logo' => 'organizacoes/logos/acme.png']);
    $comOrfao = Tenant::factory()->create(['logo_dark' => 'organizacoes/logos/sumiu.png']);

    expect($comPar->urlDaLogoEscura())->toContain('organizacoes/logos/acme-dark.png')
        // dark ≠ clara: o mutante que resolve `$this->logo` morre aqui
        ->and($comPar->urlDaLogoEscura())->not->toBe($comPar->urlDaLogo())
        ->and($semDark->urlDaLogoEscura())->toBeNull()
        ->and($comOrfao->urlDaLogoEscura())->toBeNull();
})->group('kit');

/**
 * CT-04 — `logo_dark` é atributo atribuível em massa.
 *
 * Sem ele no `$fillable`, o `create()` descarta o campo em silêncio e a feature
 * morre sem erro nenhum — o mesmo motivo do CT-01 de `IdentidadeVisualTest`.
 * `fresh()` e não o objeto em memória: é o banco que responde.
 */
it('[CT-04] guarda logo dark da organizacao em create', function (): void {
    $organizacao = Tenant::create([
        'nome'      => 'Acme',
        'slug'      => 'acme',
        'ativo'     => true,
        'logo'      => 'organizacoes/logos/acme.png',
        'logo_dark' => 'organizacoes/logos/acme-dark.png',
    ]);

    expect($organizacao->fresh())->logo_dark->toBe('organizacoes/logos/acme-dark.png');
})->group('kit');

// --- R2 — o toggle nasce unificado e persiste --------------------------------

/**
 * CT-05 — instalação nova tem a marca unificada.
 *
 * O `true` é literal do requisito (RQ-02): instalação existente não pode mudar
 * de aparência por causa de um update do kit — a separação é gesto na tela.
 */
it('[CT-05] nasce com a marca unificada', function (): void {
    expect(configuracaoGravada('unifica_logo_marca'))->toBeTrue()
        ->and(config('kit.identidade.unifica_logo_marca'))->toBeTrue();
})->group('kit');

/**
 * CT-06 — desligar o toggle na página persiste e alinha a config.
 *
 * A segunda asserção é a que mata o mutante "propriedade declarada mas fora do
 * `mapaDeConfiguracao()`": a settings gravava e a `kit.identidade.*` ficava
 * `true` para sempre — a tela prometia a separação e a resolução nunca a lia.
 */
it('[CT-06] grava o toggle pela tela e alinha a config', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->actingAs(usuarioDoKit('admin'));

    Livewire::test(PaginaDeConfiguracoes::class)
        ->fillForm(['unifica_logo_marca' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(configuracaoGravada('unifica_logo_marca'))->toBeFalse();

    alinharConfiguracoesDoKit();

    expect(config('kit.identidade.unifica_logo_marca'))->toBeFalse();
})->group('kit');

// --- R3 — unificado torna logo_dark inerte ------------------------------------

/**
 * CT-07 — logo_dark gravada é ignorada com a marca unificada.
 *
 * O mutante: `logoEscura()` que devolve `doDisco()` sem olhar o toggle. Com a
 * marca unificada, o esperado é `null` — a variante escura não existe.
 */
it('[CT-07] ignora a logo dark com a marca unificada', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('kit/logo-dark.png', 'png');
    config(['kit.identidade.logo_dark' => 'kit/logo-dark.png']);

    expect(IdentidadeDoKit::unificaLogo())->toBeTrue()
        ->and(IdentidadeDoKit::logoEscura())->toBeNull();
})->group('kit');

/**
 * CT-08 — o campo logo_dark some da página de settings no modo unificado.
 *
 * Campo exibido para algo inerte é promessa quebrada (D2). A segunda asserção —
 * visível com o toggle desligado — é o que mata o mutante "escondido sempre",
 * que reprovaria CT-10/13 mas passaria na metade esquerda sozinha.
 */
it('[CT-08] mostra e esconde o campo logo dark nas configuracoes pelo toggle', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->actingAs(usuarioDoKit('admin'));

    Livewire::test(PaginaDeConfiguracoes::class)
        ->assertSchemaComponentHidden('logo_dark')
        ->fillForm(['unifica_logo_marca' => false])
        ->assertSchemaComponentVisible('logo_dark');
})->group('kit');

/**
 * CT-09 — o campo logo_dark some do formulário da organização no modo unificado.
 *
 * A flag de tenancy é ligada no arranjo: a suíte Kit roda sem tenancy
 * (`IdentidadeVisualTest`, CT-04), e o `TenantResource` só abre a porta com ela.
 */
it('[CT-09] mostra e esconde o campo logo dark no formulario da organizacao pelo modo', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    config(['kit.tenancy.enabled' => true]);
    noPainelDoShield('admin');
    noPainelBootado('admin');

    $organizacao = Tenant::factory()->create();

    $this->actingAs(usuarioDoKit('master_global'));

    Livewire::test(EditTenant::class, ['record' => $organizacao->getRouteKey()])
        ->assertSchemaComponentHidden('logo_dark');

    config(['kit.identidade.unifica_logo_marca' => false]);

    Livewire::test(EditTenant::class, ['record' => $organizacao->getRouteKey()])
        ->assertSchemaComponentVisible('logo_dark');
})->group('kit');

// --- R4 — em modo separado, logo e logo_dark são obrigatórios entre si --------

/**
 * CT-10 — grava sem exigir o par quando a regra não se aplica.
 *
 * As duas células que os mutantes "required incondicional" e "par exigido sempre
 * no separado" matam: unificado com logo e separado com os dois vazios salvam.
 */
it('[CT-10] grava sem exigir o par fora da regra', function (bool $unifica, array $logo): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->actingAs(usuarioDoKit('admin'));

    Livewire::test(PaginaDeConfiguracoes::class)
        // FileUpload guarda estado como `uuid => caminho` — array mesmo com um arquivo
        ->fillForm([
            'unifica_logo_marca' => $unifica,
            'logo'               => $logo,
            'logo_dark'          => [],
        ])
        ->call('save')
        ->assertHasNoFormErrors(['logo', 'logo_dark']);
})->with([
    'unificado com logo'          => [true, ['kit/logo.png']],
    'separado com os dois vazios' => [false, []],
])->group('kit');

/**
 * CT-11 e CT-12 — um sem o outro é recusado, nos dois sentidos.
 *
 * O mutante é a regra de um lado só (`required_with` esquecido no irmão): só um
 * dos dois cenários morre por ele, então os dois estão aqui. "Nenhum valor novo
 * persistido" lê a settings ANTERIOR — validar e gravar na mesma linha deixa a
 * metade errada vazar.
 */
it('[CT-11][CT-12] recusa um campo de logo sem o par no modo separado', function (array $campos, string $campoErro): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->actingAs(usuarioDoKit('admin'));

    gravarConfiguracao('logo', 'kit/antes.png');
    gravarConfiguracao('logo_dark', 'kit/antes-dark.png');

    Livewire::test(PaginaDeConfiguracoes::class)
        // FileUpload guarda estado como `uuid => caminho` — array mesmo com um arquivo
        ->fillForm(array_merge(['unifica_logo_marca' => false], $campos))
        ->call('save')
        ->assertHasFormErrors([$campoErro => 'required']);

    expect(configuracaoGravada('logo'))->toBe('kit/antes.png')
        ->and(configuracaoGravada('logo_dark'))->toBe('kit/antes-dark.png');
})->with([
    'só a clara preenchida'  => [['logo' => ['kit/nova.png'], 'logo_dark' => []], 'logo_dark'],
    'só a escura preenchida' => [['logo' => [], 'logo_dark' => ['kit/nova-dark.png']], 'logo'],
])->group('kit');

/**
 * CT-13 — o par completo grava as duas entradas.
 *
 * Upload de verdade (`UploadedFile::fake`) e não path de mentira: é a gravação
 * por componente que cobre o gate de tela de escrita, e a prova de que o campo
 * separado aceita os dois arquivos numa só volta.
 */
it('[CT-13] grava o par de logos no modo separado', function (): void {
    Storage::fake('public');

    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->actingAs(usuarioDoKit('admin'));

    Livewire::test(PaginaDeConfiguracoes::class)
        ->fillForm([
            'unifica_logo_marca' => false,
            'logo'               => UploadedFile::fake()->image('logo.png'),
            'logo_dark'          => UploadedFile::fake()->image('logo-dark.png'),
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $clara  = configuracaoGravada('logo');
    $escura = configuracaoGravada('logo_dark');

    expect($clara)->toBeString()
        ->and($escura)->toBeString()
        ->and(Storage::disk('public')->exists($clara))->toBeTrue()
        ->and(Storage::disk('public')->exists($escura))->toBeTrue();
})->group('kit');

// --- R5/R8 — markup da mídia na lock-screen -----------------------------------

/**
 * CT-14 — a logo renderiza contida e sem a classe da arte.
 *
 * O oráculo tem de ser `class="fi-auth-media"` EXATO: o layout envolve a mídia
 * em `fi-auth-media-wrapper`/`overlay`/`content` e um `assertDontSee('fi-auth-media')`
 * solto falharia até com a logo certa na tela. `object-fit:contain` é a marca do
 * bloco-logo do override — a arte usa `cover` pela classe do pacote.
 */
it('[CT-14] renderiza a logo contida e sem a classe da arte na lock screen', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('organizacoes/logos/acme.png', 'png');

    $organizacao = Tenant::factory()->comIdentidadeVisual('#7c3aed', 'organizacoes/logos/acme.png')->create();

    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->actingAs(usuarioDoKit('master_global'));
    session(['lockscreen' => true]);
    session(['tenant_corrente' => $organizacao->getKey()]);

    $this->get(route('lockscreen.app.page'))
        ->assertOk()
        ->assertSee('object-fit:contain', false)
        ->assertSee('fi-logo', false)
        ->assertDontSee('class="fi-auth-media"', false);
})->group('kit');

/**
 * CT-15 — sem logo nenhuma, a arte mantém o markup original.
 *
 * O mutante é o override decidindo "é lock-screen então é logo" e aplicando o
 * bloco de logo na arte de fallback — `cover` nela é o acabamento correto.
 */
it('[CT-15] mantem a arte com a classe original sem logo nenhuma', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->actingAs(usuarioDoKit('master_global'));
    session(['lockscreen' => true]);
    session(['tenant_corrente' => Tenant::factory()->create()->getKey()]);

    $this->get(route('lockscreen.app.page'))
        ->assertOk()
        ->assertSee('class="fi-auth-media"', false);
})->group('kit');

// --- R6 — resolução por variante com override do tenant ------------------------

/**
 * CT-16 — o par exibido na lock-screen segue a tabela de decisão.
 *
 * A marca do topo NÃO aparece nesta tela (a lock-screen é `SimplePage` dentro do
 * layout do auth-designer e não emite `x-filament-panels::logo`), então cada URL
 * de logo no HTML vem de um emissor só — a mídia. `substr_count >= 1` já é o
 * oráculo forte: media errada é `0`, e `fi-logo-dark` ausente é "sem escura".
 *
 * O `Quando` é o render da tela — a resolução inteira acontece ali, sem ação.
 */
it('[CT-16] exibe na lock screen o par resolvido por variante', function (bool $separado, ?string $logoOrg, ?string $darkOrg, ?string $logoKit, ?string $darkKit, string $clara, ?string $escura): void {
    Storage::fake('public');

    config(['kit.identidade.unifica_logo_marca' => ! $separado]);
    config(['kit.identidade.logo' => $logoKit]);
    config(['kit.identidade.logo_dark' => $darkKit]);

    foreach (array_filter([$logoOrg, $darkOrg, $logoKit, $darkKit]) as $caminho) {
        Storage::disk('public')->put($caminho, 'png');
    }

    $organizacao = Tenant::factory()->create(['logo' => $logoOrg, 'logo_dark' => $darkOrg]);

    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->actingAs(usuarioDoKit('master_global'));
    session(['lockscreen' => true]);
    session(['tenant_corrente' => $organizacao->getKey()]);

    $resposta = $this->get(route('lockscreen.app.page'))->assertOk();
    $html     = $resposta->getContent();

    expect(substr_count($html, $clara))->toBeGreaterThanOrEqual(1)
        ->and(substr_count($html, 'object-fit:contain'))->toBeGreaterThanOrEqual(1);

    if ($escura === null) {
        expect($html)->not->toContain('fi-logo-dark');
    } else {
        expect(substr_count($html, $escura))->toBeGreaterThanOrEqual(1)
            ->and($html)->toContain('fi-logo-dark');
    }
})->with([
    'unificado ignora as escuras'          => [false, 'organizacoes/logos/a.png', 'organizacoes/logos/b.png', 'kit/c.png', 'kit/d.png', 'organizacoes/logos/a.png', null],
    'separado usa o par da organizacao'    => [true, 'organizacoes/logos/a.png', 'organizacoes/logos/b.png', 'kit/c.png', 'kit/d.png', 'organizacoes/logos/a.png', 'organizacoes/logos/b.png'],
    'escura cai para a do kit'             => [true, 'organizacoes/logos/a.png', null, 'kit/c.png', 'kit/d.png', 'organizacoes/logos/a.png', 'kit/d.png'],
    'sem organizacao vale o kit inteiro'   => [true, null, null, 'kit/c.png', 'kit/d.png', 'kit/c.png', 'kit/d.png'],
    'sem escura em ninguem, so a clara'    => [true, 'organizacoes/logos/a.png', null, 'kit/c.png', null, 'organizacoes/logos/a.png', null],
])->group('kit');

// --- R7 — a marca do topo ------------------------------------------------------

/**
 * CT-17 e CT-18 — a página de login traz (ou não) a variante escura.
 *
 * O mecanismo é o `darkModeBrandLogo` nativo — o que o kit promete é a CHAVE
 * alimentada certa: `logoEscura()` só devolve caminho no modo separado. O login
 * é a superfície pública onde `page.simple` emite `x-filament-panels::logo` sem
 * sessão nenhuma.
 */
it('[CT-17] traz a logo dark no login quando a marca e separada', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('kit/logo.png', 'png');
    Storage::disk('public')->put('kit/logo-dark.png', 'png');

    config(['kit.identidade.unifica_logo_marca' => false]);
    config(['kit.identidade.logo' => 'kit/logo.png']);
    config(['kit.identidade.logo_dark' => 'kit/logo-dark.png']);

    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('kit/logo-dark.png', false)
        ->assertSee('fi-logo-dark', false);
})->group('kit');

it('[CT-18] nao traz logo dark no login com a marca unificada', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('kit/logo.png', 'png');
    Storage::disk('public')->put('kit/logo-dark.png', 'png');

    config(['kit.identidade.logo' => 'kit/logo.png']);
    config(['kit.identidade.logo_dark' => 'kit/logo-dark.png']);

    $this->get('/admin/login')
        ->assertOk()
        ->assertDontSee('kit/logo-dark.png', false)
        ->assertDontSee('fi-logo-dark', false);
})->group('kit');

// --- R8 — alt com o nome da organização -----------------------------------------

/**
 * CT-19 — a `<img>` da logo carrega o nome da organização no alt.
 *
 * Acessibilidade (P-05): leitor de tela na lock-screen nomeia a marca que a
 * pessoa está desbloqueando.
 */
it('[CT-19] carrega o nome da organizacao no alt da logo', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('organizacoes/logos/acme.png', 'png');

    $organizacao = Tenant::factory()->comIdentidadeVisual('#7c3aed', 'organizacoes/logos/acme.png')->create(['nome' => 'Acme Brasil']);

    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->actingAs(usuarioDoKit('master_global'));
    session(['lockscreen' => true]);
    session(['tenant_corrente' => $organizacao->getKey()]);

    $this->get(route('lockscreen.app.page'))
        ->assertOk()
        ->assertSee('alt="Acme Brasil"', false);
})->group('kit');

// --- R9 — documentação e assets -------------------------------------------------

/**
 * CT-20 — a página de configurações documenta o recurso nos dois idiomas, e os
 * screenshots referenciados existem.
 *
 * Arquivo próprio por idioma, e não `documentacaoDoKit()` agregado: a asserção
 * tem de apontar ONDE a seção mora, não só que a frase existe em algum .md. Os
 * assets seguem a convenção das docs — `art/` na raiz, servidos por raw do
 * GitHub —, e a asserção no arquivo é o que pega markdown quebrado para uma
 * imagem que existe.
 */
it('[CT-20] documenta a logo light dark com screenshots nos dois idiomas', function (string $idioma): void {
    $pagina = file_get_contents(base_path("docs/{$idioma}/recursos/configuracoes-do-kit.md"));

    expect($pagina)
        ->toContain('logo-tema-claro.png')
        ->toContain('logo-tema-escuro.png')
        ->and(file_exists(base_path('art/logo-tema-claro.png')))->toBeTrue()
        ->and(file_exists(base_path('art/logo-tema-escuro.png')))->toBeTrue();
})->with(['pt', 'en'])->skip(fn (): bool => ! naArvoreDoKit(), 'O kit:update e o create-project nao entregam docs/ (export-ignore): a pagina que este caso le nao viaja.')->group('kit');

// --- fix/logo-dark-do-tenant — a regra única do par de logos ---------------------

/**
 * Os `<img>` do swap nativo (`fi-logo-light` e `fi-logo-dark`), por variante, da página inteira.
 *
 * @return array{light: list<string>, dark: list<string>}
 */
function imagensPorVarianteDaTelaDeBloqueio(string $html): array
{
    preg_match_all('~<img\b[^>]*>~i', $html, $tags);

    $imagens = ['light' => [], 'dark' => []];

    foreach ($tags[0] as $tag) {
        if (preg_match('~\sclass\s*=\s*"([^"]*)"~', $tag, $classe) !== 1 || preg_match('~\ssrc\s*=\s*"([^"]*)"~', $tag, $src) !== 1) {
            continue;
        }

        $classes = preg_split('~\s+~', $classe[1], flags: PREG_SPLIT_NO_EMPTY) ?: [];

        if (in_array('fi-logo-light', $classes, true)) {
            $imagens['light'][] = $src[1];
        }

        if (in_array('fi-logo-dark', $classes, true)) {
            $imagens['dark'][] = $src[1];
        }
    }

    return $imagens;
}

/**
 * CT-40 — o par resolvido para uma organização, por variante e de forma independente.
 *
 * A letra é o dono da logo: A/B da organização (clara/escura), C/D da instalação. `null` é a
 * coluna vazia, `''` o branco e `X!` o órfão (coluna preenchida, arquivo fora do disco).
 * "nenhuma" é `null` estrito: nem string vazia nem URL.
 */
it('[CT-40] o par resolvido para uma organização segue a tabela de decisão por variante', function (
    bool $separado,
    ?string $orgClara,
    ?string $orgEscura,
    ?string $instClara,
    ?string $instEscura,
    ?string $clara,
    ?string $escura,
): void {
    Storage::fake('public');

    $caminhos = [
        'A' => 'organizacoes/logos/a.png',
        'B' => 'organizacoes/logos/b.png',
        'C' => 'kit/c.png',
        'D' => 'kit/d.png',
    ];

    $resolve = function (?string $codigo) use ($caminhos): ?string {
        if ($codigo === null || $codigo === '') {
            return $codigo;
        }

        $ausente = str_ends_with($codigo, '!');
        $caminho = $caminhos[rtrim($codigo, '!')];

        if (! $ausente) {
            Storage::disk('public')->put($caminho, 'png');
        }

        return $caminho;
    };

    config([
        'kit.identidade.unifica_logo_marca' => ! $separado,
        'kit.identidade.logo'               => $resolve($instClara),
        'kit.identidade.logo_dark'          => $resolve($instEscura),
    ]);

    $organizacao = Tenant::factory()->create([
        'logo'      => $resolve($orgClara),
        'logo_dark' => $resolve($orgEscura),
    ]);

    $par = IdentidadeDoKit::logosPara($organizacao);

    if ($clara === null) {
        expect($par['clara'])->toBeNull();
    } else {
        expect($par['clara'])->toBeString()->toContain($caminhos[$clara]);
    }

    if ($escura === null) {
        expect($par['escura'])->toBeNull();
    } else {
        expect($par['escura'])->toBeString()->toContain($caminhos[$escura]);
    }
})->with([
    'unificada ignora a escura da organização'              => [false, 'A', 'B', 'C', 'D', 'A', null],
    'unificada, só escura na organização'                   => [false, null, 'B', 'C', 'D', 'C', null],
    'par da organização'                                    => [true, 'A', 'B', 'C', 'D', 'A', 'B'],
    'só clara: escura cai na da instalação'                 => [true, 'A', null, 'C', 'D', 'A', 'D'],
    'branco = null'                                         => [true, 'A', '', 'C', 'D', 'A', 'D'],
    'escura órfã'                                           => [true, 'A', 'B!', 'C', 'D', 'A', 'D'],
    'clara órfã: cai na da instalação, escura segue'        => [true, 'A!', 'B', 'C', 'D', 'C', 'B'],
    'clara em branco = null'                                => [true, '', 'B', 'C', 'D', 'C', 'B'],
    'queda da escura não vira a clara da organização'       => [true, 'A', null, 'C', null, 'A', null],
    'sem logo nenhuma: par da instalação'                   => [true, null, null, 'C', 'D', 'C', 'D'],
    'só escura: clara da instalação, escura da organização' => [true, null, 'B', 'C', 'D', 'C', 'B'],
    'só escura e instalação sem clara'                      => [true, null, 'B', null, 'D', null, 'B'],
])->group('kit');

/** CT-41 — sem organização aberta o par é o da instalação, como sempre foi. */
it('[CT-41] o par resolvido sem organização é o da instalação', function (bool $separado, ?string $escura): void {
    Storage::fake('public');
    Storage::disk('public')->put('kit/c.png', 'png');
    Storage::disk('public')->put('kit/d.png', 'png');

    config([
        'kit.identidade.unifica_logo_marca' => ! $separado,
        'kit.identidade.logo'               => 'kit/c.png',
        'kit.identidade.logo_dark'          => 'kit/d.png',
    ]);

    $par = IdentidadeDoKit::logosPara(null);

    expect($par['clara'])->toBeString()->toContain('kit/c.png');

    if ($escura === null) {
        expect($par['escura'])->toBeNull();
    } else {
        expect($par['escura'])->toBeString()->toContain($escura);
    }
})->with([
    'separada: par da instalação' => [true, 'kit/d.png'],
    'unificada: só a clara'       => [false, null],
])->group('kit');

// --- R9 — a tela de bloqueio segue com a organização da sessão ------------------

/**
 * CT-54 — a organização da tela de bloqueio é a da sessão; a aberta no gerenciador (a fonte do
 * topo do `/app`) não a contamina.
 */
it('[CT-54] a tela de bloqueio mostra o par da organização da sessão e não o de outra aberta no gerenciador', function (): void {
    Storage::fake('public');

    foreach (['kit/logo-ct.png', 'kit/logo-dark-ct.png', 'organizacoes/logos/acme.png', 'organizacoes/logos/acme-dark.png', 'organizacoes/logos/globex.png', 'organizacoes/logos/globex-dark.png'] as $caminho) {
        Storage::disk('public')->put($caminho, 'png');
    }

    config([
        'kit.identidade.unifica_logo_marca' => false,
        'kit.identidade.logo'               => 'kit/logo-ct.png',
        'kit.identidade.logo_dark'          => 'kit/logo-dark-ct.png',
    ]);

    $acme   = Tenant::factory()->comIdentidadeVisual('#7c3aed', 'organizacoes/logos/acme.png', null, 'organizacoes/logos/acme-dark.png')->create(['slug' => 'acme']);
    $globex = Tenant::factory()->comIdentidadeVisual('#059669', 'organizacoes/logos/globex.png', null, 'organizacoes/logos/globex-dark.png')->create(['slug' => 'globex']);

    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->actingAs(usuarioDoKit('master_global'));
    session(['lockscreen' => true]);
    session(['tenant_corrente' => $acme->getKey()]);

    Filament::setCurrentPanel('app');
    Filament::setTenant($globex, isQuiet: true);

    $html    = $this->get(route('lockscreen.app.page'))->assertOk()->getContent();
    $imagens = imagensPorVarianteDaTelaDeBloqueio($html);

    expect($imagens['light'])->toHaveCount(1)
        ->and($imagens['light'][0])->toContain('organizacoes/logos/acme.png')
        ->and($imagens['dark'])->toHaveCount(1)
        ->and($imagens['dark'][0])->toContain('organizacoes/logos/acme-dark.png');

    foreach (['globex.png', 'globex-dark.png', 'kit/logo-ct.png', 'kit/logo-dark-ct.png'] as $fora) {
        $this->assertStringNotContainsString($fora, $html, "a tela de bloqueio aponta para {$fora}");
    }
})->group('kit');

// --- R11 — a documentação deixa de afirmar o que a mudança desmente -------------

/**
 * CT-55 — a seção do cabeçalho dos painéis, pt e en, não diz mais que a logo do topo é sempre
 * a da instalação nem que a organização só aparece na tela de bloqueio.
 *
 * Comparação sem negrito e sem quebra de linha: o texto das docs quebra no meio da frase.
 */
it('[CT-55] a documentação do cabeçalho dos painéis não afirma mais o que deixou de valer', function (string $idioma, string $titulo, string $organizacao, string $obsoleto, string $obsoletoBloqueio): void {
    $pagina = file_get_contents(base_path("docs/{$idioma}/recursos/configuracoes-do-kit.md"));
    $inicio = strpos($pagina, "## {$titulo}");

    expect($inicio)->not->toBeFalse();

    $resto = substr($pagina, $inicio + 3);
    $fim   = strpos($resto, "\n## ");
    $secao = $fim === false ? $resto : substr($resto, 0, $fim);

    $normaliza = static fn (string $texto): string => (string) preg_replace('~\s+~u', ' ', str_replace('**', '', $texto));

    $temAppJuntoDaOrganizacao = false;

    foreach (preg_split('~\n\s*\n~', $secao) ?: [] as $paragrafo) {
        $paragrafo = $normaliza($paragrafo);

        if (str_contains($paragrafo, '/app') && str_contains($paragrafo, $organizacao)) {
            $temAppJuntoDaOrganizacao = true;
        }
    }

    expect($temAppJuntoDaOrganizacao)->toBeTrue('a seção não fala de /app junto de '.$organizacao);

    $this->assertStringNotContainsString($obsoleto, $normaliza($secao));
    $this->assertStringNotContainsString($obsoletoBloqueio, $normaliza($secao));
})->with([
    'pt' => ['pt', 'Cabeçalho dos painéis: o que mostrar no topo', 'organização', 'sempre a da instalação', 'segue aparecendo só na tela de bloqueio'],
    'en' => ['en', 'Panel header: what to show at the top', 'organisation', "always the installation's", 'keeps showing only on the lock screen'],
])->skip(fn (): bool => ! naArvoreDoKit(), 'O kit:update e o create-project nao entregam docs/ (export-ignore): a pagina que este caso le nao viaja.')->group('kit');

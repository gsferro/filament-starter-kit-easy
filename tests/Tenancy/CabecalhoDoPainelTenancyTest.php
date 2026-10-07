<?php

use App\Models\Tenant;
use App\Models\User;
use App\Support\CabecalhoDoPainel;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Livewire\Sidebar;
use Filament\Livewire\Topbar;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * O cabeçalho dos painéis no que ele só significa com organização ativa (`/app/{slug}`).
 *
 * Os IDs de CT vêm de duas wikis: os CT-04, CT-11, CT-15, CT-27 e CT-29 a CT-31 são de
 * `wikis/specs/feat/cabecalho-do-painel/cabecalho-do-painel/04-casos-de-teste.md`, e os CT-42 a
 * CT-53, CT-56 e CT-57 (a logo da organização, clara e escura, no topo do `/app`) são de
 * `wikis/specs/fix/logo-dark-do-tenant/logo-dark-do-tenant/04-casos-de-teste.md`. O CT-49 desta
 * substitui o antigo CT-07, que afirmava a logo da instalação no `/app/acme` (aprovação do
 * solicitante no Adendo 1 daquela wiki).
 *
 * Aqui e não em `tests/Kit` porque `admin_app` e a organização na rota só existem com
 * `permission.teams` ligado, e `Tests\TenancyTestCase` o fixa antes das migrations.
 *
 * Toda asserção é feita DENTRO da região (`kit-cabecalho`, `kit-usuario`): o nome da
 * organização, o e-mail e o rótulo do papel já aparecem no menu do usuário e no seletor de
 * organização, e `assertSee` na página inteira passaria com a feature removida.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * Grava as opções do cabeçalho (e o nome do projeto discriminante) e alinha a config, pela
 * cadeia real settings → config.
 *
 * @param  array<string, mixed>  $opcoes  sufixo da propriedade `cabecalho_*` => valor
 */
function gravarCabecalhoDaTenancia(array $opcoes): void
{
    gravarConfiguracao('nome_da_aplicacao', 'Projeto Ômega');

    foreach ($opcoes as $sufixo => $valor) {
        gravarConfiguracao("cabecalho_{$sufixo}", $valor);
    }

    alinharConfiguracoesDoKit();
}

/**
 * A logo da instalação no disco `public` e na settings: a clara e a escura (`kit/logo-dark-ct.png`),
 * com a marca unificada (`$unifica`, a escura fica inerte) ou separada.
 */
function gravarLogoDaInstalacaoDaTenancia(bool $unifica = true): void
{
    Storage::fake('public');
    Storage::disk('public')->put('kit/logo-ct.png', 'png');
    Storage::disk('public')->put('kit/logo-dark-ct.png', 'png');

    gravarConfiguracao('logo', 'kit/logo-ct.png');
    gravarConfiguracao('logo_dark', 'kit/logo-dark-ct.png');
    gravarConfiguracao('unifica_logo_marca', $unifica);
    alinharConfiguracoesDoKit();
}

/**
 * Os `src` das `<img>` que o Filament desenha como marca (`fi-logo`).
 *
 * Para `brandLogo()` em string o Filament emite `<img class="fi-logo ...">`, que não tem
 * região (sem filho) — por isso a extração é por tag.
 *
 * @return list<string>
 */
function imagensDaMarcaDaTenancia(string $html): array
{
    preg_match_all('~<img\b[^>]*>~i', $html, $tags);

    $enderecos = [];

    foreach ($tags[0] as $tag) {
        if (preg_match('~\sclass\s*=\s*"([^"]*)"~', $tag, $classe) !== 1) {
            continue;
        }

        if (! in_array('fi-logo', preg_split('~\s+~', $classe[1], flags: PREG_SPLIT_NO_EMPTY) ?: [], true)) {
            continue;
        }

        if (preg_match('~\ssrc\s*=\s*"([^"]*)"~', $tag, $src) === 1) {
            $enderecos[] = $src[1];
        }
    }

    return $enderecos;
}

/** O texto do elemento de detalhe do bloco do usuário, ou `null` quando ele não existe. */
function detalheDoBlocoDaTenancia(string $html): ?string
{
    $regiao = regiaoDoHeader($html, 'kit-usuario__detalhe');

    return $regiao === '' ? null : trim(html_entity_decode(strip_tags($regiao)));
}

/**
 * A pessoa vinculada primeiro à Acme e depois à outra, com papel nas duas e `admin` global.
 *
 * @return array{acme: Tenant, outra: Tenant, usuario: User}
 */
function pessoaNasDuasOrganizacoes(Tenant $outra): array
{
    $acme = Tenant::where('slug', 'acme')->firstOrFail();

    $usuario = usuarioComPapel('admin_app', $acme);

    papelNaOrganizacao($usuario, 'panel_user', $outra);
    papelNaOrganizacao($usuario, 'admin');

    $usuario->tenants()->attach([$acme->id, $outra->id]);

    return compact('acme', 'outra', 'usuario');
}

// --- R4 — com organização ativa, o segmento do painel é a organização aberta ----

it('[CT-04] o segmento do painel mostra a organização aberta, e só nela', function (
    array $opcoes,
    string $nomeDaOutra,
    ?string $antes,
    string $tela,
    array $mostra,
    array $naoMostra,
    int $separadores,
): void {
    tenant('Acme', 'acme');
    $outra = tenant($nomeDaOutra, $nomeDaOutra === 'Globex' ? 'globex' : 'omega');

    ['usuario' => $usuario] = pessoaNasDuasOrganizacoes($outra);

    gravarCabecalhoDaTenancia($opcoes + ['logo_da_marca' => false]);

    $this->actingAs($usuario);

    if ($antes !== null) {
        $this->get($antes)->assertSuccessful();
        fronteiraDeRequest();
    }

    $html   = $this->get($tela)->assertSuccessful()->getContent();
    $regiao = regiaoDoHeader($html, 'kit-cabecalho');

    // Controle positivo: região vazia satisfaz toda ausência.
    expect($regiao)->not->toBe('');

    foreach ($mostra as $texto => $vezes) {
        expect(substr_count($regiao, $texto))->toBe($vezes);
    }

    foreach ($naoMostra as $texto) {
        $this->assertStringNotContainsString($texto, $regiao, "a composição mostra {$texto}");
    }

    expect(substr_count($regiao, 'kit-cabecalho__sep'))->toBe($separadores);
})->with([
    'organização aberta' => [
        ['nome_do_projeto' => true, 'nome_do_painel' => true], 'Globex', null, '/app/acme',
        ['Acme' => 1, 'Projeto Ômega' => 1], ['Globex'], 1,
    ],
    'não é a primeira vinculada' => [
        ['nome_do_projeto' => true, 'nome_do_painel' => true], 'Globex', null, '/app/globex',
        ['Globex' => 1, 'Projeto Ômega' => 1], ['Acme'], 1,
    ],
    'sem organização na rota' => [
        ['nome_do_projeto' => true, 'nome_do_painel' => true], 'Globex', '/app/acme', '/admin',
        ['Administração' => 1, 'Projeto Ômega' => 1], ['Acme', 'Globex'], 1,
    ],
    'organização com o nome do projeto' => [
        ['nome_do_projeto' => true, 'nome_do_painel' => true], 'Projeto Ômega', null, '/app/omega',
        ['Projeto Ômega' => 2], [], 1,
    ],
    'segmento desligado' => [
        ['nome_do_projeto' => true, 'nome_do_painel' => false], 'Globex', null, '/app/acme',
        ['Projeto Ômega' => 1], ['Acme'], 0,
    ],
    'só o painel ligado' => [
        ['nome_do_projeto' => false, 'nome_do_painel' => true], 'Globex', null, '/app/acme',
        ['Acme' => 1], ['Projeto Ômega'], 0,
    ],
])->group('kit');

// --- fix/logo-dark-do-tenant — a logo da organização (clara e escura) no topo do /app ----
//
// O CT-49 substitui o antigo CT-07 (a logo era sempre a da instalação): o Adendo 1 reverte essa
// regra só no `/app` com organização aberta.

/**
 * Os `src` das `<img>` do swap nativo, por variante (`fi-logo-light` e `fi-logo-dark`), sem repetição:
 * o Filament desenha a marca na barra lateral e no topo, então cada URL sai mais de uma vez.
 *
 * @return array{light: list<string>, dark: list<string>}
 */
function imagensDaMarcaPorVariante(string $html): array
{
    preg_match_all('~<img\b[^>]*>~i', $html, $tags);

    $imagens = ['light' => [], 'dark' => []];

    foreach ($tags[0] as $tag) {
        if (preg_match('~\sclass\s*=\s*"([^"]*)"~', $tag, $classe) !== 1 || preg_match('~\ssrc\s*=\s*"([^"]*)"~', $tag, $src) !== 1) {
            continue;
        }

        $classes = preg_split('~\s+~', $classe[1], flags: PREG_SPLIT_NO_EMPTY) ?: [];

        foreach (['light' => 'fi-logo-light', 'dark' => 'fi-logo-dark'] as $variante => $nome) {
            if (in_array($nome, $classes, true)) {
                $imagens[$variante][] = $src[1];
            }
        }
    }

    return ['light' => array_values(array_unique($imagens['light'])), 'dark' => array_values(array_unique($imagens['dark']))];
}

/** O texto de todo `div.fi-logo` (a marca em texto, sem imagem), um por elemento. */
function marcasEmTextoDaTenancia(string $html): array
{
    preg_match_all('~<div\b[^>]*class="[^"]*\bfi-logo\b[^"]*"[^>]*>(.*?)</div>~s', $html, $achados);

    return array_map(static fn (string $texto): string => trim(html_entity_decode(strip_tags($texto))), $achados[1]);
}

/**
 * Uma organização com as logos pedidas no disco `public` (o disco já é fake) e na coluna.
 * `null` deixa a coluna vazia.
 */
function organizacaoComLogos(string $nome, string $slug, ?string $clara = null, ?string $escura = null): Tenant
{
    foreach (array_filter([$clara, $escura]) as $caminho) {
        Storage::disk('public')->put($caminho, 'png');
    }

    return Tenant::factory()
        ->comIdentidadeVisual('#7c3aed', $clara, null, $escura)
        ->create(['nome' => $nome, 'slug' => $slug]);
}

/** A organização de teste "Acme" com o par `acme.png` / `acme-dark.png`. */
function acmeComPar(): Tenant
{
    return organizacaoComLogos('Acme', 'acme', 'organizacoes/logos/acme.png', 'organizacoes/logos/acme-dark.png');
}

/** Pessoa com papel `panel_user` na organização e vínculo com ela. */
function pessoaDaOrganizacao(Tenant $organizacao): User
{
    $usuario = usuarioComPapel('panel_user', $organizacao);
    $usuario->tenants()->attach($organizacao->id);

    return $usuario;
}

/** Desliga a composição do cabeçalho (marca simples) ou liga só o segmento logo da marca. */
function composicaoDoCabecalhoDaTenancia(bool $ligada): void
{
    gravarCabecalhoDaTenancia(['nome_do_projeto' => false, 'nome_do_painel' => false, 'logo_da_marca' => $ligada]);
}

it('[CT-42] a marca simples do /app da Acme é o par da organização', function (): void {
    gravarLogoDaInstalacaoDaTenancia(unifica: false);
    $acme = acmeComPar();
    composicaoDoCabecalhoDaTenancia(false);

    $html    = $this->actingAs(pessoaDaOrganizacao($acme))->get('/app/acme')->assertSuccessful()->getContent();
    $imagens = imagensDaMarcaPorVariante($html);

    expect($imagens['light'])->toHaveCount(1)
        ->and($imagens['light'][0])->toContain('organizacoes/logos/acme.png')
        ->and($imagens['dark'])->toHaveCount(1)
        ->and($imagens['dark'][0])->toContain('organizacoes/logos/acme-dark.png');

    $todas = implode('|', imagensDaMarcaDaTenancia($html));

    $this->assertStringNotContainsString('kit/logo-ct.png', $todas);
    $this->assertStringNotContainsString('kit/logo-dark-ct.png', $todas);
})->group('kit');

it('[CT-43] a marca simples cai por variante na instalação quando a organização não tem a logo', function (?string $clara, string $claraEsperada): void {
    gravarLogoDaInstalacaoDaTenancia(unifica: false);
    $acme = organizacaoComLogos('Acme', 'acme', $clara);
    composicaoDoCabecalhoDaTenancia(false);

    $html    = $this->actingAs(pessoaDaOrganizacao($acme))->get('/app/acme')->assertSuccessful()->getContent();
    $imagens = imagensDaMarcaPorVariante($html);

    expect($imagens['light'])->toHaveCount(1)
        ->and($imagens['light'][0])->toContain($claraEsperada)
        ->and($imagens['dark'])->toHaveCount(1)
        ->and($imagens['dark'][0])->toContain('kit/logo-dark-ct.png');
})->with([
    'queda total'        => [null, 'kit/logo-ct.png'],
    'queda só da escura' => ['organizacoes/logos/acme.png', 'organizacoes/logos/acme.png'],
])->group('kit');

/** @premissa o nome em texto no claro e a `<img>` escura no escuro é o comportamento nativo do Filament. */
it('[CT-44] organização só com a variante escura e instalação sem clara: a escura no tema escuro e o nome no claro', function (): void {
    Storage::fake('public');
    gravarConfiguracao('unifica_logo_marca', false);
    alinharConfiguracoesDoKit();

    $acme = organizacaoComLogos('Acme', 'acme', null, 'organizacoes/logos/acme-dark.png');
    composicaoDoCabecalhoDaTenancia(false);

    $html    = $this->actingAs(pessoaDaOrganizacao($acme))->get('/app/acme')->assertSuccessful()->getContent();
    $imagens = imagensDaMarcaPorVariante($html);

    expect($imagens['dark'])->toHaveCount(1)
        ->and($imagens['dark'][0])->toContain('organizacoes/logos/acme-dark.png')
        ->and($imagens['light'])->toBe([])
        ->and(implode('|', marcasEmTextoDaTenancia($html)))->toContain((string) config('app.name'));
})->group('kit');

it('[CT-57] organização só com a variante escura e instalação com a clara: clara da instalação e escura da organização', function (bool $composicao): void {
    gravarLogoDaInstalacaoDaTenancia(unifica: false);
    $acme = organizacaoComLogos('Acme', 'acme', null, 'organizacoes/logos/acme-dark.png');
    composicaoDoCabecalhoDaTenancia($composicao);

    $html    = $this->actingAs(pessoaDaOrganizacao($acme))->get('/app/acme')->assertSuccessful()->getContent();
    $imagens = imagensDaMarcaPorVariante($html);

    expect($imagens['light'])->toHaveCount(1)
        ->and($imagens['light'][0])->toContain('kit/logo-ct.png')
        ->and($imagens['dark'])->toHaveCount(1)
        ->and($imagens['dark'][0])->toContain('organizacoes/logos/acme-dark.png');

    $this->assertStringNotContainsString('kit/logo-dark-ct.png', implode('|', imagensDaMarcaDaTenancia($html)));
})->with([
    'desligada (marca simples)' => [false],
    'ligada (composição)'       => [true],
])->group('kit');

it('[CT-45] a composição do /app da Acme mostra o par da organização, com um só fi-logo-dark', function (): void {
    gravarLogoDaInstalacaoDaTenancia(unifica: false);
    $acme = acmeComPar();
    composicaoDoCabecalhoDaTenancia(true);

    $html    = $this->actingAs(pessoaDaOrganizacao($acme))->get('/app/acme')->assertSuccessful()->getContent();
    $regiao  = regiaoDoHeader($html, 'kit-cabecalho');
    $imagens = imagensDaMarcaPorVariante($regiao);

    expect($regiao)->not->toBe('')
        ->and(substr_count($regiao, 'fi-logo-light'))->toBe(1)
        ->and(substr_count($regiao, 'fi-logo-dark'))->toBe(1)
        ->and($imagens['light'])->toHaveCount(1)
        ->and($imagens['light'][0])->toContain('organizacoes/logos/acme.png')
        ->and($imagens['dark'])->toHaveCount(1)
        ->and($imagens['dark'][0])->toContain('organizacoes/logos/acme-dark.png');

    // Nenhuma escura fora de uma composição: a página inteira tem tantas quanto composições (>= 1).
    $composicoes = substr_count($html, '<span class="kit-cabecalho">');
    $escuras     = preg_match_all('~<img\b[^>]*class="[^"]*\bfi-logo-dark\b~', $html);

    expect($composicoes)->toBeGreaterThanOrEqual(1)
        ->and($escuras)->toBe($composicoes);

    $todas = implode('|', imagensDaMarcaDaTenancia($html));

    $this->assertStringNotContainsString('kit/logo-ct.png', $todas);
    $this->assertStringNotContainsString('kit/logo-dark-ct.png', $todas);
})->group('kit');

it('[CT-46] a composição cai no par da instalação quando a organização não tem logo', function (): void {
    gravarLogoDaInstalacaoDaTenancia(unifica: false);
    $acme = organizacaoComLogos('Acme', 'acme');
    composicaoDoCabecalhoDaTenancia(true);

    $html    = $this->actingAs(pessoaDaOrganizacao($acme))->get('/app/acme')->assertSuccessful()->getContent();
    $regiao  = regiaoDoHeader($html, 'kit-cabecalho');
    $imagens = imagensDaMarcaPorVariante($regiao);

    expect($regiao)->not->toBe('')
        ->and($imagens['light'])->toHaveCount(1)
        ->and($imagens['light'][0])->toContain('kit/logo-ct.png')
        ->and($imagens['dark'])->toHaveCount(1)
        ->and($imagens['dark'][0])->toContain('kit/logo-dark-ct.png');
})->group('kit');

/** @premissa a composição descarta a escura quando a clara é nula (dependência clara → escura). */
it('[CT-47] a composição descarta a escura da organização quando não há logo clara', function (): void {
    Storage::fake('public');
    gravarConfiguracao('unifica_logo_marca', false);
    alinharConfiguracoesDoKit();

    $acme = organizacaoComLogos('Acme', 'acme', null, 'organizacoes/logos/acme-dark.png');
    gravarCabecalhoDaTenancia(['nome_do_projeto' => true, 'nome_do_painel' => false, 'logo_da_marca' => true]);

    $html   = $this->actingAs(pessoaDaOrganizacao($acme))->get('/app/acme')->assertSuccessful()->getContent();
    $regiao = regiaoDoHeader($html, 'kit-cabecalho');

    // Controle positivo: o texto prova que a região existe e tem conteúdo.
    expect(html_entity_decode(strip_tags($regiao)))->toContain('Projeto Ômega')
        ->and($regiao)->not->toContain('<img')
        ->and(imagensDaMarcaPorVariante($html)['dark'])->toBe([]);
})->group('kit');

it('[CT-48] a marca unificada mostra só a clara da organização e nenhuma escura', function (bool $composicao): void {
    gravarLogoDaInstalacaoDaTenancia(unifica: true);
    $acme = acmeComPar();
    composicaoDoCabecalhoDaTenancia($composicao);

    $html = $this->actingAs(pessoaDaOrganizacao($acme))->get('/app/acme')->assertSuccessful()->getContent();

    expect(implode('|', imagensDaMarcaDaTenancia($html)))->toContain('organizacoes/logos/acme.png')
        ->and(imagensDaMarcaPorVariante($html)['dark'])->toBe([])
        ->and(preg_match('~fi-logo-dark~', $html))->toBe(0);

    $this->assertStringNotContainsString('organizacoes/logos/acme-dark.png', $html);
    $this->assertStringNotContainsString('kit/logo-dark-ct.png', $html);
})->with([
    'desligada (marca simples)' => [false],
    'ligada (composição)'       => [true],
])->group('kit');

// --- R6 — a organização do topo é a aberta pela rota -----------------------------

it('[CT-49] a pessoa de duas organizações vê no /app/acme o par da Acme (ou o da instalação), nunca o da Globex', function (
    bool $composicao,
    bool $acmeComLogos,
    bool $acmePrimeiro,
    string $clara,
    string $escura,
): void {
    gravarLogoDaInstalacaoDaTenancia(unifica: false);

    $globex = organizacaoComLogos('Globex', 'globex', 'organizacoes/logos/globex.png', 'organizacoes/logos/globex-dark.png');
    $acme   = $acmeComLogos ? acmeComPar() : organizacaoComLogos('Acme', 'acme');

    $primeira = $acmePrimeiro ? $acme : $globex;
    $segunda  = $acmePrimeiro ? $globex : $acme;

    $usuario = usuarioComPapel('panel_user', $primeira);
    papelNaOrganizacao($usuario, 'panel_user', $segunda);
    $usuario->tenants()->attach([$primeira->id, $segunda->id]);

    composicaoDoCabecalhoDaTenancia($composicao);

    // Controle positivo: abre a Globex e vê o par dela, com a sessão apontando a Globex.
    $primeiro = $this->actingAs($usuario)->get('/app/globex')->assertSuccessful()->getContent();

    expect(imagensDaMarcaPorVariante($primeiro)['light'][0] ?? '')->toContain('organizacoes/logos/globex.png');

    fronteiraDeRequest();

    $html    = $this->get('/app/acme')->assertSuccessful()->getContent();
    $imagens = imagensDaMarcaPorVariante($html);

    expect($imagens['light'])->toHaveCount(1)
        ->and($imagens['light'][0])->toContain($clara)
        ->and($imagens['dark'])->toHaveCount(1)
        ->and($imagens['dark'][0])->toContain($escura);

    $this->assertStringNotContainsString('organizacoes/logos/globex', $html);
})->with([
    'primeira vinculada = Globex'                  => [true, true, false, 'organizacoes/logos/acme.png', 'organizacoes/logos/acme-dark.png'],
    'primeira vinculada = Acme (ordem inversa)'    => [true, true, true, 'organizacoes/logos/acme.png', 'organizacoes/logos/acme-dark.png'],
    'marca simples'                                => [false, true, false, 'organizacoes/logos/acme.png', 'organizacoes/logos/acme-dark.png'],
    'a Acme sem logo não herda o par da Globex'    => [true, false, false, 'kit/logo-ct.png', 'kit/logo-dark-ct.png'],
])->group('kit');

it('[CT-50] a logo de uma organização só chega a quem a rota deixa entrar nela', function (bool $comVinculo, bool $globexContem, ?bool $instalacaoContem): void {
    gravarLogoDaInstalacaoDaTenancia(unifica: false);
    $acme   = acmeComPar();
    $globex = organizacaoComLogos('Globex', 'globex', 'organizacoes/logos/globex.png', 'organizacoes/logos/globex-dark.png');

    $usuario = pessoaDaOrganizacao($acme);

    if ($comVinculo) {
        papelNaOrganizacao($usuario, 'panel_user', $globex);
        $usuario->tenants()->attach($globex->id);
    }

    composicaoDoCabecalhoDaTenancia(false);

    $html = $this->actingAs($usuario)->get('/app/globex')->getContent();

    if ($globexContem) {
        $this->assertStringContainsString('organizacoes/logos/globex.png', $html);
    } else {
        $this->assertStringNotContainsString('organizacoes/logos/globex.png', $html);
    }

    // A página de recusa (404 do Sentinel) não renderiza a marca do painel: a cláusula sobre a
    // logo da instalação não tem o que medir ali (`null`), e o M25 fica sem matador. Medido: a
    // resposta tem o nome da aplicação em texto e nenhum `<img>`.
    if ($instalacaoContem === true) {
        $this->assertStringContainsString('kit/logo-ct.png', $html);
    } elseif ($instalacaoContem === false) {
        $this->assertStringNotContainsString('kit/logo-ct.png', $html);
    }
})->with([
    'com vínculo: a marca é a da organização'                  => [true, true, false],
    'sem vínculo: a logo da organização não chega'             => [false, false, null],
])->group('kit');

it('[CT-56] a master_global sem vínculo que abre /app/globex vê o par da Globex', function (): void {
    gravarLogoDaInstalacaoDaTenancia(unifica: false);
    organizacaoComLogos('Globex', 'globex', 'organizacoes/logos/globex.png', 'organizacoes/logos/globex-dark.png');
    composicaoDoCabecalhoDaTenancia(true);

    $html    = $this->actingAs(usuarioDoKit('master_global'))->get('/app/globex')->assertSuccessful()->getContent();
    $regiao  = regiaoDoHeader($html, 'kit-cabecalho');
    $imagens = imagensDaMarcaPorVariante($regiao);

    expect($regiao)->not->toBe('')
        ->and($imagens['light'])->toHaveCount(1)
        ->and($imagens['light'][0])->toContain('organizacoes/logos/globex.png')
        ->and($imagens['dark'])->toHaveCount(1)
        ->and($imagens['dark'][0])->toContain('organizacoes/logos/globex-dark.png');

    $this->assertStringNotContainsString('kit/logo-ct.png', $regiao);
    $this->assertStringNotContainsString('kit/logo-dark-ct.png', $regiao);
})->group('kit');

// --- R7 — fora do /app com organização, nunca a logo de organização --------------

it('[CT-51] o topo fora do /app com organização é o par da instalação', function (
    string $tela,
    string $papelDaPessoa,
    bool $esquecidaNoGerenciador,
    bool $sessaoApontaAcme,
): void {
    gravarLogoDaInstalacaoDaTenancia(unifica: false);
    $acme = acmeComPar();
    composicaoDoCabecalhoDaTenancia(false);

    if ($papelDaPessoa === 'visitante') {
        // sem autenticação
    } elseif ($papelDaPessoa === 'panel_user') {
        $this->actingAs(pessoaDaOrganizacao($acme));
    } else {
        $this->actingAs(usuarioComPapel($papelDaPessoa));
    }

    if ($esquecidaNoGerenciador) {
        Filament::setCurrentPanel('app');
        Filament::setTenant($acme, isQuiet: true);
    }

    if ($sessaoApontaAcme) {
        session(['tenant_corrente' => $acme->getKey()]);
    }

    $html    = $this->get($tela)->assertSuccessful()->getContent();
    $imagens = imagensDaMarcaPorVariante($html);

    expect($imagens['light'])->toHaveCount(1)
        ->and($imagens['light'][0])->toContain('kit/logo-ct.png')
        ->and($imagens['dark'])->toHaveCount(1)
        ->and($imagens['dark'][0])->toContain('kit/logo-dark-ct.png');

    $this->assertStringNotContainsString('organizacoes/logos/acme.png', $html);
    $this->assertStringNotContainsString('organizacoes/logos/acme-dark.png', $html);
})->with([
    '/admin'     => ['/admin', 'admin', true, false],
    '/infra'     => ['/infra', 'infra', true, false],
    '/app/login' => ['/app/login', 'visitante', false, true],
    // `/app` só redireciona (302) e `/app/new` é 404 (o kit não tem cadastro de organização): sem topo
    // para afirmar, a linha foi trocada por outra rota do `/app` sem organização que renderiza a marca.
    '/app/password-reset/request' => ['/app/password-reset/request', 'visitante', false, true],
])->group('kit');

it('[CT-52] a marca do topo exige painel com tenancy e um objeto que seja organização', function (?string $painel, string $objeto, string $clara, string $escura): void {
    gravarLogoDaInstalacaoDaTenancia(unifica: false);
    $acme = acmeComPar();
    composicaoDoCabecalhoDaTenancia(false);

    Filament::setCurrentPanel($painel);
    Filament::setTenant($objeto === 'acme' ? $acme : usuario('pessoa@example.com'), isQuiet: true);

    $caminhos = ['A' => 'organizacoes/logos/acme.png', 'B' => 'organizacoes/logos/acme-dark.png', 'C' => 'kit/logo-ct.png', 'D' => 'kit/logo-dark-ct.png'];

    expect(CabecalhoDoPainel::marca())->toBeString()->toContain($caminhos[$clara])
        ->and(CabecalhoDoPainel::marcaEscura())->toBeString()->toContain($caminhos[$escura]);
})->with([
    'nenhum painel (console, job)'  => [null, 'acme', 'C', 'D'],
    'o painel /admin'               => ['admin', 'acme', 'C', 'D'],
    'o painel /app (controle)'      => ['app', 'acme', 'A', 'B'],
    'objeto que não é organização'  => ['app', 'pessoa', 'C', 'D'],
])->group('kit');

// --- R8 — o redesenho Livewire mantém o par ------------------------------------

it('[CT-53] a barra superior e a lateral do /app da Acme, renderizadas pelo componente, devolvem o par da organização', function (string $componente, bool $sessaoApontaGlobex): void {
    gravarLogoDaInstalacaoDaTenancia(unifica: false);
    $acme   = acmeComPar();
    $globex = organizacaoComLogos('Globex', 'globex', 'organizacoes/logos/globex.png', 'organizacoes/logos/globex-dark.png');
    composicaoDoCabecalhoDaTenancia(false);

    $usuario = pessoaDaOrganizacao($acme);

    // Boota o painel `app` por um request real (rule `testes.md`) antes do primeiro Livewire::test().
    $this->actingAs($usuario)->get('/app/acme')->assertSuccessful();

    noPainelDa($acme);

    if ($sessaoApontaGlobex) {
        session(['tenant_corrente' => $globex->getKey()]);
    }

    $html    = Livewire::test($componente)->html();
    $imagens = imagensDaMarcaPorVariante($html);

    expect($imagens['light'])->toHaveCount(1)
        ->and($imagens['light'][0])->toContain('organizacoes/logos/acme.png')
        ->and($imagens['dark'])->toHaveCount(1)
        ->and($imagens['dark'][0])->toContain('organizacoes/logos/acme-dark.png');

    foreach (['organizacoes/logos/globex', 'kit/logo-ct.png', 'kit/logo-dark-ct.png'] as $fora) {
        $this->assertStringNotContainsString($fora, $html);
    }
})->with([
    'Topbar, sem organização na sessão'  => [Topbar::class, false],
    'Sidebar, sem organização na sessão' => [Sidebar::class, false],
    'Topbar, sessão apontando a Globex'  => [Topbar::class, true],
])->group('kit');

// --- R9 — o nome da organização é texto, nunca marcação -------------------------

it('[CT-11] o nome da organização sai escapado na composição', function (): void {
    $nome        = '<img src=x onerror=alert(1)>';
    $organizacao = tenant($nome, 'xss');

    $usuario = usuarioComPapel('panel_user', $organizacao);
    $usuario->tenants()->attach($organizacao->id);

    gravarCabecalhoDaTenancia(['nome_do_projeto' => false, 'nome_do_painel' => true, 'logo_da_marca' => false]);

    $html   = $this->actingAs($usuario)->get('/app/xss')->assertSuccessful()->getContent();
    $regiao = regiaoDoHeader($html, 'kit-cabecalho');

    expect($regiao)->not->toBe('')
        ->and(html_entity_decode(strip_tags($regiao)))->toContain($nome)
        ->and($regiao)->toContain('&lt;img src=x');

    $this->assertStringNotContainsString('<img src=x', $regiao);
})->group('kit');

// --- R1 — com organização ativa, a marca de fábrica não muda --------------------

it('[CT-27] com organização ativa, a marca de fábrica não muda', function (): void {
    $acme = tenant('Acme', 'acme');

    $usuario = usuarioComPapel('panel_user', $acme);
    $usuario->tenants()->attach($acme->id);

    // Nenhuma opção do cabeçalho gravada pelo teste: só a identidade.
    gravarLogoDaInstalacaoDaTenancia();

    $html = $this->actingAs($usuario)->get('/app/acme')->assertSuccessful()->getContent();

    $this->assertStringNotContainsString('kit-cabecalho', $html);
    $this->assertStringNotContainsString('kit-usuario', $html);

    $enderecos = imagensDaMarcaDaTenancia($html);

    expect($enderecos)->not->toBeEmpty()
        ->and(implode('|', $enderecos))->toContain('kit/logo-ct.png');
})->group('kit');

// --- R12 — o papel é o da organização aberta -------------------------------------

it('[CT-15] o detalhe com organização ativa', function (
    string $detalhe,
    array $papeis,
    string $tela,
    ?string $linha,
    array $naoMostra,
): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $bianca = usuarioComPapel($papeis['acme'], $acme, 'bianca@example.com');
    $bianca->update(['name' => 'Bianca']);

    if (isset($papeis['globex'])) {
        papelNaOrganizacao($bianca, $papeis['globex'], $globex);
    }

    $bianca->tenants()->attach([$acme->id, $globex->id]);

    gravarCabecalhoDaTenancia(['usuario' => true, 'detalhe_do_usuario' => $detalhe]);

    $html   = $this->actingAs($bianca)->get($tela)->assertSuccessful()->getContent();
    $bloco  = regiaoDoHeader($html, 'kit-usuario');
    $nomeEl = html_entity_decode(strip_tags(regiaoDoHeader($html, 'kit-usuario__nome')));

    // Controle positivo: o bloco existe e traz o nome, senão toda ausência valeria.
    expect($bloco)->not->toBe('')
        ->and(trim($nomeEl))->toBe('Bianca')
        ->and(detalheDoBlocoDaTenancia($html))->toBe($linha);

    foreach ($naoMostra as $texto) {
        $this->assertStringNotContainsString($texto, $bloco, "o bloco mostra {$texto}");
    }
})->with([
    'papel da organização' => ['perfil', ['acme' => 'admin_app', 'globex' => 'panel_user'], '/app/acme', 'Administrador App', ['Painel App']],
    'outra organização'    => ['perfil', ['acme' => 'admin_app', 'globex' => 'panel_user'], '/app/globex', 'Painel App', ['Administrador App']],
    '0 papéis, sem linha'  => ['perfil', ['acme' => 'panel_user'], '/app/globex', null, ['bianca@example.com', 'Painel App']],
    'e-mail sem papel'     => ['email', ['acme' => 'panel_user'], '/app/globex', 'bianca@example.com', ['Painel App']],
])->group('kit');

// --- R23 — o papel do contexto do painel corrente, com master_global vencendo ----

it('[CT-29] o administrador geral aparece como tal na organização', function (bool $tambemPanelUser): void {
    $acme = tenant('Acme', 'acme');

    $master = usuarioCom('master_global');
    $master->tenants()->attach($acme->id);

    if ($tambemPanelUser) {
        papelNaOrganizacao($master, 'panel_user', $acme);
    }

    gravarCabecalhoDaTenancia(['usuario' => true, 'detalhe_do_usuario' => 'perfil']);

    $html  = $this->actingAs($master)->get('/app/acme')->assertSuccessful()->getContent();
    $bloco = regiaoDoHeader($html, 'kit-usuario');

    expect($bloco)->not->toBe('')
        ->and(detalheDoBlocoDaTenancia($html))->toBe('Administrador Geral');

    $this->assertStringNotContainsString('Painel App', $bloco);
})->with([
    'só master'                     => [false],
    'master vence o da organização' => [true],
])->group('kit');

/** Admin global e `admin_app` na Acme — a persona de CT-30 e CT-31. */
function adminGlobalEAdminDaAcme(): User
{
    $acme = tenant('Acme', 'acme');

    $usuario = usuarioComPapel('admin_app', $acme);
    papelNaOrganizacao($usuario, 'admin');
    $usuario->tenants()->attach($acme->id);

    gravarCabecalhoDaTenancia(['usuario' => true, 'detalhe_do_usuario' => 'perfil']);

    return $usuario;
}

it('[CT-30] papel global não vence o papel da organização aberta', function (): void {
    $usuario = adminGlobalEAdminDaAcme();

    $html = $this->actingAs($usuario)->get('/app/acme')->assertSuccessful()->getContent();

    expect(regiaoDoHeader($html, 'kit-usuario'))->not->toBe('')
        ->and(detalheDoBlocoDaTenancia($html))->toBe('Administrador App')
        ->and(detalheDoBlocoDaTenancia($html))->not->toBe('Admin');
})->group('kit');

it('[CT-31] o papel da organização não vaza para o painel global', function (): void {
    $usuario = adminGlobalEAdminDaAcme();

    $this->actingAs($usuario)->get('/app/acme')->assertSuccessful();

    fronteiraDeRequest();

    $html  = $this->get('/admin')->assertSuccessful()->getContent();
    $bloco = regiaoDoHeader($html, 'kit-usuario');

    expect($bloco)->not->toBe('')
        ->and(detalheDoBlocoDaTenancia($html))->toBe('Admin');

    $this->assertStringNotContainsString('Administrador App', $bloco);
})->group('kit');

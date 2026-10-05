<?php

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Support\Facades\Storage;

/**
 * O cabeçalho dos painéis no que ele só significa com organização ativa (`/app/{slug}`).
 *
 * Os IDs de CT são os de `wikis/specs/feat/cabecalho-do-painel/cabecalho-do-painel/04-casos-de-teste.md`.
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

/** A logo da instalação no disco `public` e na settings, com a marca unificada. */
function gravarLogoDaInstalacaoDaTenancia(): void
{
    Storage::fake('public');
    Storage::disk('public')->put('kit/logo-ct.png', 'png');

    gravarConfiguracao('logo', 'kit/logo-ct.png');
    gravarConfiguracao('unifica_logo_marca', true);
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

// --- R6 — a logo é sempre a da instalação ---------------------------------------

it('[CT-07] no /app da Acme a composição mostra a logo da instalação e nenhuma de organização', function (): void {
    gravarLogoDaInstalacaoDaTenancia();
    Storage::disk('public')->put('organizacoes/logos/acme.png', 'png');
    Storage::disk('public')->put('organizacoes/logos/globex.png', 'png');

    $globex = Tenant::factory()->create(['nome' => 'Globex', 'slug' => 'globex', 'logo' => 'organizacoes/logos/globex.png']);
    $acme   = Tenant::factory()->create(['nome' => 'Acme', 'slug' => 'acme', 'logo' => 'organizacoes/logos/acme.png']);

    $usuario = usuarioComPapel('panel_user', $globex);
    papelNaOrganizacao($usuario, 'panel_user', $acme);
    $usuario->tenants()->attach([$globex->id, $acme->id]);

    gravarCabecalhoDaTenancia(['nome_do_projeto' => false, 'nome_do_painel' => false, 'logo_da_marca' => true]);

    $this->actingAs($usuario)->get('/app/globex')->assertSuccessful();

    fronteiraDeRequest();

    $html   = $this->get('/app/acme')->assertSuccessful()->getContent();
    $regiao = regiaoDoHeader($html, 'kit-cabecalho');

    expect($regiao)->not->toBe('')
        ->and(substr_count($regiao, '<img'))->toBe(1)
        ->and($regiao)->toContain('kit/logo-ct.png');

    $this->assertStringNotContainsString('organizacoes/logos/acme.png', $regiao);
    $this->assertStringNotContainsString('organizacoes/logos/globex.png', $regiao);
})->group('kit');

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

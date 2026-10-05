<?php

use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Support\Facades\Storage;

/**
 * O cabeçalho dos painéis personalizável pelas Configurações da aplicação — render na instalação
 * (G1 do `04-casos-de-teste.md` da wiki `feat/cabecalho-do-painel`).
 *
 * Os IDs de CT são os do `04`. As classes `kit-cabecalho*` e `kit-usuario*` são só SELETOR de
 * extração (Fronteira com o Plano do `04`): o que se afirma é o conteúdo e a ordem, nunca o nome
 * da classe em si.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/*
|--------------------------------------------------------------------------
| Helpers deste arquivo (nenhum outro arquivo os usa)
|--------------------------------------------------------------------------
*/

/**
 * O DOM do HTML. UTF-8 declarado na frente: sem isso o libxml lê Latin-1 e quebra "Ômega".
 */
function cabecalhoDom(string $html): DOMXPath
{
    $documento = new DOMDocument;

    libxml_use_internal_errors(true);
    $documento->loadHTML('<?xml encoding="UTF-8">'.$html);
    libxml_clear_errors();

    return new DOMXPath($documento);
}

/** Predicado XPath de "tem este token na classe". */
function cabecalhoClasse(string $classe): string
{
    return "contains(concat(' ', normalize-space(@class), ' '), ' {$classe} ')";
}

/**
 * Todo elemento do HTML com a classe, na ordem do documento.
 *
 * @return list<DOMElement>
 */
function cabecalhoNos(string $html, string $classe): array
{
    return array_values(iterator_to_array(cabecalhoDom($html)->query('//*['.cabecalhoClasse($classe).']')));
}

/**
 * A sequência de segmentos de uma composição, em ordem: o texto do projeto e do painel, `SEP`
 * para cada separador e `LOGO` para cada imagem da logo.
 *
 * @return list<string>
 */
function cabecalhoSequencia(DOMElement $composicao): array
{
    $xpath = new DOMXPath($composicao->ownerDocument);
    $nos   = $xpath->query(
        './/*['.implode(' or ', array_map(
            cabecalhoClasse(...),
            ['kit-cabecalho__projeto', 'kit-cabecalho__painel', 'kit-cabecalho__sep', 'kit-cabecalho__logo'],
        )).']',
        $composicao,
    );

    $sequencia = [];

    foreach ($nos as $no) {
        $classes = ' '.preg_replace('~\s+~', ' ', (string) $no->getAttribute('class')).' ';

        $sequencia[] = match (true) {
            str_contains($classes, ' kit-cabecalho__sep ')   => 'SEP',
            str_contains($classes, ' kit-cabecalho__logo ')  => 'LOGO',
            default                                          => trim($no->textContent),
        };
    }

    return $sequencia;
}

/**
 * As sequências de TODAS as composições da página (barra lateral e topbar desenham a marca).
 *
 * @return list<list<string>>
 */
function cabecalhoComposicoes(string $html): array
{
    return array_map(cabecalhoSequencia(...), cabecalhoNos($html, 'kit-cabecalho'));
}

/**
 * O que as regiões da marca do Filament (`.fi-logo`) mostram: as URLs das imagens e os textos.
 *
 * @return array{imgs: list<string>, textos: list<string>, classes: list<string>}
 */
function cabecalhoMarca(string $html): array
{
    $marca = ['imgs' => [], 'textos' => [], 'classes' => []];

    foreach (cabecalhoNos($html, 'fi-logo') as $no) {
        if (strtolower($no->nodeName) === 'img') {
            $marca['imgs'][]    = (string) $no->getAttribute('src');
            $marca['classes'][] = (string) $no->getAttribute('class');

            continue;
        }

        $marca['textos'][] = trim($no->textContent);
    }

    return $marca;
}

/**
 * Grava identidade e opções do cabeçalho e alinha a config, pela cadeia real settings → config.
 *
 * `$logos` é `[propriedade => caminho]` (`logo`, `logo_dark`): o arquivo é criado no disco `public`
 * fake, exceto os listados em `$orfas`. `$opcoes` é `[sufixo => valor]` de `cabecalho_*`.
 *
 * @param  array<string, string>  $logos
 * @param  array<string, mixed>  $opcoes
 * @param  list<string>  $orfas
 */
function cabecalhoPreparar(?string $nome = null, array $logos = [], array $opcoes = [], bool $unificada = true, array $orfas = []): void
{
    Storage::fake('public');

    if ($nome !== null) {
        gravarConfiguracao('nome_da_aplicacao', $nome);
    }

    foreach ($logos as $propriedade => $caminho) {
        if (! in_array($caminho, $orfas, true)) {
            Storage::disk('public')->put($caminho, 'png');
        }

        gravarConfiguracao($propriedade, $caminho);
    }

    gravarConfiguracao('unifica_logo_marca', $unificada);

    foreach ($opcoes as $sufixo => $valor) {
        gravarConfiguracao("cabecalho_{$sufixo}", $valor);
    }

    alinharConfiguracoesDoKit();
}

/** As cinco opções gravadas explicitamente no estado desligado/perfil — a linha de base. */
function cabecalhoLinhaDeBase(): array
{
    return [
        'nome_do_projeto'    => false,
        'nome_do_painel'     => false,
        'logo_da_marca'      => false,
        'usuario'            => false,
        'detalhe_do_usuario' => 'perfil',
    ];
}

/** GET autenticado de página cheia; devolve o HTML. */
function cabecalhoAbrir(User $usuario, string $uri): string
{
    $resposta = test()->actingAs($usuario)->get($uri);

    $resposta->assertSuccessful();

    return (string) $resposta->getContent();
}

/** Muda o nome do usuário (texto livre) sem passar pela tela. */
function cabecalhoComNome(User $usuario, string $nome): User
{
    $usuario->forceFill(['name' => $nome])->save();

    return $usuario;
}

/*
|--------------------------------------------------------------------------
| R1 — com os três segmentos desligados, a marca é a de hoje
|--------------------------------------------------------------------------
*/

it('[CT-01] a marca de fábrica não muda com a feature instalada', function (string $painel, array $logos, bool $unificada, string $marcaEsperada): void {
    cabecalhoPreparar('Projeto Ômega', $logos, [], $unificada);

    $usuario = usuarioCom('master_global');

    // O default primeiro: nenhuma opção do cabeçalho gravada pelo teste. Duas visitas, porque cada
    // request no mesmo processo acrescenta ocorrências do nome fora do cabeçalho (itens de menu
    // acumulados pelo painel, +2 por visita, medido): a deriva é a diferença entre as duas.
    $primeiro = cabecalhoAbrir($usuario, $painel);
    fronteiraDeRequest();
    $padrao = cabecalhoAbrir($usuario, $painel);
    $deriva = substr_count($padrao, 'Projeto Ômega') - substr_count($primeiro, 'Projeto Ômega');

    // Depois a linha de base: as cinco opções explícitas em desligado/perfil.
    cabecalhoPreparar('Projeto Ômega', $logos, cabecalhoLinhaDeBase(), $unificada);
    fronteiraDeRequest();

    $base = cabecalhoAbrir($usuario, $painel);

    expect(substr_count($base, 'Projeto Ômega'))->toBe(substr_count($padrao, 'Projeto Ômega') + $deriva);
    expect(cabecalhoNos($padrao, 'kit-cabecalho'))->toBeEmpty();

    $marca = cabecalhoMarca($padrao);

    match ($marcaEsperada) {
        'logo' => expect($marca['imgs'])->not->toBeEmpty()
            ->and(implode('|', $marca['imgs']))->toContain('storage/kit/logo-ct.png')
            ->and(implode('|', $marca['imgs']))->not->toContain('logo-ct-dark.png'),
        'logo-e-escura' => expect(implode('|', $marca['imgs']))->toContain('storage/kit/logo-ct.png')
            ->and(implode('|', $marca['imgs']))->toContain('storage/kit/logo-ct-dark.png'),
        'texto' => expect($marca['imgs'])->toBeEmpty()
            ->and(implode('|', $marca['textos']))->toContain('Projeto Ômega'),
    };
})->with([
    '/admin logo única'      => ['/admin', ['logo' => 'kit/logo-ct.png'], true, 'logo'],
    '/admin marca separada'  => ['/admin', ['logo' => 'kit/logo-ct.png', 'logo_dark' => 'kit/logo-ct-dark.png'], false, 'logo-e-escura'],
    '/admin sem logo'        => ['/admin', [], true, 'texto'],
    '/infra outro painel'    => ['/infra', ['logo' => 'kit/logo-ct.png'], true, 'logo'],
    '/app painel do negócio' => ['/app', ['logo' => 'kit/logo-ct.png'], true, 'logo'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R2 — ordem e separadores
|--------------------------------------------------------------------------
*/

it('[CT-02] a composição respeita os interruptores, a ordem e os separadores', function (bool $projeto, bool $painel, bool $logo, array $esperado): void {
    cabecalhoPreparar('Projeto Ômega', ['logo' => 'kit/logo-ct.png'], [
        'nome_do_projeto' => $projeto,
        'nome_do_painel'  => $painel,
        'logo_da_marca'   => $logo,
    ]);

    $composicoes = cabecalhoComposicoes(cabecalhoAbrir(usuarioDoKit('admin'), '/admin'));

    expect($composicoes)->not->toBeEmpty();

    foreach ($composicoes as $sequencia) {
        expect($sequencia)->toBe($esperado);
    }
})->with([
    '100' => [true, false, false, ['Projeto Ômega']],
    '010' => [false, true, false, ['Administração']],
    '001' => [false, false, true, ['LOGO']],
    '110' => [true, true, false, ['Projeto Ômega', 'SEP', 'Administração']],
    '101' => [true, false, true, ['Projeto Ômega', 'SEP', 'LOGO']],
    '011' => [false, true, true, ['Administração', 'SEP', 'LOGO']],
    '111' => [true, true, true, ['Projeto Ômega', 'SEP', 'Administração', 'SEP', 'LOGO']],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R3 — sem organização ativa, o segmento do painel
|--------------------------------------------------------------------------
*/

it('[CT-03] o segmento do painel em cada painel, com e sem o nome do projeto ligado', function (string $painel, bool $projeto, array $esperado): void {
    cabecalhoPreparar('Projeto Ômega', [], [
        'nome_do_projeto' => $projeto,
        'nome_do_painel'  => true,
        'logo_da_marca'   => false,
    ]);

    $html        = cabecalhoAbrir(usuarioCom('master_global'), $painel);
    $composicoes = cabecalhoComposicoes($html);

    expect($composicoes)->not->toBeEmpty();

    foreach ($composicoes as $sequencia) {
        expect($sequencia)->toBe($esperado)
            ->and(count(array_filter($sequencia, fn (string $item): bool => $item === 'Projeto Ômega')))->toBe(1);
    }

    // Contagem sobre o HTML inteiro pela classe: uma ocorrência do nome por composição, nunca duas.
    expect(substr_count($html, 'kit-cabecalho__sep'))->toBe(count(array_filter($esperado, fn (string $item): bool => $item === 'SEP')) * count($composicoes));
})->with([
    '/admin ligado'          => ['/admin', true, ['Projeto Ômega', 'SEP', 'Administração']],
    '/infra ligado'          => ['/infra', true, ['Projeto Ômega', 'SEP', 'Infraestrutura']],
    '/app ligado omite'      => ['/app', true, ['Projeto Ômega']],
    '/app projeto desligado' => ['/app', false, ['Projeto Ômega']],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R5 — identidade
|--------------------------------------------------------------------------
*/

it('[CT-05] o nome do projeto é o nome da aplicação gravado nas configurações', function (): void {
    $doAmbiente = (string) config('app.name');

    cabecalhoPreparar('Projeto Ômega', [], ['nome_do_projeto' => true]);

    expect($doAmbiente)->not->toBe('Projeto Ômega');

    $composicoes = cabecalhoComposicoes(cabecalhoAbrir(usuarioDoKit('admin'), '/admin'));

    expect($composicoes)->not->toBeEmpty();

    foreach ($composicoes as $sequencia) {
        expect($sequencia)->toBe(['Projeto Ômega'])
            ->and(implode('|', $sequencia))->not->toContain($doAmbiente)
            ->and(implode('|', $sequencia))->not->toContain('Administração');
    }
})->group('kit');

it('[CT-06] a logo do segmento segue a identidade e o modo da marca', function (array $logos, bool $unificada, array $escuraEsperada): void {
    cabecalhoPreparar('Projeto Ômega', $logos, ['logo_da_marca' => true], $unificada);

    $html = cabecalhoAbrir(usuarioDoKit('admin'), '/admin');
    $nos  = cabecalhoNos($html, 'kit-cabecalho');

    expect($nos)->not->toBeEmpty();

    foreach ($nos as $composicao) {
        $imagens = [];

        foreach ($composicao->getElementsByTagName('img') as $imagem) {
            $imagens[] = ['src' => (string) $imagem->getAttribute('src'), 'class' => (string) $imagem->getAttribute('class')];
        }

        expect($imagens[0]['src'])->toContain('storage/kit/logo-ct.png')
            ->and(count($imagens))->toBe(count($escuraEsperada) + 1);

        $escuras = array_values(array_filter($imagens, fn (array $imagem): bool => str_contains($imagem['class'], 'fi-logo-dark')));

        expect(array_column($escuras, 'src'))->toHaveCount(count($escuraEsperada));

        foreach ($escuraEsperada as $indice => $url) {
            expect($escuras[$indice]['src'])->toContain($url);
        }
    }

    if ($escuraEsperada === []) {
        $this->assertStringNotContainsString('logo-ct-dark.png', implode('', array_map(
            fn (DOMElement $no): string => $no->ownerDocument->saveHTML($no),
            $nos,
        )));
    }
})->with([
    'unificada'                => [['logo' => 'kit/logo-ct.png'], true, []],
    'separada'                 => [['logo' => 'kit/logo-ct.png', 'logo_dark' => 'kit/logo-ct-dark.png'], false, ['storage/kit/logo-ct-dark.png']],
    'variante escura inerte'   => [['logo' => 'kit/logo-ct.png', 'logo_dark' => 'kit/logo-ct-dark.png'], true, []],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R7 — sem logo resolvida, o segmento some
|--------------------------------------------------------------------------
*/

it('[CT-08] o segmento da logo sem logo resolvida', function (array $opcoes, array $logos, array $orfas, ?array $composicaoEsperada): void {
    cabecalhoPreparar('Projeto Ômega', $logos, $opcoes, true, $orfas);

    $html = cabecalhoAbrir(usuarioDoKit('admin'), '/admin');

    foreach (cabecalhoDom($html)->query('//img') as $imagem) {
        expect(trim((string) $imagem->getAttribute('src')))->not->toBe('');
    }

    $this->assertStringNotContainsString('kit/sumiu.png', $html);

    if ($composicaoEsperada === null) {
        expect(cabecalhoNos($html, 'kit-cabecalho'))->toBeEmpty()
            ->and(implode('|', cabecalhoMarca($html)['textos']))->toContain('Projeto Ômega');

        return;
    }

    $composicoes = cabecalhoComposicoes($html);

    expect($composicoes)->not->toBeEmpty();

    foreach ($composicoes as $sequencia) {
        expect($sequencia)->toBe($composicaoEsperada);
    }

    expect($html)->not->toContain('kit-cabecalho__sep');
})->with([
    'órfã, sozinha'         => [['logo_da_marca' => true], ['logo' => 'kit/sumiu.png'], ['kit/sumiu.png'], null],
    'sem logo, sozinha'     => [['logo_da_marca' => true], [], [], null],
    'sem logo, com projeto' => [['nome_do_projeto' => true, 'logo_da_marca' => true], [], [], ['Projeto Ômega']],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R8 — a composição substitui a marca em todo lugar
|--------------------------------------------------------------------------
*/

it('[CT-09] cada região da marca desenhada no layout contém a composição', function (): void {
    cabecalhoPreparar('Projeto Ômega', ['logo' => 'kit/logo-ct.png'], cabecalhoLinhaDeBase());

    $usuario = usuarioDoKit('admin');
    $regioes = count(cabecalhoNos(cabecalhoAbrir($usuario, '/admin'), 'fi-logo'));

    expect($regioes)->toBeGreaterThan(0);

    cabecalhoPreparar('Projeto Ômega', ['logo' => 'kit/logo-ct.png'], ['nome_do_projeto' => true, 'nome_do_painel' => true]);
    fronteiraDeRequest();

    $html     = cabecalhoAbrir($usuario, '/admin');
    $marcas   = cabecalhoNos($html, 'fi-logo');
    $contagem = fn (DOMElement $no): int => (new DOMXPath($no->ownerDocument))->query('.//*['.cabecalhoClasse('kit-cabecalho').']', $no)->length;

    expect($marcas)->toHaveCount($regioes);

    foreach ($marcas as $marca) {
        expect($contagem($marca))->toBe(1);
    }

    // Nenhuma composição fora de uma região da marca.
    expect(cabecalhoNos($html, 'kit-cabecalho'))->toHaveCount($regioes);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R9 — texto escapado e íntegro
|--------------------------------------------------------------------------
*/

it('[CT-10] o nome do projeto e o nome do usuário saem como texto íntegro', function (array $opcoes, string $fonte, string $valor, string $painel, ?string $marcacao, bool $comLogo): void {
    cabecalhoPreparar($fonte === 'aplicacao' ? $valor : 'Projeto Ômega', $comLogo ? ['logo' => 'kit/logo-ct.png'] : [], $opcoes);

    $usuario = $painel === '/app' ? usuarioCom('master_global') : usuarioDoKit('admin');

    if ($fonte === 'administrador') {
        cabecalhoComNome($usuario, $valor);
    }

    $html = cabecalhoAbrir($usuario, $painel);

    if ($fonte === 'aplicacao') {
        $composicoes = cabecalhoComposicoes($html);

        expect($composicoes)->not->toBeEmpty();

        foreach ($composicoes as $sequencia) {
            expect($sequencia[0])->toBe($valor);
        }

        foreach (cabecalhoNos($html, 'kit-cabecalho') as $composicao) {
            expect($composicao->getElementsByTagName('b'))->toHaveCount(0);

            foreach ($composicao->getElementsByTagName('img') as $imagem) {
                expect($imagem->hasAttribute('onerror'))->toBeFalse()
                    ->and($imagem->getAttribute('alt'))->toBe($valor);
            }
        }
    } else {
        $nomes = cabecalhoNos($html, 'kit-usuario__nome');

        expect($nomes)->not->toBeEmpty();

        foreach ($nomes as $no) {
            expect(trim($no->textContent))->toBe($valor);
        }

        foreach (cabecalhoNos($html, 'kit-usuario') as $bloco) {
            expect($bloco->getElementsByTagName('img'))->toHaveCount(0);
        }
    }

    if ($marcacao !== null) {
        $this->assertStringNotContainsString($marcacao, $html);
    } else {
        $this->assertStringContainsString($valor, $html);
    }
})->with([
    'marcação no segmento do projeto' => [['nome_do_projeto' => true, 'usuario' => true], 'aplicacao', '<b>Ômega</b>', '/admin', '<b>Ômega</b>', false],
    'marcação no rótulo do painel'    => [['nome_do_painel' => true], 'aplicacao', '<b>Ômega</b>', '/app', '<b>Ômega</b>', false],
    'aspas em atributo'               => [['nome_do_projeto' => true, 'logo_da_marca' => true], 'aplicacao', 'x" onerror="alert(1)', '/admin', 'onerror="alert(1)"', true],
    'marcação no bloco'               => [['nome_do_projeto' => true, 'usuario' => true], 'administrador', '<img src=x onerror=alert(1)>', '/admin', '<img src=x', false],
    'multibyte no projeto'            => [['nome_do_projeto' => true, 'usuario' => true], 'aplicacao', 'Ação Ñandú 🚀', '/admin', null, false],
    'multibyte no usuário'            => [['nome_do_projeto' => true, 'usuario' => true], 'administrador', 'José Gonçalves 🚀', '/admin', null, false],
    'comprimento máximo'              => [['nome_do_projeto' => true, 'usuario' => true], 'administrador', str_repeat('a', 253).'ç🚀', '/admin', null, false],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R10 — o bloco do usuário
|--------------------------------------------------------------------------
*/

it('[CT-12] com os valores de fábrica, nenhum bloco do usuário aparece', function (string $painel): void {
    cabecalhoPreparar('Projeto Ômega');

    $usuario = cabecalhoComNome(usuarioCom('master_global'), 'Maria Ômega');

    $padrao = cabecalhoAbrir($usuario, $painel);

    cabecalhoPreparar('Projeto Ômega', [], cabecalhoLinhaDeBase());
    fronteiraDeRequest();

    $base = cabecalhoAbrir($usuario, $painel);

    expect(substr_count($padrao, 'Maria Ômega'))->toBe(substr_count($base, 'Maria Ômega'))
        ->and(cabecalhoNos($padrao, 'kit-usuario'))->toBeEmpty();
})->with(['/admin', '/infra', '/app'])->group('kit');

it('[CT-13] ligado, o bloco mostra o nome antes do menu do usuário, que continua inteiro', function (): void {
    cabecalhoPreparar('Projeto Ômega', [], ['usuario' => true]);

    $usuario = cabecalhoComNome(usuarioDoKit('admin', 'maria@example.com'), 'Maria Ômega');
    $html    = cabecalhoAbrir($usuario, '/admin');

    $nomes = cabecalhoNos($html, 'kit-usuario__nome');

    expect($nomes)->not->toBeEmpty()
        ->and(trim($nomes[0]->textContent))->toBe('Maria Ômega');

    $bloco   = strpos($html, 'class="kit-usuario"');
    $gatilho = strpos($html, 'fi-user-menu-trigger');

    expect($bloco)->not->toBeFalse()
        ->and($gatilho)->not->toBeFalse()
        ->and($bloco)->toBeLessThan($gatilho);

    $cabecalhos = cabecalhoDom($html)->query('//*[@data-user-menu-header]');

    expect($cabecalhos->length)->toBeGreaterThan(0)
        ->and($cabecalhos->item(0)->textContent)->toContain('maria@example.com')
        ->and($cabecalhos->item(0)->textContent)->toContain('Admin');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R11 — o detalhe
|--------------------------------------------------------------------------
*/

it('[CT-14] o detalhe segue a opção e o painel corrente', function (string $detalhe, string $persona, string $painel, string $mostra, string $naoMostra): void {
    cabecalhoPreparar('Projeto Ômega', [], ['usuario' => true, 'detalhe_do_usuario' => $detalhe]);

    $usuario = match ($persona) {
        'admin'         => usuarioDoKit('admin', 'pessoa@example.com'),
        'panel_user'    => usuarioDoKit('panel_user', 'pessoa@example.com'),
        'master_global' => usuarioDoKit('master_global', 'pessoa@example.com'),
        'admin e infra' => usuarioDoKit('admin', 'pessoa@example.com')->assignRole('infra'),
    };

    $html     = cabecalhoAbrir($usuario, $painel);
    $detalhes = cabecalhoNos($html, 'kit-usuario__detalhe');
    $blocos   = cabecalhoNos($html, 'kit-usuario');

    expect($detalhes)->not->toBeEmpty();

    foreach ($detalhes as $no) {
        expect(trim($no->textContent))->toBe($mostra);
    }

    foreach ($blocos as $bloco) {
        expect($bloco->textContent)->not->toContain($naoMostra);
    }
})->with([
    'perfil admin'                 => ['perfil', 'admin', '/admin', 'Admin', 'pessoa@example.com'],
    'perfil panel_user'            => ['perfil', 'panel_user', '/app', 'Painel App', 'pessoa@example.com'],
    'perfil master no /admin'      => ['perfil', 'master_global', '/admin', 'Administrador Geral', 'master_global'],
    'perfil master no /infra'      => ['perfil', 'master_global', '/infra', 'Administrador Geral', 'pessoa@example.com'],
    'perfil master no /app'        => ['perfil', 'master_global', '/app', 'Administrador Geral', 'pessoa@example.com'],
    'perfil admin e infra /infra'  => ['perfil', 'admin e infra', '/infra', 'Infra', 'Admin'],
    'perfil admin e infra /admin'  => ['perfil', 'admin e infra', '/admin', 'Admin', 'Infra'],
    'email admin'                  => ['email', 'admin', '/admin', 'pessoa@example.com', 'Admin'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R13 — o detalhe nasce em perfil
|--------------------------------------------------------------------------
*/

it('[CT-16] ligar só o bloco do usuário mostra o papel', function (): void {
    cabecalhoPreparar('Projeto Ômega', [], ['usuario' => true]);

    $html     = cabecalhoAbrir(usuarioDoKit('admin', 'pessoa@example.com'), '/admin');
    $detalhes = cabecalhoNos($html, 'kit-usuario__detalhe');

    expect($detalhes)->not->toBeEmpty();

    foreach ($detalhes as $no) {
        expect(trim($no->textContent))->toBe('Admin');
    }

    foreach (cabecalhoNos($html, 'kit-usuario') as $bloco) {
        expect($bloco->textContent)->not->toContain('pessoa@example.com');
    }
})->group('kit');

/*
|--------------------------------------------------------------------------
| R14 — detalhe fora do domínio gravado direto
|--------------------------------------------------------------------------
*/

it('[CT-18] o painel abre com detalhe fora do domínio gravado direto', function (string $valor): void {
    cabecalhoPreparar('Projeto Ômega', [], ['usuario' => true, 'detalhe_do_usuario' => $valor]);

    $html     = cabecalhoAbrir(usuarioDoKit('admin', 'pessoa@example.com'), '/admin');
    $detalhes = cabecalhoNos($html, 'kit-usuario__detalhe');

    expect($detalhes)->not->toBeEmpty();

    foreach ($detalhes as $no) {
        expect(trim($no->textContent))->toBe('Admin');
    }

    foreach (cabecalhoNos($html, 'kit-usuario') as $bloco) {
        expect($bloco->textContent)->not->toContain('pessoa@example.com');

        if ($valor !== '') {
            expect($bloco->textContent)->not->toContain($valor);
        }
    }
})->with(['telefone', ''])->group('kit');

/*
|--------------------------------------------------------------------------
| R15 — valores de fábrica
|--------------------------------------------------------------------------
*/

it('[CT-19] as cinco opções nascem desligadas e em perfil', function (): void {
    foreach (['NOME_DO_PROJETO', 'NOME_DO_PAINEL', 'LOGO_DA_MARCA', 'USUARIO', 'DETALHE_DO_USUARIO'] as $variavel) {
        expect(env("KIT_CABECALHO_{$variavel}"))->toBeNull();
    }

    foreach (['nome_do_projeto', 'nome_do_painel', 'logo_da_marca', 'usuario'] as $interruptor) {
        expect(configuracaoGravada("cabecalho_{$interruptor}"))->toBeFalse()
            ->and(config("kit.cabecalho.{$interruptor}"))->toBeFalse();
    }

    expect(configuracaoGravada('cabecalho_detalhe_do_usuario'))->toBe('perfil')
        ->and(config('kit.cabecalho.detalhe_do_usuario'))->toBe('perfil');
})->group('kit');

it('[CT-20] o .env.example traz o par de cada opção com o mesmo default', function (): void {
    $lidos = Dotenv\Dotenv::parse((string) file_get_contents(base_path('.env.example')));

    foreach (['NOME_DO_PROJETO', 'NOME_DO_PAINEL', 'LOGO_DA_MARCA', 'USUARIO'] as $interruptor) {
        expect($lidos["KIT_CABECALHO_{$interruptor}"] ?? null)->toBe('false');
    }

    expect($lidos['KIT_CABECALHO_DETALHE_DO_USUARIO'] ?? null)->toBe('perfil');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R22 — tela pública
|--------------------------------------------------------------------------
*/

it('[CT-28] o visitante vê a marca de hoje no login, com tudo ligado', function (): void {
    cabecalhoPreparar('Projeto Ômega', ['logo' => 'kit/logo-ct.png'], [
        'nome_do_projeto' => true,
        'nome_do_painel'  => true,
        'logo_da_marca'   => true,
        'usuario'         => true,
    ]);

    $resposta = $this->get('/admin/login');

    $resposta->assertSuccessful();

    $html  = (string) $resposta->getContent();
    $marca = cabecalhoMarca($html);

    expect(cabecalhoNos($html, 'kit-cabecalho'))->toBeEmpty()
        ->and(cabecalhoNos($html, 'kit-usuario'))->toBeEmpty()
        ->and($marca['imgs'])->not->toBeEmpty()
        ->and(implode('|', $marca['imgs']))->toContain('storage/kit/logo-ct.png');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R25 — guarda de CSS (`.ai/rules/css-filament.md`)
|--------------------------------------------------------------------------
*/

it('[CT-34] as classes das duas blades do cabeçalho têm regra no CSS do kit', function (): void {
    $classes = [];

    foreach (['cabecalho-do-painel', 'usuario-no-cabecalho'] as $blade) {
        $fonte = (string) file_get_contents(base_path("resources/views/filament/{$blade}.blade.php"));

        preg_match_all('~\bclass="([^"]*)"~', $fonte, $atributos);

        foreach ($atributos[1] as $valor) {
            $valor = (string) preg_replace('~\{\{.*?\}\}~s', ' ', $valor);

            foreach (preg_split('~\s+~', trim($valor), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $classe) {
                if (str_starts_with($classe, 'kit-')) {
                    $classes[$classe] = true;
                }
            }
        }
    }

    $classes = array_keys($classes);

    // Controle positivo: extrator quebrado devolveria lista vazia e a guarda ficaria verde sobre nada.
    expect(count($classes))->toBeGreaterThanOrEqual(8);

    $css = (string) preg_replace('~/\*.*?\*/~s', '', (string) file_get_contents(base_path('resources/css/filament/kit.css')));

    foreach ($classes as $classe) {
        expect(preg_match('~\.'.preg_quote($classe, '~').'(?![\w-])~', $css))->toBe(1, "sem regra no kit.css: .{$classe}");
    }

    expect($css)->toContain('.dark:root .kit-cabecalho')
        ->and($css)->toContain('.dark:root .kit-usuario');

    $dentroDaMedia = false;

    if (preg_match_all('~@media\s*\(min-width:\s*768px\)\s*\{~', $css, $aberturas, PREG_OFFSET_CAPTURE) > 0) {
        foreach ($aberturas[0] as [$texto, $posicao]) {
            $inicio    = $posicao + strlen($texto);
            $nivel     = 1;
            $tamanho   = strlen($css);
            $cursor    = $inicio;

            while ($nivel > 0 && $cursor < $tamanho) {
                $nivel += match ($css[$cursor]) {
                    '{'     => 1,
                    '}'     => -1,
                    default => 0,
                };
                $cursor++;
            }

            if (str_contains(substr($css, $inicio, $cursor - $inicio), '.kit-usuario')) {
                $dentroDaMedia = true;
            }
        }
    }

    expect($dentroDaMedia)->toBeTrue();

    $publicado = (string) file_get_contents(base_path('public/css/kit/kit-correcoes.css'));

    expect($publicado)->toContain('.kit-cabecalho')
        ->and($publicado)->toContain('.kit-usuario');
})->group('kit');

<?php

use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * O site de documentação em `docs/`, publicado pelo Jekyll embutido do GitHub Pages.
 *
 * IDs de CT em `wikis/specs/feat/site-de-documentacao/site-de-documentacao/04-casos-de-teste.md`.
 *
 * Tudo aqui afirma sobre ARQUIVO EM DISCO — não há código de runtime nesta feature. O que a
 * suíte não alcança (o Pages habilitado, o `baseurl` em produção, a busca do tema) está na
 * seção "Fora do alcance" da wiki, com a verificação manual e a evidência colada no `03`.
 *
 * O oráculo central é o BASELINE CONGELADO (`fixtures/baseline-readme.php`): os 115 títulos dos
 * dois READMEs medidos ANTES da migração. É o único jeito de falsificar "o conteúdo migrou"
 * sem reafirmar o que a implementação fez — e CT-24 prova que o baseline é o de antes.
 */
$baseline = require __DIR__.'/fixtures/baseline-readme.php';

/**
 * `docs/` é `export-ignore`: num projeto nascido do `create-project` ele não existe, e todo
 * cenário aqui ficaria vermelho lá. A sentinela é `.github` — e NÃO `docs/`, que seria
 * auto-anulante (CT-10, em `RedeDeDocumentacaoTest`).
 */
beforeEach(function (): void {
    if (! naArvoreDoKit()) {
        $this->markTestSkipped('Fora da árvore do kit não há site a conferir: o diretório do site é export-ignore.');
    }
});

/**
 * Os títulos (qualquer nível) de um markdown, sem espaço sobrando.
 *
 * @return list<string>
 */
function titulosDoMarkdown(string $markdown): array
{
    preg_match_all('/^#{1,6} (.+?)\s*$/m', $markdown, $achados);

    return $achados[1];
}

function readmeDe(string $idioma): string
{
    return (string) file_get_contents(base_path($idioma === 'en' ? 'README.en.md' : 'README.md'));
}

/**
 * O front matter YAML de uma página (só o subconjunto `chave: valor` que o tema usa).
 *
 * @return array<string, string>
 */
function frontMatterDe(string $pagina): array
{
    if (preg_match('/\A---\n(.*?)\n---/s', $pagina, $bloco) !== 1) {
        return [];
    }

    $campos = [];

    foreach (explode("\n", $bloco[1]) as $linha) {
        if (preg_match('/^(\w+):\s*(.*)$/', $linha, $par) === 1) {
            $campos[$par[1]] = trim($par[2], " \"'");
        }
    }

    return $campos;
}

/**
 * O id que o kramdown gera para um título — e que uma âncora `#assim` precisa acertar.
 *
 * O kramdown (`auto_ids`) descarta tudo que não é ASCII alfanumérico, espaço ou hífen, baixa
 * a caixa e troca espaço por hífen. Aplicar a MESMA normalização à âncora escrita e ao título
 * torna a comparação indiferente a acento e pontuação, que é o que o gerador faz.
 */
function idDeTitulo(string $texto): string
{
    $ascii = strtolower((string) preg_replace('/[^a-zA-Z0-9 \-]/', '', $texto));

    return trim((string) preg_replace('/[\s\-]+/', '-', $ascii), '-');
}

/** Resolve `../recursos/x.md` a partir do diretório de uma página, sem tocar o disco. */
function caminhoResolvido(string $paginaDeOrigem, string $link): string
{
    $partes = [];

    foreach (explode('/', dirname($paginaDeOrigem).'/'.$link) as $segmento) {
        if ($segmento === '..') {
            array_pop($partes);
        } elseif ($segmento !== '.' && $segmento !== '') {
            $partes[] = $segmento;
        }
    }

    $caminho = implode('/', $partes);

    return str_ends_with($link, '/') || $caminho === '' ? "{$caminho}/index.md" : $caminho;
}

/** Só o detector de Liquid, isolado para receber controle positivo em CT-18. */
function acusaLiquidSolto(string $texto): bool
{
    $semBlocoRaw = (string) preg_replace('/\{%\s*raw\s*%\}.*?\{%\s*endraw\s*%\}/s', '', $texto);

    return preg_match('/\{\{|\{%/', $semBlocoRaw) === 1;
}

/*
|--------------------------------------------------------------------------
| R1 — todo bloco do baseline chega a exatamente um destino, nos dois idiomas
|--------------------------------------------------------------------------
*/

/**
 * CT-01 — os 115 títulos do baseline, cada um procurado como TÍTULO em algum destino
 * (README ou página), nos dois idiomas.
 *
 * "Um destino só" vale dentro do site: título repetido em duas páginas só é legítimo quando
 * o PRÓPRIO baseline já o repetia (o roteiro de features tem um h3 "Multi-tenancy (opt-in)" e
 * um h3 "IA" que coincidem com títulos de outras seções). README × site é assunto de CT-03.
 */
it('[CT-01] todo título do baseline existe no destino, e num destino só dentro do site', function (string $idioma) use ($baseline): void {
    $titulosPorArquivo = ['README' => titulosDoMarkdown(readmeDe($idioma))]
        + array_map(titulosDoMarkdown(...), paginasDoSite($idioma));

    $semDestino        = [];
    $repetidosNoSite   = [];

    foreach ($baseline[$idioma] as [$nivel, $titulo]) {
        $onde = array_keys(array_filter(
            $titulosPorArquivo,
            static fn (array $titulos): bool => in_array($titulo, $titulos, true),
        ));

        if ($onde === []) {
            $semDestino[] = "h{$nivel} {$titulo}";
        }

        if (count(array_diff($onde, ['README'])) > 1) {
            $repetidosNoSite[] = $titulo;
        }
    }

    $repetidosNoBaseline = array_keys(array_filter(
        array_count_values(array_column($baseline[$idioma], 1)),
        static fn (int $vezes): bool => $vezes > 1,
    ));

    expect($semDestino)->toBe([], "Títulos do baseline ({$idioma}) que não chegaram a destino nenhum")
        ->and(array_values(array_unique($repetidosNoSite)))->toEqualCanonicalizing($repetidosNoBaseline);
})->with(['pt', 'en']);

/**
 * CT-24 — o baseline é o de ANTES, não uma foto do depois.
 *
 * Cardinalidade não é identidade: um fixture gerado do resultado teria 32 e 83 entradas e
 * faria CT-01 passar por construção. Re-derivar do commit declarado é o que fecha isso.
 *
 * O único desvio de execução aqui é um clone raso sem aquele commit — o CI faz checkout com
 * profundidade 1. Não é guarda sobre `docs/`: é sobre o histórico git.
 */
it('[CT-24] o baseline é o de antes da migração, não uma foto do depois', function (string $idioma) use ($baseline): void {
    $git = new Process(['git', 'show', "{$baseline['sha']}:".($idioma === 'en' ? 'README.en.md' : 'README.md')], base_path());
    $git->run();

    if (! $git->isSuccessful()) {
        $this->markTestSkipped("Clone raso: o commit {$baseline['sha']} não está disponível para re-derivar o baseline.");
    }

    preg_match_all('/^(#{2,3}) (.+)$/m', $git->getOutput(), $achados, PREG_SET_ORDER);
    $rederivado = array_map(static fn (array $a): array => [strlen($a[1]), trim($a[2])], $achados);

    $porNivel = array_count_values(array_column($baseline[$idioma], 0));

    expect($rederivado)->toBe($baseline[$idioma])
        ->and($porNivel[2])->toBe(32)
        ->and($porNivel[3])->toBe(83);
})->with(['pt', 'en']);

/**
 * CT-02 — a página de destino carrega o conteúdo, não um esqueleto: os h3 que o baseline dá
 * à seção estão lá, e a página tem tamanho de seção migrada. A contagem de h3 esperados é a
 * guarda do próprio dataset — um prefixo de seção que não casa devolveria zero filhos e
 * passaria em silêncio.
 */
it('[CT-02] a página de destino carrega o conteúdo, não um esqueleto', function (string $idioma, string $inicioDaSecao, string $pagina, int $minimoDeLinhas, int $h3Esperados) use ($baseline): void {
    $filhos = [];
    $dentro = false;

    foreach ($baseline[$idioma] as [$nivel, $titulo]) {
        if ($nivel === 2) {
            $dentro = str_starts_with($titulo, $inicioDaSecao);

            continue;
        }

        if ($dentro) {
            $filhos[] = $titulo;
        }
    }

    $conteudo = paginasDoSite($idioma)[$pagina] ?? '';

    expect($filhos)->toHaveCount($h3Esperados)
        ->and(array_values(array_diff($filhos, titulosDoMarkdown($conteudo))))->toBe([])
        ->and(substr_count($conteudo, "\n"))->toBeGreaterThanOrEqual($minimoDeLinhas);
})->with([
    'login social pt (maior seção)' => ['pt', 'Login social', 'autenticacao/login-social.md', 300, 12],
    'login social en'               => ['en', 'Social login', 'autenticacao/login-social.md', 300, 12],
    'import e export pt'            => ['pt', 'Import e export', 'recursos/import-export-csv.md', 150, 10],
    'estudo pt (menor seção)'       => ['pt', 'Estudo: Advanced Tables', 'referencia/estudo-advanced-tables.md', 4, 0],
    'estudo en'                     => ['en', 'Study: Advanced Tables', 'referencia/estudo-advanced-tables.md', 4, 0],
]);

/**
 * CT-03 — nada que migrou continua no README (exaustivo, nos dois idiomas).
 *
 * Por classe do mapa: `site` saiu do README e está no site; `landing` ficou e NÃO está no
 * site; `ambos` ficou resumida no README. E nenhum h3 vive nos dois lados — o que migrou
 * saiu da origem, senão não foi migração, foi cópia (M3), e as duas cópias divergem sozinhas.
 */
it('[CT-03] nada que migrou continua no README', function (string $idioma) use ($baseline): void {
    $noReadme = titulosDoMarkdown(readmeDe($idioma));
    $noSite   = array_merge(...array_values(array_map(titulosDoMarkdown(...), paginasDoSite($idioma))));
    $h2       = array_values(array_filter($baseline[$idioma], static fn (array $t): bool => $t[0] === 2));
    $h3       = array_column(array_filter($baseline[$idioma], static fn (array $t): bool => $t[0] === 3), 1);

    $foraDoLugar = [];

    foreach ($baseline['classificacao'] as $indice => $classe) {
        $titulo    = $h2[$indice - 1][1];
        $ficou     = in_array($titulo, $noReadme, true);
        $migrou    = in_array($titulo, $noSite, true);
        $esperado  = match ($classe) {
            'site'    => ! $ficou && $migrou,
            'landing' => $ficou && ! $migrou,
            'ambos'   => $ficou,
        };

        if (! $esperado) {
            $foraDoLugar[] = "h2 #{$indice} ({$classe}) {$titulo}";
        }
    }

    expect($foraDoLugar)->toBe([])
        ->and(array_values(array_intersect($h3, $noReadme, $noSite)))->toBe([]);
})->with(['pt', 'en']);

/*
|--------------------------------------------------------------------------
| R2 — /pt/ e /en/ têm o mesmo conjunto de páginas e a mesma estrutura
|--------------------------------------------------------------------------
*/

/** CT-04 — comparação de conjunto NOS DOIS SENTIDOS: `en ⊆ pt` passa com página faltando no inglês. */
it('[CT-04] nenhum idioma tem página que o outro não tem', function (): void {
    $pt = array_keys(paginasDoSite('pt'));
    $en = array_keys(paginasDoSite('en'));

    expect(array_values(array_diff($pt, $en)))->toBe([], 'só em português')
        ->and(array_values(array_diff($en, $pt)))->toBe([], 'só em inglês');
});

/**
 * CT-05 — nenhuma página inglesa é resumo da portuguesa. Sobre TODAS as páginas (o `Esquema`
 * da wiki amostrava três; o custo de percorrer as 30 é zero e M9 morre junto).
 *
 * A medida é em CARACTERES, não em linhas: a quebra de linha é escolha de quem traduz (um
 * bullet de 12 linhas no inglês é UMA linha no português, medido em `convencoes-do-kit`).
 * Um `<!-- TODO: translate -->` mais o título (M8) fica a 90% de distância de qualquer página.
 */
it('[CT-05] nenhuma página inglesa é um resumo da portuguesa', function (): void {
    $pt = paginasDoSite('pt');
    $en = paginasDoSite('en');

    $desvios = [];

    foreach ($pt as $caminho => $conteudoPt) {
        $conteudoEn = $en[$caminho] ?? '';
        $titulosPt  = count(titulosDoMarkdown($conteudoPt));
        $titulosEn  = count(titulosDoMarkdown($conteudoEn));
        $tamanhoPt  = mb_strlen($conteudoPt);
        $tamanhoEn  = mb_strlen($conteudoEn);
        $tolerancia = max(0.15 * $tamanhoPt, 300);

        if ($titulosPt !== $titulosEn || abs($tamanhoPt - $tamanhoEn) > $tolerancia) {
            $desvios[] = "{$caminho}: títulos {$titulosPt}×{$titulosEn}, caracteres {$tamanhoPt}×{$tamanhoEn}";
        }
    }

    expect($desvios)->toBe([]);
});

/**
 * CT-19 — cada árvore está no seu idioma, nos dois sentidos. Os tokens de CT-06 são
 * identificadores idênticos nos dois idiomas, então um `cp -r docs/pt docs/en` passaria por
 * CT-04, CT-05 e CT-06. Marcador lexical: partículas de altíssima frequência e sem colisão.
 */
it('[CT-19] cada árvore de idioma está no seu idioma', function (string $idioma, string $marcadorProprio, string $marcadorAlheio): void {
    $semMarcador  = [];
    $foraDoIdioma = [];

    foreach (paginasDoSite($idioma) as $caminho => $conteudo) {
        if (preg_match($marcadorProprio, $conteudo) !== 1) {
            $semMarcador[] = $caminho;
        }

        if (preg_match($marcadorAlheio, $conteudo) === 1) {
            $foraDoIdioma[] = $caminho;
        }
    }

    expect($semMarcador)->toBe([], "páginas de {$idioma} sem marcador do próprio idioma")
        ->and($foraDoIdioma)->toBe([], "páginas de {$idioma} com marcador do outro idioma");
})->with([
    'português' => ['pt', '/\b(não|que|é|são|sobre)\b/u', '/\bthe\b/'],
    'inglês'    => ['en', '/\bthe\b/', '/\b(não|que|é|são|sobre)\b/u'],
]);

/*
|--------------------------------------------------------------------------
| R3 — as 4 divergências PT/EN conhecidas não sobrevivem à migração
|--------------------------------------------------------------------------
*/

/**
 * CT-06 — cada omissão conhecida do inglês chega à página de destino em inglês (e a
 * portuguesa carrega o mesmo token). Afirma sobre o DESTINO, não sobre o README: o README
 * encolheu, e afirmar lá morreria com a migração.
 *
 * `F-06` é token fraco de propósito — a linha existe na tabela mesmo com a cláusula omitida.
 * Por isso a linha dele tem um segundo oráculo, sobre a CÉLULA: a linha da tabela menciona
 * login social (M13).
 */
it('[CT-06] cada omissão conhecida do inglês chega à página de destino', function (string $pagina, string $token, ?string $naMesmaLinha): void {
    $en = paginasDoSite('en')[$pagina] ?? '';
    $pt = paginasDoSite('pt')[$pagina] ?? '';

    expect($en)->toContain($token)
        ->and($pt)->toContain($token);

    if ($naMesmaLinha !== null) {
        $linhas = array_filter(explode("\n", $en), static fn (string $linha): bool => str_contains($linha, $token));

        expect($linhas)->not->toBeEmpty()
            ->and(implode("\n", $linhas))->toMatch($naMesmaLinha);
    }
})->with([
    'vínculo de convite × login social' => ['autenticacao/login-social.md', 'travas-de-escalada-de-papeis', null],
    'onde ficam as ADRs do kit'         => ['operacao/agentes-de-ia.md', 'export-ignore', null],
    'convenção de abas'                 => ['operacao/convencoes-do-kit.md', 'getTabs()', null],
    'F-06 volta por login social'       => ['operacao/roteiro-de-features.md', 'F-06', '/social login/i'],
]);

/*
|--------------------------------------------------------------------------
| R5 — nada da documentação do site vaza para o projeto instalado
|--------------------------------------------------------------------------
*/

/**
 * CT-11 — `docs/` fora do pacote distribuído, medido pelo `git archive` e não pelo texto do
 * `.gitattributes`: um padrão que não alcança subdiretório (`/docs/*.md`, M22) é idêntico no
 * texto e diferente no arquivo gerado. `git check-attr` também não serve — o atributo num
 * diretório não se propaga para os filhos na consulta, só no archive.
 *
 * Com controle negativo: `README.md` e `art/install.gif` PRECISAM estar lá, senão o teste
 * não distingue "docs ficou de fora" de "o archive veio vazio".
 */
it('[CT-11] o diretório de documentação fica fora do pacote distribuído', function (): void {
    $tar = tempnam(sys_get_temp_dir(), 'kit').'.tar';

    (new Process(['git', 'archive', '--format=tar', '-o', $tar, 'HEAD', 'docs', 'README.md', 'art/install.gif'], base_path()))->mustRun();

    $entradas = [];

    foreach (new RecursiveIteratorIterator(new PharData($tar)) as $entrada) {
        $caminho    = str_replace('\\', '/', $entrada->getPathname());
        $entradas[] = substr($caminho, strpos($caminho, '.tar/') + 5);
    }

    unset($entrada);
    unlink($tar);

    expect(array_values(array_filter($entradas, static fn (string $e): bool => str_starts_with($e, 'docs/'))))->toBe([])
        ->and($entradas)->toContain('README.md')
        ->and($entradas)->toContain('art/install.gif');
});

/**
 * CT-12 — nenhuma toolchain de documentação encosta na raiz. O conjunto de dependências npm
 * é comparado com o baseline CONGELADO (M63: comparar com `HEAD` depois da migração é
 * tautologia), os scripts `build`/`dev` continuam (M25), e Ruby não aparece na raiz de um
 * projeto PHP+Node (M51).
 *
 * ponytail: para o composer.json a wiki pedia o conjunto congelado; aqui é lista de recusa
 * de geradores de documentação. Os ~70 pacotes PHP mudam toda semana neste kit, e um fixture
 * deles ficaria vermelho em toda adição legítima — ensinando o time a editá-lo sem ler.
 */
it('[CT-12] nenhuma toolchain de documentação encosta na raiz do projeto', function () use ($baseline): void {
    $npm          = json_decode((string) file_get_contents(base_path('package.json')), true);
    $dependencias = array_keys(($npm['dependencies'] ?? []) + ($npm['devDependencies'] ?? []));
    sort($dependencias);

    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);
    $pacotes  = array_keys(($composer['require'] ?? []) + ($composer['require-dev'] ?? []));

    expect($dependencias)->toBe($baseline['npm']['dependencias'])
        ->and(array_keys($npm['scripts'] ?? []))->toContain(...$baseline['npm']['scripts'])
        ->and(preg_grep('/vitepress|docusaurus|mkdocs|jekyll|docsify|daux|phpdocumentor|sphinx|starlight|hugo/i', $pacotes))->toBe([])
        ->and(file_exists(base_path('Gemfile')))->toBeFalse()
        ->and(file_exists(base_path('Gemfile.lock')))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| R6 — o README encolhe nos dois idiomas e todo link que ele carrega resolve
|--------------------------------------------------------------------------
*/

/**
 * CT-13 — a landing encolhe sem se esvaziar, nos dois idiomas. O teto é RAZÃO sobre a medida
 * histórica (30% das linhas de antes), não constante (M30). O piso e as três âncoras matam
 * "README reduzido a badges e um link" (M28): 750 linhas truncadas não ensinam a instalar.
 * Os dois idiomas encolhem juntos (M27 — o modo de falha documentado deste projeto).
 */
it('[CT-13] a landing encolhe sem se esvaziar', function (string $idioma, int $linhasAntes, string $secaoDeRequisitos): void {
    $readme        = readmeDe($idioma);
    $linhas        = substr_count($readme, "\n");
    $linhasDoOutro = substr_count(readmeDe($idioma === 'pt' ? 'en' : 'pt'), "\n");

    expect($linhas)->toBeLessThanOrEqual((int) ($linhasAntes * 0.3))
        ->and($linhas)->toBeGreaterThanOrEqual(100)
        ->and($readme)->toContain('composer create-project')
        ->and($readme)->toContain($secaoDeRequisitos)
        ->and($readme)->toContain('https://gsferro.github.io/filament-starter-kit-easy/')
        ->and(abs($linhas - $linhasDoOutro))->toBeLessThanOrEqual((int) ceil(0.05 * max($linhas, $linhasDoOutro)));
})->with([
    // 2.522 e 2.557 linhas medidas no commit do baseline (o `00` mediu 2.533 no inglês antes
    // das divergências serem corrigidas; a correção acrescentou 24 linhas).
    'português' => ['pt', 2522, '## Requisitos'],
    'inglês'    => ['en', 2557, '## Requirements'],
]);

/**
 * CT-14 — o README leva ao site, e todo link resolve para um arquivo. Com PISO por grupo:
 * "todo link resolve" sobre zero links é verdadeiro, e um README sem link nenhum deixaria 44
 * páginas sem ninguém chegar a elas. Nenhum build pega link morto neste gerador (M29).
 */
it('[CT-14] o README leva ao site, e todo link que ele carrega resolve', function (string $idioma): void {
    preg_match_all('~https://gsferro\.github\.io/filament-starter-kit-easy/([^)\]\s#"<>]*)~', readmeDe($idioma), $achados);
    $links = array_values(array_unique($achados[1]));

    $paginas = ['pt' => paginasDoSite('pt'), 'en' => paginasDoSite('en')];

    $semDestino = [];

    foreach ($links as $link) {
        [$lingua, $resto] = array_pad(explode('/', $link, 2), 2, '');
        $destino          = $resto === '' || str_ends_with($resto, '/') ? "{$resto}index.md" : preg_replace('/\.html$/', '.md', $resto);

        if (! isset($paginas[$lingua][$destino])) {
            $semDestino[] = $link;
        }
    }

    $gruposComLink = array_filter(
        ['comecar', 'autenticacao', 'recursos', 'operacao', 'referencia'],
        static fn (string $grupo): bool => preg_grep("~^{$idioma}/{$grupo}/~", $links) !== [],
    );

    expect($semDestino)->toBe([])
        ->and($gruposComLink)->toHaveCount(5);
})->with(['pt', 'en']);

/**
 * CT-22 — nenhum link interno do site aponta para página inexistente, e nenhuma âncora
 * sobreviveu à migração apontando para uma seção que virou outra página. Com piso próprio:
 * CT-14 conta links do README e CT-20 conta a navegação; nenhum dos dois prova que existe
 * um link de uma página para outra.
 */
it('[CT-22] nenhum link interno do site aponta para página ou âncora inexistente', function (string $idioma): void {
    $paginas = paginasDoSite($idioma);

    $mortos       = [];
    $entrePaginas = 0;

    foreach ($paginas as $caminho => $conteudo) {
        preg_match_all('/\]\(([^)\s]+)\)/', $conteudo, $achados);

        foreach ($achados[1] as $link) {
            if (preg_match('~^(https?:|mailto:)~', $link) === 1) {
                continue;
            }

            [$alvo, $ancora] = array_pad(explode('#', $link, 2), 2, null);
            $destino         = $alvo === '' ? $caminho : caminhoResolvido($caminho, $alvo);

            if ($alvo !== '') {
                $entrePaginas++;
            }

            if (! isset($paginas[$destino])) {
                $mortos[] = "{$caminho} → {$link}";

                continue;
            }

            if ($ancora !== null && ! in_array(idDeTitulo($ancora), array_map(idDeTitulo(...), titulosDoMarkdown($paginas[$destino])), true)) {
                $mortos[] = "{$caminho} → {$link} (âncora sem título correspondente)";
            }
        }
    }

    expect($mortos)->toBe([])
        ->and($entrePaginas)->toBeGreaterThanOrEqual(10);
})->with(['pt', 'en']);

/*
|--------------------------------------------------------------------------
| R7 — o repositório está montado para o GitHub publicar, e no endereço certo
|--------------------------------------------------------------------------
*/

/** CT-15 — o que o build nativo exige em `main:/docs`: config com `baseurl`, home não vazia, e NADA de npm. */
it('[CT-15] a raiz publicada tem o que o build nativo exige', function (): void {
    $config = (string) file_get_contents(base_path('docs/_config.yml'));
    $home   = (string) file_get_contents(base_path('docs/index.md'));

    expect($config)->toMatch('/^baseurl:/m')
        ->and(substr_count($home, "\n"))->toBeGreaterThanOrEqual(5)
        ->and(glob(base_path('docs/package*.json')))->toBe([]);
});

/**
 * CT-25 — nenhum outro mecanismo publica o mesmo site. COM controle positivo: sem ele o
 * cenário não distingue "não há fluxo" de "meu detector não casa nada", e os dois fluxos
 * legítimos do repositório servem de falso conforto.
 */
it('[CT-25] nenhum fluxo de Actions publica o site por fora do build nativo', function (): void {
    $detector = '~deploy-pages|actions-gh-pages|github-pages-deploy|gh-pages|pages-build-deployment~i';

    expect(preg_match($detector, '      - run: git push origin HEAD:gh-pages --force'))->toBe(1);

    $acusados = [];

    foreach (Finder::create()->files()->in(base_path('.github/workflows'))->name('*.yml') as $fluxo) {
        if (preg_match($detector, $fluxo->getContents()) === 1) {
            $acusados[] = $fluxo->getFilename();
        }
    }

    expect($acusados)->toBe([]);
});

/**
 * CT-16 — o `baseurl` é o nome do REPOSITÓRIO, derivado do `homepage` do pacote, e não o
 * nome do PACOTE: `gsferro/starter-kit-easy` vive em `filament-starter-kit-easy`, e derivar
 * do `name` produz um site que funciona em prévia local e quebra publicado (M32, M33).
 * A forma do Jekyll é barra na frente e nenhuma no fim (M44).
 */
it('[CT-16] o endereço base do site é o do repositório, não o do pacote', function (): void {
    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);
    preg_match('/^baseurl:\s*(\S+)/m', (string) file_get_contents(base_path('docs/_config.yml')), $achado);

    $baseurl      = trim($achado[1] ?? '', '"\'');
    $repositorio  = basename((string) $composer['homepage']);
    $nomeDoPacote = explode('/', (string) $composer['name'])[1];

    expect($baseurl)->toBe("/{$repositorio}")
        ->and($baseurl)->toMatch('~^/[^/]+$~')
        ->and($baseurl)->not->toBe("/{$nomeDoPacote}");
});

/*
|--------------------------------------------------------------------------
| R8 — o processo de atualização está escrito, nos dois idiomas
|--------------------------------------------------------------------------
*/

/**
 * CT-17 — cada idioma explica como o site publica: no push para a branch padrão, sem workflow
 * nem build (M59: texto copiado do plano ANTERIOR descreveria um `docs.yml` que não existe), e
 * nomeando onde a origem do Pages se configura — o único passo que não está em arquivo (M60).
 */
it('[CT-17] cada idioma explica como a documentação é publicada', function (string $idioma, string $semWorkflow): void {
    $texto = paginasDoSite($idioma)['operacao/desenvolvendo-o-kit.md'] ?? '';

    expect($texto)->toContain('`main`')
        ->and($texto)->toMatch($semWorkflow)
        ->and($texto)->toContain('Deploy from a branch')
        ->and($texto)->toContain('`/docs`');
})->with([
    'português' => ['pt', '/não há workflow/i'],
    'inglês'    => ['en', '/there is no workflow/i'],
]);

/*
|--------------------------------------------------------------------------
| R9 — a migração não injeta sintaxe que o gerador interpreta como código
|--------------------------------------------------------------------------
*/

/**
 * CT-18 — nenhum `{{` nem `{%` solto: o Liquid processa os dois ATÉ DENTRO de bloco de código,
 * e um exemplo de Blade some da página publicada sem nada ficar vermelho (M37). Os dois
 * controles matam o detector com erro de escape que nunca casa nada (M64, M55).
 */
it('[CT-18] nenhuma página deixa delimitador de template solto', function (): void {
    expect(acusaLiquidSolto('Olá, {{ $user->name }}'))->toBeTrue()
        ->and(acusaLiquidSolto('{% if $x %}sim{% endif %}'))->toBeTrue()
        ->and(acusaLiquidSolto('{% raw %}{{ $user->name }}{% endraw %}'))->toBeFalse();

    $acusadas = [];

    foreach (['pt', 'en'] as $idioma) {
        foreach (paginasDoSite($idioma) as $caminho => $conteudo) {
            if (acusaLiquidSolto($conteudo)) {
                $acusadas[] = "{$idioma}/{$caminho}";
            }
        }
    }

    expect($acusadas)->toBe([]);
});

/*
|--------------------------------------------------------------------------
| R10 — toda página é alcançável, e as duas árvores de navegação estão em paridade
|--------------------------------------------------------------------------
*/

/**
 * CT-20 — toda página aparece na navegação do seu idioma. No `just-the-docs` a árvore É o
 * front matter: `parent`/`grand_parent` por título, `has_children` no índice. Então "está na
 * navegação" = o pai declarado é o título REAL do índice do diretório (M46: um índice
 * renomeado deixa órfãs todas as filhas), e "não referencia inexistente" é a mesma asserção.
 */
it('[CT-20] toda página aparece na navegação do idioma a que pertence', function (string $idioma): void {
    $frontMatter = array_map(frontMatterDe(...), paginasDoSite($idioma));
    $raiz        = $frontMatter['index.md']['title'] ?? null;

    $folhas = array_filter(array_keys($frontMatter), static fn (string $c): bool => ! str_ends_with($c, 'index.md'));
    $orfas  = [];

    foreach ($frontMatter as $caminho => $campos) {
        if ($caminho === 'index.md') {
            continue;
        }

        if (str_ends_with($caminho, 'index.md')) {
            $ok = ($campos['parent'] ?? null) === $raiz && ($campos['has_children'] ?? null) === 'true';
        } else {
            $pai = $frontMatter[dirname($caminho).'/index.md']['title'] ?? null;
            $ok  = $pai !== null
                && ($campos['parent'] ?? null) === $pai
                && ($campos['grand_parent'] ?? null) === $raiz
                && isset($campos['title'], $campos['nav_order']);
        }

        if (! $ok) {
            $orfas[] = $caminho;
        }
    }

    expect($raiz)->not->toBeNull()
        ->and(($frontMatter['index.md']['has_children'] ?? null))->toBe('true')
        ->and(count($folhas))->toBeGreaterThanOrEqual(22)
        ->and($orfas)->toBe([]);
})->with(['pt', 'en']);

/**
 * CT-23 — as duas árvores têm a mesma forma (mesma ordem de navegação em cada nó, mesmos nós
 * com filhos — M56) e cada uma está no seu idioma. `cp` do front matter passa nos dois
 * primeiros; o terceiro usa marcador lexical (acento de um lado, partícula do outro), porque
 * "nenhum rótulo igual" é inexequível: `Multi-tenancy (opt-in)` é legítimo nos dois (M57).
 */
it('[CT-23] as duas árvores de navegação têm a mesma estrutura, cada uma no seu idioma', function (): void {
    $pt = array_map(frontMatterDe(...), paginasDoSite('pt'));
    $en = array_map(frontMatterDe(...), paginasDoSite('en'));

    $divergem = [];

    foreach ($pt as $caminho => $campos) {
        if ($caminho === 'index.md') {
            continue; // as raízes são irmãs no menu: a ordem delas DEVE diferir
        }

        $outro = $en[$caminho] ?? [];

        if (($campos['nav_order'] ?? null) !== ($outro['nav_order'] ?? null)
            || isset($campos['has_children']) !== isset($outro['has_children'])) {
            $divergem[] = $caminho;
        }
    }

    $rotulosPt  = array_column($pt, 'title');
    $rotulosEn  = array_column($en, 'title');
    $marcadorPt = '/[áàâãéêíóôõúç]/iu';
    $marcadorEn = '/\b(the|and|with|your)\b/i';

    expect($divergem)->toBe([])
        ->and(preg_grep($marcadorPt, $rotulosPt))->not->toBeEmpty()
        ->and(preg_grep($marcadorEn, $rotulosPt))->toBe([])
        ->and(preg_grep($marcadorEn, $rotulosEn))->not->toBeEmpty()
        ->and(preg_grep($marcadorPt, $rotulosEn))->toBe([]);
});

/*
|--------------------------------------------------------------------------
| R11 — a mídia é imagem e GIF, e não duplica o peso do repositório
|--------------------------------------------------------------------------
*/

/**
 * CT-21 — sem vídeo (M48), sem cópia de `art/` (M49 — nenhum arquivo além de markdown e da
 * config), sem ponteiro de LFS que o Pages não serve (M50), e toda imagem ABSOLUTA como na
 * origem (M58: caminho relativo resolve na prévia e quebra publicado pelo `baseurl`).
 */
it('[CT-21] a documentação não carrega vídeo, cópia de mídia, ponteiro de LFS nem imagem relativa', function (): void {
    $binarios = $lfs = $video = $relativas = [];

    foreach (Finder::create()->files()->in(base_path('docs')) as $arquivo) {
        $relativo = str_replace('\\', '/', $arquivo->getRelativePathname());
        $conteudo = $arquivo->getContents();

        if (! in_array($arquivo->getExtension(), ['md', 'yml'], true)) {
            $binarios[] = $relativo;
        }

        if (str_starts_with($conteudo, 'version https://git-lfs')) {
            $lfs[] = $relativo;
        }

        if (preg_match('~<video|<iframe|youtube\.com|youtu\.be|vimeo\.com~i', $conteudo) === 1) {
            $video[] = $relativo;
        }

        if (preg_match('~!\[[^\]]*\]\((?!https?://)~', $conteudo) === 1) {
            $relativas[] = $relativo;
        }
    }

    expect($binarios)->toBe([], 'arquivo que não é markdown nem config em docs/')
        ->and($lfs)->toBe([], 'ponteiro de LFS')
        ->and($video)->toBe([], 'vídeo ou embed')
        ->and($relativas)->toBe([], 'imagem com caminho relativo');
});

<?php

use Filament\Facades\Filament;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
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
 * Os títulos de um markdown: o `title` do front-matter mais os cabeçalhos do corpo.
 *
 * O front-matter passou a contar na migração para o Starlight (2026-09-19), e não é ajuste de
 * teste para ficar verde: **o título da página mudou de lugar, não sumiu**. No just-the-docs o
 * `title` ia para a aba do navegador e o H1 era escrito à mão no corpo — os dois conviviam. O
 * Starlight RENDERIZA o `title` como H1 da página, então manter o do corpo produzia o título
 * duas vezes na tela e um segundo H1 no documento, que é defeito de acessibilidade.
 *
 * Sem esta linha o baseline congelado de 115 títulos acusaria 66 sumiços — e o sumiço seria
 * falso: o título está lá, no front-matter, e é ele que o leitor vê no lugar do H1.
 */
function titulosDoMarkdown(string $markdown): array
{
    $titulos = [];

    if (preg_match('/\A---\n(.*?)\n---\n/s', $markdown, $frente) === 1
        && preg_match('/^title:\s*(.+?)\s*$/m', $frente[1], $doFrontMatter) === 1) {
        $titulos[] = trim($doFrontMatter[1], "\"'");
    }

    preg_match_all('/^#{1,6} (.+?)\s*$/m', $markdown, $achados);

    return array_merge($titulos, $achados[1]);
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
    /*
     * Link ABSOLUTO do site — a forma que a migração para o Starlight produziu, e não por gosto.
     *
     * O Jekyll publicava `convites.html` e o Starlight publica `convites/`. A profundidade de todo
     * link relativo MUDA com isso: de `/pt/autenticacao/convites/`, um `../recursos/x/` chega em
     * `/pt/autenticacao/recursos/x/` — página errada, e que em alguns casos existe. Por isso o
     * conversor reescreveu cada link como caminho absoluto, e este resolvedor precisa conhecê-lo.
     *
     * O mapa de URL para arquivo sai da profundidade, e a árvore tem exatamente dois níveis:
     *
     * | URL              | Arquivo             |
     * |------------------|---------------------|
     * | `/pt/`           | `index.md`          |
     * | `/pt/recursos/`  | `recursos/index.md` |
     * | `/pt/recursos/x/`| `recursos/x.md`     |
     */
    if (str_starts_with($link, '/')) {
        $semIdioma = trim((string) preg_replace('~^/(pt|en)(/|$)~', '', $link), '/');

        if ($semIdioma === '') {
            return 'index.md';
        }

        return str_contains($semIdioma, '/') ? "{$semIdioma}.md" : "{$semIdioma}/index.md";
    }

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
 * CT-14 — o README leva ao site, e todo link que ele carrega resolve para um arquivo. Com PISO por grupo:
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

/**
 * CT-15 — o que a publicação exige, e o que ela NÃO pode ter perto do conteúdo.
 *
 * **Afirmação trocada junto com o mecanismo** (ADR-01/ADR-02 da wiki `site-starlight`). Antes era
 * sobre o build nativo do Pages: `_config.yml` com `baseurl` e uma `index.md` na raiz de `docs/`.
 * Hoje o gerador é o Astro, o `base` vem do workflow por `DOCS_BASE`, e `docs/` não tem mais raiz
 * própria — cada idioma tem a sua landing e `/` redireciona.
 *
 * O que sobrevive intacto é a parte que nunca foi sobre o Jekyll: **nenhum manifesto de npm perto
 * do conteúdo**. É a ADR-02 da wiki ancestral, e o `[CT-12]` a protege do lado da raiz do
 * projeto; aqui ela é protegida do lado de `docs/`. O toolchain mora em `site/`, e só lá.
 */
it('[CT-15] a publicação tem o que precisa, e o conteúdo não carrega toolchain', function (): void {
    $config = (string) file_get_contents(base_path('site/astro.config.mjs'));

    expect(glob(base_path('docs/package*.json')))->toBe([], 'manifesto de npm dentro do conteúdo')
        ->and($config)->toContain('DOCS_BASE')
        ->and(is_file(base_path('site/package-lock.json')))->toBeTrue('o `npm ci` do workflow exige o lock');

    foreach (['pt', 'en'] as $idioma) {
        $landing = (string) file_get_contents(base_path("docs/{$idioma}/index.md"));

        expect($landing)->toContain('template: splash')
            ->and(substr_count($landing, "\n"))->toBeGreaterThanOrEqual(5);
    }
});

/**
 * CT-27 — o gerador antigo não publica mais, e a configuração dele saiu.
 *
 * ## Por que isto entra no MESMO commit da migração, e não depois
 *
 * O plano original deixava o `_config.yml` para um passo final separado, com o argumento de que
 * os dois geradores conseguiriam ler a mesma árvore por um commit — um rollback barato.
 *
 * **O argumento é falso, e a implementação provou.** A transformação do conteúdo é para o
 * Starlight: o H1 saiu do corpo (o Jekyll não o repõe, porque para ele `title` é rótulo de
 * navegação) e os links viraram absolutos sem o `baseurl` (que sob `/filament-starter-kit-easy`
 * dão 404). Assim que isto for para a `main`, o site servido pelo Jekyll está quebrado —
 * manter o `_config.yml` não guarda rollback nenhum, só dá a impressão de guardar.
 *
 * O rollback real é reverter o merge inteiro e voltar o `Source` do Pages para a branch. Está
 * escrito na ADR-01 e no `01-plano-acao.md`, corrigidos depois desta descoberta.
 */
it('[CT-27] o conteudo nao guarda configuracao do gerador antigo', function (): void {
    // `glob` e não `is_file`: o detector do CT-10 procura `is_file(... docs`, e com razão — ele
    // caça cenário que se PULA quando `docs/` não existe. Aqui a ausência é a AFIRMAÇÃO, não a
    // condição, e `glob` já é o idioma deste arquivo para isso (ver CT-15).
    expect(glob(base_path('docs/_config.yml')))->toBe([], 'o Jekyll ainda tem configuração em docs/');

    $comRemoteTheme = [];

    foreach (Finder::create()->files()->in(base_path('docs'))->name(['*.yml', '*.md']) as $arquivo) {
        if (str_contains($arquivo->getContents(), 'remote_theme')) {
            $comRemoteTheme[] = $arquivo->getRelativePathname();
        }
    }

    expect($comRemoteTheme)->toBe([]);
});

/**
 * CT-25 — existe EXATAMENTE UM fluxo que publica o site, e ele é o `pages.yml`.
 *
 * ## A afirmação deste caso foi invertida, e isso é decisão registrada
 *
 * Até a v0.36.1 ele dizia *"nenhum fluxo de Actions publica o site por fora do build nativo"* —
 * porque o site saía do Jekyll embutido do Pages, e um workflow de publicação seria um segundo
 * publicador disputando a mesma saída. A migração para o Starlight trocou o mecanismo: o build
 * nativo não roda Astro, e a publicação passa a ser por Actions (ADR-03 da wiki `site-starlight`).
 *
 * **Reescrito, não removido.** Apagar o caso porque ele incomoda tiraria da rede a única
 * afirmação sobre o mecanismo de publicação — justamente o que mudou. O cenário antigo protegia
 * contra *"alguém acrescentou um publicador"*; o novo protege contra os dois modos de falha que
 * a arquitetura nova tem: **um segundo publicador** disputando o deploy, e **nenhum**, com o site
 * congelado na última versão sem ninguém perceber.
 *
 * O controle positivo continua: sem ele, o caso não distingue "há exatamente um" de "meu detector
 * casa qualquer coisa" nem de "meu detector cegou depois que a action foi renomeada".
 */
it('[CT-25] exatamente um fluxo de Actions publica o site', function (): void {
    $detector = '~deploy-pages|actions-gh-pages|github-pages-deploy|gh-pages~i';

    // Controle positivo: o detector casa uma linha plantada.
    expect(preg_match($detector, '      - uses: actions/deploy-pages@v4'))->toBe(1);

    $publicadores = [];

    foreach (Finder::create()->files()->in(base_path('.github/workflows'))->name('*.yml') as $fluxo) {
        if (preg_match($detector, $fluxo->getContents()) === 1) {
            $publicadores[] = $fluxo->getFilename();
        }
    }

    expect($publicadores)->toBe(['pages.yml']);

    $pages = (string) file_get_contents(base_path('.github/workflows/pages.yml'));

    expect($pages)->toContain('name: github-pages');
});

/**
 * CT-16 — o endereço base do site é o do REPOSITÓRIO, derivado do `homepage` do pacote.
 *
 * A afirmação é a mesma de sempre e o motivo não mudou: `gsferro/starter-kit-easy` é publicado em
 * `filament-starter-kit-easy`, e derivar do `name` do pacote produz um site que funciona em
 * prévia local e quebra publicado — todo link interno apontando para a raiz do domínio.
 *
 * O que mudou é **onde o valor mora**. Era o `baseurl` do `_config.yml`, lido pelo Jekyll; passou
 * a ser `DOCS_BASE`, passado pelo workflow ao build do Astro. O caso segue o valor.
 */
it('[CT-16] o endereço base do site é o do repositório, não o do pacote', function (): void {
    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);

    preg_match('/DOCS_BASE=(\S+)/', (string) file_get_contents(base_path('.github/workflows/pages.yml')), $achado);

    $base         = $achado[1] ?? '';
    $repositorio  = basename((string) $composer['homepage']);
    $nomeDoPacote = explode('/', (string) $composer['name'])[1];

    expect($base)->toBe("/{$repositorio}")
        ->and($base)->toMatch('~^/[^/]+$~')
        ->and($base)->not->toBe("/{$nomeDoPacote}");
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
it('[CT-17] cada idioma explica como a documentação é publicada', function (string $idioma, string $previa): void {
    $texto = paginasDoSite($idioma)['operacao/desenvolvendo-o-kit.md'] ?? '';

    /*
     * Sem mensagem no `toContain`: ele é VARIÁDICO, e a mensagem vira uma segunda agulha
     * procurada no texto. A primeira versão deste caso passava `'a doc precisa nomear o fluxo'`
     * como segundo argumento e reprovava afirmando que a doc não contém essa frase — que ela
     * realmente não contém, e nem deve. A armadilha já está documentada em `[CT-29]` e `[CT-34]`
     * deste repositório; a mensagem vai no `toMatch`, que aceita, ou em `expect()` separado.
     */
    expect($texto)->toContain('`main`')
        ->and($texto)->toContain('pages.yml')
        ->and($texto)->toContain('Source: GitHub Actions')
        ->and($texto)->toMatch($previa);
})->with([
    'português' => ['pt', '/prévia local/i'],
    'inglês'    => ['en', '/local preview/i'],
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
/**
 * CT-20 — toda página aparece na navegação do idioma a que pertence.
 *
 * O CENÁRIO É O MESMO; o mecanismo mudou. No just-the-docs a árvore saía do front-matter de cada
 * página (`parent`, `grand_parent`, `has_children`) e este caso conferia esses campos. No
 * Starlight a barra lateral é DECLARADA no `astro.config.mjs`, a partir de `site/sidebar.json`
 * que o `converter.mjs` gera — porque o `autogenerate` não funciona com o conteúdo fora da raiz
 * do projeto Astro (ADR-02 da wiki `site-starlight`).
 *
 * A troca de mecanismo criou um modo de falha que o anterior não tinha: uma página nova que não
 * entre na lista **simplesmente não aparece na navegação**, e nada mais no repositório reclama.
 * Ela continua existindo, continua sendo servida por URL direta, e some para quem navega. É
 * exatamente o que este caso passa a cobrir.
 *
 * Os `slug` do sidebar não levam prefixo de idioma — o Starlight os localiza para cada locale —,
 * então a mesma lista atende os dois, e conferi-la contra as duas árvores também afirma a
 * paridade de caminho que o i18n do Starlight exige.
 */
it('[CT-20] toda página aparece na navegação do idioma a que pertence', function (string $idioma): void {
    $sidebar = json_decode((string) file_get_contents(base_path('site/sidebar.json')), true);

    $slugs = [];

    foreach ($sidebar as $grupo) {
        foreach ($grupo['items'] ?? [] as $item) {
            $slugs[] = $item['slug'];
        }
    }

    $paginas = paginasDoSite($idioma);
    $orfas   = [];
    $folhas  = 0;

    foreach (array_keys($paginas) as $caminho) {
        // A landing do idioma é a raiz: ela não entra na barra lateral, e não é órfã por isso.
        if ($caminho === 'index.md') {
            continue;
        }

        $slug = preg_replace('~(/index)?\.md$~', '', $caminho);

        if (! str_ends_with($caminho, 'index.md')) {
            $folhas++;
        }

        if (! in_array($slug, $slugs, true)) {
            $orfas[] = $caminho;
        }
    }

    expect($slugs)->not->toBeEmpty('o sidebar gerado esta vazio — o converter nao rodou')
        ->and($folhas)->toBeGreaterThanOrEqual(22)
        ->and($orfas)->toBe([], "paginas fora da navegacao ({$idioma})");
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

it('mantem os numeros objetivos dos readmes sincronizados com a arvore', function (): void {
    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);
    $pacotes  = static fn (string $grupo): int => count(array_filter(
        array_keys($composer[$grupo]),
        static fn (int|string $nome): bool => is_string($nome) && str_contains($nome, '/'),
    ));
    $contar = static fn (string $diretorio, string $padrao): int => Finder::create()
        ->files()
        ->in(base_path($diretorio))
        ->name($padrao)
        ->count();
    $comandos       = substr_count((string) shell_exec('php artisan list --raw'), "\nkit:");
    $especificacoes = Finder::create()->files()->in(base_path('wikis/specs'))->name('00-requisito.md')->count();
    $rules          = Finder::create()->files()->in(base_path('.ai/rules'))->depth('== 0')->name('*.md')->notName('index.md')->count();

    foreach (['README.md', 'README.en.md'] as $arquivo) {
        $readme = (string) file_get_contents(base_path($arquivo));

        expect($readme)
            ->toContain('| '.($arquivo === 'README.md' ? 'Pacotes de produção' : 'Production packages')." | **{$pacotes('require')}** |")
            ->toContain('| '.($arquivo === 'README.md' ? 'Pacotes de desenvolvimento' : 'Development packages')." | **{$pacotes('require-dev')}** |")
            ->toContain("| Migrations | **{$contar('database/migrations', '*.php')}** |")
            ->toContain("| Policies | **{$contar('app/Policies', '*.php')}** |")
            ->toContain('| '.($arquivo === 'README.md' ? 'Comandos `kit:*`' : '`kit:*` commands')." | **{$comandos}** |")
            ->toContain("**{$especificacoes}**")
            ->toContain('| '.($arquivo === 'README.md' ? 'Features especificadas (`wikis/specs/`)' : 'Specified features (`wikis/specs/`)')." | **{$especificacoes}** |")
            ->toContain('| '.($arquivo === 'README.md' ? 'Project rules para agentes de IA (`.ai/rules/`, sem o índice)' : 'Project rules for AI agents (`.ai/rules/`, excluding the index)')." | **{$rules}** |");
    }
});

/**
 * A tabela por painel de "Nossos números" — as cinco linhas que envelheciam em silêncio.
 *
 * O caso acima trava sete linhas (pacotes, migrations, policies, comandos, specs, rules) e **elas
 * estão certas justamente porque ele existe**. A tabela dos painéis não tinha guarda nenhuma, e a
 * auditoria de 2026-09-19 achou **três das cinco linhas erradas**:
 *
 * | linha | declarado | real |
 * |---|---|---|
 * | Telas navegáveis | 12 / 28 / 27 | 14 / 31 / 28 |
 * | Páginas próprias | 4 / 4 / 12 | 5 / 5 / 13 |
 * | Rotas `GET` | 21 / 35 / 33 | 23 / 38 / 34 |
 *
 * A defasagem maior não foi a `ViewUser` da v0.36.0 (que bate em dois painéis): foi o
 * `DashboardClassico`, registrado nos **três**. E a linha *Telas navegáveis* estava parada desde
 * 18/08/2026 — ela atravessou um fact-check inteiro sem ser corrigida porque **não tinha critério
 * declarado**, e o que não é falsificável ninguém confere. O critério agora está escrito no próprio
 * README, ao lado da tabela.
 *
 * ## Por que contar pelo Filament e nunca por `ls`
 *
 * `ls app/Filament/**\/Widgets/*.php` dá **30**; `$painel->getWidgets()` dá **29**. A diferença são
 * os seis widgets de `Tenants/Widgets/`, que são widgets de **resource** e não de painel. Contar
 * arquivo mede o diretório; contar pelo painel mede o que o usuário alcança — e é isso que a tabela
 * promete.
 */
it('[CT-25] mantem a tabela por painel dos readmes sincronizada com os paineis registrados', function (): void {
    $rotasGet = collect(app('router')->getRoutes())
        ->filter(fn (Route $rota): bool => in_array('GET', $rota->methods(), true));

    $doPainel = static fn (Collection $rotas, string $caminho): Collection => $rotas->filter(
        static fn (Route $rota): bool => $rota->uri() === $caminho || str_starts_with($rota->uri(), $caminho.'/'),
    );

    /*
     * O critério de "tela navegável", e ele é o que está escrito no README: rota GET do painel COM
     * NOME, descontadas autenticação, endpoint que devolve JSON e redirect. Sem nome é redirect;
     * `.auth.` é login/registro/senha; `passkeys/` devolve JSON; `screen/lock` é overlay.
     */
    $ehTela = static function (Route $rota): bool {
        $nome = $rota->getName();

        return $nome !== null
            && ! str_contains($nome, '.auth.')
            && ! str_contains($rota->uri(), 'passkeys/')
            && ! str_contains($rota->uri(), 'screen/lock');
    };

    $linhas = ['telas' => [], 'resources' => [], 'paginas' => [], 'widgets' => [], 'rotas' => []];

    /*
     * O `/infra` tem UMA rota a mais numa instalacao de verdade do que nesta suite, e a diferenca
     * nao e defeito: `infra/queue-monitors/pending` so e registrada quando
     * `config('queue.default') === 'database'`
     * (`vendor/croustibat/filament-jobs-monitor/src/Models/QueueJob.php:59-64`, chamado em
     * `.../Resources/QueueMonitorResource.php:386`), e o `phpunit.xml:142` fixa
     * `QUEUE_CONNECTION=sync` de proposito.
     *
     * O `.env.example:52` entrega `QUEUE_CONNECTION=database`, e o README descreve o kit COMO
     * INSTALADO — entao o numero certo la e o que inclui a pagina de pendentes. Medido: 28 telas e
     * 34 rotas no /infra com `artisan`, 27 e 33 aqui.
     *
     * Sem este ajuste o teste exigiria do README o numero do AMBIENTE DE TESTE, que ninguem ve. O
     * kit ja documenta a mesma pegadinha em `tests/Pest.php:telasDoKit()`.
     */
    $pendentesForaDaSuite = config('queue.default') !== 'database' ? 1 : 0;

    foreach (['app', 'admin', 'infra'] as $id) {
        $painel       = Filament::getPanel($id);
        $doPainelDele = $doPainel($rotasGet, trim($painel->getPath(), '/'));
        $ajuste       = $id === 'infra' ? $pendentesForaDaSuite : 0;

        $linhas['telas'][]     = $doPainelDele->filter($ehTela)->count() + $ajuste;
        $linhas['resources'][] = count($painel->getResources());
        $linhas['paginas'][]   = count($painel->getPages());
        $linhas['widgets'][]   = count($painel->getWidgets());
        $linhas['rotas'][]     = $doPainelDele->count() + $ajuste;
    }

    // Piso de não-vacuidade: painel que não resolve devolve zero em tudo, e a tabela "bateria".
    expect(array_sum($linhas['resources']))->toBeGreaterThan(10, 'os paineis nao resolveram — a comparacao seria vacua');

    $linhaDaTabela = static fn (string $rotulo, array $valores): string => sprintf(
        '| %s | %d | %d | %d | **%d** |',
        $rotulo,
        $valores[0],
        $valores[1],
        $valores[2],
        array_sum($valores),
    );

    $rotulos = [
        'README.md' => [
            'telas'     => '**Telas navegáveis**',
            'resources' => 'Resources',
            'paginas'   => 'Páginas próprias',
            'widgets'   => 'Widgets',
            'rotas'     => 'Rotas `GET`',
        ],
        'README.en.md' => [
            'telas'     => '**Navigable screens**',
            'resources' => 'Resources',
            'paginas'   => 'Standalone pages',
            'widgets'   => 'Widgets',
            'rotas'     => '`GET` routes',
        ],
    ];

    foreach ($rotulos as $arquivo => $mapa) {
        $readme = (string) file_get_contents(base_path($arquivo));

        foreach ($mapa as $chave => $rotulo) {
            expect($readme)->toContain($linhaDaTabela($rotulo, $linhas[$chave]));
        }
    }
})->skip(fn (): bool => ! naArvoreDoKit(), 'O kit:update não entrega o README, que passa a ser do projeto.')->group('kit');

/**
 * A contagem de arquivos de teste dos readmes.
 *
 * Linha da tabela "Qualidade", e a única dela que sai de um `find` — as outras (casos, asserções,
 * telas varridas) exigem rodar a suíte ou não têm definição operacional, e estão declaradas com
 * data e ressalva. Esta não tem desculpa para envelhecer, e envelheceu: `126`/`149` viraram
 * `143`/`169` sem ninguém notar.
 */
it('[CT-25] mantem a contagem de arquivos de teste dos readmes sincronizada', function (): void {
    $contar = static fn (string $diretorio): int => Finder::create()
        ->files()
        ->in(base_path($diretorio))
        ->depth('== 0')
        ->name('*Test.php')
        ->count();

    $fundacao = $contar('tests/Kit') + $contar('tests/Tenancy');
    $total    = Finder::create()->files()->in(base_path('tests'))->name('*Test.php')->count();

    expect($fundacao)->toBeGreaterThan(50, 'a varredura de testes olhou o lugar errado')
        ->and($total)->toBeGreaterThanOrEqual($fundacao);

    expect((string) file_get_contents(base_path('README.md')))
        ->toContain("| Arquivos de teste | **{$fundacao}** em `Kit` + `Tenancy` (**{$total}** no total) |");

    expect((string) file_get_contents(base_path('README.en.md')))
        ->toContain("| Test files | **{$fundacao}** in `Kit` + `Tenancy` (**{$total}** in total) |");
})->skip(fn (): bool => ! naArvoreDoKit(), 'O kit:update não entrega o README, que passa a ser do projeto.')->group('kit');

/*
|--------------------------------------------------------------------------
| Migração para o Astro Starlight — wikis/specs/feat/site-starlight/
|--------------------------------------------------------------------------
|
| Os cenários abaixo nasceram com a troca de gerador. Os de ID menor, acima,
| continuam valendo: o conteúdo é o mesmo, e é isso que a ADR-01 daquela wiki
| promete. Cinco deles tiveram a AFIRMAÇÃO invertida junto com o mecanismo
| (CT-15, CT-16, CT-17, CT-20, CT-25), e cada um diz no próprio docblock o que
| mudou e por quê.
*/

/**
 * CT-26 — a configuração declara o Starlight e os dois locales.
 *
 * O `lang` de cada locale não é enfeite: é ele que o Pagefind usa para separar o índice de busca
 * por idioma, que é uma das duas coisas que motivaram a troca de gerador. `pt` em vez de `pt-BR`
 * indexaria com o idioma errado sem nada quebrar na tela.
 */
it('[CT-26] a configuracao declara o Starlight e os dois locales', function (): void {
    $config = (string) file_get_contents(base_path('site/astro.config.mjs'));

    expect($config)->toContain('@astrojs/starlight')
        ->and($config)->toMatch("~pt:\s*\{[^}]*lang:\s*'pt-BR'~")
        ->and($config)->toMatch("~en:\s*\{[^}]*lang:\s*'en'~")
        ->and($config)->toMatch("~defaultLocale:\s*'pt'~");
});

/**
 * CT-28 — o Starlight lê de `docs/`, e não há uma segunda árvore de conteúdo.
 *
 * A segunda metade é a que importa no dia a dia: a cópia do spike dentro do projeto Astro ficaria
 * funcionando e divergiria em silêncio, e o leitor veria a versão velha sem ninguém saber por quê.
 * Duas fontes da verdade não dão erro — dão duas verdades.
 */
it('[CT-28] o conteudo publicado e o de docs, e so ele', function (): void {
    $colecoes = (string) file_get_contents(base_path('site/src/content.config.ts'));

    expect($colecoes)->toContain("base: '../docs'")
        ->and(is_dir(base_path('site/src/content/docs')))->toBeFalse();
});

/**
 * CT-29 — nenhuma página carrega front-matter do gerador antigo, e toda uma tem `description`.
 *
 * O `description` merece explicação: o Jekyll não tinha nenhuma, e sem ela o Starlight preenche o
 * cartão de compartilhamento e o resultado de busca com o que o gerador escolher. A conversão a
 * deriva do primeiro parágrafo de PROSA — e "prosa" precisou excluir a imagem com link
 * (`[![alt](thumb)](full)`), que abre várias páginas deste site: sem isso, 20 descrições nasciam
 * com o texto alternativo de um print.
 *
 * Por isso o caso não se contenta com "a chave existe": ele exige que ela não seja igual ao
 * título e não comece por marcação de imagem.
 */
it('[CT-29] toda pagina esta no formato do Starlight', function (): void {
    $doGeradorAntigo = $semDescricao = $descricaoRuim = [];
    $conferidas      = 0;

    foreach (['pt', 'en'] as $idioma) {
        foreach (paginasDoSite($idioma) as $caminho => $conteudo) {
            $conferidas++;
            $campos = frontMatterDe($conteudo);

            foreach (['parent', 'grand_parent', 'has_children', 'nav_order'] as $chave) {
                if (isset($campos[$chave])) {
                    $doGeradorAntigo[] = "{$idioma}/{$caminho} ({$chave})";
                }
            }

            $descricao = $campos['description'] ?? null;

            if ($descricao === null || $descricao === '') {
                $semDescricao[] = "{$idioma}/{$caminho}";

                continue;
            }

            if ($descricao === ($campos['title'] ?? null) || str_starts_with($descricao, '![')) {
                $descricaoRuim[] = "{$idioma}/{$caminho}";
            }
        }
    }

    expect($conferidas)->toBeGreaterThan(60, 'a varredura olhou o lugar errado')
        ->and($doGeradorAntigo)->toBe([])
        ->and($semDescricao)->toBe([])
        ->and($descricaoRuim)->toBe([]);
});

/**
 * CT-30 — nenhuma página repete o título no corpo.
 *
 * O Starlight renderiza o `title` do front-matter como H1. Um H1 no corpo produz o título duas
 * vezes na tela e um segundo H1 no documento, que é defeito de acessibilidade, não de estética.
 *
 * **O que este caso NÃO prova** está declarado na wiki: ele afirma que nenhum corpo COMEÇA com
 * H1; não afirma que nenhum H1 do meio do texto foi apagado por engano. Quem cobre isso é o
 * `[CT-01]`, contra o baseline congelado de 115 títulos — e foi ele que pegou, na implementação,
 * que o H1 e o `title` antigo eram textos DIFERENTES em várias páginas.
 */
it('[CT-30] nenhuma pagina repete o titulo no corpo', function (): void {
    $comH1      = [];
    $conferidas = 0;

    foreach (['pt', 'en'] as $idioma) {
        foreach (paginasDoSite($idioma) as $caminho => $conteudo) {
            $conferidas++;
            $corpo = (string) preg_replace('/\A---\n.*?\n---\n/s', '', $conteudo);

            if (preg_match('/\A\s*#\s+/', $corpo) === 1) {
                $comH1[] = "{$idioma}/{$caminho}";
            }
        }
    }

    expect($conferidas)->toBeGreaterThan(60)
        ->and($comH1)->toBe([]);
});

/**
 * CT-32 e CT-33 — a identidade visual existe nos dois esquemas de cor.
 *
 * Os dois nasceram de um defeito MEDIDO em navegador, não de uma preocupação abstrata: a primeira
 * versão do tema redefinia `--sl-color-gray-6/7` no seletor global para "esquentar" o tema
 * escuro, e o tema CLARO usa essas mesmas variáveis como superfície clara. O chip de código
 * inline virou bloco quase preto com texto escuro por cima, ilegível em toda página — e no escuro
 * estava impecável. Foi a captura nos dois temas que pegou.
 *
 * CT-33 é a asserção de AUSÊNCIA correspondente, e ela só vale sobre o bloco global: redefinir um
 * cinza dentro de um bloco por tema é legítimo.
 */
it('[CT-32] a rampa de acento existe no escuro e no claro', function (): void {
    $css = (string) file_get_contents(base_path('site/src/styles/kit.css'));

    expect((string) file_get_contents(base_path('site/astro.config.mjs')))->toContain('kit.css');

    expect($css)->toMatch('~:root\s*\{[^}]*--sl-color-accent~s')
        ->and($css)->toMatch("~:root\[data-theme='light'\]\s*\{[^}]*--sl-color-accent~s");
});

it('[CT-33] nenhuma variavel de cinza do tema e redefinida no seletor global', function (): void {
    $css = (string) file_get_contents(base_path('site/src/styles/kit.css'));

    preg_match('~:root\s*\{(.*?)\}~s', $css, $global);

    expect($global[1] ?? '')->not->toBe('', 'o bloco global sumiu — o caso mediria o vazio')
        ->and($global[1])->not->toMatch('~--sl-color-gray-~');
});

/**
 * CT-34 — as duas árvores de idioma são espelho por CAMINHO.
 *
 * Não é preciosismo de organização: **caminho idêntico é o contrato do i18n do Starlight**. A
 * tradução de `pt/recursos/x.md` é `en/recursos/x.md`, e só ela. Renomear o slug de um lado faz o
 * Starlight gerar, no caminho órfão, uma página com `lang="en"` e o CORPO EM PORTUGUÊS — o
 * fallback de tradução ausente —, e o seletor de idioma continua apontando para ela.
 *
 * Medido renomeando uma página de verdade durante o planejamento. É a razão de os slugs em inglês
 * continuarem em português (ADR-04), e este caso é o que torna essa não-ação falsificável.
 */
it('[CT-34] as duas arvores de idioma sao espelho por caminho', function (): void {
    $pt = array_keys(paginasDoSite('pt'));
    $en = array_keys(paginasDoSite('en'));

    sort($pt);
    sort($en);

    expect(count($pt))->toBeGreaterThan(30, 'a varredura olhou o lugar errado')
        ->and(array_values(array_diff($pt, $en)))->toBe([], 'existe em pt e falta em en')
        ->and(array_values(array_diff($en, $pt)))->toBe([], 'existe em en e falta em pt');
});

/**
 * CT-36, CT-37 e CT-38 — os redirecionamentos das URLs que o gerador antigo publicava.
 *
 * ## O destino é relativo, e isso é a correção de um defeito de produção
 *
 * A primeira versão gravava o caminho absoluto (`/pt/comecar/x/`). Os stubs são **commitados**, e
 * quem os gera localmente não passa o `base` — então os 54 entraram no repositório apontando para
 * a raiz do domínio. O site publica em `/filament-starter-kit-easy/`: **todos dariam 404 no ar**,
 * e nada no repositório ficaria vermelho. Um destino relativo resolve contra o diretório do
 * próprio stub e acerta sob qualquer base.
 *
 * CT-37 afirma a AUSÊNCIA para índice de seção — `/pt/comecar/` tinha a mesma forma nos dois
 * geradores, e redirect de rota que não mudou é ruído numa lista de 54.
 *
 * CT-38 é o piso: `CT-36` fica verde sobre zero stubs, e sem contagem a regra inteira viraria
 * vácuo. A contagem sai da árvore, nunca digitada.
 */
it('[CT-36] todo redirecionamento aponta para uma pagina que existe, sem depender do base', function (): void {
    $ruins  = [];
    $vistos = 0;

    foreach (Finder::create()->files()->in(base_path('site/public'))->name('*.html') as $stub) {
        $vistos++;
        $conteudo = $stub->getContents();
        $relativo = str_replace('\\', '/', $stub->getRelativePathname());

        if (preg_match('~content="0; url=([^"]+)"~', $conteudo, $alvo) !== 1) {
            $ruins[] = "{$relativo} — sem meta refresh imediato";

            continue;
        }

        if (str_starts_with($alvo[1], '/')) {
            $ruins[] = "{$relativo} — destino absoluto, quebra sob base";

            continue;
        }

        $pagina = base_path('docs/'.dirname($relativo).'/'.rtrim($alvo[1], '/').'.md');

        if (! is_file($pagina)) {
            $ruins[] = "{$relativo} — destino {$alvo[1]} nao existe no conteudo";
        }
    }

    expect($vistos)->toBeGreaterThan(40, 'a varredura de stubs nao achou nada')
        ->and($ruins)->toBe([]);
});

it('[CT-37] rota que nao mudou de forma nao ganha redirecionamento', function (): void {
    $indevidos = [];

    foreach (['pt', 'en'] as $idioma) {
        foreach (array_keys(paginasDoSite($idioma)) as $caminho) {
            if (! str_ends_with($caminho, 'index.md')) {
                continue;
            }

            $stub = base_path("site/public/{$idioma}/".str_replace('/index.md', '.html', $caminho));

            if (is_file($stub)) {
                $indevidos[] = "{$idioma}/{$caminho}";
            }
        }
    }

    expect($indevidos)->toBe([]);
});

it('[CT-38] toda pagina de folha tem o seu redirecionamento', function (): void {
    $folhas  = 0;
    $semStub = [];

    foreach (['pt', 'en'] as $idioma) {
        foreach (array_keys(paginasDoSite($idioma)) as $caminho) {
            if (str_ends_with($caminho, 'index.md')) {
                continue;
            }

            $folhas++;
            $stub = base_path("site/public/{$idioma}/".str_replace('.md', '.html', $caminho));

            if (! is_file($stub)) {
                $semStub[] = "{$idioma}/{$caminho}";
            }
        }
    }

    $stubs = Finder::create()->files()->in(base_path('site/public'))->name('*.html')->count();

    expect($folhas)->toBeGreaterThan(40, 'nao ha folhas — a varredura olhou o lugar errado')
        ->and($semStub)->toBe([])
        ->and($stubs)->toBe($folhas, 'ha stub sobrando ou faltando');
});

/**
 * CT-39 — o plano B continua sendo um plano B, e não um parágrafo.
 *
 * O solicitante pediu o VitePress "como 2 opção caso algo aconteça". Alternativa em prosa nunca
 * foi executada, e quem tentar usá-la vai descobrir os problemas sob pressão — que é o pior
 * momento. Por isso ela fica no repositório construindo, e por isso o README dela declara **quando
 * trocar**, não só como instalar.
 */
it('[CT-39] o plano B esta completo e declara quando trocar', function (): void {
    $readme = (string) file_get_contents(base_path('site-vitepress/README.md'));

    expect(is_file(base_path('site-vitepress/package.json')))->toBeTrue()
        ->and(is_file(base_path('site-vitepress/.vitepress/config.mts')))->toBeTrue()
        ->and(is_file(base_path('site-vitepress/converter.mjs')))->toBeTrue()
        ->and($readme)->toContain('Quando trocar');
});

/**
 * CT-40 — o conferidor de links roda ANTES de o site ir ao ar.
 *
 * Este é o cenário que fecha o buraco da divisão de camadas. Os guardas de link, de âncora e de
 * redirect moram no Node, fora da suíte do Pest, porque exigem o site construído — e um passo de
 * workflow some com uma linha apagada, sem nada ficar vermelho.
 *
 * A ordem é parte da afirmação: conferir depois do envio deixa o site quebrado no ar mesmo com o
 * job vermelho. E `continue-on-error` num passo de guarda é a forma silenciosa de desligá-lo sem
 * removê-lo.
 */
it('[CT-40] o fluxo de publicacao confere os links antes de enviar o artefato', function (): void {
    $fluxo = (string) file_get_contents(base_path('.github/workflows/pages.yml'));

    $conferidor = strpos($fluxo, 'verifica-links.mjs');
    $envio      = strpos($fluxo, 'upload-pages-artifact');

    expect($conferidor)->not->toBeFalse('o fluxo nao confere os links')
        ->and($envio)->not->toBeFalse('o fluxo nao envia artefato')
        ->and($conferidor)->toBeLessThan($envio, 'o conferidor roda depois do envio')
        ->and($fluxo)->not->toContain('continue-on-error');
});

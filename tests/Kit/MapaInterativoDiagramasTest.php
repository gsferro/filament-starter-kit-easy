<?php

/**
 * Guardas dos diagramas e da página do mapa interativo — costura G2, metade dos diagramas.
 *
 * Fonte: `wikis/specs/feat/mapa-interativo-do-kit/mapa-interativo-do-kit/04-casos-de-teste.md`
 * (Setup Global e as regras R6, R12, R15 a R20, R22, R29, da R34 só o CT-37, a R38, do step 9, e, dos Adendos 2
 * a 4, a R41, a R42 e a R44). Escrito a
 * partir do Gherkin do `04`, nunca do plano: `site/mapa/`, `docs/` e `app/` só foram lidos para achar NOMES
 * (arquivo, campo, classe, função exportada), nunca para decidir o que é esperado.
 *
 * Lote: CT-06, CT-14, CT-15, CT-16, CT-17, CT-18, CT-19, CT-20, CT-21, CT-22, CT-24, CT-31, CT-36,
 * CT-37 e CT-43. O CT-44 é da suíte Tenancy e o CT-38 também: ficam fora deste arquivo.
 * Do step 9 (revisão do diff): CT-46 e CT-47 (R38), pela costura G5 na forma de leitura — um `node` filho
 * importa `site/mapa/gerar.mjs` e devolve a entrada que o gerador entrega ao archify, sem baixar nem renderizar.
 * Dos Adendos 2 a 4: CT-33 (R41), pelo discriminante de `tests/Pest.php` na metade sem tenant e pela condição do
 * modelo na com; CT-57 (R42 e R44), pela forma de leitura da G5; e a definição nova de "entrar no diagrama de um
 * modo" — registrada nele e disponível nele, com a chave ligada — no desenho que os CT-14, CT-19, CT-46 e CT-47
 * contam como o construtor, e na subtração viva do CT-37.
 *
 * Toda guarda é uma função aplicada ao modelo publicado e a cópias adulteradas EM MEMÓRIA, nunca
 * ao arquivo real: a linha "publicados" e as linhas de controle chamam a MESMA função.
 *
 * Helpers: os usados só por este arquivo ficam aqui, com o prefixo `mapaDiagramas`. Os que as outras guardas
 * do mapa também usam (o modelo, os diagramas, o catálogo, o registro vivo, o arquivo da classe, o motivo de
 * enfeite, o `node` do ambiente e a entrada que o gerador dá ao archify) moram em `tests/Pest.php`
 * (`.ai/rules/testes.md`).
 */

use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function (): void {
    if (! naArvoreDoKit()) {
        test()->markTestSkipped('As guardas do mapa leem site/, o diretório das páginas do site e o CHANGELOG, que não viajam no projeto instalado (.gitattributes: export-ignore).');
    }
});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/** @return list<string> os tipos de visualização, na ordem das linhas da matriz tipo × painel × modo */
function mapaDiagramasTiposDaMatriz(): array
{
    return ['tree', 'architecture', 'sequence', 'workflow', 'lifecycle', 'dataflow', 'erd', 'class', 'timeline', 'waterfall', 'compare'];
}

/** @return list<string> as seis colunas da matriz, na ordem do cabeçalho */
function mapaDiagramasColunas(): array
{
    return ['admin|sem', 'admin|com', 'app|sem', 'app|com', 'infra|sem', 'infra|com'];
}

function mapaDiagramasPt(mixed $valor): string
{
    if (is_array($valor)) {
        return (string) ($valor['pt'] ?? '');
    }

    return is_string($valor) ? $valor : '';
}

function mapaDiagramasEn(mixed $valor): string
{
    if (is_array($valor)) {
        return (string) ($valor['en'] ?? '');
    }

    return is_string($valor) ? $valor : '';
}

function mapaDiagramasNomeDaPeca(array $modelo, string $id): string
{
    return (string) ($modelo['pecas'][$id]['nome'] ?? $id);
}

/** @return array<string, list<string>> nome curto => ids */
function mapaDiagramasIdsPorNome(array $modelo): array
{
    $saida = [];
    foreach ($modelo['pecas'] as $id => $peca) {
        $saida[$peca['nome']][] = $id;
    }

    return $saida;
}

/**
 * @param  list<string>  $pecas
 * @param  list<string>  $membros
 * @param  list<string>  $nomeados
 * @return array<string, mixed>
 */
function mapaDiagramasNo(string $tipo, string $rotulo, array $pecas = [], array $membros = [], array $nomeados = []): array
{
    return ['tipo' => $tipo, 'rotulo' => $rotulo, 'pecas' => $pecas, 'membros' => $membros, 'nomeados' => $nomeados, 'texto' => ''];
}

/**
 * O desenho de um diagrama: nós (peça, agrupamento que nomeia os membros, porta) e arestas entre nós.
 * O mapa de arquitetura e a árvore de registro saem do modelo (o arquivo diz só o recorte); os demais
 * tipos são desenhados à mão, e a peça de um nó é a que ele nomeia, pelo id ou pelo nome exato.
 * No construído, a peça entra no diagrama de um modo só quando o painel a registra nele e a condição do modelo a
 * deixa disponível nele (Setup Global do `04`, "Entrar no diagrama de um modo"): o compartilhado não desenha a peça
 * que a condição prende a um modo (`pecaAbreNosModosDoDiagrama()`).
 *
 * @return array<string, mixed>
 */
function mapaDiagramasConstruir(array $decl, array $modelo): array
{
    $pecas = $modelo['pecas'];
    $ir    = [
        'slug'       => $decl['slug'],
        'tipo'       => $decl['tipo'],
        'construtor' => $decl['construtor'] ?? null,
        'paineis'    => $decl['paineis'] ?? [],
        'modos'      => $decl['modos'] ?? [],
        'nos'        => [],
        'arestas'    => [],
        // As ligações que o construtor transformou em aresta, já no painel e no modo do diagrama (R12, CT-B02).
        'representadas' => [],
        // O que o diagrama escrito à mão desenha, inteiro: a tabela do ERD não é peça, e só ela distingue um modo do outro.
        'estrutura' => null,
    ];

    if ($ir['construtor'] === 'registro') {
        $raiz             = 'painel:'.$ir['paineis'][0];
        $ir['nos'][$raiz] = mapaDiagramasNo('peca', mapaDiagramasNomeDaPeca($modelo, $raiz), [$raiz]);
        foreach ($modelo['ligacoes'] as $ligacao) {
            // A ligação vale em cada modo do diagrama (Setup Global do `04`, "O diagrama que vale nos dois modos e a ligação de um modo só").
            if ($ligacao['de'] === $raiz && $ligacao['verbo'] === 'registra' && array_diff($ir['modos'], $ligacao['modos']) === []
                && pecaAbreNosModosDoDiagrama($modelo, $ligacao['para'], $ir['modos'])) {
                $ir['nos'][$ligacao['para']] = mapaDiagramasNo('peca', mapaDiagramasNomeDaPeca($modelo, $ligacao['para']), [$ligacao['para']]);
                $ir['arestas'][]             = [$raiz, $ligacao['para']];
                $ir['representadas'][]       = $ligacao['de'].'|'.$ligacao['para'].'|'.$ligacao['verbo'];
            }
        }

        return $ir;
    }

    if ($ir['construtor'] === 'mapa') {
        $existe     = static fn (string $id): bool => pecaAbreNosModosDoDiagrama($modelo, $id, $ir['modos']);
        $desenhadas = [];
        foreach ($decl['pecas'] ?? [] as $peca) {
            if ($existe($peca['id'])) {
                $desenhadas[$peca['id']] = true;
                $ir['nos'][$peca['id']]  = mapaDiagramasNo('peca', mapaDiagramasNomeDaPeca($modelo, $peca['id']), [$peca['id']]);
            }
        }
        $grupoDe = [];
        foreach ($decl['agrupamentos'] ?? [] as $grupo) {
            $membros = array_values(array_filter($grupo['membros'], $existe));
            // O card "Quem está em …" nomeia os membros: é o que faz do agrupamento um desenho da ligação.
            $ir['nos'][$grupo['id']] = mapaDiagramasNo('grupo', mapaDiagramasPt($grupo['rotulo']), [], $membros, $membros);
            foreach ($membros as $membro) {
                $grupoDe[$membro] = $grupo['id'];
            }
        }
        foreach ($decl['portas'] ?? [] as $porta) {
            $ir['nos']['porta-'.$porta['para']] = mapaDiagramasNo('porta', mapaDiagramasPt($porta['rotulo']));
        }
        $ponta  = static fn (string $id): ?string => isset($desenhadas[$id]) ? $id : ($grupoDe[$id] ?? null);
        $vistas = [];
        foreach ($modelo['ligacoes'] as $ligacao) {
            // A ligação vale em cada modo do diagrama: a de um modo só não entra no mapa compartilhado.
            if (array_diff($ir['modos'], $ligacao['modos']) !== []) {
                continue;
            }
            $doEscopo = $ir['paineis'] !== []
                ? array_intersect($ligacao['paineis'], $ir['paineis']) !== []
                : $ligacao['paineis'] === [];
            if (! $doEscopo) {
                continue;
            }
            $de   = $ponta($ligacao['de']);
            $para = $ponta($ligacao['para']);
            if ($de === null || $para === null || $de === $para) {
                continue;
            }
            $ir['representadas'][] = $ligacao['de'].'|'.$ligacao['para'].'|'.$ligacao['verbo'];
            if (isset($vistas[$de.'|'.$para])) {
                continue;
            }
            $vistas[$de.'|'.$para] = true;
            $ir['arestas'][]       = [$de, $para];
        }

        return $ir;
    }

    [$chaveDosNos, $chaveDasArestas] = mapaDiagramasColecoes()[$decl['tipo']] ?? [null, null];
    $ir['estrutura']                 = md5((string) json_encode([$decl[$chaveDosNos] ?? [], $chaveDasArestas !== null ? ($decl[$chaveDasArestas] ?? []) : []]));
    foreach ($decl[$chaveDosNos] ?? [] as $no) {
        $rotuloPt = mapaDiagramasPt($no['label'] ?? $no['name'] ?? '');
        $rotuloEn = mapaDiagramasEn($no['label'] ?? $no['name'] ?? '');
        // o nó da D43: a prova vem antes do nome, e o nome vale só sem homônima
        $ids                  = [...(isset($pecas[$no['id']]) ? [$no['id']] : []), ...pecasDoNoEscritoAMao($no, [$rotuloPt, $rotuloEn], $modelo['pecas'])];
        $ir['nos'][$no['id']] = mapaDiagramasNo('peca', $rotuloPt, array_values(array_unique($ids)));
    }
    foreach ($chaveDasArestas !== null ? ($decl[$chaveDasArestas] ?? []) : [] as $aresta) {
        if (isset($aresta['from'], $aresta['to'])) {
            $ir['arestas'][] = [$aresta['from'], $aresta['to']];
        }
    }

    return $ir;
}

/** @return list<string> as peças que o nó é ou nomeia */
function mapaDiagramasPontas(array $ir, string $no): array
{
    $dado = $ir['nos'][$no] ?? null;

    return $dado === null ? [] : array_values(array_unique([...$dado['pecas'], ...$dado['nomeados']]));
}

/** @return array<string, true> os pares "peça|peça" que as arestas do desenho cobrem */
function mapaDiagramasPares(array $ir): array
{
    $pares = [];
    foreach ($ir['arestas'] as $aresta) {
        $origens  = mapaDiagramasPontas($ir, $aresta[0]);
        $destinos = mapaDiagramasPontas($ir, $aresta[1]);
        foreach ($origens as $origem) {
            foreach ($destinos as $destino) {
                $pares[$origem.'|'.$destino] = true;
            }
        }
    }

    return $pares;
}

/** @return array<string, true> as peças que o desenho tem como nó ou como membro nomeado */
function mapaDiagramasPecasDesenhadas(array $ir): array
{
    $saida = [];
    foreach (array_keys($ir['nos']) as $no) {
        foreach (mapaDiagramasPontas($ir, $no) as $peca) {
            $saida[$peca] = true;
        }
    }

    return $saida;
}

/**
 * As ligações do modelo que um diagrama desenha ("de|para|verbo"). No construído, a aresta de agrupamento
 * representa só as ligações do painel e do modo dele, que o construtor anotou; a aresta entre duas peças que
 * nenhuma ligação anotada explica (a da cópia de controle) desenha toda ligação daquele par. No escrito à mão,
 * a aresta desenha toda ligação do par de peças que as pontas nomeiam.
 *
 * @return list<string>
 */
function mapaDiagramasLigacoesDesenhadas(array $modelo, array $ir): array
{
    $chaves     = array_fill_keys($ir['representadas'] ?? [], true);
    $explicados = [];
    foreach (array_keys($chaves) as $chave) {
        [$de, $para]               = explode('|', $chave);
        $explicados[$de.'|'.$para] = true;
    }
    $construido = ($ir['construtor'] ?? null) !== null;
    $pares      = [];
    foreach ($ir['arestas'] as [$origem, $destino]) {
        if ($construido && ((($ir['nos'][$origem]['tipo'] ?? '') !== 'peca') || (($ir['nos'][$destino]['tipo'] ?? '') !== 'peca'))) {
            continue;
        }
        foreach (mapaDiagramasPontas($ir, $origem) as $de) {
            foreach (mapaDiagramasPontas($ir, $destino) as $para) {
                if (! ($construido && isset($explicados[$de.'|'.$para]))) {
                    $pares[$de.'|'.$para] = true;
                }
            }
        }
    }
    foreach ($modelo['ligacoes'] as $ligacao) {
        if (isset($pares[$ligacao['de'].'|'.$ligacao['para']])) {
            $chaves[$ligacao['de'].'|'.$ligacao['para'].'|'.$ligacao['verbo']] = true;
        }
    }

    return array_keys($chaves);
}

/**
 * Os desenhos de todos os diagramas, a partir de um modelo, e os slugs que cada página publica.
 *
 * @return array{modelo: array, decls: array, irs: array<string, array>}
 */
function mapaDiagramasMundoCom(array $modelo): array
{
    $decls = diagramasDoMapa();
    $irs   = [];
    foreach ($decls as $slug => $decl) {
        $irs[$slug] = mapaDiagramasConstruir($decl, $modelo);
    }

    return ['modelo' => $modelo, 'decls' => $decls, 'irs' => $irs];
}

function mapaDiagramasMundo(): array
{
    static $mundo = null;

    return $mundo ??= mapaDiagramasMundoCom(modeloDoMapaPorId());
}

function mapaDiagramasPagina(string $idioma): string
{
    return (string) file_get_contents(base_path("docs/{$idioma}/referencia/mapa-interativo.md"));
}

/** @return list<string> todos os `<a href>` da página que apontam para o mapa */
function mapaDiagramasHrefsDoMapa(string $pagina): array
{
    preg_match_all('/<a\s+href="([^"]*)"/u', $pagina, $m);

    return array_values(array_filter($m[1], static fn (string $href): bool => str_contains($href, 'mapa/')));
}

/** @return list<string> os slugs que a página de um idioma publica */
function mapaDiagramasPublicados(string $idioma): array
{
    $slugs = [];
    foreach (mapaDiagramasHrefsDoMapa(mapaDiagramasPagina($idioma)) as $href) {
        if (preg_match('#^(?:\.\./)+mapa/(?:pt|en)/([a-z0-9-]+)\.html$#', $href, $m) === 1) {
            $slugs[$m[1]] = true;
        }
    }

    return array_keys($slugs);
}

/**
 * @return array{itens: list<array{slug: string, compartilhado: bool}>, motivo: string}
 */
function mapaDiagramasCelula(string $celula): array
{
    preg_match_all('#<a href="[^"]*?/([a-z0-9-]+)\.html">[^<]*</a>(\s*\((?:compartilhado|shared)\))?#u', $celula, $m, PREG_SET_ORDER);
    $itens = array_map(static fn (array $achado): array => ['slug' => $achado[1], 'compartilhado' => isset($achado[2]) && $achado[2] !== ''], $m);
    $texto = preg_replace('#<a href="[^"]*">[^<]*</a>(\s*\((?:compartilhado|shared)\))?#u', '', $celula);
    $texto = trim((string) preg_replace('#<br\s*/?>#', ' ', (string) $texto));

    return ['itens' => $itens, 'motivo' => $texto];
}

/** @return list<string> as células de uma linha de tabela Markdown */
function mapaDiagramasCelulasDaLinha(string $linha): array
{
    $linha = trim($linha);
    $linha = substr($linha, 1, -1);

    return array_map('trim', explode('|', $linha));
}

/**
 * A grade painel × modo da página.
 *
 * @return array<string, array<string, list<array{slug: string, compartilhado: bool}>>>
 */
function mapaDiagramasGrade(string $idioma): array
{
    $pagina = mapaDiagramasPagina($idioma);
    $inicio = strpos($pagina, '<!-- mapa:grade -->');
    $fim    = strpos($pagina, '<!-- /mapa:grade -->');
    $grade  = [];
    foreach (preg_split('/\R/', substr($pagina, (int) $inicio, (int) $fim - (int) $inicio)) as $linha) {
        if (preg_match('/^\|\s*\*\*\/([a-z0-9-]+)\*\*\s*\|/u', $linha, $m) !== 1) {
            continue;
        }
        $celulas      = mapaDiagramasCelulasDaLinha($linha);
        $grade[$m[1]] = [
            'sem' => mapaDiagramasCelula($celulas[1] ?? '')['itens'],
            'com' => mapaDiagramasCelula($celulas[2] ?? '')['itens'],
        ];
    }

    return $grade;
}

/**
 * A matriz tipo × painel × modo: uma linha por tipo, na ordem fixa, e seis células por linha.
 *
 * @return list<array{tipo: string, celulas: array<string, array{itens: list<array{slug: string, compartilhado: bool}>, motivo: string}>}>
 */
function mapaDiagramasMatriz(string $idioma): array
{
    $pagina  = mapaDiagramasPagina($idioma);
    $inicio  = strpos($pagina, '<!-- mapa:matriz -->');
    $fim     = strpos($pagina, '<!-- /mapa:matriz -->');
    $linhas  = [];
    $posicao = 0;
    foreach (preg_split('/\R/', substr($pagina, (int) $inicio, (int) $fim - (int) $inicio)) as $linha) {
        if (! str_starts_with($linha, '|') || str_starts_with($linha, '|---')) {
            continue;
        }
        $celulas = mapaDiagramasCelulasDaLinha($linha);
        if (! str_contains($celulas[1] ?? '', '<a href') && str_starts_with($celulas[1] ?? '', '/admin')) {
            continue; // o cabeçalho
        }
        $porColuna = [];
        foreach (mapaDiagramasColunas() as $indice => $coluna) {
            $porColuna[$coluna] = mapaDiagramasCelula($celulas[$indice + 1] ?? '');
        }
        $linhas[] = ['tipo' => mapaDiagramasTiposDaMatriz()[$posicao] ?? 'tipo-'.$posicao, 'celulas' => $porColuna];
        $posicao++;
    }

    return $linhas;
}

/** Palavra funcional do pt como palavra: não colada a letra, dígito, `_`, `.`, `/` ou `-`. */
function mapaDiagramasTemPalavraDoPt(string $texto): bool
{
    return preg_match('/(?<![\p{L}\p{N}_.\/-])(?:de|do|da|para|com|não|uma)(?![\p{L}\p{N}_.\/-])/iu', $texto) === 1;
}

function mapaDiagramasNumeroDePalavras(string $texto): int
{
    return count(preg_split('/\s+/u', trim($texto), -1, PREG_SPLIT_NO_EMPTY) ?: []);
}

/** @return array<string, string> termo pt (minúsculo) => termo en (minúsculo) */
function mapaDiagramasGlossario(): array
{
    static $glossario = null;

    return $glossario ??= (function (): array {
        $texto = (string) file_get_contents(base_path('wikis/glossario.md'));
        preg_match_all('/^\|\s*(.+?)\s*\(en:\s*([^)]+)\)\s*\|/mu', $texto, $m, PREG_SET_ORDER);
        $saida = [];
        foreach ($m as $achado) {
            $saida[mb_strtolower(trim($achado[1]))] = mb_strtolower(trim($achado[2]));
        }

        return $saida;
    })();
}

/**
 * @param  list<array{0: string, 1: ?string, 2: ?string}>  $saida
 */
function mapaDiagramasColetarTextos(mixed $valor, string $caminho, array &$saida): void
{
    if (! is_array($valor) || $valor === []) {
        return;
    }
    if (array_diff(array_keys($valor), ['pt', 'en']) === []) {
        $saida[] = [$caminho, $valor['pt'] ?? null, $valor['en'] ?? null];

        return;
    }
    foreach ($valor as $chave => $filho) {
        mapaDiagramasColetarTextos($filho, $caminho === '' ? (string) $chave : $caminho.'.'.$chave, $saida);
    }
}

/**
 * R16: os textos pt e en de cada diagrama.
 *
 * @return array{aceita: bool, motivo: string}
 */
function mapaDiagramasConferirTextos(array $decls, array $modelo, array $glossario): array
{
    $nomes  = array_flip(array_column($modelo['pecas'], 'nome'));
    $falhas = [];
    foreach ($decls as $slug => $decl) {
        $titulo      = $decl['titulo'] ?? null;
        $tituloEhPar = is_array($titulo) && isset($titulo['pt'], $titulo['en']);
        if (! $tituloEhPar) {
            $falhas[] = "{$slug}: o título não tem o texto pt e o texto en";
        } elseif (trim($titulo['pt']) === trim($titulo['en'])) {
            $nome                  = trim($titulo['pt']);
            $ehNomeDePecaOuComando = isset($nomes[$nome]) || preg_match('/^kit:[a-z][a-z:-]*$/', $nome) === 1;
            if (! ($decl['nomeDeCodigo'] ?? false)) {
                $falhas[] = "{$slug}: o título en é igual ao pt, sem a marca de nome de código ({$nome})";
            } elseif (! $ehNomeDePecaOuComando) {
                $falhas[] = "{$slug}: o título \"{$nome}\" está marcado como nome de código, mas não é nome de peça nem de comando kit:*";
            }
        }
        $textos = [];
        mapaDiagramasColetarTextos($decl, '', $textos);
        foreach ($textos as [$caminho, $pt, $en]) {
            if ($pt === null || trim($pt) === '' || $en === null || trim($en) === '') {
                $falhas[] = "{$slug}: {$caminho} não tem o texto ".($en === null || trim((string) $en) === '' ? 'en' : 'pt');

                continue;
            }
            $tituloIgualMarcado = $caminho === 'titulo' && trim($pt) === trim($en) && ($decl['nomeDeCodigo'] ?? false);
            if ($tituloIgualMarcado) {
                continue;
            }
            $termo = $glossario[mb_strtolower(trim($pt))] ?? null;
            if ($termo !== null && mb_strtolower(trim($en)) !== $termo) {
                $falhas[] = "{$slug}: {$caminho} \"{$pt}\" é termo do glossário e o en é \"{$en}\", não \"{$termo}\"";

                continue;
            }
            if (mapaDiagramasNumeroDePalavras($en) >= 2 && mapaDiagramasTemPalavraDoPt($en)) {
                $falhas[] = "{$slug}: {$caminho} o texto en \"{$en}\" traz palavra funcional do pt";
            }
        }
    }

    return $falhas === []
        ? ['aceita' => true, 'motivo' => 'aceita']
        : ['aceita' => false, 'motivo' => 'reprova: '.implode(' | ', $falhas)];
}

/* ---------- R12: ligações desenhadas ---------- */

function mapaDiagramasDescricaoDaLigacao(array $modelo, array $ligacao): string
{
    return mapaDiagramasNomeDaPeca($modelo, $ligacao['de']).' ('.$ligacao['de'].') → '
        .mapaDiagramasNomeDaPeca($modelo, $ligacao['para']).' ('.$ligacao['para'].') ['.$ligacao['verbo'].']';
}

/**
 * Toda ligação do modelo é desenhada por aresta de diagrama publicado: direta, ou por agrupamento ou
 * porta que nomeia os membros. Menção em texto não conta.
 *
 * @param  array<string, array>  $irs
 * @param  list<string>  $publicados
 * @return array{aceita: bool, motivo: string, achadas: int}
 */
function mapaDiagramasConferirLigacoes(array $modelo, array $irs, array $publicados): array
{
    $desenhadas = [];
    foreach ($irs as $slug => $ir) {
        if (in_array($slug, $publicados, true)) {
            $desenhadas += mapaDiagramasPares($ir);
        }
    }
    $falhas  = [];
    $achadas = 0;
    foreach ($modelo['ligacoes'] as $ligacao) {
        if (isset($desenhadas[$ligacao['de'].'|'.$ligacao['para']])) {
            $achadas++;

            continue;
        }
        $dica = '';
        foreach ($irs as $slug => $ir) {
            if (! in_array($slug, $publicados, true)) {
                continue;
            }
            foreach ($ir['nos'] as $id => $no) {
                foreach ([$ligacao['de'], $ligacao['para']] as $ponta) {
                    if ($no['tipo'] === 'grupo' && in_array($ponta, $no['membros'], true) && ! in_array($ponta, $no['nomeados'], true)) {
                        $dica = " — o agrupamento {$id} do diagrama {$slug} não nomeia ".mapaDiagramasNomeDaPeca($modelo, $ponta);
                    }
                }
            }
        }
        $falhas[] = 'ligação não desenhada: '.mapaDiagramasDescricaoDaLigacao($modelo, $ligacao).$dica;
    }

    return [
        'aceita' => $falhas === [],
        // O motivo traz todas: cortado, ele escondia a ligação que a linha de controle tem de ver nomeada.
        'motivo'  => $falhas === [] ? 'aceita' : 'reprova, '.count($falhas).' ligação(ões): '.implode(' | ', $falhas),
        'achadas' => $achadas,
    ];
}

/** Copia os desenhos sem as arestas que cobrem o par (de, para). */
function mapaDiagramasSemDesenharAPar(array $irs, string $de, string $para): array
{
    foreach ($irs as $slug => $ir) {
        $ir['arestas'] = array_values(array_filter(
            $ir['arestas'],
            static fn (array $aresta): bool => ! (in_array($de, mapaDiagramasPontas($ir, $aresta[0]), true) && in_array($para, mapaDiagramasPontas($ir, $aresta[1]), true)),
        ));
        $irs[$slug] = $ir;
    }

    return $irs;
}

/** O índice da ligação "origem registra destino" no modelo. */
function mapaDiagramasLigacaoDe(array $modelo, string $de, string $para, string $verbo): array
{
    foreach ($modelo['ligacoes'] as $ligacao) {
        if ($ligacao['de'] === $de && $ligacao['para'] === $para && $ligacao['verbo'] === $verbo) {
            return $ligacao;
        }
    }

    throw new RuntimeException("a ligação {$de} {$verbo} {$para} não está no modelo");
}

/* ---------- R18: o modo de cada diagrama ---------- */

/**
 * O oráculo do código: a ligação cujo trecho está dentro do bloco `if (config('kit.tenancy.enabled'))`
 * só vale com tenant, qualquer que seja o modo que o modelo lhe dá.
 */
function mapaDiagramasSoComTenant(array $ligacao): bool
{
    if (($ligacao['modos'] ?? []) === ['com']) {
        return true;
    }
    $caminho = base_path($ligacao['evidencia']['arquivo'] ?? '');
    if (! is_file($caminho)) {
        return false;
    }
    $codigo     = codigoSemComentario((string) file_get_contents($caminho));
    $trecho     = (string) ($ligacao['evidencia']['trecho'] ?? '');
    $ocorrencia = $trecho === '' ? false : strpos($codigo, $trecho);
    if ($ocorrencia === false) {
        return false;
    }
    // Só o bloco cuja condição é a chave: o `config('kit.tenancy.enabled')` de um `->visible(fn …)` não abre bloco,
    // e a próxima chave depois dele é o corpo de outro método.
    preg_match_all("/\bif\s*\(\s*(?:\(bool\)\s*)?config\(\s*'kit\.tenancy\.enabled'[^)]*\)\s*\)\s*\{/", $codigo, $blocos, PREG_OFFSET_CAPTURE);
    foreach ($blocos[0] as [$cabecalho, $posicao]) {
        $abre  = $posicao + strlen($cabecalho) - 1;
        $nivel = 0;
        for ($i = $abre, $total = strlen($codigo); $i < $total; $i++) {
            $nivel += match ($codigo[$i]) {
                '{'     => 1,
                '}'     => -1,
                default => 0,
            };
            if ($nivel === 0) {
                if ($ocorrencia > $abre && $ocorrencia < $i) {
                    return true;
                }

                break;
            }
        }
    }

    return false;
}

/**
 * @param  array<string, array>  $irs
 * @return array{aceita: bool, motivo: string}
 */
function mapaDiagramasConferirModos(array $modelo, array $irs): array
{
    $falhas      = [];
    $ambos       = ['sem', 'com'];
    $assinaturas = [];
    foreach ($irs as $slug => $ir) {
        if ($ir['tipo'] === 'compare' || count($ir['modos']) === 0) {
            continue;
        }
        $compartilhado = count($ir['modos']) === 2;
        $pecas         = array_keys(mapaDiagramasPecasDesenhadas($ir));
        foreach ($pecas as $peca) {
            $registrada = $modelo['pecas'][$peca]['registrada'] ?? $ambos;
            if ($compartilhado && array_diff($ambos, $registrada) !== []) {
                $falhas[] = "o diagrama compartilhado {$slug} desenha a peça ".mapaDiagramasNomeDaPeca($modelo, $peca).', que só existe com tenant';
            } elseif (! $compartilhado && ! in_array($ir['modos'][0], $registrada, true)) {
                $falhas[] = "o diagrama {$slug} ({$ir['modos'][0]}) desenha a peça ".mapaDiagramasNomeDaPeca($modelo, $peca).', que só existe no outro modo';
            }
        }
        $desenhadasNoDiagrama = array_fill_keys(mapaDiagramasLigacoesDesenhadas($modelo, $ir), true);
        $ligacoesDesenhadas   = [];
        foreach ($modelo['ligacoes'] as $ligacao) {
            if (! isset($desenhadasNoDiagrama[$ligacao['de'].'|'.$ligacao['para'].'|'.$ligacao['verbo']])) {
                continue;
            }
            $ligacoesDesenhadas[] = $ligacao['de'].'|'.$ligacao['para'].'|'.$ligacao['verbo'];
            $efetivos             = mapaDiagramasSoComTenant($ligacao) ? ['com'] : $ligacao['modos'];
            $erra                 = $compartilhado ? array_diff($ambos, $efetivos) !== [] : ! in_array($ir['modos'][0], $efetivos, true);
            if ($erra) {
                $falhas[] = "o diagrama {$slug} desenha a ligação ".mapaDiagramasDescricaoDaLigacao($modelo, $ligacao).', que só vale com tenant';
            }
        }
        if (! $compartilhado) {
            sort($pecas);
            sort($ligacoesDesenhadas);
            $chave                 = $ir['tipo'].'#'.implode(',', $ir['paineis']);
            $assinaturas[$chave][] = [$slug, $ir['modos'][0], md5((string) json_encode([$pecas, $ligacoesDesenhadas, $ir['estrutura'] ?? null]))];
        }
    }
    foreach ($assinaturas as $grupo) {
        foreach ($grupo as $i => [$slugA, $modoA, $assinaturaA]) {
            foreach (array_slice($grupo, $i + 1) as [$slugB, $modoB, $assinaturaB]) {
                if ($modoA !== $modoB && $assinaturaA === $assinaturaB) {
                    $falhas[] = "os diagramas {$slugA} e {$slugB} são do mesmo tipo e painel, um por modo, e desenham as mesmas peças e ligações";
                }
            }
        }
    }

    return $falhas === []
        ? ['aceita' => true, 'motivo' => 'aceita']
        : ['aceita' => false, 'motivo' => 'reprova: '.implode(' | ', array_slice($falhas, 0, 15))];
}

/* ---------- R17: a grade ---------- */

/**
 * @param  array<string, array<string, list<array{slug: string, compartilhado: bool}>>>  $grade
 * @param  list<string>  $paineis
 * @return list<array{painel: string, modo: string, motivo: string}>
 */
function mapaDiagramasFalhasDaGrade(array $grade, array $paineis, array $decls): array
{
    $falhas = [];
    foreach ($paineis as $painel) {
        foreach (['sem', 'com'] as $modo) {
            $outro  = $modo === 'sem' ? 'com' : 'sem';
            $celula = $grade[$painel][$modo] ?? null;
            if ($celula === null) {
                $falhas[] = ['painel' => $painel, 'modo' => $modo, 'motivo' => "a grade não tem a célula {$painel} × {$modo}"];

                continue;
            }
            $validos = array_filter($celula, static fn (array $item): bool => isset($decls[$item['slug']])
                && in_array($painel, $decls[$item['slug']]['paineis'], true)
                && in_array($modo, $decls[$item['slug']]['modos'], true));
            if ($validos === []) {
                $falhas[] = ['painel' => $painel, 'modo' => $modo, 'motivo' => "a célula {$painel} × {$modo} não leva a diagrama do painel que vale nesse modo"];
            }
            foreach ($celula as $item) {
                $decl = $decls[$item['slug']] ?? null;
                if ($decl === null) {
                    $falhas[] = ['painel' => $painel, 'modo' => $modo, 'motivo' => "a célula {$painel} × {$modo} leva a {$item['slug']}, que não existe"];

                    continue;
                }
                if (! in_array($painel, $decl['paineis'], true)) {
                    $falhas[] = ['painel' => $painel, 'modo' => $modo, 'motivo' => "a célula {$painel} × {$modo} leva a {$item['slug']}, que é de outro painel"];
                } elseif (! in_array($modo, $decl['modos'], true)) {
                    $falhas[] = ['painel' => $painel, 'modo' => $modo, 'motivo' => "a célula {$painel} × {$modo} leva a {$item['slug']}, que só vale no outro modo"];
                } elseif (count($decl['modos']) === 2) {
                    $naOutra = array_filter($grade[$painel][$outro] ?? [], static fn (array $outroItem): bool => $outroItem['slug'] === $item['slug'] && $outroItem['compartilhado']);
                    if (! $item['compartilhado'] || $naOutra === []) {
                        $falhas[] = ['painel' => $painel, 'modo' => $modo, 'motivo' => "{$item['slug']} vale nos dois modos e não está nas duas células do {$painel}, marcado como compartilhado"];
                    }
                }
                if ($item['compartilhado'] && count($decl['modos']) < 2) {
                    $falhas[] = ['painel' => $painel, 'modo' => $modo, 'motivo' => "a célula {$painel} × {$modo} marca {$item['slug']} como compartilhado, e ele vale num modo só"];
                }
            }
        }
    }

    return $falhas;
}

/* ---------- R19 e R20 ---------- */

/** @return array<string, true> as peças que algum painel registra */
function mapaDiagramasRegistradasPorPainel(array $modelo): array
{
    $saida = [];
    foreach ($modelo['ligacoes'] as $ligacao) {
        if (str_starts_with($ligacao['de'], 'painel:') && $ligacao['verbo'] === 'registra') {
            $saida[$ligacao['para']] = true;
        }
    }

    return $saida;
}

/**
 * @param  list<array{tipo: string, celulas: array}>  $matriz
 * @param  list<array{tipo: string, celulas: array}>|null  $matrizPt
 * @param  array<string, array>  $irs
 * @return array{aceita: bool, motivo: string}
 */
function mapaDiagramasConferirMatriz(array $matriz, array $decls, string $idioma, ?array $matrizPt, array $irs, array $modelo): array
{
    $falhas      = [];
    $registradas = mapaDiagramasRegistradasPorPainel($modelo);
    foreach ($matriz as $indice => $linha) {
        $tipo = $linha['tipo'];
        if (! in_array($tipo, mapaDiagramasTiposDaMatriz(), true)) {
            $falhas[] = "o tipo {$tipo} não está no conjunto fechado de tipos";

            continue;
        }
        foreach ($linha['celulas'] as $coluna => $celula) {
            [$painel, $modo] = explode('|', $coluna);
            $nome            = "a célula {$tipo} × {$painel} × {$modo}";
            if ($celula['itens'] !== []) {
                foreach ($celula['itens'] as $item) {
                    $decl = $decls[$item['slug']] ?? null;
                    if ($decl === null) {
                        $falhas[] = "{$nome} leva a {$item['slug']}, que não existe";
                    } elseif ($decl['tipo'] !== $tipo) {
                        $falhas[] = "{$nome} leva a {$item['slug']}, que é do tipo {$decl['tipo']}";
                    } elseif (! in_array($painel, $decl['paineis'], true)) {
                        $falhas[] = "{$nome} leva a {$item['slug']}, que não é do painel {$painel}";
                    } elseif (! in_array($modo, $decl['modos'], true)) {
                        $falhas[] = "{$nome} leva a {$item['slug']}, que não vale no modo {$modo}";
                    }
                }

                continue;
            }
            $motivo = $celula['motivo'];
            if (motivoDeEnfeite($motivo)) {
                $falhas[] = "{$nome} não tem diagrama e o motivo é vazio ou de enfeite (\"{$motivo}\")";

                continue;
            }
            if (preg_match('/kit inteiro|kit-wide/u', $motivo) === 1) {
                $doKit = array_filter($decls, static fn (array $decl): bool => $decl['tipo'] === $tipo && $decl['paineis'] === []);
                if ($doKit === []) {
                    $falhas[] = "{$nome} se resolve com o motivo \"do kit inteiro\" e não há diagrama do kit inteiro do tipo {$tipo}";
                }
            }
            if ($idioma === 'en') {
                if (mapaDiagramasNumeroDePalavras($motivo) >= 2 && mapaDiagramasTemPalavraDoPt($motivo)) {
                    $falhas[] = "{$nome}: o motivo en \"{$motivo}\" traz palavra funcional do pt";
                }
                $motivoPt = $matrizPt[$indice]['celulas'][$coluna]['motivo'] ?? null;
                if ($motivoPt !== null && $motivoPt === $motivo) {
                    $falhas[] = "{$nome}: o motivo en é o texto pt copiado";
                }
            }
        }
    }
    foreach ($irs as $slug => $ir) {
        if ($ir['paineis'] === [] && $ir['tipo'] !== 'compare') {
            $registrada = array_intersect_key(mapaDiagramasPecasDesenhadas($ir), $registradas);
            foreach (array_keys($registrada) as $peca) {
                $falhas[] = "o diagrama {$slug} é do kit inteiro (paineis vazio) e desenha ".mapaDiagramasNomeDaPeca($modelo, $peca).', que painel registra';
            }
        }
    }

    return $falhas === []
        ? ['aceita' => true, 'motivo' => 'aceita']
        : ['aceita' => false, 'motivo' => 'reprova: '.implode(' | ', array_slice($falhas, 0, 15))];
}

/**
 * @param  list<string>  $publicados
 * @return array{aceita: bool, motivo: string, comparativos: int}
 */
function mapaDiagramasConferirComparativos(array $decls, array $publicados): array
{
    $falhas = [];
    $total  = 0;
    foreach ($decls as $slug => $decl) {
        if ($decl['tipo'] !== 'compare') {
            continue;
        }
        $total++;
        $base   = $decls[$decl['base'] ?? ''] ?? null;
        $cabeca = $decls[$decl['cabeca'] ?? ''] ?? null;
        if ($base === null || $cabeca === null) {
            $falhas[] = "o comparativo {$slug} tem base ou cabeça que não é arquivo de diagrama do mapa";

            continue;
        }
        if ($base['modos'] !== ['sem'] || $cabeca['modos'] !== ['com']) {
            $falhas[] = "o comparativo {$slug} não vai do modo sem tenant (base {$base['slug']}) para o com tenant (cabeça {$cabeca['slug']})";
        }
        if ($base['paineis'] !== $cabeca['paineis'] || $base['paineis'] !== $decl['paineis']) {
            $falhas[] = "o comparativo {$slug} mistura painéis: base ".implode(',', $base['paineis']).', cabeça '.implode(',', $cabeca['paineis']);
        }
        foreach ([$base['slug'], $cabeca['slug'], $slug] as $alvo) {
            if (! in_array($alvo, $publicados, true)) {
                $falhas[] = "o comparativo {$slug} usa {$alvo}, que a página não publica";
            }
        }
    }

    return [
        'aceita'       => $falhas === [],
        'motivo'       => $falhas === [] ? 'aceita' : 'reprova: '.implode(' | ', $falhas),
        'comparativos' => $total,
    ];
}

/* ---------- R22: fluxos ---------- */

/** @return array<string, true> os arquivos das peças do modelo: a evidência e o caminho PSR-4 da classe */
function mapaDiagramasArquivosDasPecas(array $modelo): array
{
    $saida = [];
    foreach ($modelo['pecas'] as $id => $peca) {
        foreach ($peca['evidencia'] ?? [] as $evidencia) {
            $saida[$evidencia['arquivo']] = true;
        }
        if (str_starts_with($id, 'App\\')) {
            $saida['app/'.str_replace('\\', '/', substr($id, 4)).'.php'] = true;
        }
    }

    return $saida;
}

/**
 * R22: há fluxo publicado e todo passo de workflow, sequence e dataflow percorre peça do modelo.
 * O passo é a aresta (aresta do workflow, mensagem da sequência, fluxo do dataflow); ele percorre a peça
 * quando uma ponta nomeia a peça (id, nome ou palavra do rótulo) ou quando a prova dele está no arquivo de uma peça.
 *
 * @return array{aceita: bool, motivo: string}
 */
function mapaDiagramasConferirFluxos(array $decls, array $modelo): array
{
    $fluxos = array_filter($decls, static fn (array $decl): bool => in_array($decl['tipo'], ['workflow', 'sequence', 'dataflow', 'lifecycle'], true));
    if ($fluxos === []) {
        return ['aceita' => false, 'motivo' => 'reprova: nenhum fluxo publicado'];
    }
    $arquivos   = mapaDiagramasArquivosDasPecas($modelo);
    $porNome    = mapaDiagramasIdsPorNome($modelo);
    $colecoes   = ['workflow' => ['nodes', 'edges'], 'sequence' => ['participants', 'messages'], 'dataflow' => ['nodes', 'flows']];
    $nomeiaPeca = static function (array $no) use ($porNome, $modelo): bool {
        if (isset($modelo['pecas'][$no['id'] ?? ''])) {
            return true;
        }
        $texto = implode(' ', [mapaDiagramasPt($no['label'] ?? ''), mapaDiagramasEn($no['label'] ?? ''), mapaDiagramasPt($no['sublabel'] ?? ''), mapaDiagramasPt($no['tag'] ?? '')]);
        preg_match_all('/[A-Za-z_][A-Za-z0-9_]*/', $texto, $m);
        foreach ($m[0] as $palavra) {
            if (isset($porNome[$palavra])) {
                return true;
            }
        }

        return false;
    };
    $falhas = [];
    foreach ($fluxos as $slug => $decl) {
        if (! isset($colecoes[$decl['tipo']])) {
            continue;
        }
        [$chaveDosNos, $chaveDasArestas] = $colecoes[$decl['tipo']];
        $nos                             = [];
        foreach ($decl[$chaveDosNos] ?? [] as $no) {
            $nos[$no['id']] = $no;
        }
        foreach ($decl[$chaveDasArestas] ?? [] as $passo) {
            // R22 (alterado no passo 8): no dataflow, o passo entre dois depósitos (`type: database`) não nomeia
            // peça, como o estado do lifecycle; o passo com uma ponta fora de depósito continua obrigado.
            $ehDeposito = static fn (string $id): bool => ($nos[$id]['type'] ?? null) === 'database';
            if ($decl['tipo'] === 'dataflow' && $ehDeposito((string) ($passo['from'] ?? '')) && $ehDeposito((string) ($passo['to'] ?? ''))) {
                continue;
            }
            $de   = $nos[$passo['from'] ?? ''] ?? [];
            $para = $nos[$passo['to'] ?? ''] ?? [];
            // Só a prova do próprio passo: a âncora da outra ponta (R19: ela não faz do nó a peça) deixava passar o
            // passo que nomeia peça que não existe sempre que ele saía de um nó ancorado (R22-M2).
            $provas   = array_filter([$passo['prova']['arquivo'] ?? null]);
            $percorre = ($de !== [] && $nomeiaPeca($de)) || ($para !== [] && $nomeiaPeca($para));
            foreach ($provas as $arquivo) {
                $percorre = $percorre || isset($arquivos[$arquivo]);
            }
            if (! $percorre) {
                $falhas[] = "{$slug}: o passo ".($passo['id'] ?? ($passo['from'] ?? '?').' → '.($passo['to'] ?? '?')).' não percorre peça do modelo';
            }
        }
    }

    return $falhas === []
        ? ['aceita' => true, 'motivo' => 'aceita']
        : ['aceita' => false, 'motivo' => 'reprova: '.count($falhas).' passo(s): '.implode(' | ', $falhas)];
}

/* ---------- R29: CHANGELOG ---------- */

/** @return array<string, array{data: ?string, corpo: string}> */
function mapaDiagramasSecoesDoChangelog(): array
{
    static $secoes = null;

    return $secoes ??= (function (): array {
        $texto  = (string) file_get_contents(base_path('CHANGELOG.md'));
        // O grupo da data sempre participa (vazio no [Unreleased]): o grupo opcional que não casa no fim some do
        // PREG_SPLIT_DELIM_CAPTURE e desalinha as trincas versão/data/corpo.
        $partes = preg_split('/^## \[([^\]]+)\]((?: - \d{4}-\d{2}-\d{2})?)[ \t]*$/m', $texto, -1, PREG_SPLIT_DELIM_CAPTURE);
        $saida  = [];
        for ($i = 1; $i + 2 < count($partes) + 1; $i += 3) {
            $data               = ltrim($partes[$i + 1], ' -');
            $saida[$partes[$i]] = ['data' => $data !== '' ? $data : null, 'corpo' => $partes[$i + 2] ?? ''];
        }

        return $saida;
    })();
}

function mapaDiagramasNormalizar(string $texto): string
{
    return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', str_replace(['*', '`', '_'], '', $texto))));
}

/**
 * @param  array<string, array>  $decls
 * @param  array<string, array{data: ?string, corpo: string}>  $secoes
 * @return array{aceita: bool, motivo: string, marcos: int, tempos: int}
 */
function mapaDiagramasConferirMarcosETempos(array $decls, array $secoes): array
{
    $falhas = [];
    $marcos = 0;
    $tempos = 0;
    foreach ($decls as $slug => $decl) {
        foreach ($decl['tipo'] === 'timeline' ? ($decl['events'] ?? []) : [] as $evento) {
            $marcos++;
            $titulo = mapaDiagramasPt($evento['title'] ?? '');
            if (preg_match('/^(\d+\.\d+\.\d+|Unreleased)\s*[·—–:-]\s*(.+)$/u', $titulo, $m) !== 1) {
                $falhas[] = "{$slug}: o marco \"{$titulo}\" não começa pelo número da versão";

                continue;
            }
            [, $versao, $visivel] = $m;
            $secao                = $secoes[$versao] ?? null;
            if ($secao === null) {
                $falhas[] = "{$slug}: o marco {$versao} cita versão que o CHANGELOG não tem";
            } elseif ($secao['data'] === null) {
                $falhas[] = "{$slug}: o marco {$versao} cita versão sem data";
            } else {
                if (substr((string) ($evento['at'] ?? ''), 0, 10) !== $secao['data']) {
                    $falhas[] = "{$slug}: o marco {$versao} está datado de ".substr((string) ($evento['at'] ?? ''), 0, 10).", e o CHANGELOG diz {$secao['data']}";
                }
                if (! str_contains(mapaDiagramasNormalizar($secao['corpo']), mapaDiagramasNormalizar($visivel))) {
                    $falhas[] = "{$slug}: o título visível do marco {$versao} (\"{$visivel}\") não aparece na seção dessa versão";
                }
            }
        }
        foreach ($decl['tipo'] === 'waterfall' ? ($decl['spans'] ?? []) : [] as $passo) {
            $tempos++;
            $detalhe = mapaDiagramasPt($passo['detail'] ?? '');
            if (preg_match('#\b\d{2}/\d{2}/\d{4}\b#', $detalhe) !== 1) {
                $falhas[] = "{$slug}: o tempo do passo {$passo['id']} não traz a data da medição";
            }
            if (preg_match('/\brun \d+|\blog d[oa]\b|\bphp artisan\b|\bcomposer\b|\bnpm\b/iu', $detalhe) !== 1) {
                $falhas[] = "{$slug}: o tempo do passo {$passo['id']} não traz a fonte da medição";
            }
        }
    }

    return [
        'aceita' => $falhas === [],
        'motivo' => $falhas === [] ? 'aceita' : 'reprova: '.count($falhas).' item(ns): '.implode(' | ', array_slice($falhas, 0, 14)),
        'marcos' => $marcos,
        'tempos' => $tempos,
    ];
}

/* ---------- R6: origem e entrega ---------- */

/**
 * As linhas do catálogo (`linhasDoCatalogo()`, de `tests/Pest.php`) por F-nn, como a guarda da R6 as lê:
 * a feature, a origem, os pacotes, o "onde fica" e a marca de removida, com o motivo.
 *
 * @param  list<list<string>>  $linhas
 * @return array<string, array{feature: string, origem: string, pacotes: list<string>, onde: string, removida: bool, motivo: string}>
 */
function mapaDiagramasCatalogoPorFnn(array $linhas): array
{
    $saida = [];
    foreach ($linhas as $celulas) {
        $origem = mb_strtolower($celulas[2] ?? '');
        $tipo   = str_contains($origem, 'customiza') ? 'custom' : ((str_starts_with($origem, 'pacote') || str_starts_with($origem, 'package')) ? 'pacote' : 'proprio');
        preg_match_all('/`([^`]+)`/u', $celulas[2] ?? '', $pacotes);
        $removida            = preg_match('/removid[ao]|\bremoved\b/iu', $celulas[1] ?? '') === 1;
        $saida[$celulas[0]]  = [
            'feature'  => $celulas[1] ?? '',
            'origem'   => $tipo,
            'pacotes'  => $pacotes[1],
            'onde'     => $celulas[3] ?? '',
            'removida' => $removida,
            'motivo'   => $removida ? trim((string) preg_replace('/^.*?removid[ao]\s*:?/iu', '', $celulas[1] ?? '')) : '',
        ];
    }

    return $saida;
}

/** O caminho de rota da célula "onde fica": entre crases, começa por `/`, e a célula não traz "→". */
function mapaDiagramasCaminhosDoOnde(string $onde): array
{
    if (str_contains($onde, '→')) {
        return [];
    }
    preg_match_all('/`(\/[^`]*)`/u', $onde, $m);

    return $m[1];
}

/**
 * Os caminhos concretos de um caminho do catálogo: `{painel}` e `{panel}` valem para cada painel, `{/org}`
 * vale sem a organização, `{org}` só existe com tenant (CT-44), a query sai, outro parâmetro aceita qualquer valor.
 *
 * @param  list<string>  $paineis
 * @return list<string>
 */
function mapaDiagramasExpandirCaminho(string $caminho, array $paineis): array
{
    $caminho = (string) preg_replace('/[?#].*$/', '', $caminho);
    if (str_contains($caminho, '{org}')) {
        return [];
    }
    $caminho   = str_replace('{/org}', '', $caminho);
    $variantes = (str_contains($caminho, '{painel}') || str_contains($caminho, '{panel}'))
        ? array_map(static fn (string $painel): string => str_replace(['{painel}', '{panel}'], $painel, $caminho), $paineis)
        : [$caminho];

    return array_map(static fn (string $variante): string => (string) preg_replace('/\{[^}]+\}/', 'x', $variante), $variantes);
}

function mapaDiagramasResolverRota(string $caminho): ?string
{
    try {
        $rota = app('router')->getRoutes()->match(Request::create($caminho, 'GET'));
    } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
        return null;
    }

    return $rota->isFallback ? null : $rota->getActionName();
}

/** @return list<string> a classe que responde e, nas páginas de recurso, o recurso dono */
function mapaDiagramasCandidatasDaRota(string $acao): array
{
    $classe     = explode('@', $acao)[0];
    $candidatas = [$classe];
    if (class_exists($classe) && method_exists($classe, 'getResource')) {
        try {
            $candidatas[] = $classe::getResource();
        } catch (Throwable) {
        }
    }

    return $candidatas;
}

/** O arquivo da peça é do pacote: `arquivoDaClasse()` (relativo à raiz) dentro de `vendor/{pacote}/`. */
function mapaDiagramasPecaEhDoPacote(string $id, string $pacote): bool
{
    $arquivo = arquivoDaClasse($id);

    return $arquivo !== null && str_contains('/'.$arquivo, '/vendor/'.$pacote.'/');
}

function mapaDiagramasPecaEhDoKit(array $modelo, string $id): bool
{
    $arquivo = arquivoDaClasse($id);
    if ($arquivo === null) {
        // painel:{id} e o alias `panel:{id}` não são classe: valem pelo arquivo da evidência, que é do kit.
        $evidencia = $modelo['pecas'][$id]['evidencia'][0]['arquivo'] ?? '';

        return $evidencia !== '' && ! str_starts_with($evidencia, 'vendor/');
    }

    return ! str_contains('/'.$arquivo, '/vendor/') && ! str_contains('/'.$arquivo, '/node_modules/');
}

/**
 * A lista fechada das F-nn que a guarda trata como exceção nomeada, cada uma com o motivo (D33 do plano).
 *
 * @return array{sem_peca: array<string, string>, so_parte_do_kit: array<string, string>}
 */
function mapaDiagramasExcecoesDeOrigem(): array
{
    return [
        'sem_peca' => [
            'F-64'  => 'a feature é uma guarda de teste (tests/Kit/PermissoesDeAcoesTest.php), e teste não é peça com nome do app',
            'F-99'  => 'a UI em pt-BR vem de arquivos de tradução (lang/pt_BR, lang/vendor), e nenhum arquivo de tradução é peça',
            'F-107' => 'a feature é o docker-compose.yml, serviços e profiles do Compose, sem classe do kit',
            'F-108' => 'a feature é o script de shell deploy_docker_local.sh, que roda no host, sem classe do app',
        ],
        'so_parte_do_kit' => [
            'F-100' => 'a parte do pacote é um componente de formulário e uma coluna de tabela, que não são tipo de peça da R8; vale só a parte do kit',
            'F-113' => 'o pacote é plugin do Pest e binário do npm, que não registram classe no app; vale só a parte do kit (kit:arte)',
        ],
    ];
}

/**
 * R6: toda F-nn vigente é entregue por peça desenhada da origem que declara, todo caminho do "onde fica"
 * resolve, e nenhuma peça entrega F-nn removida.
 *
 * @param  array<string, array>  $catalogo
 * @param  array<string, true>  $desenhadas
 * @param  list<string>  $paineis
 * @return array{aceita: bool, motivo: string, conferidas: array<string, int>}
 */
function mapaDiagramasConferirOrigem(array $catalogo, array $modelo, array $desenhadas, array $paineis): array
{
    $excecoes  = mapaDiagramasExcecoesDeOrigem();
    $entregues = [];
    foreach ($modelo['pecas'] as $id => $peca) {
        foreach ($peca['features'] as $fnn) {
            $entregues[$fnn][] = $id;
        }
    }
    $falhas     = [];
    $conferidas = ['pacote' => 0, 'proprio' => 0, 'custom' => 0];
    foreach ($catalogo as $fnn => $linha) {
        if ($linha['removida']) {
            foreach ($entregues[$fnn] ?? [] as $peca) {
                $falhas[] = "{$fnn} está marcada como removida e a peça ".mapaDiagramasNomeDaPeca($modelo, $peca).' ainda a entrega';
            }

            continue;
        }
        if (isset($excecoes['sem_peca'][$fnn])) {
            continue;
        }
        $conferidas[$linha['origem']]++;
        $pecas = array_values(array_filter($entregues[$fnn] ?? [], static fn (string $id): bool => isset($desenhadas[$id])));
        if ($pecas === []) {
            $falhas[] = "{$fnn} não é entregue por peça desenhada em diagrama publicado";

            continue;
        }
        $doPacote = array_filter($pecas, static function (string $id) use ($linha): bool {
            foreach ($linha['pacotes'] as $pacote) {
                if (mapaDiagramasPecaEhDoPacote($id, $pacote)) {
                    return true;
                }
            }

            return false;
        });
        $doKit = array_filter($pecas, static fn (string $id): bool => mapaDiagramasPecaEhDoKit($modelo, $id));
        $soKit = isset($excecoes['so_parte_do_kit'][$fnn]);
        if ($linha['origem'] === 'pacote' && $doPacote === []) {
            $falhas[] = "{$fnn} é de pacote ({$linha['feature']}) e nenhuma peça que a entrega é do pacote ".implode(', ', $linha['pacotes']);
        }
        if ($linha['origem'] === 'proprio' && $doKit === []) {
            $falhas[] = "{$fnn} é de código próprio e só peça de pacote a entrega";
        }
        if ($linha['origem'] === 'custom') {
            if ($doPacote === [] && ! $soKit) {
                $falhas[] = "{$fnn} é de pacote com customização própria e nenhuma peça que a entrega é do pacote ".implode(', ', $linha['pacotes']);
            }
            if ($doKit === []) {
                $falhas[] = "{$fnn} é de pacote com customização própria e nenhuma peça que a entrega é do código do kit";
            }
        }
        $caminhos      = mapaDiagramasCaminhosDoOnde($linha['onde']);
        $candidatas    = [];
        $naoResolvem   = [];
        $conferiuAlgum = false;
        foreach ($caminhos as $caminho) {
            foreach (mapaDiagramasExpandirCaminho($caminho, $paineis) as $concreto) {
                $conferiuAlgum = true;
                $acao          = mapaDiagramasResolverRota($concreto);
                if ($acao === null) {
                    $naoResolvem[] = $concreto;

                    continue;
                }
                $candidatas = [...$candidatas, ...mapaDiagramasCandidatasDaRota($acao)];
            }
        }
        if ($naoResolvem !== []) {
            $falhas[] = "{$fnn}: o caminho ".implode(', ', $naoResolvem).' não resolve no roteador';
        }
        if ($linha['origem'] === 'proprio' && $conferiuAlgum && $naoResolvem === [] && array_intersect($pecas, $candidatas) === []) {
            $falhas[] = "{$fnn} é de código próprio e nenhuma peça que a entrega responde na rota ".implode(', ', $caminhos);
        }
    }

    return [
        'aceita'     => $falhas === [],
        'motivo'     => $falhas === [] ? 'aceita' : 'reprova, '.count($falhas).' item(ns): '.implode(' | ', array_slice($falhas, 0, 15)),
        'conferidas' => $conferidas,
    ];
}

/** Copia o modelo com a F-nn entregue só pelas peças dadas. */
function mapaDiagramasEntregaSo(array $modelo, string $fnn, array $ids): array
{
    foreach ($modelo['pecas'] as $id => $peca) {
        $modelo['pecas'][$id]['features'] = array_values(array_diff($peca['features'], [$fnn]));
    }
    foreach ($ids as $id) {
        $modelo['pecas'][$id]['features'][] = $fnn;
    }

    return $modelo;
}

function mapaDiagramasIdTerminadoEm(array $modelo, string $sufixo): string
{
    foreach (array_keys($modelo['pecas']) as $id) {
        if (str_ends_with($id, '\\'.$sufixo)) {
            return $id;
        }
    }

    throw new RuntimeException("não há peça {$sufixo} no modelo");
}

/* ---------- R34: o registro do painel (o registro vivo vem de `registroVivoDoPainel()`) ---------- */

/**
 * R34: os diagramas `architecture` e a árvore de registro do painel que valem no modo desenham, juntos, o
 * registro dele nesse modo menos a peça que fica indisponível nele mesmo com a chave ligada — nem mais nem menos.
 * A subtração vem do discriminante vivo (`mapaDiagramasSubtracaoSemTenant()`), e não da condição do modelo nem de
 * lista escrita à mão: a peça subtraída que o desenho ainda tem sobra (Adendo 2, colisão a).
 *
 * @param  array<string, array>  $irs
 * @param  list<string>  $vivo
 * @param  list<string>  $subtraidas
 * @return array{aceita: bool, motivo: string, registro: int}
 */
function mapaDiagramasConferirRegistro(array $modelo, array $irs, string $painel, string $modo, array $vivo, array $subtraidas): array
{
    $raiz  = 'painel:'.$painel;
    $pares = [];
    foreach ($irs as $ir) {
        $vale = in_array($painel, $ir['paineis'], true) && in_array($modo, $ir['modos'], true)
            && ($ir['tipo'] === 'architecture' || $ir['construtor'] === 'registro');
        if ($vale) {
            $pares += mapaDiagramasPares($ir);
        }
    }
    $desenhado = [];
    foreach ($modelo['ligacoes'] as $ligacao) {
        if ($ligacao['de'] === $raiz && $ligacao['verbo'] === 'registra' && isset($pares[$raiz.'|'.$ligacao['para']])) {
            $desenhado[$ligacao['para']] = true;
        }
    }
    $esperado  = array_values(array_diff($vivo, $subtraidas));
    $desenhado = array_keys($desenhado);
    $faltam    = array_diff($esperado, $desenhado);
    $sobram    = array_diff($desenhado, $esperado);
    $falhas    = [];
    foreach ($faltam as $id) {
        $falhas[] = 'falta no desenho do painel '.$painel.': '.mapaDiagramasNomeDaPeca($modelo, $id)." ({$id})";
    }
    foreach ($sobram as $id) {
        $falhas[] = 'sobra no desenho do painel '.$painel.', que não o registra no modo '.$modo.' ou em que ele não abre nem com a chave ligada: '.mapaDiagramasNomeDaPeca($modelo, $id)." ({$id})";
    }

    return [
        'aceita'   => $falhas === [],
        'motivo'   => $falhas === [] ? 'aceita' : 'reprova, painel '.$painel.', '.count($falhas).' peça(s): '.implode(' | ', array_slice($falhas, 0, 15)),
        'registro' => count($esperado),
    ];
}

/*
|--------------------------------------------------------------------------
| R6 — origem e entrega (CT-06, CT-36)
|--------------------------------------------------------------------------
*/

it('[CT-06] toda F-nn é entregue por peça desenhada, e ao menos uma delas é da origem que declara', function (string $origem): void {
    $mundo      = mapaDiagramasMundo();
    $desenhadas = [];
    foreach (mapaDiagramasPublicados('pt') as $slug) {
        $desenhadas += mapaDiagramasPecasDesenhadas($mundo['irs'][$slug]);
    }
    $paineis  = array_keys(Filament::getPanels());
    $catalogo = mapaDiagramasCatalogoPorFnn(linhasDoCatalogo('pt'));
    $daOrigem = array_filter($catalogo, static fn (array $linha): bool => $linha['origem'] === $origem && ! $linha['removida']);
    $excecoes = mapaDiagramasExcecoesDeOrigem();

    // Piso de não vácuo: a origem tem F-nn, e as três origens juntas percorrem o catálogo inteiro.
    expect($daOrigem)->not->toBeEmpty()
        ->and(count(array_filter($catalogo, static fn (array $linha): bool => ! $linha['removida'])))->toBe(
            count(array_filter($catalogo, static fn (array $linha): bool => ! $linha['removida'] && in_array($linha['origem'], ['pacote', 'proprio', 'custom'], true))),
        );
    foreach (array_merge(array_keys($excecoes['sem_peca']), array_keys($excecoes['so_parte_do_kit'])) as $fnn) {
        expect($catalogo)->toHaveKey($fnn);
    }

    $resultado = mapaDiagramasConferirOrigem($daOrigem, $mundo['modelo'], $desenhadas, $paineis);

    expect($resultado['motivo'])->toBe('aceita')
        ->and($resultado['conferidas'][$origem])->toBeGreaterThan(0);
})->with(['pacote', 'proprio', 'custom']);

it('[CT-36] a guarda da origem reprova a F-nn entregue pela peça errada, a removida que ainda é entregue e o caminho que não resolve', function (string $mundoDeControle, array $nomeia): void {
    $mundo      = mapaDiagramasMundo();
    $paineis    = array_keys(Filament::getPanels());
    $modelo     = $mundo['modelo'];
    $catalogo   = mapaDiagramasCatalogoPorFnn(linhasDoCatalogo('pt'));
    $desenhadas = [];
    foreach (mapaDiagramasPublicados('pt') as $slug) {
        $desenhadas += mapaDiagramasPecasDesenhadas($mundo['irs'][$slug]);
    }
    $todas  = array_fill_keys(array_keys($modelo['pecas']), true);
    $breezy = mapaDiagramasIdTerminadoEm($modelo, 'BreezyCore');

    switch ($mundoDeControle) {
        case 'publicados':
            break;
        case 'F-33 entregue só pelo plugin de outro pacote':
            $modelo     = mapaDiagramasEntregaSo($modelo, 'F-33', [mapaDiagramasIdTerminadoEm($modelo, 'FilamentJobsMonitorPlugin')]);
            $desenhadas = $todas;
            break;
        case 'F-29 entregue só pelo ConviteResource':
            $modelo     = mapaDiagramasEntregaSo($modelo, 'F-29', [mapaDiagramasIdTerminadoEm($modelo, 'ConviteResource')]);
            $desenhadas = $todas;
            break;
        case 'código próprio entregue só por peça de pacote':
            expect($catalogo['F-118']['origem'])->toBe('proprio');
            $modelo     = mapaDiagramasEntregaSo($modelo, 'F-118', [$breezy]);
            $desenhadas = $todas;
            break;
        case 'customização com uma peça só':
            expect($catalogo['F-04']['origem'])->toBe('custom');
            $modelo     = mapaDiagramasEntregaSo($modelo, 'F-04', [$breezy]);
            $desenhadas = $todas;
            break;
        case 'F-05 removida e ainda entregue':
            $catalogo['F-05']['removida'] = true;
            $catalogo['F-05']['motivo']   = 'desligada no kit e sem código que a ligue';
            $desenhadas                   = $todas;
            break;
        case 'F-07 en com caminho que não resolve':
            $catalogo                 = mapaDiagramasCatalogoPorFnn(linhasDoCatalogo('en'));
            $catalogo['F-07']['onde'] = '`/{panel}/my-profile`';
            $desenhadas               = $todas;
            break;
    }

    $resultado = mapaDiagramasConferirOrigem($catalogo, $modelo, $desenhadas, $paineis);

    if ($mundoDeControle === 'publicados') {
        expect($resultado['motivo'])->toBe('aceita');

        return;
    }
    expect($resultado['aceita'])->toBeFalse();
    foreach ($nomeia as $trecho) {
        expect($resultado['motivo'])->toContain($trecho);
    }
})->with([
    'publicados'                    => ['publicados', []],
    'peça de outro pacote'          => ['F-33 entregue só pelo plugin de outro pacote', ['F-33', 'shuvroroy/filament-spatie-laravel-health']],
    'peça que não responde na rota' => ['F-29 entregue só pelo ConviteResource', ['F-29', '/admin/users']],
    'origem errada'                 => ['código próprio entregue só por peça de pacote', ['F-118']],
    'customização com uma peça só'  => ['customização com uma peça só', ['F-04']],
    'removida e entregue'           => ['F-05 removida e ainda entregue', ['F-05', 'BreezyCore']],
    'caminho que não resolve'       => ['F-07 en com caminho que não resolve', ['F-07', '/my-profile']],
]);

/*
|--------------------------------------------------------------------------
| R12 — toda ligação desenhada (CT-14)
|--------------------------------------------------------------------------
*/

it('[CT-14] toda ligação do modelo aparece desenhada em diagrama publicado, e só aresta conta', function (string $mundoDeControle): void {
    $mundo      = mapaDiagramasMundo();
    $modelo     = $mundo['modelo'];
    $irs        = $mundo['irs'];
    $publicados = mapaDiagramasPublicados('pt');
    $shield     = mapaDiagramasLigacaoDe($modelo, 'painel:admin', mapaDiagramasIdTerminadoEm($modelo, 'FilamentShieldPlugin'), 'registra');

    // Um agrupamento do /admin com recursos, e a ligação que só ele desenha, isolados num mundo de uma ligação.
    $grupo          = null;
    $ligacaoDoGrupo = null;
    foreach ($irs['admin-resumo-sem']['nos'] as $id => $no) {
        foreach ($no['membros'] ?? [] as $membro) {
            if ($no['tipo'] === 'grupo' && ($modelo['pecas'][$membro]['tipo'] ?? '') === 'recurso') {
                $grupo          = $id;
                $ligacaoDoGrupo = mapaDiagramasLigacaoDe($modelo, 'painel:admin', $membro, 'registra');

                break 2;
            }
        }
    }
    expect($grupo)->not->toBeNull();
    $isolado = ['pecas' => $modelo['pecas'], 'ligacoes' => [$ligacaoDoGrupo]];
    $soAdmin = ['admin-resumo-sem' => $irs['admin-resumo-sem']];

    if ($mundoDeControle === 'publicados') {
        $resultado = mapaDiagramasConferirLigacoes($modelo, $irs, $publicados);
        expect($resultado['motivo'])->toBe('aceita')
            ->and($resultado['achadas'])->toBe(count($modelo['ligacoes']));
        expect(mapaDiagramasConferirLigacoes($isolado, $soAdmin, ['admin-resumo-sem'])['motivo'])->toBe('aceita');

        return;
    }

    if (in_array($mundoDeControle, ['ligação de registro de um modo só na árvore compartilhada', 'a mesma ligação, nos dois modos'], true)) {
        // A árvore `infra-registro` é compartilhada (modos sem e com): o mundo é só ela, montada duas vezes — pela guarda e
        // pelo gerador (forma de leitura da G5) —, com a ligação "o painel infra registra o FilamentShieldPlugin" acrescentada
        // à cópia em memória do modelo (Setup Global do `04`, "O diagrama que vale nos dois modos e a ligação de um modo só").
        $nosDoisModos = $mundoDeControle === 'a mesma ligação, nos dois modos';
        $registro     = [...$shield, 'de' => 'painel:infra', 'paineis' => ['infra'], 'modos' => $nosDoisModos ? ['sem', 'com'] : ['sem']];
        $arvore       = diagramasDoMapa()['infra-registro'];
        $daGuarda     = ['pecas' => $modelo['pecas'], 'ligacoes' => [$registro]];
        $naGuarda     = mapaDiagramasConferirLigacoes($daGuarda, ['infra-registro' => mapaDiagramasConstruir($arvore, $daGuarda)], ['infra-registro']);
        $descricao    = mapaDiagramasDescricaoDaLigacao($daGuarda, $registro);
        $montado      = entradasDoGeradorDoMapa(['slugs' => ['infra-registro'], 'ligacoes' => [$registro]]);
        $idDoPlugin   = $montado['ids'][$shield['para']];

        if ($nosDoisModos) {
            expect($naGuarda['motivo'])->toBe('aceita');
        } else {
            expect($naGuarda['aceita'])->toBeFalse()
                ->and($naGuarda['motivo'])->toContain('FilamentShieldPlugin', 'painel:infra');
        }

        foreach (['pt', 'en'] as $idioma) {
            $entrada = $montado['diagramas']['infra-registro'][$idioma] ?? [];
            expect($entrada)->not->toHaveKey('erro');
            $nos      = array_column($entrada['nodes'] ?? [], 'id');
            $itens    = array_merge([], ...array_map(static fn (array $card): array => array_map('strval', $card['items'] ?? []), $entrada['cards'] ?? []));
            $doPlugin = array_values(array_filter($itens, static fn (string $item): bool => str_contains($item, 'FilamentShieldPlugin')));
            $provas   = array_values(array_filter($doPlugin, static fn (string $item): bool => str_contains($item, (string) $registro['evidencia']['trecho'])));

            if ($nosDoisModos) {
                expect(in_array($idDoPlugin, $nos, true))->toBeTrue("infra-registro ({$idioma}): o gerador não desenhou o nó do FilamentShieldPlugin");
                expect($provas)->not->toBeEmpty("infra-registro ({$idioma}): o item de prova da ligação não está no card");
            } else {
                expect(in_array($idDoPlugin, $nos, true))->toBeFalse("infra-registro ({$idioma}): o gerador desenhou o nó do FilamentShieldPlugin, e a ligação ({$descricao}) vale só sem tenant");
                expect($doPlugin)->toBe([], "infra-registro ({$idioma}): o gerador desenhou item de card do FilamentShieldPlugin, e a ligação ({$descricao}) vale só sem tenant");
            }
        }

        return;
    }

    $mundosDoMapaCompartilhado = [
        'ligação de um modo só no mapa compartilhado',
        'ponta registrada num modo só no mapa compartilhado',
        'as duas ligações e as duas pontas, nos dois modos',
    ];

    if (in_array($mundoDeControle, $mundosDoMapaCompartilhado, true)) {
        // O mapa de arquitetura `infra-convites` é compartilhado (modos sem e com): o mundo é só ele (Setup Global do `04`,
        // "A mesma regra vale para o mapa de arquitetura e para a ponta"). A ligação nova "ConviteDeAcesso usa Convite" vai
        // no sentido contrário ao da publicada "Convite usa ConviteDeAcesso", para a seta dela ser outra.
        $convite      = 'App\\Models\\Convite';
        $aviso        = 'App\\Notifications\\ConviteDeAcesso';
        $publicada    = mapaDiagramasLigacaoDe($modelo, $convite, $aviso, 'usa');
        $dosDoisModos = $mundoDeControle === 'as duas ligações e as duas pontas, nos dois modos';
        $soAsLigacao  = $mundoDeControle === 'ligação de um modo só no mapa compartilhado';
        $nova         = [...$publicada, 'de' => $aviso, 'para' => $convite, 'paineis' => ['infra'], 'modos' => $soAsLigacao ? ['sem'] : ['sem', 'com']];
        $arvore       = diagramasDoMapa()['infra-convites'];
        $pecas        = $modelo['pecas'];
        if ($mundoDeControle === 'ponta registrada num modo só no mapa compartilhado') {
            $pecas[$aviso]['registrada'] = ['sem'];
        }
        $daGuarda = ['pecas' => $pecas, 'ligacoes' => $soAsLigacao || $dosDoisModos ? [$nova, $publicada] : [$publicada]];
        $naGuarda = mapaDiagramasConferirLigacoes($daGuarda, ['infra-convites' => mapaDiagramasConstruir($arvore, $daGuarda)], ['infra-convites']);

        if ($dosDoisModos) {
            expect($naGuarda['motivo'])->toBe('aceita');
        } else {
            $quem = $soAsLigacao ? mapaDiagramasDescricaoDaLigacao($daGuarda, $nova) : mapaDiagramasDescricaoDaLigacao($daGuarda, $publicada);
            expect($naGuarda['aceita'])->toBeFalse()
                ->and($naGuarda['motivo'])->toContain($quem);
        }

        if ($mundoDeControle === 'ponta registrada num modo só no mapa compartilhado') {
            // O gerador já confere o `registrada` em cada modo (`entraNosModos`): esta linha só se monta pela guarda.
            return;
        }

        $montado = entradasDoGeradorDoMapa(['slugs' => ['infra-convites'], 'ligacoes' => [$nova]]);
        $idConv  = $montado['ids'][$convite];
        $idAviso = $montado['ids'][$aviso];

        foreach (['pt', 'en'] as $idioma) {
            $entrada = $montado['diagramas']['infra-convites'][$idioma] ?? [];
            expect($entrada)->not->toHaveKey('erro');
            $setas = array_map(static fn (array $seta): string => $seta['from'].'|'.$seta['to'], $entrada['connections'] ?? []);
            $itens = array_merge([], ...array_map(static fn (array $card): array => array_map('strval', $card['items'] ?? []), $entrada['cards'] ?? []));
            $prova = static fn (string $de, string $para): array => array_values(array_filter(
                $itens,
                static fn (string $item): bool => str_starts_with($item, "{$de} → {$para} ("),
            ));

            // a ligação publicada, "Convite usa ConviteDeAcesso", é desenhada nos dois mundos
            expect(in_array("{$idConv}|{$idAviso}", $setas, true))->toBeTrue("infra-convites ({$idioma}): a seta Convite → ConviteDeAcesso, publicada, não foi desenhada");
            expect($prova('Convite', 'ConviteDeAcesso'))->not->toBeEmpty("infra-convites ({$idioma}): o item de prova Convite → ConviteDeAcesso não está no card");

            if ($dosDoisModos) {
                expect(in_array("{$idAviso}|{$idConv}", $setas, true))->toBeTrue("infra-convites ({$idioma}): o gerador não desenhou a seta ConviteDeAcesso → Convite");
                expect($prova('ConviteDeAcesso', 'Convite'))->not->toBeEmpty("infra-convites ({$idioma}): o item de prova ConviteDeAcesso → Convite não está no card");
            } else {
                expect(in_array("{$idAviso}|{$idConv}", $setas, true))->toBeFalse("infra-convites ({$idioma}): o gerador desenhou a seta ConviteDeAcesso → Convite, e a ligação vale só sem tenant");
                expect($prova('ConviteDeAcesso', 'Convite'))->toBe([], "infra-convites ({$idioma}): o gerador desenhou o item de prova ConviteDeAcesso → Convite, e a ligação vale só sem tenant");
            }
        }

        return;
    }

    $nomeia = [];
    switch ($mundoDeControle) {
        case 'só no texto da peça':
            $irs                                                       = mapaDiagramasSemDesenharAPar($irs, $shield['de'], $shield['para']);
            $irs['admin-registro-sem']['nos']['painel:admin']['texto'] = 'o painel admin registra FilamentShieldPlugin';
            $resultado                                                 = mapaDiagramasConferirLigacoes($modelo, $irs, $publicados);
            $nomeia                                                    = ['FilamentShieldPlugin', 'painel:admin'];
            break;
        case 'agrupamento sem membros':
            $soAdmin['admin-resumo-sem']['nos'][$grupo]['nomeados'] = [];
            $resultado                                              = mapaDiagramasConferirLigacoes($isolado, $soAdmin, ['admin-resumo-sem']);
            $nomeia                                                 = [$grupo];
            break;
        case 'membros só no texto secundário':
            $soAdmin['admin-resumo-sem']['nos'][$grupo]['nomeados'] = [];
            $soAdmin['admin-resumo-sem']['nos'][$grupo]['texto']    = implode(', ', array_map(
                static fn (string $id): string => mapaDiagramasNomeDaPeca($modelo, $id),
                $soAdmin['admin-resumo-sem']['nos'][$grupo]['membros'],
            ));
            $resultado = mapaDiagramasConferirLigacoes($isolado, $soAdmin, ['admin-resumo-sem']);
            $nomeia    = [$grupo];
            break;
        case 'porta sem os membros':
            $porta = ['slug' => 'porta', 'tipo' => 'architecture', 'construtor' => 'mapa', 'paineis' => ['admin'], 'modos' => ['sem', 'com'],
                'nos'        => [
                    $ligacaoDoGrupo['de'] => mapaDiagramasNo('peca', 'admin', [$ligacaoDoGrupo['de']]),
                    'porta-outro-mapa'    => mapaDiagramasNo('porta', 'Outro mapa'),
                ],
                'arestas' => [[$ligacaoDoGrupo['de'], 'porta-outro-mapa']]];
            $resultado = mapaDiagramasConferirLigacoes($isolado, ['porta' => $porta], ['porta']);
            $nomeia    = [mapaDiagramasNomeDaPeca($modelo, $ligacaoDoGrupo['para'])];
            break;
        case 'ausente':
            $irs       = mapaDiagramasSemDesenharAPar($irs, $shield['de'], $shield['para']);
            $resultado = mapaDiagramasConferirLigacoes($modelo, $irs, $publicados);
            $nomeia    = ['FilamentShieldPlugin', 'painel:admin'];
            break;
        case 'diagrama órfão':
            $quemDesenha = array_keys(array_filter($irs, static fn (array $ir): bool => isset(mapaDiagramasPares($ir)[$shield['de'].'|'.$shield['para']])));
            expect($quemDesenha)->not->toBeEmpty();
            $resultado = mapaDiagramasConferirLigacoes($modelo, $irs, array_values(array_diff($publicados, $quemDesenha)));
            $nomeia    = ['FilamentShieldPlugin', 'painel:admin'];
            break;
        case 'ponta indisponível no modo do diagrama':
            // A cópia do modelo dá ao TenantResource a condição "só com tenant"; o mundo é a árvore do /admin que vale
            // só sem tenant, e a ligação do registro dele não está em nenhum outro diagrama.
            $tenant      = 'App\\Filament\\Admin\\Resources\\Tenants\\TenantResource';
            $daPonta     = mapaDiagramasLigacaoDe($modelo, 'painel:admin', $tenant, 'registra');
            $semCondicao = $modelo;
            unset($semCondicao['pecas'][$tenant]['disponibilidade']);
            $soAArvore = static function (array $doModelo) use ($daPonta): array {
                $isolado = ['pecas' => $doModelo['pecas'], 'ligacoes' => [$daPonta]];
                $arvore  = ['slug' => 'admin-registro-sem', 'tipo' => 'tree', 'construtor' => 'registro', 'paineis' => ['admin'], 'modos' => ['sem']];

                return mapaDiagramasConferirLigacoes($isolado, ['admin-registro-sem' => mapaDiagramasConstruir($arvore, $isolado)], ['admin-registro-sem']);
            };
            // Controle (R12-M8): sem a condição, a árvore sem tenant desenha a ponta, e a ligação conta como desenhada.
            expect($soAArvore($semCondicao)['motivo'])->toBe('aceita');
            $resultado = $soAArvore(mapaDiagramasModeloComCondicoes($modelo, [$tenant]));
            $nomeia    = ['TenantResource', 'painel:admin'];
            break;
    }

    expect($resultado['aceita'])->toBeFalse();
    foreach ($nomeia as $trecho) {
        expect($resultado['motivo'])->toContain($trecho);
    }
})->with([
    'publicados', 'só no texto da peça', 'agrupamento sem membros', 'membros só no texto secundário', 'porta sem os membros', 'ausente', 'diagrama órfão',
    'ponta indisponível no modo do diagrama', 'ligação de registro de um modo só na árvore compartilhada', 'a mesma ligação, nos dois modos',
    'ligação de um modo só no mapa compartilhado', 'ponta registrada num modo só no mapa compartilhado', 'as duas ligações e as duas pontas, nos dois modos',
]);

/*
|--------------------------------------------------------------------------
| R15 — a página leva a todo diagrama do idioma (CT-15)
|--------------------------------------------------------------------------
*/

it('[CT-15] a página de cada idioma leva, por <a href> relativo, a todo diagrama do seu idioma, e só a diagrama que existe', function (string $idioma): void {
    $slugsDosArquivos = array_keys(diagramasDoMapa());
    $hrefs            = mapaDiagramasHrefsDoMapa(mapaDiagramasPagina($idioma));

    $foraDoPadrao  = array_values(array_filter($hrefs, static fn (string $href): bool => preg_match('#^(?:\.\./)+mapa/'.$idioma.'/[a-z0-9-]+\.html$#', $href) !== 1));
    $slugsDaPagina = [];
    foreach ($hrefs as $href) {
        if (preg_match('#/([a-z0-9-]+)\.html$#', $href, $m) === 1) {
            $slugsDaPagina[$m[1]] = true;
        }
    }
    $slugsDaPagina = array_keys($slugsDaPagina);
    sort($slugsDaPagina);
    sort($slugsDosArquivos);

    expect($hrefs)->not->toBeEmpty()
        ->and($foraDoPadrao)->toBe([])
        ->and(array_values(array_diff($slugsDosArquivos, $slugsDaPagina)))->toBe([])
        ->and(array_values(array_diff($slugsDaPagina, $slugsDosArquivos)))->toBe([])
        ->and($slugsDaPagina)->toBe($slugsDosArquivos);
})->with(['pt', 'en']);

/*
|--------------------------------------------------------------------------
| R16 — textos pt e en (CT-16)
|--------------------------------------------------------------------------
*/

it('[CT-16] todo diagrama tem os seus textos em pt e em en, e o texto en é traduzido, salvo o título que é nome de peça ou de comando, marcado', function (string $mundoDeControle, bool $aceita, array $nomeia): void {
    $modelo    = modeloDoMapaPorId();
    $glossario = mapaDiagramasGlossario();
    $decls     = diagramasDoMapa();

    expect(count($glossario))->toBeGreaterThanOrEqual(39);

    switch ($mundoDeControle) {
        case 'publicados':
            break;
        case 'rótulo sem texto en':
            $decls['login']['participants'][1]['label'] = ['pt' => 'TelaLogin'];
            break;
        case 'título en igual ao pt, sem marca':
            $decls['dois-fatores']['titulo']['en'] = $decls['dois-fatores']['titulo']['pt'];
            break;
        case 'título kit:install igual, marcado':
            $decls = ['kit-install' => [...$decls['kit-install'], 'titulo' => ['pt' => 'kit:install', 'en' => 'kit:install'], 'nomeDeCodigo' => true]];
            break;
        case 'título de fachada marcado':
            $decls = ['kit-install' => [...$decls['kit-install'], 'titulo' => ['pt' => 'Painel admin — sem organização', 'en' => 'Painel admin — sem organização'], 'nomeDeCodigo' => true]];
            break;
        case 'descrição en copiada do pt':
            $decls                                      = ['kit-install' => $decls['kit-install']];
            $decls['kit-install']['nodes'][0]['detail'] = ['pt' => 'Gerando senha do admin', 'en' => 'Gerando senha do admin'];
            break;
        case 'estado Pendente com o rótulo pt':
            $decls = ['convite' => $decls['convite']];
            foreach ($decls['convite']['states'] as $i => $estado) {
                if (mapaDiagramasPt($estado['label']) === 'Pendente') {
                    $decls['convite']['states'][$i]['label']['en'] = 'Pendente';
                }
            }
            break;
        case 'frase com palavra do pt':
            $decls                                      = ['kit-install' => $decls['kit-install']];
            $decls['kit-install']['nodes'][0]['detail'] = ['pt' => 'Gerando senha do admin', 'en' => 'Generating senha do admin'];
            break;
        case 'diagrama de um idioma só':
            $decls     = ['login' => $decls['login']];
            $somentePt = static function (mixed $valor) use (&$somentePt): mixed {
                if (! is_array($valor)) {
                    return $valor;
                }
                if (isset($valor['pt'], $valor['en']) && count($valor) === 2) {
                    return $valor['pt'];
                }

                return array_map($somentePt, $valor);
            };
            $decls['login'] = $somentePt($decls['login']);
            break;
    }

    $resultado = mapaDiagramasConferirTextos($decls, $modelo, $glossario);

    expect($resultado['aceita'])->toBe($aceita, $resultado['motivo']);
    if ($mundoDeControle === 'publicados' || $aceita) {
        expect($resultado['motivo'])->toBe('aceita');

        return;
    }
    foreach ($nomeia as $trecho) {
        expect($resultado['motivo'])->toContain($trecho);
    }
})->with([
    'publicados'                  => ['publicados', true, []],
    'texto en ausente'            => ['rótulo sem texto en', false, ['login']],
    'título copiado'              => ['título en igual ao pt, sem marca', false, ['dois-fatores']],
    'nome de comando'             => ['título kit:install igual, marcado', true, []],
    'marca de fachada'            => ['título de fachada marcado', false, ['kit-install', 'Painel admin — sem organização']],
    'descrição copiada'           => ['descrição en copiada do pt', false, ['kit-install', 'Gerando senha do admin']],
    'termo do glossário sem o en' => ['estado Pendente com o rótulo pt', false, ['convite', 'Pendente']],
    'frase com palavra do pt'     => ['frase com palavra do pt', false, ['kit-install', 'Generating senha do admin']],
    'diagrama de um idioma só'    => ['diagrama de um idioma só', false, ['login']],
]);

/*
|--------------------------------------------------------------------------
| R17 — a grade painel × modo (CT-17, CT-18, CT-43)
|--------------------------------------------------------------------------
*/

it('[CT-17] cada painel do registro tem diagrama próprio em cada modo, e o compartilhado vem marcado', function (string $idioma, string $painel, string $modo): void {
    expect(array_keys(Filament::getPanels()))->toContain($painel);
    $grade  = mapaDiagramasGrade($idioma);
    $falhas = array_filter(
        mapaDiagramasFalhasDaGrade($grade, array_keys(Filament::getPanels()), diagramasDoMapa()),
        static fn (array $falha): bool => $falha['painel'] === $painel && $falha['modo'] === $modo,
    );

    expect($grade)->toHaveKey($painel)
        ->and($grade[$painel][$modo])->not->toBeEmpty()
        ->and(array_column($falhas, 'motivo'))->toBe([]);
})->with(function (): array {
    $linhas = [];
    foreach (['pt', 'en'] as $idioma) {
        foreach (['admin', 'app', 'infra'] as $painel) {
            foreach (['sem', 'com'] as $modo) {
                $linhas["{$idioma} {$painel} {$modo}"] = [$idioma, $painel, $modo];
            }
        }
    }

    return $linhas;
});

it('[CT-18] um painel novo no registro, sem diagrama, deixa a grade incompleta', function (): void {
    painelRegistradoEmTeste('financeiro');
    $paineis = array_keys(Filament::getPanels());
    expect($paineis)->toContain('financeiro');

    $falhas       = mapaDiagramasFalhasDaGrade(mapaDiagramasGrade('pt'), $paineis, diagramasDoMapa());
    $doFinanceiro = array_values(array_filter($falhas, static fn (array $falha): bool => $falha['painel'] === 'financeiro'));
    $dosOutros    = array_values(array_filter($falhas, static fn (array $falha): bool => $falha['painel'] !== 'financeiro'));

    expect(array_column($doFinanceiro, 'modo'))->toEqualCanonicalizing(['sem', 'com'])
        ->and(array_column($dosOutros, 'motivo'))->toBe([])
        ->and(array_values(array_intersect(['admin', 'app', 'infra'], $paineis)))->toBe(['admin', 'app', 'infra']);
});

it('[CT-43] a guarda da grade reprova a célula que leva a diagrama do outro modo ou de outro painel', function (string $mundoDeControle): void {
    $decls   = diagramasDoMapa();
    $paineis = ['admin', 'app', 'infra'];

    if ($mundoDeControle === 'publicada, pt e en') {
        foreach (['pt', 'en'] as $idioma) {
            expect(array_column(mapaDiagramasFalhasDaGrade(mapaDiagramasGrade($idioma), $paineis, $decls), 'motivo'))->toBe([]);
        }

        return;
    }

    $grade    = mapaDiagramasGrade('pt');
    $esperado = [];
    switch ($mundoDeControle) {
        case 'app × com tenant leva ao diagrama que só vale sem tenant':
            $grade['app']['com'][] = ['slug' => 'app-multi-tenancy-opt-in-sem', 'compartilhado' => false];
            $esperado              = ['app × com', 'app-multi-tenancy-opt-in-sem', 'só vale no outro modo'];
            break;
        case 'infra × sem tenant leva ao diagrama do admin':
            $grade['infra']['sem'][] = ['slug' => 'admin-resumo-sem', 'compartilhado' => false];
            $esperado                = ['infra × sem', 'admin-resumo-sem', 'de outro painel'];
            break;
        case 'app × com tenant marca como compartilhado o diagrama que só vale com tenant':
            foreach ($grade['app']['com'] as $i => $item) {
                if ($item['slug'] === 'app-multi-tenancy-opt-in-com') {
                    $grade['app']['com'][$i]['compartilhado'] = true;
                }
            }
            $esperado = ['app × com', 'app-multi-tenancy-opt-in-com', 'vale num modo só'];
            break;
    }
    $motivos = implode(' | ', array_column(mapaDiagramasFalhasDaGrade($grade, $paineis, $decls), 'motivo'));

    expect($motivos)->not->toBe('');
    foreach ($esperado as $trecho) {
        expect($motivos)->toContain($trecho);
    }
})->with([
    'publicada, pt e en',
    'app × com tenant leva ao diagrama que só vale sem tenant',
    'infra × sem tenant leva ao diagrama do admin',
    'app × com tenant marca como compartilhado o diagrama que só vale com tenant',
]);

/*
|--------------------------------------------------------------------------
| R18 — o modo de cada diagrama (CT-19)
|--------------------------------------------------------------------------
*/

it('[CT-19] compartilha-se só o que é igual nos dois modos, e o igual não se duplica', function (string $mundoDeControle): void {
    $mundo         = mapaDiagramasMundo();
    $modelo        = $mundo['modelo'];
    $irs           = $mundo['irs'];
    $definirTenant = 'App\\Http\\Middleware\\DefinirTenantDePermissoes';
    $tenant        = 'App\\Models\\Tenant';

    if ($mundoDeControle === 'publicados') {
        expect(mapaDiagramasConferirModos($modelo, $irs)['motivo'])->toBe('aceita');

        return;
    }

    if (in_array($mundoDeControle, ['raiz das árvores publicadas', 'raiz com os modos noutra ordem', 'raiz de um modo só, controle'], true)) {
        // Forma de leitura da G5: a entrada que o gerador entrega ao archify (Setup Global do `04`, "O diagrama que vale nos
        // dois modos e o rótulo da raiz"). O esperado sai do `modos` do arquivo e da tabela, e a comparação é por igualdade.
        $termoDoModo = static function (array $modos): array {
            sort($modos);

            return match ($modos) {
                ['sem']        => ['pt' => 'sem tenant', 'en' => 'tenancy off'],
                ['com']        => ['pt' => 'com tenant ativo', 'en' => 'tenancy on'],
                ['com', 'sem'] => ['pt' => 'os dois modos', 'en' => 'both modes'],
            };
        };
        $decls = array_filter(diagramasDoMapa(), static fn (array $decl): bool => ($decl['construtor'] ?? null) === 'registro');

        if ($mundoDeControle === 'raiz das árvores publicadas') {
            $pedido  = ['construtor' => 'registro'];
            $arvores = $decls;
        } else {
            $copia          = $decls['infra-registro'];
            $copia['modos'] = $mundoDeControle === 'raiz com os modos noutra ordem' ? ['com', 'sem'] : ['sem'];
            $pedido         = ['extras' => [$copia], 'soExtras' => true];
            $arvores        = ['infra-registro' => $copia];
        }
        $gerado = entradasDoGeradorDoMapa($pedido);

        expect(array_keys($gerado['diagramas']))->toEqualCanonicalizing(array_keys($arvores))
            ->and($arvores)->not->toBeEmpty();
        foreach ($arvores as $slug => $decl) {
            $raiz = $gerado['ids']['painel:'.$decl['paineis'][0]];
            foreach (['pt', 'en'] as $idioma) {
                $entrada = $gerado['diagramas'][$slug][$idioma] ?? [];
                expect($entrada)->not->toHaveKey('erro');
                $nosDaRaiz = array_values(array_filter($entrada['nodes'] ?? [], static fn (array $no): bool => $no['id'] === $raiz));
                expect($nosDaRaiz)->toHaveCount(1, "{$slug} ({$idioma}): a raiz (peça painel) não é um nó só da entrada");
                expect($nosDaRaiz[0]['sublabel'] ?? null)->toBe($termoDoModo($decl['modos'])[$idioma], "{$slug} ({$idioma}): o rótulo secundário da raiz não é o termo do `modos` ".json_encode($decl['modos']));
            }
        }
        if ($mundoDeControle === 'raiz das árvores publicadas') {
            expect($arvores)->toHaveCount(5);
        }

        return;
    }

    if ($mundoDeControle === 'disponibilidade é diferença') {
        // A cópia do modelo dá ao TenantResource a condição "só com tenant", e o /admin troca a árvore compartilhada
        // por duas, uma por modo, que só diferem nele: a de sem tenant não o desenha.
        $tenant      = 'App\\Filament\\Admin\\Resources\\Tenants\\TenantResource';
        $semCondicao = $modelo;
        unset($semCondicao['pecas'][$tenant]['disponibilidade']);
        $comDuasArvores = static function (array $doModelo): array {
            $irs = array_filter(mapaDiagramasMundoCom($doModelo)['irs'], static fn (array $ir): bool => ! ($ir['construtor'] === 'registro' && $ir['paineis'] === ['admin']));
            foreach (['sem', 'com'] as $modo) {
                $irs["admin-registro-{$modo}"] = mapaDiagramasConstruir(['slug' => "admin-registro-{$modo}", 'tipo' => 'tree', 'construtor' => 'registro', 'paineis' => ['admin'], 'modos' => [$modo]], $doModelo);
            }

            return $irs;
        };
        $copia = mapaDiagramasModeloComCondicoes($modelo, [$tenant]);
        $irs   = $comDuasArvores($copia);

        expect(array_key_exists($tenant, mapaDiagramasPecasDesenhadas($irs['admin-registro-com'])))->toBeTrue('a árvore com tenant desenha o TenantResource')
            ->and(array_key_exists($tenant, mapaDiagramasPecasDesenhadas($irs['admin-registro-sem'])))->toBeFalse('a árvore sem tenant não desenha o TenantResource')
            ->and(mapaDiagramasConferirModos($copia, $irs)['motivo'])->toBe('aceita');
        // Controle (R18-M7): sem a condição, as duas árvores saem iguais, e a guarda do igual duplicado as nomeia.
        expect(mapaDiagramasConferirModos($semCondicao, $comDuasArvores($semCondicao))['motivo'])->toContain('admin-registro-sem', 'admin-registro-com');

        return;
    }

    $nomeia = [];
    switch ($mundoDeControle) {
        case 'compartilhado desenha DefinirTenantDePermissoes':
            expect($irs['app-autorizacao']['modos'])->toBe(['sem', 'com']);
            $irs['app-autorizacao']['nos'][$definirTenant] = mapaDiagramasNo('peca', 'DefinirTenantDePermissoes', [$definirTenant]);
            $nomeia                                        = ['app-autorizacao', 'DefinirTenantDePermissoes'];
            break;
        case 'dois diagramas iguais, um por modo':
            $copia                       = $irs['app-resumo-sem'];
            $copia['slug']               = 'app-resumo-sem-copia';
            $copia['modos']              = ['com'];
            $irs['app-resumo-sem-copia'] = $copia;
            $nomeia                      = ['app-resumo-sem', 'app-resumo-sem-copia'];
            break;
        case 'sem tenant desenha DefinirTenantDePermissoes':
            expect($irs['app-resumo-sem']['modos'])->toBe(['sem']);
            $irs['app-resumo-sem']['nos'][$definirTenant] = mapaDiagramasNo('peca', 'DefinirTenantDePermissoes', [$definirTenant]);
            $nomeia                                       = ['app-resumo-sem', 'DefinirTenantDePermissoes'];
            break;
        case 'sem tenant desenha o painel app usa o model Tenant':
            // O modelo da cópia dá a ligação aos dois modos, como o código antigo; quem a prende ao modo com tenant é o código.
            foreach ($modelo['ligacoes'] as $i => $ligacao) {
                if ($ligacao['de'] === 'painel:app' && $ligacao['para'] === $tenant && $ligacao['verbo'] === 'usa') {
                    $modelo['ligacoes'][$i]['modos'] = ['sem', 'com'];
                }
            }
            // O diagrama construído sai do modelo: refeito com a cópia, ele desenha a ligação, como o gerador faria.
            $irs['app-resumo-sem'] = mapaDiagramasConstruir(diagramasDoMapa()['app-resumo-sem'], $modelo);
            $nomeia                = ['app-resumo-sem', 'painel:app', $tenant];
            break;
    }
    $resultado = mapaDiagramasConferirModos($modelo, $irs);

    expect($resultado['aceita'])->toBeFalse();
    foreach ($nomeia as $trecho) {
        expect($resultado['motivo'])->toContain($trecho);
    }
})->with([
    'publicados', 'compartilhado desenha DefinirTenantDePermissoes', 'dois diagramas iguais, um por modo',
    'sem tenant desenha DefinirTenantDePermissoes', 'sem tenant desenha o painel app usa o model Tenant', 'disponibilidade é diferença',
    'raiz das árvores publicadas', 'raiz com os modos noutra ordem', 'raiz de um modo só, controle',
]);

/*
|--------------------------------------------------------------------------
| R19 — tipos, painéis e modos (CT-20, CT-21)
|--------------------------------------------------------------------------
*/

it('[CT-20] cada um dos dez tipos nativos e o comparativo de arquitetura têm diagrama publicado ou o motivo escrito', function (string $tipo): void {
    $decls      = diagramasDoMapa();
    $publicados = array_values(array_intersect(mapaDiagramasPublicados('pt'), mapaDiagramasPublicados('en')));
    $doTipo     = array_filter($publicados, static fn (string $slug): bool => ($decls[$slug]['tipo'] ?? null) === $tipo);

    if ($doTipo !== []) {
        expect($doTipo)->not->toBeEmpty();

        return;
    }
    foreach (['pt', 'en'] as $idioma) {
        $linha = collect(mapaDiagramasMatriz($idioma))->firstWhere('tipo', $tipo);
        expect($linha)->not->toBeNull();
        foreach ($linha['celulas'] as $coluna => $celula) {
            expect(motivoDeEnfeite($celula['motivo']))->toBeFalse("{$idioma}: o motivo da célula {$tipo} × {$coluna} é de enfeite");
        }
    }
})->with(['architecture', 'workflow', 'sequence', 'dataflow', 'lifecycle', 'erd', 'class', 'tree', 'timeline', 'waterfall', 'compare']);

it('[CT-21] toda combinação tipo × painel × modo tem diagrama ou motivo ao lado, e só do conjunto fechado de tipos', function (string $mundoDeControle): void {
    $mundo   = mapaDiagramasMundo();
    $decls   = $mundo['decls'];
    $pt      = mapaDiagramasMatriz('pt');
    $en      = mapaDiagramasMatriz('en');
    $irs     = $mundo['irs'];
    $linhaDe = static fn (array $matriz, string $tipo): int => (int) array_search($tipo, array_column($matriz, 'tipo'), true);

    if ($mundoDeControle === 'publicada, pt e en') {
        expect($pt)->toHaveCount(11)->and($en)->toHaveCount(11);
        expect(mapaDiagramasConferirMatriz($pt, $decls, 'pt', null, $irs, $mundo['modelo'])['motivo'])->toBe('aceita');
        expect(mapaDiagramasConferirMatriz($en, $decls, 'en', $pt, $irs, $mundo['modelo'])['motivo'])->toBe('aceita');

        return;
    }

    $idioma = 'pt';
    $matriz = $pt;
    $nomeia = [];
    switch ($mundoDeControle) {
        case 'waterfall × admin × com tenant sem diagrama e sem motivo':
            $matriz[$linhaDe($matriz, 'waterfall')]['celulas']['admin|com'] = ['itens' => [], 'motivo' => ''];
            $nomeia                                                         = ['waterfall × admin × com'];
            break;
        case 'motivo "—" em comparativo × infra':
            $matriz[$linhaDe($matriz, 'compare')]['celulas']['infra|sem'] = ['itens' => [], 'motivo' => '—'];
            $nomeia                                                       = ['compare × infra × sem'];
            break;
        case 'motivo "não se aplica" em erd × app × com tenant':
            $matriz[$linhaDe($matriz, 'erd')]['celulas']['app|com'] = ['itens' => [], 'motivo' => 'não se aplica'];
            $nomeia                                                 = ['erd × app × com'];
            break;
        case 'página en com o motivo pt copiado':
            $idioma                                                       = 'en';
            $matriz                                                       = $en;
            $motivoPt                                                     = $pt[$linhaDe($pt, 'compare')]['celulas']['infra|sem']['motivo'];
            $matriz[$linhaDe($matriz, 'compare')]['celulas']['infra|sem'] = ['itens' => [], 'motivo' => $motivoPt];
            $nomeia                                                       = ['compare × infra × sem'];
            break;
        case 'página en com frase pt de palavra trocada':
            $idioma                                                        = 'en';
            $matriz                                                        = $en;
            $matriz[$linhaDe($matriz, 'dataflow')]['celulas']['admin|sem'] = ['itens' => [], 'motivo' => 'no flow de dados in this panel'];
            $nomeia                                                        = ['dataflow × admin × sem'];
            break;
        case 'workflow do app declarado do kit inteiro':
            $irs['kit-install']['nos']['App\\Filament\\Pages\\Auth\\TelaLogin'] = mapaDiagramasNo('peca', 'TelaLogin', ['App\\Filament\\Pages\\Auth\\TelaLogin']);
            $nomeia                                                             = ['kit-install', 'TelaLogin'];
            break;
        case 'sequence × admin × sem leva a um workflow':
            $matriz[$linhaDe($matriz, 'sequence')]['celulas']['admin|sem']['itens'][] = ['slug' => 'kit-install', 'compartilhado' => false];
            $nomeia                                                                   = ['sequence × admin × sem', 'workflow'];
            break;
        case 'sequence × admin × sem leva ao sequence do app':
            $matriz[$linhaDe($matriz, 'sequence')]['celulas']['admin|sem']['itens'][] = ['slug' => 'app-chat-de-ia', 'compartilhado' => false];
            $nomeia                                                                   = ['sequence × admin × sem', 'app-chat-de-ia'];
            break;
        case 'tipo mindmap na página':
            $matriz[] = ['tipo' => 'mindmap', 'celulas' => $matriz[0]['celulas']];
            $nomeia   = ['mindmap'];
            break;
    }
    $resultado = mapaDiagramasConferirMatriz($matriz, $decls, $idioma, $pt, $irs, $mundo['modelo']);

    expect($resultado['aceita'])->toBeFalse();
    foreach ($nomeia as $trecho) {
        expect($resultado['motivo'])->toContain($trecho);
    }
})->with([
    'publicada, pt e en',
    'waterfall × admin × com tenant sem diagrama e sem motivo',
    'motivo "—" em comparativo × infra',
    'motivo "não se aplica" em erd × app × com tenant',
    'página en com o motivo pt copiado',
    'página en com frase pt de palavra trocada',
    'workflow do app declarado do kit inteiro',
    'sequence × admin × sem leva a um workflow',
    'sequence × admin × sem leva ao sequence do app',
    'tipo mindmap na página',
]);

/*
|--------------------------------------------------------------------------
| R20 — o comparativo (CT-22)
|--------------------------------------------------------------------------
*/

it('[CT-22] todo comparativo publicado vai do modo sem tenant para o com tenant do mesmo painel', function (string $mundoDeControle, bool $aceita): void {
    $decls       = diagramasDoMapa();
    $publicados  = array_values(array_intersect(mapaDiagramasPublicados('pt'), mapaDiagramasPublicados('en')));
    $comparativo = 'app-resumo-sem-com';

    switch ($mundoDeControle) {
        case 'publicados':
            expect($decls[$comparativo]['base'])->toBe('app-resumo-sem');
            break;
        case 'app de com para sem':
            [$decls[$comparativo]['base'], $decls[$comparativo]['cabeca']] = [$decls[$comparativo]['cabeca'], $decls[$comparativo]['base']];
            break;
        case 'admin sem para app com':
            expect($decls['admin-resumo-sem']['paineis'] ?? null)->toBe(['admin']);
            $decls[$comparativo]['base'] = 'admin-resumo-sem';
            break;
        case 'base que não é arquivo de diagrama':
            $decls[$comparativo]['base'] = 'nao-existe-no-mapa';
            break;
    }
    $resultado = mapaDiagramasConferirComparativos($decls, $publicados);

    expect($resultado['comparativos'])->toBeGreaterThan(0)
        ->and($resultado['aceita'])->toBe($aceita, $resultado['motivo']);
    if (! $aceita) {
        // cada cópia reprova pela partição dela, e não por outra que a cópia também tivesse
        $particao = match ($mundoDeControle) {
            'app de com para sem'                => 'não vai do modo sem tenant',
            'admin sem para app com'             => 'mistura painéis',
            'base que não é arquivo de diagrama' => 'não é arquivo de diagrama do mapa',
        };
        expect($resultado['motivo'])->toContain($comparativo, $particao);
    }
})->with([
    'publicados'             => ['publicados', true],
    'de com para sem'        => ['app de com para sem', false],
    'admin sem para app com' => ['admin sem para app com', false],
    'base fora do mapa'      => ['base que não é arquivo de diagrama', false],
]);

/*
|--------------------------------------------------------------------------
| R22 — fluxos (CT-24)
|--------------------------------------------------------------------------
*/

it('[CT-24] o mapa publica fluxos, e todo passo deles percorre peça do modelo', function (string $mundoDeControle): void {
    $modelo = modeloDoMapaPorId();
    $decls  = diagramasDoMapa();

    if ($mundoDeControle === 'publicados') {
        expect(mapaDiagramasConferirFluxos($decls, $modelo)['motivo'])->toBe('aceita');

        return;
    }
    if ($mundoDeControle === 'sem nenhum diagrama de fluxo') {
        $decls     = array_filter($decls, static fn (array $decl): bool => ! in_array($decl['tipo'], ['workflow', 'sequence', 'dataflow', 'lifecycle'], true));
        $resultado = mapaDiagramasConferirFluxos($decls, $modelo);
        expect($resultado['aceita'])->toBeFalse()
            ->and($resultado['motivo'])->toContain('nenhum fluxo publicado');

        return;
    }
    if ($mundoDeControle === 'ponta de passo entre depósitos vira backend que nomeia ConviteService') {
        $copia = $decls['configuracoes-config'];
        expect($copia['tipo'])->toBe('dataflow');
        $tipoDoNo = array_column($copia['nodes'], 'type', 'id');
        $indice   = null;
        foreach ($copia['flows'] as $i => $fluxo) {
            if (($tipoDoNo[$fluxo['from']] ?? null) === 'database' && ($tipoDoNo[$fluxo['to']] ?? null) === 'database') {
                $indice = $i;

                break;
            }
        }
        expect($indice)->not->toBeNull();
        $copia['nodes'][]              = ['id' => 'convite-service', 'type' => 'backend', 'label' => ['pt' => 'ConviteService', 'en' => 'ConviteService']];
        $copia['flows'][$indice]['to'] = 'convite-service';
        $resultado                     = mapaDiagramasConferirFluxos(['configuracoes-config' => $copia], $modelo);

        expect($resultado['aceita'])->toBeFalse()
            ->and($resultado['motivo'])->toContain('configuracoes-config')
            ->and($resultado['motivo'])->toContain((string) $copia['flows'][$indice]['id']);

        return;
    }
    $decls                           = ['kit-install' => $decls['kit-install']];
    $decls['kit-install']['nodes'][] = ['id' => 'convite-service', 'lane' => 'handle', 'col' => 5, 'type' => 'backend', 'label' => ['pt' => 'ConviteService', 'en' => 'ConviteService']];
    $decls['kit-install']['edges'][] = [
        'id'    => 'env-convite-service', 'from' => 'env', 'to' => 'convite-service',
        'prova' => ['arquivo' => 'app/Services/ConviteService.php', 'trecho' => 'class ConviteService'],
    ];
    $resultado = mapaDiagramasConferirFluxos($decls, $modelo);

    expect($resultado['aceita'])->toBeFalse()
        ->and($resultado['motivo'])->toContain('kit-install')
        ->and($resultado['motivo'])->toContain('env-convite-service');
})->with(['publicados', 'sem nenhum diagrama de fluxo', 'passo que nomeia ConviteService', 'ponta de passo entre depósitos vira backend que nomeia ConviteService']);

/*
|--------------------------------------------------------------------------
| R29 — CHANGELOG e tempos (CT-31)
|--------------------------------------------------------------------------
*/

it('[CT-31] cada marco da linha do tempo cita versão do CHANGELOG com a data dela e um termo da seção dela, e cada tempo medido traz data e fonte', function (string $mundoDeControle, array $nomeia): void {
    $decls        = diagramasDoMapa();
    $secoes       = mapaDiagramasSecoesDoChangelog();
    $marcos       = 'marcos-do-changelog';
    $primeiro     = array_key_first($decls[$marcos]['events']);
    $indiceDaV056 = null;
    foreach ($decls[$marcos]['events'] as $i => $evento) {
        if (str_starts_with(mapaDiagramasPt($evento['title']), '0.56.0')) {
            $indiceDaV056 = $i;
        }
    }
    expect($indiceDaV056)->not->toBeNull()
        ->and($secoes['0.56.0']['data'])->toBe('2026-11-19')
        ->and($secoes)->toHaveKey('Unreleased');

    if ($mundoDeControle === 'publicados') {
        $resultado = mapaDiagramasConferirMarcosETempos($decls, $secoes);
        expect($resultado['marcos'])->toBeGreaterThan(0)
            ->and($resultado['tempos'])->toBeGreaterThan(0)
            ->and($resultado['motivo'])->toBe('aceita');

        return;
    }

    $decls   = [$marcos => $decls[$marcos], 'tempos-ci' => diagramasDoMapa()['tempos-ci']];
    $eventos = &$decls[$marcos]['events'];
    switch ($mundoDeControle) {
        case 'versão inexistente':
            $eventos[$primeiro]['title'] = ['pt' => '0.99.0 · Versão que não existe', 'en' => '0.99.0 · Version that does not exist'];
            break;
        case 'data trocada':
            $eventos[$indiceDaV056]['at'] = '2026-11-18T12:00:00-03:00';
            break;
        case 'título que a seção não traz':
            $eventos[$indiceDaV056]['title'] = ['pt' => '0.56.0 · Passkeys', 'en' => '0.56.0 · Passkeys'];
            break;
        case 'marco do Unreleased':
            $eventos[$primeiro]['title'] = ['pt' => 'Unreleased · Mudanças sem versão', 'en' => 'Unreleased · Changes without version'];
            break;
        case 'tempo sem a fonte':
            $decls['tempos-ci']['spans'][0]['detail'] = ['pt' => 'Das 10h46min19s às 10h46min59s. 19/11/2026.', 'en' => 'From 10:46:19 to 10:46:59. 19/11/2026.'];
            break;
        case 'tempo sem a data':
            $decls['tempos-ci']['spans'][0]['detail'] = ['pt' => 'Run 44536758392 do CI, API do GitHub Actions.', 'en' => 'CI run 44536758392, GitHub Actions API.'];
            break;
    }
    unset($eventos);
    $resultado = mapaDiagramasConferirMarcosETempos($decls, $secoes);

    expect($resultado['aceita'])->toBeFalse();
    foreach ($nomeia as $trecho) {
        expect($resultado['motivo'])->toContain($trecho);
    }
})->with([
    'publicados'                  => ['publicados', []],
    'versão inexistente'          => ['versão inexistente', ['0.99.0']],
    'data trocada'                => ['data trocada', ['0.56.0', '2026-11-18']],
    'título que a seção não traz' => ['título que a seção não traz', ['0.56.0', 'Passkeys']],
    'versão sem data'             => ['marco do Unreleased', ['Unreleased', 'sem data']],
    'tempo sem a fonte'           => ['tempo sem a fonte', ['instalacao', 'fonte da medição']],
    'tempo sem a data'            => ['tempo sem a data', ['instalacao', 'data da medição']],
]);

/*
|--------------------------------------------------------------------------
| R34 — sem tenant, o registro do painel (CT-37)
|--------------------------------------------------------------------------
*/

it('[CT-37] sem tenant, os diagramas de arquitetura e a árvore de registro de cada painel desenham o registro dele nesse modo, e nada além', function (string $painel, string $mundoDeControle): void {
    expect(config('kit.tenancy.enabled'))->toBeFalse();
    $viva = Filament::getPanel($painel);
    expect($viva->hasTenancy())->toBeFalse();

    $mundo      = mapaDiagramasMundo();
    $modelo     = $mundo['modelo'];
    $vivo       = array_merge(...array_values(registroVivoDoPainel($viva)));
    $subtraidas = mapaDiagramasSubtracaoSemTenant($painel, $modelo);
    $tenant     = 'App\\Filament\\Admin\\Resources\\Tenants\\TenantResource';
    $agente     = 'App\\Filament\\Admin\\Resources\\AgentesIa\\AgenteIaResource';
    $command    = 'Bityukov\\CommandCenter\\Filament\\Resources\\CommandRecordResource';

    if ($mundoDeControle === 'publicados') {
        $resultado = mapaDiagramasConferirRegistro($modelo, $mundo['irs'], $painel, 'sem', $vivo, $subtraidas);
        expect($resultado['registro'])->toBeGreaterThan(0);
        if ($painel === 'admin') {
            expect($subtraidas)->toContain($tenant)
                ->and($subtraidas)->not->toContain($agente);
        }
        if ($painel === 'app') {
            expect($vivo)->not->toContain('App\\Http\\Middleware\\DefinirTenantDePermissoes')
                ->and($vivo)->not->toContain('Filament\\Http\\Middleware\\IdentifyTenant')
                ->and($subtraidas)->toContain('App\\Filament\\App\\Resources\\Users\\UserResource', 'App\\Filament\\App\\Resources\\Convites\\ConviteResource', 'App\\Filament\\App\\Resources\\Projetos\\ProjetoResource', 'App\\Filament\\App\\Pages\\ConvitesRecebidos')
                ->and($subtraidas)->not->toContain('App\\Filament\\App\\Pages\\HubDoNegocio');
        }
        if ($painel === 'infra') {
            expect($vivo)->toContain('ShuvroRoy\\FilamentSpatieLaravelHealth\\Pages\\HealthCheckResults', $command);
        }
        expect($resultado['motivo'])->toBe('aceita');
        if ($painel === 'infra') {
            expect($subtraidas)->not->toContain($command);
        }

        return;
    }

    switch ($mundoDeControle) {
        case 'cópia em que a árvore sem tenant desenha o TenantResource':
            // Um mundo de uma árvore só: a do /admin que vale sem tenant, com todo o registro do modelo.
            $copia  = $modelo;
            $arvore = mapaDiagramasConstruir(['slug' => 'admin-registro-sem', 'tipo' => 'tree', 'construtor' => 'registro', 'paineis' => ['admin'], 'modos' => ['sem']], $modelo);
            unset($arvore['nos'][$tenant]);
            $arvore['arestas'] = array_values(array_filter($arvore['arestas'], static fn (array $aresta): bool => $aresta[1] !== $tenant));
            // Controle positivo: sem o TenantResource, a árvore desenha o registro sem tenant, nem mais nem menos.
            expect(mapaDiagramasConferirRegistro($copia, [$arvore], $painel, 'sem', $vivo, $subtraidas)['motivo'])->toBe('aceita');
            $irs  = [mapaDiagramasComoNo([...$arvore, 'arestas' => [...$arvore['arestas'], ['painel:admin', $tenant]]], $modelo, $tenant)];
            $alvo = $tenant;
            break;
        case 'cópia sem o AgenteIaResource':
            expect($subtraidas)->not->toContain($agente);
            $copia = mapaDiagramasSemALigacao($modelo, 'painel:admin', $agente);
            $irs   = mapaDiagramasMundoCom($copia)['irs'];
            $alvo  = $agente;
            break;
        case 'cópia sem o Dashboard do /admin':
            $dashboard = 'App\\Filament\\Admin\\Pages\\Dashboard';
            expect($subtraidas)->not->toContain($dashboard);
            $copia = mapaDiagramasSemALigacao($modelo, 'painel:admin', $dashboard);
            $irs   = mapaDiagramasMundoCom($copia)['irs'];
            $alvo  = $dashboard;
            break;
        default:
            $alvo  = mapaDiagramasIdTerminadoEm($modelo, 'FilamentShieldPlugin');
            $copia = mapaDiagramasSemALigacao($modelo, 'painel:admin', $alvo);
            $irs   = mapaDiagramasMundoCom($copia)['irs'];
    }
    $resultado = mapaDiagramasConferirRegistro($copia, $irs, $painel, 'sem', $vivo, $subtraidas);

    expect($vivo)->toContain($alvo)
        ->and($resultado['aceita'])->toBeFalse()
        ->and($resultado['motivo'])->toContain('admin', mapaDiagramasNomeDaPeca($modelo, $alvo));
})->with([
    'admin publicados'                                => ['admin', 'publicados'],
    'app publicados'                                  => ['app', 'publicados'],
    'infra publicados'                                => ['infra', 'publicados'],
    'admin sem o Shield em nenhum desenho'            => ['admin', 'cópia sem o FilamentShieldPlugin'],
    'admin com o TenantResource na árvore sem tenant' => ['admin', 'cópia em que a árvore sem tenant desenha o TenantResource'],
    'admin sem o AgenteIaResource em nenhum desenho'  => ['admin', 'cópia sem o AgenteIaResource'],
    'admin sem o Dashboard em nenhum desenho'         => ['admin', 'cópia sem o Dashboard do /admin'],
]);

/*
|--------------------------------------------------------------------------
| R38 — o traço da seta e a caixa de agrupamento do mapa de arquitetura (CT-46, CT-47)
|--------------------------------------------------------------------------
|
| Costura G5, forma de leitura: `entradasDoGeradorDoMapa()`, de `tests/Pest.php`, roda um `node` filho que
| importa `site/mapa/gerar.mjs` e devolve, mapa a mapa e idioma a idioma, as caixas e as setas da entrada que
| `entradaDoArchify()` entrega ao archify, sem baixar o archify nem renderizar. A cópia em memória do modelo é a
| que `carregarModelo()` devolve, alterada no filho antes da montagem; a do arquivo do diagrama é um agrupamento
| a mais na cópia que o filho lê. A falha do filho reprova por expectativa, uma vez por pedido. A guarda acha as
| ligações de cada par pela regra de desenho da R12 (`mapaDiagramasConstruir()`), e o traço de cada verbo vem da
| legenda do `04`.
*/

/**
 * O traço de cada verbo, como a legenda "Como ler as setas" o dá no `04` (R38): o oráculo, e não a tabela do gerador.
 *
 * @return array<string, string>
 */
function mapaDiagramasTracoDoVerbo(): array
{
    return ['registra' => 'emphasis', 'usa' => 'default', 'lê' => 'dashed', 'grava' => 'security'];
}

/**
 * Os mapas de arquitetura construídos: os arquivos de `site/mapa/diagramas/` com o construtor `mapa`. A pasta é o
 * manifesto, e o piso de não vácuo vem dela, e não do processo filho.
 *
 * @return array<string, array<string, mixed>>
 */
function mapaDiagramasMapasConstruidos(): array
{
    return array_filter(diagramasDoMapa(), static fn (array $decl): bool => ($decl['construtor'] ?? null) === 'mapa');
}

/**
 * Os verbos das ligações do modelo que caem em cada par de pontas desenhadas de um mapa construído, pela regra de
 * desenho da R12: a peça, ou o agrupamento que a nomeia, no painel e no modo do diagrama — as ligações que
 * `mapaDiagramasConstruir()` anota como representadas. O par vem nos ids do archify: "origem → destino".
 *
 * @param  array<string, string>  $ids  id do modelo => id do archify
 * @return array<string, array<string, true>>
 */
function mapaDiagramasVerbosPorPar(array $ir, array $ids): array
{
    $grupoDe = [];
    foreach ($ir['nos'] as $no => $dado) {
        if ($dado['tipo'] === 'grupo') {
            foreach ($dado['membros'] as $membro) {
                $grupoDe[$membro] = $no;
            }
        }
    }
    $ponta = static fn (string $id): string => ($ir['nos'][$id]['tipo'] ?? null) === 'peca' ? ($ids[$id] ?? $id) : ($grupoDe[$id] ?? '?'.$id);
    $pares = [];
    foreach ($ir['representadas'] as $chave) {
        [$de, $para, $verbo]                            = explode('|', $chave);
        $pares[$ponta($de).' → '.$ponta($para)][$verbo] = true;
    }

    return $pares;
}

/**
 * A guarda da seta (R38, CT-46): em cada mapa construído e em cada idioma, os traços das setas de cada par de
 * pontas desenhadas são os dos verbos das ligações do modelo que caem nele — um por verbo, sem faltar, sobrar
 * nem repetir. O diagrama que o gerador recusa reprova.
 *
 * @param  array<string, array<string, mixed>>  $decls  os mapas conferidos, por slug
 * @return array{aceita: bool, motivo: string, falhas: list<array{mapa: string, par: string, verbo: ?string, texto: string}>, mapas: int, paresDeDoisVerbos: int}
 */
function mapaDiagramasConferirSetas(array $decls, array $modelo, array $gerado): array
{
    $tracoDoVerbo      = mapaDiagramasTracoDoVerbo();
    $falhas            = [];
    $mapas             = 0;
    $paresDeDoisVerbos = 0;
    foreach ($decls as $slug => $decl) {
        $esperado = mapaDiagramasVerbosPorPar(mapaDiagramasConstruir($decl, $modelo), $gerado['ids']);
        $paresDeDoisVerbos += count(array_filter($esperado, static fn (array $verbos): bool => count($verbos) > 1));
        $conferido = false;
        foreach (['pt', 'en'] as $idioma) {
            $entrada = $gerado['diagramas'][$slug][$idioma] ?? ['erro' => 'o gerador não montou o diagrama'];
            if (isset($entrada['erro'])) {
                $falhas["{$slug}|erro"] = ['mapa' => $slug, 'par' => '', 'verbo' => null, 'texto' => "{$slug} ({$idioma}): {$entrada['erro']}"];

                continue;
            }
            $conferido = true;
            $tracos    = [];
            foreach ($entrada['connections'] as $seta) {
                $tracos[$seta['from'].' → '.$seta['to']][] = (string) $seta['variant'];
            }
            foreach (array_unique([...array_keys($esperado), ...array_keys($tracos)]) as $par) {
                $verbos  = array_map('strval', array_keys($esperado[$par] ?? []));
                $setas   = $tracos[$par] ?? [];
                $devidos = array_map(static fn (string $verbo): string => $tracoDoVerbo[$verbo] ?? "?{$verbo}", $verbos);
                foreach ($verbos as $indice => $verbo) {
                    if (! in_array($devidos[$indice], $setas, true)) {
                        $falhas["{$slug}|{$par}|{$verbo}"] = ['mapa' => $slug, 'par' => $par, 'verbo' => $verbo,
                            'texto'                                  => "{$slug}: o par {$par} tem a ligação \"{$verbo}\" sem a seta dela (traço {$devidos[$indice]}); as setas do par: [".implode(', ', $setas).']'];
                    }
                }
                foreach (array_count_values($setas) as $traco => $quantas) {
                    if ($quantas > 1 || ! in_array((string) $traco, $devidos, true)) {
                        $falhas["{$slug}|{$par}|traço {$traco}"] = ['mapa' => $slug, 'par' => $par, 'verbo' => null,
                            'texto'                                        => "{$slug}: o par {$par} tem {$quantas} seta(s) com o traço {$traco}, e as ligações dele são [".implode(', ', $verbos).']'];
                    }
                }
            }
        }
        $mapas += $conferido ? 1 : 0;
    }
    $lista = array_values($falhas);
    if ($lista === []) {
        return ['aceita' => true, 'motivo' => 'aceita', 'falhas' => [], 'mapas' => $mapas, 'paresDeDoisVerbos' => $paresDeDoisVerbos];
    }
    $pares        = array_values(array_unique(array_map(static fn (array $falha): string => $falha['mapa'].'|'.$falha['par'], $lista)));
    $paresPorMapa = array_count_values(array_map(static fn (string $par): string => explode('|', $par)[0], $pares));
    $motivo       = sprintf(
        'reprova: %d falhas em %d pares de %d mapas — %s. Ex.: %s',
        count($lista),
        count($pares),
        count($paresPorMapa),
        implode(', ', array_map(static fn (string $mapa, int $quantos): string => "{$mapa} ({$quantos})", array_keys($paresPorMapa), $paresPorMapa)),
        $lista[0]['texto'],
    );

    return ['aceita' => false, 'motivo' => $motivo, 'falhas' => $lista, 'mapas' => $mapas, 'paresDeDoisVerbos' => $paresDeDoisVerbos];
}

/**
 * A guarda da caixa (R38, CT-47): o rótulo secundário de cada caixa de agrupamento diz quantas peças ela nomeia no
 * modo do diagrama — depois do filtro de modo —, com a palavra no número certo; a caixa que o filtro esvazia não
 * é desenhada, ou o gerador recusa o diagrama nomeando o agrupamento.
 *
 * @param  array<string, array<string, mixed>>  $decls  os mapas conferidos, por slug
 * @return array{aceita: bool, motivo: string, falhas: list<array{mapa: string, caixa: string, texto: string}>, mapas: int, caixas: int}
 */
function mapaDiagramasConferirCaixas(array $decls, array $modelo, array $gerado): array
{
    $falhas = [];
    $mapas  = 0;
    $caixas = 0;
    foreach ($decls as $slug => $decl) {
        $ir      = mapaDiagramasConstruir($decl, $modelo);
        $membros = [];
        $nomes   = [];
        foreach ($decl['agrupamentos'] ?? [] as $grupo) {
            $membros[$grupo['id']] = count($ir['nos'][$grupo['id']]['membros'] ?? []);
            $nomes[$grupo['id']]   = [$grupo['id'], mapaDiagramasPt($grupo['rotulo'] ?? ''), mapaDiagramasEn($grupo['rotulo'] ?? '')];
        }
        $caixas += count($membros);
        $mapas++;
        foreach (['pt', 'en'] as $idioma) {
            $entrada = $gerado['diagramas'][$slug][$idioma] ?? ['erro' => 'o gerador não montou o diagrama'];
            if (isset($entrada['erro'])) {
                $nomeiaVazia = array_filter(
                    array_keys(array_filter($membros, static fn (int $quantos): bool => $quantos === 0)),
                    static fn (string $id): bool => array_filter($nomes[$id], static fn (string $nome): bool => $nome !== '' && str_contains($entrada['erro'], $nome)) !== [],
                );
                if ($nomeiaVazia === []) {
                    $falhas[] = ['mapa' => $slug, 'caixa' => '', 'texto' => "{$slug} ({$idioma}): o gerador recusou o diagrama sem nomear agrupamento vazio — {$entrada['erro']}"];
                }

                continue;
            }
            $desenhadas = array_column($entrada['components'], 'sublabel', 'id');
            foreach ($membros as $id => $quantos) {
                $id = (string) $id;
                if ($quantos === 0) {
                    if (array_key_exists($id, $desenhadas)) {
                        $falhas[] = ['mapa' => $slug, 'caixa' => $id, 'texto' => "{$slug} ({$idioma}): a caixa {$id} não nomeia peça nenhuma no modo do diagrama e saiu desenhada, com \"{$desenhadas[$id]}\""];
                    }

                    continue;
                }
                $devido = $idioma === 'pt' ? ($quantos === 1 ? '1 peça' : "{$quantos} peças") : ($quantos === 1 ? '1 piece' : "{$quantos} pieces");
                $diz    = array_key_exists($id, $desenhadas) ? '"'.$desenhadas[$id].'"' : 'nada (não foi desenhada)';
                if (($desenhadas[$id] ?? null) !== $devido) {
                    $falhas[] = ['mapa' => $slug, 'caixa' => $id, 'texto' => "{$slug} ({$idioma}): a caixa {$id} diz {$diz}, e nomeia {$quantos} no modo do diagrama: \"{$devido}\""];
                }
            }
        }
    }

    return [
        'aceita' => $falhas === [],
        'motivo' => $falhas === [] ? 'aceita' : 'reprova: '.implode('; ', array_column($falhas, 'texto')),
        'falhas' => $falhas,
        'mapas'  => $mapas,
        'caixas' => $caixas,
    ];
}

it('[CT-46] cada seta do mapa de arquitetura leva ligações de um verbo só, com o traço desse verbo, e cada par de pontas tem uma seta por verbo', function (string $mundo): void {
    expect(versaoDoNode())->toMatch('/^v\d+\.\d+\.\d+/');

    $modelo  = modeloDoMapaPorId();
    $decls   = mapaDiagramasMapasConstruidos();
    $slug    = 'app-acesso-e-autenticacao-com';
    $recurso = 'App\Filament\App\Resources\Users\UserResource';
    $user    = 'App\Models\User';
    expect(array_key_exists($recurso, $modelo['pecas']) && array_key_exists($user, $modelo['pecas']))->toBeTrue('o UserResource do /app e o User são peças do modelo')
        ->and(array_key_exists($slug, $decls))->toBeTrue("{$slug} é mapa construído");

    if ($mundo === 'publicados') {
        $gerado    = entradasDoGeradorDoMapa(['construtor' => 'mapa', 'soCaixasESetas' => true]);
        $resultado = mapaDiagramasConferirSetas($decls, $modelo, $gerado);

        expect(count($decls))->toBeGreaterThan(0)
            ->and(array_keys($gerado['diagramas']))->toEqualCanonicalizing(array_keys($decls))
            ->and($resultado['mapas'])->toBe(count($decls))
            ->and($resultado['paresDeDoisVerbos'])->toBeGreaterThanOrEqual(1)
            ->and($resultado['motivo'])->toBe('aceita');

        return;
    }

    if ($mundo === 'três verbos num par') {
        $grava               = [...mapaDiagramasLigacaoDe($modelo, $recurso, $user, 'lê'), 'verbo' => 'grava'];
        $copia               = $modelo;
        $copia['ligacoes'][] = $grava;
        $gerado              = entradasDoGeradorDoMapa(['construtor' => 'mapa', 'soCaixasESetas' => true, 'ligacoes' => [$grava]]);
        $par                 = $gerado['ids'][$recurso].' → '.$gerado['ids'][$user];
        $verbos              = array_keys(mapaDiagramasVerbosPorPar(mapaDiagramasConstruir($decls[$slug], $copia), $gerado['ids'])[$par] ?? []);

        expect($verbos)->toEqualCanonicalizing(['lê', 'usa', 'grava']);
        foreach (['pt', 'en'] as $idioma) {
            $tracos = array_column(array_filter(
                $gerado['diagramas'][$slug][$idioma]['connections'] ?? [],
                static fn (array $seta): bool => $par === $seta['from'].' → '.$seta['to'],
            ), 'variant');
            sort($tracos);
            expect($tracos)->toBe(['dashed', 'default', 'security'], "{$slug} ({$idioma}): o par {$par} tem lê, usa e grava, e deveria ter três setas, uma por verbo, com três traços");
        }
        expect(mapaDiagramasConferirSetas($decls, $copia, $gerado)['motivo'])->toBe('aceita');

        return;
    }

    // As duas linhas que adulteram a entrada do mapa: todas as setas do par saem, achadas pelas pontas, e entra o estado-alvo.
    $gerado = entradasDoGeradorDoMapa(['construtor' => 'mapa', 'soCaixasESetas' => true]);
    $de     = $gerado['ids'][$recurso];
    $para   = $gerado['ids'][$user];
    $par    = "{$de} → {$para}";
    expect(array_keys(mapaDiagramasVerbosPorPar(mapaDiagramasConstruir($decls[$slug], $modelo), $gerado['ids'])[$par] ?? []))->toEqualCanonicalizing(['lê', 'usa']);

    $seta          = static fn (string $sufixo, string $traco): array => ['id' => "{$de}--{$para}{$sufixo}", 'from' => $de, 'to' => $para, 'variant' => $traco];
    $comSetasNoPar = static function (array $gerado, array $setas) use ($slug, $de, $para): array {
        foreach (['pt', 'en'] as $idioma) {
            $outras = array_filter(
                $gerado['diagramas'][$slug][$idioma]['connections'],
                static fn (array $seta): bool => ! ($seta['from'] === $de && $seta['to'] === $para),
            );
            $gerado['diagramas'][$slug][$idioma]['connections'] = [...array_values($outras), ...$setas];
        }

        return $gerado;
    };

    // Controle positivo: com uma seta por verbo, cada uma com o seu traço, a guarda não acusa o par — o veredito abaixo vem da adulteração.
    $certo = mapaDiagramasConferirSetas([$slug => $decls[$slug]], $modelo, $comSetasNoPar($gerado, [$seta('--le', 'dashed'), $seta('--usa', 'default')]));
    expect(array_values(array_filter($certo['falhas'], static fn (array $falha): bool => $falha['par'] === $par)))->toBe([]);

    $alvo = $mundo === 'par sem a seta de um dos verbos'
        ? [$seta('', 'dashed')]
        : [$seta('--le', 'dashed'), $seta('--usa', 'dashed')];
    $resultado = mapaDiagramasConferirSetas([$slug => $decls[$slug]], $modelo, $comSetasNoPar($gerado, $alvo));
    $doPar     = array_values(array_filter($resultado['falhas'], static fn (array $falha): bool => $falha['mapa'] === $slug && $falha['par'] === $par));

    expect($resultado['aceita'])->toBeFalse()
        ->and($doPar)->not->toBeEmpty();

    if ($mundo === 'par sem a seta de um dos verbos') {
        $semSeta = array_values(array_filter($doPar, static fn (array $falha): bool => $falha['verbo'] === 'usa'));
        expect($semSeta)->toHaveCount(1)
            ->and($semSeta[0]['texto'])->toContain($slug, $de, $para, '"usa"');

        return;
    }

    expect(implode("\n", array_column($doPar, 'texto')))->toContain($slug, $de, $para);
})->with(['publicados', 'par sem a seta de um dos verbos', 'seta com o traço de outro verbo', 'três verbos num par']);

it('[CT-47] a caixa de agrupamento diz quantas peças nomeia no modo do diagrama, no número certo, e a caixa vazia não é desenhada', function (string $mundo): void {
    expect(versaoDoNode())->toMatch('/^v\d+\.\d+\.\d+/');

    $modelo = modeloDoMapaPorId();
    $decls  = mapaDiagramasMapasConstruidos();

    if ($mundo === 'publicados') {
        $gerado       = entradasDoGeradorDoMapa(['construtor' => 'mapa', 'soCaixasESetas' => true]);
        $resultado    = mapaDiagramasConferirCaixas($decls, $modelo, $gerado);
        $agrupamentos = array_sum(array_map(static fn (array $decl): int => count($decl['agrupamentos'] ?? []), $decls));

        expect(count($decls))->toBeGreaterThan(0)
            ->and($agrupamentos)->toBeGreaterThan(0)
            ->and($resultado['mapas'])->toBe(count($decls))
            ->and($resultado['caixas'])->toBe($agrupamentos)
            ->and($resultado['motivo'])->toBe('aceita');

        return;
    }

    // O Dado: o painel app registra o Dashboard sem tenant, e o modelo não lhe dá condição; o IdentifyTenant e o
    // DefinirTenantDePermissoes, só com tenant.
    $slug        = 'app-resumo-sem';
    $recurso     = 'App\Filament\App\Pages\Dashboard';
    expect($modelo['pecas'][$recurso]['disponibilidade'] ?? null)->toBeNull();
    $soComTenant = ['Filament\Http\Middleware\IdentifyTenant', 'App\Http\Middleware\DefinirTenantDePermissoes'];
    expect($decls[$slug]['modos'] ?? null)->toBe(['sem'])
        ->and($modelo['pecas'][$recurso]['registrada'] ?? [])->toContain('sem');
    foreach ($soComTenant as $id) {
        expect($modelo['pecas'][$id]['registrada'] ?? null)->toBe(['com']);
    }

    $id    = 'grupo-controle-da-caixa';
    $grupo = [
        'id'      => $id,
        'rotulo'  => ['pt' => 'Controle da caixa', 'en' => 'Box control'],
        'membros' => $mundo === 'três membros, um no modo' ? [$recurso, ...$soComTenant] : $soComTenant,
        'row'     => 4,
        'col'     => 0,
    ];
    $copia     = [$slug => [...$decls[$slug], 'agrupamentos' => [...($decls[$slug]['agrupamentos'] ?? []), $grupo]]];
    $gerado    = entradasDoGeradorDoMapa(['construtor' => 'mapa', 'soCaixasESetas' => true, 'agrupamentos' => [$slug => [$grupo]], 'slugs' => [$slug]]);
    $resultado = mapaDiagramasConferirCaixas($copia, $modelo, $gerado);
    $daCaixa   = array_values(array_filter($resultado['falhas'], static fn (array $falha): bool => in_array($falha['caixa'], [$id, ''], true)));

    // Controle positivo: com a caixa como o 04 a quer (o rótulo no singular, ou fora do desenho), a guarda não a acusa.
    $consertado = $gerado;
    foreach (['pt' => '1 peça', 'en' => '1 piece'] as $idioma => $umaPeca) {
        $componentes                                           = $consertado['diagramas'][$slug][$idioma]['components'] ?? [];
        $consertado['diagramas'][$slug][$idioma]['components'] = $mundo === 'três membros, um no modo'
            ? array_map(static fn (array $componente): array => $componente['id'] === $id ? [...$componente, 'sublabel' => $umaPeca] : $componente, $componentes)
            : array_values(array_filter($componentes, static fn (array $componente): bool => $componente['id'] !== $id));
    }
    expect(array_values(array_filter(mapaDiagramasConferirCaixas($copia, $modelo, $consertado)['falhas'], static fn (array $falha): bool => $falha['caixa'] === $id)))->toBe([]);

    foreach (['pt' => '1 peça', 'en' => '1 piece'] as $idioma => $umaPeca) {
        $entrada = $gerado['diagramas'][$slug][$idioma] ?? [];
        $caixa   = collect($entrada['components'] ?? [])->firstWhere('id', $id);
        if ($mundo === 'três membros, um no modo') {
            expect($caixa['sublabel'] ?? null)->toBe($umaPeca, "{$slug} ({$idioma}): a caixa de três membros, um só registrado sem tenant");

            continue;
        }
        $recusou = isset($entrada['erro']) && (str_contains($entrada['erro'], $id) || str_contains($entrada['erro'], 'Controle da caixa') || str_contains($entrada['erro'], 'Box control'));
        expect($recusou || $caixa === null)->toBeTrue("{$slug} ({$idioma}): a caixa sem membro no modo saiu desenhada, com \"".($caixa['sublabel'] ?? '').'"');
    }
    expect($daCaixa)->toBe([]);
})->with(['publicados', 'três membros, um no modo', 'nenhum membro no modo']);

/*
|--------------------------------------------------------------------------
| Adendos 2 a 4 — a peça condicional: o lugar (R41, CT-33)
|--------------------------------------------------------------------------
|
| A metade sem tenant lê o discriminante vivo da suíte do kit — a saída "lugar" de `discriminanteDaPeca()`, de
| `tests/Pest.php` —, sobre a população de `populacaoDoDiscriminante()`; a metade com tenant lê a condição do
| modelo (o campo `disponibilidade` da peça em `site/mapa/pecas.json`), que o CT-56 confere contra o código na
| suíte Tenancy. O desenho de cada diagrama é o de `mapaDiagramasConstruir()`, e o nó escrito à mão é também a peça
| cuja classe a prova dele declara (Adendo 4).
*/

/**
 * A coleção de nós e a de arestas de cada tipo do archify, no arquivo escrito à mão e na entrada que o gerador monta.
 *
 * @return array<string, array{0: string, 1: ?string}>
 */
function mapaDiagramasColecoes(): array
{
    return [
        'workflow'  => ['nodes', 'edges'], 'sequence' => ['participants', 'messages'], 'dataflow' => ['nodes', 'flows'],
        'lifecycle' => ['states', 'transitions'], 'erd' => ['entities', 'relationships'], 'class' => ['types', 'relationships'],
        'timeline'  => ['events', null], 'waterfall' => ['spans', null], 'architecture' => ['components', 'connections'],
        'tree'      => ['nodes', null],
    ];
}

/**
 * A condição de disponibilidade que o modelo dá à peça — o campo `disponibilidade`, `{modo?, chaves?, evidencia}` —,
 * ou nula.
 *
 * @return array<string, mixed>|null
 */
function mapaDiagramasCondicao(array $modelo, string $id): ?array
{
    $condicao = $modelo['pecas'][$id]['disponibilidade'] ?? null;

    return is_array($condicao) ? $condicao : null;
}

/**
 * A condição que o `04` dá a cada peça das linhas de controle — a tabela da população e as âncoras do Setup Global
 * (Adendos 2 a 4) —, no formato do campo `disponibilidade` do modelo. A âncora é o arquivo e o símbolo do código que
 * decide; ela só vale na cópia em memória.
 *
 * @return array<string, array<string, mixed>>
 */
function mapaDiagramasCondicoesDoCaso(): array
{
    $fontes = array_values((array) config('command-center.sources'));
    $ancora = static fn (string $arquivo, string $trecho): array => ['arquivo' => $arquivo, 'trecho' => $trecho];

    return [
        'App\\Filament\\Admin\\Resources\\Tenants\\TenantResource'            => ['modo' => 'com', 'evidencia' => $ancora('app/Filament/Admin/Resources/Tenants/TenantResource.php', 'canAccess')],
        'App\\Filament\\App\\Resources\\Users\\UserResource'                  => ['modo' => 'com', 'evidencia' => $ancora('app/Filament/App/Resources/Users/UserResource.php', 'canAccess')],
        'App\\Filament\\App\\Resources\\Projetos\\ProjetoResource'            => ['modo' => 'com', 'chaves' => [['chave' => 'KIT_DEMO', 'padrao' => false]], 'evidencia' => $ancora('app/Filament/App/Resources/Projetos/ProjetoResource.php', 'canAccess')],
        'App\\Filament\\Admin\\Resources\\AgentesIa\\AgenteIaResource'        => ['chaves' => [['chave' => 'KIT_AGENTES_IA', 'padrao' => false]], 'evidencia' => $ancora('app/Filament/Admin/Resources/AgentesIa/AgenteIaResource.php', 'canAccess')],
        'App\\Filament\\Admin\\Pages\\HubDeAdministracao'                     => ['chaves' => [['chave' => 'KIT_HUB', 'padrao' => false]], 'evidencia' => $ancora('app/Filament/Admin/Pages/HubDeAdministracao.php', 'regraLocalDeAcesso')],
        'Bityukov\\CommandCenter\\Filament\\Resources\\CommandRecordResource' => ['chaves' => [['chave' => 'command-center.sources', 'padrao' => $fontes, 'ligada' => [...$fontes, 'Bityukov\\CommandCenter\\Sources\\DatabaseSource']]], 'evidencia' => $ancora('config/command-center.php', "'sources'")],
        'App\\Livewire\\AssistenteChatWidget'                                 => ['chaves' => [['chave' => 'KIT_AGENTES_IA', 'padrao' => false], ['chave' => 'KIT_AGENTES_IA_PAINEIS', 'padrao' => []]], 'evidencia' => $ancora('app/Support/AgentesDeIa.php', 'assistenteNoPainel')],
        'App\\Filament\\Admin\\Pages\\Dashboard'                              => mapaModeloCondicaoDoDashboard('App\\Filament\\Admin\\Pages\\Dashboard'),
        'App\\Filament\\App\\Pages\\Dashboard'                                => mapaModeloCondicaoDoDashboard('App\\Filament\\App\\Pages\\Dashboard'),
        'App\\Filament\\Infra\\Pages\\Dashboard'                              => mapaModeloCondicaoDoDashboard('App\\Filament\\Infra\\Pages\\Dashboard'),
        'MDDev\\DynamicDashboard\\Pages\\DynamicDashboard'                    => mapaModeloCondicaoDoDashboard('App\\Filament\\Admin\\Pages\\Dashboard'),
    ];
}

/**
 * A cópia em memória do modelo com a condição de `mapaDiagramasCondicoesDoCaso()` nas peças pedidas, só na que o
 * modelo deixa sem condição: com a condição publicada, vale a do modelo.
 *
 * @param  list<string>  $pecas
 */
function mapaDiagramasModeloComCondicoes(array $modelo, array $pecas): array
{
    $doCaso = mapaDiagramasCondicoesDoCaso();
    foreach ($pecas as $peca) {
        $modelo['pecas'][$peca]['disponibilidade'] ??= $doCaso[$peca];
    }

    return $modelo;
}

/** A cópia do modelo sem as ligações de `$de` a `$para`. */
function mapaDiagramasSemALigacao(array $modelo, string $de, string $para): array
{
    $modelo['ligacoes'] = array_values(array_filter(
        $modelo['ligacoes'],
        static fn (array $ligacao): bool => ! ($ligacao['de'] === $de && $ligacao['para'] === $para),
    ));

    return $modelo;
}

/**
 * A saída "lugar" de `discriminanteDaPeca()` no modo da suíte do kit, sem tenant, medida uma vez por painel, peça e
 * condição no processo: o modo é decidido no boot, e a resposta não muda de um caso para outro.
 */
function mapaDiagramasAbreSemTenant(string $painel, string $peca, ?array $condicao): ?bool
{
    static $medidas = [];

    $medida = $medidas[$painel.'|'.$peca.'|'.json_encode($condicao)] ??= discriminanteDaPeca($painel, 'sem', $peca, $condicao);

    return $medida['vota'] ? $medida['lugar'] : null;
}

/**
 * A subtração da R34 sem tenant: a peça da população registrada do painel que não abre de fábrica, nem com uma chave
 * booleana de `kit.*` desligada ligada, nem com as chaves da condição do modelo ligadas juntas.
 *
 * @return list<string>
 */
function mapaDiagramasSubtracaoSemTenant(string $painel, array $modelo): array
{
    return array_values(array_filter(
        populacaoDoDiscriminante($painel, $modelo)['registrada'],
        static fn (string $peca): bool => mapaDiagramasAbreSemTenant($painel, $peca, mapaDiagramasCondicao($modelo, $peca)) === false,
    ));
}

/**
 * Os painéis em que o discriminante avalia a peça desenhada: o do diagrama, se ele a registra, ou os que a registram;
 * e a peça que painel nenhum registra — a que herda pelo método próprio e o componente flutuante —, em cada painel do
 * registro (Setup Global do `04`, "A população"). Fora da população, nenhum.
 *
 * @param  list<string>  $doDiagrama
 * @param  array<string, array{registrada: list<string>, foraDoRegistro: list<string>, flutuante: list<string>}>  $populacao
 * @return list<string>
 */
function mapaDiagramasPaineisDoLugar(string $peca, array $doDiagrama, array $populacao): array
{
    $registram = array_keys(array_filter($populacao, static fn (array $doPainel): bool => in_array($peca, $doPainel['registrada'], true)));
    if ($registram !== []) {
        return array_values(array_intersect($registram, $doDiagrama)) ?: $registram;
    }
    $foraDoRegistro = array_filter($populacao, static fn (array $doPainel): bool => in_array($peca, [...$doPainel['foraDoRegistro'], ...$doPainel['flutuante']], true));

    return $foraDoRegistro === [] ? [] : array_keys($populacao);
}

/** O desenho com a peça como nó próprio: a cópia que a desenha, mesmo que o construtor a tirasse. */
function mapaDiagramasComoNo(array $ir, array $modelo, string $peca): array
{
    $ir['nos'][$peca] ??= mapaDiagramasNo('peca', mapaDiagramasNomeDaPeca($modelo, $peca), [$peca]);

    return $ir;
}

/** O desenho com a peça como membro nomeado do agrupamento. */
function mapaDiagramasComoMembro(array $ir, string $grupo, string $peca): array
{
    foreach (['membros', 'nomeados'] as $lista) {
        $ir['nos'][$grupo][$lista] = array_values(array_unique([...($ir['nos'][$grupo][$lista] ?? []), $peca]));
    }

    return $ir;
}

/** O id do primeiro agrupamento do diagrama cujo id tem o trecho. */
function mapaDiagramasAgrupamentoQue(array $decl, string $trecho): string
{
    foreach ($decl['agrupamentos'] ?? [] as $grupo) {
        if (str_contains((string) $grupo['id'], $trecho)) {
            return (string) $grupo['id'];
        }
    }

    throw new RuntimeException("o diagrama {$decl['slug']} não tem agrupamento com \"{$trecho}\"");
}

/**
 * A guarda do lugar (R41, CT-33): em cada diagrama, a peça que ele desenha — nó, membro nomeado de agrupamento, nó
 * da árvore de registro, nó escrito à mão pelo nome ou pela prova — fica disponível em todo modo em que o diagrama
 * vale. Sem tenant, pela saída "lugar" do discriminante vivo, nos painéis de `mapaDiagramasPaineisDoLugar()`: painéis
 * que discordam reprovam, falha fechado. Com tenant, pela condição do modelo. O comparativo fica fora: é a soma de
 * dois mapas, conferidos cada um.
 *
 * @param  array<string, array<string, mixed>>  $decls
 * @param  array<string, array>  $irs
 * @return array{aceita: bool, motivo: string, falhas: list<array{diagrama: string, peca: string, texto: string}>, diagramas: list<string>, pecas: list<string>}
 */
function mapaDiagramasConferirLugar(array $modelo, array $decls, array $irs): array
{
    $populacao = [];
    foreach (collect(Filament::getPanels())->map->getId()->values()->all() as $painel) {
        $populacao[$painel] = populacaoDoDiscriminante($painel, $modelo);
    }
    $falhas     = [];
    $diagramas  = [];
    $conferidas = [];
    foreach ($irs as $slug => $ir) {
        if ($ir['tipo'] === 'compare' || $ir['modos'] === []) {
            continue;
        }
        $slug        = (string) $slug;
        $diagramas[] = $slug;
        foreach ($ir['nos'] as $no => $dado) {
            $membro = $dado['tipo'] === 'grupo';
            $onde   = $membro ? "como membro do agrupamento {$no} ({$dado['rotulo']})" : "como o nó {$no}";
            foreach (array_unique($membro ? $dado['nomeados'] : $dado['pecas']) as $peca) {
                $conferidas[$peca] = true;
                $quem              = mapaDiagramasNomeDaPeca($modelo, $peca)." ({$peca})";
                $condicao          = mapaDiagramasCondicao($modelo, $peca);
                $paineis           = in_array('sem', $ir['modos'], true) ? mapaDiagramasPaineisDoLugar($peca, $ir['paineis'], $populacao) : [];
                $abre              = array_filter(array_map(static fn (string $painel): ?bool => mapaDiagramasAbreSemTenant($painel, $peca, $condicao), array_combine($paineis, $paineis)), static fn (?bool $voto): bool => $voto !== null);
                if ($paineis !== [] && $abre === []) {
                    $falhas[] = ['diagrama' => $slug, 'peca' => $peca, 'texto' => "o diagrama {$slug} desenha {$quem} {$onde}, e nenhum painel (".implode(', ', $paineis).') registra subclasse que vote: sem voto, falha fechado'];
                }
                if (count(array_unique($abre)) > 1) {
                    $falhas[] = ['diagrama' => $slug, 'peca' => $peca, 'texto' => "o diagrama {$slug} desenha {$quem} {$onde}, e os painéis discordam sem tenant: abre em ".implode(', ', array_keys(array_filter($abre))).' e não em '.implode(', ', array_keys(array_filter($abre, static fn (bool $abreNele): bool => ! $abreNele)))];
                } elseif ($abre !== [] && ! reset($abre)) {
                    $falhas[] = ['diagrama' => $slug, 'peca' => $peca, 'texto' => "o diagrama {$slug}, que vale sem tenant, desenha {$quem} {$onde}, e ela não abre sem tenant nem com a chave ligada (".implode(', ', $paineis).')'];
                }
                if (in_array('com', $ir['modos'], true) && ($condicao['modo'] ?? 'com') !== 'com') {
                    $falhas[] = ['diagrama' => $slug, 'peca' => $peca, 'texto' => "o diagrama {$slug}, que vale com tenant, desenha {$quem} {$onde}, e o modelo a dá só sem tenant"];
                }
            }
        }
    }

    return [
        'aceita'    => $falhas === [],
        'motivo'    => $falhas === [] ? 'aceita' : 'reprova, '.count($falhas).' lugar(es): '.implode(' | ', array_column($falhas, 'texto')),
        'falhas'    => $falhas,
        'diagramas' => $diagramas,
        'pecas'     => array_keys($conferidas),
    ];
}

/**
 * A RQ-29 (CT-33): o /admin tem ao menos um diagrama que vale só sem tenant e um que vale só com tenant, e o
 * diagrama do /admin que desenha o TenantResource vale só com tenant.
 *
 * @param  array<string, array>  $irs
 * @return array{aceita: bool, motivo: string}
 */
function mapaDiagramasConferirAdminPorModo(array $irs, string $tenantResource): array
{
    $soSem  = [];
    $soCom  = [];
    $falhas = [];
    foreach ($irs as $slug => $ir) {
        if ($ir['tipo'] === 'compare' || ! in_array('admin', $ir['paineis'], true)) {
            continue;
        }
        if ($ir['modos'] === ['sem']) {
            $soSem[] = $slug;
        }
        if ($ir['modos'] === ['com']) {
            $soCom[] = $slug;
        }
        if ($ir['modos'] !== ['com'] && array_key_exists($tenantResource, mapaDiagramasPecasDesenhadas($ir))) {
            $falhas[] = "o diagrama {$slug} do /admin desenha o TenantResource e vale ".($ir['modos'] === ['sem'] ? 'só sem tenant' : 'nos dois modos');
        }
    }
    if ($soSem === []) {
        $falhas[] = 'o /admin não tem diagrama que valha só sem tenant';
    }
    if ($soCom === []) {
        $falhas[] = 'o /admin não tem diagrama que valha só com tenant';
    }

    return ['aceita' => $falhas === [], 'motivo' => $falhas === [] ? 'aceita' : 'reprova: '.implode(' | ', $falhas)];
}

it('[CT-33] nenhum diagrama desenha a peça condicional num modo em que ela fica indisponível, e o /admin tem diagramas separados sem e com tenant, com o TenantResource só nos de com', function (string $mundo): void {
    expect(config('kit.tenancy.enabled'))->toBeFalse();
    foreach (Filament::getPanels() as $doRegistro) {
        expect($doRegistro->hasTenancy())->toBeFalse("o painel {$doRegistro->getId()} tem organização na suíte sem tenant");
    }

    $modelo  = modeloDoMapaPorId();
    $decls   = diagramasDoMapa();
    $tenant  = 'App\\Filament\\Admin\\Resources\\Tenants\\TenantResource';
    $userApp = 'App\\Filament\\App\\Resources\\Users\\UserResource';
    $agente  = 'App\\Filament\\Admin\\Resources\\AgentesIa\\AgenteIaResource';
    $arvore  = static fn (string $slug, string $painel, array $modos): array => ['slug' => $slug, 'tipo' => 'tree', 'construtor' => 'registro', 'paineis' => [$painel], 'modos' => $modos];

    if ($mundo === 'publicados') {
        $irs     = mapaDiagramasMundo()['irs'];
        $lugar   = mapaDiagramasConferirLugar($modelo, $decls, $irs);
        $porModo = mapaDiagramasConferirAdminPorModo($irs, $tenant);

        expect($lugar['diagramas'])->toEqualCanonicalizing(array_keys(array_filter($decls, static fn (array $decl): bool => $decl['tipo'] !== 'compare')))
            ->and($lugar['pecas'])->toContain($tenant, $userApp, $agente)
            ->and(['lugar' => $lugar['motivo'], 'RQ-29' => $porModo['motivo']])->toBe(['lugar' => 'aceita', 'RQ-29' => 'aceita']);

        return;
    }

    if ($mundo === 'todo diagrama do /admin compartilhado e nenhum com o TenantResource') {
        $doAdmin = [];
        foreach (mapaDiagramasMundo()['irs'] as $slug => $ir) {
            if ($ir['tipo'] === 'compare' || ! in_array('admin', $ir['paineis'], true)) {
                continue;
            }
            unset($ir['nos'][$tenant]);
            foreach ($ir['nos'] as $no => $dado) {
                $ir['nos'][$no]['membros']  = array_values(array_diff($dado['membros'], [$tenant]));
                $ir['nos'][$no]['nomeados'] = array_values(array_diff($dado['nomeados'], [$tenant]));
            }
            $doAdmin[$slug] = [...$ir, 'modos' => ['sem', 'com']];
        }
        $resultado = mapaDiagramasConferirAdminPorModo($doAdmin, $tenant);

        expect($doAdmin)->not->toBeEmpty()
            ->and($resultado['aceita'])->toBeFalse()
            ->and($resultado['motivo'])->toContain('/admin', 'só com tenant');

        return;
    }

    $copia  = $modelo;
    $aceita = false;
    $nomeia = [];
    switch ($mundo) {
        case 'cópia da árvore de registro do /admin valendo nos dois modos, que desenha o TenantResource':
            [$slug, $peca] = ['admin-registro', $tenant];
            $ir            = mapaDiagramasComoNo(mapaDiagramasConstruir($arvore($slug, 'admin', ['sem', 'com']), $modelo), $modelo, $peca);
            $nomeia        = [$slug, 'TenantResource'];
            break;
        case 'cópia do app-registro-sem que desenha o UserResource do /app':
            [$slug, $peca] = ['app-registro-sem', $userApp];
            $ir            = mapaDiagramasComoNo(mapaDiagramasConstruir($arvore($slug, 'app', ['sem']), $modelo), $modelo, $peca);
            $nomeia        = [$slug, $userApp];
            break;
        case 'cópia do app-resumo-sem com o ConviteResource do /app membro do agrupamento de convites':
            [$slug, $peca] = ['app-resumo-sem', 'App\\Filament\\App\\Resources\\Convites\\ConviteResource'];
            $grupo         = mapaDiagramasAgrupamentoQue($decls[$slug], 'convites');
            $ir            = mapaDiagramasComoMembro(mapaDiagramasConstruir([...$decls[$slug], 'modos' => ['sem']], $modelo), $grupo, $peca);
            $nomeia        = [$slug, $grupo, $peca];
            break;
        case 'cópia do app-produtividade-e-ui-com valendo nos dois modos, que desenha o ProjetoResource do /app':
            [$slug, $peca] = ['app-produtividade-e-ui', 'App\\Filament\\App\\Resources\\Projetos\\ProjetoResource'];
            // A cópia compartilhada: o mapa do /app com tenant que desenha o ProjetoResource, valendo nos dois modos.
            $decls[$slug]  = [...$decls['app-produtividade-e-ui-com'], 'slug' => $slug];
            $ir            = mapaDiagramasComoNo(mapaDiagramasConstruir([...$decls[$slug], 'modos' => ['sem', 'com']], $modelo), $modelo, $peca);
            $nomeia        = [$slug, $peca];
            break;
        case 'cópia em que o admin-administracao, compartilhado, desenha o AgenteIaResource':
            [$slug, $peca, $aceita] = ['admin-administracao', $agente, true];
            $ir                     = mapaDiagramasComoNo(mapaDiagramasConstruir([...$decls[$slug], 'modos' => ['sem', 'com']], $modelo), $modelo, $peca);
            break;
        case 'cópia do kit-resumo-sem com o AcessosPorPainel membro do agrupamento de multi-tenancy':
            [$slug, $peca] = ['kit-resumo-sem', 'App\\Filament\\Admin\\Resources\\Tenants\\Widgets\\AcessosPorPainel'];
            $grupo         = mapaDiagramasAgrupamentoQue($decls[$slug], 'multi-tenancy');
            $ir            = mapaDiagramasComoMembro(mapaDiagramasConstruir([...$decls[$slug], 'modos' => ['sem']], $modelo), $grupo, $peca);
            $nomeia        = [$slug, $grupo, $peca];
            break;
        case 'cópia do modelo com a condição KIT_DASHBOARD_DINAMICO no Dashboard do /infra, que o infra-produtividade-e-ui, compartilhado, desenha':
            [$slug, $peca, $aceita] = ['infra-produtividade-e-ui', 'App\\Filament\\Infra\\Pages\\Dashboard', true];
            $copia                  = mapaDiagramasModeloComCondicoes($modelo, [$peca]);
            $ir                     = mapaDiagramasConstruir([...$decls[$slug], 'modos' => ['sem', 'com']], $copia);
            break;
        case 'cópia do modelo com a condição do CommandRecordResource, que o infra-infraestrutura-2, compartilhado, desenha':
            [$slug, $peca, $aceita] = ['infra-infraestrutura-2', 'Bityukov\\CommandCenter\\Filament\\Resources\\CommandRecordResource', true];
            $copia                  = mapaDiagramasModeloComCondicoes($modelo, [$peca]);
            $ir                     = mapaDiagramasConstruir([...$decls[$slug], 'modos' => ['sem', 'com']], $copia);
            break;
        default:
            [$slug, $peca, $aceita] = ['kit-resumo-sem', 'App\\Livewire\\AssistenteChatWidget', true];
            $copia                  = mapaDiagramasModeloComCondicoes($modelo, [$peca]);
            $grupo                  = collect($decls[$slug]['agrupamentos'] ?? [])->first(static fn (array $agrupamento): bool => in_array($peca, $agrupamento['membros'], true));
            expect($grupo)->not->toBeNull("o chat do assistente é membro de um agrupamento do {$slug}");
            $ir = mapaDiagramasConstruir([...$decls[$slug], 'modos' => ['sem'], 'pecas' => [], 'portas' => [], 'agrupamentos' => [$grupo]], $copia);
    }
    $resultado = mapaDiagramasConferirLugar($copia, $decls, [$slug => $ir]);

    expect($resultado['pecas'])->toContain($peca);
    if ($aceita) {
        expect($resultado['motivo'])->toBe('aceita');

        return;
    }
    $daPeca = array_column(array_filter($resultado['falhas'], static fn (array $falha): bool => $falha['diagrama'] === $slug && $falha['peca'] === $peca), 'texto');
    expect($resultado['aceita'])->toBeFalse()
        ->and($daPeca)->not->toBeEmpty()
        ->and(implode("\n", $daPeca))->toContain(...$nomeia);
})->with([
    'publicados',
    'cópia da árvore de registro do /admin valendo nos dois modos, que desenha o TenantResource',
    'cópia do app-registro-sem que desenha o UserResource do /app',
    'cópia do app-resumo-sem com o ConviteResource do /app membro do agrupamento de convites',
    'cópia do app-produtividade-e-ui-com valendo nos dois modos, que desenha o ProjetoResource do /app',
    'cópia em que o admin-administracao, compartilhado, desenha o AgenteIaResource',
    'todo diagrama do /admin compartilhado e nenhum com o TenantResource',
    'cópia do kit-resumo-sem com o AcessosPorPainel membro do agrupamento de multi-tenancy',
    'cópia do modelo com a condição do CommandRecordResource, que o infra-infraestrutura-2, compartilhado, desenha',
    'cópia do modelo com a condição do chat do assistente, que o kit-resumo-sem desenha como membro',
    'cópia do modelo com a condição KIT_DASHBOARD_DINAMICO no Dashboard do /infra, que o infra-produtividade-e-ui, compartilhado, desenha',
]);

/*
|--------------------------------------------------------------------------
| Adendos 2 a 4 — a marca da peça condicional e o card das condições (R42, R44; CT-57)
|--------------------------------------------------------------------------
|
| Costura G5, forma de leitura: `entradasDoGeradorDoMapa()` monta a entrada de cada diagrama em pt e em en, sem
| baixar o archify nem renderizar. A condição vem do modelo, de onde o gerador a lê. As linhas de controle partem da
| entrada publicada, com a condição que o `04` dá à peça na cópia em memória do modelo: o filho do helper não recebe
| a cópia, e por isso a adulteração é na entrada, como no CT-46. A redação da marca é a decisão de desenho da sessão
| (Adendos 3 e 4).
*/

/**
 * As partes que a marca da condição traz, no idioma (R42, redação decidida): o modo como "só com tenant ativo" /
 * "tenancy on only"; a chave booleana única como "KIT_HUB=false de fábrica" / "by default"; com várias chaves, cada
 * uma — a booleana "VAR=valor", a lista pelo nome — e o "de fábrica" depois delas; a configuração de pacote pelo
 * caminho e pela fonte que falta de fábrica, com o "de fábrica" depois. O modo "sem" não tem redação decidida: a
 * parte dele é uma que nenhuma marca traz, e a guarda reprova, falha fechado.
 *
 * @return array{partes: list<string>, fim: ?string}
 */
function mapaDiagramasPartesDaMarca(array $condicao, string $idioma): array
{
    $deFabrica = $idioma === 'pt' ? 'de fábrica' : 'by default';
    $partes    = [];
    $modo      = $condicao['modo'] ?? null;
    if ($modo !== null) {
        $partes[] = $modo === 'com' ? ($idioma === 'pt' ? 'só com tenant ativo' : 'tenancy on only') : "(a redação da marca do modo {$modo} não foi decidida)";
    }
    $chaves   = $condicao['chaves'] ?? [];
    $variavel = static fn (string $chave): bool => preg_match('/^[A-Z][A-Z0-9_]*$/', $chave) === 1;
    $valor    = static fn (mixed $padrao): string => is_bool($padrao) ? ($padrao ? 'true' : 'false') : (is_scalar($padrao) ? (string) $padrao : '');
    if (count($chaves) === 1 && $variavel((string) $chaves[0]['chave']) && is_bool($chaves[0]['padrao'] ?? null)) {
        $partes[] = $chaves[0]['chave'].'='.$valor($chaves[0]['padrao'])." {$deFabrica}";

        return ['partes' => $partes, 'fim' => null];
    }
    foreach ($chaves as $chave) {
        $nome = (string) $chave['chave'];
        if ($variavel($nome)) {
            // A chave que é lista diz o valor de fábrica dela: "vazia" / "empty" (R42, achado A3).
            $partes[] = is_bool($chave['padrao'] ?? null)
                ? $nome.'='.$valor($chave['padrao'])
                : ((array) ($chave['padrao'] ?? []) === [] ? $nome.($idioma === 'pt' ? ' vazia' : ' empty') : $nome);

            continue;
        }
        $partes[] = $nome;
        foreach (array_diff((array) ($chave['ligada'] ?? []), (array) ($chave['padrao'] ?? [])) as $falta) {
            $partes[] = class_basename((string) $falta);
        }
    }

    return ['partes' => $partes, 'fim' => $chaves === [] ? null : $deFabrica];
}

/**
 * O que falta no texto para ser a marca da condição no idioma: as partes ausentes, e o "de fábrica" fora do fim.
 *
 * @return list<string>
 */
function mapaDiagramasFaltaNaMarca(string $texto, array $condicao, string $idioma): array
{
    ['partes' => $partes, 'fim' => $fim] = mapaDiagramasPartesDaMarca($condicao, $idioma);
    $faltam                              = array_map(static fn (string $parte): string => "\"{$parte}\"", array_values(array_filter($partes, static fn (string $parte): bool => ! str_contains($texto, $parte))));
    if ($fim !== null) {
        $depois = max(array_map(static fn (string $parte): int => (int) strrpos($texto, $parte) + strlen($parte), $partes));
        if (strpos($texto, $fim, min($depois, strlen($texto))) === false) {
            $faltam[] = "\"{$fim}\" depois das chaves";
        }
    }

    return $faltam;
}

/** A marca inteira da condição no idioma, na redação decidida: o que as linhas de controle escrevem na entrada. */
function mapaDiagramasMarca(array $condicao, string $idioma): string
{
    ['partes' => $partes, 'fim' => $fim] = mapaDiagramasPartesDaMarca($condicao, $idioma);

    return implode(' · ', $partes).($fim === null ? '' : " {$fim}");
}

/**
 * O texto traz alguma marca na redação decidida inteira (R42, achado A4): o modo; uma chave — a variável `KIT_*` com o
 * valor, a lista com "vazia" / "empty", ou qualquer caminho de configuração com a fonte que falta.
 */
function mapaDiagramasTemMarca(string $texto): bool
{
    return preg_match('/só com tenant ativo|tenancy on only|\b[A-Z][A-Z0-9_]*=(?:false|true)\b|\b[A-Z][A-Z0-9_]*\s+(?:vazia|empty)\b|\b[a-z][a-z0-9_-]*(?:\.[a-z0-9_-]+)+\s+(?:sem|without)\s+\S/u', $texto) === 1;
}

/**
 * As peças que cada nó da entrada é: no construído, a do id do archify; no escrito à mão, a do id ou do nome exato
 * do rótulo e a da prova, pela D43 (`mapaDiagramasConstruir()`, `pecasDoNoEscritoAMao()`).
 *
 * @param  array<string, string>  $ids  id do modelo => id do archify
 * @return array<string, list<string>> id do nó => ids das peças
 */
function mapaDiagramasPecasDosNos(array $entrada, array $decl, array $modelo, array $ids): array
{
    $colecao    = mapaDiagramasColecoes()[$entrada['diagram_type'] ?? ''][0] ?? null;
    $escrito    = ($decl['construtor'] ?? null) === null;
    $porArchify = array_flip($ids);
    $ir         = $escrito ? mapaDiagramasConstruir($decl, $modelo) : null;
    $saida      = [];
    foreach ($colecao === null ? [] : ($entrada[$colecao] ?? []) as $no) {
        $id    = (string) ($no['id'] ?? '');
        $pecas = $escrito ? ($ir['nos'][$id]['pecas'] ?? []) : (isset($porArchify[$id]) ? [(string) $porArchify[$id]] : []);
        if ($pecas !== []) {
            $saida[$id] = array_values(array_unique($pecas));
        }
    }

    return $saida;
}

/**
 * Os agrupamentos que nomeiam a peça no mapa construído, no modo do diagrama, como o construtor
 * (`mapaDiagramasConstruir()`): o card "Quem está em …" de cada um tem a linha dela.
 *
 * @return list<array<string, mixed>>
 */
function mapaDiagramasGruposDaPeca(array $decl, array $modelo, string $peca): array
{
    if (($decl['construtor'] ?? null) !== 'mapa') {
        return [];
    }
    $ir = mapaDiagramasConstruir($decl, $modelo);

    return array_values(array_filter($decl['agrupamentos'] ?? [], static fn (array $grupo): bool => in_array($peca, $ir['nos'][$grupo['id']]['nomeados'] ?? [], true)));
}

/** O título do card que nomeia os membros do agrupamento, no idioma. */
function mapaDiagramasTituloDoGrupo(array $grupo, string $idioma): string
{
    return $idioma === 'pt' ? 'Quem está em '.mapaDiagramasPt($grupo['rotulo'] ?? '') : 'Who is in '.mapaDiagramasEn($grupo['rotulo'] ?? '');
}

/** O nome da peça no começo da linha de um card: o que vem antes do primeiro separador. */
function mapaDiagramasNomeDaLinha(string $linha): string
{
    return (string) preg_replace('/^\s*([^\s—·:(,]+).*$/us', '$1', $linha);
}

/**
 * A guarda da marca (R42, R44; CT-57), na entrada que o gerador monta, em cada idioma: o nó de toda peça com condição
 * no modelo traz a marca dela no rótulo secundário — e não só no `tag`, que o carregamento esconde —; a linha dela no
 * card "Quem está em …" do agrupamento que a nomeia, também; o card "Condições de disponibilidade" tem uma linha por
 * peça condicional desenhada, começando pelo nome dela, com a marca e a âncora da condição — o arquivo e o trecho —,
 * e nenhuma de peça que o diagrama não desenha; e nenhum nó nem linha de peça sem condição — plugin e middleware
 * inclusive — traz marca. O nó é a peça de `mapaDiagramasPecasDosNos()`, e o membro, o de `mapaDiagramasConstruir()`.
 * No diagrama escrito à mão, a marca fica só no card, e a linha pode começar pelo rótulo do nó que é a peça: a caixa
 * do participante de sequência e o nó do dataflow não comportam o texto (decisão da sessão, medida no archify).
 *
 * @param  array<string, array<string, mixed>>  $decls  os diagramas conferidos, por slug
 * @param  array{ids: array<string, string>, diagramas: array<string, array<string, array<string, mixed>>>}  $gerado
 * @return array{aceita: bool, motivo: string, falhas: list<array{diagrama: string, peca: string, texto: string}>, marcadas: list<string>, comoNo: int, comoMembro: int, semCondicao: list<string>, entradas: int}
 */
function mapaDiagramasConferirMarcas(array $modelo, array $decls, array $gerado): array
{
    $falhas      = [];
    $marcadas    = [];
    $semCondicao = [];
    $comoNo      = 0;
    $comoMembro  = 0;
    $entradas    = 0;
    $idsPorNome  = mapaDiagramasIdsPorNome($modelo);
    foreach ($decls as $slug => $decl) {
        $slug    = (string) $slug;
        $ir      = ($decl['construtor'] ?? null) === 'mapa' ? mapaDiagramasConstruir($decl, $modelo) : null;
        $escrito = ($decl['construtor'] ?? null) === null;
        foreach ($gerado['diagramas'][$slug] ?? [] as $idioma => $entrada) {
            $falha = static function (string $peca, string $texto) use (&$falhas, $slug, $idioma): void {
                $falhas[] = ['diagrama' => $slug, 'peca' => $peca, 'texto' => "{$slug} ({$idioma}): {$texto}"];
            };
            if (isset($entrada['erro'])) {
                $falha('', "o gerador recusou o diagrama — {$entrada['erro']}");

                continue;
            }
            $entradas++;
            $desenhadas  = [];
            $nomesNoCard = [];
            $colecao     = mapaDiagramasColecoes()[$entrada['diagram_type'] ?? ''][0] ?? '';
            $nos         = collect($entrada[$colecao] ?? [])->keyBy('id')->all();
            foreach (mapaDiagramasPecasDosNos($entrada, $decl, $modelo, $gerado['ids']) as $no => $pecas) {
                $sublabel = (string) ($nos[$no]['sublabel'] ?? '');
                $tag      = (string) ($nos[$no]['tag'] ?? '');
                foreach ($pecas as $peca) {
                    $quem     = mapaDiagramasNomeDaPeca($modelo, $peca)." ({$peca})";
                    $condicao = mapaDiagramasCondicao($modelo, $peca);
                    if ($condicao === null) {
                        $semCondicao[$peca] = true;
                        if (mapaDiagramasTemMarca($sublabel.' '.$tag)) {
                            $falha($peca, "o nó {$no} de {$quem}, peça sem condição no modelo, traz marca: \"{$sublabel}\"".($tag === '' ? '' : " / tag \"{$tag}\""));
                        }

                        continue;
                    }
                    $marcadas[$peca]   = true;
                    $desenhadas[$peca] = true;
                    if ($escrito) {
                        // No diagrama escrito à mão, a marca vai só no card das condições, e a linha pode começar pelo
                        // rótulo do nó que é a peça: decisão da sessão, medida no archify.
                        $nomesNoCard[$peca][] = (string) ($nos[$no]['label'] ?? '');

                        continue;
                    }
                    $comoNo++;
                    $faltam            = mapaDiagramasFaltaNaMarca($sublabel, $condicao, $idioma);
                    if ($faltam !== []) {
                        $soNoTag = mapaDiagramasFaltaNaMarca($tag, $condicao, $idioma) === [] ? '; a marca está só no tag, que o carregamento esconde' : '';
                        $falha($peca, "o nó {$no} de {$quem} não traz a marca no rótulo secundário \"{$sublabel}\": falta ".implode(', ', $faltam).$soNoTag);
                    }
                }
            }
            $cards = collect($entrada['cards'] ?? []);
            foreach ($ir === null ? [] : ($decl['agrupamentos'] ?? []) as $grupo) {
                $titulo  = mapaDiagramasTituloDoGrupo($grupo, $idioma);
                $linhas  = array_map('strval', $cards->firstWhere('title', $titulo)['items'] ?? []);
                $membros = $ir['nos'][$grupo['id']]['nomeados'] ?? [];
                foreach ($membros as $peca) {
                    $nome     = mapaDiagramasNomeDaPeca($modelo, $peca);
                    $quem     = "{$nome} ({$peca})";
                    $daPeca   = array_values(array_filter($linhas, static fn (string $linha): bool => mapaDiagramasNomeDaLinha($linha) === $nome));
                    $condicao = mapaDiagramasCondicao($modelo, $peca);
                    if ($condicao === null) {
                        $semCondicao[$peca] = true;
                        $homonimas          = count(array_filter($membros, static fn (string $outra): bool => $outra !== $peca && mapaDiagramasNomeDaPeca($modelo, $outra) === $nome && mapaDiagramasCondicao($modelo, $outra) !== null));
                        if (count(array_filter($daPeca, 'mapaDiagramasTemMarca')) > $homonimas) {
                            $falha($peca, "a linha de {$quem} no card \"{$titulo}\" do agrupamento {$grupo['id']}, peça sem condição no modelo, traz marca: \"".implode('", "', $daPeca).'"');
                        }

                        continue;
                    }
                    $comoMembro++;
                    $marcadas[$peca]   = true;
                    $desenhadas[$peca] = true;
                    if (array_filter($daPeca, static fn (string $linha): bool => mapaDiagramasFaltaNaMarca($linha, $condicao, $idioma) === []) === []) {
                        $falha($peca, "a linha de {$quem} no card \"{$titulo}\" do agrupamento {$grupo['id']} não traz a marca: ".($daPeca === [] ? 'a linha não está no card' : "\"{$daPeca[0]}\", falta ".implode(', ', mapaDiagramasFaltaNaMarca($daPeca[0], $condicao, $idioma))));
                    }
                }
            }
            $tituloDasCondicoes = $idioma === 'pt' ? 'Condições de disponibilidade' : 'Availability conditions';
            $card               = $cards->firstWhere('title', $tituloDasCondicoes);
            $linhas             = array_map('strval', $card['items'] ?? []);
            foreach (array_keys($desenhadas) as $peca) {
                $condicao = (array) mapaDiagramasCondicao($modelo, $peca);
                $nome     = mapaDiagramasNomeDaPeca($modelo, $peca);
                $ancora   = [(string) ($condicao['evidencia']['arquivo'] ?? ''), (string) ($condicao['evidencia']['trecho'] ?? '')];
                $faltaNa  = static fn (string $linha): array => [
                    ...mapaDiagramasFaltaNaMarca($linha, $condicao, $idioma),
                    ...array_map(static fn (string $parte): string => "a âncora \"{$parte}\"", array_values(array_filter($ancora, static fn (string $parte): bool => $parte === '' || ! str_contains($linha, $parte)))),
                ];
                $daPeca = array_values(array_filter($linhas, static fn (string $linha): bool => in_array(mapaDiagramasNomeDaLinha($linha), [$nome, ...($nomesNoCard[$peca] ?? [])], true)));
                if ($card === null) {
                    $falha((string) $peca, "desenha {$nome} ({$peca}), peça condicional, e não tem o card \"{$tituloDasCondicoes}\"");
                } elseif (array_filter($daPeca, static fn (string $linha): bool => $faltaNa($linha) === []) === []) {
                    $falha((string) $peca, "a linha de {$nome} ({$peca}) no card \"{$tituloDasCondicoes}\" ".($daPeca === [] ? 'não está no card' : "\"{$daPeca[0]}\" não traz a marca e a âncora da condição dela: falta ".implode(', ', $faltaNa($daPeca[0]))));
                }
            }
            $nomesDesenhados = [...array_map(static fn (string $peca): string => mapaDiagramasNomeDaPeca($modelo, $peca), array_keys($desenhadas)), ...array_merge([], ...array_values($nomesNoCard))];
            foreach ($linhas as $linha) {
                $nome = mapaDiagramasNomeDaLinha($linha);
                if (! in_array($nome, $nomesDesenhados, true)) {
                    foreach ($idsPorNome[$nome] ?? [''] as $peca) {
                        $falha((string) $peca, "o card \"{$tituloDasCondicoes}\" lista {$nome} (\"{$linha}\"), que o diagrama não desenha como peça condicional");
                    }
                }
            }
        }
    }
    $porDiagrama = count(array_unique(array_column($falhas, 'diagrama')));

    return [
        'aceita'      => $falhas === [],
        'motivo'      => $falhas === [] ? 'aceita' : 'reprova: '.count($falhas)." falha(s) em {$porDiagrama} diagrama(s) — ".implode(' | ', array_slice(array_column($falhas, 'texto'), 0, 12)),
        'falhas'      => $falhas,
        'marcadas'    => array_keys($marcadas),
        'comoNo'      => $comoNo,
        'comoMembro'  => $comoMembro,
        'semCondicao' => array_keys($semCondicao),
        'entradas'    => $entradas,
    ];
}

/**
 * A entrada com a marca da peça onde o `04` a quer: no rótulo secundário do nó, na linha dela no card "Quem está em …"
 * de cada agrupamento que a nomeia, e no card "Condições de disponibilidade", com a âncora da condição. As opções
 * tiram um lugar (`no`, `membro` e `condicoes` falsos — o card fica, sem a linha), põem a marca no `tag`, trocam o
 * texto da marca ou a âncora: a linha de controle adultera um aspecto só. No diagrama escrito à mão, o nó não leva
 * a marca, só o card (decisão da sessão, medida no archify).
 *
 * @param  array{no?: bool, tag?: bool, membro?: bool, condicoes?: bool, texto?: string, ancora?: array{arquivo: string, trecho: string}}  $opcoes
 * @param  array<string, string>  $ids
 */
function mapaDiagramasMarcada(array $entrada, string $idioma, array $modelo, array $decl, array $ids, string $peca, array $opcoes = []): array
{
    $condicao    = mapaDiagramasCondicao($modelo, $peca) ?? throw new InvalidArgumentException("a cópia do modelo não dá condição a {$peca}");
    $marca       = $opcoes['texto'] ?? mapaDiagramasMarca($condicao, $idioma);
    $nome        = mapaDiagramasNomeDaPeca($modelo, $peca);
    $ancora      = $opcoes['ancora'] ?? $condicao['evidencia'];
    $acrescentar = static fn (?string $texto): string => ($texto ?? '') === '' ? $marca : "{$texto} · {$marca}";
    $colecao     = mapaDiagramasColecoes()[$entrada['diagram_type']][0];
    $nos         = array_keys(array_filter(mapaDiagramasPecasDosNos($entrada, $decl, $modelo, $ids), static fn (array $pecas): bool => in_array($peca, $pecas, true)));
    foreach ($entrada[$colecao] ?? [] as $i => $no) {
        if (in_array((string) $no['id'], $nos, true)) {
            if ($opcoes['no'] ?? (($decl['construtor'] ?? null) !== null)) {
                $entrada[$colecao][$i]['sublabel'] = $acrescentar($no['sublabel'] ?? null);
            }
            if ($opcoes['tag'] ?? false) {
                $entrada[$colecao][$i]['tag'] = $acrescentar($no['tag'] ?? null);
            }
        }
    }
    $titulos            = ($opcoes['membro'] ?? true) ? array_map(static fn (array $grupo): string => mapaDiagramasTituloDoGrupo($grupo, $idioma), mapaDiagramasGruposDaPeca($decl, $modelo, $peca)) : [];
    $tituloDasCondicoes = $idioma === 'pt' ? 'Condições de disponibilidade' : 'Availability conditions';
    if (collect($entrada['cards'] ?? [])->firstWhere('title', $tituloDasCondicoes) === null) {
        $entrada['cards'][] = ['dot' => 'amber', 'title' => $tituloDasCondicoes, 'items' => []];
    }
    foreach ($entrada['cards'] as $i => $card) {
        if (in_array($card['title'], $titulos, true)) {
            $entrada['cards'][$i]['items'] = array_map(static fn (string $linha): string => mapaDiagramasNomeDaLinha($linha) === $nome ? "{$linha} — {$marca}" : $linha, $card['items']);
        }
        if ($card['title'] === $tituloDasCondicoes && ($opcoes['condicoes'] ?? true)) {
            $entrada['cards'][$i]['items'][] = "{$nome} — {$marca} — {$ancora['arquivo']} — {$ancora['trecho']}";
        }
    }

    return $entrada;
}

/**
 * A entrada sem marca nenhuma, a base das linhas de controle, que adulteram um aspecto só a partir dela: o rótulo
 * secundário do nó de peça com condição perde os pedaços (entre " · ") que trazem marca, a linha de membro marcada
 * volta ao nome e o card "Condições de disponibilidade" sai. Sem a base, a marca que o gerador já escreve esconderia
 * a adulteração.
 *
 * @param  array<string, string>  $ids
 */
function mapaDiagramasSemMarca(array $entrada, array $modelo, array $decl, array $ids): array
{
    $marcado = static fn (string $texto): bool => mapaDiagramasTemMarca($texto) || str_contains($texto, 'de fábrica') || str_contains($texto, 'by default');
    $colecao = mapaDiagramasColecoes()[$entrada['diagram_type'] ?? ''][0] ?? '';
    $pecas   = mapaDiagramasPecasDosNos($entrada, $decl, $modelo, $ids);
    foreach ($entrada[$colecao] ?? [] as $i => $no) {
        $comCondicao = array_filter($pecas[(string) $no['id']] ?? [], static fn (string $peca): bool => mapaDiagramasCondicao($modelo, $peca) !== null);
        if ($comCondicao !== [] && isset($no['sublabel'])) {
            $entrada[$colecao][$i]['sublabel'] = implode(' · ', array_filter(explode(' · ', (string) $no['sublabel']), static fn (string $pedaco): bool => ! $marcado($pedaco)));
        }
    }
    $entrada['cards'] = array_values(array_filter($entrada['cards'] ?? [], static fn (array $card): bool => ! in_array($card['title'] ?? '', ['Condições de disponibilidade', 'Availability conditions'], true)));
    foreach ($entrada['cards'] as $i => $card) {
        if (str_starts_with((string) $card['title'], 'Quem está em ') || str_starts_with((string) $card['title'], 'Who is in ')) {
            $entrada['cards'][$i]['items'] = array_map(static fn (string $linha): string => $marcado($linha) ? mapaDiagramasNomeDaLinha($linha) : $linha, $card['items']);
        }
    }

    return $entrada;
}

it('[CT-57] em todo diagrama que a desenha, a peça condicional leva a marca da condição, no idioma do diagrama e à vista no carregamento, e a peça sem condição não leva marca', function (string $mundo): void {
    expect(versaoDoNode())->toMatch('/^v\d+\.\d+\.\d+/');

    $modelo  = modeloDoMapaPorId();
    $decls   = array_filter(diagramasDoMapa(), static fn (array $decl): bool => $decl['tipo'] !== 'compare');
    $gerado  = entradasDoGeradorDoMapa();
    $ids     = $gerado['ids'];
    $agente  = 'App\\Filament\\Admin\\Resources\\AgentesIa\\AgenteIaResource';
    $hub     = 'App\\Filament\\Admin\\Pages\\HubDeAdministracao';
    $projeto = 'App\\Filament\\App\\Resources\\Projetos\\ProjetoResource';
    $userApp = 'App\\Filament\\App\\Resources\\Users\\UserResource';
    $command = 'Bityukov\\CommandCenter\\Filament\\Resources\\CommandRecordResource';
    $chat    = 'App\\Livewire\\AssistenteChatWidget';
    $dashAdm = 'App\\Filament\\Admin\\Pages\\Dashboard';
    $dashApp = 'App\\Filament\\App\\Pages\\Dashboard';
    $dashInf = 'App\\Filament\\Infra\\Pages\\Dashboard';
    $dinamic = 'MDDev\\DynamicDashboard\\Pages\\DynamicDashboard';
    $plugin  = 'Harvirsidhu\\FilamentCards\\FilamentCardsPlugin';
    expect(mapaDiagramasIdsPorNome($modelo)['AgenteIa'] ?? [])->toHaveCount(1);
    $agenteIa = mapaDiagramasIdsPorNome($modelo)['AgenteIa'][0];

    if ($mundo === 'publicados') {
        $resultado = mapaDiagramasConferirMarcas($modelo, $decls, $gerado);

        expect(array_keys($gerado['diagramas']))->toEqualCanonicalizing(array_keys($decls))
            ->and($resultado['semCondicao'])->toContain($plugin)
            ->and($resultado['motivo'])->toBe('aceita')
            ->and($resultado['entradas'])->toBe(2 * count($decls))
            ->and($resultado['marcadas'])->toContain($command, 'App\\Filament\\Admin\\Resources\\Tenants\\Widgets\\AcessosPorPainel', $chat, 'App\\Livewire\\ChecklistDoOnboarding')
            ->and($resultado['comoNo'])->toBeGreaterThan(0)
            ->and($resultado['comoMembro'])->toBeGreaterThan(0);

        return;
    }

    if (in_array($mundo, ['a cópia do admin-convite com o participante ConviteResource de prova do /admin, sem linha no card', 'a mesma cópia, com a linha do ConviteResource do /app no card', 'a cópia do admin-convite com o participante ConviteResource sem prova, sem linha no card', 'a cópia do admin-convite com o participante ConviteResource sem prova, com a linha do ConviteResource do /app no card'], true)) {
        // D43 (Setup Global do `04`): a prova do participante vem antes do nome, e com homônimas e sem prova o nó não designa peça.
        $slug         = 'admin-convite';
        $adminConvite = 'App\\Filament\\Admin\\Resources\\Convites\\ConviteResource';
        $appConvite   = 'App\\Filament\\App\\Resources\\Convites\\ConviteResource';
        $comProva     = ! in_array($mundo, ['a cópia do admin-convite com o participante ConviteResource sem prova, sem linha no card', 'a cópia do admin-convite com o participante ConviteResource sem prova, com a linha do ConviteResource do /app no card'], true);
        $copiaDecl    = $decls[$slug];
        $participante = ['id' => 'conviteresource', 'type' => 'frontend', 'label' => 'ConviteResource', 'sublabel' => ['pt' => 'recurso', 'en' => 'resource']];
        if ($comProva) {
            $participante['sources'] = [['arquivo' => 'app/Filament/Admin/Resources/Convites/ConviteResource.php', 'trecho' => 'class ConviteResource extends Resource', 'label' => 'ConviteResource']];
        }
        $copiaDecl['participants'][] = $participante;
        $entrada                     = entradasDoGeradorDoMapa(['extras' => [$copiaDecl], 'soExtras' => true, 'idiomas' => ['pt']])['diagramas'][$slug]['pt'] ?? [];
        expect($entrada)->not->toHaveKey('erro')->and($entrada['participants'] ?? [])->not->toBeEmpty();
        $conferirConvite = static fn (array $doConvite): array => mapaDiagramasConferirMarcas($modelo, [$slug => $copiaDecl], ['ids' => $ids, 'diagramas' => [$slug => ['pt' => $doConvite]]]);

        expect(mapaDiagramasPecasDosNos($entrada, $copiaDecl, $modelo, $ids)['conviteresource'] ?? [])->toBe($comProva ? [$adminConvite] : []);
        // Sem a linha do ConviteResource no card: aceita (e, no mundo com a linha, é o controle positivo dele).
        expect($conferirConvite($entrada)['motivo'])->toBe('aceita');
        if (! in_array($mundo, ['a mesma cópia, com a linha do ConviteResource do /app no card', 'a cópia do admin-convite com o participante ConviteResource sem prova, com a linha do ConviteResource do /app no card'], true)) {
            return;
        }

        $entrada['cards'][] = ['dot' => 'amber', 'title' => 'Condições de disponibilidade', 'items' => ['ConviteResource: só com tenant ativo — app/Filament/App/Resources/Convites/ConviteResource.php — …']];
        $resultado          = $conferirConvite($entrada);

        expect($resultado['aceita'])->toBeFalse()
            ->and($resultado['motivo'])->toContain($slug, 'ConviteResource');
        // D44: com a prova, a linha é de peça que o diagrama não desenha e a falha nomeia a do /app; sem prova, o nó não designa peça e a falha nomeia a linha.
        if ($comProva) {
            expect(array_column($resultado['falhas'], 'peca'))->toContain($appConvite);
        }

        return;
    }

    [$slug, $idioma, $peca, $comCondicao] = match ($mundo) {
        'a entrada do admin-administracao, com o nó do AgenteIaResource sem a marca',
        'a mesma entrada, com a marca do AgenteIaResource só no tag'                                                         => ['admin-administracao', 'pt', $agente, [$agente]],
        'a entrada do admin-resumo-sem, com a linha do AgenteIaResource no card do agrupamento de administração sem a marca',
        'a entrada do admin-resumo-sem, com o card Condições de disponibilidade sem a linha do AgenteIaResource'                                                                                                                           => ['admin-resumo-sem', 'pt', $agente, [$agente]],
        'a entrada en do app-registro-com, com a marca do ProjetoResource só com o modo'                                                                                                                                                   => ['app-registro-com', 'en', $projeto, [$projeto]],
        'a entrada en do app-registro-com, com a marca de modo do UserResource do /app em pt'                                                                                                                                              => ['app-registro-com', 'en', $userApp, [$userApp]],
        'a entrada do admin-acesso-e-autenticacao, com a marca de modo do UserResource do /app no nó do UserResource do /admin'                                                                                                            => ['admin-acesso-e-autenticacao', 'pt', 'App\\Filament\\Admin\\Resources\\Users\\UserResource', [$userApp]],
        'a entrada do admin-registro-com, com a marca KIT_HUB=false de fábrica no nó do FilamentCardsPlugin'                                                                                                                               => ['admin-registro-com', 'pt', $plugin, [$hub]],
        'a entrada do infra-infraestrutura-2, com a marca do CommandRecordResource sem o caminho command-center.sources'                                                                                                                   => ['infra-infraestrutura-2', 'pt', $command, [$command]],
        'a entrada do app-registro-sem, com o card listando o UserResource do /app, que o diagrama não desenha'                                                                                                                            => ['app-registro-sem', 'pt', $userApp, [$userApp]],
        'a entrada do admin-administracao, com a linha do AgenteIaResource no card trazendo a âncora do HubDeAdministracao'                                                                                                                => ['admin-administracao', 'pt', $agente, [$agente, $hub]],
        'a entrada do app-chat-de-ia, com o card Condições de disponibilidade sem a linha do chat, que o participante desenha pela prova'                                                                                                  => ['app-chat-de-ia', 'pt', $chat, [$chat]],
        'a entrada de uma cópia do app-chat-de-ia com a prova do participante abreviada para class AssistenteChatWidget, e o card sem a linha do chat'                                                                                     => ['app-chat-de-ia', 'pt', $chat, [$chat]],
        'a entrada en do kit-ia, com a marca do chat KIT_AGENTES_IA=false · KIT_AGENTES_IA_PAINEIS vazia by default'                                                                                                                       => ['kit-ia', 'en', $chat, [$chat]],
        'a entrada do admin-administracao, com KIT_AGENTES_IA_PAINEIS vazia de fábrica no nó do model AgenteIa, que não tem condição', 'a mesma entrada, com command-center.enabled sem DatabaseSource de fábrica no nó do model AgenteIa' => ['admin-administracao', 'pt', $agenteIa, []],
        'a entrada do infra-produtividade-e-ui, compartilhado, com o Dashboard do /infra condicional e o nó dele sem a marca'                                                                                                              => ['infra-produtividade-e-ui', 'pt', $dashInf, [$dashInf]],
        'a entrada do app-resumo-sem, com o Dashboard do /app condicional e a linha dele no card do agrupamento sem a marca'                                                                                                               => ['app-resumo-sem', 'pt', $dashApp, [$dashApp]],
        'a entrada do admin-registro-sem, com o Dashboard do /admin condicional e o card Condições de disponibilidade sem a linha dele'                                                                                                    => ['admin-registro-sem', 'pt', $dashAdm, [$dashAdm]],
        'a entrada do admin-produtividade-e-ui, com o DynamicDashboard do pacote condicional e o nó dele sem a marca'                                                                                                                      => ['admin-produtividade-e-ui', 'pt', $dinamic, [$dinamic]],
        default                                                                                                                                                                                                                            => ['kit-ia', 'pt', $chat, [$chat]],
    };
    if ($mundo === 'a entrada de uma cópia do app-chat-de-ia com a prova do participante abreviada para class AssistenteChatWidget, e o card sem a linha do chat') {
        // D43: a prova do participante do chat, abreviada, declara a classe no arquivo da evidência; o gerador monta a cópia.
        foreach ($decls[$slug]['participants'] as $i => $participante) {
            if ($participante['id'] === 'widget') {
                $decls[$slug]['participants'][$i]['sources'][0]['trecho'] = 'class AssistenteChatWidget';
            }
        }
        $gerado['diagramas'][$slug][$idioma] = entradasDoGeradorDoMapa(['extras' => [$decls[$slug]], 'soExtras' => true, 'idiomas' => [$idioma]])['diagramas'][$slug][$idioma] ?? [];
        expect($gerado['diagramas'][$slug][$idioma])->not->toHaveKey('erro');
    }
    $copia     = mapaDiagramasModeloComCondicoes($modelo, $comCondicao);
    $publicada = $gerado['diagramas'][$slug][$idioma];
    $marcada   = static fn (array $opcoes = [], ?array $entrada = null): array => mapaDiagramasMarcada($entrada ?? mapaDiagramasSemMarca($publicada, $copia, $decls[$slug], $ids), $idioma, $copia, $decls[$slug], $ids, $peca, $opcoes);
    $comMarca  = static function (string $marca) use ($publicada, $ids, $peca): array {
        $colecao = mapaDiagramasColecoes()[$publicada['diagram_type']][0];
        expect(collect($publicada[$colecao] ?? [])->pluck('id')->all())->toContain($ids[$peca]);
        foreach ($publicada[$colecao] as $i => $no) {
            if ($no['id'] === $ids[$peca]) {
                $publicada[$colecao][$i]['sublabel'] = trim(($no['sublabel'] ?? '').' · '.$marca);
            }
        }

        return $publicada;
    };
    $semOUserApp = $publicada;
    if (isset($semOUserApp['nodes'])) {
        $semOUserApp['nodes'] = array_values(array_filter($semOUserApp['nodes'], static fn (array $no): bool => $no['id'] !== $ids[$userApp]));
    }

    [$certo, $adulterado, $nomeia] = match ($mundo) {
        'a entrada do admin-administracao, com o nó do AgenteIaResource sem a marca'                                                                                                        => [$marcada(), $marcada(['no' => false]), [$slug, 'AgenteIaResource']],
        'a mesma entrada, com a marca do AgenteIaResource só no tag'                                                                                                                        => [$marcada(), $marcada(['no' => false, 'tag' => true]), [$slug, 'AgenteIaResource', 'tag']],
        'a entrada do admin-resumo-sem, com a linha do AgenteIaResource no card do agrupamento de administração sem a marca'                                                                => [$marcada(), $marcada(['membro' => false]), [$slug, 'AgenteIaResource', ...array_column(mapaDiagramasGruposDaPeca($decls[$slug], $copia, $peca), 'id')]],
        'a entrada do admin-resumo-sem, com o card Condições de disponibilidade sem a linha do AgenteIaResource'                                                                            => [$marcada(), $marcada(['condicoes' => false]), [$slug, 'AgenteIaResource']],
        'a entrada en do app-registro-com, com a marca do ProjetoResource só com o modo'                                                                                                    => [$marcada(), $marcada(['texto' => 'tenancy on only']), ['ProjetoResource', 'KIT_DEMO=false by default']],
        'a entrada en do app-registro-com, com a marca de modo do UserResource do /app em pt'                                                                                               => [$marcada(), $marcada(['texto' => 'só com tenant ativo']), [$slug, $userApp]],
        'a entrada do admin-acesso-e-autenticacao, com a marca de modo do UserResource do /app no nó do UserResource do /admin'                                                             => [$publicada, $comMarca('só com tenant ativo'), [$peca]],
        'a entrada do admin-registro-com, com a marca KIT_HUB=false de fábrica no nó do FilamentCardsPlugin'                                                                                => [$publicada, $comMarca('KIT_HUB=false de fábrica'), ['FilamentCardsPlugin']],
        'a entrada do infra-infraestrutura-2, com a marca do CommandRecordResource sem o caminho command-center.sources'                                                                    => [$marcada(), $marcada(['texto' => 'DatabaseSource de fábrica']), ['CommandRecordResource', 'command-center.sources']],
        'a entrada do app-registro-sem, com o card listando o UserResource do /app, que o diagrama não desenha'                                                                             => [$semOUserApp, $marcada([], $semOUserApp), [$slug, 'UserResource']],
        'a entrada do admin-administracao, com a linha do AgenteIaResource no card trazendo a âncora do HubDeAdministracao'                                                                 => [$marcada(), $marcada(['ancora' => $copia['pecas'][$hub]['disponibilidade']['evidencia']]), ['AgenteIaResource']],
        'a entrada do app-chat-de-ia, com o card Condições de disponibilidade sem a linha do chat, que o participante desenha pela prova'                                                   => [$marcada(), $marcada(['condicoes' => false]), [$slug, 'AssistenteChatWidget']],
        'a entrada de uma cópia do app-chat-de-ia com a prova do participante abreviada para class AssistenteChatWidget, e o card sem a linha do chat'                                      => [$marcada(), $marcada(['condicoes' => false]), [$slug, 'AssistenteChatWidget']],
        'a entrada do kit-ia, com a marca do chat sem o vazia: KIT_AGENTES_IA=false · KIT_AGENTES_IA_PAINEIS de fábrica'                                                                    => [$marcada(), $marcada(['texto' => 'KIT_AGENTES_IA=false · KIT_AGENTES_IA_PAINEIS de fábrica']), ['AssistenteChatWidget', 'KIT_AGENTES_IA_PAINEIS vazia']],
        'a entrada en do kit-ia, com a marca do chat KIT_AGENTES_IA=false · KIT_AGENTES_IA_PAINEIS vazia by default'                                                                        => [$marcada(), $marcada(['texto' => 'KIT_AGENTES_IA=false · KIT_AGENTES_IA_PAINEIS vazia by default']), ['AssistenteChatWidget', 'KIT_AGENTES_IA_PAINEIS empty']],
        'a entrada do admin-administracao, com KIT_AGENTES_IA_PAINEIS vazia de fábrica no nó do model AgenteIa, que não tem condição'                                                       => [$publicada, $comMarca('KIT_AGENTES_IA_PAINEIS vazia de fábrica'), ['AgenteIa']],
        'a mesma entrada, com command-center.enabled sem DatabaseSource de fábrica no nó do model AgenteIa'                                                                                 => [$publicada, $comMarca('command-center.enabled sem DatabaseSource de fábrica'), ['AgenteIa']],
        'a entrada do infra-produtividade-e-ui, compartilhado, com o Dashboard do /infra condicional e o nó dele sem a marca'                                                               => [$marcada(), $marcada(['no' => false]), [$slug, $dashInf]],
        'a entrada do app-resumo-sem, com o Dashboard do /app condicional e a linha dele no card do agrupamento sem a marca'                                                                => [$marcada(), $marcada(['membro' => false]), [$slug, $dashApp, ...array_column(mapaDiagramasGruposDaPeca($decls[$slug], $copia, $peca), 'id')]],
        'a entrada do admin-registro-sem, com o Dashboard do /admin condicional e o card Condições de disponibilidade sem a linha dele'                                                     => [$marcada(), $marcada(['condicoes' => false]), [$slug, $dashAdm]],
        'a entrada do admin-produtividade-e-ui, com o DynamicDashboard do pacote condicional e o nó dele sem a marca'                                                                       => [$marcada(), $marcada(['no' => false]), [$slug, 'DynamicDashboard']],
        default                                                                                                                                                                             => [$marcada(), $marcada(['texto' => 'KIT_AGENTES_IA=false de fábrica']), ['AssistenteChatWidget', 'KIT_AGENTES_IA_PAINEIS']],
    };
    $conferir = static fn (array $entrada): array => mapaDiagramasConferirMarcas($copia, [$slug => $decls[$slug]], ['ids' => $ids, 'diagramas' => [$slug => [$idioma => $entrada]]]);
    $daPeca   = static fn (array $resultado): array => array_column(array_filter($resultado['falhas'], static fn (array $falha): bool => $falha['peca'] === $peca), 'texto');

    // Controle positivo: com a marca onde o 04 a quer, a guarda não acusa a peça — o veredito abaixo vem da adulteração.
    expect($daPeca($conferir($certo)))->toBe([]);
    $resultado = $conferir($adulterado);

    expect($resultado['aceita'])->toBeFalse()
        ->and($daPeca($resultado))->not->toBeEmpty()
        ->and(implode("\n", $daPeca($resultado)))->toContain(...$nomeia);
})->with([
    'publicados',
    'a entrada do admin-administracao, com o nó do AgenteIaResource sem a marca',
    'a mesma entrada, com a marca do AgenteIaResource só no tag',
    'a entrada do admin-resumo-sem, com a linha do AgenteIaResource no card do agrupamento de administração sem a marca',
    'a entrada en do app-registro-com, com a marca do ProjetoResource só com o modo',
    'a entrada en do app-registro-com, com a marca de modo do UserResource do /app em pt',
    'a entrada do admin-acesso-e-autenticacao, com a marca de modo do UserResource do /app no nó do UserResource do /admin',
    'a entrada do admin-registro-com, com a marca KIT_HUB=false de fábrica no nó do FilamentCardsPlugin',
    'a entrada do infra-infraestrutura-2, com a marca do CommandRecordResource sem o caminho command-center.sources',
    'a entrada do admin-resumo-sem, com o card Condições de disponibilidade sem a linha do AgenteIaResource',
    'a entrada do app-registro-sem, com o card listando o UserResource do /app, que o diagrama não desenha',
    'a entrada do admin-administracao, com a linha do AgenteIaResource no card trazendo a âncora do HubDeAdministracao',
    'a entrada do app-chat-de-ia, com o card Condições de disponibilidade sem a linha do chat, que o participante desenha pela prova',
    'a entrada do kit-ia, com a marca do chat só com KIT_AGENTES_IA=false de fábrica',
    'a entrada do kit-ia, com a marca do chat sem o vazia: KIT_AGENTES_IA=false · KIT_AGENTES_IA_PAINEIS de fábrica',
    'a entrada en do kit-ia, com a marca do chat KIT_AGENTES_IA=false · KIT_AGENTES_IA_PAINEIS vazia by default',
    'a entrada do admin-administracao, com KIT_AGENTES_IA_PAINEIS vazia de fábrica no nó do model AgenteIa, que não tem condição',
    'a mesma entrada, com command-center.enabled sem DatabaseSource de fábrica no nó do model AgenteIa',
    'a entrada de uma cópia do app-chat-de-ia com a prova do participante abreviada para class AssistenteChatWidget, e o card sem a linha do chat',
    'a cópia do admin-convite com o participante ConviteResource de prova do /admin, sem linha no card',
    'a mesma cópia, com a linha do ConviteResource do /app no card',
    'a cópia do admin-convite com o participante ConviteResource sem prova, sem linha no card',
    'a cópia do admin-convite com o participante ConviteResource sem prova, com a linha do ConviteResource do /app no card',
    'a entrada do infra-produtividade-e-ui, compartilhado, com o Dashboard do /infra condicional e o nó dele sem a marca',
    'a entrada do app-resumo-sem, com o Dashboard do /app condicional e a linha dele no card do agrupamento sem a marca',
    'a entrada do admin-registro-sem, com o Dashboard do /admin condicional e o card Condições de disponibilidade sem a linha dele',
    'a entrada do admin-produtividade-e-ui, com o DynamicDashboard do pacote condicional e o nó dele sem a marca',
]);

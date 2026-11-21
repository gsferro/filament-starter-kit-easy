<?php

/**
 * Guardas dos diagramas e da página do mapa interativo — costura G2, metade dos diagramas.
 *
 * Fonte: `wikis/specs/feat/mapa-interativo-do-kit/mapa-interativo-do-kit/04-casos-de-teste.md`
 * (Setup Global e as regras R6, R12, R15 a R20, R22, R29 e, da R34, só o CT-37). Escrito a partir do
 * Gherkin do `04`, nunca do plano: `site/mapa/`, `docs/` e `app/` só foram lidos para achar NOMES
 * (arquivo, campo, classe), nunca para decidir o que é esperado.
 *
 * Lote: CT-06, CT-14, CT-15, CT-16, CT-17, CT-18, CT-19, CT-20, CT-21, CT-22, CT-24, CT-31, CT-36,
 * CT-37 e CT-43. O CT-44 é da suíte Tenancy e o CT-38 também: ficam fora deste arquivo.
 *
 * Toda guarda é uma função aplicada ao modelo publicado e a cópias adulteradas EM MEMÓRIA, nunca
 * ao arquivo real: a linha "publicados" e as linhas de controle chamam a MESMA função.
 *
 * Helpers: usados só por este arquivo, com o prefixo `mapaDiagramas`.
 */

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function (): void {
    if (! naArvoreDoKit()) {
        test()->markTestSkipped('As guardas do mapa leem site/, docs/ e o CHANGELOG, que não viajam no projeto instalado (.gitattributes: export-ignore).');
    }
});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

if (! function_exists('mapaDiagramasModelo')) {
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

    /** @return array<string, mixed> */
    function mapaDiagramasLerJson(string $relativo): array
    {
        return json_decode((string) file_get_contents(base_path($relativo)), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * O modelo publicado: peças por id e ligações.
     *
     * @return array{pecas: array<string, array<string, mixed>>, ligacoes: list<array<string, mixed>>}
     */
    function mapaDiagramasModelo(): array
    {
        static $modelo = null;

        return $modelo ??= (function (): array {
            $pecas = [];
            foreach (mapaDiagramasLerJson('site/mapa/pecas.json') as $peca) {
                $pecas[$peca['id']] = $peca;
            }

            return ['pecas' => $pecas, 'ligacoes' => mapaDiagramasLerJson('site/mapa/ligacoes.json')];
        })();
    }

    /** @return array<string, array<string, mixed>> os arquivos de `site/mapa/diagramas/`, por slug */
    function mapaDiagramasDecls(): array
    {
        static $decls = null;

        return $decls ??= (function (): array {
            $saida    = [];
            $arquivos = glob(base_path('site/mapa/diagramas/*.json')) ?: [];
            sort($arquivos);
            foreach ($arquivos as $arquivo) {
                $decl                 = json_decode((string) file_get_contents($arquivo), true, 512, JSON_THROW_ON_ERROR);
                $saida[$decl['slug']] = $decl;
            }

            return $saida;
        })();
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
            $modo             = $ir['modos'][0];
            $ir['nos'][$raiz] = mapaDiagramasNo('peca', mapaDiagramasNomeDaPeca($modelo, $raiz), [$raiz]);
            foreach ($modelo['ligacoes'] as $ligacao) {
                if ($ligacao['de'] === $raiz && $ligacao['verbo'] === 'registra' && in_array($modo, $ligacao['modos'], true)) {
                    $ir['nos'][$ligacao['para']] = mapaDiagramasNo('peca', mapaDiagramasNomeDaPeca($modelo, $ligacao['para']), [$ligacao['para']]);
                    $ir['arestas'][]             = [$raiz, $ligacao['para']];
                    $ir['representadas'][]       = $ligacao['de'].'|'.$ligacao['para'].'|'.$ligacao['verbo'];
                }
            }

            return $ir;
        }

        if ($ir['construtor'] === 'mapa') {
            $modo       = count($ir['modos']) === 1 ? $ir['modos'][0] : null;
            $existe     = static fn (string $id): bool => $modo === null || in_array($modo, $pecas[$id]['registrada'] ?? [], true);
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
                if ($modo !== null && ! in_array($modo, $ligacao['modos'], true)) {
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

        $porNome  = mapaDiagramasIdsPorNome($modelo);
        $colecoes = [
            'workflow'  => ['nodes', 'edges'], 'sequence' => ['participants', 'messages'], 'dataflow' => ['nodes', 'flows'],
            'lifecycle' => ['states', 'transitions'], 'erd' => ['entities', 'relationships'], 'class' => ['types', 'relationships'],
            'timeline'  => ['events', null], 'waterfall' => ['spans', null], 'architecture' => ['components', 'connections'],
            'tree'      => ['nodes', null],
        ];
        [$chaveDosNos, $chaveDasArestas] = $colecoes[$decl['tipo']] ?? [null, null];
        $ir['estrutura']                 = md5((string) json_encode([$decl[$chaveDosNos] ?? [], $chaveDasArestas !== null ? ($decl[$chaveDasArestas] ?? []) : []]));
        foreach ($decl[$chaveDosNos] ?? [] as $no) {
            $rotuloPt = mapaDiagramasPt($no['label'] ?? $no['name'] ?? '');
            $rotuloEn = mapaDiagramasEn($no['label'] ?? $no['name'] ?? '');
            $ids      = [];
            if (isset($pecas[$no['id']])) {
                $ids[] = $no['id'];
            }
            foreach ([$rotuloPt, $rotuloEn] as $rotulo) {
                foreach ($porNome[$rotulo] ?? [] as $id) {
                    $ids[] = $id;
                }
            }
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
        $decls = mapaDiagramasDecls();
        $irs   = [];
        foreach ($decls as $slug => $decl) {
            $irs[$slug] = mapaDiagramasConstruir($decl, $modelo);
        }

        return ['modelo' => $modelo, 'decls' => $decls, 'irs' => $irs];
    }

    function mapaDiagramasMundo(): array
    {
        static $mundo = null;

        return $mundo ??= mapaDiagramasMundoCom(mapaDiagramasModelo());
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

    /** Motivo de enfeite (Setup Global): vazio, só de travessão ou pontuação, ou "não se aplica" sem porquê. */
    function mapaDiagramasMotivoDeEnfeite(string $motivo): bool
    {
        $limpo = trim((string) preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $motivo));
        $limpo = mb_strtolower((string) preg_replace('/\s+/u', ' ', $limpo));

        return $limpo === '' || in_array($limpo, ['não se aplica', 'nao se aplica', 'not applicable', 'does not apply', 'n a'], true);
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
                if (mapaDiagramasMotivoDeEnfeite($motivo)) {
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
     * O catálogo de um idioma (ou de um texto): uma linha por F-nn.
     *
     * @return array<string, array{feature: string, origem: string, pacotes: list<string>, onde: string, removida: bool, motivo: string}>
     */
    function mapaDiagramasLinhasDoCatalogo(string $markdown): array
    {
        $saida = [];
        foreach (preg_split('/\R/', $markdown) as $linha) {
            if (preg_match('/^\|\s*(F-\d+[a-z]?)\s*\|/u', $linha, $m) !== 1) {
                continue;
            }
            $celulas = mapaDiagramasCelulasDaLinha($linha);
            $origem  = mb_strtolower($celulas[2] ?? '');
            $tipo    = str_contains($origem, 'customiza') ? 'custom' : ((str_starts_with($origem, 'pacote') || str_starts_with($origem, 'package')) ? 'pacote' : 'proprio');
            preg_match_all('/`([^`]+)`/u', $celulas[2] ?? '', $pacotes);
            $removida     = preg_match('/removid[ao]|\bremoved\b/iu', $celulas[1] ?? '') === 1;
            $saida[$m[1]] = [
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

    /** @return array<string, array{feature: string, origem: string, pacotes: list<string>, onde: string, removida: bool, motivo: string}> */
    function mapaDiagramasCatalogo(string $idioma): array
    {
        static $cache = [];

        return $cache[$idioma] ??= mapaDiagramasLinhasDoCatalogo((string) file_get_contents(base_path("docs/{$idioma}/operacao/roteiro-de-features.md")));
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

    function mapaDiagramasArquivoDaClasse(string $id): ?string
    {
        static $cache = [];
        if (array_key_exists($id, $cache)) {
            return $cache[$id];
        }
        $arquivo = null;
        if (! str_contains($id, ':')) {
            try {
                if (class_exists($id) || interface_exists($id) || trait_exists($id) || enum_exists($id)) {
                    $arquivo = str_replace('\\', '/', (string) (new ReflectionClass($id))->getFileName());
                }
            } catch (Throwable) {
            }
        }

        return $cache[$id] = $arquivo;
    }

    function mapaDiagramasPecaEhDoPacote(string $id, string $pacote): bool
    {
        $arquivo = mapaDiagramasArquivoDaClasse($id);

        return $arquivo !== null && str_contains($arquivo, '/vendor/'.$pacote.'/');
    }

    function mapaDiagramasPecaEhDoKit(array $modelo, string $id): bool
    {
        $arquivo = mapaDiagramasArquivoDaClasse($id);
        if ($arquivo === null) {
            // painel:{id} e o alias `panel:{id}` não são classe: valem pelo arquivo da evidência, que é do kit.
            $evidencia = $modelo['pecas'][$id]['evidencia'][0]['arquivo'] ?? '';

            return $evidencia !== '' && ! str_starts_with($evidencia, 'vendor/');
        }

        return ! str_contains($arquivo, '/vendor/') && ! str_contains($arquivo, '/node_modules/');
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
    function mapaDiagramasConferirOrigem(array $catalogo, array $modelo, array $desenhadas, array $paineis, ?array $excecoes = null): array
    {
        $excecoes ??= mapaDiagramasExcecoesDeOrigem();
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

    /* ---------- R34: o registro do painel ---------- */

    /** @return list<string> o que o painel registra, lido do registro vivo do Filament */
    function mapaDiagramasRegistroVivo(Panel $painel): array
    {
        $ids    = [];
        $classe = static function (mixed $valor) use (&$ids): void {
            if ($valor instanceof WidgetConfiguration) {
                $valor = $valor->widget;
            }
            if (is_string($valor) && $valor !== '') {
                $ids[] = $valor;
            }
        };
        foreach ($painel->getResources() as $recurso) {
            $classe($recurso);
        }
        foreach ($painel->getPages() as $pagina) {
            $classe($pagina);
        }
        foreach ($painel->getWidgets() as $widget) {
            $classe($widget);
        }
        foreach ([
            $painel->getLoginRouteAction(), $painel->getRegistrationRouteAction(), $painel->getRequestPasswordResetRouteAction(),
            $painel->getResetPasswordRouteAction(), $painel->getEmailVerificationPromptRouteAction(), $painel->getProfilePage(),
        ] as $pagina) {
            if (is_string($pagina) && class_exists($pagina)) {
                $classe($pagina);
            }
        }
        if ($painel->hasTenancy()) {
            foreach ([$painel->getTenantRegistrationPage(), $painel->getTenantProfilePage()] as $pagina) {
                $classe($pagina);
            }
        }
        foreach ($painel->getPlugins() as $plugin) {
            $ids[] = $plugin::class;
        }
        $middlewares = [...$painel->getMiddleware(), ...$painel->getAuthMiddleware()];
        if ($painel->hasTenancy()) {
            $middlewares = [...$middlewares, ...$painel->getTenantMiddleware()];
        }
        foreach ($middlewares as $middleware) {
            if (is_string($middleware)) {
                $ids[] = str_starts_with($middleware, 'panel:') ? $middleware : explode(':', $middleware)[0];
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * R34: os diagramas `architecture` e a árvore de registro do painel que valem no modo desenham, juntos, o
     * registro dele nesse modo, nem mais nem menos (fora a peça de disponibilidade condicional).
     *
     * @param  array<string, array>  $irs
     * @param  list<string>  $vivo
     * @param  list<string>  $condicionais
     * @return array{aceita: bool, motivo: string, registro: int}
     */
    function mapaDiagramasConferirRegistro(array $modelo, array $irs, string $painel, string $modo, array $vivo, array $condicionais): array
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
        $esperado  = array_values(array_diff($vivo, $condicionais));
        $desenhado = array_values(array_diff(array_keys($desenhado), $condicionais));
        $faltam    = array_diff($esperado, $desenhado);
        $sobram    = array_diff($desenhado, $esperado);
        $falhas    = [];
        foreach ($faltam as $id) {
            $falhas[] = 'falta no desenho do painel '.$painel.': '.mapaDiagramasNomeDaPeca($modelo, $id)." ({$id})";
        }
        foreach ($sobram as $id) {
            $falhas[] = 'sobra no desenho do painel '.$painel.', que não o registra no modo '.$modo.': '.mapaDiagramasNomeDaPeca($modelo, $id)." ({$id})";
        }

        return [
            'aceita'   => $falhas === [],
            'motivo'   => $falhas === [] ? 'aceita' : 'reprova, painel '.$painel.', '.count($falhas).' peça(s): '.implode(' | ', array_slice($falhas, 0, 15)),
            'registro' => count($esperado),
        ];
    }
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
    $catalogo = mapaDiagramasCatalogo('pt');
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
    $catalogo   = mapaDiagramasCatalogo('pt');
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
            $catalogo                 = mapaDiagramasCatalogo('en');
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
    foreach ($irs['admin-resumo']['nos'] as $id => $no) {
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
    $soAdmin = ['admin-resumo' => $irs['admin-resumo']];

    if ($mundoDeControle === 'publicados') {
        $resultado = mapaDiagramasConferirLigacoes($modelo, $irs, $publicados);
        expect($resultado['motivo'])->toBe('aceita')
            ->and($resultado['achadas'])->toBe(count($modelo['ligacoes']));
        expect(mapaDiagramasConferirLigacoes($isolado, $soAdmin, ['admin-resumo'])['motivo'])->toBe('aceita');

        return;
    }

    $nomeia = [];
    switch ($mundoDeControle) {
        case 'só no texto da peça':
            $irs                                                   = mapaDiagramasSemDesenharAPar($irs, $shield['de'], $shield['para']);
            $irs['admin-registro']['nos']['painel:admin']['texto'] = 'o painel admin registra FilamentShieldPlugin';
            $resultado                                             = mapaDiagramasConferirLigacoes($modelo, $irs, $publicados);
            $nomeia                                                = ['FilamentShieldPlugin', 'painel:admin'];
            break;
        case 'agrupamento sem membros':
            $soAdmin['admin-resumo']['nos'][$grupo]['nomeados'] = [];
            $resultado                                          = mapaDiagramasConferirLigacoes($isolado, $soAdmin, ['admin-resumo']);
            $nomeia                                             = [$grupo];
            break;
        case 'membros só no texto secundário':
            $soAdmin['admin-resumo']['nos'][$grupo]['nomeados'] = [];
            $soAdmin['admin-resumo']['nos'][$grupo]['texto']    = implode(', ', array_map(
                static fn (string $id): string => mapaDiagramasNomeDaPeca($modelo, $id),
                $soAdmin['admin-resumo']['nos'][$grupo]['membros'],
            ));
            $resultado = mapaDiagramasConferirLigacoes($isolado, $soAdmin, ['admin-resumo']);
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
    }

    expect($resultado['aceita'])->toBeFalse();
    foreach ($nomeia as $trecho) {
        expect($resultado['motivo'])->toContain($trecho);
    }
})->with([
    'publicados', 'só no texto da peça', 'agrupamento sem membros', 'membros só no texto secundário', 'porta sem os membros', 'ausente', 'diagrama órfão',
]);

/*
|--------------------------------------------------------------------------
| R15 — a página leva a todo diagrama do idioma (CT-15)
|--------------------------------------------------------------------------
*/

it('[CT-15] a página de cada idioma leva, por <a href> relativo, a todo diagrama do seu idioma, e só a diagrama que existe', function (string $idioma): void {
    $slugsDosArquivos = array_keys(mapaDiagramasDecls());
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
    $modelo    = mapaDiagramasModelo();
    $glossario = mapaDiagramasGlossario();
    $decls     = mapaDiagramasDecls();

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
        mapaDiagramasFalhasDaGrade($grade, array_keys(Filament::getPanels()), mapaDiagramasDecls()),
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

    $falhas       = mapaDiagramasFalhasDaGrade(mapaDiagramasGrade('pt'), $paineis, mapaDiagramasDecls());
    $doFinanceiro = array_values(array_filter($falhas, static fn (array $falha): bool => $falha['painel'] === 'financeiro'));
    $dosOutros    = array_values(array_filter($falhas, static fn (array $falha): bool => $falha['painel'] !== 'financeiro'));

    expect(array_column($doFinanceiro, 'modo'))->toEqualCanonicalizing(['sem', 'com'])
        ->and(array_column($dosOutros, 'motivo'))->toBe([])
        ->and(array_values(array_intersect(['admin', 'app', 'infra'], $paineis)))->toBe(['admin', 'app', 'infra']);
});

it('[CT-43] a guarda da grade reprova a célula que leva a diagrama do outro modo ou de outro painel', function (string $mundoDeControle): void {
    $decls   = mapaDiagramasDecls();
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
            $esperado              = ['app × com', 'app-multi-tenancy-opt-in-sem'];
            break;
        case 'infra × sem tenant leva ao diagrama do admin':
            $grade['infra']['sem'][] = ['slug' => 'admin-resumo', 'compartilhado' => true];
            $esperado                = ['infra × sem', 'admin-resumo'];
            break;
        case 'app × com tenant marca como compartilhado o diagrama que só vale com tenant':
            foreach ($grade['app']['com'] as $i => $item) {
                if ($item['slug'] === 'app-multi-tenancy-opt-in-com') {
                    $grade['app']['com'][$i]['compartilhado'] = true;
                }
            }
            $esperado = ['app × com', 'app-multi-tenancy-opt-in-com'];
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

    $nomeia = [];
    switch ($mundoDeControle) {
        case 'compartilhado desenha DefinirTenantDePermissoes':
            expect($irs['app-acesso-e-autenticacao']['modos'])->toBe(['sem', 'com']);
            $irs['app-acesso-e-autenticacao']['nos'][$definirTenant] = mapaDiagramasNo('peca', 'DefinirTenantDePermissoes', [$definirTenant]);
            $nomeia                                                  = ['app-acesso-e-autenticacao', 'DefinirTenantDePermissoes'];
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
            $irs['app-resumo-sem'] = mapaDiagramasConstruir(mapaDiagramasDecls()['app-resumo-sem'], $modelo);
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
    'sem tenant desenha DefinirTenantDePermissoes', 'sem tenant desenha o painel app usa o model Tenant',
]);

/*
|--------------------------------------------------------------------------
| R19 — tipos, painéis e modos (CT-20, CT-21)
|--------------------------------------------------------------------------
*/

it('[CT-20] cada um dos dez tipos nativos e o comparativo de arquitetura têm diagrama publicado ou o motivo escrito', function (string $tipo): void {
    $decls      = mapaDiagramasDecls();
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
            expect(mapaDiagramasMotivoDeEnfeite($celula['motivo']))->toBeFalse("{$idioma}: o motivo da célula {$tipo} × {$coluna} é de enfeite");
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
    $decls       = mapaDiagramasDecls();
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
            $decls[$comparativo]['base'] = 'admin-resumo';
            break;
        case 'base que não é arquivo de diagrama':
            $decls[$comparativo]['base'] = 'nao-existe-no-mapa';
            break;
    }
    $resultado = mapaDiagramasConferirComparativos($decls, $publicados);

    expect($resultado['comparativos'])->toBeGreaterThan(0)
        ->and($resultado['aceita'])->toBe($aceita, $resultado['motivo']);
    if (! $aceita) {
        expect($resultado['motivo'])->toContain($comparativo);
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
    $modelo = mapaDiagramasModelo();
    $decls  = mapaDiagramasDecls();

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
    $decls        = mapaDiagramasDecls();
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

    $decls   = [$marcos => $decls[$marcos], 'tempos-ci' => mapaDiagramasDecls()['tempos-ci']];
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

    $mundo        = mapaDiagramasMundo();
    $vivo         = mapaDiagramasRegistroVivo($viva);
    $condicionais = ['App\\Filament\\Admin\\Resources\\Tenants\\TenantResource'];

    if ($mundoDeControle === 'publicados') {
        $resultado = mapaDiagramasConferirRegistro($mundo['modelo'], $mundo['irs'], $painel, 'sem', $vivo, $condicionais);
        expect($resultado['registro'])->toBeGreaterThan(0)
            ->and($resultado['motivo'])->toBe('aceita');
        if ($painel === 'infra') {
            expect($vivo)->toContain('ShuvroRoy\\FilamentSpatieLaravelHealth\\Pages\\HealthCheckResults');
        }
        if ($painel === 'app') {
            expect($vivo)->not->toContain('App\\Http\\Middleware\\DefinirTenantDePermissoes')
                ->and($vivo)->not->toContain('Filament\\Http\\Middleware\\IdentifyTenant');
        }

        return;
    }

    $modelo             = $mundo['modelo'];
    $shield             = mapaDiagramasIdTerminadoEm($modelo, 'FilamentShieldPlugin');
    $modelo['ligacoes'] = array_values(array_filter(
        $modelo['ligacoes'],
        static fn (array $ligacao): bool => ! ($ligacao['de'] === 'painel:admin' && $ligacao['para'] === $shield),
    ));
    $copia     = mapaDiagramasMundoCom($modelo);
    $resultado = mapaDiagramasConferirRegistro($modelo, $copia['irs'], $painel, 'sem', $vivo, $condicionais);

    expect($vivo)->toContain($shield)
        ->and($resultado['aceita'])->toBeFalse()
        ->and($resultado['motivo'])->toContain('admin')
        ->and($resultado['motivo'])->toContain('FilamentShieldPlugin');
})->with([
    'admin publicados'                     => ['admin', 'publicados'],
    'app publicados'                       => ['app', 'publicados'],
    'infra publicados'                     => ['infra', 'publicados'],
    'admin sem o Shield em nenhum desenho' => ['admin', 'cópia sem o FilamentShieldPlugin'],
]);

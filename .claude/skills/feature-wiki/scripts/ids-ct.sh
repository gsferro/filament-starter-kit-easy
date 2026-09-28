#!/usr/bin/env bash
# ids-ct.sh — IDs CT-nn/CT-Bnn do 04/05 × IDs [CT-nn] dos arquivos de teste, nos dois sentidos.
#
# Uso (na raiz do projeto):
#   bash {skills}/feature-wiki/scripts/ids-ct.sh <wiki> <padrão-de-arquivo-de-teste> [<padrão>...]
#     <wiki>   = wikis/specs/{branch}/{feature}
#     <padrão> = glob entre aspas. Com barra, casa o path a partir da raiz, com ** para qualquer
#                profundidade: 'tests/**/AprovacaoPedido/*.php' — a forma do step 10, porque o executor-ct e o
#                executor-ctb gravam em tests/Feature/{Feature}/ e tests/Browser/{Feature}/. Sem barra, casa só
#                o nome do arquivo em qualquer pasta de tests/ (como `find tests -name`): '*AprovacaoPedido*.php'
#                não acha tests/Feature/AprovacaoPedido/AutorizacaoTest.php.
#
# O que confere:
#   - CT definido no 04/05 — linha Gherkin `Cenário: [CT-nn]` / `Esquema do Cenário: [CT-nn]`, título
#     `## CT-Bnn:` do 05, ou linha do ## Índice de Cenários — sem teste com `[CT-nn]` no nome;
#   - `[CT-nn]` num arquivo de teste sem cenário definido no 04/05;
#   - `[CT-nn]` num arquivo de teste cujo cenário está @obsoleto, riscado (~~CT-nn~~) ou "fundido em"
#     no 04/05 — cenário obsoleto não vira teste; o que o substitui, sim.
# O que NÃO confere: linha de dataset × Exemplos do Gherkin e a contagem do cabeçalho do 04 (leitura,
#   dimensão L1 do quality gate).
#
# Quem chama: feature-wiki step 10 (item 4 da reconciliação); feature-quality-gate, dimensão L1 (IDs de CT).
# Contrato: silêncio + exit 0 = OK; achado = uma linha `arquivo:linha: mensagem` + exit 1;
#   erro de uso ou de ambiente = mensagem no stderr + exit 2. Padrão que não casa nenhum arquivo é
#   exit 2: filtro que casa nada sairia "limpo" com todo CT sem teste.
# Requer: bash e php no PATH (lógica em PHP embutido: o boost:add-skill descarta arquivos .php).
#   Sem jq, node, python ou grep -P.
#
# Exemplo de falha (saída real, fixture de 2026-09-27):
#   $ bash ids-ct.sh wikis/specs/ferro/501/aprovacao-pedido 'tests/**/AprovacaoPedido/*.php'
#   tests/Feature/AprovacaoPedido/AprovacaoPedidoTest.php:11: [CT-09] no teste, sem cenário definido no 04/05
#   tests/Feature/AprovacaoPedido/AprovacaoPedidoTest.php:20: [CT-06] no teste, mas o cenário está @obsoleto/riscado/fundido no 04/05
#   wikis/specs/ferro/501/aprovacao-pedido/04-casos-de-teste.md:42: CT-03 sem teste — nenhum arquivo casando 'tests/**/AprovacaoPedido/*.php' tem [CT-03]
#   (exit 1)
set -u

if [ $# -lt 2 ] || [ -z "$1" ] || [ -z "$2" ]; then
  echo "uso: ids-ct.sh <wiki> <padrão-de-arquivo-de-teste> [<padrão>...]" >&2
  echo "     ex.: ids-ct.sh wikis/specs/ferro/501/aprovacao-pedido 'tests/**/AprovacaoPedido/*.php'" >&2
  exit 2
fi
command -v php >/dev/null 2>&1 || { echo "ids-ct.sh: php não está no PATH (o script usa PHP embutido)" >&2; exit 2; }

wiki=${1//\\//}
wiki=${wiki%/}
shift
[ -d "$wiki" ] || { echo "ids-ct.sh: $wiki não é um diretório" >&2; exit 2; }
[ -f "$wiki/04-casos-de-teste.md" ] || { echo "ids-ct.sh: $wiki/04-casos-de-teste.md não existe" >&2; exit 2; }

read -r -d '' PHP_CODE <<'PHP'
ini_set('display_errors', 'stderr');
error_reporting(E_ALL);

$wiki = $argv[1];
$padroes = array_slice($argv, 2);

function ler($f) {
    $t = @file_get_contents($f);
    if ($t === false) { fwrite(STDERR, "ids-ct.sh: não consegui ler $f\n"); exit(2); }
    return preg_split('/\r\n|\n|\r/', preg_replace('/^\xEF\xBB\xBF/', '', $t));
}

function globRegex($g) {
    $r = ''; $n = strlen($g);
    for ($i = 0; $i < $n; $i++) {
        $ch = $g[$i];
        if ($ch === '*') {
            if (($g[$i + 1] ?? '') === '*') {
                $i++;
                if (($g[$i + 1] ?? '') === '/') { $i++; $r .= '(?:.*/)?'; } else { $r .= '.*'; }
            } else { $r .= '[^/]*'; }
        } elseif ($ch === '?') { $r .= '[^/]'; }
        elseif ($ch === '[') {
            $fim = strpos($g, ']', $i + 1);
            if ($fim === false) { $r .= '\['; continue; }
            $cls = substr($g, $i + 1, $fim - $i - 1);
            if ($cls !== '' && $cls[0] === '!') $cls = '^' . substr($cls, 1);
            $r .= '[' . str_replace('/', '', $cls) . ']';
            $i = $fim;
        } elseif ($ch === '{') {
            $fim = strpos($g, '}', $i + 1);
            if ($fim === false) { $r .= '\{'; continue; }
            $alts = array_map(function ($a) { return substr(globRegex($a), 2, -2); }, explode(',', substr($g, $i + 1, $fim - $i - 1)));
            $r .= '(?:' . implode('|', $alts) . ')';
            $i = $fim;
        } else { $r .= preg_quote($ch, '#'); }
    }
    return '#^' . $r . '$#';
}

// arquivos de teste
$testes = [];
foreach ($padroes as $pad) {
    $pad = str_replace('\\', '/', $pad);
    $pad = preg_replace('#^\./#', '', $pad);
    $porNome = strpos($pad, '/') === false;
    $re = globRegex($pad);
    if ($porNome) { $base = 'tests'; }
    else {
        $lit = preg_split('/[*?\[{]/', $pad, 2)[0];
        $base = rtrim(substr($lit, 0, (int) strrpos($lit, '/')), '/');
        if ($base === '') $base = '.';
    }
    if (!is_dir($base)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
        function ($f) { return !in_array($f->getFilename(), ['vendor', 'node_modules', '.git'], true); }
    ));
    foreach ($it as $f) {
        if (!$f->isFile()) continue;
        $rel = preg_replace('#^\./#', '', str_replace('\\', '/', $f->getPathname()));
        $alvo = $porNome ? $f->getFilename() : $rel;
        if (preg_match($re, $alvo)) $testes[$rel] = true;
    }
}
if (!$testes) {
    fwrite(STDERR, "ids-ct.sh: nenhum arquivo de teste casa com " . implode(' ', array_map(function ($p) { return "'$p'"; }, $padroes)) . " — padrão errado ou testes ausentes\n");
    exit(2);
}
ksort($testes);

// IDs definidos no 04/05
$def = []; $obs = [];
$arqsWiki = glob("$wiki/0[45]-*.md") ?: [];
sort($arqsWiki);
foreach ($arqsWiki as $a) {
    $tagObs = false;
    foreach (ler($a) as $i => $l) {
        $t = trim($l);
        if ($t === '') continue;
        if ($t[0] === '@') { $tagObs = $tagObs || (bool) preg_match('/@obsoleto\b/u', $t); continue; }
        $id = null; $ob = false;
        if (preg_match('/^(?:Cen[áa]rio|Esquema do Cen[áa]rio|Scenario(?: Outline)?)\s*:\s*\[(CT-B?\d+)\]/u', $t, $m)) {
            $id = $m[1]; $ob = $tagObs;
        } elseif (preg_match('/^#{2,4}\s*\[?(CT-B\d+)\]?\s*[:—–-]/u', $t, $m)) {
            $id = $m[1];
        } elseif (preg_match('/^\|\s*(~~)?\s*[*`]*\s*(CT-B?\d+)\b/', $t, $m)) {
            $id = $m[2];
            $ob = $m[1] !== '' || stripos($t, 'fundido em') !== false || stripos($t, '@obsoleto') !== false;
        }
        $tagObs = false;
        if ($id === null) continue;
        if (!isset($def[$id])) $def[$id] = [$a, $i + 1];
        if ($ob) $obs[$id] = true;
    }
}

// IDs nos testes
$noTeste = [];
$achados = [];
foreach (array_keys($testes) as $a) {
    $vistoAqui = [];
    foreach (ler($a) as $i => $l) {
        if (!preg_match_all('/\[(CT-B?\d+)\]/', $l, $m)) continue;
        foreach ($m[1] as $id) {
            $noTeste[$id] = true;
            if (isset($vistoAqui[$id])) continue;
            $vistoAqui[$id] = true;
            if (!isset($def[$id])) $achados[] = [$a, $i + 1, "[$id] no teste, sem cenário definido no 04/05"];
            elseif (isset($obs[$id])) $achados[] = [$a, $i + 1, "[$id] no teste, mas o cenário está @obsoleto/riscado/fundido no 04/05"];
        }
    }
}
$pads = implode(' ', array_map(function ($p) { return "'$p'"; }, $padroes));
foreach ($def as $id => [$a, $n]) {
    if (isset($obs[$id]) || isset($noTeste[$id])) continue;
    $achados[] = [$a, $n, "$id sem teste — nenhum arquivo casando $pads tem [$id]"];
}

if (!$achados) exit(0);
usort($achados, function ($x, $y) { return [$x[0], $x[1]] <=> [$y[0], $y[1]]; });
foreach ($achados as [$a, $n, $msg]) echo "$a:$n: $msg\n";
exit(1);
PHP

php -r "$PHP_CODE" -- "$wiki" "$@"

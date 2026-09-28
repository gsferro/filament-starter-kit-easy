#!/usr/bin/env bash
# citacoes.sh — toda citação `arquivo:símbolo:linha` da wiki aponta um arquivo que existe, com o
# símbolo na linha citada.
#
# Uso (na raiz do projeto — os paths citados são relativos a ela):
#   bash {skills}/feature-wiki/scripts/citacoes.sh <wiki>
#     <wiki> = wikis/specs/{branch}/{feature}
#
# O que confere, em todo .md da wiki (07-tickets/ incluído) menos o 00-requisito.md:
#   - `{path}:{símbolo}:{linha}` e `{path}:{símbolo}:{inicial}-{final}` — o arquivo existe e a linha
#     (a inicial, no intervalo) contém o símbolo, sem `()` e sem aspas. Símbolo: método, função,
#     constante, `$propriedade` ou chave entre aspas ('autenticacao'). Extensões: php (e .blade.php),
#     js, mjs, cjs, ts, tsx, jsx, vue, css, scss, json, yml, yaml, xml, neon, sh, sql;
#   - path curto (`Login.php:{símbolo}:172`) resolvido pelo path completo que apareceu antes no mesmo
#     documento — nas linhas de cima ou à esquerda na mesma linha; sem ele, é achado;
#   - citação sem símbolo (`app/Models/Pedido.php:12`) fora de bloco de código cercado e de
#     comentário HTML — dentro deles costuma ser saída colada de ferramenta, não citação;
#   - símbolo fora do formato, no mesmo recorte: `{path}:{qualquer coisa}:{linha}` que não é a forma
#     acima (`Pedido::aprovar()`, `Pedido@aprovar`, `linha:coluna`). Sem esta checagem a citação
#     passava calada, e silêncio vale OK no step 10.
# Fora da conferência: o 00-requisito.md, que não leva path de código (SKILL.md, Path e número por
#   arquivo) e cujo Texto Original é imutável — citação que apareça nele é do solicitante.
#
# Quem chama: feature-wiki step 10 (item 3 da reconciliação); steps 3 e 5 ao escrever uma citação;
#   feature-quality-gate, dimensão L2 (citações). O formato está em references/citacoes-de-codigo.md.
# Contrato: silêncio + exit 0 = OK; achado = uma linha `arquivo:linha: mensagem` + exit 1;
#   erro de uso ou de ambiente = mensagem no stderr + exit 2.
# Requer: bash e php no PATH (lógica em PHP embutido: o boost:add-skill descarta arquivos .php).
#   Sem jq, node, python ou grep -P.
#
# Exemplo de falha (saída real, fixture de 2026-09-27):
#   $ bash citacoes.sh wikis/specs/ferro/501/aprovacao-pedido
#   wikis/specs/ferro/501/aprovacao-pedido/01-plano-acao.md:52: app/Models/Pedido.php:aprovar():14 — símbolo "aprovar" não está na linha 14
#   wikis/specs/ferro/501/aprovacao-pedido/01-plano-acao.md:53: app/Models/Nada.php:foo():3 — arquivo não existe
#   wikis/specs/ferro/501/aprovacao-pedido/01-plano-acao.md:54: config/logging.php:'aprovacao':300 — linha 300 além do fim do arquivo (12 linhas)
#   wikis/specs/ferro/501/aprovacao-pedido/01-plano-acao.md:55: app/Models/Pedido.php:12 — citação sem símbolo: use {arquivo}:{símbolo}:{linha}
#   wikis/specs/ferro/501/aprovacao-pedido/01-plano-acao.md:56: app/Models/Pedido.php:Pedido::aprovar():11 — símbolo fora do formato: use {arquivo}:{símbolo}:{linha}, com o símbolo = método, função, constante, $propriedade ou chave entre aspas (não Classe::método nem Classe@método)
#   wikis/specs/ferro/501/aprovacao-pedido/02-decisoes-arquiteturais.md:14: Outro.php:x():3 — path curto sem o path completo antes no documento
#   (exit 1)
set -u

if [ $# -ne 1 ] || [ -z "$1" ]; then
  echo "uso: citacoes.sh <wiki>   (wiki = wikis/specs/{branch}/{feature}; rodar na raiz do projeto)" >&2
  exit 2
fi
command -v php >/dev/null 2>&1 || { echo "citacoes.sh: php não está no PATH (o script usa PHP embutido)" >&2; exit 2; }

wiki=${1//\\//}
wiki=${wiki%/}
[ -d "$wiki" ] || { echo "citacoes.sh: $wiki não é um diretório" >&2; exit 2; }

read -r -d '' PHP_CODE <<'PHP'
ini_set('display_errors', 'stderr');
error_reporting(E_ALL);

$wiki = $argv[1];
$EXT = '(?:php|js|mjs|cjs|ts|tsx|jsx|vue|css|scss|json|ya?ml|xml|neon|sh|sql)';
$PATH = '(?<![A-Za-z0-9_./-])((?:[A-Za-z0-9_.-]+/)*[A-Za-z0-9_.-]+\.' . $EXT . ')';
$SIMB = "('[^'\\s]+'|\"[^\"\\s]+\"|\\\$?[A-Za-z_][A-Za-z0-9_]*(?:\\(\\))?)";
$reCom = '#' . $PATH . ':' . $SIMB . ':(\d+)(?:-(\d+))?(?![0-9])#';
$reComIni = '#^' . $PATH . ':' . $SIMB . ':(\d+)(?:-(\d+))?(?![0-9])#';
$reSem = '#' . $PATH . ':(\d+)(?:-\d+)?(?![0-9A-Za-z_:(])#';
// path com extensão, ":", qualquer coisa sem espaço e ":{linha}" — o que não for $reCom é símbolo fora do
// formato (Classe::método(), Classe@método, linha:coluna): sem esta regex passava calado, e silêncio vale OK
$reFora = '#' . $PATH . ':([^\s`|]+?):(\d+)(?:-\d+)?(?![0-9A-Za-z_])#';
$reCaminho = '#(?<![A-Za-z0-9_./-])((?:[A-Za-z0-9_.-]+/)+[A-Za-z0-9_.-]+\.' . $EXT . ')(?![A-Za-z0-9_])#';

$cacheArq = [];
function linhasDe($p) {
    global $cacheArq;
    if (!array_key_exists($p, $cacheArq)) {
        $t = is_file($p) ? @file_get_contents($p) : false;
        $ls = $t === false ? null : preg_split('/\r\n|\n|\r/', $t);
        if ($ls !== null && count($ls) > 1 && end($ls) === '') array_pop($ls);
        $cacheArq[$p] = $ls;
    }
    return $cacheArq[$p];
}

$arqs = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($wiki, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if (!$f->isFile() || substr($f->getFilename(), -3) !== '.md') continue;
    if ($f->getFilename() === '00-requisito.md' && dirname(str_replace('\\', '/', $f->getPathname())) === $wiki) continue;
    $arqs[] = str_replace('\\', '/', $f->getPathname());
}
sort($arqs);

$achados = [];
foreach ($arqs as $a) {
    $t = @file_get_contents($a);
    if ($t === false) { fwrite(STDERR, "citacoes.sh: não consegui ler $a\n"); exit(2); }
    $ls = preg_split('/\r\n|\n|\r/', preg_replace('/^\xEF\xBB\xBF/', '', $t));
    $completos = [];   // basename => path completo visto antes no documento
    $cerca = false; $coment = false;
    foreach ($ls as $i => $l) {
        $n = $i + 1;
        $marcaCerca = (bool) preg_match('/^\s*(```|~~~)/', $l);
        // trechos fora de comentário HTML, para a checagem "sem símbolo"
        $visivel = $l;
        if ($coment) {
            $p = strpos($visivel, '-->');
            if ($p === false) $visivel = ''; else { $visivel = substr($visivel, $p + 3); $coment = false; }
        }
        $visivel = preg_replace('/<!--.*?-->/', '', $visivel);
        if (($p = strpos($visivel, '<!--')) !== false) { $visivel = substr($visivel, 0, $p); $coment = true; }

        // paths completos da linha, com a posição: um path completo à esquerda resolve o path curto à direita
        $caminhos = [];
        if (preg_match_all($reCaminho, $l, $mm, PREG_OFFSET_CAPTURE)) foreach ($mm[1] as $c) $caminhos[] = $c;
        $k = 0;
        // 1. citações com símbolo (em qualquer lugar da linha), na ordem em que aparecem
        if (preg_match_all($reCom, $l, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                for (; $k < count($caminhos) && $caminhos[$k][1] < $m[0][1]; $k++) $completos[basename($caminhos[$k][0])] = $caminhos[$k][0];
                $cit = $m[0][0]; $path = $m[1][0]; $simb = $m[2][0]; $lin = (int) $m[3][0];
                $real = $path;
                if (strpos($path, '/') === false && !is_file($path)) {
                    if (!isset($completos[$path])) { $achados[] = [$a, $n, "$cit — path curto sem o path completo antes no documento"]; continue; }
                    $real = $completos[$path];
                }
                $conteudo = linhasDe($real);
                if ($conteudo === null) { $achados[] = [$a, $n, "$cit — arquivo não existe"]; continue; }
                if ($lin < 1 || $lin > count($conteudo)) { $achados[] = [$a, $n, "$cit — linha $lin além do fim do arquivo (" . count($conteudo) . " linhas)"]; continue; }
                $s = preg_replace('/\(\)$/', '', $simb);
                $s = trim($s, "'\"");
                if (strpos($conteudo[$lin - 1], $s) === false) $achados[] = [$a, $n, "$cit — símbolo \"$s\" não está na linha $lin"];
            }
        }
        // 2. citações sem símbolo, fora de bloco cercado e de comentário
        if (!$cerca && !$marcaCerca && $visivel !== '' && preg_match_all($reSem, $visivel, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $m) $achados[] = [$a, $n, "{$m[0]} — citação sem símbolo: use {arquivo}:{símbolo}:{linha}"];
        }
        // 3. símbolo fora do formato, no mesmo recorte da checagem 2
        if (!$cerca && !$marcaCerca && $visivel !== '' && preg_match_all($reFora, $visivel, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                if (preg_match($reComIni, substr($visivel, $m[0][1]))) continue;
                $achados[] = [$a, $n, "{$m[0][0]} — símbolo fora do formato: use {arquivo}:{símbolo}:{linha}, com o símbolo = método, função, constante, \$propriedade ou chave entre aspas (não Classe::método nem Classe@método)"];
            }
        }
        // 4. o resto dos paths completos da linha resolve path curto nas linhas seguintes
        for (; $k < count($caminhos); $k++) $completos[basename($caminhos[$k][0])] = $caminhos[$k][0];
        if ($marcaCerca) $cerca = !$cerca;
    }
}

if (!$achados) exit(0);
$vistos = [];
foreach ($achados as [$a, $n, $msg]) {
    $s = "$a:$n: $msg";
    if (isset($vistos[$s])) continue;
    $vistos[$s] = true;
    echo $s, "\n";
}
exit(1);
PHP

php -r "$PHP_CODE" -- "$wiki"

#!/usr/bin/env bash
# conformidade-rules.sh — rule de .ai/rules/*.md casada pelo diff sem linha em ## Conformidade com Rules do 03.
#
# Uso (na raiz do projeto — onde ficam .ai/rules/ e wikis/):
#   bash {skills}/feature-wiki/scripts/conformidade-rules.sh <wiki> <base>
#     <wiki> = wikis/specs/{branch}/{feature}
#     <base> = branch ou commit de onde a feature saiu (main, origin/main, a1b2c3d)
#
# O que confere: os arquivos de `git diff --name-only {base}...HEAD` (só o que está commitado; com
#   --no-renames, o rename conta o path de origem e o de destino; com --relative, paths a partir da raiz
#   do projeto, que pode ser uma subpasta do repositório) contra o `paths:` do frontmatter de cada
#   .ai/rules/*.md — os arquivos que o Boost põe no índice: o index.md e a pasta .ai/rules/boost/ (as
#   diretrizes do próprio Boost) ficam fora, e arquivo sem `paths:` também, porque o Boost o ignora.
#   Rule com algum glob que casa algum arquivo do diff e sem linha na tabela ## Conformidade com Rules
#   do <wiki>/03-progresso.md é achado. A linha conta quando a coluna Rule (ou a primeira) cita o nome
#   do arquivo da rule: `models.md` ou `.ai/rules/models.md`, com ou sem crase.
#   Glob, relativo à raiz do projeto: ** = qualquer profundidade (inclusive nenhuma pasta), * e ? não
#   passam de /, [abc], [!abc] e {a,b}. Glob sem barra casa só na raiz (*.md não casa docs/x.md); glob
#   sem curinga casa o arquivo e, como pasta, o que está dentro dele (config casa config/app.php).
#   YAML lido de forma simplificada: lista em bloco (- item), lista entre colchetes ou valor único, com
#   ou sem aspas. Tabela e título dentro de bloco cercado (```) ou de comentário HTML não contam.
# O que NÃO confere: o veredito aplicada / n.a. / violada e a evidência (julgamento: step 10, item 6;
#   quality gate, dimensão L4); linha a mais na tabela (rule que o diff não casa); arquivo não commitado.
#
# Quem chama: feature-wiki step 10 (item 6 da reconciliação); feature-quality-gate, dimensão L4.
# Contrato: silêncio + exit 0 = OK (inclusive sem .ai/rules/ ou com diff vazio: nada a conformar);
#   achado = uma linha `arquivo:linha: mensagem` + exit 1; erro de uso ou de ambiente (sem php ou git,
#   fora de um repositório, <base> que não é commit, sem o 03) = mensagem no stderr + exit 2.
# Requer: bash, git, tr e php no PATH (lógica em PHP embutido: o boost:add-skill descarta arquivos .php).
#   Sem jq, node, python ou grep -P.
#
# Exemplo de falha (saída real, repositório de teste de 2026-09-27):
#   $ bash conformidade-rules.sh wikis/specs/ferro/501/aprovacao-pedido main
#   wikis/specs/ferro/501/aprovacao-pedido/03-progresso.md:5: .ai/rules/policies.md casa app/Policies/PedidoPolicy.php (glob "app/Policies/*.php") e não tem linha em ## Conformidade com Rules
#   wikis/specs/ferro/501/aprovacao-pedido/03-progresso.md:5: .ai/rules/testes.md casa tests/Feature/Pedido/AprovacaoTest.php e mais 1 (glob "tests/**/*Test.php") e não tem linha em ## Conformidade com Rules
#   (exit 1)
set -u

if [ $# -ne 2 ] || [ -z "$1" ] || [ -z "$2" ]; then
  echo "uso: conformidade-rules.sh <wiki> <base>   (wiki = wikis/specs/{branch}/{feature}; base = main, origin/main ou um commit; rodar na raiz do projeto)" >&2
  exit 2
fi
command -v php >/dev/null 2>&1 || { echo "conformidade-rules.sh: php não está no PATH (o script usa PHP embutido)" >&2; exit 2; }
command -v git >/dev/null 2>&1 || { echo "conformidade-rules.sh: git não está no PATH" >&2; exit 2; }

wiki=${1//\\//}
wiki=${wiki%/}
base=$2
[ -d "$wiki" ] || { echo "conformidade-rules.sh: $wiki não é um diretório (rodar na raiz do projeto)" >&2; exit 2; }
[ -f "$wiki/03-progresso.md" ] || { echo "conformidade-rules.sh: $wiki/03-progresso.md não existe — a tabela ## Conformidade com Rules fica no 03" >&2; exit 2; }
git rev-parse --is-inside-work-tree >/dev/null 2>&1 || { echo "conformidade-rules.sh: $(pwd) não está num repositório git" >&2; exit 2; }
git rev-parse --verify --quiet "$base^{commit}" >/dev/null 2>&1 || { echo "conformidade-rules.sh: base \"$base\" não é um commit deste repositório (ex.: main, origin/main)" >&2; exit 2; }

# -z + tr: path com acento ou espaço sai sem aspas nem escape octal
arqs=$(git -c core.quotepath=false diff -z --name-only --no-renames --relative "$base...HEAD" 2>/dev/null | tr '\0' '\n'; exit "${PIPESTATUS[0]}")
rc=$?
if [ "$rc" -ne 0 ]; then
  echo "conformidade-rules.sh: git diff $base...HEAD falhou (exit $rc):" >&2
  git diff --name-only "$base...HEAD" >/dev/null
  exit 2
fi
[ -n "$arqs" ] || exit 0
[ -d .ai/rules ] || exit 0

read -r -d '' PHP_CODE <<'PHP'
ini_set('display_errors', 'stderr');
error_reporting(E_ALL);

$wiki = $argv[1];
$f03 = "$wiki/03-progresso.md";
$arqs = [];
foreach (preg_split('/\r?\n/', (string) stream_get_contents(STDIN)) as $l) if ($l !== '') $arqs[] = str_replace('\\', '/', $l);

// ---- glob → regex (a mesma conversão do ids-ct.sh)
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
            $r .= '[' . str_replace(['/', '#'], ['', '\#'], $cls) . ']';
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
function regexDoGlob($g) {
    $g = preg_replace('#^(\./|/)+#', '', str_replace('\\', '/', trim($g)));
    if ($g === '') return null;
    if (strpbrk($g, '*?[{') === false) return '#^' . preg_quote(rtrim($g, '/'), '#') . '(?:/.*)?$#';
    return globRegex($g);
}

// ---- paths: do frontmatter (YAML simplificado; o Boost usa symfony/yaml)
function escalar($s) {
    $s = trim($s);
    if ($s === '') return '';
    if ($s[0] === "'") {
        $out = ''; $n = strlen($s);
        for ($i = 1; $i < $n; $i++) {
            if ($s[$i] === "'") { if (($s[$i + 1] ?? '') === "'") { $out .= "'"; $i++; continue; } break; }
            $out .= $s[$i];
        }
        return $out;
    }
    if ($s[0] === '"') {
        $out = ''; $n = strlen($s);
        for ($i = 1; $i < $n; $i++) {
            if ($s[$i] === '\\' && $i + 1 < $n) { $out .= $s[$i + 1]; $i++; continue; }
            if ($s[$i] === '"') break;
            $out .= $s[$i];
        }
        return $out;
    }
    $s = trim(preg_replace('/\s+#.*$/', '', $s));
    return in_array(strtolower($s), ['~', 'null'], true) ? '' : $s;
}
function listaEmLinha($s) {   // [a, 'b', "c", x/{d,e}]
    $s = trim($s);
    $s = substr($s, 1, (int) strrpos($s, ']') - 1);
    $itens = []; $cur = ''; $q = ''; $prof = 0; $n = strlen($s);
    for ($i = 0; $i < $n; $i++) {
        $c = $s[$i];
        if ($q !== '') { $cur .= $c; if ($c === $q) $q = ''; continue; }
        if ($c === "'" || $c === '"') { $q = $c; $cur .= $c; continue; }
        if ($c === '{') $prof++;
        if ($c === '}' && $prof > 0) $prof--;
        if ($c === ',' && $prof === 0) { $itens[] = escalar($cur); $cur = ''; continue; }
        $cur .= $c;
    }
    $itens[] = escalar($cur);
    return $itens;
}
function pathsDe($txt) {
    $txt = str_replace(["\r\n", "\r"], "\n", preg_replace('/^\xEF\xBB\xBF/', '', $txt));
    if (!preg_match('/^\s*---\s*\n(.*?)\n---\s*(?:\n|$)/s', $txt, $m)) return [];
    $ls = explode("\n", $m[1]); $n = count($ls); $out = [];
    for ($i = 0; $i < $n; $i++) {
        if (!preg_match('/^paths\s*:(.*)$/', $ls[$i], $mm)) continue;
        $resto = trim($mm[1]);
        if ($resto !== '' && $resto[0] !== '#') {
            $out = $resto[0] === '[' ? listaEmLinha($resto) : [escalar($resto)];
        } else {
            for ($j = $i + 1; $j < $n; $j++) {
                $l = $ls[$j];
                if (trim($l) === '' || preg_match('/^\s*#/', $l)) continue;
                if (preg_match('/^\s*-\s+(.*)$/', $l, $it) || preg_match('/^\s*-()$/', $l, $it)) { $out[] = escalar($it[1]); continue; }
                break;
            }
        }
        break;
    }
    return array_values(array_filter($out, function ($s) { return $s !== ''; }));
}

// ---- rules casadas pelo diff
$casadas = [];
$rules = glob('.ai/rules/*.md') ?: [];
sort($rules);
foreach ($rules as $r) {
    if (strcasecmp(basename($r), 'index.md') === 0) continue;
    $t = @file_get_contents($r);
    if ($t === false) { fwrite(STDERR, "conformidade-rules.sh: não consegui ler $r\n"); exit(2); }
    $primeiro = null; $casa = [];
    foreach (pathsDe($t) as $g) {
        $re = regexDoGlob($g);
        if ($re === null) continue;
        foreach ($arqs as $a) {
            if (!preg_match($re, $a)) continue;
            if ($primeiro === null) $primeiro = [$g, $a];
            $casa[$a] = true;
        }
    }
    if ($primeiro !== null) $casadas[$r] = [$primeiro[0], $primeiro[1], count($casa)];
}
if (!$casadas) exit(0);

// ---- ## Conformidade com Rules do 03
$t = @file_get_contents($f03);
if ($t === false) { fwrite(STDERR, "conformidade-rules.sh: não consegui ler $f03\n"); exit(2); }
$ls = preg_split('/\r\n|\n|\r/', preg_replace('/^\xEF\xBB\xBF/', '', $t));
$secao = null; $linhas = []; $cerca = false; $coment = false;
foreach ($ls as $i => $l) {
    if (preg_match('/^\s*(```|~~~)/', $l)) { $cerca = !$cerca; continue; }
    if ($cerca) continue;
    if ($coment) { if (strpos($l, '-->') === false) continue; $l = substr($l, strpos($l, '-->') + 3); $coment = false; }
    $l = preg_replace('/<!--.*?-->/', '', $l);
    if (($p = strpos($l, '<!--')) !== false) { $l = substr($l, 0, $p); $coment = true; }
    if (preg_match('/^#{1,2}\s+(.+?)\s*#*\s*$/u', $l, $m)) {
        if ($secao !== null) break;   // o próximo título de nível 1 ou 2 fecha a seção
        if (preg_match('/^Conformidade com Rules$/iu', trim($m[1]))) $secao = $i + 1;
        continue;
    }
    if ($secao !== null && preg_match('/^\s*\|/', $l)) $linhas[] = $l;
}
$celulas = []; $col = 0; $cab = false;
foreach ($linhas as $l) {
    $c = array_map('trim', explode('|', preg_replace('/^\s*\||\|\s*$/', '', trim($l))));
    if (preg_match('/^[\s|:\-]+$/', $l)) continue;
    if (!$cab) {
        $cab = true;
        foreach ($c as $k => $v) if (stripos(trim(str_replace(['*', '`'], '', $v)), 'Rule') === 0) { $col = $k; break; }
        continue;
    }
    $celulas[] = $c[$col] ?? '';
}

$achados = [];
foreach ($casadas as $r => [$g, $a, $total]) {
    // models.md e .ai/rules/models.md contam; old-models.md e models.md.bak não
    $re = '#(?<![A-Za-z0-9_.-])(?:\.ai/rules/)?' . preg_quote(basename($r), '#') . '(?!\.?[A-Za-z0-9_-])#i';
    $tem = false;
    foreach ($celulas as $cel) if (preg_match($re, $cel)) { $tem = true; break; }
    if ($tem) continue;
    $quais = $a . ($total > 1 ? ' e mais ' . ($total - 1) : '');
    $onde = $secao !== null ? 'não tem linha em ## Conformidade com Rules' : 'o 03 não tem ## Conformidade com Rules';
    $achados[] = "$f03:" . ($secao !== null ? $secao : 1) . ": $r casa $quais (glob \"$g\") e $onde";
}
if (!$achados) exit(0);
foreach ($achados as $s) echo $s, "\n";
exit(1);
PHP

printf '%s\n' "$arqs" | php -r "$PHP_CODE" -- "$wiki"

#!/usr/bin/env bash
# roteiro-manual.sh — decisão do 03 e vínculos estáticos do roteiro manual 08.
#
# Uso (na raiz do projeto):
#   bash {skills}/feature-wiki/scripts/roteiro-manual.sh <wiki>
#     <wiki> = wikis/specs/{branch}/{feature}
#
# O que confere:
#   - última decisão não riscada (linha Roteiro manual: ou tabela) no 03 × presença do 08;
#     08 sem decisão; comentários, blocos de exemplo e linhas com ~~ não são decisões;
#   - linhas de tabelas com Resultado esperado e Origem: esperado vazio ou Origem sem RQ/P/CT;
#   - CT-nn/CT-Bnn citados no 08 sem definição no 04/05, ou @obsoleto/riscado/fundido;
#     as definições seguem ids-ct.sh: Cenário/Esquema, título CT-B ou primeira coluna do índice;
#     comentários HTML no 00/04/05 não definem IDs; título CT-B herda a tag @obsoleto anterior;
#   - RQ citada no 08 fora da decomposição do 00 (incluindo Adendos), ou P fora de ## Premissas;
#     RQ/P não vigente pelo Estado ou Substitui integral de Adendo; aberta fica para leitura;
#     comentários HTML e blocos de código cercados no 08 não contam como citações;
#   - RM-nn definido mais de uma vez em cabeçalhos Markdown do 08.
#   Wiki legada sem decisão nem 08 é aceita sem saída.
# O que NÃO confere: fidelidade ao requisito, suficiência do roteiro, resultado da execução ou
#   segurança dos comandos. Este script não executa comandos escritos no roteiro.
#
# Quem chama: feature-wiki step 10 (reconciliação); feature-quality-gate, conferência do roteiro manual.
# Contrato: silêncio + exit 0 = OK; achado = uma linha `arquivo:linha: mensagem` + exit 1;
#   erro de uso ou de ambiente = mensagem no stderr + exit 2.
# Requer: bash e php no PATH (lógica em PHP embutido: o boost:add-skill descarta arquivos .php).
#   Sem jq, node, python ou grep -P.
#
# Exemplo de falha:
#   $ bash roteiro-manual.sh wikis/specs/feature/demo
#   wikis/specs/feature/demo/03-progresso.md:8: Roteiro manual: sim, mas 08-roteiro-teste-manual.md não existe
#   wikis/specs/feature/demo/08-roteiro-teste-manual.md:15: CT-99 citado no 08, sem cenário definido no 04/05
#   (exit 1)
set -u

if [ $# -ne 1 ] || [ -z "$1" ]; then
  echo "uso: roteiro-manual.sh <wiki>   (wiki = wikis/specs/{branch}/{feature})" >&2
  exit 2
fi
command -v php >/dev/null 2>&1 || { echo "roteiro-manual.sh: php não está no PATH (o script usa PHP embutido)" >&2; exit 2; }

wiki=${1//\\//}
wiki=${wiki%/}
[ -d "$wiki" ] || { echo "roteiro-manual.sh: $wiki não é um diretório" >&2; exit 2; }

read -r -d '' PHP_CODE <<'PHP'
ini_set('display_errors', 'stderr');
error_reporting(E_ALL);

$wiki = $argv[1];
$f00 = "$wiki/00-requisito.md";
$f03 = "$wiki/03-progresso.md";
$f08 = "$wiki/08-roteiro-teste-manual.md";

function ler($f) {
    $t = @file_get_contents($f);
    if ($t === false) { fwrite(STDERR, "roteiro-manual.sh: não consegui ler $f\n"); exit(2); }
    return preg_split('/\r\n|\n|\r/', preg_replace('/^\xEF\xBB\xBF/', '', $t));
}

function semComentarios($ls) {
    $texto = preg_replace_callback('/<!--.*?-->/s', function ($m) {
        return str_repeat("\n", substr_count($m[0], "\n"));
    }, implode("\n", $ls));
    return explode("\n", $texto);
}

function limpa($s) { return trim(str_replace(['*', '`', '~'], '', $s)); }

function celulas($l) {
    $l = trim($l);
    if ($l === '' || $l[0] !== '|') return null;
    $l = preg_replace('/^\||\|$/', '', $l);
    return array_map('trim', preg_split('/(?<!\\\\)\|/', $l));
}

function separador($l) {
    $c = celulas($l);
    if ($c === null) return false;
    foreach ($c as $s) if (!preg_match('/^:?-{1,}:?$/', $s)) return false;
    return count($c) > 0;
}

function coluna($cab, $prefixo) {
    foreach ($cab as $i => $s) if (strpos($s, $prefixo) === 0) return $i;
    return false;
}

function emBloco($l, &$cerca) {
    if ($cerca !== null) {
        if (preg_match('/^\s*' . preg_quote($cerca[0], '/') . '{' . $cerca[1] . ',}\s*$/', $l)) $cerca = null;
        return true;
    }
    if (preg_match('/^\s*(`{3,}|~{3,})/', $l, $m)) {
        $cerca = [$m[1][0], strlen($m[1])];
        return true;
    }
    return false;
}

function tabelas($ls) {
    $out = []; $n = count($ls); $bloco = null;
    for ($i = 0; $i + 1 < $n; $i++) {
        if (emBloco($ls[$i], $bloco)) continue;
        $cab = celulas($ls[$i]);
        if ($cab === null || !separador($ls[$i + 1])) continue;
        $t = ['cab' => array_map('limpa', $cab), 'linhas' => []];
        for ($j = $i + 2; $j < $n; $j++) {
            $c = celulas($ls[$j]);
            if ($c === null) break;
            $t['linhas'][] = [$j + 1, $c];
        }
        $out[] = $t;
        $i = $j - 1;
    }
    return $out;
}

$achados = [];
function achado($arq, $linha, $msg) { $GLOBALS['achados'][] = [$arq, $linha, $msg]; }

$decisao = null;
if (is_file($f03)) {
    $bloco = null;
    foreach (semComentarios(ler($f03)) as $i => $l) {
        if (emBloco($l, $bloco) || strpos($l, '~~') !== false) continue;
        if (preg_match('/^\s*(?:-\s+)?Roteiro manual\s*:\s*(sim|n[ãa]o)\b/iu', limpa($l), $m)
            || preg_match('/^\s*\|\s*Roteiro manual\s*\|\s*(sim|n[ãa]o)\s*\|/iu', limpa($l), $m)) {
            $decisao = [strtolower($m[1]) === 'sim', $i + 1];
        }
    }
}
if ($decisao !== null) {
    if ($decisao[0] && !is_file($f08)) {
        achado($f03, $decisao[1], 'Roteiro manual: sim, mas 08-roteiro-teste-manual.md não existe');
    } elseif (!$decisao[0] && is_file($f08)) {
        achado($f03, $decisao[1], 'Roteiro manual: não, mas 08-roteiro-teste-manual.md existe');
    }
} elseif (is_file($f08)) {
    achado($f08, 1, '08 existe, mas não há decisão Roteiro manual no 03');
}

if (is_file($f08)) {
    $ls08 = semComentarios(ler($f08));
    foreach (tabelas($ls08) as $t) {
        $ce = coluna($t['cab'], 'Resultado esperado');
        $co = coluna($t['cab'], 'Origem');
        if ($ce === false || $co === false) continue;
        foreach ($t['linhas'] as [$n, $c]) {
            if (limpa($c[$ce] ?? '') === '') achado($f08, $n, 'Resultado esperado vazio');
            if (!preg_match('/\b(?:RQ-\d+|P-\d+|CT-B?\d+)\b/', $c[$co] ?? '')) {
                achado($f08, $n, 'Origem sem ID RQ-nn/P-nn/CT-nn/CT-Bnn');
            }
        }
    }

    // RQ só na decomposição (principal ou de Adendo); P só em ## Premissas.
    $rq = []; $pp = []; $subAdendo = []; $cab = [];
    $decomp = false; $premissas = false; $adendo = false; $bloco = null;
    if (is_file($f00)) foreach (semComentarios(ler($f00)) as $l) {
        if (emBloco($l, $bloco)) continue;
        if (preg_match('/^(#{1,3})\s+(.+)$/u', $l, $m)) {
            $cab = [];
            $nivel = strlen($m[1]); $titulo = limpa($m[2]);
            if ($nivel <= 2) {
                $decomp = (bool) preg_match('/^Decomposi[çc][ãa]o\b/iu', $titulo);
                $premissas = (bool) preg_match('/^Premissas\b/iu', $titulo);
                $adendo = (bool) preg_match('/^Adendo\s+\d+\b/iu', $titulo);
            } elseif ($adendo) $decomp = (bool) preg_match('/^Decomposi[çc][ãa]o\b/iu', $titulo);
            continue;
        }
        $c = celulas($l);
        if ($c === null) { $cab = []; continue; }
        if (limpa($c[0] ?? '') === 'ID') { $cab = array_map('limpa', $c); continue; }
        $id = limpa($c[0] ?? '');
        $ce = array_search('Estado', $cab, true);
        $estado = $ce === false ? '' : limpa($c[$ce] ?? '');
        if ($decomp && preg_match('/^RQ-\d+$/', $id)) {
            $rq[$id] = ($rq[$id] ?? true) && !preg_match('/substitu[íi]da|decomposta/iu', $estado);
            $cs = array_search('Substitui', $cab, true);
            if ($adendo && $cs !== false && preg_match_all('/\b(RQ-\d+)\b(\s*\(parcial\))?/iu', $c[$cs] ?? '', $mm, PREG_SET_ORDER)) {
                foreach ($mm as $m) if (empty($m[2])) $subAdendo[$m[1]] = true;
            }
        }
        if ($premissas && preg_match('/^P-\d+$/', $id)) {
            $pp[$id] = ($pp[$id] ?? true) && !preg_match('/substitu[íi]da|promovida|revogada/iu', $estado);
        }
    }
    foreach ($subAdendo as $id => $_) if (isset($rq[$id])) $rq[$id] = false;

    // Formas de definição de ids-ct.sh; comentários não definem CT e títulos CT-B herdam a tag.
    $def = []; $obs = [];
    $arqsWiki = glob("$wiki/0[45]-*.md") ?: [];
    sort($arqsWiki);
    foreach ($arqsWiki as $a) {
        $tagObs = false;
        foreach (semComentarios(ler($a)) as $l) {
            $t = trim($l);
            if ($t === '') continue;
            if ($t[0] === '@') { $tagObs = $tagObs || (bool) preg_match('/@obsoleto\b/u', $t); continue; }
            $id = null; $ob = false;
            if (preg_match('/^(?:Cen[áa]rio|Esquema do Cen[áa]rio|Scenario(?: Outline)?)\s*:\s*\[(CT-B?\d+)\]/u', $t, $m)) {
                $id = $m[1]; $ob = $tagObs;
            } elseif (preg_match('/^#{2,4}\s*\[?(CT-B\d+)\]?\s*[:—–-]/u', $t, $m)) {
                $id = $m[1]; $ob = $tagObs;
            } elseif (preg_match('/^\|\s*(~~)?\s*[*`]*\s*(CT-B?\d+)\b/', $t, $m)) {
                $id = $m[2];
                $ob = $m[1] !== '' || stripos($t, 'fundido em') !== false || stripos($t, '@obsoleto') !== false;
            }
            $tagObs = false;
            if ($id === null) continue;
            $def[$id] = true;
            if ($ob) $obs[$id] = true;
        }
    }

    $rm = []; $bloco = null;
    foreach ($ls08 as $i => $l) {
        if (emBloco($l, $bloco)) continue;
        if (preg_match_all('/\b(?:RQ-\d+|P-\d+|CT-B?\d+)\b/', $l, $m)) foreach (array_unique($m[0]) as $id) {
            if (strpos($id, 'CT-') === 0) {
                if (!isset($def[$id])) achado($f08, $i + 1, "$id citado no 08, sem cenário definido no 04/05");
                elseif (isset($obs[$id])) achado($f08, $i + 1, "$id citado no 08, mas o cenário está @obsoleto/riscado/fundido no 04/05");
            } elseif (strpos($id, 'RQ-') === 0 && !isset($rq[$id])) {
                achado($f08, $i + 1, "$id citado no 08, sem definição na decomposição do 00");
            } elseif (strpos($id, 'P-') === 0 && !isset($pp[$id])) {
                achado($f08, $i + 1, "$id citado no 08, sem definição em ## Premissas do 00");
            } elseif ((isset($rq[$id]) && !$rq[$id]) || (isset($pp[$id]) && !$pp[$id])) {
                achado($f08, $i + 1, "$id citado no 08, mas não vigente no 00");
            }
        }
        if (preg_match('/^#{1,6}\s*\[?(RM-\d+)\]?\b/', limpa($l), $m)) {
            if (isset($rm[$m[1]])) achado($f08, $i + 1, "{$m[1]} duplicado — definido antes na linha {$rm[$m[1]]}");
            else $rm[$m[1]] = $i + 1;
        }
    }
}

if (!$achados) exit(0);
usort($achados, function ($x, $y) { return [$x[0], $x[1]] <=> [$y[0], $y[1]]; });
foreach ($achados as [$a, $n, $msg]) echo "$a:$n: $msg\n";
exit(1);
PHP

php -r "$PHP_CODE" -- "$wiki"

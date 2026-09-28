#!/usr/bin/env bash
# rastreabilidade.sh — RQ/P-nn do 00 × passo do 01 × CT do 04.
#
# Uso (na raiz do projeto):
#   bash {skills}/feature-wiki/scripts/rastreabilidade.sh <wiki>
#     <wiki> = wikis/specs/{branch}/{feature} (a branch pode ter barra: ferro/501)
#
# O que confere (fontes: 00 = tabelas com ID RQ-nn/P-nn e coluna Estado, e a coluna Substitui das
#   tabelas de Adendo; 01 = ## Cobertura do Requisito e os ### N. de ## Estrutura de Implementação,
#   com **Atende** e **Bloqueado por**; 04 = ## Mapa de Regras, coluna Origem, e ## Índice de
#   Cenários, colunas ID e Regra — a coluna Costura não entra na conta):
#   0. Quem sai da cobrança: RQ com Estado "substituída por RQ-nn (Adendo N)" ou "decomposta em RQ-nn,
#      RQ-mm" (as filhas entram como RQ comuns; filha ou substituta que não existe no 00 é achado;
#      Estado "decomposta" sem RQ-nn depois de "em" é achado e a RQ continua na cobrança — o mesmo
#      critério do indice.sh); RQ citada na coluna Substitui de uma tabela de Adendo sem "(parcial)"
#      — o Adendo é imutável e não tem Estado; P-nn substituída, promovida ou revogada. E fora desta
#      entrega: RQ ou P-nn com passo "—" e justificativa na Observação da ## Cobertura do Requisito,
#      que sai de toda cobrança (passo, CT; ticket, no indice.sh --check) — o mesmo critério do
#      indice.sh. A justificativa é julgamento (quality gate, dimensão A).
#   1. RQ sem passo — RQ vigente sem linha na ## Cobertura do Requisito, com passo que não existe, ou
#      com passo "—" e Observação vazia.
#   2. RQ sem CT — RQ vigente, fechada e dentro da entrega sem regra do Mapa que o cite na Origem com
#      cenário vigente no Índice (riscado, "fundido em" e @obsoleto não contam). RQ "aberta — Qn" não
#      gera cenário: a linha "- RQ-nn — aberta (Qn), sem cenário até a resposta" do 04 é a forma esperada.
#   3. P sem CT — o mesmo para P-nn vigente (roteamento do achado: P-nn → CT → correção). P-nn não
#      precisa de passo, mas o passo que a Cobertura cita para ela (ou para qualquer outra linha) tem
#      de existir em ## Estrutura de Implementação.
#   4. passo sem RQ — ### N. sem **Atende** com RQ/P-nn e fora da ## Cobertura do Requisito.
#   5. RQ aberta implementada por passo não bloqueado — passo ligado a RQ "aberta — Qn" (pela
#      Cobertura ou pelo **Atende**) sem **Bloqueado por** que cite esse RQ.
#   Tabela fora do formato do template vira achado ("não conferido"), nunca silêncio.
# O que NÃO confere: alocação a ticket. RQ/P-nn × 07-tickets/ tem uma fonte só, o
#   feature-tickets/scripts/indice.sh --check.
#
# Quem chama: feature-wiki step 10 (reconciliação); feature-quality-gate, âncora mecânica da
#   dimensão A; feature-tickets, gate de entrada.
# Contrato: silêncio + exit 0 = OK; achado = uma linha `arquivo:linha: mensagem` + exit 1;
#   erro de uso ou de ambiente = mensagem no stderr + exit 2.
# Requer: bash e php no PATH (lógica em PHP embutido: o boost:add-skill descarta arquivos .php).
#   Sem jq, node, python ou grep -P.
#
# Exemplo de falha (saída real, fixture de 2026-09-27):
#   $ bash rastreabilidade.sh wikis/specs/ferro/501/aprovacao-pedido
#   wikis/specs/ferro/501/aprovacao-pedido/00-requisito.md:12: RQ-02 sem passo — fora da ## Cobertura do Requisito do 01
#   wikis/specs/ferro/501/aprovacao-pedido/00-requisito.md:15: RQ-05 sem CT — nenhuma regra do ## Mapa de Regras do 04 o cita na Origem com cenário vigente
#   wikis/specs/ferro/501/aprovacao-pedido/00-requisito.md:16: RQ-06 decomposta em RQ-08, que não existe no 00 — RQ-06 saiu da cobrança e RQ-08 não entrou
#   wikis/specs/ferro/501/aprovacao-pedido/00-requisito.md:29: P-01 sem CT — nenhuma regra do ## Mapa de Regras do 04 o cita na Origem com cenário vigente
#   wikis/specs/ferro/501/aprovacao-pedido/01-plano-acao.md:10: RQ-05 sem passo — aponta o passo 9, que não existe em ## Estrutura de Implementação
#   wikis/specs/ferro/501/aprovacao-pedido/01-plano-acao.md:12: P-01 aponta o passo 8, que não existe em ## Estrutura de Implementação
#   wikis/specs/ferro/501/aprovacao-pedido/01-plano-acao.md:16: passo 1 sem RQ — nem **Atende** nem a ## Cobertura do Requisito o ligam a RQ ou P-nn
#   wikis/specs/ferro/501/aprovacao-pedido/01-plano-acao.md:28: passo 4 implementa RQ-03 (aberta — Q1) sem **Bloqueado por**: RQ-03
#   (exit 1)
set -u

if [ $# -ne 1 ] || [ -z "$1" ]; then
  echo "uso: rastreabilidade.sh <wiki>   (wiki = wikis/specs/{branch}/{feature})" >&2
  exit 2
fi
command -v php >/dev/null 2>&1 || { echo "rastreabilidade.sh: php não está no PATH (o script usa PHP embutido)" >&2; exit 2; }

wiki=${1//\\//}
wiki=${wiki%/}
[ -d "$wiki" ] || { echo "rastreabilidade.sh: $wiki não é um diretório" >&2; exit 2; }
for f in 00-requisito.md 01-plano-acao.md 04-casos-de-teste.md; do
  [ -f "$wiki/$f" ] || { echo "rastreabilidade.sh: $wiki/$f não existe — a conferência exige o 00, o 01 e o 04 (rode depois do step 7)" >&2; exit 2; }
done

read -r -d '' PHP_CODE <<'PHP'
ini_set('display_errors', 'stderr');
error_reporting(E_ALL);

$wiki = $argv[1];
$f00 = "$wiki/00-requisito.md";
$f01 = "$wiki/01-plano-acao.md";
$f04 = "$wiki/04-casos-de-teste.md";

function ler($f) {
    $t = @file_get_contents($f);
    if ($t === false) { fwrite(STDERR, "rastreabilidade.sh: não consegui ler $f\n"); exit(2); }
    $t = preg_replace('/^\xEF\xBB\xBF/', '', $t);
    return preg_split('/\r\n|\n|\r/', $t);
}

function limpa($s) { return trim(str_replace(['*', '`', '~'], '', $s)); }

function celulas($l) {
    $l = trim($l);
    if ($l === '' || $l[0] !== '|') return null;
    $l = preg_replace('/^\||\|$/', '', $l);
    return array_map('trim', explode('|', $l));
}

function separador($l) {
    return strpos($l, '---') !== false && (bool) preg_match('/^[\s|:\-]+$/', $l);
}

// Cada tabela: ['cab' => células do cabeçalho, 'linhas' => [[nº da linha, células, texto cru], ...]]
function tabelas($ls) {
    $out = [];
    $n = count($ls);
    for ($i = 0; $i + 1 < $n; $i++) {
        $cab = celulas($ls[$i]);
        if ($cab === null || !separador($ls[$i + 1])) continue;
        $t = ['cab' => array_map('limpa', $cab), 'linhas' => []];
        for ($j = $i + 2; $j < $n; $j++) {
            $c = celulas($ls[$j]);
            if ($c === null) break;
            $t['linhas'][] = [$j + 1, $c, $ls[$j]];
        }
        $out[] = $t;
        $i = $j - 1;
    }
    return $out;
}

function coluna($cab, $prefixo) {
    foreach ($cab as $k => $v) if (stripos($v, $prefixo) === 0) return $k;
    return null;
}

function ids($s) {
    preg_match_all('/\b(?:RQ|P)-\d+\b/', $s, $m);
    return array_values(array_unique($m[0]));
}

$achados = [];
function achado($arq, $linha, $msg) { $GLOBALS['achados'][] = [$arq, $linha, $msg]; }

// ---- 00: RQ (vigente? aberta — Qn?) e P-nn (vigente?)
// Estado da RQ: fechada · aberta — Qn · substituída por RQ-nn (Adendo N) · decomposta em RQ-nn, RQ-mm.
// Tabela de Adendo (coluna Substitui): a RQ citada ali sem "(parcial)" sai; a linha do Adendo não
// declara estado da RQ nova (sem coluna Estado, o texto da cláusula não é lido como estado).
$rq = []; $pp = []; $subAdendo = []; $refs = [];
foreach (tabelas(ler($f00)) as $t) {
    $ce = coluna($t['cab'], 'Estado');
    $cs = coluna($t['cab'], 'Substitui');
    foreach ($t['linhas'] as [$n, $c, $cru]) {
        $id = limpa($c[0] ?? '');
        $est = ($ce !== null && isset($c[$ce])) ? $c[$ce] : ($cs !== null ? '' : $cru);
        if ($cs !== null && preg_match_all('/\b(RQ-\d+)\b(\s*\(\s*parcial)?/u', $c[$cs] ?? '', $mm, PREG_SET_ORDER)) {
            foreach ($mm as $m) if (($m[2] ?? '') === '') $subAdendo[$m[1]] = true;
        }
        if (preg_match('/^RQ-\d+$/', $id)) {
            // decomposta só com as filhas escritas ("decomposta em RQ-nn, RQ-mm"): o mesmo critério do
            // indice.sh. "decomposta" sozinha não tira a RQ da cobrança, senão ela sumia calada.
            $filhas = (preg_match('/decomposta\s+em\s+(.+)$/u', $est, $m) && preg_match_all('/\bRQ-\d+\b/', $m[1], $mf))
                ? array_values(array_unique($mf[0])) : [];
            $sai = preg_match('/substitu[íi]da/u', $est) || $filhas;
            if (!$sai && $ce !== null && isset($c[$ce]) && preg_match('/decomposta/u', $est)) {
                achado($f00, $n, "$id decomposta sem as filhas — escreva \"decomposta em RQ-nn, RQ-mm\" no Estado; até lá $id continua na cobrança");
            }
            $q = preg_match('/aberta\s*(?:—|–|-)?\s*\(?(Q\d+)/u', $est, $m) ? $m[1] : null;
            if (!isset($rq[$id])) $rq[$id] = ['linha' => $n, 'viva' => !$sai, 'q' => $q];
            else { $rq[$id]['viva'] = $rq[$id]['viva'] && !$sai; $rq[$id]['q'] = $rq[$id]['q'] ?? $q; }
            // quem substitui ou quem são as filhas: tem de existir no 00, senão a cobrança some calada
            if ($sai && $ce !== null && isset($c[$ce])) {
                if ($filhas) { $verbo = 'decomposta em'; $alvos = $filhas; }
                else { $verbo = 'substituída por'; preg_match_all('/\bRQ-\d+\b/', $est, $mm); $alvos = array_unique($mm[0]); }
                foreach ($alvos as $alvo) if ($alvo !== $id) $refs[] = [$id, $n, $verbo, $alvo];
            }
        } elseif (preg_match('/^P-\d+$/', $id) && !isset($pp[$id])) {
            $pp[$id] = ['linha' => $n, 'viva' => !preg_match('/substitu[íi]da|promovida|revogada/u', $est)];
        }
    }
}
foreach (array_keys($subAdendo) as $id) if (isset($rq[$id])) $rq[$id]['viva'] = false;
foreach ($refs as [$id, $n, $verbo, $alvo]) {
    if (!isset($rq[$alvo])) achado($f00, $n, "$id $verbo $alvo, que não existe no 00 — $id saiu da cobrança e $alvo não entrou");
}

// ---- 01: ## Cobertura do Requisito e ### N. de ## Estrutura de Implementação
$l01 = ler($f01);
$cob = null;
foreach (tabelas($l01) as $t) {
    $cp = coluna($t['cab'], 'Passo');
    if (($t['cab'][0] ?? '') !== 'RQ' || $cp === null) continue;
    $co = coluna($t['cab'], 'Observa');
    if ($cob === null) $cob = [];
    foreach ($t['linhas'] as [$n, $c]) {
        $id = limpa($c[0] ?? '');
        if (!preg_match('/^(?:RQ|P)-\d+$/', $id)) continue;
        // link markdown: fica o texto ([3](#3-policy) → 3), sai o URL, cujos dígitos não são passo
        $txt = preg_replace('/\[([^\]]*)\]\([^)]*\)/', '$1', $c[$cp] ?? '');
        preg_match_all('/\d+/', $txt, $mm);
        $obs = limpa($co !== null ? ($c[$co] ?? '') : '');
        $cob[$id] = ['linha' => $n, 'passos' => array_values(array_unique(array_map('intval', $mm[0]))),
            'just' => $obs !== '' && !preg_match('/^[—–\-\s]*$/u', $obs)];
    }
}
$temCob = $cob !== null;
if (!$temCob) {
    achado($f01, 1, 'sem ## Cobertura do Requisito no formato do template (1ª coluna RQ, coluna Passo) — RQ × passo não conferido');
    $cob = [];
}

$passos = []; $naEstrutura = false; $atual = null; $cerca = false; $temEstrutura = false; $coment = false;
foreach ($l01 as $i => $l) {
    if (preg_match('/^\s*(```|~~~)/', $l)) { $cerca = !$cerca; continue; }
    if ($cerca) continue;
    if ($coment) { if (strpos($l, '-->') === false) continue; $l = substr($l, strpos($l, '-->') + 3); $coment = false; }
    $l = preg_replace('/<!--.*?-->/', '', $l);
    if (($p = strpos($l, '<!--')) !== false) { $l = substr($l, 0, $p); $coment = true; }
    if (preg_match('/^##\s+(.+)$/u', $l, $m)) {
        $naEstrutura = (bool) preg_match('/^Estrutura de Implementa/iu', trim($m[1]));
        $temEstrutura = $temEstrutura || $naEstrutura;
        $atual = null;
        continue;
    }
    if (!$naEstrutura) continue;
    if (preg_match('/^###\s+(\d+)\s*[.)]/', $l, $m)) {
        $atual = (int) $m[1];
        $passos[$atual] = ['linha' => $i + 1, 'atende' => [], 'bloq' => []];
        continue;
    }
    if ($atual === null) continue;
    if (preg_match('/\*\*Atende\*\*\s*:(.*)$/u', $l, $m)) $passos[$atual]['atende'] = array_merge($passos[$atual]['atende'], ids($m[1]));
    if (preg_match('/\*\*Bloqueado por\*\*\s*:(.*)$/u', $l, $m)) $passos[$atual]['bloq'] = array_merge($passos[$atual]['bloq'], ids($m[1]));
}
if (!$temEstrutura) achado($f01, 1, 'sem ## Estrutura de Implementação com passos ### N. — passo × RQ não conferido');

// ---- 04: ## Mapa de Regras (Origem) e ## Índice de Cenários (Regra)
$origem = []; $ctPorRegra = []; $cbPorRegra = []; $temMapa = false; $temIndice = false;
foreach (tabelas(ler($f04)) as $t) {
    $cab0 = $t['cab'][0] ?? '';
    $creg = coluna($t['cab'], 'Regra');
    $cori = coluna($t['cab'], 'Origem');
    $ccen = coluna($t['cab'], 'Cenário');
    foreach ($t['linhas'] as [$n, $c, $cru]) {
        $c0 = str_replace(['*', '`'], '', $c[0] ?? '');
        if ($cab0 === 'ID' && $creg !== null && preg_match('/^\s*(~~)?\s*(CT-B?\d+)/', $c0, $m)) {
            $temIndice = true;
            $obs = $m[1] !== '' || stripos($cru, 'fundido em') !== false || stripos($cru, '@obsoleto') !== false;
            if ($obs) continue;
            preg_match_all('/\bR\d+\b/', $c[$creg] ?? '', $rr);
            foreach ($rr[0] as $r) $ctPorRegra[$r] = true;
        } elseif (stripos($cab0, 'Regra') === 0 && $cori !== null && preg_match('/^(R\d+)\b/', limpa($c[0] ?? ''), $m)) {
            $temMapa = true;
            foreach (ids($c[$cori] ?? '') as $id) $origem[$id][] = $m[1];
            if ($ccen !== null && preg_match('/\bCT-B\d+/', $c[$ccen] ?? '')) $cbPorRegra[$m[1]] = true;
        }
    }
}
if (!$temMapa) achado($f04, 1, 'sem ## Mapa de Regras com coluna Origem — RQ × CT não conferido');
if (!$temIndice) achado($f04, 1, 'sem ## Índice de Cenários (colunas ID e Regra) — RQ × CT não conferido');

function temCT($id) {
    foreach ($GLOBALS['origem'][$id] ?? [] as $r) {
        if (isset($GLOBALS['ctPorRegra'][$r]) || isset($GLOBALS['cbPorRegra'][$r])) return true;
    }
    return false;
}

// ---- 1, 2, 3
$vincPasso = [];
foreach ($cob as $id => $linha) foreach ($linha['passos'] as $p) $vincPasso[$p] = true;
foreach ($rq as $id => $r) {
    if (!$r['viva']) continue;
    $foraDaEntrega = false;
    if (!isset($cob[$id])) {
        if ($temCob) achado($f00, $r['linha'], "$id sem passo — fora da ## Cobertura do Requisito do 01");
    } else {
        $c = $cob[$id];
        if (!$c['passos']) {
            if ($c['just']) $foraDaEntrega = true;
            else achado($f01, $c['linha'], "$id sem passo — passo \"—\" e Observação vazia na ## Cobertura do Requisito");
        }
        foreach ($c['passos'] as $p) {
            if ($temEstrutura && !isset($passos[$p])) achado($f01, $c['linha'], "$id sem passo — aponta o passo $p, que não existe em ## Estrutura de Implementação");
        }
    }
    if ($r['q'] === null && !$foraDaEntrega && $temMapa && $temIndice && !temCT($id)) {
        achado($f00, $r['linha'], "$id sem CT — nenhuma regra do ## Mapa de Regras do 04 o cita na Origem com cenário vigente");
    }
}
foreach ($pp as $id => $p) {
    if (!$p['viva']) continue;
    $foraDaEntrega = isset($cob[$id]) && !$cob[$id]['passos'] && $cob[$id]['just'];
    if (!$foraDaEntrega && $temMapa && $temIndice && !temCT($id)) {
        achado($f00, $p['linha'], "$id sem CT — nenhuma regra do ## Mapa de Regras do 04 o cita na Origem com cenário vigente");
    }
}
// passo citado na Cobertura por linha que não é RQ vigente (P-nn, RQ que saiu): não é obrigatório,
// mas o que está escrito tem de existir — referência pendente passava calada, e silêncio vale OK
foreach ($cob as $id => $c) {
    if (isset($rq[$id]) && $rq[$id]['viva']) continue;
    foreach ($c['passos'] as $p) {
        if ($temEstrutura && !isset($passos[$p])) achado($f01, $c['linha'], "$id aponta o passo $p, que não existe em ## Estrutura de Implementação");
    }
}

// ---- 4, 5
ksort($passos);
foreach ($passos as $n => $p) {
    if (!$p['atende'] && !isset($vincPasso[$n])) {
        achado($f01, $p['linha'], "passo $n sem RQ — nem **Atende** nem a ## Cobertura do Requisito o ligam a RQ ou P-nn");
    }
}
foreach ($rq as $id => $r) {
    if (!$r['viva'] || $r['q'] === null) continue;
    $impl = $cob[$id]['passos'] ?? [];
    foreach ($passos as $n => $p) if (in_array($id, $p['atende'], true)) $impl[] = $n;
    foreach (array_unique($impl) as $n) {
        if (!isset($passos[$n])) continue;
        if (!in_array($id, $passos[$n]['bloq'], true)) {
            achado($f01, $passos[$n]['linha'], "passo $n implementa $id (aberta — {$r['q']}) sem **Bloqueado por**: $id");
        }
    }
}

// RQ/P-nn × ticket não é daqui: a fonte única é o indice.sh --check da feature-tickets.

if (!$achados) exit(0);
usort($achados, function ($a, $b) { return [$a[0], $a[1]] <=> [$b[0], $b[1]]; });
$vistos = [];
foreach ($achados as [$a, $l, $m]) {
    $s = "$a:$l: $m";
    if (isset($vistos[$s])) continue;
    $vistos[$s] = true;
    echo $s, "\n";
}
exit(1);
PHP

php -r "$PHP_CODE" -- "$wiki"

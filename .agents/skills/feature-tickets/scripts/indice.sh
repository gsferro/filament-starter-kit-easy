#!/usr/bin/env bash
# indice.sh — quadro dos tickets (no repositório e no chat) e conferência dos tickets de uma wiki.
#
# Uso (na raiz do projeto):
#   bash {skills}/feature-tickets/scripts/indice.sh                   gera os quadros (grava arquivos)
#   bash {skills}/feature-tickets/scripts/indice.sh --status <wiki>   mostra o quadro de <wiki> (não grava)
#   bash {skills}/feature-tickets/scripts/indice.sh --check <wiki>    confere os tickets de <wiki>
#     <wiki> = wikis/specs/{branch}/{feature} (a branch pode ter barra: ferro/501)
#
# Modo padrão (gera): acha todo wikis/specs/**/03-progresso.md e lê, na mesma pasta, o 00, o
#   06-relatorio-qa.md e os 07-tickets/*.md. Grava (1) {wiki}/07-tickets/README.md de cada feature
#   fatiada — barra de progresso, contagem por status, tabela por ticket (NN, entrega, status, CT
#   verdes, bloqueado por), fronteira e o grafo de dependências em Mermaid colorido por status — e
#   (2) wikis/specs/INDEX.md, uma linha por wiki: branch, feature, estado do 03, tickets por status,
#   progresso, fronteira, veredito do 06, links. Imprime os paths gravados (exit 0). Determinístico:
#   sem data, ordenado por path; rodar de novo sem mudança na wiki não muda nenhum arquivo. No 03,
#   checkbox e **Estado** dentro de bloco cercado (```) ou de comentário HTML não contam.
# Modo --status: imprime no stdout a visão compacta em texto — a primeira linha é o resumo de uma
#   linha (feature · concluídos · em revisão · em execução · fronteira), depois a barra e a tabela por
#   ticket. Não grava nada; exit 0. Wiki sem 07-tickets/ = uma linha "não fatiada".
#   Limite dos dois modos: o script lê arquivos, não roda testes. CT verdes são inferidos do Status
#   (em revisão e concluído = todos verdes; pronto = nenhum); ticket em execução mostra "—".
# Modo --check: confere os tickets contra o 00, o 01, o 03, o 04 e o 05 da wiki. É a fonte única da
#   alocação a ticket (o rastreabilidade.sh da feature-wiki não confere ticket). 07-tickets/README.md
#   é o quadro gerado, não ticket: é ignorado.
#   1. Alocação: todo RQ e P-nn vigente do 00 está em exatamente um ticket (**RQ cobertas**); todo CT
#      do 04 e todo CT-B do 05 também (fora os riscados, fundidos ou @obsoleto). Saem da cobrança, e
#      citá-los em ticket é achado: RQ substituída (Estado "substituída…" ou citada sem "(parcial)" na
#      coluna Substitui de um Adendo), RQ "decomposta em RQ-nn, …" (as filhas entram), P-nn não
#      vigente e RQ/P-nn fora desta entrega (## Cobertura do Requisito do 01 com passo "—" e
#      justificativa na Observação, aberta ou não — o mesmo critério do rastreabilidade.sh). ID citado
#      em ticket existe no 00/04/05.
#      A linha da tabela de Adendo (coluna Substitui, sem Estado) não declara estado da RQ que nasce
#      nela: o texto verbatim da cláusula não é lido como estado (mesmo critério do rastreabilidade.sh).
#   2. Forma: campos fixos presentes; Status do enum; "em revisão" e "concluído" com " — {evidência}";
#      título "# NN:" igual ao número do arquivo; nome NN-slug.md sem número repetido; campo opcional
#      **Issue** (gravado pelo espelho-gh.sh) no formato #N, dono/repo#N ou URL da issue.
#   3. Arestas: bloqueador existe e tem número menor; Qn citada existe no 00 e ainda está aberta
#      (respondida ou retirada sai de Bloqueado por); RQ "aberta — Qn" só em ticket bloqueado por Qn;
#      ticket em execução, em revisão ou concluído sem bloqueio pendente. RQ na cobrança com Estado
#      "aberta — Qn" e Qn respondida, retirada ou ausente do 00 = um achado na linha da RQ no 00 (o
#      Estado é que está velho), e o ticket não é acusado por esse par RQ/Qn.
#   4. Fatia vertical: todo passo que a ## Cobertura do Requisito do 01 liga a um RQ ou P-nn do ticket
#      está em Passos do 01 envolvidos (link [3](#…) conta como 3; no contract, também vale o passo de
#      um expand ou migrate que o bloqueia); todo CT tem ao menos uma origem (Mapa de Regras do 04) no
#      ticket e as demais nos tickets que o bloqueiam (fora expand e migrate); CT do Gherkin fora do
#      Índice de Cenários é achado; **Costura** do ticket cita o Grupo (ou, sem a coluna Grupo, a
#      Costura) de cada CT dele na coluna Costura do Índice de Cenários (coluna Camada = nome antigo).
#      O campo é lido como itens "{Grupo} — {costura}" separados por ; ou , e o nome é comparado
#      inteiro, sem diferenciar caixa: o grupo "Pagamento" não é citado por "Pagamento na tela".
#   5. Tipos: prefactoring é o 00, sem RQ nem CT; ticket comum tem RQ ou P-nn; todo ticket fora o
#      prefactoring tem CT; migrate bloqueado pelo expand; contract depois de todo migrate.
#   6. Espelho: ## Tickets do 03 tem a linha "Fatiamento confirmado", não tem mais "Não fatiado" e tem
#      uma linha por ticket, com o mesmo Status ([x] só se concluído). Única divergência aceita: o 03
#      diz "pronto" e o ticket diz "em execução — …, sessão nova".
#
# Quem chama: feature-tickets (passo 7 — gravar —, despacho, fechamento, subcomando status);
#   espelho-gh.sh (--check antes de espelhar); step 10 da feature-wiki (tickets entre as fontes);
#   step 11 da feature-wiki (quadros atualizados no PR) e o quality gate (coluna Ticket da Matriz).
#   A pessoa: `! bash {skills}/feature-tickets/scripts/indice.sh --status {wiki}` no Claude Code (zero token
#   de modelo com "respondToBashCommands": false; por padrão o Claude Code responde à saída do !).
# Contrato: gera = paths gravados + exit 0; --status = o quadro + exit 0. --check = silêncio + exit 0
#   quando OK; achado = uma linha `arquivo:linha: mensagem` + exit 1. Erro de uso ou de ambiente =
#   mensagem no stderr + exit 2.
# Limites: lê tabelas Markdown no formato dos templates (cabeçalho + linha de separador). Tabela fora
#   do formato vira achado ("… não conferida"), nunca silêncio. Confere alocação e arestas, não o
#   julgamento de granularidade — esse é o quiz com o usuário.
# Requer: bash e php no PATH (lógica em PHP embutido: o boost:add-skill descarta arquivos .php). O
#   PHP vai por arquivo temporário: no Windows a linha de comando tem teto de 32.767 caracteres.
#   Sem jq, node, python ou grep -P.
#
# Exemplo de falha (saída real, fixture de 2026-09-27):
#   $ bash indice.sh --check wikis/specs/ferro/501/aprov
#   wikis/specs/ferro/501/aprov/03-progresso.md:6: ## Tickets sem a linha "Fatiamento confirmado: {data} — {quem} — {rodadas}, {perguntas}" — o quiz aprovado não está registrado
#   wikis/specs/ferro/501/aprov/03-progresso.md:9: ## Tickets ainda diz "Não fatiado — …" e a feature tem 07-tickets/ — a linha vira "Fatiamento confirmado: {data} — {quem} — {rodadas}, {perguntas}"
#   wikis/specs/ferro/501/aprov/07-tickets/02-aprovador-decide.md:4: RQ-02 exige o(s) passo(s) 3 (Cobertura do Requisito do 01), fora de **Passos do 01 envolvidos** — a fatia não fecha vertical
#   wikis/specs/ferro/501/aprov/07-tickets/02-aprovador-decide.md:4: RQ-03 está decomposta no 00 (em RQ-08, RQ-09) — o ticket cobre as filhas
#   wikis/specs/ferro/501/aprov/07-tickets/03-email-de-recebimento.md:4: RQ-06 está substituída no 00 — o ticket cobre a RQ substituta
#   wikis/specs/ferro/501/aprov/07-tickets/04-solicitante-cancela.md:4: RQ-07 está fora desta entrega no 01 (## Cobertura do Requisito sem passo, com justificativa) — não entra em ticket
#   wikis/specs/ferro/501/aprov/07-tickets/04-solicitante-cancela.md:11: **Costura** não cita o grupo "Cancelamento" de CT-09 (## Índice de Cenários do 04) — o executor não sabe onde o teste se prende
#   wikis/specs/ferro/501/aprov/07-tickets/05-diretoria-acima-do-teto.md:12: "em execução" com bloqueio pendente: 02, Q1 (aberta) — só ticket da fronteira é despachado
#   (exit 1)
set -u

uso() {
  echo "uso: indice.sh                   gera wikis/specs/INDEX.md e 07-tickets/README.md das features fatiadas" >&2
  echo "     indice.sh --status <wiki>   mostra o quadro dos tickets de wikis/specs/{branch}/{feature} (não grava)" >&2
  echo "     indice.sh --check <wiki>    confere os tickets de wikis/specs/{branch}/{feature}" >&2
  exit 2
}

modo=gerar
wiki=
case "${1-}" in
  '') [ $# -eq 0 ] || uso ;;
  --check) [ $# -eq 2 ] || uso; modo=check; wiki=$2 ;;
  --status) [ $# -eq 2 ] || uso; modo=status; wiki=$2 ;;
  *) uso ;;
esac

command -v php >/dev/null 2>&1 || { echo "indice.sh: php não está no PATH (o script usa PHP embutido)" >&2; exit 2; }

if [ "$modo" = gerar ]; then
  [ -d wikis/specs ] || { echo "indice.sh: wikis/specs/ não existe — rode na raiz do projeto" >&2; exit 2; }
else
  wiki=${wiki//\\//}
  wiki=${wiki%/}
  [ -n "$wiki" ] || uso
  [ -d "$wiki" ] || { echo "indice.sh: $wiki não é um diretório" >&2; exit 2; }
fi
if [ "$modo" = check ]; then
  for f in 00-requisito.md 01-plano-acao.md 04-casos-de-teste.md; do
    [ -f "$wiki/$f" ] || { echo "indice.sh: $wiki/$f não existe — a feature-tickets exige o 00, o 01 e o 04" >&2; exit 2; }
  done
  [ -d "$wiki/07-tickets" ] || { echo "indice.sh: $wiki/07-tickets/ não existe — a feature não foi fatiada; nada a conferir" >&2; exit 2; }
fi

read -r -d '' PHP_CODE <<'PHP'
ini_set('display_errors', 'stderr');
error_reporting(E_ALL);

const STATUS = ['pronto', 'em execução', 'em revisão', 'concluído'];
const CAMPOS = ['Entrega', 'RQ cobertas', 'CT que ficam verdes', 'CT-B', 'Passos do 01 envolvidos',
    'Bloqueado por', 'Bloqueia', 'Prefactoring', 'Costura', 'Status'];
const OPCIONAIS = ['Issue'];
const ISSUE_RE = '~^(#\d+|[\w.-]+/[\w.-]+#\d+|https://github\.com/[\w.-]+/[\w.-]+/issues/\d+)$~';
const LIMITE = 'o script lê arquivos, não roda testes; CT verdes inferidos do Status; em execução mostra —';

$modo = $argv[1] ?? 'gerar';
$wiki = rtrim(str_replace('\\', '/', $argv[2] ?? ''), '/');

function ler($f) {
    if (!is_file($f)) return null;
    $t = file_get_contents($f);
    if ($t === false) return null;
    $t = preg_replace('/^\xEF\xBB\xBF/', '', $t);
    return preg_split('/\r\n|\n|\r/', $t);
}

// Apaga o conteúdo de bloco cercado e de comentário HTML, mantendo a numeração das linhas.
function semCerca($ls) {
    if ($ls === null) return null;
    $cerca = false;
    $coment = false;
    foreach ($ls as $i => $l) {
        if (preg_match('/^\s*(```|~~~)/', $l)) { $cerca = !$cerca; $ls[$i] = ''; continue; }
        if ($cerca) { $ls[$i] = ''; continue; }
        if ($coment) {
            if (($p = strpos($l, '-->')) === false) { $ls[$i] = ''; continue; }
            $l = substr($l, $p + 3);
            $coment = false;
        }
        $l = preg_replace('/<!--.*?-->/', '', $l);
        if (($p = strpos($l, '<!--')) !== false) { $l = substr($l, 0, $p); $coment = true; }
        $ls[$i] = $l;
    }
    return $ls;
}

function limpa($s) { return trim(str_replace(['*', '`', '~'], '', $s)); }

// link markdown: fica o texto ([3](#3-policy) → 3), sai o URL, cujos dígitos não são passo
function textoDoLink($s) { return preg_replace('/\[([^\]]*)\]\([^)]*\)/', '$1', $s); }

function celulas($l) {
    $l = trim($l);
    if ($l === '' || $l[0] !== '|') return null;
    $l = preg_replace('/^\||\|$/', '', $l);
    return array_map('trim', explode('|', $l));
}

function separador($l) {
    return strpos($l, '---') !== false && (bool) preg_match('/^[\s|:\-]+$/', $l);
}

// Cada tabela: ['cab' => células do cabeçalho, 'linha' => nº da linha do cabeçalho,
// 'linhas' => [[nº da linha, células, texto cru], ...]]
function tabelas($ls) {
    $out = [];
    $n = count($ls);
    for ($i = 0; $i + 1 < $n; $i++) {
        $cab = celulas($ls[$i]);
        if ($cab === null || !separador($ls[$i + 1])) continue;
        $t = ['cab' => array_map('limpa', $cab), 'linha' => $i + 1, 'linhas' => []];
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

// largura, corte e preenchimento por caractere (sem depender de mbstring)
function larg($s) { return preg_match_all('/./us', $s); }
function corta($s, $n) {
    if (larg($s) <= $n) return $s;
    preg_match_all('/./us', $s, $m);
    return rtrim(implode('', array_slice($m[0], 0, $n - 1))) . '…';
}
function pad($s, $n) { return $s . str_repeat(' ', max(0, $n - larg($s))); }
function minusc($s) { return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s); }

// 00: RQ (substituída? decomposta? aberta — Qn?), P-nn (vigente?), Qn (aberta?)
// Tabela de Adendo (coluna Substitui, sem Estado): a linha não declara estado da RQ nova — o texto
// verbatim da cláusula ("nota fiscal substituída…") não é lido como estado. Mesmo critério do
// rastreabilidade.sh da feature-wiki.
function ler00($f) {
    $r = ['rq' => [], 'p' => [], 'q' => []];
    $ls = ler($f);
    if ($ls === null) return $r;
    $porAdendo = [];
    foreach (tabelas($ls) as $t) {
        $ce = coluna($t['cab'], 'Estado');
        $cs = coluna($t['cab'], 'Substitui');
        foreach ($t['linhas'] as [$n, $c, $cru]) {
            $id = limpa($c[0] ?? '');
            $est = ($ce !== null && isset($c[$ce])) ? $c[$ce] : ($cs !== null ? '' : $cru);
            // Adendo é imutável: a RQ citada em Substitui sem "(parcial)" está substituída
            if ($cs !== null && isset($c[$cs]) && preg_match_all('/\b(RQ-\d+)\b(\s*\(\s*parcial)?/u', $c[$cs], $mm, PREG_SET_ORDER)) {
                foreach ($mm as $m) if (empty($m[2])) $porAdendo[$m[1]] = true;
            }
            if (preg_match('/^RQ-\d+$/', $id)) {
                $sub = (bool) preg_match('/substitu[íi]da/u', $est);
                $dec = null;
                if (preg_match('/decomposta\s+em\s+(.+)$/u', $est, $m) && preg_match_all('/\bRQ-\d+\b/', $m[1], $mf)) $dec = $mf[0];
                $q = preg_match('/aberta\s*(?:—|–|-)?\s*\(?(Q\d+)/u', $est, $m) ? $m[1] : null;
                if (!isset($r['rq'][$id])) {
                    $r['rq'][$id] = ['linha' => $n, 'sub' => $sub, 'dec' => $dec, 'q' => $q];
                } else {
                    $r['rq'][$id]['sub'] = $r['rq'][$id]['sub'] || $sub;
                    $r['rq'][$id]['dec'] = $r['rq'][$id]['dec'] ?? $dec;
                    $r['rq'][$id]['q'] = $r['rq'][$id]['q'] ?? $q;
                }
            } elseif (preg_match('/^P-\d+$/', $id) && !isset($r['p'][$id])) {
                $r['p'][$id] = ['linha' => $n, 'viva' => !preg_match('/substitu[íi]da|promovida|revogada/u', $est)];
            } elseif (preg_match('/^Q\d+$/', $id) && !isset($r['q'][$id])) {
                $r['q'][$id] = ['linha' => $n, 'aberta' => !preg_match('/respondida|retirada/u', $est)];
            }
        }
    }
    foreach (array_keys($porAdendo) as $id) if (isset($r['rq'][$id])) $r['rq'][$id]['sub'] = true;
    return $r;
}

// 01: ## Cobertura do Requisito → RQ/P-nn => passos; 'fora' = sem passo e com justificativa na
// Observação (fora desta entrega — o mesmo critério do rastreabilidade.sh). null = sem a tabela.
function ler01($f) {
    $ls = ler($f);
    if ($ls === null) return null;
    $cob = null;
    foreach (tabelas($ls) as $t) {
        $cp = coluna($t['cab'], 'Passo');
        if (($t['cab'][0] ?? '') !== 'RQ' || $cp === null) continue;
        $co = coluna($t['cab'], 'Observa');
        if ($cob === null) $cob = [];
        foreach ($t['linhas'] as [$n, $c]) {
            $id = limpa($c[0] ?? '');
            if (!preg_match('/^(?:RQ|P)-\d+$/', $id)) continue;
            preg_match_all('/\d+/', textoDoLink($c[$cp] ?? ''), $pp);
            $ps = array_values(array_unique(array_map('intval', $pp[0])));
            $obs = limpa($co !== null ? ($c[$co] ?? '') : '');
            $cob[$id] = ['linha' => $n, 'passos' => $ps, 'fora' => !$ps && !preg_match('/^[—–\-\s]*$/u', $obs)];
        }
    }
    return $cob;
}

// 04: CT do Índice de Cenários (riscado/fundido = obsoleto; colunas Regra, Grupo, Costura) e Mapa de
// Regras (Origem).
function ler04($f) {
    $r = ['ct' => [], 'mapa' => [], 'indice' => false, 'temMapa' => false,
        'colCostura' => false, 'colGrupo' => false, 'colCamada' => false, 'linhaIndice' => 1];
    $ls = ler($f);
    if ($ls === null) return $r;
    foreach (tabelas($ls) as $t) {
        $cab0 = $t['cab'][0] ?? '';
        $creg = coluna($t['cab'], 'Regra');
        $cori = coluna($t['cab'], 'Origem');
        $cgr = coluna($t['cab'], 'Grupo');
        $ccos = coluna($t['cab'], 'Costura');
        foreach ($t['linhas'] as [$n, $c, $cru]) {
            $c0 = str_replace(['*', '`'], '', $c[0] ?? '');
            if ($cab0 === 'ID' && $creg !== null && preg_match('/^\s*(~~)?\s*(CT-\d+)/', $c0, $m)) {
                if (!$r['indice']) {
                    $r['indice'] = true;
                    $r['colCostura'] = $ccos !== null;
                    $r['colGrupo'] = $cgr !== null;
                    $r['colCamada'] = coluna($t['cab'], 'Camada') !== null;
                    $r['linhaIndice'] = $t['linha'];
                }
                $obs = $m[1] !== '' || stripos($cru, 'fundido em') !== false || stripos($cru, '@obsoleto') !== false;
                preg_match_all('/\bR\d+\b/', $c[$creg] ?? '', $rr);
                if (!isset($r['ct'][$m[2]])) $r['ct'][$m[2]] = ['linha' => $n, 'obs' => $obs, 'regras' => $rr[0],
                    'grupo' => $cgr !== null ? limpa($c[$cgr] ?? '') : '', 'costura' => $ccos !== null ? limpa($c[$ccos] ?? '') : ''];
            } elseif (stripos($cab0, 'Regra') === 0 && $cori !== null && preg_match('/^(R\d+)\b/', limpa($c[0] ?? ''), $m)) {
                $r['temMapa'] = true;
                preg_match_all('/\b(?:RQ|P)-\d+\b/', $c[$cori] ?? '', $oo);
                $r['mapa'][$m[1]] = ['linha' => $n, 'origens' => array_values(array_unique($oo[0]))];
            }
        }
    }
    foreach ($ls as $i => $l) {
        if (preg_match('/Cen[áa]rio[^:\[]*:\s*\[(CT-\d+)\]/u', $l, $m) && !isset($r['ct'][$m[1]])) {
            $obs = $i > 0 && strpos($ls[$i - 1], '@obsoleto') !== false;
            $r['ct'][$m[1]] = ['linha' => $i + 1, 'obs' => $obs, 'regras' => null, 'grupo' => '', 'costura' => ''];
        }
    }
    return $r;
}

// 05: CT-B por heading "## CT-Bnn" ou Gherkin "[CT-Bnn]".
function ler05($f) {
    $r = [];
    $ls = ler($f);
    if ($ls === null) return $r;
    foreach ($ls as $i => $l) {
        if (preg_match('/^#{2,4}\s*(~~)?\s*(CT-B\d+)/', $l, $m)) {
            $id = $m[2];
            $obs = $m[1] !== '';
        } elseif (preg_match('/Cen[áa]rio[^:\[]*:\s*\[(CT-B\d+)\]/u', $l, $m)) {
            $id = $m[1];
            $obs = false;
        } else {
            continue;
        }
        $obs = $obs || stripos($l, '@obsoleto') !== false || ($i > 0 && strpos($ls[$i - 1], '@obsoleto') !== false);
        if (!isset($r[$id])) $r[$id] = ['linha' => $i + 1, 'obs' => $obs];
        elseif ($obs) $r[$id]['obs'] = true;
    }
    return $r;
}

// Tickets de 07-tickets/ — o README.md é o quadro gerado, não ticket.
function lerTickets($dir) {
    $tks = [];
    if (!is_dir($dir)) return $tks;
    $arqs = glob($dir . '/*.md') ?: [];
    sort($arqs, SORT_STRING);
    foreach ($arqs as $a) {
        $a = str_replace('\\', '/', $a);
        if (basename($a) === 'README.md') continue;
        $t = ['arq' => $a, 'num' => null, 'tipo' => 'fatia', 'campos' => [], 'titulo' => null,
            'ids' => [], 'passos' => [], 'bloq' => [], 'bloqQ' => [], 'status' => null, 'resto' => '', 'nct' => 0];
        if (preg_match('/^(\d{2})-([a-z0-9][a-z0-9-]*)\.md$/', basename($a), $m)) {
            $t['num'] = $m[1];
            foreach (['prefactor', 'expand', 'migrate', 'contract'] as $tipo) {
                if ($m[2] === $tipo || strpos($m[2], $tipo . '-') === 0) $t['tipo'] = $tipo;
            }
        }
        foreach (ler($a) ?? [] as $i => $l) {
            if ($t['titulo'] === null && preg_match('/^#\s+(\d{2})\s*:\s*(\S.*)$/u', $l, $m)) $t['titulo'] = [$i + 1, $m[1], limpa(textoDoLink($m[2]))];
            if (preg_match('/^\s*\*\*([^*]+?)\s*:?\s*\*\*\s*:?\s*(.*)$/u', $l, $m)) {
                $nome = trim($m[1]);
                if ((in_array($nome, CAMPOS, true) || in_array($nome, OPCIONAIS, true)) && !isset($t['campos'][$nome])) $t['campos'][$nome] = [$i + 1, trim($m[2])];
            }
        }
        foreach (['RQ cobertas' => '/\b(?:RQ|P)-\d+\b/', 'CT que ficam verdes' => '/\bCT-\d+\b/', 'CT-B' => '/\bCT-B\d+\b/'] as $campo => $re) {
            if (!isset($t['campos'][$campo])) continue;
            preg_match_all($re, $t['campos'][$campo][1], $mm);
            foreach (array_unique($mm[0]) as $id) $t['ids'][$id] = $t['campos'][$campo][0];
            if ($campo !== 'RQ cobertas') $t['nct'] += count(array_unique($mm[0]));
        }
        if (isset($t['campos']['Passos do 01 envolvidos'])) {
            $v = preg_split('/\s+—\s+/u', $t['campos']['Passos do 01 envolvidos'][1])[0];
            // [3](#3-policy) conta como 3; o link da seção ([01 › Estrutura…](…)) não é passo
            $v = preg_replace_callback('/\[([^\]]*)\]\([^)]*\)/', function ($m) { return preg_match('/^\s*(?:passo\s*)?\d+\s*$/iu', $m[1]) ? $m[1] : ''; }, $v);
            preg_match_all('/\d+/', $v, $mm);
            $t['passos'] = array_values(array_unique(array_map('intval', $mm[0])));
        }
        if (isset($t['campos']['Bloqueado por'])) {
            $v = textoDoLink($t['campos']['Bloqueado por'][1]);
            preg_match_all('/\bQ\d+\b/', $v, $mm);
            $t['bloqQ'] = array_values(array_unique($mm[0]));
            $v = preg_replace('/\b(?:Q|RQ-|P-|CT-B?)\d+\b/', '', $v);
            preg_match_all('/\b\d{1,3}\b/', $v, $mm);
            $t['bloq'] = array_values(array_unique(array_map(function ($x) { return sprintf('%02d', (int) $x); }, $mm[0])));
        }
        if (isset($t['campos']['Status']) && preg_match('/^(pronto|em execução|em revisão|concluído)(.*)$/u', $t['campos']['Status'][1], $m)) {
            $t['status'] = $m[1];
            $t['resto'] = $m[2];
        }
        $tks[] = $t;
    }
    return $tks;
}

function porNumero($tks) {
    $p = [];
    foreach ($tks as $k => $t) if ($t['num'] !== null && !isset($p[$t['num']])) $p[$t['num']] = $k;
    return $p;
}

// Tickets que bloqueiam $k, direta ou indiretamente (índices em $tks).
function antes($tks, $porNum, $k) {
    $vis = [];
    $pilha = $tks[$k]['bloq'];
    while ($pilha) {
        $b = array_pop($pilha);
        if (isset($vis[$b]) || !isset($porNum[$b])) continue;
        $vis[$b] = $porNum[$b];
        foreach ($tks[$porNum[$b]]['bloq'] as $x) $pilha[] = $x;
    }
    return array_values($vis);
}

function fronteira($tks, $porNum, $d00) {
    $fr = [];
    foreach ($tks as $t) {
        if ($t['status'] !== 'pronto' || $t['num'] === null) continue;
        $ok = true;
        foreach ($t['bloq'] as $b) if (!isset($porNum[$b]) || $tks[$porNum[$b]]['status'] !== 'concluído') $ok = false;
        foreach ($t['bloqQ'] as $q) if ($d00['q'][$q]['aberta'] ?? true) $ok = false;
        if ($ok) $fr[] = $t['num'];
    }
    return $fr;
}

function barra($feitos, $tot, $larg, $cheio, $vazio) {
    $n = $tot ? intdiv($feitos * $larg, $tot) : 0;
    $pct = $tot ? intdiv($feitos * 100, $tot) : 0;
    return '[' . str_repeat($cheio, $n) . str_repeat($vazio, $larg - $n) . "] $pct %";
}

// Quadro de uma wiki fatiada (null = sem tickets): o que o README, o INDEX e o --status mostram.
function quadro($w) {
    $tks = lerTickets("$w/07-tickets");
    if (!$tks) return null;
    $d00 = ler00("$w/00-requisito.md");
    $porNum = porNumero($tks);
    $fr = fronteira($tks, $porNum, $d00);
    $Q = ['tks' => [], 'cont' => array_fill_keys(STATUS, 0), 'inval' => 0, 'fr' => $fr, 'ctTot' => 0,
        'ctVerde' => 0, 'ctExec' => 0, 'qAbertas' => [], 'qFora' => [], 'arestas' => []];
    foreach ($tks as $t) {
        $st = $t['status'];
        if ($st === null) $Q['inval']++; else $Q['cont'][$st]++;
        $n = $t['nct'];
        $Q['ctTot'] += $n;
        if (in_array($st, ['em revisão', 'concluído'], true)) { $Q['ctVerde'] += $n; $cv = "$n/$n"; }
        elseif ($st === 'em execução') { if ($n) $Q['ctExec']++; $cv = "—/$n"; }
        elseif ($st === 'pronto') $cv = "0/$n";
        else $cv = "?/$n";
        if (!$n) $cv = '—';
        $bl = $t['bloq'];
        foreach ($t['bloqQ'] as $q) {
            $aberta = $d00['q'][$q]['aberta'] ?? null;
            $bl[] = "$q (" . ($aberta === null ? 'não está no 00' : ($aberta ? 'aberta' : 'respondida')) . ')';
            // Qn ausente do 00 não é pergunta aberta: vai ao grafo como nó inválido, não vermelho
            if ($aberta === true) $Q['qAbertas'][$q][] = $t['num'];
            elseif ($aberta === null) $Q['qFora'][$q][] = $t['num'];
        }
        if ($st === 'concluído') $classe = 'concluido';
        elseif ($st === 'em revisão') $classe = 'revisao';
        elseif ($st === 'em execução') $classe = 'execucao';
        elseif ($st === 'pronto') $classe = in_array($t['num'], $fr, true) ? 'pronto' : 'aguarda';
        else $classe = 'invalido';
        $rotulo = $st ?? 'status inválido';
        if ($classe === 'pronto') $rotulo .= ' · fronteira';
        if ($classe === 'aguarda') $rotulo .= ' · aguarda bloqueio';
        $num = $t['num'] ?? basename($t['arq'], '.md');
        $Q['tks'][] = ['num' => $num, 'arq' => basename($t['arq']), 'titulo' => $t['titulo'][2] ?? basename($t['arq'], '.md'),
            'status' => $st ?? 'status inválido', 'cv' => $cv, 'bloq' => $bl ? implode(', ', $bl) : '—',
            'classe' => $classe, 'rotulo' => $rotulo];
        if ($t['num'] !== null) foreach ($t['bloq'] as $b) if (isset($porNum[$b])) $Q['arestas'][] = [$b, $t['num']];
    }
    $Q['total'] = count($tks);
    $Q['concl'] = $Q['cont']['concluído'];
    return $Q;
}

function listaDe($Q, $status) {
    $l = [];
    foreach ($Q['tks'] as $x) if ($x['status'] === $status) $l[] = $x['num'];
    return $l;
}

function frTexto($Q) {
    if ($Q['fr']) return implode(', ', $Q['fr']);
    return $Q['concl'] === $Q['total'] ? 'todos concluídos' : 'nenhum';
}

function contTexto($Q) {
    $p = [];
    foreach ($Q['cont'] as $s => $n) if ($n) $p[] = "$s $n";
    if ($Q['inval']) $p[] = "status inválido {$Q['inval']}";
    return implode(' · ', $p);
}

function ctTexto($Q) {
    return "{$Q['ctVerde']} de {$Q['ctTot']}" . ($Q['ctExec'] ? ' (ticket em execução: —)' : '');
}

// Mermaid: entidades no rótulo (Mermaid converte #nome; e #nº; em entidade HTML)
function mmd($s) { return strtr($s, ['#' => '#35;', '"' => '#quot;', '<' => '#lt;', '>' => '#gt;', '&' => '#amp;']); }

function readme($w, $rel, $Q) {
    $esc = function ($s) { return str_replace('|', '\|', $s); };
    $feature = basename($rel);
    $branch = dirname($rel);
    $prof = str_repeat('../', count(explode('/', $rel)) + 1);
    $md = [
        "# Quadro dos tickets — `$feature`",
        '',
        '> **Gerado** por `scripts/indice.sh` da skill `feature-tickets`, a partir dos arquivos desta pasta.',
        '> **Não editar à mão**: rodar o script de novo — inclusive para resolver conflito de merge. O',
        '> script lê arquivos, não roda testes: CT verdes são inferidos do `Status` (`em revisão` e',
        '> `concluído` = todos verdes, `pronto` = nenhum), e ticket `em execução` mostra `—`.',
        '',
        '**Branch**: `' . ($branch === '.' ? '—' : $branch) . "` · **Wiki**: [`03-progresso.md`](../03-progresso.md) · **Todas as features**: [`INDEX.md`]({$prof}INDEX.md)",
        '',
        '## Progresso',
        '',
        '`' . barra($Q['concl'], $Q['total'], 20, '█', '░') . "` — {$Q['concl']} de {$Q['total']} tickets concluídos",
        '',
        '| pronto | em execução | em revisão | concluído |' . ($Q['inval'] ? ' status inválido |' : ''),
        '|---|---|---|---|' . ($Q['inval'] ? '---|' : ''),
        '| ' . implode(' | ', $Q['cont']) . ' |' . ($Q['inval'] ? " {$Q['inval']} |" : ''),
        '',
        '**Fronteira** (pode ser pego agora): ' . frTexto($Q) . ' · **CT verdes** (inferidos do `Status`): ' . ctTexto($Q),
        '',
        '## Tickets',
        '',
        '| NN | Entrega | Status | CT verdes | Bloqueado por |',
        '|---|---|---|---|---|',
    ];
    foreach ($Q['tks'] as $x) {
        $md[] = '| ' . implode(' | ', array_map($esc, ["[{$x['num']}]({$x['arq']})", $x['titulo'], $x['status'], $x['cv'], $x['bloq']])) . ' |';
    }
    $g = [];
    $classes = [];
    foreach ($Q['tks'] as $x) {
        $id = 't' . preg_replace('/[^A-Za-z0-9]/', '', $x['num']);
        $g[] = '  ' . $id . '["' . mmd($x['num'] . ' · ' . corta($x['titulo'], 40)) . '<br/>' . mmd($x['rotulo']) . '"]';
        $classes[$x['classe']][] = $id;
    }
    // Qn aberta no 00 = hexágono vermelho; Qn citada que não está no 00 = hexágono de borda tracejada
    ksort($Q['qAbertas']);
    ksort($Q['qFora']);
    $qs = [];
    foreach ($Q['qAbertas'] as $q => $nums) $qs[$q] = [$nums, "$q aberta", 'pergunta'];
    foreach ($Q['qFora'] as $q => $nums) $qs[$q] = [$nums, "$q não está no 00", 'invalido'];
    foreach ($qs as $q => [$nums, $rot, $cl]) {
        $g[] = '  ' . strtolower($q) . '{{"' . mmd($rot) . '"}}';
        $classes[$cl][] = strtolower($q);
    }
    foreach ($Q['arestas'] as [$de, $para]) $g[] = "  t$de --> t$para";
    foreach ($qs as $q => [$nums]) foreach ($nums as $n) if ($n !== null) $g[] = '  ' . strtolower($q) . " -.-> t$n";
    $leg = 'ao solicitante aberta (seta pontilhada).';
    if (isset($classes['invalido'])) $leg = 'ao solicitante aberta (seta pontilhada) · borda vermelha tracejada status fora do enum ou pergunta que não está no 00.';
    array_push($md, '', '## Dependências', '',
        'Seta = "bloqueia". Cor e segunda linha de cada nó = status: verde concluído · roxo em revisão ·',
        'âmbar em execução · azul pronto na fronteira · cinza pronto aguardando bloqueio · vermelho pergunta',
        $leg, '', '```mermaid', 'flowchart LR');
    $md = array_merge($md, $g);
    $cores = [
        'concluido' => 'fill:#1a7f37,stroke:#116329,color:#ffffff',
        'revisao' => 'fill:#8250df,stroke:#6639ba,color:#ffffff',
        'execucao' => 'fill:#9a6700,stroke:#7d4e00,color:#ffffff',
        'pronto' => 'fill:#0969da,stroke:#0550ae,color:#ffffff',
        'aguarda' => 'fill:#6e7781,stroke:#57606a,color:#ffffff',
        'pergunta' => 'fill:#cf222e,stroke:#a40e26,color:#ffffff',
        'invalido' => 'fill:#ffffff,stroke:#cf222e,color:#cf222e,stroke-dasharray:4 2',
    ];
    foreach ($cores as $c => $estilo) {
        if (!isset($classes[$c])) continue;
        $md[] = "  classDef $c $estilo";
        $md[] = '  class ' . implode(',', $classes[$c]) . " $c";
    }
    $md[] = '```';
    return implode("\n", $md) . "\n";
}

function grava($destino, $conteudo) {
    if (is_file($destino) && file_get_contents($destino) === $conteudo) return;
    if (file_put_contents($destino, $conteudo) === false) {
        fwrite(STDERR, "indice.sh: não consegui gravar $destino\n");
        exit(2);
    }
}

// ---------------------------------------------------------------- modo --status
if ($modo === 'status') {
    $feature = basename($wiki);
    $Q = quadro($wiki);
    if ($Q === null) {
        $tot = 0;
        $feitos = 0;
        foreach (semCerca(ler("$wiki/03-progresso.md")) ?? [] as $l) {
            if (preg_match('/^\s*[-*]\s+\[([ xX])\]/', $l, $m)) { $tot++; if ($m[1] !== ' ') $feitos++; }
        }
        echo "$feature · não fatiada (sem tickets em 07-tickets/) · 03: $feitos/$tot itens\n";
        exit(0);
    }
    $res = ["$feature · {$Q['concl']}/{$Q['total']} concluídos"];
    foreach (['em revisão', 'em execução'] as $s) if ($l = listaDe($Q, $s)) $res[] = "$s: " . implode(', ', $l);
    $res[] = 'fronteira: ' . frTexto($Q);
    echo implode(' · ', $res), "\n";
    echo barra($Q['concl'], $Q['total'], 20, '#', '-'), '  ', contTexto($Q), "\n";
    echo 'CT verdes (inferidos do Status): ', ctTexto($Q), "\n\n";
    $cab = ['NN', 'STATUS', 'CT', 'BLOQUEADO POR', 'ENTREGA'];
    $ls = [];
    foreach ($Q['tks'] as $x) $ls[] = [$x['num'], $x['status'], $x['cv'], $x['bloq'], corta($x['titulo'], 60)];
    $w = [];
    foreach (array_merge([$cab], $ls) as $l) foreach ($l as $k => $v) $w[$k] = max($w[$k] ?? 0, larg($v));
    foreach (array_merge([$cab], $ls) as $l) {
        $out = [];
        foreach ($l as $k => $v) $out[] = $k === count($l) - 1 ? $v : pad($v, $w[$k]);
        echo rtrim(implode('  ', $out)), "\n";
    }
    echo "\nlimite: ", LIMITE, "\n";
    exit(0);
}

// ---------------------------------------------------------------- modo --check
if ($modo === 'check') {
    $A = [];
    $achado = function ($arq, $linha, $msg) use (&$A) { $A[] = [$arq, (int) $linha, $msg]; };
    $f00 = "$wiki/00-requisito.md";
    $f01 = "$wiki/01-plano-acao.md";
    $f03 = "$wiki/03-progresso.md";
    $f04 = "$wiki/04-casos-de-teste.md";
    $f05 = "$wiki/05-casos-de-teste-browser.md";
    $d00 = ler00($f00);
    $cob = ler01($f01);
    $d04 = ler04($f04);
    $ctb = ler05($f05);
    $tks = lerTickets("$wiki/07-tickets");
    if (!$tks) { fwrite(STDERR, "indice.sh: $wiki/07-tickets/ não tem nenhum ticket (o README.md é o quadro gerado, não conta)\n"); exit(2); }
    if (!$d00['rq']) { fwrite(STDERR, "indice.sh: $f00 não tem RQ-nn em tabela — nada a alocar\n"); exit(2); }
    $porNum = porNumero($tks);

    // 2. forma
    $vistos = [];
    foreach ($tks as $t) {
        $a = $t['arq'];
        if ($t['num'] === null) $achado($a, 1, 'nome fora do padrão NN-slug.md (dois dígitos e slug em kebab-case)');
        elseif (isset($vistos[$t['num']])) $achado($a, 1, "número {$t['num']} repetido — também em {$vistos[$t['num']]}");
        else $vistos[$t['num']] = $a;
        foreach (CAMPOS as $c) if (!isset($t['campos'][$c])) $achado($a, 1, "campo fixo ausente: **$c**");
        if ($t['titulo'] === null) $achado($a, 1, 'título "# NN: {entrega}" ausente');
        elseif ($t['num'] !== null && $t['titulo'][1] !== $t['num']) $achado($a, $t['titulo'][0], "o título diz {$t['titulo'][1]}, o arquivo é {$t['num']}");
        if (isset($t['campos']['Status'])) {
            [$l, $v] = $t['campos']['Status'];
            if ($t['status'] === null) $achado($a, $l, "Status fora do enum (pronto | em execução | em revisão | concluído): \"$v\"");
            elseif (in_array($t['status'], ['em revisão', 'concluído'], true) && !preg_match('/^\s*—\s*\S/u', $t['resto'])) {
                $achado($a, $l, "\"{$t['status']}\" sem \" — {data}, {evidência}\"");
            }
        }
        if (isset($t['campos']['Issue'])) {
            [$l, $v] = $t['campos']['Issue'];
            if (!preg_match(ISSUE_RE, limpa($v))) $achado($a, $l, "**Issue** fora do formato (#N, dono/repo#N ou URL da issue): \"$v\" — o campo é gravado pelo espelho-gh.sh");
        }
    }

    // 1. alocação
    $onde = [];
    foreach ($tks as $t) foreach ($t['ids'] as $id => $l) $onde[$id][] = [$t['arq'], $l, $t['num'] ?? basename($t['arq'], '.md')];
    $fora = 'está fora desta entrega no 01 (## Cobertura do Requisito sem passo, com justificativa) — não entra em ticket';
    $univ = [];
    foreach ($d00['rq'] as $id => $x) {
        if ($x['sub']) $mot = 'está substituída no 00 — o ticket cobre a RQ substituta';
        elseif ($x['dec'] !== null) $mot = 'está decomposta no 00 (em ' . implode(', ', $x['dec']) . ') — o ticket cobre as filhas';
        // fora desta entrega sai de toda cobrança, aberta ou não (C13.9; o mesmo do rastreabilidade.sh)
        elseif (!empty($cob[$id]['fora'])) $mot = $fora;
        else $mot = null;
        $univ[$id] = [$f00, $x['linha'], $mot];
    }
    foreach ($d00['p'] as $id => $x) {
        if (!$x['viva']) $mot = 'não está vigente no 00 (substituída, promovida ou revogada)';
        elseif (!empty($cob[$id]['fora'])) $mot = $fora;
        else $mot = null;
        $univ[$id] = [$f00, $x['linha'], $mot];
    }
    foreach ($d04['ct'] as $id => $x) $univ[$id] = [$f04, $x['linha'], $x['obs'] ? 'está riscado, fundido ou @obsoleto no 04' : null];
    foreach ($ctb as $id => $x) $univ[$id] = [$f05, $x['linha'], $x['obs'] ? 'está riscado ou @obsoleto no 05' : null];
    foreach ($univ as $id => [$arq, $l, $mot]) {
        $locais = $onde[$id] ?? [];
        if ($mot !== null) { foreach ($locais as [$ta, $tl]) $achado($ta, $tl, "$id $mot"); continue; }
        if (strpos($id, 'CT-') !== 0) {
            $nums = array_values(array_unique(array_column($locais, 2)));
            if (!$nums) $achado($arq, $l, "$id sem ticket em 07-tickets/");
            elseif (count($nums) > 1) $achado($arq, $l, "$id em mais de um ticket: " . implode(', ', $nums));
            continue;
        }
        if (!$locais) $achado($arq, $l, "$id sem ticket");
        foreach (array_slice($locais, 1) as [$ta, $tl]) $achado($ta, $tl, "$id também está em {$locais[0][0]}:{$locais[0][1]} — cada ID pertence a exatamente um ticket");
    }
    foreach ($onde as $id => $locais) {
        if (isset($univ[$id])) continue;
        $fonte = strpos($id, 'CT-B') === 0 ? '05' : (strpos($id, 'CT-') === 0 ? '04' : '00');
        foreach ($locais as [$ta, $tl]) $achado($ta, $tl, "$id não existe no $fonte");
    }

    // 3. arestas
    // RQ na cobrança com "aberta — Qn" e Qn respondida, retirada ou ausente: o Estado da RQ no 00 está
    // velho. Um achado na linha da RQ; o ticket não é acusado por esse par (nenhum estado dele passaria).
    $velha = [];
    foreach ($d00['rq'] as $id => $x) {
        if ($x['q'] === null || $univ[$id][2] !== null) continue;
        $q = $x['q'];
        if (isset($d00['q'][$q]) && $d00['q'][$q]['aberta']) continue;
        $velha[$id] = $q;
        $achado($f00, $x['linha'], "$id está \"aberta — $q\", mas $q " . (isset($d00['q'][$q])
            ? 'está respondida ou retirada no 00 — atualize o Estado da RQ (fechada, substituída por RQ-nn (Adendo N) ou decomposta em RQ-nn, …)'
            : 'não existe em ## Perguntas ao Solicitante do 00 — corrija o Qn do Estado ou registre a pergunta')
            . '; o ticket segue o 00 corrigido');
    }
    foreach ($tks as $t) {
        $a = $t['arq'];
        $lb = $t['campos']['Bloqueado por'][0] ?? 1;
        foreach ($t['bloq'] as $b) {
            if (!isset($porNum[$b])) $achado($a, $lb, "bloqueado por $b, que não existe em 07-tickets/");
            elseif ($t['num'] !== null && strcmp($b, $t['num']) >= 0) $achado($a, $lb, "bloqueado por $b, de número igual ou maior — a numeração segue a ordem de dependência");
        }
        // Qn que é o par de uma RQ de Estado velho neste ticket: a causa já foi acusada no 00
        $parVelho = [];
        foreach ($t['ids'] as $id => $l) if (isset($velha[$id])) $parVelho[$velha[$id]] = true;
        foreach ($t['bloqQ'] as $q) {
            if (isset($parVelho[$q])) continue;
            if (!isset($d00['q'][$q])) $achado($a, $lb, "$q não existe em ## Perguntas ao Solicitante do 00");
            elseif (!$d00['q'][$q]['aberta']) $achado($a, $lb, "$q não está aberta no 00 (respondida ou retirada) — sai de **Bloqueado por**, e a resposta entra neste ticket (regra 8)");
        }
        foreach ($t['ids'] as $id => $l) {
            $q = $d00['rq'][$id]['q'] ?? null;
            // fora da cobrança (substituída, decomposta, fora desta entrega) já é achado na alocação
            if ($q === null || isset($velha[$id]) || ($univ[$id][2] ?? null) !== null) continue;
            if (!in_array($q, $t['bloqQ'], true)) $achado($a, $l, "$id está aberta ($q) e **Bloqueado por** não cita $q");
        }
        if (in_array($t['status'], ['em execução', 'em revisão', 'concluído'], true)) {
            $pend = [];
            foreach ($t['bloq'] as $b) if (isset($porNum[$b]) && $tks[$porNum[$b]]['status'] !== 'concluído') $pend[] = $b;
            foreach ($t['bloqQ'] as $q) if (!isset($parVelho[$q]) && ($d00['q'][$q]['aberta'] ?? true)) $pend[] = "$q (aberta)";
            if ($pend) $achado($a, $t['campos']['Status'][0], "\"{$t['status']}\" com bloqueio pendente: " . implode(', ', $pend) . ' — só ticket da fronteira é despachado');
        }
    }

    // 4. fatia vertical e costura
    if ($cob === null) $achado($f01, 1, 'sem tabela ## Cobertura do Requisito (colunas RQ e Passo…) — fatia vertical não conferida');
    if (!$d04['indice']) $achado($f04, 1, 'sem Índice de Cenários com coluna Regra — origem dos CT não conferida');
    elseif (!$d04['temMapa']) $achado($f04, 1, 'sem Mapa de Regras com coluna Origem — origem dos CT não conferida');
    if ($d04['indice']) {
        foreach ($d04['ct'] as $id => $x) if ($x['regras'] === null && !$x['obs']) $achado($f04, $x['linha'], "$id está no Gherkin e fora do ## Índice de Cenários — origem não conferida");
        if (!$d04['colCostura']) {
            $achado($f04, $d04['linhaIndice'], $d04['colCamada']
                ? '## Índice de Cenários com a coluna Camada — o nome é Costura desde a feature-test-design 1.16.0; **Costura** dos tickets não conferida'
                : '## Índice de Cenários sem a coluna Costura — **Costura** dos tickets não conferida');
        }
    }
    foreach ($tks as $k => $t) {
        $a = $t['arq'];
        $ids = array_keys($t['ids']);
        $antesIds = [];
        $antesPassos = [];
        foreach (antes($tks, $porNum, $k) as $kb) {
            $antesIds = array_merge($antesIds, array_keys($tks[$kb]['ids']));
            // contract: a cláusula só fica verdadeira no fim da sequência; os passos do expand e dos migrate contam
            if ($t['tipo'] === 'contract' && in_array($tks[$kb]['tipo'], ['expand', 'migrate'], true)) $antesPassos = array_merge($antesPassos, $tks[$kb]['passos']);
        }
        if ($cob !== null) {
            foreach ($ids as $id) {
                if (!preg_match('/^(?:RQ|P)-/', $id) || !isset($cob[$id])) continue;
                $falta = array_diff($cob[$id]['passos'], $t['passos'], $antesPassos);
                if ($falta) $achado($a, $t['ids'][$id], "$id exige o(s) passo(s) " . implode(', ', $falta) . ' (Cobertura do Requisito do 01), fora de **Passos do 01 envolvidos** — a fatia não fecha vertical');
            }
        }
        // Costura: o grupo (ou a costura) de cada CT do ticket, pelo Índice de Cenários. O campo são
        // itens "{Grupo} — {costura}" separados por ; ou ,; cada nome é comparado inteiro (sem caixa),
        // com e sem o comentário entre parênteses — "Pagamento na tela" não cita "Pagamento".
        if ($d04['colCostura'] && isset($t['campos']['Costura'])) {
            $nomes = [];
            $v = limpa($t['campos']['Costura'][1]);
            foreach ([$v, preg_replace('/\([^)]*\)/u', '', $v)] as $forma) {
                foreach (preg_split('/[;,]/u', $forma) as $item) {
                    foreach (array_merge([$item], preg_split('/\s+[—–-]\s+/u', $item)) as $parte) {
                        $parte = minusc(trim($parte));
                        if ($parte !== '') $nomes[$parte] = true;
                    }
                }
            }
            $faltam = [];
            foreach ($ids as $id) {
                $x = $d04['ct'][$id] ?? null;
                if ($x === null || $x['obs'] || $x['regras'] === null) continue;
                $alvo = ($d04['colGrupo'] && $x['grupo'] !== '' && $x['grupo'] !== '—') ? $x['grupo'] : $x['costura'];
                if ($alvo === '' || $alvo === '—') continue;
                if (!isset($nomes[minusc(trim($alvo))])) $faltam[$alvo][] = $id;
            }
            foreach ($faltam as $alvo => $cts) {
                $oque = $d04['colGrupo'] ? "o grupo \"$alvo\"" : "a costura \"$alvo\"";
                $achado($a, $t['campos']['Costura'][0], "**Costura** não cita $oque de " . implode(', ', $cts) . ' (## Índice de Cenários do 04) — o executor não sabe onde o teste se prende');
            }
        }
        // expand e migrate são a exceção declarada à fatia vertical: o CT deles prova a transição
        if (!$d04['temMapa'] || in_array($t['tipo'], ['expand', 'migrate'], true)) continue;
        foreach ($ids as $id) {
            if (!preg_match('/^CT-\d+$/', $id) || !isset($d04['ct'][$id]) || $d04['ct'][$id]['obs'] || $d04['ct'][$id]['regras'] === null) continue;
            $orig = [];
            foreach ($d04['ct'][$id]['regras'] as $rg) {
                if (!isset($d04['mapa'][$rg])) {
                    $achado($f04, $d04['ct'][$id]['linha'], "$id: regra $rg sem linha no Mapa de Regras — origem não conferida");
                    continue;
                }
                $orig = array_merge($orig, $d04['mapa'][$rg]['origens']);
            }
            $orig = array_values(array_unique($orig));
            if (!$orig) continue;
            $foraT = array_diff($orig, $ids, $antesIds);
            if ($foraT) {
                $achado($a, $t['ids'][$id], "$id depende de " . implode(', ', $foraT) . ' (Origem no 04), fora deste ticket e dos que o bloqueiam — o CT não fica verde aqui');
            } elseif (!array_intersect($orig, $ids)) {
                $achado($a, $t['ids'][$id], "$id não tem origem neste ticket (origem: " . implode(', ', $orig) . ', nos tickets que o bloqueiam) — fica verde antes dele; pertence ao último ticket que completa as origens');
            }
        }
    }

    // 5. tipos
    $expand = [];
    $migrate = [];
    $contract = [];
    foreach ($tks as $k => $t) {
        $a = $t['arq'];
        $temRQ = (bool) preg_grep('/^(?:RQ|P)-/', array_keys($t['ids']));
        $temCT = (bool) preg_grep('/^CT-/', array_keys($t['ids']));
        $lrq = $t['campos']['RQ cobertas'][0] ?? 1;
        $lct = $t['campos']['CT que ficam verdes'][0] ?? 1;
        if ($t['tipo'] === 'prefactor') {
            if ($t['num'] !== '00') $achado($a, 1, 'prefactoring é o ticket 00 (00-prefactor-{slug}.md)');
            if ($temRQ) $achado($a, $lrq, 'prefactoring com RQ/P-nn — prefactoring não muda comportamento');
            if ($temCT) $achado($a, $lct, 'prefactoring com CT — fecha com a suíte existente verde, sem CT novo');
        } else {
            if (!$temRQ && !in_array($t['tipo'], ['expand', 'migrate'], true)) $achado($a, $lrq, 'ticket sem RQ nem P-nn — só prefactoring, expand e migrate não entregam cláusula');
            // RQ aberta não tem cenário até a resposta (o 04 a lista sem CT): ticket só com RQ abertas fica sem CT por ora.
            $soAbertas = $temRQ;
            foreach (array_keys($t['ids']) as $id) {
                if (preg_match('/^(?:RQ|P)-/', $id) && ($d00['rq'][$id]['q'] ?? null) === null) $soAbertas = false;
            }
            if (!$temCT && !$soAbertas) $achado($a, $lct, 'ticket sem CT — sem critério de aceite falsificável');
        }
        if ($t['tipo'] === 'expand' && $t['num'] !== null) $expand[] = $t['num'];
        if ($t['tipo'] === 'migrate') $migrate[] = $k;
        if ($t['tipo'] === 'contract') $contract[] = $k;
    }
    if ($migrate || $contract) {
        $primeiro = $tks[$migrate ? $migrate[0] : $contract[0]]['arq'];
        if (count($expand) !== 1) $achado($primeiro, 1, 'expand–contract exige exatamente um ticket NN-expand-{slug}.md; há ' . count($expand));
        foreach ($migrate as $k) {
            if ($expand && !in_array($expand[0], $tks[$k]['bloq'], true)) $achado($tks[$k]['arq'], $tks[$k]['campos']['Bloqueado por'][0] ?? 1, "migrate sem o expand ({$expand[0]}) em **Bloqueado por**");
        }
        foreach ($contract as $k) {
            $nums = array_map(function ($kb) use ($tks) { return $tks[$kb]['num']; }, antes($tks, $porNum, $k));
            foreach ($migrate as $km) {
                if (!in_array($tks[$km]['num'], $nums, true)) $achado($tks[$k]['arq'], $tks[$k]['campos']['Bloqueado por'][0] ?? 1, "contract não depende do migrate {$tks[$km]['num']} — o contract vem depois de todos os lotes");
            }
        }
    }

    // 6. espelho no 03
    $l03 = semCerca(ler($f03));
    if ($l03 === null) {
        $achado($f03, 1, 'sem 03-progresso.md — ## Tickets não conferida');
    } else {
        $ini = null;
        $linhas = [];
        $confirmado = false;
        foreach ($l03 as $i => $l) {
            if ($ini === null) {
                if (preg_match('/^##\s+Tickets\s*$/u', $l)) $ini = $i;
                continue;
            }
            if (preg_match('/^##\s/', $l)) break;
            if (preg_match('/^\s*Fatiamento confirmado\s*[:—–-]/u', $l)) $confirmado = true;
            if (preg_match('/^\s*Não fatiado\b/u', $l)) $achado($f03, $i + 1, '## Tickets ainda diz "Não fatiado — …" e a feature tem 07-tickets/ — a linha vira "Fatiamento confirmado: {data} — {quem} — {rodadas}, {perguntas}"');
            if (preg_match('/^\s*[-*]\s+\[([ xX])\]\s+\[(\d{2})[^\]]*\]\([^)]*\)(.*)$/u', $l, $m)) $linhas[$m[2]] = [$i + 1, $m[1] !== ' ', $m[3]];
        }
        if ($ini === null) {
            $achado($f03, 1, 'sem ## Tickets — uma linha por ticket, espelhando o Status');
        } else {
            if (!$confirmado) $achado($f03, $ini + 1, '## Tickets sem a linha "Fatiamento confirmado: {data} — {quem} — {rodadas}, {perguntas}" — o quiz aprovado não está registrado');
            foreach ($tks as $t) {
                if ($t['num'] === null || $t['status'] === null) continue;
                if (!isset($linhas[$t['num']])) { $achado($f03, $ini + 1, "## Tickets sem a linha do ticket {$t['num']}"); continue; }
                [$l, $x, $resto] = $linhas[$t['num']];
                // formato: - [ ] [NN](07-tickets/NN-slug.md) {entrega} — {status}[ — {data}, {evidência}]
                $seg = preg_split('/\s+—\s+/u', $resto);
                $st = preg_match('/^(pronto|em execução|em revisão|concluído)/u', trim($seg[1] ?? ''), $m) ? $m[1] : '?';
                // a sessão nova marca só o ticket e não abre o 03 até o fechamento
                $sessaoNova = $st === 'pronto' && $t['status'] === 'em execução' && preg_match('/sess[ãa]o nova/u', $t['resto']);
                if ($st !== $t['status'] && !$sessaoNova) $achado($f03, $l, "ticket {$t['num']}: o 03 diz \"$st\", o ticket diz \"{$t['status']}\" — o arquivo do ticket é a fonte");
                elseif ($x !== ($t['status'] === 'concluído')) $achado($f03, $l, "ticket {$t['num']}: [x] só quando o ticket está concluído");
            }
            foreach ($linhas as $num => $v) if (!isset($porNum[$num])) $achado($f03, $v[0], "## Tickets cita o ticket $num, que não existe em 07-tickets/");
        }
    }

    usort($A, function ($x, $y) { return [$x[0], $x[1], $x[2]] <=> [$y[0], $y[1], $y[2]]; });
    $impressos = [];
    foreach ($A as [$arq, $l, $msg]) {
        $s = "$arq:$l: $msg";
        if (isset($impressos[$s])) continue;
        $impressos[$s] = true;
        echo $s, "\n";
    }
    exit($A ? 1 : 0);
}

// ---------------------------------------------------------------- modo gera
$wikis = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('wikis/specs', FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if ($f->getFilename() === '03-progresso.md') $wikis[] = str_replace('\\', '/', $f->getPath());
}
sort($wikis, SORT_STRING);

$esc = function ($s) { return str_replace('|', '\|', $s); };
$md = [
    '# Quadro das features',
    '',
    '> **Gerado** por `scripts/indice.sh` da skill `feature-tickets`, a partir do `03-progresso.md`, do',
    '> `06-relatorio-qa.md` e dos `07-tickets/` de cada wiki. **Não editar à mão**: rodar o script de novo —',
    '> inclusive para resolver conflito de merge neste arquivo.',
    '>',
    '> **03**: itens marcados / itens com checkbox. **Progresso**: tickets concluídos / tickets, quando a',
    '> feature foi fatiada; senão, itens marcados do `03`. **Fronteira**: tickets `pronto` sem bloqueio',
    '> pendente. **Quadro**: `07-tickets/README.md` da feature, com o grafo de dependências.',
    '',
];
if (!$wikis) {
    $md[] = 'Nenhuma wiki com `03-progresso.md` em `wikis/specs/`.';
} else {
    $md[] = '| Branch | Feature | 03 | Tickets | Progresso | Fronteira | Veredito do 06 | Wiki |';
    $md[] = '|---|---|---|---|---|---|---|---|';
}
$gravados = [];
foreach ($wikis as $w) {
    $rel = substr($w, strlen('wikis/specs/'));
    $feature = basename($rel);
    $branch = dirname($rel);
    if ($branch === '.' || $branch === '') $branch = '—';

    $l03 = semCerca(ler("$w/03-progresso.md")) ?? [];
    $tot = 0;
    $feitos = 0;
    $estado = null;
    foreach ($l03 as $l) {
        if (preg_match('/^\s*[-*]\s+\[([ xX])\]/', $l, $m)) { $tot++; if ($m[1] !== ' ') $feitos++; }
        if ($estado === null && preg_match('/^\s*(?:>\s*)?(?:[-*]\s+)?\*\*Estado\*\*\s*:\s*(.+)$/u', $l, $m)) $estado = trim(str_replace('*', '', $m[1]));
    }
    // placeholder do template ({…} ou o enum copiado com |) não é estado
    $c03 = "$feitos/$tot itens" . ($estado !== null && strpos($estado, '{') === false && strpos($estado, '|') === false ? " · $estado" : '');

    $Q = quadro($w);
    $links = "[03]($rel/03-progresso.md)";
    if ($Q === null) {
        $cTk = 'não fatiada';
        $cFr = '—';
        $cPr = $tot ? '`' . barra($feitos, $tot, 10, '█', '░') . '` do 03' : '—';
        $velho = "$w/07-tickets/README.md";
        // quadro gerado de uma feature que ficou sem tickets: sai junto
        if (is_file($velho) && strpos((string) file_get_contents($velho), '# Quadro dos tickets') === 0) unlink($velho);
    } else {
        $cTk = $Q['total'] . ': ' . contTexto($Q);
        $cFr = frTexto($Q);
        $cPr = '`' . barra($Q['concl'], $Q['total'], 10, '█', '░') . "` {$Q['concl']}/{$Q['total']} tickets";
        $links .= " · [quadro]($rel/07-tickets/README.md)";
        grava("$w/07-tickets/README.md", readme($w, $rel, $Q));
        $gravados[] = "$w/07-tickets/README.md";
    }

    $ver = '—';
    $l06 = ler("$w/06-relatorio-qa.md");
    if ($l06 !== null) {
        $ult = null;
        foreach ($l06 as $i => $l) if (preg_match('/^##\s+Veredito\b/u', $l)) $ult = $i;
        if ($ult !== null) {
            $ciclo = preg_match('/Ciclo\s+(\d+)/u', $l06[$ult], $m) ? $m[1] : null;
            for ($i = $ult + 1, $n = count($l06); $i < $n; $i++) {
                $s = trim($l06[$i]);
                if ($s === '') continue;
                if (preg_match('/^\*\*(.+?)\*\*$/u', $s, $m) && strpos($m[1], '{') === false) $ver = trim($m[1]) . ($ciclo !== null ? " (ciclo $ciclo)" : '');
                break;
            }
        }
    }
    if ($ver === '—') {
        foreach ($l03 as $l) {
            if (preg_match('/\*\*Veredito\*\*\s*:\s*([^·]+)/u', $l, $m) && strpos($m[1], '{') === false) { $ver = trim($m[1]) . ' (no 03; sem 06)'; break; }
        }
    }

    $md[] = '| ' . implode(' | ', array_map($esc, ["`$branch`", "`$feature`", $c03, $cTk, $cPr, $cFr, $ver, $links])) . ' |';
}

$destino = 'wikis/specs/INDEX.md';
grava($destino, implode("\n", $md) . "\n");
foreach ($gravados as $g) echo $g, "\n";
echo $destino, "\n";
exit(0);
PHP

# O PHP vai por arquivo temporário, não por `php -r`: no Windows a linha de comando tem teto de 32.767
# caracteres, e este bloco passa disso.
t=$(mktemp "${TMPDIR:-/tmp}/indice.XXXXXX") || { echo "indice.sh: mktemp falhou" >&2; exit 2; }
trap 'rm -f "$t"' EXIT
printf '<?php\n%s\n' "$PHP_CODE" > "$t" || { echo "indice.sh: não consegui gravar $t" >&2; exit 2; }
tp=$t
if command -v cygpath >/dev/null 2>&1; then tp=$(cygpath -m "$t"); fi
php "$tp" "$modo" "$wiki" </dev/null
st=$?
[ "$st" -le 2 ] || { echo "indice.sh: php saiu com $st" >&2; exit 2; }
exit "$st"

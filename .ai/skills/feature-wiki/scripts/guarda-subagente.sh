#!/usr/bin/env bash
# guarda-subagente.sh — hook PreToolUse dos cinco agentes fw-*: cegueira e não-edição por construção.
#
# Uso: no frontmatter do agente (bloco idêntico nos cinco, só o perfil muda), nunca à mão:
#   bash {skills}/feature-wiki/scripts/guarda-subagente.sh <perfil>   < JSON do PreToolUse no stdin
#   <perfil> = revisor-diff | adversario-ct | qa-gate | executor-ct | executor-ctb
#
# O que confere: lê o JSON do PreToolUse (tool_name; tool_input.file_path, notebook_path, path,
#   pattern, glob, type, command; cwd), normaliza \ para /, resolve . e .. e path relativo contra o cwd,
#   e decide pela tabela do perfil:
#   revisor-diff   Read/Grep em wikis/specs/**/01-* e 03-*; Bash que cita esses arquivos, glob em wikis/,
#                  busca recursiva que alcança wikis/specs, cd para wikis/; git diff/show (e log -p) sem
#                  ':(exclude)wikis' ou ':!wikis', git archive e git grep que alcançam wikis/; Bash que altera
#                  a árvore (abaixo); Edit/Write
#   adversario-ct  Read fora de wikis/specs/**/00-requisito.md, 04-casos-de-teste.md,
#                  05-casos-de-teste-browser.md, wikis/glossario.md e .ai/skills/, .claude/skills/,
#                  ~/.claude/skills/; Grep cujo alvo não é um desses arquivos (ou pasta de skills);
#                  Bash, Edit e Write. Glob é permitido (devolve só nomes)
#   qa-gate        Bash que altera a árvore; Edit/Write. Lê a wiki inteira por desenho
#   executor-ct    Edit/Write/MultiEdit em app/, database/migrations/, wikis/specs/**/00-*, 03-*, 04-*, 05-*
#                  e wikis/specs/**/07-tickets/** (o 03 e o Status do ticket são da sessão); Read/Grep de
#                  wikis/specs/**/01-* e 02-*; no Bash, a mesma leitura (como o revisor, com 01/02), escrita
#                  nesses caminhos, pint que formata fora dos testes, git que altera a árvore e os dois
#                  scripts da feature-tickets que gravam (abaixo). Ler o ticket (07-tickets/NN-*.md) é
#                  permitido: não é o arquivo NN da wiki
#   executor-ctb   Edit/Write/MultiEdit nos mesmos caminhos; no Bash, escrita neles, o mesmo pint, git que
#                  altera a árvore e os dois scripts da feature-tickets que gravam
#   "Altera a árvore": git add/commit/stash/checkout/switch/reset/restore/clean/rebase/merge/push/pull/rm/
#   mv/apply/am/cherry-pick/revert; rm, rmdir, mv, cp, tee, touch, mkdir, truncate, ln, unlink, install,
#   patch, shred, chmod, chown, dd of=, sed -i, perl -i, awk -i inplace, find -delete, pint sem --test;
#   indice.sh sem --check nem --status (grava wikis/specs/INDEX.md e os 07-tickets/README.md) e
#   espelho-gh.sh --aplicar (grava **Issue** nos tickets e cria issues), da feature-tickets, chamados
#   por bash/sh, source ou direto; redirecionamento > ou >> para dentro do repositório (/dev/*, $TMPDIR,
#   /tmp e fora do repositório passam). Entra também o que vem por find -exec, xargs, bash -c (e -lc,
#   -ec…), sh -c, eval e cmd /c.
#   pint nos executores (vendor/bin/pint, php vendor/bin/pint, sail pint): sem path, com --dirty ou --diff
#   (formatam todo arquivo sujo ou alterado, app/ incluído) ou com path em app/, database/migrations/, na
#   raiz ou na wiki é escrita proibida; a forma permitida é vendor/bin/pint {arquivos de teste do lote}.
#   pint --test (sem --repair) só confere e passa em todo perfil que usa Bash.
#   Grep (a ferramenta) e rg -g seguem a semântica do rg: o glob é lista (espaço e vírgula separam), o
#   último glob que casa decide, glob só negado ('!vendor/**', '!*.php') casa todo o resto e o glob vence
#   o type ('-t php -g *.md' devolve .md). Só isenta a negação que exclui wikis/ inteira ('!wikis',
#   '!wikis/**'); a que exclui parte dos números proibidos ('!**/01-*') deixa o outro alcançável.
#   Arquivo numerado de wikis/specs é o que está direto na pasta da feature: 07-tickets/01-*.md não é o 01;
#   a pasta da feature pode começar com dois dígitos (10-anos-garantia/01-plano-acao.md é o 01). Busca ou
#   glob restritos a 07-tickets/ (sem ..) não alcançam o 01/02/03 da wiki.
#   No Bash, a palavra também é conferida sem aspas e sem barra invertida ("01"-plano, 0\1-plano), e
#   expansão ($f, ${f}, $(…), $((…)), `…`) num comando que cita wikis/ é negada: o nome final não aparece.
#   app/, database/ e "dentro do repositório" valem contra três raízes: $CLAUDE_PROJECT_DIR, o cwd do
#   JSON e a pasta com `artisan` mais próxima acima do path (monorepo: sessão em /repo, app em /repo/backend).
#   Ferramenta fora de Read|Grep|Glob|Bash|Edit|Write|MultiEdit|NotebookEdit passa (o matcher escolhe) —
#   inclusive ferramenta MCP herdada (tinker e database-query do Boost, Playwright) pelos agentes sem tools:.
# O que NÃO confere (Bash é heurístico, por padrão de comando): escrita por interpretador (php -r,
#   python -c, node -e), PowerShell, corpo de heredoc, git cat-file por hash, arquivo copiado antes para
#   fora do repositório e lido de lá, nome guardado em variável num comando anterior, formatador chamado
#   por script do composer ou do npm (composer lint, npm run format), ferramenta MCP. A
#   sessão compara o git status --porcelain de antes e de depois nos agentes de julgamento: é sinal, não
#   prova — nova edição num arquivo que já estava modificado não muda o porcelain.
#
# Quem chama: o Claude Code, pelo bloco hooks: do frontmatter de fw-revisor-diff, fw-executor-ct,
#   fw-executor-ctb (feature-wiki), fw-adversario-ct (feature-test-design) e fw-qa-gate
#   (feature-quality-gate). Teste ao vivo: README da feature-wiki, "Teste do hook".
# Contrato: permite = silêncio + exit 0. Nega = exit 2 + motivo no stderr (o Claude Code devolve o
#   motivo ao agente). Falha fechado: perfil ausente ou desconhecido, JSON inválido, stdin vazio, php
#   ausente ou erro interno = exit 2.
# Requer: bash, mktemp e php no PATH (lógica em PHP embutido: o boost:add-skill descarta arquivos .php).
#   Sem jq, node, python ou grep -P. O PHP roda de um arquivo temporário, não de `php -r`: ver o fim do
#   script (teto de 32.767 caracteres da linha de comando no Windows).
#
# Exemplo de falha (saída real, JSON montado à mão, 2026-09-27):
#   $ echo '{"tool_name":"Read","tool_input":{"file_path":"D:\\proj\\wikis\\specs\\b\\f\\01-plano-acao.md"},"cwd":"D:\\proj"}' \
#       | bash guarda-subagente.sh revisor-diff
#   guarda-subagente (revisor-diff): Read negado — wikis/specs/b/f/01-plano-acao.md é o 01 da wiki; o revisor do diff é cego ao plano (01) e ao progresso (03)
#   (exit 2)
#   $ echo '{"tool_name":"Bash","tool_input":{"command":"vendor/bin/pint --dirty"},"cwd":"D:\\proj"}' | bash guarda-subagente.sh executor-ct
#   guarda-subagente (executor-ct): Bash negado — pint --dirty formata todo arquivo sujo ou alterado, app/ incluído: o executor não toca app/, database/migrations/, o 00, o 03, o 04 e o 05 da wiki nem 07-tickets/ (a correção da especificação volta como texto para a sessão; o 03 e o Status do ticket são gravados pela sessão); use vendor/bin/pint {arquivos de teste do lote}
#   (exit 2)
set -u

if [ $# -ne 1 ] || [ -z "${1-}" ]; then
  echo "guarda-subagente.sh: uso: guarda-subagente.sh <revisor-diff|adversario-ct|qa-gate|executor-ct|executor-ctb> (JSON do PreToolUse no stdin) — sem perfil, nega (falha fechado)" >&2
  exit 2
fi
command -v php >/dev/null 2>&1 || { echo "guarda-subagente.sh: php não está no PATH — nega tudo (falha fechado)" >&2; exit 2; }

read -r -d '' PHP_CODE <<'PHP'
ini_set('display_errors', 'stderr');
error_reporting(E_ALL);
set_error_handler(function ($no, $str, $arq, $lin) {
    fwrite(STDERR, "guarda-subagente.sh: erro interno ($str, linha $lin) — nega (falha fechado)\n");
    exit(2);
});

$PERFIS = [
    'revisor-diff'  => ['le' => ['01', '03'], 'bashLe' => true,  'arvore' => 'tudo',     'escreve' => false, 'lista' => false,
        'porque' => 'o revisor do diff é cego ao plano (01) e ao progresso (03)'],
    'adversario-ct' => ['le' => [],           'bashLe' => false, 'arvore' => null,       'escreve' => false, 'lista' => true,
        'porque' => 'o adversário recebe só o 00, o 04/05 e o glossário'],
    'qa-gate'       => ['le' => [],           'bashLe' => false, 'arvore' => 'tudo',     'escreve' => false, 'lista' => false,
        'porque' => 'o quality gate não corrige nada'],
    'executor-ct'   => ['le' => ['01', '02'], 'bashLe' => true,  'arvore' => 'caminhos', 'escreve' => true,  'lista' => false,
        'porque' => 'o executor-ct não lê o plano (01) nem as ADRs (02): o Então vem do 04'],
    'executor-ctb'  => ['le' => [],           'bashLe' => false, 'arvore' => 'caminhos', 'escreve' => true,  'lista' => false,
        'porque' => 'o executor-ctb não toca a aplicação nem a especificação'],
];
$perfil = $argv[1] ?? '';
const ESCRITA = 'o executor não toca app/, database/migrations/, o 00, o 03, o 04 e o 05 da wiki nem 07-tickets/ (a correção da especificação volta como texto para a sessão; o 03 e o Status do ticket são gravados pela sessão)';
function nega($msg) {
    global $perfil;
    fwrite(STDERR, "guarda-subagente ($perfil): $msg\n");
    exit(2);
}
if (!isset($PERFIS[$perfil])) nega("perfil desconhecido — use " . implode(', ', array_keys($PERFIS)) . " (falha fechado)");
$P = $PERFIS[$perfil];

$raw = stream_get_contents(STDIN);
if ($raw === false || trim($raw) === '') nega('stdin vazio — sem o JSON do PreToolUse, nega (falha fechado)');
$J = json_decode($raw, true);
if (!is_array($J)) nega('JSON inválido no stdin — nega (falha fechado)');
$TOOL = $J['tool_name'] ?? null;
if (!is_string($TOOL) || $TOOL === '') nega('JSON sem tool_name — nega (falha fechado)');
$IN = is_array($J['tool_input'] ?? null) ? $J['tool_input'] : [];

// ---------- caminhos
function barra($p) { return str_replace('\\', '/', (string) $p); }
function msys($p) {
    if (PHP_OS_FAMILY === 'Windows' && preg_match('#^/([A-Za-z])(/.*)?$#', $p, $m)) return strtolower($m[1]) . ':' . ($m[2] ?? '/');
    return $p;
}
function absoluto($p) { return (bool) preg_match('#^(/|[A-Za-z]:/|//)#', $p); }
function casas() {
    static $c = null;
    if ($c === null) {
        $c = [];
        foreach (['HOME', 'USERPROFILE'] as $v) {
            $h = getenv($v);
            if (is_string($h) && $h !== '') $c[] = normaliza($h, '/');
        }
    }
    return $c;
}
function normaliza($p, $base) {
    $p = msys(barra($p));
    if ($p === '') $p = '.';
    if ($p[0] === '~' && ($p === '~' || $p[1] === '/')) { $h = casas(); if ($h) $p = $h[0] . substr($p, 1); }
    if (!absoluto($p)) $p = rtrim($base, '/') . '/' . $p;
    $pref = '';
    if (preg_match('#^([A-Za-z]:)/#', $p, $m)) { $pref = strtolower($m[1]); $p = substr($p, 2); }
    elseif (substr($p, 0, 2) === '//') { $pref = '/'; $p = substr($p, 1); }
    $out = [];
    foreach (explode('/', $p) as $s) {
        if ($s === '' || $s === '.') continue;
        if ($s === '..') { array_pop($out); continue; }
        $out[] = $s;
    }
    return $pref . '/' . implode('/', $out);
}
$raizEnv = getenv('CLAUDE_PROJECT_DIR');
$CWD = normaliza(is_string($J['cwd'] ?? null) && $J['cwd'] !== '' ? $J['cwd'] : getcwd(), '/');
$RAIZ = is_string($raizEnv) && $raizEnv !== '' ? normaliza($raizEnv, '/') : $CWD;
function relativo($abs, $base = null) {
    global $RAIZ;
    $base = $base ?? $RAIZ;
    $r = strtolower(rtrim($base, '/')); $a = strtolower($abs);
    if ($a === $r) return '';
    if (strpos($a, $r . '/') === 0) return substr($abs, strlen(rtrim($base, '/')) + 1);
    return null;
}
// raízes candidatas do projeto Laravel para o path: a da sessão, o cwd e a pasta com `artisan`
// mais próxima acima dele (monorepo: sessão em /repo, app em /repo/backend)
function raizes($abs) {
    global $RAIZ, $CWD;
    $r = [$RAIZ, $CWD];
    $d = $abs;
    for ($i = 0; $i < 40; $i++) {
        $p = dirname($d);
        if ($p === $d || $p === '.' || $p === '') break;
        $d = $p;
        if (@is_file("$d/artisan")) { $r[] = $d; break; }
    }
    return array_values(array_unique($r));
}
function mostra($abs) { $r = relativo($abs); return $r === null ? $abs : ($r === '' ? '.' : $r); }
// número do arquivo da wiki ('01', '03'…) quando ele está direto na pasta de uma feature; senão null
function numeroWiki($abs) {
    if (!preg_match('#(?:^|/)wikis/specs/(.+)$#i', $abs, $m)) return null;
    $partes = explode('/', $m[1]);
    $nome = array_pop($partes);
    $pai = $partes ? end($partes) : '';
    // só 07-tickets/ fica de fora: pasta de feature pode começar com dois dígitos (10-anos-garantia)
    if (strcasecmp($pai, '07-tickets') === 0) return null;
    return preg_match('/^(\d\d)-/', $nome, $n) ? $n[1] : null;
}
function emSkills($abs) {
    global $RAIZ, $CWD;
    foreach (array_unique([$RAIZ, $CWD]) as $b) {
        $rel = relativo($abs, $b);
        if ($rel !== null && preg_match('#^\.(ai|claude)/skills(/|$)#i', $rel)) return true;
    }
    foreach (casas() as $h) {
        $s = rtrim($h, '/') . '/.claude/skills';
        if (strcasecmp(rtrim($abs, '/'), $s) === 0 || stripos($abs, $s . '/') === 0) return true;
    }
    return false;
}
function permitidoAdversario($abs) {
    if (numeroWiki($abs) !== null && preg_match('#/(00-requisito|04-casos-de-teste|05-casos-de-teste-browser)\.md$#i', $abs)) return true;
    if (preg_match('#(?:^|/)wikis/glossario\.md$#i', $abs)) return true;
    return emSkills($abs);
}
function pareceDir($abs) {
    if (is_dir($abs)) return true;
    if (is_file($abs)) return false;
    return !preg_match('#\.[A-Za-z0-9]+$#', basename($abs));
}
// o path está em {feature}/07-tickets/ (ou é ela)? Lá só há ticket, nunca o 01/02/03 da wiki. Confere no
// disco que a pasta de cima é mesmo uma feature (tem 00 ou 01): uma branch chamada 07-tickets não conta.
function dentroDeTickets($abs) {
    if (!preg_match('#^(.*/wikis/specs/.+)/07-tickets(?:/|$)#i', rtrim($abs, '/'), $m)) return false;
    return @is_file($m[1] . '/00-requisito.md') || @is_file($m[1] . '/01-plano-acao.md');
}
// o escopo (pasta) contém ou está dentro de wikis/specs?
function alcancaWikis($abs) {
    global $RAIZ, $CWD;
    $a = strtolower(rtrim($abs, '/'));
    if ($a === '') $a = '/';
    if (dentroDeTickets($abs)) return false;
    foreach (array_unique([$RAIZ, $CWD]) as $base) {
        $w = strtolower(rtrim($base, '/') . '/wikis/specs');
        if (strpos($w . '/', rtrim($a, '/') . '/') === 0) return true;
        if (strpos($a . '/', $w . '/') === 0) return true;
    }
    if (@is_dir(rtrim($abs, '/') . '/wikis/specs')) return true;
    return (bool) preg_match('#/wikis(/specs(/|$)|$)#i', $a);
}
function prefixoLiteral($t) { return preg_split('/[*?\[{]/', $t, 2)[0]; }
function temGlob($t) { return strpbrk($t, '*?[{') !== false; }
function expandeChaves($g) {
    if (!preg_match('/^(.*?)\{([^{}]*)\}(.*)$/', $g, $m)) return [$g];
    $out = [];
    foreach (explode(',', $m[2]) as $alt) foreach (expandeChaves($m[1] . $alt . $m[3]) as $x) $out[] = $x;
    return $out;
}
function amostras($nums) {
    $s = [];
    foreach ($nums as $n) foreach (['plano-acao', 'decisoes-arquiteturais', 'progresso', 'x'] as $nome) $s[] = "$n-$nome.md";
    return $s;
}
// o glob casa um nome proibido (ou tudo)?
function globCasa($g, $nums) {
    $g = barra($g);
    $seg = substr($g, strrpos('/' . $g, '/'));
    if ($seg === '' || $seg === '**') return true;
    foreach (amostras($nums) as $a) if (fnmatch($seg, $a, FNM_CASEFOLD)) return true;
    return false;
}
// partes do glob do Grep: a ferramenta quebra a lista em espaço e vírgula fora de chaves
// ('*.php *.md' e '*.php,*.md' são dois globs; '*.{php,md}' também, depois de expandir as chaves)
function partesGlob($g) {
    $partes = []; $cur = ''; $d = 0; $n = strlen($g);
    for ($i = 0; $i < $n; $i++) {
        $c = $g[$i];
        if ($c === '{') $d++;
        elseif ($c === '}' && $d > 0) $d--;
        if ($d === 0 && ($c === ',' || ctype_space($c))) { if ($cur !== '') $partes[] = $cur; $cur = ''; continue; }
        $cur .= $c;
    }
    if ($cur !== '') $partes[] = $cur;
    $out = [];
    foreach ($partes as $p) {
        $neg = $p[0] === '!';
        foreach (expandeChaves($neg ? substr($p, 1) : $p) as $e) $out[] = ($neg ? '!' : '') . $e;
    }
    return $out;
}
// a negação exclui com certeza o arquivo numerado $nome? Na dúvida, não — e o hook nega.
function negacaoCobre($g, $nome) {
    $g = ltrim(barra($g), '/');
    if ($g === '') return false;
    if (preg_match('#^(\*\*/)?wikis(/specs)?(/\*\*|/\*|/)?$#i', $g) || preg_match('#^(\*\*/)?specs/?$|^\*\*/specs(/\*\*|/\*)$#i', $g)) return true;
    if (strpos($g, '/') === false) return fnmatch($g, $nome, FNM_CASEFOLD);   // sem barra: casa o nome em qualquer pasta
    $k = strrpos($g, '/'); $dir = substr($g, 0, $k); $seg = substr($g, $k + 1);
    return (bool) preg_match('#^\*\*(/\*\*)*$#', $dir) && $seg !== '' && fnmatch($seg, $nome, FNM_CASEFOLD);   // **/01-*
}
// a pasta (literal ou com curinga) pode estar acima de wikis/specs, contada de uma das bases?
function pastaAcimaDaWiki($d, $bases) {
    if (temGlob($d)) { $d = prefixoLiteral($d); $k = strrpos($d, '/'); $d = $k === false ? '' : substr($d, 0, $k); }
    foreach ($bases as $b) if (alcancaWikis(normaliza($d === '' ? '.' : $d, $b))) return true;
    return false;
}
// o glob positivo pode casar o arquivo numerado $nome dentro de wikis/specs? Na dúvida, sim.
function positivoPodeCasar($g, $nome, $bases) {
    $g = ltrim(barra($g), '/');
    if ($g === '') return false;
    if (strpos($g, '/') === false) return fnmatch($g, $nome, FNM_CASEFOLD);
    $k = strrpos($g, '/'); $dir = substr($g, 0, $k); $seg = substr($g, $k + 1);
    if ($seg === '' || $seg === '**' || fnmatch($seg, $nome, FNM_CASEFOLD)) return pastaAcimaDaWiki($dir, $bases);
    if (preg_match('#\.[A-Za-z0-9]+$#', $seg)) return false;   // padrão de arquivo com extensão que não casa o nome
    return pastaAcimaDaWiki($g, $bases);                         // o último segmento pode ser uma pasta
}
// Com estes globs e types, algum arquivo numerado proibido entra na busca? Semântica do rg (a do Grep):
// o último glob que casa decide; com algum glob positivo, arquivo que não casa nenhum fica fora; o glob
// vence o type; só com negações (ou sem glob), o que sobra passa pelo type. Por isso '!vendor/**' sozinho
// casa a wiki inteira, e '-t php -g *.md' devolve o .md.
function filtroAlcanca($globs, $tipos, $bases) {
    global $P;
    $positivo = false;
    foreach ($globs as $g) if ($g !== '' && $g[0] !== '!') $positivo = true;
    foreach (amostras($P['le']) as $nome) {
        $entra = null;
        foreach ($globs as $g) {
            if ($g === '' || $g === '!') continue;
            if ($g[0] === '!') { if (negacaoCobre(substr($g, 1), $nome)) $entra = false; }
            elseif (positivoPodeCasar($g, $nome, $bases)) $entra = true;
        }
        if ($entra === null) {
            if ($positivo) $entra = false;
            elseif ($tipos) { $entra = false; foreach ($tipos as $t) if (preg_match('/^(md|markdown|mkd)$/i', trim($t))) $entra = true; }
            else $entra = true;
        }
        if ($entra) return true;
    }
    return false;
}

// ---------- escrita proibida (executores)
function escritaProibida($t) {
    global $CWD;
    $t = barra($t);
    if (temGlob($t)) $t = prefixoLiteral($t);
    $abs = normaliza($t === '' ? '.' : $t, $CWD);
    if (preg_match('#/wikis/specs/.+/07-tickets(/|$)#i', $abs)) return true;   // ticket: Status e campos são da sessão
    $n = numeroWiki($abs);
    if ($n !== null) return in_array($n, ['00', '03', '04', '05'], true);
    foreach (raizes($abs) as $base) {
        $rel = relativo($abs, $base);
        if ($rel === null) continue;
        $r = strtolower($rel);
        if ($r === '' || $r === 'app' || strpos($r, 'app/') === 0) return true;
        if ($r === 'database' || $r === 'database/migrations' || strpos($r, 'database/migrations/') === 0) return true;
        if ($r === 'wikis' || $r === 'wikis/specs') return true;
        if (strpos($r, 'wikis/specs/') === 0 && pareceDir($abs)) return true;
    }
    return false;
}
function destinoInofensivo($t) {
    global $CWD;
    if ($t === '') return false;
    if (preg_match('#^/dev/#', $t) || preg_match('/^nul$/i', $t)) return true;
    if (preg_match('#^(\$\{?(TMPDIR|TEMP|TMP)\b|/tmp/)#', $t)) return true;
    if (strpbrk($t, '$`') !== false) return false;
    $abs = normaliza($t, $CWD);
    foreach (raizes($abs) as $base) if (relativo($abs, $base) !== null) return false;
    return true;
}

// ---------- tokenizador de shell (aproximado): segmentos de comando simples
function lerPalavra($s, $i, &$extras) {
    $n = strlen($s); $w = '';
    while ($i < $n) {
        $c = $s[$i];
        if ($c === '\\') {
            if ($i + 1 < $n && $s[$i + 1] === "\n") { $i += 2; continue; }
            if ($i + 1 < $n) $w .= $s[$i + 1];
            $i += 2; continue;
        }
        if ($c === "'") {
            $j = strpos($s, "'", $i + 1); if ($j === false) $j = $n;
            $w .= substr($s, $i + 1, $j - $i - 1); $i = $j + 1; continue;
        }
        if ($c === '"') {
            $i++;
            while ($i < $n && $s[$i] !== '"') {
                if ($s[$i] === '\\' && $i + 1 < $n && strpos('"\\$`', $s[$i + 1]) !== false) { $w .= $s[$i + 1]; $i += 2; continue; }
                if ($s[$i] === '$' && ($s[$i + 1] ?? '') === '(' && ($s[$i + 2] ?? '') !== '(') {
                    $f = fimParen($s, $i + 1); $extras[] = substr($s, $i + 2, $f - $i - 2); $w .= '$(…)'; $i = $f + 1; continue;
                }
                if ($s[$i] === '`') {
                    $f = strpos($s, '`', $i + 1); if ($f === false) $f = $n;
                    $extras[] = substr($s, $i + 1, $f - $i - 1); $w .= '`…`'; $i = $f + 1; continue;
                }
                $w .= $s[$i]; $i++;
            }
            $i++; continue;
        }
        if ($c === '$' && ($s[$i + 1] ?? '') === '(') {
            if (($s[$i + 2] ?? '') === '(') { $f = fimParen($s, $i + 1); $w .= substr($s, $i, $f - $i + 1); $i = $f + 1; continue; }
            $f = fimParen($s, $i + 1); $extras[] = substr($s, $i + 2, $f - $i - 2); $w .= '$(…)'; $i = $f + 1; continue;
        }
        if ($c === '$' && ($s[$i + 1] ?? '') === '{') {
            $f = strpos($s, '}', $i); if ($f === false) $f = $n - 1;
            $w .= substr($s, $i, $f - $i + 1); $i = $f + 1; continue;
        }
        if ($c === '`') {
            $f = strpos($s, '`', $i + 1); if ($f === false) $f = $n;
            $extras[] = substr($s, $i + 1, $f - $i - 1); $w .= '`…`'; $i = $f + 1; continue;
        }
        if (strpos(" \t\n;|&()<>", $c) !== false) break;
        $w .= $c; $i++;
    }
    return [$w, $i];
}
function fimParen($s, $i) {   // $s[$i] === '(' ; devolve o índice do ')' que fecha
    $n = strlen($s); $d = 0;
    for (; $i < $n; $i++) {
        $c = $s[$i];
        if ($c === "'") { $j = strpos($s, "'", $i + 1); $i = $j === false ? $n : $j; continue; }
        if ($c === '"') { for ($i++; $i < $n && $s[$i] !== '"'; $i++) if ($s[$i] === '\\') $i++; continue; }
        if ($c === '\\') { $i++; continue; }
        if ($c === '(') $d++;
        if ($c === ')') { $d--; if ($d === 0) return $i; }
    }
    return $n - 1;
}
function segmentos($s, $prof = 0) {
    if ($prof > 8) nega('comando aninhado demais para conferir — nega (falha fechado)');
    $segs = []; $cur = ['p' => [], 'r' => [], 'in' => []]; $extras = []; $heredocs = [];
    $n = strlen($s); $i = 0;
    $fecha = function () use (&$segs, &$cur) {
        if ($cur['p'] || $cur['r'] || $cur['in']) $segs[] = $cur;
        $cur = ['p' => [], 'r' => [], 'in' => []];
    };
    while ($i < $n) {
        $c = $s[$i];
        if ($c === ' ' || $c === "\t" || $c === "\r") { $i++; continue; }
        if ($c === "\n") {
            $fecha(); $i++;
            foreach ($heredocs as [$delim, $tira]) {
                while ($i < $n) {
                    $f = strpos($s, "\n", $i); if ($f === false) $f = $n;
                    $linha = rtrim(substr($s, $i, $f - $i), "\r"); $i = $f + 1;
                    if (($tira ? ltrim($linha, "\t") : $linha) === $delim) break;
                }
            }
            $heredocs = []; continue;
        }
        if ($c === '#' ) { $f = strpos($s, "\n", $i); $i = $f === false ? $n : $f; continue; }
        if ($c === '&' && ($s[$i + 1] ?? '') === '>') {
            $i += 2; if (($s[$i] ?? '') === '>') $i++;
            while ($i < $n && ($s[$i] === ' ' || $s[$i] === "\t")) $i++;
            [$w, $i] = lerPalavra($s, $i, $extras); $cur['r'][] = $w; continue;
        }
        if (strpos(';|&()', $c) !== false) { $fecha(); $i++; continue; }
        if ($c === '>' || $c === '<') {
            if ($c === '<') {
                if (substr($s, $i, 3) === '<<<') { $i += 3; while ($i < $n && ($s[$i] === ' ' || $s[$i] === "\t")) $i++; [, $i] = lerPalavra($s, $i, $extras); continue; }
                if (substr($s, $i, 2) === '<<') {
                    $i += 2; $tira = false; if (($s[$i] ?? '') === '-') { $tira = true; $i++; }
                    while ($i < $n && ($s[$i] === ' ' || $s[$i] === "\t")) $i++;
                    [$d, $i] = lerPalavra($s, $i, $extras); $heredocs[] = [$d, $tira]; continue;
                }
                if (($s[$i + 1] ?? '') === '(') { $f = fimParen($s, $i + 1); $extras[] = substr($s, $i + 2, $f - $i - 2); $i = $f + 1; continue; }
                $i++; if (($s[$i] ?? '') === '&') { $i++; [, $i] = lerPalavra($s, $i, $extras); continue; }
                while ($i < $n && ($s[$i] === ' ' || $s[$i] === "\t")) $i++;
                [$w, $i] = lerPalavra($s, $i, $extras); $cur['in'][] = $w; continue;
            }
            $i++;
            if (($s[$i] ?? '') === '(') { $f = fimParen($s, $i); $extras[] = substr($s, $i + 1, $f - $i - 1); $i = $f + 1; continue; }
            if (($s[$i] ?? '') === '>' || ($s[$i] ?? '') === '|') $i++;
            if (($s[$i] ?? '') === '&') {
                $i++; [$w, $i] = lerPalavra($s, $i, $extras);
                if (!preg_match('/^(\d+|-)$/', $w)) $cur['r'][] = $w;
                continue;
            }
            while ($i < $n && ($s[$i] === ' ' || $s[$i] === "\t")) $i++;
            [$w, $i] = lerPalavra($s, $i, $extras); $cur['r'][] = $w; continue;
        }
        [$w, $j] = lerPalavra($s, $i, $extras);
        if ($j === $i) { $i++; continue; }
        // "2>arquivo", "2>&1": a palavra só de dígitos colada no > é o descritor
        if (preg_match('/^\d+$/', $w) && $j < $n && ($s[$j] === '>' || $s[$j] === '<')) { $i = $j; continue; }
        $cur['p'][] = $w; $i = $j;
    }
    $fecha();
    foreach ($extras as $e) foreach (segmentos($e, $prof + 1) as $x) $segs[] = $x;
    return $segs;
}
function nomeBase($t) { return strtolower(preg_replace('/\.(exe|bat|cmd)$/i', '', basename(barra($t)))); }
// tira prefixos (VAR=x, sudo, env, nohup, time, xargs…) e devolve [verbo, argumentos, veioDeXargs]
function verbo($p) {
    $i = 0; $n = count($p); $xargs = false;
    while ($i < $n) {
        $t = $p[$i];
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*=/', $t)) { $i++; continue; }
        if (in_array($t, ['{', '}', '!', 'if', 'then', 'else', 'elif', 'do', 'while', 'until'], true)) { $i++; continue; }
        $b = nomeBase($t);
        if (in_array($b, ['sudo', 'command', 'builtin', 'nohup', 'time', 'exec', 'nice', 'stdbuf', 'timeout', 'env'], true)) {
            $i++;
            while ($i < $n && ($p[$i] === '' || $p[$i][0] === '-' || ($b === 'timeout' && preg_match('/^\d/', $p[$i])) || ($b === 'env' && strpos($p[$i], '=') !== false))) {
                if ($b !== 'env' && in_array($p[$i], ['-n', '-u', '-g', '-k', '-s', '-o', '-e', '-i'], true)) $i++;
                $i++;
            }
            continue;
        }
        if ($b === 'xargs') {
            $xargs = true; $i++;
            while ($i < $n && $p[$i] !== '' && $p[$i][0] === '-') { if (preg_match('/^-[IiLlnPsEda]$/', $p[$i])) $i++; $i++; }
            continue;
        }
        break;
    }
    if ($i >= $n) return [null, [], $xargs];
    return [nomeBase($p[$i]), array_slice($p, $i + 1), $xargs];
}
function gitSub($a) {
    $n = count($a);
    for ($i = 0; $i < $n; $i++) {
        $t = $a[$i];
        if (in_array($t, ['-C', '-c', '--git-dir', '--work-tree', '--namespace', '--exec-path'], true)) { $i++; continue; }
        if ($t !== '' && $t[0] === '-') continue;
        return [strtolower($t), array_slice($a, $i + 1)];
    }
    return [null, []];
}
// pathspec que exclui wikis/ inteira (ou wikis/specs): ':(exclude)wikis', ':!wikis', ':^wikis/**'.
// ':(exclude)wikis/glossario.md' exclui um arquivo só, e o diff continua entregando o 01 e o 03.
function exclusaoPathspec($t) {
    return (bool) preg_match('#^:(?:\((?:[a-z]+,)*exclude(?:,[a-z]+)*\)|!|\^)/?wikis(?:/specs)?(?:/|/\*\*)?$#i', $t);
}
function temExclusaoWikis($a) {
    foreach ($a as $t) if (exclusaoPathspec($t)) return true;
    return false;
}
// expande find -exec, bash -c, sh -c, eval e cmd /c em segmentos próprios
function subSegmentos($v, $a, $prof) {
    $out = [];
    if ($v === 'find') {
        $n = count($a);
        for ($i = 0; $i < $n; $i++) {
            if (in_array($a[$i], ['-exec', '-execdir', '-ok', '-okdir'], true)) {
                $sub = [];
                for ($i++; $i < $n && !in_array($a[$i], [';', '+'], true); $i++) $sub[] = $a[$i];
                if ($sub) $out[] = ['p' => $sub, 'r' => [], 'in' => []];
            }
        }
    } elseif (in_array($v, ['bash', 'sh', 'zsh', 'dash', 'ksh'], true)) {
        // -c sozinho ou junto de outras opções curtas (-lc, -ec, -xc)
        $k = null;
        foreach ($a as $i => $t) if (preg_match('/^-[A-Za-z]*c[A-Za-z]*$/', $t)) { $k = $i; break; }
        if ($k !== null && isset($a[$k + 1])) $out = segmentos($a[$k + 1], $prof + 1);
    } elseif ($v === 'eval') {
        $out = segmentos(implode(' ', $a), $prof + 1);
    } elseif ($v === 'cmd') {
        foreach ($a as $k => $t) if (preg_match('#^/+c$#i', $t)) { $out = segmentos(barra(implode(' ', array_slice($a, $k + 1))), $prof + 1); break; }
    }
    return $out;
}
function expande($segs, $prof = 0) {
    $out = [];
    foreach ($segs as $sg) {
        $out[] = $sg;
        [$v, $a] = verbo($sg['p']);
        if ($v === null) continue;
        $sub = subSegmentos($v, $a, $prof);
        if ($sub) foreach (expande($sub, $prof + 1) as $x) $out[] = $x;
    }
    return $out;
}

// ---------- regras de leitura (Read, Grep, e Bash quando o perfil tem bashLe)
function checaLeitura($abs, $ferr) {
    global $P;
    if ($P['lista']) {
        if (!permitidoAdversario($abs)) nega("$ferr negado — " . mostra($abs) . " fora da lista: 00, 04 e 05 da wiki, wikis/glossario.md e arquivos das skills; {$P['porque']}");
        return;
    }
    $n = numeroWiki($abs);
    if ($n !== null && in_array($n, $P['le'], true)) nega("$ferr negado — " . mostra($abs) . " é o $n da wiki; {$P['porque']}");
}
function checaGrep($in) {
    global $P, $CWD, $RAIZ;
    $path = is_string($in['path'] ?? null) ? $in['path'] : '';
    $abs = normaliza($path === '' ? '.' : $path, $CWD);
    $dir = pareceDir($abs);
    if ($P['lista']) {
        if ($dir ? !emSkills($abs) : !permitidoAdversario($abs)) nega('Grep negado — alvo ' . mostra($abs) . ': o adversário só faz Grep num arquivo da lista (Grep devolve conteúdo)');
        return;
    }
    if (!$P['le']) return;
    if (!$dir) { checaLeitura($abs, 'Grep'); return; }
    if (!alcancaWikis($abs)) return;
    $tipo = is_string($in['type'] ?? null) ? trim($in['type']) : '';
    $glob = is_string($in['glob'] ?? null) ? $in['glob'] : '';
    if (!filtroAlcanca(partesGlob($glob), $tipo === '' ? [] : [$tipo], array_unique([$abs, $CWD, $RAIZ]))) return;
    nega('Grep negado — o alvo ' . mostra($abs) . ' alcança wikis/specs/**/' . implode('-* e ', $P['le']) . "-*; restrinja o path (ex.: app/) ou use glob positivo (ex.: *.php) — glob só negado ('!vendor/**') casa todo o resto, e o glob vence o type; {$P['porque']}");
}
// a palavra só menciona wikis para excluí-la? (':(exclude)wikis', '--exclude-dir=wikis', '-g !wikis')
function exclusaoWikis($t, $ant) {
    if (exclusaoPathspec($t)) return true;
    if (preg_match('#^--(exclude-dir|ignore-dir|ignore)=#i', $t) || in_array($ant, ['--exclude-dir', '--ignore-dir', '--ignore'], true)) return true;
    if (preg_match('#^--i?glob=!#i', $t)) return true;
    return $t !== '' && $t[0] === '!' && in_array($ant, ['-g', '--glob', '--iglob'], true);
}
function checaBashLeitura($cmd, $segs) {
    global $P, $CWD;
    $nums = implode('|', $P['le']);
    $re = '#(?<![0-9A-Za-z])(?<!07-tickets/)(?<!07-tickets\\\\)(' . $nums . ')-[A-Za-z*?\[{][^\s\'"|;&<>)]*#i';
    if (preg_match($re, $cmd, $m)) nega("Bash negado — o comando cita {$m[0]}, o {$m[1]} da wiki; {$P['porque']}");
    // o mesmo sobre as palavras já sem aspas e sem barra invertida ("01"-plano, 0\1-plano); e nome montado
    // por expansão ($f/0[13]-*, 0$((1))-…, $(ls …), `…`) num comando que cita wikis/: o nome final não aparece
    $citaWikis = false; $expansao = null;
    foreach ($segs as $sg) {
        $ant = '';
        foreach (array_merge($sg['p'], $sg['in'], $sg['r']) as $t) {
            if (preg_match($re, $t, $m)) nega("Bash negado — o comando cita {$m[0]} (sem as aspas), o {$m[1]} da wiki; {$P['porque']}");
            if (stripos($t, 'wikis') !== false && !exclusaoWikis($t, $ant)) $citaWikis = true;
            if ($expansao === null && preg_match('/\$[A-Za-z_{(\'"]|`/', $t)) $expansao = $t;
            $ant = $t;
        }
    }
    if ($citaWikis && $expansao !== null) {
        nega("Bash negado — expansão de shell ($expansao) num comando que cita wikis/: o hook não confere o nome montado, que pode ser o " . implode('/', $P['le']) . "; use o path literal (ou Read/Grep); {$P['porque']}");
    }
    $temXargs = false;
    foreach ($segs as $sg) {
        [$v, $a, $xa] = verbo($sg['p']);
        $temXargs = $temXargs || $xa;
        foreach (array_merge($sg['p'], $sg['in']) as $t) {
            if (stripos($t, 'wikis') === false || !temGlob($t)) continue;
            if ($t[0] === '!' || preg_match('#^(:|--(exclude|exclude-dir|glob|iglob)=!?)#i', $t) || preg_match('#^--exclude-dir=#i', $t)) continue;
            if (in_array($v, ['ls', 'tree', 'du', 'stat', 'echo', 'printf', 'test', '['], true)) continue;
            if ($v === 'find' && !preg_grep('/^-(exec|execdir|ok|okdir)$/', $a)) continue;
            if (strpos(barra($t), '..') === false && dentroDeTickets(normaliza(prefixoLiteral(barra($t)), $CWD))) continue;
            nega("Bash negado — glob em wikis/ ($t) alcança o " . implode('/', $P['le']) . " da wiki; {$P['porque']}");
        }
        if ($v === null) continue;
        if (in_array($v, ['cd', 'pushd'], true) && isset($a[0]) && stripos($a[0], 'wikis') !== false) {
            nega("Bash negado — cd para {$a[0]}: dentro da wiki, o próximo comando alcança o " . implode('/', $P['le']) . "; {$P['porque']}");
        }
        if ($v === 'git') {
            [$sub, $ga] = gitSub($a);
            $mostraPatch = in_array($sub, ['diff', 'show', 'whatchanged', 'format-patch', 'difftool', 'range-diff'], true)
                || ($sub === 'log' && preg_grep('/^(-p|-u|--patch|--patch-with-stat|--patch-with-raw|--cc|-L.*)$/', $ga));
            if ($mostraPatch && !temExclusaoWikis($ga)) {
                nega("Bash negado — git $sub sem excluir wikis/ entrega o " . implode('/', $P['le']) . " dentro do diff; use git $sub … -- . ':(exclude)wikis'");
            }
            // git archive {árvore} [path…] despeja o conteúdo (| tar -xO): sem path, a árvore inteira
            if ($sub === 'archive' && !temExclusaoWikis($ga)) {
                $pos = array_slice(naoOpcoes($ga), 1);
                $alcanca = !$pos;
                foreach ($pos as $sp) if (!temGlob($sp) ? alcancaWikis(normaliza($sp, $CWD)) || in_array(numeroWiki(normaliza($sp, $CWD)), $P['le'], true) : (stripos($sp, 'wikis') !== false || globCasa($sp, $P['le']))) $alcanca = true;
                if ($alcanca) nega("Bash negado — git archive que alcança wikis/ entrega o conteúdo do " . implode('/', $P['le']) . "; restrinja o path (ex.: app/) ou exclua ':(exclude)wikis'");
            }
            if ($sub === 'grep' && !temExclusaoWikis($ga)) {
                $dd = array_search('--', $ga, true);
                $specs = $dd === false ? [] : array_slice($ga, $dd + 1);
                $alcanca = !$specs;
                foreach ($specs as $sp) if (!temGlob($sp) ? alcancaWikis(normaliza($sp, $CWD)) : (stripos($sp, 'wikis') !== false || globCasa($sp, $P['le']))) $alcanca = true;
                if ($alcanca) nega("Bash negado — git grep sem pathspec que exclua wikis/ lê o " . implode('/', $P['le']) . " da wiki; use git grep … -- ':(exclude)wikis' (ou um pathspec como app/)");
            }
            continue;
        }
        if (in_array($v, ['grep', 'egrep', 'fgrep', 'rg', 'ag', 'ack', 'ugrep'], true)) checaBuscaRecursiva($v, $a);
        if ($v === 'find' && preg_grep('/^-(exec|execdir|ok|okdir)$/', $a)) {
            $caminhos = [];
            foreach ($a as $t) { if ($t === '' || $t[0] === '-' || $t === '(' || $t === '!') break; $caminhos[] = $t; }
            if (!$caminhos) $caminhos = ['.'];
            $filtra = false;
            foreach ($a as $k => $t) {
                if (in_array($t, ['-path', '-ipath', '-wholename', '-iwholename'], true) && stripos($a[$k + 1] ?? '', 'wikis') !== false) $filtra = true;
                if (in_array($t, ['-name', '-iname'], true) && isset($a[$k + 1]) && !globCasa($a[$k + 1], $P['le'])) $filtra = true;
            }
            foreach ($caminhos as $c) if (!$filtra && alcancaWikis(normaliza(temGlob($c) ? prefixoLiteral($c) : $c, $CWD))) nega("Bash negado — find -exec sobre " . $c . " alcança wikis/specs; restrinja o caminho ou o -name; {$P['porque']}");
        }
    }
    if ($temXargs && stripos($cmd, 'wikis') !== false) nega("Bash negado — xargs num comando que menciona wikis/: a lista pode incluir o " . implode('/', $P['le']) . "; {$P['porque']}");
}
// valor de --exclude-dir/--ignore-dir: nome de pasta (glob) que casa wikis ou specs
function excluiPastaWiki($val) {
    $val = trim(barra($val), '/');
    return $val !== '' && (fnmatch($val, 'wikis', FNM_CASEFOLD) || fnmatch($val, 'specs', FNM_CASEFOLD));
}
function checaBuscaRecursiva($v, $a) {
    global $P, $CWD, $RAIZ;
    $grep = in_array($v, ['grep', 'egrep', 'fgrep'], true);
    $rec = !$grep;
    $comArg = $grep ? ['-e', '-f', '-A', '-B', '-C', '-m', '-d', '-D', '--regexp', '--file', '--include', '--exclude', '--exclude-dir', '--label']
                    : ['-e', '-f', '-g', '--glob', '--iglob', '-t', '--type', '-T', '--type-not', '-m', '-A', '-B', '-C', '-j', '-M', '-E', '--max-depth', '--ignore-file', '-G', '--ignore', '--ignore-dir'];
    $padrao = false; $alvos = []; $inclui = []; $exclui = false; $tipos = []; $globs = []; $soArgs = false;
    $n = count($a);
    for ($i = 0; $i < $n; $i++) {
        $t = $a[$i];
        if (!$soArgs && $t === '--') { $soArgs = true; continue; }
        if (!$soArgs && $t !== '' && $t[0] === '-' && $t !== '-') {
            $val = null; $opt = $t;
            if (strpos($t, '=') !== false && substr($t, 0, 2) === '--') { [$opt, $val] = explode('=', $t, 2); }
            elseif (in_array($t, $comArg, true)) { $val = $a[$i + 1] ?? ''; $i++; }
            if ($grep && (preg_match('/^-[A-Za-z]*[rR]/', $opt) || in_array($opt, ['--recursive', '--dereference-recursive'], true) || (($opt === '-d' || $opt === '--directories') && $val === 'recurse'))) $rec = true;
            if (in_array($opt, ['-e', '-f', '--regexp', '--file'], true) || preg_match('/^-e./', $opt)) $padrao = true;
            if ($opt === '--include' && $val !== null) $inclui[] = $val;
            if (($opt === '--exclude-dir' || $opt === '--ignore' || $opt === '--ignore-dir') && $val !== null && excluiPastaWiki($val)) $exclui = true;
            // rg/ugrep: os globs vão em ordem para o filtroAlcanca (o último que casa decide; glob vence type)
            if (in_array($opt, ['-g', '--glob', '--iglob'], true) && $val !== null && $val !== '') {
                $neg = $val[0] === '!';
                foreach (expandeChaves($neg ? substr($val, 1) : $val) as $x) $globs[] = ($neg ? '!' : '') . $x;
            }
            if (in_array($opt, ['-t', '--type'], true) && $val !== null) $tipos[] = $val;
            continue;
        }
        if (!$padrao) { $padrao = true; continue; }
        $alvos[] = $t;
    }
    if (!$rec || $exclui) return;
    if ($grep && $inclui) { $algum = false; foreach ($inclui as $g) foreach (expandeChaves($g) as $x) if (globCasa($x, $P['le'])) $algum = true; if (!$algum) return; }
    if (!$alvos) $alvos = ['.'];
    foreach ($alvos as $t) {
        $abs = normaliza(temGlob($t) ? prefixoLiteral($t) : $t, $CWD);
        if (pareceDir($abs) ? !alcancaWikis($abs) : !in_array(numeroWiki($abs), $P['le'], true)) continue;
        if (!$grep && pareceDir($abs) && !filtroAlcanca($globs, $tipos, array_unique([$abs, $CWD, $RAIZ]))) continue;
        nega("Bash negado — $v recursivo em " . ($t === '.' ? '. (diretório corrente)' : $t) . ' alcança wikis/specs; restrinja o alvo (ex.: app/) ou exclua wikis (--exclude-dir=wikis, -g \'!wikis\'); ' . $P['porque']);
    }
}

// ---------- regras de alteração da árvore (Bash)
$ARV = ['rm', 'rmdir', 'mv', 'cp', 'tee', 'touch', 'mkdir', 'truncate', 'ln', 'unlink', 'install', 'patch', 'shred', 'chmod', 'chown',
    'del', 'erase', 'copy', 'move', 'ren', 'rename', 'rd', 'md', 'xcopy', 'robocopy', 'mklink'];
$GITMUT = ['add', 'commit', 'stash', 'checkout', 'switch', 'reset', 'restore', 'clean', 'rebase', 'merge', 'push', 'pull', 'rm', 'mv',
    'apply', 'am', 'cherry-pick', 'revert'];
function naoOpcoes($a) { return array_values(array_filter($a, function ($t) { return $t === '' || $t[0] !== '-'; })); }
// argumentos do pint quando o comando é pint (vendor/bin/pint, php vendor/bin/pint, sail pint, sail bin pint); senão null
function argsPint($v, $a) {
    if ($v === 'pint') return $a;
    if ($v !== 'php' && $v !== 'sail') return null;
    $i = 0; $n = count($a);
    while ($i < $n && $a[$i] !== '' && $a[$i][0] === '-') { if (in_array($a[$i], ['-d', '-c', '-z'], true)) $i++; $i++; }
    if ($i >= $n) return null;
    $b = nomeBase($a[$i]);
    if ($b === 'pint') return array_slice($a, $i + 1);
    if ($v === 'sail' && $b === 'bin' && isset($a[$i + 1]) && nomeBase($a[$i + 1]) === 'pint') return array_slice($a, $i + 2);
    if ($v === 'sail' && $b === 'php') return argsPint('php', array_slice($a, $i + 1));
    return null;
}
// o pint grava fora do permitido? Devolve o motivo da negação, ou null. --test (sem --repair) só confere.
function pintNega($a, $modo) {
    $comValor = ['--config', '--preset', '--format', '--output-to-file', '--output-format', '--cache-file', '--diff', '--max-processes'];
    $paths = []; $test = false; $repair = false; $sujo = null; $soArgs = false; $n = count($a);
    for ($i = 0; $i < $n; $i++) {
        $t = $a[$i];
        if (!$soArgs && $t === '--') { $soArgs = true; continue; }
        if (!$soArgs && $t !== '' && $t[0] === '-') {
            $nome = explode('=', $t, 2)[0];
            if ($nome === '--test') $test = true;
            if ($nome === '--repair') $repair = true;
            if ($nome === '--dirty' || $nome === '--diff') $sujo = $nome;
            if (strpos($t, '=') === false && in_array($nome, $comValor, true) && isset($a[$i + 1]) && ($a[$i + 1] === '' || $a[$i + 1][0] !== '-')) $i++;
            continue;
        }
        $paths[] = $t;
    }
    if ($test && !$repair) return null;
    if ($modo === 'tudo') return 'pint sem --test formata e grava arquivos';
    if ($sujo !== null) return "pint $sujo formata todo arquivo sujo ou alterado, app/ incluído";
    if (!$paths) return 'pint sem path formata o projeto inteiro, app/ incluído';
    foreach ($paths as $p) if (escritaProibida($p)) return "pint em $p";
    return null;
}
// scripts da feature-tickets que gravam, chamados por bash/sh {script}, source/. {script} ou direto:
// indice.sh sem --check nem --status e espelho-gh.sh --aplicar. Devolve [motivo, forma que não grava], ou null.
function scriptQueGrava($v, $a) {
    $SCR = ['indice.sh', 'espelho-gh.sh'];
    if (in_array($v, $SCR, true)) { $nome = $v; $args = $a; }
    elseif (in_array($v, ['bash', 'sh', 'zsh', 'dash', 'ksh', 'source', '.'], true)) {
        $n = count($a);
        for ($i = 0; $i < $n; $i++) {
            $t = $a[$i];
            if (preg_match('/^-[A-Za-z]*c[A-Za-z]*$/', $t)) return null;   // bash -c '…': o texto já virou segmento (subSegmentos)
            if ($t === '--') { $i++; break; }
            if ($t === '' || ($t[0] !== '-' && $t[0] !== '+')) break;
            if (in_array($t, ['-o', '+o', '-O', '+O', '--rcfile', '--init-file'], true)) $i++;
        }
        if ($i >= $n || !in_array(nomeBase($a[$i]), $SCR, true)) return null;
        $nome = nomeBase($a[$i]); $args = array_slice($a, $i + 1);
    } else return null;
    if ($nome === 'indice.sh') {
        if (in_array('--check', $args, true) || in_array('--status', $args, true)) return null;
        return ['indice.sh sem --check nem --status gera os quadros e grava wikis/specs/INDEX.md e os 07-tickets/README.md',
            'use indice.sh --check {wiki} ou --status {wiki}, que não gravam'];
    }
    if (!preg_grep('/^--aplicar(=|$)/', $args)) return null;
    return ['espelho-gh.sh --aplicar grava **Issue** nos 07-tickets/NN-*.md e cria issues no GitHub',
        'sem --aplicar é dry-run e não grava'];
}
function checaArvore($segs) {
    global $P, $ARV, $GITMUT;
    $modo = $P['arvore'];
    foreach ($segs as $sg) {
        foreach ($sg['r'] as $t) {
            if ($modo === 'tudo' && !destinoInofensivo($t)) nega("Bash negado — redirecionamento para $t grava dentro do repositório; {$P['porque']}");
            if ($modo === 'caminhos' && escritaProibida($t)) nega("Bash negado — redirecionamento para $t: " . ESCRITA);
        }
        [$v, $a] = verbo($sg['p']);
        if ($v === null) continue;
        if ($v === 'git') {
            [$gs] = gitSub($a);
            if (in_array($gs, $GITMUT, true)) nega("Bash negado — git $gs altera a árvore; {$P['porque']}");
            continue;
        }
        $sq = scriptQueGrava($v, $a);
        if ($sq !== null) {
            if ($modo === 'tudo') nega("Bash negado — {$sq[0]}; {$P['porque']}; {$sq[1]}");
            nega("Bash negado — {$sq[0]}: " . ESCRITA . "; {$sq[1]}");
        }
        $pa = argsPint($v, $a);
        if ($pa !== null) {
            $motivo = pintNega($pa, $modo);
            if ($motivo === null) continue;
            if ($modo === 'tudo') nega("Bash negado — $motivo; {$P['porque']}");
            nega("Bash negado — $motivo: " . ESCRITA . "; use vendor/bin/pint {arquivos de teste do lote}");
        }
        $altera = in_array($v, $ARV, true);
        $alvos = naoOpcoes($a);
        if ($v === 'sed' && preg_grep('/^(-[nrEsuz]*i|--in-place)/', $a)) $altera = true;
        if ($v === 'perl' && preg_grep('/^-[pnlaswWtTcuUv0]*i/', $a)) $altera = true;
        if (in_array($v, ['awk', 'gawk'], true) && (preg_grep('/^--inplace$/', $a) || (($k = array_search('-i', $a, true)) !== false && ($a[$k + 1] ?? '') === 'inplace'))) $altera = true;
        if ($v === 'dd') { $alvos = []; foreach ($a as $t) if (strpos($t, 'of=') === 0) { $altera = true; $alvos[] = substr($t, 3); } }
        if ($v === 'find') {
            if (preg_grep('/^-(delete|fprint0?|fprintf|fls)$/', $a)) {
                $altera = true; $alvos = [];
                foreach ($a as $t) { if ($t === '' || $t[0] === '-' || $t === '(' || $t === '!') break; $alvos[] = $t; }
                if (!$alvos) $alvos = ['.'];
            }
        }
        if (!$altera) continue;
        if ($modo === 'tudo') {
            if ($v === 'tee') { $ok = true; foreach ($alvos as $t) if (!destinoInofensivo($t)) $ok = false; if ($ok) continue; }
            nega("Bash negado — $v altera a árvore; {$P['porque']}");
        }
        if ($modo === 'caminhos') {
            foreach ($alvos as $t) if (escritaProibida($t)) nega("Bash negado — $v em $t: " . ESCRITA);
        }
    }
}

// ---------- decisão
$alvo = $IN['file_path'] ?? $IN['notebook_path'] ?? null;
switch ($TOOL) {
    case 'Read':
        if (!is_string($alvo) || $alvo === '') nega('Read sem file_path — nega (falha fechado)');
        checaLeitura(normaliza($alvo, $CWD), 'Read');
        break;
    case 'Grep':
        checaGrep($IN);
        break;
    case 'Glob':
        break;
    case 'Bash':
        $cmd = $IN['command'] ?? null;
        if (!is_string($cmd)) nega('Bash sem command — nega (falha fechado)');
        if ($P['lista']) nega("Bash negado — {$P['porque']}; este perfil não usa Bash");
        $segs = expande(segmentos($cmd));
        if ($P['bashLe']) checaBashLeitura($cmd, $segs);
        if ($P['arvore'] !== null) checaArvore($segs);
        break;
    case 'Edit': case 'Write': case 'MultiEdit': case 'NotebookEdit':
        if (!$P['escreve']) nega("$TOOL negado — este perfil só lê; {$P['porque']}");
        if (!is_string($alvo) || $alvo === '') nega("$TOOL sem file_path — nega (falha fechado)");
        if (escritaProibida($alvo)) nega("$TOOL negado — " . mostra(normaliza($alvo, $CWD)) . ": " . ESCRITA);
        break;
}
exit(0);
PHP

# O PHP vai por arquivo temporário, não por `php -r`: no Windows a linha de comando tem teto de 32.767
# caracteres e este bloco já passa de 30 mil — por argv, mais 2,7 mil bastavam para o php sair com 126
# ("Argument list too long") e o hook negar toda ferramenta dos cinco agentes. O JSON segue no stdin.
t=$(mktemp "${TMPDIR:-/tmp}/guarda-subagente.XXXXXX") || { echo "guarda-subagente.sh: mktemp falhou — nega (falha fechado)" >&2; exit 2; }
trap 'rm -f "$t"' EXIT
printf '<?php\n%s\n' "$PHP_CODE" > "$t" || { echo "guarda-subagente.sh: não consegui gravar $t — nega (falha fechado)" >&2; exit 2; }
tp=$t
if command -v cygpath >/dev/null 2>&1; then tp=$(cygpath -m "$t"); fi
MSYS_NO_PATHCONV=1 MSYS2_ARG_CONV_EXCL='*' php "$tp" "$1"
st=$?
if [ "$st" -ne 0 ] && [ "$st" -ne 2 ]; then
  echo "guarda-subagente.sh: php saiu com $st — nega (falha fechado)" >&2
  exit 2
fi
exit "$st"

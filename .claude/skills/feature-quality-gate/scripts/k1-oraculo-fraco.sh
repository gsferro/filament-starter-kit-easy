#!/usr/bin/env bash
# k1-oraculo-fraco.sh — passo K1 da dimensão K: teste sem oráculo ou com oráculo fraco.
#
# Uso (na raiz do projeto Laravel):
#   bash {skills}/feature-quality-gate/scripts/k1-oraculo-fraco.sh <arquivo-ou-diretório-de-teste>...
#   Os arquivos são os testes novos ou alterados do diff:
#   git diff --name-only --diff-filter=AM {base}...HEAD -- tests/
#
# O que confere, por teste (`it()`/`test()` do Pest com closure, ou higher-order — sem closure, com a cadeia
#   de mensagens como corpo: `it('abre')->get('/x')->assertOk()`; método `test*`, `#[Test]` ou `@test` do PHPUnit):
#   - nenhuma assertion reconhecida (`assert*`, `expect*`, cadeia `expect(...)->to*`, `->throws()`,
#     `shouldReceive`/`shouldHaveReceived`/`expects` de mock);
#   - `assertOk()`/`assertSuccessful()` (ou `assertStatus(200)`) como assertion única;
#   - `assertNoJavaScriptErrors()`/`assertNoSmoke()` como assertion única (CT-B);
#   - só asserção de tipo (`toBeInt`, `toBeString`, `toBeArray`…) ou `->not->toThrow()` — tautologia;
#   - só expectativa negada (`->not->toBe(...)`) — nenhum valor esperado;
#   e, por ocorrência: `assertDatabaseHas`/`assertDatabaseMissing` só com a chave `id`; `assertModelExists`.
# O que NÃO confere (fica com o julgamento do gate): `assertSee('{texto de layout}')` como oráculo — saber se
#   o texto é de layout exige ler a tela; se a assertion afirma a regra certa; asserção feita dentro de helper
#   (o script não segue a chamada: teste que só chama helper sai como "sem assertion reconhecida").
# Teste sem closure e sem mensagem higher-order (só configuração: `->group()`, `->with()`…; vira todo no Pest) e
#   teste com `->todo()`/`->skip()` não são conferidos.
#
# Quem chama: feature-quality-gate, dimensão K, passo 1 (K1). Cada linha é candidata: o gate confere e
#   decide a severidade pela tabela da K1 antes de virar achado.
# Contrato: silêncio + exit 0 = OK; achado = `arquivo:linha: mensagem` + exit 1; erro de uso ou de
#   ambiente = mensagem no stderr + exit 2.
#
# Exemplo de falha:
#   $ bash k1-oraculo-fraco.sh tests/Feature/AprovacaoFracaTest.php
#   tests/Feature/AprovacaoFracaTest.php:5: teste 'aprova o pedido' — sem nenhuma assertion reconhecida (asserção dentro de helper não é seguida: conferir o helper)
#   tests/Feature/AprovacaoFracaTest.php:10: teste 'abre a fila' — assertOk()/assertSuccessful() como assertion única
#   tests/Feature/AprovacaoFracaTest.php:18: teste 'grava o pedido' — assertDatabaseHas só com a chave primária (id)
#   tests/Feature/AprovacaoFracaTest.php:21: teste 'conta os pedidos' — só asserção de tipo ou "não lança" — tautologia (toBeInt())
#   (exit 1; saída cortada — o caso completo tem 8 linhas)
set -u

if ! command -v php >/dev/null 2>&1; then
  echo "k1-oraculo-fraco.sh: php não encontrado no PATH (o script usa PHP embutido)" >&2
  exit 2
fi

read -r -d '' PHP_CODE <<'PHP'
function uso($msg = null) {
    fwrite(STDERR, ($msg ? "k1-oraculo-fraco.sh: $msg\n" : '')
        . "uso: k1-oraculo-fraco.sh <arquivo-ou-diretório-de-teste>...\n");
    exit(2);
}

function abre($t) {
    return $t === '(' || $t === '[' || $t === '{' || $t === T_CURLY_OPEN || $t === T_DOLLAR_OPEN_CURLY_BRACES
        || (defined('T_ATTRIBUTE') && $t === T_ATTRIBUTE);
}

function fecha($s, $i) {
    $d = 0;
    $n = count($s);
    for ($k = $i; $k < $n; $k++) {
        $t = $s[$k][0];
        if (abre($t)) $d++;
        elseif ($t === ')' || $t === ']' || $t === '}') { $d--; if ($d === 0) return $k; }
    }
    return $n - 1;
}

function sig($raw) {
    $s = [];
    $doc = null;
    $linha = 1;
    foreach ($raw as $t) {
        if (is_array($t)) {
            $linha = $t[2];
            if ($t[0] === T_DOC_COMMENT) { $doc = $t[1]; continue; }
            if ($t[0] === T_WHITESPACE || $t[0] === T_COMMENT) continue;
            $s[] = [$t[0], $t[1], $t[2], $t[0] === T_FUNCTION ? $doc : null];
        } else {
            $s[] = [$t, $t, $linha, null];
            if ($t === ';' || $t === '{' || $t === '}') $doc = null;
        }
    }
    return $s;
}

function soChave($s, $abre) {
    $fim = fecha($s, $abre);
    $d = 0;
    $virg = null;
    for ($k = $abre + 1; $k < $fim; $k++) {
        $t = $s[$k][0];
        if (abre($t)) $d++;
        elseif ($t === ')' || $t === ']' || $t === '}') $d--;
        elseif ($t === ',' && $d === 0) { $virg = $k; break; }
    }
    if ($virg === null) return false;
    $ini = $virg + 1;
    if ($s[$ini][0] === T_ARRAY && ($s[$ini + 1][0] ?? null) === '(') $ini++;
    elseif ($s[$ini][0] !== '[') return false;
    $fimArr = fecha($s, $ini);
    $itens = [];
    $atual = [];
    $d = 0;
    for ($k = $ini + 1; $k < $fimArr; $k++) {
        $t = $s[$k][0];
        if (abre($t)) $d++;
        elseif ($t === ')' || $t === ']' || $t === '}') $d--;
        if ($t === ',' && $d === 0) { $itens[] = $atual; $atual = []; continue; }
        $atual[] = $s[$k];
    }
    if ($atual) $itens[] = $atual;
    if (count($itens) !== 1) return false;
    $it = $itens[0];
    return count($it) >= 3 && $it[0][0] === T_CONSTANT_ENCAPSED_STRING
        && trim($it[0][1], "'\"") === 'id' && $it[1][0] === T_DOUBLE_ARROW;
}

function analisa($s, $a, $b, $cadeia = false) {
    $A = [];
    $M = [];
    $pk = [];
    $ops = [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR];
    for ($k = $a; $k <= $b; $k++) {
        if ($s[$k][0] !== T_STRING) continue;
        $nome = strtolower($s[$k][1]);
        $prox = $s[$k + 1][0] ?? null;
        $membro = in_array($s[$k - 1][0] ?? null, [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON], true);
        // Na cadeia higher-order (teste sem closure), ->expect(...) abre expectativa como expect(...).
        if ($nome === 'expect' && (!$membro || $cadeia) && $prox === '(') {
            $k = fecha($s, $k + 1);
            $neg = false;
            while (in_array($s[$k + 1][0] ?? null, $ops, true) && ($s[$k + 2][0] ?? null) === T_STRING) {
                $orig = $s[$k + 2][1];
                $m = strtolower($orig);
                $k += 2;
                if ($m === 'not') $neg = !$neg;
                elseif ($m === 'and') $neg = false;
                elseif (str_starts_with($m, 'to')) { $M[] = [$m, $neg, $orig]; $neg = false; }
                if (($s[$k + 1][0] ?? null) === '(') $k = fecha($s, $k + 1);
            }
            continue;
        }
        if ($prox !== '(') continue;
        $mock = in_array($nome, ['shouldreceive', 'shouldhavereceived', 'shouldnotreceive', 'shouldnothavereceived'], true);
        if (!str_starts_with($nome, 'assert') && !str_starts_with($nome, 'expect') && !$mock) continue;
        if ($nome === 'assertstatus' && ($s[$k + 2][1] ?? null) === '200' && ($s[$k + 3][0] ?? null) === ')') $nome = 'assertok';
        $A[] = $nome;
        if (($nome === 'assertdatabasehas' || $nome === 'assertdatabasemissing') && soChave($s, $k + 1)) {
            $pk[] = [$s[$k][2], $s[$k][1] . ' só com a chave primária (id)'];
        }
        if ($nome === 'assertmodelexists') $pk[] = [$s[$k][2], 'assertModelExists() confere só a chave primária'];
    }
    return [$A, $M, $pk];
}

function testes($s) {
    $out = [];
    $n = count($s);
    $ops = [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR];
    // Métodos de configuração do TestCall do Pest: não são mensagem higher-order para o TestCase.
    $config = '/^(with|depends|group|only\w*|skip\w*|todo|wip|done|issue|ticket|note|pr|assignee|fixme|repeat|throws\w*|covers\w*|after|before|flaky|retry|defer)$/';
    $mods = [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_FINAL, T_ABSTRACT];
    for ($i = 0; $i < $n; $i++) {
        $t = $s[$i];
        if ($t[0] === T_STRING && in_array(strtolower($t[1]), ['it', 'test'], true)) {
            $ant = $s[$i - 1][0] ?? null;
            if (in_array($ant, [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW], true)) continue;
            if (($s[$i + 1][0] ?? null) !== '(' || ($s[$i + 2][0] ?? null) === ')') continue;
            $fim = fecha($s, $i + 1);
            $desc = $s[$i + 2][0] === T_CONSTANT_ENCAPSED_STRING ? $s[$i + 2][1] : null;
            $corpo = false;
            for ($k = $i + 2; $k < $fim; $k++) if ($s[$k][0] === T_FUNCTION || $s[$k][0] === T_FN) { $corpo = true; break; }
            $k = $fim;
            $throws = false;
            $pula = false;
            $ho = false;
            while (in_array($s[$k + 1][0] ?? null, $ops, true) && ($s[$k + 2][0] ?? null) === T_STRING) {
                $m = strtolower($s[$k + 2][1]);
                $k += 2;
                if (str_starts_with($m, 'throws')) $throws = true;
                if ($m === 'todo' || $m === 'skip') $pula = true;
                if (!preg_match($config, $m)) $ho = true;
                if (($s[$k + 1][0] ?? null) === '(') $k = fecha($s, $k + 1);
            }
            if ($pula) continue;
            if ($corpo) {
                [$A, $M, $pk] = analisa($s, $i + 2, $fim - 1);
            } elseif ($ho) {
                // Higher-order: sem closure, a cadeia depois de it()/test() é o corpo do teste.
                [$A, $M, $pk] = analisa($s, $fim + 1, $k, true);
            } else {
                continue; // sem closure e só configuração (group, with…): vira todo no Pest
            }
            $out[] = [$t[2], 'teste ' . ($desc ?? '(sem descrição literal)'), $A, $M, $throws, $pk];
            continue;
        }
        if ($t[0] === T_FUNCTION && ($s[$i + 1][0] ?? null) === T_STRING && in_array($s[$i - 1][0] ?? null, $mods, true)) {
            $privado = false;
            for ($k = $i - 1; $k >= 0 && in_array($s[$k][0], $mods, true); $k--) {
                if ($s[$k][0] === T_PRIVATE || $s[$k][0] === T_PROTECTED) $privado = true;
            }
            if ($privado) continue;
            $nome = $s[$i + 1][1];
            $marcado = preg_match('/^test/i', $nome) || ($t[3] !== null && preg_match('/@test\b/', $t[3]));
            if (!$marcado) {
                for ($k = $i - 1; $k >= 0 && $k >= $i - 12; $k--) {
                    $tk = $s[$k][0];
                    if ($tk === ';' || $tk === '{' || $tk === '}') break;
                    if (in_array($tk, [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)
                        && preg_match('/(^|\\\\)Test$/', $s[$k][1])) { $marcado = true; break; }
                }
            }
            if (!$marcado || ($s[$i + 2][0] ?? null) !== '(') continue;
            $k = fecha($s, $i + 2);
            while ($k < $n && $s[$k][0] !== '{' && $s[$k][0] !== ';') $k++;
            if ($k >= $n || $s[$k][0] !== '{') continue;
            $fim = fecha($s, $k);
            [$A, $M, $pk] = analisa($s, $k + 1, $fim - 1);
            $out[] = [$s[$i + 1][2], 'teste ' . $nome, $A, $M, false, $pk];
        }
    }
    return $out;
}

$args = array_slice($argv, 1);
if (!$args) uso();
$arquivos = [];
foreach ($args as $a) {
    if ($a === '-h' || $a === '--help') uso();
    if (str_starts_with($a, '--')) uso("opção desconhecida: $a");
    $a = str_replace('\\', '/', $a);
    if (is_dir($a)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($a, FilesystemIterator::SKIP_DOTS));
        $achou = [];
        foreach ($it as $f) if (str_ends_with($f->getFilename(), '.php')) $achou[] = str_replace('\\', '/', $f->getPathname());
        sort($achou);
        array_push($arquivos, ...$achou);
        continue;
    }
    if (!is_file($a)) uso("arquivo não encontrado: $a");
    $arquivos[] = $a;
}

$fracoHttp = ['assertok', 'assertsuccessful'];
$fracoBrowser = ['assertnojavascripterrors', 'assertnosmoke'];
$tipo = ['tobeint', 'tobestring', 'tobearray', 'tobebool', 'tobefloat', 'tobenumeric', 'tobeiterable', 'tobeobject', 'tobecallable', 'tobescalar'];
$linhas = [];
foreach ($arquivos as $f) {
    $src = file_get_contents($f);
    if ($src === false) uso("não foi possível ler: $f");
    foreach (testes(sig(token_get_all($src))) as [$linha, $rotulo, $A, $M, $throws, $pk]) {
        $msg = null;
        if (!$throws && !$M) {
            if (!$A) $msg = 'sem nenhuma assertion reconhecida (asserção dentro de helper não é seguida: conferir o helper)';
            elseif (!array_diff($A, $fracoHttp)) $msg = 'assertOk()/assertSuccessful() como assertion única';
            elseif (!array_diff($A, array_merge($fracoHttp, $fracoBrowser))) $msg = 'assertNoJavaScriptErrors()/assertNoSmoke() como assertion única';
        } elseif (!$throws && !$A) {
            $taut = true;
            $soNeg = true;
            foreach ($M as [$m, $neg]) {
                if (!(($m === 'tothrow' && $neg) || (in_array($m, $tipo, true) && !$neg))) $taut = false;
                if (!$neg) $soNeg = false;
            }
            $lista = implode(', ', array_map(fn ($x) => ($x[1] ? 'not->' : '') . $x[2] . '()', $M));
            if ($taut) $msg = "só asserção de tipo ou \"não lança\" — tautologia ($lista)";
            elseif ($soNeg) $msg = "só expectativa negada ($lista) — nenhum valor esperado";
        }
        if ($msg !== null) $linhas[] = [$f, $linha, "$f:$linha: $rotulo — $msg"];
        foreach ($pk as [$l, $txt]) $linhas[] = [$f, $l, "$f:$l: $rotulo — $txt"];
    }
}
usort($linhas, fn ($x, $y) => [$x[0], $x[1]] <=> [$y[0], $y[1]]);
foreach ($linhas as $l) echo $l[2], "\n";
exit($linhas ? 1 : 0);
PHP

php -r "$PHP_CODE" -- "$@"
exit $?

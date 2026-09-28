#!/usr/bin/env bash
# dark-mode.sh — mecanismo de dark mode do projeto e checagem estática (nível 1) da dimensão G.
#
# Uso (na raiz do projeto Laravel, ou com --raiz):
#   bash {skills}/feature-quality-gate/scripts/dark-mode.sh --mecanismo [--raiz DIR]
#   bash {skills}/feature-quality-gate/scripts/dark-mode.sh [--raiz DIR] <arquivo-do-diff>...
#   <arquivo> relativo vale primeiro contra o diretório corrente e, se não existir lá, contra a --raiz
#   (é como vêm os paths do `git diff --name-only`); a saída mostra o path resolvido. Arquivo que não
#   existe em nenhum dos dois (ex.: apagado no diff) é erro de uso, exit 2 — por isso o `--diff-filter=AM`.
#
# O que confere:
#   --mecanismo  O projeto tem dark mode, e por qual mecanismo? Uma linha por evidência.
#                Evidência (exit 1) = a dimensão G roda e o mecanismo diz como forçar o tema no CT-B.
#                Silêncio (exit 0) = nenhum mecanismo: G é declarada não aplicável, com esta saída como prova.
#                Mecanismos: Tailwind 3 (`darkMode` no tailwind.config.*), Tailwind 4 (`@custom-variant dark`),
#                variante `dark:` sem mecanismo declarado (o Tailwind 3 e o 4 aplicam `prefers-color-scheme`
#                por padrão), `@media (prefers-color-scheme: dark)` no CSS, classe `dark` alternada por JS ou
#                fixa no <html>, `@fluxAppearance` (Flux), painel Filament sem `->darkMode(false)`.
#   <arquivos>   Nível 1: em cada linha, classe de cor neutra (white, black, gray, slate, zinc, neutral,
#                stone) de bg/text/border/divide/ring/placeholder sem par `dark:` do mesmo utilitário na
#                mesma linha; hex fixo em classe arbitrária (`bg-[#fff]`) sem par; hex em `style` inline.
#                Projeto sem mecanismo de dark mode: silêncio e exit 0 (não há par a exigir).
#                Confere .blade.php, .php, .html, .vue, .jsx, .tsx, .js, .ts; ignora as demais extensões.
#
# Quem chama: feature-quality-gate, dimensão G — `--mecanismo` antes de validar; `<arquivos>` no nível 1
#   com os arquivos de `git diff --name-only --diff-filter=AM {base}...HEAD`.
# Contrato: silêncio + exit 0 = OK; achado = `arquivo:linha: mensagem` + exit 1; erro de uso ou de
#   ambiente = mensagem no stderr + exit 2.
# Limites: heurística por linha. Atributo `class` quebrado em várias linhas, cor que vem de variável ou de
#   componente e `text-white` sobre fundo colorido geram falso positivo ou falso negativo — o gate julga
#   cada linha antes de virar achado. Cor que só o olho vê (contraste, ícone que some) é nível 3 (Playwright MCP).
#
# Exemplo de falha:
#   $ bash dark-mode.sh resources/views/livewire/aprovacao.blade.php
#   resources/views/livewire/aprovacao.blade.php:2: sem par dark: na linha — text-gray-900
#   resources/views/livewire/aprovacao.blade.php:3: sem par dark: na linha — hover:bg-gray-100, border-gray-200
#   resources/views/livewire/aprovacao.blade.php:4: cor hex fixa em style inline — não troca com o tema
#   resources/views/livewire/aprovacao.blade.php:5: sem par dark: na linha — bg-[#ffffff]
#   (exit 1)
set -u

if ! command -v php >/dev/null 2>&1; then
  echo "dark-mode.sh: php não encontrado no PATH (o script usa PHP embutido)" >&2
  exit 2
fi

read -r -d '' PHP_CODE <<'PHP'
function uso($msg = null) {
    fwrite(STDERR, ($msg ? "dark-mode.sh: $msg\n" : '')
        . "uso: dark-mode.sh --mecanismo [--raiz DIR]\n"
        . "     dark-mode.sh [--raiz DIR] <arquivo>...\n");
    exit(2);
}

function linhas($f) {
    $c = @file_get_contents($f);
    if ($c === false) return [];
    $out = [];
    foreach (preg_split('/\r\n|\n|\r/', $c) as $i => $l) $out[$i + 1] = $l;
    return $out;
}

function arquivos_em($dir, $exts) {
    if (!is_dir($dir)) return [];
    $filtro = function ($f, $k, $it) {
        if ($it->hasChildren()) return !in_array($f->getFilename(), ['vendor', 'node_modules', 'build', '.git'], true);
        return true;
    };
    $it = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), $filtro));
    $out = [];
    foreach ($it as $f) {
        $p = str_replace('\\', '/', $f->getPathname());
        foreach ($exts as $e) if (str_ends_with($p, $e)) { $out[] = $p; break; }
    }
    sort($out);
    return $out;
}

function detectar($raiz) {
    $j = fn ($p) => $raiz === '.' ? $p : rtrim($raiz, '/') . '/' . $p;
    $ev = [];
    $explicito = false;
    $js = ['.js', '.ts', '.vue', '.jsx', '.tsx'];
    $views = ['.blade.php', '.html'];
    $toggle = '/classList\.(add|toggle|remove)\(\s*[\'"]dark[\'"]/';

    foreach (['tailwind.config.js', 'tailwind.config.cjs', 'tailwind.config.mjs', 'tailwind.config.ts'] as $cfg) {
        $f = $j($cfg);
        if (!is_file($f)) continue;
        foreach (linhas($f) as $n => $l) {
            if (preg_match('/\bdarkMode\s*:\s*(.+?)[\s,]*$/', $l, $m)) {
                $ev[] = "$f:$n: dark mode — Tailwind 3, darkMode: " . trim($m[1]);
                $explicito = true;
            }
        }
    }
    foreach (arquivos_em($j('resources/css'), ['.css']) as $f) {
        foreach (linhas($f) as $n => $l) {
            if (preg_match('/@custom-variant\s+dark\b/', $l)) {
                $ev[] = "$f:$n: dark mode — Tailwind 4, @custom-variant dark (seletor ou classe)";
                $explicito = true;
            } elseif (preg_match('/prefers-color-scheme\s*:\s*dark/', $l)) {
                $ev[] = "$f:$n: dark mode — CSS @media (prefers-color-scheme: dark)";
            }
        }
    }
    foreach (arquivos_em($j('resources/js'), $js) as $f) {
        foreach (linhas($f) as $n => $l) {
            if (preg_match($toggle, $l)) $ev[] = "$f:$n: dark mode — classe dark alternada por JS";
            elseif (str_contains($l, 'prefers-color-scheme')) $ev[] = "$f:$n: dark mode — JS consulta prefers-color-scheme";
        }
    }
    foreach (arquivos_em($j('resources/views'), $views) as $f) {
        foreach (linhas($f) as $n => $l) {
            if (preg_match('/@fluxAppearance\b/', $l)) $ev[] = "$f:$n: dark mode — Flux, @fluxAppearance (classe dark)";
            elseif (preg_match('/<html\b[^>]*class\s*=\s*"[^"]*\bdark\b/', $l)) $ev[] = "$f:$n: dark mode — classe dark fixa no <html>";
            elseif (preg_match($toggle, $l)) $ev[] = "$f:$n: dark mode — classe dark alternada por JS inline";
        }
    }
    foreach (arquivos_em($j('app/Providers'), ['PanelProvider.php']) as $f) {
        $ls = linhas($f);
        if (preg_match('/->darkMode\(\s*false\s*\)/', implode("\n", $ls))) continue;
        $alvo = null;
        foreach ($ls as $n => $l) if (preg_match('/->darkMode\(/', $l)) { $alvo = $n; break; }
        if ($alvo === null) foreach ($ls as $n => $l) if (preg_match('/function\s+panel\s*\(/', $l)) { $alvo = $n; break; }
        if ($alvo !== null) $ev[] = "$f:$alvo: dark mode — Filament, painel sem ->darkMode(false) (classe dark, padrão do Filament)";
    }
    if (!$explicito) {
        foreach (array_merge(arquivos_em($j('resources/views'), $views), arquivos_em($j('resources/js'), $js)) as $f) {
            foreach (linhas($f) as $n => $l) {
                if (preg_match('/(?<![\w-])dark:[\w-]/', $l)) {
                    $ev[] = "$f:$n: dark mode — variante dark: sem darkMode nem @custom-variant (o Tailwind aplica prefers-color-scheme por padrão)";
                    break 2;
                }
            }
        }
    }
    return $ev;
}

$args = array_slice($argv, 1);
$raiz = '.';
$mecanismo = false;
$arquivos = [];
for ($i = 0; $i < count($args); $i++) {
    $a = $args[$i];
    if ($a === '--raiz') {
        if (!isset($args[$i + 1])) uso('--raiz sem diretório');
        $raiz = str_replace('\\', '/', $args[++$i]);
        continue;
    }
    if ($a === '--mecanismo') { $mecanismo = true; continue; }
    if ($a === '-h' || $a === '--help') uso();
    if (str_starts_with($a, '--')) uso("opção desconhecida: $a");
    $arquivos[] = $a;
}
if (!is_dir($raiz)) uso("raiz não é diretório: $raiz");
if ($mecanismo && $arquivos) uso('--mecanismo não recebe arquivos');
if (!$mecanismo && !$arquivos) uso();

$ev = detectar($raiz);
if ($mecanismo) {
    foreach ($ev as $e) echo $e, "\n";
    exit($ev ? 1 : 0);
}
foreach ($arquivos as $i => $f) {
    $f = str_replace('\\', '/', $f);
    // Arquivo relativo que não existe no diretório corrente é procurado sob a --raiz
    // (os paths do `git diff --name-only` são relativos à raiz do projeto).
    if (!is_file($f) && $raiz !== '.' && !preg_match('#^(/|[A-Za-z]:/)#', $f) && is_file(rtrim($raiz, '/') . '/' . $f)) {
        $f = rtrim($raiz, '/') . '/' . $f;
    }
    if (!is_file($f)) uso("arquivo não encontrado: $f");
    $arquivos[$i] = $f;
}
if (!$ev) exit(0);

$exts = ['.blade.php', '.php', '.html', '.vue', '.jsx', '.tsx', '.js', '.ts'];
$reCor = '/(?<![\w:\/\[-])((?:[\w-]+:)*)(bg|text|border(?:-[xytrblse])?|divide|ring|placeholder)-(white|black|(?:gray|slate|zinc|neutral|stone)-(?:50|[1-9]00|950))(?:\/\d{1,3})?(?![\w-])/';
$reHex = '/(?<![\w:\/\[-])((?:[\w-]+:)*)(bg|text|border)-\[#[0-9a-fA-F]{3,8}\]/';
$reStyle = '/style\s*=\s*["\'][^"\']*(?:color|background(?:-color)?|border-color)\s*:\s*#[0-9a-fA-F]{3,8}/i';
$achados = 0;
foreach ($arquivos as $f) {
    $ok = false;
    foreach ($exts as $e) if (str_ends_with($f, $e)) { $ok = true; break; }
    if (!$ok) continue;
    foreach (linhas($f) as $n => $l) {
        $faltam = [];
        foreach ([$reCor, $reHex] as $re) {
            if (!preg_match_all($re, $l, $ms, PREG_SET_ORDER)) continue;
            foreach ($ms as $m) {
                if (str_contains($m[1], 'dark:')) continue;
                $base = explode('-', $m[2])[0];
                if (!preg_match('/(?<![\w-])dark:(?:[\w-]+:)*' . $base . '(?:-[xytrblse])?-/', $l)) $faltam[] = $m[0];
            }
        }
        if ($faltam) { echo "$f:$n: sem par dark: na linha — ", implode(', ', array_unique($faltam)), "\n"; $achados++; }
        if (preg_match($reStyle, $l)) { echo "$f:$n: cor hex fixa em style inline — não troca com o tema\n"; $achados++; }
    }
}
exit($achados ? 1 : 0);
PHP

php -r "$PHP_CODE" -- "$@"
exit $?

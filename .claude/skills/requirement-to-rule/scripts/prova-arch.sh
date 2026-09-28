#!/usr/bin/env bash
# prova-arch.sh — prova que um teste arch() do Pest pega a violação que a rule proíbe.
#
# Uso (na raiz do projeto Laravel, depois de gravar o teste aprovado no prompt único):
#   bash {skills}/requirement-to-rule/scripts/prova-arch.sh <teste-arch> <violacao> <dir-evidencia> < violacao.php
#     <teste-arch>     o teste gravado, ex.: tests/Arch/ModelsTest.php
#     <violacao>       path LIVRE (o script nunca sobrescreve) dentro de um diretório que o teste varre,
#                      ex.: app/Models/ViolacaoControladaArch.php
#     <dir-evidencia>  diretório FORA do repositório; recebe a-atual.txt, b-violacao.txt,
#                      status-antes.txt e status-depois.txt, que o agente cola na apresentação e no commit
#   O conteúdo PHP da violação controlada vem pelo stdin (modelos em references/enforcement-arch.md).
#
# Confere:
#   (0) phpunit.xml (ou phpunit.xml.dist) tem uma suíte com tests/Arch ou tests/ — sem isso o CI não roda o teste;
#       as suítes são lidas pelo DOM do PHP, então <testsuite> dentro de <!-- … --> não conta
#   (a) o teste passa no código atual — falha é violação existente: achado, não rule nova
#   (b) com a violação controlada no lugar, o teste falha e a saída cita o arquivo da violação
#   (c) o arquivo temporário foi removido e `git status --porcelain` ficou idêntico antes e depois de (b)
# Chamado por: requirement-to-rule, passo 6 (Enforcement que roda), para toda rule mecânica aprovada.
# Saída: silêncio + exit 0 = provado; achado = `arquivo:linha: mensagem` por linha + exit 1;
#        erro de uso ou de ambiente = mensagem no stderr + exit 2.
# Requer: bash, git, php (com ext-dom, que o PHPUnit já exige) e vendor/bin/pest. Sem jq, node, python ou grep -P.
#
# Exemplo de falha:
#   (saídas reais, projeto-fixture com Pest 5.2.1, 2026-09-26)
#   $ bash prova-arch.sh tests/Arch/ModelsTest.php app/Models/ViolacaoControladaArch.php "$TMP/ev3" < violacao.php
#   app/Models/Legado.php:5: viola tests/Arch/ModelsTest.php no código atual — violação existente é achado, não rule nova (saída em a-atual.txt)
#   $ echo $?
#   1
#   $ bash prova-arch.sh tests/Arch/ServicesTest.php app/Models/ViolacaoControladaArch.php "$TMP/ev4" < violacao.php
#   app/Models/ViolacaoControladaArch.php:1: a violação controlada não derrubou tests/Arch/ServicesTest.php — o teste não prova a restrição; a rule não pode dizer "enforçado"
#   $ echo $?
#   1
#   (2026-09-27, mesmo Pest; phpunit.xml com a suíte Arch só dentro de <!-- … --> e a suíte Unit em tests/Unit)
#   $ bash prova-arch.sh tests/Arch/ModelsTest.php app/Models/ViolacaoControladaArch.php "$TMP/ev5" < violacao.php
#   phpunit.xml:3: nenhuma suíte inclui tests/Arch nem tests/ — vendor/bin/pest sem argumento não roda o teste; propor <testsuite name="Arch"><directory>tests/Arch</directory></testsuite>
#   $ echo $?
#   1

set -u

uso() {
  echo "uso: prova-arch.sh <teste-arch> <violacao> <dir-evidencia> < violacao.php" >&2
  exit 2
}

[ $# -eq 3 ] || uso
teste=$1
violacao=$2
evid=$3

[ -f vendor/bin/pest ] || { echo "vendor/bin/pest não encontrado: rode na raiz do projeto" >&2; exit 2; }
command -v php >/dev/null 2>&1 || { echo "php não está no PATH" >&2; exit 2; }
raiz=$(git rev-parse --show-toplevel 2>/dev/null) || { echo "fora de um repositório git" >&2; exit 2; }
[ -f "$teste" ] || { echo "$teste não existe: grave o teste aprovado antes da prova" >&2; exit 2; }
[ -e "$violacao" ] && { echo "$violacao já existe: escolha um path livre (o script nunca sobrescreve)" >&2; exit 2; }
dir_viol=$(dirname -- "$violacao")
[ -d "$dir_viol" ] || { echo "$dir_viol não existe: a violação vai num diretório que o teste já varre" >&2; exit 2; }
[ -t 0 ] && { echo "conteúdo da violação ausente: passe o PHP pelo stdin" >&2; exit 2; }
conteudo=$(cat)
[ -n "$conteudo" ] || { echo "conteúdo da violação vazio" >&2; exit 2; }

# Confere que a evidência fica fora do repositório ANTES de criar qualquer coisa.
# Decide pelo git, não pelo texto do path: no Git Bash, `pwd -P` guarda a caixa digitada no `cd`
# e o %TEMP% aparece como /tmp, e a comparação de texto deixava passar evidência dentro do repositório.
pai=$(dirname -- "$evid")
[ -d "$pai" ] || { echo "$pai não existe: crie o diretório pai da evidência" >&2; exit 2; }
alvo=$pai
[ -d "$evid" ] && alvo=$evid
if [ "$(git -C "$alvo" rev-parse --show-toplevel 2>/dev/null)" = "$raiz" ]; then
  echo "$evid está dentro do repositório: use um diretório fora dele" >&2; exit 2
fi
mkdir -p -- "$evid" || { echo "não foi possível criar $evid" >&2; exit 2; }

achou=0
achado() { printf '%s\n' "$1"; achou=1; }

# Converte as linhas "at arquivo:linha" da saída do Pest em arquivo:linha com barras normais.
locais() {
  sed -n 's/^[[:space:]]*at \(.*\):\([0-9][0-9]*\)[[:space:]]*$/\1:\2/p' "$1" | tr '\\' '/' | sort -u
}

# (0) O teste roda no CI? As suítes são lidas pelo DOM do PHP, não por grep: um <testsuite>
# dentro de <!-- … --> não é suíte, e o grep o contava. Imprime vazio (alguma suíte cobre),
# a linha de <testsuites> (nenhuma cobre) ou "erro" (o XML não parseia).
xml=""
for f in phpunit.xml phpunit.xml.dist; do
  if [ -f "$f" ]; then xml=$f; break; fi
done
if [ -z "$xml" ]; then
  achado "$teste:1: sem phpunit.xml nem phpunit.xml.dist — confirmar como o CI roda tests/Arch"
else
  linha=$(php -r '
    $d = new DOMDocument();
    if (!@$d->load($argv[1])) { echo "erro"; exit; }
    foreach ($d->getElementsByTagName("testsuite") as $s) {
      foreach ($s->getElementsByTagName("directory") as $n) {
        $p = rtrim(preg_replace("#^\./#", "", trim($n->textContent)), "/");
        if ($p === "tests" || $p === "tests/Arch") { exit; }
      }
    }
    $t = $d->getElementsByTagName("testsuites")->item(0);
    echo $t ? $t->getLineNo() : 1;
  ' "$xml" 2>/dev/null) || linha=erro
  case $linha in
    "") ;;
    erro) achado "$xml:1: não foi possível ler as suítes (XML inválido ou php sem ext-dom) — confirmar como o CI roda tests/Arch" ;;
    *) achado "$xml:$linha: nenhuma suíte inclui tests/Arch nem tests/ — vendor/bin/pest sem argumento não roda o teste; propor <testsuite name=\"Arch\"><directory>tests/Arch</directory></testsuite>" ;;
  esac
fi

# (a) Código atual.
php vendor/bin/pest "$teste" --colors=never > "$evid/a-atual.txt" 2>&1
rc_a=$?
if [ "$rc_a" -ne 0 ]; then
  lista=$(locais "$evid/a-atual.txt")
  if [ -n "$lista" ]; then
    printf '%s\n' "$lista" | while IFS= read -r l; do
      printf '%s\n' "$l: viola $teste no código atual — violação existente é achado, não rule nova (saída em a-atual.txt)"
    done
    achou=1
  else
    achado "$teste:1: o teste falha no código atual (exit $rc_a) sem apontar arquivo — ler a-atual.txt antes de qualquer rule"
  fi
  exit 1
fi

# (b) Violação controlada, com remoção garantida mesmo em interrupção.
git status --porcelain --untracked-files=all > "$evid/status-antes.txt" 2>&1
limpar() { rm -f -- "$violacao"; }
trap limpar EXIT
trap 'limpar; echo "interrompido: $violacao removido" >&2; exit 2' INT TERM
printf '%s\n' "$conteudo" > "$violacao"
php vendor/bin/pest "$teste" --colors=never > "$evid/b-violacao.txt" 2>&1
rc_b=$?
limpar
git status --porcelain --untracked-files=all > "$evid/status-depois.txt" 2>&1

nome=$(basename -- "$violacao" .php)
if [ "$rc_b" -eq 0 ]; then
  achado "$violacao:1: a violação controlada não derrubou $teste — o teste não prova a restrição; a rule não pode dizer \"enforçado\""
elif ! grep -Fq "$nome" "$evid/b-violacao.txt"; then
  achado "$violacao:1: $teste falhou (exit $rc_b) sem citar $nome — a falha pode ser outra (sintaxe, ambiente); ler b-violacao.txt"
fi

# (c) Árvore intacta.
[ -e "$violacao" ] && achado "$violacao:1: o arquivo temporário continua no disco — remover à mão"
if ! cmp -s "$evid/status-antes.txt" "$evid/status-depois.txt"; then
  achado "$evid/status-depois.txt:1: git status --porcelain mudou durante a prova — comparar com status-antes.txt"
fi

exit "$achou"

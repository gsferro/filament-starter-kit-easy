#!/usr/bin/env bash
# espelho-gh.sh — espelha os tickets de uma wiki no GitHub: uma issue por ticket e, com --project, o
#   item do ticket na coluna do Project que corresponde ao Status. Só quando o usuário pede.
#
# Uso (na raiz do projeto):
#   bash {skills}/feature-tickets/scripts/espelho-gh.sh <wiki> [opções]             DRY-RUN (padrão)
#   bash {skills}/feature-tickets/scripts/espelho-gh.sh <wiki> --aplicar [opções]   executa
#     <wiki> = wikis/specs/{branch}/{feature}
#   Opções (cada uma também por variável de ambiente; o argumento vence a variável):
#     --repo DONO/REPO          FT_GH_REPO      repositório das issues (padrão: o do diretório atual)
#     --project N               FT_GH_PROJECT   número do Project (sem ele: só as issues, sem coluna)
#     --owner LOGIN             FT_GH_OWNER     dono do Project (padrão: @me)
#     --campo NOME              FT_GH_CAMPO     campo single select das colunas (padrão: Status)
#     --coluna STATUS=COLUNA    FT_GH_COL_PRONTO, FT_GH_COL_EXECUCAO, FT_GH_COL_REVISAO, FT_GH_COL_CONCLUIDO
#       STATUS = pronto | execucao | revisao | concluido (com ou sem acento, com ou sem "em ").
#       Padrão: pronto=Todo · execucao=In Progress · revisao=In Progress · concluido=Done — as opções
#       do campo Status do modelo de Project do GitHub; quem tem coluna "In Review" passa revisao=In Review.
#
# O que faz: roda o indice.sh --check da mesma pasta (achado = não espelha); lê os tickets em ordem de
#   número e, para cada um: sem **Issue** → gh issue create (título "NN: {entrega}", corpo com Entrega,
#   RQ, CT, CT-B, Costura e uma linha "Blocked by #N" por bloqueador) e grava "**Issue**: #N" no ticket,
#   logo abaixo do **Status**, com o fim de linha do arquivo (CRLF continua CRLF); com **Issue** → não
#   cria de novo. Com --project: gh project item-add (se
#   o item já existe, o GitHub devolve o mesmo) e gh project item-edit com a opção da coluna. Rodar de
#   novo não duplica nada. Nunca passa --label (label de estado dispara automação: ready-for-agent), não
#   fecha issue e não lê nada de volta: o arquivo do ticket é a fonte, a issue é espelho de mão única.
# DRY-RUN: imprime os comandos gh que rodaria, com os ids do Project como {marcadores}; não chama o gh,
#   não usa rede, não grava nada. --aplicar: exige gh no PATH e `gh auth status` com exit 0.
# Sintaxe do gh conferida em 2026-09-27 no manual oficial (cli.github.com/manual: gh_issue_create,
#   gh_project_item-add, gh_project_item-edit, gh_project_field-list) e no gh 2.83.0 local. O item-edit
#   usa a forma por id (--id, --project-id, --field-id, --single-select-option-id), que existe nas
#   versões antigas e na atual; --field/--value por nome só nas recentes.
#
# Quem chama: a feature-tickets, só quando o usuário pede o espelho (nunca por conta própria).
# Contrato: dry-run = os comandos + exit 0; --aplicar = uma linha por ticket + exit 0. Achado do
#   --check = as linhas `arquivo:linha: mensagem` dele + exit 1. Erro de uso, de ambiente ou do gh =
#   mensagem no stderr + exit 2 (issue já criada ficou gravada no ticket: rodar de novo continua dali).
# Requer: bash e php no PATH (PHP embutido); gh só no --aplicar. Sem jq, node, python ou grep -P (os
#   filtros de JSON são o --jq embutido no próprio gh).
#
# Exemplo de falha (saída real, fixture de 2026-09-27):
#   $ bash espelho-gh.sh wikis/specs/ferro/501/aprov --project 7
#   wikis/specs/ferro/501/aprov/07-tickets/01-solicitante-envia.md:13: **Issue** fora do formato (#N, dono/repo#N ou URL da issue): "41" — o campo é gravado pelo espelho-gh.sh
#   espelho-gh.sh: o indice.sh --check tem achado — corrija os tickets antes de espelhar      (stderr)
#   (exit 1)
set -u

uso() {
  echo "uso: espelho-gh.sh <wiki> [--aplicar] [--repo DONO/REPO] [--project N] [--owner LOGIN] [--campo NOME] [--coluna STATUS=COLUNA]..." >&2
  echo "     sem --aplicar: DRY-RUN, só imprime os comandos gh" >&2
  exit 2
}

[ $# -ge 1 ] || uso
wiki=${1//\\//}
wiki=${wiki%/}
shift
case "$wiki" in -*|'') uso ;; esac

aplicar=0
repo=${FT_GH_REPO-}
proj=${FT_GH_PROJECT-}
dono=${FT_GH_OWNER:-@me}
campo=${FT_GH_CAMPO:-Status}
col_pronto=${FT_GH_COL_PRONTO:-Todo}
col_execucao=${FT_GH_COL_EXECUCAO:-In Progress}
col_revisao=${FT_GH_COL_REVISAO:-In Progress}
col_concluido=${FT_GH_COL_CONCLUIDO:-Done}
while [ $# -gt 0 ]; do
  case "$1" in
    --aplicar) aplicar=1; shift ;;
    --repo|--project|--owner|--campo|--coluna)
      [ $# -ge 2 ] && [ -n "$2" ] || uso
      case "$1" in
        --repo) repo=$2 ;;
        --project) proj=$2 ;;
        --owner) dono=$2 ;;
        --campo) campo=$2 ;;
        --coluna)
          chave=${2%%=*}; valor=${2#*=}
          [ "$chave" != "$2" ] && [ -n "$valor" ] || uso
          chave=${chave#em }
          case "$chave" in
            pronto) col_pronto=$valor ;;
            execucao|execução) col_execucao=$valor ;;
            revisao|revisão) col_revisao=$valor ;;
            concluido|concluído) col_concluido=$valor ;;
            *) echo "espelho-gh.sh: status desconhecido em --coluna: $chave (pronto | execucao | revisao | concluido)" >&2; exit 2 ;;
          esac ;;
      esac
      shift 2 ;;
    *) uso ;;
  esac
done
case "$proj" in ''|*[!0-9]*) [ -z "$proj" ] || { echo "espelho-gh.sh: --project precisa ser o número do Project: $proj" >&2; exit 2; } ;; esac
case "$repo" in ''|*/*) ;; *) echo "espelho-gh.sh: --repo no formato DONO/REPO: $repo" >&2; exit 2 ;; esac

command -v php >/dev/null 2>&1 || { echo "espelho-gh.sh: php não está no PATH (o script usa PHP embutido)" >&2; exit 2; }
[ -d "$wiki/07-tickets" ] || { echo "espelho-gh.sh: $wiki/07-tickets/ não existe — a feature não foi fatiada; nada a espelhar" >&2; exit 2; }
ind="$(dirname "$0")/indice.sh"
[ -f "$ind" ] || { echo "espelho-gh.sh: indice.sh não encontrado ao lado deste script ($ind)" >&2; exit 2; }

# 1. os tickets têm de estar consistentes: um tema, um script — a conferência é do indice.sh
saida=$(bash "$ind" --check "$wiki")
rc=$?
if [ "$rc" -eq 1 ]; then
  printf '%s\n' "$saida"
  echo "espelho-gh.sh: o indice.sh --check tem achado — corrija os tickets antes de espelhar" >&2
  exit 1
fi
[ "$rc" -eq 0 ] || exit 2

if [ "$aplicar" = 1 ]; then
  command -v gh >/dev/null 2>&1 || { echo "espelho-gh.sh: gh (GitHub CLI) não está no PATH — https://cli.github.com" >&2; exit 2; }
  gh auth status >/dev/null 2>&1 || { echo "espelho-gh.sh: gh auth status falhou — rode gh auth login e tente de novo" >&2; exit 2; }
fi

dir=$(mktemp -d "${TMPDIR:-/tmp}/espelho-gh.XXXXXX") || { echo "espelho-gh.sh: mktemp falhou" >&2; exit 2; }
trap 'rm -rf "$dir"' EXIT
dp=$dir
if command -v cygpath >/dev/null 2>&1; then dp=$(cygpath -m "$dir"); fi

# 2. plano: uma linha por ticket (NN, arquivo, título, status, issue, bloqueadores) + o corpo em $dir/NN.corpo
read -r -d '' PHP_CODE <<'PHP'
error_reporting(E_ALL);
ini_set('display_errors', 'stderr');
[$wiki, $dir] = [$argv[1], $argv[2]];
$arqs = glob("$wiki/07-tickets/*.md") ?: [];
sort($arqs, SORT_STRING);
foreach ($arqs as $a) {
    $a = str_replace('\\', '/', $a);
    if (basename($a) === 'README.md' || !preg_match('/^(\d{2})-/', basename($a), $m)) continue;
    $num = $m[1];
    $c = [];
    $titulo = basename($a, '.md');
    foreach (preg_split('/\r\n|\n|\r/', preg_replace('/^\xEF\xBB\xBF/', '', (string) file_get_contents($a))) as $l) {
        if (preg_match('/^#\s+\d{2}\s*:\s*(\S.*)$/u', $l, $mm) && $titulo === basename($a, '.md')) $titulo = trim(str_replace(['*', '`'], '', $mm[1]));
        if (preg_match('/^\s*\*\*([^*]+?)\s*:?\s*\*\*\s*:?\s*(.*)$/u', $l, $mm) && !isset($c[trim($mm[1])])) $c[trim($mm[1])] = trim($mm[2]);
    }
    $st = preg_match('/^(pronto|em execução|em revisão|concluído)/u', $c['Status'] ?? '', $mm) ? $mm[1] : '';
    $bl = preg_replace('/\[([^\]]*)\]\([^)]*\)/', '$1', $c['Bloqueado por'] ?? '');
    preg_match_all('/\bQ\d+\b/', $bl, $qq);
    preg_match_all('/\b\d{1,3}\b/', preg_replace('/\b(?:Q|RQ-|P-|CT-B?)\d+\b/', '', $bl), $nn);
    $bloq = array_values(array_unique(array_map(function ($x) { return sprintf('%02d', (int) $x); }, $nn[0])));
    $corpo = "Espelho de `$a` — o arquivo do ticket é a fonte; esta issue não é sincronizada de volta.\n\n";
    foreach (['Entrega', 'RQ cobertas', 'CT que ficam verdes', 'CT-B', 'Costura'] as $k) $corpo .= "**$k**: " . ($c[$k] ?? '—') . "\n";
    $corpo .= "\n";
    foreach ($bloq as $b) $corpo .= "Blocked by @@$b@@\n";
    foreach (array_unique($qq[0]) as $q) $corpo .= "Blocked by $q — pergunta ao solicitante, aberta no 00-requisito.md (sem issue)\n";
    file_put_contents("$dir/$num.corpo", rtrim($corpo) . "\n");
    echo implode("\x1f", [$num, $a, str_replace("\x1f", ' ', $titulo), $st, trim(str_replace(['*', '`'], '', $c['Issue'] ?? '')), implode(',', $bloq)]), "\n";
}
PHP
plano=$(php -r "$PHP_CODE" -- "$wiki" "$dp") || { echo "espelho-gh.sh: php falhou ao ler os tickets" >&2; exit 2; }

q() { local s=$1; printf "'%s'" "${s//\'/\'\\\'\'}"; }
coluna_de() {
  case "$1" in
    pronto) printf '%s' "$col_pronto" ;;
    'em execução') printf '%s' "$col_execucao" ;;
    'em revisão') printf '%s' "$col_revisao" ;;
    concluído) printf '%s' "$col_concluido" ;;
  esac
}
repo_arg=
[ -n "$repo" ] && repo_arg=" --repo $(q "$repo")"
refs=  # "NN=ref" por linha: issue conhecida de cada ticket
ref_de() { printf '%s\n' "$refs" | sed -n "s/^$1=//p" | head -n 1; }
corpo_de() { # NN [dry] — troca @@NN@@ pela issue do bloqueador
  local c b r
  c=$(cat "$dir/$1.corpo")
  for b in $(printf '%s' "$c" | sed -n 's/^Blocked by @@\([0-9]*\)@@$/\1/p'); do
    r=$(ref_de "$b")
    [ -n "$r" ] || r="#{issue do ticket $b}"
    c=${c//@@$b@@/$r}
  done
  printf '%s\n' "$c"
}
grava_issue() { # arquivo ref — "**Issue**: ref" logo abaixo do **Status**, no fim de linha do arquivo
  # BINMODE=3: o gawk do Git Bash (MSYS) tira o \r na leitura e regravaria o ticket CRLF em LF; os
  # outros awks ignoram a variável e não tiram o \r
  awk -v BINMODE=3 -v ref="$2" '{ print } !feito && /^\*\*Status\*\*/ { cr = ($0 ~ /\r$/) ? "\r" : ""; print "**Issue**: " ref cr; feito = 1 }' "$1" > "$1.espelho" && mv "$1.espelho" "$1"
}
url_de() { # ref → comando que imprime a URL da issue
  case "$1" in
    https://*) printf 'echo %s' "$(q "$1")" ;;
    */*'#'*) printf 'gh issue view %s --repo %s --json url --jq .url' "${1##*#}" "$(q "${1%%#*}")" ;;
    *) printf 'gh issue view %s%s --json url --jq .url' "${1#\#}" "$repo_arg" ;;
  esac
}

jq_campo=".fields[] | select(.name==\"$campo\") | .id"
jq_opcao() { printf '.fields[] | select(.name=="%s") | .options[] | select(.name=="%s") | .id' "$campo" "$1"; }
ntk=$(printf '%s\n' "$plano" | grep -c .)

if [ "$aplicar" = 0 ]; then
  echo "# espelho-gh.sh — DRY-RUN: nada foi executado. Para executar, o mesmo comando com --aplicar."
  echo "# wiki: $wiki · $ntk tickets · repo: ${repo:-o do diretório atual}$([ -n "$proj" ] && echo " · project $proj (dono $dono, campo $campo)" || echo ' · sem --project: só as issues')"
  [ -n "$proj" ] && echo "# colunas: pronto → $col_pronto · em execução → $col_execucao · em revisão → $col_revisao · concluído → $col_concluido"
  echo "# nenhuma label: label de estado dispara automação (ready-for-agent); a issue é espelho de mão única."
  echo "gh auth status"
  if [ -n "$proj" ]; then
    echo
    echo "# ids do Project, resolvidos uma vez no --aplicar"
    echo "gh project view $proj --owner $(q "$dono") --format json --jq .id"
    echo "gh project field-list $proj --owner $(q "$dono") --format json --jq $(q "$jq_campo")"
    for c in "$col_pronto" "$col_execucao" "$col_revisao" "$col_concluido"; do printf '%s\n' "$c"; done | awk '!v[$0]++' | while IFS= read -r c; do
      echo "gh project field-list $proj --owner $(q "$dono") --format json --jq $(q "$(jq_opcao "$c")")"
    done
  fi
fi

# separador \037: tab é espaço para o IFS, e o campo vazio (ticket sem **Issue**) sumiria
while IFS="$(printf '\037')" read -r num arq titulo st issue bloq; do
  [ -n "$num" ] || continue
  col=$(coluna_de "$st")
  if [ "$aplicar" = 0 ]; then
    echo
    if [ -n "$issue" ]; then
      echo "# $num — $titulo · $st$([ -n "$proj" ] && echo " → $col") · já tem a issue $issue: não cria de novo"
    else
      echo "# $num — $titulo · $st$([ -n "$proj" ] && echo " → $col") · sem issue: cria e grava **Issue** no ticket"
      echo "gh issue create$repo_arg --title $(q "$num: $titulo") --body-file - <<'CORPO'"
      corpo_de "$num"
      echo "CORPO"
      echo "# grava \"**Issue**: #{número}\" em $arq, abaixo do **Status**"
      issue="#{issue do ticket $num}"
    fi
    refs="$refs$num=$issue"$'\n'
    if [ -n "$proj" ]; then
      case "$issue" in
        '#{'*) echo "gh project item-add $proj --owner $(q "$dono") --url {URL impressa pelo gh issue create} --format json --jq .id" ;;
        *) echo "gh project item-add $proj --owner $(q "$dono") --url \"\$($(url_de "$issue"))\" --format json --jq .id" ;;
      esac
      echo "gh project item-edit --id {id do item do ticket $num} --project-id {id do Project $proj} --field-id {id do campo $campo} --single-select-option-id {id da opção \"$col\"}"
    fi
    continue
  fi

  # --aplicar
  export MSYS_NO_PATHCONV=1
  criada=
  if [ -z "$issue" ]; then
    set -- issue create --title "$num: $titulo" --body-file -
    [ -n "$repo" ] && set -- "$@" --repo "$repo"
    url=$(corpo_de "$num" | gh "$@") || { echo "espelho-gh.sh: gh issue create falhou no ticket $num" >&2; exit 2; }
    url=$(printf '%s\n' "$url" | grep -E '^https://' | tail -n 1)
    n=${url##*/}
    case "$n" in ''|*[!0-9]*) echo "espelho-gh.sh: gh issue create não devolveu a URL da issue no ticket $num" >&2; exit 2 ;; esac
    if [ -n "$repo" ]; then issue="$repo#$n"; else issue="#$n"; fi
    grava_issue "$arq" "$issue" || { echo "espelho-gh.sh: issue $issue criada, mas não consegui gravar **Issue** em $arq — grave à mão antes de rodar de novo" >&2; exit 2; }
    criada=" (criada)"
  fi
  refs="$refs$num=$issue"$'\n'
  if [ -n "$proj" ]; then
    if [ -z "${pid-}" ]; then
      pid=$(gh project view "$proj" --owner "$dono" --format json --jq .id </dev/null) && [ -n "$pid" ] || { echo "espelho-gh.sh: Project $proj de $dono não encontrado" >&2; exit 2; }
      fid=$(gh project field-list "$proj" --owner "$dono" --format json --jq "$jq_campo" </dev/null) && [ -n "$fid" ] || { echo "espelho-gh.sh: o Project $proj não tem o campo \"$campo\"" >&2; exit 2; }
    fi
    oid=$(gh project field-list "$proj" --owner "$dono" --format json --jq "$(jq_opcao "$col")" </dev/null) && [ -n "$oid" ] || { echo "espelho-gh.sh: o campo \"$campo\" do Project $proj não tem a opção \"$col\" — ajuste com --coluna" >&2; exit 2; }
    [ -n "${url-}" ] && [ -n "$criada" ] || url=$(eval "$(url_de "$issue")" </dev/null) || { echo "espelho-gh.sh: não achei a URL da issue $issue (ticket $num)" >&2; exit 2; }
    item=$(gh project item-add "$proj" --owner "$dono" --url "$url" --format json --jq .id </dev/null) && [ -n "$item" ] || { echo "espelho-gh.sh: gh project item-add falhou no ticket $num" >&2; exit 2; }
    gh project item-edit --id "$item" --project-id "$pid" --field-id "$fid" --single-select-option-id "$oid" </dev/null >/dev/null || { echo "espelho-gh.sh: gh project item-edit falhou no ticket $num" >&2; exit 2; }
    echo "$num: issue $issue$criada · coluna $col"
  else
    echo "$num: issue $issue$criada"
  fi
  url=
done <<EOF
$plano
EOF
exit 0

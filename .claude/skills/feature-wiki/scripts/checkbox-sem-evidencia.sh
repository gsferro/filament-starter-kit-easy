#!/usr/bin/env bash
# checkbox-sem-evidencia.sh — checkbox fechado sem evidência inline no 03-progresso.md.
#
# Uso (na raiz do projeto):
#   bash {skills}/feature-wiki/scripts/checkbox-sem-evidencia.sh <wiki>
#     <wiki> = wikis/specs/{branch}/{feature} (a branch pode ter barra: ferro/501)
#
# O que confere: toda linha `- [x]` (ou `* [x]`, `[X]`, com recuo) do <wiki>/03-progresso.md
#   precisa trazer ` — {evidência}` — travessão (U+2014) entre espaços, seguido de texto. É o formato
#   `- [x] {item} — {evidência}, {data}` do SKILL.md (Arquivo 03, step 10 item 1). Hífen ou `--` no
#   lugar do travessão não conta. Comentário HTML (<!-- … -->) e bloco de código cercado (```) não são
#   conferidos: é onde o template guarda exemplos.
# O que NÃO confere: se a evidência é verdadeira (reproduzir o número é a L6 do feature-quality-gate),
#   nem se o travessão está no texto do item em vez de numa evidência — por isso o template do 03 não
#   usa " — " no texto dos itens.
#
# Quem chama: feature-wiki step 10 (item 1 da reconciliação, antes da Verificação Final);
#   feature-quality-gate, dimensão L6 (alegações do 03).
# Contrato: silêncio + exit 0 = OK; achado = uma linha `arquivo:linha: mensagem` + exit 1;
#   erro de uso ou de ambiente = mensagem no stderr + exit 2.
# Requer: bash e awk. Sem jq, node, python ou grep -P.
#
# Exemplo de falha (saída real, fixture de 2026-09-27):
#   $ bash checkbox-sem-evidencia.sh wikis/specs/ferro/501/aprovacao-pedido
#   wikis/specs/ferro/501/aprovacao-pedido/03-progresso.md:7: [x] sem " — {evidência}": Policy de aprovação registrada
#   wikis/specs/ferro/501/aprovacao-pedido/03-progresso.md:15: [x] sem " — {evidência}": `vendor/bin/pest --parallel --tia` -- 412/412, 2026-09-27
#   (exit 1)
set -u

if [ $# -ne 1 ] || [ -z "$1" ]; then
  echo "uso: checkbox-sem-evidencia.sh <wiki>   (wiki = wikis/specs/{branch}/{feature})" >&2
  exit 2
fi
command -v awk >/dev/null 2>&1 || { echo "checkbox-sem-evidencia.sh: awk não está no PATH" >&2; exit 2; }

wiki=${1//\\//}
wiki=${wiki%/}
arq="$wiki/03-progresso.md"
[ -d "$wiki" ] || { echo "checkbox-sem-evidencia.sh: $wiki não é um diretório" >&2; exit 2; }
[ -f "$arq" ] || { echo "checkbox-sem-evidencia.sh: $arq não existe — sem 03 não há o que conferir" >&2; exit 2; }

LC_ALL=C awk -v arq="$arq" '
  BEGIN { cerca = 0; coment = 0; achou = 0 }
  {
    linha = $0
    sub(/\r$/, "", linha)
    if (linha ~ /^[ \t]*(```|~~~)/) { cerca = !cerca; next }
    if (cerca) next
    if (coment) {
      if (index(linha, "-->") == 0) next
      linha = substr(linha, index(linha, "-->") + 3)
      coment = 0
    }
    while (match(linha, /<!--.*-->/)) linha = substr(linha, 1, RSTART - 1) substr(linha, RSTART + RLENGTH)
    if (index(linha, "<!--") > 0) { linha = substr(linha, 1, index(linha, "<!--") - 1); coment = 1 }
    if (linha !~ /^[ \t]*[-*+][ \t]+\[[xX]\]/) next
    sep = " \342\200\224 "
    p = index(linha, sep)
    if (p > 0 && substr(linha, p + length(sep)) ~ /[^ \t]/) next
    item = linha
    sub(/^[ \t]*[-*+][ \t]+\[[xX]\][ \t]*/, "", item)
    printf "%s:%d: [x] sem \" \342\200\224 {evidência}\": %s\n", arq, NR, item
    achou = 1
  }
  END { exit achou }
' "$arq"

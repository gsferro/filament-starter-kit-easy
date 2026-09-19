#!/usr/bin/env bash
# Conferência mecânica dos achados que o ciclo 3 deixou abertos.
# Uma linha por achado. PASS/FAIL sai do comando, não da minha memória.
cd "D:/PROJECTS/PACOTES/FILAMENTS/STARTER-KIT-EASY/wt-url-sem-public" || exit 1
D="wikis/specs/fix/url-sem-prefixo-public/url-sem-prefixo-public"

ok() { printf "  PASS  %s\n" "$1"; }
no() { printf "  FAIL  %s\n" "$1"; FALHOU=1; }
FALHOU=0

# QA-12 — citação arquivo:linha de vendor, exigida por .ai/rules/specs.md
if grep -qE 'vendor/[a-z-]+/[a-z-]+/[^ ]+\.php:[0-9]+' "$D"/0[124]-*.md; then
  ok "QA-12  a wiki cita vendor com arquivo:linha"
else
  no "QA-12  nenhuma citação vendor:linha na wiki"
fi

# QA-13 — o checkbox de falsificabilidade não pode citar o desenho anterior
if grep -q "desenho anterior" "$D/03-progresso.md"; then
  no "QA-13  o 03 ainda credita mutantes do desenho anterior"
else
  ok "QA-13  o 03 não cita mais o desenho anterior"
fi

# QA-14 — o Índice não pode creditar M7 a CT-01/CT-05 (quem o mata é CT-08)
if grep -qE '^\| CT-0[15] \|.*M7' "$D/04-casos-de-teste.md"; then
  no "QA-14  o Índice ainda credita M7 a CT-01/CT-05"
else
  ok "QA-14  os créditos do Índice batem com os mutantes"
fi

# QA-26 — o passo 1 do 01 precisa descrever a evidência positiva
if grep -q "sem declaração, só encurta se o" "$D/01-plano-acao.md"; then
  ok "QA-26  o passo 1 descreve a evidência positiva"
else
  no "QA-26  o passo 1 ainda omite a evidência positiva"
fi

# QA-27 — o crédito de M17/M18 tem de estar separado, com o número medido
if grep -q "medido: 2 vermelhos" "$D/04-casos-de-teste.md" && grep -q "medido: 4 vermelhos" "$D/04-casos-de-teste.md"; then
  ok "QA-27  M17 e M18 creditados separados, com o número medido"
else
  no "QA-27  o crédito de M17/M18 continua conflado"
fi

# QA-28 — Gherkin e dataset com o mesmo número de linhas
GHERKIN=$(sed -n '/Esquema do Cenário: \[CT-16\]/,/^```/p' "$D/04-casos-de-teste.md" | grep -cE '^\s+\| (true|false|1|0|on|off) ')
TESTE=$(sed -n '/\[CT-16\]/,/^]);/p' tests/Kit/BooleanoDoEnvTest.php | grep -cE '^\s+\[')
if [ "$GHERKIN" -eq "$TESTE" ]; then
  ok "QA-28  CT-16: $GHERKIN Exemplos = $TESTE linhas de dataset"
else
  no "QA-28  CT-16: $GHERKIN Exemplos != $TESTE linhas de dataset"
fi

# QA-29 — o parêntese do cabeçalho não pode atribuir o recount só ao ciclo 2
if grep -q "recontado duas vezes" "$D/04-casos-de-teste.md"; then
  ok "QA-29  o cabeçalho atribui os dois recounts"
else
  no "QA-29  o cabeçalho ainda atribui o recount ao ciclo errado"
fi

# QA-09 — a ADR-05 declara que revisa a ADR-02
if grep -q "ADR-02\*\* (o gatilho" "$D/02-decisoes-arquiteturais.md"; then
  ok "QA-09  ADR-05 declara revisar a ADR-02"
else
  no "QA-09  ADR-05 não declara revisar a ADR-02"
fi

echo
[ "$FALHOU" -eq 0 ] && echo "TODOS PASSARAM" || echo "HÁ FALHAS — não declarar fechado"
exit "$FALHOU"

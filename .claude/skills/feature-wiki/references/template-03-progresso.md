> Referência da feature-wiki 4.0.0. Lida em: step 4 (antes de escrever o `03-progresso.md`), step 7 (`## Testes`), step 8 (`## Tickets`), step 9 (`## Revisão do Diff (step 9)`), step 12 (`## Candidatos a Rule`) e ao preencher `**Estado**`, `## Despachos`, `## Conformidade com Rules`, `## Quality Gate` e `## Referências Abertas`. Fonte única de: template do `03-progresso.md` (o formato de `## Tickets` é da `feature-tickets`).

# Template do `03-progresso.md`

A regra da evidência inline, as duas seções que só existem para os steps 10 e 11, a linha `**Estado**`
e a validação de espelho ficam no corpo do `SKILL.md`, seção *Arquivo 03*.

**Template `03-progresso.md`**:
```markdown
# Progresso — {Card}

**Estado**: em planejamento
<!-- Uma linha só, no topo: em planejamento | em implementação | em revisão | concluída — {YYYY-MM-DD}.
     Step 4 → "em planejamento"; início da implementação → "em implementação"; step 9 → "em revisão";
     step 11, depois do veredito e antes de regenerar o INDEX.md → "concluída — {data}".
     O indice.sh da feature-tickets lê esta linha para a coluna 03 do wikis/specs/INDEX.md. -->

## {Seção 1 do Plano}
- [ ] {Item 1}
- [ ] {Item 2}

## {Seção 2 do Plano}
- [ ] {Item 1}

## Testes
<!-- Preenchida no step 7, depois da derivação do 04/05: um arquivo de teste por linha, com os IDs que ele cobre. -->
- [ ] `{NomeDoTesteTest}` (CT-01, CT-02, CT-03)
- [ ] `tests/Browser/{Feature}/{Nome}Test.php` (CT-B01, CT-B02) <!-- só se houver 05-*-browser.md -->

## Tickets
<!-- Step 8. Sem fatiamento: uma linha com os sinais conferidos (os números calibram o limiar do step 8).
     Com fatiamento: a feature-tickets escreve aqui "Fatiamento confirmado: …" e uma linha por ticket,
     espelhando o Status do arquivo do ticket. O formato é da feature-tickets e não se redefine aqui:
     {skills}/feature-tickets/references/template-ticket.md, seção "Seção ## Tickets do 03".
     Fatiada depois de registrado "Não fatiado — …": a feature-tickets substitui esta linha. -->
Não fatiado — {YYYY-MM-DD}: {n} RQ vigentes, {n} CT, compactação: {sim | não}, {n} perguntas de requisito — {nenhum sinal | sugestão recusada: {motivo}}

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/pest --filter={Feature} --compact`
- [ ] `vendor/bin/pest tests/Browser --filter={Feature}` <!-- se houver CT-B -->
- [ ] `vendor/bin/pest --parallel --tia`: nada mais no suite quebrou além da **baseline** de `{base}` (falhas pré-existentes por nome)
- [ ] `pest --mutate --path={classe}`: score, duração e sobreviventes listados (no Windows, via `{skills}/feature-wiki/scripts/pestw.cmd`)
- [ ] **Custo medido**: queries do caminho principal × do caminho filtrado, contra o `## Modelo de Execução`, com N acima da página
- [ ] **`/code-review high {base}...HEAD` + passe de eixos (step 9)**, antes da reconciliação: achados fechados (`P-nn` → CT → correção; o que viola `RQ` existente, CT com `Origem` = `RQ-nn` → correção) ou rejeitados com motivo
- [ ] Roteiro "Desenhado × Implementado" do `05-*-browser.md` preenchido <!-- se houver CT-B -->
- [ ] Desvios propagados ao `01`/`02`/`04`/`05` de origem, marcados `*(alterado em …)*`
- [ ] `rastreabilidade.sh {wiki}` silencioso (`RQ`/`P-nn` × passo do `01` × CT do `04`)
- [ ] `checkbox-sem-evidencia.sh {wiki}` silencioso
- [ ] Citações `arquivo:símbolo:linha` reverificadas: `citacoes.sh {wiki}` silencioso
- [ ] IDs `[CT-nn]` do teste ⊆ `04`/`05` e vice-versa: `ids-ct.sh {wiki} 'tests/**/{Feature}/*.php'` silencioso
- [ ] Rules casadas pelo diff com linha em `## Conformidade com Rules`: `conformidade-rules.sh {wiki} {base}` silencioso
- [ ] Alocação a ticket: `indice.sh --check {wiki}` silencioso <!-- só com 07-tickets/ -->
- [ ] Falsificabilidade dos CTs novos: quantos falham sem o fix; os demais "não falsificável nesta pilha", com motivo
- [ ] Docs pt/en, CHANGELOG e README reconciliados com o comportamento final
- [ ] `git commit`

<!-- Cada [x] acima leva " — {evidência}, {data}". Ex.: `- [x] composer test:kit — 677/677, 2026-09-05`
     O texto do item não leva " — ": o travessão separa a evidência, e é ele que o checkbox-sem-evidencia.sh procura.
     Evidência com NÚMERO leva o comando que o gerou (`grep -c …`); a de script, o comando e a saída (vazia = OK, exit 0). Degradação
     declarada ("sem PCOV", "plugin ausente") leva a PROVA NEGATIVA (`php -m`, `ls vendor/…`). -->

## Revisão do Diff (step 9)

<!-- Um achado por linha, dos dois passes (/code-review e fw-revisor-diff), confirmados e rejeitados.
     A dimensão I do feature-quality-gate lê daqui o que o step 9 já cobriu. -->

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|
| RD-01 | eixos | {…} | premissa → CT → correção | P-03 / CT-81 | — |
| RD-02 | eixos | {…} | teste (viola RQ-02) → CT → correção | — / CT-82 | — |
| CR-01 | `/code-review` | {…} | — | — | {motivo} |

## Conformidade com Rules

<!-- Uma linha por rule de .ai/rules/ cujo paths: casa com um arquivo do diff (conformidade-rules.sh acusa a que falta).
     "violada" = blocker do PR. -->

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `auth.md` — cobrir `fi-auth-layout` em par | `app/Filament/Pages/Auth/**` | aplicada | CT-07 + CT-38 |

## Quality Gate

<!-- Preenchido no step 11. Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

- **Ciclo**: {n} · **Veredito**: {APROVADO | APROVADO COM DÉBITO | REPROVADO → destino | NÃO APLICÁVEL} · **Data**: {YYYY-MM-DD}
<!-- NÃO APLICÁVEL é do orquestrador ("Quando pular" do step 11): a sessão grava o 06 mínimo sem rodar o gate.
     Nos perfis Mínimo e Padrão o teto é APROVADO COM DÉBITO por construção — não bloqueia nada. -->
- **Relatório**: `06-relatorio-qa.md`
- **Débito** (se `APROVADO COM DÉBITO`): {achado ou dimensão não verificada — causa} <!-- teto por cobertura: toda dimensão não verificada aparece aqui -->

## Candidatos a Rule

<!-- Step 12, depois do veredito. Quem coleta, julga e pergunta é a requirement-to-rule — um prompt de
     aprovação só, o dela. Aqui fica só o resultado. -->

- {YYYY-MM-DD} — rota: {sessão principal | sub-agente com MCP herdado} — apresentados N · gravados N · recusados N · descartados no gate N · poda N
<!-- A linha depois da rota vem pronta da requirement-to-rule, neste formato fixo. O commit do step 12 vai na branch do PR aberto. -->

## Auditoria Pré-Implementação
<!-- Saída dos steps 4 a 6, ANTES de escrever código. Não confundir com "Desvios do Plano",
     que é pós-implementação. -->

Entendimento confirmado: {YYYY-MM-DD} — {quem} — {n} rodadas; perguntas: {n} fato, {n} desenho, {n} requisito
<!-- Obrigatória antes do step 5. O sinal de escopo do step 4, quando cruzado, também é registrado aqui. -->

### Confronto código × afirmação (step 5)
| Pergunta | O `01` dizia | O código faz | Resposta (quem, data) | Onde a wiki mudou |
|---|---|---|---|---|
| Q7 | {X} | {Y — `app/…`} | {qual vale} | `01`, passo 4 |

### Revisão profunda (step 5) — premissas do plano contra o código real
| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| {"adicionar import X"} | {já existe em `app/…/Arquivo.php:{símbolo}:12`} | passo 3 reescrito |

### Auditoria Ponytail (step 6)
| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | {…} | sim / recusada: {motivo} | `01`, passo 4 |

## Despachos

<!-- Claude Code: uma linha por disparo de sub-agente, com o modelo, o que ele NÃO recebeu e a
     auditoria do retorno. Host sem sub-agente: uma linha "Sem despacho — host sem sub-agente".
     Tarefa que rodou em linha por exceção: "Sem despacho — {motivo}".
     Auditoria REPROVADA também é linha (com o redespacho ao lado); fallback para general-purpose
     por agente fw-* indisponível vai na coluna Modelo; fallback de Explore ou SendMessage, na coluna
     Agente / tarefa. Custo: o que o host reporta no retorno (tokens, duração); "—" se não reporta. -->

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| 1 | 3 | `mecanico` — Superfície Livewire | haiku | — | tabela, 7 linhas | {tokens · duração} | 2/7 conferidas por grep |
| 2 | 9 | `fw-revisor-diff` — eixos sobre `main...HEAD`, sem `wikis/` | opus | `01`, `03` | 3 achados, 1 rejeitado | {tokens · duração} | 3/3 reproduzidos; `git status --porcelain` igual antes/depois |
| 3 | 11 | `fw-qa-gate` — quality gate | opus | conversa | `06` gravado verbatim (entre `<<<06` e `>>>06`), APROVADO COM DÉBITO | {tokens · duração} | `git status --porcelain` igual antes/depois |

## Blockers
<!-- Impedimentos encontrados durante implementação -->
- [ ] {Blocker 1}: {descrição + o que está sendo feito para resolver}

## Desvios do Plano
<!-- Onde a implementação divergiu do PRD e por quê -->
- {Passo X alterado}: {motivo}

## Notas de Implementação
<!-- Descobertas durante o código que não estavam no plano -->
- {Descoberta 1}: {impacto e onde foi documentado}

## Referências Abertas
<!-- Uma linha por arquivo de references/ das skills aberto nesta feature, com o step. O checklist
     final confere contra o mínimo da tabela do Índice do SKILL.md; o que foi pulado diz por quê. -->
- `template-00-requisito.md` — step 4 — {YYYY-MM-DD}

## Retrospectiva
<!-- O que funcionou bem no planejamento e o que faltou -->
- **Funcionou bem**: {ponto positivo}
- **Faltou no plano**: {ponto de melhoria para próxima wiki}
```

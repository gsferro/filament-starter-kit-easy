> Referência da feature-quality-gate 1.7.0. Lida em: passo 6 do fluxo (antes de escrever o `06`). Fonte única de: template do `06-relatorio-qa.md` que o gate escreve. O `06` mínimo com `NÃO APLICÁVEL` não sai daqui: é escrito pela sessão da `feature-wiki`, sem rodar o gate.

# Template do `06-relatorio-qa.md`

O path, o teto de tamanho, o que o template não pode perder, o teto por cobertura e o retorno entre
`<<<06` e `>>>06` em sub-agente ficam no corpo do `SKILL.md`, seções *Arquivo 06*, *Veredito e teto
por cobertura* e passo 6 do *Fluxo de Execução*.

```markdown
# Relatório de QA — {Card}: {Título}

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: mínimo | padrão | completo
> Natureza da wiki: {tipo} · Toca infra compartilhada: não | sim → {o quê} · Regressão: sim | não
> Independência: sub-agente {agente}/{modelo}, sem acesso à conversa | em linha — {motivo} (mesma sessão que escreveu a wiki, degradado) | comprometida — recebeu {o quê}
> Cobertura: {n} de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO | APROVADO COM DÉBITO

## Veredito — Ciclo {N}

**{APROVADO | APROVADO COM DÉBITO | REPROVADO → especificação/implementação/teste}**

- Blocker: {n} · Major: {n} · Minor: {n} · Cosmético: {n}
- Não verificadas: {dimensões, ou "nenhuma"} — causas em *Não Verificado*
- `RQ` abertas: {RQ-nn (Qn), ou "nenhuma"}
- Ambiente: app em `{APP_URL}` · Pest {versão} · MCP: usado | indisponível

## Achados

### QA-01 — {título curto} · {Blocker|Major|Minor|Cosmético} · destino {1-5}

- **Dimensão**: {A-L}
- **Relacionado a**: RQ-02 ou P-03, CT-05, passo 4 do PRD, ticket 03
- **Esperado**: {o que o requisito/PRD determina, com citação}
- **Observado**: {o que o app faz}
- **Repro**:
  1. {passo}
  2. {passo}
- **Evidência**: `{comando executado}` e a saída (de script: a linha que ele devolveu) / `storage/logs/...` / screenshot
- **Destino**: {1 especificação | 2 implementação | 3 teste | 4 infra | 5 não-defeito}
- **Ação exigida**: {o que precisa ser feito, em uma frase; achado que muda o que a feature promete: `P-nn` → CT → correção}

## Matriz de Rastreabilidade

<!-- Só imprimir se houver lacuna. Matriz completa sem buraco não vai para o relatório.
     Uma linha por RQ e por P-nn vigente. Coluna Ticket só quando existe 07-tickets/.
     Fonte mecânica: saída do rastreabilidade.sh da feature-wiki; a coluna Ticket, do
     indice.sh --check da feature-tickets (CT-B conta como CT). -->

| RQ/P | Cláusula ou premissa | Passo PRD | CT | CT-B | Ticket | Código | Resultado | Veredito |
|------|----------------------|-----------|----|------|--------|--------|-----------|----------|
| RQ-01 | {…} | 3, 4 | CT-01 | CT-B01 | 02 | `{Classe}` | ✅ | OK |
| RQ-02 | {…} | — | — | — | — | — | — | ❌ omissão silenciosa |
| RQ-03 | {…} (aberta — Q2) | 5 (bloqueado por RQ-03) | — | — | 04 (bloqueado por Q2) | — | — | ⏳ aberta, sem implementação — teto |
| P-01 | {…} | 6 | CT-09 | — | 03 | `{Classe}` | ✅ | OK |

## Dimensões

<!-- Todas as 12. Status: ✅ verificada · ⚠️/❌ verificada com achado · n/a — {prova} · ⏭️ não verificada — {causa}.
     Nas dimensões com script, o exit de cada um. Na I, o que o step 9 cobriu e o que foi coberto além. -->

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ / ⚠️ / ❌ | {n} achados — `rastreabilidade.sh` exit {0 \| 1}; `indice.sh --check` exit {0 \| 1 \| sem `07-tickets/`} |
| I | Segurança da superfície nova | ✅ / ⚠️ / ❌ | coberto pelo step 9, `## Revisão do Diff (step 9)` do `03` ({n} achados, {m} rejeitados) \| seção ausente: eixo 9 rodado; coberto além: {linhas da I} |
| K | Adequação da suíte | ✅ / ⚠️ / ❌ / ⏭️ | K1 `k1-oraculo-fraco.sh` exit {…}; K2 {score, duração, sobreviventes \| não verificada — {causa}}; revisão adversarial do `04`: feita \| NÃO FEITA — {motivo} (⏭️) |
| L | Consistência documental | ✅ / ⚠️ / ❌ | {n} achados — L1…L7; L1 `ids-ct.sh` exit {…}, L2 `citacoes.sh` exit {…}, L4 `conformidade-rules.sh` exit {…}, L6 `checkbox-sem-evidencia.sh` exit {…} |
| B | Fronteiras e dados | ⏭️ não verificada | fora do perfil `mínimo` |
| G | Tema e cor | n/a | `dark-mode.sh --mecanismo` sem saída (exit 0) |
| J | Regressão adjacente | n/a | natureza `nova`, `Toca infra compartilhada?: não` |
| … | | | |

## Débitos Aceitos

- QA-04 (Minor): {descrição} — replicado em `03-progresso.md`

## Suspeitas Não Confirmadas

<!-- Sem repro mínima não é achado. Fica aqui para não virar ruído nem se perder. -->

- {descrição} — não reproduzível em {n} tentativas

## Não Verificado

<!-- Honestidade sobre o alcance desta execução. Cada linha é débito de cobertura: impõe o teto
     APROVADO COM DÉBITO e é replicada no 03 com a causa. -->

- {dimensão/caso} — motivo: {fora do perfil X | app não servido | MCP indisponível | sem driver de cobertura (prova: `php -m`) | script ausente: {nome} | sem baseline de screenshot | oráculo degradado | revisão adversarial do `04` não feita — {motivo do cabeçalho do `04`}}
```

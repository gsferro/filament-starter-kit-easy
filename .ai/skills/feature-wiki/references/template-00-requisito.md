> Referência da feature-wiki 4.0.0. Lida em: step 4 (antes de escrever o `00-requisito.md`), ao registrar um Adendo (pedido novo do solicitante ou resposta a uma pergunta) e ao registrar uma premissa (achado confirmado dos steps 5, 9 ou 11, ou decisão de desenho que muda o que a feature promete). Fonte única de: tabela de regimes das seções, template do `00-requisito.md`, da `## Premissas` e do `## Adendo N`.

# Template do `00-requisito.md`

A obrigação dos regimes, a definição de premissa `P-nn`, o que é obrigatório incluir e o
procedimento do Adendo ficam no corpo do `SKILL.md`, seção *Arquivo 00*. O formato das perguntas e
as perguntas-semente estão em [`entrevista-tres-raias.md`](entrevista-tres-raias.md).

## Regimes das seções

| Seção | Regime | Quem escreve |
|---|---|---|
| `## Fonte` | imutável após criada | sessão |
| `## Texto Original` | **imutável.** Nunca editar, corrigir, resumir ou reordenar | sessão (verbatim) |
| `## Decomposição em Cláusulas` | derivada e revisável. Coluna `Estado`: `fechada` · `aberta — Qn` · `substituída por RQ-nn (Adendo N)` · `decomposta em RQ-nn, RQ-mm` | sessão |
| `## Perguntas ao Solicitante` | revisável — só a raia requisito | sessão |
| `## Premissas` | revisável | sessão |
| `## Fora de Escopo (declarado)` | revisável | sessão |
| `## Adendo N — {YYYY-MM-DD}` | **imutável** como o Texto Original; **só** pedido do solicitante, com fonte e texto verbatim. A numeração de `RQ` continua da última | sessão |

## Template

O `00` não leva path de código, `arquivo:linha` nem contagem derivada: é o oráculo e não tem
reconciliação. A `Origem` aponta o documento de onde o requisito veio, não código.

**Template `00-requisito.md`**:
```markdown
# Requisito — {Card}: {Título}

## Fonte

- **Origem**: {card FERRO-579 colado no chat | docs/requisitos/RF-231.pdf, p. 3-4 | conversa com {quem}}
- **Data**: {YYYY-MM-DD}
- **Autor / solicitante**: {nome ou área}
- **Fidelidade**: alta (texto escrito) | **baixa** (descrição verbal — confirmar antes de implementar)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> {texto colado verbatim, ou trechos literais citados da fonte}

## Decomposição em Cláusulas

<!-- Derivada e revisável. Estado: fechada · aberta — Qn · substituída por RQ-nn (Adendo N) · decomposta em RQ-nn, RQ-mm
     Decomposta: cláusula grande demais quebrada nas filhas (ex.: devolvida pela feature-tickets, que não
     divide uma RQ entre tickets). As filhas entram como linhas novas; a decomposta sai da cobrança. -->

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | {o que deve acontecer, em uma frase} | "{citação literal}" | funcional | fechada |
| RQ-02 | {…} | "{…}" | autorização | fechada |
| RQ-03 | {…} | "{…}" | não-funcional | aberta — Q1 |

## Perguntas ao Solicitante

<!-- Raia requisito: só o solicitante responde. Enquanto aberta, a RQ afetada fica `aberta — Qn`
     e nenhum passo do 01 a implementa. A resposta entra como Adendo com fonte.
     Qn é a sequência única da feature: os números que faltam aqui são perguntas de desenho (01/02). -->

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | "{precisa ser rápido}" — sem número não é testável. Qual o tempo máximo aceitável? | RQ-03 | {…} | aberta · respondida no Adendo 2 · retirada — {motivo} |
| Q2 | RQ-05 conflita com RQ-02 — {descrever o conflito}: qual vale? | RQ-02, RQ-05 | {…} | aberta |

## Premissas

<!-- Revisável. O que a feature assume sem que o solicitante tenha escrito. Não é requisito.
     Premissa que contradiz ou estende o pedido gera também uma pergunta em ## Perguntas ao Solicitante. -->

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | {afirmação testável, uma frase} | step 9 — RD-03 (fw-revisor-diff) | 2026-09-26 | RQ-04 | vigente |

## Fora de Escopo (declarado)

<!-- O que o requisito explicitamente NÃO pede, para o quality gate não acusar omissão indevida. -->

- {item explicitamente fora}
```

`Estado` da premissa: `vigente` · `substituída por P-nn` · `promovida a RQ-nn (Adendo N)` ·
`revogada — {motivo}`. Seção sem linha fica com "nenhuma" — ausente é diferente de vazia.

## Template do Adendo

Só para pedido do solicitante — um pedido novo ou a resposta a uma `Qn` —, com fonte e texto
verbatim. Achado de revisão não é Adendo: vai para `## Premissas`.

**Template**:

```markdown
## Adendo 1 — 2026-09-05

- **Fonte**: pedido do solicitante no chat, durante a implementação do passo 9
- **Fidelidade**: alta (texto escrito)
- **Responde a**: Q1 <!-- só quando o adendo é a resposta a uma pergunta; senão, "—" -->

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> {texto verbatim do pedido novo ou da resposta}

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-09 | {…} | "{…}" | funcional | — |
| RQ-10 | {…} | "{…}" | restrição | RQ-04 (parcial) |
```

**`Substitui`**: `RQ` citada ali **sem** `(parcial)` é tratada como substituída pelo `rastreabilidade.sh`
e pelo `indice.sh`, mesmo sem a coluna `Estado` da Decomposição — é o único jeito de substituir uma `RQ`
que nasceu noutro Adendo, que é imutável. Com `(parcial)`, as duas continuam vigentes.

Depois do Adendo: a pergunta respondida passa a `respondida no Adendo N`; a `RQ` afetada, a
`fechada` ou `substituída por RQ-nn (Adendo N)`; a premissa confirmada pelo solicitante, a
`promovida a RQ-nn (Adendo N)`.

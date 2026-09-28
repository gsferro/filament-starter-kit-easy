> Referência da feature-wiki 4.0.0. Lida em: step 4 (antes de escrever o `02-decisoes-arquiteturais.md`). Fonte única de: template do `02-decisoes-arquiteturais.md` em formato ADR, com a linha dos três portões.

# Template do `02-decisoes-arquiteturais.md`

O propósito, os três portões e o que incluir ficam no corpo do `SKILL.md`, seção *Arquivo 02*. A
tabela `## Superfície Livewire`, que também mora no `02`, tem formato e greps em
`references/pesquisa-step-3.md`.

A ADR não leva `arquivo:linha` nem contagem: cita módulo ou classe por nome. Path envelhece no
próprio ciclo; a decisão, devagar. A única exceção no `02` é a `## Superfície Livewire`, que tem
reconciliação própria (re-varrida antes do step 9, citações conferidas no step 10).

**Template `02-decisoes-arquiteturais.md`**:
```markdown
# Decisões Arquiteturais — {Card}

<!-- ADR só quando as três valem: difícil de reverter, surpreendente sem contexto, resultado de
     trade-off real. Falta uma → sem ADR; a decisão vira linha em ## Decisões de Desenho do 01.
     Nenhuma ADR é resultado válido: escrever a frase abaixo no lugar delas. -->

Nenhuma decisão passou nos três portões. <!-- só quando não há ADR; apagar se houver -->

## ADR-01: {Título da Decisão}

**Status**: Aceita | Proposta | Deprecada
**Data**: {YYYY-MM-DD}
**Portões**: difícil de reverter ✅ ({porque}) · surpreendente ✅ ({…}) · trade-off ✅ ({…})

### Contexto
{Por que esta decisão é necessária — problema, restrições, pressões}

### Decisão
{O que foi decidido — a escolha feita}

### Alternativas Consideradas
1. {Alternativa A} — {por que foi descartada}
2. {Alternativa B} — {por que foi descartada}

### Consequências
- **Positivas**: {benefícios da decisão}
- **Negativas**: {trade-offs aceitos}
- **Riscos**: {riscos introduzidos e mitigações}

### Referências
- {classe/módulo, ADR relacionada, link externo}
- Refine: ADR-{xx} (se aplicável)

---

## ADR-02: {Título da Decisão}
...

## Superfície Livewire
<!-- Quando exigida (feature que cria página, widget ou componente). Formato e greps em pesquisa-step-3.md. -->
```

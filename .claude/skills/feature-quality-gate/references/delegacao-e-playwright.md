> Referência da feature-quality-gate 1.7.0. Lida em: antes de delegar a uma skill do `qa-skills` e antes de subir o Playwright MCP (dimensões A, G e H). Fonte única de: o mapa necessidade → skill → fallback inline, e os três confrontos do Playwright MCP com o exemplo.

# Delegação ao `qa-skills` e confrontos do Playwright MCP

As obrigações ficam no corpo do `SKILL.md`: delegar quando disponível e nunca reescrever, instalar
só as skills usadas, registrar no relatório a skill ou o fallback (seção *Delegação a Skills
Externas*); as regras do MCP — alvo só `localhost`/`APP_URL` de desenvolvimento, proibições, sessão
MCP não é cobertura (seção *Playwright MCP como Confronto*).

## Mapa de delegação

| Necessidade | Skill | Fallback inline se ausente |
|---|---|---|
| Priorizar o que validar | `risk-based-testing` | usar o gate de esforço do `SKILL.md` (seção *Gate de Esforço por Risco*) |
| Explorar o não-especificado | `exploratory-testing` (SBTM, charters) | charter de 1 linha + time-box de 10 min por tela, anotando o que surpreendeu |
| Severidade e root cause | `ai-bug-triage` | tabela de severidade do `SKILL.md` (seção *Severidade*) |
| Repro mínima | `bug-reproduction` | reduzir passos até o mínimo que reproduz; sem repro → "Suspeitas Não Confirmadas" |
| Avaliar os próprios CT/CT-B | `ai-qa-review` | perguntar de cada CT: "se eu quebrar a regra, ele falha?" |
| Flake × bug real | `test-reliability` | rodar 3× — falha intermitente é flake (destino 4), não defeito |
| Podar suíte na regressão | `test-suite-curation` | só listar CT redundante, sem remover |

## Os três confrontos do Playwright MCP

### 1. Inventário de elementos × cobertura do CT-B

`browser_snapshot` devolve o inventário de elementos interativos da tela. O CT-B declara quais exercita. **A diferença é lacuna de cobertura, mensurável**:

```
Tela /relatorios/lote — elementos interativos observados: 7
CT-B01 + CT-B02 exercitam: 4
Não exercitados: botão "Exportar CSV", select "Período", checkbox "Incluir inativos"
→ QA-0X (dimensão A, destino 3): 3 elementos sem CT-B
```

### 2. UI renderizada × `## Superfície de UI` do PRD

Elemento na tela fora da tabela = UI não documentada (scope creep ou PRD desatualizado). Linha na tabela sem elemento correspondente = prometido e não entregue.

### 3. Visual/tema e console/rede

Screenshot nos dois temas (dimensão G) e `browser_console_messages` / `browser_network_requests` — 4xx/5xx que o app engole em silêncio **não** falha `assertNoSmoke()`.

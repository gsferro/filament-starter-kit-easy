# feature-quality-gate — QA no Agente

> **Status: implementada — [`SKILL.md`](SKILL.md) v1.0.0.**
> Requer `feature-wiki` ≥ **2.10.0** (que introduziu o `00-requisito.md`, o oráculo desta skill).
>
> Este documento é o registro da pesquisa que precedeu a implementação: qual problema ela resolve, o que já existe no mercado (incluindo alternativas MIT), qual lacuna sobra, e por que essa lacuna justificou uma skill nova em vez de instalar o que estava pronto. Ele continua sendo o documento **para humanos** — vantagens, escopo, dependências e limitações.
>
> **`SKILL.md` fala com o agente; este README fala com a pessoa.** Procedimento, gates e templates estão no `SKILL.md` e não são duplicados aqui.

---

## Índice

**Uso**
- [Uso da skill](#uso-da-skill)

**Motivação e estudo**
- [TL;DR do veredito](#tldr-do-veredito)
- [O problema: onde a coletânea para](#o-problema-onde-a-coletânea-para)
- [Estudo de mercado](#estudo-de-mercado-o-que-já-existe)
- [A lacuna verificada](#a-lacuna-verificada)
- [A objeção que quase matou a skill](#a-objeção-que-quase-matou-a-skill)
- [Ganho real 1: omissão silenciosa](#ganho-real-1--omissão-silenciosa-e-a-matriz-de-rastreabilidade)
- [Ganho real 2: as 10 dimensões](#ganho-real-2--as-10-dimensões-que-as-camadas-atuais-não-cobrem)
- [Ganho real 3: roteamento](#ganho-real-3--roteamento-de-achados)
- [Achado técnico: dark mode inverte a regra](#achado-técnico-dark-mode-inverte-a-regra-de-visão--estrutura)
- [Achado técnico: MCP como confronto](#achado-técnico-playwright-mcp-como-confronto-do-ct-b)
- [Regressão condicional](#regressão-condicional-por-natureza-da-wiki)
- [Construir × reusar](#construir--reusar)
- [Convergência e separação de poderes](#convergência-e-separação-de-poderes)
- [Riscos honestos](#riscos-honestos)
- [Critério eliminatório](#critério-eliminatório)
- [Dependências](#dependências)
- [Fontes](#fontes)

---

## Uso da skill

A skill é a **próxima estação da esteira** — roda no step 8 da [`feature-wiki`](../feature-wiki/README.md), depois de implementar e com os testes verdes.

### O defeito que ela existe para pegar

**Omissão silenciosa**: cláusula do requisito que nunca virou passo do plano, nunca virou teste, nunca virou código.

| RQ | Cláusula | Passo PRD | CT | CT-B | Código | Veredito |
|----|----------|-----------|----|------|--------|----------|
| RQ-01 | gerar relatório em lote | 3, 4 | CT-01 | CT-B01 | `GerarRelatorioLoteJob` | OK |
| RQ-03 | notificar coordenador | — | — | — | — | ❌ **omissão silenciosa** |

Nenhum teste falhou porque **nunca existiu teste** — e nunca existiu teste porque a cláusula nunca entrou no plano. CT e CT-B só podem falhar no que foi especificado; o `ponytail-review` audita **excesso**, nunca **falta**; o TIA mede impacto do diff, não cobertura do requisito. **É uma classe de defeito estruturalmente invisível para o resto do ciclo.**

### As 10 dimensões

| # | Dimensão | Exemplo do que pega |
|---|---|---|
| A | Cobertura do requisito | cláusula sem plano/teste/código |
| B | Fronteiras e dados | `-1`, string de 500 chars, upload de 0 byte, 29/02 |
| C | Matriz de permissão | 3 papéis × 4 ações = 12 células; o CT cobre 1 |
| D | Observabilidade real | o PRD manda logar `[Classe@Método]` — **e ninguém confere**. Inclui **PII vazando no context** |
| E | Performance | N+1, query sem índice, `->get()` onde cabia paginação |
| F | UX de erro | "Erro ao processar" em vez de "CPF já inscrito na turma X" |
| G | Tema e cor | **texto branco em fundo branco: `assertSee()` PASSA** |
| H | Acessibilidade | teclado, foco, contraste, `alt` |
| I | Segurança da superfície nova | IDOR, mass assignment, rota sem `can:` |
| J | Regressão adjacente | só em wiki de evolução/correção |

A dimensão **D** é auto-referente e reveladora: a [`feature-wiki`](../feature-wiki/README.md) exige log em toda etapa de execução, com channel dedicado e context estruturado — e nada no ciclo verificava se isso acontecia. O quality gate fecha o laço da própria skill principal.

### Roteamento: 5 destinos, não 2

Esta é a peça que **não existe em nenhuma skill de QA do mercado** — verificado na pesquisa.

| Achado | Diagnóstico | Volta para |
|---|---|---|
| requisito ambíguo/incompleto | defeito de **especificação** | escrita da wiki (`00`/`01`/`02`) |
| implementação diverge do PRD | defeito de **código** | execução do passo |
| comportamento errado sem CT que cubra | defeito de **teste** | `04`/`05`, **depois** implementação |
| ambiente, dado, build | não é defeito do produto | infra |
| comportamento correto, expectativa errada | não é defeito | fecha, registrando o porquê |

Prioridade quando há vários: **especificação > teste > implementação**. Corrigir código contra especificação ambígua é retrabalho garantido.

E no destino "teste", a ordem importa: **escrever o CT que falha primeiro**, só então corrigir. Invertido, perde-se a prova e o defeito volta na feature seguinte.

### Regras que impedem a skill de virar teatro

| Princípio | Efeito prático |
|---|---|
| **Oráculo externo** | o PRD é **alegação a ser testada**, não verdade |
| **Separação de poderes** | **não corrige nada** — lê, reproduz, reporta. Quem julga não conserta |
| **Convergência** | teto de **3 ciclos**; ciclo sem achado novo encerra; estourar escala a você |
| **Teto por risco** | 3 perfis: `ajuste` sem UI roda 3 dimensões, não 10 |
| **Degradação graciosa** | sem MCP, sem `qa-skills`, sem Pest 5 ela roda — e **declara no relatório** o que não pôde verificar |

### Saída

`06-relatorio-qa.md` na wiki, com veredito (`APROVADO` / `APROVADO COM DÉBITO` / `REPROVADO → destino`), achados com repro mínima e evidência, a Matriz de Rastreabilidade (**impressa só se houver lacuna**, para não virar métrica de vaidade) e uma seção **"Não Verificado"** declarando o alcance real da execução.

### Dependências

**Obrigatórias:** `feature-wiki` ≥ 2.10.0 (pelo `00-requisito.md`), app servido, Pest.

**Opcionais** — a skill degrada sem cada uma: Pest 5 (`--parallel --tia` para regressão), `pest-plugin-agent` (sondagem), `pest-plugin-browser` (CT-B), Playwright MCP (inventário de elementos, tema, console/rede), Boost MCP (`Browser Logs`, `database-query`), PCOV/Xdebug (pré-requisito do `--tia`).

**Técnica de QA delegada** (MIT, instalar só as usadas):

```bash
# NÃO instalar as 50 — inflação de contexto. Copiar só as pastas necessárias.
# risk-based-testing · exploratory-testing · ai-bug-triage
# bug-reproduction · ai-qa-review · test-reliability
npx skills add petrkindlmann/qa-skills
```

Cada delegação tem **fallback inline** documentado na skill: se a skill externa não estiver instalada, o agente aplica a técnica resumida em 2-3 linhas e registra no relatório qual caminho usou.

---

# Motivação e Estudo de Viabilidade

## TL;DR do veredito

**Vale a pena — mas não pelo motivo óbvio.**

Técnica de QA é **commodity**: existem 50 skills MIT prontas cobrindo SBTM, risk-based testing, triagem de bug, repro mínima, exploratório e automação. Reescrever isso seria desperdício.

O que **não existe em nenhuma delas** é a camada de **controle de loop**: decidir se um achado volta para a *especificação*, para a *implementação* ou para o *teste*, e parar quando convergir. Foi verificado, não presumido.

E existe uma classe de defeito que **nenhuma** das três camadas de revisão atuais da coletânea consegue detectar: a **omissão silenciosa** — cláusula do requisito que nunca virou passo do plano, nunca virou teste, nunca virou código. Tudo verde, feature incompleta.

A skill se justifica por essas duas coisas. Todo o resto ela deve **delegar**.

---

## O problema: onde a coletânea para

Numa esteira de desenvolvimento — **requisitos → desenvolvimento → QA → deploy** — a coletânea hoje cobre as duas primeiras etapas:

| Etapa | Cobertura atual |
|---|---|
| Requisitos | `feature-wiki` — PRD, ADR |
| Desenvolvimento | `feature-wiki` + Ponytail — passos, CT, CT-B, log |
| **QA** | **descoberto** |
| Deploy | descoberto |

A `feature-wiki` já tem **três camadas de revisão**, e é importante reconhecer isso antes de propor uma quarta:

1. **Step 5** — revisão profunda pós-escrita: re-valida cada premissa do plano contra o código real
2. **Step 6** — `/ponytail:ponytail-review` audita o plano contra over-engineering
3. **Loop de CT-B** — sub-agente escreve, roda e classifica falha em (a) CT errado / (b) implementação divergente / (c) flake, preenchendo a tabela *Desenhado × Implementado*

Uma quarta camada só se justifica se cobrir algo que essas três são **estruturalmente incapazes** de ver. Esse foi o critério do estudo.

E há um limite comum às três: **todas tomam o PRD como verdade.** O step 5 valida o plano contra o código; o step 6 corta excesso do plano; o loop de CT-B compara PRD × tela. Nenhuma pergunta se o **PRD reflete o requisito**.

---

## Estudo de mercado: o que já existe

Pesquisa feita antes de qualquer linha de desenho.

| Fonte | O que é | Licença |
|---|---|---|
| [`petrkindlmann/qa-skills`](https://github.com/petrkindlmann/qa-skills) | **50 skills de QA** no Agent Skills Standard, drop-in no Claude Code / Codex / Cursor | **MIT** |
| [QASkills.sh](https://qaskills.sh/agents/claude-code) | diretório com 40+ skills de teste (Playwright, Cypress, k6, axe) | vários |
| [awesomeskill.ai — Testing & QA](https://awesomeskill.ai/category/testing-qa) | categoria inteira de skills de QA | vários |
| [QA Engineer Agent](https://mcpmarket.com/tools/skills/qa-engineer-agent-1) | "test planning, bug reporting, regression analysis" | — |
| [Playwright Test Agents](https://playwright.dev/docs/test-agents) | planner → generator → **healer** | MIT |
| Laravel Boost — skills | `pest-testing`, `infer-conventions`, `livewire-development`… — **nenhuma de QA** | MIT |

O `qa-skills` cobre praticamente todo o vocabulário técnico de um QA sênior:

| Categoria | Skills relevantes |
|---|---|
| Estratégia | `test-strategy`, `test-planning`, **`risk-based-testing`**, **`exploratory-testing`** (SBTM, charters, heurísticas) |
| Processo | `shift-left-testing`, **`release-readiness`** (go/no-go), `quality-postmortem`, `compliance-testing`, `test-case-management`, **`test-suite-curation`** |
| IA-aumentado | `ai-test-generation` (gera casos a partir de PRD), **`ai-bug-triage`** (severidade/componente/root cause + dedupe), **`test-reliability`** (flake), **`ai-qa-review`**, **`bug-reproduction`**, `agentic-browser-testing` |
| Automação | Playwright, Cypress, API, unit, mobile, visual, performance, recuperação de seletor |

**Conclusão parcial: se a skill nova fosse "como um QA sênior pensa", ela seria uma reescrita pior de 50 skills MIT.**

E o `healer` do Playwright merece nota: ele **repara o teste até passar**. Numa esteira de auditoria isso é o oposto do desejado — é exatamente o comportamento que o contrato do sub-agente de CT-B já proíbe ("alterar código para o teste passar destrói o instrumento de medição").

---

## A lacuna verificada

Todas as opções acima produzem **mais teste** ou **mais relatório**. Nenhuma fecha o ciclo. A doc do próprio QASkills.sh é explícita ao descrever o estado da arte:

> "The article describes these as complementary layers of a testing pyramid rather than a **gated feedback loop**. **No skill routes findings back to specifications or implementation gates.**"

Ou seja: nenhuma decide *"este achado volta para a especificação"* × *"este volta para a implementação"* × *"este não é defeito"*. A decisão de roteamento continua sendo humana, informal e não registrada.

**O ganho real da skill não é QA. É roteamento e convergência — um *loop controller*.**

---

## A objeção que quase matou a skill

Vale registrar o argumento contra, porque ele define o desenho.

**Cegueira correlacionada.** Hoje o mesmo agente lê o requisito → escreve o PRD → escreve os CT → implementa → roda os CT. Se ele **entendeu o requisito errado**, erra coerentemente quatro vezes. Tudo verde. Nada detectado.

Um sub-agente de QA que leia **a wiki** não resolve: ele herda a mesma premissa errada e a confirma. Isolamento de contexto ajuda na *atenção*, não em *mal-entendido compartilhado*.

**A consequência de desenho é decisiva:** o quality-gate **não pode** ter o PRD como fonte de verdade. Precisa de um oráculo externo à cadeia.

E aqui estava o problema prático: a `feature-wiki` **não guarda o requisito bruto**. Ele entra na conversa, vira PRD, e desaparece.

**A solução é barata e é o habilitador de tudo: `00-requisito.md`.**

```
wikis/specs/{branch}/{feature}/
├── 00-requisito.md          ← requisito bruto, verbatim, imutável
├── 01-plano-acao.md         ← interpretação do requisito
├── ...
└── 06-relatorio-qa.md       ← saída do quality-gate
```

Com ele, o confronto passa a ser triangular:

```
00-requisito.md   ──►  o que foi PEDIDO
01-plano-acao.md  ──►  o que foi PROMETIDO
app rodando       ──►  o que EXISTE
```

O requisito chega colado no chat ou como arquivo no projeto (`pdf`/`docx`/`md`) — em qualquer caso é **persistido no `00`** no momento da criação da wiki, com duas seções: **texto original imutável** + **decomposição em cláusulas numeradas** (`RQ-01`, `RQ-02`…) citando o trecho literal de origem. O texto bruto nunca é editado; a decomposição é derivada e revisável.

Efeito colateral valioso: cláusula ambígua (*"precisa ser rápido"* sem SLA) é registrada como **pergunta aberta** antes de qualquer código, em vez de suposição silenciosa.

---

## Ganho real 1 — Omissão silenciosa e a Matriz de Rastreabilidade

A **Matriz de Rastreabilidade** amarra cada cláusula do requisito a tudo que dela derivou:

```
cláusula → passo do PRD → CT → CT-B → código → resultado → veredito
```

> **Nomenclatura.** O termo adotado nesta coletânea — em documentos, no `06-relatorio-qa.md` e na futura `SKILL.md` — é **Matriz de Rastreabilidade**. A sigla **RTM** (*Requirements Traceability Matrix*) fica disponível como referência quando o contexto pedir: vocabulário de QA formal (ISTQB), conversa com time de qualidade, ou auditoria de setor regulado, onde o artefato é conhecido por esse nome. Em prosa corrente, escrever por extenso.

O valor não está em documentar o que existe — está em **expor a célula vazia**. E ela é **bidirecional**:

- **Para frente** (requisito → código): *o que foi pedido e não foi entregue?*
- **Para trás** (código → requisito): *o que foi entregue e ninguém pediu?*

| RQ | Cláusula (`00`) | Passo PRD | CT (`04`) | CT-B (`05`) | Código | Resultado | Veredito |
|---|---|---|---|---|---|---|---|
| RQ-01 | gerar relatório por turma em lote | 3, 4 | CT-01, CT-02 | CT-B01 | `GerarRelatorioLoteJob` | ✅ | OK |
| RQ-02 | notificar coordenador ao concluir | — | — | — | — | — | ❌ **omissão silenciosa** |
| RQ-03 | só coordenador pode disparar | 2 | CT-03 | — | `RelatorioPolicy` | ✅ | ⚠️ papel não validado na UI |
| RQ-04 | — | 6 | CT-07 | — | `ExportCsvAction` | ✅ | ⚠️ **escopo extra** |

A linha **RQ-02** é a razão de existir da skill: **nenhum teste falhou porque nunca existiu teste** — e nunca existiu teste porque a cláusula nunca virou passo do plano. CT e CT-B só podem falhar no que foi especificado. O `ponytail-review` audita se o plano tem **excesso**, nunca se tem **falta**. O TIA mede impacto do diff, não cobertura do requisito.

**Nenhuma das três camadas atuais é capaz de ver isso, por construção.**

---

## Ganho real 2 — As 10 dimensões que as camadas atuais não cobrem

| # | Dimensão | Por que escapa hoje | Como verificar |
|---|---|---|---|
| **A** | Cobertura do requisito | CT só falha no especificado | a Matriz de Rastreabilidade acima |
| **B** | Fronteiras e dados | CT cobre o caso do PRD, não os vizinhos | 0, -1, vazio, 500 chars, unicode/emoji, upload 0 byte, 29/02, timezone |
| **C** | Matriz de permissão | o CT de autorização testa **1** papel | papéis × ações — 3 × 4 = 12 células, o CT cobre 1 |
| **D** | Observabilidade real | o PRD **manda** logar `[Classe@Método]` — **quem confere?** | rodar o fluxo, ler `storage/logs/{feature}.log`, comparar com o especificado; checar PII no context |
| **E** | Performance | CT passa em 3 queries ou em 300 | `Model::preventLazyLoading()`, contagem de queries, `pest --profile` |
| **F** | UX de erro | `assertSee('erro')` passa com mensagem inútil | "Erro ao processar" × "CPF já inscrito na turma X"; form preserva o digitado? |
| **G** | Tema e cor (dark mode) | **CT-B passa com texto invisível** — ver seção seguinte | grep de classe sem par `dark:` + inspeção visual nos dois temas |
| **H** | Acessibilidade | só se alguém escreveu o CT-B | `assertNoAccessibilityIssues()`, teclado, foco pós-submit |
| **I** | Superfície nova de segurança | fora do escopo do CT | IDOR em `/x/{id}` de outro tenant, mass assignment, rota sem `can:`, upload sem mime |
| **J** | Regressão adjacente | TIA diz quais testes o diff afetou, não o que **não tinha teste** | só no modo evolução/correção |

A dimensão **D** é auto-referente e reveladora: a `feature-wiki` exige log em toda etapa de execução, com channel dedicado e context estruturado — e **nada no ciclo atual verifica se isso aconteceu**. O quality-gate fecha o laço da própria skill principal.

---

## Ganho real 3 — Roteamento de achados

A taxonomia que não existe no mercado. Cinco destinos, não dois:

| # | Achado | Diagnóstico | Volta para | Artefato |
|---|---|---|---|---|
| 1 | requisito ambíguo / incompleto / contraditório | **defeito de especificação** | escrita da wiki (`01`/`02`) | ADR nova ou revisão do PRD |
| 2 | implementação diverge do PRD | **defeito de código** | implementação | passo do PRD reaberto |
| 3 | comportamento errado que nenhum CT cobria | **defeito de teste** | `04`/`05`, **depois** implementação | CT/CT-B novo → então corrige |
| 4 | ambiente, dado, build, seed | **não é defeito do produto** | infra / setup | nota no `03` |
| 5 | comportamento correto, expectativa errada | **não é defeito** | fecha sem mudança | registrar o porquê |

O destino **3** é o mais valioso e o mais fácil de errar: a ordem correta é **escrever o CT que falha primeiro**, depois corrigir. Invertido, perde-se a prova e o caso reaparece na feature seguinte.

**A Matriz de Rastreabilidade alimenta o roteamento.** O formato da lacuna determina o destino — deixa de ser opinião do agente e passa a ser consequência de uma célula vazia:

| Padrão da lacuna | Destino |
|---|---|
| RQ sem passo no PRD | **1** |
| RQ com passo, sem CT | **3** |
| RQ com CT verde, mas app errado | **3** → depois **2** |
| RQ com passo e CT, sem código | **2** |
| Passo/CT/código sem RQ | **1** (documentar) ou remover |
| RQ ambíguo, não decomponível | **1** — pergunta ao usuário |

E a decisão **bloquear × registrar** é por severidade, senão o loop nunca fecha por cosmética:

- **Blocker / Major** → volta pelo destino, loop continua
- **Minor / Cosmético** → não bloqueia; entra num ledger de débito na wiki (mesma ideia do `ponytail-debt`)

---

## Achado técnico: dark mode inverte a regra de visão × estrutura

O `pest-plugin-browser` **tem** `->inDarkMode()`, `assertScreenshotMatches()` e `assertNoAccessibilityIssues()`. Ainda assim, falha em casos graves:

| Cenário | Resultado no pest-browser |
|---|---|
| Texto branco em fundo branco no dark mode | ✅ **`assertSee('Salvar')` PASSA** — está no DOM e na árvore de acessibilidade, apenas **invisível** |
| Feature nova, primeiro `assertScreenshotMatches()` | ✅ passa — ele **cria** o baseline, incluindo o bug |
| `bg-white text-gray-900` sem par `dark:` | ✅ passa — nenhuma assertion olha para isso |
| Contraste insuficiente | ⚠️ **talvez** — o axe tem regra de `color-contrast` e a doc fala de "level 1 (serious)"; plausível, mas a confirmar no projeto |

`assertScreenshotMatches` detecta **mudança**, não **erro**. Em requisito novo não existe baseline correto para comparar — é o furo clássico de screenshot regression.

Cobertura proposta, do mais barato ao mais caro:

1. **Estático, sem browser** — `Grep` nos Blade/componentes tocados pelo diff, procurando classe de cor **sem contraparte `dark:`** e hex hardcoded fora do arquivo de tokens. Determinístico, instantâneo. **Melhor custo-benefício de toda a lista.**
2. **Dinâmico via Pest** — `visit('/x')->inDarkMode()->assertNoAccessibilityIssues()` como CT-B novo, versionado.
3. **Visual via Playwright MCP** — screenshot nos dois temas e o agente **olha**. Único caminho para "ilegível".

> **Correção de ênfase relevante.** Na análise do Playwright MCP a coletânea defende a árvore de acessibilidade contra o screenshot (~200–400 tokens × ~3.000–5.000). **Para defeito de cor isso se inverte**: a árvore é justamente cega ao problema, porque o texto *está* lá. Cor é o caso em que a visão ganha da estrutura — e a skill precisa dizer isso explicitamente para o agente não aplicar a regra errada.

Detalhe de implementação: o quality-gate precisa **detectar o mecanismo do projeto** — `prefers-color-scheme` puro ou classe `dark` no `<html>` com toggle — porque a forma de forçar o tema no teste muda. Projeto sem dark mode: dimensão pulada com registro do motivo.

---

## Achado técnico: Playwright MCP como confronto do CT-B

A coletânea já define que **o `pest-plugin-browser` atesta e o Playwright MCP observa**. Para o quality-gate, o MCP habilita três confrontos:

**1. Inventário de elementos × cobertura do CT-B** *(o mais valioso)*

O `browser_snapshot` devolve o inventário de elementos interativos da tela. O CT-B declara quais exercita. **A diferença é lacuna de cobertura, mensurável:**

```
Tela /relatorios/lote — elementos interativos observados: 7
CT-B01 + CT-B02 exercitam: 4
Não exercitados: botão "Exportar CSV", select "Período", checkbox "Incluir inativos"
→ Achado: 3 elementos na UI sem nenhum CT-B. São escopo ou scope creep?
```

Isso é literalmente "confronto do que entrou no CT-B", e nenhuma ferramenta de teste faz — teste só sabe o que você escreveu nele.

**2. UI renderizada × tabela `## Superfície de UI` do PRD** — elemento na tela fora da tabela = UI não documentada; linha na tabela sem elemento = prometido e não entregue.

**3. Visual/tema e console/rede** — 4xx/5xx que o app engole em silêncio não falha `assertNoSmoke()`.

**Regra que mantém a disciplina:** todo achado do MCP tem dois destinos possíveis, e nenhum é "fica no relatório":

- lacuna de cobertura → **vira CT-B novo** no `05`
- defeito → **vira achado roteado** pela taxonomia

Continuam valendo: sessão MCP não é cobertura; `ref=e5` é efêmero (*"valid until the next page change"*) e nunca entra em teste; `--isolated --headless --caps=testing`; só `localhost`.

---

## Regressão condicional por natureza da wiki

Rodar regressão em toda feature é caro e desnecessário. O gatilho certo é a **natureza da wiki**, declarada no `01`:

```markdown
## Natureza da Wiki

- Tipo: nova | evolução | correção | ajuste
- Wiki ancestral: wikis/specs/ferro/501/envio-progresso/   (obrigatório se não for "nova")
```

| Tipo | Regressão | O que roda |
|---|---|---|
| `nova` | ❌ | valida só a feature |
| `evolução` / `correção` / `ajuste` | ✅ | (1) `pest --parallel --tia` para o impacto **medido**; (2) roda **por ID** os CT/CT-B do `04`/`05` da wiki ancestral; (3) RCRCRC nos arquivos que o diff tocou e a ancestral também tocava |

Rodar os CT da ancestral **por ID** é o que garante que a evolução não silenciou uma regra antiga — algo que o TIA só pega se o teste existir.

---

## Construir × reusar

**Construir apenas a camada que é nossa; delegar a técnica ao que já existe em MIT.**

### Delegar ao [`qa-skills`](https://github.com/petrkindlmann/qa-skills)

| Necessidade | Skill | Papel |
|---|---|---|
| Priorizar o que validar | `risk-based-testing` | define a profundidade por risco |
| Explorar o não-especificado | `exploratory-testing` | SBTM, charters time-boxed, heurísticas |
| Severidade + root cause | `ai-bug-triage` | **alimenta o roteamento** dos 5 destinos |
| Repro mínima antes de reportar | `bug-reproduction` | achado sem repro não é reportável |
| Avaliar os próprios CT/CT-B | `ai-qa-review` | "o CT-01 testa o que diz testar?" |
| Flake × bug real | `test-reliability` | evita roteamento errado da causa (c) |
| Podar suíte na regressão | `test-suite-curation` | só quando tipo ≠ nova |
| Defeito escapado | `quality-postmortem` | fora do loop, sob demanda |
| *(futuro)* gate de deploy | `release-readiness` | cobriria a 4ª etapa da esteira |

### Construir aqui

| Camada | Por que é nossa |
|---|---|
| Confronto `00-requisito` × PRD × app | só existe porque a `feature-wiki` produz esses artefatos |
| Taxonomia de 5 destinos + severidade | **verificado: não existe no mercado** |
| Convergência do loop (teto, sem-achado-novo, escalada) | idem |
| `06-relatorio-qa.md` com rastreabilidade por ID de CT/ADR | idem |
| Checks Laravel-específicos: log real × PRD, N+1, matriz de permissão, dark mode Tailwind | nenhuma skill genérica conhece o **nosso** padrão de log |
| Gate de regressão por natureza da wiki | idem |

### Duas ressalvas sobre a dependência

1. **Não instalar as 50.** `npx skills add petrkindlmann/qa-skills` traz a biblioteca inteira — 50 descrições competindo pela atenção do agente é inflação de contexto, o mesmo problema combatido nas Project Rules. Copiar **só as 5-6 pastas usadas** para `.ai/skills/`. *(A confirmar se o instalador permite seleção; se não, cópia manual.)*
2. **Degradar graciosamente.** O quality-gate precisa funcionar sem elas — mesmo padrão do Playwright MCP na `feature-wiki`. Cada delegação vira: *"se `exploratory-testing` estiver instalada, invoque; senão, aplique estas 3 linhas de heurística inline"*.

---

## Convergência e separação de poderes

"Loop engineering" sem regra de parada é loop infinito. Regras obrigatórias:

1. **Máximo 3 ciclos** de QA por feature
2. **Critério "sem achado novo"** — ciclo que só reencontra o já registrado encerra
3. **Escalada ao humano** ao estourar o teto, com o que ficou aberto
4. **Separação de poderes** — o quality-gate **não corrige nada**. Lê, reproduz, reporta. Quem julga não conserta, senão a cegueira correlacionada volta
5. **Orçamento por risco** — feature `ajuste` sem UI roda 3 dimensões, não 10

---

## Riscos honestos

| Risco | Mitigação |
|---|---|
| **Custo por ciclo** — 10 dimensões × 3 ciclos numa feature pequena é desproporcional | gate de esforço agressivo por risco |
| **Cegueira correlacionada residual** — requisito original já ambíguo pode ser interpretado igual duas vezes | forçar a listagem das **ambiguidades do `00`** como achado tipo 1 **antes** de validar qualquer coisa |
| **Relatório que ninguém lê** — `06` com 400 linhas morre | teto: veredito + tabela de achados + Matriz de Rastreabilidade. Detalhe vai em anexo ou não vai |
| **Matriz como métrica de vaidade** — "98% de cobertura" em planilha morta | ela existe só como **detector de lacuna**; ciclo sem lacuna não imprime tabela, só o veredito |
| **Quarta camada de revisão** virar burocracia | cada dimensão precisa justificar por que as 3 camadas atuais não a cobrem |

---

## Critério eliminatório

Registrado para evitar autoengano no futuro:

> **Se o quality-gate ler apenas a wiki, ele é teatro de qualidade** — gasta tokens confirmando o que já estava verde, e a recomendação passa a ser *não construir a skill* e apenas instalar 4-5 skills do `qa-skills`.
>
> A skill só se justifica **se** o `00-requisito.md` existir e for tratado como linha de base, com o PRD rebaixado a **alegação a ser testada**.

Por isso o `00-requisito.md` na `feature-wiki` é **pré-requisito**, não melhoria opcional.

**Como o `SKILL.md` v1.0.0 honra o critério**, ponto por ponto:

| Exigência do estudo | Onde está implementada |
|---|---|
| Oráculo externo, PRD rebaixado a alegação | Princípio 1 — declarado como inegociável |
| Wiki antiga sem `00` não passa em silêncio | seção "Oráculo degradado" — pede o requisito ao usuário, proíbe derivar do PRD, e estampa o aviso no topo do relatório |
| Detectar omissão silenciosa | Dimensão A + Matriz de Rastreabilidade, marcada como **"nunca pular"** |
| Roteamento em 5 destinos | seção "Classificação e Roteamento", com a tabela padrão-da-lacuna → destino |
| Não corrigir o que julga | Princípio 2 + 10 proibições explícitas |
| Convergência | Princípio 3 + seção própria: teto de 3, sem-achado-novo, dedupe contra o `06` anterior |
| Teto por risco | Gate de esforço com 3 perfis (mínimo / padrão / completo) |
| Degradação graciosa | Princípio 5 + coluna "Fallback inline" na tabela de delegação + seção "Não Verificado" no relatório |
| Dark mode inverte a regra visão × estrutura | Dimensão G, com o aviso destacado e os 3 níveis de verificação |
| MCP como confronto, não como cobertura | seção "Playwright MCP como Confronto" — 3 confrontos + a regra dos dois destinos obrigatórios |
| Não reinventar técnica de QA | tabela de delegação ao `qa-skills`, com a ressalva de instalar só as usadas |
| Relatório que não morre de tamanho | teto de ~150 linhas; "cortar detalhe, não cortar achado" |
| Matriz não virar métrica de vaidade | matriz só é impressa **se houver lacuna** |

---

## Dependências

### Obrigatórias

| Item | Motivo |
|---|---|
| `feature-wiki` ≥ 2.10.0 | precisa do `00-requisito.md` e da seção `## Natureza da Wiki` |
| App servido e acessível na `APP_URL` | validação dinâmica |
| Pest (4 ou 5) | rodar CT e CT-B existentes |

### Opcionais (a skill degrada sem elas)

| Item | Ganho |
|---|---|
| Pest 5 (`--parallel --tia`) | regressão por impacto medido |
| `pest-plugin-agent` (`--agent`) | sondagem efêmera durante a validação |
| `pest-plugin-browser` | rodar e criar CT-B |
| Playwright MCP (`--isolated --headless --caps=testing`) | inventário de elementos, tema/cor, console/rede |
| Boost MCP (`Browser Logs`, `database-query`, `search-docs`) | evidência de console e conferência de dados |
| 5-6 skills do `qa-skills` | técnica de QA (SBTM, triagem, repro) |
| PCOV ou Xdebug | pré-requisito do `--tia` |

---

## Fontes

- [petrkindlmann/qa-skills — 50 QA skills, MIT](https://github.com/petrkindlmann/qa-skills)
- [qa-skills na discussão do agentskills](https://github.com/agentskills/agentskills/discussions/369)
- [5 Must-Have QA Skills for Claude Code — QASkills.sh](https://qaskills.sh/blog/must-have-qa-skills-claude-code-2026) *(origem da citação sobre nenhuma skill rotear achados)*
- [QASkills.sh — diretório Claude Code](https://qaskills.sh/agents/claude-code)
- [Awesome Skills — categoria Testing & QA](https://awesomeskill.ai/category/testing-qa)
- [QA Engineer Agent](https://mcpmarket.com/tools/skills/qa-engineer-agent-1)
- [Best QA and Testing Skills for Claude Code — Agensi](https://www.agensi.io/learn/best-qa-testing-skills-claude-code-2026)
- [alirezarezvani/claude-skills](https://github.com/alirezarezvani/claude-skills)
- [Playwright Test Agents (planner / generator / healer)](https://playwright.dev/docs/test-agents)
- [Playwright MCP — snapshots e custo de token](https://playwright.dev/mcp/snapshots)
- [Pest — Browser Testing](https://pestphp.com/docs/browser-testing)
- [Pest — TIA](https://pestphp.com/docs/tia)
- [Laravel Boost — skills e MCP tools](https://laravel.com/docs/13.x/boost)

# feature-quality-gate — QA no Agente

> **Status: implementada — [`SKILL.md`](SKILL.md) v1.7.0.**
> Requer `feature-wiki` ≥ **4.0.0** (que traz a `feature-test-design` ≥ 1.16.0) — o porquê de cada versão mínima, e o que degrada sem cada item, está em [Dependências](#dependências).
>
> **`SKILL.md` fala com o agente; este README fala com a pessoa**: por que a skill existe, quando usar, o que ela não faz e do que depende. Procedimento, dimensões, roteamento, convergência e o template do `06` estão só no [`SKILL.md`](SKILL.md) — aqui eles são explicados e apontados, não copiados.
>
> A segunda metade é o registro da pesquisa que precedeu a implementação (2026-08-14): qual problema ela resolve, o que já existe no mercado (incluindo alternativas MIT), qual lacuna sobra, e por que essa lacuna justificou uma skill nova em vez de instalar o que estava pronto.
>
> **Validado em campo (1.5.0, 2026-09-21).** Primeira execução como sub-agente cego (`fw-qa-gate`,
> `opus`, sem Edit/Write) numa feature completa: `REPROVADO → especificação`, 8 achados — duas
> perguntas de requisito que ninguém tinha feito e **dois achados contra o próprio orquestrador**
> (número da Verificação Final sem comando que o reproduza; degradação declarada com a ferramenta
> presente). A checagem **L6** e a regra de plausibilidade do `--mutate` na dimensão K nasceram daí.
>
> **Medição.** A skill ainda não tem rodada própria no protocolo de [`experimentos/`](https://github.com/gsferro/laravel-ai-skills/blob/main/experimentos/README.md), que mede a derivação dos casos de teste. A execução acima é uso real, não rodada controlada. O custo do gate antes e depois da 1.7.0 (scripts no lugar de grep reescrito, teto por cobertura) também não foi medido — fica pendente lá. A fonte das tabelas de medição da coletânea é [`experimentos/README.md`](https://github.com/gsferro/laravel-ai-skills/blob/main/experimentos/README.md).

---

## Índice

**Uso**
- [Quando usar](#quando-usar)
- [O que ela entrega](#o-que-ela-entrega)
- [Limites](#limites)
- [Dependências](#dependências)

**Motivação e estudo**
- [TL;DR do veredito](#tldr-do-veredito)
- [O problema: onde a coletânea para](#o-problema-onde-a-coletânea-para)
- [Estudo de mercado](#estudo-de-mercado-o-que-já-existe)
- [A lacuna verificada](#a-lacuna-verificada)
- [A objeção que quase matou a skill](#a-objeção-que-quase-matou-a-skill)
- [Ganho real 1: omissão silenciosa](#ganho-real-1--omissão-silenciosa-e-a-matriz-de-rastreabilidade)
- [Ganho real 2: as 12 dimensões](#ganho-real-2--as-12-dimensões-que-as-camadas-atuais-não-cobrem)
- [Ganho real 3: roteamento](#ganho-real-3--roteamento-de-achados)
- [Achado técnico: dark mode inverte a regra](#achado-técnico-dark-mode-inverte-a-regra-de-visão--estrutura)
- [Achado técnico: MCP como confronto](#achado-técnico-playwright-mcp-como-confronto-do-ct-b)
- [Regressão condicional](#regressão-condicional-por-natureza-da-wiki)
- [Construir × reusar](#construir--reusar)
- [Convergência e separação de poderes](#convergência-e-separação-de-poderes)
- [Riscos honestos](#riscos-honestos)
- [Critério eliminatório](#critério-eliminatório)
- [Fontes](#fontes)

---

## Quando usar

A skill é a **próxima estação da esteira** — roda no step 11 da [`feature-wiki`](https://github.com/gsferro/laravel-ai-skills/blob/main/.ai/skills/feature-wiki/README.md), depois de implementar, revisar o diff (step 9) e reconciliar a wiki (step 10), com os testes verdes, **antes de abrir o PR**. Dentro da esteira a `feature-wiki` a invoca sozinha. Fora dela, peça para "validar a feature", "revisar como QA" ou "conferir se atende ao requisito" — inclusive numa feature entregue há tempo.

Não é hora de usá-la com teste vermelho (primeiro fazer passar), numa refatoração pequena e interna já coberta por teste verde (essa nem abre wiki; a larga, com `## Natureza da Wiki: refatoração`, roda o gate), nem antes de implementar (revisão de plano é o step 6). A lista completa está em [Quando Invocar](SKILL.md#quando-invocar).

O defeito que ela existe para pegar é a **omissão silenciosa**: cláusula do requisito que nunca virou passo do plano, nunca virou teste, nunca virou código. Tudo verde, feature incompleta — ver [Ganho real 1](#ganho-real-1--omissão-silenciosa-e-a-matriz-de-rastreabilidade).

## O que ela entrega

`06-relatorio-qa.md` na wiki, com veredito (`APROVADO` / `APROVADO COM DÉBITO` / `REPROVADO → destino`), achados com repro mínima e evidência, a Matriz de Rastreabilidade (**impressa só se houver lacuna**, para não virar métrica de vaidade) e uma seção **"Não Verificado"** declarando o alcance real da execução.

O veredito tem **teto por cobertura** (1.7.0): com qualquer dimensão não verificada — fora do perfil, sem app, sem MCP, oráculo degradado —, o máximo é `APROVADO COM DÉBITO`, e o débito lista cada dimensão com a causa. `APROVADO` passou a significar "tudo foi olhado e nada ficou aberto", não "o que foi olhado passou".

`NÃO APLICÁVEL` não é veredito do gate: é o `06` mínimo que a sessão da `feature-wiki` escreve, sem rodar o gate, quando pula o step 11 porque a feature não tem superfície validável.

Cada achado sai com um destino — especificação, implementação, teste, infra ou não-defeito —, e a feature volta para a estação onde o defeito nasceu. O cabeçalho do `06` diz quem julgou: um sub-agente que não viu a conversa, ou a mesma sessão que escreveu a wiki.

## Limites

- **Não corrige nada.** Lê, reproduz e reporta. Quem corrige é a estação do destino, na volta do loop: a escrita da wiki (especificação; step 4 da `feature-wiki`, com o `04` re-derivado no step 7 quando o `01` muda), a execução do passo do PRD (implementação), a `feature-test-design` (teste). O texto defasado que a dimensão L acha é corrigido no step 10 da `feature-wiki`.
- **Não substitui teste.** Encontra a lacuna; quem prova é o CT/CT-B versionado.
- **Depende do `00-requisito.md`.** Sem ele, roda em modo degradado e declara que a omissão silenciosa não foi verificada — é o [critério eliminatório](#critério-eliminatório) do estudo.
- **Mutation score não enxerga omissão.** Só muta código que existe: score alto não prova que o requisito foi entregue.
- **Profundidade proporcional ao risco.** Feature de ajuste, sem UI e de domínio comum roda 5 dimensões, não 12. O que fica de fora é declarado no relatório, não verificado — e, desde a 1.7.0, segura o veredito em `APROVADO COM DÉBITO`: `APROVADO` é inalcançável nos perfis mínimo e padrão, de propósito. Isso não bloqueia nada — a feature segue para o PR, e o débito é a declaração honesta do que não foi verificado.
- **Sem app servido, sem MCP ou sem driver de cobertura**, as dimensões que dependem deles ficam estáticas ou vão para "Não Verificado" — ver [Dependências](#dependências).
- **Metade é julgamento, e está dito onde.** A tabela *Mecânica × julgamento* do `SKILL.md` diz, por dimensão, o que é script (A, G, K1, L1, L2, L4, L6), o que é contagem ou leitura mecânica e o que é opinião do juiz. Script dá candidatos; quem decide o achado é o gate. A L4 (rules × diff) é mista: o script lista as rules que casam o diff e acusa a que não tem linha no `03`; se a rule foi aplicada, não se aplica ou foi violada continua julgamento.
- **Só no Claude Code, com o sub-agente instalado, parte da independência é construção**: o `fw-qa-gate` não tem Edit/Write (construção) e não recebe a conversa. No `Bash`, um hook `PreToolUse` da `feature-wiki` nega os comandos que alteram a árvore — cobertura **heurística**, por padrão de comando; o `git status --porcelain` de antes e de depois, que o agente devolve, é o que a sessão confere. Em outro host, quem julga é quem escreveu, e o `06` diz isso.
- **O juiz lê o `03`**, que guarda resíduo da conversa (`## Despachos`, `## Desvios do Plano`). É por desenho — a L6 confere as alegações dele —, e o perfil do hook do gate não o bloqueia.

Os limites que o [estudo de 2026-09-26](https://github.com/gsferro/laravel-ai-skills/blob/main/estudos/2026-09-26-agentskills-spec-to-spec-to-tickets.md) listou em §7.4 (itens 8 e 9 do §8) foram tratados na 1.7.0: teto por cobertura, tabela mecânica × julgamento, dimensão I restrita ao que a revisão do diff (step 9) não cobriu — lido de `## Revisão do Diff (step 9)` do `03` —, retorno com delimitadores e `git status` antes/depois. Continua aberto: o custo do gate não foi medido, e o `06` de mais de ~150 linhas ainda estoura com muitos achados.

## Dependências

| Item | Versão mínima | Para quê | Sem ele |
|---|---|---|---|
| `feature-wiki` | **4.0.0** | produz as entradas (`00`–`03`), os scripts que o gate roda (A, L1, L2, L4, L6), o lançador `pestw.cmd` e o hook do `fw-qa-gate`, e despacha esta skill no step 11 | sem `01`, a skill não roda; sem `00`, [oráculo degradado](SKILL.md#oráculo-degradado); sem os scripts, as checagens deles vão para "Não Verificado" |
| `feature-test-design` | **1.16.0** (exigida pela `feature-wiki` 4.0.0) | produz o `04`/`05` auditados — com `P-nn` na origem dos CT, que a matriz cruza — e recebe todo achado de destino 3; traz a referência do `pest-plugin-browser` que a seção de [dark mode](#achado-técnico-dark-mode-inverte-a-regra-de-visão--estrutura) deste README aponta | sem `04`, a skill não roda |
| `feature-tickets` | 1.0.0 | opcional: com `07-tickets/`, a coluna `Ticket` da matriz vem do `indice.sh --check` da `feature-tickets` — fonte única da alocação (cada `RQ`/`P-nn`, CT e CT-B em exatamente um ticket), das arestas e da fatia vertical; o gate não refaz a checagem | feature não fatiada — matriz sem a coluna; com `07-tickets/` e sem o script, essa checagem vai para "Não Verificado" |
| `bash` e `php` no PATH | — | scripts desta skill e da `feature-wiki` (PHP embutido no `.sh`) | as checagens com script vão para "Não Verificado" |
| Projeto Laravel com Pest | Pest 4 | rodar CT e CT-B existentes | — |
| App servido na `APP_URL` | — | dimensões dinâmicas | B, C, D, E, F, G, H e I ficam estáticas |
| Pest 5 | 5 | `--parallel --tia` para regressão por impacto medido | regressão sem impacto medido, declarada em "Não Verificado" |
| PCOV ou Xdebug | — | cobertura para o `--tia` e o `--mutate` | a dimensão K roda só o passo estático; a medição vai para "Não Verificado", depois de provar a ausência |
| `pest-plugin-agent` (`--agent`) | Pest 5 | sondagem efêmera durante a validação | leitura estática do código; o que não foi sondado é declarado |
| `pest-plugin-browser` | — | rodar e criar CT-B | dimensões G e H limitadas |
| Playwright MCP | — | inventário de elementos, tema/cor, console/rede | `screenshot()`, `content()` filtrado e leitura do Blade; o resto vai para "Não Verificado" |
| Boost MCP (`browser-logs`, `database-query`, `database-schema`, `search-docs`) | — | evidência de console e conferência de dados | o que dependia deles é declarado |
| Project Rules do Laravel Boost (`.ai/rules/*.md`) | `laravel/boost` 2.4.12 | L4: o `conformidade-rules.sh` da `feature-wiki` lista as rules cujo `paths:` casa o diff e acusa a que não tem linha no `03`; o gate confere a linha e o código | L4 não roda — declarada em "Não Verificado" |
| Skills do [`qa-skills`](https://github.com/petrkindlmann/qa-skills) (MIT) | — | técnica de QA delegada (SBTM, triagem, repro) | fallback inline de cada uma, registrado no relatório; não rebaixa o veredito, porque a dimensão rodou — ver [Delegação](SKILL.md#delegação-a-skills-externas) |
| Claude Code com sub-agentes | — | a skill roda como `fw-qa-gate` (`opus`, **sem Edit/Write**, hook no `Bash`), sem receber a conversa que escreveu a wiki (ver [Limites](#limites)) | roda em linha, e o `06` declara a independência degradada |

**Por que `feature-wiki` ≥ 4.0.0.** A 4.0.0 renumerou os steps (o gate é o 11; a revisão do diff, que a dimensão I lê, é o 9; a reconciliação, o 10) e trouxe o que a 1.7.0 usa: os scripts `rastreabilidade.sh` (âncora da A), `ids-ct.sh` (L1), `citacoes.sh` (L2), `conformidade-rules.sh` (L4) e `checkbox-sem-evidencia.sh` (L6), a seção `## Revisão do Diff (step 9)` do `03` (dimensão I), o `pestw.cmd` como arquivo (dimensão K no Windows), o hook `guarda-subagente.sh` do `fw-qa-gate`, as `P-nn` em `## Premissas` e as `## Perguntas ao Solicitante` no `00` (linhas `P-nn` da matriz; `RQ` aberta), e o `wikis/glossario.md` (L7). As peças mais antigas continuam: o `00-requisito.md` desde a 2.10.0, a `## Superfície Livewire` do `02` desde a 3.3.0, a `## Despachos` do `03` desde a 3.4.0. Com uma versão anterior, o que falta vai para "Não Verificado" — e segura o veredito no teto.

### Instalação do sub-agente (Claude Code)

A definição do agente vem nesta skill, em [`agents/fw-qa-gate.md`](agents/fw-qa-gate.md), para o
`boost:add-skill` instalá-la junto. **O Claude Code só lê `.claude/agents/`**, então depois de
instalar ou atualizar as skills copie os agentes de toda a esteira — uma vez, e de novo a cada
atualização:

```bash
mkdir -p .claude/agents
cp .ai/skills/*/agents/*.md .claude/agents/
```

No PowerShell:

```powershell
New-Item -ItemType Directory -Force .claude\agents | Out-Null
Copy-Item -Force .ai\skills\*\agents\*.md .claude\agents\
Get-ChildItem .claude\agents\fw-*.md        # cinco arquivos
```

Espelhar as skills em `.claude/skills/` só é preciso sem `boost.json`: com ele, o `boost:update` cria
cada `.claude/skills/<skill>` como symlink, e copiar por cima falha. Os dois casos estão em
[Como Instalar no Claude Code](https://github.com/gsferro/laravel-ai-skills/blob/main/README.md#-como-instalar-no-claude-code),
no README da coletânea.

Sem a cópia, a `feature-wiki` não encontra `fw-qa-gate` e despacha um `general-purpose` com
`model: opus` — funciona, mas com Edit/Write disponíveis e sem o hook: o "não corrige nada" passa a ser só instrução, também para a edição de arquivo.

O hook do agente procura `feature-wiki/scripts/guarda-subagente.sh` em `.ai/skills/`,
`.claude/skills/` e `~/.claude/skills/`. Sem a `feature-wiki` instalada, ele **falha fechado**:
nega toda ferramenta, o agente devolve só a mensagem do hook, e a sessão cai no mesmo fallback.

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

Pesquisa feita antes de qualquer linha de desenho (pesquisa feita em 2026-08-14).

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

Todas as opções acima produzem **mais teste** ou **mais relatório**. Nenhuma fecha o ciclo. Um post do blog do QASkills.sh (ver [Fontes](#fontes)) é explícito ao descrever o estado da arte:

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

> **Nomenclatura.** O termo adotado nesta coletânea — em documentos, no `06-relatorio-qa.md` e no `SKILL.md` — é **Matriz de Rastreabilidade**. A sigla **RTM** (*Requirements Traceability Matrix*) fica disponível como referência quando o contexto pedir: vocabulário de QA formal (ISTQB), conversa com time de qualidade, ou auditoria de setor regulado, onde o artefato é conhecido por esse nome. Em prosa corrente, escrever por extenso.

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

## Ganho real 2 — As 12 dimensões que as camadas atuais não cobrem

Cada dimensão precisou responder por que as três camadas de revisão existentes não a cobrem. Aqui fica o porquê e um exemplo do que ela pega; o "como verificar" de cada uma está em [As 12 Dimensões](SKILL.md#as-12-dimensões).

| # | Dimensão | Por que escapa hoje | Exemplo do que pega |
|---|---|---|---|
| **A** | Cobertura do requisito | CT só falha no especificado | cláusula sem plano/teste/código |
| **B** | Fronteiras e dados | CT cobre o caso do PRD, não os vizinhos | `-1`, string de 500 chars, upload de 0 byte, 29/02 |
| **C** | Matriz de permissão | o CT de autorização testa **1** papel | 3 papéis × 4 ações = 12 células; o CT cobre 1 |
| **D** | Observabilidade real | o PRD **manda** logar `[Classe@Método]` — **quem confere?** | log fora do padrão da `feature-wiki`; **PII vazando no context** |
| **E** | Performance | CT passa em 3 queries ou em 300 | N+1, query sem índice, `->get()` onde cabia paginação |
| **F** | UX de erro | `assertSee('erro')` passa com mensagem inútil | "Erro ao processar" em vez de "CPF já inscrito na turma X" |
| **G** | Tema e cor (dark mode) | **CT-B passa com texto invisível** — ver [achado técnico](#achado-técnico-dark-mode-inverte-a-regra-de-visão--estrutura) | **texto branco em fundo branco: `assertSee()` PASSA** |
| **H** | Acessibilidade | só se alguém escreveu o CT-B | teclado, foco, contraste, `alt` |
| **I** | Superfície nova de segurança | fora do escopo do CT | IDOR, mass assignment, rota sem `can:` |
| **J** | Regressão adjacente | TIA diz quais testes o diff afetou, não o que **não tinha teste** | regra antiga silenciada pela evolução — só em wiki de evolução/correção/ajuste/refatoração, ou `nova` que toca infra compartilhada |
| **K** | Adequação da suíte (a suíte pega defeito?) | ninguém pergunta se o teste **falharia** diante de implementação errada; 100% de linha é compatível com zero assertion útil | teste sem oráculo, `assertOk()` sozinho; mutante que sobrevive |
| **L** | Consistência documental (wiki × código × docs × rules × glossário) | quem escreveu o texto o lê como certo; o step 10 registra desvios só no `03` | PRD/ADR afirmando o que o código não faz; ID de CT só no teste; rule do projeto violada no diff; docs pt × en divergentes; frase em doc sem `RQ` de origem; número da Verificação Final que nenhum comando reproduz; termo usado com sentido diferente do `wikis/glossario.md` |

A dimensão **D** é auto-referente e reveladora: a `feature-wiki` exige log em toda etapa de execução, com channel dedicado e context estruturado — e **nada no ciclo atual verifica se isso aconteceu**. O quality-gate fecha o laço da própria skill principal.

A dimensão **L** (1.2.0) nasceu de uma medição: numa feature real, depois da reconciliação da `feature-wiki` (hoje o step 10) "concluída", uma revisão independente achou 31 itens, 27 deles texto defasado — PRD, ADR, `04`, docs e rules. Quem escreveu o texto o lê como certo; a auditoria tem de ser de quem não escreveu.

---

## Ganho real 3 — Roteamento de achados

A taxonomia que não existe no mercado. Cinco destinos, não dois: **especificação**, **implementação**, **teste**, **infra** e **não-defeito**. Achado que só diz "está errado" devolve o problema sem dizer em que estação ele nasceu; roteado, requisito ambíguo volta para a escrita da wiki, e não para o código.

**A Matriz de Rastreabilidade alimenta o roteamento.** O formato da lacuna determina o destino — deixa de ser opinião do agente e passa a ser consequência de uma célula vazia.

O destino **teste** é o mais valioso e o mais fácil de errar: invertida a ordem (corrigir antes de ter o CT que falha), perde-se a prova e o caso reaparece na feature seguinte.

E a decisão **bloquear × registrar** é por severidade, senão o loop nunca fecha por cosmética: o que não bloqueia entra num ledger de débito na wiki (mesma ideia do `ponytail-debt`).

A taxonomia completa — severidade, os cinco destinos com o que fazer em cada um, a prioridade entre eles e a tabela padrão da lacuna → destino — está em [Classificação e Roteamento](SKILL.md#classificação-e-roteamento).

---

## Achado técnico: dark mode inverte a regra de visão × estrutura

O `pest-plugin-browser` tem ferramentas para tema, screenshot e acessibilidade. O que cada uma faz e não faz — inclusive o alcance da regra de contraste do axe — está numa fonte só: `{skills}/feature-test-design/references/pest-plugin-browser.md`, onde `{skills}` é o primeiro dos três diretórios — `.ai/skills/` (Boost), `.claude/skills/` (espelho local), `~/.claude/skills/` (global) — que contém a skill citada.

O que é da dimensão G é a consequência: dentro do plugin, nenhuma assertion barata prova cor. Os casos graves — texto invisível no tema escuro, classe sem par `dark:`, baseline de screenshot criado com o defeito — passam, e estão tabelados na referência.

A cobertura escolhida vai do mais barato ao mais caro: grep estático de classe de cor sem par `dark:` (o melhor custo-benefício), CT-B versionado no tema escuro e inspeção visual pelo Playwright MCP — o único caminho para "ilegível". Como o jeito de forçar o tema no teste muda com o projeto, a dimensão começa detectando o mecanismo de tema. Desde a 1.7.0 a detecção e o nível estático são um script, `scripts/dark-mode.sh`, que reconhece também o Tailwind 4 (`@custom-variant dark`, e o `prefers-color-scheme` que ele aplica por padrão quando o projeto só usa `dark:`), o Flux e o painel Filament — os greps da 1.6.0 só viam o Tailwind 4 quando o projeto declarava `@custom-variant dark`. Os comandos estão na [dimensão G](SKILL.md#g--tema-e-cor-dark-mode).

> **Correção de ênfase relevante.** Na análise do Playwright MCP a coletânea defende a árvore de acessibilidade contra o screenshot, pelo custo em token (os números estão na [dimensão G](SKILL.md#g--tema-e-cor-dark-mode)). **Para defeito de cor isso se inverte**: a árvore é justamente cega ao problema, porque o texto *está* lá. Cor é o caso em que a visão ganha da estrutura — e a skill precisa dizer isso explicitamente para o agente não aplicar a regra errada.

---

## Achado técnico: Playwright MCP como confronto do CT-B

A coletânea já define que **o `pest-plugin-browser` atesta e o Playwright MCP observa**. Para o quality-gate, o MCP habilita três confrontos: elementos interativos da tela × elementos que o CT-B exercita; UI renderizada × tabela `## Superfície de UI` do PRD; e tema, console e rede.

O primeiro é o mais valioso: elemento interativo que a tela oferece e nenhum CT-B exercita vira achado — é escopo ou scope creep? O exemplo está em [`references/delegacao-e-playwright.md`](references/delegacao-e-playwright.md).

Isso é literalmente "confronto do que entrou no CT-B", e nenhuma ferramenta de teste faz — teste só sabe o que você escreveu nele.

O que mantém a disciplina é que sessão MCP não é cobertura: todo achado do MCP vira CT-B novo ou achado roteado, nunca "fica no relatório". A configuração, o alvo permitido e o que nunca entra em teste estão em [Playwright MCP como Confronto](SKILL.md#playwright-mcp-como-confronto).

---

## Regressão condicional por natureza da wiki

Rodar regressão em toda feature é caro e desnecessário. O gatilho certo é a **natureza da wiki**, declarada no `01` (`## Natureza da Wiki`: nova, evolução, correção, ajuste ou refatoração, com a wiki ancestral quando não é nova), junto com o campo **Toca infra compartilhada?**. Wiki nova que não toca infra compartilhada valida só a feature; as demais — inclusive a nova que toca — medem o impacto por TIA, rodam por ID os CT/CT-B da ancestral (ou das features que consomem a infra tocada) e aplicam RCRCRC aos arquivos que as duas tocaram.

Rodar os CT da ancestral **por ID** é o que garante que a evolução não silenciou uma regra antiga — algo que o TIA só pega se o teste existir.

Os passos e comandos estão em [Regressão Condicional](SKILL.md#regressão-condicional).

---

## Construir × reusar

**Construir apenas a camada que é nossa; delegar a técnica ao que já existe em MIT.**

### Delegar ao [`qa-skills`](https://github.com/petrkindlmann/qa-skills)

O mapa necessidade → skill, com o fallback inline de cada uma, está em [`references/delegacao-e-playwright.md`](references/delegacao-e-playwright.md); as obrigações, em [Delegação a Skills Externas](SKILL.md#delegação-a-skills-externas). Aqui fica o papel que o estudo deu a cada delegação:

| Skill | Papel |
|---|---|
| `risk-based-testing` | define a profundidade por risco |
| `exploratory-testing` | SBTM, charters time-boxed, heurísticas |
| `ai-bug-triage` | **alimenta o roteamento** dos 5 destinos |
| `bug-reproduction` | achado sem repro não é reportável |
| `ai-qa-review` | "o CT-01 testa o que diz testar?" |
| `test-reliability` | evita roteamento errado da causa (c) |
| `test-suite-curation` | só quando tipo ≠ nova |
| `quality-postmortem` | defeito escapado: fora do loop, sob demanda |
| `release-readiness` | *(futuro)* gate de deploy; cobriria a 4ª etapa da esteira |

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

1. **Não instalar as 50.** A biblioteca inteira é inflação de contexto — o mesmo problema combatido nas Project Rules. O mapa em [`references/delegacao-e-playwright.md`](references/delegacao-e-playwright.md) lista as que a skill usa; copie só essas pastas para `.ai/skills/`. *(A confirmar se o instalador permite seleção; se não, cópia manual.)*
2. **Degradar graciosamente.** O quality-gate precisa funcionar sem elas — mesmo padrão do Playwright MCP na `feature-wiki`. Cada delegação tem fallback inline em [`references/delegacao-e-playwright.md`](references/delegacao-e-playwright.md).

---

## Convergência e separação de poderes

"Loop engineering" sem regra de parada é loop infinito. Por isso o desenho fixou, desde o estudo, um teto de ciclos, o critério "sem achado novo encerra", a escalada ao humano quando o teto estoura e um orçamento de dimensões por risco. E fixou a separação de poderes: o quality-gate **não corrige nada**, porque quem julga e conserta volta a ter cegueira correlacionada.

Os valores e o procedimento estão nos [Princípios Inegociáveis](SKILL.md#princípios-inegociáveis) e em [Convergência do Loop](SKILL.md#convergência-do-loop).

---

## Riscos honestos

| Risco | Mitigação |
|---|---|
| **Custo por ciclo** — 12 dimensões × 3 ciclos numa feature pequena é desproporcional | gate de esforço agressivo por risco |
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

**Como o `SKILL.md` honra o critério**, ponto por ponto:

| Exigência do estudo | Onde está implementada |
|---|---|
| Oráculo externo, PRD rebaixado a alegação | Princípio 1 — declarado como inegociável |
| Wiki antiga sem `00` não passa em silêncio | seção "Oráculo degradado" — pede o requisito ao usuário, proíbe derivar do PRD, e estampa o aviso no topo do relatório; vale também para `00` sem `RQ` e para `00` derivado do PRD |
| Detectar omissão silenciosa | Dimensão A + Matriz de Rastreabilidade, marcada como **"nunca pular"** |
| Roteamento em 5 destinos | seção "Classificação e Roteamento", com a tabela padrão-da-lacuna → destino |
| Não corrigir o que julga | Princípio 2 + 13 proibições explícitas; no Claude Code, agente sem Edit/Write e com hook no Bash |
| Não aprovar o que não olhou | teto por cobertura: dimensão não verificada, por qualquer causa, segura o veredito em `APROVADO COM DÉBITO` |
| Convergência | Princípio 3 + seção própria: teto de 3, sem-achado-novo, dedupe contra o `06` anterior |
| Teto por risco | Gate de esforço com 3 perfis (mínimo / padrão / completo) |
| Degradação graciosa | Princípio 5 + coluna "Fallback inline" na tabela de delegação + seção "Não Verificado" no relatório |
| Dark mode inverte a regra visão × estrutura | Dimensão G, com o aviso destacado e os 3 níveis de verificação |
| MCP como confronto, não como cobertura | seção "Playwright MCP como Confronto" — 3 confrontos + a regra dos dois destinos obrigatórios |
| Não reinventar técnica de QA | tabela de delegação ao `qa-skills`, com a ressalva de instalar só as usadas |
| Relatório que não morre de tamanho | teto de ~150 linhas; "cortar detalhe, não cortar achado" |
| Matriz não virar métrica de vaidade | matriz só é impressa **se houver lacuna** |

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

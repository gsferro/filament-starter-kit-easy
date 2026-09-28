---
name: feature-wiki
description: >
  Cria a wiki de uma feature Laravel antes de implementá-la. Invoque SEMPRE ao iniciar
  implementação de qualquer feature nova — card, ticket, requisito ou PRD —, antes de qualquer
  php artisan make:*, e de novo quando o pedido crescer no meio da implementação (vira Adendo
  no 00). Grava em wikis/specs/{branch}/{feature}/ o requisito bruto imutável decomposto em
  cláusulas RQ (00), o plano de ação (PRD) executável por outro agente (01), as decisões
  arquiteturais em ADR (02) e o progresso com evidência (03). Os casos de teste (04/05) são
  delegados à feature-test-design, que os deriva do requisito, não do plano. Conduz a feature até
  o PR: pesquisa com search-docs do Laravel Boost, revisão do plano, revisão do diff por quem não
  implementou assim que os testes passam, reconciliação wiki × código, quality gate
  (feature-quality-gate) e candidatos a Project Rule (requirement-to-rule).
  Para Laravel, Livewire, Filament e Pest; no Claude Code, despacha sub-agentes roteados por
  modelo e por cegueira.
license: MIT
compatibility: >-
  Projeto Laravel com git; bash e php para scripts/. Laravel Boost com MCP recomendado: search-docs no step 3 e
  record-rule (Boost >= 2.4.12) no step 12. Pest 4 ou 5; assume Pest 5 (--tia e --mutate na
  Verificação Final, --agent na implementação) e cai para --filter no Pest 4. Sub-agentes do Claude Code opcionais: sem eles tudo
  roda em linha e a perda de independência é declarada no 03. Plugins Ponytail e Caveman
  opcionais; sem Ponytail o step 6 vira passe manual registrado no 03.
metadata:
  version: "4.0.0"
  requires: "feature-test-design>=1.16.0; feature-quality-gate>=1.7.0; laravel/boost>=2.4.12"
---

# Feature Wiki — Documentação Antes de Implementar

> ## O gate que mais pega defeito é o step 9 — e ele roda assim que os testes passam
>
> Esta skill tem oito gates. Sete deles leem **o plano, o requisito ou a tela**. Um só lê **o
> diff**, e é o [step 9 — Revisão de Código do Diff](#9-revisão-de-código-do-diff-obrigatório-logo-após-os-testes-passarem-e-antes-da-reconciliação),
> com `/code-review` por quem não implementou.
>
> Por que ele está no topo — a medição de 2026-09-17: [`references/casos-medidos.md`](references/casos-medidos.md#topo--o-step-9-é-o-gate-mais-produtivo-2026-09-17).
>
> **Não trate o step 9 como formalidade.** Se o orçamento apertar, corte cenário redundante, não este
> gate. E ele roda **antes** da reconciliação (step 10), não depois: cada achado confirmado muda
> código, premissa e CT — reconciliar antes de revisar é reconciliar duas vezes.
>
> **Quem revisa não pode ser quem implementou.** No Claude Code o `/code-review` roda em sub-agente
> isolado e o passe de eixos vai para um sub-agente sem o PRD — cego por construção nas ferramentas de
> arquivo com o hook instalado; no Bash, heurística; no fallback, prompt. Ver [Execução e Delegação](#execução-e-delegação-claude-code--roteamento-por-modelo-e-por-cegueira).

## Glossário

| Sigla | Significado |
|-------|-------------|
| **RQ** | Cláusula de requisito — unidade numerada da decomposição do `00-requisito.md` |
| **P-nn** | Premissa — o que a feature passa a assumir sem que o solicitante tenha escrito; seção `## Premissas` do `00` |
| **Qn** | Pergunta da entrevista — sequência **única** na feature, para as três raias; a de requisito vai para `## Perguntas ao Solicitante` do `00`, a de desenho para `## Decisões de Desenho` do `01` ou a ADR. Sub-agente numera `Q?1, Q?2…` (provisória); a sessão renumera ao gravar |
| **PRD** | Product Requirements Document — plano de ação detalhado |
| **ADR** | Architecture Decision Record — registro de decisão arquitetural |
| **CT** | Caso de Teste — especificação de um cenário de teste (backend) |
| **CT-B** | Caso de Teste de Browser — cenário E2E validado em navegador real |
| **DB** | Database — banco de dados |
| **FK** | Foreign Key — chave estrangeira |
| **TIA** | Test Impact Analysis — engine do Pest 5 que roda só os testes afetados |
| **estudo §n** | Seção de [`estudos/2026-09-26-agentskills-spec-to-spec-to-tickets.md`](https://github.com/gsferro/laravel-ai-skills/blob/main/estudos/2026-09-26-agentskills-spec-to-spec-to-tickets.md), de onde vêm as regras da 4.0.0 |
| **`{base}`** | Branch de destino do PR (`main`, salvo indicação do usuário); registrada no cabeçalho do `03` |
| **`{skills}`** | Diretório onde as skills estão instaladas: o primeiro dos três — `.ai/skills/` (Boost), `.claude/skills/` (espelho local), `~/.claude/skills/` (global) — que **contém a skill citada**. Não basta o diretório existir: `.ai/skills/` pode existir sem ela |

## Índice

- [Quando Invocar](#quando-invocar) · [Execução e Delegação (Claude Code)](#execução-e-delegação-claude-code--roteamento-por-modelo-e-por-cegueira)
- **Fluxo** (uma ordem só): [0. Requisito](#0-capturar-o-requisito--primeiro-ato) · [1. Branch](#1-descobrir-branch-e-estrutura-de-pasta) · [2. Nome](#2-definir-nome-da-feature) · [3. Pesquisa](#3-pesquisa-e-contexto-obrigatório-antes-de-escrever) ([Superfície Livewire](#superfície-livewire-obrigatório-em-toda-feature-que-cria-página-widget-ou-componente), [`search-docs`](#documentation-api-do-boost-search-docs)) · [4. `00`–`03`](#4-criar-os-arquivos) ([entrevista em três raias](#entrevista-em-três-raias-obrigatória-no-step-4), [path por arquivo](#path-e-número-por-arquivo)) · [5. Revisão profunda](#5-revisão-profunda-pós-escrita-obrigatório) ([classe irmã](#varredura-da-classe-irmã-obrigatória-para-toda-classe-nova)) · [6. Ponytail](#6-auditoria-da-wiki-com-ponytail-review-obrigatório) · [7. `04`/`05`](#7-derivar-os-casos-de-teste-obrigatório-depois-do-ponytail) · [8. Tickets (condicional)](#8-fatiar-em-tickets-condicional) · [Implementação](#implementação-sem-número) · [9. Revisão do diff](#9-revisão-de-código-do-diff-obrigatório-logo-após-os-testes-passarem-e-antes-da-reconciliação) · [10. Reconciliação](#10-pós-implementação-e-reconciliação-obrigatório-antes-do-pr) · [11. Quality gate e PR](#11-quality-gate-e-abertura-do-pr-obrigatório-antes-do-pr) · [12. Rules](#12-candidatos-a-rule-de-projeto-obrigatório-depois-do-veredito)
- **Arquivos**: [00](#arquivo-00-requisito--fonte-da-verdade) ([Adendo](#adendo-ao-requisito--quando-o-pedido-cresce-durante-a-implementação)) · [01](#arquivo-01-plano-de-ação-prd) · [Padrão de Log](#padrão-de-log--classemétodo-mensagem) · [02](#arquivo-02-decisões-arquiteturais-adr) · [03](#arquivo-03-progresso--tracking) · [Glossário do projeto](#glossário-do-projeto) · [04 e 05](#arquivos-04-e-05-casos-de-teste--delegados-à-feature-test-design) ([Playwright MCP](#playwright-mcp-na-validação-opcional--ferramenta-de-observação))
- [Pest 5](#execução-de-testes-com-pest-5) · [Citações de código](#citações-de-código--arquivosímbololinha) · [Checklist Final](#checklist-final-da-skill) · [Skills Companheiras](#skills-companheiras)

**`references/`** — só entram no contexto quando lidas. Cada step diz qual abrir, **antes** da ação;
o checklist final exige declarar quais foram abertas.

| Arquivo | Lida em | Fonte única de |
|---|---|---|
| [`template-00-requisito.md`](references/template-00-requisito.md) | step 4; Adendo; Premissa | tabela de regimes, template do `00`, do Adendo e da `## Premissas` |
| [`entrevista-tres-raias.md`](references/entrevista-tres-raias.md) | step 4; steps 5, 9, 11 e o quiz da `feature-tickets` quando geram pergunta | formato ❓/➡️, tabela das raias, exemplo de rodadas, premissa de comportamento, perguntas-semente, confronto código × afirmação |
| [`glossario.md`](references/glossario.md) | steps 3–5, quando um termo é decidido | template de `wikis/glossario.md` e a pergunta de conflito |
| [`template-01-plano.md`](references/template-01-plano.md) | step 4 | template do `01` e skills citáveis no PRD |
| [`template-02-adr.md`](references/template-02-adr.md) | step 4 | template do `02`, com a linha dos três portões |
| [`template-03-progresso.md`](references/template-03-progresso.md) | step 4; steps 7, 8, 9 e 12; `## Despachos` | template do `03` |
| [`padrao-de-log.md`](references/padrao-de-log.md) | step 4 (logs do `01`) | por que o padrão, anatomia, os sete níveis, contexto estruturado, exemplos, trait de logging |
| [`pesquisa-step-3.md`](references/pesquisa-step-3.md) | step 3; pré-9 | três origens, formato e greps da Superfície Livewire; cobertura e lacunas do `search-docs` |
| [`roteamento-e-despacho.md`](references/roteamento-e-despacho.md) | todo despacho | tabela de rotas (com o perfil do hook e o `maxTurns`), quadro de despacho, mapa por step |
| [`delegacao-casos-de-teste.md`](references/delegacao-casos-de-teste.md) | step 7; implementação; step 10 | contrato da delegação, do `executor-ct` e dos CT-B |
| [`playwright-mcp.md`](references/playwright-mcp.md) | step 3; loop do CT-B; step 10 | configuração, tools e fallback do Playwright MCP |
| [`pest-5.md`](references/pest-5.md) | step 3; implementação; step 10 | instalação, TIA, `--agent`, por quê e uso do `scripts/pestw.cmd` |
| [`citacoes-de-codigo.md`](references/citacoes-de-codigo.md) | steps 3, 4, 5 e 10 | tabela de path e número por arquivo, classes de erro, exemplos e o que o `scripts/citacoes.sh` confere |
| [`ponytail-caveman.md`](references/ponytail-caveman.md) | início da sessão | fronteira do Caveman, ativação do trio |
| [`estrutura-criada.md`](references/estrutura-criada.md) | step 4 | arquivos extras `05-*` e árvore de exemplo |
| [`casos-medidos.md`](references/casos-medidos.md) | quando um step aponta | os casos que originaram as regras do corpo |

Os fatos do `pest-plugin-browser` não têm cópia nesta skill, fora os dois que o step 3 confere: a fonte única é
`{skills}/feature-test-design/references/pest-plugin-browser.md`.

**`scripts/`** — conferências do step 10 (também rodadas pelo quality gate), o hook `guarda-subagente.sh`
dos agentes e o `pestw.cmd`; uso e exemplo de falha no cabeçalho de cada um. Silêncio + exit 0 = OK;
exit 1 = uma linha `arquivo:linha: mensagem` por achado; exit 2 = uso ou ambiente, nunca "limpo". Um
tema, um script (estudo §7.4): o grep reescrito no step 10 e no gate divergia.

## Ordem de Leitura para o Agente Implementador

Ao implementar, o agente deve ler os arquivos nesta ordem:
1. **`00-requisito.md`** — entende o que foi **pedido** (fonte da verdade, não o plano)
2. **`01-plano-acao.md`** — entende o que fazer e em que ordem
3. **`04-casos-de-teste.md`** — entende como validar cada passo
4. **`05-casos-de-teste-browser.md`** — se existir: entende como validar a UI e o fluxo do usuário
5. **`02-decisoes-arquiteturais.md`** — entende as restrições e justificativas
6. **`03-progresso.md`** — marca o que já foi feito e retoma de onde parou

Feature fatiada no step 8: quem executa um ticket lê o arquivo do ticket e só o que ele aponta — ver [Implementação](#implementação-sem-número).

---

## Quando Invocar

- Sempre que o usuário pedir para implementar uma feature nova
- Ao iniciar qualquer card/ticket/task de desenvolvimento
- Antes de qualquer `php artisan make:*` ou criação de código
- Quando o usuário pedir para arquitetar ou analisar uma feature antes de implementá-la

### Quando NÃO Invocar

- **Typo fixes**: correções de texto, mensagens, labels
- **Mudanças triviais**: ajustes de config simples, tweak de CSS isolado, mudança de 1-2 linhas sem nova lógica
- **Refatoração pequena e interna, já coberta por teste verde**: renomear variável, extrair método, sem mudança de comportamento
- **Bump de dependência**: atualizar versão de package sem mudança de API
- **Adição de seeders/migrations isoladas**: sem lógica de negócio associada

> **Critério**: se a mudança não adiciona nova lógica de negócio, não altera fluxo de dados e não cria novos arquivos de código → não precisa de wiki.
> **Bug fix com nova lógica**: se o fix introduz nova regra de negócio, novo estado ou novo fluxo → invocar a wiki.
> **Refatoração larga** (renomear coluna, retipar símbolo compartilhado) **invoca a wiki**, com `## Natureza da Wiki: refatoração`, e o step 8 sugere a `feature-tickets` (expand → migrate em lotes → contract) — feita de uma vez, tocava migration, model, factories, telas e testes numa sessão só (estudo §4.2).

## Execução e Delegação (Claude Code) — roteamento por modelo e por cegueira

> Vale quando o host expõe sub-agentes (Claude Code: ferramenta `Agent`, agentes em
> `.claude/agents/`). Em host sem sub-agente (Windsurf, Cursor, Copilot) tudo roda em linha, e a
> degradação é **declarada** no `03-progresso.md` → `## Despachos`: *"Sem despacho — host sem
> sub-agente"*. O que se perde não é só custo: perde-se a **independência** dos gates, e o leitor
> do PR precisa saber disso.

A sessão principal **orquestra, decide e audita**. Tarefa de volume e tarefa de julgamento
independente **não rodam em linha** — vão para sub-agente pela ferramenta `Agent`, com o modelo
roteado. Delegar é o padrão; rodar em linha é exceção declarada.

Princípio: **gerar barato, raciocinar sob demanda, auditar caro — e julgar às cegas.**

A validação em campo deste modelo (2026-09-21, feature completa): [`references/casos-medidos.md`](references/casos-medidos.md#validado-em-campo--2026-09-21-feature-completa-no-demo-wiki).

### Dois eixos de roteamento, não um

| Eixo | Pergunta | Decide |
|---|---|---|
| **Complexidade** | quanto raciocínio a tarefa exige? | o **modelo** (`haiku` → `sonnet` → `opus`) |
| **Cegueira** | o que o executor **não pode ter visto** para o resultado valer como prova? | o **contexto** que o sub-agente recebe — e, por consequência, que a tarefa **não pode** rodar em linha |

O segundo eixo quebra a **cegueira correlacionada** (o mesmo agente lê o requisito, escreve plano,
teste, código e veredito, e erra coerentemente): sub-agente nasce **sem** o contexto da sessão, então
*"por quem não implementou"* (step 9), *"por quem não derivou"* (step 7) e *"por quem não escreveu a
wiki"* (step 11) viram **construção** nas ferramentas de arquivo, com o hook `guarda-subagente.sh`;
no Bash, heurística mais a comparação do `git status --porcelain`; no fallback `general-purpose`,
prompt (estudo §7.1 T6). Tarefa cuja validade depende de cegueira **nunca** roda em linha.

### Rotas

Antes de montar o quadro de despacho, leia [`references/roteamento-e-despacho.md`](references/roteamento-e-despacho.md#rotas):
a tabela de rotas — modelo, uso nesta esteira e ferramentas de `mecânico`, `construtor`, `analista`,
`revisor-diff`, `adversário-ct`, `qa-gate`, `executor-ct`, `executor-ctb` e sessão principal.

**Tier é o conceito portável; o alias é a implementação Claude**: `haiku` = econômico, `sonnet` =
intermediário, `opus` = topo — outro provedor mapeia os três; `## Despachos` registra o modelo **efetivamente** usado.

**Regras da rota `mecânico`** (casos em [`references/casos-medidos.md`](references/casos-medidos.md#rota-mecânico--2026-09-21)):

- **Um item por despacho** — um grep em lote com a tabela pronta, uma conversão, um espelho. Lote
  misto (converter + mover + preencher tabela) vai para `construtor`
- **FQCN em grep vai com `grep -F`** — o escape das barras produziu um *"sem ocorrências"* falso
- **`Explore` (built-in) só para feature grande.** Para o resto, `mecânico` com **trechos**, não
  arquivos. **Sem `Explore`** (estudo §7.2): `mecânico` com trechos, um item por despacho — ou
  `general-purpose`/`sonnet` quando o mapeamento exige escolher o relevante; `## Despachos` registra o fallback

Como obter as rotas, em ordem de preferência:

1. **Agentes do projeto** em `.claude/agents/` — se o projeto já tem `mecanico`/`construtor`/
   `analista` (ou equivalentes), usá-los pelo `subagent_type`
2. **Agentes da esteira** — os cinco que carregam **cegueira e restrição de ferramenta**, cada um na
   pasta `agents/` da skill dona (`fw-revisor-diff`, `fw-executor-ct`, `fw-executor-ctb` aqui;
   `fw-adversario-ct` na `feature-test-design`; `fw-qa-gate` na `feature-quality-gate`). O Claude Code
   **não lê `.ai/skills/*/agents/`**: `cp .ai/skills/*/agents/*.md .claude/agents/` uma vez e a cada
   atualização. O hook `PreToolUse` de cada um (`guarda-subagente.sh`, perfil na [tabela de rotas](references/roteamento-e-despacho.md#rotas))
   nega por construção o Read/Grep/Glob/Edit/Write fora do contrato — no Bash, heurístico — e, sem o
   script, nega tudo (*"guarda-subagente.sh nao encontrado"*) → item 3. Os agentes só carregam de
   `.claude/agents/` do diretório onde a sessão foi aberta: antes do primeiro despacho,
   `ls .claude/agents/fw-*.md`; se faltar, item 3, e `## Despachos` registra *"fallback
   `general-purpose`/{model} — agente `fw-…` indisponível"* — perde a restrição de ferramenta e o
   hook, não a cegueira de contexto, que vai no prompt
3. **`general-purpose` com `model` explícito** — funciona em qualquer projeto sem instalar nada.
   **NUNCA despachar `general-purpose` sem `model`**: ele herda o modelo caro da sessão e o
   roteamento deixa de existir

O parâmetro `model` da chamada `Agent` sobrepõe o modelo do arquivo do agente quando a tarefa fugir
do padrão — e a sobreposição vai no quadro, com o motivo. Sub-agente que precisa seguir uma skill
companheira recebe o **path do `SKILL.md`** dela para ler e seguir; não se resume a skill no prompt.

### Paralelo é o padrão, sequência é exceção

Tarefas independentes disparam **juntas, no mesmo lote**. Sequência só quando a saída de uma é
entrada da outra — e aí o quadro declara qual dependência forçou a fila.

Duas restrições desta esteira ao paralelo:

- **Nunca dois construtores no mesmo arquivo.** Passos do PRD que tocam o mesmo arquivo rodam em
  sequência; paralelo só com conjuntos de arquivos **disjuntos**, declarados no quadro
- **Cegueira vence paralelo.** O revisor do diff só dispara depois que o último construtor
  entregou — não por dependência de saída, mas porque o diff que ele lê tem de ser o diff final

### Todo disparo é visível — o quadro de despacho

Antes de despachar (mesmo um agente só), mostrar o quadro; no retorno, reportar **contra o mesmo
quadro**: o que cada agente entregou, o que falhou, o que foi refeito. Nada de delegação silenciosa.

Formato do quadro (colunas e exemplo): [`references/roteamento-e-despacho.md`](references/roteamento-e-despacho.md#formato-do-quadro-de-despacho).

O quadro **vai para o `03-progresso.md`**, seção `## Despachos`, uma linha por disparo com o
resultado, o **`Custo`** (tokens · duração que o host reporta; `—` se não reporta) e a auditoria do
retorno — o único registro de **qual modelo** fez o quê e de quanto custou operar.

### Rodam em linha, sem despacho

Tarefa de 1–2 passos; interação direta com o usuário; decisão que depende do contexto imediato da
conversa; edição cirúrgica em arquivo com forte interdependência; captura **verbatim** do requisito
(copiar não é tarefa, e passar a fonte por um resumo de sub-agente seria alterá-la). Nesses casos,
declarar **"Sem despacho — motivo"**, citando a exceção.

Exceção que **não** vale: *"a tarefa é pequena, então reviso eu mesmo."* Tamanho não compra
cegueira. O passe de eixos do step 9, a revisão adversarial (step 7) e o step 11 vão para sub-agente **sempre**.

### Auditoria do retorno

A sessão principal confere o retorno de **todo** lote antes de usá-lo:

- **presença** — o agente entregou tudo o que o quadro pedia, no formato fixo?
- **integridade** — nenhuma proibição do contrato violada: código de aplicação intacto, `00`
  intacto, `04` intacto (`git status` e `git diff --stat` antes e depois do lote)
- **amostragem** — 2–3 itens conferidos com `Read`/`grep` direto, em todo retorno (não só no `Explore`)

**Sinais que reprovam o retorno antes da amostragem** (casos em [`references/casos-medidos.md`](references/casos-medidos.md#sinais-que-reprovam-o-retorno--2026-09-21)):

- **número sem comando** — *"88 ok"*: sem o comando e a saída literal, o número não existe
- **`git diff --stat` como prova de arquivo untracked** — a wiki nova não aparece no diff
- **"não encontrado" / "não instalado" sem a prova negativa** — `ls vendor/…`, `php -m`, `grep -c` colados
- **"sem ocorrências" em grep com FQCN** — conferir o escape (`grep -F`) antes de aceitar
- **conclusão de custo sob paginação** — *"nada cresce com N"* com página de 10 linhas; pedir N acima da página

Retorno reprovado é refeito pelo mesmo agente com o achado, ou escalado de modelo — e o quadro
registra os dois casos: **auditoria reprovada é linha do quadro**, com o redespacho ao lado, não
apagão. **Interrupção no meio do lote** (limite de sessão, 429): o construtor pode ter deixado a
árvore meio-editada. Antes de retomar, `git status` e `git diff --stat`; retomar o **mesmo** agente
por `SendMessage` (o contexto dele sobrevive) em vez de despachar um novo sobre o estado parcial.
**Sem `SendMessage`** (estudo §7.2): agente novo com `git status`, `git diff --stat` e o já entregue
no prompt, para continuar dali sem refazer; o fallback vai em `## Despachos`.

### Mapa de roteamento por step

Antes do primeiro despacho de cada step, confira a linha dele no mapa em
[`references/roteamento-e-despacho.md`](references/roteamento-e-despacho.md#mapa-de-roteamento-por-step):
rota, o que roda em paralelo e o que cada sub-agente **não recebe**.

## Fluxo de Execução

### 0. Capturar o Requisito — PRIMEIRO ATO

**Antes de descobrir branch, antes de nomear a feature, antes de qualquer pesquisa.**
O procedimento está em [Captura do Requisito](#captura-do-requisito-primeiro-ato--antes-de-qualquer-pesquisa),
dentro do step 3, porque é lá que ele convive com o resto da pesquisa — mas **a execução dele é aqui**:
nomear a feature (step 2) antes de ler o pedido é fixar uma interpretação antes de ter o requisito.

### 1. Descobrir Branch e Estrutura de Pasta

`git rev-parse --abbrev-ref HEAD` — branch `ferro/501` → pasta base `wikis/specs/ferro/501/`;
`feature/user-auth` → `wikis/specs/feature/user-auth/`.

### 2. Definir Nome da Feature

Derivar **do requisito capturado no step 0**, e confirmar com o usuário:
- Nome deve ser `kebab-case`
- Deve descrever a feature, não o ticket
- Exemplos: `envio-progresso`, `unico-jobs-progress-tracking`, `api-webhook-payments`

Pasta final: `wikis/specs/{branch}/{feature-name}/`

### 3. Pesquisa e Contexto (OBRIGATÓRIO antes de escrever)

#### Captura do Requisito (PRIMEIRO ATO — antes de qualquer pesquisa)

O requisito é a **fonte da verdade** de toda a wiki e o oráculo do `feature-quality-gate`. Ele precisa ser capturado **verbatim** antes de o agente interpretar qualquer coisa.

**Como o requisito chega** — identificar qual dos casos e registrar a origem:

| Origem | O que fazer |
|---|---|
| **Texto colado no chat** (card do Jira/Azure/GitHub, e-mail, mensagem) | copiar **exatamente** como veio, sem corrigir ortografia, sem resumir, sem reordenar |
| **Arquivo no projeto** (`.md`, `.pdf`, `.docx`) | `Read` o arquivo; registrar o **path + páginas/seções** e transcrever os trechos normativos literalmente |
| **Descrição verbal do usuário na conversa** | transcrever o que foi dito literalmente e **marcar como fonte de baixa fidelidade** — pedir confirmação antes de seguir |
| **Nenhuma das anteriores** (só "implementa X") | **parar e pedir o requisito.** Sem requisito não há oráculo, e a wiki nasce sem linha de base |

**Regra dura**: o texto original é **imutável**. Se estiver ambíguo, incompleto ou contraditório, a ambiguidade é **achado**, não algo a "melhorar" na transcrição. Corrigir o requisito na captura destrói a única linha de base independente do agente.

**Caso especial — o requisito pressupõe algo que não existe no projeto** (o card fala em "aplicar o
desconto no pedido" e **não existe `Pedido`**). É **premissa de escopo**: escolher sozinho entre
*"entrego só o motor"* e *"crio a entidade que falta"* é decidir o tamanho da entrega pelo solicitante.

1. `Grep`/`Glob` para **confirmar a ausência** antes de declará-la — pode existir com outro nome
2. Listar quais `RQ` dependem da entidade ausente
3. **Perguntar ao solicitante** (raia requisito do step 4), com as duas opções e o custo de cada uma
4. Enquanto a resposta não chega: seguir com a premissa **mais estreita** (entregar o que
   existe, não criar a entidade), registrar a pergunta em `## Perguntas ao Solicitante` e deixar as
   `RQ` dependentes `aberta — Qn` — nenhum passo do `01` as implementa, e `## Cobertura do
   Requisito` as marca `**Bloqueado por**` — nunca como atendidas. Premissa de escopo tomada em
   silêncio é o erro mais caro da wiki: tudo fica coerente, verde, e entrega outra coisa

Em seguida, **decompor em cláusulas numeradas** (`RQ-01`, `RQ-02`, …), cada uma citando o trecho literal de origem. A decomposição é derivada e revisável; o texto bruto não.

**Granularidade da cláusula — o critério.** Não é uma por frase nem uma por verbo. É:

> **Uma `RQ` = uma afirmação que pode ser verdadeira ou falsa sozinha, e cuja violação é
> observável.**

Testes práticos, nesta ordem:

1. **Consigo imaginar um sistema que atende tudo, menos esta cláusula?** Se não, ela está grudada
   em outra — funda as duas
2. **A cláusula tem dois "e" que podem falhar separadamente?** ("só admin cria **e** edita **e**
   exclui" são três permissões) — separe, porque a matriz de rastreabilidade vai marcar ✅ com
   duas das três implementadas
3. **A cláusula sobrevive sem contexto?** Se ela só faz sentido lida junto da anterior, funda

Grosso demais **esconde omissão** (`RQ` ✅ com metade entregue); fino demais vira ruído. Na dúvida,
**separe** — fundir depois é barato, e a omissão escondida não aparece nunca.

**Quando o solicitante não responde a tempo**, a ambiguidade **não vira silêncio nem interpretação
do desenvolvedor** (estudo §2.3): a pergunta fica em `## Perguntas ao Solicitante` com a
recomendação, a `RQ` fica `aberta — Qn`, todo passo que dependa dela leva `**Bloqueado por**: RQ-nn
(aberta — Qn)` e o resto segue. `RQ` aberta implementada é `REPROVADO → especificação` no step 11.

> **Por que isso existe**: sem o `00-requisito.md`, o único registro do que foi pedido é o PRD — que é a **interpretação** do agente. Se a interpretação estiver errada, ela contamina plano, testes, código e validação de forma coerente, e nada no ciclo detecta. Ver [Arquivo 00](#arquivo-00-requisito--fonte-da-verdade).

Antes de escrever qualquer documento:
- Usar `database-schema` se a feature envolve novas tabelas ou alterações
- **Usar `search-docs` para toda stack envolvida** — obrigatório antes de escrever o PRD (ver [Documentation API](#documentation-api-do-boost-search-docs) para cobertura e como consultar)
- Ler arquivos existentes relevantes com `Read` ou `Grep`
- Executar `php artisan model:show ModelName` para models relacionados
- Examinar padrões existentes com `Glob "**/[padrão]/**/*.php"`
- **Inspecionar APIs de terceiros** antes de escrever CTs — verificar vendor source ou docs oficiais para confirmar nomes de métodos, assinaturas e restrições de schema. E isso nunca basta sozinho: **toda** feature que cria página, widget ou componente preenche a [Superfície Livewire](#superfície-livewire-obrigatório-em-toda-feature-que-cria-página-widget-ou-componente)
- Para features médias/grandes: delegar o mapeamento amplo a um agent `Explore` (sem ele, o fallback de [Rotas](#rotas)) e depois **confirmar os trechos críticos com `Read` direto** (linhas exatas, imports, assinaturas) — não confiar apenas no resumo do agent. No Claude Code, os greps prescritos nesta lista e na Superfície Livewire vão para `mecânico` (`haiku`) **em paralelo**, cada um devolvendo a tabela pronta — ver [Execução e Delegação](#execução-e-delegação-claude-code--roteamento-por-modelo-e-por-cegueira)
- **Validar dados fornecidos pelo usuário** (CSV, listas, IDs) contra o banco via `database-query` — detectar divergências de título/chave, escolher chave estável (ID) para mapeamentos e documentar as divergências no plano
- **Verificar existência de factories** (`Glob "database/factories/{Model}*"`) e states disponíveis antes de escrever CTs; se não houver factory, especificar `Model::create([...])` no Setup Global
- **Confirmar padrões internos citados** no plano com grep/read (ex: seeder-em-migration, guards de environment, `$casts` property vs `casts()`) — citar `arquivo:símbolo:linha` de referência no plano ([Citações de código](#citações-de-código--arquivosímbololinha)); só com linha, o `citacoes.sh` acusa
- **Verificar**: rotas (`routes/web.php`, `routes/api.php` — conflito e naming); Policies/Gates
  (`app/Policies/*.php`, `Gate::define`); `config/*.php` da feature; `composer.json` (pacote a
  reutilizar); wikis relacionadas (`wikis/specs/**/*.md`); `git log --oneline -20`; e, quando a
  feature os toca, scheduled tasks (`routes/console.php`, `app/Console/Kernel.php`),
  `app/Events`/`app/Listeners`, `app/Observers`, middleware (`app/Http/Middleware/`,
  `bootstrap/app.php`) e `.env.example` (chaves e naming)
- **Ler o glossário do projeto** — `wikis/glossario.md`, se existir: o `00`, o `01` e a entrevista usam o termo dele (ver [Glossário do projeto](#glossário-do-projeto))

#### Superfície Livewire (OBRIGATÓRIO em toda feature que cria página, widget ou componente)

Tudo o que o **cliente** pode escrever ou chamar entre requests. Não é uma seção sobre pacotes —
é sobre a **fronteira que o navegador alcança**, e o pacote de terceiro é só uma das origens dela.

> Casos que fixaram esta seção (2026-09-15 e 2026-09-17): [`references/casos-medidos.md`](references/casos-medidos.md#step-3--superfície-livewire).

Produzir a tabela `## Superfície Livewire` no `02-decisoes-arquiteturais.md`, uma linha por ponto
que o **cliente** alcança — de três origens: **o código do projeto** (todo `public function` de Page,
Widget ou componente — ação chamável por `$wire.`, retorno no navegador — e toda `public $` sem
`#[Locked]`, escrita pelo cliente entre requests); **o framework** (os arrays de estado públicos que
o código consome — `$filters`, `$pageFilters`, `$tableFilters`, `$tableSearch`, `$tableSortColumn`:
entrada não validada que vira `where`, índice e parse); **o pacote de terceiro** (ações que recebem
id/argumento do cliente, propriedades públicas e models que a feature persiste).

Antes de varrer, leia [`references/pesquisa-step-3.md`](references/pesquisa-step-3.md#superfície-livewire--formato-e-greps):
o formato da linha e os greps, com o resultado colado na tabela — dois no código que a feature
escreve (**sempre**) e quatro no pacote de terceiro (quando a feature monta sobre um).

**Regra dura**: **todo model do pacote que a feature persiste aparece na tabela com a própria
fronteira.** *"É filho do outro, logo está protegido"* só vale com a evidência de que **nenhum**
ponto de entrada o alcança direto — e essa evidência é um `grep`, não uma dedução.

**Segunda regra dura**: **todo valor que entra por um desses pontos e vira
índice de array, argumento de `parse`, nome de coluna ou operador é um cenário de domínio
inválido.** Público sem validação não é "detalhe de framework": `$rotulos[$situacao]` sem `??` e
`Carbon::parse($filtro)` sem guarda são 500 que nenhum teste de caminho feliz vê, porque a tela
sanitiza o valor **na página** e os widgets o recebem **direto**.

A tabela é **entrada obrigatória da `feature-test-design`** (step 7): cada linha vira gatilho do
checklist de taxonomia, e a linha sem cenário correspondente é lacuna declarada, não silêncio.

#### Verificação do stack de testes (define se haverá CT-B)

- **Versão do Pest** — `Grep "pestphp/pest" composer.json`. Pest 5 habilita `--tia`, `--agent` e sharding por tempo; Pest 4 tem browser plugin mas não TIA
- **Browser plugin instalado?** — `Grep "pest-plugin-browser" composer.json` e `Glob "tests/Browser/**"`
  - Se a feature tem UI e o plugin **não** está instalado: incluir a instalação como passo explícito no PRD (`## Dependências`), não assumir que existe
  - Se `tests/Browser/` já existe: ler 1-2 testes para herdar o padrão do projeto (helper de login, traits no `Pest.php`, seletores usados)
- **Playwright instalado?** — `Grep "playwright" package.json`; browsers baixados via `npx playwright install`
- **Como o app é servido em teste** — o `pest-plugin-browser` **sobe o próprio servidor** (HTTP in-process, porta aleatória): não há Herd, `php artisan serve`, Sail nem `APP_URL` a configurar. O que confirmar é outra coisa: se o projeto roda `npm run build` antes da suíte de browser (pré-requisito duro — sem o manifest do Vite toda tela responde `ViteException`) e qual o teto em `pest()->browser()->timeout()`
  - Antes de planejar CT-B, leia os demais fatos do plugin (`actingAs()`, esperas, ordem das asserções) em `{skills}/feature-test-design/references/pest-plugin-browser.md`
- **Traits globais** — `Read tests/Pest.php` para ver se `RefreshDatabase` está aplicado globalmente e se há `pest()->browser()` ou `pest()->tia()` configurado

#### Documentation API do Boost (`search-docs`)

O Boost expõe a tool MCP **`search-docs`**, que consulta a Documentation API hospedada da Laravel — 17.000+ trechos com busca semântica por embeddings, **filtrada pelos pacotes que o projeto realmente tem instalados**. É a primeira fonte a consultar, antes de vendor source e antes de doc na web.

Antes de escrever o PRD, leia [`references/pesquisa-step-3.md`](references/pesquisa-step-3.md#documentation-api-do-boost-search-docs):
cobertura por versão, a tabela de quando a consulta é obrigatória (trecho do PRD × stack), como
consultar bem e as lacunas com o fallback de cada uma (Pest 5, `pest-plugin-browser`, pacotes de
terceiros, código da aplicação).

> **Anti-padrão**: escrever no PRD assinatura de método, nome de opção de config ou comportamento de componente **sem** confirmar em `search-docs` (ou, nas lacunas listadas em `references/pesquisa-step-3.md`, na doc oficial). É a causa nº 1 de plano que não sobrevive à implementação.
>
> **Anti-padrão**: usar `search-docs` para descobrir comportamento do **seu** código. Ele documenta o ecossistema; o seu código é `Grep`, e as suas convenções são `.ai/rules/`.

### 4. Criar os Arquivos

Criar `00`, `01`, `02` e `03`, com a entrevista em três raias. O `04` (e o `05`) nasce no step 7,
depois dos cortes do Ponytail (estudo §7.2). A wiki completa tem os **5 arquivos obrigatórios** (`00`–`04`) + extras.

**Ordem de criação**:
1. **`00-requisito.md`** — requisito bruto + `RQ-##` + `## Perguntas ao Solicitante`, `## Premissas` e `## Fora de Escopo (declarado)`; é a linha de base de tudo
2. **Entrevista em três raias** ([abaixo](#entrevista-em-três-raias-obrigatória-no-step-4)), sobre o `00` e o rascunho do `01`/`02`
3. **`01-plano-acao.md`** — PRD deriva do `00`; cada passo deve citar quais `RQ` (e `P-nn`) atende;
   passo que depende de `RQ` aberta leva `**Bloqueado por**: RQ-nn (aberta — Qn)` e não é implementado
4. **`02-decisoes-arquiteturais.md`** — só decisões que passam nos [três portões](#arquivo-02-decisões-arquiteturais-adr), mais a `## Superfície Livewire`
5. **`03-progresso.md`** — espelha os passos do PRD, com `**Estado**: em planejamento`; os CT/CT-B entram no step 7
6. **Confirmação registrada** — antes do step 5, `## Auditoria Pré-Implementação` do `03` ganha a linha
   `Entendimento confirmado: {data} — {quem} — {rodadas, nº de perguntas por raia}` (estudo §2.2)

Antes de escrever cada arquivo, leia o template dele: [`references/template-00-requisito.md`](references/template-00-requisito.md),
[`references/template-01-plano.md`](references/template-01-plano.md), [`references/template-02-adr.md`](references/template-02-adr.md)
e [`references/template-03-progresso.md`](references/template-03-progresso.md); os logs do `01`,
[`references/padrao-de-log.md`](references/padrao-de-log.md); extras `05-*`, [`references/estrutura-criada.md`](references/estrutura-criada.md).

> **Rastreabilidade obrigatória**: todo passo do PRD e todo CT/CT-B referencia o `RQ` (ou a `P-nn`) de origem — é o que deixa o `feature-quality-gate` detectar cláusula sem plano, sem teste ou sem código.

#### Entrevista em três raias (OBRIGATÓRIA no step 4)

Antes da primeira rodada, leia [`references/entrevista-tres-raias.md`](references/entrevista-tres-raias.md)
(formato, [tabela das raias](references/entrevista-tres-raias.md#as-três-raias), exemplo, sementes).
Reabre quando os steps 5, 9 ou 11, ou o quiz da `feature-tickets`, geram pergunta (estudo §2.3:
perguntar tudo ao desenvolvedor o faria responder pelo solicitante).

- **Quem responde**: **fato** → o agente (steps 3 e 5), nunca o usuário; **desenho** → o desenvolvedor;
  **requisito** → o solicitante — a pergunta vai para `## Perguntas ao Solicitante` do `00`, a `RQ`
  fica `aberta — Qn` e **nenhum passo do `01` a implementa** até o Adendo
- **Formato ❓/➡️**, `Qn` numa sequência **única** para as três raias, em **rodadas pela fronteira**:
  cada rodada pergunta tudo cujo pré-requisito já foi respondido, e espera
- **Filtro de oráculo** — só entra pergunta que toca um `RQ` ou uma `P-nn` (estudo §2.4)
- **Premissa de comportamento** (o que o sistema faz quando o texto não diz) é pergunta da raia
  requisito, com a `➡️` pela opção que **falha fechado** — a mesma regra da derivação no step 7
- **Perguntas-semente** — candidatas da raia requisito **só** quando o requisito tem papéis,
  visibilidade, notificação ou texto livre (estudo §7.1, T10)
- **Sinal de escopo** (hipótese a calibrar) — mais de 30 perguntas de requisito: registrar em
  `## Auditoria Pré-Implementação` do `03` e propor o step 8 ou dividir a feature
- **Termo decidido** vai para o [glossário do projeto](#glossário-do-projeto) na hora

#### Path e número por arquivo

Path de código, `arquivo:linha` e contagem derivada só entram onde a reconciliação do step 10 cobre
(estudo §3.2): **nunca** no `00` nem na ADR do `02`, que cita módulo ou classe por nome (exceção: a
`## Superfície Livewire`); `01`, `03`, `04` e `05` podem; o ticket de `07-tickets/` aponta passos do
`01` em vez de path. Tabela com o motivo: [`references/citacoes-de-codigo.md`](references/citacoes-de-codigo.md#path-e-número-por-arquivo).

**Wiki já existente**: se `wikis/specs/{branch}/{feature}/` já existe:
- **Perguntar ao usuário** se deseja sobrescrever, incrementar (v2) ou retomar
- Se retomar: ler `03-progresso.md` para ver o que já foi feito e continuar de onde parou
- Se sobrescrever: backup manual pelo usuário antes de criar a nova (a skill não arquiva automaticamente)
- **Pedido novo do solicitante no meio da implementação** (mesma branch, mesmo PR): não é wiki nova nem "incrementar" — é **Adendo** ao `00`. Ver [Adendo ao requisito](#adendo-ao-requisito--quando-o-pedido-cresce-durante-a-implementação). Sem isso o pedido vai direto para o código e os testes dele nascem do código

### 5. Revisão Profunda Pós-Escrita (OBRIGATÓRIO)

Após escrever o `00`, o `01`, o `02` e o `03`, **re-validar cada premissa do plano contra o código real** antes de apresentar ao usuário:

- Reler os pontos exatos citados no plano: imports dos arquivos a editar, assinaturas de métodos, relações de models, padrão das migrations-referência, factories/states que o plano cita
- **Corrigir a wiki imediatamente** quando a revisão contradisser o plano (ex: plano diz "adicionar import X" → import já existe; plano cita guard genérico → padrão real é `! app()->environment('testing')`)
- **Registrar cada correção** em `03-progresso.md` → `## Auditoria Pré-Implementação` → *Revisão profunda*. Correção aplicada e não registrada some: a próxima pessoa refaz a verificação e o histórico não mostra que a premissa original estava errada
- **Confronto código × afirmação** (estudo §2.2, §2.5) — divergência de **comportamento** entre o
  `01` e o código (não erro de fato do plano) vira pergunta *"o `01` diz X; `app/…` faz Y — qual
  vale?"*, na raia que couber, com a resposta em `## Auditoria Pré-Implementação` do `03`
- Só então avançar para o step 6 (Auditoria da Wiki)

**Padrão de despacho (Claude Code)**: **levantar com `mecânico`, julgar com `analista`** — um `haiku`
por bloco de premissas devolve a tabela *premissa do plano × o que o código diz* (`arquivo:símbolo:linha`
conferido por grep); a sessão, ou um `analista` quando a divergência exige decisão, julga e corrige a wiki.

> Exemplo real: [`references/casos-medidos.md`](references/casos-medidos.md#step-5--revisão-profunda-e-classe-irmã).

#### Varredura da classe irmã (OBRIGATÓRIA para toda classe nova)

A revisão acima confere o que o plano **afirma**. Este item confere o que o plano **não sabe que
existe**: as listas paralelas que o projeto mantém à mão e que nenhuma rule enumera por completo.

Procedimento, uma linha por classe nova:

```bash
# Onde uma classe IRMÃ já existente é citada? É onde a nova também precisa aparecer.
grep -rnF 'App\Filament\Admin\Pages\Dashboard' --include=*.php app config database tests
```

FQCN vai com `grep -F` (string fixa), como na rota `mecânico`: o escape das barras produziu um *"sem ocorrências"* falso.

Escolher como irmã a classe **mais parecida em papel** (outra Page de dashboard, outro Resource do
mesmo painel, outro Widget da mesma família) e conferir **todos** os lugares onde ela aparece:
`config/*.php`, seeders, listas de exclusão, inventários de teste, `->pages()`/`->widgets()` dos
providers, matrizes de permissão. Cada ocorrência é uma pergunta: *a classe nova entra aqui também?*

> Caso que originou a varredura (2026-09-17, o "checkbox que mente"): [`references/casos-medidos.md`](references/casos-medidos.md#step-5--revisão-profunda-e-classe-irmã).

Registrar o resultado em `03-progresso.md` → `## Auditoria Pré-Implementação`, com a irmã escolhida
e as ocorrências encontradas. "Nenhuma ocorrência além das previstas" é resposta válida e precisa
estar escrita.

### 6. Auditoria da Wiki com Ponytail-review (OBRIGATÓRIO)

Após a revisão profunda (step 5), **invocar automaticamente** `/ponytail:ponytail-review` para auditar o `01` e o `02` (o `03` acompanha os cortes do `01`). Este step é função direta da skill — o agente NÃO deve esperar o usuário pedir.

**Por que auditar a wiki**: O plano de ação pode conter over-engineering — passos desnecessários, abstrações prematuras, complexidade que não agrega valor. A auditoria com Ponytail-review identifica esses pontos **antes** da implementação começar, economizando tempo de desenvolvimento.

**Sem o plugin Ponytail instalado**: a sessão faz o passe de over-engineering manualmente com a escada de simplicidade e registra *'Ponytail indisponível — passe manual'* em `## Auditoria Pré-Implementação` do `03`.

**Como executar**:
1. Invocar `/ponytail:ponytail-review` apontando para `01-plano-acao.md` e `02-decisoes-arquiteturais.md` em `wikis/specs/{branch}/{feature}/`
2. Analisar cada sugestão de corte/simplificação retornada
3. **Aplicar as sugestões relevantes** na wiki: passo desnecessário sai do `01` e do `03`;
   abstração prematura é simplificada ou marcada YAGNI; complexidade excessiva é quebrada ou simplificada
4. **Re-executar** `/ponytail:ponytail-review` se houver mudanças significativas (>3 arquivos alterados)
5. Só então avançar para o step 7: o `04` nasce sobre o `01`/`02` já cortados

**Corte depois do step 7** (caso em [`references/casos-medidos.md`](references/casos-medidos.md#step-6--ordem-com-a-derivação-do-04-2026-09-21)) —
achado do step 9 ou 11 que muda rota, ação, filtro ou coluna da `## Superfície de UI`:
**re-sincronizar o `04` no mesmo passo** — um `mecânico` cruza o `## Índice de Cenários` com os
elementos cortados; cada CT atingido vira `@obsoleto` com o motivo (e sai do índice com `~~`) ou é
re-derivado pela `feature-test-design`; registrar em `## Desvios do Plano` do `03`. CT órfão
descoberto só no step 10 é sinal de que este item foi pulado

> Esta auditoria revisa o **plano**, não o código (o código é do step 10 e da Verificação Final).
> **Comando correto**: `/ponytail:ponytail-review` (com namespace `ponytail:`). NUNCA usar `/ponytail-review` sem o namespace — o comando não será encontrado.

### 7. Derivar os Casos de Teste (OBRIGATÓRIO, depois do Ponytail)

**Invocar a skill `feature-test-design`** (`{skills}/feature-test-design/SKILL.md`) para o
`04-casos-de-teste.md` e, condicionalmente, o `05-casos-de-teste-browser.md`. Roda depois do step 6
porque recebe do `01` exatamente o que os cortes mudam — paths, rotas, `## Superfície de UI` (estudo §7.2).
Antes de invocar, leia o contrato em [`references/delegacao-casos-de-teste.md`](references/delegacao-casos-de-teste.md#o-contrato-da-delegação).
As regras duras ficam aqui:

- **Entrada, em ordem de autoridade**: o `00` inteiro (oráculo); do `01`, **só** paths, rotas e
  `## Superfície de UI`; do `02`, **só** a `## Superfície Livewire`; `wikis/glossario.md`, se existir
- **Proibido passar como entrada: a implementação da feature** — ela ainda não existe; se existir, não é fonte de comportamento
- **`RQ` aberta não gera cenário** — o `04` a lista como `RQ-nn — aberta (Qn), sem cenário até a resposta` (forma esperada, não achado)
- **Pergunta devolvida** chega no formato ❓/➡️, numerada `Q?1, Q?2…` quando vem de sub-agente; a
  sessão a renumera na sequência `Qn` da feature (a partir do maior `Qn` de `00`–`03`), atualiza a
  linha `RQ-nn — aberta (Qn)` do `04` e a leva ao solicitante (`## Perguntas ao Solicitante`) ou ao
  desenvolvedor (raia desenho). Quem pergunta é a sessão
- **Costuras voltam como proposta** (estudo §3.4) — `## Costuras de Teste` chega do sub-agente com
  `Confirmada` vazia; a sessão confirma cada linha com o desenvolvedor, preenche quem e data e, se uma
  costura muda, pede a re-derivação daquele grupo. A costura decide se o `05` existe ([Gate do `05`](#gate-do-05-browser))
- **Revisão adversarial** — depois das costuras confirmadas, a sessão principal despacha o
  `fw-adversario-ct` (`00` + `04`/`05`, + `wikis/glossario.md` se existir) e fecha todos os achados:
  sub-agente não despacha sub-agente
- **O `03` ganha os CT/CT-B** — `## Testes` lista os arquivos de teste com os IDs que cada um cobre

> **Não escrever o `04` inline** — o caso de teste derivado do plano confirma o plano ([Arquivos 04 e 05](#arquivos-04-e-05-casos-de-teste--delegados-à-feature-test-design)).
> Sem a `feature-test-design` instalada, **declarar a degradação no `03`** antes de escrever o `04` à mão.

Só então apresentar a wiki ao usuário para aprovação e seguir para o step 8, que decide se ela é
fatiada ou implementada numa sessão.

### 8. Fatiar em Tickets (CONDICIONAL)

Com a wiki aprovada, decidir se a feature cabe numa sessão. **Se cabe numa sessão, não fatie**:
fatiar custa um quiz, N sessões e N fechamentos (estudo §4.1). Sugerir `/feature-tickets {wiki}`
quando **qualquer** sinal vale — limiares são hipótese a calibrar:

- **Tamanho**: 18 ou mais `RQ` vigentes no `00`, ou 60 ou mais CT no `04` — o tamanho da única
  feature medida de ponta a ponta ([por que este limiar](references/casos-medidos.md#step-8--o-limiar-de-tamanho-2026-09-21));
  descreve a feature inteira, não o tamanho de um ticket
- **Compactação**: a sessão já foi compactada antes do step 8 — o planejamento sozinho não coube
- **Sinal de escopo** do step 4 cruzado (mais de 30 perguntas de requisito)
- **Refatoração larga** (`## Natureza da Wiki: refatoração`) — expand → migrate em lotes → contract

**Só o usuário invoca** a `feature-tickets` (`disable-model-invocation: true`, estudo §4.4): a sessão
sugere, com o sinal e a recomendação. Sem sinal, ou sugestão recusada → `Não fatiado — {data}: {sinais
conferidos}` em `## Tickets` do `03`; fatiado → a `feature-tickets` grava `07-tickets/` e a `## Tickets`
(e substitui a linha `Não fatiado`, se o fatiamento vier depois). O passo do `01` é camada, não fatia
demonstrável (estudo §4.2, §4.3).

### Implementação (sem número)

Duas formas, com o `04` como contrato e o Ponytail ativo:

- **Passos do `01`**, em ordem — o padrão. O teste de backend nasce do Gherkin do `04` pelo
  `executor-ct` ([Execução dos testes](#execução-dos-testes--executor-ct-e-ct-b)); o código, pelo `construtor`
- **Tickets de `07-tickets/`**, quando o step 8 fatiou — só ticket da fronteira, cada um numa **sessão
  nova** (o usuário digita `/feature-tickets {wiki} {NN}`) ou num `construtor` com **só a fatia**: o
  ticket, o `00`, o `02`, os passos do `01` que ele aponta e os CT dele — nunca os outros tickets, o
  `03` nem a conversa (cegueira de fatia, estudo §4.3). Fechamento e `Status` seguem a `feature-tickets`

Ao começar, o `03` passa a `**Estado**: em implementação`. Passo ou ticket `**Bloqueado por**: RQ-nn
(aberta — Qn)` não é implementado até a resposta entrar como Adendo. Suíte da feature verde — ou todo
ticket `concluído` — → step 9, uma vez por feature.

### 9. Revisão de Código do Diff (OBRIGATÓRIO, logo após os testes passarem e antes da reconciliação)

**Quando**: a suíte da feature está verde e o último passo do PRD foi entregue — **antes** do
step 10. A ordem é **9 → 10 → 11 → PR**, e o motivo é mecânico: cada achado confirmado aqui vira
CT no `04` e correção no código (e `P-nn` no `00`, se o solicitante não o escreveu), e isso desloca linha citada, cria ID de CT e muda
frase de doc e de ADR. Reconciliar (10) antes de revisar é reconciliar duas vezes. E a dimensão L do
step 11 repete a reconciliação por quem não a fez, então ela precisa ser a **última** coisa antes
dele. Numeração antiga: README, *Numeração dos steps*. O `03` passa a `**Estado**: em revisão`.

**Por quem não implementou** — sobre o **diff completo** da feature contra a base, mais o
não-commitado, não sobre os arquivos que o agente lembra de ter tocado. Para o `fw-revisor-diff`, sem
a wiki (estudo §7.1 T6 — ela vai no PR e entregaria o `01` e o `03`): `git diff {base}...HEAD -- . ':(exclude)wikis'`;
o hook nega o `git diff` sem a exclusão.

**Pré-requisitos do lote** (casos em [`references/casos-medidos.md`](references/casos-medidos.md#step-9--revisão-do-diff)):

- **A sessão roda no repositório do projeto.** O `/code-review` só alcança o diretório onde a
  sessão foi aberta; fora dele, o passe 1 vira um `analista` (`opus`) **cego**, com o mesmo alvo
  (`{base}...HEAD`) e sem os eixos, e `## Despachos` declara *"passe genérico por sub-agente —
  `/code-review` fora de alcance"* (vale menos: faltam as heurísticas do comando nativo)
- **A `## Superfície Livewire` do `02` foi re-varrida sobre o código final** — ela **envelhece**
  durante a implementação. Um `mecânico` refaz os greps sobre o diff final **antes** do revisor, que
  recebe a tabela atualizada; leia os greps em [`references/pesquisa-step-3.md`](references/pesquisa-step-3.md#superfície-livewire--formato-e-greps)
  e passe-os literais no prompt

**Nenhum outro step cobre**: o 6 audita o **plano** e o 11 confronta **requisito × app**; nenhum lê o
diff atrás de **defeito de correção** — escrita cross-tenant, propriedade pública que o cliente
escreve, gate que falha aberto no nulo, estado de erro sem saída —, e tudo isso passa com a suíte
verde ([`references/casos-medidos.md`](references/casos-medidos.md#topo--o-step-9-é-o-gate-mais-produtivo-2026-09-17)).

#### Quando rodado no Claude Code — dois passes, no mesmo lote

| Passe | Ferramenta | O que pega | Como |
|---|---|---|---|
| **1. Genérico** | `/code-review high {base}...HEAD` | defeito de correção que qualquer revisor competente vê: nulo não tratado, condição invertida, exceção engolida, N+1, uso errado de API | o comando já roda em sub-agente isolado — a cegueira ao contexto da sessão vem de graça |
| **2. Eixos** | sub-agente `fw-revisor-diff` (`opus`, sem Edit/Write, hook `revisor-diff`) | os eixos da tabela abaixo — são de Laravel, Livewire e multi-tenant, e o passe genérico **não os conhece** | recebe: o alvo do diff sem `wikis/`, a tabela de eixos, a `## Superfície Livewire` do `02` e as rules cujos globs casam o diff. **Não recebe**: `01`, `03`, nem o raciocínio da sessão |

Os dois disparam **juntos** — são independentes. Regras dos passes:

- **Alvo explícito, sempre.** Sem alvo, o `/code-review` compara com o merge-base do **upstream**
  da branch: numa branch já pushada o "diff atual" vira só o não-commitado, e a revisão sai vazia
  parecendo limpa. Escrever `main...HEAD` (ou a base real do PR)
- **Nível `high`.** `low`/`medium` devolvem só achado de alta confiança; aqui o achado incerto é
  bem-vindo, porque o roteamento **obriga a rejeitar com motivo** — e relatório sem rejeição
  parece que só procurou onde achou
- **`--fix` é proibido.** Quem julga não conserta (princípio 2 da `feature-quality-gate`), e o
  roteamento exige premissa → CT → correção **nessa ordem**; o `--fix` pula os dois primeiros e
  entrega correção sem oráculo
- **O comando não aceita foco em texto livre** — o que vier depois do nível é lido como alvo. Por
  isso os eixos vão num sub-agente próprio, não num argumento do `/code-review` (nem pathspec: o
  passe 1 vê a wiki no diff; a cegueira ao plano é do passe 2)
- **`git status --porcelain` antes e depois** no retorno do `fw-revisor-diff`; diferença é violação de
  contrato (achados descartados, usuário avisado, reprovação em `## Despachos`). No Bash o hook é
  heurístico (estudo §7.1 T7); a comparação é sinal, não prova — não vê nova edição em arquivo já modificado
- **Re-revisar uma única vez**, e só se alguma correção tocou eixo de fronteira (dado, superfície,
  estado de erro) — correção é código novo do mesmo agente. Teto de 2 rodadas; se a segunda ainda
  trouxer achado estrutural, o problema é o plano: registrar e escalar

**Checkpoint opcional durante a implementação**: depois de um passo que cria query com discriminante,
`public function`/`public $` em componente ou estado de erro novo, `/code-review medium` no
não-commitado — achado ali custa uma linha, no step 9 custa premissa, CT e correção. **Não substitui**
o passe completo (não vê simetria de guarda entre superfícies que ainda não existem).

**Fora do Claude Code**: o passe 2 é o gate inteiro. Host com sub-agente: `revisor-diff` com o
mesmo contrato. Host sem sub-agente: em linha, com a degradação declarada no `03` —
*"step 9 em linha — mesma sessão que implementou"* — porque o resultado vale menos, e o leitor do PR
precisa saber.

**Eixos obrigatórios da revisão** (além do que o revisor achar por conta):

| Eixo | Pergunta |
|---|---|
| Fronteira de dado | toda query que o usuário alcança filtra pelo discriminante? e quando o discriminante é **nulo**, ela **fecha** ou **abre**? |
| Ponto de entrada do vendor | as ações do pacote que recebem id/argumento do cliente estão cobertas pela mesma fronteira? conferir contra `## Superfície Livewire` do `02` |
| Propriedade pública Livewire | o que o cliente pode escrever **entre requests**? `#[Locked]` em toda propriedade que decide **onde** a escrita cai |
| **Método público de componente** | todo `public function` de Page/Widget é **ação chamável por `$wire.`**, e o retorno vai para o navegador. Tem lista fechada de argumentos, ou aceita qualquer string? |
| **Valor de estado usado sem validar** | todo valor vindo de `$filters`, `$pageFilters`, `$tableFilters` ou `$tableSearch` que vira **índice de array**, argumento de **`parse`**, nome de **coluna** ou **operador** tem guarda? A página pode sanitizar e o widget receber cru |
| **Lista paralela** | nasceu classe nova? o FQCN de uma classe **irmã** aparece em quantos lugares (`config/`, seeders, inventários de teste, providers)? a nova entrou em todos? |
| **Simetria de guarda** | duas superfícies da mesma fronteira se comportam igual? uma fecha com log e a outra fecha em silêncio? |
| Estado de erro | todo 4xx/redirect novo tem saída — para onde o usuário vai depois? par "A devolve para B, B devolve para A" é blocker |
| Afirmação de comentário | comentário que justifica a **ausência** de um controle tem `arquivo:linha` do vendor provando? |

> Os quatro eixos em negrito vieram de um caso medido: [`references/casos-medidos.md`](references/casos-medidos.md#step-9--revisão-do-diff).

**Roteamento do achado** — igual ao do quality gate (step 11), e nesta ordem:

1. Achado confirmado **que o solicitante não escreveu** (sem `RQ` que o cubra) vira **premissa
   `P-nn`** em `## Premissas` do `00` — **não** Adendo: muda o que a feature promete, mas o
   solicitante não o escreveu (estudo §7.2). Achado que **viola ou omite `RQ` existente não vira
   `P-nn`** (duplicaria a `RQ`): segue os itens 2 e 3 com `Origem` = `RQ-nn` — destino *teste* do
   quality gate; se o passo do `01` também diverge da `RQ`, ele é corrigido antes do CT (*especificação*)
2. Vira **CT novo no `04`** com `Origem` = `P-nn` (ou a `RQ-nn` violada), com regra, cenário Gherkin
   e os mutantes que ele mata, **antes** da correção — derivado pela `feature-test-design`, reinvocada
   só para o achado (entrada: o `00` e o `04` existente, como no Adendo), nunca escrito inline
3. Só então a correção
4. Se o achado **contradiz ou estende** o que o solicitante pediu, abre também uma pergunta em
   `## Perguntas ao Solicitante` (raia requisito)
5. Com `07-tickets/`: a `P-nn` ganha **ticket novo no fim da numeração** (regra 11 da
   `feature-tickets`); o CT de achado sobre `RQ` existente entra no ticket que já cobre a `RQ`
   (regras 4 e 5 dela); a `RQ` de um Adendo segue o item 2 do [Adendo](#adendo-ao-requisito--quando-o-pedido-cresce-durante-a-implementação).
   Sem isso, o `indice.sh --check` e a Matriz acusam *"sem ticket"*
6. Achado **rejeitado** fica registrado com o motivo. Relatório sem rejeição parece que só procurou
   onde achou

Todo achado dos dois passes — ID, destino, `P-nn` gerada ou motivo da rejeição — vai para
`## Revisão do Diff (step 9)` do `03`: é dali que a dimensão I do quality gate lê o que já foi revisto.

**Falsificabilidade da correção (duro)**: antes de fechar, provar que o CT novo **falha sem** a
correção — `git stash push -- app/`, rodar o CT, `git stash pop`. CT que passa dos dois lados não é
oráculo, é decoração — **salvo quando a pilha de teste não consegue exibir o defeito**. Três saídas,
e a terceira precisa estar escrita:

| Sem a correção, o CT… | Veredito | Registro na `## Verificação Final` |
|---|---|---|
| falha | oráculo válido | `n de m falham sem o fix` |
| passa, e a pilha exibiria o defeito | decoração — reescrever o CT | — |
| passa porque a pilha **não exibe** o defeito (SQLite ignora `VARCHAR(255)`; `RESTRICT` sem `PRAGMA foreign_keys`; cascata só em memória) | **"não falsificável nesta pilha — guarda mantida, dívida declarada"** | linha com o motivo e o que exibiria (MySQL/Postgres em CI) |

> Casos da falsificabilidade e da revisão do diff "verde e concluído" (2026-09-15, 2026-09-21): [`references/casos-medidos.md`](references/casos-medidos.md#step-9--revisão-do-diff).

### 10. Pós-Implementação e Reconciliação (OBRIGATÓRIO, antes do PR)

Após a implementação, os testes passarem e o **step 9 ter rodado** — e **antes de abrir o PR e
antes de escrever "concluída" no `03`**. A ordem é **9 → 10 → 11 → PR**: o diff que se reconcilia
aqui é o diff **pós-revisão**.

Os casos que fixaram essa ordem e os itens 4, 5 e 6 abaixo estão em
[`references/casos-medidos.md`](references/casos-medidos.md#step-10--reconciliação). Antes do item 3,
leia [`references/citacoes-de-codigo.md`](references/citacoes-de-codigo.md).

**Fontes a reconciliar** — a lista é fechada; o que não está nela não é reconciliado por acidente:
`01`, `02`, `04`, `05`, `03`, docs de usuário (pt **e** en), `CHANGELOG.md`, `README`, e as rules
de `.ai/rules/` cujos globs casam com o diff; com `07-tickets/`, também os tickets (CT renumerado ou
passo novo deixa ticket apontando o que não existe).

**Conferências mecânicas** — antes dos itens, em paralelo (`mecânico`), na raiz do projeto, com
`{wiki}` = `wikis/specs/{branch}/{feature}`. **Saída vazia é o critério**, e ela vai colada na
`## Verificação Final` — sem ela o checkbox não fecha:

```bash
bash {skills}/feature-wiki/scripts/rastreabilidade.sh {wiki}          # RQ/P-nn × passo do 01 × CT do 04
bash {skills}/feature-wiki/scripts/checkbox-sem-evidencia.sh {wiki}   # item 1
bash {skills}/feature-wiki/scripts/citacoes.sh {wiki}                 # item 3
bash {skills}/feature-wiki/scripts/ids-ct.sh {wiki} 'tests/**/{Feature}/*.php'   # item 4; por pasta: os executores gravam em tests/Feature/{Feature}/ e tests/Browser/{Feature}/
bash {skills}/feature-wiki/scripts/conformidade-rules.sh {wiki} {base}   # item 6
bash {skills}/feature-tickets/scripts/indice.sh --check {wiki}        # só com 07-tickets/: alocação a ticket (fonte única)
```

Cada linha se corrige **na fonte**, nunca apagando citação, ID ou checkbox para "passar"; exit 2
(sem `php`, sem o `03`, padrão que não casa teste) não é silêncio. Linha do `rastreabilidade.sh` é
omissão no plano e volta ao `01`/`04` antes do step 11, que roda o mesmo script.

1. **Checkbox só fecha com evidência inline.** Formato `- [x] {item} — {evidência}, {data}`
   (ex.: `— 677/677 verdes, 2026-09-05`). Item sem evidência continua `[ ]`. É proibido fechar a
   `## Verificação Final` por substituição em lote: cada linha fecha quando o comando dela roda.
   Conferência: `checkbox-sem-evidencia.sh` silencioso
2. **Desvio corrige a fonte; o `03` só aponta.** Cada item de "Desvios do Plano" exige a edição
   correspondente no `01`, `02`, `04` ou `05` de origem, marcada inline com
   `*(alterado em {data}: {motivo curto})*`. Registrar o desvio só no `03` deixa o PRD e a ADR
   afirmando o que o código não faz — e é o PRD que a próxima pessoa lê. Critério de saída:
   **nenhuma afirmação do `01`/`02` contradiz o código**
3. **Reverificar toda citação `arquivo:símbolo:linha`** com o `citacoes.sh` (formato em
   [Citações de código](#citações-de-código--arquivosímbololinha)). Pint e imports novos deslocam
   linhas; a conferência é mecânica e o resultado (`— citacoes.sh exit 0, {data}`) vai para a Verificação Final
4. **Sincronizar `04`/`05` com o teste real, nos dois sentidos — por comando, não por leitura.**
   Todo `[CT-nn]`/`[CT-Bnn]` do arquivo de teste existe no `04`/`05`; todo CT do índice aponta um
   teste existente ou declara "fundido em CT-nn"; linha de dataset nova no teste existe como
   Exemplo no Gherkin. Cenário que nasceu durante a implementação **nasce no `04` primeiro**
   (Proibição 11 da `feature-test-design`). Instrumento: `ids-ct.sh` — cada linha diz o lado que
   falta (CT sem teste, teste sem cenário, teste de cenário `@obsoleto`); dataset × Exemplos é leitura.
5. **Todo número da wiki é derivado por comando — e procurado na wiki inteira quando muda.**
   Número escrito à mão envelhece **dentro do próprio ciclo** (contagem de CTs, regras, mutantes,
   permissions, linhas de varredura, total de cenários), no `01`, no `02` e no `04`, não só no `03`:
   a conferência é `grep -c` contra o código, ao fim. O ponto cego é a **duplicação** — o achado
   corrige o arquivo que citou e a cópia em outro sobrevive: antes de fechar,
   `grep -rn "{valor antigo}" wikis/specs/{branch}/{feature}/`
6. **Conformidade com as rules do projeto.** `conformidade-rules.sh {wiki} {base}` lista as rules de
   `.ai/rules/` cujo `paths:` casa um arquivo do diff e acusa a que ficou sem linha na tabela
   `## Conformidade com Rules` do `03`. O veredito `rule → aplicada / n.a. / violada`, com evidência
   (`arquivo:símbolo:linha` ou nome do CT), é julgamento. Rule violada é blocker do PR. O step 3 manda
   **ler** as rules; este item confere se o **código** as cumpre
7. **Docs de usuário e CHANGELOG × comportamento × rastro.** Toda consequência que a wiki
   descreveu e depois mudou (ex.: "o log registra o painel `app`") é procurada nas docs pt/en, no
   CHANGELOG, no README e na ADR que a originou. Frase nova em doc de usuário **sem `RQ` nem ADR
   de origem** é crescimento sem rastro: vira `P-nn` em `## Premissas` do `00` (e pergunta ao
   solicitante, se estende o pedido) ou sai da doc
8. **Notas de Implementação** no `03`: descobertas durante o código que não estavam no plano
   (ex.: "`Enrollment::find()` aplica scope global de tenant — documentado em `02`")
9. **Roteiro "Desenhado × Implementado"** em `05-casos-de-teste-browser.md` (se existir): rodar os
   CT-B, conferir cada linha da `## Superfície de UI` do PRD contra a tela real, marcar ✅/⚠️/❌;
   divergência vai para "Desvios do Plano" **e** para a fonte (item 2)
10. **Confirmar impacto real com TIA**: `vendor/bin/pest --parallel --tia` × `## Impacto em
   Features Existentes` do PRD — divergência é nota de implementação
11. **Retrospectiva breve** no `03`: o que funcionou no planejamento e o que faltou
12. **Limpeza de channel de log** — só **depois do merge** e da estabilização: reduzir o level de
    `debug` para `info` ou remover o channel

> **Autolimpeza não é auditoria.** Os itens 2, 6 e 7 são julgamento sobre texto que o mesmo agente
> escreveu; a `feature-quality-gate` (step 11) os repete como **dimensão L**, por quem não escreveu a
> wiki. Sem o 10 o gate afoga em defasagem trivial; sem o 11 ninguém confere quem escreveu.

### 11. Quality Gate e abertura do PR (OBRIGATÓRIO, antes do PR)

Após os testes passarem e o step 10 estar concluído, **invocar a skill `feature-quality-gate`**. Este step é função direta da skill — o agente NÃO deve esperar o usuário pedir. **O PR não abre antes do veredito**, e o `03` não diz "concluída" antes de a seção `## Quality Gate` estar preenchida.

**O que ela faz que os steps 5, 6 e 10 não fazem**: os três tomam o PRD como verdade. O quality gate confronta **`00-requisito.md` × PRD × app rodando** e detecta a classe de defeito que nenhum teste pode pegar — a **omissão silenciosa**: cláusula `RQ` que nunca virou passo, nunca virou CT, nunca virou código. Tudo verde, feature incompleta. E a **dimensão L** dela repete, por quem não escreveu a wiki, o que o step 10 declarou reconciliado: PRD/ADR × código, rules × diff, docs × comportamento, citações e IDs de CT.

**Entrada que a skill espera**:

- `00-requisito.md` com as cláusulas `RQ-##`
- `01`–`05` da wiki, e `07-tickets/` quando a feature foi fatiada (a Matriz de Rastreabilidade ganha a coluna `Ticket`)
- app servido e acessível
- `## Natureza da Wiki` do PRD (decide se roda regressão)

**Saída**: `06-relatorio-qa.md` + veredito.

**Quando rodado no Claude Code — despachar, não invocar em linha.** A skill exige *"por quem não
escreveu a wiki"*, e invocá-la na mesma sessão que escreveu o `01` e implementou entrega o
contrário disso. Despachar o sub-agente `fw-qa-gate` (`opus`, sem Edit/Write) com **só**: o path
da wiki, a URL do app servido, o `git diff --stat`, a `{base}` e a instrução de ler e seguir
`{skills}/feature-quality-gate/SKILL.md`. As dimensões que exigem Playwright MCP ou Boost rodam no
próprio sub-agente: ele herda as ferramentas MCP quando o arquivo do agente não restringe `tools`.

**Retorno do `fw-qa-gate`**: o `06` entre uma linha `<<<06` e uma linha `>>>06`; depois do
`>>>06`, `## Para o orquestrador`, com o `git status --porcelain` de antes e o de depois. A sessão
grava **só** o que está entre os delimitadores, **sem editar** — retorno sem eles é redespachado,
nunca remontado à mão — e compara as duas saídas do `git status`: diferença é violação de contrato,
o `06` não é gravado, a diferença vai ao usuário e a auditoria reprovada vai para `## Despachos`
(estudo §7.4, §7.6: sem delimitador, `## Para o orquestrador` caía dentro do `06`; no `Bash`, o hook
`qa-gate` é heurístico e o `git status` é o sinal do resto). A linha em `## Despachos` leva o `Custo`;
o custo do gate antes/depois da 4.0.0 é medição pendente (estudo §8, item 8).

O cabeçalho do `06` leva a linha `Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa`
— ou `mesma sessão`, quando degradado.

| Veredito | O que o fluxo faz |
|---|---|
| `APROVADO` | só com **todas** as dimensões verificadas e nenhum achado aberto — segue para o step 12. **Inalcançável nos perfis Mínimo e Padrão, de propósito** |
| `APROVADO COM DÉBITO` | teto quando ≥ 1 dimensão ficou não verificada, por qualquer causa (fora do perfil, app ausente, MCP ausente, oráculo degradado), ou há `RQ` `aberta` que nenhum passo nem código implementa. Nos perfis Mínimo e Padrão é o teto **por construção** e **não bloqueia nada**: é a declaração honesta do que não foi verificado. Débito — cada achado e cada dimensão com a causa — vai para o `03`; segue para o step 12 |
| `REPROVADO → especificação` | inclui `RQ` `aberta` implementada por passo não bloqueado ou por código (o dev respondeu pelo solicitante). Volta ao step 4: corrigir `00`/`01`/`02` (e re-derivar no step 7 o que a correção mudar no `04`), depois reimplementar |
| `REPROVADO → implementação` | volta à execução do passo do PRD indicado |
| `REPROVADO → teste` | volta ao `04`/`05`: escrever o CT que falha **primeiro**, depois corrigir |

**Achado confirmado que o solicitante não escreveu** (sem `RQ` que o cubra) segue o roteamento do
step 9: `P-nn` em `## Premissas` → CT com `Origem` = `P-nn` → correção → pergunta ao solicitante,
se contradiz ou estende o pedido; com `07-tickets/`, ticket novo no fim da numeração. **Omissão de
`RQ` existente não vira `P-nn`**: vai ao destino do veredito (especificação, implementação ou teste);
CT novo com `Origem` = `RQ-nn` entra no ticket que já cobre a `RQ`, com `07-tickets/`.

**Quando pular**: feature sem nenhuma superfície validável — registrar o motivo no `03-progresso.md`, gravando mesmo assim um `06-relatorio-qa.md` mínimo com veredito `NÃO APLICÁVEL` e o motivo; o arquivo continua obrigatório. `NÃO APLICÁVEL` é veredito **do orquestrador**: a sessão escreve esse `06` sem rodar o gate. Não pular por pressa. Wiki de `refatoração` não pula: a regressão contra os CT das features que consomem o símbolo é o que prova que nada mudou.

> **Teto do loop**: no máximo **3 ciclos** de quality gate por feature. Ao estourar, escalar ao usuário com o que ficou aberto. Ver a skill `feature-quality-gate` para as regras de convergência.

**Depois do veredito, e só então**:

1. Registrar ciclo, veredito e data na seção `## Quality Gate` do `03-progresso.md`
2. Marcar o `03` como concluído: a linha `**Estado**` do topo passa a `**Estado**: concluída — {data}`
   (formato no template do `03`). Vem antes do item 3 porque o `indice.sh` lê essa linha
3. Com a `feature-tickets` instalada (`{skills}/feature-tickets/scripts/indice.sh` existe): regenerar
   o quadro entre features com `bash {skills}/feature-tickets/scripts/indice.sh` e levar
   `wikis/specs/INDEX.md` no PR — gerado, nunca editado à mão (estudo §5.1)
4. **Abrir o PR** com o link da wiki e o veredito do `06-relatorio-qa.md` na descrição

> Por que a ordem é dura (caso real): [`references/casos-medidos.md`](references/casos-medidos.md#step-11--por-que-a-ordem-é-dura). O veredito é parte do PR, não um passo depois dele.

### 12. Candidatos a Rule de Projeto (OBRIGATÓRIO, depois do veredito)

Decisão registrada no `02` só é lida por quem abre aquela wiki; **Project Rules do Laravel Boost**
(`.ai/rules/`) a levam a qualquer agente que edite um arquivo do glob. Depois do veredito do step 11,
**invocar a skill `requirement-to-rule`** (`{skills}/requirement-to-rule/SKILL.md`). Ela é a dona
única do step: coleta de candidatos, os 4 gates, o **único** prompt de aprovação, `record-rule`,
índice e commit. Esta skill não coleta, não julga e não pergunta (estudo §7.5: eram duas aprovações
para a mesma decisão).

- **Rota**: sessão principal — tem MCP (`search-docs` do gate 4, `record-rule`) — ou sub-agente que
  herda MCP (sem `tools` restrito), que devolve os candidatos como texto; o prompt é sempre da sessão.
  **Nunca** `analista`: sem MCP, o gate 4 não roda
- **Entrada**: o path da wiki (`wikis/specs/{branch}/{feature}/`). Nada de lista pré-filtrada
- **"Vale virar rule"** tem uma definição só: `{skills}/requirement-to-rule/SKILL.md`, seção *Vale
  virar rule* — durável, não-inferível com evidência mínima e recorrência declarada. Esta skill não
  tem critério próprio nem chama nada de "candidato natural"
- **Commit** (rules, `tests/Arch/`, `phpunit.xml`) na **mesma branch do PR já aberto**, com uma linha
  na descrição do PR
- **Registrar no `03`**, seção `## Candidatos a Rule`, a linha que a `requirement-to-rule` devolve, no
  formato fixo `apresentados N · gravados N · recusados N · descartados no gate N · poda N`, com data e
  rota. Sem a `requirement-to-rule` instalada: a linha diz isso, e a decisão continua na ADR

---

## Arquivo 00: Requisito — Fonte da Verdade

**Path**: `wikis/specs/{branch}/{feature}/00-requisito.md`

**Propósito**: guardar o requisito **como ele chegou**, sem interpretação, e decompô-lo em cláusulas rastreáveis. É a única linha de base independente do agente — todo o resto da wiki é derivado e, portanto, contaminável por interpretação errada.

**Regimes das seções** (estudo §7.2: achado de revisor não entra como pedido do solicitante).
**Imutáveis**: `## Fonte` (depois de criada), `## Texto Original` (nunca editar, corrigir, resumir
ou reordenar) e `## Adendo N — {YYYY-MM-DD}` (**só** pedido do solicitante, com fonte e texto
verbatim). **Revisáveis**: `## Decomposição em Cláusulas` (coluna `Estado`: `fechada` · `aberta — Qn` ·
`substituída por RQ-nn (Adendo N)` · `decomposta em RQ-nn, RQ-mm`), `## Perguntas ao Solicitante`
(só a raia requisito), `## Premissas` e `## Fora de Escopo (declarado)`. Quem escreve é sempre a
sessão. Tabela: [`references/template-00-requisito.md`](references/template-00-requisito.md#regimes-das-seções).

**Premissa `P-nn`** — o que a feature passa a assumir **sem que o solicitante tenha escrito**: nasce
de achado confirmado de revisão (steps 5, 9 e 11) ou de decisão de desenho que muda o que a feature
promete; não tem texto original; se contradiz ou estende o pedido, gera também pergunta ao solicitante.

**Obrigatório incluir**:

- Origem (card, arquivo + página, conversa) com data e autor
- Texto original verbatim, ou os trechos literais normativos quando a fonte é longa
- Decomposição em `RQ-##` com: cláusula, trecho literal de origem, tipo (funcional / autorização / não-funcional / restrição) e `Estado`
- **Perguntas ao Solicitante** — cláusula não-testável é achado, não detalhe; a `RQ` que depende de pergunta aberta fica `aberta — Qn`
- **Premissas** e **Fora de Escopo (declarado)**, mesmo quando vazias ("nenhuma") — e nenhum path de código ([Path e número por arquivo](#path-e-número-por-arquivo))

Antes de escrever o `00`, leia o template em [`references/template-00-requisito.md`](references/template-00-requisito.md) —
as seções, as colunas e os templates de `## Premissas` e do Adendo. As perguntas-semente, condicionais
a papéis, visibilidade, notificação ou texto livre, estão em [`references/entrevista-tres-raias.md`](references/entrevista-tres-raias.md#perguntas-semente--condicionais).

> **Wiki antiga sem `00`**: wikis criadas antes da v2.10.0 não têm o arquivo. Ao retomar uma delas, reconstruir o `00` **pedindo o requisito original ao usuário** — não derivar do PRD. PRD derivado de PRD não é oráculo, e o `feature-quality-gate` vai marcar o relatório como *oráculo degradado*.

### Adendo ao requisito — quando o pedido cresce durante a implementação

O solicitante pede **mais uma coisa** no meio da implementação, na mesma branch e no mesmo PR. Sem
procedimento, o pedido vai direto para o código e os testes dele nascem **do código** (caso em
[`references/casos-medidos.md`](references/casos-medidos.md#adendo-ao-requisito)). O Adendo é **só**
para o que o solicitante pediu ou respondeu, com fonte e texto verbatim; achado de revisão é `P-nn`.

**Procedimento**, na ordem:

1. **Registrar no `00`** uma seção `## Adendo N — {YYYY-MM-DD}` com Fonte (quem, como chegou,
   fidelidade), o Texto Original **verbatim** do pedido novo (mesmo regime de imutabilidade) e a
   decomposição em `RQ` novos, **continuando a numeração** (`RQ-09`, `RQ-10`…). Nunca reescrever
   `RQ` existente para "acomodar" o adendo: se ele muda uma cláusula antiga, a antiga fica e o
   adendo a declara na coluna `Substitui` (sem `(parcial)`, os scripts a tratam como substituída,
   mesmo quando ela nasceu noutro Adendo). A resposta a uma `Qn` entra do mesmo jeito: a pergunta passa a
   `respondida no Adendo N`, a `RQ` afetada passa a `fechada` ou `substituída por RQ-nn (Adendo N)`,
   e o passo `**Bloqueado por**` dela perde a marca
2. **`## Cobertura do Requisito` do `01`** ganha as linhas dos `RQ` novos; o PRD ganha os passos
   novos ao final (`N+1`…), citando o adendo. Passo antigo que muda por causa do adendo é marcado
   inline com `*(alterado em {data}: adendo N)*`. Com `07-tickets/`: Adendo com `**Responde a**: —` →
   a `RQ` nova ganha ticket novo no fim da numeração (regra 11 da `feature-tickets`); Adendo que responde
   a uma `Qn` → a `RQ` vai para o ticket bloqueado por essa `Qn`, que troca a `RQ` pela substituta (ou a
   mantém, agora `fechada`) e tira a `Qn` de `**Bloqueado por**`, sem renumerar (regra 8)
3. **Reinvocar a `feature-test-design` só para o adendo**: entrada é o `00` (com o adendo) e o
   `04` existente; saída são cenários `CT` novos, em numeração contínua, com mutantes.
   **Antes** de escrever qualquer linha de código do adendo
4. **`03`** ganha a seção do passo novo e o item "Adendo N incorporado" na Verificação Final
5. Só então implementar

**Critério adendo × wiki nova**: mesma branch e mesmo PR → adendo. Branch nova ou PR novo → wiki
nova com `## Natureza da Wiki: evolução` e a ancestral apontada.

Antes de registrar o adendo, leia o template dele em [`references/template-00-requisito.md`](references/template-00-requisito.md#template-do-adendo).

---

## Arquivo 01: Plano de Ação (PRD)

**Path**: `wikis/specs/{branch}/{feature}/01-plano-acao.md`

**Propósito**: PRD completo — deve ser detalhado o suficiente para um agente implementar sem ambiguidade.

**Obrigatório incluir**:
- **Natureza da Wiki**: nova / evolução / correção / ajuste / refatoração + wiki ancestral — **decide se o quality gate roda regressão**
- **Cobertura do requisito**: tabela `RQ` → passos que o atendem; toda cláusula do `00` precisa aparecer; `RQ` aberta aparece com `**Bloqueado por**: RQ-nn (aberta — Qn)`. A `P-nn` entra quando a correção dela é um passo (novo ou alterado); o elo obrigatório dela, que o `rastreabilidade.sh` confere, é o CT com `Origem` = `P-nn`
- Objetivo claro em 1-2 parágrafos
- Contexto e problema que resolve
- Análise dos arquivos/código existente que será tocado — é onde aterrissa a raia fato da entrevista
- **Decisões de Desenho**: uma linha por decisão da raia desenho que não passou nos três portões de ADR ("nenhuma" é resposta válida)
- **Channel de log da feature** (ver seção "Padrão de Log" abaixo)
- **Autorização**: policies, gates, middleware, guards — quais serão criados/modificados
- **Rotas**: endpoints a registrar, middleware aplicado, naming convention
- **Superfície de UI**: telas/componentes que o usuário vê ou opera (Filament, Livewire, Blade, Inertia) — **sem linha aqui não há costura `browser`, e sem ela não há `05`** ([Gate do `05`](#gate-do-05-browser)). Se não houver UI, declarar explicitamente "Sem superfície de UI"
- **Variáveis de Ambiente**: `.env` keys necessárias, config publish, defaults
- **Eventos/Listeners/Observers**: se a feature emite ou escuta eventos, hooks de model
- **Jobs/Queues**: queue connection, timeout, retries, backoff — quando aplicável
- **Modelo de Execução**: quantos requests o caminho principal custa, o que é adiado, memoizado e cacheado (ou "um request, sem trabalho adiado")
- **Impacto em Features Existentes**: regression risk, o que pode quebrar
- **Rollback**: como reverter se algo der errado (migration down, feature flag, etc.)
- **Dependências**: composer/npm packages necessários, versões mínimas
- **Riscos**: áreas de incerteza, dependências externas, prazos apertados
- Passos de implementação numerados com:
  - Path exato de cada arquivo a criar/modificar
  - Assinatura de classes, métodos, interfaces
  - Lógica de negócio detalhada
  - **Logs em todas as etapas de execução** — cada passo que executa lógica deve especificar quais logs emitir (ver seção "Padrão de Log" abaixo)
  - Campos de DB com tipos, nullable, índices, constraints
  - Mapeamentos de campos (ex: API → DB)
  - Tratamento de erros esperados
  - `**Bloqueado por**: RQ-nn (aberta — Qn)` quando o passo implementa `RQ` aberta — o passo não é executado até a resposta
- Skills a invocar em cada passo (lista em [`references/template-01-plano.md`](references/template-01-plano.md#skills-disponíveis-para-referenciar-no-prd))
- Referência ao `04-casos-de-teste.md` (não duplicar cenários aqui)
- Passos de verificação (pint, tests, artisan commands)
- Passos de commit (gitmoji + escopo + mensagem)

> **Integração com Ponytail**: Após a wiki ser aprovada, o Ponytail deve ser a skill de execução ativa durante toda a implementação. Ele garante que cada passo do plano seja executado com o mínimo de código necessário (reutilização → stdlib → feature nativa → uma linha → mínimo que funciona). Após implementar, rodar `/ponytail:ponytail-review` no diff para validar contra over-engineering. Atalhos deliberados devem ser marcados com `ponytail:` comment. Ver o [README do repositório](https://github.com/gsferro/laravel-ai-skills/blob/main/README.md#passo-a-passo-da-integração) para o passo a passo completo da integração.

Antes de escrever o `01`, leia o template em [`references/template-01-plano.md`](references/template-01-plano.md)
— as seções acima, com a exceção da infra compartilhada (força regressão) e os gates de CT-B e de tela de escrita.

---

## Padrão de Log — `[Classe@Método] mensagem`

Antes de especificar os logs de cada passo do PRD, leia [`references/padrao-de-log.md`](references/padrao-de-log.md):
por que o padrão, anatomia, os sete níveis descritos, contexto estruturado, exemplos,
`Log::shareContext`, driver JSON, teste de log em Pest e a trait de logging do projeto.

### Formato Obrigatório — em todos os logs do projeto

```
[{Classe}@{Método}] {mensagem descritiva} | {parâmetro principal}: {valor} - {contexto adicional}
```

### Regras de Escrita

1. **Prefixo entre colchetes**, sem espaços dentro: `[Classe@Método]`
2. **Mensagem em português**, descrevendo a **ação executada**, não o estado ("Membro associado com sucesso", não "Membro foi associado")
3. **Pipe `|`** entre a mensagem e os parâmetros; **hífen `-`** entre parâmetros de contexto
4. **Parâmetro principal sempre que possível**: IDs, slugs, status — o que identifica o registro manipulado
5. **Nível = severidade da ação**: `fail()` de Livewire → `warning`; `catch` de exception que **interrompe** → `error`; `catch` de exception **tratada/ignorada** → `warning`; sistema/API indisponível → `critical`; sucesso esperado → `info`; detalhe intermediário → `debug`
6. **Máximo de contexto estruturado**: SEMPRE o segundo parâmetro `array $context`, com IDs, payloads e snapshots de estado; exception vai como `'exception' => $e`

### Como Implementar no Plano

Para **cada passo de implementação** no PRD, especificar: (1) **quais métodos** têm log e em que
pontos (início, sucesso, falha, decisão de fluxo, `catch`, `fail()` de validação); (2) **o channel** —
`Log::channel('{feature-name}')` em todos os logs da feature; (3) **o nível**, pela regra 5; (4) **a
mensagem**, já escrita completa no formato; (5) **o context**, com todos os campos relevantes. Trait de
logging no projeto (`Grep` por `trait.*Logging` em `app/`): usá-la; o plano declara a abordagem.

> **Anti-padrões** (NUNCA): `Log::info('...')` sem o channel da feature; mensagem genérica
> ("Processando...", "Erro ocorrido") sem `[Classe@Método]` e parâmetro principal; log só no `catch`
> (logar também sucesso e decisão de fluxo); context vazio; `error` em `fail()` de validação; `info`
> em exception.

---

## Arquivo 02: Decisões Arquiteturais (ADR)

**Path**: `wikis/specs/{branch}/{feature}/02-decisoes-arquiteturais.md`

**Propósito**: Registrar o "porquê" das escolhas — não o "o quê". Usa formato **ADR (Architecture Decision Record)** para padronizar e facilitar consulta futura.

**Três portões — ADR só quando os três valem**: (1) difícil de reverter, (2) surpreendente sem
contexto, (3) resultado de trade-off real. Falta um → sem ADR; a decisão vira uma linha em
`## Decisões de Desenho` do `01`. Cada ADR declara `**Portões**: difícil de reverter ✅ ({porque}) ·
surpreendente ✅ ({…}) · trade-off ✅ ({…})`. **`02` com zero ADR é resultado válido** — escreve
"Nenhuma decisão passou nos três portões"; a `## Superfície Livewire`, quando exigida, continua no
`02` (estudo §2.4, §2.5: sem portão, o `02` vira preenchimento de template).

**Em cada ADR, incluir**:
- A justificativa da decisão, citando módulo ou classe por nome — sem `arquivo:linha` nem contagem ([Path e número por arquivo](#path-e-número-por-arquivo))
- Alternativas consideradas e por que foram descartadas
- Trade-offs aceitos
- Restrições externas (APIs, limites de parceiros, compliance)
- Padrões reutilizados de outras partes do sistema
- Link entre decisões relacionadas (ex: "Refine ADR-01")
- Decisões overridden (quando uma ADR substitui outra)

Antes de escrever o `02`, leia o template em [`references/template-02-adr.md`](references/template-02-adr.md).

---

## Arquivo 03: Progresso / Tracking

**Path**: `wikis/specs/{branch}/{feature}/03-progresso.md`

**Propósito**: Checklist de implementação para rastrear o que foi feito e retomar de onde parou.

**Estrutura**: Seções com checkboxes `- [ ]` agrupadas pelos mesmos passos do `01-plano-acao.md`.
No topo, uma linha `**Estado**: em planejamento | em implementação | em revisão | concluída — {data}`
(step 4 → implementação → step 9 → step 11), que o `indice.sh` da `feature-tickets` lê.

**Checkbox só fecha com evidência inline.** Formato `- [x] {item} — {evidência}, {data}`; item sem evidência continua `[ ]`. A evidência inline é o que torna o lote impossível — não há o que colar (caso em [`references/casos-medidos.md`](references/casos-medidos.md#arquivo-03--checkbox-com-evidência-inline)). Conferência: `bash {skills}/feature-wiki/scripts/checkbox-sem-evidencia.sh {wiki}` silencioso na Verificação Final.

**Duas seções que só existem para o step 10 e o step 11**: `## Conformidade com Rules` (uma linha por rule cujo glob casa o diff) e `## Quality Gate` (ciclo, veredito, data). Enquanto a segunda estiver vazia, a feature **não** está concluída e o PR não abre.

**`## Auditoria Pré-Implementação`** guarda a saída dos steps 4 a 6 (entendimento confirmado, confronto código × afirmação, revisão profunda, classe irmã, Ponytail); **`## Testes`** é preenchida no step 7; **`## Tickets`**, no step 8 (`Não fatiado — …` ou as linhas da `feature-tickets`, no formato de `{skills}/feature-tickets/references/template-ticket.md`); **`## Revisão do Diff (step 9)`**, no step 9; **`## Candidatos a Rule`**, no step 12; **`## Despachos`**, a cada disparo, com a coluna `Custo`. **`## Referências Abertas`**: uma linha por arquivo de `references/` aberto, com o step — a verificação explícita de que a instrução foi carregada (estudo §2.4).

**Validação de espelho**: verificar que a estrutura de seções do `03-progresso.md` espelha exatamente os passos do `01-plano-acao.md` — se o plano tem 8 passos, o progresso tem 8 seções correspondentes.

Antes de escrever o `03`, leia o template em [`references/template-03-progresso.md`](references/template-03-progresso.md) — todas as seções acima, na ordem.

---

## Glossário do Projeto

**Path**: `wikis/glossario.md` — global, fora da pasta da feature, vai no PR. Só glossário: sem
implementação, spec ou rascunho. Escrito nos steps 3 a 5 **quando o termo é decidido**, nunca em
lote; termo do requisito que conflita com ele vira pergunta. Lido pela `feature-test-design`
(Gherkin, e o `fw-adversario-ct` pode recebê-lo) e pelo `feature-quality-gate` (L7). Termo decidido
numa feature não sobrevivia para a próxima, e rule é escopada por path (estudo §2.2, §2.4). Template e
pergunta de conflito: [`references/glossario.md`](references/glossario.md).

---

## Arquivos 04 e 05: Casos de Teste — delegados à `feature-test-design`

**Paths**: `wikis/specs/{branch}/{feature}/04-casos-de-teste.md` e `05-casos-de-teste-browser.md`

A **derivação e a escrita dos casos de teste não pertencem a esta skill**: ela delega à
`feature-test-design` (`{skills}/feature-test-design/SKILL.md`) no step 7, depois do Ponytail, sob o
contrato de [`references/delegacao-casos-de-teste.md`](references/delegacao-casos-de-teste.md#o-contrato-da-delegação)
(por que delegar, entrada em ordem de autoridade, saída, o que é proibido passar). Corte depois do
step 7 re-sincroniza o `04` no mesmo passo (step 6, *Corte depois do step 7*).

**O `01-plano-acao.md` não é fonte de comportamento esperado.** Se a única forma de saber o que
o sistema deve fazer é ler o PRD, o `00-requisito.md` está incompleto — e isso é achado, não
atalho.

### Gate do `05` (browser)

**O `05` existe se e só se `## Costuras de Teste` do `04` tem uma linha com costura `browser`**,
confirmada no step 7 (estudo §3.4). O que justifica uma linha `browser` é haver linha na
`## Superfície de UI` do PRD **e** o cenário afirmar sobre algo que **só o navegador prova** —
JavaScript executado, console/erro de JS, acessibilidade, cor/tema, layout.

Tudo o mais que parece "de tela" em Filament é costura `componente Livewire/Filament`, roda em
milissegundos, sem Node e sem Playwright, e pertence ao `04`: validação de formulário,
gravação, listagem, busca, filtro, ação de tabela, notificação e autorização na tela.

> **Gate de tela de escrita**: para toda rota `create`/`edit` da `## Superfície de UI`, o `04`
> precisa ter um cenário de **gravação por componente**. *Uma tela aberta não é uma tela que
> grava* — um `GET` fica verde com o salvamento quebrado.

Nenhuma costura `browser`: **não criar o `05`** e registrar no `04` a seção `## Sem CT-B` com o motivo.

### Execução dos testes — `executor-ct` e CT-B

O teste Pest de backend nasce do Gherkin do `04`, escrito por quem **não implementou** — no Claude
Code, o sub-agente `fw-executor-ct` ([`agents/fw-executor-ct.md`](agents/fw-executor-ct.md)). A
**execução** dos CT-B contra a UI real é desta skill, no step 10, em loop pelo `fw-executor-ctb`
([`agents/fw-executor-ctb.md`](agents/fw-executor-ctb.md)). Antes de escrever teste a partir do
`04` ou do `05` — despachando um dos dois ou, em host sem sub-agente, em linha —, leia
[`references/delegacao-casos-de-teste.md`](references/delegacao-casos-de-teste.md#contrato-do-construtor-de-testes-executor-ct):
os dois contratos — entrada, classificação de todo vermelho em a/b/c antes de qualquer edição,
proibições e saída em formato fixo.

Nos dois, o hook nega Edit/Write em `app/`, `database/migrations/`, no `00`/`04`/`05`, no `03` e em
`07-tickets/` (quem grava o `03` e o `Status` do ticket é a sessão): correção de especificação volta
como texto, e a sessão grava. O `executor-ct` formata só os arquivos de teste do lote
(`vendor/bin/pint {arquivos}`), nunca `pint --dirty`. `maxTurns` é o teto mecânico do loop (hipótese
a calibrar); corte por ele = estado parcial.

- **Vermelho é classificado antes de qualquer edição**: (a) teste errado → corrige o teste;
  (b) **implementação divergente da especificação → não corrige**, deixa vermelho e registra com
  a saída literal; (c) flake → anota. Máximo 3 iterações por arquivo
- **Vermelho por (b) é resultado válido, não falha do ciclo** — é a divergência que se queria
  capturar; sub-agente que "conserta" a aplicação destrói o instrumento. No backend, a sessão o
  roteia: o CT vermelho já é o oráculo e a correção vai no código; se a divergência revela o que o
  `00` não diz, vira pergunta ou premissa como no step 9. No CT-B (loop do step 10), 3 iterações
  com vermelho → blocker no `03`

### Fatos do `pest-plugin-browser`

Antes de escrever, rodar ou revisar qualquer CT-B, leia `{skills}/feature-test-design/references/pest-plugin-browser.md`
— a fonte única (aqui só os dois fatos do step 3). **Assertion de console ou de status nunca é o oráculo único de um CT-B.** Todo cenário precisa de
pelo menos uma assertion sobre o que ele afirma — o elemento, o valor ou o registro.

### Playwright MCP na validação (OPCIONAL — ferramenta de observação)

> **O `pest-plugin-browser` atesta. O Playwright MCP observa.**
>
> O CT-B é sempre um teste Pest versionado. O MCP nunca produz cobertura, nunca entra no `05`
> como evidência e nunca substitui um CT-B — ele existe para o agente **ver** a página quando o
> teste falha.

No step 3, no loop do CT-B e no step 10 — com ou sem o MCP disponível —, leia
[`references/playwright-mcp.md`](references/playwright-mcp.md): por que o MCP e não as ferramentas
de debug do plugin, onde ele entra, a configuração e o fallback sem ele. As regras duras ficam
aqui:

- **`--isolated` é obrigatório.** O default do MCP é perfil persistente: o login sobrevive entre
  sessões e, com uma URL errada, o agente pode clicar em produção autenticado.
- **Somente `localhost`.** Apontar para staging ou produção é proibido pela skill.

**Regras de uso**:

1. **Ref nunca entra em teste.** `ref=e5` é válido "until the next page change". Só o resultado
   de `browser_generate_locator` vai para o CT-B.
2. **`browser_find` antes de `browser_snapshot` cru** — snapshot em loop acumula contexto.
3. **Proibido `browser_run_code_unsafe`.** Se o cenário exige, ele exige um CT-B.
4. **Proibido `--caps=vision`.** Clique por coordenada XY destrói o determinismo.
5. **Sessão MCP não é cobertura.** Nada de "validado via MCP" sem CT-B correspondente.
6. **Na causa (b), o MCP é só leitura** — serve para descrever a divergência, nunca para contorná-la.
7. **Feature com dado sensível** (PII, pagamento): sem `browser_take_screenshot` versionado.

---

## Execução de Testes com Pest 5

Detectar a versão do Pest no step 3. Se o projeto está em **Pest 5** (requer **PHP 8.4+** e PHPUnit 13), usar os recursos abaixo; em Pest 4, cair para `vendor/bin/pest --filter`.

Com Pest 5 detectado, antes de implementar o primeiro passo do PRD — e antes de instalar ou
atualizar o Pest, ou de rodar a suíte com `--tia`, `--agent` ou `--mutate` —, leia
[`references/pest-5.md`](references/pest-5.md): instalação, TIA (onde encaixa em cada passo e o
watch dos CT-B), agent plugin, por quê e uso do `scripts/pestw.cmd` no Windows e os demais recursos. As regras duras
ficam aqui:

- **`--testsuite=A --testsuite=B` só honra o último.** Uma suíte por comando, as duas saídas coladas
- **`pest --mutate` dá 100 % falso no Windows.** Regra: **score só vale com `Duration` compatível com N × tempo dos testes cobridores e com a lista de sobreviventes.** No Windows, lançar por `{skills}/feature-wiki/scripts/pestw.cmd`, na raiz do projeto (por quê e uso em [`references/pest-5.md`](references/pest-5.md#pest---mutate-no-windows--o-lançador-cmd))

**Forma canônica: `--parallel --tia`** — não é pré-requisito técnico, mas é a invocação da doc oficial: o TIA corta **quanto** roda, o parallel **quanto tempo** leva.

**Nunca `--parallel` no comando de browser** (multiplica navegadores e exige DB por worker): CT-B em série (`vendor/bin/pest tests/Browser --filter={Feature}`); `--parallel --tia` fica para o backend.

> ⚠️ **Nunca usar `--tia` no comando que roda o suite em CI.** A doc do Pest é explícita: o pipeline deve rodar o suite completo. O TIA em CI existe só num job dedicado de baseline (`--tia --coverage --fresh`, artefato `pest-tia-baseline`).

Sobre o `--agent` (agent plugin):
**Onde encaixa**: durante a implementação de um passo do PRD, para confirmar uma premissa antes de escrever o teste definitivo. **Não substitui** os CTs do `04`/`05` — o `--agent` é efêmero e não fica versionado. Uma verificação via `--agent` que se mostre valiosa deve virar CT no arquivo correspondente.

---

## Citações de código — `arquivo:símbolo:linha`

Antes de escrever ou reverificar uma citação (steps 3, 5 e 10), leia [`references/citacoes-de-codigo.md`](references/citacoes-de-codigo.md):
as duas classes de erro medidas, exemplos do formato e o que o `scripts/citacoes.sh` confere.

**Formato obrigatório**: `{path relativo à raiz do projeto}:{símbolo}():{linha}` ou, para
intervalo, `:{inicial}-{final}`. O símbolo (método, função, constante ou chave de array que a linha
contém) sobrevive ao deslocamento e permite conferir sem abrir o arquivo. Path curto (`Login.php:172`)
só depois do completo no mesmo documento. Citação sem símbolo não passa no step 5 nem no step 10.

**Conferência mecânica** — `bash {skills}/feature-wiki/scripts/citacoes.sh {wiki}`, na raiz do
projeto; toda linha de saída é uma citação a corrigir na fonte, nunca a apagar para "passar". O
resultado vai para a Verificação Final do `03` (`— citacoes.sh exit 0, {data}`); a L2 do quality gate
roda o mesmo script (a L1 roda o `ids-ct.sh`).

---

## Checklist Final da Skill

Antes de encerrar a invocação, na ordem dos steps; Delegação e Referências valem para todos.

### Requisito capturado e planejamento (steps 0 a 3)

- [ ] `00-requisito.md` criado com Fonte, Texto Original **verbatim** e Fidelidade declarada
- [ ] Branch lida, pasta criada, wiki existente verificada (retomar/sobrescrever/incrementar), nome da feature confirmado com o usuário
- [ ] `search-docs` consultado para **cada stack** que o PRD toca, com a origem citada no plano; lacunas (Pest 5, Playwright/`pest-plugin-browser`, pacotes de terceiros) cobertas por doc oficial; `database-schema` e leitura de arquivos feitos
- [ ] Rotas, policies, config, composer e wikis existentes verificados; APIs de terceiros inspecionadas (vendor ou docs); dados do usuário validados contra o DB (quando aplicável); factories e states confirmados para todos os CTs
- [ ] `wikis/glossario.md` lido (se existe); termo decidido gravado nele na hora; termo em conflito com ele virou pergunta
- [ ] Tabela `## Superfície Livewire` no `02` preenchida — **sempre** que a feature cria página, widget ou componente: métodos públicos, propriedades públicas sem `#[Locked]` e os arrays de estado do framework que o código consome. Pacote de terceiro acrescenta os quatro greps do vendor, com um model por linha
- [ ] Stack de testes verificado: versão do Pest, `pest-plugin-browser`, Playwright, traits em `tests/Pest.php`; **baseline** da suíte completa em `{base}` registrada antes do primeiro commit, falhas pré-existentes por nome
- [ ] Model novo cujo nome de tabela não é o plural inglês inferido declara `$table`

### Requisito, entrevista e documentação (step 4)

- [ ] Requisito decomposto em `RQ-##`, cada uma com o trecho literal de origem e `Estado` (`fechada` · `aberta — Qn` · `substituída por RQ-nn (Adendo N)` · `decomposta em RQ-nn, RQ-mm`); `## Premissas` e `## Fora de Escopo (declarado)` presentes; nenhum path de código no `00`
- [ ] Entrevista em três raias: formato ❓/➡️ com `Qn` numa sequência única, rodadas pela fronteira, só pergunta que toca `RQ`/`P-nn`, nenhuma pergunta de fato feita ao usuário, premissa de comportamento como pergunta de requisito com a `➡️` que falha fechado
- [ ] Perguntas-semente consideradas **quando** o requisito tem papéis, visibilidade, notificação ou texto livre: acumulação de papéis **par a par**, participante histórico × recorte de visibilidade, destino do link de toda notificação, teto de todo texto livre
- [ ] Perguntas da raia requisito em `## Perguntas ao Solicitante`, com recomendação, levadas ao solicitante antes de implementar; `Entendimento confirmado: …` no `03` antes do step 5
- [ ] PRD com `## Natureza da Wiki` (+ ancestral se não for "nova"), `## Decisões de Desenho` (ou "nenhuma"), `## Modelo de Execução` (ou "um request, sem trabalho adiado"), `## Superfície de UI` (ou "Sem superfície de UI"), Autorização, Rotas, Variáveis de Ambiente, Eventos, Jobs, Impacto, Rollback, Dependências, Riscos e Filosofia de Implementação (Ponytail); passos numerados com skills referenciadas e logs em todas as etapas
- [ ] `## Cobertura do Requisito` mapeia **toda** `RQ` a passo(s) ou justificativa, e a `P-nn` cuja correção é um passo; passo que depende de `RQ` aberta está `**Bloqueado por**: RQ-nn (aberta — Qn)` e não foi implementado
- [ ] `02` só com ADR que passa nos três portões, com a linha `**Portões**`, em formato ADR — ou "Nenhuma decisão passou nos três portões"; sem `arquivo:linha` nem contagem fora da `## Superfície Livewire`
- [ ] `03` com a linha `**Estado**`, checkboxes espelhando **exatamente** os passos do `01` e as seções do template; extras `05-*` criados se necessário
- [ ] Log: channel da feature referenciado em todos os passos; `[Classe@Método] mensagem` e `$context` estruturado em cada log do PRD; log **não** vira CT no `04` (quem confere é a dimensão D do gate), salvo requisito de trilha de auditoria, que é `RQ`

### Validação (steps 5 e 6)

- [ ] Revisão profunda executada — premissas do plano re-validadas contra o código, correções registradas no `03`; confronto código × afirmação perguntado como *"o `01` diz X; `app/…` faz Y — qual vale?"*, com a resposta no `03`
- [ ] **Varredura da classe irmã** para toda classe nova (`grep -rnF` pelo FQCN), com a irmã e as ocorrências no `03`
- [ ] `/ponytail:ponytail-review` sobre `01`/`02` com sugestões aplicadas, **antes** do step 7; corte posterior ao step 7 re-sincronizou o `04` (CT atingido `@obsoleto`)

### Casos de teste (step 7)

- [ ] **`feature-test-design` invocada** com o `00` como entrada primária — o `04` não foi escrito inline a partir do PRD, e a implementação não entrou como entrada
- [ ] `04` recebido com perfil de risco, varredura SFDIPOT, mapa de regras, técnica por regra e **mutantes com o cenário e a `Asserção que mata`**; `RQ` aberta listada sem cenário; toda rota `create`/`edit` da `## Superfície de UI` com cenário de **gravação por componente**
- [ ] `## Costuras de Teste` confirmada com o desenvolvedor antes da revisão adversarial (`Confirmada` em toda linha; costura trocada re-derivou o grupo); revisão adversarial despachada pela sessão principal e todos os achados fechados
- [ ] Perguntas devolvidas (`Q?n` renumeradas na sequência `Qn`) em `## Perguntas ao Solicitante` (raia requisito) ou decididas com o desenvolvedor (raia desenho)
- [ ] `05` existe **se e só se** uma costura é `browser` — com as dependências de CT-B confirmadas ou como passo no PRD; sem ela, `## Sem CT-B` com o motivo no `04`
- [ ] `## Testes` do `03` lista os arquivos de teste com os CT/CT-B de cada um; plano confirmado com o usuário antes de implementar

### Tickets (step 8, condicional)

- [ ] Sinais do step 8 conferidos; `/feature-tickets` sugerido quando algum cruzou — nunca invocado pelo agente; sem fatiamento, `Não fatiado — …` em `## Tickets` do `03`
- [ ] Fatiado: cada ticket numa sessão nova ou num `construtor` com só a fatia; step 9 só com todo ticket `concluído`

### Revisão do diff e reconciliação (steps 9 e 10, antes do PR)

- [ ] **Step 9 antes da reconciliação**, por quem não implementou: cada achado confirmado que o solicitante não escreveu virou `P-nn` + CT com `Origem` = `P-nn` (pela `feature-test-design`) + correção, nessa ordem — pergunta ao solicitante quando contradiz ou estende o pedido; ticket novo no fim, com `07-tickets/`; achado que viola ou omite `RQ` existente virou CT com `Origem` = `RQ-nn` + correção, sem `P-nn` (com `07-tickets/`, o CT no ticket da `RQ`); nenhum achado entrou como Adendo; achados e rejeições em `## Revisão do Diff (step 9)` do `03`
- [ ] No Claude Code: `/code-review high {base}...HEAD`, sem `--fix`; passe de eixos em sub-agente cego ao `01`/`03`, com alvo `git diff {base}...HEAD -- . ':(exclude)wikis'` e `git status --porcelain` igual antes e depois; `## Superfície Livewire` re-varrida sobre o código final **antes**
- [ ] Falsificabilidade dos CTs novos provada por `git stash` — cada um falha sem a correção **ou** está declarado "não falsificável nesta pilha", com o motivo
- [ ] Silenciosos, com a saída colada na Verificação Final: `rastreabilidade.sh`, `checkbox-sem-evidencia.sh`, `citacoes.sh`, `ids-ct.sh`, `conformidade-rules.sh` e, com `07-tickets/`, `indice.sh --check`
- [ ] Todo `[x]` do `03` com evidência inline, nenhum fechado em lote; todo número com o comando que o gerou; toda degradação com a prova negativa (`php -m`, `ls vendor/…`)
- [ ] Cada desvio do `03` com a edição no `01`/`02`/`04`/`05` de origem, marcada `*(alterado em …)*` — nenhuma afirmação do `01`/`02` contradiz o código
- [ ] Contagens da wiki inteira derivadas por `grep -c`; todo número **corrigido** procurado na wiki inteira (`grep -rn "{valor antigo}"`)
- [ ] `pest --mutate` com duração plausível e sobreviventes listados (no Windows, via `scripts/pestw.cmd`)
- [ ] Pedido novo do solicitante virou `## Adendo N` no `00`, com `RQ` novos, e a `feature-test-design` foi reinvocada para ele **antes** do código
- [ ] `## Conformidade com Rules` com uma linha por rule cujo glob casa o diff — nenhuma `violada`
- [ ] Docs de usuário (pt **e** en), CHANGELOG e README reconciliados; nenhuma frase sem `RQ`, `P-nn` ou ADR de origem
- [ ] CT-B escritos e rodados via sub-agente, divergências classificadas (a/b/c); "Desenhado × Implementado" do `05` preenchido, divergências em "Desvios do Plano" e na fonte; Playwright MCP, se usado, só como observação (`--isolated --headless --caps=testing`, nenhum ref em teste, nenhuma sessão MCP como cobertura)
- [ ] Notas de implementação e retrospectiva breve escritas

### Quality Gate e PR (step 11)

- [ ] **`feature-quality-gate` invocado** — no Claude Code, via `fw-qa-gate` sem Edit/Write; `06` gravado verbatim com **só** o que está entre `<<<06` e `>>>06`; `git status --porcelain` de antes e de depois iguais; `Custo` em `## Despachos`; ciclo/veredito/data em `## Quality Gate` do `03`
- [ ] `APROVADO` só com todas as dimensões verificadas; dimensão não verificada ou `RQ` aberta sem implementação → no máximo `APROVADO COM DÉBITO`, com as causas no `03`
- [ ] `06-relatorio-qa.md` **existe** na pasta da wiki — a ausência dele é blocker do PR
- [ ] Se `REPROVADO`: achado roteado ao destino (especificação / implementação / teste) e reciclado; o que o solicitante não escreveu seguiu `P-nn` → CT → correção; omissão de `RQ` existente não virou `P-nn`
- [ ] **Só depois do veredito**, nesta ordem: `03` com `**Estado**: concluída — {data}`; `wikis/specs/INDEX.md` regenerado pelo `indice.sh` (se a `feature-tickets` está instalada); PR aberto com link da wiki e veredito do `06` na descrição

### Rules (step 12) e após o merge

- [ ] `requirement-to-rule` invocada com o path da wiki, na sessão principal ou em sub-agente que herda MCP — nunca `analista`; **um** prompt de aprovação, o dela; commit na branch do PR aberto; a linha dela em `## Candidatos a Rule` do `03`
- [ ] Depois do merge: channel de log ajustado (level reduzido ou removido)

### Delegação (Claude Code, todos os steps)

- [ ] Todo disparo em `## Despachos` do `03`, com modelo, cegueira, `Custo` e auditoria do retorno; tarefa em linha por exceção com "Sem despacho — motivo"; nenhum `general-purpose` sem `model` explícito
- [ ] Passe de eixos do step 9, revisão adversarial (step 7) e step 11 em sub-agente **cego** — ou a degradação declarada no `03` e no cabeçalho do `06`
- [ ] Todo retorno auditado (presença, integridade com `git diff --stat` antes/depois, amostragem); número sem comando, `git diff --stat` de untracked ou "não encontrado" sem prova negativa **reprovado e refeito**, com a reprovação no quadro
- [ ] `ls .claude/agents/fw-*.md` antes do primeiro despacho; fallback `general-purpose`/{model} no quadro quando o agente faltou ou devolveu *"guarda-subagente.sh nao encontrado"*
- [ ] Rota `mecânico` com **um item por despacho**; sem `Explore` ou sem `SendMessage`, o fallback declarado rodou e está no quadro

### Referências (todos os steps)

- [ ] Referências abertas registradas em `## Referências Abertas` do `03` e declaradas no relatório final da invocação — uma linha *"referências lidas: {arquivo} (step N), …"*, com cada arquivo de `references/` aberto e o step em que foi aberto
  - A linha cobre o mínimo da tabela do Índice ou diz por que pulou: todo despacho → `roteamento-e-despacho`; step 3 → `pesquisa-step-3`; step 4 → `template-00-requisito` a `template-03-progresso`, `entrevista-tres-raias`, `padrao-de-log`, `estrutura-criada`; termo decidido → `glossario`; step 7 → `delegacao-casos-de-teste`; pré-9 → `pesquisa-step-3`; step 10 → `citacoes-de-codigo`; teste escrito a partir do `04`/`05` → `delegacao-casos-de-teste`. O step 12 não abre reference desta skill: segue o `SKILL.md` da `requirement-to-rule`

## Skills Companheiras

A feature-wiki é a primeira estação de uma esteira de skills que cobrem o ciclo completo:

| Camada | Skill | Responsabilidade | Boundary |
|--------|-------|------------------|----------|
| **Comunicação** (agent ↔ usuário) | [Caveman](https://github.com/JuliusBrussee/caveman) — modo padrão `ultra` | Prosa terse — corta ~75% dos tokens removendo fluff, artigos, fillers | **NÃO aplica em arquivos wiki** (00-06), código, commits, PRs |
| **Planejamento** (estrutura de documentação) | feature-wiki | requisito + PRD + ADR + tracking + padrão de log | não deriva caso de teste — testar o próprio plano confirma o plano |
| **Especificação de teste** | `feature-test-design` | deriva o `04`/`05` do **`00-requisito.md`**, com técnica formal e gate de mutantes | não escreve código nem corrige implementação |
| **Fatiamento** (condicional) | `feature-tickets` | fatia a wiki em tickets verticais (`07-tickets/`) quando o plano não cabe numa sessão; gera `wikis/specs/INDEX.md` | só o usuário invoca; não fatia o que cabe numa sessão |
| **Execução** (código) | [Ponytail](https://github.com/DietrichGebert/ponytail) | Mínimo código que funciona — escada de simplicidade | Não corta validação, segurança, tratamento de erros |
| **Qualidade** (QA no agente) | `feature-quality-gate` | Confronta `00-requisito` × PRD × app rodando; audita a consistência wiki × código × docs × rules (dimensão L); roteia achado para especificação / implementação / teste. Roda **antes do PR** | Não corrige nada — só lê, reproduz e reporta |
| **Memória de projeto** (rules) | `requirement-to-rule` | dona do step 12: coleta, gates, o único prompt de aprovação e a gravação da Project Rule em `.ai/rules/` | Só o que é específico da aplicação; ecossistema é guideline do Boost |
| **Orquestração** (Claude Code) | sub-agentes por rota — pasta `agents/` de cada skill, instalados com `cp .ai/skills/*/agents/*.md .claude/agents/` | Modelo por complexidade, contexto por cegueira; quadro de despacho no `03` | A sessão nunca delega captura verbatim do requisito, perguntas ao usuário nem veredito final |

No início da sessão de planejamento, leia [`references/ponytail-caveman.md`](references/ponytail-caveman.md):
ativação do Caveman em `ultra`, fronteira dele com os arquivos wiki e como ativar o trio.

> **Comando correto**: `/caveman:caveman {lite | full | ultra | off}` (com namespace `caveman:`, igual ao `/ponytail:ponytail`). NUNCA usar `/caveman` sem o namespace — o comando não será encontrado.

---
name: feature-wiki
version: 3.5.1
description: >
  Cria estrutura de documentação wiki para uma feature antes de implementá-la.
  Invoque SEMPRE ao iniciar implementação de qualquer feature nova.
  Cria pasta em wikis/specs/{branch} com os arquivos obrigatórios: requisito bruto
  imutável (00), plano de ação (PRD), decisões arquiteturais (ADR) e tracking de
  progresso. O plano de ação deve ser minucioso o suficiente para um agente
  implementar sem ambiguidade.
  A DERIVAÇÃO DOS CASOS DE TESTE (04 e 05) É DELEGADA à skill feature-test-design,
  invocada no step 4 — ela deriva do 00-requisito, nunca do PRD, com técnicas formais
  (partição, valor limite, tabela de decisão, tabela estado x evento) e um gate de
  falsificabilidade por mutantes. Esta skill mantém o gate do 05 (browser só para o
  que só o navegador prova) e o loop de execução dos CT-B por sub-agente.
  Inclui padrão de log obrigatório, channel por feature, etapa de pós-implementação,
  auditoria automática da wiki via /ponytail:ponytail-review, e integração com
  Caveman (comunicação terse, modo padrão `ultra`) e Ponytail (execução minimalista).
  Usa Pest 5 (--parallel --tia, --agent, --mutate) na verificação.
  O quality gate roda ANTES do PR e antes de o 03 dizer "concluída". O step 7 reconcilia
  wiki, docs de usuário, CHANGELOG e .ai/rules com o código: checkbox só fecha com evidência
  inline, desvio corrige o 01/02 de origem, citação é arquivo:símbolo:linha conferida por grep,
  IDs de CT do teste e do 04 são sincronizados nos dois sentidos. Requisito que cresce no meio
  da implementação vira Adendo numerado no 00, com feature-test-design reinvocada só para ele.
  Após os testes passarem, aciona a skill feature-quality-gate (etapa de QA no
  agente), que confronta 00-requisito x PRD x app rodando. No fim, avalia se
  alguma decisão da wiki deve virar Project Rule do Boost e
  submete a decisão ao usuário (skill requirement-to-rule). Exige consulta à
  Documentation API do Boost (search-docs) para cada stack que o PRD toca.
  Toda feature que cria página, widget ou componente exige a tabela ## Superfície Livewire
  no 02 — métodos públicos (que são ações chamáveis por $wire.), propriedades públicas sem
  #[Locked] e os arrays de estado do framework que o código consome ($filters, $pageFilters,
  $tableFilters), cada um com a fronteira aplicada e o arquivo:linha; pacote de terceiro
  acrescenta os quatro greps do vendor. Toda classe nova passa pela varredura da classe irmã
  (grep pelo FQCN de uma irmã já existente, para achar as listas paralelas que o projeto
  mantém à mão em config, seeders e inventários de teste). O PRD declara o ## Modelo de
  Execução — quantos requests a tela custa, o que é adiado, o que é memoizado por request e o
  que é cacheado entre eles —, porque premissa de custo não escrita produz ADR coerente e
  errada. O step 6.5 roda revisão de código do diff por quem não implementou, LOGO APÓS os
  testes passarem e ANTES da reconciliação (7) e do quality gate (8): é o ÚNICO gate que lê o
  diff atrás de defeito de correção, e medido numa feature real foi o mais produtivo de todos.
  No Claude Code ele são dois passes no mesmo lote — `/code-review high {base}...HEAD` (alvo
  explícito, nunca `--fix`) e um passe de eixos Laravel/Livewire por sub-agente cego ao PRD.
  Quando roda no Claude Code, a skill DESPACHA: tarefa de volume e todo julgamento que exige
  independência vão para sub-agente via Agent, com modelo roteado por complexidade (haiku
  mecânico, sonnet construtor, opus analista/juiz) e por cegueira (o juiz não recebe o que o
  viciaria); todo disparo aparece num quadro visível e fica registrado em ## Despachos do 03;
  NUNCA general-purpose sem model explícito; o step 8 roda em sub-agente sem Edit/Write.
---

# Feature Wiki — Documentação Antes de Implementar

> ## O gate que mais pega defeito é o step 6.5 — e ele roda assim que os testes passam
>
> Esta skill tem oito gates. Sete deles leem **o plano, o requisito ou a tela**. Um só lê **o
> diff**, e é o [step 6.5 — Revisão de Código do Diff](#65-revisão-de-código-do-diff-obrigatório-logo-após-os-testes-passarem-e-antes-da-reconciliação),
> com `/code-review` por quem não implementou.
>
> Até a 3.3.0 ele era o step 7.5: o último antes do quality gate, no fim do documento, e o mais
> fácil de adiar. **É também o mais produtivo.** Medido em 2026-09-17, numa feature com wiki
> completa — 43 CTs, revisão adversarial com cinco implementações erradas fechadas, auditoria
> Ponytail com dez cortes aplicados, 61 testes verdes:
>
> | Gate | Achados de correção |
> |---|---|
> | step 5 — revisão profunda (premissas do plano) | 2 (nomes de classe e de método errados no PRD) |
> | step 6 — `ponytail-review` (excesso no plano) | 0 — **por charter**: *"correctness bugs, security holes and performance are explicitly out of scope"* |
> | revisão adversarial do `04` (requisito × cenários) | 0 de correção; 5 de **cobertura**, que é o trabalho dela |
> | suíte de testes verde, 2.383 casos | 1 (enforço de arquitetura do próprio projeto) |
> | **step 6.5 (então 7.5) — `/code-review` no diff** | **7**, dois deles produzindo 500 em produção |
>
> Os sete não eram visíveis para nenhum gate anterior, e o motivo é estrutural: o step 6 exclui
> correção por definição, a revisão adversarial só enxerga o que o **requisito** descreve, e o
> step 8 pergunta *"o requisito foi atendido?"* — nenhum deles pergunta *"este código está certo?"*.
>
> **Não trate o 6.5 como formalidade.** Se o orçamento apertar, corte cenário redundante, não este
> gate. E ele roda **antes** da reconciliação (step 7), não depois: cada achado confirmado muda
> código, cláusula e CT — reconciliar antes de revisar é reconciliar duas vezes.
>
> **Quem revisa não pode ser quem implementou.** No Claude Code isso é construção, não intenção:
> o `/code-review` já roda em sub-agente isolado, e o passe de eixos vai para um sub-agente que
> não recebe o PRD. Ver [Execução e Delegação](#execução-e-delegação-claude-code--roteamento-por-modelo-e-por-cegueira).

## Glossário

| Sigla | Significado |
|-------|-------------|
| **RQ** | Cláusula de requisito — unidade numerada da decomposição do `00-requisito.md` |
| **PRD** | Product Requirements Document — plano de ação detalhado |
| **ADR** | Architecture Decision Record — registro de decisão arquitetural |
| **CT** | Caso de Teste — especificação de um cenário de teste (backend) |
| **CT-B** | Caso de Teste de Browser — cenário E2E validado em navegador real |
| **DB** | Database — banco de dados |
| **FK** | Foreign Key — chave estrangeira |
| **TIA** | Test Impact Analysis — engine do Pest 5 que roda só os testes afetados |

## Índice

- [Quando Invocar](#quando-invocar)
- [Execução e Delegação (Claude Code)](#execução-e-delegação-claude-code--roteamento-por-modelo-e-por-cegueira)
- [Fluxo de Execução](#fluxo-de-execução)
  - [1. Descobrir Branch](#1-descobrir-branch-e-estrutura-de-pasta)
  - [2. Definir Nome da Feature](#2-definir-nome-da-feature)
  - [3. Pesquisa e Contexto](#3-pesquisa-e-contexto-obrigatório-antes-de-escrever)
    - [Superfície Livewire](#superfície-livewire-obrigatório-em-toda-feature-que-cria-página-widget-ou-componente)
    - [Documentation API do Boost](#documentation-api-do-boost-search-docs)
  - [4. Criar os Arquivos](#4-criar-os-arquivos)
  - [5. Revisão Profunda Pós-Escrita](#5-revisão-profunda-pós-escrita-obrigatório)
    - [Varredura da classe irmã](#varredura-da-classe-irmã-obrigatória-para-toda-classe-nova)
  - [6. Auditoria da Wiki com Ponytail-review](#6-auditoria-da-wiki-com-ponytail-review-obrigatório)
  - [6.5. Revisão de Código do Diff](#65-revisão-de-código-do-diff-obrigatório-logo-após-os-testes-passarem-e-antes-da-reconciliação)
  - [7. Pós-Implementação e Reconciliação](#7-pós-implementação-e-reconciliação-obrigatório-antes-do-pr)
  - [8. Quality Gate e abertura do PR](#8-quality-gate-e-abertura-do-pr-obrigatório-antes-do-pr)
  - [9. Candidatos a Rule](#9-candidatos-a-rule-de-projeto-decisão-do-usuário)
- [Arquivo 00: Requisito](#arquivo-00-requisito--fonte-da-verdade)
- [Arquivo 01: PRD](#arquivo-01-plano-de-ação-prd)
- [Padrão de Log](#padrão-de-log--classeétodo-mensagem)
- [Arquivo 02: ADR](#arquivo-02-decisões-arquiteturais)
- [Arquivo 03: Progresso](#arquivo-03-progresso--tracking)
- [Arquivos 04 e 05: Casos de Teste (delegados)](#arquivos-04-e-05-casos-de-teste--delegados-à-feature-test-design)
  - [Playwright MCP na validação](#playwright-mcp-na-validação-opcional--ferramenta-de-observação)
- [Execução de Testes com Pest 5](#execução-de-testes-com-pest-5)
- [Citações de código](#citações-de-código--arquivosímbololinha)
- [Arquivos Extras](#arquivos-extras-conforme-necessidade)
- [Skills Companheiras](#skills-companheiras)
- [Checklist Final](#checklist-final-da-skill)

## Ordem de Leitura para o Agente Implementador

Ao implementar, o agente deve ler os arquivos nesta ordem:
1. **`00-requisito.md`** — entende o que foi **pedido** (fonte da verdade, não o plano)
2. **`01-plano-acao.md`** — entende o que fazer e em que ordem
3. **`04-casos-de-teste.md`** — entende como validar cada passo
4. **`05-casos-de-teste-browser.md`** — se existir: entende como validar a UI e o fluxo do usuário
5. **`02-decisoes-arquiteturais.md`** — entende as restrições e justificativas
6. **`03-progresso.md`** — marca o que já foi feito e retoma de onde parou

---

## Quando Invocar

- Sempre que o usuário pedir para implementar uma feature nova
- Ao iniciar qualquer card/ticket/task de desenvolvimento
- Antes de qualquer `php artisan make:*` ou criação de código
- Quando o usuário pedir para arquitetar ou analisar uma feature antes de implementá-la

### Quando NÃO Invocar

- **Typo fixes**: correções de texto, mensagens, labels
- **Mudanças triviais**: ajustes de config simples, tweak de CSS isolado, mudança de 1-2 linhas sem nova lógica
- **Refactoring puro**: renomear variáveis, extrair método, sem mudança de comportamento
- **Bump de dependência**: atualizar versão de package sem mudança de API
- **Adição de seeders/migrations isoladas**: sem lógica de negócio associada

> **Critério**: se a mudança não adiciona nova lógica de negócio, não altera fluxo de dados e não cria novos arquivos de código → não precisa de wiki.
> **Bug fix com nova lógica**: se o fix introduz nova regra de negócio, novo estado ou novo fluxo → invocar a wiki.

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

### Validado em campo — 2026-09-21, feature completa no `demo-wiki`

A 3.4.0 desenhou o modelo; a 3.5.0 o **mediu** numa feature real de ponta a ponta (fluxo de
aprovação de compra em Laravel 13 / Filament 5: 18 `RQ`, 28 premissas, 79 CTs, 182 testes,
14 commits, 48 despachos, ~4,6 M tokens de sub-agente, quality gate ciclo 1 devolvido
`REPROVADO → especificação` com 8 achados). O que a medição **confirmou** vira regra dura nesta
versão; o que ela **desmentiu** está corrigido nas seções indicadas, cada uma com o caso.

| Confirmado | Evidência |
|---|---|
| **Cegueira vale mais que modelo.** O juiz cego acha o que a sessão não acha, mesmo com a sessão num modelo mais forte | duas rodadas adversariais (`opus`, só `00` + `04`) acharam 5 implementações erradas sobre 60 CTs derivados por outro `opus`, e uma regra que eram duas; o 6.5 cego produziu 14 achados que mudaram código, `00` (P-24, P-25) e `04` (CT-76..79) |
| **O juiz cego acusa a própria sessão.** O step 8 pegou duas alegações falsas da `## Verificação Final` — *"88 citações ok"* sem comando por trás e *"sem PCOV / mutate não instalado"* sem `php -m` — que nenhum gate anterior tinha como ver | QA-03 e QA-04 do ciclo 1; as duas eram do orquestrador, não do código |
| **O juiz cego faz a pergunta de requisito que ninguém fez** | gestor que acumula `diretor` assinava as duas etapas sozinho; quem já decidiu perdia a solicitação de vista e o link do e-mail virava 404 — QA-01 e QA-02, nascidos só no step 8 |
| **6.5 antes do 7.** Os 14 achados do 6.5 deslocaram citações, criaram IDs de CT e mudaram frases de ADR | se o 7 tivesse rodado antes, teria sido refeito inteiro |
| **Contrato de executor com classificação a/b/c** (teste errado / implementação divergente / flake) e *"nunca tocar `app/`"* | 5 lotes de teste; todo vermelho que sobrou era defeito real (CT-58: ação sem registro → 500; CT-59: justificativa sem teto) |
| **Auditoria do retorno é obrigatória, sobretudo com `haiku`** | 3 de 8 retornos `haiku` tinham defeito (escape de FQCN no grep → *"sem ocorrências"*; `preload()` *"não encontrado"* e existia; *"nada cresce com N"* medido com paginação de 10). Todos pegos por amostragem |
| **Quadro de despacho mede custo** | 48 linhas em `## Despachos`; a maior parte do custo foi `sonnet` construindo; o `Explore` sozinho custou 139 k tokens |

O que ela **desmentiu**, e onde está a correção: `pest --mutate` dá 100 % falso no Windows
(seção *Execução de Testes com Pest 5*); `haiku` em lote misto faz um item e reporta três
(*Rotas*); o `04` derivado antes dos cortes do Ponytail fica com CT órfão (*step 6*); a
`## Superfície Livewire` envelhece durante a implementação (*step 6.5*); número na
`## Verificação Final` sem o comando que o gerou é alegação (*Arquivo 03* e *Auditoria do
retorno*); CT que passa dos dois lados do `git stash` pode ser a pilha, não o CT (*step 6.5*).

### Dois eixos de roteamento, não um

| Eixo | Pergunta | Decide |
|---|---|---|
| **Complexidade** | quanto raciocínio a tarefa exige? | o **modelo** (`haiku` → `sonnet` → `opus`) |
| **Cegueira** | o que o executor **não pode ter visto** para o resultado valer como prova? | o **contexto** que o sub-agente recebe — e, por consequência, que a tarefa **não pode** rodar em linha |

O segundo eixo é o que esta esteira tem de específico. A coletânea inteira existe para quebrar a
**cegueira correlacionada**: o mesmo agente lê o requisito, escreve o plano, o teste, o código e o
veredito, e erra coerentemente. Sub-agente é o instrumento mais barato contra isso — ele nasce
**sem** o contexto da sessão, então *"por quem não implementou"* (step 6.5), *"por quem não
derivou"* (revisão adversarial da `feature-test-design`) e *"por quem não escreveu a wiki"*
(step 8) deixam de ser intenção e viram **construção**. Tarefa cuja validade depende de cegueira
**nunca** roda em linha, mesmo que caiba em dois passos.

### Rotas

| Rota | Modelo | Uso nesta esteira | Ferramentas |
|---|---|---|---|
| **mecânico** | `haiku` | grep em lote com a tabela pronta (Superfície Livewire, classe irmã, factories, rotas, policies, config), conferência de citação `arquivo:símbolo:linha`, `diff` de IDs de CT, espelho `01` → `03`, contagens por `grep -c`, `search-docs` uma pergunta por consulta | leitura + Bash |
| **construtor** | `sonnet` | gerar artefato a partir de template e insumo: rascunho do `01`/`02` a partir do pacote de pesquisa, código de **um** passo do PRD guiado pelo plano e pelo `04`, arquivo de teste a partir do Gherkin | tudo |
| **analista** | `opus` | julgamento com contexto: revisão profunda do plano (step 5), ADR, derivação do `04` pela `feature-test-design`, os 4 gates de candidato a rule, classificação de achado | leitura + Bash |
| **revisor-diff** | `opus` | step 6.5, passe de eixos sobre o diff. **Cego ao PRD** | leitura + Bash; **sem** Edit/Write |
| **adversário-ct** | `opus` | revisão adversarial da `feature-test-design`. Recebe **só** `00` + `04`/`05` | leitura; **sem** Edit/Write |
| **qa-gate** | `opus` | step 8: roda a `feature-quality-gate` inteira e devolve o `06` **como texto**. Não grava nada | leitura + Bash + MCP herdado (Boost, Playwright); **sem** Edit/Write |
| **executor-ct** | `sonnet` | escrever e rodar os testes Pest de **backend** a partir do Gherkin do `04`, sob o [contrato do construtor de testes](#contrato-do-construtor-de-testes-executor-ct): classifica cada vermelho em a/b/c e **nunca toca `app/`** | tudo, sob o contrato |
| **executor-ctb** | `sonnet` | escrever e rodar os CT-B em loop, sob o [contrato dos CT-B](#ciclo-de-escrita-e-auditoria-dos-ct-b-loop--sub-agente) | tudo, sob o contrato |
| **sessão principal** | o da sessão | captura **verbatim** do requisito, decomposição em `RQ`, perguntas ao usuário, decisão de roteamento de achado, veredito final, auditoria de todo retorno | — |

**Tier é o conceito portável; o alias é a implementação Claude.** `haiku` = **econômico**,
`sonnet` = **intermediário**, `opus` = **topo**. Em outro provedor o projeto mapeia os três tiers
para os modelos que tiver (um "mini", um padrão, um de raciocínio) e o resto da esteira fica
intacto; a coluna *Modelo* de `## Despachos` registra sempre o modelo **efetivamente** usado.

**Regras da rota `mecânico`, medidas em 2026-09-21:**

- **Um item por despacho** — um grep em lote com a tabela pronta, uma conversão, um espelho. Lote
  misto (converter + mover + preencher tabela) vai para `construtor`: o `haiku` fez 1 de 5 itens e
  reportou 3 como feitos, com um `git diff --stat` de arquivo que era untracked
- **FQCN em grep vai com `grep -F`** — o escape das barras produziu um *"sem ocorrências"* falso
- **`Explore` (built-in) só para feature grande.** Para o resto, `mecânico` com **trechos**, não
  arquivos: o `Explore` custou 139 k tokens onde cada `haiku` custou 40–60 k

Como obter as rotas, em ordem de preferência:

1. **Agentes do projeto** em `.claude/agents/` — se o projeto já tem `mecanico`/`construtor`/
   `analista` (ou equivalentes), usá-los pelo `subagent_type`
2. **Agentes da esteira** — cada skill é dona do seu, na pasta `agents/` dela, para o Boost
   instalá-lo junto com a skill: `feature-wiki/agents/fw-revisor-diff.md`, `fw-executor-ct.md` e `fw-executor-ctb.md`,
   `feature-test-design/agents/fw-adversario-ct.md`, `feature-quality-gate/agents/fw-qa-gate.md`.
   O Claude Code **não lê `.ai/skills/*/agents/`** — os arquivos precisam ser copiados uma vez para
   `.claude/agents/`, e de novo a cada atualização das skills:

   ```bash
   cp .ai/skills/*/agents/*.md .claude/agents/
   ```

   Sem a cópia, `subagent_type: "fw-…"` falha e a rota cai no item 3. São os cinco que carregam
   **cegueira e restrição de ferramenta**; as rotas genéricas não precisam de arquivo.
   **Os agentes só carregam de `.claude/agents/` do diretório onde a sessão foi aberta** — sessão
   aberta num diretório pai ou noutro repositório não os vê. Antes do primeiro despacho,
   `ls .claude/agents/fw-*.md`; se faltar, a rota cai no item 3 e a linha de `## Despachos`
   registra *"fallback `general-purpose`/{model} — agente `fw-…` indisponível"*. O fallback
   segurou uma feature inteira (2026-09-21): perde a **restrição de ferramenta**, não a cegueira,
   porque o contrato e o que o agente não recebe vão no prompt
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

| # | Agente / tarefa | Modelo | Depende de | Não recebe (cegueira) | Por quê este modelo |
|---|---|---|---|---|---|
| 1 | `mecanico` — greps da Superfície Livewire, tabela pronta | haiku | — | — | transformação direta, sem julgamento |
| 2 | `fw-revisor-diff` — eixos sobre `main...HEAD` | opus | último construtor | `01`, `03`, raciocínio da sessão | julgamento crítico; cegueira exigida |

O quadro **vai para o `03-progresso.md`**, seção `## Despachos`, uma linha por disparo com o
resultado e a auditoria do retorno. É o único registro de **qual modelo** fez o quê nesta feature
— e é o primeiro instrumento desta coletânea que mede **custo de operar**, não só eficácia.

### Rodam em linha, sem despacho

Tarefa de 1–2 passos; interação direta com o usuário; decisão que depende do contexto imediato da
conversa; edição cirúrgica em arquivo com forte interdependência; captura **verbatim** do requisito
(copiar não é tarefa, e passar a fonte por um resumo de sub-agente seria alterá-la). Nesses casos,
declarar **"Sem despacho — motivo"**, citando a exceção.

Exceção que **não** vale: *"a tarefa é pequena, então reviso eu mesmo."* Tamanho não compra
cegueira. O passe de eixos do 6.5, a revisão adversarial e o step 8 vão para sub-agente **sempre**.

### Auditoria do retorno

A sessão principal confere o retorno de **todo** lote antes de usá-lo:

- **presença** — o agente entregou tudo o que o quadro pedia, no formato fixo?
- **integridade** — nenhuma proibição do contrato violada: código de aplicação intacto, `00`
  intacto, `04` intacto (`git status` e `git diff --stat` antes e depois do lote)
- **amostragem** — 2–3 itens conferidos com `Read`/`grep` direto. O step 3 já manda *"confirmar os
  trechos críticos com `Read` direto"* para o `Explore`; a regra vale para todo retorno

**Sinais que reprovam o retorno antes da amostragem** (todos medidos em 2026-09-21):

- **número sem comando** — *"88 ok"*, *"32 convertidas"*: se o retorno não traz o comando e a
  saída literal, o número não existe. Um deles chegou à `## Verificação Final` e foi o juiz cego
  quem o derrubou
- **`git diff --stat` como prova de arquivo untracked** — a wiki nova não aparece no diff; retorno
  que a "prova" por ele não a conferiu
- **"não encontrado" / "não instalado" sem a prova negativa** — `ls vendor/…`, `php -m`, `grep -c`
  colados. Duas dessas afirmações (`Select::preload()`, `pest-plugin-mutate`) eram falsas
- **"sem ocorrências" em grep com FQCN** — conferir o escape (`grep -F`) antes de aceitar
- **conclusão de custo sob paginação** — *"nada cresce com N"* medido com página de 10 linhas não
  mede nada; pedir N acima da página

Retorno reprovado é refeito pelo mesmo agente com o achado, ou escalado de modelo — e o quadro
registra os dois casos: **auditoria reprovada é linha do quadro**, com o redespacho ao lado, não
apagão. **Interrupção no meio do lote** (limite de sessão, 429): o construtor pode ter deixado a
árvore meio-editada. Antes de retomar, `git status` e `git diff --stat`; retomar o **mesmo** agente
por `SendMessage` (o contexto dele sobrevive) em vez de despachar um novo sobre o estado parcial.

### Mapa de roteamento por step

| Step | Tarefa | Rota | Em paralelo com | Cegueira |
|---|---|---|---|---|
| 0, 2 | capturar requisito verbatim, decompor em `RQ`, nomear a feature | **sessão** | — | — |
| 3 | mapeamento amplo do código | `Explore` (built-in) | greps abaixo | — |
| 3 | greps de Superfície Livewire, classe irmã, factories, rotas, policies, config, `.env.example` — **tabelas prontas** | `mecânico` ×N | entre si e com o `Explore` | — |
| 3 | `search-docs` por stack, com a versão | `mecânico` | entre si | — |
| 4 | `00-requisito.md` | **sessão** | — | — |
| 4 | rascunho do `01` e do `02` a partir do pacote de pesquisa | `construtor` | — | — |
| 4 | `03` espelhando o `01` | `mecânico` | derivação do `04` | — |
| 4 | derivação do `04`/`05` — lê e segue `feature-test-design/SKILL.md` | `analista` | `03` | recebe `00` inteiro e, do `01`, **só** paths, rotas e `## Superfície de UI` (a própria skill delimita). Perguntas voltam como saída; a sessão as leva ao usuário |
| 4 | revisão adversarial do `04` | `adversário-ct` | — | recebe **só** `00` + `04`/`05` |
| 5 | levantar cada premissa do plano no código: existe? assinatura? linha? | `mecânico` | classe irmã | — |
| 5 | julgar as divergências e corrigir a wiki | `analista` ou **sessão** | — | — |
| 6 | `/ponytail:ponytail-review` | em linha (comando do plugin) | — | — |
| 6 | re-sincronização do `04` após corte que mude a `## Superfície de UI` | `mecânico` cruza índice × elementos cortados; **sessão** decide `@obsoleto` ou re-derivar | — | — |
| pré-6.5 | re-varrer a `## Superfície Livewire` do `02` sobre o **código final** | `mecânico` | — | — |
| impl. | cada passo do PRD, com o `04` como contrato | `construtor`, **sequencial** por padrão | só com arquivos disjuntos | não edita `00`, `04`, `05` |
| impl. | teste Pest a partir do Gherkin do `04` | `executor-ct` | passo seguinte, se disjunto | não lê `01`/`02`; lê `app/` só para nomes, nunca para o `Então`; **não toca `app/`** |
| **6.5** | `/code-review high {base}...HEAD` | o próprio comando (já roda em sub-agente isolado) | passe de eixos | — |
| **6.5** | passe de eixos sobre o diff | `revisor-diff` | `/code-review` | **não recebe** `01`, `03` nem o raciocínio da sessão; recebe diff, eixos, `## Superfície Livewire` do `02`, rules que casam o diff |
| 6.5 | roteamento do achado: Adendo → CT → correção | **sessão** decide; `construtor` corrige | — | — |
| 7 | citações, `diff` de IDs, checkbox sem evidência, docs pt/en × CHANGELOG | `mecânico` ×N | entre si | — |
| 7 | conformidade com rules — candidatos por glob, veredito por rule | `mecânico` levanta, `analista` julga | — | — |
| 7 | CT-B em loop | `executor-ctb` | — | contrato existente |
| **8** | `feature-quality-gate` inteira — lê e segue o `SKILL.md` dela | `qa-gate` | — | **não recebe** a conversa; só path da wiki, URL do app e `git diff --stat`. Devolve o `06` como texto; a **sessão** grava sem editar |
| 9 | 4 gates dos candidatos a rule | `analista` | — | — |
| 9 | decisão e `record-rule` | **sessão** + usuário | — | — |

> **Por que o step 8 é o maior ganho.** A `feature-quality-gate` pede *"por quem não escreveu a
> wiki"* e *"não corrige nada"*. Invocada em linha, ela roda na sessão que escreveu o `01` e
> implementou — a cegueira correlacionada que ela existe para quebrar. Em sub-agente **sem
> Edit/Write**, as duas exigências deixam de ser promessa: o juiz não viu a conversa e não
> consegue consertar.

## Fluxo de Execução

### 0. Capturar o Requisito — PRIMEIRO ATO

**Antes de descobrir branch, antes de nomear a feature, antes de qualquer pesquisa.**
O procedimento está em [Captura do Requisito](#captura-do-requisito-primeiro-ato--antes-de-qualquer-pesquisa),
dentro do step 3, porque é lá que ele convive com o resto da pesquisa — mas **a execução dele é aqui**.

> Por que a ordem importa: o step 2 manda nomear a feature, e nome de feature não se decide bem
> antes de ler o que foi pedido. Nomear primeiro é fixar uma interpretação antes de ter o requisito.

### 1. Descobrir Branch e Estrutura de Pasta

```bash
git rev-parse --abbrev-ref HEAD
```

Branch `ferro/501` → pasta base: `wikis/specs/ferro/501/`
Branch `feature/user-auth` → pasta base: `wikis/specs/feature/user-auth/`
Branch `fix/boleto-juros` → pasta base: `wikis/specs/fix/boleto-juros/`

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

**Caso especial — o requisito pressupõe algo que não existe no projeto.** É comum: o card fala em
"aplicar o desconto no pedido" e **não existe `Pedido`** — nem model, nem tabela. Isso não é
ambiguidade de redação, é **premissa de escopo**, e escolher sozinho entre *"entrego só o motor"* e
*"crio a entidade que falta"* é decidir o tamanho da entrega no lugar do usuário.

Procedimento:

1. `Grep`/`Glob` para **confirmar a ausência** antes de declará-la — pode existir com outro nome
2. Listar quais `RQ` dependem da entidade ausente
3. **Perguntar ao usuário**, com as duas opções e o custo de cada uma
4. Se o usuário não estiver disponível: seguir com a premissa **mais estreita** (entregar o que
   existe, não criar a entidade), registrá-la em `## Ambiguidades` e marcar as `RQ` dependentes
   como **fora desta entrega** em `## Cobertura do Requisito` — nunca como atendidas

Premissa de escopo tomada em silêncio é a forma mais cara de erro da wiki inteira: tudo fica
coerente, verde, e entrega outra coisa.

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

Grosso demais **esconde omissão** (uma `RQ` marcada ✅ com metade entregue); fino demais vira
ruído e a matriz fica ilegível. Na dúvida, **separe** — fundir depois é barato, e a omissão
escondida não aparece nunca.

**Quando não há usuário para responder a ambiguidade.** A skill manda perguntar antes de
implementar. Se não houver ninguém disponível, a ambiguidade **não vira silêncio**: registre em
`## Ambiguidades` no par obrigatório

```markdown
- **RQ-04** — o limite de usos é global ou por usuário?
  - **Assumido**: global (o card fala em "limite de quantas vezes pode ser usado", sem sujeito)
  - **Se negado**: RQ-04 muda de escopo; o passo 6 do PRD e os cenários CT-09..CT-11 são refeitos
```

e propague a premissa para `## Cobertura do Requisito`, marcando a `RQ` como **atendida sob
premissa**. Premissa sem "Se negado" é suposição disfarçada de decisão: ninguém sabe o custo de
descobrir que ela estava errada.

> **Por que isso existe**: sem o `00-requisito.md`, o único registro do que foi pedido é o PRD — que é a **interpretação** do agente. Se a interpretação estiver errada, ela contamina plano, testes, código e validação de forma coerente, e nada no ciclo detecta. Ver [Arquivo 00](#arquivo-00-requisito--fonte-da-verdade).

Antes de escrever qualquer documento:
- Usar `database-schema` se a feature envolve novas tabelas ou alterações
- **Usar `search-docs` para toda stack envolvida** — obrigatório antes de escrever o PRD (ver [Documentation API](#documentation-api-do-boost-search-docs) para cobertura e como consultar)
- Ler arquivos existentes relevantes com `Read` ou `Grep`
- Executar `php artisan model:show ModelName` para models relacionados
- Examinar padrões existentes com `Glob "**/[padrão]/**/*.php"`
- **Inspecionar APIs de terceiros** antes de escrever CTs — verificar vendor source ou docs oficiais para confirmar nomes de métodos, assinaturas e restrições de schema. E isso nunca basta sozinho: **toda** feature que cria página, widget ou componente preenche a [Superfície Livewire](#superfície-livewire-obrigatório-em-toda-feature-que-cria-página-widget-ou-componente)
- Para features médias/grandes: delegar o mapeamento amplo a um agent `Explore` e depois **confirmar os trechos críticos com `Read` direto** (linhas exatas, imports, assinaturas) — não confiar apenas no resumo do agent. No Claude Code, os greps prescritos nesta lista e na Superfície Livewire vão para `mecânico` (`haiku`) **em paralelo**, cada um devolvendo a tabela pronta — ver [Execução e Delegação](#execução-e-delegação-claude-code--roteamento-por-modelo-e-por-cegueira)
- **Validar dados fornecidos pelo usuário** (CSV, listas, IDs) contra o banco via `database-query` — detectar divergências de título/chave, escolher chave estável (ID) para mapeamentos e documentar as divergências no plano
- **Verificar existência de factories** (`Glob "database/factories/{Model}*"`) e states disponíveis antes de escrever CTs; se não houver factory, especificar `Model::create([...])` no Setup Global
- **Confirmar padrões internos citados** no plano com grep/read (ex: seeder-em-migration, guards de environment, `$casts` property vs `casts()`) — citar `arquivo:linha` de referência no plano
- **Verificar rotas existentes** — `Grep` em `routes/web.php` e `routes/api.php` para evitar conflito de endpoints e entender naming conventions
- **Verificar Policies/Gates** — `Glob "app/Policies/*.php"` e `Grep` por `Gate::define` para entender o padrão de autorização do projeto
- **Verificar config files** — `Read` em `config/*.php` relevantes à feature (services, logging, queue, auth)
- **Verificar composer.json** — `Read` em `composer.json` para pacotes instalados que poderiam ser reutilizados em vez de criar do zero
- **Verificar wikis existentes** — `Glob "wikis/specs/**/*.md"` para features relacionadas que já foram documentadas e podem ter decisões relevantes
- **Verificar git log do branch** — `git log --oneline -20` para contexto do que já foi feito no branch
- **Verificar scheduled tasks** — `Grep` em `app/Console/Kernel.php` ou `routes/console.php` se a feature envolve cron/scheduling
- **Verificar eventos/listeners** — `Glob "app/Events/*.php"` e `Glob "app/Listeners/*.php"` se a feature emite ou escuta eventos
- **Verificar observers** — `Glob "app/Observers/*.php"` para hooks de model existentes
- **Verificar middleware** — `Grep` em `app/Http/Middleware/` e em `bootstrap/app.php` (Laravel 11+) para middleware stack
- **Verificar variáveis de ambiente** — `Read` em `.env.example` para chaves existentes e padrão de naming

#### Superfície Livewire (OBRIGATÓRIO em toda feature que cria página, widget ou componente)

Tudo o que o **cliente** pode escrever ou chamar entre requests. Não é uma seção sobre pacotes —
é sobre a **fronteira que o navegador alcança**, e o pacote de terceiro é só uma das origens dela.

> **A condição desta seção já foi "quando a feature monta sobre pacote de terceiro", e essa redação
> custou dois defeitos** (caso real, 2026-09-17). A feature montava sobre o **framework**, não sobre
> um pacote; o agente leu a condição ao pé da letra, declarou *"nenhum pacote persiste entidade →
> não se aplica"* e a tabela nunca foi preenchida. Passaram: um método público do componente
> (**ação chamável por `$wire.`**, que devolvia coluna não exposta e produzia 500 com nome
> inexistente) e um array público de filtro consumido sem validação (`$rotulos[$valor]` e
> `Carbon::parse($valor)` → dois 500). A condição certa é a superfície, não a origem dela.

Produzir a tabela `## Superfície Livewire` no `02-decisoes-arquiteturais.md`, uma linha por ponto
que o **cliente** alcança — de qualquer origem:

| Origem | O que inventariar | Por que |
|---|---|---|
| **o código do projeto** | todo `public function` de Page, Widget ou componente Livewire; toda `public $` sem `#[Locked]` | método público de componente Livewire **é ação chamável pelo cliente**, e o retorno vai para o navegador; propriedade pública é escrita pelo cliente **entre requests** |
| **o framework** | os arrays de estado que o framework publica e o seu código consome — `$filters` (`HasFilters`), `$pageFilters` (`InteractsWithPageFilters`), `$tableFilters`, `$tableSearch`, `$tableSortColumn` | são **entrada de usuário não validada** que vira `where`, índice de array e parse de data. O framework os declara `public` |
| **o pacote de terceiro** | ações que recebem id/argumento do cliente, propriedades públicas e models que a feature persiste | ver a varredura abaixo |

Uma linha por ponto, com a fronteira e a evidência:

| Ponto de entrada (vendor) | Alcançável por | Fronteira aplicada pelo projeto | Evidência |
|---|---|---|---|
| `Widget::find($arguments['widget'])` | `$wire.mountAction('deleteWidget', {widget: <id>})` | global scope `whereHas('pai')` | `vendor/{pkg}/src/Pages/X.php:962` |
| `public ?int $currentDashboardId` | `$wire.set()` em qualquer request após o `mount()` | `#[Locked]` na subclasse do projeto | `vendor/{pkg}/src/Pages/X.php:71` |

Varredura mínima — os greps, com o resultado colado na tabela.

**No código que a feature escreve** (sempre):

```bash
grep -rn "public function " app/Filament/{Painel}/{Pages,Widgets}   # ação chamável por $wire.
grep -rn "public \$\|public ?" app/Filament app/Livewire | grep -v Locked
```

**No pacote de terceiro** (quando a feature monta sobre um):

```bash
grep -rn "::find(\|whereKey(\|findOrFail(" vendor/{vendor}/{pkg}/src        # busca por id cru
grep -rn "public \$\|public ?" vendor/{vendor}/{pkg}/src | grep -v Locked   # prop que o cliente escreve
grep -rn '\$arguments\[\|\$data\[' vendor/{vendor}/{pkg}/src              # argumento do cliente na ação
grep -rn "extends Model" vendor/{vendor}/{pkg}/src/Models                    # models a escopar
```

**Regra dura**: **todo model do pacote que a feature persiste aparece na tabela com a própria
fronteira.** *"É filho do outro, logo está protegido"* só vale com a evidência de que **nenhum**
ponto de entrada o alcança direto — e essa evidência é um `grep`, não uma dedução.

**Segunda regra dura, do caso de 2026-09-17**: **todo valor que entra por um desses pontos e vira
índice de array, argumento de `parse`, nome de coluna ou operador é um cenário de domínio
inválido.** Público sem validação não é "detalhe de framework": `$rotulos[$situacao]` sem `??` e
`Carbon::parse($filtro)` sem guarda são 500 que nenhum teste de caminho feliz vê, porque a tela
sanitiza o valor **na página** e os widgets o recebem **direto**.

A tabela é **entrada obrigatória da `feature-test-design`** (step 4): cada linha vira gatilho do
checklist de taxonomia, e a linha sem cenário correspondente é lacuna declarada, não silêncio.

> **Por que este bloco existe** (caso real, 2026-09-15): uma feature com wiki completa — 29 CTs,
> revisão adversarial, 11 regras, 38 mutantes — entregou **quatro defeitos**, dois deles de escrita
> cross-tenant. Os quatro moravam nesta superfície: ação do pacote buscando o filho por id cru do
> cliente, propriedade pública Livewire sem `#[Locked]`, escopo que falhava aberto no caso nulo e
> 403 do vendor sem saída. A wiki citava o vendor corretamente para justificar desenho e **nunca o
> inventariou como superfície de ataque**. O texto que liberou o pior deles foi uma dedução sem
> grep: *"o filho não precisa de escopo, é sempre alcançado pelo pai"*.

#### Verificação do stack de testes (define se haverá CT-B)

- **Versão do Pest** — `Grep "pestphp/pest" composer.json`. Pest 5 habilita `--tia`, `--agent` e sharding por tempo; Pest 4 tem browser plugin mas não TIA
- **Browser plugin instalado?** — `Grep "pest-plugin-browser" composer.json` e `Glob "tests/Browser/**"`
  - Se a feature tem UI e o plugin **não** está instalado: incluir a instalação como passo explícito no PRD (`## Dependências`), não assumir que existe
  - Se `tests/Browser/` já existe: ler 1-2 testes para herdar o padrão do projeto (helper de login, traits no `Pest.php`, seletores usados)
- **Playwright instalado?** — `Grep "playwright" package.json`; browsers baixados via `npx playwright install`
- **Como o app é servido em teste** — o `pest-plugin-browser` **sobe o próprio servidor** (HTTP in-process, porta aleatória): não há Herd, `php artisan serve`, Sail nem `APP_URL` a configurar. O que confirmar é outra coisa: se o projeto roda `npm run build` antes da suíte de browser (pré-requisito duro — sem o manifest do Vite toda tela responde `ViteException`) e qual o teto em `pest()->browser()->timeout()`
- **Traits globais** — `Read tests/Pest.php` para ver se `RefreshDatabase` está aplicado globalmente e se há `pest()->browser()` ou `pest()->tia()` configurado

#### Documentation API do Boost (`search-docs`)

O Boost expõe a tool MCP **`search-docs`**, que consulta a Documentation API hospedada da Laravel — 17.000+ trechos com busca semântica por embeddings, **filtrada pelos pacotes que o projeto realmente tem instalados**. É a primeira fonte a consultar, antes de vendor source e antes de doc na web.

**Cobertura oficial da Documentation API** (versões suportadas):

| Stack | Versões cobertas |
|---|---|
| Laravel Framework | 10.x, 11.x, 12.x, **13.x** |
| Filament | 2.x, 3.x, 4.x, **5.x** |
| Livewire | 1.x, 2.x, 3.x, **4.x** |
| Inertia | 1.x, 2.x |
| Flux UI | 2.x Free, 2.x Pro |
| Nova | 4.x, 5.x |
| Pest | 3.x, **4.x** |
| Tailwind CSS | 3.x, 4.x |

**Quando é obrigatório consultar** — antes de escrever qualquer um destes trechos do PRD:

| O que vai escrever | Consultar `search-docs` sobre |
|---|---|
| Rotas, middleware, policies, validação | Laravel Framework (versão do projeto) |
| Componente de UI, tabela, form, modal | Filament / Livewire / Flux (versão do projeto) |
| Jobs, queues, batching, scheduling | Laravel Framework — queues |
| CTs do arquivo `04` | Pest — expectations, mocking, datasets |
| CT-B do arquivo `05` | Livewire/Filament (comportamento assíncrono) + Pest browser |
| Broadcasting, eventos, Reverb/Echo | Laravel Framework |

**Como consultar bem**:

1. **Uma pergunta por consulta**, específica: *"Filament 5 table bulk action confirmation modal"* vence *"Filament tabelas"*
2. **Citar a versão** do pacote na consulta — a busca é filtrada pelos pacotes instalados, mas a versão desambigua o trecho retornado
3. **Confirmar no código antes de escrever no PRD**: a doc diz o que a API oferece; o `Grep`/`Read` diz o que o **seu** projeto faz. Divergência entre os dois vai para `02-decisoes-arquiteturais.md`
4. **Citar a origem no PRD** quando a decisão veio da doc: *"conforme doc do Filament 5 (search-docs)"* — dá rastreabilidade e evita re-pesquisa na próxima wiki

**Lacunas conhecidas — o que `search-docs` NÃO cobre**:

| Stack | Situação | Fallback |
|---|---|---|
| **Pest 5** | API cobre até **4.x** | doc oficial em `pestphp.com/docs` — `--tia`, `--agent`, sharding e os matchers novos **não** estão no `search-docs` |
| **Playwright / `pest-plugin-browser`** | não coberto | `pestphp.com/docs/browser-testing` + `playwright.dev` |
| Pacotes de terceiros | não coberto | vendor source (`Read vendor/{vendor}/{pkg}/src/...`) — já obrigatório no step 3 |
| Código da sua aplicação | não coberto por design | `Grep`/`Read` + `.ai/rules/` do projeto |

> **Anti-padrão**: escrever no PRD assinatura de método, nome de opção de config ou comportamento de componente **sem** confirmar em `search-docs` (ou, nas lacunas acima, na doc oficial). É a causa nº 1 de plano que não sobrevive à implementação.
>
> **Anti-padrão**: usar `search-docs` para descobrir comportamento do **seu** código. Ele documenta o ecossistema; o seu código é `Grep`, e as suas convenções são `.ai/rules/`.

### 4. Criar os Arquivos

Criar os **5 arquivos obrigatórios** + extras se necessário.

**Ordem de criação**:
1. **`00-requisito.md`** — requisito bruto + decomposição em `RQ-##`; é a linha de base de tudo
2. **`01-plano-acao.md`** — PRD deriva do `00`; cada passo deve citar quais `RQ` atende
3. **`02-decisoes-arquiteturais.md`** — ADRs justificam escolhas do PRD
4. **`04-casos-de-teste.md`** e, condicionalmente, **`05-casos-de-teste-browser.md`** —
   **invocar a skill `feature-test-design`**. Ela deriva os cenários do **`00-requisito.md`**;
   o PRD entra só para paths, rotas e a tabela `## Superfície de UI`
5. **`03-progresso.md`** — espelha os passos do PRD (por isso é o último; se houver CT-B, o progresso também os lista)

> **Não escrever o `04` inline.** O caso de teste derivado do plano confirma o plano — é a
> mesma cegueira correlacionada que o `00-requisito.md` existe para quebrar. Ver
> [Arquivos 04 e 05](#arquivos-04-e-05-casos-de-teste--delegados-à-feature-test-design).
> Se a skill `feature-test-design` não estiver instalada, **declarar a degradação no
> `03-progresso.md`** antes de escrever o `04` à mão.

> **Rastreabilidade obrigatória**: todo passo do PRD e todo CT/CT-B referencia o `RQ` de origem. É isso que permite ao `feature-quality-gate` montar a Matriz de Rastreabilidade e detectar cláusula sem plano, sem teste ou sem código.

**Wiki já existente**: se `wikis/specs/{branch}/{feature}/` já existe:
- **Perguntar ao usuário** se deseja sobrescrever, incrementar (v2) ou retomar
- Se retomar: ler `03-progresso.md` para ver o que já foi feito e continuar de onde parou
- Se sobrescrever: backup manual pelo usuário antes de criar a nova (a skill não arquiva automaticamente)
- **Requisito novo no meio da implementação** (mesma branch, mesmo PR): não é wiki nova nem "incrementar" — é **Adendo** ao `00`. Ver [Adendo ao requisito](#adendo-ao-requisito--quando-o-pedido-cresce-durante-a-implementação). Sem isso o pedido vai direto para o código e os testes dele nascem do código

### 5. Revisão Profunda Pós-Escrita (OBRIGATÓRIO)

Após escrever os 4 arquivos, **re-validar cada premissa do plano contra o código real** antes de apresentar ao usuário:

- Reler os pontos exatos citados no plano: imports dos arquivos a editar, assinaturas de métodos, relações de models, padrão das migrations-referência, factories/states usados nos CTs
- **Corrigir a wiki imediatamente** quando a revisão contradisser o plano (ex: plano diz "adicionar import X" → import já existe; plano cita guard genérico → padrão real é `! app()->environment('testing')`)
- **Registrar cada correção** em `03-progresso.md` → `## Auditoria Pré-Implementação` → *Revisão profunda*. Correção aplicada e não registrada some: a próxima pessoa refaz a verificação e o histórico não mostra que a premissa original estava errada
- Só então avançar para o step 6 (Auditoria da Wiki)

**Padrão de despacho (Claude Code)** — o mesmo do `PM_Arquiteto`: **levantar com `mecânico`,
julgar com `analista`**. Um `haiku` por bloco de premissas devolve a tabela *premissa do plano ×
o que o código diz* (`arquivo:símbolo:linha` conferido por grep, assinatura real, import
existente); a sessão — ou um `analista`, quando a divergência exige decisão — julga cada linha e
corrige a wiki. Levantar é volume; julgar é raciocínio. Misturar os dois num só agente caro é o
desperdício que o roteamento existe para evitar.

> Exemplo real (feature/implementar-carga-horaria): a revisão pós-escrita detectou que o import `MbaTrack` já existia no arquivo a editar e confirmou o padrão exato do guard de environment nas migrations com seeder — ambos corrigidos na wiki antes da implementação.

#### Varredura da classe irmã (OBRIGATÓRIA para toda classe nova)

A revisão acima confere o que o plano **afirma**. Este item confere o que o plano **não sabe que
existe**: as listas paralelas que o projeto mantém à mão e que nenhuma rule enumera por completo.

Procedimento, uma linha por classe nova:

```bash
# Onde uma classe IRMÃ já existente é citada? É onde a nova também precisa aparecer.
grep -rn "App\\\\Filament\\\\Admin\\\\Pages\\\\Dashboard" --include=*.php app config database tests
```

Escolher como irmã a classe **mais parecida em papel** (outra Page de dashboard, outro Resource do
mesmo painel, outro Widget da mesma família) e conferir **todos** os lugares onde ela aparece:
`config/*.php`, seeders, listas de exclusão, inventários de teste, `->pages()`/`->widgets()` dos
providers, matrizes de permissão. Cada ocorrência é uma pergunta: *a classe nova entra aqui também?*

> **Caso real, 2026-09-17.** A feature criou uma Page de dashboard nova. O agente leu a rule do
> projeto sobre tela de entrada, entendeu a decisão e atualizou **a lista do teste**
> (`$telasDeEntrada`). Existia uma **segunda** lista, em `config/filament-shield.php`
> (`pages.exclude`), com as outras quatro telas de entrada — e a informação de que ela existia
> estava só num **comentário dentro do próprio config**. Sem a linha, o Shield geraria uma
> permission `View:{Page}` que apareceria como checkbox na tela de papéis e **não mudaria nada
> quando desmarcada** — o "checkbox que mente". O `grep` pelo FQCN da irmã devolvia as duas listas
> em segundos; nenhum outro gate da wiki olha para listas paralelas.

Registrar o resultado em `03-progresso.md` → `## Auditoria Pré-Implementação`, com a irmã escolhida
e as ocorrências encontradas. "Nenhuma ocorrência além das previstas" é resposta válida e precisa
estar escrita.

### 6. Auditoria da Wiki com Ponytail-review (OBRIGATÓRIO)

Após a revisão profunda (step 5), **invocar automaticamente** `/ponytail:ponytail-review` para auditar a wiki criada. Este step é função direta da skill — o agente NÃO deve esperar o usuário pedir.

**Por que auditar a wiki**: O plano de ação pode conter over-engineering — passos desnecessários, abstrações prematuras, complexidade que não agrega valor. A auditoria com Ponytail-review identifica esses pontos **antes** da implementação começar, economizando tempo de desenvolvimento.

**Como executar**:
1. Invocar `/ponytail:ponytail-review` apontando para os arquivos da wiki criada em `wikis/specs/{branch}/{feature}/`
2. Analisar cada sugestão de corte/simplificação retornada
3. **Aplicar as sugestões relevantes** diretamente nos arquivos da wiki:
   - Passos desnecessários → remover do `01-plano-acao.md` e `03-progresso.md`
   - Abstrações prematuras → simplificar ou marcar como YAGNI
   - Complexidade excessiva → quebrar em passos menores ou simplificar
   - Over-engineering em CTs → simplificar setup, reduzir mocks desnecessários
4. **Re-executar** `/ponytail:ponytail-review` se houver mudanças significativas (>3 arquivos alterados)
5. Só então apresentar ao usuário para aprovação / iniciar implementação

**Ordem com o step 4 (medido em 2026-09-21)**: o `04` derivado **antes** dos cortes do Ponytail
herda os elementos cortados. Na feature de referência o Ponytail removeu um filtro de tabela e o
CT-42 ficou órfão — só apareceu no `diff` de IDs do step 7. Duas regras:

1. **Preferir** rodar este step sobre `01`/`02` **antes** de invocar a `feature-test-design`: a
   derivação recebe do `01` só paths, rotas e `## Superfície de UI` — exatamente o que os cortes
   mudam
2. Se o `04` já existe quando um corte muda rota, ação, filtro ou coluna da `## Superfície de UI`,
   **re-sincronizar o `04` no mesmo passo**: um `mecânico` cruza o `## Índice de Cenários` com os
   elementos cortados; cada CT atingido vira `@obsoleto` com o motivo (e sai do índice com `~~`)
   ou é re-derivado pela `feature-test-design`. Registrar em `### Auditoria Ponytail (step 6)` do
   `03`. CT órfão descoberto só no step 7 é sinal de que este item foi pulado

> **Importante**: Esta auditoria revisa o **plano** (a wiki), não o código implementado. A auditoria do código implementado acontece no step 7 (Pós-Implementação) e nos templates de Verificação Final.
>
> **Comando correto**: `/ponytail:ponytail-review` (com namespace `ponytail:`). NUNCA usar `/ponytail-review` sem o namespace — o comando não será encontrado.

### 6.5. Revisão de Código do Diff (OBRIGATÓRIO, logo após os testes passarem e antes da reconciliação)

**Quando**: a suíte da feature está verde e o último passo do PRD foi entregue — **antes** do
step 7. A ordem é **6.5 → 7 → 8 → PR**, e o motivo é mecânico: cada achado confirmado aqui vira
Adendo no `00`, CT no `04` e correção no código, e isso desloca linha citada, cria ID de CT e muda
frase de doc e de ADR. Reconciliar (7) antes de revisar é reconciliar duas vezes. E a dimensão L do
step 8 repete a reconciliação por quem não a fez, então ela precisa ser a **última** coisa antes
dele. Até a 3.3.0 este step era o 7.5 e rodava depois do 7; mudou só a posição — o gate é o mesmo.

**Por quem não implementou** — sobre o **diff completo** da feature contra a base
(`git diff {base}...HEAD` mais o não-commitado), não sobre os arquivos que o agente lembra de ter
tocado.

**Pré-requisitos do lote** (os dois medidos em 2026-09-21):

- **A sessão roda no repositório do projeto.** O `/code-review` só alcança o diretório onde a
  sessão foi aberta; sessão aberta noutro repositório não consegue apontá-lo para o projeto. Nesse
  caso o passe 1 é substituído por um `analista` (`opus`) **cego**, com o mesmo alvo
  (`{base}...HEAD`) e sem os eixos, e a linha de `## Despachos` declara *"passe genérico por
  sub-agente — `/code-review` fora de alcance"*. Vale menos: o comando nativo tem heurísticas
  próprias que o substituto não tem
- **A `## Superfície Livewire` do `02` foi re-varrida sobre o código final.** A tabela nasce no
  planejamento e **envelhece** durante a implementação: na feature de referência ela negava
  superfície de vendor, e as duas Pages herdavam uma trait com quatro métodos `$wire.` que recebem
  índice de array. Um `mecânico` refaz os quatro greps sobre o diff final **antes** de despachar o
  revisor; a tabela atualizada é o que ele recebe — a antiga é insumo do plano, não prova

**Por que existe, e por que nenhum outro step cobre**: o step 6 audita o **plano**
(`ponytail-review`, over-engineering); o step 8 confronta **requisito × app rodando** (omissão
silenciosa). Nenhum dos dois lê o diff atrás de **defeito de correção**. Entre um e outro passa
uma classe inteira: escrita cross-tenant, propriedade pública que o cliente escreve, gate que
falha aberto no caso nulo, estado de erro sem saída. Nada disso é visível para quem pergunta *"o
requisito foi atendido?"* nem para quem pergunta *"o plano é simples demais?"* — e tudo isso passa
com a suíte verde, porque os testes foram derivados da mesma leitura que produziu o defeito.

#### Quando rodado no Claude Code — dois passes, no mesmo lote

| Passe | Ferramenta | O que pega | Como |
|---|---|---|---|
| **1. Genérico** | `/code-review high {base}...HEAD` | defeito de correção que qualquer revisor competente vê: nulo não tratado, condição invertida, exceção engolida, N+1, uso errado de API | o comando já roda em sub-agente isolado — a cegueira ao contexto da sessão vem de graça |
| **2. Eixos** | sub-agente `fw-revisor-diff` (`opus`, sem Edit/Write) | os eixos da tabela abaixo — são de Laravel, Livewire e multi-tenant, e o passe genérico **não os conhece** | recebe: o diff, a tabela de eixos, a `## Superfície Livewire` do `02` e as rules cujos globs casam o diff. **Não recebe**: `01`, `03`, nem o raciocínio da sessão |

Os dois disparam **juntos** — são independentes. Regras dos passes:

- **Alvo explícito, sempre.** Sem alvo, o `/code-review` compara com o merge-base do **upstream**
  da branch: numa branch já pushada o "diff atual" vira só o não-commitado, e a revisão sai vazia
  parecendo limpa. Escrever `main...HEAD` (ou a base real do PR)
- **Nível `high`.** `low`/`medium` devolvem só achado de alta confiança; aqui o achado incerto é
  bem-vindo, porque o roteamento **obriga a rejeitar com motivo** — e relatório sem rejeição
  parece que só procurou onde achou
- **`--fix` é proibido.** Quem julga não conserta (princípio 2 da `feature-quality-gate`), e o
  roteamento exige Adendo → CT → correção **nessa ordem**; o `--fix` pula os dois primeiros e
  entrega correção sem oráculo
- **O comando não aceita foco em texto livre** — o que vier depois do nível é lido como alvo. Por
  isso os eixos vão num sub-agente próprio, não num argumento do `/code-review`
- **Re-revisar uma única vez**, e só se alguma correção tocou eixo de fronteira (dado, superfície,
  estado de erro) — correção é código novo do mesmo agente. Teto de 2 rodadas; se a segunda ainda
  trouxer achado estrutural, o problema é o plano: registrar e escalar

**Checkpoint opcional durante a implementação**: depois de um passo do PRD que cria query com
discriminante, `public function`/`public $` em componente, ou estado de erro novo, rodar
`/code-review medium` sobre o diff não-commitado. Achado aqui custa uma linha; o mesmo achado no
6.5 custa Adendo, CT, correção e re-teste. O checkpoint **não substitui** o passe completo — ele
não enxerga simetria de guarda entre superfícies que ainda não existem.

**Fora do Claude Code**: o passe 2 é o gate inteiro. Host com sub-agente: `revisor-diff` com o
mesmo contrato. Host sem sub-agente: em linha, com a degradação declarada no `03` —
*"6.5 em linha — mesma sessão que implementou"* — porque o resultado vale menos, e o leitor do PR
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

> Os quatro eixos em negrito vieram do caso de 2026-09-17 — foram exatamente os achados que os
> gates anteriores não tinham como ver, e cada um deles já era um 500 ou um checkbox que mente.

**Roteamento do achado** — igual ao do quality gate, e nesta ordem:

1. Achado confirmado vira **Adendo numerado no `00`** (`## Adendo N`, premissas `Pnn`) — porque ele
   muda o que a feature promete, não só o código
2. Vira **CT novo no `04`** (regra, cenário Gherkin e os mutantes que ele mata), **antes** da
   correção
3. Só então a correção
4. Achado **rejeitado** fica registrado com o motivo. Relatório sem rejeição parece que só procurou
   onde achou

**Falsificabilidade da correção (duro)**: antes de fechar, provar que o CT novo **falha sem** a
correção — `git stash push -- app/`, rodar o CT, `git stash pop`. CT que passa dos dois lados não é
oráculo, é decoração — **salvo quando a pilha de teste não consegue exibir o defeito**. Três saídas,
e a terceira precisa estar escrita:

| Sem a correção, o CT… | Veredito | Registro na `## Verificação Final` |
|---|---|---|
| falha | oráculo válido | `n de m falham sem o fix` |
| passa, e a pilha exibiria o defeito | decoração — reescrever o CT | — |
| passa porque a pilha **não exibe** o defeito (SQLite ignora `VARCHAR(255)`; `RESTRICT` sem `PRAGMA foreign_keys`; cascata só em memória) | **"não falsificável nesta pilha — guarda mantida, dívida declarada"** | linha com o motivo e o que exibiria (MySQL/Postgres em CI) |

Na feature de referência 3 de 5 CTs novos caíram na terceira linha. Lida como regra absoluta, a
frase *"decoração"* mandaria apagar guardas corretas.

> Caso real (2026-09-15): a revisão de código do diff de uma feature já "verde e concluída" achou
> quatro defeitos — dois de escrita cross-tenant, um de fail-open e um beco sem saída na raiz do
> painel. Os steps 5, 6 e 7 tinham rodado; o 8 não. Nenhum dos quatro seria pego por nenhum deles,
> porque todos os quatro estavam **corretos em relação ao plano**.

### 7. Pós-Implementação e Reconciliação (OBRIGATÓRIO, antes do PR)

Após a implementação, os testes passarem e o **step 6.5 ter rodado** — e **antes de abrir o PR e
antes de escrever "concluída" no `03`**. A ordem é **6.5 → 7 → 8 → PR**: o diff que se reconcilia
aqui é o diff **pós-revisão**. Abrir o PR antes foi o que produziu, num caso
real, uma feature "concluída" com quality gate "para o passo seguinte", quatro quebras reais e 27
afirmações defasadas na wiki e nas docs.

**Fontes a reconciliar** — a lista é fechada; o que não está nela não é reconciliado por acidente:
`01`, `02`, `04`, `05`, `03`, docs de usuário (pt **e** en), `CHANGELOG.md`, `README`, e as rules
de `.ai/rules/` cujos globs casam com o diff.

1. **Checkbox só fecha com evidência inline.** Formato `- [x] {item} — {evidência}, {data}`
   (ex.: `— 677/677 verdes, 2026-09-05`). Item sem evidência continua `[ ]`. É proibido fechar a
   `## Verificação Final` por substituição em lote: cada linha fecha quando o comando dela roda.
   Conferência: `grep -n '^- \[x\]' 03-progresso.md | grep -v ' — '` tem de voltar vazio
2. **Desvio corrige a fonte; o `03` só aponta.** Cada item de "Desvios do Plano" exige a edição
   correspondente no `01`, `02`, `04` ou `05` de origem, marcada inline com
   `*(alterado em {data}: {motivo curto})*`. Registrar o desvio só no `03` deixa o PRD e a ADR
   afirmando o que o código não faz — e é o PRD que a próxima pessoa lê. Critério de saída:
   **nenhuma afirmação do `01`/`02` contradiz o código**
3. **Reverificar toda citação `arquivo:símbolo:linha`** com o grep de
   [Citações de código](#citações-de-código--arquivosímbololinha). Pint e imports novos deslocam
   linhas; a conferência é mecânica e o resultado (`— 14/14 ok`) vai para a Verificação Final
4. **Sincronizar `04`/`05` com o teste real, nos dois sentidos — por comando, não por leitura.**
   Todo `[CT-nn]`/`[CT-Bnn]` do arquivo de teste existe no `04`/`05`; todo CT do índice aponta um
   teste existente ou declara "fundido em CT-nn"; linha de dataset nova no teste existe como
   Exemplo no Gherkin. Cenário que nasceu durante a implementação **nasce no `04` primeiro**
   (Proibição 11 da `feature-test-design`).

   ```bash
   diff <(grep -oh 'CT-B\?[0-9]\+' wikis/specs/{branch}/{feature}/0[45]-*.md | sort -u) \
        <(grep -oh 'CT-B\?[0-9]\+' tests/**/*{Feature}*.php | sort -u)
   ```

   **Saída vazia é o critério**; linha com `<` é CT sem teste, linha com `>` é teste sem CT. A
   saída vai colada na `## Verificação Final` — sem ela o checkbox não fecha. (Caso real: o `04`
   declarava um CT de ciclo liga/desliga com dois mutantes exclusivos e **nenhum teste o
   implementava**; o checkbox *"testes conforme 04/05"* fechou assim mesmo, e a lacuna só apareceu
   numa revisão de código posterior. O `diff` acima leva segundos e a teria pego no dia.)
5. **Todo número da wiki é derivado por comando — e procurado na wiki inteira quando muda.**
   Número escrito à mão envelhece **dentro do próprio ciclo**: contagem de CTs, de regras, de
   mutantes, de permissions, de linhas de uma varredura, total de cenários no cabeçalho. A regra
   vale para o `01`, o `02` e o `04`, não só para o `03` — e a conferência é `grep -c` contra o
   código, ao fim, nunca leitura.

   O ponto cego é a **duplicação**: quando um achado corrige um número, a correção vai para o
   arquivo que o achado citou e a **cópia do mesmo número em outro arquivo sobrevive**. Antes de
   fechar, procurar o valor antigo na wiki inteira:

   ```bash
   grep -rn "{valor antigo}" wikis/specs/{branch}/{feature}/
   ```

   > Medido numa mesma wiki, três vezes: nove citações `arquivo:linha` desatualizadas, uma
   > varredura colada na `## Superfície Livewire` que o código já contradizia, e uma contagem que
   > o quality gate acusou — corrigida no `01`, mantida na ADR do `02`, que repetia o mesmo
   > número. **A defasagem sobreviveu ao gate que existia para pegá-la**, porque o gate leu o
   > arquivo onde esperava a afirmação e conferiu ali.
6. **Conformidade com as rules do projeto.** Para cada rule em `.ai/rules/index.md` cujo glob
   casa com um arquivo do diff, uma linha na tabela `## Conformidade com Rules` do `03`:
   `rule → aplicada / n.a. / violada`, com evidência (`arquivo:símbolo:linha` ou nome do CT).
   Rule violada é blocker do PR. O step 3 manda **ler** as rules antes de planejar; este item
   confere se o **código** as cumpre — são coisas diferentes, e a segunda nunca era feita
   (medido: `group('kit')` em teste de browser, chave `KIT_*` fora do `phpunit.xml`, par de
   cenário exigido pela rule de auth ausente — três rules lidas no step 3, três violadas no código)
7. **Docs de usuário e CHANGELOG × comportamento × rastro.** Toda consequência que a wiki
   descreveu e depois mudou (ex.: "o log registra o painel `app`") é procurada nas docs pt/en, no
   CHANGELOG, no README e na ADR que a originou. Frase nova em doc de usuário **sem `RQ` nem ADR
   de origem** é crescimento sem rastro: vira Adendo no `00` ou sai da doc
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

> **Autolimpeza não é auditoria.** Os itens 2, 6 e 7 são julgamento sobre texto que o mesmo
> agente escreveu, e ele tende a lê-lo como certo. Por isso a `feature-quality-gate` (step 8)
> repete os três como **dimensão L — Consistência Documental**, por quem não escreveu a wiki.
> Fazer só um dos dois não basta: sem o 7 o quality gate afoga em defasagem trivial; sem o 8
> ninguém confere quem escreveu.

### 8. Quality Gate e abertura do PR (OBRIGATÓRIO, antes do PR)

Após os testes passarem e o step 7 estar concluído, **invocar a skill `feature-quality-gate`**. Este step é função direta da skill — o agente NÃO deve esperar o usuário pedir. **O PR não abre antes do veredito**, e o `03` não diz "concluída" antes de a seção `## Quality Gate` estar preenchida.

**O que ela faz que os steps 5, 6 e 7 não fazem**: os três tomam o PRD como verdade. O quality gate confronta **`00-requisito.md` × PRD × app rodando** e detecta a classe de defeito que nenhum teste pode pegar — a **omissão silenciosa**: cláusula `RQ` que nunca virou passo, nunca virou CT, nunca virou código. Tudo verde, feature incompleta. E a **dimensão L** dela repete, por quem não escreveu a wiki, o que o step 7 declarou reconciliado: PRD/ADR × código, rules × diff, docs × comportamento, citações e IDs de CT.

**Entrada que a skill espera**:

- `00-requisito.md` com as cláusulas `RQ-##`
- `01`–`05` da wiki
- app servido e acessível
- `## Natureza da Wiki` do PRD (decide se roda regressão)

**Saída**: `06-relatorio-qa.md` + veredito.

**Quando rodado no Claude Code — despachar, não invocar em linha.** A skill exige *"por quem não
escreveu a wiki"*, e invocá-la na mesma sessão que escreveu o `01` e implementou entrega o
contrário disso. Despachar o sub-agente `fw-qa-gate` (`opus`, sem Edit/Write) com **só**: o path
da wiki, a URL do app servido, o `git diff --stat` e a instrução de ler e seguir
`.ai/skills/feature-quality-gate/SKILL.md`. Ele devolve o `06-relatorio-qa.md` **como texto**, e a
sessão grava o arquivo **sem editar** — a ausência de Edit/Write no agente é o que torna *"não
corrige nada"* uma propriedade, não uma promessa. O cabeçalho do `06` leva a linha
`Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa` — ou `mesma sessão`, quando
degradado. As dimensões que exigem Playwright MCP ou Boost rodam no próprio sub-agente: ele herda
as ferramentas MCP quando o arquivo do agente não restringe `tools`.

| Veredito | O que o fluxo faz |
|---|---|
| `APROVADO` | segue para o step 9 |
| `APROVADO COM DÉBITO` | segue para o step 9; débito fica registrado no `03-progresso.md` |
| `REPROVADO → especificação` | volta ao step 4: corrigir `01`/`02`, depois reimplementar |
| `REPROVADO → implementação` | volta à execução do passo do PRD indicado |
| `REPROVADO → teste` | volta ao `04`/`05`: escrever o CT que falha **primeiro**, depois corrigir |

**Quando pular**: feature sem nenhuma superfície validável (ex.: só refactor interno já coberto por CT verde) — registrar o motivo no `03-progresso.md`. Não pular por pressa.

> **Teto do loop**: no máximo **3 ciclos** de quality gate por feature. Ao estourar, escalar ao usuário com o que ficou aberto. Ver a skill `feature-quality-gate` para as regras de convergência.

**Depois do veredito, e só então**:

1. Registrar ciclo, veredito e data na seção `## Quality Gate` do `03-progresso.md`
2. **Abrir o PR** com o link da wiki e o veredito do `06-relatorio-qa.md` na descrição
3. Marcar o `03` como "concluída"

> Por que a ordem é dura: "linkar ao PR" ficava no step 7 e o quality gate no step 8, e o
> checklist chamava a seção de "após merge". Lido ao pé da letra, o QA acontecia depois do merge
> — e foi exatamente o que uma sessão real fez: PR aberto, `03` "concluída", quality gate nunca
> executado. O veredito é parte do PR, não um passo depois dele.

### 9. Candidatos a Rule de Projeto (DECISÃO DO USUÁRIO)

**O problema que este step resolve**: hoje uma decisão registrada em `02-decisoes-arquiteturais.md` só é lida por quem abrir aquela wiki. Na sessão seguinte, em outra feature, o agente não sabe que ela existe e repete o erro que a ADR já resolveu. **Project Rules do Laravel Boost** (`.ai/rules/`) fecham esse ciclo: são carregadas automaticamente por glob de path, para qualquer agente, em qualquer sessão.

Após o step 7, **varrer a wiki em busca de candidatos** e **apresentar ao usuário para decisão**. A skill nunca grava rule sem aprovação explícita.

**Fontes de candidatos dentro da wiki**:

| Fonte | O que procurar | Exemplo |
|---|---|---|
| `02-decisoes-arquiteturais.md` | ADR cuja **consequência generaliza** além desta feature | "Todo valor monetário é `integer` em centavos" |
| `03-progresso.md` → Notas de Implementação | **Armadilha descoberta no código** que não se infere lendo o arquivo | "`Enrollment::find()` aplica scope global de tenant" |
| `01-plano-acao.md` | Padrão obrigatório que a wiki repetiu e que vale para o projeto todo | Padrão de log `[Classe@Método]` + channel por feature |

**Os 4 gates — candidato só passa se cumprir TODOS**:

1. **Durável** — vale além desta feature e desta sprint? (decisão de fluxo/negócio pontual → não é rule)
2. **Escopável por path** — dá para expressar em glob (`app/Models/**`, `app/Http/Controllers/**`)? Se não se consegue nomear os paths, não é rule — é ADR.
3. **Não-inferível** — um agente competente, lendo o código ao redor, erraria? Se ele acertaria sozinho, a rule é só imposto de contexto.
4. **Não-redundante** — não é default do framework, não é coberto por Pint/Rector/PHPStan, não está nas guidelines do Boost e não duplica rule existente em `.ai/rules/index.md`.

**Antes de propor**: `Read .ai/rules/index.md` e as rules dos globs afetados. **Atualizar rule existente é sempre preferível a criar uma nova.**

**Teto**: no máximo **3 candidatos por feature**. Cada rule é imposto permanente de contexto em todo arquivo que casa com o glob — inflação de rules degrada o agente em vez de ajudar.

**Preferir enforcement automático à prosa** (escada do Ponytail aplicada a rules): se a restrição pode ser verificada por teste de arquitetura (`pest --arch`), PHPStan ou Rector, implementar a verificação **e** deixar a rule curta apontando para ela. Prosa só onde a máquina não alcança.

**Como apresentar**:

```text
Candidatos a rule desta feature (decisão sua):

1. [ADR-02] Valores monetários em centavos (integer)
   Glob: app/Models/**, app/Services/Billing/**
   Evidência: 02-decisoes-arquiteturais.md ADR-02 + app/Models/Invoice.php:34
   Gates: durável ✅ | escopável ✅ | não-inferível ✅ | não-redundante ✅

2. [Nota] Enrollment::find() aplica scope global de tenant
   Glob: app/Models/Enrollment.php, app/Services/Enrollment/**
   Evidência: 03-progresso.md → Notas de Implementação
   Gates: durável ✅ | escopável ✅ | não-inferível ✅ | não-redundante ✅

Virar rule? (1, 2, ambos, nenhum)
```

**Se aprovado**: invocar a skill `requirement-to-rule`, que grava via a tool MCP `record-rule` do Boost (nunca escrevendo o arquivo à mão — o Boost regenera o `.ai/rules/index.md`, e rule criada manualmente não é descoberta até a próxima regeneração).

**Se recusado**: não insistir. A decisão continua registrada na ADR, que é o comportamento atual e já é válido.

---

## Arquivo 00: Requisito — Fonte da Verdade

**Path**: `wikis/specs/{branch}/{feature}/00-requisito.md`

**Propósito**: guardar o requisito **como ele chegou**, sem interpretação, e decompô-lo em cláusulas rastreáveis. É a única linha de base independente do agente — todo o resto da wiki é derivado e, portanto, contaminável por interpretação errada.

**Três seções, dois regimes**:

| Seção | Regime |
|---|---|
| `## Texto Original` | **imutável.** Nunca editar, corrigir, resumir ou reordenar |
| `## Decomposição em Cláusulas` | derivada e revisável. Pode ser corrigida se a leitura estiver errada |
| `## Adendo N — {data}` | **imutável** como o Texto Original; um por pedido novo que chegou durante a implementação, com fonte e data. A numeração de `RQ` continua da última. Ver [Adendo ao requisito](#adendo-ao-requisito--quando-o-pedido-cresce-durante-a-implementação) |

**Obrigatório incluir**:

- Origem (card, arquivo + página, conversa) com data e autor
- Texto original verbatim, ou os trechos literais normativos quando a fonte é longa
- Decomposição em `RQ-##` com: cláusula, trecho literal de origem, tipo (funcional / autorização / não-funcional / restrição)
- **Ambiguidades e perguntas abertas** — cláusula não-testável é achado, não detalhe

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

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | {o que deve acontecer, em uma frase} | "{citação literal}" | funcional |
| RQ-02 | {…} | "{…}" | autorização |
| RQ-03 | {…} | "{…}" | não-funcional |

## Ambiguidades e Perguntas Abertas

<!-- Cláusula que não dá para testar como está. Perguntar ANTES de implementar.
     Perguntas OBRIGATÓRIAS quando o requisito tem papéis, visibilidade, notificação ou texto livre
     (as quatro que o juiz cego fez em 2026-09-21 e a sessão não fez):
     1. Acumulação de papéis, PAR A PAR: quem é X pode também ser Y? (solicitante × aprovador,
        aprovador × aprovador de outra etapa). Uma pergunta por par, não uma genérica
     2. Recorte de visibilidade: quem VÊ agora × quem JÁ PARTICIPOU. O participante histórico
        continua vendo? O que vê quem perdeu a corrida?
     3. Toda notificação tem link: para ONDE leva, e o destino ainda existe e está visível para o
        destinatário quando ele clica?
     4. Todo texto livre tem teto — no model, não só no formulário? -->

- **RQ-03**: "{precisa ser rápido}" — sem número não é testável. Qual SLA?
- **RQ-05**: conflita com RQ-02 — {descrever o conflito}

## Fora de Escopo (declarado)

<!-- O que o requisito explicitamente NÃO pede, para o quality gate não acusar omissão indevida. -->

- {item explicitamente fora}
```

> **Wiki antiga sem `00`**: wikis criadas antes da v2.10.0 não têm o arquivo. Ao retomar uma delas, reconstruir o `00` **pedindo o requisito original ao usuário** — não derivar do PRD. PRD derivado de PRD não é oráculo, e o `feature-quality-gate` vai marcar o relatório como *oráculo degradado*.

### Adendo ao requisito — quando o pedido cresce durante a implementação

O `## Texto Original` é imutável, e a skill só previa "sobrescrever / incrementar / retomar" a
wiki inteira. Entre os dois cabia o caso mais comum: o usuário pede **mais uma coisa** no meio da
implementação, na mesma branch e no mesmo PR. Sem procedimento, o pedido novo vai direto para o
código, e os testes dele nascem **do código** — a inversão exata que a `feature-test-design`
existe para proibir. Caso real: o "carimbo do painel no log de acesso" chegou depois da wiki
pronta; cinco cenários foram escritos a partir da implementação e nenhuma cláusula do `00` os
sustentava.

**Procedimento**, na ordem:

1. **Registrar no `00`** uma seção `## Adendo N — {YYYY-MM-DD}` com Fonte (quem, como chegou,
   fidelidade), o Texto Original **verbatim** do pedido novo (mesmo regime de imutabilidade) e a
   decomposição em `RQ` novos, **continuando a numeração** (`RQ-09`, `RQ-10`…). Nunca reescrever
   `RQ` existente para "acomodar" o adendo: se ele muda uma cláusula antiga, a antiga fica e o
   adendo declara qual ela substitui
2. **`## Cobertura do Requisito` do `01`** ganha as linhas dos `RQ` novos; o PRD ganha os passos
   novos ao final (`N+1`…), citando o adendo. Passo antigo que muda por causa do adendo é marcado
   inline com `*(alterado em {data}: adendo N)*`
3. **Reinvocar a `feature-test-design` só para o adendo**: entrada é o `00` (com o adendo) e o
   `04` existente; saída são cenários `CT` novos, em numeração contínua, com mutantes.
   **Antes** de escrever qualquer linha de código do adendo
4. **`03`** ganha a seção do passo novo e o item "Adendo N incorporado" na Verificação Final
5. Só então implementar

**Critério adendo × wiki nova**: mesma branch e mesmo PR → adendo. Branch nova ou PR novo → wiki
nova com `## Natureza da Wiki: evolução` e a ancestral apontada.

**Template**:

```markdown
## Adendo 1 — 2026-09-05

- **Fonte**: pedido do solicitante no chat, durante a implementação do passo 9
- **Fidelidade**: alta (texto escrito)

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> {texto verbatim do pedido novo}

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-09 | {…} | "{…}" | funcional | — |
| RQ-10 | {…} | "{…}" | restrição | RQ-04 (parcial) |
```

---

## Arquivo 01: Plano de Ação (PRD)

**Path**: `wikis/specs/{branch}/{feature}/01-plano-acao.md`

**Propósito**: PRD completo — deve ser detalhado o suficiente para um agente implementar sem ambiguidade.

**Obrigatório incluir**:
- **Natureza da Wiki**: nova / evolução / correção / ajuste + wiki ancestral — **decide se o quality gate roda regressão**
- **Cobertura do requisito**: tabela `RQ` → passos que o atendem; toda cláusula do `00` precisa aparecer
- Objetivo claro em 1-2 parágrafos
- Contexto e problema que resolve
- Análise dos arquivos/código existente que será tocado
- **Channel de log da feature** (ver seção "Padrão de Log" abaixo)
- **Autorização**: policies, gates, middleware, guards — quais serão criados/modificados
- **Rotas**: endpoints a registrar, middleware aplicado, naming convention
- **Superfície de UI**: telas/componentes que o usuário vê ou opera (Filament, Livewire, Blade, Inertia) — **é esta seção que decide se haverá `05-casos-de-teste-browser.md`**. Se não houver UI, declarar explicitamente "Sem superfície de UI"
- **Variáveis de Ambiente**: `.env` keys necessárias, config publish, defaults
- **Eventos/Listeners/Observers**: se a feature emite ou escuta eventos, hooks de model
- **Jobs/Queues**: queue connection, timeout, retries, backoff — quando aplicável
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
- Skills a invocar em cada passo (ver lista abaixo)
- Referência ao `04-casos-de-teste.md` (não duplicar cenários aqui)
- Passos de verificação (pint, tests, artisan commands)
- Passos de commit (gitmoji + escopo + mensagem)

**Skills disponíveis para referenciar no PRD**:
```
- laravel-best-practices   → qualquer código PHP Laravel
- eloquent-best-practices  → models, queries, relacionamentos
- laravel-specialist       → Sanctum, queues, Livewire, API resources
- laravel-11-12-app-guidelines → features, bugs, UI
- pest-testing             → escrever/editar testes Pest (backend e browser)
- tailwindcss-development  → qualquer Tailwind/Blade/UI
- livewire-development     → componentes Livewire
- ponytail                 → execução minimalista (escada de simplicidade)
- requirement-to-rule      → transformar decisão da wiki em Project Rule do Boost
- feature-quality-gate     → QA no agente: confronto requisito × plano × app
```

> **Integração com Ponytail**: Após a wiki ser aprovada, o Ponytail deve ser a skill de execução ativa durante toda a implementação. Ele garante que cada passo do plano seja executado com o mínimo de código necessário (reutilização → stdlib → feature nativa → uma linha → mínimo que funciona). Após implementar, rodar `/ponytail:ponytail-review` no diff para validar contra over-engineering. Atalhos deliberados devem ser marcados com `ponytail:` comment. Ver o README do repositório para o passo a passo completo da integração.

**Template `01-plano-acao.md`**:
```markdown
# Plano de Ação — {Card}: {Título da Feature}

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova | evolução | correção | ajuste
- **Wiki ancestral**: `wikis/specs/{branch}/{feature}/` — **obrigatório** se o tipo não for "nova"
- **Motivo**: {o que mudou desde a ancestral}
- **Toca infra compartilhada?**: não | sim → {o quê: seeder de permissões, middleware global, `tests/Pest.php`, config de logging, migration em tabela de outra feature}

> O tipo decide o escopo do `feature-quality-gate`: `nova` valida só a feature; os outros três disparam **regressão** contra os CT/CT-B da wiki ancestral.
>
> **Exceção que o tipo não cobre**: feature `nova` que **altera infra compartilhada** — a matriz
> de papéis, um seeder que outras features consomem, um middleware global, o `tests/Pest.php`.
> Aí o tipo é `nova` e a regressão é **obrigatória** mesmo assim, contra os CT/CT-B das features
> que consomem a infra tocada. Marcar "Toca infra compartilhada? sim" **força a regressão**,
> independente do tipo.

## Cobertura do Requisito

<!-- Toda cláusula do 00-requisito.md precisa aparecer aqui. Cláusula sem passo é omissão. -->

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | {resumo} | 3, 4 | — |
| RQ-02 | {resumo} | 5 | — |
| RQ-03 | {resumo} | — | ⚠️ fora de escopo desta entrega — justificar |

## Objetivo

{1-2 parágrafos descrevendo o que será implementado e por quê}

## Contexto

{Problema atual, limitações, por que essa feature é necessária}

## Análise dos Arquivos Existentes

### {NomeDoArquivo}
- {Descrição do que existe e como será afetado}

## Autorização

- **Policies**: {quais criar/modificar, métodos autorizados}
- **Gates**: {se aplicável}
- **Middleware**: {rotas protegidas por qual middleware}
- **Guards**: {se aplicável}

## Rotas

| Método | URI | Name | Middleware |
|--------|-----|------|------------|
| {GET/POST/...} | {/path} | {route.name} | {auth,can:...} |

## Superfície de UI

<!-- Preencher "Sem superfície de UI" quando a feature for só backend (job, webhook, command) -->

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| {NomeDoComponente} | Filament \| Livewire \| Blade \| Inertia | {/path} | {o que o usuário faz} | Sim \| Não |

**Gate de CT-B**: esta tabela é o **gatilho**, não o critério. O cenário só vai para o browser
quando afirma sobre algo que **só o navegador prova** — JavaScript executado, console/erro de JS,
acessibilidade, cor/tema, layout. Validação de formulário, gravação, listagem, filtro, ação de
tabela, notificação e autorização na tela são **teste de componente Livewire** e pertencem ao `04`.

**Gate de tela de escrita**: para toda rota `create`/`edit` desta tabela, o `04` precisa ter um
cenário de **gravação por componente** — *uma tela aberta não é uma tela que grava*.

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| {FEATURE_KEY} | {default} | {o que controla} |

## Eventos / Listeners / Observers

- **Eventos emitidos**: {lista}
- **Listeners**: {lista}
- **Observers**: {model e métodos hooked}

## Jobs / Queues

- **Job**: {nome} → queue: {connection/name}, timeout: {s}, retries: {n}, backoff: {s}

## Modelo de Execução

<!-- Quantas VEZES o caminho principal roda, e o que é compartilhado entre elas.
     Preencher "um request, sem trabalho adiado" quando for o caso — é resposta válida. -->

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | {1 · ou N, e por quê: widget lazy, tabela adiada, polling, ação assíncrona} |
| O que é adiado, e por qual gatilho? | {`lazy` do Livewire ao entrar na viewport · `deferLoading` da tabela · nenhum} |
| O que é memoizado **por request**? | {e o que isso NÃO alcança quando há N requests} |
| O que é cacheado **entre** requests? | {chave, TTL, quem invalida} |
| Custo do caminho principal | {queries do caminho comum × queries do caminho com filtro/busca} |

**Este bloco existe porque uma ADR pode estar internamente coerente e apoiada numa premissa que
ninguém escreveu.** Caso real (2026-09-17): uma ADR decidiu, com bom argumento, não cachear o
agregado quando há filtro — e assumiu implicitamente *"uma tela = um request"*. Os widgets eram
`lazy`, ou seja **oito requests independentes**, cada um recalculando: 48 queries viraram ~384 por
carga filtrada. O memo por request que o código documentava **não existia**, e não teria ajudado —
memo estático não atravessa request. Nenhum gate da wiki mede custo; declarar o modelo é o que
torna a premissa falsificável na revisão.

## Impacto em Features Existentes

- {Feature X}: {o que pode quebrar e por quê}
- {Feature Y}: {dependência compartilhada}

## Rollback

- **Migration down**: {o que `down()` faz}
- **Feature flag**: {se aplicável, como desativar}
- **Reversão de dados**: {se aplicável, como reverter dados migrados}

## Dependências

- **Composer**: {package} {version}
- **NPM**: {package} {version}

## Riscos

- {Risco 1}: {mitigação}
- {Risco 2}: {mitigação}

## Channel de Log da Feature

### Verificação de Channel Existente

- Buscar em `config/logging.php` por channels já configurados
- Verificar se já existe um channel com nome relacionado à feature (ex: `feature-{nome}`, `{sistema}-{feature}`)
- Usar `Grep` em `config/logging.php` e em `app/` por referências a `Log::channel(`

### Decisão

- **Se channel existe**: referenciar no plano como `Log::channel('{nome}')` em todos os passos
- **Se não existe**: incluir como primeiro passo de implementação a criação do channel em `config/logging.php`, com:
  - Nome: `{feature-name}` (kebab-case, mesmo nome da pasta da feature)
  - Driver: `daily` (rotação automática)
  - Path: `storage/logs/{feature-name}.log`
  - Level: `debug` (para rastreabilidade completa durante desenvolvimento)
  - Exemplo de configuração:
    ```php
    '{feature-name}' => [
        'driver' => 'daily',
        'path' => storage_path('logs/{feature-name}.log'),
        'level' => 'debug',
        'days' => 14,
    ],
    ```

> **Por que agrupar por channel**: Logs de uma feature ficam isolados em arquivo próprio, facilitando debug, auditoria e remoção futura. Evita poluir o log principal do sistema com ruído de uma feature específica.

## Estrutura de Implementação

### 1. {Nome do Passo}

> Skills: `laravel-best-practices`, `pest-testing`

- **Path**: `app/...`
- {Detalhes de implementação}
- **Logs**:
  - `Log::channel('{feature-name}')->info('[{Classe}@{metodo}] {mensagem da ação} | {parametro principal}')`
  - Especificar cada ponto de log: início, sucesso, falha, decisões de fluxo

### 2. {Nome do Passo}
...

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> Cada passo deve aplicar a escada de simplicidade:
> 1. Reutilizar código existente antes de criar novo
> 2. Usar stdlib do PHP/Laravel antes de código custom
> 3. Usar features nativas antes de dependências
> 4. Uma linha quando possível
> 5. Mínimo código que funciona
>
> Atalhos deliberados devem ser marcados com `ponytail:` comment.
> Após implementação, rodar `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em modo `ultra`** (padrão) na comunicação agent ↔ usuário.
> Arquivos wiki (00-06) são boundary do Caveman — escrever em prosa normal.
> Código, commits e PRs também são boundary do Caveman.
>
> **Model novo declara `$table`** sempre que o nome da tabela não for o plural inglês que o
> Eloquent infere — com nome em pt-BR é sempre: `centros_custo`, não `centro_custos`. Nasceu como
> defeito (2026-09-21) e é candidato natural a Project Rule no step 9.
>
> **Baseline antes do primeiro commit**: rodar a suíte completa em `{base}` e listar por nome as
> falhas pré-existentes. A `## Verificação Final` compara contra a baseline, não contra zero.

## Mapeamentos

{Tabelas de mapeamento de campos, status, etc. — quando aplicável}

## Testes

> Ver `04-casos-de-teste.md` para especificação completa dos cenários de backend.
> Ver `05-casos-de-teste-browser.md` para os cenários de UI (quando a feature tem superfície de UI).

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/pest --filter={Feature} --compact` (CTs de backend)
- [ ] `vendor/bin/pest tests/Browser --filter={Feature}` (CT-B — só se houver `05-*-browser.md`)
- [ ] `vendor/bin/pest --parallel --tia` (Pest 5 — confirma que nada mais no suite quebrou, rodando só o afetado) — comparado à **baseline** de `{base}`
- [ ] `pest --mutate --path={classe de regra}` — score, **duração** e lista de sobreviventes (score sem duração plausível é falso; ver *Pest 5*)
- [ ] **Custo medido** — queries do caminho principal e do caminho com filtro/busca, contra o `## Modelo de Execução`, com N **acima da página**
- [ ] **`/code-review high {base}...HEAD` + passe de eixos (step 6.5)** — antes da reconciliação; o único gate que lê o diff atrás de defeito de correção
- [ ] {outros comandos de verificação específicos}

## Commits
- `{gitmoji} {escopo}: {mensagem}`
- `:memo: {escopo}: wiki da feature {nome}`
```

---

## Padrão de Log — `[Classe@Método] mensagem`

### Por que este padrão

O formato `[Classe@Método] mensagem` é obrigatório em **todos os logs** do projeto. Ele resolve três problemas:

1. **Rastreabilidade**: ao ler um log, sabe-se imediatamente qual classe e método o gerou — sem precisar buscar no código
2. **Filtragem**: permite `grep` por classe ou método para isolar fluxos específicos
3. **Consistência**: padroniza a leitura em qualquer nível (info, warning, error) e em qualquer channel

### Formato Obrigatório

```
[{Classe}@{Método}] {mensagem descritiva} | {parâmetro principal}: {valor} - {contexto adicional}
```

### Anatomia da Mensagem

| Parte | Descrição | Exemplo |
|-------|-----------|---------|
| `{Classe}` | Nome da classe (sem namespace) | `AddUserToClassJob` |
| `{Método}` | Nome do método que está logando | `processAddUserToClass` |
| `{mensagem}` | Descrição clara da ação executada | `Membro associado com sucesso` |
| `{parâmetro}` | ID, status, ou valor principal manipulado | `enrollment: 280114` |
| `{contexto}` | Informação adicional relevante (opcional) | `evento: enrollment.requested` |

### Regras de Escrita

1. **Prefixo sempre entre colchetes**: `[Classe@Método]` — sem espaços dentro dos colchetes
2. **Mensagem em português**: descrever a **ação executada**, não o estado (use "Membro associado com sucesso" em vez de "Membro foi associado")
3. **Pipe `|` como separador**: entre a mensagem e os parâmetros/contexto
4. **Hífen `-` como separador secundário**: entre múltiplos parâmetros de contexto
5. **Incluir parâmetro principal sempre que possível**: IDs, slugs, status — valores que identificam o registro manipulado
6. **Nível de log apropriado**:
   - `debug` → detalhe intermediário para rastreabilidade
   - `info` → sucesso de operação esperada
   - `notice` → evento significativo mas normal (ex: queue retry agendado)
   - `warning` → condição anormal mas não fatal — **usar em `fail()` de Livewire**, fallback, retry, dado ausente
   - `error` → falha que interrompe o fluxo — **usar em `catch` de exceptions** que quebram a execução
   - `critical` → erro de sistema que exige intervenção imediata (ex: DB inacessível, API crítica fora do ar)
   - `emergency` → sistema indisponível, intervenção humana urgente
7. **Máximo de contexto estruturado**: SEMPRE passar o segundo parâmetro `array $context` do Laravel com todos os dados relevantes — IDs, payloads, snapshots de estado, dados do modelo (ver seção "Contexto Estruturado" abaixo)
8. **Exceptions no contexto**: ao logar uma exception, incluir `'exception' => $e` no array de contexto — o Laravel serializa automaticamente stack trace, mensagem e código
9. **Nível do log = severidade da ação**: `fail()` → `warning`; `catch` de exception que interrompe → `error`; `catch` de exception tratada/ignorada → `warning`

### Contexto Estruturado (array `$context`)

O Laravel aceita um segundo parâmetro `array $context` em todos os métodos de log. **Sempre usar** — é onde vai o máximo de informação estruturada para debug e auditoria.

#### O que incluir no context

- **IDs**: todos os IDs relacionados ao fluxo (`user_id`, `enrollment_id`, `turma_id`, `job_id`)
- **Payloads**: dados de entrada que dispararam a ação (`payload`, `request_data`, `webhook_data`)
- **Snapshots de estado**: valores antes/depois de alterações (`before`, `after`)
- **Dados do modelo**: atributos relevantes do model manipulado (`attributes`, `changes`)
- **Exception**: `'exception' => $e` — o Laravel serializa stack trace, mensagem e código automaticamente
- **Contexto de execução**: `queue`, `attempt`, `connection` em jobs; `route`, `ip` em controllers
- **Decisões de fluxo**: `reason`, `condition`, `skip_reason` para branches tomados

#### Exemplo de context rico

```php
Log::channel('feature-name')->info(
    '[AddUserToClassJob@processAddUserToClass] Membro associado com sucesso | enrollment: 280114',
    [
        'enrollment_id' => 280114,
        'user_id'       => 123,
        'turma_id'      => 456,
        'evento'        => 'enrollment.requested',
        'payload'       => $request->all(),
        'attributes'    => $enrollment->getAttributes(),
        'changes'       => $enrollment->getChanges(),
    ]
);
```

> **Regra de ouro**: se a informação pode ajudar a reproduzir ou diagnosticar o problema, vai no `context`. Melhor ter informação demais que de menos.

### Exemplos Práticos

```php
// Início de processamento
Log::channel('feature-name')->info('[AddUserToClassJob@handle] Iniciando adição do usuário | user_id: 123 - turma_id: 456', [
    'user_id'  => 123,
    'turma_id' => 456,
    'attempt'  => 1,
    'queue'    => 'default',
]);

// Sucesso com contexto
Log::channel('feature-name')->info('[AddUserToClassJob@processAddUserToClass] Membro associado com sucesso | enrollment: 280114 - evento: enrollment.requested', [
    'enrollment_id' => 280114,
    'user_id'       => 123,
    'turma_id'      => 456,
    'evento'        => 'enrollment.requested',
    'changes'       => $enrollment->getChanges(),
]);

// Criação de recurso externo
Log::channel('feature-name')->info('[CreateCurseducaUserJob@createCurseducaAccount] Conta criada com sucesso | aluno_id: 789', [
    'aluno_id'        => 789,
    'external_id'     => $response->json('id'),
    'response_status' => $response->status(),
]);

// Webhook recebido
Log::channel('feature-name')->info('[UnicoWebhookController@handle] Webhook recebido | evento: enrollment.requested', [
    'evento'  => 'enrollment.requested',
    'payload' => $request->all(),
    'ip'      => $request->ip(),
    'route'   => $request->path(),
]);

// Condição de fluxo — warning (dado ausente, fallback, retry)
Log::channel('feature-name')->warning('[ProcessCurseducaAccountCreationJob@handle] Usuário já existe, pulando criação | aluno_id: 789', [
    'aluno_id'   => 789,
    'skip_reason'=> 'user_already_exists',
    'existing_id'=> $existingUser->id,
]);

// fail() de Livewire — warning (fluxo interrompido pelo usuário, não é erro de sistema)
Log::channel('feature-name')->warning('[CreateEnrollmentForm@submit] Validação falhou | user_id: 123', [
    'user_id'    => 123,
    'errors'     => $this->getErrorBag()->toArray(),
    'input'      => $this->form->toArray(),
]);

// catch de exception que interrompe o fluxo — error
Log::channel('feature-name')->error('[AddUserToClassJob@processAddUserToClass] Falha ao associar membro | enrollment: 280114', [
    'enrollment_id' => 280114,
    'exception'     => $e,  // Laravel serializa stack trace + mensagem + código
    'attempt'       => $this->attempts(),
    'payload'       => $this->payload,
]);

// catch de exception tratada/ignorada — warning (não quebra o fluxo)
Log::channel('feature-name')->warning('[SyncEnrollmentsJob@handle] Erro ao sincronizar um item, continuando | enrollment: 280114', [
    'enrollment_id' => 280114,
    'exception'     => $e,
    'will_retry'    => true,
]);

// Erro crítico de sistema — critical
Log::channel('feature-name')->critical('[ProcessCurseducaAccountCreationJob@handle] API Curseduca indisponível | tentativa: 3', [
    'attempt'       => 3,
    'exception'     => $e,
    'api_endpoint'  => config('services.curseduca.url'),
    'queue'         => 'default',
]);
```

### Como Implementar no Plano

Para **cada passo de implementação** no PRD, especificar:

1. **Quais métodos terão logs** — listar cada método e os pontos exatos (início, sucesso, falha, decisão de fluxo, catch de exception, fail de validação)
2. **Qual channel usar** — `Log::channel('{feature-name}')` em todos os logs da feature
3. **Qual nível** — debug/info/notice/warning/error/critical conforme a regra de severidade:
   - `fail()` de Livewire → `warning`
   - `catch` de exception que **interrompe** o fluxo → `error`
   - `catch` de exception **tratada/ignorada** → `warning`
   - Sistema/API indisponível → `critical`
4. **Qual mensagem** — já escrever a string completa no plano, seguindo o formato
5. **Qual context** — listar o array de contexto com todos os campos relevantes (IDs, payloads, snapshots, `exception`)

> **Anti-padrão**: NUNCA usar `Log::info('...')` sem channel — sempre especificar o channel da feature.
> **Anti-padrão**: NUNCA usar mensagens genéricas como "Processando..." ou "Erro ocorrido" — sempre incluir `[Classe@Método]` e parâmetro principal.
> **Anti-padrão**: NUNCA logar apenas em `catch` — logar também no sucesso e nos pontos de decisão de fluxo.
> **Anti-padrão**: NUNCA passar context vazio — incluir o máximo de informação estruturada possível (IDs, payloads, snapshots, exception).
> **Anti-padrão**: NUNCA usar `error` para `fail()` de validação — `fail()` é uma condição esperada de interrupção, usar `warning`.
> **Anti-padrão**: NUNCA usar `info` para exceptions — exceptions são anomalias, usar no mínimo `warning` (tratada) ou `error` (interrompe).

### Contexto Compartilhado (`Log::shareContext`)

Para contexto que se propaga automaticamente em **todos** os logs da requisição/job (correlation ID, user ID, request ID):

```php
// No início do lifecycle (middleware, job boot, service provider)
Log::shareContext([
    'correlation_id' => Str::uuid()->toString(),
    'user_id'        => Auth::id(),
    'request_uri'    => request()->path(),
]);

// Todos os logs subsequentes incluem automaticamente esses campos
Log::channel('feature-name')->info('[Controller@handle] Processando requisição');
// → context mesclado: ['correlation_id' => '...', 'user_id' => 123, 'request_uri' => '...', ...]
```

> **Quando usar**: em jobs longos, webhooks, fluxos multi-etapas onde o mesmo ID precisa aparecer em todos os logs para rastreabilidade.

### Driver JSON em Produção

O channel `daily` gera arquivos de texto. Para parsing estruturado em produção (ELK, Datadog, Grafana), trocar o driver para `json`:

```php
'{feature-name}' => [
    'driver' => 'daily',
    'path'   => storage_path('logs/{feature-name}.log'),
    'level'  => env('LOG_LEVEL', 'debug'),
    'days'   => 14,
    'replace_placeholders' => true,
],
```

> O Laravel 11+ já formata context como JSON automaticamente quando o handler suporta. Para garantir, usar `'driver' => 'json'` ou configurar o handler do channel.

### Testando Logs em Pest

> Técnica **opcional**. Log não é cláusula do requisito, então a `feature-test-design` **não deriva
> CT de log** e este template não os exige mais — quem confere o log é a **dimensão D** da
> `feature-quality-gate` (17 logs conferidos um a um na feature de referência, sem nenhum CT de
> log). Use quando o requisito pede trilha de auditoria (aí é `RQ`) ou quando um passo do PRD
> trata o log como saída observável. Helper de log declarado e nunca usado é código morto
> (achado F9 do 6.5 em 2026-09-21).

Para verificar que os logs foram emitidos corretamente nos CTs:

```php
// Spy — verifica que foi chamado sem bloquear
Log::spy();

it('emite log de sucesso ao associar membro', function () {
    Log::shouldReceive('channel')
        ->once()
        ->with('feature-name')
        ->andReturn(Mockery::self());

    Log::channel('feature-name')
        ->shouldReceive('info')
        ->once()
        ->with('[AddUserToClassJob@processAddUserToClass] Membro associado com sucesso | enrollment: 280114', \Mockery::on(fn ($context) => $context['enrollment_id'] === 280114));

    // ... executar ação
});

// Alternativa mais simples — Log::spy() captura tudo
it('emite log no channel correto', function () {
    Log::spy();

    // ... executar ação

    Log::shouldHaveReceived('channel')
        ->with('feature-name')
        ->atLeast()
        ->once();
});
```

> **Incluir CTs de log** no `04-casos-de-teste.md` para validar: channel correto, nível correto, mensagem no formato `[Classe@Método]`, e context com campos esperados.

### Trait UnicoLogging (se aplicável)

Se o projeto possuir uma trait de logging (ex: `UnicoLogging`), verificar:

- `Grep` por `trait UnicoLogging` ou `trait.*Logging` em `app/`
- Se existir, usar a trait nos classes da feature — ela formata automaticamente o prefixo `[Classe@Método]`
- Se não existir, implementar o formato manualmente via `Log::channel(...)->info('[Classe@metodo] ...')`
- Documentar no plano qual abordagem será usada

---

## Arquivo 02: Decisões Arquiteturais (ADR)

**Path**: `wikis/specs/{branch}/{feature}/02-decisoes-arquiteturais.md`

**Propósito**: Registrar o "porquê" das escolhas — não o "o quê". Usa formato **ADR (Architecture Decision Record)** para padronizar e facilitar consulta futura.

**Incluir**:
- Cada decisão não-óbvia com justificativa
- Alternativas consideradas e por que foram descartadas
- Trade-offs aceitos
- Restrições externas (APIs, limites de parceiros, compliance)
- Padrões reutilizados de outras partes do sistema
- Link entre decisões relacionadas (ex: "Refine ADR-01")
- Decisões overridden (quando uma ADR substitui outra)

**Template `02-decisoes-arquiteturais.md`**:
```markdown
# Decisões Arquiteturais — {Card}

## ADR-01: {Título da Decisão}

**Status**: Aceita | Proposta | Deprecada
**Data**: {YYYY-MM-DD}

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
- {arquivo:linha ou link relacionado}
- Refine: ADR-{xx} (se aplicável)

---

## ADR-02: {Título da Decisão}
...
```

---

## Arquivo 03: Progresso / Tracking

**Path**: `wikis/specs/{branch}/{feature}/03-progresso.md`

**Propósito**: Checklist de implementação para rastrear o que foi feito e retomar de onde parou.

**Estrutura**: Seções com checkboxes `- [ ]` agrupadas pelos mesmos passos do `01-plano-acao.md`.

**Checkbox só fecha com evidência inline.** Formato `- [x] {item} — {evidência}, {data}`; item sem evidência continua `[ ]`. "Atualizar em tempo real, não em lote" já estava escrito aqui e foi ignorado: num caso real a Verificação Final foi fechada por substituição em lote antes de alguns comandos rodarem, e um teste marcado verde estava vermelho. A evidência inline é o que torna o lote impossível — não há o que colar. Conferência: `grep -n '^- \[x\]' 03-progresso.md | grep -v ' — '` tem de voltar vazio na Verificação Final.

**Duas seções que só existem para o step 7 e o step 8**: `## Conformidade com Rules` (uma linha por rule cujo glob casa o diff) e `## Quality Gate` (ciclo, veredito, data). Enquanto a segunda estiver vazia, a feature **não** está concluída e o PR não abre.

**Validação de espelho**: verificar que a estrutura de seções do `03-progresso.md` espelha exatamente os passos do `01-plano-acao.md` — se o plano tem 8 passos, o progresso tem 8 seções correspondentes.

**Template `03-progresso.md`**:
```markdown
# Progresso — {Card}

## {Seção 1 do Plano}
- [ ] {Item 1}
- [ ] {Item 2}

## {Seção 2 do Plano}
- [ ] {Item 1}

## Testes
- [ ] `{NomeDoTesteTest}` — CT-01, CT-02, CT-03
- [ ] `tests/Browser/{Nome}Test.php` — CT-B01, CT-B02 <!-- só se houver 05-*-browser.md -->

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/pest --filter={Feature} --compact`
- [ ] `vendor/bin/pest tests/Browser --filter={Feature}` <!-- se houver CT-B -->
- [ ] `vendor/bin/pest --parallel --tia` — nada mais no suite quebrou além da **baseline** de `{base}` (falhas pré-existentes por nome)
- [ ] `pest --mutate --path={classe}` — {score} em {duração}, {n} sobreviventes listados (no Windows, via lançador `.cmd`)
- [ ] **Custo medido** — queries do caminho principal × do caminho filtrado, contra o `## Modelo de Execução`, com N acima da página
- [ ] **`/code-review high {base}...HEAD` + passe de eixos (step 6.5)** — antes da reconciliação; achados fechados ou rejeitados com motivo
- [ ] Roteiro "Desenhado × Implementado" do `05-*-browser.md` preenchido <!-- se houver CT-B -->
- [ ] Desvios propagados ao `01`/`02`/`04`/`05` de origem, marcados `*(alterado em …)*`
- [ ] Citações `arquivo:símbolo:linha` reverificadas — {n}/{n} ok (saída do script colada)
- [ ] IDs `[CT-nn]` do teste ⊆ `04`/`05` e vice-versa
- [ ] Falsificabilidade dos CTs novos — {n} de {m} falham sem o fix; os demais "não falsificável nesta pilha", com motivo
- [ ] Docs pt/en, CHANGELOG e README reconciliados com o comportamento final
- [ ] `git commit`

<!-- Cada [x] acima leva " — {evidência}, {data}". Ex.: `- [x] composer test:kit — 677/677, 2026-09-05`
     Evidência com NÚMERO leva o comando que o gerou (`grep -c …`, saída do script). Degradação
     declarada ("sem PCOV", "plugin ausente") leva a PROVA NEGATIVA (`php -m`, `ls vendor/…`).
     Número sem comando e ausência sem prova foram os dois achados que o juiz cego devolveu
     CONTRA A SESSÃO em 2026-09-21 (QA-03, QA-04). -->

## Conformidade com Rules

<!-- Uma linha por rule de .ai/rules/index.md cujo glob casa com um arquivo do diff. "violada" = blocker do PR. -->

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `auth.md` — cobrir `fi-auth-layout` em par | `app/Filament/Pages/Auth/**` | aplicada | CT-07 + CT-38 |

## Quality Gate

<!-- Preenchido no step 8. Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

- **Ciclo**: {n} · **Veredito**: {APROVADO | APROVADO COM DÉBITO | REPROVADO → destino} · **Data**: {YYYY-MM-DD}
- **Relatório**: `06-relatorio-qa.md`

## Auditoria Pré-Implementação
<!-- Saída dos steps 5 e 6, ANTES de escrever código. Não confundir com "Desvios do Plano",
     que é pós-implementação. -->

### Revisão profunda (step 5) — premissas do plano contra o código real
| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| {"adicionar import X"} | {já existe em `Arquivo.php:12`} | passo 3 reescrito |

### Auditoria Ponytail (step 6)
| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | {…} | sim / recusada: {motivo} | `01`, passo 4 |

## Despachos

<!-- Claude Code: uma linha por disparo de sub-agente, com o modelo, o que ele NÃO recebeu e a
     auditoria do retorno. Host sem sub-agente: uma linha "Sem despacho — host sem sub-agente".
     Tarefa que rodou em linha por exceção: "Sem despacho — {motivo}".
     Auditoria REPROVADA também é linha (com o redespacho ao lado); fallback para general-purpose
     por agente fw-* indisponível vai na coluna Modelo. -->

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Auditoria do retorno |
|---|---|---|---|---|---|---|
| 1 | 3 | `mecanico` — Superfície Livewire | haiku | — | tabela, 7 linhas | 2/7 conferidas por grep |
| 2 | 6.5 | `fw-revisor-diff` — eixos sobre `main...HEAD` | opus | `01`, `03` | 3 achados, 1 rejeitado | 3/3 reproduzidos |
| 3 | 8 | `fw-qa-gate` — quality gate | opus | conversa | `06` gravado verbatim, APROVADO COM DÉBITO | `git status` limpo antes/depois |

## Blockers
<!-- Impedimentos encontrados durante implementação -->
- [ ] {Blocker 1}: {descrição + o que está sendo feito para resolver}

## Desvios do Plano
<!-- Onde a implementação divergiu do PRD e por quê -->
- {Passo X alterado}: {motivo}

## Notas de Implementação
<!-- Descobertas durante o código que não estavam no plano -->
- {Descoberta 1}: {impacto e onde foi documentado}

## Retrospectiva
<!-- O que funcionou bem no planejamento e o que faltou -->
- **Funcionou bem**: {ponto positivo}
- **Faltou no plano**: {ponto de melhoria para próxima wiki}
```

---

## Arquivos 04 e 05: Casos de Teste — delegados à `feature-test-design`

**Paths**: `wikis/specs/{branch}/{feature}/04-casos-de-teste.md` e `05-casos-de-teste-browser.md`

A **derivação e a escrita dos casos de teste não pertencem a esta skill**. Ela delega à skill
[`feature-test-design`](../feature-test-design/SKILL.md), invocada no step 4 desta wiki.

### Por que delegar

O `04` era escrito logo depois do `01`, pelo mesmo agente, para "validar os passos do PRD".
Isso é a direção invertida: o PRD é a **interpretação** do requisito, e testar a interpretação a
confirma. Medido sobre 318 defeitos reais com 11 modelos, derivar teste a partir do código/plano
em vez da especificação multiplica por ~8 os testes que codificam o bug como comportamento
esperado e corta por ~3 os que detectam o defeito.

É o mesmo princípio que criou o `00-requisito.md` como oráculo e que proíbe o
`feature-quality-gate` de corrigir o que julga: **quem escreve o plano não deriva o teste do
próprio plano**.

Sintoma medido na coletânea antes da delegação, sobre 9 wikis reais e 125 casos: 52% dos casos
caíam nos quatro arquétipos que o antigo template nomeava (happy path, falha, autorização, log)
e nada além; análise de valor limite apareceu **1 vez em 125**; tabela de decisão e pairwise,
nenhuma; 9 de 19 cláusulas `RQ` rastreáveis ficaram sem nenhum caso.

### O contrato da delegação

```text
Invocar: feature-test-design

Entrada (nesta ordem de autoridade):
  1. 00-requisito.md            → ORÁCULO. É daqui que o comportamento esperado sai
  2. 01-plano-acao.md           → APENAS paths, rotas, stack e a tabela ## Superfície de UI
  3. .ai/rules/, tests/Pest.php → convenção de teste do projeto
  4. versões: Pest, Filament, Livewire, Laravel

Saída:
  - 04-casos-de-teste.md   (sempre)
  - 05-casos-de-teste-browser.md   (só se o gate abaixo passar)
  - perguntas novas devolvidas para ## Ambiguidades do 00-requisito.md

Proibido passar como entrada:
  - implementação da feature (ela ainda não existe; se existir, não é fonte de comportamento)
```

**O `01-plano-acao.md` não é fonte de comportamento esperado.** Se a única forma de saber o que
o sistema deve fazer é ler o PRD, o `00-requisito.md` está incompleto — e isso é achado, não
atalho.

**Ordem**: sempre que possível, o step 6 (Ponytail) roda sobre `01`/`02` **antes** desta invocação;
se o `04` já existir quando um corte mudar a `## Superfície de UI`, ele é re-sincronizado no mesmo
passo (ver step 6). O `04` derivado de uma superfície que depois foi cortada fica com CT órfão.

### Gate do `05` (browser)

A tabela `## Superfície de UI` do PRD continua sendo o gatilho, mas o critério mudou: **o cenário
vai para o browser somente quando afirma sobre algo que só o navegador prova** — JavaScript
executado, console/erro de JS, acessibilidade, cor/tema, layout.

Tudo o mais que parece "de tela" em Filament é **teste de componente Livewire**, roda em
milissegundos, sem Node e sem Playwright, e pertence ao `04`: validação de formulário,
gravação, listagem, busca, filtro, ação de tabela, notificação e autorização na tela.

> **Gate de tela de escrita**: para toda rota `create`/`edit` da `## Superfície de UI`, o `04`
> precisa ter um cenário de **gravação por componente**. *Uma tela aberta não é uma tela que
> grava* — um `GET` fica verde com o salvamento quebrado.

Se nenhum cenário exigir navegador: **não criar o `05`** e registrar no `04` a seção
`## Sem CT-B` com o motivo.

### Contrato do construtor de testes (`executor-ct`)

O teste Pest de backend nasce do Gherkin do `04`, escrito por quem **não implementou** — no Claude
Code, o sub-agente `fw-executor-ct` (`sonnet`; definição em
[`agents/fw-executor-ct.md`](agents/fw-executor-ct.md)). É a generalização do contrato dos CT-B para
o backend, medida em 5 lotes (2026-09-21): **todo vermelho que sobrou era defeito real**. O
contrato, em resumo — o arquivo do agente tem o texto completo:

- **Fonte é o `04`**: `## Setup Global` inteiro + só as regras/cenários do lote. Pode ler `app/`
  para descobrir nome de classe, método e rota que o cenário deixa em aberto; **nunca** para
  inferir o `Então`. Não lê `01` nem `02`
- **Fixture por transições reais**: a situação de partida se constrói chamando a máquina de
  estados do domínio (`enviar()`, `aprovar()`…), não gravando `situacao` à força — reimplementar a
  transição no teste esconde exatamente o defeito que o teste existe para pegar. O helper
  (`{entidade}Em('{situacao}')`) vive em `tests/Pest.php` e é dono de um lote `D0`, anterior aos
  outros
- **Um lote por arquivo, arquivos disjuntos** entre construtores paralelos; `tests/Pest.php` tem um
  único dono
- **Todo `Então` vira asserção; o nome do teste começa com `[CT-nn]`**; `Esquema do Cenário` vira
  `->with([...])`, uma linha por `Exemplos`; cenário `@obsoleto` não vira teste
- **Vermelho é classificado antes de qualquer edição**: (a) teste errado → corrige o teste;
  (b) **implementação divergente da especificação → não corrige**, deixa vermelho e registra com
  a saída literal; (c) flake → anota. Máximo 3 iterações por arquivo. **Vermelho por (b) é
  resultado válido** e é o que a sessão roteia (Adendo → CT → correção)
- **Proibido**: tocar `app/`, `database/`, `config/`; relaxar asserção; remover cenário; editar
  `00`/`01`/`02`/`04`
- **Saída em formato fixo**: arquivos e contagem; status por CT com a causa a/b/c; divergências
  (o que o `04` afirma / o que o código faz / `arquivo:símbolo:linha`); ambiguidades do `04` que
  teve de resolver; saída literal do `pest` e do `pint`
- **Interrompido no meio** (limite de sessão): reporta *"estado parcial"* com os arquivos tocados;
  a sessão confere `git status` antes de retomá-lo por `SendMessage`

### Ciclo de escrita e auditoria dos CT-B (loop + sub-agente)

A **especificação** dos CT-B é da `feature-test-design`. A **execução** deles contra a UI real é
desta skill, no step 7, e roda em loop delegado a um sub-agente — porque falha de browser
despeja HTML, snapshot e stack de Playwright no contexto, porque acertar seletor e timing é
tentativa e erro, e porque quem escreve o teste a partir do `05` não deve ser quem implementou.

**Contrato do sub-agente**:

```text
Entrada:
  - 05-casos-de-teste-browser.md   (os CT-B a implementar)
  - 01-plano-acao.md               (seção ## Superfície de UI — o que foi desenhado)

Tarefa:
  1. Escrever tests/Browser/{Feature}/{Nome}Test.php a partir dos CT-B
  2. Rodar: vendor/bin/pest --testsuite=Browser   (NUNCA com --parallel)
  3. Se falhar, classificar a causa ANTES de mexer em qualquer coisa:
     (a) CT-B especificado errado (seletor/rota/texto)  → corrigir o CT-B no arquivo 05
     (b) Implementação divergente do PRD                → NÃO corrigir; registrar divergência
     (c) Flake (timing/assíncrono)                      → rever a estratégia de espera e anotar
  4. Nas causas (a) e (c), se o Playwright MCP estiver disponível, observar a página ao vivo
     para descobrir o locator/estado real. Na causa (b): NÃO usar o MCP para contornar
  5. Repetir no máximo 3 iterações

PROIBIDO:
  - Alterar código de aplicação para o teste passar
  - Relaxar assertion para "ficar verde"
  - Remover CT-B que não passou

Saída (formato fixo):
  - Arquivos de teste criados/alterados
  - Status por CT-B: verde | vermelho + causa classificada (a/b/c)
  - Tabela "Desenhado × Implementado" preenchida
  - Lista de divergências para "Desvios do Plano" do 03-progresso.md
```

**Teste vermelho por causa (b) é resultado válido, não falha do ciclo** — é exatamente a
divergência entre desenhado e implementado que se queria capturar. Sub-agente que "conserta" a
aplicação para ficar verde destrói o instrumento de medição. Após 3 iterações com vermelho,
parar e registrar como blocker no `03-progresso.md`.

> **Sondagem rápida dentro do loop**: `vendor/bin/pest --agent='visit("/rota")->assertSee("...");'`
> confirma uma premissa de UI sem versionar nada. Serve para descobrir, não para provar.

### Fatos do `pest-plugin-browser` que o sub-agente precisa saber

Cada um destes já custou tempo em projeto real, e vários contradizem o que a documentação
anterior desta skill afirmava:

1. **O plugin sobe o próprio servidor** — HTTP in-process, porta aleatória. **Nada** de Herd,
   `php artisan serve`, Sail ou Vite dev server; nada de `APP_URL` a configurar.
2. Como é o **mesmo processo**, valem dentro do navegador: `DB_DATABASE=:memory:`,
   `RefreshDatabase`, **`$this->actingAs($user)` antes do `visit()`** e `assertAuthenticated()`.
   Use `actingAs()` — login pela tela custa dezenas de segundos por cenário. Reserve um único
   cenário para o formulário de login, que é o caminho real do usuário.
3. **Nunca `wait($segundos)`.** O plugin reexecuta cada assertion até o teto de
   `pest()->browser()->timeout()`. Espere pelo estado final visível. `waitForText`,
   `waitForSelector` e `waitUntil` **não existem** — não inventar.
4. **`assertPathIs` antes das asserções de conteúdo.** Depois de qualquer ação que navegue
   (`press`, `click`), ela vem primeiro — é ela que espera a navegação. Invertido, o `assertSee`
   roda contra o snapshot da página anterior e falha **com a ação tendo funcionado**.
5. **`npm run build` é pré-requisito duro** — sem `public/build/manifest.json` toda tela responde
   `ViteException` e todo cenário falha por um motivo que não é o dele.
6. **Nunca `--parallel` com browser** (multiplica processos de navegador e produz timeout). E
   como o `--tia` exige run completo, `--parallel --tia` e os CT-B não convivem numa invocação
   só — são dois comandos.
7. **`assertNoSmoke()` só em tela de autoria própria**; em tela de plugin de terceiro use
   `assertNoJavaScriptErrors()`, senão a suíte fica vermelha por `console.log` alheio.
8. **`visit([...])` em lote aborta na primeira falha** — as rotas seguintes não são verificadas
   naquele run.
9. Upload é **`attach()`**, não `upload()`.
10. **`assertSee` não valida tema**: passa com texto branco em fundo branco.

**Assertion de console ou de status nunca é o oráculo único de um CT-B.** Todo cenário precisa de
pelo menos uma assertion sobre o que ele afirma — o elemento, o valor ou o registro.

### Playwright MCP na validação (OPCIONAL — ferramenta de observação)

> **O `pest-plugin-browser` atesta. O Playwright MCP observa.**
>
> O CT-B é sempre um teste Pest versionado. O MCP nunca produz cobertura, nunca entra no `05`
> como evidência e nunca substitui um CT-B — ele existe para o agente **ver** a página quando o
> teste falha.

As ferramentas de debug do próprio plugin (`debug()`, `tinker()`, `waitForKey()`, `--headed`)
**exigem um humano na frente** e travariam um agente autônomo. As que servem —
`screenshot()` e `content()` — devolvem um PNG caro ou a página inteira. O MCP resolve os três
casos em que isso não basta: descobrir o locator verdadeiro numa falha de seletor, observar
quando o elemento realmente aparece em UI assíncrona, e extrair seletores de tela existente.

| Etapa | Uso | Tools |
|---|---|---|
| **Step 3** — pesquisa | extrair locators reais das telas que a feature vai tocar | `browser_navigate`, `browser_find`, `browser_generate_locator` |
| **Loop do CT-B** — falha (a)/(c) | observar a página ao vivo e corrigir o CT-B | `browser_find`, `browser_generate_locator`, `browser_wait_for` |
| **Step 7** — evidência | anexar console e rede ao roteiro *Desenhado × Implementado* | `browser_console_messages`, `browser_network_requests` |

> Para o step 7, verificar primeiro se o **`Browser Logs`** do Boost MCP já resolve — é uma tool
> que o projeto provavelmente já tem, sem adicionar servidor novo.

**Configuração obrigatória**:

```json
{
  "mcpServers": {
    "playwright": {
      "command": "npx",
      "args": ["@playwright/mcp@latest",
               "--isolated", "--headless",
               "--caps=testing",
               "--test-id-attribute=data-testid"]
    }
  }
}
```

- **`--isolated` é obrigatório.** O default do MCP é perfil persistente: o login sobrevive entre
  sessões e, com uma URL errada, o agente pode clicar em produção autenticado.
- **`--caps=testing`** habilita `browser_generate_locator` e os `browser_verify_*`.
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

**Se o MCP não estiver disponível**, a skill funciona: `screenshot()` no ponto da falha →
`content()` filtrado com `Grep` → ler o Blade/componente e derivar o seletor do código-fonte →
após 3 iterações, escalar ao usuário.

---

## Execução de Testes com Pest 5

Detectar a versão do Pest no step 3. Se o projeto está em **Pest 5** (requer **PHP 8.4+** e PHPUnit 13), usar os recursos abaixo; em Pest 4, cair para `vendor/bin/pest --filter`.

Instalação/upgrade (conforme a doc oficial — **não existe `php artisan pest:install`**):

```bash
composer remove phpunit/phpunit
composer require pestphp/pest --dev --with-all-dependencies
./vendor/bin/pest --init          # cria tests/Pest.php
```

Vindo de Pest 4: `"pestphp/pest": "^5.0"` no `composer.json` + todos os plugins para `^5.0`.

**Duas armadilhas medidas (2026-09-21):**

- **`--testsuite=A --testsuite=B` só honra o último.** Uma suíte por comando, as duas saídas coladas
- **`pest --mutate` dá 100 % falso no Windows.** O plugin relança `argv[0]` (`vendor/bin/pest`,
  script sh) por Symfony Process; o `cmd` não o executa, o subprocesso sai com código 1 em ~30 ms e
  o plugin conta **qualquer** saída não-zero como mutante morto. Sintoma: *206 mutantes em 3 s*
  para uma suíte de 200 s — e o juiz cego caiu nisso também (*"2 mutantes, 100 %"*). Regra:
  **score só vale com `Duration` compatível com N × tempo dos testes cobridores e com a lista de
  sobreviventes.** No Windows, lançar por um `.cmd` poliglota na raiz do projeto, para que
  `argv[0]` seja executável pelo `cmd`:

  ```
  <?php /*
  @echo off
  php "%~f0" %*
  exit /b %errorlevel%
  */ require __DIR__.'/vendor/pestphp/pest/bin/pest';
  ```

  `XDEBUG_MODE=coverage cmd //c pestw.cmd tests/Feature/{Feature} --mutate --path=app/Models/X.php --covered-only --parallel`
  — medido de verdade na feature de referência: 206 mutantes, 196 mortos, 7 timeout,
  3 sobreviventes (context de log), 98,54 % em 594 s. Timeout conta como morto no score; listar
  os sobreviventes é o que vale

### TIA — Test Impact Analysis (`--tia`)

Roda apenas os testes afetados pelo diff e replica o resultado em cache para o restante. Exige driver de cobertura (**PCOV ou Xdebug**) instalado.

**Forma canônica: `--parallel --tia`.** O `--parallel` **não** é pré-requisito técnico do `--tia` (o `--tia` funciona sozinho), mas é a invocação que a doc oficial usa, e os dois são complementares: o TIA corta **quanto** roda, o parallel corta **quanto tempo** o que sobrou leva. Usar sempre juntos como padrão da skill.

```bash
vendor/bin/pest --parallel --tia    # PADRÃO da skill
vendor/bin/pest --tia               # sozinho funciona (sem ganho de paralelismo)
vendor/bin/pest --tia --fresh       # descarta o grafo e re-grava do zero
vendor/bin/pest --tia --filtered    # carrega no PHPUnit só os arquivos afetados
vendor/bin/pest --no-tia            # desativa em uma execução
vendor/bin/pest --baseline          # imprime o path do storage do grafo
```

> **Replay não é atalho que pula trabalho.** A doc é explícita: cada teste em cache guarda tudo que produziu, **inclusive as linhas e branches cobertos** — um run replayado reporta a mesma cobertura de um run completo. É por isso que o `--tia` pode ser usado na Verificação Final sem perder confiança.

**Cuidado com `--parallel` + CT-B**: browser em paralelo multiplica processos de navegador e exige DB por worker. Antes de adotar `--parallel` no comando de browser, confirmar que o projeto isola o DB por processo; se houver flake, rodar os CT-B em série (`vendor/bin/pest tests/Browser`) e deixar o `--parallel --tia` para o suite de backend.

**Onde encaixa no fluxo da skill**:

- **Durante a implementação** (passo a passo do PRD): `--parallel --tia` a cada passo concluído. Feedback em segundos em vez de minutos, o que torna viável rodar o suite **a cada passo** e não só no final.
- **Na Verificação Final**: `--tia` responde "o que mais no sistema meu diff afetou?" — isto é exatamente a seção `## Impacto em Features Existentes` do PRD, agora verificável em vez de especulativa. Divergência entre o previsto no PRD e o que o TIA marcou como afetado → registrar em "Desvios do Plano" do `03-progresso.md`.
- **CT-B**: o TIA mapeia assets de browser. Se o projeto tem CT-B, registrar o watch no `tests/Pest.php`:

  ```php
  pest()->tia()->watch([
      'public/build/**/*' => 'tests/Browser',
  ]);
  ```

- **Ativação sem flag** (recomendado pela doc do Pest): `pest()->tia()->locally()` no `tests/Pest.php` — liga localmente e desliga sozinho em CI.

> ⚠️ **Nunca usar `--tia` no comando que roda o suite em CI.** A doc do Pest é explícita: o pipeline deve rodar o suite completo. O TIA em CI existe só num job dedicado de baseline (`--tia --coverage --fresh`, artefato `pest-tia-baseline`).
>
> Cache fica em `~/.pest/tia/<project-key>/` (caminho via `--baseline`). Edições cosméticas (whitespace, comentários, docblocks) são normalizadas e **não** disparam testes.

### Agent plugin (`--agent`) — verificação pontual durante a implementação

```bash
composer require pestphp/pest-plugin-agent --dev
```

Executa um snippet PHP dentro da configuração real do Pest do projeto e devolve pass/fail definitivo — em vez de o agente "achar" que funcionou:

```bash
# backend
vendor/bin/pest --agent='$u = \App\Models\User::factory()->create(); $this->actingAs($u)->get("/dashboard")->assertOk();'

# UI + backend na mesma verificação (requer pest-plugin-browser)
vendor/bin/pest --agent='visit("/contato")->type("email", "a@b.com")->press("Enviar")->assertSee("Mensagem enviada");'
```

Regras: aspas simples envolvendo o snippet, aspas duplas para strings PHP internas, **classes sempre com FQN** (`\App\Models\User`). Vários `--agent` na mesma chamada rodam isolados.

**Onde encaixa**: durante a implementação de um passo do PRD, para confirmar uma premissa antes de escrever o teste definitivo. **Não substitui** os CTs do `04`/`05` — o `--agent` é efêmero e não fica versionado. Uma verificação via `--agent` que se mostre valiosa deve virar CT no arquivo correspondente.

### Outros recursos do Pest 5 úteis à skill

| Recurso | Comando | Uso na skill |
|---|---|---|
| Sharding por tempo real | `pest --update-shards` / `--shard=1/4` | CI de features grandes com muitos CT-B |
| Profiling | `pest --profile` | Investigar CT lento antes de aceitar o tempo como normal |
| Type coverage | `pest --type-coverage` | Verificação Final em features com muito DTO/enum |
| Mutation testing | `pest --mutate` | Features de regra de negócio crítica (cálculo, cobrança) — ver a armadilha do Windows acima |
| Novos matchers | `toBeEmail()`, `toBeUlid()`, `toBeIpAddress()`, `toBeMacAddress()`, `toBeHostname()`, `toBeDomain()`, `toBeBase64()`, `toBeHexadecimal()` | Substituem regex custom nos CTs — aplicar a escada do Ponytail |

---

## Citações de código — `arquivo:símbolo:linha`

O step 3 desta skill (e a rule `specs.md` do projeto-cobaia) exige `arquivo:linha` para toda
afirmação sobre vendor ou padrão interno. O formato só com linha falha de dois jeitos distintos,
medidos na mesma feature:

| Classe | Exemplo real | O que pega |
|---|---|---|
| **Errada ao nascer** | `Login.php:165` para `return app(LoginResponse::class)`, que está na 169 — e o `composer.lock` não mudou em nenhum commit da feature | conferir **ao escrever** (step 5) |
| **Deslocada depois** | citações de arquivos da própria app, 3 a 10 linhas fora após Pint e imports novos | conferir **no step 7** |

"Reverificar depois" não pega a primeira classe; "conferir ao escrever" não pega a segunda. São
dois momentos, e o mesmo comando serve aos dois.

**Formato obrigatório**: `{path relativo à raiz do projeto}:{símbolo}():{linha}` ou, para
intervalo, `:{inicial}-{final}`. O símbolo é o método, função, constante ou chave de array que a
linha (ou a primeira linha do intervalo) contém — é ele que sobrevive ao deslocamento e que
permite conferir sem abrir o arquivo.

```text
vendor/filament/filament/src/Auth/Pages/Login.php:isUserAllowedToAccessPanel():172
app/Support/DestinoAposLogin.php:urlPara():41-58
config/logging.php:'autenticacao':132
```

Path curto (`Login.php:172`) é aceito só depois de o path completo ter aparecido no mesmo
documento. Citação sem símbolo não passa no step 5 nem no step 7.

**Conferência mecânica** — rodar na raiz do projeto; toda linha `ERRO` é uma citação a corrigir
na fonte, nunca a apagar para "passar":

```bash
grep -rhoE "[A-Za-z0-9_./-]+\.php:[A-Za-z_'\"][A-Za-z0-9_'\"]*(\(\))?:[0-9]+" wikis/specs/{branch}/{feature}/ \
  | sort -u | while IFS=: read -r arquivo simbolo linha; do
    simbolo="${simbolo%()}"; simbolo="${simbolo//\'/}"; simbolo="${simbolo//\"/}"
    if sed -n "${linha}p" "$arquivo" 2>/dev/null | grep -q -- "$simbolo"; then echo "ok   $arquivo:$simbolo:$linha"
    else echo "ERRO $arquivo:$simbolo:$linha"; fi
  done
```

Registrar o resultado na Verificação Final do `03` (`— 14/14 ok, {data}`). A dimensão L da
`feature-quality-gate` roda o mesmo comando e compara.

---

## Arquivos Extras (conforme necessidade)

Criar apenas quando a feature exige:

| Arquivo | Quando criar |
|---------|-------------|
| `05-casos-de-teste-browser.md` | Feature que passa no gate de CT-B — ver [Arquivos 04 e 05](#arquivos-04-e-05-casos-de-teste--delegados-à-feature-test-design) |
| `05-design.md` | Feature com UI significativa (Filament, Livewire, Blade) |
| `05-api-contract.md` | Feature com API externa (payloads, endpoints, autenticação) |
| `05-db-schema.md` | Feature com schema complexo (múltiplas tabelas, migrations em cadeia) |
| `05-fluxo.md` | Feature com fluxo multi-etapas (queues + jobs + callbacks) |
| `05-rollback.md` | Feature com migrations destrutivas ou mudanças de schema irreversíveis |
| `05-performance.md` | Feature com volume alto (batch processing, relatórios, imports de CSV) |
| `05-security.md` | Feature com dados sensíveis (PII, pagamentos, autenticação, LGPD) |

---

## Checklist Final da Skill

Antes de encerrar a invocação:

### Requisito

- [ ] `00-requisito.md` criado com Fonte, Texto Original **verbatim** e Fidelidade declarada
- [ ] Requisito decomposto em cláusulas `RQ-##`, cada uma citando o trecho literal de origem
- [ ] Ambiguidades e perguntas abertas listadas — e perguntadas ao usuário antes de implementar
- [ ] Fora de escopo declarado (evita o quality gate acusar omissão indevida)
- [ ] Perguntas obrigatórias do `00` feitas: acumulação de papéis **par a par**, participante histórico × recorte de visibilidade, destino do link de toda notificação, teto de todo texto livre
- [ ] `## Natureza da Wiki` preenchida no PRD (+ wiki ancestral se não for "nova")
- [ ] `## Cobertura do Requisito` no PRD mapeia **toda** cláusula `RQ` a passo(s) ou justificativa

### Planejamento

- [ ] Branch lida e estrutura de pasta criada
- [ ] Wiki existente verificada (retomar/sobrescrever/incrementar se já existe)
- [ ] Nome da feature confirmado com usuário
- [ ] Pesquisa feita (`search-docs`, `database-schema`, leitura de arquivos)
- [ ] `search-docs` consultado para **cada stack** que o PRD toca (Laravel, Filament, Livewire, Inertia, Pest, Tailwind) e origem citada no plano
- [ ] Lacunas do `search-docs` cobertas por doc oficial: Pest 5, Playwright/`pest-plugin-browser`, pacotes de terceiros
- [ ] Rotas, policies, config, composer, wikis existentes verificados
- [ ] APIs de terceiros inspecionadas (vendor source ou docs) — métodos e schema confirmados
- [ ] Tabela `## Superfície Livewire` no `02` preenchida — **sempre** que a feature cria página, widget ou componente: métodos públicos, propriedades públicas sem `#[Locked]` e os arrays de estado do framework que o código consome. Pacote de terceiro acrescenta os quatro greps do vendor, com um model por linha
- [ ] Dados fornecidos pelo usuário validados contra o DB (quando aplicável)
- [ ] Factories confirmadas (existência + states) para todos os CTs
- [ ] Stack de testes verificado: versão do Pest, `pest-plugin-browser`, Playwright, `APP_URL`, traits em `tests/Pest.php`
- [ ] **Baseline** da suíte completa em `{base}` registrada antes do primeiro commit, falhas pré-existentes por nome
- [ ] Model novo cujo nome de tabela não é o plural inglês inferido declara `$table`

### Documentação

- [ ] `01-plano-acao.md` escrito com passos numerados + skills referenciadas + logs em todas as etapas
- [ ] `01-plano-acao.md` inclui seções: Autorização, Rotas, Variáveis de Ambiente, Eventos, Jobs, Impacto, Rollback, Dependências, Riscos
- [ ] `02-decisoes-arquiteturais.md` escrito em formato ADR (Status, Contexto, Decisão, Alternativas, Consequências)
- [ ] `03-progresso.md` escrito com checkboxes espelhando o plano + seções Blockers, Desvios, Notas, Retrospectiva
- [ ] **`feature-test-design` invocada** (step 4) com o `00-requisito.md` como entrada primária — o `04` **não** foi escrito inline a partir do PRD
- [ ] `04-casos-de-teste.md` recebido com: perfil de risco, varredura SFDIPOT, mapa de regras, técnica nomeada por regra e **mutantes previstos com o cenário que mata cada um**
- [ ] Toda rota `create`/`edit` da `## Superfície de UI` tem cenário de **gravação por componente** no `04`
- [ ] Perguntas devolvidas pela `feature-test-design` incorporadas em `## Ambiguidades` do `00-requisito.md`
- [ ] `01-plano-acao.md` tem a seção `## Superfície de UI` preenchida (ou "Sem superfície de UI" declarado)
- [ ] Gate de CT-B avaliado → `05-casos-de-teste-browser.md` criado **ou** motivo da ausência registrado no `04`
- [ ] Se houver CT-B: dependências (`pest-plugin-browser`, Playwright) confirmadas ou incluídas como passo no PRD
- [ ] Arquivos extras (`05-*`) criados se necessário (rollback, performance, security)

### Log

- [ ] Channel de log da feature verificado/criado e referenciado em todos os passos do PRD
- [ ] Padrão de log `[Classe@Método] mensagem` especificado em cada passo de execução do PRD
- [ ] Context estruturado (array `$context`) especificado em cada log do PRD
- [ ] Log **não** vira CT no `04` (log não é cláusula do requisito) — quem o confere é a dimensão D do quality gate; exceção: requisito que pede trilha de auditoria, e aí é `RQ`

### Validação

- [ ] Revisão profunda pós-escrita executada — premissas do plano re-validadas contra o código
- [ ] **Varredura da classe irmã** executada para toda classe nova, com a irmã escolhida e as ocorrências registradas no `03`
- [ ] `## Modelo de Execução` preenchido no PRD (ou "um request, sem trabalho adiado" declarado)
- [ ] **Auditoria da wiki executada** — `/ponytail:ponytail-review` invocado e sugestões aplicadas
- [ ] Step 6 rodou sobre `01`/`02` **antes** da derivação do `04` — ou o `04` foi re-sincronizado após os cortes (CT órfão declarado `@obsoleto`)
- [ ] `03-progresso.md` espelha exatamente os passos do `01-plano-acao.md`
- [ ] Filosofia de Implementação (Ponytail) incluída no PRD
- [ ] Confirmar com usuário se o plano está correto antes de implementar

### Pós-Implementação e Reconciliação (antes do PR)

- [ ] Todo `[x]` do `03-progresso.md` tem evidência inline (`— {resultado}, {data}`); nenhum fechado em lote
- [ ] Cada desvio do `03` tem a edição correspondente no `01`/`02`/`04`/`05` de origem, marcada `*(alterado em …)*` — nenhuma afirmação do `01`/`02` contradiz o código
- [ ] Toda citação `arquivo:símbolo:linha` da wiki reverificada pelo grep — resultado no `03`
- [ ] IDs `[CT-nn]`/`[CT-Bnn]` do teste ⊆ `04`/`05` e vice-versa — **saída do `diff` colada na Verificação Final, vazia**
- [ ] **Revisão de código do diff executada** (step 6.5) **antes** da reconciliação, por quem não implementou; cada achado confirmado virou Adendo no `00` + CT no `04` + correção, nessa ordem
- [ ] No Claude Code: `/code-review` rodou com alvo explícito (`{base}...HEAD`), nível `high`, sem `--fix` — e o passe de eixos rodou em sub-agente cego ao `01`/`03`
- [ ] Falsificabilidade dos CTs novos provada por `git stash` — cada um falha sem a correção **ou** está declarado "não falsificável nesta pilha", com o motivo
- [ ] `## Superfície Livewire` do `02` re-varrida sobre o código final **antes** do 6.5
- [ ] Todo número da `## Verificação Final` tem o comando que o gerou; toda degradação declarada tem a prova negativa (`php -m`, `ls vendor/…`)
- [ ] `pest --mutate` com duração plausível e sobreviventes listados (no Windows, via lançador `.cmd`)
- [ ] Contagens da wiki inteira (`01`, `02`, `03`, `04`: nº de CTs, regras, mutantes, permissions, linhas de varredura) derivadas por `grep -c`, nunca digitadas — número digitado envelhece no primeiro adendo
- [ ] Todo número **corrigido** durante o ciclo foi procurado na wiki inteira (`grep -rn "{valor antigo}"`) — a cópia em outro arquivo é o que sobrevive ao gate
- [ ] Requisito que cresceu virou `## Adendo N` no `00`, com `RQ` novos, e a `feature-test-design` foi reinvocada para ele **antes** do código
- [ ] Tabela `## Conformidade com Rules` do `03` preenchida para toda rule cujo glob casa o diff — nenhuma `violada`
- [ ] Docs de usuário (pt **e** en), CHANGELOG e README reconciliados; nenhuma frase neles sem `RQ` ou ADR de origem
- [ ] Roteiro "Desenhado × Implementado" do `05-*-browser.md` preenchido, com divergências replicadas em "Desvios do Plano" e na fonte
- [ ] Notas de implementação e retrospectiva breve escritas
- [ ] CT-B escritos e rodados via sub-agente; divergências classificadas (CT errado / implementação divergente / flake)
- [ ] Se o Playwright MCP foi usado: só como observação (`--isolated --headless --caps=testing`), nenhum ref em arquivo de teste, nenhuma sessão MCP registrada como cobertura

### Delegação (Claude Code)

- [ ] Todo disparo de sub-agente está em `## Despachos` do `03`, com modelo, cegueira e auditoria do retorno; tarefa em linha por exceção tem "Sem despacho — motivo"
- [ ] Nenhum `general-purpose` despachado sem `model` explícito
- [ ] Passe de eixos do 6.5, revisão adversarial e step 8 rodaram em sub-agente **cego** — ou a degradação está declarada no `03` e no cabeçalho do `06`
- [ ] Todo retorno auditado: presença, integridade (`git diff --stat` antes/depois do lote) e amostragem
- [ ] `ls .claude/agents/fw-*.md` conferido antes do primeiro despacho; fallback `general-purpose`/{model} registrado no quadro quando o agente faltou
- [ ] Rota `mecânico` só com **um item por despacho**; lote misto foi para `construtor`
- [ ] Retorno com número sem comando, `git diff --stat` de untracked ou "não encontrado" sem prova negativa foi **reprovado e refeito** — e a reprovação está no quadro

### Quality Gate e PR

- [ ] **`feature-quality-gate` invocado** (step 8) — no Claude Code, via `fw-qa-gate` sem Edit/Write, `06` gravado verbatim pela sessão — e ciclo/veredito/data registrados na seção `## Quality Gate` do `03-progresso.md`
- [ ] `06-relatorio-qa.md` **existe** no diretório da wiki (`ls wikis/specs/{branch}/{feature}/06-relatorio-qa.md`) — a ausência dele é blocker do PR, e é a evidência de que o step 8 rodou
- [ ] Se `REPROVADO`: achado roteado para o destino correto (especificação / implementação / teste) e reciclado
- [ ] **Só depois do veredito**: PR aberto com link da wiki e veredito do `06` na descrição; `03` marcado "concluída"
- [ ] Candidatos a rule avaliados nos 4 gates e **apresentados ao usuário** — gravados via `requirement-to-rule` só se aprovados

### Após o merge

- [ ] Channel de log ajustado (level reduzido ou removido)

## Skills Companheiras

A feature-wiki é a primeira estação de uma esteira de skills que cobrem o ciclo completo:

| Camada | Skill | Responsabilidade | Boundary |
|--------|-------|------------------|----------|
| **Comunicação** (agent ↔ usuário) | [Caveman](https://github.com/JuliusBrussee/caveman) — modo padrão `ultra` | Prosa terse — corta ~75% dos tokens removendo fluff, artigos, fillers | **NÃO aplica em arquivos wiki** (00-06), código, commits, PRs |
| **Planejamento** (estrutura de documentação) | feature-wiki | requisito + PRD + ADR + tracking + padrão de log | não deriva caso de teste — testar o próprio plano confirma o plano |
| **Especificação de teste** | `feature-test-design` | deriva o `04`/`05` do **`00-requisito.md`**, com técnica formal e gate de mutantes | não escreve código nem corrige implementação |
| **Execução** (código) | [Ponytail](https://github.com/DietrichGebert/ponytail) | Mínimo código que funciona — escada de simplicidade | Não corta validação, segurança, tratamento de erros |
| **Qualidade** (QA no agente) | `feature-quality-gate` | Confronta `00-requisito` × PRD × app rodando; audita a consistência wiki × código × docs × rules (dimensão L); roteia achado para especificação / implementação / teste. Roda **antes do PR** | Não corrige nada — só lê, reproduz e reporta |
| **Memória de projeto** (rules) | `requirement-to-rule` | Decisão da wiki vira Project Rule do Boost em `.ai/rules/` | Só o que é específico da aplicação; ecossistema é guideline do Boost |
| **Orquestração** (Claude Code) | sub-agentes por rota — pasta `agents/` de cada skill, instalados com `cp .ai/skills/*/agents/*.md .claude/agents/` | Modelo por complexidade, contexto por cegueira; quadro de despacho no `03` | A sessão nunca delega captura verbatim do requisito, perguntas ao usuário nem veredito final |

### Caveman + feature-wiki: fronteira clara

**Modo padrão: `ultra`.** Ao iniciar uma sessão de planejamento com esta skill, ativar `/caveman:caveman ultra` — a compressão máxima da prosa vale porque o conteúdo denso vive nos arquivos wiki, não na conversa. Se a resposta ficar ambígua num ponto crítico, descer para `/caveman:caveman full` apenas naquele trecho (o Auto-Clarity do Caveman já faz isso automaticamente em security warnings, ações irreversíveis e sequências multi-etapas).

> **Comando correto**: `/caveman:caveman {modo}` (com namespace `caveman:`, igual ao `/ponytail:ponytail`). NUNCA usar `/caveman` sem o namespace — o comando não será encontrado.
> Modos disponíveis: `lite` | `full` | `ultra` | `off`.

O Caveman tem uma regra de **Auto-Clarity** que desativa o modo terse em situações críticas (security warnings, irreversible actions, multi-step sequences). Mas isso é implícito — a feature-wiki torna explícito:

> **Arquivos wiki são boundary do Caveman.**
>
> - `00-requisito.md` — o texto original é **verbatim por definição**. Comprimir aqui é falsificar a fonte da verdade.
> - `01-plano-acao.md` — PRD precisa ser "minucioso o suficiente para um agente implementar sem ambiguidade". Compressão destrói essa propriedade.
> - `02-decisoes-arquiteturais.md` — ADR é argumentativo por natureza (Contexto, Decisão, Alternativas, Consequências). Fragmentos perdem o raciocínio.
> - `03-progresso.md` — Checklists e descrições de blockers/desvios precisam de clareza.
> - `04-casos-de-teste.md` — CTs já são estruturados (tabelas, code blocks), mas a prosa explicativa entre eles não deve ser comprimida.
> - `05-*.md` — Arquivos extras (rollback, performance, security) são críticos e não podem ser ambíguos.

**Onde Caveman é bem-vindo**:
- Conversa agent ↔ usuário durante a sessão de planejamento
- Resumos de progresso ("CT-01 passou, CT-02 falha em assertion X")
- Perguntas e confirmações ("Confirmar nome da feature: X?")
- Respostas a dúvidas rápidas durante a implementação

**Onde Caveman NÃO se aplica** (já definido pelo próprio Caveman):
- Código/commits/PRs: "write normal"
- Security warnings e irreversible action confirmations

### Como ativar o trio

```bash
# 1. feature-wiki (via Laravel Boost)
php artisan boost:add-skill gsferro/laravel-ai-skills
php artisan boost:update

# 2. Ponytail (escolha um agente)
# Claude Code:
#   /plugin marketplace add DietrichGebert/ponytail
#   /plugin install ponytail@ponytail
# Windsurf:
#   curl -o .windsurf/rules/ponytail.md https://raw.githubusercontent.com/DietrichGebert/ponytail/main/.windsurf/rules/ponytail.md

# 3. Caveman (escolha um agente)
# Claude Code:
#   /plugin marketplace add JuliusBrussee/caveman
#   /plugin install caveman@caveman
# Windsurf:
#   curl -o .windsurf/rules/caveman.md https://raw.githubusercontent.com/JuliusBrussee/caveman/main/.windsurf/rules/caveman.md
```

Sessão com o trio ativo: `/caveman:caveman ultra` + `/ponytail:ponytail full` + `feature-wiki` → resposta curta + diff curto + plano detalhado.

---

## Exemplo de Estrutura Criada

```text
wikis/
└── specs/
    └── ferro/
        └── 579/
            └── relatorio-mba-lote/
                ├── 00-requisito.md                  ← requisito bruto imutável + RQ-##
                ├── 01-plano-acao.md
                ├── 02-decisoes-arquiteturais.md      ← formato ADR
                ├── 03-progresso.md                   ← + Blockers, Desvios, Retrospectiva
                ├── 04-casos-de-teste.md              ← backend: + CTs de log e autorização
                ├── 05-casos-de-teste-browser.md      ← CT-B + roteiro desenhado × implementado
                ├── 05-api-contract.md                ← extra quando necessário
                ├── 05-rollback.md                    ← extra quando necessário
                └── 06-relatorio-qa.md                ← saída do feature-quality-gate
```

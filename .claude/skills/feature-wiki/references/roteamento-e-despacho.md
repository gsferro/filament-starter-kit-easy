> Referência da feature-wiki 4.0.0. Lida em: antes de montar o quadro de despacho e antes do primeiro despacho de cada step (rota, ferramentas, o que o sub-agente não recebe). Fonte única de: tabela de rotas (com o perfil do hook e o `maxTurns` de cada agente), formato do quadro de despacho e mapa de roteamento por step.

# Roteamento e despacho

As regras de roteamento — os dois eixos, a rota `mecânico`, como obter as rotas, paralelo,
quadro visível, o que roda em linha e a auditoria do retorno — ficam no corpo do `SKILL.md`, seção
*Execução e Delegação*. Aqui ficam as tabelas.

### Rotas

| Rota | Modelo | Uso nesta esteira | Ferramentas | Construção (hook `guarda-subagente.sh`) |
|---|---|---|---|---|
| **mecânico** | `haiku` | grep em lote com a tabela pronta (Superfície Livewire, classe irmã, factories, rotas, policies, config), os scripts da reconciliação (`rastreabilidade.sh`, `checkbox-sem-evidencia.sh`, `citacoes.sh`, `ids-ct.sh`, `conformidade-rules.sh`; `indice.sh --check` com `07-tickets/`), espelho `01` → `03`, contagens por `grep -c`, `search-docs` uma pergunta por consulta — devolve o artefato **como texto**; a sessão grava | leitura + Bash | — |
| **construtor** | `sonnet` | gerar artefato a partir de template e insumo: rascunho do `01`/`02` a partir do pacote de pesquisa, código de **um** passo do PRD guiado pelo plano e pelo `04`, arquivo de teste a partir do Gherkin | tudo | — |
| **analista** | `opus` | julgamento com contexto: revisão profunda do plano (step 5), ADR, derivação do `04` pela `feature-test-design` (step 7), classificação de achado — devolve o artefato **como texto**; a sessão grava. **Nunca** o step 12: sem MCP, o gate 4 da `requirement-to-rule` não roda | leitura + Bash | — |
| **revisor-diff** | `opus` | step 9, passe de eixos sobre o diff (`git diff {base}...HEAD -- . ':(exclude)wikis'`). **Cego ao PRD** | leitura + Bash; **sem** Edit/Write | perfil `revisor-diff`: nega Read/Grep de `01-*` e `03-*`; `git diff`/`git show`/`git log -p` sem `':(exclude)wikis'`; Bash que cita o `01`/`03` ou altera a árvore; Edit/Write |
| **adversário-ct** | `opus` | revisão adversarial da `feature-test-design` (step 7). Recebe **só** `00` + `04`/`05` (+ `wikis/glossario.md`, se existir) | leitura; **sem** Edit/Write/Bash | perfil `adversario-ct`: nega Read e Grep fora de `00`, `04`, `05`, `wikis/glossario.md` e arquivos das skills; Bash, Edit e Write. Glob passa (só nomes) |
| **qa-gate** | `opus` | step 11: roda a `feature-quality-gate` inteira e devolve o `06` **como texto**. Não grava nada | leitura + Bash + MCP herdado (Boost, Playwright); **sem** Edit/Write | perfil `qa-gate`: nega Bash que altera a árvore e Edit/Write; lê a wiki inteira |
| **executor-ct** | `sonnet` | escrever e rodar os testes Pest de **backend** a partir do Gherkin do `04`, sob o [contrato do construtor de testes](delegacao-casos-de-teste.md#contrato-do-construtor-de-testes-executor-ct): classifica cada vermelho em a/b/c e **nunca toca `app/`** | tudo, sob o contrato | perfil `executor-ct`: nega Edit/Write em `app/`, `database/migrations/`, `00`/`04`/`05`, `03` e `07-tickets/` (ler o ticket é permitido); Read/Grep (e Bash) do `01`/`02`; git que altera a árvore; `pint` sem path ou com path em `app/` (o executor formata só os seus testes). `maxTurns: 40` |
| **executor-ctb** | `sonnet` | escrever e rodar os CT-B em loop, sob o [contrato dos CT-B](delegacao-casos-de-teste.md#ciclo-de-escrita-e-auditoria-dos-ct-b-loop--sub-agente) | tudo, sob o contrato | perfil `executor-ctb`: nega Edit/Write em `app/`, `database/migrations/`, `00`/`04`/`05`, `03` e `07-tickets/` (a correção do `05` volta como texto); git que altera a árvore. `maxTurns: 60` |
| **sessão principal** | o da sessão | captura **verbatim** do requisito, decomposição em `RQ`, entrevista em três raias e perguntas ao usuário, confirmação das costuras de teste, sugestão do step 8, decisão de roteamento de achado, veredito final, step 12 (tem MCP), auditoria de todo retorno | — | — |

**O que a coluna *Construção* garante — e o que não.** O bloco `hooks:` é idêntico nos cinco
agentes (só o perfil muda) e está no frontmatter de cada um. **Read, Grep, Glob, Edit e Write** passam
pelo hook por construção: o path é normalizado (`\` → `/`, `.` e `..`, relativo ao `cwd`) e o que o
perfil proíbe é negado antes de a ferramenta rodar; no Grep, o glob é lido como o rg o lê (lista,
último que casa decide, glob só negado casa o resto, glob vence type). **No Bash a cobertura é heurística**: o hook lê o
comando por padrões (cita `01`/`03`, `git diff` sem a exclusão, `rm`, `sed -i`, `>` para dentro do
repositório…) e não vê escrita por interpretador (`php -r`, `python -c`), PowerShell nem corpo de
heredoc — a lista completa está no cabeçalho do `scripts/guarda-subagente.sh`. Por isso o
`fw-revisor-diff` e o `fw-qa-gate` devolvem `git status --porcelain` de antes e de depois, e a sessão
compara — é sinal, não prova: nova edição num arquivo que já estava modificado não muda o porcelain.
**Ferramenta MCP herdada não passa pelo hook**: o matcher cobre só as ferramentas de arquivo e o Bash,
e os agentes sem `tools:` (`fw-executor-ct`, `fw-executor-ctb`, `fw-qa-gate`) herdam o MCP da sessão —
um `tinker` do Boost lê o `01` e grava em `app/` sem hook. O fallback `general-purpose` não tem hook:
fica só o contrato no prompt. `maxTurns` dos
executores é hipótese a calibrar — nenhum despacho registrou turnos; o valor é folga sobre os passos
do contrato (leitura, escrita, até 3 iterações, pint; no CT-B, a observação pelo Playwright MCP).
Por que (estudo §7.1 T6/T7, §7.6; roteiro item 9): a cegueira e o "não corrige nada" eram por
instrução, e o loop dos executores não tinha teto mecânico.

## Formato do quadro de despacho

| # | Agente / tarefa | Modelo | Depende de | Não recebe (cegueira) | Por quê este modelo |
|---|---|---|---|---|---|
| 1 | `mecanico` — greps da Superfície Livewire, tabela pronta | haiku | — | — | transformação direta, sem julgamento |
| 2 | `fw-revisor-diff` — eixos sobre `main...HEAD`, sem `wikis/` | opus | último construtor | `01`, `03`, raciocínio da sessão | julgamento crítico; cegueira exigida |

### Mapa de roteamento por step

Uma linha por tarefa, na ordem dos steps — a mesma do `SKILL.md`: 0 → 1 → 2 → 3 → 4 → 5 → 6 → 7 →
8 (condicional) → implementação → 9 → 10 → 11 → PR → 12.

| Step | Tarefa | Rota | Em paralelo com | Cegueira |
|---|---|---|---|---|
| 0, 2 | capturar requisito verbatim, decompor em `RQ`, nomear a feature | **sessão** | — | — |
| 3 | mapeamento amplo do código | `Explore` (built-in); sem ele, `mecânico` com trechos ou `general-purpose`/`sonnet` | greps abaixo | — |
| 3 | greps de Superfície Livewire, classe irmã, factories, rotas, policies, config, `.env.example` — **tabelas prontas** | `mecânico` ×N | entre si e com o `Explore` | — |
| 3 | `search-docs` por stack, com a versão | `mecânico` | entre si | — |
| 4 | `00-requisito.md` | **sessão** | — | — |
| 4 | entrevista em três raias — perguntas ao desenvolvedor (desenho) e registro das perguntas ao solicitante (requisito); a raia fato vai para os greps do step 3 | **sessão** | — | — |
| 4 | rascunho do `01` e do `02` a partir do pacote de pesquisa e das respostas | `construtor` | — | — |
| 4 | `03` espelhando o `01` | `mecânico` | — | — |
| 5 | levantar cada premissa do plano no código: existe? assinatura? linha? | `mecânico` | classe irmã | — |
| 5 | julgar as divergências, corrigir a wiki; confronto código × afirmação levado ao usuário | `analista` ou **sessão** (a pergunta é sempre da sessão) | — | — |
| 6 | `/ponytail:ponytail-review` sobre `01`/`02` | em linha (comando do plugin) | — | — |
| 7 | derivação do `04`/`05` — lê e segue `{skills}/feature-test-design/SKILL.md` | `analista` | — | recebe `00` inteiro; do `01`, **só** paths, rotas e `## Superfície de UI`; do `02`, **só** `## Superfície Livewire`; `wikis/glossario.md` se existir (a própria skill delimita). Perguntas voltam como saída, ❓/➡️ numeradas `Q?1, Q?2…`; a sessão renumera na sequência `Qn` e as leva ao usuário. `## Costuras de Teste` volta como proposta, com `Confirmada` vazia |
| 7 | confirmar as costuras com o desenvolvedor (raia desenho) e preencher `Confirmada`; costura trocada → re-derivação do grupo | **sessão** | — | — |
| 7 | revisão adversarial do `04`, depois das costuras confirmadas | `adversário-ct`, despachado pela **sessão** | — | recebe **só** `00` + `04`/`05` (+ `wikis/glossario.md`, se existir) |
| 7 | `## Testes` do `03` com os CT/CT-B | `mecânico` | revisão adversarial | — |
| 8 | conferir os sinais (tamanho, compactação, sinal de escopo, refatoração larga); sugerir `/feature-tickets` — só o usuário invoca | **sessão** | — | — |
| impl. | cada passo do PRD, com o `04` como contrato | `construtor`, **sequencial** por padrão | só com arquivos disjuntos | não edita `00`, `04`, `05` |
| impl. | cada ticket de `07-tickets/` da fronteira (feature fatiada) | sessão nova (`/feature-tickets {wiki} {NN}`) ou `construtor` | ticket sem bloqueio pendente e arquivos disjuntos | recebe **só a fatia**: o ticket, `00`, `02`, os passos do `01` que ele aponta, os CT dele; **não recebe** os outros tickets, o `03` nem a conversa |
| impl. | teste Pest a partir do Gherkin do `04` | `executor-ct` | passo seguinte, se disjunto | não lê `01`/`02` e **não edita** `app/`, `database/migrations/`, `00`/`04`/`05`, `03` nem `07-tickets/` — por hook; lê `app/` só para nomes, nunca para o `Então` |
| pré-9 | re-varrer a `## Superfície Livewire` do `02` sobre o **código final** | `mecânico` | — | — |
| **9** | `/code-review high {base}...HEAD` | o próprio comando (já roda em sub-agente isolado) | passe de eixos | — |
| **9** | passe de eixos sobre o diff | `revisor-diff` | `/code-review` | recebe o alvo `git diff {base}...HEAD -- . ':(exclude)wikis'`, os eixos, a `## Superfície Livewire` do `02` e as rules que casam o diff; **não recebe** `01`, `03` nem o raciocínio da sessão (hook `revisor-diff`); devolve `git status --porcelain` de antes e de depois, e a **sessão** compara |
| 9 | roteamento do achado: o que o solicitante não escreveu → `P-nn` → CT → correção (→ pergunta ao solicitante, se contradiz ou estende o pedido; → ticket novo no fim, com `07-tickets/`); o que viola ou omite `RQ` existente → CT com `Origem` = `RQ-nn` → correção, sem `P-nn` (→ o CT no ticket que já cobre a `RQ`, com `07-tickets/`); registro em `## Revisão do Diff (step 9)` do `03` | **sessão** decide; `analista` com a `feature-test-design`, reinvocada só para o achado, deriva o CT com `Origem` = `P-nn` ou `RQ-nn`; `construtor` corrige | — | a derivação recebe o `00` (com a `P-nn`, quando há) e o `04`, como no step 7 |
| 9, 11 | re-sincronização do `04` após corte que mude a `## Superfície de UI` depois do step 7 | `mecânico` cruza índice × elementos cortados; **sessão** decide `@obsoleto` ou re-derivar | — | — |
| 10 | `rastreabilidade.sh`, `checkbox-sem-evidencia.sh`, `citacoes.sh`, `ids-ct.sh`, `conformidade-rules.sh {wiki} {base}` (saída vazia é o critério), docs pt/en × CHANGELOG; com `07-tickets/`, `indice.sh --check` (fonte única da alocação a ticket) | `mecânico` ×N | entre si | — |
| 10 | conformidade com rules — o `conformidade-rules.sh` lista as rules casadas pelo diff; veredito aplicada / n.a. / violada por rule | `mecânico` roda, `analista` julga | — | — |
| 10 | CT-B em loop | `executor-ctb` | — | contrato existente; hook `executor-ctb` — a correção do `05` volta como texto e a **sessão** grava |
| **11** | `feature-quality-gate` inteira — lê e segue o `SKILL.md` dela | `qa-gate` | — | **não recebe** a conversa; só path da wiki, URL do app, `git diff --stat` e `{base}`. Devolve o `06` entre `<<<06` e `>>>06` e o `git status --porcelain` de antes e depois; a **sessão** grava só o que está entre os delimitadores, sem editar, e compara os dois `git status` |
| 11 | regenerar `wikis/specs/INDEX.md` com o `indice.sh` da `feature-tickets` (se instalada), depois do veredito | em linha (um comando) | — | — |
| 12 | `requirement-to-rule` inteira — coleta, 4 gates, o único prompt de aprovação, `record-rule`, índice, commit na branch do PR aberto; devolve a linha fixa de `## Candidatos a Rule` | **sessão**; ou sub-agente que herda MCP (sem `tools` restrito) para coleta e gates, com o prompt na sessão. **Nunca** `analista` | — | recebe só o path da wiki |

> **Por que o step 11 é o maior ganho.** A `feature-quality-gate` pede *"por quem não escreveu a
> wiki"* e *"não corrige nada"*. Invocada em linha, ela roda na sessão que escreveu o `01` e
> implementou — a cegueira correlacionada que ela existe para quebrar. Em sub-agente **sem
> Edit/Write** e com o hook `qa-gate`, as duas exigências deixam de ser promessa: o juiz não viu a
> conversa e não consegue consertar — no Bash, por padrão de comando e pela comparação do
> `git status --porcelain`.

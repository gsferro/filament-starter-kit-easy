---
name: feature-quality-gate
description: >
  Etapa de QA dentro do agente, antes do PR. Invoque no step 11 da
  feature-wiki, com a implementação e os testes verdes, ou sempre que precisar
  validar se uma feature entregue atende ao que foi pedido: a pedido (revisar
  como QA) ou antes de abrir PR com UI ou regra de negócio sensível.
  Confronta 00-requisito.md x 01-plano-acao.md x app rodando; monta a Matriz
  de Rastreabilidade para achar omissão silenciosa (cláusula RQ que nunca
  virou passo, teste nem código); roda as dimensões do perfil de risco
  (cobertura do requisito, fronteiras, matriz de permissão, log real, N+1, UX
  de erro, dark mode, acessibilidade, segurança da superfície nova,
  regressão, oráculo fraco e mutation score via pest --mutate, consistência
  documental: PRD/ADR x código, rules x diff, docs x CHANGELOG, glossário); e
  roteia cada achado por severidade a um destino: especificação,
  implementação, teste, infra ou não-defeito. Não corrige nada: lê, reproduz
  e reporta em 06-relatorio-qa.md. Palavras-chave: QA, quality gate.
license: MIT
compatibility: >
  Projeto Laravel com Pest 4 ou 5; bash e php no PATH para os scripts. No
  Claude Code roda como sub-agente fw-qa-gate, sem Edit/Write e com hook no
  Bash; em outro host roda em linha e o 06 declara a independência degradada.
  Opcionais, declarados no 06 quando faltam: app na APP_URL, Pest 5 (--tia),
  pest-plugin-agent (--agent), PCOV ou Xdebug (--mutate), pest-plugin-browser,
  Playwright MCP, Boost MCP, petrkindlmann/qa-skills. A falta só rebaixa o
  veredito quando deixa uma dimensão não verificada.
metadata:
  version: "1.7.0"
  requires: "feature-wiki>=4.0.0"
---

# Feature Quality Gate — QA no Agente, com Roteamento

## Glossário

| Sigla | Significado |
|-------|-------------|
| **RQ** | Cláusula de requisito — unidade numerada do `00-requisito.md` |
| **P-nn** | Premissa — afirmação que a feature assume sem que o solicitante a tenha escrito (`## Premissas` do `00`); nasce de achado confirmado de revisão (steps 5, 9, 11) ou de decisão de desenho que muda o que a feature promete |
| **Matriz de Rastreabilidade** | Tabela `RQ`/`P-nn` → passo do PRD → CT → CT-B → ticket → código → resultado. Sigla **RTM** (*Requirements Traceability Matrix*) só em contexto de QA formal / auditoria |
| **CT** | Caso de Teste de backend (`04-casos-de-teste.md`) |
| **CT-B** | Caso de Teste de Browser (`05-casos-de-teste-browser.md`) |
| **PRD** | Plano de ação (`01-plano-acao.md`) |
| **SBTM** | Session-Based Test Management — exploratório com charter e time-box |
| **RCRCRC** | Heurística de regressão: Recent, Core, Risk, Configuration, Repaired, Chronic |
| **`{skills}`** | Diretório onde a skill citada está instalada: o primeiro dos três — `.ai/skills/` (Boost), `.claude/skills/` (espelho local), `~/.claude/skills/` (global) — que **contém a skill citada**. Não "o primeiro que existir": `.ai/skills/` pode existir sem a skill. `{skills}/feature-wiki/scripts/` e `{skills}/feature-quality-gate/scripts/` podem estar em diretórios diferentes |
| **`{wiki}`** | Pasta da feature: `wikis/specs/{branch}/{feature}/` |
| **estudo §n** | Seção do [estudo de 2026-09-26](https://github.com/gsferro/laravel-ai-skills/blob/main/estudos/2026-09-26-agentskills-spec-to-spec-to-tickets.md) (spec Agent Skills × `to-spec` × `to-tickets`), de onde vêm as regras novas da 1.7.0 |

## Índice

- [Princípios Inegociáveis](#princípios-inegociáveis)
- [Quando Invocar](#quando-invocar)
- [Entradas e Gate de Entrada](#entradas-e-gate-de-entrada)
- [Gate de Esforço por Risco](#gate-de-esforço-por-risco)
- [Fluxo de Execução](#fluxo-de-execução)
- [As 12 Dimensões](#as-12-dimensões) — começa por [Mecânica × julgamento](#mecânica--julgamento)
- [Classificação e Roteamento](#classificação-e-roteamento) — inclui o [Veredito e o teto por cobertura](#veredito-e-teto-por-cobertura)
- [Convergência do Loop](#convergência-do-loop)
- [Arquivo 06: Relatório de QA](#arquivo-06-relatório-de-qa)
- [Delegação a Skills Externas](#delegação-a-skills-externas)
- [Playwright MCP como Confronto](#playwright-mcp-como-confronto)
- [Regressão Condicional](#regressão-condicional)
- [Proibições](#proibições)
- [Checklist Final](#checklist-final)

---

## Princípios Inegociáveis

Estes cinco definem a skill. Violar qualquer um a transforma em teatro de qualidade.

### 1. Oráculo externo — o PRD é alegação, não verdade

A fonte da verdade é o **`00-requisito.md`** e o **app rodando**. O PRD, os ADRs, os CTs e os CT-B são **alegações a serem testadas**.

> **Por quê**: o mesmo agente leu o requisito, escreveu o PRD, escreveu os CTs, implementou e rodou os testes. Se entendeu errado, errou coerentemente cinco vezes e tudo está verde. Validar contra o PRD confirma o erro; validar contra o requisito o expõe.

### 2. Separação de poderes — quem julga não conserta

O quality gate **lê, reproduz e reporta**. Não edita código de aplicação, não edita teste, não relaxa assertion, não "arruma" o PRD.

> **Por quê**: agente que corrige o que acabou de julgar volta a ter cegueira correlacionada, e o relatório perde valor de prova.

**No Claude Code, parte dos princípios 1 e 2 é construção — e só parte.** A `feature-wiki`
despacha esta skill no step 11 como sub-agente `fw-qa-gate` (`opus`), que recebe **só** o path da
wiki, a URL do app, o `git diff --stat` e a base do PR — nunca a conversa que escreveu o `01` e
implementou. O que é construção e o que não é:

- **Edição de arquivo** — construção: o agente não tem `Edit`/`Write`/`NotebookEdit`.
- **`Bash`** — o hook `PreToolUse` do agente (`{skills}/feature-wiki/scripts/guarda-subagente.sh`,
  perfil `qa-gate`) nega o comando que altera a árvore; a cobertura é **heurística** (padrões de
  comando), não construção. Por isso o retorno traz `git status --porcelain` antes e depois, e a
  sessão compara: diferença é violação de contrato (estudo §7.1, T7).
- **Leitura da wiki** — livre por desenho: o gate lê o `03` inteiro (a L6 confere as alegações
  dele), inclusive `## Despachos` e `## Desvios do Plano`, que guardam resíduo da conversa.

A definição do agente é [`agents/fw-qa-gate.md`](agents/fw-qa-gate.md), nesta
skill; o Claude Code só a enxerga depois de `cp .ai/skills/*/agents/*.md .claude/agents/` (uma vez,
e a cada atualização das skills). O relatório volta como texto, entre delimitadores
([passo 6](#6-escrever-06-relatorio-qamd)), e a sessão o grava sem editar. Em qualquer host, o
cabeçalho do `06` declara a linha `Independência:` — `sub-agente fw-qa-gate/{modelo}, sem acesso à
conversa` ou `mesma sessão que escreveu a wiki`. A segunda é modo degradado: o leitor precisa
saber que quem julgou foi quem escreveu.

> **Medido em 2026-09-21** (primeira execução como `fw-qa-gate` cego, feature completa): o juiz
> devolveu `REPROVADO → especificação` com 8 achados — 2 perguntas de requisito que ninguém tinha
> feito (acumulação gestor × diretor; visibilidade de quem já decidiu) e **2 achados contra o
> próprio orquestrador**: a `## Verificação Final` alegava *"88 citações ok"* sem comando que
> reproduzisse o número, e declarava *"sem PCOV / `pest-plugin-mutate` não instalado"* quando
> `php -m` e `ls vendor/pestphp/` provavam o contrário. Nenhum gate anterior tinha como acusar a
> sessão; o juiz cego acusou porque não a viu. A dimensão L ganhou a checagem L6 por isso.

### 3. Convergência — loop com regra de parada

Máximo **3 ciclos**. Ciclo que não traz achado novo encerra. Estourar o teto escala ao usuário.

### 4. Teto por risco — profundidade proporcional

Feature de ajuste sem UI não merece 12 dimensões. O [gate de esforço](#gate-de-esforço-por-risco) decide o escopo **antes** de começar — e o que ele deixa de fora limita o veredito ([teto por cobertura](#veredito-e-teto-por-cobertura)).

### 5. Degradação graciosa — nada é dependência dura

Playwright MCP, skills de `qa-skills`, Pest 5, `pest-plugin-agent`, PCOV ou Xdebug, `pest-plugin-browser`, Boost MCP, app servido: **todos opcionais**. Sem eles a skill roda com menos profundidade e **declara no relatório** o que não pôde ser verificado. O teto `APROVADO COM DÉBITO` vem da **dimensão** que ficou não verificada por falta da ferramenta ([teto por cobertura](#veredito-e-teto-por-cobertura)), não da ausência do opcional: skill do `qa-skills` com fallback inline não rebaixa o veredito, porque a dimensão rodou. Por quê (estudo §7.4, veredito decidível): com as duas regras, 12 dimensões verificadas sem o `qa-skills` davam `APROVADO` por uma e `COM DÉBITO` pela outra. Nunca finge cobertura que não teve.

---

## Quando Invocar

- **Step 11 da `feature-wiki`** — automático, após a revisão do diff (step 9) e a reconciliação (step 10), com os testes verdes, **antes de abrir o PR** e antes de o `03` dizer "concluída"
- Quando o usuário pedir para "validar", "revisar como QA", "conferir se atende ao requisito"
- Antes de abrir PR de feature com superfície de UI ou regra de negócio sensível
- Ao retomar uma feature entregue há tempo, para conferir se ainda atende

### Quando NÃO Invocar

- **Testes vermelhos**: primeiro fazer passar. O quality gate valida o que passa, não substitui o teste
- **Refatoração pequena e interna, já coberta por teste verde** (fora da esteira: não abre wiki). Wiki com `## Natureza da Wiki: refatoração` (a larga) **roda** o gate: a regressão (J) é o que prova que nada mudou
- **Feature sem nenhuma superfície validável** (*Quando pular* do step 11 da `feature-wiki`): o `06` mínimo com `NÃO APLICÁVEL` é escrito pela sessão, sem rodar o gate — veredito do orquestrador, que este gate não emite
- **Antes de implementar**: não é revisão de plano — isso é o step 6 (`/ponytail:ponytail-review`)
- **Como substituto de CT/CT-B**: o gate encontra lacuna; quem prova é o teste versionado

---

## Entradas e Gate de Entrada

| Entrada | Obrigatória? | Sem ela |
|---|---|---|
| `00-requisito.md` com cláusulas `RQ-##` (+ `## Premissas`, `## Perguntas ao Solicitante`) | **sim** | ver "oráculo degradado" abaixo |
| `01-plano-acao.md` (+ `## Natureza da Wiki`, `## Cobertura do Requisito`) | **sim** | não roda — pedir ao usuário |
| `02-decisoes-arquiteturais.md` | se existir | L3 confere só o PRD; `## Superfície Livewire` (dimensão I) via greps da `feature-wiki` (`{skills}/feature-wiki/references/pesquisa-step-3.md`, §*Superfície Livewire — formato e greps*) |
| `03-progresso.md` (+ `## Conformidade com Rules`, `## Verificação Final`, `## Despachos`, `## Revisão do Diff (step 9)`) | **degradável** | L4 e L6 não rodam — vão para "Não Verificado", e o veredito fica no teto; a I roda inteira, com as quatro linhas *eixo 9* (sem registro do step 9) |
| `04-casos-de-teste.md` (+ `## Costuras de Teste`, `## Índice de Cenários`) | sim | não roda |
| `05-casos-de-teste-browser.md` | se alguma costura do `04` é `browser` | dimensões G/H limitadas |
| `07-tickets/` (`feature-tickets`) | se existir | matriz sem a coluna `Ticket` — feature não fatiada |
| `wikis/glossario.md` | se existir | L7 confere só se a feature decidiu termo sem registrá-lo |
| `06-relatorio-qa.md` do ciclo anterior | ciclo ≥ 2 | sem dedupe — achado já rejeitado (destino 5) reaparece |
| `.ai/rules/*.md` (as que o `conformidade-rules.sh` lista), docs de usuário e `CHANGELOG` tocados | para a dimensão L | L4/L5 não rodam — declarar em "Não Verificado" |
| App servido e acessível na `APP_URL` | para dimensões dinâmicas | dimensões B, C, D, E, F, G, H, I ficam estáticas |
| Driver de cobertura (PCOV ou Xdebug) | para a dimensão K medida | K roda só o passo estático; a medição vai para "Não Verificado" |
| Scripts da `feature-wiki` (`{skills}/feature-wiki/scripts/`), desta skill (`{skills}/feature-quality-gate/scripts/`) e, com `07-tickets/`, da `feature-tickets`, com `bash` e `php` | para A (e a coluna `Ticket`), G, K1, L1, L2, L4, L6 | a checagem vai para "Não Verificado" — o gate não reescreve o script à mão |
| Diff da feature (`git diff`) | sim | escopo indefinido |

O `03` é **degradável**, não obrigatório: sem ele o gate ainda confronta `00` × código × app, que é
a razão de existir; o que se perde é declarado e rebaixa o teto do veredito.

**Formas do `04` que não são achado**: no `## Índice de Cenários` a coluna é `Costura` (não mais
`Camada`); linha do checklist de taxonomia com `não se aplica` leva `—` na costura; e
`- RQ-nn — aberta (Qn), sem cenário até a resposta` é a forma esperada de `RQ` aberta.

### Oráculo degradado

Três casos, o mesmo tratamento: o `00-requisito.md` **não existe** (wiki criada antes da
`feature-wiki` 2.10.0); existe **sem nenhuma `RQ-nn`** na `## Decomposição em Cláusulas`; ou foi
**derivado do PRD**. Sinais do terceiro: `## Fonte` aponta o `01` ou nenhuma fonte externa;
`## Texto Original` repete frases do `01`; `git log --diff-filter=A --format='%h %ad' -- {wiki}00-requisito.md {wiki}01-plano-acao.md`
mostra o `00` criado depois do `01`. Por quê (estudo §7.4): com `00` sem `RQ` ou derivado do PRD,
nada disparava o modo degradado, e a dimensão A "passava" contra um oráculo que não era oráculo.

1. **Pedir o requisito original ao usuário** — texto do card, arquivo, o que houver
2. **Nunca derivar o `00` do PRD.** PRD derivado de PRD não é oráculo
3. Se o usuário não tiver o requisito: rodar em **modo degradado**, validando só as dimensões B–L (que não dependem do requisito), e **estampar no topo do relatório**:

```markdown
> ⚠️ ORÁCULO DEGRADADO — {sem 00-requisito.md | 00 sem cláusulas RQ | 00 derivado do PRD: {sinal}}.
> A dimensão A (cobertura do requisito) NÃO foi verificada. Omissão silenciosa não
> pode ser detectada nesta execução. Teto do veredito: APROVADO COM DÉBITO.
```

Modo degradado é resultado honesto. Fingir que a dimensão A rodou é o pior desfecho possível.

---

## Gate de Esforço por Risco

Determinar o escopo **antes** de começar. Três fatores:

| Fator | Valores |
|---|---|
| **Natureza da wiki** (`01`) | nova · evolução · correção · ajuste · refatoração |
| **Superfície de UI** (`01`) | ausente · presente · presente com JS |
| **Criticidade do domínio** | comum · sensível (dinheiro, PII, autorização, integração externa) |

| Perfil | Dimensões a rodar | Ciclos |
|---|---|---|
| **Completo** — UI com JS **ou** domínio sensível, qualquer natureza | A a L (todas), **K com `--mutate`** | até 3 |
| **Mínimo** — `ajuste`, sem UI, domínio comum | A, D, J, **K** (só o passo estático), **L** | 1 |
| **Padrão** — toda combinação fora das duas acima (inclui `ajuste` com UI sem JS) | A, B, C, D, E, F, I, **J** (conforme a natureza), **K** (estático), **L** | até 2 |

> **A dimensão K nunca é pulada por inteiro.** O passo estático dela (procurar teste sem oráculo)
> é grep, custa segundos, e é a checagem com melhor razão achado/esforço da skill inteira. O que
> o perfil decide é se roda também a medição por mutação.
>
> **A dimensão L tampouco é pulada.** É leitura de texto contra código, sem app servido, e a
> defasagem que ela pega foi, no caso medido, 27 dos 31 achados de uma revisão pós-entrega.
>
> **A dimensão J segue a natureza, não o perfil.** Roda em todo perfil quando a natureza não é
> `nova` **ou** quando o campo **Toca infra compartilhada?** do `01` é `sim` ([Regressão Condicional](#regressão-condicional));
> só `nova` com o campo `não` é não aplicável. Até a 1.6.0 o perfil Padrão pulava a J numa wiki de
> evolução que a Regressão Condicional manda regredir, e `correção` sem UI ou `ajuste` com UI sem JS,
> em domínio comum, não cabiam em perfil nenhum — o Padrão agora é o resto, e todo trio tem perfil.

**Dimensão pulada é dimensão declarada.** No relatório, cada dimensão fora do escopo aparece com o motivo (`fora do perfil {X}` / `projeto sem dark mode` / `app não servido`). Nunca omitir em silêncio — omissão silenciosa no relatório de QA é ironia dispensável. E dimensão pulada **limita o veredito**: ver [teto por cobertura](#veredito-e-teto-por-cobertura).

---

## Fluxo de Execução

### 1. Verificar entradas

**Em sub-agente**, o primeiro comando é `git status --porcelain`, guardado para o retorno. Ler `00`, `01`, `04`, `05` (se existir), `03`, `07-tickets/` (se existir) e `wikis/glossario.md` (se existir). Rodar `git diff --stat` para delimitar o escopo. Ler também as rules que o `conformidade-rules.sh` lista (L4), as docs de usuário e o `CHANGELOG` tocados pelo diff, e a tabela `## Conformidade com Rules` do `03` — entradas da dimensão L. Aplicar o gate de entrada; se faltar o `00`, decidir entre pedir ou modo degradado.

### 2. Auditar o requisito ANTES de validar qualquer coisa

Ler `## Perguntas ao Solicitante`, `## Premissas` e a coluna `Estado` da `## Decomposição em Cláusulas` do `00`, e **buscar ambiguidades novas** que passaram batido:

- cláusula não-testável (*"precisa ser rápido"*, *"interface amigável"*) → **achado tipo 1**
- cláusulas contraditórias entre si → **achado tipo 1**
- termo de domínio usado com dois sentidos → **achado tipo 1**
- **`RQ` `aberta — Qn`** → achado destino 1. Nenhum passo nem código a implementa: a pergunta segue aberta e o veredito fica no [teto](#veredito-e-teto-por-cobertura). Algum passo sem `**Bloqueado por**`, ou código do diff, a implementa: **Major**, `REPROVADO → especificação` — o dev respondeu pelo solicitante, que é o que a raia requisito existe para impedir (estudo §2.3). O passo não bloqueado sai do `rastreabilidade.sh` (passo 3); o código, do diff.

> Isto vem primeiro por um motivo prático: se o requisito é ambíguo, validar contra ele produz achado inválido. Ambiguidade é pergunta ao solicitante (raia requisito), não suposição.

### 3. Montar a Matriz de Rastreabilidade

Uma linha por `RQ` e por `P-nn` vigente, mais linhas para o que existe sem nenhum dos dois:

```
RQ/P → passo(s) do PRD → CT → CT-B → ticket → arquivo/classe implementada → resultado
```

Fontes: `## Cobertura do Requisito` do PRD (mapa declarado), `04`/`05` (CTs que citam `RQ`/`P-nn` na origem), `07-tickets/` (coluna `Ticket` só quando a pasta existe), `git diff` (código real). **Conferir o mapa declarado contra a realidade** — PRD que diz "RQ-02 → passo 5" mas o passo 5 não trata disso é achado. Saem da cobrança, como nos scripts: `RQ` `substituída por …` ou `decomposta em …` (as filhas entram), `RQ` citada sem `(parcial)` na coluna `Substitui` de um Adendo, e `RQ`/`P-nn` fora desta entrega no `01` (passo `—` com justificativa, que a A julga).

**Âncora mecânica** (a A deixa de ser só julgamento — estudo §7.4):

1. `bash {skills}/feature-wiki/scripts/rastreabilidade.sh {wiki}` — `RQ` sem passo, `RQ` sem CT, `P-nn` sem CT, passo sem `RQ`, `RQ` aberta implementada por passo não bloqueado. Cada linha é uma célula vazia candidata da matriz.
2. **`git diff` por passo** — para cada passo ligado a um `RQ`/`P-nn`: os arquivos que o passo cita × `git diff --name-only {base}...HEAD`, e o trecho do arquivo em `git diff {base}...HEAD -- {arquivo}`. Arquivo do passo fora do diff, ou diff que não trata do que o passo diz, é achado.
3. **Coluna `Ticket`**, com `07-tickets/`: vem de `bash {skills}/feature-tickets/scripts/indice.sh --check {wiki}`, a fonte única da alocação — `RQ`/`P-nn`, CT e CT-B (CT-B conta como CT) em exatamente um ticket, arestas, fatia vertical. Silêncio = a célula é o ticket que cita o ID; cada linha é achado candidato, destino 1 (nível do plano: ID sem ticket é omissão). Sem o script, "Não Verificado". O gate não refaz a alocação pelos campos dos tickets (estudo §8, item 4: o gate chama scripts, não reescreve greps).

O julgamento que sobra é o que nenhum script decide: se o diff faz o que o `RQ` diz, e se o app se comporta assim.

### 4. Executar as dimensões do perfil

Ordem: **estáticas antes das dinâmicas** (mais baratas, e o resultado pode dispensar as caras); dentro de cada dimensão, **script antes de julgamento** ([Mecânica × julgamento](#mecânica--julgamento)).

### 5. Classificar e rotear cada achado

Severidade + destino, pela [taxonomia](#classificação-e-roteamento). Todo achado precisa de **repro mínima** — achado sem passo-a-passo reproduzível não entra no relatório, vai para "Suspeitas não confirmadas".

### 6. Escrever `06-relatorio-qa.md`

Ver [Arquivo 06](#arquivo-06-relatório-de-qa); antes de escrever, leia o template em [`references/template-06.md`](references/template-06.md). Teto: veredito + achados + matriz. Detalhe longo vai em anexo ou não vai — **em sub-agente**: devolver o `06` inteiro entre uma linha `<<<06` e uma linha `>>>06`, com `## Para o orquestrador` **depois** do `>>>06`; a sessão grava só o que está entre os delimitadores, sem editar, e preenche `## Quality Gate` do `03`. Por quê (estudo §7.4): sem delimitador, `## Para o orquestrador` ia parar dentro do `06`, ou a sessão editava o que devia gravar verbatim.

### 7. Emitir veredito e devolver o controle

Pela tabela de [Veredito e teto por cobertura](#veredito-e-teto-por-cobertura): primeiro o teto (dimensões não verificadas, `RQ` abertas), depois os achados.

### 8. Atualizar o `03-progresso.md`

Registrar: veredito, número do ciclo, achados abertos, débitos aceitos e o débito de cobertura (cada dimensão não verificada, com a causa). O `03` continua sendo o tracking único da feature — **em sub-agente**: devolver como texto; a sessão grava o `06` verbatim e preenche `## Quality Gate` do `03`.

---

## As 12 Dimensões

### Mecânica × julgamento

O gate **roda o script e julga a saída** — não reescreve o grep, nem improvisa um quando o script
falta. Exit 0 = checagem limpa. Exit 1 = cada linha é candidata: confirmada, vira achado com o
comando como repro; rejeitada, fica registrada com o motivo. Exit 2, ou script ausente = a
checagem vai para "Não Verificado" com a mensagem. Por quê (estudo §7.4): metade das dimensões era
opinião sem regra, e L1/L2/L4 reexecutavam num `opus` greps que o step 10 já tinha. **Exceção**:
`dark-mode.sh --mecanismo` — exit 1 = mecanismo detectado (a G roda; as linhas são evidência, não
candidatas a achado), exit 0 = G não aplicável.

| Dim. | Natureza | Instrumento |
|---|---|---|
| A | misto | `rastreabilidade.sh {wiki}` (+ `indice.sh --check {wiki}` com `07-tickets/`) + `git diff` por passo (mecânico); se o diff faz o que o `RQ` diz (julgamento) |
| B | julgamento | sondagem `--agent` com as classes de valor da tabela |
| C | julgamento | matriz papéis × ações montada de policies e rotas × CT; confirmação por `--agent` |
| D | misto | leitura do log real: channel, prefixo, context vazio, PII (mecânico); se os pontos de log bastam (julgamento) |
| E | misto | contagem de queries por `--agent` e `--profile` (mecânico); se a contagem cresce com N (julgamento) |
| F | julgamento | mensagem, estado do formulário e idioma, na tela ou na resposta |
| G | misto | `dark-mode.sh --mecanismo` (exit 1 = G roda) e nível 1 `dark-mode.sh {arquivos}` (mecânico); nível 2 CT-B `inDarkMode()`; nível 3 olho no Playwright MCP (julgamento) |
| H | misto | `assertNoAccessibilityIssues()` (mecânico); teclado e foco (julgamento) |
| I | misto | rota sem `can:` por grep (mecânico); o resto é julgamento, só sobre o que o step 9 não cobriu |
| J | misto | `pest --parallel --tia` e CT/CT-B da ancestral por ID (mecânico); RCRCRC (julgamento) |
| K | misto | K1 `k1-oraculo-fraco.sh` (heurístico; `assertSee` de layout é julgamento); K2 `pest --mutate`, com a plausibilidade julgada |
| L | misto | L1 `ids-ct.sh`, L2 `citacoes.sh`, L6 `checkbox-sem-evidencia.sh` + reprodução dos números (mecânico); L4 misto: `conformidade-rules.sh` lista a rule casada sem linha no `03` (mecânico), aplicada / n.a. / violada é julgamento; L3, L5, L7 julgamento |

Chamada: `bash {skills}/feature-wiki/scripts/{script}` (`rastreabilidade.sh`, `ids-ct.sh`,
`citacoes.sh`, `conformidade-rules.sh`, `checkbox-sem-evidencia.sh`), `bash {skills}/feature-quality-gate/scripts/{script}`
(`dark-mode.sh`, `k1-oraculo-fraco.sh`) ou `bash {skills}/feature-tickets/scripts/indice.sh --check {wiki}`;
uso e exemplo de falha no cabeçalho de cada script.

### A — Cobertura do Requisito (omissão silenciosa)

**O que é**: cláusula `RQ` sem rastro em plano, teste ou código.

**Por que escapa**: CT e CT-B só falham no que foi especificado. `ponytail-review` audita **excesso**, nunca **falta**. TIA mede impacto do diff, não cobertura do requisito.

**Como verificar**: a Matriz de Rastreabilidade do passo 3, com a âncora mecânica (`rastreabilidade.sh` + `git diff` por passo). O formato da lacuna já indica o destino — ver [tabela de padrões](#o-formato-da-lacuna-determina-o-destino).

**Nunca pular.** É a razão de existir da skill.

### B — Fronteiras e Dados

**Como verificar** — sondar com `--agent` (efêmero) ou ler a validação e conferir:

| Classe | Valores |
|---|---|
| Numérico | `0`, `-1`, máximo do tipo, decimal onde se espera inteiro |
| String | vazia, 1 char, 500+ chars, unicode/emoji, espaços nas bordas, HTML/`<script>` |
| Data | `29/02` de ano não-bissexto, timezone, fim de mês, passado onde se espera futuro |
| Coleção | lista vazia, 1 item, N+1 acima do limite paginado |
| Arquivo | 0 byte, extensão trocada, mime falsificado, acima do limite |

```bash
vendor/bin/pest --agent='$r = $this->postJson("/api/x", ["valor" => -1]); dump($r->status(), $r->json());'
```

Achado só existe se o comportamento for **errado**, não apenas diferente do esperado pelo agente.

### C — Matriz de Permissão

**Por que escapa**: o CT de autorização testa **um** papel. A combinação real é papéis × ações.

**Como verificar**: montar a matriz completa e conferir as células que nenhum CT cobre.

| Papel | criar | ver | editar | excluir |
|---|---|---|---|---|
| admin | ✅ CT-03 | ✅ | ✅ | ✅ |
| coordenador | ✅ | ✅ | ⬜ **não testado** | ⬜ **não testado** |
| aluno | ⬜ deve dar 403 | ✅ | ⬜ deve dar 403 | ⬜ deve dar 403 |

Célula não testada em ação destrutiva é **Major**. Confirmar com `--agent` antes de reportar.

### D — Observabilidade Real (log × PRD)

**O achado auto-referente**: a `feature-wiki` **exige** log `[Classe@Método]` com channel dedicado e context estruturado em cada etapa de execução — e nada no ciclo verifica se isso aconteceu.

**Como verificar**:

1. Executar o fluxo principal (via CT, CT-B ou `--agent`)
2. `Read storage/logs/{feature-name}-*.log`
3. Conferir contra o especificado no PRD:

| Checagem | Achado se |
|---|---|
| Channel correto | log caiu no `laravel.log` em vez do channel da feature |
| Formato `[Classe@Método]` | mensagem genérica, sem prefixo |
| Nível por severidade | `catch` que interrompe logado como `info`; `fail()` como `error` |
| Context não-vazio | `Log::info('...')` sem segundo parâmetro |
| Pontos de log | só há log no `catch`; sucesso e decisão de fluxo sem log |
| **PII no context** | CPF, e-mail, senha, token ou cartão em texto claro → **Blocker** |

O último é o mais importante e o menos óbvio: o padrão da skill pede "máximo de contexto", o que aumenta o risco de vazar dado pessoal em log. Verificar sempre.

### E — Performance

**Por que escapa**: CT verde em 3 queries e em 300.

**Como verificar**:

```bash
# N+1: se o projeto não usa preventLazyLoading, sondar contagem de queries
vendor/bin/pest --agent='\DB::enableQueryLog(); $this->get("/rota")->assertOk(); dump(count(\DB::getQueryLog()));'

# CT lento
vendor/bin/pest --profile --filter={Feature}
```

Achado: contagem que cresce com o número de registros (N+1), query sem índice em coluna filtrada, `->get()` onde caberia paginação, job sem `chunk` sobre coleção grande.

### F — UX de Erro

**Por que escapa**: `assertSee('erro')` passa com mensagem inútil.

| Checagem | Achado |
|---|---|
| Mensagem diz **o que** e **como resolver** | "Erro ao processar" em vez de "CPF já inscrito na turma X" |
| Estado do formulário preservado | usuário perde 12 campos digitados ao errar 1 |
| Erro de sistema não expõe interno | stack trace, nome de tabela ou SQL na tela |
| Mensagem no idioma do projeto | string em inglês no meio de UI em português |

### G — Tema e Cor (dark mode)

**Por que escapa — e é o caso mais traiçoeiro**: `assertSee('Salvar')` **passa** com texto branco em fundo branco. O texto está no DOM e na árvore de acessibilidade; só está invisível. E `assertScreenshotMatches()` detecta **mudança**, não erro — em feature nova ele **cria** o baseline, incluindo o bug. (Os demais fatos do plugin sobre tema, cor e acessibilidade, com as ressalvas: `{skills}/feature-test-design/references/pest-plugin-browser.md`, §*Tema, cor e acessibilidade — o que o plugin não prova*.)

> **Atenção — aqui a regra da coletânea se inverte.** Em toda a `feature-wiki` a árvore de acessibilidade é preferida ao screenshot (~200–400 tokens × ~3.000–5.000). **Para defeito de cor, a árvore é justamente cega**: o texto está lá. Cor é o único caso em que a visão ganha da estrutura.

**Primeiro, detectar o mecanismo do projeto**: `bash {skills}/feature-quality-gate/scripts/dark-mode.sh --mecanismo`.
Evidência (exit 1) = G roda, e o mecanismo diz como forçar o tema no CT-B (classe `dark` no
`<html>` × `prefers-color-scheme`). Silêncio (exit 0) = projeto sem dark mode: pular a dimensão
declarando o motivo, com a saída como prova de não aplicável. O script reconhece Tailwind 3
(`darkMode`), Tailwind 4 (`@custom-variant dark`, e o `prefers-color-scheme` padrão quando só há
`dark:`), CSS `prefers-color-scheme`, classe `dark` alternada por JS, Flux e painel Filament.

**Três níveis, do mais barato ao mais caro**:

1. **Estático** (melhor custo-benefício) — `bash {skills}/feature-quality-gate/scripts/dark-mode.sh {arquivos}`, com os arquivos de `git diff --name-only --diff-filter=AM {base}...HEAD`, como na K1 (arquivo apagado no diff sai com exit 2 e derruba o nível inteiro): classe de cor neutra sem par `dark:` do mesmo utilitário na linha, hex fixo em classe arbitrária ou em `style`. Cada linha é candidata (`text-white` sobre fundo colorido é falso positivo).

Também: hex hardcoded fora do arquivo de tokens, e cor semântica trocada (erro em verde, sucesso em vermelho).

2. **Dinâmico via Pest** — vira CT-B novo, versionado:

```php
visit('/rota')->inDarkMode()->assertNoAccessibilityIssues();
```

3. **Visual via Playwright MCP** — screenshot nos dois temas e o agente **olha**: texto ilegível, ícone que desaparece, borda que some, sombra invertida, imagem com fundo branco cravado.

Achado de texto invisível é **Major**: o teste passa e o usuário não vê o botão.

### H — Acessibilidade

`assertNoAccessibilityIssues()` no CT-B cobre o essencial. Além dele: navegação por teclado (tab order), foco após submit/modal, `alt` em imagem informativa, label associado ao input, contraste (o axe reporta como *serious*). O Ponytail **não corta acessibilidade** — achado aqui é legítimo, não preciosismo.

### I — Segurança da Superfície Nova

Escopo: **só o que o diff introduziu** — e só o que a revisão do diff (step 9) não cobriu.

**Antes**: ler `## Revisão do Diff (step 9)` do `03` — achados do `/code-review` e do
`fw-revisor-diff`, com ID, destino, `P-nn` gerada e os rejeitados com motivo. As quatro linhas marcadas
*eixo 9* repetem eixos obrigatórios do `fw-revisor-diff`: com a seção preenchida, **não rodam de
novo**, e o `06` diz "coberto pelo step 9 ({n} achados, {m} rejeitados)". Sem a seção (wiki anterior
à `feature-wiki` 4.0.0, ou vazia), rodam. As demais são o que a I cobre além do step 9, e o `06` as
lista. Por quê (estudo §7.4): a I repetia os eixos do step 9 por outro `opus` cego, minutos depois.

| Checagem | Como |
|---|---|
| **IDOR** — acessar recurso de outro tenant/usuário | `--agent`: autenticar como A e pedir `/x/{id de B}` → deve dar 403/404. A reprodução dinâmica é da I; o step 9 lê o diff |
| Rota sem autorização | `Grep` nas rotas novas por `can:`/`middleware`/`authorize()` |
| Mass assignment | `$guarded = []` ou `fillable` amplo no model tocado |
| Upload | validação de mime **e** extensão, path fora do webroot |
| Dado sensível em resposta | API Resource devolvendo hash de senha, token, campo interno |
| Query com input direto | `DB::raw` concatenando request |
| **Ação do pacote de terceiro com id do cliente** *(eixo 9)* | conferir contra `## Superfície Livewire` do `02`; sem a tabela, rodar os greps do step 3 da `feature-wiki` (`{skills}/feature-wiki/references/pesquisa-step-3.md`, §*Superfície Livewire — formato e greps*). `$wire.mountAction('x', {id: <alheio>})` é ponto de entrada como qualquer rota |
| **Propriedade pública Livewire sem `#[Locked]`** que decide **onde** a escrita cai (id de dono, tenant, agregado) *(eixo 9)* | `Grep "public \$\|public ?"` nas páginas/componentes novos **e** nos do vendor que a feature estende. `#[Session]` não tranca: ele só repõe o valor no `mount()` |
| **Escopo com discriminante nulo** *(eixo 9)* | rodar a query sem tenant/owner resolvido: devolve tudo (falha **aberta**) ou nada (falha **fechada**)? |
| **Estado de erro sem saída** *(eixo 9)* | todo 403/404 novo: existe caminho alcançável a partir dele? par de redirect que se devolve mutuamente é **Blocker** |

Achado de IDOR ou dado sensível exposto é **Blocker**, sempre.

### J — Regressão Adjacente

Só no modo evolução/correção/ajuste/refatoração, ou `nova` que toca infra compartilhada, em qualquer perfil — ver [Regressão Condicional](#regressão-condicional).

### K — Adequação da Suíte (a suíte pega defeito?)

**O que é**: as dimensões A–J perguntam se o **produto** está certo. Esta pergunta se o
**instrumento de medição** presta. Uma feature pode passar em tudo aqui e ainda estar
desprotegida — os testes ficam verdes porque não afirmam nada.

**Por que escapa**: `ponytail-review` audita excesso; a dimensão A audita cobertura do requisito;
nenhuma das duas pergunta se um teste **falharia** diante de uma implementação errada. E cobertura
de linha não responde: com o tamanho da suíte controlado, ela não prevê eficácia de detecção — 100%
de linha é compatível com zero assertion útil.

**Antes dos dois passos**: `Revisão adversarial: NÃO FEITA — {motivo}` no cabeçalho do `04` (host sem
sub-agente) vai para `## Não Verificado` e conta como K não verificada. Por quê (estudo §7.3): a lacuna
declarada substituiu a autorrevisão, e a `feature-test-design` promete que o gate a reporta como débito.

**Como verificar** — dois passos, o segundo só no perfil completo:

1. **K1 — estático, barato** — varrer os testes novos do diff procurando oráculo ausente ou fraco:
   `bash {skills}/feature-quality-gate/scripts/k1-oraculo-fraco.sh {testes}`, com os testes de
   `git diff --name-only --diff-filter=AM {base}...HEAD -- tests/`. O script cobre as linhas da
   tabela, menos `assertSee('{texto de layout}')`: saber se o texto é de layout exige ler a tela, e
   isso fica com o julgamento. Cada linha do script é candidata; a severidade é a da tabela:

| Padrão no teste | Achado |
|---|---|
| teste **sem nenhuma** assertion | Major — não prova nada |
| `assertOk()` / `assertSuccessful()` como assertion única | Major |
| `assertNoJavaScriptErrors()` / `assertNoSmoke()` como assertion única de um CT-B | Major — página em branco e 403 renderizado passam |
| `assertSee('{texto de layout}')` como oráculo do comportamento | Major — o texto do layout aparece em qualquer estado |
| `assertDatabaseHas` só com a chave primária | Minor a Major, conforme a regra |
| "não lança exceção" / `expect($x->count())->toBeInt()` | Minor — tautologia |
| `->not->toBe($outro)` sem valor esperado | Minor — dois resultados errados porém diferentes passam |

2. **K2 — medido** — mutation score nas classes que o diff introduziu:

```bash
XDEBUG_MODE=coverage vendor/bin/pest tests/Feature/{Feature} --mutate --path=app/Services
```

(No Windows, o prefixo de env não roda em cmd/PowerShell: usar o lançador
`{skills}/feature-wiki/scripts/pestw.cmd` (explicado em `{skills}/feature-wiki/references/pest-5.md`),
ou `$env:XDEBUG_MODE='coverage'` no PowerShell.)

Exige driver de cobertura (PCOV ou Xdebug). Escopar sempre: mutar o projeto inteiro é caro e
devolve ruído.

**Plausibilidade do score, antes de lê-lo** (medido em 2026-09-21): no Windows o plugin relança
`argv[0]` (`vendor/bin/pest`, script sh) e o `cmd` não o executa — cada subprocesso morre em
~30 ms com código 1 e o plugin conta como mutante **morto**. Resultado: *206 mutantes, 100 %,
3 s* para uma suíte de 200 s — e este gate, na primeira execução cega, aceitou um *"2 mutantes,
100 %"* sem desconfiar. Regra: **score sem `Duration` compatível com N × tempo dos testes
cobridores, ou sem a lista de sobreviventes, é "Não Verificado"**, não 100 %. No Windows, rodar
pelo `pestw.cmd` acima. Timeout conta como
morto no score; o achado é sempre o **sobrevivente nomeado**.

> **Armadilha verificada**: `covers(X::class)` no arquivo de teste **restringe o que conta como
> coberto**. Mutantes em classe fora do `covers()` são reportados como `uncovered` e o score vai a
> 0%, mesmo com os testes executando aquele código em toda chamada. **`--path=` é o filtro
> verificado** (funcionou nas medições de 2026-09-21); `--class=` é o fallback se a versão
> instalada do Pest não aceitar `--path` — o mesmo conselho da `feature-test-design`.

> **O que este passo NÃO responde — e é o erro mais fácil de cometer com ele.**
> Mutation testing só muta **código que existe**. Cláusula do requisito que nunca virou código não
> gera mutante nenhum, e o score **não cai**. Medido contra a mesma implementação: duas suítes
> reportaram o **mesmo** mutation score (100 % cada, não verificado: Windows, sem `Duration`
> registrada) e detectaram 7 e 12 defeitos plantados de 18 — a métrica não distinguiu as duas
> (números e ressalva:
> <https://github.com/gsferro/laravel-ai-skills/blob/main/experimentos/README.md#materialização-em-pest-rodada-1-cenário-1>).
>
> Portanto: score alto **não** absolve a dimensão A. Score baixo é achado; score alto é apenas
> ausência de um achado específico — o de assertion fraca.

**Como ler o resultado** — cada mutante sobrevivente é um **defeito que ninguém detectaria**, e o
operador diz qual lacuna de derivação o deixou vivo:

| Mutante sobreviveu | Lacuna | Destino |
|---|---|---|
| `>` → `>=` | falta valor limite | **3** |
| `&&` → `\|\|` | falta linha da tabela de decisão | **3** |
| `return $x` → `return null` | oráculo fraco sobre o retorno | **3** |
| chamada removida (e-mail, log, `increment`) | falta assertion de efeito colateral | **3** |
| linha **uncovered** | comportamento sem nenhum teste | **3**, e possivelmente **1** se for `RQ` sem plano |

**Roteamento**: todo achado desta dimensão vai para o **destino 3**, e a correção é invocar a
`feature-test-design` com o mutante como entrada — ela fecha a **classe** de lacuna, não só o caso.

**Piso sugerido**: 70% de mutation score nas classes de regra de negócio da feature — abaixo disso,
**investigar**; o achado registrado é sempre o mutante nomeado que sobreviveu (nunca o percentual).
Sem driver de cobertura, rodar só o passo 1 e declarar o passo 2 em
"Não Verificado" — **depois de provar a ausência** (`php -m | grep -i "pcov\|xdebug"`,
`ls vendor/pestphp/`). A wiki que declara a degradação sem a prova é achado **L6**: em
2026-09-21 as duas declarações estavam lá e as duas eram falsas.

> **Nunca reprovar por cobertura de linha.** O indicador é o mutation score, e o achado é sempre
> um mutante nomeado — não um percentual.

---

### L — Consistência Documental (wiki × código × docs × rules)

**O que é**: as dimensões A–K perguntam se o produto e o instrumento estão certos. Esta pergunta
se o que está **escrito** sobre eles ainda é verdade: PRD, ADR, `04`, docs de usuário, CHANGELOG,
glossário e as rules do projeto, contra o código que foi entregue.

**Por que escapa**: o step 10 da `feature-wiki` manda registrar "Desvios do Plano", e o agente
registra — no `03`. O PRD e a ADR continuam afirmando o que o código não faz, e são eles que a
próxima pessoa lê. Quem escreveu o texto tende a lê-lo como certo, então a autolimpeza do step 10
não basta. Medido numa feature real, depois de o step 10 "concluído": **31 achados** numa revisão
independente, **27 desta dimensão** — PRD e ADR com a guarda antiga, oito IDs de CT só no teste,
citação de vendor 4 linhas fora, consequência invalidada ainda nas docs pt/en e na ADR, e duas
rules do projeto violadas no código novo.

**Entrada**: `00`–`05`, `git diff`, as rules de `.ai/rules/*.md` que casam o diff,
docs de usuário e CHANGELOG tocados, `wikis/glossario.md`, e a tabela `## Conformidade com Rules` do `03` — o que o
implementador **declarou**; esta dimensão confere a declaração.

**Como verificar** — sete checagens, todas estáticas, na ordem de custo:

| # | Checagem | Como | Achado se |
|---|---|---|---|
| L1 | IDs de CT | `bash {skills}/feature-wiki/scripts/ids-ct.sh {wiki} 'tests/**/{Feature}/*.php'` (IDs nos dois sentidos; a forma do padrão está no cabeçalho do script); o dataset × Exemplos do Gherkin e a contagem do cabeçalho do `04` (por `grep -c`) são leitura | ID num lado só; linha de dataset sem Exemplo no Gherkin; contagem do cabeçalho do `04` diferente da real |
| L2 | Citações `arquivo:símbolo:linha` | `bash {skills}/feature-wiki/scripts/citacoes.sh {wiki}`; o formato das citações fica em `{skills}/feature-wiki/references/citacoes-de-codigo.md` | símbolo não está na linha citada; citação sem símbolo |
| L3 | PRD/ADR × código | para cada passo do `01` e cada "Decisão"/"Consequências" do `02`, abrir o arquivo citado e conferir a afirmação. **Toda afirmação com número é conferida por `grep -rn` do valor na wiki inteira**, não lendo o arquivo onde ela é esperada — número duplicado entre `01` e `02` é o que sobrevive ao gate | afirmação que o código contradiz sem marca `*(alterado em …)*`; desvio que existe só no `03`; número certo num arquivo e velho em outro |
| L4 | Rules × diff | `bash {skills}/feature-wiki/scripts/conformidade-rules.sh {wiki} {base}` lista as rules cujo `paths:` casa um arquivo de `git diff --name-only {base}...HEAD` e acusa cada uma sem linha em `## Conformidade com Rules` do `03`. O veredito aplicada / n.a. / violada continua julgamento: para cada rule casada, conferir a linha do `03` **e** o código | rule sem linha na tabela; "aplicada" sem evidência; rule violada (`group` errado, chave de env fora do `phpunit.xml`, par de cenário exigido pela rule ausente) |
| L5 | Docs × comportamento × rastro | docs pt × en × CHANGELOG × README contra o comportamento final; cada frase nova procurada no `00`/`02`. Passo sem `RQ` que o `rastreabilidade.sh` listou é o mesmo crescimento, do lado do plano | pt e en dizem coisas diferentes; consequência invalidada ainda descrita; frase em doc de usuário **sem `RQ` nem ADR** de origem — crescimento sem rastro, o mesmo padrão que a matriz chama de "código sem `RQ`" |
| **L6** | Alegações da `## Verificação Final` e de `## Despachos` do `03` | primeiro `bash {skills}/feature-wiki/scripts/checkbox-sem-evidencia.sh {wiki}` (`[x]` sem ` — {evidência}`); depois, cada `[x]` com **número**: reproduzir o comando que o gera (`grep -c`, `citacoes.sh`, `pest`); cada **degradação declarada** ("sem PCOV", "plugin ausente", "MCP indisponível"): prova negativa (`php -m`, `ls vendor/…`); cada `Duration` de `--mutate`: plausível para N × testes. A coluna `Custo` de `## Despachos` (tokens · duração) é o que o host reportou, e `—` quando ele não reporta: não é número a reproduzir, e `—` não é achado | checkbox fechado sem evidência; célula de `Custo` vazia (nem valor nem `—`); número que nenhum comando reproduz; degradação declarada com a ferramenta presente; score de mutação com duração implausível. **É a checagem que acusa o orquestrador**, e só um juiz que não viu a conversa a faz sem viés |
| **L7** | Vocabulário × glossário | ler `wikis/glossario.md`; para cada termo, `grep -rn` do termo e do "Não confundir com" no `00`, `01`, `04`, `05` e nas views do diff; conferir os termos decididos na feature (resposta de pergunta de significado, `## Decisões de Desenho` do `01`) contra o glossário | termo do `01`/`04`/UI com sentido diferente do glossário, ou trocado pelo "Não confundir com"; termo do `00` com sentido diferente do glossário sem pergunta registrada; termo decidido na feature que não entrou no glossário |

Por que a L7 existe (estudo §2.5, item 4): o glossário é o único lugar durável e global para o
vocabulário do domínio; termo decidido numa feature e escrito diferente na seguinte é o mal-entendido
que o Gherkin e a UI propagam, e quem escreveu a wiki não o vê.

**Severidade e destino**:

| Achado | Severidade | Destino |
|---|---|---|
| L4 rule violada | **Major**; **Blocker** se a rule é de autenticação/autorização ou de fronteira de dados | **2** |
| L4 rule casada sem linha no `03`, ou "aplicada" sem evidência, com o código cumprindo a rule | Minor | **1** — a linha entra no `03` com o veredito e a evidência |
| L3 PRD/ADR contradizendo o código | Major | **1** — corrigir o texto e marcar a data; se a contradição esconder comportamento não pedido, também **2** |
| L1 CT só no teste | Major | **3** — o cenário nasce no `04` via `feature-test-design`, com mutante; teste sem cenário é teste derivado do código |
| L5 frase sem rastro | Major | **1** — vira `P-nn` em `## Premissas` do `00` (com pergunta ao solicitante se estende o pedido) ou sai da doc; Adendo é só pedido do solicitante |
| L5 pt × en divergentes; consequência invalidada ainda descrita | Minor | **1** |
| L2 citação errada | Minor | **1** |
| L6 número irreproduzível | Major | **1** — substituir pela saída real do comando |
| L6 degradação falsa (a ferramenta existe) | Major | **1**, e **3** quando a degradação pulou o passo medido da dimensão K |
| L6 checkbox fechado sem evidência | Minor | **1** — reabrir ou colar a evidência |
| L6 célula de `Custo` vazia em `## Despachos` | Cosmético | **1** — o valor que o host reportou, ou `—` |
| L7 termo do `00` com outro sentido, sem pergunta registrada | Major | **1** — pergunta ao solicitante (raia requisito) |
| L7 termo divergente no `01`/`04`/UI; termo decidido fora do glossário | Minor | **1** — alinhar o texto ao glossário; o termo decidido entra em `wikis/glossario.md` |
| L1 contagem do cabeçalho errada | Cosmético | **1** — recalcular pelos comandos `grep -c` de `{skills}/feature-test-design/references/template-04.md`, §*Contagem do cabeçalho*, nunca à mão |

> **Achado de número: procure a cópia antes de fechar.** Medido: este gate acusou uma contagem
> errada, o orquestrador corrigiu o `01` e a ADR do `02` — que repetia o mesmo número — ficou para
> trás. A defasagem sobreviveu ao gate que existia para pegá-la, porque o gate conferiu o arquivo
> onde esperava a afirmação. Todo achado de L2, L3 ou L6 que envolva um valor sai com o
> `grep -rn "{valor}" wikis/specs/{branch}/{feature}/` colado, e a ação exigida nomeia **todos** os
> arquivos que o repetem.

> **Esta dimensão não corrige nada**, como as outras. Devolve a lista com `arquivo:linha` dos
> dois lados — o texto e o código — e o destino. Quem corrige é o step 10 da `feature-wiki`, na
> volta do loop.

---

## Classificação e Roteamento

### Severidade

| Severidade | Critério | Efeito |
|---|---|---|
| **Blocker** | perda/corrupção de dado, exposição de dado sensível, IDOR, fluxo principal quebrado, cláusula `RQ` não entregue | reprova |
| **Major** | regra de negócio errada em caminho secundário, permissão não validada em ação destrutiva, texto invisível, N+1 em rota de uso frequente, `RQ` `aberta` implementada com a interpretação do dev | reprova |
| **Minor** | mensagem de erro pobre, log fora do padrão, falta de CT para caso coberto por outro | débito |
| **Cosmético** | espaçamento, capitalização, ordem de coluna | débito |

### Veredito e teto por cobertura

**Dimensão verificada** = rodou inteira no escopo dela, ou foi provada **não aplicável** com a
prova no `06` (natureza `nova` com **Toca infra compartilhada?** `não` → J; `dark-mode.sh --mecanismo`
em silêncio → G; sem UI no `01` nem no diff → G e H). **Não verificada**, por qualquer causa = fora
do perfil, rodada em parte (K sem o K2 ou com `Revisão adversarial: NÃO FEITA` no `04`, dimensão
dinâmica rodada estática sem app, checagem com script ausente), sem a ferramenta (MCP, driver de
cobertura), ou a A em oráculo degradado. Skill do `qa-skills` trocada pelo fallback inline não conta.

| Veredito | Condição |
|---|---|
| `APROVADO` | nenhum achado aberto **e** as 12 dimensões verificadas |
| `APROVADO COM DÉBITO` | nenhum Blocker/Major, e ao menos um de: Minor/Cosmético aberto; dimensão não verificada; `RQ` `aberta` que nenhum passo nem código implementa. O débito lista cada achado e cada dimensão com a causa, e vai para o `03-progresso.md` |
| `REPROVADO → {destino}` | ≥ 1 Blocker ou Major, roteado ao destino de maior prioridade |

**`APROVADO` é inalcançável nos perfis Mínimo e Padrão, de propósito**: o teto ali é `APROVADO COM
DÉBITO` por construção (dimensões fora do perfil, K sem o K2), e isso **não bloqueia nada** — é a
declaração honesta do que não foi verificado. Por quê (estudo §7.4, "falso APROVADO por omissão"):
os perfis mínimo e padrão pulavam B, C, E–I e ainda emitiam `APROVADO`; sem app, seis dimensões
ficavam estáticas e nada rebaixava o veredito.

Prioridade de destino quando há vários: **especificação > teste > implementação**. Corrigir código contra especificação ambígua é retrabalho garantido.

### Os 5 destinos

| # | Achado | Diagnóstico | Volta para | O que fazer |
|---|---|---|---|---|
| **1** | requisito ambíguo, incompleto ou contraditório | defeito de **especificação** | escrita da wiki (`00`/`01`/`02`) | requisito: pergunta ao solicitante em `## Perguntas ao Solicitante` do `00` (a `RQ` fica `aberta — Qn`); desenho: decisão do desenvolvedor no `01`/`02`; corrigir a decomposição `RQ` |
| **2** | implementação diverge do PRD | defeito de **código** | execução do passo do PRD | corrigir o código, não o plano |
| **3** | comportamento errado que nenhum CT cobria | defeito de **teste** | `04`/`05` via **`feature-test-design`**, **depois** implementação | invocar a skill com o achado como entrada; escrever o CT que **falha** primeiro; só então corrigir |
| **4** | ambiente, dado, build, seed | não é defeito do produto | infra / setup | nota no `03`; não reprova a feature |
| **5** | comportamento correto, expectativa do gate errada | não é defeito | fecha | registrar o porquê, para não reaparecer no próximo ciclo |

O destino **3** é o mais fácil de errar. **Escrever o teste primeiro não é formalidade**: corrigir antes destrói a prova, e o mesmo defeito volta na feature seguinte sem nada para detectá-lo.

**Achado confirmado que muda o que a feature promete** (comportamento que nenhum `RQ` escreve)
segue o roteamento de achado de revisão da `feature-wiki` (steps 9 e 11), nesta ordem: `P-nn` em
`## Premissas` do `00` — não Adendo, que é só pedido do solicitante —, CT no `04` com `Origem` =
`P-nn`, correção; se contradiz ou estende o pedido, também pergunta ao solicitante; com `07-tickets/`,
ticket novo no fim da numeração. Omissão de `RQ` existente **não** vira `P-nn`: vai ao destino da
tabela abaixo. A `Ação exigida` do achado nomeia essa ordem; quem grava é a sessão, não o gate.

> **O destino 3 não termina no CT que reproduz o achado.** O achado é um **mutante que
> sobreviveu** — então a pergunta seguinte é qual **lacuna de derivação** o deixou vivo (faltou
> valor limite? linha de tabela de decisão? célula da tabela de estados? assertion de efeito
> colateral?). Invocar a `feature-test-design` com o achado: ela fecha a classe inteira, não só o
> caso. Fechar só o caso garante que o vizinho dele volte na próxima feature.
>
> **Lacuna de derivação que já apareceu em outra feature** (o `06` anterior ou o de outra wiki a
> mostra) vira proposta de linha nova no checklist de taxonomia da `feature-test-design`.
> **Candidato a rule** é outra coisa: só o que passa na definição única de *Vale virar rule* da
> `requirement-to-rule` (`{skills}/requirement-to-rule/SKILL.md`, seção *Vale virar rule* —
> durável, não-inferível e com recorrência declarada), e quem decide é o step 12. O gate não tem
> critério próprio: aponta o achado em `## Para o orquestrador`.

### O formato da lacuna determina o destino

A Matriz de Rastreabilidade transforma roteamento em consequência, não opinião:

| Padrão na matriz | Destino |
|---|---|
| `RQ` sem passo no PRD | **1** |
| `RQ` com passo, sem CT | **3** |
| `RQ` com CT verde, mas app se comporta errado | **3** → depois **2** |
| `RQ` com passo e CT, sem código | **2** |
| `P-nn` vigente sem CT | **3** |
| `RQ` `aberta` implementada por passo não bloqueado ou por código | **1** (Major) |
| `RQ`/`P-nn`/CT/CT-B sem ticket, ou em mais de um (linha do `indice.sh --check`) | **1** — omissão no nível do plano |
| Passo / CT / código sem `RQ` nem `P-nn` | **1** (documentar como `P-nn`, com pergunta ao solicitante se estende o pedido) ou remover |
| `RQ` não decomponível | **1** — pergunta ao solicitante |

---

## Convergência do Loop

1. **Teto de 3 ciclos** por feature
2. **Sem achado novo encerra** — ciclo que só reencontra o já registrado no `06` termina o loop
3. **Ao estourar o teto**: parar, escalar ao usuário com a lista de achados abertos, registrar blocker no `03-progresso.md`. Não seguir tentando
4. **Deduplicar contra o `06` anterior**, não contra os achados corrigidos — senão achado rejeitado como tipo 5 reaparece a cada ciclo e o loop nunca fecha
5. **Numerar os ciclos** no relatório (`## Ciclo 1`, `## Ciclo 2`), preservando o histórico

---

## Arquivo 06: Relatório de QA

**Path**: `wikis/specs/{branch}/{feature}/06-relatorio-qa.md`

**Teto**: veredito + achados + matriz. Se passar de ~150 linhas, cortar detalhe, não cortar achado.

**Template**: [`references/template-06.md`](references/template-06.md) — leia antes de escrever. O que ele não pode perder:

- a linha `Independência:` no cabeçalho, uma só;
- a linha `Cobertura:` e o teto aplicado no bloco do veredito;
- a Matriz de Rastreabilidade **só se houver lacuna**, com linhas para `P-nn` e a coluna `Ticket` quando existe `07-tickets/`;
- a tabela de Dimensões com **todas** as 12: verificada, não aplicável (com a prova) ou não verificada (com a causa); na I, o que foi coberto além do step 9; nas dimensões com script, o exit de cada um;
- `## Não Verificado` como débito de cobertura: cada linha é uma dimensão ou caso com a causa, e cada uma é replicada no `03`.

Em sub-agente, o `06` volta entre `<<<06` e `>>>06` ([passo 6](#6-escrever-06-relatorio-qamd)).

---

## Delegação a Skills Externas

Técnica de QA é commodity: a biblioteca **MIT** [`petrkindlmann/qa-skills`](https://github.com/petrkindlmann/qa-skills) (50 skills, Agent Skills Standard) cobre o vocabulário clássico. **Delegar quando disponível; nunca reescrever.** O mapa necessidade → skill → fallback inline está em [`references/delegacao-e-playwright.md`](references/delegacao-e-playwright.md) — leia antes de delegar.

**Instalar apenas as usadas.** `npx skills add petrkindlmann/qa-skills` traz as 50 — 50 descrições competindo pela atenção do agente é inflação de contexto, o mesmo problema combatido nas Project Rules. Copiar só as pastas necessárias para `.ai/skills/`.

Ao delegar, **registrar no relatório** qual skill foi usada. Ao cair no fallback, registrar também — o leitor precisa saber a profundidade da análise. O fallback não rebaixa o veredito: a dimensão rodou ([princípio 5](#5-degradação-graciosa--nada-é-dependência-dura)).

---

## Playwright MCP como Confronto

A `feature-wiki` já fixa a regra: **o `pest-plugin-browser` atesta, o Playwright MCP observa**. Aqui o MCP habilita três confrontos que nenhuma ferramenta de teste faz: inventário de elementos da tela × elementos que o CT-B exercita (a diferença é lacuna de cobertura, mensurável); UI renderizada × `## Superfície de UI` do PRD; e tema, console e rede. O que cada um mede, com o exemplo, está em [`references/delegacao-e-playwright.md`](references/delegacao-e-playwright.md) — leia antes de subir o MCP.

### Regras

- Configuração obrigatória: `--isolated --headless --caps=testing --test-id-attribute=data-testid`
- Só `localhost` / `APP_URL` de desenvolvimento. Apontar para staging ou produção é **proibido**
- `ref=e5` é efêmero (*"valid until the next page change"*) e **nunca** entra em teste — só o resultado de `browser_generate_locator`
- `browser_find` antes de `browser_snapshot` cru
- Proibido `browser_run_code_unsafe` e `--caps=vision`
- **Sessão MCP não é cobertura.** Todo achado do MCP tem dois destinos: lacuna de cobertura → **vira CT-B** no `05`; defeito → **achado roteado**. Nunca "fica no relatório e pronto"
- Antes de subir o MCP, checar se o **`browser-logs` (Browser Logs) do Boost MCP** já resolve o caso de console/erro

Sem MCP: `screenshot()` no ponto de interesse, `content()` filtrado com `Grep`, leitura do Blade/componente. Registrar em "Não Verificado" o que ficou fora.

---

## Regressão Condicional

Lida do `## Natureza da Wiki` do PRD — campos **Tipo** e **Toca infra compartilhada?** —, em qualquer perfil de esforço:

| Tipo | Regressão | O que rodar |
|---|---|---|
| `nova`, infra compartilhada `não` | ❌ | valida só a feature |
| `nova`, infra compartilhada `sim → {o quê}` | ✅ | os três passos abaixo; no passo 2, a "ancestral" é cada feature que consome a infra tocada |
| `evolução` · `correção` · `ajuste` · `refatoração` | ✅ | os três passos abaixo |

Por quê (estudo §7.4, falso `APROVADO`): o `01` da `feature-wiki` diz que infra compartilhada força a
regressão; lendo só o tipo, feature `nova` que mexe em middleware global contava a J como não aplicável.

1. **Impacto medido** (não especulado):

```bash
vendor/bin/pest --parallel --tia
```

2. **CT/CT-B da wiki ancestral, por ID** — ler o `04`/`05` da ancestral e rodar exatamente aqueles testes. O TIA só pega o que tem teste; rodar por ID garante que a evolução não silenciou uma regra antiga.

3. **RCRCRC** nos arquivos que o diff tocou e a ancestral também tocava:

| Letra | Foco |
|---|---|
| **R**ecent | mudou agora |
| **C**ore | fluxo central do sistema |
| **R**isk | onde o defeito dói mais |
| **C**onfiguration | depende de env/config |
| **R**epaired | já foi corrigido antes |
| **C**hronic | quebra com frequência |

Comparar o resultado com a seção `## Impacto em Features Existentes` do PRD: divergência entre previsto e medido é achado (destino 1 — o plano subestimou o impacto).

---

## Proibições

Violação de qualquer uma invalida a execução:

1. **Não alterar código de aplicação.** Nem "só para testar".
2. **Não alterar teste existente.** CT errado é achado de destino 3, não conserto.
3. **Não relaxar assertion.**
4. **Não editar o `00-requisito.md`.** Texto original é imutável; ambiguidade é achado.
5. **Não derivar o `00` do PRD.** Sem requisito original, é modo degradado declarado.
6. **Não reportar achado sem repro mínima.** Vai para "Suspeitas Não Confirmadas".
7. **Não pular dimensão em silêncio.** Toda exclusão é declarada com motivo.
8. **Não aprovar com Blocker ou Major aberto.**
9. **Não seguir além de 3 ciclos.** Escalar.
10. **Não apontar o MCP para staging ou produção.**
11. **Não aceitar número sem comando nem ausência sem prova.** "88 ok" sem o script, "sem PCOV"
    sem `php -m`, "100 %" em 3 segundos — tudo isso é alegação do orquestrador, e a dimensão L6
    existe para conferi-la.
12. **Não emitir `APROVADO` com dimensão não verificada.** O teto é `APROVADO COM DÉBITO`, com cada
    dimensão e a causa.
13. **Não reescrever a checagem de um script.** Rodar o script e julgar a saída; script ausente ou
    exit 2 é "Não Verificado", não grep improvisado.

---

## Checklist Final

### Entrada
- [ ] Em sub-agente: `git status --porcelain` guardado antes do primeiro passo
- [ ] `00-requisito.md` lido; cláusulas `RQ` e `P-nn` vigentes identificadas (ou modo degradado declarado, pelos três casos)
- [ ] `01` lido: `## Natureza da Wiki` e `## Cobertura do Requisito`
- [ ] `04` e `05` lidos; diff delimitado; `07-tickets/` e `wikis/glossario.md` lidos se existirem
- [ ] Rules que casam o diff (`conformidade-rules.sh`), docs de usuário e CHANGELOG tocados lidos; tabela `## Conformidade com Rules` do `03` em mãos
- [ ] Perfil de esforço definido pelo gate de risco; J incluída se a natureza não é `nova` ou se `Toca infra compartilhada?` é `sim`

### Execução
- [ ] Ambiguidades do requisito auditadas **antes** de validar comportamento; cada `RQ` `aberta` classificada (sem implementação → teto; implementada → Major, especificação)
- [ ] Matriz de Rastreabilidade montada e conferida contra a realidade (não só contra o mapa declarado): `rastreabilidade.sh` rodado, `git diff` por passo, linhas `P-nn`, coluna `Ticket` pelo `indice.sh --check` se há `07-tickets/`
- [ ] Todas as dimensões do perfil executadas; as fora do perfil **declaradas com motivo**
- [ ] Scripts rodados, não reescritos: A (+ `indice.sh --check` com `07-tickets/`, fonte da coluna `Ticket`), G (`--mecanismo` e nível 1 com `--diff-filter=AM`), K1, L1 (`ids-ct.sh`), L2 (`citacoes.sh`), L4 (`conformidade-rules.sh`), L6 — exit de cada um registrado; script ausente → "Não Verificado"
- [ ] Dimensão D verificou log real, incluindo **PII no context**
- [ ] Dimensão I leu `## Revisão do Diff (step 9)` do `03` e declarou o que cobriu além dela
- [ ] Dimensão L conferiu IDs de CT, citações, PRD/ADR × código, rules × diff, docs pt × en × CHANGELOG e vocabulário × glossário — inclusive a declaração do `03`
- [ ] **L6**: todo número da `## Verificação Final` reproduzido pelo comando; toda degradação declarada conferida com a prova negativa; `Duration` do `--mutate` plausível
- [ ] Todo achado que envolve um valor numérico sai com o `grep -rn` do valor na wiki inteira — a ação exigida nomeia cada arquivo que o repete
- [ ] Dimensão K: cabeçalho do `04` lido (`Revisão adversarial: NÃO FEITA` → "Não Verificado"); score de mutação só aceito com duração plausível e sobreviventes nomeados — senão "Não Verificado"
- [ ] Dimensão G detectou o mecanismo de tema do projeto antes de validar
- [ ] Achados do MCP convertidos em CT-B novo ou em achado roteado

### Saída
- [ ] Cada achado tem: severidade, dimensão, esperado × observado, repro, evidência, destino, ação exigida
- [ ] Roteamento por prioridade (especificação > teste > implementação)
- [ ] Teto por cobertura aplicado: com dimensão não verificada, o veredito máximo é `APROVADO COM DÉBITO`, e cada dimensão aparece com a causa
- [ ] `06-relatorio-qa.md` escrito, dentro do teto de tamanho — **em sub-agente**: devolvido entre `<<<06` e `>>>06`, com `## Para o orquestrador` depois e o `git status --porcelain` de antes e de depois; a sessão grava o `06` verbatim
- [ ] Seção "Não Verificado" preenchida com honestidade
- [ ] Veredito emitido e registrado no `03-progresso.md` com o número do ciclo — **em sub-agente**: devolvido como texto; a sessão preenche `## Quality Gate` do `03`
- [ ] Nenhuma linha de código de aplicação ou de teste alterada por esta skill

---

## Skills Companheiras

| Skill | Relação |
|---|---|
| `feature-wiki` | produz as entradas (`00`–`03`), os scripts que o gate roda (`{skills}/feature-wiki/scripts/`) e o hook do `fw-qa-gate`, e invoca esta skill no step 11 |
| `feature-test-design` | produz o `04`/`05` que esta skill audita, e **recebe de volta** todo achado de destino 3 — o achado é um mutante que sobreviveu, e a lacuna de derivação é o que precisa fechar |
| `feature-tickets` | produz `07-tickets/` e o `indice.sh --check`, fonte única da alocação a ticket e da coluna `Ticket` da matriz (CT-B conta como CT); o gate não refaz a checagem |
| `requirement-to-rule` | step 12: candidato a Project Rule só pela definição de *Vale virar rule* dela; o gate não tem critério próprio |
| `ponytail` | complementar e não sobreposto: o `ponytail-review` audita **excesso** no plano/diff; o quality gate audita **falta** em relação ao requisito |
| `pest-testing` | materializa o achado de destino 3 em CT/CT-B novo |
| [`qa-skills`](https://github.com/petrkindlmann/qa-skills) (MIT) | técnica de QA delegada — ver [Delegação](#delegação-a-skills-externas) |

> **Caveman**: o `06-relatorio-qa.md` é **boundary** — prosa normal. Achado ambíguo gera correção errada, e o relatório é lido por quem não acompanhou a sessão.
>
> **Motivação e estudo de viabilidade**: ver o [README desta skill](README.md) — pesquisa de mercado, lacuna verificada e critério eliminatório.

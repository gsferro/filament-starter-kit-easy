# Relatório de QA — Host local no final do `kit:install`

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: **completo** — domínio **sensível** (escreve em arquivo de sistema com elevação de privilégio)
> Natureza da wiki: **nova**, mas `## Toca infra compartilhada? sim, parcialmente` → **regressão obrigatória**

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: **1** · Major: **1** · Minor: 0 · Cosmético: **1**
- Ambiente: app **não servido** (feature é comando de console; nenhuma dimensão dependia de HTTP) · Pest 5.1.1 · Playwright MCP: **indisponível**

## Achados

### QA-01 — A branch apagaria a correção de CSS já lançada na v0.37.1 · **Blocker** · destino **4**

- **Dimensão**: L (consistência) / infra
- **Relacionado a**: nenhuma `RQ` — é defeito de **base da branch**, não do produto
- **Esperado**: o PR desta feature acrescenta arquivos; não remove nada de outra entrega
- **Observado**: a base da branch é `38bd86a`, **anterior** ao merge da v0.37.1 (`c99c92d`). `git diff origin/main..HEAD --diff-filter=D` lista **4 arquivos que o PR apagaria**:
  - `tests/Kit/OrdemDasCascadeLayersTest.php` — **a guarda da correção de cascade layer que está em produção**
  - os três arquivos da wiki `fix/css-quebrado-com-tenant`
- **Repro**:
  1. `git merge-base --is-ancestor origin/main HEAD` → falso
  2. `git diff origin/main..HEAD --diff-filter=D --name-only` → os 4 arquivos
- **Evidência**: `merge-base: 38bd86a` × `origin/main: c99c92d`
- **Destino**: **4** (infra) — não é defeito do código da feature
- **Ação exigida**: `git rebase origin/main` **antes** de abrir o PR. Sem isso, mergear esta feature **regride a v0.37.1**

> Pela taxonomia da skill, destino 4 "não reprova a feature". Este caso mostra o limite dessa
> regra: o achado não é do produto, mas **mergear assim desfaz uma correção lançada**. Registrado
> como Blocker de PR, com destino 4 — a classificação é honesta e a consequência é a mesma.

### QA-02 — As quatro cláusulas do Adendo 1 não têm linha na Cobertura do Requisito · **Major** · destino **1**

- **Dimensão**: A (omissão de rastreabilidade) + L3
- **Relacionado a**: RQ-10, RQ-11, RQ-12, RQ-13; `## Adendo 1` do `00`; `## Cobertura do Requisito` do `01`
- **Esperado**: o procedimento de Adendo da `feature-wiki` (passo 2) determina que *"`## Cobertura do Requisito` do `01` ganha as linhas dos `RQ` novos"*
- **Observado**: a tabela do `01` vai de `RQ-01` a `RQ-09`. `grep -cE '^\| RQ-1[0-3] '` no `01` devolve **0**
- **Não é omissão de implementação**: as quatro **têm** CT no `04` (RQ-10: 4 menções, RQ-11: 4, RQ-12: 4, RQ-13: 1) e **têm** código (`MAX_ROTULO = 63`, `MAX_NOME = 253` em `HostLocal.php:94,96`). O rastro existe no `04` e no código, e **falta no mapa**
- **Repro**: `grep -oE '^\| RQ-[0-9]+' 00-requisito.md` → 13 cláusulas; o mesmo grep no `01` → 9 linhas
- **Destino**: **1** (especificação) — corrigir a tabela, não o código
- **Ação exigida**: acrescentar as quatro linhas em `## Cobertura do Requisito`, apontando os passos e os CTs

> **Por que isto importa mais do que parece**: a matriz de rastreabilidade é o instrumento que
> detecta omissão silenciosa. Uma cláusula fora dela é invisível para o próximo ciclo de QA —
> ainda que esteja implementada hoje. O defeito não é a ausência do código; é a **cegueira futura**.

### QA-03 — O doc em inglês mostra o prompt em português sem glosa · **Cosmético** · destino **1**

- **Dimensão**: L5
- **Relacionado a**: RQ-08
- **Esperado**: coerência com o resto de `docs/en/` — nenhum outro doc em inglês transcreve saída de CLI em português (`grep -rlnE "Personalizar o projeto|Nome do projeto" docs/en/` → vazio)
- **Observado**: `docs/en/comecar/instalacao-avancada.md:74-75` traz o bloco literal `Cadastrar um domínio local (ex.: …)? [y/N]` / `Qual domínio? ›`
- **Avaliação honesta**: é **fiel** — o prompt do kit é em pt-BR e é isso que o leitor verá. Não há convenção estabelecida sendo violada, porque não há outro transcript de CLI em `docs/en/`
- **Destino**: **1** — uma glosa entre parênteses ("*Register a local domain…*") resolve
- **Ação exigida**: opcional. Aceitável como débito

## Matriz de Rastreabilidade

<!-- Só as linhas com lacuna. RQ-01..RQ-09 fecham e não vão para o relatório. -->

| RQ | Cláusula | Passo PRD | CT | Código | Resultado | Veredito |
|----|----------|-----------|----|--------|-----------|----------|
| RQ-10 | TLD não reservado exige confirmação extra | **— ausente da tabela** | ✅ `04` | ✅ | ✅ | ⚠️ QA-02 — rastro fora do mapa |
| RQ-11 | A confirmação diz o que vai acontecer | **— ausente** | ✅ | ✅ | ✅ | ⚠️ QA-02 |
| RQ-12 | "Já resolve" = resolve para **loopback** | **— ausente** | ✅ | ✅ | ✅ | ⚠️ QA-02 |
| RQ-13 | Rótulo > 63 e nome > 253 são recusados | **— ausente** | ✅ | ✅ `:94,96` | ✅ | ⚠️ QA-02 |

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | QA-02 — 4 cláusulas fora do mapa (implementadas) |
| B | Fronteiras e dados | ✅ | `MAX_ROTULO = 63` / `MAX_NOME = 253`; CTs de valor limite presentes |
| C | Matriz de permissão | ⏭️ **n.a.** | comando de console, sem papéis nem rota |
| D | Observabilidade real | ✅ | 4 chamadas, todas em `Log::channel('configuracoes')` — o canal que o PRD determinou. **Zero PII**: grep por `email/cpf/senha/password/token` no context devolve vazio |
| E | Performance | ⏭️ **n.a.** | console, sem query e sem request |
| F | UX de erro | ✅ | falha vira aviso **com o comando pronto**, e por SO (achado A4 do step 7.5, corrigido) |
| G | Tema e cor | ⏭️ **n.a.** | sem superfície de UI |
| H | Acessibilidade | ⏭️ **n.a.** | sem superfície de UI |
| I | Segurança da superfície nova | ✅ | **o eixo mais importante desta feature.** Injeção de comando no processo **elevado**: `\A…\z`, charset `[a-zA-Z0-9.-]`, validação nos **dois** pontos, `Process` em forma de array. E o achado A2 foi fechado — `comandoDeElevacao()` usa `caminhoDoHosts()` (`:296`), não `$env:windir` |
| J | Regressão adjacente | ✅ | `## Toca infra compartilhada? sim` → rodada. `composer test:kit` **2.686/2.686, 10.439 asserções**; vizinhos do `.env` **136/136** |
| K | Adequação da suíte | ✅ estático | 63 `expect()`; **zero** oráculo fraco (nenhum `assertOk()` solo, nenhuma tautologia). Falsificabilidade medida: **55 dos 57 reprovam** sem `app/` |
| L | Consistência documental | ⚠️ | L1 ✅ (`diff` de IDs vazio) · L2 ✅ (9/9 citações) · L3 ⚠️ **QA-02** · L4 ✅ (5 rules na tabela do `03`) · L5 ⚠️ **QA-03** |

## Débitos Aceitos

- **QA-03** (Cosmético): glosa em inglês no transcript do prompt — replicar em `03-progresso.md`

## Suspeitas Não Confirmadas

Nenhuma. Os três achados têm repro mínima por comando.

## Não Verificado

- **Dimensão K, passo 2 (mutation score)** — não rodado. O perfil completo pede `--mutate`, e não confirmei driver de cobertura (PCOV/Xdebug) neste ambiente. O passo 1 (estático) rodou e não achou oráculo fraco; a medição fica declarada como **não feita**, não como aprovada
- **A janela real do UAC** — nenhuma suíte cobre, por desenho (o executor é injetado nos 57 CTs). É o único caminho que exige teste manual, e já está registrado como pendência no `03`
- **Playwright MCP** — indisponível nesta sessão; irrelevante aqui (sem UI)

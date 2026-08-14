---
name: feature-quality-gate
version: 1.0.0
description: >
  Etapa de QA dentro do agente — a próxima estação da esteira depois de
  implementar e rodar os testes. Invoque no step 8 da skill feature-wiki, ou
  sempre que precisar validar se uma feature entregue atende de fato ao que foi
  pedido. Confronta 00-requisito.md x 01-plano-acao.md x app rodando, monta a
  Matriz de Rastreabilidade para detectar omissão silenciosa (cláusula que nunca
  virou passo, teste nem código), e valida 10 dimensões que CT e CT-B não cobrem:
  fronteiras, matriz de permissão, log real, N+1, UX de erro, tema/dark mode,
  acessibilidade, segurança da superfície nova e regressão adjacente. Cada achado
  é classificado por severidade e roteado para um de 5 destinos — especificação,
  implementação, teste, infra ou não-defeito. NÃO corrige nada: lê, reproduz e
  reporta em 06-relatorio-qa.md. Loop converge em no máximo 3 ciclos.
---

# Feature Quality Gate — QA no Agente, com Roteamento

## Glossário

| Sigla | Significado |
|-------|-------------|
| **RQ** | Cláusula de requisito — unidade numerada do `00-requisito.md` |
| **Matriz de Rastreabilidade** | Tabela `RQ` → passo do PRD → CT → CT-B → código → resultado. Sigla **RTM** (*Requirements Traceability Matrix*) só em contexto de QA formal / auditoria |
| **CT** | Caso de Teste de backend (`04-casos-de-teste.md`) |
| **CT-B** | Caso de Teste de Browser (`05-casos-de-teste-browser.md`) |
| **PRD** | Plano de ação (`01-plano-acao.md`) |
| **SBTM** | Session-Based Test Management — exploratório com charter e time-box |
| **RCRCRC** | Heurística de regressão: Recent, Core, Risk, Configuration, Repaired, Chronic |

## Índice

- [Princípios Inegociáveis](#princípios-inegociáveis)
- [Quando Invocar](#quando-invocar)
- [Entradas e Gate de Entrada](#entradas-e-gate-de-entrada)
- [Gate de Esforço por Risco](#gate-de-esforço-por-risco)
- [Fluxo de Execução](#fluxo-de-execução)
- [As 10 Dimensões](#as-10-dimensões)
- [Classificação e Roteamento](#classificação-e-roteamento)
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

### 3. Convergência — loop com regra de parada

Máximo **3 ciclos**. Ciclo que não traz achado novo encerra. Estourar o teto escala ao usuário.

### 4. Teto por risco — profundidade proporcional

Feature de ajuste sem UI não merece 10 dimensões. O [gate de esforço](#gate-de-esforço-por-risco) decide o escopo **antes** de começar.

### 5. Degradação graciosa — nada é dependência dura

Playwright MCP, skills de `qa-skills`, Pest 5, PCOV: **todos opcionais**. Sem eles a skill roda com menos profundidade e **declara no relatório** o que não pôde ser verificado. Nunca finge cobertura que não teve.

---

## Quando Invocar

- **Step 8 da `feature-wiki`** — automático, após implementação concluída e testes verdes
- Quando o usuário pedir para "validar", "revisar como QA", "conferir se atende ao requisito"
- Antes de abrir PR de feature com superfície de UI ou regra de negócio sensível
- Ao retomar uma feature entregue há tempo, para conferir se ainda atende

### Quando NÃO Invocar

- **Testes vermelhos**: primeiro fazer passar. O quality gate valida o que passa, não substitui o teste
- **Feature sem superfície validável**: refactor puro sem mudança de comportamento, já coberto por CT verde
- **Antes de implementar**: não é revisão de plano — isso é o step 6 (`/ponytail:ponytail-review`)
- **Como substituto de CT/CT-B**: o gate encontra lacuna; quem prova é o teste versionado

---

## Entradas e Gate de Entrada

| Entrada | Obrigatória? | Sem ela |
|---|---|---|
| `00-requisito.md` com cláusulas `RQ-##` | **sim** | ver "oráculo degradado" abaixo |
| `01-plano-acao.md` (+ `## Natureza da Wiki`, `## Cobertura do Requisito`) | **sim** | não roda — pedir ao usuário |
| `04-casos-de-teste.md` | sim | não roda |
| `05-casos-de-teste-browser.md` | se houver UI | dimensões G/H limitadas |
| App servido e acessível na `APP_URL` | para dimensões dinâmicas | dimensões B, D, F, G, H, I ficam estáticas |
| Diff da feature (`git diff`) | sim | escopo indefinido |

### Oráculo degradado

Se o `00-requisito.md` **não existir** (wiki criada antes da `feature-wiki` 2.10.0):

1. **Pedir o requisito original ao usuário** — texto do card, arquivo, o que houver
2. **Nunca derivar o `00` do PRD.** PRD derivado de PRD não é oráculo
3. Se o usuário não tiver o requisito: rodar em **modo degradado**, validando só as dimensões B–J (que não dependem do requisito), e **estampar no topo do relatório**:

```markdown
> ⚠️ ORÁCULO DEGRADADO — sem 00-requisito.md. A dimensão A (cobertura do
> requisito) NÃO foi verificada. Omissão silenciosa não pode ser detectada
> nesta execução.
```

Modo degradado é resultado honesto. Fingir que a dimensão A rodou é o pior desfecho possível.

---

## Gate de Esforço por Risco

Determinar o escopo **antes** de começar. Três fatores:

| Fator | Valores |
|---|---|
| **Natureza da wiki** (`01`) | nova · evolução · correção · ajuste |
| **Superfície de UI** (`01`) | ausente · presente · presente com JS |
| **Criticidade do domínio** | comum · sensível (dinheiro, PII, autorização, integração externa) |

| Perfil | Dimensões a rodar | Ciclos |
|---|---|---|
| **Mínimo** — ajuste, sem UI, domínio comum | A, D, J | 1 |
| **Padrão** — nova/evolução, sem UI ou UI simples | A, B, C, D, E, F, I | até 2 |
| **Completo** — UI com JS **ou** domínio sensível | A a J (todas) | até 3 |

**Dimensão pulada é dimensão declarada.** No relatório, cada dimensão fora do escopo aparece com o motivo (`fora do perfil {X}` / `projeto sem dark mode` / `app não servido`). Nunca omitir em silêncio — omissão silenciosa no relatório de QA é ironia dispensável.

---

## Fluxo de Execução

### 1. Verificar entradas

Ler `00`, `01`, `04`, `05` (se existir), `03`. Rodar `git diff --stat` para delimitar o escopo. Aplicar o gate de entrada; se faltar o `00`, decidir entre pedir ou modo degradado.

### 2. Auditar o requisito ANTES de validar qualquer coisa

Ler a seção `## Ambiguidades e Perguntas Abertas` do `00` e **buscar ambiguidades novas** que passaram batido:

- cláusula não-testável (*"precisa ser rápido"*, *"interface amigável"*) → **achado tipo 1**
- cláusulas contraditórias entre si → **achado tipo 1**
- termo de domínio usado com dois sentidos → **achado tipo 1**

> Isto vem primeiro por um motivo prático: se o requisito é ambíguo, validar contra ele produz achado inválido. Ambiguidade é pergunta ao usuário, não suposição.

### 3. Montar a Matriz de Rastreabilidade

Uma linha por `RQ`, mais linhas para o que existe sem `RQ`:

```
RQ → passo(s) do PRD → CT → CT-B → arquivo/classe implementada → resultado
```

Fontes: `## Cobertura do Requisito` do PRD (mapa declarado), `04`/`05` (CTs que citam `RQ`), `git diff` (código real). **Conferir o mapa declarado contra a realidade** — PRD que diz "RQ-02 → passo 5" mas o passo 5 não trata disso é achado.

### 4. Executar as dimensões do perfil

Ordem: **estáticas antes das dinâmicas** (mais baratas, e o resultado pode dispensar as caras).

### 5. Classificar e rotear cada achado

Severidade + destino, pela [taxonomia](#classificação-e-roteamento). Todo achado precisa de **repro mínima** — achado sem passo-a-passo reproduzível não entra no relatório, vai para "Suspeitas não confirmadas".

### 6. Escrever `06-relatorio-qa.md`

Ver [template](#arquivo-06-relatório-de-qa). Teto: veredito + achados + matriz. Detalhe longo vai em anexo ou não vai.

### 7. Emitir veredito e devolver o controle

| Veredito | Condição |
|---|---|
| `APROVADO` | nenhum achado Blocker ou Major |
| `APROVADO COM DÉBITO` | só Minor/Cosmético — registrados no `03-progresso.md` como débito |
| `REPROVADO → {destino}` | ≥ 1 Blocker ou Major, roteado ao destino de maior prioridade |

Prioridade de destino quando há vários: **especificação > teste > implementação**. Corrigir código contra especificação ambígua é retrabalho garantido.

### 8. Atualizar o `03-progresso.md`

Registrar: veredito, número do ciclo, achados abertos e débitos aceitos. O `03` continua sendo o tracking único da feature.

---

## As 10 Dimensões

### A — Cobertura do Requisito (omissão silenciosa)

**O que é**: cláusula `RQ` sem rastro em plano, teste ou código.

**Por que escapa**: CT e CT-B só falham no que foi especificado. `ponytail-review` audita **excesso**, nunca **falta**. TIA mede impacto do diff, não cobertura do requisito.

**Como verificar**: a Matriz de Rastreabilidade do passo 3. O formato da lacuna já indica o destino — ver [tabela de padrões](#o-formato-da-lacuna-determina-o-destino).

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

**Por que escapa — e é o caso mais traiçoeiro**: `assertSee('Salvar')` **passa** com texto branco em fundo branco. O texto está no DOM e na árvore de acessibilidade; só está invisível. E `assertScreenshotMatches()` detecta **mudança**, não erro — em feature nova ele **cria** o baseline, incluindo o bug.

> **Atenção — aqui a regra da coletânea se inverte.** Em toda a `feature-wiki` a árvore de acessibilidade é preferida ao screenshot (~200–400 tokens × ~3.000–5.000). **Para defeito de cor, a árvore é justamente cega**: o texto está lá. Cor é o único caso em que a visão ganha da estrutura.

**Primeiro, detectar o mecanismo do projeto**:

```bash
# classe no <html> com toggle, ou prefers-color-scheme puro?
grep -rn "darkMode" tailwind.config.js 2>/dev/null
grep -rn "prefers-color-scheme" resources/css/
grep -rln "class=\"dark\"\|classList.*dark" resources/
```

Se o projeto não tem dark mode: pular a dimensão declarando o motivo.

**Três níveis, do mais barato ao mais caro**:

1. **Estático** (melhor custo-benefício) — classe de cor sem contraparte `dark:` nos arquivos do diff:

```bash
grep -rnE "(bg|text|border)-(white|black|gray-[0-9]{2,3})" {arquivos do diff} | grep -v "dark:"
```

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

Escopo: **só o que o diff introduziu**. Não é auditoria do sistema.

| Checagem | Como |
|---|---|
| **IDOR** — acessar recurso de outro tenant/usuário | `--agent`: autenticar como A e pedir `/x/{id de B}` → deve dar 403/404 |
| Rota sem autorização | `Grep` nas rotas novas por `can:`/`middleware`/`authorize()` |
| Mass assignment | `$guarded = []` ou `fillable` amplo no model tocado |
| Upload | validação de mime **e** extensão, path fora do webroot |
| Dado sensível em resposta | API Resource devolvendo hash de senha, token, campo interno |
| Query com input direto | `DB::raw` concatenando request |

Achado de IDOR ou dado sensível exposto é **Blocker**, sempre.

### J — Regressão Adjacente

Só no modo evolução/correção/ajuste — ver [Regressão Condicional](#regressão-condicional).

---

## Classificação e Roteamento

### Severidade

| Severidade | Critério | Efeito |
|---|---|---|
| **Blocker** | perda/corrupção de dado, exposição de dado sensível, IDOR, fluxo principal quebrado, cláusula `RQ` não entregue | reprova |
| **Major** | regra de negócio errada em caminho secundário, permissão não validada em ação destrutiva, texto invisível, N+1 em rota de uso frequente | reprova |
| **Minor** | mensagem de erro pobre, log fora do padrão, falta de CT para caso coberto por outro | débito |
| **Cosmético** | espaçamento, capitalização, ordem de coluna | débito |

### Os 5 destinos

| # | Achado | Diagnóstico | Volta para | O que fazer |
|---|---|---|---|---|
| **1** | requisito ambíguo, incompleto ou contraditório | defeito de **especificação** | escrita da wiki (`00`/`01`/`02`) | perguntar ao usuário; corrigir a decomposição `RQ` ou abrir ADR |
| **2** | implementação diverge do PRD | defeito de **código** | execução do passo do PRD | corrigir o código, não o plano |
| **3** | comportamento errado que nenhum CT cobria | defeito de **teste** | `04`/`05`, **depois** implementação | escrever o CT que **falha** primeiro; só então corrigir |
| **4** | ambiente, dado, build, seed | não é defeito do produto | infra / setup | nota no `03`; não reprova a feature |
| **5** | comportamento correto, expectativa do gate errada | não é defeito | fecha | registrar o porquê, para não reaparecer no próximo ciclo |

O destino **3** é o mais fácil de errar. **Escrever o teste primeiro não é formalidade**: corrigir antes destrói a prova, e o mesmo defeito volta na feature seguinte sem nada para detectá-lo.

### O formato da lacuna determina o destino

A Matriz de Rastreabilidade transforma roteamento em consequência, não opinião:

| Padrão na matriz | Destino |
|---|---|
| `RQ` sem passo no PRD | **1** |
| `RQ` com passo, sem CT | **3** |
| `RQ` com CT verde, mas app se comporta errado | **3** → depois **2** |
| `RQ` com passo e CT, sem código | **2** |
| Passo / CT / código sem `RQ` | **1** (documentar como requisito implícito) ou remover |
| `RQ` não decomponível | **1** — pergunta ao usuário |

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

```markdown
# Relatório de QA — {Card}: {Título}

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: mínimo | padrão | completo
> Natureza da wiki: {tipo} · Regressão: sim | não

## Veredito — Ciclo {N}

**{APROVADO | APROVADO COM DÉBITO | REPROVADO → especificação/implementação/teste}**

- Blocker: {n} · Major: {n} · Minor: {n} · Cosmético: {n}
- Ambiente: app em `{APP_URL}` · Pest {versão} · MCP: usado | indisponível

## Achados

### QA-01 — {título curto} · {Blocker|Major|Minor|Cosmético} · destino {1-5}

- **Dimensão**: {A-J}
- **Relacionado a**: RQ-02, CT-05, passo 4 do PRD
- **Esperado**: {o que o requisito/PRD determina, com citação}
- **Observado**: {o que o app faz}
- **Repro**:
  1. {passo}
  2. {passo}
- **Evidência**: `{comando executado}` / `storage/logs/...` / screenshot
- **Destino**: {1 especificação | 2 implementação | 3 teste | 4 infra | 5 não-defeito}
- **Ação exigida**: {o que precisa ser feito, em uma frase}

## Matriz de Rastreabilidade

<!-- Só imprimir se houver lacuna. Matriz completa sem buraco não vai para o relatório. -->

| RQ | Cláusula | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|----|----------|-----------|----|------|--------|-----------|----------|
| RQ-01 | {…} | 3, 4 | CT-01 | CT-B01 | `{Classe}` | ✅ | OK |
| RQ-02 | {…} | — | — | — | — | — | ❌ omissão silenciosa |

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ / ⚠️ / ❌ | {n} achados |
| B | Fronteiras e dados | ⏭️ pulada | fora do perfil `mínimo` |
| G | Tema e cor | ⏭️ pulada | projeto sem dark mode |
| … | | | |

## Débitos Aceitos

- QA-04 (Minor): {descrição} — replicado em `03-progresso.md`

## Suspeitas Não Confirmadas

<!-- Sem repro mínima não é achado. Fica aqui para não virar ruído nem se perder. -->

- {descrição} — não reproduzível em {n} tentativas

## Não Verificado

<!-- Honestidade sobre o alcance desta execução. -->

- {dimensão/caso} — motivo: {app não servido | MCP indisponível | sem baseline de screenshot}
```

---

## Delegação a Skills Externas

Técnica de QA é commodity: a biblioteca **MIT** [`petrkindlmann/qa-skills`](https://github.com/petrkindlmann/qa-skills) (50 skills, Agent Skills Standard) cobre o vocabulário clássico. **Delegar quando disponível; nunca reescrever.**

| Necessidade | Skill | Fallback inline se ausente |
|---|---|---|
| Priorizar o que validar | `risk-based-testing` | usar o [gate de esforço](#gate-de-esforço-por-risco) desta skill |
| Explorar o não-especificado | `exploratory-testing` (SBTM, charters) | charter de 1 linha + time-box de 10 min por tela, anotando o que surpreendeu |
| Severidade e root cause | `ai-bug-triage` | tabela de [severidade](#severidade) desta skill |
| Repro mínima | `bug-reproduction` | reduzir passos até o mínimo que reproduz; sem repro → "Suspeitas Não Confirmadas" |
| Avaliar os próprios CT/CT-B | `ai-qa-review` | perguntar de cada CT: "se eu quebrar a regra, ele falha?" |
| Flake × bug real | `test-reliability` | rodar 3× — falha intermitente é flake (destino 4), não defeito |
| Podar suíte na regressão | `test-suite-curation` | só listar CT redundante, sem remover |

**Instalar apenas as usadas.** `npx skills add petrkindlmann/qa-skills` traz as 50 — 50 descrições competindo pela atenção do agente é inflação de contexto, o mesmo problema combatido nas Project Rules. Copiar só as pastas necessárias para `.ai/skills/`.

Ao delegar, **registrar no relatório** qual skill foi usada. Ao cair no fallback, registrar também — o leitor precisa saber a profundidade da análise.

---

## Playwright MCP como Confronto

A `feature-wiki` já fixa a regra: **o `pest-plugin-browser` atesta, o Playwright MCP observa**. Aqui o MCP habilita três confrontos que nenhuma ferramenta de teste faz.

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

### Regras

- Configuração obrigatória: `--isolated --headless --caps=testing --test-id-attribute=data-testid`
- Só `localhost` / `APP_URL` de desenvolvimento. Apontar para staging ou produção é **proibido**
- `ref=e5` é efêmero (*"valid until the next page change"*) e **nunca** entra em teste — só o resultado de `browser_generate_locator`
- `browser_find` antes de `browser_snapshot` cru
- Proibido `browser_run_code_unsafe` e `--caps=vision`
- **Sessão MCP não é cobertura.** Todo achado do MCP tem dois destinos: lacuna de cobertura → **vira CT-B** no `05`; defeito → **achado roteado**. Nunca "fica no relatório e pronto"
- Antes de subir o MCP, checar se o **`Browser Logs` do Boost MCP** já resolve o caso de console/erro

Sem MCP: `screenshot()` no ponto de interesse, `content()` filtrado com `Grep`, leitura do Blade/componente. Registrar em "Não Verificado" o que ficou fora.

---

## Regressão Condicional

Lida do `## Natureza da Wiki` do PRD:

| Tipo | Regressão | O que rodar |
|---|---|---|
| `nova` | ❌ | valida só a feature |
| `evolução` · `correção` · `ajuste` | ✅ | os três passos abaixo |

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

---

## Checklist Final

### Entrada
- [ ] `00-requisito.md` lido; cláusulas `RQ` identificadas (ou modo degradado declarado)
- [ ] `01` lido: `## Natureza da Wiki` e `## Cobertura do Requisito`
- [ ] `04` e `05` lidos; diff delimitado
- [ ] Perfil de esforço definido pelo gate de risco

### Execução
- [ ] Ambiguidades do requisito auditadas **antes** de validar comportamento
- [ ] Matriz de Rastreabilidade montada e conferida contra a realidade (não só contra o mapa declarado)
- [ ] Todas as dimensões do perfil executadas; as fora do perfil **declaradas com motivo**
- [ ] Dimensão D verificou log real, incluindo **PII no context**
- [ ] Dimensão G detectou o mecanismo de tema do projeto antes de validar
- [ ] Achados do MCP convertidos em CT-B novo ou em achado roteado

### Saída
- [ ] Cada achado tem: severidade, dimensão, esperado × observado, repro, evidência, destino, ação exigida
- [ ] Roteamento por prioridade (especificação > teste > implementação)
- [ ] `06-relatorio-qa.md` escrito, dentro do teto de tamanho
- [ ] Seção "Não Verificado" preenchida com honestidade
- [ ] Veredito emitido e registrado no `03-progresso.md` com o número do ciclo
- [ ] Nenhuma linha de código de aplicação ou de teste alterada por esta skill

---

## Skills Companheiras

| Skill | Relação |
|---|---|
| `feature-wiki` | produz as entradas (`00`–`05`) e invoca esta skill no step 8 |
| `requirement-to-rule` | step 9: achado recorrente entre features é candidato a Project Rule |
| `ponytail` | complementar e não sobreposto: o `ponytail-review` audita **excesso** no plano/diff; o quality gate audita **falta** em relação ao requisito |
| `pest-testing` | materializa o achado de destino 3 em CT/CT-B novo |
| [`qa-skills`](https://github.com/petrkindlmann/qa-skills) (MIT) | técnica de QA delegada — ver [Delegação](#delegação-a-skills-externas) |

> **Caveman**: o `06-relatorio-qa.md` é **boundary** — prosa normal. Achado ambíguo gera correção errada, e o relatório é lido por quem não acompanhou a sessão.
>
> **Motivação e estudo de viabilidade**: ver o [README desta skill](README.md) — pesquisa de mercado, lacuna verificada e critério eliminatório.

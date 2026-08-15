---
name: feature-wiki
version: 3.0.0
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
  Após os testes passarem, aciona a skill feature-quality-gate (etapa de QA no
  agente), que confronta 00-requisito x PRD x app rodando. No fim, avalia se
  alguma decisão da wiki deve virar Project Rule do Boost e
  submete a decisão ao usuário (skill requirement-to-rule). Exige consulta à
  Documentation API do Boost (search-docs) para cada stack que o PRD toca.
---

# Feature Wiki — Documentação Antes de Implementar

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
- [Fluxo de Execução](#fluxo-de-execução)
  - [1. Descobrir Branch](#1-descobrir-branch-e-estrutura-de-pasta)
  - [2. Definir Nome da Feature](#2-definir-nome-da-feature)
  - [3. Pesquisa e Contexto](#3-pesquisa-e-contexto-obrigatório-antes-de-escrever)
    - [Documentation API do Boost](#documentation-api-do-boost-search-docs)
  - [4. Criar os Arquivos](#4-criar-os-arquivos)
  - [5. Revisão Profunda Pós-Escrita](#5-revisão-profunda-pós-escrita-obrigatório)
  - [6. Auditoria da Wiki com Ponytail-review](#6-auditoria-da-wiki-com-ponytail-review-obrigatório)
  - [7. Pós-Implementação](#7-pós-implementação-obrigatório)
  - [8. Quality Gate](#8-quality-gate-obrigatório-quando-houver-superfície-validável)
  - [9. Candidatos a Rule](#9-candidatos-a-rule-de-projeto-decisão-do-usuário)
- [Arquivo 00: Requisito](#arquivo-00-requisito--fonte-da-verdade)
- [Arquivo 01: PRD](#arquivo-01-plano-de-ação-prd)
- [Padrão de Log](#padrão-de-log--classeétodo-mensagem)
- [Arquivo 02: ADR](#arquivo-02-decisões-arquiteturais)
- [Arquivo 03: Progresso](#arquivo-03-progresso--tracking)
- [Arquivos 04 e 05: Casos de Teste (delegados)](#arquivos-04-e-05-casos-de-teste--delegados-à-feature-test-design)
  - [Playwright MCP na validação](#playwright-mcp-na-validação-opcional--ferramenta-de-observação)
- [Execução de Testes com Pest 5](#execução-de-testes-com-pest-5)
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
- **Inspecionar APIs de terceiros** antes de escrever CTs — verificar vendor source ou docs oficiais para confirmar nomes de métodos, assinaturas e restrições de schema
- Para features médias/grandes: delegar o mapeamento amplo a um agent `Explore` e depois **confirmar os trechos críticos com `Read` direto** (linhas exatas, imports, assinaturas) — não confiar apenas no resumo do agent
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

### 5. Revisão Profunda Pós-Escrita (OBRIGATÓRIO)

Após escrever os 4 arquivos, **re-validar cada premissa do plano contra o código real** antes de apresentar ao usuário:

- Reler os pontos exatos citados no plano: imports dos arquivos a editar, assinaturas de métodos, relações de models, padrão das migrations-referência, factories/states usados nos CTs
- **Corrigir a wiki imediatamente** quando a revisão contradisser o plano (ex: plano diz "adicionar import X" → import já existe; plano cita guard genérico → padrão real é `! app()->environment('testing')`)
- **Registrar cada correção** em `03-progresso.md` → `## Auditoria Pré-Implementação` → *Revisão profunda*. Correção aplicada e não registrada some: a próxima pessoa refaz a verificação e o histórico não mostra que a premissa original estava errada
- Só então avançar para o step 6 (Auditoria da Wiki)

> Exemplo real (feature/implementar-carga-horaria): a revisão pós-escrita detectou que o import `MbaTrack` já existia no arquivo a editar e confirmou o padrão exato do guard de environment nas migrations com seeder — ambos corrigidos na wiki antes da implementação.

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

> **Importante**: Esta auditoria revisa o **plano** (a wiki), não o código implementado. A auditoria do código implementado acontece no step 7 (Pós-Implementação) e nos templates de Verificação Final.
>
> **Comando correto**: `/ponytail:ponytail-review` (com namespace `ponytail:`). NUNCA usar `/ponytail-review` sem o namespace — o comando não será encontrado.

### 7. Pós-Implementação (OBRIGATÓRIO)

Após a implementação ser concluída e testes passarem:

1. **Atualizar `03-progresso.md`**: marcar todos os checkboxes como `[x]`, adicionar data de conclusão
2. **Adicionar seção "Desvios do Plano"** em `03-progresso.md`: documentar onde a implementação divergiu do PRD e por quê (ex: "Passo 3 alterado: API retornava campo `uuid` em vez de `id` — ajustado mapeamento")
3. **Adicionar seção "Notas de Implementação"** em `03-progresso.md**: descobertas durante o código que não estavam no plano (ex: "Descoberto que `Enrollment::find()` aplica scope global de tenant — documentado em `02-decisoes-arquiteturais.md`")
4. **Preencher o roteiro "Desenhado × Implementado"** em `05-casos-de-teste-browser.md` (se existir): rodar os CT-B, conferir cada linha da tabela `## Superfície de UI` do PRD contra a tela real e marcar ✅/⚠️/❌. Divergências vão para "Desvios do Plano" no `03-progresso.md`
5. **Confirmar impacto real com TIA**: `vendor/bin/pest --parallel --tia` e comparar o que foi marcado como afetado com a seção `## Impacto em Features Existentes` do PRD — divergência é nota de implementação
6. **Linkar wiki ao PR**: incluir link da wiki na descrição do PR para rastreabilidade
7. **Retrospectiva breve**: anotar na wiki o que funcionou bem no planejamento e o que faltou — serve para melhorar futuras invocações da skill
8. **Limpeza de channel de log**: se a feature foi mergeada e está estável, considerar reduzir o level do channel de `debug` para `info` ou remover o channel se não for mais necessário

### 8. Quality Gate (OBRIGATÓRIO quando houver superfície validável)

Após os testes passarem e o step 7 estar concluído, **invocar a skill `feature-quality-gate`**. Este step é função direta da skill — o agente NÃO deve esperar o usuário pedir.

**O que ela faz que os steps 5, 6 e 7 não fazem**: os três tomam o PRD como verdade. O quality gate confronta **`00-requisito.md` × PRD × app rodando** e detecta a classe de defeito que nenhum teste pode pegar — a **omissão silenciosa**: cláusula `RQ` que nunca virou passo, nunca virou CT, nunca virou código. Tudo verde, feature incompleta.

**Entrada que a skill espera**:

- `00-requisito.md` com as cláusulas `RQ-##`
- `01`–`05` da wiki
- app servido e acessível
- `## Natureza da Wiki` do PRD (decide se roda regressão)

**Saída**: `06-relatorio-qa.md` + veredito.

| Veredito | O que o fluxo faz |
|---|---|
| `APROVADO` | segue para o step 9 |
| `APROVADO COM DÉBITO` | segue para o step 9; débito fica registrado no `03-progresso.md` |
| `REPROVADO → especificação` | volta ao step 4: corrigir `01`/`02`, depois reimplementar |
| `REPROVADO → implementação` | volta à execução do passo do PRD indicado |
| `REPROVADO → teste` | volta ao `04`/`05`: escrever o CT que falha **primeiro**, depois corrigir |

**Quando pular**: feature sem nenhuma superfície validável (ex.: só refactor interno já coberto por CT verde) — registrar o motivo no `03-progresso.md`. Não pular por pressa.

> **Teto do loop**: no máximo **3 ciclos** de quality gate por feature. Ao estourar, escalar ao usuário com o que ficou aberto. Ver a skill `feature-quality-gate` para as regras de convergência.

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

**Duas seções com regimes opostos**:

| Seção | Regime |
|---|---|
| `## Texto Original` | **imutável.** Nunca editar, corrigir, resumir ou reordenar |
| `## Decomposição em Cláusulas` | derivada e revisável. Pode ser corrigida se a leitura estiver errada |

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

<!-- Cláusula que não dá para testar como está. Perguntar ANTES de implementar. -->

- **RQ-03**: "{precisa ser rápido}" — sem número não é testável. Qual SLA?
- **RQ-05**: conflita com RQ-02 — {descrever o conflito}

## Fora de Escopo (declarado)

<!-- O que o requisito explicitamente NÃO pede, para o quality gate não acusar omissão indevida. -->

- {item explicitamente fora}
```

> **Wiki antiga sem `00`**: wikis criadas antes da v2.10.0 não têm o arquivo. Ao retomar uma delas, reconstruir o `00` **pedindo o requisito original ao usuário** — não derivar do PRD. PRD derivado de PRD não é oráculo, e o `feature-quality-gate` vai marcar o relatório como *oráculo degradado*.

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
- [ ] `vendor/bin/pest --parallel --tia` (Pest 5 — confirma que nada mais no suite quebrou, rodando só o afetado)
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

**Estrutura**: Seções com checkboxes `- [ ]` agrupadas pelos mesmos passos do `01-plano-acao.md`. Atualizar os checkboxes **em tempo real** durante a implementação — não em lote no final.

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
- [ ] `vendor/bin/pest --parallel --tia` — nada mais no suite quebrou
- [ ] Roteiro "Desenhado × Implementado" do `05-*-browser.md` preenchido <!-- se houver CT-B -->
- [ ] `git commit`

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
| Mutation testing | `pest --mutate` | Features de regra de negócio crítica (cálculo, cobrança) |
| Novos matchers | `toBeEmail()`, `toBeUlid()`, `toBeIpAddress()`, `toBeMacAddress()`, `toBeHostname()`, `toBeDomain()`, `toBeBase64()`, `toBeHexadecimal()` | Substituem regex custom nos CTs — aplicar a escada do Ponytail |

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
- [ ] Dados fornecidos pelo usuário validados contra o DB (quando aplicável)
- [ ] Factories confirmadas (existência + states) para todos os CTs
- [ ] Stack de testes verificado: versão do Pest, `pest-plugin-browser`, Playwright, `APP_URL`, traits em `tests/Pest.php`

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
- [ ] CTs de log incluídos no `04-casos-de-teste.md`

### Validação
- [ ] Revisão profunda pós-escrita executada — premissas do plano re-validadas contra o código
- [ ] **Auditoria da wiki executada** — `/ponytail:ponytail-review` invocado e sugestões aplicadas
- [ ] `03-progresso.md` espelha exatamente os passos do `01-plano-acao.md`
- [ ] Filosofia de Implementação (Ponytail) incluída no PRD
- [ ] Confirmar com usuário se o plano está correto antes de implementar

### Pós-Implementação (após merge)
- [ ] `03-progresso.md` atualizado com checkboxes `[x]` + data de conclusão
- [ ] Roteiro "Desenhado × Implementado" do `05-*-browser.md` preenchido, com divergências replicadas em "Desvios do Plano"
- [ ] Desvios do plano e notas de implementação documentados
- [ ] Wiki linkada no PR
- [ ] Retrospectiva breve escrita
- [ ] Channel de log ajustado (level reduzido ou removido)
- [ ] CT-B escritos e rodados via sub-agente; divergências classificadas (CT errado / implementação divergente / flake)
- [ ] Se o Playwright MCP foi usado: só como observação (`--isolated --headless --caps=testing`), nenhum ref em arquivo de teste, nenhuma sessão MCP registrada como cobertura
- [ ] **`feature-quality-gate` invocado** (step 8) e veredito registrado no `03-progresso.md`
- [ ] Se `REPROVADO`: achado roteado para o destino correto (especificação / implementação / teste) e reciclado
- [ ] Candidatos a rule avaliados nos 4 gates e **apresentados ao usuário** — gravados via `requirement-to-rule` só se aprovados

## Skills Companheiras

A feature-wiki é a primeira estação de uma esteira de skills que cobrem o ciclo completo:

| Camada | Skill | Responsabilidade | Boundary |
|--------|-------|------------------|----------|
| **Comunicação** (agent ↔ usuário) | [Caveman](https://github.com/JuliusBrussee/caveman) — modo padrão `ultra` | Prosa terse — corta ~75% dos tokens removendo fluff, artigos, fillers | **NÃO aplica em arquivos wiki** (00-06), código, commits, PRs |
| **Planejamento** (estrutura de documentação) | feature-wiki | requisito + PRD + ADR + tracking + padrão de log | não deriva caso de teste — testar o próprio plano confirma o plano |
| **Especificação de teste** | `feature-test-design` | deriva o `04`/`05` do **`00-requisito.md`**, com técnica formal e gate de mutantes | não escreve código nem corrige implementação |
| **Execução** (código) | [Ponytail](https://github.com/DietrichGebert/ponytail) | Mínimo código que funciona — escada de simplicidade | Não corta validação, segurança, tratamento de erros |
| **Qualidade** (QA no agente) | `feature-quality-gate` | Confronta `00-requisito` × PRD × app rodando; roteia achado para especificação / implementação / teste | Não corrige nada — só lê, reproduz e reporta |
| **Memória de projeto** (rules) | `requirement-to-rule` | Decisão da wiki vira Project Rule do Boost em `.ai/rules/` | Só o que é específico da aplicação; ecossistema é guideline do Boost |

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

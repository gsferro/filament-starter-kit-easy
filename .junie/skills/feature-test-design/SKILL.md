---
name: feature-test-design
description: >
  Deriva casos de teste que matam defeito a partir do requisito (00-requisito.md) —
  não do plano e nunca do código — e escreve o 04-casos-de-teste.md e, só quando a
  asserção depende do navegador, o 05-casos-de-teste-browser.md. Pipeline: perfil de
  risco, varredura SFDIPOT, mapa de regras, técnica formal por regra, taxonomia de
  defeito, cenários em Gherkin pt-BR e gate de falsificabilidade por mutantes,
  fechado com pest --mutate e revisão adversarial. Invoque no step 7 da feature-wiki,
  antes de implementar; quando um achado de revisão virar premissa P-nn ou o
  feature-quality-gate rotear um achado para o destino 3 (teste); ao escrever o teste
  de regressão de um bug de produção; para cobrir código legado sem wiki; quando
  pest --mutate deixar mutante sobrevivente.
  Palavras-chave: casos de teste, CT, CT-B, costuras de teste, Gherkin, mutantes, pest --mutate,
  partição, valor limite, tabela de decisão, estado × evento, IDOR, idempotência,
  superfície Livewire, Filament, Pest, Laravel.
license: MIT
compatibility: >
  Projeto Laravel com Pest; entrada obrigatória: o 00-requisito.md da feature-wiki
  (sem ele a skill para e pede). pest --mutate exige pest-plugin-mutate e PCOV ou
  Xdebug; ausência só com prova negativa; no Windows, o score só vale pelo lançador
  .cmd. CT-B exigem pest-plugin-browser, Playwright e npm run build. Revisão
  adversarial exige sub-agente (Claude Code: fw-adversario-ct em .claude/agents/, ou
  general-purpose com model opus); sem ele, lacuna declarada no 04, nunca
  autorrevisão.
metadata:
  version: "1.16.0"
  requires: "feature-wiki>=4.0.0"
---

# Feature Test Design — Do Requisito ao Caso de Teste que Mata Defeito

## Glossário

| Sigla | Significado |
|-------|-------------|
| **RQ** | Cláusula de requisito — unidade numerada do `00-requisito.md`. `RQ` `aberta — Qn` espera resposta do solicitante e não tem cenário |
| **P-nn** | Premissa de `## Premissas` do `00`: o que a feature assume sem o solicitante ter escrito (nasce de achado confirmado de revisão). Origem de CT como a `RQ` |
| **Costura** | Onde um grupo de CT se prende ao sistema: `unit de regra` · `Pest feature HTTP` · `componente Livewire/Filament` · `browser`. **`Pest feature HTTP`** = teste em `tests/Feature` com a aplicação de pé — por HTTP **ou** chamando action/service/model direto ("por fora do componente") |
| **Regra** | Uma regra de negócio verificável extraída de uma ou mais `RQ`. É o `Regra:` do Gherkin |
| **CT** | Caso de Teste — um `Cenário:` do Gherkin, com ID |
| **CT-B** | Caso de Teste de Browser |
| **EP** | Equivalence Partitioning — particionamento de equivalência |
| **BVA** | Boundary Value Analysis — análise de valor limite |
| **SFDIPOT** | Structure, Function, Data, Interfaces, Platform, Operations, Time |
| **Mutante** | Implementação errada plausível. O CT que "mata" o mutante é o que falharia se ela existisse |
| **MSI** | Mutation Score Indicator — % de mutantes mortos (`pest --mutate`) |
| **`{skills}`** | Diretório onde as skills estão instaladas: o primeiro dos três — `.ai/skills/` (Boost), `.claude/skills/` (espelho local), `~/.claude/skills/` (global) — que **contém a skill citada** (`.ai/skills/` pode existir sem ela). É a resolução do hook `guarda-subagente.sh` |

## Índice

- [Princípios Inegociáveis](#princípios-inegociáveis) · [Quando Invocar](#quando-invocar) · [Entradas e Gate de Entrada](#entradas-e-gate-de-entrada)
- [O Pipeline de Derivação](#o-pipeline-de-derivação): [0 perfil](#passo-0--perfil-de-esforço-por-risco) · [1 SFDIPOT](#passo-1--varredura-sfdipot) · [2 mapa de regras](#passo-2--mapa-de-regras-example-mapping) · [3 técnica](#passo-3--técnica-formal-por-regra) · [4 taxonomia](#passo-4--checklist-de-taxonomia-de-defeito) · [5 Gherkin](#passo-5--escrever-os-cenários-em-gherkin) · [6 gate](#passo-6--gate-de-falsificabilidade-obrigatório) · [7 camada e poda](#passo-7--alocar-camada-e-podar) · [Precedência: Project Rule](#precedência-project-rule-do-projeto-vence-a-skill)
- [Escolha de Camada](#escolha-de-camada-em-laravelfilament) · [Arquivo 04](#arquivo-04-casos-de-teste) · [Arquivo 05](#arquivo-05-casos-de-teste-de-browser--condicional) · [Armadilhas de API](#armadilhas-de-api-que-invalidam-ct) · [Mutation Testing](#fechamento-do-ciclo-com-mutation-testing) · [Revisão Adversarial](#revisão-adversarial-obrigatória-no-perfil-completo-ou-com-impacto-3)
- [Proibições](#proibições) · [Checklist Final](#checklist-final) · [Skills Companheiras](#skills-companheiras)

### References — cada passo diz qual abrir, antes da ação

| Arquivo | Passo | Conteúdo |
|---|---|---|
| [`tecnicas-por-regra.md`](references/tecnicas-por-regra.md) | 3 | desenvolvimento, tabelas e exemplos das regras de execução |
| [`taxonomia-de-defeito.md`](references/taxonomia-de-defeito.md) | 4 | tabela gatilho × cenário obrigatório |
| [`gherkin.md`](references/gherkin.md) | 5 | estrutura `Funcionalidade` → `Regra` → `Cenário`, exemplos de `Esquema do Cenário` |
| [`template-04.md`](references/template-04.md) | 2, 6, 7, arquivo 04, pós | template do `04`, formato ❓/➡️ das perguntas, costuras de teste, tabela de mutantes, cogitado e cortado, contagem por `grep -c`, teste de arquitetura de IDs |
| [`escolha-de-camada.md`](references/escolha-de-camada.md) | 2 (costuras), 7 | cenário → camada → API; camada → valor de `Costura`; helpers `@deprecated` do Filament |
| [`template-05.md`](references/template-05.md) | arquivo 05 | template do `05` |
| [`pest-plugin-browser.md`](references/pest-plugin-browser.md) | arquivo 05 | fatos do plugin, comando, seletores, tema e cor — fonte única da coletânea |
| [`armadilhas-de-api.md`](references/armadilhas-de-api.md) | 5, 7, arquivo 04 | fakes, assertions e helpers que invalidam CT |
| [`mutation-testing.md`](references/mutation-testing.md) | 6, pós | operadores, comandos, lançador do Windows, tradução do sobrevivente |
| [`revisao-adversarial.md`](references/revisao-adversarial.md) | revisão | resumo do contrato do `fw-adversario-ct` |
| [`casos-medidos.md`](references/casos-medidos.md) | qualquer | o caso que motivou cada regra |

---

## Princípios Inegociáveis

Cinco. Violar qualquer um devolve a skill ao problema que ela existe para resolver: teste que
executa o código, fica verde e não prova nada.
A evidência de cada princípio está em [`references/casos-medidos.md`](references/casos-medidos.md).

### 1. O caso de teste deriva do **requisito**, nunca do código

Fonte primária é o `00-requisito.md`. O PRD (`01`) entra só para nomes, paths e superfície —
**nunca** como fonte do comportamento esperado.

**Corolário: log não é cláusula** — só vira cenário quando o requisito pede trilha de auditoria, e
então é `RQ` (Proibição 12).

### 2. Cenário sem mutante morto não é caso de teste

Toda `Regra:` declara as implementações erradas plausíveis e aponta qual cenário falharia
diante de cada uma. Mutante sem matador é **lacuna declarada**, não detalhe.

### 3. A camada mais barata que prova

Cada cenário roda no nível mais barato capaz de falsificá-lo: `Unit` < `Feature` (HTTP) <
componente Livewire/Filament < `Browser`. Browser só quando a asserção depende de **JavaScript
executado, pixel ou acessibilidade**.

### 4. Regra antes de exemplo

Primeiro enumeram-se as **regras** (eixo de cobertura), depois se cobre cada regra com
exemplos. Escrever cenário direto produz variações do mesmo eixo e buracos nos outros.

### 5. Ambiguidade é pergunta, não caso inventado

Regra que o requisito não determina não vira cenário com valor chutado: vira pergunta ❓/➡️ com
raia (formato em `references/template-04.md`) — **requisito** (o que o sistema faz; só o
solicitante responde) ou **desenho** (como implementar; o desenvolvedor responde). A sessão leva as
de requisito a `## Perguntas ao Solicitante` do `00`; em sub-agente, elas voltam como **saída**,
numeradas `Q?1, Q?2…` — provisório: o `Qn` é uma sequência só da feature, nas três raias, e a
sessão renumera ao gravar.

**`RQ` aberta não tem cenário**: com `Estado` `aberta — Qn` no `00`, ou citada em `afeta:` de
pergunta de requisito da derivação, ela entra no `04` como
`RQ-nn — aberta (Qn), sem cenário até a resposta` — nenhum passo do `01` a implementa, e cenário
sobre a suposição é requisito inventado com cara de confirmado ([estudo 2026-09-26](https://github.com/gsferro/laravel-ai-skills/blob/main/estudos/2026-09-26-agentskills-spec-to-spec-to-tickets.md) §2.3).
**Premissa de comportamento** (o que o sistema faz quando o texto não diz) é pergunta da raia
requisito, e a **➡️ não é livre**: recomenda
[falha fechado](references/tecnicas-por-regra.md#premissa-escopo-apaga-mecanismo-escolhe-comportamento-falha-fechado)
— a mesma regra da entrevista do step 4 da `feature-wiki` —, e o invariante das duas leituras, de
cláusula fechada, vira cenário já. Premissa de **mecanismo** (raia desenho) não bloqueia: fixa o
mecanismo do cenário, marcado `@premissa`.

---

## Quando Invocar

- **Step 7 da `feature-wiki`**, depois do `00`/`01`/`02` e da auditoria Ponytail (step 6), e **antes** de implementar
- Quando um achado confirmado de revisão (steps 9 e 11 da `feature-wiki`) virar `P-nn` em `## Premissas` do `00`: o CT com origem `P-nn` nasce aqui, antes da correção
- Quando o `feature-quality-gate` rotear um achado para **destino 3 — teste** ("escrever o CT que falha primeiro")
- Ao escrever o teste de regressão de um **bug encontrado em produção**
- Para cobrir **código legado** sem wiki (nesse caso a entrada é o comportamento acordado com o usuário, não o código)
- Quando `pest --mutate` deixar mutante sobrevivente
- Wiki de **refatoração larga** (`## Natureza da Wiki: refatoração`, step 7): além dos CT das `RQ`, que ficam verdes no contract, derivar os CT de **transição** — escrita dupla, um por lote de leitores, remoção do antigo —, com origem na `RQ` da refatoração; a `feature-tickets` os aloca em expand, migrate e contract

### Quando NÃO invocar

- Sem requisito nem comportamento acordado — sem oráculo não há derivação, só transcrição do código
- Para **corrigir** implementação: esta skill escreve especificação de teste, não conserta produto
- Ajuste cosmético, bump de dependência, refatoração pequena e interna já coberta por teste verde

---

## Entradas e Gate de Entrada

| Entrada | Obrigatória | Sem ela |
|---|---|---|
| `00-requisito.md` com cláusulas `RQ-##` e o `Estado` de cada uma, `## Premissas` (`P-nn`) e `## Perguntas ao Solicitante` | **sim** | pedir ao usuário. Nunca derivar do PRD |
| `wikis/glossario.md` — vocabulário do domínio decidido nas features | se existir | o Gherkin usa o termo literal do `00`, sem sinônimo inventado |
| `01-plano-acao.md` — `## Superfície de UI`, rotas, paths, stack | sim (no fluxo da wiki) | fora do fluxo da wiki, perguntar a superfície |
| `02-decisoes-arquiteturais.md` — `## Superfície Livewire` | **sim**, sempre que a feature cria página, widget ou componente (pacote de terceiro é uma das origens, não a condição) | a superfície que o cliente realmente alcança fica fora do inventário — e é onde o defeito mora |
| `.ai/rules/` do projeto | se existir | herdar convenção pelo código de teste existente |
| `tests/Pest.php` + 1-2 testes existentes | sim | não saber os helpers e traits do projeto |
| Versões: Pest, Filament, Livewire, Laravel | sim | gerar API de versão errada |

**Gate**: sem `00-requisito.md`, **parar e pedir**. Derivar do plano reintroduz exatamente a
cegueira correlacionada que a separação de arquivos existe para quebrar.

> **Regra de higiene de contexto**: ao derivar, **não leia a implementação da feature**
> (ela normalmente nem existe). Ler implementação similar de outra feature é permitido apenas
> para herdar *convenção de teste* (helpers, traits, seletores) — nunca para inferir
> comportamento esperado.

### A fronteira com o plano é escorregadia — registre-a

"O PRD entra só para paths e superfície" é fácil de enunciar e difícil de aplicar, porque muita
coisa é **simultaneamente** superfície e comportamento: o rótulo de um campo na tela, o nome de um
método (`valido()`, `aplicarEm()`), as colunas da trilha de auditoria, as strings de status.

A regra que resolve caso a caso:

| O item vem do PRD e… | Pode virar `Então`? |
|---|---|
| o requisito **também** o determina | **sim** — a fonte é o requisito, o PRD só deu o nome |
| só o PRD o determina, e é **escolha de implementação** (nome de método, de coluna, de classe) | **não** — vira detalhe do cenário, nunca oráculo |
| só o PRD o determina, e é **comportamento visível ao usuário** (texto de erro, rótulo, ordem) | **não** — e é **achado**: o requisito está incompleto. Registrar como pergunta |

**Cuidado com o valor que o plano parametrizou.** *Onde* um número mora (`config()`, coluna,
constante) é escolha de implementação e não vira oráculo — mas **o número em si, quando está no
requisito, é cláusula**. Injetar o limite por `config()->set()` em *todos* os cenários deixa o
único valor literal do card sem nenhum teste, e qualquer default errado passa. Ao menos um cenário
usa o **valor do requisito**, escrito literalmente.

E esse cenário **não pode depender do ambiente de teste**: `Dado a configuração de fábrica, sem
ajuste do teste` é vácuo se o `phpunit.xml` ou o `.env.testing` definirem a chave — o cenário passa
medindo o ambiente, e o default errado sobrevive sem nada ficar vermelho. O `Dado` afirma o
**valor efetivo lido**, e o `Então` usa o número do requisito.

Registrar isso não é burocracia: sem uma seção `## Fronteira com o Plano` listando **o que foi
recusado como oráculo e por quê**, metade dos cenários vira teste do PRD sem ninguém perceber —
que é exatamente o defeito que esta skill existe para evitar.

### Quando o `00-requisito.md` é somente leitura

A skill obriga a devolver as perguntas novas de raia requisito para `## Perguntas ao Solicitante` do `00`. Há casos em que isso
não é possível: o `00` está fechado para edição, pertence a outra branch, ou está sendo usado como
linha de base de comparação.

Nesse caso: escrever as perguntas no próprio `04`, numa seção
`## Perguntas para o 00-requisito.md`, **em bloco pronto para colagem** (mesmo formato da seção de
destino), e **declarar o desvio** em uma linha. A pergunta continua bloqueando o que depende dela:
a `RQ` que ela afeta é tratada como aberta.
O que não pode acontecer é a pergunta morrer porque o arquivo de destino estava travado.

---

## O Pipeline de Derivação

Oito passos (0 a 7), em ordem. Os passos 3 e 4 são onde nasce a cobertura; o 6 é onde ela é auditada.

### Passo 0 — Perfil de esforço por risco

Antes de derivar qualquer coisa, pontuar **Probabilidade × Impacto** por área da feature.

| Fator | 1 | 2 | 3 |
|---|---|---|---|
| **Probabilidade** | código novo isolado, regra simples | integra com 1 componente existente | concorrência, integração externa, migração de dado, regra com muitas condições |
| **Impacto** | cosmético, reversível | retrabalho manual | dinheiro, dado de terceiro, autorização, irreversível, compliance/LGPD |

| P×I | Perfil | O que rodar do pipeline |
|---|---|---|
| 1–3 | **mínimo** | passos 1, 2 (com as costuras), 5, 6 — técnica só EP; 1 cenário por regra |
| 4–6 | **padrão** | passos 1–7, sem pairwise; BVA 2-valores; taxonomia só nos itens aplicáveis |
| 7–9 | **completo** | passos 1–7 integrais; BVA 3-valores; tabela de decisão completa; 100% das células inválidas da tabela de estado; revisão adversarial |

**Perfil declarado no cabeçalho do `04`.** Áreas diferentes da mesma feature podem ter perfis
diferentes — o cálculo do desconto é `completo`, a listagem é `mínimo`.

**A área é o que recebe o perfil; a regra é o que recebe a técnica.** Como só o passo 2 produz as
regras, o mapeamento **área → regra** é preenchido no `## Mapa de Regras`, e cada regra herda o
perfil da sua área. Regra que atravessa duas áreas herda o **maior** perfil.

**Escalar a técnica é permitido; rebaixar não.** Se a regra exige uma técnica mais forte do que o
perfil da área prevê — o caso clássico é uma regra de arredondamento numa área `padrão`, onde
BVA 2-valores não distingue truncar de arredondar — **use a técnica mais forte e escreva por quê
em uma linha**. O perfil é orçamento, não teto de rigor: ele controla *quantos* cenários, não
*quão cega* é a técnica.

**Gatilho da revisão adversarial: perfil completo em qualquer área, OU Impacto 3 em qualquer
área**, mesmo com P×I ≤ 6. O P×I decide quantos cenários; a adversarial não é por área — o
sub-agente recebe o `04` inteiro, e o achado cai onde cai. A saída da revisão declara quais áreas e
regras percorreu (caso que motivou o gatilho: `references/casos-medidos.md` §Passo 0).

### Passo 1 — Varredura SFDIPOT

Sete perguntas, uma tabela, **antes** de escrever qualquer cenário. Custo baixíssimo e ataca a
causa real do "cobre alguns erros e outros passam": o que escapa quase nunca é um caso a mais
na dimensão já pensada — é uma **dimensão inteira esquecida**.

| Letra | Pergunta para esta feature | Se vazio |
|---|---|---|
| **S**tructure | que artefatos a feature cria/toca? (model, migration, action, job, policy, resource, command, config) | declarar |
| **F**unction | que funções ela executa? cálculo, fluxo, erro, segurança, função administrativa escondida | declarar |
| **D**ata | que dados entram, saem e já existem? cardinalidade, dado nulo, dado grande, **dado de outro tenant**, dado temporal | declarar |
| **I**nterfaces | por onde se chega até ela? UI, rota HTTP, comando artisan, job, webhook, import, API | declarar |
| **P**latform | de que depende? versão de PHP, banco (colação/case-sensitivity), Redis, fila, storage, navegador | declarar |
| **O**perations | como será usada de verdade? perfis de usuário, volume, uso indevido, ambiente | declarar |
| **T**ime | como o tempo afeta? concorrência, ordem, timeout, agendamento, DST/timezone, expiração, `updated_at` | declarar |

**Dimensão vazia é dimensão declarada**, com o motivo. "Não se aplica" escrito é aceitável;
silêncio não é.

### Passo 2 — Mapa de Regras (Example Mapping)

Converter as cláusulas `RQ` fechadas e as `P-nn` vigentes em **Regras** verificáveis — a origem
aceita `P-nn` como `RQ-nn`, porque achado confirmado de revisão vira premissa no `00` e CT antes da
correção. Uma `RQ` pode gerar várias regras; uma regra pode atender várias `RQ`.

| Cartão | O que é | Onde vai |
|---|---|---|
| 🟦 **Regra** | critério de aceite verificável | vira `Regra:` no `04` |
| 🟩 **Exemplo** | caso concreto que ilustra a regra | vira `Cenário:` no `04` |
| 🟥 **Pergunta** | o requisito não determina | vira ❓/➡️ com raia ([princípio 5](#5-ambiguidade-é-pergunta-não-caso-inventado)); a de requisito vai, pela sessão, a `## Perguntas ao Solicitante` do `00` |

Sinais de leitura do mapa, antes de seguir:

- **Muita pergunta vermelha** → o requisito não está maduro. Escalar ao usuário antes de derivar
- **Regra sem nenhum exemplo** → regra não é verificável como está; reescrever ou virar pergunta
- **Exemplo sem regra** → ou existe uma regra implícita não escrita (achado), ou o exemplo é ruído

> Só regras e exemplos entram no arquivo de casos de teste. Perguntas e história ficam fora —
> mas perguntas **bloqueiam** o que dependem delas.

#### Costuras de teste — fecham o passo 2, antes do primeiro cenário

Em todo perfil, declarar `## Costuras de Teste` no `04` (template em `references/template-04.md`;
camada → `Costura` em `references/escolha-de-camada.md`): uma linha por grupo, `Costura` do
[Glossário](#glossário), **existente > nova** (a nova diz por quê). A proposta sai do que a regra
(a `RQ`/`P-nn`) afirma, pela escada do [item 0 do passo 7](#passo-7--alocar-camada-e-podar) — não
do `Então`, que ainda não existe. A derivação **propõe**; a sessão **confirma** com o desenvolvedor
(raia desenho) antes do passo 5 — em sub-agente, a proposta volta com `Confirmada` vazia, e costura
trocada re-deriva o grupo. **Todo cenário pertence a um grupo** (colunas `Grupo` e `Costura` do
índice) e **toda linha da taxonomia com `CT-nn` ou lacuna aponta um grupo** (`não se aplica` leva
`—`). **O `05` existe se e só se uma linha tem costura `browser`** ([gate](#gate--quando-criar)). O
número de costuras não é meta. Por quê: a costura confirmada por quem conhece o arnês evita CT e
CT-B duplicados, faz do gate do `05` uma consequência (estudo 2026-09-26 §3.4) e decide a camada de
cada cenário — por isso vem antes deles.

### Passo 3 — Técnica formal por regra

Para cada 🟦 Regra, escolher a técnica pelo **tipo** da regra. Uma regra pode exigir duas.

| A regra fala sobre… | Técnica | Como derivar | Que defeito só ela pega |
|---|---|---|---|
| um **valor** dentro de um domínio | **EP** — particionamento | partições válidas + **cada inválida isolada em um cenário** | ramo de tratamento que nunca foi escrito |
| uma **faixa ordenável** (número, data, tamanho, contagem, dinheiro) | **BVA** | `borda−1`, `borda`, `borda+1` — com o **incremento do tipo certo** | off-by-one, `<` no lugar de `<=`, arredondamento |
| **combinação de condições** | **tabela de decisão** | montar condições × regras, colapsar só onde a ação comprovadamente não depende da condição; 1 cenário por regra sobrevivente | `AND`/`OR` trocado, combinação sem regra definida |
| **ciclo de vida / status** | **tabela estado × evento** | matriz completa; **toda célula vazia é um cenário negativo** | dupla aprovação, transição ilegal aceita, ordem invertida |
| **quem pode fazer o quê** | **matriz papel × ação** | células não cobertas por cenário existente; ação destrutiva é obrigatória | permissão validada só na UI |
| **≥3 parâmetros independentes** | **pairwise** | gerar combinações 2-a-2 e registrar as restrições | falha de interação de configuração |
| **efeito colateral** (e-mail, job, evento, auditoria) | **rastreio de efeito** | **primeiro o QUE**: canal/tipo exato que o requisito nomeia e destinatário. **Depois as direções**: aconteceu / **não** aconteceu quando não devia / aconteceu **uma só vez**; e uma quarta se a atomicidade importar | efeito removido, duplicado, fora da transação — ou **entregue pelo canal errado** |
| **identidade / unicidade** | **normalização** | caixa, espaços nas bordas, acento, unicode | `PROMO10` ≠ `promo10` |

**Regras de execução que mudam o resultado:**

Antes de aplicar, abra [`references/tecnicas-por-regra.md`](references/tecnicas-por-regra.md) — o
desenvolvimento, a tabela e o exemplo de cada regra. A regra vale mesmo sem o exemplo:

- **Criação ≠ edição ≠ uso** — partição e valor limite nos três pontos, sempre; na edição,
  unicidade contra si mesmo e validação que só roda na criação
- **Partição de EP × rastreio de efeito** — nenhum par (partição do discriminador, efeito
  rastreado) sem cenário
- **Domínio condicionado** — cruzar a partição do discriminador com o valor limite do dependente
- **Ciclo de volta: 2-switch** — dois eventos em sequência, oráculo sobre o destino do segundo e
  os registros do ciclo anterior
- **Estado exibido** — toda partição do enum é classe de equivalência obrigatória; não se amostra
- **Não-efeito** — o `Dado` declara o destinatário/alvo que existe (zero destinatários nunca prova
  não-efeito); atomicidade exige falhar depois do ponto do efeito **e** destinatário real
- **Estado × operação** — uma matriz só, montada antes das regras: produto cartesiano fechado
  `todos os estados × todas as operações`, total de células declarado, legenda auditada, ao menos
  uma célula válida por coluna, persona e campo como dimensões (campo exercitado fora do estado
  inicial; a linha inválida de `editar` afirma o valor gravado); célula só conta se a operação
  dela for executada; verbo irmão não herda evidência
- **Idempotência** — assertion sobre o agregado **persistido**; agregado fora de escopo → não
  escrever, lacuna declarada + pergunta
- **Exemplo discriminante** — a implementação defeituosa produziria resultado diferente com este
  valor, este instante, este ambiente, este `Dado`?
- **Lacuna fechada sem discriminar é piora** — provar em uma linha por que o cenário novo discrimina
- **Premissa** — escopo apaga; mecanismo escolhe (raia desenho, `@premissa`) e nunca autoriza a não
  escrever o cenário; comportamento vira pergunta de requisito com a ➡️ por falha fechado, a `RQ`
  fica aberta e sem cenário, e o invariante das duas leituras vira cenário da regra fechada
- **Impossibilidade de arnês é hipótese** — tentar mudar o arnês; a lacuna declara o que foi tentado
- **Afirmação negativa é hipótese até um `grep` prová-la** — toda negativa que **dispensa um
  controle** entra na wiki com a mesma exigência de evidência que a positiva: `arquivo:símbolo:linha`
  do vendor (formato da `feature-wiki`, *Citações de código*; `arquivo:linha` sem símbolo é achado
  do `citacoes.sh`) — e, quando dispensa um controle de **fronteira** (escopo, autorização, trava
  de escrita), também **um cenário escrito como se ela fosse falsa**
- **Estado de erro declara a saída** — todo cenário cujo `Então` é 4xx, 5xx ou redirect ganha um
  par que afirma **um destino alcançável a partir dali**. Se não existir destino, o achado não é do
  teste — é de desenho, e volta para o `00` como pergunta

### Demais regras

1. **Partição inválida nunca se combina com outra inválida** no mesmo cenário — a primeira
   validação a disparar mascara as demais, e o cenário passa a provar menos do que aparenta.
2. **Incremento do BVA tem o tipo do campo**: `decimal(10,2)` → `0,01`; `date` → 1 dia;
   `datetime` → 1 segundo; string → 1 caractere. Incremento errado gera cenário redundante que
   parece cobertura.
3. **Tabela de estados, não diagrama.** O diagrama só mostra transições válidas, e por isso só
   produz teste positivo. É a **matriz** que expõe as células vazias — e elas são a maior fonte
   de defeito de workflow.
4. **Pairwise não é garantia**: 2-a-2 deixa passar de 10% a 40% das falhas de interação. Usar
   como redutor, e subir para 3-a-3 no subgrupo crítico.

### Passo 4 — Checklist de taxonomia de defeito

As técnicas do passo 3 derivam do que **está escrito**. Este passo cobre o que a especificação
**nunca menciona** — e é onde mora a maior parte do retrabalho.

Percorrer a lista **uma vez por feature**. Cada item recebe **o ID do cenário que o mata** —
não a palavra "sim".

> **`sim` não é resposta.** Num experimento controlado, os dois conjuntos avaliados marcaram
> itens do checklist como cobertos (*"Idempotência: sim"*, *"Timezone: parcialmente coberto"*)
> enquanto o defeito correspondente atravessava intacto. Item de checklist sem ID de cenário é
> exatamente o "falso ✅" que faz o requisito parecer coberto. As três respostas válidas são:
> **`CT-nn`**, **`não se aplica: {motivo}`** ou **`lacuna declarada: {o que foi tentado}`**. Item
> que depende de `RQ` aberta responde `lacuna declarada: RQ-nn aberta (Qn)`.

Abra [`references/taxonomia-de-defeito.md`](references/taxonomia-de-defeito.md) e percorra a tabela
inteira — ela dá o cenário obrigatório de cada gatilho (IDOR, autorização na ação, idempotência,
concorrência, fronteira na gravação, cardinalidade 0/1/N, ausente ≠ `null` ≠ `""`, paginação,
timezone, soft delete, mass assignment, superfície Livewire — método público, propriedade pública
sem `#[Locked]`, estado do framework —, IDOR e mass assignment **por tabela**, discriminante nulo…).
**`não se aplica` que dispensa um controle é afirmação negativa**: segue a regra do `grep` do passo 3. O gatilho é
a **superfície**, não a origem dela. Defeito que escapou para produção vira linha nova no checklist
de taxonomia e candidato a rule pela definição *Vale virar rule* da `requirement-to-rule` (step 12) —
nunca gravado direto em `.ai/rules/`: a definição de rule é uma só (estudo 2026-09-26 §8, item 10).

### Passo 5 — Escrever os cenários em Gherkin

**Gherkin como linguagem de especificação, sem runner.** Não existe plugin Gherkin viável para
Pest, e Behat exigiria uma ponte Laravel abandonada. Os cenários vivem no markdown e são
traduzidos para `describe()`/`it()` do Pest.

Estrutura `Funcionalidade` → `Regra` → `Cenário` e exemplos de `Esquema do Cenário` (a forma
canônica de EP e BVA): abra [`references/gherkin.md`](references/gherkin.md) antes do primeiro cenário.
Antes de nomear fake, assertion ou helper num cenário, abra também
[`references/armadilhas-de-api.md`](references/armadilhas-de-api.md).

**Regras de escrita — cada uma corrige um anti-padrão catalogado:**

| Regra | Anti-padrão que evita |
|---|---|
| **Declarativo, não imperativo**. Teste: *"esta frase precisa mudar se a implementação mudar?"* Se sim, reescrever | cenário que descreve cliques e campos, e quebra a cada mudança de UI |
| **Um único `Quando` por cenário** | cenário que testa dois comportamentos e não diz qual falhou |
| **3 a 5 passos; nunca mais de 9** | setup mecânico que esconde a regra |
| **Ator nomeado em 3ª pessoa** (`o coordenador`, `o comprador`), nunca "eu" | ambiguidade de quem faz a ação |
| **`Então` sobre saída observável**, com o valor concreto | `Então funciona` — que não é oráculo |
| **Cenário de recusa afirma o não-efeito, e nomeia quais.** "Recusado" sozinho não basta: afirmar que o estado **não** mudou e que **cada efeito que a operação dispara no caminho feliz** não aconteceu — notificação, histórico, trilha, job, contador. "Nenhum registro" genérico não é asserção | implementação que recusa **depois** de gravar, ou **depois** de avisar alguém, passa no cenário |
| **`Dado` fixa a situação de partida** sempre que a entidade tem ciclo de vida — inclusive nos cenários positivos | cenário que aprova "uma solicitação criada por X" sem dizer que ela foi enviada: materializado, ele **certifica** a transição ilegal (ver [gate, item 6](#passo-6--gate-de-falsificabilidade-obrigatório)) |
| **Nenhum termo de domínio não definido no `Então`** — use o campo, o estado ou o valor | `Então o aprovador da vez é o Rui` / `Então o acesso é concedido` (que é `assertOk` com outro nome) |
| **Vocabulário do glossário**: termo de domínio é o de `wikis/glossario.md`; ausente dele, o literal do `00` — nunca sinônimo inventado | dois nomes para a mesma coisa: o cenário afirma um conceito que ninguém decidiu (estudo 2026-09-26 §2.5, item 4) |
| **Título descreve o comportamento** | `Cenário: teste 3` / `Cenário: criar, editar e excluir` |
| **Sem detalhe incidental** — só os dados que afetam a regra | dado mágico que invalida o cenário quando muda |
| **Cenários independentes**, executáveis em qualquer ordem | cenário que só passa depois do anterior |
| **`Esquema do Cenário` só para classes de equivalência** | matriz combinatória disfarçada de tabela |
| **`Contexto` (Background) no máximo 4 linhas, só `Dado`** | precondição invisível para quem lê o cenário no meio |

### Passo 6 — Gate de falsificabilidade (OBRIGATÓRIO)

Para **cada `Regra:`**, escrever as implementações erradas plausíveis e apontar o cenário que
morre com cada uma.

Antes de montar a tabela `#### Mutantes previstos`, abra [`references/template-04.md`](references/template-04.md)
(formato) e [`references/mutation-testing.md`](references/mutation-testing.md) (operadores que servem de fonte dos mutantes).

**Regras do gate:**

1. **De 2 a 5 mutantes por regra** no perfil padrão; de 3 a 6 no completo. O piso evita gate
   decorativo; **o teto evita inflar o gate com mutantes triviais para parecer rigoroso**. Regra
   que precisa de mais de 6 mutantes plausíveis quase sempre é duas regras.
   **Exceção: mutante trazido pela revisão adversarial não conta para o teto.** Ele é achado
   medido, não enchimento — e desdobrar a regra no fechamento da revisão significaria renumerar
   toda a rastreabilidade por um motivo cosmético. Registrar o estouro com a origem
   (`M-nn — revisão adversarial`) e seguir
2. Mutante **sem cenário matador** → escrever o cenário. Se não for viável, registrar como
   lacuna declarada, com o motivo
3. O mutante tem de ser **plausível**, e a plausibilidade tem teste: *um dev competente,
   lendo só o requisito e sem má-fé, escreveria isso?* Se a resposta é não, o mutante não conta.
   "Apagar o método inteiro", "retornar sempre `null`" e "trocar o nome da coluna" são
   enchimento, não mutantes
4. Cenário que não mata mutante nenhum é **candidato a corte** — provavelmente é caminho feliz
   redundante
5. **O gate vence o teto.** Se o único matador de um mutante for um cenário além do teto do
   perfil (típico: o teto do `05` é 1 happy path, e o matador é um erro visível), **escreva o
   cenário e justifique o estouro**. Deixar mutante vivo para economizar cenário inverte a razão
   de existir da skill
6. **Cenário cujo `Dado` não fixa a situação de partida é barrado aqui.** Quando a entidade tem
   ciclo de vida, um cenário positivo que não declara de que estado parte não é oráculo fraco —
   é **oráculo invertido**: materializado ao pé da letra, ele **certifica** a transição ilegal
   como comportamento esperado. O gate não o aceita nem como "cenário que não mata mutante
   nenhum" (item 4, que manda cortar): esse mata ao contrário, e precisa ser **corrigido**, não
   podado — o `Dado` recebe o estado, e a célula que ele estava ocupando na matriz volta a ficar
   vazia
7. **Toda asserção de ausência é auditada contra o `Dado`.** Cenário que afirma "nenhum X foi
   criado/enviado" numa configuração onde X não teria destinatário, alvo ou saldo é **falso ✅** e é
   barrado — corrigido pela fixture, não podado
8. **A legenda da matriz é verificada célula a célula**: cada `❌` afirma os efeitos que aquela
   operação dispara no caminho feliz. Legenda não conferida vale como célula **não resolvida**
9. **Todo mutante preenche `Asserção que mata`, em todo perfil** (mínimo, padrão, completo): a
   asserção ou o valor do CT que diverge sob ele (*linha `borda` (3, 3): "recusado"; o mutante
   aceita*). Vazia = **gate não passou**; sem matador = `— (lacuna declarada)`. Por quê: sem ela,
   "CT-nn mata M-nn" é afirmação de quem derivou os dois — gate autocertificado (estudo 2026-09-26 §7.3)

### Passo 7 — Alocar camada e podar

Antes de alocar, abra [`references/escolha-de-camada.md`](references/escolha-de-camada.md) — a tabela cenário → camada → API —
e, antes de fixar a API de cada camada (fake, assertion, helper), [`references/armadilhas-de-api.md`](references/armadilhas-de-api.md).

0. **Desempate da camada: ela sai do observável que o requisito afirma, não da estrutura provável
   do código.** Decidir "isto é `Unit` ou `Feature`" perguntando *"existiria um predicado puro
   para isso?"* é palpite de implementação — exatamente o que o princípio 1 proíbe. A pergunta
   certa é: **o que o `Então` afirma?** Valor calculado → `Unit`. Registro no banco, autorização,
   efeito colateral → `Feature`. Elemento de tela → componente. Pixel, JS, acessibilidade →
   `Browser`. Se o requisito não determina onde o comportamento vive, a camada é a **mais externa
   observável**, não a mais barata imaginável

1. Cada cenário recebe a **camada mais barata que existe no projeto** e o falsifica
   (ver [tabela](references/escolha-de-camada.md)). "Que existe no projeto" não é detalhe:
   um projeto cujo `tests/Pest.php` não liga o `TestCase` da aplicação a `tests/Unit` roda o caso
   "unitário" sem container, e cast de enum, config e container não resolvem. **Confirmar as
   ligações do `tests/Pest.php` antes de alocar** — a escada real começa na camada mais barata
   que o arnês do projeto sustenta, não na teórica
2. **Podar** (escada do Ponytail aplicada a teste):
   - cenário que não mata mutante nenhum → cortar
   - dois cenários que matam exatamente o mesmo conjunto de mutantes → manter um
   - cenário de caminho feliz repetido em duas camadas → manter o mais barato, salvo se o caro
     provar algo a mais (renderização, JS)
3. **Teto por perfil**, para o conjunto não virar burocracia abandonada:

| Perfil | Teto de cenários | Teto de CT-B |
|---|---|---|
| mínimo | 1 por regra | 0 |
| padrão | 3 por regra | 1 happy path |
| completo | 5 por regra | 1 happy path + 1 erro visível |

**Regra de rastreio de efeito consome o teto inteiro.** Ela já exige três cenários obrigatórios —
quatro quando a atomicidade importa —, e o teto do perfil `padrão` é três por regra. Não é
estouro: é o custo declarado da técnica. Regra de efeito colateral **não divide o teto** com
cenários de fronteira ou de partição; se a regra também tem domínio a particionar, ela é duas
regras.

**Um `Esquema do Cenário` conta como 1 cenário, não como N linhas** (por quê: `references/escolha-de-camada.md`).

Estourar o teto é permitido **com justificativa escrita** — normalmente significa que a regra
deveria ser duas. E o [gate do passo 6 vence o teto](#passo-6--gate-de-falsificabilidade-obrigatório):
mutante vivo é pior que cenário a mais.

4. **Registrar o que foi cortado.** Quando há mais candidatos que teto — o caso normal no `05`,
   onde o gate é generoso e o teto é apertado —, escrever uma tabela de **cogitado e cortado**
   (formato em [`references/template-04.md`](references/template-04.md)).
5. **Conferir as costuras** do [passo 2](#costuras-de-teste--fecham-o-passo-2-antes-do-primeiro-cenário):
   cada cenário cai na costura do seu grupo; o que o item 0 manda para outra camada muda de grupo
   ou abre linha nova em `## Costuras de Teste`, que volta à confirmação da sessão.

### Precedência: Project Rule do projeto vence a skill

Quando uma instrução desta skill colidir com uma rule em `.ai/rules/` do projeto, **a rule vence** —
ela é medição local, a skill é generalização (o caso: `references/casos-medidos.md` §Precedência).

Obrigatório: **declarar a divergência** em uma linha no `04`, dizendo qual rule venceu e por quê.
Divergência silenciosa entre skill e rule é a forma mais fácil de a wiki descrever um comando que
ninguém consegue rodar.

---

## Escolha de Camada em Laravel/Filament

Tabela cenário → camada → API e helpers `@deprecated`: [`references/escolha-de-camada.md`](references/escolha-de-camada.md),
aberta no passo 7. Os gates abaixo valem sem ela; os casos que os motivaram estão em `references/casos-medidos.md`.

> **Regra do par** (aprendida em produção): *uma tela aberta não é uma tela que grava.* Um `GET`
> pode ficar verde com o salvamento quebrado. Toda tela de escrita gera **dois** cenários — a
> visita **e** a gravação por componente.

**Gate de tela de escrita (obrigatório).** Para **toda** rota `create` / `edit` da tabela
`## Superfície de UI` do PRD, é obrigatório existir um cenário de **gravação por componente**
(`fillForm` → `->call('create'|'save')` → `assertDatabaseHas` com os campos que importam).
Tela de escrita coberta apenas por visita é **lacuna de gate**, não decisão de escopo.

**Gate de camada da regra (obrigatório).** Toda regra de **autorização** e toda regra de
**validação de domínio** precisa de **ao menos um** cenário que exercite a escrita **por fora do
componente de UI** — `Feature` chamando o model, o service ou a rota diretamente. O teste de
componente continua sendo o padrão e a camada mais barata; o que ele não consegue, **por
construção**, é distinguir a regra que vive no domínio da regra que vive **só no formulário** —
esta fica verde no componente e vermelha por fora (tabela em `references/escolha-de-camada.md`).

Um cenário por regra basta — não é para duplicar a matriz inteira na camada externa. O que o gate
proíbe é a superfície de escrita **inteira** existir só na camada do componente.

**Assertion proibida como oráculo único de um cenário:**

| Assertion sozinha | Por que não prova nada |
|---|---|
| `assertNoJavaScriptErrors()` / `assertNoSmoke()` | página em branco, 403 renderizado e tela sem conteúdo passam |
| `assertOk()` / `assertSuccessful()` | responde 200 com o conteúdo errado |
| `assertSee('{texto de layout}')` | o texto do layout aparece em qualquer estado da página — **um teste de dark mode que só faz `->inDarkMode()->assertSee('Painel')` não testa dark mode** |
| `assertDatabaseHas` só com a chave primária | passa com todos os outros campos errados |
| "não lança exceção" / `expect($x->count())->toBeInt()` | tautologia: o tipo já é garantido pela linguagem |
| `->not->toBe($outro)` sem valor esperado | dois resultados errados, porém diferentes, passam |

Console e status são **assertions de apoio**. Todo cenário precisa de pelo menos uma assertion
sobre **o que ele afirma** — o valor, o registro, o estado ou o elemento.

**Versões importam** — e o modo de errar aqui é silencioso. Em Filament 4/5 os helpers antigos
**continuam existindo**, marcados `@deprecated` nos `.stubs.php`: `assertFormSet`,
`callTableAction` e `assertTableActionExists` funcionam e não avisam nada. O CT escrito com eles
passa hoje e quebra no upgrade.

**Confirmar no vendor antes de escrever**, não na memória:
`grep -rn "@deprecated" vendor/filament/*/.stubs.php`.

---

## Arquivo 04: Casos de Teste

**Path**: `wikis/specs/{branch}/{feature}/04-casos-de-teste.md`

Template: abra [`references/template-04.md`](references/template-04.md) antes de escrever. O que ele
carrega vale mesmo adaptado: cabeçalho *"Derivado do requisito, não do plano"*; `## Perfil de
Derivação` (P×I por área) com a contagem por `grep -c`, nunca à mão; `## Varredura SFDIPOT`;
`## Mapa de Regras` (área, origem `RQ`/`P-nn`, técnica), com uma linha por `RQ` aberta;
`## Costuras de Teste`; `## Fronteira com o Plano`; `## Setup Global` com situação de partida
**por transições reais** — helper `{entidade}Em('{situacao}', [...])` em `tests/Pest.php` que
chama a máquina de estados do domínio, nunca `situacao` gravada à força;
Gherkin + `#### Mutantes previstos` por regra, com `Asserção que mata`; `## Checklist de Taxonomia`
com o grupo de cada linha; `## Índice de Cenários` (ID, regra, técnica, grupo, costura, arquivo, mata);
`## Sem CT-B` quando nenhuma costura é `browser`.

---

## Arquivo 05: Casos de Teste de Browser — Condicional

**Path**: `wikis/specs/{branch}/{feature}/05-casos-de-teste-browser.md`

### Gate — quando criar

O `05` existe **se e só se** uma linha de `## Costuras de Teste` do `04` tem costura `browser`. E a
linha `browser` só se justifica se houver linha em `## Superfície de UI` do PRD **e** o cenário
afirmar sobre algo que **só o navegador prova**: JavaScript executado, console/erro de JS,
acessibilidade, cor/tema, layout. Se o cenário puder ser provado por componente Livewire, ele
pertence ao `04`, na costura `componente Livewire/Filament`.

Sem costura `browser`: **não criar o arquivo** e registrar no `04` a seção `## Sem CT-B` com o motivo.

Antes de qualquer CT-B, abra [`references/pest-plugin-browser.md`](references/pest-plugin-browser.md)
(fatos do plugin, comando, seletores, tema e cor) e [`references/template-05.md`](references/template-05.md).
Os que mais invalidam CT-B: nunca `wait($segundos)`; `assertPathIs` antes do conteúdo; `actingAs()`
antes do `visit()`; `npm run build` antes; nunca `--parallel`; `assertNoSmoke()` só em tela própria.

---

## Armadilhas de API que Invalidam CT

Antes de nomear fake, assertion ou helper num cenário, abra [`references/armadilhas-de-api.md`](references/armadilhas-de-api.md)
— cada linha dela já produziu teste vermelho sem defeito no código, ou verde sem provar nada.

---

## Fechamento do Ciclo com Mutation Testing

O passo 6 **prevê** os mutantes. Depois de implementar, `pest --mutate` **mede** — mas mede uma
coisa só, e é preciso saber qual.

### O que o mutation score NÃO responde

> **Mutation testing só muta código que existe.** Se a cláusula do requisito nunca virou código —
> não há `if ($percentual > 100)` para mutar —, **nenhum mutante é gerado e o score não cai**.
> Ele é estruturalmente **cego à omissão**, que é justamente a classe de defeito mais cara.

O experimento — mesmo score, detecção diferente — está em [`references/mutation-testing.md`](references/mutation-testing.md).

**Conclusão operacional**: o mutation score é um **piso de qualidade de assertion**, não um
indicador de cobertura de requisito. Quem responde por omissão é a rastreabilidade `RQ` → cenário
(passo 2) e o gate de mutantes **de especificação** (passo 6) — que nascem do requisito, não do
código, e por isso enxergam o que não foi escrito.

### Como rodar

Comandos verificados, `--path`/`--class`, `covers()`, lançador do Windows e a tabela de tradução do
sobrevivente: abra [`references/mutation-testing.md`](references/mutation-testing.md) antes do primeiro `--mutate`.

- Exige driver de cobertura (**PCOV ou Xdebug** com `XDEBUG_MODE=coverage`). *"Sem driver"* e
  *"plugin ausente"* só se declaram com a prova negativa colada (`php -m | grep -i "pcov\|xdebug"`,
  `ls vendor/pestphp/`) — em 2026-09-21 as duas afirmações estavam na wiki e as duas eram falsas
- **No Windows, `pest --mutate` dá 100 % falso** — o subprocesso que o `cmd` não executa conta como
  morto. **Score só vale com `Duration` compatível com N × tempo dos testes cobridores e com a
  lista de sobreviventes**; lançar pelo `.cmd` poliglota (arquivo `{skills}/feature-wiki/scripts/pestw.cmd`;
  explicação em `{skills}/feature-wiki/references/pest-5.md`)
- **`--testsuite=A --testsuite=B` só honra o último** — uma suíte por comando
- **Confirmar que `pestphp/pest-plugin-mutate` está declarado no `composer.json`.** Ele costuma
  aparecer em `vendor/` como dependência transitiva do Pest 5 — o comando funciona por acidente da
  árvore de dependências e some num `composer update`. Se estiver só transitivo, incluir
  `composer require pestphp/pest-plugin-mutate --dev` como passo no PRD
- **`pest()->mutate()` em `Pest.php` não existe** — não inventar
- `covers(X::class)` restringe o que conta como coberto: mutante fora dele sai `uncovered` e o score vai a 0 %
- Escopar sempre: mutar o projeto inteiro é caro e devolve ruído

**Cada mutante sobrevivente é traduzido de volta para a lacuna de derivação** e vira cenário novo
(tabela de tradução em `references/mutation-testing.md`).

> **Nunca usar cobertura de linha como meta de qualidade.** Com o tamanho da suíte controlado,
> ela não prevê eficácia — 100% de linha é compatível com zero assertion útil. O indicador é o
> mutation score.

---

## Revisão Adversarial (obrigatória no perfil completo ou com Impacto 3)

Antes de montar o despacho, abra [`references/revisao-adversarial.md`](references/revisao-adversarial.md)
— o resumo das entradas e das oito tarefas do contrato.

**Disparo**: perfil **completo** em qualquer área, **ou Impacto 3** em qualquer área (ver
[Passo 0](#passo-0--perfil-de-esforço-por-risco)). Uma única rodada cobre o `04` inteiro.

Delegar a um **sub-agente que não derivou os cenários**, com o contrato de
[`agents/fw-adversario-ct.md`](agents/fw-adversario-ct.md). No Claude Code a rota é
`fw-adversario-ct` (`opus`, sem `Edit`/`Write`/`Bash`; definição em
[`agents/fw-adversario-ct.md`](agents/fw-adversario-ct.md) desta skill, que o Claude Code só enxerga
depois de `cp .ai/skills/*/agents/*.md .claude/agents/`) ou
`general-purpose` com `model: opus` **explícito** — o mais forte disponível, porque classificar
se um oráculo está correto é a tarefa em que modelos são comprovadamente piores do que em gerá-lo.
**Cegueira**: o sub-agente recebe **só** o que a linha `Entrada` (abaixo) lista. No `fw-adversario-ct`,
Read e Grep fora dela são negados **por construção** pelo hook `PreToolUse` (perfil `adversario-ct`)
— desde que o hook esteja instalado (`guarda-subagente.sh` da `feature-wiki` ≥ 4.0.0 em `{skills}`).
Sem o script, o hook nega tudo: o agente devolve as leituras negadas e a sessão redespacha pela rota
`general-purpose` com `model: opus` (cegueira de prompt). O
orquestrador registra o disparo em `## Despachos` do `03`. Quem despacha o adversário e fecha os
achados é a **sessão principal** — um sub-agente não despacha sub-agente; se a derivação rodou em
sub-agente, ela devolve o `04` e a sessão dispara a revisão. Host sem sub-agente: **não**
autorrevisar; declarar no cabeçalho do `04` a lacuna `Revisão adversarial: NÃO FEITA — host sem
sub-agente`, que o `feature-quality-gate` reporta como débito.

Linhas do contrato que todo despacho carrega — inclusive pela rota `general-purpose`, em que nenhum
agente recusa o que não devia receber:

```text
Entrada: 00-requisito.md + 04-casos-de-teste.md (e 05, se houver; e wikis/glossario.md, se existir)
NÃO receber: o PRD, o código, nem o raciocínio de quem derivou
PROIBIDO: elogiar o conjunto, reescrever os cenários, dizer "está bom".
```

O contrato completo e a fonte da verdade é [`agents/fw-adversario-ct.md`](agents/fw-adversario-ct.md);
o resumo das entradas e das oito tarefas em [`references/revisao-adversarial.md`](references/revisao-adversarial.md)
não o substitui.

**O que fazer com os achados** (a revisão não termina na lista), pela sessão principal:

1. **Fechar todos** — cada lacuna vira cenário novo, ou oráculo reescrito, ou lacuna declarada com motivo
2. **Re-revisar uma única vez**, e só se o fechamento tiver criado **cenário novo** (não se apenas reforçou oráculo existente). Cenário novo introduz superfície nova, e é aí que mora a lacuna de segunda ordem
3. **Teto de 2 rodadas.** Se a segunda rodada ainda trouxer achado estrutural, o problema não é o conjunto — é a regra, que provavelmente deveria ser duas. Registrar e escalar

Registrar no `04` quantos achados a revisão produziu e o que virou cada um. Revisão adversarial
cujos achados ninguém fecha é teatro caro.

---

## Proibições

1. **Não derivar cenário lendo a implementação da feature.** Se ela existe (legado, bug de
   produção), derivar do comportamento **acordado** e só então comparar com o código.
2. **Não escrever cenário sem `Então`.** Cenário sem oráculo é o defeito mais comum de suíte
   gerada por IA.
3. **Não combinar duas partições inválidas** no mesmo cenário.
4. **Não usar `float` para dinheiro** em nenhum exemplo.
5. **Não inventar API.** Confirmar a versão de Pest/Filament/Livewire do projeto antes de
   escrever o nome de qualquer helper.
6. **Não marcar regra como coberta** enquanto houver mutante previsto sem matador — declarar a lacuna.
7. **Não empurrar para o browser** o que um teste de componente prova.
8. **Não editar o `00-requisito.md`** a não ser para acrescentar pergunta em `## Perguntas ao Solicitante` e a marca `aberta — Qn` que ela põe na `RQ` afetada.
9. **Não autorrevisar** o conjunto no perfil completo ou com Impacto 3.
10. **Não usar cobertura de código como critério de suficiência.** "Todo método público tem ao
    menos 1 CT" e "cada branch tem um CT" são critérios sobre um código que **ainda não existe**
    no momento da derivação — seguir isso obriga o agente a imaginar a implementação e testá-la,
    que é a definição de teste tautológico. O critério de suficiência aqui é: **toda regra tem
    seus mutantes previstos mortos**.
11. **Não escrever teste `[CT-nn]` sem o cenário no `04`/`05`.** Cenário descoberto durante a
    implementação nasce **aqui** — Gherkin, regra, mutante — e só depois vira código de teste.
    O caminho inverso, teste escrito e "documentado depois", é a Proibição 1 com outro nome, e
    foi medido: oito IDs de CT só no arquivo de teste, todos derivados do código. Requisito novo
    entra pelo **Adendo** do `00`, e achado de revisão pela **premissa `P-nn`** (ver `feature-wiki`),
    não direto no teste.
12. **Não derivar CT de log.** Log não é cláusula do requisito; é saída observável do plano, e quem
    a confere é a dimensão D do quality gate. Exceção: requisito que pede trilha de auditoria —
    aí é `RQ`, e o cenário afirma o **registro**, não a linha de log.

---

## Checklist Final

### Derivação

- [ ] Perfil de esforço definido por área, com P×I registrado
- [ ] Varredura SFDIPOT preenchida; dimensão vazia **declarada** com motivo
- [ ] Mapa de regras montado; toda `RQ` fechada e toda `P-nn` vigente do `00` gerou ao menos uma regra ou uma justificativa
- [ ] Toda `RQ` aberta aparece como `RQ-nn — aberta (Qn), sem cenário até a resposta` — e nenhum cenário a afirma
- [ ] Perguntas geradas no formato ❓/➡️ com raia (em sub-agente, numeração provisória `Q?n`, que a sessão renumera); as de requisito devolvidas para `## Perguntas ao Solicitante` do `00`
- [ ] Técnica formal escolhida e **nomeada** por regra
- [ ] BVA com o incremento do tipo certo (`0,01` em decimal, 1 dia em date)
- [ ] Entidade com `status` → tabela **estado × evento**, com 100% das células inválidas no perfil completo
- [ ] A matriz é **uma só** e é o produto cartesiano `todos os estados × todas as operações`, montada do enum e não do mapa de regras — com o **total de células declarado** e cada uma resolvida
- [ ] Nenhuma premissa de **mecanismo** foi usada para apagar cenário — ela fixa qual escrever, e o mecanismo descartado virou lacuna declarada
- [ ] Toda premissa de **comportamento** (o que o sistema faz quando o texto não diz) virou pergunta da raia requisito com ➡️ por **falha fechado**, e o invariante das duas leituras virou cenário da regra fechada
- [ ] Partições inválidas isoladas uma por cenário
- [ ] Checklist de taxonomia percorrido item a item, com dispensa justificada

### Escrita

- [ ] Cenários em Gherkin pt-BR: `Funcionalidade` → `Regra` → `Cenário`
- [ ] Um único `Quando` por cenário; 3–5 passos; ator nomeado em 3ª pessoa
- [ ] Todo `Então` afirma saída observável com valor concreto
- [ ] Termos de domínio de `wikis/glossario.md` (se existir); termo ausente dele = literal do `00`, sem sinônimo
- [ ] Todo cenário de entidade com ciclo de vida tem a **situação de partida fixada no `Dado`** — inclusive os positivos
- [ ] Todo cenário de recusa afirma o não-efeito de **cada** efeito que a operação dispara no caminho feliz — nomeados, não "nenhum registro"
- [ ] Todo `Então` de ausência tem, no `Dado`, o destinatário/alvo que tornaria o efeito possível
- [ ] `Esquema do Cenário` só onde há classe de equivalência ou borda, com a coluna do rótulo

### Gate

- [ ] **Toda regra declara ≥2 mutantes** (≥3 no perfil completo)
- [ ] Todo mutante tem cenário matador e `Asserção que mata` preenchida (em **todo** perfil) **ou** lacuna declarada com motivo
- [ ] Cenário que não mata mutante nenhum foi cortado ou justificado
- [ ] Nenhum **oráculo invertido** — cenário positivo sem situação de partida foi corrigido, não podado
- [ ] Nenhuma asserção de ausência em mundo vazio — atomicidade provada com falha **depois** do ponto do efeito **e** destinatário real
- [ ] Legenda da matriz conferida célula a célula contra os efeitos daquela operação
- [ ] Cada cenário na camada mais barata que o prova
- [ ] `## Costuras de Teste` declarada depois do mapa de regras e antes dos cenários: uma linha por grupo, `Costura` do enum, existente > nova, `Confirmada` pela sessão; todo cenário com `Grupo` e `Costura` no índice; toda linha da taxonomia com `CT-nn` ou lacuna aponta um grupo (`não se aplica` leva `—`)
- [ ] `05` criado se e só se uma costura é `browser`; senão, `## Sem CT-B` no `04`
- [ ] Toda regra de autorização e de validação de domínio tem **≥1 cenário por fora do componente de UI**
- [ ] Teto do perfil respeitado, ou estouro justificado
- [ ] Revisão adversarial executada por sub-agente independente (perfil completo ou Impacto 3)

### Saída da derivação

- [ ] References abertas declaradas no retorno da derivação (relatório final da invocação, ou o que o sub-agente devolve) — uma linha *"references lidas: {arquivo} (passo N), …"*, com cada arquivo de `references/` aberto e o passo em que foi aberto (lista na tabela do [Índice](#índice))

### Pós-implementação

- [ ] `pest --mutate --covered-only --path={escopo da feature}` executado — com **duração plausível** e sobreviventes listados (no Windows, via lançador `.cmd`); "sem driver/plugin" só com a prova negativa
- [ ] CT cujo elemento foi cortado depois do step 7 da `feature-wiki` (achado do step 9 ou 11: filtro, ação, coluna) marcado `@obsoleto` com motivo e `~~` no índice — não apagado, não deixado órfão
- [ ] CT novo que passa dos dois lados do `git stash`: reescrito, **ou** declarado "não falsificável nesta pilha" com o motivo (SQLite sem `VARCHAR`/`RESTRICT`)
- [ ] Mutante sobrevivente traduzido em lacuna de derivação e convertido em cenário novo
- [ ] Índice de cenários atualizado com o arquivo de teste real de cada CT
- [ ] **Sincronia nos dois sentidos**: todo `[CT-nn]`/`[CT-Bnn]` do teste existe no `04`/`05`, e todo CT do índice aponta um teste existente ou declara "fundido em CT-nn"; linha de dataset nova existe como Exemplo no Gherkin
- [ ] Contagem do cabeçalho (`Cenários: {n} · Regras: {n} · Mutantes previstos: {n} · Sem matador: {n}`) recalculada pelos quatro `grep -c` de `references/template-04.md` §Contagem do cabeçalho (nunca escrita à mão — ver feature-wiki 3.5.1)

**Teste de arquitetura sugerido** (um por projeto): falha com o `[CT-nn]` que existe só no teste ou só
no `04`/`05` — código em [`references/template-04.md`](references/template-04.md).

---

## Skills Companheiras

| Skill | Relação |
|---|---|
| `feature-wiki` | produz o `00`/`01`/`02` e invoca esta skill no step 7; esta devolve o `04`, o `05`, as perguntas e a proposta de costuras |
| `feature-tickets` | aloca cada CT do `04` a um ticket; o campo **Costura** do ticket é uma linha de `## Costuras de Teste` |
| `feature-quality-gate` | roteia achado de **destino 3** para cá; usa a Matriz de Rastreabilidade que os IDs desta skill sustentam |
| `ponytail` | a poda do passo 7 é a escada de simplicidade aplicada a teste — sem cortar cobertura de risco |
| `requirement-to-rule` | linha nova do checklist de taxonomia, vinda de defeito real do projeto, é candidata a rule pela definição *Vale virar rule* dela (step 12); esta skill não grava em `.ai/rules/` |

> **Caveman**: o `04` e o `05` são **boundary** — prosa normal. Cenário ambíguo produz teste errado.

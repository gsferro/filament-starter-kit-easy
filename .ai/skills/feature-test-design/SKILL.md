---
name: feature-test-design
version: 1.7.0
description: >
  Deriva casos de teste que MATAM defeito, a partir do requisito — não do plano e
  nunca do código. Invoque no step 4 da feature-wiki (antes de implementar), quando
  o feature-quality-gate rotear um achado para "destino 3 — teste", ao escrever o
  teste de regressão de um bug de produção, ou para cobrir código legado sem wiki.
  Substitui o preenchimento de gabarito por um pipeline de derivação: perfil de
  risco, varredura SFDIPOT, mapa de regras (Example Mapping), técnica formal por
  regra (partição, valor limite 3-valores, tabela de decisão, tabela estado x evento,
  pairwise), checklist de taxonomia de defeito (IDOR, idempotencia, concorrencia,
  timezone, nulo/vazio/ausente, paginacao, soft delete), cenários em Gherkin pt-BR
  (Funcionalidade > Regra > Cenário) e um gate de falsificabilidade: toda regra
  declara os mutantes plausíveis e aponta qual cenário mata cada um. Escolhe a
  camada mais barata que prova (Unit < Feature < componente Livewire/Filament <
  Browser). Escreve 04-casos-de-teste.md e, condicionalmente, 05-casos-de-teste-browser.md.
  Fecha o ciclo com pest --mutate: mutante sobrevivente vira lacuna de derivação.
---

# Feature Test Design — Do Requisito ao Caso de Teste que Mata Defeito

## Glossário

| Sigla | Significado |
|-------|-------------|
| **RQ** | Cláusula de requisito — unidade numerada do `00-requisito.md` |
| **Regra** | Uma regra de negócio verificável extraída de uma ou mais `RQ`. É o `Regra:` do Gherkin |
| **CT** | Caso de Teste — um `Cenário:` do Gherkin, com ID |
| **CT-B** | Caso de Teste de Browser |
| **EP** | Equivalence Partitioning — particionamento de equivalência |
| **BVA** | Boundary Value Analysis — análise de valor limite |
| **SFDIPOT** | Structure, Function, Data, Interfaces, Platform, Operations, Time |
| **Mutante** | Implementação errada plausível. O CT que "mata" o mutante é o que falharia se ela existisse |
| **MSI** | Mutation Score Indicator — % de mutantes mortos (`pest --mutate`) |

## Índice

- [Princípios Inegociáveis](#princípios-inegociáveis)
- [Quando Invocar](#quando-invocar)
- [Entradas e Gate de Entrada](#entradas-e-gate-de-entrada)
- [O Pipeline de Derivação](#o-pipeline-de-derivação)
  - [0. Perfil de esforço por risco](#passo-0--perfil-de-esforço-por-risco)
  - [1. Varredura SFDIPOT](#passo-1--varredura-sfdipot)
  - [2. Mapa de Regras](#passo-2--mapa-de-regras-example-mapping)
  - [3. Técnica formal por regra](#passo-3--técnica-formal-por-regra)
  - [4. Checklist de taxonomia](#passo-4--checklist-de-taxonomia-de-defeito)
  - [5. Escrever os cenários em Gherkin](#passo-5--escrever-os-cenários-em-gherkin)
  - [6. Gate de falsificabilidade](#passo-6--gate-de-falsificabilidade-obrigatório)
  - [7. Alocar camada e podar](#passo-7--alocar-camada-e-podar)
- [Escolha de Camada em Laravel/Filament](#escolha-de-camada-em-laravelfilament)
- [Arquivo 04](#arquivo-04-casos-de-teste)
- [Arquivo 05: Browser](#arquivo-05-casos-de-teste-de-browser--condicional)
- [Armadilhas de API](#armadilhas-de-api-que-invalidam-ct)
- [Fechamento do Ciclo com Mutation Testing](#fechamento-do-ciclo-com-mutation-testing)
- [Revisão Adversarial](#revisão-adversarial-obrigatória-no-perfil-completo)
- [Proibições](#proibições)
- [Checklist Final](#checklist-final)

---

## Princípios Inegociáveis

Cinco. Violar qualquer um devolve a skill ao problema que ela existe para resolver: teste que
executa o código, fica verde e não prova nada.

### 1. O caso de teste deriva do **requisito**, nunca do código

Fonte primária é o `00-requisito.md`. O PRD (`01`) entra só para nomes, paths e superfície —
**nunca** como fonte do comportamento esperado.

> **Por quê**: medido em 318 defeitos reais do Defects4J com 11 modelos — derivar testes a
> partir do código em vez da especificação multiplica por ~8 os testes que **codificam o bug
> como comportamento esperado** (0,46% → 3,84%) e corta por ~3 os testes que detectam o
> defeito (8,51% → 2,98%). Trocar o código por uma descrição do comportamento pretendido no
> prompt reverte os dois números. *(arXiv 2607.22883)*
>
> É o mesmo mecanismo pelo qual o PRD não serve de oráculo para o `feature-quality-gate`:
> validar contra a interpretação confirma a interpretação.

### 2. Cenário sem mutante morto não é caso de teste

Toda `Regra:` declara as implementações erradas plausíveis e aponta qual cenário falharia
diante de cada uma. Mutante sem matador é **lacuna declarada**, não detalhe.

> **Por quê**: cobertura de linha não prevê eficácia quando se controla o tamanho da suíte
> *(Inozemtseva & Holmes, ICSE 2014)*; detecção de mutantes correlaciona ~73% com detecção de
> defeitos reais e carrega informação que a cobertura não carrega *(Just et al., FSE 2014)*.
> Gerar o teste mirando um mutante é a técnica que o Meta industrializou em 10.795 classes *(ACH, FSE 2025)*.

### 3. A camada mais barata que prova

Cada cenário roda no nível mais barato capaz de falsificá-lo: `Unit` < `Feature` (HTTP) <
componente Livewire/Filament < `Browser`. Browser só quando a asserção depende de **JavaScript
executado, pixel ou acessibilidade**.

### 4. Regra antes de exemplo

Primeiro enumeram-se as **regras** (eixo de cobertura), depois se cobre cada regra com
exemplos. Escrever cenário direto produz variações do mesmo eixo e buracos nos outros.

### 5. Ambiguidade é pergunta, não caso inventado

Regra que o requisito não determina não vira cenário com valor chutado. Vira pergunta
registrada no `00-requisito.md` **e** um cenário marcado `@premissa` com a suposição explícita —
para que, quando a resposta vier, se saiba exatamente o que muda.

---

## Quando Invocar

- **Step 4 da `feature-wiki`**, depois do `00`/`01`/`02` e **antes** de implementar
- Quando o `feature-quality-gate` rotear um achado para **destino 3 — teste** ("escrever o CT que falha primeiro")
- Ao escrever o teste de regressão de um **bug encontrado em produção**
- Para cobrir **código legado** sem wiki (nesse caso a entrada é o comportamento acordado com o usuário, não o código)
- Quando `pest --mutate` deixar mutante sobrevivente

### Quando NÃO invocar

- Sem requisito nem comportamento acordado — sem oráculo não há derivação, só transcrição do código
- Para **corrigir** implementação: esta skill escreve especificação de teste, não conserta produto
- Ajuste cosmético, bump de dependência, refactor sem mudança de comportamento

---

## Entradas e Gate de Entrada

| Entrada | Obrigatória | Sem ela |
|---|---|---|
| `00-requisito.md` com cláusulas `RQ-##` | **sim** | pedir ao usuário. Nunca derivar do PRD |
| `01-plano-acao.md` — `## Superfície de UI`, rotas, paths, stack | sim (no fluxo da wiki) | fora do fluxo da wiki, perguntar a superfície |
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

A skill obriga a devolver as perguntas novas para `## Ambiguidades` do `00`. Há casos em que isso
não é possível: o `00` está fechado para edição, pertence a outra branch, ou está sendo usado como
linha de base de comparação.

Nesse caso: escrever as perguntas no próprio `04`, numa seção
`## Perguntas para o 00-requisito.md`, **em bloco pronto para colagem** (mesmo formato da seção de
destino), e **declarar o desvio** em uma linha. A pergunta continua bloqueando o que depende dela.
O que não pode acontecer é a pergunta morrer porque o arquivo de destino estava travado.

---

## O Pipeline de Derivação

Sete passos, em ordem. Os passos 3 e 4 são onde nasce a cobertura; o 6 é onde ela é auditada.

### Passo 0 — Perfil de esforço por risco

Antes de derivar qualquer coisa, pontuar **Probabilidade × Impacto** por área da feature.

| Fator | 1 | 2 | 3 |
|---|---|---|---|
| **Probabilidade** | código novo isolado, regra simples | integra com 1 componente existente | concorrência, integração externa, migração de dado, regra com muitas condições |
| **Impacto** | cosmético, reversível | retrabalho manual | dinheiro, dado de terceiro, autorização, irreversível, compliance/LGPD |

| P×I | Perfil | O que rodar do pipeline |
|---|---|---|
| 1–3 | **mínimo** | passos 1, 2, 5, 6 — técnica só EP; 1 cenário por regra |
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

> Sem este passo o pipeline explode: tabela de decisão e pairwise crescem rápido, e conjunto
> grande demais é abandonado, o que dá cobertura zero.

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

Converter as cláusulas `RQ` em **Regras** verificáveis. Uma `RQ` pode gerar várias regras; uma
regra pode atender várias `RQ`.

| Cartão | O que é | Onde vai |
|---|---|---|
| 🟦 **Regra** | critério de aceite verificável | vira `Regra:` no `04` |
| 🟩 **Exemplo** | caso concreto que ilustra a regra | vira `Cenário:` no `04` |
| 🟥 **Pergunta** | o requisito não determina | vai para `## Ambiguidades` do `00-requisito.md` |

Sinais de leitura do mapa, antes de seguir:

- **Muita pergunta vermelha** → o requisito não está maduro. Escalar ao usuário antes de derivar
- **Regra sem nenhum exemplo** → regra não é verificável como está; reescrever ou virar pergunta
- **Exemplo sem regra** → ou existe uma regra implícita não escrita (achado), ou o exemplo é ruído

> Só regras e exemplos entram no arquivo de casos de teste. Perguntas e história ficam fora —
> mas perguntas **bloqueiam** o que dependem delas.

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
| **efeito colateral** (e-mail, job, evento, log, auditoria) | **rastreio de efeito** | **primeiro o QUE**: canal/tipo exato que o requisito nomeia e destinatário. **Depois as direções**: aconteceu / **não** aconteceu quando não devia / aconteceu **uma só vez**; e uma quarta se a atomicidade importar | efeito removido, duplicado, fora da transação — ou **entregue pelo canal errado** |
| **identidade / unicidade** | **normalização** | caixa, espaços nas bordas, acento, unicode | `PROMO10` ≠ `promo10` |

**Regras de execução que mudam o resultado:**

### A regra que mais defeito produz: **criação ≠ edição ≠ uso**

Toda variável tem **três** pontos onde o sistema decide sobre ela, não dois:

| Ponto | Pergunta | Exemplo |
|---|---|---|
| **criação** | esse valor pode sequer ser gravado? | desconto de −5%? validade ontem? limite 0? |
| **edição** | e depois, no `save`? | a mesma validação existe? a unicidade ignora o próprio registro? |
| **uso** (leitura) | dado que está gravado, o que acontece? | cupom expirado é recusado na aplicação |

**Derivar partição e valor limite nos três pontos, sempre.** É a omissão mais cara e a mais fácil
de cometer, porque o requisito costuma descrever só o ponto de uso — *"valida se está dentro da
validade"* fala da aplicação e não diz nada sobre cadastrar, nem sobre editar para, uma validade
no passado.

> Medido em experimento controlado: dois conjuntos independentes deixaram passar os mesmos três
> defeitos de **criação** (valor negativo, valor acima do teto, data no passado) — ambos haviam
> testado o mesmo campo exaustivamente pelo lado do cálculo. Fechada a criação, uma revisão
> adversarial encontrou **quatro defeitos que viviam só na edição**: normalização, unicidade,
> autorização e domínio existiam no `create` e sumiam no `save`.

**A edição tem duas armadilhas próprias**, que não existem na criação:

- **unicidade contra si mesmo** — salvar sem alterar o campo único deve passar; a validação
  ingênua acusa colisão do registro com ele próprio
- **validação que só roda na criação** — regra escrita no `create` e esquecida no `save` é
  invisível para qualquer cenário que só crie

### Toda partição de EP se repete em cada rastreio de efeito

Quando um campo discriminador particiona o domínio (`tipo = percentual | valor_fixo`), ele
**também particiona o comportamento** — consumo, trilha de auditoria, validação, notificação. Não
basta cruzar discriminador × valor na gravação: cada **rastreio de efeito** precisa ser exercitado
em **cada partição do discriminador**.

> Medido: todos os cenários de consumo e trilha de um conjunto usavam cupom de porcentagem. Um
> atalho no ramo `valor_fixo` — ignorando validade, limite e o registro de auditoria — ficava
> **verde no conjunto inteiro**. Foi o achado mais caro da revisão adversarial.

Na prática: se há `N` partições do discriminador e `M` efeitos rastreados, o mínimo não é `N + M`,
é garantir que nenhum par `(partição, efeito)` fique sem nenhum cenário. Um `Esquema do Cenário`
com o discriminador como coluna resolve sem inflar a contagem.

### Domínio condicionado: a fronteira muda com o outro campo

Quando o domínio válido de um campo **depende do valor de outro**, ele não é um domínio só —
são vários, e cada um tem fronteiras próprias:

| Campo discriminador | Campo dependente | Fronteiras |
|---|---|---|
| `tipo = percentual` | `valor` | 0, 1, **100, 101** |
| `tipo = valor_fixo` | `valor` | 0, 1, sem teto superior |

**Cruzar a partição do discriminador com o valor limite do dependente** — uma tabela de decisão
pequena. Tratar `valor` como um domínio único faz o teto de 100% desaparecer sem que ninguém note,
porque os cenários "cobrem o campo `valor`".

### Ciclo de volta exige **2-switch**, não 1-switch

Quando um estado pode ser **reentrado** — rejeitado volta a rascunho, devolvido volta para
correção, estornado volta a pendente —, cobrir uma transição por vez não prova nada sobre o
**segundo giro**. O defeito mora ali: o ciclo novo herda o que o anterior deixou.

Derivar a **sequência de dois eventos** com oráculo sobre o resultado do segundo:

```
aguardando_diretor → rejeitar → rascunho → enviar → ?
```

O `Então` é sobre o **destino do segundo envio** (`aguardando_gestor`, e não
`aguardando_diretor`) e sobre **quais registros do ciclo anterior ainda contam**. Medido: os dois
conjuntos avaliados pararam no primeiro evento e deixaram passar exatamente esse defeito.

### Estado exibido: partição **exaustiva** do enum

Quando o usuário vê um rótulo derivado de um enum de estado, **toda partição do enum é uma classe
de equivalência obrigatória** — não se amostra. Cobrir "Aguardando gestor" e "Aprovada" e deixar
"Aguardando diretor" de fora permite exatamente o defeito que importa: a tela dizer "Aprovada"
enquanto falta uma etapa.

Um cenário com `Esquema do Cenário` e uma linha por valor do enum resolve. Se o enum tem 5 casos,
a tabela tem 5 linhas.

### Atomicidade: `assertNothingSent()` em pré-validação não prova nada

Para verificar que o efeito colateral não escapa quando a gravação falha, é preciso **falhar
depois do ponto de notificação** — constraint violada, mock do `save`, evento de model lançando.
Afirmar `assertNothingSent()` num caminho de **pré-validação** (onde nada seria enviado de
qualquer forma) parece cobrir atomicidade e não distingue as duas implementações. É um falso ✅
clássico, e os dois conjuntos medidos caíram nele.

### Estado × **operação**, não estado × visibilidade

Ao montar a tabela de estados, as colunas são **todas as operações** que a entidade aceita —
`aplicar`, `editar`, `excluir`, `listar`, `exportar` — e não apenas a de leitura. A célula que
mais escapa é *"entidade excluída/desativada × operação de escrita"*: os cenários provam que ela
some da listagem e ninguém prova que ela **deixou de funcionar**.

**A matriz cobra as duas metades.** "Toda célula vazia vira cenário negativo" é só metade da
regra — e seguir só ela deixa colunas inteiras sem **nenhuma operação bem-sucedida**. O caso
concreto: a coluna `editar` fica com três recusas e nenhuma edição que funciona, e a armadilha da
unicidade contra o próprio registro passa inteira. Cada coluna precisa de **ao menos uma célula
válida exercitada**, e é ela que se liga ao
[gate de tela de escrita](#escolha-de-camada-em-laravelfilament).

**A matriz não é bidimensional.** `estado × operação` é a face visível; **quem** executa e **qual
campo** muda são dimensões, não detalhes do exemplo:

| Dimensão | Fixar significa perder |
|---|---|
| **estado** | transição ilegal |
| **operação** | ação sem barreira |
| **persona** | autorização inteira — percorrer toda a matriz com o dono do registro deixa a barreira de identidade sem um único cenário |
| **campo alterado** | a regra que depende do campo. Editar sempre "a descrição" deixa sem cenário justamente o campo que decide o fluxo (valor, centro de custo, papel) |

Percorrer estado × operação com persona e campo fixos produz uma matriz **"100% coberta"** com
duas dimensões intocadas. Escolher a persona e o campo é escolha **discriminante** — vale a mesma
regra dos valores: fixe o que revela a diferença, não o que é conveniente.

**A dimensão do campo tem de ser exercitada FORA do estado inicial.** Trocar o campo decisivo em
`rascunho`, onde tudo ainda é editável, não reabre a dimensão para os estados de trânsito — e é
exatamente ali que mora o defeito de recomputação (alterar o valor depois do envio sem reavaliar a
alçada). A linha inválida de `editar` precisa afirmar o **valor gravado**, não só que a operação
foi recusada.

**Célula só conta se a operação daquela célula for executada.** Apontar para um cenário que
executa **outra** operação — a listagem no lugar do detalhe, o `rascunho` no lugar do estado em
trânsito — é falso ✅. E **argumentar** que "uma implementação correta se comportaria igual nas
duas linhas" não é executar: o argumento pressupõe a corretude que a célula existe para testar.

**Verbo irmão não herda evidência.** Quando a regra diz "aprova **ou** rejeita", "edita **ou**
exclui", "publica **ou** arquiva, a autorização precisa ser falsificada em **cada verbo**. Uma
implementação que confere o ator em `aprovar()` e esquece em `rejeitar()` passa em todo conjunto
cuja evidência de autorização venha só do primeiro verbo — e o checklist lê ✅ cobrindo metade da
regra.

### Efeito idempotente: ancorar no agregado, não no recurso

Para verificar idempotência, a assertion vai sobre **o que sofre o efeito**, não sobre o que é
consumido. Aplicar o mesmo cupom duas vezes: o oráculo é *"o total do pedido é o mesmo depois da
segunda aplicação"*, não *"o contador do cupom foi a 2"*. Ancorar no recurso consumido prova
contabilidade e não prova idempotência.

**E o agregado tem de ser o persistido, não o retorno da chamada.** Se o `Então` afirma sobre o
valor **devolvido** por duas chamadas independentes, o cenário passa por construção quando o motor
é uma função pura — o mutante "acumula" nem sequer é expressável ali. O cenário só falsifica se
aplicar duas vezes **ao mesmo registro persistido** e afirmar sobre o estado dele.

**Quando o agregado está fora de escopo**, o cenário de idempotência é **inexpressável** — e
escrevê-lo assim mesmo produz um caso tautológico que parece cobertura. O procedimento é: **não
escrever o cenário**, registrar como **lacuna declarada** vinculada à premissa que tirou o agregado
do escopo, e transformá-la em **pergunta ao usuário**. Idempotência que não se pode ancorar não é
lacuna do conjunto — é consequência de uma decisão de escopo que alguém precisa confirmar.

### O exemplo tem de ser **discriminante**

Um cenário só mata um mutante se os **valores escolhidos** distinguem a implementação certa da
errada. Valor redondo é a forma mais comum de um cenário parecer cobrir e não cobrir.

Antes de fixar cada valor de um `Exemplos:`, perguntar: **a implementação defeituosa produziria
um resultado diferente com este valor?** Se produz o mesmo, o exemplo é decorativo.

| Defeito | Valor que **não** discrimina | Valor que discrimina |
|---|---|---|
| percentual em `float` em vez de inteiro | 10% de 10.000 → 1.000 nos dois | **29% de 10.000** → `(int)(10000*0.29)` = 2.899, inteiro dá 2.900 |
| arredondamento vs truncamento | qualquer divisão exata | resto ≠ 0 (5% de 50 → 2 ou 3) |
| off-by-one em limite | 1 e 10 num limite de 3 | **2, 3, 4** |
| unicidade sem normalização | `PROMO10` × `BLACKFRIDAY` | `PROMO10` × `promo10` × `" PROMO10 "` |
| ordenação instável | 3 registros distintos | dois registros **empatados** na coluna de ordenação |
| **autorização por identidade** | solicitante = gestor = quem chama, tudo na **mesma pessoa** | três pessoas distintas, e o ator sendo cada uma delas por vez |
| **canal do efeito** | "uma notificação foi enviada" | o **canal** que o requisito nomeia (`mail`, e não `database`) |
| **valor do requisito parametrizado** | injetar o limite por `config()` em todo cenário | ao menos um cenário com o **número literal do requisito** |

As três últimas linhas são a versão não-numérica do valor redondo. **Persona colapsada** é o caso
mais comum: quando o mesmo usuário é dono, aprovador e chamador, nenhuma barreira de identidade é
exercitada, e todo cenário passa com a autorização removida.

Isto vale sobretudo para **precisão numérica e representação**, onde a implementação errada
acerta a maioria dos valores por acidente. Medido: um conjunto marcou "precisão monetária" como
coberta, citou dois cenários, e **nenhum dos cinco exemplos numéricos distinguia `float` de
inteiro** — o item ficou ✅ no checklist com o defeito intacto, que é pior que lacuna declarada,
porque ninguém volta a olhar.

**O parâmetro livre nem sempre é o dado de entrada.** Em defeito de contexto — fuso, relógio,
locale, tenant — o que precisa cair na janela de divergência é o **instante ou o ambiente da
observação**, não o valor do formulário. Escolher o instante "bonito" é o mesmo erro do valor
redondo:

| Defeito de contexto | Parâmetro livre | Janela em que é observável |
|---|---|---|
| validade lida em UTC com app em `America/Sao_Paulo` | **o instante da aplicação** | as 3 h de deslocamento — testar às 20:00 não distingue nada; às 23:30 sim |
| virada de dia | o instante | os minutos ao redor da meia-noite **do fuso do app** |
| locale na formatação/ordenação | o locale ativo | um em que a ordem ou o separador difere (`pt_BR` × `en_US`) |
| escopo por tenant | o tenant do ator | um recurso que existe **no outro** tenant |

Antes de fixar o instante ou o ambiente, calcular **onde as duas implementações divergem** e
escolher um ponto lá dentro. E o `Então` precisa afirmar mais do que "aceito": o valor comparado,
o registro ou o estado.

### Fechar uma lacuna declarada sem discriminar é **piorar**

Ao converter uma lacuna declarada em cenário, o gate é mais duro que o normal: **provar que o
novo cenário discrimina**, escrevendo em uma linha por que a implementação defeituosa produz
resultado diferente ali. Se não discriminar, a lacuna deixa de ser **declarada** (dívida que
alguém conhece) e vira **cega** (item ✅ com o defeito dentro) — regressão, mesmo que a contagem
de cenários suba.

> Medido: entre duas rodadas, o fuso horário saiu de *lacuna declarada com quatro tentativas
> registradas* para *item ✅ do checklist apontando um cenário que não mata o mutante*. A taxa de
> detecção não mudou; a honestidade do conjunto, sim — para pior.

### Impossibilidade de arnês é hipótese, não conclusão

Antes de declarar um mutante como "sem matador porque o arnês não permite", **tente mudar o
arnês**: `config(['app.timezone' => ...])` para divergir app e banco, `travelTo()` para a virada
do dia, `DB::statement` para pragmas, factory com estado inválido gravado direto. Só depois de
tentar é que a lacuna é real — e aí ela é declarada com **o que foi tentado**.

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
> **`CT-nn`**, **`não se aplica: {motivo}`** ou **`lacuna declarada: {o que foi tentado}`**.

| Gatilho na feature | Cenário obrigatório |
|---|---|
| rota/ação que recebe `{id}` de um recurso | **IDOR / autorização horizontal**: usuário A pede o recurso de B → 403/404. Dois usuários no setup |
| autorização declarada em policy/permission | **a ação disparada fora do caminho feliz** — não basta afirmar `can()`. Policy correta que o Resource nunca consulta passa em todo teste de `can()` |
| qualquer operação de escrita | **idempotência**: a mesma requisição duas vezes (duplo clique, retry, webhook redundante), com a assertion **no agregado afetado** |
| campo cujo domínio depende de outro campo | fronteira **por combinação** (tipo × valor), não fronteira do campo isolado |
| todo campo, em **todo ponto de entrada** | valor abaixo do mínimo, acima do máximo e no limite — **na gravação**, não só no uso |
| contador, saldo, estoque, limite de uso | **concorrência**: duas execuções simultâneas não ultrapassam o limite |
| campo opcional | **ausente ≠ `null` ≠ `""`** — três casos, com semântica declarada |
| listagem | **paginação**: 0, 1, limite, além do limite; e item inserido entre a página 1 e a 2 |
| ordenação por coluna | coluna inexistente (injeção via `orderBy`), coluna nullable, empate sem desempate determinístico |
| data/hora | **timezone do app × do banco × do usuário**; virada de meia-noite; DST; `date` comparado com `datetime` |
| texto livre | acento, emoji (4 bytes), string no limite do `varchar`, só espaços, espaços nas bordas |
| unicidade + `SoftDeletes` | criar → excluir → recriar com o mesmo valor único |
| CRUD | ler/editar/excluir ID inexistente; excluir duas vezes; editar sem alterar nada |
| formulário/payload | **mass assignment**: enviar campo não previsto (`is_admin`, `user_id`, `status`) e provar que é ignorado |
| upload | 0 byte, extensão que mente sobre o conteúdo, acima do limite |
| valor monetário | inteiro em centavos ou `decimal`; **nunca `float`**; arredondamento na borda de centavo |

> Esta tabela é **viva**: todo defeito que escapou para produção e gerou retrabalho deve virar
> uma linha aqui, no `.ai/rules/` do projeto. Taxonomia alimentada pelo histórico do próprio
> projeto é o item de maior alavancagem do pipeline inteiro.

### Passo 5 — Escrever os cenários em Gherkin

**Gherkin como linguagem de especificação, sem runner.** Não existe plugin Gherkin viável para
Pest, e Behat exigiria uma ponte Laravel abandonada. Os cenários vivem no markdown e são
traduzidos para `describe()`/`it()` do Pest.

> Isso não é meio-caminho: o BDD se decompõe em *Discovery → Formulation → Automation*, e a
> Formulation entrega valor sozinha. O próprio criador do Cucumber é explícito: quem só precisa
> executar teste não deve usar Cucumber.

**Estrutura:**

```gherkin
# language: pt
Funcionalidade: {título da feature}

  Regra: {a regra de negócio, em uma frase afirmativa}

    Cenário: [CT-01] {o comportamento, não o procedimento}
      Dado {estado inicial}
      E {mais estado}
      Quando {a única ação}
      Então {resultado observável}
      E {mais resultado}
```

**Regras de escrita — cada uma corrige um anti-padrão catalogado:**

| Regra | Anti-padrão que evita |
|---|---|
| **Declarativo, não imperativo**. Teste: *"esta frase precisa mudar se a implementação mudar?"* Se sim, reescrever | cenário que descreve cliques e campos, e quebra a cada mudança de UI |
| **Um único `Quando` por cenário** | cenário que testa dois comportamentos e não diz qual falhou |
| **3 a 5 passos; nunca mais de 9** | setup mecânico que esconde a regra |
| **Ator nomeado em 3ª pessoa** (`o coordenador`, `o comprador`), nunca "eu" | ambiguidade de quem faz a ação |
| **`Então` sobre saída observável**, com o valor concreto | `Então funciona` — que não é oráculo |
| **Cenário de recusa afirma o não-efeito.** "Recusado" sozinho não basta: afirmar também que o estado **não** mudou e que nenhum registro/notificação foi criado | implementação que recusa **depois** de gravar passa no cenário |
| **Nenhum termo de domínio não definido no `Então`** — use o campo, o estado ou o valor | `Então o aprovador da vez é o Rui` / `Então o acesso é concedido` (que é `assertOk` com outro nome) |
| **Título descreve o comportamento** | `Cenário: teste 3` / `Cenário: criar, editar e excluir` |
| **Sem detalhe incidental** — só os dados que afetam a regra | dado mágico que invalida o cenário quando muda |
| **Cenários independentes**, executáveis em qualquer ordem | cenário que só passa depois do anterior |
| **`Esquema do Cenário` só para classes de equivalência** | matriz combinatória disfarçada de tabela |
| **`Contexto` (Background) no máximo 4 linhas, só `Dado`** | precondição invisível para quem lê o cenário no meio |

**`Esquema do Cenário` é a forma canônica de expressar EP e BVA** — cada linha de `Exemplos`
é uma partição ou um valor de borda, com o rótulo dizendo qual:

```gherkin
    Esquema do Cenário: [CT-04] o limite de usos é inclusivo no último uso
      Dado um cupom com limite de <limite> usos e <ja_usado> usos já feitos
      Quando o comprador aplica o cupom
      Então o resultado é "<resultado>"

      Exemplos:
        | limite | ja_usado | resultado | # borda    |
        | 3      | 1        | aceito    | dentro     |
        | 3      | 2        | aceito    | borda−1    |
        | 3      | 3        | recusado  | borda      |
        | 3      | 4        | recusado  | borda+1    |
```

### Passo 6 — Gate de falsificabilidade (OBRIGATÓRIO)

Para **cada `Regra:`**, escrever as implementações erradas plausíveis e apontar o cenário que
morre com cada uma.

```markdown
#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|--------------------------------|------------------|
| M1 | `<` no lugar de `<=` no limite de usos | CT-04 (linha "borda") |
| M2 | contador incrementado antes de validar | CT-06 |
| M3 | contador não incrementado no sucesso | CT-05 |
| M4 | limite lido do cupom mas não comparado | CT-04 |
```

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

**Fonte dos mutantes** — os operadores que as ferramentas de mutação usam de verdade, porque são
os erros que os humanos cometem:

| Operador | Mutante | Lacuna de derivação correspondente |
|---|---|---|
| relacional | `>` ↔ `>=`, `<` ↔ `<=`, `==` ↔ `!=` | falta BVA na fronteira |
| lógico | `&&` ↔ `\|\|`, condição negada | falta linha da tabela de decisão |
| retorno | `return $x` → `return null` / `true` → `false` | assertion ausente ou fraca sobre o retorno |
| **remoção de chamada** | o `Mail::send`, o `Log::`, o `->increment()` some | falta assertion de efeito colateral |
| literal | número → `0`/`1`, string → `''`, array → `[]` | valor mágico não verificado |
| aritmético | `+` ↔ `-`, `*` ↔ `/` | falta assertion sobre o **valor** calculado |

> Este é o passo que responde à pergunta que motiva a skill. Um conjunto de CT que não declara
> o que ele mata não tem como ser auditado — e é assim que o requisito fica ✅ na Matriz de
> Rastreabilidade com o defeito passando batido.

### Passo 7 — Alocar camada e podar

0. **Desempate da camada: ela sai do observável que o requisito afirma, não da estrutura provável
   do código.** Decidir "isto é `Unit` ou `Feature`" perguntando *"existiria um predicado puro
   para isso?"* é palpite de implementação — exatamente o que o princípio 1 proíbe. A pergunta
   certa é: **o que o `Então` afirma?** Valor calculado → `Unit`. Registro no banco, autorização,
   efeito colateral → `Feature`. Elemento de tela → componente. Pixel, JS, acessibilidade →
   `Browser`. Se o requisito não determina onde o comportamento vive, a camada é a **mais externa
   observável**, não a mais barata imaginável

1. Cada cenário recebe a **camada mais barata que existe no projeto** e o falsifica
   (ver [tabela](#escolha-de-camada-em-laravelfilament)). "Que existe no projeto" não é detalhe:
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

**Um `Esquema do Cenário` conta como 1 cenário, não como N linhas.** Sem essa regra, o teto e a
exigência de "100% das células inválidas da tabela de estados" ficam aritmeticamente
incompatíveis — 21 células contra teto de 5. A tabela de `Exemplos` é a forma canônica de
expressar partição, borda e célula de matriz **dentro** de um cenário; contar cada linha como um
cenário puniria exatamente a técnica que a skill quer.

Estourar o teto é permitido **com justificativa escrita** — normalmente significa que a regra
deveria ser duas. E o [gate do passo 6 vence o teto](#passo-6--gate-de-falsificabilidade-obrigatório):
mutante vivo é pior que cenário a mais.

4. **Registrar o que foi cortado.** Quando há mais candidatos que teto — o caso normal no `05`,
   onde o gate é generoso e o teto é apertado —, escrever uma tabela de **cogitado e cortado**:

| Cenário cogitado | Por que foi cortado |
|---|---|
| {…} | já provado por CT-07, mais barato |
| {…} | mata o mesmo mutante que CT-B01 |
| {…} | não mata nenhum mutante previsto |

Sem essa tabela, "só há 2 CT-B" é indistinguível de "só pensamos em 2", e a próxima pessoa
refaz a análise do zero.

### Precedência: Project Rule do projeto vence a skill

Quando uma instrução desta skill colidir com uma rule em `.ai/rules/` do projeto, **a rule vence** —
ela é medição local, a skill é generalização. O caso concreto: a skill sugere `pest --parallel --tia`
como padrão, e um projeto pode ter medido que `--parallel` derruba os CT-B e que sem PCOV o `--tia`
não termina.

Obrigatório: **declarar a divergência** em uma linha no `04`, dizendo qual rule venceu e por quê.
Divergência silenciosa entre skill e rule é a forma mais fácil de a wiki descrever um comando que
ninguém consegue rodar.

---

## Escolha de Camada em Laravel/Filament

Em Laravel + Filament, **a maior parte do que parece exigir browser é teste de componente
Livewire** — milissegundos, sem Node, sem Playwright. Empurrar UI para o browser é a decisão
que mais destrói o orçamento de teste de uma feature.

| O cenário afirma sobre… | Camada | API |
|---|---|---|
| cálculo, regra pura, value object | `Unit` | `expect()`, `toThrow()`, datasets |
| persistência, autorização, efeito colateral | `Feature` | `assertDatabaseHas`, `assertForbidden`, `Queue::fake`, `Mail::fake` |
| validação de formulário Filament | Livewire | `fillForm([...])` → `assertHasFormErrors([...])` |
| gravação pelo formulário | Livewire | `->call('create')` / `->call('save')` + `assertDatabaseHas` |
| listagem, busca, ordenação, filtro | Livewire | `assertCanSeeTableRecords`, `searchTable`, `sortTable`, `filterTable` |
| ação de tabela ou de página | Livewire | `callAction(TestAction::make(X::class)->table(), [...])` |
| notificação exibida | Livewire | `assertNotified()` |
| visibilidade condicional de campo/coluna/ação | Livewire | `assertFormFieldHidden`, `assertTableColumnHidden`, `assertActionHidden` |
| autorização na tela | Livewire | `livewire(...)->assertForbidden()` |
| wizard multi-etapa | Livewire | `goToNextWizardStep()`, `assertWizardCurrentStep()` |
| comportamento dependente do tempo | `Feature` | `travelTo()`, `freezeTime()` |
| **JavaScript executado** (modal que não abre, Alpine, atalho) | **Browser** | — |
| **console limpo / erro de JS** | **Browser** | `assertNoSmoke()`, `assertNoJavaScriptErrors()` |
| **acessibilidade** | **Browser** | `assertNoAccessibilityIssues()` |
| **cor, tema, layout** | **Browser** | `inDarkMode()`, `assertScreenshotMatches()` |

> **Regra do par** (aprendida em produção): *uma tela aberta não é uma tela que grava.* Um `GET`
> pode ficar verde com o salvamento quebrado. Toda tela de escrita gera **dois** cenários — a
> visita **e** a gravação por componente.

**Gate de tela de escrita (obrigatório).** Para **toda** rota `create` / `edit` da tabela
`## Superfície de UI` do PRD, é obrigatório existir um cenário de **gravação por componente**
(`fillForm` → `->call('create'|'save')` → `assertDatabaseHas` com os campos que importam).
Tela de escrita coberta apenas por visita é **lacuna de gate**, não decisão de escopo.

> Medido num kit real: 45 telas cobertas por `visit($rotas)->assertNoJavaScriptErrors()`, das
> quais **5 telas `create` não tinham gravação testada em lugar nenhum** — exatamente o defeito
> (`Select::make('roles')` derrubando o `save` com o `GET` verde) que a regra do projeto fora
> escrita para prevenir.

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

| Escrever | Em vez de (`@deprecated`) |
|---|---|
| `assertSchemaStateSet` | `assertFormSet` |
| `callAction(TestAction::make(X::class)->table(), [...])` | `callTableAction` |
| `assertActionExists` | `assertTableActionExists` |

**Confirmar no vendor antes de escrever**, não na memória:
`grep -rn "@deprecated" vendor/filament/*/.stubs.php`.

---

## Arquivo 04: Casos de Teste

**Path**: `wikis/specs/{branch}/{feature}/04-casos-de-teste.md`

```markdown
# Casos de Teste — {Card}: {Título}

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| {cálculo do desconto} | 3 | 3 | 9 | completo |
| {listagem} | 1 | 1 | 1 | mínimo |

- Técnicas aplicadas: {EP, BVA 3-valores, tabela de decisão, tabela estado × evento}
- Cenários: {n} · Regras: {n} · Mutantes previstos: {n} · Sem matador: {n}

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | {…} | — |
| F | {…} | CT-01, CT-02 |
| D | {…} | CT-03…CT-08 |
| I | {…} | CT-09 |
| P | {não se aplica: sem dependência de plataforma além do banco} | — |
| O | {…} | CT-10 |
| T | {…} | CT-11, CT-12 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — {…} | cálculo (completo) | RQ-01, RQ-04 | BVA 3-valores | CT-01…CT-04 |
| R2 — {…} | listagem (mínimo) | RQ-02 | tabela de decisão | CT-05, CT-06 |

<!-- Técnica escalada acima do perfil da área: declarar aqui, em uma linha, com o motivo. -->

## Fronteira com o Plano

<!-- O que veio do 01-plano-acao.md e foi RECUSADO como oráculo, para o cenário não virar
     teste do PRD. Item que só o PRD determina e é visível ao usuário vira pergunta. -->

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| {nome do método `aplicarEm()`} | escolha de implementação | detalhe do cenário |
| {texto do erro na tela} | comportamento visível que o requisito não determina | pergunta ao usuário |

**Perguntas em aberto** (replicadas em `00-requisito.md` → `## Ambiguidades`):
- {pergunta} — bloqueia R{n}; premissa adotada: {…} (cenários marcados `@premissa`)

## Setup Global

### Personas
- `{papel}` — {como criar, com o helper real do projeto}

### Fixtures
- `{Model}::factory()->{state}()` — {estado}

### Fakes
- `Queue::fake()` / `Mail::fake()` / `Notification::fake()` / `Http::fake()` + `Http::preventStrayRequests()`

### Estratégia de DB
- {`RefreshDatabase` global no `tests/Pest.php`, ou o que o projeto usa}

---

## Regra R1 — {enunciado da regra}

> `RQ-01`, `RQ-04` · perfil **completo** · técnica: **BVA 3-valores** (fronteira: {campo}, granularidade {tipo})

```gherkin
# language: pt
Funcionalidade: {…}

  Regra: {…}

    Cenário: [CT-01] {comportamento}
      Dado {…}
      Quando {…}
      Então {…}
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | {…} | CT-01 |
| M2 | {…} | ⚠️ **sem matador** — {motivo / lacuna declarada} |

---

## Checklist de Taxonomia

<!-- Resposta válida: um ID de cenário, "não se aplica: {motivo}", ou
     "lacuna declarada: {o que foi tentado}". NUNCA "sim". -->

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | CT-09 |
| Autorização exercida na ação (não só `can()`) | CT-19 |
| Idempotência (ancorada no agregado) | CT-07 |
| Concorrência | CT-08 |
| **Fronteira no ponto de entrada** (gravação) | CT-30, CT-31 |
| **Domínio condicionado** (tipo × valor) | CT-08 |
| **Estado × operação de escrita** (excluído ainda funciona?) | CT-21 |
| Ausente ≠ null ≠ vazio | não se aplica: sem campo opcional |
| Paginação / ordenação | … |
| Timezone / DST | lacuna declarada: tentado `config(['app.timezone'])` divergente; {resultado} |
| Unicode / limite de varchar | … |
| Unicidade + soft delete | … |
| CRUD combinado | … |
| Mass assignment | … |
| Upload | não se aplica: sem upload |
| Precisão monetária | CT-02 |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | {…} | R1 | BVA | Unit | `tests/Unit/...` | M1 |
| CT-09 | {…} | R4 | matriz papel×ação | Feature | `tests/Feature/...` | M8, M9 |

## Sem CT-B

<!-- só quando o gate do 05 não passar -->
- Motivo: {…}
```

---

## Arquivo 05: Casos de Teste de Browser — Condicional

**Path**: `wikis/specs/{branch}/{feature}/05-casos-de-teste-browser.md`

### Gate — quando criar

Criar **somente** se houver linha em `## Superfície de UI` do PRD **e** o cenário afirmar sobre
algo que **só o navegador prova**: JavaScript executado, console/erro de JS, acessibilidade,
cor/tema, layout. Se o cenário puder ser provado por componente Livewire, ele pertence ao `04`.

Se o gate não passar: **não criar o arquivo** e registrar no `04` a seção `## Sem CT-B` com o motivo.

### Fatos do `pest-plugin-browser` que mudam o que se escreve

Estes contradizem crenças comuns e cada um já custou tempo em projeto real:

1. **O plugin sobe o próprio servidor** — HTTP in-process, porta aleatória. **Nada** de Herd,
   `artisan serve`, Sail ou Vite dev server; nada de `APP_URL` a configurar.
2. Como é o **mesmo processo**, valem dentro do navegador: `DB_DATABASE=:memory:`,
   `RefreshDatabase`, **`$this->actingAs($user)` antes do `visit()`** e `assertAuthenticated()`.
   **Use `actingAs()`** — login pela tela custa dezenas de segundos por cenário. Reserve um
   único cenário para o formulário de login, que é o caminho real do usuário.
3. **Nunca `wait($segundos)`.** O plugin reexecuta cada assertion até o teto de
   `pest()->browser()->timeout()`. Espere pelo **estado final visível**. Não existem
   `waitForText`, `waitForSelector`, `waitUntil` — não invente.
4. **`assertPathIs` antes das asserções de conteúdo.** Depois de qualquer ação que navegue
   (`press`, `click`), ela vem primeiro — é ela que espera a navegação. Invertido, o `assertSee`
   é avaliado contra o snapshot da página anterior e falha **com a ação tendo funcionado**.
5. **`npm run build` é pré-requisito duro.** Sem `public/build/manifest.json` toda tela responde
   `ViteException` e todo cenário falha por um motivo que não é o dele.
6. **Nunca `--parallel` com browser** — multiplica processos de navegador e produz timeout. E
   como `--tia` exige run completo, `--parallel --tia` e os CT-B não convivem numa invocação só.
   São dois comandos.
7. **`assertNoSmoke()` só em tela de autoria própria.** Em tela de plugin de terceiro use
   `assertNoJavaScriptErrors()`, senão a suíte fica vermelha por `console.log` alheio.
8. **`visit([...])` em lote aborta na primeira falha** — as rotas seguintes não são verificadas
   naquele run. Para colher todos os problemas, um cenário por painel.
9. Upload é **`attach()`**, não `upload()`.
10. **`assertSee` não valida tema**: passa com texto branco em fundo branco. Para defeito de cor
    não há saída barata — é screenshot e olhar.

### Seletores

Preferir `data-testid` / `aria-label` / texto visível a classe de CSS. Se o projeto não tem
`data-testid`, registrar como dívida e usar o que existe — em Filament, o `id` gerado do campo
(`#form\.email`, com o `.` escapado) e o texto **traduzido** do rótulo.

```markdown
# Casos de Teste de Browser — {Card}: {Título}

> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor.
> Comando: `vendor/bin/pest --testsuite=Browser` (em série — nunca `--parallel`)

## Pré-requisitos
- [ ] `npm run build` executado
- [ ] `tests/Browser/Screenshots` no `.gitignore`
- [ ] Autenticação por `$this->actingAs($user)` — {ou o helper do projeto}

## Seletores
| Elemento | Seletor | Já existe? |
|---|---|---|

---

## CT-B01: {o que só o navegador prova}

**Por que browser e não Livewire**: {a asserção depende de JS executado / acessibilidade / cor}

```gherkin
# language: pt
  Cenário: [CT-B01] {…}
    Dado {…}
    Quando {…}
    Então {…}
```

**Roteiro executável**
| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | | `visit('/…')` | |
| 2 | | `->press('…')->assertPathIs('/…')` | |

**Assertions**: `assertPathIs` primeiro · `assertNoJavaScriptErrors()` · uma única âncora de persistência

#### Mutantes previstos
| # | Implementação errada plausível | Cenário que mata |
|---|---|---|

---

## Roteiro de Validação: Desenhado × Implementado

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
```

---

## Armadilhas de API que Invalidam CT

Cada linha já produziu teste vermelho sem defeito no código — ou verde sem provar nada.

| Armadilha | Consequência |
|---|---|
| `Mail::assertSent` em mailable `ShouldQueue` | nunca passa — é `assertQueued` |
| `Event::fake()` **antes** das factories | eventos de model (uuid em `creating`) não rodam; fixture nasce quebrada |
| `Http::fake()` sem stub | devolve 200 vazio e o teste passa sem provar nada — use `Http::preventStrayRequests()` |
| `withoutExceptionHandling()` + `assertForbidden()` | o 403 vira exceção lançada; a assertion nunca roda |
| `RefreshDatabase` + job `->afterCommit()` | tudo roda em transação, o job não despacha |
| `travel()` sem `travelBack()` nem closure | vaza para os testes seguintes; flake em `--parallel` |
| `Repeater::fake()` / `Builder::fake()` ausentes no Filament | UUID aleatório quebra `assertSchemaStateSet` |
| helper de teste declarado fora do `tests/Pest.php` e usado por 2 arquivos | `Call to undefined function` em `--parallel`, `--tia` ou arquivo isolado |
| `assertDatabaseHas` só com a chave primária | passa com todos os outros campos errados |
| `Log::spy()` citado como API oficial | é o mecanismo genérico de Facade Spy — funciona, mas não é doc |

---

## Fechamento do Ciclo com Mutation Testing

O passo 6 **prevê** os mutantes. Depois de implementar, `pest --mutate` **mede** — mas mede uma
coisa só, e é preciso saber qual.

### O que o mutation score NÃO responde

> **Mutation testing só muta código que existe.** Se a cláusula do requisito nunca virou código —
> não há `if ($percentual > 100)` para mutar —, **nenhum mutante é gerado e o score não cai**.
> Ele é estruturalmente **cego à omissão**, que é justamente a classe de defeito mais cara.

Medido em experimento controlado, contra a **mesma** implementação:

| | Suíte derivada por gabarito | Suíte derivada pelo pipeline |
|---|---|---|
| Mutation score na classe sob teste | **100%** (24/24) | **100%** (24/24) |
| Defeitos plantados detectados (juiz cego, 18 no total) | **7** | **12** |

As duas suítes mataram **todos** os mutantes, e uma detecta 71% mais defeito que a outra. A razão:
os defeitos que as separam são **comportamentos ausentes** — validação que ninguém escreveu,
transição que ninguém barrou. Não existe linha para mutar.

**Conclusão operacional**: o mutation score é um **piso de qualidade de assertion**, não um
indicador de cobertura de requisito. Quem responde por omissão é a rastreabilidade `RQ` → cenário
(passo 2) e o gate de mutantes **de especificação** (passo 6) — que nascem do requisito, não do
código, e por isso enxergam o que não foi escrito.

### Como rodar (comandos verificados)

```bash
vendor/bin/pest tests/Feature/{Feature} --mutate --path=app/Services
vendor/bin/pest tests/Feature/{Feature} --mutate --path=app/Services --min=70
```

- Exige driver de cobertura (**PCOV ou Xdebug** com `XDEBUG_MODE=coverage`)
- **Confirmar que `pestphp/pest-plugin-mutate` está declarado no `composer.json`.** Ele costuma
  aparecer em `vendor/` como dependência transitiva do Pest 5 — o comando funciona por acidente da
  árvore de dependências e some num `composer update`. Se estiver só transitivo, incluir
  `composer require pestphp/pest-plugin-mutate --dev` como passo no PRD
- **`pest()->mutate()` em `Pest.php` não existe** — não inventar
- **Armadilha verificada: `covers(X::class)` restringe o que conta como coberto.** Mutantes em
  qualquer classe fora do `covers()` são reportados como `uncovered` e o score vai a **0%** —
  mesmo que os testes executem aquele código em toda chamada. Para medir uma classe vizinha,
  declare-a em `covers()`/`mutates()` ou meça em execução separada
- `--class='App\Services\X'` pode não casar; **`--path=` é o filtro que funciona de forma confiável**
- Escopar sempre: mutar o projeto inteiro é caro e devolve ruído

**Cada mutante sobrevivente é traduzido de volta para a lacuna de derivação** e vira cenário novo:

| Mutante sobreviveu | Lacuna | O que escrever |
|---|---|---|
| `>` → `>=` | BVA faltando | cenário na borda exata |
| `&&` → `\|\|` | linha da tabela de decisão faltando | cenário da combinação |
| `return $x` → `return null` | oráculo fraco | assertion sobre o **valor** |
| chamada removida | efeito colateral não verificado | cenário de rastreio de efeito |

> **Nunca usar cobertura de linha como meta de qualidade.** Com o tamanho da suíte controlado,
> ela não prevê eficácia — 100% de linha é compatível com zero assertion útil. O indicador é o
> mutation score.

---

## Revisão Adversarial (obrigatória no perfil completo)

Delegar a um **sub-agente que não derivou os cenários**, com este contrato:

```text
Entrada: 00-requisito.md + 04-casos-de-teste.md (e 05, se houver)
NÃO receber: o PRD, o código, nem o raciocínio de quem derivou

Tarefa: PROVAR que este conjunto deixa passar um defeito.
  1. Escreva 5 implementações erradas plausíveis que passariam por TODOS os cenários
  2. Para cada uma, aponte a regra afetada e a técnica de derivação que faltou
  3. Aponte todo cenário cujo "Então" é fraco — isto é, que passaria com a
     implementação defeituosa (assertOk sozinho, assertSee de layout,
     assertDatabaseHas só com a chave, ausência de assertion sobre o valor)
  4. Aponte todo cenário sem nenhum "Então" e todo cenário com mais de um "Quando"

Saída: lista de lacunas, cada uma com a regra, a técnica faltante e o cenário sugerido.
PROIBIDO: elogiar o conjunto, reescrever os cenários, dizer "está bom".
```

**O que fazer com os achados** (a revisão não termina na lista):

1. **Fechar todos** — cada lacuna vira cenário novo, ou oráculo reescrito, ou lacuna declarada com motivo
2. **Re-revisar uma única vez**, e só se o fechamento tiver criado **cenário novo** (não se apenas reforçou oráculo existente). Cenário novo introduz superfície nova, e é aí que mora a lacuna de segunda ordem
3. **Teto de 2 rodadas.** Se a segunda rodada ainda trouxer achado estrutural, o problema não é o conjunto — é a regra, que provavelmente deveria ser duas. Registrar e escalar

Registrar no `04` quantos achados a revisão produziu e o que virou cada um. Revisão adversarial
cujos achados ninguém fecha é teatro caro.

> **Não autorrevisar.** Modelos de linguagem são comprovadamente melhores em **gerar** oráculos
> do que em **classificar** se um oráculo está correto — o mesmo agente conferindo o próprio
> conjunto reproduz o viés que o gerou.

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
8. **Não editar o `00-requisito.md`** a não ser para acrescentar pergunta em `## Ambiguidades`.
9. **Não autorrevisar** o conjunto no perfil completo.
10. **Não usar cobertura de código como critério de suficiência.** "Todo método público tem ao
    menos 1 CT" e "cada branch tem um CT" são critérios sobre um código que **ainda não existe**
    no momento da derivação — seguir isso obriga o agente a imaginar a implementação e testá-la,
    que é a definição de teste tautológico. O critério de suficiência aqui é: **toda regra tem
    seus mutantes previstos mortos**.

---

## Checklist Final

### Derivação
- [ ] Perfil de esforço definido por área, com P×I registrado
- [ ] Varredura SFDIPOT preenchida; dimensão vazia **declarada** com motivo
- [ ] Mapa de regras montado; toda `RQ` do `00` gerou ao menos uma regra ou uma justificativa
- [ ] Perguntas em aberto replicadas no `00-requisito.md` e cenários dependentes marcados `@premissa`
- [ ] Técnica formal escolhida e **nomeada** por regra
- [ ] BVA com o incremento do tipo certo (`0,01` em decimal, 1 dia em date)
- [ ] Entidade com `status` → tabela **estado × evento**, com 100% das células inválidas no perfil completo
- [ ] Partições inválidas isoladas uma por cenário
- [ ] Checklist de taxonomia percorrido item a item, com dispensa justificada

### Escrita
- [ ] Cenários em Gherkin pt-BR: `Funcionalidade` → `Regra` → `Cenário`
- [ ] Um único `Quando` por cenário; 3–5 passos; ator nomeado em 3ª pessoa
- [ ] Todo `Então` afirma saída observável com valor concreto
- [ ] `Esquema do Cenário` só onde há classe de equivalência ou borda, com a coluna do rótulo

### Gate
- [ ] **Toda regra declara ≥2 mutantes** (≥3 no perfil completo)
- [ ] Todo mutante tem cenário matador **ou** lacuna declarada com motivo
- [ ] Cenário que não mata mutante nenhum foi cortado ou justificado
- [ ] Cada cenário na camada mais barata que o prova
- [ ] Teto do perfil respeitado, ou estouro justificado
- [ ] Revisão adversarial executada por sub-agente independente (perfil completo)

### Pós-implementação
- [ ] `pest --mutate --covered-only --class={escopo da feature}` executado
- [ ] Mutante sobrevivente traduzido em lacuna de derivação e convertido em cenário novo
- [ ] Índice de cenários atualizado com o arquivo de teste real de cada CT

---

## Skills Companheiras

| Skill | Relação |
|---|---|
| `feature-wiki` | produz o `00`/`01`/`02` e invoca esta skill no step 4; esta devolve o `04` e o `05` |
| `feature-quality-gate` | roteia achado de **destino 3** para cá; usa a Matriz de Rastreabilidade que os IDs desta skill sustentam |
| `ponytail` | a poda do passo 7 é a escada de simplicidade aplicada a teste — sem cortar cobertura de risco |
| `requirement-to-rule` | linha nova do checklist de taxonomia, vinda de defeito real do projeto, é candidata a rule |

> **Caveman**: o `04` e o `05` são **boundary** — prosa normal. Cenário ambíguo produz teste errado.

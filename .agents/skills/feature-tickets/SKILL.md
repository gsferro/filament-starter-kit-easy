---
name: feature-tickets
description: >
  Fatia a wiki de uma feature Laravel em tickets verticais quando o plano não cabe numa sessão.
  Só o usuário invoca (/feature-tickets), por sugestão do step 8 da feature-wiki ou numa
  refatoração larga (renomear coluna, retipar símbolo). Cada ticket é uma fatia vertical (tracer
  bullet): RQ e P-nn do 00, os CT do 04 que ficam verdes com ele, passos do 01, costura de teste,
  arestas de dependência e bloqueio, status. Grava 07-tickets/ depois de um quiz de granularidade,
  acusa RQ ou CT sem ticket, sequencia expand-contract, executa um ticket por sessão nova e gera
  o quadro por script (INDEX.md, 07-tickets/README.md, status). Não fatia o que cabe numa sessão.
  Palavras-chave: tickets, fatiar, fatia vertical, tracer bullet, dependência, bloqueio, sessão,
  expand-contract, refatoração larga, quadro.
license: MIT
compatibility: >
  Wiki da feature-wiki 4.0.0 ou superior (00, 01, 02, 03) com o 04 da feature-test-design 1.16.0 ou
  superior (## Costuras de Teste). scripts/indice.sh e scripts/espelho-gh.sh precisam de bash e php
  no PATH; o espelho no GitHub precisa do gh só para executar (--aplicar).
  disable-model-invocation é extensão do Claude Code; em outro host, a skill roda quando o usuário
  a pede. Sub-agentes opcionais: sem eles, cada ticket roda numa sessão nova. Espelho no GitHub
  Projects opcional, só sob pedido.
metadata:
  version: "1.0.0"
  requires: "feature-wiki>=4.0.0; feature-test-design>=1.16.0"
disable-model-invocation: true
---

# Feature Tickets — Fatias Verticais Ancoradas no Requisito

O `01` da `feature-wiki` fatia por **camada** (migration → model → policy → tela): nada se
demonstra até o último passo, e a feature inteira roda numa sessão. Esta skill troca a unidade de
planejamento por **ticket vertical**: um subconjunto de `RQ` do `00` mais os `CT` do `04` que ele
faz passar. O critério de aceite já existe — é o CT, vermelho antes por construção — e a alocação
única de `RQ` e `CT` acusa omissão antes de implementar ([estudo de 2026-09-26](https://github.com/gsferro/laravel-ai-skills/blob/main/estudos/2026-09-26-agentskills-spec-to-spec-to-tickets.md),
§4.2 e §4.3; as citações "estudo §n" abaixo são desse arquivo).

## Glossário

| Termo | Significado |
|---|---|
| **RQ** | Cláusula de requisito do `00-requisito.md` |
| **P-nn** | Premissa: o que a feature assume sem que o solicitante tenha escrito (`## Premissas` do `00`). É alocada como `RQ` |
| **Qn** | Pergunta numerada, com numeração única na feature para as três raias. A de requisito fica em `## Perguntas ao Solicitante` do `00`; `RQ` com `Estado` = `aberta — Qn` espera a resposta |
| **CT / CT-B** | Caso de teste de backend (`04`) / de browser (`05`) |
| **Costura** | Linha de `## Costuras de Teste` do `04`: onde um grupo de CT se prende ao sistema (`unit de regra` · `Pest feature HTTP` · `componente Livewire/Filament` · `browser`) |
| **Ticket** | `07-tickets/NN-slug.md`: `RQ`/`P-nn` + CT que ficam verdes + passos do `01` envolvidos + arestas + status |
| **Fatia vertical** (*tracer bullet*) | Ticket que atravessa toda camada que os seus `RQ` exigem e se demonstra sozinho, com os bloqueadores feitos |
| **Fronteira** | Tickets `pronto` com todo bloqueador `concluído` e nenhuma `Qn` aberta: o que pode ser pego agora. `pronto` com `Qn` aberta em `**Bloqueado por**` é o ticket *bloqueado por Qn*: o `Status` não tem valor `bloqueado`, e o bloqueio mora no campo |
| **Fora desta entrega** | `RQ` ou `P-nn` que a `## Cobertura do Requisito` do `01` deixa sem passo, com justificativa na `Observação` |
| **Quadro** | A visão dos tickets gerada por script: `07-tickets/README.md` (barra, tabela, grafo Mermaid), `wikis/specs/INDEX.md` e o `indice.sh --status`. Nunca escrita pelo modelo |
| **Sessão nova** | Janela de contexto que começa sem a conversa do planejamento |
| **`{wiki}`** | `wikis/specs/{branch}/{feature}/`; a barra da branch vira subpasta. Ex.: branch `feature/ferro-830`, feature `aprovacao-compra` → `wikis/specs/feature/ferro-830/aprovacao-compra` |
| **`{base}`** | Branch de destino do PR, a mesma do `03` |
| **`{skills}`** | O primeiro dos três diretórios — `.ai/skills/` (Boost), `.claude/skills/` (espelho local), `~/.claude/skills/` (global) — que **contém a skill citada**. `.ai/skills/` pode existir sem ela |

## Índice

- [Quando Invocar](#quando-invocar) · [Entradas e Gate de Entrada](#entradas-e-gate-de-entrada)
- [Procedimento](#procedimento) (1 ler · 2 cortar · 3 prefactoring · 4 fatiar · 5 arestas · 6 quiz · 7 gravar)
- [Regras dos Tickets](#regras-dos-tickets) · [Execução e Cegueira de Fatia](#execução-e-cegueira-de-fatia) · [Fechamento](#fechamento)
- [Visualização e Regra de Silêncio](#visualização-e-regra-de-silêncio) · [Expand–Contract](#expandcontract-refatoração-larga) · [Achados que a Skill Produz](#achados-que-a-skill-produz)
- [Proibições](#proibições) · [Checklist Final](#checklist-final) · [Skills Companheiras](#skills-companheiras)

**`references/`** — só entram no contexto quando lidas. Cada passo diz qual abrir, **antes** da ação;
cada arquivo aberto vira uma linha em `## Referências Abertas` do `03`.

| Arquivo | Lida em | Fonte única de |
|---|---|---|
| [`template-ticket.md`](references/template-ticket.md) | passo 7; fechamento; regra 11 | template do ticket, formato dos campos e do `Status`, `## Tickets` do `03`, ticket nascido depois do fatiamento, colunas do `INDEX.md` |
| [`exemplo-de-fatiamento.md`](references/exemplo-de-fatiamento.md) | passos 4 e 6 | caso resolvido: `01` por camada → tickets verticais; exemplo de quiz |
| [`expand-contract.md`](references/expand-contract.md) | passo 4, em refatoração larga | a sequência em Laravel, exemplo de renomear coluna, armadilhas |
| [`despacho-e-fechamento.md`](references/despacho-e-fechamento.md) | execução; fechamento | prompts de despacho, abertura da sessão nova, comandos de fechamento, linha de `## Despachos` |
| [`visualizacao.md`](references/visualizacao.md) | subcomando `status`; fechamento; espelho | formato do `--status`, do `07-tickets/README.md` e do `INDEX.md`; cores do grafo; `espelho-gh.sh` e a sintaxe do `gh` |

**Scripts** — `bash {skills}/feature-tickets/scripts/indice.sh`: sem argumento, grava o
`07-tickets/README.md` de cada feature fatiada e o `wikis/specs/INDEX.md` e imprime os paths; com
`--status {wiki}`, imprime o quadro da wiki e não grava nada; com `--check {wiki}`, confere os tickets:
alocação única de `RQ`, `P-nn`, CT e CT-B, forma, arestas, fatia vertical, costura, tipos e espelho no
`03`. O `--check` é a **fonte única** da alocação a ticket (o `rastreabilidade.sh` da `feature-wiki`
não confere ticket). Por quê: um tema, um script, porque a mesma alocação em dois parsers já saiu duas
vezes, com textos diferentes (estudo §7.1, T4). `bash {skills}/feature-tickets/scripts/espelho-gh.sh {wiki}`:
espelho no GitHub, dry-run por padrão ([Visualização](#visualização-e-regra-de-silêncio)). Contrato
do `--check`: silêncio + exit 0 = OK; `arquivo:linha: mensagem` + exit 1 = achado; exit 2 = erro de uso
ou de ambiente. A lista completa está no cabeçalho de cada script.

**Sem `php` no PATH** (exit 2 do `indice.sh` e do `rastreabilidade.sh`): conferir à mão, pela lista
do cabeçalho de cada script, e registrar em `## Tickets` do `03` a linha
`Conferência mecânica: degradada — {motivo}`. Nesse caso nenhum quadro é gerado. Por quê: o que não
rodou tem de aparecer como não rodado, senão nada rebaixa a conclusão (estudo §7.4).

---

## Quando Invocar

- **Pelo usuário**, com `/feature-tickets {wiki}` para fatiar, `/feature-tickets {wiki} {NN}` para
  executar o ticket `NN` numa sessão nova e `/feature-tickets {wiki} status` para ver o quadro
  ([Visualização](#visualização-e-regra-de-silêncio)). Ex.: `/feature-tickets wikis/specs/feature/ferro-830/aprovacao-compra 04`.
  O frontmatter traz `disable-model-invocation: true`: o modelo não se invoca sozinho, porque fatiar
  é decisão do desenvolvedor e a label automática da `to-tickets` disparou execução indevida
  (estudo §4.4).
- **Sugerida pelo step 8 da `feature-wiki`**, quando o plano não cabe numa sessão.
- **Refatoração larga**: wiki com `## Natureza da Wiki` = `refatoração` que troca um símbolo usado
  em muitos lugares → [Expand–Contract](#expandcontract-refatoração-larga).

### Quando NÃO Invocar

- **Se cabe numa sessão, não fatie.** Fatiar custa um quiz, N sessões e N fechamentos; só compensa
  quando a sessão única estoura (estudo §4.1: *"se cabe numa janela de contexto, você não precisa
  desta skill"*).
- **Wiki sem `04`** — ver o gate de entrada.
- **Refatoração pequena e interna, já coberta por teste verde** — fica fora da esteira.
- **Para substituir o quality gate** — ele continua por feature, no fim, depois do último ticket.
- **Para abrir issues por conta própria** — o espelho no GitHub (`espelho-gh.sh`) só roda sob pedido;
  o arquivo do ticket é a fonte.

---

## Entradas e Gate de Entrada

| Entrada | Obrigatória? | Sem ela |
|---|---|---|
| `00-requisito.md` com `RQ` (e `P-nn`, `Qn` quando houver) | **sim** | pare — sem `RQ` não há o que alocar |
| `01-plano-acao.md` com `## Cobertura do Requisito` e os passos | **sim** | pare — o ticket aponta passos do `01` |
| `02-decisoes-arquiteturais.md` (zero ADR é válido) | **sim** | pare — o executor do ticket recebe o `02` |
| `04-casos-de-teste.md` com `## Costuras de Teste` | **sim** | pare — o ticket ancora em CT, e o campo `**Costura**` é linha dessa tabela |
| `05-casos-de-teste-browser.md` | se existir | o campo `**CT-B**` fica `—` |
| `03-progresso.md` | **sim** | pare — a skill escreve `## Tickets` nele |

**Gate de entrada**, antes do passo 1:

1. `bash {skills}/feature-wiki/scripts/rastreabilidade.sh {wiki}` silencioso. Achado (`RQ` sem
   passo, `RQ` sem CT, `P-nn` sem CT, passo sem `RQ`, `RQ` aberta implementada) = **pare** e devolva à
   `feature-wiki`. Por quê: o ticket se define por `RQ` + CT; fatiar sobre `RQ` sem passo ou sem CT
   produz ticket que não fecha, e a omissão some dentro dele.
2. `04` sem `## Costuras de Teste` (derivado antes da `feature-test-design` 1.16.0) = **pare** e peça
   a re-derivação das costuras.
3. `RQ` `aberta — Qn` **não** impede fatiar: vai para um ticket bloqueado por `Qn` (regra 8).

---

## Procedimento

### 1. Ler

O `00` inteiro; do `01`, `## Cobertura do Requisito`, `## Estrutura de Implementação`,
`## Análise dos Arquivos Existentes` e `## Decisões de Desenho`; o `02`; do `04`, `## Mapa de Regras`,
`## Costuras de Teste` e `## Índice de Cenários`; o `05`; do `03`, `## Auditoria Pré-Implementação`.

### 2. Cortar ou não

Aplicar o critério de corte do step 8 da `feature-wiki` (os limiares, quando houver, são dela e são
hipótese a calibrar). Cabe numa sessão → dizer isso ao usuário, com recomendação, e **parar**.

### 3. Prefactoring

Procurar o que precisa ser preparado antes de qualquer comportamento: a varredura da classe irmã e as
premissas corrigidas no `## Auditoria Pré-Implementação` do `03`, e a `## Análise dos Arquivos
Existentes` do `01`. *"Torne a mudança fácil, depois faça a mudança fácil."* Se houver, vira o ticket
`00-prefactor-{slug}.md`: sem `RQ`, sem CT novo, comportamento inalterado, fecha com a suíte existente
verde contra a baseline. É um só; preparo que não cabe numa sessão é refatoração larga, com wiki
própria. Por quê: preparo misturado com comportamento deixa o CT do ticket vermelho por dois motivos
(estudo §4.1; §4.2: a coletânea descobre o preparo, mas não o sequencia).

### 4. Fatiar vertical

Antes da primeira vez numa sessão, leia [`references/exemplo-de-fatiamento.md`](references/exemplo-de-fatiamento.md).
Em refatoração larga, leia [`references/expand-contract.md`](references/expand-contract.md) e siga a
seção [Expand–Contract](#expandcontract-refatoração-larga).

- **Unidade: `RQ` e `P-nn`.** Junte os que só se demonstram juntos (autorização e a ação que ela
  protege); separe os que se demonstram sozinhos. O que está *fora desta entrega* não entra em ticket
  (regra 3).
- **CT**: os que têm origem (Mapa de Regras do `04`) nos `RQ`/`P-nn` do ticket. CT com origem em dois
  tickets vai para o **último** — o que completa as origens — e esse ticket fica bloqueado pelo outro.
- **Passos do `01`**: todo passo que a `## Cobertura do Requisito` liga a um `RQ` ou `P-nn` do ticket.
  Um passo pode estar em mais de um ticket: cada ticket faz a parte do passo que o seu `RQ` exige.
- **Costura**: a linha de `## Costuras de Teste` de cada grupo de CT do ticket.
- **Entrega**: uma frase do ponto de vista do usuário, ponta a ponta.
- **Teste da fatia**: com os bloqueadores feitos, o ticket se demonstra sozinho? Não → falta camada:
  puxe o passo ou funda com o vizinho.
- **Tamanho**: cabe numa sessão nova. Esse julgamento é feito no quiz (passo 6), com o usuário. Os
  limiares do step 8 **não** valem aqui, porque descrevem uma feature inteira, e nenhum número foi
  medido para ticket. O sinal de retorno é este: *se a sessão de um ticket compactou, o ticket era
  grande demais, e os próximos se dividem*. Ticket grande se divide por `RQ`. Um `RQ` sozinho que
  estoura **não** se divide entre tickets (quebraria a alocação única): volta à `feature-wiki`, que o
  marca `decomposta em RQ-nn, RQ-mm` e cria as filhas.

### 5. Arestas e numeração

- `**Bloqueado por**`: os tickets de que a entrega depende e as `Qn` abertas dos seus `RQ`.
  `**Bloqueia**` é o inverso, para leitura.
- Numerar em ordem de dependência: bloqueador sempre com número **menor**. Ciclo é fatia mal cortada —
  funda os tickets do ciclo.

### 6. Quiz com o usuário

Antes de gravar. Por quê: granularidade e arestas são decisão de desenho, e o palpite do agente sobre
elas não tem outro revisor (estudo §4.1, quiz da `to-tickets`; §2.3, quem responde cada raia).

1. Mostrar a proposta: uma linha por ticket — `NN — {entrega} · bloqueado por · RQ · nº de CT`.
2. Perguntar em rodadas pela fronteira, no formato da entrevista da `feature-wiki`:

   ```text
   ❓ Q{n} · raia: desenho · afeta: {RQ-nn, P-nn} · depende de: {Qn | —}
   {pergunta}
   ➡️ Recomendação: {resposta recomendada} — {por quê}
   ```

   A numeração `Qn` é única na feature, para as três raias, e o quiz é uma quarta fonte de pergunta
   nova (além dos steps 5, 9 e 11 da `feature-wiki`). Ele começa no número seguinte ao maior `Qn` já
   usado no `00` (`## Perguntas ao Solicitante`), no `01` (`## Decisões de Desenho`), no `02` e no
   `03` (`## Auditoria Pré-Implementação`). Por quê: a pergunta de requisito nascida aqui entra no
   `00` com o mesmo ID, e o `00` sozinho não mostra as `Qn` de desenho (estudo §2.5). Pergunta que
   depende de outra aberta espera a rodada seguinte. Só entra pergunta que toca um `RQ` ou `P-nn`.
3. Perguntas fixas, quando houver dúvida real: granularidade (fundir ou dividir), arestas (falta ou
   sobra dependência), prefactoring (precisa, basta), tamanho (cabe numa sessão nova: o julgamento
   do passo 4 é feito aqui).
4. Pergunta sobre **o que o sistema deve fazer** não é do desenvolvedor: é raia requisito. Registre-a
   em `## Perguntas ao Solicitante` do `00`, marque o `RQ` como `aberta — Qn` (regime da
   `feature-wiki`) e bloqueie o ticket por ela.
5. Repetir até a aprovação; resposta que muda a proposta → mostrar a lista de novo.

Exemplo de proposta e de rodada: [`references/exemplo-de-fatiamento.md`](references/exemplo-de-fatiamento.md#o-quiz).

### 7. Gravar

Antes do primeiro arquivo, leia [`references/template-ticket.md`](references/template-ticket.md).

1. Um arquivo por ticket em `{wiki}/07-tickets/NN-slug.md`, pelo template, com `**Status**: pronto`.
2. `## Tickets` no `03`: a linha `Fatiamento confirmado: {data} — {quem} — {rodadas}, {nº de perguntas}`
   e uma linha por ticket, no formato do template. Se o step 8 já tinha registrado `Não fatiado — …`,
   essa linha é **substituída** pela `Fatiamento confirmado` (o `--check` acusa as duas coisas).
3. `bash {skills}/feature-tickets/scripts/indice.sh --check {wiki}` — **silencioso**. Achado =
   corrigir e rodar de novo; nada segue com achado aberto.
4. `bash {skills}/feature-tickets/scripts/indice.sh` — grava `07-tickets/README.md` e `wikis/specs/INDEX.md`.
5. Dizer ao usuário, numa linha, quais tickets estão na fronteira e como ver o quadro (a linha de
   `status` da [regra de silêncio](#visualização-e-regra-de-silêncio)).

---

## Regras dos Tickets

1. **Fatia vertical.** O ticket atravessa toda camada que os seus `RQ` exigem. Por quê: fatia
   horizontal não funciona até a última camada aterrissar (estudo §4.1).
2. **Cabe numa sessão nova.** Por quê: o ticket é a unidade que sobrevive à troca de sessão com algo
   demonstrável; a feature de 2026-09-21 rodou numa sessão com 48 despachos e ~4,6 M tokens (estudo §4.2).
3. **Todo `RQ` e `P-nn` vigente do `00` está em exatamente um ticket.** Sem ticket = achado: omissão no
   nível do plano. Ficam fora: `RQ` substituída (pelo `Estado` ou pela coluna `Substitui` de um Adendo,
   sem `(parcial)`), `RQ` `decomposta em …` (as filhas entram) e `P-nn` não vigente. Por quê: é a omissão silenciosa
   que o quality gate caça, pega antes de implementar (estudo §4.3). O que está *fora desta entrega*
   também fica fora, e citá-lo num ticket é achado: não tem passo nem CT para ficar verde, e a
   justificativa é julgada no quality gate (dimensão A).
4. **Todo CT do `04` e todo CT-B do `05` está em exatamente um ticket** (fora os riscados, fundidos
   ou `@obsoleto`). Por quê: CT sem ticket nunca é cobrado; CT em dois tickets não diz qual fechamento
   o prova.
5. **O CT fica no ticket onde vira verde**: ao menos uma origem no ticket, as demais nos que o
   bloqueiam. Por quê: critério já verdadeiro antes do ticket, ou satisfeito por trabalho de outro
   ticket, não avalia nada (estudo §4.1).
6. **Sem path de código no ticket**; passos do `01` por número + link, nunca copiados. Por quê: o
   `01` é a fonte do path e é reconciliado no step 10; cópia envelhece sem ninguém conferir.
7. **O critério de aceite são os CT listados** — nada de critério em prosa. Por quê: prosa reafirma o
   pedido em vez de derivar do artefato; o `04` já é mais forte (estudo §4.4).
8. **`RQ` aberta só entra em ticket cujo `**Bloqueado por**` cita a `Qn`** — o ticket *bloqueado por
   Qn*, `pronto` e fora da fronteira até a resposta entrar como Adendo. Por quê: nenhum passo
   implementa `RQ` aberta com a interpretação do desenvolvedor (raia requisito da `feature-wiki`).
   A resposta vai para **esse mesmo ticket**: depois de a `feature-wiki` registrar o Adendo e atualizar
   o `01`, e de a `feature-test-design` derivar os CT, o ticket troca a `RQ` pela substituta (ou a
   mantém, agora `fechada`), recebe os CT, os passos e a costura, e a `Qn` sai de `**Bloqueado por**`
   — sem renumerar. `Qn` respondida que continua no campo é achado do `--check`.
9. **`Status`**: `pronto | em execução | em revisão | concluído — {data, evidência}`. `em execução`
   é marcado por quem despacha; `em revisão`, quando os CT do ticket estão verdes; `concluído`,
   quando o desenvolvedor aprova. Nenhuma label automática. Por quê: estado é campo do arquivo, e a
   label `ready-for-agent` disparou execução indevida (estudo §4.4).
10. **O arquivo do ticket é a fonte, e vai no PR com a wiki.** A `## Tickets` do `03` espelha o
    `Status`; o `INDEX.md` é gerado, nunca editado; a issue do tracker, quando existe, é espelho de mão
    única. Por quê: ticket fora do controle de versão se perde do código que ele descreve (estudo §4.4).
11. **`RQ` ou `P-nn` que nasce depois do fatiamento** — Adendo com `**Responde a**: —`; `P-nn` do
    step 9, do item 7 do step 10 (doc sem rastro) ou do step 11 (inclusive a L5 do gate) — ganha
    ticket novo no fim da numeração, bloqueado pelos tickets cujo código a mudança toca. **Quem grava
    é a sessão que registra o Adendo ou roteia o achado, sem invocar esta skill**, pela sequência de
    [`template-ticket.md`](references/template-ticket.md#ticket-nascido-depois-do-fatiamento-regra-11):
    ticket, linha em `## Tickets` do `03`, `--check` silencioso, quadros. Nasce
    `em revisão — {data}, {CT verdes}` se o CT da `P-nn` já ficou verde no próprio step; senão
    `pronto`; `concluído`, só com o desenvolvedor (regra 9). Resposta a uma `Qn` segue a regra 8.
    Por quê: mantém a regra 3 verdadeira até o PR (sem o ticket, o `--check` do step 10 acusa a
    `P-nn`), e o modelo não invoca esta skill (`disable-model-invocation`).

---

## Execução e Cegueira de Fatia

- **Só ticket da fronteira é despachado.** Quem despacha marca `em execução — {data}, {sessão nova | construtor}`
  no ticket. Despacho a `construtor`: a sessão que despacha atualiza também a linha do `03`. Sessão
  nova: ela mesma marca o ticket ao começar e não abre o `03`, que fica `pronto` até o fechamento — a
  única divergência que o `--check` aceita. Por quê: a sessão nova não lê o `03` (cegueira de fatia,
  estudo §4.3), e sessões em paralelo precisam do `--check` silencioso para despachar. O `--check`
  acusa ticket em execução com bloqueio pendente.
- **Um ticket por sessão nova** (`/feature-tickets {wiki} {NN}`) **ou por `construtor` despachado.**
  Invocada com `{wiki} {NN}`, a skill não fatia: roda o `--check`, confere que o ticket `NN` está na
  fronteira e segue esta seção e o [Fechamento](#fechamento).
- **O executor recebe só a fatia**: o arquivo do ticket, o `00`, o `02`, do `01` só as seções dos
  passos listados, e do `04`/`05` o `## Setup Global` e os cenários do ticket. **Não recebe** os outros
  tickets, o `03` (`## Despachos`, `## Desvios do Plano`, `## Retrospectiva`), o resto do `01` nem a
  conversa do planejamento. Por quê: o construtor da fatia 03 não vê o raciocínio da fatia 02 — a
  mesma cegueira que os gates da esteira usam, agora entre fatias (estudo §4.3).
- Os testes dos CT nascem antes do código, pelo `executor-ct` da `feature-wiki`, sob o contrato dele
  (sem `01`/`02`); o código dos passos vai ao `construtor`; os CT-B, ao `executor-ctb`.
- **Limite declarado**: a cegueira de fatia é por **contexto entregue**, não por construção. O
  `construtor` tem `Read` e pode abrir outro ticket; o hook `guarda-subagente.sh` da `feature-wiki`
  não tem perfil para ele. A sessão nova segue a instrução de abertura.
- Todo despacho a sub-agente vira linha em `## Despachos` do `03` (regra da `feature-wiki`), com a
  coluna `Custo` preenchida pelo que o host reporta no retorno (tokens · duração; `—` se não reporta).
  Por quê: a etapa de tickets multiplica sessões e despachos, e o custo por ticket é o dado que
  calibra o tamanho do ticket.

Antes de despachar, leia [`references/despacho-e-fechamento.md`](references/despacho-e-fechamento.md):
prompts do `construtor` e do `executor-ct`, e o que a sessão nova lê.

---

## Fechamento

### Do ticket

1. **CT do ticket verdes**, com a saída do comando — e os IDs `[CT-nn]` distintos da saída iguais aos
   CT do ticket: cada um aparece ao menos uma vez e nenhum sobra. Por quê: filtro que casa nada também
   sai verde, e contar testes não serve — cada linha de `Exemplos` de um `Esquema do Cenário` roda como
   um teste do dataset e o CT conta como um.
2. **`vendor/bin/pest --parallel --tia` sem falha nova** contra a baseline de `{base}` (Pest 4: a suíte
   completa).
3. `**Status**` → `em revisão — {data}, {evidência}`. É o ponto de entrada da revisão humana: o
   desenvolvedor revisa **este** entregável, não o PR inteiro no fim (estudo §5.1).
4. Aprovado → `concluído — {data}, aprovado por {quem}`. **A skill não marca `concluído` sozinha.**
   Reprovado → volta a `em execução` com o motivo; CT que faltou nasce no `04` primeiro, pela
   `feature-test-design`, e é alocado a este ticket.
5. Linha do `03` atualizada, `--check` silencioso, quadros gerados de novo (`indice.sh` sem
   argumento). A sessão nova só abre o `03` aqui, com os CT já verdes, e só para essa linha e para
   `## Referências Abertas`.
6. Resposta ao usuário: **uma linha**, `NN → {status} · x/y concluídos · fronteira: …`, com os números
   do `--status` rodado depois da gravação ([regra de silêncio](#visualização-e-regra-de-silêncio)).

Comandos: [`references/despacho-e-fechamento.md`](references/despacho-e-fechamento.md#fechamento).

### Da feature

Com todos os tickets `concluído`, a feature volta à `feature-wiki`: step 9 (revisão do diff), step 10
(reconciliação), step 11 (quality gate, cuja Matriz de Rastreabilidade ganha a coluna `Ticket`) e PR —
**uma vez, por feature**. O step 11 roda `indice.sh` para o `INDEX.md` ir atualizado no PR.

---

## Visualização e Regra de Silêncio

O quadro dos tickets é **gerado por script, nunca escrito pelo modelo**. Por quê: a etapa de tickets
já multiplica sessões e tokens (regra 2), então o quadro não pode custar token de saída. E uma tabela
redigida pelo modelo pode divergir do arquivo do ticket, que é a fonte. Formatos, cores e comandos:
[`references/visualizacao.md`](references/visualizacao.md).

1. **Repositório**: `indice.sh` sem argumento, nos passos que já o rodam (gravar, fechar ticket, step
   11). Grava `07-tickets/README.md` e `wikis/specs/INDEX.md`. A skill não lê de volta nem reproduz
   o conteúdo na conversa.
2. **Chat, só sob pedido**: `/feature-tickets {wiki} status` roda `indice.sh --status {wiki}` e responde
   **uma linha**, a primeira da saída, sem recopiar a tabela. O quadro inteiro fica na saída recolhida
   do comando. Para acompanhar, o usuário digita
   `! bash {skills}/feature-tickets/scripts/indice.sh --status {wiki}` no Claude Code — ex.:
   `! bash .ai/skills/feature-tickets/scripts/indice.sh --status wikis/specs/feature/ferro-830/aprovacao-compra`.
   Isso custa zero token de modelo só com `"respondToBashCommands": false` no `settings.json`, porque
   por padrão o Claude Code responde à saída do `!`. Se ele perguntar como ver o status, indique essa forma e essa
   configuração.
3. **GitHub Projects, só sob pedido**: `espelho-gh.sh {wiki}` roda primeiro em dry-run. Os comandos
   ficam na saída do comando, e a skill resume em uma linha quantas issues cria e para quais colunas
   move, sem recopiá-los. `--aplicar` só com pedido explícito depois do dry-run. Nunca com label.
4. **Regra de silêncio**: sem pedido, a skill nunca imprime o quadro, a tabela de tickets nem o grafo.
   Ao fechar ou mudar o status de um ticket, responde uma linha: `NN → {status} · x/y concluídos · fronteira: …`.
   A saída de um `--status` que o usuário rodou pelo `!` já está na conversa: responder, se for o
   caso, no máximo uma linha, sem recopiá-la.
5. **Limite declarado**, igual nos três: o script lê arquivos, não roda testes. CT verdes são inferidos
   do `Status`, e ticket `em execução` mostra `—`. A prova de CT verde é o fechamento, com a saída do Pest.

---

## Expand–Contract (refatoração larga)

Refatoração larga — renomear coluna, retipar símbolo compartilhado — que não cabe numa sessão é
sequenciada em três tipos de ticket:

1. **expand** (`NN-expand-{slug}.md`): o novo nasce ao lado do antigo, com escrita dupla; nenhum
   leitor muda.
2. **migrate** em lotes (`NN-migrate-{slug}.md`): cada lote move um grupo de leitores; cada um
   **bloqueado pelo expand**; a suíte do CI (sem `--tia`) verde entre lotes.
3. **contract** (`NN-contract-{slug}.md`): bloqueado por todos os migrate; desliga a escrita dupla e
   remove o antigo.

Exemplo Laravel, renomear `clientes.nome` para `razao_social`: expand = migration com a coluna nova
anulável e preenchida + escrita dupla no `saving` do model → migrate = leitores (Filament, Livewire,
API Resources, factories) em lotes → contract = migration que remove `nome`, com `down()` que a
recria. Exemplo completo e armadilhas (update em massa não dispara `saving`):
[`references/expand-contract.md`](references/expand-contract.md).

- A wiki é `## Natureza da Wiki` = `refatoração`. Os `RQ` da refatoração ficam no **contract**, onde
  passam a ser verdade; expand e migrate levam `**RQ cobertas**: —` e CT próprios, que provam a
  transição. É a exceção declarada à fatia vertical: o `--check` não confere a origem dos CT de
  expand e migrate.
- Na `## Cobertura do Requisito`, cada `RQ` da refatoração liga **todos** os passos que o tornam verdade
  (expand, lotes, contract). O contract lista em `**Passos do 01 envolvidos**` só o próprio passo; o
  `--check` aceita os demais nos expand e migrate que o bloqueiam. Por quê: ligar o `RQ` só ao contract
  deixa os outros passos sem `RQ` (o gate de entrada para), e exigir todos no contract entrega o `01`
  inteiro ao executor dele (sem cegueira de fatia).
- Por quê: feita de uma vez, a troca toca migration, model, factories, telas e testes no mesmo passo,
  não cabe numa sessão e deixa a árvore quebrada se a sessão acaba no meio (estudo §4.1, §4.2).

---

## Achados que a Skill Produz

| Achado | Como aparece | O que fazer |
|---|---|---|
| `RQ`/`P-nn` sem ticket ou em dois | `--check`: `RQ-04 sem ticket em 07-tickets/` · `P-01 em mais de um ticket: 02, 04` | alocar; sem passo no `01` → devolver à `feature-wiki` |
| `RQ` substituída ou decomposta num ticket | `--check`: `RQ-06 está substituída no 00 …` · `RQ-03 está decomposta no 00 (em RQ-08, RQ-09) …` | trocar pela substituta ou pelas filhas |
| CT ou CT-B sem ticket | `--check`: `CT-06 sem ticket` | alocar ao ticket onde ele vira verde |
| Ticket que não fecha vertical | `--check`: `RQ-03 exige o(s) passo(s) 5 …` (idem `P-nn`) ou `CT-07 depende de RQ-04 … fora deste ticket` | puxar o passo, criar a aresta ou fundir |
| CT que fica verde antes do ticket | `--check`: `CT-02 não tem origem neste ticket …` | mover para o ticket de uma das origens |
| `RQ` aberta fora de bloqueio | `--check`: `RQ-05 está aberta (Q1) e **Bloqueado por** não cita Q1` | citar a `Qn` |
| `Qn` respondida ainda no bloqueio | `--check`: `Q1 não está aberta no 00 …` | levar a resposta ao ticket (regra 8) |
| `RQ` ainda `aberta — Qn` com a `Qn` respondida, retirada ou fora do `00` | `--check`, na linha da `RQ` no `00`: `RQ-04 está "aberta — Q1", mas Q1 está respondida ou retirada no 00 …` (o ticket não é acusado) | atualizar o `Estado` da `RQ` no `00` (regime da `feature-wiki`); o ticket segue o `00` corrigido |
| `RQ` fora desta entrega num ticket | `--check`: `RQ-07 está fora desta entrega no 01 …` | tirar do ticket (regra 3) |
| CT fora do Índice de Cenários | `--check`: `CT-04 está no Gherkin e fora do ## Índice de Cenários …` | devolver o `04` à `feature-test-design` |
| Aresta para frente ou ciclo | `--check`: `bloqueado por 02, de número igual ou maior` | renumerar ou fundir |
| `RQ` maior que uma sessão | julgamento, no passo 4 | devolver à `feature-wiki` para decompor |
| `03` divergente do ticket | `--check`: `o 03 diz "pronto", o ticket diz "em execução"` (fora a sessão nova em execução) | corrigir o `03` — o ticket é a fonte |
| `03` sem `Fatiamento confirmado` ou ainda `Não fatiado` | `--check`: `## Tickets ainda diz "Não fatiado — …"` | substituir a linha (passo 7) |
| `**Costura**` sem o grupo de um CT | `--check`: `**Costura** não cita o grupo "Decisão" de CT-03 …` | copiar a linha do `## Costuras de Teste` do grupo |

Achado do `--check` bloqueia a gravação (passo 7) e o despacho. O que depende de outra skill vai ao
usuário com o destino.

---

## Proibições

1. **Não fatiar o que cabe numa sessão.**
2. **Não gravar ticket antes de o quiz ser aprovado.**
3. **Não criar ticket horizontal** (uma camada: "migration", "model", "tela").
4. **Não pôr path de código nem copiar passo do `01` no ticket.**
5. **Não escrever critério de aceite em prosa.**
6. **Não editar `01`, `02`, `04` nem `05`.** No `00`, só a pergunta de raia requisito nascida no quiz
   (passo 6, item 4), no regime da `feature-wiki`; no `03`, só `## Tickets`, a linha de `## Despachos`
   e `## Referências Abertas`.
7. **Não dividir um `RQ` entre tickets.**
8. **Não editar o `INDEX.md` à mão** — nem para resolver conflito de merge: rodar o script.
9. **Não despachar ticket fora da fronteira; não usar label automática de estado.**
10. **Não marcar `concluído` sem a aprovação do desenvolvedor; não marcar `em revisão` ou `concluído`
    sem evidência.**
11. **Não pular o quality gate por feature** porque os tickets fecharam.
12. **Não escrever o quadro à mão nem imprimi-lo sem pedido** — nem `07-tickets/README.md`, nem tabela
    de tickets na conversa: quem desenha é o `indice.sh`.
13. **Não rodar `espelho-gh.sh --aplicar` sem pedido explícito depois do dry-run; nunca com label.**

---

## Checklist Final

### Fatiamento

- [ ] Gate de entrada: `rastreabilidade.sh` silencioso; `04` com `## Costuras de Teste`
- [ ] Critério de corte do step 8 aplicado — o plano não cabe numa sessão (ou a skill parou)
- [ ] Prefactoring avaliado; `00-prefactor-*` só se necessário, sem `RQ` e sem CT novo
- [ ] Cada ticket vertical, demonstrável com os bloqueadores feitos, do tamanho de uma sessão nova
- [ ] Refatoração larga: expand → migrate em lotes → contract, com as arestas
- [ ] Quiz no formato ❓/➡️, em rodadas pela fronteira, `Qn` a partir do maior de `00`–`03`, aprovado;
  `Fatiamento confirmado` no `03` (no lugar do `Não fatiado`, se havia)

### Gravação

- [ ] Tickets pelo template, com os campos fixos, sem path de código e sem critério em prosa
- [ ] `## Tickets` no `03`, uma linha por ticket
- [ ] `indice.sh --check {wiki}` silencioso — saída (vazia) e exit colados no relatório; sem `php`,
  `Conferência mecânica: degradada — {motivo}` em `## Tickets` do `03`
- [ ] `07-tickets/README.md` e `INDEX.md` gerados pelo script; fronteira dita ao usuário numa linha

### Por ticket executado

- [ ] Despachado da fronteira; `em execução` marcado por quem despachou; linha em `## Despachos` com `Custo`
- [ ] Executor recebeu só a fatia
- [ ] CT do ticket verdes (IDs `[CT-nn]` da saída conferidos) e `--tia` sem falha nova; `em revisão` com a evidência
- [ ] `concluído` só depois da aprovação do desenvolvedor
- [ ] Quadros regenerados; resposta de uma linha (`NN → {status} · x/y concluídos · fronteira: …`), sem o quadro
- [ ] Sessão do ticket compactou? Registrado como sinal de ticket grande; os próximos divididos

### Referências

- [ ] Cada arquivo de `references/` aberto tem uma linha em `## Referências Abertas` do `03` —
  `` `{arquivo}` — {passo N | ticket NN} — {data} `` (a sessão nova grava no fechamento)

---

## Skills Companheiras

| Skill | Relação |
|---|---|
| `feature-wiki` | produz `00`–`03`, sugere esta skill no step 8 e retoma a feature no step 9 depois do último ticket; dona do `executor-ct`, do `construtor` e do `rastreabilidade.sh` (gate de entrada; a alocação a ticket é do `indice.sh --check`) |
| `feature-test-design` | produz o `04`/`05` e as `## Costuras de Teste`; recebe o CT que faltou num ticket reprovado |
| `feature-quality-gate` | continua por feature, no fim; roda `indice.sh --check` e a Matriz de Rastreabilidade ganha a coluna `Ticket` (CT-B conta como CT) |

Motivação, limites e a diferença para a `to-tickets`: [README desta skill](README.md).

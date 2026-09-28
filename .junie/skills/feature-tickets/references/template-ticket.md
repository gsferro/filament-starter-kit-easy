> Referência da feature-tickets 1.0.0. Lida em: passo 7 (antes de gravar o primeiro ticket), no
> fechamento de ticket (formato do `Status` e da linha do `03`) e pela sessão que grava o ticket de
> uma `RQ` ou `P-nn` nascida depois do fatiamento (regra 11). Fonte única de: template do ticket,
> formato dos campos que o `scripts/indice.sh` lê (inclusive o campo opcional `**Issue**`), formato da
> seção `## Tickets` do `03`, sequência da regra 11 e colunas do `INDEX.md`.

# Template do ticket

**Path**: `wikis/specs/{branch}/{feature}/07-tickets/NN-slug.md` — `NN` com dois dígitos, em ordem de
dependência; `slug` em kebab-case, tirado da entrega. Prefactoring: `00-prefactor-{slug}.md`.
Refatoração larga: `NN-expand-{slug}.md`, `NN-migrate-{slug}.md`, `NN-contract-{slug}.md`
([`expand-contract.md`](expand-contract.md)).

As regras que o template carrega estão no `SKILL.md`, seção *Regras dos Tickets*.

## Template

Um campo por linha, sempre nesta ordem. O `indice.sh --check` lê cada campo pelo rótulo em negrito;
campo fixo ausente é achado. `**Issue**` é opcional e só existe depois do espelho no GitHub: quem o
grava é o `espelho-gh.sh --aplicar`, logo abaixo do `**Status**` ([`visualizacao.md`](visualizacao.md#3-github-projects--espelho-ghsh)).

```markdown
# NN: {entrega — o que o usuário passa a conseguir, em uma frase}

**Entrega**: {comportamento ponta a ponta, do ponto de vista do usuário: quem faz o quê e o que vê. Sem camada, sem path, sem critério de aceite.}
**RQ cobertas**: {RQ-02, RQ-05, P-01}
**CT que ficam verdes**: {CT-04, CT-05, CT-07}
**CT-B**: {CT-B02 | —}
**Passos do 01 envolvidos**: {3, 4, 6} — [01 › Estrutura de Implementação](../01-plano-acao.md#estrutura-de-implementação)
**Bloqueado por**: {01, 02 | Q3 | 01, Q3 | —}
**Bloqueia**: {05 | —}
**Prefactoring**: {não | sim → 00 ({o que o 00 prepara para este ticket})}
**Costura**: {Grupo} — {costura} ({uma por grupo de CT do ticket, copiada do ## Costuras de Teste do 04})
**Status**: pronto
**Issue**: {#N | dono/repo#N | URL da issue — opcional, gravado pelo espelho-gh.sh}
```

## Como preencher cada campo

| Campo | Como preencher | Por quê |
|---|---|---|
| `# NN:` | o mesmo `NN` do nome do arquivo | o `--check` compara os dois |
| **Entrega** | frase de usuário: *"o aprovador do centro aprova, ou recusa com motivo"*. Nunca *"criar a Policy"* | ticket descrito por camada é fatia horizontal com outro nome (estudo §4.1) |
| **RQ cobertas** | os `RQ` e `P-nn` vigentes que este ticket torna verdadeiros — nunca um *fora desta entrega* no `01`, uma `RQ` substituída (pelo `Estado` ou pela coluna `Substitui` de um Adendo) nem uma `decomposta em …` (vão as filhas). Prefactoring, expand e migrate: `— ({motivo})` | todo `RQ`/`P-nn` pertence a exatamente um ticket |
| **CT que ficam verdes** | os CT do `04` que passam **neste** ticket: ao menos uma origem aqui, as demais nos bloqueadores. Prefactoring: `— (suíte existente verde contra a baseline)`. Ticket só com `RQ` aberta: `— ({RQ} aberta: sem cenário até a resposta de {Qn})` | o critério de aceite é o CT; CT já verde antes do ticket não avalia nada (estudo §4.1) |
| **CT-B** | os CT-B do `05` que ficam verdes aqui, ou `—` | mesma alocação única dos CT |
| **Passos do 01 envolvidos** | **números primeiro**, depois ` — ` e o link para a seção do `01`. Todo passo que a `## Cobertura do Requisito` liga a um `RQ` ou `P-nn` do ticket entra aqui (no contract, os do expand e dos migrate que o bloqueiam já contam). Um passo pode aparecer em mais de um ticket | o `--check` lê os números antes do ` — `; o link evita copiar o passo (o `01` é a fonte do path) |
| **Bloqueado por** | números de ticket (`01, 02`) e `Qn` abertas de que a entrega depende. `—` quando nada bloqueia. `Qn` respondida sai daqui, e a resposta entra neste ticket (regra 8) | é o que define a fronteira; bloqueador sempre com número menor |
| **Bloqueia** | o inverso de *Bloqueado por*, para leitura | quem pega um ticket vê o que destrava |
| **Prefactoring** | `não`; `sim → 00 (…)` quando depende do ticket `00`; no próprio `00`: `é o prefactoring — {o que prepara}` | o preparo é visível em quem depende dele |
| **Costura** | a linha do `## Costuras de Teste` do `04` de cada grupo de CT do ticket, `{Grupo} — {costura}`, separadas por `;`. O `--check` confere que o campo cita o `Grupo` de cada CT do ticket na coluna `Costura` do `## Índice de Cenários`, pelo nome inteiro (`Pagamento na tela` não cita `Pagamento`). Ticket sem CT: `— ({motivo})` | diz ao executor em que camada o teste se prende |
| **Status** | `pronto` ao gravar. Transições em [Status](#status) | estado é campo do arquivo, não label |
| **Issue** (opcional) | não se preenche à mão: o `espelho-gh.sh --aplicar` grava o número da issue criada. Ticket com `**Issue**` não ganha issue nova | torna o espelho idempotente |

## Status

| Valor | Quem marca | Quando | Formato |
|---|---|---|---|
| `pronto` | a skill, ao gravar | ticket aprovado no quiz, ainda não despachado. Com `Qn` aberta em **Bloqueado por**, é o ticket *bloqueado por Qn*: fora da fronteira até a resposta | `pronto` |
| `em execução` | quem despacha (na sessão nova, ela mesma, ao começar) | o ticket está na fronteira e foi entregue a uma sessão nova ou a um `construtor`. Sessão nova: a linha do `03` fica `pronto` até o fechamento | `em execução — {data}, {sessão nova \| construtor}` |
| `em revisão` | a sessão que executou | os CT do ticket estão verdes e o `--tia` não mostra falha nova contra a baseline | `em revisão — {data}, {CT verdes, com o comando}` |
| `concluído` | o desenvolvedor aprova; a sessão grava | revisão humana aprovada | `concluído — {data}, aprovado por {quem}` |

`em revisão` e `concluído` sem ` — {data}, {evidência}` são achado do `--check`: são afirmações de
que algo passou, e afirmação sem evidência não conta nesta coletânea.

Revisão reprovada: o ticket volta a `em execução — {data}, reprovado: {motivo}`.

## Seção `## Tickets` do `03`

A primeira linha é `Fatiamento confirmado: …`. Se o step 8 da `feature-wiki` já tinha escrito
`Não fatiado — …` (a feature foi fatiada depois), a `feature-tickets` **substitui** essa linha; o
`--check` acusa a linha `Não fatiado` que ficou e a `Fatiamento confirmado` que falta (aceita `:` ou
` — ` depois do rótulo). Depois, uma linha por ticket, espelhando o `Status` do arquivo do ticket, que é a fonte. `[x]` só quando o
ticket está `concluído` — o mesmo formato de checkbox com evidência que o resto do `03` usa.

```markdown
## Tickets

Fatiamento confirmado: {YYYY-MM-DD} — {quem} — {n} rodadas, {n} perguntas

- [x] [00](07-tickets/00-prefactor-centro-de-custo.md) centro de custo no model — concluído — 2026-09-27, suíte verde contra a baseline (412/412)
- [x] [01](07-tickets/01-solicitante-envia-pedido.md) solicitante envia pedido — concluído — 2026-09-27, aprovado por {dev}
- [ ] [02](07-tickets/02-aprovador-decide.md) aprovador decide — em revisão — 2026-09-27, CT-03…CT-06 verdes
- [ ] [03](07-tickets/03-solicitante-notificado.md) solicitante notificado — pronto
```

Formato lido pelo `--check`: `- [ ] [NN](07-tickets/NN-slug.md) {entrega} — {status}[ — {data}, {evidência}]`.
A entrega não leva ` — ` no meio.

Sem `php` no PATH, logo abaixo de `Fatiamento confirmado`: `Conferência mecânica: degradada — {motivo}; alocação conferida à mão`.

## Ticket nascido depois do fatiamento (regra 11)

Vale para a `RQ` de um Adendo com `**Responde a**: —` e para a `P-nn` nascida no step 9, no item 7 do
step 10 (doc sem rastro) ou no step 11 (inclusive a L5 do quality gate). **Quem grava é a sessão que
registra o Adendo ou roteia o achado, sem invocar a skill**: a `feature-tickets` tem
`disable-model-invocation: true`, e o modelo não a invoca. Não há quiz: o ticket carrega um `RQ` ou
uma `P-nn`, e as arestas saem do código que a mudança toca.

1. `N` = o maior número em `07-tickets/`. O ticket novo é `07-tickets/{N+1}-{slug}.md`, pelo template
   acima. `**Bloqueado por**`: os tickets cujo código a mudança toca (todos de número menor).
   `**Passos do 01 envolvidos**`: os que a `## Cobertura do Requisito` liga ao `RQ` ou à `P-nn`, ou
   `— ({motivo})`, quando a correção não virou passo do `01`.
2. `**Status**` ao nascer:
   - `em revisão — {data}, {CT verdes, com o comando}`, quando o CT da `P-nn` já ficou verde no próprio
     step (no roteamento da `feature-wiki`, a correção vem antes do ticket);
   - `pronto`, para a `RQ` de Adendo e para a `P-nn` ainda sem correção: vão à execução como os demais,
     só da fronteira;
   - `concluído — {data}, aprovado por {quem}`, só depois da aprovação do desenvolvedor (regra 9).
3. Uma linha nova em `## Tickets` do `03`, no formato acima, com o mesmo `Status`.
4. `bash {skills}/feature-tickets/scripts/indice.sh --check {wiki}` silencioso. Depois,
   `bash {skills}/feature-tickets/scripts/indice.sh`, para os quadros.

Exemplo: a `P-02` nasceu no step 9 (achado `RD-03`), com CT-06 e a correção feitos no próprio step. Os
tickets 01 a 04 estavam concluídos:

```markdown
# 06: Aprovador de outro centro não baixa o anexo do pedido

**Entrega**: o aprovador de outro centro de custo abre o pedido e não consegue baixar o anexo.
**RQ cobertas**: P-02
**CT que ficam verdes**: CT-06
**CT-B**: —
**Passos do 01 envolvidos**: — (a correção do step 9 não virou passo do 01)
**Bloqueado por**: 02, 04
**Bloqueia**: —
**Prefactoring**: não
**Costura**: Decisão — Pest feature HTTP
**Status**: em revisão — 2026-09-28, CT-06 verde (`vendor/bin/pest --filter="CT-06\]"`)
```

E a linha em `## Tickets` do `03`:

```markdown
- [ ] [06](07-tickets/06-outro-centro-nao-baixa-anexo.md) outro centro não baixa o anexo — em revisão — 2026-09-28, CT-06 verde
```

## Exemplo preenchido

```markdown
# 02: Aprovador do centro decide o pedido

**Entrega**: o aprovador do centro de custo aprova, ou recusa informando o motivo; outro aprovador não consegue decidir.
**RQ cobertas**: RQ-02, RQ-03, P-01
**CT que ficam verdes**: CT-03, CT-04, CT-05, CT-06
**CT-B**: —
**Passos do 01 envolvidos**: 2, 3, 5 — [01 › Estrutura de Implementação](../01-plano-acao.md#estrutura-de-implementação)
**Bloqueado por**: 01
**Bloqueia**: 03
**Prefactoring**: sim → 00 (relação do centro de custo)
**Costura**: Decisão — Pest feature HTTP
**Status**: em revisão — 2026-09-27, CT-03…CT-06 verdes (`vendor/bin/pest --filter="CT-0[3-6]\]"`)
```

## `wikis/specs/INDEX.md`

Gerado por `bash {skills}/feature-tickets/scripts/indice.sh`, nunca escrito à mão. Uma linha por wiki
(pasta com `03-progresso.md`):

| Coluna | De onde vem |
|---|---|
| Branch, Feature | o path da wiki: `wikis/specs/{branch}/{feature}/` (a branch pode ter barra) |
| 03 | itens `[x]` / itens com checkbox no `03`; e o valor de uma linha `**Estado**: …`, se o `03` tiver |
| Tickets | total e contagem por `Status`; `não fatiada` sem `07-tickets/` |
| Progresso | barra de 10 posições: tickets `concluído` / tickets (`1/5 tickets`); feature não fatiada: itens marcados do `03` (`do 03`) |
| Fronteira | tickets `pronto` cujos bloqueadores estão `concluído` e sem `Qn` aberta no `00` |
| Veredito do 06 | o último `## Veredito — Ciclo N` do `06`; sem `06`, o `**Veredito**` da `## Quality Gate` do `03`, marcado *(no 03; sem 06)* |
| Wiki | link para o `03` e, se fatiada, `quadro` = link para o `07-tickets/README.md` |

Na mesma rodada o script grava o `{wiki}/07-tickets/README.md` de cada feature fatiada: barra de
progresso, contagem por status, fronteira, tabela por ticket e o grafo de dependências em Mermaid
(formato e cores: [`visualizacao.md`](visualizacao.md#1-repositório--07-ticketsreadmemd-e-indexmd)).
Os dois arquivos são gerados, sem data, e o conflito de merge neles se resolve rodando o script de novo.

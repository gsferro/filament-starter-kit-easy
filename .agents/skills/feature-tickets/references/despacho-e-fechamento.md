> Referência da feature-tickets 1.0.0. Lida em: antes de despachar um ticket (sessão nova ou
> sub-agente) e no fechamento do ticket. Fonte única de: os prompts de despacho, a instrução de
> abertura da sessão nova, a linha de `## Despachos` e os comandos de fechamento. O espelho no GitHub
> está em [`visualizacao.md`](visualizacao.md#3-github-projects--espelho-ghsh).

# Despacho e fechamento de ticket

As obrigações — só a fronteira é despachada, o que o executor recebe e o que não recebe, quem marca
cada `Status` — estão no `SKILL.md`, seções *Execução e Cegueira de Fatia* e *Fechamento*. Aqui
ficam os textos e os comandos.

## Antes de despachar

1. `bash {skills}/feature-tickets/scripts/indice.sh --check {wiki}` silencioso.
2. O ticket está na **fronteira**: primeira linha de `bash {skills}/feature-tickets/scripts/indice.sh --status {wiki}`.
3. `**Status**` do ticket vira `em execução — {data}, {sessão nova | construtor}`. Despacho a
   `construtor`: a sessão que despacha atualiza também a linha do `## Tickets` do `03`. Sessão nova:
   quem a abre é quem despacha; a própria sessão grava o `Status` no arquivo do ticket ao começar, e a
   linha do `03` fica `pronto` até o fechamento. O `--check` aceita essa divergência — e só essa —, e
   as outras sessões continuam despachando da fronteira.
4. Sub-agente: uma linha em `## Despachos` do `03` (formato da `feature-wiki`), com *Não recebeu* =
   `outros tickets, 03, 01 fora dos passos {n}` e *Custo* = o que o host reporta no retorno
   (tokens · duração; `—` se não reporta):

   ```markdown
   | 7 | ticket 02 | `construtor` — ticket 02 | sonnet | outros tickets, 03, 01 fora dos passos 2, 3, 5 | CT-03…CT-06 verdes | {tokens · duração} | saída do pest conferida; `git status --porcelain` igual antes/depois |
   ```

## Sessão nova

O desenvolvedor abre a sessão e invoca a skill com a wiki e o número do ticket:

```text
/feature-tickets wikis/specs/{branch}/{feature} {NN}
```

A sessão lê **só**, nesta ordem: o arquivo do ticket; o `00`; o `02`; do `01`, as seções
`### N.` dos passos listados no ticket; do `04`, o `## Setup Global` e os cenários dos CT do ticket;
do `05`, os CT-B do ticket. A costura já vem no campo `**Costura**` do ticket. É o mesmo recorte do
`construtor`: nem a `## Cobertura do Requisito` (que liga os `RQ` de todos os tickets) nem outros
tickets. O `03` só é aberto no fechamento, com os CT já verdes, para atualizar a linha do ticket em
`## Tickets` e registrar em `## Referências Abertas` os arquivos de `references/` lidos na sessão
(`` `despacho-e-fechamento.md` — ticket 04 — {data} ``).

## Prompt do `construtor` (código dos passos)

```text
Você implementa o ticket {NN} da feature em {wiki}. Leia só isto, nesta ordem:
1. {wiki}/07-tickets/{NN}-{slug}.md — o ticket: entrega, RQ, CT, passos, costura.
2. {wiki}/00-requisito.md — o que foi pedido. É a fonte da verdade.
3. {wiki}/02-decisoes-arquiteturais.md — as restrições.
4. Em {wiki}/01-plano-acao.md, só as seções dos passos {3, 4, 6} (### 3., ### 4., ### 6.).
5. Em {wiki}/04-casos-de-teste.md, o ## Setup Global e os cenários {CT-04, CT-05, CT-07}.
Não abra outros arquivos de 07-tickets/, o 03-progresso.md nem o resto do 01.
Faça só a parte de cada passo que os RQ deste ticket exigem. Não edite 00, 04 nem 05.
Os testes dos CT já existem (vermelhos); pare quando ficarem verdes.
Devolva: arquivos alterados; a saída literal do pest para os CT do ticket; `git status --porcelain`
antes e depois.
```

## Prompt do `executor-ct` (testes dos CT, antes do código)

O `executor-ct` da `feature-wiki` escreve e roda o teste a partir do Gherkin, sob o contrato dele
(`{skills}/feature-wiki/references/delegacao-casos-de-teste.md`): não lê `01` nem `02` e não toca
`app/`. Para um ticket, ele recebe o `00`, o arquivo do ticket e, do `04`, o `## Setup Global` e os
cenários dos CT do ticket — nada dos outros tickets.

```text
Você escreve e roda os testes Pest dos cenários {CT-04, CT-05, CT-07} do ticket {NN}, seguindo o
contrato do executor-ct em {skills}/feature-wiki/references/delegacao-casos-de-teste.md.
Leia: {wiki}/00-requisito.md; {wiki}/07-tickets/{NN}-{slug}.md; em {wiki}/04-casos-de-teste.md,
o ## Setup Global e esses cenários. Costura: {linha do ## Costuras de Teste}.
Não leia outros tickets, o 01 nem o 02. Não toque app/.
Devolva: arquivos de teste; saída literal do pest; cada vermelho classificado em a/b/c.
```

CT-B do ticket: o `executor-ctb` da `feature-wiki`, com o mesmo recorte (o ticket, o `00` e os
CT-B do ticket no `05`), sob o contrato dele.

## Fechamento

1. **CT do ticket verdes.** O `--filter` do Pest é regex do PHPUnit sobre o nome do teste, e o nome
   leva o ID entre colchetes:

   ```bash
   vendor/bin/pest --filter="CT-(04|05|07)\]"
   vendor/bin/pest --filter="CT-(04|05|07)\]" --colors=never | grep -oE '\[CT-[0-9]+\]' | sort -u
   ```

   O primeiro tem de sair verde; o segundo tem de listar **exatamente** os CT do ticket — filtro que
   casa nada também sai verde (`No tests found`). Não comparar a contagem de testes: cada linha de
   dataset roda como um teste. Medido com o Pest 5.2.1 numa fixture (2026-09-27): 4 CT, um deles com
   dataset de 4 linhas → `Tests: 7 passed`, e o segundo comando listou os 4 IDs.
   CT-B: `vendor/bin/pest tests/Browser --filter="CT-B02\]"`, em série, com o mesmo `grep -oE
   '\[CT-B[0-9]+\]' | sort -u`.
2. **Nada mais quebrou**, contra a baseline de falhas pré-existentes de `{base}` (a da `feature-wiki`):

   ```bash
   vendor/bin/pest --parallel --tia
   ```

   Pest 4 (sem `--tia`): a suíte completa, comparada à mesma baseline.
3. `**Status**`: `em revisão — {data}, {CT} verdes ({comando}), --tia sem falha nova`. A linha do
   `03` acompanha. O desenvolvedor é chamado a revisar **este** ticket.
4. Aprovado: `concluído — {data}, aprovado por {quem}`; `[x]` na linha do `03`.
   Reprovado: `em execução — {data}, reprovado: {motivo}`. CT que faltou nasce no `04` primeiro
   (pela `feature-test-design`) e é alocado a este ticket.
5. `bash {skills}/feature-tickets/scripts/indice.sh --check {wiki}` silencioso, e
   `bash {skills}/feature-tickets/scripts/indice.sh` para atualizar os quadros (`07-tickets/README.md`
   e `INDEX.md`).
6. Resposta ao usuário, uma linha, com os números de `indice.sh --status {wiki}`:
   `NN → {status} · x/y concluídos · fronteira: …`. O quadro não é recopiado na conversa.
7. A sessão do ticket compactou? É o sinal de que o ticket era grande demais: diga isso na mesma
   resposta e divida os próximos no mesmo critério (nenhum tamanho de ticket foi medido).

Expand, migrate e contract fecham com a suíte do CI, não com `--tia` — ver [`expand-contract.md`](expand-contract.md).

## Espelho no GitHub (só se o usuário pedir)

Pelo `scripts/espelho-gh.sh`, em dry-run primeiro. Opções, mapeamento de status para coluna do
Project, idempotência pelo campo `**Issue**` e a sintaxe do `gh` conferida:
[`visualizacao.md`](visualizacao.md#3-github-projects--espelho-ghsh). A issue é espelho de mão única,
sem label de estado; o arquivo do ticket continua a fonte.

# Requisito — Plumb a 100 e as dívidas técnicas aparadas

> **Fonte da verdade.** Captura verbatim do pedido, antes de qualquer interpretação.

## O pedido, como veio

> - pode remover os worktress.
> - ajuste tudo e deixa tudo limpo e funcionando
> - veja a validação do plumb que esta em 79 e ajuste para ficar 100%. pode criar uma
>   /feature-wiki para isso junto de uma branch ou worktree se necessário
> - vamos aproveitar essa fase para resolver dividas tecnicas e aparar as arestas que estejam
>   soltas no kit de modo criterioso e detalhista, deixando tudo 100% para os projetos que venham
>   a usar o kit em seus projetos

## Decomposição em cláusulas

| # | Cláusula | De onde vem | Tipo |
|---|---|---|---|
| RQ-01 | Os worktrees residuais são removidos | *"pode remover os worktress"* | manutenção |
| RQ-02 | A árvore fica limpa e funcionando | *"ajuste tudo e deixa tudo limpo e funcionando"* | não-funcional |
| RQ-03 | O score do Plumb sobe de **79** para o máximo alcançável | *"veja a validação do plumb que esta em 79 e ajuste para ficar 100%"* | funcional |
| RQ-04 | As dívidas técnicas declaradas são resolvidas | *"resolver dividas tecnicas"* | funcional |
| RQ-05 | O trabalho é **criterioso e detalhista**, mirando quem usa o kit em produção | *"de modo criterioso e detalhista, deixando tudo 100% para os projetos que venham a usar o kit"* | não-funcional |

## Ambiguidades e Perguntas Abertas

### RQ-03 — "100%" no Plumb pode não ser alcançável, e isso precisa ser dito

O score é composto por **três categorias com pesos** — Security 55 %, Maintenance 30 %,
Ecosystem 15 % — e **nem todo check é acionável pelo repositório**. Medido em 2026-09-25:

| Check | Situação | É acionável aqui? |
|---|---|---|
| `security/actions-sha-pinned` | **10 de 23** não pinadas | **sim** |
| `security/dependency-update-cooldown` | não configurado | **sim** |
| `security/gitlab-ci-pinned` | n.a. — o repositório é GitHub | **não** |
| `security/renovate-mr-responsiveness` | n.a. — só GitLab | **não** |
| `security/dependabot-general-responsiveness` | 0 PRs abertos | já no melhor estado |
| `security/dependabot-or-renovate-configured` | Dependabot configurado | já passa |
| `security/advisories-open` | sem advisory aberto | já passa |
| `security/security-policy-present` | `.github/SECURITY.md` existe | já passa |
| `maintenance/composer-lock-policy` | `composer.lock` e `package-lock.json` commitados | já passa |
| `maintenance/activity-recency` · `abandoned-flag` | ativo | já passa |
| `ecosystem/current-php-supported` | `^8.4` | já passa |

- **Assumido**: *"ficar 100%"* significa **fechar tudo o que é acionável pelo repositório**, e
  declarar por escrito o que não é. Um check que só se aplica a repositório GitLab não vira 100
  por esforço nenhum, e prometer o contrário seria promessa que envelhece.
- **Se negado**: se o mantenedor quiser o número redondo a qualquer custo, a conversa muda de
  engenharia para negociação com a ferramenta, e isso precisa ser decidido com o resultado real na
  mão — que é o que esta entrega produz.

### RQ-04 — "dívidas técnicas" é aberto; qual o critério de corte?

O `wikis/roadmap.md` tem **oito** itens, e eles não são da mesma natureza: há preferência de
produto (itens 1–5, sobre densidade e tema), há dependência de terceiro (item 6, credenciais reais)
e há **defeito medido em produção** (item 7).

- **Assumido**: entra nesta entrega o que é **defeito com consequência medida**, não preferência
  de produto. Concretamente o **item 7** — o middleware que não chega a quem atualiza —, que o
  próprio roadmap classifica como *"débito com consequência medida, não melhoria"*.
- **Se negado**: os itens 1–5 são features de produto e precisam de wiki própria cada um; o item 6
  depende de credenciais que só o mantenedor tem.

### RQ-05 — "deixando tudo 100%" não é o mesmo que "sem dívida"

Um kit sem dívida declarada é um kit que parou de crescer. O que o pedido quer, lido junto de
*"para os projetos que venham a usar o kit"*, é que **nenhuma aresta solta atinja quem instala** —
e que o que sobrar esteja **escrito**, não escondido.

- **Assumido**: o critério é *defeito que alcança o consumidor* primeiro, *ruído interno* depois.
- **Invariante afirmado de qualquer forma**: toda dívida que ficar, fica **declarada com o
  motivo**, e não some do registro.

> Referência da feature-tickets 1.0.0. Lida em: passo 4 (antes de fatiar a primeira vez numa
> sessão) e passo 6 (formato da proposta e das perguntas do quiz). Fonte única de: o exemplo
> resolvido de fatiamento — de um `01` por camada a tickets verticais — e o exemplo de quiz.

# Exemplo de fatiamento

O procedimento está no `SKILL.md`, seção *Procedimento*. Este é um caso resolvido, o mesmo das
fixtures do `scripts/indice.sh`.

## Os insumos

**`00`** — aprovação de pedido de compra:

| ID | Cláusula | Estado |
|---|---|---|
| RQ-01 | solicitante envia pedido com itens e valor | fechada |
| RQ-02 | só o aprovador do centro de custo do pedido decide | fechada |
| RQ-03 | aprovador aprova ou recusa; recusa exige motivo | fechada |
| RQ-04 | solicitante é notificado da decisão | fechada |
| RQ-05 | pedido acima do teto vai para a diretoria | aberta — Q1 |
| P-01 | recusa sem motivo é rejeitada no servidor, não só no formulário | vigente |

**`01`** — passos por camada: 1 migration · 2 model · 3 policy · 4 tela de envio · 5 ação aprovar e
recusar · 6 notificação · 7 encaminhamento à diretoria. `## Cobertura do Requisito`: RQ-01 → 1, 2, 4 ·
RQ-02 → 3 · RQ-03 → 2, 5 · RQ-04 → 6 · RQ-05 → 7 (`**Bloqueado por**: RQ-05 (aberta — Q1)`) ·
P-01 → 5. `## Decisões de Desenho`: D1 — a notificação sai por evento + listener, decidida na Q2 da
entrevista do step 4.

**`04`** — R1 (RQ-01) → CT-01, CT-02 · R2 (RQ-02) → CT-03, CT-04 · R3 (RQ-03, P-01) → CT-04, CT-05,
CT-06 · R4 (RQ-04) → CT-07. CT-04 tem duas regras: origem RQ-02, RQ-03 e P-01. `05`: CT-B01 (total
do formulário atualiza sem recarregar, origem RQ-01).

## O fatiamento que a skill recusa

```text
01 — criar a migration de pedidos
02 — criar o model Pedido
03 — criar a policy de decisão
04 — criar a tela de envio
…
```

Cada ticket é uma camada. Nenhum CT fica verde até o 04, e nenhum ticket se demonstra sozinho: é o
`01` copiado para arquivos separados. É o fatiamento horizontal que a `to-tickets` mede como pior
(estudo §4.1) e que o `01` já é.

## O raciocínio

1. **Prefactoring.** A varredura da classe irmã (no `03`, `## Auditoria Pré-Implementação`) achou a
   leitura do centro de custo repetida em três lugares. Sem preparar, o CT-01 e o CT-03 ficariam
   vermelhos também por causa dessa leitura. Vira `00-prefactor-centro-de-custo.md`: passo 2, sem
   `RQ`, sem CT novo, fecha com a suíte existente verde.
2. **RQ-01 sozinho** se demonstra: o solicitante envia e vê o pedido aguardando. Ticket `01`: passos
   1, 2 e 4 (tudo o que a Cobertura liga ao RQ-01), CT-01, CT-02 e CT-B01.
3. **RQ-02 e RQ-03 juntos.** RQ-02 (*só o aprovador do centro decide*) não se demonstra sem uma
   decisão acontecendo: separado, o ticket não teria CT que falha sem o outro. P-01 afeta RQ-03 e vai
   junto. Ticket `02`: passos 2, 3 e 5 (o passo 2 também está no `01`: cada ticket faz a parte do
   model que o seu `RQ` exige), CT-03 a CT-06. O CT-04 (origens RQ-02, RQ-03, P-01) fica aqui: todas
   as origens estão no ticket.
4. **RQ-04** precisa de uma decisão para notificar: ticket `03`, bloqueado por `02`, CT-07.
5. **RQ-05 está aberta (Q1).** Entra num ticket próprio, `04`, com `**Bloqueado por**: 03, Q1` e sem
   CT até a resposta: é `pronto` no `Status` e *bloqueado por Q1* no campo, fora da fronteira até a
   resposta entrar como Adendo no `00`. Quando ela entra — digamos, RQ-05 `substituída por RQ-07
   (Adendo 2)`, com o CT-09 derivado —, o **mesmo** ticket `04` passa a `**RQ cobertas**: RQ-07`,
   `**CT que ficam verdes**: CT-09` e `**Bloqueado por**: 03`. Nada de ticket `05` novo.

Se o CT-04 tivesse ficado no `01`, o `--check` acusaria: *"CT-04 depende de RQ-02, RQ-03, P-01
(Origem no 04), fora deste ticket e dos que o bloqueiam — o CT não fica verde aqui"*.

## O quiz

A proposta vem primeiro, numerada, uma linha por ticket:

```text
Proposta de fatiamento — 5 tickets

00 — centro de custo vira relação do pedido (prefactoring) · bloqueado por: — · RQ: — · CT: suíte existente
01 — solicitante envia um pedido com itens · bloqueado por: 00 · RQ-01 · CT: 2 + CT-B01
02 — aprovador do centro decide o pedido · bloqueado por: 01 · RQ-02, RQ-03, P-01 · CT: 4
03 — solicitante é avisado da decisão · bloqueado por: 02 · RQ-04 · CT: 1
04 — pedido acima do teto vai para a diretoria · bloqueado por: 03, Q1 · RQ-05 (aberta) · CT: nenhum até Q1
```

Depois as perguntas da fronteira, no formato da entrevista da `feature-wiki`. A numeração `Qn` é
única na feature: o `00` tem a Q1 (requisito) e o `01` registra a Q2 (desenho) na D1, então o quiz
começa na Q3. Olhar só o `00` daria Q2, e o ID se repetiria. O maior `Qn` já usado no `00`, no `01`,
no `02` e no `03`:

```bash
grep -ohE '\bQ[0-9]+\b' {wiki}/00-requisito.md {wiki}/01-plano-acao.md {wiki}/02-decisoes-arquiteturais.md {wiki}/03-progresso.md | sort -t Q -k 2 -n | tail -1
```

```text
❓ Q3 · raia: desenho · afeta: RQ-02, RQ-03 · depende de: —
O ticket 02 junta a autorização (RQ-02) e a decisão (RQ-03). Separar em dois tickets?
➡️ Recomendação: manter junto — RQ-02 só se demonstra com uma decisão acontecendo; separado, o
primeiro ticket não teria CT que falha sem o segundo.

❓ Q4 · raia: desenho · afeta: RQ-01, RQ-02 · depende de: —
O prefactoring 00 é necessário, ou o 01 cria a relação do centro de custo direto?
➡️ Recomendação: manter o 00 — a leitura se repete em três lugares; sem o 00, o CT-01 e o CT-03
ficam vermelhos por dois motivos.

❓ Q5 · raia: desenho · afeta: RQ-01 · depende de: —
O ticket 01 (três passos, dois CT e um CT-B) cabe numa sessão nova?
➡️ Recomendação: cabe — é uma tela e um model, sem integração externa. Se a sessão dele compactar,
o ticket era grande demais, e os próximos se dividem.
```

Pergunta que depende de outra ainda aberta espera a rodada seguinte. Quando uma resposta muda a
proposta (fundir, dividir, nova aresta), a lista é mostrada de novo antes da rodada seguinte.

Se uma pergunta for sobre **o que o sistema deve fazer** — *"acima do teto, o aprovador do centro
ainda vê o pedido?"* — ela não é do desenvolvedor: é raia requisito, vai para
`## Perguntas ao Solicitante` do `00` e bloqueia o ticket que depende dela.

Aprovado, o `03` ganha a primeira linha da `## Tickets`:

```text
Fatiamento confirmado: 2026-09-27 — {dev} — 1 rodada, 3 perguntas
```

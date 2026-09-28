> Referência da feature-wiki 4.0.0. Lida em: step 4 (antes da primeira rodada da entrevista) e sempre que os steps 5, 9 ou 11, ou o quiz da `feature-tickets`, geram pergunta. Fonte única de: formato da pergunta e numeração `Qn`, tabela completa das raias, fronteira e filtro de oráculo com exemplo, premissa de comportamento, perguntas-semente condicionais e formato do confronto código × afirmação.

# Entrevista em três raias

A obrigação — as três raias, quem responde, o bloqueio da raia requisito, a sequência única de `Qn`,
o filtro de oráculo, a premissa de comportamento, o sinal de escopo e a linha `Entendimento
confirmado` no `03` — fica no corpo do `SKILL.md`, step 4.
Os templates de `## Perguntas ao Solicitante` e de `## Premissas` estão em
[`template-00-requisito.md`](template-00-requisito.md); o de `## Decisões de Desenho`, em
[`template-01-plano.md`](template-01-plano.md).

## Formato da pergunta

Numerada, com raia, o que ela afeta, de que pergunta depende e a resposta recomendada:

```text
❓ Q3 · raia: requisito · afeta: RQ-05 · depende de: Q1
{pergunta}
➡️ Recomendação: {resposta recomendada} — {por quê}
```

- A numeração `Qn` é única na feature, para as três raias. A pergunta de requisito leva o mesmo `Qn`
  em `## Perguntas ao Solicitante`; a de desenho, na linha de `## Decisões de Desenho` do `01` ou na
  ADR do `02`
- **Quatro fontes de pergunta nova** depois do step 4, todas na mesma sequência: os steps 5, 9 e 11 e o
  quiz de granularidade da `feature-tickets`. A próxima `Qn` é a maior já usada em `00`–`03`, mais um
- **Sub-agente não sabe o próximo `Qn`**: a derivação do step 7 (e qualquer sub-agente que devolva
  pergunta) numera `Q?1, Q?2…`; a sessão renumera ao gravar e atualiza toda referência a ela — no
  `04`, a linha `RQ-nn — aberta (Q?1)` passa a `RQ-nn — aberta (Q7)`
- Sem dependência, `depende de: —`
- A recomendação é da sessão. Ela não decide: facilita a resposta e deixa explícito o que a sessão
  faria se ninguém respondesse — o que, na raia requisito, **não** autoriza implementar

## As três raias

| Raia | Pergunta sobre | Quem responde | Onde aterrissa | Bloqueio |
|---|---|---|---|---|
| **fato** | código, schema, config, doc do framework | **o agente** (steps 3/5); nunca perguntar ao usuário o que dá para descobrir | `01` (Análise dos Arquivos Existentes) | — |
| **desenho** | como implementar dado o requisito (costura de teste, cache, evento × observer…) | **o desenvolvedor** (usuário da sessão), em rodadas com recomendação | `02` se passar os três portões de ADR; senão, uma linha em `## Decisões de Desenho` do `01` | — |
| **requisito** | o que o sistema deve fazer quando o texto não diz | **o solicitante** (não o dev). Pergunta registrada em `## Perguntas ao Solicitante` do `00` | resposta entra como **Adendo** com fonte | a `RQ` fica `aberta — Qn`; **nenhum passo do `01` a implementa**: passo dependente é marcado `**Bloqueado por**: RQ-nn (aberta — Qn)` |

Como separar desenho de requisito: se a resposta muda **o que o usuário do sistema observa**
(quem vê, quem pode, o que acontece, qual o limite), é requisito. Se muda só **como** o código chega
ao mesmo comportamento observável, é desenho.

## Rodadas pela fronteira

- **Fronteira** = as perguntas cujos pré-requisitos já estão respondidos
- Cada rodada pergunta a fronteira **inteira** e espera. Pergunta que depende de outra ainda aberta
  vai para a rodada seguinte
- A entrevista termina quando a fronteira fica vazia; então a sessão registra no `03` a linha
  `Entendimento confirmado: {data} — {quem} — {rodadas, nº de perguntas por raia}`

Exemplo — a Q3 só entra na segunda rodada porque depende da Q1:

```text
Rodada 1
❓ Q1 · raia: requisito · afeta: RQ-02 · depende de: —
Quem é aprovador de uma etapa pode também ser o solicitante da mesma solicitação?
➡️ Recomendação: não — sem essa trava, uma pessoa aprova o próprio pedido.

❓ Q2 · raia: desenho · afeta: RQ-04 · depende de: —
A notificação de aprovação sai por evento + listener ou direto na ação?
➡️ Recomendação: evento + listener — o projeto já notifica assim em `Pedido` (fato conferido no step 3).

Rodada 2 (Q1 respondida no Adendo 1)
❓ Q3 · raia: requisito · afeta: RQ-02 · depende de: Q1
Se o aprovador for substituído no meio da etapa, o substituto herda os pedidos já abertos?
➡️ Recomendação: sim — senão o pedido fica sem aprovador.
```

## Filtro de oráculo

Só entra pergunta que toca um `RQ` ou uma `P-nn`. Ramo que não toca nenhum não é perguntado. É o
que impede a entrevista de inchar sem limite (estudo §2.4: sem oráculo, a entrevista otimiza para
completar a árvore, não para cobrir o pedido).

Teste rápido: *se a resposta fosse o oposto da recomendação, alguma `RQ` ou `P-nn` mudaria?* Se não,
a pergunta não entra.

## Premissa de comportamento — falha fechado

Quando o texto não diz o que o sistema faz num caso (aceita ou recusa, mostra ou esconde, notifica ou
não), isso é pergunta da **raia requisito** — nunca uma premissa assumida em silêncio. A `➡️` recomenda
a opção que **falha fechado**: recusa, esconde, não executa. É a mesma regra que a `feature-test-design`
usa na derivação do step 7, para as duas skills não recomendarem direções opostas; o invariante que
vale sob qualquer resposta vira cenário da regra fechada, e a `RQ` fica `aberta — Qn` até a resposta.

```text
❓ Q4 · raia: requisito · afeta: RQ-06 · depende de: —
O pedido com valor acima do teto do centro pode ser enviado para aprovação, ou é bloqueado no envio?
➡️ Recomendação: bloqueado no envio — falha fechado; liberar depois é um Adendo, desfazer um envio indevido não.
```

## Perguntas-semente — condicionais

Candidatas da raia requisito, **só** quando o requisito tem o elemento correspondente. Sem o
elemento, a semente não entra — imposta a toda feature, era overfitting de um único domínio
(estudo §7.1, T10; a origem está em [`casos-medidos.md`](casos-medidos.md#perguntas-semente-do-00-2026-09-21)).

| O requisito tem… | Semente |
|---|---|
| **papéis** | acumulação de papéis, **par a par**: quem é X pode também ser Y? (solicitante × aprovador, aprovador × aprovador de outra etapa). Uma pergunta por par, não uma genérica |
| **visibilidade** | recorte de visibilidade: quem **vê** agora × quem **já participou**. O participante histórico continua vendo? O que vê quem perdeu a corrida? |
| **notificação** | toda notificação tem link: para **onde** leva, e o destino ainda existe e está visível para o destinatário quando ele clica? |
| **texto livre** | todo texto livre tem teto — no model, não só no formulário? |

Cada semente que entra passa pelo filtro de oráculo como qualquer outra pergunta.

## Sinal de escopo

O limiar está no corpo do `SKILL.md` (step 4) e é hipótese a calibrar: nenhuma rodada desta
coletânea o mediu. Ao cruzar o sinal, registrar em `## Auditoria Pré-Implementação` do `03` e
propor ao usuário fatiar (step 8) ou dividir a feature. Referência externa, também não medida aqui: o autor da `grilling` relata 46 perguntas numa sessão típica e 200+ como sinal de
escopo grande demais (estudo §2.1).

## Confronto código × afirmação (step 5)

Quando o código faz outra coisa que o `01` afirma e a divergência é de comportamento — não erro de
fato do plano, como um import que já existe —, a pergunta sai assim:

```text
❓ Q7 · raia: desenho · afeta: RQ-03 · depende de: —
O `01` diz que o total do pedido é recalculado no model; `app/Services/Pedido/CalculaTotal.php` recalcula no service — qual vale?
➡️ Recomendação: no service — é onde o projeto já calcula, e o valor que o usuário vê é o mesmo.
```

A raia segue a regra acima: se a escolha muda o que o usuário observa e o `00` não decide, é
requisito, e vai ao solicitante. A resposta vai para `## Auditoria Pré-Implementação` do `03`,
tabela *Confronto código × afirmação*.

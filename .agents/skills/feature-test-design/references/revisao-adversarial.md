> Referência da feature-test-design 1.16.0. Lida em: revisão adversarial (ao montar o despacho do
> sub-agente — o que entra, o que não entra, as oito tarefas). Fonte única de: o resumo do contrato
> adversarial; a fonte do contrato em si é `agents/fw-adversario-ct.md`.

# Revisão adversarial — resumo do contrato

Disparo, rota, dono do fechamento, host sem sub-agente e o que fazer com os achados estão no
`SKILL.md` §Revisão Adversarial.

O contrato completo e a fonte da verdade é `agents/fw-adversario-ct.md`;
o resumo abaixo não o substitui:

```text
Entrada: 00-requisito.md + 04-casos-de-teste.md (e 05, se houver; e wikis/glossario.md, se existir)
NÃO receber: o PRD, o código, nem o raciocínio de quem derivou

Tarefa: PROVAR que este conjunto deixa passar um defeito.
  1. Escreva no mínimo 5 implementações erradas plausíveis (mais se o conjunto tiver mais de
     20 CTs) que passariam por TODOS os cenários. Para cada uma, a RQ (ou P-nn), a regra
     afetada e a técnica de derivação que faltou
  2. Aponte todo cenário cujo "Então" é fraco — isto é, que passaria com a
     implementação defeituosa (assertOk sozinho, assertSee de layout,
     assertDatabaseHas só com a chave, ausência de assertion sobre o valor) —, e toda
     "Asserção que mata" da tabela de mutantes que não diverge sob o próprio mutante
  3. Aponte todo cenário sem nenhum "Então" e todo cenário com mais de um "Quando"
  4. Aponte toda RQ fechada e toda P-nn vigente do 00 sem cenário que a discrimine. RQ aberta
     não tem cenário por regra: confira que o 04 a lista como "RQ-nn — aberta (Qn), sem
     cenário até a resposta", e aponte cenário que a afirme (é requisito inventado)
  5. Declare as áreas e regras percorridas — a revisão cobre o conjunto inteiro, não só
     a área que a disparou; achado em outra área é achado válido
  Sondas 6–8, só quando o 00 tem o que elas sondam:
  6. Para cada PAR de papéis do requisito, pergunte: o conjunto tem cenário em que a mesma
     pessoa acumula os dois? (solicitante × aprovador; aprovador da etapa 1 × aprovador da
     etapa 2). Par sem cenário é lacuna
  7. Para cada recorte de visibilidade, pergunte: quem JÁ PARTICIPOU continua vendo? e o link
     de toda notificação leva a um destino que o destinatário ainda vê?
  8. Para cada texto livre do requisito, pergunte: há cenário no teto (n, n+1) — no model, não
     só no formulário?

Saída: o formato fixo do agente — um ID ADV-nn por achado, uma seção por tarefa
       (as sondas 6–8 numa seção só) e a seção de leituras negadas
PROIBIDO: elogiar o conjunto, reescrever os cenários, dizer "está bom".
```

As sondas 6–8 só se aplicam quando o `00` tem o que elas sondam (dois ou mais papéis, recorte de
visibilidade ou notificação com link, texto livre). O caso que as motivou está em
`references/casos-medidos.md` §Revisão adversarial.

**Glossário**: o adversário **pode** receber `wikis/glossario.md`, quando existe — vocabulário não é
plano nem código, e sem ele o adversário lê o termo do `00` e do `04` num sentido que ninguém decidiu.
O despacho da `feature-wiki` (step 7) passa `00` + `04`/`05` (+ `wikis/glossario.md`, se existir).

**Cegueira**: no `fw-adversario-ct`, Read e Grep fora da lista da `Entrada` (e dos arquivos das
próprias skills) são negados pelo hook `PreToolUse` do frontmatter — perfil `adversario-ct` do
`guarda-subagente.sh` da `feature-wiki` ≥ 4.0.0; Glob fica permitido porque devolve só nomes. Se o
script não está em `{skills}`, o hook nega tudo e a sessão cai na rota `general-purpose` com
`model: opus`, em que a cegueira é de prompt: o despacho carrega a linha `Entrada` e a linha
`NÃO receber` acima.

## Não autorrevisar — por quê (regra: Proibição 9)

> **Não autorrevisar.** Modelos de linguagem são comprovadamente melhores em **gerar** oráculos
> do que em **classificar** se um oráculo está correto — o mesmo agente conferindo o próprio
> conjunto reproduz o viés que o gerou.

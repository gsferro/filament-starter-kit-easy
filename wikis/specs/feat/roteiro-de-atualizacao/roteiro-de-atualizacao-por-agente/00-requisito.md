# Requisito — Roteiro de atualização por prompt para agentes

## Fonte

- **Origem**: mensagem do mantenedor no chat da sessão de Claude Code, durante a validação da v0.52.0
- **Data**: 2026-10-23
- **Autor / solicitante**: gsferro (mantenedor do kit)
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> posso mandar a proxima melhoria ou espero voce terminar?
> - não é bem uma melhoria, quero criar algum mecanismo de update baseado em prompt para que ao pedir pro agente atualizar (rodando via agente, sem ser usando o command de kit:update manual) o prompt já explique pro agente o que foi feito, os cuidados que precisam ser tomados, como proceder na atualização e todo o processo.
> - dessa forma, cada nova release teria um roteiro como prompt para qualuqer agente (seja mais ou menos capaz) consiga rodar o update seguindo os passos necessários
> - não mudaremos o codigo, apenas acrescentaremos +1 etapa pre-release de gerar o prompt. isso pode ser, inclusive, +1 cenario de testes pos-release, para ver se o prompt proposto, atende ao que foi entregue
> - mas como teremos que propor um prompt, ao fianlizar a entrega, poderiamos usar o CHANGELOG para ser a base ou voce usar a propria /feature-wiki criada para ser, mas essa etapa passa a ser OBRIGATORIA para cada nova publicação (tanto para hotfixs quanto novas releases)

## Decomposição em Cláusulas

<!-- Rascunho do step 0 — revisado depois da entrevista do step 4. -->

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | Existe um mecanismo de atualização baseado em prompt: um agente que recebe o pedido de atualizar o projeto, sem o mantenedor rodar o comando de atualização à mão, tem um prompt a seguir | "quero criar algum mecanismo de update baseado em prompt para que ao pedir pro agente atualizar (rodando via agente, sem ser usando o command de kit:update manual)" | funcional | fechada |
| RQ-02 | O prompt explica ao agente o que foi feito na versão | "o prompt já explique pro agente o que foi feito" | funcional | fechada |
| RQ-03 | O prompt explica os cuidados que precisam ser tomados | "os cuidados que precisam ser tomados" | funcional | fechada |
| RQ-04 | O prompt explica como proceder na atualização e o processo inteiro | "como proceder na atualização e todo o processo" | funcional | fechada |
| RQ-05 | Cada nova release tem o seu roteiro como prompt | "cada nova release teria um roteiro como prompt" | funcional | fechada |
| RQ-06 | O roteiro é seguível por qualquer agente, mais ou menos capaz, que rode o update seguindo os passos necessários | "para qualuqer agente (seja mais ou menos capaz) consiga rodar o update seguindo os passos necessários" | não-funcional | fechada |
| RQ-07 | O código da aplicação não muda: a entrega é uma etapa a mais antes da release, a de gerar o prompt | "não mudaremos o codigo, apenas acrescentaremos +1 etapa pre-release de gerar o prompt" | restrição | fechada |
| RQ-08 | Depois da release, um cenário de teste confere se o prompt proposto atende ao que foi entregue | "isso pode ser, inclusive, +1 cenario de testes pos-release, para ver se o prompt proposto, atende ao que foi entregue" | funcional | substituída por RQ-11 (Adendo 1) |
| RQ-09 | A base do prompt é o CHANGELOG ou a wiki da feature | "poderiamos usar o CHANGELOG para ser a base ou voce usar a propria /feature-wiki criada para ser" | funcional | substituída por RQ-12 (Adendo 1) |
| RQ-10 | A etapa de gerar o prompt é obrigatória em toda publicação, hotfix ou release nova | "essa etapa passa a ser OBRIGATORIA para cada nova publicação (tanto para hotfixs quanto novas releases)" | restrição | fechada |

## Perguntas ao Solicitante

<!-- Preenchidas na entrevista do step 4. -->

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | "isso pode ser, inclusive" — o cenário pós-release com agente é obrigatório como os quatro do checklist, ou opcional? | RQ-08 | obrigatório, 1 agente em sessão nova, nos dois derivados de update | respondida no Adendo 1 |
| Q2 | "CHANGELOG … ou … /feature-wiki" — qual é a base do prompt: um, outro, ou os dois? | RQ-09 | os dois, reconciliados pelo mantenedor | respondida no Adendo 1 |
| Q3 | "não mudaremos o codigo" — o que pode entrar: só documentos, ou também testes e o workflow de release? O agente usa o `kit:update`? | RQ-07 | docs + testes + workflow; o agente usa o comando | respondida no Adendo 1 |
| Q4 | De que versão o roteiro parte: só da anterior, desde uma versão fixa, ou de toda a história? | RQ-05, RQ-06 | desde a 0.49, por link fixado | respondida no Adendo 1 (só da anterior) |
| Q5 | O agente lê o roteiro antes de atualizar, mas o projeto na versão anterior ainda não o tem: como ele chega no roteiro? | RQ-01, RQ-15 | link raw na tag + cópia local | respondida no Adendo 2 |
| Q6 | Como a obrigatoriedade é garantida, se o workflow de release só roda depois do push da tag? | RQ-10, RQ-16 | guarda no CI da main | respondida no Adendo 2 |
| Q7 | Se o cenário pós-release reprovar o roteiro, o que acontece com a publicação? | RQ-11 | corrige o roteiro na main | respondida no Adendo 2 |
| Q8 | Quando a versão muda um default, o roteiro manda o agente decidir sozinho ou parar e perguntar ao dono do projeto? | RQ-03, RQ-04 | para e pergunta | respondida no Adendo 2 |
| Q12 | O workflow de release confere só que o roteiro existe, ou também as nove seções do contrato? | RQ-23 | existe + nove seções | respondida no Adendo 3 |
| Q13 | Depois de uma revisão na main, o link de uma versão que não é a v0.52.0 pode apontar para a main ou para um commit? | RQ-19, RQ-24 | só com o registro da revisão no CHANGELOG | respondida no Adendo 3 |
| Q14 | Marcador de versão sem seção correspondente no CHANGELOG: a guarda reprova? | RQ-22 | reprova | respondida no Adendo 3 |
| Q16 | Quem fixa a lista de pontos de decisão da versão, contra a qual o roteiro é conferido? | RQ-25, RQ-14 | o checklist exige a lista | respondida no Adendo 3 |
| Q17 | "Customizações do projeto preservadas" inclui os dados do banco? | RQ-12 | sim | respondida no Adendo 3 |
| Q18 | Depois de uma revisão na main, como quem monta o link pelo padrão chega à correção? | RQ-20, RQ-24 | o padrão manda conferir a main | respondida no Adendo 3 |
| Q19 | O `migrate:rollback` entra nos "afins" dos comandos destrutivos do banco? | RQ-31 | sim | respondida no Adendo 4 |
| Q20 | A guarda de comando destrutivo vale só em `## Passos` ou em toda seção que o agente executa? | RQ-31 | toda seção | respondida no Adendo 4 |
| Q21 | Conflito do `kit:update` num arquivo do kit que o dono editou é ponto de decisão do dono? | RQ-12, RQ-25 | é decisão | respondida no Adendo 4 |

## Premissas

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | O roteiro da v0.52.0 nasce depois da tag dela (a tag já foi publicada sem o arquivo): o link desse roteiro aponta para a main, e não para a tag | step 4 — decisão de desenho D6 sobre RQ-19 e RQ-24 | 2026-10-23 | RQ-19, RQ-24 | vigente |

## Fora de Escopo (declarado)

- Mudar o comportamento do `kit:update` ou de qualquer código da aplicação (RQ-07).

## Adendo 1 — 2026-10-23

- **Fonte**: respostas do solicitante (gsferro) à primeira rodada da entrevista, escolhidas entre opções apresentadas no chat
- **Fidelidade**: alta (escolha registrada pelo próprio solicitante)
- **Responde a**: Q1, Q2, Q3, Q4

### Texto Original

<!-- IMUTÁVEL. As escolhas como o solicitante as selecionou. -->

> Q1 — O cenário pós-release em que um agente executa o roteiro num projeto da versão anterior é obrigatório em toda publicação (como os 4 do checklist)? = "Obrigatório, 1 agente": toda publicação: sessão NOVA de um agente (fora do contexto de quem escreveu), nos dois derivados de update (com e sem tenancy), critério objetivo = mesmo estado dos cenários 3/4 + canais/customização preservados. Outros agentes/hosts com cadência declarada, sem prometer 'todos'.
>
> Q2 — Qual é a base do roteiro? = "CHANGELOG + wiki": o CHANGELOG consolida a publicação; a wiki da feature (P-nn, ADR, notas) traz os cuidados finos; o mantenedor reconcilia com o que foi entregue. O agente do projeto lê só o roteiro final, autossuficiente.
>
> Q3 — "Não mudaremos o código": o que pode entrar na entrega? = "Docs + testes + workflow": nada muda no app nem no kit:update; entram o roteiro, uma guarda de teste no CI (toda versão tem roteiro) e o checklist/workflow de release. O agente USA o kit:update como ferramenta.
>
> Q5/Q6 — De onde o agente atualiza, e como acha o roteiro? = "Só da versão anterior": cada roteiro cobre apenas anterior → nova. Salto longo = encadear manualmente.

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-11 | Toda publicação passa por um cenário pós-release obrigatório: um agente em sessão nova, sem o contexto de quem escreveu o roteiro, atualiza dois projetos da versão anterior (com e sem multi-organização) seguindo só o roteiro | "Obrigatório, 1 agente": "sessão NOVA de um agente (fora do contexto de quem escreveu), nos dois derivados de update (com e sem tenancy)" | funcional | RQ-08 |
| RQ-12 | O cenário pós-release passa quando os dois projetos chegam ao mesmo estado dos cenários 3 e 4 do checklist, com canais e customizações do projeto preservados | "critério objetivo = mesmo estado dos cenários 3/4 + canais/customização preservados" | funcional | — |
| RQ-13 | Outros agentes e hosts rodam o cenário numa cadência declarada, e o roteiro não promete funcionar em todos | "Outros agentes/hosts com cadência declarada, sem prometer 'todos'" | restrição | — |
| RQ-14 | O roteiro é escrito a partir do CHANGELOG da publicação e da wiki da feature, e o mantenedor reconcilia os dois com o que foi entregue | "o CHANGELOG consolida a publicação; a wiki da feature (P-nn, ADR, notas) traz os cuidados finos; o mantenedor reconcilia com o que foi entregue" | funcional | RQ-09 |
| RQ-15 | O roteiro final é autossuficiente: o agente do projeto lê só ele | "O agente do projeto lê só o roteiro final, autossuficiente." | não-funcional | — |
| RQ-16 | Nada muda na aplicação nem no comando de atualização; a entrega é o roteiro, uma guarda de teste no CI de que toda versão tem roteiro, e o checklist e o workflow de release | "nada muda no app nem no kit:update; entram o roteiro, uma guarda de teste no CI (toda versão tem roteiro) e o checklist/workflow de release" | restrição | RQ-07 (parcial) |
| RQ-17 | O agente usa o comando de atualização do kit como ferramenta, guiado pelo roteiro | "O agente USA o kit:update como ferramenta." | funcional | — |
| RQ-18 | Cada roteiro cobre só a passagem da versão anterior para a nova; um salto longo é o encadeamento manual dos roteiros intermediários | "cada roteiro cobre apenas anterior → nova. Salto longo = encadear manualmente." | restrição | — |

## Adendo 2 — 2026-10-23

- **Fonte**: respostas do solicitante (gsferro) à segunda rodada da entrevista, escolhidas entre opções apresentadas no chat
- **Fidelidade**: alta (escolha registrada pelo próprio solicitante)
- **Responde a**: Q5, Q6, Q7, Q8

### Texto Original

<!-- IMUTÁVEL. As escolhas como o solicitante as selecionou. -->

> Q5 — O agente precisa ler o roteiro ANTES de atualizar, mas o projeto na versão anterior ainda não tem o roteiro da nova (docs/ e CHANGELOG não viajam). Como o agente chega nele? = "Link raw na tag + cópia local": o roteiro de cada versão mora no repo do kit e o agente lê pela URL raw do GitHub fixada na tag destino (o pedido traz o link, ou o roteiro anterior já ensina o padrão do link). Uma cópia também viaja no projeto (wiki já entregue) para consulta depois.
>
> Q6 — Como a obrigatoriedade é garantida, já que hoje o workflow de release só roda depois do push da tag? = "Guarda no CI da main": teste no CI exige o roteiro da versão que está em config/kit.php: o commit de versão sem roteiro já fica vermelho ANTES da tag. Mais: linha obrigatória no checklist e o workflow de release confere também.
>
> Q7 — Se o cenário pós-release reprovar o roteiro (o agente não chega ao estado esperado), o que acontece com a publicação? = "Corrige o roteiro na main": a tag não é reescrita. O roteiro corrigido entra na main com a evidência nova; o link do roteiro passa a apontar para a main (ou para o commit corrigido) e o CHANGELOG registra que a versão X teve o roteiro revisado. Se a falha for do CÓDIGO, é hotfix.
>
> Q8 — Quando a versão muda um default (ex.: na 0.51 o chat de IA nasce desligado), o roteiro manda o agente decidir sozinho ou parar e perguntar ao dono do projeto? = "Para e pergunta": todo ponto de decisão do dono (manter IA ligada, semear o tutorial, ligar o interruptor) vira uma pergunta explícita antes do passo, com a recomendação da release. Sem resposta, segue o default seguro e registra.

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-19 | O roteiro de cada versão mora no repositório do kit e o agente o lê pela URL raw fixada na tag destino, antes de alterar o projeto | "o roteiro de cada versão mora no repo do kit e o agente lê pela URL raw do GitHub fixada na tag destino" | funcional | — |
| RQ-20 | O agente chega ao link pelo pedido, ou pelo roteiro anterior, que ensina o padrão do link | "(o pedido traz o link, ou o roteiro anterior já ensina o padrão do link)" | funcional | — |
| RQ-21 | Uma cópia do roteiro também viaja no projeto, num arquivo já entregue, para consulta depois | "Uma cópia também viaja no projeto (wiki já entregue) para consulta depois." | funcional | — |
| RQ-22 | Um teste no CI exige o roteiro da versão declarada no marcador de versão do kit: o commit de versão sem roteiro fica vermelho antes da tag | "teste no CI exige o roteiro da versão que está em config/kit.php: o commit de versão sem roteiro já fica vermelho ANTES da tag" | funcional | — |
| RQ-23 | O checklist de release ganha a linha obrigatória do roteiro, e o workflow de release também confere o roteiro | "Mais: linha obrigatória no checklist e o workflow de release confere também." | funcional | — |
| RQ-24 | Reprovação do cenário pós-release não reescreve a tag: o roteiro é corrigido na main com evidência nova, o link passa a apontar para a correção e o CHANGELOG registra a revisão; falha do código vira hotfix | "a tag não é reescrita. O roteiro corrigido entra na main com a evidência nova; o link do roteiro passa a apontar para a main (ou para o commit corrigido) e o CHANGELOG registra que a versão X teve o roteiro revisado. Se a falha for do CÓDIGO, é hotfix." | funcional | — |
| RQ-25 | Todo ponto de decisão do dono do projeto vira uma pergunta explícita antes do passo, com a recomendação da release | "todo ponto de decisão do dono (manter IA ligada, semear o tutorial, ligar o interruptor) vira uma pergunta explícita antes do passo, com a recomendação da release" | funcional | — |
| RQ-26 | Sem resposta do dono, o agente segue o default seguro e registra a escolha | "Sem resposta, segue o default seguro e registra." | funcional | — |

## Adendo 3 — 2026-10-23

- **Fonte**: respostas do solicitante (gsferro) às perguntas do step 7 (derivação dos casos de teste e revisão adversarial), escolhidas entre opções apresentadas no chat
- **Fidelidade**: alta (escolha registrada pelo próprio solicitante)
- **Responde a**: Q12, Q13, Q14, Q16, Q17, Q18 (Q15 é de desenho: D12 do `01`)

### Texto Original

<!-- IMUTÁVEL. As escolhas como o solicitante as selecionou. -->

> Q12 — o passo novo no workflow de release (depois do push da tag) confere só que o roteiro existe, ou também as nove seções do contrato? = "Existe + 9 seções": Falha fechado na tag também; a guarda do CI na main já confere o resto.
>
> Q13 — depois de corrigir o roteiro na main (RQ-24), o link raw de uma versão que não é a v0.52.0 pode apontar para main/commit em vez da tag? = "Só com registro": Aceita main só se a seção da versão no CHANGELOG registrar a revisão; senão exige a tag.
>
> Q14 — se o marcador de versão não tiver seção correspondente no CHANGELOG, a guarda do CI reprova? = "Reprova": Falha fechado, nomeando a seção que falta.
>
> Q16 — quem fixa a lista de pontos de decisão da versão (todo default que mudou), contra a qual o roteiro é conferido? = "Checklist exige lista": Na etapa pré-release o mantenedor lista todo default mudado na reconciliação; o CP-01 confere que cada item virou pergunta. Lista ausente reprova.
>
> Q17 — 'customizações do projeto preservadas' (RQ-12) inclui os dados do banco? = "Sim, inclui dados": Canário de registro no banco no CP-01 + guarda na suíte que reprova migrate:fresh, db:wipe e afins em '## Passos'.
>
> Q18 — depois de uma revisão do roteiro na main (RQ-24), quem monta o link pelo padrão chega na tag, ou seja, ao roteiro reprovado. Como chegar à correção? = "Conferir a main": O padrão manda ler também o arquivo na main e preferi-lo quando ele registrar a revisão; na dúvida, parar.

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-27 | O workflow de release confere que o roteiro da tag existe e traz as nove seções do contrato; falha fechado | "Falha fechado na tag também" | funcional | — |
| RQ-28 | O link de uma versão que não é a v0.52.0 aponta para a main (ou um commit) só quando a seção da versão no CHANGELOG registra a revisão; sem o registro, exige a tag | "Aceita main só se a seção da versão no CHANGELOG registrar a revisão; senão exige a tag." | funcional | — |
| RQ-29 | Marcador de versão sem seção correspondente no CHANGELOG reprova a guarda, nomeando a seção que falta | "Falha fechado, nomeando a seção que falta." | funcional | — |
| RQ-30 | Na etapa pré-release o mantenedor lista todo default que a versão mudou; cada item da lista vira pergunta no roteiro; o cenário pós-release confere; lista ausente reprova | "o mantenedor lista todo default mudado na reconciliação; o CP-01 confere que cada item virou pergunta. Lista ausente reprova." | funcional | — |
| RQ-31 | As customizações preservadas incluem os dados do banco: o cenário pós-release confere um registro do dono, e a guarda reprova comando destrutivo do banco (`migrate:fresh`, `db:wipe` e afins) nos passos | "Canário de registro no banco no CP-01 + guarda na suíte que reprova migrate:fresh, db:wipe e afins" | funcional | — |
| RQ-32 | O padrão do link manda ler também o arquivo do roteiro na main e preferi-lo quando ele registrar a revisão; na dúvida, o agente para | "O padrão manda ler também o arquivo na main e preferi-lo quando ele registrar a revisão; na dúvida, parar." | funcional | — |

## Adendo 4 — 2026-10-23

- **Fonte**: respostas do solicitante (gsferro) às perguntas da revisão adversarial do step 7 (rodadas 1 e 2), escolhidas entre opções apresentadas no chat
- **Fidelidade**: alta (escolha registrada pelo próprio solicitante)
- **Responde a**: Q19, Q20, Q21

### Texto Original

<!-- IMUTÁVEL. As escolhas como o solicitante as selecionou. -->

> Q19 — a guarda reprova comando destrutivo do banco nos passos do roteiro (migrate:fresh, db:wipe, migrate:refresh, migrate:reset). O migrate:rollback entra nos 'afins'? = "Sim, reprova": Falha fechado: rollback pode apagar tabelas com dados do dono.
>
> Q20 — a guarda de comando destrutivo do banco vale só em '## Passos' ou em toda seção que o agente executa ('Antes de começar', 'Como conferir', 'Quando parar')? = "Toda seção": Busca no texto bruto, com comentários e qualquer prefixo (docker, sail). Só '## Cuidados' pode citar o comando, como proibição.
>
> Q21 — um conflito do kit:update num arquivo do kit que o dono editou é ponto de decisão do dono? Hoje o cenário com agente passa se o agente sobrescreve e só relata. = "É decisão": Vira pergunta antes do passo; sem resposta, mantém a versão do dono e registra. O CP confere o arquivo intacto, não o relatório.

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-33 | O `migrate:rollback` é comando destrutivo do banco, reprovado como os outros da RQ-31 | "rollback pode apagar tabelas com dados do dono" | funcional | — |
| RQ-34 | A guarda de comando destrutivo do banco confere o texto bruto do roteiro, com comentários e qualquer prefixo de invocação, em toda seção; só `## Cuidados` pode citar o comando, como proibição | "Busca no texto bruto, com comentários e qualquer prefixo (docker, sail). Só '## Cuidados' pode citar o comando, como proibição." | funcional | RQ-31 (recorte "nos passos") |
| RQ-35 | Conflito do `kit:update` num arquivo do kit que o dono editou é ponto de decisão do dono: pergunta antes do passo; sem resposta, mantém a versão do dono e registra; o cenário pós-release confere o arquivo intacto | "Vira pergunta antes do passo; sem resposta, mantém a versão do dono e registra. O CP confere o arquivo intacto, não o relatório." | funcional | — |

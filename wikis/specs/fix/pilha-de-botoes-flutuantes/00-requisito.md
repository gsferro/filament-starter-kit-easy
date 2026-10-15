# Requisito — Pilha harmônica dos botões flutuantes (dock × "Voltar ao topo" × chat da IA)

## Fonte

- **Origem**: mensagem do solicitante no chat, depois de ver o dock de atalhos funcionando no servidor local
- **Data**: 2026-10-14
- **Autor / solicitante**: Guilherme Ferro (mantenedor do kit)
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> - se o botão estiver no canto inferior direito, e a tela tiver rodado, ele fica em cima do botão "Voltar ao topo", o ideal agora é que o botão de voltar, fique mais a cima quando houver esse cenario.
> - faça o seu teste controlado, e depois abra uma /feature-wiki se necessário para adequar a essa possibilidade
> - ainda tem o botão do chat da IA, que deveria ficar no mesmo canto, faça com que eles fiquem harmonicos na tela
> - ao final, pode gerar a v0.48.1 com essas correções antes de eu subir a outra melhoria

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | Com o dock no canto inferior direito e a página rolada, o "Voltar ao topo" não fica coberto pelo dock: ele sobe para cima do dock | "se o botão estiver no canto inferior direito, e a tela tiver rodado, ele fica em cima do botão \"Voltar ao topo\", o ideal agora é que o botão de voltar, fique mais a cima quando houver esse cenario" | funcional | fechada |
| RQ-02 | O comportamento é comprovado por um teste controlado antes da adequação | "faça o seu teste controlado" | restrição | fechada |
| RQ-03 | O botão do chat da IA fica no mesmo canto inferior direito dos outros dois | "ainda tem o botão do chat da IA, que deveria ficar no mesmo canto" | funcional | fechada |
| RQ-04 | Os botões flutuantes do canto inferior direito ficam harmônicos na tela: nenhum cobre outro e a disposição é organizada | "faça com que eles fiquem harmonicos na tela" | funcional | fechada |
| RQ-05 | A correção sai na v0.48.1 (tag e release), antes da próxima melhoria do solicitante | "ao final, pode gerar a v0.48.1 com essas correções antes de eu subir a outra melhoria" | restrição | fechada |

## Perguntas ao Solicitante

<!-- Raia requisito. As quatro perguntas foram feitas numa rodada só, antes do 01; as respostas estão no Adendo 1. -->

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | Com o dock no canto inferior direito, como os três botões se organizam? | RQ-01, RQ-03, RQ-04 | o dock fica no canto escolhido pelo usuário; o chat logo acima; o "Voltar ao topo" acima do chat; todos no mesmo eixo vertical, com o mesmo espaço entre eles | respondida no Adendo 1 |
| Q2 | Com o dock ABERTO no canto inferior direito (o leque sobe por onde ficam os outros dois), o que acontece com o chat e o "Voltar ao topo"? | RQ-04 | somem enquanto o dock está aberto e voltam ao fechar | respondida no Adendo 1 |
| Q3 | O chat da IA está sem estilo nos painéis (achado do teste controlado). Corrigir neste trabalho? | RQ-03, RQ-04 | corrigir o botão e o slide-over | respondida no Adendo 1 |
| Q4 | O dock solto pelo arrasto perto da coluna da direita, sem estar no canto, faz a pilha reagir? | RQ-01, RQ-04 | reagir a qualquer sobreposição com a coluna dos botões | respondida no Adendo 1 |
| Q5 | O que é "a área da coluna dos botões da direita"? Com o dock solto na borda direita a meia altura, longe do chat e do "Voltar ao topo", a pilha reage? | RQ-12 | não reage: só quando o dock cruza o lugar de um botão da pilha, ou o espaço entre eles | respondida no Adendo 2 |
| Q6 | Com o dock solto na coluna, fora do canto, e aberto, o chat e o "Voltar ao topo" somem como no canto? | RQ-09 | somem | respondida no Adendo 2 |
| Q7 | No layout barra (sempre expandido) no canto inferior direito, o chat e o "Voltar ao topo" somem ou se empilham acima da barra? | RQ-09, RQ-04 | empilham acima da barra e continuam visíveis | respondida no Adendo 2 |
| Q8 | Qual o espaço entre vizinhos da pilha, e ele é o mesmo em todo painel? | RQ-08 | 16 px em todo painel | respondida no Adendo 2 |
| Q9 | No tema escuro, o slide-over do chat segue o tema com contraste mínimo? | RQ-11 | sim, título a pelo menos 4,5:1 do fundo, nos dois temas | respondida no Adendo 2 |
| Q12 | No layout barra no canto inferior direito, a pilha fica no eixo de qual ponto da barra? | RQ-15, RQ-07 | na mesma coluna de sempre, no eixo do botão da barra que fica no canto | respondida no Adendo 3 |
| Q14 | A tela de escolha de painel do login unificado (`/login/painel`) conta como "tela de login"? | RQ-20 | sim, sem o dock | respondida no Adendo 6 |
| Q15 | Na tela de bloqueio e no desafio 2FA do `/app`, o chat da IA também some? | RQ-19, RQ-21 | sim, nas mesmas telas que o dock | respondida no Adendo 6 |
| Q16 | Como indicar a organização de cada favorito do `/app`? | RQ-24 | um grupo por organização, com o separador "{painel} · {organização}" | respondida no Adendo 7 |
| Q17 | O tooltip de cada favorito mostra o caminho ou a URL completa? | RQ-26 | o caminho | respondida no Adendo 7 |
| Q18 | Saber se o usuário tem mais de uma organização custa 1 consulta por request; mostrar sempre a organização ou só com mais de uma? | RQ-24, RQ-25 | sempre, com zero consulta | respondida no Adendo 7 |
| Q19 | Em que ordem ficam os grupos de organização do `/app` no dock? | RQ-24 | na ordem em que o primeiro favorito de cada organização foi gravado | respondida no Adendo 8 |

## Premissas

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | Sem JavaScript, ou sem o dock na página, os botões ficam nas posições de hoje: o chat no canto e o "Voltar ao topo" acima do chat no painel que tem chat, no canto nos outros | decisão de desenho (falha fechada da pilha) | 2026-10-14 | RQ-04 | substituída por P-03 |
| P-03 | Sem JavaScript, ou sem o dock na página, os botões ficam nas alturas de hoje (o chat no canto; o "Voltar ao topo" acima do chat no painel que tem chat, no canto nos outros). Na horizontal, o "Voltar ao topo" fica sempre no eixo comum da pilha (30 px da borda, centro alinhado ao do chat e do gatilho do dock), para não saltar de lado quando o dock chega ao canto | implementação — divergência classificada (b) pelo executor dos CT-B na folga direita de 24 px | 2026-10-14 | RQ-04, RQ-07 | vigente |
| P-02 | A pilha vale nos três painéis e em qualquer painel registrado depois, como o "Voltar ao topo" | decisão de desenho (mesmo alcance do render hook global) | 2026-10-14 | RQ-01, RQ-04 | vigente |

## Fora de Escopo (declarado)

- Mudar o comportamento, o conteúdo ou o servidor do chat da IA (mensagens, histórico, guardrails, LLM): só a apresentação nos painéis.
- O canto inferior esquerdo e os cantos superiores: o dock nesses lugares não disputa espaço com os outros dois, e nada muda ali.
- Telas fora dos painéis (site público). ~~telas de autenticação~~ *(alterado em 2026-10-15: QA-04 — os Adendos 4 a 6 trouxeram bloqueio, login e telas do Auth Designer para o escopo, RQ-19 a RQ-23)*

## Adendo 1 — 2026-10-14

- **Fonte**: respostas do solicitante às perguntas Q1 a Q4, escolhidas no chat
- **Fidelidade**: alta (opções escolhidas por escrito)
- **Responde a**: Q1, Q2, Q3, Q4

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> "Com o dock no canto inferior direito, como os três botões se organizam?"="Dock no canto, pilha acima (Recommended)", "Quando o dock está ABERTO no canto inferior direito (o leque sobe por cima de onde ficam o chat e o "Voltar ao topo"), o que acontece com os outros dois?"="Somem enquanto aberto (Recommended)", "O chat da IA está sem estilo nos painéis desde o esqueleto do kit. Corrijo neste mesmo trabalho?"="Botão e slide-over (Recommended)", "O dock também pode ser arrastado livremente (não só para os cantos). Se ele for solto perto da coluna da direita, sem estar exatamente no canto, a pilha reage?"="Reage a qualquer sobreposição (Recommended)"

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-06 | O dock fica no canto inferior direito quando o usuário o põe lá; o chat fica logo acima do dock e o "Voltar ao topo" acima do chat | "Dock no canto, pilha acima" | funcional | — |
| RQ-07 | Os botões da pilha ficam centralizados no mesmo eixo vertical | "Dock no canto, pilha acima" (descrição: "todos centralizados no mesmo eixo vertical") | funcional | — |
| RQ-08 | O espaço entre botões vizinhos da pilha é o mesmo | "Dock no canto, pilha acima" (descrição: "com o mesmo espaço entre eles") | funcional | — |
| RQ-09 | Com o dock aberto no canto inferior direito, o chat e o "Voltar ao topo" ficam ocultos, e voltam quando o dock fecha | "Somem enquanto aberto" | funcional | — |
| RQ-10 | O botão flutuante do chat aparece estilizado nos painéis: fixo no canto inferior direito, redondo, na cor primária | "Botão e slide-over" | funcional | — |
| RQ-11 | O slide-over do chat aparece estilizado nos painéis: painel lateral fixo à direita, com cabeçalho, conversa e campo de mensagem | "Botão e slide-over" | funcional | — |
| RQ-12 | A pilha reage sempre que o dock ocupa a área da coluna dos botões da direita, inclusive solto pelo arrasto fora do canto | "Reage a qualquer sobreposição" | funcional | — |

## Adendo 2 — 2026-10-14

- **Fonte**: respostas do solicitante às perguntas Q5 a Q9 (devolvidas pela derivação dos casos de teste), escolhidas no chat
- **Fidelidade**: alta (opções escolhidas por escrito)
- **Responde a**: Q5, Q6, Q7, Q8, Q9

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> "Com o dock solto na borda direita (fora do canto), quando a pilha reage? E se ele estiver aberto ali?"="Só se cobrir um botão; aberto, oculta (Recommended)", "No layout BARRA (o dock fica sempre expandido como barra), no canto inferior direito: o chat e o "Voltar ao topo"…"="Empilham acima da barra (Recommended)", "Qual o espaço entre botões vizinhos da pilha?"="16 px em todo painel (Recommended)", "No tema escuro, o slide-over do chat deve seguir o tema com contraste mínimo legado?"="Sim, contraste ≥ 4,5:1 (Recommended)"

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-13 | A pilha reage só quando o dock cruza o lugar de um botão da pilha, ou o espaço entre eles; com o dock em outro ponto da borda direita, longe deles, nada muda | "Só se cobrir um botão" | funcional | RQ-12 (parcial) |
| RQ-14 | Com o dock solto sobre o lugar da pilha, fora do canto, e aberto, o chat e o "Voltar ao topo" ficam ocultos, como no canto | "aberto, oculta" | funcional | — |
| RQ-15 | Com o dock no layout barra no canto inferior direito, o chat e o "Voltar ao topo" continuam visíveis, empilhados acima da barra | "Empilham acima da barra" | funcional | — |
| RQ-16 | O espaço entre botões vizinhos da pilha é de 16 px, em todo painel | "16 px em todo painel" | funcional | — |
| RQ-17 | O slide-over do chat segue o tema do painel, com o título a pelo menos 4,5:1 de contraste com o fundo, nos dois temas | "Sim, contraste ≥ 4,5:1" | não-funcional | — |

## Adendo 3 — 2026-10-14

- **Fonte**: resposta do solicitante à pergunta Q12 (devolvida pela segunda rodada da derivação dos casos de teste), escolhida no chat
- **Fidelidade**: alta (opção escolhida por escrito)
- **Responde a**: Q12

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> "No layout BARRA no canto inferior direito, a barra é larga e se estende para a esquerda. O chat e o "Voltar ao topo" ficam no eixo de qual ponto?"="Mesma coluna de sempre (Recommended)"

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-18 | No layout barra no canto inferior direito, o chat e o "Voltar ao topo" ficam na mesma coluna de sempre, no eixo do botão da barra que fica no canto | "Mesma coluna de sempre" | funcional | — |

## Adendo 4 — 2026-10-14

- **Fonte**: pedido do solicitante no chat, durante a implementação
- **Fidelidade**: alta (texto escrito)
- **Responde a**: —

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> - quando a tela estiver em "/screen/lock" o botão de dock não deve ser exibido

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-19 | Na tela de bloqueio (`/screen/lock`), o botão do dock de atalhos não é exibido | "quando a tela estiver em \"/screen/lock\" o botão de dock não deve ser exibido" | funcional | — |

## Adendo 5 — 2026-10-14

- **Fonte**: pedido do solicitante no chat, logo depois do Adendo 4
- **Fidelidade**: alta (texto escrito)
- **Responde a**: —

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> - como não deve ser exibido também em nenhuma tela de login ou que use o pacote Auth Designer

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-20 | O botão do dock não é exibido em nenhuma tela de login | "não deve ser exibido também em nenhuma tela de login" | funcional | — |
| RQ-21 | O botão do dock não é exibido em nenhuma tela que use o pacote Auth Designer | "ou que use o pacote Auth Designer" | funcional | — |

## Adendo 6 — 2026-10-14

- **Fonte**: respostas do solicitante às perguntas Q14 e Q15 (devolvidas pela derivação dos casos de teste dos Adendos 4 e 5), escolhidas no chat
- **Fidelidade**: alta (opções escolhidas por escrito)
- **Responde a**: Q14, Q15

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> "A tela de escolha de painel do login unificado (/login/painel) conta como "tela de login"? O usuário já está autenticado ali e pode ter o dock."="Sim, sem o dock (Recommended)", "Na tela de bloqueio (e no desafio 2FA) do /app, o chat da IA também deve sumir? O slide-over dele mostra os títulos das conversas anteriores."="Sim, some também (Recommended)"

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-22 | A tela de escolha de painel do login unificado é tela de login: o dock não é exibido nela | "Sim, sem o dock" | funcional | — |
| RQ-23 | O chat da IA não é exibido nas mesmas telas em que o dock não é exibido (bloqueio, login e telas do Auth Designer) | "Sim, some também" | funcional | — |

## Adendo 7 — 2026-10-14

- **Fonte**: pedido do solicitante no chat, durante a implementação, e as respostas dele às três perguntas que o pedido gerou (Q16 a Q18), escolhidas no chat
- **Fidelidade**: alta (texto escrito e opções escolhidas por escrito)
- **Responde a**: Q16, Q17, Q18

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> - quando o multi-tenant estiver ativo, e o usuário tiver acesso a mais de um tenant, precisa ter algum indicativo ou label de qual tenant o dock que ele salvou pertence
> - poderia ser adicionado um tooltip com a url que sera direcionado também para melhor UX

> "Com multi-tenant ativo e o usuário em mais de uma organização, como mostrar de qual organização é cada favorito do painel /app no dock?"="Um grupo por organização (Recommended)", "O tooltip de cada favorito mostra o quê?"="O caminho (Recommended)"

> "Saber se o usuário tem mais de uma organização custa 1 consulta ao banco por request, e o dock hoje promete zero consulta para se montar. Como resolver?"="Sempre com a organização (Recommended)"

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-24 | Com o multi-tenant ativo, os favoritos do painel de organização aparecem no dock agrupados por organização, cada grupo com o separador "{rótulo do painel} · {nome da organização}", inclusive para quem tem acesso a uma organização só | "precisa ter algum indicativo ou label de qual tenant o dock que ele salvou pertence" + "Um grupo por organização" + "Sempre com a organização" | funcional | — |
| RQ-25 | Indicar a organização não acrescenta consulta ao banco para montar o dock | "Sempre com a organização" (descrição: "Zero consulta") | não-funcional | — |
| RQ-26 | Cada favorito do dock mostra, num tooltip, o caminho para onde ele leva | "poderia ser adicionado um tooltip com a url que sera direcionado" + "O caminho" | funcional | — |

## Adendo 8 — 2026-10-14

- **Fonte**: resposta do solicitante à pergunta Q19 (devolvida pela derivação dos casos de teste do Adendo 7), escolhida no chat
- **Fidelidade**: alta (opção escolhida por escrito)
- **Responde a**: Q19

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> "Quando os favoritos do /app são de mais de uma organização, em que ordem ficam os grupos de organização no dock?"="Ordem em que foram gravados (Recommended)"

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-27 | Os grupos de organização do painel de organização seguem a ordem em que o primeiro favorito de cada organização foi gravado | "Ordem em que foram gravados" | funcional | — |


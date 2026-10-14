# Requisito — Dock de ações rápidas (Quick Action Dock)

## Fonte

- **Origem**: pedido do solicitante no chat, via `/feature-wiki`
- **Data**: 2026-10-13
- **Autor / solicitante**: gsferro (desenvolvedor e solicitante)
- **Fidelidade**: alta (texto escrito) — mas o texto é **exploratório**: formula alternativas ("poderia ser … ou …", "?") em vez de afirmar comportamento. As alternativas viraram perguntas em `## Perguntas ao Solicitante`

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> faça uma analise completa e detalhada no pacote: "https://filamentphp.com/plugins/hocein-el-idrissi-quick-action-dock"
> - poderia ser uma opção de action favoritos do user ou dentro da settings, configurar quais as urls estaria disponiveis (respeitando as permissões correspondentes)
> - ter uma permissão exclusiva ou dar permissão de uso para determinado perfil?
> - escolher qual painel teria uso do botão ou se for um botão de favorito do user, ele poderia abrir qualquer url em qualquer painel
> - dar a opção de escolha do layout no settings ou na config do user, usando: "https://filamentphp.com/plugins/hocein-el-idrissi-quick-action-dock#layouts"

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | Entregar uma análise completa e detalhada do pacote Quick Action Dock | "faça uma analise completa e detalhada no pacote" | funcional | fechada |
| RQ-02 | O dock oferece atalhos para URLs da aplicação | "configurar quais as urls estaria disponiveis" | funcional | fechada |
| RQ-03 | A origem dos atalhos é a lista de favoritos do usuário e/ou uma lista definida no settings | "poderia ser uma opção de action favoritos do user ou dentro da settings" | funcional | substituída por RQ-10 (Adendo 1) |
| RQ-04 | Um atalho só aparece para quem tem a permissão do destino | "(respeitando as permissões correspondentes)" | autorização | fechada |
| RQ-05 | O uso do dock é controlado por permissão exclusiva ou concedido por perfil | "ter uma permissão exclusiva ou dar permissão de uso para determinado perfil?" | autorização | substituída por RQ-13 (Adendo 1) |
| RQ-06 | É possível escolher em qual painel o botão do dock aparece | "escolher qual painel teria uso do botão" | funcional | substituída por RQ-14 (Adendo 1) |
| RQ-07 | O favorito do usuário pode abrir uma URL de qualquer painel, a partir de qualquer painel | "se for um botão de favorito do user, ele poderia abrir qualquer url em qualquer painel" | funcional | substituída por RQ-15 (Adendo 1) |
| RQ-08 | O layout do dock é escolhível, entre os layouts do pacote | "dar a opção de escolha do layout … usando: …#layouts" | funcional | fechada |
| RQ-09 | O lugar da escolha do layout é o settings e/ou a configuração do usuário | "no settings ou na config do user" | funcional | substituída por RQ-16 (Adendo 1) |

## Perguntas ao Solicitante

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | De onde vêm os atalhos: (a) favoritos que cada usuário escolhe; (b) lista única definida no settings; (c) settings define um padrão e o usuário acrescenta favoritos? | RQ-03 | (a) favoritos do usuário, escolhidos de um catálogo gerado automaticamente com as telas que ele já pode abrir; o settings só liga/desliga e define o padrão de layout. Lista de URLs no settings envelhece a cada Resource novo (mesmo motivo do hub de cards) | respondida no Adendo 1 |
| Q2 | O favorito pode ser URL livre digitada (inclusive externa) ou só um destino do catálogo (Resource/Page da aplicação)? | RQ-02, RQ-04 | Só destino do catálogo. URL livre não tem permissão para conferir — RQ-04 vira impossível de garantir — e URL externa abre redirecionamento para fora | respondida no Adendo 1 |
| Q3 | Quem pode usar o dock: (a) permissão exclusiva, concedida a papéis na tela de papéis; (b) todo usuário com acesso ao painel; (c) papéis fixos? | RQ-05 | (a) permissão exclusiva concedível por papel — "perfil" no kit é papel, então (a) atende às duas leituras do texto. Sem a permissão, o dock não renderiza | respondida no Adendo 1 |
| Q4 | Quem escolhe os painéis do dock (admin, app, infra) e qual o padrão de instalação nova? | RQ-06 | Um interruptor por painel no settings, todos nascem desligados | respondida no Adendo 1 |
| Q5 | Favorito de outro painel aparece no dock de qualquer painel? No painel de organização, o favorito fica preso à organização em que foi criado? | RQ-07 | Sim, com duas travas: só aparece se o usuário ainda acessa aquele painel e aquela tela; favorito do painel de organização guarda a organização e some se o usuário perder o vínculo | respondida no Adendo 1 |
| Q6 | O layout vem do settings, do usuário, ou o settings define o padrão e o usuário sobrescreve? Estilo (Native/Outline/Contrast/Tinted) também entra? | RQ-09 | Settings define o padrão (layout e estilo); o usuário sobrescreve só o layout | respondida no Adendo 1 |
| Q7 | Há teto de favoritos por usuário? | RQ-03 | 10 por usuário — speed dial com mais itens passa da altura da tela | respondida no Adendo 1 |
| Q8 | Destino que o usuário perdeu permissão: some do dock ou aparece desabilitado? | RQ-04 | Some do dock e continua gravado; volta se a permissão voltar | respondida no Adendo 1 |
| Q11 | A opção "adicionar a página atual aos atalhos" aparece em qualquer página, ou só onde a página é um destino do catálogo (lista de um Resource ou Page avulsa)? Em tela de registro (criar/editar/ver), ela favorita a lista do Resource? | RQ-21 | Só onde a página atual É um destino do catálogo; em tela de registro ela não aparece. Favoritar a lista estando no formulário de um registro surpreende, e registro individual está fora do catálogo (Q2) | respondida no Adendo 2 |
| Q13 | No slideover, quais telas do painel de organização podem ser escolhidas: só as da organização em que o usuário está, ou as de todas as organizações dele? Fora do painel de organização, elas aparecem? | RQ-12, RQ-16 | Só as da organização corrente, e só quando o slideover é aberto dentro do painel de organização; os favoritos já gravados de outras organizações continuam no dock. Listar todas custa uma checagem de permissão por organização × tela a cada abertura | respondida no Adendo 2 |
| Q12 | Quais papéis recebem a permissão do dock numa instalação nova? | RQ-13 | Os papéis de administração dos três painéis (pela matriz do seeder); o papel de usuário comum (`panel_user`) não — o administrador concede pela tela de papéis | respondida no Adendo 2 |
| Q28 | Favorito de uma organização que foi excluída: some e é descartado ao salvar (P-02), ou fica gravado e ocupando vaga para sempre? | P-02, RQ-27 | Descartar — a organização não volta, e mantê-lo trava o limite de 10 sem que o usuário consiga removê-lo pela tela | aberta |
| Q14 | Com 10 favoritos gravados, o que acontece ao "adicionar a página atual" ou ao enviar 11 destinos pelo slideover: recusa com aviso, corte silencioso do 11º ou substituição de um antigo? | RQ-18 | Recusar sem mudar os 10 gravados, com aviso do limite — falha fechado; cortar descarta a escolha sem avisar, substituir apaga favorito não pedido | respondida no Adendo 3 |
| Q15 | O favorito oculto (sem permissão) ou inválido: (a) aparece no slideover? (b) sobrevive quando o usuário salva o slideover sem mexer nele? (c) conta no teto de 10? | RQ-19, RQ-18 | (a) não aparece; (b) o oculto é preservado (RQ-19) e a chave que nunca foi destino é descartada; (c) o oculto conta no teto — nunca mais de 10 gravados | respondida no Adendo 3 |
| Q16 | O mesmo destino pode ser favorito duas vezes (adicionando página já favorita ou repetindo no slideover)? | RQ-21, RQ-10 | Não; fica um só, e "adicionar a página atual" não aparece quando a página já é favorita | respondida no Adendo 3 |
| Q17 | Slideover recebe destinos válidos junto com chave fora do catálogo (só com cliente adulterado): grava os válidos ou recusa o envio inteiro? | RQ-11 | Recusar o envio inteiro, sem gravar nada — a interface nunca envia chave inválida | respondida no Adendo 3 |
| Q18 | A ordem dos atalhos no dock é a escolhida pelo usuário, reordenável no slideover? | RQ-20, RQ-02 | Sim, ordem do usuário, reordenável no slideover | respondida no Adendo 3 |
| Q19 | Como o slideover identifica destinos de mesmo nome em painéis ou organizações diferentes? | RQ-12 | O rótulo traz o painel (e a organização, no painel de organização) antes do grupo e da tela | respondida no Adendo 3 |
| Q21 | A chave gravada que nunca foi destino (descartada só ao salvar o slideover) ocupa vaga no teto quando o usuário usa "adicionar esta página" sem salvar o slideover? | RQ-27, RQ-26 | Ocupa até ser descartada; a gravação nunca passa de 10 entradas | respondida no Adendo 4 |
| Q22 | Em que posição entra o destino incluído por "adicionar esta página"? | RQ-30 | No fim da lista — não mexe na ordem já escolhida | respondida no Adendo 4 |
| Q23 | No slideover aberto numa organização, o favorito já gravado de outra organização aparece entre os escolhidos (com o rótulo da organização dele) para ser removido ou reordenado? | RQ-25, RQ-30 | Sim, entre os escolhidos, mas não no catálogo de novas opções — senão a lista inteira não pode ser reordenada de lugar nenhum | respondida no Adendo 4 |
| Q24 | No slideover aberto fora do painel de organização (admin ou infra), os favoritos do painel de organização aparecem entre os escolhidos? | RQ-34, RQ-25, RQ-27 | Generalizar: em qualquer slideover, todo favorito gravado que o usuário ainda pode abrir aparece entre os escolhidos, com rótulo de painel e organização, removível e reordenável; muda só o catálogo de novas opções | respondida no Adendo 5 |

## Premissas

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | Favorito do painel e da organização correntes é exibido sem trocar contexto nem recarregar papéis: custa no máximo a consulta que a autorização da própria tela já custa no resto do kit (uma por destino que passa pelo `Gate`) | step 9 — CR-02 (`/code-review`) | 2026-10-14 | RQ-22, RQ-15 | substituída por P-04 |
| P-04 | Favorito do painel e da organização correntes é exibido sem trocar contexto nem recarregar papéis; a única consulta por favorito admitida é a da autorização da própria tela (`Gate::before` → `User::isMasterGlobal()`), a mesma de cada item da barra lateral | step 9 — CR-02 (`/code-review`), revisão da P-01 após a correção: memorizar `isMasterGlobal()` muda a autorização do kit inteiro e fica fora desta feature | 2026-10-14 | RQ-22, RQ-15 | vigente |
| P-05 | O dock acompanha o tema escuro do painel: com o painel em modo escuro, a superfície do dock e dos itens usa as cores escuras, sem fundo claro sobre a página escura | step 11 — dimensão G (tema e cor) não verificada no quality gate, ciclos 1 e 2; pedido do solicitante em 2026-10-14 para capturas e prova em modo escuro | 2026-10-14 | RQ-02, RQ-17 | vigente |
| P-02 | Favorito de organização que não existe mais é descartado quando o usuário salva o slideover, como a chave que não é mais destino | step 9 — CR-03 (`/code-review`) | 2026-10-14 | RQ-27, RQ-19 | vigente |
| P-03 | Clicar em "Adicionar esta página" quando a página já não pode ser adicionada mostra um aviso, em vez de não fazer nada | step 9 — RD-03 (`fw-revisor-diff`) | 2026-10-14 | RQ-21, RQ-28 | vigente |

## Fora de Escopo (declarado)

- Ações que executam código no dock (formulário, modal, confirmação): o texto pede URLs
- Posição e arrastar do dock: o pacote já guarda a posição por usuário no navegador

## Adendo 1 — 2026-10-13

- **Fonte**: resposta do solicitante no chat à rodada 1 da entrevista (Q1–Q10)
- **Fidelidade**: alta (texto escrito)
- **Responde a**: Q1, Q2, Q3, Q4, Q5, Q6, Q7, Q8 (e Q9/Q10, perguntas de desenho registradas no `01`)

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> - no Q10, abra uma modal slideover para termos uma melhor experiencia do usuário
> - seria bom ter um botão na tela para que o usuário possa adicionar a url que ele esta no favorito (pode ser uma das opções ao clicar)
> - o restante das perguntas estão aprovadas
> - de uma atenção especial no settings para que funcione de forma eficiente

### Decomposição

"o restante das perguntas estão aprovadas" aprova a recomendação de cada pergunta; a cláusula abaixo cita a recomendação aprovada.

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-10 | Os atalhos são favoritos que cada usuário escolhe; não há lista de URLs definida no settings | "o restante das perguntas estão aprovadas" (Q1, recomendação a) | funcional | RQ-03 |
| RQ-11 | O favorito só pode ser um destino do catálogo — Resource ou Page da aplicação que o usuário pode abrir —, nunca URL digitada | "o restante das perguntas estão aprovadas" (Q2) | restrição | — |
| RQ-12 | O catálogo de destinos é gerado automaticamente das telas que o usuário pode abrir, sem lista mantida à mão | "o restante das perguntas estão aprovadas" (Q1) | funcional | — |
| RQ-13 | Só quem tem a permissão exclusiva do dock, concedida a papéis na tela de papéis, vê o dock; sem ela o dock não renderiza | "o restante das perguntas estão aprovadas" (Q3) | autorização | RQ-05 |
| RQ-14 | O settings tem um interruptor por painel (admin, app, infra) para o dock; numa instalação nova todos nascem desligados | "o restante das perguntas estão aprovadas" (Q4) | funcional | RQ-06 |
| RQ-15 | O favorito de um painel aparece no dock de qualquer painel em que o dock esteja ligado, e só enquanto o usuário puder acessar aquele painel e aquela tela | "o restante das perguntas estão aprovadas" (Q5) | autorização | RQ-07 |
| RQ-16 | O favorito criado no painel de organização guarda a organização, abre nela e some do dock se o usuário perder o acesso a ela | "o restante das perguntas estão aprovadas" (Q5) | autorização | — |
| RQ-17 | O settings define o layout e o estilo padrão do dock; o usuário pode sobrescrever só o layout | "o restante das perguntas estão aprovadas" (Q6) | funcional | RQ-09 |
| RQ-18 | Cada usuário tem no máximo 10 favoritos | "o restante das perguntas estão aprovadas" (Q7) | restrição | — |
| RQ-19 | Favorito cujo destino o usuário deixou de poder abrir some do dock, continua gravado e volta se a permissão voltar | "o restante das perguntas estão aprovadas" (Q8) | autorização | — |
| RQ-20 | Os favoritos são gerenciados num modal slideover aberto a partir do dock | "no Q10, abra uma modal slideover" | funcional | — |
| RQ-21 | Uma das opções do dock adiciona a página em que o usuário está aos favoritos | "seria bom ter um botão na tela para que o usuário possa adicionar a url que ele esta no favorito (pode ser uma das opções ao clicar)" | funcional | — |
| RQ-22 | A leitura do settings pelo dock é eficiente: não acrescenta consulta ao banco por request para decidir se o dock aparece nem como ele aparece | "de uma atenção especial no settings para que funcione de forma eficiente" | não-funcional | — |

## Adendo 2 — 2026-10-13

- **Fonte**: resposta do solicitante no chat às perguntas Q11, Q12 e Q13
- **Fidelidade**: alta (texto escrito)
- **Responde a**: Q11, Q12, Q13

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> Q11, Q12 e Q13 aprovadas, pode seguir

### Decomposição

"aprovadas" aprova a recomendação de cada pergunta; a cláusula abaixo cita a recomendação aprovada.

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-23 | A opção "adicionar esta página" só aparece quando a página atual é um destino do catálogo (lista de um Resource ou Page avulsa); em tela de criar, editar ou ver registro ela não aparece | "Q11, Q12 e Q13 aprovadas" (Q11) | funcional | — |
| RQ-24 | Numa instalação nova, a permissão do dock vai para os papéis de administração dos três painéis e não para o papel de usuário comum (`panel_user`) | "Q11, Q12 e Q13 aprovadas" (Q12) | autorização | — |
| RQ-25 | No slideover, as telas do painel de organização oferecidas são só as da organização corrente, e só quando o slideover é aberto dentro do painel de organização; favoritos já gravados de outras organizações continuam no dock | "Q11, Q12 e Q13 aprovadas" (Q13) | funcional | — |

## Adendo 3 — 2026-10-13

- **Fonte**: resposta do solicitante no chat às perguntas Q14 a Q19 (devolvidas pela derivação dos casos de teste)
- **Fidelidade**: alta (texto escrito)
- **Responde a**: Q14, Q15, Q16, Q17, Q18, Q19

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> Q14 a Q19 aprovadas, pode seguir

### Decomposição

"aprovadas" aprova a recomendação de cada pergunta; a cláusula abaixo cita a recomendação aprovada.

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-26 | Com 10 favoritos gravados, adicionar a página atual ou enviar mais de 10 pelo slideover é recusado com aviso do limite, sem alterar os favoritos gravados | "Q14 a Q19 aprovadas" (Q14) | restrição | RQ-18 (parcial) |
| RQ-27 | O favorito oculto não aparece no slideover, é preservado quando o usuário salva o slideover e conta no teto de 10; chave gravada que nunca foi destino do catálogo é descartada ao salvar | "Q14 a Q19 aprovadas" (Q15) | funcional | RQ-19 (parcial) |
| RQ-28 | O mesmo destino não é favorito duas vezes; "adicionar esta página" não aparece quando a página atual já é favorita | "Q14 a Q19 aprovadas" (Q16) | restrição | — |
| RQ-29 | Envio do slideover com qualquer chave fora do catálogo é recusado por inteiro, sem gravar nada | "Q14 a Q19 aprovadas" (Q17) | restrição | — |
| RQ-30 | A ordem dos atalhos no dock é a escolhida pelo usuário, que pode reordená-los no slideover | "Q14 a Q19 aprovadas" (Q18) | funcional | — |
| RQ-31 | No slideover, o rótulo de cada destino traz o painel — e, no painel de organização, a organização — antes do grupo e da tela | "Q14 a Q19 aprovadas" (Q19) | funcional | — |

## Adendo 4 — 2026-10-13

- **Fonte**: resposta do solicitante no chat às perguntas Q21, Q22 e Q23 (devolvidas pela re-derivação dos casos de teste)
- **Fidelidade**: alta (texto escrito)
- **Responde a**: Q21, Q22, Q23

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> Q21, Q22 e Q23 aprovadas, pode seguir

### Decomposição

"aprovadas" aprova a recomendação de cada pergunta; a cláusula abaixo cita a recomendação aprovada.

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-32 | Toda entrada gravada, inclusive a chave que nunca foi destino e ainda não foi descartada, ocupa vaga no teto; os favoritos gravados nunca passam de 10 entradas | "Q21, Q22 e Q23 aprovadas" (Q21) | restrição | — |
| RQ-33 | O destino incluído por "adicionar esta página" entra no fim da lista, sem mudar a ordem dos demais | "Q21, Q22 e Q23 aprovadas" (Q22) | funcional | — |
| RQ-34 | No slideover aberto numa organização, o favorito de outra organização aparece entre os escolhidos, com o rótulo da organização dele, podendo ser removido ou reordenado, mas não aparece entre as novas opções do catálogo | "Q21, Q22 e Q23 aprovadas" (Q23) | funcional | — |

## Adendo 5 — 2026-10-13

- **Fonte**: resposta do solicitante no chat à pergunta Q24 (devolvida pela rodada 2 da revisão adversarial)
- **Fidelidade**: alta (texto escrito)
- **Responde a**: Q24

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> Q24 aprovada, pode seguir

### Decomposição

"aprovada" aprova a recomendação da pergunta; a cláusula abaixo cita a recomendação aprovada.

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-35 | Em qualquer slideover, de qualquer painel, todo favorito gravado que o usuário ainda pode abrir aparece entre os escolhidos, com o rótulo de painel (e organização), e pode ser removido ou reordenado; só as novas opções do catálogo variam conforme onde o slideover é aberto | "Q24 aprovada" (Q24) | funcional | RQ-34 |

# Requisito — Mapa interativo do kit com a skill archify

> **Fonte da verdade.** Captura verbatim do pedido, antes de qualquer interpretação.

## Fonte

- **Origem**: mensagem do mantenedor no chat do Claude Code, mencionando `/feature-wiki`
- **Data**: 2026-11-20
- **Autor / solicitante**: mantenedor do kit
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> analise a skill archify: "https://archify.si/"
> - veja onde podemos colocar no readme e no site para demonstrar todas as features que temos implementado no kit, tanto de pacotes quanto de implementações feitas direto no codigo
> - explore o maximo todos os tipos de graficos e diagramas tecnicos e formas de visualização interativa para exibir todas as ligações que temos tanto entre modulos quando de fluxos no proprio starter-kit
> - faça separado por cada panel e também com e sem tenant ativo
> - use o codex --yolo como auxiliar no processo
> - crie uma /feature-wiki se necessário
> - implemente ate o fim. acho que não precisa de alteração no codigo, mas se precisar, pode alterar e documentar muito bem o que foi feito, além dos testes padrão
> - use subagents para rodar em paralelo se necessário
> - implemente ate o fim.

## Decomposição em Cláusulas

<!-- Derivada e revisável. Estado: fechada · aberta — Qn · substituída por RQ-nn (Adendo N) · decomposta em RQ-nn, RQ-mm -->

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | A skill archify é analisada — o que ela gera, que tipos de diagrama oferece, como se instala e quais os limites — e a análise fica registrada antes de ela ser usada | "analise a skill archify: "https://archify.si/"" | restrição | fechada |
| RQ-02 | O README ganha um lugar escolhido para demonstrar as features que o kit implementa | "veja onde podemos colocar no readme" | funcional | fechada |
| RQ-03 | O site ganha um lugar escolhido para demonstrar as features que o kit implementa | "e no site" | funcional | fechada |
| RQ-04 | A demonstração cobre **todas** as features que o kit implementa por meio de **pacotes** | "todas as features que temos implementado no kit, tanto de pacotes" | funcional | fechada |
| RQ-05 | A demonstração cobre **todas** as features que o kit implementa **direto no código** | "quanto de implementações feitas direto no codigo" | funcional | fechada |
| RQ-06 | A demonstração explora ao máximo os tipos de gráfico e de diagrama técnico — nenhum tipo disponível fica sem uso sem um motivo registrado | "explore o maximo todos os tipos de graficos e diagramas tecnicos" | funcional | fechada |
| RQ-07 | A demonstração usa formas de visualização **interativa** | "e formas de visualização interativa" | funcional | fechada |
| RQ-08 | A demonstração exibe **todas** as ligações entre os módulos do kit | "para exibir todas as ligações que temos tanto entre modulos" | funcional | fechada |
| RQ-09 | A demonstração exibe os **fluxos** do próprio starter-kit | "quando de fluxos no proprio starter-kit" | funcional | fechada |
| RQ-10 | A demonstração é separada por painel — cada painel do kit tem a sua | "faça separado por cada panel" | funcional | fechada |
| RQ-11 | A demonstração é separada também pelo modo multi-tenant — com o tenant ativo e sem ele | "e também com e sem tenant ativo" | funcional | fechada |
| RQ-12 | O Codex, rodado com `--yolo`, participa do processo como auxiliar | "use o codex --yolo como auxiliar no processo" | restrição | fechada |
| RQ-13 | O trabalho é conduzido por uma feature-wiki, se for necessária | "crie uma /feature-wiki se necessário" | restrição | fechada |
| RQ-14 | A feature é implementada até o fim, não só planejada ou estudada | "implemente ate o fim." | restrição | fechada |
| RQ-15 | O código de aplicação só muda se for necessário para a demonstração | "acho que não precisa de alteração no codigo, mas se precisar, pode alterar" | restrição | fechada |
| RQ-16 | Toda mudança de código de aplicação que acontecer fica muito bem documentada | "e documentar muito bem o que foi feito" | restrição | fechada |
| RQ-17 | A entrega vem com os testes padrão do projeto | "além dos testes padrão" | não-funcional | fechada |
| RQ-18 | Subagentes rodam em paralelo quando for necessário | "use subagents para rodar em paralelo se necessário" | restrição | fechada |

## Perguntas ao Solicitante

<!-- Raia requisito: só o solicitante responde. Bloquear somente o recorte dependente da resposta;
     preservar obrigações determinadas. A resposta entra como Adendo com fonte. -->

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | "todas as features" — qual é a lista? O kit não tem um catálogo único de features: há a lista de features do README, as páginas do site e os pacotes instalados | RQ-04, RQ-05 | a união das três fontes que o projeto já mantém — a lista de features do README, as páginas de recursos e autenticação do site e a referência de pacotes instalados —, com cada item marcado como **pacote** ou **código próprio**; nada fora delas | respondida no Adendo 1 |
| Q2 | "todas as ligações entre modulos" — o que é um módulo? | RQ-08 | toda peça com nome que um painel registra ou usa: painel, recurso, página, widget, plugin de pacote, provedor, middleware, serviço próprio e model; ligação é o uso de uma pela outra, com o lugar do código que a prova | respondida no Adendo 1 |
| Q3 | "todos os tipos de graficos e diagramas tecnicos" — só os tipos do archify, ou também outras bibliotecas de visualização (o que acrescenta dependência nova ao site)? | RQ-06, RQ-07 | só o archify (os dez tipos nativos e o comparativo de arquitetura), somado aos diagramas Mermaid que o site já tem — falha fechado: nenhuma dependência nova além da que o pedido nomeia | respondida no Adendo 1 |
| Q4 | "implemente ate o fim" inclui fazer o merge na `main` (e publicar uma versão)? | RQ-14 | não: o fim é o PR aberto com o CI verde; merge e release só com um OK explícito — falha fechado, porque o merge publica no site e no README | respondida no Adendo 1 |
| Q6 | Quais são "os fluxos" que o mapa exibe? Todos? O texto pede "todas as ligações que temos tanto entre modulos quando de fluxos", e a RQ-09 não fecha a lista *(step 7, derivação)* | RQ-09 | todos, numa lista fechada na página do mapa: um fluxo por caso de uso de ponta a ponta que o kit implementa, no mínimo os que o site já desenha em Mermaid como fluxo, sequência ou estados; o caso de uso que ficar sem diagrama leva o motivo escrito — falha fechado | aberta |
| Q7 | Quais "páginas do site" e quais "pacotes instalados" contam como fonte do catálogo? *(step 7, derivação)* | RQ-19, RQ-04, RQ-05 | todas as páginas, cada uma com uma F-nn ou com o motivo de não descrever feature; todos os pacotes que a referência de pacotes instalados lista (produção, desenvolvimento e front), cada um numa F-nn ou numa lista de pacotes sem feature própria, com o motivo — falha fechado | fechada pelo próprio `00`: a RQ-19 diz "páginas do site" e "pacotes instalados" sem recorte — todas as páginas, e todo pacote que os manifestos declaram (`composer.json`: `require` e `require-dev`; `package.json` da aplicação: `dependencies`, `devDependencies` e `optionalDependencies`); a referência de pacotes instalados, a fonte que a Q1 nomeou, lista produção, desenvolvimento e front (`docs/pt/referencia/pacotes-instalados.md`), fora os "avaliados e não adotados" *(alterado em 2026-11-20: a primeira redação limitava a conta ao que a referência lista; a Q16 mostrou que é mais estreito que o texto)* |
| Q16 | O pacote instalado que a referência de pacotes não lista entra na conta da RQ-19? Na base, quatro estão nos manifestos e fora da referência: `hoceineel/filament-quick-action-dock`, `brianium/paratest`, `icanhazstring/composer-unused` e `maglnet/composer-require-checker` *(step 7, derivação, 2ª rodada)* | RQ-19, RQ-04, P-03 | sim: a guarda reprova o pacote instalado que a referência não lista, e a referência passa a listar os quatro, cada um numa F-nn de origem pacote ou na lista dos sem feature própria, com o motivo — falha fechado | fechada pelo próprio `00`: a RQ-19 diz "pacotes instalados", e instalado é o que os manifestos declaram; a referência é o meio, e está defasada em quatro |
| Q9 | Como aparece a prova de uma ligação que só existe dentro de um pacote (`vendor/`), como o recurso que um plugin registra? *(step 7, derivação)* | RQ-24, P-04, P-05, P-07 | a prova cita o código do kit; nunca um link que dê 404 — falha fechado | fechada pelo próprio `00`: pela P-07, o link de prova aponta para o commit que publica, e o `vendor/` não está nele; pela RQ-24, o trecho que prova a ligação de uma peça trazida por plugin é, no código versionado do kit, o registro do plugin no painel |
| Q11 | Peça que o painel registra sempre, mas que só fica disponível com uma chave (as features desligadas por padrão) ou só num modo (a tela de organizações do `/admin`, registrada nos dois modos e aberta só com tenant): o mapa a marca com a condição? Em que diagrama de modo ela entra? *(step 7, derivação)* | RQ-04, RQ-05, RQ-11, P-03, P-09 | marcada com a condição (a chave e o valor de fábrica, ou o modo) em todo diagrama que a desenha, desenhada só nos diagramas do modo em que está disponível, e nunca como sempre disponível — falha fechado | aberta |
| Q12 | Na versão pt, a interface do visualizador fica em português? Os diagramas passam pela conferência de acessibilidade das páginas do site? *(step 7, derivação)* | P-02, RQ-03, RQ-07 | sim às duas — falha fechado | assumida como P-11 (sem resposta do mantenedor; a tolerância a dois defeitos do archify e a casca em inglês do comparativo vão sinalizadas no PR) |
| Q13 | Feature nova que é variante de outra pode receber sufixo de letra (F-03d), ou recebe o próximo inteiro? *(step 7, derivação)* | P-08, RQ-23 | o próximo inteiro; os sufixos ficam só nos que a base já tem — falha fechado | fechada pelo próprio `00`: a P-08 dá à feature nova "o próximo número livre", e depois da F-71 o próximo é a F-72; um sufixo não é o próximo número |
| Q23 | A raiz da árvore de registro de um painel leva o nome que o modelo dá à peça painel (`/app`), como toda peça desenhada, ou um formato próprio ("Painel /app" em pt, "Panel /app" em en)? *(step 9, achado A14)* | RQ-24, P-02 | o formato próprio, com o nome tirado do modelo — lê melhor que o nome sozinho | decidida pela sessão em 2026-11-21: o nome do modelo, como toda peça (R24 do `04`); as 8 raízes de hoje são vermelho de implementação |
| Q24 | `--tentativas 0`, no posicionador do mapa, é recusado, ou quer dizer "só a grade inicial, sem busca"? *(step 9, achado A6)* | P-14 | recusado — falha fechado: zero tentativas não é busca | decidida pela sessão em 2026-11-21: recusado; o valor válido de `--tentativas` é um inteiro positivo |

## Premissas

<!-- Revisável. O que a feature assume sem que o solicitante tenha escrito. Não é requisito. -->

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | Os diagramas Mermaid que o README e o site já publicam continuam no lugar; a demonstração nova se soma a eles, sem substituí-los | step 4 — decisão de desenho (nada no pedido pede remoção) | 2026-11-20 | RQ-02, RQ-03 | vigente |
| P-02 | "readme" e "site" valem nos dois idiomas que o kit mantém em paridade (português e inglês) | step 4 — convenção do repositório, cobrada pelos testes de documentação (mesma premissa da wiki `diagramas-da-arquitetura`) | 2026-11-20 | RQ-02, RQ-03 | vigente |
| P-03 | Toda ligação, fluxo ou feature que a demonstração afirma reflete o que o kit implementa hoje e tem uma guarda que falha quando o código deixar de bater com ela | step 4 — herdada da wiki `diagramas-da-arquitetura` (RQ-10 dela) e de "testes padrão" | 2026-11-20 | RQ-04, RQ-05, RQ-08, RQ-09, RQ-17 | vigente |
| P-04 | A prova de cada ligação — o arquivo e o trecho de código — fica à vista do leitor no mesmo artefato que desenha a ligação, e não só num documento à parte; ligação sem prova à vista não é publicada. *(alterado em 2026-11-20 — segunda revisão adversarial, ADV2-20)* Uma exceção, declarada na página do mapa ao lado das duas da P-11: o comparativo sem → com tenant, cuja casca do archify não leva cards nem passaporte (medido no esqueleto do passo 1: 62 ligações desenhadas e nenhum card). Ele só é publicado quando os dois diagramas que compara são diagramas do mapa, publicados ao lado com a prova de cada ligação à vista | step 4 — RQ-24 pede a prova, e o desenho de uma ligação não carrega texto próprio; a exceção do comparativo afrouxa a premissa porque o kit não consegue pôr cards na casca do archify sem pós-processar o HTML, e vai sinalizada ao mantenedor no PR | 2026-11-20 | RQ-08, RQ-24, RQ-11 | vigente |
| P-05 | "Todas as ligações" tem completude garantida na camada de registro de cada painel — o que o painel registra (recursos, páginas, widgets, plugins e middlewares) é conferido contra o painel vivo, nos dois modos de tenancy; as demais ligações de uso são levantadas por varredura e têm a prova conferida, sem promessa de que a varredura achou todas | step 4 — nenhuma guarda prova a ausência de uma ligação de uso que ninguém declarou; a camada de registro é a única que o próprio painel enumera | 2026-11-20 | RQ-08, RQ-20, RQ-24 | vigente |
| P-06 | A separação por painel e por modo vale para o que tem painel: cada um dos três painéis ganha a sua visualização sem e com tenant ativo; o que não pertence a um painel (histórico de versões, tempos de instalação e de CI, modelo de dados e de classes) é do kit inteiro, e toda combinação de tipo × painel × modo que fica sem visualização tem o motivo escrito ao lado | step 4 — o pedido separa por painel e por modo, mas parte dos tipos de diagrama não tem eixo de painel | 2026-11-20 | RQ-06, RQ-10, RQ-11, RQ-25 | vigente |
| P-07 | O que a demonstração afirma corresponde à revisão publicada: ela é gerada a partir do mesmo commit que publica o site, e os links de evidência apontam para esse commit | step 4 — um artefato versionado não consegue citar o próprio commit, e número de linha guardado envelhece a cada edição do arquivo citado | 2026-11-20 | RQ-08, RQ-09, P-03 | vigente |
| P-08 | A numeração do catálogo só cresce: uma feature nova recebe o próximo número livre; nenhum número existente é trocado, reaproveitado ou removido sem deixar registro | step 4 — os números já nomeiam testes, comentários, wikis e o CHANGELOG, e houve colisão quando um número foi reaproveitado | 2026-11-20 | RQ-21, RQ-22, RQ-23 | vigente |
| P-09 | "Com e sem tenant ativo" separa o que muda entre os modos: uma visualização que é igual nos dois modos é publicada uma vez e vale para os dois; ganha uma visualização de cada modo só o que muda de estrutura. A página mostra a grade painel × modo inteira, com a célula compartilhada marcada como tal | step 4 — spike de escala: no `/infra` só duas peças aparecem com tenant e nenhuma ligação muda; no `/admin` as diferenças ficam em organizações e pessoas; duplicar o igual dobraria a página sem mostrar diferença | 2026-11-20 | RQ-06, RQ-11, P-06 | vigente |
| P-10 | "Exibir todas as ligações" quer dizer: toda ligação do modelo aparece desenhada em pelo menos uma visualização — direto, ou por um agrupamento que nomeia os seus membros —, e nada do que só existe numa etiqueta escondida pelo zoom conta como desenhado | step 4 — spike de escala: nenhum painel cabe num desenho só (o `/admin` tem 243 peças e 496 ligações), então "todas" passa a valer para o conjunto das visualizações, não para cada uma | 2026-11-20 | RQ-08, RQ-24, P-05 | vigente |
| P-11 | Os diagramas do mapa são páginas do site para a P-02 e para a acessibilidade: a versão pt traz a interface do visualizador em português (botões, busca, legenda dos tipos de peça e o `lang` do documento) e a en, em inglês; e os diagramas passam pela conferência de acessibilidade do site (WCAG 2.1 AA, violações serious e critical) por amostra — um diagrama por tipo, idioma e tema, e o comparativo no tema escuro. Duas exceções, declaradas na página do mapa: a casca do comparativo, que o archify só tem em inglês; e dois defeitos do próprio archify, que a conferência tolera nomeados pela regra e pelo elemento, reprovando qualquer outra violação | step 7 — fecha a Q12, sem resposta do mantenedor, com o que o desenho já tinha decidido (`01` D2, D8 e D12; `02` ADR-06); as duas exceções afrouxam o "falha fechado" porque o kit não consegue corrigir o archify, e vão sinalizadas ao mantenedor no PR | 2026-11-20 | P-02, RQ-03, RQ-07 | vigente |
| P-12 | A varredura do código inteiro (RQ-20) tem um piso mecânico: toda peça que um painel registra — recurso, página, widget e plugin —, nos dois modos, e toda classe de `app/` fora de `app/Filament` entrega ao menos uma F-nn do catálogo, ou está numa lista de peças e classes sem feature própria, com o motivo de cada uma. Além desse piso, a completude da varredura não tem garantia, e a página do mapa diz isso | step 7 — revisão adversarial (ADV-33): o piso só no registro dos painéis, decidido como oráculo na Q8, deixava de fora comandos, serviços próprios e middlewares; medido no step 7, 103 das 135 classes de `app/` fora de `app/Filament` já eram peça na varredura do step 3. É uma obrigação permanente do CI sobre toda classe nova, sinalizada no PR | 2026-11-20 | RQ-20, RQ-04, RQ-05 | vigente |
| P-13 | O que o mapa de arquitetura diz de uma seta ou de uma caixa de agrupamento é o que o modelo dá ao diagrama, no modo dele: o traço da seta nunca anuncia um verbo que nem todas as ligações que ela junta têm; a caixa de agrupamento diz quantas peças nomeia no modo do diagrama, com a palavra no número certo, nos dois idiomas; e caixa de agrupamento sem membro no modo do diagrama não é desenhada | step 9 — achados A1 e A5 da revisão do diff: a seta que junta ligações de verbos diferentes leva o traço da primeira ligação do modelo (medido no commit `168d1827`: 51 setas em 13 mapas), a caixa de um membro diz "1 peças" / "1 pieces", e o filtro de modo pode esvaziar uma caixa, que sairia com "0 peças". A legenda e os itens de prova dizem o verbo certo, mas o traço diz outro, e o leitor lê o traço | 2026-11-21 | RQ-08, RQ-24, P-10, P-02 | vigente |
| P-14 | As ferramentas do mapa — o gerador, no build e na mão do autor, o posicionador e o conferidor — falham fechado e dizem por quê: conferem os argumentos, a pasta de saída e os arquivos de entrada antes de qualquer efeito (apagar, baixar o archify, renderizar ou gravar); não apagam nem sobrescrevem o que não geraram, e recusam a pasta de saída que já tem arquivo que elas não geraram; recusam o argumento sem valor válido, em vez de assumir um; não terminam com código 0 sem ter feito o que o comando pediu, qualquer que seja o caminho pelo qual são chamadas; e todo erro nomeia, com o prefixo `[mapa]`, o arquivo, a pasta ou o argumento que o causou | step 9 — achados A2, A4, A6 e A11 da revisão do diff: `--saida` sem slug apaga a pasta inteira que o autor passou; chamado por um caminho que passa por um link, o gerador termina com código 0 sem gerar nada (medido no Windows por junção, sem privilégio); `--tentativas` sem número vira zero tentativas; e o JSON quebrado de um diagrama ou do modelo reprova com a mensagem do `JSON.parse`, sem o arquivo. Uma ferramenta que apaga, cala ou culpa o arquivo errado custa retrabalho a quem escreve diagramas e deixa o build publicar sem o mapa | 2026-11-21 | P-07, P-03, RQ-17 | vigente |

## Adendo 1 — 2026-11-20

- **Fonte**: respostas do mantenedor às perguntas Q1–Q4, no diálogo de perguntas do Claude Code (Q2, Q3 e Q4 pela opção escolhida; Q1 com texto próprio)
- **Fidelidade**: alta (texto escrito e opções escolhidas)
- **Responde a**: Q1, Q2, Q3, Q4

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> **Q1** — " README + site + pacotes. 
> mas faça uma Varredura no codigo todo.
> aproveite para criar um catalogo unico (pois isso já foi programado para existir desde o inicio, caso esteja defasado, vamos unificar e isso se torna uma RULE do projeto)"
>
> **Q2** — "Peças com nome (Recommended)" — opção: "Painel, recurso, página, widget, plugin de pacote, provider, middleware, serviço próprio e model; ligação = uso de um pelo outro, com o trecho de código que prova."
>
> **Q3** — "Só archify + Mermaid (Recommended)" — opção: "Os dez tipos nativos do archify e o comparativo de arquitetura (sem/com tenant), somados aos Mermaid que o site já tem. Nenhuma dependência nova além do archify."
>
> **Q4** — "Até o PR com CI verde (Recommended)" — opção: "Eu abro o PR e deixo o CI verde; merge e release só com o seu OK, porque o merge publica no site e no README."

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-19 | A lista de features que a demonstração cobre parte da união da lista do README, das páginas do site e dos pacotes instalados | "README + site + pacotes." | funcional | RQ-04 (parcial), RQ-05 (parcial) |
| RQ-20 | Uma varredura do código inteiro completa essa lista com toda feature implementada que as três fontes não citam | "mas faça uma Varredura no codigo todo." | funcional | RQ-04 (parcial), RQ-05 (parcial) |
| RQ-21 | O kit passa a ter um catálogo único de features | "aproveite para criar um catalogo unico" | funcional | — |
| RQ-22 | O catálogo que o kit já previa é a base do catálogo único: se estiver defasado, é unificado com as outras fontes, e não nasce um segundo catálogo ao lado dele | "pois isso já foi programado para existir desde o inicio, caso esteja defasado, vamos unificar" | restrição | — |
| RQ-23 | Manter o catálogo único vira uma rule do projeto | "e isso se torna uma RULE do projeto" | restrição | — |
| RQ-24 | Módulo é toda peça com nome — painel, recurso, página, widget, plugin de pacote, provider, middleware, serviço próprio e model —, e ligação é o uso de uma pela outra, com o trecho de código que a prova | "Painel, recurso, página, widget, plugin de pacote, provider, middleware, serviço próprio e model; ligação = uso de um pelo outro, com o trecho de código que prova." | funcional | RQ-08 (parcial) |
| RQ-25 | Os tipos de visualização são os dez nativos do archify e o comparativo de arquitetura sem/com tenant, somados aos Mermaid existentes; nenhuma dependência nova de visualização além do archify | "Os dez tipos nativos do archify e o comparativo de arquitetura (sem/com tenant), somados aos Mermaid que o site já tem. Nenhuma dependência nova além do archify." | restrição | RQ-06 (parcial), RQ-07 (parcial) |
| RQ-26 | O fim da implementação é o PR aberto com o CI verde; merge e release só acontecem com um OK explícito do mantenedor | "Eu abro o PR e deixo o CI verde; merge e release só com o seu OK" | restrição | RQ-14 (parcial) |

## Fora de Escopo (declarado)

- Remover ou reescrever os diagramas Mermaid existentes (P-01)
- Mudar comportamento de qualquer tela do kit para caber num diagrama (RQ-15: código só muda se for necessário à demonstração)
- Merge na `main` e publicação de versão sem o OK explícito do mantenedor (RQ-26)
- Bibliotecas de visualização além do archify e do Mermaid (RQ-25)

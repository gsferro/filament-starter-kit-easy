# Requisito — Roadmap item 1: preferências de aparência por usuário

## Fonte

- **Origem**: `wikis/roadmap.md`, seção "1. Preferências de usuário — densidade, fonte, cor e modo do menu", mais o pedido do mantenedor no chat em 2026-11-16 e as quatro respostas dele às perguntas Q1–Q4 (ferramenta de perguntas do chat, em 2026-11-18, três delas com texto livre)
- **Data**: 2026-11-18
- **Autor / solicitante**: mantenedor do kit
- **Fidelidade**: alta (texto escrito no roadmap e respostas escritas pelo solicitante)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

**Pedido no chat (2026-11-16):**

> abra uma /feature-wiki  para resolver de 1 a 4 que são as pendencias para depois que tudo estiver resolvido, vamos para novas melhorias

(o item 2 da lista oferecida era "Preferências por usuário — Item 1 do roadmap: densidade, tamanho de fonte, cor e modo do menu escolhidos por cada pessoa, com a configuração da instalação como padrão.")

**`wikis/roadmap.md`, item 1:**

> Hoje a densidade do layout é uma configuração **da instalação**: quem administra escolhe em
> `/admin` → Configurações → Kit, e vale para todo mundo. O passo seguinte natural é a escolha ser
> **de cada pessoa**, guardada no perfil:
>
> | Preferência | Situação hoje | O que falta |
> |---|---|---|
> | **Densidade do layout** | existe, por instalação | mover a leitura para o usuário logado, com a da instalação como padrão |
> | **Tamanho da fonte** | não existe | mesma técnica da densidade — o Tailwind 4 do Filament deriva tudo de `--text-*` |
> | **Cor primária** | existe por instalação e por organização | falta o nível do usuário |
> | **Modo de ocultação do menu** (esconder tudo × manter o ícone, que é o padrão hoje) | não existe | é a única das quatro que **não** sai de variável CSS |
>
> **A hipótese que vale registrar**: isto é candidato natural a **pacote Filament externo**. Ele
> nasceria no kit, mas nada nele depende do kit — e um pacote de "preferências de aparência por
> usuário" é útil para qualquer painel Filament, podendo ser evoluído por terceiros. Se for por esse
> caminho, o kit passa a consumir o pacote em vez de manter o código.

**Q1 — "Preferências por usuário — cor primária: dentro de /app/{organização}, quando a pessoa escolheu uma cor e a organização tem a sua, qual vale?" — resposta escrita:**

> precisa de um settings global, permitindo a personalização por parte do usuário. Se a aplicação usar tenant, precisa do settings ligado e cada tenant pode ou não liberar a customização por parte do usuário, com esses 2 settings ativados, o usuário dentro do tenant para usar ou não

**Q2 — "Preferências por usuário — tamanho da fonte e modo do menu não existem hoje como configuração da instalação. Eles ganham também um padrão na tela Configurações do kit?" — resposta escrita:**

> Sim, as quatro, desde que os settings (principal e, se o tenant tiver sendo usado, tambem liberado naquela organização)

**Q3 — "Preferências por usuário — o administrador pode desligar a escolha pessoal (todo mundo passa a ver o padrão da instalação)?" — resposta escrita** (a opção oferecida dizia: "Uma chave em Configurações do kit, ligada por padrão. Desligada, a seção some do perfil e o que cada um gravou é ignorado (não apagado)."):

> "Sim, uma chave" porem, se tiver um tenant, cada tenant pode também liberar ou não seus usuários

**Q4 — "Preferências por usuário — o roadmap sugere que isto vire um pacote Filament externo. Como fazer agora?" — resposta escrita:**

> por enquanto, fica dentro do Kit, mas vamos criar uma parte dentro do projeto como features canditatas a pacote, onde as melhorias que implementando direto no kit, pode se tornar um pacote filament, tanto para outros projetos usarem somente essa melhoria, quanto para que outros colaboradores possam contribuir com a melhoria da feature em si.
> a principio, entra essa preferencia como candidata, mas revise o codigo e veja tudo que foi implementado diretamente no kit como feature de melhoria que poderia se tornar um pacote no futuro

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | Cada pessoa pode escolher a densidade do layout, guardada no perfil dela | "**Densidade do layout** … mover a leitura para o usuário logado" | funcional | fechada |
| RQ-02 | Cada pessoa pode escolher o tamanho da fonte | "**Tamanho da fonte** … não existe" | funcional | fechada |
| RQ-03 | Cada pessoa pode escolher a cor primária | "**Cor primária** … falta o nível do usuário" | funcional | fechada |
| RQ-04 | Cada pessoa pode escolher o modo de ocultação do menu: esconder tudo ou manter o ícone | "**Modo de ocultação do menu** (esconder tudo × manter o ícone, que é o padrão hoje)" | funcional | fechada |
| RQ-05 | Quem não escolheu vê a configuração da instalação | "com a da instalação como padrão" | funcional | fechada |
| RQ-06 | A instalação ganha uma configuração padrão também para o tamanho da fonte e para o modo do menu | Q2: "Sim, as quatro" | funcional | fechada |
| RQ-07 | Uma chave da instalação permite ou não a personalização por usuário; desligada, ninguém vê a escolha pessoal | Q1: "precisa de um settings global, permitindo a personalização por parte do usuário"; Q3: "Sim, uma chave" | funcional | fechada |
| RQ-08 | Com organizações (tenancy), cada organização pode liberar ou não a personalização para os usuários dela | Q1: "cada tenant pode ou não liberar a customização por parte do usuário"; Q3: "cada tenant pode também liberar ou não seus usuários" | funcional | fechada |
| RQ-09 | Dentro de uma organização, a escolha pessoal só vale com as duas chaves ligadas: a da instalação e a da organização | Q1: "com esses 2 settings ativados"; Q2: "desde que os settings (principal e, se o tenant tiver sendo usado, tambem liberado naquela organização)" | restrição | fechada |
| RQ-10 | Com a personalização liberada, usar é opcional: a pessoa pode deixar cada preferência no padrão | Q1: "o usuário dentro do tenant para usar ou não" | funcional | fechada |
| RQ-11 | A feature é implementada dentro do kit, sem pacote externo | Q4: "por enquanto, fica dentro do Kit" | restrição | fechada |
| RQ-12 | O projeto ganha um documento de features candidatas a pacote, com o propósito de uso isolado e de contribuição | Q4: "vamos criar uma parte dentro do projeto como features canditatas a pacote … tanto para outros projetos usarem somente essa melhoria, quanto para que outros colaboradores possam contribuir" | funcional | fechada |
| RQ-13 | As preferências de aparência entram nesse documento como candidata | Q4: "a principio, entra essa preferencia como candidata" | funcional | fechada |
| RQ-14 | O documento lista o que foi implementado direto no kit e poderia virar pacote, a partir de uma revisão do código | Q4: "revise o codigo e veja tudo que foi implementado diretamente no kit como feature de melhoria que poderia se tornar um pacote no futuro" | funcional | fechada |

## Perguntas ao Solicitante

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | Cor primária dentro de uma organização: vale a da pessoa ou a da organização? | RQ-03, RQ-09 | a da pessoa | respondida no Texto Original (2026-11-18) — a resposta troca a pergunta pelas duas chaves; com elas ligadas, a pessoa usa a escolha dela (P-02) |
| Q2 | Fonte e menu ganham padrão da instalação? | RQ-06 | sim, as quatro | respondida no Texto Original (2026-11-18) |
| Q3 | O administrador pode desligar a escolha pessoal? | RQ-07, RQ-08 | sim, uma chave | respondida no Texto Original (2026-11-18) |
| Q4 | Pacote externo agora ou dentro do kit? | RQ-11..RQ-14 | dentro do kit | respondida no Texto Original (2026-11-18) |
| Q5 | A chave da instalação é desligada depois que a pessoa abriu o Meu perfil, e ela ainda envia a seção Aparência: o sistema recusa (nada gravado, com aviso) ou grava e só ignora? | P-06, RQ-07 | recusar sem gravar, com aviso (falha fechada) | aberta — premissa P-15 vigente até a resposta (step 7, Q?1 da `feature-test-design`) |
| Q7 | Com organizações ligadas, nos painéis sem organização (`/admin` e `/infra`), a escolha pessoal segue só a chave da instalação — mesmo para quem pertence só a organizações que não liberaram — ou também deve exigir alguma organização liberada? | RQ-09, P-13 | só a chave da instalação: fora de uma organização não há organização a consultar, e exigir "alguma" liberada tornaria a regra dependente da lista de organizações da pessoa | aberta — premissa P-13 vigente até a resposta (step 11, QA-05 do quality gate) |
| Q6 | Com organizações ligadas, o aviso "numa organização que não liberou, vale o padrão dela" aparece só no Meu perfil do `/app` ou também no `/admin` e no `/infra`? | P-06 | nos três painéis, porque a escolha é uma só (P-01) | aberta — premissa P-16 vigente até a resposta (step 7, Q?3) |

## Premissas

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | As preferências são um conjunto só por pessoa: a mesma escolha vale nos três painéis e em toda organização que libera | step 3 — "guardada no perfil"; o perfil é um só por pessoa | 2026-11-18 | RQ-01..RQ-04 | vigente |
| P-02 | Com a personalização liberada no contexto, a escolha da pessoa vence a da organização e a da instalação; sem escolha da pessoa, vale a da organização (só a cor existe por organização) e, depois, a da instalação | step 4 — resposta a Q1 ("o usuário dentro do tenant para usar") | 2026-11-18 | RQ-03, RQ-05, RQ-09 | vigente |
| P-03 | A chave da instalação nasce **ligada** | step 4 — a opção escolhida em Q3 dizia "ligada por padrão" | 2026-11-18 | RQ-07 | vigente |
| P-04 | A chave de cada organização nasce **desligada**: ligar a da instalação não libera organização nenhuma sozinha | step 4 — premissa de comportamento pela opção que falha fechado; mesmo arranjo do cadastro público por organização | 2026-11-18 | RQ-08, RQ-09 | vigente |
| P-05 | Desligar uma chave não apaga o que a pessoa gravou: a escolha é ignorada enquanto a chave estiver desligada e volta a valer quando religada | step 4 — a opção escolhida em Q3 dizia "o que cada um gravou é ignorado (não apagado)" | 2026-11-18 | RQ-07, RQ-08 | vigente |
| P-06 | A escolha fica numa seção "Aparência" da tela Meu perfil, nos três painéis; a seção só aparece com a chave da instalação ligada. Como a escolha é uma só e vale em várias organizações, a seção avisa que, numa organização que não liberou, vale o padrão dela *(alterado em 2026-11-18: o motivo anterior — o Meu perfil fora de organização — era falso com tenancy, ver P-13)* | step 3 — "guardada no perfil"; a tela de perfil não tem organização no endereço | 2026-11-18 | RQ-01..RQ-04, RQ-08 | vigente |
| P-07 | A pessoa escolhe a cor só na lista fechada da paleta do kit (a mesma da instalação e da organização), sem cor livre em hexadecimal | step 4 — premissa de comportamento: a lista fechada não deixa gravar cor ilegível | 2026-11-18 | RQ-03 | vigente |
| P-08 | Os tamanhos de fonte são quatro: Pequena (87,5%), Normal (o padrão do Filament, nada muda), Grande (112,5%) e Muito grande (125%) | step 4 — escolha de desenho | 2026-11-18 | RQ-02, RQ-06 | vigente |
| P-09 | Os modos do menu são dois: "Manter os ícones" (o comportamento de hoje, padrão de fábrica) e "Esconder tudo" | step 4 — os dois do texto do roadmap | 2026-11-18 | RQ-04, RQ-06 | vigente |
| P-10 | Telas sem pessoa logada (login, cadastro, recuperação de senha) e telas de uma organização que não liberou mostram sempre o padrão (da organização, para a cor; da instalação, para o resto) | step 4 — a escolha é "da pessoa logada" | 2026-11-18 | RQ-05, RQ-09 | vigente |
| P-11 | A chave de cada organização é um interruptor no cadastro da organização em `/admin`, que só aparece com a chave da instalação ligada | step 3 — precedente do "aceita cadastro público"; interruptor sem efeito possível é pior que ausente | 2026-11-18 | RQ-08 | vigente |
| P-12 | Valor gravado fora do vocabulário (na pessoa ou na instalação) cai no padrão, sem erro na tela | step 3 — a mesma regra da densidade (vocabulário fechado, coerção) | 2026-11-18 | RQ-01..RQ-06 | vigente |
| P-13 | Tela sem organização corrente (o `/app` sem organizações ligadas, o `/admin` e o `/infra`) segue só a chave da instalação; com organizações ligadas, toda tela do `/app` — inclusive o Meu perfil — está dentro de uma organização e segue as duas chaves *(alterado em 2026-11-18: a versão anterior dizia que o Meu perfil do `/app` fica fora de organização; com tenancy a rota é `/app/{organização}/meu-perfil` — CT-12, implementação, `route:list` com `KIT_TENANCY=true`)* | step 3 — sem organização corrente não há chave de organização a consultar | 2026-11-18 | RQ-09 | vigente |
| P-15 | Com a chave da instalação desligada depois de a tela abrir, enviar a seção Aparência é recusado: nada é gravado e a pessoa vê um aviso | step 7 — Q?1 da `feature-test-design` (Q5), premissa de comportamento pela opção que falha fechado | 2026-11-18 | RQ-07, P-06 | vigente |
| P-16 | Com organizações ligadas, o aviso de que numa organização que não liberou vale o padrão dela aparece no Meu perfil dos três painéis | step 7 — Q?3 da `feature-test-design` (Q6); a escolha é uma só (P-01) | 2026-11-18 | P-06 | vigente |
| P-17 | A pré-visualização das telas de acesso (na tela de configurações) mostra o padrão — o que um visitante vê —, mesmo aberta por alguém logado que tem escolha própria | step 9 — CR-01 do `/code-review`; extensão de P-10 (telas de acesso mostram o padrão) | 2026-11-18 | P-10, RQ-05 | vigente |
| P-14 | O documento de candidatas a pacote mora em `wikis/`, ao lado do roadmap e dos pacotes candidatos, e viaja com o kit como eles | step 4 — "criar uma parte dentro do projeto" | 2026-11-18 | RQ-12 | vigente |

## Fora de Escopo (declarado)

- Extrair qualquer código para pacote agora (RQ-11)
- Padrão por organização para densidade, fonte e menu: a organização só ganha a chave de liberar; a cor dela continua como é
- Cor livre em hexadecimal para a pessoa (P-07)
- Preferência por painel ou por organização (P-01)

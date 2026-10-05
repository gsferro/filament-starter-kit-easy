# Requisito — feat/cabecalho-do-painel: cabeçalho dos painéis personalizável pelas Configurações da aplicação

## Fonte

- **Origem**: pedido do solicitante colado no chat (lista de itens), na mesma mensagem que pediu a revisão da branch `feat/logo-dark-mode` e o merge dos PRs abertos
- **Data**: 2026-10-05
- **Autor / solicitante**: usuário da sessão (mantenedor do kit)
- **Fidelidade**: alta (texto escrito)
- **Referência visual citada pelo solicitante**: o header do projeto derivado "projeto-3" (`D:\PROJECTS\FIOTEC\PROJETO 3\projeto-3`), lido no step 3 como **fato**, não como requisito

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> - revise a branch atual, para confirmar que tudo esteja ok.
> - revise os PRs abertos e faça o merge na main mas não precise de tag, so lance uma nova quando essa melhoria da branch atual entrar
> - no projeto 3: "D:\PROJECTS\FIOTEC\PROJETO 3\projeto-3" tem um header legal que exibe: "nome do projeto | nome do painel (nome da entidade, que pode entrar quando tiver o tenant ativo no painel) | Logo da marca". também do lado direito, tem a exibição do nome e da permissão direto no header que pode entrar no settings da aplicação para ser exibido e ter a opção de escolher o que exibir do usuário abaixo do nome: "email ou perfil".
> - na verdade todas as opções de exibição devem entrar no settings da aplicação para dar ao projeto que usa o kit a opção de customização.
> - mantenha tudo como esta hoje como default e adicione essas opções novas de customização
> - crie uma /feature-wiki para tratar dessa melhoria. se julgar necessário crie uma nova branch para isso, partindo da branch main ou crie direto aqui
> - faça os testes e garanta que tudo esteja funcionando corretamente

## Decomposição em Cláusulas

<!-- Derivada e revisável. Estado: fechada · aberta — Qn · substituída por RQ-nn (Adendo N) · decomposta em RQ-nn, RQ-mm -->

Os dois primeiros itens do texto (revisão da branch `feat/logo-dark-mode`, merge dos PRs abertos e a
release seguinte) são tarefas de manutenção fora desta feature — ver `## Fora de Escopo (declarado)`.
A decomposição cobre do terceiro item em diante.

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | O cabeçalho (topbar) dos painéis pode exibir, no lugar da marca, a composição "nome do projeto \| nome do painel \| logo da marca", nessa ordem. | "um header legal que exibe: \"nome do projeto \| nome do painel (…) \| Logo da marca\"" | funcional | fechada |
| RQ-02 | No segmento do nome do painel, o nome da entidade (organização) entra quando há tenant ativo no painel. | "nome do painel (nome da entidade, que pode entrar quando tiver o tenant ativo no painel)" | funcional | fechada |
| RQ-03 | Do lado direito do cabeçalho, o nome do usuário e a permissão dele podem ser exibidos direto no header. | "do lado direito, tem a exibição do nome e da permissão direto no header que pode entrar no settings da aplicação para ser exibido" | funcional | fechada |
| RQ-04 | Há a opção de escolher o que aparece abaixo do nome do usuário: e-mail ou perfil. | "ter a opção de escolher o que exibir do usuário abaixo do nome: \"email ou perfil\"" | funcional | fechada |
| RQ-05 | Todas as opções de exibição do cabeçalho vivem nas Configurações da aplicação (settings), para o projeto que usa o kit customizar sem editar código. | "todas as opções de exibição devem entrar no settings da aplicação para dar ao projeto que usa o kit a opção de customização" | funcional | fechada |
| RQ-06 | O comportamento atual permanece como default: as opções novas nascem de modo que nada mude para quem não as ligar. | "mantenha tudo como esta hoje como default e adicione essas opções novas de customização" | restrição | fechada |
| RQ-07 | A melhoria é tratada por uma wiki de feature; branch nova a partir da `main` se o agente julgar necessário. | "crie uma /feature-wiki para tratar dessa melhoria. se julgar necessário crie uma nova branch para isso, partindo da branch main ou crie direto aqui" | não-funcional | fechada |
| RQ-08 | A feature é entregue com testes que garantem que tudo funciona. | "faça os testes e garanta que tudo esteja funcionando corretamente" | não-funcional | fechada |

## Perguntas ao Solicitante

<!-- Raia requisito: só o solicitante responde. Enquanto aberta, a RQ afetada fica `aberta — Qn`
     e nenhum passo do 01 a implementa. A resposta entra como Adendo com fonte.
     Qn é a sequência única da feature: os números que faltam aqui são perguntas de desenho (01/02). -->

O solicitante pediu a entrega completa numa sessão autônoma e não está disponível para a rodada.
As perguntas abaixo são as de **comportamento que o texto não diz**; cada uma foi respondida pela
própria recomendação e registrada como premissa (`P-nn`) para a entrega não parar — e para a
resposta contrária, quando vier, entrar como Adendo e virar correção rastreável, não retrabalho às cegas.
As `RQ` ficam `fechada` porque o que o texto diz está implementado como diz; o que ele não diz está em `## Premissas`.

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | No painel `/app` o rótulo do painel **é** o nome da aplicação (`Paineis::rotulos()`), então "nome do projeto \| nome do painel" imprimiria o mesmo texto duas vezes. Com tenant ativo o segmento mostra a organização (RQ-02); e **sem** tenant, quando o rótulo do painel é igual ao nome do projeto, o segmento é omitido ou repetido? | RQ-01, RQ-02 | **Omitido** — repetir não informa nada e come espaço do topbar; com tenant ativo, a organização ocupa o segmento. | aberta — aplicada pela recomendação (P-01) |
| Q2 | Os três segmentos (nome do projeto, nome do painel, logo da marca) são **três interruptores independentes** nas configurações, ou um interruptor só liga a composição inteira? | RQ-01, RQ-05, RQ-06 | **Três interruptores**, todos desligados por padrão — é o que dá ao projeto a "opção de customização" pedida; com os três desligados o topbar fica exatamente como hoje. | aberta — aplicada pela recomendação (P-02) |
| Q3 | "Permissão" do usuário no cabeçalho é o **papel** (perfil) dele no painel corrente, com o rótulo legível que o menu do usuário já mostra (ex.: "Administrador Geral")? E quem não tem papel no painel (entra por outro caminho) fica só com o nome, sem a linha de baixo? | RQ-03, RQ-04 | **Sim** aos dois — mesma fonte do badge do menu do usuário, e sem papel a linha não aparece (falha fechado: não inventa texto). | aberta — aplicada pela recomendação (P-03) |
| Q4 | Em tela estreita (celular) o bloco do usuário disputa espaço com busca, sininho e avatar. Ele some abaixo de ~768 px, como na referência do projeto-3, ou aparece sempre? | RQ-03 | **Some abaixo de 768 px** — o avatar continua abrindo o menu com nome, e-mail e papel; nada se perde. | aberta — aplicada pela recomendação (P-04) |
| Q5 | Ao ligar o bloco do usuário, qual é o detalhe inicial abaixo do nome: e-mail ou perfil? | RQ-04 | **Perfil** — é o que a referência mostra e é o dado que o e-mail do menu do usuário não repete. | aberta — aplicada pela recomendação (P-05) |
| Q6 | O Filament desenha a marca em **dois** lugares: no topo da barra lateral (quando ela está aberta) e no topbar (quando ela está recolhida ou no celular). A composição vale nos dois, ou só no topbar? | RQ-01 | **Nos dois** — é a marca do painel, e manter a composição num lugar e a logo solta no outro faria a identidade trocar ao recolher a barra; dentro da barra lateral ela quebra linha para caber. | aberta — aplicada pela recomendação (P-06) |
| Q14 | (da derivação do `04`) Com o segmento "logo da marca" ligado e **nenhuma logo enviada** (ou com o arquivo apagado do disco), o que o segmento mostra? E se ele for o único ligado, a marca fica vazia? | P-07 | **Omitido**; se a composição ficar sem nenhum segmento, a marca volta à de hoje (o nome em texto) — falha fechado: nunca imagem quebrada, nunca marca vazia. | aberta — aplicada pela recomendação (P-11) |
| Q16 | (da derivação do `04`) No `/app` de uma organização com logo própria, o segmento "logo da marca" mostra a logo da organização ou a da instalação? | P-07 | **A da instalação** — é a que a marca do `/app` mostra hoje (a logo da organização só aparece na tela de bloqueio e no cadastro dela); trocar a marca pela logo da organização seria mudança de comportamento que o texto não pede, e identidade por organização está fora de escopo. A pergunta nasceu do fato errado de que a marca atual do `/app` seria a da organização. | aberta — aplicada pela recomendação (P-12) |
| Q17 | (da derivação do `04`) O seletor "e-mail ou perfil" fica escondido enquanto o bloco do usuário está desligado, ou aparece sempre na seção? | P-10 | **Escondido** — opção sem efeito exibida é promessa quebrada (mesmo critério da `logo_dark` com a marca unificada). | aberta — aplicada pela recomendação (P-13) |

A Q15 da mesma derivação é de **desenho** (valor fora da lista gravado direto: a tela carrega coagido e grava) e está em `## Decisões de Desenho` do `01` (D8).

## Premissas

<!-- Revisável. O que a feature assume sem que o solicitante tenha escrito. Não é requisito.
     Premissa que contradiz ou estende o pedido gera também uma pergunta em ## Perguntas ao Solicitante. -->

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | Com tenant ativo, o segmento "nome do painel" mostra o nome da organização. Sem tenant ativo, ele mostra o rótulo do painel — e é omitido quando o segmento "nome do projeto" também está ligado e o rótulo é idêntico ao nome do projeto (caso do `/app`), para não imprimir o mesmo texto duas vezes. | Q1 — recomendação aplicada | 2026-10-05 | RQ-01, RQ-02 | vigente |
| P-02 | Cada segmento da composição é um interruptor próprio, desligado por padrão; com os três desligados, a marca do painel é a de hoje (logo enviada, ou o nome em texto). | Q2 — recomendação aplicada | 2026-10-05 | RQ-01, RQ-05, RQ-06 | vigente |
| P-03 | A "permissão" exibida é o papel do usuário no painel corrente, no mesmo rótulo legível do menu do usuário; `master_global` aparece em qualquer painel; sem papel no painel, a linha de baixo não é renderizada. | Q3 — recomendação aplicada | 2026-10-05 | RQ-03, RQ-04 | vigente |
| P-04 | O bloco do usuário no cabeçalho fica oculto abaixo de 768 px de largura. | Q4 — recomendação aplicada | 2026-10-05 | RQ-03 | vigente |
| P-05 | O detalhe abaixo do nome nasce em "perfil"; "e-mail" é a outra opção. Não há terceira opção ("nenhum"): o texto só cita duas. | Q5 — recomendação aplicada | 2026-10-05 | RQ-04 | vigente |
| P-06 | A composição substitui a marca do painel onde quer que o Filament a desenhe (barra lateral aberta e topbar), e não só no topbar. Fica sempre numa linha: na barra lateral (que o Filament só usa para a marca em tela estreita, abaixo de 1024 px, com a barra aberta) os textos encolhem e cortam com reticências (quebrar linha vazaria por baixo do cabeçalho de altura fixa — revisão do diff e QA ciclo 1, 2026-10-05). | Q6 — recomendação aplicada | 2026-10-05 | RQ-01 | vigente |
| P-07 | "Logo da marca" da composição é a logo já configurada na aba Identidade (inclusive a variante escura, quando a marca está separada); não há upload novo. | step 3 — identidade existente do kit | 2026-10-05 | RQ-01 | vigente |
| P-08 | "Nome do projeto" é o nome da aplicação configurado na aba Identidade (o mesmo do título das abas e do rodapé). | step 3 — identidade existente do kit | 2026-10-05 | RQ-01 | vigente |
| P-09 | O bloco do usuário aparece à esquerda do avatar do menu do usuário e **complementa** o menu nativo (não o substitui): o dropdown continua com nome, e-mail, papel e itens. | step 3 — referência do projeto-3 e hook nativo do Filament | 2026-10-05 | RQ-03 | vigente |
| P-10 | As opções moram na aba **Identidade** das Configurações da aplicação, numa seção própria "Cabeçalho dos painéis", e cada uma tem par no `.env` com o mesmo default (desligado / perfil), como as demais opções booleanas do kit. | step 3 — convenção da tela de configurações | 2026-10-05 | RQ-05, RQ-06 | vigente |
| P-11 | Segmento "logo da marca" ligado sem logo resolvida é omitido; composição sem nenhum segmento visível cai para a marca de hoje — nunca imagem quebrada, nunca marca vazia. | Q14 — recomendação aplicada (step 7) | 2026-10-05 | RQ-01, RQ-06 | vigente |
| P-12 | A logo da composição é sempre a da instalação (aba Identidade), inclusive no `/app` de organização com logo própria — a mesma que a marca do painel mostra hoje. A logo da organização continua restrita à tela de bloqueio e ao cadastro dela. | Q16 — recomendação aplicada (step 7) | 2026-10-05 | RQ-01, RQ-02 | vigente |
| P-13 | O seletor "e-mail ou perfil" só aparece com o bloco do usuário ligado. | Q17 — recomendação aplicada (step 7) | 2026-10-05 | RQ-04, RQ-05 | vigente |

## Fora de Escopo (declarado)

- Os itens 1 e 2 do texto original — revisão da branch `feat/logo-dark-mode`, merge dos PRs abertos e a tag seguinte — são manutenção do repositório tratada na mesma sessão, fora desta wiki.
- Cor, fonte, tamanho ou ordem dos segmentos da composição: a ordem é a do texto ("projeto | painel | logo") e o estilo segue o tema do painel.
- Cabeçalho nas telas públicas de autenticação (login, recuperação de senha, bloqueio): a composição é dos painéis autenticados.
- Opções de cabeçalho **por organização** (tenant): as opções são da instalação, como todas as outras da tela de Configurações da aplicação.
- Um rótulo de painel editável pela tela: o nome do painel continua vindo do código (`Administração`, `Infraestrutura`, nome da aplicação para o `/app`).

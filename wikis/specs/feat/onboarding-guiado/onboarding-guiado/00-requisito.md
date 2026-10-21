# Requisito — Onboarding guiado: jornadas semeadas, interruptor no settings e rule de manutenção

## Fonte

- **Origem**: pedido do solicitante no chat, via `/feature-wiki` (argumentos do comando)
- **Data**: 2026-10-21
- **Autor / solicitante**: gsferro (desenvolvedor e solicitante)
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> precisamos criar um seeder e rules para popular o pacote "Onboarding"
> - precisamos criar Onboarding para todas as features que já temos implementado
> - precisamos criar uma regra que caso um agente altere alguma das features base, ele dispare um alerta para revisar/atualizar/criar um onboarding 
> - é necessário tanto o Onboarding quanto Condições
> - segue o link do pacote que já esta instalado para voce revisar se necessário: "https://filamentphp.com/plugins/wallacemartins-onboarding"
> - revise cada feature/pacote implementado e veja quais deles se qualificam para uso tutorial guiado
> - crie no settings uma config se vai querer o uso do tutorial na aplicação, por padrão vem false, e se for false, não exibe o menu, assim como foi feito no uso dos Agentes de IA na ultima release
> - crie um seeder Pai e cada feature atual (e cada nova que vira com a rule) se torna um seeder filho, que sera executado dentro do pai para rodar de uma vez (caso assim o usuário queira na hora da instalação). Deve estar documentado como iniciar os onboarding via seeder e quais features de quais telas/paineis será populado
> - se atente ao tenant, para verificar se o projeto que usa o kit ativou ou não o multi-tenant para ter o seu seeder correspondente
> continue

## Decomposição em Cláusulas

<!-- Derivada e revisável. Estado: fechada · aberta — Qn · substituída por RQ-nn (Adendo N) · decomposta em RQ-nn, RQ-mm -->

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | Cada feature já implementada no kit é revisada quanto a se qualificar para tutorial guiado, e o resultado (qualifica ou não, com o motivo) fica registrado | "revise cada feature/pacote implementado e veja quais deles se qualificam para uso tutorial guiado" | funcional | fechada |
| RQ-02 | Toda feature já implementada que se qualifica ganha uma jornada de onboarding populada por seeder | "precisamos criar Onboarding para todas as features que já temos implementado" · "precisamos criar um seeder [...] para popular o pacote "Onboarding"" | funcional | fechada |
| RQ-03 | Os seeders populam também as condições do pacote: as condições de que os passos das jornadas dependem existem depois de semear | "é necessário tanto o Onboarding quanto Condições" | funcional | fechada |
| RQ-04 | Existe um seeder pai que executa todos os seeders filhos de uma vez | "crie um seeder Pai [...] que sera executado dentro do pai para rodar de uma vez" | funcional | fechada |
| RQ-05 | Cada feature atual é um seeder filho próprio, chamado pelo seeder pai | "cada feature atual [...] se torna um seeder filho" | restrição | fechada |
| RQ-06 | A semeadura das jornadas é uma escolha de quem instala: na hora da instalação a pessoa decide se roda o seeder pai | "(caso assim o usuário queira na hora da instalação)" | funcional | fechada |
| RQ-07 | Está documentado como iniciar as jornadas de onboarding via seeder | "Deve estar documentado como iniciar os onboarding via seeder" | funcional | fechada |
| RQ-08 | Está documentado quais features, de quais telas e de quais painéis, cada seeder popula | "e quais features de quais telas/paineis será populado" | funcional | fechada |
| RQ-09 | Com multi-tenant ligado no projeto, as features que só existem com tenant têm seeder correspondente e são semeadas; com multi-tenant desligado, não são | "se atente ao tenant, para verificar se o projeto que usa o kit ativou ou não o multi-tenant para ter o seu seeder correspondente" | funcional | fechada |
| RQ-10 | A tela de configurações da aplicação tem uma opção que liga e desliga o uso do tutorial na aplicação | "crie no settings uma config se vai querer o uso do tutorial na aplicação" | funcional | fechada |
| RQ-11 | A opção do tutorial nasce desligada | "por padrão vem false" | restrição | fechada |
| RQ-12 | Com o tutorial desligado, o menu do onboarding não é exibido, no mesmo modelo do uso dos Agentes de IA da última release | "e se for false, não exibe o menu, assim como foi feito no uso dos Agentes de IA na ultima release" | funcional | fechada |
| RQ-13 | Existe uma regra de projeto: quando um agente altera uma das features base, ele recebe um alerta para revisar, atualizar ou criar o onboarding correspondente | "precisamos criar uma regra que caso um agente altere alguma das features base, ele dispare um alerta para revisar/atualizar/criar um onboarding" | funcional | fechada |
| RQ-14 | Feature nova que nascer depois desta entra no mesmo esquema: ganha o seu seeder filho, chamado pelo pai, por força da regra | "(e cada nova que vira com a rule)" | restrição | fechada |

O "continue" ao fim do texto e o link do pacote são instruções à sessão (seguir com o fluxo; o
pacote pode ser consultado), não cláusulas do sistema.

## Perguntas ao Solicitante

<!-- Raia requisito: só o solicitante responde. A resposta entra como Adendo. -->

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | Com o tutorial desligado, o que acontece com a autoria (menus "Onboarding" e "Condições" do `/admin`) e com o consumo (checklist flutuante, tours, boas-vindas) — somem do menu e a URL responde 404, ou só somem do menu? | RQ-12 | Tudo some e a URL responde 404, mesmo com permissão — o regime dos agentes de IA, falha fechado | respondida no Adendo 1 |
| Q2 | As jornadas são uma por painel (cada feature acrescenta o seu passo) ou uma por feature? | RQ-02, RQ-05 | Uma por painel: o checklist fica com no máximo três abas | respondida no Adendo 1 |
| Q3 | "Na hora da instalação": como a pessoa escolhe semear? | RQ-06 | Pergunta no `kit:install` (padrão não) e opção para o modo não interativo; semear não liga o interruptor | respondida no Adendo 1 |
| Q4 | Além do interruptor, o settings tem uma lista de painéis, como os agentes de IA? | RQ-10, RQ-12 | Só o interruptor: cada jornada já carrega o painel dela | respondida no Adendo 1 |
| Q5 | A lista de features qualificadas proposta está certa? | RQ-01, RQ-02 | Aprovar a lista apresentada | respondida no Adendo 2 |
| Q6 | Ao rodar o seeder pai de novo, a jornada ou o passo que o admin já editou no painel é preservado ou sobrescrito? | RQ-04, RQ-06 | Preservado: o seeder cria só o que falta | respondida no Adendo 2 |
| Q7 | O "alerta" quando um agente altera uma feature base é só a regra escrita, ou regra mais um teste que falha? | RQ-13, RQ-14 | Regra mais teste de inventário | respondida no Adendo 2 |
| Q8 | Com o tutorial ligado, o que aparece para o usuário? | RQ-10, RQ-12 | Checklist flutuante, tours e a página de progresso no menu, nos três painéis; boas-vindas desligada | respondida no Adendo 2 |
| Q10 | Com o tutorial desligado depois de a tela abrir o checklist, o próximo clique nele é recusado ou ainda grava? | RQ-17 | Recusado, nada gravado — falha fechado | respondida no Adendo 3 |
| Q11 | Com o tutorial desligado, a URL da página "Primeiros passos" (`/{painel}/onboarding`) responde 404 ou abre? (devolvida pela derivação do `04`, era Q?1) | RQ-12, RQ-17 | 404 — falha fechado, o regime do Adendo 1 | aberta — premissa P-04 |
| Q12 | Uma tela de autoria já aberta quando o tutorial é desligado: o próximo "Salvar" é recusado (404) e nada é gravado? (era Q?2) | RQ-16 | Sim — o mesmo da Q10 e da P-06 dos agentes de IA | aberta — premissa P-05 |
| Q13 | Com o tutorial ligado, quem vê o checklist e a página: todo papel que entra no painel, ou só quem tem a permissão da página? (era Q?3) | RQ-28 | Todo papel do kit recebe a permissão por padrão; o administrador pode retirar na tela de papéis | aberta — premissa P-06 |
| Q14 | Com o tutorial desligado, visitar a tela de configurações conclui o passo "por visita"? (era Q?4) | RQ-30, RQ-17 | Não — desligado, nada é gravado | aberta — premissa P-07 |
| Q15 | Na linha do `/app` com tenancy da tabela aprovada ("tour / condição"), qual passo conclui por condição? (era Q?5) | RQ-23, RQ-24 | Só "Convites", por "já convidou alguém nesta organização"; os outros três por tour | aberta — premissa P-08 |
| Q16 | O progresso do checklist do `/app` é por organização ou da pessoa em todas? (era Q?6) | RQ-28, RQ-24 | Por organização — o escopo do pacote | aberta — premissa P-09 |
| Q17 | `kit:install --no-seed --onboarding` semeia as jornadas? (era Q?7) | RQ-06, RQ-20 | Não semeia (sem papéis e usuário não há a quem mostrar); o resumo diz como semear depois | aberta — premissa P-10 |
| Q21 | Um passo cuja tela a pessoa não tem permissão de abrir aparece no checklist dela? (revisão adversarial do `04`, era Q?11) | RQ-23, P-03 | Não — some e não conta no percentual, falha fechado | aberta — premissa P-11 |
| Q22 | A multi-organização foi ligada, as jornadas semeadas, e depois ela foi desligada: os passos de organização continuam visíveis? (era Q?12) | RQ-09, RQ-24, P-03 | Não — somem enquanto ela estiver desligada, sem apagar | aberta — premissa P-12 |
| Q23 | Com os agentes de IA desligados, o passo "Execuções de IA" do `/infra` some? (era Q?13) | P-03, RQ-23 | Só se a tela some; ela continua existindo (é trilha), então o passo fica | aberta — premissa P-13 |
| Q24 | Concluir "Busca ⌘K" (ou Dock, ou Bloqueio de tela) no `/admin` conclui o mesmo passo no `/app` e no `/infra`? (era Q?14) | RQ-18, RQ-28 | Não — cada painel tem a sua jornada e o seu progresso | aberta — premissa P-14 |
| Q25 | A rule de manutenção carrega também quando o agente altera features base sem tela própria (dock, bloqueio de tela, ⌘K, dashboard dinâmico, hub)? (era Q?15) | RQ-13, RQ-26 | Sim — "alguma das features base" não se limita às telas | aberta — premissa P-15 |
| Q26 | "Documentar como iniciar via seeder" inclui dizer que é preciso ligar o tutorial nas configurações depois de semear? (era Q?16) | RQ-07, RQ-21 | Sim — sem isso o passo documentado não mostra nada | aberta — premissa P-16 |
| Q27 | Com o tutorial ligado, quem não tem a permissão da página "Primeiros passos" e digita `/{painel}/onboarding` recebe 403 ou 404? (fechamento da revisão adversarial, era Q?17) | P-06, RQ-28 | 403 — o regime das telas do kit sem permissão; 404 fica para o tutorial desligado (P-04) | aberta — premissa P-18 |
| Q28 | Uma jornada concluída com um passo escondido pendente: se o passo voltar a ficar visível, a jornada volta a "em andamento"? (2ª rodada adversarial, era Q?18) | P-03, P-11, P-02 | Não — a conclusão registrada permanece; o passo que voltou aparece pendente, e o widget não oscila | aberta — premissa P-19 |

## Premissas

<!-- Revisável. O que a feature assume sem que o solicitante tenha escrito. Não é requisito. -->

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | O texto das jornadas, passos, paradas de tour e condições semeadas é escrito em português do Brasil e em inglês, e os dois idiomas ficam editáveis no painel de autoria | step 4 — o kit é documentado em pt e en | 2026-10-21 | RQ-02, RQ-03 | vigente |
| P-02 | Com o tutorial desligado, o widget "Jornadas de onboarding concluídas" do dashboard do `/admin` não é exibido; volta ao religar, sem perder dado | step 4 — premissa de comportamento, falha fechado (mesma decisão do Adendo 1 dos agentes de IA para os widgets do catálogo) | 2026-10-21 | RQ-12 | vigente |
| P-04 | Com o tutorial desligado, a página "Primeiros passos" (`/{painel}/onboarding`) responde 404 pela URL, nos três painéis | step 7 — Q11, premissa de comportamento, falha fechado | 2026-10-21 | RQ-12, RQ-17 | vigente |
| P-05 | Com o tutorial desligado depois de uma tela de autoria já aberta, a próxima ação nela responde 404 e nada é gravado | step 7 — Q12, falha fechado | 2026-10-21 | RQ-16 | vigente |
| P-06 | Com o tutorial ligado, vê o checklist, os tours e a página "Primeiros passos" quem tem a permissão da página; numa instalação nova, todo papel do kit que entra num painel a recebe nesse painel | step 7 — Q13 | 2026-10-21 | RQ-28 | vigente |
| P-07 | Com o tutorial desligado, visitar a tela de um passo "por visita" não conclui o passo | step 7 — Q14, falha fechado | 2026-10-21 | RQ-30 | vigente |
| P-08 | Dos passos de tenancy do `/app`, só "Convites" conclui por condição ("já convidou alguém nesta organização"); os demais concluem ao terminar o tour | step 7 — Q15 | 2026-10-21 | RQ-23, RQ-24 | vigente |
| P-09 | Com multi-tenant, o progresso no `/app` é por organização: a mesma pessoa faz a jornada separadamente em cada uma | step 7 — Q16 | 2026-10-21 | RQ-24, RQ-28 | vigente |
| P-10 | `kit:install --no-seed --onboarding` não semeia as jornadas e avisa no resumo como semear depois | step 7 — Q17 | 2026-10-21 | RQ-20 | vigente |
| P-11 | Um passo cuja tela a pessoa não pode abrir no painel corrente não aparece no checklist dela e não conta no percentual | step 7 — Q21, revisão adversarial (ADV-36), falha fechado | 2026-10-21 | RQ-23, P-03 | vigente |
| P-12 | Com a multi-organização desligada depois de semeada, os passos de organização não aparecem; voltam ao religar, sem perder dado | step 7 — Q22 (ADV-37) | 2026-10-21 | RQ-09, RQ-24 | vigente |
| P-13 | O passo "Execuções de IA" do `/infra` continua visível com os agentes de IA desligados: a tela continua existindo | step 7 — Q23 (ADV-37) | 2026-10-21 | P-03 | vigente |
| P-14 | O progresso é por jornada: concluir um passo no `/admin` não conclui o passo de mesmo nome no `/app` nem no `/infra` | step 7 — Q24 (ADV-35) | 2026-10-21 | RQ-18, RQ-28 | vigente |
| P-15 | A regra de manutenção vale também para o código das features base sem tela própria (dock, ⌘K, bloqueio de tela, dashboard dinâmico, hub, agentes de IA) | step 7 — Q25 (ADV-31) | 2026-10-21 | RQ-13, RQ-26 | vigente |
| P-16 | A documentação de como iniciar via seeder diz que, depois de semear, é preciso ligar o tutorial na tela de configurações | step 7 — Q26 (ADV-32) | 2026-10-21 | RQ-07 | vigente |
| P-17 | Um seeder filho de multi-organização rodado sozinho com a multi-organização desligada não cria nada | step 7 — ADV-12 | 2026-10-21 | RQ-24 | vigente |
| P-18 | Com o tutorial ligado, `/{painel}/onboarding` responde 403 a quem não tem a permissão da página "Primeiros passos" | step 7 — Q27 | 2026-10-21 | P-06, RQ-28 | vigente |
| P-19 | Uma jornada já concluída continua concluída quando um passo antes escondido volta a ficar visível; o passo aparece pendente e o widget continua contando a jornada como concluída | step 7 — Q28 (2ª rodada adversarial) | 2026-10-21 | P-03, P-11, P-02 | vigente |
| P-20 | Escrever a chave da jornada direto na propriedade do checklist (sem passar pela troca de aba) com uma jornada que a pessoa não vê neste painel não muda o checklist: ele continua na jornada visível | step 9 — CR-01 (`/code-review`) | 2026-10-21 | RQ-28 | vigente |
| P-21 | Se a semeadura do tutorial falhar durante o `kit:install`, a instalação não é interrompida: o resumo final (com a senha do administrador gerada) aparece, junto com o aviso de como semear depois | step 9 — CR-02 (`/code-review`) | 2026-10-21 | RQ-06, RQ-19, RQ-20 | vigente |
| P-22 | O passo do dashboard dinâmico só aparece para quem pode montar o dashboard dinâmico daquele painel (além de ele estar ligado no painel) | step 9 — CR-03 (`/code-review`); redação revista por gsferro no chat: a página abre para todos, montar é o que o passo ensina | 2026-10-21 | P-03, P-11 | vigente |
| P-23 | Nenhum passo semeado fica escondido de todo mundo por uma condição de visibilidade que não existe: toda condição citada por um passo semeado está registrada | step 9 — CR-04 (`/code-review`) | 2026-10-21 | RQ-02, P-11 | vigente |
| P-24 | Num projeto atualizado pelo `kit:update`, o canal de log do tutorial existe mesmo que a configuração de log do projeto não o declare, e a configuração de log do projeto não é sobrescrita pela atualização | step 9 — RD-02 (`fw-revisor-diff`); redação revista por gsferro no chat na rodada 2 (RD2-03, CR2-02): o arquivo de log do esqueleto, entregue inteiro, apagava os canais próprios do projeto | 2026-10-21 | RQ-02 | vigente |
| P-03 | Um passo de feature que a instalação desligou (dock fora do painel, bloqueio de tela desligado, hub desligado, dashboard dinâmico desligado, agentes de IA desligados, central de comandos desligada) não aparece no checklist nem conta no percentual enquanto a feature estiver desligada; volta ao religar | step 4 — leitura de RQ-01 ("se qualificam"), falha fechado: tour que aponta para tela ausente | 2026-10-21 | RQ-02 | vigente |

## Fora de Escopo (declarado)

- Botões "ver o tutorial" dentro das próprias telas das features: os tours partem do checklist e da página de progresso
- Vídeos e imagens nos passos: o seeder não envia mídia
- Tela de boas-vindas do pacote (Adendo 2: desligada)
- Telas de autenticação (login, cadastro, recuperação, verificação de e-mail, desafio de 2FA) e a página pública de boas-vindas do kit: estão fora de um painel carregado, onde o checklist não existe
- Apagar progresso, jornadas ou condições ao desligar o tutorial: nada é apagado

## Adendo 1 — 2026-10-21

- **Fonte**: respostas do solicitante às perguntas Q1 a Q4, no chat (seleção de opção)
- **Fidelidade**: alta (texto escrito — opção escolhida)
- **Responde a**: Q1, Q2, Q3, Q4

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> Q1="Tudo some + 404 (Recomendado)" — Os menus 'Onboarding' e 'Condições' saem do /admin e a URL responde 404, mesmo com permissão. Launcher, tours e boas-vindas não aparecem em painel nenhum. É o mesmo regime dos agentes de IA e falha fechado.
> Q2="Uma por painel (Recomendado)" — 'Primeiros passos no Admin/App/Infra': cada seeder filho de feature acrescenta o seu passo, com tour, à jornada do painel. São 3 abas no launcher no máximo.
> Q3="Pergunta no kit:install (Recomendado)" — Confirm 'Popular os tutoriais guiados?' com padrão não, mais a opção --onboarding para o modo não interativo. Só semeia: o interruptor continua desligado até ser ligado no settings.
> Q4="Só o interruptor (Recomendado)" — Cada jornada já carrega o painel dela (panel_id). Uma segunda lista duplicaria essa escolha e poderia contradizê-la.

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-15 | Com o tutorial desligado, os menus "Onboarding" e "Condições" não aparecem no `/admin` | "Os menus 'Onboarding' e 'Condições' saem do /admin" | funcional | RQ-12 (parcial) |
| RQ-16 | Com o tutorial desligado, as telas de autoria (jornadas e condições) respondem 404 pela URL, inclusive para quem tem a permissão | "e a URL responde 404, mesmo com permissão" | autorização | RQ-12 (parcial) |
| RQ-17 | Com o tutorial desligado, o checklist flutuante, os tours e as boas-vindas não aparecem em painel nenhum | "Launcher, tours e boas-vindas não aparecem em painel nenhum" | funcional | RQ-12 (parcial) |
| RQ-18 | Existe uma jornada por painel ("Primeiros passos" no `/admin`, no `/app` e no `/infra`), e cada seeder filho de feature acrescenta o seu passo, com tour, à jornada do painel em que a feature vive | "'Primeiros passos no Admin/App/Infra': cada seeder filho de feature acrescenta o seu passo, com tour, à jornada do painel" | funcional | — |
| RQ-19 | O `kit:install` interativo pergunta "Popular os tutoriais guiados?", com a resposta padrão não; sim roda o seeder pai | "Confirm 'Popular os tutoriais guiados?' com padrão não" | funcional | — |
| RQ-20 | O `kit:install` aceita uma opção `--onboarding` que roda o seeder pai sem perguntar, para o modo não interativo | "mais a opção --onboarding para o modo não interativo" | funcional | — |
| RQ-21 | Semear as jornadas não liga o interruptor: ele continua desligado até ser ligado na tela de configurações | "Só semeia: o interruptor continua desligado até ser ligado no settings" | restrição | — |
| RQ-22 | O settings do tutorial tem só o interruptor, sem lista de painéis; o painel de cada jornada é o que ela mesma declara | "Cada jornada já carrega o painel dela (panel_id). Uma segunda lista duplicaria essa escolha" | restrição | — |

## Adendo 2 — 2026-10-21

- **Fonte**: respostas do solicitante às perguntas Q5 a Q8, no chat (seleção de opção)
- **Fidelidade**: alta (texto escrito — opção escolhida)
- **Responde a**: Q5, Q6, Q7, Q8

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> Q5="Aprovar a lista (Recomendado)" — Segue a tabela: todas as features operadas no painel, com as de tenancy em seeders só de tenant.
> Q6="Preservado (Recomendado)" — O seeder cria só o que falta (pela key) e nunca sobrescreve texto editado no painel. Falha fechado: não destrói autoria.
> Q7="Rule + teste de inventário (Recomendado)" — Uma rule em .ai/rules com os globs das telas que manda revisar o seeder filho. Mais um teste em tests/Kit que falha quando nasce um Resource ou Page sem seeder de onboarding e sem isenção declarada. O teste avisa mesmo quando o agente não lê a rule.
> Q8="Launcher + tours + página (Recomendado)" — Checklist flutuante, tours e a página 'Primeiros passos' no menu, nos três painéis. A tela de boas-vindas fica desligada, para não interromper o primeiro login.

A tabela aprovada na Q5, apresentada no chat imediatamente antes da pergunta, copiada sem alteração:

> | Painel | Passo (seeder filho) | Some quando… | Conclui por |
> |---|---|---|---|
> | todos | Perfil e 2FA | — | condição (2FA ativo) |
> | todos | Busca ⌘K (Spotlight) | — | manual (tour) |
> | todos | Dock de atalhos | dock fora do painel | manual (tour) |
> | todos | Bloqueio de tela | lockscreen desligado | manual (tour) |
> | todos | Dashboard dinâmico | dinâmico desligado no painel | manual (tour) |
> | admin | Usuários | — | tour |
> | admin | Convites | — | condição (já convidou alguém) |
> | admin | Papéis e permissões | — | tour |
> | admin | Configurações da aplicação | — | visita |
> | admin | Organizações | **seeder só com tenancy** | tour |
> | admin | Agentes de IA | agentes desligados | tour |
> | admin / app | Hub (cards) | `kit.hub` desligado | tour |
> | app | Membros, Convites, Convites recebidos, Troca de organização | **seeders só com tenancy** | tour / condição |
> | app | Projetos (demo) | **só com tenancy**; some sem `kit.demo` | tour |
> | infra | Hub de infraestrutura, Pulse, Backups, Logs, Exceções, Filas, Auditoria, Execuções de IA, Lixeira, Central de comandos | central de comandos desligada | tour |
>
> Ficam de fora, com o motivo: as telas de login, cadastro e recuperação (sem painel carregado, o launcher não existe), a Boas-vindas (`/` fora de painel) e os widgets soltos (já fazem parte do passo do dashboard).

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-23 | As features qualificadas são as da tabela aprovada, cada uma no painel e com o modo de conclusão que a tabela declara; as excluídas são as que a tabela exclui | "Segue a tabela: todas as features operadas no painel" | funcional | RQ-01 (parcial) |
| RQ-24 | As features que só existem com multi-tenant (Organizações no `/admin`; Membros, Convites, Convites recebidos, Troca de organização e Projetos no `/app`) ficam em seeders filhos que só rodam com o multi-tenant ligado | "com as de tenancy em seeders só de tenant" | funcional | RQ-09 (parcial) |
| RQ-25 | Rodar o seeder pai de novo cria só a jornada, o passo e a condição que faltam (identificados pela chave) e nunca sobrescreve o que já existe, inclusive texto editado no painel | "O seeder cria só o que falta (pela key) e nunca sobrescreve texto editado no painel" | restrição | — |
| RQ-26 | Existe uma regra de projeto em `.ai/rules`, com os caminhos das telas das features, que manda revisar o seeder filho correspondente | "Uma rule em .ai/rules com os globs das telas que manda revisar o seeder filho" | funcional | RQ-13 (parcial) |
| RQ-27 | Um teste do kit falha quando nasce um Resource ou uma Page de painel sem seeder de onboarding e sem isenção declarada | "Mais um teste em tests/Kit que falha quando nasce um Resource ou Page sem seeder de onboarding e sem isenção declarada" | funcional | RQ-13 (parcial) |
| RQ-28 | Com o tutorial ligado, o checklist flutuante, os tours e a página "Primeiros passos" no menu aparecem nos três painéis | "Checklist flutuante, tours e a página 'Primeiros passos' no menu, nos três painéis" | funcional | — |
| RQ-29 | A tela de boas-vindas do pacote fica desligada, com o tutorial ligado ou desligado | "A tela de boas-vindas fica desligada" | restrição | — |

## Adendo 3 — 2026-10-21

- **Fonte**: resposta do solicitante à pergunta Q10, no chat (seleção de opção)
- **Fidelidade**: alta (texto escrito — opção escolhida)
- **Responde a**: Q10

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> Q10="É recusado (Recomendado)" — Nada é gravado. O checklist some no próximo carregamento. Falha fechado, igual ao chat dos agentes de IA.

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-30 | Com o tutorial desligado depois de uma tela já ter aberto o checklist, toda ação seguinte do checklist (marcar, pular, desfazer, iniciar tour, progresso de tour) é recusada e nada é gravado | "Nada é gravado" | autorização | — |
| RQ-31 | Depois de desligado, o checklist não aparece no próximo carregamento de tela | "O checklist some no próximo carregamento" | funcional | — |

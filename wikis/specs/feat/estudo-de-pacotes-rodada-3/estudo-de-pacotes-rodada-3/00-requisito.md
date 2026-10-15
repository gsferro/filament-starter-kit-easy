# Requisito — Estudo e adoção de pacotes Filament, rodada 3 (tabelas e PHPStan)

## Fonte

- **Origem**: mensagem no chat, invocando a skill `feature-wiki` (texto escrito pelo solicitante como argumento do comando)
- **Data**: 2026-10-14
- **Autor / solicitante**: gsferro (dono do kit; também o desenvolvedor)
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> analise os proximos pacotes e instale no projeto em caso de agregar valor ao kit, links:
> - https://filamentphp.com/plugins/msehsah-remember-table
> - https://filamentphp.com/plugins/spiggle-list-view
> - https://filamentphp.com/plugins/asign-infinite-scroll 
> - https://filamentphp.com/plugins/happenv-com-phpstan-readable-macros
> - https://filamentphp.com/plugins/happenv-com-saved-views
> use o "codex --yolo" para te ajudar na analise e implementação.
> talvez seja necessário criar settings para ativar ou desativar essas novas funcionalidades.
> como padrão, tudo vem desativado e pode ser ativado conforme alteração no settings do projeto.
> deixe tudo muito bem documentado e faça a captura de trumbs e gifs (tanto no modo light quanto no dark) se necessário.
> crie uma nova branch para essa implementação, enquanto outra sessão esta finalizando os testes do CI no repositorio.

## Decomposição em Cláusulas

<!-- Derivada e revisável. Estado: fechada · aberta — Qn · substituída por RQ-nn (Adendo N) · decomposta em RQ-nn, RQ-mm -->

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | Os cinco pacotes listados são analisados, um a um, com o critério de "agregar valor ao kit" escrito e a conclusão de cada um registrada | "analise os proximos pacotes" · "em caso de agregar valor ao kit" | funcional | fechada |
| RQ-02 | Só os pacotes cuja análise conclui que agregam valor são instalados no projeto; os demais ficam de fora com o motivo escrito | "instale no projeto em caso de agregar valor ao kit" | funcional | fechada |
| RQ-03 | O Codex CLI (`codex --yolo`) é usado como auxiliar na análise e na implementação | "use o \"codex --yolo\" para te ajudar na analise e implementação" | restrição | fechada |
| RQ-04 | Toda funcionalidade nova que chega ao usuário por um pacote adotado pode ser ligada e desligada nas configurações do kit (Settings) | "talvez seja necessário criar settings para ativar ou desativar essas novas funcionalidades" | funcional | fechada |
| RQ-05 | Toda funcionalidade nova nasce **desligada** numa instalação; ligar é uma alteração nas configurações do projeto, não um deploy | "como padrão, tudo vem desativado e pode ser ativado conforme alteração no settings do projeto" | funcional | fechada |
| RQ-06 | A entrega é documentada por completo: o que entrou, o que ficou de fora e por quê, como ligar, e o que cada opção faz | "deixe tudo muito bem documentado" | não-funcional | fechada |
| RQ-07 | As funcionalidades visíveis ganham capturas de tela (thumbs) e animações (GIF) nos temas claro **e** escuro, quando a funcionalidade tem o que mostrar | "faça a captura de trumbs e gifs (tanto no modo light quanto no dark) se necessário" | não-funcional | fechada |
| RQ-08 | A implementação acontece numa branch nova, sem interferir no trabalho de CI que outra sessão conduz no repositório | "crie uma nova branch para essa implementação, enquanto outra sessão esta finalizando os testes do CI no repositorio" | restrição | fechada |

## Perguntas ao Solicitante

<!-- Raia requisito: só o solicitante responde. A sessão roda em autonomia e o solicitante não está
     disponível durante a execução: cada pergunta abaixo foi respondida pela recomendação (a opção que
     falha fechado) e registrada como premissa em ## Premissas. Resposta diferente entra como Adendo e
     reverte a premissa. -->

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | Numa instalação nova, quais papéis recebem a permissão de salvar visões de tabela (`Usar:VisoesDeTabela`)? Todos os papéis de painel, ou só os de administração, como foi decidido para o dock (`Usar:Dock`)? | RQ-04, RQ-05 | Só os papéis de administração (`admin`, `infra`, `admin_app`); o `panel_user` recebe pela tela de papéis, se o administrador quiser — a mesma resposta dada à Q12 da wiki do dock, e a que falha fechado | aberta → premissa P-01 |
| Q2 | A opção "Compartilhar com todos" de uma visão salva (visível a todo usuário do painel — na organização, a todos os membros dela) deve existir? Se sim, nasce ligada ou desligada? | RQ-04, RQ-05 | Existe, governada por um segundo interruptor nas configurações, e nasce **desligada**: visão compartilhada expõe o nome e o recorte escolhido por um colega a todos os outros | aberta → premissa P-02 |
| Q3 | Quando o administrador **desliga** as visões salvas depois de os usuários terem gravado visões, elas são apagadas ou só escondidas? | RQ-05 | Só escondidas: continuam gravadas e voltam quando a opção é religada — apagar é irreversível e o interruptor é reversível | aberta → premissa P-03 |
| Q5 | O `infinite-scroll` 1.0.1 declara `filament/filament ^5.0`, mas usa `TablesRenderHook::CONTENT_AFTER`, que só existe a partir do Filament 5.10 — com o 5.8.2 travado no `composer.lock` a aplicação nem boota. Subir o Filament para 5.10.2 (dentro do `^5.8.1` do `composer.json`) nesta entrega, ou deixar o pacote de fora até o próximo bump? | RQ-02 | Subir: o bump cabe na constraint declarada, é o mesmo tipo de atualização que o dependabot abre toda semana, e a suíte do kit é a prova; a alternativa deixa um dos três pacotes com valor reconhecido de fora por um detalhe de versão | aberta → premissa P-12 |
| Q6 | O nome de uma visão tem regras (obrigatório, tamanho máximo, sem repetir na mesma listagem)? Há teto de visões por usuário por listagem? | P-08, RQ-04 | Nome obrigatório e até 100 caracteres, teto de 50 por usuário por listagem (os dois do pacote); **repetição permitida** — exigir nome único custaria validação própria sobre o schema do vendor nas duas Actions, e o dono distingue pelo badge e pela posição | aberta → premissa P-13 (devolvida pela derivação do `04`) |
| Q7 | A visão marcada como padrão é aplicada sozinha ao abrir a listagem? Vale para a padrão compartilhada por um colega? | RQ-04, P-09 | Só a padrão do próprio usuário; a de colega nunca — aplicar sozinho o recorte de outra pessoa muda o que o usuário vê sem ele pedir | aberta → premissa P-14 (derivação do `04`) |
| Q8 | O campo de compartilhamento aparece só com as visões ligadas? Ao desligar as visões, o compartilhamento gravado continua ligado para quando elas voltarem? | P-02, P-03 | Só com as visões ligadas e, ao desligá-las, o compartilhamento é gravado desligado — reabrir a exposição a todos deve ser ato explícito | aberta → premissa P-15 (derivação do `04`) |
| Q9 | A visão salva guarda o tamanho de página? | P-09, P-10 | Não — com o carregamento contínuo o tamanho é o bloco da instalação, e uma visão que o restaurasse brigaria com ele | aberta → premissa P-16 (derivação do `04`) |
| Q10 | As telas novas ficam em pt_BR, com todo texto do pacote traduzido, e os rótulos/ajudas do plano valem? | RQ-04, RQ-06 | Sim — o kit inteiro fala pt_BR | aberta → premissa P-17 (derivação do `04`) |
| Q11 | Quando o autor de uma visão compartilhada perde o vínculo com a organização, ou a conta fica inativa ou excluída, a visão dele continua aparecendo para os colegas? | P-07 | Deixar de aparecer, sem apagar — mas isso exige consulta própria sobre o estado do autor por cima do pacote; **fora desta entrega**, recorte de R10 bloqueado no `04` até a resposta | aberta (derivação do `04`) |
| Q13 | No painel de organização, o teto de 50 visões por usuário por listagem conta cada organização separadamente ou soma todas as organizações do usuário? | P-13 | Cada organização separadamente — cada organização é um espaço isolado (P-07) e é o que o pacote faz; somar exigiria consulta própria por cima do escopo dele | aberta → premissa P-18 (derivação do `04`, rodada 2) |
| Q14 | Gerenciar (renomear) uma visão já compartilhada com o compartilhamento desligado preserva o `is_public` gravado ou o zera? | P-02, P-15 | Zera: toda visão tocada no gerenciamento é gravada com compartilhamento desligado (o pacote grava `allow_public_views && input`); a não tocada fica como está | aberta → premissa P-19 (revisão adversarial, rodada 1) |
| Q15 | O `master_global` é exceção a "só o dono atualiza, renomeia, reordena ou apaga"? | P-08 | Não: a posse é filtro da consulta (`ownedBy`), não policy, e o `Gate::before` não a alcança | aberta → premissa P-20 (revisão adversarial) |
| Q16 | "Nome obrigatório" recusa um nome só com espaços? | P-13 | Sim — vazio depois de `trim` é vazio | aberta → premissa P-21 (revisão adversarial) |
| Q17 | O `?view={id}` na URL vence a visão padrão? | P-14 | Sim: a visão da URL é aplicada antes de a padrão ser procurada | aberta → premissa P-22 (revisão adversarial) |
| Q18 | Com o total exatamente igual a 500, o rodapé pede busca ou filtro? | P-10 | Não: tudo carregou ("Todos os 500 registros"); o pedido só aparece quando há mais do que o teto | aberta → premissa P-23 (revisão adversarial) |
| Q19 | Salvar a tela com um valor ilegível gravado em `tabela_carregamento` regrava `paginacao`? O Select mostra o quê? | P-11 | O Select mostra `paginacao` (o ilegível é coagido ao preencher, como a densidade) e salvar regrava `paginacao` | aberta → premissa P-24 (revisão adversarial) |
| Q20 | A documentação é em pt e en? | RQ-06 | Sim — convenção do kit (todo doc tem o par pt/en) | aberta → premissa P-25 (revisão adversarial) |
| Q21 | "Visão tocada" (P-19) é só a visão alterada no gerenciamento, ou toda visão enviada pelo formulário, que reenvia todas? | P-19 | Toda visão enviada: com o compartilhamento desligado, todas as do formulário são regravadas privadas — é o que o pacote faz e o que falha fechado | aberta → premissa P-26 (revisão adversarial, rodada 2), que substitui P-19 |
| Q22 | O `master_global` fica sem exceção também na visibilidade das visões privadas de outros usuários? | P-07, P-20 | Sim: a visibilidade é consulta (próprias + compartilhadas), sem policy; o master não vê nem aplica a privada de outro | aberta → premissa P-27 (revisão adversarial, rodada 2) |
| Q4 | O carregamento contínuo (rolagem infinita / botão "Carregar mais") vale para **todas** as tabelas dos três painéis, inclusive as dos plugins de terceiros, como os demais defaults de tabela do kit? | RQ-04 | Sim: é um default de tabela, e os quatro já existentes (paginação, listras, persistência, colunas arrastáveis) valem para toda tabela — meia aplicação paginada e meia rolando seria pior que qualquer um dos dois | aberta → premissa P-04 |

## Premissas

<!-- Revisável. O que a feature assume sem que o solicitante tenha escrito. -->

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | A permissão `Usar:VisoesDeTabela` nasce nos papéis `admin`, `infra` e `admin_app`, e **não** no `panel_user` | Q1, respondida pela recomendação (autonomia) | 2026-10-14 | RQ-04, RQ-05 | vigente |
| P-02 | "Compartilhar com todos" existe e é governada por um interruptor próprio, que nasce desligado; desligado, a opção some dos formulários e nenhuma visão é compartilhada mesmo que já tenha sido marcada | Q2, respondida pela recomendação (autonomia) | 2026-10-14 | RQ-04, RQ-05 | vigente |
| P-03 | Desligar as visões salvas esconde o botão e as ações; as visões gravadas ficam no banco e voltam ao religar | Q3, respondida pela recomendação (autonomia) | 2026-10-14 | RQ-05 | vigente |
| P-04 | O carregamento contínuo é um default global de tabela: vale para toda tabela dos três painéis, inclusive as de plugins de terceiros, e cada bloco tem o tamanho de "Linhas por página" das configurações | Q4, respondida pela recomendação (autonomia) · análise do step 3 | 2026-10-14 | RQ-04 | vigente |
| P-05 | A extensão de PHPStan não tem interruptor: é ferramenta de desenvolvimento, não chega ao usuário e RQ-04/RQ-05 não se aplicam a ela | análise do step 3 | 2026-10-14 | RQ-04, RQ-05 | vigente |
| P-07 | Uma visão salva pertence a quem a gravou, à página de listagem em que foi gravada e, no painel de organização, à organização aberta: não aparece noutra página, noutra organização nem para outro usuário — salvo quando marcada como compartilhada, e aí só para os usuários da mesma organização (ou do mesmo painel, fora da organização) | análise do step 3 (pacote: `scope_to_tenant`, `visibleTo`, `ownedBy`) | 2026-10-14 | RQ-04 | vigente |
| P-08 | Só o dono atualiza, renomeia, reordena ou apaga a própria visão; os demais só aplicam a compartilhada. Salvar, atualizar, gerenciar e limpar exigem a permissão `Usar:VisoesDeTabela`; **aplicar** uma visão visível não exige | decisão de desenho D6 | 2026-10-14 | RQ-04, P-01 | vigente |
| P-09 | Uma visão aplicada só restaura o que ainda existe na tabela (filtro, coluna, agrupamento, aba e tamanho de página removidos são ignorados; coluna nova aparece); ela guarda recorte, não dados | análise do step 3 (pacote: `applyTableViewState`) | 2026-10-14 | RQ-04 | vigente |
| P-10 | Com o carregamento contínuo ligado (`rolagem` ou `botao`), cada bloco tem o tamanho de "Linhas por página", a lista reinicia no primeiro bloco quando busca, filtro, ordenação, agrupamento ou aba mudam, nunca passa de 500 linhas na tela (o rodapé pede busca ou filtro), e o seletor "registros por página" some; com `paginacao` (o default) nada muda em relação a hoje | análise do step 3 (pacote: `InfiniteScroll::configure`, `InfiniteScrollHook`) · P-04 | 2026-10-14 | RQ-04, RQ-05 | vigente |
| P-11 | Valor fora do vocabulário em "carregamento da tabela" (no `.env` ou no banco) cai em `paginacao` sem erro na tela | precedente `DensidadeDoLayout` | 2026-10-14 | RQ-05 | vigente |
| P-12 | O Filament sobe de 5.8.2 para 5.10.2 nesta entrega (dentro de `^5.8.1`), porque o `infinite-scroll` depende do render hook `TablesRenderHook::CONTENT_AFTER`, inexistente no 5.8; os assets publicados do Filament acompanham | Q5, respondida pela recomendação (autonomia) · passo 1 da implementação | 2026-10-14 | RQ-02 | vigente |
| P-13 | O nome da visão é obrigatório e tem até 100 caracteres; há no máximo 50 visões por usuário em cada listagem; nomes podem se repetir | Q6, respondida pela sessão (autonomia) · validação e config do pacote | 2026-10-14 | P-08, RQ-04 | vigente |
| P-14 | Só a visão padrão do próprio usuário é aplicada sozinha ao abrir a listagem (e só quando a URL não traz filtro, busca, ordenação, agrupamento nem aba); a padrão de um colega nunca | Q7, respondida pela recomendação | 2026-10-14 | RQ-04, P-09 | vigente |
| P-15 | "Compartilhar visões com todos" só aparece na aba Tabelas com as visões ligadas, e salvar a aba com as visões desligadas grava o compartilhamento desligado | Q8, respondida pela recomendação | 2026-10-14 | P-02, P-03 | vigente |
| P-16 | A visão não guarda o tamanho de página | Q9, respondida pela recomendação · default do pacote | 2026-10-14 | P-09, P-10 | vigente |
| P-17 | Toda chave de texto do pacote de visões tem tradução pt_BR, e os rótulos e textos de ajuda das opções novas são os do plano | Q10, respondida pela recomendação | 2026-10-14 | RQ-04, RQ-06 | vigente |
| P-18 | O teto de 50 visões por usuário por listagem é contado por organização: com 50 numa organização, a primeira em outra é aceita | Q13, respondida pela sessão (autonomia) · escopo do pacote | 2026-10-14 | P-13, P-07 | vigente |
| P-19 | Gerenciar visões com o compartilhamento desligado grava `is_public = false` em toda visão tocada; a não tocada fica como está | Q14, respondida pela recomendação (autonomia) · comportamento do pacote | 2026-10-14 | P-02, P-15 | substituída por P-26 |
| P-26 | Gerenciar visões com o compartilhamento desligado regrava como privada (`is_public = false`) toda visão enviada pelo formulário — o formulário reenvia todas as do usuário, alteradas ou não; visão não enviada (nenhum formulário de gerenciar submetido) fica como está | Q21, respondida pela recomendação · comportamento do pacote | 2026-10-14 | P-02, P-15, P-19 | vigente |
| P-27 | O `master_global` não vê nem aplica (lista, `applyTableView`, `?view=`) a visão privada de outro usuário: a visibilidade é só próprias + compartilhadas, para todo papel | Q22, respondida pela recomendação · comportamento do pacote | 2026-10-14 | P-07, P-20 | vigente |
| P-20 | O `master_global` não é exceção: só o dono atualiza, renomeia, reordena ou apaga a própria visão | Q15, respondida pela recomendação | 2026-10-14 | P-08 | vigente |
| P-21 | Nome só com espaços é recusado como vazio | Q16, respondida pela recomendação | 2026-10-14 | P-13 | vigente |
| P-22 | `?view={id}` na URL vence a visão padrão | Q17, respondida pela recomendação · comportamento do pacote | 2026-10-14 | P-14 | vigente |
| P-23 | Com total igual a 500, tudo carrega e o rodapé não pede busca nem filtro | Q18, respondida pela recomendação · comportamento do pacote | 2026-10-14 | P-10 | vigente |
| P-24 | Valor ilegível gravado em `tabela_carregamento` aparece no Select como `paginacao` (coagido ao preencher) e salvar regrava `paginacao` | Q19, respondida pela recomendação · precedente da densidade | 2026-10-14 | P-11 | vigente |
| P-25 | A documentação da feature existe em pt e em en | Q20, respondida pela recomendação · convenção do kit | 2026-10-14 | RQ-06 | vigente |
| P-28 | Entrada de tipo errado numa propriedade pública do pacote (`activeTableView` com array) é recusada pelo framework com erro, sem aplicar nem gravar nada — não é 200 nem vazamento | achado do executor (CT-32, onda 2) · `TypeError` do Livewire na propriedade `string` do vendor | 2026-10-14 | P-07 | vigente |
| P-06 | Os pacotes `spiggle/list-view` e `happenv-com/filament-saved-views` **não** entram: o primeiro reconstrói as colunas de toda tabela num modo lista sem chave de organização e com adoção mínima; o segundo cobre o mesmo requisito do `remember-table` sem escopo por organização e exige tema customizado do Filament, que o kit não tem. Ambos ficam documentados com o gatilho de reabertura | análise do step 3 (RQ-01, RQ-02) | 2026-10-14 | RQ-02 | vigente |

## Fora de Escopo (declarado)

- Visões pré-definidas em código (`PresetView` do `remember-table`): o kit não embarca visão nenhuma — é recurso do projeto que nasce dele, e fica descrito na documentação como extensão.
- Compartilhamento por papel ou por usuário escolhido (o pacote só oferece "com todos", e isso está no roadmap dele).
- Tema customizado do Filament (`viteTheme()`), que o `saved-views` exigiria — decisão anterior do kit (`.ai/rules/css-filament.md`).
- Modo lista alfabética (`list-view`): o kit já empilha linhas em cartão no celular (`stackedOnMobile()`).
- Remover a exceção `simpleLightbox()` do `phpstan.neon`: o macro é registrado no `boot(Panel)` do plugin, que não roda na análise; a extensão de PHPStan não o alcança.
- Abrir o PR com merge: a branch é criada e o PR aberto, mas o merge espera o CI da outra sessão.

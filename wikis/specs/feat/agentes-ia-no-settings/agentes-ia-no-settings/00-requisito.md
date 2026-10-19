# Requisito — Agentes de IA ligados pelo settings, por painel

## Fonte

- **Origem**: pedido do solicitante no chat, via `/feature-wiki` (argumentos do comando)
- **Data**: 2026-10-19
- **Autor / solicitante**: gsferro (desenvolvedor e solicitante)
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> precisamos ter a opção de ativar e desativar o uso do agente e a opção de disponibilizar ele por cada painel, como é feito hoje com outros pacotes no settings
> - use o "codex --yolo" como auxiliar
> - crie uma branch para essa feature
> - se estiver false no settings o uso de Agentes, o Resource "Agentes de IA" (/admin/agentes-ia) não é disponivel no menu de admin e recebe 404 na url se tiver acesso, se tiver ativo, exibe normalmente.
> - por padrão deixe desativado
> - use a opção de escolha do painel, como foi feito no "Dock de atalhos", escolhendo em qual painel podera ser usado a feature
> - ao finalizar, abra o PR, quando passar, crie a nova tag + release

## Decomposição em Cláusulas

<!-- Derivada e revisável. Estado: fechada · aberta — Qn · substituída por RQ-nn (Adendo N) · decomposta em RQ-nn, RQ-mm -->

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | A tela de configurações da aplicação tem um interruptor que liga e desliga o uso dos agentes de IA | "a opção de ativar e desativar o uso do agente" | funcional | fechada |
| RQ-02 | A tela de configurações tem a opção de disponibilizar o agente por painel: uma lista dos painéis em que ele pode ser usado | "a opção de disponibilizar ele por cada painel" · "escolhendo em qual painel podera ser usado a feature" | funcional | fechada |
| RQ-03 | As duas opções seguem o modelo dos outros pacotes já configuráveis no settings: vivem na tela de configurações, gravam no banco (que vence o `.env` em execução) e valem no request seguinte, sem deploy | "como é feito hoje com outros pacotes no settings" | restrição | fechada |
| RQ-04 | Com o uso de agentes desligado, o Resource "Agentes de IA" não aparece no menu do painel de administração | "se estiver false no settings o uso de Agentes, o Resource "Agentes de IA" (/admin/agentes-ia) não é disponivel no menu de admin" | funcional | fechada |
| RQ-05 | Com o uso de agentes desligado, a URL `/admin/agentes-ia` responde 404 — inclusive para quem tem a permissão de abrir o catálogo | "e recebe 404 na url se tiver acesso" | autorização | fechada |
| RQ-06 | Com o uso de agentes ligado, o Resource "Agentes de IA" aparece e abre normalmente, como hoje | "se tiver ativo, exibe normalmente" | funcional | fechada |
| RQ-07 | Numa instalação nova (e no `.env` de fábrica) o uso de agentes nasce desligado | "por padrão deixe desativado" | restrição | fechada |
| RQ-08 | A escolha dos painéis segue o modelo do Dock de atalhos: uma lista de painéis em que a feature pode ser usada, em que lista vazia significa nenhum painel | "use a opção de escolha do painel, como foi feito no "Dock de atalhos"" | funcional | fechada |

Os quatro itens de processo do pedido — usar o `codex --yolo` como auxiliar, criar uma branch para a
feature, abrir o PR ao finalizar e, quando ele passar, criar a tag e a release — não são cláusulas do
sistema: são instruções à sessão, cumpridas e registradas em `03-progresso.md` (`## Processo do pedido`).

## Perguntas ao Solicitante

<!-- Raia requisito: só o solicitante responde. A sessão seguiu a recomendação de cada uma como premissa
     (P-01 a P-04, P-06 para a Q6, P-07 para a Q9 e P-08 para a Q10), porque a execução foi autônoma; a resposta entra como Adendo e, se divergir, a premissa
     é revogada e o código ajustado. -->

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | "Disponibilizar ele por cada painel": o que fica disponível por painel é o **chat do Assistente** (o botão flutuante que hoje só existe no `/app`), ou o pedido quer o próprio catálogo "Agentes de IA" registrado nos outros painéis? | RQ-02, RQ-08 | O chat do Assistente, nos painéis escolhidos; o catálogo continua só no `/admin`, onde é operação técnica. Registrar o Resource em outro painel exigiria policies e permissões por painel e contraria a decisão escrita no próprio Resource | aberta |
| Q2 | Com o uso desligado, os dois widgets do dashboard do `/admin` que leem o catálogo ("Agentes de IA" e "Agentes por provedor") também somem, ou continuam mostrando as contagens? | RQ-04 | Somem — falha fechado: mostrar números de um catálogo que a instalação diz não usar confunde, e o widget volta ao religar | respondida no Adendo 1 |
| Q3 | O chat do Assistente aberto numa tela quando a instalação desliga o uso (ou tira o painel da lista): a próxima mensagem é recusada (404, como a conversa alheia), ou ainda é respondida? | RQ-01, RQ-02 | Recusada com 404 e trilha no canal `ai` — falha fechado; o botão some no próximo carregamento da página | aberta |
| Q4 | Com o uso ligado e **nenhum** painel na lista, o chat não aparece em painel nenhum e o catálogo continua disponível no `/admin`? | RQ-02, RQ-08 | Sim: a lista governa só onde o chat aparece (vazio = nenhum, como no dock); o interruptor governa o catálogo e é condição também para o chat | aberta |
| Q6 | Uma tela do catálogo já aberta (criar ou editar um agente) quando a instalação desliga o uso: a próxima ação nela (salvar) é recusada com 404 e nada é gravado, ou ainda grava? (devolvida pela derivação do `04`, era Q?2) | RQ-05 | Recusa com 404 e nada gravado — falha fechado, a mesma regra de P-03 para o chat | aberta |
| Q8 | Os rótulos e textos de ajuda dos dois campos novos ("Usar agentes de IA", "Painéis onde o chat do Assistente aparece") só existem no plano. O solicitante quer decidir o texto? (era Q?4 do `04`) | RQ-01, RQ-02 | Não — aceitar o texto do plano; nenhum cenário depende dele. Sem bloqueio | aberta |
| Q9 | Com o chat em mais de um painel, as conversas de uma pessoa são as mesmas em todos os painéis da lista (o histórico segue a pessoa), ou separadas por painel? (ADV-22 da revisão adversarial) | P-01 | As mesmas: a conversa é da pessoa, não do painel — é o que o chat já faz (posse pelo participante), e separar por painel criaria histórico que some ao trocar de painel | aberta |
| Q10 | Com o chat no `/admin` ou no `/infra` (painéis sem organização na rota), a execução e o orçamento de IA vão para o tenant `default` do ledger (a instalação), como já acontece com job, comando e seeder — ou o pedido quer atribuí-los a alguma organização da pessoa? (CR-01 da revisão do diff) | P-01 | O tenant `default`: fora de uma rota de organização não há organização a escolher, e é o desenho já escrito do ledger; quem precisa de orçamento por organização usa o chat dentro do `/app/{organização}` | aberta |

## Premissas

<!-- Revisável. O que a feature assume sem que o solicitante tenha escrito. -->

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | O que a lista de painéis disponibiliza é o chat do Assistente: ele passa a ser renderizado em todo painel da lista (e só neles), e deixa de ser exclusivo do `/app`; o catálogo "Agentes de IA" continua registrado só no `/admin` | step 4 — leitura do pedido (Q1) | 2026-10-19 | RQ-02, RQ-08 | vigente |
| P-02 | Com o uso desligado, os widgets do dashboard do `/admin` que leem o catálogo ("Agentes de IA" e "Agentes por provedor") não são exibidos; voltam ao religar, sem perder dado | step 4 — premissa de comportamento, falha fechado (Q2) | 2026-10-19 | RQ-04 | promovida a RQ-09 (Adendo 1) |
| P-03 | Com o uso desligado no painel corrente (interruptor desligado ou painel fora da lista), toda ação do chat do Assistente responde 404 e nada é enviado ao agente nem gravado; a recusa deixa um `warning` no canal `ai` | step 4 — premissa de comportamento, falha fechado (Q3) | 2026-10-19 | RQ-01, RQ-02 | vigente |
| P-04 | "Não disponível no menu" vale para toda superfície de navegação que lista o catálogo: barra lateral, hub de cartões do `/admin`, busca ⌘K (telas, registros e "Criar agente") e o catálogo do dock de atalhos; favorito do dock que aponte para o catálogo vira favorito oculto enquanto o uso estiver desligado | step 4 — leitura do pedido ("não é disponivel no menu de admin") | 2026-10-19 | RQ-04 | vigente |
| P-05 | O interruptor e a lista nascem no `.env` (`KIT_AGENTES_IA`, `KIT_AGENTES_IA_PAINEIS`), semeiam o banco na migration de settings e, a partir daí, o banco vence — o mesmo regime do dock e do dashboard dinâmico | step 4 — leitura de "como é feito hoje com outros pacotes no settings" (RQ-03) | 2026-10-19 | RQ-03, RQ-07 | vigente |
| P-06 | Com o uso desligado depois de uma tela do catálogo (criar ou editar) já estar aberta, a próxima ação nela responde 404 e nada é gravado | step 7 — Q6 devolvida pela derivação do `04`, premissa de comportamento, falha fechado | 2026-10-19 | RQ-05 | vigente |
| P-07 | As conversas do chat são da pessoa: o histórico dela aparece no chat de qualquer painel da lista, e só o dela | step 7 — ADV-22 da revisão adversarial (Q9), premissa de comportamento | 2026-10-19 | P-01 | vigente |
| P-08 | Com o chat num painel sem organização na rota (`/admin`, `/infra`), a execução é registrada e o orçamento é conferido no tenant `default` do ledger de IA (a instalação), nunca numa organização da pessoa | step 9 — CR-01 (`/code-review`), premissa de comportamento (Q10) | 2026-10-19 | P-01 | vigente |

## Fora de Escopo (declarado)

- As trilhas do `/infra` sobre IA (execuções em `ai_runs`, o widget de estatísticas de IA): continuam como hoje, com o uso ligado ou desligado — são histórico, não uso
- O interruptor `ativo` de cada agente do catálogo e os seeders de agentes (`AssistenteSeeder`, `GuardaPromptSeeder`): continuam como hoje
- Permissões (`ViewAny:AgenteIa` e irmãs) e a matriz de papéis: não mudam; o interruptor é condição a mais, não substitui a permissão
- Registrar o catálogo "Agentes de IA" em outro painel que não o `/admin` (ver Q1)
- Apagar ou migrar dados ao desligar: nada é apagado

## Adendo 1 — 2026-10-19

- **Fonte**: pedido do solicitante no chat, durante a implementação (passo 6 já escrito)
- **Fidelidade**: alta (texto escrito)
- **Responde a**: Q2

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> adcione também no settings quando estiver off, o grupo de widget no painel Admin: "Agentes de IA" não deve ser exibido

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-09 | Com o uso de agentes desligado no settings, o grupo de widgets "Agentes de IA" do dashboard do painel de administração (o bloco de estatísticas "Agentes de IA" e o widget "Agentes por provedor", que lê o mesmo catálogo) não é exibido | "quando estiver off, o grupo de widget no painel Admin: \"Agentes de IA\" não deve ser exibido" | funcional | — |

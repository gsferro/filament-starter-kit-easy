# Requisito — Tela de configurações: aba Aplicação, seções colapsáveis com ícone e carregamento adiado das abas

## Fonte

- **Origem**: pedido colado no chat da sessão (Claude Code), na sequência do fechamento do PR #165 (v0.49.0)
- **Data**: 2026-10-15
- **Autor / solicitante**: gsferro (desenvolvedor e solicitante)
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> - foi lançado a v0.48.1, quando voltar o processo do nosso CI com tudo passando e o merge feito na main, lança a nova tag e  vira a release v0.49.0.
> - quando finalizar, quero uma melhoria na tela de settings: "/admin/configuracoes-da-aplicacao":
> 1. mudar o nome da aba "Kit" para "Aplicação"
> 2. encapsular todos os itens do form (que fazem sentido estarem juntos) em um container de section e todos devem ter a opção de colapse com icon (ate os que já estão implementados devem ganhar icon e colapse). Isso deve virar uma rule do projeto
> 3. como podemos aumentar sempre a parte da config, é melhor usar o deferLoading: "https://filamentphp.com/docs/5.x/schemas/tabs#deferring-the-loading-of-tab-content"
> - quero que seja feito depois da proxima release pois já saiu melhorias em outra sessão que devem ser incorporadas nessa proxima evolução
> - use uma nova branch 
> - use o "code --yolo" para auxiliar
> - crie uma /feature-wiki se necessário

> *(mensagem seguinte, mesma fonte, 2026-10-15)* depois da release, inicia a /feature-wiki da tela de configurações

## Decomposição em Cláusulas

<!-- Derivada e revisável. Estado: fechada · aberta — Qn · substituída por RQ-nn (Adendo N) · decomposta em RQ-nn, RQ-mm -->

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | A aba hoje chamada "Kit" da tela `/admin/configuracoes-da-aplicacao` passa a se chamar "Aplicação" | "mudar o nome da aba "Kit" para "Aplicação"" | funcional | fechada |
| RQ-02 | Todo item do formulário da tela fica dentro de um container de seção (Section), agrupado com os itens que fazem sentido estarem juntos — nenhum campo solto fora de seção | "encapsular todos os itens do form (que fazem sentido estarem juntos) em um container de section" | funcional | fechada |
| RQ-03 | Toda seção da tela é colapsável e tem ícone — inclusive as seções que já existem hoje | "todos devem ter a opção de colapse com icon (ate os que já estão implementados devem ganhar icon e colapse)" | funcional | fechada |
| RQ-04 | A convenção "seção com ícone e colapsável" vira uma rule do projeto (`.ai/rules/`) | "Isso deve virar uma rule do projeto" | restrição | fechada |
| RQ-05 | O conteúdo das abas da tela é carregado de forma adiada (deferred loading do Filament), porque a área de configuração só cresce | "como podemos aumentar sempre a parte da config, é melhor usar o deferLoading" | não-funcional | fechada |
| RQ-06 | A evolução parte do código posterior à release v0.49.0, incorporando as melhorias já publicadas por outra sessão | "quero que seja feito depois da proxima release pois já saiu melhorias em outra sessão que devem ser incorporadas nessa proxima evolução" | restrição | fechada |
| RQ-07 | O trabalho acontece numa branch nova | "use uma nova branch" | restrição | fechada |
| RQ-08 | O Codex CLI (`codex --yolo`) é usado como auxiliar no processo | "use o "code --yolo" para auxiliar" | restrição | fechada |
| RQ-09 | O processo segue a `feature-wiki` | "crie uma /feature-wiki se necessário" · "inicia a /feature-wiki da tela de configurações" | restrição | fechada |

## Perguntas ao Solicitante

<!-- Raia requisito: só o solicitante responde. Bloquear somente o recorte dependente da resposta. -->

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | Ao abrir a tela, as seções nascem **expandidas** ou **colapsadas**? E a tela lembra o estado que o usuário deixou (seção fechada continua fechada na próxima visita, neste navegador)? | RQ-03 | Nascem expandidas (nada fica escondido por padrão — é o que a tela mostra hoje) e o estado colapsado **persiste** por navegador (`persistCollapsed`), que é o que dá sentido a colapsar numa tela que só cresce. Exceção já decidida: os blocos de provedor social que hoje nascem fechados continuam fechados | aberta |
| Q2 | O agrupamento proposto dos campos em seções (tabela em `01` › *Mapeamentos* › *Seções por aba*) vale, ou algum campo muda de seção/nome? | RQ-02 | Vale como proposto — segue a ordem e os blocos que a tela já tem; a sessão só fechou os campos soltos em seções nomeadas | aberta |
| Q3 | Links e favoritos antigos com a aba Kit na URL (`?tab=…kit…`) podem passar a abrir na primeira aba, ou a tela deve continuar reconhecendo o nome antigo? | RQ-01 | Podem abrir na primeira aba — a chave da aba na URL deriva do nome; manter um apelido para "kit" é código a mais para um link interno do próprio admin | aberta |
| Q4 | "Todo item do formulário" inclui os itens que não são campo de entrada (textos explicativos e avisos, como o aviso de pacote ausente do dock) ou só os campos? | RQ-02 | Inclui — todo componente folha do formulário fica dentro de uma seção; é a leitura literal de "todos os itens", e a rule (RQ-04) fica sem exceção. Implementada a recomendação; o invariante das duas leituras (todo campo em seção) é o CT-03 | aberta (derivação do `04`, 2026-10-15) |
| Q6 | Abrir uma aba passou a ser um pedido ao servidor: quem perdeu a permissão da tela com ela aberta deve ser recusado ao abrir outra aba? | RQ-05 | Recusar — falha fechado, a mesma fronteira que a gravação já tem (CT-33 da ancestral `settings-do-kit`); o Filament já roda o `loadDeferredSchema()` dentro do componente, sob a mesma autorização da tela (`ExigePermissaoDaTela`), então a recomendação é o comportamento atual — a confirmar por CT se o solicitante quiser cenário próprio; o invariante (carregar a aba não entrega segredo) é o CT-15 | aberta (derivação do `04`, 2026-10-15) |

## Premissas

<!-- Revisável. O que a feature assume sem que o solicitante tenha escrito. -->

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | Toda menção textual à "aba Kit" nas docs (pt/en), no README e nas capturas de `art/` é atualizada para "aba Aplicação"; o CHANGELOG histórico não é reescrito | step 3 — referências levantadas por grep | 2026-10-15 | RQ-01 | vigente |
| P-02 | A aba renomeada mantém a posição (última) e o ícone de hoje; muda só o rótulo | step 4 — interpretação mínima de "mudar o nome" | 2026-10-15 | RQ-01 | vigente |
| P-03 | Carregamento adiado vale para **todas** as abas, com a aba ativa carregando ao entrar na viewport e as demais ao serem selecionadas — comportamento do Filament 5.10.2 (`Schema::deferLoading()` + `key()` em `Tabs` e em cada `Tab`); uma aba cujo campo tem erro de validação carrega sozinha para mostrar o erro | step 3 — vendor lido | 2026-10-15 | RQ-05 | vigente |
| P-04 | Salvar a tela sem nunca abrir uma aba adiada grava o que já estava nela: o estado do formulário é preenchido inteiro no `mount()`, o adiamento é só do HTML | step 3 — vendor lido; a confirmar por CT de gravação | 2026-10-15 | RQ-05 | vigente |
| P-05 | Seção colapsada continua enviando os campos no salvamento e continua sendo validada; erro de validação em campo de seção colapsada fica visível (a seção abre ou o erro aparece no topo) | step 4 — premissa de comportamento, falha fechado | 2026-10-15 | RQ-03 | vigente |

## Fora de Escopo (declarado)

- Mudar qualquer campo, valor, validação ou efeito das configurações — a feature reorganiza a tela, não o que ela governa.
- Outras Pages do kit (a rule nasce com o glob da tela de configurações; estender a outras telas é decisão da rule, não desta feature).
- Trocar o mecanismo de persistência da aba ativa (`persistTabInQueryString` continua).
- Itens 1 (release v0.49.0) do texto original: já executados antes desta wiki (PR #165, tag e release em 2026-10-15).

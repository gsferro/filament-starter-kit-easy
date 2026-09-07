# Requisito — Listagem de entidades (tenant) no `/admin`: ordem dos widgets e título capitalizado

## Fonte

- **Origem**: pedido colado no chat pelo solicitante, via `/feature-wiki`
- **Data**: 2026-09-06
- **Autor / solicitante**: gsferro (mantenedor do kit)
- **Fidelidade**: alta — texto escrito pelo solicitante, colado verbatim abaixo

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> na tela de entidades (tenant) eu pedi para colocar mais widgets, ficou otimo, porem quero um ajuste de posição.
> - no topo pode ter a visão geral, em seguida, coloque a tabela e depois coloque os demais widgets criados
> - aproveite e ajuste o title que esta minusculo. foi customizado Entidades (aparece no menu correto), mas no breadcum e no title da pagina estão minuscula
> - use sub-agente e worktree para rodar em paralelo

## Contexto de leitura (derivado, não normativo)

- "tela de entidades (tenant)" é a listagem do `TenantResource` no painel `/admin`
  (`app/Filament/Admin/Resources/Tenants/Pages/ListTenants.php`). "Entidades" é o rótulo que a
  instalação do solicitante deu à organização/tenant via Settings (`rotulo_das_organizacoes`), que
  alimenta `config('kit.tenancy.label_plural')`. No kit, o default é "Organizações".
- "eu pedi para colocar mais widgets" refere-se à wiki ancestral
  `wikis/specs/main/insights-das-organizacoes/`, que pôs quatro widgets no cabeçalho dessa listagem:
  `OrganizacoesStats` (heading "Visão geral"), `UsuariosUnicosPorOrganizacao`, `AcessosPorPainel`
  e `AtualizacoesDasOrganizacoes`.
- A última linha do texto ("use sub-agente e worktree para rodar em paralelo") é instrução de
  processo para o agente, não cláusula de produto. Não gera `RQ`.

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Na listagem de entidades, o widget de visão geral fica no topo, acima da tabela | "no topo pode ter a visão geral" | funcional |
| RQ-02 | A tabela vem imediatamente depois da visão geral — nenhum outro widget entre os dois | "em seguida, coloque a tabela" | funcional |
| RQ-03 | Os demais widgets criados (usuários únicos por organização, acessos por painel, atualizações recentes) ficam abaixo da tabela | "e depois coloque os demais widgets criados" | funcional |
| RQ-04 | O título da página da listagem exibe o rótulo plural com a capitalização em que foi configurado ("Entidades"), não em minúsculas | "ajuste o title que esta minusculo […] no title da pagina estão minuscula" | funcional (correção) |
| RQ-05 | O breadcrumb da listagem exibe o rótulo plural com a capitalização em que foi configurado, não em minúsculas | "no breadcum […] estão minuscula" | funcional (correção) |
| RQ-06 | O menu continua exibindo o rótulo como configurado — a correção não pode regredir o que já está certo | "foi customizado Entidades (aparece no menu correto)" | restrição (não-regressão) |

## Ambiguidades e Perguntas Abertas

<!-- Sem usuário disponível para responder antes de planejar: cada uma tem premissa e custo de erro. -->

- **RQ-03** — "os demais widgets criados" são exatamente os três que hoje acompanham a visão geral na
  listagem (`UsuariosUnicosPorOrganizacao`, `AcessosPorPainel`, `AtualizacoesDasOrganizacoes`)?
  - **Assumido**: sim. São os únicos widgets da listagem além da visão geral; os dois da tela de
    um registro (`OrganizacaoStats`, `OrganizacaoUltimosAcessos`) vivem em outra página e não
    são "da tela de entidades".
  - **Se negado**: o passo 1 do PRD muda a lista de `getFooterWidgets()`; o CT-01 muda a lista de
    marcadores esperados.

- **RQ-03** — a ordem relativa e a grade dos três widgets abaixo da tabela devem mudar?
  - **Assumido**: não. Mantêm a ordem atual (`$sort` 2, 3, 4) e a grade atual (usuários únicos e
    acessos lado a lado em duas colunas; atualizações em largura total).
  - **Se negado**: ajuste de `$sort`/`$columnSpan` nos widgets; CT-01 ganha a nova ordem.

- **RQ-04** — "title da página" é o `<h1>` do cabeçalho, o `<title>` da aba, ou ambos?
  - **Assumido**: ambos — os dois saem do mesmo `getTitle()` da página (medido em
    `vendor/filament/filament/resources/views/components/layout/base.blade.php:getTitle():30` e no cabeçalho
    da página). Corrigir um corrige o outro; o CT-02 afirma sobre os dois.
  - **Se negado**: nada muda no código; só a asserção do CT-02 encolhe.

- **RQ-04 / RQ-05** — "capitalizado" significa **como foi configurado** ("Entidades"; e "Unidades de
  Negócio" fica "Unidades de Negócio") ou Title Case forçado ("Unidades De Negócio")?
  - **Assumido**: como configurado. O kit desligou o Title Case do Filament de propósito por ser
    regra do inglês (`app/Providers/Concerns/ConfiguraFilamentGlobal.php:titleCaseModelLabel():79`),
    e o menu — que o solicitante diz estar correto — já exibe o rótulo como configurado.
  - **Se negado**: a decisão ADR-01 muda para `Str::ucwords` no título/breadcrumb; o CT-02 e o
    CT-03 invertem a linha "Unidades de Negócio".

- **Consequência da correção** — hoje a tela de criação chama-se "Criar entidade" (rótulo
  singular minúsculo no meio da frase). Com a causa-raiz removida ela passa a "Criar Entidade",
  que é o padrão de todos os outros Resources do kit ("Criar Usuário", "Criar Convite",
  "Criar Agente de IA" — todos declaram `$modelLabel` capitalizado). O requisito não fala da
  tela de criação.
  - **Assumido**: aceitar "Criar Entidade" — consistência com os irmãos vence a gramática do meio
    da frase, e é a alternativa de menor código (ver ADR-01).
  - **Se negado**: ADR-01 troca para a alternativa 2 (sobrescrever só `getTitle()` da listagem e
    `getBreadcrumb()` do Resource, mantendo o rótulo minúsculo no `getModelLabel()`); +6 linhas.

- **RQ-04** — e se o rótulo for configurado em minúsculas pela própria instalação ("entidades")?
  - **Assumido**: exibe-se como configurado. O kit não corrige o que a pessoa escreveu — o mesmo
    espírito da decisão que desligou o Title Case.
  - **Se negado**: acrescentar `Str::ucfirst` no título e no breadcrumb; uma linha em cada.

## Fora de Escopo (declarado)

- Os dois widgets da tela de um registro (`ViewTenant`: `OrganizacaoStats`,
  `OrganizacaoUltimosAcessos`) — o requisito fala da "tela de entidades" onde os widgets novos
  foram postos junto da tabela, e só a listagem tem tabela.
- Conteúdo, métricas e janela dos widgets — nada muda no que eles mostram.
- O mecanismo Settings → `config('kit.tenancy.*')` que faz "Entidades" chegar ao Resource — já
  coberto por `tests/Kit/ConfiguracoesDoKitTest.php`.
- Rótulos minúsculos usados de propósito em frases de outras telas
  (`app/Console/Commands/KitTenancy.php:mb_strtolower():255`, `app/Notifications/ConviteDeAcesso.php:mb_strtolower():72`,
  `app/Filament/Concerns/ConvidaEmMassa.php:mb_strtolower():34`, entre outros): cada um aplica o próprio
  `mb_strtolower()` ao `config()` e não passa pelo `TenantResource`.
- "use sub-agente e worktree para rodar em paralelo" — instrução de processo, cumprida pela forma
  de execução desta wiki (worktree isolada, branch própria), não pelo produto.

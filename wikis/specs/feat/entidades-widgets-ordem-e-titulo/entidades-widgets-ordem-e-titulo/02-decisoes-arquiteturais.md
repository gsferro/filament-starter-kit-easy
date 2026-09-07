# Decisões Arquiteturais — Listagem de entidades: ordem dos widgets e título

## ADR-01: O rótulo minúsculo sai do `TenantResource`; título e breadcrumb não são sobrescritos

**Status**: Aceita
**Data**: 2026-09-06

### Contexto

O título da listagem e o primeiro item do breadcrumb de todas as páginas do `TenantResource` saem de `getTitleCasePluralModelLabel()` (`vendor/filament/filament/src/Resources/Pages/ListRecords.php:getTitle():76-79`; `vendor/filament/filament/src/Resources/Resource/Concerns/HasBreadcrumbs.php:getBreadcrumb():9-12`). Com o Title Case desligado — `Resource::titleCaseModelLabel(false)` em `app/Providers/Concerns/ConfiguraFilamentGlobal.php:titleCaseModelLabel():79` — esse método devolve `getPluralModelLabel()` sem tocar (`vendor/filament/filament/src/Resources/Resource/Concerns/HasLabels.php:getTitleCasePluralModelLabel():78-85`). E `TenantResource::getPluralModelLabel():73-76` aplica `mb_strtolower()` ao rótulo configurado.

Cronologia, pelo `git log -S`: o `mb_strtolower()` é de `681be6d` (2026-08-13), quando o Title Case ainda estava ligado e `Str::ucwords()` recapitalizava título e breadcrumb; o desligamento global é de `d5cf820` (2026-08-15). Desde então toda instalação exibe "organizações" no `<h1>`, no `<title>` e no breadcrumb, e ninguém notou porque o menu — `getNavigationLabel():78-81`, que lê o `config()` cru — continuou certo, e nenhum teste assere título ou breadcrumb.

Os outros doze Resources do kit declaram `$modelLabel`/`$pluralModelLabel` capitalizados ("Usuário"/"Usuários", "Convite"/"Convites", "Agente de IA"/"Agentes de IA"…) e por isso mostram "Usuários", "Criar Usuário", "Usuários › Listar". O `TenantResource` é o único que devolve minúsculo.

### Decisão

Remover o `mb_strtolower()` de `getModelLabel()` e de `getPluralModelLabel()`. Os dois passam a devolver o rótulo **como configurado**. Nada é sobrescrito na página nem no Resource além disso; `getNavigationLabel()` fica como está.

### Alternativas Consideradas

1. **Sobrescrever `getTitle()` em `ListTenants` e `getBreadcrumb()` em `TenantResource`** com `Str::ucfirst(config(...))`, mantendo o rótulo minúsculo — descartada: trata dois sintomas e deixa a causa; são +6 linhas e dois pontos de manutenção contra −2 linhas; e mantém o `TenantResource` como o único Resource do kit com rótulo minúsculo, o que é o que produziu o defeito quando o contexto (Title Case) mudou. A única coisa que ela preserva é "Criar entidade" no título da tela de criação — que é inconsistente com "Criar Usuário", "Criar Convite" dos irmãos. Fica como plano B se o solicitante negar a premissa correspondente do `00`.
2. **Religar o Title Case só para o `TenantResource`** (redeclarar `protected static bool $hasTitleCaseModelLabel = true;` na classe) — descartada: `Str::ucwords()` capitaliza preposição ("Unidades **De** Negócio"), exatamente o motivo pelo qual o kit o desligou. Reintroduz o defeito de `d5cf820` numa classe.
3. **`Str::ucfirst()` dentro de `getPluralModelLabel()`** — descartada: corrige um rótulo que a pessoa escreveu em minúsculas, que não é papel do kit (o Title Case foi desligado com esse argumento), e não muda nada para "Entidades", que já vem capitalizado.
4. **`$breadcrumb` / `$title` estáticos** — descartada: propriedade estática é avaliada antes da config existir; é a razão de os rótulos serem métodos (docblock do próprio Resource, `:32-38`).

### Consequências

- **Positivas**: −2 linhas; título, `<title>` da aba, breadcrumb das quatro páginas e categoria da busca global nativa passam a exibir o rótulo como configurado, de uma vez; o `TenantResource` fica igual aos irmãos.
- **Negativas**: a tela de criação passa de "Criar entidade" a "Criar Entidade" (mid-sentence capitalizado). Aceito por consistência com os doze irmãos; registrado em `00` → Ambiguidades com o plano B.
- **Riscos**: o único consumidor no app é genérico — `app/Filament/Spotlight/AcoesDeCriacao.php:$rotulo:70` monta "Criar {rótulo}" com `ucfirst($resource::getModelLabel())` para todo Resource, e o texto fica igual ou melhor; nenhum teste consome os dois métodos do `TenantResource` *(alterado em 2026-09-07: dizia "varredura: zero" — QA-01 do `06`)* — os textos em frase de outras telas aplicam o próprio `mb_strtolower()` ao `config()` e não passam pelo Resource. Mitigação: CT-02 cobre título e menu, CT-03 o breadcrumb; `composer test:kit` cobre o resto.

### Referências

- `app/Filament/Admin/Resources/Tenants/TenantResource.php:getModelLabel():77-80`, `getPluralModelLabel():82-85`, `getNavigationLabel():87-90` *(linhas do código já corrigido; antes da entrega eram 68-71, 73-76 e 78-81)*
- `app/Providers/Concerns/ConfiguraFilamentGlobal.php:titleCaseModelLabel():79`
- `vendor/filament/filament/src/Resources/Resource/Concerns/HasLabels.php:getTitleCasePluralModelLabel():78-85`
- `vendor/filament/filament/src/Resources/Pages/ListRecords.php:getTitle():76-79`
- `vendor/filament/filament/src/Resources/Resource/Concerns/HasBreadcrumbs.php:getBreadcrumb():9-12`
- `vendor/filament/filament/src/Resources/Pages/Page.php:getBreadcrumb():186`
- `vendor/filament/filament/src/Resources/Pages/CreateRecord.php:getTitle():302-311`
- `vendor/filament/filament/resources/views/components/layout/base.blade.php:getTitle():30`
- Doc Filament 5 (search-docs): *Resources → Overview → Automatic model label capitalization* — `$hasTitleCaseModelLabel` afeta "page titles, the navigation menu, and the breadcrumbs"

---

## ADR-02: Os três widgets de detalhe vão para `getFooterWidgets()`; nenhuma grade é declarada

**Status**: Aceita
**Data**: 2026-09-06

### Contexto

`ListTenants::getHeaderWidgets():54-62` declara os quatro widgets, e o template de página do Filament renderiza cabeçalho, conteúdo e rodapé nesta ordem fixa: `{{ $this->headerWidgets }}` (`vendor/filament/filament/resources/views/components/page/index.blade.php:headerWidgets:105`), `{{ $slot }}` (`:109`, a tabela), `{{ $this->footerWidgets }}` (`:113`). O pedido é "visão geral, tabela, demais widgets".

### Decisão

`getHeaderWidgets()` devolve só `OrganizacoesStats::class`; um `getFooterWidgets()` novo (`vendor/filament/filament/src/Pages/Page.php:getFooterWidgets():314-317`) devolve os três restantes na ordem atual. O `getHeaderWidgetsColumns()` de `ListTenants` é **removido** — devolve 2, que já é o default (`vendor/filament/filament/src/Pages/Page.php:getHeaderWidgetsColumns():306-309`); pelo mesmo motivo não se declara `getFooterWidgetsColumns()` (`:356-359`). `$sort` e `$columnSpan` dos widgets não mudam: a grade de duas colunas do rodapé reproduz o par lado a lado e a timeline em largura total.

### Alternativas Consideradas

1. **Render hook `RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER`** com uma view que monta os três widgets — descartada: reimplementa a grade e o filtro `canView()` que `Page::getWidgetsSchemaComponents()` (`vendor/filament/filament/src/Pages/Page.php:getWidgetsSchemaComponents():423`) já faz para o rodapé; é a API que existe para outra coisa.
2. **View custom da página** (`$view`) — descartada: copia o template inteiro para trocar a ordem de três linhas que o Filament já expõe como dois métodos.
3. **Manter `getHeaderWidgetsColumns()`** "para deixar explícito" — descartada: repete o default; é linha que parece configurar e não configura nada. Deletar é o degrau 6 do Ponytail.

### Consequências

- **Positivas**: usa a API que a doc do Filament 5 descreve para exatamente este caso (search-docs: "`getHeaderWidgets()` … above the page content, whereas `getFooterWidgets()` are displayed below"); `canView()` continua filtrando as duas listas, então o cenário CT-16 da ancestral (coluna `painel` ausente esconde só `AcessosPorPainel`) segue válido.
- **Negativas**: o `[CT-12]` da ancestral, que afirma quatro widgets no cabeçalho, quebra — é atualizado na mesma entrega (passo 3 do PRD), com a marca de alteração no `04` de lá.
- **Riscos**: nenhum de comportamento. O de teste está na ADR-03.

### Referências

- `app/Filament/Admin/Resources/Tenants/Pages/ListTenants.php:getHeaderWidgets():58-63`, `getFooterWidgets():68-74` *(linhas do código já corrigido; antes da entrega o cabeçalho era 54-62 e havia um `getHeaderWidgetsColumns():64-67`, removido)*
- `vendor/filament/filament/src/Pages/Page.php:getHeaderWidgetsColumns():306-309`, `getFooterWidgets():314-317`, `getFooterWidgetsColumns():356-359`, `getWidgetsSchemaComponents():423`
- `vendor/filament/filament/resources/views/components/page/index.blade.php:headerWidgets:105, slot:109, footerWidgets:113`
- ADR-03 de `wikis/specs/main/insights-das-organizacoes/02-decisoes-arquiteturais.md` (por que os widgets vivem em `Resources/Tenants/Widgets/`) — não muda

---

## ADR-03: A ordem visual é provada no HTML do componente, não no navegador

**Status**: Aceita
**Data**: 2026-09-06

### Contexto

RQ-01 a RQ-03 são sobre posição na tela. A regra da `feature-test-design` é a camada mais barata que prova; a rule `.ai/rules/testes-browser.md` reserva o navegador para o que só ele prova (JS, tema, geometria). O template do Filament é linear (ADR-02), então "acima" e "abaixo" são ordem no HTML. Mas os widgets do Filament são **lazy** por default (`vendor/filament/support/src/Concerns/CanBeLazy.php:isLazy:9`, `$isLazy = true`): no HTML inicial cada um é um placeholder do Livewire, sem heading — `assertSeeHtmlInOrder(['Visão geral', …])` não encontraria o texto.

### Decisão

Um cenário de componente (`Livewire::test(ListTenants::class)`) com `assertSeeHtmlInOrder()` (`vendor/livewire/livewire/src/Features/SupportTesting/MakesAssertions.php:assertSeeHtmlInOrder():59`) sobre marcadores que existem no HTML inicial mesmo com lazy: o **nome Livewire de cada widget** — presente no atributo `wire:snapshot` que o Livewire injeta na raiz de todo componente montado (`vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php:snapshot:76`), placeholder incluído — e a classe `fi-ta-ctn` do contêiner da tabela (`vendor/filament/tables/resources/views/index.blade.php:'fi-ta-ctn':235`), que é renderizada mesmo com `deferLoading`. *(alterado em 2026-09-07 na implementação: `Component::getName()` (`vendor/livewire/livewire/src/Component.php:getName():70-73`) devolve `$this->__name`, que só é preenchido no MOUNT — em instância criada por `app()` ele é `null`. Medido no HTML inicial da listagem: no Livewire 4 o nome do componente É o FQCN, e o snapshot é JSON, então a contrabarra chega ao HTML dobrada. O marcador passa a ser `str_replace(chr(92), chr(92).chr(92), Widget::class)` — continua derivado de `::class`, sem string mágica.)*

Complementa, não substitui, o `[CT-12]` da ancestral (as duas listas por closure): o CT-12 mata "widget esquecido"; o cenário HTML mata "método declarado com nome errado" (`getFooterWidget()`, que o Filament nunca chama e o CT-12 por closure ainda encontraria) e prova que o vendor de fato renderiza rodapé depois da tabela.

### Alternativas Consideradas

1. **CT-B** com `visit('/admin/organizacoes')` e geometria via `script()` (`getBoundingClientRect`) — descartada: prova o mesmo que a ordem no HTML a um custo de dezenas de segundos por cenário, e a rule só admite browser para o que **só** ele prova. Ordem de blocos empilhados num template linear não é isso.
2. **Só as listas por closure** (CT-12 atualizado) — descartada como oráculo único: não distingue `getFooterWidgets()` de um método com outro nome, nem prova a ordem de renderização; mas fica como oráculo estrutural.
3. **Desligar o lazy nos quatro widgets** para o heading aparecer no HTML — descartada: muda comportamento de produção por causa do teste.

### Consequências

- **Positivas**: milissegundos, sem Node; o cenário fica vermelho tanto com os quatro no cabeçalho quanto com a visão geral no rodapé.
- **Negativas**: depende de o nome do componente aparecer literal dentro do `wire:snapshot` HTML-escapado. Foi verificado por leitura do vendor, não por execução — a worktree não tem `vendor/`.
- **Riscos**: se na implementação o marcador não aparecer literal, cair para a alternativa 2 e registrar em `03` → Notas de Implementação, deixando a ordem de renderização provada pela citação do template (`page/index.blade.php:headerWidgets:105, slot:109, footerWidgets:113`). Nunca "consertar" trocando para `assertSee` de texto que não existe no HTML inicial.

### Referências

- `vendor/filament/support/src/Concerns/CanBeLazy.php:isLazy:9`
- `vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php:snapshot:76`
- `vendor/livewire/livewire/src/Component.php:getName():70-73`
- `vendor/livewire/livewire/src/Features/SupportTesting/MakesAssertions.php:assertSeeHtmlInOrder():59`
- `vendor/filament/tables/resources/views/index.blade.php:'fi-ta-ctn':235`
- `.ai/rules/testes-browser.md` → "`assertVisible` não prova posição — para layout, meça geometria via `script()`" (aplicável quando o defeito é de CSS; aqui é de template)

# Plano de Ação — Estudo de viabilidade: Advanced Tables e alternativas

> Requisito: `00-requisito.md`
>
> **Este arquivo é o plano de UMA eventual implementação, com estimativa de custo. Nada aqui foi
> implementado (RQ-05).** A decisão de qual nível executar — ou nenhum — está no ADR-06 de
> `02-decisoes-arquiteturais.md`.

## Natureza da Wiki

- **Tipo**: estudo (sem código; não roda quality gate)
- **Wiki ancestral**: nenhuma. Relacionadas: `wikis/specs/feat/settings-do-kit/` (ADR-09 — defaults de tabela configuráveis) e `wikis/pacotes-candidatos.md` (linha "Table Presets / DB Table State")
- **Motivo**: o solicitante pediu análise do pacote pago e custo de reproduzir o que ele faz
- **Toca infra compartilhada?**: não nesta entrega. Os níveis (b) e (c) tocariam `PapeisSeeder`, `config/filament-shield.php` e `tests/Kit/PermissoesDeAcoesTest.php` — está marcado em cada passo

## Cobertura do Requisito

| RQ | Cláusula | Onde é atendida | Observação |
|----|----------|-----------------|------------|
| RQ-01 | Analisar o pacote pago | seção "O que o Advanced Tables entrega" | — |
| RQ-02 | Varrer o diretório por alternativas gratuitas | seção "Alternativas gratuitas encontradas" | limitação do método registrada no `00` e no `03` |
| RQ-03 | Como o kit implementaria as mesmas funções | seções "O que o kit já tem" e "Estrutura de Implementação" (níveis a, b, c) | — |
| RQ-04 | Foco em "botões de filtros específicos" | nível (a), passos 1–2; ADR-02 | mecanismo nativo confirmado no vendor |
| RQ-05 | Não implementar nada | todo o arquivo é plano; `03-progresso.md` marca estudo | — |
| RQ-06 | Custo de implementar / criar pacote free | seção "Estimativa de custo" e ADR-06 | em dias de dev, com premissas explícitas |
| RQ-07 | Sub-agentes e worktree | `03-progresso.md` → Método | dois sub-agentes de pesquisa web; worktree `feat/estudo-advanced-tables` |

## Objetivo

Responder três perguntas com evidência: o que exatamente o Advanced Tables (Kenneth Sese,
`archilex/filament-filter-sets`, €79 por projeto) entrega; quanto disso o Filament 5 nativo e o kit
já cobrem; e quanto custaria fechar o que falta — em três níveis, do mais barato ao mais caro — para
que o mantenedor decida entre comprar, adotar um gratuito, implementar no kit ou publicar um pacote.

## Contexto

O kit já configura toda tabela globalmente em `App\Providers\Concerns\ConfiguraFilamentGlobal::configuraTable()`:
persistência de filtros, busca, ordenação e buscas por coluna na sessão (`config('kit.tabelas.persistir_filtros')`),
colunas reordenáveis pelo gerenciador nativo, gerenciador em modal, filtros em modal de duas colunas,
`deferFilters()`, e colunas arrastáveis/redimensionáveis via `asmit/resized-column`. O que **não** existe hoje em
nenhuma listagem do kit é um botão ou aba que aplique um recorte pré-definido com um clique — e é isso que
o solicitante chamou de "botões de filtros específicos". Todas as listagens (`grep -rn 'getTabs' app/Filament`
não devolve nenhuma `ListRecords`) dependem do modal de filtros.

## O que o Advanced Tables entrega (RQ-01)

Fonte: sub-agente de pesquisa; docs em `docs.advancedtables.com/v5/*` e página do plugin em
`filamentphp.com/plugins/kenneth-sese-advanced-tables`. Pacote Composer `archilex/filament-filter-sets`,
namespace `Archilex\AdvancedTables`, repositório privado com auth http-basic.

| Funcionalidade | O que faz | Nativo no Filament 5? | Kit já tem? |
|---|---|---|---|
| Preset Views | views definidas em código (`getPresetViews()`), com `modifyQueryUsing()`, `defaultFilters()`, `defaultSort()`, `defaultColumns()`, `defaultSearch()`, `defaultGrouping()`, ícone, cor, badge, `favorite()`, `default()`, `visible()` | **parcial** — `getTabs()` + `Tab::modifyQueryUsing()`/`badge()`/`icon()` cobrem query, badge e ícone; não cobre "aplicar filtros/colunas/sort junto" | não usa |
| Quick Filters | indicadores de filtro viram botões inline; `SelectFilter::favorite()` fixa o indicador na barra mesmo inativo | **não** — indicadores nativos só aparecem com filtro ativo e só removem | não |
| User Views | usuário salva filtros + busca + buscas por coluna + sort + colunas visíveis + ordem das colunas + agrupamento numa view nomeada, com ícone e cor | **não** | não |
| Compartilhamento | pessoal / pública / favorita global, com aprovação (`Status::Approved/Pending/Rejected`) e Resource de administração | não | não |
| Favorites Bar | barra acima da tabela com favoritos, Quick Save e View Manager; 6 temas | não (as abas nativas são o análogo visual) | não |
| Quick Save | salvar a view atual em um clique | não | não |
| View Manager | dropdown/slide-over para aplicar, definir padrão, favoritar, editar, excluir, reordenar | não | não |
| Managed Default Views | usuário escolhe qual view carrega ao abrir a tela | não | não |
| Persistência da view ativa na sessão | `persistActiveViewInSession()` | **sim, para o estado bruto**: `persistFiltersInSession()` e irmãos (`vendor/filament/tables/src/Table/Concerns/HasFilters.php:164`, `CanSearchRecords.php:38,45`, `CanSortRecords.php:31`, `HasColumnManager.php:90`) | **sim**, global e configurável em `/admin/configuracoes-do-kit` |
| Multi-sort | ordenar por várias colunas com prioridade | não | não |
| Advanced Search | busca com operadores (`=`, `^`, `$`, `-`) e grupos AND/OR | não | não |
| Advanced Filter Builder | `AdvancedFilter::make()->includeColumns()` gera filtros por coluna com operadores e grupos OR | **sim** — `Filament\Tables\Filters\QueryBuilder` com constraints (doc `03-filters/04-query-builder.md`) | não usa |
| Reorderable Columns | arrastar colunas | **sim** — `reorderableColumns()` (`HasColumnManager.php:55`); o autor diz ter portado para o core | **sim**, global, mais o drag do `asmit/resized-column` |
| Tenancy | `tenant_id` nas views, comando `advanced-tables:add-tenancy` | — | o kit tem `BelongsToTenant` e o padrão de `tenant_id` |
| Autorização | `UserViewPolicy` com `makePublic`, `makeFavorite`, `makeGlobalFavorite`, `selectIcon`, `selectColor` | — | o kit tem Shield + `custom_permissions` |

Preço e licença: **€79** (Single — 1 domínio, 5 devs, 1 ano de updates); Unlimited e Lifetime existem mas
o preço não está público. Nenhuma licença permite redistribuir o código — o que já **descarta** usar o
pacote como dependência do kit: quem cria um projeto a partir do kit não teria a licença.

Compatibilidade: v5.x do plugin → Filament 5 (primeiro release 17/01/2026); requer PHP 8.2+, Laravel
11.28+, MySQL 5.7.8+/Postgres/SQL Server 2017+ e **tema Filament customizado** (importa
`vendor/archilex/filament-filter-sets/resources/css/plugin.css`). Último release v5.5.0 em 25/08/2026;
cadência quase semanal.

Modelo de dados: tabelas `filament_filter_sets` (views) e `filament_filter_set_user` (pivot favoritos/ordem);
colunas exatas não são públicas. Trait `HasViews` no `User`; models `UserView`, `ManagedUserView`,
`ManagedPresetView`.

## Alternativas gratuitas encontradas (RQ-02)

Varredura em 26/08/2026 por sub-agente: `filamentphp.com/plugins` (termos table, filter, view, column,
preset, saved), busca web complementar e Packagist. Constraints lidas no `composer.json` publicado
(Packagist espelha o arquivo; para ymsoft, ableaura, wotz, kisame76, asmit, zvizvi e bostos o raw do GitHub
foi conferido direto). Stars e último push via API do GitHub na mesma data.

| Pacote | Link | O que cobre | Filament 5? | Laravel 13? | Último release | Stars | Estado |
|---|---|---|---|---|---|---|---|
| `ymsoft/filament-table-presets` | github.com/yarmat/filament-table-presets | views salvas por usuário (filtros + sort + busca + colunas visíveis + ordem), preset padrão auto-aplicado, público/privado, troca via header action; sem barra de favoritos, sem quick filters | sim (`^5.0`, só v5) | sim (`^11.28\|\|^12\|\|^13`) | 1.0.1 — 25/03/2026 | 17 | ativo, mantenedor único, nada desde mar/2026 |
| `kisame76/filament-db-table-state` | github.com/Kisame76/filament-db-table-state | espelha em banco, por usuário, o que o `persist*InSession()` grava (filtros/sort/busca/colunas); sem views nomeadas | sim (`^4.0\|^5.0`) | sim | v1.0.2 — 17/06/2026 | 2 | ativo, criado jun/2026 |
| `kingmaker/filament-filter-sets` | github.com/kingmaker-agm/filament-filter-sets | presets de filtro definidos em código (`FilterSet::make()`), aplicados por dropdown na toolbar; substitui (não mescla) os filtros atuais; não salva nem persiste | sim (`filament/tables ^4.0\|^5.0`) | sim | 1.1.0 — 30/07/2026 | 1 | ativo, um release |
| `wotz/filament-table-filter-presets` | github.com/wotzebra/filament-table-filter-presets | presets de **filtro** por usuário e resource em JSON; Save/Load/Delete como header actions; README não fala em sort/colunas/sharing | sim (`^5.0`) | sim | v0.5.0 — 17/04/2026 | 0 | pré-1.0; PHP `^8.3`; depende de `wotz/laravel-locale-collection` |
| `asmit/resized-column` | github.com/AsmitNepali/resized-column | arrastar, redimensionar e fixar colunas; persistência por usuário em sessão ou banco (`preserveOnDB()`) | sim (`filament/support ^3.2\|^4.0\|^5.0`) | não declara | v4.0.2 — 31/07/2026 | 55 | ativo — **já é dependência do kit** |
| `zvizvi/filament-column-filters` | github.com/zvizvi/filament-column-filters | filtros estilo Excel no cabeçalho da coluna; UX de filtro, não view | sim (`^5.0`) | não declara | 0.0.6 — 03/08/2026 | 8 | pré-1.0, criado jul/2026 |
| `shkubu18/filament-widget-tabs` | github.com/shkubu18/filament-widget-tabs | abas como widgets com contador que filtram a tabela | sim (v2.0.0: `^4.0\|^5.0`; `dev-main` ainda `^3.0`) | não declara | v2.0.0 — 17/04/2026 | 25 | ativo; exige tema custom |
| `ptplugins/filament-auto-filters` | github.com/ptplugins/filament-auto-filters | gera filtros a partir das colunas; fora do escopo | sim | **não** (`illuminate/support ^10\|\|^11\|\|^12`) | 1.4.1 — 20/08/2026 | 4 | ativo |
| `ableaura/filament-advanced-tables` | github.com/AbleAura/filament-advanced-tables | anuncia a lista inteira do pago (user views, preset views, favorites bar, quick filters, multi-sort, advanced search) | **não** (`^3.0\|\|^4.0`) | **não** (`^10\|\|^11`) | v1.0.2 — 16/03/2026 | 0 | 4 commits no mesmo dia; texto copiado do marketing do pago; **suspeito de derivação do proprietário** |
| `kozsuper/filament-table-views` | github.com/KozSuper/Filament-Table-Views | extração do AureusERP: preset views, saved views, favoritos, público/privado | **não** (`^3.0`) | **não** | 1.0.1 — 04/04/2025 | 2 | abandonado (sem push desde abr/2025); exige `guava/filament-icon-picker ^2.0` |
| `bostos/reorderable-columns` | github.com/Bostos/reorderable-columns | reordenar colunas com persistência | **não** (`^3.0`) | não declara | v1.1.0 — 16/06/2025 | 12 | 6 issues abertas |
| `guiu/filament-filter-presets` | github.com/gerardguiu/filament-filter-presets | presets de filtro por usuário, default, compartilhar com time | **não** (`^3.0`) | **não** (`^10\|^11`) | v1.0.1 — 01/07/2025 | 8 | parado |
| `webbingbrasil/filament-advancedfilter` | github.com/webbingbrasil/filament-advancedfilter | filtros com cláusulas; não é view | **não** (`^3.0.0`) | não declara | v3.0.1 — 02/01/2024 | 148 | sem release há 2,5 anos |
| `tima/filament-column-order` | github.com/Timur0021/filament-column-order | widget para salvar ordem de colunas | **não** (`^3.3`) | `>=11.0` | 1.0.10 — 22/09/2025 | 0 | parado |

Nomes verificados e **inexistentes** (404): `pxlrbt/filament-table-layout`, `hugomyb/filament-table-views`,
`tomatophp/filament-table-views`, `dutchcodingcompany/filament-table-views`, `eightynine/filament-table-views`,
`solution-forest/filament-table-views`, `ibrahim-bougaoua/filament-saved-filters`, `zvizvi/filament-table-views`.
Não há fork open source do `archilex/filament-filter-sets` (o repositório é privado; só as docs são públicas).

**Leitura**: os únicos candidatos que rodam em Filament 5 + Laravel 13 e fazem "view salva por usuário" são
`ymsoft/filament-table-presets` (17 stars) e `wotz/filament-table-filter-presets` (0 stars). O único que faz
"botão de filtro pré-definido" é `kingmaker/filament-filter-sets` (1 star) — e o `getTabs()` nativo faz o mesmo
como aba, sem dependência. Nenhum gratuito compatível tem barra de favoritos nem quick filters fixáveis. Todos os
compatíveis têm mantenedor único e menos de 20 stars: o risco dominante é manutenção, não licença.

## O que o kit já tem que cobre parte disso (RQ-03)

Confirmado no código, com `arquivo:linha`:

| Capacidade | Onde | Observação |
|---|---|---|
| Filtros, busca, sort e buscas por coluna sobrevivem à navegação | `app/Providers/Concerns/ConfiguraFilamentGlobal.php` → `configuraTable()`: `persistFiltersInSession/Search/Sort/ColumnSearches($persistir)` | ligado por `config('kit.tabelas.persistir_filtros')`, editável em `/admin/configuracoes-do-kit` |
| Colunas visíveis e ordem persistem na sessão | default do Filament 5 — `protected bool | Closure $persistsColumnsInSession = true` (`vendor/filament/tables/src/Table/Concerns/HasColumnManager.php:39`) + `reorderableColumns()` no `configuraTable()` | vale para as tabelas dos plugins também |
| Largura das colunas persiste | `asmit/resized-column` com `->preserveOnSession()` nos três PanelProviders (`AdminPanelProvider.php:238-239`) | o pacote também tem `preserveOnDB()` e o model `Asmit\ResizedColumn\Models\TableSetting` (`user_id`, `resource`, `styles`) — **já existe um "estado de tabela por usuário em banco" no vendor**, só que restrito a largura |
| Filtros são endereçáveis por URL | `vendor/filament/filament/src/Resources/Pages/ListRecords.php:39-55`: `#[Url(as: 'filters')] $tableFilters`, `#[Url(as: 'search')]`, `#[Url(as: 'sort')]`, `#[Url(as: 'tab')] $activeTab` | é o mecanismo do nível (a): um link com `?filters[pendente][value]=1` abre a tela já filtrada |
| URL vence a sessão | `vendor/filament/tables/src/Concerns/InteractsWithTable.php:64-73`: a sessão só é lida quando `$tableFilters === null` | garante que o botão sempre aplica o recorte dele, mesmo com `persistir_filtros` ligado |
| URL funciona com `deferFilters()` | `InteractsWithTable.php:81-85`: o form é preenchido e, com filtros diferidos, `tableFilters` recebe `tableDeferredFilters` | sem isso o botão preencheria o modal mas não filtraria |
| Abas que restringem a query | `vendor/filament/filament/src/Resources/Concerns/HasTabs.php:31-84`: `getTabs()`, `getDefaultActiveTab()`, `modifyQueryWithActiveTab()`; `Tab::modifyQueryUsing()` em `vendor/filament/schemas/src/Components/Tabs/Tab.php:93` | trait já está em toda `ListRecords`; nenhuma listagem do kit sobrescreve `getTabs()` |
| Filtro com valor inicial | `vendor/filament/tables/src/Filters/Concerns/HasDefaultState.php:9` `default()` | "abrir a tela já filtrada por padrão" custa uma chamada |
| Filtro como toggle em vez de checkbox | `vendor/filament/tables/src/Filters/Filter.php:32` `toggle()` | cosmético; não vira botão fora do modal |
| Query builder avançado | `Filament\Tables\Filters\QueryBuilder` (pacote `filament/query-builder` está em `vendor/filament/`) | cobre o "Advanced Filter Builder" do pago sem código |
| Permissão por Action | `->authorize()` obrigatório (`.ai/rules/filament.md`, "Page, Widget e Action novos nascem com a permissão consultada") e inventário em `tests/Kit/PermissoesDeAcoesTest.php` | qualquer Action "Salvar visão" entra no inventário |

## Análise dos Arquivos Existentes

### `app/Providers/Concerns/ConfiguraFilamentGlobal.php`

- `configuraTable()` é o único lugar onde os níveis (a)/(b) **não** mexem: abas e views são por listagem, não globais. O que se aproveita é o comentário sobre `filtersTriggerAction()` (linha "Sem `filtersTriggerAction()`..."): qualquer Action registrada num `configureUsing()` global atinge tabelas sem filtro e derruba oito telas do `/infra`. Logo, **a barra de views do nível (b) não pode ser injetada por `Table::configureUsing()`** — entra por trait na `ListRecords`, como o `HasResizableColumn` do asmit.

### `app/Filament/**/Pages/List*.php` (10 arquivos)

- Todas seguem o mesmo esqueleto (`ListUsers` do painel `app`: `use HasResizableColumn`, `getHeaderActions()` com `CreateAction`). O nível (a) acrescenta `getTabs()` em quatro delas; o nível (b) acrescenta um `use TemVisoesSalvas` em todas as que quiserem.

### `app/Filament/Concerns/AprovacaoDeCadastro.php:90-95`

- `filtroDePendentes()` é o `Filter::make('aprovacao_pendente')` já compartilhado entre os dois `UserResource`. É o candidato óbvio ao primeiro "botão de filtro específico": aba "Pendentes de aprovação" com badge de contagem.

### `app/Filament/Admin/Resources/Convites/Tables/ConvitesTable.php:59-66`

- `TernaryFilter::make('pendente')` com `queries(true/false/blank)`. Vira três abas (Todos / Pendentes / Aceitos) sem escrever query nova — cada aba reaproveita a closure.

### `app/Filament/Infra/Resources/AiRuns/Tables/AiRunsTable.php:48-62`

- Três `SelectFilter` (`status`, `task`, `driver`). Ficam no modal: aba por status duplicaria o `SelectFilter` para um usuário de infra (passo 2).

## Autorização

- **Nível (a)**: nenhuma nova. Abas restringem a query; a policy do Resource continua mandando (`ViewAny:{Model}`).
- **Nível (b)**: policy `VisaoDeTabelaPolicy` (`viewAny`, `create`, `update`, `delete` — só o dono; `master_global` pelo `Gate::before`). As Actions "Salvar visão", "Excluir visão" declaram `->authorize()`. Permissões `Create:VisaoDeTabela` etc. entram na matriz de `database/seeders/PapeisSeeder.php` para **todos** os papéis de painel (é feature de usuário comum, não de administração) — sem isso o `panel_user` não salva nada e nenhum erro aparece.
- **Nível (c)**: a policy vira publicável (`--tag=...-policy`), como o pago faz.

## Rotas

Nenhuma nova. Tudo vive na rota `index` de cada Resource; o estado viaja por query string (`?tab=`, `?filters[...]`, `?search=`, `?sort=`) já registrada pelo `ListRecords`.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Abas acima da tabela (nível a) | Filament `Tabs` nativo | `/admin/users`, `/app/{tenant}/users`, `/admin/convites`, `/infra/ai-runs` | clica na aba; a tabela filtra e a URL ganha `?tab=` | Não (Livewire) |
| Botões "Salvar visão" / lista de visões (nível b) | Filament `Action` + `ActionGroup` em `getHeaderActions()` | mesmas rotas | salva o estado atual com nome; aplica uma visão salva | Não |
| Barra de favoritos (nível c) | Blade + render hook `PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE` (`vendor/filament/filament/src/View/PanelsRenderHook.php:103`) | idem | igual à do pago | Não |

Gate de CT-B: nada aqui só o navegador prova — filtro, aba e Action são teste de componente Livewire (`04`). Não haveria `05`.

## Variáveis de Ambiente

Nenhuma. No nível (b) o interruptor é o `use TemVisoesSalvas;` de cada tela — uma flag de config por cima seria um segundo interruptor para a mesma coisa.

## Eventos / Listeners / Observers

- Nenhum. O nível (b) grava via Eloquent direto na Action; auditoria entra pelo `tapp/filament-auditing` se o model receber o trait `Auditable`, como os outros models do kit.

## Jobs / Queues

- Nenhum.

## Impacto em Features Existentes

- **`persistir_filtros`**: aba ativa (`?tab=`) **não** é persistida na sessão pelo Filament — `HasTabs::loadDefaultActiveTab()` só cai no `getDefaultActiveTab()`. Ao voltar para a tela, o usuário verá a primeira aba, mas com os filtros do modal ainda aplicados. É comportamento nativo; documentar no README, não contornar.
- **Contagem em badge**: `Tab::badge(fn () => Model::query()->count())` é uma query por aba a cada render. Em `/infra/ai-runs` com muitas linhas, usar `->deferBadge()` (`Tab.php:141`) ou omitir o badge.
- **Tenancy**: `Tab::modifyQueryUsing()` recebe a query já escopada pelo `getEloquentQuery()` do Resource — o recorte por tenant se mantém. A tabela `visoes_de_tabela` do nível (b) precisa de `tenant_id` nullable + `BelongsToTenant`, e o teste em par (`tests/Kit` + `tests/Tenancy`) exigido por `.ai/rules/filament.md`.
- **`tests/Kit/PermissoesDeAcoesTest.php`**: toda Action nova fica vermelha no inventário até declarar como é autorizada — é o enforço, não contornar.

## Rollback

- **Nível (a)**: remover `getTabs()`; nenhum dado.
- **Nível (b)**: `down()` derruba `visoes_de_tabela`; remover o `use TemVisoesSalvas;` da tela esconde a UI sem migration.
- **Nível (c)**: `composer remove`; as tabelas ficam até `migrate:rollback` do pacote.

## Dependências

- **Composer**: nenhuma nova em (a) e (b). Em (c), o pacote extraído depende de `filament/tables ^5.0` e é publicado como `gsferro/filament-table-views-easy` (nome a decidir), seguindo `gsferro/filament-stat-plus-easy` e `gsferro/filament-odometer-easy` já publicados.
- **NPM**: nenhuma. Nível (c) com barra de favoritos própria exigiria CSS no tema, como o pago exige `@source` — motivo para a barra ser feita de componentes Filament (`Action`/`ActionGroup`) e não de Blade solto.

## Riscos

- **Formato do estado de filtro é interno do Filament**: `Filter` grava `['isActive' => bool]` (`Filter.php:18,74`), `SelectFilter` grava `['value' => …]` ou `['values' => […]]` (`SelectFilter.php:109-155`). Uma view salva em JSON cristaliza esse formato; um minor do Filament pode mudá-lo. Mitigação: salvar o array cru e aplicar via `getTableFiltersForm()->fill()`, que é o mesmo caminho que a URL usa (`InteractsWithTable.php:81`) — se a URL continuar funcionando, a view continua.
- **Colunas não viajam por URL**: `$tableColumns` não tem `#[Url]` (`vendor/filament/tables/src/Concerns/HasColumnManager.php:23`). Aplicar "colunas da view" exige escrever em `$this->tableColumns` + `applyTableColumnManager()` dentro da Action, não por link. É o passo mais frágil do nível (b).
- **Cadência do pago**: release semanal. Um pacote free concorrente precisa acompanhar cada minor do Filament — o custo recorrente é o que domina o nível (c), não o inicial.
- **Varredura do diretório é por termos**: um plugin com nome fora de table/filter/view/preset/saved/column pode ter escapado (registrado em `00` → Ambiguidades).

## Channel de Log da Feature

### Verificação de Channel Existente

- `config/logging.php` não tem channel para tabelas. Nível (a) não loga nada (não há decisão de fluxo). Nível (b) cria `visoes-de-tabela` (driver `daily`, `storage/logs/visoes-de-tabela.log`, `debug`, 14 dias), com logs `[VisaoDeTabela@salvar]`, `[VisaoDeTabela@aplicar]`, `[VisaoDeTabela@excluir]` no padrão `[Classe@Método] mensagem | id: x`.

## Estrutura de Implementação

> Cada nível é independente e cumulativo: (b) pressupõe (a) só como aprendizado, não como código; (c) pressupõe (b).

### Nível (a) — Abas e botões de filtro nativos (RQ-04)

> Skills: `laravel-best-practices`, `pest-testing`, `ponytail`

**Passo 1 — Abas em `ListUsers` (admin e app)**

- **Path**: `app/Filament/Admin/Resources/Users/Pages/ListUsers.php`, `app/Filament/App/Resources/Users/Pages/ListUsers.php`
- Sobrescrever `getTabs(): array` devolvendo `['todos' => Tab::make('Todos'), 'pendentes' => Tab::make('Pendentes de aprovação')->icon(Heroicon::OutlinedClock)->badge(fn (): int => static::getResource()::getEloquentQuery()->where('aprovacao_pendente', true)->count())->modifyQueryUsing(fn (Builder $q): Builder => $q->where('aprovacao_pendente', true))]`. Namespace do `Tab`: `Filament\Schemas\Components\Tabs\Tab` (o mesmo que `RoleResource.php:290` já importa).
- A query da aba **repete** a de `AprovacaoDeCadastro::filtroDePendentes()`; para não duplicar, extrair a closure para `AprovacaoDeCadastro::recorteDePendentes(Builder): Builder` e usá-la nos dois lugares. O filtro do modal continua existindo (o usuário pode combinar).
- Query da badge sempre via `static::getResource()::getEloquentQuery()`, nunca `User::query()` — `.ai/rules/filament.md`, "Resource de model sem relação de posse com o tenant".
- Botão **em outra tela** (card do hub, notificação) que abre esta listagem já recortada não precisa de passo: é `ListUsers::getUrl(['tab' => 'pendentes'])` ou `ListUsers::getUrl(['filters' => ['aprovacao_pendente' => ['isActive' => true]]])` — ambos confirmados em `ListRecords.php:39,54`. Se algum card vier a precisar, é uma linha no `->url()` dele.
- **Logs**: nenhum.

**Passo 2 — Abas em `ListConvites` (admin e app)**

- **Path**: `app/Filament/Admin/Resources/Convites/Pages/ListConvites.php`, `app/Filament/App/Resources/Convites/Pages/ListConvites.php`
- `getTabs()`: `todos`, `pendentes` (`whereNull('aceito_em')`), `aceitos` (`whereNotNull('aceito_em')`). Reaproveitar as closures do `TernaryFilter` de `ConvitesTable.php:60-66` movendo-as para métodos estáticos de `ConvitesTable` (`pendentes(Builder)`, `aceitos(Builder)`).
- `/infra/ai-runs` fica **de fora de propósito**: o `SelectFilter('status')` já está na tela e uma aba por status o duplicaria para um usuário de infra. Quem quiser, é o mesmo padrão dos passos 1 e 2, com `->deferBadge()` por causa do volume.
- **Logs**: nenhum.

**Passo 3 — Testes**

- Um caso por listagem em `tests/Kit/`: `livewire(ListUsers::class)->set('activeTab', 'pendentes')->assertCanSeeTableRecords($pendentes)->assertCanNotSeeTableRecords($aprovados)`; e um caso em `tests/Tenancy/` para o `ListUsers` do painel `app` conferindo que a aba não vaza usuário de outra organização.

**Passo 4 — README**

- Seção curta em "Convenções do kit": "Listagem com estados distintos ganha `getTabs()`; o filtro do modal é para combinação, a aba é para o recorte de um clique". Registrar que a aba ativa não persiste na sessão (nativo).

### Nível (b) — Visões salvas por usuário

> Skills: `laravel-best-practices`, `pest-testing`, `ponytail`

**Passo 5 — Migration e model**

- `php artisan make:model VisaoDeTabela -mf --no-interaction`
- Tabela `visoes_de_tabela`: `id`, `user_id` (FK `users`, cascade), `tenant_id` (nullable, FK `tenants`, index), `tabela` (string 255 — identificador da tela; usar o FQCN da `ListRecords`, o mesmo que o Filament usa para compor a chave de sessão), `nome` (string 100), `estado` (json — `{filters, search, sort, columns}`), `padrao` (bool default false), timestamps. Índice único `(user_id, tabela, nome)`. Sem ícone, cor nem favorita: são a Favorites Bar do pago, e ela não existe neste nível.
- Model: `BelongsToTenant` (só quando `config('kit.tenancy.enabled')` — seguir como `Projeto` faz), `Auditable` do `tapp/filament-auditing`, `casts()` com `estado => 'array'`. Sem `HasFactory` além do gerado.
- Comparar antes com `Asmit\ResizedColumn\Models\TableSetting` (`user_id`, `resource`, `styles`): estender a tabela do asmit foi **recusado** (ADR-04) — ela pertence ao vendor e a migration dele muda sem aviso.
- **Logs**: nenhum no model.

**Passo 6 — Policy e permissões**

- `php artisan make:policy VisaoDeTabelaPolicy --model=VisaoDeTabela --no-interaction`; `update`/`delete` só quando `$visao->user_id === $user->id`.
- Como não haverá Resource, as chaves entram em `config('filament-shield.custom_permissions')` (`Create:VisaoDeTabela`, `Update:VisaoDeTabela`, `Delete:VisaoDeTabela`) e em `PapeisSeeder::paineisDasPermissoesCustomizadas()` para os três painéis — `.ai/rules/filament.md`, "RelationManager o Shield não enxerga" aplica-se igual a model sem Resource.
- Ressemear `ShieldPermissionsSeeder` e `PapeisSeeder`.

**Passo 7 — Trait `TemVisoesSalvas` para `ListRecords`**

- **Path**: `app/Filament/Concerns/TemVisoesSalvas.php`
- `protected function acoesDeVisoes(): array` devolvendo: `Action::make('salvarVisao')->label('Salvar visão')->icon(Heroicon::OutlinedBookmark)->authorize('create', VisaoDeTabela::class)->schema([TextInput::make('nome')->required()->maxLength(100)])->action(fn (array $data) => $this->salvarVisao($data))` e um `ActionGroup::make(...)` com uma `Action` por visão do usuário para a `static::class` corrente (`->action(fn () => $this->aplicarVisao($visao))`), mais `Action` de excluir com `->authorize('delete', $visao)`.
- `salvarVisao()` captura `$this->tableFilters`, `$this->tableSearch`, `$this->tableSort`, `$this->tableColumns` (propriedades públicas do `InteractsWithTable` — `HasFilters.php:23`, `CanSearchRecords.php:22`, `CanSortRecords.php:9`, `HasColumnManager.php:23`).
- `aplicarVisao()` faz `$this->tableFilters = $estado['filters']; $this->tableDeferredFilters = $estado['filters']; $this->getTableFiltersForm()->fill($estado['filters']); $this->tableSearch = …; $this->tableSort = …; $this->tableColumns = …; $this->applyTableColumnManager(); $this->handleTableFilterUpdates()` — `handleTableFilterUpdates()` é `protected` em `HasFilters.php:62`, acessível de dentro do trait porque o trait vive na mesma classe; `applyTableColumnManager(?array $state = null, bool $wasReordered = false)` é público (`vendor/filament/tables/src/Concerns/HasColumnManager.php:77`).
- Ligar/desligar é o próprio `use TemVisoesSalvas;`: opt-in por tela, sem flag de config por cima (dois interruptores para uma coisa).
- Cada `ListRecords` que quiser: `use TemVisoesSalvas;` e `[...$this->acoesDeVisoes(), CreateAction::make()]` em `getHeaderActions()`.
- **Logs**: `Log::channel('visoes-de-tabela')->info('[TemVisoesSalvas@salvarVisao] Visão salva | visao: {id}', ['user_id', 'tabela', 'estado'])`; `->info('[TemVisoesSalvas@aplicarVisao] Visão aplicada | visao: {id}', [...])`; `->warning('[TemVisoesSalvas@aplicarVisao] Estado da visão não bateu com os filtros da tabela | visao: {id}', ['filtros_desconhecidos' => [...]])` quando uma chave do JSON não existe mais na tabela (filtro removido após a visão ser salva).

**Passo 8 — Testes**

- `tests/Kit/VisoesDeTabelaTest.php`: salvar captura filtro+busca+sort; aplicar restaura e a tabela mostra só o recorte; usuário B não vê nem aplica visão de A (chamar `aplicarVisao()` **direto**, como manda `.ai/rules/filament.md` "Asserção de identidade vive no model"); Action escondida sem permissão; visão com filtro que não existe mais não quebra a tela.
- `tests/Tenancy/VisoesDeTabelaTenancyTest.php`: `tenant_id` gravado; visão de outra organização não aparece.
- Atualizar `tests/Kit/PermissoesDeAcoesTest.php` (inventário); `tests/Kit/PermissoesDeResourcesTest.php` não muda (não há Resource).

**Passo 9 — README**

- Seção "Visões salvas de tabela": o que salva, o que não salva (largura de coluna é do asmit; agrupamento e multi-sort não existem), como ligar por tela.

### Nível (c) — Pacote gratuito publicável

> Skills: `laravel-best-practices`, `pest-testing`, `ponytail`

**Passo 10 — Extração**

- Novo repositório `gsferro/filament-table-views-easy` no molde de `gsferro/filament-stat-plus-easy`: `src/` (Plugin de painel com `->userModel()`, `->tenantColumn()`, `->enabled()`), `database/migrations/*.stub`, `resources/lang/{pt_BR,en}`, `resources/views` (só se a barra deixar de ser `ActionGroup`), `config/`, `tests/` com Orchestra Testbench, GitHub Actions (Pest em PHP 8.3/8.4 × Laravel 12/13 × Filament 5).
- A policy vira publicável; a permissão deixa de depender do Shield (o pacote não pode assumir Shield) — `->authorize()` com `Gate` simples e hook para o app trocar.

**Passo 11 — Preset Views**

- `PresetView::make()` com `modifyQueryUsing()`, `defaultFilters()`, `defaultSort()`, `defaultColumns()`, `icon()`, `color()`, `badge()`, `default()`, `favorite()` — renderizadas como `Action`s antes das visões do usuário. É a API que o pago tem e que o nativo `getTabs()` não cobre (aba não aplica filtros nem colunas).

**Passo 12 — Compartilhamento e View Manager**

- `publica` (bool) + `aprovada_em` (nullable) na tabela; ícone e cor por view; Resource de administração opcional; View Manager como `Action` com slide-over listando pessoais/públicas/presets.

**Passo 13 — Publicação**

- Packagist, README bilíngue, submissão em `filamentphp.com/plugins`, `CHANGELOG`, política de versão casada com o Filament (5.x → 1.x). O kit então **troca** o código do nível (b) pela dependência.

## Estimativa de custo (RQ-06)

Premissas: 1 dev sênior que conhece o kit e o Filament 5; dia de 8 h; inclui testes Pest e README, exclui revisão por terceiros. As faixas refletem incerteza, não folga.

| Nível | O que entrega | Passos | Dias de dev | Custo recorrente | Quem se beneficia |
|---|---|---|---|---|---|
| (a) Abas e botões nativos | "botões de filtros específicos" em 4 telas de listagem (2 Resources) + link com filtro na URL | 1–4 | **1 a 2** | ~zero (API nativa estável, `getTabs()` existe desde o v3) | todo projeto do kit |
| (b) Visões salvas por usuário | salvar/aplicar/excluir estado da tabela por usuário e organização, opt-in por tela | 5–9 | **4 a 6** | baixo: 1 dia por major do Filament para reconferir o formato do estado de filtro | projeto com operador que repete o mesmo recorte todo dia |
| (c) Pacote free publicável | (b) + preset views com filtros/colunas + compartilhamento + view manager + tenancy configurável + i18n + CI + Packagist | 10–13 | **15 a 25** iniciais | **alto**: o concorrente pago lança semanalmente; manter paridade é 2 a 4 dias/mês | comunidade; marca gsferro |

Referência de mercado: a licença Single do pago custa €79 (~R$ 500 em 08/2026), menos que **meio dia** de dev
sênior. O que justifica o nível (c) não é economia — é ausência de licença redistribuível (o kit é um
skeleton `create-project`) e posicionamento do autor no ecossistema.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação, se houver.
> A escada aqui já foi subida no estudo: o nível (a) é rung 4 (feature nativa); o nível (b) é rung 7
> (mínimo que funciona) e só entra quando um projeto real pedir; o nível (c) é investimento, não
> simplicidade. Atalhos deliberados levam `ponytail:` comment.
>
> **Caveman ativo em modo `ultra`** na comunicação agent ↔ usuário. Arquivos wiki são boundary — prosa normal.

## Testes

> Sem `04-casos-de-teste.md` nesta wiki: é estudo (RQ-05). Os cenários por passo estão inline nos passos 3 e 8 como
> insumo para a `feature-test-design` quando um nível for aprovado.

## Verificação Final

- [ ] `/ponytail:ponytail-review` na wiki (step 6 da skill — feito, ver `03-progresso.md`)
- [ ] Commit `:memo: docs(wiki): estudo de viabilidade — advanced tables e alternativas`
- [ ] `git push -u origin feat/estudo-advanced-tables` (sem PR)

## Commits

- `:memo: docs(wiki): estudo de viabilidade — advanced tables e alternativas`

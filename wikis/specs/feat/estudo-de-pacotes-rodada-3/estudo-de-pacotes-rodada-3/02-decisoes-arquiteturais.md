# Decisões Arquiteturais — Estudo e adoção de pacotes Filament, rodada 3

<!-- ADR só quando as três valem: difícil de reverter, surpreendente sem contexto, resultado de
     trade-off real. As demais decisões estão em ## Decisões de Desenho do 01. -->

## ADR-01: Visões salvas de tabela vêm do `sehsah/filament-remember-table`, não do `happenv-com/filament-saved-views` nem de código do kit

**Status**: Aceita
**Data**: 2026-10-14
**Portões**: difícil de reverter ✅ (a tabela `remember_table_views` passa a guardar dado de usuário em toda instalação que ligar a feature, e o trait entra nas dez páginas de listagem do kit — trocar de pacote depois é migração de dado e de dez arquivos) · surpreendente ✅ (o pacote recusado tem dezesseis vezes mais downloads, seis versões a mais, sessenta e quatro locales e CI em três versões de Laravel; o escolhido tem uma versão, três estrelas e nem traz pt-BR) · trade-off ✅ (maturidade e adoção **contra** escopo por organização e ausência de blade própria — e o kit é multi-organização e não tem tema customizado)

### Contexto

O estudo `estudo-advanced-tables` (agosto de 2026) apontou "visões salvas por usuário" como o item de
maior valor que o kit não tinha e custeou implementá-lo à mão: model com `user_id` e `tenant_id`,
policy, trait em `ListRecords`, permissões na matriz, testes em `Kit` e `Tenancy`. Dois pacotes da
comunidade entregam esse item agora, e o solicitante pediu que se escolhesse o que agrega valor.

Três restrições do kit decidem a escolha, e nenhuma é de gosto:

1. **Multi-organização.** O painel `app` é por organização; uma visão gravada numa organização não
   pode aparecer noutra, e a fronteira precisa estar na **consulta**, não na tela
   (`.ai/rules/filament.md`, "asserção de identidade vive no model").
2. **Sem tema customizado do Filament.** A CSS pré-compilada do Filament 5 não carrega as
   utilitárias Tailwind que uma blade de pacote emite; todo pacote com blade própria exige um
   subconjunto de CSS escrito à mão e uma guarda que lê a blade do vendor em runtime
   (`.ai/rules/css-filament.md`, dois casos medidos).
3. **Permissão consultada em toda Action nova** (`.ai/rules/filament.md`), o que exige um ponto
   onde o kit consiga acrescentar `->authorize()` sem editar o vendor.

### Decisão

Adotar o `sehsah/filament-remember-table`, embrulhado num trait do kit (`App\Filament\Concerns\VisoesDeTabela`)
que aplica o interruptor das configurações e a permissão. O `happenv-com/filament-saved-views` fica
documentado como alternativa, com o gatilho de reabertura: abandono do pacote escolhido, ou o kit
passar a ter tema customizado **e** o `saved-views` ganhar escopo por organização.

### Alternativas Consideradas

1. **`happenv-com/filament-saved-views`** — descartado por duas das três restrições: a migration não
   tem coluna de organização e a consulta do gerenciador filtra só por usuário e classe (um usuário
   de duas organizações vê as mesmas visões nas duas), e a blade própria emite utilitárias
   (`flex`, `gap-2`, `px-2`, `text-sm`, `dark:border-white/10`…) cuja instalação oficial manda
   acrescentar `@source` ao CSS de um tema customizado — que o kit decidiu não ter. Cobrir isso à
   mão é exatamente o custo que a rule de CSS mede como silencioso e recorrente.
2. **Código do kit, como o nível (b) do estudo anterior** — descartado: o estudo custeou model,
   policy, trait, permissões e testes; o pacote escolhido já entrega tudo isso (inclusive o que o
   estudo deixou de fora: visão padrão, compartilhamento, aba ativa, agrupamento, `?view=` na URL,
   reordenação) com o escopo por organização que o kit exigiria. Manter um clone interno custa a
   manutenção sem ganhar fronteira.
3. **Os dois pacotes** — descartado: o mesmo requisito, duas tabelas, dois gerenciadores.

### Consequências

- **Positivas**: o nível (b) do `estudo-advanced-tables` fecha sem código de domínio; a fronteira
  por organização e por dono já está na consulta do pacote (`visibleTo`, `ownedBy`, `forTenant`);
  sem blade própria, nenhum CSS a cobrir; as Actions são do Filament, então `->authorize()` entra
  por alias de trait.
- **Negativas**: dependência jovem (uma versão, três estrelas) e sem constraint explícita de
  Laravel; o pacote não traz pt-BR (o kit escreve as traduções em `lang/vendor/`); com a feature
  ligada, o pacote consulta `remember_table_views` quatro vezes na montagem da listagem (lista de
  visões, visão padrão e a condição `visible()` de *Gerenciar* avaliada duas vezes) e três por render
  seguinte — medido no step 10 (`01`, *Modelo de Execução*) *(alterado em 2026-10-15, QA-04 do gate:
  dizia "uma consulta por carga")*; desligada, zero.
- **Riscos**: abandono do pacote — mitigado pelo tamanho (um trait de ~800 linhas, um model, um
  helper de estado, lidos por inteiro no step 3) e pelo embrulho num ponto só do kit, que é onde
  um substituto entraria.

### Referências

- `App\Filament\Concerns\VisoesDeTabela` (o embrulho), `Sehsah\FilamentRememberTable\Concerns\HasRememberTable`
- `wikis/specs/feat/estudo-advanced-tables/estudo-advanced-tables/01-plano-acao.md`, nível (b)
- `.ai/rules/css-filament.md`, `.ai/rules/filament.md`
- `07-dossies-dos-pacotes.md` desta wiki

---

## Superfície Livewire

<!-- Formato e greps em references/pesquisa-step-3.md da feature-wiki. Re-varrida antes do step 9. -->

As três origens: o código do kit (o trait `VisoesDeTabela` e o enum `CarregamentoDaTabela` — nenhum
método público novo além dos que sobrescrevem o pacote; os sete nomes do pacote renomeados pelo `use … as protected …DoPacote` ficam fora do `$wire` — RD-02, P-30, CT-80; re-varrido sobre o código final antes do step 9 e corrigido depois dele),
o framework (os arrays de estado da tabela que as visões capturam e reaplicam) e os dois pacotes.

### Pacote `sehsah/filament-remember-table`

Greps rodados no step 3 sobre `vendor/sehsah/filament-remember-table/src` (cópia temporária com a
mesma versão 1.0.0): `::find(|whereKey(|findOrFail(` → 1 ocorrência (`whereKey`, sob `ownedTableViewsQuery`; o
`whereKeyNot` da visão padrão é outro método, também sob `ownedTableViewsQuery`); `public …$` → 1 (`$activeTableView`); `$arguments[|$data[` → 5,
todas dentro de Actions com formulário validado; `extends Model` → 1 (`TableView`).

| Ponto de entrada (vendor) | Alcançável por | Fronteira aplicada | Evidência |
|---|---|---|---|
| `public string $activeTableView` com `#[Url(as: 'view')]`, sem `#[Locked]` | `?view=12` na URL e `$wire.set('activeTableView', …)` entre requests | só resolve por `findTableView()`: `preset-*` contra os presets em código, ou dígitos contra `getTableViews()` — que é `visibleTo($user)` + `forTenant()` + `forTable()`; chave fora disso vira visão nenhuma | `vendor/sehsah/filament-remember-table/src/Concerns/HasRememberTable.php:$activeTableView:33` · `:findTableView():593-610` · `:tableViewsQuery():576-584` |
| `applyTableView(string $key, bool $notify = true)` | `$wire.applyTableView('12')` de qualquer cliente autenticado | mesma fronteira de `findTableView()`; só altera o estado da tabela do próprio componente; com as visões **desligadas** o trait do kit devolve coleção vazia e a chamada devolve `false` | `vendor/sehsah/filament-remember-table/src/Concerns/HasRememberTable.php:applyTableView():304-327` |
| `resetTableToDefaultState()` | `$wire.resetTableToDefaultState()` | só o estado da própria tabela (filtros, busca, ordenação, colunas, aba) | `vendor/sehsah/filament-remember-table/src/Concerns/HasRememberTable.php:resetTableToDefaultState():511-547` |
| `getTableViews()`, `getTableViewActions()`, `getPresetTableViews()`, `getTableViewsKey()`, `isActiveTableViewModified()` | `$wire.<método>()` — o retorno vai ao navegador | sem argumento; devolvem o que o próprio usuário já vê; o trait do kit curto-circuita os dois primeiros com a feature desligada | `vendor/sehsah/filament-remember-table/src/Concerns/HasRememberTable.php:getTableViews():560-574` · `:getTableViewActions():119-144` |
| `Action saveTableView` — `$data['name']`, `$data['is_default']`, `$data['is_public']` | `$wire.mountAction('saveTableView')` + envio do formulário | `name` `required`/`maxLength(100)`; `is_public` só existe no formulário com `allow_public_views` (campo `visible(false)` não é desidratado pelo schema — cenário do `04` confirma que o valor forjado grava `false`); teto `max_views_per_table` por usuário; **kit**: `->authorize('Usar:VisoesDeTabela')` | `vendor/sehsah/filament-remember-table/src/Concerns/HasRememberTable.php:saveTableViewAction():146-207` (`is_public` em `:166` e `:189`; teto em `:170-179`) |
| `Action updateTableView` | `$wire.mountAction('updateTableView')` | só se a visão ativa é do usuário (`isOwnedBy`), conferido na visibilidade **e** na ação; **kit**: `->authorize(…)` | `vendor/sehsah/filament-remember-table/src/Concerns/HasRememberTable.php:updateTableViewAction():209-236` (`:219`, `:224`) |
| `Action manageTableViews` — `$data['views'][]` (`id`, `name`, `is_default`, `is_public`) | `$wire.mountAction('manageTableViews')` + repeater | `syncManagedTableViews()` só percorre `ownedTableViewsQuery($user)`: id de outro usuário é ignorado, e a exclusão é `ownedBy(...)->whereKey(...)`; `is_public` só grava com `allow_public_views`; **kit**: `->authorize(…)` | `vendor/sehsah/filament-remember-table/src/Concerns/HasRememberTable.php:manageTableViewsAction():238-290` · `:syncManagedTableViews():649-688` (`:652`, `:670`, `:680`) |
| `Action resetTableView` | `$wire.mountAction('resetTableView')` | chama `resetTableToDefaultState()`; **kit**: `->authorize(…)` | `vendor/sehsah/filament-remember-table/src/Concerns/HasRememberTable.php:resetTableViewAction():292-299` |
| `Action applyTableView_{md5}` (uma por visão) | `$wire.mountAction('applyTableView_…')` | só existe para visão que `getTableViews()` devolveu; sem permissão por decisão (D6: só lê) | `vendor/sehsah/filament-remember-table/src/Concerns/HasRememberTable.php:makeTableViewItemsGroup():699-730` |
| Model `TableView` (`$guarded = ['id']`) — **persistido pela feature** | nenhum ponto de entrada o alcança por id cru: `grep -rn "::find(\|findOrFail(" src` = 0; o único `whereKey` é o da exclusão, escopado | `user_type`/`user_id` e `tenant_id` são preenchidos pelo trait a partir do usuário autenticado e de `Filament::getTenant()`, nunca do cliente; `scope_to_tenant` fica `true` | `vendor/sehsah/filament-remember-table/src/Models/TableView.php:class TableView:23` · `:scopeVisibleTo():63-73` · `vendor/sehsah/filament-remember-table/src/Concerns/HasRememberTable.php:getTableViewsTenantId():797-804` |
| Estado da visão reaplicado: `filters`, `search`, `columnSearches`, `sort`, `grouping`, `columns`, `columnOrder`, `perPage`, `tab` (JSON gravado por um usuário e lido de volta) | o JSON de `state` é escrito por `captureTableViewState()` a partir dos arrays públicos do Filament — isto é, entrada do cliente de um request anterior | cada parte só entra se existir na definição da tabela: filtros por `array_intersect_key` com `getFilters()`, ordenação só com `getColumn()`, agrupamento só com `getGroup()`, aba só se está em `getCachedTabs()`, `perPage` só se está em `getPaginationPageOptions()`; valores de filtro passam pelo `fill()` do formulário de filtros | `vendor/sehsah/filament-remember-table/src/Concerns/HasRememberTable.php:captureTableViewState():338-376` · `:applyTableViewState():381-456` |

### Pacote `asignua/filament-infinite-scroll`

Greps: `::find(|whereKey(|findOrFail(` → 0; `public …$` → 0 em `src/` (o estado vive no memo do Livewire e na propriedade **do Filament** `tableRecordsPerPage`); `$arguments[|$data[` → 2 (`$data['table']`, `$data['records']` do render hook, dados do servidor); `extends Model` → 0.

| Ponto de entrada (vendor) | Alcançável por | Fronteira aplicada | Evidência |
|---|---|---|---|
| Método `infiniteScrollLoadMore` (interceptado pelo hook global; nenhum componente o declara) | `$wire.infiniteScrollLoadMore()` em **qualquer** componente `HasTable` de qualquer painel | ignorado quando a tabela não registrou `infiniteScroll()` (`config() === null`); cresce **um** bloco por chamada, até o teto | `vendor/asignua/filament-infinite-scroll/src/Livewire/InfiniteScrollHook.php:call():85-95` · `:grow():149-165` · `vendor/asignua/filament-infinite-scroll/src/InfiniteScrollConfig.php:next():27-30` |
| `public $tableRecordsPerPage` (do Filament, `InteractsWithTable`) | `$wire.set('tableRecordsPerPage', 50000)` | o hook devolve ao intervalo em todo `render`/`update`: nunca acima do tamanho que o servidor emitiu (guardado no memo, coberto pelo checksum do snapshot), sempre múltiplo do bloco e abaixo do teto; `?page=N` é reiniciado para 1 | `vendor/asignua/filament-infinite-scroll/src/Livewire/InfiniteScrollHook.php:sync():167-209` (`:189-202`) · `:hydrate():58-75` |
| Render hook `TablesRenderHook::CONTENT_AFTER` | renderiza em toda tabela com `infiniteScroll()` registrado | só lê `$data['table']` e `$data['records']` que o Filament passa; string vazia se `records` não é `LengthAwarePaginator` ou está vazio | `vendor/asignua/filament-infinite-scroll/src/FilamentInfiniteScrollServiceProvider.php:packageBooted():40-86` |
| Arrays que reiniciam o bloco: `tableSearch`, `tableColumnSearches`, `tableFilters`, `tableSort`, `tableGrouping`, `activeTab`, `pageFilters` | já são públicos do Filament; o hook só os **lê** para a assinatura | nenhuma escrita; um valor inválido neles é tratado pelo próprio Filament, como hoje | `vendor/asignua/filament-infinite-scroll/src/Livewire/InfiniteScrollHook.php:QUERY_PROPERTIES:44-53` |

### Código do kit

| Ponto | Alcançável por | Fronteira | Evidência |
|---|---|---|---|
| `VisoesDeTabela::bootedHasRememberTable()`, `getTableViews()`, `getTableViewActions()` (sobrescritas públicas, mesmos nomes do pacote) | os mesmos `$wire.*` acima; o `booted` é hook do Livewire | guarda `visoesHabilitadas()` lida de `config('kit.tabelas.visoes_salvas')` por request: desligada, coleção vazia, `[]` e nenhum `booted` do pacote | `app/Filament/Concerns/VisoesDeTabela.php:visoesHabilitadas():96` · `:bootedHasRememberTable():101` · `:getTableViews():109` · `:getTableViewActions():115` |
| `saveTableViewAction()`, `updateTableViewAction()`, `manageTableViewsAction()`, `resetTableViewAction()` (sobrescritas públicas) | `$wire.mountAction('saveTableView')` etc. | `->hidden(! visoesHabilitadas())` (achado CT-08: o nome continua montável com o botão escondido; `hidden()` e não `visible()` porque `visible()` substituiria a condição do pacote em *Atualizar*/*Gerenciar* — RD-01, P-29, CT-79) **e** `->authorize('Usar:VisoesDeTabela')` — os dois bloqueiam o `mountAction` | `app/Filament/Concerns/VisoesDeTabela.php:saveTableViewAction():131-136` · `:updateTableViewAction():138-143` · `:manageTableViewsAction():145-150` · `:resetTableViewAction():152-157` |
| `CarregamentoDaTabela` (enum) e `ConfiguraFilamentGlobal::aplicaCarregamento()` | nenhum — não são componentes Livewire (`grep -rnE 'public[[:space:]]+([^($;]*[[:space:]])?\$' app/Filament/Concerns/VisoesDeTabela.php app/Support/CarregamentoDaTabela.php app/Providers/Concerns/ConfiguraFilamentGlobal.php` = 0 propriedades públicas novas) | `coagir()` aceita qualquer valor e cai em `Paginacao` | `app/Support/CarregamentoDaTabela.php:coagir():110` |

Re-varredura sobre o código final (pré-step 9, 2026-10-14): `grep -n "public function " app/Filament/Concerns/VisoesDeTabela.php app/Support/CarregamentoDaTabela.php` = 8 + 6 métodos (os 14 acima; nenhum recebe argumento do cliente); vendor instalado (`vendor/sehsah/filament-remember-table/src`): `::find(|whereKey(|findOrFail(` = 1 linha (`whereKey($deleted->all())`, sob `ownedTableViewsQuery`; o `whereKeyNot` é outro método), `extends Model` = 1 (`TableView`).

# Decisões Arquiteturais — Tela de perfis (papéis)

## ADR-01: O termo é "Papéis", não "Perfis"

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

O requisito delega a escolha: *"vamos mudar para papeis ou perfis, o que voce preferir"*. O rótulo
atual é "Funções" — tradução pt_BR do Shield
(`vendor/bezhansalleh/filament-shield/resources/lang/pt_BR/filament-shield.php:37`), termo que não
aparece em nenhum outro lugar do kit.

O nome importa mais do que parece porque o kit já tem uma decisão tomada sobre isto: a chave do
papel é `master_global`/`admin_app`/`panel_user` e o rótulo exibido vem de `App\Support\Papeis`,
cujo docblock (`app/Support/Papeis.php:7-19`) existe exatamente para impedir que a mesma informação
seja escrita de dois jeitos em telas diferentes.

### Decisão

**"Papel" / "Papéis"**.

### Alternativas Consideradas

1. **"Perfil" / "Perfis"** — é o termo que o usuário usou na conversa e no nome da branch
   (`feat/perfis-e-permissoes`). Descartada porque colide em dois lugares:
   - "perfil" já significa outra coisa nesta interface: o menu do usuário tem "Meu perfil"
     (`BreezyCore::make()->myProfile(… slug: 'meu-perfil' …)`, `AdminPanelProvider.php:131`) e o
     badge do cabeçalho é `resources/views/filament/perfil-indicator.blade.php`. Duas coisas
     diferentes chamadas "perfil" na mesma barra superior;
   - a base inteira diz papel: `App\Support\Papeis`, `database/seeders/PapeisSeeder.php`, as colunas
     rotuladas "Papéis" em quatro tabelas, e a `.ai/rules/filament.md` (*"Papel novo precisa
     declarar o painel"*). Trocar o rótulo da tela para "Perfis" criaria uma tela chamada "Perfis"
     cujas colunas em outras telas se chamam "Papéis".
2. **Manter "Funções"** — descartada pelo requisito.

### Consequências

- **Positivas**: zero divergência entre a tela nova e os treze outros pontos que já dizem "papel";
  a mudança se resume a três chamadas de label no `AdminPanelProvider`.
- **Negativas**: o nome da branch e da pasta da wiki dizem "perfis" e a tela diz "Papéis". É
  cosmético e ficou registrado aqui.
- **Riscos**: nenhum técnico.

### Referências

- `app/Support/Papeis.php:7-19`
- `.ai/rules/filament.md` → *"Papel novo precisa declarar o painel"*
- `00-requisito.md` → RQ-01

---

## ADR-02: A URL continua `/admin/shield/roles`

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

Renomear o rótulo para "Papéis" deixa a URL dizendo `shield/roles`. É tentador trocar o slug
(`config('filament-shield.shield_resource.slug')`) para `papeis`.

### Decisão

**Não trocar.** O requisito pede label e breadcrumb, e essas são as duas coisas que a pessoa lê.

### Alternativas Consideradas

1. **`'slug' => 'papeis'` na config** — descartada: quebra a URL literal em
   `tests/Kit/PaginasInfraTest.php:83`, `tests/Kit/VoltarAoTopoTest.php:52`,
   `tests/Pest.php:240-241`, `tests/BrowserTenancy/CapturaDeArteTest.php:133,141` e
   `tests/Kit/PaineisTest.php:174`, além de qualquer link salvo por quem já usa o kit. Custo alto,
   benefício estético, e nenhuma cláusula do requisito pede.
2. Redirect de `shield/roles` para `papeis` — mais código para um problema que ninguém tem.

### Consequências

- **Positivas**: diff pequeno, nenhum teste de URL tocado, nenhuma quebra para instalação existente.
- **Negativas**: incoerência visível entre o que a barra de endereço diz e o que a tela diz. Dívida
  declarada; se um dia for paga, é numa entrega que também ajuste os cinco arquivos de teste.

---

## ADR-03: `uuid` na tabela `roles` por migration própria, com backfill em três tempos

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

RQ-08/RQ-09 exigem `uuid` na URL. A convenção do kit é a trait `App\Traits\TemUuid`, cujo checklist
(`app/Traits/TemUuid.php:14-18`) pede migration com `$table->uuid('uuid')->unique()` **NOT NULL**.
`roles` é criada pela migration publicada do spatie
(`database/migrations/2026_08_12_164859_create_permission_tables.php`) e já tem linhas em toda
instalação existente (cinco papéis do `PapeisSeeder`).

### Decisão

Migration nova (`2026_08_24_000001_add_uuid_to_roles_table.php`), em três tempos: coluna
`nullable()`, backfill por `Str::uuid()`, depois `unique('uuid')`. Sem `->change()` para fechar o
NOT NULL. A migration termina limpando o cache de permissões, como a do spatie faz
(`create_permission_tables.php:117-119`).

### Alternativas Consideradas

1. **Editar a migration publicada do spatie** — descartada: ela é republicável
   (`vendor:publish --force`) e a edição sumiria sem aviso.
2. **`$table->uuid('uuid')->unique()` direto** — em SQLite, acrescentar coluna NOT NULL sem default
   a tabela com linhas falha. E a instalação existente tem linhas.
3. **Fechar o NOT NULL com `->change()` depois do backfill** — descartada: em SQLite o `change()`
   reconstrói a tabela, e `roles` é alvo de FK em `model_has_roles` e `role_has_permissions`
   (`create_permission_tables.php:84-87`, `:109-112`). O índice único já garante o que a rota
   precisa (unicidade), e o `HasUuids` garante o preenchimento de toda linha nova pelo hook de
   `creating`. Nullable sem linha nula é diferente de NOT NULL apenas para uma linha inserida por
   SQL cru — e SQL cru já contorna o kit inteiro.
4. **`uuid` também em `permissions`** — descartada: `permissions` não tem tela nem rota. YAGNI.

### Consequências

- **Positivas**: a última tabela do kit com Resource passa a expor `uuid` na rota; nenhuma FK
  tocada; reversível pelo `down()`.
- **Negativas**: a coluna fica `nullable` no schema, divergindo do texto do checklist de `TemUuid`.
  Registrado no docblock da migration para o próximo agente não "corrigir" com um `change()`.
- **Riscos**: `permission.teams` ligado troca a PK de `model_has_roles`, não de `roles` — sem
  impacto. O `PermissionRegistrar` cacheia papéis com colunas explícitas, então a coluna nova não
  entra no payload de cache; a limpeza no fim da migration é cinto e suspensório.

### Referências

- `app/Traits/TemUuid.php:14-18`
- `database/migrations/2026_08_12_164859_create_permission_tables.php:84-87`, `:109-112`, `:117-119`
- `database/migrations/0001_01_01_000020_create_tenants_table.php:18-26` (o padrão em tabela nova)

---

## ADR-04: A contagem de usuários é de pessoas distintas, não de linhas de pivot

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

`TextColumn::make('users_count')->counts('users')` é a forma nativa e de uma linha. Ela gera:

```sql
(select count(*) from "users"
   inner join "model_has_roles" on "users"."id" = "model_has_roles"."model_id"
  where "roles"."id" = "model_has_roles"."role_id"
    and "model_has_roles"."model_type" = 'App\Models\User') as "users_count"
```

Com `permission.teams` ligada — o modo multi-organização do kit — a chave primária de
`model_has_roles` é `(team_id, role_id, model_id, model_type)`
(`database/migrations/2026_08_12_164859_create_permission_tables.php:88-93`). Logo **a mesma pessoa
com o mesmo papel em duas organizações são duas linhas**, e `count(*)` diria 2 para uma pessoa.

`Spatie\Permission\Models\Role::users()` é um `morphedByMany` sem filtro de team
(`vendor/spatie/laravel-permission/src/Models/Role.php:100-109`) — o `wherePivot` de team o spatie
põe em `HasRoles::roles()`, do lado do usuário, não aqui. Então nem a relação dedupe.

### Decisão

Contar `distinct`, passando um closure ao `counts()`:

```php
->counts(['users' => fn (Builder $query): Builder => $query->select(
    new Expression('count(distinct users.id)')
)])
```

Verificado que produz o SQL certo, uma consulta, sem N+1:

```sql
(select count(distinct users.id) from "users" inner join "model_has_roles" …) as "users_count"
```

O `select()` (que SUBSTITUI colunas) e não `selectRaw()` (que acrescenta): o `withAggregate()`
do Laravel mantém apenas a PRIMEIRA coluna do subselect, então um `selectRaw` deixaria o
`count(*)` original valendo e o `distinct` seria descartado sem erro.

A expressão é **literal**, e a primeira versão não era: ela montava
`'count(distinct '.$query->getModel()->getQualifiedKeyName().')'`. O PHPStan reprovou
(`argument.type` — `Expression` exige `float|int|literal-string`), e a regra está certa: SQL
por concatenação é por onde injeção entra. O preço do literal é acoplar a coluna a
`auth.providers.users.model` apontar para uma model na tabela `users` — e esse acoplamento
**falha alto**: se alguém trocar, a listagem morre em "no such column: users.id" na primeira
abertura, em vez de devolver número errado em silêncio.

O mesmo raciocínio de dedupe vale para a lista do slide-over (RQ-05), e lá sem SQL nenhum:
`$papel->users()->distinct()`.

### Alternativas Consideradas

1. **`->counts('users')` puro** — descartada: número errado na tela sob tenancy, que é o modo em que
   o kit é interessante. Ninguém percebe: a coluna mostra um número plausível.
2. **`->state(fn (Role $r) => $r->users()->distinct()->count())`** — correto, mas é uma consulta por
   linha (N+1 na listagem). Recusada porque a versão de uma consulta custa a mesma linha de código.
3. **Deduplicar em PHP depois do `get()`** — traz todos os usuários da instalação para contar.

### Consequências

- **Positivas**: número correto nos dois modos, uma consulta, coluna ordenável.
- **Negativas**: um `DB::raw` na tela. Mitigado por comentário no ponto e pelo `getQualifiedKeyName()`
  em vez de nome de tabela escrito à mão.
- **Riscos**: `counts()` com closure depende de o `withAggregate` do Laravel deixar o `select` do
  closure substituir a expressão `count(*)`. Coberto por CT em `tests/Tenancy` que só passa com o
  `distinct` — se um upgrade do Laravel mudar isso, o teste cai. E a expressão literal acopla a
  coluna à tabela `users`; falha alto, e o CT-04 é o que acusa.

### Referências

- `vendor/spatie/laravel-permission/src/Models/Role.php:100-109`
- `vendor/filament/support/src/Concerns/CanAggregateRelatedModels.php:62-67`
- `00-requisito.md` → Ambiguidades, RQ-04/RQ-05

---

## ADR-05: Tab vertical de painel substitui a `Section` collapsible

**Status**: Aceita
**Data**: 2026-08-24
**Refine**: a decisão original de agrupar por painel, tomada em
`wikis/specs/main/perfil-e-acesso-ao-painel/`

### Contexto

O kit já não usa a lista plana do vendor: `RoleResource::getResourceEntitiesSchema()` agrupa os
Resources por painel numa `Section` collapsible por painel. O requisito diz que o accordion piora a
experiência de quem customiza permissão e pede tab vertical.

Medido no `/admin`: são três painéis e, no painel `app` sozinho, 59 permissions em 4 Resources e 3
Pages (`.ai/rules/filament.md`). Com dois painéis abertos a página rola por vários viewports e
perde-se a referência de onde se estava.

### Decisão

`Tabs::make('paineis')->vertical()` com um `Tab` por painel, dentro do tab "Recursos" do vendor. A
`Section` por Resource **continua** — ela é o grupo mais fino e é onde o contador de RQ-07 mora.

### Alternativas Consideradas

1. **Manter a `Section` e só acrescentar o contador** — atende RQ-07 e não atende RQ-10.
2. **Tab horizontal** — o requisito pede vertical, e com três a cinco painéis o rótulo "Painel
   /admin" não cabe bem numa fita horizontal já ocupada por Recursos/Páginas/Widgets.
3. **Reescrever `getShieldFormComponents()` inteiro** — descartada: aumentaria a divergência com o
   vendor, contra o que o docblock de `RoleResource.php:42-44` pede explicitamente.

### Consequências

- **Positivas**: um painel visível por vez; a divergência com o vendor continua confinada ao mesmo
  método que já era sobrescrito.
- **Negativas**: `Tabs` dentro de `Tabs`. Resolvido dando nome próprio (`paineis`) ao interno — dois
  `Tabs` com a mesma chave compartilhariam o estado de aba ativa.
- **Riscos**: o tab ativo não persiste entre requests (não se usou `persistTabInQueryString()`, para
  não pôr estado de UI na URL de uma tela de permissão).

### Referências

- `app/Filament/Admin/Resources/Roles/RoleResource.php:177-209`
- doc do Filament 5 — *Schemas → Tabs*, `vertical()` e `badge()` (confirmado também em
  `vendor/filament/schemas/src/Components/Tabs.php:246-256` e
  `vendor/filament/schemas/src/Components/Tabs/Tab.php`)

---

## ADR-06: O contador de permissões lê o state pelo FQCN, e não o banco

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

RQ-07 pede *"a quantidade de permissões que cada grupo aquele perfil já tem selecionado"*. Há duas
fontes possíveis: o que está gravado (`$papel->permissions`) e o que está marcado no formulário.

### Decisão

Ler o **state do formulário**, pelo `Get` do Filament, usando como chave o FQCN do Resource.

O FQCN é a chave certa porque é esse o `name` que o Shield dá ao `CheckboxList`:
`HasShieldFormComponents::getCheckBoxListComponentForResource()` chama
`getCheckboxListFormComponent(name: $entity['resourceFqcn'], …)`
(`vendor/bezhansalleh/filament-shield/src/Traits/HasShieldFormComponents.php:122-133`) — e é a mesma
premissa de que `CreateRole::mutateFormDataBeforeCreate()` já depende, ao tratar toda chave de
`$data` que não seja `name`/`guard_name`/`painel`/`select_all`/team como lista de permissões
(`app/Filament/Admin/Resources/Roles/Pages/CreateRole.php:27-31`).

FQCN tem barra invertida e nenhum ponto; o `Get` separa caminho por ponto, então o FQCN é chave de
primeiro nível válida, sem escape.

### Alternativas Consideradas

1. **Contar do banco (`$record->permissions`)** — descartada por dois motivos: o contador ficaria
   parado enquanto a pessoa marca caixas (que é justamente o momento em que ele é útil), e em
   `create` não há registro nenhum.
2. **Somar percorrendo `$livewire->form->getFlatComponents()`**, como o vendor faz em
   `toggleSelectAllViaEntities()` — funciona, mas montar a árvore de componentes dentro do closure
   de um badge que é avaliado durante o render é convite a recursão.

### Consequências

- **Positivas**: o contador é reativo de graça — o `CheckboxList` do Shield já é `->live()`
  (`HasShieldFormComponents.php:209`), então toda marcação re-renderiza o formulário e reavalia o
  badge. Nenhum `->live()` novo, nenhum listener.
- **Negativas**: o contador depende do nome que o Shield dá ao componente. Se um upgrade trocar
  `resourceFqcn` por outra chave, o badge mostra `0/12`. Coberto por CT de componente que marca uma
  permissão e confere o badge.
- **Riscos**: nenhum de dado — é leitura de state.

---

## ADR-07: A action do slide-over declara `->authorize('view')`

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

Action do Filament **não** consulta policy sozinha: o default de
`Filament\Actions\Concerns\CanBeAuthorized` é `null`, ou seja, liberada — a mesma armadilha que
`.ai/rules/filament.md` registra para `ImportAction`/`ExportAction`. A action nova exibe nome e
e-mail de todos os usuários que têm o papel.

### Decisão

`->authorize('view')`. E um log em `Log::channel('autenticacao')` registrando quem consultou a lista
de quem.

**O log vive em `->afterFormFilled()`, não em `->action()`** — e a correção veio da auditoria do
diff, não do desenho. Com `->modalSubmitAction(false)` não existe botão que dispare
`callMountedAction`, e "Fechar" desmonta a action: um log em `->action()` seria **código morto**,
verde no teste (que pode chamar `callAction()`) e inexistente na tela. `afterFormFilled` é chamado
por `InteractsWithActions::mountAction()` logo depois do `mount()`
(`vendor/filament/actions/src/Concerns/InteractsWithActions.php:185-194`), uma vez por abertura —
que é exatamente o momento do acesso.

### Alternativas Consideradas

1. **Sem `authorize()`** — quem tem `ViewAny:Role` (para abrir a listagem) passaria a ler o e-mail de
   toda a base. `ViewAny` e `View` são permissões separadas justamente para isso.
2. **`->visible()` em vez de `->authorize()`** — `visible` esconde o botão e não fecha a chamada
   Livewire.
3. **Sem log** — leitura de e-mail de terceiros sem rastro é o tipo de coisa que ninguém sente falta
   até precisar.
4. **Log em `->action()`** — descartada porque não funciona: ver acima.

### Consequências

- **Positivas**: a superfície nova nasce autorizada e auditada.
- **Negativas**: um log a mais por clique. `info`, no channel que já existe, sem channel novo.
- **Riscos**: `Import:`/`Export:` não estão em jogo aqui; nenhuma permission nova é criada, então
  não é preciso ressemear o Shield.

### Referências

- `vendor/filament/actions/src/Concerns/CanBeAuthorized.php` (o próprio comentário do vendor diz que
  a autorização default é `null` = liberada)
- `vendor/filament/actions/src/Concerns/InteractsWithActions.php:185-194` (o hook de montagem)
- `vendor/filament/actions/src/Concerns/CanBeHidden.php:74-95` (não-autorizado ⇒ escondido, o que
  torna `assertActionHidden` um oráculo válido para CT-07)
- `.ai/rules/filament.md` → *"`->authorize()` não é opcional"*

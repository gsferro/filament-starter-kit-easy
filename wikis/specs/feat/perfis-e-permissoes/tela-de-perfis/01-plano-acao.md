# Plano de Ação — Tela de perfis (papéis) do /admin

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: evolução
- **Wiki ancestral**: `wikis/specs/main/perfil-e-acesso-ao-painel/` (criou a coluna `roles.painel`,
  o `Papeis::rotulo()` e o `RoleResource` publicado) e
  `wikis/specs/main/admin-da-organizacao/` (ADR-07, as barreiras de painel na escrita de papel)
- **Motivo**: a tela existe e funciona; o requisito pede rótulo, contagem de usuários, UX das
  permissões, guard como seleção e `uuid` na rota
- **Toca infra compartilhada?**: **sim** — a migration acrescenta `uuid` à tabela `roles`, que é
  lida por `spatie/laravel-permission`, pelo Shield, pelo `PapeisSeeder`, pelos convites e pelos
  dois `UserResource`. Regressão **obrigatória** contra `tests/Kit` e `tests/Tenancy` inteiros.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Termo: papéis ou perfis | 1 | "Papéis" — ADR-01 |
| RQ-02 | Label do recurso | 1 | `modelLabel`/`pluralModelLabel`/`navigationLabel` no plugin |
| RQ-03 | Breadcrumb, inclusive o segmento do registro | 1, 5 | `getRecordTitle()` deixa de vazar a chave |
| RQ-04 | Coluna de quantidade de usuários | 2 | contagem de pessoas distintas — ADR-04 |
| RQ-05 | Slide-over com todos os usuários | 3 | `Action`+`slideOver()`+`RepeatableEntry` |
| RQ-06 | Rótulo em toda exibição de papel | 5 | cinco pontos crus, listados no passo |
| RQ-07 | Contagem de permissões selecionadas por grupo | 4 | dois níveis: painel e Resource |
| RQ-08 | `uuid` na URL de alteração | 6 | migration + trait + ajuste do teste de arte |
| RQ-09 | Nenhum `id` em URL, no kit todo | 6 | ⚠️ **parcial** — `roles` fechado; três rotas de vendor no `/infra` ficam como dívida declarada (achado 3 do QA) |
| RQ-10 | Painéis viram tab vertical no tab "Recursos" | 4 | `Tabs::make()->vertical()` |
| RQ-11 | Guard vira seleção de `config('auth.guards')` | 7 | |
| RQ-12 | Playwright MCP + skills de design | — | ⚠️ **fora desta entrega** — ver `00-requisito.md` → Fora desta entrega |
| RQ-13 | Componentes nativos do Filament | 1..7 | nenhum Blade novo, nenhum componente próprio |

## Objetivo

Transformar `/admin/shield/roles` — hoje a tela do Shield com rótulo em inglês traduzido para
"Funções", chave crua no breadcrumb, guard em texto livre, `id` na URL e um accordion por painel —
na tela de **Papéis** do kit: rótulo legível em todo lugar, quantidade de usuários por papel com
slide-over de quem são, `uuid` na rota, guard como seleção fechada, e o vínculo de permissões
navegável por tab vertical de painel com contador de quantas permissões cada grupo já tem marcadas.

Nada disso é tela nova: é a mesma tela publicada em
`app/Filament/Admin/Resources/Roles/RoleResource.php`, editada nos pontos que o requisito nomeia,
com componentes nativos do Filament 5.

## Contexto

O `RoleResource` é **publicado** no projeto (não é o do vendor) porque o Shield não oferece hook
para agrupar as permissões por painel. O docblock da classe (`:39-64`) lista as quatro divergências
deliberadas em relação ao vendor e pede que o diff de um upgrade continue legível — este plano
respeita isso: toda edição fica confinada aos métodos que a classe **já** sobrescreve, mais dois
novos (`getRecordTitle()` e o método que monta o tab vertical).

O que a tela hoje não faz e o requisito cobra:

1. o rótulo do recurso vem de `filament-shield::filament-shield.nav.role.label`, que em pt_BR é
   "Funções" (`vendor/bezhansalleh/filament-shield/resources/lang/pt_BR/filament-shield.php:37`) —
   termo que não aparece em nenhum outro lugar do kit;
2. `Resource::getRecordTitle()` devolve `$record->getAttribute('name')` cru
   (`vendor/filament/filament/src/Resources/Resource/Concerns/HasLabels.php:105-108`), e é ele que
   alimenta breadcrumb, título da página de edição e busca global — daí `panel_user` no breadcrumb;
3. `roles` não tem coluna `uuid` (a migration de permissões é a do spatie,
   `database/migrations/2026_08_12_164859_create_permission_tables.php`), e `App\Models\Role` não
   usa `App\Traits\TemUuid`;
4. `guard_name` é `TextInput` (`RoleResource.php:94-98`);
5. `getResourceEntitiesSchema()` (`:192-209`) devolve uma `Section` collapsible por painel;
6. não há coluna de usuários nem qualquer lugar que mostre **quem** tem o papel.

## Análise dos Arquivos Existentes

### `app/Filament/Admin/Resources/Roles/RoleResource.php` (320 linhas)

O centro da entrega. Recebe: o `Select` de guard (RQ-11), a coluna `users_count` e a Action de
slide-over (RQ-04, RQ-05), o `getRecordTitle()` (RQ-03/RQ-06) e a troca do accordion por tab
vertical com contadores (RQ-07, RQ-10).

**Cuidado documentado no arquivo, a preservar**: `getResourceEntitiesSchema()` sobrescreve um método
de **trait** (`HasShieldFormComponents`) e por isso **não** leva `#[Override]` — o comentário em
`:190-191` registra que o atributo aborta o request. Todo método novo desta entrega que venha de
trait segue a mesma regra; `getRecordTitle()` vem de `Filament\Resources\Resource` (classe pai) e
**leva** `#[Override]`.

### `app/Providers/Filament/AdminPanelProvider.php:126`

`FilamentShieldPlugin::make()` sem configuração. Ganha os três labels. É o ponto certo porque
`BezhanSalleh\PluginEssentials\Concerns\Resource\HasLabels` (usado pelo Resource) delega ao plugin
antes de cair no pai (`vendor/bezhansalleh/filament-plugin-essentials/src/Concerns/Resource/HasLabels.php:11-19`),
e o plugin só sobrescreve o default quando o valor foi setado pelo usuário
(`.../Concerns/Plugin/HasPluginDefaults.php:24-31`).

### `app/Models/Role.php`

Model magro, existe só pela coluna `painel`. Ganha `use TemUuid`.

### `app/Support/Papeis.php`

Não muda. É a fonte do rótulo, e o passo 5 só amplia quem a consulta.

### `app/Filament/Admin/Widgets/UltimosUsuariosCadastrados.php:86-91` e `UsuariosPorPapel.php:55-64`

Os dois widgets do dashboard `/admin` exibem `roles.name` cru. Entram no passo 5.

### `app/Filament/Admin/Resources/Tenants/RelationManagers/UsersRelationManager.php:99`

`Role::query()->where('painel','app')->pluck('name','id')` alimenta o `Select` da action "Papéis
nesta organização" com a chave crua. Entra no passo 5.

### `app/Filament/App/Resources/Users/UserResource.php:174-186`

`Select::make('roles')->relationship('roles','name', …)` sem
`getOptionLabelFromRecordUsing()` — o irmão do `/admin` (`Admin/.../UserResource.php:58-62`) tem.
Entra no passo 5.

### `tests/BrowserTenancy/CapturaDeArteTest.php:141`

`visit("/admin/shield/roles/{$papel->getKey()}/edit")` — usa `getKey()`. Com o `uuid` na rota este
cenário passa a visitar uma URL inexistente. Vira `getRouteKey()` no passo 6.

## Autorização

Nada muda. As permissões do `RoleResource` (`ViewAny:Role`, `Update:Role`, …) já são geradas pelo
`ShieldPermissionsSeeder` e recortadas por painel pelo `PapeisSeeder`; nenhum Resource,
RelationManager, Page ou Widget novo é criado, então **não há permissão nova a semear** e a regra
"Resource ou RelationManager novo exige gerar as permissões" (`.ai/rules/filament.md`) não é
disparada.

A Action de slide-over (RQ-05) é uma **table action** dentro do `RoleResource`, e action do Filament
não consulta policy sozinha (`vendor/filament/actions/src/Concerns/CanBeAuthorized.php` — default
`null`). Ela só lê dados do papel que a linha já expõe, e quem chegou à linha já passou por
`ViewAny:Role`; mesmo assim recebe `->authorize('view')` explícito, para não abrir um caminho de
leitura de e-mails de usuários a quem só se deu `ViewAny` sem `View`.

## Rotas

Nenhuma rota nova. As quatro rotas do Resource continuam nos mesmos paths; o que muda é o
**parâmetro** de `{record}`:

| Método | URI | Name | Muda? |
|--------|-----|------|-------|
| GET | `/admin/shield/roles` | `filament.admin.resources.shield/roles.index` | não |
| GET | `/admin/shield/roles/create` | `…create` | não |
| GET | `/admin/shield/roles/{record}` | `…view` | `{record}` passa a ser `uuid` |
| GET | `/admin/shield/roles/{record}/edit` | `…edit` | `{record}` passa a ser `uuid` |

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `ListRoles` (tabela de papéis) | Filament | `/admin/shield/roles` | lê a tabela, vê a coluna de usuários, abre o slide-over | Sim (slide-over) |
| Slide-over "Usuários com este papel" | Filament Action | idem | abre e fecha | Sim |
| `CreateRole` | Filament | `/admin/shield/roles/create` | preenche nome, guard, painel e marca permissões | Sim |
| `EditRole` | Filament | `/admin/shield/roles/{uuid}/edit` | idem + navega os tabs verticais de painel | Sim |
| `ViewRole` | Filament | `/admin/shield/roles/{uuid}` | lê | Sim |
| Dashboard `/admin` (2 widgets) | Filament Widget | `/admin` | lê os rótulos de papel | Não |

**Gate de CT-B**: a tabela é o gatilho, não o critério. Vai para o browser só o que **só o
navegador prova**: que o tab vertical de painel renderiza e troca de painel por clique (Alpine), que
o slide-over abre, e que a tela de edição não emite erro de JS. Contagem, gravação, rótulo,
autorização e o `uuid` na rota são teste de componente Livewire / HTTP e pertencem ao `04`.

**Gate de tela de escrita**: `create` e `edit` já têm cenário de gravação por componente em
`tests/Kit/PaineisTest.php`; o `04` acrescenta os que faltam para guard e `uuid`.

## Variáveis de Ambiente

Nenhuma nova.

## Eventos / Listeners / Observers

Nenhum novo. Atenção a um efeito existente: `App\Traits\TemUuid` usa `HasUuids`, que registra um
hook de `creating` para preencher `uniqueIds()`. `Role` passa a ter esse hook — é o mesmo que
`Tenant`, `User` e `Projeto` já têm.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **spatie/laravel-permission** — o `PermissionRegistrar` cacheia papéis. A coluna nova não entra no
  cache (`Config::$cacheModelKey` seleciona colunas explícitas), mas a migration **precisa** limpar
  o cache de permissões, como a própria migration do spatie faz
  (`…create_permission_tables.php:117-119`).
- **`PapeisSeeder` / `ShieldPermissionsSeeder`** — criam papéis com `Role::firstOrCreate`. Com o
  `HasUuids` o `uuid` é preenchido no `creating`; papel já existente não recebe (daí o backfill na
  migration).
- **Convites** — `Convite::papel()` é `belongsTo(Role::class)` por `role_id` (FK numérica). Não
  muda: a PK continua `id`, só a **route key** vira `uuid`.
- **`tests/BrowserTenancy/CapturaDeArteTest.php`** — usa `getKey()` na URL. Quebra se não for
  ajustado (passo 6).
- **`tests/Kit/PaineisTest.php`, `PaginasInfraTest.php`, `VoltarAoTopoTest.php`,
  `HubDeCardsTest.php`, `tests/Browser/TelasDoKitTest.php`** — citam a URL de listagem (que não
  muda) ou `RoleResource::getNavigationLabel()` (que passa a ser "Papéis" — o teste lê da classe,
  então continua verde).
- **Renomeação de papel** (`tests/Kit/RenomeacaoDePapelTest.php`, migration
  `2026_08_16_000001_rename_admin_organizacao_role.php`) — mexe em `roles.name` por `update`, não em
  `uuid`. Sem impacto.

## Rollback

- **Migration down**: `Schema::table('roles', fn ($t) => $t->dropUnique(...)->dropColumn('uuid'))` e
  limpeza do cache de permissões. Reversível sem perda: `uuid` não é referenciado por FK.
- **Feature flag**: nenhuma. Todas as mudanças são de apresentação, exceto o `uuid`, que é reversível
  pela migration.

## Dependências

Nenhuma nova, em composer ou npm. Tudo sai de `filament/filament 5.7.6`,
`bezhansalleh/filament-shield 4.3.1` e do que o kit já tem.

## Riscos

- **`#[Override]` em método de trait aborta o request** (já documentado em `RoleResource.php:190`).
  *Mitigação*: `getRecordTitle()` vem da classe pai e leva o atributo; qualquer método novo que
  venha de `HasShieldFormComponents` não leva. Verificação: `php artisan test --testsuite=Kit`
  falha ruidosamente se errar.
- **`uuid` NOT NULL em SQLite com linhas existentes**. *Mitigação*: migration em três tempos —
  coluna nullable, backfill linha a linha, depois índice único. Sem `->change()`, que em SQLite
  reconstrói a tabela e perderia a PK composta de nenhuma tabela aqui, mas é risco desnecessário.
- **Contagem de usuários errada com tenancy** (mesma pessoa, mesmo papel, duas organizações =
  duas linhas em `model_has_roles`). *Mitigação*: contagem `distinct` — ADR-04 — e um CT em
  `tests/Tenancy` que só passa com o `distinct`.
- **Tab vertical dentro de tab horizontal**: `Tabs` aninhado precisa de chave própria, senão o
  estado de tab ativo colide. *Mitigação*: `Tabs::make('paineis')` com nome distinto de
  `Tabs::make('Permissions')` do vendor, e CT-B que clica de um painel para outro.
- **Contador de permissões lendo o state pelo caminho errado**: o nome do `CheckboxList` é o **FQCN
  do Resource** (`HasShieldFormComponents::getCheckBoxListComponentForResource()` →
  `getCheckboxListFormComponent(name: $entity['resourceFqcn'], …)`), com barras invertidas. O
  `Get` do Filament separa caminho por ponto, não por barra, então o FQCN é uma chave de primeiro
  nível válida. *Mitigação*: CT de componente que marca uma permissão e confere o badge.

## Channel de Log da Feature

### Verificação de Channel Existente

`grep -n "autenticacao" config/logging.php` → o channel `autenticacao` existe e é o que
`CreateRole@afterCreate` e `EditRole@afterSave` já usam (`CreateRole.php:66`, `EditRole.php:74`),
junto de `User::canAccessPanel()` e do `UsersRelationManager`.

### Decisão

**Reusar `autenticacao`.** Não se cria channel novo: papel e permissão são assunto de autenticação/
autorização, é onde a trilha já mora, e um channel por tela fragmentaria a auditoria de quem mexeu
em quem pode o quê. Nenhuma linha nova de log é obrigatória nesta entrega — as duas gravações
(criar e editar papel) já logam. **Um** log novo entra, no passo 3, porque abrir a lista de
usuários de um papel é leitura de e-mail de terceiros e hoje não deixa rastro nenhum.

## Estrutura de Implementação

### 1. Rótulo e breadcrumb do recurso — RQ-01, RQ-02, RQ-03

> Skills: `laravel-best-practices`

- **Path**: `app/Providers/Filament/AdminPanelProvider.php` (linha 126)

Trocar `FilamentShieldPlugin::make(),` por:

```php
// "Funções" é a tradução pt_BR do Shield e o termo não existe em nenhum outro lugar do kit:
// a coluna se chama "Papéis", o helper é `App\Support\Papeis`, o seeder é `PapeisSeeder`.
// Ver ADR-01.
FilamentShieldPlugin::make()
    ->modelLabel('Papel')
    ->pluralModelLabel('Papéis')
    ->navigationLabel('Papéis'),
```

- **Path**: `app/Filament/Admin/Resources/Roles/RoleResource.php`

Acrescentar, junto dos outros overrides de `Resource`:

```php
/**
 * O título do registro, que alimenta breadcrumb, título da página de edição e busca global.
 *
 * O default devolve `$record->name` cru
 * (`vendor/filament/filament/src/Resources/Resource/Concerns/HasLabels.php:105-108`), e é por
 * isso que o breadcrumb de `/admin/shield/roles/{uuid}/edit` dizia `panel_user`. Chave é
 * identificador, não rótulo — a mesma razão de `App\Support\Papeis` existir.
 */
#[Override]
public static function getRecordTitle(?Model $record): string|Htmlable|null
{
    return $record === null
        ? static::getModelLabel()
        : Papeis::rotulo((string) $record->getAttribute('name'));
}
```

`Htmlable` já não está importado — acrescentar `use Illuminate\Contracts\Support\Htmlable;`.
`Model` e `Papeis` já estão (`:33`, `:12`).

- **Logs**: nenhum. Passo de apresentação.

### 2. Coluna de quantidade de usuários — RQ-04

> Skills: `laravel-best-practices`, `eloquent-best-practices`

- **Path**: `app/Filament/Admin/Resources/Roles/RoleResource.php`, dentro de `table()`, logo antes
  de `permissions_count` (`:156`)

```php
TextColumn::make('users_count')
    ->label('Usuários')
    ->badge()
    ->color(fn (int $state): string => $state === 0 ? 'gray' : 'primary')
    /*
     * `distinct` e não `->counts('users')` puro: com `permission.teams` ligada a chave
     * primária de `model_has_roles` inclui `team_id`
     * (database/migrations/2026_08_12_164859_create_permission_tables.php:88-93), então a
     * MESMA pessoa com o MESMO papel em duas organizações são duas linhas de pivot. O
     * `count(*)` diria 2 para uma pessoa. Ver ADR-04.
     */
    ->counts(['users' => fn (Builder $query): Builder => $query->select(
        DB::raw('count(distinct '.$query->getModel()->getQualifiedKeyName().')')
    )])
    ->sortable(),
```

Imports novos: `Illuminate\Database\Eloquent\Builder`, `Illuminate\Support\Facades\DB`.

- **Logs**: nenhum. Leitura agregada, sem dado pessoal.

### 3. Slide-over com os usuários do papel — RQ-05

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/Roles/RoleResource.php`, em `table()->recordActions()`,
  **antes** do `EditAction` (`:168-171`)

```php
static::acaoDeUsuarios(),
EditAction::make(),
DeleteAction::make(),
```

E o método novo:

```php
/**
 * Quem tem este papel, num slide-over.
 *
 * `->authorize('view')` não é zelo: Action do Filament não consulta policy sozinha — o
 * default de `Concerns/CanBeAuthorized` é `null`, ou seja, liberada. Sem a linha, quem
 * recebeu `ViewAny:Role` (para ver a listagem) passaria a ler o e-mail de todos os
 * usuários da instalação.
 *
 * O state vem por `->state()` e não pela relação `users` direto pelo mesmo motivo do
 * `distinct` na coluna (ADR-04): com tenancy, `$papel->users` devolve a mesma pessoa uma
 * vez por organização.
 */
private static function acaoDeUsuarios(): Action
{
    return Action::make('usuarios')
        ->label('Usuários')
        ->icon(Heroicon::OutlinedUsers)
        ->color('gray')
        ->authorize('view')
        ->slideOver()
        ->modalHeading(fn (Model $record): string => 'Usuários com o papel '.Papeis::rotulo((string) $record->getAttribute('name')))
        ->modalDescription('Somente leitura. O vínculo se altera no cadastro do usuário.')
        ->modalSubmitAction(false)
        ->modalCancelActionLabel('Fechar')
        ->schema([
            RepeatableEntry::make('usuarios')
                ->hiddenLabel()
                ->state(static::usuariosDoPapel(...))
                ->table([
                    TableColumn::make('Nome'),
                    TableColumn::make('E-mail'),
                ])
                ->schema([
                    TextEntry::make('name')->hiddenLabel(),
                    TextEntry::make('email')->hiddenLabel()->copyable(),
                ])
                ->visible(fn (Model $record): bool => static::usuariosDoPapel($record) !== []),

            EmptyState::make('Nenhum usuário tem este papel')
                ->description('Papel sem ninguém vinculado não concede acesso a ninguém.')
                ->icon(Heroicon::OutlinedUsers)
                ->visible(fn (Model $record): bool => static::usuariosDoPapel($record) === []),
        ])
        ->action(function (Model $record): void {
            Log::channel('autenticacao')->info(
                '[RoleResource@usuarios] Lista de usuários do papel consultada | papel: '.$record->getAttribute('name'),
                [
                    'role_id'  => $record->getKey(),
                    'papel'    => $record->getAttribute('name'),
                    'executor' => auth()->id(),
                ],
            );
        });
}

/**
 * Os usuários do papel, uma vez cada.
 *
 * @return list<User>
 */
private static function usuariosDoPapel(Model $record): array
{
    if (! $record instanceof SpatieRole) {
        return [];
    }

    return $record->users()->distinct()->orderBy('name')->get()->all();
}
```

> **Ponytail**: sem paginação e sem busca dentro do slide-over. "Exibir todos os usuários" é o que o
> requisito pede, e papel de instalação tem dezenas de pessoas, não milhares. Se um dia tiver,
> a saída é um RelationManager, não paginar dentro de modal.
> `// ponytail: lista inteira, sem paginação — vira RelationManager se passar de algumas centenas`

Imports novos: `Filament\Actions\Action`, `Filament\Infolists\Components\RepeatableEntry`,
`Filament\Infolists\Components\RepeatableEntry\TableColumn`,
`Filament\Infolists\Components\TextEntry`, `Filament\Schemas\Components\EmptyState`,
`Filament\Support\Icons\Heroicon`, `Illuminate\Support\Facades\Log`,
`Spatie\Permission\Models\Role as SpatieRole`.

- **Logs**:
  - `Log::channel('autenticacao')->info('[RoleResource@usuarios] Lista de usuarios do papel consultada | papel: {name}', ['role_id', 'papel', 'executor'])`
    — nível `info` porque é leitura esperada de dado de terceiro, não anomalia. É o único log novo
    da entrega.
  - **Em `->afterFormFilled()`, não em `->action()`.** Com `->modalSubmitAction(false)` nada dispara
    `callMountedAction`, então `->action()` é código morto e o log nunca aconteceria na tela.
    `afterFormFilled` é chamado por `InteractsWithActions::mountAction()` logo depois do `mount()`
    (`vendor/filament/actions/src/Concerns/InteractsWithActions.php:185-194`), uma vez por abertura.
    Ver ADR-07.

### 4. Tab vertical de painéis + contador de permissões por grupo — RQ-07, RQ-10

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/Roles/RoleResource.php`, reescrevendo
  `getResourceEntitiesSchema()` (`:192-209`) e ajustando `secaoDoResource()` (`:245-261`)

`getResourceEntitiesSchema()` continua **sem `#[Override]`** (vem da trait). Ela passa a devolver
um único componente: o `Tabs` vertical, com um `Tab` por painel.

```php
/**
 * As permissões, agrupadas por painel num tab VERTICAL.
 *
 * Sobrescreve `HasShieldFormComponents::getResourceEntitiesSchema()`, que devolve uma lista
 * plana de seções — os Resources dos três painéis misturados, sem pista de onde cada tela
 * mora. Até a versão anterior o kit agrupava em `Section` collapsible por painel; virou tab
 * vertical porque com três painéis abertos ao mesmo tempo a tela rolava por metros e quem
 * customiza permissão perdia a referência de onde estava (ADR-05).
 *
 * A fonte NÃO pode ser `FilamentShield::getResources()`: ela devolve o painel corrente, e
 * esta tela vive no /admin — sairia um grupo só. Quem varre os três é `App\Support\Paineis`.
 *
 * @return array<int, Tabs>
 */
// Sem #[Override]: o método vem da trait HasShieldFormComponents, e o atributo só
// vale para método de classe pai — com ele o PHP aborta o request inteiro.
public static function getResourceEntitiesSchema(): ?array
{
    $opcoes = Paineis::opcoes();

    $tabs = collect(Paineis::resources())
        ->reject(fn (array $entidades): bool => $entidades === [])
        ->map(fn (array $entidades, string $painel): Tab => Tab::make('painel-'.$painel)
            ->label('Painel '.$opcoes[$painel])
            ->icon(Heroicon::OutlinedRectangleGroup)
            ->badge(fn (Get $get): string => self::contagemDoPainel($get, $entidades))
            ->badgeColor(fn (Get $get): string => self::selecionadas($get, $entidades) === 0 ? 'gray' : 'primary')
            ->schema([
                Grid::make()
                    ->schema(array_map(static::secaoDoResource(...), $entidades))
                    ->columns(self::colunasDaGrade()),
            ]))
        ->values()
        ->all();

    // Nome próprio, diferente do `Tabs::make('Permissions')` do vendor que envolve este:
    // dois Tabs com a mesma chave compartilhariam o estado de aba ativa.
    return [Tabs::make('paineis')->vertical()->tabs($tabs)->columnSpanFull()];
}
```

E os dois helpers de contagem, mais o rótulo da seção:

```php
/**
 * `3/12` — quantas permissões deste painel o papel já tem marcadas.
 *
 * O state de cada grupo vive sob o FQCN do Resource, porque é esse o `name` que o Shield dá
 * ao CheckboxList (`HasShieldFormComponents::getCheckBoxListComponentForResource()` chama
 * `getCheckboxListFormComponent(name: $entity['resourceFqcn'], …)`). FQCN tem barra
 * invertida e nenhum ponto, e o `Get` do Filament separa caminho por PONTO — então o FQCN é
 * uma chave de primeiro nível válida, sem escape.
 *
 * @param  list<array<string, mixed>>  $entidades
 */
private static function contagemDoPainel(Get $get, array $entidades): string
{
    $total = array_sum(array_map(
        static fn (array $entidade): int => count($entidade['permissions']),
        $entidades,
    ));

    return self::selecionadas($get, $entidades).'/'.$total;
}

/**
 * @param  list<array<string, mixed>>  $entidades
 */
private static function selecionadas(Get $get, array $entidades): int
{
    return array_sum(array_map(
        static fn (array $entidade): int => count((array) $get($entidade['resourceFqcn'])),
        $entidades,
    ));
}
```

Em `secaoDoResource()`, o mesmo contador no nível do Resource — o "grupo" mais fino que a tela tem.
A `Section` do Filament não tem `badge()`, e o `afterHeader()` aceita componente
(`vendor/filament/schemas/src/Components/Section.php:159`), então o contador entra por lá:

```php
return Section::make($rotulo)
    ->description(fn (): HtmlString => new HtmlString('<span style="word-break: break-word;">'.Utils::showModelPath($entity['modelFqcn']).'</span>'))
    ->afterHeader([
        Text::make(fn (Get $get): string => count((array) $get($entity['resourceFqcn'])).'/'.count($entity['permissions']))
            ->badge()
            ->color(fn (Get $get): string => $get($entity['resourceFqcn']) === [] || $get($entity['resourceFqcn']) === null ? 'gray' : 'primary'),
    ])
    ->compact()
    ->schema([
        static::getCheckBoxListComponentForResource($entity),
    ])
    ->columnSpan(static::shield()->getSectionColumnSpan())
    ->collapsible();
```

> O contador atualiza sozinho porque o `CheckboxList` do Shield já é `->live()`
> (`HasShieldFormComponents::getCheckboxListFormComponent()`, `:209`): toda marcação re-renderiza o
> formulário e os closures de badge são reavaliados. Nenhum `->live()` novo é necessário.

Imports novos: `Filament\Schemas\Components\Tabs`, `Filament\Schemas\Components\Tabs\Tab`,
`Filament\Schemas\Components\Text`, `Filament\Schemas\Components\Utilities\Get`.

- **Logs**: nenhum. Apresentação.

### 5. Rótulo de papel em toda exibição — RQ-06, RQ-03

> Skills: `laravel-best-practices`

A varredura (`grep -rn "Papeis::" app/ resources/` mais `grep -rn "roles\.name\|papel\.name"`)
encontrou **doze** pontos que já usam `Papeis::rotulo()` e **cinco** que exibem a chave crua. Os
cinco:

| # | Path | O que exibe cru | Correção |
|---|---|---|---|
| 5.1 | `app/Filament/Admin/Resources/Roles/RoleResource.php` | `getRecordTitle()` → breadcrumb, título da página de edição e busca global | feito no passo 1 |
| 5.2 | `app/Filament/Admin/Widgets/UltimosUsuariosCadastrados.php:86-91` | `rotuloDosPapeis()` faz `pluck('name')` e `implode` | mapear por `Papeis::rotulo()` antes do `implode` |
| 5.3 | `app/Filament/Admin/Widgets/UsuariosPorPapel.php:58-59` | `BreakdownItem::make((string) $papel->getAttribute('name'), …)` | `Papeis::rotulo(...)` no primeiro argumento |
| 5.4 | `app/Filament/Admin/Resources/Tenants/RelationManagers/UsersRelationManager.php:99` | `Role::query()->where('painel','app')->pluck('name','id')` | `->get()->mapWithKeys(fn (Role $p): array => [$p->getKey() => Papeis::rotulo((string) $p->getAttribute('name'))])->all()` |
| 5.5 | `app/Filament/App/Resources/Users/UserResource.php:174-186` | `Select::make('roles')->relationship('roles','name', …)` sem rótulo | acrescentar `->getOptionLabelFromRecordUsing(fn (Role $record): string => Papeis::rotulo($record->name))`, igual ao irmão do `/admin` (`Admin/…/UserResource.php:61`) |
| 5.6 | `app/Filament/App/Pages/ConvitesRecebidos.php` | `modalDescription` do aceite imprime `$record->papel?->getAttribute('name')` — chave crua, na mesma tela cuja coluna já usa o rótulo | `Papeis::rotulo(...)` |

> ⚠️ **A linha 5.6 não estava aqui: foi achado do quality gate.** A varredura que produziu esta
> tabela usou `grep "roles\.name\|papel\.name"`, e `->getAttribute('name')` não casa com nenhum
> dos dois. A lição, e o que a próxima varredura precisa fazer diferente: **procurar pelo ACESSO ao
> atributo, não pelo nome da coluna** — `getAttribute('name')`, `->name`, `pluck('name'`, `implode`
> sobre `pluck`. Ver `06-relatorio-qa.md`, achado 1.

Nos doze pontos que já usam o helper: nada muda. Lista, para o quality gate não reabrir a varredura:
`Admin/Resources/Convites/Pages/ListConvites.php:44`,
`Admin/Resources/Convites/Schemas/ConviteForm.php:52`,
`Admin/Resources/Convites/Tables/ConvitesTable.php:32`,
`Admin/Resources/Roles/RoleResource.php:137` e `:147`,
`Admin/Resources/Tenants/RelationManagers/UsersRelationManager.php:55`,
`Admin/Resources/Users/UserResource.php:61` e `:163`,
`App/Pages/ConvitesRecebidos.php:74`,
`App/Resources/Convites/ConviteResource.php:119` e `:147`,
`App/Resources/Convites/Pages/ListConvites.php:46`,
`App/Resources/Users/UserResource.php:216`,
`resources/views/filament/perfil-indicator.blade.php:50`.

> O docblock de `app/Support/Papeis.php:16-19` diz "sete telas". O número está velho: são doze
> pontos hoje, dezessete depois deste passo. Atualizar o docblock para não repetir o erro que
> `.ai/rules/filament.md` registra sobre número parado em rule.

- **Logs**: nenhum. Apresentação.

### 6. `uuid` na rota do papel — RQ-08, RQ-09

> Skills: `laravel-best-practices`, `eloquent-best-practices`

- **Path (novo)**: `database/migrations/2026_08_24_000001_add_uuid_to_roles_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * `uuid` na tabela de papéis — a convenção `App\Traits\TemUuid` aplicada à última tabela do
 * kit que ainda expunha `id` na URL.
 *
 * A tabela é a do spatie (`create_permission_tables`), então a coluna vem por migration
 * própria em vez de editar aquela — a do vendor é republicável e um `vendor:publish --force`
 * apagaria a edição.
 *
 * Três tempos, e não um: em SQLite acrescentar coluna NOT NULL sem default a uma tabela com
 * linhas é erro, e a instalação já tem cinco papéis semeados. Coluna nullable → backfill →
 * índice único. Não se usa `->change()` para fechar o NOT NULL: em SQLite ele reconstrói a
 * tabela, e `roles` é alvo de FK em `model_has_roles` e `role_has_permissions`
 * (`create_permission_tables.php:84-87`, `:109-112`) — o unique já garante a unicidade que a
 * rota precisa, e o `HasUuids` garante o preenchimento de toda linha nova.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->tabela(), function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::table($this->tabela())->whereNull('uuid')->orderBy('id')->each(
            fn (object $papel): bool => (bool) DB::table($this->tabela())
                ->where('id', $papel->id)
                ->update(['uuid' => (string) Str::uuid()]),
        );

        Schema::table($this->tabela(), function (Blueprint $table): void {
            $table->unique('uuid');
        });

        $this->esquecerCacheDePermissoes();
    }

    public function down(): void
    {
        Schema::table($this->tabela(), function (Blueprint $table): void {
            $table->dropUnique([$this->tabela().'_uuid_unique']);
            $table->dropColumn('uuid');
        });

        $this->esquecerCacheDePermissoes();
    }

    private function tabela(): string
    {
        return (string) config('permission.table_names.roles', 'roles');
    }

    /**
     * Mesmo encerramento da migration do spatie: o PermissionRegistrar cacheia os papéis, e
     * cache velho depois de mudança de schema é defeito que só aparece no request seguinte.
     */
    private function esquecerCacheDePermissoes(): void
    {
        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
```

- **Path**: `app/Models/Role.php`

```php
use App\Traits\TemUuid;
…
class Role extends SpatieRole
{
    use TemUuid;
}
```

E no docblock, a linha do checklist: `uuid` fica fora do `$fillable` — o spatie usa `$guarded = []`,
então **não há `$fillable`** e o item 3 do checklist de `TemUuid` (`:17`) é vacuously satisfeito;
registrar isso por escrito para o próximo agente não "corrigir".

- **Path**: `tests/BrowserTenancy/CapturaDeArteTest.php:141`

`visit("/admin/shield/roles/{$papel->getKey()}/edit")` → `{$papel->getRouteKey()}`.

- **Auditoria de RQ-09**: `grep -rn "getKey()}/edit\|getKey()}\"" tests/ app/` e conferência de que
  todo model com Resource usa `TemUuid`. Resultado registrado em `03-progresso.md`.

- **Logs**: nenhum. Migration.

### 7. Guard como seleção — RQ-11

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/Roles/RoleResource.php`, substituindo o `TextInput` de
  `guard_name` (`:94-98`)

```php
Select::make('guard_name')
    ->label(__('filament-shield::filament-shield.field.guard_name'))
    /*
     * As chaves de `config('auth.guards')`, e não texto livre: guard é o que amarra o
     * papel ao provider de usuários, e um valor digitado errado cria um papel que nunca
     * casa com ninguém — sem erro nenhum, porque `guard_name` é só uma string na tabela.
     * Hoje o kit tem um guard só (`web`); a lista vem da config para que um projeto que
     * acrescente o seu não precise tocar nesta tela.
     */
    ->options(fn (): array => array_combine(
        array_keys((array) config('auth.guards', [])),
        array_keys((array) config('auth.guards', [])),
    ))
    ->default(Utils::getFilamentAuthGuard())
    ->required()
    ->native(false),
```

`->required()` no lugar de `->nullable()`: `CreateRole@afterCreate` usa
`$this->data['guard_name']` no `firstOrCreate` da permission (`CreateRole.php:44-47`), e guard nulo
ali cria permission com `guard_name` nulo, que `checkPermissionTo()` nunca encontra.

> **Sem `->in()` explícito.** A primeira versão deste passo acrescentava
> `->in(fn () => array_keys(...))` "para travar no servidor". É redundante e pior:
> `Select::getInValidationRuleValues()` (`vendor/filament/forms/src/Components/Select.php:1787-1811`)
> já devolve `[]` quando o state não casa com nenhuma opção, e
> `CanBeValidated::getInValidationRule()` (`:808-815`) transforma isso em `Rule::in([])`. A versão
> nativa também cobre opção desabilitada; a nossa não. Ver `03-progresso.md` → Auditoria
> Pós-Implementação, achado 1.

- **Logs**: nenhum novo — a gravação já loga em `CreateRole@afterCreate` / `EditRole@afterSave`.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação. Nenhum arquivo Blade novo, nenhum
> componente Livewire próprio, nenhuma classe de suporte nova: tudo sai de componente nativo do
> Filament 5 (RQ-13) ou de helper que o kit já tem (`Papeis`, `Paineis`, `TemUuid`).
> Atalhos deliberados marcados com `ponytail:` — hoje um só, a lista sem paginação do passo 3.
> Depois de implementar: `/ponytail:ponytail-review` no diff.
>
> **Caveman** não se aplica aos arquivos 00-06 desta wiki, nem a código, commit ou PR.

## Mapeamentos

Nenhum mapeamento de campo externo.

## Testes

> Ver `04-casos-de-teste.md` (backend e componente) e `05-casos-de-teste-browser.md` (o que só o
> navegador prova).

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse --no-progress`
- [ ] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy`
- [ ] `composer test:browser` (embute `npm run build` e `view:cache`)

## Commits

- `:sparkles: feat(papeis): rotulo, contagem de usuarios e slide-over na tela de papeis`
- `:sparkles: feat(papeis): tab vertical de painel com contador de permissoes selecionadas`
- `:sparkles: feat(papeis): uuid na rota do papel e guard como selecao`
- `:memo: docs(wiki): wiki da feature tela-de-perfis`

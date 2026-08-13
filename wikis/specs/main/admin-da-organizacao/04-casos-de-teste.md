# Casos de Teste — Admin da organização

## Setup Global

### Estratégia de DB

`RefreshDatabase`, herdado de `tests/Pest.php`. Não há escolha: o modo de tenancy muda o
schema — as colunas de team só existem com `permission.teams` ligado antes do migrate — e
`Tests\TestCase` invalida o schema quando o modo troca. É o que permite `--group=kit` rodar
os dois modos no mesmo processo.

| Arquivo | TestCase | Modo |
| --- | --- | --- |
| `tests/Tenancy/AdminDaOrganizacaoTest.php` | `Tests\TenancyTestCase` | multi-tenant — **a suíte principal** |
| `tests/Kit/AdminDaOrganizacaoTest.php` | `Tests\TestCase` | single-tenant — só CT-15 |

Os dois já entram no grupo `kit`, então `composer test:kit` cobre ambos.

### Seeders no setup

```php
beforeEach(function (): void {
    $this->seed(ShieldPermissionsSeeder::class);
    $this->seed(PapeisSeeder::class);
});
```

Mesmo padrão de `tests/Kit/PaineisTest.php`. **Obrigatório**: sem `ShieldPermissionsSeeder`
não há permission no banco, o `PapeisSeeder` semeia papéis vazios e metade dos casos
passaria pelo motivo errado (403 por falta de permission, não pela barreira que se quer
provar).

Ordem importa: o segundo seeder é quem faz a subtração de `panel_user` (ADR-06).

### Helpers — reusar, não reescrever

`tests/Tenancy/TenancyTest.php` já tem, no escopo global do Pest, `tenant()`, `usuario()`,
`projeto()` e — a que importa aqui — **`usuarioComPapel(string $papel, ?Tenant $tenant, string $email)`**,
que já faz a troca de `setPermissionsTeamId()` com `unsetRelation('roles')` nas duas pontas
e restauração no `finally`. **Não escrever uma segunda cópia desse bloco.**

Falta só o caso de atribuir papel a um usuário que **já existe** (a Carla, que tem papel em
duas organizações). Extrair o corpo de `usuarioComPapel()` e fazê-la delegar:

```php
/**
 * Atribui papel DENTRO do contexto de uma organização.
 *
 * É a diferença entre a persona funcionar e a persona entrar num painel vazio: papel
 * gravado em Tenant::CONTEXTO_GLOBAL fica invisível no /app (o wherePivot do spatie).
 * Ver ADR-10.
 */
function papelNaOrganizacao(User $user, string $papel, ?Tenant $tenant): User
```

`usuarioComPapel()` passa a ser `papelNaOrganizacao(usuario($email), $papel, $tenant)` — a
mudança é de duas linhas em `TenancyTest.php` e apaga a duplicação em vez de criá-la.

O cenário dos casos (`cenario(): array`): Acme e Globex; Ana `admin_organizacao` na Acme,
Beto `panel_user` na Acme, Bruno `admin_organizacao` na Globex, Carla `panel_user` nas
duas; cada um `attach`ado à(s) organização(ões) correspondente(s).

`UserFactory` existe e **não** tem state de papel — atribuir depois do `create()`.

### Contexto de painel nos testes de Livewire

Teste de componente não passa pelo middleware do painel, então **duas** coisas precisam ser
fixadas à mão antes de qualquer `livewire(...)` desta feature:

```php
Filament::setCurrentPanel('app');
Filament::setTenant($acme, isQuiet: true);
app(PermissionRegistrar::class)->setPermissionsTeamId($acme->getKey());
```

- `setTenant` é o que `getEloquentQuery()` lê — sem ele, todo caso cairia no ramo
  fail-closed e passaria por CT-14 em vez do que se quer provar;
- `setPermissionsTeamId` é o que `DefinirTenantDePermissoes` faria em request real — sem ele
  o `syncRoles()` gravaria em `CONTEXTO_GLOBAL` e CT-07 passaria por acidente.

`Filament::setTenant(..., isQuiet: true)` é o mesmo uso já feito no caso
`it('deixa o master_global acessar qualquer tenant')` de `TenancyTest.php`.

### Precondições padrão de todo caso

Os dois seeders no `beforeEach` e `cenario()`. Cada caso abaixo diz apenas o que
acrescenta — tipicamente o contexto de painel, quando é teste de componente Livewire.

### Estratégia de Mock

- `Log::shouldReceive('channel')->with('autenticacao')->andReturn($canal)` com
  `Mockery::spy(LoggerInterface::class)` — o padrão já usado em
  `it('registra em log a tentativa de acesso a tenant não vinculado')`. Usado em CT-07,
  CT-08 e CT-14.
- Nada mais é mockado: a feature não fala com serviço externo, não despacha job e não manda
  e-mail (o e-mail do convite é da wiki irmã).

---

## CT-01: o admin da organização entra no painel da organização dele

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/AdminDaOrganizacaoTest.php`
**Método**: `it('abre o painel de negocio da organizacao que administra')`

**Precondições**: as padrão.

### Dados de Entrada

```php
$this->actingAs($ana)->get('/app/acme');
$this->actingAs($ana)->get('/app/acme/users');
```

### Resultado Esperado

- Os dois respondem `200`.
- A segunda rota prova que o Resource novo está registrado, tem permission no banco e é
  alcançável — se qualquer um dos três faltar, é 403.
- Sanidade da fundação: `$ana->canAccessPanel(Filament::getPanel('app'))` é `true` porque
  `admin_organizacao` declara `roles.painel = 'app'`, atribuído no contexto da Acme.

---

## CT-02: ele não entra no `/admin` nem no `/infra`

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/AdminDaOrganizacaoTest.php`
**Método**: `it('nao entra nos paineis de instalacao')` — com `dataset(['/admin', '/infra'])`

**Precondições**: as padrão.

### Dados de Entrada

```php
$this->actingAs($ana)->get($rota);
```

### Resultado Esperado

- `403` nas duas rotas.
- `assertDatabaseHas('model_has_roles', ['model_id' => $ana->id, 'team_id' => $acme->id])`
  — o `403` vem do **painel do papel**, não da ausência de papel. Sem esta asserção o caso
  passaria com um usuário sem papel nenhum e não provaria nada.
- A barreira é da wiki 1 (`canAccessPanel()` exige `roles.painel = 'admin'` atribuído em
  `Tenant::CONTEXTO_GLOBAL`). Este caso **não reimplementa** a barreira — trava que ela
  continua valendo para a persona nova.

---

## CT-03: ele leva 404 no painel de outra organização

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/AdminDaOrganizacaoTest.php`
**Método**: `it('responde 404 no painel de outra organizacao')`

**Precondições**: as padrão.

### Dados de Entrada

```php
$this->actingAs($ana)->get('/app/globex');
```

### Resultado Esperado

- `404`, **não** `403`. Quem barra é `User::canAccessTenant()`, depois de
  `canAccessPanel()` ter dito sim — a mesma propriedade já travada em
  `it('responde 404 — e não 403 — no painel de um tenant não vinculado')`, agora para um
  usuário que **tem** papel de administração em outro lugar.
- 403 confirmaria que a organização existe e bastaria varrer slugs para enumerar clientes.

---

## CT-04: a listagem mostra só os usuários da organização dele

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/AdminDaOrganizacaoTest.php`
**Método**: `it('lista apenas os usuarios da organizacao corrente')`

> Barreira 4 (parte 1).

**Precondições**: padrão + contexto de painel.

### Dados de Entrada

```php
livewire(ListUsers::class)   // App\Filament\App\Resources\Users\Pages\ListUsers
    ->assertCanSeeTableRecords([$ana, $beto, $carla])
    ->assertCanNotSeeTableRecords([$bruno]);
```

### Resultado Esperado

- Ana, Beto e Carla aparecem; Bruno não.
- Carla aparece **de propósito**: ela pertence às duas organizações, e dentro da Acme ela é
  usuária da Acme. É o caso que distingue `whereHas` de um `where('tenant_id', …)` que não
  existiria.
- Complemento direto no mesmo caso, que prova a fonte do recorte sem passar pela tela:
  `expect(UserResource::getEloquentQuery()->pluck('email')->all())->not->toContain('bruno@example.com')`.

---

## CT-05: acesso direto ao registro de um usuário de outra organização é negado

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/AdminDaOrganizacaoTest.php`
**Método**: `it('nega o acesso direto ao registro de usuario de outra organizacao')`

> Barreira 4 (parte 2). **É o caso que a listagem não cobre.**

**Precondições**: as padrão.

### Dados de Entrada

```php
// A URL usa uuid: App\Traits\TemUuid declara getRouteKeyName() = 'uuid'.
$this->actingAs($ana)->get("/app/acme/users/{$bruno->uuid}/edit");   // outra organização
$this->actingAs($ana)->get("/app/acme/users/{$beto->uuid}/edit");    // a dele
```

### Resultado Esperado

- Bruno → `404`. Quem barra é o route binding:
  `resolveRecordRouteBinding()` consulta `getEloquentQuery()`
  (`vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php:41-51`), o
  registro não está na query escopada e o Laravel devolve 404. **Nenhuma linha de código
  foi escrita para isso** — é o que justifica ADR-03 ter posto o filtro em
  `getEloquentQuery()` e não na `table()`.
- Beto → `200`.
- Sem este caso, uma implementação que escopasse só a tabela passaria em CT-04 e vazaria
  aqui.

---

## CT-06: o select de papéis só oferece papéis do painel `app`

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/AdminDaOrganizacaoTest.php`
**Método**: `it('oferece apenas papeis do painel app')`

> Barreira 1.

**Precondições**: padrão + contexto de painel.

### Dados de Entrada

```php
$opcoes = livewire(EditUser::class, ['record' => $beto->uuid])
    ->instance()
    ->getSchemaComponent('form.roles')     // Filament\Forms\Components\Select
    ->getOptions();
```

> **Confirmar a API antes de escrever.** Se `getSchemaComponent()` divergir na 5.7.6, o
> fallback é assertar o HTML da página de edição. **Não** trocar por uma asserção sobre
> `Role::where('painel','app')`: isso testaria o seeder, não o Select.

### Resultado Esperado

- As chaves contêm os ids de `panel_user` e `admin_organizacao`.
- **Não** contêm os ids de `master_global`, `admin` nem `infra`.
- Asserção espelho, ligando a opção ao dado: cada id oferecido tem
  `Role::find($id)->painel === 'app'`. Escrito assim, o caso não quebra quando o kit
  ganhar um quinto papel de painel `app`.

---

## CT-07: o papel é atribuído no contexto da organização, nunca no global

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/AdminDaOrganizacaoTest.php`
**Método**: `it('grava o papel no contexto da organizacao')`

> Barreira 2. É a diferença entre a persona funcionar e ela entrar num painel vazio.

**Precondições**: padrão + contexto de painel no `acme`; `$adminOrg = Role::findByName('admin_organizacao')`.

### Dados de Entrada

```php
livewire(EditUser::class, ['record' => $beto->uuid])
    ->fillForm(['roles' => [$adminOrg->getKey()]])
    ->call('save')
    ->assertHasNoFormErrors();
```

### Resultado Esperado

- `assertDatabaseHas('model_has_roles', ['model_id' => $beto->id, 'role_id' => $adminOrg->getKey(), 'team_id' => $acme->id])`.
- `assertDatabaseMissing('model_has_roles', ['model_id' => $beto->id, 'role_id' => $adminOrg->getKey(), 'team_id' => Tenant::CONTEXTO_GLOBAL])`.
  A segunda asserção é a que importa: `team_id = 0` produziria alguém que entra no `/app`
  e não vê nada (ADR-10).
- Sem `assertRedirect` — tela de edição não redireciona após salvar.
- **E o log da mudança de poder**, no mesmo `call('save')` — não vale um caso próprio,
  vale uma asserção a mais com `actingAs($ana)` e o spy de `Log::channel('autenticacao')`:
  `$canal->shouldHaveReceived('info')` com mensagem começando por
  `[UserResource@saveRelationshipsUsing]` e `context` com `alvo_id` (Beto), `executor_id`
  (Ana), `tenant_id` (Acme) e `papeis` contendo `admin_organizacao`.
  Por que é obrigatório e não decoração: `roles` **não** é `$fillable`, logo
  `AuditsFillables` não cobre a mudança e a trilha de `/infra/audits` fica muda. Este log é
  a única memória de que alguém virou administrador da organização. Nível `info` (mudança
  autorizada), contra o `warning` de CT-08 (tentativa descartada).

**Segunda metade do mesmo caso — a ação do `/admin`** (método
`it('promove a admin da organizacao pelo relation manager')`):

```php
$master = User::where('email', config('kit.admin.email'))->firstOrFail();   // UsuarioAdminSeeder
Filament::setCurrentPanel('admin');

livewire(UsersRelationManager::class, ['ownerRecord' => $acme, 'pageClass' => EditTenant::class])
    ->callAction(TestAction::make('papeisNaOrganizacao')->table($beto), [
        'roles' => [$adminOrg->getKey()],
    ]);
```

> `EditTenant` é `App\Filament\Admin\Resources\Tenants\Pages\EditTenant` — existe. A forma
> `callAction(TestAction::make(…)->table($record), $dados)` é a documentada no `CLAUDE.md`
> do projeto para ações de tabela em Filament 5.

- Mesma dupla de asserções: grava com `team_id = $acme->id`, nunca com `0` — mesmo a ação
  rodando num painel **sem** tenancy, onde o contexto default do processo é
  `Tenant::CONTEXTO_GLOBAL` (fixado por `KitServiceProvider::configureTenancy()`). É a
  troca explícita de contexto do passo 3 do plano que faz isso.
- E o contexto tem de voltar ao normal: depois da ação,
  `expect(app(PermissionRegistrar::class)->getPermissionsTeamId())->toBe(Tenant::CONTEXTO_GLOBAL)`
  — o `finally` do passo 3.

---

## CT-08: ele não promove ninguém a `master_global`, `admin` ou `infra`

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/AdminDaOrganizacaoTest.php`
**Método**: `it('descarta papel de outro painel enviado no payload')`

> Barreira 5. O caso que prova ADR-07: a trava é na escrita, não nas opções.

**Precondições**: padrão + contexto de painel + o spy de `Log::channel('autenticacao')`.

### Dados de Entrada

```php
// Payload forjado: `admin` NÃO está entre as opções do Select, mas o state do Livewire
// vem do cliente. Este é o teste que falha se alguém confiar só no filtro das opções.
livewire(EditUser::class, ['record' => $beto->uuid])
    ->fillForm(['roles' => [
        Role::findByName('panel_user')->getKey(),
        Role::findByName('admin')->getKey(),
        Role::findByName('master_global')->getKey(),
    ]])
    ->call('save');
```

### Resultado Esperado

- `$beto->fresh()` tem `panel_user` e **só** ele no contexto da Acme.
- `assertDatabaseMissing('model_has_roles', ['model_id' => $beto->id, 'role_id' => Role::findByName('admin')->getKey()])`
  — em **qualquer** `team_id`.
- Idem para `master_global`.
- O `warning` chegou: mensagem começa com
  `[UserResource@saveRelationshipsUsing]`, `context['motivo'] === 'papel_de_outro_painel'`,
  e `context['ids_enviados']` tem 3 itens contra 1 em `context['ids_aceitos']`.
- Reforço final, atravessando a fronteira toda:
  `$this->actingAs($beto->fresh())->get('/admin')->assertForbidden()`.

---

## CT-09: ele não cria nem edita papéis

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/AdminDaOrganizacaoTest.php`
**Método**: `it('nao alcanca a administracao de papeis')`

> Barreira 3. Prova ADR-02.

**Precondições**: as padrão.

### Dados de Entrada

```php
$resources = Filament::getPanel('app')->getResources();
```

### Resultado Esperado

- Nenhum elemento de `$resources` tem `class_basename($r) === 'RoleResource'` — nem o do
  projeto (`App\Filament\Admin\Resources\Roles\RoleResource`, publicado pela wiki 1) nem o
  do vendor. Escrito por `class_basename` para pegar as duas de uma vez.
- `expect($ana->can('Create:Role'))->toBeFalse()` e `->and($ana->can('Update:Role'))->toBeFalse()`
  — `*:Role` pertence a `Paineis::permissoes('admin')`, e `admin_organizacao` recebe
  `permissoes('app')`.
- Sem asserção de `GET /admin/shield/roles` → 403: quem barra ali é o `canAccessPanel` do
  `/admin`, que **CT-02 já trava**. Repetir aqui não prova nada sobre papéis.
- Este é o caso que falha se alguém "completar" a feature registrando o `RoleResource` no
  painel `app` — o cenário em que criar um papel dentro de uma organização o tornaria
  visível em todas as outras.

---

## CT-10: o convite nasce com a organização de quem o criou, ignorando o formulário

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/AdminDaOrganizacaoTest.php`
**Método**: `it('carimba a organizacao no convite ignorando o formulario')`
**Bloqueado por**: wiki `convite-de-usuario` (o model `Convite`)

> Barreira 6. A garantia é o `mutateFormDataBeforeCreate` do passo 4 — este teste é o que
> a impede de sumir, e o que acusa se a wiki irmã mexer no `$fillable` do `Convite`.

**Precondições**: padrão + contexto de painel no `acme`.

### Dados de Entrada

```php
livewire(CreateConvite::class)
    ->fillForm([
        'email'     => 'novo@example.com',
        'roles'     => [Role::findByName('panel_user')->getKey()],
        // Forjado: o campo não existe no formulário. Se um dia alguém puser `tenant_id`
        // no $fillable do Convite, é aqui que aparece.
        'tenant_id' => $globex->id,
    ])
    ->call('create')
    ->assertHasNoFormErrors();
```

### Resultado Esperado

- `assertDatabaseHas('convites', ['email' => 'novo@example.com', 'tenant_id' => $acme->id])`.
- `assertDatabaseMissing('convites', ['tenant_id' => $globex->id])`.
- Quem garante é o `mutateFormDataBeforeCreate` da `CreateConvite` do `/app`, que
  sobrescreve com `Filament::getTenant()`. **O `$fillable` do `Convite` não protege**: o
  model implementado tem `tenant_id` dentro dele, para o Select do `/admin` funcionar.
- Complemento da leitura: com o tenant corrente na Globex, o `getEloquentQuery()` do
  `ConviteResource` do `/app` devolve `0`.

---

## CT-11: o usuário criado nasce vinculado à organização de quem o criou

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/AdminDaOrganizacaoTest.php`
**Método**: `it('vincula o usuario criado a organizacao corrente')`

**Precondições**: padrão + contexto de painel no `acme`.

### Dados de Entrada

```php
livewire(CreateUser::class)   // App\Filament\App\Resources\Users\Pages\CreateUser
    ->fillForm([
        'name'     => 'Fulano',
        'email'    => 'fulano@example.com',
        'password' => 'password1234',
        'roles'    => [Role::findByName('panel_user')->getKey()],
    ])
    ->call('create')
    ->assertHasNoFormErrors();

$novo = User::where('email', 'fulano@example.com')->firstOrFail();
```

### Resultado Esperado

- `assertDatabaseHas('tenant_user', ['user_id' => $novo->id, 'tenant_id' => $acme->id])`.
- `assertDatabaseMissing('tenant_user', ['user_id' => $novo->id, 'tenant_id' => $globex->id])`.
- `assertDatabaseHas('model_has_roles', ['model_id' => $novo->id, 'team_id' => $acme->id])`.
- O novo usuário aparece na listagem do CT-04 — ou seja, o `afterCreate` roda antes de
  qualquer leitura subsequente. Sem ele, o usuário nasceria órfão: criado, mas invisível na
  própria tela que o criou.
- E, negativamente: `expect($novo->canAccessTenant($globex))->toBeFalse()`.

---

## CT-12: `panel_user` não alcança a administração de usuários

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/AdminDaOrganizacaoTest.php`
**Método**: `it('mantem o usuario comum fora da administracao da organizacao')`

> Prova ADR-06 — a subtração no `PapeisSeeder`. **Falha se ninguém a implementar**, e
> falha em silêncio na produção.

**Precondições**: as padrão — Beto é `panel_user` na Acme.

### Dados de Entrada

```php
$this->actingAs($beto)->get('/app/acme');           // o painel
$this->actingAs($beto)->get('/app/acme/users');     // a tela de administração
$this->actingAs($beto)->get('/app/acme/convites');
```

### Resultado Esperado

- `/app/acme` → `200`. Ele é usuário do negócio e continua entrando.
- `/app/acme/users` → `403`.
- `/app/acme/convites` → `403`.
- No dado, que é onde a regressão nasce:
  `expect($beto->can('ViewAny:User'))->toBeFalse()`,
  `->and($beto->can('Create:User'))->toBeFalse()`,
  `->and($beto->can('Create:Convite'))->toBeFalse()`.
- E o contraste que prova que a subtração não subtraiu demais:
  `expect($beto->can('ViewAny:Projeto'))->toBeTrue()` — o Resource de negócio continua
  liberado.

---

## CT-13: editar um usuário compartilhado não apaga os papéis dele na outra organização

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/AdminDaOrganizacaoTest.php`
**Método**: `it('preserva os papeis do usuario nas outras organizacoes')`

**Precondições**: padrão + contexto de painel no `acme` — Carla é `panel_user` nas duas.

### Dados de Entrada

```php
livewire(EditUser::class, ['record' => $carla->uuid])
    ->fillForm(['roles' => [Role::findByName('admin_organizacao')->getKey()]])
    ->call('save')
    ->assertHasNoFormErrors();
```

### Resultado Esperado

- Na Acme: Carla tem `admin_organizacao` e **não** tem mais `panel_user`
  (`assertDatabaseMissing` com `team_id = $acme->id`).
- Na Globex: `assertDatabaseHas('model_has_roles', ['model_id' => $carla->id, 'role_id' => $panelUser->getKey(), 'team_id' => $globex->id])`
  — **intacto**.
- Quem garante é o spatie, não este código: `syncRoles()` chama `detachRoles()`, que apaga
  pela pivot query escopada pelo team corrente
  (`vendor/spatie/laravel-permission/src/Traits/HasRoles.php:213-233`, com o `wherePivot`
  de `:75-76`). **É comportamento de vendor** — daí o teste, que acusa se um upgrade
  mudar isso. Sem ele, uma edição na Acme silenciosamente derrubaria o acesso da Carla na
  Globex.

---

## CT-14: sem organização corrente, a consulta de usuários fecha

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/AdminDaOrganizacaoTest.php`
**Método**: `it('fecha a consulta de usuarios quando nao ha organizacao corrente')`

> Fail-closed. É o argumento decisivo de ADR-03 contra o escopo nativo do Filament, que no
> mesmo cenário devolveria a base inteira.

**Precondições**: padrão + o spy de log, e **`Filament::setTenant(null, isQuiet: true)`** com
`setCurrentPanel('app')` — o estado de um job, um comando ou um `pulse:check`.

### Dados de Entrada

```php
UserResource::getEloquentQuery()->count();
```

### Resultado Esperado

- `0`. Não "todos", não exception.
- O `warning` chegou: mensagem começando por `[UserResource@getEloquentQuery]`, com
  `context['motivo'] === 'sem_tenant_corrente'`.
- Nível `warning` e não `error`: é condição anômala esperada, não falha de sistema — a
  mesma severidade do `warning` de `User::canAccessTenant()`.
- Contraste no mesmo caso: com `Filament::setTenant($acme)`, a mesma query devolve `3`.

---

## CT-15: sem multi-tenancy, a persona não existe

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/AdminDaOrganizacaoTest.php` (single-tenant)
**Método**: `it('nao semeia o admin da organizacao sem tenancy')`

> Prova ADR-09.

**Precondições**: os dois seeders; modo single-tenant garantido por `Tests\TestCase`.

### Dados de Entrada

```php
Role::where('name', 'admin_organizacao')->exists();

$user = usuarioCom('panel_user');            // helper já existente em tests/Kit/PaineisTest.php
$this->actingAs($user)->get('/app/users');
```

### Resultado Esperado

- O papel **não existe** no banco.
- `GET /app/users` → `403`. Quem barra é `UserResource::canAccess()`, que devolve `false`
  quando `config('kit.tenancy.enabled')` é falso — o mesmo par usado em
  `TenantResource`.
- `expect(UserResource::shouldRegisterNavigation())->toBeFalse()` — o item não aparece no
  menu nem na busca ⌘K.
- **E a subtração continua valendo**, que é a parte contraintuitiva:
  `expect($user->can('ViewAny:User'))->toBeFalse()`. Os Resources são descobertos e têm
  permission gerada mesmo em single-tenant, então `panel_user` os herdaria se a subtração
  fosse condicional à tenancy.

---

## CT-16: o Resource não é escopado pelo Filament, e o painel boota

**Tipo**: `Unit`
**Arquivo**: `tests/Tenancy/AdminDaOrganizacaoTest.php`
**Método**: `it('nao delega o escopo de usuario ao Filament')`

**Precondições**: nenhuma além do boot da aplicação.

### Dados de Entrada

```php
UserResource::isScopedToTenant();

// Só com tenancy: é aqui que o escopo nativo estouraria.
Filament::setCurrentPanel('app');
Filament::setTenant(tenant('Acme', 'acme'), isQuiet: true);
User::query()->count();
```

### Resultado Esperado

- `isScopedToTenant()` é `false`.
- `User::query()->count()` **não lança**. Se alguém remover
  `protected static bool $isScopedToTenant = false;`, `Panel::boot()` registra o global
  scope (`vendor/filament/filament/src/Panel.php:84-91`) e a query morre com
  `LogicException: The model [App\Models\User] does not have a relationship named [tenant].`
  (`vendor/filament/filament/src/Resources/Resource/Concerns/BelongsToTenant.php:99`).
- E o efeito colateral que a mesma remoção traria: `User::hasGlobalScope('app_tenancy')`
  é `false` (o nome vem de `HasTenancy::getTenancyScopeName()`,
  `vendor/filament/filament/src/Panel/Concerns/HasTenancy.php:512-515`). O model `User` não
  pode ganhar escopo global — ele é consultado pelo guard de autenticação e pelo `/admin`.
- **Só na suíte de tenancy.** Havia um gêmeo em `tests/Kit` que assertava apenas
  `isScopedToTenant() === false` em single-tenant: é a leitura de uma propriedade estática
  constante, que não muda com o modo e não exercita o `Panel::boot()`. Cortado.

---

## CT-17: do painel `app` não se exclui usuário

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/AdminDaOrganizacaoTest.php`
**Método**: `it('nao permite excluir usuario a partir da organizacao')`

> Prova ADR-08.

**Precondições**: padrão + contexto de painel.

### Dados de Entrada

```php
livewire(ListUsers::class)
    ->assertActionDoesNotExist(TestAction::make('delete')->table($beto));

UserResource::canDelete($beto);
UserResource::canDeleteAny();
```

> **Confirmar a API antes de escrever**: em Filament 5 as ações de tabela se endereçam por
> `Filament\Actions\Testing\TestAction::make('delete')->table($record)` (é a forma que o
> `CLAUDE.md` do projeto documenta para `callAction`). Se o helper de negação tiver outro
> nome na 5.7.6, as duas asserções de `canDelete()`/`canDeleteAny()` **já bastam como
> trava** — a asserção de tabela é a cereja, não a garantia.

### Resultado Esperado

- A ação de exclusão não existe na tabela nem no header da página de edição.
- `canDelete()` e `canDeleteAny()` devolvem `false` — mesmo com `admin_organizacao` tendo
  a permissão `Delete:User` (a matriz é a do painel inteiro).
- `assertDatabaseHas('users', ['id' => $beto->id])` ao final.
- Complemento que explica o porquê no próprio teste: excluir a Carla apagaria o vínculo
  dela com a Globex por `cascadeOnDelete`
  (`cascadeOnDelete` na migration de `tenant_user`) — uma operação
  de dentro da Acme atravessando a fronteira da organização.

---

## Índice de Casos

| ID | Cenário | Barreira | Tipo | Arquivo |
| --- | --- | --- | --- | --- |
| CT-01 | entra em `/app/{slug dele}` | — | Feature | `tests/Tenancy/AdminDaOrganizacaoTest.php` |
| CT-02 | 403 em `/admin` e `/infra` | — | Feature | `tests/Tenancy/AdminDaOrganizacaoTest.php` |
| CT-03 | 404 em `/app/{outro slug}` | — | Feature | `tests/Tenancy/AdminDaOrganizacaoTest.php` |
| CT-04 | listagem só da organização dele | **4a** | Feature | `tests/Tenancy/AdminDaOrganizacaoTest.php` |
| CT-05 | registro de outra organização por URL direta | **4b** | Feature | `tests/Tenancy/AdminDaOrganizacaoTest.php` |
| CT-06 | select só oferece papéis `painel = 'app'` | **1** | Feature | `tests/Tenancy/AdminDaOrganizacaoTest.php` |
| CT-07 | atribuição no contexto do tenant, e o log dela | **2** | Feature | `tests/Tenancy/AdminDaOrganizacaoTest.php` |
| CT-08 | payload com papel de outro painel é descartado | **5** | Feature | `tests/Tenancy/AdminDaOrganizacaoTest.php` |
| CT-09 | não cria nem edita papéis | **3** | Feature | `tests/Tenancy/AdminDaOrganizacaoTest.php` |
| CT-10 | convite carimba o `tenant_id` dele | **6** | Feature | `tests/Tenancy/AdminDaOrganizacaoTest.php` |
| CT-11 | usuário criado nasce vinculado | — | Feature | `tests/Tenancy/AdminDaOrganizacaoTest.php` |
| CT-12 | `panel_user` fora da administração | — | Feature | `tests/Tenancy/AdminDaOrganizacaoTest.php` |
| CT-13 | papéis de outra organização preservados | — | Feature | `tests/Tenancy/AdminDaOrganizacaoTest.php` |
| CT-14 | sem tenant, query fecha + log | — | Feature | `tests/Tenancy/AdminDaOrganizacaoTest.php` |
| CT-15 | sem tenancy, a persona não existe | — | Feature | `tests/Kit/AdminDaOrganizacaoTest.php` |
| CT-16 | `$isScopedToTenant = false` e o painel boota | — | Unit | `tests/Tenancy/AdminDaOrganizacaoTest.php` |
| CT-17 | não exclui usuário do `/app` | — | Feature | `tests/Tenancy/AdminDaOrganizacaoTest.php` |

## Testes existentes que mudam de expectativa

| Caso em `tests/Tenancy/TenancyTest.php` | O que muda |
| --- | --- |
| `it('cria o cenário completo da demo, de forma idempotente')` | O `DemoTenancySeeder` passa a atribuir `admin_organizacao` à Ana (passo 5). As contagens atuais continuam válidas; **acrescentar** a asserção do papel da Ana na Acme e de que rodar o seeder duas vezes não duplica a atribuição. |
| `it('mantém admin e infra fora do escopo de tenant')` | Continua verde. Conferir que é **pelo motivo certo**: quem entra é o `master_global`, não o escopo novo (que não existe no `/admin`). |
| `it('exige papel no contexto global para os painéis da instalação')` | Continua verde e é o irmão de CT-02: aquele prova que papel `admin` num tenant não abre `/admin`; CT-02 prova o mesmo para o papel `painel = 'app'`. |
| `tests/Kit/PaineisTest.php` | Nenhuma mudança. `admin_organizacao` não é semeado em single-tenant (CT-15). |

## Ordem de escrita recomendada

**CT-16 → CT-12 → CT-05 → CT-04 → o resto do índice.** Os três primeiros têm de ser vistos
**falhando** antes do código: CT-16 falha com o `LogicException` literal (prova que a
armadilha de ADR-03 é real); CT-12 falha porque `panel_user` herda a matriz nova (prova que
ADR-06 era necessária); e escrever CT-05 antes de CT-04 é o que faz aparecer a diferença
entre escopar na `table()` e no `getEloquentQuery()`.

# Casos de Teste — Perfil × permissão × acesso ao painel

## Setup Global

### Estratégia de DB

`RefreshDatabase`, herdado do `tests/Pest.php` (`:34-37` para `tests/Kit`, `:58-61` para
`tests/Tenancy`). Não há escolha a fazer: o modo de tenancy muda o schema (as colunas de
team só existem com `permission.teams` ligado), e `Tests\TestCase::setUp()` já invalida
`RefreshDatabaseState::$migrated` quando o modo troca (`tests/TestCase.php:126-134`).

Os arquivos de teste vão em `tests/Kit/` (single-tenant, `Tests\TestCase`) e
`tests/Tenancy/` (multi-tenant, `Tests\TenancyTestCase`). Os dois já entram no grupo
`kit`, então `composer test:kit` cobre os dois.

### Seeders no setup

```php
beforeEach(function (): void {
    $this->seed(ShieldPermissionsSeeder::class);
    $this->seed(PapeisSeeder::class);
});
```

Mesmo padrão de `tests/Kit/PaineisTest.php:13-15`. É obrigatório: sem
`ShieldPermissionsSeeder` não há permission no banco e o `PapeisSeeder` semeia papéis
vazios — os casos passariam por motivo errado.

### Factories / Fixtures

- `User::factory()->create()` — a factory existe (`Database\Factories\UserFactory`,
  referenciada em `app/Models/User.php:31`). **Conferir os states disponíveis antes de
  escrever o teste**; se não houver state de papel, atribuir com `assignRole()` depois do
  `create()`, como faz `tests/Kit/PaineisTest.php:17-30`.
- `Tenant::factory()->create(['slug' => 'acme'])` — existe
  (`database/factories/TenantFactory.php`, listada em `KitUpdate::CAMINHOS_DO_KIT`).
- Helper local, espelhando `PaineisTest::usuarioCom()`:

```php
function usuarioComPapel(?string $papel, ?Tenant $tenant = null): User
{
    $user = User::factory()->create();

    if ($papel !== null) {
        $registrar = app(PermissionRegistrar::class);
        $anterior  = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($tenant?->getKey() ?? Tenant::CONTEXTO_GLOBAL);

        $user->assignRole($papel);

        $registrar->setPermissionsTeamId($anterior);
    }

    return $user;
}
```

Sem tenancy o `setPermissionsTeamId()` é inofensivo (o spatie ignora quando
`permission.teams` está `false`), então o mesmo helper serve nas duas suítes.

### Estratégia de Mock

- `Log::spy()` nos CTs de log (CT-04). Nada mais é mockado: a feature não fala com
  serviço externo, não despacha job e não manda e-mail.

---

## CT-01: master_global entra nos três painéis

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/PerfilEAcessoTest.php`
**Método**: `it('master_global entra nos tres paineis')`

### Precondições

- Seeders no `beforeEach`.
- Usuário com o papel `master_global`.

### Dados de Entrada

```
actingAs($user)->get('/admin'), get('/app'), get('/infra')
```

### Resultado Esperado

- Os três respondem `200`.
- `$user->canAccessPanel($painel)` é `true` nos três — não pela coluna (`painel` é nulo,
  e nulo não casa com painel algum), mas porque `isMasterGlobal()` corta antes da
  comparação. Ver ADR-03.

---

## CT-02: cada papel entra só no painel que declara

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/PerfilEAcessoTest.php`
**Método**: `it('papel abre so o painel que declara')` — com `dataset`

### Precondições

- Seeders no `beforeEach`.

### Dados de Entrada

```php
dataset('papel_x_painel', [
    ['admin',      '/admin', 200], ['admin',      '/infra', 403], ['admin',      '/app', 403],
    ['infra',      '/infra', 200], ['infra',      '/admin', 403], ['infra',      '/app', 403],
    ['panel_user', '/app',   200], ['panel_user', '/admin', 403], ['panel_user', '/infra', 403],
]);
```

### Resultado Esperado

- Cada linha responde o status esperado.
- É o caso que prova a inversão de comportamento: hoje `panel_user` levaria `200` em
  `/app` **e** todo usuário autenticado também. Com a mudança, `admin` e `infra` levam
  `403` no `/app`, porque os papéis deles declaram outro painel.

---

## CT-03: usuário sem papel nenhum não entra em painel nenhum

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/PerfilEAcessoTest.php`
**Método**: `it('usuario sem papel leva 403 nos tres paineis')`

### Precondições

- Seeders no `beforeEach`.
- `User::factory()->create()`, sem `assignRole()`.

### Dados de Entrada

```
actingAs($user)->get('/admin'), get('/app'), get('/infra')
```

### Resultado Esperado

- Os três respondem `403`.
- Este é o caso central da feature. Ele **falha** contra o código atual em `/app`
  (`canAccessPanel()` devolve `'app' => true`) — escrever e ver falhar antes de
  implementar.

---

## CT-04: negativa de painel vira log com motivo

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/PerfilEAcessoTest.php`
**Método**: `it('nega painel e registra o motivo no log')`

### Precondições

- Seeders no `beforeEach`.
- `Log::spy()`.
- Usuário com `panel_user`.

### Dados de Entrada

```
actingAs($user)->get('/admin')
```

### Resultado Esperado

- `Log::shouldHaveReceived('channel')->with('autenticacao')` ao menos uma vez.
- O `warning` recebido tem mensagem começando por `[User@canAccessPanel]` e context com
  `motivo = 'sem_papel_do_painel'`, `painel = 'admin'` e `user_id` do usuário.
- Espelha o teste que já existe para `canAccessTenant` (`tests/Tenancy/TenancyTest.php:94-110`).

---

## CT-05: `Paineis::permissoes()` devolve conjuntos diferentes por painel

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/PerfilEAcessoTest.php`
**Método**: `it('mapeia permissoes por painel')`

### Precondições

- Seeders no `beforeEach`.

### Dados de Entrada

```php
Paineis::permissoes('admin'); Paineis::permissoes('app'); Paineis::permissoes('infra');
```

### Resultado Esperado

- `permissoes('admin')` contém `ViewAny:User` e `ViewAny:Role`.
- `permissoes('infra')` **não** contém `ViewAny:User`.
- `permissoes('infra')` contém pelo menos uma chave de `AiRun`.
- Os três conjuntos não são idênticos entre si — é o que prova que o
  `forgetInstance('filament-shield')` está funcionando. Sem ele os três voltam iguais.

---

## CT-06: as permissões dos três painéis existem no banco

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/PerfilEAcessoTest.php`
**Método**: `it('gera permission para os tres paineis')`

### Precondições

- Seeders no `beforeEach`.

### Dados de Entrada

```php
Permission::pluck('name');
```

### Resultado Esperado

- Contém `ViewAny:Projeto` (Resource do painel `app`) e ao menos uma permission de
  Resource do painel `infra`.
- Falha contra o código atual: `ShieldPermissionsSeeder` só roda `--panel=admin`.

---

## CT-07: matriz do `PapeisSeeder` é exatamente a do painel

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/PerfilEAcessoTest.php`
**Método**: `it('recorta a matriz de papeis pelo painel')`

### Precondições

- Seeders no `beforeEach`.

### Dados de Entrada

```php
Role::findByName('admin')->permissions->pluck('name');
Role::findByName('master_global')->permissions;
```

### Resultado Esperado

- `master_global` continua com **zero** permissions (o poder vem do `Gate::before`).
- `Role::findByName('admin')->painel === 'admin'`; `master_global` tem `painel` nulo.
- O conjunto de `admin` contém `ViewAny:User` e **não** contém permission de `AiRun`.

> Sem asserção do tipo `permissions == Paineis::permissoes('admin')`: com o seeder
> implementado como `syncPermissions(Paineis::permissoes(…))`, ela seria tautologia. Quem
> prova o recorte é CT-05, sobre o mapa; aqui só se prova que o seeder o usa.

---

## CT-08: papel com painel nulo não abre painel algum

**Tipo**: `Unit`
**Arquivo**: `tests/Kit/PerfilEAcessoTest.php`
**Método**: `it('papel sem painel vale em qualquer painel')`

### Precondições

- Seeders no `beforeEach`.
- Papel `auditor` criado com `painel = null` e sem permissions; usuário com ele.

### Dados de Entrada

```php
$user->canAccessPanel(Filament::getPanel('admin'));
```

### Resultado Esperado

- `false`. **O coringa é do `master_global`, não da coluna.** `canAccessPanel()` compara
  `roles.painel` com o id do painel; nulo não casa com nada.
- Trava a leitura de ADR-03: nulo significa "não abre painel algum", e o acesso
  irrestrito do `master_global` vem do `isMasterGlobal()`, que corta antes da comparação.
  Se alguém implementar nulo como "entra em todos", este caso falha — e é a implementação
  que a intuição sugere primeiro.

---

## CT-09: tela de papéis agrupa por painel

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/PerfilEAcessoTest.php`
**Método**: `it('a tela de papeis mostra um grupo por painel')`

### Precondições

- Seeders no `beforeEach`.
- Usuário `master_global` autenticado.

### Dados de Entrada

```
actingAs($user)->get('/admin/shield/roles/create')
```

### Resultado Esperado

- `200`.
- O HTML contém `Painel /admin`, `Painel /app` e `Painel /infra`.
- Prova em par com CT-10: a tela renderiza **e** o Resource publicado é o que responde.

---

## CT-10: o Resource de papéis é o do projeto

**Tipo**: `Unit`
**Arquivo**: `tests/Kit/PerfilEAcessoTest.php`
**Método**: `it('usa o RoleResource publicado no projeto')`

### Precondições

- Nenhuma além do boot da aplicação.

### Dados de Entrada

```php
Filament::getPanel('admin')->getResources();
```

### Resultado Esperado

- Contém `App\Filament\Admin\Resources\Roles\RoleResource`.
- **Não** contém `BezhanSalleh\FilamentShield\Resources\Roles\RoleResource`.
- Falha alto se um upgrade do Shield voltar a registrar o Resource dele — que é o
  cenário em que a tela agrupada some sem ninguém perceber.

---

## CT-11: gravar papel preserva o painel e não cria permission fantasma

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/PerfilEAcessoTest.php`
**Método**: `it('salva o painel do papel sem virar permission')`

### Precondições

- Seeders no `beforeEach`.
- Usuário `master_global`.

### Dados de Entrada

```php
livewire(CreateRole::class)
    ->fillForm(['name' => 'suporte', 'guard_name' => 'web', 'painel' => 'app'])
    ->call('create')
    ->assertHasNoFormErrors();
```

### Resultado Esperado

- `assertDatabaseHas('roles', ['name' => 'suporte', 'painel' => 'app'])`.
- `Permission::where('name', 'app')->doesntExist()` — se `painel` não for acrescentado à
  lista de exclusão do `mutateFormDataBeforeCreate`, o `afterCreate` do Shield cria uma
  permission chamada `app`. É a falha silenciosa mais provável de todo este plano.
- Repetir para `EditRole::class` com `->call('save')` (sem `assertRedirect`: tela de
  edição não redireciona).

---

## CT-12: papel do painel `app` vale em qualquer organização

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/PerfilEAcessoTenancyTest.php`
**Método**: `it('papel do painel app vale em qualquer organizacao')`

### Precondições

- Seeders no `beforeEach`.
- Duas organizações (`acme`, `globex`).
- Usuário vinculado só a `acme`, com `panel_user` atribuído **no contexto de `acme`**.

### Dados de Entrada

```
actingAs($user)->get('/app/acme')
actingAs($user)->get('/app/globex')
```

### Resultado Esperado

- `/app/acme` → `200`.
- `/app/globex` → **`404`**, não `403` — a propriedade já travada em
  `tests/Tenancy/TenancyTest.php:205-216` continua valendo. Quem barra é
  `canAccessTenant()`, depois de `canAccessPanel()` ter dito sim.
- Prova o ramo `$panel->hasTenancy() ? null : ...` de ADR-04: o papel está atribuído a
  `acme`, não ao contexto global, e ainda assim o painel abre.

---

## CT-13: papel de organização não abre `/admin`

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/PerfilEAcessoTenancyTest.php`
**Método**: `it('papel admin dentro de uma organizacao nao abre o painel admin')`

### Precondições

- Seeders no `beforeEach`.
- Organização `acme`.
- Usuário com o papel `admin` atribuído **no contexto de `acme`** (não no global).

### Dados de Entrada

```
actingAs($user)->get('/admin')
```

### Resultado Esperado

- `403`.
- `assertDatabaseHas('model_has_roles', ['team_id' => $acme->id])` — confirma que a
  atribuição existe, e que o `403` vem do contexto, não da ausência de papel.
- É a propriedade de segurança de ADR-04. Sem este caso, alguém "simplifica"
  `canAccessPanel()` para ignorar o contexto e ninguém percebe.

---

## CT-14: criar usuário sem papel é erro de formulário

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/PerfilEAcessoTest.php`
**Método**: `it('exige papel ao criar usuario')`

### Precondições

- Seeders no `beforeEach`.
- Usuário `master_global`.

### Dados de Entrada

```php
livewire(CreateUser::class)
    ->fillForm(['name' => 'Fulano', 'email' => 'fulano@example.com', 'password' => 'secret1234'])
    ->call('create');
```

### Resultado Esperado

- `->assertHasFormErrors(['roles' => 'required'])`.
- `User::where('email', 'fulano@example.com')->doesntExist()`.

---

## CT-15: com tenancy, criar usuário exige organização

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/PerfilEAcessoTenancyTest.php`
**Método**: `it('exige organizacao ao criar usuario')`

### Precondições

- Seeders no `beforeEach`.
- Organização `acme`.
- Usuário `master_global`.

### Dados de Entrada

```php
// sem `tenants`
livewire(CreateUser::class)->fillForm([... , 'roles' => [$panelUser->id]])->call('create');
// com `tenants`
livewire(CreateUser::class)->fillForm([... , 'tenants' => [$acme->id]])->call('create');
```

### Resultado Esperado

- Primeiro: `assertHasFormErrors(['tenants' => 'required'])`.
- Segundo: `assertHasNoFormErrors()` e `assertDatabaseHas('tenant_user', ['tenant_id' => $acme->id, 'user_id' => $novo->id])`.
- Cobre o pedido "adicionar ao cadastro do usuário o tenant a que ele pertence, para
  evitar acesso indevido a outros dados".

---

## CT-16: `app/Support` está coberto pelo `kit:update`

**Tipo**: `Unit`
**Arquivo**: já existe — `tests/Kit/KitUpdateTest.php`

### Precondições

Nenhuma. O teste já varre a árvore do kit (`:136`) e cobra que todo arquivo sob `app/`
esteja coberto por `CAMINHOS_DO_KIT`.

### Resultado Esperado

- Falha **antes** de `app/Support` entrar na constante, passa depois. Rodar nessa ordem
  é o que prova que a varredura funciona — foi a lição da versão 0.9.8, onde o teste era
  uma lista à mão e deixou metade do Filament de fora.

---

## Índice de Casos

| ID | Cenário | Tipo | Arquivo |
| --- | --- | --- | --- |
| CT-01 | master_global nos três painéis | Feature | `tests/Kit/PerfilEAcessoTest.php` |
| CT-02 | papel abre só o painel que declara | Feature | `tests/Kit/PerfilEAcessoTest.php` |
| CT-03 | sem papel, 403 nos três | Feature | `tests/Kit/PerfilEAcessoTest.php` |
| CT-04 | negativa vira log com motivo | Feature | `tests/Kit/PerfilEAcessoTest.php` |
| CT-05 | mapa de permissões por painel | Feature | `tests/Kit/PerfilEAcessoTest.php` |
| CT-06 | permission dos três painéis no banco | Feature | `tests/Kit/PerfilEAcessoTest.php` |
| CT-07 | matriz do papel = matriz do painel | Feature | `tests/Kit/PerfilEAcessoTest.php` |
| CT-08 | painel nulo não abre painel | Unit | `tests/Kit/PerfilEAcessoTest.php` |
| CT-09 | tela de papéis agrupa por painel | Feature | `tests/Kit/PerfilEAcessoTest.php` |
| CT-10 | RoleResource publicado é o registrado | Unit | `tests/Kit/PerfilEAcessoTest.php` |
| CT-11 | painel salva sem virar permission | Feature | `tests/Kit/PerfilEAcessoTest.php` |
| CT-12 | papel `app` vale em qualquer organização | Feature | `tests/Tenancy/PerfilEAcessoTenancyTest.php` |
| CT-13 | papel de organização não abre `/admin` | Feature | `tests/Tenancy/PerfilEAcessoTenancyTest.php` |
| CT-14 | papel obrigatório ao criar usuário | Feature | `tests/Kit/PerfilEAcessoTest.php` |
| CT-15 | organização obrigatória ao criar usuário | Feature | `tests/Tenancy/PerfilEAcessoTenancyTest.php` |
| CT-16 | `app/Support` no `kit:update` | Unit | `tests/Kit/KitUpdateTest.php` (já existe) |

## Testes existentes que mudam de expectativa

| Arquivo | Caso | O que muda |
| --- | --- | --- |
| `tests/Kit/PaineisTest.php:52-54` | `/app` aberto a autenticado | Inverte: usuário sem papel passa a levar `403`. Reescrever para usar `panel_user`. |
| `tests/Kit/PaineisTest.php:85-89` | 403 no painel errado | Continua verde; conferir que continua por motivo certo. |
| `tests/Tenancy/TenancyTest.php:195-203` | `GET /app/{slug}` responde 200 | O usuário do setup precisa de `panel_user` no contexto do tenant. |
| `tests/Tenancy/TenancyTest.php:230-260` | `EditUser` grava papéis | O form ganha `tenants` obrigatório — o `fillForm` precisa incluí-lo. |

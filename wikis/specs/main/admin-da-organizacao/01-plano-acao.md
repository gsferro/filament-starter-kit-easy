# Plano de Ação — Admin da organização

## Objetivo

Criar a persona que hoje não existe: alguém que administra **uma organização** sem
administrar **a instalação**. O papel `admin_organizacao` vive no painel `/app`, nunca
entra no `/admin`, e dentro do `/app` ganha duas telas novas — usuários e convites —
recortadas à organização corrente.

O trabalho é 80% de recorte e 20% de tela. O recorte é a parte perigosa: `User` **não
tem `tenant_id`**. O vínculo é a pivot many-to-many `tenant_user`
(`database/migrations/0001_01_01_000021_create_tenant_user_table.php`), e nem a
trait `App\Traits\BelongsToTenant` nem o escopo nativo do Filament sabem lidar com isso —
os dois pressupõem uma relação de posse direta. Este plano diz exatamente onde o filtro
entra, por que ele falha fechado, e enumera seis barreiras contra escalada de privilégio
com um caso de teste para cada.

## Contexto

### O que o usuário pediu, nas palavras dele

> "O painel /admin é o admin geral da aplicação. Quando for multi-tenancy, tem que ter um
> 'admin' dentro do painel app, que NÃO vai acessar o /admin mas sim ter mais permissões
> em /app, podendo criar usuários e usar o convite, vendo somente os usuários e permissões
> correspondentes ao tenancy dele e pertencentes ao painel app."

### O que a fundação já resolve (wiki `perfil-e-acesso-ao-painel`)

Esta wiki **constrói em cima** da anterior e não re-decide nada dela:

> Conferido contra a árvore **com a wiki 1 já aplicada** (migration `2026_08_13_000001`,
> `app/Support/Paineis.php`, `app/Filament/Admin/Resources/Roles/` e o `PapeisSeeder`
> reescrito). Código do kit é citado por símbolo; só vendor leva faixa de linha, porque a
> versão está travada no `composer.lock`.

| Peça | Onde | O que esta feature ganha de graça |
| --- | --- | --- |
| `roles.painel` | `database/migrations/2026_08_13_000001_add_painel_to_roles_table.php` | o papel novo declara `painel = 'app'` e o acesso ao painel sai disso |
| `canAccessPanel()` lendo o papel | `User::canAccessPanel()` | `/admin` e `/infra` exigem papel no **contexto global** (`$panel->hasTenancy() ? null : $this->contextoGlobal()`); o papel desta persona é `painel = 'app'` atribuído **no contexto do tenant** → a barreira do `/admin` já está de pé, e já loga a negativa no canal `autenticacao` |
| `temPapelDoPainel()` | `App\Models\User` | a pergunta "tem papel deste painel neste contexto?", pronta |
| `App\Support\Paineis` | `app/Support/Paineis.php` | `permissoes('app')` e `resources()['app']` — a matriz do painel, derivada do Shield |
| Permissão global por nome (ADR-01 da wiki 1) | — | `ViewAny:User` é **uma linha só**, compartilhada entre `/admin` e `/app`. A separação por painel vem do papel; a separação de dado vem do escopo da query |
| Tela do Shield publicada | `app/Filament/Admin/Resources/Roles/` | fica **no `/admin`**. Não é replicada aqui — ver ADR-05 |
| A regra de IA dos dois seeders | `.ai/rules/filament.md` (2ª e 3ª regras) | "Resource novo exige gerar as permissões" e "papel novo precisa declarar o painel" já estão escritas — esta feature **obedece**, não reescreve |

Nada de renomear permissão por painel e nada de guard por painel: as duas alternativas
já foram recusadas em ADR-01 e ADR-02 da wiki 1.

### O que a tenancy já resolve

| Peça | Onde | Efeito nesta feature |
| --- | --- | --- |
| `User::tenants()` | `App\Models\User` | a fonte do recorte — many-to-many, sem coluna em `users` |
| `User::canAccessTenant()` | `App\Models\User` | `/app/{outro slug}` responde **404**, não 403 |
| `DefinirTenantDePermissoes` | `app/Http/Middleware/DefinirTenantDePermissoes.php` | fixa `setPermissionsTeamId($tenant->id)` a cada request do `/app`, inclusive nos AJAX do Livewire (`isPersistent: true`) — é ele que faz o `syncRoles()` gravar no contexto certo |
| `Tenant::CONTEXTO_GLOBAL = 0` | `App\Models\Tenant` | o contexto que esta persona **nunca** usa |
| `BelongsToTenant` (kit) | `app/Traits/BelongsToTenant.php` | escopo global + carimbo de `tenant_id` para model **com** a coluna. Não resolve o `User` — e a wiki irmã não a aplica ao `Convite`, ver Dependências |

### As duas armadilhas que decidem o plano

1. **`User` não tem relação de posse com `Tenant`.** Registrar um `UserResource` no painel
   `app` com a configuração default estoura na primeira query:
   `LogicException: The model [App\Models\User] does not have a relationship named [tenant].`
   A saída é `protected static bool $isScopedToTenant = false;` mais escopo à mão. O caminho
   completo no vendor — e por que `$tenantOwnershipRelationshipName = 'tenants'`, que
   **funcionaria**, foi recusado — está em **ADR-03**.
2. **Registrar dois Resources no painel `app` promove `panel_user` sem ninguém decidir.** A
   wiki 1 deu a `panel_user` a matriz inteira de `Paineis::permissoes('app')`; no minuto em
   que `UserResource` e `ConviteResource` entram no painel, essa matriz passa a conter
   `Create:User`, `Delete:User` e `Create:Convite`. Todo usuário comum do negócio vira
   administrador da organização, sem erro nenhum. O `PapeisSeeder` passa a **subtrair** —
   passo 1 e **ADR-06**.

## Análise dos Arquivos Existentes

> Citados por **símbolo**, não por linha: a fundação mexeu nesses arquivos depois que esta
> wiki foi escrita, e faixa de linha em documento envelhece calada.

### `app/Models/User.php`

- `tenants()` — `belongsToMany(Tenant::class)`, pivot `tenant_user`. É a única ligação
  usuário ↔ organização. **Não muda.**
- `canAccessTenant()` — já loga a negativa no canal `tenancy`. **Não muda**; é o que
  devolve 404 em `/app/{outro slug}`.
- `canImpersonate()` / `canBeImpersonated()` — impersonar é privilégio de `master_global`.
  Nenhum `Impersonate::make()` entra nas telas do `/app`.
- `AuditsFillables` cobre `$fillable`. **Papel não é fillable**, logo troca de papel não
  entra na trilha de `/infra/audits` — daí os logs do passo 2.

### `app/Filament/Admin/Resources/Users/UserResource.php`

O irmão desta feature. O que copiar **conscientemente** e o que nunca copiar:

- `Select::make('roles')` + `saveRelationshipsUsing()` com `syncRoles()` — a armadilha já
  registrada em `.ai/rules/filament.md` (1ª regra): o `->relationship()` sozinho grava por
  `sync()` e estoura `NOT NULL constraint failed: model_has_roles.team_id`. **Copiar a
  forma, acrescentando o filtro de painel na escrita** (passo 2).
- `->getOptionLabelFromRecordUsing()` mostrando o painel de cada papel — **não copiar**: no
  `/app` todos os papéis oferecidos são do mesmo painel, e o sufixo "— /app" em todas as
  opções é ruído.
- `Select::make('tenants')` — **não copiar**. No `/app` a organização vem do painel (ADR-04).
- `Impersonate::make()`, `DeleteAction`, `DeleteBulkAction` — **não copiar**. Ver ADR-08.
- `TextInput` de nome/e-mail/senha — copiar como está.

### `app/Filament/Admin/Resources/Tenants/RelationManagers/UsersRelationManager.php`

O vínculo usuário ↔ organização, no `/admin`. **Ganha uma ação** (passo 3): é o único
lugar do sistema que conhece o usuário **e** a organização ao mesmo tempo, e portanto o
único que consegue criar o **primeiro** admin de uma organização. O helper privado
`registrar()` já é o formato de log a seguir.

### `app/Filament/Admin/Resources/Tenants/TenantResource.php`

O precedente de "resource que só existe com tenancy ligada": `shouldRegisterNavigation()`
e `canAccess()` consultando `config('kit.tenancy.enabled')`. Os dois Resources novos copiam
esse par.

### `app/Filament/Concerns/BadgeContagemNavegacao.php`

`getNavigationBadge()` conta por `getEloquentQuery()`, de propósito. Consequência grátis:
com o escopo lá, o badge do menu mostra o número da organização corrente. Escopo só na
`table()` faria o badge mentir.

### `database/seeders/PapeisSeeder.php`

O arquivo já está na forma que a wiki 1 deixou. Três pontos:

- `papel(string $nome, string $guard, ?string $painel)` grava `roles.team_id = null` —
  definição global, atribuição por tenant. É o que permite `admin_organizacao` existir
  **uma vez** e ser atribuído em N organizações. **Não mexer.** O painel é o **terceiro
  argumento posicional**.
- `permissoesDoPainel(string $painel, string $guard): Collection` intersecta
  `Paineis::permissoes($painel)` com o que **existe** na tabela `permissions`.
  `syncPermissions()` com um nome ausente lança `PermissionDoesNotExist` e derruba o seeder
  inteiro — o que acontece sempre que ele roda sem o `ShieldPermissionsSeeder` antes,
  cenário comum em teste. **A subtração do passo 1 se constrói sobre este método, não sobre
  `Paineis::permissoes()` cru.**
- `panel_user` muda de conjunto. O comentário atual já antecipa esta feature: *"no seu
  projeto, este seeder é o lugar da matriz de autorização: recorte o que o usuário comum
  pode fazer"*.

### `database/seeders/DemoTenancySeeder.php`

Ana, Bruno e Carla. O helper privado `papelDoApp(User $usuario, Tenant $tenant)` **já faz**
a troca de contexto com `setPermissionsTeamId()` + `unsetRelation('roles')` nas duas pontas
— o passo 5 só lhe acrescenta um terceiro argumento com o nome do papel.

## Autorização

**Nada de policy, gate, middleware ou guard novo.** `UserPolicy` continua delegando a
`$authUser->can('Ação:User')` e é a mesma nos dois painéis (ADR-01 da wiki 1);
`Gate::before` do `master_global` continua vencendo tudo, inclusive no `/app`;
`DefinirTenantDePermissoes` já está registrado como tenant middleware persistente; o guard
é um só. `ConvitePolicy` vem da wiki irmã.

As quatro fronteiras desta persona:

| Fronteira | Onde | Estado |
| --- | --- | --- |
| **Painel** — não entra em `/admin` nem `/infra` | `canAccessPanel()` exige `painel = 'admin'` em `Tenant::CONTEXTO_GLOBAL`; o papel novo é `painel = 'app'` no contexto do tenant | já vale (wiki 1). **Não reimplementar — testar**: CT-02 |
| **Organização** — 404 em `/app/{outro slug}` | `User::canAccessTenant()` | já vale. CT-03 |
| **Dado** — só usuários da organização corrente | `getEloquentQuery()` do Resource novo (passo 2b) | esta feature constrói. CT-04, CT-05 |
| **Escrita** — não grava papel de outro painel | filtro `painel = 'app'` dentro do `saveRelationshipsUsing()` | esta feature constrói. Sem ele, opção de Select é sugestão, não trava — ADR-07. CT-08 |

## Rotas

Nenhuma rota escrita à mão. O Filament registra, a partir do discovery de
`app/Filament/App/Resources` (`AppPanelProvider::discoverResources()`):

| Método | URI | Quem entra |
| --- | --- | --- |
| GET | `/app/{tenant}/users` | `ViewAny:User` + `canAccessTenant` |
| GET | `/app/{tenant}/users/create` | `Create:User` |
| GET | `/app/{tenant}/users/{record}/edit` | `Update:User` + o registro precisa existir **na query escopada** |
| GET | `/app/{tenant}/convites` | `ViewAny:Convite` |
| GET | `/app/{tenant}/convites/create` | `Create:Convite` |

Slugs em inglês (`users`, `convites`) seguindo o precedente `/admin/users`. `{record}` é o
**uuid** — `App\Traits\TemUuid` declara `getRouteKeyName(): 'uuid'`, então id numérico na
URL já devolve 404 nativo.

## Variáveis de Ambiente, Eventos, Jobs

Nenhum de nenhum tipo. O modo continua saindo de `KIT_TENANCY` e o rótulo de
`KIT_TENANCY_LABEL`; o envio do e-mail de convite é da wiki irmã `convite-de-usuario`.

## Impacto em Features Existentes

| O que | Impacto |
| --- | --- |
| `PapeisSeeder` → `panel_user` | **Muda de conjunto.** Deixa de receber a matriz inteira do painel `app` e passa a receber a matriz **menos** as permissões de `UserResource` e `ConviteResource`. Sem isso, todo usuário comum vira admin da organização (ver Contexto). |
| `Paineis::permissoes('app')` | Passa a incluir `*:User` e `*:Convite`. Nenhuma linha muda em `App\Support\Paineis` — o conjunto cresce porque o painel cresceu. |
| `UsersRelationManager` (`/admin`) | **Ganha a ação "Papéis nesta organização"**. A tela continua fazendo attach/detach como antes. |
| `DemoTenancySeeder` | Ana passa a receber `admin_organizacao` na Acme; Bruno e Carla ficam com `panel_user`. |
| Model `User` | **Zero mudanças.** Nenhum `tenant_id`, nenhuma trait nova — ver ADR-03. |
| `/admin/users` | Intocado. Continua sem escopo: quem administra a instalação precisa ver todos (`wikis/arquitetura.md:129`). |
| Busca ⌘K no `/app` | Passa a poder devolver usuários e convites — já escopados, porque a categoria de registros usa `getEloquentQuery()`. |
| Modo single-tenant | Os dois Resources ficam registrados mas inacessíveis (`canAccess()` falso), e `admin_organizacao` **não é semeado**. Ver ADR-09. |
| `kit:update` | Nada a fazer: `app/Filament`, `database/seeders` e `tests/Tenancy` já estão em `CAMINHOS_DO_KIT` (`KitUpdate::CAMINHOS_DO_KIT`). |

## Rollback

- **Sem migration.** A persona é uma linha em `roles` — nada de schema.
- **Reverter o papel**: `Role::where('name', 'admin_organizacao')->delete()`. As
  atribuições caem junto por FK cascade — `model_has_roles.role_id` tem
  `->cascadeOnDelete()`
  (`->cascadeOnDelete()` na migration de permissões do spatie).
- **Reverter as telas**: apagar `app/Filament/App/Resources/Users/` e
  `app/Filament/App/Resources/Convites/`, rodar `ShieldPermissionsSeeder` +
  `PapeisSeeder`. As permissões órfãs somem do papel na próxima sincronização.
- **Sem feature flag.** Recorte de dado é fronteira de segurança; um interruptor que a
  desliga é uma porta. O interruptor legítimo já existe e é `config('kit.tenancy.enabled')`.
- **Atenção na ordem ao reverter**: apagar os Resources **antes** de rodar o `PapeisSeeder`.
  Ao contrário, o seeder tenta subtrair permissões de classes que não existem mais.

## Dependências

### De pacote

Nenhum novo. Tudo instalado: `filament/filament` 5.7.6,
`bezhansalleh/filament-shield` 4.3.1, `spatie/laravel-permission`.

### De ordem, em relação às wikis irmãs

```
perfil-e-acesso-ao-painel  →  convite-de-usuario  →  admin-da-organizacao
      (fundação)                  (o model)             (esta wiki)
```

| Depende de | Por quê | O que trava se faltar |
| --- | --- | --- |
| `perfil-e-acesso-ao-painel` — **bloqueante e total** | `roles.painel`, `canAccessPanel()` lendo o papel, `App\Support\Paineis` | tudo. Sem `roles.painel` o papel novo não abre painel nenhum; sem `Paineis::permissoes()` o seeder não tem matriz |
| `convite-de-usuario` — **bloqueante parcial** | `App\Models\Convite`, `ConvitePolicy`, o `ConviteResource` do `/admin` e o fluxo de envio | apenas o passo 4 (tela de convites) e a metade `Convite` do passo 1. Os passos 2, 3 e 5 são independentes e podem ser entregues antes |

**Fronteira com a wiki `convite-de-usuario`** — o que esta wiki *assume* e não implementa:
o model `Convite`, o token, a expiração, o envio do e-mail, o aceite e o Resource do
`/admin`. Aqui só existe a **tela de criação dentro do `/app`** e a garantia de que o
`tenant_id` é o do admin que criou.

> ⚠️ **Divergência de premissa, conferida contra o `App\Models\Convite` já escrito.** Esta
> wiki afirmava que `Convite` usa `App\Traits\BelongsToTenant` com `tenant_id` fora do
> `$fillable`, e que daí barreira 6 e o escopo de leitura sairiam de graça. **Não saem.** O
> model implementado tem `tenant_id` **dentro** do `$fillable` e **não** usa a trait —
> porque o `ConviteResource` do `/admin` precisa de um `Select::make('tenant_id')` para
> escolher a organização do convidado. Consequência: no `/app`, o escopo de leitura e o
> carimbo do `tenant_id` **são código desta feature**, não herança. Ver passo 4.

Se a wiki do convite ainda não existir quando esta for implementada: entregue os passos
1 (parte `User`), 2, 3, 5 e 6, e deixe o passo 4 marcado como bloqueado em
`03-progresso.md`. Não crie um `Convite` provisório aqui.

## Riscos

| Risco | Sintoma | Mitigação |
| --- | --- | --- |
| Escopo default do Filament em `User` | `LogicException: The model [App\Models\User] does not have a relationship named [tenant].` (`vendor/.../Resource/Concerns/BelongsToTenant.php:99`) | `$isScopedToTenant = false` + CT-16 |
| Escopo que **falha aberto** | Sem tenant corrente, o escopo nativo retorna em silêncio (`BelongsToTenant.php:150-152`) e a listagem mostra **todos os usuários da instalação** | `getEloquentQuery()` fecha (`whereRaw('1 = 0')`) + `warning` no log + CT-14 |
| `panel_user` herda a matriz nova | Usuário comum passa a criar usuários, sem erro nenhum | subtração no `PapeisSeeder` + CT-12 |
| Papel atribuído no contexto global | A pessoa **entra** no `/app` (papel em qualquer contexto basta, ADR-04 da wiki 1) e não tem permissão nenhuma dentro dele: menu vazio, 403 em tudo, sem mensagem. É a linha "Usuário perdeu os papéis dentro do `/app`" de `wikis/receitas.md` | a concessão só existe pela ação do `UsersRelationManager` (passo 3), que fixa o contexto do tenant + CT-07 |
| Opção de Select tratada como trava | Payload do Livewire com id de papel `admin` grava escalada | filtro `where('painel', 'app')` na **escrita** + CT-08 |
| Exclusão de usuário a partir do `/app` | Apagar a linha de `users` remove a pessoa de **todas** as organizações | sem `DeleteAction` e `canDelete()`/`canDeleteAny()` fixos em `false` + CT-17 |
| `syncRoles()` apagar papéis de outra organização | Editar um usuário compartilhado (a "Carla" da demo) zeraria os papéis dele na outra organização | é o spatie que garante: `detachRoles()` usa a pivot query escopada pelo team corrente (`vendor/spatie/laravel-permission/src/Traits/HasRoles.php:213-233`, com o `wherePivot` de `:75-76`). **Comportamento de vendor → CT-13 trava** |
| Admin da organização se rebaixa sozinho | Ele tira o próprio `admin_organizacao` e perde a administração (não o painel) | **cortado de propósito** — ver Filosofia de Implementação |

## Channel de Log da Feature

**Nenhum channel novo.** Reusar `autenticacao` (`config/logging.php` — driver
`daily`, 14 dias, `replace_placeholders`), como a wiki 1 decidiu. Acesso, papel e
promoção são eventos de autenticação e acesso; um canal por feature aqui daria três
arquivos para o mesmo assunto.

Formato obrigatório: `[Classe@Método] mensagem | chave: valor`, com `array $context` rico.

**O que se loga**: negativas e **mudanças de poder**. Caminho feliz de leitura não vira
log. Vale a regra LGPD do cabeçalho do arquivo — identificador, nunca conteúdo em claro
(logar `user_id`, não o e-mail).

**Onde não se loga**: `getEloquentQuery()` no caminho normal. Ele roda quatro vezes por
tela (listagem, badge, busca, binding) e um `info` ali encheria o arquivo com o óbvio. Só
o caso anômalo — sem organização corrente — vira `warning`.

## Estrutura de Implementação

### 1. `PapeisSeeder` — o papel novo e a subtração

> Skills: `laravel-best-practices`

- **Path**: `database/seeders/PapeisSeeder.php`

**1a. O papel, só com tenancy ligada**

```php
// admin_organizacao só existe no modo multi-tenant: sem organização não há o que
// administrar dentro do /app, e um papel com permissão de criar usuário sem recorte
// de organização seria um segundo `admin` com outro nome. Ver ADR-09.
if (config('kit.tenancy.enabled')) {
    $this->papel('admin_organizacao', $guard, 'app')
        ->syncPermissions($this->permissoesDoPainel('app', $guard));
}
```

`papel()` já recebe o painel no **terceiro argumento posicional** e já usa `updateOrCreate`
(`PapeisSeeder::papel()`). Nada a mudar no helper.

**1b. A subtração de `panel_user`** — o passo que evita a escalada acidental

```php
/**
 * Permissões dos Resources de ADMINISTRAÇÃO do painel app.
 *
 * Por FQCN de Resource, nunca por substring do nome da permission (ADR-06).
 * `class_exists` porque o ConviteResource vem da wiki irmã.
 *
 * @return list<string>
 */
private function permissoesDeAdministracaoDoApp(): array
{
    $administracao = array_filter([
        \App\Filament\App\Resources\Users\UserResource::class,
        \App\Filament\App\Resources\Convites\ConviteResource::class,
    ], 'class_exists');

    return collect(Paineis::resources()['app'] ?? [])
        ->whereIn('resourceFqcn', $administracao)
        ->flatMap(fn (array $entidade): array => array_column($entidade['permissions'], 'key'))
        ->unique()
        ->values()
        ->all();
}
```

O formato `['permissions' => ['viewAny' => ['key' => 'ViewAny:User', 'label' => …]]]` é o
que o Shield monta em
`vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityTransformers.php:14-28`. Daí o
`array_column(..., 'key')`.

E o `panel_user` passa a ser:

```php
$administracao = $this->permissoesDeAdministracaoDoApp();

$this->papel(config('filament-shield.panel_user.name', 'panel_user'), $guard, 'app')
    ->syncPermissions(
        $this->permissoesDoPainel('app', $guard)
            ->reject(fn (string $p): bool => in_array($p, $administracao, true))
    );
```

O comentário do `panel_user` — hoje uma sugestão ao projeto ("recorte o que o usuário comum
pode fazer") — passa a descrever o que o kit **faz** e por quê.

A subtração roda **sempre**, inclusive em single-tenant: os Resources existem no painel
mesmo com `canAccess()` falso, então o Shield gera as permissões deles nos dois modos.

- **Logs**: nenhum. Seeder reporta pelo `$this->command`.

### 2. `UserResource` do painel `app`

> Skills: `laravel-best-practices`, `eloquent-best-practices`

- **Paths**:
  - `app/Filament/App/Resources/Users/UserResource.php`
  - `app/Filament/App/Resources/Users/Pages/{ListUsers,CreateUser,EditUser}.php`

```bash
php artisan make:filament-resource User --panel=app --no-interaction
```

O gerador cria a pasta e as três Pages; o resto é edição. **Não** é uma subclasse do
`UserResource` do `/admin` — ver ADR-04.

**2a. Cabeçalho e visibilidade**

```php
namespace App\Filament\App\Resources\Users;

class UserResource extends Resource
{
    use BadgeContagemNavegacao;

    protected static ?string $model = User::class;

    /**
     * O Filament NÃO escopa este resource sozinho.
     *
     * `User` não tem relação de posse com `Tenant`: o vínculo é a pivot many-to-many
     * `tenant_user`. Com o escopo nativo ligado, `Panel::boot()` registra um global scope
     * que procura a relação `tenant` (singular, o default de
     * `Filament::getTenantOwnershipRelationshipName()`) e a primeira query do painel morre
     * com `LogicException: The model [App\Models\User] does not have a relationship named
     * [tenant]`.
     *
     * Desligar aqui é o que devolve o recorte para `getEloquentQuery()` — que falha
     * FECHADO, e o escopo nativo falharia aberto. Ver ADR-03.
     */
    protected static bool $isScopedToTenant = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Administração';

    protected static ?string $modelLabel = 'usuário';

    protected static ?string $pluralModelLabel = 'usuários';

    protected static ?string $recordTitleAttribute = 'name';

    /** Espelha TenantResource: sem tenancy não existe organização para administrar. */
    public static function shouldRegisterNavigation(): bool
    {
        return (bool) config('kit.tenancy.enabled');
    }

    public static function canAccess(): bool
    {
        return (bool) config('kit.tenancy.enabled') && parent::canAccess();
    }
}
```

**2b. O recorte — `getEloquentQuery()`**

```php
/**
 * Só os usuários vinculados à organização corrente.
 *
 * `whereHas` (e não `where('tenant_id', …)`): a posse mora na pivot `tenant_user`, e um
 * usuário pertence a N organizações — a Carla da demo pertence a duas.
 *
 * Sem organização corrente a query fecha. Fora de um request de painel
 * `Filament::getTenant()` é null (job, comando, tinker) e, se este método devolvesse a
 * query crua, a listagem mostraria a base inteira de usuários da instalação. É a mesma
 * situação que `App\Traits\BelongsToTenant` documenta como limite deliberado — a
 * diferença é que ali a query é do NEGÓCIO e aqui é de PESSOAS de outros clientes.
 */
public static function getEloquentQuery(): Builder
{
    $tenant = Filament::getTenant();

    if (! $tenant instanceof Tenant) {
        Log::channel('autenticacao')->warning(
            '[UserResource@getEloquentQuery] Consulta de usuários sem organização corrente — recorte fechado | painel: app',
            [
                'painel'      => 'app',
                'executor_id' => Auth::id(),
                'motivo'      => 'sem_tenant_corrente',
            ],
        );

        return parent::getEloquentQuery()->whereRaw('1 = 0');
    }

    return parent::getEloquentQuery()
        ->whereHas('tenants', fn (Builder $query) => $query->whereKey($tenant->getKey()));
}
```

- `parent::getEloquentQuery()` e não `User::query()`: o pai remove o global scope de
  tenancy quando `isScopedToTenant()` é falso. Aqui é no-op — mas `User::query()` quebraria
  em silêncio se alguém religasse `$isScopedToTenant` um dia.
- **Logs**: um `warning`, só no ramo anômalo. Escopar aqui (e não na `table()`) é o que faz
  o route binding, a busca ⌘K e o badge do menu virem escopados de graça — ADR-03.

**2c. Formulário**

```php
TextInput::make('name')->label('Nome')->required()->maxLength(255),
TextInput::make('email')->label('E-mail')->email()->required()->unique(ignoreRecord: true),
TextInput::make('password')->label('Senha')->password()->revealable()
    ->required(fn (string $operation): bool => $operation === 'create')
    ->dehydrated(fn (?string $state): bool => filled($state))
    ->maxLength(255),

Select::make('roles')
    ->label('Papéis')
    // Barreira 1: só papéis DO PAINEL APP entram na lista. `master_global`, `admin` e
    // `infra` nunca aparecem — e nem chegariam a gravar, ver saveRelationshipsUsing.
    ->relationship('roles', 'name', fn (Builder $query) => $query->where('painel', 'app'))
    ->multiple()->preload()->searchable()->required()
    ->helperText('Os papéis valem apenas dentro desta '.mb_strtolower((string) config('kit.tenancy.label', 'Organização')).'.')
    ->saveRelationshipsUsing(function (User $record, array $state): void {
        // Barreira 5: a trava é NA ESCRITA. Opção de Select é sugestão de UI — o state
        // vem do Livewire e um payload forjado com o id do papel `admin` passaria direto
        // pelo `->relationship()`. O `where('painel','app')` aqui é o que grava certo.
        //
        // syncRoles (e não sync da relação): `model_has_roles.team_id` é NOT NULL e quem
        // preenche é a API do spatie — ver .ai/rules/filament.md.
        //
        // Barreira 2: o contexto de team é o do request, fixado por
        // DefinirTenantDePermissoes no tenant corrente. NUNCA Tenant::CONTEXTO_GLOBAL.
        $papeis = $record->roles()->getRelated()->newQuery()
            ->whereKey($state)
            ->where('painel', 'app')
            ->get();

        if ($papeis->count() !== count($state)) {
            Log::channel('autenticacao')->warning(
                "[UserResource@saveRelationshipsUsing] Papel fora do painel app descartado | alvo: {$record->id}",
                [
                    'alvo_id'      => $record->id,
                    'executor_id'  => Auth::id(),
                    'tenant_id'    => Filament::getTenant()?->getKey(),
                    'ids_enviados' => $state,
                    'ids_aceitos'  => $papeis->modelKeys(),
                    'motivo'       => 'papel_de_outro_painel',
                ],
            );
        }

        $record->syncRoles($papeis);

        Log::channel('autenticacao')->info(
            "[UserResource@saveRelationshipsUsing] Papéis atualizados na organização | alvo: {$record->id} - tenant: ".Filament::getTenant()?->getKey(),
            [
                'alvo_id'     => $record->id,
                'executor_id' => Auth::id(),
                'tenant_id'   => Filament::getTenant()?->getKey(),
                'papeis'      => $papeis->pluck('name')->all(),
            ],
        );
    }),
```

**Nenhum campo de organização.** Um Select de organização dentro de um painel que já
está numa organização é superfície de escalada, não conveniência. O vínculo é carimbado
no `afterCreate` (2e).

- **Logs**: `warning` na tentativa de papel de outro painel; `info` na gravação. Papel não
  é `$fillable`, logo a trilha de `AuditsFillables` não o cobre — este log é a única
  memória da mudança de poder.

**2d. Tabela**

```php
->columns([
    TextColumn::make('name')->label('Nome')->searchable()->sortable(),
    TextColumn::make('email')->label('E-mail')->searchable()->sortable(),
    TextColumn::make('roles.name')->label('Papéis')->badge(),
    TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->sortable(),
])
->headerActions([CreateAction::make()->label('Novo usuário')])
->recordActions([EditAction::make()])
```

- **Sem `Impersonate::make()`**: impersonar é do `master_global`
  (`User::canImpersonate()`).
- **Sem `DeleteAction` e sem `DeleteBulkAction`**: ADR-08.
- `TextColumn::make('roles.name')` já mostra só os papéis do team corrente — o `wherePivot`
  do spatie (`vendor/spatie/laravel-permission/src/Traits/HasRoles.php:75-76`) faz o
  recorte sozinho.

E a trava correspondente, para que não haja rota nem ação alcançável:

```php
// Excluir usuário é ato GLOBAL: apaga a linha de `users` e, com ela, o vínculo da pessoa
// com TODAS as organizações. Quem administra uma organização não pode alcançar isso.
// A permissão `Delete:User` existe no papel (a matriz é do painel inteiro) — a trava é
// aqui, no resource. Ver ADR-08.
public static function canDelete(Model $record): bool { return false; }
public static function canDeleteAny(): bool { return false; }
```

**2e. Pages**

- `ListUsers` — `use Asmit\ResizedColumn\HasResizableColumn;` (receita do kit,
  `wikis/receitas.md:61-67`).
- `CreateUser` — o vínculo com a organização:

```php
/**
 * O usuário nasce vinculado à organização corrente.
 *
 * Uma linha em vez do observer nativo do Filament (`observeTenancyModelCreation`, que
 * faria `syncWithoutDetaching` para relação BelongsToMany em
 * `vendor/.../Resource/Concerns/BelongsToTenant.php:205-209`): aquele observer só existe
 * quando `$isScopedToTenant` é true, e ligá-lo traria junto o escopo de leitura que
 * ADR-03 recusa. Não dá para ter metade.
 */
protected function afterCreate(): void
{
    $tenant = Filament::getTenant();

    $this->record->tenants()->syncWithoutDetaching([$tenant->getKey()]);

    Log::channel('autenticacao')->info(
        "[CreateUser@afterCreate] Usuário criado e vinculado à organização | alvo: {$this->record->id} - tenant: {$tenant->getKey()}",
        [
            'alvo_id'     => $this->record->id,
            'tenant_id'   => $tenant->getKey(),
            'executor_id' => Auth::id(),
        ],
    );
}
```

- `EditUser` — sem `DeleteAction` no `getHeaderActions()` (o gerador o inclui por
  default; **removê-lo**).

### 3. `UsersRelationManager` — quem cria o primeiro admin de uma organização

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/Tenants/RelationManagers/UsersRelationManager.php`

Problema de bootstrap: `admin_organizacao` só vale atribuído **dentro** da organização, e o
Select de papéis do `/admin` grava em `Tenant::CONTEXTO_GLOBAL` — quem é promovido por ali
entra no `/app` e não vê nada. O porquê completo está em **ADR-10**. O relation manager é o
único lugar que conhece o usuário **e** a organização. Ação nova na linha de cada usuário:

```php
Action::make('papeisNaOrganizacao')
    ->label('Papéis nesta organização')
    ->icon(Heroicon::OutlinedShieldCheck)
    ->schema([
        Select::make('roles')
            ->label('Papéis')
            ->multiple()->preload()->searchable()
            ->options(fn (): array => Role::query()->where('painel', 'app')->pluck('name', 'id')->all())
            ->helperText('Só papéis do painel /app. Papel de instalação (admin, infra) se dá no cadastro do usuário.'),
    ])
    ->fillForm(fn (User $record): array => ['roles' => $this->papeisAtuais($record)])
    ->action(function (User $record, array $data): void {
        /** @var Tenant $tenant */
        $tenant    = $this->getOwnerRecord();
        $registrar = app(PermissionRegistrar::class);
        $anterior  = $registrar->getPermissionsTeamId();

        try {
            // O contexto é o da ORGANIZAÇÃO, nunca o global. Sem esta troca, o papel
            // seria gravado em Tenant::CONTEXTO_GLOBAL e ficaria invisível dentro do /app.
            $registrar->setPermissionsTeamId($tenant->getKey());
            $record->unsetRelation('roles');

            $papeis = Role::query()->whereKey($data['roles'] ?? [])->where('painel', 'app')->get();
            $record->syncRoles($papeis);
        } finally {
            $registrar->setPermissionsTeamId($anterior);
            $record->unsetRelation('roles');
        }

        Log::channel('autenticacao')->info(
            "[UsersRelationManager@papeisNaOrganizacao] Papéis definidos na organização | tenant: {$tenant->slug} - user: {$record->id}",
            [
                'tenant_id'   => $tenant->getKey(),
                'user_id'     => $record->id,
                'executor_id' => Auth::id(),
                'papeis'      => $papeis->pluck('name')->all(),
            ],
        );
    }),
```

- `unsetRelation('roles')` nas duas pontas: o Eloquent cacheia `roles` na instância, e o
  cache do contexto anterior contaminaria o `syncRoles()`. É o mesmo par que
  `DemoTenancySeeder::papelDoApp()` já usa.
- `papeisAtuais(User $record): array` é um método privado que faz a mesma troca de contexto
  para **ler**.
- **Logs**: `info` no canal `autenticacao` (e não `tenancy`, que é o canal do arquivo): é
  mudança de poder, não mudança de vínculo. O `registrar()` existente continua logando
  attach/detach em `tenancy`.

### 4. `ConviteResource` do painel `app`

> Skills: `laravel-best-practices`
> **Bloqueado pela wiki `convite-de-usuario`.**

- **Paths**:
  - `app/Filament/App/Resources/Convites/ConviteResource.php`
  - `app/Filament/App/Resources/Convites/Pages/{ListConvites,CreateConvite}.php`

**Contraste com o passo 2**: aqui `Convite` tem coluna `tenant_id`, então o recorte é um
`where` e não um `whereHas`. Mas ele **não vem de graça** — o `App\Models\Convite` escrito
pela wiki irmã tem `tenant_id` no `$fillable` e não usa `App\Traits\BelongsToTenant` (ver
Fronteira, acima). Duas linhas fecham as duas pontas:

```php
// Leitura: o mesmo par fail-closed do passo 2b, com `where` no lugar do `whereHas`.
public static function getEloquentQuery(): Builder   // idem 2b: sem tenant → whereRaw('1 = 0') + warning

// Barreira 6, na escrita: o tenant vem do PAINEL, nunca do payload. Um `tenant_id`
// forjado no state do Livewire é sobrescrito aqui, antes do insert.
protected function mutateFormDataBeforeCreate(array $data): array
{
    $data['tenant_id'] = Filament::getTenant()->getKey();

    return $data;
}
```

**CT-10 prova a barreira 6** e é ele que acusa se a wiki irmã mudar o `$fillable` do
`Convite` de novo.

Alternativa mais barata, se a wiki irmã aceitar mudar o model: `Convite` usa
`BelongsToTenant` — a leitura passa a ser resolvida pelo escopo global da trait, em job e
comando também — e o `/admin` grava o `tenant_id` fora do mass assignment, como
`DemoTenancySeeder::projeto()` já faz. Mesmo assim o `mutateFormDataBeforeCreate` fica: a
trait só carimba quando o valor está `blank`, e o payload forjado não está.

O resto do Resource:

- `shouldRegisterNavigation()` / `canAccess()` com `config('kit.tenancy.enabled')`, igual
  ao passo 2a;
- formulário com o e-mail do convidado e o Select de papéis **com o mesmo filtro
  `painel = 'app'` na exibição e na escrita** do passo 2c — convidar alguém já escolhendo o
  papel é a via mais curta para escalada, e a trava tem de ser a mesma;
- nenhum campo de organização;
- as ações de reenvio/cancelamento são da wiki irmã.

- **Logs**: `info` em `afterCreate` da Page —
  `[CreateConvite@afterCreate] Convite criado pela administração da organização | convite: {id} - tenant: {id}`,
  com `context` de `convite_id`, `tenant_id`, `executor_id`, `papeis`.

### 5. `DemoTenancySeeder` — a persona na demo

> Skills: `laravel-best-practices`

- **Path**: `database/seeders/DemoTenancySeeder.php`

Ana vira a admin da Acme; Bruno e Carla ficam `panel_user`. Sem isso a demo continua sem
mostrar a persona, e o cenário de "usuário compartilhado entre organizações" (Carla) não
exercita CT-13.

**Uma linha de mudança, não um helper novo**: `papelDoApp(User $usuario, Tenant $tenant)`
já existe e já faz a troca de contexto correta; ganha um terceiro argumento
`string $papel = 'panel_user'` e a chamada da Ana passa `'admin_organizacao'`.

- **Logs**: nenhum; o seeder escreve no console.

> Rodar `ShieldPermissionsSeeder` e depois `PapeisSeeder` **não é um passo deste plano** —
> é a regra do kit já gravada em `.ai/rules/filament.md` para todo Resource novo. Está na
> Verificação Final para não ser esquecida.

### 6. Documentação

> Skills: nenhuma

| Arquivo | O que muda |
| --- | --- |
| `.ai/rules/filament.md` | **quarta** regra (o arquivo já tem três, da wiki 1): **Resource de model sem relação de posse com o tenant** — o sintoma, o `$isScopedToTenant = false`, o `getEloquentQuery()` que falha fechado, e o par de testes. É o texto canônico da armadilha; os arquivos abaixo apontam para cá em vez de recontá-la |
| `wikis/convencoes.md` | `## Armadilhas já resolvidas` ganha duas linhas: `$isScopedToTenant = false` no `UserResource` do `/app`, e o filtro de painel na **escrita** de papéis |
| `wikis/arquitetura.md` | `### O que muda em cada painel` — a linha do `/app` ganha "administração da própria organização". Só isso |
| `wikis/receitas.md` | nova receita `## Promover alguém a admin de uma organização` (o caminho pelo relation manager, com o sintoma de fazer pelo lugar errado); `## Problemas comuns` ganha "admin da organização entra no /app e não vê nada" |
| `README.md` / `README.en.md` | a seção de multi-tenancy ganha a persona e os quatro papéis do kit |

`.ai/rules/index.md` não muda: o glob `app/Filament/**` já mapeia para `filament.md`.
`CLAUDE.md` e `AGENTS.md` **não** se editam à mão — `boost:update` os sobrescreve.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`.** A escada em cada passo: precisa existir? já existe no
> repo? stdlib? feature nativa do Filament? uma linha? só então o mínimo que funciona.

Sem migration, sem channel de log novo, sem entrada em `CAMINHOS_DO_KIT`, sem classe base
compartilhada (ADR-04), sem tela de permissões (ADR-05). O que sobra de código novo são
dois Resources, um método de seeder e uma ação.

### O que foi cortado, e quando vale voltar

| Cortado | Quando acrescentar |
| --- | --- |
| Tela de permissões no `/app` | quando alguém precisar auditar a matriz de dentro da organização. É uma `ViewAction` com Infolist sobre `Paineis::permissoes('app')`, ~30 linhas, sem model novo — ADR-05 |
| "Remover usuário da organização" (detach) | quando aparecer o pedido. Hoje não foi pedido, e a operação certa é detach da pivot, nunca delete — não é o `DeleteAction` que o gerador cria |
| Guarda contra auto-rebaixamento | quando o suporte reclamar. São 3 linhas no `saveRelationshipsUsing`; o estrago é reversível pelo `master_global` em `/admin` |
| Nome do papel configurável (`config('kit.tenancy.papel_admin')`) | nunca por especulação. Nome de papel é identificador de código, usado em seeder e teste; o que é configurável é o **rótulo** da organização |
| Convite com papel obrigatório | é decisão da wiki irmã |

> Atalhos deliberados marcados com comentário `ponytail:`.
> Ao final, `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em `full`** na conversa com o usuário. Wiki, código, commits e READMEs
> são boundary — prosa normal.

## Mapeamentos

### Papel → painel → contexto (estado final)

| Papel | `roles.painel` | Contexto da atribuição | Onde entra | Permissões |
| --- | --- | --- | --- | --- |
| `master_global` | `null` | global | os três, por `Gate::before` | nenhuma no banco |
| `admin` | `admin` | global | `/admin` | `Paineis::permissoes('admin')` |
| `infra` | `infra` | global | `/infra` | `Paineis::permissoes('infra')` |
| `admin_organizacao` | `app` | **o tenant** | `/app/{slug}` das organizações dele | `Paineis::permissoes('app')` |
| `panel_user` | `app` | o tenant | `/app/{slug}` das organizações dele | `Paineis::permissoes('app')` **menos** `*:User` e `*:Convite` |

### As seis barreiras contra escalada de privilégio

| # | Barreira | Onde é implementada | CT |
| --- | --- | --- | --- |
| 1 | O Select só oferece papéis com `roles.painel = 'app'` | `->relationship('roles', 'name', fn ($q) => $q->where('painel','app'))` (passo 2c) | CT-06 |
| 2 | A atribuição acontece no contexto do tenant, nunca em `CONTEXTO_GLOBAL` | `DefinirTenantDePermissoes` + `syncRoles()` (passo 2c); troca explícita no passo 3 | CT-07 |
| 3 | Ele não cria nem edita papéis, só atribui | nenhum `RoleResource` no painel `app`; `Create:Role` está em `permissoes('admin')` e não em `permissoes('app')` — ADR-02 | CT-09 |
| 4 | Não vê nem edita usuário fora da organização, nem por URL direta | `getEloquentQuery()` (passo 2b) + `resolveRecordRouteBinding` (`HasRoutes.php:41-51`) | CT-04, CT-05 |
| 5 | Não promove ninguém a `master_global`/`admin`/`infra` | filtro `where('painel','app')` **na escrita** (passo 2c) — ADR-07 | CT-08 |
| 6 | O convite nasce com o `tenant_id` dele, ignorando o formulário | `mutateFormDataBeforeCreate` sobrescreve com `Filament::getTenant()` (passo 4) | CT-10 |

## Testes

> Ver `04-casos-de-teste.md`. Dezessete casos: `tests/Tenancy/AdminDaOrganizacaoTest.php`
> (a suíte principal) e `tests/Kit/AdminDaOrganizacaoTest.php` (o caso que só faz sentido
> em single-tenant).

## Verificação Final

- [ ] `php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder`
- [ ] `php artisan db:seed --class=Database\\Seeders\\PapeisSeeder`
- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --compact --group=kit`
- [ ] `composer types:check`
- [ ] Ana (`ana@example.com`, senha `password`) entra em `/app/acme`, vê "Usuários", e a
      lista tem **só** os usuários da Acme
- [ ] A mesma Ana leva 403 em `/admin` e em `/infra`, e 404 em `/app/globex`
- [ ] Bruno (`panel_user`) entra em `/app/globex` e **não** vê o item "Usuários"
- [ ] `storage/logs/autenticacao-*.log` tem a linha `[UserResource@saveRelationshipsUsing]`
      depois de salvar papéis

## Commits

- `:sparkles: admin da organizacao administra usuarios e convites dentro do /app`
- `:memo: wiki da feature admin-da-organizacao`

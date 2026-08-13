# Plano de Ação — Multi-tenancy por Organização

## Objetivo

Dar ao starter-kit suporte a multi-tenancy **opt-in**: um comando `php artisan kit:tenancy` liga o modo multi-organização, e o kit continua nascendo single-tenant para quem não precisa. Com o modo ligado, o painel `/app` passa a ser tenant-aware (`/app/{organizacao}`), o usuário só enxerga as organizações às quais está vinculado, o `/admin` ganha o CRUD de Organizações e o vínculo de usuários, e o `/infra` segue global — observabilidade não se recorta por cliente.

Os papéis passam a ser **por organização** (`spatie/permission` com `teams = true`), que é o modo que o Shield já suporta nativamente: `Utils::isTenancyEnabled()` lê exatamente `config('permission.teams')` (`vendor/bezhansalleh/filament-shield/src/Support/Utils.php`). Assim um usuário pode ser `admin` na Organização A e usuário comum na B.

## Contexto

Hoje o kit é single-tenant, mas já carrega três sinais de que a tenancy foi antecipada:

1. `app/Filament/Spotlight/AcoesDeCriacao.php:55` já tem o guard `$painel->hasTenancy() && Filament::getTenant() === null`, com o comentário *"O kit não usa tenancy, mas o guard fica: é grátis e evita a regressão no dia em que alguém ligar"*.
2. `ai_runs.tenant_id` é NOT NULL e hoje recebe sempre `config('ai-tasks.default_tenant')` — ver `app/Ai/Listeners/RegistrarAiRun.php` e `app/Ai/Middleware/BudgetGuardMiddleware.php:29`.
3. `config/filament-shield.php:45` tem `'tenant_model' => null` esperando ser preenchido.

O que falta é o model do tenant, o vínculo usuário↔tenant, a configuração do painel e o recorte real das queries.

## Análise dos Arquivos Existentes

### `app/Models/User.php`

Implementa `FilamentUser`, `HasAvatar`, `Auditable`. `canAccessPanel()` (linha 65) decide painel por papel. Passa a implementar também `Filament\Models\Contracts\HasTenants`, que exige exatamente dois métodos (confirmado em `vendor/filament/filament/src/Models/Contracts/HasTenants.php`):

```php
public function canAccessTenant(Model $tenant): bool;
public function getTenants(Panel $panel): array | Collection;
```

### `app/Providers/Filament/AppPanelProvider.php`

Painel `default()`, path `app`, discovery em `app/Filament/App/*`. Recebe `->tenant(...)` e o middleware de tenant. É o único painel que muda.

### `app/Providers/Filament/AdminPanelProvider.php`

Recebe o `OrganizacaoResource` por discovery (`app/Filament/Admin/Resources`). Nenhuma mudança no provider em si.

### `app/Providers/KitServiceProvider.php`

`configureGates()` (linha 60) define os gates de infra. `configureAiLedger()` (linha 87) liga o listener do ledger. Nenhuma mudança estrutural; a resolução de tenant entra na camada de IA (passo 7).

### `config/permission.php`

`'teams' => false` (linha 151) e `'team_foreign_key' => 'team_id'` (linha 113). A migration `database/migrations/2026_08_12_164859_create_permission_tables.php` cria as colunas de team **condicionalmente** (`if ($teams || config('permission.testing'))`, linhas 40-48 e 65-67) — ou seja, ligar a flag **antes** do `migrate` resolve tudo; ligar depois exige migration aditiva. Ver ADR-04.

### `app/Filament/Spotlight/*`

`AcoesDeCriacao` já está pronto. `ResourcesAutorizadasCategory` e `PagesAutorizadasCategory` filtram por `canAccess()` e não tocam em tenant — seguem corretos, porque as URLs são resolvidas em closure, dentro do request com tenant já identificado.

### `tests/Kit/PaineisTest.php`

Usa `/app` cru (linha ~64 usa `/admin`). Com tenancy ligada, `/app` redireciona para `/app/{organizacao}`. Os testes do kit rodam com tenancy **desligada** (default), então continuam válidos; os testes de tenancy ficam num arquivo próprio que liga a flag no setup. Ver `04-casos-de-teste.md`.

### `app/Ai/Middleware/BudgetGuardMiddleware.php` e `app/Ai/Listeners/RegistrarAiRun.php`

Ambos fixam o tenant em `config('ai-tasks.default_tenant', 'default')`. O pacote já tem `Fomvasss\AiTasks\Support\TenantResolver` (resolve por header `X-Tenant-Id` → `auth()->user()->tenant_id` → config). Passa a existir um resolvedor do kit que prefere `Filament::getTenant()`.

## Autorização

- **Policies**: `App\Policies\OrganizacaoPolicy` no padrão Shield do kit — `$authUser->can('ViewAny:Organizacao')` etc., espelhando `app/Policies/UserPolicy.php`.
- **Gates**: nenhum gate novo. O `Gate::before` do `master_global` continua vencendo tudo.
- **Fronteira de tenant**: `User::canAccessTenant()` — é o que impede acesso por adivinhação de ID/slug na URL.
- **Middleware**: `Filament\Http\Middleware\IdentifyTenant` **já é registrado automaticamente** pelo Filament (`vendor/filament/filament/src/Panel/Concerns/HasMiddleware.php:119`) — não entra no plano. O que entra é o middleware do kit que fixa o contexto de papéis (passo 6), via `tenantMiddleware(array $middleware, bool $isPersistent = false)` (`HasMiddleware.php:67`).
- **Papéis por organização**: com `permission.teams = true`, `setPermissionsTeamId()` precisa ser chamado a cada request com o tenant corrente (passo 6).

## Rotas

Nenhuma rota escrita à mão. O Filament reescreve as do painel `app`:

| Antes | Depois |
|---|---|
| `/app` | `/app/{tenant}` (redireciona para a primeira organização do usuário) |
| `/app/produtos` | `/app/{tenant}/produtos` |
| `/app/login` | `/app/login` (inalterada) |

`tenantRoutePrefix` **não** será usado (URL mais curta). O `slugAttribute` será `slug`, para a URL ficar `/app/acme` em vez de `/app/1`.

## Variáveis de Ambiente

| Key | Default | Descrição |
|---|---|---|
| `KIT_TENANCY` | `false` | Marca se o modo multi-organização está ligado. Lido por `config/kit.php` → `kit.tenancy` |

Nenhuma outra. `permission.teams` é config, não env, porque o valor precisa estar fixo no momento da migration.

## Eventos / Listeners / Observers

- **Nenhum evento novo.**
- Listener existente afetado: `App\Ai\Listeners\RegistrarAiRun` passa a gravar o tenant real em `ai_runs.tenant_id`.

## Jobs / Queues

Nenhum job novo. **Atenção documentada**: job enfileirado não tem `Filament::getTenant()` — quem despacha precisa passar o tenant explicitamente. Vira nota em `wikis/convencoes.md`.

## Impacto em Features Existentes

| Feature | Impacto |
|---|---|
| Painel `/app` | URLs mudam para `/app/{slug}`. Qualquer link fixo para `/app/...` quebra |
| Shield | Papéis passam a ser por organização; a UI do Shield muda de comportamento (roles com `team_id`) |
| `PapeisSeeder` | Precisa semear os papéis com `team_id` nulo (globais) ou por organização — ver passo 8 |
| Busca ⌘K | Sem mudança de código; o guard já existe |
| `/admin` e `/infra` | Sem tenancy. Continuam globais |
| Camada de IA | `ai_runs.tenant_id` passa a ter valor real; budget passa a ser por organização |
| Lockscreen | **Risco**: `vendor/marjose123/filament-lockscreen/routes/web.php` registra com `->prefix($panel->getPath())`, sem o segmento de tenant. A tela de bloqueio cai fora do escopo de tenant. Validar no CT-09 |
| Testes do kit | Rodam com tenancy desligada e continuam válidos |

## Rollback

- **Feature flag**: `kit.tenancy = false` no `config/kit.php` + reverter o `->tenant(...)` do `AppPanelProvider` devolve o comportamento single-tenant sem apagar dado.
- **Migration down**: `organizacao_user` e `organizacoes` têm `down()` completo.
- **Permission teams**: reverter `permission.teams` para `false` exige `migrate:fresh` — está documentado como caminho sem volta fácil. É o principal motivo do comando exigir árvore git limpa.

## Dependências

Nenhuma nova. Tudo já instalado: `filament/filament` (tenancy nativa), `spatie/laravel-permission` (teams), `bezhansalleh/filament-shield` (modo tenant), `fomvasss/laravel-ai-tasks` (budget por tenant).

## Riscos

| Risco | Mitigação |
|---|---|
| Ligar `permission.teams` depois do `migrate` deixa o schema incompatível | O comando exige confirmação e roda `migrate:fresh --seed`; caminho aditivo fica documentado como manual |
| Vazamento entre organizações por model sem resource | Filament só escopa o que tem resource. CT-05 cobre o caso; a convenção manda usar `BelongsToOrganizacao` |
| `Filament::getTenant()` nulo fora do request (job, comando, seeder) | Documentado; o resolvedor de IA cai no default do pacote |
| Lockscreen fora do escopo de tenant | CT-09 valida; se quebrar, a mitigação é registrar a rota dentro do grupo de tenant |
| Cache de query (`laravel-model-caching`) servir dado de outra organização | O scope global entra na query e muda a chave de cache; CT-06 prova |

## Channel de Log da Feature

`config/logging.php` já tem os channels `ai` e `autenticacao` (mesmo padrão `daily`). **Não existe** channel de tenancy — criar como primeiro passo:

```php
'tenancy' => [
    'driver' => 'daily',
    'path'   => storage_path('logs/tenancy.log'),
    'level'  => env('LOG_LEVEL', 'debug'),
    'days'   => 14,
],
```

Todos os logs da feature usam `Log::channel('tenancy')`.

## Estrutura de Implementação

### 1. Channel de log e flag de configuração

> Skills: `laravel-best-practices`

- **Path**: `config/logging.php` — acrescentar o channel `tenancy` acima, no mesmo formato do channel `ai`.
- **Path**: `config/kit.php` — nova chave, depois de `version`:
  ```php
  'tenancy' => (bool) env('KIT_TENANCY', false),
  ```
- **Path**: `.env.example` — `KIT_TENANCY=false` junto das demais chaves do kit.
- Sem log neste passo (só configuração).

### 2. Migrations

> Skills: `laravel-best-practices`

- **Path**: `database/migrations/xxxx_create_organizacoes_table.php`

  | Coluna | Tipo | Observação |
  |---|---|---|
  | `id` | `id()` | PK numérica (convenção do kit) |
  | `uuid` | `uuid()->unique()` | convenção `TemUuid` |
  | `nome` | `string` | |
  | `slug` | `string`, unique | usado na URL (`slugAttribute`) |
  | `ativa` | `boolean`, default `true` | exclusão lógica |
  | `timestamps` | | |

- **Path**: `database/migrations/xxxx_create_organizacao_user_table.php` — pivot:

  | Coluna | Tipo |
  |---|---|
  | `organizacao_id` | `foreignId`, `constrained('organizacoes')`, `cascadeOnDelete` |
  | `user_id` | `foreignId`, `constrained()`, `cascadeOnDelete` |
  | unique | `['organizacao_id', 'user_id']` |

- **Não criar** migration para as colunas de team do `spatie/permission`: a migration existente já as cria condicionalmente quando `permission.teams` é `true` (`2026_08_12_164859_create_permission_tables.php:40-48`).

### 3. Model `Organizacao`

> Skills: `laravel-best-practices`, `eloquent-best-practices`

- **Path**: `app/Models/Organizacao.php`
- Traits do kit: `TemUuid`, `AuditsFillables`; implementa `Auditable`.
- `protected $table = 'organizacoes';` (o Laravel pluralizaria errado).
- `$fillable = ['nome', 'slug', 'ativa']` — `uuid` fora, por convenção.
- `casts()`: `'ativa' => 'boolean'`.
- Relação: `users(): BelongsToMany` → `User::class`.
- **Path**: `database/factories/OrganizacaoFactory.php` — só para teste (seeder do kit nunca usa factory).

### 4. `User` implementa `HasTenants`

> Skills: `laravel-best-practices`

- **Path**: `app/Models/User.php`
- Acrescentar `implements ... HasTenants` e:
  ```php
  public function organizacoes(): BelongsToMany
  {
      return $this->belongsToMany(Organizacao::class);
  }

  public function getTenants(Panel $panel): Collection
  {
      return $this->organizacoes()->where('ativa', true)->get();
  }

  public function canAccessTenant(Model $tenant): bool
  {
      return $this->isMasterGlobal()
          || $this->organizacoes()->whereKey($tenant)->exists();
  }
  ```
- **Logs**:
  - `Log::channel('tenancy')->warning('[User@canAccessTenant] Acesso a organização negado | user: {id} - organizacao: {id}', ['user_id' => ..., 'organizacao_id' => ..., 'motivo' => 'sem_vinculo'])` — só no ramo de negação, que é o evento que interessa auditar.

### 5. Painel `/app` tenant-aware

> Skills: `laravel-best-practices`

- **Path**: `app/Providers/Filament/AppPanelProvider.php`
- Dentro do `panel()`, condicionado à flag (para o kit continuar single-tenant por default):
  ```php
  if (config('kit.tenancy')) {
      $panel
          ->tenant(Organizacao::class, slugAttribute: 'slug')
          ->tenantMiddleware([DefinirOrganizacaoDePermissoes::class], isPersistent: true);
  }
  ```
- O `IdentifyTenant` do Filament **não** entra na lista: ele já é aplicado automaticamente. `tenantMiddleware()` recebe só o middleware do kit, e `isPersistent: true` é o que faz ele rodar também nos requests AJAX do Livewire — sem isso o contexto de papéis se perde em toda interação de tabela.
- O `->tenant()` fica **depois** de `->plugins()`, pela mesma razão documentada para `databaseNotifications()` em `wikis/convencoes.md`.
- Nenhuma mudança em `AdminPanelProvider` nem em `InfraPanelProvider`.

### 6. Papéis por organização

> Skills: `laravel-best-practices`

- **Path**: `config/permission.php` — `'teams' => true` (escrito pelo comando do passo 9, não à mão).
- **Path**: `config/filament-shield.php` — `'tenant_model' => \App\Models\Organizacao::class`.
- **Path**: `app/Http/Middleware/DefinirOrganizacaoDePermissoes.php` (novo):
  ```php
  public function handle(Request $request, Closure $next): Response
  {
      app(PermissionRegistrar::class)->setPermissionsTeamId(Filament::getTenant());

      return $next($request);
  }
  ```
  Sem isso, o spatie resolve papéis com `team_id` nulo e o recorte por organização não acontece — em silêncio, que é o pior modo de falhar.
- Registrado no painel `/app` pelo passo 5, com `isPersistent: true`.
- **Alternativa descartada na revisão**: escutar o evento `Filament\Events\TenantSet` no `KitServiceProvider`. A classe existe (`vendor/filament/filament/src/Events/TenantSet.php`), mas o middleware persistente é o mecanismo que a própria documentação indica para cobrir os requests AJAX do Livewire — e mantém a configuração de tenancy num arquivo só, em vez de espalhar entre provider e painel.
- **Logs**:
  - `Log::channel('tenancy')->debug('[DefinirOrganizacaoDePermissoes@handle] Contexto de papéis fixado na organização | organizacao: {id}', ['organizacao_id' => ..., 'user_id' => ...])`.

### 7. Camada de IA por organização

> Skills: `ai-sdk-development`

- **Path**: `app/Ai/Support/ResolvedorDeTenant.php` (novo)
  ```php
  public function id(): string   // Filament::getTenant()?->getKey() ?? config('ai-tasks.default_tenant')
  ```
  Devolve string porque `ai_runs.tenant_id` é string e o `Budget` do pacote assina `string $tenant`.
- **Path**: `app/Ai/Middleware/BudgetGuardMiddleware.php:31` — trocar `config('ai-tasks.default_tenant', 'default')` pelo resolvedor.
- **Path**: `app/Ai/Listeners/RegistrarAiRun.php` — mesma troca no `AiRun::create([...])`.
- **Logs**: o `warning` de budget estourado já existe; acrescentar `organizacao_id` ao context.

### 8. Seeders

> Skills: `laravel-best-practices`

- **Path**: `database/seeders/OrganizacoesSeeder.php` — idempotente (`updateOrCreate` por `slug`), **sem factory e sem faker** (convenção do kit). Cria a organização `Padrão` (slug `padrao`) e vincula o usuário admin.
- **Path**: `database/seeders/PapeisSeeder.php` — quando `permission.teams` está ligado, os papéis do kit continuam **globais** (`team_id` nulo) e a atribuição por organização é feita no vínculo. Documentar no cabeçalho do seeder.
- **Path**: `database/seeders/DatabaseSeeder.php` — chamar `OrganizacoesSeeder` depois de `UsuarioAdminSeeder`, só quando `config('kit.tenancy')`.
- **Logs**: seeders não logam (saída pelo console do Artisan).

### 9. Comando `kit:tenancy`

> Skills: `laravel-best-practices`

- **Path**: `app/Console/Commands/KitTenancy.php`
- Assinatura: `kit:tenancy {--demo : Cria também o recurso de demonstração} {--force : Pula a confirmação}`
- Espelhar o estilo de `app/Console/Commands/KitInstall.php` e `KitUpdate.php` (mesmas mensagens `components->info/warn`, mesmo tratamento de `--no-interaction`).
- Passos do comando, em ordem:
  1. **Conferir o terreno** — exige repositório git com árvore limpa. A checagem já existe em `KitUpdate::preVoo()` (`app/Console/Commands/KitUpdate.php:186`, com `git status --porcelain` na linha 210) e a mensagem de erro em 216: **reaproveitar essa lógica em vez de reescrever** (extrair para uma concern se ficar duplicada). Sem isso não há como reverter.
  2. **Avisar que é destrutivo** e pedir confirmação: liga `permission.teams` e roda `migrate:fresh --seed`, o que **apaga os dados**. Com `--no-interaction` só segue se receber `--force`.
  3. Escrever `KIT_TENANCY=true` no `.env`.
  4. Escrever `'teams' => true` em `config/permission.php` e `tenant_model` em `config/filament-shield.php` (substituição pontual de linha, no estilo do `KitUpdate::marcarVersao()`).
  5. `config:clear` (o spatie aborta com mensagem explícita se a config estiver em cache — ver `create_permission_tables.php:21`).
  6. `migrate:fresh --seed`.
  7. Se `--demo`: publicar os arquivos do passo 10 e rodar o seeder da demo.
  8. Imprimir as URLs (`/app/padrao`, `/admin/organizacoes`) e o resumo do que mudou.
- **Logs**:
  - `Log::channel('tenancy')->info('[KitTenancy@handle] Modo multi-organização ativado | demo: {bool}', ['demo' => ..., 'usuario' => get_current_user()])`
  - `Log::channel('tenancy')->error('[KitTenancy@handle] Falha ao ativar o modo multi-organização', ['exception' => $e])` no `catch`.

### 10. `/admin`: CRUD de Organizações e vínculo de usuários

> Skills: `laravel-best-practices`, `tailwindcss-development`

- **Path**: `app/Filament/Admin/Resources/Organizacoes/OrganizacaoResource.php` (+ `Pages/`, `Schemas/`, `Tables/`, seguindo a estrutura de `AgentesIa/`).
- Usa `App\Filament\Concerns\BadgeContagemNavegacao`; a List page usa `Asmit\ResizedColumn\HasResizableColumn`.
- Form: `nome`, `slug` (gerado do nome com `->live(onBlur: true)` + `Set`), `ativa`.
- **Vínculo de usuários**: `RelationManager` `UsersRelationManager` com `attach`/`detach` — é o que o `/admin` precisa para "vincular os usuários às organizações correspondentes".
- **Path**: `app/Policies/OrganizacaoPolicy.php` — espelhar `UserPolicy` (`can('ViewAny:Organizacao')` …).
- Permissões: rodar `ShieldPermissionsSeeder` + `PapeisSeeder` depois de criar o resource (convenção do kit).
- **Logs**:
  - `Log::channel('tenancy')->info('[UsersRelationManager@attach] Usuário vinculado à organização | organizacao: {slug} - user: {id}', [...])`
  - idem para `detach`, no mesmo nível.

### 11. Demo (`--demo`)

> Skills: `laravel-best-practices`, `pest-testing`

Existe para **provar o isolamento**, não para virar feature do kit. Tudo que a demo cria é removível apagando quatro arquivos.

- **Path**: `app/Models/Projeto.php` — `TemUuid`, `AuditsFillables`, `belongsTo(Organizacao::class)`.
- **Path**: migration `xxxx_create_projetos_table.php` — `id`, `uuid`, `organizacao_id` (FK), `nome`, `timestamps`.
- **Path**: `app/Filament/App/Resources/Projetos/ProjetoResource.php` — o primeiro resource do painel `/app`. Escopo automático pelo Filament (relação `organizacao`).
- **Path**: `database/seeders/DemoTenancySeeder.php` — 2 organizações (`Acme`, `Globex`), 3 usuários (um em cada, um nas duas) e 2 projetos por organização. Sem faker.
- **Logs**: nenhum (código de demonstração).

### 12. Testes

> Skills: `pest-testing`

- **Path**: `tests/Kit/TenancyTest.php`, grupo `kit`.
- A suíte atual roda com `kit.tenancy = false` e **não muda**. Este arquivo liga a flag no `beforeEach` via `config()->set(...)` e roda as migrations com teams.
- Cenários completos em `04-casos-de-teste.md`.

### 13. Documentação

> Skills: nenhuma

- **Path**: `wikis/arquitetura.md` — seção "Multi-tenancy (opt-in)".
- **Path**: `wikis/convencoes.md` — nova convenção `BelongsToOrganizacao` + a nota sobre jobs sem tenant.
- **Path**: `wikis/receitas.md` — receita "Resource novo num painel com tenancy".
- **Path**: `README.md` e `README.en.md` — o comando `kit:tenancy` na seção de comandos.
- **Path**: `CHANGELOG.md` — entrada da versão.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> Cada passo deve aplicar a escada de simplicidade:
> 1. Reutilizar código existente antes de criar novo
> 2. Usar stdlib do PHP/Laravel antes de código custom
> 3. Usar features nativas antes de dependências
> 4. Uma linha quando possível
> 5. Mínimo código que funciona
>
> Aqui isso significa, concretamente: **não escrever scope global próprio** (o Filament já escopa por resource), **não escrever middleware de tenant** (o `ApplyTenantScopes` é nativo) e **não criar tabela de convite/associação além do pivot**.
>
> Atalhos deliberados devem ser marcados com `ponytail:` comment.
> Após implementação, rodar `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em modo `full`** na comunicação agent ↔ usuário.
> Arquivos wiki (01-05) são boundary do Caveman — escrever em prosa normal.
> Código, commits e PRs também são boundary do Caveman.

## Mapeamentos

| Conceito Filament | Neste kit |
|---|---|
| tenant | `App\Models\Organizacao` |
| `ownershipRelationship` | `organizacao` (default, derivado do nome do model) |
| `slugAttribute` | `slug` |
| `team_id` (spatie) | FK para `organizacoes.id` — mantido com o nome default do pacote (ver ADR-05) |
| `ai_runs.tenant_id` | `organizacoes.id` em string, ou `'default'` fora de request |

## Testes

> Ver `04-casos-de-teste.md` para especificação completa dos cenários.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty`
- [ ] `php artisan test --compact --filter=Tenancy`
- [ ] `composer test:kit` (a fundação inteira, com tenancy desligada)
- [ ] `php artisan kit:tenancy --demo` num clone limpo, e navegar `/app/acme` × `/app/globex`

## Commits

- `:sparkles: tenancy: modelo Organizacao, painel /app por tenant e comando kit:tenancy`
- `:sparkles: tenancy: CRUD de organizacoes e vinculo de usuarios no /admin`
- `:white_check_mark: tenancy: testes de isolamento entre organizacoes`
- `:memo: tenancy: wiki da feature e documentacao do kit`

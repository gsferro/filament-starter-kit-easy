# Arquitetura e design

> Como o kit é montado e por quê. Leia junto de [convencoes.md](convencoes.md).

## A ideia central

Um Laravel comum com **três painéis Filament** montados sobre a mesma base de usuários e a mesma sessão. Não há tenancy, não há microserviço, não há camada de serviço obrigatória: é Laravel idiomático, com uma fina camada de "cola" que aplica defaults e fecha buracos dos plugins.

O que justifica os três painéis é **separação de superfície**, não de dados:

| Painel | Path | Provider | Discovery | Quem entra |
|---|---|---|---|---|
| **App** | `/app` | `AppPanelProvider` | `app/Filament/App/{Resources,Pages,Widgets}` | quem tem papel com `roles.painel = 'app'` (`panel_user`) |
| **Admin** | `/admin` | `AdminPanelProvider` | `app/Filament/Admin/…` | quem tem papel com `roles.painel = 'admin'` (`admin`) |
| **Infra** | `/infra` | `InfraPanelProvider` | `app/Filament/Infra/…` | quem tem papel com `roles.painel = 'infra'` (`infra`) |

O `master_global` entra nos três, mas **não pela coluna** — ver [Painel é dado do papel](#painel-é-dado-do-papel).

`/app` é o painel **default** (`->default()`) e nasce vazio de propósito — é onde o projeto do usuário cresce. Quem administra usuários não precisa (nem deve) enxergar logs, filas e comandos operacionais, e vice-versa.

> Até a 0.10.0 o `/app` era aberto a **qualquer autenticado** (`canAccessPanel()` devolvia `'app' => true`). Deixou de ser: hoje sem papel não se entra em painel nenhum. É quebra deliberada, e o caminho de volta é rodar os dois seeders (abaixo).

## Camadas

```
┌─ Painéis Filament ───────────────────────────────────────────┐
│  app/Providers/Filament/{App,Admin,Infra}PanelProvider.php   │  ← plugins, rotas, hooks
│  app/Filament/{App,Admin,Infra}/{Resources,Pages,Widgets}    │  ← telas
│  app/Filament/Concerns/  app/Filament/Spotlight/             │  ← reuso entre painéis
├─ Cola do kit ────────────────────────────────────────────────┤
│  app/Providers/KitServiceProvider.php                        │  ← gates, health, ledger, defaults
│  app/Providers/Concerns/ConfiguraFilamentGlobal.php          │  ← defaults de TODA tabela/modal/toggle
│  app/Traits/{TemUuid,AuditsFillables}.php                    │  ← invariantes de model
├─ Domínio ────────────────────────────────────────────────────┤
│  app/Models/  app/Policies/  database/{migrations,seeders}    │
├─ IA ─────────────────────────────────────────────────────────┤
│  app/Ai/{Agents,Guardrails,Middleware,Listeners,Health}      │  ← ver ia.md
└──────────────────────────────────────────────────────────────┘
```

## A cola: `KitServiceProvider`

`app/Providers/KitServiceProvider.php` é o único lugar onde o kit toca o Laravel cru. Seis responsabilidades, uma por método:

| Método | O que faz |
|---|---|
| `configureDefaults()` | `CarbonImmutable` como default de data, `DB::prohibitDestructiveCommands()` em produção, política de senha (12 chars + `uncompromised()` em produção, 8 fora) |
| `configureGates()` | `Gate::before` do `master_global` + os gates de infra (`ver-logs`, `ver-ai-tasks`, `command-center:access`, `viewPulse`) |
| `configureAiLedger()` | liga `AgentPrompted` e `AgentStreamed` ao listener `RegistrarAiRun` |
| `configureHealthChecks()` | a lista de checks do Spatie Health que aparece em `/infra` |
| `configureClearCacheButton()` | comandos extras no botão "Limpar cache" |
| `configureProcessEnvNoWindows()` | repõe `SystemRoot`/`PATH` em `$_SERVER` no Windows (sem isso o Command Center morre com erro vazio de socket) |

### O gate do `master_global`

```php
Gate::before(fn (User $user) => $user->isMasterGlobal() ? true : null);
```

Retornar **`null`** (e não `false`) é o ponto: `null` deixa o fluxo normal de checagem continuar para quem não é master. `master_global` fica deliberadamente **sem permissions no banco** — sincronizar tudo criaria uma lista que apodrece a cada Resource novo.

Os gates de infra são definidos explicitamente para o papel `infra`. Dois deles — `command-center:prune-history` e `command-center:manage-commands` — ficam **sem `define()` de propósito**: só o `master_global` os tem, via `Gate::before`.

## A cola: `ConfiguraFilamentGlobal`

`app/Providers/Concerns/ConfiguraFilamentGlobal.php`, aplicada pelo `KitServiceProvider`, define como **toda** tabela, toggle, modal e coluna se comporta — nos três painéis e também nas telas dos plugins de terceiros, que não dá para editar de outro jeito.

Mora numa concern do provider, e não num plugin de painel, porque `configureUsing()` é estático do container: registrar em cada `PanelProvider` daria a impressão de configuração por painel com comportamento global (venceria o último registro).

Os defaults estão tabelados no [README](../README.md#configuração-global-do-filament). O que importa para quem edita:

- **Mudou ali, mudou em todo lugar.** Antes de alterar, pense no efeito nas telas de vendor.
- **Ações de filtro ficam fora do `configureUsing()` global** — em tabela sem filtro a ação nasce sem nome e derruba a página inteira. Oito telas do painel infra já caíram em 500 por isso.
- Macros do `asmit/resized-column` são aplicadas com `hasMacro()` antes: o pacote as registra em runtime, então a tabela degrada sem quebrar se ele sair.

## Ciclo de um request no painel

1. Middleware do painel (sessão, CSRF, bindings) → `Authenticate`.
2. **`User::canAccessPanel($panel)`** decide se o usuário entra. É a primeira fronteira, antes de qualquer gate.
3. `bootUsing()` roda com auth resolvido — é onde `AcoesDeCriacao::registrar()` monta as sugestões "Criar X" do Spotlight.
4. Navegação e ações consultam `canAccess()`/`canCreate()` (policies do Shield) antes de renderizar.
5. Render hooks injetam o gatilho da busca ⌘K (`GLOBAL_SEARCH_BEFORE`) e, no painel app, o chat do assistente (`BODY_END`).

## Autorização, em três níveis

| Nível | Onde | Pergunta que responde |
|---|---|---|
| **Painel** | `User::canAccessPanel()`, lendo `roles.painel` | Este usuário entra em `/admin`? |
| **Gate** | `KitServiceProvider::configureGates()` | Ele pode ver logs / rodar comandos / abrir o Pulse? |
| **Policy** | `app/Policies/*` + permissions do Shield | Ele pode editar **este** registro? |

`master_global` atravessa os níveis 2 e 3 pelo `Gate::before`, mas **não** o nível 1 — acesso a painel é checado antes, no model: `canAccessPanel()` chama `isMasterGlobal()` na primeira linha (`app/Models/User.php:76-78`) e só depois olha a coluna.

Permissions vêm do `ShieldPermissionsSeeder` (não do `shield:generate` interativo), e a matriz de papéis do `PapeisSeeder`. É isso que permite instalar sem intervenção humana.

### Painel é dado do papel

Não há lista de nomes de papel no código. Quem declara o painel é a **coluna `roles.painel`** (`database/migrations/2026_08_13_000001_add_painel_to_roles_table.php`), e `canAccessPanel()` a compara com o id do painel corrente (`app/Models/User.php:74-103`).

| Papel | `roles.painel` | Entra em | Contexto exigido |
|---|---|---|---|
| `master_global` | `null` | os três — por `isMasterGlobal()`, nunca pela coluna | global |
| `admin` | `admin` | `/admin` | global |
| `infra` | `infra` | `/infra` | global |
| `panel_user` | `app` | `/app` | qualquer organização |

**Nulo não é coringa.** Papel sem painel não abre painel algum: o default fecha. A analogia com `roles.team_id` (onde nulo *é* coringa) é armadilha — lá o nulo vale para a **definição** do papel, aqui valeria para a **concessão** de acesso, e um papel criado em branco na tela viraria chave-mestra em silêncio.

**O contexto muda com a tenancy do painel** (`app/Models/User.php:87`):

| Painel | `hasTenancy()` | O que exige |
|---|---|---|
| `/admin`, `/infra` | não | papel atribuído no **contexto global** (`model_has_roles.team_id = Tenant::CONTEXTO_GLOBAL`) — ser `admin` dentro de uma organização não é credencial para administrar a instalação |
| `/app` | sim | papel em **qualquer** organização; qual organização é decidido depois, por `canAccessTenant()` (que responde [404, não 403](#404-não-403)) |

A pergunta é respondida por `temPapelDoPainel()`, sobre a relação `papeisEmQualquerContexto()` — a `roles()` do spatie sem o `wherePivot(team_id)` do team corrente. É preciso: `canAccessPanel()` roda **antes** de o tenant da rota ser identificado.

Negativa vira log (`Log::channel('autenticacao')->warning`, com `motivo: sem_papel_do_painel`); acesso concedido não loga, para não encher o arquivo com o caminho feliz.

Permission continua **global por nome** — `ViewAny:User` é uma linha só na tabela do spatie. O Shield não sabe a que painel uma permission pertence: o nome é `{Ação}:{Model}` e o único diferenciador que chega ao banco é o `guard_name`, que é o mesmo `web` nos três painéis. Quem cruza painel × Resource × permission é `App\Support\Paineis`, varrendo `Filament::getPanels()` e perguntando ao próprio Shield. É dele que saem a matriz do `PapeisSeeder` e o agrupamento da tela `/admin/shield/roles`. O porquê completo está nas ADR-01 a ADR-03 de `wikis/specs/main/perfil-e-acesso-ao-painel/02-decisoes-arquiteturais.md`.

### Convite é a única porta de entrada

O painel `/app` tem registro ligado, mas **não é cadastro aberto**: `App\Filament\Pages\Auth\RegistroPorConvite` estende a página de registro nativa do Filament e recusa no `mount()` sem um token válido na query string. Registrar e aceitar convite passam a ser a mesma tela — e daí vêm de graça o rate limit (por IP e por e-mail), a transação e o auto-login.

O token é a credencial: quem o tem cria uma conta com o papel do convite. Por isso vai **hasheado** (`sha256`) para o banco, vale **uma vez** (`aceito_em`) e por um **prazo** (`expira_em`); em claro ele existe só no e-mail. Os três motivos de recusa dão a mesma resposta, pelo mesmo motivo do 404 dos tenants: distinguir vaza a existência do registro.

Quem decide quem entra é quem convida. E-mail, papel e organização vêm do convite — o formulário só coleta nome e senha, e `mutateFormDataBeforeRegister()` sobrescreve o e-mail com o do convite, porque estado de Livewire é do cliente.

## Busca ⌘K (Spotlight)

O campo da topbar é o **nativo do Filament**; o clique abre o overlay do `wezlo/filament-search-spotlight`. Quatro categorias, duas delas do kit:

| Categoria | Classe | Origem |
|---|---|---|
| Registros | `RecordsCategory` | vendor |
| Telas | `App\Filament\Spotlight\ResourcesAutorizadasCategory` | **kit** |
| Páginas | `App\Filament\Spotlight\PagesAutorizadasCategory` | **kit** |
| Ações "Criar X" | `ActionsCategory` alimentada por `App\Filament\Spotlight\AcoesDeCriacao` | vendor + **kit** |

As categorias do pacote **não** chamam `canAccess()`. Sem as versões do kit, a busca ofereceria telas que resultariam em 403 — vazamento de affordance, considerado bug aqui. O discovery de ações do pacote resolve URLs sem checar contexto e derruba a tela de login com 500; por isso `disableCreateActions()` + registro próprio em `bootUsing()`.

## Dashboards e widgets

Widgets do kit ficam em `app/Filament/{Admin,Infra}/Widgets/` e são descobertos automaticamente. Eles leem os dados que os painéis **já têm** (usuários, papéis, filas, saúde, auditoria, execuções de IA) — nenhum widget exige tabela nova.

Bases prontas vêm de `laboiteacode/filament-dashboard-widgets` (funil, timeline, metas, breakdown) e os contadores animados de `gsferro/filament-odometer-easy` / `gsferro/filament-stat-plus-easy`.

## Multi-tenancy (opt-in)

O kit nasce **single-tenant**. `php artisan kit:tenancy` liga o modo multi-tenant; sem ele, nada nesta seção existe na prática.

### Código em inglês, interface no idioma do negócio

| Camada | Vocabulário |
|---|---|
| Código | `App\Models\Tenant`, tabela `tenants`, coluna `tenant_id`, `getTenants()`, `canAccessTenant()` — o padrão da API do Filament |
| Interface e URL | `config('kit.tenancy')` → `label`, `label_plural`, `slug`. Default: "Organização" / "organizacoes" |

Assim a documentação oficial do Filament se lê sem tradução mental, e cada projeto troca o termo (Empresa, Cliente, Escola, Unidade) sem tocar em código. O gancho oficial para o rótulo é `HasCurrentTenantLabel`, implementado em `Tenant::getCurrentTenantLabel()`.

### O que muda em cada painel

| Painel | Com tenancy |
|---|---|
| `/app` | vira `/app/{slug}`; o usuário só enxerga os tenants a que está vinculado |
| `/admin` | ganha o CRUD de tenants + o vínculo de usuários. **Não** é escopado — quem administra precisa ver todos |
| `/infra` | inalterado. Saúde, filas e logs são da instalação, não de um cliente |

### As peças

| Arquivo | Papel |
|---|---|
| `app/Models/Tenant.php` | o tenant, com a constante `CONTEXTO_GLOBAL` |
| `app/Traits/BelongsToTenant.php` | relação + escopo global + preenchimento de `tenant_id` nas models de negócio |
| `app/Models/User.php` | `HasTenants`: `tenants()`, `getTenants()`, `canAccessTenant()`, `papeisEmQualquerContexto()` |
| `app/Http/Middleware/DefinirTenantDePermissoes.php` | fixa o contexto de papéis do spatie a cada request |
| `app/Console/Commands/KitTenancy.php` | o comando que liga tudo |
| `app/Filament/Admin/Resources/Tenants/` | CRUD + `UsersRelationManager` (o vínculo) |
| `app/Ai/Support/ResolvedorDeTenant.php` | tenant das execuções de IA (`ai_runs.tenant_id`, budget) |

### Duas camadas de escopo, de propósito

1. **O Filament** escopa sozinho os resources cuja model tem a relação de posse — mas *só* os que passam por um Resource. A documentação é explícita: model sem resource não é escopada.
2. **A trait `BelongsToTenant`** fecha esse buraco no próprio model, cobrindo job, comando, listener, widget e API.

Em model com resource as duas se sobrepõem, aplicando a mesma condição — sem efeito colateral.

### Papéis: global × por tenant

Esta é a parte mais sutil. Com `permission.teams` ligado:

| Conceito | Coluna | Nulo permitido? | Significado |
|---|---|---|---|
| **Definição** do papel | `roles.team_id` | sim | nulo = papel disponível em qualquer tenant |
| **Atribuição** do papel | `model_has_roles.team_id` | **não** | sempre um valor: o tenant, ou `Tenant::CONTEXTO_GLOBAL` (`0`) |
| **Painel** do papel | `roles.painel` | sim | nulo = **não abre painel nenhum**. É a coluna oposta à de cima: aqui nulo fecha |

O spatie não tem "atribuição global" — a coluna é NOT NULL. Mas o kit precisa de papéis globais: `master_global`, `admin` e `infra` governam painéis que não têm tenant nenhum. Daí o sentinela `0`:

- atribuição em `0` → vale em `/admin`, `/infra`, console, jobs e seeders;
- atribuição com o id de um tenant → vale só dentro dele, no `/app`.

`KitServiceProvider::configureTenancy()` fixa `0` como contexto padrão do processo; o `DefinirTenantDePermissoes` sobrescreve por request no `/app`.

Para consultar papel **sem** depender do contexto corrente, o `User` tem a relação `papeisEmQualquerContexto()` (`app/Models/User.php:166-175`): é a `roles()` do spatie sem o `wherePivot(team_id)` que ele acrescenta com `permission.teams` ligado. É sobre ela que `isMasterGlobal()` e `temPapelDoPainel()` perguntam — sem isso o `master_global` perderia os poderes justamente ao entrar no `/app`, e `canAccessPanel()`, que roda antes de o tenant da rota existir, não teria contexto para consultar.

> O antigo `User::temPapelGlobal()` **foi removido**. Ele trocava o `PermissionRegistrar` do container e descarregava a relação duas vezes para responder uma pergunta de leitura. Quem o chamava usa hoje `temPapelDoPainel()` ou `isMasterGlobal()`.

Os gates de `/infra` seguem o mesmo caminho: `temPapelDoPainel('infra', CONTEXTO_GLOBAL)` no lugar de `hasRole('infra')` (`app/Providers/KitServiceProvider.php:96-104`). Com `hasRole()` o mesmo gate respondia diferente conforme a organização aberta no request — e a pergunta é sobre a instalação, não sobre a organização.

### Por que o comando recria o banco

A migration de permissões do spatie cria as colunas de team **condicionalmente**, lendo `config('permission.teams')` em tempo de execução. Ligar a flag depois de migrar deixa config e schema incoerentes, em silêncio. Refazer aditivamente exigiria recriar índices únicos — em SQLite, recriar a tabela.

Por isso `kit:tenancy` exige árvore git limpa, avisa que é destrutivo e roda `migrate:fresh --seed`. **A hora de rodar é o dia 1 do projeto.** Projeto com dados em produção precisa migrar à mão.

### Contratos que o model do tenant precisa implementar

| Contrato | Por quê |
|---|---|
| `HasName` → `getFilamentName()` | **obrigatório aqui.** Sem ele o Filament cai em `$tenant->getAttributeValue('name')`, e a coluna do kit é `nome` — o retorno vira `null` e o método, tipado como `string`, estoura `TypeError` ao montar o menu de tenant |
| `HasCurrentTenantLabel` → `getCurrentTenantLabel()` | o rótulo configurável acima do nome no seletor |

Toda coluna em pt-BR que o Filament espera em inglês precisa de um contrato desses. É o preço de manter o domínio em português com uma API em inglês — e o erro só aparece ao renderizar a página, nunca num teste de model.

### 404, não 403

Acesso a um tenant não vinculado responde **404**. É do Filament (`IdentifyTenant` faz `abort(404)`) e é deliberado: um 403 confirmaria que o tenant existe, e bastaria varrer slugs para enumerar os clientes da instalação.

### Testes

Ficam em `tests/Tenancy/`, suíte própria e mesmo grupo `kit`. A separação é de **bootstrap**, não de organização: `Tests\TenancyTestCase` fixa a config em `createApplication()`, que roda antes das migrations do `RefreshDatabase` — e o Pest não permite dois TestCases na mesma pasta.

`Tests\TestCase` invalida o schema quando o modo muda, para que `--group=kit` rode os dois modos no mesmo processo sem colisão.

## Erros e traduções

- Páginas de erro (403, 404, 419, 500, 503) são do `anselmokossa/filament-sentinel`, com views próprias em `resources/views/errors/`. A de 403 só mostra o diagnóstico de permissão **fora de produção**.
- Traduções de plugins ficam em `lang/vendor/<pacote>/pt_BR/` — nunca editando o `vendor/`.

## Agendamentos

`routes/console.php` guarda o schedule do kit: `health:check` a cada 15 minutos e `authentication-log:purge` diário. Backup vem comentado, para você ligar ao configurar o destino. Nada disso roda sem `php artisan schedule:work` (já incluso no `composer dev`) ou o serviço `scheduler` do compose — e é justamente o `ScheduleCheck` do Health que denuncia o agendador parado.

## Testes

| Suíte | Pasta | Para quê |
|---|---|---|
| **Kit** | `tests/Kit/` | a fundação: acesso aos três painéis, telas de infra/admin de pé, invariantes (uuid, gates, auditoria), contrato da camada de IA |
| **Sua** | `tests/Feature`, `tests/Unit` | o seu negócio — o kit nunca encosta |

Ambas usam Pest com `RefreshDatabase` (`tests/Pest.php`). A suíte do kit também recebe o grupo `kit`, então `php artisan test --group=kit` e `composer test:kit` são equivalentes.

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

O **seletor de idioma** (`bezhansalleh/filament-language-switch`) mora aqui pelo mesmo motivo do Panel Switch, e não em cada `PanelProvider`: `LanguageSwitch::configureUsing()` é registro estático do container e o pacote pendura um **render hook global** — não é plugin de painel. Registrar por painel daria a impressão de configuração por painel com efeito global.

Quem manda na lista é `config('kit.idiomas')`, lida **dentro** da closure. Lida fora, ela seria avaliada uma vez no boot e capturada por valor: o seletor exibiria a lista que existia naquele instante, não a que o request tem — foi defeito real, pego pelo caso "mostra o seletor quando há um segundo idioma". E é **lista, não booleano**: com um idioma só o botão não aparece, dentro nem fora do painel. Não há flag para alguém deixar ligada com um idioma só. A contagem é repetida explicitamente para as telas de login, porque `isVisibleOutsidePanels()` avalia só a flag — dentro do painel o pacote já exige mais de um locale sozinho.

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

#### Duas vias, decididas no aceite

Um convite para um endereço que **já tem conta** não é erro: é uma **oferta de acesso**. A via não é escolhida por quem convida (que não sabe, nem deveria saber, se o endereço já existe) nem congelada na criação — é uma pergunta ao banco no momento do aceite, `Convite::usuarioExistente()`. Entre criar o convite e alguém clicar podem passar dias, e a pessoa pode ter criado conta nesse meio-tempo por outro caminho.

| E-mail do convite | Via | O que o token faz | Quem confirma |
|---|---|---|---|
| não tem conta | **registro** — `aceitar()` cria o usuário (já com `email_verified_at`: o token prova a posse do endereço) | **suficiente** | quem tem o link |
| já tem conta | **oferta de acesso** — `aceitarComoUsuarioExistente()` vincula quem já existe, sem criar segunda conta | **necessário, não suficiente** | a própria pessoa, autenticada, com o e-mail conferido no model |

Na via de oferta o token sozinho não abre nada: interceptar o link não dá nada a quem não tem a senha do endereço convidado. A asserção `$user->email === $convite->email` está **no model** (`exigirDono()`), não na query da tela — a tela é filtro de UI. Ver `.ai/rules/filament.md`.

Dois caminhos chegam ao aceite, e o link é o canônico:

- **O link do e-mail** funciona sempre — inclusive para quem tem conta e **zero** organizações, ou papel só de `/admin`/`/infra`. A caixa de entrada não alcança esses casos (é uma página do painel `app`, sob `/app/{tenant}` com tenancy), e não inventamos organização pessoal para destravar uma tela.
- **A caixa de entrada** (`App\Filament\App\Pages\ConvitesRecebidos`, no menu do usuário, com a contagem de ofertas pendentes) é conveniência para quem já está dentro — e é o único lugar de onde se **recusa**: link tem um destino só. A recusa fica registrada em `recusado_em`, e um convite recusado não volta a valer nem pelo link; reconvidar é criar outro.

O consumo é um `update` condicional (`WHERE aceito_em IS NULL AND recusado_em IS NULL`), não um `SELECT` seguido de `save()`: na via de oferta não existe o `unique` de `users.email` para abortar um segundo aceite concorrente, e `syncWithoutDetaching`/`assignRole` são idempotentes — os dois cliques passariam.

Convidar uma turma é a **mesma porta**, com uma entrada a mais: a ação "Convidar em massa" no header das duas listagens de convites cola N endereços, um papel e uma organização, e chama `Convite::enviar()` N vezes. **Não existe segundo fluxo de envio** — nenhum Job de lote (a notificação já é `ShouldQueue`, então cada e-mail já é um job), nenhuma coluna de lote, nenhuma transação em volta: cada endereço é sua própria unidade, e é isso que dá **resultado parcial** — o décimo segundo endereço com problema não impede os outros trinta e nove. O que não saiu volta como lista de `{email, motivo}` e vira resumo na tela e contagem por motivo no log. É uma `Action` e não uma `Page` de propósito: `Page` nova no painel `app` gera permission que entra na matriz do `panel_user`.

#### O convite cobra a si mesmo

`kit:convites-lembrar`, agendado diariamente em `routes/console.php`, manda **um** lembrete por convite pendente por dia devido (`kit.convites.lembretes_dias`, D+3 e D+5 por default; lista vazia desliga). Um lembrete por convite **por execução**, por construção — há uma única chamada de `lembrar()` no laço —, então cron parado uma semana se recupera nas execuções seguintes em vez de disparar uma rajada.

O lembrete **não chama `enviar()`**, e é aí que está a decisão. O token em claro existe no e-mail e em lugar nenhum mais: dias depois, dentro de um cron, ele não é recuperável. Reenviar o link exigiria rotacionar o token — e um lembrete que caísse no spam teria **revogado** o único link válido que a pessoa tinha. Então **dois tokens hasheados abrem o mesmo convite**: `token` (o do envio) e `token_lembrete` (o do último lembrete), os dois presos ao **mesmo `expira_em`**, que o lembrete não renova. Cada lembrete sobrescreve `token_lembrete`, então são no máximo dois links vivos; `enviar()`, `aceitar()`, `aceitarComoUsuarioExistente()` e `recusar()` limpam a coluna, para que convite reenviado ou consumido não deixe link pendurado.

`Convite::valido()` é a porta única dos dois, e o `orWhere` do par vive dentro de um `where(closure)`: **os três filtros de estado ficam fora do agrupamento**. Sem o closure o `OR` parte o `WHERE` inteiro e cada token passa a valer sozinho, sem prazo e sem estado. Ver ADR-01 de `wikis/specs/main/lembretes-de-convite/`.

O relógio é `enviado_em`, não `created_at`: `enviar()` é também o reenvio, então `created_at` pode estar a semanas do último envio — e a linha anterior à migration, com `enviado_em` nulo, fica de fora do lote em vez de ganhar um relógio fabricado. O contador `lembretes_enviados` é zerado por `enviar()`, e o teto **é** `count(dias)` — não existe uma segunda chave de máximo, porque dois botões discordam em silêncio.

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

## Os quatro grupos do `/infra`

`InfraPanelProvider` declara a ordem dos grupos explicitamente (`->navigationGroups([...])`) porque sem isso o Filament os ordena em ordem alfabética, e a navegação vira lista sem hierarquia de leitura. Cada plugin é encaixado num deles **pelo mecanismo que ele próprio expõe** — e os três mecanismos aparecem lado a lado nas telas novas da 0.17.0:

| Grupo | Telas | Como a tela entra no grupo |
|---|---|---|
| **Observabilidade** | Saúde (Health), Pulse, **Exceções** | método do plugin (`->navigationGroup('Observabilidade')`) ou `$navigationGroup` da página do kit |
| **IA** | Execuções de IA, Dashboard de IA | `$navigationGroup` do `AiRunResource` e o `navigationItems()` do painel |
| **Trilhas** | Acessos, Auditoria, Arquivos de log, **E-mails enviados** | tradução: o `MailLogResource` lê `__('filament-maillog::filament-maillog.navigation.group')` — não há chave de config nem método de plugin, então o grupo mora em `lang/vendor/filament-maillog/pt_BR/` |
| **Sistema** | Central de comandos, Mapa de dependências, **Lixeira** | método do plugin |

Backup Monitor e Auditing não expõem nem método nem chave: ficam soltos no topo do menu, antes dos grupos. Filas vêm de `config/filament-jobs-monitor.php`.

**Exceções** (`bezhansalleh/filament-exceptions`) responde a pergunta que nenhuma das outras respondia: *qual exception está estourando, e quantas vezes*. Saúde cobre estado, Pulse cobre desempenho, Logs Explorer cobre o arquivo e Filas cobrem o job — achar um erro recorrente exigia saber o dia e caçar dentro do arquivo. O plugin é registrado nos **três** painéis, com navegação só aqui; o porquê está em [convencoes.md](convencoes.md#armadilhas-já-resolvidas).

**E-mails enviados** (`tapp/filament-maillog`) existe por causa do convite: ele é a única porta de entrada de usuário e não deixava registro nenhum, então "o convite não chegou" era impossível de responder — não dava para separar *não foi enviado* de *foi enviado e caiu no spam*.

**Lixeira** (`promethys/revive`) fica no `/infra` e **não** no `/app`, apesar de o pacote suportar escopo por tenant: uma tela que lista tudo o que foi apagado na instalação é, ela mesma, exposição de dado. Aqui entrar já exige `master_global` ou `infra`; no `/app` qualquer papel do painel veria. Por isso ela vai com `withoutScoping()` — o `/infra` não tem tenancy, não haveria de onde tirar o escopo.

As duas primeiras **gravam dado sensível**: stack trace com parâmetro de request e corpo de e-mail com o link de aceite. É metade da razão de viverem só aqui; a outra metade é a [retenção](#agendamentos), que não é opcional.

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
| `/app` | vira `/app/{slug}`; o usuário só enxerga os tenants a que está vinculado. Ganha a **administração da própria organização** (usuários e convites recortados a ela), para quem tem o papel `admin_app` |
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

## Camada de mídia

Arquivo anexado a registro é `spatie/laravel-medialibrary`, pela ponte oficial `filament/spatie-laravel-media-library-plugin`. Não há tabela de anexo por model, nem coluna de caminho: tudo vive na tabela **polimórfica** `media` (`morphs('model')`), e a model declara coleções em vez de colunas.

É a polimorfia que decide o isolamento. O arquivo pertence a **um registro**, e o registro já é escopado por `BelongsToTenant` — quem não alcança o dono não alcança o anexo. **O isolamento por organização é herdado, não configurado**: não existe coluna de tenant em `media`, e não existe checkbox a lembrar de marcar. Foi esse o critério que escolheu este pacote em vez do `awcodes/filament-curator`, cuja biblioteca é **compartilhada** por natureza e cujo escopo por tenant nasce **desligado** — o isolamento passaria a depender de alguém ligar uma flag, e falharia aberto se esquecesse.

### Os três furos do isolamento herdado

Herdado quer dizer que ele existe **quando há de quem herdar**. Três casos em que não há:

1. **Query direta em `Media`.** `Media::query()` não tem escopo nenhum — a tabela não tem coluna de tenant e o escopo do dono não a alcança. Contar, listar ou exportar mídia por ali devolve a instalação inteira.
2. **Dono que não é escopado.** O `User` do kit pertence a **várias** organizações (a pivot `tenant_user`), então não usa `BelongsToTenant`: avatar e qualquer mídia de usuário são globais por construção. É correto para foto de perfil e errado para qualquer coisa que seja de uma organização só.
3. **Model nova sem `BelongsToTenant`.** A mídia dela é global pelo mesmo motivo. A trait não é opcional numa model de negócio — ver [Model de negócio pertence a um tenant](convencoes.md#model-de-negócio-pertence-a-um-tenant).

### A camada de URL é assinada, não autorizada

O escopo herdado vale para a **query**. Não vale para o **arquivo**, e quem decide isso é o **disco** — não a visibilidade declarada no campo de upload.

`MEDIA_DISK` nasce **`local`**, cuja `serve => true` (`config/filesystems.php`) registra a rota `storage.local`, que **exige URL assinada**. Com `public` o arquivo cairia em `storage/app/public`, servido pelo symlink `public/storage`: caminho `/storage/{id}/{arquivo}`, ID **sequencial**, alcançável **sem sessão**. A tenancy do Filament vive no request do painel; ela nunca chega ao sistema de arquivos.

O que isso resolve, e o que **não** resolve:

- **Resolve** o arquivo alcançável por quem só adivinhou o ID. Sem assinatura a rota devolve 403 antes mesmo de checar se o arquivo existe.
- **Não resolve** autorização. A rota valida a **assinatura**, não o usuário: **quem tem o link entra, sem sessão, durante a validade**. É limite aceito e documentado, não descuido. Anexo que precise de autorização por organização pede rota própria consultando a policy antes de entregar.

Duas consequências para quem escreve código aqui:

1. **`Media::getUrl()` de mídia privada responde 403** — falha fechada. Link publicável se obtém com **`getTemporaryUrl()`**.
2. **Coleção de mídia declara o disco** (`->useDisk('local')` em `registerMediaCollections()`), mesmo sendo redundante com o default. É defesa em profundidade: trocar `MEDIA_DISK` de volta não reabre o vazamento na coleção.

Avatar e logo continuam em `->disk('public')` **explícito**, e isso é deliberado: aparecem na tela de login, antes de existir sessão para assinar nada.

Instalação anterior a esta mudança tem mídia já gravada em disco público, e a config nova não a alcança: **`php artisan kit:midia-privada`** (com `--dry-run`) move original e conversões e atualiza as colunas `disk`/`conversions_disk`. Ele preserva coleção que declara `useDisk('public')`.

## Import e export: o worker perde o tenant, o export o herda

Import e export de CSV são **nativos do Filament 5** — `ImportAction`, `ExportAction`, os jobs, o batch, as tabelas `imports` / `exports` / `failed_import_rows`. O kit não escreve wrapper nenhum em volta disso. Ele acrescenta duas classes base em `app/Support/ImportExport/`, e a razão de existirem é uma assimetria que não aparece na API: **os dois lados atravessam a fronteira de organização em direções opostas.**

| | Onde a query nasce | O que acontece com `BelongsToTenant` |
|---|---|---|
| **Export** | no **request** (`CanExportRecords::getTableQueryForExport()`, a tabela da tela) | o escopo global aplica o `where tenant_id = X`, a query é serializada **com** ele dentro, e é isso que o job executa |
| **Import** | no **worker** (`Importer::resolveRecord()`, uma linha do CSV por vez) | `Filament::getTenant()` devolve `null` — não há painel nem rota na sessão — e o escopo global vira **no-op** |

O isolamento do export é **herdado**, e por isso `ExportadorDoKit` não tem uma linha de código de tenant: nada a construir. O do import tem de ser **construído**, porque o que existiria de graça foi perdido antes de a primeira linha ser lida. `ImportCsv` restaura o `auth()->setUser()` — o **usuário**, para que a policy e a notificação funcionem. Nada restaura o tenant, e o próprio `Importer` do Filament avisa no código que não faz esse tipo de verificação (`// Security: This method runs without policy checks.`).

Sem `ImportadorDoKit`, as duas consequências são silenciosas: linha de CSV cuja chave casa com registro de **outra** organização faz UPDATE nele (sem 403, sem log), e linha nova nasce com `tenant_id` **nulo**, invisível para todo mundo — inclusive para quem importou.

A correção é de **duas pontas**, e nenhuma delas funciona sozinha:

1. **A Action captura o tenant no request** — `->options(['tenant_id' => Filament::getTenant()?->getKey()])`. O array de options viaja no payload do job, que é onde o dado ainda existe.
2. **A classe base o usa nas duas pontas** — filtra a resolução do registro e preenche a criação, no lugar do hook `creating` da trait, que ali não tem contexto.

E ela **falha fechada**: tenancy ligada, model que usa `BelongsToTenant`, nenhum `tenant_id` nas options ⇒ a linha é recusada com `RowImportFailedException` e o motivo vai para o log. O contrário — seguir sem escopo — é exatamente o defeito que a classe fecha. Em single-tenant, ou em model que não é de organização (`AgenteIa`, `Tenant`), a exigência não se aplica: não há fronteira, e cobrar uma mataria a feature no modo que o kit tem por default.

> ⚠️ **Cenário de teste que passa pela tela não prova nada aqui.** Ele roda no request, onde o tenant existe: fica verde com a classe base inteira removida. O que prova é chamar o importador **direto**, sem tenant no contexto — a reprodução fiel do worker. É o que `tests/Tenancy/ImportExportTenancyTest.php` faz.

### O que mais essas duas classes decidem

- **Fórmula neutralizada em toda coluna.** `preventFormulaInjection()` existe **por coluna** no Filament e nasce **desligado**. Célula começando em `=`, `+`, `-` ou `@` é fórmula quando alguém abre o CSV no Excel, e o dado que a preencheu veio de formulário de usuário. `ExportadorDoKit` aplica a neutralização a toda coluna que a subclasse declarar — daí a subclasse declarar `colunas()` e não `getColumns()`, que é `final`. `ImportadorDoKit` liga a mesma coisa, porque o CSV de linhas que falharam volta para download.
- **Autorização explícita, porque a Action não tem.** `Actions\Concerns\CanBeAuthorized` nasce com autorização `null` — liberada. `import` e `export` foram acrescentados a `config('filament-shield.policies.methods')` (e a `single_parameter_methods`, porque nenhum dos dois recebe registro) e cada Action carrega `->authorize('import')` / `->authorize('export')` na mão. Ler a listagem e levar a listagem inteira embora são duas permissões diferentes — e `panel_user` não nasce com nenhuma das duas, por subtração de **prefixo de ação** no `PapeisSeeder`.
- **Coluna ausente é decisão de arquitetura, não esquecimento.** `tenant` fora do `ProjetoImporter` (senão o CSV escolhe a organização de destino e a fronteira acima fica decorativa), `token` fora do `ConviteExporter`, `request`/`response` fora do `AiRunExporter`. O gerador do Filament recoloca as três em `--force`; quem guarda a ausência são os testes de `tests/Kit/ImportExportTest.php`.
- **Rastro sem tabela nova.** `imports` e `exports` são do pacote e **não têm `tenant_id`** — não respondem de qual organização saiu o arquivo, que é a pergunta de uma auditoria de vazamento. `KitServiceProvider::configureRastroDeImportExport()` completa isso no channel `tenancy`. Import tem eventos de verdade (`ImportStarted` / `ImportCompleted`); o export **não tem nenhum** no Filament, então o gancho é o model `Export` (`created`, e `completed_at` recém-preenchido). A retenção dos dois históricos é de **30 dias**, e a do export apaga o **arquivo** antes da linha — ver [Retenção](#retenção-o-prazo-é-config-o-expurgo-é-schedule).

## Erros e traduções

- Páginas de erro (403, 404, 419, 500, 503) são do `anselmokossa/filament-sentinel`, com views próprias em `resources/views/errors/`. A de 403 só mostra o diagnóstico de permissão **fora de produção**.
- Traduções de plugins ficam em `lang/vendor/<pacote>/pt_BR/` — nunca editando o `vendor/`.

## Agendamentos

`routes/console.php` guarda o schedule do kit: `health:check` a cada 15 minutos, `authentication-log:purge` diário, o lembrete de convites às 8h e a **retenção das duas trilhas que o kit grava**. Backup vem comentado, para você ligar ao configurar o destino.

### Retenção: o prazo é config, o expurgo é schedule

`config('kit.retencao')` declara **quanto tempo** exceções e e-mails sobrevivem (14 dias nos dois, alinhado ao `days` da rotação de log — a trilha morre junto com o log que a originou, não depois). Quem **aplica** é `routes/console.php`. Separar os dois é o ponto: com o agendador parado, o número no config é intenção declarada e as tabelas crescem sem teto — as duas crescem por evento e as duas guardam dado sensível.

São **dois mecanismos diferentes**, e é o pacote que decide qual:

| Trilha | Mecanismo | Por quê |
|---|---|---|
| Exceções | `model:prune --model=…`, 02:00 | o `Exception` do pacote declara `prunable()`, o contrato do Laravel; a data de corte sai de `modelPruneInterval()` no `InfraPanelProvider`, lendo o mesmo config |
| E-mails | `delete()` direto num `Schedule::call`, 02:10 | o `MailLog` **não** implementa `Prunable`. Passá-lo no `--model` daria um agendamento verde que nunca apaga nada — o pior resultado possível para uma rotina de dado pessoal |

O `--model` é explícito de propósito: a varredura automática do `model:prune` alcançaria qualquer model podável do projeto, inclusive as **suas**, e retenção de dado de terceiro não pode ser efeito colateral de um agendamento do kit. Zero ou negativo em qualquer dos dois prazos desliga aquela poda, sem apagar nada por engano. Nada disso roda sem `php artisan schedule:work` (já incluso no `composer dev`) ou o serviço `scheduler` do compose — e é justamente o `ScheduleCheck` do Health que denuncia o agendador parado.

## Testes

| Suíte | Pasta | Para quê |
|---|---|---|
| **Kit** | `tests/Kit/` | a fundação: acesso aos três painéis, telas de infra/admin de pé, invariantes (uuid, gates, auditoria), contrato da camada de IA |
| **Sua** | `tests/Feature`, `tests/Unit` | o seu negócio — o kit nunca encosta |

Ambas usam Pest com `RefreshDatabase` (`tests/Pest.php`). A suíte do kit também recebe o grupo `kit`, então `php artisan test --group=kit` e `composer test:kit` são equivalentes.

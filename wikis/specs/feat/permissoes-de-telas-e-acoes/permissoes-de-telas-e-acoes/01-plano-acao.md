# Plano de Ação — Permissões de telas e ações

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: — (mas consome decisões de `wikis/specs/feature/v1-enriquecimento-kit/hub-de-cards-opcional/` ADR-02/ADR-03 e de `wikis/specs/main/admin-da-organizacao/`)
- **Motivo**: —
- **Toca infra compartilhada?**: **sim** → `config/filament-shield.php` (matriz de permissões),
  `database/seeders/PapeisSeeder.php` (recorte por papel), 5 Pages e 23 Widgets dos três painéis.
  **A regressão é obrigatória**, contra `tests/Kit/HubDeCardsTest.php`,
  `tests/Kit/PaginasInfraTest.php`, `tests/Kit/PapeisSeederTest.php` (e equivalentes),
  `tests/Tenancy/**` e a suíte de browser inteira (52 telas).

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Levantar o que não tem permissão | 0 (varredura, já feita — ver `## Achado estrutural`) | — |
| RQ-02 | Criar as permissões que faltam | 3 (6 permissões novas) | Pages e Widgets **já tinham** permissão gerada; só Actions precisavam de permissão nova |
| RQ-03 | Deixar selecionáveis na tela de papéis | 3 (aba `custom_permissions` ligada; as de `resources.manage` já caem na aba de Resources) | — |
| RQ-04 | Aplicar a permissão na superfície | 1, 2, 5 | — |
| RQ-05 | Toda tela com permissão específica | 1, 2 | Pages de vendor fora — ver `00-requisito.md` `## Fora desta entrega` e ADR-05 |
| RQ-06 | Todo link com permissão específica | 5 | `dashboardAiTasks`; o `NavigationItem` já estava coberto |
| RQ-07 | Toda action com permissão específica | 5 | — |
| RQ-08 | Default do kit | 3, 4 | as permissões nascem no seeder, nos papéis certos |
| RQ-09 | Concedível/revogável por papel sem editar código | 3, 4 | cada permissão nova é checkbox em `/admin/shield/roles` |

## Objetivo

Fechar o buraco entre **permissão que existe** e **permissão que é consultada**. O kit já grava no
banco `View:{Page}` e `View:{Widget}` para todas as 5 Pages e 23 Widgets próprios dos três painéis,
já as entrega aos papéis certos pelo `PapeisSeeder`, e já as mostra como checkbox em
`/admin/shield/roles` — mas **nenhuma classe do app consulta nenhuma delas**. Os defaults do
framework são permissivos, então desmarcar o checkbox não muda nada: quem abre o painel vê tudo.

Esta entrega faz duas coisas: (a) liga a consulta dessas permissões nas 5 Pages e 23 Widgets, e
(b) cria as 6 permissões que faltavam de verdade — as das Actions customizadas, que o Shield não
descobre porque Action não é entidade dele.

## Contexto

### Achado estrutural (cada ponto confirmado com `Read`)

Nenhuma classe de `app/` usa `HasPageShield` nem `HasWidgetShield` — `grep` retorna zero. Os
defaults do framework são **fail-open**, e o próprio vendor documenta isso em comentário:

- `vendor/filament/filament/src/Pages/Concerns/CanAuthorizeAccess.php:17-24` —
  `canAccess(): bool { return true; }`, com o comentário
  *"Security: Custom pages default to allowing access for all authenticated panel users."*
- `vendor/filament/widgets/src/Widget.php:34-37` — `canView(): bool { return true; }`
- `vendor/filament/actions/src/Concerns/CanBeAuthorized.php:16-21` —
  *"Security: Actions do not have automatic policy-based authorization. Authorization defaults to
  `null` (allowed for all users)."*
- `vendor/filament/filament/src/Resources/RelationManagers/RelationManager.php:348-353` —
  *"Security: `AssociateAction`, `AttachAction`, `DetachAction`, and `DissociateAction` only check
  `isReadOnly()` — they do not check specific policy methods."* O arm do `match` que confirma está
  em `:359`.
- `vendor/bezhansalleh/filament-shield/src/Traits/HasPageShield.php:19-27` e
  `.../HasWidgetShield.php:14-22` — as traits que ligariam a permissão. Não aplicadas em lugar nenhum.

Confirmação de que a permissão **já existe** (rodado neste worktree, `php artisan tinker`, contra
o banco semeado — o `mcp__laravel-boost__database-query` não chegou a ficar exposto, ver
`03-progresso.md`):

| Painel | Pages com `View:` | Widgets com `View:` |
|---|---|---|
| `admin` | `HubDeAdministracao`, `MyProfilePage` | `UsuariosVisaoGeralStats`, `UsuariosPorPapel`, `UltimosUsuariosCadastrados`, `ConvitesPorSituacao`, `AgentesIaStats`, `AgentesIaPorProvider`, `ProgressoOnboarding` |
| `infra` | `HubDeInfraestrutura`, `Pulse`, `MyProfilePage` + 9 de vendor | 16 do kit + `ComposerReleaseOverviewWidget` de vendor |
| `app` | `ConvitesRecebidos`, `HubDoNegocio`, `MyProfilePage` | — (o painel `app` não tem Widget) |

`Paineis::permissoes($painel)` já devolve as três famílias juntas, porque
`FilamentShield::getEntitiesPermissions()` faz `merge` de Resource + Page + Widget +
`custom_permissions` (`vendor/bezhansalleh/filament-shield/src/FilamentShield.php:114-124`). Ou
seja: **a matriz do `PapeisSeeder` já contempla Page e Widget**. Nenhuma mudança de seeder é
necessária para elas.

### Furos concretos, por família

**Pages sem consulta de permissão (5, todas código do kit):**

| Page | Estado hoje | O que vaza |
|---|---|---|
| `Infra/Pages/Pulse.php:28` | nenhum `canAccess()` | servidores, filas, cache, slow queries, exceções. O comentário `:22-23` fala do gate `viewPulse`, que protege a rota **do pacote** `/pulse`, não esta Page |
| `Infra/Pages/HubDeInfraestrutura.php:56` | nenhum `canAccess()` | o índice de 16 destinos do `/infra` |
| `Admin/Pages/HubDeAdministracao.php:69-71` | `canAccess()` = `config('kit.hub') && parent::canAccess()` — e `parent` é o `return true` | kill-switch de config, não autorização |
| `App/Pages/HubDoNegocio.php:70-72` | idem | idem |
| `App/Pages/ConvitesRecebidos.php:54-57` | `canAccess()` = tenancy + `Auth::check()` | a barreira real é a query por e-mail + `Convite::exigirDono()` |

**Widgets (23, todos código do kit):**

- **5 sem guarda alguma**: `Admin/Widgets/AgentesIaStats.php:18`, `AgentesIaPorProvider.php:19`,
  `UsuariosPorPapel.php:21` (contagem de usuários por papel), `UsuariosVisaoGeralStats.php:25`
  (lê `Role` e `Permission` direto), `UltimosUsuariosCadastrados.php:20` (nomes e e-mails; só o
  *link* do rodapé checa `UserResource::canViewAny()` em `:73`).
- **18 com `canView()` que não é autorização**: fazem `rescue(fn () => Schema::hasTable(...), false)`
  — checam **existência de tabela**, não permissão. `Infra/Widgets/IaStats.php:27-34`,
  `AuditoriaRecente.php:29-36`, `UltimosAcessos.php:32-39`, `AutenticacaoStats.php:27`,
  `FilasStats.php:32`, `FilasPorFila.php:29`, `FilasTaxaDeSucesso.php:37`, `IaCustoPorTask.php:26`,
  `IaExecucoesPorDia.php:48`, `IaExecucoesPorStatus.php:38`, `IaTokensPorDriver.php:26`,
  `PacotesComposer.php:27`, `SaudeAplicacaoStats.php:35`, `SaudeAplicacaoPorStatus.php:28`,
  `UltimoBackup.php:34`, `AuditoriaPorEvento.php:34`, `Admin/Widgets/ConvitesPorSituacao.php:46-53`,
  `ProgressoOnboarding.php:35-42`.

A barreira efetiva hoje, para os 23, é só o `User::canAccessPanel()`.

**Actions sem autorização (6, mais 1 furo de affordance):**

| Local | Action | O que faz sem permissão |
|---|---|---|
| `Admin/Resources/Convites/Tables/ConvitesTable.php:71` | `reenviar` | dispara e-mail e invalida o token anterior. Só `->visible()` por estado (`:76`) |
| `Admin/Resources/Tenants/RelationManagers/UsersRelationManager.php:58` | `AttachAction` | **vincula qualquer usuário à organização** — é o vínculo que `User::canAccessTenant()` consulta. O vendor só checa `isReadOnly()` |
| `.../UsersRelationManager.php:66` | `DetachAction` | remove o vínculo. Idem |
| `.../UsersRelationManager.php:89` | `papeisNaOrganizacao` | **atribui papéis** via `syncRoles()` (`:110`). Tem defesa em profundidade (`where('painel','app')` em `:98` e `:108`), então não escala para `admin`/`infra` — mas escala para `admin_app` |
| `App/Pages/ConvitesRecebidos.php:79` | `aceitar` | entra numa organização com o papel do convite |
| `App/Pages/ConvitesRecebidos.php:94` | `recusar` | invalida o convite |
| `Infra/Resources/AiRuns/Pages/ListAiRuns.php:25` | `dashboardAiTasks` | link externo sem `visible`; o destino é coberto pelo gate `ver-ai-tasks`, então é furo de **affordance** apenas |

**Já coberto corretamente** (é o padrão a imitar): `ImportAction`/`ExportAction` com
`->authorize('import')`/`->authorize('export')` em `ListAgentesIa.php:33-39`, `ListTenants.php:30-32`,
`ListAiRuns.php:36-38`, `ListProjetos.php:49-56`; `ConvidaEmMassa.php:45` com
`->authorize('create', Convite::class)`; `NavigationItem` `dashboard-ia` em
`InfraPanelProvider.php:115` com `->visible(fn () => auth()->user()?->can('ver-ai-tasks'))`; as
categorias do Spotlight consultando `canAccess()`.

## Análise dos Arquivos Existentes

### `vendor/bezhansalleh/filament-shield/src/Traits/HasPageShield.php`

`canAccess()` resolve a chave por `FilamentShield::getPages()[static::class]` e memoiza em
`static::$pagePermissionKey`. **Fail-open**: chave nula ou usuário nulo cai em `parent::canAccess()`,
que é `true`. A trait também define `shouldRegisterNavigation()` (`:14-17`) como
`static::canAccess() && parent::shouldRegisterNavigation()` — resolução tardia, então nossa
sobrescrita de `canAccess()` é respeitada, e o item de menu some junto com o acesso (RQ-06 de graça).

### `vendor/bezhansalleh/filament-shield/src/Traits/HasWidgetShield.php`

Mesma mecânica em `canView()` (`:14-22`). **Armadilha**: método definido na classe vence método de
trait, sem erro nenhum. Aplicar `use HasWidgetShield;` nos 18 widgets que já têm `canView()` faria a
trait ser **silenciosamente ignorada** — nada quebraria, nada avisaria, e a feature ficaria verde
sem existir. Daí o concern do passo 2.

### `vendor/bezhansalleh/filament-shield/src/Concerns/HasResourceHelpers.php` + `HasEntityTransformers.php:171-188`

`getDefaultPolicyMethodsOrFor($resource)` faz
`array_merge($policyConfig->methods, $resourcePolicyMethods)` quando `policies.merge` é `true`
(`:179-181`) — e no kit é. Logo `resources.manage[MeuResource::class] = ['reenviar']` **acrescenta**
`reenviar` às 14 chaves daquele Resource, gerando `Reenviar:Convite`. É o mecanismo que dá
**escopo de painel de graça**, porque Resource pertence a um painel.

### `vendor/bezhansalleh/filament-shield/src/FilamentShield.php:114-124`

`getEntitiesPermissions()` faz `merge(collect($this->getCustomPermissions())->keys())`. E
`transformCustomPermissions()` (`HasEntityTransformers.php:88-112`) lê `config('...custom_permissions')`
**sem consultar painel algum**. Consequência: **toda custom permission entra na matriz dos três
painéis**, e portanto em todos os papéis. É a razão do passo 4.

### `vendor/bezhansalleh/filament-shield/src/Traits/HasShieldFormComponents.php:25-36,174-180`

A aba de custom permissions é montada pelo vendor a partir de
`config('filament-shield.shield_resource.tabs.custom_permissions')`. Ligar a flag é suficiente para
RQ-03 — **nada em `app/Filament/Admin/Resources/Roles/**` precisa ser tocado**, o que preserva o
worktree paralelo.

### `vendor/filament/actions/src/Concerns/CanBeAuthorized.php:34-53,104-128`

`->authorize('X')` guarda `['type'=>'all','abilities'=>['X'],'arguments'=>[...]]` e resolve com
`Gate::check($abilities, $arguments)`. `parseAuthorizationArguments()` (`:80-89`) empurra o record
(ou o model, se não houver record) para a frente dos argumentos.

### `vendor/spatie/laravel-permission/src/PermissionRegistrar.php` → `registerPermissions()`

O `Gate::before` do spatie é `function (Authorizable $user, string $ability, array &$args = [])` e
devolve `$user->checkPermissionTo($ability) ?: null`. **Ignora os argumentos** (só desloca o
primeiro quando é string que não é classe — tratado como guard). Logo `->authorize('Reenviar:Convite')`
funciona com o nome da permission direto: se o usuário tem, o `before` devolve `true`; se não tem,
devolve `null`, o Laravel não acha método de policy chamado `Reenviar:Convite` e o
`Gate::check` resulta **false**. Fail-closed.

### `database/seeders/PapeisSeeder.php`

`permissoesDoPainel($painel, $guard)` (`:223-229`) intersecta `Paineis::permissoes($painel)` com o
que existe no banco. É o ponto único por onde toda permissão passa antes de chegar a um papel — e
portanto o lugar certo para o recorte de custom permission do passo 4.

## Autorização

- **Policies**: nenhuma nova, nenhuma alterada. As 6 permissões novas são checadas **pelo nome**
  (`->authorize('Reenviar:Convite')`), no mesmo estilo em que as 14 policies do kit escrevem
  `can('ViewAny:Convite')`. Evita hand-editar policy que o `shield:generate` não reescreve
  (o `ShieldPermissionsSeeder` passa `--ignore-existing-policies`, `:82`).
- **Gates**: nenhum novo. `dashboardAiTasks` passa a consultar o gate `ver-ai-tasks` que já existe
  (`KitServiceProvider.php:110`), o mesmo do `NavigationItem` irmão e do destino.
- **Middleware / Guards**: sem mudança.

### Permissões novas (6)

| Permissão | Mecanismo | Painel(éis) | Papéis que recebem | Superfície |
|---|---|---|---|---|
| `Reenviar:Convite` | `resources.manage[Admin\ConviteResource] += ['reenviar']` | `admin` | `admin` | `ConvitesTable` → `reenviar` |
| `VincularUsuario:Tenant` | `resources.manage[TenantResource] += ['vincularUsuario']` | `admin` | `admin` | `UsersRelationManager` → `AttachAction` |
| `DesvincularUsuario:Tenant` | idem `+= ['desvincularUsuario']` | `admin` | `admin` | `UsersRelationManager` → `DetachAction` |
| `AtribuirPapeis:Tenant` | idem `+= ['atribuirPapeis']` | `admin` | `admin` | `UsersRelationManager` → `papeisNaOrganizacao` |
| `Aceitar:Convite` | `custom_permissions` | `app` (declarado no `PapeisSeeder`) | `admin_app`, `panel_user` | `ConvitesRecebidos` → `aceitar` |
| `Recusar:Convite` | `custom_permissions` | `app` (declarado) | `admin_app`, `panel_user` | `ConvitesRecebidos` → `recusar` |

`master_global` recebe as 6 pelo `Gate::before`, sem linha no banco — como todas as outras.

**Por que dois mecanismos e não um** — ver ADR-02. Em uma frase: `resources.manage` dá escopo de
painel de graça, mas as permissões dele são subtraídas do `panel_user` em bloco por FQCN
(`permissoesDeAdministracaoDoApp()`), e `Aceitar:Convite` **precisa** ficar com o `panel_user`.

## Rotas

Nenhuma rota nova. As existentes passam a responder `403` para quem não tem a permissão — o Filament
faz `abort_unless(static::canAccess(), 403)` em
`vendor/filament/filament/src/Pages/Concerns/CanAuthorizeAccess.php:9,14`. A rota continua
**registrada** (não 404), como já documentado em ADR-02 da wiki `hub-de-cards-opcional`.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `HubDeInfraestrutura` | Filament Page | `/infra/hub-de-infraestrutura` | abre a grade de destinos | Não |
| `Pulse` | Filament Page | `/infra/pulse` | lê os painéis do Pulse | Sim (gráficos) |
| `HubDeAdministracao` | Filament Page | `/admin/hub-de-administracao` | abre a grade | Não |
| `HubDoNegocio` | Filament Page | `/app{/org}/hub-do-negocio` | abre a grade | Não |
| `ConvitesRecebidos` | Filament Page + tabela | `/app{/org}/convites-recebidos` | aceita/recusa convite | Não |
| Dashboard `/admin` | 7 Widgets | `/admin` | lê os cards | Sim (Apex) |
| Dashboard `/infra` | 16 Widgets | `/infra` | lê os cards | Sim (Apex) |
| `ConvitesTable` | tabela de Resource | `/admin/convites` | clica em Reenviar | Não |
| `UsersRelationManager` | RelationManager | `/admin/tenants/{id}` | vincula / desvincula / atribui papéis | Não |
| `ListAiRuns` header | Action de URL | `/infra/ai-runs` | clica no link do dashboard de IA | Não |

**Gate de CT-B**: a tabela é o gatilho, não o critério. Autorização na tela, visibilidade de Action,
403 e ausência de item de menu são **teste de componente / HTTP** e ficam no `04`. O que só o
navegador prova aqui: que o dashboard dos dois painéis **renderiza sem erro de JS** com um
subconjunto de widgets ocultos (o `columnSpan` da grade e os gráficos Apex são montados por JS, e
widget ausente já derrubou grade antes). Isso vai para o `05`.

**Gate de tela de escrita**: nenhuma rota `create`/`edit` nova. As Actions de escrita tocadas
(`reenviar`, attach/detach, `papeisNaOrganizacao`, `aceitar`, `recusar`) ganham cenário de
**execução por componente** no `04` — não só de visibilidade.

## Variáveis de Ambiente

Nenhuma nova. `KIT_HUB` continua sendo o kill-switch dos dois hubs opcionais, **ortogonal** à
permissão: com a flag desligada a Page não abre nem para quem tem `View:HubDeAdministracao`; com a
flag ligada, ainda precisa da permissão. Ver ADR-03.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **`tests/Kit/HubDeCardsTest.php`** — o cenário `'infra com a flag desligada'` (`:110`) usa papel
  `infra`, que tem `View:HubDeInfraestrutura` → segue verde. O aviso do docblock de
  `HubDeInfraestrutura.php:52-54` proíbe `canAccess()` **com a flag**; permissão é outra coisa.
  Confirmado por leitura antes de implementar. Se ficar vermelho, o teste está certo e o plano
  está errado.
- **Hubs em cartões** — `DescobreCardsDoPainel` filtra por `canAccess()` de cada destino. Com as
  Pages passando a consultar permissão, cartão de destino sem permissão **desaparece** — efeito
  desejado, e é o que CT-08 de `HubDeCardsTest` mede com `master_global` (que passa por tudo).
- **Dashboards** — `/admin` e `/infra` passam a renderizar com menos widgets para papel sem a
  permissão. Como todos os papéis de painel recebem a matriz **inteira** daquele painel, o efeito
  no default do kit é **zero**: nada muda para `admin`, `infra`, `admin_app`, `panel_user` e
  `master_global`. A mudança só aparece quando alguém desmarca um checkbox — que é o requisito.
- **`tests/Kit/PaginasInfraTest.php`** e a suíte de browser (52 telas) — visitam com papel de
  painel, que tem a permissão. Devem seguir verdes; qualquer vermelho é sinal de que o papel não
  recebeu a permissão, ou seja, defeito de seeder.
- **`Paineis::permissoes('app')`** cresce de 59 para 61 (as duas custom). O número está citado em
  `.ai/rules/filament.md`; a rule manda **recontar** em vez de confiar, e o passo 8 atualiza a
  linha.

## Rollback

Sem migration, sem dado migrado. Reverter é `git revert` do range + `db:seed` dos dois seeders
(idempotentes). As 6 permissões novas ficariam órfãs na tabela `permissions` até um
`PapeisSeeder` novo — inócuas, porque nada as consulta depois do revert.

## Dependências

Nenhuma nova, composer ou npm.

## Riscos

| Risco | Mitigação |
|---|---|
| Trancar papel legítimo fora de uma tela | Testar **em par** por papel: quem tem entra, quem não tem toma 403. `master_global` **não** vale como prova de permissão (passa pelo `Gate::before`) — usar papel de painel |
| Permissão nova sem entrada no papel = tela que ninguém abre | As 4 de `resources.manage` entram automaticamente (o `PapeisSeeder` colhe a matriz do painel). As 2 custom entram pelo mapa do passo 4, com **teste de arquitetura** que reprova custom permission sem painel declarado |
| `use HasWidgetShield` ignorado em silêncio nos 18 widgets com `canView()` | Concern do passo 2 + teste que reprova widget de painel sem o concern |
| Custom permission vazando para painel errado | Passo 4: `permissoesDoPainel()` rejeita custom permission cujo painel declarado não é o corrente. **Fail-closed** (sem declaração, ninguém recebe) |
| Fail-open da trait quando o painel corrente não é o da Page | Documentado em ADR-01. Em request real o `SetUpPanel` sempre fixa o painel; em teste de componente o kit já usa `Filament::setCurrentPanel()` / `noPainelBootado()` |
| Conflito de merge com `feat/perfis-e-permissoes` | Não tocar em `app/Filament/Admin/Resources/Roles/**`. O único arquivo compartilhado provável é `config/filament-shield.php` (uma linha em `tabs`) |

## Channel de Log da Feature

### Verificação de Channel Existente

`config/logging.php` já tem `ai` (`:114`), `tenancy` (`:123`) e `autenticacao` (`:132`), além dos
padrões. `grep -rn "Log::channel(" app/` mostra o kit usando `autenticacao` para eventos de acesso
(`UsersRelationManager:112`, `ConvitesTable:88`) e `tenancy` para cruzamento de organização.

### Decisão: **nenhum channel novo, e nenhum log novo**

Justificativa, porque ausência de log precisa ser decisão escrita e não esquecimento:

1. **`canView()` e `canAccess()` rodam em loop de render.** O dashboard do `/infra` chama
   `canView()` 16 vezes por carregamento, e `canAccess()` é consultado por cada cartão de hub, por
   cada item de navegação e por cada categoria do Spotlight. Logar negativa aí produz dezenas de
   linhas por request — ruído que **esconde** o evento real em vez de registrá-lo.
2. **A negativa de Action já é observável sem log**: a Action simplesmente não aparece (o vendor
   esconde por default, `CanBeAuthorized.php:239-250`), e o `403` de Page fica no log de acesso do
   servidor.
3. **O evento que interessa auditar já é logado**: `papeisNaOrganizacao` registra sucesso no channel
   `autenticacao` (`UsersRelationManager.php:112-121`), attach/detach registram em `:registrar()`, e
   `reenviar`/revogar registram em `ConvitesTable.php:88`. Esta entrega não muda nenhum deles.

Se um projeto quiser trilha de negativa, o lugar é um listener do
`Illuminate\Auth\Access\Events\GateEvaluated` — fora do escopo, registrado como proposta no `03`.

## Estrutura de Implementação

### 0. Varredura (feita antes do plano)

> Skills: —

`grep -rn "Action::make(" app/ resources/views/` + inventário de `find app/Filament -path '*Pages*'`
e `'*Widgets*'` + `Paineis::permissoes()` por painel no tinker. Resultado em `## Contexto`.
Nada a executar; o passo existe para o `feature-quality-gate` poder rastrear RQ-01.

### 1. Concern de Page + aplicar nas 5 Pages

> Skills: `laravel-best-practices`, `ponytail`

- **Path novo**: `app/Filament/Concerns/ExigePermissaoDaTela.php`
- **Assinatura**:
  ```php
  trait ExigePermissaoDaTela
  {
      use HasPageShield {
          canAccess as protected permitidaPelaPermissao;
      }

      public static function canAccess(): bool
      {
          return static::permitidaPelaPermissao() && static::regraLocalDeAcesso();
      }

      protected static function regraLocalDeAcesso(): bool
      {
          return true;
      }
  }
  ```
  O alias é o ponto do arquivo: método definido na classe (ou no trait de nível mais alto) vence
  método de trait usado por ele, **sem erro nenhum**. Sem o alias, uma Page que já tem `canAccess()`
  simplesmente ignoraria a permissão. Com o alias, quem tem regra local sobrescreve
  `regraLocalDeAcesso()` e a permissão continua sendo checada.
- **Aplicar**:
  | Page | Mudança |
  |---|---|
  | `app/Filament/Infra/Pages/Pulse.php` | `use ExigePermissaoDaTela;` |
  | `app/Filament/Infra/Pages/HubDeInfraestrutura.php` | `use ExigePermissaoDaTela;` (e atualizar o docblock `:52-54`: o que continua proibido é `canAccess()` **com a flag**) |
  | `app/Filament/Admin/Pages/HubDeAdministracao.php` | `use ExigePermissaoDaTela;` + `canAccess()` → `regraLocalDeAcesso()` |
  | `app/Filament/App/Pages/HubDoNegocio.php` | idem |
  | `app/Filament/App/Pages/ConvitesRecebidos.php` | idem |
- **Cuidado com `DescobreCardsDoPainel`**: `HubDeInfraestrutura` e os outros dois usam os dois
  traits. Nenhum define `canAccess()`, então não há conflito.
- **Logs**: nenhum — ver `## Channel de Log`.

### 2. Concern de Widget + aplicar nos 23 Widgets

> Skills: `laravel-best-practices`, `ponytail`

- **Path novo**: `app/Filament/Concerns/ExigePermissaoDoWidget.php`
- **Assinatura**: mesma forma, com `HasWidgetShield { canView as protected visivelPelaPermissao; }`,
  `canView()` public e hook `protected static function fonteDeDadosDisponivel(): bool { return true; }`
- **Aplicar**:
  - **5 sem `canView()`** — só `use ExigePermissaoDoWidget;`:
    `Admin/Widgets/AgentesIaStats`, `AgentesIaPorProvider`, `UsuariosPorPapel`,
    `UsuariosVisaoGeralStats`, `UltimosUsuariosCadastrados`
  - **18 com `canView()`** — `use ExigePermissaoDoWidget;` + renomear `public static function canView()`
    para `protected static function fonteDeDadosDisponivel()`, corpo inalterado:
    `Admin/Widgets/ConvitesPorSituacao`, `ProgressoOnboarding`; `Infra/Widgets/AuditoriaPorEvento`,
    `AuditoriaRecente`, `AutenticacaoStats`, `FilasPorFila`, `FilasStats`, `FilasTaxaDeSucesso`,
    `IaCustoPorTask`, `IaExecucoesPorDia`, `IaExecucoesPorStatus`, `IaStats`, `IaTokensPorDriver`,
    `PacotesComposer`, `SaudeAplicacaoPorStatus`, `SaudeAplicacaoStats`, `UltimoBackup`,
    `UltimosAcessos`
- **Verificado**: todos os 23 herdam `canView()` estático de `Filament\Widgets\Widget:34-37`
  (as bases `StatsOverviewWidget`, `ApexChartWidget` e as 6 de `filament-dashboard-widgets` não o
  declaram), então `parent::canView()` dentro da trait do Shield sempre resolve.
- **Logs**: nenhum.

### 3. Criar as 6 permissões e deixá-las selecionáveis

> Skills: —

- **Path**: `config/filament-shield.php`
- `shield_resource.tabs.custom_permissions`: `false` → `true` (RQ-03 para as duas custom; as 4 de
  `resources.manage` já aparecem na aba de Resources, que está ligada)
- `resources.manage`: acrescentar
  ```php
  App\Filament\Admin\Resources\Convites\ConviteResource::class => ['reenviar'],
  App\Filament\Admin\Resources\Tenants\TenantResource::class   => [
      'vincularUsuario', 'desvincularUsuario', 'atribuirPapeis',
  ],
  ```
  Com `policies.merge => true` isto **soma** às 14 chaves default daquele Resource
  (`HasEntityTransformers.php:179-181`). **Nenhuma das três entra em
  `policies.single_parameter_methods`**: as três recebem registro.
- `custom_permissions`: `['aceitar:convite' => 'Aceitar convite recebido', 'recusar:convite' => 'Recusar convite recebido']`
  — `format_custom_permission_keys` é `true` e `case` é `pascal`, então o Shield formata cada
  segmento e grava `Aceitar:Convite` / `Recusar:Convite`
  (`HasEntityTransformers.php:155-164`). Escrever a chave já em pascal também funcionaria; em
  minúsculo é mais legível como *declaração* e o resultado é o mesmo.
- **Nota deixada no arquivo**: a entrada existente `RoleResource::class => [...]` aponta para o
  `RoleResource` **do vendor**, não para o publicado do kit, e com `merge => true` seria no-op de
  qualquer forma. Não mexer nela nesta entrega (é tela da feature paralela) — apenas registrar em
  `03-progresso.md` → Notas.

### 4. Recorte de painel das custom permissions no `PapeisSeeder`

> Skills: `laravel-best-practices`

- **Path**: `database/seeders/PapeisSeeder.php`
- Acrescentar
  ```php
  /** @return array<string, list<string>> permissão custom => painéis a que ela pertence */
  private function paineisDasPermissoesCustomizadas(): array
  {
      return [
          'Aceitar:Convite' => ['app'],
          'Recusar:Convite' => ['app'],
      ];
  }
  ```
- Em `permissoesDoPainel()`, rejeitar custom permission que não declare este painel. **Fail-closed**:
  chave custom sem entrada no mapa não vai para papel nenhum.
- **Por quê**: `transformCustomPermissions()` não consulta painel
  (`HasEntityTransformers.php:88-112`), então sem este recorte as duas chaves entrariam nos papéis
  `admin` e `infra` também. Hoje seria inócuo (a Page é do `/app`), mas a próxima custom permission
  — uma de `admin` — cairia **no `panel_user`**, que é o over-grant silencioso que
  `.ai/rules/filament.md` chama de "a falha mais cara desta parte do kit".
- **Logs**: nenhum (seeder).

### 5. Aplicar a permissão nas 6 Actions + 1 link

> Skills: `laravel-best-practices`, `ponytail`

| Path | Mudança |
|---|---|
| `app/Filament/Admin/Resources/Convites/Tables/ConvitesTable.php:71` | `->authorize('Reenviar:Convite')` na Action `reenviar` |
| `app/Filament/Admin/Resources/Tenants/RelationManagers/UsersRelationManager.php:58` | `->authorize('VincularUsuario:Tenant')` no `AttachAction` |
| `.../UsersRelationManager.php:66` | `->authorize('DesvincularUsuario:Tenant')` no `DetachAction` |
| `.../UsersRelationManager.php:89` | `->authorize('AtribuirPapeis:Tenant')` em `papeisNaOrganizacao` |
| `app/Filament/App/Pages/ConvitesRecebidos.php:79` | `->authorize('Aceitar:Convite')` |
| `app/Filament/App/Pages/ConvitesRecebidos.php:94` | `->authorize('Recusar:Convite')` |
| `app/Filament/Infra/Resources/AiRuns/Pages/ListAiRuns.php:25` | `->visible(fn (): bool => Gate::allows('ver-ai-tasks'))` — **gate existente**, o mesmo do destino e do `NavigationItem` irmão. Não é permissão nova: o furo é de affordance |

`->authorize()` com o nome da permission, e não com nome de método de policy, por três razões:
é o estilo que as 14 policies do kit já usam (`can('ViewAny:Convite')`); dispensa hand-editar
policy que o `ShieldPermissionsSeeder` não reescreve (`--ignore-existing-policies`, `:82`); e é
fail-closed pelo caminho do `Gate::before` do spatie descrito em `## Análise`.

- **Logs**: nenhum novo. Os logs de sucesso existentes (`autenticacao`) ficam intactos.

### 6. Ressemear e conferir a matriz

> Skills: —

```bash
php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
```

Conferir por query (não por leitura do seeder) que cada uma das 6 chegou ao papel certo e **não**
chegou aos outros. É a conferência que o `04` automatiza.

### 7. Testes

> Skills: `pest-testing`, `feature-test-design`

Ver `04-casos-de-teste.md` e `05-casos-de-teste-browser.md`.

### 8. Documentação

> Skills: —

- `README.md` e `README.en.md`: a tabela de papéis (`README.md:430-436`) e a seção de permissão de
  import/export ganham a família nova — "toda Page, Widget e Action do kit tem permissão própria".
- `.ai/rules/filament.md`: recontar `Paineis::permissoes('app')->count()` (59 → 61) na §4, como a
  própria rule manda.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`.** A escada aplicada aqui, explicitamente:
> - **Rung 1 (precisa existir?)**: nenhuma permissão nova para Page e Widget — elas **já existem**
>   no banco e nos papéis. A entrega liga a consulta, não cria matriz.
> - **Rung 2 (já existe no projeto?)**: `dashboardAiTasks` reusa o gate `ver-ai-tasks`; as Actions
>   reusam o padrão `->authorize()` de import/export.
> - **Rung 5 (dependência instalada resolve?)**: `HasPageShield`/`HasWidgetShield` do Shield, em
>   vez de reimplementar a resolução da chave (que dependeria de `permissions.case`,
>   `separator`, `pages.prefix` e `widgets.prefix` — quatro chaves de config que dessincronizam).
> - **Rung 7**: os dois concerns têm 12 linhas cada e existem só porque método de classe vence
>   método de trait em silêncio.
>
> Atalhos deliberados marcados com `ponytail:`.
> Após implementar, `/ponytail:ponytail-review` no diff.

## Testes

> `04-casos-de-teste.md` (backend) e `05-casos-de-teste-browser.md` (o que só o navegador prova).

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy`
- [ ] `vendor/bin/phpstan analyse --no-progress`
- [ ] `composer test:browser`

## Commits

- `:lock: feat(permissoes): Page do painel consulta a permissao dela`
- `:lock: feat(permissoes): Widget do painel consulta a permissao dele`
- `:lock: feat(permissoes): Action customizada nasce com permissao propria`
- `:sparkles: feat(permissoes): matriz recorta custom permission por painel`
- `:white_check_mark: test(permissoes): par tem/nao-tem por papel nas tres familias`
- `:memo: docs(permissoes): wiki, README e recontagem da rule`

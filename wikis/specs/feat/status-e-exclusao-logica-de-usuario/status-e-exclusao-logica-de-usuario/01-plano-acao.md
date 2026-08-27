# Plano de Ação — Status de ativo/inativo e exclusão lógica de usuário

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: — (mas evolui o que `registro-e-aprovacao` deixou em `User::canAccessPanel()`
  e o que `travas-de-exclusao-e-upload-anonimo` decidiu sobre exclusão de usuário)
- **Motivo**: o usuário ganha um estado (`ativo`) e a exclusão passa a ser lógica (`deleted_at`)
- **Toca infra compartilhada?**: **sim** — `App\Models\User` (guard de autenticação dos três
  painéis), `config/filament-shield.php` (matriz de permissões), `UserPolicy`, a trait
  `AprovacaoDeCadastro` (dois resources), o `InfraPanelProvider` (Lixeira) e a tela de login dos
  três painéis. **Regressão obrigatória** contra: `ExclusaoDeUsuarioTest`, `TravaDeExclusaoTest`,
  `LoginSocialGoogleTest`, `LoginSocialProvedoresTest`, `VinculoDeProvedorSocialTest`,
  `ConviteTest`, `ConviteUsuarioExistenteTest`, `RegistroAbertoTest`, `PermissoesDeAcoesTest`,
  `PermissoesDeResourcesTest`, `TelasDeAutenticacaoTest`, `BloqueioDeSessaoTest`,
  `ExibicaoDePapeisTest`, `AdminDaOrganizacaoTest`.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | estado ativo/inativo consultado no login | 1, 2 | coluna `users.ativo` + primeira guarda de `canAccessPanel()` |
| RQ-02 | inativo com senha certa não abre sessão | 2, 4 | `canAccessPanel()` nega; o Filament não chama `attemptWhen` e dispara `Failed` |
| RQ-03 | inativo cai em tela de aviso própria | 3, 4 | view no layout do Sentinel + interceptor em `TelaLogin::authenticate()` |
| RQ-04 | o aviso diz "desativada, contate o administrador" | 3 | texto fixo na view |
| RQ-05 | tentativa de inativo registrada | 2, 4 | `Log::channel('autenticacao')` em `canAccessPanel()` e em `TelaLogin`; linha `login_successful=false` em `authentication_log` (evento `Failed` do próprio Filament) |
| RQ-06 | idem para login social | 5 | `recusarSeIndisponivel()` nos dois ramos de `retorno()` e em `confirmarVinculo()` |
| RQ-07 | desativar/reativar como alternativa a excluir | 2, 6, 7 | `User::desativar()/reativar()`, ações no `/admin`, permissões `Desativar:User`/`Reativar:User` |
| RQ-08 | exclusão lógica | 1, 2 | `SoftDeletes` + `Recyclable` em `User` |
| RQ-09 | tentativa de excluído registrada | 4, 5 | `motivo: conta_excluida` no channel; evento `Failed` disparado à mão (o Filament não acha o usuário) |
| RQ-10 | aviso do excluído com a data | 3, 4, 5 | `deleted_at` formatado `d/m/Y` na view; só com senha certa (ADR-03) |
| RQ-11 | restaurar da lixeira no `/admin` ou `/infra` | 8, 9 | Lixeira do `/infra` ganha `User::class`; `/admin/users` ganha `TrashedFilter` + `RestoreAction` |
| RQ-12 | README | 11 | seção nova em PT e EN + Lixeira + roteiro |
| RQ-13 | sub-agente e worktree | — | cumprido pelo coordenador; sem passo |

## Objetivo

Dar ao usuário do kit um estado **ativo/inativo** e trocar a exclusão física pela **lógica**, de
modo que quem administra tenha uma alternativa reversível ao "excluir" e que a pessoa barrada
saiba **por quê** — em vez do erro genérico de credenciais que o Filament devolve hoje para toda
negativa de `canAccessPanel()`.

A negação mora num lugar só (`User::canAccessPanel()`, primeira instrução), como já acontece com o
cadastro pendente. O que é novo é a **explicação**: a tela de login e o login social, ao verem a
negativa, mandam a pessoa para uma página no visual do Sentinel dizendo se a conta está desativada
ou quando foi excluída, e pedindo contato com o administrador. Tudo fica registrado no channel
`autenticacao` e na trilha de acessos do `/infra`.

## Contexto

Hoje excluir usuário no `/admin` apaga a linha (com cascata em `tenant_user`, `model_has_roles`,
`vinculos_sociais` e nos dashboards pessoais). Não há como desfazer, e não há meio-termo: ou a
pessoa entra, ou some. A única "desativação" possível é tirar o papel — o que deixa a pessoa
autenticando e levando 403, sem saber o motivo.

Descoberta da pesquisa (step 3) que muda o escopo: a Lixeira (`promethys/revive`) só lista models
que usam a trait `Promethys\Revive\Concerns\Recyclable` — é ela que grava a linha em
`recycle_bin_items` no evento `deleted` (`vendor/promethys/revive/src/Concerns/Recyclable.php:29-45`).
`App\Models\Projeto` tem `SoftDeletes` e **não** tem a trait: a Lixeira do kit está vazia por
construção desde a 0.17.0. Como RQ-11 depende da Lixeira funcionar, a dívida é paga aqui
(ADR-06).

## Análise dos Arquivos Existentes

### `app/Models/User.php`

- `canAccessPanel()` já nega `aprovacao_pendente` como primeira instrução, com log e `motivo`. A
  guarda de `ativo`/`deleted_at` entra **antes** dela (ADR-01).
- `casts()` declara `aprovacao_pendente` fora do `$fillable` — `ativo` segue o mesmo regime.
- `aprovar()` é o molde de método de transição no model: idempotente, `forceFill`, log com
  `alvo_id`/`executor_id`/e-mail mascarado.
- Traits: nenhuma declara `booted()`, então `Recyclable::booted()` não colide (verificado por
  grep em `TwoFactorAuthenticatable`, `AuthenticationLoggable`, `HasRoles`, `Auditable`,
  `app/Traits/*`).

### `app/Filament/Pages/Auth/TelaLogin.php`

- Estende `Caresome\FilamentAuthDesigner\Pages\Auth\Login`, que estende
  `Filament\Auth\Pages\Login`. Só o `/app` a usa hoje (`AppPanelProvider.php:212`,
  `->usingPage(TelaLogin::class)`); `/admin` e `/infra` usam a do Auth Designer crua.
- `Filament\Auth\Pages\Login::authenticate()` (`vendor/filament/filament/src/Auth/Pages/Login.php:73-170`):
  rate limit → `retrieveByCredentials` (respeita o escopo do `SoftDeletes`, logo **não acha**
  excluído) → `validateCredentials` → `isUserAllowedToAccessPanel()` (= `canAccessPanel()`) →
  em qualquer falha, `fireFailedEvent()` + `throwFailureValidationException()`, tudo dentro de
  um `Timebox`. O evento `Failed` leva o usuário quando ele foi encontrado — é o que alimenta a
  trilha do `rappasoft/laravel-authentication-log` (`FailedLoginListener.php:24-49`).
- `throwFailureValidationException()` é `never` e roda dentro do `Timebox`: não serve para
  redirecionar. O ponto de interceptação é `authenticate()` com `try/catch ValidationException`
  (ADR-02).

### `app/Http/Controllers/Auth/LoginSocialController.php`

- `retorno()` resolve `$user` por dois ramos: vínculo (`VinculoSocial::de()` → `$vinculo->user`,
  relação `belongsTo` que **respeita o escopo** e devolve `null` para excluído) ou e-mail
  (`contaCom()`, `User::query()` → também não acha excluído). Depois checa
  `aprovacao_pendente` e faz `Auth::login()`.
- `confirmarVinculo()` faz `User::query()->find()` e `Auth::login()`.
- `recusar()` volta ao login com `Notification` — não serve para RQ-03/RQ-10, que pedem a tela.

### `app/Filament/Concerns/AprovacaoDeCadastro.php`

- `colunaDeSituacao()` mostra Pendente/Ativo a partir de `aprovacao_pendente`. Passa a ter três
  estados e muda de trait (passo 6).
- `acaoDeAprovar()` é o molde das ações novas: `->authorize()` por policy, `->visible()` por
  estado, `requiresConfirmation()`, corpo chamando o método do model.

### `app/Filament/Admin/Resources/Users/UserResource.php` e `app/Filament/App/Resources/Users/UserResource.php`

- Ambos usam `AprovacaoDeCadastro` e `BadgeContagemNavegacao` (conta por `getEloquentQuery()`,
  então **não** se remove o `SoftDeletingScope` da query — o `TrashedFilter` já o remove sozinho
  quando o operador escolhe "com excluídos").
- O do `/app` nega exclusão em `getDeleteAuthorizationResponse()` (ADR-01 da wiki
  `travas-de-exclusao`). Desativar segue a mesma régua: não há ação no `/app` (ADR-04).

### `app/Providers/Filament/InfraPanelProvider.php`

- `RevivePlugin::make()->models([Projeto::class])->withoutScoping()` (linhas 530-548). O
  comentário recusa `User` por "consequência de autorização". Com exclusão lógica as pivots
  (`tenant_user`, `model_has_roles`) **não** são apagadas — restaurar devolve exatamente o que
  havia. O comentário é reescrito (ADR-06).

### `config/filament-shield.php` e `database/seeders/PapeisSeeder.php`

- `resources.manage[FQCN] = ['acao']` gera `Acao:Model` só na matriz do painel do resource. As
  duas permissões novas entram para o `UserResource` do `/admin`. O `PapeisSeeder` não muda: o
  `admin` recebe a matriz inteira do painel; `panel_user` já subtrai `UserResource` do `/app` por
  FQCN, e o resource do `/app` não ganha as ações.
- `Restore:User` já existe (policy `restore()` gerada pelo Shield) — a `RestoreAction` nativa
  a consulta por `getRestoreAuthorizationResponse()`.

### `app/Policies/UserPolicy.php`

- Gerada pelo Shield, não é reescrita (`--ignore-existing-policies`). Ganha `desativar()` e
  `reativar()` à mão, no mesmo formato dos irmãos.

### `resources/views/errors/403.blade.php` e `sentinel-layout.blade.php`

- O layout é autossuficiente (`@extends('errors.sentinel-layout', [code, tone, title, body])` +
  `@section('icon')` + `@section('content')`). A view nova o reaproveita; o 403 do Sentinel só
  exibe `Motivo` fora de produção (`$mostrarDiagnostico`), por isso não serve cru (ADR-02).

### `tests/Kit/PermissoesDeAcoesTest.php`

- O inventário `inventarioDeAutorizacao()` fica **vermelho** com qualquer `Action::make('x')` nova
  em `app/Filament`. As duas ações entram lá com mecanismo `permissao`.

### `tests/Kit/VinculoDeProvedorSocialTest.php` (CT-V07)

- "apaga os vínculos junto com a conta" faz `$user->delete()` e espera a cascata da FK. Com
  exclusão lógica a linha de `users` continua e a FK não dispara — e **é o comportamento certo**
  (restaurar devolve a identidade no provedor). O caso passa a usar `forceDelete()`, que continua
  provando a cascata. Registrado em "Desvios" do `03`.

## Autorização

- **Policies**: `UserPolicy::desativar()` → `Desativar:User`; `UserPolicy::reativar()` →
  `Reativar:User`. `restore()`/`restoreAny()` já existem.
- **Gates**: nenhum novo. `master_global` vence por `Gate::before`.
- **Middleware**: a rota do aviso é pública (grupo `web`), sem `auth` — quem a vê está
  justamente sem sessão. Só renderiza com o aviso na sessão; sem ele, redireciona para `/`.
- **Guards**: `web` (o único do kit).

## Rotas

| Método | URI | Name | Middleware |
|--------|-----|------|------------|
| GET | `/conta-indisponivel` | `auth.conta-indisponivel` | `web` (grupo default de `routes/web.php`) |

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Tela de login dos três painéis (`TelaLogin`) | Filament (Livewire) | `/app/login`, `/admin/login`, `/infra/login` | digita e-mail e senha; se a conta está inativa/excluída é levado ao aviso | Não (redirect do Livewire) |
| Aviso de conta indisponível | Blade (layout Sentinel) | `/conta-indisponivel` | lê o aviso; botão "Voltar ao login" | Não |
| Lista de usuários do `/admin` | Filament Resource | `/admin/users` | coluna Situação (Pendente/Inativo/Ativo), filtros "Somente inativos" e "Lixeira", ações Desativar/Reativar/Restaurar | Não |
| Lista de usuários do `/app` | Filament Resource | `/app/{org}/users` | coluna Situação e filtro "Somente inativos" (sem ação) | Não |
| Lixeira do `/infra` | Page de pacote (Revive) | `/infra/recycle-bin` | vê usuários excluídos e restaura | Não |

**Gate de CT-B**: nenhum cenário afirma sobre JS, console, tema ou layout — tudo é teste de
componente Livewire ou de HTTP. **Sem `05-casos-de-teste-browser.md`**; o motivo fica registrado
no `04`.

**Gate de tela de escrita**: as telas `edit`/`create` de usuário não mudam (nenhum campo novo no
formulário — `ativo` é ação, não campo). A gravação por componente das ações Desativar/Reativar
e Restaurar é exigida no `04`.

## Variáveis de Ambiente

Nenhuma. Status é dado do usuário, não configuração da instalação (fora de escopo declarado no
`00`).

## Eventos / Listeners / Observers

- **Eventos emitidos**: `Illuminate\Auth\Events\Failed` — pelo próprio Filament quando
  `canAccessPanel()` nega (inativo), e **à mão** por `TelaLogin` e `LoginSocialController` quando
  a conta é excluída/inativa e o Filament não a encontrou (o listener do `authentication-log` só
  grava quando o evento leva o usuário — `FailedLoginListener.php:26`).
- **Listeners**: nenhum novo. `FailedLoginListener` (vendor) grava a linha
  `login_successful=false`.
- **Observers**: `Recyclable::booted()` (vendor) pendura `deleted`/`restored`/`forceDeleted` no
  `User` e no `Projeto` para manter `recycle_bin_items`. O `User::deleting` do
  `filament-dynamic-dashboard` continua disparando **na exclusão lógica** e apaga os dashboards
  pessoais — restaurar não os devolve (ADR-07).

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **Login por senha nos três painéis**: `/admin` e `/infra` passam a usar `TelaLogin` (hoje só o
  `/app`). O `getSubheading()` dela devolve `null` quando o registro está fechado, e o
  `parent::getSubheading()` já devolve `null` em painel sem registro — nada muda visualmente.
- **`ExclusaoDeUsuarioTest`**: `$user->delete()` vira soft delete. `User::query()->find($id)`
  continua `null` (escopo); o listener de dashboards continua apagando. Fica verde sem edição.
- **`VinculoDeProvedorSocialTest` CT-V07**: passa a `forceDelete()` (ver análise).
- **E-mail reservado**: `users.email` é único e a linha excluída continua lá. `->unique()` do
  Filament (formulário de usuário, registro aberto, aceite de convite) inclui soft-deleted por
  default (doc do Laravel 13, `Rule::unique()->withoutTrashed()` é opt-in) — o e-mail de conta na
  lixeira é recusado como "já em uso". `KitAdmin::emailEmUso()` e `UsuarioAdminSeeder::firstOrCreate`
  usam `User::where()` (sem trashed): se o e-mail do admin estiver na lixeira, o `firstOrCreate`
  estoura na unique. Cenário de instalação, não de operação; documentado no README (ADR-05).
- **Widgets do `/admin`** (`UltimosUsuariosCadastrados`, `UsuariosPorPapel`,
  `UsuariosVisaoGeralStats`): `User::query()` exclui trashed — contam só quem existe. Inativos
  continuam contando (estão cadastrados).
- **`AuditoriaRecente`, `RoleResource::usuarios`, `UsersRelationManager`**: mesma regra — excluído
  some, inativo aparece.
- **Impersonate**: `canBeImpersonated()` não muda; impersonar inativo abre sessão que o middleware
  do painel derruba com 403 no request seguinte. Aceito.
- **Sessão aberta de quem é desativado/excluído**: `Filament\Http\Middleware\Authenticate`
  consulta `canAccessPanel()` a cada request (`Authenticate.php:35-39`) → 403 do Sentinel para o
  inativo; para o excluído, `EloquentUserProvider::retrieveById()` não o acha e a sessão morre
  (redirect ao login).

## Rollback

- **Migration down**: `dropColumn('ativo')` e `dropSoftDeletes()`. **Atenção**: derrubar
  `deleted_at` apaga a informação de quem estava na lixeira — as linhas voltam a ser usuários
  vivos. Reverter com usuários excluídos na base é ressuscitá-los; o `down()` documenta isso.
- **Feature flag**: não há. Reverter é `migrate:rollback` do arquivo + reverter o commit.
- **Reversão de dados**: `recycle_bin_items` do tipo `App\Models\User` ficam órfãos; o pacote os
  ignora quando o `model` não existe (`$record->model?->restore()`).

## Dependências

- **Composer**: nenhuma nova. `promethys/revive ^3.1`, `rappasoft/laravel-authentication-log`
  (via `tapp/filament-authentication-log ^5.0`) e `anselmokossa/filament-sentinel ^1.0` já estão
  instalados.
- **NPM**: nenhuma.

## Riscos

- **Enumeração de contas pelo aviso** (o aviso confirma que existe conta com aquele e-mail):
  mitigado exigindo senha correta (`Hash::check`) antes de mostrar o aviso, dentro de um
  `Timebox` com a mesma duração do Filament (`auth.timebox_duration`) para não abrir oráculo de
  tempo. ADR-03.
- **Duplo evento `Failed`**: o Filament já dispara `Failed` com o usuário quando
  `canAccessPanel()` nega. O interceptor só dispara à mão quando o usuário está **excluído**
  (o Filament não o achou, `$user = null`). Sem essa condição a trilha teria duas linhas por
  tentativa de inativo.
- **`Recyclable::booted()` sobrescreve `booted()`**: nenhuma trait do `User` nem do `Projeto`
  declara `booted()`; verificado. Um `booted()` futuro na classe precisa chamar o da trait.
- **`Log::info()` do vendor no channel default** a cada delete/restore (`Recyclable.php:44,54,63`).
  Ruído aceito; `LOG_CHANNEL=null` no `phpunit.xml` já o silencia nos testes.
- **Trashed no `Select` de `roles`/`tenants`**: os selects do formulário listam papéis e
  organizações, não usuários — sem impacto. O `UsersRelationManager` do `TenantResource` anexa por
  `recordSelect` sobre `User::query()` — excluídos não aparecem, e é o desejado.

## Channel de Log da Feature

### Verificação de Channel Existente

- `config/logging.php:132` — channel `autenticacao` (`daily`, 14 dias, `LOG_KIT_DRIVER`).
- Todo log de acesso do kit já vai para ele (`User::canAccessPanel`, `LoginSocialController`,
  `RegistroAberto`, `Convite`).

### Decisão

**Channel existe**: `Log::channel('autenticacao')` em todos os passos. Nada a criar em
`config/logging.php`.

Padrão de `context` desta feature (toda linha traz): `motivo` ∈ {`conta_inativa`,
`conta_excluida`, `desativacao_recusada`}, `user_id`/`alvo_id`, `email` mascarado
(`Str::mask($email, '*', 3)`), `ip`, `painel` (login por senha) ou `provedor` (social),
`executor_id` (ações de administração), `excluida_em` (quando excluída).

## Estrutura de Implementação

### 1. Migration: `users.ativo` e `users.deleted_at`

> Skills: `laravel-best-practices`

- **Path**: `database/migrations/2026_08_26_200001_add_ativo_and_soft_deletes_to_users_table.php`
  (via `php artisan make:migration add_ativo_and_soft_deletes_to_users_table --table=users --no-interaction`,
  depois renomeada para o prefixo de data fixo, como as irmãs `2026_08_24_000001_*`)
- `up()`: `$table->boolean('ativo')->default(true)->after('aprovacao_pendente');`
  `$table->softDeletes();`
- `down()`: `dropSoftDeletes()` e `dropColumn('ativo')`, com o docblock avisando que reverter
  ressuscita quem estava na lixeira e reativa quem estava inativo.
- Sem índice em `ativo` (baixa cardinalidade — mesmo argumento da migration de
  `aprovacao_pendente`). `softDeletes()` cria `deleted_at` nullable sem índice, que é o default
  do Laravel.
- **Logs**: nenhum (migration).

### 2. `App\Models\User`: estado, exclusão lógica e transições

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Models/User.php`
- `use Illuminate\Database\Eloquent\SoftDeletes;` e `use Promethys\Revive\Concerns\Recyclable;`
  (a segunda com docblock: sem ela a Lixeira não lista — `Recyclable.php:29-45`).
- `casts()`: `'ativo' => 'boolean'` (fora do `$fillable`, com o mesmo comentário de
  `aprovacao_pendente`). `deleted_at` é castado pelo `initializeSoftDeletes()` do vendor.
- **Novo** `scopeComEmail(Builder $query, string $email): Builder` →
  `whereRaw('lower(email) = ?', [mb_strtolower(trim($email))])`. Substitui as três cópias da
  mesma query (`LoginSocialController::contaCom()`, `Convite::usuarioExistente()` e a nova do
  `TelaLogin`). Ponytail: rung 2, reutilizar.
- **Novo** `motivoDeIndisponibilidade(): ?string` — `'conta_excluida'` se `trashed()`,
  `'conta_inativa'` se `! $this->ativo`, senão `null`. Excluído vence inativo (a mensagem da data
  é a mais informativa).
- `canAccessPanel()`: **antes** da guarda de `aprovacao_pendente`:
  ```php
  if (($motivo = $this->motivoDeIndisponibilidade()) !== null) {
      Log::channel('autenticacao')->warning(
          "[User@canAccessPanel] Acesso negado: {$motivo} | user: {$this->id} - painel: {$panel->getId()}",
          ['user_id' => $this->id, 'painel' => $panel->getId(), 'motivo' => $motivo, 'email' => Str::mask((string) $this->email, '*', 3), 'excluida_em' => $this->deleted_at?->toIso8601String()],
      );
      return false;
  }
  ```
- **Novo** `motivoParaNaoDesativar(): ?string` — a **única** fonte da regra de guarda, lida pela
  tela (`->visible()`) e pelo model (`desativar()`): `'propria_conta'` se `$this->is(Auth::user())`,
  `'ultimo_master_global'` se `ehOUltimoMasterGlobalAtivo()`, senão `null`. (Ponytail: uma regra,
  um lugar — a versão anterior do plano tinha `podeSerDesativado()` **e** as guardas repetidas em
  `desativar()`.)
- **Novo** `desativar(): void` — idempotente (`if (! $this->ativo) return;`); se
  `motivoParaNaoDesativar()` não é nulo, loga `warning` `motivo: desativacao_recusada` + `razao` e
  lança `RuntimeException` com mensagem legível; senão `forceFill(['ativo' => false])->save()` e
  log `info` `"[User@desativar] Usuário desativado | alvo: {$this->id}"` com `alvo_id`,
  `executor_id` (`Auth::id()`), `email` mascarado.
- **Novo** `reativar(): void` — idempotente; `forceFill(['ativo' => true])->save()`; log `info`
  `"[User@reativar] Usuário reativado | alvo: {$this->id}"`, mesmo context.
- **Novo** `ehOUltimoMasterGlobalAtivo(): bool` — `$this->isMasterGlobal()` e não existe outro
  `User` ativo com o papel `master_global` no contexto global:
  ```php
  User::query()->where('ativo', true)->whereKeyNot($this->getKey())
      ->whereHas('papeisEmQualquerContexto', function (Builder $q): void {
          $q->where('name', config('filament-shield.super_admin.name', 'master_global'));
          if (config('permission.teams')) {
              $q->where(Config::modelHasRolesTable().'.'.Config::teamForeignKey(), Tenant::CONTEXTO_GLOBAL);
          }
      })->doesntExist()
  ```
- `recycleBinItem()` vem da trait. `getDeletedByUser()` do vendor cai em `Auth::id()` (não há
  `deleted_by` nem `user_id` em `users`), que é o executor — correto.
- **Logs**: os quatro acima. Nada em `motivoDeIndisponibilidade()` (é pergunta, não ação).

### 3. A tela de aviso: view, controller e rota

> Skills: `laravel-best-practices`, `tailwindcss-development` (não se aplica — o layout do Sentinel é CSS próprio)

- **Path**: `resources/views/auth/conta-indisponivel.blade.php`
  - `@extends('errors.sentinel-layout', ['code' => 403, 'tone' => 'warning', 'title' => $titulo, 'body' => $corpo, 'exception' => null])`
  - `$titulo`/`$corpo` resolvidos no `@php` do topo a partir de `$motivo` e `$excluidaEm`:
    - `conta_inativa` → "Conta desativada" / "Sua conta está desativada. Entre em contato com o
      administrador para reativá-la."
    - `conta_excluida` → "Conta excluída" / "Sua conta foi excluída em {dd/mm/aaaa}. Entre em
      contato com o administrador para restaurá-la."
  - `@section('icon')`: o SVG de cadeado do 403 (mesmo ícone, mesma família visual).
  - `@section('content')`: `<a class="sn-btn sn-btn-primary" href="{{ $voltarPara }}">Voltar ao login</a>`
    e a `sn-note` "Quem administra a aplicação pode reativar ou restaurar a sua conta."
  - Regra de `.ai/rules/views.md`: nenhuma diretiva dentro de comentário Blade.
- **Path**: `app/Http/Controllers/Auth/ContaIndisponivelController.php` (`php artisan make:controller Auth/ContaIndisponivelController --invokable --no-interaction`)
  - `public const CHAVE_DA_SESSAO = 'conta_indisponivel';`
  - `public static function redirecionar(User $user, string $voltarPara): string` — grava
    `session()->flash(self::CHAVE_DA_SESSAO, ['motivo' => $user->motivoDeIndisponibilidade(), 'excluida_em' => $user->deleted_at?->toIso8601String(), 'voltar_para' => $voltarPara])`
    e devolve `route('auth.conta-indisponivel')`. Quem chama decide como redirecionar
    (`$this->redirect()` no Livewire, `redirect()->to()` no controller).
  - `__invoke(Request $request): Response|RedirectResponse` — lê o flash; sem ele,
    `redirect()->to('/')` (a página não existe "solta": só serve o aviso do request anterior);
    com ele, `response()->view('auth.conta-indisponivel', [...], 403)`.
  - **Logs**: nenhum — quem registra a recusa é o interceptor; um segundo log na exibição seria
    ruído (cortado na auditoria Ponytail).
- **Path**: `routes/web.php` — `Route::get('/conta-indisponivel', ContaIndisponivelController::class)->name('auth.conta-indisponivel');`
  com comentário no padrão do arquivo (por que é pública, por que só renderiza com flash).

### 4. `TelaLogin`: interceptar a falha e explicar

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Filament/Pages/Auth/TelaLogin.php`
- Sobrescrever `authenticate(): ?LoginResponse`:
  ```php
  try {
      return parent::authenticate();
  } catch (ValidationException $excecao) {
      $indisponivel = $this->contaIndisponivelComSenhaCerta();
      if (! $indisponivel instanceof User) {
          throw $excecao;
      }
      // log + Failed (só excluído) + redirect
      $this->redirect(ContaIndisponivelController::redirecionar($indisponivel, Filament::getLoginUrl()));
      return null;
  }
  ```
- `contaIndisponivelComSenhaCerta(): ?User` — dentro de
  `app(Timebox::class)->call(fn (Timebox $t) => ..., (int) config('auth.timebox_duration', 200_000))`:
  `$user = User::withTrashed()->comEmail((string) ($this->data['email'] ?? ''))->first();`
  devolve `$user` só se `$user?->motivoDeIndisponibilidade() !== null` **e**
  `Hash::check((string) ($this->data['password'] ?? ''), (string) $user->password)`. Senão `null`.
  O `Timebox` é o mesmo que o Filament usa para a falha normal — a duração da resposta não
  distingue "não existe" de "existe e está excluído com senha errada" (ADR-03).
- Evento `Failed`: `if ($indisponivel->trashed()) { event(new Failed(Filament::getAuthGuard(), $indisponivel, [])); }`
  — para o inativo o Filament já disparou (`Login.php:105-110`).
- **Logs**: `warning`
  `"[TelaLogin@authenticate] Login recusado: {$motivo} | user: {$user->id} - painel: {$painel} - ip: {$ip}"`
  com `user_id`, `email` mascarado, `motivo`, `painel`, `ip`, `excluida_em`.
- Docblock da classe atualizado: deixa de ser "o login do painel /app" e passa a ser "a tela de
  login dos três painéis".
- **Path**: `app/Providers/Filament/AdminPanelProvider.php` e `InfraPanelProvider.php` —
  `->usingPage(TelaLogin::class)` no `->login(...)` do `AuthDesignerPlugin`, com `use`.

### 5. Login social: os dois ramos e a confirmação

> Skills: `laravel-best-practices`, `socialite-development`

- **Path**: `app/Http/Controllers/Auth/LoginSocialController.php`
- `contaCom()` → `User::withTrashed()->comEmail($email)->first()` (o escopo local do passo 2).
- **Novo** `recusarSeIndisponivel(ProvedorSocial $provedor, User $user, string $mascarado): ?RedirectResponse`:
  `$motivo = $user->motivoDeIndisponibilidade(); if ($motivo === null) return null;`
  log `warning` `"[LoginSocialController@retorno] Recusado: {$motivo} | provedor: {$provedor->value} - user: {$user->getKey()} - email: {$mascarado}"`
  com `user_id`, `email`, `motivo`, `provedor`, `ip`, `excluida_em`;
  `event(new Failed(config('auth.defaults.guard', 'web'), $user, []))` (aqui **sempre**: o
  Socialite não passa pelo guard, ninguém mais dispara);
  `return redirect()->to(ContaIndisponivelController::redirecionar($user, Filament::getPanel('app')->getLoginUrl()));`
- Em `retorno()`:
  - ramo do vínculo: a checagem entra **antes** de `$vinculo->registrarAcesso()` (recusa não
    conta como acesso). Nota: para excluído, `$vinculo->user` já vem `null` (relação respeita o
    escopo) e o fluxo cai no ramo do e-mail — que agora acha o excluído por `withTrashed()`.
  - ramo do e-mail: logo depois de `$user = $this->contaCom($email)`, se `$user instanceof User`
    e indisponível → recusa **antes** do `elseif (vinculoExigeConfirmacao)` e antes de
    `VinculoSocial::vincular()` — nenhum e-mail de confirmação, nenhum vínculo novo para conta
    fechada.
- Em `confirmarVinculo()`: `User::withTrashed()->find(...)`; depois de validar `sub` e antes de
  `VinculoSocial::vincular()`, `recusarSeIndisponivel()`.
- **Logs**: o do método novo. Os existentes não mudam.

### 6. Trait `SituacaoDaConta`: coluna, filtro e as duas ações

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Filament/Concerns/SituacaoDaConta.php` (novo)
  - `colunaDeSituacao(): TextColumn` — **movida** de `AprovacaoDeCadastro`, agora com três
    estados a partir do `$record`: Pendente (`warning`) se `aprovacao_pendente`, Inativo
    (`danger`) se `! ativo`, Ativo (`success`). Excluído não aparece na coluna: só entra na tabela
    pelo `TrashedFilter`, e ali a própria `RestoreAction` é o sinal.
  - `filtroDeInativos(): Filter` — `Filter::make('inativos')->label('Somente inativos')->query(fn (Builder $q) => $q->where('ativo', false))`.
  - `acaoDeDesativar(): Action` — `Action::make('desativar')->label('Desativar')
    ->icon(Heroicon::OutlinedNoSymbol)->color('danger')->authorize('desativar')
    ->visible(fn (User $record): bool => $record->ativo && ! $record->trashed() && $record->motivoParaNaoDesativar() === null)->requiresConfirmation()
    ->modalHeading('Desativar este usuário?')->modalDescription('A pessoa deixa de entrar em qualquer painel até ser reativada. Nada é apagado.')
    ->successNotificationTitle('Usuário desativado')->action(fn (User $record) => $record->desativar())`.
    Sem `try/catch`: a ação já está oculta quando a guarda vale; a exceção do model só aparece
    numa corrida entre duas abas, e a mensagem dela é legível (`ponytail:` no código).
  - `acaoDeReativar(): Action` — espelho: `Heroicon::OutlinedCheckCircle`, `success`,
    `->authorize('reativar')`, `->visible(fn (User $record): bool => ! $record->ativo && ! $record->trashed())`,
    `->modalHeading('Reativar este usuário?')`, `->successNotificationTitle('Usuário reativado')`,
    `->action(fn (User $record) => $record->reativar())`.
  - Docblock no molde de `AprovacaoDeCadastro`: por que trait (regra idêntica nos dois painéis),
    por que `->authorize()` não é decoração, por que a barreira mora no model.
- **Path**: `app/Filament/Concerns/AprovacaoDeCadastro.php` — remove `colunaDeSituacao()` e o
  `use` de `TextColumn`; docblock aponta para a trait nova.
- **Logs**: nenhum na trait (quem loga é o model).

### 7. Os dois `UserResource`

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/Users/UserResource.php`
  - `use SituacaoDaConta;`
  - `->filters([self::filtroDePendentes(), self::filtroDeInativos(), TrashedFilter::make()])`
  - `->recordActions([self::acaoDeAprovar(), self::acaoDeDesativar(), self::acaoDeReativar(), Impersonate::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()])`
    — `RestoreAction` nativa: só aparece em registro trashed (`->visible` do vendor) e autoriza
    por `getRestoreAuthorizationResponse()` → policy `restore()` → `Restore:User`.
  - `getEloquentQuery()` **não** muda (ver análise: badge e busca continuam sem trashed; o
    `TrashedFilter` remove o escopo sozinho quando pedido).
  - `DeleteAction` e `DeleteBulkAction` ficam — agora são soft delete pelo model.
- **Path**: `app/Filament/App/Resources/Users/UserResource.php`
  - `use SituacaoDaConta;` e `self::filtroDeInativos()` nos filtros. **Sem** as ações e sem
    `TrashedFilter` (ADR-04). Comentário curto dizendo por quê, ao lado do já existente sobre
    exclusão.
- **Path**: `config/filament-shield.php` → `resources.manage`:
  `\App\Filament\Admin\Resources\Users\UserResource::class => ['desativar', 'reativar']`, com o
  comentário do bloco ("Action de Resource vem para cá").
- **Path**: `app/Policies/UserPolicy.php` → `desativar()` e `reativar()` devolvendo
  `$authUser->can('Desativar:User')` / `can('Reativar:User')`, com docblock de uma linha cada.
- **Path**: `tests/Kit/PermissoesDeAcoesTest.php` → `inventarioDeAutorizacao()` ganha
  `'app/Filament/Concerns/SituacaoDaConta.php::desativar' => 'permissao'` e `::reativar`.
- Ressemear: os dois seeders rodam no `beforeEach` dos testes; em instalação existente o README
  manda rodar os dois (`ShieldPermissionsSeeder`, `PapeisSeeder`).
- **Logs**: nenhum (as ações delegam ao model).

### 8. Lixeira do `/infra`: `User` entra, e a dívida do `Projeto` é paga

> Skills: `laravel-best-practices`

- **Path**: `app/Providers/Filament/InfraPanelProvider.php` — `->models([Projeto::class, User::class])`;
  o comentário do bloco é reescrito: a recusa antiga a `User` pressupunha exclusão física com
  cascata; com exclusão lógica as pivots ficam e restaurar devolve o que havia. `Role` e `Tenant`
  continuam fora (não têm `SoftDeletes`).
- **Path**: `app/Models/Projeto.php` — `use Recyclable;` ao lado do `SoftDeletes`, com o docblock
  dizendo que sem ela a Lixeira não lista (a dívida). Nenhuma migration: `recycle_bin_items` já
  existe.
- **Backfill**: `php artisan revive:discover` (comando do pacote,
  `DiscoverSoftDeletedRecords.php`) cria as linhas para registros já excluídos antes da trait.
  Vai para o README; não roda automaticamente.
- **Logs**: os do vendor (`Log::info` default) — aceitos.

### 9. Guarda executável: model com `SoftDeletes` é `Recyclable` e está na Lixeira

> Skills: `pest-testing`

- **Path**: `tests/Kit/LixeiraTest.php` (novo) — três casos:
  1. varre `app/Models/*.php`; toda classe com `SoftDeletes` usa `Recyclable` **e** está em
     `RevivePlugin::getModels()` do painel `infra` (a rule de `.ai/rules/models.md` vira teste, e
     ganha a metade que faltava);
  2. excluir um `User` cria a linha em `recycle_bin_items` com `deleted_by` = executor;
  3. restaurar pela Lixeira (`Livewire::test(Promethys\Revive\Pages\RecycleBin::class)` com
     `noPainelBootado('infra')`, ação `restore` do registro) devolve o usuário e ele volta a
     autenticar.
- **Logs**: nenhum.

### 10. Testes da feature

> Skills: `pest-testing`, `feature-test-design`

- **Path**: `tests/Kit/SituacaoDaContaTest.php` (login por senha, `canAccessPanel`, ações,
  permissões, guardas, aviso) e `tests/Kit/LoginSocialContaIndisponivelTest.php` (os dois ramos e
  a confirmação), conforme `04-casos-de-teste.md`.
- Ajuste de regressão: `tests/Kit/VinculoDeProvedorSocialTest.php` CT-V07 → `forceDelete()`.
- Mutação: pelo menos dois mutantes executados à mão e registrados no `03` (remover a guarda de
  `canAccessPanel()`; remover o `Hash::check()` do interceptor).

### 11. README (PT e EN)

> Skills: —

- **Path**: `README.md` — nova seção `## Usuário ativo, inativo e excluído` logo após
  "Registro aberto e aprovação" (antes de "Login social"), com: a tabela dos três estados (o que
  cada um faz, o que a pessoa vê, o que fica em log e na trilha), as ações e permissões, a
  decisão de segurança (aviso só com senha certa; login social prova pelo e-mail verificado), o
  e-mail reservado, como reativar, como restaurar (`/admin/users` → filtro Lixeira → Restaurar;
  `/infra/recycle-bin`), o que restaurar devolve e o que não devolve (dashboards pessoais), a
  sessão aberta de quem é desativado, e "Onde isso vive no código".
- Seção "A Lixeira lista o que você declarar": `models([Projeto::class, User::class])`, a trait
  `Recyclable` como pré-requisito e o `revive:discover`.
- Roteiro: linhas `F-69` (desativar/reativar + aviso) e `F-70` (exclusão lógica + lixeira) em
  "Acesso e autenticação".
- **Path**: `README.en.md` — o espelho.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> Cada passo deve aplicar a escada de simplicidade:
> 1. Reutilizar código existente antes de criar novo
> 2. Usar stdlib do PHP/Laravel antes de código custom
> 3. Usar features nativas antes de dependências
> 4. Uma linha quando possível
> 5. Mínimo código que funciona
>
> Atalhos deliberados devem ser marcados com `ponytail:` comment.
> Após implementação, rodar `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em modo `ultra`** (padrão) na comunicação agent ↔ usuário.
> Arquivos wiki (00-06) são boundary do Caveman — escrever em prosa normal.
> Código, commits e PRs também são boundary do Caveman.

Onde a escada decidiu nesta wiki: `SoftDeletes` + `TrashedFilter` + `RestoreAction` nativos em
vez de coluna própria de exclusão; `Rule::unique` default (inclui trashed) em vez de código para
"e-mail reservado"; o evento `Failed` que o Filament já dispara em vez de gravar a trilha à mão;
o layout do Sentinel já publicado em vez de página nova do zero; um escopo `comEmail()` em vez de
três `whereRaw` iguais.

## Mapeamentos

| Estado do usuário | `ativo` | `deleted_at` | `aprovacao_pendente` | Login por senha | Login social | Coluna Situação |
|---|---|---|---|---|---|---|
| Ativo | `true` | `null` | `false` | entra | entra | Ativo |
| Pendente | `true` | `null` | `true` | genérico (hoje) | "Cadastro recebido" (hoje) | Pendente |
| Inativo | `false` | `null` | qualquer | aviso "desativada" (senha certa) | aviso "desativada" | Inativo |
| Excluído | qualquer | data | qualquer | aviso "excluída em dd/mm/aaaa" (senha certa) | aviso com a data | só via filtro Lixeira |

| `motivo` no log | Quando |
|---|---|
| `conta_inativa` | `ativo = false`, não excluído |
| `conta_excluida` | `deleted_at` preenchido (vence o inativo) |
| `desativacao_recusada` | `desativar()` recusou (`razao`: `propria_conta` \| `ultimo_master_global`) |

## Testes

> Ver `04-casos-de-teste.md` para especificação completa dos cenários de backend.
> Sem `05-casos-de-teste-browser.md` — motivo registrado no `04`.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse <arquivos tocados> --no-progress`
- [ ] `php artisan test --compact tests/Kit/SituacaoDaContaTest.php tests/Kit/LoginSocialContaIndisponivelTest.php tests/Kit/LixeiraTest.php`
- [ ] regressão: os 14 arquivos da seção "Natureza da Wiki"
- [ ] `vendor/bin/pest --parallel --tia` (ou `--testsuite=Kit` se não houver driver de cobertura)
- [ ] duas mutações registradas no `03`

## Commits

- `:sparkles: feat(usuarios): status ativo/inativo e exclusão lógica — model, migration e Lixeira`
- `:sparkles: feat(login): conta inativa ou excluída cai no aviso do Sentinel, por senha e por provedor social`
- `:sparkles: feat(admin): ações Desativar/Reativar, filtro Lixeira e Restaurar em /admin/users`
- `:memo: docs: README PT/EN, wiki e testes da feature` (testes acompanham cada commit de código; o último fecha wiki e README)

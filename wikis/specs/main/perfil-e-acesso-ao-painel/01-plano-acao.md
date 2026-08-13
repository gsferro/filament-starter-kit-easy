# Plano de Ação — Perfil × permissão × acesso ao painel

## Objetivo

Fazer do **papel** a única fonte do acesso a painel. Hoje o kit decide isso em três
lugares diferentes: uma lista de nomes de papel dentro de `User::canAccessPanel()`, uma
lista de substrings dentro do `PapeisSeeder` e um `shield:generate` que só conhece o
painel `/admin`. O resultado é que `/app` está aberto a qualquer usuário autenticado,
que as permissões dos painéis `/app` e `/infra` nunca chegaram a existir no banco, e que
a tela do Shield mostra os Resources dos três painéis misturados numa lista só.

Este plano introduz a coluna `roles.painel`, reescreve `canAccessPanel()` para lê-la,
faz o `ShieldPermissionsSeeder` gerar permissão para **todos** os painéis, agrupa a tela
do Shield por painel, torna papel obrigatório no cadastro de usuário (mais a
organização, quando a tenancy está ligada) e grava a regra "Resource ou RelationManager
novo → gere as permissões" onde os agentes de IA leem antes de escrever código.

## Contexto

### O que está quebrado hoje

| Sintoma | Causa |
| --- | --- |
| Qualquer autenticado entra em `/app` | `User::canAccessPanel()` retorna `'app' => true` (`app/Models/User.php:79`) |
| `/app` e `/infra` não têm nenhuma permission no banco | `ShieldPermissionsSeeder` chama `shield:generate --all --panel=admin` e só (`database/seeders/ShieldPermissionsSeeder.php:31-35`) |
| Matriz de papéis casa por substring | `PapeisSeeder` filtra `str_contains($p, 'User')`, `'Role'`, `'AgenteIa'`, `'Tenant'` (`database/seeders/PapeisSeeder.php:53-62`) — um Resource novo chamado `UserPreference` entra no papel `admin` por acidente |
| Tela do Shield mistura os painéis | `HasShieldFormComponents::getResourceEntitiesSchema()` itera `FilamentShield::getResources()` numa `Grid` plana (`vendor/bezhansalleh/filament-shield/src/Traits/HasShieldFormComponents.php:38-57`) |
| Criar usuário não dá acesso a nada | `UserResource::form()` tem `roles` opcional e nenhum vínculo de organização (`app/Filament/Admin/Resources/Users/UserResource.php:57-88`) |

### O que o Shield NÃO faz (e por isso precisa ser construído aqui)

Investigado no vendor da versão instalada (`bezhansalleh/filament-shield` 4.3.1):

- **O nome da permission não carrega o painel.** `FilamentShield::defaultPermissionKeyBuilder()`
  (`vendor/.../src/FilamentShield.php:86-89`) monta `{Affix}{separator}{Subject}` — nada
  de painel. A tabela `permissions` é a do spatie, sem coluna extra
  (`database/migrations/2026_08_12_164859_create_permission_tables.php:26-33`).
- **O único diferenciador persistido é o `guard_name`**, tirado do painel corrente
  (`Utils::getFilamentAuthGuard()`, `vendor/.../src/Support/Utils.php:28-31`). Os três
  painéis do kit usam o mesmo guard `web`, então `ViewAny:User` gerado no `/admin` e no
  `/app` é **a mesma linha**.
- **Não existe hook para agrupar a tela por painel.** Nada em
  `HasShieldFormComponents` consulta `Filament::getCurrentPanel()`. O caminho suportado
  para mudar a tela é `shield:publish`
  (`vendor/.../src/Commands/PublishCommand.php:60-91`).
- **RelationManager não gera permission alguma.** A descoberta cobre apenas Resources,
  Pages e Widgets (`vendor/.../src/Concerns/HasEntityDiscovery.php:23-42`), e
  `FilamentShield::resolveSubject()` (`FilamentShield.php:151-163`) lança
  `InvalidArgumentException` para qualquer outra coisa.
- **A descoberta é por painel corrente** — `discovery.discover_all_*` está `false` nas
  três chaves (`config/filament-shield.php:268-272`), então `FilamentShield::getResources()`
  devolve só o painel corrente. Isso é o que torna o mapa por painel possível.
- **`FilamentShield` é `scoped` e memoizado com `once()`**
  (`FilamentShieldServiceProvider.php:46`, `FilamentShield.php:66-84`). Trocar de painel
  no mesmo processo **não** invalida o cache: quem quiser varrer os três painéis numa só
  execução tem de fazer `app()->forgetInstance('filament-shield')` entre as voltas.

### A decisão que atravessa tudo

Permissão continua **global por nome**; o que passa a ser por painel é o **papel**.
`ViewAny:User` é uma linha só, e é o papel que declara em qual painel ela vale. A
fronteira de painel é `canAccessPanel()`; a fronteira de dado é o escopo da query. Ver
ADR-01 e ADR-02 em `02-decisoes-arquiteturais.md` — sem isso o resto do plano parece
inconsistente.

## Análise dos Arquivos Existentes

### `app/Models/User.php`

- `canAccessPanel()` (`:71-82`) — a lista de nomes de papel a ser substituída.
- `temPapelGlobal()` (`:104-122`) — troca temporária do `PermissionRegistrar` para o
  contexto global, com `unsetRelation('roles')` nas duas pontas. **Será deletado**: a
  relação nova (passo 3) resolve a mesma pergunta com uma query direta na pivot, sem
  mexer em estado global do container.
- `isMasterGlobal()` (`:85-88`) — permanece, reescrito sobre a relação nova.
- `getTenants()`, `canAccessTenant()` (`:153-187`) — intocados.

### `database/seeders/PapeisSeeder.php`

O helper `papel()` (`:79-88`) já grava `roles.team_id = null` quando `permission.teams`
está ligado — a definição do papel é global e a atribuição é que é por tenant. Continua
valendo; ganha só o argumento `painel`.

### `database/seeders/ShieldPermissionsSeeder.php`

Roda `shield:generate` dentro de try/catch porque o comando é interativo e quebraria o
`composer create-project` (`:24-46`). O try/catch fica; o que muda é passar a varrer os
três painéis.

### `app/Filament/Admin/Resources/Users/UserResource.php`

O `Select::make('roles')` (`:57-88`) já grava pela API do spatie via
`saveRelationshipsUsing()` + `syncRoles()` — regra registrada em `.ai/rules/filament.md`.
**Não mexer nessa parte.** O que muda: `->required()`, rótulo da opção mostrando o
painel, e o campo de organizações quando a tenancy está ligada.

### `app/Providers/KitServiceProvider.php`

`configureGates()` (`:83-99`) define `ver-ai-tasks`, `ver-logs`, `command-center:access`
e `viewPulse` como `$user->hasRole('infra')`. `hasRole()` respeita o team corrente — no
`/infra` (sem tenancy no painel) o contexto é o global fixado por `configureTenancy()`
(`:74-81`), então continua correto. Os gates passam a usar o helper novo por
consistência, não por correção.

## Autorização

- **Policies**: nenhuma criada ou alterada. As 4 policies existentes (`User`, `Role`,
  `Tenant`, `AgenteIa`) seguem delegando a `$authUser->can('Ação:Model')`.
- **Gates**: `Gate::before` do `master_global` intocado (`KitServiceProvider.php:87`).
  Os quatro gates de `/infra` passam a chamar `$user->temPapelDoPainel('infra')`.
- **Middleware**: nenhum novo.
- **Guards**: um só (`web`) nos três painéis. Ver ADR-02 para por que não se separa
  guard por painel.
- **A fronteira nova**: `User::canAccessPanel()` passa a exigir papel com
  `roles.painel = {id do painel}`. Nos painéis **sem tenancy** (`/admin`, `/infra`) o
  papel precisa estar atribuído no **contexto global** (`model_has_roles.team_id =
  Tenant::CONTEXTO_GLOBAL`) — ser `admin` dentro de uma organização não é credencial
  para administrar a instalação. No painel **com tenancy** (`/app`) vale o papel em
  qualquer organização; qual organização é decidido depois, por `canAccessTenant()`.

## Rotas

Nenhuma rota nova. A tela de papéis muda de dono: sai
`BezhanSalleh\FilamentShield\Resources\Roles\RoleResource` e entra
`App\Filament\Admin\Resources\Roles\RoleResource`, publicada por `shield:publish`. A URL
não muda — o slug segue vindo de `config('filament-shield.shield_resource.slug')` =
`shield/roles`.

## Variáveis de Ambiente

Nenhuma nova.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

| O que | Impacto |
| --- | --- |
| Acesso a `/app` | **Quebra deliberada.** Deixa de ser aberto a qualquer autenticado. Sem papel de painel `app`, o usuário cai em 403. Pré-release 1.0: sem migration de retrofit — quem atualizar roda os seeders. |
| `User::temPapelGlobal()` | **Removido.** Quem o chamava troca por `temPapelDoPainel()` ou `isMasterGlobal()`. Aparece no CHANGELOG. |
| `tests/Kit/PaineisTest.php` | O caso `/app aberto a autenticado` (`:52-54`) inverte de sentido. |
| `tests/Tenancy/TenancyTest.php` | O caso HTTP de `/app/{slug}` (`:195-203`) passa a exigir papel no usuário do setup. |
| `PapeisSeeder` | A matriz de permissões de `admin` e `infra` muda de conjunto: passa a ser exatamente o do painel, não mais o casamento por substring. `admin` **perde** `AgenteIa`? Não — o `AgenteIaResource` vive em `app/Filament/Admin/Resources/AgentesIa`, logo continua no painel `admin`. |
| Tela `/admin/shield/roles` | Passa a ser código do projeto. Upgrade maior do Shield exige reconferir o diff do vendor. |
| `kit:update` | `app/Filament` e `database/migrations` já estão em `CAMINHOS_DO_KIT` (`app/Console/Commands/KitUpdate.php:66-115`); o Resource publicado e a migration nova chegam sozinhos. `app/Support` **não** está na lista e precisa entrar. |

## Rollback

- **Migration down**: `Schema::table('roles', fn ($t) => $t->dropColumn('painel'))`. A
  coluna é aditiva e nullable — derrubá-la não perde papel nem atribuição.
- **Sem feature flag.** O acesso a painel é fronteira de segurança; um interruptor que
  a desliga é uma porta.
- **Reversão da tela do Shield**: apagar `app/Filament/Admin/Resources/Roles/` faz o
  `FilamentShieldPlugin` voltar a registrar o Resource dele
  (`Utils::isResourcePublished()`, `vendor/.../src/Support/Utils.php:33-41`).

## Dependências

Nenhum pacote novo. Tudo já instalado: `bezhansalleh/filament-shield` 4.3.1,
`spatie/laravel-permission` (transitiva do Shield), `filament/filament` 5.7.6.

## Riscos

| Risco | Mitigação |
| --- | --- |
| `shield:publish` congela a tela na versão 4.3.1 do Shield | O arquivo publicado edita **um** método (`getResourceEntitiesSchema`) e acrescenta **um** campo; o resto é cópia. CT-10 falha se o vendor mudar a forma da entidade (`resourceFqcn`/`model`/`modelFqcn`). |
| `forgetInstance('filament-shield')` entre painéis é dependência de detalhe interno | CT-06 prova que os três painéis geram conjuntos diferentes. Se o Shield trocar a memoização, o teste acusa. |
| Instalação existente perde `/app` | Deliberado e documentado (ver Impacto). O `kit:install` semeia papel para o usuário inicial; o CHANGELOG manda rodar os seeders. |
| Resource exposto em dois painéis | O mapa passa a listar o Resource nos dois; a permission é uma só e vale nos dois. Documentado em ADR-01. |

## Channel de Log da Feature

**Nenhum channel novo.** `config/logging.php:99-105` já declara `autenticacao` (driver
`daily`, 14 dias, `replace_placeholders`), e `grep -rn "channel('autenticacao')" app/
tests/` não retorna nada: o canal existe e nunca foi usado. Acesso a painel, negativa de
papel e, nas duas wikis irmãs, convite e promoção de usuário são todos eventos de
autenticação e acesso — é exatamente o canal.

Criar um `acesso` ao lado dele seria um segundo arquivo para o mesmo assunto, e o cabeçalho
da seção de canais do kit é explícito: "um por camada transversal".

`Log::channel('autenticacao')` nos três documentos (`perfil-e-acesso-ao-painel`,
`convite-de-usuario`, `admin-da-organizacao`).

O que se loga: **negativas** e **mudanças de poder**. Acesso concedido não vira log — é
o caminho feliz de todo request e encheria o arquivo sem informar nada. Vale também a
regra LGPD do cabeçalho do arquivo: identificadores mascarados, nunca conteúdo em claro.

## Estrutura de Implementação

### 1. Coluna `roles.painel`

> Skills: `laravel-best-practices`

- **Path**: `database/migrations/2026_08_13_000001_add_painel_to_roles_table.php`
- O nome usa a data, **não** o prefixo `0001_01_01_` da fundação: a tabela `roles` nasce
  em `2026_08_12_164859_create_permission_tables` e esta migration precisa rodar depois.

```php
Schema::table(config('permission.table_names.roles', 'roles'), function (Blueprint $table): void {
    $table->string('painel')->nullable()->index()->after('guard_name');
});
```

- `nullable` = o papel **não abre painel algum** (ADR-03). É o valor do `master_global`,
  que entra nos três por `Gate::before` — nunca pela coluna. Default fecha.
- Sem FK: painel não é registro de banco, é id declarado no `PanelProvider`.
- `down()`: `dropColumn('painel')`.

### 2. `App\Support\Paineis` — o mapa painel × entidade × permission

> Skills: `laravel-best-practices`

- **Path**: `app/Support/Paineis.php` (diretório novo — aprovado por ser o único lugar
  neutro entre seeder, model e Resource; entra em `CAMINHOS_DO_KIT` no passo 11)

```php
final class Paineis
{
    /** ['admin' => '/admin', 'app' => '/app', 'infra' => '/infra'] — para selects. */
    public static function opcoes(): array;

    /** Nomes de permission de um painel, na fonte do próprio Shield. */
    public static function permissoes(string $painel): Collection;

    /**
     * Entidades de Resource por painel, no formato do Shield.
     *
     * ['admin' => [['resourceFqcn'=>…, 'model'=>…, 'modelFqcn'=>…, 'permissions'=>[…]], …], …]
     * Consumido pela tela de papéis (passo 7b) para agrupar as seções.
     */
    public static function resources(): array;
}
```

`permissoes()` e `resources()` saem da **mesma** varredura, memoizada em propriedade
estática dentro do processo — a tela de papéis chama as duas no mesmo request e varrer
os três painéis duas vezes seria desperdício gratuito.

**Falhar alto se a descoberta global estiver ligada**: se qualquer chave de
`config('filament-shield.discovery')` for `true`, `FilamentShield::getResources()` achata
todos os painéis (`vendor/.../src/Concerns/HasEntityDiscovery.php:23-42`) e o mapa deixa
de separar coisa alguma — em silêncio, produzindo uma matriz de permissão errada.
`Paineis` lança `RuntimeException` nesse caso.

Implementação de `permissoes()` e `porEntidade()`: varrer `Filament::getPanels()`,
trocando o painel corrente e **descartando a instância memoizada do Shield** a cada
volta, restaurando o painel original no `finally`.

```php
$anterior = Filament::getCurrentPanel();

try {
    foreach (Filament::getPanels() as $id => $painel) {
        app()->forgetInstance('filament-shield');
        Filament::setCurrentPanel($painel);
        $mapa[$id] = FilamentShield::getEntitiesPermissions();
    }
} finally {
    app()->forgetInstance('filament-shield');
    $anterior && Filament::setCurrentPanel($anterior);
}
```

- Por que `FilamentShield::getEntitiesPermissions()` e não montar o nome à mão: é a
  **mesma** função que o `shield:generate` usa para decidir o que gravar
  (`vendor/.../src/FilamentShield.php:114-124`). Duplicar a montagem do nome aqui
  significaria reimplementar `separator`, `case`, `subject` e os affixes das policies —
  quatro chaves de config que dessincronizam em silêncio.
- Por que `forgetInstance`: `FilamentShield` é `scoped` e usa `once()`. Sem descartar,
  os três painéis devolvem o resultado do primeiro.
- **Sem cache próprio.** O método roda em seeder e ao montar a tela de papéis, não em
  request de usuário. Cachear seria complexidade especulativa.
- **Logs**: nenhum. É função pura de leitura.

### 3. `User` — acesso a painel vem do papel

> Skills: `laravel-best-practices`, `eloquent-best-practices`

- **Path**: `app/Models/User.php`

**4a. Relação que ignora o team corrente**

```php
/**
 * Papéis do usuário em QUALQUER contexto de team.
 *
 * É a `roles()` do spatie sem o `wherePivot(team_id)` que ele aplica quando
 * `permission.teams` está ligado. Existe porque as perguntas de ACESSO A PAINEL
 * não são perguntas de tenant: "este usuário é admin em alguma organização?" não
 * pode depender de qual organização está aberta no momento.
 */
public function papeisEmQualquerContexto(): MorphToMany
{
    return $this->morphToMany(
        config('permission.models.role'),
        'model',
        config('permission.table_names.model_has_roles'),
        config('permission.column_names.model_morph_key'),
        app(PermissionRegistrar::class)->pivotRole,
    );
}
```

**4b. As duas perguntas, cada uma direta**

```php
/** @param int|null $contexto team_id exigido; null aceita qualquer contexto. */
public function temPapelDoPainel(string $painel, ?int $contexto = null): bool
{
    return $this->papeisEmQualquerContexto()
        ->where('painel', $painel)
        ->where('guard_name', $this->getDefaultGuardName())
        ->when($contexto !== null, fn ($q) => $q->wherePivot($this->colunaDeTeam(), $contexto))
        ->exists();
}
```

`isMasterGlobal()` é a mesma query trocando `painel` por `name`. **Sem um método genérico
`temPapel($coluna, $valor, …)` no meio**: dois chamadores não justificam passar nome de
coluna como string, e o genérico sairia mais longo que as duas queries somadas.

`colunaDeTeam()` devolve `config('permission.column_names.team_foreign_key', 'team_id')`.

**4c. `temPapelGlobal()` é removido**

O método atual (`:104-122`) troca o `PermissionRegistrar` global, faz `unsetRelation`
duas vezes e restaura no `finally` — três efeitos colaterais para responder uma pergunta
de leitura. A relação de 4a responde a mesma coisa com um `exists()`. Deletar.

```php
public function isMasterGlobal(): bool
{
    return $this->papeisEmQualquerContexto()
        ->where('name', config('filament-shield.super_admin.name', 'master_global'))
        ->where('guard_name', $this->getDefaultGuardName())
        ->when(config('permission.teams'), fn ($q) => $q->wherePivot($this->colunaDeTeam(), Tenant::CONTEXTO_GLOBAL))
        ->exists();
}

/** Team exigido para papéis que governam a instalação — null quando não há teams. */
private function contextoGlobal(): ?int
{
    return config('permission.teams') ? Tenant::CONTEXTO_GLOBAL : null;
}
```

**4d. `canAccessPanel()`**

```php
public function canAccessPanel(Panel $panel): bool
{
    if ($this->isMasterGlobal()) {
        return true;
    }

    // Painel com tenancy (/app): basta ter o papel em ALGUMA organização — qual
    // organização é decidido depois, por canAccessTenant(). Painel sem tenancy
    // (/admin, /infra) governa a instalação inteira: o papel tem de estar atribuído
    // no contexto global. Ser admin dentro de uma organização não é credencial
    // para administrar o sistema.
    $contexto = $panel->hasTenancy() ? null : $this->contextoGlobal();

    if ($this->temPapelDoPainel($panel->getId(), $contexto)) {
        return true;
    }

    Log::channel('autenticacao')->warning(
        "[User@canAccessPanel] Acesso a painel negado | user: {$this->id} - painel: {$panel->getId()}",
        ['user_id' => $this->id, 'painel' => $panel->getId(), 'motivo' => 'sem_papel_do_painel'],
    );

    return false;
}
```

- **Logs**: um `warning` na negativa, com `motivo`. Nenhum log no caminho feliz.

### 4. `PapeisSeeder` — papel declara painel, permissão vem do painel

> Skills: `laravel-best-practices`

- **Path**: `database/seeders/PapeisSeeder.php`

| Papel | `painel` | Permissões |
| --- | --- | --- |
| `master_global` | `null` (não abre painel; entra por `Gate::before`) | nenhuma |
| `admin` | `admin` | `Paineis::permissoes('admin')` |
| `infra` | `infra` | `Paineis::permissoes('infra')` |
| `panel_user` | `app` | `Paineis::permissoes('app')` |

- `papel()` ganha o parâmetro `?string $painel` e passa a usar `updateOrCreate` (para
  carimbar o painel em papel que já existe) em vez de `firstOrCreate`.
- Some o casamento por substring (`:53-62`).
- `panel_user` deixa de ser "papel sem nada" e passa a ser o perfil básico do `/app` —
  é o que dá acesso ao painel de negócio.
- **Logs**: nenhum. Seeder já reporta pelo `$this->command`.

### 5. `ShieldPermissionsSeeder` — gerar para os três painéis

> Skills: `laravel-best-practices`

- **Path**: `database/seeders/ShieldPermissionsSeeder.php`

```php
foreach (array_keys(Filament::getPanels()) as $painel) {
    app()->forgetInstance('filament-shield');

    Artisan::call('shield:generate', [
        '--all' => true, '--panel' => $painel, '--no-interaction' => true,
    ]);
}
```

- O try/catch e o aviso de fallback continuam, agora por painel: falhar num painel não
  pode abortar os outros.
- O `--all` também gera as **policies**; gerar três vezes é idempotente
  (`Utils::generatePolicyFor()` pula policy existente).
- **Logs**: nenhum; o seeder escreve no console.

### 6. Tela do Shield agrupada por painel

> Skills: `laravel-best-practices`, `tailwindcss-development`

**7a. Publicar**

```bash
php artisan shield:publish --panel=admin --no-interaction
```

Copia `RoleResource.php` + `Pages/{Create,Edit,List,View}Role.php` para
`app/Filament/Admin/Resources/Roles/` com o namespace reescrito
(`vendor/.../src/Commands/PublishCommand.php:60-91`). O `FilamentShieldPlugin` deixa de
registrar o dele sozinho, porque `Utils::isResourcePublished()` acha `\RoleResource`
entre os resources do painel (`Utils.php:33-41`).

**7b. Agrupar as seções por painel**

- **Path**: `app/Filament/Admin/Resources/Roles/RoleResource.php`

Sobrescrever `getResourceEntitiesSchema()` — o único método com mudança de comportamento.

**A armadilha que decide a implementação**: `FilamentShield::getResources()` devolve os
Resources do **painel corrente**. A tela vive no `/admin`, então iterá-la mostraria só os
Resources do `/admin` — agrupados por painel, sim, mas com um grupo só. Para que a tela
mostre os três, a fonte da lista tem de ser a varredura do passo 3.

Portanto `Paineis` (passo 3) devolve, na mesma varredura, também as entidades já no
formato do Shield:

```php
/** ['admin' => [ ['resourceFqcn'=>…, 'model'=>…, 'modelFqcn'=>…, 'permissions'=>[…]], … ], …] */
public static function resources(): array;
```

E a tela itera esse array:

```php
public static function getResourceEntitiesSchema(): ?array
{
    return collect(Paineis::resources())
        ->map(fn (array $entidades, string $painel): Section => Section::make('Painel '.Paineis::opcoes()[$painel])
            ->description('Permissões dos Resources registrados neste painel.')
            ->collapsible()
            ->columnSpanFull()
            ->schema(collect($entidades)->map(static::secaoDoResource(...))->all()))
        ->values()
        ->all();
}
```

- `secaoDoResource(array $entity): Section` é o corpo do `map()` original do vendor
  (`HasShieldFormComponents.php:40-56`), extraído para método próprio no Resource
  publicado. Zero lógica duplicada, uma indireção a mais.
- Rótulo do grupo: `'Painel /admin'`, `'Painel /app'`, `'Painel /infra'`.
- Painel sem Resource nenhum não aparece (a chave nem existe no array).
- O `name` do `CheckboxList` continua sendo o FQCN do Resource
  (`HasShieldFormComponents.php:127`) — chave única, então dois painéis nunca colidem no
  estado do formulário mesmo compartilhando permission.

**7c. Campo `painel` no papel**

- Um `Select::make('painel')` no schema do `RoleResource`, opções `Paineis::opcoes()`,
  `placeholder` "Global (todos os painéis)", `nullable`, com `helperText` explicando que
  é isso que dá acesso ao painel.

**7d. Persistir o campo** — o passo que quebra em silêncio se esquecido

- **Paths**: `app/Filament/Admin/Resources/Roles/Pages/CreateRole.php` e `EditRole.php`

Os dois métodos `mutateFormDataBefore*` do vendor tratam **toda** chave que não seja
`name`, `guard_name`, `select_all` ou a FK de tenant como se fosse uma lista de
permissões, e depois devolvem `Arr::only($data, ['name', 'guard_name'])`
(`CreateRole.php:22-35`, `EditRole.php:29-43`). Sem editar os dois:

1. `painel` entraria no `flatten()` das permissões e o `afterCreate` tentaria criar uma
   permission chamada `admin`;
2. o valor nunca chegaria ao `save()`.

Acrescentar `'painel'` **nas duas listas** (`in_array` e `Arr::only`) nos dois arquivos.

- **Logs**: `Log::channel('autenticacao')->info('[RoleResource@salvar] Painel do papel definido | papel: {nome} - painel: {painel}', [...])` no `afterSave`/`afterCreate`. Mudança de poder se registra.

### 7. `UserResource` — perfil obrigatório e organização no cadastro

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/Users/UserResource.php`

**Não tocar** no `saveRelationshipsUsing()` que grava por `syncRoles()` — é a armadilha
já registrada em `.ai/rules/filament.md`.

- `Select::make('roles')` ganha:
  - `->required()` — usuário sem papel não entra em painel nenhum, criar um assim é
    criar uma conta morta;
  - `->getOptionLabelFromRecordUsing(fn (Role $r) => $r->painel ? "{$r->name} — /{$r->painel}" : "{$r->name} — global")`
    — o painel aparece em cada opção. É a forma mais barata de "dar acesso a um painel"
    ficar explícito na tela; agrupar exigiria abandonar o `->relationship()`, que é quem
    hidrata o estado na edição e mantém a chave fora do `update()` do model.
  - `->helperText('O painel a que o usuário terá acesso vem do papel.')`
- Campo novo, só com a tenancy ligada:

```php
Select::make('tenants')
    ->label(config('kit.tenancy.label_plural', 'Organizações'))
    ->relationship('tenants', 'nome')
    ->multiple()->preload()->searchable()
    ->required()
    ->visible(fn (): bool => (bool) config('kit.tenancy.enabled'))
    ->helperText('Sem vínculo, o usuário entra no /app e não vê organização nenhuma.'),
```

  - Aqui `->relationship()` sozinho **é** suficiente: `tenant_user` é pivot simples, sem
    coluna NOT NULL extra — o problema do `sync()` era específico de
    `model_has_roles.team_id`.
  - `required` quando visível: é o vínculo que evita acesso indevido a dados de outra
    organização.
- **Logs**: nenhum no Resource. A auditoria de `User` já é coberta por
  `AuditsFillables`; papel e vínculo entram no log em 7d e na wiki `admin-da-organizacao`.

### 8. `DemoTenancySeeder`

> Skills: `laravel-best-practices`

- **Path**: `database/seeders/DemoTenancySeeder.php:28-49`
- Ana, Bruno e Carla passam a receber `panel_user` na organização correspondente,
  atribuído dentro do contexto de cada tenant (`setPermissionsTeamId`) — senão a demo
  nasce sem acesso ao `/app` e o `--demo` deixa de demonstrar coisa alguma.

### 9. Gates de `/infra`

> Skills: `laravel-best-practices`

- **Path**: `app/Providers/KitServiceProvider.php:91-94`
- `fn (User $user): bool => $user->hasRole('infra')` vira
  `fn (User $user): bool => $user->temPapelDoPainel('infra', ...)`. Mudança de
  consistência: os gates deixam de depender do team corrente do request.

### 10. `kit:update` conhece `app/Support`

> Skills: `laravel-best-practices`

- **Path**: `app/Console/Commands/KitUpdate.php`, constante `CAMINHOS_DO_KIT` (`:66-115`)
- Acrescentar `'app/Support'`. Sem isso `App\Support\Paineis` nunca chega a quem
  instalou — exatamente o buraco da versão 0.9.8.
- `tests/Kit/KitUpdateTest.php` varre a árvore e falharia sozinho; conferir que falha
  **antes** de corrigir, para provar que o teste funciona.

### 11. Regra de IA: Resource/RelationManager novo → gere as permissões

> Skills: nenhuma

- **Path**: `.ai/rules/filament.md` (glob `app/Filament/**` já cobre Resources e
  RelationManagers)

Acrescentar uma segunda regra, no formato das existentes (o que não fazer + o sintoma
literal + o que fazer + arquivo de referência + como testar):

```markdown
## Resource ou RelationManager novo exige gerar as permissões

Resource novo nasce sem permission: a tela responde 403 para todo mundo que não é
`master_global`. Depois de `make:filament-resource`, rode sempre:

    php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
    php artisan db:seed --class=Database\\Seeders\\PapeisSeeder

O primeiro roda `shield:generate --all` **em cada painel** e escreve as policies; o
segundo recorta a matriz por painel (`App\Support\Paineis::permissoes()`) e devolve as
permissões aos papéis. Os dois são idempotentes.

**RelationManager o Shield não enxerga.** A descoberta cobre apenas Resources, Pages e
Widgets, então nenhuma permission é gerada para ele e a autorização recai na policy do
model relacionado. Se esse model já tem Resource num painel, não há nada a fazer. Se
não tem, crie a policy à mão (`make:policy`) e declare as chaves em
`config/filament-shield.permissions.custom_permissions` antes de rodar os seeders — do
contrário o RelationManager fica aberto a qualquer um que abra o Resource pai.

O papel novo precisa declarar o `painel` em que vale (`roles.painel`), senão ninguém
entra na tela mesmo com a permission no banco.
```

- `.ai/rules/index.md` não muda: o glob `app/Filament/**` já mapeia para este arquivo.
- Os cinco agentes leem `.ai/rules` por instrução do `CLAUDE.md`/`AGENTS.md` gerados pelo
  Boost — **não editar esses dois arquivos**, `boost:update` os sobrescreve.

### 12. Documentação

> Skills: nenhuma

| Arquivo | O que muda |
| --- | --- |
| `wikis/arquitetura.md` | `## Autorização, em três níveis` — a tabela ganha a linha do painel vindo de `roles.painel`; nova subseção `### Painel é dado do papel` |
| `wikis/convencoes.md` | `## Autorização` — o bullet dos seeders passa a citar os três painéis e o RelationManager; `## Armadilhas já resolvidas` ganha a linha do `mutateFormDataBefore*` do Shield |
| `wikis/receitas.md` | `## Resource novo` atualizada; **`## RelationManager novo`** (não existe hoje); `## Papel novo`; `## Problemas comuns` ganha "usuário entra e vê 403 no painel" |
| `wikis/pacotes.md` | linha do Shield anota que o `RoleResource` está publicado no projeto |
| `README.md` | `## Os três painéis` (quem entra vem do papel), `## Depois de criar seus Resources` (os dois seeders + RelationManager), `## Convenções do kit` |
| `README.en.md` | espelho obrigatório do acima |

## Filosofia de Implementação

> **Ponytail ativo em modo `full`.** A escada de simplicidade em cada passo:
> reutilizar antes de criar, stdlib antes de custom, feature nativa antes de dependência,
> uma linha quando der, mínimo que funciona.
>
> Aplicações concretas já embutidas neste plano:
> - `FilamentShield::getEntitiesPermissions()` em vez de remontar o nome da permission.
> - `->getOptionLabelFromRecordUsing()` em vez de abandonar o `->relationship()` para
>   agrupar opções.
> - `->relationship()` puro no campo de organizações (o `syncRoles()` só é necessário
>   onde existe coluna NOT NULL na pivot).
> - **Deleção**: `User::temPapelGlobal()` sai inteiro.
> - Sem cache em `Paineis` — roda em seeder e ao montar tela de admin, não em request
>   de usuário.
> - Sem migration de retrofit — pré-release, o certo vale mais que o compatível.
>
> Atalhos deliberados marcados com comentário `ponytail:`.
> Ao final, `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em `full`** na conversa com o usuário. Arquivos wiki, código, commits e
> READMEs são boundary — prosa normal.

## Mapeamentos

### Papel → painel (estado final dos seeders)

| Papel | `roles.painel` | Onde entra | Contexto exigido |
| --- | --- | --- | --- |
| `master_global` | `null` | todos, por `Gate::before` — não pela coluna | global |
| `admin` | `admin` | `/admin` | global |
| `infra` | `infra` | `/infra` | global |
| `panel_user` | `app` | `/app` | qualquer organização |

### Painel → namespace de descoberta

| Painel | Resources | Pages | Widgets |
| --- | --- | --- | --- |
| `admin` | `App\Filament\Admin\Resources` | `App\Filament\Admin\Pages` | `App\Filament\Admin\Widgets` |
| `app` | `App\Filament\App\Resources` | `App\Filament\App\Pages` | `App\Filament\App\Widgets` |
| `infra` | `App\Filament\Infra\Resources` | `App\Filament\Infra\Pages` | `App\Filament\Infra\Widgets` |

O mapa **não** é montado a partir desses namespaces: Resource de plugin (o do Shield, os
do Pulse, os do jobs-monitor) não vive sob `App\`. A fonte é `$panel->getResources()`.

## Testes

> Ver `04-casos-de-teste.md`. Onze casos: `tests/Kit/PerfilEAcessoTest.php` (single-tenant)
> e `tests/Tenancy/PerfilEAcessoTenancyTest.php` (multi-tenant), mais os ajustes em
> `tests/Kit/PaineisTest.php` e `tests/Tenancy/TenancyTest.php`.

## Verificação Final

- [ ] `php artisan migrate`
- [ ] `php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder`
- [ ] `php artisan db:seed --class=Database\\Seeders\\PapeisSeeder`
- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --compact --group=kit`
- [ ] `composer types:check`
- [ ] `GET /admin/shield/roles` mostra três grupos de painel
- [ ] Usuário só com `panel_user` entra em `/app` e leva 403 em `/admin` e `/infra`
- [ ] Usuário sem papel nenhum leva 403 nos três

## Commits

- `:sparkles: painel de acesso vem do papel, nao de uma lista no codigo`
- `:memo: wiki da feature perfil-e-acesso-ao-painel`

# Plano de Ação — O administrador da organização não alcança quem governa a instalação

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: evolução
- **Wiki ancestral**: `wikis/specs/main/admin-da-organizacao/` (a persona `admin_app` e as suas seis
  barreiras) e `wikis/specs/feat/travas-de-escalada-de-papeis/` (a trava de papel na escrita)
- **Motivo**: as barreiras existentes recortam **qual organização** e **qual papel**; nenhuma
  recorta **quem é o alvo**. Um `master_global` vinculado à organização é editável pelo `admin_app`.
- **Toca infra compartilhada?**: **sim** — `App\Models\User` (usado pelo guard de autenticação e
  pelos três painéis) ganha um predicado e um scope. Regressão obrigatória sobre
  `tests/Tenancy/AdminDaOrganizacaoTest.php`, `EscopoFailClosedTest.php` e a suíte `Tenancy`.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Observação |
|----|----------|----------|------------|
| RQ-01 | `admin_app` vê e gere usuários | — | já existe; CT de regressão garante que o recorte novo não esconde usuário comum |
| RQ-02 | não vê quem governa a instalação, em nenhuma superfície | 1, 2 | scope no model + `getEloquentQuery()` — os quatro consumidores passam por lá |
| RQ-03 | não altera quem governa a instalação, por nenhum caminho | 1, 3 | resposta de autorização de edição negada, **independente** da query |
| RQ-04 | só usuários da sua organização | — | já existe; CT de regressão |

## Objetivo

Fechar a única brecha de escalada que restou no `/app`: o `admin_app` alcançar a **conta** de quem
governa a instalação. Depois desta entrega, quem tem papel de instalação **não existe** para o
painel `/app` — não aparece, não é encontrado, não abre por URL — e, se algum caminho novo um dia
contornar a query, a edição é negada por uma segunda camada que não depende dela.

## Contexto

O `/app` já recorta por organização (`whereHas('tenants')`, falha fechado) e já impede conceder
papel de outro painel (`gravarPapeis()`). O que sobrou é o alvo: `TenantsSeeder` vincula o
`master_global` a toda organização, então em toda instalação o `admin_app` abre `/app/{org}/users`,
vê o `admin@example.com` e pode trocar a senha dele. É o furo que a wiki `travas-de-escalada-de-papeis`
deixou fora de escopo — ela travou o papel, não a conta.

## Análise dos Arquivos Existentes

### `app/Models/User.php`

- `isMasterGlobal()` → `temPapelOnde('name', 'master_global', contextoGlobal())`, sobre
  `papeisEmQualquerContexto()` (a relação **sem** o `wherePivot` de team do spatie).
- `contextoGlobal()` devolve `Tenant::CONTEXTO_GLOBAL` com teams ligado, `null` sem.
- É aqui que nasce o predicado `governaAInstalacao()` e o scope que o espelha em query — mesma
  relação, mesmo contexto, mesma definição de "papel de instalação" (`roles.painel` nulo ou ≠ `app`).

### `app/Filament/App/Resources/Users/UserResource.php`

- `getEloquentQuery()`: fecha sem tenant, senão `whereHas('tenants')`. Ganha o scope novo **depois**
  do `whereHas`.
- Já sobrescreve `getDeleteAuthorizationResponse()` / `getDeleteAnyAuthorizationResponse()` com
  `Response::deny($motivo)` — o mesmo padrão serve para a edição.
- `getPages()`: `index`, `create`, `edit`. Sem `view`.

### `vendor/filament/filament/src/Resources/Resource/Concerns/HasAuthorization.php`

- `getEditAuthorizationResponse(Model $record): Response` (`:89`) é o que `canEdit()` (`:149`) e
  `authorizeEdit()` (`:209`) consultam. Sobrescrevê-lo cobre a `EditAction` da tabela e o `mount()`
  da `EditRecord`. **Confirmado (step 5)**: `Resources/Pages/Page.php:314` chama
  `getEditAuthorizationResponse()` direto para a `EditAction` da tabela, e `EditRecord.php:100` usa
  `canEdit()`, que lê a mesma resposta (`HasAuthorization.php:149`).

### `database/seeders/TenantsSeeder.php:32`

- `$admin?->tenants()->syncWithoutDetaching([$tenant->id])` — é o que torna o furo visível em toda
  instalação. **Não muda**: o `master_global` continua vinculado (ele precisa abrir o `/app` da
  organização); o que muda é ele não aparecer para o `admin_app`.

## Autorização

- **Policies**: nenhuma mudança em `UserPolicy`. A regra é **por painel** (no `/admin` o
  `master_global` é editável), e policy é global — mesmo argumento do `getDeleteAuthorizationResponse()`
  existente.
- **Resource**: `UserResource::getEditAuthorizationResponse()` (app) nega quando o alvo governa a
  instalação.
- **Gates / Middleware / Guards**: nenhum.

## Rotas

Nenhuma nova. As do resource (`/app/{tenant}/users`, `/create`, `/{record}/edit`) mudam de alcance,
não de forma.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `ListUsers` (app) | Filament | `/app/{tenant}/users` | lista, busca, filtra; quem governa a instalação não aparece | Não |
| `EditUser` (app) | Filament | `/app/{tenant}/users/{record}/edit` | URL direta para quem governa a instalação → 404 | Não |
| Busca ⌘K, badge do menu | Filament (Spotlight, `BadgeContagemNavegacao`) | topbar do `/app` | quem governa a instalação não é encontrado nem contado | Sim (Spotlight), mas o dado vem de `getEloquentQuery()` |

**Gate de CT-B**: **não passa**. Tudo que se afirma é conteúdo de query e resposta HTTP — listagem
(`assertCanNotSeeTableRecords`), route binding (404), badge (contagem) e a resposta de autorização.
Nada depende de JavaScript executado; o Spotlight só consome `getEloquentQuery()`, que se prova por
`pluck()`. Sem `05`.

**Gate de tela de escrita**: `edit` existe → o `04` precisa de cenário de **gravação por
componente** para usuário comum (regressão de RQ-01) e de **recusa** para quem governa a instalação.

## Variáveis de Ambiente · Eventos · Jobs

Nenhum.

## Impacto em Features Existentes

- **`tests/Tenancy/AdminDaOrganizacaoTest.php`** — as fixtures (`ana`, `beto`, `carla`) não têm papel
  de instalação; nada muda. Regressão obrigatória mesmo assim.
- **`duasOrganizacoes()`** (`tests/Pest.php`) — o usuário tem `admin` global; é usado pelos testes de
  identidade visual, não de listagem. Sem impacto.
- **Badge de contagem** (`BadgeContagemNavegacao`) e **busca ⌘K** — passam por `getEloquentQuery()`;
  o número e os resultados **diminuem** onde há alguém de instalação vinculado. É o efeito pedido.
- **O `master_global` no `/app`** — continua abrindo o painel de qualquer organização (`getTenants()`
  devolve todas); só deixa de aparecer na lista de usuários dela. Ele se administra no `/admin`.
- **`EscopoFailClosedTest`** — a query continua fechando sem tenant (o scope novo é `AND`).

## Rollback

`git revert`. Sem migration, sem dado.

## Dependências

Nenhuma.

## Riscos

- **Predicado divergir de `isMasterGlobal()`** — duas definições de "governa a instalação" que
  envelhecem separadas. Mitigação: o predicado e o scope são escritos **uma vez**, no model, sobre a
  mesma relação e o mesmo contexto; CT compara `governaAInstalacao()` com o scope sobre a mesma
  fixture (M1 do `04`).
- **`whereDoesntHave` sobre relação morph com pivot de team** — o `wherePivot()` não existe no
  `Builder` do closure (a lição de `temPapelOnde()`); a coluna de team precisa ser qualificada
  (`Config::modelHasRolesTable().'.'.Config::teamForeignKey()`). Mitigação: CT com fixture em que o
  mesmo usuário tem `admin` **só dentro da organização** — deve continuar visível.
- **Barreira de escrita que só o Filament chama** — a wiki das travas mediu que `canDelete()` não
  era consultado; `canEdit()` pode ter o mesmo destino. Mitigação: sobrescrever a **resposta**
  (`getEditAuthorizationResponse`), que é o que os dois caminhos lêem, e confirmar no vendor (step 5).

## Channel de Log da Feature

`autenticacao` — **existe** e é o channel das barreiras desta área (`UserResource::getEloquentQuery()`,
`gravarPapeis()`, `User::canAccessPanel()` já logam nele). Não se cria channel novo para uma
barreira a mais na mesma área.

## Estrutura de Implementação

### 1. `User::governaAInstalacao()` e o scope que o espelha (RQ-02, RQ-03)

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Models/User.php`, ao lado de `isMasterGlobal()`
- **Definição** (uma só, no docblock): governa a instalação quem tem, **no contexto global**, papel
  cujo `roles.painel` é nulo ou diferente de `app`. Cobre `master_global` (nulo), `admin`, `infra` e
  qualquer papel futuro de painel sem tenancy.

  ```php
  /** Tem papel que governa a instalação (`master_global`, `admin`, `infra`…) no contexto global? */
  public function governaAInstalacao(): bool
  {
      return $this->papeisQueGovernamAInstalacao()->exists();
  }

  /**
   * Os usuários que NÃO governam a instalação — o recorte do `/app`.
   *
   * @param  Builder<User>  $query
   * @return Builder<User>
   */
  public function scopeQueNaoGovernamAInstalacao(Builder $query): Builder
  {
      return $query->whereDoesntHave('papeisEmQualquerContexto', function (Builder $papeis): void {
          $this->restringirAosPapeisDeInstalacao($papeis);
      });
  }
  ```

  com um helper privado que aplica **as mesmas** duas condições nos dois lugares (relação para o
  predicado, closure para o scope): `where(fn ($q) => $q->whereNull('painel')->orWhere('painel', '!=', 'app'))`
  e, quando `contextoGlobal() !== null`, `where(Config::modelHasRolesTable().'.'.Config::teamForeignKey(), Tenant::CONTEXTO_GLOBAL)`.
  A coluna **qualificada** e não `wherePivot()`: dentro do `whereDoesntHave` o closure recebe o
  `Eloquent\Builder` do papel, onde `wherePivot()` não existe (docblock de `temPapelOnde()`).
- **Logs**: nenhum — predicado puro, sem decisão de fluxo. Quem loga é quem o consome.

### 2. O recorte da query do `/app` (RQ-02)

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/App/Resources/Users/UserResource.php`, `getEloquentQuery()`
- Acrescentar `->queNaoGovernamAInstalacao()` ao ramo com tenant. Docblock ganha um parágrafo: por
  que aqui (os quatro consumidores) e por que não basta (ver passo 3).
- **Logs**: nenhum novo — o `warning` de "sem organização corrente" continua como está. Registro
  escondido da lista não é evento: é o estado normal de toda organização com o `master_global`
  vinculado, e um log por request de listagem seria ruído no channel.

### 3. A recusa de edição, independente da query (RQ-03)

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/App/Resources/Users/UserResource.php`
- Sobrescrever, no padrão do `getDeleteAuthorizationResponse()` existente:

  ```php
  private const MOTIVO_DA_NEGACAO_DE_INSTALACAO = 'Quem governa a instalação não se edita a partir de uma organização.';

  public static function getEditAuthorizationResponse(Model $record): Response
  {
      if ($record instanceof User && $record->governaAInstalacao()) {
          Log::channel('autenticacao')->warning(
              "[UserResource@getEditAuthorizationResponse] Edição de quem governa a instalação recusada no painel app | alvo: {$record->id}",
              [
                  'alvo_id'     => $record->id,
                  'executor_id' => Auth::id(),
                  'tenant_id'   => Filament::getTenant()?->getKey(),
                  'painel'      => 'app',
                  'motivo'      => 'alvo_governa_a_instalacao',
              ],
          );

          return Response::deny(self::MOTIVO_DA_NEGACAO_DE_INSTALACAO);
      }

      return parent::getEditAuthorizationResponse($record);
  }
  ```

  Por que a segunda camada, se a query já esconde: a query é **falha de um só ponto** — um
  `EditUser::mount()` chamado com um model resolvido por outro caminho, um `resolveRecord()`
  sobrescrito, uma action nova que receba `User` de fora da tabela. A regra do kit já é essa para a
  exclusão; a edição ganha o mesmo tratamento. **Só `edit`**: não há página `view` no resource, e
  `create` cria alguém novo, que nasce sem papel de instalação (a trava de papel já garante).
- **Logs**: o `warning` acima. É o único ponto de decisão de fluxo desta feature, e só dispara quando
  a primeira camada foi contornada — por isso `warning`, não `info`.

### 4. Testes (RQ-01 a RQ-04)

> Skills: `pest-testing`. Especificação em `04-casos-de-teste.md`.

- **Path**: `tests/Tenancy/FronteiraDoAdminAppTest.php` (novo). Só `tests/Tenancy`: `admin_app` não
  existe sem tenancy (`.ai/rules/testes.md`).
- Regressão: `vendor/bin/pest --no-tia tests/Tenancy/AdminDaOrganizacaoTest.php tests/Tenancy/EscopoFailClosedTest.php`.

### 5. CHANGELOG e documentação

- `CHANGELOG.md` → `[Unreleased]` → `### Segurança`: o que o `admin_app` alcançava, o que passa a
  não alcançar, e a consequência para quem governa a instalação (some da lista do `/app`).
- `docs/pt/recursos/multi-tenancy.md` e `docs/en/recursos/multi-tenancy.md`: uma frase na
  descrição do `admin_app`, onde as barreiras dele já são listadas. Confirmar o trecho no step 5.

## Filosofia de Implementação

> **Ponytail em `full`.**
> 1. **Reutilizar**: `papeisEmQualquerContexto()`, `contextoGlobal()`, o padrão `Response::deny()`
>    já em uso no mesmo arquivo, o channel `autenticacao`.
> 2. **Um predicado, duas formas** (bool e scope) escritas sobre o **mesmo** helper de condições —
>    não duas definições.
> 3. **Nada de policy nova, nada de middleware, nada de coluna**: o dado que decide já existe
>    (`roles.painel` + `model_has_roles.team_id`).
> 4. **Sem `getViewAuthorizationResponse`**: não há página `view`. Atalhos com `ponytail:` comment.
>
> **Caveman `full`** na conversa; wiki, código, commits e PR em prosa normal.

## Testes

> Ver `04-casos-de-teste.md`. Sem `05`: o gate de CT-B não passa (ver `## Superfície de UI`).

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/pest --no-tia tests/Tenancy/FronteiraDoAdminAppTest.php --compact`
- [ ] `vendor/bin/pest --no-tia tests/Tenancy/AdminDaOrganizacaoTest.php tests/Tenancy/EscopoFailClosedTest.php --compact`
- [ ] `vendor/bin/pest --no-tia --parallel --testsuite=Tenancy --compact` (a suíte inteira do modo multi-tenant)
- [ ] Numa instalação com tenancy (`TESTES KIT/v0223-tenancy` ou nova): entrar como `admin_app`,
      abrir `/app/{org}/users` — o `admin@example.com` não aparece; URL direta do `edit` dele → 404

## Commits

- `🔒 feat(app): quem governa a instalação some do /app — o admin_app não vê nem edita master_global, admin e infra`
- `✅ test(tenancy): a fronteira do admin_app — listagem, busca, URL direta e recusa de edição`
- `📝 docs(wiki): feat/admin-app-nao-alcanca-master-global`

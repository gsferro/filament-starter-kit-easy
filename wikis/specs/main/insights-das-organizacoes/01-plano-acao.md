# Plano de Ação — Insights das organizações no `/admin`

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: não se aplica
- **Motivo**: superfície nova (widgets em páginas de Resource) + coluna nova em tabela de vendor
- **Toca infra compartilhada?**: **sim** — a migration altera `authentication_log`, tabela que o
  painel `/infra` já consome em `AutenticacaoStats`, `UltimosAcessos` e no
  `AuthenticationLogResource` do `tapp/filament-authentication-log`.

> Marcar "sim" **força regressão** no quality gate mesmo com o tipo `nova`: os CT existentes que
> tocam `authentication_log` (`tests/Kit/PermissoesDeWidgetsTest.php`, `tests/Kit/PaginasInfraTest.php`)
> precisam continuar verdes com a coluna nova.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Acessos por painel | 1, 2, 6 | coluna `painel` + carimbo na criação do registro + widget `AcessosPorPainel` |
| RQ-02 | Acessos por organização | 5 | derivado do vínculo `tenant_user`, sem coluna nova — ver ADR-02 |
| RQ-03 | Widgets na tela de cadastro das organizações | 4, 8, 9 | `ListTenants` (agregado) + `ViewTenant` (registro) |
| RQ-04 | Usuários únicos por organização | 5 | `UsuariosUnicosPorOrganizacao` |
| RQ-05 | Contagem sai do pacote de logs de acesso | 5, 6, 10 | fonte é `Rappasoft\...\AuthenticationLog` em todos |
| RQ-06 | Timeline dos últimos dados atualizados | 7 | `AtualizacoesDasOrganizacoes` sobre `audits` |
| RQ-07 | Outros insights que enriqueçam a tela | 3, 10 | `OrganizacoesStats` (agregado) e `OrganizacaoUltimosAcessos` (registro) |

## Objetivo

Dar ao administrador da instalação, na própria tela de organizações do `/admin`, a leitura de
quem está usando o sistema: quantos usuários distintos de cada organização entraram, por qual
painel os acessos passaram, e o que mudou recentemente no cadastro. Hoje esse dado existe cru em
`/infra` (log de autenticação) e em `audits`, mas nunca cruzado com a organização — quem administra
as organizações não tem como responder "esta organização está viva?".

A entrega tem duas metades. A primeira é de **dado**: a tabela de log de acesso passa a saber por
qual painel cada login entrou, coisa que hoje ela não registra. A segunda é de **apresentação**:
seis widgets, quatro na listagem e dois na tela do registro.

## Contexto

- `authentication_log` (pacote `rappasoft/laravel-authentication-log`, exposto pelo
  `tapp/filament-authentication-log`) guarda um `morphTo` para `User` e mais nada sobre origem —
  não há painel, não há organização.
- O vínculo usuário ↔ organização é a pivot `tenant_user` (`tenant_id`, `user_id`, única
  composta). É por ela que se sabe de quem é cada acesso.
- Os widgets do kit hoje vivem só em dashboard: `discoverWidgets()` do `AdminPanelProvider`
  registra `app/Filament/Admin/Widgets/` inteiro, e o `Dashboard` padrão renderiza **todos** os
  widgets registrados no painel. Widget colocado ali apareceria no dashboard, que não é o pedido.
- `Filament\Resources\Pages\Concerns\InteractsWithRecord::getWidgetData()` já entrega
  `['record' => $this->getRecord()]` aos widgets de cabeçalho de uma `ViewRecord` — é o que torna
  os widgets do passo 9 possíveis sem parâmetro manual.

## Análise dos Arquivos Existentes

### `app/Providers/KitServiceProvider.php`

Já registra hooks de model no boot (`Export::created(...)`, linha 294) e listeners de evento
(`Event::listen(...)`, linhas 235-294). É onde entra o carimbo do painel — mesmo lugar, mesmo
padrão, sem provider novo.

### `app/Filament/Infra/Widgets/AutenticacaoStats.php` e `UltimosAcessos.php`

São o precedente de como o kit lê `authentication_log`: `Schema::hasTable(config('authentication-log.table_name'))`
dentro de `fonteDeDadosDisponivel()`, `->with('authenticatable')` para evitar N+1 no morph.
Os widgets novos copiam essa forma.

### `app/Filament/Infra/Widgets/AuditoriaRecente.php`

Precedente de `TimelineWidget` com `TimelineEvent`. O widget do passo 7 é o mesmo desenho, com a
query recortada em `Tenant`.

### `app/Filament/Admin/Resources/Tenants/Pages/ListTenants.php` e `ViewTenant.php`

Hoje só declaram `getHeaderActions()`. Ganham `getHeaderWidgets()` e
`getHeaderWidgetsColumns()`.

### `app/Filament/Concerns/ExigePermissaoDoWidget.php`

**Não serve para estes widgets** — ver ADR-03. Ele resolve `View:{Widget}` via
`HasWidgetShield`, e essa permission só existe no banco para widget que o `discoverWidgets()`
registrou no painel. Os widgets desta feature ficam fora do discovery de propósito.

## Autorização

- **Policies**: nenhuma nova. A barreira é `TenantResource::canAccess()`, que já é
  `config('kit.tenancy.enabled') && parent::canAccess()` — ou seja, `ViewAny:Tenant` do Shield
  mais o kill-switch da tenancy.
- **Gates**: nenhum novo.
- **Middleware**: nenhum novo.
- **Widgets**: cada widget desta feature declara
  `public static function canView(): bool { return TenantResource::canAccess(); }`.
  Sem permission própria — ver ADR-03 para o porquê e para o que isso custa.

## Rotas

Nenhuma rota nova. Os widgets vivem nas rotas já existentes do `TenantResource`:

| Método | URI | Name | Middleware |
|--------|-----|------|------------|
| GET | `/admin/organizacoes` | `filament.admin.resources.organizacoes.index` | herdado do painel |
| GET | `/admin/organizacoes/{record}` | `filament.admin.resources.organizacoes.view` | herdado do painel |

> O segmento da URL sai de `config('kit.tenancy.slug')` e pode não ser `organizacoes`.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `OrganizacoesStats` | Filament (StatsOverviewWidget) | `/admin/organizacoes` | leitura | Não |
| `UsuariosUnicosPorOrganizacao` | Filament (BreakdownWidget) | `/admin/organizacoes` | leitura | Não |
| `AcessosPorPainel` | Filament (BreakdownWidget) | `/admin/organizacoes` | leitura | Não |
| `AtualizacoesDasOrganizacoes` | Filament (TimelineWidget) | `/admin/organizacoes` | leitura | Não |
| `OrganizacaoStats` | Filament (StatsOverviewWidget) | `/admin/organizacoes/{record}` | leitura | Não |
| `OrganizacaoUltimosAcessos` | Filament (RecentItemsWidget) | `/admin/organizacoes/{record}` | leitura | Não |

**Gate de CT-B**: nenhum destes widgets afirma sobre algo que só o navegador prova — são todos
leitura server-rendered, sem JavaScript próprio, sem tema, sem acessibilidade em jogo além do que
o Filament já entrega. **Não haverá `05-casos-de-teste-browser.md`.** O motivo fica registrado no
`04`.

**Gate de tela de escrita**: esta feature não acrescenta rota `create`/`edit`. As existentes
(`CreateTenant`, `EditTenant`) não são tocadas e já têm cobertura de gravação.

## Variáveis de Ambiente

Nenhuma nova. A janela das métricas é constante no código (30 dias) — ver ADR-05.

## Eventos / Listeners / Observers

- **Eventos emitidos**: nenhum.
- **Listeners**: nenhum listener de evento novo.
- **Observers**: um hook `creating` em `Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog`,
  registrado no `boot()` do `KitServiceProvider`. Ver ADR-01 para por que é hook de model e não
  substituição do listener do pacote.

## Jobs / Queues

Nenhum. Todas as consultas são síncronas e agregadas por `COUNT`/`GROUP BY`.

## Impacto em Features Existentes

- **`/infra` → `AutenticacaoStats`, `UltimosAcessos`**: leem a mesma tabela. Coluna nova nullable
  não altera o que consultam, mas os dois precisam continuar verdes na regressão.
- **`tapp/filament-authentication-log` → `AuthenticationLogResource`**: monta a tabela a partir de
  colunas declaradas no próprio Resource, não de `SELECT *`. Coluna extra é ignorada.
- **`tests/Kit/PermissoesDeWidgetsTest.php`**: insere linha em `authentication_log` via
  `DB::table(...)->insert(...)` — insert direto **não dispara o hook `creating` do Eloquent**, então
  a coluna `painel` fica nula ali e o teste segue válido. Registrado porque parece frágil e não é.
- **`ListTenants` / `ViewTenant`**: ganham consultas no carregamento. Ver Riscos.
- **`PapeisSeeder` / `ShieldPermissionsSeeder`**: **não** são tocados — não há permission nova.

## Rollback

- **Migration down**: `dropColumn('painel')` na tabela de `config('authentication-log.table_name')`,
  guardado por `Schema::hasColumn()`. SQLite recria a tabela; por isso o `down()` é declarado e
  testado, não presumido.
- **Feature flag**: `config('kit.tenancy.enabled')` já desliga a tela inteira — sem tenancy, o
  `TenantResource` não é alcançável e nenhum widget renderiza.
- **Reversão de dados**: nenhuma. A coluna é aditiva e nullable; nada existente é reescrito.

## Dependências

Nenhuma nova. Tudo já está instalado:

- **Composer**: `rappasoft/laravel-authentication-log` (via `tapp/filament-authentication-log ^5.0`),
  `laboiteacode/filament-dashboard-widgets`, `gsferro/filament-stat-plus-easy`, `owen-it/laravel-auditing`.
- **NPM**: nada.

## Riscos

- **Consulta pesada na listagem**: `UsuariosUnicosPorOrganizacao` faz join de três tabelas com
  `COUNT(DISTINCT)`. Com muitas organizações e log grande, o custo é real.
  **Mitigação**: janela fixa de 30 dias no `where login_at >=`, `->limit()` no breakdown, e a
  coluna `painel` nasce com índice. Se ainda assim doer, o próximo passo é cache — mas medir antes
  (ADR-05).
- **Coluna nula em log histórico**: todo login anterior ao deploy fica sem painel.
  **Mitigação**: o widget agrupa os nulos numa fatia própria rotulada "antes do registro por
  painel", em vez de omiti-los — omitir faria o total do widget divergir do total real de acessos,
  sem nada avisar.
- **Nome da coluna colidir com o pacote**: uma versão futura do `rappasoft` pode acrescentar
  `painel`/`panel`. **Mitigação**: a migration usa `Schema::hasColumn()` como guarda e o nome é
  português (`painel`), alinhado com o resto do schema do kit e improvável num pacote inglês.

## Channel de Log da Feature

### Verificação de Channel Existente

`config/logging.php` já tem, entre outros, `autenticacao` (linha 132) e `tenancy` (linha 123).
`Grep` confirmou que `autenticacao` é o channel usado pelo kit para tudo que é entrada no sistema
(`UserResource::getEloquentQuery()` falhando fechado, fluxo de convite, aprovação).

### Decisão

**Nenhum channel novo, e nenhum log novo.** Esta feature não emite uma única linha de log, e as
duas metades da decisão têm o mesmo motivo.

> **Os widgets não logam.** Log de render produziria uma linha por carregamento de tela, por
> widget, sem nenhuma pergunta que essa linha responda.
>
> **O carimbo do painel também não loga**, e este era um log previsto que a auditoria Ponytail
> cortou. Ele rodaria a **cada login**, no channel `autenticacao` — que é onde o kit registra
> eventos de segurança de verdade. O evento do acesso já é gravado pelo pacote em
> `authentication_log`, e o painel é uma coluna **daquela mesma linha**: quem quiser saber o painel
> lê o registro, não o log. Log que duplica um registro que já existe é ruído no lugar em que
> ruído custa mais caro.

O que se quer auditar já está em `audits` (quem mexeu no cadastro) e em `authentication_log`
(quem entrou, de onde e — agora — por qual painel).

## Estrutura de Implementação

### 1. Migration: coluna `painel` em `authentication_log`

> Skills: `laravel-best-practices`

- **Path**: `database/migrations/2026_09_04_000001_add_painel_to_authentication_log_table.php`
- Comando: `php artisan make:migration add_painel_to_authentication_log_table --no-interaction`
- Tabela vem de `config('authentication-log.table_name', 'authentication_log')` — nunca literal,
  porque o pacote permite renomear.
- `up()`:
  ```php
  $tabela = (string) config('authentication-log.table_name', 'authentication_log');

  if (! Schema::hasTable($tabela) || Schema::hasColumn($tabela, 'painel')) {
      return;
  }

  Schema::table($tabela, function (Blueprint $table): void {
      $table->string('painel')->nullable()->index()->after('user_agent');
  });
  ```
- `down()`: guarda simétrica com `Schema::hasColumn()` e `dropColumn('painel')`.
- **Sem log**: migration não loga.

### 2. Carimbo do painel no `KitServiceProvider`

> Skills: `laravel-best-practices`

- **Path**: `app/Providers/KitServiceProvider.php` — método novo `registrarPainelNoLogDeAcesso()`,
  chamado do `boot()` junto dos outros registros.
- Lógica:
  ```php
  AuthenticationLog::creating(function (AuthenticationLog $acesso): void {
      if (filled($acesso->getAttribute('painel'))) {
          return;
      }

      $painel = rescue(fn (): ?string => Filament::getCurrentPanel()?->getId(), null, report: false);

      $acesso->setAttribute('painel', $painel);
  });
  ```
- **Guarda de coluna ausente**: o hook precisa ser inerte numa instalação que ainda não rodou a
  migration. `setAttribute` numa coluna inexistente só estoura no `INSERT`, e estourar ali quebraria
  **o login**. Envolver o registro do hook em
  `rescue(fn () => Schema::hasColumn($tabela, 'painel'), false, report: false)`, no mesmo padrão de
  `configureSettingsDoKit()`.
- **Sem log** — e a ausência é decisão, registrada na auditoria Ponytail do `03-progresso.md`.
  O hook roda a **cada login**. Uma linha `debug` por login no channel `autenticacao`, que é onde
  o kit registra eventos de segurança de verdade, é ruído puro: o evento do acesso já é gravado
  pelo pacote, e o painel é uma coluna **dessa mesma linha**. Quem quiser saber o painel lê o
  registro, não o log.

### 3. Widget `OrganizacoesStats` — visão geral na listagem

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/Tenants/Widgets/OrganizacoesStats.php`
- Estende `Filament\Widgets\StatsOverviewWidget`, usa `Gsferro\FilamentStatPlusEasy\Widgets\StatPlus`
  (mesmo padrão de `AutenticacaoStats`).
- `protected int|string|array $columnSpan = 'full';`
- `canView()`: `TenantResource::canAccess()`.
- Stats:
  1. **Organizações ativas** — `Tenant::query()->where('ativo', true)->count()`, com descrição
     dizendo o total incluindo inativas.
  2. **Usuários vinculados** — `DB::table('tenant_user')->distinct()->count('user_id')`.
  3. **Usuários ativos em 30 dias** — usuários distintos com login bem-sucedido na janela **e**
     com pelo menos um vínculo.
  4. **Taxa de ativação** — (3) ÷ (2), em porcentagem. É o insight de RQ-07: diz quanto do cadastro
     virou uso.
- **Sem log.**

### 4. `ListTenants` passa a declarar os widgets de cabeçalho

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/Tenants/Pages/ListTenants.php`
- Acrescentar:
  ```php
  protected function getHeaderWidgets(): array
  {
      return [
          OrganizacoesStats::class,
          UsuariosUnicosPorOrganizacao::class,
          AcessosPorPainel::class,
          AtualizacoesDasOrganizacoes::class,
      ];
  }

  public function getHeaderWidgetsColumns(): int|array
  {
      return 2;
  }
  ```
- `Page::getWidgetsSchemaComponents()` já filtra por `canView()`, então widget negado some sem
  deixar buraco no grid.
- **Sem log.**

### 5. Widget `UsuariosUnicosPorOrganizacao` — o pedido central (RQ-02, RQ-04, RQ-05)

> Skills: `laravel-best-practices`, `eloquent-best-practices`

- **Path**: `app/Filament/Admin/Resources/Tenants/Widgets/UsuariosUnicosPorOrganizacao.php`
- Estende `LaBoiteACode\FilamentDashboardWidgets\Widgets\BreakdownWidget`, itens são
  `BreakdownItem`.
- `columnSpan = 1`, heading "Usuários únicos por organização", descrição "Pessoas distintas que
  entraram nos últimos 30 dias".
- Query — uma só, agregada, sem N+1:
  ```php
  Tenant::query()
      ->select('tenants.id', 'tenants.nome')
      ->selectRaw('COUNT(DISTINCT tenant_user.user_id) as usuarios')
      ->join('tenant_user', 'tenant_user.tenant_id', '=', 'tenants.id')
      ->join($tabelaDeLog, function (JoinClause $join) use ($tabelaDeLog, $desde): void {
          $join->on($tabelaDeLog.'.authenticatable_id', '=', 'tenant_user.user_id')
              ->where($tabelaDeLog.'.authenticatable_type', '=', (new User)->getMorphClass())
              ->where($tabelaDeLog.'.login_successful', '=', true)
              ->where($tabelaDeLog.'.login_at', '>=', $desde);
      })
      ->groupBy('tenants.id', 'tenants.nome')
      ->orderByDesc('usuarios')
      ->limit(10)
      ->get()
  ```
- Cada item recebe `->url(TenantResource::getUrl('view', ['record' => $id]))` — o breakdown vira
  navegação, não só número.
- `fonteDeDadosDisponivel()` equivalente: `canView()` também confere
  `Schema::hasTable($tabelaDeLog)`, porque o pacote é opcional numa instalação derivada.
- **Sem log.**

### 6. Widget `AcessosPorPainel` (RQ-01)

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/Tenants/Widgets/AcessosPorPainel.php`
- `BreakdownWidget`, `columnSpan = 1`.
- Query: `AuthenticationLog::query()->where('login_successful', true)->where('login_at', '>=', $desde)->groupBy('painel')->selectRaw('painel, COUNT(*) as acessos')`.
- Rótulo de cada fatia sai de `Filament::getPanel($id, isStrict: false)?->getId()`. O
  `isStrict: false` **não é opcional**: a assinatura é
  `getPanel(?string $id = null, bool $isStrict = true)` e o default **lança** quando o id não
  existe — id de painel que sobrou no log depois de o painel ser removido derrubaria o widget.
  Painel nulo vira a fatia
  **"antes do registro por painel"** em cinza, com descrição dizendo que o carimbo começou na
  data da migration. É o que impede o widget de mentir sobre o histórico.
- `canView()`: `TenantResource::canAccess()` **e** `Schema::hasColumn($tabelaDeLog, 'painel')` —
  sem a migration, o widget não aparece em vez de estourar.
- **Sem log.**

### 7. Widget `AtualizacoesDasOrganizacoes` (RQ-06)

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/Tenants/Widgets/AtualizacoesDasOrganizacoes.php`
- **Estende `App\Filament\Infra\Widgets\AuditoriaRecente`, não `TimelineWidget` direto.** Este
  widget é aquele mais um `where` — `getEvents()`, o agrupamento por dia, os rótulos "Hoje"/"Ontem",
  a resolução dos nomes dos autores numa consulta só, os ícones e as cores por evento são todos
  idênticos. Reescrevê-los seria copiar ~120 linhas para acrescentar uma cláusula.
- **O passo 7 tem duas metades, nesta ordem:**
  1. abrir um seam em `AuditoriaRecente`: extrair a montagem da consulta para
     `protected function consulta(): Builder`, sem mudar comportamento nenhum;
  2. a classe nova sobrescreve `consulta()` com o filtro
     `->where('auditable_type', (new Tenant)->getMorphClass())`, mais `getHeading()`,
     `getHeadingDescription()`, `$sort`, `$columnSpan` e `canView()`.
- **Sem `->with('user')`**, e isso é decisão herdada, não esquecimento: `AuditoriaRecente:113-119`
  documenta que o morph do autor dispara uma consulta por **tipo** de autor no lote e traz o
  registro inteiro para usar só o nome.
- `canView()`: `TenantResource::canAccess()` **e** `Schema::hasTable(config('audit.drivers.database.table', 'audits'))`.
  Sobrescreve o `ExigePermissaoDoWidget` herdado — o pai é widget de painel e tem permission
  própria; este não. Ver ADR-03.
- **Consequência de herdar do widget de `/infra`**: mudar `AuditoriaRecente` passa a mexer nos dois.
  É o custo aceito em troca de não manter duas cópias da mesma timeline; o seam do item 1 é
  justamente o que torna a herança um ponto só de contato.
- **Sem log.**

### 8. `ViewTenant` passa a declarar os widgets do registro

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/Tenants/Pages/ViewTenant.php`
- `getHeaderWidgets()` devolvendo `OrganizacaoStats::class` e `OrganizacaoUltimosAcessos::class`;
  `getHeaderWidgetsColumns()` devolvendo 2.
- **Sem log.**

### 9. Widget `OrganizacaoStats` — métricas do registro aberto (RQ-03, RQ-07)

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/Tenants/Widgets/OrganizacaoStats.php`
- `StatsOverviewWidget` com `public ?Tenant $record = null;` — o Filament injeta via
  `InteractsWithRecord::getWidgetData()`.
- Stats do registro: usuários vinculados; usuários distintos com acesso em 30 dias; data do
  último acesso de alguém da organização (`diffForHumans`).
- **Sem stat de "situação"**: o campo `ativo` já está no infolist do mesmo registro, dois blocos
  abaixo na mesma tela.
- Guarda: `$this->record` nulo devolve array vazio em vez de estourar.
- **Sem log.**

### 10. Widget `OrganizacaoUltimosAcessos` (RQ-05, RQ-07)

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/Tenants/Widgets/OrganizacaoUltimosAcessos.php`
- `RecentItemsWidget` com `public ?Tenant $record = null;` — mesmo desenho de
  `App\Filament\Infra\Widgets\UltimosAcessos`, recortado nos usuários da organização.
- Query: `AuthenticationLog` `whereIn('authenticatable_id', <ids da pivot>)` com o morph de `User`,
  `->with('authenticatable')`, `orderByDesc('login_at')->limit(5)`.
- Cada item mostra nome, IP/dispositivo, painel (quando carimbado) e badge sucesso/falha.
- **Sem log.**

> **Nenhum seeder de permissão roda nesta feature.** Não há entidade nova descoberta pelo Shield
> (ADR-03), então `ShieldPermissionsSeeder`/`PapeisSeeder` seriam no-op. Registrado aqui para a
> próxima pessoa não "corrigir" a ausência.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> Cada passo deve aplicar a escada de simplicidade:
> 1. Reutilizar código existente antes de criar novo — os widgets de `/infra` são o molde
> 2. Usar stdlib do PHP/Laravel antes de código custom
> 3. Usar features nativas antes de dependências — `getHeaderWidgets()` é nativo do Filament
> 4. Uma linha quando possível
> 5. Mínimo código que funciona
>
> Atalhos deliberados devem ser marcados com `ponytail:` comment.
> Após implementação, rodar `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em modo `ultra`** na comunicação agent ↔ usuário.
> Arquivos wiki (00-06) são boundary do Caveman — escrever em prosa normal.
> Código, commits e PRs também são boundary do Caveman.

## Mapeamentos

| Conceito do requisito | Onde vive no schema | Como é lido |
|---|---|---|
| "acessos" | `authentication_log.login_at` + `login_successful = true` | contagem de linhas |
| "painel" | `authentication_log.painel` (**novo**) | carimbado no `creating` |
| "tenant" / "organização" | `tenants` + pivot `tenant_user` | join pela `user_id` |
| "usuários exclusivos" | `COUNT(DISTINCT tenant_user.user_id)` | ver ADR-02 |
| "dados atualizados" | `audits` com `auditable_type = Tenant` | `owen-it/laravel-auditing` |

## Testes

> Ver `04-casos-de-teste.md` para a especificação completa dos cenários de backend.
> **Sem `05-casos-de-teste-browser.md`** — o motivo está registrado no `04`, seção `## Sem CT-B`.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --compact --filter=InsightsDasOrganizacoes`
- [ ] `php artisan test --compact tests/Kit/PermissoesDeWidgetsTest.php tests/Kit/PaginasInfraTest.php` (regressão da infra compartilhada)
- [ ] `vendor/bin/pest --parallel --tia` — confirma o que mais o diff afetou
- [ ] `php artisan migrate:rollback --step=1` e `migrate` de novo — prova o `down()`

## Commits

- `:sparkles: feat(organizacoes): widgets de acesso e atualizações na tela de organizações`
- `:memo: docs(wiki): wiki da feature insights-das-organizacoes`

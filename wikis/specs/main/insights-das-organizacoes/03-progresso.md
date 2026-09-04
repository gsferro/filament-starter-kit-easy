# Progresso — Insights das organizações no `/admin`

Wiki criada em 2026-09-04. Implementação ainda não iniciada.

## 1. Migration: coluna `painel` em `authentication_log`

- [ ] `make:migration add_painel_to_authentication_log_table --no-interaction`
- [ ] `up()` guardado por `Schema::hasTable()` + `Schema::hasColumn()`, tabela vinda de `config('authentication-log.table_name')`
- [ ] `down()` simétrico, com guarda própria
- [ ] `php artisan migrate` aplicado

## 2. Carimbo do painel no `KitServiceProvider`

- [ ] Método `registrarPainelNoLogDeAcesso()` criado e chamado do `boot()`
- [ ] Hook `AuthenticationLog::creating(...)` com guarda de valor já preenchido
- [ ] Registro do hook envolvido em `rescue(fn () => Schema::hasColumn(...), false, report: false)`
- [ ] **Sem log** — corte #1 da auditoria Ponytail

## 3. Widget `OrganizacoesStats`

- [ ] Classe criada em `app/Filament/Admin/Resources/Tenants/Widgets/`
- [ ] Quatro stats: organizações ativas, usuários vinculados, usuários ativos em 30 dias, taxa de ativação
- [ ] `canView()` delegando a `TenantResource::canAccess()`

## 4. `ListTenants` declara os widgets de cabeçalho

- [ ] `getHeaderWidgets()` com os quatro widgets agregados
- [ ] `getHeaderWidgetsColumns()` devolvendo 2

## 5. Widget `UsuariosUnicosPorOrganizacao`

- [ ] Classe criada, estendendo `BreakdownWidget`
- [ ] Consulta única com `COUNT(DISTINCT tenant_user.user_id)` e filtro de morph
- [ ] Item com `->url()` para a tela da organização
- [ ] `canView()` com a barreira + `Schema::hasTable()` da tabela de log

## 6. Widget `AcessosPorPainel`

- [ ] Classe criada, estendendo `BreakdownWidget`
- [ ] Agregação por `painel`, só acessos bem-sucedidos, dentro da janela
- [ ] Fatia própria para os acessos sem painel, em cinza, com descrição
- [ ] `canView()` com a barreira + `Schema::hasColumn(..., 'painel')`

## 7. Widget `AtualizacoesDasOrganizacoes`

- [ ] Seam aberto em `AuditoriaRecente`: `protected function consulta(): Builder`, sem mudança de comportamento
- [ ] `tests/Kit`/`tests/Infra` do widget de auditoria continuam verdes depois do seam
- [ ] Classe criada **estendendo `AuditoriaRecente`**, sobrescrevendo só `consulta()`, heading, sort, columnSpan e `canView()`
- [ ] `canView()` com a barreira + `Schema::hasTable()` da tabela de auditoria (sobrescreve o `ExigePermissaoDoWidget` herdado)

## 8. `ViewTenant` declara os widgets do registro

- [ ] `getHeaderWidgets()` com os dois widgets do registro
- [ ] `getHeaderWidgetsColumns()` devolvendo 2

## 9. Widget `OrganizacaoStats`

- [ ] Classe criada com `public ?Tenant $record = null;`
- [ ] Três stats do registro, com guarda para `$record` nulo (o stat de "situação" foi cortado — corte #4)

## 10. Widget `OrganizacaoUltimosAcessos`

- [ ] Classe criada, estendendo `RecentItemsWidget`, com `public ?Tenant $record = null;`
- [ ] Consulta recortada nos usuários da organização, com `->with('authenticatable')`

## Testes

- [ ] `tests/Kit/CarimboDePainelNoAcessoTest.php` — CT-01, CT-03, CT-04, CT-05, CT-17
- [ ] `tests/Tenancy/InsightsDasOrganizacoesTest.php` — CT-06 … CT-16
- [ ] Varredura da pasta de widgets alimentando CT-14 e CT-17 (lista derivada, não escrita à mão)

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --compact tests/Kit/CarimboDePainelNoAcessoTest.php tests/Tenancy/InsightsDasOrganizacoesTest.php`
- [ ] Regressão da infra compartilhada: `php artisan test --compact tests/Kit/PermissoesDeWidgetsTest.php tests/Kit/PaginasInfraTest.php`
- [ ] `vendor/bin/pest --parallel --tia` — confirma o que mais o diff afetou
- [ ] `pest --mutate` nos dois escopos do `04`
- [ ] `php artisan migrate:rollback --step=1` e `migrate` de novo — prova o `down()`
- [ ] `git commit`

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| passo 7 usaria `Audit::query()->with('user')` | `app/Filament/Infra/Widgets/AuditoriaRecente.php:113-119` **rejeita** o `with('user')` num docblock: o morph do autor dispara uma consulta por **tipo** de autor no lote e traz o registro inteiro para usar só o nome. O widget existente resolve os nomes num helper próprio | passo 7 reescrito: sem `with()`, reaproveitando o helper de `AuditoriaRecente`. Nome do método corrigido de `getItems()` para `getEvents()` |
| passo 6 usaria `Filament::getPanel($id)?->getId()` | `FilamentManager.php:372` é `getPanel(?string $id = null, bool $isStrict = true)` — o default **lança** quando o id não existe. Id de painel removido que sobrou no log derrubaria o widget | passo 6 reescrito com `isStrict: false` e o motivo registrado |
| `canView()` seria método de instância | `Page::getWidgetsSchemaComponents()` (`Page.php:427`) chama `$widget::canView()` **estaticamente** | confirmado: o PRD já dizia `public static function canView(): bool`. Sem correção |
| injeção do `$record` no widget de `ViewTenant` seria manual | `Page.php:431` espalha `...$this->getWidgetData()` nos parâmetros de mount do Livewire, e `InteractsWithRecord::getWidgetData()` devolve `['record' => …]` | confirmado. Sem correção |
| widget em subpasta de `Admin/Widgets/` escaparia do dashboard | `HasComponents::discoverComponents()` (`:515`) usa `$filesystem->allFiles()`, que é **recursivo** | confirmado — é o que sustenta ADR-03. Sem correção |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `delete:` log `debug` a cada login, num channel de auditoria de segurança. O pacote já grava a linha do acesso, e o painel é coluna dela | **sim** | `01` passo 2 e seção *Channel de Log*; CT-02 cortado do `04` |
| 2 | `delete:` CT-02, que existia só para o log do corte #1. M6 migrou para CT-01 como "exatamente um registro criado" | **sim** | `04` R1 e Índice de Cenários |
| 3 | `shrink:` `AtualizacoesDasOrganizacoes` era `AuditoriaRecente` mais um `where`, reescrevendo `getEvents()`, agrupamento por dia, rótulos, autores, ícones e cores | **sim** — vira herança com um seam de consulta | `01` passo 7 |
| 4 | `delete:` stat "situação (`ativo`)" em `OrganizacaoStats`, que repete o campo do próprio infolist na mesma tela | **sim** | `01` passo 9 |
| 5 | `delete:` passo 11 "Verificação e seeders" — não é passo de implementação, já estava em *Verificação Final* | **sim** — virou nota | `01` fim da Estrutura de Implementação |
| 6 | `yagni:` `OrganizacaoUltimosAcessos` é `Infra/UltimosAcessos` com um `whereIn` | **recusada** — o usuário pediu widgets nas **duas** telas; sem ele o `ViewTenant` fica com um widget só. Registrado como o primeiro a cair se o escopo precisar encolher | `01` passo 10 |

Segunda passada não foi executada: os cortes aplicados removeram conteúdo e não introduziram
arquivo novo nem passo novo, então não há superfície nova a auditar.

## Degradações declaradas

- **`search-docs` indisponível.** O MCP `laravel-boost` respondeu `CONNECT_TIMEOUT` durante toda a
  sessão de planejamento. A Documentation API não pôde ser consultada. Toda API citada no PRD foi
  confirmada por **leitura direta do vendor** — `filament/filament`, `bezhansalleh/filament-shield`,
  `rappasoft/laravel-authentication-log`, `laboiteacode/filament-dashboard-widgets` — com
  `arquivo:linha` registrado nas ADRs. Antes de implementar, vale tentar o `search-docs` de novo
  para as partes de Filament 5.

## Blockers

- Nenhum.

## Desvios do Plano

_a preencher após a implementação._

## Notas de Implementação

_a preencher após a implementação._

## Retrospectiva

_a preencher após a implementação._

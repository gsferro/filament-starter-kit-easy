# Progresso — Insights das organizações no `/admin`

Wiki criada em 2026-09-04. **Implementação concluída em 2026-09-04.**

## 1. Migration: coluna `painel` em `authentication_log`

- [x] `make:migration add_painel_to_authentication_log_table --no-interaction`
- [x] `up()` guardado por `Schema::hasTable()` + `Schema::hasColumn()`, tabela vinda de `config('authentication-log.table_name')`
- [x] `down()` simétrico, com guarda própria
- [x] `php artisan migrate` aplicado

## 2. Carimbo do painel no `KitServiceProvider`

- [x] Método `registrarPainelNoLogDeAcesso()` criado e chamado do `boot()`
- [x] Hook `AuthenticationLog::creating(...)` com guarda de valor já preenchido
- [x] Registro do hook envolvido em `rescue(fn () => Schema::hasColumn(...), false, report: false)`
- [x] **Sem log** — corte #1 da auditoria Ponytail

## 3. Widget `OrganizacoesStats`

- [x] Classe criada em `app/Filament/Admin/Resources/Tenants/Widgets/`
- [x] Quatro stats: organizações ativas, usuários vinculados, usuários ativos em 30 dias, taxa de ativação
- [x] `canView()` delegando a `TenantResource::canAccess()`

## 4. `ListTenants` declara os widgets de cabeçalho

- [x] `getHeaderWidgets()` com os quatro widgets agregados
- [x] `getHeaderWidgetsColumns()` devolvendo 2

## 5. Widget `UsuariosUnicosPorOrganizacao`

- [x] Classe criada, estendendo `BreakdownWidget`
- [x] Consulta única com `COUNT(DISTINCT tenant_user.user_id)` e filtro de morph
- [x] Item com `->url()` para a tela da organização
- [x] `canView()` com a barreira + `Schema::hasTable()` da tabela de log

## 6. Widget `AcessosPorPainel`

- [x] Classe criada, estendendo `BreakdownWidget`
- [x] Agregação por `painel`, só acessos bem-sucedidos, dentro da janela
- [x] Fatia própria para os acessos sem painel, em cinza, com descrição
- [x] `canView()` com a barreira + `Schema::hasColumn(..., 'painel')`

## 7. Widget `AtualizacoesDasOrganizacoes`

- [x] Seam aberto em `AuditoriaRecente`: `protected function consulta(): Builder`, sem mudança de comportamento
- [x] `tests/Kit`/`tests/Infra` do widget de auditoria continuam verdes depois do seam
- [x] Classe criada **estendendo `AuditoriaRecente`**, sobrescrevendo só `consulta()`, heading, sort, columnSpan e `canView()`
- [x] `canView()` com a barreira + `Schema::hasTable()` da tabela de auditoria (sobrescreve o `ExigePermissaoDoWidget` herdado)

## 8. `ViewTenant` declara os widgets do registro

- [x] `getHeaderWidgets()` com os dois widgets do registro
- [x] `getHeaderWidgetsColumns()` devolvendo 2

## 9. Widget `OrganizacaoStats`

- [x] Classe criada com `public ?Tenant $record = null;`
- [x] Três stats do registro, com guarda para `$record` nulo (o stat de "situação" foi cortado — corte #4)

## 10. Widget `OrganizacaoUltimosAcessos`

- [x] Classe criada, estendendo `RecentItemsWidget`, com `public ?Tenant $record = null;`
- [x] Consulta recortada nos usuários da organização, com `->with('authenticatable')`

## Testes

- [x] `tests/Kit/CarimboDePainelNoAcessoTest.php` — CT-01, CT-03, CT-04, CT-05, CT-17
- [x] `tests/Tenancy/InsightsDasOrganizacoesTest.php` — CT-06 … CT-16
- [x] Varredura da pasta de widgets alimentando CT-14 e CT-17 (lista derivada, não escrita à mão)

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `php artisan test --compact tests/Kit/CarimboDePainelNoAcessoTest.php tests/Tenancy/InsightsDasOrganizacoesTest.php`
- [x] Regressão da infra compartilhada: `php artisan test --compact tests/Kit/PermissoesDeWidgetsTest.php tests/Kit/PaginasInfraTest.php`
- [x] `vendor/bin/pest --parallel --tia` — confirma o que mais o diff afetou
- [x] `pest --mutate` nos dois escopos do `04`
- [x] `php artisan migrate:rollback --step=1` e `migrate` de novo — prova o `down()`
- [x] `git commit`

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

O primeiro é de **produção** e o PRD estava errado; os outros são de arranjo de teste.

- **A guarda do hook foi para DENTRO do closure, não no registro.** O passo 2 mandava envolver o
  REGISTRO do hook em `rescue(fn () => Schema::hasColumn(...))`. Isso está errado, e o motivo
  estava no docblock do método vizinho: `configureSettingsDoKit()` explica que, com
  `RefreshDatabase`, o `boot()` roda **antes** das migrations — nenhuma tabela existe nesse
  instante. O hook nunca se registraria em teste nenhum e a feature seria inverificável. A
  checagem virou `logDeAcessoTemColunaDePainel()`, memoizada com `once()`, chamada dentro do
  closure.
- **`TenantResource::getUrl('view', …, panel: 'admin')`** — o `panel:` não estava no plano e é
  obrigatório. `Resource::getUrl()` resolve contra o painel CORRENTE, e o widget é montado por
  componente Livewire, não por request de painel. Sem fixar, estoura
  `Route [filament.infra.resources.organizacoes.view] not defined`.
- **O route binding de `Tenant` é o `uuid`, não o `id`.** `Livewire::test(ViewTenant::class,
  ['record' => $tenant->getKey()])` estoura `No query results for model [App\Models\Tenant] 1`;
  o certo é `getRouteKey()`.
- **Discriminância medida em duas correções**: sem `whereNull('users.deleted_at')`, CT-09 reprova
  (17 passam, 1 falha); sem `panel: 'admin'`, quatro casos erram (14 passam, 4 erros).
- **`AuditoriaRecente::getEvents()` passou a usar `$this->getLimit() ?? 8`** em vez do `8`
  literal. Era necessário para o seam ficar coerente: uma subclasse que mudasse o limite não
  surtiria efeito, e o número aparecia duas vezes na mesma classe.

## Retrospectiva

- **Funcionou bem**: o `04` ter escolhido o **evento real** (`Login`/`Failed`) como `Quando` do
  carimbo, em vez de criar a linha à mão. Foi isso que provou que o hook de model cobre as duas
  origens — a linha `Failed` do dataset mata o hook registrado só no `Login`, que é o erro mais
  provável.
- **Funcionou bem**: os três casos que só existem para pegar defeito de arranjo — CT-04 (coluna
  ausente não derruba o login), CT-12 e CT-13 (widget escrito e nunca ligado à página). CT-13
  reprovou de verdade: o `getHeaderWidgets()` do `ViewTenant` nunca tinha sido aplicado, porque o
  script que o adicionava morreu num erro de sintaxe antes de rodar.
- **Faltou no plano**: a revisão profunda (step 5) leu o docblock de `configureSettingsDoKit()`
  para justificar o padrão de `rescue()`, mas não percebeu que a mesma frase invalidava a guarda
  no registro do hook. Ler o vizinho para copiar o padrão e não para conferir a premissa é meia
  revisão.
- **Faltou no plano**: nem o `01` nem o `04` previram `panel:` no `getUrl()` nem o soft delete no
  join. Os dois eram verificáveis lendo a assinatura e o schema, e os dois foram achados por
  teste vermelho — o que é o desenlace certo, mas mais caro que a leitura.

## Quality Gate Final — 2026-09-04

- **Ciclo 1**: REPROVADO por QA-01 (Major). O CT-09 codificava o defeito como esperado e o `INNER JOIN` removia organizações sem acesso elegível, contrariando RQ-02 e o próprio desenho do `04`.
- **Correção test-first**: CT-09 exigiu `Globex => 0`, falhou; a consulta passou a `LEFT JOIN` com agregado condicional; CT-08..CT-10 passaram.
- **Ciclo 2**: APROVADO, sem achados abertos.
- **Relatório**: `06-relatorio-qa.md`.

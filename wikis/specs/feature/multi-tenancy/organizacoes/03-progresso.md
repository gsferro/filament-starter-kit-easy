# Progresso — Multi-tenancy

Branch: `feature/multi-tenancy` · Iniciado e concluído em 2026-08-13

## 1. Channel de log e flag de configuração

- [x] Channel `tenancy` em `config/logging.php` (padrão do channel `ai`)
- [x] Bloco `tenancy` em `config/kit.php` — `enabled`, `label`, `label_plural`, `slug`
- [x] `KIT_TENANCY=false` em `.env.example`

## 2. Migrations

- [x] `create_tenants_table` — id, uuid único, nome, slug único, ativo, timestamps
- [x] `create_tenant_user_table` — FKs com cascade, unique composto
- [x] Confirmado que a migration de permissões cria as colunas de team sozinha quando a flag está ligada

## 3. Model `Tenant`

- [x] `app/Models/Tenant.php` com `TemUuid`, `AuditsFillables` e `HasCurrentTenantLabel`
- [x] Constante `CONTEXTO_GLOBAL`
- [x] Relação `users(): BelongsToMany`
- [x] `database/factories/TenantFactory.php` (só para teste, com state `inativo()`)

## 4. `User` implementa `HasTenants`

- [x] `tenants(): BelongsToMany`
- [x] `getTenants(Panel $panel)` — só ativos; master_global recebe todos
- [x] `canAccessTenant(Model $tenant)` — master_global ou vínculo
- [x] `temPapelGlobal()` — consulta papel no contexto global
- [x] Log `warning` no ramo de negação

## 5. Painel `/app` tenant-aware

- [x] `->tenant(Tenant::class, slugAttribute: 'slug')` guardado por `config('kit.tenancy.enabled')`
- [x] `->tenantMiddleware([DefinirTenantDePermissoes::class], isPersistent: true)`
- [x] Declarado depois de `->plugins()`
- [x] Sem `tenantRegistration()` — quem cria tenant é o administrador

## 6. Papéis por tenant

- [x] `permission.teams = true` (escrito pelo comando)
- [x] `filament-shield.tenant_model` apontando para `Tenant`
- [x] `app/Http/Middleware/DefinirTenantDePermissoes.php`
- [x] `KitServiceProvider::configureTenancy()` — contexto global default do processo
- [x] `PapeisSeeder` cria as definições de papel com `roles.team_id` nulo
- [x] Log `debug` do contexto de papéis

## 7. Camada de IA por tenant

- [x] `app/Ai/Support/ResolvedorDeTenant.php`
- [x] `BudgetGuardMiddleware` usando o resolvedor
- [x] `RegistrarAiRun` usando o resolvedor

## 8. Seeders

- [x] `TenantsSeeder` idempotente, sem factory/faker
- [x] Nota no `PapeisSeeder` sobre definição global × atribuição por tenant
- [x] `DatabaseSeeder` chama o seeder novo só com a flag ligada

## 9. Comando `kit:tenancy`

- [x] `app/Console/Commands/KitTenancy.php` no estilo de `KitInstall`/`KitUpdate`
- [x] Checagem de árvore git limpa (`preVoo()`)
- [x] Aviso destrutivo + confirmação; `--force` obrigatório em `--no-interaction`
- [x] Escrita de `.env`, `config/permission.php` e `config/filament-shield.php`
- [x] `config:clear` antes do `migrate:fresh --seed`
- [x] Banner final com URLs e o lembrete da trait
- [x] Logs de sucesso e de falha

## 10. `/admin`: CRUD de tenants e vínculo de usuários

- [x] `TenantResource` + Pages + Schemas + Tables, com rótulo e slug vindos da config
- [x] `BadgeContagemNavegacao` no resource e `HasResizableColumn` na List page
- [x] `UsersRelationManager` com attach/detach e log
- [x] `TenantPolicy` no padrão Shield
- [x] `PapeisSeeder` concede as permissions de `Tenant` ao papel `admin`

## 11. Demo (`--demo`)

- [x] `Projeto` model + migration com índice `(tenant_id, nome)`
- [x] `ProjetoResource` no painel `/app`, com `scopedUnique()`
- [x] `DemoTenancySeeder` — 2 tenants, 3 usuários, 4 projetos
- [x] Comando imprime quais arquivos apagar para remover a demo

## 12. Testes

- [x] `tests/Tenancy/TenancyTest.php` — 14 casos
- [x] `Tests\TenancyTestCase` resolve a ordem config × migrations
- [x] `Tests\TestCase` remigra quando o modo muda
- [x] `composer test:kit` verde nos dois modos (52 testes)

## 13. Documentação

- [x] `wikis/arquitetura.md` — seção "Multi-tenancy (opt-in)"
- [x] `wikis/convencoes.md` — `BelongsToTenant`, `scopedUnique()`, 4 armadilhas novas
- [x] `wikis/receitas.md` — ligar, model com tenancy, vincular usuário, 3 sintomas novos
- [x] `wikis/README.md` — resumo
- [x] `README.md` e `README.en.md` — seção própria + comando na lista
- [x] `CHANGELOG.md`

## Verificação Final

- [x] `vendor/bin/pint --dirty`
- [x] `composer types:check` (phpstan) — 0 erros
- [x] `php artisan test --group=kit` — 52 passando
- [ ] `/ponytail:ponytail-review` no diff — **não executado**: o comando não está disponível na sessão (ver Blockers)
- [ ] `php artisan kit:tenancy --demo` num clone limpo + navegação manual

## Blockers

- **`/ponytail:ponytail-review` indisponível.** O plugin está habilitado em `.claude/settings.json`, mas o comando não aparece na lista de skills da sessão do agente. A passada anti-over-engineering foi feita à mão (ver Retrospectiva). Rodar manualmente quando possível.

## Desvios do Plano

- **Vocabulário trocado no meio da implementação** (decisão do usuário): `Organizacao`/`organizacoes` → `Tenant`/`tenants` no código, com rótulo e slug configuráveis (`kit.tenancy.label`, `label_plural`, `slug`) e "Organização" como default. Motivo: manter o padrão da API do Filament no código e deixar o termo do negócio só na exibição. Isso **reverteu a ADR-05** parcialmente — ela tratava só da coluna `team_id`; agora todo o vocabulário de código é inglês.
- **`config('kit.tenancy')` virou array** (`kit.tenancy.enabled`), pela mesma decisão.
- **Trait renomeada**: o plano previa `BelongsToOrganizacao`; virou `App\Traits\BelongsToTenant`.
- **Listener de `TenantSet` descartado** já na revisão pós-escrita, em favor do middleware persistente — a doc do Filament indica `isPersistent: true` para cobrir os requests AJAX do Livewire.
- **CT-06 (cache) e CT-11 (árvore suja) não implementados.** O CT-11 já estava marcado como candidato a corte no plano (testa guarda reaproveitada do `KitUpdate`). O CT-06 foi substituído por um teste mais direto do escopo (`recorta as queries pelo tenant corrente` + `volta a enxergar tudo quando não há tenant`), que cobre a causa; o cache do `laravel-model-caching` chaveia pela query, e a query passa a conter o `where tenant_id`.
- **Testes em suíte própria** (`tests/Tenancy/`), não em `tests/Kit/`: o Pest não permite dois TestCases na mesma pasta. Mesmo grupo `kit`, e `composer test:kit` passou a usar `--group=kit` para cobrir as duas.

## Notas de Implementação

- **`model_has_roles.team_id` é NOT NULL** (só `roles.team_id` é nullable). Descoberto pelos testes, com 6 erros de constraint. Consequência: **não existe atribuição global de papel no spatie**. Foi preciso criar o sentinela `Tenant::CONTEXTO_GLOBAL = 0` e o `User::temPapelGlobal()`, sem os quais o `master_global` perderia os poderes ao entrar num tenant e os painéis `/admin` e `/infra` ficariam inacessíveis. Documentado em `wikis/arquitetura.md`.
- **`Role::findOrCreate` carimba o team corrente na definição do papel.** Papel criado dentro do tenant A fica invisível no B. O `PapeisSeeder` passou a usar `firstOrCreate` com `team_id => null` explícito — o `Role::create` do spatie respeita a chave quando ela é passada (`array_key_exists`).
- **`RefreshDatabase` migra uma vez por processo**, então a primeira suíte a rodar definia o schema das duas e a outra quebrava com violação de constraint. Resolvido em `Tests\TestCase`, invalidando o schema só quando o modo muda.
- **`createApplication()` roda depois do boot dos providers**, então o `PermissionRegistrar` (singleton que lê `permission.teams` no construtor) precisou ser descartado com `forgetInstance()` no `TenancyTestCase`.
- **A doc do Filament exige `scopedUnique()`/`scopedExists()`** em resources escopados: as regras `unique`/`exists` do Laravel não passam pelo Eloquent e ignoram o tenant. Virou convenção.
- **A trait é complementar ao escopo do Filament**, não redundante: a doc afirma que só models COM resource são escopadas. Job, comando e API ficariam de fora.

## Retrospectiva

- **Funcionou bem**: escrever os CTs antes forçou a pesquisa das APIs de terceiros na fase de planejamento — mas foi a EXECUÇÃO dos testes que achou o problema real (NOT NULL do `team_id`), não o planejamento. A revisão pós-escrita também pagou: pegou `ApplyTenantScopes`, uma classe que eu havia inventado a partir da documentação.
- **Faltou no plano**: a semântica de `model_has_roles.team_id` NOT NULL. O plano tratou "papéis por tenant" como um flag a ligar, sem investigar como o spatie representa papel global — que era justamente o ponto de atrito com o `master_global` do kit. Lição: quando uma feature cruza um invariante existente (aqui, "master_global vence qualquer gate"), o plano deveria ter um passo explícito de "como o invariante sobrevive".
- **Faltou no plano**: a interação entre `RefreshDatabase` e migrations condicionais. O CT já levantava a dúvida ("verificar na implementação qual funciona"), mas não previu que o problema apareceria entre suítes, não dentro de uma.

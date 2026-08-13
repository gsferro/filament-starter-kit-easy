# Casos de Teste — Multi-tenancy por Organização

## Setup Global

### Estratégia de DB

- **`RefreshDatabase`** — o mesmo já aplicado a `tests/Kit` em `tests/Pest.php:31-35`. Isolamento total é obrigatório aqui: os CTs alternam `permission.teams`, que muda o schema das tabelas de permissão.
- **Seeders no setup**: `ShieldPermissionsSeeder`, `PapeisSeeder`, `OrganizacoesSeeder` — chamados por CT, não globalmente, para cada cenário declarar o que precisa.

### Ligar a tenancy no teste

A suíte do kit roda com `kit.tenancy = false`. Este arquivo é o único que liga:

```php
beforeEach(function (): void {
    config()->set('kit.tenancy', true);
    config()->set('permission.teams', true);
    config()->set('filament-shield.tenant_model', Organizacao::class);
});
```

> **Verificar na implementação**: `RefreshDatabase` roda as migrations **antes** do `beforeEach`. Se a migration de permissões precisar das colunas de team, a flag tem de estar ligada antes — o caminho é `config()->set()` no `tests/Kit/TenancyTest.php` via `uses()->beforeEach()` do Pest **ou** `permission.testing = true`, que a própria migration já aceita como equivalente (`create_permission_tables.php:40`). Confirmar qual funciona antes de escrever os CTs de papel.

### Factories / Fixtures

- `Organizacao::factory()->create(['nome' => 'Acme', 'slug' => 'acme'])` — factory nova (passo 3 do PRD).
- `User::factory()->create()` — já existe em `database/factories/UserFactory.php`, com o state `unverified()`.
- Vínculo: `$user->organizacoes()->attach($organizacao)`.

### Estratégia de Mock

- `Log::spy()` — nos CTs de log (CT-08, CT-11).
- Nenhum mock de HTTP ou fila: a feature não sai do processo.

---

## CT-01: Usuário só enxerga as organizações a que está vinculado

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/TenancyTest.php`
**Método**: `it('lista apenas as organizações vinculadas ao usuário')`

### Precondições

- Organizações `Acme` e `Globex` criadas.
- `$user` vinculado só a `Acme`.

### Dados de Entrada

```
$user->getTenants($painelApp)
```

### Resultado Esperado

- Coleção com 1 item.
- O item é `Acme` (comparar por `id`).
- `Globex` não está presente.

---

## CT-02: Organização inativa não aparece para o usuário

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/TenancyTest.php`
**Método**: `it('esconde organização inativa da lista de tenants')`

### Precondições

- `$user` vinculado a `Acme` (ativa) e `Globex` (`ativa = false`).

### Resultado Esperado

- `getTenants()` devolve só `Acme`.

---

## CT-03: Acesso a organização não vinculada é negado

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/TenancyTest.php`
**Método**: `it('nega acesso a organização à qual o usuário não pertence')`

### Precondições

- `$user` vinculado só a `Acme`.
- `$outra` = `Globex`.

### Resultado Esperado

- `$user->canAccessTenant($acme)` → `true`
- `$user->canAccessTenant($globex)` → `false`
- Requisição autenticada a `/app/globex` → **403**

> Este é o CT que fecha o ataque de adivinhação de slug na URL — o risco que a própria documentação do Filament levanta ("prevent users from accessing the data of other tenants by guessing their tenant ID").

---

## CT-04: `master_global` acessa qualquer organização

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/TenancyTest.php`
**Método**: `it('deixa o master_global acessar qualquer organização')`

### Precondições

- `ShieldPermissionsSeeder`, `PapeisSeeder`, `UsuarioAdminSeeder`.
- `$master` = usuário `admin@example.com`, **sem vínculo** em `organizacao_user`.

### Resultado Esperado

- `$master->canAccessTenant($qualquer)` → `true`
- Requisição a `/app/{slug}` de qualquer organização → **200**

> Coerente com o `Gate::before` já testado em `tests/Kit/FundacaoTest.php:25`.

---

## CT-05: Registros são escopados pela organização corrente

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/TenancyTest.php`
**Método**: `it('mostra na listagem apenas os registros da organização corrente')`

### Precondições

- Demo ligada: `Projeto` e `ProjetoResource` disponíveis.
- `Acme` com projetos `A1`, `A2`; `Globex` com `G1`.
- `$user` vinculado às duas; tenant corrente = `Acme`.

### Dados de Entrada

```php
Filament::setTenant($acme);
livewire(ListProjetos::class)
```

### Resultado Esperado

- `assertCanSeeTableRecords([$a1, $a2])`
- `assertCanNotSeeTableRecords([$g1])`
- Trocar para `Globex` inverte o resultado.

---

## CT-06: Cache de query não vaza entre organizações

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/TenancyTest.php`
**Método**: `it('não serve registros cacheados de outra organização')`

### Precondições

- `laravel-model-caching.enabled = true`.
- Mesmo cenário do CT-05.

### Dados de Entrada

```
listar projetos como Acme → listar projetos como Globex (mesmo request lifecycle)
```

### Resultado Esperado

- A segunda listagem devolve só `G1`.
- Prova que o scope de tenant entra na chave de cache.

> **Risco coberto**: é o vazamento mais silencioso da feature — dado correto no banco, errado na tela.

---

## CT-07: Papel vale só na organização em que foi concedido

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/TenancyTest.php`
**Método**: `it('resolve papéis por organização')`

### Precondições

- `permission.teams = true`.
- `$user` vinculado a `Acme` e `Globex`.
- Papel `admin` atribuído com `setPermissionsTeamId($acme->id)`.

### Dados de Entrada

```php
setPermissionsTeamId($acme->id);  → $user->hasRole('admin')
setPermissionsTeamId($globex->id); → $user->hasRole('admin')
```

### Resultado Esperado

- Em `Acme` → `true`
- Em `Globex` → `false`

> É o CT que prova a ADR-03. Se ele passar sem o listener de `TenantSet`, o listener é desnecessário — vale conferir na implementação.

---

## CT-08: Negação de tenant é registrada em log

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/TenancyTest.php`
**Método**: `it('registra em log a tentativa de acesso a organização não vinculada')`

### Precondições

- `Log::spy()` ativo.
- Cenário do CT-03.

### Resultado Esperado

- `Log::shouldHaveReceived('channel')` com `'tenancy'`
- Nível `warning`
- Mensagem começando com `[User@canAccessTenant]`
- Context contém `user_id`, `organizacao_id` e `motivo => 'sem_vinculo'`

---

## CT-09: Telas globais continuam de pé com tenancy ligada

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/TenancyTest.php`
**Método**: `it('mantém /admin e /infra fora do escopo de tenant')`

### Precondições

- Tenancy ligada, `$master` autenticado.

### Dados de Entrada

Dataset com as rotas já usadas em `tests/Kit/PaginasInfraTest.php` (`/infra/health-check-results`, `/infra/logs`, `/admin/users`, `/admin/shield/roles`, …) **mais** a tela de bloqueio do Lockscreen.

### Resultado Esperado

- Todas devolvem **200**.
- Nenhuma redireciona para uma URL com segmento de tenant.

> **Risco documentado no PRD**: `vendor/marjose123/filament-lockscreen/routes/web.php` registra as rotas com `->prefix($panel->getPath())`, sem o segmento de tenant. Este CT é o que descobre se isso quebra.

---

## CT-10: Execução de IA grava a organização corrente no ledger

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/TenancyTest.php`
**Método**: `it('grava a organização corrente no ledger de IA')`

### Precondições

- Tenant corrente = `Acme`.
- Agente do catálogo com guardrails (usar o `AssistenteSeeder`, como em `tests/Kit/IaTest.php`).
- `Assistente::fake()` — o SDK expõe `::fake()` via `Promptable` (`app/Ai/Agents/AgenteBase.php:33`).

### Resultado Esperado

- Linha em `ai_runs` com `tenant_id` = `(string) $acme->id`.
- Fora de request (sem tenant), `tenant_id` = `config('ai-tasks.default_tenant')`.

---

## CT-11: `kit:tenancy` recusa rodar com árvore git suja

> **Candidato a corte (`ponytail:`)**: a guarda é a mesma de `KitUpdate::preVoo()`, que o plano manda reaproveitar em vez de reescrever. Se a lógica for de fato reaproveitada, este CT testa código de terceiro-já-testado e pode ser descartado. Manter apenas se a extração criar uma concern nova.

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/TenancyTest.php`
**Método**: `it('recusa ativar tenancy com alterações não commitadas')`

### Precondições

- Simular árvore suja (mesmo padrão de teste que `KitUpdate` usa hoje — **verificar na implementação** se existe teste equivalente para reaproveitar o helper; se não existir, mockar a checagem).

### Dados de Entrada

```
php artisan kit:tenancy --no-interaction
```

### Resultado Esperado

- Exit code diferente de 0.
- Saída contém a instrução de commitar antes.
- `config/permission.php` **não** modificado.
- Log `error` no channel `tenancy` **não** emitido (é recusa, não falha).

---

## CT-12: `kit:tenancy --demo` cria o cenário de demonstração

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/TenancyTest.php`
**Método**: `it('cria organizações, usuários e projetos da demo')`

### Precondições

- Rodar apenas o `DemoTenancySeeder` (não o comando inteiro, que roda `migrate:fresh`).

### Resultado Esperado

- 2 organizações (`acme`, `globex`).
- 3 usuários; um deles vinculado às duas organizações.
- 2 projetos por organização, cada um com o `organizacao_id` correto.
- Seeder idempotente: rodar duas vezes não duplica (mesma garantia de `IaTest`, "semeia o catálogo de forma idempotente").

---

## Índice de Casos

| ID | Cenário | Tipo | Arquivo |
|----|---------|------|---------|
| CT-01 | lista só organizações vinculadas | Feature | `tests/Kit/TenancyTest.php` |
| CT-02 | organização inativa não aparece | Feature | `tests/Kit/TenancyTest.php` |
| CT-03 | acesso a organização alheia → 403 | Feature | `tests/Kit/TenancyTest.php` |
| CT-04 | master_global acessa qualquer uma | Feature | `tests/Kit/TenancyTest.php` |
| CT-05 | listagem escopada pelo tenant | Feature | `tests/Kit/TenancyTest.php` |
| CT-06 | cache não vaza entre organizações | Feature | `tests/Kit/TenancyTest.php` |
| CT-07 | papel vale só na organização concedida | Feature | `tests/Kit/TenancyTest.php` |
| CT-08 | negação de tenant vai para o log | Feature | `tests/Kit/TenancyTest.php` |
| CT-09 | /admin, /infra e lockscreen de pé | Feature | `tests/Kit/TenancyTest.php` |
| CT-10 | ledger de IA grava a organização | Feature | `tests/Kit/TenancyTest.php` |
| CT-11 | comando recusa árvore suja | Feature | `tests/Kit/TenancyTest.php` |
| CT-12 | demo cria o cenário completo | Feature | `tests/Kit/TenancyTest.php` |

## Cobertura esperada

- `User::getTenants()` → CT-01, CT-02
- `User::canAccessTenant()` → CT-03 (nega), CT-04 (master), CT-08 (log)
- `ResolvedorDeTenant::id()` → CT-10 (com tenant e sem tenant)
- `KitTenancy::handle()` → CT-11 (recusa); o caminho feliz é validado manualmente em clone limpo, porque roda `migrate:fresh`
- `DemoTenancySeeder::run()` → CT-12

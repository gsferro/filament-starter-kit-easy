# Casos de Teste — Identidade visual da organização

## Setup Global

### Factories / Fixtures

- `Tenant::factory()->create()` — organização **sem** identidade visual (o estado neutro, e o mais
  importante de cobrir: a feature tem de ser inerte)
- `Tenant::factory()->comIdentidadeVisual('#7c3aed')->create()` — state **novo**, criado no passo 3
  do PRD
- `usuarioDoKit('master_global')` / `usuarioCom('admin')` — helpers de `tests/Pest.php`
- Seeders: `ShieldPermissionsSeeder` + `PapeisSeeder`, o par que `tests/Kit/PaineisTest.php:20-22`
  usa

### Estratégia de Mock

- `Storage::fake('public')` — nos CTs de upload de logo. **Não** tocar o disk real.
- `Log::spy()` — nos CTs de log (CT-07)
- Nenhum `Http::fake()` nem `Queue::fake()`: a feature não chama serviço externo nem enfileira

### Estratégia de DB

- `RefreshDatabase`, já aplicado globalmente em `tests/Pest.php` para `Kit` e `Tenancy`
- **Os CTs de tenancy vão em `tests/Tenancy/`**, não em `tests/Kit/`. O motivo é o de sempre neste
  projeto: `Tests\TenancyTestCase` fixa `permission.teams` em `createApplication()`, antes das
  migrations. Ligar a flag num `beforeEach` seria tarde — está documentado em `tests/Pest.php:44-61`
- Os CTs que não dependem de tenancy (fillable, acessor, log) vão em `tests/Kit/`

---

## CT-01: A organização guarda cor e logo

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/IdentidadeVisualTest.php`
**Método**: `it('guarda cor e logo da organizacao')`
**Cobre**: RQ-02, RQ-03

### Precondições

- Seeders rodados

### Dados de Entrada

```php
Tenant::create([
    'nome' => 'Acme', 'slug' => 'acme', 'ativo' => true,
    'cor_primaria' => '#7c3aed', 'logo' => 'organizacoes/logos/acme.png',
]);
```

### Resultado Esperado

- `Tenant` persistido com `cor_primaria` = `'#7c3aed'` e `logo` = `'organizacoes/logos/acme.png'`
- **É o CT que prova o `$fillable`.** Sem os dois campos em `$fillable`, `create()` os descarta em
  silêncio — o registro nasce com os campos nulos, sem erro nenhum. É a falha mais provável do
  passo 2 do PRD, e a que nenhum outro CT pegaria.

---

## CT-02: A URL da logo sai do disk público, e é nula quando não há logo

**Tipo**: `Unit`
**Arquivo**: `tests/Kit/IdentidadeVisualTest.php`
**Método**: `it('resolve a url da logo pelo disk publico')`
**Cobre**: RQ-06

### Precondições

- `Storage::fake('public')`

### Dados de Entrada

Dois tenants: um com `logo = 'organizacoes/logos/acme.png'`, outro com `logo = null`.

### Resultado Esperado

- Com logo: `urlDaLogo()` devolve string contendo `organizacoes/logos/acme.png`
- Sem logo: `urlDaLogo()` devolve **`null`** — e não string vazia. String vazia num `src` de `<img>`
  faz o navegador requisitar a própria página e renderizar ícone quebrado; `null` é o que o Auth
  Designer trata como "sem mídia" (`AuthPageConfig::hasMedia()`)

---

## CT-03: A tela `view` da organização abre para quem pode administrá-la

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/IdentidadeVisualTenancyTest.php`
**Método**: `it('abre a tela de view da organizacao')`
**Cobre**: RQ-08

### Precondições

- Seeders rodados; um `Tenant`; `usuarioCom('admin')`
- Tenancy ligada (é o `TenancyTestCase`) — o `TenantResource::canAccess()` exige
  `config('kit.tenancy.enabled')` (`TenantResource.php:85-88`)

### Dados de Entrada

`GET /admin/organizacoes/{id}`

### Resultado Esperado

- HTTP `200`
- A tela mostra o nome da organização **e o hex da cor primária** — a asserção de conteúdo
  veio para cá quando o `/ponytail:ponytail-review` cortou o CT-B03, que a duplicava em navegador
  sem precisar de navegador para nada (tela de leitura, sem JS interativo)
- **Este CT depende de o `ShieldPermissionsSeeder` ter gerado `View:Tenant`.** Se ele falhar com
  403, a causa é o passo 3 do PRD não ter rodado o seeder — e a mensagem de falha deve deixar isso
  claro, senão custa meia hora de investigação

---

## CT-04: Sem tenancy, a tela `view` não existe

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/IdentidadeVisualTest.php`
**Método**: `it('esconde a tela de view sem tenancy')`
**Cobre**: RQ-08 (o recorte)

### Precondições

- Seeders rodados; `kit.tenancy.enabled` **desligada** (o default de `tests/Kit`)
- `usuarioDoKit('master_global')`

### Resultado Esperado

- `TenantResource::canAccess()` é `false`
- A rota `filament.admin.resources.organizacoes.view` **não** é acessível
- Espelha o que `TenantResource.php:85-88` já promete para as outras páginas. Sem este CT, a página
  nova seria a única do resource a vazar no modo single-tenant

---

## CT-05: A cor de uma organização não vaza para outra, nem para os painéis sem tenant

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/IdentidadeVisualTenancyTest.php`
**Método**: `it('nao vaza a cor entre organizacoes e paineis')`
**Cobre**: RQ-05

### Precondições

- Dois tenants: `acme` com `#7c3aed`, `globex` com `#059669`
- Usuário com papel nos dois

### Dados de Entrada

Três requisições, na mesma execução: `GET /app/acme`, `GET /app/globex`, `GET /admin`.

### Resultado Esperado

- Em `/app/acme` a cor registrada é a da Acme
- Em `/app/globex` a cor registrada é a da Globex — **e não a da Acme**, o que o cache de
  `ColorManager::$cachedColors` (`ColorManager.php:78`) poderia causar
- Em `/admin` **nenhuma cor de tenant** é registrada; o default do Filament sobrevive

> **É o CT mais importante do arquivo.** `FilamentColor` é global, não por painel — a guarda dupla
> do passo 6 do PRD é a única coisa entre esta feature e o `/admin` pintado com a cor de um cliente.
> Um teste HTTP dá para escrever aqui: inspeciona-se `FilamentColor::getColors()['primary']` depois
> do request, sem depender do navegador.

---

## CT-06: A sessão guarda o tenant corrente, e o limpa fora dele

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/IdentidadeVisualTenancyTest.php`
**Método**: `it('guarda o tenant corrente na sessao')`
**Cobre**: RQ-06 (a via, não a tela)

### Precondições

- Um tenant; usuário com papel nele

### Dados de Entrada

`GET /app/{slug}` e depois `GET /admin`.

### Resultado Esperado

- Depois do `/app/{slug}`: `session('tenant_corrente')` é o id do tenant
- Depois do `/admin`: a chave é **`null`** — o middleware do kit não roda ali, mas o valor antigo
  não deve ser lido como se fosse do request corrente. **Se este CT falhar, a lock-screen do
  `/admin` mostraria a logo de um cliente**, que é o risco nomeado em ADR-03

---

## CT-07: O log explica por que a logo genérica apareceu

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/IdentidadeVisualTest.php`
**Método**: `it('registra o motivo de usar a midia base')`
**Cobre**: rastreabilidade

### Precondições

- Sem tenant na sessão; `Log::spy()`

### Resultado Esperado

- `debug` com `[TelaBloqueio@getAuthDesignerConfig]` e context `motivo` ∈
  `{sem_tenant, sem_logo, painel_sem_tenancy}`
- **Por que este log existe e o do caso neutro da cor não**: "por que apareceu a logo genérica?" é
  a pergunta que alguém vai fazer olhando a tela. "Por que a cor é a default?" é auto-evidente. O
  log serve a pergunta, não à simetria

---

## CT-09 a CT-12: Regressão da wiki ancestral

**Tipo**: `Feature`
**Arquivo**: **os que já existem** — nenhum arquivo novo
**Cobre**: `## Impacto em Features Existentes` do PRD

A natureza da wiki é `evolução`, então estes não são CTs novos: são os da ancestral, que **têm de
continuar verdes** depois de o `Tenant` ganhar colunas e `$fillable` crescer.

| # | Suíte | O que protege |
|---|---|---|
| CT-09 | `tests/Tenancy/TenancyTest.php` | recorte por organização, `getTenants`, `canAccessTenant` |
| CT-10 | `tests/Kit/AdminDaOrganizacaoTest.php` + `tests/Tenancy/AdminDaOrganizacaoTest.php` | as seis barreiras da persona `admin_organizacao` |
| CT-11 | `tests/Kit/PaineisTest.php:127-135` | a matriz de permissões por painel — muda com `View:Tenant` |
| CT-12 | `tests/Kit/BloqueioDeSessaoTest.php` | o layout da `TelaBloqueio`, que o passo 8 modifica |

**CT-11 e CT-12 são os que mais provavelmente quebram**, e por motivos opostos: o CT-11 porque a
matriz cresce (permission nova), o CT-12 porque a classe muda. Rodar os dois **antes** de escrever
os CT novos.

---

## Fronteira com os CT-B

| Pergunta | Arquivo | Por quê |
|---|---|---|
| A cor foi **registrada** no `ColorManager`? | este arquivo (CT-05) | é estado de PHP, inspecionável sem navegador |
| A cor **aparece** na tela? | `05-*-browser.md` (CT-B02) | depende do `@filamentStyles` gerar a CSS var e o navegador aplicá-la |
| A logo tem URL? | este arquivo (CT-02) | acessor puro |
| A logo **é exibida** na lock-screen? | `05-*-browser.md` (CT-B04) | depende do render do layout do Auth Designer |
| O `EditAction` navega ou abre modal? | `05-*-browser.md` (CT-B01) | é comportamento de UI, e é a premissa que RQ-09 supõe |

## Índice de Casos

| ID | Cenário | Tipo | Arquivo | RQ |
|----|---------|------|---------|-----|
| CT-01 | guarda cor e logo (prova o `$fillable`) | Feature | `tests/Kit/IdentidadeVisualTest.php` | RQ-02, RQ-03 |
| CT-02 | URL da logo, e nula sem logo | Unit | `tests/Kit/IdentidadeVisualTest.php` | RQ-06 |
| CT-03 | tela `view` abre para `admin` | Feature | `tests/Tenancy/IdentidadeVisualTenancyTest.php` | RQ-08 |
| CT-04 | tela `view` não existe sem tenancy | Feature | `tests/Kit/IdentidadeVisualTest.php` | RQ-08 |
| CT-05 | **cor não vaza** entre organizações nem para `/admin` | Feature | `tests/Tenancy/IdentidadeVisualTenancyTest.php` | RQ-05 |
| CT-06 | sessão guarda o tenant corrente | Feature | `tests/Tenancy/IdentidadeVisualTenancyTest.php` | RQ-06 |
| CT-07 | log do motivo da mídia base | Feature | `tests/Kit/IdentidadeVisualTest.php` | — |
| CT-09–12 | regressão da ancestral | Feature | arquivos existentes | — |

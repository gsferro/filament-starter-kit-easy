# Casos de Teste de Browser — Identidade visual da organização

> Referência: `01-plano-acao.md` seção `## Superfície de UI`
> Runtime: `pest-plugin-browser` 5.0.1 — `vendor/bin/pest --testsuite=Browser`

## Por que esta feature depende de CT-B mais que a média

Cor e logo são **invisíveis para teste HTTP**. `$this->get('/app/acme')` devolve o mesmo corpo de
HTML para qualquer cor: as CSS vars entram no `<head>` pelo `@filamentStyles`
(`AssetManager.php:279-305`), e a cor efetiva depende de o navegador aplicar a variável ao
componente. O `04-casos-de-teste.md` prova que a cor foi **registrada**; só o navegador prova que
ela **aparece**.

## Pré-requisitos de Ambiente

Todos herdados da wiki `regressao-de-telas` — nada novo a instalar.

- [x] `pestphp/pest-plugin-browser` 5.0.1 + Playwright instalados
- [x] App servido pelo próprio plugin, in-process. Nenhum Herd/serve/Sail
- [x] **`npm run build` obrigatório** — `composer test:browser` já o embute
- [x] `pest()->browser()->timeout(20_000)` em `tests/Pest.php`
- [x] `$this->actingAs()` autentica o navegador
- [x] `tests/Browser/Screenshots` no `.gitignore`
- [ ] **`Storage::fake('public')` NÃO serve aqui.** O navegador faz request HTTP real à URL da
      logo; disk fake não é servido. Os CT-B que exibem logo usam `Storage::disk('public')->put()`
      real e limpam depois — registrado no Setup Global

## Setup Global

### Tenancy ligada — e é o que separa este arquivo do da wiki anterior

Os CT-B de `regressao-de-telas` rodam **single-tenant**. Estes precisam de `/app/{tenant}`, então
precisam de `kit.tenancy.enabled`.

**O `tests/Pest.php` hoje aplica `TestCase` (single-tenant) em `tests/Browser`**
(`->extend(TestCase::class)->in('Browser')`). E o Pest **não permite dois TestCases na mesma
pasta** — a mesma restrição que criou `tests/Tenancy/` separado de `tests/Kit/`
(`tests/Pest.php:44-61`).

**Consequência**: os CT-B de tenancy precisam de **subpasta própria** com `TenancyTestCase`:

```php
// tests/Pest.php — a acrescentar no passo de teste
pest()->extend(TenancyTestCase::class)
    ->use(RefreshDatabase::class)
    ->group('browser')
    ->in('BrowserTenancy');
```

Pasta: `tests/BrowserTenancy/`. Mesmo grupo `browser`, então continua fora do `composer test:kit`
e dentro do `--testsuite=Browser` — **e o `phpunit.xml` precisa incluir o diretório novo na
testsuite `Browser`**. Isto é trabalho de infra que o PRD não previu; está anotado como o primeiro
item do passo de testes.

### Autenticação

`$this->actingAs(usuarioComPapel('panel_user', $tenant))` — o helper de `tests/Pest.php:123`, que
grava o papel **no contexto do tenant**. Usar `usuarioDoKit()` aqui daria papel no contexto global,
e o usuário entraria num painel vazio (a armadilha que ADR-10 da wiki `admin-da-organizacao`
documenta).

### Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| CSS var da cor primária | `--primary-500` no `<head>` | Sim — gerada por `@filamentStyles` |
| Mídia do layout de auth | `img` dentro do container de mídia | Sim — `partials/media.blade.php:19` |
| Botão "Editar" da tabela | texto `Editar` | Sim |
| Link para a tela `view` | texto `Visualizar` | **Criado no passo 3 do PRD** |
| `ColorPicker` da cor | `#form\.cor_primaria` | Criado no passo 5 — `id` gerado pelo Filament |

---

## CT-B01: O `EditAction` da tabela navega para a página, não abre modal

**Arquivo**: `tests/BrowserTenancy/IdentidadeVisualTest.php`
**Método**: `it('abre a tela cheia de edicao da organizacao')`
**Cobre**: RQ-09 — **e é o CT-B que responde a ambiguidade, antes de qualquer correção**

### Precondições

- Tenancy ligada; um `Tenant`; `actingAs(usuarioCom('admin'))`

### Roteiro

| # | Ação | Código Pest | Resultado visível esperado |
|---|---|---|---|
| 1 | Abrir a listagem | `visit('/admin/organizacoes')` | nome da organização visível |
| 2 | Clicar em Editar | `->click('Editar')` | navega |
| 3 | Conferir a URL | `->assertPathIs('/admin/organizacoes/{id}/edit')` | tela cheia |

**`assertPathIs` antes de qualquer assertion de conteúdo** — é ele que espera a navegação. A ordem
inversa falha com a ação tendo funcionado; foi o achado D-06 da wiki anterior, e está em
`.ai/rules/testes-browser.md`.

### Assertions

- `assertPathIs('/admin/organizacoes/{id}/edit')`
- `assertSee('Identidade visual')` — a Section nova, que só existe na tela cheia
- `assertNoJavaScriptErrors()`

### Interpretação do resultado — definida ANTES de rodar

| Resultado | Significado | Ação |
|---|---|---|
| **Verde** | `EditAction` navega, como `Page.php:373-380` prevê. RQ-09 já satisfeito na tabela | registrar em "Desvios do Plano": a premissa do requisito não se aplica aqui. **Não mexer no `EditAction`** |
| **Vermelho, path continua `/admin/organizacoes`** | abriu modal | achado de implementação. Investigar as três guardas de `Page.php:376-377` — **não** aplicar `->url()` às cegas |

> **O modal do RelationManager é correto, e não tem CT-B próprio.** O `UsersRelationManager` não
> declara `$relatedResource`, e `RelationManager::getDefaultActionUrl()` devolve `null` nesse caso
> (`RelationManagers/RelationManager.php:396-398`) — então `AttachAction`, `DetachAction` e a
> `Action::make('papeisNaOrganizacao')` abrem modal **sempre**. Vincular usuário a uma organização
> é ação de relação: modal é a forma certa, e transformá-la em tela cheia pioraria o fluxo.
>
> Isto está escrito aqui, e não num cenário de browser, porque impedir que alguém "corrija" o que
> está certo é trabalho de comentário — o `/ponytail:ponytail-review` cortou o CT-B que fazia
> isso a ~10 s de navegador por execução.

---

## CT-B02: A cor da organização chega ao painel `/app` dela — e duas organizações diferem

**Arquivo**: `tests/BrowserTenancy/IdentidadeVisualTest.php`
**Método**: `it('aplica a cor da organizacao no painel de negocio')`
**Cobre**: RQ-05 — **o CT-B central da feature**

### Precondições

- **Dois** tenants: `acme` com `#7c3aed`, `globex` com `#059669`
- Usuário com papel `panel_user` **nos dois**, via `papelNaOrganizacao()`

### Roteiro

| # | Ação | Código Pest | Resultado visível esperado |
|---|---|---|---|
| 1 | Abrir o painel da Acme | `visit('/app/acme')` | dashboard renderiza |
| 2 | Ler a CSS var | `->script('getComputedStyle(document.documentElement).getPropertyValue("--primary-500")')` | valor OKLCH derivado de `#7c3aed` |
| 3 | Abrir o da Globex | `visit('/app/globex')` | dashboard renderiza |
| 4 | Ler a CSS var de novo | idem | valor **diferente** do passo 2 |

### Assertions

- A CSS var `--primary-500` existe e não está vazia nos dois
- **Os dois valores são diferentes entre si** — é isto que prova que o cache de
  `ColorManager::$cachedColors` (`ColorManager.php:78`) não está congelando a cor do primeiro
  tenant do processo. Risco nomeado em ADR-02 e o mais fino do plano
- `assertSee('Painel de Controle')` — o `<h1>` do dashboard, em pt_BR (**não** `Dashboard`; foi o
  achado D-01 da wiki anterior)
- `assertNoJavaScriptErrors()`

> **Por que `script()` e não screenshot**: cor exata em pixel é frágil (antialiasing, perfil de
> cor). A CSS var é o contrato que o Filament publica, e comparar dois valores entre si é
> determinístico. Screenshot fica para o que só o olho pega — e aqui não é o caso.

---

## CT-B03: A cor de um cliente não vaza para o `/admin`

**Arquivo**: `tests/BrowserTenancy/IdentidadeVisualTest.php`
**Método**: `it('nao vaza a cor da organizacao para o painel admin')`
**Cobre**: RQ-05 (o limite) — **o CT-B de segurança da feature**

### Precondições

- Tenant `acme` com `#7c3aed`; usuário com papel no tenant **e** papel `admin` no contexto global

### Roteiro

| # | Ação | Código Pest | Resultado visível esperado |
|---|---|---|---|
| 1 | Abrir o painel da Acme | `visit('/app/acme')` | cor da Acme aplicada |
| 2 | Ir para o `/admin` | `visit('/admin')` | **cor default do Filament** |
| 3 | Ler a CSS var | `->script(...)` | valor de Amber, não de `#7c3aed` |

### Assertions

- `--primary-500` no `/admin` é **diferente** do valor visto no `/app/acme`
- `assertNoJavaScriptErrors()`

> `FilamentColor` é **global, não por painel**. A guarda de painel do passo 6 do PRD é a única coisa
> entre esta feature e o painel de administração pintado com a cor de um cliente. A ordem dos passos
> importa: visitar o `/app` **primeiro** é o que torna o teste capaz de pegar o vazamento.

---

## CT-B04: A lock-screen do `/app` mostra a logo da organização

**Arquivo**: `tests/BrowserTenancy/IdentidadeVisualTest.php`
**Método**: `it('exibe a logo da organizacao na tela de bloqueio')`
**Cobre**: RQ-06

### Precondições

- Tenant `acme` com uma logo **de verdade** no disk `public`:
  `Storage::disk('public')->put('organizacoes/logos/acme.png', $bytesDeUmPngMinimo)`
  — **não** `Storage::fake()`: o navegador faz request HTTP real à URL, e disk fake não é servido
- `actingAs(usuarioComPapel('panel_user', $acme))`
- `config('lockscreen.enabled')` ligada

### Roteiro

| # | Ação | Código Pest | Resultado visível esperado |
|---|---|---|---|
| 1 | Abrir o painel do tenant | `visit('/app/acme')` | dashboard — **e é este passo que grava a sessão** |
| 2 | Travar a sessão | POST para `lockscreen.app.lock-session` | redireciona à lock-screen |
| 3 | Conferir a mídia | `->assertAttribute('img', 'src', …)` | `src` contém `organizacoes/logos/acme.png` |

**O passo 1 não é cerimônia**: é ele que faz o `DefinirTenantDePermissoes` gravar
`session('tenant_corrente')`. Sem visitar o painel primeiro, a lock-screen não tem tenant — que é
exatamente o que ADR-03 descreve.

### Assertions

- O `src` da `img` de mídia aponta para a logo da organização
- `assertSee(…)` de um texto do formulário de desbloqueio — garante que a tela não veio vazia
- `assertNoJavaScriptErrors()`
- **O alternador de tema continua na tela** — é a assertion que pega o erro de `setPageConfig()`
  substituir o config inteiro em vez de fazer merge (ADR-04). Sem ela, a troca de mídia poderia
  apagar `themeToggle()` e `mediaPosition()` sem nenhum sinal

---

## CT-B05: Sem tenant, a lock-screen usa a mídia base

**Arquivo**: `tests/Browser/IdentidadeVisualPadraoTest.php` (single-tenant, pasta existente)
**Método**: `it('usa a midia base na tela de bloqueio sem organizacao')`
**Cobre**: RQ-06 (o fallback)

### Precondições

- **Sem tenancy** (`tests/Browser` é single-tenant); `usuarioDoKit('master_global')`
- `config('lockscreen.enabled')` ligada

### Roteiro

Travar a sessão no `/app` e abrir a lock-screen.

### Assertions

- O `src` da mídia é `images/auth/login.svg` — a base
- `assertNoJavaScriptErrors()`

> **Este é o CT-B que protege os painéis `/admin` e `/infra`.** A mesma `TelaBloqueio` serve os
> três, e a guarda de painel do passo 8 é o que impede o administrador de ver a logo de um cliente.
> Fica na pasta single-tenant de propósito: é o cenário sem tenant.

---

## Roteiro de Validação: Desenhado × Implementado

<!-- Preencher após a execução. -->

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | Duas colunas em `tenants` (`cor_primaria`, `logo`) | *a preencher* | | CT-01 |
| 2 | `Section` "Identidade visual" com `ColorPicker` + `FileUpload` | *a preencher* | | CT-B01 |
| 3 | Página `view` registrada e no menu de ações | *a preencher* | | CT-03 (backend) |
| 4 | Cor via `FilamentColor::register(Closure)`, avaliada no render | *a preencher* | | CT-B02 |
| 5 | Guarda dupla (painel `app` + tenant com cor) | *a preencher* | | CT-B03 |
| 6 | `session('tenant_corrente')` gravada no middleware existente | *a preencher* | | CT-06 |
| 7 | Logo na lock-screen via `mergeWith()`, preservando o alternador de tema | *a preencher* | | CT-B04 |
| 8 | Fallback para a mídia base sem tenant | *a preencher* | | CT-B05 |
| 9 | RQ-09: o `EditAction` já navega (premissa do requisito não se aplica) | *a preencher* | | CT-B01 |
| 10 | Regressão: `tests/Tenancy/*` e `BloqueioDeSessaoTest` verdes | *a preencher* | | CT-09–12 |
| 11 | *(não desenhado)* pasta `tests/BrowserTenancy/` + `phpunit.xml` | *a preencher* | | Setup Global |

**Divergências encontradas**: registrar aqui e replicar em `03-progresso.md` → "Desvios do Plano".

## Índice de CT-B

| ID | Cenário | Rota | Arquivo | RQ |
|----|---------|------|---------|-----|
| CT-B01 | `EditAction` navega para tela cheia | `/admin/organizacoes` | `tests/BrowserTenancy/IdentidadeVisualTest.php` | RQ-09 |
| CT-B02 | RelationManager em modal — correto | `/admin/organizacoes/{id}/edit` | idem | RQ-09 |
| CT-B03 | tela `view` mostra a identidade visual | `/admin/organizacoes/{id}` | idem | RQ-08 |
| CT-B02 | **cor chega ao `/app`, e difere entre organizações** | `/app/{slug}` | idem | RQ-05 |
| CT-B03 | **cor não vaza para o `/admin`** | `/admin` | idem | RQ-05 |
| CT-B04 | logo da organização na lock-screen | `/app/screen/lock` | idem | RQ-06 |
| CT-B05 | mídia base sem organização | `/app/screen/lock` | `tests/Browser/IdentidadeVisualPadraoTest.php` | RQ-06 |

# Plano de Ação — Identidade visual da organização

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: evolução
- **Wiki ancestral**: `wikis/specs/feature/multi-tenancy/organizacoes/` — é ela que criou o
  `Tenant`, o `TenantResource` e o painel `/app/{tenant}`. Secundariamente
  `wikis/specs/main/admin-da-organizacao/`, que criou a persona `admin_organizacao`.
- **Motivo**: o `Tenant` existe e tem CRUD, mas o registro dele guarda três campos
  (`nome`, `slug`, `ativo`). Esta evolução acrescenta a identidade visual e a faz atravessar o
  request do painel `/app`.

> **Efeito no `feature-quality-gate`**: tipo `evolução` **dispara regressão** contra os CT da
> wiki ancestral — `tests/Tenancy/*` e `tests/Kit/AdminDaOrganizacaoTest.php`. O recorte por
> organização não pode quebrar porque o `Tenant` ganhou colunas.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Customizador de identidade visual no `/admin` | 2, 4 | `Section` própria no `TenantForm` |
| RQ-02 | Imagens, logos e cores no registro da organização | 1, 4 | **parcial por decisão do próprio requisito** — cores + logo. "Imagens" no plural fica fora por RQ-03; ver ADR-01 |
| RQ-03 | A princípio, só cores | 1, 4, 5 | cores são o núcleo; a logo entra porque RQ-06 a exige |
| RQ-04 | Usar a doc oficial do Filament 5 como base | 5 | `FilamentColor::register()` com Closure, `Color::generatePalette()`. Origem citada em ADR-02 |
| RQ-05 | Ao abrir `/app` do tenant, carrega a identidade dele | 5, 8, 9 | o passo central |
| RQ-06 | Logo do cliente na lock-screen | 6, 7 | **a premissa do requisito não se sustenta pela rota** — ver `00` → Ambiguidades e ADR-03 |
| RQ-07 | Espaço para mais definições, a cargo de quem usa o kit | 4 | ponto de extensão, não campos inventados. Ver ADR-05 |
| RQ-08 | Telas de create, edit e **view** | 3 | create e edit **já existem**; a `view` é a lacuna real |
| RQ-09 | Telas completas, não modal | 3, 9 | **verificar antes de corrigir** — o `EditAction` da tabela já navega; o modal vem de outro lugar |

## Objetivo

Fazer o painel `/app` **parecer do cliente**. Hoje as três instalações de organização diferentes
vêem exatamente a mesma tela: mesma cor primária (Amber, o default do Filament — o kit não
declara cor nenhuma), mesma logo, mesma mídia de login. Para um SaaS multi-tenant isso é a
diferença entre "um sistema que eu uso" e "o meu sistema".

A entrega é deliberadamente estreita: **uma cor e uma logo por organização**, escolhidas no
`/admin`, carregadas no `/app` do tenant correspondente, com a logo aparecendo também na
lock-screen. Junto vem o que o requisito pediu no CRUD: a página `view`, que não existe, e a
verificação de onde de fato aparece o modal que o usuário viu.

## Contexto

### O que existe, e o que falta

| Peça | Onde | Estado |
|---|---|---|
| Model `Tenant` | `app/Models/Tenant.php:72-76` | `$fillable` = `['nome', 'slug', 'ativo']`. **Nenhuma coluna de identidade visual** |
| `TenantResource` | `app/Filament/Admin/Resources/Tenants/TenantResource.php:110-114` | páginas `index`, `create`, `edit`. **Sem `view`** |
| `TenantForm` | `.../Schemas/TenantForm.php` | uma `Section` "Identificação" com 3 campos |
| `TenantsTable` | `.../Tables/TenantsTable.php:38` | `EditAction::make()` sem `->url()` |
| `UsersRelationManager` | `.../RelationManagers/UsersRelationManager.php` | `AttachAction`, `DetachAction`, `Action::make('papeisNaOrganizacao')` — **sem `$relatedResource`** |
| Cor dos painéis | — | **nenhum painel declara `->colors()`**. Todos usam o default (`primary` = Amber) |
| Upload de arquivo | — | **nenhum `FileUpload` em `app/`**. O único em uso é o avatar do Breezy |
| `storage:link` | `app/Console/Commands/KitInstall.php:163` | já roda no install — o disk `public` está pronto |
| Middleware de tenant do kit | `app/Http/Middleware/DefinirTenantDePermissoes.php:33` | **já tem o tenant em mãos**, em todo request do `/app`, inclusive AJAX do Livewire |

### O mecanismo de cores do Filament 5 — e a distinção que decide o plano

Confirmado na doc oficial (RQ-04) **e** no vendor:

```php
// vendor/filament/support/src/Colors/ColorManager.php:63
public function register(array | Closure $colors): static
{
    $this->colors[] = $colors;   // GUARDA a Closure

    return $this;
}
```

A Closure é avaliada em `getColors()` (`ColorManager.php:80`), que por sua vez é chamado por
`AssetManager::renderStyles()` (`AssetManager.php:286`) — o `@filamentStyles`, **no render do
`<head>`**. Depois de todo middleware. É a janela que a feature precisa.

**A armadilha, e é fácil cair nela**: `Panel::colors()` também aceita Closure
(`Panel/Concerns/HasColors.php:17`), mas o `Panel::boot()` faz
`FilamentColor::register($this->getColors())` (`Panel.php:95`), e o `getColors()` do painel
**avalia a Closure ali** (`HasColors.php:31`). O `Panel::boot()` é disparado pelo middleware
`panel:{id}`/`SetUpPanel`, que é o **primeiro** da pilha
(`Panel/Concerns/HasMiddleware.php:97-103`) — antes do `IdentifyTenant`. Logo:

| Caminho | Quando a Closure roda | `Filament::getTenant()` disponível? |
|---|---|---|
| `$panel->colors(fn () => …)` | `Panel::boot()`, no 1º middleware | ❌ sempre `null` |
| `FilamentColor::register(fn () => …)` | render do `@filamentStyles` | ✅ sim |

**Só o segundo serve.** Ver ADR-02.

E a paleta sai de uma cor só: `Color::generatePalette(string $color)`
(`vendor/filament/support/src/Colors/Color.php:663`) devolve as 11 shades em OKLCH, e o
`ColorManager` já a chama sozinho quando recebe string (`ColorManager.php:84-85`). Então guardar
**um hex** por organização basta — nada de 11 colunas.

### A lock-screen não sabe o tenant — e por quê

O pacote registra a rota com o path do **painel**
(`vendor/marjose123/filament-lockscreen/routes/web.php`):

```php
->middleware(...$panel->getMiddleware())   // só o middleware base
->prefix($panel->getPath())                 // 'app', nunca 'app/{tenant}'
```

Duas consequências verificadas:

1. A URL é `/app/screen/lock`, sem segmento de tenant, mesmo com tenancy ligada.
2. O `tenantMiddleware` — onde vivem o `IdentifyTenant` do Filament e o
   `DefinirTenantDePermissoes` do kit — **não roda nessa rota**.

E o Filament **não persiste tenant em sessão**: `FilamentManager::$tenant` é propriedade de
instância (`FilamentManager.php:54`), e o único lugar que a preenche é o `IdentifyTenant`, a
partir do parâmetro de rota (`IdentifyTenant.php:27-44`). O kit também não guarda nada —
`DefinirTenantDePermissoes` não toca em `session()`, e `User` não implementa `HasDefaultTenant`.

Portanto o tenant tem de ser **gravado por nós** enquanto ele é conhecido. Ver ADR-03.

### O Auth Designer aceita troca em runtime — na janela do `mount()`

`AuthPageConfig::media(?string $media, ?string $alt)`
(`vendor/caresome/filament-auth-designer/src/Data/AuthPageConfig.php:28`) aceita **só string**,
não Closure. Mas isso não fecha a porta:

- `AuthDesignerConfigRepository` é **singleton**
  (`AuthDesignerServiceProvider.php:29`), com `setPageConfig()` **público**
  (`AuthDesignerConfigRepository.php:40`).
- O blade lê o config na **primeira linha**, em tempo de render:
  `$config = $livewire->getAuthDesignerConfig();`
  (`resources/views/components/layouts/auth.blade.php:6`).
- `getAuthDesignerConfig()` vem da trait `HasAuthDesignerLayout` (`:20`), é `public` e
  **não-final** — sobrescrevível em `TelaBloqueio`.

**Cuidado**: `setPageConfig()` **substitui** o objeto inteiro, sem merge (`:42`). Trocar só a
mídia exige ler o atual com `getPageConfig()` e combinar com `mergeWith()`
(`AuthPageConfig.php:180`), ou reconstruir `mediaPosition`/`mediaSize`/`themeToggle`. Ver ADR-04.

### Onde de fato está o modal que o requisito menciona

`Resources/Pages/Page.php:373-380` — lido e confirmado:

```php
if (
    ($action instanceof EditAction) &&
    (static::getResource()::hasPage('edit')) &&
    (! $this instanceof EditRecord) &&
    (blank($actionModel) || ($actionModel === static::getResource()::getModel()))
) {
    return $this->getResourceUrl('edit', ['record' => $action->getRecord()]);
}
```

Com URL preenchida, o Filament renderiza `<a href>` em vez de `wire:click`
(`Actions/Action.php:889`). Como o `TenantResource` **tem** página `edit`, o `EditAction` da
tabela **navega**.

O modal vem de outro lugar: o `UsersRelationManager` **não declara `$relatedResource`**, e
`RelationManager::getDefaultActionUrl()` retorna `null` nesse caso
(`RelationManagers/RelationManager.php:396-398`) — então `AttachAction`, `DetachAction` e a
`Action::make('papeisNaOrganizacao')` abrem modal, **sempre**, e é isso que se vê ao editar uma
organização.

**Isto é hipótese com evidência de código, não conclusão.** O passo 4 a confirma por CT-B antes
de qualquer correção. E `ViewAction` tem a mecânica simétrica (`Page.php:382-389`): sem página
`view`, ele abriria modal — o que reforça criar a página, e não só a action.

## Autorização

Nenhuma policy nova. A feature herda o que existe:

- `TenantResource::canAccess()` já exige `config('kit.tenancy.enabled')` **e** `parent::canAccess()`
  (`TenantResource.php:85-88`) — quem edita identidade visual é quem já podia editar a organização.
- A página `view` nova entra na matriz do Shield como `View:Tenant`. **O
  `ShieldPermissionsSeeder` precisa rodar de novo** para a permission existir — passo 3.
- O painel `/app` **lê** a identidade visual sem checagem de permissão: é a aparência do painel
  que o usuário já pode abrir, não um dado protegido.

## Rotas

Uma rota nova, gerada pelo Resource — nenhuma rota escrita à mão:

| Método | URI | Name | Middleware |
|--------|-----|------|------------|
| GET | `/admin/organizacoes/{record}` | `filament.admin.resources.organizacoes.view` | o do painel `admin` |

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `TenantForm` → `Section` "Identidade visual" | Filament Schema | `/admin/organizacoes/create` e `/{record}/edit` | escolhe cor no `ColorPicker`, envia logo no `FileUpload` | **Sim** |
| `ViewTenant` (nova) | Filament Page (`ViewRecord`) | `/admin/organizacoes/{record}` | lê os dados, incluindo a identidade visual | Sim |
| `TenantsTable` | Filament Table | `/admin/organizacoes` | `ViewAction` nova ao lado do `EditAction` | Sim |
| Painel `/app/{tenant}` — **todas as telas** | Filament Panel | `/app/{tenant}/*` | vê a cor da organização em botões, links, badges | Sim |
| Lock-screen do `/app` | Filament Page + Auth Designer | `/app/screen/lock` | vê a logo da organização | Sim |

**Gate de CT-B**: 5 linhas, todas `Depende de JS? = Sim` → **criar** `05-casos-de-teste-browser.md`. ✅

E há um motivo extra, específico desta feature: **cor e logo são invisíveis para teste HTTP**.
`$this->get('/app')` devolve o mesmo HTML para qualquer cor — as CSS vars entram no `<head>` via
`@filamentStyles`, e a cor efetiva depende do navegador aplicar a variável. É a classe de defeito
que só browser pega.

## Variáveis de Ambiente

Nenhuma chave nova. A feature usa o disk `public`, já configurado
(`config/filesystems.php:41-48`) e já com symlink criado no install.

> **Atenção**: `FILESYSTEM_DISK` default é `local`, que aponta para `storage/app/private`
> (`filesystems.php:16,35`) e **não é servível por URL**. Por isso o `FileUpload` da logo declara
> `->disk('public')` explicitamente — herdar o default entregaria uma logo inacessível.

## Eventos / Listeners / Observers

Nenhum. O Filament dispara `TenantSet` em `setTenant()` (`FilamentManager.php:899-906`), e um
listener dele seria o gancho semanticamente mais bonito para gravar o tenant na sessão — mas
custa um arquivo e um registro para o que uma linha resolve no middleware que já existe. Ver
ADR-03, alternativa 3.

## Jobs / Queues

Nenhum. Upload de logo é síncrono; não há processamento de imagem nesta entrega.

## Impacto em Features Existentes

| Feature | O que pode quebrar e por quê |
|---|---|
| **Recorte por organização** (`tests/Tenancy/*`) | o `Tenant` ganha colunas e `$fillable` cresce. Baixo risco, mas é o motivo de a natureza ser `evolução`: o quality gate roda regressão |
| **`TenantFactory`** | ganha os campos novos com default nulo. Testes existentes que fazem `Tenant::factory()->create()` não podem quebrar |
| **Matriz de permissões** | a página `view` nova cria `View:Tenant`. Sem rodar o `ShieldPermissionsSeeder`, o papel `admin` não a tem e a tela dá 403. `tests/Kit/PaineisTest.php:127-135` afirma a matriz |
| **Cor global dos painéis `/admin` e `/infra`** | `FilamentColor::register()` é **global**, não por painel. Um registro descuidado pintaria os três painéis com a cor do tenant. A guarda é o painel corrente — passo 6, e CT-05 existe só para isso |
| **Lock-screen dos painéis `/admin` e `/infra`** | a mesma `TelaBloqueio` serve os três painéis. A troca de mídia tem de ser condicionada ao painel `app` **e** a haver tenant, senão o admin passa a ver a logo de um cliente |
| **`tests/Kit/BloqueioDeSessaoTest.php`** | mexe-se no `TelaBloqueio`; a suíte que cobre o layout dele tem de continuar verde |

## Rollback

- **Migration down**: `dropColumn(['cor_primaria', 'logo'])` na tabela `tenants`. Reversível sem
  perda de outro dado.
- **Logos já enviadas**: ficam em `storage/app/public/organizacoes/logos`. O `down()` **não** as
  apaga — arquivo órfão é preferível a `down()` destrutivo.
- **Desligar sem reverter**: com as colunas nulas, `FilamentColor::register()` cai no default e o
  Auth Designer mantém a mídia base. **A feature é inerte quando os campos estão vazios** — é o
  que a torna segura de mergear antes de qualquer organização preencher.

## Dependências

Nenhum pacote novo. Tudo usado já está instalado:

| Peça | Origem |
|---|---|
| `Filament\Forms\Components\ColorPicker` | `filament/forms`, já presente (`ColorPicker.php:13`, com `->hex()` em `:31`) |
| `Filament\Forms\Components\FileUpload` | `filament/forms`, já presente |
| `Filament\Support\Facades\FilamentColor` | `filament/support`, já presente |
| `Filament\Support\Colors\Color` | idem |
| disk `public` + symlink | `config/filesystems.php` + `KitInstall.php:163` |

## Riscos

| Risco | Mitigação |
|---|---|
| **Cor do tenant vazar para `/admin` e `/infra`** — `FilamentColor::register()` é global | registrar só quando o painel corrente é `app` **e** há tenant. CT-05 prova o não-vazamento |
| **Cor ilegível escolhida pelo cliente** (branco em branco) | o Filament escolhe a shade por contraste WCAG em runtime (`Color::WCAG_AA_TEXT`), o que absorve boa parte. Não se valida a escolha nesta entrega — registrado como limite conhecido |
| **Logo em disk errado** vira imagem quebrada | `->disk('public')` explícito, e leitura por `Storage::disk('public')->url()`, o padrão de `User::getFilamentAvatarUrl()` (`User.php:276-281`) |
| **`setPageConfig()` apagar a configuração da mídia** por substituir o objeto inteiro | usar `getPageConfig()` + `mergeWith()`. CT-B03 assere que o alternador de tema e a posição da mídia sobrevivem |
| **Cache de config/rotas** esconder a feature em produção | nada aqui depende de `config()`; a cor vem do banco a cada request |
| **`ColorManager` cacheia em `$cachedColors`** (`ColorManager.php:78`) | o cache é por request (o manager é singleton do container, que morre no fim do request). Um request = um tenant, então é seguro. **Anotado como premissa a verificar** no passo 6 |

## Channel de Log da Feature

### Verificação de Channel Existente

`config/logging.php` lido. Existem os channels `autenticacao` (:101-107) e **`tenancy`**, este
usado por `DefinirTenantDePermissoes.php:42`.

### Decisão

**Nenhum channel novo. Usar o `tenancy` que já existe.**

A feature é sobre o tenant corrente do request — exatamente o assunto do channel `tenancy`, e o
middleware que ela toca já loga nele. Criar `identidade-visual` separaria em dois arquivos duas
metades da mesma pergunta ("qual era o tenant deste request, e o que ele pintou").

Os pontos de log estão especificados passo por passo abaixo. Todos em
`Log::channel('tenancy')`, no formato `[Classe@Método]`, com context estruturado.

## Estrutura de Implementação

### 1. Migration — as duas colunas

> Skills: `laravel-best-practices`

- **Path**: `database/migrations/2026_08_14_000003_add_identidade_visual_to_tenants_table.php`
  (segue o padrão de nome de `2026_08_14_000002_add_lembretes_to_convites_table.php`)
- Docblock no topo explicando **por que uma cor só**: `Color::generatePalette()` deriva as 11
  shades de um hex, então guardar a paleta seria guardar dado derivado.

```php
Schema::table('tenants', function (Blueprint $table): void {
    // Hex de 7 caracteres (#RRGGBB). A paleta de 11 shades é DERIVADA em runtime por
    // Color::generatePalette() — guardá-la aqui seria guardar dado calculável.
    $table->string('cor_primaria', 7)->nullable()->after('ativo');
    // Path relativo no disk `public`, no mesmo formato de users.avatar_url.
    $table->string('logo')->nullable()->after('cor_primaria');
});
```

- `down()`: `dropColumn(['cor_primaria', 'logo'])`
- **Nulo é o estado neutro**: sem cor, o painel usa o default do Filament; sem logo, a mídia base.
  Não há valor "desligado" além do nulo.
- **Logs**: nenhum — migration.

### 2. Model `Tenant` — fillable, cast e o acessor da logo

> Skills: `eloquent-best-practices`

- **Path**: `app/Models/Tenant.php`
- Acrescentar `'cor_primaria'` e `'logo'` a `$fillable` (hoje `:72-76`).
- **Sem cast novo**: os dois são string. O `casts()` (`:106-111`) continua só com `'ativo'`.
- Acrescentar o acessor da URL, espelhando `User::getFilamentAvatarUrl()` (`User.php:276-281`),
  que é o padrão do kit para arquivo em disk:

```php
/**
 * URL pública da logo, ou null quando a organização não enviou uma.
 *
 * Mesma forma de `User::getFilamentAvatarUrl()`: o banco guarda o path relativo, e o disk
 * resolve a URL. `public` explícito porque o disk default é `local`, que não é servível.
 */
public function urlDaLogo(): ?string
{
    return $this->logo
        ? Storage::disk('public')->url($this->logo)
        : null;
}
```

- Atualizar o `@property` do docblock da classe (`:41-46`).
- **Logs**: nenhum — model sem lógica de fluxo.

### 3. `TenantFactory` + página `view` + permissions

> Skills: `laravel-best-practices`, `pest-testing`

- **Path**: `database/factories/TenantFactory.php` — os campos novos entram como `null` no
  `definition()`, e um state novo `comIdentidadeVisual()` para os testes:

```php
public function comIdentidadeVisual(string $cor = '#7c3aed'): static
{
    return $this->state(fn (array $attributes): array => [
        'cor_primaria' => $cor,
    ]);
}
```

- **Path**: `app/Filament/Admin/Resources/Tenants/Pages/ViewTenant.php` — criar com
  `php artisan make:filament-page ViewTenant --resource=TenantResource --type=ViewRecord --panel=admin --no-interaction`.
  Herdar o `use HasResizableColumn` **não** se aplica aqui (é de listagem).
- **Path**: `TenantResource.php` — registrar a página em `getPages()` (`:110-114`):
  `'view' => ViewTenant::route('/{record}')`.
- **Path**: `TenantsTable.php:37-39` — acrescentar `ViewAction::make()` antes do `EditAction`.
  **Não** passar `->url()`: `Page.php:382-389` resolve sozinho agora que a página existe. Passar
  URL à mão seria duplicar o que o framework faz.
- **Rodar** `php artisan db:seed --class=ShieldPermissionsSeeder` para gerar `View:Tenant`, senão
  o papel `admin` toma 403 na tela nova.
- **Critério de aceite**: `/admin/organizacoes/{id}` abre para `admin` e para `master_global`.
- **Logs**: nenhum.

### 4. `TenantForm` — a Section de identidade visual

> Skills: `tailwindcss-development`, `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php`
- Acrescentar uma `Section` **depois** da "Identificação" existente:

```php
Section::make('Identidade visual')
    ->description('Cor e logo desta organização. Aplicadas no painel de negócio dela — as demais organizações não são afetadas.')
    ->columns(2)
    ->components([
        ColorPicker::make('cor_primaria')
            ->label('Cor primária')
            ->hex()
            ->helperText('O Filament deriva as 11 tonalidades desta cor e escolhe a legível por contraste. Em branco, usa a cor padrão da aplicação.'),

        FileUpload::make('logo')
            ->label('Logo')
            ->image()
            ->disk('public')
            ->directory('organizacoes/logos')
            ->visibility('public')
            ->maxSize(1024)
            ->helperText('Exibida na tela de bloqueio de sessão do painel de negócio. Em branco, usa a imagem padrão.'),
    ]),
```

- **`->visibility('public')`, e não `->visible('public')`.** O Breezy escreve `->visible('public')`
  (`vendor/jeffgreco13/filament-breezy/src/Concerns/Plugin/HasMyProfile.php:64`), que é **bug do
  vendor**: `visible()` espera `bool|Closure`, a string é só truthy, e a visibility nunca é
  declarada. Funciona lá por acidente, porque o disk `public` já tem `'visibility' => 'public'`.
  Não copiar.
- **RQ-07 se cumpre aqui**, e só aqui: a `Section` é o ponto de extensão óbvio. O docblock do
  arquivo diz onde acrescentar campos próprios. Nenhum campo é inventado — ver ADR-05.
- **Logs**: nenhum — form declarativo.

### 5. O carregamento da cor no painel `/app` — o passo central

> Skills: `laravel-specialist`, `ponytail`

- **Path**: `app/Providers/Filament/AppPanelProvider.php`
- Dentro de `bootUsing()` (que já existe, `:87-105`), registrar a cor **via `FilamentColor`**, e
  **não** via `$panel->colors()`:

```php
/*
 * Cor da organização corrente. `FilamentColor::register()` e NÃO `$panel->colors()`:
 * o segundo avalia a Closure em `Panel::boot()` (Panel.php:95), disparado pelo primeiro
 * middleware da pilha (SetUpPanel), quando o IdentifyTenant ainda não rodou e
 * `Filament::getTenant()` é null. O `register()` guarda a Closure e a avalia no
 * `@filamentStyles` (AssetManager.php:286), depois de todo middleware.
 *
 * A guarda dupla existe porque `FilamentColor` é GLOBAL, não por painel: sem ela, a cor
 * de um cliente pintaria /admin e /infra também.
 */
FilamentColor::register(function (): array {
    $tenant = Filament::getTenant();

    if (Filament::getCurrentPanel()?->getId() !== 'app' || ! $tenant?->cor_primaria) {
        return [];
    }

    return ['primary' => $tenant->cor_primaria];
});
```

- **Uma cor, não uma paleta**: o `ColorManager` chama `Color::generatePalette()` sozinho quando
  recebe string (`ColorManager.php:84-85`). Chamá-la aqui seria refazer o trabalho dele.
- **Array vazio é o neutro**: `getColors()` faz `foreach` sobre o resultado
  (`ColorManager.php:82`), então `[]` não sobrescreve nada e o default sobrevive.
- **Verificar a premissa do cache** (`ColorManager.php:78`): confirmar por CT-B que dois requests
  de tenants diferentes no mesmo processo recebem cores diferentes. Está no CT-B04 e é o risco
  técnico mais fino do plano.
- **Logs**:
  - `Log::channel('tenancy')->debug('[AppPanelProvider@bootUsing] Cor da organização aplicada | tenant: {id}', ['tenant_id' => …, 'cor_primaria' => …])` — só quando aplica, para não poluir com o caso neutro.

### 6. Gravar o tenant corrente na sessão

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Http/Middleware/DefinirTenantDePermissoes.php`
- **Uma linha**, no método que já tem o tenant em mãos (`:33`):

```php
// Guardado para quem NÃO recebe o tenant pela rota: a lock-screen registra
// `/app/screen/lock` com o path do painel e sem o tenantMiddleware
// (vendor/marjose123/filament-lockscreen/routes/web.php), então lá o
// `Filament::getTenant()` é null e esta é a única fonte. Ver ADR-03.
session(['tenant_corrente' => $tenant?->getKey()]);
```

- Atualizar o docblock da classe: ela deixa de ter uma responsabilidade só, e isso precisa estar
  escrito. Nome mantido — renomear tocaria `AppPanelProvider` e os testes de tenancy por ganho
  cosmético.
- **Logs**: o `debug` que já existe (`:42-48`) passa a incluir a gravação no context, sem linha
  nova de log.

### 7. A logo na lock-screen

> Skills: `laravel-specialist`

- **Path**: `app/Filament/Pages/Auth/TelaBloqueio.php`
- Sobrescrever `getAuthDesignerConfig()` — `public`, não-final, vindo da trait
  (`HasAuthDesignerLayout.php:20`):

```php
/**
 * O config do Auth Designer com a logo da organização no lugar da mídia base.
 *
 * Sobrescreve o método da trait `HasAuthDesignerLayout` (:20), e funciona porque o blade lê o
 * config no RENDER, na primeira linha (auth.blade.php:6) — não no boot do plugin.
 *
 * `mergeWith()` e não `setPageConfig()`: o setter do repositório SUBSTITUI o objeto inteiro
 * (AuthDesignerConfigRepository.php:42), o que apagaria mediaPosition, mediaSize e o
 * alternador de tema que o AppPanelProvider configurou.
 *
 * Falha para a mídia base quando não há tenant ou não há logo — mostrar a logo do cliente
 * errado é pior que mostrar a genérica. É por isso que a guarda checa o painel também: a
 * mesma classe serve /admin e /infra, que não têm tenant.
 */
public function getAuthDesignerConfig(): AuthDesignerConfig
```

- Lógica: se painel corrente é `app`, e `session('tenant_corrente')` resolve um `Tenant` com
  `logo`, então devolver o config com a mídia trocada; senão, devolver o da trait, intacto.
- **Logs**:
  - `Log::channel('tenancy')->debug('[TelaBloqueio@getAuthDesignerConfig] Logo da organização aplicada na tela de bloqueio | tenant: {id}', ['tenant_id' => …, 'painel' => …])`
  - `Log::channel('tenancy')->debug('[TelaBloqueio@getAuthDesignerConfig] Sem logo de organização, usando a mídia base | motivo: {sem_tenant|sem_logo|painel_sem_tenancy}', ['motivo' => …, 'painel' => …])` — o caso neutro **é** logado aqui, ao contrário do passo 6, porque a pergunta "por que apareceu a logo genérica?" é a que alguém vai fazer.

### 8. Infra de teste: a pasta `tests/BrowserTenancy/`

> Skills: `pest-testing`

**Este passo nasceu da revisão pós-escrita, não do plano original** — apareceu ao escrever o `05`.

Os CT-B desta feature precisam de `kit.tenancy.enabled` para alcançar `/app/{tenant}`. Mas
`tests/Browser` está registrado com `TestCase`, que é single-tenant
(`tests/Pest.php` → bloco `->in('Browser')`), e **o Pest não permite dois TestCases na mesma
pasta** — a mesma restrição que criou `tests/Tenancy/` separado de `tests/Kit/`, documentada em
`tests/Pest.php:44-61`.

- **Path**: `tests/Pest.php` — bloco novo, depois do de `Browser`:

  ```php
  pest()->extend(TenancyTestCase::class)
      ->use(RefreshDatabase::class)
      ->group('browser')
      ->in('BrowserTenancy');
  ```

  Grupo `browser` (não `browser-tenancy`): continua fora do `composer test:kit` e dentro do
  `--testsuite=Browser`.

- **Path**: `phpunit.xml` — acrescentar `<directory>tests/BrowserTenancy</directory>` **dentro** da
  testsuite `Browser` que já existe. Sem isso, `--testsuite=Browser` não vê a pasta nova e os CT-B
  passam por não existirem.
- **Critério de aceite**: `vendor/bin/pest --testsuite=Browser` conta os cenários das duas pastas,
  e `vendor/bin/pest --group=kit` continua em 214.
- **Logs**: nenhum.

### 9. Testes

> Skills: `pest-testing`

- Especificação em `04-casos-de-teste.md` e `05-casos-de-teste-browser.md`.
- **Ordem obrigatória**, e ela tem duas razões distintas:
  1. **Regressão da ancestral primeiro** — CT-11 (`PaineisTest`, pela matriz de permissões) e
     CT-12 (`BloqueioDeSessaoTest`, pelo layout). Escrever teste novo sobre suíte quebrada não
     mede nada.
  2. **CT-B01 antes de tocar no `TenantForm`** — ele responde a ambiguidade de RQ-09, e a
     resposta decide se há algo a corrigir no `EditAction`. Ver ADR-06, que traz a tabela de
     interpretação do resultado. Vermelho aqui é achado, não falha do ciclo.
- Os CT-B vão delegados a sub-agente, conforme a skill.

### 10. Commits

Um commit por passo entregável: migration+model → página view → form → cor no `/app` → sessão →
logo na lock-screen → infra de teste + testes → wiki.

> O passo que verificava o modal por CT-B foi absorvido pelo passo 9 depois do
> `/ponytail:ponytail-review`: ele não produzia código, e o CT-B já nasce ali.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`.** A escada, aplicada aqui:
> 1. **Reutilizar**: o middleware do passo 7 já existe e já tem o tenant — 1 linha, não um
>    listener novo. O acessor do passo 2 copia a forma de `User::getFilamentAvatarUrl()`.
> 2. **Feature nativa**: `Color::generatePalette()` faz a paleta; `Page.php:382-389` resolve a URL
>    do `ViewAction`. Não reimplementar nenhum dos dois.
> 3. **Uma coluna, não onze**: a paleta é derivada.
> 4. **Mínimo que funciona**: nenhum service, nenhum DTO, nenhuma abstração de "tema". Duas
>    colunas, um form, dois pontos de leitura.
>
> Atalhos deliberados com `ponytail:` comment. Depois, `/ponytail:ponytail-review` no diff.

## Testes

> `04-casos-de-teste.md` — backend: fillable, acessor, autorização da tela nova, logs, e a
> regressão do recorte por organização.
> `05-casos-de-teste-browser.md` — CT-B: o modal (passo 4), a cor no `/app`, o não-vazamento para
> `/admin`, e a logo na lock-screen.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse`
- [ ] `vendor/bin/pest --parallel --group=kit` — 214/214 + os CT novos
- [ ] `vendor/bin/pest --testsuite=Browser` — em série
- [ ] **Regressão da ancestral** (natureza = evolução): `tests/Tenancy/*` e
      `tests/Kit/AdminDaOrganizacaoTest.php` verdes
- [ ] Roteiro *Desenhado × Implementado* do `05` preenchido
- [ ] `feature-quality-gate` invocado

## Commits

- `:sparkles: identidade visual da organizacao: cor e logo no registro`
- `:sparkles: pagina view do resource de organizacoes`
- `:sparkles: cor da organizacao aplicada no painel de negocio`
- `:sparkles: logo da organizacao na tela de bloqueio`
- `:white_check_mark: CT e CT-B da identidade visual`
- `:memo: wiki: identidade visual da organizacao`

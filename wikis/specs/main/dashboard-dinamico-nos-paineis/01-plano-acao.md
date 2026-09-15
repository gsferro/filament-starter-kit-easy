# Plano de Ação — Dashboard dinâmico nos painéis

> PRD. Deriva de `00-requisito.md`; cada passo declara as cláusulas RQ que
> cobre. Minucioso o bastante para implementar sem ambiguidade.

## Visão geral

O `mddev31/filament-dynamic-dashboard` vira o dashboard dos painéis do kit,
atrás de um interruptor editável em `/admin/configuracoes-da-aplicacao`.
Desligado, cada painel responde o `Filament\Pages\Dashboard` de sempre; ligado,
a raiz do painel passa a responder a página dinâmica (grade GridStack com
widgets que o usuário monta, move e redimensiona). A troca é por REQUEST —
nenhuma rota nasce ou morre.

## 1. Configuração (`config/kit.php` + `.env.example`) — RQ-03, RQ-08

Bloco novo `dashboard_dinamico`, seguindo o padrão documentado do arquivo
(comentário de bloco `|---|` explicando default, precedência banco × `.env` e
o motivo do default desligado):

```php
'dashboard_dinamico' => [
    'habilitado' => filter_var(env('KIT_DASHBOARD_DINAMICO', false), FILTER_VALIDATE_BOOLEAN),
    'paineis'    => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('KIT_DASHBOARD_DINAMICO_PAINEIS', '')),
    ))),
],
```

- `habilitado` — `filter_var`, NÃO `(bool) env()`: interruptor que muda a tela
  de entrada de todo painel falha FECHADO (mesmo argumento do bloco de login
  social em `config/kit.php`). Default `false` é o que torna o `kit:update`
  inerte (RQ-08).
- `paineis` — lista de ids separados por vírgula, mesma forma de
  `kit.login.*.paineis`. **Vazio significa TODOS os painéis registrados**, e a
  tradução é de quem lê (`App\Support\DashboardDinamico`), não da config.
- `.env.example` ganha as duas chaves comentadas.

## 2. Decisor por request — `App\Support\DashboardDinamico` — RQ-03, RQ-07

Classe `final` com dois métodos:

```php
public static function habilitado(?Panel $painel = null): bool
public static function habilitadoPara(string $panelId): bool
```

- `habilitado()`: `$painel` default `Filament::getCurrentPanel()`; regra
  `config('kit.dashboard_dinamico.habilitado')` **E** (`paineis` vazio **OU**
  `$painel->getId()` na lista). Painel `null` (console, queue) → decide só
  pela flag global.
- `habilitadoPara($panelId)`: a mesma conjunção, mas com o id explícito — é o
  que o `TenantObserver` e o `DashboardPadraoSeeder` consultam, porque eles
  rodam FORA de contexto de painel e o gate correto é por painel, não pela
  flag global (P5, CT-27).
- **Por que existe**: os painéis são montados no `register()` dos
  PanelProviders e `ConfiguracoesDoKit::aplicarNaConfig()` roda no `boot()` do
  `KitServiceProvider` — o valor do banco chega DEPOIS do painel montado
  (`.ai/rules/settings.md`). Toda decisão liga/desliga passa por aqui, por
  request: `mount()`, `canAccess()`, `shouldRegisterNavigation()`. Nenhum
  `->pages([...])` condicional.

## 3. As duas páginas por painel — RQ-02, RQ-03, RQ-04

Cada painel do kit passa a registrar DUAS páginas, sempre as duas, no
`->pages([...])` do provider:

| Página | Estende | Rota | Papel |
|---|---|---|---|
| `App\Filament\{P}\Pages\Dashboard` | `MDDev\DynamicDashboard\Pages\DynamicDashboard` | `/` | dinâmica |
| `App\Filament\Pages\DashboardClassico` | `Filament\Pages\Dashboard` | `/` (raiz) | fallback *(alterado em 2026-09-15: era `/inicio`; ver ADR-02)* |

`{P}` ∈ `App`, `Admin`, `Infra` — **a dinâmica é uma subclasse por painel,
nunca uma classe compartilhada**: o escopo `available()` do pacote filtra por
`dashboards.page = static::class` (`vendor/.../src/Models/Dashboard.php:218-222`),
então a FQCN da página É a fronteira entre os dashboards de painéis diferentes.
A clássica é o contrário: **uma classe só**, registrada nos três painéis — ela
não grava `dashboards.page`, e o próprio kit já registra a mesma
`Filament\Pages\Dashboard` nos três hoje.

### 3.1 `Dashboard` (dinâmica), em cada painel

```php
final class Dashboard extends DynamicDashboard
{
    protected static string $routePath = '/';

    public static function getRoutePath(Panel $panel): string
    {
        return static::$routePath;
    }

    public function mount(): void
    {
        if (! DashboardDinamico::habilitado()) {
            $this->redirect(DashboardClassico::getUrl(), navigate: true);
            return;
        }
        parent::mount();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return DashboardDinamico::habilitado();
    }

    public static function canEdit(): bool
    {
        return auth()->user()?->can('Manage:Dashboard') ?? false;
    }
}
```

- `mount()` roda por request, depois de `canAccess()` — é o decisor. Desligado,
  `/dashboard-dinamico` redireciona para a raiz em vez de 403: a URL canônica do painel
  nunca quebra (RQ-04, sentido "desligar com dado gravado")
  *(alterado em 2026-09-15: a dinâmica saiu da raiz; ver ADR-02)*.
- `shouldRegisterNavigation()` é avaliado por request na montagem do menu —
  desligado, o item some e o da clássica aparece no lugar.
- `canEdit()` é o gancho que o pacote consulta em TODA superfície de escrita:
  botão "Add Widget" (`DynamicDashboard.php:751`), ação "Manage"
  (`:858`), `createWidget()` (`:782`) e o modo drag do GridStack
  (`canDrag = canEdit AND !is_locked`, `:355`). Sem override ele é `true`
  para qualquer um (`:189`) — o override é obrigatório, não opcional (RQ-06).
- `mount()` já existe na página do vendor (`:90`) — o override PRECISA chamar
  `parent::mount()` no caminho habilitado, senão a página não inicializa.
- `canAccess()` NÃO é sobrescrito: a página responde sempre, e quem decide é o
  `mount()`. As 4 páginas novas entram em `shield.pages.exclude` (P11): o gate
  de página do Shield é opt-in via `HasPageShield` e o kit já exclui
  `Filament\Pages\Dashboard` (`config/filament-shield.php:327`) — "ver" é
  livre, "gerenciar" é `Manage:Dashboard`.

### 3.2 `DashboardClassico`, em cada painel

```php
final class DashboardClassico extends FilamentDashboard
{
    // Sem $routePath: herda '/' do Filament\Pages\Dashboard
    // (alterado em 2026-09-15; ver ADR-02)

    public function mount(): void
    {
        if (DashboardDinamico::habilitado()) {
            $this->redirect(Dashboard::getUrl(), navigate: true);
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        return ! DashboardDinamico::habilitado();
    }
}
```

- Espelho simétrico: ligado, a raiz devolve para `/dashboard-dinamico`. As duas páginas
  existem sempre; a config escolhe qual responde de verdade.
- Herda os widgets clássicos do painel (`getWidgets()`/`getVisibleWidgets()`)
  sem uma linha a mais.

### 3.3 Providers

Nos três `*PanelProvider`, trocar `Dashboard::class` (o `Filament\Pages\Dashboard`)
pelo par `Dashboard::class` (a dinâmica do painel) + `DashboardClassico::class`.
Ajustar o `use` — hoje `use Filament\Pages\Dashboard;` aponta para o vendor.

**Nota de rota — confirmada no vendor** *(alterada em 2026-09-15; ver ADR-02)*:
`Filament\Pages\Dashboard` ocupa `/` via `protected static string $routePath = '/'`
+ `getRoutePath()` próprio (`vendor/filament/filament/src/Pages/Dashboard.php:21,39-42`)
— e é dele que a CLÁSSICA herda a raiz, sem escrever uma linha. A dinâmica não
declara rota: declara `$slug = 'dashboard-dinamico'` e cai no `$slug` de `HasRoutes`
(`Pages/Concerns/HasRoutes.php:50-53`). Detalhe de tenancy: em painel com
`hasTenancy()`, a rota `/` é registrada como `->fallback()`
(`HasRoutes.php:46-47`) — herdado de graça, agora pela clássica.

## 4. Widgets compatíveis — `App\Filament\Concerns\WidgetDinamico` — RQ-02, RQ-07

Trait compartilhado (não por painel — `App\Filament\Concerns`, não
`App\Filament\App\Concerns` como na referência, porque serve a qualquer painel):

```php
trait WidgetDinamico
{
    use HasEmptySettings;
    use HasSizeDefaults;

    public static function getWidgetLabel(): string
    {
        return static::$widgetLabel ?? class_basename(static::class);
    }
}
```

- O widget do projeto declara `implements DynamicWidget` + `use WidgetDinamico`
  e opcionalmente `protected static ?string $widgetLabel` /
  `protected static ?int $defaultHeight`.
- A descoberta é automática por painel (`panel->getWidgets()` filtrado por
  `is_subclass_of(DynamicWidget::class)`,
  `vendor/.../DynamicDashboard.php:597-614`): widget registrado no painel e
  compatível entra no seletor sem configuração. Painel novo do projeto segue o
  mesmo caminho (RQ-07).
- O kit NÃO embarca widget de negócio: o `/app` nasce vazio por desenho
  (`config/kit.php`, bloco `demo`). O trait é a superfície que o kit entrega;
  quem instala cria os widgets do seu domínio.

## 5. Permissão de gestão — `Manage:Dashboard` — RQ-06

- `config/filament-shield.php` → `custom_permissions` ganha
  `'manage:dashboard' => 'Gerenciar dashboards dinâmicos'` (verificar o formato
  exato: as existentes são `minúsculo:modelo` → rótulo; o nome final segue
  `permissions.separator`/`case` — o seeder resolve pelo Shield, nunca por
  string montada à mão) **e** `pages.exclude` ganha as 4 classes novas
  (`DashboardClassico` + as 3 `Dashboard` dinâmicas) — P11.
- `PapeisSeeder`: custom permission não tem noção de painel — o
  `getEntitiesPermissions()` faz merge na matriz de TODOS os painéis
  (docblock do seeder, `HasEntityTransformers.php:88-112`). Então:
  - entra no mapa painel × custom permission do seeder, marcada para os
    painéis onde a feature vale;
  - é SUBTRAÍDA do `panel_user` (mesmo mecanismo das outras subtrações) —
    usuário comum do `/app` VÊ o dashboard mas não monta nada;
  - `admin_app` a recebe de graça pela matriz completa do painel.
- Ressemeio obrigatório documentado: `php artisan db:seed
  --class=Database\Seeders\ShieldPermissionsSeeder` (o próprio
  `config/filament-shield.php:253-255` já avisa que sem isso a permissão fica
  invisível sem erro).
- `canEdit()` da página consulta `->can('Manage:Dashboard')` — o nome exato
  sai do que o Shield gerar; o teste de contrato assere a string.

## 6. Tenancy — RQ-05

### 6.1 Migration

`database/migrations/xxxx_add_tenant_id_to_dashboards_table.php` — cópia da
referência: `foreignId('tenant_id')->nullable()->after('page')
->constrained('tenants')->nullOnDelete()`, guardada por
`Schema::hasColumn('dashboards', 'tenant_id')`. Nullable de propósito: sem
tenancy a coluna fica `null` e nada muda.

### 6.2 Escopo — em `KitServiceProvider` (não `AppServiceProvider`)

Método `escoparDashboardsPorTenant()` chamado no `boot()`, generalizando a
referência (que fixa `getId() !== 'app'`):

```php
$model = DashboardModelHelper::model(); // resolve DashboardWithRoles quando
                                      // use_spatie_permissions está ligado

$model::addGlobalScope('tenant', function (Builder $builder): void {
    $painel = filament()->getCurrentPanel();
    if (! $painel?->hasTenancy()) {
        return;
    }
    $tenant = Filament::getTenant();
    if ($tenant) {
        $builder->where('dashboards.tenant_id', $tenant->getKey());
    }
});

$model::creating(function (Dashboard $dashboard): void {
    $painel = filament()->getCurrentPanel();
    if (! $painel?->hasTenancy()) {
        return;
    }
    $tenant = Filament::getTenant();
    if ($tenant) {
        $dashboard->tenant_id = $tenant->getKey(); // sobrescreve SEMPRE (P10)
    }
});
```

- O scope/hook vão na classe RESOLVIDA por `DashboardModelHelper::model()`,
  não em `Dashboard::class` fixo: global scope e evento registrados no pai NÃO
  alcançam `DashboardWithRoles` (P7).
- `hasTenancy()` em vez de id fixo: vale para `/app` hoje e para qualquer
  painel tenant-aware que o projeto adicionar (RQ-07). Painel sem tenancy
  (`/admin`, `/infra`) sai no primeiro `return` — dashboards deles são globais.
- `tenant_id` NÃO entra no `$fillable` do model do vendor
  (`Models/Dashboard.php:44-54`) — mass assignment já é seguro; o hook
  sobrescreve SEMPRE que há tenant corrente para cobrir atribuição direta
  (P10). Ninguém grava tenant por formulário.
- `DashboardWidget` não precisa de escopo próprio: é filho de `Dashboard`,
  sempre alcançado via `dashboard_id` dentro de um dashboard já escopado.
- `use_spatie_permissions` do pacote fica LIGADO (P7): é o eixo de
  VISIBILIDADE por papel — `canDisplay()` filtra por roles
  (`DynamicDashboard.php:164-176`) e o `DashboardManager` ganha o seletor de
  roles. Edição continua sendo `canEdit()` — eixos distintos. Dashboard sem
  roles fica visível a todos, então o semeado não exclui ninguém.

### 6.3 Dashboard padrão por tenant

- `App\Services\Dashboard\CriadorDeDashboardPadrao::para(?Tenant $tenant)` —
  porte da referência, com duas diferenças: aceita `null` (instalação sem
  tenancy → dashboard global) e `widgets()` devolve `[]` por default (o kit não
  tem widgets de negócio; o método existe para o projeto sobrescrever).
- `App\Observers\TenantObserver@created` → `CriadorDeDashboardPadrao::para($tenant)`
  **somente se** `DashboardDinamico::habilitadoPara('app')` — o gate é por
  PAINEL, não pela flag global: flag ligada com `paineis=['admin']` não pode
  semear dashboard órfão do `/app` (P5, CT-27). Registrar o observer no
  `KitServiceProvider`.
- O observer/seeder cobrem os painéis do kit com `hasTenancy()` (hoje: `app`) —
  a linha precisa de `page = <FQCN>` e painel do projeto tem FQCN que o kit
  não conhece; painel do projeto semeia o seu (P6).
- `Database\Seeders\DashboardPadraoSeeder` — idempotente, porte da referência:
  `--tenant={id}` para um, ou `chunkById` em todos; sem tenancy, cria o global
  se não existir. É o caminho de backfill para quem liga a feature depois de
  já ter tenants (RQ-04).

## 7. Tela de settings — RQ-03

`ConfiguracoesDoKit` (a página do /admin), aba **Kit**, seção nova
"Dashboard dinâmico":

- `Toggle::make('dashboard_dinamico_habilitado')` — "Dashboard dinâmico nos
  painéis", helper explicando que desligar preserva os dashboards montados.
- `CheckboxList::make('dashboard_dinamico_paineis')` — options de
  `Paineis::opcoes()` (que sai de `Filament::getPanels()`, então painel novo do
  projeto aparece sozinho — RQ-07), helper "Vazio = todos os painéis",
  `->visible(fn (Get $get) => $get('dashboard_dinamico_habilitado'))`.

E o contrato de TRÊS lugares de `App\Settings\ConfiguracoesDoKit`:

1. propriedades `public bool $dashboard_dinamico_habilitado;` e
   `public array $dashboard_dinamico_paineis;`;
2. linhas no `mapaDeConfiguracao()` → `kit.dashboard_dinamico.habilitado` e
   `kit.dashboard_dinamico.paineis`;
3. migration de settings NOVA (`add()`) semeando a partir de `config()` —
   nunca editar migration já rodada (docblock da classe, ADR-05 da wiki
   `verificacao-de-email-editavel`).

## 8. Cenários de liga/desliga (RQ-04) — comportamento esperado

| Cenário | Resultado |
|---|---|
| Update do kit, flag ausente | Tudo igual: `/` responde a clássica, nada novo na tela |
| Liga no /admin, sem tenancy | `/` passa a responder a dinâmica; sem dashboards → grade vazia + "Add Widget" para quem tem `Manage:Dashboard`; seeder cria o "Padrão" global |
| Liga com tenancy | Idem por tenant; `TenantObserver` cobre tenants novos, seeder cobre os existentes |
| Desliga depois de montado | `/` volta à clássica; linhas em `dashboards`/`dashboard_widgets` ficam intactas e voltam ao religar — desligar NUNCA apaga dado |
| Liga só para alguns painéis | `paineis=['app']` → `/admin` e `/infra` seguem clássicos; o decisor é por painel |
| Painel novo do projeto | Registra o par de páginas + widgets com a trait; entra na lista de `paineis` (ou em "todos" se vazia) |

## 9. Arquivos tocados (resumo)

- `config/kit.php`, `.env.example`
- `app/Support/DashboardDinamico.php` (novo)
- `app/Filament/Concerns/WidgetDinamico.php` (novo)
- `app/Filament/{App,Admin,Infra}/Pages/Dashboard.php` (novo ×3)
- `app/Filament/Pages/DashboardClassico.php` (novo ×1, compartilhada)
- `app/Providers/Filament/{App,Admin,Infra}PanelProvider.php`
- `app/Providers/KitServiceProvider.php` (scope + observer)
- `app/Services/Dashboard/CriadorDeDashboardPadrao.php` (novo)
- `app/Observers/TenantObserver.php` (novo)
- `database/seeders/DashboardPadraoSeeder.php` (novo)
- `database/migrations/*_add_tenant_id_to_dashboards_table.php` (novo)
- `database/migrations/*_add_dashboard_dinamico_to_kit_settings.php` (novo)
- `app/Settings/ConfiguracoesDoKit.php` (2 propriedades + 2 linhas no mapa)
- `app/Filament/Admin/Pages/ConfiguracoesDoKit.php` (seção na aba Kit)
- `config/filament-shield.php` (custom permission)
- `database/seeders/PapeisSeeder.php` (mapa + subtração)

## 10. Fora de escopo

- Templates customizados em `resources/dashboard-templates` — o pacote já
  varre esse diretório por default; quem quiser cria, sem código do kit.
- Widgets de negócio embarcados — o `/app` nasce vazio por desenho.
- Migrar dashboards entre painéis ou entre tenants pela UI.
- Backfill automático ao ligar o toggle — o seeder roda por artisan (P9).

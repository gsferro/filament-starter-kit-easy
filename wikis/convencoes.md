# Convenções e invariantes

> O documento mais importante desta wiki. Cada item aqui é uma decisão tomada, com custo pago. Reverter sem entender o porquê quebra algo — às vezes em silêncio.

## Invariantes de model

### UUID na rota, `id` int como PK

Toda tabela nova ganha `$table->uuid('uuid')->unique()` e o model usa `App\Traits\TemUuid`.

```php
// migration
$table->id();
$table->uuid('uuid')->unique();

// model
use App\Traits\TemUuid;

class Produto extends Model
{
    use TemUuid;

    protected $fillable = ['nome'];   // `uuid` fica FORA do fillable
}
```

- **Por quê:** `id` int mantém joins e índices baratos; `uuid` como route key faz URL com id numérico devolver 404 nativo e impede enumerar registros por sequência.
- **Atenção:** UUID **não é autorização**. Policies continuam obrigatórias.

### Auditoria no que é editável

Model auditável usa `App\Traits\AuditsFillables` e implementa `OwenIt\Auditing\Contracts\Auditable`.

```php
class Produto extends Model implements Auditable
{
    use AuditsFillables;
}
```

O trait faz `getAuditInclude()` devolver o `$fillable` — a trilha registra exatamente o que o usuário pode alterar, sem vazar colunas técnicas (tokens, contadores, caches). A trilha aparece em `/infra/audits`.

### Model de negócio pertence a um tenant

**Vale só no modo multi-tenant** (`php artisan kit:tenancy`). Toda model do negócio usa `App\Traits\BelongsToTenant`:

```php
// migration
$table->foreignId('tenant_id')->constrained();

// model
use App\Traits\BelongsToTenant;

class Projeto extends Model
{
    use BelongsToTenant;

    protected $fillable = ['nome'];   // `tenant_id` fica FORA — quem preenche é a trait
}
```

A trait entrega três coisas: a relação `tenant()` (que é o `ownershipRelationship` do Filament), um **escopo global** e o preenchimento automático de `tenant_id` ao criar.

- **Por que o escopo, se o Filament já escopa:** ele só escopa models que passam por um Resource. Job, comando, listener, widget e API ficam de fora — e é exatamente aí que vaza dado de um cliente para outro, em silêncio.
- **Sem tenant corrente, sem escopo.** Fora de request de painel a query volta a ser global, de propósito: um job que roda para todos os tenants precisa ver todos. Para um tenant só, filtre explícito ou chame `Filament::setTenant()`.
- **`withoutGlobalScopes()` derruba o escopo de tenant junto.** A própria doc do Filament avisa que isso "can lead to data leakage". Para uma query deliberadamente global, remova só este: `Model::withoutGlobalScope('tenant')`.
- **Escopo não é autorização.** Policies continuam obrigatórias.

### Validação em resource com tenancy: `scopedUnique()`

```php
TextInput::make('nome')->scopedUnique(ignoreRecord: true)   // ✅
TextInput::make('nome')->unique(ignoreRecord: true)         // ❌ ignora o tenant
```

As regras `unique` e `exists` do Laravel não passam pelo Eloquent, então não enxergam o escopo: um nome já usado por **outro cliente** bloquearia o cadastro aqui. O Filament oferece `scopedUnique()` e `scopedExists()` exatamente para isso.

### Exclusão de configuração é lógica

Registros de catálogo (ex.: `agentes_ia`) usam flag `ativo` em vez de `DELETE`. Desligar é dado, não destruição.

## Autorização

- **Nada de affordance sem permissão.** Menu, busca e ações consultam `canAccess()` / `canCreate()` antes de aparecer. Encontrar na UI algo que resulta em 403 é considerado **bug**, não detalhe.
- **Permissions vêm de seeder**, nunca do `shield:generate` interativo — é o que permite instalar sem intervenção. Depois de criar Resources novos:
  ```bash
  php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
  php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
  ```
- **`master_global` fica sem permissions no banco** — o acesso vem do `Gate::before`. Não "conserte" isso sincronizando permissions para ele.
- **Não implemente `canSwitchPanels()` no `User`.** O nome engana: não esconde painel nenhum, só mapeia URLs para `null` e deixa a lista renderizada. O recorte real é o `canAccessPanel()`, que o próprio Panel Switch consulta — painel inacessível some sozinho.

## Banco e seeders

- **Seeder nunca usa factory nem faker.** `fakerphp/faker` é `require-dev` e a imagem Docker roda `composer install --no-dev`: um seeder com faker quebra o deploy containerizado. Factory é para teste.
- **Seeders são idempotentes** (`findOrCreate`, `updateOrCreate`): rodar de novo depois de criar Resources é operação normal.
- **Ordem importa** em `DatabaseSeeder`: permissions → papéis → usuário → catálogo de IA.

## Testes

- Suíte do kit isolada em `tests/Kit/` (grupo `kit`); a sua em `tests/Feature` e `tests/Unit`.
- Toda mudança precisa de teste. Rode o mínimo necessário: `php artisan test --compact --filter=Nome`.
- Encostou na fundação (providers, traits, gates, painéis, camada de IA)? Rode `composer test:kit`.
- Não apague teste sem aprovação.

## Estilo e ferramentas

| Ferramenta | Comando | Quando |
|---|---|---|
| Pint | `vendor/bin/pint --dirty` | sempre, antes de finalizar mudança em PHP |
| PHPStan (larastan) | `composer types:check` | antes de PR |
| Suíte completa | `composer test` | pint + phpstan + testes |

Regras de PHP que o Boost já cobra e valem aqui: chaves sempre em estruturas de controle, promoção de propriedade no construtor, tipos de retorno e de parâmetro explícitos, `TitleCase` em chave de Enum, PHPDoc em vez de comentário inline.

## Idioma

- **UI, mensagens e nomes de domínio em pt-BR** — inclusive nos plugins que só trazem inglês.
- Tradução de plugin vai em `lang/vendor/<pacote>/pt_BR/`. **Nunca** editar `vendor/`.
- Nomes de classe do kit são em pt-BR quando representam domínio (`AgenteIa`, `PapeisSeeder`, `BadgeContagemNavegacao`); APIs de framework permanecem em inglês.

## Documentação

- Feature nova com lógica de negócio → invoque a skill `feature-wiki` **antes** de codar. Ela cria `wikis/specs/{branch}/{feature}/` com PRD, ADR, progresso e casos de teste.
- Mudou a fundação do kit (provider, trait, convenção, painel)? Atualize também esta wiki.
- `AGENTS.md` e `CLAUDE.md` são **gerados pelo Laravel Boost** — editar à mão é trabalho perdido no próximo `boost:update`. Regra durável e específica de path vai em `.ai/rules` (ferramenta `record-rule` do Boost).

## Padrão de log

Definido pela skill `feature-wiki` e válido para todo o projeto:

```php
Log::channel('nome-da-feature')->info(
    '[Classe@metodo] Ação executada | parametro: valor',
    ['id' => 1, 'exception' => $e]   // context estruturado, sempre
);
```

- Prefixo `[Classe@Método]` sem espaços dentro dos colchetes; mensagem em pt-BR descrevendo a **ação**.
- Cada feature ganha seu channel em `config/logging.php` (driver `daily`), para não poluir o log principal.
- Severidade: `fail()` de Livewire → `warning`; `catch` que interrompe o fluxo → `error`; `catch` tratado → `warning`; API/infra fora do ar → `critical`.
- Nunca `Log::info()` sem channel, nunca context vazio.

O detalhamento completo (exemplos, `Log::shareContext`, como testar log em Pest) está em `.claude/skills/feature-wiki/SKILL.md`.

## Armadilhas já resolvidas

Código que parece errado e **é deliberado**. Antes de "corrigir" qualquer linha desta tabela, leia o comentário no arquivo.

| Onde | O quê | Se você reverter |
|---|---|---|
| Lockscreen | registrado nos **três** painéis | o `routes/web.php` do pacote resolve o plugin pelo painel corrente e estoura `LogicException` em todo request — até `artisan package:discover` morre |
| `TelaBloqueio` redeclara `$layout` | parece redundante com a trait `HasAuthDesignerLayout` | a trait faz `static::$layout = ...`; sem storage próprio na subclasse a atribuição cai no estático de `Filament\Pages\Page` e o layout de login veste **toda** página Filament do processo (a de 2FA do Breezy morre em `getAuthDesignerConfig does not exist`) |
| `TelaBloqueio::mount()` sai por `HttpResponseException` | o pacote usa `redirect()` sem `return` | com o Redirector do Livewire já instalado no processo, o objeto chega onde o Laravel espera código HTTP: 500 em `GET /{painel}/screen/lock` com a sessão destravada — e essa URL fica em favorito/histórico |
| "Bloquear sessão" registrado em `bootUsing()` com `sort(-1)` | o item do pacote nasce sem `sort` | sem sort ele cai depois do alternador de tema, colado em "Sair"; no corpo de `panel()` não funciona — plugin boota antes e quem registra por último vence |
| Command Center | **sem** `->cluster()` | a página raiz devolve 500 (`Redirector could not be converted to int`) |
| `databaseNotifications()` | declarado **depois** de `plugins()` | o Notification Center apaga o recorte, sem erro nenhum |
| Dependency Graph | `canAccessUsing()` próprio | volta a regra local-only do pacote: 404 em homologação |
| Logs Explorer | `deletable(false)` | o delete do pacote faz `@unlink()` sem gravar rastro — apaga evidência |
| Ações de filtro | **fora** do `configureUsing()` global | em tabela sem filtro a ação nasce sem nome e derruba a página |
| Pulse + resized-column | carregados como ES module | os dois bundles declaram constantes no escopo global; o segundo morre calado (foi assim que os gráficos do Pulse sumiram) |
| Busca ⌘K | hook `GLOBAL_SEARCH_BEFORE` + overlay em `setTimeout` | `USER_MENU_BEFORE` renderiza dentro do dropdown; sem o `setTimeout` o próprio clique fecha o painel |
| Rótulos do Command Center | trocados em `bootUsing()`, nunca o slug | o `register()` do plugin escreve por cima; trocar slug por setter estático quebra a rota |
| `UsedDiskSpaceCheck` | pulado no Windows | o check do Spatie não suporta Windows |
| `$_SERVER` no Windows | reposto no `KitServiceProvider` | processos criados pela UI nascem sem `SystemRoot`/`PATH` e morrem com erro vazio de socket |
| Badge de resource de vendor | **não existe** | `getNavigationBadge()` é estático e o Filament não oferece API para sobrescrever de fora; forçar exige estender cada resource de vendor e quebra a cada update |
| `Tenant::CONTEXTO_GLOBAL = 0` | sentinela de papel global | `model_has_roles.team_id` é NOT NULL: sem o sentinela, atribuir papel em seeder, job ou nos painéis /admin e /infra estoura violação de constraint |
| `User::temPapelGlobal()` | troca o contexto de papéis | sem ela o `master_global` perde os poderes ao entrar num tenant — o spatie filtra a relação `roles` pelo team corrente |
| `->tenant()` depois de `->plugins()` | reescreve as rotas do painel | plugin registrado depois não enxerga o prefixo `/{tenant}` |
| Papéis criados com `roles.team_id` nulo | definição global | o `Role::findOrCreate` do spatie carimba o team corrente, e um papel carimbado no tenant A fica invisível no B |

## Onde cada coisa se configura

| Quero mudar | Arquivo |
|---|---|
| Acesso aos painéis | `app/Models/User.php` → `canAccessPanel()` |
| Gates de infra | `app/Providers/KitServiceProvider.php` → `configureGates()` |
| Matriz de permissões | `database/seeders/PapeisSeeder.php` |
| Health checks | `KitServiceProvider::configureHealthChecks()` |
| Defaults de tabela/modal/toggle | `app/Providers/Concerns/ConfiguraFilamentGlobal.php` |
| Comandos liberados na UI | `config/command-center.php` |
| Credenciais do seeder | `KIT_ADMIN_EMAIL` / `KIT_ADMIN_PASSWORD` no `.env` (defaults em `config/kit.php`) |
| Provider de IA | `AI_PROVIDER` no `.env` (`config/ai.php`) |
| Backups | `config/backup.php` + agendamento em `routes/console.php` |
| Cores de cada painel | `->colors([...])` no `*PanelProvider` |
| Arte do login | `public/images/auth/login.svg` |
| Ligar multi-tenancy | `php artisan kit:tenancy` (destrutivo — ver [arquitetura](arquitetura.md#multi-tenancy-opt-in)) |
| Termo do tenant na UI | `kit.tenancy.label` / `label_plural` / `slug` em `config/kit.php` |

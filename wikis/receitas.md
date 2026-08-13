# Receitas

> O passo a passo do que mais se faz neste kit. Cada receita já embute as convenções de [convencoes.md](convencoes.md) — seguir a receita é seguir a convenção.

## Model + tabela nova

```bash
php artisan make:model Produto -mf --no-interaction
```

1. **Migration** — `id()` + `uuid('uuid')->unique()`:
   ```php
   $table->id();
   $table->uuid('uuid')->unique();
   // ... suas colunas
   $table->timestamps();
   ```
2. **Model** — traits do kit e `uuid` fora do `$fillable`:
   ```php
   use App\Traits\AuditsFillables;
   use App\Traits\TemUuid;
   use OwenIt\Auditing\Contracts\Auditable;

   class Produto extends Model implements Auditable
   {
       use AuditsFillables;
       use TemUuid;

       protected $fillable = ['nome', 'preco'];
   }
   ```
3. **Policy** — `php artisan make:policy ProdutoPolicy --model=Produto`. UUID não autoriza nada; a policy sim.
4. **Factory** só para teste. **Seeder nunca usa factory nem faker.**

## Resource novo

```bash
php artisan make:filament-resource Produto --panel=app --no-interaction
```

Escolha o painel pelo público: `app` = negócio, `admin` = administração, `infra` = operação.

Depois:

```bash
php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
```

E acrescente os dois traits do kit ao que foi gerado:

```php
// No Resource — badge de contagem animado no menu
use App\Filament\Concerns\BadgeContagemNavegacao;

class ProdutoResource extends Resource
{
    use BadgeContagemNavegacao;
}

// Na List page — lembra a largura de coluna escolhida pelo usuário
use Asmit\ResizedColumn\HasResizableColumn;

class ListProdutos extends ListRecords
{
    use HasResizableColumn;
}
```

> Não repita no `table()` os defaults globais (striped, deferLoading, persistência de filtro, paginação, colunas redimensionáveis) — eles já valem. Configure só o que é específico da tela.

## Ligar o multi-tenancy

```bash
git add -A && git commit -m "antes de ligar o multi-tenancy"   # o comando exige árvore limpa
php artisan kit:tenancy            # liga o modo
php artisan kit:tenancy --demo     # liga + cria o cenário de demonstração
```

**É destrutivo** (`migrate:fresh --seed`) e a hora certa é o dia 1 do projeto. O porquê está em [arquitetura.md](arquitetura.md#por-que-o-comando-recria-o-banco).

Para trocar o termo exibido, sem tocar em código — `config/kit.php`:

```php
'tenancy' => [
    'label'        => 'Empresa',
    'label_plural' => 'Empresas',
    'slug'         => 'empresas',   // /admin/empresas
],
```

## Model de negócio com tenancy

```php
// migration
$table->foreignId('tenant_id')->constrained();
$table->index(['tenant_id', 'nome']);   // toda listagem filtra por tenant primeiro

// model
use App\Traits\BelongsToTenant;
use App\Traits\TemUuid;

class Projeto extends Model implements Auditable
{
    use AuditsFillables;
    use BelongsToTenant;
    use TemUuid;

    protected $fillable = ['nome'];   // tenant_id fora: a trait preenche
}
```

No form do resource, `->scopedUnique()` no lugar de `->unique()`. Ver [convencoes.md](convencoes.md#validação-em-resource-com-tenancy-scopedunique).

O `app/Models/Projeto.php` e o `ProjetoResource` da demo são o exemplo canônico completo.

## Vincular usuário a um tenant

`/admin` → o cadastro de tenants → aba **Usuários vinculados** → *Vincular usuário*.

Sem vínculo, o usuário não vê o tenant no seletor e toma **404** se tentar a URL direto — não 403. É deliberado do Filament: um 403 confirmaria que o tenant existe, e bastaria varrer slugs para enumerar os clientes da instalação. O `master_global` é a exceção — enxerga todos.

## Página de painel

```bash
php artisan make:filament-page Relatorio --panel=infra --no-interaction
```

O discovery pega a classe automaticamente (`app/Filament/{Painel}/Pages`). Para recortar acesso, implemente `canAccess()` na página — a busca ⌘K e o menu respeitam isso sozinhos.

## Widget de dashboard

```bash
php artisan make:filament-widget VendasStats --panel=app --no-interaction
```

- Contador animado: `gsferro/filament-odometer-easy` (`OdometerStat`).
- Card com ícone de canto e borda colorida: `gsferro/filament-stat-plus-easy`.
- Funil, meta, timeline, breakdown: `laboiteacode/filament-dashboard-widgets`.
- Série temporal: `flowframe/laravel-trend` para agregar por período.

Olhe `app/Filament/Infra/Widgets/` antes de escrever do zero — provavelmente já existe um widget parecido para copiar a forma.

## Health check novo

Em `KitServiceProvider::configureHealthChecks()`, acrescente à lista:

```php
Health::checks(array_filter([
    // ... os existentes
    MinhaIntegracaoCheck::new()->label('Integração X'),
]));
```

A página em `/infra` e o `health:check` agendado pegam sozinhos. Check que não vale em toda plataforma entra condicionado (é o que o kit faz com `UsedDiskSpaceCheck` no Windows).

## Comando na Central de Comandos

A trava real é a allow-list de `config/command-center.php` — comando fora dela não roda pela UI, ponto. Acrescente lá, e o gate `command-center:access` (papel `infra`) já cuida de quem vê a tela.

## Agente de IA novo

1. Classe estendendo `AgenteBase`, implementando só `slug()` (veja `app/Ai/Agents/Assistente.php`).
2. Registro em `agentes_ia` — via painel `/admin` → Agentes de IA, ou seeder idempotente no estilo do `AssistenteSeeder`.
3. Guardrails: declare as chaves na coluna `guardrails`. **Lista vazia bloqueia a subida do agente** (fail-closed) — é de propósito.
4. Tools: fábrica no `tools()` do agente **e** chave liberada em `agentes_ia.tools`. A permissão do usuário vai **na query da tool**.

Detalhes em [ia.md](ia.md). Skill obrigatória: `ai-sdk-development`.

## Tradução de plugin

Plugin que só traz inglês: publique/copie os arquivos para `lang/vendor/<pacote>/pt_BR/`. **Nunca** edite `vendor/`.

Rótulo de página de plugin que é propriedade estática (o caso do Command Center) muda em `bootUsing()` do painel — e só o rótulo, nunca o slug.

## Teste

```bash
php artisan make:test --pest ProdutoTest --no-interaction
php artisan test --compact --filter=Produto
```

- Teste de tela Filament usa `livewire()` com `actingAs` antes.
- Página de edição: passe `['record' => $model->id]`, chame `->call('save')` e **não** asserte redirect.
- Encostou na fundação? `composer test:kit`.

## Antes de entregar

```bash
vendor/bin/pint --dirty
php artisan test --compact --filter=<oQueVocêTocou>
composer test:kit        # se mexeu em provider, trait, painel, gate ou app/Ai
```

Commit no padrão do repositório: gitmoji + escopo, mensagem em pt-BR.

## Problemas comuns

| Sintoma | Causa provável |
|---|---|
| Tela nova dá 403 | falta rodar `ShieldPermissionsSeeder` + `PapeisSeeder` depois de criar o Resource |
| `NOT NULL constraint failed: model_has_roles.team_id` | atribuiu papel sem contexto de tenant — use `Tenant::CONTEXTO_GLOBAL` ou rode dentro de um request do `/app` |
| `no such column: model_has_roles.team_id` | as tabelas de permissão nasceram sem a coluna de tenant. Refaça num processo novo: `php artisan migrate:fresh --seed` (corrigido no kit a partir da v0.9.2) |
| Usuário perdeu os papéis dentro do `/app` | papel atribuído no contexto global; para valer no tenant, atribua com `setPermissionsTeamId($tenant->id)` |
| Listagem mostra dados de outro cliente | model sem `BelongsToTenant`, ou query com `withoutGlobalScopes()` |
| Menu não mostra o item | `canAccess()` da policy, ou `shouldRegisterNavigation()` |
| Assets do Filament sumiram | `php artisan filament:assets` |
| Vite manifest não encontrado | `npm run build` ou `composer dev` |
| Pulse sem dados | falta o daemon: `php artisan pulse:check` |
| Health parado no tempo | falta scheduler: `php artisan schedule:work` |
| Sininho não atualiza em tempo real | `BROADCAST_CONNECTION=reverb` sem o processo Reverb no ar (cai para polling de 30s) |
| Assistente indisponível | `docker compose --profile ai up -d`, ou trocar `AI_PROVIDER` |

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
- **Permissions vêm de seeder**, nunca do `shield:generate` interativo — é o que permite instalar sem intervenção. Depois de criar Resources novos, os dois, nesta ordem:
  ```bash
  php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
  php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
  ```
  O primeiro roda `shield:generate --all` **em cada painel** — o comando só enxerga o painel corrente, e até a 0.10.0 o seeder passava `--panel=admin` e mais nada: as permissions de `/app` e `/infra` nunca chegaram a existir no banco. Medido depois da correção: admin 79, app 13, infra 96 — **186 no total**, contra 79 antes (hoje, com a administração da organização e os convites no ar: admin 91, app 38, infra 96 — 199). O segundo recorta a matriz por painel via `App\Support\Paineis::permissoes()` e devolve as permissões aos papéis. Os dois são idempotentes.
- **`RelationManager` o Shield não enxerga.** A descoberta cobre só Resources, Pages e Widgets: nenhuma permission é gerada e a autorização recai na policy do model relacionado. Se esse model já tem Resource em algum painel, não há nada a fazer; se não tem, `make:policy` à mão e as chaves em `config('filament-shield.custom_permissions')` **antes** dos seeders — senão o RelationManager fica aberto a quem abrir o Resource pai.
- **A subtração do `panel_user` cobre Resource, Page e Widget.** O usuário comum do negócio recebe a matriz do painel `app` **menos** as permissões das entidades de administração (`PapeisSeeder::permissoesDeAdministracaoDoApp()`, uma lista de FQCN). Entidade nova de administração nesse painel entra nessa lista, seja Resource, Page ou Widget: a matriz vem de `getEntitiesPermissions()`, que mistura as três. Medido em 2026-08-22 no `app`: **59 permissions, 56 de Resource e 3 de Page** (eram 38 na 0.11.0, quando a subtração só varria Resources e as de Page eram inalcançáveis). Esquecer não dá erro nenhum: **todo usuário comum vira administrador da própria organização**, sem migration e sem 403.
- **Papel novo declara o painel em que vale** (`roles.painel`). Nulo **não** é coringa: papel sem painel só carrega permissões, e quem o tiver sozinho autentica e leva 403 nos três painéis. Papel semeado entra no `PapeisSeeder`, com o painel no terceiro argumento de `papel()`.
- **`master_global` fica sem permissions no banco** — o acesso vem do `Gate::before`, e ao painel de `isMasterGlobal()` dentro do `canAccessPanel()`. Não "conserte" isso sincronizando permissions nem carimbando um painel nele.
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
| Filacheck | `composer filament:check` | antes de PR — lint específico do Filament (`laraveldaily/filacheck`), que olha o que PHPStan não olha: uso de API das telas |
| Suíte completa | `composer test` | pint + phpstan + filacheck + testes |

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
| `User::papeisEmQualquerContexto()` | relação sem o `wherePivot(team_id)` do spatie | sem ela o `master_global` perde os poderes ao entrar num tenant, e `canAccessPanel()` — que roda **antes** de o tenant da rota existir — passa a depender de qual organização está aberta |
| `->tenant()` depois de `->plugins()` | reescreve as rotas do painel | plugin registrado depois não enxerga o prefixo `/{tenant}` |
| Papéis criados com `roles.team_id` nulo | definição global | o `Role::findOrCreate` do spatie carimba o team corrente, e um papel carimbado no tenant A fica invisível no B |
| `roles.painel` nulo | **não** é coringa | papel em branco viraria chave-mestra dos três painéis, em silêncio. O default fecha; quem entra em todos é o `master_global`, por `isMasterGlobal()`/`Gate::before` |
| `temPapelOnde()` usa `if`, não `->when()` | `when()` numa relação Eloquent entrega o **Builder** ao closure | `wherePivot()` não existe no Builder: o filtro de contexto some sem erro nenhum. `isMasterGlobal()` respondia `false` com a pivot correta no banco |
| `Paineis::shieldNovo()` chama `Facade::clearResolvedInstance` | `app()->forgetInstance('filament-shield')` **não** basta | a facade guarda o objeto em `Facade::$resolvedInstance` e continua entregando o antigo: os três painéis devolvem o mapa do primeiro. Sintoma: os três papéis nascendo com a mesma matriz de 79 permissões |
| `Tests\TestCase::seed()` usa `Artisan::call` | o `seed()` do Laravel passa por `PendingCommand` | o `PendingCommand` liga um mock de `OutputStyle` (`shouldIgnoreMissing`) no container, e comando aninhado resolve esse mock: `shield:generate` termina com exit 0, imprime "79 permissions generated" e grava **zero** linhas. Medido: 0 permissions por `$this->seed()`, 186 por `Artisan::call` |
| `getOptionLabelFromRecordUsing(fn (Role $record) => …)` | o parâmetro **tem** de se chamar `$record` | o Filament injeta closure por NOME, não por tipo: com outro nome a tela morre em `[$papel] was unresolvable`, e só ao renderizar o campo |
| Registro ligado pelo `AuthDesignerPlugin`, não por `$panel->registration()` | parece o caminho óbvio e não é | o plugin só grava a config da página sob a flag que ele próprio liga; pela chamada direta a tela nasce **sem mídia e sem alternador de tema**, idêntica em tudo menos no visual, e sem uma linha de erro |
| `TelaLogin` só para devolver `getSubheading()` | parece classe vazia | ligar o registro põe "Cadastre-se" na tela de login do `/app`, e o link leva a uma página que sempre recusa — affordance sem permissão, que é bug pela regra acima |
| `Convite`: `token` fora do `$fillable` | não é estilo | `AuditsFillables::getAuditInclude()` devolve o `$fillable`: dentro dele, o hash do token entraria na trilha de `/infra/audits` |
| `'painel'` nas listas das Pages do Shield | `CreateRole`/`EditRole` publicadas | as Pages tratam toda chave desconhecida do formulário como permissão: sem entrar no `in_array` **e** no `Arr::only` das duas, o Shield cria uma permission chamada `app` e o valor nunca chega ao banco |
| `getResourceEntitiesSchema()` **sem** `#[Override]` | o método vem da trait `HasShieldFormComponents` | `#[Override]` só vale para método de classe pai; num método de trait o PHP aborta o request inteiro |
| `$isScopedToTenant = false` no `UserResource` do `/app` | parece desligar o isolamento e é o contrário | `User` não tem a relação `tenant()` — o vínculo é a pivot `tenant_user`. Com o escopo nativo ligado, a primeira query do painel morre em `LogicException: … does not have a relationship named [tenant]`; e o escopo nativo falha **aberto** (sem tenant corrente, devolve a base inteira de usuários da instalação). O recorte fica em `getEloquentQuery()`, que falha **fechado**. Ver `.ai/rules/filament.md` |
| Filtro `painel = 'app'` repetido na **escrita** dos papéis | o Select já filtra as opções | opção de Select é UX, não autorização: o state chega do cliente. O `where('painel','app')` de `UserResource::gravarPapeis()` é o que impede um payload forjado de promover alguém a `admin` da instalação a partir do painel de negócio |
| `Convite::exigirDono()` confere o e-mail **no model**, e a caixa de entrada já filtra por e-mail | parece asserção duplicada | a query da tela é filtro de UI, não controle de acesso: o primeiro chamador novo (job, comando, action em massa, rota de API) passa por cima dela **sem nada acusar** e vincula qualquer usuário à organização, com o papel do convite. É literalmente o furo do `jeffersongoncalves/filament-teams`, cujo `TeamInvitation::accept(Authenticatable $user)` só faz `attach()`. CT-04 chama o método direto, com o usuário errado |
| Aceite consumido por `update` condicional (`WHERE aceito_em IS NULL`), não por `get()` + `save()` | parece rigor desnecessário para um clique de gente | na via de conta nova o `unique` de `users.email` aborta o segundo aceite concorrente; na via de oferta **não há unique que salve** — `syncWithoutDetaching` e `assignRole` são idempotentes, então dois cliques (ou duas abas) passariam os dois pelo `whereNull` e o papel seria atribuído duas vezes. É o defeito do `offload-project/laravel-invite-only`, check-then-act sem transação nem lock |
| Tabela de convites do `/app` mostra `situacao()` do model, não `aceito_em` com `->placeholder('Pendente')` | a coluna de data com placeholder é mais curta | placeholder mente: convite **recusado** apareceria como "Pendente" para sempre, e quem administra a organização reconvidaria alguém que já disse não. O estado continua derivado (`aceito_em`, `recusado_em`, `expira_em`) e vive no model porque **duas** telas o mostram — foi terem derivado por dois caminhos que produziu a divergência |
| `panel_user` recebe a matriz do `/app` **menos** as entidades de administração | parece recorte arbitrário no seeder | sem a subtração, registrar `UserResource`/`ConviteResource` no painel `app` dá `Create:User` e `Delete:User` a **todo** usuário comum: cada um vira admin da própria organização, sem migration e sem erro nenhum |
| `Paineis::entidadesDoPainel()` extrai a chave de permission por **família**: `array_column(…, 'key')` para Resource, `array_keys()` para Page e Widget | parece inconsistência que dava para unificar | os formatos são diferentes no Shield (`getDefaultPermissionKeys()` ramifica por `is_array($affixes)`): Resource guarda `[affix => ['key' => …]]`, Page e Widget guardam `[chave => rótulo]`. `array_column` numa Page devolve `[]` **sem erro, sem exception e sem aviso**, e a subtração do `panel_user` volta a não subtrair nada — com cara de correção aplicada. Medido em 2026-08-22: 59 permissions no painel `app`, 56 de Resource e 3 de Page. O único teste que acusa é `it('alcanca Page e Widget na subtracao do painel app')` |
| Campo de e-mails do lote **sem** `->email()` e **sem** `->nestedRecursiveRules(['email'])` | parece campo sem validação | validação de formato no formulário reprova a **modal inteira**, e o convite em massa deixa de ter resultado parcial: um endereço com `@gmial.com` no meio de quarenta impediria os outros trinta e nove. O formato é decidido endereço por endereço dentro de `Convite::convidarEmMassa()`, com a **mesma** regra `email` do Laravel que o campo do convite individual usa. CT-02 (`it('envia os validos mesmo com um endereco torto no meio')`) fica vermelho no dia em que alguém "validar direito" |
| `Convite::valido()` embrulha o par de tokens num `where(closure)` | parece parêntese decorativo em volta de dois `where` | `orWhere` **sem agrupamento explícito escapa dos outros filtros**: `AND` liga mais forte que `OR`, então o SQL sai como `token = ? OR (token_lembrete = ? AND aceito_em IS NULL AND …)` e cada token passa a valer **sozinho, sem prazo e sem estado** — convite expirado, aceito ou recusado volta a ser aceitável, sem erro e sem log, e a tela simplesmente aceita. Os **três** filtros de estado ficam FORA do agrupamento. Visto vermelho de propósito antes de o closure entrar; `it('nao aceita token de lembrete de convite aceito, recusado nem expirado')` é o alarme |
| `Convite::lembrar()` gera um **segundo** token em vez de reenviar o link do convite | parece duplicação de coluna que dava para unificar | o token em claro existe no e-mail e em lugar nenhum mais, então um lembrete só poderia "reenviar o mesmo link" chamando `enviar()` — que rotaciona o token e renova o prazo. O e-mail que a pessoa já tem passaria a dar redirect para o login, e um lembrete perdido no spam teria **revogado** o único link válido, sem avisar ninguém. `token` e `expira_em` não são tocados; `it('lembra com um link novo sem invalidar o do envio')` acusa quem "simplificar" para `enviar()` |
| `Convite::enviar()` chama `$this->refresh()` antes do `forceFill` | parece SELECT desnecessário logo antes de um UPDATE | `save()` grava só o que está **sujo**: numa instância carregada antes de um lembrete, `lembretes_enviados` e `token_lembrete` estão velhos em memória, o `forceFill` os iguala ao valor antigo e o Eloquent não vê mudança nenhuma — a reinicialização é descartada **em silêncio** e o link do último lembrete continua valendo depois de um reenvio que promete matá-lo |
| A ação de lote declara `->authorize('create', Convite::class)` | parece redundante ao lado de um `CreateAction` que não declara nada | `CreateAction::make()` consulta `canCreate()` sozinho; um `Action::make()` cru **não consulta nada** e apareceria para quem só tem `ViewAny:Convite` — affordance sem permissão, que é bug pela regra acima. `authorize()` esconde **e** recusa: `isAuthorizedOrNotHiddenWhenUnauthorized()` faz o `CanBeHidden` devolver escondido, então a ação nem é montável |

| `SimpleLightBoxPlugin` registrado nos **três** painéis, inclusive no `/infra`, que não tem mídia | parece peso sem uso num painel | o plugin registra **macros** (`ImageColumn::simpleLightbox()`) no `boot(Panel $panel)`. Coluna de imagem criada num painel sem ele derruba a tela com `BadMethodCallException` **na renderização** — não no boot, não no deploy, e a mensagem não menciona nem "painel" nem "plugin". A economia seria um `<script>` por página |
| `resources/css/filament/cards.css` com ~45 utilitárias escritas à mão | parece Tailwind reimplementado | o `harvirsidhu/filament-cards` **não registra CSS nenhum** (o provider dele só faz `->hasViews()`), e a CSS pré-compilada do Filament 5 carrega quase só as classes `fi-*`: medido, **51 das 53** utilitárias que a blade do pacote emite não existem lá. Sem o arquivo a grade sai como lista de links soltos — com o HTML correto, sem erro, sem aviso. Escopado em `.kit-cards-page` para não atropelar a marcação de outros plugins |
| `CardItem` do hub sempre construído por `DescobreCardsDoPainel`, nunca à mão | parece indireção evitável | `CardItem` **não verifica autorização**: `Concerns/CanBeHidden` avalia só `visible`/`hidden`, e o `canAccess()` do pacote existe apenas dentro de `discoverClusterCards()`/`discoverResourceCards()`, que exigem Cluster ou página de Resource. Um cartão escrito à mão aparece para todo mundo e só devolve 403 no clique — vaza a existência da tela e oferece caminho que só falha depois |
| Todo `ApexChartWidget` declara `$pollingInterval` | parece configuração repetida | o default do pacote é **5 segundos**, por widget e por aba aberta: três gráficos numa aba esquecida geram 36 consultas agregadas por minuto, indefinidamente e sem ninguém olhando. Custo de banco proporcional a abas esquecidas |
| Cor de gráfico por token (`var(--success-500)`), nunca hexadecimal | parece preciosismo | hexadecimal literal ignora tema claro/escuro e a identidade visual da organização no `/app` — é o mesmo defeito que o `resources/css/filament/kit.css` existe para corrigir no alternador de painel |
| `ConvitesPorSituacao` carrega três colunas de cada convite em vez de agregar em SQL | parece consulta ineficiente | não há coluna de status: a situação é derivada por `Convite::situacao()`, e a regra tem uma precedência que um `where` ingênuo erra — **aceito vence expirado**. Reescrever em SQL cria uma segunda definição do mesmo estado, que é exatamente como a divergência volta |
| `FilamentExceptionsPlugin` registrado nos **três** painéis, com `->registerNavigation(false)` no `/app` e no `/admin` | parece plugin sobrando em painel que não tem a tela | o `ExceptionResource` resolve o plugin pelo painel **corrente** (`FilamentExceptionsPlugin::get()`, pelo helper `filament()`) já nos métodos **estáticos** de navegação, e o `filament-shield` percorre `Filament::getPanels()` no boot **sem fixar** qual é o corrente — a resolução cai no painel default. Painel sem o plugin estoura `LogicException: Plugin [filament-exceptions] is not registered for panel [app]` em **todo** request e em **todo** comando artisan, `migrate` e `inspire` inclusive. Medido, não suposto. É a mesma armadilha do `Lockscreen`, e a saída é a mesma: registrar nos três, com navegação só onde a tela deve estar |
| `ExceptionResource` na lista de subtração do `panel_user` (`PapeisSeeder`) | parece resource de vendor entrando numa lista de telas do `/app` | é **consequência obrigatória** da linha acima: registrar o plugin no painel `app` põe o resource na matriz de permissões **daquele** painel. Sem a subtração, o papel herda `ViewAny:Exception` e companhia, e a rota existe no painel dele (`app/{tenant:slug}/exceptions`). Medido em 2026-08-22: **14** permissions de `Exception`, **0** no `panel_user` e **0** no `admin_app`. O zero do `admin_app` é novo — até a 0.18.2 a subtração pegava só o `panel_user` e quem administrava a organização recebia as 14, `DeleteAny` inclusive (QA-01 do `06-relatorio-qa.md` da wiki admin-da-organizacao). Por isso são **duas** listas no seeder: `permissoesForaDoApp()` sai dos dois papéis, `permissoesDeAdministracaoDoApp()` só do `panel_user`. E cuidado com o motivo: **não** era vazamento de stack trace. Com a tenancy ligada a tela nem renderiza — o global scope chama `getTenantOwnershipRelationship()` e lança `LogicException` em `vendor/filament/filament/src/Resources/Resource/Concerns/BelongsToTenant.php:98`, porque o model `Exception` não tem relação `tenant`. Era 500, não leitura. A permission existir já bastava para ser defeito. Vale como regra geral: plugin registrado por obrigação técnica num painel exige revisar a matriz daquele painel |
| `modelPruneInterval(Carbon::now()->subDays($dias))` | parece que o método pede uma quantidade de dias | ele recebe a **data de corte**: o `Exception::prunable()` do pacote faz `whereDate('created_at', '<=', $intervalo)`. Passar `14` compararia `created_at` com o **ano 14** e nunca podaria nada — agendamento verde, tabela crescendo, ninguém avisado. E é `Carbon` **mutável**, não o helper `now()`: o kit faz `Date::use(CarbonImmutable::class)` no `KitServiceProvider` e a assinatura do pacote pede o mutável — o PHPStan pega, em runtime seria `TypeError` |

## Onde cada coisa se configura

| Quero mudar | Arquivo |
|---|---|
| Quem entra em cada painel | o **papel** do usuário: `/admin` → Funções → campo *Painel* (`roles.painel`). O código que lê é `app/Models/User.php` → `canAccessPanel()` |
| Papéis do kit e o painel de cada um | `database/seeders/PapeisSeeder.php` → `papel()`, terceiro argumento |
| Gates de infra | `app/Providers/KitServiceProvider.php` → `configureGates()` |
| Matriz de permissões | `database/seeders/PapeisSeeder.php` (o recorte por painel vem de `app/Support/Paineis.php`) |
| A tela de papéis do Shield | `app/Filament/Admin/Resources/Roles/` — publicada no projeto |
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
| Retenção das trilhas (exceções, e-mails) | `KIT_RETENCAO_EXCECOES_DIAS` / `KIT_RETENCAO_EMAILS_DIAS` no `.env` (`kit.retencao` em `config/kit.php`). O config declara o prazo; **quem aplica é `routes/console.php`** |
| Idiomas oferecidos no seletor | `kit.idiomas` em `config/kit.php` — um item só esconde o botão. O seletor em si é configurado em `ConfiguraFilamentGlobal` |
| Grupo de navegação da trilha de e-mail | `lang/vendor/filament-maillog/pt_BR/filament-maillog.php` → `navigation.group`. O resource lê de uma chave de **tradução**, não de config nem de método de plugin — mudar em qualquer outro lugar não tem efeito |
| Models restauráveis pela Lixeira | `RevivePlugin::make()->models([...])` no `InfraPanelProvider` (lista explícita, de propósito) |
| Disco dos anexos | `MEDIA_DISK` / `MEDIA_PREFIX` no `.env` (`config/media-library.php`) |

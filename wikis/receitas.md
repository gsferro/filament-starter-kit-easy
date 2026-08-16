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

Escolha o painel pelo público: `app` = negócio, `admin` = administração, `infra` = operação. O painel também decide de qual papel a permission vai fazer parte — o `PapeisSeeder` recorta a matriz pelo painel em que o Resource está registrado.

Depois, **sempre os dois, nesta ordem**:

```bash
php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
```

O primeiro gera as permissions e as policies em **cada painel**; o segundo devolve as permissões aos papéis. Sem eles a tela nova responde 403 para todo mundo que não seja `master_global`.

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

## RelationManager novo

```bash
php artisan make:filament-relation-manager ProdutoResource itens nome --no-interaction
```

**O Shield não enxerga RelationManager.** A descoberta dele cobre só Resources, Pages e Widgets, então nenhuma permission é gerada e a autorização recai na **policy do model relacionado**. Duas situações:

| O model relacionado… | O que fazer |
|---|---|
| já tem Resource em algum painel | nada — a policy já existe e já foi semeada |
| não tem Resource nenhum | `php artisan make:policy ItemPolicy --model=Item`, declare as chaves em `config('filament-shield.custom_permissions')` e **só então** rode os dois seeders |

Pular isso deixa o RelationManager aberto a qualquer um que consiga abrir o Resource pai — sem erro, sem 403, sem pista.

## Papel novo

Pela tela: `/admin` → **Funções** → *Criar*. O campo **Painel** é o que dá o acesso; deixá-lo em branco cria um papel que só carrega permissões, e quem o tiver sozinho autentica e leva 403 nos três painéis.

Para semear (o caminho de quem versiona a matriz), em `database/seeders/PapeisSeeder.php`:

```php
$this->papel('suporte', $guard, 'admin')
    ->syncPermissions($this->permissoesDoPainel('admin', $guard));
```

- O terceiro argumento é o painel (`admin`, `app`, `infra`, ou `null` para nenhum).
- `permissoesDoPainel()` já intersecta com o que existe no banco: nome ausente na tabela faria `syncPermissions()` estourar `PermissionDoesNotExist` e derrubar o seeder.
- Para recortar em vez de dar o painel inteiro: `Paineis::permissoes('app')->reject(fn (string $p): bool => str_starts_with($p, 'Delete:'))`.
- `updateOrCreate`, não `firstOrCreate`: papel que já existe precisa **receber** o painel, senão quem atualiza o kit fica com papéis sem acesso.

Atribuir a alguém: `/admin` → Usuários → o papel aparece com o painel no rótulo (`admin — /admin`). Com a tenancy ligada a atribuição é **por organização** (`model_has_roles.team_id`) — em código, `setPermissionsTeamId($tenant->id)` antes do `assignRole()`, como faz o `DemoTenancySeeder`.

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

## Promover alguém a admin de uma organização

**O caminho intuitivo é o errado.** Dar o papel `admin_organizacao` pelo `/admin` → Usuários grava a atribuição no **contexto global** (`model_has_roles.team_id = 0`): a pessoa entra no `/app`, e lá dentro o papel não existe — menu vazio, 403 em cada tela, nenhuma mensagem de erro.

O caminho certo:

`/admin` → o cadastro de organizações → aba **Usuários vinculados** → ação **Papéis nesta organização**.

É o único lugar que conhece o usuário **e** a organização ao mesmo tempo, e é ele que fixa o contexto antes de gravar. O Select oferece só papéis do painel `/app` — papel de instalação (`admin`, `infra`) continua no cadastro do usuário.

Em código (seeder, comando):

```php
$registrar = app(PermissionRegistrar::class);
$registrar->setPermissionsTeamId($tenant->getKey());
$usuario->unsetRelation('roles');          // o Eloquent cacheia `roles` na instância
$usuario->assignRole('admin_organizacao');
```

O que a persona ganha: **Usuários** e **Convites** dentro do `/app`, recortados à organização dela. O que ela **não** ganha, e é de propósito:

- não entra em `/admin` nem `/infra` (o papel declara `roles.painel = 'app'`);
- não vê nem edita usuário de outra organização, nem por URL direta (404);
- não cria nem edita papéis — só atribui, e só papéis do painel `/app`;
- não exclui usuário (o delete apagaria a pessoa de **todas** as organizações);
- o convite que ela cria nasce com a organização dela, ignorando o formulário.

O papel só é semeado com a tenancy ligada — sem organização ele seria um segundo `admin` com outro nome. `panel_user` continua sendo o perfil de quem só usa o negócio: ele recebe a matriz do painel **menos** as permissões dessas duas telas (`PapeisSeeder::permissoesDeAdministracaoDoApp()`). Resource de administração novo no `/app` precisa entrar nessa lista, senão todo usuário comum o herda.

## Convidar alguém

`/admin` → **Convites** → *Novo convite*: e-mail, papel e (com tenancy) a organização. Com a tenancy ligada, quem tem `admin_organizacao` faz o mesmo por `/app/{organizacao}` → **Convites**, e ali a organização é a do painel — o formulário não a pergunta. O e-mail sai na hora e o link leva a `/app/register?token=…`.

**Não pergunte se a pessoa já tem conta.** O sistema decide no aceite, e as duas vias usam o mesmo convite e o mesmo link:

| O endereço | O que acontece no aceite |
|---|---|
| ainda **não tem** conta | quem clica escolhe **só nome e senha** — e-mail, papel e organização vêm do convite, impostos pelo servidor. O usuário nasce com o e-mail já verificado (o token prova a posse do endereço) |
| **já tem** conta | ninguém é cadastrado de novo: é uma **oferta de acesso**. A pessoa entra com a senha que já tem, confirma, e é vinculada à organização com o papel do convite. Os acessos dela nas outras organizações ficam intactos — e ela pode **recusar** |

Em qualquer das duas, o papel nasce no contexto certo: contexto global se for de `/admin` ou `/infra`, a organização do convite se for de `/app`.

Quem recebeu a oferta tem dois caminhos, e os dois valem:

- **O link do e-mail**, que é o canônico: funciona inclusive para quem ainda não pertence a nenhuma organização.
- **O menu do usuário → Convites recebidos**, com a contagem de ofertas pendentes. É de lá que se **recusa** (o link tem um destino só). A recusa fica registrada, o convite deixa de valer e reconvidar é enviar outro.

Três coisas a saber:

- **O link vale uma vez e expira** (`kit.convites.validade_em_dias`, 7 dias). Recusa por token inexistente, expirado ou já usado dá a **mesma** resposta — distinguir confirmaria que o convite existiu.
- **Reenviar gera um token novo e mata o anterior.** É a mesma ação de enviar.
- **Revogar é apagar o convite.** O rastro fica na auditoria (`/infra/audits`).

O e-mail sai pela fila (`QUEUE_CONNECTION=database` no `.env.example`): **sem worker no ar o convite não chega**. Em desenvolvimento, `php artisan queue:work` ou `composer dev`.

## Convidar várias pessoas de uma vez

`/admin` → **Convites** → *Convidar em massa* (e, com tenancy, o mesmo botão em `/app/{organizacao}` → **Convites**). Cole os endereços — um por linha, ou separados por vírgula ou ponto-e-vírgula —, escolha **um** papel e **uma** organização para o lote inteiro, e envie. Até `KIT_CONVITE_LIMITE_LOTE` endereços (100 por padrão).

**Um endereço com problema não impede os outros.** Ao final você recebe um resumo que fica na tela: quantos saíram e uma linha por endereço que não saiu, com o motivo.

| O endereço | Resultado |
|---|---|
| novo, formato válido | **enviado** |
| **já tem conta** | **enviado** — é oferta de acesso, não erro |
| repetido no próprio texto | **enviado uma vez**, sem aviso |
| formato inválido | não enviado — *endereço inválido* |
| já tem convite pendente para essa organização | não enviado — o link antigo continua valendo; para renovar, use o **Reenviar** da linha |
| recusou um convite anterior | não enviado — quem disse não não é reconvidado porque alguém colou a planilha antiga de novo |
| já faz parte da organização do lote | não enviado — não há acesso novo a conceder |
| lote acima do limite | **nada é enviado**, e a modal fica aberta com o texto colado |

O que fazer com o resumo: *endereço inválido* é digitação, corrija e mande só ele; *já tem convite pendente* e *já faz parte* não pedem ação nenhuma; *recusou o convite anterior* é uma conversa, não um reenvio — se ainda faz sentido, use o **Novo convite** individual, que não tem essa trava. *Falha no envio* é infraestrutura: o convite **existe** e aparece como Pendente, então o **Reenviar** da linha resolve depois de o e-mail voltar. O motivo de cada falha está no `storage/logs` do channel `autenticacao`, com os endereços mascarados.

O envio é o mesmo do convite individual: **sem worker de fila no ar, cem convites param na tabela `jobs`** e ninguém recebe nada. Reenviar e revogar continuam por linha — não existe versão em massa das duas, porque reenviar trinta mata trinta links antigos e manda trinta e-mails repetidos com um clique.

## Página de painel

```bash
php artisan make:filament-page Relatorio --panel=infra --no-interaction
```

O discovery pega a classe automaticamente (`app/Filament/{Painel}/Pages`). Para recortar acesso, implemente `canAccess()` na página — a busca ⌘K e o menu respeitam isso sozinhos.

> **Vale um hub de cartões?** Se a página que você está criando é um **índice de caminhos** — várias
> escolhas, atalhos rápidos, uma área de configurações —, ela provavelmente quer ser uma
> `CardsPage`. Ver [Página hub de cards](#página-hub-de-cards).

## Widget de dashboard

```bash
php artisan make:filament-widget VendasStats --panel=app --no-interaction
```

### Qual pacote usar — a regra é por TIPO DE DESENHO

| Vou desenhar | Pacote | Classe base |
|---|---|---|
| **Gráfico** — linha, área, barra, rosca, radial, radar, heatmap | `leandrocfe/filament-apex-charts` | `ApexChartWidget` |
| **Stat card** — número grande, ícone de canto, variação | `gsferro/filament-stat-plus-easy` | `StatsOverviewWidget` + `StatPlus` |
| **Todo o resto** — métrica, meta, breakdown, barra segmentada, timeline, lista, bullet | `laboiteacode/filament-dashboard-widgets` | `MetricWidget`, `GoalProgressWidget`, `BreakdownWidget`, … |
| Contador animado dentro de um stat | `gsferro/filament-odometer-easy` | `OdometerStat` |

Sem essa fronteira a escolha vira preferência de quem escreve, e o dashboard fica com duas
linguagens visuais para a mesma pergunta. Ver ADR-01 da wiki `graficos-com-apexcharts`.

### Gráfico novo

```bash
php artisan make:filament-apex-charts VendasPorMes
```

Checklist — cada item existe por causa de um modo de falha real:

1. **`$pollingInterval` explícito, sempre.** O default do pacote é **5 segundos**, por widget e
   por aba aberta: uma aba esquecida gera dezenas de consultas agregadas por minuto,
   indefinidamente. Use `null` para dado que muda por ação humana.
2. **`canView()` com `Schema::hasTable()`** quando a fonte é tabela de pacote opcional. Widget
   que estoura derruba o **dashboard inteiro**, não só o próprio card.
3. **Estado vazio com a série ZERADA**, nunca `series: []` — array vazio faz o ApexCharts
   desenhar um canvas em branco, sem legenda e sem explicação. É o estado de toda instalação
   nova.
4. **Cor por token semântico** (`var(--success-500)`, `var(--primary-500)`), nunca hexadecimal:
   é isso que faz o gráfico acompanhar tema claro/escuro e a cor da organização no `/app`.
5. **`$chartId` declarado** — vira o `id` do elemento, e é o seletor dos testes de browser.
6. **Plugin registrado no painel**: `FilamentApexChartsPlugin::make()`. Hoje está em `/admin` e
   `/infra`; o primeiro gráfico do `/app` precisa registrá-lo lá junto.
7. **Rodar os dois seeders** — widget é entidade do Shield e nasce sem permission.

### Quando rosca, quando radial

- **Rosca (`donut`)**: categorias mutuamente exclusivas que somam o total, de 2 a 5 fatias, e a
  leitura procurada é a **proporção entre elas**. Ex.: `ConvitesPorSituacao`.
- **Radial (`radialBar`)**: **um** número entre 0 e 100%. Ex.: `FilasTaxaDeSucesso`.
- **Nenhum dos dois** para série temporal — aí é área ou linha.

### Quando NÃO usar gráfico

O exemplo está no próprio kit: `SaudeAplicacaoPorStatus` recusou rosca e usa barra segmentada,
porque a pergunta ali é *"quanto da barra ainda é verde"* — e barra horizontal responde isso mais
rápido que comparar ângulos. Gráfico não é sempre a melhor resposta para "tenho categorias que
somam o todo".

Olhe `app/Filament/Infra/Widgets/` antes de escrever do zero — provavelmente já existe um widget parecido para copiar a forma.

## Imagem ou documento em tabela

Toda coluna de mídia do kit nasce com lightbox: clicar na miniatura amplia sobre a listagem, sem
sair da página. É o `solution-forest/filament-simplelightbox`.

```php
use Filament\Tables\Columns\ImageColumn;

ImageColumn::make('avatar_url')
    ->label('Avatar')
    // `disk('public')` explícito: o default é `local`, que aponta para storage/app/private e
    // NÃO é servível por URL — a miniatura nasceria quebrada.
    ->disk('public')
    ->circular()
    // Sem `defaultImageUrl()`: quem não enviou nada fica com a célula VAZIA, e não com um
    // placeholder clicável que abriria o lightbox em cima de nada.
    ->simpleLightbox(),
```

Duas armadilhas, as duas silenciosas:

1. **O plugin tem de estar registrado NO PAINEL.** `simpleLightbox()` é um **macro**, registrado
   no `boot(Panel $panel)` do plugin. Num painel sem ele, a coluna derruba a tela com
   `BadMethodCallException` **na renderização** — não no boot, não no deploy. Os três painéis do
   kit já registram; painel novo precisa registrar junto.
2. **`php artisan filament:assets` depois de instalar ou atualizar.** Sem o JS publicado o clique
   é **inerte**, sem erro nenhum.

`ImageColumn` confere a existência do arquivo por padrão e devolve célula vazia quando não acha —
não é preciso `Storage::exists()` à mão.

### Documento (PDF, Office): só se o arquivo for público e não sensível

```php
TextColumn::make('manual_url')
    ->label('Manual')
    ->simpleLightbox(fn ($record) => $record->manual_url),
```

⚠️ **O preview de documento sai da sua aplicação.** O JS do pacote monta PDF via
`https://docs.google.com/viewer?url=…` e Office via `https://view.officeapps.live.com/…`. Duas
consequências: a URL do arquivo é **enviada a um terceiro**, e o arquivo precisa ser
**publicamente acessível** — documento atrás de autenticação devolve preview em branco, sem erro.

Regra do kit: **imagem sempre; documento apenas quando já é público e não é sensível** (manual,
catálogo, folheto). Contrato, holerite, anexo de cliente e qualquer coisa com dado pessoal seguem
com download autenticado, sem lightbox. Ver ADR-03 da wiki `lightbox-em-imagens-e-documentos`.

## Página hub de cards

Quando um painel — ou um cluster, ou uma área de configurações — tem muitos destinos, uma **grade
de cartões** lê melhor que uma árvore de barra lateral. É o `harvirsidhu/filament-cards`.

```php
use App\Filament\Concerns\DescobreCardsDoPainel;
use Harvirsidhu\FilamentCards\Filament\Pages\CardsPage;

class HubDeInfraestrutura extends CardsPage
{
    use DescobreCardsDoPainel;

    protected static ?int $navigationSort = -10;

    protected static bool $searchable = true;

    /** A classe que dá escopo ao `resources/css/filament/cards.css` — sem ela a grade sai sem estilo. */
    public function getPageClasses(): array
    {
        return ['kit-cards-page'];
    }

    protected static function getCards(): array
    {
        return static::cardsDoPainel(excluir: [static::class, Dashboard::class]);
    }
}
```

### Quatro casos de uso

1. **Porta de entrada de painel denso** — é o que o kit faz nos três painéis. O hub **soma** à
   barra lateral, não a substitui: esconder itens da navegação quebraria a busca ⌘K e custaria
   dois cliques onde havia um.
2. **Hub de configurações** — agrupar as páginas de settings numa grade em vez de espalhá-las
   pelo menu.
3. **Página inicial de Cluster** — aí sim vale o `discoverClusterCards()` do pacote, que já filtra
   por `canAccess()` sozinho.
4. **Atalhos externos** — `CardItem::make('https://status.exemplo.com')->openUrlInNewTab()`.

### O que NÃO fazer

- **`CardItem::make(SeuResource::class)` cru.** O `CardItem` **não** verifica autorização: o
  cartão aparece para todo mundo e só devolve 403 no clique, vazando a existência da tela. Use
  `App\Filament\Concerns\DescobreCardsDoPainel`, que filtra por `canAccess()` por construção.
- **`$columns` ≥ 5, `columnSpan(['lg' => n])` e cor de ícone no hover.** As três montam o nome da
  classe CSS por interpolação de string, e Tailwind **nunca** gera classe montada em runtime —
  com ou sem tema. Ver ADR-03 da wiki `hub-de-navegacao-em-cards`.
- **Usar o pacote como componente de formulário.** Ele transforma uma *página* em grade de links;
  não substitui `Radio` nem `Select`.

### Depois de criar

1. Os **dois seeders** do Shield — Page nova nasce sem permission e responde 403 para todo mundo.
2. `php artisan filament:assets` se mexer em `resources/css/filament/cards.css`.
3. No painel `app`: a página **só** entra em `PapeisSeeder::permissoesDeAdministracaoDoApp()` se
   for de administração. Hub de navegação **não** é — ver ADR-05 da wiki.

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
| Usuário autentica e leva 403 nos **três** painéis | ele não tem papel nenhum, ou o papel que tem está com `roles.painel` vazio — e nulo não é coringa. Dê um papel em `/admin` → Usuários, ou declare o painel do papel em `/admin` → Funções |
| Entra no `/app` mas não no `/admin` (ou vice-versa) | é o desenho: o papel vale para **um** painel. E `/admin` e `/infra` exigem o papel atribuído no contexto global — ser `admin` dentro de uma organização não abre a administração da instalação |
| Convite não chega | o e-mail vai pela fila e não há worker: `php artisan queue:work`. Em desenvolvimento o mailer é `log` — o conteúdo cai em `storage/logs/laravel.log` |
| Convidei e a pessoa não respondeu | o kit cobra sozinho: `kit:convites-lembrar` manda um lembrete nos dias de `KIT_CONVITE_LEMBRETES_DIAS` (D+3 e D+5), com um **segundo link** — o link original continua valendo. Se quiser insistir na hora, *Reenviar* pelo `/admin` → Convites, lembrando que ele **mata os links anteriores** e reinicia o relógio |
| O lembrete não sai | quatro causas, nesta ordem: não há **scheduler** (`php artisan schedule:work`, ou o serviço `scheduler` do compose); não há **worker** (o contador sobe e o e-mail fica na fila — a fila parada aparece no monitor do `/infra`); `MAIL_MAILER=log`, e o e-mail está em `storage/logs/laravel.log`; ou algum dia de `KIT_CONVITE_LEMBRETES_DIAS` é **maior ou igual** a `KIT_CONVITE_VALIDADE_DIAS` — aí o convite expira antes de o lembrete ser devido e nenhum lembrete jamais sai, sem erro nenhum. `KIT_CONVITE_LEMBRETES_DIAS=` (vazio) desliga a feature de propósito |
| Link do convite sempre recusa | usado, expirado, recusado ou token inexistente — todos dão a mesma tela, de propósito. Reenvie pelo `/admin` → Convites |
| "Este convite não é para esta conta" | o link foi aberto com **outra** conta autenticada. É a barreira funcionando: o token não basta na via de oferta, o e-mail tem de ser o do convite. Saia e entre com a conta convidada, ou peça um convite novo. A sessão não é derrubada e o convite continua pendente |
| "Não vejo meus convites" | a caixa de entrada é uma página do painel `app` — quem tem **zero** organizações (ou só papel de `/admin`/`/infra`) não a alcança, porque o painel precisa de uma organização para abrir. Para esses casos a via é o **link do e-mail**, que funciona sempre. Quem já pertence a alguma organização acha o item no menu do usuário, e ele só aparece quando há oferta pendente |
| Tela nova dá 403 | falta rodar `ShieldPermissionsSeeder` + `PapeisSeeder` depois de criar o Resource — nessa ordem, e os dois: só o primeiro cria a permission e não a entrega a papel nenhum |
| RelationManager aberto a quem não devia | o Shield não gera permission para RelationManager; ver [RelationManager novo](#relationmanager-novo) |
| `NOT NULL constraint failed: model_has_roles.team_id` | atribuiu papel sem contexto de tenant — use `Tenant::CONTEXTO_GLOBAL` ou rode dentro de um request do `/app` |
| `no such column: model_has_roles.team_id` | as tabelas de permissão nasceram sem a coluna de tenant. Refaça num processo novo: `php artisan migrate:fresh --seed` (corrigido no kit a partir da v0.9.2) |
| Usuário perdeu os papéis dentro do `/app` | papel atribuído no contexto global; para valer no tenant, atribua com `setPermissionsTeamId($tenant->id)` |
| Admin da organização entra no `/app` e não vê nada | o `admin_organizacao` foi dado pelo `/admin` → Usuários, que grava no contexto global. Refaça por `/admin` → organizações → **Usuários vinculados** → *Papéis nesta organização* — ver [a receita](#promover-alguém-a-admin-de-uma-organização) |
| Usuário comum vê "Usuários" e "Convites" no `/app` | o `PapeisSeeder` não rodou depois de o kit ganhar essas telas, ou a subtração de `permissoesDeAdministracaoDoApp()` foi removida: `panel_user` está com a matriz inteira do painel |
| Listagem mostra dados de outro cliente | model sem `BelongsToTenant`, ou query com `withoutGlobalScopes()` |
| Menu não mostra o item | `canAccess()` da policy, ou `shouldRegisterNavigation()` |
| Assets do Filament sumiram | `php artisan filament:assets` |
| Vite manifest não encontrado | `npm run build` ou `composer dev` |
| Pulse sem dados | falta o daemon: `php artisan pulse:check` |
| Health parado no tempo | falta scheduler: `php artisan schedule:work` |
| Sininho não atualiza em tempo real | `BROADCAST_CONNECTION=reverb` sem o processo Reverb no ar (cai para polling de 30s) |
| Assistente indisponível | `docker compose --profile ai up -d`, ou trocar `AI_PROVIDER` |

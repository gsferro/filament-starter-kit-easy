---
paths:
  - 'app/Filament/**'
---

# Filament

## Papel e permissão se gravam pela API do spatie, nunca por sync da relação
Em campo de formulário que grava `roles` ou `permissions`, o `->relationship()` do Filament NÃO serve sozinho: ele salva com `$relationship->sync()`, que escreve na pivot só as colunas da chave.

Com multi-tenancy ligada isso estoura 500 (`NOT NULL constraint failed: model_has_roles.team_id`) — o `wherePivot` que o spatie põe em `roles()` filtra LEITURA e não alimenta escrita; quem passa o `team_id` do contexto é o `assignRole()`/`syncRoles()`. Mesmo em single-tenant o `sync()` deixa o cache de papéis velho.

Use `->saveRelationshipsUsing()` chamando `syncRoles()`, e resolva os papéis em MODELOS antes (o state vem do Livewire como string, e o `collectRoles()` do spatie trata string como nome de papel — `"4"` viraria `RoleDoesNotExist`). Ver `app/Filament/Admin/Resources/Users/UserResource.php`.

Teste em par: um caso em `tests/Kit` (single-tenant) e um em `tests/Tenancy` conferindo o `team_id` da pivot. Abrir a tela não cobre — o `GET /admin/users` seguia verde com o salvamento quebrado.

## Asserção de identidade vive no model, não na query da tela

Ação de tabela que age **sobre o registro de outra pessoa** (aceitar um convite, confirmar um convite, assumir algo endereçado a um e-mail) não se protege com o `where` da query que lista os registros. A query é **filtro de UI**; a barreira é uma asserção no método do model, que lança quando o registro não é de quem chamou.

O raciocínio que produz o furo é sempre o mesmo: "a tabela já filtra por e-mail, então conferir de novo no model é redundância". Não é. Enquanto a página for o único chamador, funciona; o primeiro job, comando, action em massa, seeder ou rota de API chama o método direto e passa por cima da barreira **sem nada acusar** — e o resultado é alguém dentro de uma organização com o papel de um convite que não era dele.

É literalmente o furo do `jeffersongoncalves/filament-teams`: `TeamInvitation::accept(Authenticatable $user)` faz `attach()` + `delete()` sem comparar e-mail nenhum, e a única barreira é o `->where('email', $email)` da página de aceite.

No kit: `Convite::exigirDono()`, chamado na primeira linha de `aceitarComoUsuarioExistente()` e de `recusar()`. Comparação normalizada (`mb_strtolower(trim(...))`) nos dois lados — e-mail não é case-sensitive na prática. `App\Filament\App\Pages\ConvitesRecebidos` continua escapando a query por e-mail, e o PHPDoc dela diz que isso é conveniência.

Policy não substitui: policy é autorização de ação por perfil, isto é identidade do **dono do registro** — e policy não é consultada por job nem por comando, que é justamente o chamador que se quer cobrir.

Teste: chame o método **direto**, com o usuário errado, e cobre a exceção (`it('recusa aceite quando o e-mail nao corresponde')` em `tests/Kit/ConviteUsuarioExistenteTest.php`). Barreira sem teste direto não é barreira — o caso que passa pela tela continuaria verde com a asserção removida.

## Resource ou RelationManager novo exige gerar as permissões

Resource novo nasce sem permission no banco: a tela responde 403 para todo mundo que não seja `master_global`. Depois de `make:filament-resource`, rode sempre os dois seeders, nesta ordem:

```bash
php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
```

O primeiro roda `shield:generate --all` **em cada painel** (o comando só enxerga o painel corrente) e escreve as policies; o segundo recorta a matriz por painel via `App\Support\Paineis::permissoes()` e devolve as permissões aos papéis. Os dois são idempotentes.

**RelationManager o Shield não enxerga.** A descoberta cobre apenas Resources, Pages e Widgets (`vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityDiscovery.php`), então nenhuma permission é gerada para ele e a autorização recai na policy do model relacionado. Se esse model já tem Resource em algum painel, não há nada a fazer. Se não tem, crie a policy à mão (`php artisan make:policy`) e declare as chaves em `config('filament-shield.custom_permissions')` **antes** de rodar os seeders — do contrário o RelationManager fica aberto a qualquer um que consiga abrir o Resource pai.

## Resource, Page ou Widget de administração no painel `app` entra na lista de subtração

O `panel_user` recebe a matriz do painel `app` **menos** as permissões das entidades de administração — a lista `PapeisSeeder::permissoesDeAdministracaoDoApp()`, hoje só FQCN. Entidade nova de administração nesse painel (qualquer uma que mexa em quem entra: usuários, convites, papéis) precisa entrar nessa lista, e **as três famílias contam**: a matriz vem de `FilamentShield::getEntitiesPermissions()`, que mistura Resource **com Page e Widget** (e com `custom_permissions`, hoje vazia).

Medido em 2026-08-22 no painel `app`: **59 permissions, 56 de Resource (4 classes) e 3 de Page** (`View:MyProfilePage`, `View:ConvitesRecebidos` e `View:HubDoNegocio`), 0 de Widget. Reconte com `Paineis::permissoes('app')->count()` em vez de confiar neste número: ele já ficou parado em **38** por sete versões, e número de rule parado é o que faz o próximo agente concluir que a subtração está completa quando não está. Até a 0.11.0 a subtração varria só `Paineis::resources()` e as de Page eram **inalcançáveis** — o furo era inofensivo por acidente, porque nenhuma Page de administração existia ainda. `Paineis::permissoesDe()` fechou: todas são alcançáveis.

**Duas listas, dois motivos, dois alcances.** `permissoesDeAdministracaoDoApp()` (User, Convite) sai só do `panel_user` — são telas legítimas de quem administra a organização. `permissoesForaDoApp()` (hoje só o `ExceptionResource`) sai do `panel_user` **e** do `admin_app`: é Resource de vendor que está no painel por obrigação técnica e não é tela do `/app`. Unificar as duas reintroduz o defeito da 0.18.2, em que o `admin_app` herdava `DeleteAny:Exception`.

Esquecer não dá erro: os dois seeders rodam, tudo fica verde, e **todo usuário comum do negócio vira administrador da organização** — sem migration, sem 403, sem log. É a falha mais cara desta parte do kit porque ela só aparece quando alguém repara que o cliente está editando os próprios colegas.

A lista casa por **FQCN exato**, nunca por substring do nome da permission. Numa subtração o erro do substring é espelhado: tirar permissão de quem deveria tê-la.

Ao mexer em `Paineis::entidadesDoPainel()`, cuidado com o formato: Resource guarda `permissions` como `[affix => ['key' => …, 'label' => …]]`, Page e Widget como `[chave => rótulo]`. `array_column($e['permissions'], 'key')` numa Page devolve `[]` **sem erro nenhum** e a subtração volta a não subtrair nada — com cara de correção aplicada. Page e Widget usam `array_keys()`, como o próprio Shield em `getEntityPermissionKeys()`.

Teste: um caso conferindo que `panel_user` **não** tem `ViewAny:{SuaEntidade}` e **tem** a permissão de uma entidade de negócio — ver `it('mantem o usuario comum fora da administracao da organizacao')` e `it('alcanca Page e Widget na subtracao do painel app')` (este assere a chave de uma **Page** saindo de `permissoesDe()`; com a extração errada ele é o único que fica vermelho).

## Papel novo precisa declarar o painel

`roles.painel` é o que dá acesso ao painel — `User::canAccessPanel()` compara a coluna com o id do painel. **Nulo não é coringa**: papel sem painel não abre painel algum (o `master_global` entra pelo `Gate::before`, não pela coluna). Papel criado sem painel só carrega permissões, e quem o tiver sozinho autentica e leva 403 nos três painéis.

Papel semeado entra em `database/seeders/PapeisSeeder.php`, com o painel no terceiro argumento de `papel()`.

## Resource de model sem relação de posse com o tenant

Resource de painel COM tenancy cujo model não tem a relação `tenant()` (ex.: `User`, cujo vínculo é a pivot many-to-many `tenant_user`) precisa de `protected static bool $isScopedToTenant = false;`. Sem isso `Panel::boot()` registra o global scope nativo e a PRIMEIRA query do painel morre com `LogicException: The model [App\Models\User] does not have a relationship named [tenant]`.

Desligado o escopo nativo, o recorte é seu: escreva em `getEloquentQuery()` (nunca só na `table()` — o `getEloquentQuery()` é o que também alimenta o route binding, a busca ⌘K e o badge de contagem) e faça-o FALHAR FECHADO: sem `Filament::getTenant()`, devolva `->whereRaw('1 = 0')` e um `warning` no channel `autenticacao`. O escopo nativo, no mesmo cenário, falha ABERTO e devolve a base inteira da instalação. Consulta em Page nova é sempre `static::getEloquentQuery()`, nunca `Model::query()`.

Apontar `$tenantOwnershipRelationshipName` para a relação plural funciona e foi recusado: falha aberto, registra global scope no model compartilhado com o guard de autenticação, e traz junto o observer de vendor do `created`. Ver `app/Filament/App/Resources/Users/UserResource.php` e ADR-03 em `wikis/specs/main/admin-da-organizacao/`.

Teste em par: um caso conferindo `isScopedToTenant() === false` + `Model::query()->count()` sem exception, e outro conferindo que a query fecha (0, não "todos") sem tenant corrente.

## Resource novo decide import e export, e a decisão nasce escrita no arquivo

Ao criar um Resource com listagem, decida as duas coisas — importar CSV e exportar CSV — e deixe a decisão **no arquivo da Page**, ligada ou comentada. Ausência silenciosa não é decisão: ninguém volta para reavaliar o que nunca foi escrito.

O mecanismo é o nativo do Filament 5 (`ImportAction`, `ExportAction`, job, batch, notificação com botão de download). O kit não tem wrapper. O que ele acrescenta são duas classes-base em `app/Support/ImportExport/`, e cada uma existe por um motivo que não dá para omitir.

### As duas linhas, no `getHeaderActions()`

```php
ImportAction::make()
    ->importer(SeuImporter::class)
    ->authorize('import')
    // Só em painel COM tenancy e model com BelongsToTenant:
    ->options(fn (): array => ['tenant_id' => Filament::getTenant()?->getKey()]),

ExportAction::make()
    ->exporter(SeuExporter::class)
    ->authorize('export'),
```

Não faz sentido? Deixe as linhas **comentadas**, com uma frase dizendo o que descomentar expõe. Ver `app/Filament/App/Resources/Users/Pages/ListUsers.php`, onde export nasce comentado porque a planilha sai com o e-mail de todo mundo, e import não existe porque criar conta por CSV contorna convite, verificação de e-mail e atribuição de papel.

### `->authorize()` não é opcional

**Action do Filament não consulta policy sozinha.** O vendor diz isso em `Concerns/CanBeAuthorized.php`: a autorização default é `null`, ou seja, liberada para todo mundo. Sem a linha, quem abre a listagem exporta a listagem inteira.

`import` e `export` são métodos de policy do kit — estão em `config('filament-shield.policies.methods')` (e em `single_parameter_methods`, porque nenhum dos dois recebe registro) e geram `Import:{Model}` e `Export:{Model}`. **Depois de criar o Resource, ressemeie os dois seeders**, como manda a regra "Resource ou RelationManager novo exige gerar as permissões": sem a permission no banco a Action simplesmente não aparece, sem erro nenhum.

`panel_user` **não** herda as duas: `PapeisSeeder::ehPermissaoDeImportOuExport()` as subtrai por prefixo da ação, e é a única subtração do kit que casa por prefixo em vez de FQCN — de propósito, porque `Import:` só aparece em permissão de import, para qualquer model presente ou futuro. Resource novo nasce com as duas fora do usuário comum sem ninguém lembrar de nada.

### O import perde o tenant, e o export não

**Estenda `App\Support\ImportExport\ImportadorDoKit`, nunca o `Importer` do Filament.** `resolveRecord()` roda DENTRO do worker, onde `Filament::getTenant()` é `null` e o escopo global de `BelongsToTenant` vira no-op. O `ImportCsv` do Filament restaura o `auth()->setUser()` — o usuário, não o tenant. Sem a classe-base, duas coisas acontecem em silêncio: linha cuja chave colide com registro de OUTRA organização faz UPDATE nele, e linha nova nasce com `tenant_id` nulo, invisível para todo mundo. A base recebe o `tenant_id` das `options` (capturado no request, onde o tenant existe), escopa a resolução, preenche a criação e **recusa a linha** se a organização for necessária e não chegar.

**Estenda `App\Support\ImportExport\ExportadorDoKit` e declare `colunas()`, não `getColumns()`.** Ele liga `preventFormulaInjection()` em toda coluna — o default do Filament é desligado, por coluna, e célula começando em `=`, `+`, `-` ou `@` é fórmula quando alguém abre o CSV no Excel. O escopo por organização o export ganha de graça: a query vem da tabela da tela (`getTableQueryForExport()`), montada no request já com o `where tenant_id`, e é serializada com ele dentro.

### O gerador do Filament devolve colunas que não podem ir

`make:filament-importer Model -G` e `make:filament-exporter Model -G` inferem colunas do banco, e três classes de coluna precisam sair na mão:

- **A FK do tenant no import.** O gerador cria `ImportColumn::make('tenant')->relationship()`. Aceitá-la deixa o CSV escolher a organização de destino e torna a fronteira decorativa.
- **Segredo.** `token`, `token_lembrete`: `Convite::aceitar()` valida o token e vincula o usuário à organização com o papel do convite — CSV com essa coluna é planilha de chaves de entrada.
- **Payload livre.** `request`/`response` do ledger de IA são prompt e resposta completos, de qualquer organização.

`--force` põe tudo de volta. Os casos que reprovam estão em `tests/Kit/ImportExportTest.php`.

### Teste

Um caso chamando o **importador direto**, sem tenant no contexto — é a reprodução fiel do worker. Cenário que passa pela tela mede o contexto, não a fronteira: fica verde com a classe-base inteira removida. Ver `tests/Tenancy/ImportExportTenancyTest.php`.

E lembre: sem worker de fila nada processa. `composer dev` sobe um.

## Mídia em tabela

Coluna de imagem nasce com `->simpleLightbox()` e `->disk('public')` explícito, sem `defaultImageUrl()`. O plugin precisa estar registrado no painel: `simpleLightbox()` é macro do `boot(Panel $panel)`, e coluna num painel sem ele derruba a tela com `BadMethodCallException` na renderização. Documento (PDF/Office) só quando o arquivo é público e não sensível — o preview passa por Google/Microsoft.

## Qual pacote de widget

Gráfico é `filament-apex-charts`; stat card é `filament-stat-plus-easy`; o resto é `filament-dashboard-widgets`. Todo `ApexChartWidget` declara `$pollingInterval` (o default é 5 s por aba aberta) e `canView()` com `Schema::hasTable()` quando a fonte é tabela opcional — widget que estoura derruba o dashboard inteiro.

## Em Page, canAccess() sozinho basta; em Resource são dois métodos
Para esconder uma Page de painel por config/permissão, sobrescreva SÓ `canAccess()`. Um método cobre os três efeitos: `Page::registerNavigationItems()` retorna cedo quando `canAccess()` é falso (`vendor/filament/filament/src/Pages/Page.php:133-135`), a rota responde 403 via `abort_unless()` (`vendor/filament/filament/src/Pages/Concerns/CanAuthorizeAccess.php:8-15`), e a categoria `PagesAutorizadasCategory` do Spotlight consulta o mesmo método.

Em **Resource** são dois: `canAccess()` E `shouldRegisterNavigation()` — é o que `ProjetoResource`, `TenantResource`, `ConviteResource` e os dois `UserResource` fazem. Copiar esse par para uma Page acrescenta um método que não muda nada e sugere uma barreira a mais do que existe.

Exemplo do padrão certo: `App\Filament\Admin\Pages\HubDeAdministracao::canAccess()`.

A rota fica registrada e responde 403 (não 404). Tirá-la do ar exigiria recortar o `discoverPages()` do provider, e aí o Shield deixa de gerar a permission — a descoberta dele usa `$panel->getPages()` cru (`vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityDiscovery.php:30-34`). Ver ADR-02 de `wikis/specs/feature/v1-enriquecimento-kit/hub-de-cards-opcional/`.

## CardItem do hub sempre por DescobreCardsDoPainel, nunca à mão
`CardItem` **não verifica autorização**. `vendor/harvirsidhu/filament-cards/src/CardItem.php:22` não tem `canAccess()` — a única guarda da classe é `Concerns/CanBeHidden.php:13,20`, que avalia só `visible`/`hidden`. A verificação de acesso do pacote vive apenas dentro de `CardsPage::discoverClusterCards()` (`src/Filament/Pages/CardsPage.php:89,94`) e da descoberta de páginas de Resource (`:151`), que exigem Cluster ou página de Resource.

Consequência de escrever um cartão à mão: ele aparece para **todo mundo** e só devolve 403 no clique. Vaza a existência da tela e oferece um caminho que falha depois.

Regra: todo cartão de hub sai de `app/Filament/Concerns/DescobreCardsDoPainel.php`, que filtra pelo `canAccess()` de cada destino. `CardItem::make()` direto num `->cards()` é o defeito, não o atalho.

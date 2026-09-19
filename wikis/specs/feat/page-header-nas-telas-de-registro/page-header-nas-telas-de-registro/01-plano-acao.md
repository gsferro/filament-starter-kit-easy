# Plano de Ação — Page header nas telas de registro

> Fonte da verdade é o [`00-requisito.md`](00-requisito.md). Este documento é **interpretação**:
> onde ele divergir do `00`, o `00` vence.

## Natureza da Wiki

**Evolução.** Não nasce comportamento novo de negócio: as telas de `User` e `Tenant` já existem, já
autorizam e já gravam. O que entra é (a) uma camada de apresentação no topo de quatro telas, (b)
uma tela `ViewUser` que faltava em dois painéis, e (c) a permissão que ela consome — que **já
existe no banco** e nunca teve consumidor.

Superfície de UI: **presente, com JS** — o pacote registra um `AlpineComponent` e comportamento de
scroll (`PageHeaderServiceProvider::boot():21`). Criticidade: **sensível** — a entrega cria uma tela
que expõe dados de conta alheia, em painel com multi-tenancy.

Logo, o perfil do `feature-quality-gate` é **Completo** (A–L, até 3 ciclos).

## Dependências

| Dependência | Estado | Evidência |
|---|---|---|
| `mortalkiller/filament-page-header` `^2.1.5` | **instalada** | `composer.json:mortalkiller/filament-page-header:60`; lock em `v2.1.5` |
| `filament/filament` `^5.8.1` | **subida** (RQ-14, Adendo 2) | `composer.json:filament/filament:34`; lock em `v5.8.2` |
| Dependências transitivas novas | **nenhuma** | o `require` do pacote é só `filament/filament`, `illuminate/support`, `php` |
| `php artisan filament:assets` | **roda sozinho** | `composer.json:post-update-cmd:193` publica assets a cada `composer update` |

**Por que `^2.1.5` e não `^2.1`**: a v2.1.4 quebra no Filament 5.8.2. O `.fi-header-actions-ctn` do
5.8.2 passou a trazer `sm:self-end`, que conflita com o layout do pacote; a correção é
`align-self: auto` em `resources/css/page-header.css`. Quem resolvesse `2.1.4` teria as ações do
cabeçalho deslocadas, sem erro nenhum.

## Cobertura do Requisito

| RQ | Passo(s) | Entregue por | Estado |
|----|----------|--------------|--------|
| RQ-01 | 1 | `composer.json`, `composer.lock` | ✅ feito |
| RQ-02 | 3, 4, 5 | `Schemas\UserHeader` ×2, `HasPageHeader` em `EditUser` ×2 e `ViewUser` ×2 | pendente |
| RQ-03 | 3, 4 | `Schemas\TenantHeader`, `HasPageHeader` em `EditTenant` e `ViewTenant` | pendente |
| RQ-04 | 7 | `wikis/receitas.md` (receita nova), ADR-05, e o `ViewTenant` como exemplo executável | pendente |
| RQ-05 | — | três sub-agentes de mapeamento (telas, permissões, docs) + dossiê do pacote | ✅ feito |
| RQ-06 | — | branch `feat/page-header-nas-telas-de-registro` | ✅ feito |
| RQ-07 | 7 | `wikis/pacotes-candidatos.md`, `CHANGELOG.md`, o `02` e o `07` da wiki anterior, `PacotesRodada2Test` | pendente |
| RQ-08 | 9 | seis commits agrupados | pendente |
| RQ-09 | 10 | `composer bp:on` → aderência → `bp:off` | pendente |
| RQ-10 | 10 | `/code-review` no diff | pendente |
| RQ-11 | 11 | PR com suíte verde | pendente |
| RQ-12 | 7 | `docs/pt/comecar/atualizando-o-projeto.md` + par `en`; nenhum código novo — ver ADR-06 | pendente |
| RQ-13 | 5, 6 | `'view'` nos dois `getPages()`, reseed, testes tem/não-tem, `getViewAuthorizationResponse()` no `/app` | pendente |
| RQ-14 | 1 | `composer.json:filament/filament:34` | ✅ feito |

Nenhuma cláusula fica fora desta entrega.

---

## Passo 1 — A dependência *(RQ-01, RQ-14)* — **feito**

1. `composer require mortalkiller/filament-page-header:^2.1.5`
2. Corrigir o que o `composer require` gravou: ele escreveu o **pino** `"2.1.5"`; a constraint do kit
   é `^2.1.5`. Toda a `require` do kit é caret (verificado: 60 de 60 entradas, exceto
   `spatie/laravel-backup: *`).
3. `filament/filament` de `^5.6` para `^5.8.1` — Adendo 2.

**Oráculo de que o passo 3 não quebrou o que a rodada anterior comprou**: `[CT-32]` de
`tests/Kit/PacotesRodada2Test.php:70` continua verde. Medido: aceita `5.8.2`, aceita `5.99.99`,
recusa `6.0.0`.

## Passo 2 — Registrar o plugin *(RQ-02, RQ-03)*

`->plugin(PageHeaderPlugin::make())` no array `->plugins([...])` de **`AdminPanelProvider`** e
**`AppPanelProvider`**. O `/infra` fica fora — ver ADR-01.

**Uma instância por painel, nunca uma compartilhada.** `PageHeaderPlugin::make():33-36` devolve
`app(self::class)`, e o `PageHeaderServiceProvider` **não** registra binding de singleton
(`PageHeaderServiceProvider::boot():14-23` só faz `loadViewsFrom` e `FilamentAsset::register`) —
logo o container constrói uma instância nova a cada chamada, e o estado (`$options`,
`$resourceSchemas`) é por painel. Guardar a instância numa variável e reusá-la nos dois providers
faria a configuração de um vazar para o outro.

**O que o `register()` do plugin faz**, e é tudo: acrescenta um render hook `STYLES_AFTER` que emite
o `<link>` da CSS do pacote (`PageHeaderPlugin::register():60-68`). O hook é **escopado por
identidade** (`Filament::getCurrentPanel() === $panel`), então não cai no defeito de bucket vazio
que o kit já mediu em render hook sem `scopes:`.

## Passo 3 — Os três schemas de cabeçalho *(RQ-02, RQ-03)*

O pacote descobre a classe por **convenção sobre o model**, em
`HasPageHeader::getPageHeaderSchemaClass():65-66`:

```php
$class = substr($resource, 0, (int) strrpos($resource, '\\'))
    .'\\Schemas\\'.class_basename($resource::getModel()).'Header';
```

Então, literalmente:

| Resource | Classe a criar |
|---|---|
| `App\Filament\Admin\Resources\Users\UserResource` | `App\Filament\Admin\Resources\Users\Schemas\UserHeader` |
| `App\Filament\App\Resources\Users\UserResource` | `App\Filament\App\Resources\Users\Schemas\UserHeader` |
| `App\Filament\Admin\Resources\Tenants\TenantResource` | `App\Filament\Admin\Resources\Tenants\Schemas\TenantHeader` |

Assinatura obrigatória: `public static function configure(Schema $schema): Schema` — é o que
`getPageHeaderSchemaClass():68` exige (`is_callable([$class, 'configure'])`).

**Os diretórios `Schemas/` de Users não existem hoje** nos dois painéis; o de Tenants existe e tem
só `TenantForm.php`.

### `UserHeader` — o conteúdo

- **Identidade**: `->avatar(fn (User $record) => $record->getFilamentAvatarUrl())` e
  `->initials(fn (User $record) => $record->name)`.

  **Não** passar o avatar do painel (`Filament::getUserAvatarUrl()` / `AvatarDeIniciais`) — ver
  ADR-02. Em resumo: o provider do kit devolve `data:image/svg+xml;base64,…` e
  `Header::getAvatarUrl():174-177` recusa todo esquema fora de `http`/`https`, devolvendo `null`
  **sem erro**. `getFilamentAvatarUrl()` devolve `http://…/storage/…` (medido) e é aceito; quando
  não há foto ele devolve `null` e o slot cai nas iniciais, que é exatamente o desenho do kit.

- **Heading / description**: herdados. `Header::setUp():70-76` já liga `heading()` a
  `$livewire->getHeading()` e `description()` a `getSubheading()`. Declarar de novo o nome e o
  e-mail seria duplicar o que o Filament já resolve — o header recebe `name` e `email` pelos slots
  de metadata, não pelo título.

- **Badges**: a situação da conta. O `match` de `SituacaoDaConta::colunaDeSituacao():42-46` já
  decide Pendente/Inativo/Ativo com cor; o header reusa a mesma decisão em vez de reescrevê-la.

- **Metadata**: e-mail (`MetadataEntry` com `fieldIcon`), origem da conta
  (`User::rotuloDaOrigem():478-487`) e data de criação.

- **Sem `html: true`.** `Heading::html()` e `Subheading::html()` existem e desativam o `e($state)`
  de `Heading::getContent():30`. O estado aqui é `$record->name`, que é **entrada de usuário**.
  Proibido por teste de arquitetura — ADR-04.

### `TenantHeader` — o conteúdo

- **Identidade**: `->avatar(fn (Tenant $record) => $record->urlDaLogo())`. O método já confere
  `Storage::disk('public')->exists()` antes (`Tenant::urlDaLogo():138`), então não renderiza `<img>`
  quebrado. Queda para `->initials(fn (Tenant $record) => $record->nome)`, tingida com
  `->initialsBgColor(fn (Tenant $record) => $record->cor_primaria_nome)` — a organização já tem cor
  própria e o header é o lugar onde ela significa alguma coisa.
- **Badges**: `ativo` e `registro_habilitado`.
- **Metadata**: `slug`, contagem de usuários, data de criação.

## Passo 4 — O trait nas telas existentes *(RQ-02, RQ-03)*

`use HasPageHeader;` em:

| Arquivo | Painel |
|---|---|
| `app/Filament/Admin/Resources/Users/Pages/EditUser.php` | admin |
| `app/Filament/App/Resources/Users/Pages/EditUser.php` | app |
| `app/Filament/Admin/Resources/Tenants/Pages/EditTenant.php` | admin |
| `app/Filament/Admin/Resources/Tenants/Pages/ViewTenant.php` | admin |

**Nenhuma das quatro declara `getHeader()`** — verificado: `getHeader()` tem **zero ocorrências em
`app/`**. Isso importa porque o trait sobrescreve exatamente esse método, e uma página que já o
tivesse tornaria o pacote **inerte em silêncio** (`docs/specification.md:15`: *"a page getHeader
override wins"*). É a armadilha nº 1 da receita do passo 7.

O `ViewTenant` mantém `getHeaderActions()` e `getHeaderWidgets()`: o header do pacote renderiza as
ações dentro dele (`actions.blade.php:5` lê `$page->getCachedHeaderActions()`), e os widgets ficam
abaixo, em região de DOM disjunta.

## Passo 5 — `ViewUser` nos dois painéis *(RQ-02, RQ-13)*

O passo mais caro. Cinco entregas por painel:

1. **A classe** `Pages/ViewUser.php`, `extends ViewRecord`, com `use HasPageHeader;`. Molde:
   `ViewTenant:27`, **nunca** `ViewRole:11` — este declara `getActions():15`, que não é o hook do
   v5 (é `getHeaderActions()`), e por isso não renderiza. Defeito pré-existente, fora de escopo,
   registrado aqui para não ser copiado.

2. **A rota**, em `getPages()`, **antes** da de edição:

   ```php
   'view' => ViewUser::route('/{record}'),
   'edit' => EditUser::route('/{record}/edit'),
   ```

   A ordem é do molde `TenantResource::getPages():141-144`, que a comenta: `/{record}` é a rota mais
   curta e o Filament casa na ordem de declaração.

3. **O infolist** — `Schemas/UserInfolist.php`, e `infolist()` no Resource apontando para ele.
   Único molde do projeto: `app/Filament/Infra/Resources/AiRuns/Schemas/AiRunInfolist.php:configure:18-57`
   (`Section::make(...)->columns(n)->schema([TextEntry…])`).

4. **`ViewAction::make()`** nas `recordActions()` das duas tabelas. Hoje não existe em nenhuma das
   duas. Sem a página `view` registrada o `ViewAction` abriria **modal** em vez de navegar
   (`Resources/Pages/Page::getDefaultActionUrl():382-389` só devolve URL quando `hasPage('view')`);
   com ela, navega.

5. **`getViewAuthorizationResponse()` no `UserResource` do `/app`**, espelhando
   `getEditAuthorizationResponse():215-232`: nega quando o alvo `governaAInstalacao()`, com o mesmo
   `Log::channel('autenticacao')->warning`.

   **Por que não é redundante com a query.** `getEloquentQuery():198` já recorta por
   `User::queNaoGovernamAInstalacao()`, então o alvo some da listagem e o route binding devolve 404.
   Mas `.ai/rules/resources.md` é explícita: *"a query é falha de um só ponto — uma action nova que
   receba o model de fora da tabela passa por fora dela"*. E há a assimetria concreta: a Edit já tem
   as duas camadas; entregar a View com uma só a deixa **mais permissiva que a Edit**, que é a
   direção errada de todas.

**Autorização: nada a escrever além disso.** `ViewRecord::mount():71` chama `authorizeAccess()`, que
é `abort_unless(static::getResource()::canView($this->getRecord()), 403)`
(`ViewRecord::authorizeAccess():80`), e a cadeia termina em `UserPolicy::view():17-20` →
`can('View:User')`. `ViewRecord::hydrate():85` repete a checagem a cada hidratação Livewire.
Nada de `canAccess()`, nada de `ExigePermissaoDaTela` — esse trait é para **Page de painel**, e Page
de Resource autoriza pela policy do Resource.

## Passo 6 — A permissão *(RQ-13)*

**Nenhuma mudança de config, policy ou seeder.** `View:User` já é gerada e já é distribuída:

| Peça | Estado | Evidência |
|---|---|---|
| nome da permissão | `View:User` | `config/filament-shield.php:permissions.separator:141` (`:`) + `case:142` (pascal) + `view` em `policies.methods:183` + `resources.subject:260` (`model`) |
| `UserPolicy::view()` | existe | `app/Policies/UserPolicy.php:view:17-20` |
| `admin` a recebe | sim | matriz do painel admin, `PapeisSeeder::run():58-59` |
| `admin_app` a recebe | sim | matriz do painel app menos `permissoesForaDoApp()`, `PapeisSeeder::run():80-85` |
| `panel_user` **não** recebe | correto | subtraída em bloco por FQCN em `PapeisSeeder::permissoesDeAdministracaoDoApp():171` |
| `infra` não recebe | correto | não é da matriz do painel |

O que o passo faz é **ressemear** e **provar**:

```bash
php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
```

**O enforço tem de ser escrito.** Nenhum teste do kit enumera as permissões de um papel, e os dois
inventários que ficam vermelhos ao nascer superfície nova **não alcançam** esta:
`PermissoesDeAcoesTest::superficiesDeAcaoDoKit():414-450` casa só `Action::make('nome')` literal
(`ViewAction::make()` não casa), e `InventarioDeTelasTest` filtra sozinho toda rota com `{record}`
(`UrlGenerationException` → `null`). `PermissoesDeResourcesTest` tem âncora de **Resource**, e o
`UserResource` já está lá.

Ou seja: sem os casos do passo 8, a tela pode nascer sem consultar permissão nenhuma e **a suíte
fica verde**. É exatamente o defeito que a RQ-13 nomeia.

## Passo 7 — Documentação e correção do registro *(RQ-04, RQ-07, RQ-12)*

### A receita *(RQ-04)*

Seção nova em `wikis/receitas.md`, logo após `## RelationManager novo:200` — é o vizinho temático,
porque a receita é "resumo do registro no topo + abas para os relacionamentos".

Conteúdo, no formato do arquivo (heading `##` → passos numerados com PHP curto → nota `>` com a
armadilha):

1. criar `Schemas/{Model}Header.php` (a convenção é sobre o **model**, não sobre o Resource);
2. `use HasPageHeader;` na página;
3. combinar com relations: `hasCombinedRelationManagerTabsWithContent()`
   (`vendor/filament/filament/src/Resources/Pages/Concerns/HasRelationManagers.php:96`) +
   `getContentTabLabel()` (`ViewRecord.php:62`) — o infolist vira a **primeira aba** e os
   relation managers as seguintes, com o header rico acima de todas;
4. `php artisan filament:assets` depois de atualizar o pacote.

Três armadilhas na nota:
- página que já sobrescreve `getHeader()` torna o trait **inerte em silêncio**;
- o avatar do painel (`data:` URI) é descartado por `Header::getAvatarUrl():174-177`;
- `hideWhenCompact()` e `retainSummaryWhenCompact()` estão `@deprecated` no vendor
  (`Header.php:385` e `:328`) — a API viva é `whenCompact()` com `HeaderPart`.

Ganchos a atualizar junto: a tabela `## Problemas comuns:795` e o checklist `## Antes de
entregar:784`.

### A doc de usuário *(RQ-04, RQ-12)*

| Arquivo (e o par em `docs/en/`) | O que entra |
|---|---|
| `docs/pt/referencia/pacotes-instalados.md` | linha nova na tabela do grupo de UX de painel |
| `docs/pt/comecar/atualizando-o-projeto.md` | o passo manual do `kit:update`: `composer require` + `filament:assets` + **os dois seeders** |
| `docs/pt/operacao/depois-de-criar-resources.md` | a `ViewUser` como caso da mecânica já descrita ali |

O link para a receita segue o padrão do repositório: URL absoluta do GitHub com âncora, como
`docs/pt/recursos/import-export-csv.md:196`.

### A correção do registro *(RQ-07)*

Quatro arquivos, não um — o veredito ADIAR está replicado:

| Arquivo | Hoje | Vira |
|---|---|---|
| `wikis/pacotes-candidatos.md:590` | `\| **ADIAR** \| Exige filament ^5.8.1 (kit em 5.7.6); repo de 5 dias…` | **ADOTADO**, com **onde no código** |
| `…/estudo-de-pacotes-rodada-2/…/02-decisoes-arquiteturais.md:463` | ADIAR | nota de reabertura apontando esta wiki |
| `…/07-dossies-dos-pacotes.md:40` e `:56` | ADIAR (1 de 3 motivos já riscado) | os outros dois motivos, resolvidos ou aceitos |
| `CHANGELOG.md:90` | *"dez indicados, nenhum adotado"* | corrigido: um foi adotado depois |

O pacote também precisa entrar em `wikis/pacotes-candidatos.md:## 1. Instalados hoje:55`, que hoje
diz "os 51 pacotes de `require`".

`wikis/pacotes-ranking.md` **não cita o pacote** — nada a fazer lá.

### O oráculo travado

`tests/Kit/PacotesRodada2Test.php` reprova esta entrega **por desenho**, e as duas metades são
legítimas:

- `[CT-33]:110` casa o veredito na mesma linha do nome Composer, e o dataset declara
  `'mortalkiller/filament-page-header' => 'ADIAR':37`;
- `[CT-34]:148` assere que **nenhum** dos dez entrou nas dependências.

Não se apaga o caso. O dataset passa a declarar `ADOTAR` para este pacote, o `[CT-34]` passa a
varrer os **nove** que continuam fora, e um caso novo assere que este **está** no `require` — a
asserção de ausência vira asserção de presença para exatamente uma linha, e continua não-vácua.

### `kit:update` *(RQ-12)*

**Nenhum código novo.** Verificado em `app/Console/Commands/KitUpdate.php`:

- os caminhos que esta entrega toca já estão em `CAMINHOS_DO_KIT`: `app/Filament:101`,
  `app/Providers:137`, `database/seeders:192`, `tests/Kit:219`, `wikis/receitas.md:260`,
  `wikis/pacotes-candidatos.md:256`;
- a dependência nova entra sozinha no relatório: `relatarComposerJson():941-972` filtra por
  `/^[+-]\s{8,}"[^"]+"\s*:/`, que casa a linha do `require`, e imprime `warn` + `note`;
- `composer.json` nunca é aplicado, de propósito (`CAMINHOS_SO_RELATORIO:305-307`).

**A lacuna real**, que a doc fecha: `encerrar():1032-1039` lista `filament:assets`,
`composer test:kit` e `composer test` — e **não** cita os dois seeders. Quem atualiza e ganha a
`ViewUser` precisa ressemear, e hoje nada diz isso. Ver ADR-06 sobre por que a correção é
documental e não código.

## Passo 8 — Testes *(todas as RQ)*

Os casos vão para `04-casos-de-teste.md` e `05-casos-de-teste-browser.md`, delegados à
`feature-test-design`. As famílias, com o que cada uma protege:

| Família | Protege |
|---|---|
| **Permissão** | `View:User` consultada de verdade: `semAPermissao('admin', 'View:User')` → 403, e o par com a permissão → 200. Sem isto a tela pode nascer aberta com a suíte verde |
| **Tenancy** | URL direta para conta de outra organização → 404 (molde `FronteiraDoAdminAppTest:[CT-02]:109-125`); `getViewAuthorizationResponse()` chamada **direto** com alvo que governa a instalação → `denied()` |
| **Header renderiza** | o `fph-root` presente nas quatro telas + nas duas `ViewUser`; e o controle negativo: sem o plugin no painel, `pageHeaderIsEnabled()` é falso e o header é o nativo |
| **Avatar** | o `data:` URI do `AvatarDeIniciais` **não** chega ao header, e o `getFilamentAvatarUrl()` chega. É o ADR-02 virando oráculo |
| **Arquitetura** | `html: true`, `hideWhenCompact()` e `retainSummaryWhenCompact()` proibidos em `app/`. Filtrando comentário antes de afirmar ausência — `.ai/rules/testes.md` |
| **Guarda de CSS** | toda classe que as blades do pacote emitem existe em `page-header.css` ou é `fi-*`. Hoje: 26 `fph-*` emitidas, 26 definidas, 2 `fi-*`. Com piso de contagem, senão um regex quebrado fica verde sobre lista vazia |
| **Ordem da rota** | `'view'` antes de `'edit'` nos dois `getPages()` |

**Armadilha de suíte** já conhecida: `noPainelBootado('app')` **não** serve para o `/app` — o
`BreezyCore::boot()` lê `route()->parameter()` e morre sem request. Caso Livewire do `/app` precisa
de um `GET` real antes. E `admin_app` só existe em `tests/Tenancy`.

## Passo 9 — Commits agrupados *(RQ-08)*

| # | Escopo | Conteúdo |
|---|---|---|
| 1 | `deps` | `composer.json`/`lock`: o pacote e a constraint do Filament |
| 2 | `filament` | plugin nos dois providers + os três `Schemas\*Header` + trait nas quatro telas |
| 3 | `filament` | `ViewUser` ×2, rota, infolist, `ViewAction`, `getViewAuthorizationResponse()` |
| 4 | `test` | as sete famílias de caso |
| 5 | `docs` | receita, docs pt/en, `pacotes-candidatos.md`, `CHANGELOG.md`, correção da wiki anterior + oráculo do `PacotesRodada2Test` |
| 6 | `docs` | a wiki desta feature |

## Passo 10 — Portões *(RQ-09, RQ-10)*

1. `composer test` verde
2. `composer bp:on` → aderência ao Blueprint → `composer bp:off` → guarda de
   `BlueprintForaDoPacoteTest` → árvore limpa
3. `/code-review` no diff, por quem não implementou
4. `feature-quality-gate`, perfil Completo

## Passo 11 — PR *(RQ-11)*

Só depois da suíte verde. A descrição declara o que ficou aberto, sem maquiar.

## Padrão de Log

Um ponto novo de log nesta entrega, e ele reusa canal existente:

```php
Log::channel('autenticacao')->warning('[UserResource@getViewAuthorizationResponse] Visualização negada: alvo governa a instalação.', [
    'alvo_id' => $record->getKey(),
    'ator_id' => Filament::auth()->id(),
    'tenant_id' => Filament::getTenant()?->getKey(),
]);
```

Espelha `getEditAuthorizationResponse():215-232`, mesmo canal, mesmo nível, mesmo formato
`[Classe@Método]`. **Sem e-mail nem nome no context** — só chaves. O canal `autenticacao` já é lido
pela trilha de `/infra`, e a dimensão D do quality gate reprova PII em log.

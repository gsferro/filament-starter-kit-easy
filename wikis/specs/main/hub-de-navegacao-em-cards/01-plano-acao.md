# Plano de Ação — Hub de navegação em cards

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: —
- **Motivo**: pacote novo (`harvirsidhu/filament-cards`) e um padrão de navegação novo no kit
- **Toca infra compartilhada?**: **sim** → três `PanelProvider`, o `KitServiceProvider` (registro de CSS), **três Pages novas → três permissions novas** e, portanto, os seeders do Shield e a matriz de papéis.

> Regressão **obrigatória**. As três Pages entram em `FilamentShield::getEntitiesPermissions()` e, no painel `app`, na conta da subtração do `panel_user` — a área coberta por `it('mantem o usuario comum fora da administracao da organizacao')` e `it('alcanca Page e Widget na subtracao do painel app')` em `tests/Kit/`. Rodar a suíte `kit` inteira, não só os testes novos.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Pacote instalado e integrado | 1, 2, 3 | inclui o CSS do ADR-02 |
| RQ-02 | Aplicado onde há múltiplos caminhos | 5, 6, 7 | `/infra` é o caso forte: 4 grupos de navegação + plugins |
| RQ-03 | Documentado | 9 | `wikis/pacotes.md`, `wikis/receitas.md` |
| RQ-04 | Vira sugestão de uso para quem cria página/resource | 9, 11 | checklists em `receitas.md` + candidato a rule em `.ai/rules/filament.md` |
| RQ-05 | Exemplos e casos de uso bem documentados | 9 | receita com os 4 casos de uso e o que **não** fazer |
| RQ-06 | Implementado nas páginas atuais com oportunidade | 5, 6, 7 | três hubs |

## Objetivo

Instalar o `harvirsidhu/filament-cards` e criar, em cada painel, uma página **hub**: uma grade de cartões com os destinos daquele painel, filtrada pelo que o usuário corrente realmente pode acessar.

O ganho está no `/infra`, onde a barra lateral hoje soma quatro grupos de navegação (`Observabilidade`, `IA`, `Trilhas`, `Sistema`) mais os itens soltos de sete plugins. Quem entra procurando "onde vejo os backups" percorre uma árvore; com o hub, lê uma grade agrupada, com descrição e badge, e ainda pode filtrar por texto.

Junto vem a parte durável: a **sugestão de uso** para o próximo agente que criar uma página ou resource, para que o padrão seja considerado sem que alguém precise lembrar dele.

## Contexto

O kit tem hoje três painéis com densidades muito diferentes:

- **`/infra`** — 1 Resource próprio (`AiRuns`), 1 Page (`Pulse`), 1 item de navegação externo (Dashboard de IA) e **sete plugins** que injetam páginas próprias (health, backups, jobs, logs, auditoria, authentication log, command center, dependency graph, release notifier). É o painel onde "onde fica X?" é uma pergunta real.
- **`/admin`** — 5 Resources (Users, Roles, Convites, Tenants, AgentesIa) + dashboard com 6 widgets.
- **`/app`** — 3 Resources (Projetos, Convites, Users) + a Page `ConvitesRecebidos`, escopados por organização.

Nenhum deles tem página de aterrissagem além do `Dashboard`, que é feito de widgets — ou seja, responde "como estão as coisas", nunca "para onde eu vou".

## Análise dos Arquivos Existentes

### `app/Providers/Filament/{Admin,App,Infra}PanelProvider.php`

Cada um já faz `->discoverPages(in: app_path('Filament/{Painel}/Pages'), …)`. **Consequência**: basta criar o arquivo da Page na pasta certa — nenhum registro manual em `->pages([...])` é necessário. O array `->pages([Dashboard::class])` continua como está.

### `app/Providers/KitServiceProvider.php:146-152` — `configureCorrecoesDeCss()`

```php
FilamentAsset::register(
    [Css::make('kit-correcoes', resource_path('css/filament/kit.css'))],
    package: 'kit',
);
```

É exatamente o mecanismo de que o CSS dos cards precisa. **Recebe mais um item no array**, não um método novo.

### `app/Support/Paineis.php` + `database/seeders/PapeisSeeder.php:117-124`

`permissoesDeAdministracaoDoApp()` lista as entidades do painel `app` que o `panel_user` **não** herda. Hoje: `UserResource` e `ConviteResource`.

**O hub do `/app` NÃO entra nessa lista** — ele é navegação de negócio e todo usuário do painel deve vê-lo. Os cartões dentro dele já se escondem sozinhos pelo `canAccess()` de cada destino (passo 4), então um `panel_user` vê o hub com os cartões dele, sem os de administração.

### `app/Filament/Concerns/BadgeContagemNavegacao.php`

Concern existente que mostra o padrão do kit para comportamento compartilhado entre Resources. O concern novo do passo 4 segue a mesma pasta e o mesmo estilo.

### `resources/css/filament/kit.css`

Modelo de como o kit escreve CSS de correção: comentário longo explicando **por que** a regra existe, e uso das variáveis do Filament (`var(--primary-500)`) em vez de cor literal. O `cards.css` segue o mesmo padrão.

## Autorização

- **Policies**: nenhuma escrita à mão. O Shield gera a policy de cada Page nova.
- **Gates**: nenhum.
- **Middleware**: nenhum.
- **Permissions novas**: `View:HubDeInfraestrutura` (painel infra), `View:HubDeAdministracao` (admin), `View:HubDoNegocio` (app) — nomes conforme o Shield derivar da classe.
- **Obrigatório após criar as Pages** (regra de `.ai/rules/filament.md`):

```bash
php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
```

Sem isso, as três telas respondem **403 para todo mundo** que não seja `master_global`.

- **Regra de subtração do painel `app`**: o hub do `/app` **não** entra em `PapeisSeeder::permissoesDeAdministracaoDoApp()`. Decisão consciente, com CT dedicado — é o inverso do erro que a rule descreve, e igualmente detectável.

## Rotas

Três rotas novas, registradas automaticamente pelo `discoverPages()`:

| Método | URI | Name | Middleware |
|--------|-----|------|------------|
| GET | `/infra/hub-de-infraestrutura` | `filament.infra.pages.hub-de-infraestrutura` | painel infra (`Authenticate`) |
| GET | `/admin/hub-de-administracao` | `filament.admin.pages.hub-de-administracao` | painel admin |
| GET | `/app/{tenant}/hub-do-negocio` | `filament.app.pages.hub-do-negocio` | painel app + tenancy |

> Os slugs finais saem do Filament a partir do nome da classe. Confirmar com `php artisan route:list --path=hub` depois de criar.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `HubDeInfraestrutura` | Filament (CardsPage) | `/infra/hub-de-infraestrutura` | lê a grade, filtra por texto, clica no cartão | **Sim** — o filtro é Alpine (`x-data`, `x-show`), client-side |
| `HubDeAdministracao` | Filament (CardsPage) | `/admin/hub-de-administracao` | idem | **Sim** |
| `HubDoNegocio` | Filament (CardsPage) | `/app/{tenant}/hub-do-negocio` | idem | **Sim** |

**Gate de CT-B**: duas afirmações só o navegador prova — (a) o **filtro por texto**, que é Alpine puro e não passa pelo servidor, e (b) que os cartões **têm a aparência de cartão**, que é justamente o risco do ADR-02 (CSS ausente = grade de links soltos, com o HTML idêntico). `assertSee` não distingue os dois casos.

**Gate de tela de escrita**: nenhuma rota `create`/`edit` é criada ou alterada. Não se aplica.

## Variáveis de Ambiente

Nenhuma.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **Matriz de permissões**: três permissions novas. O `PapeisSeeder` precisa rodar; o `panel_user` do `/app` ganha `View:HubDoNegocio` por herança da matriz do painel — que é o comportamento desejado, e tem CT.
- **Busca ⌘K**: `PagesAutorizadasCategory` varre as Pages autorizadas do painel; os hubs passam a aparecer nos resultados. Efeito colateral desejado, sem ação.
- **`tests/Kit/PaineisTest.php` e `PaginasInfraTest.php`**: contam entidades e visitam páginas. Se algum caso assere **número** de Pages ou de permissions, ele quebra e precisa ser atualizado — não relaxado. Verificar antes de implementar.
- **`FilamentAsset`**: mais um `Css::make()` no registro do kit. Depois de mexer, `php artisan filament:assets`.
- **Navegação**: três itens novos na barra lateral (os próprios hubs). Ordenados com `$navigationSort` baixo para ficarem no topo.

## Rollback

- **Migration down**: não há migration. As permissions ficam em banco; para removê-las, rodar `ShieldPermissionsSeeder` depois de apagar as classes (o `shield:generate --all` recalcula).
- **Reverter**: apagar as três Pages e o concern, remover o `Css::make('kit-cards', …)`, `composer remove harvirsidhu/filament-cards`, rodar os dois seeders, `php artisan filament:assets`.
- **Kill-switch**: as Pages têm `canAccess()` padrão do Shield — revogar a permission esconde a tela sem mexer em código.

## Dependências

- **Composer**: `harvirsidhu/filament-cards` `^1.0` (v1.0.9 em 2026-07-26; requer PHP ^8.2, Filament ^4|^5 — compatível com PHP 8.4 e Filament 5.6 do kit)
- **NPM**: nenhuma. O caminho oficial pediria `@source` no `theme.css` + `npm run build`; o kit troca isso pelo CSS registrado do ADR-02.

## Riscos

- **Cards sem estilo** (o risco principal): o plugin não registra CSS. Se a CSS pré-compilada do Filament não trouxer alguma classe da blade, o cartão perde borda, sombra ou grade — **sem erro nenhum**, com o HTML correto. Mitigação: passo 3 é empírico (abrir a tela e comparar), e há CT-B com screenshot.
- **Três classes da blade são impossíveis de gerar por Tailwind**, mesmo com tema: `2xl:grid-cols-{$count}`, `lg:col-span-{$lgSpan}` e `group-hover:{$iconHoverColorClass}` são montadas por interpolação de string. Mitigação: o kit **não usa** as APIs que passam por elas — `$columns` fica em 3 ou 4, `columnSpan(['lg' => n])` não é usado e a cor de ícone no hover é aceita como perda cosmética. Registrado em ADR-03 e escrito na receita.
- **`CardItem` manual NÃO verifica `canAccess()`** (`src/Concerns/CanBeHidden.php` só avalia `visible`/`hidden`; o filtro por `canAccess()` existe apenas dentro de `discoverClusterCards()`/`discoverResourceCards()`). Um cartão declarado à mão aparece para todo mundo e só devolve 403 no clique — vaza a existência da tela. Mitigação: o concern do passo 4 aplica o filtro **por construção**; nenhum `CardItem::make()` cru entra nas três Pages. CT dedicado.
- **Permissions esquecidas**: 403 geral. Mitigação: passo 8 e a rule já existente.

## Channel de Log da Feature

### Verificação de Channel Existente

Channels do kit hoje em `config/logging.php`: `ai` (85), `tenancy` (93), `autenticacao` (101).

### Decisão

**Nenhum channel novo, e nenhum log novo.** Montar uma grade de links não é operação auditável, e um log por renderização de página seria ruído proporcional ao tráfego.

> **Considerado e recusado na auditoria Ponytail**: um `warning` no channel `autenticacao` para o caso "sem painel corrente". O precedente do kit (`UserResource::getEloquentQuery()`) existe porque **job, comando e a busca ⌘K** chamam aquele método fora de request de painel. Aqui não há segundo chamador: `cardsDoPainel()` só é invocado por `getCards()` de uma `CardsPage`, que nunca renderiza fora de um request de painel. Guarda para estado inalcançável é código morto com cara de robustez. Se um dia surgir um chamador externo, a guarda nasce com ele.

## Estrutura de Implementação

### 1. Instalar o pacote

> Skills: `ponytail`

```bash
composer require harvirsidhu/filament-cards:"^1.0"
```

**Não** acrescentar `@source` a nenhum `theme.css` — o kit não tem tema customizado, e a decisão do usuário é o caminho do passo 3.

### 2. Registrar o plugin nos três painéis

> Skills: `laravel-best-practices`

- **Paths**: `app/Providers/Filament/{Admin,App,Infra}PanelProvider.php`, dentro de `->plugins([...])`

```php
use Harvirsidhu\FilamentCards\FilamentCardsPlugin;

// …
FilamentCardsPlugin::make(),
```

- **Verificar antes de escrever**: confirmar em `vendor/harvirsidhu/filament-cards/src/FilamentCardsPlugin.php` se o plugin faz algo além de registrar id — se for inerte, registrá-lo mesmo assim, por simetria com os demais plugins e para o dia em que passar a registrar asset.

### 3. CSS dos cards, registrado pelo mecanismo do kit

> Skills: `tailwindcss-development`

- **Path novo**: `resources/css/filament/cards.css`
- **Path alterado**: `app/Providers/KitServiceProvider.php` → `configureCorrecoesDeCss()`

```php
FilamentAsset::register(
    [
        Css::make('kit-correcoes', resource_path('css/filament/kit.css')),
        Css::make('kit-cards', resource_path('css/filament/cards.css')),
    ],
    package: 'kit',
);
```

**O conteúdo do `cards.css` é determinado empiricamente**, nesta ordem:

1. Instalar, criar uma das Pages (passo 5), abrir a tela
2. Comparar com o esperado: cartão com fundo, canto arredondado, anel de 1px, sombra no hover e grade responsiva
3. Para cada classe da blade que **não** pintar, escrever a regra equivalente em CSS puro, usando as variáveis do Filament — nunca cor literal

Candidatas conhecidas por leitura de `cards-page.blade.php` (o `colorMap` e o `iconColorMap` são os mais prováveis, porque `border-success-500` e irmãs não são usadas pelo Filament nos componentes dele):

```css
/* Bordas de destaque do CardItem::color() */
.border-primary-500 { border-color: var(--primary-500); }
.border-success-500 { border-color: var(--success-500); }
.border-danger-500  { border-color: var(--danger-500); }
.border-warning-500 { border-color: var(--warning-500); }
.border-info-500    { border-color: var(--info-500); }
```

- **Cabeçalho obrigatório no arquivo**, no padrão do `kit.css`: por que este CSS existe (o pacote não registra o dele), por que não é `resources/css/app.css` (o painel não carrega o Vite do app), e o que fazer depois de editar (`php artisan filament:assets`).
- **Regra de disciplina**: escrever **apenas** o que faltou de fato. Copiar preventivamente todo o `colorMap` sem verificar é o oposto do que a escada do Ponytail manda.

### 4. Concern de descoberta de cards do painel

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Concerns/DescobreCardsDoPainel.php`

Responsabilidade única: transformar os Resources e Pages **do painel corrente** em `CardItem`, filtrados por `canAccess()` e agrupados pelo grupo de navegação de cada um.

```php
namespace App\Filament\Concerns;

use Filament\Facades\Filament;
use Harvirsidhu\FilamentCards\CardGroup;
use Harvirsidhu\FilamentCards\CardItem;

trait DescobreCardsDoPainel
{
    /**
     * Os destinos do painel corrente que o usuário PODE acessar, como cartões.
     *
     * @param  list<class-string>  $excluir  classes que não viram cartão (a própria Page, o Dashboard)
     * @return list<CardGroup>
     */
    protected static function cardsDoPainel(array $excluir = []): array
    {
        // …
    }
}
```

Regras que a implementação tem de cumprir:

- **`canAccess()` sempre.** Cada Resource/Page candidato só vira cartão se `$classe::canAccess()` for verdadeiro. Este é o ponto do concern: `CardItem` **não** faz essa verificação sozinho (`Concerns/CanBeHidden.php` avalia apenas `visible`/`hidden`), e `discoverClusterCards()` — que faz — só funciona dentro de um Cluster, que o kit não usa.
- **Sem guarda de painel nulo.** `Filament::getCurrentPanel()` é o painel do request corrente, e o único chamador do concern é uma Page que só existe dentro de um. Ver a nota da auditoria Ponytail na seção de log.
- **Rótulo, ícone, descrição e badge vêm da própria classe**: `getNavigationLabel()`, `getNavigationIcon()`, `getNavigationBadge()`, `getNavigationSort()`. Nada é redigitado — resource renomeado se renomeia no hub sozinho.
- **Agrupamento** por `getNavigationGroup()`, preservando a ordem declarada em `->navigationGroups([...])` do painel quando ela existir. Sem grupo → um `CardGroup` sem rótulo, no fim.
- **Exclusões** vêm por parâmetro, não por lista fixa dentro do concern.

> **Por que um concern e não três listas escritas à mão**: são três Pages consumindo a mesma lógica, e a alternativa manual repete `->visible(fn () => X::canAccess())` em cada cartão — omitir um é um vazamento silencioso de existência de tela. Além disso, lista manual não acompanha Resource novo: o hub ficaria incompleto sem ninguém perceber. Ver ADR-04.

### 5. Hub do painel `/infra`

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Infra/Pages/HubDeInfraestrutura.php`

```php
class HubDeInfraestrutura extends CardsPage
{
    use DescobreCardsDoPainel;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = -10;   // no topo, antes dos grupos

    protected static int|string|array $columns = 3;

    protected static bool $searchable = true;

    protected static ?string $title = 'Central de infraestrutura';

    protected static function getCards(): array
    {
        return static::cardsDoPainel(excluir: [static::class, Dashboard::class]);
    }
}
```

- **Tipos das propriedades**: respeitar as uniões do Filament 5 (`string|BackedEnum|null $navigationIcon`, `string|UnitEnum|null $navigationGroup`). Verificar no `vendor/harvirsidhu/filament-cards/src/Filament/Pages/CardsPage.php` se `$columns` é `int|string|array` antes de sobrescrever.
- **`$searchable = true` aqui**: é o painel com mais destinos, onde o filtro paga.
- **Sem `$navigationGroup`**: o hub fica solto no topo, acima dos quatro grupos, como porta de entrada.

### 6. Hub do painel `/admin`

- **Path**: `app/Filament/Admin/Pages/HubDeAdministracao.php`
- Mesma estrutura; `$title = 'Hub de administração'`, `$columns = 3`, `$searchable = true`.

### 7. Hub do painel `/app`

- **Path**: `app/Filament/App/Pages/HubDoNegocio.php`
- Mesma estrutura; `$title = 'Início'`, `$columns = 3`.
- **`$searchable = false`**: com 4 destinos, um campo de busca é ruído.
- **Atenção de tenancy**: a Page vive num painel com tenant. Nada a fazer no código — os Resources é que carregam o escopo —, mas o CT precisa entrar com organização corrente, senão o `getUrl()` de cada cartão não resolve.
- **NÃO acrescentar a `permissoesDeAdministracaoDoApp()`** (ver seção Autorização).

### 8. Regenerar permissões

> Skills: nenhuma

```bash
php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
```

Nesta ordem, conforme `.ai/rules/filament.md`. Ambos idempotentes.

### 9. Documentação do kit

> Skills: nenhuma

- **`wikis/pacotes.md`** → "Já existe — não escreva de novo": grade de links de navegação já tem dono; não escrever Blade de cartões à mão.
- **`wikis/receitas.md`** → receita **"Página hub de cards"**, com:
  - o esqueleto de `CardsPage` + `DescobreCardsDoPainel`
  - **os quatro casos de uso** (RQ-05): (1) porta de entrada de painel denso; (2) hub de configurações agrupando páginas de settings; (3) página inicial de Cluster, via `discoverClusterCards()`; (4) atalhos externos, com `CardItem::make('https://…')->openUrlInNewTab()`
  - **o que NÃO fazer**: `CardItem::make()` cru sem `canAccess()`; `columnSpan(['lg' => n])` e cor de ícone no hover (classes interpoladas que nunca são geradas — ADR-03); usar o pacote como componente de formulário
  - o lembrete dos dois seeders (Page nova = permission nova)
  - o lembrete do `filament:assets` ao mexer no `cards.css`
- **`wikis/receitas.md` → "Resource novo" e "Página de painel"**: uma linha em cada checklist perguntando se o destino novo deve entrar num hub — é o formato de "sugestão de uso" do RQ-04.
- **`wikis/convencoes.md`** → "Armadilhas já resolvidas": `CardItem` não checa `canAccess()`; classes interpoladas da blade.

### 10. README — dependência

- **Path**: `README.md`, seção `### UI e produtividade`

```markdown
| [harvirsidhu/filament-cards](https://packagist.org/packages/harvirsidhu/filament-cards) | páginas hub: grade de cartões de navegação em vez de árvore na barra lateral |
```

- Se o roteiro de features do README listar as telas do kit, acrescentar os três hubs lá também.

### 11. Candidato a rule de projeto

> Skills: `requirement-to-rule`

Propor ao usuário (**sem gravar sem aprovação**), para `app/Filament/**`:
página com muitos destinos usa `CardsPage`; `CardItem` manual não verifica `canAccess()`; classes interpoladas da blade não são geradas. Ver step 9 da skill `feature-wiki`.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`**.
>
> Aplicações concretas nesta wiki:
> - **um** concern compartilhado, não três listas de cartões escritas à mão — menos código total **e** menos superfície de esquecimento de `canAccess()`
> - o CSS do passo 3 é escrito **depois** de olhar a tela, e só o que faltou. Copiar o `colorMap` inteiro preventivamente é adivinhação com cara de completude
> - nenhum Cluster é criado; nenhuma configuração publicada; nenhuma variável de ambiente
>
> Atalhos deliberados marcados com `ponytail:` comment.
>
> **Caveman ativo em modo `full`** na comunicação agent ↔ usuário. Wiki, código, commits e PRs são boundary — prosa normal.

## Testes

> Ver `04-casos-de-teste.md` (componente Livewire) e `05-casos-de-teste-browser.md` (filtro Alpine e aparência de cartão).

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `composer types:check`
- [ ] `vendor/bin/pest --group=kit --compact`
- [ ] `composer test:browser`
- [ ] `php artisan route:list --path=hub` — as três rotas existem com os slugs esperados
- [ ] Abrir os três hubs com `master_global`, com papel de painel e com `panel_user`, conferindo que a grade muda

## Commits

- `:package: instala o harvirsidhu/filament-cards e registra o CSS do kit`
- `:sparkles: hub de navegacao em cards nos tres paineis`
- `:lock: cards do hub respeitam o canAccess de cada destino`
- `:white_check_mark: testes dos hubs de navegacao`
- `:memo: documenta a pagina hub de cards e quando usa-la`

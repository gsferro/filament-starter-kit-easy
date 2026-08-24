# Plano de Ação — página de boas-vindas na rota `/`

> Requisito: `00-requisito.md`
> Decisões: `02-decisoes-arquiteturais.md`
> Desenho: `design/Main.dc.html` → https://claude.ai/code/artifact/cd1677da-a5f4-44f0-9995-70baf64e0552

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: — (nenhuma; a rota `/` nunca foi tocada por feature do kit; o único commit
  que escreveu `resources/views/welcome.blade.php` é o skeleton inicial)
- **Motivo**: —
- **Toca infra compartilhada?**: **não**. Nada de seeder de permissões, migration, middleware
  global, `tests/Pest.php` ou config compartilhada. A página não é registrada em painel nenhum,
  logo não entra na matriz do Shield nem na subtração do `panel_user`
  (`.ai/rules/filament.md`, seção "Resource, Page ou Widget de administração no painel `app`").
  Também não entra em `telasDoKit()` — ver "Impacto em Features Existentes".

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | `/` serve a página de boas-vindas do kit | 1, 3 | — |
| RQ-02 | substitui a welcome padrão | 3, 4 | passo 4 **apaga** `welcome.blade.php` |
| RQ-03 | cards levam aos painéis | 1 | três cartões, um por painel |
| RQ-04 | os cards são do pacote de Cards | 1 | `CardsPage` + `CardItem` de `harvirsidhu/filament-cards` |
| RQ-05 | exibe informações do kit | 2 | infolist nativa no rodapé da página |
| RQ-06 | exibe informações da config | 2 | subconjunto curado de `config/kit.php` — ADR-04 |
| RQ-07 | exibe o que o `kit:install` atualizou | 2 | nome, cor, multi-organização, demo — ADR-04 |
| RQ-08 | desenho pela skill de design | 0 | artboard publicado, tokens lidos do projeto |
| RQ-09 | estrutura nativa do Filament | 1, 2 | Page + Schema/infolist + componentes do pacote |
| RQ-10 | herda o CSS do starter-kit | 1, 3 | painel bootado pelo middleware `panel:app` — ADR-01 |
| RQ-11 | herda o dark mode do starter-kit | 1, 3 | idem — o `layout.base` do painel traz o script — ADR-01 |
| RQ-12 | exibe as informações customizadas | 2 | seção "Este projeto" — ADR-04 |

## Objetivo

Trocar a welcome padrão do Laravel, na rota `/`, por uma porta de entrada do próprio kit: três
cartões que levam aos painéis `/app`, `/admin` e `/infra`, e uma infolist com o que o projeto tem
de configurado. A página tem de parecer parte do kit — mesma paleta, mesma tipografia, mesmo
tema claro/escuro — sem ser um segundo tema mantido à mão.

A rota `/` é **anônima**, e essa é a restrição que governa o conteúdo: nada de credencial, de
e-mail de administrador, de topologia de banco ou de endereço de repositório.

## Contexto

Hoje `routes/web.php` tem sete linhas e devolve `view('welcome')` — a welcome de fábrica do
Laravel 13, 224 linhas, com CSS Tailwind embutido de fallback e blocos `@if (Route::has('login'))`
que **nunca renderizam neste projeto**: não existe rota nomeada `login` (medido com
`php artisan route:list`; os painéis registram `filament.app.auth.login`,
`filament.admin.auth.login` e `filament.infra.auth.login`). Ou seja: quem faz `create-project` do
kit hoje cai numa tela genérica de Laravel que não menciona nenhum dos três painéis e não tem
nenhum link que funcione.

## Análise dos Arquivos Existentes

### `routes/web.php` (7 linhas)

Única rota: `Route::get('/', fn () => view('welcome'))`. Será substituída pela rota da página.

### `resources/views/welcome.blade.php` (224 linhas)

Welcome de fábrica, intocada. **Será apagada** — RQ-02 diz "no lugar d welcome padrão", e deixar
o arquivo órfão no repositório é dívida silenciosa.

### `app/Filament/Admin/Pages/HubDeAdministracao.php`, `App/Pages/HubDoNegocio.php`, `Infra/Pages/HubDeInfraestrutura.php`

As três páginas hub, todas `extends CardsPage` com `use DescobreCardsDoPainel`. Herdamos delas
três coisas e **rejeitamos uma**:

- herdado: `getPageClasses(): ['kit-cards-page']` — sem essa classe a grade sai sem estilo
  nenhum, com HTML correto e sem erro (`.ai/rules/css-filament.md`);
- herdado: `$columns = 3` e alinhamento `Center` — as únicas combinações que
  `resources/css/filament/cards.css` cobre (o cabeçalho do arquivo lista, por escrito, o que ele
  **não** cobre: `compact()`, `collapsible()`, `columnSpan(['lg' => n])`, alinhamento diferente de
  Center e `$columns >= 5`);
- herdado: cartões sem `->badge()` obrigatório — `null` é seguro na blade do pacote;
- **rejeitado**: o trait `DescobreCardsDoPainel`. Ele filtra por `canAccess()` de cada destino, e
  o visitante da rota `/` é anônimo — o resultado seria **zero cartão**. Ver ADR-03.

### `app/Providers/Filament/AppPanelProvider.php`

Painel `app`, `->default()`, `brandName(config('app.name'))`, cor por `CorPrimaria::paleta()`,
tenancy opcional. É o painel que a página vai bootar (ADR-01): é o `default()`, e é o único cujo
`brandName` não tem sufixo — o `<title>` sai "Bem-vindo ao Starter Kit Easy - Starter Kit".

### `resources/css/filament/cards.css` e `kit.css`

Registradas globalmente por `KitServiceProvider::configureCorrecoesDeCss()` via
`FilamentAsset::register()`. Medido nesta árvore: as duas aparecem no `@filamentStyles`
(`css/kit/kit-correcoes.css`, `css/kit/kit-cards.css`) sem painel corrente nenhum. Não precisam de
nada novo.

## Autorização

- **Policies**: nenhuma criada ou alterada.
- **Gates**: nenhum.
- **Middleware**: a rota recebe `panel:app` (alias de `Filament\Http\Middleware\SetUpPanel`,
  registrado em `vendor/filament/filament/src/FilamentServiceProvider.php:87`). **Não** recebe
  `Authenticate` — a página é pública, como a welcome que ela substitui.
- **Guards**: nenhum.
- **Permissão do Shield**: nenhuma. A página não é registrada em painel algum, então
  `FilamentShield::getEntitiesPermissions()` não a enxerga e a matriz de papéis não muda.

## Rotas

| Método | URI | Name | Middleware |
|--------|-----|------|------------|
| GET | `/` | `boas-vindas` | `web` (grupo), `panel:app` |

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `App\Filament\Pages\BoasVindas` | Filament Page (fora de painel) | `/` | ler as informações e clicar num dos três cartões | **Não** para o conteúdo; **sim** para o tema claro/escuro (Alpine + `localStorage`) |

**Gate de CT-B**: a tabela é o gatilho. O que só o navegador prova aqui é **um** eixo: o tema
escuro (`assertSee` devolve o mesmo HTML nos dois temas — `.ai/rules/testes-browser.md`) e a
ausência de erro de JavaScript numa página que boota um painel inteiro fora do contexto de painel.
Todo o resto — a página responder 200, os três cartões existirem com o `href` certo, cada entrada
da infolist trazer o valor da config, e nenhum segredo estar no HTML — é asserção sobre HTML e
pertence ao `04`.

**Gate de tela de escrita**: não se aplica — a página não tem rota `create`/`edit` nem grava nada.

## Variáveis de Ambiente

Nenhuma nova. A página **lê** o que já existe (`config/kit.php`, `config('app.name')`) e não
introduz chave alguma.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **`tests/Kit/InventarioDeTelasTest.php`**: reconcilia `getPages()` + `getResources()` de cada
  painel contra `telasDoKit()`. A página **não** é registrada em painel, então nenhum dos dois
  sentidos da comparação a vê. `telasDoKit()` fica intocado — e isso é decisão, não esquecimento:
  a lista é keyed por painel e a rota `/` não é tela de painel.
- **`tests/Browser/TelasDoKitTest.php` CT-B04** ("telas públicas"): usa `assertNoSmoke()`, que
  reprova em qualquer `console.log`. A `/` boota o painel `app` inteiro, com todos os plugins e o
  render hook do `assistente-chat-widget`. Por isso a `/` **não** entra no CT-B04; ela ganha
  arquivo próprio com `assertNoJavaScriptErrors()`. Ver ADR-05.
- **`tests/Kit/PaineisTest.php`**: cobre `GET /app|/admin|/infra`. Nada muda ali.
- **Nenhuma migration, nenhum seeder, nenhuma permissão.** O risco de regressão nas 52 telas dos
  painéis é o do boot do painel `app` numa rota nova, e ele é medido pelo `--parallel` do
  `composer test:kit` (Verificação Final).

## Rollback

- Não há migration. Reverter é `git revert` do commit: a rota volta a `view('welcome')` e o
  arquivo apagado volta com ela.
- **Sem feature flag.** Uma flag aqui significaria manter as duas telas vivas, o que contradiz
  RQ-02 ("no lugar d welcome padrão").

## Dependências

Nenhuma nova. `harvirsidhu/filament-cards` **1.0.9** e `filament/filament` **5.7.6** já estão
instalados (`composer show --direct`), e o `FilamentCardsPlugin` já está registrado nos três
painéis.

## Riscos

| Risco | Mitigação |
|---|---|
| Bootar o painel `app` numa rota anônima aciona 30+ plugins fora do contexto habitual | É exatamente o que `/app/login` já faz hoje: rota anônima, `panel:app`, `layout.simple`. O caminho é o mesmo, não é novo. Coberto por CT-01 (200) e CT-B01 (sem erro de JS) |
| `layout.simple` renderiza topbar com menu do usuário | Não renderiza: os dois blocos são guardados por `filament()->auth()->check()` (`vendor/filament/filament/resources/views/components/layout/simple.blade.php:22` e `:30`). Coberto por CT-06 |
| Utilitária do pacote de cartões ausente no `cards.css` → grade sem estilo, HTML correto, tudo verde | O plano usa **só** as combinações que o arquivo cobre, e a lista do que ele não cobre está escrita no cabeçalho dele. CT-05 assere a classe de escopo `kit-cards-page`; a legibilidade em si é CT-B02 |
| Vazar segredo numa página pública | ADR-04 fixa a lista do que entra e do que não entra, e CT-09 assere a **ausência** de cada item da lista negra no HTML |
| Componente Livewire de página não registrado em painel não resolver no round-trip AJAX | A página é estática: sem action, sem busca, sem `->live()`. Nenhum request Livewire acontece. Se um dia acontecer, `SetUpPanel` está em `Livewire::addPersistentMiddleware()` (`FilamentServiceProvider.php:105-116`) |

## Channel de Log da Feature

### Verificação de Channel Existente

`config/logging.php` tem os channels do kit (`autenticacao`, entre outros), todos alimentados por
`Log::channel()` nomeado. Nenhum se refere a boas-vindas.

### Decisão: **a feature não cria channel, e não emite log**

Isto é desvio deliberado do padrão da skill, com motivo:

1. **Não há lógica a rastrear.** A página não grava, não chama serviço externo, não autoriza, não
   decide fluxo. Ela lê `config()` e renderiza. Não existe o "ponto de decisão" que o padrão de log
   da skill manda cobrir.
2. **A rota é pública e anônima.** Um `Log::info()` por request em `/` é uma escrita em disco por
   visita não autenticada — ruído em operação normal e amplificador em caso de flood. É custo sem
   contrapartida de diagnóstico.
3. **A única falha possível já é rastreada.** Se `Filament::getPanel('app')` não existir, o
   `InvalidArgumentException` do Filament sobe e cai na trilha de exceções que o kit já mantém
   (`/infra/exceptions`, `bezhansalleh/filament-exceptions`). Um `try/catch` para logar o mesmo
   evento em outro lugar duplicaria a trilha.

Consequência aceita: não há CT de log neste `04`. Registrado aqui para que a ausência seja
decisão lida, e não omissão.

## Estrutura de Implementação

### 0. Desenho da tela (RQ-08) — **concluído antes do plano**

> Skill: `design`

- **Artefato**: `wikis/specs/feat/pagina-boas-vindas/pagina-boas-vindas/design/Main.dc.html` +
  `canvas.json`, publicado em https://claude.ai/code/artifact/cd1677da-a5f4-44f0-9995-70baf64e0552
- Uma direção, com o chip `dark` alternando claro/escuro no mesmo artboard.
- Os tokens do artboard **não** foram inventados: os `oklch` saem do que
  `FilamentAsset::renderStyles()` emite nesta árvore, e as classes de cartão saem de
  `resources/css/filament/cards.css`. Fonte Inter porque é a que o Filament registra
  (`Font::make('inter', dist/fonts/inter)` em `FilamentServiceProvider.php:100`).
- O que o desenho fixou e o código deve seguir: cabeçalho centralizado (marca, `<h1>`, subtítulo,
  badge de versão) → grade de 3 cartões com borda esquerda colorida → duas seções de infolist
  ("Este projeto" com 6 entradas em 3 colunas, "Configuração do kit" com 6 entradas em 2 colunas)
  → rodapé de uma linha.

### 1. A página com os cartões dos painéis (RQ-01, RQ-03, RQ-04, RQ-09, RQ-10, RQ-11)

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Filament/Pages/BoasVindas.php` (namespace `App\Filament\Pages`)

  A pasta é deliberada: `app/Filament/Admin|App|Infra/Pages` é **varrida** por
  `discoverPages()` do painel correspondente, e `app/Filament/Pages` **não é varrida por
  nenhum** — é onde já vive `Pages/Auth/TelaBloqueio`, referenciada por FQCN e não descoberta.
  Colocar a página numa das três pastas de painel a registraria no painel, criando rota
  `/{painel}/boas-vindas`, item de navegação e permissão do Shield — três efeitos indesejados.

- **Assinatura**:

  ```php
  namespace App\Filament\Pages;

  use App\Filament\Pages\Concerns\...;            // nenhum
  use Filament\Facades\Filament;
  use Filament\Infolists\Components\TextEntry;
  use Filament\Schemas\Components\Grid;
  use Filament\Schemas\Components\Section;
  use Filament\Schemas\Schema;
  use Filament\Support\Enums\Width;
  use Filament\Support\Icons\Heroicon;
  use Harvirsidhu\FilamentCards\CardItem;
  use Harvirsidhu\FilamentCards\Filament\Pages\CardsPage;
  use Illuminate\Contracts\View\View;

  class BoasVindas extends CardsPage
  {
      protected static string $layout = 'filament-panels::components.layout.simple';
      protected static ?string $title = 'Bem-vindo ao Starter Kit Easy';
      protected static int|string|array $columns = 3;
      protected static bool $searchable = false;
      protected Width|string|null $maxContentWidth = Width::SevenExtraLarge;

      public function getPageClasses(): array;
      public function getSubheading(): ?string;
      public function getFooter(): ?View;
      protected static function getCards(): array;
      public function informacoesDoKit(Schema $schema): Schema;   // passo 2
  }
  ```

  Por que `$layout = 'filament-panels::components.layout.simple'` e não o default
  `layout.index`: o `index` renderiza a barra lateral e a topbar do painel — numa página pública
  isso é um menu vazio (todo `canAccess()` é falso para anônimo) e um menu de usuário sem
  usuário. O `simple` é o layout das telas de autenticação: conteúdo centralizado, sem sidebar,
  e a topbar dele só existe sob `filament()->auth()->check()`
  (`simple.blade.php:22` e `:30`). É `protected static string $layout` porque é assim que
  `Filament\Pages\Page:41` o declara (`Page::getLayout()` faz
  `static::$layout ?? 'filament-panels::components.layout.index'`, linha 85).

  `Width::SevenExtraLarge` porque o default do `simple` é `Width::Large`
  (`simple.blade.php:7`) — largura de caixa de login, estreita demais para três cartões lado a
  lado.

- **`getPageClasses()`**: `return ['kit-cards-page'];` — a classe que dá escopo a
  `resources/css/filament/cards.css`. Sem ela a grade sai sem estilo, com HTML byte a byte
  correto e sem erro nenhum (`.ai/rules/css-filament.md`).

- **`getSubheading()`**: uma frase, escrita no código:
  `'Três painéis prontos para usar. O acesso a cada um continua pedindo login.'`

- **`getCards()`**: três `CardItem`, escritos à mão (e não descobertos — ADR-03), na ordem
  negócio → administração → infraestrutura:

  | Painel | Rótulo | Ícone | Badge | Cor da borda | Descrição |
  |---|---|---|---|---|---|
  | `app` | Painel do negócio | `Heroicon::OutlinedBuildingOffice2` | `/app` | `primary` | Onde o seu produto vive. Multi-organização, convites e o cadastro do dia a dia. |
  | `admin` | Administração | `Heroicon::OutlinedUsers` | `/admin` | `info` | Usuários, papéis e permissões, convites, organizações e agentes de IA. |
  | `infra` | Infraestrutura | `Heroicon::OutlinedServerStack` | `/infra` | `gray` | Filas, logs, exceções, backups, saúde da aplicação e o Pulse. |

  A URL de cada cartão é
  `Filament::getPanel($id)->getUrl() ?? url(Filament::getPanel($id)->getPath())`.

  **Nunca `url('/app')` literal.** O `getUrl()` do painel resolve domínio próprio e prefixo de
  tenant; e ele é seguro para anônimo com tenancy ligada: em
  `vendor/filament/filament/src/Panel/Concerns/HasRoutes.php:170-197`, sem usuário
  autenticado o `$tenant` fica nulo, os dois `if` de tenant não entram, o `if` da rota `home`
  exige `(! $hasTenancy) || $tenant` e também não entra — sobra o `return url($this->getPath())`
  da linha 196. O `??` existe só porque a assinatura devolve `?string`; nesses ramos ela não
  devolve nulo.

  `CardItem::make()` aceita URL crua: `make(string $pageClassOrUrl)` cai no ramo
  `new static(url: $pageClassOrUrl)` quando o argumento não é classe de Page/Resource
  (`vendor/harvirsidhu/filament-cards/src/CardItem.php:55-62`). Vamos ainda assim usar
  `->url(...)` explícito, que é mais legível que depender do despacho do `make()`.

  As três cores de borda (`primary`, `info`, `gray`) estão no `cards.css`
  (`.kit-cards-page .border-primary-500`, `.border-info-500`, `.border-gray-500`), e as três
  cores de badge saem do `<x-filament::badge>` nativo, que é `fi-*` e não depende do `cards.css`.

  **Os cartões não filtram por autorização** — ADR-03.

- **Logs**: nenhum. Ver "Channel de Log da Feature".

### 2. A infolist com as informações do kit (RQ-05, RQ-06, RQ-07, RQ-09, RQ-12)

> Skills: `laravel-best-practices`, `ponytail`

- **Path do método**: `App\Filament\Pages\BoasVindas::informacoesDoKit(Schema $schema): Schema`
- **Path da view**: `resources/views/filament/pages/boas-vindas.blade.php`

  ```blade
  {{-- O rodapé da página: a infolist com as informações do kit. --}}
  {{ $this->informacoesDoKit }}
  ```

  Só isso. A view existe porque `Page::getFooter(): ?View`
  (`vendor/filament/filament/src/Pages/Page.php:275`) devolve uma View, e o
  `<x-filament-panels::page>` a renderiza em
  `vendor/filament/filament/resources/views/components/page/index.blade.php:129-131`. A blade do
  pacote de cartões não tem slot para conteúdo extra — o rodapé é o encaixe nativo, e evita
  copiar as 397 linhas dela.

  **Cuidado de Blade** (`.ai/rules/views.md`): não escrever nome de diretiva dentro de
  comentário `{{-- --}}`. O comentário acima diz "o rodapé da página", não a diretiva.

  `{{ $this->informacoesDoKit }}` resolve porque `InteractsWithSchemas::cacheSchema()` reflete o
  método pelo nome e aceita método com parâmetro `Schema`
  (`vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php:231-260`, `getSchema()` em
  `:322`).

- **Estrutura do schema** — duas `Section`, cada uma com um `Grid`, entradas com
  `TextEntry::make(...)->state(...)`. Sem registro Eloquent: conforme a doc do Filament 5
  (infolists/overview, consultada em 2026-08-24), `state()` é o caminho para valor estático.

  **Seção 1 — "Este projeto"** (RQ-07, RQ-12), 3 colunas, com a descrição
  *"O que o `kit:install` personalizou. Sem o comando, você vê os padrões do kit."*:

  | Entrada | Valor | Origem |
  |---|---|---|
  | Nome da aplicação | `config('app.name')` | `.env` `APP_NAME`, escrito pelo `kit:install` |
  | Cor primária | `config('kit.cor_primaria')` ou `'Âmbar (padrão do Filament)'` | `.env` `KIT_COR_PRIMARIA` |
  | Multi-organização | `Ligada` / `Desligada` (badge) | `config('kit.tenancy.enabled')` |
  | Como a organização é chamada | `config('kit.tenancy.label')` + plural | `config('kit.tenancy.label_plural')` |
  | Cenário de demonstração | `Ligado` / `Desligado` (badge) | `config('kit.demo')` |
  | Hub em cartões | `Ligado` / `Desligado` (badge) | `config('kit.hub')` |

  **Seção 2 — "Configuração do kit"** (RQ-06), 2 colunas, com a descrição
  *"Lida de `config/kit.php` — troque no `.env`, não no arquivo."*:

  | Entrada | Valor | Origem |
  |---|---|---|
  | Versão do kit | `config('kit.version')` | `config/kit.php` |
  | Idiomas do painel | `implode(', ', config('kit.idiomas'))` | `config/kit.php` |
  | Validade do convite | `n dias` | `config('kit.convites.validade_em_dias')` |
  | Lembretes do convite | dias, ou `Desligados` com lista vazia | `config('kit.convites.lembretes_dias')` |
  | Convites por lote | `até n endereços` | `config('kit.convites.limite_do_lote')` |
  | Retenção de trilhas | `exceções / e-mails / import e export`, com `Sem poda` quando `<= 0` | `config('kit.retencao.*')` |

  O `Sem poda` não é enfeite: `config/kit.php` promete por escrito que zero ou negativo desliga a
  poda, e `App\Support\NumeroDoEnv::diasOuDesligado()` permite o zero de propósito
  (`.ai/rules/config.md`). Exibir "0 dias" mentiria sobre o comportamento.

- **Badge nas entradas booleanas**: `->badge()` + `->color('success'|'gray')` no `TextEntry`, que é
  nativo do Filament e usa CSS `fi-*` — não depende do `cards.css`.

- **O que NÃO entra na página**: ver ADR-04. A lista negra é `kit.admin.*` (nome, e-mail, senha),
  `config('database.*')`, `config('kit.repository')`, `config('app.env')`, `config('app.debug')`,
  `config('app.url')` e `config('mail.*')`.

- **Logs**: nenhum.

### 3. A rota (RQ-01, RQ-02, RQ-10, RQ-11)

> Skills: `laravel-best-practices`

- **Path**: `routes/web.php`

  ```php
  use App\Filament\Pages\BoasVindas;
  use Illuminate\Support\Facades\Route;

  Route::get('/', BoasVindas::class)
      ->middleware('panel:app')
      ->name('boas-vindas');
  ```

  O `panel:app` é o que faz RQ-10 e RQ-11 acontecerem sem uma linha de CSS nova: ele é alias de
  `Filament\Http\Middleware\SetUpPanel` (registrado em `FilamentServiceProvider.php:87`), que faz
  `Filament::setCurrentPanel()` + `Filament::bootCurrentPanel()`
  (`vendor/filament/filament/src/Http/Middleware/SetUpPanel.php:11-20`). Com painel corrente, o
  `layout.base` do Filament emite `filament()->getTheme()->getHtml()`, as fontes, a paleta e o
  script de tema — as quatro coisas que a página precisa herdar. Ver ADR-01 para o que foi medido
  e por que a alternativa (Blade solta com `@filamentStyles`) perde a paleta do kit.

- **Logs**: nenhum.

### 4. Apagar a welcome padrão (RQ-02)

> Skill: `ponytail`

- `git rm resources/views/welcome.blade.php`
- Confirmar antes que nada mais a referencia: hoje o único uso é a linha 6 de `routes/web.php`,
  que o passo 3 substitui.

### 5. Casos de teste de backend

> Skill: `pest-testing`

- **Path**: `tests/Kit/BoasVindasTest.php` (suíte `Kit` — a página não depende de tenancy nem de
  papel; `phpunit.xml` já fixa `KIT_HUB=false`, `KIT_DEMO=false`, `KIT_COR_PRIMARIA=''`, e é
  contra esses valores que os casos afirmam)
- Cenários especificados em `04-casos-de-teste.md`.

### 6. Casos de teste de navegador

> Skill: `pest-testing`

- **Path**: `tests/Browser/BoasVindasTest.php` (suíte `Browser`)
- Cenários especificados em `05-casos-de-teste-browser.md`.
- Aquecer as views pelo kernel no `beforeEach` (`$this->get('/')`) antes do `visit()` — o
  primeiro render de painel paga ~25 s de compilação de componente Livewire que o `view:cache`
  não adianta, e ela cairia dentro do cronômetro de 45 s do Playwright
  (`.ai/rules/testes-browser.md`).

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
>
> Onde a escada foi aplicada, e onde ela mandou parar:
>
> 1. **Reutilizar antes de criar**: a página herda `CardsPage` do pacote já instalado, o
>    `layout.simple` do Filament, o `getFooter()` nativo e o `cards.css` já registrado. Nenhum
>    CSS novo, nenhum layout novo, nenhum componente Blade novo além de uma view de uma linha.
> 2. **Feature nativa antes de código**: o `panel:app` no lugar de reimplementar `layout.base`
>    (tema, paleta, fontes, script de dark mode) — ver ADR-01, que mede o que a reimplementação
>    perderia.
> 3. **Recusado como over-engineering**: painel Filament novo só para a rota `/`; publicar/copiar
>    as 397 linhas da blade do pacote; um `config/boas-vindas.php` para o que são três cartões
>    escritos à mão; channel de log numa página que não decide nada; feature flag para uma tela
>    que substitui outra.
>
> Atalhos deliberados levam comentário `ponytail:`.
>
> **Caveman**: arquivos wiki (00–06), código e commits ficam em prosa normal.

## Testes

> Ver `04-casos-de-teste.md` (backend) e `05-casos-de-teste-browser.md` (navegador).

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse --no-progress` (level 7; `app`, `routes` e `config` estão nos paths)
- [ ] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy`
- [ ] `composer test:browser` (embute `npm run build` + `view:cache`)
- [ ] `php artisan route:list --path=/` confirma a rota e o middleware
- [ ] Roteiro "Desenhado × Implementado" do `05` preenchido

## Commits

- `:sparkles: feat(boas-vindas): rota / mostra os painéis e a config do kit`
- `:white_check_mark: test(boas-vindas): cobre a rota anonima, a config exibida e o tema escuro`
- `:memo: docs(wiki): wiki da feature pagina-boas-vindas`

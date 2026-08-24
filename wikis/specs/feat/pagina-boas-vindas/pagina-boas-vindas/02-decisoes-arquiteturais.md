# Decisões Arquiteturais — página de boas-vindas na rota `/`

## ADR-01: a rota `/` boota o painel `app` pelo middleware `panel:app`

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

RQ-10 e RQ-11 exigem que a página **herde** o CSS e o dark mode "já implementados no
starter-kit". No Filament 5 essas duas coisas não são globais — elas são emitidas pelo layout do
painel, e o layout lê do painel corrente:

- `filament()->getTheme()->getHtml()` — o `<link>` da folha do Filament
  (`vendor/filament/filament/resources/views/components/layout/base.blade.php:67`);
- as fontes e as variáveis `--font-family`, `--sidebar-width`, `--default-theme-mode`
  (`base.blade.php:68-84`);
- o `<script>` que aplica a classe `dark` a partir de `localStorage.theme` e de
  `prefers-color-scheme`, guardado por `filament()->hasDarkMode()`
  (`base.blade.php:96-124`, e o segundo `loadDarkMode()` em `:159-163`).

Sem painel corrente, `filament()` não tem de onde ler.

**Medido nesta árvore**, não suposto:

| Pergunta | Comando | Resultado |
|---|---|---|
| `@filamentStyles` traz a folha do Filament? | `php artisan tinker --execute 'echo FilamentAsset::renderStyles();'` | **Não.** Nenhum `theme.css`/`app.css` na saída. Ela sai por `filament()->getTheme()->getHtml()`, que devolve `css/filament/filament/app.css` |
| `@filamentStyles` traz o CSS do kit? | idem | **Sim** — `css/kit/kit-correcoes.css` e `css/kit/kit-cards.css`. `KitServiceProvider::configureCorrecoesDeCss()` registra global |
| `@filamentStyles` traz a cor do projeto? | `KIT_COR_PRIMARIA=Violet php artisan tinker --execute 'echo FilamentAsset::renderStyles();'` | **Não.** `--primary-500:oklch(0.769 0.188 70.08)` — âmbar, o default do Filament, com `Violet` na env. A paleta do kit entra por `Panel::boot()` → `FilamentColor::register($this->getColors())` (`vendor/filament/filament/src/Panel.php:95`), e `->colors()` do painel é que chama `CorPrimaria::paleta()` |

### Decisão

A rota é

```php
Route::get('/', BoasVindas::class)->middleware('panel:app')->name('boas-vindas');
```

`panel` é alias de `Filament\Http\Middleware\SetUpPanel`, registrado em
`vendor/filament/filament/src/FilamentServiceProvider.php:87`. O middleware recebe o id do painel
como parâmetro e faz `Filament::setCurrentPanel()` + `Filament::bootCurrentPanel()`
(`vendor/filament/filament/src/Http/Middleware/SetUpPanel.php:11-20`).

Painel escolhido: **`app`**. Ele é o `->default()` (`AppPanelProvider.php:63`) e é o único cujo
`brandName` não tem sufixo — `config('app.name')`, contra `'… • Admin'` e `'… • Infra'`. O
`<title>` sai "Bem-vindo ao Starter Kit Easy - Starter Kit".

### Alternativas Consideradas

1. **Blade solta com `@filamentStyles` + script de tema escrito à mão** (o padrão que
   `resources/views/errors/sentinel-layout.blade.php` usa, e que declara no cabeçalho não depender
   nem do Filament). Descartada: perde a folha do Filament e a paleta do projeto, pelas duas
   medições acima. Recuperá-las significa reimplementar quatro trechos do `layout.base`
   (`getTheme()->getHtml()`, as fontes, `FilamentColor::register(CorPrimaria::paleta())`, o script
   de tema) — quatro cópias que envelhecem em silêncio no próximo upgrade do Filament. E os
   componentes `<x-filament::icon>` / `<x-filament::badge>` que a blade do pacote de cartões emite
   continuariam sem a folha que os estiliza.
2. **Painel Filament novo, só para a rota `/`.** Descartada por raio de alcance: painel novo
   entra na varredura do `filament-shield` (que percorre todos os painéis no boot), ganha
   permissões próprias, entra em `App\Support\Paineis` e na subtração de papéis do `PapeisSeeder`
   — e exige registrar `Lockscreen` e `FilamentExceptionsPlugin` nele, porque painel sem esses
   dois estoura `LogicException` em todo request e em todo comando artisan (comentários em
   `AdminPanelProvider.php:134-143` e `:201-215`). Custo alto, para nenhum ganho: a página não
   precisa de rota de painel, de navegação nem de autorização.
3. **Copiar/publicar a blade do pacote de cartões** para tirar o `<x-filament-panels::page>` do
   caminho. Descartada: 397 linhas de vendor no repositório, com o custo de manutenção descrito em
   `.ai/rules/css-filament.md` (a última cópia de blade de vendor no kit foi um botão, não uma
   página).

### Consequências

- **Positivas**: zero CSS novo, zero layout novo, zero script de tema novo. Cor primária do
  projeto, tema claro/escuro, fontes e folha do Filament chegam pelo mesmo caminho das telas de
  login — que são, elas também, rotas anônimas com `panel:` e `layout.simple`.
- **Negativas**: a rota `/` boota os ~30 plugins do painel `app`, o que é mais trabalho por
  request do que uma Blade estática. O precedente que sustenta isso é `/app/login`: mesma pilha,
  mesmo layout, mesmo anonimato, já em produção.
- **Riscos**: `bootCurrentPanel()` **falha no console** — `php artisan tinker --execute
  'Filament::setCurrentPanel("app"); Filament::bootCurrentPanel();'` morre com
  `Error: Call to a member function parameter() on null` (algum plugin do painel espera uma rota
  corrente). Não afeta a página, que só boota dentro de um request HTTP, mas afeta quem quiser
  medir a paleta por tinker: **meça por request**, não por comando. Registrado porque a próxima
  pessoa vai tentar o tinker primeiro.

### Referências

- `vendor/filament/filament/src/Http/Middleware/SetUpPanel.php:11-20`
- `vendor/filament/filament/src/FilamentServiceProvider.php:87` (alias `panel`), `:100-103` (fonte
  Inter e `Theme::make('app')`), `:105-116` (`SetUpPanel` em `addPersistentMiddleware`)
- `vendor/filament/filament/resources/views/components/layout/base.blade.php:67-124`
- `vendor/filament/filament/src/Panel.php:95`
- `app/Support/CorPrimaria.php`

---

## ADR-02: a página é uma `CardsPage` fora de painel, com `layout.simple` e a infolist no rodapé

**Status**: Aceita
**Data**: 2026-08-24
**Refina**: ADR-01

### Contexto

O requisito pede duas coisas de estrutura ao mesmo tempo: os links dos painéis feitos **com o
pacote de Cards** (RQ-04) e as informações do kit em **estrutura nativa do Filament, podendo ser
uma infolist** (RQ-05, RQ-09). O pacote entrega uma página inteira, não um componente: a view
`harvirsidhu-filament-cards::pages.cards-page` é envelopada em `<x-filament-panels::page>` da
linha 109 à 397, e **não tem slot** para conteúdo extra.

Três fatos limitam as saídas:

1. `<x-filament-panels::page>` chama `$this->getCachedSubNavigation()`, `$this->getWidgetData()` e
   `filament()->hasBreadcrumbs()` (`components/page/index.blade.php:8-10` e `:52`) — exige uma
   `Filament\Pages\Page` **e** painel corrente. O ADR-01 já garante o painel.
2. Para uma Page sem cluster, `getBreadcrumbs()` e `getSubNavigation()` devolvem `[]`
   (`vendor/filament/filament/src/Pages/Page.php:185-192` e
   `src/Pages/Concerns/HasSubNavigation.php:26-33`), então nada no envelope exige que a página
   esteja **registrada** no painel.
3. `Page::getFooter(): ?View` (`Pages/Page.php:275`) é renderizado pelo envelope em
   `components/page/index.blade.php:129-131`.

### Decisão

`App\Filament\Pages\BoasVindas extends Harvirsidhu\FilamentCards\Filament\Pages\CardsPage`, com:

- `protected static string $layout = 'filament-panels::components.layout.simple';` — o layout das
  telas de autenticação. `Page::getLayout()` faz `static::$layout ?? '…layout.index'`
  (`Pages/Page.php:85`), então sobrescrever a propriedade basta. O `index` traria barra lateral
  (vazia, porque todo `canAccess()` é falso para anônimo) e menu de usuário (sem usuário); no
  `simple`, os dois blocos de topbar são guardados por `filament()->auth()->check()`
  (`components/layout/simple.blade.php:22` e `:30`).
- `protected Width|string|null $maxContentWidth = Width::SevenExtraLarge;` — o default do `simple`
  é `Width::Large` (`simple.blade.php:7`), largura de caixa de login.
- A infolist em `getFooter()`, apontando para uma view de **uma linha**
  (`{{ $this->informacoesDoKit }}`).
- A pasta é `app/Filament/Pages/`, que **nenhum** painel varre — os três `discoverPages()` apontam
  para `Filament/Admin|App|Infra/Pages`. É onde já vive `Pages/Auth/TelaBloqueio`.

### Alternativas Consideradas

1. **Componente Livewire próprio com uma infolist, e a grade de cartões escrita à mão.** Atenderia
   RQ-05 e RQ-09, mas trairia RQ-04: os cartões não seriam do pacote, e o `cards.css` do kit
   (escrito para as classes que a blade do pacote emite) deixaria de ter dono.
2. **Duas rotas / duas telas.** Contraria RQ-02.
3. **`getHeader()` em vez de `getFooter()`.** Colocaria a config **antes** dos cartões. Os cartões
   são a ação; a config é referência. Cartões primeiro.
4. **Registrar a página no painel `app` com `shouldRegisterNavigation() = false`.** Ganharia a rota
   `/app/boas-vindas` que ninguém quer, uma permissão do Shield e uma linha nova na matriz de
   papéis — pelo mesmo motivo do ADR-01, alternativa 2.

### Consequências

- **Positivas**: nenhuma linha da blade do pacote é copiada; o kit continua com um único lugar
  onde a grade de cartões é estilizada; a infolist é a nativa, com `Section`, `Grid` e
  `TextEntry`.
- **Negativas**: a página depende de dois detalhes de layout do Filament (`$layout` como
  propriedade estática, `getFooter()` renderizado dentro de `<x-filament-panels::page>`). Um
  upgrade major do Filament pode mexer nos dois. Mitigado pelos CT-05 e CT-06, que asserem a
  classe de escopo e o conteúdo do rodapé — se o encaixe mudar, eles ficam vermelhos.
- **Negativas**: a página usa só o subconjunto de opções do pacote que o `cards.css` cobre
  (`$columns = 3`, alinhamento `Center`, não-compacto, não-recolhível, sem `columnSpan` por
  breakpoint). O cabeçalho do `cards.css` lista essas exclusões por escrito; sair delas produz
  HTML correto **sem estilo nenhum**, com todo teste verde.

### Referências

- `vendor/harvirsidhu/filament-cards/resources/views/pages/cards-page.blade.php:109` e `:397`
- `vendor/harvirsidhu/filament-cards/src/Filament/Pages/CardsPage.php:18-20`
- `vendor/filament/filament/src/Pages/Page.php:41`, `:85`, `:185`, `:275`
- `vendor/filament/filament/resources/views/components/page/index.blade.php:8-10`, `:129-131`
- `vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php:231-260`, `:322`
- `resources/css/filament/cards.css` (cabeçalho, bloco "Combinações que o kit NÃO usa")

---

## ADR-03: os cartões da rota `/` NÃO filtram por autorização

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

O kit tem uma regra forte e bem documentada em `App\Filament\Concerns\DescobreCardsDoPainel`:
cartão de hub **precisa** passar por `canAccess()`, porque `CardItem` não verifica autorização e
uma lista escrita à mão "vaza a existência de telas de administração e oferece um caminho que só
falha depois do clique".

Aplicar essa regra aqui daria **zero cartão**: o visitante da rota `/` é anônimo por definição
(00-requisito, premissa de autenticação), e `canAccess()` de todo destino de painel é falso sem
usuário.

### Decisão

Os três cartões são escritos à mão em `getCards()` e aparecem **sempre**, para qualquer visitante,
autenticado ou não. O trait `DescobreCardsDoPainel` **não** é usado.

O destino de cada cartão é a **raiz do painel**, não uma tela interna. Para o anônimo isso cai no
login do painel; para o autenticado, no painel.

### Alternativas Consideradas

1. **Filtrar por `canAccess()`.** Página vazia para o público que ela existe para receber.
2. **Mostrar só os painéis que o usuário autenticado pode acessar, e todos para o anônimo.** Duas
   regras para a mesma tela, e a versão anônima — que é o caso de uso real — continuaria sem
   filtro. Complexidade sem ganho de segurança.
3. **Um único botão "Entrar" apontando para `/app/login`.** Contraria RQ-03 ("cards para acessar
   os paines", plural) e esconde do desenvolvedor recém-instalado que existem três painéis, que é
   metade do valor da página.

### Consequências

- **Positivas**: a página cumpre a função de porta de entrada. Nenhuma autorização é
  enfraquecida: cada painel continua exigindo login e cada tela continua exigindo sua permissão.
- **Negativas / o que se aceita vazar**: a página confirma a um anônimo que existem os caminhos
  `/admin` e `/infra`. Isso **já** é público — os três `->login()` registram telas de login
  públicas em `/app/login`, `/admin/login` e `/infra/login`, os três caminhos estão no `README.md`
  do repositório público, e o CT-B04 de `tests/Browser/TelasDoKitTest.php` visita as três sem
  autenticação. A página não revela nenhum caminho novo.
- **Riscos**: alguém replicar este padrão num hub **dentro** de painel, onde a regra do
  `DescobreCardsDoPainel` vale. Mitigação: o PHPDoc da `getCards()` da página diz por escrito que
  a ausência de filtro é consequência do anonimato da rota `/`, e aponta para este ADR.

### Referências

- `app/Filament/Concerns/DescobreCardsDoPainel.php` (cabeçalho da classe)
- `vendor/harvirsidhu/filament-cards/src/Concerns/CanBeHidden.php`
- `tests/Browser/TelasDoKitTest.php` (CT-B04)

---

## ADR-04: a lista do que a página pública exibe — e a lista negra

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

RQ-06 diz "podemos exibir as informações da config" e RQ-07 "os dados que foram atualizados (caso
tenham sido rodado o kit:install)". `config/kit.php` tem 291 linhas, e o `kit:install` escreve no
`.env` — `app/Support/CustomizadorDaInstalacao.php` toca `APP_NAME`, `DB_*`, `KIT_ADMIN_EMAIL`,
`KIT_ADMIN_PASSWORD`, `KIT_COR_PRIMARIA` e `KIT_TENANCY*`.

Metade dessas chaves é segredo, e a rota `/` é anônima.

### Decisão

**Entra** (subconjunto curado):

| Grupo | Chaves |
|---|---|
| Este projeto | `config('app.name')`, `config('kit.cor_primaria')`, `config('kit.tenancy.enabled')`, `config('kit.tenancy.label')`, `config('kit.tenancy.label_plural')`, `config('kit.demo')`, `config('kit.hub')` |
| Configuração do kit | `config('kit.version')`, `config('kit.idiomas')`, `config('kit.convites.validade_em_dias')`, `config('kit.convites.lembretes_dias')`, `config('kit.convites.limite_do_lote')`, `config('kit.retencao.*')` |

**Não entra** (lista negra, com o motivo de cada linha):

| Chave | Por que não |
|---|---|
| `config('kit.admin.password')` | senha. Encerra a discussão |
| `config('kit.admin.email')` | enumeração de conta: dá ao atacante o alvo exato da tela de login, e é o alvo com mais poder da instalação |
| `config('kit.admin.name')` | mesmo eixo, menos grave; sai junto porque não tem valor para o visitante |
| `config('database.*')` (host, porta, banco, usuário) | topologia de infraestrutura |
| `config('kit.repository')` | pode ser fork privado. Um endereço `git.empresa.local/...` numa página pública é vazamento de rede interna, e a chave é `env('KIT_REPOSITORY', …)` — o default público não é garantia |
| `config('app.env')`, `config('app.debug')` | diz ao visitante se ele está diante de um ambiente de desenvolvimento. O kit já mostra o ambiente **dentro** dos painéis (`pxlrbt/filament-environment-indicator`), onde há autenticação |
| `config('app.url')`, `config('mail.*')` | sem valor para o visitante, com custo de superfície |

**"Rodou o `kit:install`?" não é exibido como fato**, porque não existe esse fato: o comando não
grava marcador nenhum. A seção "Este projeto" mostra os **valores**, e a descrição dela diz "o que
o `kit:install` personalizou. Sem o comando, você vê os padrões do kit."

### Alternativas Consideradas

1. **Dump completo da config.** Descartado: arrasta a senha do admin para uma página pública.
2. **Exibir tudo, mas só fora de produção (`! app()->isProduction()`).** Tentador e descartado: a
   proteção passa a depender de `APP_ENV` estar certo — e um `.env` mal copiado é o modo de falha
   mais comum de um skeleton recém-instalado. Segurança que depende de uma variável de ambiente
   estar certa não é segurança; é uma aposta. Além disso o branch precisaria de dois conjuntos de
   CT e de dois desenhos de tela.
3. **Autenticar a rota `/`.** Muda a natureza da feature (00-requisito, premissa de autenticação)
   e deixa o `create-project` de novo sem uma porta de entrada.

### Consequências

- **Positivas**: a página é segura por construção, não por configuração. Não existe combinação de
  `.env` que faça um segredo aparecer nela.
- **Negativas**: o desenvolvedor que acabou de rodar o `kit:install` não vê na tela o e-mail do
  admin que ele criou. Ele o vê no terminal, na saída do próprio comando, que é onde essa
  informação pertence.
- **Riscos**: alguém acrescentar uma entrada nova à infolist sem consultar a lista negra.
  Mitigação: CT-09 assere a **ausência** de cada item da lista negra no HTML da rota `/` —
  incluindo a senha e o e-mail do admin com valores plantados no arranjo, para que o caso falhe se
  a entrada for acrescentada.

### Referências

- `config/kit.php:285-289` (bloco `admin`)
- `app/Support/CustomizadorDaInstalacao.php`
- `config/kit.php:172-191` (retenções, e o significado do zero — ver `.ai/rules/config.md`)

---

## ADR-05: a página não emite log, e a `/` fica fora do CT-B04

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

Duas convenções do projeto empurram em direções que não servem aqui:

1. A skill `feature-wiki` exige channel de log por feature e `[Classe@Método]` em cada etapa de
   execução.
2. `tests/Browser/TelasDoKitTest.php` tem um cenário "telas públicas" (CT-B04) com
   `assertNoSmoke()`, e a `/` é agora a tela pública mais óbvia do kit.

### Decisão

**Sem channel de log e sem log.** A página lê `config()` e renderiza: não grava, não chama serviço
externo, não autoriza, não ramifica fluxo. Não há ponto de decisão a rastrear, e um `info()` por
request numa rota pública anônima é escrita em disco por visita — ruído em operação normal e
amplificador sob flood. A única falha possível (painel inexistente) já sobe como exceção e cai na
trilha que o kit mantém em `/infra/exceptions`.

**A `/` não entra no CT-B04.** Ela ganha `tests/Browser/BoasVindasTest.php` com
`assertNoJavaScriptErrors()`. Motivo: o CT-B04 usa `assertNoSmoke()`, que reprova em qualquer
`console.log`, e o docblock dele justifica isso dizendo que aquelas são "telas de autoria do kit",
ao contrário das telas de painel, "cheias de plugin". A `/` é as duas coisas ao mesmo tempo: HTML
de autoria do kit **dentro** de um painel bootado com ~30 plugins e o render hook
`BODY_END` do `assistente-chat-widget` (`AppPanelProvider.php:94-97`). Colocá-la no CT-B04
transformaria aquele cenário — que hoje guarda o console das telas próprias — num cenário que
falha por dívida de terceiro.

### Alternativas Consideradas

1. **Channel `boas-vindas` com um `info()` por visita.** Recusado pelos motivos acima.
2. **Um `debug()` só, guardado por `app()->hasDebugModeEnabled()`.** Log condicionado a `APP_DEBUG`
   é log que não existe quando se precisa dele.
3. **Acrescentar `/` ao CT-B04 e trocar o `assertNoSmoke()` de lá por
   `assertNoJavaScriptErrors()`.** Recusado: enfraquece a asserção das sete telas que já estão no
   cenário, para acomodar uma oitava. O relaxamento seria invisível no diff.

### Consequências

- **Positivas**: nenhum ruído de log, nenhuma asserção existente enfraquecida.
- **Negativas**: não há trilha de acesso à página. Aceito — o servidor web já registra o acesso a
  `/`, e a página não tem estado sobre o qual uma trilha diria algo.
- **Riscos**: a próxima wiki concluir, pelo precedente, que log é opcional. Mitigação: este ADR
  nomeia as três condições que dispensaram o log (sem escrita, sem chamada externa, sem
  ramificação de fluxo). Faltando qualquer uma, o padrão da skill volta a valer.

### Referências

- `tests/Browser/TelasDoKitTest.php:48-74` (CT-B04 e o docblock que justifica o `assertNoSmoke()`)
- `app/Providers/Filament/AppPanelProvider.php:94-97` (render hook do chat)
- `.ai/rules/testes-browser.md`, seção "Seletores" e "`assertSee` não valida tema"

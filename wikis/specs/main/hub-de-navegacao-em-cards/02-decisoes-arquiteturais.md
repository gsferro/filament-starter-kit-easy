# Decisões Arquiteturais — Hub de navegação em cards

## ADR-01: O hub soma à barra lateral, não a substitui

**Status**: Aceita
**Data**: 2026-08-15

### Contexto

A descrição do pacote propõe "uma grade organizada de links em vez de uma árvore na barra lateral". Levar isso ao pé da letra significaria esconder itens da navegação lateral e forçar a passagem pelo hub.

### Decisão

Os hubs são **páginas a mais**. Nenhum item sai da barra lateral.

### Alternativas Consideradas

1. **Esconder da sidebar o que está no hub** (`shouldRegisterNavigation() = false`) — descartada: quebra a navegação por teclado, quebra o `PagesAutorizadasCategory` da busca ⌘K, e obriga dois cliques onde antes havia um. Pior ainda em quem já conhece o painel.
2. **Hub como página inicial do painel** (no lugar do Dashboard) — descartada: o Dashboard responde "como estão as coisas", o hub responde "para onde eu vou". Trocar um pelo outro perde a leitura de estado que os widgets dão logo na entrada.

### Consequências

- **Positivas**: nada regride para quem já usa o kit; o hub é ganho puro para quem está chegando.
- **Negativas**: um mesmo destino existe em dois lugares. Aceito — é a natureza de um índice.
- **Riscos**: hub desatualizado em relação à sidebar. Mitigado pela descoberta automática do ADR-04.

---

## ADR-02: O CSS dos cards é do kit, registrado por `FilamentAsset` — sem tema customizado

**Status**: Aceita
**Data**: 2026-08-15
**Decidida por**: usuário, em 2026-08-15

### Contexto

`harvirsidhu/filament-cards` **não registra CSS nenhum**. O `FilamentCardsServiceProvider` só faz:

```php
$package->name(static::$name)->hasViews(static::$viewNamespace);
```

A única blade (`resources/views/pages/cards-page.blade.php`) usa utilitárias Tailwind, e a instalação oficial manda acrescentar ao `theme.css` da aplicação:

```
@source '../../../../vendor/harvirsidhu/filament-cards/resources/views';
```

Isso pressupõe um **tema Filament customizado**. O kit não tem: nenhum dos três painéis chama `viteTheme()`, e o cabeçalho de `resources/css/filament/kit.css` registra o motivo — *"o painel Filament não carrega o Vite da aplicação"*.

### Decisão

Escrever `resources/css/filament/cards.css` com **apenas** as classes que faltarem, e registrá-lo no array já existente de `KitServiceProvider::configureCorrecoesDeCss()`, com `Css::make('kit-cards', …)`.

O conteúdo é determinado **empiricamente**: abrir a tela, ver o que não pintou, escrever a regra. As candidatas mais prováveis são as do `colorMap`/`iconColorMap` (`border-success-500`, `text-danger-500` e irmãs), porque o Filament não usa essas utilitárias nos componentes dele e portanto elas podem não estar na CSS pré-compilada.

### Alternativas Consideradas

1. **`php artisan make:filament-theme` + `viteTheme()` nos três painéis** — recusada pelo usuário. Custo real: `npm run build` viraria **pré-requisito duro** para os painéis abrirem, num kit cuja proposta é funcionar logo após `create-project`; e o tema passaria a exigir revisão a cada upgrade de Filament. Ganho: seria o caminho oficial e pegaria classes futuras do plugin sozinho.
2. **Publicar a view do plugin e reescrevê-la com componentes Filament** (`x-filament::section`) — descartada: assume manutenção de uma cópia que quebra a cada upgrade do pacote, exatamente o custo que o `kit.css` foi criado para evitar (ver o comentário sobre "publicar as views dos quatro vendors").
3. **Só documentar, sem implementar página nenhuma** — foi oferecida ao usuário e recusada: o RQ-06 pede implementação onde houver oportunidade.

### Consequências

- **Positivas**: o kit continua abrindo os três painéis sem build de front-end; o mecanismo é o mesmo que o projeto já usa e já testa.
- **Negativas**: manutenção manual. Se o plugin mudar a markup num upgrade, o CSS precisa ser revisto — e a falha é **silenciosa** (cartão sem borda, HTML correto).
- **Riscos**: a falha silenciosa é o risco central da wiki. Mitigação: CT-B com screenshot, porque `assertSee` passa com o cartão despintado.

### Referências

- `vendor/harvirsidhu/filament-cards/src/FilamentCardsServiceProvider.php`
- `app/Providers/KitServiceProvider.php:132-152`
- `resources/css/filament/kit.css` (cabeçalho)

---

## ADR-03: Três APIs do pacote não são usadas, porque as classes delas não existem

**Status**: Aceita
**Data**: 2026-08-15

### Contexto

Lendo `cards-page.blade.php`, três classes são montadas por **interpolação de string**:

```php
$classes[] = "2xl:grid-cols-{$count}";           // $columns >= 5
$spanClasses = 'lg:col-span-' . $lgSpan;         // columnSpan(['lg' => n])
"group-hover:{$iconHoverColorClass}" => …        // CardItem::color()
```

Tailwind gera CSS varrendo **texto literal** no código-fonte. Nome de classe montado em runtime nunca é encontrado — e isso vale **com ou sem** tema customizado. Não é uma limitação da decisão do ADR-02; é uma limitação do pacote.

### Decisão

O kit evita as três APIs, e a receita diz isso:

- `$columns` fica em **3 ou 4** (nunca ≥ 5, que é onde entra o `2xl:grid-cols-{n}`)
- `columnSpan(['lg' => n])` **não é usado**; se um cartão precisar de destaque, use `columnSpanFull()`, que é literal
- `CardItem::color()` é usado apenas pela **borda** (`$colorMap`, que é literal e resolvível pelo ADR-02); a cor de ícone no hover é perda cosmética aceita

### Alternativas Consideradas

1. **Safelist de classes** — inviável sem tema Tailwind próprio, e reintroduziria toda a discussão do ADR-02.
2. **Escrever as regras faltantes à mão no `cards.css`** — descartada para os três casos dinâmicos: seriam 12 variantes de `col-span`, 6 de `grid-cols` e 6 de `group-hover` para cobrir combinações que o kit não usa. É o oposto da escada do Ponytail.
3. **Abrir issue/PR no pacote** para trocar interpolação por mapa literal — caminho válido e recomendado, mas não bloqueia esta entrega.

### Consequências

- **Positivas**: nenhuma classe silenciosamente inexistente no código do kit.
- **Negativas**: três recursos do pacote ficam fora do vocabulário do kit.
- **Riscos**: alguém usa uma delas sem saber. Mitigação: está na receita, na seção "o que NÃO fazer", e é candidata a rule.

---

## ADR-04: A descoberta de cards é um concern do kit, e é ela que aplica `canAccess()`

**Status**: Aceita
**Data**: 2026-08-15

### Contexto

Duas descobertas na leitura do pacote:

1. **`CardItem` manual não verifica autorização.** `Concerns/CanBeHidden.php` avalia só `visible`/`hidden`; `CardsPage::getProcessedGroups()` filtra apenas por `isVisible()`. O `canAccess()` aparece **exclusivamente** dentro de `discoverClusterCards()` e `discoverResourceCards()`.
2. **Nenhum dos dois `discover*` serve ao kit**: um exige que a Page esteja num **Cluster**, o outro que ela seja **página de um Resource**. Os hubs do kit são Pages de painel.

Consequência prática: `CardItem::make(UserResource::class)` escrito à mão aparece para **todo mundo**, inclusive para quem levaria 403 ao clicar. Não é escalada de privilégio — a autorização do destino continua íntegra —, mas vaza a existência da tela e produz um caminho que só falha depois do clique.

### Decisão

Criar `App\Filament\Concerns\DescobreCardsDoPainel`, com `cardsDoPainel(array $excluir = [])`, que descobre Resources e Pages do **painel corrente**, filtra por `canAccess()`, herda rótulo/ícone/badge/sort da própria classe e agrupa pelo grupo de navegação. Nenhuma das três Pages instancia `CardItem` diretamente.

### Alternativas Consideradas

1. **Listas manuais de `CardItem` em cada hub, com `->visible(fn () => X::canAccess())`** — descartada por dois motivos somados: (a) o `->visible()` é fácil de esquecer, e o esquecimento não quebra nada visivelmente; (b) a lista não acompanha Resource novo, então o hub fica incompleto em silêncio — o mesmo modo de falha que a rule da subtração de permissões descreve.
2. **Criar um Cluster por painel só para poder usar `discoverClusterCards()`** — descartada: reorganizaria a navegação dos três painéis (Cluster muda URL e agrupamento) para reaproveitar 20 linhas.
3. **Estender `CardsPage` numa classe base do kit** em vez de um trait — descartada: os três hubs já precisam estender `CardsPage` do vendor; herança dupla não existe, e uma base intermediária acopla o kit à hierarquia do pacote sem ganho.

### Consequências

- **Positivas**: autorização por construção; hub que se mantém sozinho quando um Resource entra ou sai do painel.
- **Negativas**: ~40 linhas de código próprio que espelham parcialmente o `discoverClusterCards()` do vendor.
- **Riscos**: se o pacote ganhar um `discoverPanelCards()` oficial, o concern vira dívida. Mitigação: marcar com `ponytail:` apontando o caminho de substituição.

### Referências

- `vendor/harvirsidhu/filament-cards/src/Concerns/CanBeHidden.php`
- `vendor/harvirsidhu/filament-cards/src/Filament/Pages/CardsPage.php:74-133, 374-402`
- `.ai/rules/filament.md` — "Asserção de identidade vive no model, não na query da tela" (mesmo princípio: a barreira não pode morar só na tela)

---

## ADR-05: O hub do `/app` fica fora da lista de subtração do `panel_user`

**Status**: Aceita
**Data**: 2026-08-15

### Contexto

`.ai/rules/filament.md` é explícita: *"Resource, Page ou Widget de administração no painel `app` entra na lista de subtração"* (`PapeisSeeder::permissoesDeAdministracaoDoApp()`). O `HubDoNegocio` é uma **Page nova no painel `app`** — exatamente a família que a rule diz alcançar.

### Decisão

**Não** acrescentá-lo à lista. O `panel_user` herda `View:HubDoNegocio`.

### Alternativas Consideradas

1. **Acrescentar por precaução** — descartada: numa subtração, o erro é espelhado. Tirar a permissão de quem deveria tê-la deixaria o usuário comum do negócio sem a página inicial do painel dele, com 403 numa tela que é só um índice de links que ele já vê na sidebar.

### Consequências

- **Positivas**: o hub cumpre o papel de porta de entrada para quem mais precisa dele.
- **Negativas**: nenhuma — os cartões de administração dentro dele já somem sozinhos pelo `canAccess()` (ADR-04). O usuário comum vê o hub **com menos cartões**, não uma tela negada.
- **Riscos**: leitura apressada da rule concluir que faltou aplicá-la. Mitigação: o CT que assere que `panel_user` **tem** `View:HubDoNegocio` e **não tem** `ViewAny:User` documenta a decisão em código executável.

### Referências

- `database/seeders/PapeisSeeder.php:98-124`
- `.ai/rules/filament.md`
- `tests/Kit/` — `it('mantem o usuario comum fora da administracao da organizacao')`

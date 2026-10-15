# Dossiês dos pacotes — rodada 3

> Lidos em 2026-10-14: a página de cada plugin em `filamentphp.com/plugins`, o `composer.json` e o
> código-fonte da versão publicada (resolvidos numa cópia temporária fora do projeto, com Laravel
> 13.36 e Filament 5.10.2), e o relatório do Codex CLI (`## Despachos` do `03`, nº 2). Downloads e
> estrelas são os do dia; constraints são literais do `composer.json` de cada pacote.

## 1. `sehsah/filament-remember-table` — Remember Table (msehsah) — **entra**

| Item | Resultado |
|---|---|
| Versão · data | v1.0.0 · 2026-09-29 |
| Constraints | `php ^8.2`, `filament/filament ^5.0`, `spatie/laravel-package-tools ^1.16` — sem `laravel/framework` nem `illuminate/*` (aceito pela resolução com Laravel 13.36) |
| Licença · adoção | MIT · 45 downloads · 3 estrelas |
| Registro | provider auto-descoberto; trait `HasRememberTable` na Page de listagem + `...$this->getTableViewActions()` em `getHeaderActions()`; **não** é plugin de painel |
| Persistência | migration `create_remember_table_views_table` (publicável): `id`, `morphs('user')`, `tenant_id` (string, nullable, index), `table_key`, `name`, `icon`, `state` (json), `is_default`, `is_public`, `sort_order`, timestamps; model `TableView` (`$guarded = ['id']`) |
| O que grava | filtros, busca global, buscas por coluna, ordenação, agrupamento, colunas (visibilidade e ordem), aba ativa; `per_page` desligado por default |
| Recursos | visão padrão por usuário; "Compartilhar com todos" (`allow_public_views`); presets em código (`PresetView`); `?view={id}` na URL; "(modificada)" no botão; "Atualizar visão atual" só para o dono; gerenciar (renomear, reordenar, apagar, padrão, compartilhar); "Limpar tabela"; validação ao aplicar (filtro/coluna/grupo/aba removidos são ignorados; coluna nova aparece) |
| Multi-organização | **sim**: `tenant_id` = `Filament::getTenant()?->getKey()`; `scope_to_tenant` (default `true`) filtra toda consulta por `forTenant()` |
| Config | `table_name`, `model`, `remember.*` (8 partes), `allow_public_views` (default **true**), `apply_default_view_on_load`, `scope_to_tenant`, `max_views_per_table` (50) |
| Blade / CSS | **nenhuma** blade própria — só Actions, ActionGroup, Repeater e Toggle do Filament; nada a cobrir no CSS do kit |
| Traduções | só `en` e `ar` no `resources/lang/` (a página do plugin lista 19 idiomas, inclusive pt-BR — não confere) |
| Testes do pacote | Pest (model, estado, presets, escopo por tabela/organização) |
| Sobreposição com o kit | `persistir_filtros` guarda o **último** recorte na sessão; o pacote guarda **vários**, nomeados, no banco; os dois convivem (o pacote lê o estado do Filament, que já foi hidratado da sessão) |
| Custo | 1 consulta por carga de listagem (lista de visões) + 1 na primeira carga sem estado na URL (visão padrão); zero com a feature desligada pelo trait do kit |
| Veredito | **entra** — fecha o nível (b) do `estudo-advanced-tables`; única opção com escopo por organização e sem blade própria. Embrulhado em `App\Filament\Concerns\VisoesDeTabela` (interruptor + permissão) |

## 2. `spiggle/list-view` — List View (Spiggle) — **fica fora**

| Item | Resultado |
|---|---|
| Versão · data | v1.0.2 · 2026-10-01 (o README ainda diz "não está no Packagist"; o P2 do Packagist o lista) |
| Constraints | `php ^8.2`, `filament/filament ^5.0 \|\| ^5.7`, `illuminate/contracts ^11\|^12\|^13`, `illuminate/support ^11\|^12\|^13` |
| Licença · adoção | MIT · 6 downloads · 2 estrelas |
| Registro | plugin de painel: `$panel->plugin(ListViewPlugin::make())` — alcança **toda** tabela do painel, inclusive as de outros plugins |
| Persistência | nenhuma; o modo fica na sessão, chave `'slv:'.$livewire::class` — **sem organização nem painel** |
| O que entrega | um alternador tabela/lista no fim da barra de ferramentas; o modo lista reconstrói as linhas como cartões A–Z com título (`name`/`title`/`number`/primeira coluna de texto), subtítulo (`email`/próxima coluna de texto) e iniciais coloridas; clique na linha usa `recordAction`/`recordUrl` |
| Config | nenhuma documentada além de `make()` |
| Por que fica fora | (1) reconstrói as colunas: badge (`badgeable-column`), imagem com lightbox, ícone booleano e colunas custom do kit não aparecem no modo lista; (2) a preferência é por classe, sem organização — o mesmo usuário em duas organizações herda o modo; (3) plugin de painel é registrado no **boot**, então não daria para governar pelo Settings sem um decisor por request (`.ai/rules/settings.md`); (4) o kit já empilha linha em cartão no celular (`stackedOnMobile()`), que é o caso de uso real de "lista"; (5) adoção mínima |
| Gatilho de reabertura | um projeto do kit pedir lista alfabética com iniciais no desktop |

## 3. `asignua/filament-infinite-scroll` — Infinite Scroll (asign) — **entra**

| Item | Resultado |
|---|---|
| Versão · data | v1.0.1 · 2026-10-08 |
| Constraints | `php ^8.3`, `filament/filament ^5.0`, `illuminate/contracts ^12\|^13`, `livewire/livewire ^4.0`, `spatie/laravel-package-tools ^1.16` |
| Licença · adoção | MIT · 172 downloads · 1 estrela · CI com PHP 8.4 e Laravel 13 |
| Registro | provider auto-descoberto: macro `Table::infiniteScroll(perPage, mode, maxRecords, rootMargin)`, hook global do Livewire (`InfiniteScrollHook`), render hook `TablesRenderHook::CONTENT_AFTER`, folha `filament-infinite-scroll.css` (publicada por `filament:assets`) |
| Persistência | nenhuma; o tamanho emitido vive no memo do snapshot do Livewire (coberto pelo checksum) |
| Como funciona | aumenta `tableRecordsPerPage` em blocos (uma query só, com `LIMIT` maior e `COUNT(*)`), reinicia no primeiro bloco quando busca/filtro/ordenação/agrupamento/aba/filtros de página mudam; fixa na tabela `paginated([$perPage])`, `defaultPaginationPageOption($perPage)`, `PaginationMode::Default`, `persistRecordsPerPageInSession(false)` |
| Modos | `scroll` (sentinela `x-intersect` com margem) e `button` ("Carregar mais"); rodapé "Mostrando N de M" / "Todos os M registros foram carregados" / "Mostrando os primeiros N de M… use a busca ou os filtros" |
| Config | `per_page` (25), `mode` (`scroll`), `max_records` (500; `null` sem teto), `root_margin` (300 px) |
| Limitações declaradas | o seletor "registros por página" some; não combinar `deferLoading()` com `records()` custom (o kit não usa `records()` custom); paginação simples/cursor não suportadas (o render hook devolve vazio); o DOM cresce com a lista (daí o teto) |
| Blade / CSS | blade do rodapé com classes `fi-ta-infinite-scroll*` próprias, estilizadas pela folha do pacote; **sem `@layer`**, sem utilitária Tailwind solta |
| Traduções | 10 idiomas, **com `pt_BR`** |
| Testes do pacote | PHPUnit/Testbench: crescimento, teto, clamp do cliente, filtros, widgets, relation managers; o autor declara que o comportamento Alpine é validado à mão |
| Sobreposição com o kit | `defaultPaginationPageOption(kit.tabelas.paginacao)` e `persistRecordsPerPageInSession` são sobrepostos pelo macro quando ligado; com o default (`paginacao`) nada muda |
| Veredito | **entra** — um macro no `configureUsing()` global, governado por `kit.tabelas.carregamento` (`paginacao` · `rolagem` · `botao`), bloco = "Linhas por página" |

## 4. `happenv-com/filament-phpstan-macros` — PHPStan Readable Macros (Happenv) — **entra, em `require-dev`**

| Item | Resultado |
|---|---|
| Versão · data | v1.0.0 · 2026-09-24 |
| Constraints | `php ^8.3`, `phpstan/phpstan ^2.1`; sem dependência de runtime (Filament só em `require-dev` do pacote: `^3.3 \|\| ^4.0 \|\| ^5.0`) |
| Licença · adoção | MIT · 855 downloads · 0 estrelas |
| Registro | `extra.phpstan.includes` (automático com `phpstan/extension-installer`); sem o installer, `includes: - vendor/happenv-com/filament-phpstan-macros/extension.neon` — o caminho do kit, que não usa o installer por decisão escrita no `phpstan.neon` |
| O que faz | `methodsClassReflectionExtension` que lê os macros registrados em `Filament\Support\Concerns\Macroable` (closure, invocável ou array callable, `macro()` ou `mixin()`), com parâmetros e retorno tipados da closure; macro na classe vence o do pai, como em runtime; retorno de ancestral vira `static` |
| Condição | o macro precisa estar registrado quando a análise roda — o Larastan boota a aplicação, então macro registrado em **service provider** (`packageBooted()` do `infinite-scroll`) é visto; macro registrado no `boot(Panel)` de um plugin de painel (`simpleLightbox()`) **não** — a exceção dele no `phpstan.neon` fica |
| Limitações | closure sem tipo de retorno → `mixed`; só chamadas de instância; só o `Macroable` do Filament (o do Laravel continua com o Larastan) |
| Testes do pacote | Pest, CI com PHP 8.3–8.5 e Filament 3/4/5 |
| Veredito | **entra** — o kit roda PHPStan nível 8 como gate; o macro `infiniteScroll()` é chamado direto e tipado, sem `hasMacro()` nem `ignoreErrors` (a prova de valor) |

## 5. `happenv-com/filament-saved-views` — Saved Views (Happenv) — **fica fora**

| Item | Resultado |
|---|---|
| Versão · data | v1.6.1 · 2026-09-24 |
| Constraints | `php ^8.3`, `filament/filament ^4.11.2 \|\| ^5.6.2`, `illuminate/* ^11\|^12\|^13`, `livewire ^3\|^4`, `spatie/eloquent-sortable ^4.5\|^5.0` |
| Licença · adoção | MIT · 758 downloads · 0 estrelas · CI Laravel 12/13, PHP 8.3–8.5, Filament 4/5 |
| Registro | provider + `$panel->plugin(FilamentSavedViewsPlugin::make())` + trait `HasSavedViews` na Page + macros de `Table` (`savedViewManagerLayout`, `savedViewManagerTriggerAction`, `deferSavedViewManager`) + componente Livewire `filament-saved-views-control` |
| Persistência | migration `create_saved_views_table`: `id`, `user_id` (nullable), `label`, `class`, `saved_data` (json), `sort_order`, `submenu_visible` — **sem `tenant_id`**; model `SavedView` com `SortableTrait` e `$guarded = []` |
| O que entrega | visões por usuário (filtros, busca, buscas por coluna, ordenação, agrupamento, per-page, colunas e ordem); "Atualizar esta visão" explícito; ponto de "não salvo"; gerenciador dropdown/modal/slide-over; reordenar, renomear, apagar; checkbox de sub-navegação; modo adiado; 64 locales; ícones configuráveis; model trocável pelo container |
| Instalação oficial | exige **tema customizado** do Filament e `@source '../../../../vendor/happenv-com/filament-saved-views/resources/**/*.blade.php'` no CSS do tema |
| Blade / CSS | três blades com 28 tokens de classe, entre eles `flex`, `flex-col`, `gap-2`, `gap-3`, `px-2`, `py-1.5`, `text-sm`, `text-gray-400`, `border-t`, `dark:border-white/10`, `-mx-1`, `absolute`, `w-full` — utilitárias que a CSS pré-compilada do Filament 5 não carrega (`.ai/rules/css-filament.md`); a folha própria do pacote cobre só as classes `fi-fm-sv-*` |
| Por que fica fora | (1) mesmo requisito do `remember-table`; (2) sem escopo por organização na implementação padrão — um usuário de duas organizações vê as mesmas visões nas duas; adaptar exige model próprio e validação; (3) blade própria em utilitárias Tailwind sem tema customizado → CSS do kit escrito à mão + guarda em runtime, a família de defeito silencioso que a rule registra duas vezes; (4) sem compartilhamento |
| Gatilho de reabertura | abandono do `remember-table`; ou o kit adotar tema customizado **e** o pacote ganhar escopo por organização |

## Comparativo

| Pacote | Stack ok | Banco próprio | Blade própria | Organização | pt-BR | Decisão |
|---|---|---|---|---|---|---|
| `sehsah/filament-remember-table` | sim (Laravel por transitiva) | `remember_table_views` | não | **sim** | não (kit escreve) | **entra** |
| `spiggle/list-view` | sim | não | sim (plugin de painel) | não | — | fora |
| `asignua/filament-infinite-scroll` | sim | não | rodapé com classes próprias | n.a. | sim | **entra** |
| `happenv-com/filament-phpstan-macros` | sim (PHPStan 2.x) | não | não | n.a. | n.a. | **entra (dev)** |
| `happenv-com/filament-saved-views` | sim | `saved_views` | sim (utilitárias Tailwind) | não | sim | fora |

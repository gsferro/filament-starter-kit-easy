# Plano de Ação — Estudo e adoção de pacotes Filament, rodada 3 (tabelas e PHPStan)

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: nenhuma — é uma rodada nova de estudo. Duas wikis são **parentes**, não ancestrais: `wikis/specs/feat/estudo-advanced-tables/estudo-advanced-tables/` (o nível (b) daquele estudo, "visões salvas por usuário", é exatamente o que o `remember-table` entrega de graça) e `wikis/specs/feat/settings-do-kit/settings-do-kit/` (a aba *Tabelas* das configurações, que esta feature estende).
- **Motivo**: cinco pacotes novos no diretório de plugins do Filament, a avaliar com o critério "agrega valor ao kit".
- **Toca infra compartilhada?**: **sim** → `database/seeders/PapeisSeeder.php` (matriz de papéis, permissão nova), `config/filament-shield.php` (`custom_permissions`), `app/Providers/Concerns/ConfiguraFilamentGlobal.php` (default de **toda** tabela dos três painéis), `app/Settings/ConfiguracoesDoKit.php` + migration de settings, `tests/Pest.php` (helpers). A regressão do quality gate é obrigatória contra os CT de `settings-do-kit` (aba Tabelas), `permissoes-de-telas-e-acoes` (inventário) e `dock-de-acoes-rapidas` (custom permission mais recente, mesmo mecanismo).

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Os cinco pacotes analisados com o critério escrito | 0 (dossiê), 13 | O dossiê é a seção *O que cada pacote entrega e o veredito* abaixo, mais `07-dossies-dos-pacotes.md`; a documentação de usuário (passo 13) publica o resumo |
| RQ-02 | Só o que agrega valor é instalado; o resto fica fora com motivo | 1, 13 | Entram três (`remember-table`, `infinite-scroll`, `phpstan-macros`); ficam fora dois (`list-view`, `saved-views`) — P-06 |
| RQ-03 | Codex CLI na análise e na implementação | — | restrição de processo, sem comportamento do sistema a testar: sem CT por decisão; a evidência é `## Despachos` do `03` (relatório do Codex no step 3; lotes D0 e L1…L8 e CT-B pelo Codex) — passos 0, 11 e 12 a cumprem |
| RQ-04 | Toda funcionalidade de usuário liga/desliga nas configurações | 3, 4, 5, 6, 7, 9 | Três chaves novas na aba *Tabelas*: carregamento da tabela, visões salvas, visões compartilhadas. A extensão de PHPStan não tem interruptor (P-05) |
| RQ-05 | Tudo nasce desligado; ligar é alteração de settings, não deploy | 3, 5, 7, 9 | `.env` semeia desligado; o banco vence; as três chaves são lidas **por request** (critério de `.ai/rules/settings.md`) |
| RQ-06 | Tudo documentado | 13 | docs pt/en (página nova + três páginas existentes), `CHANGELOG`, READMEs, `wikis/pacotes.md`, `pacotes-instalados` |
| RQ-07 | Thumbs e GIF nos temas claro e escuro | 14 | Visões salvas (GIF + 3 capturas × 2 temas), carregamento contínuo (2 capturas × 2 temas), aba Tabelas (1 captura) |
| RQ-08 | Branch nova, sem interferir no CI da outra sessão | — | restrição de processo, sem comportamento do sistema a testar: sem CT por decisão; a evidência é o git (`feat/estudo-de-pacotes-rodada-3` criada no step 1, `main` só mergeada para dentro, PR só depois do veredito) — passo 15 a cumpre |
| P-01 | Permissão só nos papéis de administração | 8 | — |
| P-02 | "Compartilhar com todos" governada por interruptor próprio, desligado | 3, 5, 6 | — |
| P-03 | Desligar esconde, não apaga | 9 | — |
| P-04 | Carregamento contínuo é default global de tabela, bloco = "Linhas por página" | 7 | — |
| P-05 | PHPStan sem interruptor | 2 | — |
| P-06 | `list-view` e `saved-views` ficam fora, com gatilho de reabertura | 13 | — |
| P-12 | Filament 5.8.2 → 5.10.2 nesta entrega | 1 | — |
| P-13 | Nome ≤ 100, teto 50, repetição permitida | 1 (config do pacote), 9 | — |
| P-14 | Só a padrão do próprio usuário abre sozinha | 9 | comportamento do pacote, provado por CT |
| P-15 | Compartilhamento visível só com visões ligadas; desligar as visões grava o compartilhamento desligado | 6 | — |
| P-16 | A visão não guarda tamanho de página | 1 (config do pacote) | — |
| P-17 | pt_BR completo | 10 | — |
| P-18 | Teto de 50 contado por organização | 9 | comportamento do pacote (`forTenant()`), provado por CT |
| P-19 | Gerenciar com compartilhamento desligado grava `is_public = false` | 9 | comportamento do pacote, provado por CT |
| P-20 | `master_global` sem exceção na posse | 9 | comportamento do pacote (`ownedBy`), provado por CT |
| P-21 | Nome só com espaços recusado | 9 | `required` do Laravel + `TrimStrings`, provado por CT |
| P-22 | `?view` vence a padrão | 9 | comportamento do pacote, provado por CT |
| P-23 | Total = 500 não pede busca nem filtro | 7 | comportamento do pacote, provado por CT |
| P-24 | Ilegível em `tabela_carregamento` coagido ao preencher e ao salvar | 6 | — |
| P-25 | Docs pt e en | 13 | — |
| P-26 | Gerenciar com compartilhamento desligado regrava todas as enviadas como privadas | 9 | comportamento do pacote, provado por CT (substitui P-19) |
| P-27 | `master_global` sem exceção na visibilidade | 9 | comportamento do pacote, provado por CT |
| P-28 | Tipo errado na propriedade do pacote é recusado pelo framework | 9 | comportamento do vendor/Livewire, provado por CT-32 |
| P-29 | Visibilidade do vendor preservada por `hidden()` | 9 | RD-01 do step 9; CT derivado |
| P-30 | Aliases `*DoPacote` protegidos, fora do `$wire` | 9 | RD-02 do step 9; CT derivado |
| P-31 | Dropdown do cabeçalho da página acima da barra da tabela (z-index 22) e cópia publicada em dia | 9 (capturas) | achado da sessão; CT-81 estrutural |

## Objetivo

Avaliar cinco plugins da comunidade Filament e adotar os que agregam valor ao kit, cada um
ligado e desligado pela tela de configurações, nascendo desligado. Ao fim, o kit ganha **visões
salvas de tabela** (filtros, busca, ordenação, agrupamento e colunas gravados com nome, por usuário
e por organização), **carregamento contínuo** das tabelas (rolagem infinita ou botão "Carregar
mais") e **análise estática que entende os macros do Filament** — e fica documentado por que os
outros dois pacotes não entraram.

## Contexto

A aba *Tabelas* de `/admin/configuracoes-da-aplicacao` governa hoje quatro defaults de toda tabela
dos três painéis (paginação, listras, persistência do recorte na sessão e colunas arrastáveis),
pelo caminho `config('kit.tabelas.*')` → `ConfiguraFilamentGlobal::configuraTable()`. O estudo
`estudo-advanced-tables` (2026-08-26) custeou "visões salvas por usuário" como o item de maior
valor que o kit não tinha, e estimou implementá-lo à mão (model, policy, trait, testes). O
`remember-table` entrega esse item pronto, com escopo por organização e compartilhamento, sem
blade própria (só Actions do Filament — nenhuma utilitária Tailwind a cobrir, ao contrário do
`saved-views`). O `infinite-scroll` é um macro de `Table`, que encaixa no mesmo `configureUsing()`
global. O `phpstan-macros` fecha uma lacuna conhecida do Larastan: macro de componente Filament é
"undefined method", e a exceção em `ignoreErrors` apaga a análise de tudo que vem depois na cadeia.

## O que cada pacote entrega e o veredito (RQ-01, RQ-02)

Fontes: página do plugin, `composer.json` e código-fonte da versão publicada (resolvidos numa cópia
temporária, fora do projeto), e o relatório do Codex (`## Despachos` do `03`). Dossiê completo em
`07-dossies-dos-pacotes.md`.

| Pacote (Composer) | Versão · data | Stack (PHP 8.4 · Filament ^5.8 · Laravel ^13) | O que entrega | Veredito |
|---|---|---|---|---|
| `sehsah/filament-remember-table` | 1.0.0 · 2026-09-29 | `php ^8.2`, `filament/filament ^5.0`; sem constraint de Laravel (transitiva) | visões salvas por página de listagem: filtros, busca, buscas por coluna, ordenação, agrupamento, colunas (visibilidade e ordem), aba ativa; visão padrão; compartilhar com o painel; `?view=` na URL; **escopo por organização** (`Filament::getTenant()`), teto por usuário; trait + ações de cabeçalho, sem blade própria | **entra** — fecha o nível (b) do `estudo-advanced-tables` sem código próprio |
| `asignua/filament-infinite-scroll` | 1.0.1 · 2026-10-08 | `php ^8.3`, `filament ^5.0`, `illuminate/contracts ^12\|^13`, `livewire ^4` | macro `Table::infiniteScroll()`: rolagem infinita ou botão "Carregar mais", um bloco por vez, teto de linhas, reinício ao filtrar/buscar/ordenar; hook global do Livewire, render hook no fim da tabela, CSS própria que esconde o paginador | **entra** — encaixa no `configureUsing()` global como os quatro defaults existentes |
| `happenv-com/filament-phpstan-macros` | 1.0.0 · 2026-09-24 | `php ^8.3`, `phpstan ^2.1`; sem dependência de runtime | extensão do PHPStan: macro registrado em `Filament\Support\Concerns\Macroable` vira método tipado; a cadeia depois do macro volta a ser analisada | **entra, em `require-dev`** — o kit roda PHPStan nível 8 como gate; o macro `infiniteScroll()` é chamado direto, sem `hasMacro()` nem `ignoreErrors` |
| `spiggle/list-view` | 1.0.2 · 2026-10-01 | `php ^8.2`, `filament ^5.0`, `illuminate ^11\|^12\|^13` | alternador tabela/lista alfabética em **toda** tabela do painel; título/subtítulo inferidos das colunas; modo guardado na sessão por classe, sem organização | **fica fora** — reconstrói as colunas (badge, lightbox e coluna custom do kit não sobrevivem ao modo lista), chave de sessão sem organização, 6 downloads; o kit já empilha em cartão no celular. Reabrir se um projeto pedir lista alfabética |
| `happenv-com/filament-saved-views` | 1.6.1 · 2026-09-24 | `php ^8.3`, `filament ^4.11.2\|^5.6.2`, `livewire ^3\|^4` | visões salvas por usuário, com reordenação, submenu e três layouts de gerenciador | **fica fora** — mesmo requisito do `remember-table`, sem escopo por organização (a migration não tem `tenant_id`) e com blade própria em utilitárias Tailwind que exige tema customizado (`@source` no CSS do tema), que o kit não tem (`.ai/rules/css-filament.md`). Reabrir se o `remember-table` for abandonado |

## Análise dos Arquivos Existentes

### `app/Providers/Concerns/ConfiguraFilamentGlobal.php`
- `configuraTable()` (`app/Providers/Concerns/ConfiguraFilamentGlobal.php:configuraTable():236`) lê `kit.tabelas.*` por request dentro do `Table::configureUsing()`; é onde o carregamento contínuo entra, logo antes de `aplicaMacrosDeColuna()`.
- `aplicaMacrosDeColuna()` (`:aplicaMacrosDeColuna():314`) chama macro do `resized-column` por `hasMacro()` — padrão que o `phpstan-macros` dispensa para o macro novo.

### `app/Settings/ConfiguracoesDoKit.php`
- Bloco `// Tabelas` com quatro propriedades (`app/Settings/ConfiguracoesDoKit.php:$paginacao_padrao:151`); `mapaDeConfiguracao()` (`:mapaDeConfiguracao():422`) é a única ligação propriedade → config. Três propriedades novas entram no bloco e três linhas no mapa; duas apontam para `kit.tabelas.*` e uma para `remember-table.allow_public_views` (chave de config do pacote, como as de e-mail apontam para `mail.*`).

### `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`
- `abaTabelas()` (`app/Filament/Admin/Pages/ConfiguracoesDoKit.php:abaTabelas():557`) é a aba a estender; `mutateFormDataBeforeSave()` coage a densidade (`:201`) e coagirá o carregamento pelo mesmo caminho.

### `config/kit.php`
- Bloco `'tabelas'` (`config/kit.php:'tabelas':289`) com `BooleanoDoEnv::comPadrao()`; ganha `carregamento` e `visoes_salvas`.

### `app/Support/DensidadeDoLayout.php`
- Molde do enum de configuração com `opcoes()`, `deConfig()`, `coagir()` e `padrao()` (`app/Support/DensidadeDoLayout.php:coagir():236`). `CarregamentoDaTabela` segue o mesmo contrato.

### `database/settings/2026_10_14_030956_add_dock_to_kit_settings.php`
- Molde da migration de settings com limpeza do cache do repositório na ida e na volta.

### `database/seeders/PapeisSeeder.php` e `config/filament-shield.php`
- `paineisDasPermissoesCustomizadas()` (`database/seeders/PapeisSeeder.php:paineisDasPermissoesCustomizadas():252`) e a subtração explícita do `panel_user` (`:118`, `Usar:Dock`) são o molde de P-01; `custom_permissions` (`config/filament-shield.php:'custom_permissions':408`).

### `tests/Kit/PermissoesDeAcoesTest.php`
- `inventarioDeAutorizacao()` (`tests/Kit/PermissoesDeAcoesTest.php:inventarioDeAutorizacao():541`) varre `Action::make(` em `app/Filament/**` e fica vermelho com Action nova fora do inventário. As Actions das visões são construídas pelo **vendor** (`HasRememberTable`), então a varredura não as vê — o `->authorize()` entra pelo trait do kit (passo 9) e o inventário ganha as linhas mesmo assim, por decisão, para que a varredura passe a cobri-las se o padrão mudar.

### As dez páginas de listagem do kit
- `app/Filament/{Admin,App,Infra}/Resources/*/Pages/List*.php` — 10 arquivos; 7 usam `Asmit\ResizedColumn\HasResizableColumn` (`grep -l HasResizableColumn app/Filament` = 7). `ListRoles` declara `getActions()` (`app/Filament/Admin/Resources/Roles/Pages/ListRoles.php:getActions():18`), que o Filament honra como fonte das header actions.

### `phpstan.neon` e `tests/Kit/QualidadeDeCodigoTest.php`
- `includes:` manual (sem `phpstan/extension-installer`, `phpstan.neon:includes:5`); o `[CT-06]` daquele teste congela o inventário de `ignoreErrors` em **3** entradas — a feature não acrescenta nenhuma.

### `app/Console/Commands/KitUpdate.php`
- `CAMINHOS_DO_KIT` lista `config/*.php` **um a um**; `config/remember-table.php` publicado precisa da linha. `database/migrations`, `database/settings`, `database/seeders`, `lang/vendor`, `app/Support`, `app/Filament` e `tests/*` já estão.

### `tests/Kit/SiteDeDocumentacaoTest.php`
- `mantem os numeros objetivos dos readmes sincronizados com a arvore` (`tests/Kit/SiteDeDocumentacaoTest.php:'mantem os numeros objetivos dos readmes sincronizados com a arvore':990`) exige nos dois READMEs: pacotes de produção = chaves `require` com `/` (hoje 59 → 61), de desenvolvimento (20 → 21) e migrations (63 → 64).

### O vendor dos pacotes (conferido no fonte)
- `HasRememberTable` (`vendor/sehsah/filament-remember-table/src/Concerns/HasRememberTable.php`): `bootedHasRememberTable()` (`:bootedHasRememberTable():78`) aplica a visão padrão **em toda carga** da página, com uma consulta; `getTableViewActions()` (`:getTableViewActions():119`) monta o botão; `getTableViews()` (`:getTableViews():560`) consulta `visibleTo($user)` + `forTenant()`; `applyTableView()` (`:applyTableView():304`) é endpoint público do Livewire; `syncManagedTableViews()` (`:syncManagedTableViews():649`) só atualiza/apaga o que `ownedBy($user)` devolve. O pacote **não traz pt_BR** (só `ar` e `en`) — a página do plugin afirma o contrário.
- `InfiniteScroll::configure()` (`vendor/asignua/filament-infinite-scroll/src/InfiniteScroll.php:configure():17`) fixa `paginated([$perPage])`, `defaultPaginationPageOption($perPage)`, `PaginationMode::Default` e `persistRecordsPerPageInSession(false)`; `InfiniteScrollHook` (`vendor/asignua/filament-infinite-scroll/src/Livewire/InfiniteScrollHook.php:sync():167`) devolve ao intervalo qualquer `tableRecordsPerPage` que o navegador mande e reinicia no primeiro bloco quando busca, filtro, ordenação, agrupamento ou aba mudam. A folha publicada (`vendor/asignua/filament-infinite-scroll/resources/dist/filament-infinite-scroll.css`) usa só classes `fi-*` próprias — nada a cobrir no CSS do kit — e **não declara `@layer`**.
- `extension.neon` do `phpstan-macros` registra uma `methodsClassReflectionExtension`; sem `extension-installer`, o include é manual.

## Decisões de Desenho

| # | Decisão | Pergunta | Portão que falta | Quem decidiu, data |
|---|---|---|---|---|
| D1 | O carregamento é **um** campo de três valores (`paginacao` · `rolagem` · `botao`), não um toggle + um select: um estado só, sem combinação "ligado mas sem modo" | autorização existente (RQ-04) · molde `DensidadeDoLayout` | fácil de reverter | sessão, 2026-10-14 |
| D2 | O bloco do carregamento contínuo reusa "Linhas por página" (`kit.tabelas.paginacao`), sem campo novo; o teto fica no default do pacote (500) | P-04 · Ponytail | fácil de reverter | sessão, 2026-10-14 |
| D3 | As visões salvas entram nas **dez** páginas de listagem do kit por um trait do kit (`App\Filament\Concerns\VisoesDeTabela`) que embrulha o do pacote; Resource de vendor fica de fora (não há como editar a Page dele) | convenção existente (`HasResizableColumn` é opt-in por página pelo mesmo motivo) | fácil de reverter | sessão, 2026-10-14 |
| D4 | O interruptor das visões é lido **por request** dentro do trait: desligado, o trait devolve `[]` em `getTableViewActions()`, coleção vazia em `getTableViews()` e não aplica visão padrão no `booted` — zero consulta ao banco com a feature desligada | RQ-05 · `.ai/rules/settings.md` | não surpreende (é o critério da rule) | sessão, 2026-10-14 |
| D5 | O interruptor de compartilhamento mapeia direto para `remember-table.allow_public_views` (config do pacote) pelo `mapaDeConfiguracao()`, e o arquivo `config/remember-table.php` publicado semeia `false` do `.env` — assim o default do pacote (`true`) nunca vale | P-02 | fácil de reverter | sessão, 2026-10-14 |
| D6 | Permissão `Usar:VisoesDeTabela` em `custom_permissions`, aplicada por `->authorize()` nas quatro Actions do pacote (salvar, atualizar, gerenciar, limpar) via alias de trait; **aplicar** uma visão (`applyTableView`) não exige a permissão: só lê o que o usuário já vê | P-01 · `.ai/rules/filament.md` ("Action declara `->authorize`") | não surpreende | sessão, 2026-10-14 |
| D7 | `phpstan-macros` entra por `includes:` manual no `phpstan.neon` (o kit não usa `extension-installer`, por decisão registrada no próprio arquivo) | P-05 | fácil de reverter | sessão, 2026-10-14 |
| D8 | O macro `infiniteScroll()` é chamado **direto**, sem `hasMacro()`: o pacote é dependência declarada e a extensão de PHPStan o tipa; é a prova observável de que o `phpstan-macros` agrega valor (nenhuma linha nova em `ignoreErrors`) | D7 | fácil de reverter | sessão, 2026-10-14 |
| D9 | Traduções pt_BR do `remember-table` em `lang/vendor/filament-remember-table/pt_BR/` (o pacote só traz `en` e `ar`) — convenção "UI 100% em pt-BR" do README | RQ-06 | fácil de reverter | sessão, 2026-10-14 |
| D10 | A migration publicada do pacote vai para `database/migrations/` como as de `breezy`, `health` e `pulse` | convenção existente | fácil de reverter | sessão, 2026-10-14 |
| D11 | A barreira `Usar:VisoesDeTabela` fica só nas quatro Actions, não no model `TableView`: nenhum ponto de entrada grava o model fora delas (greps da `## Superfície Livewire`) | Q12 (raia desenho, devolvida pela derivação do `04`) | não surpreende | sessão, 2026-10-14 |

## Autorização

- **Policies**: nenhuma nova. O model `TableView` do pacote não tem Resource; quem decide é a query (`visibleTo`/`ownedBy` + `forTenant`) e a permissão abaixo.
- **Gates**: nenhum.
- **Permissão custom**: `Usar:VisoesDeTabela` (`config('filament-shield.custom_permissions')`), painéis `app`, `admin`, `infra`; nasce em `admin`, `infra` e `admin_app`, subtraída do `panel_user` (P-01). Aplicada por `->authorize('Usar:VisoesDeTabela')` nas Actions `saveTableView`, `updateTableView`, `manageTableViews` e `resetTableView`.
- **Middleware / Guards**: nenhum novo.

## Rotas

Nenhuma rota nova. O `?view={id}` é query string da própria listagem (`#[Url(as: 'view')]` do pacote).

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Aba *Tabelas* de `ConfiguracoesDoKit` (campos novos: `tabela_carregamento`, `visoes_de_tabela`, `visoes_de_tabela_compartilhadas`) | Filament (SettingsPage) | `/admin/configuracoes-da-aplicacao` | escolhe o carregamento; liga as visões; liga o compartilhamento (só com visões ligadas) | Não (toggle `live` só esconde o terceiro campo) |
| Botão **Visões** no cabeçalho das 10 listagens do kit (`ActionGroup` do pacote): lista de visões, *Salvar como nova visão* (modal: nome, padrão, compartilhar), *Atualizar visão atual*, *Gerenciar visões* (repeater reordenável), *Limpar tabela* | Filament (Actions) | `/admin/users`, `/admin/organizacoes`, `/admin/convites`, `/admin/agentes-ia`, `/admin/shield/roles`, `/app/{org}/projetos`, `/app/{org}/users`, `/app/{org}/convites`, `/infra/ai-runs`, `/infra/composer-release-packages` | salva, aplica, atualiza, renomeia, reordena, apaga e compartilha visões | Não (dropdown e modais do Filament; o reorder do repeater é JS, mas a gravação é componente) |
| Rodapé de carregamento contínuo em **toda** tabela (`Mostrando N de M`, indicador de carga, botão *Carregar mais*) | Blade do pacote via render hook | todas as listagens dos três painéis, inclusive de plugins | rola até o fim ou clica em *Carregar mais* | **Sim** (`x-intersect` dispara o `$wire.infiniteScrollLoadMore()`; o botão é `wire:click`) |

**Gate de CT-B**: o único cenário que só o navegador prova é o da rolagem (a interseção que dispara o próximo bloco) e o do tema escuro do rodapé; tudo o mais (gravação de visão, aplicação, permissão, settings) é costura de componente e vai para o `04`.

**Gate de tela de escrita**: a aba *Tabelas* é tela de gravação → o `04` precisa de cenário de gravação por componente dos três campos; os modais de salvar/gerenciar visão também.

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_TABELA_CARREGAMENTO` | `paginacao` | `paginacao` (o de hoje), `rolagem` (infinita) ou `botao` ("Carregar mais"); valor fora do vocabulário cai em `paginacao` |
| `KIT_TABELA_VISOES_SALVAS` | `false` | liga o botão *Visões* nas listagens do kit |
| `KIT_TABELA_VISOES_COMPARTILHADAS` | `false` | liga a opção "Compartilhar com todos" nas visões; lida em `config/remember-table.php` (`allow_public_views`) |

As três são semente e plano B: o banco (aba *Tabelas*) vence, como as quatro já existentes.

## Eventos / Listeners / Observers

- **Eventos emitidos**: nenhum.
- **Listeners**: nenhum novo. `AuditarConfiguracoesDoKit` já ouve `SavingSettings` e registra as três chaves novas sem mudança.
- **Observers**: nenhum.

## Jobs / Queues

Nenhum.

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | Listagem: os de hoje (carga adiada da tabela = 2). Com carregamento contínuo, **+1 request por bloco** carregado (`infiniteScrollLoadMore`), que reexecuta a query com `LIMIT` maior — é como o pacote funciona (`vendor/asignua/filament-infinite-scroll/src/InfiniteScroll.php:configure():17-53`, `:45-52`, comentário do vendor) |
| O que é adiado, e por qual gatilho? | `deferLoading` da tabela (já existe); o próximo bloco, por interseção do sentinela (`rolagem`) ou clique (`botao`) |
| O que é memoizado **por request**? | A lista de visões do usuário (`cachedTableViews` do trait, uma consulta por request com a feature ligada); a `InfiniteScrollConfig` da tabela (`WeakMap` por instância) |
| O que é cacheado **entre** requests? | Nada novo. As configurações já vêm do cache do `spatie/laravel-settings` quando ligado |
| Custo do caminho principal | Visões **desligadas**: zero consulta extra em qualquer listagem (D4 — **medido**: 0 consultas a `remember_table_views` na montagem e na carga). Ligadas *(alterado em 2026-10-15, medição do step 10)*: **4 consultas na montagem** (lista de visões, `id` da visão padrão e dois `exists` da condição `visible()` de *Gerenciar*, o único `exists()` do trait, avaliado no grupo e na Action — `vendor/sehsah/filament-remember-table/src/Concerns/HasRememberTable.php:manageTableViewsAction():238`, `:246`) e **3 por render seguinte** (lista + dois `exists`); o modelo anterior ("+1 e +1") não contava os `exists`. Carregamento contínuo: a mesma query da página com `LIMIT` crescente e um `COUNT(*)` por bloco (**medido**: `limit 20 offset 0` + `count(*)` no segundo bloco de 10), e o bloco re-renderiza todas as linhas carregadas — o custo por linha que a listagem já tem cresce com o total na tela |

## Impacto em Features Existentes

- **Aba Tabelas das configurações** (`settings-do-kit`): três campos a mais; a gravação da aba inteira continua uma só — valor ilegível em `tabela_carregamento` é coagido antes de salvar, como a densidade.
- **Matriz de papéis** (`permissoes-de-telas-e-acoes`, `dock-de-acoes-rapidas`): permissão nova; `PermissoesDeAcoesTest` e os seeders são rerodados.
- **Toda tabela dos três painéis**: com `carregamento ≠ paginacao`, o seletor "registros por página" some e `persistRecordsPerPageInSession` é desligado pelo pacote — é a promessa dele (`vendor/asignua/filament-infinite-scroll/src/InfiniteScroll.php:configure():17-53`, `:45-52`). Com `paginacao` (o default), **nada muda** — é o que o `[CT]` de regressão prova.
- **`resized-column`** (`HasResizableColumn`) e `remember-table` convivem na mesma Page: o `remember-table` guarda visibilidade e ordem; a largura continua do `resized-column`. Sem colisão de método (nomes distintos; conferido no step 5).
- **PHPStan nível 8**: a extensão só acrescenta reflexão; nenhuma exceção nova; o `[CT-06]` de `QualidadeDeCodigoTest` continua em 3.
- **Ordem das cascade layers**: a folha do `infinite-scroll` não declara `@layer`; `OrdemDasCascadeLayersTest` continua valendo.
- **Bump do Filament 5.8.2 → 5.10.2 (P-12)** *(alterado em 2026-10-15, QA-09 do gate — o medido no passo 1, antes só nas Notas do `03`)*: (1) notificações saíram da sessão para `Filament\Notifications\NotificationMailbox` (cache) — helpers `avisosDoFilament()`/`limparAvisosDoFilament()` em `tests/Pest.php` e os testes do dock; (2) todo widget aborta 403 **antes** do `mount()` sem `canView()` (`vendor/filament/widgets/src/Concerns/CanAuthorizeAccess.php:bootCanAuthorizeAccess():9`) — `InsightsDasOrganizacoesTest` monta como `admin`, e `GraficosDoDashboardTest` passou a usar o super-admin do Shield (flake de ordem com `HasWidgetShield::$widgetPermissionKey` estática, despacho #22 do `03`); (3) `getNavigationLabel()`, `NavigationGroup::getLabel()` e `Filament::getTenantName()` devolvem `string|Htmlable` — `textoDoRotulo()` em `DescobreCardsDoPainel` e `DockDeAtalhos`; (4) 11 citações `arquivo:símbolo:linha` do vendor refeitas; (5) `.fi-dropdown-panel` em `z-index: 20` fica abaixo do cabeçalho da tabela do `resized-column` (21) — `kit.css` sobe para 22, para todo `ActionGroup` de cabeçalho (P-31, CT-81); (6) os assets de `public/` do Filament entram atualizados no PR. Suítes Kit e Tenancy inteiras verdes depois do bump (Verificação Final do `03`).

## Rollback

- **Migration down**: a de settings apaga as três propriedades e limpa o cache do repositório; a do pacote derruba `remember_table_views`.
- **Feature flag**: as três chaves nascem desligadas — desligar na tela devolve o comportamento de hoje sem deploy (P-03: as visões gravadas ficam).
- **Reversão de dados**: nenhuma migração de dados.

## Dependências

- **Composer**: `sehsah/filament-remember-table ^1.0`, `asignua/filament-infinite-scroll ^1.0`; `--dev` `happenv-com/filament-phpstan-macros ^1.0`; `filament/*` 5.10.2 (P-12).
- **NPM**: nenhuma.

## Riscos

- **Adoção baixa dos dois pacotes de runtime** (45 e 172 downloads): mitigação — os dois são pequenos (um trait + um model; um macro + um hook), lidos por inteiro no step 3, e o kit os embrulha num ponto só cada (trait do kit; `configuraTable()`), então trocar é local.
- **O `infinite-scroll` declara `filament ^5.0` e exige 5.10** (`TablesRenderHook::CONTENT_AFTER`): mitigação — o bump do Filament (P-12), medido pela suíte antes de qualquer código do kit; o `[CT]` do `04` que prova o rodapé do carregamento é o que fica vermelho se o hook sumir.
- **O `remember-table` não declara constraint de Laravel**: mitigação — resolvido com Laravel 13.36 e Filament 5.10 na cópia temporária; a suíte do kit é a prova.
- **Carregamento contínuo em tabela de plugin que não é `LengthAwarePaginator`** (paginação simples/cursor): o render hook devolve string vazia (`vendor/asignua/filament-infinite-scroll/src/FilamentInfiniteScrollServiceProvider.php:packageBooted():40-86`, `:66-76`) — mas o hook continua registrado e reinicia a página em todo request (`vendor/asignua/filament-infinite-scroll/src/Livewire/InfiniteScrollHook.php:sync():167-209`, `:204-208`): a tabela fica presa na página 1. Não é "como era": é incompatibilidade, documentada no docblock e na página de docs; nenhuma tabela do kit usa esses modos *(alterado em 2026-10-14, step 9: CR-03)*.
- **Vazamento entre organizações nas visões**: mitigação — `scope_to_tenant` fica `true` (default do pacote) e o `04` tem cenário em `tests/Tenancy`.
- **Captura de GIF depende de `ffmpeg`** (como os clipes existentes): mitigação — `kit:arte` já reporta ausência sem falhar.

## Channel de Log da Feature

### Verificação de Channel Existente

- `config/logging.php` tem `configuracoes` (usado pelo listener de auditoria das settings), `autenticacao`, `dock-de-acoes-rapidas`, entre outros; nenhum para tabelas.

### Decisão

- **Nenhum channel novo e nenhum log novo.** A feature não tem decisão de fluxo própria: ligar/desligar é configuração lida por request; a coerção de valor ilegível segue o precedente de `DensidadeDoLayout::coagir()` (cai no default sem log — logar por request produziria um aviso por tabela renderizada); salvar/aplicar/apagar visão é operação do usuário sobre o próprio registro, notificada na UI pelo pacote; a trilha das configurações continua em `AuditarConfiguracoesDoKit` (channel `configuracoes`), que já cobre as três chaves novas. Se um passo abaixo ganhar decisão de fluxo durante a implementação, ele entra com log no channel `configuracoes` e esta seção é reescrita.

## Estrutura de Implementação

### 0. Dossiê dos pacotes e baseline

> Skills: `feature-wiki`

- **Path**: `wikis/specs/feat/estudo-de-pacotes-rodada-3/estudo-de-pacotes-rodada-3/07-dossies-dos-pacotes.md`
- Uma seção por pacote: identificação, constraints literais, o que entrega, superfície (trait/macro/plugin), persistência, multi-organização, testes do pacote, sobreposição com o kit, veredito e gatilho de reabertura. Fonte: código lido no step 3 + relatório do Codex.
- Baseline da suíte (`Kit`, `Tenancy`) em `main` registrada no `03` antes do primeiro commit.
- **Atende**: RQ-01, RQ-03
- **Logs**: nenhum.

### 1. Dependências e publicações

> Skills: `laravel-best-practices`

- `composer require sehsah/filament-remember-table:^1.0 asignua/filament-infinite-scroll:^1.0 --no-interaction`
- `composer update "filament/*" --with-all-dependencies` → Filament 5.8.2 → **5.10.2** *(alterado em 2026-10-14: P-12 — o `infinite-scroll` registra o render hook `TablesRenderHook::CONTENT_AFTER`, que o 5.8 não tem; com o pacote instalado e o Filament travado, `package:discover` morria com `Undefined constant`)*. O `filament:assets` do `post-update` republica os assets do Filament em `public/`; eles entram no commit
- `composer require --dev happenv-com/filament-phpstan-macros:^1.0 --no-interaction`
- `php artisan vendor:publish --tag=filament-remember-table-migrations --no-interaction` → `database/migrations/{timestamp}_create_remember_table_views_table.php` (D10)
- `php artisan vendor:publish --tag=filament-remember-table-config --no-interaction` → `config/remember-table.php`; editar `allow_public_views` para `BooleanoDoEnv::comPadrao(env('KIT_TABELA_VISOES_COMPARTILHADAS'), false)` com o comentário do porquê (D5) e manter o resto no default do pacote.
- `php artisan filament:assets` — publica a folha do `infinite-scroll` em `public/css/asignua/...` (caminho publicado por deploy, como as demais folhas de plugin; conferir no `.gitignore` se `public/css/*` de vendor é versionado — hoje só `public/css/kit` está em `CAMINHOS_DO_KIT`).
- `app/Console/Commands/KitUpdate.php` → `CAMINHOS_DO_KIT` ganha `'config/remember-table.php'`.
- READMEs: `Pacotes de produção` 59 → 61, `Pacotes de desenvolvimento` 20 → 21, `Migrations` 63 → 64 (pt e en); `README.md:311` e `README.en.md:311` "os 77 pacotes instalados" → 80.
- **Atende**: RQ-02, P-06
- **Logs**: nenhum.

### 2. PHPStan entende macros

> Skills: `laravel-best-practices`

- **Path**: `phpstan.neon` → `includes:` ganha `- vendor/happenv-com/filament-phpstan-macros/extension.neon`, com comentário: por que manual (sem `extension-installer`), o que passa a ser analisado (`infiniteScroll()` no passo 7) e o que **não** (`simpleLightbox()`, registrado no `boot(Panel)`, fora da análise — a exceção dele fica).
- `tests/Kit/QualidadeDeCodigoTest.php`: o `[CT-06]` (inventário em 3) e o `[CT-04]` continuam verdes sem edição; o `04` deriva o CT que prova o include e a ausência de exceção para o macro novo.
- Verificação: `composer types:check` verde com `$table->infiniteScroll(...)` chamado direto (passo 7).
- **Atende**: RQ-02, P-05
- **Logs**: nenhum.

### 3. Config e `.env.example`

> Skills: `laravel-best-practices`

- **Path**: `config/kit.php`, bloco `'tabelas'`: `'carregamento' => CarregamentoDaTabela::coagir(env('KIT_TABELA_CARREGAMENTO'))->value` e `'visoes_salvas' => BooleanoDoEnv::comPadrao(env('KIT_TABELA_VISOES_SALVAS'), false)`; comentário do bloco atualizado (seis chaves, não quatro; o compartilhamento mora em `config/remember-table.php` e por quê).
- `.env.example`, bloco *Defaults de toda tabela*: as três chaves comentadas com o default.
- **Atende**: RQ-04, RQ-05, P-02
- **Logs**: nenhum.

### 4. Enum `CarregamentoDaTabela`

> Skills: `laravel-best-practices`

- **Path**: `app/Support/CarregamentoDaTabela.php`
- `enum CarregamentoDaTabela: string implements HasLabel` — `Paginacao = 'paginacao'`, `Rolagem = 'rolagem'`, `Botao = 'botao'`; `getLabel()` (pt-BR, uma frase por opção), `opcoes()` (array cru, pelo mesmo motivo de `DensidadeDoLayout::opcoes()`), `deConfig()` (lê `config('kit.tabelas.carregamento')`), `coagir(mixed): self` (`tryFrom` ?? `padrao()`), `padrao(): self` (`Paginacao`), `modoDoPacote(): ?InfiniteScrollMode` (`null` em `Paginacao`, `Scroll`, `Button`).
- Docblock: por que três valores e não toggle (D1), por que a coerção fica no consumo (mesma lição da densidade).
- **Atende**: RQ-04, RQ-05, P-04
- **Logs**: nenhum (D1 e o precedente da densidade).

### 5. Settings: propriedades, mapa e migration

> Skills: `laravel-best-practices`

- **Path**: `app/Settings/ConfiguracoesDoKit.php` — no bloco `// Tabelas`: `public string $tabela_carregamento;`, `public bool $visoes_de_tabela;`, `public bool $visoes_de_tabela_compartilhadas;` com docblocks (lidas por request: a primeira em `configuraTable()`, a segunda no trait do kit, a terceira pelo próprio pacote).
- `mapaDeConfiguracao()`: `'tabela_carregamento' => 'kit.tabelas.carregamento'`, `'visoes_de_tabela' => 'kit.tabelas.visoes_salvas'`, `'visoes_de_tabela_compartilhadas' => 'remember-table.allow_public_views'` — com o comentário de que a terceira aponta para config de pacote (D5), como as de e-mail.
- `database/settings/2026_10_14_200000_add_tabelas_rodada_3_to_kit_settings.php`: `add()` das três com os defaults de `config()`, `delete()` no `down()`, `limparCacheDoRepositorio()` nos dois, docblock no molde do dock.
- Nenhuma entra em `encrypted()`.
- **Atende**: RQ-04, RQ-05, P-02
- **Logs**: nenhum (a trilha é do listener existente).

### 6. Aba *Tabelas* da tela de configurações

> Skills: `filament-development`

- **Path**: `app/Filament/Admin/Pages/ConfiguracoesDoKit.php` → `abaTabelas()`: `Select::make('tabela_carregamento')->options(CarregamentoDaTabela::opcoes())->selectablePlaceholder(false)->required()` com helper "Rolagem e botão carregam um bloco por vez do tamanho de *Linhas por página*; o seletor de registros por página some"; `Toggle::make('visoes_de_tabela')->live()` com helper "Botão *Visões* nas listagens do kit: filtros, busca, ordenação, agrupamento e colunas gravados com nome, por usuário e por organização. Desligar esconde as visões sem apagá-las" (P-03); `Toggle::make('visoes_de_tabela_compartilhadas')->visible(fn (Get $get) => (bool) $get('visoes_de_tabela'))` com helper "Permite marcar *Compartilhar com todos* ao salvar: a visão passa a aparecer para todo usuário do painel (na organização, para os membros dela)".
- `mutateFormDataBeforeFill()`: `$data['tabela_carregamento'] = CarregamentoDaTabela::coagir(...)->value;` ao lado da densidade — o ilegível gravado nunca chega ao Select, que tem `Rule::in()` das opções e travaria a tela inteira *(alterado em 2026-10-14: P-24, revisão adversarial ADV-28)*.
- `mutateFormDataBeforeSave()` — método NOVO na Page (a densidade só coage no fill; a Page não tinha o hook de save): `$data['tabela_carregamento'] = CarregamentoDaTabela::coagir(...)->value;` (P-11/P-24, segunda coerção, na saída) e `$data['visoes_de_tabela_compartilhadas'] = (bool) ($data['visoes_de_tabela'] ?? false) && (bool) ($data['visoes_de_tabela_compartilhadas'] ?? false);` (P-15 — o toggle oculto não é desidratado, então a chave é escrita aqui) *(alterado em 2026-10-14: P-15; e CT-15 da onda 1, que achou a regra no lugar errado)*. O `Select` leva `->rule('string')`: array no payload passava pelo `in()` das opções e chegava ao settings como `null` *(alterado em 2026-10-14: CT-14)*.
- `ConfiguracoesDoKitTelaTest` já tem o caso "grava mesmo com valor configurado fora da lista" por dataset — o `04` decide se o campo novo entra nele.
- **Atende**: RQ-04, RQ-05, P-02
- **Logs**: nenhum.

### 7. Carregamento contínuo em toda tabela

> Skills: `filament-development`

- **Path**: `app/Providers/Concerns/ConfiguraFilamentGlobal.php` → novo `aplicaCarregamento(Table $table): Table`, chamado em `configuraTable()` depois de `extremePaginationLinks()` e antes de `aplicaMacrosDeColuna()`:
  ```php
  $modo = CarregamentoDaTabela::deConfig()->modoDoPacote();

  return $modo === null
      ? $table
      : $table->infiniteScroll(perPage: (int) config('kit.tabelas.paginacao', 10), mode: $modo);
  ```
- Chamada **direta** do macro (D8), com docblock: o que o pacote fixa na tabela (`vendor/asignua/filament-infinite-scroll/src/InfiniteScroll.php:configure():17-53`, `:48-52`), por que o bloco é "Linhas por página" (D2), por que não há `hasMacro()` (D7/D8) e o que acontece numa tabela sem `LengthAwarePaginator`.
- Docblock do trait: "Quatro destes defaults" → "Sete", listando as três chaves novas.
- **Atende**: RQ-04, RQ-05, P-04
- **Logs**: nenhum.

### 8. Permissão `Usar:VisoesDeTabela`

> Skills: `filament-development`

- **Path**: `config/filament-shield.php` → `custom_permissions`: `'usar:visoes_de_tabela' => 'Salvar e gerenciar visões de tabela'` (comentário: quem consulta é o trait do kit; aplicar visão não exige) *(alterado em 2026-10-15, QA-02 do gate: a grafia `usar:visoesdetabela` gerava `Usar:Visoesdetabela` — o Shield capitaliza por segmento `_`; ver `03`, seção 8)*.
- `database/seeders/PapeisSeeder.php`: `paineisDasPermissoesCustomizadas()` ganha `'Usar:VisoesDeTabela' => ['app', 'admin', 'infra']`; o `run()` subtrai do `panel_user` ao lado de `Usar:Dock` (P-01); `admin_app` (ramo de tenancy) a recebe pela matriz do `app`.
- `tests/Kit/PermissoesDeAcoesTest.php` **não** ganha linha: `inventarioDeAutorizacao()` só aceita Action que a varredura de `Action::make(` em `app/Filament/**` encontra, e as quatro moram no vendor — linha sem ocorrência vira órfã e deixa o caso vermelho *(alterado em 2026-10-14: Ponytail/revisão profunda)*. A prova de que as quatro consultam a permissão é CT próprio do `04` (Action negada sem `Usar:VisoesDeTabela`).
- Ressemear: `ShieldPermissionsSeeder` e `PapeisSeeder`.
- **Atende**: P-01, RQ-04
- **Logs**: nenhum.

### 9. Trait `VisoesDeTabela` e as dez listagens

> Skills: `filament-development`

- **Path**: `app/Filament/Concerns/VisoesDeTabela.php`
  ```php
  trait VisoesDeTabela
  {
      use HasRememberTable {
          bootedHasRememberTable as protected bootedHasRememberTableDoPacote;
          getTableViews as protected getTableViewsDoPacote;
          getTableViewActions as protected getTableViewActionsDoPacote;
          saveTableViewAction as protected saveTableViewActionDoPacote;
          updateTableViewAction as protected updateTableViewActionDoPacote;
          manageTableViewsAction as protected manageTableViewsActionDoPacote;
          resetTableViewAction as protected resetTableViewActionDoPacote;
      }

      public const string PERMISSAO_DAS_VISOES = 'Usar:VisoesDeTabela';

      public static function visoesHabilitadas(): bool { return (bool) config('kit.tabelas.visoes_salvas', false); }

      public function bootedHasRememberTable(): void { if (self::visoesHabilitadas()) { $this->bootedHasRememberTableDoPacote(); } }

      public function getTableViews(): Collection { return self::visoesHabilitadas() ? $this->getTableViewsDoPacote() : new Collection; }

      public function getTableViewActions(): array { return self::visoesHabilitadas() ? [...grupo com dropdownWidth(Width::Small)...] : []; }

      public function saveTableViewAction(): Action
      {
          return $this->saveTableViewActionDoPacote()
              ->hidden(static fn (): bool => ! self::visoesHabilitadas())
              ->authorize(self::PERMISSAO_DAS_VISOES);
      }
      // idem update, manage, reset
  }
  ```
  *(alterado em 2026-10-15, QA-02 do gate — o trecho passa a ser o do código entregue: aliases `protected` (P-30/RD-02: alias público vira método `$wire`), `->hidden()` nas quatro Actions (P-29/RD-01: `visible()` substituiria a condição do pacote que esconde *Atualizar* e *Gerenciar*), os nomes `PERMISSAO_DAS_VISOES`/`visoesHabilitadas()` e o `dropdownWidth(Width::Small)` no grupo — `app/Filament/Concerns/VisoesDeTabela.php:saveTableViewAction():131`)*
  - O método do trait externo **vence** o do trait interno (provado no step 3 com um script: `kit→pacote`), e o Livewire resolve o hook `booted{Trait}` por `class_uses_recursive` + nome do método, então o `bootedHasRememberTable` do kit é o chamado.
  - Docblock: D3, D4, D6, P-03; a fronteira que o pacote já aplica (`visibleTo`, `ownedBy`, `forTenant`) com as citações do vendor; por que `applyTableView` continua sem permissão.
- Nas 10 Pages `List*`: `use VisoesDeTabela;` e `...$this->getTableViewActions()` no **início** de `getHeaderActions()` (o botão *Visões* fica antes de *Criar*). `ListRoles` mantém o `getActions()` dele e recebe o spread ali: `getHeaderActions()` do Filament delega a `getActions()` por default (`vendor/filament/filament/src/Pages/Concerns/InteractsWithHeaderActions.php:getHeaderActions():55-58`) *(alterado em 2026-10-14: revisão profunda — a troca de nome era diff sem efeito)*.
- **Atende**: RQ-04, RQ-05, P-01, P-03
- **Logs**: nenhum.

### 10. Traduções pt_BR do pacote

> Skills: `laravel-best-practices`

- **Path**: `lang/vendor/filament-remember-table/pt_BR/remember-table.php` — todas as chaves de `vendor/sehsah/filament-remember-table/resources/lang/en/remember-table.php` traduzidas (botão *Visões*, `:name (modificada)`, ações, modais, campos, badges *Padrão*/*Compartilhada*/*Pré-definida*, notificações).
- A folha `pt_BR` do `infinite-scroll` já vem no pacote.
- **Atende**: RQ-06 (D9)
- **Logs**: nenhum.

### 11. Testes de backend (`04`)

> Skills: `pest-testing`

- Escritos pelo Codex sob o contrato do `executor-ct` (RQ-03), a partir do Gherkin do `04`; arquivos em `tests/Kit/` e `tests/Tenancy/` conforme as costuras do `04`.
- **Atende**: RQ-03 (quem escreve é o Codex), RQ-04, RQ-05 — e, pelos CT do `04`, toda RQ/P-nn do mapa de regras.

### 12. Testes de navegador (`05`), se o `04` tiver costura `browser`

> Skills: `pest-testing`

- Escritos pelo Codex sob o contrato do `executor-ctb` (RQ-03).
- **Atende**: RQ-03, RQ-07 (tema escuro) e o cenário de rolagem (P-10).

### 13. Documentação

> Skills: —

- **Path** (pt e en):
  - `docs/{pt,en}/recursos/visoes-de-tabela-e-carregamento.md` — página nova: o que são visões salvas (para quem, o que gravam, padrão, compartilhar, `?view=`, organização), carregamento contínuo (modos, bloco, teto, o que some), configuração (as três chaves + env), tema escuro, custo; capturas e GIF do passo 14.
  - `docs/{pt,en}/recursos/configuracoes-do-kit.md` — linha *Tabelas* da tabela de abas.
  - `docs/{pt,en}/recursos/configuracao-global-filament.md` — a tabela "Toda tabela nasce com" ganha a linha do carregamento; "Quatro desses defaults" → "Sete".
  - `docs/{pt,en}/referencia/pacotes-instalados.md` — três linhas (duas em *Tabelas*, uma na seção de desenvolvimento) e o parágrafo "Avaliados e não adotados" com `list-view` e `saved-views` (P-06).
  - `docs/{pt,en}/referencia/estudo-advanced-tables.md` — nota: o nível (b) foi fechado pelo `remember-table` nesta rodada.
  - `docs/{pt,en}/referencia/qualidade-de-codigo.md` — uma frase: macros de componente Filament são tipados pela extensão; a exceção do `simpleLightbox()` fica e por quê.
  - `docs/{pt,en}/operacao/depois-de-criar-resources.md` — ao lado do `HasResizableColumn`, o `use VisoesDeTabela` + `getTableViewActions()` para Resource novo (achado da varredura da classe irmã).
  - `site/sidebar.json` e `site/redirects.json` — a página nova.
  - `wikis/pacotes.md`, `wikis/pacotes-candidatos.md` — linhas dos três que entraram e dos dois que não.
  - `CHANGELOG.md` → `[Unreleased]`: *Adicionado* (três pacotes, três chaves, permissão, traduções, página nova) e *Validação*.
- Cada frase nova cita (no `03`, não na doc) a `RQ`/`P-nn` de origem.
- **Atende**: RQ-01, RQ-02, RQ-06, P-06
- **Logs**: nenhum.

### 14. Capturas e GIF, nos dois temas

> Skills: `pest-testing`

- **Path**: `tests/BrowserTenancy/CapturaDeArteTest.php` (cenários `->group('browser', 'art')`), `app/Console/Commands/KitArte.php` (`IMAGENS` e `CLIPES`).
- Visões salvas em `/admin/users` com três visões gravadas (uma padrão, uma compartilhada por outro usuário): quadros `visoes-1-fechada`, `visoes-2-aberta` (dropdown), `visoes-3-salvar` (modal), `visoes-4-gerenciar` (repeater) → clipe `visoes-de-tabela`; o mesmo em tema escuro → `visoes-de-tabela-escuro`. Imagens publicadas: `visoes-aberta`, `visoes-gerenciar` (+ `-escuro`).
- Carregamento contínuo em `/admin/users` com 30 usuários: `tabela-rolagem` (rodapé "Mostrando 10 de 31" + indicador) e o clipe `carregamento-continuo` no modo botão (quadros `tabela-carregar-mais`, `-2`, `-3`: 10, 20 e 30 de 31), nos DOIS temas (`tabela-rolagem-escuro`, `carregamento-continuo-escuro`) *(alterado em 2026-10-14: o corte do Ponytail — só uma captura escura do contínuo, sem clipe — foi revertido pelo CT-51 do `04` (ADV-38, RQ-07): toda captura da feature tem o par escuro e o contínuo tem clipe animado)*.
- `admin-configuracoes-tabelas` (aba *Tabelas* com os campos novos).
- Regras de `.ai/rules/testes-browser.md`: tema declarado por visita, arranjo de painel por cenário, cenários escuros no fim do arquivo.
- `composer art` publica em `art/` e `art/thumbs/`.
- **Atende**: RQ-07
- **Logs**: nenhum.

### 15. Branch, commits e PR

- Branch `feat/estudo-de-pacotes-rodada-3` (criada no step 1). Commits por passo (gitmoji). PR para `main` **só depois** do veredito do quality gate, sem merge (RQ-08).
- **Atende**: RQ-08

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação: reutilizar (`DensidadeDoLayout`, a migration do dock, `BooleanoDoEnv`), stdlib antes de custom, nativo antes de dependência, mínimo que funciona. Atalhos deliberados com `ponytail:`. `/ponytail:ponytail-review` no diff ao fim.
>
> **Caveman** na conversa; os arquivos da wiki, código, commits e PR em prosa normal.
>
> **Model novo**: nenhum do kit (o `TableView` é do pacote e declara a própria tabela por config).
>
> **Baseline antes do primeiro commit**: `Kit` e `Tenancy` em paralelo sobre a árvore de `main`, falhas por nome no `03`.

## Mapeamentos

| Propriedade do Settings | Chave de config | Lida por | Default |
|---|---|---|---|
| `tabela_carregamento` | `kit.tabelas.carregamento` | `ConfiguraFilamentGlobal::aplicaCarregamento()` (por request) | `paginacao` |
| `visoes_de_tabela` | `kit.tabelas.visoes_salvas` | `VisoesDeTabela::visoesHabilitadas()` (por request) *(alterado em 2026-10-15, QA-02)* | `false` |
| `visoes_de_tabela_compartilhadas` | `remember-table.allow_public_views` | `HasRememberTable` do pacote (por request) | `false` |

| Valor de `tabela_carregamento` | Rótulo | `InfiniteScrollMode` |
|---|---|---|
| `paginacao` | Paginação — as páginas numeradas de hoje | — |
| `rolagem` | Rolagem infinita — o próximo bloco carrega ao chegar ao fim | `Scroll` |
| `botao` | Botão "Carregar mais" — o próximo bloco carrega no clique | `Button` |

## Testes

> Ver `04-casos-de-teste.md` para os cenários de backend e `05-casos-de-teste-browser.md` para os de navegador.

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/filacheck --fix` (arquivos em `app/Filament`)
- [ ] `composer types:check` (PHPStan nível 8, com o macro chamado direto)
- [ ] `vendor/bin/pest --parallel --testsuite=Kit` e `--testsuite=Tenancy` (CTs de backend), comparados à baseline
- [ ] `vendor/bin/pest tests/Browser --filter=...` / `tests/BrowserTenancy --filter=...` (CT-B, se houver `05`)
- [ ] `pest --mutate --path=app/Support/CarregamentoDaTabela.php,app/Filament/Concerns/VisoesDeTabela.php` — score, duração, sobreviventes (via `pestw.cmd`)
- [ ] Custo medido: consultas da listagem com visões desligadas × ligadas; com paginação × rolagem (N acima da página)
- [ ] `/code-review high main...HEAD` + passe de eixos (step 9)
- [ ] `composer art` com as capturas novas conferidas visualmente (barra lateral, tema)

## Commits
- `:sparkles: feat(tabelas): visoes salvas, carregamento continuo e phpstan-macros com interruptores no settings`
- `:memo: docs(tabelas): pagina de visoes e carregamento, pacotes instalados, changelog`
- `:camera_flash: art(tabelas): capturas e gifs das visoes e do carregamento, claro e escuro`
- `:memo: wiki(estudo-de-pacotes-rodada-3): requisito, plano, ADR, testes e progresso`

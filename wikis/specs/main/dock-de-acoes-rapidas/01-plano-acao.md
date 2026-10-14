# Plano de Ação — Dock de ações rápidas (favoritos do usuário)

> Requisito: `00-requisito.md` (com o Adendo 1)

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: —
- **Motivo**: —
- **Toca infra compartilhada?**: sim → matriz de papéis (`PapeisSeeder`, `config/filament-shield.php` `custom_permissions`), settings da instalação (`ConfiguracoesDoKit` + migration de settings), tabela `users` (migration nova), os três `PanelProvider`, inventário `tests/Kit/PermissoesDeAcoesTest.php`. Força regressão contra as features que consomem essa infra: `permissoes-de-telas-e-acoes`, `dashboard-dinamico-nos-paineis`, `settings-do-kit`, `hub-de-navegacao-em-cards`

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Análise completa do pacote | — | entregue no chat de 2026-10-13 e destilada em `## Análise dos Arquivos Existentes` e no `02`; não é código |
| RQ-02 | Dock oferece atalhos para URLs da aplicação | 1, 8, 9 | — |
| RQ-03 | Origem dos atalhos | — | substituída por RQ-10 (Adendo 1) |
| RQ-04 | Atalho só aparece para quem tem a permissão do destino | 7, 8 | — |
| RQ-05 | Permissão exclusiva ou por perfil | — | substituída por RQ-13 (Adendo 1) |
| RQ-06 | Escolher o painel do botão | — | substituída por RQ-14 (Adendo 1) |
| RQ-07 | Favorito de qualquer painel | — | substituída por RQ-15 (Adendo 1) |
| RQ-08 | Layout escolhível entre os do pacote | 3, 4, 8 | — |
| RQ-09 | Onde se escolhe o layout | — | substituída por RQ-16 (Adendo 1) |
| RQ-10 | Atalhos são favoritos do usuário | 6, 7, 8 | — |
| RQ-11 | Favorito só é destino do catálogo | 7, 8 | — |
| RQ-12 | Catálogo gerado automaticamente | 7 | — |
| RQ-13 | Permissão exclusiva concedida a papéis | 5, 8 | — |
| RQ-14 | Interruptor por painel, nasce desligado | 3, 4, 7, 9 | — |
| RQ-15 | Favorito de um painel aparece em todo painel com dock, enquanto houver acesso | 7, 8 | — |
| RQ-16 | Favorito do `/app` guarda a organização | 6, 7, 8 | — |
| RQ-17 | Settings define layout e estilo padrão; usuário sobrescreve o layout | 3, 4, 6, 8 | — |
| RQ-18 | Teto de 10 favoritos | 7, 8 | — |
| RQ-19 | Favorito sem permissão some e continua gravado | 7 | — |
| RQ-20 | Gerenciar favoritos em slideover | 8 | — |
| RQ-21 | Opção do dock adiciona a página atual | 7, 8 | — |
| RQ-23 | "Adicionar esta página" só em destino do catálogo | 7, 8 | Adendo 2 (Q11) |
| RQ-24 | Permissão padrão: administração sim, `panel_user` não | 5 | Adendo 2 (Q12) |
| RQ-25 | Catálogo do `/app` só da organização aberta, só dentro do `/app` | 7, 8 | Adendo 2 (Q13) |
| RQ-26 | Acima de 10: recusa com aviso, nada muda | 7, 8 | Adendo 3 (Q14) |
| RQ-27 | Oculto: fora do slideover, preservado ao salvar, conta no teto; inválido descartado | 7, 8 | Adendo 3 (Q15) |
| RQ-28 | Sem duplicata; "adicionar" some se a página já é favorita | 7, 8 | Adendo 3 (Q16) |
| RQ-29 | Envio com chave fora do catálogo recusado por inteiro | 7 | Adendo 3 (Q17) |
| RQ-30 | Ordem do usuário, reordenável | 7, 8 | Adendo 3 (Q18) |
| RQ-31 | Rótulo com painel (e organização) | 7 | Adendo 3 (Q19) |
| RQ-32 | Teto conta toda entrada gravada | 7, 8 | Adendo 4 (Q21) |
| RQ-33 | Página adicionada entra no fim | 7 | Adendo 4 (Q22) |
| RQ-34 | Favorito de outra organização entre os escolhidos do slideover | — | substituída por RQ-35 (Adendo 5) |
| RQ-35 | Todo favorito gravado abrível entre os escolhidos, em qualquer slideover | 7, 8 | Adendo 5 (Q24) |
| RQ-22 | Settings eficiente: zero consulta extra por request para decidir se e como o dock aparece | 3, 4, 7 | — |

## Objetivo

Instalar o `hoceineel/filament-quick-action-dock` nos três painéis como um **dock de favoritos por usuário**: cada usuário com a permissão `Usar:Dock` fixa até 10 telas que já pode abrir — de qualquer painel —, gerencia a lista num slideover e adiciona a página atual com um clique. A instalação decide em quais painéis o dock aparece e o layout/estilo padrão; o usuário pode trocar só o layout.

A leitura de tudo o que decide **se** e **como** o dock aparece não toca o banco no request: o settings chega pela config já sobreposta no boot, a permissão pelo cache do spatie e os favoritos na própria linha do usuário autenticado.

## Contexto

O kit já tem três formas de navegar (barra lateral, hub de cards, Spotlight `mod+k`), todas **por painel** e todas iguais para todo mundo. Falta um atalho **pessoal**, que atravesse painéis — quem administra no `/admin` e opera no `/app` hoje troca de painel e navega de novo a cada ida e volta.

## Análise dos Arquivos Existentes

### O pacote (`hoceineel/filament-quick-action-dock` v0.2.0, ainda não instalado)

Lido no GitHub, tag `v0.2.0` (vendor ausente — a reconciliação reconfere em `vendor/` depois do passo 1):

- `src/QuickActionDockPlugin.php:register()` pendura o dock no render hook `PanelsRenderHook::BODY_END` do painel; `renderDock()` só monta o componente se o plugin está registrado no painel corrente **e** `shouldRender()` (= `isVisible() && isVisibleForCurrentUser() && ! isHiddenOnCurrentPage()`)
- `getLayout()`, `getStyle()`, `getGroups()`/`getActions()` passam por `evaluate()`: Closure recalculada a cada chamada — a preferência do usuário e o settings podem ser lidos por request
- `src/Livewire/QuickActionDock.php`: `getPlugin()` chama `QuickActionDockPlugin::get()`, que resolve o plugin pelo **painel corrente**; `hydrate()` e `mount()` chamam `cacheDockActions()`, que avalia `getActions()` — **toda requisição Livewire do dock reavalia a Closure de actions**; e `getDockGroups()` a avalia de novo no render. Sem memo, a montagem dos favoritos roda 2× por request
- Propriedades públicas do componente (`routeName`, `routeParameters`, `path`) são `#[Locked]`
- Enums: `DockLayout` (`speed-dial`, `bar`), `DockStyle` (`native`, `outline`, `contrast`, `tinted`), `DockPosition`
- `DockContext::current()->getPageClass()` devolve a classe da página onde o dock foi montado — base da opção "adicionar esta página"

### `app/Settings/ConfiguracoesDoKit.php`

- `ConfiguracoesDoKit::mapaDeConfiguracao()` liga propriedade → chave de `config()`; `aplicarNaConfig()` sobrepõe a config com o banco **uma vez por boot**. Consumidor que lê `config()` por request recebe o valor do banco sem consulta extra (`.ai/rules/settings.md`)
- Precedente direto: `dashboard_dinamico_paineis` (array de painéis) → `kit.dashboard_dinamico.paineis`, lido por `App\Support\DashboardDinamico::habilitadoPara()`
- Propriedade nova = três lugares: propriedade, linha no mapa, migration de settings **nova**. Com `SETTINGS_CACHE_ENABLED`, propriedade nova exige limpar o cache serializado — precedente `database/settings/2026_10_09_203319_clear_kit_settings_cache_after_schema_update.php`

### `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`

- Aba `Kit` (`Tab::make('Kit')`) já hospeda `hub_de_navegacao` e o `Select::make('dashboard_dinamico_paineis')->multiple()->options(Paineis::opcoes())` — o dock entra na mesma aba, mesmo padrão

### Permissões

- `config/filament-shield.php` `custom_permissions` (`'manage:dashboard'` é o precedente de permissão válida nos três painéis)
- `database/seeders/PapeisSeeder.php:paineisDasPermissoesCustomizadas()` — mapa obrigatório; chave sem entrada não vai a papel nenhum (fail-closed). `Manage:Dashboard` é subtraída do `panel_user` no `run()` — mesmo tratamento para `Usar:Dock` (RQ-24)
- `App\Support\ContextoDePapeis::em()` fixa o `team_id` do spatie e restaura no `finally`
- `App\Filament\Concerns\ExigePermissaoDaTela` herda o **fail-open** do `HasPageShield`: quando o painel corrente não é o da Page, `canAccess()` **libera**. Avaliar um destino de outro painel sem trocar o painel corrente devolveria `true` — ver ADR-01

### Catálogo

- `App\Filament\Concerns\DescobreCardsDoPainel::cardsDoPainel()` filtra `[...getResources(), ...getPages()]` por `canAccess()` e `shouldRegisterNavigation()` no painel **corrente**. O catálogo do dock aplica o mesmo filtro, por painel

### `app/Models/User.php`

- `$fillable` = `name`, `email`, `password`, `avatar_url`; estado sensível fica fora e é escrito por `forceFill` em método do model (`casts()` documenta o regime)
- `canAccessPanel()` e `canAccessTenant()` decidem entrada no painel e na organização

### Elementos flutuantes já no `BODY_END`

- `resources/views/filament/voltar-ao-topo.blade.php` — canto inferior direito nos três painéis
- `assistente-chat-widget` — canto inferior direito no `/app` (`fixed bottom-6 right-6`)
- O dock no padrão do pacote (`BottomEnd`) cairia em cima dos dois → D5

### APIs do Filament usadas (conferidas em `vendor/`)

- `vendor/filament/filament/src/Resources/Resource/Concerns/CanGenerateUrls.php:getUrl():16` — `getUrl(?string $name, array $parameters, bool $isAbsolute, ?string $panel, ?Model $tenant, …)`
- `vendor/filament/filament/src/Pages/Page.php:getUrl():91` — `getUrl(array $parameters, bool $isAbsolute, ?string $panel, ?Model $tenant, …)`
- `vendor/filament/filament/src/FilamentManager.php:setCurrentPanel():885`, `:setTenant():899`, `:getTenant():448`
- `vendor/filament/forms/src/Components/Select.php:reorderable():524` — `Select` múltiplo reordenável (RQ-30)

### Testes de inventário (listas paralelas)

- `tests/Kit/PermissoesDeAcoesTest.php:inventarioDeAutorizacao():541` varre `app/Filament` e `app/Providers/Filament` e fica vermelho com Action nova sem declaração de autorização
- `tests/Kit/PermissoesDeAcoesTest.php` CT-15 lista por nome as permissões novas de Action; CT-19 cruza `custom_permissions` × `paineisDasPermissoesCustomizadas()`
- `tests/Kit/KitInfoTest.php:toHaveCount():189` — âncora do número de propriedades do settings (63 hoje)

## Decisões de Desenho

| # | Decisão | Pergunta | Portão que falta | Quem decidiu, data |
|---|---|---|---|---|
| D1 | Adicionar a dependência `hoceineel/filament-quick-action-dock:^0.2` | Q9 | difícil de reverter (remover é um `composer remove` + três linhas) | solicitante, 2026-10-13 (Adendo 1) |
| D2 | Gerenciar favoritos num slideover aberto por um item fixo do dock | Q10 | difícil de reverter | solicitante, 2026-10-13 (Adendo 1) |
| D3 | Plugin registrado **sempre** nos três painéis; quem decide se aparece é `visible()` por request (settings + permissão). `QuickActionDockPlugin::get()` resolve pelo painel corrente — painel sem o plugin estoura no update Livewire (`.ai/rules/providers-filament.md`) | — | surpreendente (é a rule do projeto) | sessão, 2026-10-13 — convenção existente |
| D4 | Settings entra pelo `mapaDeConfiguracao()` → `kit.dock.*`; o dock só lê `config()`. Nenhum `app(ConfiguracoesDoKit::class)` no caminho do request | RQ-22 | surpreendente (é o padrão do kit) | sessão, 2026-10-13 — convenção existente |
| D5 | Posição padrão `DockPosition::BottomCenter`: os cantos inferiores direitos já têm "voltar ao topo" e o chat | — | difícil de reverter | sessão, 2026-10-13 |
| D6 | A chave de opção do catálogo é `{painel}\|{FQCN}\|{organização ou vazio}`; a gravação só aceita chave presente no catálogo recalculado no servidor | RQ-11 | surpreendente | sessão, 2026-10-13 |
| D7 | No slideover, o catálogo oferece destinos do `/app` **só da organização aberta** (quando aberto no `/app`); fora do `/app`, só os favoritos já gravados aparecem para o `/app`. Avaliar destinos de N organizações custa N trocas de contexto por abertura | Q13 → RQ-25 | difícil de reverter | solicitante, 2026-10-13 (Adendo 2) |
| D8 | Memo da montagem dos favoritos por request (`WeakMap` com a `Request`), como `App\Support\CabecalhoDoPainel` — o componente avalia as actions 2× por request | RQ-22 | surpreendente | sessão, 2026-10-13 — convenção existente |
| D9 | RQ-22 é provada atribuindo cada consulta à tabela `settings` pela pilha (`DB::listen()` + `debug_backtrace()`), não só pelo total do request | Q20 (devolvida pela derivação) | difícil de reverter | sessão, 2026-10-13 — sem alternativa que separe a consulta do dock da de outro recurso |

## Autorização

- **Permissão nova**: `Usar:Dock` (custom permission `usar:dock`), válida nos três painéis via `PapeisSeeder::paineisDasPermissoesCustomizadas()`; subtraída do `panel_user` (RQ-24)
- **Dock**: `QuickActionDockPlugin::authorize('Usar:Dock')` — sem ela o componente não renderiza nem registra actions (vendor: `shouldRender()`)
- **Cada favorito**: `DockDeAtalhos::podeAbrir()` — painel do favorito existe, `canAccessPanel()`, no `/app` `canAccessTenant()`, destino pertence ao catálogo do painel e `canAccess()` do destino, avaliado **no painel e no contexto de papéis do favorito** (ADR-01). Qualquer exceção → `false`. Exceção (P-04): favorito do painel **e** da organização abertos não troca contexto nem confere `canAccessPanel()`/`canAccessTenant()` — o middleware persistente do painel já fixou painel, tenant e `team_id`; a consulta de acesso a painel usa `User::motivoDaNegativaDoPainel()`, sem log (RD-05) *(alterado em 2026-10-14: QA-02, ciclo 1 do quality gate)*
- **Gravação**: só pelo slideover e pela opção "adicionar esta página", ambas com `->authorize('Usar:Dock')`; os valores chegam do cliente e são filtrados contra o catálogo do servidor
- **Policies / Gates / Middleware / Guards**: nenhum novo

## Rotas

Nenhuma nova. O dock usa o endpoint `livewire/update` existente.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Dock (`quick-action-dock`) | Livewire (vendor) | todas as páginas autenticadas dos painéis com dock ligado | abrir/fechar, clicar num favorito (navega), `mod+.` | Sim |
| Item "Adicionar esta página" | Action do dock | idem | grava a página corrente como favorito | Não (action) |
| Slideover "Gerenciar atalhos" | Action do dock com `schema` + `slideOver()` | idem | escolher/ordenar até 10 destinos, escolher layout próprio | Não (action) |
| Seção "Dock de atalhos" na aba Kit | Filament (Page de settings) | `/admin/configuracoes-da-aplicacao` (rota da Page existente) | escolher painéis, layout e estilo padrão | Não |

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_DOCK_PAINEIS` | `''` (nenhum) | lista separada por vírgula dos ids de painel com dock; semeia `dock_paineis` |

## Eventos / Listeners / Observers

- Nenhum novo. A gravação do settings já é auditada por `App\Listeners\AuditarConfiguracoesDoKit`

## Jobs / Queues

- Nenhum

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | O render da página monta o dock (1 render a mais por página); cada abertura do slideover e cada "adicionar" é 1 `livewire/update`. Clicar num favorito é navegação comum |
| O que é adiado, e por qual gatilho? | O catálogo completo só é calculado ao abrir o slideover (dentro do `schema` da action), nunca no render |
| O que é memoizado **por request**? | A lista de favoritos visíveis do usuário (`DockDeAtalhos`, `WeakMap` por `Request`) — cobre as 2 avaliações do componente no mesmo request; não alcança o request seguinte |
| O que é cacheado **entre** requests? | Settings: `aplicarNaConfig()` no boot (com `SETTINGS_CACHE_ENABLED`, cache do spatie). Permissões: cache do spatie/permission. Nada novo |
| Custo do caminho principal | Decidir se o dock aparece: 0 query (config + permissão em cache). Montar favoritos: 0 query de favorito (coluna da linha do usuário autenticado) + as consultas que o `canAccess()` de cada destino já faz (≤ 10 destinos, avaliações em cache do spatie). Slideover: 1 avaliação de `canAccess()` por destino do catálogo |

## Impacto em Features Existentes

- **Permissões de telas e ações**: chave custom nova; inventário de autorização e sweep de permissões precisam da linha nova
- **Settings do kit**: três propriedades novas + migration; a suíte que guarda o mapa (`tests/Kit/…`) precisa enxergar as chaves
- **Hub de cards / Spotlight**: nenhum — o dock não altera o concern, só repete o filtro
- **Voltar ao topo / chat**: posição D5 evita sobreposição
- **Tela de papéis**: ganha o checkbox `Usar:Dock`

## Rollback

- **Migration down**: `users` remove `dock_atalhos` e `dock_layout`; settings `delete` das três propriedades e limpa o cache
- **Feature flag**: `dock_paineis` vazio desliga o dock em todos os painéis sem deploy
- **Reversão de dados**: favoritos se perdem com o `down` (preferência pessoal, sem valor de negócio)

## Dependências

- **Composer**: `hoceineel/filament-quick-action-dock` `^0.2` (PHP ^8.3, Filament ^4|^5) — aprovada em Q9
- **NPM**: nenhuma (assets pré-compilados via `php artisan filament:assets`)

## Riscos

- **Pacote 0.x com 5 dias**: API pode quebrar em `0.3` → constraint `^0.2` (não sobe para 0.3 sozinho) e o CT de render por painel acusa a quebra
- **Fail-open do `HasPageShield` fora do painel corrente**: mitigado por ADR-01 e por CT que prova destino de outro painel negado
- **Troca de contexto que vaza** (painel/tenant/team não restaurados após avaliar um favorito): `finally` em todas as trocas + CT que confere o painel/tenant/team depois da montagem
- **Catálogo do `/app` com muitas organizações**: D7

## Channel de Log da Feature

### Verificação de Channel Existente

- `config/logging.php` tem `ai`, `tenancy`, `autenticacao`, `configuracoes`; nenhum de navegação/favoritos

### Decisão

- Passo 2 cria o channel `dock-de-acoes-rapidas` (`daily`, `storage/logs/dock-de-acoes-rapidas.log`, `debug`, 14 dias)

## Estrutura de Implementação

### 1. Dependência

> Skills: `laravel-best-practices`

- `composer require hoceineel/filament-quick-action-dock:^0.2` e `php artisan filament:assets`
- Conferir em `vendor/hoceineel/filament-quick-action-dock/src/` as afirmações de `## Análise dos Arquivos Existentes` (citação com símbolo) antes do passo 7
- **Atende**: RQ-02
- **Logs**: nenhum (sem lógica)

### 2. Channel de log

- **Path**: `config/logging.php` — channel `dock-de-acoes-rapidas`
- **Atende**: RQ-04, RQ-11, RQ-18 (passos 7 e 8 registram nele)
- **Logs**: —

### 3. Config `kit.dock`

> Skills: `laravel-best-practices`

- **Path**: `config/kit.php` — bloco `'dock' => ['paineis' => [...], 'layout' => 'speed-dial', 'estilo' => 'native']`, `paineis` com o mesmo parse de `KIT_DASHBOARD_DINAMICO_PAINEIS`; `.env.example` ganha só `KIT_DOCK_PAINEIS` *(alterado em 2026-10-13: Ponytail #4 — layout/estilo sem env)*
- Diferença deliberada do dashboard dinâmico: **lista vazia = nenhum painel** (RQ-14, fail-closed)
- **Atende**: RQ-08, RQ-14, RQ-17, RQ-22
- **Logs**: nenhum

### 4. Settings

> Skills: `laravel-best-practices`, `filament-development`

- **Path**: `app/Settings/ConfiguracoesDoKit.php` — `public array $dock_paineis; public string $dock_layout; public string $dock_estilo;` + linhas `'dock_paineis' => 'kit.dock.paineis'`, `'dock_layout' => 'kit.dock.layout'`, `'dock_estilo' => 'kit.dock.estilo'` no `mapaDeConfiguracao()`
- **Path**: `database/settings/2026_10_14_030956_add_dock_to_kit_settings.php` *(alterado em 2026-10-14: nome gerado pelo artisan)* — `add()` semeado de `config('kit.dock.*')`; `up()` e `down()` limpam o cache do repositório (`SettingsCacheFactory`, como a migration de 2026-10-09)
- **Path**: `app/Filament/Admin/Pages/ConfiguracoesDoKit.php` — aba `Kit`, `Section` "Dock de atalhos": `Select::make('dock_paineis')->multiple()->options(Paineis::opcoes())` (helper: "Em branco = nenhum painel"), `Select::make('dock_layout')->options(...)->required()->in(...)` e `Select::make('dock_estilo')` com as opções dos enums do pacote em pt-BR
- **Atende**: RQ-08, RQ-14, RQ-17, RQ-22
- **Logs**: nenhum novo — a gravação já é auditada

### 5. Permissão `Usar:Dock`

> Skills: `laravel-best-practices`

- **Path**: `config/filament-shield.php` — `'usar:dock' => 'Usar o dock de atalhos'` em `custom_permissions`
- **Path**: `database/seeders/PapeisSeeder.php` — `'Usar:Dock' => ['app', 'admin', 'infra']` em `paineisDasPermissoesCustomizadas()`; subtrair do `panel_user` junto de `Manage:Dashboard` (RQ-24)
- **Atende**: RQ-13, RQ-24
- **Logs**: nenhum

### 6. Preferências do usuário

> Skills: `laravel-best-practices`

- **Path**: migration `2026_10_14_031048_add_dock_to_users_table` *(alterado em 2026-10-14: nome gerado pelo artisan)* — `dock_atalhos` `json` nullable, `dock_layout` `string(20)` nullable (ADR-02)
- `dock_atalhos` guarda `list<string>` com a própria chave D6 (`{painel}|{FQCN}|{organização ou vazio}`), na ordem do usuário — sem DTO *(alterado em 2026-10-13: Ponytail #1)*
- **Path**: `app/Models/User.php` — cast só em `dock_atalhos` → `array`; `dock_layout` fica texto (nulo = padrão da instalação), lido por `DockLayout::tryFrom()` *(alterado em 2026-10-14: QA-01, ciclo 1 do quality gate)*; **fora** do `$fillable` e **dentro** do `$hidden` (o `PageHeaderTest` trava as colunas expostas do usuário) *(alterado em 2026-10-14: ticket 01)*. Sem método novo: quem grava é `DockDeAtalhos::gravar()` com `forceFill()` *(alterado em 2026-10-13: Ponytail #2/#3)*
- **Atende**: RQ-10, RQ-16, RQ-17
- **Logs**: nenhum

### 7. `App\Support\DockDeAtalhos` — settings, catálogo e autorização

> Skills: `laravel-best-practices`, `filament-development`

- **Path**: `app/Support/DockDeAtalhos.php` (só estáticos, como `CabecalhoDoPainel`)
- `habilitadoNoPainel(Panel $painel): bool` — `in_array($painel->getId(), config('kit.dock.paineis'), true)`. Zero query (RQ-14, RQ-22)
- `layoutPara(?User $usuario): DockLayout` — `DockLayout::tryFrom((string) $usuario?->dock_layout) ?? DockLayout::tryFrom(config('kit.dock.layout')) ?? DockLayout::SpeedDial` *(alterado em 2026-10-14: QA-01, ciclo 1 do quality gate)*; `estilo(): DockStyle` análogo (RQ-17)
- `catalogo(User $usuario): array<string, string>` — chave D6 → rótulo `/{painel} — {organização, no /app} — {grupo} — {tela}` (RQ-31). Para cada painel em que `canAccessPanel()`: `[...getResources(), ...getPages()]` filtrado por `shouldRegisterNavigation()` e `canAccess()` **dentro de `noContexto()`**; para o `app`, só a organização aberta (D7) (RQ-11, RQ-12)
- `atalhosVisiveis(User $usuario): list<array{chave: string, url: string, rotulo: string, icone: ?string}>` — memo por `Request` (D8); cada atalho passa por `podeAbrir()`; URL por `$destino::getUrl(panel: …, tenant: …)` (RQ-04, RQ-15, RQ-16, RQ-19)
- `podeAbrir(User, string $chave): bool` — chave tem 3 partes; painel existe; destino é classe dentre `getResources()/getPages()` do painel; `canAccessPanel()`; no `app`, organização existe e `canAccessTenant()`; `noContexto(...)` → `shouldRegisterNavigation() && canAccess()`. `catch (Throwable)` → `false` (ADR-01). Favorito do painel e da organização abertos: direto em `shouldRegisterNavigation() && canAccess()`, sem `noContexto` (P-04) *(alterado em 2026-10-14: QA-02, ciclo 1 do quality gate)*
- `noContexto(Panel $painel, ?Tenant $organizacao, User $usuario, Closure $callback): mixed` — guarda painel corrente e tenant, `Filament::setCurrentPanel()`, `Filament::setTenant()`, `ContextoDePapeis::em($organizacao?->getKey() ?? Tenant::CONTEXTO_GLOBAL, ...)`, restaura tudo no `finally`; antes de avaliar destino de outro painel, e de novo no `finally`, descarta o `FilamentShield` memorizado (`Paineis::shieldNovo()`, RD-01) *(alterado em 2026-10-14: QA-02, ciclo 1 do quality gate)*
- `paginaAtualComoAtalho(User): ?string` (chave) — `DockContext::current()->getPageClass()`; se for a página `index` de um Resource, o Resource; se for Page avulsa, a Page; senão `null` (RQ-23). Só devolve se a chave está no catálogo e ainda não é favorita (RQ-23, RQ-28); a adição anexa no fim (RQ-33) e conta toda entrada gravada no teto (RQ-32)
- `gravar(User, list<string> $chaves, ?string $layout): void` *(alterado em 2026-10-13: Adendos 3 e 4)* — (1) qualquer chave fora do `catalogo()` **e** fora dos favoritos já gravados ainda abríveis (RQ-35) → recusa o envio inteiro, nada gravado, `ValidationException` no campo (RQ-29); (2) dedup (RQ-28); (3) resultado = chaves enviadas, **na ordem enviada** (RQ-30), + favoritos gravados que hoje estão ocultos (`! podeAbrir()` mas chave bem formada de destino existente no painel), preservados no fim; chave gravada que nunca foi destino (classe/painel inexistente, malformada) é descartada (RQ-27); (4) total > 10 — contando toda entrada gravada que permanece (RQ-32) — → recusa com aviso "limite de 10 atalhos", nada gravado (RQ-18, RQ-26); (5) layout por `DockLayout::tryFrom()`; `forceFill(['dock_atalhos' => …, 'dock_layout' => …])->save()`
- **Atende**: RQ-04, RQ-11, RQ-12, RQ-14, RQ-15, RQ-16, RQ-18, RQ-19, RQ-22, RQ-23, RQ-25, RQ-26, RQ-27, RQ-28, RQ-29, RQ-30, RQ-31, RQ-32, RQ-33, RQ-35
- **Logs**:
  - `warning`: `[DockDeAtalhos@gravar] Chave fora do catálogo descartada | user: {id}` — context `['user_id', 'descartadas' => contagem]` (nunca a chave crua)
  - `warning`: `[DockDeAtalhos@podeAbrir] Falha ao avaliar destino, negado | user: {id} - painel: {painel}` — context `['user_id', 'painel', 'erro' => classe da exceção]`
  - `info`: `[DockDeAtalhos@gravar] Favoritos gravados | user: {id}` — context `['user_id', 'total']`

### 8. `App\Filament\Dock\PluginDoDock` — as actions

> Skills: `filament-development`, `laravel-best-practices`

- **Path**: `app/Filament/Dock/PluginDoDock.php` — `make(): QuickActionDockPlugin`:
  - `->authorize('Usar:Dock')`, `->visible(fn () => DockDeAtalhos::habilitadoNoPainel(Filament::getCurrentPanel()))`, `->layout(fn () => DockDeAtalhos::layoutPara(auth()->user()))`, `->style(fn () => DockDeAtalhos::estilo())`, `->position(DockPosition::BottomCenter)` (D5)
  - `->actions(fn () => …)`: uma `Action::make('atalho_'.$i)->label()->icon()->url($url)` por favorito visível (ordem gravada)
  - `->group('Atalhos', …)` com `adicionarPaginaAtual` (visível só quando `paginaAtualComoAtalho()` não é nulo; com 10 gravados — ocultos contam — recusa com notificação de aviso "limite de 10 atalhos" e não grava (RQ-26); notificação de sucesso) e `gerenciarAtalhos` (`->slideOver()`, `schema`: `Select::make('atalhos')->multiple()->reorderable()->options(fn () => DockDeAtalhos::catalogo($usuario))` — `fillForm()` traz **todo favorito gravado que `podeAbrir()`**, de qualquer painel e organização, como escolhido (opções extras com rótulo de painel e organização, fora do catálogo de novas opções — RQ-35) *(alterado em 2026-10-13: Adendo 5)*; a validação de `gravar()` aceita essas chaves já gravadas além do catálogo; ocultos ficam fora do formulário e são preservados por `gravar()` (RQ-27, RQ-30); sem `maxItems()` — o teto é decidido em `gravar()`, contando os ocultos, `Select::make('layout')->options([...])->placeholder('Padrão da instalação')`; `->fillForm()` com o estado atual; `->action()` chama `DockDeAtalhos::gravar()`)
  - As duas actions com `->authorize('Usar:Dock')` (inventário)
- **Atende**: RQ-02, RQ-04, RQ-08, RQ-10, RQ-11, RQ-13, RQ-15, RQ-16, RQ-17, RQ-18, RQ-20, RQ-21, RQ-23, RQ-26, RQ-27, RQ-28, RQ-30
- **Logs**: os de `DockDeAtalhos` (a action não loga por conta própria)

### 9. Registro nos três painéis e inventário

> Skills: `filament-development`, `pest-testing`

- **Path**: `app/Providers/Filament/{Admin,App,Infra}PanelProvider.php` — `PluginDoDock::make()` em `->plugins([...])` dos três (D3)
- **Path**: `tests/Kit/PermissoesDeAcoesTest.php:inventarioDeAutorizacao()` — linhas `app/Filament/Dock/PluginDoDock.php::adicionarPaginaAtual` e `::gerenciarAtalhos` → `permissao`
- **Path**: `tests/Kit/PermissoesDeAcoesTest.php` — `Usar:Dock` no dataset do CT-15 ("cria a permissão nova de Action no banco"); o CT-19 (painel declarado para toda custom permission) já cobre o mapa sem edição
- **Path**: `tests/Kit/KitInfoTest.php` — âncora `toHaveCount(63)` → `66`, com a linha de histórico da feature
- **Atende**: RQ-02, RQ-13, RQ-14
- **Logs**: nenhum

### 10. Documentação e CHANGELOG

- **Path**: `docs/pt/recursos/hub-de-navegacao.md` e `docs/en/recursos/hub-de-navegacao.md` — seção "Dock de atalhos" (o que é, a permissão `Usar:Dock`, o teto de 10, favoritos entre painéis); `docs/{pt,en}/recursos/configuracoes-do-kit.md` — as três opções da aba Kit; `CHANGELOG.md`
- Toda frase cita comportamento com `RQ` de origem (RQ-10 a RQ-21); nada além
- **Atende**: RQ-02, RQ-13, RQ-14, RQ-17, RQ-18
- **Logs**: nenhum

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação: reutilizar (`ContextoDePapeis`, `Paineis::opcoes()`, o filtro do concern de cards, o padrão de memo do `CabecalhoDoPainel`), sem tabela nova, sem model novo.
>
> Atalhos deliberados levam comentário `ponytail:`.
>
> **Caveman ativo em modo `ultra`** só na conversa; wiki, código, commits e PR em prosa normal.
>
> **Baseline antes do primeiro commit**: suíte completa em `main`, falhas pré-existentes por nome.

## Mapeamentos

| Valor gravado | Rótulo pt-BR |
|---|---|
| `speed-dial` | Leque (speed dial) |
| `bar` | Barra |
| `native` | Nativo |
| `outline` | Contorno |
| `contrast` | Contraste |
| `tinted` | Tingido |

*(alterado em 2026-10-14: rótulos seguem `DockDeAtalhos::opcoesDeLayout()`/`opcoesDeEstilo()` e as docs)*

## Testes

> Ver `04-casos-de-teste.md` (step 7) e, se houver costura `browser`, `05-casos-de-teste-browser.md`.

## Verificação Final

- [ ] `/ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent` e `vendor/bin/filacheck --fix`
- [ ] `vendor/bin/pest --filter=Dock --compact`
- [ ] `vendor/bin/pest tests/Browser --filter=Dock` (só se houver `05`)
- [ ] `vendor/bin/pest --parallel --tia` contra a baseline de `main`
- [ ] `pest --mutate --path=app/Support/DockDeAtalhos.php` — score, duração e sobreviventes
- [ ] **Custo medido** — query count do render com dock ligado × desligado (RQ-22) e do slideover
- [ ] **`/code-review high main...HEAD` + passe de eixos (step 9)**

## Commits

- `:heavy_plus_sign: chore(deps): adiciona hoceineel/filament-quick-action-dock`
- `:sparkles: feat(dock): dock de favoritos por usuário nos três painéis`
- `:white_check_mark: test(dock): casos de teste do dock de atalhos`

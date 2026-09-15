# Decisões Arquiteturais — Dashboard dinâmico nos painéis

## ADR-01: O liga/desliga é decidido por request, nunca no registro do painel

**Status**: Aceita

### Contexto

O requisito pede um toggle em `/admin/configuracoes-da-aplicacao` que valha sem
deploy. O caminho ingênuo — `->pages([...])` condicional ou `->plugin()`
condicional no PanelProvider — não funciona: os painéis são montados no
`register()` dos providers, e `ConfiguracoesDoKit::aplicarNaConfig()` só roda no
`boot()` do `KitServiceProvider` (`KitServiceProvider.php:263-277`). O valor do
banco chega depois do painel montado — é exatamente o cenário que
`.ai/rules/settings.md` proíbe ("toggle que grava e não faz efeito é pior que
campo ausente").

### Decisão

As duas páginas (dinâmica e clássica) são registradas SEMPRE, e a escolha
acontece por request em três pontos que o Filament já avalia a cada chamada:
`mount()` (redirect), `shouldRegisterNavigation()` (item de menu) e
`canAccess()`/`canEdit()` (autorização). O ponto único de leitura é
`App\Support\DashboardDinamico::habilitado()`.

É o mesmo padrão do `ExigirEmailVerificado` (v0.19.8): aplica-se sempre e
decide dentro — aplicar condicionalmente devolve o problema ao boot.

### Alternativas consideradas

1. **`->pages()` condicional no provider** — lê config no `register()`, antes
   do banco ser aplicado: o toggle gravaria e não faria efeito até o próximo
   deploy. Rejeitada pela regra de settings.
2. **Middleware que troca a rota** — a rota `/` já existe e aponta para uma
   página; reescrever o destino no middleware é lutar contra o roteador do
   Filament. O redirect no `mount()` é o mesmo efeito com uma linha.
3. **Uma página só que renderiza os dois modos** — exigiria reimplementar o
   `content()` da `Filament\Pages\Dashboard` dentro da `DynamicDashboard`
   (ou vice-versa): dois motores de grade numa classe. Duas páginas finas com
   redirect são mais simples e cada uma fica a um `extends` do vendor.

### Consequências

- Nenhuma rota nasce ou morre com o toggle — `route:list` é estável.
- O redirect adiciona um hop HTTP no acesso à raiz do painel no modo
  "redirecionado". Custo medíocre, ganho de nunca haver 403/404 na `/`.
- Testes precisam cobrir os dois sentidos do redirect por request.

## ADR-02: A clássica fica na raiz `/`; a dinâmica mora em `/dashboard-dinamico`

**Status**: Aceita *(alterado em 2026-09-15: a decisão original — dinâmica na
raiz, clássica em `/inicio` — foi REVERTIDA. Ver "O que mudou e por quê".)*

### Contexto

Duas páginas não podem dividir a mesma rota. Alguém fica com `/` e o outro
recebe um slug. O projeto de referência colocou a dinâmica em `/` (a página
`Dashboard` dele estende `DynamicDashboard` direto, sem toggle) — mas lá não
existe toggle nem kit instalado, o que muda o cálculo.

### Decisão

A **clássica** herda `/`, como o `Filament\Pages\Dashboard` que ela substituiu
(`$routePath = '/'`, herdado). A **dinâmica** fica em `/dashboard-dinamico` (`$slug`
próprio) — sem `$routePath` nem `getRoutePath()`. Com a feature ligada, a raiz
devolve para `/dashboard-dinamico`; desligada, a dinâmica devolve para a raiz.

O slug `dashboard` continua sendo do clássico, e não por estética: o NOME da
rota sai do slug (`Pages/Concerns/HasRoutes.php:55-58`), então deixá-lo com o
clássico preserva `route('filament.{painel}.pages.dashboard')` para todo projeto
sobre o kit. RQ-08 vale para o nome da rota, não só para a URL.

### O que mudou e por quê

A decisão original punha a dinâmica na raiz, e com a feature DESLIGADA a raiz
passava a responder 302 para `/inicio`. Isso quebra `RQ-08` ("update do kit é
inerte"): a URL canônica dos três painéis mudava para todo projeto que
atualizasse, mesmo sem ligar nada. Medido: **98 testes** do próprio kit que
abrem `/app`, `/admin` e `/infra` esperando 200 ficaram vermelhos — e nenhum CT
desta wiki percebeu, porque todos foram derivados do desenho novo.

A alternativa 1 abaixo, preterida por estética de URL, era a correta.

### Alternativas consideradas

1. **Clássica em `/`, dinâmica em `/dashboard-dinamico`** — a decisão vigente. O custo é
   a URL `/dashboard` no modo ligado; o ganho é update inerte e zero regressão
   nas rotas existentes.
2. **Dinâmica em `/`, clássica em `/inicio`** — a decisão original, revertida:
   muda a URL canônica de todo painel mesmo com a feature desligada.
3. **Dinâmica em `/` com `canAccess()` = desligado** — devolve 403 na raiz do
   painel em vez de cair na clássica. Rejeitada: a home do painel não pode
   responder 403 por configuração.

### Consequências

- Atualizar o kit não muda rota nenhuma: com a feature desligada, `/app`,
  `/admin` e `/infra` respondem exatamente o que respondiam.
- Com a feature ligada, o usuário que abre a raiz é redirecionado uma vez para
  `/dashboard-dinamico`. Bookmark da raiz continua funcionando.
- O item de navegação "Dashboard" alterna de dono conforme o toggle; os dois
  nunca aparecem juntos (`shouldRegisterNavigation` espelhado).
- Em painel com tenancy, a rota `/` (agora da clássica) vira `->fallback()`
  automaticamente (`Pages/Concerns/HasRoutes.php:46-47`) — herdado, sem código.
- As três rotas `/{painel}/dashboard-dinamico` entram em `telasForaDoInventario()` do
  `InventarioDeTelasTest`: desligadas só redirecionam, e ligadas têm CT-B
  próprio.

## ADR-03: Uma subclasse de página por painel — a FQCN é a fronteira

**Status**: Aceita

### Contexto

Seria tentador registrar a MESMA classe de página dinâmica nos três painéis —
o próprio kit faz isso hoje com `Filament\Pages\Dashboard`. Mas o isolamento
de dashboards do pacote é pela coluna `dashboards.page`: `scopeAvailable()`
filtra `page = static::class OR page IS NULL`
(`vendor/mddev31/filament-dynamic-dashboard/src/Models/Dashboard.php:218-222`).

### Decisão

`App\Filament\App\Pages\Dashboard`, `App\Filament\Admin\Pages\Dashboard` e
`App\Filament\Infra\Pages\Dashboard` são três classes distintas (cada uma
`final`, estendendo `DynamicDashboard`). Painel novo do projeto cria a sua —
é a receita documentada no PRD §3.

### Consequências

- Dashboard montado no `/app` nunca aparece no `/admin` — a fronteira é o
  banco, não a UI.
- O Shield gera uma permission `View:Dashboard` por painel, escopada de graça
  (mesmo argumento de `config/filament-shield.php:239-247`).
- Custo: três classes quase vazias. Aceito — é o preço da fronteira.
- A clássica NÃO tem essa fronteira (não grava `dashboards.page`), então é
  uma classe compartilhada `App\Filament\Pages\DashboardClassico` registrada
  nos três painéis — mesmo padrão do `Filament\Pages\Dashboard` hoje.

## ADR-04: Gestão via custom permission `Manage:Dashboard` do Shield

**Status**: Aceita

### Contexto

`DynamicDashboard::canEdit()` devolve `true` por default
(`vendor/.../DynamicDashboard.php:189`) e governa TODA a superfície de
escrita: "Add Widget" (`:751`), "Manage" (`:858`), `createWidget()` (`:782`)
e o drag do GridStack (`canDrag = canEdit AND !is_locked`, `:355`). Sem override, qualquer usuário que abre a
página monta e desmonta dashboards — inaceitável no `/app`, onde o
`panel_user` é o papel de cliente (RQ-06).

### Decisão

`canEdit()` delega ao Shield: `auth()->user()?->can('Manage:Dashboard')`. A
permission entra em `custom_permissions` do `config/filament-shield.php` e no
mapa painel × custom permission do `PapeisSeeder`, com subtração explícita do
`panel_user`.

### Alternativas consideradas

1. **`use_spatie_permissions` do pacote (`DashboardWithRoles`) como gate de
   edição** — governa a VISIBILIDADE de cada dashboard por papel, não a
   edição; eixo diferente do pedido. A flag fica ligada por paridade com a
   referência — ver ADR-05 — mas quem governa a escrita é `canEdit()`.
2. **Gate/policy própria fora do Shield** — funcionaria, mas ficaria invisível
   na tela de papéis: ninguém concederia nem revogaria por UI. O Shield é onde
   o kit concentra autorização.
3. **`canEdit()` por papel fixo (`admin_app`)** — engessa o que o requisito
   pede controlável ("permissões para poder gerenciar/manipular").

### Consequências

- `panel_user` vê o dashboard, não edita — read-only por default.
- Quem quiser que cliente monte o próprio dashboard concede a permission na
  tela de papéis — cenário previsto, não exceção.
- Ressemeio do `ShieldPermissionsSeeder` é obrigatório após o deploy
  (documentado no PRD §5).

## ADR-05: `use_spatie_permissions` do pacote fica LIGADO — eixo de visibilidade

**Status**: Aceita (revisada na revisão adversarial — P7)

### Contexto

O pacote tem um segundo model, `DashboardWithRoles`, ativado por
`config('filament-dynamic-dashboard.use_spatie_permissions')`
(`DashboardModelHelper.php:15-20`), que adiciona visibilidade por papel Spatie
a cada dashboard: `canDisplay()` filtra por roles
(`DynamicDashboard.php:164-176`) e o `DashboardManager` ganha o seletor de
roles. A referência roda com a flag ligada.

### Decisão

Ligar a flag. É um eixo DIFERENTE do `Manage:Dashboard`: roles decidem QUEM
VÊ qual dashboard; `canEdit()` decide quem monta. Dashboard sem roles fica
visível a todos — o semeado não exclui ninguém. A consequência de
implementação é que scope e hook de tenancy se registram na classe RESOLVIDA
por `DashboardModelHelper::model()`, não em `Dashboard::class` — global scope
e evento no pai não alcançam a subclasse.

### Alternativas consideradas

1. **Manter `false`** — mais simples, mas quebra a paridade com a referência e
   entrega menos que o pacote já oferece de graça. Rejeitada.

### Consequências

- O contrato do vendor passa a valer: dashboards existentes e nenhum exibível
  → 403 (`DynamicDashboard.php:122-124`) — P8, CT-28.
- O seletor de roles aparece no "Manage" — superfície extra de gestão,
  coerente com RQ-06.

## ADR-06: Escopo de tenant por `hasTenancy()`, não por id de painel

**Status**: Aceita

### Contexto

A referência fixa `filament()->getCurrentPanel()?->getId() !== 'app'` no scope
e no hook `creating`. Funciona lá porque só o `/app` tem tenancy — mas o
requisito pede painéis além dos três (RQ-07), e um painel novo com tenancy
ficaria de fora da regra.

### Decisão

A condição é `$painel?->hasTenancy()` + `Filament::getTenant()` — pergunta ao
painel se ele é tenant-aware, não quem ele é. `/admin` e `/infra` saem no
primeiro `return` por não terem tenancy; um quarto painel com `->tenant()`
entra sozinho.

### Consequências

- Sem tenancy ligada, o scope é no-op e a coluna `tenant_id` fica `null` —
  a migration é aditiva e inócua.
- O hook `creating` é o único escritor de `tenant_id` — a coluna não entra em
  `$fillable` nem em formulário.

## ADR-07: Feature nasce desligada — update do kit é inerte

**Status**: Aceita

### Contexto

RQ-08: projetos em produção sobre o kit não podem ganhar comportamento-surpresa
num `kit:update`. As migrations do pacote já estão no `database/migrations` do
kit desde antes desta feature — quem atualiza já roda `migrate` e ganha as
tabelas vazias.

### Decisão

`KIT_DASHBOARD_DINAMICO` default `false`, e a migration de settings semeia
`dashboard_dinamico_habilitado` a partir de `config()` — ou seja, `false` para
quem atualiza. Ligar é uma escolha consciente na tela. O `kit:install` NÃO
pergunta: o default desligado vale para instalação nova também — o kit nasce
com o dashboard clássico e quem constrói liga quando tiver widgets para
oferecer (o `/app` nasce vazio por desenho).

### Consequências

- Update seguro por construção: sem a chave no `.env` e com a seed `false`,
  nada muda.
- As tabelas `dashboards`/`dashboard_widgets` existem vazias em toda
  instalação — custo: duas tabelas ociosas até a feature ser ligada.
- Desligar nunca apaga dado: o toggle governa qual página responde, não o que
  existe no banco.

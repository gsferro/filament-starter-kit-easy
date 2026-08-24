# Progresso — Permissões de telas e ações

## 0. Varredura (RQ-01)

- [x] Inventário de Pages e Widgets dos três painéis
- [x] `grep -rn "Action::make("` em `app/` e `resources/views/`
- [x] `Paineis::permissoes($painel)` por painel, contra o banco semeado
- [x] Leitura do `vendor/` nos 6 pontos que sustentam decisão (com `file:line` no PRD e nas ADRs)

## 1. Concern de Page + as 5 Pages

- [x] `app/Filament/Concerns/ExigePermissaoDaTela.php`
- [x] `Infra/Pages/Pulse.php`
- [x] `Infra/Pages/HubDeInfraestrutura.php` (+ docblock `:52-54` atualizado)
- [x] `Admin/Pages/HubDeAdministracao.php` (`canAccess()` → `regraLocalDeAcesso()`)
- [x] `App/Pages/HubDoNegocio.php` (idem)
- [x] `App/Pages/ConvitesRecebidos.php` (idem)

## 2. Concern de Widget + os 23 Widgets

- [x] `app/Filament/Concerns/ExigePermissaoDoWidget.php`
- [x] 5 sem `canView()`: `AgentesIaStats`, `AgentesIaPorProvider`, `UsuariosPorPapel`, `UsuariosVisaoGeralStats`, `UltimosUsuariosCadastrados`
- [x] 18 com `canView()` → hook `fonteDeDadosDisponivel()`

## 3. As 6 permissões novas (`config/filament-shield.php`)

- [x] `tabs.custom_permissions` ligada
- [x] `resources.manage` com as 4 chaves de Action
- [x] `custom_permissions` com as 2 de Page

## 4. Recorte de painel no `PapeisSeeder`

- [x] `paineisDasPermissoesCustomizadas()`
- [x] `permissoesDoPainel()` rejeitando custom permission de outro painel

## 5. As 6 Actions + 1 link

- [x] `ConvitesTable` → `reenviar`
- [x] `UsersRelationManager` → `AttachAction`, `DetachAction`, `papeisNaOrganizacao`
- [x] `ConvitesRecebidos` → `aceitar`, `recusar`
- [x] `ListAiRuns` → `dashboardAiTasks`

## 6. Ressemear e conferir a matriz

- [x] `ShieldPermissionsSeeder` + `PapeisSeeder`
- [x] conferência por query das 6 × 4 papéis

## 7. Testes

- [x] `tests/Kit/PermissoesDeTelasTest.php` — CT-01..CT-05, CT-21, CT-23, CT-24, CT-26
- [x] `tests/Kit/PermissoesDeWidgetsTest.php` — CT-07, CT-08, CT-22, CT-32
- [x] `tests/Kit/PermissoesDeAcoesTest.php` — CT-09..CT-12, CT-15..CT-17, CT-19, CT-20, CT-25, CT-27, CT-28, CT-29, CT-31
- [x] CT-06 foi para `tests/Tenancy/PermissoesDeAcoesTenancyTest.php` (arquivo único da suíte Tenancy — ver Desvios)
- [x] `tests/Tenancy/PermissoesDeAcoesTenancyTest.php` — CT-13, CT-14, CT-18, CT-30
- [x] `tests/Browser/PermissoesDoDashboardTest.php` — CT-B01, CT-B02

## 8. Documentação

- [x] `README.md` — família nova na tabela de papéis
- [x] `README.en.md` — idem
- [x] `.ai/rules/filament.md` — recontagem de `Paineis::permissoes('app')->count()`

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy`
- [x] `vendor/bin/phpstan analyse --no-progress`
- [x] `composer test:browser`
- [x] Roteiro "Desenhado × Implementado" do `05` preenchido
- [x] `git push -u origin feat/permissoes-de-telas-e-acoes`

---

## Degradação de ferramenta declarada

**O MCP do Laravel Boost conectou e as tools dele nunca ficaram expostas a este agente.** O
servidor apareceu como "conectando" e depois publicou as instruções dele, mas
`ToolSearch` com `select:mcp__laravel-boost__search-docs,...`, com `+boost` e com
`database schema query tinker artisan application info` devolveu **"No matching deferred tools
found"** nas três tentativas. Logo `search-docs`, `database-query`, `database-schema` e
`record-rule` **não** foram utilizáveis.

Substitutos usados, e o que cada um cobriu:

| Tool ausente | Substituto | Onde aparece |
|---|---|---|
| `search-docs` (contrato do Filament 5) | `WebFetch` em `filamentphp.com/docs/5.x/navigation/custom-pages`, `.../widgets/overview`, `.../actions/overview` | confirmou: `canAccess()` esconde da navegação **e** barra o acesso direto; `canView()` é o gancho de ocultação de widget; `authorize()` aceita nome de método de policy e **esconde** a Action por default, com `authorizationTooltip()`/`authorizationNotification()` como alternativas |
| `search-docs` (Shield) | leitura do `vendor/` com `file:line` — **é o que `.ai/rules/specs.md` exige de qualquer forma**, e a doc do Boost não cobre `bezhansalleh/filament-shield` | 6 citações no PRD + 12 nas ADRs |
| `database-query` | `php artisan tinker --execute` contra o banco SQLite semeado do worktree | a tabela painel × `View:` no PRD, `## Contexto` |
| `database-schema` | não foi necessário: a feature não toca schema | — |
| `record-rule` | **por instrução explícita do coordenador**, rule só é PROPOSTA aqui, nunca gravada | `## Candidatos a Rule` abaixo |

A doc oficial **não contradisse** nada que o PRD assumia. O único ponto em que ela é omissa é o
default de `canView()` quando não sobrescrito — respondido pelo vendor
(`vendor/filament/widgets/src/Widget.php:34-37`: `return true`).

**Atualização ao fim da entrega**: as tools do Boost ficaram expostas **depois** que a
implementação e os testes já estavam prontos, então os substitutos acima é que sustentaram as
decisões — e continuam sendo a evidência citada nas ADRs. Duas notas para quem vier depois:

- **`search-docs` não teria mudado nada de material.** As sete decisões vêm de `vendor/` com
  `file:line` (o comentário de segurança do `CanAuthorizeAccess`, o do `RelationManager`, o
  `array_merge` do `getDefaultPolicyMethodsOrFor`, o merge de `custom_permissions` em
  `getEntitiesPermissions`), e nenhuma delas está na doc oficial — `bezhansalleh/filament-shield`
  não é coberto pela Documentation API, e o comportamento do vendor é justamente o que
  `.ai/rules/specs.md` manda ler no fonte em vez de na doc.
- **`database-query` do Boost aponta para o banco do repositório PRINCIPAL, não para o do
  worktree.** Rodado depois do rebase, devolveu `[]` para as 6 permissões novas — não porque elas
  não existem, mas porque o servidor MCP roda a partir da raiz do projeto e lê o `.env` de lá. Num
  worktree, a verificação de banco é `php artisan tinker` **dentro do worktree**. Confirmado assim:
  as 4 de administração só em `admin`, as 2 de convite em `admin_app` e `panel_user`, totais
  61/127/142 (app/admin/infra) — idênticos aos de antes do rebase.

**Playwright MCP não foi usado** (proibido nesta rodada — instância única compartilhada). Os CT-B
são `pest-plugin-browser`.

---

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa inicial | O código real diz | Correção aplicada na wiki |
|---|---|---|
| "as permissões de Page e Widget não existem no banco, é preciso criá-las" | **existem, e já estão nos papéis.** `FilamentShield::getEntitiesPermissions()` mistura Resource + Page + Widget (`FilamentShield.php:114-124`), e `Paineis::permissoes()` consome exatamente isso. Confirmado por tinker: 7 `View:{Widget}` em `admin`, 16 em `infra`, 3 `View:{Page}` em `app` | RQ-02 passou a valer **só** para as 6 de Action. `## Objetivo` do PRD reescrito: a entrega liga a consulta, não cria matriz. Cortou um passo de seeder inteiro |
| "`use HasWidgetShield;` nos 23 widgets resolve" | método de classe vence método de trait, **em silêncio**. 18 dos 23 já têm `canView()` — a linha seria no-op | ADR-01 e o concern do passo 2 |
| "`->authorize('reenviar')` com nome de método de policy, como o `import`/`export` do kit" | o `ShieldPermissionsSeeder` passa `--ignore-existing-policies` (`:82`), então o método **não** seria escrito nas policies existentes, e a Action ficaria oculta para quem tem a permissão | passo 5 do PRD usa o **nome da permission**, confirmado viável pelo `Gate::before` do spatie (`PermissionRegistrar::registerPermissions()`) |
| "só as Actions customizadas precisam de `->authorize()`; as nativas já consultam a policy" | verdade em Resource, **falso em RelationManager** — `RelationManager.php:348-353` diz em comentário que `AttachAction`/`DetachAction` só checam `isReadOnly()` | ADR-04; `AttachAction` e `DetachAction` entraram no escopo, +2 permissões |
| "ligar o tab de custom permissions exige mexer no `RoleResource` publicado" | a aba é montada por `HasShieldFormComponents::getShieldFormComponents()` (`:25-36,174-180`), no vendor, a partir da config. **Nada** em `app/Filament/Admin/Resources/Roles/**` precisa ser tocado | passo 3 do PRD; preserva o worktree paralelo |
| "custom permission é escopada por painel como o resto" | `transformCustomPermissions()` não consulta painel (`HasEntityTransformers.php:88-112`); `getEntitiesPermissions()` faz merge dela em **todo** painel (`:119`) | ADR-03 + passo 4 (recorte no seeder) + CT-19 |
| "`resources.manage[RoleResource]` restringe o RoleResource às 5 chaves listadas" | **é no-op duplo**: (a) `policies.merge => true` faz `array_merge`, devolvendo as 14; (b) a chave é o `RoleResource` do **vendor**, e o painel usa o publicado do kit | não corrigido — é arquivo da feature paralela. Registrado em Notas |
| "o docblock de `HubDeInfraestrutura` proíbe `canAccess()`, então a Page fica fora" | o docblock (`:52-54`) proíbe `canAccess()` **com a flag `kit.hub`**, e o teste guarda (`HubDeCardsTest.php:110`) usa papel `infra`, que **tem** `View:HubDeInfraestrutura` | ADR-06; a Page entrou no escopo |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | não criar permissão nova para Page e Widget — elas já existem | **sim** | `01`, passo 2 e `## Objetivo`. É o corte que reduziu a entrega de "criar 34 permissões" para "criar 6" |
| 2 | não criar channel de log nem linha de log | **sim** | ADR-07 |
| 3 | não escrever policy method para as 6 permissões novas | **sim** | passo 5: `->authorize()` com o nome da permission |
| 4 | `dashboardAiTasks` reusa o gate `ver-ai-tasks` em vez de ganhar permissão nova | **sim** | passo 5, última linha. É a rung 2 da escada: já existe no projeto, no `NavigationItem` irmão |
| 5 | não subclassear as 10 Pages de vendor | **sim** | ADR-05 |
| 6 | não escrever 21 cenários de par tem/não-tem para as Pages e Widgets restantes | **sim** | R9 do `04`: teste de arquitetura cobre o conjunto por preço fixo |
| 7 | usar `HasPageShield`/`HasWidgetShield` em vez de reimplementar a resolução da chave | **sim** | ADR-01, alternativa 3 |
| 8 | não construir o mapa de painel de custom permission (YAGNI: hoje o vazamento é inócuo) | **recusada** | o mecanismo é o over-grant silencioso que `.ai/rules/filament.md` nomeia como a falha mais cara desta parte do kit, e fechar custa ~15 linhas num arquivo que já existe. Ponytail não corta segurança |
| 9 | não escrever CT-24 (caso que afirma a ausência de cobertura) | **recusada** | sem ele, a "correção" da inconsistência é descobrir em produção o `LogicException` de plugin não registrado |

### Revisão adversarial do `04` (step 6 da `feature-test-design`)

Delegada a sub-agente que não derivou os cenários e recebeu **só** o `00` e o `04` — nunca o PRD,
as ADRs nem código.

**Resultado: 21 achados. 19 aceitos, 2 recusados com motivo escrito.** A tabela caso a caso está em
`04-casos-de-teste.md` → `## Revisão Adversarial`. O conjunto foi de 24 para **32 cenários** e de 9
para **10 regras**.

Os três que mudaram a entrega, e não só o teste:

1. **RQ-01 e RQ-07 não tinham barreira executável.** R4/R5 cobriam uma lista **fechada de 6
   Actions**, escolhida pelo mesmo agente que escreveu os testes — logo nada falsificava "faltou uma
   na varredura", e Action nova nasceria aberta em silêncio. Virou a Regra **R10** e o cenário
   **CT-25**: um inventário declarado, com varredura do código-fonte de `app/Filament/**` por
   `Action::make('`, `SpotlightAction::make('` e `NavigationItem::make('`. É o único mecanismo da
   wiki que responde por "TODAS as actions" em vez de "as 6 que encontramos".
2. **O agregado da idempotência era o errado.** A dispensa olhava as Actions; o agregado que esta
   feature grava é a **matriz papel × permissão**, e `db:seed` sobre banco existente é o caminho real
   de quem atualiza o kit. Virou **CT-27**.
3. **Fail-open com a permissão ausente do banco.** Nenhum cenário rodava com a linha de permissão
   inexistente, e a guarda "para não travar instalação nova" é plausível. Virou **CT-26**.

Recusados: o cenário do caminho pela tela de papéis para RQ-09 (exigiria
`Livewire::test(EditRole::class)`, e `app/Filament/Admin/Resources/Roles/**` está sendo reescrito
pela feature paralela — conflito de merge garantido; a metade "salvar pela tela" fica declarada como
lacuna desta entrega) e o mutante `->authorize($booleano)` (inexpressível na forma escolhida —
`->authorize('Chave')` resolve por `Gate::check()` a cada render,
`vendor/filament/actions/src/Concerns/CanBeAuthorized.php:42-47,119-127`).

Segunda rodada **não** executada: o fechamento criou 8 cenários novos, mas o teto de 2 rodadas
existe para conter o loop e não para obrigá-lo. Lacuna nova em CT-25..CT-32 entra pelo destino 3 do
`feature-quality-gate`.

## Quality Gate (step 8)

**Ciclo 1 — `APROVADO COM DÉBITO`.** Perfil **completo** (natureza `nova`, UI com JS, domínio
sensível). Relatório em `06-relatorio-qa.md`.

- Blocker **0** · Major **0** · Minor **2** · Cosmético 0
- Matriz de Rastreabilidade: as 9 cláusulas `RQ` têm passo, CT e código. Nenhum passo, CT ou arquivo
  ficou **sem** `RQ`. Duas cláusulas parciais, as duas por premissa declarada no `00` antes de
  implementar (RQ-05 e RQ-09).
- Dimensões rodadas: A, B, C, D, E, F, I, J, K. Puladas com motivo: **G** e **H** (o diff não toca
  marcação, CSS nem classe de cor — verificável por `git diff --stat`).
- Não verificado, declarado: `--mutate` (fora do escopo desde o `04`, com motivo), a segunda sonda
  de performance (timeout disputando CPU com a suíte), Playwright MCP (proibido) e as tools do Boost
  (nunca expostas).

**Débitos aceitos**, os dois replicados aqui:

1. **QA-01** — Page e Widget de **vendor** do `/infra` (10 Pages + 1 Widget) têm a permissão gerada
   e selecionável, e ela **não é consultada**. Premissa de escopo declarada em ADR-05, com o custo da
   alternativa escrito. CT-24 assere a lacuna e **fica vermelho no dia em que alguém a fechar** — é
   o sinal de que ADR-05 precisa ser revisada, não de que o teste está errado.
2. **QA-03** — RQ-09 ("conceder/revogar por papel sem editar código") está provada em três peças
   separadas (CT-16 oferece a opção, CT-01/CT-02 mudam o resultado, `tests/Kit/PaineisTest.php:198,228`
   provam que a tela grava), e falta o cenário que marca o checkbox em `/admin/shield/roles` e vê a
   tela passar de 403 para 200. **Escrever depois do merge de `feat/perfis-e-permissoes`**: um
   `Livewire::test(EditRole::class)` agora é conflito de merge garantido naquele diretório.

Nenhum achado exigiu volta ao step 4 nem ao passo do PRD. Um ciclo, sem reciclagem.

## Blockers

<!-- vazio -->

## Desvios do Plano

### 1. O link `dashboardAiTasks` NÃO ganhou `->visible()`, e isso é o achado

O passo 5 do PRD mandava acrescentar `->visible(fn () => Gate::allows('ver-ai-tasks'))`. **Foi
implementado, o teste não conseguiu falsificá-lo, e a investigação mostrou que a premissa estava
errada.**

`AiRunResource::canAccess()` é literalmente `Auth::user()?->can('ver-ai-tasks')`
(`app/Filament/Infra/Resources/AiRuns/AiRunResource.php:81-84`) — a **mesma** expressão que protege a
rota de destino. Logo não existe persona que abra a listagem e falhe no gate: a tentativa de arranjar
uma redefinindo o gate para `false` fecha a tela inteira, porque é o mesmo gate. O `->visible()` era
no-op **e infalsificável**, e ficaria como mutante sem matador — o que o `04` proíbe.

Ação: a linha foi revertida, o docblock do arquivo passou a explicar a ausência com `file:line`, e
CT-20/CT-31 foram reescritos para afirmar a propriedade que existe de verdade — a tela responde 403
para quem não passa no gate do destino, e o link está lá para quem passa. A entrada do inventário
declara o mecanismo `gate-da-tela`.

O `NavigationItem` irmão do `InfraPanelProvider` **mantém** o `->visible()`: item de menu é
renderizado fora do Resource, então lá a checagem não é redundante.

**O furo de affordance descrito na varredura inicial não existia.** É a hipótese rejeitada desta
entrega, e `.ai/rules/specs.md` manda registrá-la.

### 2. O mutante "autorização no `->visible()` em vez do `->authorize()`" não existe

O `04` previa (M16) que pôr a autorização no `->visible()` esconderia o botão e deixaria o
`callAction` executando — o furo clássico de "permissão validada só na UI". **Falso**: `mountAction()`
consulta `isVisible()` **e** `isAuthorized()`, e Action oculta por qualquer dos dois não é montada
nem executada. A diferença entre os dois é semântica e de recurso (`authorizationTooltip()`,
`authorizationNotification()`), não de enforço.

Consequência prática: `callAction()` não serve como oráculo em cenário de recusa, porque o helper do
Pest assere a visibilidade antes de chamar. Os casos de efeito (CT-11, CT-12, CT-14, CT-28) usam o
par `assertActionExists` + `assertActionHidden` mais as duas direções do não-efeito. Registrado no
docblock de CT-11.

### 3. CT-06 foi para o arquivo de Actions da suíte Tenancy

O PRD previa `tests/Tenancy/PermissoesDeTelasTenancyTest.php` só para CT-06. Um arquivo com um caso
custa um `beforeEach` de seeders inteiro (segundos) e obriga o Pest a carregar mais um arquivo.
CT-06 ficou em `tests/Tenancy/PermissoesDeAcoesTenancyTest.php`, que já tem o arranjo de organização.

### 4. Dois casos precisaram virar dataset por causa da sessão

CT-04 e CT-26 foram desenhados com as duas personas no mesmo `it()`. Dois `actingAs()` no mesmo caso
deixam a sessão do primeiro request valendo, e o segundo `get()` responde **302 para o login** — a
asserção mediria a sessão, não a permissão. Viraram dataset de duas linhas, o que preserva o poder
discriminante (as duas linhas continuam sendo o caso) e diz qual metade falhou.

### 5. Um helper novo em `tests/Pest.php` que o plano não previa: `noPainelDoShield()`

Descoberto ao rodar CT-32: os 16 widgets do `/infra` apareciam como visíveis para um usuário sem
permissão alguma. Não era defeito da implementação — era o arranjo. `FilamentShield` é `scoped` e
memoiza com `once()`, e a **facade** guarda o objeto resolvido, então percorrer os três painéis num
processo só devolve os widgets do PRIMEIRO painel em todas as voltas. As traits do Shield **falham
abertas** quando não acham a classe na lista, e o caso concluiria "a permissão não é consultada"
quando o que houve foi o arranjo consultar o painel errado.

`noPainelDoShield()` faz os dois descartes (container + facade) antes de fixar o painel — a mesma
mecânica que `App\Support\Paineis::shieldNovo()` já documentava. Em request real não acontece: um
request é um painel só.

## Notas de Implementação

- **`resources.manage[RoleResource]` do kit é no-op duplo** (ver Revisão profunda). Não corrigido
  aqui por ser arquivo da feature paralela `feat/perfis-e-permissoes`. Vale registrar o achado: se
  a intenção era limitar o `Role` a 5 permissões, ela nunca valeu — `DeleteAny:Role` existe e está
  no papel `admin`.

- **A matriz não mudou para nenhum papel do kit.** Conferido por query depois de ressemear: as 6
  permissões novas nascem em `admin` (4) e em `admin_app`/`panel_user` (2), e `master_global` segue
  com **zero** permissions, entrando pelo `Gate::before`. Para as Pages e os Widgets, o efeito no
  default é **nenhum** — todos os papéis de painel recebem a matriz inteira do painel deles. A
  mudança só aparece quando alguém desmarca um checkbox, que é exatamente o requisito.
  `Paineis::permissoes()` foi de 59/125/140 para **61/127/142** (app/admin/infra).

- **`AttachAction` em `headerActions()` de tabela precisa de `TestAction::make('attach')->table()`** —
  sem registro, mas com `->table()`. Sem isso o helper devolve `null` e a asserção falha com "null is
  not an instance of Filament\Actions\Action", que não aponta a causa.

- **`->authorize()` com nome de permission funciona por causa do `Gate::before` do spatie.**
  `PermissionRegistrar::registerPermissions()` registra `function (Authorizable $user, string $ability,
  array &$args = [])` e devolve `$user->checkPermissionTo($ability) ?: null` — os argumentos extras
  que `parseAuthorizationArguments()` empurra são ignorados. Quando o usuário não tem, o `before`
  devolve `null`, o Laravel não acha método de policy chamado `Reenviar:Convite` e o `Gate::check`
  resulta **false**. Fail-closed sem precisar escrever policy nenhuma.

- **O mapa de custom permission é montado sobre as chaves que o Shield GERA, não sobre as declaradas.**
  Primeira versão do `permissoesDoPainel()` rejeitava só o que estava no mapa — o que é **fail-open**:
  chave nova sem entrada escaparia do `reject` e iria para os quatro papéis. Corrigido antes de
  commitar; o comentário no método registra a inversão.

## Nota de rebase (main avançou durante a entrega)

Rebase sobre `origin/main` = `0d423dd`, **sem conflito**. Três merges entraram desde o início:
`#21` (auth designer nos três PanelProviders), `#22` (página de boas-vindas, com `routes/web.php` e
uma Page nova) e `#23` (fix do `env()` vazio em `config/kit.php`).

**A `BoasVindas` do `#22` NÃO entra na conta desta feature, e a decisão está registrada** no
`00-requisito.md` → `## Fora desta entrega`. Em resumo: ela vive em `app/Filament/Pages/`, que
nenhum `discoverPages()` varre, e é registrada como rota simples em `routes/web.php` com o
middleware `panel:app`. O Shield não gera permissão para ela — não há o que consultar. E ela é
servida em `/` para visitante **anônimo**: registrá-la num painel "por coerência" faria o `mount()`
chamar `authorizeAccess()` e devolveria **403 na home pública**.

Os dois testes de arquitetura desta feature (CT-21, CT-23) derivam a lista de
`Filament::getPanels()->getPages()`, e não do sistema de arquivos — então eles **nem a alcançam**,
por construção. Confirmado rodando os dois depois do rebase.

Verificado também que os três merges **não** trouxeram Action nem item de navegação novo: a
varredura de CT-25 sobre `app/Filament` + `app/Providers/Filament` devolve as mesmas 9 superfícies
nomeadas de antes do rebase. O `TelaDoisFatores` do `#21` é Page de autenticação, mesma família da
`BoasVindas`.

Pós-rebase: PHPStan 0 erros, os 73 casos desta feature verdes.

## Candidatos a Rule de Projeto (PROPOSTA — decisão do usuário)

Nenhuma rule foi gravada. `record-rule` não ficou disponível e, por instrução do coordenador, a
gravação é do usuário. Três candidatos, nos 4 gates.

### 1. Page e Widget novos nascem com a permissão consultada

- **Glob**: `app/Filament/**`
- **Onde**: atualizar `.ai/rules/filament.md`, **não** criar arquivo novo — a rule
  §"Em Page, `canAccess()` sozinho basta; em Resource são dois métodos" já fala do assunto e ficaria
  contraditória ao lado de uma rule nova. Atualizar é sempre preferível a criar.
- **Texto proposto** (para colar como seção nova em `.ai/rules/filament.md`):

  > ## Page e Widget novos nascem com a permissão consultada
  >
  > O Shield **gera** `View:{Page}` e `View:{Widget}` por descoberta, o `PapeisSeeder` **entrega**
  > aos papéis do painel, e a tela de papéis **mostra** o checkbox — mas nada disso faz a permissão
  > ser consultada. Os defaults do Filament são permissivos e o vendor diz isso em comentário:
  > `vendor/filament/filament/src/Pages/Concerns/CanAuthorizeAccess.php:17-24` e
  > `vendor/filament/widgets/src/Widget.php:34-37` retornam `true`. Desmarcar o checkbox de uma
  > Page ou Widget sem a trait não muda nada.
  >
  > Page de painel nova usa `App\Filament\Concerns\ExigePermissaoDaTela`; Widget novo usa
  > `ExigePermissaoDoWidget`. Regra local (flag de config, tenancy, `Schema::hasTable()`) vai no
  > hook — `regraLocalDeAcesso()` / `fonteDeDadosDisponivel()` —, nunca sobrescrevendo
  > `canAccess()`/`canView()`: **método de classe vence método de trait em silêncio**, e a linha
  > `use` fica no-op sem erro nenhum.
  >
  > **Não** use `HasPageShield`/`HasWidgetShield` do Shield direto: em classe que já tem o método,
  > a trait é ignorada.
  >
  > Enforçado por `tests/Kit/PermissoesDeTelasTest.php` e `PermissoesDeWidgetsTest.php` (os casos
  > de arquitetura, CT-21 e CT-22): classe nova sem o concern deixa o caso vermelho, com o nome da
  > classe na mensagem.
  >
  > Page e Widget de **vendor** ficam fora: são classes de pacote, sem ponto de extensão. A
  > permissão delas existe no banco e no checkbox, e **não é consultada**. A barreira é
  > `canAccessPanel()` mais os gates nomeados de `KitServiceProvider`.
  >
  > **Em teste que percorre mais de um painel, use `noPainelDoShield()`** (`tests/Pest.php`), nunca
  > `Filament::setCurrentPanel()` sozinho: o `FilamentShield` é `scoped`, memoiza com `once()` e a
  > facade guarda o objeto resolvido, então as três voltas leem o conjunto do PRIMEIRO painel. As
  > traits do Shield **falham abertas** quando não acham a classe na lista, e o caso conclui "a
  > permissão não é consultada" quando o que houve foi o arranjo consultar o painel errado.

- **Gates**: durável ✅ (vale para toda Page/Widget futura) · escopável ✅ (`app/Filament/**`) ·
  não-inferível ✅ (o default permissivo é invisível; a armadilha do trait vs. método é silenciosa) ·
  não-redundante ✅ (nenhuma rule atual diz isso; a §"Em Page, `canAccess()` sozinho basta" fala de
  quantos métodos sobrescrever, não de permissão)

### 2. Action de RelationManager não herda autorização de nada

- **Glob**: `app/Filament/**`
- **Onde**: seção nova em `.ai/rules/filament.md`, colada logo abaixo de
  §"`->authorize()` não é opcional", que já existe e trata do mesmo assunto pelo lado de import/export.
- **Texto proposto**:

  > ### E em RelationManager, nem a Action NATIVA está coberta
  >
  > `getDefaultActionAuthorizationResponse()` do RelationManager diz em comentário que
  > `AssociateAction`, `AttachAction`, `DetachAction` e `DissociateAction` **só** checam
  > `isReadOnly()` — não consultam método de policy nenhum
  > (`vendor/filament/filament/src/Resources/RelationManagers/RelationManager.php:348-353`, e o arm
  > do `match` em `:359`). `null` ali significa "sem opinião", que
  > `CanBeAuthorized::resolveIsAuthorized()` (`:106-107`) converte em **permitido**.
  >
  > Consequência medida no kit: quem podia abrir `/admin/tenants/{id}` podia vincular qualquer
  > usuário da instalação àquela organização — e o pivot `tenant_user` é exatamente o que
  > `User::canAccessTenant()` consulta para liberar `/app/{slug}`.
  >
  > Toda Action de RelationManager — nativa inclusive — leva `->authorize('{Permissao}:{Model}')`,
  > e a permissão nasce por `resources.manage[$resourceDoPainel]` em
  > `config/filament-shield.php`, que dá escopo de painel de graça.
  >
  > `->authorize()` com **argumento explícito não resolve** a policy que você espera:
  > `parseAuthorizationArguments()` (`CanBeAuthorized.php:80-89`) empurra o record — ou o model da
  > relação — para a frente dos argumentos, então `Gate::check('update', [$tenant])` numa
  > `headerAction` de `UsersRelationManager` resolve a `UserPolicy`, não a `TenantPolicy`. Use o
  > nome da permission.

- **Gates**: durável ✅ · escopável ✅ · não-inferível ✅ (é o oposto do que vale em Resource, e a
  única pista é um comentário no vendor) · não-redundante ✅ (a rule atual sobre RelationManager
  fala de o Shield não gerar permissão para ele, não de a Action nativa não autorizar)

### 3. Custom permission declara o painel dela

- **Glob**: `config/**`, `database/seeders/**`
- **Onde**: `.ai/rules/config.md` (existe) — uma seção curta, apontando para o enforço.
- **Texto proposto**:

  > ## Custom permission declara o painel dela
  >
  > `custom_permissions` do Shield **não tem noção de painel**:
  > `transformCustomPermissions()` (`vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityTransformers.php:88-112`)
  > lê a config e `getEntitiesPermissions()` faz merge das chaves na matriz de **todo** painel
  > (`FilamentShield.php:119`). Sem recorte, chave custom nova cai em `admin`, `infra`, `admin_app`
  > **e `panel_user`** — o over-grant silencioso descrito em `.ai/rules/filament.md` §4.
  >
  > Chave nova em `config('filament-shield.custom_permissions')` precisa de entrada em
  > `PapeisSeeder::paineisDasPermissoesCustomizadas()`. Sem entrada, ela não vai para papel nenhum
  > (fail-closed) e o caso CT-19 de `tests/Kit/PermissoesDeAcoesTest.php` fica vermelho com o nome
  > da chave.
  >
  > Action de **Resource** não passa por aqui: use `resources.manage`, que é escopado por painel.

- **Gates**: durável ✅ · escopável ✅ · não-inferível ✅ · não-redundante ✅

**Teto respeitado**: 3 candidatos. Dois deles são **atualização** de rule existente, não criação —
o índice `.ai/rules/index.md` não muda para eles; muda só se o candidato 3 for para um arquivo novo,
o que a proposta evita.

## Retrospectiva

**Funcionou bem**

- **Ler o `vendor/` antes de escrever a ADR** encontrou o que decidiu a entrega inteira: as
  permissões de Page e Widget **já existiam** e já estavam nos papéis. A leitura de
  `FilamentShield::getEntitiesPermissions()` cortou um passo de seeder e reduziu "criar 34
  permissões" para "criar 6". Se a wiki tivesse sido escrita a partir do que se esperava encontrar,
  o PRD teria mandado recriar uma matriz que existe.
- **A revisão adversarial pagou o custo com folga.** 21 achados, 19 aceitos, e o maior deles
  (RQ-01/RQ-07 sem barreira executável — R4/R5 cobriam uma lista fechada de 6 Actions escolhida pelo
  próprio agente) não teria aparecido em autorrevisão. Ele virou R10/CT-25, que é o único mecanismo
  da entrega que responde por "TODAS as actions".
- **Enforço estrutural em vez de 21 pares tem/não-tem.** CT-21/CT-22 (o concern presente) mais
  CT-23/CT-32 (o comportamento observável) cobrem 28 classes por preço fixo, e ficam vermelhos
  quando alguém cria classe nova. É a escada do Ponytail aplicada a teste sem cortar cobertura.
- **Rodar cada arquivo de teste assim que escrito**, em vez de tudo no fim. Cinco defeitos de
  arranjo (a memoização do Shield, o `Dashboard::class` resolvido no namespace errado, o `attach`
  precisando de `->table()`, o `Notification::fake()` antes do arranjo, os dois `actingAs()`) foram
  encontrados isolados, cada um com uma mensagem que apontava a causa.

**Faltou no plano**

- **O PRD não previu que o próprio arranjo de teste teria uma armadilha de vendor.** A memoização
  `scoped` + `once()` + facade do `FilamentShield` está documentada em `App\Support\Paineis` desde
  antes desta feature, e eu a citei nas ADRs — mas não liguei que qualquer caso que percorre painéis
  cai nela. Custou uma volta de investigação num teste que parecia acusar defeito de implementação.
  A rule proposta nº 1 deveria mencionar `noPainelDoShield()`.
- **Duas premissas de furo não sobreviveram à medição** (o `->visible()` do link e o mutante do
  `->visible()` vs `->authorize()`). As duas vinham da varredura inicial, e as duas foram escritas
  no PRD **sem** `file:line` do ponto que as sustentaria — que é exatamente o sinal de alerta que
  `.ai/rules/specs.md` nomeia. Se eu tivesse aberto `AiRunResource::canAccess()` ao escrever o passo
  5, o passo teria nascido diferente.
- **O `04` mediu mal o custo de cenário de recusa em Filament.** Escrevi quatro cenários de "a
  chamada é recusada" supondo `callAction()` como estímulo, e o helper do Pest não permite isso em
  Action oculta. O gate de camada do passo 7 deveria ter perguntado "que API prova isto?" e não só
  "que camada".

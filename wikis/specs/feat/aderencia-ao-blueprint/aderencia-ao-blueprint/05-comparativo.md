# Comparativo — o kit contra a norma do Blueprint

> Norma: [`05-norma-blueprint.md`](05-norma-blueprint.md) (43 normas). Este arquivo é a medição,
> norma por norma, com evidência em `arquivo:linha` ou em sonda executada. Onde a medição foi
> delegada a um sub-agente, o veredito foi **re-verificado** por mim nos pontos que decidem
> gravidade; onde discordei do agente, está dito.

## Como foi medido

| Frente | Método | Cobertura |
|---|---|---|
| Código (A–F, N-01..N-28) | sub-agente com acesso ao `app/` e ao `vendor/`, proibido de editar; 59 leituras | 21 PASS · 4 FINDING · 3 N/A |
| Testes (G, N-29..N-34) | sub-agente cruzando inventário × `tests/`; 33 leituras | 18 FINDING em 6 normas |
| Documentação (RQ-08) | sub-agente lendo os dois READMEs em blocos e cruzando com código, `route:list`, `composer.json`; 88 leituras | **39 divergências** |
| Instalação e opt-in (I, N-37..N-43) | **duas instalações reais** em `TESTES KIT/` via `composer create-project` do pacote publicado; servidor HTTP; sessão real via Playwright; sondas em processo | ver §I |
| Regressão da skill (H) | as 5 buscas dos checks com achado provável, re-rodadas | sem regressão |

Legenda de gravidade: **S** segurança · **A** arquitetura · **Q** qualidade · **D** documentação.

---

## A. Autorização

| ID | Veredito | Evidência | Correção |
|---|---|---|---|
| N-01 | **PASS** | 11 ações customizadas; 10 com `->authorize()` ou `->visible()` por permissão (`ConvitesTable.php:84`, `RoleResource.php:384`, `UsersRelationManager.php:124`, `ConvitesRecebidos.php:114,139,170`, `AprovacaoDeCadastro.php:107`, `ConvidaEmMassa.php:45`, `AcoesDeCriacao.php:66`). `lockSession` é ação sobre a própria sessão — N/A. `dashboardAiTasks` (`ListAiRuns.php:32-36`) não tem guarda própria, mas é `->url()` para rota com `can:ver-ai-tasks` no middleware (`config/ai-tasks.php:38`) e vive numa página já gateada pela mesma permissão | — |
| N-02 | **PASS** | os 4 `canDelete*()` do `/app` têm o par `get*AuthorizationResponse()` (v0.20.0). `ConfiguracoesDoKit::canEdit()` é contrato do plugin de settings (`SettingsPage.php:184` consulta direto). `AiRunResource::canCreate()` **é** consultado pela página (`CreateRecord.php:75` faz `abort_unless(canCreate())`), mas não pela `CreateAction` (`Page.php:312`); sem `CreateAction` registrada, é decorativo — nota, não achado | — |
| N-03 | **PASS** | zero `hasRole` em `app/Policies/` (14 arquivos) | — |
| N-04 | **FINDING · A** | `ProjetoResource` **não** sobrescreve `getEloquentQuery()`; depende do escopo global de `BelongsToTenant.php:64-70`, que **falha aberto** sem tenant. **Medido na instalação tenancy**: sem tenant corrente, `ProjetoResource::getEloquentQuery()->count()` = **4 de 4** (todas as organizações); `UserResource` do mesmo painel = **0** (`whereRaw('1 = 0')`, `:176`). Em request HTTP de painel o tenant sempre existe (middleware), então o alcance é **fora de request**: job, comando, ⌘K sem tenant | `getEloquentQuery()` fail-closed em `ProjetoResource`, igual aos dois irmãos |
| N-05 | **PASS** | 2 `DeleteBulkAction`; as 14 policies têm `deleteAny()` | — |

## B. Multi-tenancy

| ID | Veredito | Evidência | Correção |
|---|---|---|---|
| N-06 | **PASS** | `User.php:429` consulta a pivot. **Medido em HTTP real**: Carla (membro de `acme` e `globex`) → `/app/padrao/projetos` = **404**; `/app/inexistente` = 404; `/app/globex/projetos` mostra exatamente os 2 projetos de globex | — |
| N-07 | **PASS** | único `->unique()` no `/app` é em `users` (`App/Users/UserResource.php:205`), tabela **global** por desenho (docblock `:52-71`, e-mail único no sistema). O campo de tabela escopada usa `scopedUnique()` (`ProjetoResource.php:109`, motivo em `:36`) | — |
| N-08 | **PASS** | zero `withoutGlobalScopes()` sem argumento; a remoção real é cirúrgica (`Convite.php:525`) | — |
| N-09 | **PASS** | `projetos` (trait), `convites` (escopo explícito `:78-96`), `ai_runs`/`jobs-monitor`/`recycle_bin_items` só no `/infra` global | — |
| N-10 | **PASS** | nenhuma query em provider; só config e constante | — |

## C. Formulários e validação

| ID | Veredito | Evidência | Correção |
|---|---|---|---|
| N-11 | **FINDING · Q** | 6 `ignoreRecord: true` redundantes. Confirmado no vendor: `CanBeValidated.php:34` `$shouldUniqueValidationIgnoreRecordByDefault = true`, consumido em `:566` e `:598`. Arquivos: `Admin/Users/UserResource.php:52`, `RoleResource.php:111`, `TenantForm.php:50`, `AgenteIaForm.php:40`, `ProjetoResource.php:109`, `App/Users/UserResource.php:205` | remover o argumento nos 6 |
| N-12 | **PASS** | zero `->reactive(` | — |
| N-13 | **PASS** | zero `Filament\Forms\{Get,Set}` | — |
| N-14 | **FINDING · Q** | `ConfiguracoesDoKit.php:300` (`mail_port`) e `:376` (`paginacao_padrao`) são inteiros com `->numeric()` sem `->integer()` | `->integer()` nos dois |
| N-15 | **PASS** | zero componentes inexistentes | — |
| N-16 | N/A | único enum (`ProvedorSocial`) não vai a campo nem coluna | — |
| N-17 | **PASS** | zero `Checkbox::make`; 11 `Toggle` são preferência | — |
| N-18 | **PASS** | vendor `HasColumns.php:68-69`: default 1 coluna. As 9 `columns(2)` são filhas de schema de 1 coluna ou têm `columnSpanFull()`. Caso de risco `ConfiguracoesDoKit` (raiz 2 colunas do plugin) só tem `Tabs->columnSpanFull()` | — |

## D. Tabelas

| ID | Veredito | Evidência | Correção |
|---|---|---|---|
| N-19 | **FINDING · Q** | `RoleResource.php:246-248` `updated_at->dateTime()` sem `->sortable()`; as outras 9 colunas de data têm | `->sortable()` |
| N-20 | N/A | zero coluna editável instanciada | — |
| N-21 | **PASS** | toda coluna de status é `->badge()` com cor | — |

## E. Models

| ID | Veredito | Evidência | Correção |
|---|---|---|---|
| N-22 | **PASS** | `User::$hidden` = password, remember_token; `Convite::$hidden` = token, token_lembrete. 2FA mora em `breezy_sessions` (vendor) | — |
| N-23 | **PASS** | 5 models com `$fillable`; `Role` herda `$guarded = []` do Spatie, decisão registrada e mitigada por `Arr::only()` em `CreateRole.php:34,37` | — |
| N-24 | N/A | ver N-16 | — |

## F. Páginas e widgets

| ID | Veredito | Evidência | Correção |
|---|---|---|---|
| N-25 | **PASS** | 8/8 Pages de painel com `ExigePermissaoDaTela` | — |
| N-26 | **PASS** | 24/24 Widgets com `ExigePermissaoDoWidget` | — |
| N-27 | **PASS** | v0.20.0 | — |
| N-28 | **PASS** | `SettingsPage` do plugin, mesmo padrão do kit | — |

## G. Testes

O achado estrutural que explica a maioria: **existe sweep de autorização sobre `getPages()` e sobre
widgets (`PermissoesDeTelasTest.php:241,270,394`, `PermissoesDeWidgetsTest.php:207`), mas nenhum
sobre `getResources()`**. Cinco dos nove resources nunca tiveram a permissão revogada num teste.

| ID | Veredito | Evidência | Correção |
|---|---|---|---|
| N-29 | **FINDING · S** — e o sweep achou o que a falta dele escondia | O agente mediu "5 de 9 resources sem teste de autorização negativa". Escrito o sweep sobre `getResources()`, a primeira rodada dele revelou **três defeitos de autorização reais**, um explorável em request HTTP: **(a) 8 policies de modelo de vendor nunca registradas** — `Gate::getPolicyFor()` = `null` para Exception, AiRun, QueueMonitor, AuthenticationLog, Audit, ComposerReleasePackageSnapshot, CommandRecord, MailLog; o Laravel só descobre `App\Models\*`. Prova com controle, um teste por caso: `/infra/audits`, `/infra/mail-logs`, `/infra/queue-monitors`, `/infra/authentication-logs`, `/infra/exceptions`, `/admin/exceptions`, `/infra/composer-release-packages` = **200 com `ViewAny` revogada**; `/admin/users` (modelo do kit) = 403. Mais 2 policies de onboarding registradas pelo **vendor** com `return true` em tudo. **(b) `AiRunResource::canAccess()` só com o gate**, sem `parent::canAccess()` — `CanAuthorizeResourceAccess:19` chama `canAccess()`, então a policy nunca era consultada para o índice (F-01 em outra roupa). **(c) `ComposerReleasePackageResource` do vendor com `$shouldSkipAuthorization = true`** — `HasAuthorization:35-37` devolve `allow()` antes de olhar a policy. Em comum: permissão no banco, checkbox em `/admin/shield/roles`, e nada decidindo. Exatamente o que a RQ-04 pediu para garantir | `App\Support\PoliciesDeVendor` (10 `Gate::policy()`), `AiRunResource::canAccess()` com `&& parent::canAccess()`, subclasse do resource do Composer com a flag desligada **e a página junto** (a página do vendor aponta `$resource` para a classe do vendor), `resource(enabled: false)` no plugin. Sweep `PermissoesDeResourcesTest`: 47 casos, um por resource, com âncora de população e caso de registro de policy. Mutação: remover o registro derruba **19** |
| N-30 | **FINDING · Q** — 3 | `Admin/AgentesIa` sem validação; `Admin/Convites` só positivo (`ConviteTest.php:124,420`); `App/Users` `assertHasFormErrors()` sem chave (`AdminDaOrganizacaoTest.php:270`). Só 2 de 8 forms usam dataset | dataset de validação nos 3 |
| N-31 | **FINDING · Q** — 3 | `recusar` nunca atravessa a Action (só `$convite->recusar()` do model); `convitesRecebidos` só no inventário estático; `dashboardAiTasks` sem `assertActionHasUrl` | `callAction` para `recusar`; `assertActionVisible/Hidden` para `convitesRecebidos`; URL para `dashboardAiTasks` |
| N-32 | **FINDING · Q** — 6 de 7 | 7 filtros, 1 `filterTable()` (`RegistroAbertoTest.php:413`). Sem teste: `ativo` (AgentesIa, Tenants), `pendente` (Convites, com `->queries()` custom), `status`/`task`/`driver` (AiRuns) | `filterTable()` + `assertCanSee/CanNotSeeTableRecords` |
| N-33 | **PASS** | 12 asserções de existência, todas com regra de negócio | — |
| N-34 | **FINDING · Q** — 1 | `ConfiguracoesDoKitTelaTest.php:306` `assertFormSet` (`@deprecated` em `vendor/filament/forms/.stubs.php:11-13`) | `assertSchemaStateSet` |

## H. Regressão da skill de segurança

| ID | Veredito | Evidência |
|---|---|---|
| N-35 | **PASS** | A3: 6 `can*()`, os 4 do `/app` com o par novo, os 2 restantes analisados em N-02. A5: 0 páginas fora de painel sem o trait. B2: 4 restrições nos 4 `FileUpload`. C1: o único `{!! !!}` é o markdown do chat com `html_input: escape` |
| N-36 | **§5 — dica mantida** | `grep preventFilePathTampering app/Providers` = 0. Condição continua real: 3 `FileUpload` não-Spatie e `FILESYSTEM_DISK=local` |

## I. Instalação e opt-in

Duas instalações reais, do pacote **publicado** (`composer create-project gsferro/starter-kit-easy`),
em `TESTES KIT/padrao` e `TESTES KIT/tenancy`.

| ID | Veredito | Evidência |
|---|---|---|
| N-37 | **PASS** | `padrao`: instalação `--no-interaction` exit 0; migrations, seeders, assets, `npm run build`, `.snyk` removido pelo `--create-project`. Servidor: `/up`, `/`, `/app/login`, `/admin/login`, `/infra/login` = **200** |
| N-38 | **FINDING · D** | **10 chaves `KIT_*` lidas pela config e ausentes do README**: `KIT_HUB`, `KIT_TENANCY` (+`_LABEL`, `_LABEL_PLURAL`, `_SLUG`), `KIT_TABELA_*` (4), `KIT_COR_PRIMARIA_HEX`. Mais: `KIT_ADMIN_NAME` lida em `config/kit.php:592` mas ausente do `.env.example`; `KIT_REPOSITORY` lida em `:33`, ausente dos dois | documentar/incluir (ver §Docs) |
| N-39 | **PASS parcial** | `--create-project` medido (removeu o `.snyk`); `--no-interaction` medido. **Não medidos**: `--no-npm`, `--no-seed`, `--force`, `--custom` — ficam como lacuna declarada desta rodada; são cobertos por `CustomizadorDaInstalacaoTest` e `KitAdminTest` no CI |
| N-40 | **PASS** | `kit:tenancy --demo --force` numa instalação com git: `KIT_TENANCY=true`, `permission.teams`, `migrate:fresh`, 3 tenants, 4 usuários. `/app` anônimo → 302 login; logada, Carla cai em `/app/acme`; tenant alheio → 404 (ver N-06). **Nota**: sem `.git` o comando recusa com instrução de `git init` — é deliberado e documentado (README:938,1369,1700) |
| N-41 | **PASS** | matriz papel × tela na instalação: `master_global` abre tudo do `/admin` e `/infra`; `admin` abre só `/admin` (403 em `/infra`); `infra` o inverso; `panel_user` só `/app`. Sem tenancy, `/app/*` (users, convites, projetos) = 403 para todos, por `canAccess()` condicionado a `kit.tenancy.enabled` (`App/Users/UserResource.php:86-91`) — desenho |
| N-42 | **PASS** | **medido em HTTP real com sessão via Playwright**: `infra` logado, `/infra/pulse` = 200; `revokePermissionTo('View:Pulse')` no banco; recarregar = **403**. `View:ConfiguracoesDoKit` e `ViewAny:User` no `/admin`: 200 → 403 em sonda de Kernel |
| N-43 | **lacuna declarada** | `kit:admin`, `kit:update`, `kit:midia-privada`, `kit:convites-lembrar`, `kit:arte` não foram rodados nas instalações desta rodada. Têm suíte própria (`KitAdminTest`, `KitUpdate*`, `MidiaPrivadaTest`, `LembretesDeConviteTest`, `CapturaDeArteTest`) |

### Um falso achado, e o que ele ensinou

Durante N-42 eu anunciei que `Pulse` **não fechava** ao revogar a permissão e classifiquei como
achado sério de segurança. Era artefato da minha sonda: rodar os três painéis num **único processo**
faz o `once()` de `FilamentShield::getPages()` congelar o mapa no primeiro painel tocado, e
`PermissaoDaTela::permite()` devolve `true` quando a página não está no mapa (`:70-72`). Em request
HTTP real, cada request nasce no painel certo — e a sessão via Playwright deu **403**.

O que sobra disso é real, e vai para §5 como dica: `permite()` **falha aberta por desenho** quando a
chave não resolve. Hoje inalcançável em request; alcançável se algum provider tocar o mapa do Shield
antes do `SetUpPanel`. Fechar por default (`false` quando não há chave, exceto para páginas
declaradamente públicas) custa uma linha e elimina a classe inteira.

## Documentação (RQ-08) — 39 divergências

Contagem do sub-agente, com as que eu re-verifiquei marcadas ✓:

| Tipo | Qtd | Exemplos |
|---|---|---|
| **Código tem, README não** | 15 | `KIT_HUB` ✓, `KIT_TABELA_*` ✓, `KIT_TENANCY*` ✓, `KIT_COR_PRIMARIA_HEX` ✓, flags `--no-npm/--no-seed/--no-support/--create-project` do `kit:install`, `--repo` do `kit:update`, `--force` do `kit:tenancy`, opções do `kit:admin`, 4 pacotes de produção e 7 de dev fora das tabelas, **o hub de cartões inteiro sem seção** |
| **Número/afirmação errada** | 16 | "4 comandos `kit:*`" (são 7 ✓), "48 migrations" (54), "411 casos" (≥704 declarações), "20 widgets" (24), "quatro abas" na tela de configurações (são **6** ✓ — Registro e Login faltam da tabela), "quatro papéis" com tabela de cinco, "uma exceção no phpstan.neon" (duas), exceções "só no `/infra`" (rota existe nos 3 painéis; a barreira é permissão) |
| **PT ≠ EN** | 5 | **o bloco Windows/sem-TTY inteiro (README.md:35-72) não existe em inglês**; `KIT_DEMO` só em PT; bloco de comandos dessincronizado |
| **README cita, código não tem** | 2 | `KIT_ADMIN_NAME` fora do `.env.example` ✓; `KIT_ART` é variável de teste |

**Duas afirmações do sub-agente caíram na re-verificação** e saem da conta: "`@laravel/multiplex` não
está no `package.json`" — está (`grep -c multiplex package.json` = 1); e "`general.md` não está no
`index.md`" — está (`.ai/rules/index.md:12`). O total honesto é **37**, não 39. Fica registrado
porque é o mesmo tipo de erro que a auditoria cobra do kit: afirmação sem a segunda medição.

A lista completa, com `arquivo:linha` dos dois lados, está em [`05-divergencias-readme.md`](05-divergencias-readme.md).

## Rules (RQ-09)

14 arquivos lidos por título e os candidatos a conflito abertos:

| Rule | Contra o Blueprint | Veredito |
|---|---|---|
| `filament.md` → "Em Page, `canAccess()` sozinho basta; em Resource são dois métodos" | parece contradizer o F-01 (v0.20.0) | **coerente**: a rule é sobre **acesso à tela e navegação**, não sobre autorização de ação. O F-01 é outra camada. Ganha uma frase remetendo a `get*AuthorizationResponse()` para ação |
| `filament.md` → "Page, Widget e Action novos nascem com a permissão consultada" | = N-25/N-26/N-01 | **coerente** e é a rule que produziu 100% de cobertura em pages/widgets. **Falta o irmão para Resource**: a mesma exigência não está escrita para `getResources()`, e é exatamente onde N-29 achou 5 buracos |
| `models.md` → "`papelDoPainel()` é exibição, nunca autorização" | = N-03 | coerente |
| `testes.md` → "Uma tela aberta não é uma tela que grava" | = gate de tela de escrita | coerente |
| nenhuma rule sobre `unique`/`ignoreRecord` | N-11 | **lacuna**: sem rule, o `ignoreRecord: true` volta no próximo scaffold |
| nenhuma rule sobre fail-closed em `getEloquentQuery()` de resource do `/app` | N-04 | **lacuna**: dois resources fecham e um não, e nada escrito decide |

## Placar

| Peso | FINDING | Onde |
|---|---|---|
| **S** | 1 achado, 3 defeitos | N-29: 10 policies de vendor mortas (8 não registradas + 2 do vendor com `true`), `AiRunResource::canAccess()` sem o pai, resource do Composer com `$shouldSkipAuthorization = true` |
| **A** | 1 | N-04 (`ProjetoResource` falha aberto sem tenant) |
| **Q** | 7 | N-11, N-14, N-19, N-30, N-31, N-32, N-34 |
| **D** | 1 + 37 | N-38 e as divergências dos READMEs |
| **§5** | 2 | N-36 (`preventFilePathTampering`), `permite()` fail-open — **este foi fechado** (ADR-02) |

**Um achado explorável em request HTTP hoje, e é o que a RQ-04 pediu para garantir.** Qualquer
usuário com o papel `infra` via as trilhas de auditoria, e-mails, filas, logins e exceções mesmo
com essas permissões desmarcadas no seu papel; qualquer `admin` via as exceções e o onboarding. A
barreira que existia era `canAccessPanel()` — dentro do painel, a matriz de permissões dessas nove
telas era decorativa. A causa é uma só e é silenciosa: o Laravel não descobre policy para modelo de
vendor, e ninguém registrava. O sweep que não existia é o que achou; a rule e o teste de registro
são o que impede de voltar.

A primeira versão deste comparativo dizia "nenhum achado explorável em request HTTP hoje". Estava
errada, e estava errada porque a medição do agente parou em "falta teste" sem escrever o teste. A
lição fica no `03-progresso.md`.

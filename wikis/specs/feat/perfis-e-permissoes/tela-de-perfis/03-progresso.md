# Progresso — Tela de perfis (papéis) do /admin

## 1. Rótulo e breadcrumb do recurso (RQ-01, RQ-02, RQ-03)

- [x] `AdminPanelProvider`: `FilamentShieldPlugin::make()` com `modelLabel`/`pluralModelLabel`/`navigationLabel`
- [x] `RoleResource::getRecordTitle()` devolvendo `Papeis::rotulo()`

## 2. Coluna de quantidade de usuários (RQ-04)

- [x] `TextColumn::make('users_count')` com contagem `distinct`, badge e `sortable()`

## 3. Slide-over com os usuários do papel (RQ-05)

- [x] `acaoDeUsuarios()` com `slideOver()`, `authorize('view')` e `modalSubmitAction(false)`
- [x] `usuariosDoPapel()` com `distinct()`
- [x] Estado vazio próprio (`EmptyState`)
- [x] Log no channel `autenticacao`

## 4. Tab vertical de painéis + contador por grupo (RQ-07, RQ-10)

- [x] `getResourceEntitiesSchema()` devolvendo `Tabs::make('paineis')->vertical()`
- [x] Badge `selecionadas/total` no tab de cada painel
- [x] Badge `selecionadas/total` na seção de cada Resource

## 5. Rótulo de papel em toda exibição (RQ-06)

- [x] `UltimosUsuariosCadastrados::rotuloDosPapeis()`
- [x] `UsuariosPorPapel::getItems()`
- [x] `UsersRelationManager::acaoDePapeis()` (opções do Select)
- [x] `App/Resources/Users/UserResource` (`getOptionLabelFromRecordUsing`)
- [x] Docblock de `app/Support/Papeis.php` com a contagem atualizada

## 6. `uuid` na rota do papel (RQ-08, RQ-09)

- [x] Migration `add_uuid_to_roles_table` (nullable → backfill → unique → limpar cache)
- [x] `App\Models\Role` usando `TemUuid`
- [x] `tests/BrowserTenancy/CapturaDeArteTest.php` de `getKey()` para `getRouteKey()`
- [x] `tests/Kit/PaineisTest.php` (`EditRole`) de `getKey()` para `getRouteKey()`
- [x] `database/seeders/PapeisSeeder.php` passa a escrever pelo model configurado (achado em execução — ver Desvios)
- [x] Auditoria de RQ-09 — **e ela achou outros três**, ao contrário do que esta linha dizia
  antes (ver Desvios do Plano, item 10)

## 7. Guard como seleção (RQ-11)

- [x] `Select::make('guard_name')` com opções de `config('auth.guards')`, `required()`

## Testes

- [x] `tests/Kit/TelaDePapeisTest.php` — CT-01, CT-02, CT-03, CT-04, CT-05, CT-07, CT-08, CT-15, CT-16, CT-18, CT-19, CT-20
- [x] `tests/Kit/UuidDoPapelTest.php` — CT-11, CT-12, CT-13, CT-14, CT-14b
- [x] `tests/Kit/ExibicaoDePapeisTest.php` — CT-09 (acrescentado ao arquivo existente)
- [x] `tests/Tenancy/TelaDePapeisTenancyTest.php` — CT-06, CT-10
- [x] `tests/Browser/PapeisTest.php` — CT-B01, CT-B02
- [x] Contraprova de CT-07 (ação **visível** para quem tem `View:Role`) — acrescentada em execução

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `vendor/bin/phpstan analyse --no-progress`
- [x] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy`
- [x] `composer test:browser`
- [x] Roteiro "Desenhado × Implementado" do `05` preenchido
- [x] `git commit` (agrupado por assunto)

## Degradações e desvios declarados

### Playwright MCP não usado (RQ-12) — desvio deliberado

O requisito pede *"use o mcp do playwrite para acessar a pagina do form"*. Não foi usado: o MCP do
Playwright desta sessão é uma **instância única de navegador compartilhada com outros agentes
rodando em paralelo**, e usá-la colide com eles. A validação de UI desta entrega usa
`pest-plugin-browser` (`tests/Browser/PapeisTest.php`), que sobe servidor próprio in-process.

A inspeção visual da tela e as skills de design ficam para o agente principal, serializadas depois
do merge. RQ-12 está marcada como **fora desta entrega** em `00-requisito.md` e como **não atendida**
na `## Cobertura do Requisito` do PRD — não como atendida por substituto.

### Item recortado para outra feature

O requisito original do usuário tinha um item a mais — *"ver quais outras modais ainda não tem
permissões... TODAS as telas, links e actions precisam ter permissão específica"*. O coordenador o
separou para a feature `feat/permissoes-de-telas-e-acoes`, que roda em paralelo em outro worktree.
Nada dele foi implementado aqui. Registrado em `00-requisito.md` → Fora desta entrega.

### `search-docs` do Laravel Boost

A instrução inicial desta sessão dizia que o MCP do Boost estava indisponível; a correção do
coordenador informou que voltou. Na prática, as tools do Boost (`search-docs`, `database-schema`,
`database-query`) **não ficaram alcançáveis** nesta sessão de sub-agente: o `ToolSearch` não as
encontra por nome (`select:mcp__laravel-boost__search-docs`) nem por palavra-chave, mesmo depois de
o servidor anunciar as instruções dele.

Fallback usado, na ordem que a skill manda:

1. **Vendor source com `file:line`** — a fonte mais forte, e a que `.ai/rules/specs.md` exige. Toda
   afirmação sobre comportamento de pacote nesta wiki cita arquivo e linha:
   `HasShieldFormComponents.php:122-133` e `:209` (nome e reatividade do CheckboxList),
   `Resource/Concerns/HasLabels.php:105-108` (título do registro),
   `plugin-essentials/Concerns/Resource/HasLabels.php:11-19` (delegação de label),
   `spatie/.../Models/Role.php:100-109` (relação `users`),
   `filament/support/.../CanAggregateRelatedModels.php:62-67` (`counts()` aceita closure),
   `filament/schemas/.../Tabs.php:246-256` (`vertical()`),
   `filament/schemas/.../Section.php:159` (`afterHeader()`),
   `filament/infolists/.../RepeatableEntry.php:64-95` (aceita Model no state),
   `filament/actions/.../InteractsWithActions.php:718-750` (o schema da action recebe o record),
   `create_permission_tables.php:88-93` (PK de `model_has_roles` com `team_id`).
2. **Doc oficial do Filament 5 por `WebFetch`** — `https://filamentphp.com/docs/5.x/schemas/tabs`
   (confirmou `vertical()` e `badge()`) e `https://filamentphp.com/docs/5.x/actions/modals`
   (confirmou `slideOver()`, `modalSubmitAction(false)` e entradas de infolist dentro de `schema()`).
3. **Verificação empírica** — o SQL gerado pelas duas formas de `counts()` foi conferido com
   `php artisan tinker --execute '… toRawSql()'` antes de a ADR-04 ser escrita, em vez de descrito
   de memória.

**Resolvido no fim da entrega.** O MCP do Boost passou a responder, e o `search-docs` foi
consultado (`filament/filament@5.x`) para os quatro pontos que mais dependiam de doc: validação de
Select, `Tabs` vertical com badge, `slideOver()` + `modalSubmitAction(false)` com entradas de
infolist, e o ciclo de vida de Action. Ele **confirmou** `afterFormFilled` como hook oficial
(*Actions → Create → Lifecycle hooks*) e o `slideOver()`; sobre validação de Select a doc é um
stub (*"Select validation"* sem corpo), então ali a leitura do `vendor/` continua sendo a evidência
mais forte — e é ela que está citada no código.

Consequência: a exigência formal da skill foi cumprida, mas **atrasada** — as decisões já estavam
tomadas quando a tool voltou. O que salvou a entrega não foi a doc, foi a regra de
`.ai/rules/specs.md`: citar `file:line` do `vendor/` para toda afirmação sobre pacote. Os dois erros
de fato desta wiki foram pegos por releitura de vendor, não por doc.

**Armadilha registrada: o `database-query` do Boost aponta para o banco do repositório RAIZ**
(`starter-kit-easy/database/database.sqlite`), não para o do worktree. Um `SELECT ... FROM roles`
por ele devolveu `no such column: uuid` **depois** de a migration ter rodado com sucesso aqui — o
que parece defeito da migration e é diferença de banco. A verificação do backfill foi feita pelo
`php artisan tinker` do próprio worktree: 5 papéis, 0 uuid nulo, 5 uuid distintos, índice
`roles_uuid_unique` criado e os índices anteriores (`roles_painel_index`,
`roles_name_guard_name_unique`) intactos.

### Divergência entre a skill e a Project Rule do projeto

A `feature-test-design` sugere `pest --parallel --tia` como comando padrão.
**Venceu `.ai/rules/testes-browser.md`**: neste projeto `--parallel` derruba 4 dos 11 cenários de
navegador e, sem PCOV, o `--tia` não termina (medido, abortado após 35 min). Os comandos desta wiki
são `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` e `composer test:browser`.

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| "Filament 4, conforme `CLAUDE.md`" | `composer show --direct` → `filament/filament 5.7.6`, Laravel 13.25.0, Pest 5.1.1, Shield 4.3.1 | toda a wiki escrita contra a 5.x; `CLAUDE.md` fica desatualizado (candidato a correção fora desta entrega) |
| `Papeis::rotulo()` cobre "sete telas" (docblock `:16-19`) | são **doze** pontos usando o helper e **cinco** exibindo a chave crua | passo 5 do PRD lista os dezessete; o docblock é atualizado no próprio passo |
| a coluna de usuários é `->counts('users')`, como `permissions_count` | `permissions` é `belongsToMany` sem coluna extra na PK; `model_has_roles` tem `team_id` na PK, então a mesma pessoa em duas organizações são duas linhas | ADR-04 escrita; `distinct` no plano; CT-06 criado em `tests/Tenancy` |
| só o `CapturaDeArteTest` usa `getKey()` em URL de papel | `tests/Kit/PaineisTest.php:236` passa `$papel->getKey()` a `Livewire::test(EditRole::class, ['record' => …])`, e o route binding do componente resolve por `getRouteKeyName()` | passo 6 do PRD ganhou a linha; sem ela o CT existente quebraria e pareceria defeito da feature |
| `Section` do Filament tem `badge()` | não tem; o que existe é `afterHeader()` (`Section.php:159`), que aceita componente | passo 4 do PRD usa `afterHeader([Text::make(...)->badge()])` |
| entradas de infolist não funcionam dentro de `Action::schema()` | funcionam: `InteractsWithActions::getMountedActionSchema()` monta o schema com `->model($record)` (`:735-737`) | passo 3 do PRD usa `RepeatableEntry`/`TextEntry` direto, sem Blade custom |
| `HasUuids` transformaria a PK de `roles` em string | `HasUniqueStringIds::getKeyType()`/`getIncrementing()` só mudam quando a **chave primária** está em `uniqueIds()`; `TemUuid::uniqueIds()` devolve `['uuid']` | ADR-03 registra que a PK continua `id` int, e as FKs de `model_has_roles`/`role_has_permissions` não são afetadas |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | não criar channel de log novo — reusar `autenticacao` | sim | `01`, seção Channel de Log |
| 2 | não criar Blade nem componente próprio para a lista de usuários — `RepeatableEntry` nativo resolve | sim | `01`, passo 3 |
| 3 | não paginar nem buscar dentro do slide-over | sim (marcado `ponytail:`) | `01`, passo 3 |
| 4 | não trocar o slug da rota — nenhuma cláusula pede e quebraria cinco arquivos de teste | sim | ADR-02 |
| 5 | não criar coluna de rótulo em `roles` — o rótulo é derivado | sim | `00`, Fora desta entrega |
| 6 | não escrever `pest --arch` para RQ-09 — a auditoria manual não achou outro caso | sim | `00`, Ambiguidades RQ-09 |
| 7 | não criar `RoleFactory` — dois arquivos de teste, `Role::create()` basta | sim | `04`, Setup Global |
| 8 | não criar `App\Support\ContagemDePermissoes` — dois métodos privados no Resource bastam | sim | `01`, passo 4 |
| 9 | reaproveitar o CT existente de agrupamento por painel em vez de escrever CT-17 novo | sim | `04`, R7 |
| 10 | usar `->state()` em vez de `withCount` na coluna (menos SQL bruto) | **recusada**: seria N+1 na listagem, e a versão de uma consulta custa a mesma linha | ADR-04, M3 |

## Auditoria Pós-Implementação (`/ponytail:ponytail-review` no diff)

Delegada a um sub-agente que não escreveu o código, com o contrato de caçar **só**
over-engineering. Devolveu 12 achados. Cada um foi conferido no `vendor/` **antes** de aplicar —
`.ai/rules/specs.md` registra que cinco de cinco prescrições auditadas em releases anteriores
estavam erradas, e neste caso duas de doze estavam.

### Aplicados

| # | Achado | O que mudou | Verificação |
|---|---|---|---|
| 1 | **o `->in()` do guard era redundante, e o comentário que o justificava estava factualmente errado** | linha removida; o comentário no ponto foi reescrito com o fato certo | `Select::getInValidationRuleValues()` (`vendor/filament/forms/src/Components/Select.php:1787-1811`) devolve `[]` quando o state não casa com nenhuma opção, e `CanBeValidated::getInValidationRule()` (`:808-815`) o transforma em `Rule::in([])` — que reprova qualquer valor. O `->in()` nosso **sobrescrevia** essa lógica por uma pior (a nativa também cobre opção desabilitada). CT-19 continua verde sem a linha |
| 2 | **o log do slide-over era código morto** | saiu de `->action()` e foi para `->afterFormFilled()` | com `->modalSubmitAction(false)` não existe botão que dispare `callMountedAction`, e "Fechar" desmonta a action. `afterFormFilled` é chamado por `InteractsWithActions::mountAction()` logo depois do `mount()` (`vendor/filament/actions/src/Concerns/InteractsWithActions.php:185-194`), uma vez por abertura. **CT-08 era verde falso** — passava por usar `callAction()`, que força o `->action()` que a tela nunca dispara. O caso passou a usar `mountAction`, que é o que o clique faz |
| 3 | `esquecerCacheDePermissoes()` reimplementava 12 linhas | `app(PermissionRegistrar::class)->forgetCachedPermissions()` | `vendor/spatie/laravel-permission/src/PermissionRegistrar.php:136-142` — resolve a store sozinho **e** limpa o índice de wildcard, que a versão manual da migration do spatie deixa para trás |
| 4 | `dropUnique` com nome de índice escrito à mão no `down()` | só `dropColumn('uuid')` | índice de coluna única cai com a coluna em todos os drivers que o Laravel suporta, e o nome à mão acoplava a convenção do Laravel a uma tabela cujo nome vem de config |
| 5 | contador da seção duplicava a lógica de `selecionadas()`/`totalDe()` | passou a chamar os dois helpers com `[$entity]` | — |
| 6 | `usuariosDoPapel()` rodava **3 consultas idênticas** por abertura do slide-over (o `->state()` e os dois `->visible()`) | `once()` em volta do corpo | o modo de falhar era invisível: a tela ficava certa |
| 7 | `if ($record === null)` de quatro linhas no `getRecordTitle()` | uma expressão ternária | — |

### Recusados, com motivo

| # | Achado | Por que não |
|---|---|---|
| 8 | tipar `usuariosDoPapel(Role $record)` e apagar a guarda `instanceof SpatieRole` + o import | a guarda existe porque `permission.models.role` é **config**: o Resource inteiro (`getModel()`, `CreateRole@afterCreate`, `EditRole@afterSave`) já tem a mesma checagem, com o mesmo comentário. Ser inconsistente com o padrão que o próprio arquivo estabelece custa mais que quatro linhas — e o comentário no ponto passou a dizer por que aqui o retorno é `[]` e lá é exceção |
| 9 | cortar a linha `'um usuário' => 1` do dataset de CT-04 | é a `borda+1` da análise de valor limite. O par (`borda`, `borda+1`) é a forma canônica da técnica, e uma linha de dataset é o insumo mais barato do conjunto |
| 10 | cortar as duas linhas de `visualização` de CT-12 | RQ-09 diz "acessar **ou** editar", então as duas são cláusula, não variação. E são duas Pages diferentes (`ViewRole`/`EditRole`): quem sobrescrever a resolução numa delas passa por um conjunto que só cobre a outra |
| 11 | CT-03 perde alvo se o `getRecordTitle()` for simplificado | não perde: ele afirma que o ramo nulo devolve o rótulo singular. Um `getRecordTitle()` que estoure com `$record` nulo derruba a busca global do painel, e é esse o caso |

### O que a auditoria NÃO achou

Import não usado: nenhum, nos dez arquivos PHP tocados. Abstração especulativa nova: nenhuma —
`selecionadas()`, `totalDe()`, `guardsDaAplicacao()`, `acaoDeUsuarios()` e `usuariosDoPapel()` são
extração de closure inline ou têm mais de um chamador.

## Blockers

- nenhum. A feature fechou sem impedimento.

## Desvios do Plano

1. **Passo 7 (guard) ganhou uma barreira de servidor que o plano não previa.** O plano tratava
   `Select::make('guard_name')->options(...)` como suficiente. Não é: `Select::setUp()`
   (`vendor/filament/forms/src/Components/Select.php:147-166`) só configura placeholder,
   transformação de opções e actions de sufixo — **nenhuma regra `in:`**. Sem `->in()` explícito, um
   `guard_name` forjado no state do Livewire grava sem erro e o Select vira barreira apenas de UX.
   É a mesma dobradinha UX+servidor que `ConviteResource::role_id` já usa. CT-19 é o caso que
   reprova a versão sem a linha.

2. **Passo 6 cresceu: `PapeisSeeder` escrevia papel pela classe errada.** Ver Notas de
   Implementação, item 1 — é o achado mais caro da entrega.

3. **A expressão SQL da contagem ficou literal, não montada.** O plano escrevia
   `new Expression('count(distinct '.$query->getModel()->getQualifiedKeyName().')')`. O PHPStan
   reprova (`argument.type`: `Expression` exige `float|int|literal-string`) e a regra está certa —
   SQL por concatenação é por onde injeção entra. Virou `'count(distinct users.id)'`, literal, com
   o acoplamento a `auth.providers.users.model` documentado no ponto: ele falha **alto** ("no such
   column: users.id" na primeira abertura da listagem), não em silêncio. ADR-04 atualizada.

4. **Métodos privados estáticos passaram a ser chamados por `self::`, não `static::`.** PHPStan
   (`staticClassAccess.privateMethod`). É o padrão que o arquivo já usava para
   `self::colunasDaGrade()`.

5. **O rótulo da action é "Ver usuários", não "Usuários".** O plano não fixava o texto. "Usuários" é
   também o rótulo premissado da coluna nova, e no modo estrito do Playwright um
   `click('Usuários')` casaria os dois — a colisão prevista em `05` virou o argumento para os dois
   textos serem diferentes.

6. **CT-10 não pôde ser provado por `assertSee`.** O `Select` de papéis da organização é
   `->searchable()`, e o Filament não imprime as opções no HTML inicial (elas vão por requisição
   separada). O oráculo passou a ser `assertSchemaComponentExists('roles', 'mountedActionSchema0',
   fn (Select $c) => …)` sobre `getOptions()`. Um `assertSee` ali ficaria vermelho com a tela
   certa, e um `assertDontSee` da chave ficaria **verde com a tela errada**, que é pior.

7. **CT-02 arranja com `firstOrCreate`, não `create`.** Três dos quatro papéis do dataset já vêm do
   `PapeisSeeder`, e o `create` do spatie lança `RoleAlreadyExists`. O que o caso mede é a tradução
   da chave, e para isso tanto faz quem criou a linha.

8. **CT-B02 filtra a tabela por `?search=panel_user`.** "Ver usuários" aparece uma vez por linha, e
   sem o filtro o clique cairia numa linha arbitrária. O parâmetro é o `#[Url(as: 'search')]` de
   `vendor/filament/filament/src/Resources/Pages/ListRecords.php:48-49`.

9. **RQ-12 não foi atendida** (Playwright MCP + skills de design). Ver "Degradações e desvios
   declarados", acima. Não há substituto: `pest-plugin-browser` prova comportamento, não diagramação.

10. **RQ-09 é parcial, e a versão anterior daquela linha era uma afirmação FALSA.** Ela dizia
    "auditoria: nenhum outro `id` em URL de registro". O quality gate rodou `route:list` e achou
    três: `infra/exceptions/{record}`, `infra/audits/{record}` e
    `infra/command-center/definitions/{record}/edit`, todas resolvendo por PK inteira — o model de
    exceções do vendor não tem `uuid` nem `getRouteKeyName()`
    (`vendor/bezhansalleh/filament-exceptions/src/Models/Exception.php:33`).

    RQ-09 é cláusula geral ("NUNCA ... qualquer registro"), então isto é **escopo não entregue**, e
    não engano de leitura. Fica como **dívida declarada**: são três models de **vendor**, e trocar a
    route key deles é entrega própria — precisa de migration na tabela do pacote, ou de um model
    intermediário como o `App\Models\Role` desta feature, e de decidir o que acontece num
    `vendor:publish`. Não bloqueia RQ-08, que é sobre `roles` e está fechado.

    O que a auditoria original fez de errado: procurou `getKey()` em URL dentro de `app/` e
    `tests/`, e não perguntou ao **roteador**. `php artisan route:list --path=infra` responde em
    segundos e é a fonte certa.

11. **Três afirmações falsas de docblock, achadas pelo gate.** O docblock do CT-19 dizia que o
    `Select` não valida `in:` sozinho (diz o oposto do código, e o código está certo);
    `Papeis::rotulo()` documentava `master_global → "Master Global"` quando o mapa devolve
    "Administrador Geral"; e o docblock de `Papeis` dizia "dezessete" cópias quando eram dezenove.
    As três são da classe que `.ai/rules/specs.md` persegue — conclusão certa, justificativa errada
    — e a primeira é a pior, porque induziria o próximo agente a reintroduzir o `->in()` que a
    auditoria do diff acabou de remover.

## Notas de Implementação

1. **`PapeisSeeder` escrevia papel pela classe do vendor, e por isso todo papel semeado nasceria
   com `uuid` nulo.** `database/seeders/PapeisSeeder.php:12` importava
   `Spatie\Permission\Models\Role`; só `App\Models\Role` tem `App\Traits\TemUuid`. As duas
   classes escrevem na **mesma tabela**, e o hook de `creating` do `HasUuids` é por classe.

   Consequência, se tivesse passado: os cinco papéis do kit nascem sem `uuid`, a rota deles não
   resolve, a tela de alteração responde 404 e o `EditAction` da listagem gera URL sem parâmetro.
   Nada disso dá erro no seeder — o seeder termina verde.

   Descoberto porque `tests/Kit/PaineisTest.php` ("salva o painel do papel na edicao…") passou a
   falhar com `No query results for model [App\Models\Role] 5` — a mensagem aponta o **id**, que é
   o sintoma de o `getRouteKey()` ter devolvido `id` em vez de `uuid`. O teste estava certo e a
   arrumação dele estava errada.

   Seguindo `.ai/rules/specs.md` ("ao encontrar defeito numa fronteira, varra o padrão no repo
   inteiro antes de consertar aquele ponto"), a varredura
   (`grep -rn 'Spatie\Permission\Models\Role'`, com a tool Grep e não com o grep do Git Bash —
   ver a nota de memória sobre pipe vazio) achou **cinco** arquivos escrevendo ou roteando pela
   classe do vendor: o seeder, `tests/Kit/PaineisTest.php`,
   `tests/BrowserTenancy/CapturaDeArteTest.php`, `tests/Kit/GraficosDoDashboardTest.php` e
   `tests/Kit/ConviteEmMassaTest.php` (este já usava o model do kit). Os quatro primeiros foram
   corrigidos de uma vez.

   Os pontos que **apenas leem** pela classe do vendor (`UsuariosVisaoGeralStats`,
   `Admin/.../UserResource`) ficaram como estão: leitura não precisa do hook, e trocar por trocar
   aumentaria o diff sem fechar nada.

   O guarda permanente é CT-11 ("preenche o uuid dos papeis que ja existiam"): ele reprova qualquer
   caminho futuro que crie papel sem `uuid`, seja qual for a classe.

2. **A PK de `roles` continua `int`, e isso é consequência de `uniqueIds()`, não sorte.** O
   `HasUniqueStringIds` do Laravel só troca `getKeyType()`/`getIncrementing()` quando a chave
   primária está em `uniqueIds()`; `TemUuid::uniqueIds()` devolve `['uuid']`. Trocar `TemUuid` por
   um `HasUuids` puro faria a PK virar string e as foreign keys de `model_has_roles` e
   `role_has_permissions` quebrarem. Tem caso próprio em `tests/Kit/UuidDoPapelTest.php`
   ("mantem a chave primaria do papel numerica"), porque nenhum outro caso da suíte falharia por
   isso de forma legível.

3. **O contador de permissões é reativo de graça.** Nenhum `->live()` novo foi escrito: o
   `CheckboxList` do Shield já é `->live()`
   (`vendor/bezhansalleh/filament-shield/src/Traits/HasShieldFormComponents.php:209`), então toda
   marcação re-renderiza o formulário e reavalia os closures de badge. Por isso CT-16 é teste de
   **componente** e não de navegador — o número está no HTML que o Livewire devolve.

4. **`Get $get` é injetável por tipo em closure de componente de schema**, confirmado em
   `vendor/filament/schemas/src/Components/Component.php:104-119`: o ramo que resolve `Get::class`
   só é ignorado quando o parâmetro é um `Model`, o que não é o caso.

5. **`Section` do Filament não tem `badge()`.** O que existe é `afterHeader()`
   (`vendor/filament/schemas/src/Components/Section.php:159`), que aceita componente — daí o
   `Text::make(...)->badge()`.

6. **Entradas de infolist funcionam dentro de `Action::schema()`**, porque
   `InteractsWithActions::getMountedActionSchema()` monta o schema com `->model($record)`
   (`vendor/filament/actions/src/Concerns/InteractsWithActions.php:735-737`). Foi o que dispensou
   qualquer Blade nova (RQ-13).

7. **`assertSee` do `pest-plugin-browser` confere visibilidade**
   (`vendor/pestphp/pest-plugin-browser/src/Api/Concerns/MakesElementAssertions.php:53-58`), e é
   exatamente isso que torna CT-B01 possível: os três painéis estão no DOM ao mesmo tempo, e só um
   está visível.

## Candidatos a Project Rule (PROPOSTA — decisão do usuário)

A gravação é do usuário; o agente principal a executa. Nada foi escrito em `.ai/rules/`.

1. **`Role` conta pessoas, não linhas de pivot** — `glob: app/Filament/Admin/Resources/Roles/**, app/Models/Role.php`
   Contagem ou listagem de usuários de um papel precisa de `distinct`: com `permission.teams`, a PK
   de `model_has_roles` inclui `team_id`, então a mesma pessoa com o mesmo papel em duas
   organizações são duas linhas, e `count(*)` mostra 2 para uma pessoa.
   Evidência: ADR-04 + `create_permission_tables.php:88-93` + `spatie/.../Models/Role.php:100-109`.
   Gates: durável ✅ | escopável ✅ | não-inferível ✅ | não-redundante ✅

2. **Título de registro é rótulo, não chave** — `glob: app/Filament/**/Resources/**`
   Resource cujo `$recordTitleAttribute` aponta para uma coluna que é **chave** (`roles.name`,
   qualquer slug/enum) precisa sobrescrever `getRecordTitle()`. O default devolve o atributo cru
   (`Resource/Concerns/HasLabels.php:105-108`) e é ele que alimenta breadcrumb, título da página de
   edição e busca global — três telas mostrando a chave sem nada acusar.
   Gates: durável ✅ | escopável ✅ | não-inferível ✅ | não-redundante ✅

3. **`#[Override]` só para método de classe pai** — já está registrado *no arquivo*
   (`RoleResource.php:190-191`), não em rule. Proposta: **não** virar rule — é armadilha de um
   arquivo, e o comentário no ponto certo é mais eficaz que um imposto de contexto em
   `app/Filament/**`. Registrado aqui como candidato **recusado pelo próprio agente**, para não
   parecer omissão.

## Quality Gate (step 8)

Executado por agente independente, que não escreveu o código nem a wiki. Relatório completo em
`06-relatorio-qa.md`.

**Veredito: APROVADO COM DÉBITO.** Um ciclo, sem reciclagem — nenhum achado exigiu reimplementar um
passo do PRD.

| Achado | Severidade | Destino | Situação |
|---|---|---|---|
| 1 — chave crua na confirmação do aceite de convite | média | implementação | **corrigido**, com caso próprio |
| 2 — chaves cruas no bloco de diagnóstico do 403 | baixa | não-defeito | escopo declarado no `00` |
| 3 — RQ-09: três rotas de vendor ainda por `id` | média | especificação | dívida declarada (Desvios, item 10) |
| 4 — `acaoDePapeis()` sem `->authorize()`, e ela CONCEDE papel | **alta** | fora desta wiki | → `feat/permissoes-de-telas-e-acoes` |
| 5 — `ConvitesTable::reenviar` sem `->authorize()` | baixa | fora desta wiki | → `feat/permissoes-de-telas-e-acoes` |
| 6, 7, 8 — três docblocks falsos | baixa | teste / implementação | **corrigidos** |
| 9 — CT-12 provava só o status HTTP | baixa | teste | **corrigido** |
| 10, 11 — dois oráculos fracos | baixa | não-defeito | lacuna já declarada no `04` |

**O achado 4 é o mais grave do relatório e não é desta entrega**: o `UsersRelationManager` deixa
quem tem apenas `View:Tenant` conceder papel numa organização, porque
`RelationManager::isReadOnly()` só neutraliza as actions padrão
(`vendor/filament/filament/src/Resources/RelationManagers/RelationManager.php:220-237`). Sinalizado
para a fila.

**O que o gate não cobriu**: aparência (as duas lacunas de oráculo declaradas), mutation testing
(sem PCOV no ambiente) e a regressão contra `feat/permissoes-de-telas-e-acoes`, que mergeia antes
desta e toca os mesmos quatro arquivos — só observável num segundo rebase.

## Retrospectiva

- **Funcionou bem**: a revisão profunda (step 5) pagou por si sozinha. Seis das sete premissas
  auditadas estavam erradas ou incompletas — `Section` sem `badge()`, entradas de infolist em
  action, `HasUuids` e a PK, `->counts()` sob tenancy, a versão do Filament, e o `getKey()` no
  `Livewire::test(EditRole)`. Nenhuma delas custaria pouco se descoberta durante a implementação.

- **Funcionou bem**: derivar o `04` do requisito, e não do plano, produziu o único caso que pegou o
  defeito do seeder — CT-11 nasceu de "papel que já existia recebe uuid", que é uma frase sobre o
  REQUISITO ("nunca id na url"), não sobre o plano. Um caso derivado do PRD teria testado a
  migration, que estava certa.

- **Faltou no plano**: a validação de servidor do guard (`->in()`). O plano tratou "vira Select"
  como se Select fosse barreira. A lição generaliza: **componente de formulário do Filament não é
  validação** — nem `Select`, nem `Action` (que não consulta policy), nem `->visible()`. Nas três,
  a barreira é uma linha explícita, e as três aparecem nesta entrega.

- **Faltou no plano**: varrer RQ-06 pelo **acesso ao atributo**, não pelo nome da coluna. O
  `grep "roles\.name\|papel\.name"` que produziu a lista dos cinco pontos crus não casa com
  `->getAttribute('name')`, e foi assim que a confirmação do aceite de convite passou. Vale como
  regra de varredura: procure `getAttribute('name')`, `->name`, `pluck('name'` e `implode` sobre
  `pluck` — não o nome da coluna.

- **Faltou no plano**: perguntar ao **roteador** na auditoria de RQ-09. `php artisan route:list`
  responde em segundos e teria mostrado as três rotas de vendor que ainda usam `id`. A auditoria
  original procurou `getKey()` em URL dentro de `app/` e `tests/` — que é onde o defeito não estava.

- **Faltou no plano**: que **widget de painel é lazy por default**
  (`vendor/filament/support/src/Concerns/CanBeLazy.php:9`), então `GET /admin` devolve
  placeholder e não o conteúdo do widget. CT-09 nasceu como visita HTTP e falhou por isso — e
  quem denunciou foi a asserção **positiva**: com apenas o `assertDontSee` da chave, o caso
  passaria verde medindo uma página sem widget nenhum. É a mesma família de
  *"uma tela aberta não é uma tela que grava"* que `.ai/rules/testes.md` registra, com outra
  cara: **uma página aberta não é um widget renderizado**. Virou teste de componente nos dois
  widgets, com o par (vê o rótulo / não vê a chave) em cada um.

- **Faltou no plano**: a existência de duas classes de model para a tabela `roles`. O PRD leu
  `App\Models\Role` e assumiu que era a única porta de escrita. A pergunta que teria evitado:
  *"quem mais escreve nesta tabela, e por qual classe?"* — e ela vale para toda tabela de vendor
  com model publicado.

## Data de conclusão

2026-08-24

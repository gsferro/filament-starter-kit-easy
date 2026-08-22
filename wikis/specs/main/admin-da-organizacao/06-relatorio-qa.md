# Relatório de QA — Admin da organização

> Requisito: **não existe `00-requisito.md`**, mas existe **oráculo forte** — a fala do
> usuário está literal no `01-plano-acao.md:20-26`, seção "O que o usuário pediu, nas
> palavras dele". Plano: `01-plano-acao.md` · Casos: `04-casos-de-teste.md`
> Perfil de esforço: **completo** (domínio sensível — escalada de privilégio e dado de outro tenant)
> Natureza da wiki: nova, sobre infra compartilhada · Regressão: sim
> Confrontado contra o código de `main` em `e196f20` (v0.18.2).

## O oráculo, transcrito

> "O painel /admin é o admin geral da aplicação. Quando for multi-tenancy, tem que ter um
> 'admin' dentro do painel app, que NÃO vai acessar o /admin mas sim ter mais permissões
> em /app, podendo criar usuários e usar o convite, **vendo somente os usuários e
> permissões correspondentes ao tenancy dele e pertencentes ao painel app**."

Decomposição usada como `RQ` (a wiki não numerou; a numeração abaixo é deste relatório e
serve só à matriz):

| RQ | Cláusula extraída da fala |
|----|---------------------------|
| RQ-01 | existe um papel de administrador **dentro** do painel `app` |
| RQ-02 | esse papel **não** acessa o `/admin` |
| RQ-03 | tem **mais** permissões que o usuário comum no `/app` |
| RQ-04 | pode criar usuários |
| RQ-05 | pode usar o convite |
| RQ-06 | vê **somente** os usuários correspondentes ao tenancy dele |
| RQ-07 | vê **somente** permissões pertencentes ao painel `app` |

## Veredito — Ciclo 1

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 1 · Minor: 2 · Cosmético: 0
- Ambiente: sem app servido; Pest 5.0.5; sqlite `:memory:`; MCP não usado
- Suítes: `--testsuite=Tenancy --filter=AdminDaOrganizacao` → **19 casos, 92 asserções, verde**

As seis barreiras contra escalada estão implementadas e cada uma tem CT. O único achado com
consequência funcional é uma **rota de terceiro que entrou no painel depois desta wiki** e
que o papel desta wiki alcança.

## Achados

### QA-01 — `admin_app` alcança `/app/{org}/exceptions` e a tela quebra com `LogicException` · Major · destino 2

- **Dimensão**: C (matriz de permissão) + I (segurança da superfície) + A (RQ-07)
- **Relacionado a**: RQ-07, passo 1 do PRD ("a matriz é a do painel INTEIRA"), ADR-06,
  `database/seeders/PapeisSeeder.php` (`permissoesDeAdministracaoDoApp()`),
  `app/Providers/Filament/AppPanelProvider.php` (`FilamentExceptionsPlugin::make()->registerNavigation(false)`)
- **Esperado**: RQ-07 — *"vendo somente ... permissões pertencentes ao painel app"* no
  sentido que a própria wiki adota: o recorte de **dado** é por organização
  (`01`, passo 1: *"O recorte dele é de DADO (só a organização corrente), feito no
  `getEloquentQuery()` dos Resources"*). O `ExceptionResource` do
  `bezhansalleh/filament-exceptions` **não tem** recorte de organização — a tabela
  `filament_exceptions_table` é global e guarda `path`, `ip`, `headers`, `cookies` e `body`
  de request de qualquer organização.
- **Observado**, medido:
  1. o plugin registra o Resource no painel `app`
     (`vendor/bezhansalleh/filament-exceptions/src/FilamentExceptionsPlugin.php:49-52`), e
     `registerNavigation(false)` só afeta `shouldRegisterNavigation()`
     (`vendor/.../src/Resources/ExceptionResource.php:84-86`) — **não** `canAccess()`;
  2. as rotas existem: `GET app/{tenant:slug}/exceptions` e
     `app/{tenant:slug}/exceptions/{record}` (`php artisan route:list --path=app`);
  3. `Paineis::permissoes('app')` contém as 14 permissions de `Exception`, e o
     `PapeisSeeder` dá ao `admin_app` a matriz **inteira** → sonda: `admin_app
     ViewAny:Exception => true`, `DeleteAny:Exception => true`, `panel_user
     ViewAny:Exception => false`;
  4. `GET /app/acme/exceptions` como `admin_app` responde **200** (a tabela do kit é
     `->deferLoading()`, `app/Providers/Concerns/ConfiguraFilamentGlobal.php:181`, então o
     GET devolve o esqueleto);
  5. ao carregar a tabela — o que qualquer navegador real faz no request Livewire seguinte —
     estoura `LogicException: The model [BezhanSalleh\FilamentExceptions\Models\Exception]
     does not have a relationship named [tenant]`, em
     `vendor/filament/filament/src/Resources/Resource/Concerns/BelongsToTenant.php:99`.
- **Repro** (sonda efêmera em `tests/Tenancy/`, `Tests\TenancyTestCase`):
  1. seeders `ShieldPermissionsSeeder` + `PapeisSeeder`
  2. `$acme = tenant('Acme','acme')`; `papelNaOrganizacao(usuario('ana@example.com'),'admin_app',$acme)`; `attach`
  3. criar uma linha em `filament_exceptions_table`
  4. `$this->actingAs($ana)->get('/app/acme/exceptions')` → **200**, sem a linha no HTML
  5. `noPainelDa($acme)` + `Livewire::test(ListExceptions::class)->loadTable()` → **LogicException**
- **Evidência**: saída da sonda — `status GET => 200`, `ve mensagem => false`; exceção do
  item 5 com o `file:line` acima.
- **O que este achado NÃO é** — hipótese testada e **rejeitada**: *"o `admin_app` lê as
  stack traces da instalação inteira"*. **Não lê.** O global scope nativo do Filament
  (`BelongsToTenant::registerTenancyModelGlobalScope`, `:143-155`) é registrado no model
  `Exception` porque o Resource é do painel com tenancy e mantém
  `$isScopedToTenant = true`, e com tenant corrente ele **lança** em vez de devolver
  linhas. Ou seja: a barreira existe, mas é acidental e se manifesta como erro 500, não
  como 403. E o comentário do `PapeisSeeder` que motivou a subtração do `panel_user`
  (*"a permission bastaria para ler stack traces da instalação inteira"*) está correto
  **no modo single-tenant** — que é exatamente o modo em que `panel_user` existe sem
  `admin_app` e em que nenhum global scope é registrado.
- **Destino**: 2 (implementação). A correção mínima é acrescentar
  `BezhanSalleh\FilamentExceptions\Resources\ExceptionResource::class` à lista subtraída
  **também** do `admin_app` — hoje a subtração só se aplica ao `panel_user`
  (`PapeisSeeder::run()`), embora `permissoesDeAdministracaoDoApp()` já liste o
  `ExceptionResource`. Alternativa: não dar ao `admin_app` a matriz inteira do painel e sim
  a matriz menos as entidades de administração que **não** têm recorte de organização.
- **Ação exigida**: decidir se `admin_app` recebe a matriz inteira ou a matriz menos as
  entidades sem recorte por organização; e acrescentar o caso que fixa a decisão, do lado
  de `it('mantem o usuario comum fora da administracao da organizacao')`. Este achado
  viola, de fato, a rule `.ai/rules/filament.md` §"Resource de model sem relação de posse
  com o tenant" — a rule é do kit e o Resource é de vendor, então a saída é pela permissão.

### QA-02 — a wiki inteira fala de `admin_organizacao`; o papel se chama `admin_app` desde 2026-08-16 · Minor · destino 1

- **Dimensão**: A (rastreabilidade) + J
- **Relacionado a**: os quatro arquivos desta wiki
- **Esperado / Observado**: `admin_organizacao` aparece **39 vezes** nesta wiki (11 no
  `01`, 11 no `02`, 4 no `03`, 13 no `04`) e **zero vez** no código. O papel foi renomeado
  para `admin_app` por `database/migrations/2026_08_16_000001_rename_admin_organizacao_role.php`,
  com dois testes de retrofit (`tests/Kit/RenomeacaoDePapelTest.php`) e rótulo próprio
  (`App\Support\Papeis::ROTULOS['admin_app'] = 'Administrador App'`). Nenhum arquivo desta
  wiki registra a renomeação — nem o `03-progresso.md`, que é o tracking único da feature.
- **Repro**: `grep -rc admin_organizacao wikis/specs/main/admin-da-organizacao/*.md` → 11/11/4/13;
  `grep -rn admin_organizacao app/ database/seeders/` → só a migration de renomeação.
- **Destino**: 1 (especificação). Não é preciosismo: a wiki é o documento que o próximo
  agente lê para entender a persona, e `Role::findByName('admin_organizacao')` hoje lança
  `RoleDoesNotExist`. Além disso a renomeação **não tem wiki própria** — ela existe só
  como migration + teste.

### QA-03 — o `->unique()` do e-mail transforma o cadastro em oráculo de existência entre organizações · Minor · destino 1

- **Dimensão**: I (segurança da superfície nova) + A (RQ-06)
- **Relacionado a**: RQ-06, `app/Filament/App/Resources/Users/UserResource.php` (campo
  `email`, `->unique(ignoreRecord: true)`)
- **Esperado**: RQ-06 — *"vendo somente os usuários correspondentes ao tenancy dele"*. A
  listagem obedece (`getEloquentQuery()` com `whereHas('tenants')`, e CT-04/CT-05 provam).
- **Observado**: o formulário de criação não. O `admin_app` da Acme digita um endereço que
  só existe na Globex e recebe *"O valor indicado para o campo e-mail já se encontra
  registrado."* — enquanto a mesma tela, na listagem, não mostra esse usuário. O
  administrador de uma organização passa a poder **testar, endereço por endereço**, se uma
  pessoa tem conta na instalação. Regra de validação ignora escopo por desenho — o próprio
  vendor avisa (`BelongsToTenant.php:24-26`: *"Laravel's `unique()`/`exists()` validation
  rules bypass global scopes"*).
- **Repro**:
  1. Acme e Globex; Ana `admin_app` na Acme; `segredo@globex.test` `panel_user` na Globex
  2. `noPainelDa($acme)`, `actingAs($ana)`
  3. `Livewire::test(App\Filament\App\Resources\Users\Pages\CreateUser::class)->fillForm([... 'email' => 'segredo@globex.test' ...])->call('create')`
- **Evidência**: `erros => ['data.email' => ['O valor indicado para o campo e-mail já se
  encontra registrado.']]` e `listagem ve o usuario da globex => false`.
- **Destino**: 1 (especificação) — e o motivo de ser especificação e não implementação é
  que a wiki irmã tomou a decisão **oposta** na tela vizinha: `convite-para-usuario-existente`
  **removeu** o `->unique('users','email')` do formulário de convite justamente para que
  endereço com conta deixasse de ser parede. As duas telas do mesmo painel, para o mesmo
  papel, tratam "e-mail já existe" de formas contrárias. É preciso decidir uma: ou o
  cadastro direto também aceita e desvia para o convite, ou a mensagem deixa de revelar
  existência ("não é possível cadastrar este endereço aqui — use o convite").
- **Nota de severidade**: Minor, não Major. O que vaza é a **existência** de uma conta, não
  dado dela; e o convite já expõe o mesmo fato de outra forma (convidar quem já tem conta
  produz uma tela diferente). Não inflar.

## Matriz de Rastreabilidade

Todas as linhas — a matriz desta wiki não tem lacuna de cláusula, e vale imprimi-la
inteira porque é a única das três com oráculo forte.

| RQ | Cláusula | Passo PRD | CT | Código | Teste | Veredito |
|----|----------|-----------|----|--------|-------|----------|
| RQ-01 | papel de admin dentro do `/app` | 1 | CT-01, CT-15 | `PapeisSeeder` (`admin_app`, `painel='app'`) | `AdminDaOrganizacaoTest` (Tenancy + Kit) | OK |
| RQ-02 | não acessa o `/admin` | — (herdado) | CT-02 | `User::canAccessPanel` (contexto global) | `nao entra nos paineis de instalacao` (2 datasets) | OK |
| RQ-03 | mais permissões que o comum | 1 | CT-12 | subtração em `PapeisSeeder` | `mantem o usuario comum fora da administracao…` | OK |
| RQ-04 | cria usuários | 2 | CT-11 | `App/Resources/Users/*` + `CreateUser::afterCreate` | `vincula o usuario criado a organizacao corrente` | OK |
| RQ-05 | usa o convite | 4 | CT-10 | `App/Resources/Convites/*` | `carimba a organizacao no convite ignorando o formulario` | OK |
| RQ-06 | vê só usuários da organização | 2b | CT-04, CT-05 | `UserResource::getEloquentQuery` (fail-closed) | `lista apenas os usuarios…`, `nega o acesso direto…`, `fecha a consulta…` | ⚠️ **QA-03** (só no formulário) |
| RQ-07 | vê só permissões do painel `app` | 1, 2c | CT-06, CT-08, CT-09 | `gravarPapeis()` (`where('painel','app')` na escrita) | `oferece apenas papeis…`, `descarta papel de outro painel…`, `nao alcanca a administracao de papeis` | ⚠️ **QA-01** (`Exception` entrou no painel) |

Barreiras 1 a 6 do `01:785-795`: todas com CT correspondente e verde. CT-01 a CT-17 → 19
casos (CT-02 tem dois datasets), 92 asserções.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | RQ-06 e RQ-07 com ressalva; nenhuma cláusula órfã |
| B | Fronteiras e dados | ✅ | tenant ausente (CT-14), papel de outro painel (CT-08), uuid vs id (route binding) |
| C | Matriz de permissão | ⚠️ | **QA-01**: célula `admin_app × Exception` nunca foi considerada |
| D | Observabilidade | ✅ | `autenticacao`, `[Classe@Método]`, `warning` na negativa e `info` na mudança de poder; loga `user_id`, nunca e-mail |
| E | Performance | ✅ | `whereHas` sobre pivot indexada; `getEloquentQuery()` sem log no caminho feliz, por decisão registrada |
| F | UX de erro | ✅ | `emptyState` próprio nas duas tabelas; `helperText` dizendo que o papel vale só na organização |
| G | Tema e cor | ⏭️ | sem view nova; `ImageColumn::simpleLightbox()` já coberto pela wiki do lightbox |
| H | Acessibilidade | ⏭️ | sem componente novo |
| I | Segurança da superfície nova | ⚠️ | **QA-01** e **QA-03**; IDOR coberto por CT-05 (404 no registro de outra organização) |
| J | Regressão adjacente | ✅ | 19 casos verdes; `PaineisTest` e `TenancyTest` intactos |
| K | Adequação da suíte | ✅ | nenhum CT com oráculo único de `assertOk()`; CT-08/CT-13/CT-14 asserem log **e** estado |

## Débitos Aceitos

- QA-02 (Minor): renomeação não registrada na wiki.
- QA-03 (Minor): decisão pendente entre as duas telas.

## Suspeitas Não Confirmadas

- **`admin_app` lê stack traces de outras organizações** — reproduzido e **rejeitado**: a
  tabela lança antes de devolver linha. Ver QA-01, seção "O que este achado NÃO é".

## Não Verificado

- `--mutate` (dimensão K, passo 2) — sem driver de cobertura no worktree isolado.
- Rota `app/{tenant}/exceptions/{record}` (a de detalhe) — só a de listagem foi exercitada;
  a de detalhe passa pelo mesmo route binding e pelo mesmo global scope.
- Dimensões G/H por screenshot — app não servido; Playwright MCP não usado.

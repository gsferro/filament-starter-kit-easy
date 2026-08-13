# Decisões Arquiteturais — Admin da organização

## ADR-01: A persona é um PAPEL, não uma coluna na pivot

**Status**: Aceita
**Data**: 2026-08-13

### Decisão

Papel novo `admin_organizacao`, `roles.painel = 'app'`, semeado pelo `PapeisSeeder` e
atribuído em `model_has_roles` com `team_id` = o id da organização.

**Zero código novo.** `roles.painel` veio da wiki 1, o contexto por tenant já é fixado a
cada request por `DefinirTenantDePermissoes`, e a definição do papel é global
(`roles.team_id` nulo) — uma linha em `roles` serve N organizações.

Uma coluna `tenant_user.is_admin` seria a alternativa óbvia, e o comentário da própria
migration da pivot já a recusou: *"o pivot é intencionalmente magro (sem papel, sem data de
entrada): papel é responsabilidade do spatie/permission"*. Booleano na pivot criaria uma
segunda fonte de autorização ao lado do spatie e não escalaria além do primeiro perfil. Um
papel **por** organização (`admin_acme`) é o que `roles.team_id` nulo existe para evitar.
Reusar `admin` daria `Create:Tenant` e `Update:Role` a um cliente — é o bug que a feature
evita.

### Consequências

- **Positivas**: zero schema novo; uma fonte de autorização.
- **Negativas**: a atribuição depende do contexto de team estar certo no momento da
  escrita — falha silenciosa se feita no lugar errado. Mitigado por ADR-10 e CT-07.
- **Riscos**: `admin_organizacao` e `panel_user` são os dois `painel = 'app'`, então o
  admin de uma organização **pode criar outro admin da mesma organização**. É lateral, não
  escalada, e é o ponto do pedido ("dar liberdade e flexibilidade aos admins das
  organizações"). Fica bounded pela organização.

### Referências

- `database/migrations/0001_01_01_000021_create_tenant_user_table.php` (o comentário)
- `database/seeders/PapeisSeeder::papel()`
- Refina: ADR-01 e ADR-03 de `perfil-e-acesso-ao-painel`

---

## ADR-02: O admin da organização não cria nem edita papéis — só atribui

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

O pedido fala em "ver as permissões correspondentes ao painel app". A leitura generosa
seria dar a ele uma tela de papéis recortada: criar um papel "Financeiro" com as
permissões que ele escolher, dentro da organização dele.

**Isso não é possível neste kit sem mudar a modelagem do spatie.** A definição do papel é
global: `PapeisSeeder::papel()` grava `roles.team_id = null` de propósito, porque o
`Role::findOrCreate` do spatie carimbaria o team corrente e um papel carimbado na Acme
ficaria invisível na Globex.

Consequência direta: **um papel criado dentro de uma organização apareceria em todas as
outras**. Não como um bug de tela — como dado. O admin da Acme criaria "Financeiro", e o
admin da Globex veria "Financeiro" no Select dele. Pior: se o admin da Acme editasse as
permissões desse papel, mudaria a autorização de quem o tem na Globex.

### Decisão

O admin da organização **atribui** papéis existentes e nada mais. Nenhum `RoleResource` é
registrado no painel `app`; a tela do Shield continua exclusiva do `/admin`
(publicada em `app/Filament/Admin/Resources/Roles/`, ADR-05 da wiki 1).

A garantia é dupla e nenhuma das duas é código novo:

1. o Resource simplesmente não existe no painel `app`;
2. `Create:Role` e `Update:Role` pertencem a `Paineis::permissoes('admin')` e **não** a
   `permissoes('app')`, porque o `RoleResource` está registrado só no painel admin — logo
   `admin_organizacao`, que recebe `permissoes('app')`, nunca as tem.

### Alternativas Consideradas

1. **Papel por tenant** (`roles.team_id` = id da organização), liberando a criação.
   Tecnicamente possível. Descartada porque `admin` e `infra` precisam ser globais, e as
   duas classes de papel ficariam indistinguíveis na mesma listagem. Um projeto derivado
   que queira isso precisa de um `RoleResource` próprio no `/app` forçando
   `team_id = Filament::getTenant()->getKey()`, e de reconferir todos os
   `Role::findByName()` do kit, que resolvem sem team.
2. **Prefixar o nome do papel com o slug da organização.** Descartada: convenção em string
   é dado sem tipo (mesmo argumento da ADR-03 da wiki 1 contra `app.gestor`), e o papel
   continuaria visível a todos.

### Consequências

- **Positivas**: nenhum dado de uma organização vaza para outra pela tabela `roles`; zero
  código; a matriz de autorização continua tendo um único dono (`PapeisSeeder`).
- **Negativas**: o conjunto de papéis do painel `app` é decisão do projeto, não do cliente.
  Um cliente que queira um perfil próprio precisa pedir a quem administra a instalação.
- **Riscos**: alguém acrescenta um `RoleResource` ao painel `app` sem ler esta ADR e
  entrega criação de papel global disfarçada de recurso da organização. **CT-09 falha nesse
  cenário** — ele assere que nenhum resource de `Role` está registrado no painel `app`.

### Referências

- `database/seeders/PapeisSeeder::papel()` (`roles.team_id` nulo)
- `perfil-e-acesso-ao-painel/02-decisoes-arquiteturais.md` ADR-03, ADR-05

---

## ADR-03: `User` é escopado por `getEloquentQuery()` explícito, com `$isScopedToTenant = false`

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

O recorte de `Projeto` não custa nada: a model usa `App\Traits\BelongsToTenant`, tem
`tenant_id` e a relação `tenant()`, e o `ProjetoResource` chega a documentar isso — *"repare
no que NÃO está aqui: nenhum `where('tenant_id', ...)`"*
(`app/Filament/App/Resources/Projetos/ProjetoResource`).

`User` é o caso oposto e é preciso enumerar o porquê, senão alguém "limpa" o escopo por
simetria.

**Por que a trait `BelongsToTenant` do kit não serve.** Ela pressupõe `tenant_id` na tabela
e uma relação `belongsTo` (a trait `BelongsToTenant` do kit). `users` não tem
`tenant_id`, e acrescentar a coluna significaria "um usuário pertence a **uma** organização"
— o que contradiz `User::tenants()`, a pivot
`tenant_user` e a própria demo, onde a Carla pertence a duas
(`DemoTenancySeeder`).

**Por que o escopo nativo do Filament não serve na configuração default.** `Panel::boot()`
chama `registerTenancyModelGlobalScope()` para cada Resource do painel com tenancy
(`vendor/filament/filament/src/Panel.php:84-91`); o escopo resolve o nome da relação de
posse por `Filament::getTenantOwnershipRelationshipName()`, cujo default é o `classBasename`
do model de tenant em camelCase — **`tenant`**, singular
(`vendor/filament/filament/src/Panel/Concerns/HasTenancy.php:501-510`; o painel chama
`->tenant(Tenant::class, slugAttribute: 'slug')` sem o terceiro argumento em
`AppPanelProvider`). `User` não tem `tenant()`, e o `throw`
sai literal de
`vendor/filament/filament/src/Resources/Resource/Concerns/BelongsToTenant.php:99`:

```
LogicException: The model [App\Models\User] does not have a relationship named [tenant].
```

**Por que apontar `$tenantOwnershipRelationshipName = 'tenants'` — que funcionaria — também
não serve.** Com a relação certa, `scopeEloquentQueryToTenant()` cai no ramo default e
produz exatamente o `whereHas('tenants', fn ($q) => $q->whereKey($tenant))` que queremos
(`BelongsToTenant.php:60-64`). Uma linha, sem `getEloquentQuery()`. Três problemas matam a
opção:

1. **Falha aberto.** O closure do escopo global retorna em silêncio quando não há tenant
   corrente (`BelongsToTenant.php:150-152`). Fora de request de painel — job, comando,
   `pulse:check`, tinker — a listagem volta a ser a base inteira de usuários da instalação.
   Para `Projeto` isso é aceitável e documentado (a seção "Limites" da trait do kit:
   um job que roda para todos os tenants precisa ver todos). Para **pessoas de outros
   clientes** não é.
2. **Raio de alcance.** O escopo é registrado no model `User` inteiro, compartilhado com o
   `/admin`, o guard de autenticação e o `UsersRelationManager`. A guarda
   `Filament::getCurrentPanel() !== $panel` (`BelongsToTenant.php:144-146`) segura hoje, mas
   é uma condição de runtime protegendo uma leitura de segurança.
3. **Vem em pacote fechado.** Ligar `$isScopedToTenant` traz junto
   `observeTenancyModelCreation()`, que faz `syncWithoutDetaching([$tenant])` no `created`
   de qualquer `User` criado no painel (`BelongsToTenant.php:205-209`). O comportamento é
   desejável — mas passa a ser um observer de vendor que o `UserResource` do `/admin` não
   tem, e a diferença fica invisível.

### Decisão

`protected static bool $isScopedToTenant = false;` mais um `getEloquentQuery()` explícito e
fail-closed — o código está no **passo 2b do `01-plano-acao.md`**.

`$isScopedToTenant = false` faz `Panel::boot()` pular as duas chamadas
(`BelongsToTenant.php:129-131` e `:160-162`) — sem `LogicException`, sem escopo global no
model `User`, sem observer. O vínculo com a organização na criação passa a ser uma linha
explícita no `afterCreate` da Page.

**Escopar em `getEloquentQuery()` e não na `table()`** porque quatro consumidores passam
por ele: a listagem (`vendor/.../Resources/Pages/ListRecords.php:223`), o route binding
(`vendor/.../Resource/Concerns/HasRoutes.php:41-51`), a busca global
(`vendor/.../Resource/Concerns/HasGlobalSearch.php:259`) e o badge de contagem do menu
(`BadgeContagemNavegacao`). É o binding que faz a URL
direta para um usuário de outra organização devolver 404 sem uma linha a mais.

**`whereRaw('1 = 0')` e não uma exception**: exception derrubaria qualquer varredura que
toque o Resource fora de request. Query vazia falha fechado sem quebrar processo.

### Alternativas Consideradas

1. **`tenant_id` em `users`.** Descartada: transformaria N:N em 1:N, contra o model, a
   pivot e a demo.
2. **`$tenantOwnershipRelationshipName = 'tenants'`.** Descartada pelos três motivos acima
   — o decisivo é falhar aberto.
3. **Escopar só na `table()->modifyQueryUsing()`.** Descartada: deixa o route binding, a
   busca ⌘K e o badge fora do recorte. O badge mentiria e a URL direta abriria o registro.
4. **Um global scope próprio no model `User`.** Descartada: `User` é consultado pelo guard
   de autenticação, por `impersonate`, pelo `UsersRelationManager` do `/admin` e por
   qualquer job. Escopo global ali é a versão pior do problema 2 do Filament.

### Consequências

- **Positivas**: o recorte fica em **um** método, legível, testável e fail-closed; o model
  `User` não muda uma linha; `/admin` continua sem escopo.
- **Negativas**: `UserResource` do `/app` precisa lembrar de `$isScopedToTenant = false` —
  esquecer é `LogicException` na primeira query. É barulhento, o que é bom.
- **Riscos**: uma Page nova do Resource que consulte `User::query()` direto sai do recorte.
  Mitigação: a regra nova em `.ai/rules/filament.md` (passo 6) diz que a consulta é sempre
  `static::getEloquentQuery()`.

### Referências

- `vendor/filament/filament/src/Panel.php:84-91`
- `vendor/filament/filament/src/Resources/Resource.php:88-106`
- `vendor/filament/filament/src/Resources/Resource/Concerns/BelongsToTenant.php:30, 40-64, 99, 129-131, 144-156, 160-162, 205-209`
- `vendor/filament/filament/src/Panel/Concerns/HasTenancy.php:501-510`
- `app/Traits/BelongsToTenant.php` (a seção "Limites")
- CT-04, CT-05, CT-14, CT-16

---

## ADR-04: Dois `UserResource` separados, sem classe base compartilhada

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

`App\Filament\Admin\Resources\Users\UserResource` e
`App\Filament\App\Resources\Users\UserResource` vão parecer irmãos: mesmo model, mesmos
quatro campos de formulário, mesmas quatro colunas de tabela. A pressão por extrair uma
base é imediata, e a escada do Ponytail manda checar reuso antes de escrever.

O que é **igual**: os `TextInput` de nome, e-mail e senha, as colunas da tabela e a forma
do par `saveRelationshipsUsing()` + `syncRoles()`.

O que é **diferente**, e é tudo regra de segurança:

| | `/admin` | `/app` |
| --- | --- | --- |
| Query | sem escopo (`Resource::getEloquentQuery()`) | `whereHas('tenants', …)`, fail-closed |
| `$isScopedToTenant` | irrelevante (painel sem tenancy) | **`false`**, obrigatório |
| Papéis oferecidos | todos | só `roles.painel = 'app'` |
| Papéis graváveis | todos | filtrados na escrita |
| Contexto da atribuição | `Tenant::CONTEXTO_GLOBAL` | o tenant corrente |
| Organizações | campo `tenants` obrigatório (wiki 1, passo 7) | **nenhum campo** — vem do painel |
| Excluir | `DeleteAction` + `DeleteBulkAction` | proibido (ADR-08) |
| Impersonar | `Impersonate::make()` | não |

### Decisão

**Duas classes independentes.** Nenhuma classe abstrata, nenhum trait de schema
compartilhado, nenhum `UserForm::configure()` parametrizado.

O que se reusa é o que já é reusável no kit: o trait `BadgeContagemNavegacao`, a receita de
Resource em `wikis/receitas.md` e — a parte que realmente importa — a **regra escrita**
sobre `syncRoles()` em `.ai/rules/filament.md` (1ª regra), que os agentes leem antes de
escrever código em `app/Filament/**`.

Regra de conduta: abstração com uma implementação só é proibida; **duas implementações com
regras de segurança diferentes ficam separadas**. Uma base compartilhada aqui significaria
que uma edição no pai, feita pensando no `/admin`, alarga o `/app` em silêncio — e o `/app`
é o que tem cliente dentro.

### Alternativas Consideradas

1. **`abstract class UserResourceBase`.** `getEloquentQuery()`, `canDelete()` e as opções
   do Select seriam sobrescritos no filho — a base ficaria com os quatro `TextInput`.
2. **Trait com o schema compartilhado.** Resolve ~25 linhas e cria um arquivo que precisa
   ser lido junto dos dois. O kit já tem o precedente contrário: `TenantForm`/`TenantsTable`
   vivem em `Schemas/` e `Tables/` porque são **de um** Resource.
3. **Um Resource só nos dois painéis, com `if (Filament::getCurrentPanel())`.** Regra de
   segurança dependendo de um `if` em runtime; e o Shield geraria a mesma entidade em dois
   painéis, indistinguível na tela de papéis agrupada da wiki 1.

### Consequências

- **Positivas**: cada arquivo se lê inteiro; a diferença de segurança é visível no diff;
  mudar o `/admin` não pode afrouxar o `/app`.
- **Negativas**: ~25 linhas de `TextInput` duplicadas. Uma mudança de campo (por exemplo,
  telefone) precisa ser feita em dois lugares. É o custo aceito.
- **Riscos**: as duas telas divergem sem intenção — por exemplo, uma valida e-mail e a
  outra não. Mitigação: os CTs de formulário rodam contra as duas, e a tabela de diferenças
  acima é a referência de revisão.

### Referências

- `app/Filament/Admin/Resources/Users/UserResource.php`
- `.ai/rules/filament.md` (1ª regra)
- `app/Filament/Admin/Resources/Tenants/` (o precedente de `Schemas/` por Resource)

---

## ADR-05: Nenhuma tela de permissões no painel `app`

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

O pedido diz "vendo somente os usuários e permissões correspondentes ao tenancy dele e
pertencentes ao painel app". Metade da frase é clara (usuários recortados pela
organização); a outra metade admite duas leituras:

1. *o vocabulário de autorização que ele manipula é o do painel `app`* — ou seja, o Select
   de papéis só oferece papéis `painel = 'app'`;
2. *ele tem uma tela onde vê as permissões* — uma versão somente-leitura da tela do Shield.

### Decisão

Leitura 1. **Nenhuma tela nova.** O que ele "vê de permissões" é o Select filtrado por
`roles.painel = 'app'`, com o `helperText` dizendo que os papéis valem só dentro da
organização.

Ele não cria nem edita papéis (ADR-02), então a lista de permissões de um papel que ele não
pode alterar não é insumo de decisão; a fonte única da matriz continua sendo
`/admin/shield/roles`. E registrar o `RoleResource` publicado no painel `app` traria a
máquina de **escrita** junto (as Pages do Shield com `mutateFormDataBefore*` que a wiki 1
teve de editar): uma tela "somente-leitura" construída sobre elas é uma tela de escrita com
os botões escondidos — e affordance escondida não é autorização.

O gatilho para reabrir a decisão está na tabela "O que foi cortado" do `01-plano-acao.md`.

### Consequências

- **Positivas**: zero arquivo novo; a fonte de verdade da matriz continua única.
- **Negativas**: se a intenção do usuário era mesmo a leitura 2, falta uma tela. Descobrir
  isso custa uma conversa; acrescentar depois custa ~30 linhas.
- **Riscos**: o admin da organização escolhe um papel sem saber o que ele concede.
  Mitigação: nomes de papel descritivos (é o `PapeisSeeder` que os define) e o `helperText`.

### Referências

- `perfil-e-acesso-ao-painel/02-decisoes-arquiteturais.md` ADR-05
- ADR-02 desta wiki

---

## ADR-06: `panel_user` deixa de receber a matriz inteira do painel `app`

**Status**: Aceita
**Data**: 2026-08-13
**Corrige**: `perfil-e-acesso-ao-painel`, passo 4 do `01-plano-acao.md`

### Contexto

A wiki 1 definiu `panel_user` → `Paineis::permissoes('app')`. Era correto enquanto o painel
`app` tinha um Resource de demonstração (`ProjetoResource`). Esta feature registra
`UserResource` e `ConviteResource` no mesmo painel, e `Paineis::permissoes('app')` passa a
conter `Create:User`, `Update:User`, `Delete:User` e `Create:Convite`.

Sem intervenção, **todo usuário comum do negócio vira admin da organização** — sem ninguém
decidir, sem migration, sem erro. Basta rodar o `PapeisSeeder`.

O modo de falhar é o pior possível: a tela aparece, funciona e não reclama.

### Decisão

`PapeisSeeder` passa a subtrair. `panel_user` recebe `Paineis::permissoes('app')` **menos**
as permissões dos Resources de administração do painel `app`.

O recorte é feito por **FQCN de Resource**, lendo `Paineis::resources()['app']` e o formato
que o Shield monta (`'permissions' => ['viewAny' => ['key' => 'ViewAny:User', …]]`,
`vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityTransformers.php:14-28`), nunca
por substring do nome da permissão.

Isso importa: a wiki 1 removeu deste mesmo seeder o casamento por
`str_contains($p, 'User')` justamente porque um `UserPreferenceResource` futuro cairia nele
por acidente. Reintroduzir o padrão aqui, agora numa **subtração**, produziria o erro
espelhado — tirar permissão de quem deveria tê-la.

A subtração roda nos dois modos, single e multi-tenant: os Resources existem no painel
mesmo quando `canAccess()` é falso, então o Shield gera as permissões deles de qualquer
forma.

### Alternativas Consideradas

1. **Só dar as permissões novas a `admin_organizacao`, deixando `panel_user` como está.**
   É o cenário do bug: `panel_user` continuaria com tudo.
2. **Listar positivamente as permissões de `panel_user`.** O kit não sabe quais Resources
   de negócio o projeto vai criar; lista positiva apodrece a cada Resource novo — o mesmo
   problema que `master_global` sem permissions evita.
3. **Um quarto painel `/gestao` só para a administração da organização.** Login, seletor de
   organização e plugins próprios para duas telas, e o usuário trocaria de painel para
   administrar a mesma organização em que trabalha.

### Consequências

- **Positivas**: `panel_user` volta a significar "usa o negócio"; nenhuma lista à mão.
- **Negativas**: o `PapeisSeeder` passa a conhecer dois FQCN de Resource por nome. É
  acoplamento, mas explícito e coberto por `class_exists` — é o preço de a matriz ser
  derivada e não escrita.
- **Riscos**: um terceiro Resource de administração no painel `app` (um "Configurações da
  organização", por exemplo) precisa entrar na lista, senão `panel_user` o herda.
  Mitigação: a lista tem comentário dizendo isso, e CT-12 falha se o `panel_user` alcançar
  a tela de usuários.

### Referências

- `PapeisSeeder::permissoesDoPainel()` — substituiu o casamento por substring na wiki 1, e é sobre ele que a subtração se constrói
- `vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityTransformers.php:14-28`
- CT-12

---

## ADR-07: A trava contra escalada é na ESCRITA, não nas opções do Select

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

Filtrar as opções do `Select::make('roles')` por `roles.painel = 'app'` resolve a tela: o
admin da organização não vê `master_global`, `admin` nem `infra`.

Mas `Select` do Filament é um componente Livewire, e o `$state` que chega ao
`saveRelationshipsUsing()` vem do cliente. Um payload com o id do papel `admin` não passa
por nenhuma verificação de "esse id estava entre as opções" — o
`->relationship('roles', 'name', $modifyQuery)` usa a query modificada para **montar a
lista**, não para validar a gravação.

### Decisão

O filtro `where('painel', 'app')` é aplicado **de novo**, no servidor, dentro do
`saveRelationshipsUsing()`, antes do `syncRoles()`:

```php
$papeis = $record->roles()->getRelated()->newQuery()
    ->whereKey($state)
    ->where('painel', 'app')
    ->get();
```

Um id descartado gera `warning` no canal `autenticacao` com `ids_enviados` e `ids_aceitos`
— tentativa de escalada é exatamente o que se quer poder reconstituir depois.

O filtro do Select **continua** existindo: os dois têm funções diferentes. O do Select é
UX (não oferecer o que não vale); o da escrita é segurança.

### Alternativas Consideradas

1. **Confiar na validação do Filament.** Descartada por não ter sido possível provar, na
   versão instalada, que `Select` com `->relationship()` e `->multiple()` valida o state
   contra as opções — e uma barreira de segurança não se apoia em comportamento não
   verificado de framework. Se ele validar, o filtro extra é uma query a mais; se não
   validar, é a única coisa entre o payload e a escalada.
2. **Uma `Rule::in()` nas opções.** Equivalente em efeito, pior em localização: a regra
   ficaria no campo (que já filtra) em vez de no ponto de escrita, e um segundo caminho de
   gravação (import, action em massa) passaria por fora dela.
3. **Uma policy `assignRole`.** Descartada: policy é por model, não por par
   (model, papel-alvo); e o kit inteiro delega policies ao Shield
   (`UserPolicy`) — uma policy escrita à mão aqui sairia do padrão.

### Consequências

- **Positivas**: a mesma linha cobre o Select, um action em massa futuro e qualquer outro
  caminho que chame o mesmo método; a tentativa fica registrada.
- **Negativas**: uma query a mais por gravação, e o filtro aparece em dois lugares.
- **Riscos**: o `UsersRelationManager` do `/admin` (ADR-10) tem a **mesma** necessidade e
  precisa do mesmo filtro. Está no passo 3 do plano e coberto por CT-07.

### Referências

- `app/Filament/Admin/Resources/Users/UserResource`, `saveRelationshipsUsing()` (o comentário
  original sobre o state vir como string do Livewire)
- `.ai/rules/filament.md` (1ª regra)
- CT-06, CT-08

---

## ADR-08: Do painel `app` não se exclui usuário

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

`make:filament-resource` gera `DeleteAction` na tabela e no header da página de edição, e
`DeleteBulkAction` na toolbar — é o que o `UserResource` do `/admin` tem.

Copiar isso para o `/app` seria dar ao admin de uma organização o poder de apagar a **linha
de `users`**. Como o vínculo é a pivot `tenant_user` com `cascadeOnDelete`, apagar o usuário
o remove de **todas** as organizações — a Carla da demo sumiria da Globex porque o admin da
Acme clicou em excluir.

`admin_organizacao` recebe `Paineis::permissoes('app')` inteira, o que inclui `Delete:User`
e `DeleteAny:User`. A permissão existe; o que não pode existir é a superfície.

### Decisão

O `UserResource` do `/app` não registra `DeleteAction`, `DeleteBulkAction` nem a ação de
delete no header do `EditUser`. E, para que a proibição não dependa de ninguém lembrar:

```php
public static function canDelete(Model $record): bool { return false; }
public static function canDeleteAny(): bool { return false; }
```

Quatro linhas, fail-closed, no lugar onde a decisão se lê.

A operação que o admin da organização **deveria** ter — "remover da minha organização" —
é um detach da pivot, não um delete. Está cortada por YAGNI (não foi pedida) e o gatilho
para acrescentá-la está no `01-plano-acao.md`, seção "O que foi cortado".

### Alternativas Consideradas

1. **Subtrair `Delete:User` da matriz de `admin_organizacao`.** Exigiria surgery de string
   na matriz derivada, que é o que ADR-06 evita — e a trava no Resource é mais forte, vale
   mesmo que a permissão volte.
2. **`DeleteAction` com `->before()` fazendo detach.** Ação chamada "Excluir" que não
   exclui é armadilha para quem lê o código depois.
3. **Deixar excluir, confiando no escopo.** O escopo garante que ele só alcança usuários
   **da organização dele** — e a Carla é usuária da organização dele *e* de outra. O escopo
   não protege contra o efeito colateral.

### Consequências

- **Positivas**: nenhuma operação de dentro de uma organização atravessa a fronteira dela.
- **Negativas**: um usuário criado por engano fica na lista até alguém do `/admin` apagá-lo.
- **Riscos**: alguém "completa" o Resource acrescentando `DeleteAction`. CT-17 falha nesse
  caso.

### Referências

- `database/migrations/0001_01_01_000021_create_tenant_user_table.php` (`cascadeOnDelete`)
- CT-17

---

## ADR-09: O papel não existe sem multi-tenancy

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

O kit nasce single-tenant . Sem tenancy, `/app` não tem organização —
`AppPanelProvider` só chama `->tenant()` dentro do `if (config('kit.tenancy.enabled'))`
.

Um `admin_organizacao` nesse modo seria um papel com permissão de criar usuário e **nenhum
recorte** — ou seja, um segundo `admin` com outro nome, alcançando toda a base de usuários
da instalação a partir do painel de negócio.

### Decisão

O `PapeisSeeder` só semeia `admin_organizacao` quando `config('kit.tenancy.enabled')`.

Os dois Resources continuam existindo no disco (o discovery do Filament os registra de
qualquer forma) mas ficam inacessíveis, pelo par `shouldRegisterNavigation()`/`canAccess()`
já usado em `TenantResource` — o código está no passo 2a do `01-plano-acao.md`. E o
`getEloquentQuery()` fecha por conta própria: sem tenancy `Filament::getTenant()` é sempre
null, então a query devolveria vazio mesmo se alguém alcançasse a tela.

Três camadas para a mesma proibição, todas baratas. Aqui não se economiza.

### Alternativas Consideradas

1. **Semear sempre e deixar o papel sem uso.** Papel visível no Select do `/admin` que, se
   atribuído, dá acesso irrestrito a usuários: arma carregada documentada como descarregada.
2. **Não criar os Resources sem tenancy**, movendo-os no `kit:tenancy`. O comando já é
   destrutivo e delicado; isso troca um `if` de config por uma operação de disco.

### Consequências

- **Positivas**: o modo default do kit não ganha superfície nova; o `PapeisSeeder` continua
  idempotente nos dois modos.
- **Negativas**: dois Resources inertes no disco de todo projeto single-tenant. É o mesmo
  custo que `TenantResource` e `ProjetoResource` já pagam.
- **Riscos**: `Paineis::permissoes('app')` inclui `*:User` mesmo em single-tenant, e a
  subtração de ADR-06 precisa rodar nos dois modos. **CT-15 cobre.**

### Referências

- `config/kit.php` (bloco `tenancy`)
- `AppPanelProvider` (o `if` de tenancy em torno de `->tenant()`)
- `TenantResource` (o par de visibilidade)
- CT-15

---

## ADR-10: O primeiro admin de uma organização é criado no `/admin`, pelo relation manager

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

Problema de bootstrap: `admin_organizacao` só vale atribuído no contexto da organização.
Quem atribui o primeiro, antes de existir qualquer admin naquela organização?

O caminho intuitivo é o Select de papéis do `UserResource` do `/admin`. **Ele produz a
falha mais silenciosa desta feature**: fora do painel `/app` o contexto de team é
`Tenant::CONTEXTO_GLOBAL`, fixado por `KitServiceProvider::configureTenancy()`, e o papel é
gravado com `team_id = 0`.

Efeito: a pessoa **entra** no `/app` — o `canAccessPanel()` da wiki 1 aceita, para painel
com tenancy, o papel em qualquer contexto (ADR-04 de lá) — e, dentro dele, o `wherePivot`
do spatie filtra pelo team do request, que é o id do tenant. `hasRole('admin_organizacao')`
devolve `false`. Menu vazio, 403 em cada tela, nenhuma mensagem de erro. É a linha que
`wikis/receitas.md` já cataloga como "usuário perdeu os papéis dentro do `/app`".

### Decisão

A concessão acontece em `/admin` → cadastro de organizações → aba **Usuários vinculados** →
ação **"Papéis nesta organização"**, no `UsersRelationManager`.

É o único lugar do sistema que conhece o **usuário** e a **organização** ao mesmo tempo —
o relation manager tem `getOwnerRecord()`. A ação troca o contexto para
`$tenant->getKey()`, chama `syncRoles()` com o mesmo filtro `painel = 'app'` de ADR-07, e
restaura o contexto no `finally`, com `unsetRelation('roles')` nas duas pontas (o Eloquent
cacheia `roles` na instância, e o cache do contexto anterior contaminaria a escrita).

O Select do `UserResource` do `/admin` **continua existindo e continua gravando no contexto
global** — é o que deve fazer: é lá que se concede `admin`, `infra` e `master_global`, que
são papéis de instalação.

### Alternativas Consideradas

1. **Um seletor de organização no Select de papéis do `/admin`.** Uma matriz usuário ×
   organização × papéis dentro de um campo de formulário, para um caso de bootstrap.
2. **Um comando `kit:admin-organizacao {email} {slug}`.** Resolve a instalação inicial, não
   o dia a dia (organização nova, troca de responsável). Boa segunda ferramenta, não
   substituta da tela.
3. **O próprio `admin_organizacao` promove.** É o caminho do dia a dia e funciona — não
   resolve o primeiro.
4. **Estender o `AttachAction` "Vincular usuário" com um campo de papéis.** Tentador, mas o
   `->schema()` do `AttachAction` escreve nas colunas da **pivot**, e `tenant_user` é magra
   de propósito: seria preciso um `->after()` fazendo o trabalho de qualquer forma. E a
   ação separada também serve para quem já está vinculado.

### Consequências

- **Positivas**: a promoção acontece onde a organização é o contexto óbvio da tela; o log
  de mudança de poder sai com `tenant_id` e `executor_id`; nenhuma nova rota.
- **Negativas**: uma ação a mais num relation manager que era só attach/detach.
- **Riscos**: alguém promove pelo Select do `/admin` mesmo assim e abre um chamado de
  "entra e não vê nada". Mitigação: a receita nova em `wikis/receitas.md` (passo 6 do
  plano) descreve o sintoma antes do caminho, e CT-07 trava o contexto correto.

### Referências

- `app/Filament/Admin/Resources/Tenants/RelationManagers/UsersRelationManager.php`
- `KitServiceProvider::configureTenancy()`
- `vendor/spatie/laravel-permission/src/Traits/HasRoles.php` — `roles()` (`wherePivot`) e `syncRoles()`
- `perfil-e-acesso-ao-painel/02-decisoes-arquiteturais.md` ADR-04
- CT-07

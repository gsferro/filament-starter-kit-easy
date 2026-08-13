# Decisões Arquiteturais — Perfil × permissão × acesso ao painel

## ADR-01: O painel é atributo do PAPEL, não da permissão

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

O pedido é "separar as permissões por painel". A leitura literal seria dar a cada painel
o seu próprio conjunto de permissões — `admin.ViewAny:User`, `app.ViewAny:User`. Mas a
geração é do Shield, e investigando a versão instalada (4.3.1):

- o nome da permission é montado por `defaultPermissionKeyBuilder()`
  (`vendor/bezhansalleh/filament-shield/src/FilamentShield.php:86-89`) como
  `{Affix}{separator}{Subject}` — o painel não entra;
- o único ponto em que a identidade do painel chega ao banco é o `guard_name`
  (`Utils::getFilamentAuthGuard()`, `Utils.php:28-31`), e os três painéis do kit usam o
  mesmo guard `web`;
- a tabela `permissions` é a do spatie, sem coluna de painel
  (`database/migrations/2026_08_12_164859_create_permission_tables.php:26-33`).

Renomear as permissões exigiria sobrescrever `buildPermissionKeyUsing()`
(`FilamentShield.php:31-36`) — e aí as policies geradas pelo Shield, que chamam
`$authUser->can('ViewAny:User')` a partir de um stub fixo
(`vendor/.../stubs/SingleParamMethod.stub:4`), passariam a apontar para nomes que não
existem mais. O kit teria de gerar policy por painel para o mesmo model. Um model, uma
policy: essa é a premissa do Laravel inteiro.

### Decisão

A permission continua **global por nome**. O que declara painel é o **papel**, numa
coluna nova `roles.painel` (nullable = vale em qualquer painel).

- Fronteira de painel: `User::canAccessPanel()` exige papel com `roles.painel` igual ao
  id do painel.
- Fronteira de tela: a policy, alimentada pelas permissões do papel.
- Fronteira de dado: o escopo da query (`BelongsToTenant`, `getEloquentQuery()`).

A "separação por painel" que o usuário vê é: papéis rotulados por painel, e a tela do
Shield mostrando as permissões agrupadas pelo painel em que cada Resource está
registrado.

### Alternativas Consideradas

1. **Um guard por painel** (`web_admin`, `web_app`, `web_infra`, todos com o provider
   `users`). O Shield passaria a gerar permissions e roles separados por guard, de
   graça, e a tela de papéis já filtra por guard. Descartada: guards distintos são
   sessões de autenticação distintas — o usuário faria login três vezes, e o Panel
   Switch (`filament-panel-switch`) deixaria de funcionar, porque ele pressupõe uma
   sessão só. O custo de UX é maior que o ganho de modelagem.
2. **Prefixar o nome da permission com o painel** via `buildPermissionKeyUsing()`.
   Descartada: quebra as policies geradas (stub fixo), quebra o `shield:generate` de
   quem já tem banco, e obriga uma policy por painel para o mesmo model.
3. **Derivar o painel das permissões do papel** ("tem permissão de algum Resource do
   painel X → entra no painel X"). Descartada: `panel_user` não tem permission nenhuma e
   ainda assim precisa entrar no `/app`; e a conta viraria uma varredura por request.

### Consequências

- **Positivas**: zero divergência do Shield; uma policy por model; "dar acesso a um
  painel" vira um ato explícito e legível (escolher o papel); o mapa painel × permissão
  é derivado do Filament, não mantido à mão.
- **Negativas**: um mesmo Resource exposto em dois painéis compartilha a permission. Quem
  tiver `Update:Projeto` pelo painel `app` também a tem se abrir o mesmo Resource no
  `admin` — desde que consiga entrar no `admin`, o que `canAccessPanel()` impede.
- **Riscos**: alguém pode ler "permissões separadas por painel" como isolamento de nome e
  se surpreender. Mitigação: a tela agrupa por painel e o texto do grupo diz de onde vem
  o agrupamento; esta ADR é citada em `wikis/arquitetura.md`.

### Referências

- `app/Models/User.php:71-82` (o que é substituído)
- `vendor/bezhansalleh/filament-shield/src/FilamentShield.php:86-89, 147-163`
- Refina: ADR-02

---

## ADR-02: Um guard só nos três painéis

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

Separar guard por painel é a forma **nativa** de o Shield separar permissões: ele lê
`Filament::getCurrentOrDefaultPanel()->getAuthGuard()` ao gravar
(`Utils.php:28-31, 250-255`), e `permissions` tem unique em `['name','guard_name']`.
Seria a solução com menos código do kit.

### Decisão

Manter o guard `web` único nos três painéis.

### Alternativas Consideradas

1. **Guards separados com o mesmo provider `users`.** Descartada: `SessionGuard` guarda
   a sessão sob a chave `login_{guard}_{hash}` — entrar no `/admin` não autentica o
   `/app`. Três logins por sessão de trabalho, e o Panel Switch quebra.
2. **Guards separados com providers separados.** Pior: exigiria model de usuário por
   painel, ou o mesmo model registrado três vezes, e o `HasTenants` do Filament deixaria
   de casar com o usuário autenticado no painel de negócio.

### Consequências

- **Positivas**: um login serve os três painéis; Panel Switch, impersonate e 2FA
  continuam funcionando sem adaptação.
- **Negativas**: a separação por painel tem de ser construída (ADR-01), não vem de graça.
- **Riscos**: se um projeto derivado ligar guards separados, `Paineis::permissoes()`
  passa a devolver conjuntos que o `PapeisSeeder` já sabe recortar — mas
  `canAccessPanel()` continuaria correto, porque lê o papel, não o guard.

### Referências

- `vendor/bezhansalleh/filament-shield/src/Support/Utils.php:28-31`
- Refina: ADR-01

---

## ADR-03: `roles.painel` nullable = papel que não abre painel nenhum

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

`master_global` precisa entrar nos três painéis. Amarrá-lo a um painel seria mentira, e
criar três papéis `master_global` (um por painel) contraria a razão de ele existir: ele
vence por `Gate::before` (`app/Providers/KitServiceProvider.php:87`), sem permission
nenhuma no banco (`PapeisSeeder.php:43-46`).

Isso sugere ler o nulo como coringa — "vale em qualquer painel" —, por analogia com
`roles.team_id`, onde nulo significa exatamente isso (`PapeisSeeder.php:79-88`).

**A analogia é armadilha.** `team_id` nulo é o coringa da **definição** do papel; quem
concede acesso é a atribuição. Já `painel` seria o coringa da **concessão**. Um papel
criado à mão na tela, com o campo em branco, abriria os três painéis — mais poder do que
o autor quis, em silêncio. Numa feature cuja razão de existir é acabar com a concessão
automática de acesso, o default tem de fechar, não abrir.

### Decisão

`roles.painel` é `string nullable` indexada. **Nulo = o papel não abre painel algum.**

`canAccessPanel()` compara `roles.painel` com o id do painel; nulo não casa com nada. O
acesso irrestrito do `master_global` vem de `isMasterGlobal()`, que corta antes da
comparação — nunca da coluna. Papel de negócio puro (um `auditor` que só carrega
permissões, sempre acompanhado de outro papel) é representável, e é o que o nulo diz.

Sem foreign key: painel não é registro de banco, é um id declarado no `PanelProvider` e
resolvido em runtime por `Filament::getPanels()`. Uma FK exigiria uma tabela `paineis`
semeada em sincronia com o código — segunda fonte de verdade para um dado que o
framework já tem.

### Alternativas Consideradas

1. **Nulo como coringa.** Descartada pelo argumento acima: default que abre.
2. **Coluna NOT NULL com valor `'*'` para "todos".** Descartada duas vezes: repõe o
   coringa, e sentinela em string exige que todo `where` lembre do `'*'`.
3. **Tabela pivot `role_painel`** (papel em vários painéis). Descartada como YAGNI: nenhum
   dos quatro papéis do kit precisa de mais de um. Se um projeto precisar, a coluna vira
   pivot sem quebrar `canAccessPanel()` — a comparação já é de igualdade.
4. **Convenção de nome** (`app.gestor`, `admin.suporte`). Descartada: convenção em string
   é dado sem tipo — não indexa direito, não valida, e quebra ao renomear.

### Consequências

- **Positivas**: default fecha. Uma coluna, um índice, comparação de igualdade. Migration
  puramente aditiva, `down()` trivial.
- **Negativas**: `master_global` depende de um segundo mecanismo (`Gate::before`) para
  entrar nos painéis — o dado sozinho não explica o acesso dele. É o mecanismo que já
  existia; esta ADR só não o substitui.
- **Riscos**: papel criado sem painel parece quebrado ("dei a permissão e a pessoa não
  entra"). Mitigação: o `Select` do painel na tela do Shield tem `helperText` dizendo que
  é ele que dá o acesso, e `wikis/receitas.md#problemas-comuns` ganha a linha.

### Referências

- `database/seeders/PapeisSeeder.php:79-88` (o `team_id`, que é o caso oposto)
- CT-08 em `04-casos-de-teste.md` trava a leitura
- Refina: ADR-01

---

## ADR-04: Painel sem tenancy exige o papel no contexto global

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

Com `permission.teams` ligado, `model_has_roles.team_id` é NOT NULL: toda atribuição de
papel pertence a um contexto. O kit usa o sentinela `Tenant::CONTEXTO_GLOBAL = 0` para os
papéis que governam a instalação (`app/Models/Tenant.php:62`).

`canAccessPanel()` roda **antes** de o tenant da rota ser identificado, então não pode
depender do contexto corrente. Duas leituras possíveis:

- "tem o papel em qualquer contexto" — simples, e **errado** para `/admin`: alguém
  promovido a `admin` dentro da organização Acme passaria a administrar a instalação
  inteira;
- "tem o papel no contexto global" — correto para `/admin` e `/infra`, e **errado** para
  `/app`: lá o papel é atribuído dentro de cada organização, e exigir contexto global
  fecharia o painel para todo mundo.

### Decisão

A regra vem do próprio painel:

```php
$contexto = $panel->hasTenancy() ? null : $this->contextoGlobal();
```

- Painel **com** tenancy (`/app`): papel em **qualquer** organização basta. Qual
  organização é decidido depois, por `canAccessTenant()` — que já responde 404 (não 403)
  em organização não vinculada, para não permitir enumerar clientes por varredura de
  slug (`app/Models/User.php:167-187`, `wikis/arquitetura.md#404-não-403`).
- Painel **sem** tenancy (`/admin`, `/infra`): papel atribuído em
  `Tenant::CONTEXTO_GLOBAL`.
- Com `permission.teams` desligado não há contexto nenhum, e `contextoGlobal()` devolve
  `null` — um caminho de código só para os dois modos.

Isto preserva, sem lista de nomes de papel, exatamente a propriedade que o comentário do
`canAccessPanel()` atual declara: "ser `admin` dentro de um tenant não é credencial para
administrar o sistema" (`app/Models/User.php:74-76`).

### Alternativas Consideradas

1. **Marcar o painel como global numa config do kit.** Descartada: `$panel->hasTenancy()`
   já responde, e uma config duplicada dessincroniza — foi exatamente o bug da versão
   0.9.6, onde três chaves precisavam concordar e não concordavam.
2. **Exigir contexto global em todos os painéis.** Descartada: fecharia o `/app`.

### Consequências

- **Positivas**: a propriedade de segurança sobrevive à remoção da lista de nomes; a
  regra é derivada do painel, não declarada em segundo lugar.
- **Negativas**: `canAccessPanel()` tem um ramo condicional a mais.
- **Riscos**: um painel novo que ligue tenancy herda "papel em qualquer organização
  basta" sem que ninguém decida isso. É o comportamento correto para painel de negócio,
  que é o único caso em que se liga tenancy no kit.

### Referências

- `app/Models/Tenant.php:47-62`
- `app/Models/User.php:71-82, 167-187`
- `app/Providers/KitServiceProvider.php:74-81`

---

## ADR-05: Publicar o `RoleResource` do Shield

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

Agrupar a tela de papéis por painel exige mudar `getResourceEntitiesSchema()`
(`vendor/bezhansalleh/filament-shield/src/Traits/HasShieldFormComponents.php:38-57`).
Não existe hook, config ou método de plugin para isso: os métodos de customização do
`FilamentShieldPlugin` cobrem colunas, tradução de rótulos e a visão "simples", nada de
agrupamento.

### Decisão

Rodar `shield:publish --panel=admin`. Copia `RoleResource.php` e as quatro Pages para
`app/Filament/Admin/Resources/Roles/`, reescrevendo o namespace
(`vendor/.../src/Commands/PublishCommand.php:60-91`). O `FilamentShieldPlugin` para de
registrar o Resource dele sozinho — `Utils::isResourcePublished()` procura `\RoleResource`
entre os resources do painel (`Utils.php:33-41`).

Regra de manutenção: **editar o mínimo**. Um método de comportamento
(`getResourceEntitiesSchema`), um campo (`painel`) e duas listas nas Pages. O resto fica
byte a byte como o vendor, para o diff de um upgrade futuro ser legível.

### Alternativas Consideradas

1. **Estender `BezhanSalleh\...\RoleResource` num Resource próprio**, sem publicar. Um
   arquivo em vez de cinco. Descartada: as Pages do vendor declaram
   `protected static string $resource = RoleResource::class` apontando para a classe do
   vendor (`vendor/.../Pages/EditRole.php:20`) — a subclasse teria as Pages resolvendo de
   volta para o pai, e as duas listas de `mutateFormDataBefore*` (que precisam conhecer
   `painel`) ficariam fora de alcance.
2. **Deixar a tela como está e documentar.** Descartada: era o pedido explícito.
3. **Segundo Resource, só de leitura, mostrando a matriz por painel.** Descartada: duas
   telas para o mesmo dado, e a de escrita continuaria confusa.

### Consequências

- **Positivas**: caminho oficial do pacote; a tela é do projeto e pode evoluir com as
  outras wikis (o admin da organização precisa dela recortada).
- **Negativas**: cinco arquivos do vendor entram no repositório. Upgrade maior do Shield
  exige `diff` contra o vendor novo.
- **Riscos**: o formato da entidade (`resourceFqcn`, `model`, `modelFqcn`, `permissions`)
  é contrato interno do Shield. CT-10 falha se mudar.

### Referências

- `vendor/bezhansalleh/filament-shield/src/Commands/PublishCommand.php:60-91`
- `vendor/bezhansalleh/filament-shield/src/Support/Utils.php:33-41`
- `vendor/bezhansalleh/filament-shield/src/Resources/Roles/Pages/CreateRole.php:22-35`

---

## ADR-06: `Paineis::permissoes()` pergunta ao Shield, não remonta o nome

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

O `PapeisSeeder` precisa saber quais permissões pertencem a cada painel. Hoje ele
adivinha por substring — `str_contains($p, 'User')` (`PapeisSeeder.php:53-58`) — o que
faz um Resource futuro chamado `UserPreference` cair no papel `admin` sem ninguém
decidir.

Montar o nome corretamente exige reproduzir quatro chaves de config: `permissions.separator`,
`permissions.case`, `resources.subject`/`pages.subject`/`widgets.subject` e a lista de
`policies.methods` (`config/filament-shield.php:119-124, 187, 214, 233`).

### Decisão

`Paineis` chama `FilamentShield::getEntitiesPermissions()`
(`vendor/.../src/FilamentShield.php:114-124`) — a **mesma** função de onde o
`shield:generate` tira o que gravar. Se o projeto trocar o `case` para `kebab`, o seeder
acompanha sem uma linha de mudança.

Para varrer os três painéis num processo só, é preciso descartar a instância memoizada
entre as voltas:

```php
app()->forgetInstance('filament-shield');
Filament::setCurrentPanel($painel);
```

`FilamentShield` é `scoped` (`FilamentShieldServiceProvider.php:46`) e usa `once()`
(`FilamentShield.php:66-84`), que memoiza por instância — instância nova, memo novo. Sem
o `forgetInstance`, os três painéis devolvem o resultado do primeiro.

A varredura por painel só é possível porque `discovery.discover_all_*` está `false` nas
três chaves (`config/filament-shield.php:268-272`); com `true`, `getResources()` passa a
achatar todos os painéis e o mapa vira ruído.

### Alternativas Consideradas

1. **Remontar o nome da permission no kit.** Descartada: quatro chaves de config
   duplicadas, que dessincronizam em silêncio.
2. **Tabela de mapa painel × permissão semeada à mão.** Descartada: apodrece a cada
   Resource novo — exatamente o problema que o `master_global` sem permissions evita
   (`PapeisSeeder.php:43-46`).
3. **Três processos** (`Artisan::call` num sub-processo por painel). Descartada: mais
   lento, e não funciona dentro de um teste.

### Consequências

- **Positivas**: uma fonte de verdade; o seeder sobrevive a mudança de convenção de nome.
- **Negativas**: o kit passa a depender de dois detalhes internos do Shield — o nome do
  binding (`filament-shield`) e a memoização por instância.
- **Riscos**: se `discovery.discover_all_resources` for ligado num projeto derivado, o
  mapa deixa de separar painéis. **Mitigação**: `Paineis` falha alto (`RuntimeException`)
  quando qualquer chave de `discovery` estiver ligada — silêncio aqui viraria matriz de
  permissão errada.

### Referências

- `vendor/bezhansalleh/filament-shield/src/FilamentShield.php:66-84, 114-124`
- `vendor/bezhansalleh/filament-shield/src/FilamentShieldServiceProvider.php:46`
- `config/filament-shield.php:268-272`

---

## ADR-07: Sem migration de retrofit

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

`canAccessPanel()` passar a exigir papel fecha o `/app` para todo usuário que não tenha
papel com `painel = 'app'`. Num projeto já instalado, isso é perda de acesso no primeiro
`kit:update`.

Uma migration de retrofit resolveria: carimbar `painel` nos quatro papéis conhecidos e
atribuir `panel_user` a quem ficou sem nenhum.

### Decisão

Não fazer. O kit está em pré-release (0.10.0, sem 1.0); a orientação do mantenedor é
implementar do jeito certo mesmo quebrando o que está em evolução. O CHANGELOG registra
como quebra e manda rodar os dois seeders.

Uma migration que **atribui papel** a usuários é, além disso, uma migration que concede
acesso — e concessão automática de acesso é exatamente o que esta feature existe para
acabar. Escrever uma seria contradizer a feature na primeira linha.

### Alternativas Consideradas

1. **Migration carimbando só `roles.painel`** (sem tocar em `model_has_roles`). Metade do
   retrofit, e a metade que o `PapeisSeeder` já faz de graça com `updateOrCreate`.
   Descartada por redundância.
2. **Retrofit completo.** Descartada pelo argumento acima.

### Consequências

- **Positivas**: nenhuma concessão automática de acesso; menos código.
- **Negativas**: quem atualizar precisa rodar dois seeders e revisar os usuários.
- **Riscos**: atualização silenciosa deixa usuários sem `/app`. Mitigação: nota de quebra
  no CHANGELOG, com os comandos.

### Referências

- `CHANGELOG.md` (nota da versão)
- `database/seeders/PapeisSeeder.php`

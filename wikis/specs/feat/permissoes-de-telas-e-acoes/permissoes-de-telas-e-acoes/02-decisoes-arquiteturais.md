# Decisões Arquiteturais — Permissões de telas e ações

## ADR-01: Um concern do kit envolvendo a trait do Shield, em vez da trait do Shield direto

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

`HasPageShield` e `HasWidgetShield` são o ponto de integração publicado do
`bezhansalleh/filament-shield`. Cada uma sobrescreve **um** método (`canAccess()` /
`canView()`) resolvendo a chave de permissão pela própria descoberta do Shield.

O problema é que 18 dos 23 Widgets e 3 das 5 Pages **já definem** esse método — com regra local
legítima: os Widgets checam existência de tabela (`Schema::hasTable()`, porque a fonte é de plugin
opcional e widget que estoura derruba o dashboard inteiro), as Pages checam `config('kit.hub')` ou
tenancy.

Em PHP, **método definido na classe vence método vindo de trait**, sem erro, sem aviso e sem
deprecation. `use HasWidgetShield;` num widget que já tem `canView()` é uma linha que não faz
absolutamente nada — a feature ficaria verde no diff e inexistente em execução.

### Decisão

Dois concerns no kit, `app/Filament/Concerns/ExigePermissaoDaTela.php` e
`ExigePermissaoDoWidget.php`. Cada um **usa** a trait do Shield, dá **alias** ao método dela, e
publica o método real como `permissão && regra local`, onde "regra local" é um hook com default
`true` que a classe sobrescreve.

```php
use HasWidgetShield { canView as protected visivelPelaPermissao; }

public static function canView(): bool
{
    return static::visivelPelaPermissao() && static::fonteDeDadosDisponivel();
}
```

### Alternativas Consideradas

1. **`use HasWidgetShield;` direto nos 23 widgets** — descartada: silenciosamente no-op nos 18 que
   têm `canView()`. É o defeito que esta ADR existe para evitar.
2. **`use HasWidgetShield;` só nos 5 sem `canView()`, e AND manual nos 18** — descartada: dois
   padrões para o mesmo problema, 18 cópias de `static::algumaCoisa() && rescue(...)` e nenhum
   lugar único para um teste de arquitetura apontar.
3. **Reimplementar a resolução da chave no concern do kit** (`View:` + `class_basename`) —
   descartada: reimplementaria `permissions.case`, `permissions.separator`, `widgets.prefix` e
   `pages.prefix`, quatro chaves de `config/filament-shield.php` que dessincronizam em silêncio. É
   exatamente a razão pela qual `App\Support\Paineis` pergunta ao Shield em vez de montar o nome
   (docblock `Paineis.php:24-29`).
4. **Mover o `Schema::hasTable()` para fora do `canView()`** — descartada: é refactor de outra
   feature, e o `hasTable()` no `canView()` é o padrão que `.ai/rules/filament.md` §"Qual pacote de
   widget" manda seguir.

### Consequências

- **Positivas**: um lugar único para a regra; hook explícito para a regra local; teste de
  arquitetura pode exigir o concern em toda classe de `app/Filament/*/Widgets/**`; upgrade do
  Shield continua sendo herdado (a lógica de chave é dele).
- **Negativas**: o alias de trait é sintaxe pouco usada e precisa de comentário para não parecer
  enfeite. Documentado no docblock dos dois concerns.
- **Riscos**: **fail-open herdado.** `HasPageShield::canAccess()` (`:24-26`) cai em
  `parent::canAccess()` — que é `true` — quando a chave não resolve **ou** quando não há usuário
  autenticado. A chave não resolve se `FilamentShield::getPages()` não contiver a classe, o que
  acontece quando o painel corrente não é o da Page. Em request real isso não ocorre (o middleware
  `SetUpPanel` fixa o painel antes de qualquer Page ser tocada); em teste de componente ocorre se
  ninguém chamar `Filament::setCurrentPanel()`, e o kit já tem `noPainelBootado()`/`noPainelDa()`
  no `tests/Pest.php` para isso. **Não** invertemos para fail-closed: mudar o comportamento do
  vendor por dentro de um alias é pior que herdá-lo, e o cenário fail-open é de teste, não de
  produção. Registrado como proposta de rule no `03`.

### Referências

- `vendor/bezhansalleh/filament-shield/src/Traits/HasPageShield.php:14-37`
- `vendor/bezhansalleh/filament-shield/src/Traits/HasWidgetShield.php:14-32`
- `vendor/filament/widgets/src/Widget.php:34-37`
- `app/Support/Paineis.php:24-29`
- `.ai/rules/filament.md` §"Qual pacote de widget"

---

## ADR-02: Dois mecanismos para as permissões de Action — `resources.manage` e `custom_permissions`

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

Action não é entidade do Shield: `HasEntityDiscovery` cobre Resources, Pages e Widgets e nada mais
(`vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityDiscovery.php:13-21`). Permissão para
Action, portanto, tem que ser **declarada**. O Shield oferece duas vias, e elas têm propriedades
opostas no ponto que mais importa aqui: **escopo de painel**.

- **`resources.manage[$resource] = [...métodos]`** com `policies.merge => true` acrescenta chaves
  ao Resource (`HasEntityTransformers.php:171-188`). Como Resource pertence a um painel, a
  permissão nasce **escopada** — `Paineis::permissoes('admin')` a inclui e
  `Paineis::permissoes('app')` não.
- **`custom_permissions`** é um array plano `chave => rótulo` lido de config, **sem nenhuma noção
  de painel** (`HasEntityTransformers.php:88-112`), e `getEntitiesPermissions()` faz `merge` das
  chaves dele na matriz de **todo** painel (`FilamentShield.php:114-124`).

Se `resources.manage` é estritamente melhor, por que usar `custom_permissions`? Por causa da
subtração. `PapeisSeeder::permissoesDeAdministracaoDoApp()` remove do `panel_user` **todas** as
permissões do `App\Filament\App\Resources\Convites\ConviteResource`, casando por FQCN, em bloco. As
duas Actions da Page `ConvitesRecebidos` (`aceitar`, `recusar`) precisam ficar **com** o
`panel_user` — é o usuário comum que aceita o próprio convite. Declará-las no `ConviteResource` do
`/app` as jogaria dentro da subtração e quebraria o fluxo de convite para o público-alvo dele, sem
erro nenhum: o botão simplesmente desapareceria.

### Decisão

- **4 permissões via `resources.manage`**, todas do painel `admin`, onde não há subtração:
  `Reenviar:Convite` (no `Admin\...\ConviteResource`), `VincularUsuario:Tenant`,
  `DesvincularUsuario:Tenant`, `AtribuirPapeis:Tenant` (no `TenantResource`).
- **2 permissões via `custom_permissions`**, porque pertencem a uma **Page** e não a um Resource:
  `Aceitar:Convite`, `Recusar:Convite`.
- O vazamento de painel das custom permissions é fechado no `PapeisSeeder` — ADR-03.

### Alternativas Consideradas

1. **Tudo em `custom_permissions`** — descartada: perderia o escopo de painel das 4 de `admin`, e
   `Reenviar:Convite` acabaria no `panel_user`.
2. **Tudo em `resources.manage`** — descartada pela subtração descrita acima.
3. **Afinar `permissoesDeAdministracaoDoApp()` para subtrair "todas menos `Aceitar:`/`Recusar:`"** —
   descartada: reintroduz casamento por nome numa **subtração**, e o docblock daquele método
   (`PapeisSeeder.php:148-152`) registra que casamento por nome foi removido de lá de propósito,
   porque numa subtração o erro é o espelhado — tirar permissão de quem deveria tê-la.
4. **Reusar `View:ConvitesRecebidos` nas duas Actions** — descartada: viola RQ-07 ("permissão
   específica"), e junta "ver a caixa de entrada" com "entrar numa organização" no mesmo checkbox.
5. **Adicionar `aceitar`/`recusar` a `policies.methods` global** — descartada: geraria
   `Aceitar:{Model}` para os 14 models do kit, 28 permissões inúteis.

### Consequências

- **Positivas**: as 4 de `admin` chegam ao papel `admin` **sem nenhuma mudança de seeder** — o
  `PapeisSeeder` colhe a matriz do painel e elas estão nela. Zero risco de "permissão órfã".
- **Negativas**: duas vias para a mesma classe de coisa, e quem acrescentar uma Action nova tem de
  escolher. A regra de escolha é uma frase: **Action de Resource vai em `resources.manage`; Action
  de Page vai em `custom_permissions` + mapa de painel.** Escrita no comentário do config.
- **Riscos**: a via `custom_permissions` é a perigosa, e ADR-03 é a mitigação.

### Referências

- `vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityTransformers.php:88-112,171-188`
- `vendor/bezhansalleh/filament-shield/src/FilamentShield.php:114-124`
- `database/seeders/PapeisSeeder.php:140-167`
- `.ai/rules/filament.md` §"Resource, Page ou Widget de administração no painel `app`…"

---

## ADR-03: Custom permission declara o painel a que pertence, e sem declaração ninguém a recebe

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

`transformCustomPermissions()` lê `config('filament-shield.custom_permissions')` e devolve as
chaves formatadas, **sem consultar painel**. `getEntitiesPermissions()` faz `merge` dessas chaves
na matriz de qualquer painel que se pergunte. Logo `Paineis::permissoes('admin')`,
`Paineis::permissoes('infra')` e `Paineis::permissoes('app')` devolvem **as mesmas** custom
permissions, e o `PapeisSeeder` as entrega a `admin`, `infra`, `admin_app` e `panel_user`.

Hoje o estrago seria nulo — `Aceitar:Convite` só é consultada numa Page do `/app`, e o papel
`admin` não abre o `/app`. Mas o mecanismo é o over-grant silencioso que `.ai/rules/filament.md`
chama de "a falha mais cara desta parte do kit": a **próxima** custom permission, que provavelmente
será de uma Action de administração, cai no `panel_user` sem migration, sem 403 e sem log.

### Decisão

O recorte fica no `PapeisSeeder`, no ponto único por onde toda permissão passa antes de chegar a um
papel (`permissoesDoPainel()`). Um mapa privado declara, por chave custom, os painéis a que ela
pertence; `permissoesDoPainel($painel)` rejeita chave custom cujo mapa não inclua `$painel`.

**Fail-closed de propósito**: custom permission **sem** entrada no mapa não vai para papel nenhum.
Quem acrescenta uma custom permission e esquece o mapa vê o botão sumir — e o caso de teste de
arquitetura fica vermelho apontando a chave.

### Alternativas Consideradas

1. **Não fazer nada** — descartada: hoje é inócuo, amanhã é `panel_user` com permissão de
   administração. O custo de fechar agora é ~15 linhas num arquivo que já existe.
2. **Fail-open (chave sem mapa vai para todos)** — descartada: preserva exatamente o defeito.
3. **`buildPermissionKeyUsing()` com prefixo de painel na chave** (`app:Aceitar:Convite`) —
   descartada: muda o formato de **todas** as permissões do kit, quebra as 14 policies escritas à
   mão e o `separator` da config.
4. **Um `Paineis::permissoesCustomizadas()`** — descartada por ora: `Paineis` deriva do Filament e
   do Shield, e este mapa é uma **decisão de matriz de papéis**, que é o assunto do `PapeisSeeder`.
   Mover para `Paineis` se um segundo consumidor aparecer.

### Consequências

- **Positivas**: a armadilha fica fechada para toda custom permission futura, com enforço por
  teste em vez de por prosa (é o que a §9 da `feature-wiki` e `.ai/rules/specs.md` pedem).
- **Negativas**: a declaração da permissão fica em **dois** arquivos — `config/filament-shield.php`
  (existência e rótulo) e `PapeisSeeder` (painel). Comentário cruzado nos dois.
- **Riscos**: o teste de arquitetura é a única coisa que impede o esquecimento. Ele existe (CT-14).

### Referências

- `vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityTransformers.php:88-112`
- `vendor/bezhansalleh/filament-shield/src/FilamentShield.php:119`
- `database/seeders/PapeisSeeder.php:223-229`

---

## ADR-04: `AttachAction` e `DetachAction` de RelationManager entram no escopo, apesar de serem Actions nativas

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

O `00-requisito.md` declara fora de escopo as Actions nativas (`CreateAction`, `EditAction`,
`DeleteAction`, …) porque o Filament as autoriza pela policy do model, via
`getDefaultActionAuthorizationResponse()`. Isso é verdade em Resource e em Page de Resource.

Em **RelationManager** não é, e o vendor diz isso em comentário, no próprio método:

> `vendor/filament/filament/src/Resources/RelationManagers/RelationManager.php:348-353`
> *"Security: `AssociateAction`, `AttachAction`, `DetachAction`, and `DissociateAction` only check
> `isReadOnly()` — they do not check specific policy methods."*

O arm do `match` em `:359` confirma: para essas classes a resposta é `$this->isReadOnly() ?
Response::deny() : null`, e `null` significa "sem opinião", que o
`CanBeAuthorized::resolveIsAuthorized()` (`:106-107`) converte em **permitido**.

No kit isso tem consequência concreta e não teórica. `UsersRelationManager` é o RelationManager de
`TenantResource`, e a linha do pivot `tenant_user` que ele cria é **exatamente** o que
`User::canAccessTenant()` consulta para decidir quem abre `/app/{slug}`. Quem consegue abrir a tela
de visualização de uma organização — permissão `View:Tenant` — pode hoje vincular qualquer usuário
da instalação a ela, e desvincular qualquer um.

### Decisão

`AttachAction` e `DetachAction` deste RelationManager recebem `->authorize()` com permissão própria
(`VincularUsuario:Tenant`, `DesvincularUsuario:Tenant`). A generalização "Action nativa já está
coberta" passa a valer **só fora de RelationManager**, e isso é o que a proposta de rule no `03`
registra.

### Alternativas Consideradas

1. **Deixar como está** — descartada: é o furo de escalonamento de privilégio mais direto do kit,
   e o requisito é literalmente sobre isso.
2. **`->authorize('update')` reusando `TenantPolicy::update`** — descartada: junta "editar o nome da
   organização" com "decidir quem entra nela". E a Action é `headerAction`, sem record: o
   `parseAuthorizationArguments()` (`CanBeAuthorized.php:80-89`) empurra o **model da relação**
   (`User`), então `Gate::check('update', [User::class])` resolveria a `UserPolicy`, não a
   `TenantPolicy` — o oposto do pretendido.
3. **`->authorize()` com nome de método de policy + argumento explícito** — descartada pelo mesmo
   motivo: o record/model é empurrado para a frente dos argumentos e a policy resolvida é sempre a
   do model da relação.
4. **`isReadOnly()` verdadeiro no RelationManager** — descartada: trancaria a tela para todos,
   inclusive `admin`, e o bootstrap do primeiro `admin_app` de uma organização depende dela
   (docblock de `acaoDePapeis()`, `:74-86`).

### Consequências

- **Positivas**: o vínculo que dá acesso a `/app/{slug}` passa a ser um checkbox próprio.
  Torna-se possível um papel que **vê** organizações sem **mexer** em quem entra nelas — que é a
  flexibilidade de RQ-09.
- **Negativas**: a fronteira "nativa vs. customizada" deixa de ser a linha divisória; a linha passa
  a ser "está num RelationManager?". Mais uma coisa a lembrar — daí a proposta de rule.
- **Riscos**: nenhum novo. `admin` recebe as três permissões pela matriz do painel `admin`, então o
  default do kit não muda.

### Referências

- `vendor/filament/filament/src/Resources/RelationManagers/RelationManager.php:346-371`
- `vendor/filament/actions/src/Concerns/CanBeAuthorized.php:16-21,80-89,104-128`
- `app/Filament/Admin/Resources/Tenants/RelationManagers/UsersRelationManager.php:57-93`
- `.ai/rules/filament.md` §"Resource ou RelationManager novo exige gerar as permissões"

---

## ADR-05: Page e Widget de vendor ficam com a permissão gerada e não consultada

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

O painel `/infra` tem 9 Pages de vendor (`HealthCheckResults`, `BackupRunsPage`, `LogsExplorer`,
`DependencyGraphPage`, `Commands`, `History`, `RunView`, `RecycleBin`) mais a `MyProfilePage` do
`filament-edit-profile`, e 1 Widget de vendor (`ComposerReleaseOverviewWidget`). Todas têm
permissão gerada — `View:LogsExplorer`, `View:RecycleBin`, … — porque o Shield descobre por
`$panel->getPages()` cru, e todas aparecem como checkbox em `/admin/shield/roles`.

Nenhuma delas pode receber um `use ExigePermissaoDaTela;`: são classes de pacote, registradas pelo
plugin e não pelo `discoverPages()` do provider. Três delas **já** têm `canAccess()` próprio
(`HealthCheckResults:86`, `LogsExplorer:107`, `RecycleBin:66`), duas via gate do kit
(`command-center:access` para `Commands`/`History`, `ver-logs` para `LogsExplorer`).

### Decisão

Fora desta entrega, declarado em `00-requisito.md` `## Fora desta entrega`. O checkbox existe e não
faz nada para essas classes — e isso fica **escrito**, em vez de ficar implícito.

### Alternativas Consideradas

1. **Subclassear cada Page de vendor no kit e registrar a subclasse** — descartada: o Shield geraria
   permissão para o nome da subclasse (`View:LogsExplorerDoKit`), quebrando o checkbox que já
   existe; o slug, o cluster e o registro do plugin teriam de ser replicados por classe; e
   `.ai/rules/providers-filament.md` documenta que alguns desses pacotes resolvem o plugin pelo
   painel corrente e derrubam a aplicação inteira quando o registro sai do esperado. Esforço
   comparável ao da entrega toda, com risco desproporcional.
2. **`->registerNavigation(false)` nos plugins** — descartada: esconde o item de menu e **não**
   mexe em `canAccess()` (o mesmo engano já registrado no docblock de
   `PapeisSeeder::permissoesForaDoApp()`, `:180-181`). A rota continua respondendo.
3. **Excluir essas classes de `config('filament-shield.pages.exclude')`** para o checkbox não
   mentir — descartada: tiraria a permissão do banco, e ela é a única alavanca disponível caso o
   pacote passe a consultá-la num upgrade. Checkbox inerte documentado é melhor que alavanca
   removida.

### Consequências

- **Positivas**: escopo fechado e honesto; a alavanca fica no banco para o dia em que houver como
  usá-la.
- **Negativas**: RQ-05 ("TODAS as telas") fica **parcialmente** atendido, e isso está marcado como
  tal na cobertura do requisito. A barreira efetiva dessas 10 telas continua sendo
  `canAccessPanel()` + os 4 gates nomeados.
- **Riscos**: alguém lê o checkbox e conclui que a tela está protegida. Mitigado por: o
  `00-requisito.md` lista as 10 classes por nome, e a proposta de rule no `03` inclui a frase.

### Referências

- `vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityDiscovery.php:30-34`
- `app/Providers/Filament/InfraPanelProvider.php:99-101`
- `.ai/rules/providers-filament.md`
- ADR-02 de `wikis/specs/feature/v1-enriquecimento-kit/hub-de-cards-opcional/`

---

## ADR-06: A flag `kit.hub` e a permissão são ortogonais, e as duas continuam valendo

**Status**: Aceita
**Data**: 2026-08-24
**Refina**: ADR-02 e ADR-03 de `wikis/specs/feature/v1-enriquecimento-kit/hub-de-cards-opcional/`

### Contexto

`HubDeAdministracao` e `HubDoNegocio` têm hoje `canAccess()` = `config('kit.hub') && parent::canAccess()`.
Acrescentar a checagem de permissão cria a pergunta: as duas condições coexistem, ou uma substitui a
outra?

A wiki ancestral já respondeu para o lado da permissão: CT-06 de `tests/Kit/HubDeCardsTest.php`
(`:126-134`) assere que desligar a flag **não** remove `View:HubDeAdministracao` da matriz, e ADR-02
de lá recusou implementar o desligamento como permissão justamente porque `master_global` venceria
qualquer permissão pelo `Gate::before` e a flag deixaria de desligar nada para ele. CT-01 (`:47-60`)
tem `master_global` como linha discriminante exatamente disso.

### Decisão

As duas coexistem, com `&&`: `regraLocalDeAcesso()` guarda a flag, o concern guarda a permissão.
Flag desligada → ninguém abre, nem `master_global`. Flag ligada → abre quem tem a permissão.

`HubDeInfraestrutura` continua **sem** flag, por ADR-03 da ancestral, e ganha só a permissão. O
docblock dele (`:52-54`) proíbe acrescentar `canAccess()` **com a flag** — foi lido antes de mexer,
e a proibição é sobre a flag, não sobre permissão. O cenário guarda dessa decisão
(`'infra com a flag desligada'`, `HubDeCardsTest.php:110`) usa papel `infra`, que tem
`View:HubDeInfraestrutura`, e segue verde. Se ficar vermelho, o teste está certo: significa que a
permissão não chegou ao papel, e o defeito é de seeder.

### Alternativas Consideradas

1. **Permissão substitui a flag** — descartada: reverte ADR-02 da ancestral e deixa a flag inócua
   para `master_global`. CT-01 daquele arquivo ficaria vermelho, e com razão.
2. **Flag substitui a permissão nos dois hubs** — descartada: viola RQ-05 e deixa dois dos três
   hubs sem permissão consultada.

### Consequências

- **Positivas**: nenhum teste existente da ancestral muda de significado; o `03-progresso.md` não
  precisa justificar alteração de teste.
- **Negativas**: duas condições para uma tela. Documentado no docblock de `regraLocalDeAcesso()` de
  cada hub.

### Referências

- `tests/Kit/HubDeCardsTest.php:34-60,88-114,116-134`
- `app/Filament/Infra/Pages/HubDeInfraestrutura.php:43-54`
- `vendor/filament/filament/src/Pages/Page.php:133-135`

---

## ADR-07: Nenhum log novo

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

A `feature-wiki` exige log em toda etapa de execução e um channel por feature. Esta feature não
acrescenta execução: acrescenta guardas em métodos consultados em **loop de render**.

### Decisão

Nenhum channel novo e nenhuma linha de log nova. Justificativa completa em
`01-plano-acao.md` → `## Channel de Log da Feature`; em resumo: `canView()` roda 16 vezes por
carregamento do dashboard do `/infra` e `canAccess()` é consultado por cartão de hub, item de
navegação e categoria do Spotlight — logar negativa aí esconde o evento em vez de registrá-lo. Os
eventos que interessam auditar (attach, detach, atribuição de papéis, reenvio, revogação) **já**
logam sucesso no channel `autenticacao`, e nenhum deles é alterado.

### Alternativas Consideradas

1. **Channel `permissoes` com `debug` de cada negativa** — descartada: dezenas de linhas por
   request no default do kit.
2. **Log só na negativa de Action** — descartada: a Action negada não é executada, ela é
   **ocultada** na renderização; o gancho seria o mesmo loop de render.
3. **Listener de `Illuminate\Auth\Access\Events\GateEvaluated`** — a saída certa para quem quiser a
   trilha, mas é feature própria (amostragem, filtro por ability, retenção). Registrada como
   proposta no `03`.

### Consequências

- **Positivas**: zero ruído acrescentado; nenhum custo por request.
- **Negativas**: não há trilha de "tentou e foi negado". Aceito: o `403` fica no log de acesso do
  servidor web, e a Action oculta não gera tentativa.

### Referências

- `vendor/filament/actions/src/Concerns/CanBeAuthorized.php:237-250`
- `app/Filament/Admin/Resources/Tenants/RelationManagers/UsersRelationManager.php:112-121`
- `config/logging.php:132`

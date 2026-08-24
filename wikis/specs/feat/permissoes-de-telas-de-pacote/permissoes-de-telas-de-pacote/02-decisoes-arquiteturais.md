# Decisões Arquiteturais — W6: permissões das telas de pacote

## ADR-01: O callback de autorização que o pacote publica, não um middleware de painel

**Status**: Aceita
**Data**: 2026-08-24
**Substitui**: ADR-05 de `wikis/specs/feat/permissoes-de-telas-e-acoes/permissoes-de-telas-e-acoes/`
(parcialmente — 7 das 10 classes; as outras 3 continuam declaradas, ver ADR-05 desta wiki)

### Contexto

ADR-05 da wiki ancestral recusou a entrega por um argumento correto e um caminho incompleto. O
argumento correto: `.ai/rules/providers-filament.md` documenta que mexer no registro de certos
plugins derruba a **aplicação** — `marjose123/filament-lockscreen` resolve o próprio plugin no
`routes/web.php` durante o boot, e `bezhansalleh/filament-exceptions` faz
`FilamentExceptionsPlugin::get()` nos métodos estáticos de navegação, o que estoura
`LogicException: Plugin [...] is not registered for panel [app]` em **todo** comando artisan.

O caminho incompleto: ADR-05 avaliou duas alternativas — subclassear cada Page (e perder a chave
da permissão) ou desligar a navegação — e nenhuma das duas é o ponto de extensão que os pacotes
publicam. Sete das dez classes chegam ao `canAccess()` **por dentro do plugin**:

| Classe | Método que decide | `file:line` |
|---|---|---|
| `HealthCheckResults` | `plugin->isAuthorized()` | `vendor/shuvroroy/filament-spatie-laravel-health/src/Pages/HealthCheckResults.php:86-89` |
| `RecycleBin` | `plugin->isAuthorized()` | `vendor/promethys/revive/src/Pages/RecycleBin.php:66-69` |
| `LogsExplorer` | `plugin->canAccess()` | `vendor/laboiteacode/filament-logs-explorer/src/Pages/LogsExplorer.php:107-110` |
| `DependencyGraphPage` | `plugin->isVisible()` | `vendor/laboiteacode/filament-dependency-graph/src/Filament/Pages/DependencyGraphPage.php:107-110` |

E os quatro plugins têm setter para o predicado: `authorize(bool|Closure)`
(`FilamentSpatieLaravelHealthPlugin.php:41-51`, `RevivePlugin.php:155-168`),
`canAccessUsing(Closure)` (`FilamentLogsExplorerPlugin.php:250-255`,
`DependencyGraphPlugin.php:122-125`).

A alternativa considerada com mais força foi um **middleware de painel** que resolvesse a classe da
Page pela rota e checasse a permissão — uma classe cobrindo as dez de uma vez, sem tocar em plugin
nenhum.

### Decisão

Usar o callback publicado pelo pacote. Nenhum middleware.

### Alternativas Consideradas

1. **Middleware no `->middleware([])` do painel, resolvendo a Page pela rota** — descartada por
   **dois** motivos independentes, e cada um sozinho já bastaria:
   - **cobre só o `GET` inicial.** O Filament reavalia autorização em **todo** request Livewire, e
     o vendor documenta isso: `Pages\Concerns\CanAuthorizeAccess::hydrateCanAuthorizeAccess()`
     (`vendor/filament/filament/src/Pages/Concerns/CanAuthorizeAccess.php:12-15`) chama
     `abort_unless(static::canAccess(), 403)` a cada hidratação. Requests Livewire vão para
     `/livewire/update`, que não passa pelo middleware do painel — a barreira valeria para a
     primeira visita e não para as interações seguintes.
   - **não esconde o item de menu.** `Page::registerNavigationItems()` retorna cedo quando
     `canAccess()` é falso (`vendor/filament/filament/src/Pages/Page.php:133-135`). Com
     middleware, o item continuaria na barra lateral e daria 403 no clique — que é exatamente o
     defeito que `.ai/rules/filament.md` §"CardItem do hub" nomeia: "vaza a existência da tela e
     oferece um caminho que falha depois".
2. **`->registerNavigation(false)` nos plugins** — descartada, e por escrito já em ADR-05 da
   ancestral: esconde o menu e **não** mexe em `canAccess()`. A rota continua respondendo.
3. **Excluir as classes de `config('filament-shield.pages.exclude')`** para o checkbox parar de
   mentir — proibida por RQ-04: tiraria a alavanca do banco.

### Consequências

- **Positivas**: uma linha por tela, dentro de um bloco que já existe; o item de menu some junto
  com o acesso; a autorização é reavaliada em todo request Livewire porque é o `canAccess()` do
  Filament que decide; nenhum registro de plugin muda, então o `LogicException` da rule não é
  tocado.
- **Negativas**: quatro pontos de configuração em vez de um. Mitigado por: os quatro ficam no
  mesmo arquivo, em quatro linhas, e cada uma cita o `file:line` do método do plugin.
- **Riscos**: upgrade de pacote que remova o setter. Mitigado pelo CT de inventário (CT-30), que
  fica vermelho quando uma tela de pacote registrada no `/infra` não está nem na lista de fechadas
  nem na de declaradas.

### Referências

- `vendor/filament/filament/src/Pages/Concerns/CanAuthorizeAccess.php:7-24`
- `vendor/filament/filament/src/Pages/Page.php:133-135`
- `.ai/rules/providers-filament.md`
- `.ai/rules/filament.md` §"Page, Widget e Action novos nascem com a permissão consultada"

---

## ADR-02: Um helper de 6 linhas em `App\Support\PermissaoDaTela`, e não um método em `Paineis`

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

Os quatro callbacks de ADR-01 precisam responder "o usuário corrente tem a permissão desta Page?".
`App\Support\Paineis` é a classe do kit que já pergunta ao Shield em vez de montar o nome da
permissão, e `Paineis::permissoesDe($painel, [$fqcn])` devolve exatamente as chaves de uma classe
num painel. A escada do Ponytail manda reusar.

### Decisão

Classe nova `App\Support\PermissaoDaTela`, com **um** método estático que replica a semântica de
`HasPageShield::canAccess()` (`vendor/bezhansalleh/filament-shield/src/Traits/HasPageShield.php:19-27`)
consultando **só o painel corrente**.

### Alternativas Consideradas

1. **`Paineis::permissoesDe()`** — descartada por efeito colateral em laço de render.
   `Paineis::mapa()` percorre os **três** painéis e, a cada volta, faz
   `app()->forgetInstance('filament-shield')`, `Facade::clearResolvedInstance('filament-shield')` e
   `Filament::setCurrentPanel($painel)` (`app/Support/Paineis.php:129-154`). O mapa é memoizado no
   container, então o custo é pago uma vez — mas ele é pago **de dentro de `canAccess()`**, que
   roda durante a montagem da navegação, e a primeira chamada trocaria o painel corrente e
   descartaria a instância do Shield no meio do render. O `finally` restaura o painel; a instância
   do Shield descartada não é restaurada para quem já a tinha em mão.
   O docblock de `Paineis` (`:31-42`) documenta que essa troca de instância já produziu "6/1/6 nos
   três painéis" quando feita fora de ordem. Não é lugar de chamar em laço de render.
2. **Método novo dentro de um dos concerns** — impossível: trait não se chama estaticamente sem uma
   classe que a use, e as classes aqui são de pacote.
3. **`fn () => auth()->user()?->can('View:LogsExplorer')`, string literal** — descartada, e é a
   alternativa mais tentadora porque é a mais curta. Ela reimplementa quatro chaves de
   `config/filament-shield.php` (`permissions.case`, `permissions.separator`, `pages.prefix`,
   `widgets.prefix`) por cópia, em quatro arquivos, e o docblock de `Paineis.php:24-29` já registra
   que "dessincronizam em silêncio". O sintoma seria o pior possível: a chave literal não casa com
   nenhuma permission, o helper cai no fail-open e a tela **abre para todos** com o diff parecendo
   correto.
4. **Cachear a chave numa propriedade estática**, como a trait do vendor faz — descartada: a trait
   pode, porque `static::$pagePermissionKey` é por classe; aqui o parâmetro varia e um cache por
   classe daria a chave da primeira Page a todas as seguintes. É o mesmo defeito que CT-23 da wiki
   ancestral existe para pegar. E é desnecessário: `FilamentShield::getPages()` já é `once()`
   (`FilamentShield.php:71-74`).

### Consequências

- **Positivas**: seis linhas de corpo, sem estado, sem efeito colateral; a resolução da chave
  continua sendo do Shield, então upgrade dele é herdado.
- **Negativas**: uma classe de `Support` a mais, com um método. Aceito: o alternativo era
  duplicar a resolução em quatro call sites.
- **Riscos**: fail-open herdado (ver ADR-03).

### Referências

- `vendor/bezhansalleh/filament-shield/src/Traits/HasPageShield.php:19-37`
- `vendor/bezhansalleh/filament-shield/src/FilamentShield.php:71-74`
- `app/Support/Paineis.php:24-42,129-154`

---

## ADR-03: O fail-open do Shield é herdado, não invertido — mesma decisão de ADR-01 da ancestral

**Status**: Aceita
**Data**: 2026-08-24
**Refina**: ADR-01 de `wikis/specs/feat/permissoes-de-telas-e-acoes/permissoes-de-telas-e-acoes/`

### Contexto

`HasPageShield::canAccess()` cai em `parent::canAccess()` — que é `true` — quando a chave não
resolve **ou** quando não há usuário. `PermissaoDaTela::permite()` copia isso. A pergunta é se
copiar é certo, já que aqui o consumidor é um callback de plugin e não uma Page.

Um detalhe novo pesa a favor de copiar: nas quatro telas de ADR-01 o resultado do helper entra em
`&&` com uma condição que **já** exige usuário (`can('ver-logs')`, `auth()->check()`) ou é
consumido por um `canAccess()` que só é alcançado depois do middleware `Authenticate` do painel.
Fail-open sem usuário, portanto, não abre nada em request real.

### Decisão

Copiar a semântica do vendor, incluindo o fail-open. Registrar a razão no docblock do helper.

### Alternativas Consideradas

1. **Fail-closed (`return false` quando a chave não resolve)** — descartada. A chave não resolve
   quando `FilamentShield::getPages()` não contém a classe, e isso acontece quando o painel
   corrente não é o da Page — em teste de componente que não chama `noPainelDoShield()`. Inverter
   trancaria as telas em teste e produziria vermelho que não é defeito. Mais grave: duas semânticas
   diferentes no kit para a mesma pergunta (a trait falha aberta, o helper falharia fechado) é a
   inconsistência que ninguém lembra na hora de depurar.
2. **Fail-closed só quando existe usuário** — descartada: é a mesma coisa escrita mais difícil, e o
   ramo "usuário existe e a chave não resolve" só acontece no painel errado.

### Consequências

- **Positivas**: uma semântica só no kit; `tests/Pest.php` já tem `noPainelDoShield()` para o
  arranjo correto.
- **Negativas**: a leitura do helper exige saber que `true` significa "a permissão não opinou", não
  "está liberado". Escrito no docblock.
- **Riscos**: nenhum novo em relação ao já aceito na ancestral.

### Referências

- ADR-01 de `wikis/specs/feat/permissoes-de-telas-e-acoes/permissoes-de-telas-e-acoes/`
- `tests/Pest.php:649-656` (`noPainelDoShield()`)

---

## ADR-04: Subclasse com o MESMO `class_basename`, e o plugin do backup monitor sai do painel

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

Três classes não têm callback: `BackupRunsPage` e `MyProfilePage` não declaram `canAccess()` (caem
no `true` do Filament), e `ComposerReleaseOverviewWidget::canView()` é `auth()->check()`
(`vendor/mominalzaraa/filament-composer-release-notifier/src/Filament/Widgets/ComposerReleaseOverviewWidget.php:18-21`).

ADR-05 da ancestral descartou subclassear com esta frase: *"o Shield geraria permissão para o nome
da subclasse (`View:LogsExplorerDoKit`), quebrando o checkbox que já existe"*. Isso é verdade **se
a subclasse tiver outro nome**. A chave sai de
`FilamentShield::getDefaultPermissionKeys($page, $prefix)` →
`buildPermissionKey($entity, $affix, $subject)` (`FilamentShield.php:91-112`), e o subject vem do
nome da classe — não do namespace. Uma subclasse chamada `BackupRunsPage` em outro namespace
produz **a mesma chave**.

O que sobra é o conflito de registro: se o plugin registra a classe do pacote e o painel registra a
subclasse, existem duas Pages com o **mesmo slug**.

### Decisão

- Subclasse no kit com **o nome idêntico** ao da classe de pacote, alias no `use` para poder dar
  `extends`.
- `BackupRunsPage`: remover `FilamentBackupMonitorPlugin::make()` do painel e registrar
  `->livewireComponents([LatestBackupsWidget::class])` na mão. A subclasse entra por
  `discoverPages()`, que já varre `app/Filament/Infra/Pages`.
- `MyProfilePage`: **não** remover o plugin — o Breezy publica
  `->customMyProfilePage(string $class)` (`src/Concerns/Plugin/HasMyProfile.php:30-38`), lido em
  `getMyProfilePageClass()` (`:151-154`) tanto no registro da Page (`BreezyCore.php:70`) quanto na
  URL do item de menu do usuário (`:115,120`). A subclasse fica em `app/Filament/Pages/`, fora de
  qualquer `discoverPages()`, e é registrada uma vez por painel.
- `ComposerReleaseOverviewWidget`: `->widget(enabled: false)` no plugin
  (`FilamentComposerReleaseNotifierPlugin.php:33-37`) e a subclasse entra por
  `discoverWidgets()`.

Remover o `FilamentBackupMonitorPlugin` é seguro, e a razão é medida, não presumida: **nada** no
pacote resolve `filament('filament-backup-monitor')` — o `getId()` só é usado pelo próprio plugin,
e `BackupRunsPage` e `LatestBackupsWidget` não chamam `plugin()`/`getPlugin()` em nenhum ponto
(`grep -rn "filament-backup-monitor'" vendor/brimham/` devolve apenas o `getId()` e os dois
`loadViewsFrom`/`loadTranslationsFrom` do ServiceProvider). É o oposto do caso de
`.ai/rules/providers-filament.md`, cujo sintoma é justamente a classe **resolver** o plugin.

### Alternativas Consideradas

1. **Subclasse com nome diferente** (`BackupRunsDoKit`) — descartada: troca a chave da permissão,
   deixa `View:BackupRunsPage` órfã no banco e viola RQ-04 e RQ-06.
2. **Manter o plugin e registrar a subclasse também** — descartada: duas Pages com slug
   `backup-runs`, e a segunda rota registrada vence em silêncio. Qual vence depende da ordem de
   registro, que é o pior tipo de dependência.
3. **`$slug` diferente na subclasse** — descartada: mudaria a URL `/infra/backup-runs`, quebraria
   `tests/Kit/InventarioDeTelasTest.php` e deixaria a rota do pacote **ainda** sem barreira.
4. **Middleware só para estas três** — descartada pelos dois motivos de ADR-01.
5. **`MyProfilePage` em `app/Filament/Infra/Pages/`** — descartada: seria descoberta pelo
   `discoverPages()` do `/infra` **além** do registro do Breezy nos outros dois painéis, e o
   mesmo basename apareceria duas vezes no painel `infra`.

### Consequências

- **Positivas**: nenhuma chave de permissão muda; as três subclasses são 4-8 linhas cada e
  reutilizam os concerns que já existem; `.ai/rules/filament.md` §"Page, Widget e Action novos
  nascem com a permissão consultada" passa a valer literalmente para elas, e os casos de
  inventário do kit (CT-21, CT-23 e os equivalentes de Widget) as adotam de graça porque caem no
  filtro de namespace `App\Filament\`.
- **Negativas**: duas classes do kit têm nome igual a duas de pacote, e o `use ... as ...` é
  obrigatório em todo arquivo que importe as duas. Documentado no docblock de cada subclasse, com
  a frase "o nome é obrigatório: é ele que produz a chave da permissão".
  E `->livewireComponents([LatestBackupsWidget::class])` passa a ser responsabilidade do kit — se
  um upgrade do pacote acrescentar outro componente isolado, o kit tem de acompanhar. O comentário
  do próprio pacote (`FilamentBackupMonitorPlugin.php:22-25`) foi transcrito para o provider.
- **Riscos**: upgrade do backup monitor que passe a resolver o plugin. Mitigado: sem o plugin
  registrado, o sintoma é o `LogicException` alto e imediato da rule — não silencioso.

### Referências

- `vendor/bezhansalleh/filament-shield/src/FilamentShield.php:91-112`
- `vendor/brimham/filament-backup-monitor/src/FilamentBackupMonitorPlugin.php:17-27`
- `vendor/jeffgreco13/filament-breezy/src/Concerns/Plugin/HasMyProfile.php:30-38,151-154`
- `vendor/jeffgreco13/filament-breezy/src/BreezyCore.php:69-70,110-120`
- `vendor/mominalzaraa/filament-composer-release-notifier/src/FilamentComposerReleaseNotifierPlugin.php:33-37,59-70`

---

## ADR-05: `Commands`, `History` e `RunView` ficam declaradas — um callback não distingue três telas

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

As três Pages da Central de comandos têm três permissões — `View:Commands`, `View:History`,
`View:RunView` — e **um** ponto de decisão:

```php
// Pages/Commands.php:137-140, Pages/History.php:67-70, Pages/RunView.php:72-75
public static function canAccess(): bool
{
    return CommandCenterPlugin::forCurrentPanel()?->canAccess() ?? true;
}
```

`CommandCenterPlugin::canAccess()` (`:129-142`) invoca `($this->authorizeUsing)(Auth::user())` — o
único argumento é o usuário. Nada no callback identifica **qual** das três Pages está perguntando.
E a lista de Pages é fixa no `register()` (`:176`, `$pages = [Commands::class, History::class,
RunView::class]`), sem setter — subclassear exigiria não registrar o plugin, que também registra o
`CommandCenterResource`, o cluster e os rótulos de navegação.

### Decisão

Deixar as três **declaradas**, com o motivo e o `file:line` no comentário do plugin no
`InfraPanelProvider`, e manter um caso de teste que assere a lacuna — o substituto honesto do
antigo CT-24.

A barreira efetiva delas continua sendo `config('command-center.enabled')` +
`Gate::allows('command-center:access')`. Está escrito que isso **não é permissão por tela**:
`command-center:access` é definido como `temPapelDoPainel('infra')`
(`app/Providers/KitServiceProvider.php:173`), logo é equivalente a `canAccessPanel()` para este
painel.

### Alternativas Consideradas

1. **`View:Commands` no callback, valendo pelas três** — descartada: revogar `View:Commands`
   fecharia `History` e `RunView` também, e os checkboxes delas continuariam inertes. Troca um
   checkbox que mente por três que mentem de forma **cruzada**, que é pior de depurar.
2. **União (`can('View:Commands') || can('View:History') || can('View:RunView')`)** — descartada:
   quem tem uma entra nas três.
3. **Descobrir a Page pela rota corrente dentro do callback** — descartada: funcionaria para o
   `GET` e daria a resposta errada na montagem da navegação, onde as três perguntam no mesmo
   request e nenhuma é a rota corrente. Determinismo dependendo do contexto de chamada é o defeito
   que ninguém reproduz.
4. **Não registrar o plugin e registrar três subclasses** — descartada: perde o
   `CommandCenterResource`, o cluster e os rótulos, e é o cenário exato que
   `.ai/rules/providers-filament.md` diz para não arriscar.
5. **Pedir o setter ao upstream** — fora de escopo desta entrega, e não bloqueia nada: quando
   existir, o CT-30 de inventário é o lugar que aponta.

### Consequências

- **Positivas**: escopo honesto; o custo está escrito com `file:line`; o caso de teste que assere
  a lacuna continua existindo, então o dia em que o pacote publicar o setter alguém fica vermelho.
- **Negativas**: RQ-01 fica atendido em **7 de 10**, e isso está marcado como tal na cobertura.
  Três checkboxes seguem inertes.
- **Riscos**: alguém lê o checkbox `View:History` e conclui que a tela está protegida por ele.
  Mitigado por: o comentário no provider, este ADR, o `README` (RQ-07) e o CT que assere a lacuna
  nomeando as três classes.

### Referências

- `vendor/ssbityukov/filament-command-center/src/Filament/CommandCenterPlugin.php:81-85,129-142,176`
- `vendor/ssbityukov/filament-command-center/src/Filament/Pages/Commands.php:137-140`
- `vendor/ssbityukov/filament-command-center/src/Filament/Pages/History.php:67-70`
- `vendor/ssbityukov/filament-command-center/src/Filament/Pages/RunView.php:72-75`
- `app/Providers/KitServiceProvider.php:171-174`

---

## ADR-06: Nenhum log novo, e o número que justifica

**Status**: Aceita
**Data**: 2026-08-24
**Refina**: ADR-07 de `wikis/specs/feat/permissoes-de-telas-e-acoes/permissoes-de-telas-e-acoes/`

### Contexto

A `feature-wiki` exige log em toda etapa de execução. Esta feature não acrescenta execução:
acrescenta predicado em métodos consultados em laço de render.

### Decisão

Nenhum channel novo, nenhuma linha de log nova.

### Alternativas Consideradas

1. **`Log::channel('permissoes')->debug()` em cada negativa** — descartada. `canAccess()` é
   consultado uma vez por item de navegação, uma vez por cartão de hub e uma vez por categoria do
   Spotlight. Só as telas de pacote do `/infra` são 9; com as do kit e os Resources, um
   carregamento do `/infra` produziria dezenas de linhas dizendo "alguém tem permissão" — ruído que
   **apaga** a trilha útil, e a trilha útil deste kit é o channel `autenticacao`.
2. **Logar só a negativa** — descartada: negativa é o caso normal de quem não tem o papel, e o
   403 já está no log de acesso do servidor com URL, usuário e status.

### Consequências

- **Positivas**: zero ruído; nada a limpar depois.
- **Negativas**: não há trilha de aplicação para "quem tentou abrir a tela X e levou 403". Aceito:
  é o log do servidor, e o kit tem a trilha de acesso do `filament-authentication-log` para
  autenticação.

### Referências

- ADR-07 de `wikis/specs/feat/permissoes-de-telas-e-acoes/permissoes-de-telas-e-acoes/`

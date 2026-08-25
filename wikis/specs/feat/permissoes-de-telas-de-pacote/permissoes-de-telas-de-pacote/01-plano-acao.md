# Plano de Ação — W6: permissões das telas de pacote no painel /infra

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: evolução
- **Wiki ancestral**: `wikis/specs/feat/permissoes-de-telas-e-acoes/permissoes-de-telas-e-acoes/`
- **Motivo**: ADR-05 daquela wiki declarou estas 10 classes **fora de escopo**, com o custo
  escrito. O usuário pediu para fechar. Esta wiki **revisa ADR-05** — não a contradiz por
  omissão: ela mostra que a alternativa que ADR-05 avaliou (subclassear cada Page e perder a
  chave da permissão) não era a única, e que 7 das 10 têm ponto de extensão publicado pelo
  próprio pacote.
- **Toca infra compartilhada?**: **sim** — `app/Providers/Filament/InfraPanelProvider.php`,
  `AdminPanelProvider.php` e `AppPanelProvider.php` (os três registram o `BreezyCore`). A
  matriz de permissões **não** muda (ver "Invariante da matriz" abaixo), mas a regressão é
  obrigatória contra os CT/CT-B da wiki ancestral e contra `tests/Kit/PaginasInfraTest.php`.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | a permissão passa a decidir | 2, 3, 4, 5 | 7 de 10 classes; 3 declaradas em RQ-05 |
| RQ-02 | quem tem, entra | 6 (CT em par) | — |
| RQ-03 | quem não tem, 403 | 6 (CT em par) | a repro do requisito inverte |
| RQ-04 | permissão continua no banco e no checkbox | 1 (nenhuma chave muda) | invariante conferido por CT |
| RQ-05 | o inviável fica declarado com `file:line` | 5 | `Commands`, `History`, `RunView` |
| RQ-06 | nenhuma permissão órfã | 1 + verificação por `database-query` | nenhuma chave NOVA nasce |
| RQ-07 | README pt e en refletem o presente | 7 | — |
| RQ-08 | CT-24 atualizado, não apagado | 6 | inversão explicada no `03-progresso.md` |

## Objetivo

Fazer a permissão `View:{Classe}` **decidir** o acesso às telas que o painel `/infra` recebe de
pacotes de terceiros, sem trocar nenhuma chave de permissão, sem acrescentar dependência e sem
mexer no registro dos plugins que resolvem o painel corrente — o `LogicException` que
`.ai/rules/providers-filament.md` documenta.

O caminho é: **usar o ponto de extensão que cada pacote já publica** (um callback de autorização
no plugin) onde ele existe, e **subclassear a classe do pacote mantendo o mesmo `class_basename`**
onde não existe. O basename idêntico é o que preserva a chave de permissão — e era exatamente a
objeção nº 1 de ADR-05 da wiki ancestral.

## Contexto

### A medição, tela por tela — barreira HOJE e o que falta

Cada linha foi lida no `vendor/`, com `file:line`. As quatro "barreiras por gate" que o enunciado
menciona **não discriminam papel**: `ver-logs`, `command-center:access`, `viewPulse` e
`ver-ai-tasks` são definidos como `temPapelDoPainel('infra')` em
`app/Providers/KitServiceProvider.php:171-174` — ou seja, são equivalentes a `canAccessPanel()`
para este painel, e não a uma permissão de tela.

| # | Classe | Barreira HOJE | `file:line` | Ponto de extensão | Ação |
|---|--------|---------------|-------------|-------------------|------|
| 1 | `HealthCheckResults` | `plugin->isAuthorized()`, e o default do plugin é `true` — o kit nunca chamou `->authorize()` | `vendor/shuvroroy/filament-spatie-laravel-health/src/Pages/HealthCheckResults.php:86-89` + `FilamentSpatieLaravelHealthPlugin.php:16,41-51` | `->authorize(Closure)` | fecha (passo 2) |
| 2 | `BackupRunsPage` | nenhuma — sem `canAccess()`, cai no `true` do Filament | `vendor/brimham/filament-backup-monitor/src/Pages/BackupRunsPage.php:16` (a classe não declara `canAccess`) | nenhum no plugin (`FilamentBackupMonitorPlugin.php:17-27` só registra) | fecha por subclasse (passo 3) |
| 3 | `LogsExplorer` | `can('ver-logs')` = "tem papel do painel infra" | `vendor/laboiteacode/filament-logs-explorer/src/Pages/LogsExplorer.php:107-110`; gate em `KitServiceProvider.php:172` | `->canAccessUsing(Closure)` (`FilamentLogsExplorerPlugin.php:250-255`) | fecha (passo 2) |
| 4 | `DependencyGraphPage` | `plugin->isVisible()` = `config enabled && auth()->check()` | `vendor/laboiteacode/filament-dependency-graph/src/Filament/Pages/DependencyGraphPage.php:107-110`; `DependencyGraphPlugin.php:122-125,425-435`; kit em `InfraPanelProvider.php:236` | `->canAccessUsing(Closure)` | fecha (passo 2) |
| 5 | `Commands` | `plugin->canAccess()` = kill-switch && `can('command-center:access')` = "tem papel do painel infra" | `vendor/ssbityukov/filament-command-center/src/Filament/Pages/Commands.php:137-140`; `CommandCenterPlugin.php:81-85,129-142` | **um** callback para **três** Pages | **declarada** (passo 5) |
| 6 | `History` | idem | `.../Pages/History.php:67-70` | idem | **declarada** (passo 5) |
| 7 | `RunView` | idem | `.../Pages/RunView.php:72-75` | idem | **declarada** (passo 5) |
| 8 | `RecycleBin` | `plugin->isAuthorized()`, default `true` — o kit nunca chamou `->authorize()` | `vendor/promethys/revive/src/Pages/RecycleBin.php:66-69` + `RevivePlugin.php:16,155-168` | `->authorize(Closure)` | fecha (passo 2) |
| 9 | `MyProfilePage` | nenhuma — sem `canAccess()` | `vendor/jeffgreco13/filament-breezy/src/Pages/MyProfilePage.php:10` (a classe não declara `canAccess`) | `->customMyProfilePage(string $class)` (`src/Concerns/Plugin/HasMyProfile.php:30-38,151-154`) | fecha por subclasse (passo 4) |
| 10 | `ComposerReleaseOverviewWidget` | `canView()` = `auth()->check()` | `vendor/mominalzaraa/filament-composer-release-notifier/src/Filament/Widgets/ComposerReleaseOverviewWidget.php:18-21` | `->widget(enabled: false)` + registro próprio (`FilamentComposerReleaseNotifierPlugin.php:33-37,66`) | fecha por subclasse (passo 4) |

**Resultado**: 7 fechadas, 3 declaradas. As três declaradas não ficam sem barreira — continuam
atrás do kill-switch de `config('command-center.enabled')` e do gate
`command-center:access`; o que fica inerte é o checkbox `View:Commands` / `View:History` /
`View:RunView`.

### Invariante da matriz (RQ-04 e RQ-06)

**Nenhuma chave de permissão nova nasce, e nenhuma morre.** As duas subclasses de Page e a
subclasse de Widget têm o **mesmo `class_basename`** da classe de pacote que substituem, e o
Shield constrói a chave a partir do subject resolvido do nome da classe
(`FilamentShield::getDefaultPermissionKeys()`, `vendor/bezhansalleh/filament-shield/src/FilamentShield.php:91-112`,
ramo `is_array($affixes)` falso para Page/Widget). Logo `View:BackupRunsPage`,
`View:MyProfilePage` e `View:ComposerReleaseOverviewWidget` continuam sendo as mesmas chaves.

Baseline medido neste worktree, **antes** da implementação
(`php artisan db:seed` dos dois seeders, depois contagem):

| Papel | `roles.painel` | permissions |
|---|---|---|
| `master_global` | nulo | 0 (entra pelo `Gate::before`) |
| `admin` | `admin` | 126 |
| `infra` | `infra` | 140 |
| `admin_app` | `app` | 47 |
| `panel_user` | `app` | 17 |
| **total na tabela `permissions`** | — | **269** |

Os cinco números têm de ficar **idênticos** depois. Diferença é sinal de chave renomeada, e chave
renomeada é permissão órfã — o defeito que RQ-06 proíbe.

As 10 permissões e quem as tem hoje (confirmado por consulta, não por leitura de seeder):

```
View:HealthCheckResults            infra
View:BackupRunsPage                infra
View:LogsExplorer                  infra
View:DependencyGraphPage           infra
View:Commands                      infra
View:History                       infra
View:RunView                       infra
View:RecycleBin                    infra
View:MyProfilePage                 admin, infra, admin_app, panel_user
View:ComposerReleaseOverviewWidget infra
```

## Análise dos Arquivos Existentes

### `app/Filament/Concerns/ExigePermissaoDaTela.php` / `ExigePermissaoDoWidget.php`

Os dois concerns do kit já resolvem o problema **para classe que o kit escreve**: dão alias ao
método da trait do Shield e publicam `permissão && regra local`. As subclasses dos passos 3 e 4
reusam os dois sem uma linha nova de lógica.

Precedência de PHP a favor: **método vindo de trait na subclasse vence método herdado da classe
pai**. É o que faz o `use ExigePermissaoDoWidget;` na subclasse sobrescrever o
`canView()` do widget do pacote — e o `parent::canView()` de dentro da trait do Shield continua
caindo no `auth()->check()` do pacote, que é o comportamento de fail-open já herdado por ADR-01
da wiki ancestral.

### `app/Providers/Filament/InfraPanelProvider.php`

Registra os plugins. Os quatro callbacks do passo 2 são **uma linha cada** dentro do bloco
`->plugins([...])` que já existe. Duas remoções: `FilamentBackupMonitorPlugin::make()` (passo 3) e
`->widget(enabled: true)` vira `false` (passo 4).

### `app/Support/Paineis.php`

Já pergunta ao Shield em vez de montar o nome da permissão — é o precedente para o passo 1. **Não
serve** como sede do novo helper: `Paineis::mapa()` percorre os TRÊS painéis chamando
`Filament::setCurrentPanel()` e `forgetInstance('filament-shield')` a cada volta (`:129-154`), e
`canAccess()` roda em laço de render. Ver ADR-02.

### `database/seeders/PapeisSeeder.php`

**Não muda.** As 10 permissões já estão na matriz do painel `infra` (e `View:MyProfilePage` nos
quatro papéis), porque o Shield descobre por `$panel->getPages()` cru e as classes de pacote estão
lá. Trocar a classe registrada por uma subclasse de mesmo basename não muda a chave, logo não
muda a matriz.

## Autorização

- **Policies**: nenhuma nova.
- **Gates**: nenhum novo. `ver-logs` e `command-center:access` permanecem exatamente como estão.
- **Middleware**: nenhum. A decisão fica em `canAccess()`/`canView()`, que o Filament reavalia em
  **todo** request Livewire (`Pages\Concerns\CanAuthorizeAccess::hydrateCanAuthorizeAccess()`,
  `vendor/filament/filament/src/Pages/Concerns/CanAuthorizeAccess.php:12-15`) — um middleware de
  rota só cobriria o `GET` inicial. Ver ADR-01.
- **Guards**: inalterados.

## Rotas

Nenhuma rota nova, nenhuma rota renomeada. As três subclasses herdam slug da classe de pacote:

| Método | URI | Origem do slug |
|--------|-----|----------------|
| GET | `/infra/backup-runs` | `BackupRunsPage::$slug = 'backup-runs'` (herdado) |
| GET | `/{painel}/meu-perfil` | `MyProfilePage::getSlug()` → `filament('filament-breezy')->slug()` (herdado) |

`tests/Kit/InventarioDeTelasTest.php` compara as rotas registradas com `telasDoKit()` nos dois
sentidos e é o enforço de que nenhuma URL mudou.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `HealthCheckResults` | Filament (pacote) | `/infra/health` | abre a tela | Não |
| `BackupRunsPage` (subclasse do kit) | Filament | `/infra/backup-runs` | abre a tela | Não |
| `LogsExplorer` | Filament (pacote) | `/infra/logs` | abre a tela | Não |
| `DependencyGraphPage` | Filament (pacote) | `/infra/dependency-graph` | abre a tela | Sim (grafo) |
| `RecycleBin` | Filament (pacote) | `/infra/recycle-bin` | abre a tela | Não |
| `MyProfilePage` (subclasse do kit) | Filament | `/infra/meu-perfil`, `/admin/meu-perfil`, `/app/meu-perfil` | abre a tela | Não |
| `ComposerReleaseOverviewWidget` (subclasse do kit) | Filament Widget | `/infra` (dashboard) | vê ou não vê o cartão | Não |
| barra lateral do `/infra` | Filament | `/infra` | item de menu presente/ausente | Não |

**Gate de CT-B**: nada aqui afirma sobre algo que **só o navegador prova**. Presença de item de
menu, 403 de rota e visibilidade de widget são teste de request e de componente Livewire — e o
kit já tem `tests/Browser/PermissoesDoDashboardTest.php` cobrindo a superfície de dashboard com
navegador. O CT-B desta entrega existe por **um** motivo: o `/infra` renderiza as telas de
pacote, e um `canAccess()` novo que estoure derruba a tela inteira com um erro que o teste de
request não vê (erro de JS/console em tela de terceiro). Um único cenário de smoke cobre isso.

**Gate de tela de escrita**: nenhuma rota `create`/`edit` nova.

## Variáveis de Ambiente

Nenhuma nova.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **`tests/Kit/PermissoesDeTelasTest.php` CT-21 e CT-23**: as duas novas Pages de subclasse caem
  no filtro `App\Filament\` de `paginasDePainelDoKit()` e passam a ser exigidas pelos dois casos.
  Ambas têm o concern e ambas negam — os casos ficam verdes cobrindo mais.
- **`tests/Kit/PermissoesDeWidgetsTest.php`**: idem para o novo widget em
  `App\Filament\Infra\Widgets`.
- **`tests/Kit/PermissoesDeTelasTest.php` CT-24**: fica **vermelho** e é atualizado (RQ-08).
- **`tests/Kit/PaginasInfraTest.php`**: visita as telas do `/infra` com o papel `infra`, que tem
  todas as 10 permissões — deve seguir verde. Se ficar vermelho, é sinal de over-block.
- **`tests/Kit/InventarioDeTelasTest.php`**: compara rotas × inventário. Deve seguir verde
  (slugs preservados).
- **`tests/Browser/TelasDoKitTest.php`** e `PermissoesDoDashboardTest.php`: smoke do `/infra`.
- **Backup monitor sem plugin**: `LatestBackupsWidget` é o header widget da página e precisa de
  registro em `livewireComponents()`, senão o commit por nome do Livewire responde 419 (o próprio
  comentário do pacote diz isso, `FilamentBackupMonitorPlugin.php:22-25`). O passo 3 carrega essa
  linha.

## Rollback

- Sem migration, sem mudança de dado, sem mudança de chave de permissão. `git revert` do commit
  devolve o estado anterior inteiro.
- Kill-switch de emergência por tela: dar a permissão a todo mundo (`/admin/shield/roles`) tem o
  mesmo efeito prático de antes da entrega.

## Dependências

Nenhuma nova. Proibido acrescentar.

## Riscos

- **Trancar o papel `infra` fora de uma tela de observabilidade.** Mitigação: as 10 permissões já
  estão no papel `infra` (conferido por consulta, acima), e o CT é sempre **em par** — quem tem
  entra, quem não tem toma 403.
- **Remover `FilamentBackupMonitorPlugin` derrubar algo.** Mitigação: `grep` confirmou que nada
  no pacote resolve `filament('filament-backup-monitor')` — o `getId()` só é usado pelo próprio
  plugin. Views e traduções vêm do ServiceProvider, que continua carregado. É o oposto do caso
  que `.ai/rules/providers-filament.md` descreve (`lockscreen`, `filament-exceptions`), e o
  motivo está escrito no ADR-04.
- **Fail-open herdado do Shield** quando a chave não resolve. Já analisado em ADR-01 da wiki
  ancestral e mantido; o helper do passo 1 replica a mesma semântica em vez de inventar outra.
- **`master_global` não prova nada** sobre permissão (passa pelo `Gate::before`,
  `KitServiceProvider.php:157`). Todo CT usa o papel real e revoga a permissão dele.

## Channel de Log da Feature

**Nenhum channel novo e nenhuma linha de log nova.** A justificativa completa, com o número que a
sustenta, está em ADR-06 — em resumo: `canAccess()` roda em laço de render e um `Log::info` ali
produz dezenas de linhas por carregamento de página dizendo "alguém tem permissão".

## Estrutura de Implementação

### 1. O helper que pergunta ao Shield qual é a permissão da tela

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Support/PermissaoDaTela.php` (novo)
- Classe `final`, um método estático:

```php
public static function permite(string $pagina): bool
```

- Corpo: replica **exatamente** a semântica de
  `BezhanSalleh\FilamentShield\Traits\HasPageShield::canAccess()`
  (`vendor/bezhansalleh/filament-shield/src/Traits/HasPageShield.php:19-27`):
  resolve a chave por `FilamentShield::getPages()[$pagina]['permissions']` →
  `array_key_first()`; pega o usuário por `Filament::auth()?->user()`; devolve
  `$usuario->can($chave)` quando os dois existem, e `true` (fail-open herdado) quando não.
- **Sem cache em propriedade estática.** A trait do vendor guarda a chave em
  `static::$pagePermissionKey`, que é por classe; aqui o parâmetro varia, e
  `FilamentShield::getPages()` já é memoizado por `once()` na instância `scoped` do Shield
  (`FilamentShield.php:71-74`).
- Docblock obrigatório: por que não `Paineis` (ADR-02), por que fail-open (ADR-01 da ancestral),
  e por que não montar a string `'View:'.class_basename()` à mão (as quatro chaves de
  `config/filament-shield.php` que dessincronizam — o mesmo argumento do docblock de
  `Paineis.php:24-29`).
- **Logs**: nenhum (ver "Channel de Log da Feature").

### 2. As quatro telas com callback publicado pelo pacote

> Skills: `laravel-best-practices`

- **Path**: `app/Providers/Filament/InfraPanelProvider.php`
- `FilamentSpatieLaravelHealthPlugin` (hoje `:202-203`): acrescentar
  `->authorize(fn (): bool => PermissaoDaTela::permite(HealthCheckResults::class))`
- `FilamentLogsExplorerPlugin` (hoje `:221-225`): o `canAccessUsing` passa a ser
  `fn (): bool => (auth()->user()?->can('ver-logs') ?? false) && PermissaoDaTela::permite(LogsExplorer::class)`
  — o gate **fica**, em `&&`. Não é redundância decorativa: o gate é a barreira do painel e a
  permissão é a da tela, e é a mesma coexistência que ADR-06 da wiki ancestral decidiu para a
  flag `kit.hub`.
- `DependencyGraphPlugin` (hoje `:233-236`): `canAccessUsing` passa a ser
  `fn (): bool => auth()->check() && PermissaoDaTela::permite(DependencyGraphPage::class)`.
  O `auth()->check()` fica porque o callback **substitui** a regra local-only do pacote (o
  comentário existente no arquivo explica) e o helper falha aberto sem usuário.
- `RevivePlugin` (hoje `:387-394`): acrescentar
  `->authorize(fn (): bool => PermissaoDaTela::permite(RecycleBin::class))`
- Comentário curto em cada um: **"a permissão da tela, pelo callback que o pacote publica"** +
  o `file:line` do método do plugin. Sem isso a linha parece enfeite na próxima leitura.
- **Logs**: nenhum.

### 3. `BackupRunsPage` — subclasse do kit, plugin fora

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Filament/Infra/Pages/BackupRunsPage.php` (novo)

```php
namespace App\Filament\Infra\Pages;

use App\Filament\Concerns\ExigePermissaoDaTela;
use Brimham\FilamentBackupMonitor\Pages\BackupRunsPage as BackupRunsDoPacote;

class BackupRunsPage extends BackupRunsDoPacote
{
    use ExigePermissaoDaTela;
}
```

- **O nome da classe é `BackupRunsPage`, igual ao do pacote, e isso é obrigatório**: é o
  `class_basename` que produz a chave `View:BackupRunsPage`. Renomear troca a chave e cria
  permissão órfã (RQ-06). O `as BackupRunsDoPacote` no import é o que torna o `extends` possível.
- Descoberta automática: `InfraPanelProvider:103` já faz
  `discoverPages(in: app_path('Filament/Infra/Pages'))`.
- **Path**: `app/Providers/Filament/InfraPanelProvider.php`
  - remover `FilamentBackupMonitorPlugin::make()` de `->plugins([])` (hoje `:206`)
  - acrescentar `->livewireComponents([LatestBackupsWidget::class])` ao painel, com o comentário
    do pacote transcrito (o header widget é isolado e faz commit por nome; sem o registro o
    follow-up responde 419)
  - remover o import de `FilamentBackupMonitorPlugin`
- **Logs**: nenhum.

### 4. `MyProfilePage` e `ComposerReleaseOverviewWidget` — subclasses do kit

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Filament/Pages/MyProfilePage.php` (novo)
  - `namespace App\Filament\Pages;`, `extends Jeffgreco13\FilamentBreezy\Pages\MyProfilePage as MeuPerfilDoPacote`,
    `use ExigePermissaoDaTela;`
  - Fica em `app/Filament/Pages/` (e **não** em `Infra/Pages/`) porque a tela é registrada nos
    três painéis. Esse diretório **não** é varrido por nenhum `discoverPages()` — os três
    providers apontam para `Filament/{Admin,App,Infra}/Pages` —, então a classe só entra no painel
    pelo registro explícito do Breezy, uma vez por painel. `app/Filament/Pages/Auth/` é o
    precedente do mesmo arranjo.
- **Path**: `AdminPanelProvider.php:182`, `AppPanelProvider.php:289`, `InfraPanelProvider.php:187`
  - acrescentar `->customMyProfilePage(MyProfilePage::class)` ao `BreezyCore::make()` de cada um
    (`vendor/jeffgreco13/filament-breezy/src/Concerns/Plugin/HasMyProfile.php:30-38`)
- **Path**: `app/Filament/Infra/Widgets/ComposerReleaseOverviewWidget.php` (novo)
  - `extends MominAlZaraa\FilamentComposerReleaseNotifier\Filament\Widgets\ComposerReleaseOverviewWidget as ComposerReleasesDoPacote`,
    `use ExigePermissaoDoWidget;`
  - `fonteDeDadosDisponivel()` com
    `rescue(fn (): bool => Schema::hasTable('composer_release_package_snapshots'), false)` — a
    tabela é de plugin, e `.ai/rules/filament.md` §"Qual pacote de widget" manda o guarda; o
    `canView()` do pacote não tinha nenhum, e widget que estoura derruba o dashboard inteiro.
    Confirmar o nome da tabela pelo model do pacote antes de escrever.
- **Path**: `app/Providers/Filament/InfraPanelProvider.php:239-242`
  - `FilamentComposerReleaseNotifierPlugin::make()->widget(enabled: true)` → `false`, com
    comentário dizendo que o widget do kit substitui, mesmo nome e mesma permissão
- **Logs**: nenhum.

### 5. As três telas da Central de comandos — declaração

> Skills: nenhuma (é documentação em código)

- **Path**: `app/Providers/Filament/InfraPanelProvider.php`, no comentário do
  `CommandCenterPlugin` (hoje `:249-261`)
- Acrescentar ao comentário existente **por que** `View:Commands`, `View:History` e
  `View:RunView` seguem inertes, com `file:line`:
  - `CommandCenterPlugin::authorize()` recebe **um** `?Closure`
    (`vendor/ssbityukov/filament-command-center/src/Filament/CommandCenterPlugin.php:81-85`) e
    `canAccess()` o invoca sem nenhum argumento que identifique a Page (`:129-142`);
  - as três Pages chamam o **mesmo** `CommandCenterPlugin::forCurrentPanel()?->canAccess()`
    (`Pages/Commands.php:137-140`, `Pages/History.php:67-70`, `Pages/RunView.php:72-75`);
  - a lista de Pages é **fixa** no `register()` do plugin, sem setter
    (`CommandCenterPlugin.php:176`), então subclassear exigiria não registrar o plugin — e ele
    também registra o `CommandRecordResource`, o cluster e os rótulos.
- Consequência escrita: a barreira das três é `config('command-center.enabled')` +
  `command-center:access`, que é papel do painel — **não** é permissão por tela.
- **Logs**: nenhum.

### 6. Testes

> Skills: `pest-testing`, `feature-test-design`

Ver `04-casos-de-teste.md`. O que muda no que já existe:

- `tests/Kit/PermissoesDeTelasTest.php` → **CT-24 invertido** (RQ-08): de "abre com a permissão
  revogada" para "fecha com a permissão revogada", em dataset com as 5 Pages fechadas do `/infra`,
  mantendo a segunda asserção que prova que a permissão continua no banco (RQ-04).
- CT novo com as três Pages da Central de comandos, asserindo a lacuna que **permanece** — o
  substituto honesto do antigo CT-24 (RQ-05).
- CT novo do widget (`tests/Kit/PermissoesDeWidgetsTest.php`).
- CT novo de inventário: nenhuma Page/Widget **de pacote** registrada no `/infra` fica fora da
  lista de "fechada" ou da lista de "declarada". Fica vermelho quando um upgrade de plugin trouxer
  tela nova — que é o enforço automático que `.ai/rules/specs.md` pede.
- CT novo do invariante da matriz: as contagens por papel e o total de `permissions` seguem os
  números do baseline.

### 7. Documentação

> Skills: nenhuma

- `README.md` e `README.en.md`: a frase "o kit entrega 'toda tela DO KIT nasce com permissão', não
  'toda tela do painel'" descreve o passado. Reescrever para o presente, nomeando as três exceções
  restantes.
- `CHANGELOG.md`: entrada nova. **Não** reescrever a seção "Sabido" da v0.18.10 — o passado fica.
- `wikis/specs/feat/permissoes-de-telas-e-acoes/.../02-decisoes-arquiteturais.md`: ADR-05 ganha
  `**Status**: Substituída por ADR-01..ADR-05 de wikis/specs/feat/permissoes-de-telas-de-pacote/`.
  Uma linha; o corpo dela fica, porque o raciocínio continua correto para as três declaradas.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`.** A escada desta feature:
> 1. **Reutilizar**: `ExigePermissaoDaTela` e `ExigePermissaoDoWidget` já existem e fazem tudo —
>    as três subclasses são 4 linhas cada.
> 2. **Feature nativa do pacote antes de código próprio**: o callback publicado pelo plugin vence
>    subclasse, e subclasse vence middleware.
> 3. **Uma linha por tela** no provider.
> 4. Um único arquivo novo de lógica (`PermissaoDaTela`), com 6 linhas de corpo.
>
> O que foi **recusado** por over-engineering, e está em `02-decisoes-arquiteturais.md`: um
> middleware genérico de painel (ADR-01), um método em `Paineis` (ADR-02), reimplementar a
> resolução da chave (ADR-03).

## Testes

> Ver `04-casos-de-teste.md` (backend) e `05-casos-de-teste-browser.md` (o único cenário que
> precisa de navegador).

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse` — 0 erros
- [ ] `php artisan test --testsuite=Kit --compact`
- [ ] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` — base 1016 passando
- [ ] `composer test:browser`
- [ ] `database-query` conferindo as contagens do baseline (126 / 140 / 47 / 17 / 269)

## Commits

- `:sparkles: feat(permissoes): a tela de pacote passa a consultar a permissao dela`
- `:white_check_mark: test(permissoes): CT-24 inverte e a lacuna que sobra fica asserida`
- `:memo: docs(readme): o kit entrega permissao em toda tela do painel, menos tres`
- `:memo: docs(wiki): wiki da feature permissoes-de-telas-de-pacote`

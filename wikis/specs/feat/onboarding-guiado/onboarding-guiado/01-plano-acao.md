# Plano de Ação — Onboarding guiado: jornadas semeadas, interruptor no settings e rule de manutenção

> Requisito: `00-requisito.md` (Texto Original + Adendos 1, 2 e 3)

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: — (o pacote foi instalado sem wiki própria; a autoria no `/admin` já existia)
- **Motivo**: o pacote `wallacemartinss/filament-onboarding` está instalado, mas só a autoria está registrada, e nenhuma jornada existe
- **Toca infra compartilhada?**: **sim** — os três `PanelProvider` (plugin novo no `/app` e no `/infra`, middleware de painel), `PapeisSeeder` (permissão da página de progresso), `ConfiguracoesDoKit` (settings), `KitInstall` (opção nova) e o inventário de telas dos testes. A regressão é obrigatória contra os CT das features que consomem essa infra: `agentes-ia-no-settings`, `dock-de-acoes-rapidas`, `configuracoes-secoes-colapsaveis` e a suíte `tests/Kit` de instalação

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | revisar cada feature quanto à qualificação | 5, 8 | a revisão é a tabela aprovada (RQ-23), e a documentação registra a decisão de cada feature |
| RQ-02 | toda feature qualificada ganha jornada semeada | 5 | uma jornada por painel (RQ-18) |
| RQ-03 | os seeders populam também as condições | 4, 5 | condições gravadas no banco (aparecem em "Condições") e condições em código, descobertas pelo pacote |
| RQ-04 | seeder pai que executa os filhos | 5 | — |
| RQ-05 | um seeder filho por feature | 5 | — |
| RQ-06 | semear é escolha de quem instala | 6 | forma decidida em RQ-19/RQ-20 |
| RQ-07 | documentado como iniciar via seeder | 8 | — |
| RQ-08 | documentado o que cada seeder popula | 8 | — |
| RQ-09 | features de tenant têm seeder correspondente, semeado só com tenancy | 5 | detalhado em RQ-24 |
| RQ-10 | interruptor no settings | 2 | — |
| RQ-11 | interruptor nasce desligado | 1, 2 | — |
| RQ-12 | desligado, não exibe o menu | 3 | detalhado em RQ-15, RQ-16, RQ-17 |
| RQ-13 | regra que alerta o agente | 7 | detalhado em RQ-26, RQ-27 |
| RQ-14 | feature nova ganha seeder filho pela regra | 7 | — |
| RQ-15 | desligado, menus de autoria somem | 3 | — |
| RQ-16 | desligado, autoria responde 404 mesmo com permissão | 3 | — |
| RQ-17 | desligado, checklist/tours/boas-vindas não aparecem | 3 | — |
| RQ-18 | uma jornada por painel; cada filho acrescenta o seu passo | 5 | — |
| RQ-19 | `kit:install` pergunta, padrão não | 6 | — |
| RQ-20 | `kit:install --onboarding` | 6 | — |
| RQ-21 | semear não liga o interruptor | 5, 6 | — |
| RQ-22 | só o interruptor, sem lista de painéis | 2 | — |
| RQ-23 | features qualificadas = tabela aprovada | 5 | — |
| RQ-24 | features de tenant em seeders só de tenant | 5 | — |
| RQ-25 | re-semear cria só o que falta | 5 | — |
| RQ-26 | rule em `.ai/rules` com os caminhos das telas | 7 | — |
| RQ-27 | teste que falha com tela sem seeder nem isenção | 7 | — |
| RQ-28 | ligado: checklist, tours e página nos três painéis | 3 | — |
| RQ-29 | boas-vindas do pacote sempre desligada | 3 | — |
| RQ-30 | desligado com a tela aberta: ação do checklist recusada, nada gravado | 3 | — |
| RQ-31 | desligado: checklist some no próximo carregamento | 3 | — |
| P-01 | textos em pt_BR e en, editáveis | 1, 5 | — |
| P-02 | widget de progresso some com o interruptor desligado | 3 | — |
| P-03 | passo de feature desligada some e não conta | 4, 5 | — |

## Objetivo

Ligar o pacote de onboarding de ponta a ponta: um interruptor na tela de configurações (nasce
desligado) decide, por request, se a autoria e o consumo existem; com ele ligado, os três painéis
mostram o checklist flutuante, os tours e a página "Primeiros passos". O conteúdo nasce de um seeder
pai que chama um seeder filho por feature do kit, cada filho acrescentando o seu passo, com tour,
à jornada do painel em que a feature vive — e as features que só existem com multi-tenant ficam em
filhos que só rodam com ele ligado.

Para que o conteúdo não envelheça, uma rule de projeto aponta o seeder filho a quem edita a tela da
feature, e um teste do kit fica vermelho quando nasce Resource ou Page sem seeder nem isenção.

## Contexto

- O plugin está registrado só no `/admin`, com `->launcher(false)->tours(false)` e
  `->manageFlows((bool) config('filament-onboarding.enabled', true))`
  (`app/Providers/Filament/AdminPanelProvider.php:plugin:285`). A chave
  `filament-onboarding.enabled` **não existe** no `config/filament-onboarding.php` publicado: o
  `manageFlows` vale sempre `true`. O widget `ProgressoOnboarding` cita a mesma chave inexistente no
  docblock
- `config/filament-onboarding.php:'locales':27` está em `['en']`: texto pt_BR gravado pelo seeder
  aparece, mas não é editável no painel
- Nenhuma jornada, passo ou condição existe; nenhum seeder toca o pacote

## Análise dos Arquivos Existentes

### Pacote (vendor, conferido no step 3)
- `FilamentOnboardingPlugin::register()` registra os resources de autoria (`manageFlows`) e a página
  de progresso (`progressPage`) **no boot do painel**; `launcher`, `tours` e `welcome` aceitam
  `bool|Closure` avaliado **por request** (`hasLauncher()`/`hasTours()`, em `renderLauncher()`); o
  render hook `BODY_END` é registrado sempre
- `skipWhen(Closure)` vai para o manager no `boot` e é consultado por request em
  `OnboardingManager::current()`: com `true`, o checklist não monta, a página de progresso sai do menu
  (`OnboardingProgress::shouldRegisterNavigation()` usa `Onboarding::current()`) e nenhuma tabela
  `onboarding_*` é lida
- Flows, steps e conditions são **globais** (sem coluna de tenant); só o progresso é escopado pelo
  tenant (`scope_type`/`scope_id`). `panel_id` nulo = todos os painéis
- Gravar por Eloquent (`create`/`save`) limpa o cache das definições (eventos `saved`/`deleted` dos
  models); `insert()` não
- `onboarding_flows.key` é único; `onboarding_steps.key` é único só junto com `flow_id`;
  `onboarding_conditions.key` é único
- Condição em código: closure registrada pelo `->conditions([...], [...])` do plugin (no boot), ou classe
  em `app/Onboarding/Conditions` implementando `Wallacemartinss\FilamentOnboarding\Contracts\OnboardingCondition`,
  descoberta sem registro. Código vence o banco em conflito de chave.
  Chave não registrada em `visibility_condition` **esconde** o item; exceção vira `false` e log
- `tour_steps` (json): lista de `{route|url, target, selector, placement, title, body, condition, advance, optional}`;
  `target` aceita `field:x`, `action:submit`, `part:table`, `part:search`, `column:x`, `link:rota`,
  `widget:FQCN` ou `custom` + `selector`; sem alvo que case, o popover centraliza

### Kit
- Padrão a copiar para o interruptor: `App\Support\AgentesDeIa::habilitado()` (decisor único, lê
  `config()` já sobreposto pelo banco), propriedade `agentes_ia_habilitado` em
  `app/Settings/ConfiguracoesDoKit.php:agentes_ia_habilitado:254`, linha no `mapaDeConfiguracao()`,
  migration em `database/settings/`, chave em `config/kit.php:'agentes_ia':621` e no `.env.example`
- Seção da tela: `app/Filament/Admin/Pages/ConfiguracoesDoKit.php:secao:1137` (agentes de IA);
  toda seção é colapsável com ícone e id estável (`.ai/rules/pages.md`)
- Policies do kit para os models do pacote já existem e estão no mapa de `App\Support\PoliciesDeVendor`
- Tenancy: `config('kit.tenancy.enabled')`, perguntado direto (sem helper); `DatabaseSeeder` usa o
  mesmo `if` para o `TenantsSeeder`. Com tenancy desligada as rotas de tenant do `/app` continuam
  registradas
- `KitInstall::semear()` (`app/Console/Commands/KitInstall.php:semear:437`) roda `db:seed --force`
  numa task; opções em `$signature` (`--no-seed` etc.)
- Seeders do kit: `updateOrCreate`, docblock "Idempotente", nunca factory/faker
- Decisores por request reaproveitados nas condições de visibilidade: `DockDeAtalhos::habilitadoNoPainel()`,
  `DashboardDinamico::habilitado()`, `config('kit.hub')`, `config('lockscreen.enabled')`,
  `config('command-center.enabled')`, `AgentesDeIa::habilitado()`, `config('kit.demo')`
- 2FA: `User::hasConfirmedTwoFactor()` (trait do Breezy) — relação `breezySession`, não coluna do
  usuário: condição em código, não pelo formulário de "atributo"
- Convite: `convidado_por_id` e `tenant_id` no model `Convite` — condição "convidou alguém" pelo
  formulário de agregado (gravada no banco)
- `tests/Kit/InventarioDeTelasTest.php:telasRegistradasNoPainel:98` já enumera as telas de cada painel
  a partir de `getPages()`/`getResources()` — o teste de inventário do passo 7 reaproveita a técnica

## Decisões de Desenho

| # | Decisão | Pergunta | Portão que falta | Quem decidiu, data |
|---|---|---|---|---|
| D1 | Decisor único `App\Support\TutorialGuiado::habilitado()`, lendo `kit.onboarding.habilitado` | convenção (`AgentesDeIa`) | surpreendente | sessão, 2026-10-21 |
| D2 | Chave de env `KIT_ONBOARDING`, `filter_var(..., FILTER_VALIDATE_BOOLEAN)`, default `false` | convenção (`.ai/rules/config.md`) | difícil de reverter | sessão, 2026-10-21 |
| D3 | Consumo desligado por `skipWhen(fn () => ! TutorialGuiado::habilitado())` mais `launcher`/`tours` por closure; `welcome(false)` fixo | fato do vendor | surpreendente | sessão, 2026-10-21 |
| D4 | Menu de autoria escondido por duas subclasses finas dos Resources do vendor (`App\Filament\Admin\Resources\Onboarding\JornadaResource` e `CondicaoResource`), com `canAccess()`/`shouldRegisterNavigation()` exigindo o interruptor — descobertas pelo `discoverResources()` do `/admin`, no lugar do `manageFlows()` *(alterado em 2026-10-21: a policy não esconde o menu do `master_global`, que passa pelo `Gate::before`)* | convenção (`AgenteIaResource`) | trade-off | sessão, 2026-10-21 |
| D5 | Seeders em `database/seeders/Onboarding/`, um arquivo por feature, com uma classe base abstrata que dá `jornada()`, `passo()` e `condicao()` por `firstOrCreate` na chave | RQ-05, RQ-25 | surpreendente | sessão, 2026-10-21 |
| D6 | Condições de visibilidade (feature desligada) em código, uma closure por decisor no `->conditions()` do plugin; condições de conclusão "contar registros" gravadas no banco pelo seeder | RQ-03, P-03 | trade-off | sessão, 2026-10-21 |
| D7 | Channel de log `onboarding` novo | convenção (um channel por feature) | — | sessão, 2026-10-21 |
| D8 | O teste de inventário lê o mapa `OnboardingSeeder::TELAS` (tela → seeder filho) e a lista `OnboardingSeeder::ISENTAS` (tela → motivo) | Q7 | surpreendente | sessão, 2026-10-21 |
| D10 | Helpers de `kit:install` de verdade (`diretorioDeInstalacaoDoKit`, `rodarKitInstallDeVerdade`, `rodarKitInstallDigitandoASenha`) sobem para `tests/Pest.php` quando um segundo arquivo os usar | Q18 (era Q?8) | trade-off | sessão, 2026-10-21 |
| D7b | O canal `onboarding` é garantido pelo `KitServiceProvider` (declarado só quando o `config/logging.php` do projeto não o tem) e o `config/logging.php` fica FORA de `KitUpdate::CAMINHOS_DO_KIT` — mesmo motivo das rotas do kit (`.ai/rules/providers.md`): arquivo do esqueleto não viaja sem apagar o do projeto (P-24) *(alterado em 2026-10-21: rodada 2 do step 9, RD2-03/CR2-02)* | gsferro no chat, 2026-10-21 | surpreendente | gsferro, 2026-10-21 |
| D13 | CT-70 sobe uma aplicação nova (`bootstrap/app.php`) com `afterBootstrapping(LoadConfiguration::class)` ajustando o `logging.channels` antes de os providers subirem — prova o fio do provider, não um método isolado | Q29 (era Q?1 da re-derivação do CT-70) | trade-off | sessão, 2026-10-21 |
| D14 | CT-16 distingue o painel pela chave da jornada no corpo; se a página não a mostrar, por um passo exclusivo de cada painel, com o do próprio painel como controle | Q30 | trade-off | sessão, 2026-10-21 |
| D15 | O rótulo do dataset do CT-37 descreve a invariante (P-11, P-22), não uma contagem | Q31 | surpreendente | sessão, 2026-10-21 |
| D11 | `KIT_ONBOARDING=false` com `force="true"` no `phpunit.xml`: a suíte mede o kit de fábrica; quem precisa liga por `ligarTutorialGuiado()` | Q19 (era Q?9) | difícil de reverter | sessão, 2026-10-21 |
| D12 | A verificação do inventário do onboarding é uma função que recebe os painéis (controles negativos e suíte Tenancy) | Q20 (era Q?10) | surpreendente | sessão, 2026-10-21 |
| D9 | O 404 da autoria e da página de progresso vem de um middleware de painel persistente (`isPersistent: true`, que o Filament 5 reaplica nos updates Livewire — `search-docs`, "Applying middleware"), e não do trait nas Pages que `.ai/rules/filament.md` descreve para o `AgenteIaResource`: as telas são do vendor, e o trait exigiria subclassificar 2 Resources e 7 Pages só para levar duas linhas | Q1 (Adendo 1) | difícil de reverter (troca por trait é local) | sessão, 2026-10-21 |

## Autorização

- **Resources de autoria**: subclasses `JornadaResource`/`CondicaoResource` com `canAccess()` e
  `shouldRegisterNavigation()` exigindo `TutorialGuiado::habilitado()` **e** o `parent::` (menu, hub, ⌘K e
  dock somem juntos, inclusive para o `master_global`). As policies do kit não mudam *(alterado em
  2026-10-21: D4)*
- **Middleware**: `App\Http\Middleware\ExigeTutorialGuiadoLigado`, persistente, nos três painéis —
  responde 404 nas rotas de autoria e na página de progresso com o interruptor desligado, antes da
  permissão (D9)
- **Página de progresso**: permissão da tela gerada pelo Shield, concedida a `panel_user` e `admin`
  nos três painéis pelo `PapeisSeeder` (ver passo 3)
- **Guards**: inalterados

## Rotas

Nenhuma rota nova do kit. Rotas do pacote que passam a existir:

| Método | URI | Name | Middleware |
|--------|-----|------|------------|
| GET | `/admin/onboarding` | `filament.admin.pages.onboarding` | painel + `ExigeTutorialGuiadoLigado` |
| GET | `/app/onboarding` (`/app/{tenant}/onboarding` com tenancy) | `filament.app.pages.onboarding` | idem |
| GET | `/infra/onboarding` | `filament.infra.pages.onboarding` | idem |

As rotas de autoria (`filament.admin.resources.onboarding-flows.*`, `…onboarding-conditions.*`) já existem.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Seção "Tutorial guiado" em Configurações da aplicação | Filament (SettingsPage) | `/admin/configuracoes-da-aplicacao` | liga/desliga e salva | Não |
| Página "Primeiros passos" | Filament Page (subclasse do kit `App\Filament\Pages\PrimeirosPassos` sobre a do vendor) *(alterado em 2026-10-21: desvio do passo 3)* | `/{painel}/onboarding` | vê a jornada, abre passo, inicia tour | Não (o tour sim) |
| Checklist flutuante (launcher) | Livewire (subclasse do kit sobre o do vendor), render hook `BODY_END` | todas as telas dos três painéis | abre checklist, marca, inicia tour; Esc fecha (P-28) | Sim |
| Contraste do tutorial (botão primário, destaque, texto secundário) | CSS do kit (`resources/css/filament/kit.css`, variáveis `--fio-theme-*` do pacote + o botão primário no tom 700) | checklist e página nos três painéis | leitura (AA, P-27) | Não *(alterado em 2026-10-22: QA-15 e QA-16 do gate, ciclo 3)* |
| Tour | Alpine (vendor) | telas das features | avança, pula, conclui | Sim |
| Autoria: Onboarding e Condições | Filament Resource (subclasses do kit `JornadaResource`/`CondicaoResource` sobre as do vendor) *(alterado em 2026-10-21: D4)* | `/admin/onboarding-flows`, `/admin/onboarding-conditions` | edita o que o seeder criou | Não |

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_ONBOARDING` | `false` | semeia o interruptor do tutorial na migration de settings; depois, o banco vence |

## Eventos / Listeners / Observers

Nenhum novo. Os eventos `saved`/`deleted` dos models do pacote limpam o cache; o seeder grava por Eloquent para dispará-los.

## Jobs / Queues

Nenhum.

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | Desligado: 1, e nenhuma tabela `onboarding_*` lida (skipWhen). Ligado: o render hook monta um componente Livewire do pacote; os tours rodam no cliente |
| O que é adiado, e por qual gatilho? | nada do kit |
| O que é memoizado por request? | o `canAccess()` de cada tela que a condição `tela:{chave}` pergunta, uma vez por tela, preso ao objeto do request (`TutorialGuiado::abre()`, P-25); o interruptor vem de `config()` (sem query) *(alterado em 2026-10-21: QA-09 do gate, ciclo 2)* |
| O que é cacheado entre requests? | definições de jornada no cache do pacote (TTL 3600 s), limpas pelo seeder ao gravar por Eloquent |
| Custo do caminho principal | desligado: zero query a mais por tela; ligado: o custo do pacote mais uma checagem de acesso por tela distinta da jornada (cada uma paga as consultas de papel do `Gate::before`), que não cresce com o número de passos (CT-73) *(alterado em 2026-10-21: QA-09)* |

## Impacto em Features Existentes

- Agentes de IA, dock, dashboard dinâmico, hub, lockscreen: só leitura dos decisores pelas condições
- `InventarioDeTelasTest`, `PermissoesDeTelasTest` e `PermissoesDeResourcesTest`: a página de progresso entra nos três painéis
- `KitInstall`: opção nova e uma pergunta a mais no modo interativo — os testes de instalação que respondem perguntas por ordem podem precisar da resposta nova
- `ConfiguracoesDoKitTest` (mapa × banco, reversibilidade da migration de settings)

## Rollback

- **Migration down**: a migration de settings remove `onboarding_habilitado`
- **Feature flag**: o próprio interruptor; desligado, nada aparece
- **Reversão de dados**: os registros semeados ficam; apagá-los é manual pela autoria (o seeder não apaga nada)

## Dependências

Nenhuma nova. `wallacemartinss/filament-onboarding` ^2.1 já está no `composer.json`.

## Riscos

- Seletores de tour apontando para markup de vendor (infra): mitigado usando `part:table`/`part:search`
  ou nenhum alvo (popover centralizado), nunca CSS do vendor
- Pergunta nova no `kit:install` quebrando a ordem dos testes que respondem prompts: rodar `tests/Kit/KitInstall*` cedo
- Middleware persistente casando rota pelo nome: a lista de nomes vem dos `getRouteBaseName()` do pacote, não de string escrita à mão

## Channel de Log da Feature

`config/logging.php` não tem `onboarding` (conferido no step 3). O passo 1 cria o canal abaixo, e o `KitServiceProvider::garantirCanalDoTutorialGuiado()` o declara em tempo de boot quando o `config/logging.php` do projeto não o tem — projeto atualizado pelo `kit:update`, que não entrega esse arquivo (D7b, P-24) *(alterado em 2026-10-21: rodada 2 do step 9)*:

No molde do channel irmão `dock-de-acoes-rapidas` (driver por `LOG_KIT_DRIVER`, `NullHandler` na suíte):

```php
'onboarding' => [
    'driver'               => env('LOG_KIT_DRIVER', 'daily'),
    'handler'              => NullHandler::class,
    'path'                 => storage_path('logs/onboarding.log'),
    'level'                => env('LOG_LEVEL', 'debug'),
    'days'                 => 14,
    'replace_placeholders' => true,
],
```

## Estrutura de Implementação

### 1. Config, env e channel

> Skills: `laravel-best-practices`

- **Paths**: `config/kit.php` (bloco `'onboarding' => ['habilitado' => filter_var(env('KIT_ONBOARDING', false), FILTER_VALIDATE_BOOLEAN)]`, com docblock no molde do bloco `agentes_ia`), `.env.example` (`KIT_ONBOARDING=false` com comentário), `config/logging.php` (channel `onboarding`), `config/filament-onboarding.php` (`'locales' => ['pt_BR', 'en']`)
- Listas paralelas (varredura da classe irmã, step 5): o dataset de channels de `tests/Kit/QualidadeDeCodigoTest.php` (suíte sem log em disco) ganha `onboarding`; o mapa de chaves `KIT_*` de `tests/Kit/DiagramasDaArquiteturaTest.php` ganha `KIT_ONBOARDING` com as assinaturas da feature
- **Atende**: RQ-11, P-01 (e infra de log de RQ-16, RQ-25)
- **Logs**: nenhum (config)

### 2. Settings, tela e decisor

> Skills: `laravel-best-practices`, `filament-development`

- **Paths**: `app/Support/TutorialGuiado.php` (`final class`, `public static function habilitado(): bool` → `(bool) config('kit.onboarding.habilitado', false)`); `app/Settings/ConfiguracoesDoKit.php` (`public bool $onboarding_habilitado`, docblock "Nasce DESLIGADO… lido por request em `TutorialGuiado::habilitado()`"; linha `'onboarding_habilitado' => 'kit.onboarding.habilitado'` no `mapaDeConfiguracao()`); `database/settings/2026_10_21_100000_add_onboarding_to_kit_settings.php` (`add('onboarding_habilitado', (bool) config('kit.onboarding.habilitado', false))`, `down` com `delete`, cache do repositório limpo como na migration dos agentes); `app/Filament/Admin/Pages/ConfiguracoesDoKit.php` (seção `$this->secao('aplicacao', 'tutorial-guiado', 'Tutorial guiado', 'heroicon-o-map')` com `Toggle::make('onboarding_habilitado')` e helperText dizendo que desligado esconde autoria, checklist, tours e página, e que semear não liga)
- Sem lista de painéis (RQ-22)
- Lista paralela: a contagem de propriedades de `tests/Kit/KitInfoTest.php` passa de 71 para 72, com a linha do histórico no docblock
- **Atende**: RQ-10, RQ-11, RQ-22
- **Logs**: nenhum novo — a tela já registra a gravação no channel `configuracoes`

### 3. Painéis: plugin nos três, 404 e menu

> Skills: `filament-development`, `laravel-best-practices`

- **Paths**: `app/Providers/Filament/{Admin,App,Infra}PanelProvider.php`; `app/Http/Middleware/ExigeTutorialGuiadoLigado.php`; `app/Filament/Admin/Resources/Onboarding/{Jornada,Condicao}Resource.php`; `app/Livewire/ChecklistDoOnboarding.php`; `app/Providers/KitServiceProvider.php`; `app/Filament/Admin/Widgets/ProgressoOnboarding.php`; `database/seeders/PapeisSeeder.php` (se a permissão da página não cair na matriz sozinha)
- *(alterado em 2026-10-21: QA-01 do quality gate — o registro real é `->launcher(fn (): bool => self::visivel())->tours(fn (): bool => self::visivel())->welcome(false)->progressPage(false)->progressPageNavigation(...)->skipWhen(fn (): bool => ! self::visivel())->conditions(...)`, com `visivel()` = `PrimeirosPassos::canAccess()`; ver o item da subclasse abaixo)* Desenho original — nos três painéis, o mesmo registro (helper estático para não repetir, ex.: `TutorialGuiado::plugin(): FilamentOnboardingPlugin`):
  `->launcher(fn (): bool => TutorialGuiado::habilitado())->tours(fn (): bool => TutorialGuiado::habilitado())->welcome(false)->progressPage()->progressPageNavigation(label: 'Primeiros passos', icon: 'heroicon-o-map')->skipWhen(fn (): bool => ! TutorialGuiado::habilitado() || ! PermissaoDaTela::permite(OnboardingProgress::class))`;
  no `/admin`, além disso `->manageFlows()` **sem** a chave inexistente
- Plugin nos três painéis também atende `.ai/rules/providers-filament.md` (`OnboardingProgress::plugin()` resolve pelo painel corrente)
- Middleware `ExigeTutorialGuiadoLigado::handle()`: com o interruptor desligado e a rota corrente sendo
  de autoria ou da página de progresso (nomes montados a partir das classes do pacote), `abort(404)`;
  registrado com `isPersistent: true` (vale também nos updates Livewire, como `DefinirTenantDePermissoes`)
- *(alterado em 2026-10-21: achado da suíte — a Page do vendor tem `canAccess()` permissivo)* A página é a subclasse `App\Filament\Pages\PrimeirosPassos` com `ExigePermissaoDaTela` (permissão `View:PrimeirosPassos`) e o interruptor como regra local, registrada nos `->pages()`; plugin com `progressPage(false)`; o middleware só responde o 404. O texto abaixo é o desenho original:
- Permissão da página de progresso (`View:OnboardingProgress`, gerada pelo Shield): a Page é do vendor e
  não aceita o concern `ExigePermissaoDaTela`, então ela é consultada por `App\Support\PermissaoDaTela::permite(OnboardingProgress::class)`,
  o predicado do kit para tela de pacote — no `skipWhen` (sem a permissão, checklist e item de menu
  somem: a permissão da página passa a ser "usa o tutorial") e no middleware (403 na rota da página com
  o tutorial ligado e sem a permissão). *(alterado em 2026-10-21: revisão profunda, step 5)*
- Autoria: subclasses `app/Filament/Admin/Resources/Onboarding/{Jornada,Condicao}Resource.php` (D4) *(alterado em 2026-10-21: a policy não alcança o `master_global`)*
- Checklist já montado (RQ-30): `App\Livewire\ChecklistDoOnboarding extends Wallacemartinss\FilamentOnboarding\Livewire\OnboardingLauncher`, com `hydrate()` que registra o `warning` e faz `abort(404)` quando `TutorialGuiado::visivel()` é falso — interruptor desligado OU permissão da página retirada — *(alterado em 2026-10-21: RD-03 do step 9 e QA-10 do gate)*; `selectFlow()` e `updatedFlowKey()` só aceitam jornada visível no painel (P-20, CT-18, CT-66); registrado sob o mesmo nome do pacote (`Livewire::component('filament-onboarding-launcher', …)`) num provider do kit que boota depois do pacote — o render hook do plugin continua montando pelo nome. O middleware não alcança este caso: o checklist mora nas telas das features, não nas rotas do onboarding
- A view publicada `resources/views/vendor/filament-onboarding/pages/progress.blade.php` lê `progress->completed_at` em vez do `completedAt()` do pacote (tipado `Illuminate\Support\Carbon`, quebrava com o `Date::use(CarbonImmutable)` do kit); a pasta entra em `KitUpdate::CAMINHOS_DO_KIT` por ser autoral (`.ai/rules/vendor.md`) *(alterado em 2026-10-21: QA-08 do gate, ciclo 2; CT-72)*
- Widget `ProgressoOnboarding::canView()` acrescenta `TutorialGuiado::habilitado()` (P-02); docblock corrigido (a chave citada não existe)
- **Atende**: RQ-12, RQ-15, RQ-16, RQ-17, RQ-28, RQ-29, RQ-30, RQ-31, P-02
- **Logs**:
  - `Log::channel('onboarding')->warning('[ExigeTutorialGuiadoLigado@handle] Tela de onboarding recusada com o tutorial desligado | rota: '.$nome, ['user_id' => auth()->id(), 'rota' => $nome, 'motivo' => 'tutorial_desligado'])`
  - `Log::channel('onboarding')->warning('[ChecklistDoOnboarding@hydrate] Ação do checklist recusada | motivo: '.$motivo, ['user_id' => auth()->id(), 'motivo' => $motivo])`, com `$motivo` = `tutorial_desligado` ou `sem_permissao_da_pagina` *(alterado em 2026-10-21: RD-03 e QA-10)*
  - `Log::channel('onboarding')->warning('[ChecklistDoOnboarding@updatedFlowKey] Jornada fora do painel recusada no checklist', ['user_id' => auth()->id(), 'flow' => Str::limit(preg_replace('/\s+/', ' ', $flowKey), 80), 'motivo' => 'jornada_invisivel'])` — sem o valor do cliente na mensagem, e no context sem quebra de linha e truncado (P-26) *(alterado em 2026-10-22: QA-18 do gate, ciclo 3)* *(alterado em 2026-10-21: CR2-08 e QA-11)*

### 4. Condições em código

> Skills: `laravel-best-practices`

- *(alterado em 2026-10-21: revisão adversarial — P-11/P-12/P-13)* Passo com tela: visibilidade padrão `tela:{chave}`, gerada de `OnboardingSeeder::TELAS` e respondida pelo `canAccess()` da tela no painel corrente; sem tela: `dock_no_painel`, `dashboard_dinamico_no_painel`, `bloqueio_de_tela_ligado`, `multi_tenant_ligado`. As condições `hub_ligado`, `agentes_de_ia_ligados`, `central_de_comandos_ligada` e `demo_ligada` não existem mais
- **Path**: `app/Support/TutorialGuiado.php` — um array passado a `->conditions($condicoes, $rotulos)` dentro de `TutorialGuiado::plugin()` (registrado no boot pelo plugin, `FilamentOnboardingPlugin::conditions()`), uma closure por chave. *(alterado em 2026-10-21: Ponytail, step 6 — eram oito classes em `app/Onboarding/Conditions/`)*
- Conclusão: `tem_dois_fatores` → `fn (Model $usuario): bool => $usuario->hasConfirmedTwoFactor()`
- Visibilidade (P-03, P-11), lendo o painel por `Filament::getCurrentPanel()`: `tela:{chave}` (uma por filho com tela) e, para os passos sem tela própria, `dock_no_painel`, `bloqueio_de_tela_ligado`, `multi_tenant_ligado`; `dashboard_dinamico_no_painel` exige também o `canEdit()` da página (P-22) *(alterado em 2026-10-21: QA-01 — a lista de sete condições é o desenho anterior ao item do topo deste passo)*
- Rótulos em pt_BR no segundo array (o que o editor de passos mostra)
- **Atende**: RQ-03, P-03
- **Logs**: nenhum — a exceção numa condição já é registrada pelo pacote

### 5. Seeders: pai, base e filhos

> Skills: `laravel-best-practices`

- **Paths**: `database/seeders/Onboarding/SeederDeOnboarding.php` (abstrata), `database/seeders/OnboardingSeeder.php` (pai), `database/seeders/Onboarding/*Seeder.php` (filhos)
- Base: `jornada(string $painel): OnboardingFlow` — `firstOrCreate(['key' => "primeiros-passos-{$painel}"], [...])` com `panel_id`, título pt_BR/en, `is_dismissible` true; `passo(OnboardingFlow, string $key, array $atributos): void` — `firstOrCreate(['flow_id', 'key'], $atributos)`; `condicao(string $key, array $atributos): void` — `firstOrCreate(['key'], ...)`. `firstOrCreate` em vez do `updateOrCreate` dos outros seeders do kit, de propósito: RQ-25 (cada um com docblock dizendo por quê)
- Pai `OnboardingSeeder::run()`: chama os filhos de `FILHOS` e, se `config('kit.tenancy.enabled')`, os de `FILHOS_DE_TENANCY`; constantes `TELAS` e `ISENTAS` (D8). Não toca o settings (RQ-21). Não entra no `DatabaseSeeder`
- Filhos (um por feature — RQ-05, RQ-23), com o painel e o modo de conclusão da tabela aprovada:

| Seeder filho | Painel(is) | Conclui por | Visibilidade *(alterado em 2026-10-21: QA-01 — padrão `tela:{chave}` nos filhos com tela, P-11)* |
|---|---|---|---|
| `PerfilEDoisFatoresSeeder` | admin, app, infra | condição `tem_dois_fatores` | `tela:perfil-e-dois-fatores` |
| `BuscaGlobalSeeder` | admin, app, infra | tour (manual) | — |
| `DockDeAtalhosSeeder` | admin, app, infra | tour (manual) | `dock_no_painel` |
| `BloqueioDeTelaSeeder` | admin, app, infra | tour (manual) | `bloqueio_de_tela_ligado` |
| `DashboardDinamicoSeeder` | admin, app, infra | tour (manual) | `dashboard_dinamico_no_painel` |
| `UsuariosSeeder` | admin | tour (manual) | `tela:usuarios` |
| `ConvitesSeeder` | admin | condição `convidou_alguem` (banco) | `tela:convites` |
| `PapeisEPermissoesSeeder` | admin | tour (manual) | `tela:papeis-e-permissoes` |
| `ConfiguracoesDaAplicacaoSeeder` | admin | visita | `tela:configuracoes-da-aplicacao` |
| `AgentesDeIaSeeder` | admin | tour (manual) | `tela:agentes-de-ia` |
| `HubDeNavegacaoSeeder` | admin, app | tour (manual) | `tela:hub-de-navegacao` |
| `OrganizacoesSeeder` *(tenancy)* | admin | tour (manual) | `tela:organizacoes` |
| `MembrosDaOrganizacaoSeeder` *(tenancy)* | app | tour (manual) | `tela:membros-da-organizacao` |
| `ConvitesDaOrganizacaoSeeder` *(tenancy)* | app | condição `convidou_alguem_na_organizacao` (banco, com `scope_column` `tenant_id`) | `tela:convites-da-organizacao` |
| `ConvitesRecebidosSeeder` *(tenancy)* | app | tour (manual) | `tela:convites-recebidos` |
| `TrocaDeOrganizacaoSeeder` *(tenancy)* | app | tour (manual) | `multi_tenant_ligado` |
| `ProjetosSeeder` *(tenancy)* | app | tour (manual) | `tela:projetos` |
| `HubDeInfraestruturaSeeder` | infra | tour (manual) | `tela:hub-de-infraestrutura` |
| `PulseSeeder` | infra | tour (manual) | `tela:pulse` |
| `BackupsSeeder` | infra | tour (manual) | `tela:backups` |
| `LogsDaAplicacaoSeeder` | infra | tour (manual) | `tela:logs-da-aplicacao` |
| `ExcecoesSeeder` | infra | tour (manual) | `tela:excecoes` |
| `FilasSeeder` | infra | tour (manual) | `tela:filas` |
| `AuditoriaSeeder` | infra | tour (manual) | `tela:auditoria` |
| `ExecucoesDeIaSeeder` | infra | tour (manual) | `tela:execucoes-de-ia` |
| `LixeiraSeeder` | infra | tour (manual) | `tela:lixeira` |
| `CentralDeComandosSeeder` | infra | tour (manual) | `tela:central-de-comandos` |

- Paradas de tour: `route` = nome da rota da tela (conferido no `route:list` do step 3); `target` só dos
  alvos que o pacote nomeia (`part:table`, `part:search`, `action:submit`, `field:x`) ou nenhum
  (popover centralizado); textos pt_BR e en
- **Atende**: RQ-01, RQ-02, RQ-03, RQ-04, RQ-05, RQ-09, RQ-18, RQ-21, RQ-23, RQ-24, RQ-25, P-01, P-03
- **Logs**:
  - `Log::channel('onboarding')->info('[OnboardingSeeder@run] Jornadas semeadas | filhos: '.$n, ['filhos' => $n, 'tenancy' => (bool) config('kit.tenancy.enabled')])`
  - `Log::channel('onboarding')->info('[SeederDeOnboarding@passo] Passo já existia, preservado | passo: '.$key, ['flow' => $flow->key, 'passo' => $key])` (`debug`)

### 6. `kit:install`

> Skills: `laravel-best-practices`

- **Path**: `app/Console/Commands/KitInstall.php`
- `$signature` ganha `{--onboarding : Popula as jornadas de tutorial guiado (o interruptor continua desligado)}`
- Depois de `semear()` (só se semeou): com `--onboarding`, roda `db:seed --class=OnboardingSeeder --force`
  numa task; sem ela e em modo interativo, `confirm('Popular os tutoriais guiados?', default: false)`;
  não interativo sem a opção, não semeia. Falha vira aviso com o comando a rodar
- **Atende**: RQ-06, RQ-19, RQ-20, RQ-21
- **Logs**: nenhum novo — a task já reporta; o seeder loga (passo 5)

### 7. Rule e teste de inventário

> Skills: `requirement-to-rule` (formato), `pest-testing`

- **Paths**: `.ai/rules/onboarding.md` (com `paths:` para `app/Filament/**`, `app/Providers/Filament/**`, `database/seeders/Onboarding/**`, `database/seeders/OnboardingSeeder.php`), linha no `.ai/rules/index.md`; `tests/Kit/InventarioDeOnboardingTest.php`
- Rule: quem cria ou altera tela de feature revisa, atualiza ou cria o seeder filho; feature nova ganha filho chamado pelo pai e linha em `TELAS`; tela sem tutorial entra em `ISENTAS` com o motivo
- Teste: para cada painel, toda Page e todo Resource registrado está em `OnboardingSeeder::TELAS` ou em `ISENTAS`; todo filho de `TELAS` existe e está em `FILHOS`/`FILHOS_DE_TENANCY`; nenhum filho fora do pai
- **Atende**: RQ-13, RQ-14, RQ-26, RQ-27
- **Logs**: nenhum

### 8. Documentação

- **Paths**: `docs/pt/recursos/tutorial-guiado.md` e `docs/en/recursos/tutorial-guiado.md` (como iniciar via seeder e via `kit:install`, tabela seeder → feature → tela → painel, tenancy, re-semear, ligar o interruptor), `docs/{pt,en}/recursos/index.md`, `docs/{pt,en}/recursos/configuracoes-do-kit.md` (seção nova), `docs/{pt,en}/comecar/instalacao-avancada.md` (`--onboarding`), `README.md`/`README.en.md` (comandos), `docs/{pt,en}/comecar/atualizando-o-projeto.md` (nota de atualização: interruptor nasce desligado, como fazer para ligar), `CHANGELOG.md`
- **Atende**: RQ-01, RQ-07, RQ-08
- **Logs**: nenhum

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação. Atalhos deliberados com `ponytail:`.
> Após implementar, `/ponytail:ponytail-review` no diff.
>
> **Baseline antes do primeiro commit**: suíte completa em `main`, falhas pré-existentes por nome.

## Testes

> Ver `04-casos-de-teste.md`. `05-casos-de-teste-browser.md` só se a `## Costuras de Teste` do `04` tiver costura `browser`.

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/filacheck --fix`
- [ ] `vendor/bin/pest --filter=Onboarding --compact`
- [ ] `vendor/bin/pest --parallel --tia` comparado à baseline de `main`
- [ ] `/code-review high main...HEAD` + passe de eixos (step 9)

## Commits
- `:wrench: onboarding: interruptor no settings e plugin nos três painéis`
- `:seedling: onboarding: seeder pai e filhos por feature`
- `:memo: onboarding: rule, docs e wiki`

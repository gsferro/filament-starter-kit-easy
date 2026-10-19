# Plano de Ação — Agentes de IA ligados pelo settings, por painel

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: —
- **Motivo**: —
- **Toca infra compartilhada?**: sim → settings da instalação (`App\Settings\ConfiguracoesDoKit` + migration de settings), `config/kit.php`, a tela `/admin/configuracoes-da-aplicacao`, o `AppPanelProvider` (o render hook do chat sai dele) e `App\Providers\Concerns\ConfiguraFilamentGlobal` (o hook entra lá, global), além do inventário `telasDoKit()` de `tests/Pest.php`. Força regressão contra as features que consomem essa infra: `settings-do-kit`, `configuracoes-secoes-colapsaveis`, `main/dock-de-acoes-rapidas` (catálogo e pilha de botões), `fix/pilha-de-botoes-flutuantes` (chat + dock + voltar ao topo), `pacotes-laravel-ia` (o chat do Assistente)

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Interruptor liga/desliga o uso dos agentes | 2, 3, 4 | — |
| RQ-02 | Lista de painéis em que o agente é disponibilizado | 2, 3, 4, 7 | — |
| RQ-03 | Mesmo modelo dos outros pacotes no settings (banco vence `.env`, vale no request seguinte) | 1, 2, 4 | — |
| RQ-04 | Desligado: o Resource some do menu do `/admin` | 5 | — |
| RQ-05 | Desligado: `/admin/agentes-ia` responde 404 mesmo com permissão | 5 | — |
| RQ-06 | Ligado: o Resource exibe normalmente | 5, 8 | — |
| RQ-07 | Nasce desligado | 1, 2, 8 | — |
| RQ-08 | Painéis como no dock: lista, vazio = nenhum | 1, 3, 4, 7 | — |
| P-01 | A lista governa o chat do Assistente; o catálogo continua só no `/admin` | 7 | — |
| P-02 | Desligado: os widgets do catálogo somem do dashboard do `/admin` | — | promovida a RQ-09 (Adendo 1) |
| RQ-09 | Desligado: o grupo de widgets "Agentes de IA" do dashboard do `/admin` não é exibido | 6 | Adendo 1 (Q2) |
| P-03 | Chat com o uso desligado no painel: toda ação responde 404, nada vai ao agente | 7 | — |
| P-04 | "Menu" = barra lateral, hub, ⌘K e catálogo do dock | 5 | — |
| P-05 | `.env` semeia (`KIT_AGENTES_IA`, `KIT_AGENTES_IA_PAINEIS`); o banco vence | 1, 2 | — |
| P-06 | Tela do catálogo já aberta: a próxima ação responde 404 com o uso desligado | 5 | o `hydrate` das Pages já passa por `authorizeResourceAccess()` (trait) |

## Objetivo

Dar à instalação dois controles sobre a camada de IA, na tela de configurações da aplicação: um
interruptor que liga o uso dos agentes e uma lista dos painéis em que o chat do Assistente fica
disponível. Desligado — que é o estado de fábrica —, o catálogo "Agentes de IA" sai do menu do `/admin`
e a URL dele responde 404, o chat não é renderizado em painel nenhum e os widgets do catálogo somem do
dashboard. Ligado, tudo volta ao que é hoje, e o chat passa a poder aparecer também no `/admin` e no
`/infra`, painel a painel.

Nada disso toca o banco no request: as duas chaves chegam pela config já sobreposta pelo banco no
boot, exatamente como o dock e o dashboard dinâmico.

## Contexto

Hoje a camada de IA está sempre ligada: o catálogo é um Resource do `/admin` sem condição além da
permissão, e o chat do Assistente é um render hook fixo do `AppPanelProvider` — presente em toda tela
do `/app`, ausente dos outros painéis, sem opção. Quem instala o kit sem provedor de IA vê um botão de
chat que responde "indisponível" e um catálogo que não usa. O dock e o dashboard dinâmico já mostram o
desenho que se pede aqui: interruptor e lista de painéis no settings, decididos por request.

## Análise dos Arquivos Existentes

### `app/Settings/ConfiguracoesDoKit.php`

- Contrato de propriedade nova: três lugares — a propriedade, a linha em `mapaDeConfiguracao()` e o `add()` numa migration de settings nova (`app/Settings/ConfiguracoesDoKit.php:mapaDeConfiguracao():443`; o docblock da classe, "Ao acrescentar uma propriedade")
- Precedente direto da lista de painéis "vazio = nenhum": `app/Settings/ConfiguracoesDoKit.php:$dock_paineis:240`, mapeada em `app/Settings/ConfiguracoesDoKit.php:'dock_paineis':511`
- Precedente do par interruptor + lista: `app/Settings/ConfiguracoesDoKit.php:$dashboard_dinamico_habilitado:228` e `:$dashboard_dinamico_paineis:231`
- `aplicarNaConfig()` sobrepõe a config com o banco uma vez por boot (`app/Settings/ConfiguracoesDoKit.php:aplicarNaConfig():605`): quem lê `config()` por request recebe o banco sem consulta extra (`.ai/rules/settings.md`)

### `database/settings/`

- Migration-modelo: `database/settings/2026_10_14_030956_add_dock_to_kit_settings.php:up():22` — semeia de `config()`, limpa o cache serializado do repositório na ida e na volta

### `config/kit.php` e `.env.example`

- Bloco do dock: `config/kit.php:'dock':591` — `paineis` por `explode(',', env)`, vazio = nenhum; bloco do dashboard dinâmico: `config/kit.php:'dashboard_dinamico':563` — `habilitado` por `filter_var(..., FILTER_VALIDATE_BOOLEAN)` (falha fechado, `.ai/rules/config.md`)
- `.env.example:'KIT_DOCK_PAINEIS':222` — a chave do dock, comentada

### `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`

- Aba `aplicacao` (`app/Filament/Admin/Pages/ConfiguracoesDoKit.php:abaAplicacao():988`); seção do dock com `Select::make('dock_paineis')->multiple()->rule('array')->options(Paineis::opcoes())` (`app/Filament/Admin/Pages/ConfiguracoesDoKit.php:'dock_paineis':1091`); seção do dashboard dinâmico com o toggle `->live()` e o select `->visible(fn (Get $get) => (bool) $get('dashboard_dinamico_habilitado'))` (`app/Filament/Admin/Pages/ConfiguracoesDoKit.php:'dashboard_dinamico_paineis':1059`)
- Toda seção nasce por `secao()` (`app/Filament/Admin/Pages/ConfiguracoesDoKit.php:secao():304`) e todo campo mora numa seção — `.ai/rules/pages.md`, enforçado por `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php`

### Decisores por request já existentes

- `app/Support/DockDeAtalhos.php:habilitadoNoPainel():110` — `in_array($painel->getId(), (array) config('kit.dock.paineis', []), true)`, nulo = falso
- `app/Support/DashboardDinamico.php:habilitadoPara():56` — flag && (lista vazia || painel na lista)

### `app/Filament/Admin/Resources/AgentesIa/AgenteIaResource.php`

- Sem `canAccess()` nem `shouldRegisterNavigation()` próprios (`app/Filament/Admin/Resources/AgentesIa/AgenteIaResource.php:class AgenteIaResource:28`): vale o vendor — `vendor/filament/filament/src/Resources/Resource/Concerns/HasAuthorization.php:canAccess():28` (= `canViewAny()`) e `vendor/filament/filament/src/Resources/Resource/Concerns/HasNavigation.php:shouldRegisterNavigation():183`
- Padrão do kit para Resource condicionado a config: `app/Filament/App/Resources/Projetos/ProjetoResource.php:shouldRegisterNavigation():76` e `:canAccess():88` (`daDemo() && parent::canAccess()`) — "em Resource são dois métodos" (`.ai/rules/filament.md`)
- As três Pages (`ListAgentesIa`, `CreateAgenteIa`, `EditAgenteIa`) herdam a autorização do vendor: `vendor/filament/filament/src/Resources/Pages/Concerns/CanAuthorizeResourceAccess.php:authorizeResourceAccess():17` faz `abort_unless(static::getResource()::canAccess(), 403)` no `mount` e no `hydrate` — é o ponto a sobrescrever para devolver **404** em vez de 403 (RQ-05)
- A busca global já segue `canAccess()`: `vendor/filament/filament/src/Resources/Resource/Concerns/HasGlobalSearch.php:canGloballySearch():36` termina em `&& static::canAccess()`, e a categoria de registros do Spotlight consulta esse método (`vendor/wezlo/filament-search-spotlight/src/Categories/RecordsCategory.php:canGloballySearch():48`). As categorias do kit (`ResourcesAutorizadasCategory`, `AcoesDeCriacao`) e o hub (`DescobreCardsDoPainel`) consultam `canAccess()` e `shouldRegisterNavigation()`; o catálogo do dock também — P-04 sai de graça dos dois métodos

### Widgets do catálogo

- `app/Filament/Admin/Widgets/AgentesIaStats.php:class AgentesIaStats:19` e `app/Filament/Admin/Widgets/AgentesIaPorProvider.php:class AgentesIaPorProvider:20`, os dois com `ExigePermissaoDoWidget`, cujo hook para condição além da permissão é `app/Filament/Concerns/ExigePermissaoDoWidget.php:fonteDeDadosDisponivel():66` (sobrescrever este, nunca `canView()`)

### O chat do Assistente

- Registrado só no `/app`, como render hook `BODY_END` do painel (`app/Providers/Filament/AppPanelProvider.php:BODY_END:166`), que não monta o componente em tela de autenticação (`app/Providers/Filament/AppPanelProvider.php:corrente():169`)
- Hooks globais do kit, sem `scopes`, valem para todo painel: `app/Providers/Concerns/ConfiguraFilamentGlobal.php:configuraBotaoVoltarAoTopo():160` (e `:configuraVersaoNoRodape():132`), chamados de `app/Providers/Concerns/ConfiguraFilamentGlobal.php:configuraFilamentGlobal():63`
- A CSS do chat já é asset global do pacote `kit` (`app/Providers/KitServiceProvider.php:'kit-assistente-chat':755`), então o componente já tem estilo em qualquer painel
- Ponto único de autorização das actions: `app/Livewire/AssistenteChatWidget.php:assertContexto():219` — 403 sem usuário, 404 em conversa alheia, com `warning` no canal `ai`
- `Filament::getCurrentPanel()` está disponível no update Livewire: `SetUpPanel` é middleware persistente (`vendor/filament/filament/src/FilamentServiceProvider.php:addPersistentMiddleware():128`)
- A pilha de botões flutuantes descobre o chat pelo DOM para a faixa de colisão com o dock (`resources/views/filament/voltar-ao-topo.blade.php:temChat:164`), **mas o recuo do botão "voltar ao topo" é decidido pelo id do painel**: `bottom-24` só no `/app`, `bottom-6` nos outros (`resources/views/filament/voltar-ao-topo.blade.php:$recuoDoBotao:97`). Com o chat no `/admin` ou no `/infra`, os dois botões se sobreporiam; com o chat desligado no `/app`, sobra um recuo de 4,5 rem sem nada embaixo. O recuo precisa seguir a **mesma condição** que renderiza o chat *(alterado em 2026-10-19: revisão profunda, achado do Codex)*

### Testes que dependem do estado atual (lote T0)

Com o uso nascendo desligado, estes casos passam a precisar ligá-lo no arranjo:

- `tests/Kit/AgenteIaResourceTest.php:'ViewAny:AgenteIa':119` e os demais do arquivo (`CreateAgenteIa` por `Livewire::test`)
- `tests/Kit/PaginasInfraTest.php:'agentes de IA':108`; `tests/Kit/PermissoesDeResourcesTest.php:'admin: AgenteIa':66`
- `tests/Kit/CarregamentoContinuoTest.php:'/admin/agentes-ia | master_global | rolagem | Scroll | kit':132`; `tests/Kit/VisoesDeTabelaTest.php:'/admin/agentes-ia':122` (e as linhas `/admin/agentes-ia` do dataset)
- `tests/Browser/TelasDoKitTest.php:'/admin/agentes-ia/create':44` e `:47` (o lote de `telasDoKit()` e o formulário do agente)
- Chat: `tests/Kit/TelasSemDockNemChatTest.php:'controle app':90` (CT-04 afirma chat só no `/app`), `tests/Tenancy/TelasSemDockNemChatTenancyTest.php:'[CT-10]':7`, `tests/Kit/AssistenteChatWidgetTest.php:'[CT-15]':67` e irmãos (CT-16, CT-22, CT-24, CT-25, CT-26), `tests/Browser/AssistenteChatEstiloTest.php:'[CT-B09]':70` e `'[CT-B10]':93`, `tests/Browser/PilhaDeBotoesFlutuantesTest.php:'[CT-B01]':157` e irmãos, `tests/Browser/BoasVindasTest.php` (só cita o hook em comentário)
- Helpers-modelo em `tests/Pest.php:ligarDock():603` e `tests/Pest.php:ligarDashboardDinamico():589`

### `kit:update`

- `app/Console/Commands/KitUpdate.php:'app/Filament':101`, `:'app/Livewire':115`, `:'app/Settings':150`, `:'app/Support':151`, `:'config/kit.php':153`, `:'database/settings':196` — todo arquivo desta feature cai numa pasta já entregue; nada a acrescentar na lista

## Decisões de Desenho

| # | Decisão | Pergunta | Portão que falta | Quem decidiu, data |
|---|---|---|---|---|
| D1 | Um decisor só, `App\Support\AgentesDeIa` (final, estático), como único leitor de `kit.agentes_ia.*`: `habilitado()` e `assistenteNoPainel(?Panel)` — o mesmo papel de `DockDeAtalhos::habilitadoNoPainel()` | convenção existente (dock, dashboard dinâmico) | difícil de reverter | sessão, 2026-10-19 |
| D2 | O 404 do catálogo vem de um trait nas três Pages do Resource, `App\Filament\Concerns\ExigeAgentesDeIaLigados`, que sobrescreve `authorizeResourceAccess()` com `abort_unless(AgentesDeIa::habilitado(), 404)` **antes** do `parent::` — o 403 por permissão continua vindo do vendor. O Resource ganha `canAccess()` e `shouldRegisterNavigation()` (os dois, `.ai/rules/filament.md`) para sumir do menu, do hub, do ⌘K e do catálogo do dock. A rota continua registrada e o Shield continua gerando as permissions | RQ-05 pede 404; a rule `filament.md` descreve 403 para Page escondida por config — aqui o pedido é explícito e o trait é o ponto em que o Filament já decide | difícil de reverter | sessão, 2026-10-19 |
| D3 | O render hook do chat sai do `AppPanelProvider` e entra em `ConfiguraFilamentGlobal::configuraChatDoAssistente()`, sem `scopes` (como o botão "voltar ao topo"): uma Closure, por request, decide pelo painel corrente e pela tela de autenticação. Três cópias do hook, uma por provider, divergiriam | P-01 | difícil de reverter | sessão, 2026-10-19 |
| D4 | Interruptor + lista, com a semântica do dock: lista vazia = nenhum painel. O select só aparece com o interruptor ligado (como o do dashboard dinâmico) e a lista gravada não é apagada ao desligar — o interruptor já a torna inerte | RQ-08, Q4 | difícil de reverter | sessão, 2026-10-19 |
| D5 | Widgets do catálogo somem por `fonteDeDadosDisponivel()` (o hook do trait), nunca por `canView()` sobrescrito | P-02 | difícil de reverter | sessão, 2026-10-19 |
| D6 | A guarda do chat entra em `assertContexto()`, entre a checagem de usuário (403) e a de posse (404): `abort(404)` com `warning` no canal `ai` quando `AgentesDeIa::assistenteNoPainel(Filament::getCurrentPanel())` é falso — mesma resposta da conversa alheia | P-03 | difícil de reverter | sessão, 2026-10-19 |
| D7 | A suíte continua medindo o kit como ele nasce (desligado; `phpunit.xml` não muda); um helper `ligarAgentesDeIa(bool $ligado = true, array $paineis = [])` em `tests/Pest.php` (usado por mais de um arquivo — `.ai/rules/testes.md`) liga no arranjo dos casos do lote T0 | convenção do dock e do dashboard dinâmico | difícil de reverter | sessão, 2026-10-19 |
| D8 | `KIT_AGENTES_IA` lido por `filter_var(..., FILTER_VALIDATE_BOOLEAN)` (falha fechado) e `KIT_AGENTES_IA_PAINEIS` com o mesmo parse de `KIT_DOCK_PAINEIS` | `.ai/rules/config.md` | difícil de reverter | sessão, 2026-10-19 |
| D9 | Sem channel de log novo: o settings já loga em `configuracoes` (salvar, alinhar) e o chat em `ai`; a recusa do catálogo (404) e a do chat logam `warning` em `ai`. O decisor não loga nada no caminho quente — zero linhas por request, como o dock | padrão de log da skill (channel existente → reutilizar) | difícil de reverter | sessão, 2026-10-19 |
| D10 | O `phpunit.xml` fixa `KIT_AGENTES_IA=false` e `KIT_AGENTES_IA_PAINEIS=` com `force="true"`, como as outras chaves `KIT_*` que mudam comportamento — a suíte mede o kit, não o `.env` local; os CT do `04` fixam o estado explicitamente e não dependem disso | Q5 (era Q?1 do `04`) | difícil de reverter | sessão, 2026-10-19 |
| D11 | `MEDIR_PILHA_FLUTUANTE` e `abrirPainelDaPilha()` saem de `tests/Browser/PilhaDeBotoesFlutuantesTest.php` para `tests/Pest.php` (helper usado por dois arquivos, `.ai/rules/testes.md`; o clone com outro nome é proibido) | Q7 (era Q?3 do `04`) | difícil de reverter | sessão, 2026-10-19 |

## Autorização

- **Policies**: nenhuma nova; `AgenteIaPolicy` e `ViewAny:AgenteIa` continuam decidindo quem abre o catálogo **quando ele está ligado**
- **Gates**: nenhum
- **Middleware**: nenhum; a decisão é por request, dentro de `authorizeResourceAccess()` e de `assertContexto()`
- **Guards**: `web`, inalterado
- A tela de configurações continua sob `View:ConfiguracoesDoKit`

## Rotas

| Método | URI | Name | Middleware |
|--------|-----|------|------------|
| GET | `/admin/agentes-ia`, `/admin/agentes-ia/create`, `/admin/agentes-ia/{record}/edit` | `filament.admin.resources.agentes-ia.*` (existentes) | as do painel; com o uso desligado respondem **404** |

Nenhuma rota nova. O chat não tem rota: é componente Livewire montado por render hook.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Configurações da aplicação → aba Aplicação → seção "Agentes de IA" (toggle `agentes_ia_habilitado`, select múltiplo `agentes_ia_paineis`) | Filament (SettingsPage) | `/admin/configuracoes-da-aplicacao?tab=aplicacao` | liga o uso, escolhe os painéis, salva | Não (o select só aparece com o toggle ligado — `visible()` por Livewire) |
| Catálogo "Agentes de IA" (lista, criar, editar) — item do menu, cartão do hub, resultados do ⌘K | Filament (Resource) | `/admin/agentes-ia` | abre o catálogo; com o uso desligado não o vê e recebe 404 | Não |
| Chat do Assistente (botão flutuante + slide-over) | Livewire | toda tela dos painéis da lista | abre o chat e conversa; some nos painéis fora da lista | Sim (pilha de botões flutuantes, slide-over) |
| Widgets "Agentes de IA" e "Agentes por provedor" | Filament (Widget) | `/admin` (dashboard) | vê as contagens; somem com o uso desligado | Não |

**Gate de CT-B**: a única afirmação que só o navegador prova é a pilha de botões flutuantes com o chat
num painel que nunca o teve (`/admin`, `/infra`): posição e sobreposição com o dock e o "voltar ao topo".
O `04` decide a costura.

**Gate de tela de escrita**: a seção nova da tela de configurações grava (toggle + lista) — cenário de
gravação por componente obrigatório no `04`, inclusive os inválidos (lista como string, painel fora das
opções).

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_AGENTES_IA` | `false` | semeia `agentes_ia_habilitado`; liga o uso dos agentes (catálogo no `/admin` e chat nos painéis da lista) |
| `KIT_AGENTES_IA_PAINEIS` | vazio (= nenhum) | ids separados por vírgula (`admin,app,infra`); semeia `agentes_ia_paineis` — em quais painéis o chat do Assistente aparece |

Depois de semeado, o banco vence (tela de configurações). `AI_PROVIDER` e as chaves de provedor não
mudam.

## Eventos / Listeners / Observers

- **Eventos emitidos**: nenhum
- **Listeners**: `AuditarConfiguracoesDoKit` (existente) passa a registrar as duas propriedades novas na trilha, sem alteração
- **Observers**: nenhum

## Jobs / Queues

- Nenhum

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | 1 por tela; o chat continua sendo 1 request por mensagem (two-phase do componente, inalterado) |
| O que é adiado, e por qual gatilho? | nada novo; a aba Aplicação já é `deferLoading()` |
| O que é memoizado **por request**? | nada: as duas chaves são leituras de `config()` |
| O que é cacheado **entre** requests? | o próprio settings, pelo `SETTINGS_CACHE_ENABLED` já existente (limpo na migration) |
| Custo do caminho principal | **zero consultas** a mais por request para decidir se o catálogo, os widgets ou o chat aparecem: `config('kit.agentes_ia.*')` já vem sobreposta pelo banco no boot. A migration semeia as duas linhas no grupo `kit`, carregado na mesma query de sempre |

## Impacto em Features Existentes

- **Chat do Assistente no `/app`**: deixa de aparecer por default. Instalação que atualiza e quer o chat de volta liga o uso e marca o `/app` na tela de configurações (ou no `.env` antes da migration de settings). Documentado no CHANGELOG ("Atualizando") e no site
- **Dock de atalhos**: favorito que aponta para o catálogo vira favorito oculto com o uso desligado (comportamento já existente para destino sem `canAccess()`); o catálogo do slideover deixa de oferecê-lo
- **Hub do `/admin` e ⌘K**: o cartão e os resultados do catálogo somem com o uso desligado (já filtram por `canAccess()`/`shouldRegisterNavigation()`)
- **Pilha de botões flutuantes**: o chat passa a poder existir no `/admin` e no `/infra`, ao lado do dock e do "voltar ao topo"; a faixa de colisão com o dock já detecta o chat pelo DOM, e o recuo do "voltar ao topo" passa a seguir a presença do chat em vez do id do painel (passo 7)
- **Suíte**: os casos do lote T0 ligam o uso no arranjo; `KitInfoTest` CT-06/CT-07 e `ConfiguracoesDoKitTest` iteram o mapa e absorvem as duas propriedades sem edição
- **`kit:info`**: imprime as duas propriedades novas (itera o mapa)
- **`kit:update`**: nada a acrescentar em `CAMINHOS_DO_KIT`

## Rollback

- **Migration down**: `delete('agentes_ia_habilitado')` e `delete('agentes_ia_paineis')` no grupo `kit`, com limpeza do cache do repositório; a config volta a ler o `.env`
- **Feature flag**: o próprio interruptor — desligar devolve o estado de fábrica; não há dado a reverter
- **Reversão de código**: `git revert` do PR devolve o chat fixo ao `/app`

## Dependências

- **Composer**: nenhuma nova
- **NPM**: nenhuma

## Riscos

- **Chat some do `/app` em quem atualiza**: mitigado por nota no CHANGELOG e no site, com o comando/tela para religar
- **404 numa tela com permissão confunde o administrador**: mitigado pelo `helperText` do interruptor ("desligado, o catálogo some do menu e a URL responde 404") e pela nota nas docs
- **Lista parcial em tela aberta**: desligar com o chat aberto → próxima mensagem 404 (P-03); o aviso do Livewire é o mesmo da conversa alheia

## Channel de Log da Feature

### Verificação de Channel Existente

- `config/logging.php` já tem `configuracoes` (settings: alinhar, salvar, trilha) e `ai` (toda a camada de IA, inclusive o chat); os dois leem `LOG_KIT_DRIVER`, que o `phpunit.xml` fixa em `monolog`

### Decisão

- **Channel existe**: reutilizar — `Log::channel('configuracoes')` nas gravações do settings (já acontece pelo `afterSave()` e pelo listener de trilha) e `Log::channel('ai')` nas duas recusas novas (catálogo e chat). Sem channel novo: a feature não tem caminho de execução próprio que justifique um arquivo só dela (D9)

## Estrutura de Implementação

### 1. Config `kit.agentes_ia` e `.env.example`

> Skills: `laravel-best-practices`

- **Path**: `config/kit.php` (bloco novo, logo depois de `'dock'`), `.env.example` (depois de `# KIT_DOCK_PAINEIS=`)
- `'agentes_ia' => ['habilitado' => filter_var(env('KIT_AGENTES_IA', false), FILTER_VALIDATE_BOOLEAN), 'paineis' => array_values(array_filter(array_map('trim', explode(',', (string) env('KIT_AGENTES_IA_PAINEIS')))))]` — sem default de texto no `env()`, como o dock: `tests/Kit/TextoDoEnvTest.php:'nao reintroduz env com default de texto':69` reprova `env('X', '')` *(alterado em 2026-10-19: revisão profunda)*
- Comentário de bloco no padrão dos vizinhos: o que liga, onde é editável, "VAZIO significa NENHUM painel", e que o decisor é `App\Support\AgentesDeIa`
- `.env.example`: `KIT_AGENTES_IA=false` e `# KIT_AGENTES_IA_PAINEIS=` com o comentário de três linhas
- **Atende**: RQ-03, RQ-07, RQ-08, P-05
- **Logs**: nenhum (arquivo de configuração)

### 2. Settings: duas propriedades e a migration

> Skills: `laravel-best-practices`

- **Path**: `app/Settings/ConfiguracoesDoKit.php`; `database/settings/2026_10_19_100000_add_agentes_ia_to_kit_settings.php` (nova)
- Propriedades, no bloco `// Kit`, depois de `$dock_estilo`: `public bool $agentes_ia_habilitado;` e `/** @var array<int, string> */ public array $agentes_ia_paineis;`, com docblock curto: nascem desligadas, vazio = nenhum painel, lidas por request em `App\Support\AgentesDeIa`
- Mapa: `'agentes_ia_habilitado' => 'kit.agentes_ia.habilitado'`, `'agentes_ia_paineis' => 'kit.agentes_ia.paineis'`, com o comentário de que o decisor lê `config()` já sobreposta
- Migration no molde da do dock: `add('agentes_ia_habilitado', (bool) config('kit.agentes_ia.habilitado', false))`, `add('agentes_ia_paineis', array_values((array) config('kit.agentes_ia.paineis', [])))`; `down()` com os dois `delete()`; `limparCacheDoRepositorio()` nos dois sentidos
- **Atende**: RQ-01, RQ-02, RQ-03, RQ-07, P-05
- **Logs**: nenhum novo — `aplicarNaConfig()` já loga `debug` em `configuracoes` com a lista de chaves, que passa a incluir as duas

### 3. Decisor `App\Support\AgentesDeIa`

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Support/AgentesDeIa.php` (nova, `final class`)
- `public static function habilitado(): bool` → `(bool) config('kit.agentes_ia.habilitado', false)`
- `public static function assistenteNoPainel(?Panel $painel): bool` → `$painel !== null && self::habilitado() && in_array($painel->getId(), (array) config('kit.agentes_ia.paineis', []), true)` — uma linha, como `DockDeAtalhos::habilitadoNoPainel()` *(alterado em 2026-10-19: Ponytail, sem helper `paineis()`)*
- `public static function chatNaTela(): bool` → `self::assistenteNoPainel(Filament::getCurrentPanel()) && ! TelaDeAutenticacao::corrente()` — a condição ÚNICA de "o chat está nesta tela", consumida pelo render hook e pelo recuo do "voltar ao topo" *(alterado em 2026-10-19: revisão profunda — a blade decidia o recuo pelo id do painel)*
- Docblock: única leitura de `kit.agentes_ia.*`; zero consulta ao banco; vazio = nenhum (o oposto do dashboard dinâmico, igual ao dock)
- **Atende**: RQ-01, RQ-02, RQ-08
- **Logs**: nenhum — caminho quente, uma chamada por item de menu, por widget e por render do chat

### 4. Tela de configurações: seção "Agentes de IA"

> Skills: `filament-development`

- **Path**: `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`, em `abaAplicacao()`, nova seção entre "Dock de atalhos" e "Organizações": `$this->secao('aplicacao', 'agentes-ia', 'Agentes de IA', 'heroicon-o-cpu-chip')->description(...)->columnSpanFull()`
- `Toggle::make('agentes_ia_habilitado')->label('Usar agentes de IA')->helperText('Desligado (padrão), o catálogo "Agentes de IA" some do menu do /admin e a URL dele responde 404, mesmo para quem tem a permissão; o chat do Assistente não aparece em painel nenhum. Ligado, o catálogo volta e o chat aparece nos painéis escolhidos abaixo.')->live()`
- `Select::make('agentes_ia_paineis')->label('Painéis onde o chat do Assistente aparece')->helperText('Em branco = nenhum painel, como no dock. Escolha os painéis em que o botão do chat deve aparecer. O catálogo continua só no /admin.')->multiple()->rule('array')->options(Paineis::opcoes())->visible(fn (Get $get): bool => (bool) $get('agentes_ia_habilitado'))`
- Sem `mutateFormDataBeforeSave()` novo: o select oculto não é desidratado e a lista gravada fica (D4)
- **Atende**: RQ-01, RQ-02, RQ-03, RQ-08
- **Logs**: `afterSave()` já registra `info` em `configuracoes` com `user_id` e os campos; nada a acrescentar

### 5. Catálogo: some do menu e responde 404

> Skills: `filament-development`, `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/AgentesIa/AgenteIaResource.php`; `app/Filament/Concerns/ExigeAgentesDeIaLigados.php` (nova); as três Pages em `app/Filament/Admin/Resources/AgentesIa/Pages/`
- Resource: `public static function shouldRegisterNavigation(): bool { return AgentesDeIa::habilitado() && parent::shouldRegisterNavigation(); }` e `public static function canAccess(): bool { return AgentesDeIa::habilitado() && parent::canAccess(); }` — o `parent::` mantém a policy (sem ele, `CanAuthorizeResourceAccess` deixaria de consultar `ViewAny`)
- Trait: `public static function authorizeResourceAccess(): void { if (! AgentesDeIa::habilitado()) { Log::channel('ai')->warning('[ExigeAgentesDeIaLigados@authorizeResourceAccess] Catálogo recusado com o uso de agentes desligado | pagina: '.static::class, ['user_id' => auth()->id(), 'pagina' => static::class, 'motivo' => 'agentes_ia_desligado']); abort(404); } parent::authorizeResourceAccess(); }` — `use ExigeAgentesDeIaLigados;` em `ListAgentesIa`, `CreateAgenteIa` e `EditAgenteIa`
- Uma frase no docblock existente do Resource: "ligado pelo settings; desligado some do menu e responde 404 (wiki `agentes-ia-no-settings`)" *(alterado em 2026-10-19: Ponytail)*
- O trait sobrescreve também `authorizeAccess()` (`static::authorizeResourceAccess()` antes do `parent::`): o `mount()` de `CreateRecord`/`EditRecord` confere `canCreate()`/`canEdit()` (403) antes de o Livewire rodar o hook do trait, e sem isto quem não tem a permissão recebia 403 com o uso desligado; `ListRecords` declara o método vazio, então o `parent::` existe nas três *(alterado em 2026-10-19: causa (b) do lote A, CT-11)*
- O trait vale também no `hydrate` de toda Page do Resource (`CanAuthorizeResourceAccess::hydrateCanAuthorizeResourceAccess()`): salvar numa tela já aberta depois de desligar responde 404 e nada grava (P-06) *(alterado em 2026-10-19: Q6 do `04`)*
- **Atende**: RQ-04, RQ-05, RQ-06, P-04, P-06
- **Logs**: o `warning` acima, em `ai`, só na recusa (sem log por abertura normal)

### 6. Widgets do catálogo seguem o interruptor

> Skills: `filament-development`

- **Path**: `app/Filament/Admin/Widgets/AgentesIaStats.php`, `app/Filament/Admin/Widgets/AgentesIaPorProvider.php`
- Em cada um: `protected static function fonteDeDadosDisponivel(): bool { return AgentesDeIa::habilitado(); }` com uma linha de comentário (RQ-09; o hook do trait, não `canView()`) *(alterado em 2026-10-19: Adendo 1 — o pedido do solicitante confirmou a P-02)*
- **Atende**: RQ-09
- **Logs**: nenhum

### 7. Chat do Assistente por painel

> Skills: `filament-development`, `laravel-best-practices`

- **Path**: `app/Providers/Concerns/ConfiguraFilamentGlobal.php`, `app/Providers/Filament/AppPanelProvider.php`, `app/Livewire/AssistenteChatWidget.php`
- `ConfiguraFilamentGlobal::configuraChatDoAssistente()`: `FilamentView::registerRenderHook(PanelsRenderHook::BODY_END, fn (): string => AgentesDeIa::chatNaTela() ? Blade::render('@livewire(\'assistente-chat-widget\')') : '')`, chamado em `configuraFilamentGlobal()` ao lado de `configuraBotaoVoltarAoTopo()`; docblock explica o "sem `scopes`" e a decisão por request
- `resources/views/filament/voltar-ao-topo.blade.php`: `$recuoDoBotao = AgentesDeIa::chatNaTela() ? 'bottom-24' : 'bottom-6'` no lugar da comparação com `'app'` — o recuo existe por causa do chat, então segue o chat (comentário da blade e docblock de `VoltarAoTopoTest` atualizados) *(alterado em 2026-10-19: revisão profunda)*
- `AppPanelProvider`: remover o `->renderHook(PanelsRenderHook::BODY_END, ...)` do chat e o comentário; deixar uma linha apontando para o hook global
- `AssistenteChatWidget::assertContexto()`: depois do `abort_unless($user instanceof User, 403)`, `if (! AgentesDeIa::assistenteNoPainel(Filament::getCurrentPanel())) { Log::channel('ai')->warning('[AssistenteChatWidget@assertContexto] Chat recusado com o uso de agentes desligado no painel | painel: '.(Filament::getCurrentPanel()?->getId() ?? 'nenhum'), ['user_id' => $user->id, 'painel' => Filament::getCurrentPanel()?->getId(), 'motivo' => 'agentes_ia_desligado']); abort(404); }` — o docblock do método ganha a terceira condição
- O docblock da classe ("embarcada em todas as telas do painel via render hook ... registrado no PanelProvider") passa a dizer: hook global, painéis da lista do settings
- **Atende**: RQ-02, RQ-08, P-01, P-03
- **Logs**: o `warning` acima, em `ai`, só na recusa; os logs `debug`/`info` existentes do widget não mudam

### 8. Lote T0: suíte com o uso desligado e o helper de teste

> Skills: `pest-testing`

- **Path**: `tests/Pest.php` (helper `ligarAgentesDeIa(bool $ligado = true, array $paineis = []): void` → `config()->set('kit.agentes_ia.habilitado', $ligado); config()->set('kit.agentes_ia.paineis', $paineis);`, ao lado de `ligarDock()`), e os arquivos de `### Testes que dependem do estado atual`
- Catálogo: `ligarAgentesDeIa()` no `beforeEach` ou no arranjo de `AgenteIaResourceTest`, `PaginasInfraTest` (bloco do admin), `PermissoesDeResourcesTest`, `CarregamentoContinuoTest`, `VisoesDeTabelaTest`, `TelasDoKitTest` (lote `telasDoKit()` do admin e o formulário do agente)
- Contagem fixa: `tests/Kit/KitInfoTest.php:toHaveCount():191` passa a 71, com a linha de histórico `69 → 71 na feature agentes-ia-no-settings` *(alterado em 2026-10-19: revisão profunda, achado do Codex)*
- Recuo do "voltar ao topo": `tests/Kit/VoltarAoTopoTest.php:'levanta o botão no /app':64` e `:'mantém o botão na posição padrão':70` ligam o chat no `/app` (`ligarAgentesDeIa(true, ['app'])`) e o docblock deixa de dizer "o chat só está no /app"; `tests/Kit/AssistenteChatCssTest.php:'ana /app':141` idem, e o CT-01 do mesmo arquivo (GET do `/app` esperando a raiz do chat) *(alterado em 2026-10-19: revisão profunda; CT-01 achado pelo lote T0 vermelho)*
- Chat: `ligarAgentesDeIa(true, ['app'])` em `TelasSemDockNemChatTest` CT-04/CT-09 (CT-11 só mede o dock, sem mudança), `TelasSemDockNemChatTenancyTest` CT-10, `AssistenteChatWidgetTest` nos casos que chamam action (CT-16, CT-22, CT-24, CT-25, CT-26; CT-15 só renderiza e não passa por `assertContexto()`), com `Filament::setCurrentPanel('app')` onde o componente é testado direto, `AssistenteChatEstiloTest`, `PilhaDeBotoesFlutuantesTest` (o helper `abrirPainelDaPilha()` ou o arranjo); `BoasVindasTest` só cita o hook do `AppPanelProvider` em comentário, que é corrigido *(alterado em 2026-10-19: revisão profunda)*
- `phpunit.xml`: `<env name="KIT_AGENTES_IA" value="false" force="true"/>` e `<env name="KIT_AGENTES_IA_PAINEIS" value="" force="true"/>` no bloco das chaves `KIT_*` (D10)
- `MEDIR_PILHA_FLUTUANTE` e `abrirPainelDaPilha()` movidos de `tests/Browser/PilhaDeBotoesFlutuantesTest.php` para `tests/Pest.php` (D11), sem clone
- Nenhum caso novo aqui: os cenários da feature nascem no `04`
- **Atende**: RQ-06, RQ-07
- **Logs**: nenhum

### 9. Documentação

> Skills: —

- **Path**: `docs/pt/recursos/configuracoes-do-kit.md` e `docs/en/recursos/configuracoes-do-kit.md` (linha da aba Aplicação na tabela e seção nova "Agentes de IA: um interruptor e os painéis do chat", ao lado da do dock); `docs/pt/comecar/atualizando-o-projeto.md` e `docs/en/...` (nota da v0.51.0: rodar `migrate`; o chat do `/app` volta só ligando); `wikis/ia.md` (seção "Superfícies no painel": o interruptor e a lista); `README.md` e `README.en.md` (bullet da camada de IA em "O que já vem pronto": desligada por default); `CHANGELOG.md` (`[Unreleased]` → Adicionado/Alterado/Atualizando)
- **Atende**: RQ-01, RQ-02, RQ-07 (documentação do comportamento entregue)
- **Logs**: nenhum

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> Cada passo deve aplicar a escada de simplicidade:
> 1. Reutilizar código existente antes de criar novo
> 2. Usar stdlib do PHP/Laravel antes de código custom
> 3. Usar features nativas antes de dependências
> 4. Uma linha quando possível
> 5. Mínimo código que funciona
>
> Atalhos deliberados devem ser marcados com `ponytail:` comment.
> Após implementação, rodar `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em modo `ultra`** (padrão) na comunicação agent ↔ usuário.
> Arquivos wiki (00-06) são boundary do Caveman — escrever em prosa normal.
> Código, commits e PRs também são boundary do Caveman.
>
> **Baseline antes do primeiro commit**: rodar a suíte completa em `main` e listar por nome as
> falhas pré-existentes. A `## Verificação Final` compara contra a baseline, não contra zero.

## Mapeamentos

| Propriedade do settings | Chave de `config()` | `.env` (semente) | Quem lê |
|---|---|---|---|
| `agentes_ia_habilitado` (bool) | `kit.agentes_ia.habilitado` | `KIT_AGENTES_IA` | `AgentesDeIa::habilitado()` |
| `agentes_ia_paineis` (array) | `kit.agentes_ia.paineis` | `KIT_AGENTES_IA_PAINEIS` | `AgentesDeIa::assistenteNoPainel()` |

## Testes

> Ver `04-casos-de-teste.md` para especificação completa dos cenários de backend.
> Ver `05-casos-de-teste-browser.md` para os cenários de UI (se a costura `browser` for confirmada).

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/filacheck --fix`
- [ ] `vendor/bin/pest tests/Kit/AgentesIaNoSettingsTest.php --compact` (CTs de backend)
- [ ] `vendor/bin/pest tests/Browser --filter=AgentesIaNoSettings` (CT-B — só se houver `05-*-browser.md`)
- [ ] `php artisan test --testsuite=Kit,Tenancy --parallel` — comparado à **baseline** de `main`
- [ ] `pest --mutate --path=app/Support/AgentesDeIa.php` — score, **duração** e lista de sobreviventes (no Windows, amostragem manual)
- [ ] **Custo medido** — consultas de `GET /admin` com o uso ligado × desligado, contra o `## Modelo de Execução`
- [ ] **`/code-review high main...HEAD` + passe de eixos (step 9)** — antes da reconciliação
- [ ] `php artisan kit:info` lista as duas propriedades novas

## Commits
- `:sparkles: agentes-ia: interruptor e painéis do chat do Assistente no settings`
- `:white_check_mark: agentes-ia: casos de teste do interruptor, do 404 e do chat por painel`
- `:memo: agentes-ia: docs pt/en, wikis/ia.md, READMEs e CHANGELOG`
- `:memo: wiki(agentes-ia-no-settings): requisito, plano, ADR, progresso e casos de teste`

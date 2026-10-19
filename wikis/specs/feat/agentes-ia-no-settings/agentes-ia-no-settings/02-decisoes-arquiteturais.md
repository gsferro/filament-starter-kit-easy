# Decisões Arquiteturais — Agentes de IA ligados pelo settings, por painel

<!-- ADR só quando as três valem: difícil de reverter, surpreendente sem contexto, resultado de
     trade-off real. Falta uma → sem ADR; a decisão vira linha em ## Decisões de Desenho do 01. -->

Nenhuma decisão passou nos três portões. As onze decisões de desenho (D1 a D11; D10 e D11 nasceram no step 7) estão em
`## Decisões de Desenho` do `01-plano-acao.md`; todas são reversíveis num commit — o decisor, o trait
do 404, o hook global do chat, a semântica da lista e a guarda do chat seguem precedentes já escritos no
kit (dock de atalhos, dashboard dinâmico, botão "voltar ao topo", `ExigePermissaoDoWidget`).

## Superfície Livewire

Tudo o que o cliente alcança entre requests nas superfícies que a feature toca. Greps do step 3
(`references/pesquisa-step-3.md`), colados e conferidos; **re-varridos sobre o código final em 2026-10-19 (pré-step 9)**: nenhuma `public $` nova; o único `public function` de instância novo é o `hydrate()` do chat, acrescentado pelo step 9 (RD-01) e listado abaixo *(alterado em 2026-10-19: QA-02)*; `authorizeAccess()` do trait é `protected` (não alcançável por `$wire.`); os demais métodos novos (`AgenteIaResource::canAccess()`/`shouldRegisterNavigation()`, `ExigeAgentesDeIaLigados::authorizeResourceAccess()`, `AgentesDeIa::*`) são estáticos; as linhas do chat deslocaram e foram atualizadas.

### O código do projeto

| Ponto de entrada | Alcançável por | Fronteira aplicada pelo projeto | Evidência |
|---|---|---|---|
| `AssistenteChatWidget::enviar()` | `$wire.enviar()` em qualquer tela com o chat montado | `assertContexto()`: 403 sem usuário; **404 com o uso desligado no painel corrente (novo, P-03)**; validação `required\|string\|max:2000` | `app/Livewire/AssistenteChatWidget.php:enviar():72` |
| `AssistenteChatWidget::responder()` | `$wire.responder()` direto, sem `enviar()` | no-op sem `mensagemPendente` ou acima de 2000; depois `assertContexto()` (403 / **404 novo** / 404 posse) | `app/Livewire/AssistenteChatWidget.php:responder():94` |
| `AssistenteChatWidget::renomearConversa(string $id, string $titulo)` | `$wire.renomearConversa(id, titulo)` | `assertContexto($id)`: posse da conversa; **404 novo** com o uso desligado | `app/Livewire/AssistenteChatWidget.php:renomearConversa():169` |
| `AssistenteChatWidget::novaConversa()` | `$wire.novaConversa()` | `assertContexto()`; **404 novo** | `app/Livewire/AssistenteChatWidget.php:novaConversa():189` |
| `AssistenteChatWidget::retomarConversa(string $id)` | `$wire.retomarConversa(id)` | `assertContexto($id)`; **404 novo** | `app/Livewire/AssistenteChatWidget.php:retomarConversa():200` |
| `public bool $aberto` | `$wire.set('aberto', …)` | só estado de UI (abre/fecha o slide-over); nada decide por ele | `app/Livewire/AssistenteChatWidget.php:$aberto:41` |
| `public string $mensagem` | `$wire.set('mensagem', …)` | validada em `enviar()` | `app/Livewire/AssistenteChatWidget.php:$mensagem:51` |
| `public ?string $mensagemPendente` | `$wire.set('mensagemPendente', …)` | revalidada em `responder()` (nulo ou > 2000 = no-op) | `app/Livewire/AssistenteChatWidget.php:$mensagemPendente:54` |
| `public ?string $conversaId` | — | `#[Locked]` | `app/Livewire/AssistenteChatWidget.php:$conversaId:57` |
| `public bool $indisponivel`, `public ?string $recusa` | `$wire.set(...)` | só exibição; sobrescritos pelo próprio fluxo | `app/Livewire/AssistenteChatWidget.php:$indisponivel:59`, `:$recusa:66` |
| `ConfiguracoesDoKit::$data` (SettingsPage) — chaves novas `agentes_ia_habilitado`, `agentes_ia_paineis` | `$wire.set('data.configuracoes.aplicacao.agentes_ia_paineis', …)` e `save()` | `Toggle` (boolean) e `Select` múltiplo com `rule('array')` + `in` das opções (`Paineis::opcoes()`); settings tipado (`bool`, `array`) recusa o resto com `TypeError` antes de gravar | `vendor/filament/spatie-laravel-settings-plugin/src/Pages/SettingsPage.php:$data:33` |
| `AssistenteChatWidget::hydrate()` (novo no step 9, RD-01) | todo update Livewire do componente já montado — `$refresh`, `$wire.set(...)`, qualquer action | `exigirUsoNoPainel()`: 404 + `warning` com o uso desligado no painel corrente; sem usuário, nada a guardar (o render entrega vazio) | `app/Livewire/AssistenteChatWidget.php:hydrate():287` |
| `ListAgentesIa`, `CreateAgenteIa`, `EditAgenteIa` — `authorizeAccess()` sobrescrito pelo trait (`protected`, só o `mount()` do vendor o chama) | `mount()` de criar/editar, antes do hook do vendor | `exigirUsoDeAgentesLigado()` (404) antes do 403 por permissão (CR-02/CT-11) | `app/Filament/Concerns/ExigeAgentesDeIaLigados.php:authorizeAccess():43` |
| `ListAgentesIa`, `CreateAgenteIa`, `EditAgenteIa` — `mountCanAuthorizeResourceAccess()` / `hydrateCanAuthorizeResourceAccess()` | todo mount e todo update Livewire das três Pages | `authorizeResourceAccess()` sobrescrito pelo trait: **404 com o uso desligado (novo)**, depois o 403 do vendor por permissão | `vendor/filament/filament/src/Resources/Pages/Concerns/CanAuthorizeResourceAccess.php:mountCanAuthorizeResourceAccess():7`, `:hydrateCanAuthorizeResourceAccess():12` |

Métodos estáticos novos (`AgenteIaResource::canAccess()`, `::shouldRegisterNavigation()`,
`ExigeAgentesDeIaLigados::authorizeResourceAccess()`, `AgentesDeIa::*`) não são ações chamáveis por
`$wire.` — o Livewire expõe só métodos públicos de instância.

### O framework

Nenhum array de estado do framework (`$filters`, `$pageFilters`, `$tableFilters`, `$tableSearch`,
`$tableSortColumn`) é consumido pelo código desta feature.

### O pacote de terceiro

A feature não monta sobre pacote novo. O chat usa `laravel/ai` (conversas), cuja fronteira de posse já
está em `assertContexto()`; a feature só acrescenta a condição do painel antes dela.

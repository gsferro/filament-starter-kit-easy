# Casos de Teste — agentes-ia-no-settings: Agentes de IA ligados pelo settings, por painel

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · Superfície Livewire: `02-decisoes-arquiteturais.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação (a feature não existe).
> Revisão adversarial: feita em 2026-10-19 — 22 achados aplicados, 1 rejeitado (ADV-12). Mutantes trazidos pela revisão (M55–M67) não contam para o teto por regra (SKILL.md, passo 6, item 1).

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Configuração (fábrica, `.env`, banco, tela, decisão por request) | 2 | 2 | 4 | padrão |
| Catálogo "Agentes de IA" (navegação, 404, permissão) | 2 | 3 | 6 | padrão |
| Chat do Assistente por painel (render e ações) | 3 | 3 | 9 | completo |
| Widgets do catálogo no dashboard | 1 | 1 | 1 | mínimo |
| Pilha de botões flutuantes com o chat em painel novo (browser) | 2 | 1 | 2 | mínimo |

- Probabilidade 3 no Chat: a decisão combina interruptor × lista × painel corrente × tela de autenticação, e o chat passa de um painel para três. Impacto 3 no Chat: com o uso desligado, a pergunta do usuário não pode chegar ao provedor de IA (dado de terceiro saindo da instalação). Impacto 3 no Catálogo: regra de autorização (404 mesmo com permissão)
- Técnicas aplicadas: EP, tabela de decisão completa (Chat), matriz papel × ação, tabela estado × operação (uma, 24 células), rastreio de efeito, 2-switch
- Adendo 1: a P-02 foi promovida a RQ-09; a R11 tem origem RQ-09 e o CT-20 é o cenário dela
- Técnica escalada: R5 (Catálogo, padrão) usa **2-switch** no favorito do dock, porque o defeito plausível é o favorito apagado ao desligar, que só aparece no religar
- Cenários: 26 · Regras: 13 · Mutantes previstos: 67 · Sem matador: 0
<!-- derivado por grep -c (template-04 §Contagem do cabeçalho). O CT-B01 e seus mutantes estão no 05. -->

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | duas propriedades novas no settings do kit (bool, lista), chaves `kit.agentes_ia.*`, migration de settings, `.env.example`, seção nova na tela de configurações, guarda no Resource do catálogo e nas três Pages, guarda nas ações do chat, render do chat em todos os painéis, dois widgets | CT-01, CT-02, CT-07 |
| F | ligar/desligar o uso; escolher painéis do chat; esconder o catálogo de toda navegação; 404 nas três URLs; esconder widgets; recusar toda ação do chat; trilha `warning` no canal `ai` (P-03) | CT-09…CT-19 |
| D | bool e lista de ids de painel; lista vazia (= nenhum), id inexistente gravado, lista como texto, toggle como lista, chave ausente/nula na config; dado já existente que não pode sumir (agentes do catálogo, favoritos do dock, lista gravada ao desligar, conversas) | CT-03, CT-07, CT-08, CT-10, CT-16, CT-18, CT-20 |
| I | rotas `/admin/agentes-ia` (lista, criar, editar); barra lateral, hub de cartões, busca ⌘K (telas, registros, "Criar agente"), catálogo do dock; render hook do chat; cinco ações `$wire.` do chat; formulário Livewire da SettingsPage; `.env` → migration → banco → config no boot | CT-09, CT-11, CT-13, CT-15, CT-16, CT-18 |
| P | painéis `admin`, `app`, `infra` com tema próprio cada um (o chat nunca foi desenhado em `/admin` nem `/infra`); navegador para a pilha de botões flutuantes; `.env` local do desenvolvedor que a suíte lê (Q5: o `phpunit.xml` passa a fixar as duas chaves); multi-organização ligada (suíte Tenancy) | CT-B01 (05), CT-01, CT-23 |
| O | instalação nova; projeto atualizado sem o bloco novo na config; administrador com e sem permissão; tela do chat aberta quando o uso é desligado; usuário com papéis nos três painéis | CT-03, CT-11, CT-13, CT-18 |
| T | decisão por request (vale no seguinte, sem deploy); ordem env → banco (banco vence); ciclo desligar → religar; troca de estado entre o `mount` do chat e a ação | CT-04, CT-05, CT-10, CT-18, CT-19, CT-20 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — Instalação nova e `.env` de fábrica nascem com o uso desligado e nenhum painel | Configuração (padrão) | RQ-07, RQ-08, P-05 | EP | CT-01, CT-02 |
| R2 — Sem a chave na config (projeto atualizado sem o bloco), o uso fica desligado | Configuração (padrão) | RQ-07, RQ-08 | EP (discriminante nulo) | CT-03 |
| R3 — O banco vence o `.env`, e a decisão vale por request, sem consulta ao settings pelo código da feature | Configuração (padrão) | RQ-03, P-05 | tabela de decisão (env × banco), 2-switch | CT-04, CT-05, CT-06 |
| R4 — A tela de configurações grava o interruptor e a lista, recusa valor fora do domínio e não perde o que está gravado ao salvar outro campo | Configuração (padrão) | RQ-01, RQ-02, RQ-03, RQ-08, Fora de Escopo ("nada é apagado") | EP, gate de tela de escrita | CT-07, CT-08, CT-25 |
| R5 — Com o uso desligado, o catálogo some de toda superfície de navegação; o favorito do dock vira oculto e volta | Catálogo (padrão) | RQ-04, P-04 | EP por superfície, 2-switch | CT-09, CT-10 |
| R6 — Com o uso desligado, as três URLs do catálogo respondem 404, com e sem permissão; o painel continua abrindo | Catálogo (padrão) | RQ-05 | matriz papel × ação | CT-11, CT-12 |
| R6b — Com o uso desligado depois de uma tela do catálogo já aberta, a próxima ação nela responde 404 e nada é gravado | Catálogo (padrão) | P-06 | tabela estado × evento (mount ligado → desliga → ação), rastreio de efeito (não-efeito) | CT-22 |
| R7 — Com o uso ligado, o catálogo abre e grava como hoje, com o par tem/não-tem permissão, só no `/admin` | Catálogo (padrão) | RQ-06, P-01 | matriz papel × ação, gate de tela de escrita | CT-13, CT-14, CT-15 |
| R8 — O chat aparece só nos painéis da lista, com o uso ligado, e nunca em tela de autenticação | Chat (completo) | RQ-02, RQ-08, P-01; glossário "Tela de autenticação" | tabela de decisão completa | CT-16, CT-17 |
| R9 — Toda ação do chat responde 404 quando o uso é desligado no painel corrente depois do `mount`, sem consultar agente, sem gravar, com um `warning` no canal `ai` | Chat (completo) | P-03 | tabela de decisão (verbo × motivo), rastreio de efeito | CT-18 |
| R10 — "Desligado no painel corrente" cobre lista vazia, id inexistente e painel nulo; com o painel na lista, a ação segue | Chat (completo) | P-03, P-01, RQ-08 | EP + rastreio de efeito (direção "não aconteceu") | CT-19 |
| R11 — O grupo de widgets "Agentes de IA" do dashboard do `/admin` segue o interruptor sem perder dado; as trilhas de IA do `/infra` não mudam | Widgets (mínimo) | RQ-09 (Adendo 1; era P-02), Fora de Escopo (trilhas do `/infra`) | EP, 2-switch | CT-20, CT-21 |
| R12 — No painel que nunca teve o chat, o botão dele entra na pilha de botões flutuantes sem sobrepor | Pilha (mínimo) | P-01 | EP (painel) | CT-B01 (no `05`) |
| R13 — O histórico do chat é da pessoa: aparece em qualquer painel da lista, só o dela, e sobrevive à saída e volta do painel | Chat (completo) | P-07 (Q9), P-01, P-03, Fora de Escopo ("nada é apagado") | EP (painel × dono), 2-switch | CT-23, CT-24, CT-26 |

- Nenhuma `RQ` está com Estado aberto no `00`. Q1–Q3 viraram P-01–P-03; Q4 está `aberta` no `00` **sem premissa própria**, mas as duas metades dela são determinadas por cláusulas fechadas: o catálogo depende só do interruptor (RQ-06) e lista vazia é nenhum painel do chat (RQ-08, P-01). Os cenários dessas metades (CT-13 com lista vazia, CT-16 linhas "vazia") têm origem nessas cláusulas, não em Q4
- RQ-09 (Adendo 1) — coberta: CT-20 (a P-02, que ela substitui, não origina mais cenário)
- RQ-05 — coberta: CT-11, CT-12 (as três URLs, com e sem permissão); o recorte "ação Livewire numa tela do catálogo já aberta" foi decidido pela P-06 (Q6) e é o CT-22 (R6b)

### Matriz estado × operação (única)

Estados da instalação, vistos do painel corrente: **E1** desligado (painel na lista) · **E2** ligado, painel fora da lista (inclui lista vazia) · **E3** ligado, painel na lista. Para as operações do catálogo, o painel corrente é o `/admin`.

**3 estados × 8 operações = 24 células** · válidas: 14 · inválidas: 10 (todas com CT).
Legenda: `❌` = recusa + não-efeito de **cada** efeito do caminho feliz daquela operação; `✅` = operação executada com sucesso e valor afirmado.

| Operação | E1 | E2 | E3 |
|---|---|---|---|
| O1 GET lista do catálogo | ❌ 404 · CT-11 | ✅ 200 · CT-13 (lista vazia) | ✅ 200 · CT-13 (lista `admin`) |
| O2 GET criar | ❌ 404 · CT-11 | ✅ CT-13 | ✅ CT-13 (linha `admin`, criar) |
| O3 GET editar | ❌ 404 · CT-11 | ✅ CT-13 | ✅ CT-13 (linha `admin`, editar) |
| O4 ação Livewire numa tela do catálogo (salvar o formulário, ordenar a lista) | ❌ 404, nenhum agente criado nem alterado · CT-22 (criar, editar e a lista, telas abertas com o uso ligado) | ✅ grava · CT-14 (criar, lista vazia) | ✅ grava · CT-14 (editar, lista `admin`) |
| O5 listar o catálogo na navegação (barra, hub, ⌘K, dock) | ❌ ausente · CT-09, CT-10 | ✅ presente · CT-09 | ✅ presente · CT-09 (barra lateral, lista `admin`) |
| O6 widgets do catálogo no dashboard | ❌ ausentes, agentes intactos · CT-20 | ✅ CT-20 (lista vazia) | ✅ CT-20 (lista `admin`) |
| O7 render do chat no painel | ❌ ausente · CT-16 | ❌ ausente · CT-16 | ✅ presente · CT-16 (dashboard e Page comum), CT-23 (com organizações) |
| O8 ações do chat (5 verbos) | ❌ 404, agente e guardrail não consultados, nada gravado, `conversaId` intacto, 1 `warning` · CT-18 | ❌ idem · CT-18 (o painel sai da lista), CT-19 | ✅ CT-19 (controle, `responder`, no `/app` e no `/admin`), CT-24 (histórico), CT-26 (retomar depois de o `/app` voltar à lista); demais verbos: `tests/Kit/AssistenteChatWidgetTest.php` CT-15/22/24/25, que o lote T0 liga com `['app']` |

Legenda conferida célula a célula: O1–O3 (GET) não têm efeito colateral no caminho feliz, então o `❌` afirma só o 404 e o corpo sem o catálogo; O6 afirma ausência **e** a contagem de agentes; O8 afirma os cinco não-efeitos que as ações produzem no caminho feliz (consulta ao `Assistente`, consulta ao `GuardaPrompt`, conversa/mensagem gravada, título renomeado, `conversaId` trocado) mais a trilha da recusa.

## Costuras de Teste

<!-- Proposta pela derivação em sub-agente; confirmada pela sessão em 2026-10-19 sob convenção existente (nenhuma costura nova). Q?1–Q?4 renumeradas Q5–Q8 no 00/01. -->

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| G1 Configuração e decisão por request | R1, R2, R3 | Pest feature HTTP | existente — molde de `tests/Kit/DockDeAtalhosTest.php` CT-06/CT-07/CT-35 (`kitConfigCom`, `gravarConfiguracao` + `alinharConfiguracoesDoKit`, `consultasDoGet`) e `tests/Kit/DashboardDinamicoTest.php` (toggle por request + `fronteiraDeRequest`); arquivo `tests/Kit/AgentesIaNoSettingsTest.php` | a regra afirma valor efetivo de config, linha gravada no settings e o efeito no request seguinte (status e HTML) | sessão, 2026-10-19 — convenção existente (molde citado), sem decisão material pendente |
| G2 Tela de configurações | R4 | componente Livewire/Filament | existente — `tests/Kit/ConfiguracoesDoKitTelaTest.php` CT-08/CT-09 do dock (`fillForm`/`set` → `save` → `configuracaoGravada`, `chaveDoCampo`); arquivo `tests/Kit/AgentesIaNoSettingsTest.php` | gravação e validação de formulário Filament (gate de tela de escrita) | sessão, 2026-10-19 — convenção existente (molde citado), sem decisão material pendente |
| G3 Catálogo por URL | R6, R7 | Pest feature HTTP | existente — `tests/Kit/AgenteIaResourceTest.php` (par `semAPermissao('admin', 'ViewAny:AgenteIa')` + `noPainelDoShield('admin')` + `get`); gravação do CT-14 por componente (`Livewire::test(CreateAgenteIa::class)`), mesma costura do `AgenteIaResourceTest` | autorização por rota é o observável do requisito (404/403/200); é o cenário "por fora do componente" do gate de camada | sessão, 2026-10-19 — convenção existente (molde citado), sem decisão material pendente |
| G4 Catálogo nas superfícies de navegação | R5 | componente Livewire/Filament | existente — `tests/Kit/HubDeCardsTest.php` (`Livewire::test(HubDeAdministracao::class)`), `tests/Kit/DockDeAtalhosTest.php` (`dockNaPagina`, `opcoesDoSlideover`, `gravarAtalhos`, `atalhosGravados`); ⌘K pelas categorias do kit chamadas direto | elemento de tela/navegação; nenhuma dessas superfícies precisa de JS para provar presença | sessão, 2026-10-19 — convenção existente (molde citado), sem decisão material pendente |
| G5 Chat por painel (render) | R8 | Pest feature HTTP | existente — `tests/Kit/AssistenteChatWidgetTest.php` CT-14 (`get` + `assertDontSeeLivewire` + `data-assistente-chat` fora de `<script>`) | o requisito afirma onde o chat é renderizado: HTML da resposta do painel | sessão, 2026-10-19 — convenção existente (molde citado), sem decisão material pendente |
| G6 Ações do chat | R9, R10, R13 (CT-24, CT-26) | componente Livewire/Filament | existente — `tests/Kit/AssistenteChatWidgetTest.php` CT-16/CT-22/CT-24/CT-26 (`Livewire::test(AssistenteChatWidget::class)`, `Assistente::fake`, espião do canal `ai`) | as ações só são alcançáveis por `$wire.`; `Livewire::test` executa o mesmo hydrate + call. Painel corrente: em produção o `SetUpPanel` é middleware persistente do Livewire (`vendor/filament/filament/src/FilamentServiceProvider.php:packageBooted():128`), no teste é `Filament::setCurrentPanel()` | sessão, 2026-10-19 — convenção existente (molde citado), sem decisão material pendente |
| G7 Widgets | R11 | Pest feature HTTP | existente — GET do dashboard com `assertSeeLivewire`/`assertDontSeeLivewire`, persona `master_global` (`.ai/rules/testes.md`, "Teste que monta Widget") | o requisito afirma exibição no dashboard e dado intacto | sessão, 2026-10-19 — convenção existente (molde citado), sem decisão material pendente |
| G9 Chat com multi-organização | R13 (CT-23) | Pest feature HTTP + componente Livewire/Filament | existente — `tests/Tenancy/TelasSemDockNemChatTenancyTest.php` (`Tests\TenancyTestCase`); arquivo `tests/Tenancy/AgentesIaNoSettingsTenancyTest.php` | com organizações ligadas, o render no `/admin` e o recorte do histórico por dono são o observável de P-01/P-07; só a suíte Tenancy liga `permission.teams` | sessão, 2026-10-19 — indicada na triagem do ADV-05 |
| G8 Pilha de botões flutuantes | R12 | browser | existente — `tests/Browser/PilhaDeBotoesFlutuantesTest.php` (`MEDIR_PILHA_FLUTUANTE`, `abrirPainelDaPilha`); arquivo `tests/Browser/AgentesIaNoSettingsTest.php` (Q7 (era Q?3): helpers vão para `tests/Pest.php`) | posição, dimensão e sobreposição no tema de outro painel: só o navegador prova | sessão, 2026-10-19 — convenção existente (molde citado), sem decisão material pendente |

Divergência declarada (Project Rule vence a skill): a skill chama a costura HTTP de "teste em `tests/Feature`"; neste projeto os testes do kit vivem em `tests/Kit` com o mesmo `TestCase` + `RefreshDatabase` (`tests/Pest.php`, bloco "Testes do KIT"), e é lá que ficam.

Gate de camada da regra: R6/R7 (autorização) têm cenário HTTP por fora do componente (CT-11, CT-13, CT-15). R4 (validação de domínio) tem o ponto de **uso** por fora da tela: o id fora das opções gravado direto no banco não acende chat nem quebra painel (CT-16, linhas `App`/`apps`/`infra-x`). R9/R10 (autorização das ações do chat) não têm rota própria: a única entrada é o endpoint de update do Livewire, que `Livewire::test` reproduz — desvio declarado, sem camada mais externa disponível além do browser (cortado, ver *Cogitado e cortado*).

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| `App\Support\AgentesDeIa::habilitado()` / `::assistenteNoPainel()`, trait `ExigeAgentesDeIaLigados`, `authorizeResourceAccess()`, `fonteDeDadosDisponivel()` | escolha de implementação | detalhe: só na lista de arquivos do filtro de pilha do CT-06; nenhum `Então` os chama |
| aba `aplicacao`, seção "Agentes de IA", ícone, id da seção | escolha de implementação (o `00` diz "tela de configurações", não a aba) | detalhe: o CT localiza o campo por `chaveDoCampo()`, sem afirmar a aba |
| rótulos "Usar agentes de IA", "Painéis onde o chat do Assistente aparece" e os textos de ajuda | comportamento visível que só o PRD determina | Q8 respondida: texto do plano aceito; nenhum cenário depende dele |
| `Select` visível só com o toggle ligado | comportamento visível só do PRD | não vira oráculo; o que é do `00` é "nada é apagado ao desligar" (Fora de Escopo), e isso é o CT-07 |
| `warning` no canal `ai` na recusa do **catálogo** (passo 5 do plano) | log sem cláusula no `00` (Proibição 12) | nenhum CT; conferência da dimensão D do quality gate |
| texto e contexto do `warning` do chat (`[AssistenteChatWidget@assertContexto] ...`, `motivo => agentes_ia_desligado`) | o `00` (P-03) determina **que** há um `warning` no canal `ai`, não o texto | o CT-18 afirma um `warning` no canal `ai`, uma vez; prefixo e `motivo` são detalhe |
| formato de `KIT_AGENTES_IA_PAINEIS` ("ids separados por vírgula") | o `00` (P-05) nomeia a variável, não o formato | detalhe de fixture do CT-02 |
| hook global em `BODY_END` (`ConfiguraFilamentGlobal`) e remoção do hook do `AppPanelProvider` | escolha de implementação | detalhe do filtro do CT-06; o oráculo é o HTML de cada painel (CT-16) |
| "zero consulta ao banco" no docblock do decisor | o oráculo vem de RQ-03 ("como é feito hoje com outros pacotes no settings"): os vizinhos (dock, CT-35) decidem pela config sobreposta no boot, sem consultar o settings | CT-06, com o observável do vizinho |
| path da migration `2026_10_19_100000_add_agentes_ia_to_kit_settings.php` | nome de arquivo | detalhe do CT-02 |
| opções do select = painéis registrados (`Paineis::opcoes()`) | fato do código existente (`app/Support/Paineis.php:opcoes():64`), não do plano | `apps` e `App` são os ids fora das opções no CT-08, escolhidos para discriminar comparação frouxa (caixa, prefixo) |

**Valor literal do requisito**: RQ-07 ("por padrão deixe desativado") é afirmado pelo CT-01 e pelo CT-02 **sem** `config()->set()`: a config é relida do `config/kit.php` com a variável removida do ambiente (`kitConfigCom`) e com o valor do `.env.example`. Não depende do `phpunit.xml`, que **não** fixa `KIT_AGENTES_IA` — por isso um `.env` local com `KIT_AGENTES_IA=true` mudaria toda cenário que confiasse no default (Q5, era Q?1: o `phpunit.xml` passa a fixar as duas). Todo cenário fora de R1 fixa o estado com `ligarAgentesDeIa()`.

**Perguntas geradas pela derivação** (provisórias; a sessão renumera):

❓ Q5 (era Q?1) · raia: desenho · afeta: RQ-07 · depende de: —
O `phpunit.xml` fixa as chaves `KIT_*` que mudam comportamento (`KIT_HUB`, `KIT_REGISTRO`…) para a suíte medir o kit e não o `.env` local, mas não fixa `KIT_AGENTES_IA` nem `KIT_AGENTES_IA_PAINEIS`. Fixar as duas (`false` e vazio, `force="true"`)?
➡️ Recomendação: sim — é o motivo escrito no próprio bloco do `phpunit.xml`; sem isso, um desenvolvedor com `KIT_AGENTES_IA=true` no `.env` vê os testes do lote T0 que dependem do default mudarem. Os CT deste `04` não dependem da resposta (fixam o estado explicitamente).

❓ Q6 (era Q?2) · raia: requisito · afeta: RQ-05 · depende de: —
Uma tela do catálogo já aberta (criar ou editar um agente) quando a instalação desliga o uso: a próxima ação nela (salvar) é recusada com 404 e nada é gravado, ou ainda grava?
➡️ Recomendação: recusa com 404 e nada gravado — falha fechado, a mesma regra que P-03 fixou para o chat; o catálogo "não é disponível" com o uso desligado. Invariante já coberto: a URL responde 404 em qualquer entrada nova (CT-11).

❓ Q7 (era Q?3) · raia: desenho · afeta: P-01 · depende de: —
O CT-B01 precisa de `MEDIR_PILHA_FLUTUANTE` e de `abrirPainelDaPilha()`, hoje declarados dentro de `tests/Browser/PilhaDeBotoesFlutuantesTest.php`. Mover os dois para `tests/Pest.php` (regra "helper usado por mais de um arquivo vive em `tests/Pest.php`") ou escrever o CT-B01 dentro daquele arquivo?
➡️ Recomendação: mover para `tests/Pest.php` — a rule proíbe o clone com outro nome, e o `abrirPainelDaPilha()` já aceita `/admin` e `/infra`; ganha só o parâmetro dos agentes.

❓ Q8 (era Q?4) · raia: requisito · afeta: RQ-01, RQ-02 · depende de: —
Os rótulos e textos de ajuda dos dois campos novos ("Usar agentes de IA", "Painéis onde o chat do Assistente aparece" e as ajudas) só existem no plano. O solicitante quer decidir o texto?
➡️ Recomendação: não — aceitar o texto do plano; nenhum cenário depende dele (o campo é achado pelo nome).

## Setup Global

### Personas
- **a administradora** — `usuarioDoKit('admin', 'admin@example.com')`; para "sem a permissão", `semAPermissao('admin', '{permissão}')` no papel real (a única variável é a permissão; `canAccessPanel()` continua passando), seguido de `noPainelDoShield('admin')`
- **o Bruno** — `usuarioDoKit('admin', 'bruno@example.com')` + `assignRole(['panel_user', 'infra'])`: entra nos três painéis; é a persona das tabelas de decisão de painel (molde do CT-07 do dock)
- **a Ana** — `usuarioDoKit('panel_user', 'ana@example.com')`, dona das conversas no `/app`
- **o administrador geral** — `usuarioDoKit('master_global', 'master@example.com')`, persona dos widgets (passa no `canView()`)
- **a Íris** — `usuarioDoKit('infra', 'iris@example.com')`, persona do `/infra` no CT-21
- **a Carla** — pessoa de uma organização na suíte Tenancy (CT-23), dona da conversa "Pedido da Acme"

### Fixtures
- Seeds em todo arquivo: `ShieldPermissionsSeeder`, `PapeisSeeder`. Catálogo e chat: `AssistenteSeeder`, `GuardaPromptSeeder` (dão o agente `assistente` — o registro do `/{record}/edit` — e o `guardaprompt`; o catálogo parte com **2** agentes) e `config(['ai.providers.llamacpp.models.text.default' => 'modelo-de-teste'])`, como o `AssistenteChatWidgetTest`
- Estado da feature por caso: `ligarAgentesDeIa(bool $ligado, array $paineis)` (helper planejado em `tests/Pest.php`). **Todo** cenário fora de R1 chama o helper explicitamente, inclusive para desligar
- "Banco" do settings: `gravarConfiguracao('agentes_ia_habilitado', …)` / `gravarConfiguracao('agentes_ia_paineis', …)` + `alinharConfiguracoesDoKit()` (o caminho do boot); leitura por `configuracaoGravada()`
- Config de fábrica: `kitConfigCom('KIT_AGENTES_IA', null)` e `kitConfigCom('KIT_AGENTES_IA', {valor do .env.example})`, lendo o `.env.example` por `envDoTeste()`/`valorNoEnv()`
- Conversas: `conversaPara($ana, 'Plano de férias')`, `conversaPara($ana, 'Viagem')`; mensagens anteriores (CT-26) por `ConversationMessage::create` na conversa da Ana; a conversa do Bruno no CT-24 nasce pelo `responder` no `/app` com o `Assistente` fake
- Suíte Tenancy (CT-23): `Tests\TenancyTestCase`, `duasOrganizacoes()`, `usuarioComPapel()`/`papelNaOrganizacao()` para a pessoa de uma organização; o `admin` é papel de contexto global (`.ai/rules/testes.md`, tabela de papéis)
- Dock: `ligarDock(['admin'])`, `concederDock('admin')`, `gravarAtalhos($bruno, [chaveDeAtalho('admin', AgenteIaResource::class)])`, `atalhosGravados()`, `dockNaPagina('admin')`, `opcoesDoSlideover()`
- Hub: `config(['kit.hub' => true])` (o `phpunit.xml` força `KIT_HUB=false`)
- Sem situação de partida com máquina de estados: as entidades tocadas (settings, favorito, conversa) não têm ciclo de vida; o "estado" é a configuração da instalação, montada pelo helper

### Como cada superfície é observada
- Painel `/app` só boota por request real (`.ai/rules/testes.md`): `get('/app')->assertOk()` antes de `Livewire::test` no `/app`; `/admin` e `/infra`, `noPainelBootado()`
- Entre requests com estado diferente, ou entre painéis: `fronteiraDeRequest()`
- Chat presente/ausente: `assertSeeLivewire(AssistenteChatWidget::class)` / `assertDontSeeLivewire(...)` **e** `data-assistente-chat` no HTML sem `<script>` (molde do CT-14 do `AssistenteChatWidgetTest`); toda linha "ausente" convive com uma linha "presente" da mesma tabela (controle positivo)
- Barra lateral: `Filament::getNavigation()` do `/admin` montado, URLs dos itens contra `AgenteIaResource::getUrl('index', panel: 'admin')`
- ⌘K: `ResourcesAutorizadasCategory` (telas), `Wezlo\FilamentSearchSpotlight\Categories\RecordsCategory` (registros, termo `Assistente`) e `AcoesDeCriacao::registrar()` + `SpotlightActionRegistry` (ação `criar-agentes-ia`), como o plugin as monta no `AdminPanelProvider`
- Widgets: `assertSeeLivewire(AgentesIaStats::class)` / `AgentesIaPorProvider::class` no GET do dashboard do `/admin`; `IaStats::class` no `/infra`
- Status 200 nunca sozinho: junto, `assertSeeLivewire` da Page do catálogo (`ListAgentesIa`, `CreateAgenteIa`, `EditAgenteIa`)

### Fakes
- `Assistente::fake(['Resposta fixa do teste'])` e `GuardaPrompt::fake([['seguro' => true, 'categoria' => 'legitima', 'motivo' => 'pergunta comum']])`; não-efeito por `Assistente::assertNeverPrompted()` **e** `GuardaPrompt::assertNeverPrompted()` (`vendor/laravel/ai/src/Promptable.php:assertNeverPrompted():404`)
- Canal `ai`: `Mockery::spy(LoggerInterface::class)` + `Log::partialMock()->shouldReceive('channel')->with('ai')->andReturn($canal)`, instalado **depois** do arranjo (o `retomarConversa` do `Dado` pode logar no canal)
- Consultas: `consultasDoGet()` de `tests/Pest.php`; filtro local de tabela `settings` × pilha com arquivo da feature

### Estratégia de DB
- `RefreshDatabase` do bloco `tests/Kit` em `tests/Pest.php`. Com ele, o boot roda antes das migrations e o alinhamento é no-op: quem quer o caminho banco → config chama `alinharConfiguracoesDoKit()`

---

## Regra R1 — Instalação nova e `.env` de fábrica nascem com o uso desligado e nenhum painel

> `RQ-07`, `RQ-08`, `P-05` · perfil **padrão** · técnica: **EP** (partições do ambiente: chaves ausentes · valores do `.env.example`)

```gherkin
# language: pt
Funcionalidade: Agentes de IA ligados pelo settings, por painel

  Regra: a instalação nova e o .env de fábrica nascem com o uso de agentes desligado e o chat em nenhum painel

    Esquema do Cenário: [CT-01] a config lida do ambiente de fábrica desliga o uso e não escolhe painel
      Dado o ambiente com <ambiente>
      Quando o config/kit.php é lido
      Então o uso de agentes lido é false
      E a lista de painéis lida é []

      Exemplos:
        | ambiente                                                                                         | # partição             |
        | KIT_AGENTES_IA e KIT_AGENTES_IA_PAINEIS ausentes                                                 | instalação sem as chaves |
        | KIT_AGENTES_IA e KIT_AGENTES_IA_PAINEIS com os valores do .env.example (linha comentada = ausente) | .env de fábrica         |

    Esquema do Cenário: [CT-02] a migration de settings semeia o banco com o que o ambiente diz
      Dado as duas propriedades ainda não semeadas no settings do kit
      E a config resolvida do ambiente <semente>
      Quando a migration de settings da feature roda
      Então o uso gravado no banco é <uso gravado>
      E a lista gravada é <lista gravada>

      Exemplos:
        | semente                                                  | uso gravado | lista gravada        | # partição        |
        | de fábrica (kitConfigCom com as duas chaves ausentes)    | false       | []                   | fábrica           |
        | KIT_AGENTES_IA=true e KIT_AGENTES_IA_PAINEIS=admin,infra | true        | ["admin", "infra"]   | .env preenchido   |
```

As duas variáveis são relidas juntas por `kitConfigCom` (aninhado, ou o helper estendido a um mapa de chaves) — nunca pela config do processo, que lê o `.env` local. A linha "fábrica" do CT-02 roda a migration sob essa config relida.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | `env('KIT_AGENTES_IA', true)` — default ligado | CT-01 (linha "ausentes") | uso lido `false`; o mutante lê `true` |
| M2 | `.env.example` publicado com `KIT_AGENTES_IA=true` | CT-01 (linha ".env de fábrica") | uso lido `false`; o mutante lê `true` |
| M3 | lista de fábrica `['app']` no `config/kit.php`, para manter o chat onde ele já estava | CT-01 (linha "ausentes") | lista lida `[]`; o mutante lê `["app"]` |
| M63 | `.env.example` publicado com `KIT_AGENTES_IA_PAINEIS=app` ativo (M-ADV-13 — revisão adversarial) | CT-01 (linha ".env de fábrica") | lista lida `[]`; o mutante lê `["app"]` |
| M4 | migration semeia `true` fixo (ou `false` fixo, ignorando o ambiente) | CT-02 | linha "fábrica": `false`; linha ".env preenchido": `true` — cada literal fixo erra uma das duas |
| M5 | migration semeia a lista de todos os painéis registrados, ou `[]` fixo | CT-02 | linha "fábrica" `[]` mata o "todos"; linha ".env preenchido" `["admin","infra"]` mata o `[]` fixo |

---

## Regra R2 — Sem a chave na config, o uso fica desligado

> `RQ-07`, `RQ-08` · perfil **padrão** · técnica: **EP — discriminante nulo** (o projeto atualizado pelo `kit:update` pode ter o `config/kit.php` sem o bloco novo)

```gherkin
  Regra: sem a chave na config, o uso de agentes fica desligado e o chat em nenhum painel

    Esquema do Cenário: [CT-03] config sem o bloco ou com a lista nula falha fechado
      Dado o Bruno com a permissão do catálogo e papéis nos três painéis
      E a config com <estado da config>
      Quando ele abre <url>
      Então a resposta é <status>
      E o chat do Assistente <chat>

      Exemplos:
        | estado da config                                   | url                 | status | chat                   | # partição                    |
        | sem o bloco kit.agentes_ia                         | /admin/agentes-ia   | 404    | —                      | bloco ausente                  |
        | sem o bloco kit.agentes_ia                         | /admin              | 200    | não aparece no /admin  | bloco ausente                  |
        | habilitado true e paineis null                     | /admin              | 200    | não aparece no /admin  | lista nula                     |
        | habilitado true e paineis null                     | /app                | 200    | não aparece no /app    | lista nula não é o legado /app |
        | habilitado true e paineis null                     | /admin/agentes-ia   | 200    | —                      | controle (ligado)              |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M6 | default `true` na leitura da chave ausente (`config('kit.agentes_ia.habilitado', true)`) | CT-03 (linha 1) | `/admin/agentes-ia` 404; o mutante abre 200 |
| M7 | lista nula lida como "todos os painéis" (a semântica do dashboard dinâmico) | CT-03 (linha 3) | chat ausente no `/admin`; o mutante o renderiza |
| M8 | lista nula estoura `TypeError` no render do painel | CT-03 (linhas 3 e 4) | 200; o mutante responde 500 |
| M56 | lista nula cai no legado `['app']` (o chat volta só ao `/app`, como era antes da feature) — revisão adversarial (ADV-03) | CT-03 (linha 4) | chat ausente no `/app`; o mutante o renderiza |

---

## Regra R3 — O banco vence o `.env`, e a decisão vale por request, sem consulta ao settings

> `RQ-03`, `P-05` · perfil **padrão** · técnica: **tabela de decisão** (semente do `.env` × valor gravado) + **2-switch** (dois requests no mesmo processo). Um `Quando` por linha: cada linha observa um alvo só

```gherkin
  Regra: o valor gravado no banco vence o .env e a decisão é tomada a cada request, sem deploy

    Esquema do Cenário: [CT-04] depois do boot, o que está gravado decide, não o .env
      Dado o Bruno com a permissão do catálogo e papéis nos três painéis
      E a config semeada pelo .env com <semente>
      E o banco com <gravado>
      Quando o boot do request seguinte alinha a config e <alvo> é observado
      Então <resultado>

      Exemplos:
        | semente                          | gravado                            | alvo                                  | resultado                                                     |
        | desligado, []                    | ligado, ["admin"]                  | a config efetiva lida                 | uso `true` e lista `["admin"]` (comparação estrita de tipo)  |
        | desligado, []                    | ligado, ["admin"]                  | o GET /admin/agentes-ia               | 200 com a Page ListAgentesIa no corpo                         |
        | desligado, []                    | ligado, ["admin"]                  | o GET /admin                          | o chat do Assistente aparece                                  |
        | ligado, ["admin","app","infra"]  | desligado, ["admin","app","infra"] | o GET /admin/agentes-ia               | 404                                                           |
        | ligado, ["admin","app","infra"]  | desligado, ["admin","app","infra"] | o GET /admin                          | o chat do Assistente não aparece                              |
        | ligado, ["admin"]                | ligado, ["infra"]                  | o GET /infra                          | o chat do Assistente aparece                                  |
        | ligado, ["admin","app"]          | ligado, ["infra"]                  | o GET /app                            | o chat do Assistente não aparece                              |
        | ligado, ["admin"]                | ligado, []                         | o GET /admin                          | o chat do Assistente não aparece                              |

    Esquema do Cenário: [CT-05] o request seguinte já obedece à mudança, no mesmo processo
      Dado o Bruno com a permissão do catálogo
      E o uso ligado com ["admin"], e um primeiro request respondeu 200 no catálogo e mostrou o chat no /admin
      E a instalação passa a <mudança>
      Quando o request seguinte abre <alvo>
      Então <resultado>

      Exemplos:
        | mudança                                     | alvo                | resultado                          |
        | uso desligado, lista ["admin"] mantida      | /admin/agentes-ia   | 404                                |
        | uso desligado, lista ["admin"] mantida      | /admin              | o chat do Assistente não aparece   |
        | uso ligado, lista esvaziada                 | /admin/agentes-ia   | 200 com a Page ListAgentesIa       |
        | uso ligado, lista esvaziada                 | /admin              | o chat do Assistente não aparece   |

    Cenário: [CT-06] ligar ou desligar o uso não muda as consultas à tabela de settings, e a feature não a consulta
      Dado o Bruno com a lista ["admin"] e o /admin já aberto uma vez (aquecimento)
      E o contador de consultas à tabela settings conta ao menos 1 num arranjo que lê o settings do banco (controle do contador)
      Quando o /admin é medido em quatro requests alternando o uso: desligado, ligado, ligado, desligado
      Então a soma das consultas à tabela settings nos dois requests ligados é igual à soma nos dois desligados
      E nenhuma consulta à tabela settings, em nenhum dos quatro, tem na pilha arquivo da feature
```

Ordem desligado-ligado-ligado-desligado: cancela a deriva linear de consultas por request dentro de um processo (molde do CT-56 do dock). Arquivos da feature no filtro de pilha (detalhe, do plano): `app/Support/AgentesDeIa.php`, `app/Filament/Admin/Resources/AgentesIa/`, `app/Filament/Concerns/ExigeAgentesDeIaLigados.php`, `app/Filament/Admin/Widgets/AgentesIa*`, `app/Livewire/AssistenteChatWidget.php`, `app/Providers/Concerns/ConfiguraFilamentGlobal.php`. Controle do contador: o mesmo `DB::listen` registra ≥ 1 consulta ao `settings` ao resolver `ConfiguracoesDoKit` depois de `app()->forgetInstance(ConfiguracoesDoKit::class)` — sem ele, um contador quebrado passaria a igualdade com 0 = 0.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M9 | `agentes_ia_paineis` fora do mapa settings → config (o banco não chega à lista) | CT-04 (linhas 6, 7 e 8) | linha 6: chat no `/infra`; o mutante segue com `["admin"]` da semente e não o mostra |
| M10 | as chaves trocadas no mapa (`agentes_ia_habilitado` → `kit.agentes_ia.paineis`) | CT-04 (linhas 1 e 2) | linha 1: uso `true` e lista `["admin"]` com `toBe`; o mutante deixa o uso `false` da semente e a lista `true`. Linha 2: 200; o mutante responde 404 |
| M11 | `.env` preenchido vence o banco (alinhamento pula chave definida no ambiente) | CT-04 (linhas 4 e 5) | 404 e chat ausente; o mutante mantém a semente ligada |
| M55 | a lista gravada é somada à semente (união) em vez de substituí-la — revisão adversarial (ADV-01/ADV-02) | CT-04 (linhas 7 e 8) | chat ausente no `/app` (linha 7) e no `/admin` (linha 8); o mutante renderiza pela parte da semente |
| M12 | decisão tomada no registro do painel (Resource/hook condicionados no `PanelProvider`), fixa no processo | CT-05 | linha 1: 404 no segundo request; o mutante segue com o 200 do primeiro. Linha 4: chat ausente; o mutante segue renderizando |
| M13 | decisor lê o settings direto (`app(ConfiguracoesDoKit::class)` ou `SettingsProperty::query()`) a cada chamada | CT-06 | nenhuma consulta ao `settings` com pilha da feature, e somas iguais; o mutante entrega consulta com pilha da feature (e, se só quando ligado, desequilibra a soma) |

---

## Regra R4 — A tela de configurações grava o interruptor e a lista, recusa valor fora do domínio e não perde o que está gravado ao salvar outro campo

> `RQ-01`, `RQ-02`, `RQ-03`, `RQ-08`, Fora de Escopo ("nada é apagado") · perfil **padrão** · técnica: **EP** (uma partição inválida por linha) + **gate de tela de escrita**

```gherkin
  Regra: a tela de configurações grava o interruptor e a lista de painéis, recusa o que está fora do domínio e não perde o gravado

    Esquema do Cenário: [CT-07] o administrador grava o uso e os painéis pela tela
      Dado a administradora na tela de configurações da aplicação
      E o catálogo com 2 agentes ativos e a conversa "Plano de férias" da Ana gravada
      E o banco com <antes>
      Quando ela <ação> e salva
      Então o formulário salva sem erro
      E o uso gravado é <uso gravado>
      E a lista gravada é <lista gravada>
      E o catálogo continua com 2 agentes ativos, e a conversa "Plano de férias" da Ana continua gravada

      Exemplos:
        | antes               | ação                                     | uso gravado | lista gravada      | # partição                         |
        | desligado, []       | liga o uso e escolhe admin e infra       | true        | ["admin", "infra"] | ligar com dois painéis              |
        | desligado, []       | liga o uso sem escolher painel           | true        | []                 | lista vazia = nenhum                |
        | ligado, ["app"]     | desliga o uso                            | false       | ["app"]            | desligar não apaga nada             |

    Esquema do Cenário: [CT-08] valor fora do domínio é recusado no campo e nada é gravado
      Dado a administradora na tela de configurações da aplicação
      E o banco com o uso desligado e a lista ["app"]
      Quando ela salva com <campo> = <valor> e o outro campo válido
      Então o erro aparece em <erro em>
      E o uso gravado continua false
      E a lista gravada continua ["app"]

      Exemplos:
        | campo                  | valor                   | erro em                  | # partição inválida                  |
        | agentes_ia_paineis     | "app" (texto)           | agentes_ia_paineis       | lista como texto                      |
        | agentes_ia_paineis     | ["apps"]                | agentes_ia_paineis.0     | painel fora das opções (prefixo)      |
        | agentes_ia_paineis     | ["admin", "App"]        | agentes_ia_paineis.1     | um válido ao lado de um fora (caixa)  |
        | agentes_ia_habilitado  | ["1"] (lista)           | agentes_ia_habilitado    | toggle como lista                     |
        | agentes_ia_habilitado  | "talvez"                | agentes_ia_habilitado    | toggle como texto não booleano        |

    Cenário: [CT-25] salvar outro campo da tela não apaga o uso nem a lista gravados
      Dado a administradora na tela de configurações da aplicação
      E o banco com o uso ligado e a lista ["admin"]
      Quando ela altera um campo de outra aba (o nome da aplicação) e salva, sem abrir a seção dos agentes
      Então o formulário salva sem erro
      E o uso gravado continua true
      E a lista gravada continua ["admin"]
```

Nas linhas de `agentes_ia_paineis` do CT-08 o uso vai `true` no mesmo salvamento (válido), para o select estar no formulário; assim o "uso continua false" também prova que o salvamento recusado não gravou o campo válido. O campo existe no formulário: `chaveDoCampo($tela, 'agentes_ia_habilitado')` e `chaveDoCampo($tela, 'agentes_ia_paineis')` não são nulos (o helper afirma). Asserções de tipo com `toBe` (`true`, não `"1"`). No CT-25 a aba dos agentes não é carregada (`carregarAba` só na aba do campo alterado) — é o caminho em que um campo de aba adiada poderia ir ao `save` com o default.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M14 | select múltiplo sem a regra `in` das opções (aceita `apps`) | CT-08 (linhas 2 e 3) | erro em `agentes_ia_paineis.0`/`.1` e lista `["app"]`; o mutante grava `["apps"]` |
| M15 | sem a regra de lista (texto aceito e gravado como string, ou `TypeError` 500 no settings tipado) | CT-08 (linha 1) | erro de formulário em `agentes_ia_paineis` e lista `["app"]`; o mutante grava `"app"` ou estoura |
| M16 | toggle sem validação booleana | CT-08 (linhas 4 e 5) | erro em `agentes_ia_habilitado`; o mutante grava `["1"]`/`"talvez"` ou estoura |
| M17 | desligar zera a lista (`mutateFormDataBeforeSave` grava `[]` quando o uso vai `false`) | CT-07 (linha 3) | lista gravada `["app"]`; o mutante grava `[]` |
| M18 | o campo não chega ao settings (não desidratado, ou propriedade com outro nome) | CT-07 (linha 1) | lista gravada `["admin","infra"]` e uso `true`; o mutante deixa `[]`/`false` |
| M61 | campo de aba adiada não carregada vai ao `save` com o default (`false`/`[]`) e sobrescreve o gravado — revisão adversarial (ADV-10) | CT-25 | uso `true` e lista `["admin"]`; o mutante grava `false`/`[]` |
| M62 | desligar pela tela "limpa" o que dependia do uso: desativa os agentes ou apaga as conversas — revisão adversarial (ADV-11) | CT-07 (linha 3) | 2 agentes ativos e "Plano de férias" gravada; o mutante deixa 0/inativos ou apaga a conversa |

---

## Regra R5 — Com o uso desligado, o catálogo some de toda superfície de navegação; o favorito do dock vira oculto e volta

> `RQ-04`, `P-04` · perfil **padrão** · técnica: **EP por superfície** + **2-switch** (escalada declarada no Perfil)

```gherkin
  Regra: com o uso desligado, o catálogo Agentes de IA não é oferecido em nenhuma superfície de navegação do /admin

    Esquema do Cenário: [CT-09] cada superfície de navegação lista o catálogo só com o uso ligado
      Dado o Bruno com a permissão do catálogo, Usar:Dock, o dock ligado no /admin e o hub ligado
      E o uso <uso> com a lista <lista>
      Quando a <superfície> do /admin é montada
      Então o catálogo Agentes de IA está <catálogo> nela

      Exemplos:
        | superfície                                    | uso       | lista       | catálogo  |
        | barra lateral                                 | desligado | ["admin"]   | ausente   |
        | barra lateral                                 | ligado    | []          | presente  |
        | barra lateral                                 | ligado    | ["admin"]   | presente  |
        | hub de cartões                                | desligado | ["admin"]   | ausente   |
        | hub de cartões                                | ligado    | []          | presente  |
        | busca ⌘K — telas (termo "Agentes")            | desligado | ["admin"]   | ausente   |
        | busca ⌘K — telas (termo "Agentes")            | ligado    | []          | presente  |
        | busca ⌘K — registros (termo "Assistente")     | desligado | ["admin"]   | ausente   |
        | busca ⌘K — registros (termo "Assistente")     | ligado    | []          | presente  |
        | busca ⌘K — ação "Criar agente"                | desligado | ["admin"]   | ausente   |
        | busca ⌘K — ação "Criar agente"                | ligado    | []          | presente  |
        | catálogo do dock (opções do slideover)        | desligado | ["admin"]   | ausente   |
        | catálogo do dock (opções do slideover)        | ligado    | []          | presente  |

    Esquema do Cenário: [CT-10] o favorito do dock para o catálogo fica oculto com o uso desligado, sobrevive ao salvar o slideover e volta ao religar
      Dado o Bruno com Usar:Dock, o dock ligado no /admin e o favorito do catálogo gravado
      E <histórico>
      Quando <ação>
      Então <resultado>
      E os favoritos gravados do Bruno contêm a chave do catálogo no /admin

      Exemplos:
        | histórico                                                    | ação                                                                 | resultado                                                    |
        | o uso desligado                                              | o dock do /admin é desenhado                                         | o link do catálogo está ausente no dock                      |
        | o uso desligado                                              | o slideover do dock é aberto                                         | a chave do catálogo não está entre as opções nem os escolhidos |
        | o uso desligado                                              | o Bruno salva o slideover escolhendo também a lista de usuários      | os favoritos gravados contêm também a lista de usuários       |
        | o slideover salvo com o uso desligado, e depois o uso religado | o dock do /admin é desenhado                                       | o link do catálogo está presente no dock                     |
```

"Presente" no ⌘K: um resultado cujo destino é o `AgenteIaResource` (telas), um registro do agente `assistente` (registros) e a ação `criar-agentes-ia` no registry (ação). "Presente" no hub e na barra: a URL `AgenteIaResource::getUrl('index', panel: 'admin')`. No CT-10, a lista de usuários é `chaveDeAtalho('admin', UserResource::class)`; o slideover é lido por `opcoesDoSlideover()`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M19 | só `shouldRegisterNavigation()` olha o interruptor (barra e hub somem; `canAccess()` segue verdadeiro) | CT-09 (linhas ⌘K telas e "Criar agente", desligado) | ausente; as categorias que filtram por `canAccess()` devolvem o catálogo |
| M20 | a busca de registros consulta o Resource sem passar pelo acesso (`RecordsCategory` cru) | CT-09 (linha ⌘K registros, desligado) | nenhum resultado do agente `assistente`; o mutante o lista |
| M21 | catálogo esconde do menu exigindo o `/admin` na lista (a lista governando a navegação) | CT-09 (linhas "ligado, []") | presente; o mutante esconde com lista vazia |
| M22 | o dock apaga do banco o favorito cujo destino ficou inacessível | CT-10 (linhas 1 e 4) | chave do catálogo nos favoritos gravados e link de volta; o mutante perde a chave e não volta |
| M23 | o dock não consulta o acesso do destino Resource (favorito segue visível e oferecido com o uso desligado) | CT-10 (linhas 1 e 2) e CT-09 (linha dock, desligado) | link ausente e chave fora do slideover; o mutante a desenha/oferece |
| M60 | salvar o slideover regrava só o que estava visível e descarta o favorito oculto — revisão adversarial (ADV-08/ADV-09) | CT-10 (linha 3) | favoritos gravados com a chave do catálogo **e** a de usuários; o mutante grava só a de usuários |

---

## Regra R6 — Com o uso desligado, as três URLs do catálogo respondem 404, com e sem permissão; o painel continua abrindo

> `RQ-05` · perfil **padrão** · técnica: **matriz papel × ação** (permissão da tela × rota)

```gherkin
  Regra: com o uso desligado, toda URL do catálogo responde 404, inclusive para quem tem a permissão

    Esquema do Cenário: [CT-11] lista, criar e editar respondem 404 com o uso desligado
      Dado o uso desligado com a lista ["admin", "app", "infra"]
      E a administradora <permissão>
      Quando ela abre <rota> do catálogo
      Então a resposta é 404
      E o corpo não traz a Page do catálogo nem o nome do agente "Assistente"

      Exemplos:
        | rota                                  | permissão                          |
        | /admin/agentes-ia                     | com ViewAny:AgenteIa               |
        | /admin/agentes-ia                     | sem ViewAny:AgenteIa               |
        | /admin/agentes-ia/create              | com Create:AgenteIa                |
        | /admin/agentes-ia/create              | sem Create:AgenteIa                |
        | /admin/agentes-ia/{assistente}/edit   | com Update:AgenteIa                |
        | /admin/agentes-ia/{assistente}/edit   | sem Update:AgenteIa                |

    Esquema do Cenário: [CT-12] depois do 404, o painel de administração continua abrindo, e só o link do catálogo some
      Dado a administradora com a permissão do catálogo
      E <estado>
      Quando ela abre o /admin
      Então a resposta é 200 com o dashboard do /admin
      E o corpo <link> o link AgenteIaResource::getUrl('index', panel: 'admin')

      Exemplos:
        | estado                                                         | link        |
        | o uso desligado, e o /admin/agentes-ia já respondeu 404 a ela  | não contém  |
        | o uso ligado com [] (controle positivo)                        | contém      |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M24 | `canAccess()` falso, sem 404 próprio (o Filament responde 403) | CT-11 (todas as linhas) | status `404`; o mutante entrega `403` |
| M25 | 404 só na lista; criar e editar por URL direta abrem | CT-11 (linhas create/edit "com") | 404; o mutante responde 200 com a Page |
| M26 | permissão conferida antes do interruptor (sem permissão → 403) | CT-11 (linhas "sem") | 404; o mutante entrega 403 |
| M27 | a guarda aborta na montagem da navegação e derruba o painel inteiro | CT-12 (linha 1) | `/admin` 200; o mutante responde 404 no dashboard. A linha 2 prova que a busca do link no corpo enxerga o link quando ele existe |

---

## Regra R6b — Com o uso desligado depois de uma tela do catálogo já aberta, a próxima ação nela responde 404 e nada é gravado

> `P-06` (Q6) · perfil **padrão** · técnica: **tabela estado × evento** (a célula E1 × O4, alcançada pela transição ligado → desligado entre o `mount` e a ação) + **rastreio de efeito** (não-efeito na tabela `agentes_ia`). Regra própria, e não linha da R6, porque a R6 já tem 4 mutantes e o teto do perfil padrão é 5

O `Dado` põe no mundo o efeito que o caminho feliz produziria: a administradora tem a permissão, a tela foi montada com o uso ligado e o formulário está preenchido com dados válidos — com o uso ligado, o mesmo salvamento grava (CT-14). A lista do catálogo só tem a ação de tabela "Editar" (sem excluir nem alternar ativo); o update Livewire dela é exercitado por uma ordenação.

```gherkin
  Regra: com o uso desligado depois de uma tela do catálogo já aberta, a próxima ação nela responde 404 e nada é gravado

    Esquema do Cenário: [CT-22] a tela do catálogo aberta com o uso ligado recusa a ação depois que o uso é desligado
      Dado o uso ligado com a lista ["admin"] e a administradora com <permissão>
      E a tela <tela> do catálogo montada <preparo>
      E depois do mount o uso é desligado
      Quando ela <ação>
      Então a resposta é 404
      E <não-efeito>
      E o catálogo continua com 2 agentes

      Exemplos:
        | tela                  | permissão        | preparo                                                       | ação                               | não-efeito                                               |
        | criar                 | Create:AgenteIa  | com o formulário preenchido: slug "relator", nome "Relator", ativo | salva o formulário             | não existe agente com slug "relator"                     |
        | editar o "assistente" | Update:AgenteIa  | com o nome trocado para "Assistente geral" no formulário      | salva o formulário                 | o agente "assistente" continua com o nome que tinha antes |
        | lista                 | ViewAny:AgenteIa | com a tabela carregada                                        | ordena a tabela pelo nome          | a resposta não traz a linha do agente "Assistente"       |
```

Molde do CT-14: `ligarAgentesDeIa(true, ['admin'])` antes do `Livewire::test(CreateAgenteIa::class)` / `Livewire::test(EditAgenteIa::class, ['record' => …])` / `Livewire::test(ListAgentesIa::class)->loadTable()`, o preparo, depois `ligarAgentesDeIa(false)` e então `call('create')` / `call('save')` / `sortTable('nome')`, com `assertNotFound()`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M51 | a guarda só no `mount` (o hydrate das Pages não confere o interruptor) | CT-22 (as três linhas) | 404 e nenhum agente "relator" / nome original; o mutante grava ou redesenha a tabela |
| M52 | a guarda só na Page da lista (criar e editar sem a guarda) | CT-22 (linhas criar e editar) | 404; o mutante salva na tela de criar/editar |
| M59 | a guarda do hydrate só nas Pages de formulário (Create/Edit), não na List — revisão adversarial (ADV-07) | CT-22 (linha lista) | 404; o mutante devolve a tabela ordenada com o "Assistente" |
| M53 | 403 no lugar de 404 na ação (o `canAccess()` falso devolve o 403 do Filament) | CT-22 | status `404`; o mutante entrega `403` |
| M54 | a gravação acontece antes da guarda (o salvamento conclui e só a página seguinte responde 404) | CT-22 (linhas criar e editar) | nenhum agente com slug "relator" e 2 agentes no catálogo; o mutante deixa 3 / o nome trocado |

---

## Regra R7 — Com o uso ligado, o catálogo abre e grava como hoje, com o par tem/não-tem permissão, só no `/admin`

> `RQ-06`, `P-01` · perfil **padrão** · técnica: **matriz papel × ação** + **gate de tela de escrita** (criar e editar estão na `## Superfície de UI`)

```gherkin
  Regra: com o uso ligado, o catálogo abre normalmente para quem tem a permissão, com qualquer lista de painéis, e só no /admin

    Esquema do Cenário: [CT-13] com o uso ligado, a permissão da tela decide como hoje
      Dado o uso ligado com a lista <lista>
      E a administradora <permissão>
      Quando ela abre <rota> do catálogo
      Então a resposta é <status>
      E <conteúdo>

      Exemplos:
        | lista       | rota                                 | permissão             | status | conteúdo                           |
        | []          | /admin/agentes-ia                    | com ViewAny:AgenteIa  | 200    | a Page ListAgentesIa está no corpo |
        | []          | /admin/agentes-ia/create             | com Create:AgenteIa   | 200    | a Page CreateAgenteIa está no corpo |
        | []          | /admin/agentes-ia/{assistente}/edit  | com Update:AgenteIa   | 200    | a Page EditAgenteIa está no corpo  |
        | ["admin"]   | /admin/agentes-ia                    | com ViewAny:AgenteIa  | 200    | a Page ListAgentesIa está no corpo |
        | ["admin"]   | /admin/agentes-ia/create             | com Create:AgenteIa   | 200    | a Page CreateAgenteIa está no corpo |
        | ["admin"]   | /admin/agentes-ia/{assistente}/edit  | com Update:AgenteIa   | 200    | a Page EditAgenteIa está no corpo  |
        | []          | /admin/agentes-ia                    | sem ViewAny:AgenteIa  | 403    | a Page não está no corpo           |
        | []          | /admin/agentes-ia/create             | sem Create:AgenteIa   | 403    | a Page não está no corpo           |
        | []          | /admin/agentes-ia/{assistente}/edit  | sem Update:AgenteIa   | 403    | a Page não está no corpo           |

    Esquema do Cenário: [CT-14] com o uso ligado, o formulário do catálogo grava
      Dado o uso ligado com a lista <lista>
      E a administradora com a permissão do catálogo
      Quando ela <ação> e salva
      Então o formulário salva sem erro
      E o banco tem <registro>

      Exemplos:
        | lista       | ação                                                              | registro                                                  |
        | []          | cria o agente slug "relator", nome "Relator", ativo                | agentes_ia com slug "relator", nome "Relator", ativo true |
        | ["admin"]   | edita o agente "assistente" trocando o nome para "Assistente geral" | agentes_ia com slug "assistente", nome "Assistente geral" |

    Esquema do Cenário: [CT-15] o catálogo continua só no /admin, mesmo com o chat nos três painéis
      Dado o uso ligado com a lista ["admin", "app", "infra"]
      E o Bruno com a permissão do catálogo e papéis nos três painéis
      Quando ele abre <url>
      Então a resposta é <status>

      Exemplos:
        | url                 | status                                   |
        | /app/agentes-ia     | 404                                      |
        | /infra/agentes-ia   | 404                                      |
        | /admin/agentes-ia   | 200 com a Page ListAgentesIa (controle)  |
```

Os campos obrigatórios do formulário do agente (além de `slug` e `nome`) são preenchidos com valor válido mínimo, lidos do `AgenteIaForm` só para os nomes; o `Então` afirma os campos que a linha muda.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M28 | `canAccess()` sobrescrito sem `&& parent::canAccess()` (a policy deixa de ser consultada) | CT-13 (linha "sem ViewAny") | 403; o mutante abre 200 |
| M29 | o catálogo exige o `/admin` na lista de painéis | CT-13 (linhas lista `[]`) | 200; o mutante responde 404 |
| M30 | a guarda de criar/editar só confere o interruptor e pula `canCreate()`/`canEdit()` | CT-13 (linhas "sem Create"/"sem Update") | 403; o mutante abre 200 |
| M31 | a guarda das ações Livewire das Pages confere a lista (ou inverte o interruptor) e recusa salvar com o uso ligado | CT-14 | registro gravado; o mutante responde 404 no `create`/`save` e não grava |
| M32 | o Resource passa a ser registrado em todo painel da lista (a leitura alternativa de Q1) | CT-15 (linhas `/app` e `/infra`) | 404; o mutante responde 200/403 |

---

## Regra R8 — O chat aparece só nos painéis da lista, com o uso ligado, em toda tela do painel e nunca em tela de autenticação

> `RQ-02`, `RQ-08`, `P-01`, glossário "Tela de autenticação" · perfil **completo** · técnica: **tabela de decisão completa** (interruptor × lista × painel)

Condições: interruptor (2) × relação painel/lista (vazia · contém · não contém · só id fora das opções · id fora ao lado do painel) × painel (3), mais a tela dentro do painel (dashboard · Page comum). Colapsos comprovados: com o interruptor desligado a ação não depende da lista (uma lista cheia, três painéis). Os ids fora das opções (`App`, `apps`, `infra-x`) diferem do painel só em caixa ou sufixo, para matar comparação frouxa.

```gherkin
  Regra: o chat do Assistente é renderizado em toda tela de todo painel da lista, e só neles, quando o uso está ligado

    Esquema do Cenário: [CT-16] a lista e o interruptor decidem em que painel o chat é renderizado
      Dado o Bruno com papéis nos três painéis
      E o uso <uso> com a lista <lista>
      Quando ele abre <tela>
      Então a resposta é 200
      E o chat do Assistente <chat> no corpo

      Exemplos:
        | uso       | lista                    | tela                      | chat         | # linha                              |
        | desligado | ["admin","app","infra"]  | o dashboard do /admin     | não aparece  | interruptor vence a lista            |
        | desligado | ["admin","app","infra"]  | o dashboard do /app       | não aparece  | interruptor vence a lista            |
        | desligado | ["admin","app","infra"]  | o dashboard do /infra     | não aparece  | interruptor vence a lista            |
        | ligado    | []                       | o dashboard do /admin     | não aparece  | vazia = nenhum                       |
        | ligado    | []                       | o dashboard do /app       | não aparece  | vazia = nenhum (o /app perde o chat) |
        | ligado    | []                       | o dashboard do /infra     | não aparece  | vazia = nenhum                       |
        | ligado    | ["admin"]                | o dashboard do /admin     | aparece      | painel que nunca o teve              |
        | ligado    | ["admin"]                | /admin/users              | aparece      | Page comum, não o dashboard          |
        | ligado    | ["admin"]                | o dashboard do /app       | não aparece  | fora da lista                        |
        | ligado    | ["app"]                  | o dashboard do /app       | aparece      | o /app na lista                      |
        | ligado    | ["admin","infra"]        | o dashboard do /infra     | aparece      | o segundo da lista                   |
        | ligado    | ["infra"]                | /infra/queue-monitors     | aparece      | Page comum do /infra                 |
        | ligado    | ["admin","infra"]        | o dashboard do /app       | não aparece  | fora de lista com dois               |
        | ligado    | ["App"]                  | o dashboard do /app       | não aparece  | id fora das opções (caixa)           |
        | ligado    | ["apps"]                 | o dashboard do /app       | não aparece  | id fora das opções (sufixo)          |
        | ligado    | ["infra-x","admin"]      | o dashboard do /infra     | não aparece  | id fora das opções (sufixo)          |
        | ligado    | ["infra-x","infra"]      | o dashboard do /infra     | aparece      | id fora não derruba o válido         |

    Esquema do Cenário: [CT-17] o chat nunca é renderizado em tela de autenticação
      Dado o uso ligado com a lista ["admin", "app", "infra"] e a página única de login ligada só nas linhas /login e /login/painel (com ela ligada, /admin/login e /admin/password-reset/request redirecionam para /login) *(alterado em 2026-10-19: causa (a) do lote B)*
      E <sessão>
      Quando a <tela> é aberta
      Então a resposta é 200
      E o chat do Assistente <chat> no corpo

      Exemplos:
        | tela                               | sessão                  | chat         | # linha                    |
        | /admin/screen/lock                 | o Bruno autenticado     | não aparece  | bloqueio, dentro do painel |
        | /admin/login                       | visitante               | não aparece  | login do painel            |
        | /admin/password-reset/request      | visitante               | não aparece  | layout do Auth Designer    |
        | /login                             | visitante               | não aparece  | página única de login      |
        | /login/painel                      | o Bruno autenticado     | não aparece  | escolha de painel          |
        | /admin                             | o Bruno autenticado     | aparece      | controle positivo          |
```

As linhas de id fora das opções são o ponto de **uso** do valor gravado por fora da tela (`gravarConfiguracao` + alinhamento): criação ≠ uso, e o gate de camada de R4. `/admin/users` e `/infra/queue-monitors` saem de `getUrl()` dos Resources, não de string fixa.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M33 | o hook do chat continua só no `AppPanelProvider` | CT-16 (linhas `/admin` ["admin"] e `/infra` ["admin","infra"]) | aparece; o mutante não renderiza fora do `/app` |
| M34 | lista vazia lida como "todos os painéis" | CT-16 (linhas `[]`) | não aparece; o mutante renderiza nos três |
| M35 | o render olha só a lista, ignorando o interruptor | CT-16 (linhas "desligado") | não aparece; o mutante renderiza |
| M36 | o render olha só o interruptor, ignorando a lista | CT-16 (linhas "fora da lista") | não aparece no `/app` com `["admin"]`; o mutante renderiza |
| M37 | o hook global não consulta a tela de autenticação (o chat no bloqueio, que é rota do painel) | CT-17 (linha `/admin/screen/lock`) | não aparece; o mutante renderiza |
| M38 | comparação frouxa do id (sem caixa, `str_starts_with`, `in_array` não estrito) ou id desconhecido estourando ao resolver o painel | CT-16 (linhas `App`, `apps`, `infra-x`) | não aparece e 200; o mutante renderiza por semelhança ou responde 500 |
| M58 | o chat renderizado só no dashboard (hook com escopo na página Dashboard) — revisão adversarial (ADV-06) | CT-16 (linhas `/admin/users` e `/infra/queue-monitors`) | aparece; o mutante só o desenha no dashboard |

---

## Regra R9 — Toda ação do chat responde 404 quando o uso é desligado depois do `mount`, sem consultar agente nem gravar, com um `warning` no canal `ai`

> `P-03` · perfil **completo** · técnica: **tabela de decisão** (verbo × motivo do desligamento) + **rastreio de efeito** (o `warning`: aconteceu, uma só vez; a direção "não aconteceu" está no CT-19)

O `Dado` põe no mundo tudo o que o caminho feliz afetaria: o `Assistente` e o `GuardaPrompt` com fake (seriam consultados), duas conversas da Ana (uma retomada, alvo de renomear e de retomar), a pergunta pendente válida (o `responder` a enviaria). O verbo irmão não herda evidência: os cinco verbos estão em cada motivo.

```gherkin
  Regra: com o uso desligado no painel corrente, toda ação do chat aberto responde 404 e nada vai ao agente nem é gravado

    Esquema do Cenário: [CT-18] o chat montado com o uso ligado recusa a ação depois que o uso é desligado no painel
      Dado o uso ligado com a lista ["app"] e a Ana no /app com o chat montado
      E a conversa "Plano de férias" da Ana retomada no chat, e a conversa "Viagem" dela gravada
      E depois do mount <motivo>
      Quando a Ana chama <verbo> com <argumentos>
      Então a resposta é 404
      E o Assistente e o GuardaPrompt nunca foram consultados
      E o total de conversas e de mensagens é o mesmo de antes, e os títulos continuam "Plano de férias" e "Viagem"
      E a conversa aberta no chat continua "Plano de férias" (observada no banco: depois do `abort(404)` o harness do Livewire perde o snapshot do componente, então `conversaId` não é legível — nota do lote B)
      E o canal ai recebeu um warning, uma vez

      Exemplos:
        | motivo                          | verbo             | argumentos                                  |
        | o uso é desligado               | enviar            | mensagem "Qual é o prazo?"                  |
        | o uso é desligado               | responder         | pergunta pendente "Qual é o prazo?"         |
        | o uso é desligado               | novaConversa      | —                                           |
        | o uso é desligado               | retomarConversa   | id da conversa "Viagem"                     |
        | o uso é desligado               | renomearConversa  | id da conversa "Viagem", título "Renomeada" |
        | o /app sai da lista (["admin"]) | enviar            | mensagem "Qual é o prazo?"                  |
        | o /app sai da lista (["admin"]) | responder         | pergunta pendente "Qual é o prazo?"         |
        | o /app sai da lista (["admin"]) | novaConversa      | —                                           |
        | o /app sai da lista (["admin"]) | retomarConversa   | id da conversa "Viagem"                     |
        | o /app sai da lista (["admin"]) | renomearConversa  | id da conversa "Viagem", título "Renomeada" |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M39 | a condição só no render/`mount` (o botão some no próximo carregamento, a ação do chat já aberto passa) | CT-18 (todas as linhas) | 404; o mutante executa a ação (consulta o agente, renomeia, troca a conversa) |
| M40 | um verbo sem a guarda — `novaConversa` ou `renomearConversa`, que não falam com o agente | CT-18 (linhas `novaConversa`, `renomearConversa`) | 404, título "Viagem" e conversa aberta "Plano de férias"; o mutante zera `conversaId` ou grava "Renomeada" |
| M41 | a recusa vem depois do guardrail ou depois de gravar a pergunta (guarda no meio do `responder`) | CT-18 (linha `responder`) | `GuardaPrompt` nunca consultado e mensagens iguais; o mutante já consultou o classificador ou gravou a mensagem |
| M42 | 403 no lugar de 404 (reaproveita o ramo do visitante) | CT-18 | status `404`; o mutante entrega `403` |
| M43 | a recusa sem trilha, ou com dois `warning` (o novo e o de posse) | CT-18 | `warning` no canal `ai` exatamente uma vez; o mutante entrega zero ou dois |

---

## Regra R10 — "Desligado no painel corrente" cobre lista vazia, id fora das opções e painel nulo; com o painel na lista, a ação segue em qualquer painel

> `P-03`, `P-01`, `RQ-08` · perfil **completo** · técnica: **EP** das partições de "desligado no painel corrente" + **rastreio de efeito** na direção "não aconteceu quando não devia"

```gherkin
  Regra: a ação do chat só segue com o uso ligado e o painel corrente na lista

    Esquema do Cenário: [CT-19] cada forma de o painel corrente ficar fora da lista recusa a pergunta, e o painel na lista a responde
      Dado o uso ligado com a lista <lista>, <pessoa> no <painel> com o chat montado e a pergunta pendente "Qual é o prazo?"
      E depois do mount <mudança>
      Quando <pessoa> chama responder
      Então a resposta é <resposta>
      E o Assistente <consulta>
      E o total de conversas de <pessoa> é <conversas>
      E o canal ai recebeu <warnings> warning de recusa

      Exemplos:
        | lista     | pessoa  | painel  | mudança                                                 | resposta | consulta                             | conversas | warnings |
        | ["app"]   | a Ana   | /app    | a lista é esvaziada                                     | 404      | nunca foi consultado                 | 0         | 1        |
        | ["app"]   | a Ana   | /app    | a lista passa a ["App"]                                 | 404      | nunca foi consultado                 | 0         | 1        |
        | ["app"]   | a Ana   | /app    | a lista passa a ["apps"]                                | 404      | nunca foi consultado                 | 0         | 1        |
        | ["app"]   | a Ana   | /app    | não há painel corrente (lista ["admin","app","infra"])  | 404      | nunca foi consultado                 | 0         | 1        |
        | ["app"]   | a Ana   | /app    | nada muda (controle positivo)                           | sem erro | foi consultado com "Qual é o prazo?" | 1         | 0        |
        | ["admin"] | o Bruno | /admin  | nada muda (controle positivo no painel novo)            | sem erro | foi consultado com "Qual é o prazo?" | 1         | 0        |
```

O "nunca foi consultado" também vale para o `GuardaPrompt`. As linhas de controle afirmam a conversa gravada da pessoa (`participant_id` = ela) e nenhum `warning` no canal `ai`. Painel corrente no teste: `Filament::setCurrentPanel('app')` / `Filament::setCurrentPanel('admin')` (em produção, o `SetUpPanel` persistente — ver G6).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M44 | lista vazia na guarda da ação lida como "todos" (divergente do render) | CT-19 (linha "esvaziada") | 404 e 0 conversas; o mutante responde e grava 1 |
| M45 | painel nulo abre (`$painel?->getId()` nulo comparado frouxo, ou `true` sem painel) | CT-19 (linha "não há painel corrente") | 404; o mutante responde |
| M46 | comparação frouxa entre id gravado e painel (sem caixa, por prefixo, `in_array` não estrito) | CT-19 (linhas `["App"]` e `["apps"]`) | 404; o mutante aceita |
| M47 | a guarda recusa sempre (inverte a condição) | CT-19 (linhas de controle) | consultado com "Qual é o prazo?", 1 conversa, 0 `warning`; o mutante recusa com 404 |
| M57 | a guarda da ação compara com `app` fixo (herança do chat exclusivo do `/app`) — revisão adversarial (ADV-04) | CT-19 (linha do Bruno no `/admin`) | consultado e 1 conversa do Bruno; o mutante responde 404 |

---

## Regra R11 — O grupo de widgets "Agentes de IA" do dashboard do `/admin` segue o interruptor sem perder dado; as trilhas de IA do `/infra` não mudam

> `RQ-09` (Adendo 1; era a P-02), Fora de Escopo ("trilhas do `/infra` sobre IA continuam como hoje") · perfil **mínimo** · técnica: **EP** + **2-switch**. Estouro do teto (2 cenários contra 1): o único matador de M49 está no `/infra`, outro painel e outro `Quando` — o gate vence o teto

```gherkin
  Regra: com o uso desligado, o grupo de widgets Agentes de IA do dashboard do /admin não é exibido, e desligar não apaga nada

    Esquema do Cenário: [CT-20] os dois widgets somem com o uso desligado e voltam ao religar, com os agentes intactos
      Dado o administrador geral e o catálogo com os agentes "assistente" e "guardaprompt", ativos
      E <histórico>
      Quando ele abre o dashboard do /admin
      Então os widgets "Agentes de IA" e "Agentes por provedor" estão <widgets>
      E o catálogo continua com 2 agentes, os dois ativos

      Exemplos:
        | histórico                                                           | widgets   |
        | o uso desligado                                                     | ausentes  |
        | o dashboard já aberto com o uso desligado, e o uso religado com []  | presentes |
        | o uso ligado com ["admin"]                                          | presentes |

    Esquema do Cenário: [CT-21] com o uso desligado, as trilhas de IA do /infra continuam
      Dado o uso desligado
      E a Íris no /infra
      Quando ela abre <tela>
      Então <resultado>

      Exemplos:
        | tela                               | resultado                                                    |
        | o dashboard do /infra              | o widget de estatísticas de IA está no corpo                 |
        | a lista de execuções de IA         | a resposta é 200 com a Page da lista de execuções no corpo   |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M48 | só um dos dois widgets segue o interruptor | CT-20 (linha 1) | os dois ausentes; o mutante deixa um |
| M49 | desligar esconde também as trilhas de IA do `/infra` | CT-21 | `IaStats` presente e lista 200; o mutante esconde/403 |
| M50 | desligar marca os agentes inativos (ou os apaga) para "desligar de verdade" | CT-20 (linhas 1 e 2) | 2 agentes, os dois ativos; o mutante deixa 0 ou `ativo` false |

---

## Regra R13 — O histórico do chat é da pessoa: aparece em qualquer painel da lista, só o dela, e sobrevive à saída e volta do painel

> `P-07` (Q9), `P-01`, `P-03`, Fora de Escopo ("nada é apagado") · perfil **completo** · técnica: **EP** (painel × dono da conversa) + **2-switch** (o painel sai da lista e volta). Regra nascida da revisão adversarial (ADV-05, ADV-22, ADV-23)

```gherkin
  Regra: as conversas do chat são da pessoa, em qualquer painel da lista, e só as dela

    Cenário: [CT-23] com organizações ligadas, o chat no /admin abre e lista só as conversas de quem está nele
      Dado a instalação com organizações ligadas e o uso ligado com a lista ["admin"]
      E a conversa "Revisão do contrato" do Bruno (admin) e a conversa "Pedido da Acme" da Carla, pessoa de uma organização
      Quando o Bruno abre o /admin
      Então a resposta é 200 com o chat do Assistente no corpo
      E o histórico do chat lista "Revisão do contrato" e não lista "Pedido da Acme"

    Cenário: [CT-24] a conversa criada no /app aparece no chat do /admin da mesma pessoa
      Dado o uso ligado com a lista ["admin", "app"]
      E a conversa "Viagem" que o Bruno criou pelo chat do /app, e a conversa "Plano de férias" da Ana
      Quando o chat é montado para o Bruno no /admin
      Então o histórico do chat lista "Viagem"
      E não lista "Plano de férias"

    Cenário: [CT-26] o /app sai da lista e volta, e a Ana retoma a conversa com as mensagens de antes
      Dado o uso ligado com a lista ["app"] e a conversa "Plano de férias" da Ana com uma pergunta e uma resposta gravadas
      E a lista passou a ["admin"], o retomar da Ana no /app respondeu 404, e depois a lista voltou a ["app"]
      Quando a Ana retoma "Plano de férias" no chat do /app
      Então a conversa aberta no chat é "Plano de férias"
      E as duas mensagens anteriores estão nela, na ordem em que foram gravadas
```

CT-23 vive em `tests/Tenancy/AgentesIaNoSettingsTenancyTest.php` (`Tests\TenancyTestCase`): GET do `/admin` para o render e `Livewire::test(AssistenteChatWidget::class)` com `Filament::setCurrentPanel('admin')` para o histórico — a Carla é a persona de controle, com conversa real no banco. CT-24 e CT-26 vivem em `tests/Kit/AgentesIaNoSettingsTest.php`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M64 | recusar a ação com o painel fora da lista fecha ou apaga a conversa (limpeza ao desligar) | CT-26 | conversa aberta "Plano de férias" com as 2 mensagens; o mutante não acha a conversa ou a abre vazia |
| M65 | histórico separado por painel (filtra conversas pelo painel em que nasceram) | CT-24 | "Viagem" listada no `/admin`; o mutante a esconde |
| M66 | com organizações ligadas e sem organização corrente no `/admin`, o recorte do histórico fica nulo e abre para todos | CT-23 | "Pedido da Acme" não listada; o mutante a lista |
| M67 | o chat no `/admin` com organizações ligadas estoura por depender de organização corrente | CT-23 | 200 com o chat; o mutante responde 500 |

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | conversas: CT-23 e CT-24 (histórico só do dono, em outro painel e com organizações); a posse por id é do `AssistenteChatWidgetTest` CT-22, que o lote T0 liga. Catálogo: a única rota com `{id}` é o editar, recusado inteiro com o uso desligado (CT-11) e coberto pela policy com o uso ligado (CT-13) | G6, G9, G3 |
| Autorização exercida na ação (não só `can()`) | CT-11, CT-13, CT-18; catálogo em tela já aberta: CT-22 (formulários e lista) | G3, G6 |
| Autorização por fora do componente de UI | CT-11, CT-13, CT-15 (HTTP); chat sem rota própria — desvio declarado em `## Costuras de Teste` | G3 |
| Idempotência (ancorada no agregado) | não se aplica: gravar configuração substitui o valor (CT-07 afirma o valor gravado); a feature não acumula agregado | — |
| Concorrência | não se aplica: sem contador, saldo ou limite | — |
| **Fronteira no ponto de entrada** (gravação) | CT-08 (tela), CT-25 (salvar outro campo), CT-02 (semente da migration), CT-16 linhas `App`/`apps`/`infra-x` (uso do valor gravado por fora) | G2, G1, G5 |
| **Domínio condicionado** (tipo × valor) | não se aplica: a lista é válida com o uso ligado ou desligado (o `00` não condiciona uma à outra; desligar mantém a lista — CT-07) | — |
| **Estado × operação** | matriz única em `## Mapa de Regras`: 24 células, todas com CT | G1–G7, G9 |
| Cardinalidade da lista (0 / 1 / N) | CT-16 (`[]`, `["admin"]`, `["admin","infra"]`), CT-07, CT-04 (linha `[]`) | G5, G2, G1 |
| Ausente ≠ null ≠ vazio | CT-03 (bloco ausente, lista nula), CT-16 (lista `[]`), CT-01 (variáveis ausentes) | G1, G5 |
| Paginação / ordenação | não se aplica: a feature não cria listagem (a ordenação do CT-22 é só o gatilho do update Livewire) | — |
| Timezone / DST | não se aplica: sem dado temporal | — |
| Unicode / limite de varchar | normalização do id de painel: CT-08, CT-16 e CT-19 com `App` (caixa) e `apps`/`infra-x` (sufixo) | G2, G5, G6 |
| Unicidade + soft delete | não se aplica: a feature não cria entidade | — |
| CRUD combinado | CT-14 (criar e editar com o uso ligado), CT-11 (os três verbos de leitura com o uso desligado), CT-22 (ações com o uso desligado) | G3 |
| Mass assignment | não se aplica: a SettingsPage grava só o que o schema devolve — `vendor/filament/spatie-laravel-settings-plugin/src/Pages/SettingsPage.php:save():72` (`$this->form->getState()`) e `:save():82` (`$settings->fill($data)`); a feature não acrescenta model nem `$fillable`, e nenhum campo novo é de autorização | — |
| Upload | não se aplica: sem upload | — |
| Precisão monetária | não se aplica: sem valor monetário | — |
| **Superfície Livewire** (método público, prop pública) | ações do chat (5 métodos) com o uso desligado: CT-18; `$mensagemPendente` por `set` com o uso desligado: CT-18 linha `responder`; `$data` da SettingsPage com valor fora do domínio e tipo errado: CT-08, e sem a aba carregada: CT-25; `mount`/hydrate das Pages do catálogo: CT-11 (mount), CT-22 (hydrate nas três Pages); `$aberto`, `$indisponivel`, `$recusa`: não se aplica — só exibição, nada decide por eles (`02`) | G6, G2, G3 |
| **Estado do framework usado sem validar** | não se aplica: a feature não consome `$filters`, `$tableFilters`, `$tableSearch` nem `$tableSortColumn` (`02`, "O framework") | — |
| **IDOR por entidade** (por tabela persistida) | `settings` (2 propriedades): não se aplica — configuração da instalação, sem dono; `agentes_ia`: CT-11 / CT-13 / CT-22; `agent_conversations`/`agent_conversation_messages`: CT-18 (nada gravado), CT-23/CT-24 (histórico só do dono) e `AssistenteChatWidgetTest` CT-22 (posse) | G3, G6, G9 |
| **Escopo com discriminante nulo** (fecha ou abre?) | fecha: CT-03 (config nula), CT-19 (painel corrente nulo), CT-23 (organização corrente nula no `/admin`) | G1, G6, G9 |
| **Saída do estado de erro** (4xx tem destino) | CT-12 (depois do 404 do catálogo, o `/admin` abre); 404 do chat: o botão some no próximo carregamento — CT-16 linhas "desligado"; e a conversa volta ao religar — CT-26 | G3, G5, G6 |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | a config de fábrica desliga o uso e não escolhe painel | R1 | EP | G1 | Pest feature HTTP | `tests/Kit/AgentesIaNoSettingsTest.php` | M1, M2, M3, M63 |
| CT-02 | a migration semeia o banco com o que o ambiente diz | R1 | EP | G1 | Pest feature HTTP | `tests/Kit/AgentesIaNoSettingsTest.php` | M4, M5 |
| CT-03 | config sem o bloco ou lista nula falha fechado | R2 | EP (nulo) | G1 | Pest feature HTTP | `tests/Kit/AgentesIaNoSettingsTest.php` | M6, M7, M8, M56 |
| CT-04 | depois do boot, o gravado decide, não o `.env` | R3 | tabela de decisão | G1 | Pest feature HTTP | `tests/Kit/AgentesIaNoSettingsTest.php` | M9, M10, M11, M55 |
| CT-05 | o request seguinte obedece à mudança no mesmo processo | R3 | 2-switch | G1 | Pest feature HTTP | `tests/Kit/AgentesIaNoSettingsTest.php` | M12 |
| CT-06 | ligar/desligar não muda as consultas ao settings, e a feature não o consulta | R3 | contagem com controle | G1 | Pest feature HTTP | `tests/Kit/AgentesIaNoSettingsTest.php` | M13 |
| CT-07 | o administrador grava o uso e os painéis pela tela, sem apagar nada | R4 | EP | G2 | componente Livewire/Filament | `tests/Kit/AgentesIaNoSettingsTest.php` | M17, M18, M62 |
| CT-08 | valor fora do domínio é recusado e nada é gravado | R4 | EP (inválidas isoladas) | G2 | componente Livewire/Filament | `tests/Kit/AgentesIaNoSettingsTest.php` | M14, M15, M16 |
| CT-09 | cada superfície de navegação lista o catálogo só com o uso ligado | R5 | EP por superfície | G4 | componente Livewire/Filament | `tests/Kit/AgentesIaNoSettingsTest.php` | M19, M20, M21, M23 |
| CT-10 | o favorito do dock fica oculto, sobrevive ao slideover e volta | R5 | 2-switch | G4 | componente Livewire/Filament | `tests/Kit/AgentesIaNoSettingsTest.php` | M22, M23, M60 |
| CT-11 | lista, criar e editar respondem 404 com o uso desligado | R6 | matriz papel × ação | G3 | Pest feature HTTP | `tests/Kit/AgentesIaNoSettingsTest.php` | M24, M25, M26 |
| CT-12 | depois do 404, o `/admin` continua abrindo, só sem o link | R6 | saída do erro | G3 | Pest feature HTTP | `tests/Kit/AgentesIaNoSettingsTest.php` | M27 |
| CT-13 | com o uso ligado, a permissão decide como hoje | R7 | matriz papel × ação | G3 | Pest feature HTTP | `tests/Kit/AgentesIaNoSettingsTest.php` | M28, M29, M30 |
| CT-14 | com o uso ligado, o formulário do catálogo grava | R7 | gate de tela de escrita | G3 | componente Livewire/Filament | `tests/Kit/AgentesIaNoSettingsTest.php` | M31 |
| CT-15 | o catálogo continua só no `/admin` | R7 | EP | G3 | Pest feature HTTP | `tests/Kit/AgentesIaNoSettingsTest.php` | M32 |
| CT-16 | a lista e o interruptor decidem onde o chat é renderizado | R8 | tabela de decisão completa | G5 | Pest feature HTTP | `tests/Kit/AgentesIaNoSettingsChatTest.php` | M33, M34, M35, M36, M38, M58 |
| CT-17 | o chat nunca é renderizado em tela de autenticação | R8 | EP | G5 | Pest feature HTTP | `tests/Kit/AgentesIaNoSettingsChatTest.php` | M37 |
| CT-18 | o chat montado recusa a ação depois que o uso é desligado | R9 | tabela de decisão + rastreio de efeito | G6 | componente Livewire/Filament | `tests/Kit/AgentesIaNoSettingsChatTest.php` | M39, M40, M41, M42, M43 |
| CT-19 | cada forma de ficar fora da lista recusa; na lista, responde em qualquer painel | R10 | EP + rastreio de efeito | G6 | componente Livewire/Filament | `tests/Kit/AgentesIaNoSettingsChatTest.php` | M44, M45, M46, M47, M57 |
| CT-20 | os widgets somem e voltam, com os agentes intactos | R11 | EP + 2-switch | G7 | Pest feature HTTP | `tests/Kit/AgentesIaNoSettingsChatTest.php` | M48, M50 |
| CT-21 | as trilhas de IA do `/infra` continuam com o uso desligado | R11 | EP | G7 | Pest feature HTTP | `tests/Kit/AgentesIaNoSettingsChatTest.php` | M49 |
| CT-22 | a tela do catálogo aberta recusa a ação depois que o uso é desligado | R6b | estado × evento + rastreio de efeito | G3 | componente Livewire/Filament | `tests/Kit/AgentesIaNoSettingsTest.php` | M51, M52, M53, M54, M59 |
| CT-23 | com organizações, o chat no `/admin` abre e lista só as conversas de quem está nele | R13 | EP (painel × dono) | G9 | Pest feature HTTP + componente Livewire/Filament | `tests/Tenancy/AgentesIaNoSettingsTenancyTest.php` | M66, M67 |
| CT-24 | a conversa criada no `/app` aparece no chat do `/admin` da mesma pessoa | R13 | EP (painel × dono) | G6 | componente Livewire/Filament | `tests/Kit/AgentesIaNoSettingsChatTest.php` | M65 |
| CT-25 | salvar outro campo da tela não apaga o uso nem a lista | R4 | EP | G2 | componente Livewire/Filament | `tests/Kit/AgentesIaNoSettingsTest.php` | M61 |
| CT-26 | o `/app` sai da lista e volta, e a conversa volta com as mensagens | R13 | 2-switch | G6 | componente Livewire/Filament | `tests/Kit/AgentesIaNoSettingsChatTest.php` | M64 |
| CT-B01 | o chat entra na pilha no painel que nunca o teve | R12 | EP | G8 | browser | `tests/Browser/AgentesIaNoSettingsTest.php` | ver `05` |

## Tabela de cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| chat no `/app` com organização (suíte Tenancy) | a decisão é pelo id do painel, igual com e sem organização; o lote T0 já liga `['app']` no `TelasSemDockNemChatTenancyTest` CT-10, que renderiza o chat no `/app/{organização}`. A suíte Tenancy desta feature cobre o painel que muda (`/admin`, CT-23) |
| gravado pela tela vale no request seguinte (salvar → alinhar → GET) | mata o mesmo conjunto que CT-07 (gravação) + CT-04 (banco → config → efeito) |
| ida e volta do chat no navegador no `/admin` (enviar → resposta) | o update Livewire recebe o painel pelo `SetUpPanel` persistente (`vendor/filament/filament/src/FilamentServiceProvider.php:packageBooted():128`), e CT-18/CT-19 já exercitam as ações com o painel corrente; o browser não mataria mutante novo |
| `KIT_AGENTES_IA=talvez` no ambiente | o `00` fixa só o default (RQ-07); valor ilegível não tem cláusula. A semente passa pelo settings tipado (`bool`) |
| `warning` no canal `ai` na recusa do catálogo | log sem cláusula no `00` (Proibição 12) |
| widget do catálogo montado direto por Livewire com o uso desligado, ou polling depois de desligar | RQ-09 fala de exibição no dashboard (CT-20); montar o widget fora do dashboard não tem cláusula |
| widget do catálogo desenhado pelo dashboard dinâmico sem `canView()` (ADV-12) | rejeitado na triagem: se o pacote ignora `canView()`, já ignora a permissão do widget — defeito da feature `dashboard-dinamico`, fora desta; a sessão registra como nota |
| visita GET da tela de configurações (par da regra "uma tela aberta…") | a visita já é do `ConfiguracoesDoKitTelaTest` ("mostra a tela de configuracoes…"); a existência dos dois campos é afirmada por `chaveDoCampo()` no CT-07/CT-08 |

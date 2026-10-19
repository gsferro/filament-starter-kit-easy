# Progresso — Agentes de IA ligados pelo settings, por painel

**Estado**: em implementação

## Processo do pedido

<!-- Os quatro itens de processo do Texto Original, que não são cláusulas do sistema. -->

- [x] Branch criada — `git checkout -b feat/agentes-ia-no-settings` a partir de `main` (`27e47714`), 2026-10-19
- [ ] `codex --yolo` usado como auxiliar
- [ ] PR aberto
- [ ] Tag + release criadas depois de o PR passar

## 1. Config `kit.agentes_ia` e `.env.example`
- [x] Bloco `agentes_ia` em `config/kit.php` — `php artisan tinker --execute 'echo json_encode(config("kit.agentes_ia"));'` → `{"habilitado":false,"paineis":[]}`; `php -l` sem erro, 2026-10-19
- [x] `KIT_AGENTES_IA` e `KIT_AGENTES_IA_PAINEIS` no `.env.example` — `grep -n KIT_AGENTES_IA .env.example` → 2 linhas (false; comentada), 2026-10-19

## 2. Settings: duas propriedades e a migration
- [x] Propriedades e linhas do mapa em `ConfiguracoesDoKit` — `php -l` sem erro; `vendor/bin/phpstan analyse` 0 erros, 2026-10-19
- [x] Migration `2026_10_19_100000_add_agentes_ia_to_kit_settings.php` — `vendor/bin/phpstan analyse` 0 erros; semeadura coberta por `ConfiguracoesDoKitTest` ('semeia todas as propriedades que a classe de settings declara'), 2026-10-19

## 3. Decisor `App\Support\AgentesDeIa`
- [x] `habilitado()`, `assistenteNoPainel()` e `chatNaTela()` — `app/Support/AgentesDeIa.php`, `vendor/bin/phpstan analyse` 0 erros, 2026-10-19

## 4. Tela de configurações: seção "Agentes de IA"
- [x] Seção na aba Aplicação com toggle e select — `vendor/bin/filacheck --fix` "All 17 rules passed"; `vendor/bin/pint --dirty` passed, 2026-10-19

## 5. Catálogo: some do menu e responde 404
- [x] `canAccess()` e `shouldRegisterNavigation()` no Resource — `vendor/bin/filacheck --fix` 17/17; phpstan 0 erros, 2026-10-19
- [x] Trait `ExigeAgentesDeIaLigados` nas três Pages — `grep -l ExigeAgentesDeIaLigados app/Filament/Admin/Resources/AgentesIa/Pages/*.php` → 3 arquivos, 2026-10-19

## 6. Widgets do catálogo seguem o interruptor
- [x] `fonteDeDadosDisponivel()` nos dois widgets — `grep -c fonteDeDadosDisponivel app/Filament/Admin/Widgets/AgentesIa*.php` → 1 em cada, 2026-10-19

## 7. Chat do Assistente por painel
- [x] Hook global em `ConfiguraFilamentGlobal::configuraChatDoAssistente()` — phpstan 0 erros; `php artisan about` boota, 2026-10-19
- [x] Hook removido do `AppPanelProvider` — `grep -c assistente-chat-widget app/Providers/Filament/AppPanelProvider.php` → 0 (resta só o comentário que aponta o hook global); Pint removeu o import `Blade`, 2026-10-19
- [x] Guarda em `AssistenteChatWidget::assertContexto()` — phpstan 0 erros, 2026-10-19
- [x] Recuo do "voltar ao topo" por `AgentesDeIa::chatNaTela()` na blade — `grep -n chatNaTela resources/views/filament/voltar-ao-topo.blade.php` → 2 ocorrências (comentário e `$recuoDoBotao`), 2026-10-19

## 8. Lote T0: suíte com o uso desligado e o helper de teste
- [x] `ligarAgentesDeIa()` em `tests/Pest.php` (com `MEDIR_PILHA_FLUTUANTE` e `abrirPainelDaPilha()` movidos, D11) — `php -l tests/Pest.php` sem erro; pint fixed, 2026-10-19
- [x] Casos existentes ligando o uso no arranjo (14 arquivos + `phpunit.xml`, D10) — `php artisan test --compact` em 24 arquivos da Kit (T0 + settings + inventários): 872 testes, 870 verdes, 2 vermelhos em `AssistenteChatCssTest` CT-01 (faltava ligar o uso — corrigido; o arquivo rerodado dá 6/6); `tests/Tenancy/TelasSemDockNemChatTenancyTest.php` + `DockDeAtalhosTenancyTest.php` 46/46, 2026-10-19

## 9. Documentação
- [x] docs pt/en (configurações, atualizando), `wikis/ia.md`, READMEs (bullet da IA e contagem 84 → 85), CHANGELOG — `php artisan test tests/Kit/*Documentacao*Test.php tests/Kit/SiteDeDocumentacaoTest.php` → 124/124 verdes, 2026-10-19

## Testes
<!-- Preenchida no step 7, depois da derivação do 04/05. -->
- [x] `tests/Kit/AgentesIaNoSettingsTest.php` (CT-01 a CT-15, CT-22, CT-25) — `php artisan test --compact tests/Kit/AgentesIaNoSettingsTest.php` 73/73 (278 asserções) depois da correção da causa (b) do CT-11, 2026-10-19
- [x] `tests/Kit/AgentesIaNoSettingsChatTest.php` (CT-16 a CT-21, CT-24, CT-26) — `vendor/bin/pest tests/Kit/AgentesIaNoSettingsChatTest.php --compact` 46/46 (239 asserções), despacho 5, 2026-10-19
- [x] `tests/Tenancy/AgentesIaNoSettingsTenancyTest.php` (CT-23) — `vendor/bin/pest tests/Tenancy/AgentesIaNoSettingsTenancyTest.php --compact` 1/1, 4 asserções (despacho 6), 2026-10-19
- [x] `tests/Browser/AgentesIaNoSettingsTest.php` (CT-B01) — `vendor/bin/pest tests/Browser/AgentesIaNoSettingsTest.php --compact` 2/2 (34 asserções) rerodado pela sessão com os helpers em `tests/Pest.php`; `PilhaDeBotoesFlutuantesTest` + `AssistenteChatEstiloTest` 27/27 na mesma rodada, 2026-10-19
- [ ] `tests/Pest.php` — `ligarAgentesDeIa()` (D7) e os helpers da pilha movidos (D11)

## Tickets
<!-- Step 8. -->
Não fatiado — 2026-10-19: 9 RQ vigentes (+6 P-nn vigentes), 26 CT + 1 CT-B, compactação: não, 7 perguntas de requisito — nenhum sinal

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/filacheck --fix`
- [ ] `vendor/bin/pest tests/Kit/AgentesIaNoSettingsTest.php --compact`
- [ ] `vendor/bin/pest tests/Browser --filter=AgentesIaNoSettings` <!-- se houver CT-B -->
- [ ] `php artisan test --testsuite=Kit,Tenancy --parallel`: nada mais no suite quebrou além da **baseline** de `main` (falhas pré-existentes por nome)
- [ ] `pest --mutate --path=app/Support/AgentesDeIa.php`: score, duração e sobreviventes listados (no Windows, amostragem manual)
- [ ] **Custo medido**: consultas de `GET /admin` com o uso ligado × desligado, contra o `## Modelo de Execução`
- [ ] **`/code-review high main...HEAD` + passe de eixos (step 9)**, antes da reconciliação: achados fechados ou rejeitados com motivo
- [ ] Roteiro "Desenhado × Implementado" do `05-*-browser.md` preenchido <!-- se houver CT-B -->
- [ ] Desvios propagados ao `01`/`02`/`04`/`05` de origem, marcados `*(alterado em …)*`
- [ ] `rastreabilidade.sh {wiki}` silencioso
- [ ] `checkbox-sem-evidencia.sh {wiki}` silencioso
- [ ] Citações `arquivo:símbolo:linha` reverificadas: `citacoes.sh {wiki}` silencioso
- [ ] IDs `[CT-nn]` do teste ⊆ `04`/`05` e vice-versa: `ids-ct.sh {wiki} 'tests/**/AgentesIaNoSettings*.php'` silencioso
- [ ] Rules casadas pelo diff com linha em `## Conformidade com Rules`: `conformidade-rules.sh {wiki} main` silencioso
- [ ] Falsificabilidade em cópia temporária, revertendo só o patch do fix: quantos falham sem ele
- [ ] Docs pt/en, CHANGELOG e README reconciliados com o comportamento final
- [ ] `php artisan kit:info` lista as duas propriedades novas
- [ ] Adendo 1 incorporado (RQ-09: grupo de widgets "Agentes de IA" some com o uso desligado — `04` re-derivado antes do teste)
- [ ] `git commit`

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|

## Quality Gate

<!-- Preenchido no step 11. Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

- **Ciclo**: — · **Veredito**: — · **Data**: —
- **Relatório**: `06-relatorio-qa.md`
- **Débito**: —

## Candidatos a Rule

<!-- Step 12. -->

## Auditoria Pré-Implementação

Entendimento confirmado: 2026-10-19 — autorização existente no pedido (execução autônoma: "ao finalizar, abra o PR, quando passar, crie a nova tag + release") — 2 rodadas (step 4 e step 7); perguntas: 0 fato ao usuário, 2 desenho (Q5, Q7, decididas pela sessão sob convenção — D10, D11; D1–D9 idem), 6 requisito (Q1–Q4, Q6, Q8 em `## Perguntas ao Solicitante` do `00`; Q1–Q4 e Q6 seguidas como premissas P-01 a P-04 e P-06; Q8 sem bloqueio)

### Confronto código × afirmação (step 5)
| Pergunta | O `01` dizia | O código faz | Resposta (quem, data) | Onde a wiki mudou |
|---|---|---|---|---|

### Revisão profunda (step 5) — premissas do plano contra o código real
| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| 29 citações `arquivo:símbolo:linha` do `01` e 13 do `02` | `citacoes.sh` acusou 4 fora do formato (`Classe::método`), nenhuma linha errada | reescritas; `citacoes.sh` exit 0, 2026-10-19 |
| passo 7: "a pilha já detecta o chat pelo DOM" (Codex, despacho #1) | o DOM decide só a faixa de colisão com o dock (`resources/views/filament/voltar-ao-topo.blade.php:temChat:164`); o recuo do botão é `bottom-24` **se o painel é o `/app`** (`resources/views/filament/voltar-ao-topo.blade.php:$recuoDoBotao:97`) — chat no `/admin`/`/infra` sobreporia o botão; chat desligado no `/app` deixaria 4,5 rem vazios | passo 3 ganha `AgentesDeIa::chatNaTela()`; passo 7 troca a condição da blade; T0 ganha `VoltarAoTopoTest` e `AssistenteChatCssTest` |
| passo 8: "`KitInfoTest` absorve as propriedades sem edição" (Codex) | `tests/Kit/KitInfoTest.php:toHaveCount():191` fixa a contagem; CT-07 tem dataset explícito | passo 8: 69 → 71 com a linha de histórico |
| passo 7: "toda ação do chat responde 404" (P-03) (Codex) | `app/Livewire/AssistenteChatWidget.php:responder():90` devolve sem chamar `assertContexto()` quando não há pendência ou ela passa de 2000 — no-op que não alcança agente nem banco | mantido: o no-op continua sem 404; P-03 vale para toda ação com carga (a que alcançaria o agente ou o banco). Registrado aqui; o `04` decide o cenário |
| citação `TelasSemDockNemChatTest.php:'controle app':91` (Codex) | a linha 91 é `'controle admin'`; `'controle app'` está na 90 | citação corrigida no `01` |
| T0 lista `BoasVindasTest`, CT-11 e CT-15 como quebras (Codex) | comentário, round-trip do dock e render direto não passam por `assertContexto()` nem pelo hook | passo 8 reescrito: só os casos que de fato dependem |
| passo 1: `explode(',', (string) env('KIT_AGENTES_IA_PAINEIS', ''))` | `tests/Kit/TextoDoEnvTest.php:'nao reintroduz env com default de texto':69` reprova default de texto em `env()` fora da lista de isenção; o dock usa `env('KIT_DOCK_PAINEIS')` sem default (`config/kit.php:'dock':591`) | passo 1 do `01`: sem default, como o dock |

### Varredura da classe irmã (step 5)
<!-- uma linha por classe nova: irmã escolhida, ocorrências do FQCN dela e o que a nova herda -->

| Classe nova | Irmã | `grep -rnF` do FQCN da irmã (app, config, database, tests, routes, bootstrap) | A nova entra? |
|---|---|---|---|
| `App\Support\AgentesDeIa` | `App\Support\DockDeAtalhos` | `ConfiguracoesDoKit` (Page e Settings: docblocks/comentários), `PluginDoDock` (consumidor), `User` (favoritos), `Paineis` (comentário), `config/kit.php` (comentário do bloco), `tests/Kit/DockDeAtalhosTest.php` — nenhuma lista/inventário; só consumidores e comentários | só nos consumidores previstos (Resource, trait, widgets, hook, Page e Settings) e no comentário do bloco novo de `config/kit.php` |
| `App\Filament\Concerns\ExigeAgentesDeIaLigados` | `App\Filament\Concerns\ExigePermissaoDaTela` | 9 Pages (consumidoras), `App\Support\DockDeAtalhos` e `PermissaoDaTela` (avaliam o fail-open do trait), `tests/Kit/PermissoesDeTelasTest.php` (varre Pages com o trait) — nenhuma lista que enumere traits | não: o trait novo não é de permissão; `PermissoesDeTelasTest` varre Pages, não Resources |
| chave `KIT_AGENTES_IA_PAINEIS` | `KIT_DOCK_PAINEIS` / `KIT_DASHBOARD_DINAMICO_PAINEIS` | `tests/Kit/TextoDoEnvTest.php:'nao reintroduz env com default de texto':69` acusa `env('X', '')` fora da isenção; o dock escreve `env('KIT_DOCK_PAINEIS')` sem default | seguir o dock: `env('KIT_AGENTES_IA_PAINEIS')` sem default (sem linha na isenção) — corrigido no passo 1 do `01` |
| arquivos novos × `KitUpdate::CAMINHOS_DO_KIT` | — | `app/Support`, `app/Filament`, `database/settings`, `config/kit.php` já na lista; `tests/Kit/KitUpdateTest.php:'cobre todo o código do kit':165` varre | nada a acrescentar |

### Revisão adversarial (step 7)
`fw-adversario-ct` (opus, só `00` + `04` + `05` + glossário): 23 achados — ADV-01..ADV-11, ADV-13..ADV-23 aplicados ao `04` pela derivação (CT-03, CT-04, CT-06, CT-07, CT-08, CT-10, CT-12, CT-15, CT-16, CT-19, CT-22 alterados; CT-23 Tenancy, CT-24, CT-25, CT-26 novos; R13 nova; M55–M67); ADV-22 virou Q9 + P-07 no `00`. **ADV-12 rejeitado**: "o dashboard dinâmico desenha o widget sem consultar `canView()`" — se o pacote `mddev31/filament-dynamic-dashboard` ignorar `canView()`, ele já ignora a permissão `View:{Widget}` de todo widget do kit, defeito da feature `dashboard-dinamico-nos-paineis`, não desta; anotado em `## Notas de Implementação`.

### Auditoria Ponytail (step 6)
| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `shrink`: helper privado `paineis()` com filtro `is_string` no decisor — `in_array(..., (array) config(...), true)` inline, como o dock | sim | `01`, passo 3 |
| 2 | `delete`: reescrever o docblock do Resource — uma frase no existente basta | sim | `01`, passo 5 |
| 3 | Trait com 3 consumidores, hook global, helper de teste usado por 10 arquivos: lean | — | — |

Ponytail-review rodado em linha (skill `ponytail-review`, sem namespace de plugin neste projeto), 2026-10-19 — `net: -6 lines possible`.

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| 1 | 5 | `codex exec --yolo -s read-only` (auxiliar pedido pelo solicitante) — revisão profunda independente: cada premissa do `01` contra o código, testes que faltam no lote T0, listas paralelas | codex-cli 0.163.0, modelo da sessão do Codex (não exposto pelo host) | a conversa desta sessão; o `03` | tabela com 47 premissas: 42 sem divergência, 5 achados (recuo da pilha por id de painel; `KitInfoTest` com contagem fixa 69; `responder()` sem 404 no no-op; citação deslocada; T0 com referências incidentais) + 1 já corrigido (default de texto no `env()`) | tokens não reportados · ~4 min | `git status --porcelain` igual antes/depois (só a wiki untracked); 5/5 achados reproduzidos por `sed -n` (`resources/views/filament/voltar-ao-topo.blade.php:$recuoDoBotao:97`, `tests/Kit/KitInfoTest.php:toHaveCount():191`, `app/Livewire/AssistenteChatWidget.php:responder():90`, `tests/Kit/TelasSemDockNemChatTest.php:'controle app':90`); todos aplicados ao `01` |
| 2 | 7 | `general-purpose`/opus (fallback do `analista`, sem agente de projeto) — deriva o `04` (e o `05` só com costura browser) lendo e seguindo `feature-test-design/SKILL.md`; gravou os dois arquivos direto (desvio do "devolve como texto": 850 linhas; a sessão auditou antes de usar) | opus | `01` inteiro (só rotas, UI, env, mapeamentos e nomes), `02` fora da Superfície Livewire, `03`, a conversa e qualquer implementação (inexistente) | `04` com 11 regras, 21 CT, 50 mutantes (0 sem matador), 8 costuras propostas; `05` com CT-B01 (pilha no `/admin` e `/infra`); 4 perguntas (Q?1–Q?4 → Q5–Q8); revisão adversarial pendente | 280.876 tokens · 14 min 55 s | `git status --porcelain`: só `04` e `05` novos, `00`–`03` intactos; amostra: R6 (CT-11/CT-12, 404 com e sem permissão, matriz papel × ação) e R9 (CT-18, 5 verbos × 2 motivos, `assertNeverPrompted` + `warning` uma vez) conferidas contra o `00`; costuras confirmadas pela sessão sob convenção |
| 3 | 7 | `fw-adversario-ct` — revisão adversarial do `04`/`05` | opus | `01`, `02` (fora da Superfície Livewire — e nem ela), `03`, código, conversa | 23 achados (12 implementações que passam, 5 oráculos fracos, 4 malformados, 2 sondas) | 80.419 tokens · 3 min 40 s | hashes de `00`/`04`/`05`/glossário iguais antes e depois (`sha1sum`); 22 aceitos, 1 rejeitado com motivo (ADV-12) |
| 7 | 10 | `fw-executor-ctb` — `tests/Browser/AgentesIaNoSettingsTest.php` (CT-B01) | sonnet | `01` fora da Superfície de UI, `03`, código de aplicação | 2 linhas verdes (34 asserções) na 2ª iteração; 1ª vermelha por causa (a): o `05` não armava o dock no canto nem rolava a página; `05` corrigido pela sessão (passo 1b) e "Desenhado × Implementado" preenchido | 70.192 tokens · 2 min 55 s | `git status`: só o arquivo do lote (a sonda `ZzSondaTest.php` foi apagada); o agente reimplementou `moverDockDaPilha`/`rolarPaginaDaPilha` inline — a sessão os moveu para `tests/Pest.php` (D11) e o teste passou a chamá-los; rerodado pela sessão |
| 2b | 7 | mesmo agente do despacho 2 (`SendMessage`) — CT-22 (P-06) e depois os 22 achados + Adendo 1 | opus | idem | `04` com 26 CT, 13 regras, 67 mutantes, 0 sem matador | 291k + 371k tokens (acumulados) · 50 s + 5 min 43 s | `git status`: só o `04` mudou; contagens reconferidas por `grep -c` (26 / 13 / 67); amostra CT-22 e CT-23 lidas |
| 4 | impl. | `fw-executor-ct` lote A — `tests/Kit/AgentesIaNoSettingsTest.php` (CT-01..15, 22, 25) | sonnet | `01`, `02`, `03`; não edita `app/`, `database/migrations/`, `00`/`04`/`05`, `tests/Pest.php` | 17 `it()`, 73 linhas: 71 verdes + **2 vermelhas por causa (b)** — CT-11 `criar sem`/`editar sem` respondiam 403 em vez de 404 (o `mount()` de `CreateRecord`/`EditRecord` chama `authorizeAccess()` antes do hook do trait); 3 iterações; 6 ambiguidades do `04` resolvidas no arquivo (`.env.example` lido por `Dotenv::parse`, migration `up()` chamada, hub por reflexão, ⌘K pelas categorias) | 181.343 tokens · 9 min 14 s | `git status`: só o arquivo do lote untracked; vermelho (b) reproduzido pela sessão em `vendor/filament/filament/src/Resources/Pages/CreateRecord.php:mount():64` e corrigido no trait (`authorizeAccess()` sobrescrito); rerodado: 73/73 |
| 5 | impl. | `fw-executor-ct` lote B — `tests/Kit/AgentesIaNoSettingsChatTest.php` (CT-16..21, 24, 26) | sonnet | idem | 8 `it()`, 46 linhas de dataset verdes (239 asserções); 13 vermelhos na 1ª iteração, todos causa (a); causa (b): nenhuma; 2 correções do `04` devolvidas como texto (CT-17 `Dado` do login único; CT-18 `conversaId` ilegível depois do 404) e gravadas pela sessão | 132.325 tokens · 4 min 44 s | `git status`: só o arquivo do lote untracked; amostra: CT-18 com 5 verbos × 2 motivos, `assertNeverPrompted` nos dois agentes, `warning` once; CT-19 inclui `setCurrentPanel(null)`. Ressalva: o agente leu `AssistenteChatWidget.php` também para entender o estado pós-404 (contrato pede só nomes) — o `Então` continuou vindo do `04` |
| 6 | impl. | `fw-executor-ct` lote C — `tests/Tenancy/AgentesIaNoSettingsTenancyTest.php` (CT-23) | sonnet | idem | 1 teste verde, 4 asserções, causa b: nenhuma | 67.040 tokens · 1 min 3 s | `git status`: só o arquivo do lote untracked; amostra: `ligarAgentesDeIa(true, ['admin'])`, `Filament::setCurrentPanel('admin')`, controle da Carla presente |
| — | pré-9 | Sem despacho — re-varredura da Superfície Livewire feita em linha (2 greps do `pesquisa-step-3.md` sobre `git diff --name-only -- app resources`): 0 métodos públicos de instância novos, 0 propriedades públicas novas; linhas do `02` atualizadas | — | — | — | — | — |
| — | 0–4 | Sem despacho — captura verbatim do requisito, decomposição, entrevista e escrita de `00`–`03` são da sessão; pesquisa feita em linha por `grep`/`sed` (feature pequena: 2 propriedades, 1 decisor, 1 trait, 1 hook) | — | — | — | — | — |

## Blockers

## Desvios do Plano
- Passo 5 (trait `ExigeAgentesDeIaLigados`): além de `authorizeResourceAccess()`, o trait sobrescreve `authorizeAccess()` — o `mount()` de `CreateRecord`/`EditRecord` confere a permissão (403) antes de o Livewire rodar o hook do trait, e quem não tinha a permissão recebia 403 com o uso desligado (causa (b) do lote A, CT-11). Fonte corrigida: `01`, passo 5.
- Passo 8 (lote T0): `tests/Kit/AssistenteChatCssTest.php` CT-01 também dependia do chat no `/app` (o grep de aceite do Codex pegou só o CT-03); ligado no arranjo. Fonte corrigida: `01`, passo 8.
- Implementação: o lote Kit do `04` foi dividido em dois arquivos (`AgentesIaNoSettingsTest` e `AgentesIaNoSettingsChatTest`); índice do `04` ajustado.
- Step 10 (CT-B): o `05` não armava o dock no canto nem rolava a página (causa (a)); passo 1b acrescentado; `moverDockDaPilha()`/`rolarPaginaDaPilha()` promovidos a `tests/Pest.php` (D11 estendida).

## Notas de Implementação
- **ADV-12 (rejeitado)**: se o dashboard dinâmico desenhar um widget sem consultar `canView()`, o grupo "Agentes de IA" apareceria lá com o uso desligado — e a permissão `View:AgentesIaStats` também seria ignorada. É propriedade do pacote/feature `dashboard-dinamico-nos-paineis`; fica registrado como candidato a verificação naquela wiki.
- **Lote Kit dividido em dois arquivos** (`AgentesIaNoSettingsTest` e `AgentesIaNoSettingsChatTest`): 25 cenários num arquivo só estourariam o teto de turnos do `fw-executor-ct`; o índice do `04` foi ajustado pela sessão.
- **Python no Windows**: `io.open(..., 'w')` sem `newline=''` converteu quatro arquivos inteiros para CRLF (o `git ls-files --eol` acusou); normalizados para LF antes do primeiro commit. Os scripts seguintes escrevem com `newline=''`.
- **Baseline de `main` (27e47714), antes do primeiro commit** — `php artisan test --testsuite=Kit,Tenancy --parallel --compact`, 2026-10-19: 4.850 testes, 4.846 verdes, 3 pulados, **1 vermelho**: `tests/Kit/SiteDeDocumentacaoTest.php:'mantem os numeros objetivos dos readmes sincronizados com a arvore':993` — o README diz `**84**` features especificadas e a árvore já tem 85 `00-requisito.md` (a pasta desta wiki, untracked, já contava). Não é falha pré-existente: é consequência da própria feature, e o passo 9 (docs) atualiza os dois READMEs para 85. Duração: 553 s.

## Referências Abertas
- `hosts-e-subagentes.md` — início — 2026-10-19
- `ponytail-caveman.md` — início — 2026-10-19
- `roteamento-e-despacho.md` — início (antes de qualquer despacho) — 2026-10-19
- `pesquisa-step-3.md` — step 3 — 2026-10-19
- `template-00-requisito.md` — step 4 — 2026-10-19
- `entrevista-tres-raias.md` — step 4 — 2026-10-19
- `glossario.md` — step 3 (lido; nenhum termo novo decidido até aqui) — 2026-10-19
- `template-01-plano.md` — step 4 — 2026-10-19
- `template-02-adr.md` — step 4 — 2026-10-19
- `template-03-progresso.md` — step 4 — 2026-10-19
- `padrao-de-log.md` — step 4 — 2026-10-19
- `estrutura-criada.md` — step 4 — 2026-10-19
- `citacoes-de-codigo.md` — step 3/4 — 2026-10-19

## Retrospectiva
- **Funcionou bem**:
- **Faltou no plano**:

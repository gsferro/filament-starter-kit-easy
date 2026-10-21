# Progresso — Onboarding guiado

**Estado**: em planejamento

**Base do PR**: `main`

## 1. Config, env e channel
- [ ] Bloco `kit.onboarding` em `config/kit.php` e `KIT_ONBOARDING=false` no `.env.example`
- [ ] Channel `onboarding` em `config/logging.php`
- [ ] `locales` do `config/filament-onboarding.php` em `['pt_BR', 'en']`

## 2. Settings, tela e decisor
- [ ] `App\Support\TutorialGuiado::habilitado()`
- [ ] Propriedade, linha do mapa e migration de settings
- [ ] Seção "Tutorial guiado" na tela de configurações

## 3. Painéis: plugin nos três, 404 e menu
- [ ] Plugin registrado nos três painéis (launcher/tours por closure, welcome desligado, página de progresso, skipWhen)
- [ ] Middleware `ExigeTutorialGuiadoLigado` persistente
- [ ] `ChecklistDoOnboarding` recusa ação com o tutorial desligado
- [ ] Policies de autoria exigem o interruptor
- [ ] Widget `ProgressoOnboarding` some com o interruptor desligado
- [ ] Permissão da página de progresso na matriz de papéis

## 4. Condições em código
- [ ] `tem_dois_fatores` e as sete condições de visibilidade no `->conditions()` do plugin

## 5. Seeders: pai, base e filhos
- [ ] `SeederDeOnboarding` (base) e `OnboardingSeeder` (pai, `FILHOS`, `FILHOS_DE_TENANCY`, `TELAS`, `ISENTAS`)
- [ ] 21 filhos sempre semeados
- [ ] 6 filhos de tenancy

## 6. `kit:install`
- [ ] Opção `--onboarding` e pergunta interativa

## 7. Rule e teste de inventário
- [ ] `.ai/rules/onboarding.md` e linha no índice
- [ ] `tests/Kit/InventarioDeOnboardingTest.php`

## 8. Documentação
- [ ] `docs/{pt,en}/recursos/tutorial-guiado.md` e índices
- [ ] Configurações, instalação avançada, READMEs e CHANGELOG

## Testes
<!-- Preenchida no step 7. -->

## Tickets
<!-- Step 8. -->

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/filacheck --fix`
- [ ] `vendor/bin/pest --filter=Onboarding --compact`
- [ ] `vendor/bin/pest --parallel --tia`: nada mais quebrou além da baseline de `main`
- [ ] `/code-review high main...HEAD` + passe de eixos (step 9)
- [ ] Desvios propagados ao `01`/`02`/`04`/`05`
- [ ] `rastreabilidade.sh` silencioso
- [ ] `checkbox-sem-evidencia.sh` silencioso
- [ ] `citacoes.sh` silencioso
- [ ] `ids-ct.sh` silencioso
- [ ] `conformidade-rules.sh` silencioso
- [ ] Falsificabilidade dos CT de achado
- [ ] Docs pt/en, CHANGELOG e README reconciliados
- [ ] `git commit`

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|

## Quality Gate

<!-- Preenchido no step 11. -->

## Candidatos a Rule

<!-- Step 12. -->

## Auditoria Pré-Implementação

Entendimento confirmado: 2026-10-21 — gsferro (solicitante e desenvolvedor), respostas no chat — 4 rodadas; perguntas: 0 fato, 1 desenho (Q9, roteiro), 9 requisito

Sinal de escopo: 31 RQ vigentes (14 do texto + 8 do Adendo 1 + 7 do Adendo 2 + 2 do Adendo 3), acima do limiar de 18 do step 8 — a sugestão de `/feature-tickets` é registrada no step 8.

Roteiro manual: sim — 2026-10-21 — resposta a Q9 — tours, checklist e página em três painéis: o popover sobre cada tela só se confere a olho

### Confronto código × afirmação (step 5)
| Pergunta | O `01` dizia | O código faz | Resposta (quem, data) | Onde a wiki mudou |
|---|---|---|---|---|

### Revisão profunda (step 5) — premissas do plano contra o código real
| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| `progressPageNavigation(label:, icon:)` existe | `vendor/wallacemartinss/filament-onboarding/src/FilamentOnboardingPlugin.php:progressPageNavigation:312`, aceita `string\|Closure\|null` | nenhuma |
| `launcher`/`tours` aceitam closure por request | `FilamentOnboardingPlugin.php:launcher:149` e `:tours:186` (`bool\|Closure`), avaliados em `renderLauncher()` | nenhuma |
| `skipWhen` recebe closure sem argumento | o manager chama `($this->skipResolver)($subject, $scope)` (`vendor/wallacemartinss/filament-onboarding/src/OnboardingManager.php:skipResolver:103`); closure sem parâmetro ignora os argumentos | nenhuma |
| o checklist é um componente que dá para substituir pelo nome | `Livewire::component('filament-onboarding-launcher', …)` no boot do provider do pacote (`vendor/wallacemartinss/filament-onboarding/src/FilamentOnboardingServiceProvider.php:component:64`); o render hook monta pelo nome | nenhuma — a ordem (provider do kit depois do pacote) é conferida no CT |
| a página de progresso nasce com a permissão consultada | Page de vendor não aceita `ExigePermissaoDaTela`; nada consultaria `View:OnboardingProgress` ("checkbox que mente") | passo 3: `PermissaoDaTela::permite()` no `skipWhen` e no middleware |
| rotas de tenant do `/app` só existem com tenancy | existem sempre (`route:list` do step 3 com `kit.tenancy.enabled` = false) | nenhuma: o que separa os filhos de tenancy é o `if` do pai (RQ-24), não a rota |

### Varredura da classe irmã (step 5)

`grep -rnF` (string fixa) em `app config database tests docs .ai/rules routes bootstrap` e nos READMEs, despacho 6.

| Classe nova | Irmã escolhida | Ocorrências da irmã | A nova entra? |
|---|---|---|---|
| `App\Support\TutorialGuiado` | `App\Support\AgentesDeIa` | 17: settings, tela, provider, config, `docs/{pt,en}/operacao/roteiro-de-features.md`, `.ai/rules/settings.md` | docs: sim (passo 8, só a página da feature e configurações; o roteiro de features ganha uma linha); rule de settings: não (sem lista de painéis) |
| propriedade `onboarding_habilitado` | `agentes_ia_habilitado` | 27: tela, settings, migration, `AgentesIaNoSettingsTest`, **`tests/Kit/KitInfoTest.php` (contagem 71)** | `KitInfoTest` 71 → 72 (passo 2) |
| `KIT_ONBOARDING` | `KIT_AGENTES_IA` | 26: config, migration, **`tests/Kit/DiagramasDaArquiteturaTest.php` (mapa de chaves)**, docs de atualização, configurações e diagramas, READMEs | mapa do teste de diagramas (passo 1) e `docs/{pt,en}/comecar/atualizando-o-projeto.md` (passo 8) |
| channel `onboarding` | `'ai'` | 11: `config/logging.php`, **`tests/Kit/QualidadeDeCodigoTest.php` (dataset de channels sem log em disco)**, `DiagramasDaArquiteturaTest` | dataset do `QualidadeDeCodigoTest` (passo 1); o channel segue o molde `LOG_KIT_DRIVER` + `NullHandler` |
| middleware `ExigeTutorialGuiadoLigado` | `App\Http\Middleware\DefinirTenantDePermissoes` | 3: provider do `/app`, docblock do `KitServiceProvider`, teste de diagramas de tenancy | nenhuma ocorrência além das previstas |
| `OnboardingSeeder` e filhos | `TenantsSeeder` | 9: `DatabaseSeeder`, `KitUpdateTest` (lista de arquivos do kit), testes de tenancy, `instalacao-avancada.md` | `KitUpdateTest` lista `database/seeders` como diretório, e o diretório já cobre os arquivos novos; `instalacao-avancada.md` sim (passo 8). `DatabaseSeeder`: não, de propósito (RQ-06) |
| migration de settings | `add_agentes_ia_to_kit_settings` | 1: `AgentesIaNoSettingsTest` (glob da migration) | o CT de reversibilidade da feature faz o mesmo glob |
| `ChecklistDoOnboarding` | `App\Livewire\AssistenteChatWidget` | 4: só testes | nenhuma ocorrência além das previstas |

### Auditoria Ponytail (step 6)
| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `yagni`: oito classes de condição de uma linha em `app/Onboarding/Conditions/` → um array no `->conditions()` do plugin | sim | `01`, passo 4, D6 e Análise |
| 2 | `delete`: o diretório `app/Onboarding/Conditions` e a dependência de discovery | sim | `01`, passo 4 |

`/ponytail-review` (o comando sem o namespace `ponytail:` é o que a instalação expõe) sobre `01` e `02`, 2026-10-21: `net: -7 arquivos, ~-120 linhas`. Mudança em 1 arquivo de código planejado: sem re-execução (limiar: >3 arquivos).

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| 1 | 3 | `Explore` — inventário de telas por painel | não exposto pelo host (herdado) | conversa | tabelas dos três painéis, plugins e tenancy | 86 500 tokens · 127 s | `AdminPanelProvider.php` (linha do plugin) e `config/kit.php` (bloco `tenancy`) conferidos por grep |
| 2 | 3 | `Explore` — tenancy, seeders, settings | não exposto pelo host (herdado) | conversa | `kit.tenancy.enabled`, padrão de seeder, molde dos agentes de IA | 92 588 tokens · 110 s | `DatabaseSeeder` lido inteiro; propriedade `agentes_ia_habilitado` conferida |
| 3 | 3 | `Explore` — modelo de dados do pacote | não exposto pelo host (herdado) | conversa | colunas, enums, condições, cache, plugin | 109 532 tokens · 106 s | `FilamentOnboardingPlugin::register` e `OnboardingProgress::shouldRegisterNavigation` lidos direto |
| 4 | 3 | `Explore` — rotas e decisores | não exposto pelo host (herdado) | conversa | route names, decisores, `KitInstall::semear` | 41 772 tokens · 130 s | `KitInstall::semear()` e `Convite` conferidos por grep |
| 6 | 5 | `Explore` — varredura da classe irmã, 9 greps `-F` | haiku | conversa | 9 saídas literais, categorizadas | 54 521 tokens · 76 s | `KitInfoTest`, `QualidadeDeCodigoTest` e `DiagramasDaArquiteturaTest` lidos direto; o agente criou 2 arquivos temporários fora do repo, apagados pela sessão |
| 7 | 7 | `general-purpose` — derivação do `04`/`05` pela `feature-test-design` | opus | `01` (só paths/rotas/UI), `02` (só Superfície Livewire), implementação, conversa | 42 CT + 1 CT-B, 10 perguntas (Q11–Q20) | 321 075 tokens · 1094 s | contagens por `grep -c`; Q?n renumeradas pela sessão |
| 8 | 7 | `fw-adversario-ct` — revisão adversarial | opus | `01`, `02`, código, conversa | 38 achados, 6 perguntas (Q21–Q26) | 140 398 tokens · 333 s | ADV-21 (oráculo invertido) e ADV-03 conferidos contra o `04` e o código (o ⌘K mostrava a página: confirmado) |
| 9 | 7 | `general-purpose` (mesmo agente do 7, por `SendMessage`) — fechamento dos ADV | opus | idem 7 | 59 CT + 2 CT-B, 1 pergunta (Q27) | 463 224 tokens · 668 s | cabeçalho "Revisão adversarial: concluída"; amostra CT-13/CT-45 lida |
| 10 | impl. | `fw-executor-ct` — `tests/Kit/OnboardingGuiado*` + dono de `tests/Pest.php` | sonnet | `01`, `02`, conversa | cortado no `maxTurns` (40) sem arquivo em disco — **auditoria reprovada** (nada entregue) | 319 202 tokens · 410 s | `ls tests/Kit/OnboardingGuiado*` vazio; retomado pelo mesmo agente (`SendMessage`) com escrita em quatro partes |
| 11 | impl. | `fw-executor-ct` — `tests/Tenancy/OnboardingGuiadoTenancyTest.php` | sonnet | `01`, `02`, conversa; `tests/Pest.php` só leitura | 9 CT, 11/17 verdes; 6 vermelhos (b) de uma divergência só — era contradição do `04` (ADV2-13) | 161 345 tokens · 268 s | saída do pest colada; app/ intocado; retomado para materializar a 2ª rodada |
| 12 | 7 | `fw-adversario-ct` — 2ª rodada (cenários novos) | opus | `01`, `02`, código, conversa | 16 achados (ADV2-01..16), 1 pergunta (Q28) | 126 566 tokens · 227 s | ADV2-13 confirmado pela suíte Tenancy (panel_user não abre Membros/Convites: 9 passos, não 11) |
| 13 | 7 | `general-purpose` (mesmo do 7, `SendMessage`) — fechamento da 2ª rodada | opus | idem 7 | 65 CT + 2 CT-B, nenhuma pergunta | 516 941 tokens · 273 s | lista "o que materializar" conferida contra o `## Fechamento da 2ª rodada adversarial` |
| C1 | impl. | `codex exec --yolo` — 26 seeders filhos | não exposto (Codex CLI 0.163.0) | conversa | 26 arquivos, `php -l` e Pint ok | — | 2 filhos lidos inteiros (`ConvitesSeeder`, `PerfilEDoisFatoresSeeder`) |
| C2 | impl. | `codex exec --yolo` — docs en, sidebar, índices, configurações, instalação, atualização, READMEs | não exposto (Codex CLI 0.163.0) | conversa | 12 arquivos | — | diff do `README.md` lido; `SiteDeDocumentacaoTest` acusou link relativo errado no índice (corrigido pela sessão) |
| 5 | 3 | `Explore` — greps da Superfície Livewire do pacote | haiku | conversa | 4 greps colados, tabela das ações do checklist | 51 891 tokens · 56 s | `findStepState()` e `$flowKey` do trait lidos; revelou o caso do checklist já aberto (Q10) |

## Blockers

## Desvios do Plano
- Passo 3, D4: a autoria some do menu por subclasses dos Resources do vendor, não pela policy — o `Gate::before` do `master_global` responde `true` antes de qualquer policy. Fonte corrigida: `01`, D4, Autorização e passo 3.
- Passo 3: o `ChecklistDoOnboarding` é registrado no `KitServiceProvider` (era "um provider do kit"); a recusa usa `TutorialGuiado::visivel()` (interruptor + permissão), não só o interruptor.
- Passo 4: as condições ficaram em `TutorialGuiado::condicoes()`, passadas ao plugin (Ponytail, step 6).
- Passo 6: `--no-seed --onboarding` avisa no resumo como semear depois (P-10).
- Passo 3: a página "Primeiros passos" é a subclasse do kit `App\Filament\Pages\PrimeirosPassos` (com `ExigePermissaoDaTela` e o interruptor como regra local), registrada nos `->pages()` dos três painéis; o plugin roda com `progressPage(false)`. A Page do vendor tinha `canAccess()` permissivo: a permissão não governava nada e a página aparecia no ⌘K com o tutorial desligado (achado da suíte, `PermissoesDeTelasTest`, e ADV-03). O middleware ficou só com o 404. `TutorialGuiado::visivel()` passou a ser `PrimeirosPassos::canAccess()`.
- Passos 4 e 5 (P-11, P-12, P-13, P-17): a visibilidade padrão de todo passo com tela é a condição `tela:{chave}` (o `canAccess()` da tela no painel corrente); as condições `hub_ligado`, `agentes_de_ia_ligados`, `central_de_comandos_ligada` e `demo_ligada` saíram (o `canAccess()` das telas já as decide); entrou `multi_tenant_ligado` (troca de organização). Os filhos de multi-tenant estendem `SeederDeOnboardingDeTenancy`, que não semeia nada sem multi-tenant.
- Passo 7 (P-15): a rule ganhou os caminhos das features base sem tela própria.
- `config/filament-onboarding.php` entrou em `KitUpdate::CAMINHOS_DO_KIT` (ADV-16).
- Inventário de telas dos CT-B: `/{painel}/onboarding` entra em `telasDoKit()` e o `TelasDoKitTest` liga o tutorial com `ligarTutorialGuiado()` (padrão dos agentes de IA), em vez de isenção.

## Notas de Implementação

## Referências Abertas
- `template-00-requisito.md` — step 4 — 2026-10-21
- `entrevista-tres-raias.md` — step 4 — 2026-10-21
- `template-01-plano.md` — step 4 — 2026-10-21
- `template-02-adr.md` — step 4 — 2026-10-21
- `template-03-progresso.md` — step 4 — 2026-10-21
- `pesquisa-step-3.md` — step 3 — 2026-10-21
- `roteamento-e-despacho.md`, `padrao-de-log.md`, `estrutura-criada.md` — pulados: despachos só por `Explore`; o padrão de log do corpo do `SKILL.md` bastou; sem extras `05-*`

## Retrospectiva

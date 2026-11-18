# Progresso — Roadmap item 1: preferências de aparência por usuário

**Estado**: em revisão

**Base do PR**: `main`

## 1. Colunas na pessoa e na organização
- [x] `users.aparencia` (JSON, fora do `$fillable`, em `$hidden`) e `tenants.aparencia_por_usuario` (default `false`) — `database/migrations/2026_11_18_100000_add_aparencia_to_users_table.php`, `…100001_add_aparencia_por_usuario_to_tenants_table.php`; CT-23 e CT-35 verdes, 2026-11-18

## 2. Vocabulário e padrões da instalação
- [x] `TamanhoDaFonte` e `ModoDoMenu` — `app/Support/TamanhoDaFonte.php`, `app/Support/ModoDoMenu.php`; CT-15, CT-16, CT-B02 verdes, 2026-11-18
- [x] `aparencia_por_usuario`, `tamanho_da_fonte`, `modo_do_menu` nos três lugares do settings, `config/kit.php` e `.env.example` — CT-20, CT-21 verdes; âncora do `KitInfoTest` 73 → 76, 2026-11-18

## 3. O decisor
- [x] `AparenciaDoUsuario` — `app/Support/AparenciaDoUsuario.php`; CT-09..CT-18, CT-37..CT-41 verdes; guarda da pré-visualização (P-17) no step 9, 2026-11-18

## 4. Os quatro consumidores
- [x] render hook (densidade + fonte), largura do menu, modo do menu e cor nos três painéis — `KitServiceProvider::configureDensidadeDoLayout()`, os três providers; `DensidadeDoLayoutTest` 100% verde, CT-B01/CT-B02 verdes, Playwright 17/17 (sem tenancy) e 22/23 (com tenancy, o aviso de sucesso expirou no servidor lento), 2026-11-18

## 5. A tela de configurações
- [x] seção "Aparência dos painéis" e coerção ao abrir e ao salvar — CT-20, CT-25, CT-26, CT-42 verdes; a coerção no save veio do vermelho do CT-25 (M49), 2026-11-18

## 6. A seção "Aparência" do Meu perfil
- [x] `PreferenciasDeAparencia` registrado nos três painéis — CT-01..CT-08, CT-29..CT-31, CT-35 verdes, 2026-11-18

## 7. A chave da organização
- [x] `Toggle aparencia_por_usuario` no `TenantForm` — CT-19, CT-22..CT-24, CT-26, CT-28, CT-42 verdes, 2026-11-18

## 8. O documento de candidatas a pacote
- [x] `wikis/candidatas-a-pacote.md` e links — roadmap item 1, `wikis/README.md`, `KitUpdate::CAMINHOS_DO_KIT`; CT-32..CT-34 verdes, 2026-11-18

## Testes
- [x] `tests/Kit/PreferenciasDeAparenciaTest.php` (CT-01..CT-08, CT-31, CT-35) — 33/33, 2026-11-18
- [x] `tests/Kit/AparenciaEfetivaTest.php` (CT-09..CT-11, CT-15..CT-17, CT-29, CT-37..CT-41) — 38/38, 2026-11-18
- [x] `tests/Kit/ConfiguracoesDeAparenciaTest.php` (CT-20, CT-21, CT-25, CT-27) — 10/10 depois da coerção no save, 2026-11-18
- [x] `tests/Kit/CandidatasAPacoteTest.php` (CT-32..CT-34) — 3/3, 2026-11-18
- [x] `tests/Tenancy/AparenciaEfetivaTenancyTest.php` (CT-12..CT-14, CT-18) — 14/14 depois de as linhas D5/D6 virarem `@obsoleto` (P-13), 2026-11-18
- [x] `tests/Tenancy/ChaveDeAparenciaDaOrganizacaoTest.php` (CT-19, CT-22..CT-24, CT-26, CT-28, CT-42) — 16/16, 2026-11-18
- [x] `tests/Tenancy/PreferenciasDeAparenciaTenancyTest.php` (CT-30) — 4/4, 2026-11-18
- [x] `tests/Browser/PreferenciasDeAparenciaTest.php` (CT-B01, CT-B02) — 5/5 em série, 54 asserções, 2026-11-18
- [x] Ajustes em testes existentes — âncora de `tests/Kit/KitInfoTest.php` (73 → 76); citações deslocadas em `tests/Kit/{ChecklistDeRelease,CitacoesDeCodigo,DeployDockerLocal,DiagramasDaArquitetura,KitArte,LinkDoPainelSemTenancy}Test.php` e `tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php`; `phpunit.xml` fixa as três chaves novas (CX-01), 2026-11-18

## Tickets
Não fatiado — 2026-11-18: 14 RQ vigentes (< 18), 41 CT (< 60), compactação: sim (a sessão vinha compactada da feature anterior), 4 perguntas de requisito — só o sinal de compactação; não fatiado porque a feature cabe numa sessão e a implementação já estava em curso

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent` — `{"tool":"pint","result":"passed"}`, 2026-11-18
- [x] `vendor/bin/filacheck --fix` — "All 17 rules passed!", 2026-11-18
- [x] `vendor/bin/phpstan analyse` (level 8) — `{"tool":"phpstan","result":"passed","errors":0}`, 2026-11-18
- [x] testes da feature (Kit e Tenancy) — ver `## Testes`, 2026-11-18
- [ ] `vendor/bin/pest --parallel --tia` contra a baseline
- [x] `/code-review high main...HEAD` + passe de eixos (step 9), antes da reconciliação — 10 achados do `/code-review`, 2 do `fw-revisor-diff`, 1 do `codex`; destino de cada um em `## Revisão do Diff (step 9)`, 2026-11-18
- [x] Playwright nos três painéis, sem e com tenancy — roteiro da sessão (banco SQLite próprio): sem tenancy 17/17; com tenancy 22/23 (Acme liberada com a cor da pessoa, Globex não liberada com a cor dela, interruptor no cadastro, chave desligada e religada sem perder a escolha; o único vermelho foi o aviso "Aparência salva" já expirado no servidor lento, visto no roteiro sem tenancy), 2026-11-18
- [x] Falsificabilidade — a fiação da feature nos 13 arquivos de `app/` já existentes revertida (`git diff --diff-filter=M main -- app` + `git apply -R`, reaplicado e conferido por `git status`), com as classes novas mantidas: Kit 59 de 81 vermelhos (PreferenciasDeAparencia + AparenciaEfetiva + ConfiguracoesDeAparencia) e Tenancy 32 de 34; os que passam sem a fiação são os de vocabulário e fábrica dos enums novos e as recusas que já eram do Filament (Toggle `boolean`). CT-41 e CT-25 também provados vermelhos isolados contra as respectivas correções, 2026-11-18
- [x] Desvios propagados ao `01`/`02`/`04` de origem — marcas `*(alterado em 2026-11-18…)*` no `00` (P-06, P-13), no `01` (Análise, passo 5, passo 7, P-13) e no `02`; CT-12 D5/D6 `@obsoleto`, CT-41 e CT-42 acrescentados ao `04`, 2026-11-18
- [x] `rastreabilidade.sh {wiki}` silencioso — exit 0 depois de P-17 entrar na origem da R4, 2026-11-18
- [x] `checkbox-sem-evidencia.sh {wiki}` silencioso — exit 0, 2026-11-18
- [x] `citacoes.sh {wiki}` silencioso — exit 0 depois de recalcular as citações do `01` deslocadas pela própria feature, 2026-11-18
- [x] `ids-ct.sh {wiki}` silencioso — exit 0 com os oito arquivos de teste da feature, 2026-11-18
- [x] `conformidade-rules.sh {wiki} main` silencioso — exit 0, 2026-11-18
- [x] Docs pt/en, CHANGELOG, roadmap e roteiro de atualização reconciliados — `docs/{pt,en}/recursos/configuracoes-do-kit.md` (seção nova), `atualizando-o-projeto`, CHANGELOG 0.54.0, roadmap item 1 FEITO, glossário ("Aparência pessoal"), `wikis/atualizacoes/v0.54.0.md` + cópia em `agentes-e-skills.md`, READMEs (contagens), 2026-11-18
- [ ] `git commit`

## Auditoria Pré-Implementação

- Entendimento confirmado: 2026-11-18 — mantenedor, pelas respostas escritas a Q1–Q4 e "prossiga ate finalizar" — 1 rodada, 4 perguntas na raia requisito (Q1–Q4), 0 na raia desenho (decisões rotineiras sob a autorização), 0 de fato
- Roteiro manual: não — 2026-11-18 — instrução explícita do desenvolvedor ("use o playwrite para navegar no site e validar as correções") — a validação manual é a navegação Playwright da sessão, registrada na Verificação Final, sem `08`

### Revisão profunda (step 5)

| Premissa do plano | O que o código diz | Correção |
|---|---|---|
| sidebar recolhível por Closure no render | `HasSidebar::isSidebarFullyCollapsibleOnDesktop()` faz `evaluate()`; lido em `vendor/filament/filament/resources/views/components/layout/index.blade.php:isSidebarFullyCollapsibleOnDesktop:12` no render | nenhuma |
| `/app/meu-perfil` sem organização no endereço | `route:list`: `app/meu-perfil` — rodado **sem** tenancy; com `KIT_TENANCY=true` a rota é `/app/{tenant}/meu-perfil` (descoberto pelo CT-12 na implementação) | P-13 escrita a partir disto e corrigida em 2026-11-18 |
| Breezy filtra por `canView()` estático | `HasMyProfile::getRegisteredMyProfileComponents():96` | nenhuma |
| cor da organização no `bootUsing`, registrada depois da do painel | `app/Providers/Filament/AppPanelProvider.php` (o `FilamentColor::register` da organização no `bootUsing`); a da pessoa precisa vir depois desta | D5 escrita a partir disto |
| contagem de propriedades do settings travada num teste | `tests/Kit/KitInfoTest.php` âncora `toHaveCount(73)` | o passo 2 sobe para 76 (registrado em Impacto) |
| `TenantPolicy` restringe o Resource | `app/Policies/TenantPolicy.php` existe | nenhuma |

### Varredura da classe irmã

- `App\Livewire\DefinirSenhaPorEmail` (irmã de `PreferenciasDeAparencia`): citada nos três providers (`->myProfileComponents`) e em `tests/Kit/DefinirSenhaPorEmailTest.php`. O componente novo entra nos três providers; nenhuma outra lista
- `App\Support\DensidadeDoLayout` (irmã de `TamanhoDaFonte`/`ModoDoMenu`): `config/kit.php`, migration de settings, `ConfiguracoesDoKit` (classe e tela), três providers, `KitServiceProvider`, `.env.example`, `tests/Kit/DensidadeDoLayoutTest.php`, `tests/Kit/KitInfoTest.php` (âncora). Os dois enums novos entram em `config/kit.php`, settings (três lugares), tela e `.env.example`; a âncora sobe 73 → 76
- `tenants.registro_habilitado` (irmã de `aparencia_por_usuario`): `Tenant::$fillable`/cast, `TenantForm`, `TenantHeader`, `TenantInfolist`. A chave nova entra em `$fillable`, cast e `TenantForm`; `TenantHeader`/`TenantInfolist` não (corte do Ponytail abaixo)

### Auditoria Ponytail (step 6)

Ponytail indisponível como plugin com namespace — passe manual pela skill `ponytail-review` do projeto sobre `01` e `02`:

- `01` passo 7: `delete:` entrada no `TenantInfolist` — o requisito não pede exibir a chave; o formulário basta. Aplicado: passo 7 só no `TenantForm`
- `01` D2: um decisor para quatro consumidores — mantido; quatro cópias da regra das duas chaves seriam o defeito da `.ai/rules/models.md` (fonte única)
- `01` D6: mover a densidade de seção — mantido; é uma linha, e deixar a densidade longe de fonte e menu espalharia a mesma pergunta em duas seções
- `02`: nada a cortar

## Desvios do Plano

- 2026-11-18 — **citações das wikis irmãs** que apontam para arquivos tocados por esta feature e estavam certas em `main`: 7 deslocadas em 6 wikis (`aparencia-das-telas-de-acesso`, `cabecalho-do-painel`, `diagramas-da-arquitetura` ×2, `roteiro-de-atualizacao` ×2), recalculadas pela ocorrência equivalente; as demais saídas do `citacoes.sh` nessas wikis já estavam assim em `main` e não são desta feature

- 2026-11-18 — **P-13 corrigida pelo CT-12**: com tenancy, o Meu perfil do `/app` está dentro de uma organização (`/app/{tenant}/meu-perfil`); as linhas D5 e D6 do CT-12 (que abriam `/app/meu-perfil` com tenancy) viraram `@obsoleto` no `04` e saíram do teste; a fronteira "sem organização corrente" continua coberta pela linha D7/D10 (`/admin`) e pelo CT-09 (Kit, `/app` sem tenancy). O código não muda: `AparenciaDoUsuario::liberada()` já decide pela organização corrente
- 2026-11-18 — **defeito achado pelo CT-25 (M49)**: fonte e menu de tipo errado forjados no estado da tela de configurações (`["grande"]`) passavam pelo `in()` do Select, chegavam nulos ao `fill()` do spatie e davam `TypeError` (500) ao salvar. Corrigido coagindo densidade, fonte e menu também em `mutateFormDataBeforeSave()`

- 2026-11-18 — **ordem**: a implementação dos passos 1 a 8 começou em paralelo com a derivação do `04` (step 7), e não depois dela. O derivador recebeu só o `00`, as rotas e a Superfície de UI do `01`, a Superfície Livewire do `02` e o glossário, com a ordem de ler `app/` só para nomes. Para conter o risco de o `04` confirmar o código em vez do requisito: o `04` é auditado por comportamento que só viria de `app/` (nomes de método, textos de log ou de tela que não estão no `01`), passa pelo `fw-adversario-ct`, e a falsificabilidade é provada revertendo o patch de `app/` (`git apply -R`) e vendo os CT ficarem vermelhos
- 2026-11-18 — **erro de leitura do step 3**: a coerção da densidade na tela de configurações mora em `mutateFormDataBeforeFill()`, não em `mutateFormDataBeforeSave()`; corrigido no `01` (Análise e passo 5), no `02` e no comentário do código
- 2026-11-18 — **consequências do documento novo em `wikis/`**: `wikis/candidatas-a-pacote.md` entrou em `KitUpdate::CAMINHOS_DO_KIT` (a varredura de `wikis/*.md` do `KitUpdateTest`), na contagem de documentos de referência dos dois READMEs (12 → 13) e na tabela de `wikis/README.md`

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|
| CR-01 | `/code-review` | a pré-visualização das telas de acesso aplicava a aparência do administrador logado | premissa → CT → correção | P-17 / CT-41 (vermelho sem a guarda: Denso em vez de Compacto) | — |
| CR-02 | `/code-review` | outros `string` do settings vindos de Select (`dock_layout`, `dock_estilo`, `cabecalho_detalhe_do_usuario`, `login_anti_robo_provedor`) também dariam 500 com array forjado | — | — | anterior à feature e fora do escopo; só quem administra a instalação alcança; registrado como pendência no relatório final |
| CR-03 | `/code-review` | o decisor roda uma vez por item e grupo do menu | — | — | medido: 23 ms por 1.000 chamadas (~23 µs), ~2 ms numa página de 40 itens; memoizar quebraria CT-10/CT-14/CT-37/CT-40 (estado entre requests no mesmo processo) |
| CR-04 | `/code-review` | docblocks diziam que o Meu perfil do `/app` fica fora de organização | correção de comentário | P-13 | — |
| CR-05 | `/code-review` | o comentário copiado nos três providers citava "a cor da organização" no `/admin` e no `/infra` | correção de comentário | — | — |
| CR-06 | `/code-review` | o tour do Meu perfil (`PerfilEDoisFatoresSeeder`) não citava a seção nova (`.ai/rules/onboarding.md`) | correção de texto | — | — |
| CR-07 / CR-08 | `/code-review` | `escolha()` relia a pessoa e reduzia as quatro chaves duas vezes; `CHAVES` sem uso | simplificação | — | — |
| CR-09 | `/code-review` | cinco enums com `opcoes()`/`coagir()`/`padrao()` repetidos | — | — | refatoração dos três enums anteriores fora do escopo; o molde é o da casa |
| CR-10 | `/code-review` | comentários de linha em vez de PHPDoc | — | — | a convenção viva do kit usa comentário de linha para o porquê local (providers e telas inteiras) |
| RD-01 | `fw-revisor-diff` | duas citações de `tests/Pest.php` deslocadas pelos `use` novos | correção | — | — |
| RD-02 | `fw-revisor-diff` | `null` forjado nas chaves booleanas daria 500 (por leitura) | CT de regressão | CT-42 | refutado por execução: forjado pelo estado do Livewire, a gravação responde sem erro e grava desligado, com ou sem coerção; a coerção defensiva foi retirada por não ser falsificável |
| CX-01 | `codex` | CT-21 dependia do `.env` local | correção (fixar as três chaves no `phpunit.xml`) | CT-21 | — |

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `app.md` | `app/**` | aplicada | sem pasta-base nova; nenhum papel atribuído; nenhuma URL de disco |
| `commands.md` | `app/Console/Commands/**` | aplicada | `KitUpdate::CAMINHOS_DO_KIT` ganhou `wikis/candidatas-a-pacote.md` em ordem alfabética |
| `config.md` | `config/**` | aplicada | chave booleana nova por `BooleanoDoEnv::comPadrao()`; fonte e menu coagidos pelo enum, sem `(int) env()`; sem canal de log novo (D7) |
| `css-filament.md` | `app/Providers/**` | aplicada | nenhuma regra por classe `fi-*`: fonte por variáveis `--text-*` fora de layer, no mesmo `<style>` do `--spacing` |
| `filament-resources.md` | `app/Filament/**/Resources/**` | aplicada | só um `Toggle` no `TenantForm`; o Resource continua pela `TenantPolicy` (CT-28) |
| `filament.md` | `app/Filament/**` | aplicada | `filacheck` 17/17; nenhuma Action nova (o salvar é método do componente do perfil, gateado pela tela `View:MyProfilePage` e pela chave); `PermissoesDeAcoesTest` verde na suíte |
| `kit.md` | `tests/Kit/**` | aplicada | casos sem tenancy em `tests/Kit`, com `admin_app` só em `tests/Tenancy` |
| `livewire.md` | `app/Livewire/**` | aplicada | o log do `salvar()` leva só os valores reduzidos ao vocabulário, nunca o bruto do cliente |
| `models.md` | `app/Models/**` | aplicada | `users.aparencia` fora do `$fillable`, em `$hidden`, escrita só por `forceFill` (CT-35); nenhuma trait nova |
| `onboarding.md` | `app/Filament/**` | aplicada | o tour do Meu perfil (`PerfilEDoisFatoresSeeder`) passou a citar a seção Aparência (CR-06); os tours das Configurações e das Organizações são genéricos e continuam verdadeiros |
| `pages.md` | `app/Filament/Admin/Pages/**` | aplicada | a seção nova nasce por `secao('aplicacao', 'aparencia', …)` (colapsável, ícone, id estável); `ConfiguracoesSecoesColapsaveisTest` verde |
| `providers-filament.md` | `app/Providers/Filament/**` | aplicada | o componente do perfil e a cor da pessoa entram nos TRÊS painéis |
| `providers.md` | `app/Providers/**` | n.a. | nenhuma rota nova |
| `settings.md` | `app/Settings/**` | aplicada | três propriedades nos três lugares (propriedade, `mapaDeConfiguracao()`, migration de settings); todas lidas por request (render hook e Closures) |
| `specs.md` | `wikis/specs/**` | aplicada | citações de vendor por símbolo; citações de teste pelo ID do CT |
| `support.md` | `app/Support/**` | n.a. | nenhuma gravação no `.env` |
| `testes-browser.md` | `tests/Browser/**` | aplicada | em série, geometria por `script()`, `assertPathIs` antes do conteúdo (relato do `fw-executor-ctb`) |
| `testes.md` | `tests/**` | aplicada | helpers cruzados em `tests/Pest.php` com docblock; `flushSession()` na segunda pessoa; `chaveDoCampo()`/`carregarAba()`; `naArvoreDoKit()` no caso que lê `wikis/` |
| `views.md` | `resources/views/**` | aplicada | a view nova não menciona diretiva em comentário Blade |

## Despachos

| # | Step | Rota | Modelo | Contexto | Resultado | Custo | Auditoria |
|---|---|---|---|---|---|---|---|
| 1 | 3 | `Explore` (revisão do código para RQ-14) | não exposto pelo host | contexto novo, só o objetivo e as pastas | tabela de 20 candidatas com arquivos, acoplamento e esforço | 113k tokens · 2,5 min | amostragem: 8 arquivos citados conferidos por `ls` (todos existem); 2 chaves de config conferidas por grep e corrigidas no documento (`kit.exibir_versao`, `kit.tabelas.visoes_salvas`) |
| 2 | 7 | `feature-test-design` (derivação do `04`) | `opus` | contexto novo: `00`, rotas e Superfície de UI do `01`, Superfície Livewire do `02`, glossário | 35 CT + 2 CT-B, 5 perguntas (Q?1..Q?5) | 273k tokens · 18 min | auditado por vazamento de `app/`: só nomes de arquivo de teste e paths do `01`; Q?2 virou fato (regra `boolean` do Toggle), Q?4 decisão de desenho, Q?5 correção do `02` |
| 3 | 7 | `fw-adversario-ct` rodada 1 | não exposto pelo host | `00`, `04`, `05`, glossário | 18 achados (ADV-01..ADV-18) | 85k tokens · 4 min | amostragem de ADV-01/ADV-02 contra o `04`: procedentes |
| 4 | 7 | `feature-test-design` (fechamento da rodada 1) | `opus` (mesmo agente) | o mesmo + os achados | 37 CT, 80 mutantes, 0 sem matador | 348k tokens · 6 min | `grep 'Q\?[0-9]'` vazio no `04` |
| 5 | 7 | `fw-adversario-ct` rodada 2 (teto) | não exposto pelo host | o mesmo | 16 fechados, 1 parcial, 1 não verificável, 6 novos (ADV-19..ADV-24) | 143k tokens · 1,6 min | — |
| 6 | 7 | `feature-test-design` (fechamento da rodada 2) | `opus` (mesmo agente) | o mesmo + os achados | 39 CT, 85 mutantes | 385k tokens · 3 min | delta repassado ao executor |
| 7 | impl. | `fw-executor-ct` | não exposto pelo host | `04` + `.ai/rules/testes.md` | 7 arquivos, 117 testes; 3 vermelhos classificados "implementação divergente" (CT-12 D5/D6 → P-13; CT-25 → coerção no save) | 280k + 230k tokens · 32 min (parou no teto de turnos e foi retomado) | os 3 vermelhos reproduzidos pela sessão antes de corrigir |
| 8 | impl. | `fw-executor-ctb` | não exposto pelo host | `05` + Superfície de UI | 1 arquivo, 5/5 verdes | 78k tokens · 1,3 min | — |
| 9 | 9 | `/code-review high main...HEAD` | sub-agente (forked) | diff | 10 achados (CR-01..CR-10) | 183k tokens · 11 min | CR-01 reproduzido (CT-41 vermelho sem a guarda) |
| 10 | 9 | `fw-revisor-diff` | não exposto pelo host | diff + Superfície Livewire | 2 achados (RD-01, RD-02) | 204k tokens · 26 min | RD-02 refutado por execução (CT-42 verde sem a coerção) |
| 11 | 9 | `codex exec --yolo` (revisão independente) | não exposto pelo host | diff | 1 achado (CX-01) | — | reproduzido pelo próprio codex com `KIT_TAMANHO_DA_FONTE=grande` |
| 12 | 10 | `codex exec --yolo` (roteiro de atualização v0.54.0) | não exposto pelo host | CHANGELOG, diff, docs | `wikis/atualizacoes/v0.54.0.md` + cópia em `agentes-e-skills.md`; `RoteiroDeAtualizacaoTest` 212/212 | — | conferido: chave ligada, três migrations, como iniciar desligada |
| 13 | 10 | `codex exec --yolo` (Filament Blueprint, skill `planning-filament`) | não exposto pelo host | só `00`, `01`, `02` + vendor | 40 requisitos BP-01..BP-40 | — | pedido do usuário ("use o blueprint para validar o filament") |

## Candidatos a Rule

# Progresso — feat/cabecalho-do-painel

**Estado**: em revisão — QA ciclo 3 REPROVADO só por texto do `03`/`04` (corrigido depois do ciclo); teto de ciclos atingido, aceite final é do solicitante (ver `## Blockers`)

> Base do PR: `main` (depois do merge de `feat/logo-dark-mode`, #145, e da release que a leva). Branch: `feat/cabecalho-do-painel`.

## 1. Settings: cinco propriedades, o mapa e a migration
- [x] Propriedades `cabecalho_*` em `app/Settings/ConfiguracoesDoKit.php` — CT-19 verde (defaults na settings e na config), 2026-10-05
- [x] Linhas em `mapaDeConfiguracao()` → `kit.cabecalho.*` — `ConfiguracoesDoKitTest` 35/35 (dataset "leva cada propriedade gravada…" e "semeia todas as propriedades"), `KitInfoTest` 62 propriedades, 2026-10-05
- [x] `database/settings/2026_10_05_100000_add_cabecalho_to_kit_settings.php` — "desfaz e refaz as migrations de settings sem quebrar" verde, 2026-10-05

## 2. `config/kit.php` e `.env.example`
- [x] Bloco `'cabecalho'` com `BooleanoDoEnv::comPadrao()` e `DetalheDoUsuario::coagir()` — CT-19 verde, 2026-10-05
- [x] Cinco chaves `KIT_CABECALHO_*` no `.env.example` — CT-20 verde (linhas **ativas**, depois do vermelho de causa b), 2026-10-05

## 3. `App\Support\DetalheDoUsuario` e `App\Support\CabecalhoDoPainel`
- [x] Enum `DetalheDoUsuario` (`perfil`, `email`) com `coagir()` e `opcoes()` (sem `rotulo()`, Ponytail #1) — CT-17/CT-18/CT-25 verdes, 2026-10-05
- [x] `CabecalhoDoPainel::marca()`, `marcaEscura()`, `usuario()`, `segmentos()` (memo por request em `WeakMap`, guardas de tela de autenticação) *(alterado em 2026-10-05: step 9 e QA ciclo 1)* — CT-01..CT-16, CT-27..CT-31 verdes, 2026-10-05

## 4. As duas blades
- [x] `resources/views/filament/cabecalho-do-painel.blade.php` — CT-02/CT-03/CT-10 verdes (ordem, separadores, escape), 2026-10-05
- [x] `resources/views/filament/usuario-no-cabecalho.blade.php` — CT-12..CT-16 verdes, 2026-10-05

## 5. CSS em `kit.css` + republicação
- [x] Regras `.kit-cabecalho*` e `.kit-usuario*` com par `.dark:root`, regra de mídia e o swap da logo com especificidade própria — CT-34 e CT-B01/CT-B02 verdes (CT-B02 depois do vermelho de causa b), 2026-10-05
- [x] `php artisan filament:assets` — `public/css/kit/kit-correcoes.css` no commit `bbea5a9`; CT-34 confere as duas classes no publicado, 2026-10-05

## 6. Os três `PanelProvider`
- [x] `brandLogo`/`darkModeBrandLogo` → `CabecalhoDoPainel`, e o hook `USER_MENU_BEFORE`, nos três — CT-01 (três painéis), CT-09, CT-12, CT-28 verdes; `phpstan` nível 8 `errors: 0`, 2026-10-05

## 7. A seção na aba Identidade
- [x] `Section::make('Cabeçalho dos painéis')` com os quatro toggles e o select condicional (+ `->rule('string')`, CT-22) — CT-21..CT-26, CT-32, CT-33 verdes; `filacheck` 17 regras ok, 2026-10-05
- [x] Coerção do detalhe em `mutateFormDataBeforeFill()` — CT-25 verde (gravado volta a `perfil`), 2026-10-05

## 8. Documentação pt e en, READMEs e contagens
- [x] Seção nova + linha da tabela em `docs/pt` e `docs/en` — commit `64748a8`; `ConfiguracoesDoKitDocumentacaoTest`/`SiteDeDocumentacaoTest` na regressão (Verificação Final), 2026-10-05
- [x] READMEs: specs 73 → 74 · `KitInfoTest`: 57 → 62 — `grep -c '\*\*74\*\*' README.md README.en.md` = 1 e 1 (depois do RD-01, que tirou o `**74**` indevido do total de telas); `KitInfoTest` verde, 2026-10-05

## 9. Testes
- [x] Derivados pela `feature-test-design` (step 7) e escritos pelos `fw-executor-ct`/`ctb` — Kit 50/50, Tela 16/16, Tenancy 17/17, Browser 4/4 (`--compact --no-tia`; CT-35 e CT-B03 entraram no QA ciclo 1), 2026-10-05

## Testes
<!-- Preenchida no step 7, depois da derivação do 04/05: um arquivo de teste por linha, com os IDs que ele cobre. -->
- [ ] `tests/Kit/CabecalhoDoPainelTest.php` (CT-01, CT-02, CT-03, CT-05, CT-06, CT-08, CT-09, CT-10, CT-12, CT-13, CT-14, CT-16, CT-18, CT-19, CT-20, CT-28, CT-34)
- [ ] `tests/Kit/CabecalhoDoPainelTelaTest.php` (CT-17, CT-21, CT-22, CT-23, CT-24, CT-25, CT-26, CT-32, CT-33, CT-35)
- [ ] `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` (CT-04, CT-07, CT-11, CT-15, CT-27, CT-29, CT-30, CT-31)
- [ ] `tests/Browser/CabecalhoDoPainelTest.php` (CT-B01, CT-B02, CT-B03)

Revisão adversarial (step 7, `fw-adversario-ct`): 35 achados ADV-01..ADV-35 — 2 blockers (ADV-01 escape do rótulo do painel no `/app`; ADV-02 logo da organização no segmento, contra P-12), 11 major, 4 minor, 8 oráculos fracos, 1 malformado, 6 "sem cenário discriminante", 5 sondas. **Aceitos 30**, rejeitado 1 (ADV-13 como mutante — a feature não corta texto; a linha de 255 chars entrou), duplicados 4. Re-derivação pelo mesmo analista: 26 → **33 CT**, 19 → 22 regras, 76 → 99 mutantes; CT-27..CT-33 novos. Duas decisões de código saíram daqui: `segmentos()` devolve `null` sem usuário autenticado (ADV-06 — tela pública fica com a marca de hoje) e a tela coage o detalhe no `fill` (ADV-14/D8).

## Tickets
<!-- Step 8 -->
Não fatiado — 2026-10-05: 8 RQ vigentes (+13 P-nn), 26 CT + 2 CT-B na derivação (35 CT + 3 CT-B depois da revisão adversarial e do QA ciclo 1), compactação: não, 9 perguntas de requisito — nenhum sinal (limiar: 18 RQ ou 60 CT)

## Verificação Final
- [x] `/ponytail-review` no plano (step 6) e o diff revisto pelos dois passes do step 9 — 5 cortes aplicados no `01`; no código, `/code-review high` não apontou over-engineering (7 achados, todos de correção/limpeza), 2026-10-05
- [x] `vendor/bin/pint --dirty` — `{"result":"passed"}` depois do último commit, 2026-10-05
- [x] `vendor/bin/phpstan analyse` + `vendor/bin/filacheck` — `{"tool":"phpstan","result":"passed","errors":0}` (nível 8) · `All 17 rules passed!`, 2026-10-05
- [x] `php artisan filament:assets` — `public/css/kit/kit-correcoes.css` regenerado e commitado (`bbea5a9`, `1972811`); CT-34 confere o publicado, 2026-10-05
- [x] `vendor/bin/pest tests/Kit/CabecalhoDoPainelTest.php tests/Kit/CabecalhoDoPainelTelaTest.php tests/Tenancy/CabecalhoDoPainelTenancyTest.php --compact --no-tia` — `{"result":"passed","tests":83,"passed":83,"assertions":530}` (50 + 16 + 17, com o CT-35 já com a asserção positiva do QA-08 — re-rodado depois do commit `5b065e8`), 2026-10-05
- [x] `vendor/bin/pest tests/Browser/CabecalhoDoPainelTest.php --compact --no-tia` — `{"result":"passed","tests":4,"passed":4,"assertions":30}` (série, com o CT-B03), 2026-10-05
- [x] Regressão nomeada no `01` + `DiagramasDaArquitetura*`, `GuardasDosDiagramas`, `LinkDoPainelDaOrganizacao`, `ConfiguracoesDoKitDocumentacao` — `{"result":"passed","tests":841,"passed":840}` + `{"tests":625,"passed":625}` (depois da entrada das 4 chaves no mapa opt-in da guarda dos diagramas, CT-56), 2026-10-05
- [x] Suíte `Kit,Tenancy`: `php artisan test --testsuite=Kit,Tenancy --parallel --processes=4 --compact` → 1ª rodada (antes do QA): `{"result":"passed","tests":3911,"passed":3908,"assertions":19018,"duration_ms":666418,"skipped":3}` — baseline 3826 + 82 novos, 0 falhas; 2ª rodada (depois do QA ciclo 2, com CT-35/CT-B03): **1 falha**, `SiteDeDocumentacaoTest` CT-50 — o badge "casos de teste" dos READMEs contava 1.891 e a árvore passou a 1.893 com os dois `it()` novos; corrigido (`64c41db`); 3ª rodada, sobre `64c41db`: `{"result":"passed","tests":3912,"passed":3909,"assertions":19022,"duration_ms":487024,"skipped":3}` — 0 falhas, medido pela sessão, 2026-10-05
- [x] `pestw.cmd tests/Kit/CabecalhoDoPainelTest.php tests/Kit/CabecalhoDoPainelTelaTest.php tests/Tenancy/CabecalhoDoPainelTenancyTest.php --mutate --path=app/Support/CabecalhoDoPainel.php --no-tia` — `Mutations: 9 untested, 3 uncovered, 86 tested · Score: 87.76% · Duration: 52.53s` (plausível: 98 mutantes × suíte de ~1 s; acima do piso de 70 %). **Sobreviventes (9, todos no `warning` da composição vazia, linhas 183–190)**: `RemoveMethodCall` do `Log::channel(...)->warning(...)`, `ConcatRemoveLeft/Right/SwitchSides` da mensagem e `RemoveArrayItem` dos cinco campos do contexto — log não é cláusula do `00` e não vira CT (dimensão D do gate; `padrao-de-log.md`). **Uncovered (3)**: linha 94 (`usuario()` com `auth()->user()` que não é `App\Models\User` — guarda de tipo sem caminho de teste) e linha 119 (`segmentos()` fora de request, sem `Request` no container — caminho de artisan). **Re-medido depois da correção do QA-01** (mesmo comando): `Mutations: 9 untested, 5 uncovered, 89 tested · Score: 86.41% · Duration: 40.74s` — os 9 sobreviventes são os mesmos do log; os 2 uncovered novos são a linha 221 (`emTelaDeAutenticacao()`: o ramo da rota `.auth.`, que só um GET autenticado de 2FA alcançaria — o CT-35 entra pelo ramo `SimplePage`). Uma primeira rodada com dois `--path` e `--covered-only` devolveu `5 mutantes, 100 %, 0,32 s` — descartada como medição inválida (só o último `--path` é honrado; o 100 % com duração implausível é o falso do Windows que a rule `testes.md` descreve), 2026-10-05
- [x] **Custo medido** — `segmentos()` 1× por request (memo por `WeakMap`; antes do step 9 eram 4×), com os 2 `exists()` de `IdentidadeDoKit` só com a logo ligada; tudo desligado: 0 chamadas a mais (a Closure devolve `IdentidadeDoKit::logo()` direto); bloco do usuário: 2 queries constantes (`exists` do `master_global` + `select roles … painel`), as mesmas do badge — o QA do ciclo 1 mediu 2, não 1 (QA-05) — medido por leitura do fluxo e pelo revisor (RD-04), 2026-10-05
- [x] **`/code-review high main...HEAD` + passe de eixos (step 9)**, antes da reconciliação — 7 + 8 achados, tabela `## Revisão do Diff (step 9)`; correções em `1972811`, 2026-10-05
- [x] Roteiro "Desenhado × Implementado" do `05-*-browser.md` preenchido — 3 linhas (✅, ✅, ⚠️→✅ com a correção do swap), 2026-10-05
- [x] Desvios propagados ao `01`/`02`/`04`/`05` de origem, marcados `*(alterado em …)*` — `01` passos 1, 2, 3, 5, 7, D4, Modelo de Execução; `00` P-01, P-06; `04` R1–R24 pela re-derivação, R25 nova; `05` roteiro, 2026-10-05
- [x] `rastreabilidade.sh {wiki}` silencioso — exit 0 depois de R25 citar RQ-08 (a RQ "entregue com testes" tem a guarda de CSS como cenário próprio), 2026-10-05
- [x] `checkbox-sem-evidencia.sh {wiki}` silencioso — exit 0, 2026-10-05
- [x] `citacoes.sh {wiki}` — exit 0 sobre `00`–`05` (duas rodadas de reancoragem: implementação e `User.php` +30 linhas); **exit 1 só pelas citações do texto verbatim do `06`** (8 linhas: as 4 do Ciclo 1 e a transcrição delas no QA-09 do Ciclo 2), texto do QA gravado verbatim e não editado pela sessão — débito aceito (QA-09), 2026-10-05
- [x] `ids-ct.sh {wiki} 'tests/**/CabecalhoDoPainel*.php'` silencioso — exit 0 (35 CT + 3 CT-B ↔ 4 arquivos), 2026-10-05
- [x] `conformidade-rules.sh {wiki} main` silencioso — exit 0 com 14 rules na tabela, nenhuma violada, 2026-10-05
- [x] Falsificabilidade dos CTs nascidos de achado: **CT-35** (QA-01) nasceu vermelho (`Expecting '<div wire:key=…' not to contain 'kit-cabecalho'`) e ficou verde só com a guarda por `SimplePage`; **CT-B03** (QA-04) derruba o mutante sem `overflow: hidden`/`text-overflow` (`Failed asserting that 463 is greater than 463`) e os mutantes de `min-width: 0` sobrevivem por serem redundantes (registrado no `05`); **CT-22 "lista"** e **CT-B02** nasceram vermelhos na primeira rodada dos executores e ficaram verdes só com a correção (`->rule('string')`; swap fora de camada) — e o CT-22 foi re-medido vermelho com a regra removida no step 9 (RD-03); **CT-20** idem (linhas do `.env.example`). Os demais 31 CT são de feature nova, não de correção, 2026-10-05
- [x] Docs pt/en, CHANGELOG e README reconciliados com o comportamento final — seção nova pt/en reescrita após RD-05/CR-04 ("telas de autenticação", "encolhem e cortam"); README 73/74/175/204/1.891 conferidos por `SiteDeDocumentacaoTest`; CHANGELOG: entra na release seguinte (não tocado nesta branch), 2026-10-05
- [x] `git commit` — `bbea5a9`, `64748a8`, `371ac34`, `1972811`, `37f05d7`, `0314ed9`, `9960ea6`, `5b065e8`, `64c41db` + a wiki no commit final (hashes depois do rebase sobre `597acdc`), 2026-10-05

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|
| RD-01 / CR-02 | eixos + `/code-review` | READMEs: o `sed` global `**73**→**74**` da sessão bumpou o total de **Telas navegáveis** (14+31+28 = 73) junto com o de specs; e os arquivos de teste (172/200 → 175/204) e o badge de casos de teste (1.855 → 1.891) não foram recontados — `SiteDeDocumentacaoTest` 3 vermelhos | correção (manutenção, não RQ) | — | — |
| RD-02 | eixos | comentário do `kit.css` atribuía o swap vencido a "especificidade zero"; o mecanismo real é **cascade layer**: a regra do Filament é `(0,2,0)` dentro de `@layer components`, e o `kit.css` sem camada vence por estar fora de camada | correção do comentário | — | — |
| RD-03 | eixos | comentário do `->rule('string')` afirmava que o `in` do Laravel aprova array; **medido** (regra removida, CT-22 "lista" re-rodado): sem a regra o save estoura `TypeError`, então a regra fica e o comentário passa a dizer o fato medido, sem explicar o vendor | correção do comentário | CT-22 | parcialmente rejeitado: a regra não é redundante — o teste prova |
| RD-04 / CR-03 / CR-05 | eixos + `/code-review` | `marcaEscura()` re-rodava `segmentos()` (4 resoluções e até 8 `exists()` por página) e o `warning` saía a cada render | correção: memo por request (`WeakMap` com a `Request` como chave — não vaza entre requests da suíte, ao contrário de `once()`), uma resolução e no máximo um `warning` por request; docblock reescrito com o custo real | — | — |
| RD-05 | eixos | guarda `auth()->check()` deixava a composição aparecer no 2FA do Breezy e no aviso de verificação de e-mail (autenticados, mas telas de autenticação) | correção: segunda guarda por rota `filament.{painel}.auth.*`; docs pt/en dizem "telas de autenticação" em vez de "públicas" | — | sem CT novo: montar o 2FA do Breezy em teste é arranjo de outra feature; coberto por leitura do vendor (`SimplePage::hasLogo()`) e pela rota |
| RD-06 / CR-01 | eixos + `/code-review` | página de erro do Sentinel ecoa o `getBrandLogo()` num `span` sem altura; com `height:100%` a logo da composição saía no tamanho do arquivo | correção: `height: 2rem` absoluto no CSS **e** inline na blade (fora do painel o CSS do kit pode não carregar) | — | — |
| RD-07 / CR-06 | eixos + `/code-review` | chave de tenant não numérica virava contexto nulo (filtro aberto, em silêncio); e a regra "painel corrente + organização aberta" estava duplicada entre `perfil-indicator.blade.php` e `CabecalhoDoPainel` | correção: `User::papelNoPainelCorrente()` (fonte única, fecha para `null` com chave não numérica), consumido pelos dois | — | — |
| RD-08 | eixos | comentários da página e do enum diziam "sem coerção na página"/"numa linha só" depois de a coerção do `fill` voltar (ADV-14) | correção dos comentários | — | — |
| CR-04 | `/code-review` | `flex-wrap` na barra lateral vazava por baixo do cabeçalho de altura fixa (`div.fi-logo` com `height: 2rem` inline num header de 4 rem) | correção: composição sempre numa linha; na barra lateral textos menores com `text-overflow: ellipsis`; P-06 e docs pt/en reescritas | P-06 (texto) | — |
| CR-07 | `/code-review` | `CT-B02` gravava dois PNG no disco `public` real e não apagava | correção no teste: `afterEach` com `delete()` | CT-B02 | — |
| — | eixos | rejeitados pelo revisor com motivo: XSS em `title`/`alt` (tudo `{{ }}`); bloco dentro do dropdown (hook em `vendor/filament/filament/resources/views/components/user-menu.blade.php:USER_MENU_BEFORE:43`, antes do dropdown da linha 45); `TelaBloqueio` com composição (`hasLogo()` falso); fonte do papel diferente do badge; `skip()` do CT-20 escondendo outro leitor de `docs/` | — | — | ver motivo |

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `app.md` — papel por `ContextoDePapeis`; DTO; `Paineis::correnteOuPadrao()`; `asset()` para URL pública | `app/**` | aplicada | `CabecalhoDoPainel::resolverSegmentos()` e `User::papelNoPainelCorrente()` usam `Paineis::correnteOuPadrao()`; nenhum `assignRole`, DTO ou `Storage::url()` no diff; a logo vem de `IdentidadeDoKit` (já `asset()`) — CT-06 |
| `config.md` — vazio no `.env` nunca `(int) env()`; interruptor que abre superfície falha fechado; uma pergunta, uma dona | `config/**` | aplicada | os quatro booleanos usam `BooleanoDoEnv::comPadrao(…, false)` (falham fechado; CT-56 dos diagramas os extrai como opt-in); o detalhe passa por `DetalheDoUsuario::coagir()`; nenhuma chave nova responde pergunta que outra já responde — CT-19 |
| `css-filament.md` — utilitária de blade precisa existir no CSS do kit, escopada; guarda que lê a blade; cuidado com `@layer` | `resources/css/filament/**`, `app/Providers/**` | aplicada | só classes `kit-*` nas blades, todas em `kit.css` com par `.dark:root`; guarda CT-34 lê as duas blades em runtime com piso; o swap repetido fora de camada está documentado no próprio CSS (RD-02) |
| `filament-resources.md` | `app/Filament/**/Resources/**` (`UserInfolist.php`) | n.a. | o arquivo só teve uma citação `arquivo:símbolo:linha` reancorada no comentário (CT-26); nenhum Resource mudou |
| `filament.md` — Page com `ExigePermissaoDaTela`; Action com `authorize`; Blueprint v5 | `app/Filament/**` | aplicada | a página já usa `ExigePermissaoDaTela` (CT-26: 403 na montagem); nenhuma Action nova; `Get` do namespace `Schemas`; `AderenciaAoBlueprintTest` verde na regressão |
| `models.md` — `papelDoPainel()` é exibição, consulta `papeisEmQualquerContexto()`, nunca `roles()` | `app/Models/**` | aplicada | `papelNoPainelCorrente()` só delega a `papelDoPainel()` (exibição); `CabecalhoDoMenuDoUsuario{,Tenancy}Test` verdes (inclusive "acha o papel mesmo fora do contexto") |
| `pages.md` — segredo em formulário: fill + dehydrate + `encrypted()` + trio de casos | `app/Filament/Admin/Pages/**` | n.a. | nenhum campo novo é segredo (quatro booleanos e um select) |
| `providers-filament.md` — plugin que resolve o painel corrente vai nos três painéis | `app/Providers/Filament/**` | aplicada | nenhum plugin novo; a Closure e o hook entraram nos **três** providers (CT-01/CT-12 por painel) |
| `providers.md` — rota do kit nasce no `KitServiceProvider` com `web` | `app/Providers/**` | n.a. | nenhuma rota nova |
| `settings.md` — chave lida por request pode virar Settings; três lugares por propriedade | `app/Settings/**` | aplicada | as cinco chaves são lidas no render (Closure/hook), não no boot; propriedade + mapa + migration nova — "semeia todas as propriedades" e "desfaz e refaz as migrations" verdes, CT-19/CT-21 |
| `support.md` — `.env` só por `SubstituicaoEmArquivo` | `app/Support/**` | n.a. | nada grava `.env` |
| `testes-browser.md` — uma visita por tema; sem `--parallel` | `tests/Browser/**` | aplicada | CT-B02 faz uma visita por tema (`inDarkMode()` no load); rodado em série |
| `testes.md` — helper cruzado em `Pest.php`; `toContain()` sem mensagem; `noPainelBootado()` não serve ao `/app`; comentário filtrado em asserção de ausência | `tests/**` | aplicada | helpers dos lotes com sufixo por arquivo (`DaTenancia`), `HelpersDeTesteTest` verde; `assertStringNotContainsString` para ausência; o `/app` é bootado por GET real nos lotes Tenancy; CT-34 lê o CSS sem comentários |
| `views.md` — nenhuma diretiva dentro de comentário `{{-- --}}` | `resources/views/**` | aplicada | os comentários das duas blades e do `perfil-indicator` citam "render hook"/"inclusão" por extenso; as três views renderizam nos CT-01..CT-16 |

## Quality Gate

- **Ciclo**: 1 · **Veredito**: REPROVADO → especificação · **Data**: 2026-10-05 — 0 blocker, 1 major (QA-02: texto do `01`/`02`/`03` com o desenho que o step 9 revogou — `flex-wrap`, `once()`, "sem memo", `kit.css`), 2 minor (QA-01: composição aparecendo em tela de autenticação autenticada depois de um update Livewire — defeito real; QA-04: P-06 revisada sem CT-B), 2 cosméticos (QA-03 contagens; QA-05 "1 query" era 2). Roteamento: QA-02/03/05 → destino 1 (wiki reescrita com marcas `*(alterado em …)*`: ADR-01, `01` passos 3/5, P-06, Modelo de Execução, Riscos, Verificação Final, `03` passo 3 e Desvios, `04:18` recontado 35 · 23 · 105); QA-01 → destino 3 depois 2 (CT-35 no `04` R22, escrito pelo `fw-executor-ct` e **vermelho** antes da correção; guarda passa a reconhecer `SimplePage` por `Livewire::current()`); QA-04 → destino 3 (CT-B03 no `05`, pelo `fw-executor-ctb`, com a causa a do viewport corrigida no `05`: a barra lateral só desenha a marca abaixo de 1024 px). Ambiente: a migration de settings foi aplicada no banco servido (`php artisan migrate`).
- **Ciclo**: 2 · **Veredito**: REPROVADO → especificação · **Data**: 2026-10-05 — 0 blocker, 2 major (QA-06: passo 3/5 do `01` e Desvios do `03` sem a guarda final por `SimplePage` e com `height:100%`; QA-07: números da Verificação Final defasados — 82→83, 3/3→4/4, 34+2→35+3, `citacoes.sh`), 1 minor (QA-08: CT-35 só com negação), 3 cosméticos (QA-03′ contagens, QA-09 citações do Ciclo 1 do `06`, QA-10 CT-B03 na R20). QA-01/04/05 fechados; QA-02 fechado com resíduo. Roteamento: QA-06/07/03′ → destino 1 (reescritos com marcas); QA-08 → destino 3 (CT-35 ganha a asserção positiva "Projeto Ômega" dentro de `.fi-logo`, mutante M5; executor da tela); QA-10 → destino 3 (R26 nova no Mapa de Regras para a P-06, CT-B03 movido da R20); QA-09 → **débito aceito** (as 4 citações estão no texto verbatim do Ciclo 1 do `06`, que a sessão não edita; nota na Verificação Final). `phpstan`/`filacheck` re-rodados sobre `9960ea6`: `errors: 0` / `All 17 rules passed!`; suíte `Kit,Tenancy` completa re-rodada (resultado abaixo, na Verificação Final).
- **Ciclo**: 3 · **Veredito**: REPROVADO → especificação · **Data**: 2026-10-05 — 0 blocker, 1 major (QA-07′: `03:60` dizia 528 asserções e o HEAD dá 530 — o número foi gravado antes do commit da asserção positiva), 2 minor (QA-11: hashes anteriores ao rebase e o `grep -c` dos READMEs; QA-13: K2 — sobreviventes do `warning` e o ramo `.auth.` sem cobertura), 2 cosméticos (QA-12 contagem 106/G4/índice; QA-14 suíte e débito fora do `03`). QA-06/08 fechados; QA-03′/QA-10 fechados com resíduo (→ QA-12); QA-09 débito aceito. **Teto de 3 ciclos atingido**: pela regra de convergência o caso vai ao usuário. Todos os destinos 1 foram aplicados pela sessão depois do ciclo (530; hashes `bbea5a9`/`64748a8`/`371ac34`/`1972811` + commits posteriores; "1 e 1"; 106, G4 com R26, índice do CT-35 com M4–M5; 8 linhas; suíte 3912/3909 colada); QA-13 decidido como **débito declarado** (sem CT de log, `padrao-de-log.md`; o ramo `.auth.` mantido como defesa redundante ao `SimplePage`). Nenhum código de aplicação mudou desde `9960ea6`.
- **Relatório**: `06-relatorio-qa.md`
- **Débito consolidado**: QA-09 (citações do texto verbatim do `06`); H parcial (axe reprova elementos do Filament anteriores à feature, sem Playwright MCP); K2 — 9 sobreviventes no `warning` da composição vazia (log não é cláusula do `00`; CT de efeito colateral recusado, `padrao-de-log.md`) e o ramo da rota `.auth.` de `emTelaDeAutenticacao()` sem cobertura (redundante com o sinal `SimplePage` — mantido como defesa, declarado); 9 perguntas de requisito aplicadas pela recomendação e pendentes de confirmação do solicitante (Q1–Q6, Q14, Q16, Q17).

## Candidatos a Rule

- 2026-10-05 — rota: sessão principal (MCP) — apresentados 3 · gravados 3 · recusados 0 · descartados no gate 0 · poda 0 — aprovados pelo solicitante ("pode gravar as 3 rules, aprovo todas") e gravados via `record-rule` em `css-filament.md`, `testes.md` e `models.md` (índice regenerado pelo Boost, globs já existentes, nenhuma seção duplicada): (1) `css-filament.md` ↑ "regra fora de camada vence o `:where()`/`@layer` do Filament — nunca declarar `display` numa classe aplicada à mesma imagem que carrega `fi-logo-light/dark`; repetir o swap com par `.dark:root`" (glob `resources/css/filament/**`); (2) `testes.md` ↑ "caso que lê `docs/`, README ou site por path **interpolado** pula fora da árvore — o CT-11 só pega path literal" (glob `tests/**`; segunda recorrência: `HostLocalTest` CT-12 na v0.38.0 e `LogoDarkModeTest` CT-20 na v0.43.0); (3) `models.md` ↑ "`User::papelNoPainelCorrente()` é a fonte única do papel exibido (badge e cabeçalho)" (glob `app/Models/**`). Gate 4 verificado por `search-docs` (nenhum é doc do Filament/Laravel); gate 3 com os irmãos: `kit.css`/`cards.css`/`spotlight.css`/`voltar-ao-topo.css` (só `kit.css` tem `:where`/camada), `CoberturaDeTestesTest`/`DiagramasDaArquiteturaTest`/`SiteDeDocumentacaoTest` (leem `docs/` com `skip`; `LogoDarkModeTest` não tinha), `perfil-indicator.blade.php`/`User.php` (os dois consumidores).

## Auditoria Pré-Implementação

Entendimento confirmado: **pendente de confirmação do solicitante** — 2026-10-05 — sessão autônoma (o solicitante pediu a entrega completa e não está disponível para a rodada); 2 rodadas (step 4 e step 7); perguntas: 0 fato (a pesquisa do step 3 fechou tudo), 8 desenho (Q7–Q13 e Q15, decididas pela sessão como desenvolvedora, com a alternativa recusada no `01`), 9 requisito (Q1–Q6 do step 4 e Q14, Q16, Q17 da derivação, registradas em `## Perguntas ao Solicitante` do `00`, cada uma aplicada pela recomendação como `P-01`–`P-06` e `P-11`–`P-13`). Resposta contrária a qualquer uma entra como Adendo e corrige a premissa correspondente.

### Confronto código × afirmação (step 5)
| Pergunta | O `01` dizia | O código faz | Resposta (quem, data) | Onde a wiki mudou |
|---|---|---|---|---|
| — | nenhuma divergência de comportamento entre o `01` e o código: a feature só acrescenta; o que ela reaproveita (`Paineis::rotulo()`, `papelDoPainel()`, `IdentidadeDoKit::logo()`) foi lido antes de ser citado | — | — | — |

### Revisão profunda (step 5) — premissas do plano contra o código real
Conferência em linha por `grep`/`Read` (sem despacho: cada premissa é um grep de uma linha, e o `citacoes.sh` confere o conjunto — exit 0 em 2026-10-05 depois de 9 correções de linha/formato na primeira rodada).

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| `brandLogo()`/`darkModeBrandLogo()` aceitam `Htmlable` | `vendor/filament/filament/src/Panel/Concerns/HasBrandLogo.php:getBrandLogo():37` e `:getDarkModeBrandLogo():47` — `string \| Htmlable \| null` | nenhuma |
| com `Htmlable`, o Filament não acrescenta `<img>` e envolve numa `div.fi-logo` com `height` | `vendor/filament/filament/resources/views/components/logo.blade.php:$brandLogo:6` e o ramo `instanceof Htmlable` | nenhuma — virou o risco mitigado da ADR-01 |
| a marca é desenhada na barra lateral **e** no topbar | `vendor/filament/filament/resources/views/livewire/sidebar.blade.php:'fi-sidebar-header-logo-ctn':89` (com `x-show` quando recolhível) e `vendor/filament/filament/resources/views/livewire/topbar.blade.php:TOPBAR_LOGO_BEFORE:118` | nenhuma — é a P-06 |
| `USER_MENU_BEFORE` renderiza fora do dropdown | `vendor/filament/filament/resources/views/components/user-menu.blade.php:USER_MENU_BEFORE:43`, antes do `<x-filament::dropdown>` da linha 45 | nenhuma |
| `Paineis::rotulo()` devolve o nome da aplicação para o `app` | `app/Support/Paineis.php:rotulo():298` e `rotulos()` — `'app' => (string) config('app.name')` | linhas citadas corrigidas (170 → 298; `correnteOuPadrao()` 158 → 252) |
| `papelDoPainel()` resolve `master_global` antes e usa `papeisEmQualquerContexto()` | `app/Models/User.php:papelDoPainel():444` | nenhuma |
| `Papeis::rotulo(null)` devolve `—` | `app/Support/Papeis.php:rotulo():49` — `blank($nome) → '—'` | o passo 3 testa o nulo antes de chamar |
| três lugares por propriedade de settings; `KitInfoTest` conta 57 | `app/Settings/ConfiguracoesDoKit.php:mapaDeConfiguracao:396`; `tests/Kit/KitInfoTest.php:'toHaveCount':186` | nenhuma |
| booleano com default vem de `BooleanoDoEnv::comPadrao()`; enum com `coagir()` para lista fechada | `config/kit.php:'exibir_versao':356`, `:'densidade_do_layout':297` | nenhuma |
| channel `configuracoes` existe | `config/logging.php:'configuracoes':153` | nenhuma |
| `CAMINHOS_DO_KIT` cobre os diretórios tocados | `app/Console/Commands/KitUpdate.php:'resources/css/filament':224`, `:'resources/views/filament':227`, `app/Support` (150-ish), `database/settings` (195), `tests/Kit`/`tests/Tenancy` (233-234) | nenhuma — nenhum arquivo novo fora deles |
| `SettingsPage::save()` do plugin faz `fill()` + `save()` | `vendor/filament/spatie-laravel-settings-plugin/src/Pages/SettingsPage.php:save():62` | linha citada corrigida (75 → 62) |

### Varredura da classe irmã (step 5)
`grep -rnF '{Irmã}' --include=*.php app config database tests` (2026-10-05):

| Classe nova | Irmã escolhida | Onde a irmã aparece | A nova entra? |
|---|---|---|---|
| `App\Support\CabecalhoDoPainel` | `App\Support\IdentidadeDoKit` (mesmo papel: apoio estático lido pelas Closures dos providers) | os três `PanelProvider` (9–10 ocorrências cada), `TelaBloqueio` (4), `config/kit.php` (2, comentários), `app/Settings/ConfiguracoesDoKit.php` (1), `app/Console/Commands/KitUpdate.php` (1, comentário), `tests/Pest.php` (1), `IdentidadeDoKitTest`, `LogoDarkModeTest`, `KitUpdateTest`, `DuasRotasDeEntregaTest` | entra nos três providers (passo 6). **Não** entra em `TelaBloqueio` (a tela de bloqueio é pública, fora do escopo), nem em `KitUpdate`/`KitUpdateTest` (o diretório `app/Support` já viaja inteiro); `DuasRotasDeEntregaTest` cita `IdentidadeDoKit` como exemplo de arquivo, não como lista |
| `App\Support\DetalheDoUsuario` | `App\Support\DensidadeDoLayout` (enum string com `coagir()` lido por `config/kit.php` e pela tela) | `KitServiceProvider` (6 — a densidade injeta CSS por request, o detalhe não), `config/kit.php` (4), `ConfiguracoesDoKit` página (4) e settings (3), a migration de settings dela (2), os três providers (2 cada — `sidebarWidth`), `DensidadeDoLayoutTest` (20) | entra em `config/kit.php` (passo 2), na página (passo 7, `opcoes()`) e na migration (passo 1). **Não** entra nos providers nem no `KitServiceProvider`: o consumidor é `CabecalhoDoPainel::usuario()` (passo 3). Teste próprio do enum fica dentro de `CabecalhoDoPainelTest` (o `04` decide) |

### Auditoria Ponytail (step 6)
`/ponytail-review` em linha sobre `01`/`02` (2026-10-05), achados no formato da skill:

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `01` passo 3: `yagni:` `DetalheDoUsuario::rotulo()` por case com um consumidor (o select). `opcoes()` com os dois rótulos inline | sim | `01`, passo 3 |
| 2 | `01` passos 1 e 7: `delete:` coerção do detalhe em `aplicarNaConfig()` **e** em `mutateFormDataBeforeFill()`. Uma coerção só, no consumidor (`CabecalhoDoPainel::usuario()`); a tela valida por `required()` + `options()` | parcial — a de `aplicarNaConfig()` saiu; a do `fill` **voltou** no step 7 (ADV-14/D8): sem ela `telefone` gravado à mão trava a tela inteira, o defeito que a densidade já corrigia | `01`, passos 1, 3 e 7 |
| 3 | `01` passo 3: `delete:` log `debug` "usuário sem papel" — estado normal em todo request de quem entra por outro caminho; não é anomalia. Fica só o `warning` da composição vazia | sim | `01`, passo 3 e `## Channel de Log` |
| 4 | `01` passo 7: `delete:` `->default()` no select de uma `SettingsPage` (ela preenche do banco, semeado com `perfil`) | sim | `01`, passo 7 |
| 5 | `01` passo 4: `delete:` atributo `data-kit-usuario` redundante com a classe `.kit-usuario` | sim | `01`, passo 4 |
| 6 | `01` D6: `yagni:` cinco chaves de `.env` para opções que a tela governa | recusada: toda opção booleana vizinha tem par no `.env`, e as docs prometem que o `.env` semeia a primeira gravação (P-10) | — |
| 7 | `02` ADR-01: `yagni:` CT que lê a blade do vendor atrás do ramo `instanceof Htmlable` | recusada: é a guarda barata (mesma técnica do CT-35) contra o único risco da ADR; sobreviveu ao bump 5.7 → 5.8 sem edição no vizinho | — |

`net: -12 lines possible` no código previsto (um método de enum, duas coerções, um log, um atributo, um `default()`); aplicados 5 de 7.

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| 1 | 3 | `Explore` (built-in) — header do projeto-3: hook, blade, CSS, testes, versão | sessão (herdado) | — | mapa completo: `CabecalhoProjTEC`, `brandLogo` como `HtmlString`, `USER_MENU_BEFORE`, entidade fixa por decisão do PO, `getRoleNames()->first()` (viola `models.md`) | 108 k tokens · 126 s | 3 trechos conferidos por leitura direta do retorno literal (blade, provider, CSS) |
| 2 | 3 | `Explore` (built-in) — estado atual do kit: providers, `IdentidadeDoKit`, settings, tenancy, usuário, views, CSS, testes, docs, glossário | sessão (herdado) | — | mapa de 12 itens, com linhas | 178 k tokens · 217 s | amostragem por grep: `brandLogo` nos três providers (linhas 86/95/107), `papelDoPainel` (414), `Paineis::rotulo()` — todas conferem |
| 3 | 3 | `search-docs` (Boost) — render hooks, `brandLogo`/`Htmlable`, `getTenant()`, sidebar recolhível | sessão | — | hooks `TOPBAR_LOGO_*`, `USER_MENU_BEFORE`, `Filament::getTenant()`, `sidebarCollapsibleOnDesktop()` confirmados na doc 5.x | — | vendor conferido por `Read`: `vendor/filament/filament/src/Panel/Concerns/HasBrandLogo.php:getBrandLogo():37`, `vendor/filament/filament/resources/views/components/logo.blade.php:$brandLogo:6`, `vendor/filament/filament/resources/views/components/user-menu.blade.php:USER_MENU_BEFORE:43` |
| 4 | 7 | `general-purpose`/opus (rota `analista`) — derivação do `04`/`05` pela `feature-test-design` 1.16.0 | opus | `01` (só paths, rotas e Superfície de UI colados), `02` (só Superfície Livewire colada), `03`, a conversa, a implementação (inexistente) | `04` com 26 CT / 19 regras / 76 mutantes, `05` com 2 CT-B / 9 mutantes; 4 perguntas `Q?1–Q?4` (renumeradas Q14–Q17); costuras G1–G4 propostas | 231 k tokens · 746 s | presença: arquivos gravados, cabeçalho com contagem por `grep -c`; integridade: `git status` só os dois arquivos novos; amostragem: CT-01 (marca de fábrica), CT-04 (organização no segmento), CT-21 (gravação por componente) lidos — Gherkin com `Então` derivado do `00`; **reprovado parcialmente**: a Q16 parte de fato errado ("a marca do `/app` mostra hoje a logo da organização" — mostra a da instalação, `app/Providers/Filament/AppPanelProvider.php:brandLogo:100`); respondida pela sessão em P-12 sem redespacho, porque o invariante (CT-07) vale nas duas direções |

| 5 | 7 | `fw-adversario-ct` — revisão adversarial do `04`/`05` | opus | `01`, `02`, `03`, código, raciocínio de quem derivou (hook `adversario-ct`) | 35 achados (ADV-01..35), 2 blockers | 76 k tokens · 182 s | 3 achados reproduzidos por leitura do `04` (ADV-02: CT-07 sem logo na Acme aberta; ADV-05: CT-01 só no `/admin`; ADV-22: CT-26 com dois "Quando"); "Leituras negadas: nenhuma"; nenhum arquivo alterado (`git status`) |
| 6 | 7 | mesmo analista do #4 (`SendMessage`) — re-derivação com as 30 decisões da sessão | opus | idem #4 | `04` 26 → 33 CT, 19 → 22 regras, 76 → 99 mutantes; `05` intacto | 301 k tokens · 369 s | CT-07, CT-08 e CT-28 lidos: afirmam P-12, P-11 e a marca de hoje no login; contagem do cabeçalho refeita por `grep -c`; `git status` só o `04` |
| 7a | impl. | `fw-executor-ct` — `tests/Kit/CabecalhoDoPainelTest.php` (16 CT) | sonnet | `01`, `02`, `03`, a conversa; `app/` só para nomes (hook `executor-ct`) | 49 execuções: 48 verdes, **CT-20 vermelho (b)** — `.env.example` com as linhas comentadas; implementação corrigida (linhas ativas) e re-rodada: 49/49; depois CT-34 acrescentado por `SendMessage`: 50/50 | 162 k + 171 k tokens · 436 s + 68 s | saída literal do pest colada; CT-01 "deriva de 2 ocorrências por request" conferida como arnês (itens de dropdown acumulados) — aceita; `git status`: só o arquivo do lote |
| 7b | impl. | `fw-executor-ct` — `tests/Kit/CabecalhoDoPainelTelaTest.php` (9 CT) | sonnet | idem | 15 execuções: 14 verdes, **CT-22 "lista" vermelho (b)** — `TypeError` na settings com `["email"]`; `->rule('string')` no select e re-rodada: 15/15 | 149 k tokens · 157 s | saída literal colada; a divergência é o mutante R17 M3 previsto no `04` |
| 7c | impl. | `fw-executor-ct` — `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` (8 CT) | sonnet | idem | 17/17 verdes na primeira rodada | 135 k tokens · 163 s | saída literal colada (antes e depois do pint); comparação do detalhe por igualdade ("Administrador App" ≠ "Admin") conferida no arquivo |
| 9 | 9 | `/code-review high main...HEAD` (sub-agente isolado do comando) | sessão (fork) | a conversa | 7 achados (CR-01..CR-07), nenhum rejeitado | 160 k tokens · 611 s | 7/7 reproduzidos por leitura (README soma, Sentinel `resources/views/vendor/filament-sentinel/errors/layout.blade.php:'sn-logo':47`, `flex-wrap` × `height: 2rem` inline) ou por teste (CT-B02 arquivos) |
| 10 | 9 | `fw-revisor-diff` — eixos sobre `main...HEAD -- . ':(exclude)wikis'` | opus | `01`, `03`, a conversa (hook `revisor-diff`) | 8 achados (RD-01..RD-08) + 5 rejeitados com motivo + 4 não verificados | 130 k tokens · 567 s | RD-01 reproduzido (`SiteDeDocumentacaoTest` 65/68); RD-03 **refutado parcialmente** por medição (regra necessária); `git status --porcelain` idêntico antes/depois |
| 11 | 11 | `fw-qa-gate` — `feature-quality-gate` inteira (ciclo 1) | opus | a conversa; só path da wiki, URL do app, `git diff --stat`, base (hook `qa-gate`) | `06` gravado verbatim (entre `<<<06` e `>>>06`): REPROVADO → especificação, 5 achados; 10/12 dimensões | 262 k tokens · 1212 s | `git status --porcelain` igual antes/depois (a diferença declarada foi o commit `0314ed9` da sessão, `.gitignore`); QA-01 reproduzido pelo CT-35 (vermelho antes da correção); QA-02 reproduzido pelo `grep` da própria repro |
| 7b' | 11 | `fw-executor-ct` (`SendMessage`) — CT-35 no lote da tela | sonnet | idem 7b | CT-35 **vermelho (b)** no HTML inicial do prompt de verificação (componente do Auth Designer, `AppPanelProvider:emailVerification`); verde depois da guarda por `SimplePage`: lote 16/16 | 159 k tokens · 43 s | saída literal; o executor relatou o CT-22 "lista" como vermelho por memória da rodada 1 — já estava verde (conferido: 16/16 na re-rodada da sessão) |
| 8' | 11 | `fw-executor-ctb` (`SendMessage`) — CT-B03 | sonnet | idem 8 | 1ª rodada vermelha (causa a: viewport 1280 px → composição da barra 0×0); a 900 px com a barra aberta, 4/4 | 97 k tokens · 212 s | correção do `05` gravada pela sessão; falsificabilidade do M2 refeita pela sessão (ver `05`): o mutante que mata é `overflow/ellipsis`, não `min-width: 0` |
| 12 | 11 | `fw-qa-gate` — ciclo 2 | opus | a conversa | REPROVADO → especificação; QA-06..QA-10 | 206 k tokens · 551 s | QA-06/07 reproduzidos por `grep`; `git status` igual antes/depois |
| 13 | 11 | `fw-qa-gate` — ciclo 3 (último) | opus | a conversa | REPROVADO → especificação só por texto (QA-07′ 528→530, QA-11..14); H parcial; K2 aceito por plausibilidade | 182 k tokens · 479 s | QA-07′ reproduzido (530); hashes conferidos com `git branch --contains`; `git status` igual antes/depois |
| 7b'' | 11 | `fw-executor-ct` (`SendMessage`) — CT-35 com a asserção positiva (QA-08) | sonnet | idem 7b | lote 16/16; `k1-oraculo-fraco.sh` exit 0 | 163 k tokens · 31 s | saída literal; asserção dentro de `.fi-logo` conferida no arquivo |
| 8 | 10 | `fw-executor-ctb` — `tests/Browser/CabecalhoDoPainelTest.php` (CT-B01 ×2, CT-B02) | sonnet | `01` (só Superfície de UI colada), `03`, a conversa (hook `executor-ctb`) | CT-B01 verdes; **CT-B02 vermelho (b)** — swap da logo vencido por `display: block`; CSS corrigido e re-rodada pela sessão: 3/3 | 81 k tokens · 190 s | saída literal colada; tabela Desenhado × Implementado levada ao `05`; probe temporário removido (conferido: `git status` limpo fora do arquivo) |

Sem despacho — captura verbatim do requisito, decomposição em `RQ`, entrevista e escrita do `00`–`03` (tarefas da sessão por contrato). **Implementação dos passos 1–8 em linha** — exceção declarada: edição cirúrgica em arquivos com forte interdependência (classe de apoio × três providers × settings × página × config × blades × CSS), com o `01` como contrato e `pint`/`phpstan`/`filacheck` como gate antes dos testes; os testes nascem do `04` por quem não implementou (#7).

## Blockers
- [ ] **Aceite do solicitante pendente** — o quality gate esgotou os 3 ciclos com veredito REPROVADO → especificação, todos os achados abertos do ciclo 3 eram de texto da wiki e foram aplicados pela sessão depois do ciclo (sem novo gate); a feature, os testes (3912/3909 na suíte completa, 83 + 4 nos lotes) e as regressões estão verdes. Pela regra de convergência, quem aprova agora é o solicitante — no PR. Pendentes também as 9 perguntas de requisito aplicadas pela recomendação (Q1–Q6, Q14, Q16, Q17): resposta contrária entra como Adendo.

## Desvios do Plano
- **Passo 3 — memo por request, não `once()` nem "sem memo"** (D4, duas revisões): `once()` em método estático sobrevive de um request para o outro no mesmo processo (a suíte roda vários requests por caso) e devolveria a composição velha; "sem memo" (primeira correção) custava 4 resoluções e até 8 `exists()` por página, medido no step 9 (RD-04/CR-05). Ficou um `WeakMap` com a `Request` corrente como chave: uma resolução por request, nada vaza. `01` passo 3, D4 e `## Modelo de Execução` marcados *(alterado em 2026-10-05)*; o QA do ciclo 1 (QA-02) pegou este parágrafo ainda dizendo "sem memo".
- **Passo 3 — guarda de tela de autenticação, em três sinais** *(alterado em 2026-10-05: ADV-06 → RD-05 → QA-01)*: `segmentos()` devolve `null` (a) sem usuário autenticado, (b) em rota `filament.{painel}.auth.*` e (c) quando `Livewire::current()` é uma `SimplePage` — o terceiro porque o QA do ciclo 1 provou que o 2FA e o aviso de verificação de e-mail, autenticados, recebiam a composição depois do primeiro update Livewire (`default-livewire.update`). O `00` já declarava as telas de autenticação fora de escopo; o `01` passo 3 e a R22/CT-35 do `04` trazem a guarda final.
- **Passo 7 — coerção do detalhe no `fill` mantida** (Ponytail #2 revisto): sem ela, `telefone` gravado à mão trava a tela inteira pela validação do `Select` (o mesmo defeito que a densidade já corrigia). Fica a coerção em `mutateFormDataBeforeFill()` **e** no consumidor; sai só a de `aplicarNaConfig()`. `01` passos 1 e 7 e a tabela do Ponytail marcados.
- **Passo 5 — swap da logo com especificidade própria** (CT-B02, causa b): `display: block` em `.kit-cabecalho__logo` vencia o `:where(.fi-logo-dark)`/`:where(.dark .fi-logo-light)` do Filament (especificidade zero) e as DUAS logos apareciam no tema claro. Saiu o `display` da classe da imagem e entrou o par `.kit-cabecalho .fi-logo-light/.fi-logo-dark` + `.dark:root …` — o kit repete o swap com a própria especificidade. A premissa "zero CSS de troca, as regras nativas cobrem" (herdada da ADR-01 de `logo-dark-mode`) valia só enquanto nenhuma classe do kit declarava `display` na mesma imagem. `01` passo 5 marcado.
- **Passo 2 — linhas do `.env.example` ativas** (CT-20, causa b): o `04` derivou de P-10 que o `.env.example` traz o par com o mesmo default **lido pelo Dotenv**; as linhas estavam comentadas (padrão de `KIT_UNIFICA_LOGO_MARCA`), e o precedente de `KIT_EXIBIR_VERSAO=false` é ativo. Ficaram ativas. `01` passo 2 marcado.
- **Passo 5 — nome do asset publicado**: `public/css/kit/kit-correcoes.css`, não `kit.css` (o `Css::make('kit-correcoes', …)` do `KitServiceProvider` dá o nome). `01` corrigido.

## Notas de Implementação
- **CT-22 (R17 M3) pegou defeito real na primeira rodada do executor da tela**: o cliente enviando `["email"]` (lista) no select passava na validação (`in` do Laravel aprova array cujos elementos estão na lista), o estado desidratava como `null` e a settings tipada estourava `TypeError: Cannot assign null to property ...$cabecalho_detalhe_do_usuario of type string` no `save()`. Correção: `->rule('string')` no `Select` (`app/Filament/Admin/Pages/ConfiguracoesDoKit.php`), com o motivo no comentário; re-rodada: 15/15. É exatamente o mutante que o `04` previa — o `04` acertou o defeito antes de o código existir.
- **`filament:assets` republicou também os assets do `filament-page-header` 2.4.3** (bump #140 já na `main`): `public/css/mortalkiller/...` e `public/js/mortalkiller/...` mudaram e `header-actions.css` nasceu. Foram para um commit próprio (`:package: build(assets)`), separado da feature, para irem à `main` com a release.

## Referências Abertas
- `template-00-requisito.md` — step 4 — 2026-10-05
- `entrevista-tres-raias.md` — step 4 — 2026-10-05
- `roteamento-e-despacho.md` — step 3 (antes do primeiro despacho) — 2026-10-05
- `pesquisa-step-3.md` — step 3 — 2026-10-05
- `template-01-plano.md` — step 4 — 2026-10-05
- `template-02-adr.md` — step 4 — 2026-10-05
- `template-03-progresso.md` — step 4 — 2026-10-05
- `padrao-de-log.md` — step 4 — 2026-10-05
- `citacoes-de-codigo.md` — step 3/4 — 2026-10-05
- `delegacao-casos-de-teste.md` — step 4 (lida antes do step 7) — 2026-10-05
- `glossario.md` — step 4 — 2026-10-05
- `estrutura-criada.md` — step 4 — 2026-10-05
- `ponytail-caveman.md` — início da sessão — 2026-10-05

## Retrospectiva
- **Funcionou bem**: a revisão adversarial do `04` (35 achados, 2 blockers) antes de qualquer teste existir — o CT-22 e o CT-B02 nasceram vermelhos na primeira rodada dos executores e pegaram dois defeitos reais (array no select → `TypeError`; swap da logo vencido por `display`), exatamente os mutantes que o `04` previa. O step 9 com dois passes rendeu 15 achados sobre um diff que já tinha 85 testes verdes, inclusive um erro de manutenção da própria sessão (o `sed` global nos READMEs) e uma afirmação falsa de vendor no comentário (RD-02). A tabela de rules conferida mecanicamente (`conformidade-rules.sh`) listou 14 rules que casavam o diff — três a mais do que a sessão tinha em mente.
- **Faltou no plano**: (1) o `01` dizia "memo com `once()`" sem pensar no processo da suíte, depois "sem memo" sem medir o custo — a resposta certa (memo por `WeakMap` keyed pela `Request`) só saiu quando o revisor mediu 4 resoluções por página; medir custo antes de decidir cache deveria ser passo do step 5. (2) "Zero CSS de troca, as regras nativas cobrem" foi herdado da ADR-01 da logo e virou premissa sem reconferir o contexto novo (uma classe do kit na mesma imagem). (3) A guarda de tela pública por `auth()->check()` ignorou que 2FA e verificação de e-mail são autenticadas; o `00` dizia "telas públicas" quando queria dizer "telas de autenticação". (4) Em sessão autônoma as perguntas de requisito viraram premissas pela recomendação — nove `P-nn` esperam a confirmação do solicitante, e a Q16 nasceu de um fato errado do próprio analista; vale uma rodada curta com ele antes do merge.

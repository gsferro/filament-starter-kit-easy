# Progresso — Onboarding guiado

**Estado**: concluída — 2026-10-22

**Base do PR**: `main`

## Processo do pedido

- [x] Tag + release criadas — tag `v0.52.0`, release https://github.com/gsferro/filament-starter-kit-easy/releases/tag/v0.52.0; quatro cenários de `wikis/checklist-de-release.md` aprovados sobre a tag publicada (CHANGELOG `[0.52.0]` › Validação local dos quatro cenários), 2026-10-23

## 1. Config, env e channel
- [x] Bloco `kit.onboarding` em `config/kit.php` e `KIT_ONBOARDING=false` no `.env.example` — `config/kit.php:'onboarding':650`, `.env.example:KIT_ONBOARDING:238`, 2026-10-21
- [x] Channel `onboarding` em `config/logging.php` — `config/logging.php:'onboarding':183`; projeto atualizado: `KitServiceProvider::garantirCanalDoTutorialGuiado()` (CT-70 3/3), 2026-10-21
- [x] `locales` do `config/filament-onboarding.php` em `['pt_BR', 'en']` — `config/filament-onboarding.php:'locales':27`, 2026-10-21

## 2. Settings, tela e decisor
- [x] `App\Support\TutorialGuiado::habilitado()` — `app/Support/TutorialGuiado.php:habilitado:42`, 2026-10-21
- [x] Propriedade, linha do mapa e migration de settings — `app/Settings/ConfiguracoesDoKit.php:onboarding_habilitado:272`, mapa `:548`, `database/settings/2026_10_21_100000_add_onboarding_to_kit_settings.php`, 2026-10-21
- [x] Seção "Tutorial guiado" na tela de configurações — `app/Filament/Admin/Pages/ConfiguracoesDoKit.php:secao:1162`, 2026-10-21

## 3. Painéis: plugin nos três, 404 e menu
- [x] Plugin registrado nos três painéis (launcher/tours por closure, welcome desligado, página de progresso, skipWhen) — `TutorialGuiado::plugin()` nos três `app/Providers/Filament/*PanelProvider.php` (grep -l: 3), 2026-10-21
- [x] Middleware `ExigeTutorialGuiadoLigado` persistente — `app/Providers/Filament/AdminPanelProvider.php:ExigeTutorialGuiadoLigado:457` e nos outros dois painéis, 2026-10-21
- [x] `ChecklistDoOnboarding` recusa ação com o tutorial desligado — `app/Livewire/ChecklistDoOnboarding.php:hydrate:29`, 2026-10-21
- [x] Policies de autoria exigem o interruptor — por subclasses (desvio D4): `app/Filament/Admin/Resources/Onboarding/{JornadaResource,CondicaoResource}.php`, 2026-10-21
- [x] Widget `ProgressoOnboarding` some com o interruptor desligado — `app/Filament/Admin/Widgets/ProgressoOnboarding.php:habilitado:42` (CT-12), 2026-10-21
- [x] Permissão da página de progresso na matriz de papéis — `View:PrimeirosPassos` gerada pelo Shield para a Page do kit (`PermissoesDeTelasTest`; CT-47/CT-62 usam `semAPermissao(..., 'View:PrimeirosPassos')`), 2026-10-21

## 4. Condições em código
- [x] `tem_dois_fatores` e as condições de visibilidade no `->conditions()` do plugin *(alterado em 2026-10-21: QA-01 — eram sete; viraram `tela:{chave}` mais quatro sem tela)* — `app/Support/TutorialGuiado.php:tem_dois_fatores:98`; visibilidade como `tela:{chave}` + quatro sem tela (desvio P-11..P-13), 2026-10-21

## 5. Seeders: pai, base e filhos
- [x] `SeederDeOnboarding` (base) e `OnboardingSeeder` (pai, `FILHOS`, `FILHOS_DE_TENANCY`, `TELAS`, `ISENTAS`) — `database/seeders/Onboarding/SeederDeOnboarding.php`, `database/seeders/OnboardingSeeder.php`, 2026-10-21
- [x] 21 filhos sempre semeados — `ls database/seeders/Onboarding/ | wc -l` = 29 (2 bases + 21 + 6), 2026-10-21
- [x] 6 filhos de tenancy — idem; os 6 estendem `SeederDeOnboardingDeTenancy` (CT-52), 2026-10-21

## 6. `kit:install`
- [x] Opção `--onboarding` e pergunta interativa — `app/Console/Commands/KitInstall.php:onboarding:134` (CT-67 nos dois modos), 2026-10-21

## 7. Rule e teste de inventário
- [x] `.ai/rules/onboarding.md` e linha no índice — `.ai/rules/index.md` linha 20, 2026-10-21
- [x] `tests/Kit/InventarioDeOnboardingTest.php` — arquivo presente, verde no `--filter=Onboarding` (312/312 depois do ciclo 3; 305 no ciclo 2), 2026-10-22

## 8. Documentação
- [x] `docs/{pt,en}/recursos/tutorial-guiado.md` e índices — os dois arquivos presentes; reconciliados no step 10 (RD2-02), 2026-10-21
- [x] Configurações, instalação avançada, READMEs e CHANGELOG — `CHANGELOG.md` `[Unreleased]` (Adicionado + Atualizando); READMEs com as contagens derivadas pelo `SiteDeDocumentacaoTest` (76 telas, 26 páginas, 98 rotas `GET`, 88 specs, 26 rules, 207/244 arquivos, badge 2.393); `docs/{pt,en}/comecar/atualizando-o-projeto.md` com o canal de log; redirects `site/public/{pt,en}/recursos/tutorial-guiado.html`, 2026-10-21

## Testes

IDs por arquivo, derivados por `grep -o "\[CT-B\?[0-9]*\]"` em 2026-10-22 (75 CT + 4 CT-B = 79 IDs, igual ao `04`/`05`) *(alterado em 2026-10-22: QA-18)*:

| Arquivo | CT |
|---|---|
| `tests/Kit/OnboardingGuiadoInterruptorTest.php` | CT-01..CT-10, CT-44, CT-46, CT-58 (13) |
| `tests/Kit/OnboardingGuiadoConsumoTest.php` | CT-11..CT-16, CT-18, CT-19, CT-26, CT-43, CT-45, CT-47, CT-48, CT-54, CT-56, CT-60, CT-62, CT-66, CT-68, CT-72..CT-75 (23) |
| `tests/Kit/OnboardingGuiadoSemeaduraTest.php` | CT-17, CT-20..CT-25, CT-27..CT-33, CT-52, CT-53, CT-59, CT-61, CT-65, CT-69 (20) |
| `tests/Kit/OnboardingGuiadoInstalacaoTest.php` | CT-38..CT-40, CT-42, CT-57, CT-67, CT-70, CT-71 (8) |
| `tests/Tenancy/OnboardingGuiadoTenancyTest.php` | CT-34..CT-37, CT-41, CT-49..CT-51, CT-55, CT-63, CT-64 (11) |
| `tests/Browser/OnboardingGuiadoTest.php` | CT-B01..CT-B04 |
| `tests/Kit/InventarioDeOnboardingTest.php` | guarda da rule `.ai/rules/onboarding.md` (sem CT próprio: o CT-40 a exercita) |

## Tickets
<!-- Step 8. -->

## Verificação Final
- [x] `/ponytail:ponytail-review` no diff — `/ponytail-review` (o comando sem o namespace é o que a instalação expõe) sobre o diff das correções: 1 corte (`! method_exists(..., 'canEdit') ||`), aplicado; `net: -0 lines`, 2026-10-21
- [x] `vendor/bin/pint --dirty --format agent` — `{"tool":"pint","result":"fixed"}` (TutorialGuiado.php), 2026-10-21
- [x] `vendor/bin/filacheck --fix` — `All 17 rules passed!`, 2026-10-21
- [x] `vendor/bin/pest --filter=Onboarding --compact` — `{"result":"passed","tests":312,"passed":312,"assertions":3241}` depois do ciclo 3, 2026-10-22 (305/305 no ciclo 2; 293/293 no ciclo 1; o 291 anterior era de antes do CT-70 reescrito e do CT-71 — QA-02), 2026-10-21
- [x] `vendor/bin/pest --parallel --tia`: nada mais quebrou além da baseline de `main` — suíte inteira sem `--tia` (mede mais que o TIA): `vendor/bin/pest --parallel --processes=4 --exclude-testsuite=Browser` → `{"result":"passed","tests":5333,"passed":5330,"skipped":3,"risky":2}` no HEAD `10b422b1` (2026-10-22; antes do ciclo 2 eram 5321/5318); browser da feature em série (`OnboardingGuiadoTest` + `TelasDoKitTest`) 9/9. Com `--parallel`, o browser dá 132 erros `playwright-server.json: No such file` (ambiente: o plugin não sobe o servidor em paralelo) — o browser inteiro roda em série, 2026-10-21
- [x] `/code-review high main...HEAD` + passe de eixos (step 9) — rodada 1 (CR-01..10, RD-01..04) e rodada 2 (CR2-01..09, RD2-01..04), despachos 14, 15, 19, 20, 2026-10-21
- [x] Desvios propagados ao `01`/`02`/`04`/`05` — `01` (D7b, D13, Channel de Log, Superfície de UI com as subclasses), `02` (Superfície Livewire com `updatedFlowKey`), `04` (R30, R32, CT-27 linha 17), `05` (Desenhado × Implementado), 2026-10-21
- [x] `rastreabilidade.sh` silencioso — exit 0, sem saída, 2026-10-21
- [x] `checkbox-sem-evidencia.sh` silencioso — exit 0, sem saída, 2026-10-21
- [x] `citacoes.sh` silencioso — `citacoes.sh` exit 0 após corrigir 6 linhas deslocadas, 2026-10-21
- [x] `roteiro-manual.sh` silencioso — `08-roteiro-teste-manual.md` gerado (9 RM, 0 executados pela sessão: sem ambiente descartável confirmado), exit 0, 2026-10-21
- [ ] `pest --mutate` — não rodado: débito declarado (no Windows exige o `pestw.cmd` por chunk; os 192 mutantes do `04` têm cenário matador nomeado, e a falsificabilidade dos CT de achado foi provada em cópia temporária)
- [x] `ids-ct.sh` silencioso — `ids-ct.sh {wiki} 'tests/**/OnboardingGuiado*.php'` exit 0, sem saída, 2026-10-21
- [x] `conformidade-rules.sh` silencioso — exit 0, sem saída (16 rules com veredito; 2 violações corrigidas: `specs.md` e `testes.md`), 2026-10-21
- [x] Falsificabilidade dos CT de achado — ciclo 4 do gate: CT-B03 linhas 5 e 7 vermelhas (3,12:1 e 3,24:1) e CT-B04 linha 3 vermelha 3/3 antes das correções (executor, mesma árvore sem elas), 13/13 depois em 3 rodadas, 2026-10-22. ciclo 3 do gate: CT-B03 4/4 e CT-B04 2/2 vermelhos antes das correções (executor, mesma árvore sem elas); `.fio-hero-description` intermitente (2 de 3 rodadas vermelhas no `/admin` claro) antes do `--fio-theme-text-muted`, 0 de 4 depois; CT-75 refeito vermelho com M205 (2/4) e M209 (1/4) por `auto_prepend_file` sobre cópia no scratchpad, 2026-10-22. ciclo 2 do gate: CT-72 7/7, CT-73 1/1 (sem a memória), CT-74 1/1 (memória estática presa ao container, M203), CT-75 2/3 (sem sanitizar o context; a linha `selectFlow` é controle) falham sem a correção — mutantes aplicados na árvore com backup e hash conferido depois (`scratchpad/falsif2.py`), 2026-10-21. Step 9: 4 de 5 falham sem o fix (CT-66 3/3, CT-67 2/2, CT-68 2/5 — as linhas "sem", as demais são controle —, CT-70 1/1 — depois reescrito); CT-69 passa sem o patch do CR-04 porque o patch é refator (achatamento do `TELAS` sem perda, mesmo resultado): o CT-69 é guarda do defeito descrito, e mata o mutante M187 (chave `hub-de-navegacao` → `hub-navegacao` no seeder filho: 2 de 3 painéis falham). Cópia: `git worktree --detach` + `git diff HEAD` aplicado + `vendor` copiado, um patch revertido por vez (`scratchpad/reverte.py`), 2026-10-21. Rodada 2: CT-70 (D1) e CT-71 vermelhos sem a correção do canal, escritos pelo `fw-executor-ct` antes dela (2 de 3; a linha D2 é o controle que mata o M192), verdes depois — 3/3, 2026-10-21
- [x] Docs pt/en, CHANGELOG e README reconciliados — RD2-02 nas docs do tutorial ("Some quando", condições, `View:PrimeirosPassos` também em `configuracoes-do-kit.md`), citações do `KitServiceProvider` (+23) em 6 docs, `roteiro-manual.sh` exit 0, 2026-10-21
- [ ] `git commit`

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|
| CR-01 | `/code-review high main...HEAD` | `flowKey` gravável por `$wire.set`, contornando a guarda do `selectFlow` | premissa → CT → correção (`ChecklistDoOnboarding::updatedFlowKey`) | P-20 / CT-66 | — |
| CR-02 | `/code-review` | falha do seeder aborta o `kit:install` antes do resumo com a senha | premissa → CT → correção (`try` no `KitInstall::semearTutorialGuiado`) | P-21 / CT-67 | — |
| CR-03 | `/code-review` | passo do dashboard dinâmico não consulta a permissão da tela | premissa → CT → correção (`TutorialGuiado::abreATelaDo()` com o `canEdit()` da página, na condição `dashboard_dinamico_no_painel`); redação da P-22 revista por gsferro em 2026-10-21: "quem pode montar", não "quem abre" | P-22 / CT-68 | — |
| CR-04 | `/code-review` | nada garante que a chave do passo casa a condição `tela:{chave}` | premissa → CT (guarda) + helper único `OnboardingSeeder::filhosComTela()` | P-23 / CT-69 | — |
| CR-05 | `/code-review` | helper de teste renderiza o widget sem `canView()` | teste (classe a) — helper corrigido; revelou dois verdes falsos | — | — |
| CR-06 | `/code-review` | `ONBOARDING_ENABLED=true` morto no `.env.example` | correção direta (linha removida) | — | — |
| CR-07 | `/code-review` | seeders no autoload de produção | — | — | `Database\Seeders` já está no autoload de produção do kit, e o `TELAS` é o inventário único citado pela rule e pelo teste; copiá-lo para `app/` recriaria o drift do CR-10 |
| CR-08 | `/code-review` | middleware deveria olhar o interruptor primeiro | correção direta (`ExigeTutorialGuiadoLigado::handle`) | — | — |
| CR-09 | `/code-review` | tipo de retorno ausente | correção direta (`SeederDeOnboardingDeTenancy::__invoke(): mixed`) | — | — |
| CR-10 | `/code-review` | achatamento do `TELAS` repetido; sugestão de trait | parte aceita: helper único `filhosComTela()`; parte rejeitada | — | trait: duas classes explícitas de ~10 linhas não justificam a abstração |
| RD-01 | `fw-revisor-diff` (eixo Afirmação de comentário) | comentário diz que o `skipWhen()` não desliga o checklist montado; o vendor desliga (no-op) | correção direta (docblock do `ChecklistDoOnboarding`) | — | — |
| RD-02 | `fw-revisor-diff` (eixo Lista paralela) | canal `onboarding` não chega a quem atualiza por `kit:update` | premissa → CT → correção (`config/logging.php` em `KitUpdate::CAMINHOS_DO_KIT`) | P-24 / CT-70 | — |
| RD-03 | `fw-revisor-diff` (eixo Simetria de guarda) | log do checklist atribui ao interruptor a recusa causada pela permissão | correção direta (`motivo` no `ChecklistDoOnboarding::hydrate`) | — | — |
| RD-04 | `fw-revisor-diff` (eixo Afirmação de comentário) | docblock do decisor e doc contradizem o código | correção direta (docblock do `TutorialGuiado`) | — | — |
| S9-01 | sessão (falha do CT-69/CT-27 após a correção da P-22) | helper `checklistDoOnboarding()` herdava a sessão da pessoa anterior: o `AuthenticateSession` do painel deslogava a segunda pessoa, que via o checklist vazio | teste (classe a) — `flushSession()` no helper; CT-27 linha 17 passa a usar o administrador geral (P-22) | — / CT-27, CT-69 | — |
| RD-02 (revisto) | rodada 2 (RD2-03 + CR2-02) | a correção do RD-02 (`config/logging.php` em `CAMINHOS_DO_KIT`) não chega na 1ª rodada do `kit:update` (classe antiga) e, quando chega, substitui o arquivo inteiro e apaga os canais próprios do projeto | premissa reescrita (gsferro, 2026-10-21) → CT re-derivado → correção: `config/logging.php` sai da lista e o `KitServiceProvider::garantirCanalDoTutorialGuiado()` declara o canal só quando o projeto não o tem | P-24 / CT-70, CT-71 | — |
| CR2-01 | `/code-review high` (rodada 2, diff não commitado) | `textoDoDashboardDoMaster()` não exige 200: redirect vira `''` e todo `not->toContain` passa | teste (classe a) — `assertOk()` no helper | — / CT-12, CT-60, CT-61 | — |
| CR2-03 | `/code-review` rodada 2 | a linha 5 do CT-68 não falha: `Manage:Dashboard` é uma permissão só para os três painéis | — | — | a linha foi reescrita com a P-22: prova que basta um papel da pessoa ter a permissão e mata o M185 (exigir em todos os papéis) |
| CR2-04 / RD2-01 | os dois passes, rodada 2 | rótulo e docblock da condição `dashboard_dinamico_no_painel` descrevem a regra antiga | correção direta (`TutorialGuiado::rotulosDasCondicoes` e docblock de `condicoes()`) | — | — |
| CR2-05 / CR2-06 | `/code-review` rodada 2 | `podeMontarODashboard()` copiava o laço do `abreATelaDo()`; painel nulo vira offset nulo de array | correção direta: um laço só (`abreATelaDo($filho, ?Closure $alemDeAbrir)`), saída antecipada com painel nulo | — | — |
| CR2-07 | `/code-review` rodada 2 | falha no meio da semeadura deixa jornadas parciais e o aviso diz "não foram semeadas" | correção direta: aviso "não foram semeadas, ou ficaram incompletas"; o seeder é idempotente (`firstOrCreate`), rodar de novo completa | — | — |
| CR2-08 | `/code-review` rodada 2 | `flowKey` forjado é descartado sem trilha no canal `onboarding` | correção direta: `warning` no `updatedFlowKey()` | — | — |
| CR2-09 | `/code-review` rodada 2 | comentários inline onde o CLAUDE.md prefere PHPDoc | — | — | o código vizinho comenta inline as mesmas construções (entradas de `CAMINHOS_DO_KIT`, chaves de `condicoes()`); o comentário do `KitUpdate` saiu junto com a linha |
| RD2-02 | `fw-revisor-diff` rodada 2 (Lista paralela) | `docs/{pt,en}/recursos/tutorial-guiado.md`: "Some quando" do dashboard e lista de condições envelhecidas | correção direta nas docs pt/en (e `View:OnboardingProgress` → `View:PrimeirosPassos` também em `configuracoes-do-kit.md`) | — | — |
| RD2-04 | `fw-revisor-diff` rodada 2 (Simetria de guarda) | o `db:seed` principal do `kit:install` também lança sem `try` | — | — | já existia, fora do escopo da feature (sem o seeder principal não há administrador a mostrar); registrado como assimetria |

Rodada 2 encerra o teto de 2 do step 9: nenhum achado estrutural; os dois de fronteira (RD-02 revisto e CR2-01) seguiram premissa → CT → correção ou correção de teste.

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `.ai/rules/app.md` | `app/**` | aplicada | painel corrente com nulo tratado: `app/Support/TutorialGuiado.php:abreATelaDo:149`, `app/Http/Middleware/ExigeTutorialGuiadoLigado.php:handle:34` |
| `.ai/rules/commands.md` | `app/Console/Commands/**` | aplicada | `KitUpdate::CAMINHOS_DO_KIT` leva `config/filament-onboarding.php` (muda entre as tags); `config/logging.php` fora (P-24, CT-71); `KitInstall::semearTutorialGuiado` não grava `.env` |
| `.ai/rules/config.md` | `config/**` | aplicada | `config/kit.php:'onboarding':650` com `filter_var(..., FILTER_VALIDATE_BOOLEAN)`; leitor único `TutorialGuiado::habilitado()` |
| `.ai/rules/css-filament.md` | `resources/css/filament/**`, `app/Providers/**` | aplicada | `resources/css/filament/kit.css` (P-27) só declara variáveis `--fio-theme-*` e o fundo do `.fio-button--primary` escopado em `:root`, sem utilitária sem escopo e sem `@layer`; `php artisan filament:assets` rodado (`public/css/kit/kit-correcoes.css`); CT-B03 |
| `.ai/rules/filament-resources.md` | `app/Filament/**/Resources/**` | aplicada | `JornadaResource`/`CondicaoResource` com `BadgeContagemNavegacao`; `BadgeDeNavegacaoTest` e `PermissoesDeResourcesTest` verdes |
| `.ai/rules/filament.md` | `app/Filament/**` | aplicada | `app/Filament/Pages/PrimeirosPassos.php:regraLocalDeAcesso:30` com `ExigePermissaoDaTela`; par `canAccess()`/`shouldRegisterNavigation()` nos Resources |
| `.ai/rules/kit.md` | `tests/Kit/**` | aplicada | ação com middleware persistente pela rota de update (`tests/Kit/OnboardingGuiadoInterruptorTest.php:acaoPelaRotaDeUpdateDoOnboarding:403`) |
| `.ai/rules/onboarding.md` | `app/Filament/**`, seeders, `app/Support/TutorialGuiado.php` | aplicada | `ConfiguracoesDaAplicacaoSeeder` ensina a seção nova; `OnboardingSeeder::ISENTAS` com motivo; docs pt/en mudaram junto; `InventarioDeOnboardingTest` verde |
| `.ai/rules/pages.md` | `app/Filament/Admin/Pages/**` | aplicada | `app/Filament/Admin/Pages/ConfiguracoesDoKit.php:secao:1162` (seção colapsável por helper) |
| `.ai/rules/providers-filament.md` | `app/Providers/Filament/**` | aplicada | `TutorialGuiado::plugin()` nos três painéis (`AdminPanelProvider.php:plugin:285`) |
| `.ai/rules/providers.md` | `app/Providers/**` | aplicada | mesmo princípio da rule (o que o kit entrega nasce no `KitServiceProvider`, porque o arquivo do esqueleto não viaja): `KitServiceProvider::garantirCanalDoTutorialGuiado()` (CT-70) |
| `.ai/rules/settings.md` | `app/Settings/**` | aplicada | propriedade, `mapaDeConfiguracao()` e migration de settings (CT-02..CT-05) |
| `.ai/rules/specs.md` | `wikis/specs/**` | aplicada (violada e corrigida em 2026-10-21) | citações deslocadas pelo diff em `tests/Pest.php` (`kitConfigCom:828` → `:836` em `KitArteTest`, `telasDoKit:245` → `:253` em `LinkDoPainelSemTenancyTest`) e em `KitServiceProvider.php` (+23 linhas em 8 arquivos); `CitacoesDeCodigoTest` e `DiagramasDaArquiteturaTest` [CT-66] reconferidos na suíte |
| `.ai/rules/support.md` | `app/Support/**` | n.a. | `TutorialGuiado` não grava `.env`; `CustomizadorDaInstalacao` só teve citação atualizada |
| `.ai/rules/testes-browser.md` | `tests/Browser/**` | aplicada | `tests/Browser/OnboardingGuiadoTest.php`: `actingAs`, aquecimento por `get()`, `assertPathIs` antes do conteúdo, `assertNoJavaScriptErrors` |
| `.ai/rules/testes.md` | `tests/**` | aplicada (violada e corrigida em 2026-10-21) | `not->toContain($x, 'mensagem')` trocado por `assertNotContains` em `OnboardingGuiadoConsumoTest` (CT-54) e `OnboardingGuiadoInstalacaoTest` (CT-70); `naArvoreDoKit()` nos casos que leem `docs/` e `.ai/rules` |
| `.ai/rules/vendor.md` | `resources/views/vendor/**` | aplicada | a view `filament-onboarding/pages/progress.blade.php` virou autoral (QA-08) e a pasta entrou em `KitUpdate::CAMINHOS_DO_KIT`; `tests/Kit/KitUpdateTest.php` (CT-06/CT-07) 97/97 |
| `.ai/rules/views.md` | `resources/views/**` | aplicada | o comentário `{{-- --}}` novo da view não tem diretiva Blade (`grep "@"` na linha: 0) |

## Quality Gate

| Ciclo | Data | Veredito | Achados | Destino | O que mudou |
|---|---|---|---|---|---|
| 1 | 2026-10-21 | REPROVADO → especificação | 0 Blocker · 2 Major (QA-01, QA-02) · 4 Minor (QA-03..06) · 1 Cosmético (QA-07); 7 dimensões não verificadas (app na URL informada não era o kit) | 1 (wiki) | QA-01: `01` passos 3/4/5 e `03` com as condições reais; QA-02: 293/293; QA-03: coluna `Arquivo` do índice do `04`; QA-04: origens do `08`; QA-05: `.ai/rules/onboarding.md` (chave nova + `visibility_condition`); QA-06: glossário (Tutorial guiado, Jornada, Passo); QA-07: D13, D9, Adendo 3. Ambiente do ciclo 2: SQLite descartável no scratchpad, kit em `http://127.0.0.1:8011`, tutorial ligado e semeado |
| 2 | 2026-10-21 | REPROVADO → especificação | 1 Blocker (QA-08: página "Primeiros passos" dava 500 com passo concluído) · 2 Major (QA-09 N+1, QA-10 PRD) · 4 Minor (QA-11..14); G, H, K não verificadas | 3 → 2 (QA-08, QA-09, QA-11), 1 (QA-10, 12, 13, 14) | QA-08: CT-72 + view publicada corrigida e pasta em `CAMINHOS_DO_KIT`; QA-09: P-25, CT-73/74 + memória por request; QA-11: P-26, CT-75 + valor do cliente fora da mensagem e sem quebra no context; QA-12: CT-16 refeito (5 no `/app`, chave da jornada), rótulo do CT-37 (Q31); QA-10: passo 3 do `01`; QA-13: preparação e esperados do `08`; QA-14: `02` e docs de tenancy |
| 3 | 2026-10-22 | REPROVADO → especificação (teto do loop: escalado ao usuário) | 0 Blocker · 1 Major (QA-18: números e linha de log defasados na wiki) · 3 Minor (QA-15 contraste AA dos botões do pacote, QA-16 Esc no checklist, QA-17 CT-75 deixa passar sanitização só de `
| 4 (autorizado por gsferro além do teto) | 2026-10-22 | APROVADO COM DÉBITO | 0 Blocker · 0 Major · 3 Minor (QA-19 rótulo "Concluída" 3,24:1 no claro; QA-20 dois Esc seguidos reabrem; QA-21 Esc fecha Spotlight e checklist juntos); K não verificada (score do `--mutate` não certificável no Windows; sem revisão adversarial dos CT-B03/B04) | 3 → 2 (QA-19, QA-20); 1 (QA-21) | decisão de gsferro: corrigir QA-19/QA-20 com CT-B vermelho antes e abrir o PR sem ciclo 5; QA-21 não é defeito (D16 do `01`). Feito: QA-19 → CT-B03 linhas 5–8 + `--fio-theme-success` no `kit.css`; QA-20 → CT-B04 linha 3 + `$wire.set('isOpen', false)` na view do launcher; browser da feature 13/13 em 3 rodadas |
`); K não verificada; app passou em A–J | 1 (QA-18), 3 (QA-15, QA-16, QA-17) | decisão de gsferro em 2026-10-22: corrigir os quatro e rodar um 4º ciclo autorizado; Esc fecha o checklist (P-28). Feito: QA-18 (`03`, `02`, `01`); QA-15 → P-27, CT-B03, `kit.css`; QA-16 → P-28, CT-B04, view do launcher; QA-17 → CT-75 refeito (mata M205/M209). A cópia morta `resources/css/vendor/filament-onboarding/onboarding.css` foi apagada a pedido de gsferro |

## Candidatos a Rule

apresentados 3 · gravados 3 · recusados 0 · descartados no gate 3 · poda 0 — 2026-10-22, sessão principal (com MCP: `search-docs` no gate 4, `record-rule`)

- Gravados: `config.md` "Canal de log novo do kit também é garantido no KitServiceProvider" (glob `config/logging.php`, `record-rule`); `livewire.md` (novo) "Valor vindo do navegador nunca entra na mensagem de log" (glob `app/Livewire/**`, `record-rule`); `testes.md` "Segunda pessoa no mesmo teste começa com sessão nova" (seção nova escrita na rule existente, `paths:` inalterado, índice sem mudança). Sem enforcement `arch()`: nenhuma das três é restrição estrutural de classe.
- Descartados: `Date::use(CarbonImmutable)` × retorno tipado de pacote (QA-08) — sem recorrência declarada; contraste por `--fio-theme-*` (QA-15/19) — específico do pacote, já no comentário do `kit.css`; memória de acesso por request (QA-09) — decisão da feature (gate 1).
- Poda: gatilho de 3 features seguidas não conferível só com esta wiki; nenhuma proposta.
- Achados fora do escopo, registrados: o canal `dock-de-acoes-rapidas` (#160) não chega a quem atualiza; o `AssistenteChatWidget` interpola o `$conversaId` público no log.

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
| 14 | 9 | `fw-revisor-diff` — passe de eixos, árvore completa sem `wikis/` | herdado (frontmatter do agente) | `01`, `03`, conversa | RD-01..04 | — (não recuperado: sessão encerrada pelo limite semanal) | RD-02 conferido em `KitUpdate::CAMINHOS_DO_KIT`; RD-03 no `hydrate()` |
| 15 | 9 | `/code-review high main...HEAD` (sub-agente nativo) | herdado | conversa | CR-01..10 | — (idem) | CR-01 reproduzido pelo CT-66 vermelho; CR-07 conferido no `composer.json` |
| 16 | 10 | `fw-executor-ctb` — CT-B do onboarding | herdado | `01`, `02`, conversa | `tests/Browser/OnboardingGuiadoTest.php` (untracked) | — (idem) | pendente: rodar no step 10 |
| 17 | 9 | `general-purpose` do #7 (`SendMessage`) — CT-66..70 para os achados | opus | idem 7 | R28–R32, CT-66..70, M177–M191 | — (idem) | regras e mutantes lidos no `04` |
| 18 | 9 | `fw-executor-ct` do #10 (`SendMessage`) — testes dos CT-66..70 | sonnet | `01`, `02`, conversa | CT-66..70 nos três arquivos `tests/Kit/OnboardingGuiado*` | — (idem) | vermelhos antes das correções (TDD); verdes depois: `pest --filter=Onboarding` 291/291, 2026-10-21 (antes do CT-70 reescrito e do CT-71; hoje 293/293) |
| 19 | 9 | `fw-revisor-diff` — rodada 2, correções não commitadas | herdado | `01`, `03`, conversa | RD2-01..04 | 122 295 tokens · 483 s | `git status` e hashes (sha1) dos arquivos tocados iguais antes/depois; RD2-03 conferido em `KitUpdate::aplicar()` (checkout do arquivo inteiro) |
| 20 | 9 | `/code-review high` — rodada 2 (diff não commitado) | nativo | conversa | CR2-01..09 | 102 565 tokens · 231 s | CR2-01 conferido no helper (sem `assertOk`); CR2-02 confirmado pela `.ai/rules/commands.md` (1ª rodada roda a classe antiga) |
| 21 | 9 | `general-purpose` + `feature-test-design` — re-derivação de R32 pela P-24 reescrita | opus | `01`, `02`, `03`, código, conversa | CT-70 reescrito, CT-71 novo, M190–M195, Q?1 → Q29 | 120 855 tokens · 214 s | contagens refeitas por `grep -c` (71 cenários, 32 regras, 192 mutantes) |
| 22 | 9 | `fw-executor-ct` — CT-70 e CT-71 | herdado | `01`, `02`, conversa | 1 verde, 2 vermelhos (b) antes da correção | 83 532 tokens · 80 s | saída do pest conferida; `app/` e `config/` intactos (`git diff --stat`) |
| 23 | 10 | `general-purpose` — veredito de conformidade com rules | sonnet | `01`, `03`, conversa | 16 rules, 2 violações (`specs.md`, `testes.md`) | 156 477 tokens · 799 s | violações reproduzidas (`CitacoesDeCodigoTest` vermelho; `not->toContain` com mensagem lido) e corrigidas pela sessão |
| 24 | 11 | `fw-qa-gate` — ciclo 1 | herdado | conversa | `06` (REPROVADO → especificação) | 270 313 tokens · 1175 s | `git status --porcelain` vazio antes e depois; `06` gravado só com o conteúdo entre os delimitadores; QA-02 reproduzido (293) |
| 25 | 11 | `fw-qa-gate` — ciclo 2 (kit servido na 8011, SQLite descartável) | herdado | conversa | `06` (REPROVADO → especificação, QA-08..14) | 351 299 tokens · 1816 s | `git status --porcelain` vazio antes e depois; QA-08 reproduzido pelo CT-72 vermelho; QA-09 reproduzido (84 × 52 consultas) |
| 26 | 11 | `general-purpose` + `feature-test-design` — CT dos achados do ciclo 2 | opus | `01`, `02`, `03`, `app/`, conversa | R33–R35, CT-72..75, CT-16 refeito, M196–M207, Q?1/Q?2 → Q30/Q31 | 203 413 tokens · 388 s | contagens por `grep -c` (75 · 35 · 204 · 0) |
| 27 | 11 | `fw-executor-ct` — CT-16, CT-72..75, rótulo do CT-37 | herdado | `01`, `02`, conversa | cortado no `maxTurns` (40) com os 5 `it()` escritos; retomado por `SendMessage`: CT-72/73/75 vermelhos (b), CT-16/74 verdes | 173 531 tokens · 429 s (2 partes) | saída do pest conferida; o CT-73 foi refeito pela sessão (classe a: deriva de consultas do arnês, controles intercalados) |
| 28 | 11 | `fw-qa-gate` — ciclo 3 | herdado | conversa | `06` (REPROVADO → especificação, QA-15..18) | 316 356 tokens · 1743 s | `git status --porcelain` vazio antes e depois; QA-18 conferido (`grep` de 5321/2.387/71 CT no `03`) |
| C3 | 11 | `codex exec --yolo` — rascunho da descrição do PR (só leitura, saída no scratchpad) | não exposto (Codex CLI 0.163.0) | conversa | `scratchpad/pr-body.md` | — | árvore limpa depois; amostra conferida; número da suíte defasado corrigido pela sessão |
| C4 | 11 | `codex exec --yolo` — plano da release v0.52.0 (só leitura) | não exposto (Codex CLI 0.163.0) | conversa | `scratchpad/plano-release.md` | — | árvore limpa depois; `config/kit.php:'version':26`, as 6 ocorrências de `0.51.2` e a tensão do checklist (`:304-323`) conferidas |
| 29 | 11 | `general-purpose` + `feature-test-design` — CT-B03/B04 (P-27/P-28) e CT-75 refeito | opus | `01`, `02`, `03`, `app/`, conversa | R36/R37, CT-B03, CT-B04, MB7–MB16, M208–M210, Q?1..3 → Q32..Q34 | 190 108 tokens · 503 s | contagens (75 · 37 · 207 · 0; CT-B 4); desvio de leitura declarado (o `06` inteiro, sem uso como oráculo) |
| 30 | 11 | `fw-executor-ctb` — CT-B03 e CT-B04 | herdado | `01`, `02`, conversa | 6 vermelhos (b) antes da correção; linha de base do `/infra` já falha (Q32) | 93 252 tokens · 1134 s | screenshot e saída do pest conferidos; `app/` e CSS intactos |
| 31 | 11 | `fw-executor-ct` — CT-75 refeito | herdado | `01`, `02`, conversa | verde no código; vermelho com M205 e M209 | 77 098 tokens · 551 s | mutantes por `auto_prepend_file` sobre cópia no scratchpad; `app/` intacto |
| 32 | 11 | `fw-qa-gate` — ciclo 4 (autorizado além do teto) | herdado | conversa | `06` (APROVADO COM DÉBITO, QA-19..21) | 249 579 tokens · 1611 s | `git status --porcelain` vazio antes e depois; QA-19 conferido no CSS do pacote (`--fio-success` = `success-600`) |
| 33 | 11 | `general-purpose` + `feature-test-design` — linhas do ciclo 4 (QA-19/QA-20) | opus | `01`, `02`, `03`, `app/`, conversa | CT-B03 linhas 5–8, CT-B04 linha 3, MB17–MB21, Q?1 → Q35 | 165 200 tokens · 409 s | contagens (75 · 37 · 207 · 0; MB 21) |
| 34 | 11 | `fw-executor-ctb` — linhas do ciclo 4 | herdado | `01`, `02`, conversa | CT-B03 5 e 7 vermelhas (b); CT-B04 linha 3 verde com o bug (classe a, `press` sequencial), refeita por `SendMessage` com Escape a 5 ms: vermelha 3/3 | 90 888 + 110 169 tokens · 268 + 1026 s | saída do pest conferida; `app/` e `resources/` intactos |
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
- Step 9, rodada 2 (P-24 reescrita): `config/logging.php` NÃO entra em `KitUpdate::CAMINHOS_DO_KIT`; o canal `onboarding` chega ao projeto atualizado pelo `KitServiceProvider::garantirCanalDoTutorialGuiado()`. Fonte corrigida: `01`, D7b e Channel de Log da Feature.
- Gate, ciclo 3: o contraste do tutorial vem das variáveis `--fio-theme-*` do pacote no `kit.css`, mais o fundo do `.fio-button--primary` (P-27); o Esc entrou na view publicada do launcher (P-28). Fonte: `01` Superfície de UI.
- Gate, ciclo 2 (QA-08): a página "Primeiros passos" passou a ler `progress->completed_at` na view publicada, que virou autoral e entrou em `KitUpdate::CAMINHOS_DO_KIT`. Fonte: `01` passo 3. (QA-09) `TutorialGuiado::abre()` memoriza o acesso por tela no request. Fonte: `01` Modelo de Execução.
- Step 9 (P-22): a condição `dashboard_dinamico_no_painel` exige também o `canEdit()` da página (montar), não só o dinâmico ligado. Fonte: `00` P-22, `04` R30, `02` (sem mudança: a condição não é superfície Livewire).
- Inventário de telas dos CT-B: `/{painel}/onboarding` entra em `telasDoKit()` e o `TelasDoKitTest` liga o tutorial com `ligarTutorialGuiado()` (padrão dos agentes de IA), em vez de isenção.

## Notas de Implementação

- O `AuthenticateSession` do painel guarda o hash da senha na sessão: num teste que monta o checklist para duas pessoas, a segunda era deslogada e via o checklist vazio (S9-01). `checklistDoOnboarding()` e `textoDoDashboardDoMaster()` fazem `flushSession()` antes do `actingAs`.
- `Date::use(CarbonImmutable)` do kit quebra qualquer pacote que tipe retorno `Illuminate\Support\Carbon` sobre coluna de data (QA-08): o `StepState::completedAt()` do onboarding dava `TypeError`. Nenhum CT abria a página com passo concluído — o gate pegou no app servido.
- O formatter do Laravel (`allowInlineLineBreaks`) devolve a quebra de linha do JSON do context como linha real: valor do cliente no context forja registro tanto quanto na mensagem (QA-11).
- No processo do teste, os render hooks de painel se acumulam a cada request (o `PanelSwitch` pergunta `canAccessPanel()` mais vezes por GET): contagem de consultas entre requests precisa de controle intercalado (CT-73).
- O dashboard do `/infra` já falha no axe (`color-contrast` serious) sem o tutorial, em widgets alheios (`AccountWidget`, `FilamentInfoWidget`, releases do Composer): linha de base do CT-B03 (Q32), achado fora do escopo desta feature.
- `Log::channel()` com canal sem configuração não lança: o `get()` do `LogManager` cai no logger de emergência (`vendor/laravel/framework/src/Illuminate/Log/LogManager.php:get:140`). Daí o CT-70 afirmar a configuração do canal, não a ausência de exceção.
- Inserir código no topo do `KitServiceProvider` ou de `tests/Pest.php` desloca citações `arquivo:símbolo:linha` espalhadas por docs, providers e testes (+23 linhas, 8 arquivos nesta feature); `DiagramasDaArquiteturaTest` [CT-66] e `CitacoesDeCodigoTest` acusam.
- O `updated{Prop}` do Livewire não é chamável pelo `$wire` (`SupportLifecycleHooks` bloqueia `updated*`), e o snapshot adulterado cai no checksum: o `updatedFlowKey()` cobre todos os caminhos de escrita do `flowKey` (RD2, eixo Propriedade pública).

## Referências Abertas
- `template-00-requisito.md` — step 4 — 2026-10-21
- `entrevista-tres-raias.md` — step 4 — 2026-10-21
- `template-01-plano.md` — step 4 — 2026-10-21
- `template-02-adr.md` — step 4 — 2026-10-21
- `template-03-progresso.md` — step 4 — 2026-10-21
- `pesquisa-step-3.md` — step 3 — 2026-10-21
- `roteamento-e-despacho.md`, `padrao-de-log.md`, `estrutura-criada.md` — pulados: despachos só por `Explore`; o padrão de log do corpo do `SKILL.md` bastou; sem extras `05-*`

## Retrospectiva

- Funcionou: o step 9 em duas rodadas pegou cinco defeitos com a suíte verde (P-20..P-24), e a falsificabilidade em cópia temporária separou correção de refator (CR-04).
- Faltou: a sessão anterior caiu no limite semanal no meio do step 9 sem gravar a `## Revisão do Diff` nem os custos dos despachos 14–18 — o registro foi reconstruído do transcript. Gravar o `03` a cada lote, não no fim.
- Faltou: a redação da P-22 ("quem abre a página") foi escrita antes de conferir que a página abre para todos; a premissa precisou ser revista com o desenvolvedor. Conferir o `canAccess()` real antes de redigir premissa de visibilidade.
- Faltou: a correção do RD-02 (entregar `config/logging.php`) ignorou a regra da primeira rodada do `kit:update` e o arquivo do esqueleto; a rodada 2 a reverteu. A `.ai/rules/commands.md` já dizia isso.

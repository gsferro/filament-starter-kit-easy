# Progresso — Regressão de telas em browser real

**Branch**: `feature/wiki-regressao-telas`
**Início**: 2026-08-14
**Conclusão**: 2026-08-14

## 1. Instalar Pest 5 + `pest-plugin-browser`

- [x] `composer require --dev pestphp/pest:^5.1 pestphp/pest-plugin-laravel:^5.0 pestphp/pest-plugin-browser:^5.0 phpunit/phpunit:^13.3 -W`
- [x] `npm install --save-dev playwright@latest`
- [x] `npx playwright install`
- [x] `/tests/Browser/Screenshots` no `.gitignore`
- [x] Commit `8e5221d`

## 2. Confirmar que o upgrade não quebrou a suíte existente

- [x] `vendor/bin/phpstan analyse` — **0 erros** com PHPUnit 13
- [x] `vendor/bin/pest --group=kit --parallel` — 213 testes, 701 asserções, **7 erros**
- [x] Causa dos 7 erros isolada — **pré-existente**, não causada pelo upgrade (ver Blockers)
- [x] `vendor/bin/pest --group=kit` (série) — **213 testes / 213 passados / 726 asserções**,
      idêntico ao baseline pré-upgrade. O upgrade não quebrou nada

## 3. Registrar a suíte `Browser`

- [x] `tests/Pest.php` — bloco `->group('browser')->in('Browser')`
- [x] `tests/Pest.php` — `pest()->browser()->timeout(20_000)` (não previsto — ver D-03)
- [x] `phpunit.xml` — `<testsuite name="Browser">`
- [x] `composer.json` — script `test:browser` com `npm run build` embutido
- [x] `vendor/bin/pest --group=kit` continua em 213/213, 726 asserções
- [x] **`.github/workflows/ci.yml` corrigido** — `php artisan test` passou a incluir a suíte
      `Browser` e o job de qualidade não tem Node/Playwright. Job `telas` novo +
      `--exclude-group=browser` no job de qualidade. Ver D-05

## 4. Helper de persona para browser

- [x] Confirmado: **nenhum arquivo novo**. O `beforeEach` dos três arquivos de CT-B chama
      `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])` e `usuarioDoKit()`,
      exatamente como `tests/Kit/PaineisTest.php:20-22`

## 5. CT-B de smoke das 52 telas

- [x] `tests/Browser/TelasDoKitTest.php` — CT-B01, CT-B02, CT-B03, CT-B04 — **4 verdes**
- [x] 52 rotas cobertas = **100% das alcançáveis por URL fixa**, conferido por script contra
      `route:list`
- [x] Falhas classificadas: 2 no primeiro run, ambas **(a) CT-B errado** ou **(c) flake**.
      Nenhuma **(b)**: nenhuma tela divergiu do desenhado

## 6. CT-B de perfis

- [x] `tests/Browser/PerfisTest.php` — CT-B05 (dataset de 3), CT-B06 — **verdes**
- [x] `admin`, `infra` e `panel_user` entram no seu painel e veem `403` legível no negado

## 7. CT-B de dark mode

- [x] `tests/Browser/TemaEscuroTest.php` — CT-B07, CT-B08 verdes; CT-B09 `->todo()`
- [x] `->inDarkMode()` e o alternador por `aria-label` funcionam nos dois casos

## 8. Registrar as dívidas técnicas

- [x] `06-divida-tecnica.md` escrito — **10 dívidas**: 1 bloqueante, 3 relevantes, 6 cosméticas
      (7 da rodada de CT-B; DT-08/09/10 vieram do quality gate)
- [x] Confirmado: **nenhum arquivo de `app/` no diff** (`git diff main --stat`)

## 9. Commits individualizados

- [x] `8e5221d` — `:arrow_up:` deps
- [x] `5379368` — `:white_check_mark:` suíte Browser registrada
- [x] `e68d6b4` — `:white_check_mark:` CT-B de smoke
- [x] `0365e43` — `:white_check_mark:` CT-B de perfis
- [x] `b4d8645` — `:white_check_mark:` CT-B de dark mode
- [x] `1bce3a3` — `:green_heart:` CI: job de telas
- [x] `364c767` — `:memo:` dívida técnica
- [x] `7b7a4e8` — `:recycle:` fusão dos CT-B de smoke + ordem das assertions
- [x] `9bc437a` — `:memo:` wiki
- [x] `:memo:` relatório de QA + correções que ele exigiu — `07-relatorio-qa.md`

## Testes

- [x] `tests/Browser/TelasDoKitTest.php` — CT-B01 a CT-B03 (`:39`, dataset de rotas) e CT-B04 (`:146`, telas públicas)
- [x] `tests/Browser/PerfisTest.php` — CT-B05 (dataset de 3), CT-B06 — **verdes**
- [x] `admin`, `infra` e `panel_user` entram no seu painel e veem `403` legível no negado
- [x] `tests/Browser/TemaEscuroTest.php` — CT-B07 (`:26`) e CT-B08 (`:44`) verdes; **CT-B09 nasce `->todo()`** de propósito (`:74`, ver QA-03 do `07`)

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff — 3 achados, 2 aplicados (fusão dos CT-B de smoke e
      corte da duplicação de inventário), ~85 linhas removidas
- [x] `vendor/bin/pint --dirty --format agent` — passed
- [x] `vendor/bin/pest --group=kit` — **213/213, 726 asserções**
- [x] `vendor/bin/pest --testsuite=Browser` — **11 cenários, 10 verdes + 1 todo, 82 asserções**
- [x] `vendor/bin/phpstan analyse` — **0 erros** com PHPUnit 13
- [x] `vendor/bin/pest --exclude-group=browser --list-tests` — 0 testes de `Browser`, o que
      valida a correção do CI (D-05)
- [x] `vendor/bin/pest --group=kit --parallel` — **214/214**, 727 asserções, 196 s. Era
      206/213 com 7 erros antes de DT-03 ser paga
- [~] `vendor/bin/pest --parallel --tia` — **desbloqueado tecnicamente** (o grafo é criado e o
      TIA roda), mas **impraticável** por falta de PCOV: no run completo o `--parallel` derruba
      4 dos 11 CT-B, e em série não termina (abortado após 35 min com Xdebug). Registrado como
      **DT-11**. Contorno usado: dois comandos, `--parallel --group=kit` + `--testsuite=Browser`
- [x] Roteiro *Desenhado × Implementado* do `05-*-browser.md` preenchido
- [x] `feature-quality-gate` invocado — **ciclo 1: REPROVADO → especificação**, depois
      corrigido. Ver `07-relatorio-qa.md`
- [x] Candidatos a rule apresentados ao usuário — **ambos aprovados**:
      `.ai/rules/testes.md` (glob `tests/**`) e `.ai/rules/testes-browser.md`
      (glob `tests/Browser/**`), com o `index.md` atualizado
- [x] `git commit` — branch mergeada em `main`; CI verde em `1a83eec`, com o job `Testes de tela (Pest browser)`

## Blockers

- [x] **Helpers de teste declarados dentro de arquivos de teste quebram qualquer execução
      parcial** — 7 erros `Call to undefined function`, reproduzidos em `--parallel` **e** ao
      rodar um subconjunto de arquivos em série.

  Causa exata, confirmada por `grep -rn "^function"` em `tests/`:

  | Função | Declarada em | Usada por |
  |---|---|---|
  | `usuarioCom()` | `tests/Kit/PaineisTest.php:24` | `tests/Kit/AdminDaOrganizacaoTest.php:22` |
  | `noPainelDa()` | `tests/Tenancy/AdminDaOrganizacaoTest.php:76` | `tests/Tenancy/ConviteUsuarioExistenteTest.php:129,267` |
  | `pivotDePapeis()` | `tests/Tenancy/ConviteTenancyTest.php:54` | `tests/Tenancy/ConviteUsuarioExistenteTest.php:173,213,238,407` |

  Em PHP as funções são globais no processo: quando o Pest carrega **todos** os arquivos, a
  declaração de um vaza para o outro e tudo passa. Em `--parallel` cada worker carrega só o seu
  subconjunto, e a declaração não está lá. O comentário de `tests/Pest.php:72-79` já descreve
  exatamente esta armadilha — e três arquivos a violam.

  **Não é regressão do upgrade**: o mesmo erro aparece rodando os dois arquivos isolados em
  série, sem `--parallel`.

  **Consequência para esta wiki**: `--parallel --tia`, que a skill pede na Verificação Final, é
  **inutilizável** neste projeto até os três helpers migrarem para `tests/Pest.php` — porque o
  `--tia` roda, por definição, apenas os arquivos afetados pelo diff.

  **RESOLVIDO nesta branch**, por decisão explícita do usuário depois de o quality gate expor
  que a dívida bloqueava justamente o `--tia`. Os três helpers foram para `tests/Pest.php`, dois
  clones que existiam só para escapar da colisão desapareceram, e uma guarda automática
  (`tests/Kit/HelpersDeTesteTest.php`, com `token_get_all()`) impede a reincidência.

  Medido: `--parallel` de 206/213 com 7 erros para **214/214** em 196 s, contra 818 s em série.
  Um segundo bloqueio do `--tia` apareceu no caminho e também foi resolvido —
  `tests/Unit/ExampleTest.php` era classe PHPUnit, e o `--tia` aborta a execução inteira ao
  encontrar uma. Detalhes em `06-divida-tecnica.md` → DT-03.

## Quality Gate — Ciclo 1

**Veredito: REPROVADO → especificação**, e **corrigido na mesma rodada**. Relatório completo em
`07-relatorio-qa.md`.

- Blocker: 0 · Major: 1 · Minor: 4 · Cosmético: 0
- Perfil: **completo** (10 dimensões). Dimensão J pulada: natureza `nova`, sem ancestral
- Dimensões dinâmicas delegadas a um **agente avaliador independente**, para reduzir a cegueira
  correlacionada de quem escreveu requisito, plano e testes ser quem julga. Achados dele
  **reverificados por reprodução própria**: 3 confirmados, 1 rebaixado, 4 rejeitados

O que reprovou **não foi código** — a suíte estava verde e estável. Foi **documentação de dívida
errada**, que é o defeito mais caro de um documento cujo propósito é ser verificado depois por
outra pessoa:

| # | Achado | Severidade | Destino | Situação |
|---|---|---|---|---|
| QA-01 | Render hook de plugin vaza entre painéis no mesmo processo PHP: `/admin` isolado tem 0 botões de *Clear Cache*, e 9 depois de visitar `/infra`. **O DOM que os CT-B validam não é o que o usuário vê** | **Major** | 3 + 2 | virou **DT-08** |
| QA-02 | DT-01 atribuía a `critical` de acessibilidade ao `/admin`; o plugin está só no `InfraPanelProvider`. Quem fosse pagar a dívida concluiria que já estava resolvida | Minor | 1 | **corrigido** no `06` e no `05` |
| QA-03 | O CT-B09, como lote, nunca alcançaria a `critical`: `visit([...])` aborta na primeira falha e `/app` já falha no contraste | Minor | 3 | **documentado** no `05`, no `06` e no docblock do teste |
| QA-04 | DT-02 vale **só no tema claro** — no escuro o `dark:fi-text-color-400` atravessa o limiar. Era justamente o eixo que RQ-06 pedia | Minor | 1 | **corrigido** no `06` |
| QA-05 | Nenhum CT-B assere valor de indicador; se o odômetro falhar, o `0` fica permanente e silencioso | Minor | 3 | registrado como não-dívida no `06` |

Quatro suspeitas **rejeitadas** com motivo, para não voltarem no próximo ciclo: `admin_organizacao`
sem CT-B (só existe com tenancy, fora de escopo), senha preservada após login inválido (default do
Filament), avatar do perfil "quebrado" (artefato de captura pré-hidratação) e N+1 em `/admin/users`
(medido: 13 queries constantes com 1, 10 e 30 usuários).

Dimensões que passaram limpas: **D** (nenhum PII em log — e-mails aparecem mascarados),
**E** (nenhum N+1), **F** (a tela de 403 é boa: pt-BR, explica, tem saída, zero vazamento de
stack trace), **I** (nenhum segredo no CI; `--exclude-group=browser` exclui exatamente 11 de 227).

**Ciclo 2 não foi necessário**: nenhum achado exigia reimplementação, e todos tinham destino claro.

## Desvios do Plano

<!-- Onde a implementação divergiu do PRD e por quê -->

- **Passo 1 executado antes de a wiki existir** — deliberado, e a razão está em ADR-04: quatro
  fatos sobre o contrato do `pest-plugin-browser` decidem o desenho dos CT-B, e nenhum está
  explícito na doc. Plano escrito sem eles teria exigido Herd e desenhado login por UI em 52
  telas.
- **`search-docs` do Laravel Boost indisponível nesta sessão** — o MCP não está ativo. A
  pesquisa de doc do Pest browser foi feita na doc oficial (`pestphp.com/docs/browser-testing`),
  que é o fallback que a própria skill prevê para Pest 5 e para o plugin de browser (ambos são
  lacuna declarada do `search-docs`).
- **Playwright MCP não usado**, embora RQ-10 permita — não está configurado, e o fallback
  nativo do plugin resolveu todos os seletores. ADR-05.
- **D-01 — o `05` não fixava o texto do dashboard.** A especificação pedia *"`assertSee()` de um
  texto do layout"* sem nomeá-lo; o `<h1>` real é `Painel de Controle` (tradução pt_BR do
  Filament), não `Dashboard`. Causa raiz é especificação vaga, não implementação divergente.
- **D-02 — aritmética do inventário estava errada na primeira escrita.** Dizia 76 rotas GET e 48
  telas alcançáveis; o real é **74** e **52**. Faltavam as 6 rotas que exigem estado ou token
  (`/*/screen/lock`, `/*/password-reset/reset`) na conta de exclusões. Conferido por script
  contra `route:list` e corrigido em `00`, `01`, `02`, `04` e `05`.
- **D-03 — o teto de espera do plugin não estava previsto.** `pest()->browser()->timeout(20_000)`
  foi necessário: o default de 5 s não alcança o primeiro boot de um painel Filament em teste, e
  o CT-B06 falhava em `assertPathIs` com o dashboard já renderizado no screenshot.
- **D-06 — flake de ordem de assertion no CT-B06.** `assertPathIs` é a assertion que espera a
  navegação; encadeado DEPOIS de `assertSee`, o `assertSee` é avaliado contra o snapshot da
  página anterior. A primeira escrita inverteu os dois e passou; falhou na reexecução, com o
  screenshot mostrando o dashboard já renderizado. Ordem correta: `press` → `assertPathIs` →
  `assertSee`. Corrigido sem `wait()` fixo. Ver D-06 no arquivo 05.
- **Cortes do `/ponytail:ponytail-review` aplicados**, por decisão do usuário: os três cenários
  de smoke (CT-B01/02/03) fundidos num `it()` com dataset por painel (~35 linhas), e os roteiros
  de rota do arquivo `05` mais a tabela `## Mapeamentos` do PRD substituídos por ponteiro para a
  fonte única (~50 linhas de wiki). O inventário das 52 rotas passa a viver em **dois** lugares
  — a tabela `## Superfície de UI` do PRD e o array do teste — e não em três, que foi o que
  produziu D-02.
- **D-07 — a sonda inicial produziu um erro de documentação que só o quality gate pegou.** Ela
  registrou o botão *Clear Cache* em `/admin`, e isso entrou em DT-01 e no CT-B09. O botão é do
  `/infra`; apareceu no `/admin` por vazamento de render hook no mesmo processo (DT-08). É o
  contra-exemplo honesto de ADR-04: sondar antes de planejar deu quatro fatos certos e **um
  errado**, e o errado sobreviveu a duas revisões porque a sonda parecia evidência direta.
- **D-05 — regressão que esta entrega introduziu, e corrigiu.** Registrar a suíte `Browser` em
  `phpunit.xml` fez `php artisan test` sem argumentos passar a incluí-la, e o job `qualidade` do
  CI não tem Node nem browsers do Playwright — quebraria em toda tela com `ViteException`.
  Corrigido com `--exclude-group=browser` no job de qualidade e um job `telas` novo. **Não** foi
  registrado como dívida: dívida é o que se decide não pagar; isto era defeito da entrega.

## Notas de Implementação

<!-- Descobertas durante o trabalho que não estavam no plano -->

- **O plugin de browser sobe servidor próprio in-process.** Nenhuma doc consultada afirma isso
  de forma explícita; foi descoberto pela URL que a sonda imprimiu
  (`http://127.0.0.1:60212/app`) e pelo fato de `amphp/http-server` entrar como dependência
  transitiva do plugin. Efeito prático grande: `:memory:`, `RefreshDatabase` e `actingAs()`
  todos continuam valendo, o que é o oposto do que um teste de browser costuma exigir.
- **A suíte `tests/Kit` leva ~4 minutos em `--parallel` e mais de 10 em série.** Isso é dívida
  por si (DT-06) e é a razão de a suíte de browser nascer no grupo `browser`, fora do
  `composer test:kit`.
- **O painel `/app` não tinha nenhuma tela com smoke.** Descoberto ao inventariar a cobertura
  para escrever `04-casos-de-teste.md`. Os CT-B passam a cobrir as 13, mas a assimetria na
  suíte de backend permanece — DT-04.
- **`assertNoAccessibilityIssues()` encontrou dois problemas reais na primeira execução**, ambos
  em pacote de terceiro. DT-01 e DT-02.

## Retrospectiva

- **Funcionou bem**: sondar o contrato da ferramenta antes de escrever o PRD (ADR-04). Os CT-B
  nasceram com seletor certo, e o pré-requisito do `npm run build` foi descoberto na sonda em
  vez de em 52 falhas simultâneas.
- **Funcionou bem**: `visit([...])` em lote. 52 telas em 4 cenários mantém a suíte em tempo
  utilizável, o que é a diferença entre suíte que roda e suíte que ninguém roda.
- **Faltou no plano**: nada previa que o **baseline** fosse o primeiro achado. A skill trata o
  baseline como pré-requisito burocrático do passo 2; aqui ele produziu DT-03, que é a dívida
  mais consequente da rodada — porque bloqueia justamente o `--tia`, a feature que motivou o
  upgrade para Pest 5.
- **Faltou no plano**: o PRD contou o inventário de telas de cabeça, e errou (D-02). A contagem
  deveria ter saído de script contra `route:list` desde a primeira escrita — foi o que a
  verificação usou para corrigir. Inventário conferido à mão é inventário errado.
- **Faltou no plano**: nada previu que registrar uma testsuite nova em `phpunit.xml` mudaria o
  que `php artisan test` executa, e portanto quebraria o CI (D-05). A seção
  `## Impacto em Features Existentes` listou "CI: `npm run build` passa a ser pré-requisito",
  o que era a metade certa da observação — e parou antes da consequência.
- **Para a próxima invocação**: para todo upgrade de major de ferramenta de teste, a seção
  `## Impacto em Features Existentes` deveria exigir *"rodar o baseline em série E em paralelo"*
  — foi a divergência entre os dois modos que revelou DT-03, a dívida mais consequente da
  rodada. E deveria exigir *"reler o CI depois de mexer em `phpunit.xml`"*.
- **Funcionou bem, e é o achado metodológico da rodada**: delegar as dimensões dinâmicas do
  quality gate a um **agente independente**, instruído a ser cético. Ele encontrou o vazamento
  de render hook (DT-08) que invalidava a proveniência de DT-01 — algo que eu não acharia,
  porque a sonda que produziu o erro era *minha* e parecia evidência direta. A separação de
  poderes do princípio 2 da skill não é formalidade.
- **Faltou no plano**: reverificar a evidência da sonda contra o código antes de escrever
  dívida. `grep FilamentClearCachePlugin app/Providers/` levaria 5 segundos e teria evitado
  QA-02 inteiro.

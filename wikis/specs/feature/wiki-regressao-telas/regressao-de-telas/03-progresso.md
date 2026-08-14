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

- [x] `06-divida-tecnica.md` escrito — **7 dívidas**: 1 bloqueante, 3 relevantes, 3 cosméticas
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
- [ ] `:memo:` wiki

## Testes

- [ ] `tests/Browser/TelasDoKitTest.php` — CT-B01 a CT-B04
- [x] `tests/Browser/PerfisTest.php` — CT-B05 (dataset de 3), CT-B06 — **verdes**
- [x] `admin`, `infra` e `panel_user` entram no seu painel e veem `403` legível no negado
- [ ] `tests/Browser/TemaEscuroTest.php` — CT-B07, CT-B08, CT-B09

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff — 3 achados, 2 aplicados (fusão dos CT-B de smoke e
      corte da duplicação de inventário), ~85 linhas removidas
- [x] `vendor/bin/pint --dirty --format agent` — passed
- [x] `vendor/bin/pest --group=kit` — **213/213, 726 asserções**
- [x] `vendor/bin/pest --testsuite=Browser` — **11 cenários, 10 verdes + 1 todo, 82 asserções**
- [x] `vendor/bin/phpstan analyse` — **0 erros** com PHPUnit 13
- [x] `vendor/bin/pest --exclude-group=browser --list-tests` — 0 testes de `Browser`, o que
      valida a correção do CI (D-05)
- [~] `vendor/bin/pest --parallel --tia` — **bloqueado por DT-03**, não por esta entrega. Ver
      Blockers. O contorno é rodar em série, que é verde
- [x] Roteiro *Desenhado × Implementado* do `05-*-browser.md` preenchido
- [ ] `feature-quality-gate` invocado, veredito registrado
- [ ] Candidatos a rule apresentados ao usuário
- [ ] `git commit`

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

  **Não corrigido aqui**: mover os helpers alteraria três arquivos de teste de features
  alheias, fora do escopo desta wiki (RQ-07 pede identificar). Registrado como
  `06-divida-tecnica.md` → **DT-03**, com severidade **bloqueante** e o diff exato da correção.

  **Contorno adotado**: a Verificação Final desta wiki usa `vendor/bin/pest --group=kit` em
  série, que é o comando sob o qual a suíte é verde.

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

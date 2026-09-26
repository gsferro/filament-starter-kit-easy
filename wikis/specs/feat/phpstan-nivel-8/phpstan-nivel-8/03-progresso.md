# Progresso — PHPStan no level 8, e as pendências das últimas rodadas

## Baseline (antes do primeiro commit)

- [x] Suíte em `main` @ `acf94fb` — **2.993 testes, 2.990 passaram, 3 pulados, 12.582 asserções,
  0 falhas**, 188,6 s — `php artisan test --testsuite=Unit,Feature,Kit,Tenancy --parallel --compact`,
  2026-09-26
- [x] Pint `passed`, PHPStan L7 `0 erros`, Filacheck `All 17 rules passed!`, 2026-09-26
- [x] Level 8: **48 erros** — `phpstan analyse --level=8 --error-format=raw | grep -c identifier=`,
  2026-09-26. Por arquivo (`cut -d: -f1 | sort | uniq -c`): CreateRole 3, EditRole 3,
  DescobreCardsDoPainel 9, RegistroPorConvite 5, TelaBloqueio 3, TelaLogin 1, LoginSocialController 1,
  ExigirEmailVerificado 1, AssistenteChatWidget 10, DefinirSenhaPorEmail 2, Convite 5,
  PrimeiroAcessoSocial 1, ImportadorDoKit 3, migration harden_onboarding 1

## 1. Classe A — o tipo diz o que o fluxo garante
- [x] `getCurrentOrDefaultPanel()` → `getCurrentPanel() ?? getDefaultPanel()` (TelaLogin, DefinirSenhaPorEmail, TelaBloqueio) — `grep -rn getCurrentOrDefaultPanel app` → só o comentário de `TelaRecuperarSenhaUnificada:55`, 2026-09-26
- [x] CreateRole / EditRole leem do `$papel` — e o `instanceof` subiu para antes do `firstOrCreate`, 2026-09-26
- [x] AssistenteChatWidget: `assertContexto(): User` + leitura única de `mensagemPendente` — `historico()` com guarda local, 2026-09-26
- [x] RegistroPorConvite via `convite()`, 2026-09-26
- [x] Convite: `filter()` + `diff()`, 2026-09-26
- [x] migration: guarda do `$survivor` (`continue`), 2026-09-26

## 2. Classe B — URL de painel nula
- [x] RegistroPorConvite (`urlDeLoginDoApp()`, `urlDaOrganizacao()`), TelaBloqueio, PrimeiroAcessoSocial com `?? url($painel->getPath())`; LoginSocialController com o login do painel padrão (P-03), 2026-09-26

## 3. Classe C — invariante com guarda
- [x] DescobreCardsDoPainel `painelCorrente()` + ternário morto removido, 2026-09-26
- [x] Convite `papelOuFalha()` na primeira linha útil dos dois verbos, antes de qualquer escrita (CT-13), 2026-09-26
- [x] ImportadorDoKit `registro()`, 2026-09-26

## 4. Classe D — anotação do vendor
- [x] ~~`ignoreErrors` do ExigirEmailVerificado~~ → guarda de invariante no middleware (ADR-05, RD-06); `git diff main -- phpstan.neon` → só a linha do `level`, 2026-09-26

## 5. O nível sobe e fica travado
- [x] `phpstan.neon` `level: 8` — `vendor/bin/phpstan analyse` → `{"result":"passed","errors":0}`, 2026-09-26
- [x] guarda em `QualidadeDeCodigoTest` — CT-01…CT-08 (CT-03 é o comando `composer types:check` → `[OK] No errors`); 3 mutantes no neon derrubam o caso certo e o arquivo volta byte a byte (md5), 2026-09-26

## 6. Pendências documentais das wikis anteriores
- [x] `cobertura-de-testes/03` — presets e roadmap fechados com evidência; a caixa do `CoberturaDeTestesTest` depende do 6b, 2026-09-26
- [x] `plumb-e-dividas-tecnicas/03` — as duas caixas fechadas com a baseline de hoje e o #108, 2026-09-26

## 6b. Reconciliação de testes da wiki de cobertura
- [x] Correções de especificação no `04` da cobertura (CT-07, CT-10, CT-23) — `grep -n "alterado em 2026-09-26"` → 3 blocos, 2026-09-26
- [x] Renumeração + cenários faltantes (`fw-executor-ct`) — 27 CTs com teste (`comm -12` dos IDs → 27); `php artisan test` dos 4 arquivos → 107 passaram, 1.461 asserções, 2026-09-26
- [x] Não automatizáveis declarados no `04` da cobertura (CT-01, 02, 03, 25, 28) + CT-49/50 como referência cruzada — `diff` de IDs volta **só** com esses 7, 2026-09-26

## 7. Docs e CHANGELOG
- [x] nível corrente nas docs pt/en, `site-vitepress` pt/en, READMEs, CONTRIBUTING, `wikis/qualidade-de-codigo.md`, `wikis/README.md`, `rector.php`, roadmap §8.1 (e §9 novo) — frases históricas mantidas, 2026-09-26
- [x] ADR-04 decidida — saída (b): `$baselineVigente` com mapa de renomeação auditável em `tests/Kit/SiteDeDocumentacaoTest.php`; o `$baseline` cru segue intacto para o CT-24. `--filter` CT-01/02/03/24 → 11 passaram, 2026-09-26
- [x] CHANGELOG — `[Unreleased]` com Alterado, Corrigido e Testes (commit `87164c6`, conceito de mutação corrigido em `501b2cb`), 2026-09-26

## 8. Release
- [ ] `config/kit.php` 0.41.0, PR, merge, tag

## Testes
- [x] `04-casos-de-teste.md` implementado — 22 de 23 CTs com teste; o CT-03 é comando de gate por desenho. `diff` de IDs → só `< CT-03`, 2026-09-26

## Verificação Final
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse` — level 8, 0 erros
- [ ] `vendor/bin/filacheck --fix`
- [ ] suíte Unit, Feature, Kit, Tenancy `--parallel` contra a baseline
- [x] `/code-review high main...HEAD` + passe de eixos (step 6.5) — 16 achados, 13 aceitos, 3 rejeitados com prova; re-revisão única do delta: 0 Blocker/Major, 3 Minor corrigidos (tabela `## Revisão de código do diff`), 2026-09-26
- [x] IDs `[CT-nn]` do teste ⊆ `04` e vice-versa — `diff` → `< CT-03` (comando de gate), `< CT-27`, `< CT-28` (materializados em `[CT-12]`/`[CT-13]` do `KitCoberturaTest`, namespace da cobertura), todos declarados no `04`, 2026-09-26
- [x] Citações `arquivo:símbolo:linha` reverificadas — script do step 7 → 8/8 ok, depois de 4 corrigidas (`urlDoPainel` inexistente e três linhas de docblock), 2026-09-26
- [x] Docs pt/en, CHANGELOG e README reconciliados — nível, badges (`PHPStan-level%208`, casos de teste 1.668), "três exceções", tabela de não adotados e conceito de mutação; `SiteDeDocumentacaoTest` 68/68, 2026-09-26
- [ ] `git commit`

## Revisão de código do diff (step 6.5)

Dois passes cegos ao plano, disparados juntos sobre `main...c33cee8`: `/code-review high` (10 achados) e
`fw-revisor-diff` (6 achados). Nove eram o mesmo defeito visto pelos dois, ou achados distintos
aceitos; três foram rejeitados com prova.

| Achado | Eixo / tema | Decisão | Evidência |
|---|---|---|---|
| CR#1 = RD-01 | `[CT-02]` vermelho em projeto instalado | **aceito** — Blocker. Adendo 1 / RQ-07, CT-02 alterado, teste corrigido | prova com `.github` renomeado: `tests 3, passed 1, skipped 2` |
| CR#2 | fallback da tela de bloqueio daria 401 vazio | **rejeitado** | medido: `AuthenticationException::redirectTo()` cai no callback estático (`vendor/laravel/framework/src/Illuminate/Auth/AuthenticationException.php:redirectTo:68-69`), que `withMiddleware()` registra como `route('login')` (`vendor/laravel/framework/src/Illuminate/Foundation/Configuration/ApplicationBuilder.php:redirectGuestsTo:291`); a rota `login` existe (`php artisan route:list --name=login`) |
| CR#3 = RD-02 | `redirect(null)` em `DefinirSenhaPorEmail` e `RegistroPorConvite::register` | **aceito** — Adendo 1 / RQ-08, CT-23 | CT-23 `financeiro` vermelho antes (`Component did not perform a redirect`), verde depois |
| CR#4 | `phpstan dump-parameters` 8× por suíte | **aceito** — memoizado numa `static` | — |
| CR#5 | `responder()` sem caso para visitante | **aceito** — Adendo 1 / RQ-10, linha nova no CT-16 | verde na primeira execução: a guarda já estava certa; o CT fecha a lacuna de cobertura |
| CR#6 = RD-04 | `Paineis::url()` reimplementado e citado como `urlDoPainel()` | **aceito** | `PrimeiroAcessoSocial` usa `Paineis::url()`; citação corrigida |
| CR#7 | = RD-04 | — | — |
| CR#8 | a mesma expressão colada 4× | **aceito** — `Paineis::correnteOuPadrao()` | — |
| CR#9 = RD-05 | comentários de teste falsos (CT-22; ponteiro do CT-18) | **aceito** | CT-22 remedido contra a `main`: `passed 2` |
| CR#10 | comentários inline × regra do CLAUDE.md | **rejeitado** | a base usa comentário inline explicativo em todo `app/` (ex.: o próprio `Convite::aceitarComoUsuarioExistente`); os novos explicam ordem de efeito colateral, que não se lê do código |
| RD-03 | `?? url('/')` em `LoginSocialController::urlDoPainel` | **rejeitado** | anterior ao diff, fora de escopo pela premissa P-06 |
| RD-06 | a guarda de invariante não foi tentada no middleware | **aceito, e mudou a decisão** — Adendo 1 / RQ-09, ADR-05 substitui a ADR-03 | CT-06 vermelho antes (`actual size 4 matches expected size 3`), verde depois |

**Falsificabilidade das correções**: CT-06 e CT-23 `financeiro` nasceram vermelhos contra o código
anterior à correção — **2 de 2 falham sem o fix**. CT-02 (guarda de teste) e CT-16/`responder`
(guarda já correta) não são correções de app.

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `app.md` — papel dentro de `ContextoDePapeis` | `app/**` | aplicada | `Convite::atribuirPapel()` segue em `ContextoDePapeis::em()`; só ganhou o parâmetro `$papel` |
| `auth.md` — guarda de laço em `mount()` por método, redirect por `HttpResponseException` | `app/Filament/Pages/Auth/**` | aplicada | `TelaBloqueio::mount()` continua saindo por `sairPara()` → `HttpResponseException` |
| `auth.md` — página fora do painel resolve o painel da conta antes de `canAccessPanel()` | idem | n.a. | os usos novos de `Paineis::correnteOuPadrao()` são log e URL de login, nenhum consulta `canAccessPanel()` |
| `filament.md` — papel e permissão pela API do spatie | `app/Filament/**` | aplicada | `CreateRole`/`EditRole` seguem em `syncPermissions()` |
| `filament.md` — asserção de identidade no model | idem | aplicada | `exigirDono()` continua a primeira linha de `aceitarComoUsuarioExistente()`; `papelOuFalha()` vem depois dela |
| `filament.md` — cartão de hub só por `DescobreCardsDoPainel` | idem | aplicada | o concern mudou por dentro; nenhum `CardItem::make()` fora dele |
| `models.md` | `app/Models/**` | n.a. | `Convite` não ganhou Resource, trait nem mídia |
| `testes.md` — helper cruzado em `tests/Pest.php` | `tests/**` | aplicada | `grep` de cada helper novo: definido e usado num arquivo só |
| `testes.md` — `toContain()` variádico | idem | aplicada | `grep` por `toContain(x, 'mensagem')` nos testes do diff → vazio |
| `testes.md` — asserção de ausência filtra comentário | idem | aplicada | CT-23 da cobertura usa `semComentarioYaml()`; CT-08 conta `@phpstan-ignore` no texto cru de propósito (o ignore **é** comentário) |
| `specs.md` — citação conferida por símbolo | `wikis/specs/**` | aplicada | script do step 7 → 8/8 ok, depois de 4 corrigidas |

## Quality Gate

<!-- Preenchido no step 8. Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| a anotação errada do `EnsureEmailIsVerified` se corrige por stub | o validador de stub do PHPStan acusa `class.notFound` para `Response` (Symfony e Illuminate), com e sem `use`, dentro e fora do projeto — medido em 4 tentativas | ADR-03 reescrita para `ignoreErrors`; `00` RQ-06 marcado `(alterado em …)` |
| `AssistenteChatWidget` tem 11 erros | **10** (`uniq -c`) | tabela do `01` corrigida; B = 7, C = 13 |
| a caixa de `CoberturaDeTestesTest` no `03` da cobertura só está desatualizada | o `diff` de IDs mostra 22 CTs sem teste e 3 IDs fora do `04` | passo 6b criado no `01` |
| o `--min=0` precisa de correção (reconciliação de 25/09) | já recusado em `app/Console/Commands/KitCobertura.php:pisoPedido():137-162` | só o executor é avisado; nenhuma correção de app |
| `Filament::getPanel('app')` pode ser nulo | o level 8 **não** acusa — a facade é tipada sem `?` | nenhuma mudança; fora do escopo |

### Varredura da classe irmã

Nenhuma classe nova. A lista paralela relevante é a do **nível do PHPStan**, citado em 10+ arquivos
de prosa (`grep -rn "level 7"` fora de `vendor/`, `wikis/specs/` e `CHANGELOG`) e numa fixture
congelada — passo 7 e ADR-04.

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | seis seções "nenhum" no PRD | sim, fundidas numa | `01` |
| 2 | guarda "toda `ignoreErrors` com path" é yagni | **recusada**: é a forma mecânica de RQ-06 | `01` passo 5 |
| 3 | `filter()`+`reject()` avalia o Validator duas vezes | sim, `diff()` | `01` passo 1 |

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Auditoria do retorno |
|---|---|---|---|---|---|---|
| 1 | 4 | `general-purpose` — derivar o `04` seguindo `feature-test-design` (rodou a adversarial rodada 1 por dentro) | opus | `01`, `02`, conversa | 18 CTs, 7 premissas | `git status`: só o `04` criado; `@phpstan-ignore` 2+1 conferidos por grep |
| 2 | 6 | `ponytail-review` sobre `01`/`02` | em linha (skill) | — | 3 sugestões, 2 aplicadas | — |
| 3 | 6b | `fw-executor-ct` — reconciliar o `04` da cobertura × testes | sonnet | `01`/`02`/`03` da cobertura, conversa | 27 CTs com teste, 1 vermelho (b) no CT-27 | `git status` só nos arquivos permitidos; 3 amostras lidas (CT-10, CT-23, helpers sem uso cruzado). **Reprovado em parte**: citações `[CT-nn]` em docblock mascaravam 4 IDs no `diff` — corrigidas pela sessão |
| 4 | 4 | `fw-adversario-ct` — rodada 2 | opus | tudo menos `00` + `04` | **não convergiu**: 12 implementações erradas, 10 oráculos fracos; R6 misturava duas regras | M-I rejeitado com motivo; demais aceitos |
| 5 | 4 | mesmo analista do #1 — aplicar a rodada 2 (R6 → R6a/R6b) | opus | `01`, `02`, código | 22 CTs, 60 mutantes, 9 regras | `grep -c` conferido; só o `04` editado |
| 6 | impl. | sem despacho — edição cirúrgica em 14 arquivos interdependentes (mesma exceção vale para docs) | sessão | — | L8: 48 → 0 | `phpstan analyse` → 0 erros |
| 7 | impl. | `fw-executor-ct` lote A — CT-01…08 (`QualidadeDeCodigoTest`) | sonnet | `01`, `02`, conversa | — | — |
| 8 | impl. | `fw-executor-ct` lote B — CT-09…22 | sonnet | `01`, `02`, conversa | 37 casos verdes; falsificabilidade 9/11 | `git status`/`git stash list` limpos; 6 vermelhos que ele chamou de "pré-existentes" eram da branch (citação deslocada, sentinela ausente, badges) — **reprovado nesse ponto**, corrigidos pela sessão |
| 9 | 6.5 | `fw-revisor-diff` — eixos sobre `main...HEAD` | opus | `01`, `03`, conversa | 6 achados | 6/6 reproduzidos; RD-03 rejeitado |
| 10 | 6.5 | `/code-review high main...HEAD` | skill | — | 10 achados | CR#2 **refutado por medição**; CR#10 rejeitado |
| 11 | 6.5 | mesmo analista — CTs do Adendo 1 | opus | `01`, `02`, código | 23 CTs, 10 regras | só o `04` editado |
| 12 | 6.5 | `fw-executor-ct` — CTs do Adendo 1, vermelhos antes do fix | sonnet | `01`, `02`, conversa | 2 vermelhos (b) esperados | ambos os vermelhos conferidos; `.github` restaurado (`ls -d .github`) |
| 13 | 6.5 | sem despacho — correção do Adendo 1 (8 arquivos pequenos e interdependentes) | sessão | — | L8 0 erros; 164 testes afetados verdes | — |

## Blockers
- Nenhum.

## Desvios do Plano

- **P-03 contradiz a ADR-02 num ponto só**: o login social que volta de painel sem login vai ao login
  do painel **padrão**, não à raiz do painel de origem. O `04` é o oráculo; a ADR-02 ganhou a exceção
- **A guarda do convite subiu de `atribuirPapel()` para o topo dos dois verbos** (`papelOuFalha()`):
  o CT-13 exige total de usuários igual e convite pendente depois da falha, e `atribuirPapel()` roda
  depois do `User::create()` e do consumo atômico
- **Docs tinham dois erros anteriores a esta entrega**: afirmavam que o level 7 cobra nulo não
  checado (é o `checkNullables` do 8), e contavam **duas** (docs) / **uma** (wiki) exceções no
  `ignoreErrors` quando já eram três

## Notas de Implementação

- **Errei o conceito de mutação, e o quality gate pegou (QA-02).** Li *"225, sendo 4 não testados"*
  como *"zero sobreviventes, 4 mutantes em linha sem teste"* e publiquei isso nas docs e no CHANGELOG
  como correção. É o contrário: no `pest-plugin-mutate`, `UNTESTED` é o mutante com o qual o processo
  de teste **passou** — o sobrevivente (`vendor/pestphp/pest-plugin-mutate/src/MutationTest.php:hasFinished:120`,
  que emite `mutationEscaped`); linha sem teste é `UNCOVERED`. O CT-27 que o executor escreveu
  tratava "não testados" como sobreviventes — **certo** — e eu o contrariei no texto. A doc original,
  que só dizia "4 não testados", estava mais certa que a minha "correção". Os números remedidos
  continuam valendo (`KitCobertura`: 158 mutantes, **4 sobreviventes**, 97,47 %, 38 s); o que mudou é
  o que eles significam, e os sobreviventes viraram cenário (QA-02 → destino 3)
- **Citação de ID em docblock é ID para o `diff`.** Quatro docblocks citavam `[CT-51]`…`[CT-53]` e
  `[CT-01]`..`[CT-12]` como história; o `grep` do step 7 não distingue citação de declaração, e o
  `[CT-01]` citado escondia que o CT-01 real não tem teste. Citação histórica vai sem colchete

- **`getCurrentOrDefaultPanel(): ?Panel` nunca devolve nulo.** O corpo é
  `getCurrentPanel() ?? getDefaultPanel()`, e `getDefaultPanel(): Panel`. A assinatura do vendor é
  mais larga do que o comportamento — escrever o corpo inline é o que dá ao analisador o tipo real
- **`--error-format=raw` não funciona sob o `laravel/pao`**: a saída vira JSON truncado de agente.
  `PAO_DISABLE=1` na frente do comando devolve o formato pedido

## Retrospectiva

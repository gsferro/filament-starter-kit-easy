# Progresso — Desbloqueio da sessão volta para a página em que a pessoa estava

**Estado**: em implementação

**Base do PR**: `main`

## Processo do pedido

- [x] Analisar se o pacote base tem a funcionalidade nativa — parcial: só o bloqueio manual guarda a página; `01`, `## Contexto`, 2026-10-20
- [x] Usar o `codex --yolo` como auxiliar — análise independente do vendor, despacho 1, 2026-10-20
- [x] Usar o Blueprint no processo — `filament/blueprint` ligado por `bp:on` (sem estado commitado), `planning/overview.md` + `checklist.md` aplicados em `01`, `## Consistência do Ciclo de Vida (Blueprint)`, 2026-10-20
- [x] Criar branch específica — `feat/desbloqueio-volta-a-pagina`, 2026-10-20
- [ ] Abrir o PR
- [ ] Tag + release
- [ ] Quatro cenários do checklist de release sobre a tag publicada

## 1. Middleware que guarda a página ao travar

- [x] `app/Http/Middleware/BloquearSessaoComRetorno.php` criado, estendendo o `Locker` do pacote — `make:middleware`, 2026-10-20
- [x] Bind `Locker::class` → `BloquearSessaoComRetorno::class` em `AppServiceProvider::register()` — 2026-10-20
- [x] Log `[BloquearSessaoComRetorno@handle]` no canal `autenticacao` — só path, sem query, 2026-10-20

## 2. Fallback do desbloqueio para a raiz do painel e guarda da sessão destravada

- [x] `TelaBloqueio::mount()` grava `url.intended` com a raiz do painel quando vazia — `Paineis::url()`, 2026-10-20
- [x] Guarda do `mount()` lê o valor da marca (`!== true`), não a existência da chave — 2026-10-20

## 3. Desbloqueio pelo provedor social volta à página

- [x] `LoginSocialController::retorno()` honra `url.intended` quando destrava a sessão da mesma conta — e descarta a página para outra conta (P-06), 2026-10-20
- [x] Contexto `desbloqueio` no log de `retorno()` — 2026-10-20

## Testes

## Tickets

Não fatiado — 2026-10-20: 3 RQ vigentes, 31 CT, compactação: não, 3 perguntas de requisito — nenhum sinal

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty`
- [x] `vendor/bin/filacheck --fix` — All 17 rules passed, 2026-10-20
- [ ] `composer types:check`
- [ ] Testes da feature verdes
- [ ] Suítes Kit e Tenancy sem regressão além da baseline de `main`
- [ ] `pest --mutate` na classe nova: score, duração e sobreviventes listados
- [ ] **`/code-review high main...HEAD` + passe de eixos (step 9)**, antes da reconciliação
- [ ] Desvios propagados ao `01`/`02`/`04` de origem, marcados `*(alterado em …)*`
- [ ] `rastreabilidade.sh {wiki}` silencioso
- [ ] `checkbox-sem-evidencia.sh {wiki}` silencioso
- [ ] Citações reverificadas: `citacoes.sh {wiki}` silencioso
- [ ] `ids-ct.sh {wiki}` silencioso
- [ ] `conformidade-rules.sh {wiki} main` silencioso
- [ ] Falsificabilidade em cópia temporária, revertendo só o patch do fix
- [ ] Docs pt/en, CHANGELOG e README reconciliados com o comportamento final
- [ ] `git commit`

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|

## Quality Gate

## Candidatos a Rule

## Auditoria Pré-Implementação

Entendimento confirmado: 2026-10-20 — autorização existente do solicitante no pedido ("crie uma branch… ao finalizar, abra o PR… rode o fluxo completo") — 1 rodada; perguntas: 0 fato, 0 desenho, 2 requisito (Q1, Q2, seguidas como P-01 e P-02, sem bloqueio)

### Análise do vendor (step 3)

Feita em duas vias independentes — a sessão e o Codex (`codex exec --yolo`, somente leitura, sem a conclusão da sessão no prompt).
As duas concordam: o retorno é nativo só no bloqueio manual; o ramo de inatividade não grava `url.intended`, e o ramo
"já travada" grava `url()->previous()`, que prefere o `Referer` e cai em `/`. O Codex achou, além disso, a guarda
`has('lockscreen')` do `mount()` que não dispara depois de destravar (virou P-05) e a perda do painel de destino no
login social não unificado (o contexto é consumido antes de `urlDoPainel()`; a página guardada corrige isso no desbloqueio).

### Confronto código × afirmação (step 5)

| Pergunta | O `01` dizia | O código faz | Resposta (quem, data) | Onde a wiki mudou |
|---|---|---|---|---|

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| bind troca o middleware do pacote | o pipeline resolve cada middleware pelo container (`vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:make:208`) | nenhuma |
| `mount()` não é chamável por `$wire` | o Livewire recusa chamada direta a gancho de ciclo de vida (`DirectlyCallingLifecycleHooksNotAllowedException`) | nenhuma |
| citações do `01` com o método que envolve a linha | o `citacoes.sh` exige o símbolo **da linha** | 12 citações reescritas; `citacoes.sh` exit 0 |
| `url()->previous()` do pacote pode ser nulo e cair no `??` | nunca é nulo: cai em `/` (`UrlGenerator::previous()`) | registrado no `## Contexto` do `01` |

### Varredura da classe irmã (step 5)

Irmã: `App\Http\Middleware\ExigirEmailVerificado` (outro middleware do kit que roda em painel). `grep -rnF` em `app config database tests bootstrap`: registrado em `AppPanelProvider` e citado em testes — a classe nova **não** entra nessas listas, porque não é registrada em painel: substitui a do pacote por bind no container. Entrega: `app/Http/Middleware` e `app/Providers` estão em `KitUpdate::CAMINHOS_DO_KIT`, então classe e bind viajam pelas duas rotas (ao contrário do registro em `bootstrap/app.php`, débito declarado em `tests/Kit/DuasRotasDeEntregaTest.php`). Nenhuma ocorrência além das previstas.

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | yagni: `! $request->prefetch()` — painéis sem SPA, e o `Redirector::guest()` do Laravel não confere | sim | `01`, passo 1; `02`, Superfície Livewire |
| 2 | delete: `$request->route()` — o middleware só roda dentro de rota de painel | sim | `01`, passo 1 |

Ponytail via skill `ponytail-review` (namespace `ponytail:` não instalado neste projeto; a skill local é a mesma). net: -2 linhas.

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| 2 | 7 | `general-purpose` — derivação do `04` pela `feature-test-design` (3 rodadas: derivação, P-06/Q?2, fechamento dos ADV) | sonnet | conversa, corpo dos passos do `01` (o agente declarou ter visto a lógica do passo 1 ao imprimir o `01` — higiene tocada, registrada no cabeçalho do `04`) | 31 CT, 58 mutantes, 0 sem matador, `## Sem CT-B` | 181k + 190k + 237k tokens · 6,0 + 0,7 + 3,4 min | totais por `grep -c` conferidos pelo agente; costuras confirmadas pela sessão; amostra CT-01/CT-03/CT-21 lida |
| 3 | 7 | `fw-adversario-ct` — revisão adversarial | opus (agente) | `01`, `02`, `03`, código | 24 achados; 21 aceitos, 3 rejeitados (ADV-07, ADV-08, ADV-14) | 67k tokens · 4,2 min | achados conferidos contra o `04`; rejeições com prova (`grep -c '->spa('` = 0) |
| 1 | 3 | `codex exec --yolo` — análise do vendor, somente leitura | não exposto pelo host | a conclusão da sessão | tabela cenário × destino, 2 achados novos (P-05, contexto social) | — | 3 afirmações conferidas com `grep -n` no vendor (`vendor/marjose123/filament-lockscreen/src/Http/Middleware/Locker.php:setIntendedUrl:32`, `vendor/marjose123/filament-lockscreen/src/Http/Livewire/LockerScreen.php:lockscreen:121`, a guarda `has('lockscreen')` do `mount()` da `TelaBloqueio` em `main`); `git status --porcelain` limpo antes e depois |

## Blockers

## Desvios do Plano

- Passo 3: além de honrar a página para a mesma conta, descarta `url.intended` quando outra conta volta numa sessão travada — no login unificado `urlDoPainel()` consome a chave de qualquer um, e sem o descarte a P-06 não valeria. Fonte corrigida: `01`, passo 3 *(alterado em 2026-10-20: P-06)*

## Notas de Implementação

- Baseline em `main` (2026-10-20): Kit 4341/4345 — 1 falha pré-existente: `SiteDeDocumentacaoTest` › *mantém os números objetivos dos READMEs sincronizados com a árvore*; 3 pulados — Tenancy 634/634 (`php artisan test --testsuite=Kit --parallel --compact`; idem Tenancy)
- Suíte existente com a implementação, antes dos CT novos: 408/408 (`php artisan test --compact tests/Kit/BloqueioDeSessaoTest.php tests/Kit/LoginSocialGoogleTest.php tests/Kit/LoginSocialPorPainelTest.php tests/Kit/LoginSocialProvedoresTest.php tests/Kit/LoginUnificadoTest.php`); PHPStan 0 erros nos 4 arquivos
- O erro do provedor (`?error=…`) sai por `recusar()` antes de qualquer código da feature — não destrava nem gasta a página guardada

## Referências Abertas

- `template-00-requisito.md` — step 4 — 2026-10-20
- `template-01-plano.md` — step 4 — 2026-10-20
- `template-03-progresso.md` — step 4 — 2026-10-20

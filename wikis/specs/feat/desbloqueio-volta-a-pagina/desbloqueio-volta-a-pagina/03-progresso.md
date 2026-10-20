# Progresso — Desbloqueio da sessão volta para a página em que a pessoa estava

**Estado**: em revisão

**Base do PR**: `main`

## Processo do pedido

- [x] Analisar se o pacote base tem a funcionalidade nativa — parcial: só o bloqueio manual guarda a página; `01`, `## Contexto`, 2026-10-20
- [x] Usar o `codex --yolo` como auxiliar — análise independente do vendor, despacho 1, 2026-10-20
- [x] Usar o Blueprint no processo — relatório de conformidade `reviewing-filament-plans` por sub-agente cego (62 Conforms, 2 Mismatched — CT-24/CT-25, fortalecidos —, 3 Not verified — RD-03/RD-04, fechados depois), `bp:off` com o lock restaurado ao commitado; `filament/blueprint` ligado por `bp:on` (sem estado commitado), `planning/overview.md` + `checklist.md` aplicados em `01`, `## Consistência do Ciclo de Vida (Blueprint)`, 2026-10-20
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

- [x] `/ponytail:ponytail-review` no diff (validar contra over-engineering) — passe no plano (step 6, -2 linhas) e CR-08 no diff (`isMethod('GET')` redundante, cortado), 2026-10-20
- [x] `vendor/bin/pint --dirty` — `{"tool":"pint","result":"passed"}`, 2026-10-20
- [x] `vendor/bin/filacheck --fix` — All 17 rules passed, 2026-10-20
- [x] `composer types:check` — `vendor/bin/phpstan analyse` nos 4 arquivos de `app/`: `{"result":"passed","errors":0}`, 2026-10-20
- [x] Testes da feature verdes — `php artisan test --compact tests/Kit/BloqueioDeSessaoTest.php` 75/75 (374 asserções); `tests/Tenancy/BloqueioComRetornoTenancyTest.php` 5/5, 2026-10-20
- [x] Suítes Kit e Tenancy sem regressão além da baseline de `main` — `php artisan test --testsuite=Kit --parallel --compact` sobre `aaca0153`: 4385/4395, 3 pulados, 7 vermelhos explicados: 3 `BlueprintForaDoPacoteTest` (o `bp:on` temporário, desligado depois: `composer validate` ok, `install --dry-run` "Nothing to install"), 1 `CitacoesDeCodigoTest` (citações de `tests/Pest.php` deslocadas, corrigidas: 3/3) e 3 `SiteDeDocumentacaoTest` (números dos READMEs, sincronizados: 118/118 com citações e diagramas) — o da baseline incluído; Tenancy 639/639, 2026-10-20
- [x] `pest --mutate` na classe nova: score, duração e sobreviventes listados — no Windows o plugin não certifica (`.ai/rules/testes.md`); amostragem manual de 8 mutantes (`scratchpad/mutantes_manuais.py`: aplica, roda o CT previsto, `git checkout`): 8/8 mortos depois de endurecer o CT-27 (M-a sobrevivia — GET JSON na mesma URL da página guardada, mutante equivalente no cenário; corrigido no `04` e no teste: 3/3 vermelhos com o mutante); `app/` limpo depois, 2026-10-20
- [x] **`/code-review high main...HEAD` + passe de eixos (step 9)**, antes da reconciliação — 8 CR + 4 RD + 1 CX; 9 roteados (premissa/teste → CT → correção), 4 rejeitados com motivo; re-revisão única fechou RD-01/RD-02 e trouxe RD-03/RD-04 (Minor), fechados; ver `## Revisão do Diff (step 9)`, 2026-10-20
- [x] Desvios propagados ao `01`/`02`/`04` de origem, marcados `*(alterado em …)*` — `grep -c 'alterado em 2026-10-20'` nos três > 0; ver `## Desvios do Plano`, 2026-10-20
- [x] `rastreabilidade.sh {wiki}` silencioso — exit 0, 2026-10-20
- [x] `checkbox-sem-evidencia.sh {wiki}` silencioso — exit 0, 2026-10-20
- [x] Citações reverificadas: `citacoes.sh {wiki}` silencioso — exit 0, 2026-10-20
- [x] `ids-ct.sh {wiki}` silencioso — `ids-ct.sh {wiki} 'tests/{Kit/BloqueioDeSessaoTest.php,Tenancy/BloqueioComRetornoTenancyTest.php}'` exit 0 (CT-26 `@obsoleto`), 2026-10-20
- [x] `conformidade-rules.sh {wiki} main` silencioso — exit 0, nenhuma rule violada, 2026-10-20
- [x] Falsificabilidade em cópia temporária, revertendo só o patch do fix — mais forte que a cópia: os 8 CT do step 9 (CT-27 ×2, CT-11, CT-32 unificado, CT-33, CT-35 ×2, CT-34) foram escritos e rodados ANTES da correção e falharam pela causa (b), com a saída literal no retorno do `fw-executor-ct`; 8 de 8 falham sem o fix. O CT-11 da 1ª rodada também nasceu vermelho (`'http://localhost:8000' starts with "…/admin"`) antes do fallback no `authenticate()`, 2026-10-20
- [x] Docs pt/en, CHANGELOG e README reconciliados com o comportamento final — `docs/{pt,en}/autenticacao/index.md` (parágrafo do desbloqueio), `CHANGELOG.md` `[Unreleased] › Corrigido`, READMEs (números objetivos), 2026-10-20
- [ ] `git commit`

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|
| RD-01 | eixos (`fw-revisor-diff`) | sessão já travada + GET JSON com `Referer`: o ramo do pacote sobrescreve `url.intended` com `previous()` antes do filtro de JSON; o CT-27 passava por coincidência | teste (viola P-04) → CT → correção | — / CT-27 endurecido | — |
| CX-01 | Codex (`codex exec --yolo`, cego) | o mesmo de RD-01, achado de forma independente | idem RD-01 | — / CT-27 | — |
| RD-02 | eixos | `confirmarVinculo()` faz `Auth::login()` numa sessão travada sem descartar a página de outra conta; no não unificado a página fica velha na sessão | premissa → CT → correção | P-07 / (CT do step 9) | — |
| CR-01 | `/code-review` | `composer.json`/`lock` com `filament/blueprint` e o repositório privado | processo — estado temporário do `bp:on`, nunca staged; `bp:off` antes do PR | — | não é defeito do diff commitado (o `git diff main...HEAD` não toca `composer.*`) |
| CR-02 | `/code-review` | fallback no `/app` usa `Panel::getUrl()` → primeira organização da conta, não a da sessão | premissa → CT → correção | P-08 / (CT do step 9) | — |
| CR-03 | `/code-review` | o mesmo de RD-02 | idem RD-02 | P-07 | — |
| CR-04 | `/code-review` | duplo envio: o segundo acha a página consumida e vai à raiz do painel, não à página | teste (viola P-02/RQ-02) → CT → correção | — / CT-11 endurecido | — |
| CR-05 | `/code-review` | o fallback grava a raiz antes do rate limit; com `forceLogout: true` (kit) a página sobra para o próximo login | premissa → CT → correção | P-09 / (CT do step 9) | — |
| CR-06 | `/code-review` | GET que não é página (imagem, download) vira destino | — | — | P-04 reescrita no step 7 (ADV-08, concordância do adversário): a régua é a do *intended* do Laravel |
| CR-07 | `/code-review` | login social não unificado sem trava deixa `url.intended` velho, que a tela aberta direto depois reaproveita | — | — | semântica preexistente do login comum (Fora de Escopo do `00`); o `redirect()->to()` de hoje já deixa a chave — débito declarado |
| RD-03 | eixos (re-revisão) | o destino lido no `mount()` sobrescrevia uma página mais nova guardada por outra aba; senha × provedor assimétricos | teste (viola P-03) → CT → correção | — / CT-36 | — |
| RD-04 | eixos (re-revisão) | fallback pela organização da sessão sem conferir acesso: pessoa removida da organização ia a um 404 | premissa refinada → CT → correção | P-08 / CT-37 | — |
| CR-08 | `/code-review` | `isMethod('GET')` redundante: os dois ramos que não chamam `$next` já exigem GET | simplificação | — | aceito, sem CT (sem mudança de comportamento) |

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `app.md` — painel corrente por `Paineis::correnteOuPadrao()`, URL nula por `Paineis::url()` | `app/**` | aplicada | `TelaBloqueio::raizDoPainel()` usa `Paineis::correnteOuPadrao()` e cai em `Paineis::url($panel)` quando `getUrl()` é nulo; o redirect nunca recebe `null` |
| `app.md` — papel por `ContextoDePapeis`, DTO em `app/Data`, URL de arquivo por `asset()` | `app/**` | n.a. | o diff não atribui papel, não cria DTO nem gera URL de disco |
| `auth.md` — `$layout` redeclarado; cobertura em par de `fi-auth-layout` | `app/Filament/Pages/Auth/**` | aplicada | `$layout` intacto; os dois casos do par seguem em `tests/Kit/BloqueioDeSessaoTest.php` ('renderiza a tela de bloqueio com o layout do login…' e 'não vaza o layout de login…'), verdes |
| `auth.md` — redirect em `mount()` por `HttpResponseException`, sem `routeIs()` | `app/Filament/Pages/Auth/**` | aplicada | as guardas do `mount()` saem por `sairPara()`; o fallback novo só grava sessão, não redireciona |
| `auth.md` — página fora do painel resolve o painel da conta antes de `canAccessPanel()` | `app/Filament/Pages/Auth/**` | n.a. | a tela de bloqueio é servida dentro do painel (`/{painel}/screen/lock`); `canAccessTenant()` é chamado no usuário autenticado |
| `filament.md` — permissões, Resource, Page/Widget novos, cartões, Blueprint v5, alias de trait `as protected` | `app/Filament/**` | n.a. / aplicada | nenhum Resource, Page, Widget ou Action novo; `filacheck --fix` 17/17; o alias `configBaseDoAuthDesigner` preexistente não mudou |
| `providers.md` — rota do kit no `KitServiceProvider` | `app/Providers/**` | n.a. | nenhuma rota nova; só um `bind()` no `AppServiceProvider`, que viaja pelo `kit:update` (`app/Providers` em `KitUpdate::CAMINHOS_DO_KIT`) |
| `css-filament.md` — CSS do kit para blade de vendor | `app/Providers/**` | n.a. | nenhuma classe CSS nem blade nova |
| `specs.md` — vendor citado com `file:line` lido; citação por símbolo; CT citado pelo ID | `wikis/specs/**` | aplicada | toda afirmação de vendor do `01` cita `arquivo:símbolo:linha`; `citacoes.sh` exit 0 |
| `testes.md` — helper de mais de um arquivo em `tests/Pest.php` | `tests/**` | aplicada | `travadaPorInatividadeAoPedir`, `desbloquearPorSenha`, `destinoDoComponente` e `enderecoDe` em `tests/Pest.php` |
| `testes.md` — `/app` boota com GET real antes do primeiro `Livewire::test()`; papéis por suíte | `tests/**` | aplicada | os CT de Tenancy fazem `get('/app/{org}/users')` antes do componente; persona `admin_app` (o `panel_user` toma 403) |
| `testes.md` — `--mutate` no Windows não mede | `tests/**` | aplicada | mutação certificada por amostragem manual (`scratchpad/mutantes_manuais.py`), não pelo plugin |

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

- Passo 1: restauração de `url.intended` no GET JSON e corte do `isMethod('GET')` (RD-01/CX-01, CR-08). Fonte corrigida: `01`, passo 1 *(alterado em 2026-10-20)*
- Passo 2: destino em propriedade `#[Locked]`, reposto a cada envio; `forget` após logout forçado; fallback pela organização da sessão (CR-02, CR-04, CR-05, CT-11). Fonte corrigida: `01`, passo 2; `02`, Superfície Livewire *(alterado em 2026-10-20)*
- Passo 3: `confirmarVinculo()` descarta a página numa sessão travada (CR-03/RD-02). Fonte corrigida: `01`, passo 3; `00`, Fora de Escopo *(alterado em 2026-10-20)*
- Passo 2 (re-revisão): a chave da sessão vence a propriedade (RD-03); a raiz gravada pelo `mount()` é marcada na sessão (`lockscreen_raiz_gravada`) e recalculada no envio, com `canAccessTenant` (RD-04). CT-36 e CT-37 nasceram vermelhos (`'…/admin/users'` ≠ `'…/admin/shield/roles'`; `'…/app/globex' not to start with '…/app/globex'`). Fonte corrigida: `01`, passo 2 *(alterado em 2026-10-20)*
- CT-26 `@obsoleto`: nenhuma rota POST do painel atravessa a barreira (`php artisan route:list --path=admin --method=POST` → `lock-session`, `logout`, `configuracoes-do-kit` só com `web`). Fonte: `04` *(alterado em 2026-10-20)*

- Passo 3: além de honrar a página para a mesma conta, descarta `url.intended` quando outra conta volta numa sessão travada — no login unificado `urlDoPainel()` consome a chave de qualquer um, e sem o descarte a P-06 não valeria. Fonte corrigida: `01`, passo 3 *(alterado em 2026-10-20: P-06)*

## Notas de Implementação

- A porta 8000 desta máquina serve outra aplicação (redireciona a `/projetos`): o quality gate usa o kit servido em `http://127.0.0.1:8123` (`php artisan serve`)

- Baseline em `main` (2026-10-20): Kit 4341/4345 — 1 falha pré-existente: `SiteDeDocumentacaoTest` › *mantém os números objetivos dos READMEs sincronizados com a árvore*; 3 pulados — Tenancy 634/634 (`php artisan test --testsuite=Kit --parallel --compact`; idem Tenancy)
- Suíte existente com a implementação, antes dos CT novos: 408/408 (`php artisan test --compact tests/Kit/BloqueioDeSessaoTest.php tests/Kit/LoginSocialGoogleTest.php tests/Kit/LoginSocialPorPainelTest.php tests/Kit/LoginSocialProvedoresTest.php tests/Kit/LoginUnificadoTest.php`); PHPStan 0 erros nos 4 arquivos
- O erro do provedor (`?error=…`) sai por `recusar()` antes de qualquer código da feature — não destrava nem gasta a página guardada

## Referências Abertas

- `template-00-requisito.md` — step 4 — 2026-10-20
- `template-01-plano.md` — step 4 — 2026-10-20
- `template-03-progresso.md` — step 4 — 2026-10-20

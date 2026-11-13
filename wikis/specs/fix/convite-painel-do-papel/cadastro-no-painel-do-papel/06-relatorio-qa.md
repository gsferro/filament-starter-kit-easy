# Relatório de QA — #191: cadastro por convite cai no painel do papel

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (domínio sensível: autorização de painel, sessão, URL pretendida)
> Natureza da wiki: correção · Toca infra compartilhada: sim → `RespostaDeCadastro` (convite e registro aberto) e `DestinoAposLogin::painelDe()` (login unificado e social) · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 10 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · Major: 2 · Minor: 7 · Cosmético: 0
- Não verificadas: E, K — causas em *Não Verificado*
- `RQ` abertas: nenhuma
- Ambiente: app em `http://localhost:8000` (sqlite, `KIT_TENANCY=false`, `ConfiguracaoDoLogin::unificado()` = false) · Pest 5.0.5 · MCP: Boost falhou ao conectar; Playwright indisponível

O fluxo da issue foi reproduzido de ponta a ponta no app servido e passou:

1. Criei um convite `infra` para `qa-gate-infra@example.test`.
2. Fiz o cadastro pelo `/app/register?token=…` (via `livewire/update`) e o redirect foi para `http://localhost:8000/infra`.
3. Um GET na mesma sessão devolveu 200 em `/infra` e 403 em `/app`.

A reprovação vem do escopo e da conformidade, não do caminho principal.

## Achados

### QA-01 — P-04 estende a correção ao login (modo ligado), que o `00` declara fora de escopo · Major · destino 1
- **Dimensão**: A / L3 / J
- **Relacionado a**: P-04, `## Fora de Escopo` do `00`, `## Impacto em Features Existentes` e passos 1–2 do `01`
- **Esperado**: o `00` diz "Fora de Escopo: O modo ligado (`kit.login.unificado = true`)". Quando uma `P-nn` contradiz ou estende o pedido, ela leva pergunta ao solicitante. O `01` diz "Impacto: Só o ramo desligado de `RespostaDeCadastro`".
- **Observado**: o commit d90f380c passou o `$user` para `painelDe()` e criou `organizacaoDaUrlAcessivel()`. O `urlPara()` (login unificado e login social) chama `painelDe()` e agora descarta a pretendida de organização sem vínculo. P-04 declara "Vale para o login e o cadastro", e o CHANGELOG também. Não há pergunta registrada em `## Perguntas ao Solicitante` (diz "nenhuma"). Nenhum passo do `01` cita esse código, e `## Impacto` continua dizendo que só o cadastro desligado muda.
- **Repro**: `git diff main...HEAD -- app/Support/DestinoAposLogin.php`, linha `$painelDaPretendida = … self::painelDe($pretendida, $paineis, $user)` em `urlPara()` (`app/Support/DestinoAposLogin.php:96`). Comparar com `00-requisito.md` `## Fora de Escopo` e com `01-plano-acao.md` `## Impacto em Features Existentes`.
- **Evidência**: `rastreabilidade.sh` → `00-requisito.md:84: P-04 sem CT`. `grep -rn "app/outra" tests/` só acha os dois arquivos novos de cadastro.
- **Destino**: 1 especificação
- **Ação exigida**:
  - Registrar a pergunta ao solicitante sobre o efeito no login unificado/social, ou restringir P-04 ao cadastro.
  - Atualizar `## Fora de Escopo` do `00` (só pela pergunta), `## Impacto em Features Existentes` e um passo do `01` que cite `organizacaoDaUrlAcessivel()`.
  - Depois, CT do lado do login (ver QA-03).

### QA-02 — Helpers de teste clonados com outro nome para fugir da colisão · Major · destino 2
- **Dimensão**: L4 (`.ai/rules/testes.md`, "Helper de teste usado por mais de um arquivo vive em `tests/Pest.php`")
- **Esperado**: "Nunca crie um clone com outro nome para escapar da colisão de redeclaração […] Mova para `tests/Pest.php` e use uma só."
- **Observado**: há pares idênticos nos dois arquivos novos:
  - `acessoDoPapelNaMesmaSessao` × `acessoNaMesmaSessaoTenancy`
  - `esperarAcessoLivreNoCadastro` × `esperarAcessoLivreTenancy`
  - `cadastroDoPapelPorConvite` × `cadastroDoPapelPorConviteTenancy`, ambos clones de `cadastrarPorConvite` (`tests/Kit/DestinoAposCadastroTest.php:27`)

  O docblock admite o motivo: "funcao global colide entre arquivos".
- **Repro**: `grep -n "^function " tests/Kit/CadastroNoPainelDoPapelTest.php tests/Tenancy/CadastroNoPainelDoPapelTenancyTest.php tests/Kit/DestinoAposCadastroTest.php`
- **Evidência**: linhas 35–68 (`tests/Kit/CadastroNoPainelDoPapelTest.php`) e 27–55 (`tests/Tenancy/CadastroNoPainelDoPapelTenancyTest.php`), com o mesmo corpo. O `HelpersDeTesteTest` passa porque só detecta uso cruzado, não clone.
- **Destino**: 2 implementação
- **Ação exigida**: mover os helpers para `tests/Pest.php` com um nome só, e reaproveitar `cadastrarPorConvite`.

### QA-03 — Lado do login de P-04 sem CT · Minor · destino 3
- **Dimensão**: A / K2
- **Relacionado a**: P-04, `urlPara()`
- **Esperado**: toda `P-nn` vigente tem CT.
- **Observado**: CT-15 e CT-16 só exercitam o cadastro desligado. Na mutação de `DestinoAposLogin` com os testes da feature, 5 mutantes sobrevivem na linha 96 (o ramo da pretendida em `urlPara`): `487df474ce002fca`, `71f45e35db7e2c4a`, `8efc1bdfd6464295`, `d6eb99e3015a34db`, `b72c3267822af075`. O predicado é o mesmo `painelDe()`, já coberto pelo cadastro, e por isso a severidade é Minor.
- **Repro**: `XDEBUG_MODE=coverage cmd //c "$(cygpath -w .claude/skills/feature-wiki/scripts/pestw.cmd)" tests/Kit/CadastroNoPainelDoPapelTest.php tests/Tenancy/CadastroNoPainelDoPapelTenancyTest.php --mutate --path=app/Support/DestinoAposLogin.php --covered-only --no-cache`
- **Destino**: 3 teste
- **Ação exigida**: depois do QA-01, `feature-test-design` com o mutante como entrada. O CT novo é "login unificado com pretendida `/app/outra` → destino sem a pretendida".

### QA-04 — CT-10 não prova o encerramento da sessão (só o da autenticação) · Minor · destino 3
- **Dimensão**: K2
- **Relacionado a**: RQ-05, P-01 ("com a sessão encerrada"), CT-10, `app/Http/Responses/RespostaDeCadastro.php:87-88`
- **Observado**: removendo `$request->session()->invalidate()` (mutante `517097c4c0f5dc56`) ou `regenerateToken()` (`c5c866c60012e650`), a suíte continua verde. O CT-10 só afirma `Filament::auth()->check() === false`.
- **Repro**: o mesmo comando de mutação, com `--path=app/Http/Responses/RespostaDeCadastro.php` (26 mutantes, 7 sobreviventes, 123 s).
- **Destino**: 3 teste
- **Ação exigida**: `feature-test-design` com estes dois mutantes. O CT-10 passa a afirmar que o id da sessão e o token CSRF mudaram.

### QA-05 — Zero painéis encerra a sessão sem dizer por quê · Minor · destino 1
- **Dimensão**: F
- **Esperado**: a mensagem diz o que aconteceu e como resolver. A irmã do modo ligado, `EscolhaDePainel::encerrarSemPainel()` (`app/Filament/Pages/Auth/EscolhaDePainel.php:120-125`), envia "Sua conta não tem acesso a nenhum painel. Procure quem administra o sistema."
- **Observado**: o ramo novo (`RespostaDeCadastro.php:81-90`) só faz logout e redirect para o login, sem notificação. Quem acabou de criar a conta cai no login em silêncio.
- **Repro**: `grep -n "Notification" app/Http/Responses/RespostaDeCadastro.php` não devolve nada. Na tela, é o cenário CT-10.
- **Destino**: 1 (decisão de desenho no `01`: notificar ou não)
- **Ação exigida**: decidir no `01`. Se for notificar: `P-nn` → CT → correção.

### QA-06 — `03` não reconciliado (step 9 e 10 ausentes) · Minor · destino 1
- **Dimensão**: L4 / L6
- **Observado**:
  - `**Estado**: em implementação`.
  - Os passos 4 e 5 seguem `[ ]`, embora entregues em d90f380c.
  - `## Testes` está "(a preencher)".
  - `## Revisão do Diff (step 9)`, `## Conformidade com Rules` e `## Verificação Final` estão "(pendente)", mas P-04 e os adendos CT-15/CT-16 do `04` citam achados do step 9 (RD-01, revisão Codex) que não estão registrados em lugar nenhum.
  - `## Despachos` #2 e #3 seguem "(em curso)".
- **Repro**: `bash .claude/skills/feature-wiki/scripts/conformidade-rules.sh wikis/specs/fix/convite-painel-do-papel/cadastro-no-painel-do-papel/ main` → exit 1, 8 rules sem linha (`app.md`, `commands.md`, `config.md`, `kit.md`, `onboarding.md`, `specs.md`, `support.md`, `testes.md`).
- **Destino**: 1
- **Ação exigida**: rodar o step 10. Isso inclui a tabela de conformidade, com `testes.md` = violada (QA-02), e os achados do step 9 com ID e destino.

### QA-07 — `04` inconsistente com o estado atual · Minor · destino 1
- **Dimensão**: L1 / L6
- **Observado**:
  1. As linhas CT-15 e CT-16 foram inseridas dentro da `## Tabela de Decisão`, com outro formato de colunas. Elas não estão no `## Índice de Cenários`.
  2. O `## Mapa de Regras` não tem P-03 nem P-04. O `rastreabilidade.sh` acusa as duas linhas `sem CT`, embora CT-12, CT-15 e CT-16 existam.
  3. O cabeçalho diz "Q1 permanece aberta", "nenhum teste foi executado" e "contagens… pendentes", tudo já superado.
  4. O cabeçalho e `## Revisão Adversarial` dizem "duas rodadas independentes… contexto novo". O `03` (`## Despachos`) diz "feita pelo próprio Codex na derivação (duas rodadas internas), não por `fw-adversario-ct`".
  5. O dataset `pt`/`en` do CT-13 não tem `Exemplos` no Gherkin.
- **Repro**: `bash .claude/skills/feature-wiki/scripts/rastreabilidade.sh wikis/specs/fix/convite-painel-do-papel/cadastro-no-painel-do-papel/` → exit 1. Ler as linhas 122–123 do `04`.
- **Destino**: 1
- **Ação exigida**: reconciliar o `04` pela `feature-test-design` e alinhar a alegação de revisão adversarial entre `04` e `03`.

### QA-08 — `01` fora do formato que o script confere · Minor · destino 1
- **Dimensão**: A / L3
- **Observado**: `rastreabilidade.sh` → "sem `## Cobertura do Requisito` no formato do template" e "sem `## Estrutura de Implementação` com passos `### N.`". O `01` usa `## Passos` e a coluna `RQ / P`. A checagem mecânica RQ × passo não rodou. Conferi o `git diff` por passo à mão: os passos 1, 2, 3 e 5 batem com o diff.
- **Destino**: 1
- **Ação exigida**: renomear as seções e colunas para o template da `feature-wiki`.

### QA-09 — Docs do login unificado não descrevem P-04 · Minor · destino 1
- **Dimensão**: L5
- **Observado**: o CHANGELOG 0.52.1 diz que o login unificado e o social passam a descartar pretendida de organização sem vínculo. `docs/{pt,en}/autenticacao/login-unificado.md` ainda dizem que "com a URL pretendida de um painel acessível, ela vence", sem a condição de organização. O pt e o en concordam entre si.
- **Repro**: `git diff main...HEAD -- docs/pt/autenticacao/login-unificado.md CHANGELOG.md`
- **Destino**: 1
- **Ação exigida**: depois do QA-01, acrescentar a condição nas docs pt/en.

## Matriz de Rastreabilidade

| RQ/P | Cláusula ou premissa | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|------|------|------|------|------|------|------|------|
| RQ-01…RQ-08 | (ver `00`) | 1–3 | CT-01…CT-14 | — | `RespostaDeCadastro`, `DestinoAposLogin::painelSemEscolha` | ✅ (40 testes verdes; RQ-01 reproduzido no app) | OK |
| P-01 | login com sessão encerrada | 2 | CT-10 (parcial) | — | `RespostaDeCadastro:86-88` | ⚠️ invalidate/regenerate sem oráculo | QA-04 |
| P-03 | pretendida acessível vence | 2 | CT-12 (fora do Mapa) | — | `RespostaDeCadastro:61` | ✅ | QA-07 (rastro) |
| P-04 | organização da pretendida | **—** | CT-15, CT-16, CT-04/11 tenancy (cadastro); **login: —** | — | `DestinoAposLogin::organizacaoDaUrlAcessivel` | ⚠️ código sem passo; login sem CT | QA-01, QA-03 |

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | QA-01, QA-03, QA-08. `rastreabilidade.sh` exit 1 (4 linhas: P-03/P-04 sem regra no Mapa, formato do `01`). Sem `07-tickets/` (não fatiado). `git diff` por passo conferido à mão |
| B | Fronteiras e dados | ✅ | Por leitura (sem `--agent` instalado). Pretendida `//`, `/\`, absoluta de outro host e path de outro painel continuam barrados em `painelDe()`. Slug desconhecido e rota inexistente falham fechado (`rescue` → `false`). CT-16 cobre a rota inexistente |
| C | Matriz de permissão | ✅ | Papéis × painéis × tenancy cobertos por CT-01…CT-11 (`infra`, `admin`, `panel_user`, 0 painéis, org sem vínculo). Sem ação destrutiva nova |
| D | Observabilidade | ✅ | Log real em `storage/logs/autenticacao-2026-11-13.log`: `[RespostaDeCadastro@toResponse] … painel: infra {"user_id":32,"corrente":"app","painel":"infra"}`. Canal certo, prefixo certo, contexto não vazio, sem PII. Ruído de `canAccessPanel` declarado nos Riscos do `01` |
| E | Performance | ⏭️ | Leitura apenas: custo constante por request (match de rota + 1 `getTenant` + `canAccessTenant`). Contagem de queries não medida (sem `--agent`) |
| F | UX de erro | ⚠️ | QA-05 |
| G | Tema e cor | n/a | `dark-mode.sh --mecanismo` exit 1 (Filament com dark mode), mas o `01` declara "Sem superfície de UI nova" e o diff não toca `resources/` (nenhuma view, CSS ou Blade) |
| H | Acessibilidade | n/a | Mesma prova da G: sem UI no `01` nem no diff |
| I | Segurança | ✅ | `## Revisão do Diff (step 9)` ausente ("pendente"), então os 4 eixos 9 rodaram. Sem Livewire novo (`02`). Discriminante nulo falha fechado (slug inválido/rota inexistente → `false`; rota sem `{tenant}` → aceita, conforme P-04). Zero painéis tem saída (login 200 no CT-10). Além do step 9: sem rota nova, sem mass assignment, sem upload, sem `DB::raw`. P-04 **fecha** um IDOR de destino (`/app/outra`) |
| J | Regressão | ✅ | Kit + Tenancy completos: 5583 testes, 5580 passed, 3 skipped, 0 falhas (o TIA não se aplicou a execução parcial, então a seleção rodou direto). Ancestral `DestinoAposCadastro{,Tenancy}Test`, login, convite e registro: 435 + 120 verdes. Previsto × medido diverge (QA-01) |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 0. K2 pelo `pestw.cmd`: `RespostaDeCadastro` 26 mutantes, 73,08 %, 123 s, 7 sobreviventes; `DestinoAposLogin` 137 mutantes, 67,15 %, 504 s, 45 sobreviventes. Linhas novas (`painelSemEscolha`, `organizacaoDaUrlAcessivel`, `painelDe`) todas mortas. Triagem dos sobreviventes: logs sem RQ → D; `urlPara` e `carimbarAcesso` são pré-existentes, exceto a linha 96 (QA-03); QA-04. Mortos não certificados (Windows, ver *Não Verificado*). Revisão adversarial não independente |
| L | Consistência documental | ⚠️ | QA-06, QA-07, QA-08, QA-09, e a L4 de QA-02. L1 `ids-ct.sh` exit 0 (datasets × Exemplos conferidos; desvio no CT-13). L2 `citacoes.sh` exit 0; citações das docs (`urlPara:88`, `:96`, `:100`, `:101`, `entrarEm:196`) conferidas. L4 `conformidade-rules.sh` exit 1 (8 rules). L6 `checkbox-sem-evidencia.sh` exit 0; "`phpstan analyse` 0 erros" reproduzido (`errors: 0`). L7 sem divergência com o glossário. L8 n/a — sem `08` e `Roteiro manual: não` no `03` |

## Débitos Aceitos

- nenhum (ciclo 1, ainda reprovado)

## Suspeitas Não Confirmadas

- Sobrevivente `0457c6bef694ffb6` (`RespostaDeCadastro.php:50`, remove o `return` do ramo ligado): os testes da feature só têm um caso no modo ligado (CT-14, painel único, onde os dois ramos coincidem). Provavelmente morre pelos CT-67 e seguintes da ancestral (`DestinoAposCadastroTest`), que não entraram na mutação. Não confirmado.
- `organizacaoDaUrlAcessivel()` usa `getRoutes()->match()`, que faz `bind()` na instância de `Route` compartilhada. Não encontrei caminho em que essa instância seja a rota do request corrente. Sem repro.

## Não Verificado

- E — motivo: contagem de queries não medida. O Pest 5.0.5 instalado não expõe `--agent` (prova: `vendor/bin/pest --help` sem a flag) e o Boost MCP (`database-query`) falhou ao conectar.
- K2 — motivo: prova indispensável pendente. No Windows os mutantes "mortos" só valem com conferência manual por amostragem (`.ai/rules/testes.md`), e este gate não pode alterar arquivo. Os sobreviventes são confiáveis; o score não.
- K — motivo: revisão adversarial do `04` não independente. O `03` declara "feita pelo próprio Codex na derivação… não por `fw-adversario-ct`", em perfil completo.
- Dinâmico com tenancy ligada (CT-04/06/07/11/15/16 no app) — motivo: o app local está com `KIT_TENANCY=false`. Coberto só pela suíte Tenancy.
- Playwright MCP — motivo: indisponível. Sem impacto: não há UI no escopo.

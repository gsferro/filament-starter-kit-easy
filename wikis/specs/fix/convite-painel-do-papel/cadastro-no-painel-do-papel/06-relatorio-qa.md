# Relatório de QA — #191: cadastro por convite cai no painel do papel

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (domínio sensível: autorização de painel, sessão, URL pretendida)
> Natureza da wiki: correção · Toca infra compartilhada: sim → `RespostaDeCadastro` (convite e registro aberto) e `DestinoAposLogin::painelDe()` · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 10 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 2

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 0 · Minor: 2 · Cosmético: 1
- Não verificadas: E, K. As causas estão em *Não Verificado*.
- `RQ` abertas: nenhuma. A Q2 está aberta, mas não está ligada a nenhuma `RQ` e nada a implementa: P-04 vale só para o cadastro, e `urlPara()` é idêntico ao da `main`.
- Ambiente: app em `http://localhost:8000` (sqlite, `KIT_TENANCY=false`, unificado = false) · Pest 5.0.5 · MCP: o Boost falhou ao conectar e o Playwright está indisponível.

O fluxo da issue foi reproduzido de novo no HEAD 9acafae5:

1. Criei um convite `infra` para `qa-gate-c2-infra@example.test`.
2. Fiz o cadastro pelo `/app/register?token=…` (`livewire/update`, `register`).
3. O redirect foi para `http://localhost:8000/infra`.
4. Na mesma sessão, `/infra` devolveu 200; `/app` e `/admin` devolveram 403.
5. O log `autenticacao` registrou `[RespostaDeCadastro@toResponse] … painel: infra {"user_id":33,"corrente":"app","painel":"infra"}`.

### Situação dos achados do ciclo 1

| ID | Estado | Prova |
|---|---|---|
| QA-01 | fechado | Q2 registrada no `00`, P-04 restrita ao cadastro, passo 3 e `## Impacto` do `01` alinhados; `git diff main...HEAD -- app/Support/DestinoAposLogin.php` não toca mais `urlPara()` |
| QA-02 | fechado | `cadastrarPorConvite`, `acessoNaMesmaSessao` e `esperarAcessoLivre` estão em `tests/Pest.php`; resíduo cosmético em QA-12 |
| QA-03 | fechado (destino 5) | O código-alvo saiu: `urlPara()` é igual ao da `main`. Os sobreviventes das linhas 91–125 de `DestinoAposLogin` (`urlPara`) são de código anterior à feature |
| QA-04 | **parcial** — ver QA-04 abaixo | o mutante de `invalidate()` agora morre; o de `regenerateToken()` sobrevive |
| QA-05 | fechado | P-05, notificação em `app/Http/Responses/RespostaDeCadastro.php:Notification::make:92`; a CT-10 afirma título, corpo e status; o mutante da linha 92 morre |
| QA-06 | fechado | o `03` foi reconciliado; `conformidade-rules.sh` exit 0 |
| QA-07 | fechado (resíduo em QA-10) | Índice, Mapa (R11–R13), cabeçalho e revisão adversarial alinhados com o `03` |
| QA-08 | fechado | `rastreabilidade.sh` exit 0 |
| QA-09 | fechado | o CHANGELOG e o roteiro dizem "o login unificado e o social não mudam" |

## Achados

### QA-04 — CT-10 ainda não distingue token regenerado de token ausente · Minor · destino 3
- **Dimensão**: K2
- **Relacionado a**: RQ-05, P-01, CT-10, mutante M-44 do adendo do `04`
- **Esperado**: o adendo do `04` diz que M-44 ("Não regenera o token CSRF") morre pela CT-10 com a asserção "token diferente".
- **Observado**: o mutante `77cde9e9d7d2e8c9` (RemoveMethodCall em `app/Http/Responses/RespostaDeCadastro.php:regenerateToken:89`) **sobrevive**. A causa:
  - `invalidate()` chama `flush()` e apaga o `_token` (`vendor/laravel/framework/src/Illuminate/Session/Store.php:invalidate:605`, `:flush:595`). Sem o `regenerateToken()`, `token()` passa a devolver `null`.
  - A asserção `->not->toBe($tokenAntes)` (`tests/Kit/CadastroNoPainelDoPapelTest.php:tokenAntes:226`) passa com `null`. É o padrão K1 "`->not->toBe($outro)` sem valor esperado".
  - O `03` marca `[x] … CT-10 verde (sessão, token e notificação afirmados)`.
- **Repro**: `XDEBUG_MODE=coverage cmd //c "$(cygpath -w .claude/skills/feature-wiki/scripts/pestw.cmd)" tests/Kit/CadastroNoPainelDoPapelTest.php tests/Tenancy/CadastroNoPainelDoPapelTenancyTest.php --mutate --path=app/Http/Responses/RespostaDeCadastro.php --covered-only --no-cache`
- **Evidência**: 27 mutantes, 6 sobreviventes, 77,78 %, `Duration` 237 s (cerca de 8,8 s por mutante, compatível com 40 testes). As linhas 87 (`logout`) e 88 (`invalidate`) morrem; a 89 sobrevive.
- **Destino**: 3 teste
- **Ação exigida**: passar M-44 pela `feature-test-design`. Uma saída é a CT-10 afirmar que o token novo é string não vazia e diferente do anterior. A outra é provar no `04` a equivalência no domínio (`Store::start:98` regenera o token na requisição seguinte) e fechar M-44 como destino 5. Nos dois casos, corrigir a alegação do M-44 no `04`.

### QA-10 — Tabela de Decisão do `04` ainda dá Q1 como aberta · Minor · destino 1
- **Dimensão**: L1 / L3
- **Relacionado a**: P-03, R11, CT-12, Q1 do `04`
- **Esperado**: P-03 responde a Q1 ("a pretendida vence"), e o cabeçalho e o Mapa já dizem isso.
- **Observado**: duas linhas da `## Tabela de Decisão` do `04` não foram atualizadas:
  - linha 119 do arquivo: "Preservação da URL completa aberta | CT-12 / Q1";
  - linha 123: `Não | >1 | Acessível | Qualquer | Precedência exata aberta | Q1`.

  O texto da Q1 (linha 577: "Até a decisão, CT-12 afirma apenas a invariante") e o fora de escopo (linha 592) também ficaram para trás. O caso "mais de um painel acessível, pretendida acessível" não tem cenário nesta wiki. O código trata a pretendida antes da contagem, então o risco é baixo.
- **Repro**: `grep -n "Q1" wikis/specs/fix/convite-painel-do-papel/cadastro-no-painel-do-papel/04-casos-de-teste.md`
- **Destino**: 1
- **Ação exigida**:
  - Reescrever as duas linhas com o resultado de P-03 (pretendida completa) e a cobertura: CT-12 para um painel; para mais de um, CT-75 da ancestral ou um exemplo novo pela `feature-test-design`.
  - Marcar a Q1 como respondida também nas linhas 577 e 592.

### QA-12 — Docblocks dos testes ainda justificam helper prefixado pela colisão · Cosmético · destino 2
- **Dimensão**: L4 (`.ai/rules/testes.md`)
- **Observado**:
  - `tests/Kit/CadastroNoPainelDoPapelTest.php:colide:22` ("funcao global colide entre arquivos") e `tests/Tenancy/CadastroNoPainelDoPapelTenancyTest.php:colide:17` repetem o motivo que a rule proíbe.
  - `respostaDeCadastroTenancy` repete o corpo de `paineisNoCadastro` + `respostaDeCadastroNoCadastro`. A assinatura é diferente e cada um é usado num arquivo só, então não é o "clone idêntico" da rule.
- **Repro**: `grep -n "colide" tests/Kit/CadastroNoPainelDoPapelTest.php tests/Tenancy/CadastroNoPainelDoPapelTenancyTest.php`
- **Destino**: 2
- **Ação exigida**: tirar a justificativa dos docblocks; unificar o helper de resposta em `tests/Pest.php` é opcional.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | `rastreabilidade.sh` exit 0. Sem `07-tickets/` (não fatiado). `git diff` por passo: o passo 2 tem a notificação de P-05; no passo 3, `painelDe(…, ?User $conferirOrganizacaoDe = null)` só recebe a conta em `descartarPretendidaInacessivel`. RQ-01 reproduzido no app |
| B | Fronteiras e dados | ✅ | Sem mudança de fronteira no ciclo 2. O parâmetro opcional de `painelDe` com `null` mantém o comportamento da `main` no login, e com a conta falha fechado (mutantes das linhas 281–314 mortos). Verificação por leitura (sem `--agent`) |
| C | Matriz de permissão | ✅ | Sem ação nova; papéis × painéis cobertos pela CT-01…CT-16; `/app` e `/admin` dão 403 para a conta `infra` no app |
| D | Observabilidade | ✅ | Log real (user 33) no canal `autenticacao`, com prefixo e contexto, sem PII. Os sobreviventes do log `warning` (`RespostaDeCadastro` linhas 82 e 84) são log sem RQ de auditoria e foram julgados aqui; não geram CT |
| E | Performance | ⏭️ | Contagem de queries não medida (ver *Não Verificado*) |
| F | UX de erro | ✅ | P-05: mesma mensagem da `EscolhaDePainel`, `warning` e `persistent`; a CT-10 afirma título, corpo e status. Não reproduzido no app (ver *Não Verificado*) |
| G | Tema e cor | n/a | O diff não toca `resources/` nem view; o `01` declara "Sem superfície de UI nova". A notificação é componente existente do Filament |
| H | Acessibilidade | n/a | Mesma prova da G |
| I | Segurança | ✅ | Coberto pelo step 9, `## Revisão do Diff (step 9)` do `03`: 5 achados, 1 rejeitado (RD-03, que virou Q2). Além do step 9: a re-revisão das correções (o `03` delega ao gate) não tem rota, Livewire ou propriedade pública novos. A notificação é gravada depois do `invalidate()`, na sessão nova. Discriminante nulo: `null` = sem conferência (login, igual à `main`), conta = falha fechado |
| J | Regressão | ✅ | `pest --testsuite=Kit,Tenancy --parallel --processes=8`: 5583 testes, 5575 passed, 3 skipped, 5 failed. As 5 falhas são de paralelismo: `CabecalhoDoPainelTenancyTest` (CT-44/45/57/58) e `AgentesIaNoSettingsChatTest` (CT-16, `rename … storage/framework/views/test_8 … Acesso negado`). Rodados em série, os dois arquivos passam (98/98), e nenhum dos dois está no diff. Ancestral e feature: 51/51. `RoteiroDeAtualizacaoTest`: 212/212. Previsto × medido: bate com o `## Impacto` (só o cadastro) |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 0. A CT-10 tem `not->toBe` sem valor esperado, e o script não o pegou (QA-04). K2 pelo `pestw.cmd`, `--no-cache`: `RespostaDeCadastro` 27 mutantes, 77,78 %, 237 s; `DestinoAposLogin` 139 mutantes, 67,63 %, 528 s. Todos os mutantes das linhas novas (155, 183–189, 264–314) morrem; os sobreviventes estão em `urlPara`/`carimbarAcesso`, que vêm de antes da feature, mais QA-04, log (→D) e a linha 51 (suspeita). Revisão adversarial do `04` não independente |
| L | Consistência documental | ⚠️ | QA-10 e QA-12 (L4). L1 `ids-ct.sh` exit 0. L2 `citacoes.sh` exit 1, só no `06` do ciclo 1 (citação sem símbolo, texto do gate, substituído por este). As citações das docs (`urlPara:88`, `:96`, `:100`, `:101`, `entrarEm:196`) batem. L3: `01`/`02` conferidos contra o código. L4 `conformidade-rules.sh` exit 0 (8 rules); "aplicada" em `testes.md` vale, com QA-12. L5: pt e en concordam; o CHANGELOG e o roteiro batem com P-04/P-05. L6 `checkbox-sem-evidencia.sh` exit 0; números reproduzidos: 51/51, 212/212, `phpstan` errors 0, os exits dos scripts e as "8 rules". L7: "sem vínculo" e "organização" batem com o glossário. L8 n/a: sem `08` e `Roteiro manual: não` |

## Débitos Aceitos

- QA-04 (Minor, destino 3): mutante `77cde9e9d7d2e8c9` (`regenerateToken`) vivo.
- QA-10 (Minor, destino 1): Tabela de Decisão e Q1 do `04` desatualizadas.
- QA-12 (Cosmético, destino 2): docblocks dos helpers.
- Os três vão para o `03`.

## Suspeitas Não Confirmadas

- Sobrevivente `9091a408acdb5a86` (`app/Http/Responses/RespostaDeCadastro.php:toResponse:51`, `RemoveEarlyReturn` do ramo ligado). Continua do ciclo 1: os testes da feature só têm a CT-14 no modo ligado. Deve morrer pelos testes da ancestral, mas não confirmei.

## Não Verificado

- E — motivo: o Pest 5.0.5 não expõe `--agent` e o Boost MCP (`database-query`) falhou ao conectar. A contagem de queries não foi medida.
- K2 — motivo: prova indispensável pendente. Os mutantes "mortos" no Windows só valem com amostragem manual (`.ai/rules/testes.md`, memória do projeto), e este gate não altera arquivo. Os sobreviventes são confiáveis; o score não.
- K — motivo: a revisão adversarial do `04` não é independente. O cabeçalho do `04` diz "duas rodadas internas da própria derivação (Codex), não por `fw-adversario-ct`".
- L6 parcial — "Pré-validação cenário 1 … 1156 pulados" e o teto "no máximo 1156 pulados" do roteiro v0.52.1 não foram reproduzidos: exigem a suíte fora da árvore do kit (`git archive`).
- F/P-05 no app — motivo: o cenário de zero painéis exige um papel sem painel, e criar papel altera dados além de contas de teste. Coberto só pela CT-10.
- Dinâmico com tenancy ligada (CT-04/06/07/11/15/16) — motivo: o app local está com `KIT_TENANCY=false`. Coberto só pela suíte Tenancy.
- Playwright MCP — motivo: indisponível. Sem impacto, porque não há UI nova.

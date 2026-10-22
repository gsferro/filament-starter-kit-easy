# Relatório de QA — onboarding-guiado: jornadas semeadas, interruptor no settings e rule de manutenção

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (UI com JS: checklist Livewire e tour Alpine)
> Natureza da wiki: nova · Toca infra compartilhada: sim → três `PanelProvider`, `KitInstall`, `KitUpdate`, `KitServiceProvider`, settings do kit · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 9 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 2

**REPROVADO → especificação**

- Blocker: 1 · Major: 2 · Minor: 4 · Cosmético: 0
- Destino de cada Blocker/Major: QA-08 (Blocker) → 3; QA-09 (Major) → 3; QA-10 (Major) → 1. O veredito vai para "especificação" pela regra de prioridade (especificação > teste > implementação), mas o defeito mais grave é o QA-08, que é de teste e depois de implementação.
- Não verificadas: G, H, K. As causas estão em *Não Verificado*.
- `RQ` abertas: nenhuma. Q11–Q17 e Q21–Q28 continuam abertas ao solicitante e sustentam P-04..P-19, uma forma admitida.
- Ambiente: app em `http://127.0.0.1:8011`, o kit confirmado, com SQLite descartável, tutorial ligado e semeado · Pest 5.0.5, PHP 8.4.25, PCOV e Xdebug carregados · MCP: Playwright indisponível; Boost não usado (as consultas foram por `tinker` no banco descartável)
- Convergência: é o ciclo 2 de 3 e trouxe achados novos. Se o ciclo 3 reprovar, o caso vai ao usuário.

## Achados

### QA-08 — A página "Primeiros passos" responde 500 depois do primeiro passo concluído · Blocker · destino 3

- **Dimensão**: A (RQ com CT verde e app errado) e J (RCRCRC, Configuration)
- **Relacionado a**: RQ-28, P-06, CT-16, CT-37, CT-47, RM-05, passo 3 do PRD
- **Esperado**: pela RQ-28 e pela Q8, com o tutorial ligado a página "Primeiros passos" aparece nos três painéis e mostra o progresso da jornada.
- **Observado**: a página dá `TypeError` (HTTP 500) assim que a pessoa conclui qualquer passo da jornada daquele painel. O passo pode ser concluído por tour, por visita ou por condição. A administradora quebra a própria página só por abrir as Configurações, que é o passo concluído "por visita". O defeito fica até o progresso ser apagado. Causa: o kit fixa `Date::use(CarbonImmutable::class)` (`app/Providers/KitServiceProvider.php:CarbonImmutable:294`); o pacote declara `completedAt(): ?Illuminate\Support\Carbon` (`vendor/wallacemartinss/filament-onboarding/src/States/StepState.php:completedAt:151`); e a view publicada chama esse método (`resources/views/vendor/filament-onboarding/pages/progress.blade.php:completedAt:230`). Nenhum CT abre a página com um passo concluído e visível. O CT-18 conclui um passo do `/infra` e abre a página do `/admin`, onde o passo não aparece.
- **Repro**:
  1. Íris (`infra`) faz `GET /infra/onboarding` → 200, "0 de 15".
  2. No checklist da mesma tela, `finishTour('pulse')` pelo POST Livewire → 200.
  3. Íris faz `GET /infra/onboarding` de novo → 500.
  4. A administradora (`admin`) abre `/admin/configuracoes-da-aplicacao` e depois `/admin/onboarding` → 500.
- **Evidência**: o corpo do 500 diz `StepState::completedAt(): Return value must be of type ?Illuminate\Support\Carbon, Carbon\CarbonImmutable returned (View: …/progress.blade.php)`. O `storage/logs/laravel.log` traz o mesmo erro para `userId` 1 e 2.
- **Ação exigida**: invocar a `feature-test-design` para um CT que abre `/{painel}/onboarding` com passo concluído por tour, por visita e por condição, nos três painéis. O CT tem de falhar primeiro. Só então corrigir: por exemplo, a view publicada do kit lê `completed_at` sem o retorno tipado. Os testes sobem o mesmo `KitServiceProvider`, então o CT reproduz o defeito.

### QA-09 — A visibilidade `tela:{chave}` faz N+1 em toda tela dos painéis · Major · destino 3

- **Dimensão**: E (e L3)
- **Relacionado a**: P-11, passo 4 do PRD, `## Modelo de Execução` do `01`
- **Esperado**: o `01` diz que, com o tutorial ligado, o custo é "o custo do pacote, inalterado". Pela E, a contagem de queries não cresce com o número de registros.
- **Observado**: `/admin/configuracoes-da-aplicacao`, como `admin`:
  - desligado: 26 queries, nenhuma em `onboarding_*` (bate com o `01`);
  - ligado: 53 queries, das quais 35 são `select exists(… roles inner join model_has_roles …)` (com o tutorial desligado são 18);
  - com 10 passos a mais com `tela:usuarios`: 86 queries, 65 delas de papel (3 por passo);
  - controle, com 10 passos a mais sem condição: 56 queries, 35 de papel (+0).

  A causa é o `canAccess()` da tela, chamado por passo e por request em `app/Support/TutorialGuiado.php:abreATelaDo:102`. O checklist é montado pelo render hook em todas as telas.
- **Repro**: no banco descartável, um GET por processo pelo `tinker`, com `kit.onboarding.habilitado` alternado em memória, contando o `DB::getQueryLog()`. Os passos extras foram criados e depois apagados (o banco voltou a 32 passos).
- **Ação exigida**: um CT de contagem de queries que não cresce com o número de passos (destino 3). Depois, memoizar por request a resposta do acesso por tela (destino 2). Corrigir também o `## Modelo de Execução` do `01`, com data.

### QA-10 — O PRD descreve a recusa do checklist e o log dela como antes do step 9 · Major · destino 1

- **Dimensão**: L (L3)
- **Relacionado a**: RQ-30, passo 3 do PRD (item "Checklist já montado" e `**Logs**`), RD-03, CR2-08
- **Esperado**: o PRD diz o que o código faz ou traz a marca `*(alterado em …)*`.
- **Observado**:
  - O `01` diz que o `hydrate()` faz `abort(404)` "quando o interruptor está desligado". O log de lá é `'… Ação do checklist recusada com o tutorial desligado | user_id: '` com `motivo` fixo.
  - O código recusa quando `TutorialGuiado::visivel()` é falso, isto é, interruptor desligado ou permissão da página retirada. A mensagem é `'… Ação do checklist recusada | motivo: '` e o `motivo` vale `sem_permissao_da_pagina` ou `tutorial_desligado` (`app/Livewire/ChecklistDoOnboarding.php:hydrate:28`).
  - O warning do `updatedFlowKey` não aparece no `01`.
  - O desvio só está registrado no `03` (`## Desvios do Plano`, 2º item, e RD-03).
- **Repro**: `grep -rn "Ação do checklist recusada" wikis/specs/feat/onboarding-guiado/onboarding-guiado/01-plano-acao.md app/Livewire/ChecklistDoOnboarding.php`
- **Ação exigida**: atualizar o item e os `**Logs**` do passo 3 do `01`, incluindo o log do `updatedFlowKey`, e marcar a data.

### QA-11 — Um `flowKey` vindo do cliente forja linhas no log `onboarding` · Minor · destino 3

- **Dimensão**: I (além do step 9; nasceu da correção CR2-08)
- **Relacionado a**: P-20, CT-66
- **Observado**: o valor do cliente é concatenado na mensagem e vai também no context (`app/Livewire/ChecklistDoOnboarding.php:updatedFlowKey:68`). O formatter do Laravel mantém as quebras de linha. Com um `flowKey` contendo `\n[2026-10-21 23:59:59] local.CRITICAL: …`, o `onboarding-2026-10-21.log` ganhou uma linha `CRITICAL` falsa.
- **Repro**: como `admin`, numa tela com checklist, fazer o POST Livewire com `updates: {"flowKey": "qa-gate\n[2026-10-21 23:59:59] local.CRITICAL: [KitInstall@handle] linha forjada…"}` e depois `tail -3 storage/logs/onboarding-*.log`.
- **Ação exigida**: um CT mostrando que um `flowKey` com quebra de linha não gera linha nova no canal. Depois, não interpolar o valor do cliente (ou sanitizá-lo e truncá-lo).

### QA-12 — O oráculo do CT-16 está defasado, e o teste afrouxou a linha do `/app` · Minor · destino 3

- **Dimensão**: K / L1
- **Relacionado a**: CT-16, CT-37, P-22, M40, M155
- **Observado**:
  - O `04` diz que, no `/app`, a Ana vê "6 passos" e que "o título da jornada de <outro painel> não está no corpo".
  - Pelo HTTP, com todas as features ligadas, a Ana vê 5 passos: o do dashboard dinâmico some pela P-22, porque o `panel_user` não tem `Manage:Dashboard`. O `master_global` vê 6.
  - As três jornadas se chamam "Primeiros passos", então o oráculo por título nunca falha. O teste confere a chave, não o título.
  - Na linha `app`, o teste afirma `1..6` em vez de 5 (`tests/Kit/OnboardingGuiadoConsumoTest.php:toBeLessThanOrEqual:185`) e cita a P-11.
  - O rótulo do dataset do CT-37, `'200, 11 passos'` (linha 310 de `tests/Tenancy/OnboardingGuiadoTenancyTest.php`), ficou para trás do `04`, que já tinha sido reescrito.
- **Mitigação medida**: o mutante MA (passo do hub escondido no `/app`) morre no CT-27, no CT-53 e no CT-69. A lacuna é de oráculo e de documento, não de detecção.
- **Ação exigida**: passar pela `feature-test-design` para refazer a linha `app` do CT-16 (5, origem P-22) e trocar "título" por "chave". O teste afirma o valor exato; corrigir também o rótulo do dataset.

### QA-13 — Esperados do roteiro manual que o testador não consegue conferir · Minor · destino 1

- **Dimensão**: L (L8)
- **Relacionado a**: RM-03, RM-05, RM-06
- **Observado**:
  - A RM-03 e a RM-05 conferem o painel pelo título da jornada, e o título é igual nos três painéis.
  - As contagens da RM-05 (11 e 15) e da RM-06 (10, 11) exigem hub, dashboard dinâmico, dock e agentes de IA ligados. A `## Preparação` só define `KIT_ONBOARDING` e `KIT_TENANCY`. Com a preparação como está, a administradora vê 7. Com hub, dashboard e dock ligados e os agentes desligados, observei 9 e 10.
- **Ação exigida**: a preparação diz quais features ligar e onde, ou os esperados passam a ser relativos ao controle. Trocar o título por algo que distinga o painel.

### QA-14 — `02` e docs com rastros envelhecidos · Minor · destino 1

- **Dimensão**: L (L3/L5)
- **Observado**:
  - A abertura do `02` diz "D1 a D9", mas o `01` tem D1..D13 e D7b.
  - Na primeira linha da Superfície Livewire, "ver Q10/P-04" deveria ser RQ-30; a P-04 é o 404 da página.
  - A abertura do `02` repete um parêntese.
  - Na tabela de tenancy de `docs/{pt,en}/recursos/tutorial-guiado.md`, a coluna "Some quando" está "—", mas a P-12 e o código (`multi_tenant_ligado` / `tela:{chave}`) escondem esses passos quando a multi-organização é desligada depois de semear.
- **Ação exigida**: alinhar o `02`. As docs pt e en passam a dizer "o modo multi-tenant foi desligado".

## Ciclo 1 (histórico)

| ID | Sev. | Situação no ciclo 2 |
|---|---|---|
| QA-01 | Major | corrigido: `01` (passos 3/4/5) e `03` (grep das condições antigas só acha marcas de alteração) |
| QA-02 | Major | corrigido: `--filter=Onboarding` → 293/293 (reproduzido de novo) |
| QA-03 | Minor | corrigido: `grep -c "tests/Kit/OnboardingGuiadoTest.php"` no `04` → 0 |
| QA-04 | Minor | corrigido: RM-02 → RQ-10; RM-07 → RQ-16, RQ-17, P-04; base atualizada |
| QA-05 | Minor | corrigido: a rule manda passar `tela:{chave antiga}`, e o `passo()` deixa os atributos vencerem o padrão |
| QA-06 | Minor | corrigido: glossário com Tutorial guiado, Jornada e Passo |
| QA-07 | Cosm. | corrigido: D13, D9, "Adendos 1, 2 e 3" |

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ❌ | QA-08 (RQ-28 com CT verde e app quebrado). `rastreabilidade.sh` exit 0; `indice.sh --check` n/a (sem `07-tickets/`). |
| B | Fronteiras e dados | ✅ | Livewire do checklist sondado pelo POST com HTML, 600 caracteres, emoji, chave de outra jornada, passo inexistente, passo por condição, índice −5 / total 2³¹−1 e total 0. Nada gravado fora do próprio progresso; os achados de fronteira estão em I (QA-11). |
| C | Matriz de permissão | ✅ | Ligado: autoria 200 para `master_global`/`admin` e 403 para `infra`/`panel_user`; página 403 sem `View:PrimeirosPassos` (RM-08). Desligado: 404 para todos, inclusive `master_global` (RM-07). |
| D | Observabilidade | ✅ | Log real em `onboarding-2026-10-21.log`: prefixo `[Classe@Método]`, `warning` nas recusas, `info`/`debug` no seeder (64 debug = 2 × 32), context `user_id`/`rota`/`motivo`/`flow`, sem PII. A injeção está em QA-11. |
| E | Performance | ❌ | QA-09. Desligado: zero query em `onboarding_*`, como diz o `01`. |
| F | UX de erro | ✅ | 404 e 403 em pt com saída ("Início", "Voltar", pedido de permissão); textos do settings e do `kit:install` dizem o quê e como resolver. O 500 da página está em QA-08. |
| G | Tema e cor | ⏭️ | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`); nível 1 exit 0. Níveis 2 e 3 não verificados (ver *Não Verificado*). |
| H | Acessibilidade | ⏭️ | Ver *Não Verificado*. |
| I | Segurança da superfície nova | ⚠️ | O step 9 cobriu (25 linhas, 5 rejeitadas). Além dele: IDOR dinâmico (`completeStep` com jornada do `/infra` a partir do `/admin` → nada gravado; `flowKey` forjado volta para nulo); 404/403 com saída; nenhuma rota nova sem `can`; nenhum model novo. QA-11. |
| J | Regressão adjacente | ⚠️ | 6 arquivos da feature 276/276 (214 s); `--filter=Onboarding` 293/293. Suíte inteira não reexecutada: o código é o mesmo do ciclo 1 (`git diff 259732f8 5f66c820` só toca wiki, rule e glossário), e lá deu 5321. RCRCRC, Configuration: `Date::use(CarbonImmutable)` × pacote → QA-08. |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 1: os mesmos 8 candidatos do ciclo 1, rejeitados (anteriores à feature). K2 manual: 6 mutantes aplicados por `auto_prepend_file` com cópia em scratchpad, sem tocar a árvore; baseline 276 verdes. MA morto (CT-27/53/69), MB morto (CT-45, CT-37), MC morto (CT-13/43/63), MD morto (CT-30), MH morto (CT-03/04/11/44/58…), MG (log removido do middleware) sobreviveu e foi triado para D. 255–288 s por mutante, próximo da baseline. Revisão adversarial do `04` feita. |
| L | Consistência documental | ❌ | QA-10, QA-12, QA-13, QA-14. L1 `ids-ct.sh` exit 0 (cabeçalho 71 · 32 · 192 · 0 reproduzido). L2 `citacoes.sh` exit 1: 8 linhas, todas no `06` do ciclo 1 (citação sem símbolo do próprio gate), que este `06` substitui. L4 `conformidade-rules.sh` exit 0. L6 `checkbox-sem-evidencia.sh` exit 0; números reproduzidos: 293, 29 arquivos, 3 providers, 6 filhos de tenancy, 71+2. L7 sem divergência. L8 `roteiro-manual.sh` exit 0. RM executados: 01, 02, 03, 05 (parcial), 06, 07, 08, 09. |

## Débitos Aceitos

- Nenhum: o veredito é REPROVADO.

## Suspeitas Não Confirmadas

- Rejeitado: `completeStep` sem argumento → 500 (`ArgumentCountError`). É comportamento genérico do Livewire em qualquer componente, não desta feature.
- Rejeitado: `tourProgress` com total 2³¹−1 grava `tour_total` enorme. Só no progresso da própria pessoa, comportamento do pacote já documentado no `02`.
- O `--mutate` do plugin no ciclo 1 "matou" mutantes de log. Pelo arnês manual deste ciclo, o equivalente (MG) sobrevive, o que confirma morte falsa do plugin no Windows.

## Não Verificado

- **G**, níveis 2 e 3: nenhum CT-B em `inDarkMode()` (o CT-B usa só `inLightMode()`) e Playwright MCP indisponível. Toda a UI é do vendor.
- **H**: nenhum `assertNoAccessibilityIssues()` e nenhum MCP para teclado e foco.
- **K2**: o mutation score do plugin não é certificável no Windows (morte falsa confirmada acima). Só há a amostra manual de 6 mutantes; não há score para comparar ao piso de 70 %.
- **RM-04**, popover do tour: exige navegador e não há MCP. O CT-B01 continua sendo a prova automatizada.
- **RM-05, linha da administradora**: não executável depois do primeiro passo concluído (QA-08). A linha da Íris (15) foi conferida antes de ela concluir um passo.
